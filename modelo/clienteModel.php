<?php
// modelo/clienteModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class ClienteModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT * FROM clientes WHERE comercio_id = {$this->cid} ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function obtenerPorId(int $id): array|false {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT * FROM clientes WHERE id = :id AND comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function crear(string $nombre, string $telefono, string $tipo_doc, string $num_doc,
                          string $email, string $direccion, string $notas): int {
        $this->requireCid();
        $this->db->prepare(
            "INSERT INTO clientes (comercio_id, nombre, telefono, tipo_doc, num_doc, email, direccion, notas)
             VALUES ({$this->cid}, :n, :t, :td, :nd, :e, :d, :no)"
        )->execute([':n' => $nombre, ':t' => $telefono, ':td' => $tipo_doc ?: null,
                    ':nd' => $num_doc ?: null, ':e' => $email,
                    ':d' => $direccion, ':no' => $notas]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, string $telefono, string $tipo_doc,
                                string $num_doc, string $email, string $direccion, string $notas): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "UPDATE clientes SET nombre=:n, telefono=:t, tipo_doc=:td, num_doc=:nd,
             email=:e, direccion=:d, notas=:no, updated_at=NOW()
             WHERE id=:id AND comercio_id={$this->cid}"
        )->execute([':n' => $nombre, ':t' => $telefono, ':td' => $tipo_doc ?: null,
                    ':nd' => $num_doc ?: null, ':e' => $email,
                    ':d' => $direccion, ':no' => $notas, ':id' => $id]);
    }

    public function toggleActivo(int $id): void {
        $this->requireCid();
        $this->db->prepare(
            "UPDATE clientes SET activo = 1 - activo WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "DELETE FROM clientes WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);
    }

    public function buscar(string $q, int $limite = 20): array {
        $this->requireCid();
        if ($q === '') {
            $stmt = $this->db->prepare(
                "SELECT id, nombre, telefono, email, tipo_doc, num_doc
                 FROM clientes WHERE activo = 1 AND comercio_id = {$this->cid}
                 ORDER BY nombre ASC LIMIT :lim"
            );
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare(
            "SELECT id, nombre, telefono, email, tipo_doc, num_doc FROM clientes
             WHERE activo = 1 AND comercio_id = {$this->cid}
               AND (nombre LIKE :q1 OR telefono LIKE :q2 OR email LIKE :q3 OR num_doc LIKE :q4)
             ORDER BY nombre ASC LIMIT :lim"
        );
        $stmt->bindValue(':q1',  $like);
        $stmt->bindValue(':q2',  $like);
        $stmt->bindValue(':q3',  $like);
        $stmt->bindValue(':q4',  $like);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function estadisticas(): array {
        $this->requireCid();
        $total   = (int)$this->db->query("SELECT COUNT(*) FROM clientes WHERE comercio_id = {$this->cid}")->fetchColumn();
        $activos = (int)$this->db->query("SELECT COUNT(*) FROM clientes WHERE activo = 1 AND comercio_id = {$this->cid}")->fetchColumn();
        $nuevos  = (int)$this->db->query(
            "SELECT COUNT(*) FROM clientes
             WHERE comercio_id = {$this->cid}
               AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
        )->fetchColumn();
        return compact('total', 'activos', 'nuevos');
    }
}
?>
