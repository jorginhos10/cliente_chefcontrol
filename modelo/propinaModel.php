<?php
// modelo/propinaModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class PropinaModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function registrar(float $monto, ?int $id_mesa, ?int $mesa_numero, ?string $mesa_nombre, ?string $numero_orden, ?int $id_usuario): bool {
        $this->requireCid();
        if ($monto <= 0) return false;
        $stmt = $this->db->prepare(
            "INSERT INTO propinas (comercio_id, monto, id_mesa, mesa_numero, mesa_nombre, numero_orden, id_usuario)
             VALUES ({$this->cid}, :monto, :id_mesa, :mesa_num, :mesa_nom, :num_orden, :id_usuario)"
        );
        return $stmt->execute([
            ':monto'      => $monto,
            ':id_mesa'    => $id_mesa,
            ':mesa_num'   => $mesa_numero,
            ':mesa_nom'   => $mesa_nombre,
            ':num_orden'  => $numero_orden,
            ':id_usuario' => $id_usuario,
        ]);
    }

    public function obtenerResumenHoy(): array {
        $this->requireCid();
        $hoy = date('Y-m-d');
        $row = $this->db->prepare(
            "SELECT COUNT(*) AS total_registros, COALESCE(SUM(monto), 0) AS total_monto
             FROM propinas WHERE DATE(created_at) = :hoy AND comercio_id = {$this->cid}"
        );
        $row->execute([':hoy' => $hoy]);
        return $row->fetch() ?: ['total_registros' => 0, 'total_monto' => 0];
    }

    public function obtenerPaginadas(int $pagina, int $porPagina, array $filtros): array {
        $this->requireCid();
        $where  = ["p.comercio_id = {$this->cid}"];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]           = 'DATE(p.created_at) >= :desde';
            $params[':desde']  = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]           = 'DATE(p.created_at) <= :hasta';
            $params[':hasta']  = $filtros['hasta'];
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $offset   = ($pagina - 1) * $porPagina;

        $sql = "SELECT p.*, u.nombre AS usuario_nombre
                FROM propinas p
                LEFT JOIN usuarios u ON u.id = p.id_usuario AND u.comercio_id = {$this->cid}
                $whereStr
                ORDER BY p.created_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,    PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contar(array $filtros): int {
        $this->requireCid();
        $where  = ["comercio_id = {$this->cid}"];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]          = 'DATE(created_at) >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]          = 'DATE(created_at) <= :hasta';
            $params[':hasta'] = $filtros['hasta'];
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM propinas $whereStr");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function acumuladoPorUsuario(array $filtros): array {
        $this->requireCid();
        $where  = ["p.comercio_id = {$this->cid}"];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]          = 'DATE(p.created_at) >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]          = 'DATE(p.created_at) <= :hasta';
            $params[':hasta'] = $filtros['hasta'];
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(u.nombre, 'Sin asignar') AS usuario_nombre,
                COUNT(*) AS cantidad,
                COALESCE(SUM(p.monto), 0) AS total,
                MIN(p.created_at) AS primera,
                MAX(p.created_at) AS ultima
             FROM propinas p
             LEFT JOIN usuarios u ON u.id = p.id_usuario AND u.comercio_id = {$this->cid}
             $whereStr
             GROUP BY p.id_usuario, u.nombre
             ORDER BY total DESC"
        );
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function totalPorRango(array $filtros): float {
        $this->requireCid();
        $where  = ["comercio_id = {$this->cid}"];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]          = 'DATE(created_at) >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]          = 'DATE(created_at) <= :hasta';
            $params[':hasta'] = $filtros['hasta'];
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(monto), 0) FROM propinas $whereStr");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (float)$stmt->fetchColumn();
    }
}
?>
