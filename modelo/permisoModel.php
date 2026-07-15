<?php
// modelo/permisoModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class PermisoModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerInfoUsuarioParaPermisos($usuario_id) {
        $this->requireCid();
        $sql = "SELECT id, username, nombre, email, rol, avatar
                FROM usuarios WHERE id = :uid AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':uid', (int)$usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch();
            if ($usuario) {
                $roles = ['admin' => 'Administrador', 'cocina' => 'Cocina', 'inventario' => 'Inventario', 'mesero' => 'Mesero'];
                $usuario['rol_formateado'] = $roles[$usuario['rol']] ?? ucfirst($usuario['rol']);
            }
            return $usuario;
        } catch (PDOException $e) {
            error_log("Error obteniendo info de usuario: " . $e->getMessage());
            return [];
        }
    }

    public function getDB() {
        return $this->db;
    }
}
?>
