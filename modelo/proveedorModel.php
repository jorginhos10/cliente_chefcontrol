<?php
// modelo/proveedorModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class proveedorModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodosProveedores() {
        $this->requireCid();
        $sql = "SELECT * FROM proveedores WHERE comercio_id = {$this->cid} ORDER BY created_at DESC";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo proveedores: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProveedorPorId($id) {
        $this->requireCid();
        $sql = "SELECT * FROM proveedores WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo proveedor ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function crearProveedor($datos) {
        $this->requireCid();
        try {
            $sql = "INSERT INTO proveedores
                        (comercio_id, nombre, empresa, telefono, direccion, correo,
                         categoria, foto, observacion, nit_rut, activo)
                    VALUES
                        ({$this->cid}, :nombre, :empresa, :telefono, :direccion, :correo,
                         :categoria, :foto, :observacion, :nit_rut, :activo)";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre',      $datos['nombre']);
            $stmt->bindValue(':empresa',     $datos['empresa']);
            $stmt->bindValue(':telefono',    $datos['telefono']);
            $stmt->bindValue(':direccion',   $datos['direccion']);
            $stmt->bindValue(':correo',      $datos['correo']);
            $stmt->bindValue(':categoria',   $datos['categoria']);
            $stmt->bindValue(':foto',        $datos['foto']);
            $stmt->bindValue(':observacion', $datos['observacion']);
            $stmt->bindValue(':nit_rut',     $datos['nit_rut']);
            $stmt->bindValue(':activo',      $datos['activo']);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creando proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarProveedor($id) {
        $this->requireCid();
        $sql = "DELETE FROM proveedores WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstado($id, $activo) {
        $this->requireCid();
        $sql = "UPDATE proveedores SET activo = :activo WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':activo', (int)$activo, PDO::PARAM_INT);
            $stmt->bindValue(':id',     (int)$id,     PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando estado: " . $e->getMessage());
            return false;
        }
    }

    public function buscarProveedores($termino = '') {
        $this->requireCid();
        $sql = "SELECT * FROM proveedores
                WHERE comercio_id = {$this->cid}
                  AND (nombre   LIKE :t1
                    OR empresa  LIKE :t2
                    OR telefono LIKE :t3
                    OR correo   LIKE :t4
                    OR nit_rut  LIKE :t5)
                ORDER BY nombre";
        try {
            $stmt    = $this->db->prepare($sql);
            $like    = '%' . $termino . '%';
            $stmt->bindValue(':t1', $like);
            $stmt->bindValue(':t2', $like);
            $stmt->bindValue(':t3', $like);
            $stmt->bindValue(':t4', $like);
            $stmt->bindValue(':t5', $like);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error buscando proveedores: " . $e->getMessage());
            return [];
        }
    }

    public function actualizarProveedor($id, $datos) {
        $this->requireCid();
        try {
            $setClauses = [
                'nombre      = :nombre',
                'empresa     = :empresa',
                'telefono    = :telefono',
                'direccion   = :direccion',
                'correo      = :correo',
                'categoria   = :categoria',
                'observacion = :observacion',
                'nit_rut     = :nit_rut',
                'activo      = :activo',
            ];

            if (isset($datos['foto'])) {
                $setClauses[] = 'foto = :foto';
            }

            $sql  = "UPDATE proveedores SET " . implode(', ', $setClauses) . " WHERE id = :id AND comercio_id = {$this->cid}";
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':id',          (int)$id,              PDO::PARAM_INT);
            $stmt->bindValue(':nombre',      $datos['nombre']);
            $stmt->bindValue(':empresa',     $datos['empresa']);
            $stmt->bindValue(':telefono',    $datos['telefono']);
            $stmt->bindValue(':direccion',   $datos['direccion']);
            $stmt->bindValue(':correo',      $datos['correo']);
            $stmt->bindValue(':categoria',   $datos['categoria']);
            $stmt->bindValue(':observacion', $datos['observacion']);
            $stmt->bindValue(':nit_rut',     $datos['nit_rut']);
            $stmt->bindValue(':activo',      $datos['activo'],       PDO::PARAM_INT);

            if (isset($datos['foto'])) {
                $stmt->bindValue(':foto', $datos['foto']);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerCategorias() {
        $this->requireCid();
        $sql = "SELECT DISTINCT categoria FROM proveedores
                WHERE comercio_id = {$this->cid} AND categoria IS NOT NULL AND categoria != ''
                ORDER BY categoria";
        try {
            $resultados = $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
            return $resultados ?: ['cocina', 'inventario', 'mesero', 'admin'];
        } catch (PDOException $e) {
            return ['cocina', 'inventario', 'mesero', 'admin'];
        }
    }
}
?>
