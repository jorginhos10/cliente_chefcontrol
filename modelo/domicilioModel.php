<?php
// modelo/domicilioModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class DomicilioModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    // ── Links ────────────────────────────────────────────────────────────────

    public function crearLink(string $nombre, string $descripcion): int {
        $this->requireCid();
        $token = bin2hex(random_bytes(16));
        $this->db->prepare(
            "INSERT INTO dom_links (comercio_id, nombre, descripcion, token) VALUES ({$this->cid}, :n, :d, :t)"
        )->execute([':n' => $nombre, ':d' => $descripcion, ':t' => $token]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerLinks(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT l.*,
                COUNT(CASE WHEN p.estado = 'pendiente'                          THEN 1 END) AS pendientes,
                COUNT(CASE WHEN p.estado IN ('preparacion','listo','en_camino') THEN 1 END) AS activos
             FROM dom_links l
             LEFT JOIN dom_pedidos p ON p.link_id = l.id AND p.comercio_id = {$this->cid}
             WHERE l.comercio_id = {$this->cid}
             GROUP BY l.id
             ORDER BY l.created_at DESC"
        )->fetchAll();
    }

    public function obtenerLinkPorToken(string $token): array|false {
        // Público — resuelve comercio_id por el token
        $stmt = $this->db->prepare("SELECT * FROM dom_links WHERE token = :t AND activo = 1");
        $stmt->execute([':t' => $token]);
        return $stmt->fetch();
    }

    public function editarLink(int $id, string $nombre, string $descripcion): void {
        $this->requireCid();
        $this->db->prepare(
            "UPDATE dom_links SET nombre = :n, descripcion = :d WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':n' => $nombre, ':d' => $descripcion, ':id' => $id]);
    }

    public function toggleLink(int $id): void {
        $this->requireCid();
        $this->db->prepare(
            "UPDATE dom_links SET activo = 1 - activo WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);
    }

    public function eliminarLink(int $id): void {
        $this->requireCid();
        $this->db->prepare(
            "DELETE FROM dom_links WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);
    }

    public function guardarConfigLink(int $id, array $config): void {
        $this->requireCid();
        $campos = [];
        $params = [':id' => $id];
        if (array_key_exists('mostrar_sin_stock', $config)) {
            $campos[] = 'mostrar_sin_stock = :mss';
            $params[':mss'] = (int)(bool)$config['mostrar_sin_stock'];
        }
        if (array_key_exists('horario_desde', $config)) {
            $campos[] = 'horario_desde = :hd';
            $params[':hd'] = $config['horario_desde'] ?: null;
        }
        if (array_key_exists('horario_hasta', $config)) {
            $campos[] = 'horario_hasta = :hh';
            $params[':hh'] = $config['horario_hasta'] ?: null;
        }
        if (array_key_exists('categorias_activas', $config)) {
            $campos[] = 'categorias_activas = :ca';
            $v = $config['categorias_activas'];
            $params[':ca'] = ($v !== null && is_array($v)) ? json_encode(array_values($v)) : null;
        }
        if (empty($campos)) return;
        $this->db->prepare(
            "UPDATE dom_links SET " . implode(', ', $campos) . " WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute($params);
    }

    // ── Pedidos ──────────────────────────────────────────────────────────────

    public function crearPedido(int $link_id, string $nombre, string $telefono,
                                string $direccion, string $notas, string $tipo,
                                array $items, float $total): string {
        // Resolve comercio_id from the link (link was already validated by caller)
        $linkRow = $this->db->prepare("SELECT comercio_id FROM dom_links WHERE id = :id AND activo = 1");
        $linkRow->execute([':id' => $link_id]);
        $row = $linkRow->fetch();
        if (!$row) throw new \RuntimeException("Link no encontrado o inactivo");
        $cid = (int)$row['comercio_id'];

        $token = bin2hex(random_bytes(20));
        $tipo  = in_array($tipo, ['domicilio', 'recoger']) ? $tipo : 'domicilio';
        $this->db->prepare(
            "INSERT INTO dom_pedidos (comercio_id, link_id, token_pedido, nombre_cliente, telefono, direccion, notas, tipo, total)
             VALUES ($cid, :l, :t, :n, :tel, :dir, :not, :tipo, :tot)"
        )->execute([
            ':l' => $link_id, ':t' => $token, ':n' => $nombre,
            ':tel' => $telefono, ':dir' => $direccion,
            ':not' => $notas, ':tipo' => $tipo, ':tot' => $total,
        ]);
        $pedido_id = (int)$this->db->lastInsertId();

        $si = $this->db->prepare(
            "INSERT INTO dom_items (comercio_id, pedido_id, receta_id, nombre, precio, cantidad)
             VALUES ($cid, :pid, :rid, :n, :p, :c)"
        );
        foreach ($items as $item) {
            $si->execute([
                ':pid' => $pedido_id,
                ':rid' => $item['receta_id'] ?? null,
                ':n'   => $item['nombre'],
                ':p'   => (float)($item['precio'] ?? 0),
                ':c'   => (int)($item['cantidad'] ?? 1),
            ]);
        }

        try {
            $this->descontarStockReservado($pedido_id, $items, $cid);
        } catch (\Throwable $e) {
            error_log("DomicilioModel: no se pudo reservar stock para pedido $pedido_id: " . $e->getMessage());
        }

        return $token;
    }

    public function obtenerPedidoPorToken(string $token): array|false {
        // Público — token es globalmente único
        $stmt = $this->db->prepare(
            "SELECT p.*, l.nombre AS link_nombre
             FROM dom_pedidos p JOIN dom_links l ON l.id = p.link_id
             WHERE p.token_pedido = :t"
        );
        $stmt->execute([':t' => $token]);
        return $stmt->fetch();
    }

    public function obtenerItemsPedido(int $pedido_id): array {
        $stmt = $this->db->prepare("SELECT * FROM dom_items WHERE pedido_id = :pid ORDER BY id");
        $stmt->execute([':pid' => $pedido_id]);
        return $stmt->fetchAll();
    }

    public function obtenerPedidosAdmin(): array {
        $this->requireCid();
        $rows = $this->db->query(
            "SELECT p.*, l.nombre AS link_nombre
             FROM dom_pedidos p
             JOIN dom_links l ON l.id = p.link_id AND l.comercio_id = {$this->cid}
             WHERE p.comercio_id = {$this->cid}
               AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY FIELD(p.estado,'pendiente','preparacion','listo','en_camino'), p.created_at ASC"
        )->fetchAll();

        foreach ($rows as &$row) {
            $row['items']          = $this->obtenerItemsPedido($row['id']);
            $row['chat_no_leidos'] = $this->contarNoLeidosChat($row['token_pedido'], 'admin');
        }
        return $rows;
    }

    public function cambiarEstado(int $pedido_id, string $estado, ?float $valorDomicilio = null): bool {
        $this->requireCid();
        $validos = ['pendiente','preparacion','listo','en_camino','entregado','cancelado'];
        if (!in_array($estado, $validos)) return false;

        $s = $this->db->prepare(
            "SELECT p.estado, p.stock_reservado FROM dom_pedidos p
             JOIN dom_links l ON l.id = p.link_id AND l.comercio_id = {$this->cid}
             WHERE p.id = :id AND p.comercio_id = {$this->cid}"
        );
        $s->execute([':id' => $pedido_id]);
        $row            = $s->fetch();
        if (!$row) return false;

        $estadoActual   = $row['estado'];
        $stockReservado = (int)$row['stock_reservado'];

        if ($valorDomicilio !== null) {
            $this->db->prepare(
                "UPDATE dom_pedidos SET estado = :e, valor_domicilio = :v
                 WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':e' => $estado, ':v' => $valorDomicilio, ':id' => $pedido_id]);
        } else {
            $this->db->prepare(
                "UPDATE dom_pedidos SET estado = :e WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':e' => $estado, ':id' => $pedido_id]);
        }

        if ($estadoActual !== $estado) {
            $this->db->prepare(
                "INSERT INTO dom_historial (comercio_id, pedido_id, estado_de, estado_a)
                 VALUES ({$this->cid}, :p, :de, :a)"
            )->execute([':p' => $pedido_id, ':de' => $estadoActual, ':a' => $estado]);
        }

        if ($estado === 'entregado') {
            $this->registrarComoVenta($pedido_id, $this->cid);
        }
        if ($estado === 'cancelado' && $stockReservado) {
            try {
                $this->restaurarStock($pedido_id, $this->cid);
            } catch (\Throwable $e) {
                error_log("DomicilioModel::restaurarStock error: " . $e->getMessage());
            }
        }
        return true;
    }

    public function obtenerHistorialNoLeido(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT h.*, p.nombre_cliente
             FROM dom_historial h
             JOIN dom_pedidos p ON p.id = h.pedido_id AND p.comercio_id = {$this->cid}
             WHERE h.leido = 0 AND h.comercio_id = {$this->cid}
             ORDER BY h.created_at DESC LIMIT 30"
        )->fetchAll();
    }

    public function obtenerHistorialReciente(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT h.*, p.nombre_cliente
             FROM dom_historial h
             JOIN dom_pedidos p ON p.id = h.pedido_id AND p.comercio_id = {$this->cid}
             WHERE h.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND h.comercio_id = {$this->cid}
             ORDER BY h.created_at DESC LIMIT 50"
        )->fetchAll();
    }

    public function contarOrdenesActivasCocina(): int {
        $this->requireCid();
        $dom = (int)$this->db->query(
            "SELECT COUNT(*) FROM dom_pedidos WHERE comercio_id = {$this->cid} AND estado IN ('pendiente','preparacion','listo')"
        )->fetchColumn();
        try {
            $salon = (int)$this->db->query(
                "SELECT COUNT(*) FROM ventas WHERE comercio_id = {$this->cid} AND estado IN ('abierta','en_preparacion','lista')"
            )->fetchColumn();
        } catch (\Throwable $e) {
            $salon = 0;
        }
        return $dom + $salon;
    }

    public function contarPedidosActivos(): int {
        $this->requireCid();
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM dom_pedidos WHERE comercio_id = {$this->cid} AND estado IN ('pendiente','preparacion','listo','en_camino')"
        )->fetchColumn();
    }

    public function marcarHistorialLeido(): void {
        $this->requireCid();
        $this->db->exec(
            "UPDATE dom_historial SET leido = 1 WHERE leido = 0 AND comercio_id = {$this->cid}"
        );
    }

    public function obtenerPedidosPendientes(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT id, nombre_cliente, tipo, created_at
             FROM dom_pedidos WHERE comercio_id = {$this->cid} AND estado = 'pendiente'
             ORDER BY created_at DESC"
        )->fetchAll();
    }

    public function obtenerOrdenesParaCocina(): array {
        $this->requireCid();
        $estadoMap = [
            'pendiente'   => 'abierta',
            'preparacion' => 'en_preparacion',
            'listo'       => 'lista',
        ];

        $rows = $this->db->query(
            "SELECT id, nombre_cliente, direccion, notas, estado, total, created_at
             FROM dom_pedidos
             WHERE comercio_id = {$this->cid} AND estado IN ('pendiente','preparacion','listo')
             ORDER BY created_at ASC"
        )->fetchAll();

        $result = [];
        foreach ($rows as $p) {
            $items = $this->obtenerItemsPedido($p['id']);
            $mins  = (int)round((time() - strtotime($p['created_at'])) / 60);
            $result[] = [
                'id'             => -((int)$p['id']),
                'dom_id'         => (int)$p['id'],
                'source'         => 'domicilio',
                'estado'         => $estadoMap[$p['estado']],
                'numero_orden'   => 'DOM-' . $p['id'],
                'mesa_numero'    => '&#x1F6F5;',
                'mesa_nombre'    => $p['nombre_cliente'],
                'mesa_zona'      => $p['direccion'],
                'fecha_creacion' => $p['created_at'],
                'minutos_espera' => $mins,
                'notas'          => $p['notas'],
                'items'          => array_map(fn($it) => [
                    'receta_nombre' => $it['nombre'],
                    'cantidad'      => $it['cantidad'],
                    'categoria'     => 'otro',
                ], $items),
            ];
        }
        return $result;
    }

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function enviarMensajeChat(string $token_pedido, string $de, string $mensaje): int {
        $this->db->prepare(
            "INSERT INTO dom_chat (token_pedido, de, mensaje) VALUES (:t, :d, :m)"
        )->execute([':t' => $token_pedido, ':d' => $de, ':m' => mb_substr($mensaje, 0, 1000)]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerMensajesChat(string $token_pedido, int $desde = 0): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM dom_chat WHERE token_pedido = :t AND id > :d ORDER BY id ASC LIMIT 100"
        );
        $stmt->execute([':t' => $token_pedido, ':d' => $desde]);
        return $stmt->fetchAll();
    }

    public function contarNoLeidosChat(string $token_pedido, string $para): int {
        $de   = $para === 'admin' ? 'cliente' : 'admin';
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM dom_chat WHERE token_pedido = :t AND de = :d AND leido = 0"
        );
        $stmt->execute([':t' => $token_pedido, ':d' => $de]);
        return (int)$stmt->fetchColumn();
    }

    public function marcarLeidosChat(string $token_pedido, string $para): void {
        $de = $para === 'admin' ? 'cliente' : 'admin';
        $this->db->prepare(
            "UPDATE dom_chat SET leido = 1, leido_at = NOW()
             WHERE token_pedido = :t AND de = :d AND leido = 0"
        )->execute([':t' => $token_pedido, ':d' => $de]);
    }

    public function obtenerResumenesChat(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT p.id, p.token_pedido, p.nombre_cliente, p.tipo, p.estado,
                    (SELECT COUNT(*) FROM dom_chat c
                     WHERE c.token_pedido = p.token_pedido AND c.de = 'cliente' AND c.leido = 0) AS no_leidos,
                    (SELECT c2.mensaje FROM dom_chat c2
                     WHERE c2.token_pedido = p.token_pedido ORDER BY c2.id DESC LIMIT 1) AS ultimo_mensaje,
                    (SELECT c3.created_at FROM dom_chat c3
                     WHERE c3.token_pedido = p.token_pedido ORDER BY c3.id DESC LIMIT 1) AS ultima_fecha
             FROM dom_pedidos p
             WHERE p.comercio_id = {$this->cid} AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY no_leidos DESC, ultima_fecha DESC, p.created_at DESC"
        )->fetchAll();
    }

    // ── Recetas / Stock ──────────────────────────────────────────────────────

    public function obtenerRecetas(bool $mostrarSinStock = false, ?array $catActivas = null, int $cid = 0): array {
        // cid puede venir del link (público) o de $this->cid (admin)
        $cid = $cid > 0 ? $cid : $this->cid;
        if ($cid <= 0) return [];
        try {
            $having = $mostrarSinStock ? '' : 'HAVING unidades_disponibles IS NULL OR unidades_disponibles > 0';
            $rows   = $this->db->query(
                "SELECT r.id, r.nombre, r.precio_venta AS precio, r.categoria, r.foto,
                        CASE WHEN COUNT(ri.id_insumo) = 0 THEN NULL
                             ELSE FLOOR(MIN(i.cantidad_stock / NULLIF(ri.cantidad, 0)))
                        END AS unidades_disponibles
                 FROM recetas r
                 LEFT JOIN receta_insumos ri ON ri.id_receta = r.id AND ri.comercio_id = $cid
                 LEFT JOIN insumos i ON i.id = ri.id_insumo AND i.comercio_id = $cid
                 WHERE r.activo = 1 AND r.comercio_id = $cid
                 GROUP BY r.id, r.nombre, r.precio_venta, r.categoria, r.foto
                 $having
                 ORDER BY r.categoria, r.nombre"
            )->fetchAll();
        } catch (\Throwable $e) {
            try {
                $rows = $this->db->query(
                    "SELECT id, nombre, precio_venta AS precio, categoria, foto, NULL AS unidades_disponibles
                     FROM recetas WHERE activo = 1 AND comercio_id = $cid ORDER BY categoria, nombre"
                )->fetchAll();
            } catch (\Throwable $e2) {
                return [];
            }
        }
        if ($catActivas !== null && count($catActivas) > 0) {
            $rows = array_values(array_filter($rows, fn($r) => in_array($r['categoria'], $catActivas, true)));
        }
        return $rows;
    }

    public function obtenerCategoriasRecetas(int $cid = 0): array {
        $cid = $cid > 0 ? $cid : $this->cid;
        if ($cid <= 0) return [];
        try {
            return $this->db->query(
                "SELECT DISTINCT categoria FROM recetas WHERE activo = 1 AND comercio_id = $cid ORDER BY categoria"
            )->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) { return []; }
    }

    public function verificarStock(array $items, int $cid = 0): array {
        $cid = $cid > 0 ? $cid : $this->cid;
        if ($cid <= 0) return [];
        $insuficientes = [];
        try {
            $stmtDisp = $this->db->prepare(
                "SELECT CASE WHEN COUNT(ri.id_insumo) = 0 THEN NULL
                             ELSE FLOOR(MIN(i.cantidad_stock / NULLIF(ri.cantidad, 0)))
                        END AS disponibles
                 FROM recetas r
                 LEFT JOIN receta_insumos ri ON ri.id_receta = r.id AND ri.comercio_id = $cid
                 LEFT JOIN insumos i ON i.id = ri.id_insumo AND i.comercio_id = $cid
                 WHERE r.id = :rid AND r.comercio_id = $cid
                 GROUP BY r.id"
            );
            foreach ($items as $item) {
                $rid = (int)($item['receta_id'] ?? 0);
                if (!$rid) continue;
                $stmtDisp->execute([':rid' => $rid]);
                $row = $stmtDisp->fetch();
                if ($row === false) continue;
                $disp = $row['disponibles'];
                if ($disp === null) continue;
                $disp = (int)$disp;
                $cant = (int)($item['cantidad'] ?? 1);
                if ($disp < $cant) {
                    $insuficientes[] = [
                        'nombre'      => $item['nombre'] ?? "Receta $rid",
                        'disponibles' => $disp,
                        'pedido'      => $cant,
                    ];
                }
            }
        } catch (\Throwable $e) { /* sin inventario, no bloquear */ }
        return $insuficientes;
    }

    // ── Privados ─────────────────────────────────────────────────────────────

    private function registrarComoVenta(int $pedido_id, int $cid): void {
        try {
            $existe = $this->db->prepare(
                "SELECT COUNT(*) FROM ventas WHERE numero_orden = :n AND comercio_id = $cid"
            );
            $existe->execute([':n' => 'DOM-' . $pedido_id]);
            if ($existe->fetchColumn() > 0) return;

            $stmt = $this->db->prepare(
                "SELECT * FROM dom_pedidos WHERE id = :id AND comercio_id = $cid"
            );
            $stmt->execute([':id' => $pedido_id]);
            $pedido = $stmt->fetch();
            if (!$pedido) return;

            $items = $this->obtenerItemsPedido($pedido_id);

            $tipoLabel  = $pedido['tipo'] === 'recoger' ? 'Recoger' : 'Domicilio';
            $notasVenta = $tipoLabel . ' — ' . $pedido['nombre_cliente'];
            if (!empty($pedido['direccion'])) $notasVenta .= ' | ' . $pedido['direccion'];
            if (!empty($pedido['notas']))     $notasVenta .= ' | ' . $pedido['notas'];

            $this->db->beginTransaction();

            $this->db->prepare(
                "INSERT INTO ventas (comercio_id, numero_orden, total, notas, estado)
                 VALUES ($cid, :num, :total, :notas, 'completada')"
            )->execute([
                ':num'   => 'DOM-' . $pedido_id,
                ':total' => $pedido['total'],
                ':notas' => mb_substr($notasVenta, 0, 500),
            ]);
            $id_venta = (int)$this->db->lastInsertId();

            $stmtDet = $this->db->prepare(
                "INSERT INTO venta_detalle (comercio_id, id_venta, id_receta, cantidad, precio_unitario, subtotal)
                 VALUES ($cid, :v, :r, :c, :p, :s)"
            );
            foreach ($items as $item) {
                if (empty($item['receta_id'])) continue;
                $stmtDet->execute([
                    ':v' => $id_venta,
                    ':r' => (int)$item['receta_id'],
                    ':c' => (int)$item['cantidad'],
                    ':p' => (float)$item['precio'],
                    ':s' => (float)$item['precio'] * (int)$item['cantidad'],
                ]);
            }

            $sRes = $this->db->prepare(
                "SELECT stock_reservado FROM dom_pedidos WHERE id = :id AND comercio_id = $cid"
            );
            $sRes->execute([':id' => $pedido_id]);
            $yaReservado = (int)$sRes->fetchColumn();

            if (!$yaReservado) {
                $stmtIng = $this->db->prepare(
                    "SELECT ri.id_insumo, ri.cantidad
                     FROM receta_insumos ri WHERE ri.id_receta = :rid AND ri.comercio_id = $cid"
                );
                $consumo = [];
                foreach ($items as $item) {
                    if (empty($item['receta_id'])) continue;
                    $stmtIng->execute([':rid' => (int)$item['receta_id']]);
                    foreach ($stmtIng->fetchAll() as $ing) {
                        $id_ins = (int)$ing['id_insumo'];
                        $consumo[$id_ins] = ($consumo[$id_ins] ?? 0.0)
                            + (float)$ing['cantidad'] * (int)$item['cantidad'];
                    }
                }
                if ($consumo) {
                    $stmtUpd = $this->db->prepare(
                        "UPDATE insumos SET cantidad_stock = cantidad_stock - :cant
                         WHERE id = :id AND comercio_id = $cid"
                    );
                    $stmtMov = $this->db->prepare(
                        "INSERT INTO movimientos_insumos
                             (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion)
                         SELECT $cid, :id_insumo, 'salida', :cantidad,
                                cantidad_stock + :cantidad2, cantidad_stock, :desc
                         FROM insumos WHERE id = :id2 AND comercio_id = $cid"
                    );
                    foreach ($consumo as $id_ins => $cant) {
                        $stmtUpd->execute([':cant' => $cant, ':id' => $id_ins]);
                        $stmtMov->execute([
                            ':id_insumo' => $id_ins, ':cantidad'  => $cant,
                            ':cantidad2' => $cant,   ':desc'      => 'DOM-' . $pedido_id,
                            ':id2'       => $id_ins,
                        ]);
                    }
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("DomicilioModel::registrarComoVenta error: " . $e->getMessage());
        }
    }

    private function descontarStockReservado(int $pedido_id, array $items, int $cid): void {
        $stmtIng = $this->db->prepare(
            "SELECT ri.id_insumo, ri.cantidad FROM receta_insumos ri
             WHERE ri.id_receta = :rid AND ri.comercio_id = $cid"
        );
        $consumo = [];
        foreach ($items as $item) {
            if (empty($item['receta_id'])) continue;
            $stmtIng->execute([':rid' => (int)$item['receta_id']]);
            foreach ($stmtIng->fetchAll() as $ing) {
                $id_ins = (int)$ing['id_insumo'];
                $consumo[$id_ins] = ($consumo[$id_ins] ?? 0.0)
                    + (float)$ing['cantidad'] * (int)$item['cantidad'];
            }
        }
        if ($consumo) {
            $stmtUpd = $this->db->prepare(
                "UPDATE insumos SET cantidad_stock = cantidad_stock - :cant
                 WHERE id = :id AND comercio_id = $cid"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_insumos
                     (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion)
                 SELECT $cid, :id_insumo, 'salida', :cantidad,
                        cantidad_stock + :cantidad2, cantidad_stock, :desc
                 FROM insumos WHERE id = :id2 AND comercio_id = $cid"
            );
            foreach ($consumo as $id_ins => $cant) {
                $stmtUpd->execute([':cant' => $cant, ':id' => $id_ins]);
                $stmtMov->execute([
                    ':id_insumo' => $id_ins, ':cantidad'  => $cant,
                    ':cantidad2' => $cant,   ':desc'      => 'RESERVA-DOM-' . $pedido_id,
                    ':id2'       => $id_ins,
                ]);
            }
        }
        $this->db->prepare(
            "UPDATE dom_pedidos SET stock_reservado = 1 WHERE id = :id AND comercio_id = $cid"
        )->execute([':id' => $pedido_id]);
    }

    private function restaurarStock(int $pedido_id, int $cid): void {
        $items   = $this->obtenerItemsPedido($pedido_id);
        $stmtIng = $this->db->prepare(
            "SELECT ri.id_insumo, ri.cantidad FROM receta_insumos ri
             WHERE ri.id_receta = :rid AND ri.comercio_id = $cid"
        );
        $consumo = [];
        foreach ($items as $item) {
            if (empty($item['receta_id'])) continue;
            $stmtIng->execute([':rid' => (int)$item['receta_id']]);
            foreach ($stmtIng->fetchAll() as $ing) {
                $id_ins = (int)$ing['id_insumo'];
                $consumo[$id_ins] = ($consumo[$id_ins] ?? 0.0)
                    + (float)$ing['cantidad'] * (int)$item['cantidad'];
            }
        }
        if ($consumo) {
            $stmtUpd = $this->db->prepare(
                "UPDATE insumos SET cantidad_stock = cantidad_stock + :cant
                 WHERE id = :id AND comercio_id = $cid"
            );
            $stmtMov = $this->db->prepare(
                "INSERT INTO movimientos_insumos
                     (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion)
                 SELECT $cid, :id_insumo, 'entrada', :cantidad,
                        cantidad_stock - :cantidad2, cantidad_stock, :desc
                 FROM insumos WHERE id = :id2 AND comercio_id = $cid"
            );
            foreach ($consumo as $id_ins => $cant) {
                $stmtUpd->execute([':cant' => $cant, ':id' => $id_ins]);
                $stmtMov->execute([
                    ':id_insumo' => $id_ins, ':cantidad'  => $cant,
                    ':cantidad2' => $cant,   ':desc'      => 'DEVOLUCION-DOM-' . $pedido_id,
                    ':id2'       => $id_ins,
                ]);
            }
        }
        $this->db->prepare(
            "UPDATE dom_pedidos SET stock_reservado = 0 WHERE id = :id AND comercio_id = $cid"
        )->execute([':id' => $pedido_id]);
    }
}
?>
