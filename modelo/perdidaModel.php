<?php
// modelo/perdidaModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class PerdidaModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function estadisticas(string $desde, string $hasta): array {
        $this->requireCid();
        $p = [':desde' => $desde, ':hasta' => $hasta];

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total_salidas,
                    COALESCE(SUM(cantidad), 0) AS total_unidades
             FROM movimientos_insumos
             WHERE tipo = 'salida' AND comercio_id = {$this->cid}
               AND DATE(fecha) BETWEEN :desde AND :hasta"
        );
        $stmt->execute($p);
        $row = $stmt->fetch();

        $stmt2 = $this->db->prepare(
            "SELECT i.nombre, SUM(m.cantidad) AS perdido
             FROM movimientos_insumos m
             JOIN insumos i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
             WHERE m.tipo = 'salida' AND m.comercio_id = {$this->cid}
               AND DATE(m.fecha) BETWEEN :desde AND :hasta
             GROUP BY m.id_insumo
             ORDER BY perdido DESC
             LIMIT 1"
        );
        $stmt2->execute($p);
        $top = $stmt2->fetch();

        return [
            'total_salidas'  => (int)$row['total_salidas'],
            'total_unidades' => (float)$row['total_unidades'],
            'top_insumo'     => $top['nombre'] ?? null,
        ];
    }

    public function obtenerSalidas(string $desde, string $hasta): array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT m.id, m.fecha, m.cantidad, m.stock_anterior, m.stock_nuevo,
                    m.descripcion AS motivo,
                    i.nombre      AS insumo,
                    i.categoria,
                    i.unidad_medida AS unidad,
                    COALESCE(u.nombre, '—') AS usuario
             FROM movimientos_insumos m
             JOIN  insumos  i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
             LEFT JOIN usuarios u ON m.id_usuario = u.id AND u.comercio_id = {$this->cid}
             WHERE m.tipo = 'salida' AND m.comercio_id = {$this->cid}
               AND DATE(m.fecha) BETWEEN :desde AND :hasta
             ORDER BY m.fecha DESC"
        );
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    public function getInsumos(): array {
        $this->requireCid();
        $stmt = $this->db->query(
            "SELECT id, nombre, unidad_medida, cantidad_stock
             FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid}
             ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }

    public function registrarSalida(int $idInsumo, float $cantidad, string $descripcion, ?int $idUsuario): bool {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT cantidad_stock FROM insumos WHERE id = :id AND comercio_id = {$this->cid} FOR UPDATE"
            );
            $stmt->execute([':id' => $idInsumo]);
            $row = $stmt->fetch();
            if (!$row) { $this->db->rollBack(); return false; }

            $stockAnterior = (float)$row['cantidad_stock'];
            $stockNuevo    = max(0, $stockAnterior - $cantidad);

            $this->db->prepare(
                "UPDATE insumos SET cantidad_stock = :s WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':s' => $stockNuevo, ':id' => $idInsumo]);

            $this->db->prepare(
                "INSERT INTO movimientos_insumos
                     (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario)
                 VALUES ({$this->cid}, :id_insumo, 'salida', :cantidad, :stock_anterior, :stock_nuevo, :descripcion, :id_usuario)"
            )->execute([
                ':id_insumo'      => $idInsumo,
                ':cantidad'       => $cantidad,
                ':stock_anterior' => $stockAnterior,
                ':stock_nuevo'    => $stockNuevo,
                ':descripcion'    => $descripcion ?: null,
                ':id_usuario'     => $idUsuario,
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
?>
