<?php
require_once __DIR__ . '/../core/BaseModel.php';

class MenuDigitalModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerPorId(int $id): ?array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT * FROM menus_digitales WHERE id = :id AND comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerRecetasActivas(): array {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT id, nombre, categoria, precio_venta, foto, descripcion
                 FROM recetas WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY categoria, nombre"
            )->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function obtenerItems(int $menu_id): array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT receta_id FROM menu_items WHERE menu_id = :mid AND comercio_id = {$this->cid} ORDER BY orden"
        );
        $stmt->execute([':mid' => $menu_id]);
        return array_column($stmt->fetchAll(), 'receta_id');
    }

    public function guardarItems(int $menu_id, array $receta_ids): void {
        $this->requireCid();
        $this->db->prepare(
            "DELETE FROM menu_items WHERE menu_id = :mid AND comercio_id = {$this->cid}"
        )->execute([':mid' => $menu_id]);
        $stmt = $this->db->prepare(
            "INSERT INTO menu_items (comercio_id, menu_id, receta_id, orden)
             VALUES ({$this->cid}, :mid, :rid, :ord)"
        );
        foreach (array_values($receta_ids) as $i => $rid) {
            $stmt->execute([':mid' => $menu_id, ':rid' => (int)$rid, ':ord' => $i]);
        }
    }

    public function obtenerTodos(): array {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT md.*,
                        CASE WHEN m.id IS NOT NULL
                             THEN CONCAT('[', m.numero, '] ', m.nombre)
                             ELSE NULL END AS mesa_label
                 FROM menus_digitales md
                 LEFT JOIN mesas m ON m.id = md.mesa_id AND m.comercio_id = {$this->cid}
                 WHERE md.comercio_id = {$this->cid}
                 ORDER BY md.created_at DESC"
            )->fetchAll();
        } catch (\Throwable $e) {
            return $this->db->query(
                "SELECT * FROM menus_digitales WHERE comercio_id = {$this->cid} ORDER BY created_at DESC"
            )->fetchAll();
        }
    }

    public function obtenerMesas(): array {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT id, numero, nombre, zona FROM mesas
                 WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY numero ASC"
            )->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function actualizarNombreYMesa(int $id, string $nombre, ?int $mesa_id): bool {
        $this->requireCid();
        return $this->db->prepare(
            "UPDATE menus_digitales SET nombre=:n, mesa_id=:m WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':n' => $nombre, ':m' => $mesa_id, ':id' => $id]);
    }

    // Precios reales desde BD para los ids pedidos, sólo si pertenecen al menú y siguen activos
    public function obtenerPreciosValidos(int $menu_id, array $ids): array {
        $this->requireCid();
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) return [];
        $place = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT r.id, r.precio_venta
             FROM menu_items mi
             JOIN recetas r ON r.id = mi.receta_id AND r.comercio_id = {$this->cid} AND r.activo = 1
             WHERE mi.menu_id = ? AND mi.comercio_id = {$this->cid} AND mi.receta_id IN ($place)"
        );
        $stmt->execute(array_merge([$menu_id], $ids));
        $out = [];
        foreach ($stmt->fetchAll() as $row) $out[(int)$row['id']] = (float)$row['precio_venta'];
        return $out;
    }

    public function obtenerPorToken(string $token): ?array {
        // Token es público — resuelve comercio_id por el token mismo
        try {
            $stmt = $this->db->prepare(
                "SELECT md.*, m.nombre AS mesa_nombre, m.numero AS mesa_numero
                 FROM menus_digitales md
                 LEFT JOIN mesas m ON m.id = md.mesa_id AND m.comercio_id = md.comercio_id
                 WHERE md.token = :t"
            );
            $stmt->execute([':t' => $token]);
            return $stmt->fetch() ?: null;
        } catch (\Throwable $e) { return null; }
    }

    public function obtenerItemsConDetalle(int $menu_id): array {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT r.id, r.nombre, r.categoria, r.precio_venta, r.foto, r.descripcion,
                        CASE WHEN COUNT(ri.id_insumo) = 0 THEN NULL
                             ELSE FLOOR(MIN(i.cantidad_stock / NULLIF(ri.cantidad, 0)))
                        END AS unidades_disponibles
                 FROM menu_items mi
                 JOIN recetas r ON r.id = mi.receta_id AND r.comercio_id = {$this->cid}
                 LEFT JOIN receta_insumos ri ON ri.id_receta = r.id AND ri.comercio_id = {$this->cid}
                 LEFT JOIN insumos i ON i.id = ri.id_insumo AND i.comercio_id = {$this->cid}
                 WHERE mi.menu_id = :mid AND mi.comercio_id = {$this->cid}
                 GROUP BY r.id, r.nombre, r.categoria, r.precio_venta, r.foto, r.descripcion, mi.orden
                 ORDER BY mi.orden ASC"
            );
            $stmt->execute([':mid' => $menu_id]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function crear(string $nombre, string $descripcion, int $activo): array {
        $this->requireCid();
        $token = bin2hex(random_bytes(16));
        $stmt  = $this->db->prepare(
            "INSERT INTO menus_digitales (comercio_id, nombre, descripcion, activo, token)
             VALUES ({$this->cid}, :n, :d, :a, :t)"
        );
        $stmt->execute([':n' => $nombre, ':d' => $descripcion, ':a' => $activo, ':t' => $token]);
        $id = (int)$this->db->lastInsertId();
        return ['id' => $id, 'token' => $token];
    }

    public function actualizar(int $id, string $nombre, string $descripcion, int $activo): bool {
        $this->requireCid();
        return $this->db->prepare(
            "UPDATE menus_digitales SET nombre=:n, descripcion=:d, activo=:a WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':n' => $nombre, ':d' => $descripcion, ':a' => $activo, ':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $this->requireCid();
        return $this->db->prepare(
            "DELETE FROM menus_digitales WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':id' => $id]);
    }

    public function toggleActivo(int $id): bool {
        $this->requireCid();
        return $this->db->prepare(
            "UPDATE menus_digitales SET activo = NOT activo WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':id' => $id]);
    }

    public function duplicar(int $id): ?int {
        $this->requireCid();
        $orig = $this->obtenerPorId($id);
        if (!$orig) return null;
        $token = bin2hex(random_bytes(16));
        $this->db->prepare(
            "INSERT INTO menus_digitales (comercio_id, nombre, descripcion, activo, token)
             VALUES ({$this->cid}, :n, :d, :a, :t)"
        )->execute([
            ':n' => $orig['nombre'] . ' (copia)',
            ':d' => $orig['descripcion'],
            ':a' => $orig['activo'],
            ':t' => $token,
        ]);
        $nuevoId = (int)$this->db->lastInsertId();
        $items   = $this->obtenerItems($id);
        if ($items) $this->guardarItems($nuevoId, $items);
        return $nuevoId;
    }
}
?>
