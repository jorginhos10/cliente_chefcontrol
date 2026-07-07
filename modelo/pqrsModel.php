<?php
// modelo/pqrsModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class PqrsModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerToken(): string {
        $this->requireCid();
        $token = $this->db->query(
            "SELECT token FROM pqrs_config WHERE comercio_id = {$this->cid} LIMIT 1"
        )->fetchColumn();
        if (!$token) {
            $token = bin2hex(random_bytes(16));
            $this->db->prepare(
                "INSERT INTO pqrs_config (comercio_id, token) VALUES ({$this->cid}, :t)"
            )->execute([':t' => $token]);
        }
        return $token;
    }

    public function obtenerTodos(array $filtros = []): array {
        $this->requireCid();
        $where  = ["comercio_id = {$this->cid}"];
        $params = [];
        if (!empty($filtros['tipo']))   { $where[] = "tipo = :tipo";     $params[':tipo']   = $filtros['tipo'];   }
        if (!empty($filtros['estado'])) { $where[] = "estado = :estado"; $params[':estado'] = $filtros['estado']; }
        if (!empty($filtros['q'])) {
            $where[] = "(nombre LIKE :q1 OR mensaje LIKE :q2)";
            $params[':q1'] = '%'.$filtros['q'].'%';
            $params[':q2'] = '%'.$filtros['q'].'%';
        }
        $sql  = "SELECT * FROM pqrs WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function crear(string $nombre, string $email, string $telefono,
                          string $tipo, int $calificacion, string $mensaje): int {
        $this->requireCid();
        $this->db->prepare(
            "INSERT INTO pqrs (comercio_id, nombre, email, telefono, tipo, calificacion, mensaje)
             VALUES ({$this->cid}, :n, :e, :t, :ti, :c, :m)"
        )->execute([':n' => $nombre, ':e' => $email ?: null, ':t' => $telefono ?: null,
                    ':ti' => $tipo, ':c' => $calificacion, ':m' => $mensaje]);
        return (int)$this->db->lastInsertId();
    }

    public function cambiarEstado(int $id, string $estado): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "UPDATE pqrs SET estado=:e, leido=1 WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':e' => $estado, ':id' => $id]);
    }

    public function responder(int $id, string $respuesta): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "UPDATE pqrs SET respuesta=:r, estado='resuelto', leido=1 WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':r' => $respuesta, ':id' => $id]);
    }

    public function marcarLeido(int $id): void {
        $this->requireCid();
        $this->db->prepare(
            "UPDATE pqrs SET leido=1 WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "DELETE FROM pqrs WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':id' => $id]);
    }

    public function estadisticas(): array {
        $this->requireCid();
        $total      = (int)$this->db->query("SELECT COUNT(*) FROM pqrs WHERE comercio_id = {$this->cid}")->fetchColumn();
        $pendientes = (int)$this->db->query("SELECT COUNT(*) FROM pqrs WHERE estado='pendiente' AND comercio_id = {$this->cid}")->fetchColumn();
        $resueltos  = (int)$this->db->query("SELECT COUNT(*) FROM pqrs WHERE estado='resuelto' AND comercio_id = {$this->cid}")->fetchColumn();
        $promedio   = round((float)$this->db->query("SELECT COALESCE(AVG(calificacion),0) FROM pqrs WHERE comercio_id = {$this->cid}")->fetchColumn(), 1);
        return compact('total', 'pendientes', 'resueltos', 'promedio');
    }

    public function contarPendientes(): int {
        $this->requireCid();
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM pqrs WHERE estado='pendiente' AND leido=0 AND comercio_id = {$this->cid}"
        )->fetchColumn();
    }
}
?>
