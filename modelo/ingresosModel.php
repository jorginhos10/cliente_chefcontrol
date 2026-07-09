<?php
// modelo/ingresosModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class IngresosModel extends BaseModel {

    public function __construct() {
        parent::__construct();
        $this->asegurarColumnaInsumo();
    }

    // La tabla ya existe en producción sin columna de insumo; la agregamos
    // en caliente la primera vez que se detecta ausente (mismo enfoque que
    // InventarioInmobiliarioModel::asegurarColumnaSeccion).
    private function asegurarColumnaInsumo(): void {
        try {
            $col = $this->db->query("SHOW COLUMNS FROM ingresos_items LIKE 'id_insumo'")->fetch();
            if (!$col) {
                $this->db->exec("ALTER TABLE ingresos_items
                    ADD COLUMN id_insumo INT NULL AFTER id_ingreso,
                    ADD INDEX idx_cid_insumo (comercio_id, id_insumo)");
            }
        } catch (\Throwable $e) {
            error_log('IngresosModel::asegurarColumnaInsumo — ' . $e->getMessage());
        }
    }

    public function obtenerTodos(string $desde = '', string $hasta = '', string $estado = ''): array {
        $this->requireCid();
        $where  = "i.comercio_id = {$this->cid}";
        $params = [];
        if ($desde)  { $where .= ' AND i.fecha >= :desde';  $params[':desde']  = $desde; }
        if ($hasta)  { $where .= ' AND i.fecha <= :hasta';  $params[':hasta']  = $hasta; }
        if ($estado) { $where .= ' AND i.estado = :estado'; $params[':estado'] = $estado; }

        $stmt = $this->db->prepare(
            "SELECT i.*, COALESCE(u.nombre, '—') AS usuario_nombre,
                    COALESCE(p.nombre, '—') AS proveedor_nombre
             FROM ingresos i
             LEFT JOIN usuarios u ON i.id_usuario = u.id AND u.comercio_id = {$this->cid}
             LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
             WHERE $where
             ORDER BY i.fecha DESC, i.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT i.*, COALESCE(p.nombre, '') AS proveedor_nombre
             FROM ingresos i
             LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
             WHERE i.id = :id AND i.comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerItems(int $idIngreso): array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT * FROM ingresos_items
             WHERE id_ingreso = :id AND comercio_id = {$this->cid}
             ORDER BY id ASC"
        );
        $stmt->execute([':id' => $idIngreso]);
        return $stmt->fetchAll();
    }

    public function getInsumos(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT id, nombre, unidad_medida, precio_unitario
             FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function getProveedores(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT id, nombre, empresa FROM proveedores
             WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function generarRadicado(): string {
        $this->requireCid();
        $hoy  = date('Ymd');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ingresos WHERE DATE(fecha) = CURDATE() AND comercio_id = {$this->cid}"
        );
        $stmt->execute();
        $n = (int)$stmt->fetchColumn() + 1;
        return $hoy . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    public function crear(array $datos, array $items): int {
        $this->requireCid();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO ingresos
                     (comercio_id, radicado, fecha, tipo_documento, serie, numero, concepto,
                      subtotal, impuesto, total, estado, id_usuario, id_proveedor)
                 VALUES
                     ({$this->cid}, :radicado, :fecha, :tipo_documento, :serie, :numero, :concepto,
                      :subtotal, :impuesto, :total, :estado, :id_usuario, :id_proveedor)"
            );
            $stmt->execute($datos);
            $id = (int)$this->db->lastInsertId();
            $this->insertarItems($id, $items);

            $descripcion = 'Ingreso #' . $datos[':radicado'] . ($datos[':concepto'] ? ' — ' . $datos[':concepto'] : '');
            foreach ($items as $item) {
                if (!empty($item['id_insumo'])) {
                    $this->aplicarMovimientoStock(
                        (int)$item['id_insumo'], 'entrada', (float)$item['cantidad'],
                        $descripcion, $datos[':id_usuario'], $datos[':id_proveedor']
                    );
                }
            }

            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $datos, array $items): bool {
        $this->requireCid();
        $this->db->beginTransaction();
        try {
            // Revertir el stock aplicado por los artículos anteriores antes de reemplazarlos,
            // para no duplicar la suma cada vez que se edita el ingreso.
            $itemsAnteriores = $this->obtenerItems($id);
            $descRev = 'Edición ingreso #' . $id . ' (reversión)';
            foreach ($itemsAnteriores as $old) {
                if (!empty($old['id_insumo'])) {
                    $this->aplicarMovimientoStock(
                        (int)$old['id_insumo'], 'salida', (float)$old['cantidad'],
                        $descRev, $datos[':id_usuario'] ?? null, null
                    );
                }
            }

            $stmt = $this->db->prepare(
                "UPDATE ingresos SET concepto=:concepto, subtotal=:subtotal,
                 impuesto=:impuesto, total=:total, id_proveedor=:id_proveedor
                 WHERE id=:id AND comercio_id={$this->cid}"
            );
            $stmt->execute([
                ':concepto'     => $datos[':concepto'],
                ':subtotal'     => $datos[':subtotal'],
                ':impuesto'     => $datos[':impuesto'],
                ':total'        => $datos[':total'],
                ':id_proveedor' => $datos[':id_proveedor'],
                ':id'           => $id,
            ]);
            $this->db->prepare(
                "DELETE FROM ingresos_items WHERE id_ingreso = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id]);
            $this->insertarItems($id, $items);

            $descripcion = 'Edición ingreso #' . $id . ($datos[':concepto'] ? ' — ' . $datos[':concepto'] : '');
            foreach ($items as $item) {
                if (!empty($item['id_insumo'])) {
                    $this->aplicarMovimientoStock(
                        (int)$item['id_insumo'], 'entrada', (float)$item['cantidad'],
                        $descripcion, $datos[':id_usuario'] ?? null, $datos[':id_proveedor'] ?? null
                    );
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function eliminar(int $id): bool {
        $this->requireCid();
        $this->db->beginTransaction();
        try {
            $items = $this->obtenerItems($id);
            $descripcion = 'Eliminación ingreso #' . $id;
            foreach ($items as $item) {
                if (!empty($item['id_insumo'])) {
                    $this->aplicarMovimientoStock(
                        (int)$item['id_insumo'], 'salida', (float)$item['cantidad'],
                        $descripcion, null, null
                    );
                }
            }
            $this->db->prepare(
                "DELETE FROM ingresos_items WHERE id_ingreso = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id]);
            $ok = (bool)$this->db->prepare(
                "DELETE FROM ingresos WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id]);
            $this->db->commit();
            return $ok;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Aplica un movimiento de stock sobre un insumo dentro de la transacción activa
    // del llamador (no abre su propia transacción — el caller debe envolver la llamada).
    // Si el insumo ya no existe, se ignora en silencio para no bloquear el ingreso.
    private function aplicarMovimientoStock(
        int $idInsumo, string $tipo, float $cantidad, string $descripcion,
        ?int $idUsuario, ?int $idProveedor
    ): void {
        $stmt = $this->db->prepare(
            "SELECT cantidad_stock FROM insumos WHERE id = :id AND comercio_id = {$this->cid} FOR UPDATE"
        );
        $stmt->execute([':id' => $idInsumo]);
        $row = $stmt->fetch();
        if (!$row) return;

        $stockAnterior = (float)$row['cantidad_stock'];
        $stockNuevo    = $tipo === 'entrada'
            ? $stockAnterior + $cantidad
            : max(0, $stockAnterior - $cantidad);

        $this->db->prepare(
            "UPDATE insumos SET cantidad_stock = :nuevo WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':nuevo' => $stockNuevo, ':id' => $idInsumo]);

        $this->db->prepare(
            "INSERT INTO movimientos_insumos
                 (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario, id_proveedor)
             VALUES ({$this->cid}, :id_insumo, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :descripcion, :id_usuario, :id_proveedor)"
        )->execute([
            ':id_insumo'      => $idInsumo,
            ':tipo'           => $tipo,
            ':cantidad'       => $cantidad,
            ':stock_anterior' => $stockAnterior,
            ':stock_nuevo'    => $stockNuevo,
            ':descripcion'    => $descripcion,
            ':id_usuario'     => $idUsuario,
            ':id_proveedor'   => $idProveedor,
        ]);
    }

    public function estadisticas(string $desde = '', string $hasta = ''): array {
        $this->requireCid();
        $where  = "comercio_id = {$this->cid}";
        $params = [];
        if ($desde) { $where .= ' AND fecha >= :desde'; $params[':desde'] = $desde; }
        if ($hasta) { $where .= ' AND fecha <= :hasta'; $params[':hasta'] = $hasta; }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS suma
             FROM ingresos WHERE $where AND estado != 'Anulado'"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) AS anulados FROM ingresos WHERE $where AND estado = 'Anulado'"
        );
        $stmt2->execute($params);
        $row2 = $stmt2->fetch();

        return [
            'total'    => (int)$row['total'],
            'suma'     => (float)$row['suma'],
            'anulados' => (int)$row2['anulados'],
        ];
    }

    private function insertarItems(int $idIngreso, array $items): void {
        $stmt = $this->db->prepare(
            "INSERT INTO ingresos_items (comercio_id, id_ingreso, id_insumo, articulo, cantidad, precio_unitario, subtotal)
             VALUES ({$this->cid}, :id_ingreso, :id_insumo, :articulo, :cantidad, :precio_unitario, :subtotal)"
        );
        foreach ($items as $item) {
            $stmt->execute([
                ':id_ingreso'      => $idIngreso,
                ':id_insumo'       => $item['id_insumo'] ?: null,
                ':articulo'        => $item['articulo'],
                ':cantidad'        => (float)$item['cantidad'],
                ':precio_unitario' => (float)$item['precio_unitario'],
                ':subtotal'        => (float)$item['subtotal'],
            ]);
        }
    }
}
?>
