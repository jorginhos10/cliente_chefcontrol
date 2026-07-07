<?php
// modelo/permisoModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class PermisoModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    // lista_permisos es tabla global (sin comercio_id) — catalogo del sistema
    // detalle_permiso y usuarios sí requieren filtro por comercio_id

    public function obtenerPermisosUsuario($usuario_id) {
        $this->requireCid();
        $sql = "SELECT
                    lp.id,
                    lp.nombre,
                    CASE WHEN dp.id IS NOT NULL THEN 1 ELSE 0 END AS activo,
                    dp.fecha_asignacion
                FROM lista_permisos lp
                LEFT JOIN detalle_permiso dp
                    ON lp.id = dp.id_permiso AND dp.id_usuario = :usuario_id AND dp.comercio_id = {$this->cid}
                WHERE lp.estado = 1
                ORDER BY lp.nombre ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':usuario_id', (int)$usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            $permisos = $stmt->fetchAll();

            foreach ($permisos as &$permiso) {
                $permiso['nombre_formateado'] = $this->formatearNombrePermiso($permiso['nombre']);
                $permiso['descripcion']       = $this->getDescripcionPermiso($permiso['nombre']);
            }

            return $permisos;
        } catch (PDOException $e) {
            error_log("Error obteniendo permisos de usuario: " . $e->getMessage());
            return [];
        }
    }

    private function formatearNombrePermiso($nombre) {
        $nombres = [
            'crear_usuarios'       => 'Crear Usuarios',
            'editar_usuarios'      => 'Editar Usuarios',
            'eliminar_usuarios'    => 'Eliminar Usuarios',
            'ver_reportes'         => 'Ver Reportes',
            'gestionar_inventario' => 'Gestionar Inventario',
            'ver_dashboard'        => 'Ver Dashboard',
            'configurar_sistema'   => 'Configurar Sistema',
        ];
        return $nombres[$nombre] ?? ucwords(str_replace('_', ' ', $nombre));
    }

    private function getDescripcionPermiso($nombre) {
        $descripciones = [
            'crear_usuarios'       => 'Permite crear nuevos usuarios en el sistema',
            'editar_usuarios'      => 'Permite editar información de usuarios existentes',
            'eliminar_usuarios'    => 'Permite eliminar usuarios del sistema',
            'ver_reportes'         => 'Permite ver reportes y estadísticas del sistema',
            'gestionar_inventario' => 'Permite gestionar el inventario de productos',
            'ver_dashboard'        => 'Permite ver el panel principal de control',
            'configurar_sistema'   => 'Permite configurar ajustes del sistema',
        ];
        return $descripciones[$nombre] ?? 'Permiso del sistema';
    }

    public function asignarPermisoUsuario($usuario_id, $permiso_id) {
        $this->requireCid();
        try {
            $stmtCheck = $this->db->prepare(
                "SELECT id FROM lista_permisos WHERE id = :permiso_id AND estado = 1"
            );
            $stmtCheck->bindValue(':permiso_id', (int)$permiso_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            if (!$stmtCheck->fetch()) return false;

            $stmtExiste = $this->db->prepare(
                "SELECT id FROM detalle_permiso
                 WHERE id_usuario = :uid AND id_permiso = :pid AND comercio_id = {$this->cid}"
            );
            $stmtExiste->bindValue(':uid', (int)$usuario_id, PDO::PARAM_INT);
            $stmtExiste->bindValue(':pid', (int)$permiso_id,  PDO::PARAM_INT);
            $stmtExiste->execute();
            if ($stmtExiste->fetch()) return true;

            $stmtIns = $this->db->prepare(
                "INSERT INTO detalle_permiso (comercio_id, id_usuario, id_permiso, fecha_asignacion)
                 VALUES ({$this->cid}, :uid, :pid, NOW())"
            );
            $stmtIns->bindValue(':uid', (int)$usuario_id, PDO::PARAM_INT);
            $stmtIns->bindValue(':pid', (int)$permiso_id,  PDO::PARAM_INT);
            return $stmtIns->execute();
        } catch (PDOException $e) {
            error_log("Error asignando permiso: " . $e->getMessage());
            return false;
        }
    }

    public function quitarPermisoUsuario($usuario_id, $permiso_id) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM detalle_permiso
                 WHERE id_usuario = :uid AND id_permiso = :pid AND comercio_id = {$this->cid}"
            );
            $stmt->bindValue(':uid', (int)$usuario_id, PDO::PARAM_INT);
            $stmt->bindValue(':pid', (int)$permiso_id,  PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error quitando permiso: " . $e->getMessage());
            return false;
        }
    }

    public function togglePermisoUsuario($usuario_id, $permiso_id, $nuevo_estado) {
        return $nuevo_estado == 1
            ? $this->asignarPermisoUsuario($usuario_id, $permiso_id)
            : $this->quitarPermisoUsuario($usuario_id, $permiso_id);
    }

    public function obtenerEstadisticasPermisos($usuario_id) {
        $this->requireCid();
        $sql = "SELECT
                    (SELECT COUNT(*) FROM detalle_permiso dp
                     JOIN lista_permisos lp ON dp.id_permiso = lp.id
                     WHERE dp.id_usuario = :uid AND lp.estado = 1 AND dp.comercio_id = {$this->cid}) AS permisos_asignados,
                    (SELECT COUNT(*) FROM lista_permisos WHERE estado = 1) AS total_permisos_disponibles";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':uid', (int)$usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            return [
                'permisos_asignados'          => (int)$row['permisos_asignados'],
                'total_permisos_disponibles'  => (int)$row['total_permisos_disponibles'],
            ];
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas permisos: " . $e->getMessage());
            return ['permisos_asignados' => 0, 'total_permisos_disponibles' => 0];
        }
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

    public function permisoEstaActivo($permiso_id) {
        try {
            $stmt = $this->db->prepare("SELECT estado FROM lista_permisos WHERE id = :pid");
            $stmt->bindValue(':pid', (int)$permiso_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result && $result['estado'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerTotalPermisosActivos() {
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM lista_permisos WHERE estado = 1")->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function obtenerTodosPermisos() {
        try {
            return $this->db->query(
                "SELECT id, nombre, estado FROM lista_permisos ORDER BY nombre"
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getDB() {
        return $this->db;
    }
}
?>
