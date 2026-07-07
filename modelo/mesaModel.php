<?php
// modelo/mesaModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class MesaModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodasMesas() {
        $this->requireCid();
        $sql = "SELECT m.*,
                    (SELECT COUNT(*) FROM ventas v
                     WHERE v.id_mesa = m.id AND v.comercio_id = {$this->cid} AND v.estado = 'abierta'
                    ) AS tiene_orden_activa
                FROM mesas m
                WHERE m.comercio_id = {$this->cid}
                ORDER BY m.numero ASC";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo mesas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMesaPorId($id) {
        $this->requireCid();
        $sql = "SELECT * FROM mesas WHERE id = :id AND comercio_id = :cid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id',  (int)$id,      PDO::PARAM_INT);
            $stmt->bindValue(':cid', $this->cid,    PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo mesa: " . $e->getMessage());
            return false;
        }
    }

    public function crearMesa($datos) {
        $this->requireCid();
        $sql = "INSERT INTO mesas (comercio_id, numero, nombre, capacidad, zona, estado, activo)
                VALUES (:cid, :numero, :nombre, :capacidad, :zona, :estado, :activo)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':cid',       $this->cid,           PDO::PARAM_INT);
            $stmt->bindValue(':numero',    $datos['numero'],      PDO::PARAM_INT);
            $stmt->bindValue(':nombre',    $datos['nombre']);
            $stmt->bindValue(':capacidad', $datos['capacidad'],   PDO::PARAM_INT);
            $stmt->bindValue(':zona',      $datos['zona']);
            $stmt->bindValue(':estado',    $datos['estado']);
            $stmt->bindValue(':activo',    $datos['activo'],      PDO::PARAM_INT);
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creando mesa: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarMesa($id, $datos) {
        $this->requireCid();
        $sql = "UPDATE mesas SET
                    numero    = :numero,
                    nombre    = :nombre,
                    capacidad = :capacidad,
                    zona      = :zona,
                    activo    = :activo
                WHERE id = :id AND comercio_id = :cid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':numero',    $datos['numero'],    PDO::PARAM_INT);
            $stmt->bindValue(':nombre',    $datos['nombre']);
            $stmt->bindValue(':capacidad', $datos['capacidad'], PDO::PARAM_INT);
            $stmt->bindValue(':zona',      $datos['zona']);
            $stmt->bindValue(':activo',    $datos['activo'],    PDO::PARAM_INT);
            $stmt->bindValue(':id',        (int)$id,            PDO::PARAM_INT);
            $stmt->bindValue(':cid',       $this->cid,          PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando mesa: " . $e->getMessage());
            return false;
        }
    }

    public function cambiarEstado($id, $estado) {
        $this->requireCid();
        $estadosValidos = ['disponible', 'ocupada', 'reservada', 'mantenimiento'];
        if (!in_array($estado, $estadosValidos)) return false;

        $sql = "UPDATE mesas SET estado = :estado WHERE id = :id AND comercio_id = :cid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $estado);
            $stmt->bindValue(':id',     (int)$id,   PDO::PARAM_INT);
            $stmt->bindValue(':cid',    $this->cid, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error cambiando estado de mesa: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarActivo($id, $activo) {
        $this->requireCid();
        $sql = "UPDATE mesas SET activo = :activo WHERE id = :id AND comercio_id = :cid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':activo', (int)$activo, PDO::PARAM_INT);
            $stmt->bindValue(':id',     (int)$id,     PDO::PARAM_INT);
            $stmt->bindValue(':cid',    $this->cid,   PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando activo mesa: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarMesa($id) {
        $this->requireCid();
        $sql = "DELETE FROM mesas WHERE id = :id AND comercio_id = :cid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id',  (int)$id,   PDO::PARAM_INT);
            $stmt->bindValue(':cid', $this->cid, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando mesa: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas() {
        $this->requireCid();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'disponible'    THEN 1 ELSE 0 END) AS disponibles,
                    SUM(CASE WHEN estado = 'ocupada'       THEN 1 ELSE 0 END) AS ocupadas,
                    SUM(CASE WHEN estado = 'reservada'     THEN 1 ELSE 0 END) AS reservadas,
                    SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) AS mantenimiento,
                    SUM(capacidad) AS capacidad_total
                FROM mesas
                WHERE activo = 1 AND comercio_id = {$this->cid}";
        try {
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            error_log("Error estadísticas mesas: " . $e->getMessage());
            return ['total'=>0,'disponibles'=>0,'ocupadas'=>0,'reservadas'=>0,'mantenimiento'=>0,'capacidad_total'=>0];
        }
    }

    public function numeroExiste($numero, $excludeId = 0) {
        $this->requireCid();
        $sql = "SELECT COUNT(*) FROM mesas
                WHERE numero = :numero AND id != :id AND comercio_id = :cid";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':numero', (int)$numero,    PDO::PARAM_INT);
            $stmt->bindValue(':id',     (int)$excludeId, PDO::PARAM_INT);
            $stmt->bindValue(':cid',    $this->cid,      PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
