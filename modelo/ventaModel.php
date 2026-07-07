<?php
// modelo/ventaModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class VentaModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function asignarCliente(int $venta_id, int $cliente_id): bool {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "UPDATE ventas SET cliente_id = :cid WHERE id = :id AND comercio_id = {$this->cid}"
            );
            return (bool)$stmt->execute([':cid' => $cliente_id, ':id' => $venta_id]);
        } catch (PDOException $e) {
            error_log("Error asignarCliente: " . $e->getMessage());
            return false;
        }
    }

    // ── Recetas con sus ingredientes y stock actual ────────────────────────
    public function obtenerRecetasConIngredientes() {
        $this->requireCid();
        $recetas = $this->db->query(
            "SELECT id, nombre, descripcion, categoria, tiempo_preparacion, porciones, precio_venta, activo, foto
             FROM recetas WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY categoria, nombre"
        )->fetchAll();

        $stmtIng = $this->db->prepare(
            "SELECT ri.id_insumo, ri.cantidad,
                    i.nombre AS insumo_nombre, i.unidad_medida,
                    i.cantidad_stock, i.cantidad_minima, i.categoria AS cat_insumo
             FROM receta_insumos ri
             JOIN insumos i ON ri.id_insumo = i.id AND i.comercio_id = {$this->cid}
             WHERE ri.id_receta = :id AND ri.comercio_id = {$this->cid}"
        );

        foreach ($recetas as &$receta) {
            $stmtIng->execute([':id' => $receta['id']]);
            $receta['ingredientes'] = $stmtIng->fetchAll();
        }

        return $recetas;
    }

    // ── Registrar venta directa (sin mesa) ────────────────────────────────
    public function registrarVenta($items, $notas, $id_usuario) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $count  = (int)$this->db->query(
                "SELECT COUNT(*) FROM ventas WHERE comercio_id = {$this->cid}"
            )->fetchColumn();
            $numero = 'V-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $total = array_reduce($items, fn($c, $it) => $c + ($it['precio_unitario'] * $it['cantidad']), 0);

            $stmt = $this->db->prepare(
                "INSERT INTO ventas (comercio_id, numero_orden, total, notas, estado, id_usuario)
                 VALUES ({$this->cid}, :numero, :total, :notas, 'abierta', :id_usuario)"
            );
            $stmt->execute([':numero' => $numero, ':total' => $total, ':notas' => $notas, ':id_usuario' => $id_usuario]);
            $id_venta = (int)$this->db->lastInsertId();

            $consumo = [];
            foreach ($items as $item) {
                foreach ($item['ingredientes'] as $ing) {
                    $id_ins = (int)$ing['id_insumo'];
                    $cant   = (float)$ing['cantidad'] * (int)$item['cantidad'];
                    $consumo[$id_ins] = ($consumo[$id_ins] ?? 0) + $cant;
                }
            }

            foreach ($consumo as $id_ins => $necesario) {
                $row = $this->db->query(
                    "SELECT cantidad_stock, nombre FROM insumos WHERE id = $id_ins AND comercio_id = {$this->cid} FOR UPDATE"
                )->fetch();
                if (!$row || $row['cantidad_stock'] < $necesario) {
                    $this->db->rollBack();
                    $nombre = $row['nombre'] ?? "ID $id_ins";
                    return ['ok' => false, 'msg' => "Stock insuficiente: $nombre (disponible: " . ($row['cantidad_stock'] ?? 0) . ", necesario: $necesario)"];
                }
            }

            $stmtDet = $this->db->prepare(
                "INSERT INTO venta_detalle (comercio_id, id_venta, id_receta, cantidad, precio_unitario, subtotal)
                 VALUES ({$this->cid}, :id_venta, :id_receta, :cantidad, :precio, :subtotal)"
            );
            foreach ($items as $item) {
                $stmtDet->execute([
                    ':id_venta'  => $id_venta,
                    ':id_receta' => $item['id_receta'],
                    ':cantidad'  => $item['cantidad'],
                    ':precio'    => $item['precio_unitario'],
                    ':subtotal'  => $item['precio_unitario'] * $item['cantidad'],
                ]);
            }

            $stmtUpd = $this->db->prepare(
                "UPDATE insumos SET cantidad_stock = cantidad_stock - :cant WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_insumos (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario)
                 SELECT {$this->cid}, :id_insumo, 'salida', :cantidad,
                        cantidad_stock + :cantidad2, cantidad_stock, :desc, :id_usuario
                 FROM insumos WHERE id = :id_insumo2 AND comercio_id = {$this->cid}"
            );

            foreach ($consumo as $id_ins => $cant) {
                $stmtUpd->execute([':cant' => $cant, ':id' => $id_ins]);
                $stmtMov->execute([
                    ':id_insumo'  => $id_ins,
                    ':cantidad'   => $cant,
                    ':cantidad2'  => $cant,
                    ':desc'       => "Venta $numero",
                    ':id_usuario' => $id_usuario,
                    ':id_insumo2' => $id_ins,
                ]);
            }

            $this->db->commit();
            return ['ok' => true, 'numero' => $numero, 'id_venta' => $id_venta, 'total' => $total];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("Error registrarVenta: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al registrar: ' . $e->getMessage()];
        }
    }

    // ── Historial de ventas ───────────────────────────────────────────────
    public function obtenerVentas($limite = 50) {
        $this->requireCid();
        $sql = "SELECT v.*, u.nombre AS usuario_nombre,
                    COUNT(vd.id) AS total_platos
                FROM ventas v
                LEFT JOIN usuarios u ON v.id_usuario = u.id AND u.comercio_id = {$this->cid}
                LEFT JOIN venta_detalle vd ON v.id = vd.id_venta AND vd.comercio_id = {$this->cid}
                WHERE v.comercio_id = {$this->cid}
                GROUP BY v.id
                ORDER BY v.created_at DESC
                LIMIT :lim";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obtenerVentas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerDetalleVenta($id_venta) {
        $this->requireCid();
        $sql = "SELECT vd.*, r.nombre AS receta_nombre, r.categoria
                FROM venta_detalle vd
                JOIN recetas r ON vd.id_receta = r.id AND r.comercio_id = {$this->cid}
                WHERE vd.id_venta = :id AND vd.comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id_venta, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obtenerDetalleVenta: " . $e->getMessage());
            return [];
        }
    }

    // ── Mesas con totales sumados de todas sus órdenes activas ───────────
    public function obtenerMesasConOrdenes() {
        $this->requireCid();
        $sql = "SELECT m.*,
                    MAX(v.id)                     AS venta_id,
                    COALESCE(SUM(v.total), 0)     AS orden_total,
                    COALESCE(SUM(cnt.items_count),0) AS items_count,
                    MIN(v.created_at)          AS orden_inicio,
                    (SELECT v2.estado FROM ventas v2
                     WHERE v2.id_mesa = m.id AND v2.comercio_id = {$this->cid}
                       AND v2.estado IN ('abierta','en_preparacion','lista')
                     ORDER BY v2.created_at DESC LIMIT 1) AS orden_estado
                FROM mesas m
                LEFT JOIN ventas v ON v.id_mesa = m.id AND v.comercio_id = {$this->cid}
                    AND v.estado IN ('abierta','en_preparacion','lista')
                LEFT JOIN (
                    SELECT id_venta, COUNT(*) AS items_count
                    FROM venta_detalle WHERE comercio_id = {$this->cid} GROUP BY id_venta
                ) cnt ON cnt.id_venta = v.id
                WHERE m.activo = 1 AND m.comercio_id = {$this->cid}
                GROUP BY m.id
                ORDER BY m.numero ASC";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obtenerMesasConOrdenes: " . $e->getMessage());
            return [];
        }
    }

    // ── Todas las órdenes activas de una mesa (con items) ─────────────────
    public function obtenerOrdenesActivasMesa($id_mesa) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM ventas WHERE id_mesa = :id AND comercio_id = {$this->cid}
                 AND estado IN ('abierta','en_preparacion','lista')
                 ORDER BY created_at ASC"
            );
            $stmt->execute([':id' => (int)$id_mesa]);
            $ventas = $stmt->fetchAll();

            $stmtDet = $this->db->prepare(
                "SELECT vd.*, r.nombre AS receta_nombre, r.categoria, r.precio_venta
                 FROM venta_detalle vd
                 JOIN recetas r ON r.id = vd.id_receta AND r.comercio_id = {$this->cid}
                 WHERE vd.id_venta = :id AND vd.comercio_id = {$this->cid}
                 ORDER BY r.categoria, r.nombre"
            );
            foreach ($ventas as &$venta) {
                $stmtDet->execute([':id' => $venta['id']]);
                $venta['items'] = $stmtDet->fetchAll();
            }
            return $ventas;
        } catch (PDOException $e) {
            error_log("Error obtenerOrdenesActivasMesa: " . $e->getMessage());
            return [];
        }
    }

    // ── Crear nueva orden de mesa ─────────────────────────────────────────
    public function guardarOrdenMesa($id_mesa, $items, $notas, $id_usuario) {
        $this->requireCid();

        foreach ($items as $item) {
            if ((float)($item['precio_unitario'] ?? 0) <= 0) {
                return ['ok' => false, 'msg' => 'Hay productos sin precio configurado.'];
            }
        }

        $stmtYaOrdenado = $this->db->prepare(
            "SELECT COALESCE(SUM(vd.cantidad), 0)
             FROM venta_detalle vd
             JOIN ventas v ON v.id = vd.id_venta
             WHERE vd.id_receta = :rid AND vd.comercio_id = {$this->cid}
               AND v.id_mesa   = :mesa AND v.comercio_id = {$this->cid}
               AND v.estado    IN ('abierta','en_preparacion','lista')"
        );
        $stmtIng = $this->db->prepare(
            "SELECT ri.id_insumo, ri.cantidad, i.nombre, i.cantidad_stock
             FROM receta_insumos ri
             JOIN insumos i ON i.id = ri.id_insumo AND i.comercio_id = {$this->cid}
             WHERE ri.id_receta = :rid AND ri.comercio_id = {$this->cid}"
        );
        $stmtReceta = $this->db->prepare(
            "SELECT nombre FROM recetas WHERE id = :id AND comercio_id = {$this->cid}"
        );

        foreach ($items as $item) {
            $id_receta = (int)$item['id_receta'];
            $cantNueva = (int)$item['cantidad'];

            $stmtYaOrdenado->execute([':rid' => $id_receta, ':mesa' => $id_mesa]);
            $cantActiva = (int)$stmtYaOrdenado->fetchColumn();
            $cantTotal  = $cantActiva + $cantNueva;

            $stmtIng->execute([':rid' => $id_receta]);
            $ingredientes = $stmtIng->fetchAll();

            foreach ($ingredientes as $ing) {
                $req = (float)$ing['cantidad'];
                if ($req <= 0) continue;
                $maxPorciones = (int)floor((float)$ing['cantidad_stock'] / $req);
                if ($cantTotal > $maxPorciones) {
                    $stmtReceta->execute([':id' => $id_receta]);
                    $nomReceta   = $stmtReceta->fetchColumn() ?: "Receta $id_receta";
                    $disponibles = max(0, $maxPorciones - $cantActiva);
                    return [
                        'ok'  => false,
                        'msg' => "Stock insuficiente para \"$nomReceta\": "
                               . "solo quedan $disponibles porción(es) disponibles "
                               . "(ingrediente limitante: {$ing['nombre']})",
                    ];
                }
            }
        }

        try {
            $this->db->beginTransaction();

            $total  = array_reduce($items, fn($c, $it) =>
                $c + ((float)$it['precio_unitario'] * (int)$it['cantidad']), 0.0);
            $count  = (int)$this->db->query(
                "SELECT COUNT(*) FROM ventas WHERE comercio_id = {$this->cid}"
            )->fetchColumn();
            $numero = 'M-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $this->db->prepare(
                "INSERT INTO ventas (comercio_id, numero_orden, total, notas, estado, id_usuario, id_mesa)
                 VALUES ({$this->cid}, :num, :total, :notas, 'abierta', :uid, :mesa)"
            )->execute([
                ':num' => $numero, ':total' => $total,
                ':notas' => $notas, ':uid' => $id_usuario, ':mesa' => $id_mesa,
            ]);
            $id_venta = (int)$this->db->lastInsertId();

            $this->db->prepare(
                "UPDATE mesas SET estado = 'ocupada' WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id_mesa]);

            $stmtDet = $this->db->prepare(
                "INSERT INTO venta_detalle (comercio_id, id_venta, id_receta, cantidad, precio_unitario, subtotal)
                 VALUES ({$this->cid}, :v, :r, :c, :p, :s)"
            );
            foreach ($items as $item) {
                $stmtDet->execute([
                    ':v' => $id_venta,
                    ':r' => (int)$item['id_receta'],
                    ':c' => (int)$item['cantidad'],
                    ':p' => (float)$item['precio_unitario'],
                    ':s' => (float)$item['precio_unitario'] * (int)$item['cantidad'],
                ]);
            }

            $this->db->commit();
            return ['ok' => true, 'id_venta' => $id_venta, 'numero' => $numero, 'total' => $total];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error guardarOrdenMesa: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al guardar la orden'];
        }
    }

    // ── Cobrar orden individual: descuenta stock y cierra ─────────────────
    public function cobrarOrden($id_venta, $id_usuario) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $stmtV = $this->db->prepare(
                "SELECT * FROM ventas WHERE id = :id AND comercio_id = {$this->cid}
                 AND estado IN ('abierta','en_preparacion','lista') FOR UPDATE"
            );
            $stmtV->execute([':id' => $id_venta]);
            $venta = $stmtV->fetch();
            if (!$venta) {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'La orden no existe o ya fue cobrada'];
            }

            $stmtDet = $this->db->prepare(
                "SELECT vd.id_receta, vd.cantidad FROM venta_detalle vd
                 WHERE vd.id_venta = :id AND vd.comercio_id = {$this->cid}"
            );
            $stmtDet->execute([':id' => $id_venta]);
            $itemsList = $stmtDet->fetchAll();

            $consumo = [];
            $stmtIng = $this->db->prepare(
                "SELECT ri.id_insumo, ri.cantidad, i.nombre
                 FROM receta_insumos ri JOIN insumos i ON i.id = ri.id_insumo AND i.comercio_id = {$this->cid}
                 WHERE ri.id_receta = :rid AND ri.comercio_id = {$this->cid}"
            );
            foreach ($itemsList as $item) {
                $stmtIng->execute([':rid' => $item['id_receta']]);
                foreach ($stmtIng->fetchAll() as $ing) {
                    $id_ins = (int)$ing['id_insumo'];
                    $cant   = (float)$ing['cantidad'] * (int)$item['cantidad'];
                    if (!isset($consumo[$id_ins])) {
                        $consumo[$id_ins] = ['cantidad' => 0.0, 'nombre' => $ing['nombre']];
                    }
                    $consumo[$id_ins]['cantidad'] += $cant;
                }
            }

            foreach ($consumo as $id_ins => $info) {
                $row = $this->db->query(
                    "SELECT cantidad_stock, nombre FROM insumos
                     WHERE id = $id_ins AND comercio_id = {$this->cid} FOR UPDATE"
                )->fetch();
                if (!$row || (float)$row['cantidad_stock'] < $info['cantidad']) {
                    $this->db->rollBack();
                    $nombre = $row['nombre'] ?? "ID $id_ins";
                    $disp   = number_format($row['cantidad_stock'] ?? 0, 2);
                    $nec    = number_format($info['cantidad'], 2);
                    return ['ok' => false, 'msg' => "Stock insuficiente: $nombre (disponible: $disp, necesario: $nec)"];
                }
            }

            $numero   = $venta['numero_orden'];
            $stmtUpd  = $this->db->prepare(
                "UPDATE insumos SET cantidad_stock = cantidad_stock - :cant WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmtMov  = $this->db->prepare(
                "INSERT INTO movimientos_insumos (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario)
                 SELECT {$this->cid}, :id_insumo, 'salida', :cantidad,
                        cantidad_stock + :cantidad2, cantidad_stock, :desc, :uid
                 FROM insumos WHERE id = :id2 AND comercio_id = {$this->cid}"
            );
            foreach ($consumo as $id_ins => $info) {
                $stmtUpd->execute([':cant' => $info['cantidad'], ':id' => $id_ins]);
                $stmtMov->execute([
                    ':id_insumo' => $id_ins, ':cantidad'  => $info['cantidad'],
                    ':cantidad2' => $info['cantidad'],
                    ':desc'      => "Cobro $numero", ':uid' => $id_usuario,
                    ':id2'       => $id_ins,
                ]);
            }

            $this->db->prepare(
                "UPDATE ventas SET estado = 'cobrada' WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id_venta]);

            if ($venta['id_mesa']) {
                $this->db->prepare(
                    "UPDATE mesas SET estado = 'disponible' WHERE id = :id AND comercio_id = {$this->cid}"
                )->execute([':id' => $venta['id_mesa']]);
            }

            $this->db->commit();
            return ['ok' => true, 'numero' => $numero, 'total' => $venta['total']];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error cobrarOrden: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al cobrar la orden'];
        }
    }

    // ── Cancelar orden abierta ────────────────────────────────────────────
    public function cancelarOrden($id_venta) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT id_mesa FROM ventas WHERE id = :id AND comercio_id = {$this->cid}
                 AND estado IN ('abierta','en_preparacion','lista')"
            );
            $stmt->execute([':id' => $id_venta]);
            $venta = $stmt->fetch();
            if (!$venta) return ['ok' => false, 'msg' => 'Orden no encontrada'];

            $this->db->beginTransaction();
            $this->db->prepare(
                "UPDATE ventas SET estado = 'cancelada' WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id_venta]);
            if ($venta['id_mesa']) {
                $this->db->prepare(
                    "UPDATE mesas SET estado = 'disponible' WHERE id = :id AND comercio_id = {$this->cid}"
                )->execute([':id' => $venta['id_mesa']]);
            }
            $this->db->commit();
            return ['ok' => true];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error cancelarOrden: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al cancelar'];
        }
    }

    // ── Cocina: órdenes activas ───────────────────────────────────────────
    public function obtenerOrdenesActivas() {
        $this->requireCid();
        $sql = "SELECT v.*,
                    m.numero AS mesa_numero,
                    m.nombre AS mesa_nombre,
                    m.zona   AS mesa_zona,
                    TIMESTAMPDIFF(MINUTE, v.created_at, NOW()) AS minutos_espera
                FROM ventas v
                LEFT JOIN mesas m ON m.id = v.id_mesa AND m.comercio_id = {$this->cid}
                WHERE v.estado IN ('abierta','en_preparacion','lista')
                  AND v.comercio_id = {$this->cid}
                ORDER BY v.created_at ASC";
        try {
            $rows    = $this->db->query($sql)->fetchAll();
            $stmtD   = $this->db->prepare(
                "SELECT vd.cantidad, r.nombre AS receta_nombre, r.categoria
                 FROM venta_detalle vd
                 JOIN recetas r ON r.id = vd.id_receta AND r.comercio_id = {$this->cid}
                 WHERE vd.id_venta = :id AND vd.comercio_id = {$this->cid}
                 ORDER BY r.categoria, r.nombre"
            );
            foreach ($rows as &$v) {
                $stmtD->execute([':id' => $v['id']]);
                $v['items'] = $stmtD->fetchAll();
            }
            return $rows;
        } catch (PDOException $e) {
            error_log("Error obtenerOrdenesActivas: " . $e->getMessage());
            return [];
        }
    }

    public function aceptarOrden($id_venta) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "UPDATE ventas SET estado='en_preparacion'
                 WHERE id=:id AND estado='abierta' AND comercio_id = {$this->cid}"
            );
            $stmt->execute([':id' => (int)$id_venta]);
            return $stmt->rowCount() > 0
                ? ['ok' => true]
                : ['ok' => false, 'msg' => 'Orden no encontrada o ya en proceso'];
        } catch (PDOException $e) {
            error_log("Error aceptarOrden: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error interno'];
        }
    }

    public function marcarListaOrden($id_venta) {
        $this->requireCid();
        try {
            $row = $this->db->prepare(
                "SELECT id_mesa FROM ventas WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $row->execute([':id' => (int)$id_venta]);
            $venta       = $row->fetch();
            $nuevoEstado = ($venta && $venta['id_mesa'] === null) ? 'completada' : 'lista';

            $stmt = $this->db->prepare(
                "UPDATE ventas SET estado=:estado WHERE id=:id AND estado='en_preparacion' AND comercio_id = {$this->cid}"
            );
            $stmt->execute([':estado' => $nuevoEstado, ':id' => (int)$id_venta]);
            return $stmt->rowCount() > 0
                ? ['ok' => true]
                : ['ok' => false, 'msg' => 'Orden no encontrada o no está en preparación'];
        } catch (PDOException $e) {
            error_log("Error marcarListaOrden: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error interno'];
        }
    }

    // ── Cobrar TODAS las órdenes activas de una mesa ─────────────────────
    public function cobrarTodasOrdenesMesa($id_mesa, $id_usuario) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT * FROM ventas WHERE id_mesa = :id AND comercio_id = {$this->cid}
                 AND estado IN ('abierta','en_preparacion','lista') FOR UPDATE"
            );
            $stmt->execute([':id' => (int)$id_mesa]);
            $ventas = $stmt->fetchAll();

            if (empty($ventas)) {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'No hay órdenes activas para esta mesa'];
            }

            $consumo  = [];
            $stmtDet  = $this->db->prepare(
                "SELECT id_receta, cantidad FROM venta_detalle WHERE id_venta = :id AND comercio_id = {$this->cid}"
            );
            $stmtIng  = $this->db->prepare(
                "SELECT ri.id_insumo, ri.cantidad, i.nombre
                 FROM receta_insumos ri JOIN insumos i ON i.id = ri.id_insumo AND i.comercio_id = {$this->cid}
                 WHERE ri.id_receta = :rid AND ri.comercio_id = {$this->cid}"
            );
            foreach ($ventas as $venta) {
                $stmtDet->execute([':id' => $venta['id']]);
                foreach ($stmtDet->fetchAll() as $item) {
                    $stmtIng->execute([':rid' => $item['id_receta']]);
                    foreach ($stmtIng->fetchAll() as $ing) {
                        $id_ins = (int)$ing['id_insumo'];
                        $cant   = (float)$ing['cantidad'] * (int)$item['cantidad'];
                        if (!isset($consumo[$id_ins])) $consumo[$id_ins] = ['cantidad' => 0.0, 'nombre' => $ing['nombre']];
                        $consumo[$id_ins]['cantidad'] += $cant;
                    }
                }
            }

            foreach ($consumo as $id_ins => $info) {
                $row = $this->db->query(
                    "SELECT cantidad_stock, nombre FROM insumos WHERE id = $id_ins AND comercio_id = {$this->cid} FOR UPDATE"
                )->fetch();
                if (!$row || (float)$row['cantidad_stock'] < $info['cantidad']) {
                    $this->db->rollBack();
                    $nombre = $row['nombre'] ?? "ID $id_ins";
                    return ['ok' => false, 'msg' => "Stock insuficiente: $nombre (disponible: " .
                        number_format($row['cantidad_stock'] ?? 0, 2) . ", necesario: " .
                        number_format($info['cantidad'], 2) . ")"];
                }
            }

            $stmtUpd = $this->db->prepare(
                "UPDATE insumos SET cantidad_stock = cantidad_stock - :cant WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_insumos (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario)
                 SELECT {$this->cid}, :id_insumo, 'salida', :cantidad,
                        cantidad_stock + :cantidad2, cantidad_stock, :desc, :uid
                 FROM insumos WHERE id = :id2 AND comercio_id = {$this->cid}"
            );
            foreach ($consumo as $id_ins => $info) {
                $stmtUpd->execute([':cant' => $info['cantidad'], ':id' => $id_ins]);
                $stmtMov->execute([
                    ':id_insumo' => $id_ins, ':cantidad'  => $info['cantidad'],
                    ':cantidad2' => $info['cantidad'],
                    ':desc'      => "Cobro mesa $id_mesa", ':uid' => $id_usuario,
                    ':id2'       => $id_ins,
                ]);
            }

            $ids          = implode(',', array_map('intval', array_column($ventas, 'id')));
            $totalGeneral = (float)$this->db->query(
                "SELECT COALESCE(SUM(subtotal),0) FROM venta_detalle WHERE id_venta IN ($ids) AND comercio_id = {$this->cid}"
            )->fetchColumn();
            $numeros      = [];
            $stmtClose    = $this->db->prepare(
                "UPDATE ventas SET estado = 'cobrada' WHERE id = :id AND comercio_id = {$this->cid}"
            );
            foreach ($ventas as $venta) {
                $stmtClose->execute([':id' => $venta['id']]);
                $numeros[] = $venta['numero_orden'];
            }

            $this->db->prepare(
                "UPDATE mesas SET estado = 'disponible' WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id_mesa]);

            $this->db->commit();
            return [
                'ok'     => true,
                'total'  => $totalGeneral,
                'ordenes'=> count($ventas),
                'numero' => implode(', ', $numeros),
                'ids'    => array_column($ventas, 'id'),
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error cobrarTodasOrdenesMesa: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al cobrar la mesa'];
        }
    }

    // ── Cancelar TODAS las órdenes activas de una mesa ───────────────────
    public function cancelarOrdenesActivas($id_mesa) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();
            $this->db->prepare(
                "UPDATE ventas SET estado = 'cancelada'
                 WHERE id_mesa = :id AND comercio_id = {$this->cid}
                 AND estado IN ('abierta','en_preparacion','lista')"
            )->execute([':id' => (int)$id_mesa]);
            $this->db->prepare(
                "UPDATE mesas SET estado = 'disponible' WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => (int)$id_mesa]);
            $this->db->commit();
            return ['ok' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error cancelarOrdenesActivas: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al cancelar'];
        }
    }

    // ── Cancelar una venta individual desde el historial ─────────────────
    public function cancelarVentaRegistrada($id_venta) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT id, id_mesa, estado FROM ventas WHERE id = :id AND comercio_id = {$this->cid} FOR UPDATE"
            );
            $stmt->execute([':id' => (int)$id_venta]);
            $venta = $stmt->fetch();

            if (!$venta) {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'Venta no encontrada'];
            }
            if ($venta['estado'] === 'cancelada') {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'La venta ya está cancelada'];
            }

            $deducted = ($venta['id_mesa'] === null) || ($venta['estado'] === 'cobrada');

            if ($deducted) {
                $stmtDet = $this->db->prepare(
                    "SELECT id_receta, cantidad FROM venta_detalle
                     WHERE id_venta = :id AND comercio_id = {$this->cid}"
                );
                $stmtDet->execute([':id' => (int)$id_venta]);
                $detalles = $stmtDet->fetchAll();

                $stmtIng = $this->db->prepare(
                    "SELECT ri.id_insumo, ri.cantidad FROM receta_insumos ri
                     WHERE ri.id_receta = :rid AND ri.comercio_id = {$this->cid}"
                );
                $stmtUpd = $this->db->prepare(
                    "UPDATE insumos SET cantidad_stock = cantidad_stock + :cant
                     WHERE id = :id AND comercio_id = {$this->cid}"
                );

                foreach ($detalles as $det) {
                    $stmtIng->execute([':rid' => $det['id_receta']]);
                    foreach ($stmtIng->fetchAll() as $ing) {
                        $restorer = (float)$ing['cantidad'] * (int)$det['cantidad'];
                        $stmtUpd->execute([':cant' => $restorer, ':id' => (int)$ing['id_insumo']]);
                    }
                }
            }

            $this->db->prepare(
                "UPDATE ventas SET estado = 'cancelada' WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => (int)$id_venta]);

            $this->db->commit();
            return ['ok' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error cancelarVentaRegistrada: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error interno'];
        }
    }

    // ── Estado público de una orden (polling desde menú digital) ─────────
    public function obtenerEstadoOrden(int $id): ?array {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT v.id, v.numero_orden, v.estado, v.total, v.created_at,
                        m.numero AS mesa_numero, m.nombre AS mesa_nombre
                 FROM ventas v LEFT JOIN mesas m ON m.id = v.id_mesa AND m.comercio_id = {$this->cid}
                 WHERE v.id = :id AND v.comercio_id = {$this->cid}"
            );
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    // ── Orden completa con items (para factura pública) ───────────────────
    public function obtenerOrdenConItems(int $id): ?array {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT v.*, m.numero AS mesa_numero, m.nombre AS mesa_nombre
                 FROM ventas v LEFT JOIN mesas m ON m.id = v.id_mesa AND m.comercio_id = {$this->cid}
                 WHERE v.id = :id AND v.comercio_id = {$this->cid}"
            );
            $stmt->execute([':id' => $id]);
            $venta = $stmt->fetch();
            if (!$venta) return null;
            $stmtD = $this->db->prepare(
                "SELECT vd.cantidad, vd.precio_unitario, vd.subtotal, r.nombre AS receta_nombre
                 FROM venta_detalle vd JOIN recetas r ON r.id = vd.id_receta AND r.comercio_id = {$this->cid}
                 WHERE vd.id_venta = :id AND vd.comercio_id = {$this->cid}
                 ORDER BY r.nombre"
            );
            $stmtD->execute([':id' => $id]);
            $venta['items'] = $stmtD->fetchAll();
            return $venta;
        } catch (\Throwable $e) { return null; }
    }

    // ── Resumen de todos los pedidos de una mesa (hoy, no cancelados) ─────
    public function obtenerResumenMesa(int $mesaId): array {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT r.nombre AS producto,
                        SUM(vd.cantidad) AS cantidad,
                        SUM(vd.subtotal) AS subtotal
                 FROM ventas v
                 JOIN venta_detalle vd ON vd.id_venta = v.id AND vd.comercio_id = {$this->cid}
                 JOIN recetas r        ON r.id = vd.id_receta AND r.comercio_id = {$this->cid}
                 WHERE v.id_mesa = :mesa AND v.comercio_id = {$this->cid}
                   AND v.estado NOT IN ('cancelada')
                   AND DATE(v.created_at) = CURDATE()
                 GROUP BY r.id, r.nombre
                 ORDER BY r.nombre"
            );
            $stmt->execute([':mesa' => $mesaId]);
            $items = $stmt->fetchAll();
            return ['items' => $items, 'total' => (float)array_sum(array_column($items, 'subtotal'))];
        } catch (\Throwable $e) { return ['items' => [], 'total' => 0.0]; }
    }

    // ── Orden completa de toda la mesa (para factura combinada) ──────────
    public function obtenerOrdenCompletaMesa(int $mesaId): array {
        $this->requireCid();
        try {
            $stmtMesa = $this->db->prepare(
                "SELECT numero, nombre FROM mesas WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmtMesa->execute([':id' => $mesaId]);
            $mesa = $stmtMesa->fetch() ?: [];

            $stmt = $this->db->prepare(
                "SELECT r.nombre AS receta_nombre,
                        SUM(vd.cantidad)  AS cantidad,
                        SUM(vd.subtotal)  AS subtotal,
                        AVG(vd.precio_unitario) AS precio_unitario
                 FROM ventas v
                 JOIN venta_detalle vd ON vd.id_venta = v.id AND vd.comercio_id = {$this->cid}
                 JOIN recetas r        ON r.id = vd.id_receta AND r.comercio_id = {$this->cid}
                 WHERE v.id_mesa = :mesa AND v.comercio_id = {$this->cid}
                   AND v.estado NOT IN ('cancelada')
                   AND DATE(v.created_at) = CURDATE()
                 GROUP BY r.id, r.nombre
                 ORDER BY r.nombre"
            );
            $stmt->execute([':mesa' => $mesaId]);
            $items = $stmt->fetchAll();
            return [
                'mesa_numero'    => $mesa['numero'] ?? '—',
                'mesa_nombre'    => $mesa['nombre'] ?? '',
                'numero_orden'   => 'Mesa ' . ($mesa['numero'] ?? ''),
                'items'          => $items,
                'total'          => (float)array_sum(array_column($items, 'subtotal')),
                'fecha_creacion' => date('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0.0, 'mesa_numero' => '—', 'mesa_nombre' => '', 'numero_orden' => '', 'fecha_creacion' => ''];
        }
    }

    // ── Listado paginado de ventas ────────────────────────────────────────
    public function obtenerVentasPaginadas($pagina = 1, $porPagina = 25, $filtros = []) {
        $this->requireCid();
        $where  = ["v.comercio_id = {$this->cid}"];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $where[]            = "(v.numero_orden LIKE :buscar OR u.nombre LIKE :buscar2)";
            $params[':buscar']  = '%' . $filtros['buscar'] . '%';
            $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['estado'])) {
            $where[]           = "v.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['desde'])) {
            $where[]           = "DATE(v.created_at) >= :desde";
            $params[':desde']  = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]           = "DATE(v.created_at) <= :hasta";
            $params[':hasta']  = $filtros['hasta'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        $sql = "SELECT v.id, v.numero_orden, v.total, v.estado, v.notas, v.created_at,
                       v.cliente_id,
                       u.nombre AS usuario_nombre,
                       m.numero AS mesa_numero, m.nombre AS mesa_nombre,
                       COUNT(vd.id) AS total_platos,
                       c.nombre   AS cliente_nombre,
                       c.tipo_doc AS cliente_tipo_doc,
                       c.num_doc  AS cliente_num_doc,
                       c.telefono AS cliente_telefono
                FROM ventas v
                LEFT JOIN usuarios u  ON u.id = v.id_usuario AND u.comercio_id = {$this->cid}
                LEFT JOIN mesas m     ON m.id = v.id_mesa AND m.comercio_id = {$this->cid}
                LEFT JOIN venta_detalle vd ON vd.id_venta = v.id AND vd.comercio_id = {$this->cid}
                LEFT JOIN clientes c  ON c.id = v.cliente_id AND c.comercio_id = {$this->cid}
                $whereSql
                GROUP BY v.id
                ORDER BY v.created_at DESC
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obtenerVentasPaginadas: " . $e->getMessage());
            return [];
        }
    }

    public function contarVentas($filtros = []) {
        $this->requireCid();
        $where  = ["v.comercio_id = {$this->cid}"];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $where[]            = "(v.numero_orden LIKE :buscar OR u.nombre LIKE :buscar2)";
            $params[':buscar']  = '%' . $filtros['buscar'] . '%';
            $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['estado'])) {
            $where[]           = "v.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['desde'])) {
            $where[]           = "DATE(v.created_at) >= :desde";
            $params[':desde']  = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]           = "DATE(v.created_at) <= :hasta";
            $params[':hasta']  = $filtros['hasta'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT COUNT(DISTINCT v.id) AS total,
                       COALESCE(SUM(v.total), 0) AS suma_total
                FROM ventas v
                LEFT JOIN usuarios u ON u.id = v.id_usuario AND u.comercio_id = {$this->cid}
                $whereSql";
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error contarVentas: " . $e->getMessage());
            return ['total' => 0, 'suma_total' => 0];
        }
    }

    public function obtenerEstadisticasHoy() {
        $this->requireCid();
        $sql = "SELECT
                    COUNT(*) AS ventas_hoy,
                    COALESCE(SUM(total), 0) AS ingresos_hoy,
                    (SELECT COUNT(*) FROM ventas WHERE comercio_id = {$this->cid}) AS ventas_total,
                    (SELECT COALESCE(SUM(total),0) FROM ventas WHERE comercio_id = {$this->cid}) AS ingresos_total
                FROM ventas
                WHERE DATE(created_at) = CURDATE() AND comercio_id = {$this->cid}";
        try {
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            return ['ventas_hoy' => 0, 'ingresos_hoy' => 0, 'ventas_total' => 0, 'ingresos_total' => 0];
        }
    }
}
?>
