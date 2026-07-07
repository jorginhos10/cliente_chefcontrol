<?php
// modelo/cuponModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class CuponModel extends BaseModel {

    public function __construct() {
        parent::__construct();
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS cupones_usos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    comercio_id INT NOT NULL,
                    id_cupon INT NOT NULL,
                    codigo VARCHAR(8) NOT NULL,
                    monto_descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
                    fecha_uso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_cid (comercio_id),
                    INDEX idx_cid_cupon (comercio_id, id_cupon),
                    INDEX idx_cid_fecha (comercio_id, fecha_uso)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {}
    }

    private function generarCodigo(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while ($this->existeCodigo($code));
        return $code;
    }

    private function existeCodigo(string $code): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM cupones WHERE codigo = :c AND comercio_id = {$this->cid}"
        );
        $stmt->execute([':c' => $code]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function codigoDisponible(string $codigo): bool {
        $this->requireCid();
        return !$this->existeCodigo(strtoupper(trim($codigo)));
    }

    public function crear(string $nombre, string $tipo, float $descuento,
                          int $usos_max, ?string $expira_en, ?int $id_receta = null,
                          ?string $codigoCustom = null): array {
        $this->requireCid();
        $codigo = ($codigoCustom !== null && $codigoCustom !== '')
            ? strtoupper(trim($codigoCustom))
            : $this->generarCodigo();
        $this->db->prepare(
            "INSERT INTO cupones (comercio_id, codigo, nombre, tipo, descuento, usos_max, expira_en, id_receta)
             VALUES ({$this->cid}, :cod, :nom, :tip, :desc, :umax, :exp, :rid)"
        )->execute([
            ':cod'  => $codigo,
            ':nom'  => $nombre ?: null,
            ':tip'  => in_array($tipo, ['porcentaje','valor','producto']) ? $tipo : 'porcentaje',
            ':desc' => max(0, $descuento),
            ':umax' => max(1, $usos_max),
            ':exp'  => $expira_en ?: null,
            ':rid'  => ($tipo === 'producto' && $id_receta) ? $id_receta : null,
        ]);
        return $this->obtenerPorId((int)$this->db->lastInsertId());
    }

    public function obtenerTodos(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT c.*, r.nombre AS receta_nombre
             FROM cupones c LEFT JOIN recetas r ON r.id = c.id_receta AND r.comercio_id = {$this->cid}
             WHERE c.comercio_id = {$this->cid}
             ORDER BY c.created_at DESC"
        )->fetchAll();
    }

    public function obtenerPorId(int $id): array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT c.*, r.nombre AS receta_nombre
             FROM cupones c LEFT JOIN recetas r ON r.id = c.id_receta AND r.comercio_id = {$this->cid}
             WHERE c.id = :id AND c.comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function obtenerRecetasParaSelector(): array {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT id, nombre FROM recetas WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY nombre ASC"
            )->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    public function toggleEstado(int $id): bool {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT estado FROM cupones WHERE id = :id AND comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        $actual = $stmt->fetchColumn();
        if (!$actual || $actual === 'usado') return false;
        $nuevo = $actual === 'activo' ? 'inactivo' : 'activo';
        return (bool)$this->db->prepare(
            "UPDATE cupones SET estado = :e WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':e' => $nuevo, ':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "DELETE FROM cupones WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);
    }

    public function validar(string $codigo): array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT c.*, r.nombre AS receta_nombre
             FROM cupones c LEFT JOIN recetas r ON r.id = c.id_receta AND r.comercio_id = {$this->cid}
             WHERE c.codigo = :c AND c.estado = 'activo' AND c.comercio_id = {$this->cid}"
        );
        $stmt->execute([':c' => strtoupper(trim($codigo))]);
        $cup = $stmt->fetch();
        if (!$cup) return ['ok' => false, 'msg' => 'Cupón inválido o inactivo'];
        if ($cup['expira_en'] && $cup['expira_en'] < date('Y-m-d')) {
            return ['ok' => false, 'msg' => 'El cupón ha expirado'];
        }
        if ((int)$cup['usos_actual'] >= (int)$cup['usos_max']) {
            return ['ok' => false, 'msg' => 'El cupón ya alcanzó el límite de usos'];
        }
        return [
            'ok'            => true,
            'id'            => (int)$cup['id'],
            'tipo'          => $cup['tipo'],
            'descuento'     => (float)$cup['descuento'],
            'nombre'        => $cup['nombre'] ?? '',
            'id_receta'     => $cup['id_receta'] ? (int)$cup['id_receta'] : null,
            'receta_nombre' => $cup['receta_nombre'] ?? null,
        ];
    }

    public function registrarUso(int $id, float $montoDescuento = 0): void {
        $this->requireCid();
        $this->db->prepare(
            "UPDATE cupones
             SET usos_actual = usos_actual + 1,
                 estado = IF(usos_actual + 1 >= usos_max, 'usado', estado)
             WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);

        $stmt = $this->db->prepare(
            "SELECT codigo FROM cupones WHERE id = :id AND comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        $codigo = $stmt->fetchColumn() ?: '';

        $this->db->prepare(
            "INSERT INTO cupones_usos (comercio_id, id_cupon, codigo, monto_descuento)
             VALUES ({$this->cid}, :id, :codigo, :monto)"
        )->execute([':id' => $id, ':codigo' => $codigo, ':monto' => max(0, $montoDescuento)]);
    }

    public function graficaMensual(int $anio = 0): array {
        $this->requireCid();
        if (!$anio) $anio = (int)date('Y');
        $stmt = $this->db->prepare(
            "SELECT MONTH(fecha_uso) AS mes,
                    COUNT(*)                         AS usos,
                    COALESCE(SUM(monto_descuento),0) AS total
             FROM cupones_usos
             WHERE YEAR(fecha_uso) = :anio AND comercio_id = {$this->cid}
             GROUP BY MONTH(fecha_uso)"
        );
        $stmt->execute([':anio' => $anio]);
        $rows  = $stmt->fetchAll();

        $meses = array_fill(1, 12, ['usos' => 0, 'total' => 0.0]);
        foreach ($rows as $r) {
            $meses[(int)$r['mes']] = ['usos' => (int)$r['usos'], 'total' => (float)$r['total']];
        }
        return $meses;
    }

    public function estadisticas(): array {
        $this->requireCid();
        $row = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'activo')   AS activos,
                    SUM(estado = 'usado')    AS usados,
                    SUM(estado = 'inactivo') AS inactivos
             FROM cupones WHERE comercio_id = {$this->cid}"
        )->fetch();
        return [
            'total'     => (int)$row['total'],
            'activos'   => (int)$row['activos'],
            'usados'    => (int)$row['usados'],
            'inactivos' => (int)$row['inactivos'],
        ];
    }
}
?>
