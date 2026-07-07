<?php
// config/permissions.php - SISTEMA DE PERMISOS REUTILIZABLE

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../modelo/permisoModel.php';

class Permisos {
    // CONSTANTES DE PERMISOS (AJUSTA SEGÚN TU BASE DE DATOS)
    const DASHBOARD = 1;        // ver_dashboard
    const RECETAS = 2;          // gestionar_recetas
    const INVENTARIO = 3;       // gestionar_inventario
    const REPORTES = 4;         // ver_reportes
    const CONFIGURACIONES = 5;  // configurar_sistema
    const USUARIOS = 6;         // gestionar_usuarios
    const PERMISOS = 7;         // gestionar_permisos
    
    private static $permisosCache = null;
    
    /**
     * Verificar si el usuario actual tiene un permiso específico
     * @param int $permisoId ID del permiso a verificar
     * @return bool True si tiene permiso, False si no
     */
    public static function tienePermiso($permisoId) {
        // Verificar sesión
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }
        
        // Los administradores tienen acceso a TODO
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin') {
            return true;
        }
        
        // Cargar permisos del usuario (caché)
        if (self::$permisosCache === null) {
            self::cargarPermisosUsuario();
        }
        
        // Verificar si tiene el permiso específico
        return in_array($permisoId, self::$permisosCache);
    }
    
    /**
     * Cargar permisos del usuario actual en caché
     */
    private static function cargarPermisosUsuario() {
        self::$permisosCache = [];
        
        try {
            $permisoModel = new PermisoModel();
            $permisos = $permisoModel->obtenerPermisosUsuario($_SESSION['usuario_id']);
            
            foreach ($permisos as $permiso) {
                if ($permiso['activo'] == 1) {
                    self::$permisosCache[] = $permiso['id'];
                }
            }
            
            error_log("Permisos cargados para usuario ID " . $_SESSION['usuario_id'] . ": " . 
                     count(self::$permisosCache) . " permisos activos");
        } catch (Exception $e) {
            error_log("Error cargando permisos: " . $e->getMessage());
        }
    }
    
    /**
     * Requerir un permiso - Si no lo tiene, redirige a 403
     * @param int $permisoId ID del permiso requerido
     * @param string $mensajeError Mensaje opcional para mostrar
     */
    public static function requerirPermiso($permisoId, $mensajeError = null) {
        if (!self::tienePermiso($permisoId)) {
            $mensaje = $mensajeError ?: 'No tienes permisos para acceder a esta sección';
            $_SESSION['error'] = $mensaje;
            
            // Registrar intento de acceso no autorizado
            error_log("Acceso denegado: Usuario ID " . ($_SESSION['usuario_id'] ?? '0') . 
                     " intentó acceder a recurso que requiere permiso ID $permisoId");
            
            // Redirigir a 403
            header("Location: " . Config::getBasePath() . "/403.php");
            exit;
        }
        
        // Registrar acceso autorizado (opcional)
        error_log("Acceso autorizado: Usuario ID " . $_SESSION['usuario_id'] . 
                 " accedió a recurso con permiso ID $permisoId");
    }
    
    /**
     * Obtener todos los permisos activos del usuario actual
     * @return array Array de IDs de permisos activos
     */
    public static function obtenerPermisosUsuario() {
        if (self::$permisosCache === null) {
            self::cargarPermisosUsuario();
        }
        return self::$permisosCache;
    }
    
    /**
     * Verificar si el usuario puede ver un item específico en el sidebar
     * @param int $permisoId ID del permiso requerido
     * @return bool
     */
    public static function puedeVerEnSidebar($permisoId) {
        return self::tienePermiso($permisoId);
    }
    
    /**
     * Limpiar caché de permisos (útil después de cambios)
     */
    public static function limpiarCache() {
        self::$permisosCache = null;
    }
}
?>