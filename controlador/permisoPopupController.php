<?php
// controlador/permisoPopupController.php

require_once 'modelo/permisoModel.php';

class PermisoPopupController {
    private $permisoModel;

    private const MOD_INFO = [
        'ventas'         => ['Ventas / Caja',    'Acceso al punto de venta y módulo de caja'],
        'cocina'         => ['Pantalla Cocina',   'Visualización de órdenes en pantalla de cocina'],
        'mesas'          => ['Mesas',             'Gestión de mesas del salón'],
        'domicilios'     => ['Domicilios',        'Gestión de pedidos a domicilio'],
        'clientes'       => ['Clientes',          'Gestión de clientes y fidelización'],
        'cupones'        => ['Descuentos',        'Gestión de cupones y descuentos'],
        'pqrs'           => ['PQRS',             'Gestión de quejas, reclamos y sugerencias'],
        'propinas'       => ['Propinas',          'Registro y consulta de propinas'],
        'recetas'        => ['Recetas',           'Gestión de recetas y menú'],
        'insumos'        => ['Insumos',           'Gestión de ingredientes e insumos'],
        'insumos-internos' => ['Uso Interno',     'Gestión de insumos de uso interno (limpieza, papelería, etc.)'],
        'inventario'     => ['Inventario',        'Control de inventario y stock'],
        'proveedores'    => ['Proveedores',       'Gestión de proveedores'],
        'ingresos'       => ['Ingresos',          'Registro de ingresos del negocio'],
        'perdidas'       => ['Pérdidas',          'Registro de pérdidas y mermas'],
        'reportes'       => ['Reportes',          'Consulta de reportes y estadísticas'],
        'chat'           => ['Chat',              'Chat de soporte interno'],
        'notificaciones' => ['Notificaciones',    'Gestión de notificaciones'],
    ];

    public function __construct() {
        $this->permisoModel = new PermisoModel();
        $this->migrar();
    }

    private function migrar(): void {
        try {
            $this->permisoModel->getDB()->exec(
                "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS modulos_config TEXT NULL"
            );
        } catch (\Throwable $e) {}
    }

    // ── Helpers plan/usuario ──────────────────────────────────────────────────

    private function getModulosPlan(): array {
        try {
            $cid = (int)($_SESSION['comercio_id'] ?? 0);
            if (!$cid) return [];
            $row = $this->permisoModel->getDB()->prepare(
                "SELECT plan FROM comercios WHERE id=? LIMIT 1"
            );
            $row->execute([$cid]);
            $planSlug = $row->fetchColumn();
            if (!$planSlug) return [];

            $opts  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            $dbSup = new PDO("mysql:host=".Config::DB_HOST.";dbname=".Config::DB_NAME_SUP.";charset=utf8mb4",
                             Config::DB_USER, Config::DB_PASS, $opts);
            $ps = $dbSup->prepare("SELECT modulos FROM planes WHERE slug=? LIMIT 1");
            $ps->execute([$planSlug]);
            $json = $ps->fetchColumn();
            return $json ? (json_decode($json, true) ?? []) : [];
        } catch (\Throwable $e) { return []; }
    }

    private function getUsuarioModulosDesactivados(int $usuario_id): array {
        try {
            $cid  = (int)($_SESSION['comercio_id'] ?? 0);
            $stmt = $this->permisoModel->getDB()->prepare(
                "SELECT modulos_config FROM usuarios WHERE id=? AND comercio_id=? LIMIT 1"
            );
            $stmt->execute([$usuario_id, $cid]);
            $json = $stmt->fetchColumn();
            return $json ? (json_decode($json, true) ?? []) : [];
        } catch (\Throwable $e) { return []; }
    }

    private function setUsuarioModulosDesactivados(int $usuario_id, array $desactivados): bool {
        try {
            $cid  = (int)($_SESSION['comercio_id'] ?? 0);
            $stmt = $this->permisoModel->getDB()->prepare(
                "UPDATE usuarios SET modulos_config=? WHERE id=? AND comercio_id=?"
            );
            return $stmt->execute([
                empty($desactivados) ? null : json_encode(array_values($desactivados)),
                $usuario_id,
                $cid,
            ]);
        } catch (\Throwable $e) { return false; }
    }

    // ── Guards comunes ────────────────────────────────────────────────────────

    private function guardAuth(): void {
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            exit;
        }
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para gestionar accesos']);
            exit;
        }
    }

    private function guardUsuario(int $usuario_id): void {
        if ($usuario_id == 1) {
            echo json_encode(['success' => false, 'message' => 'No se pueden modificar los permisos del administrador principal']);
            exit;
        }
        if ($usuario_id == $_SESSION['usuario_id']) {
            echo json_encode(['success' => false, 'message' => 'No puedes modificar tus propios permisos']);
            exit;
        }
    }

    // ── GET permisos popup ────────────────────────────────────────────────────

    public function getPermisosPopup($usuario_id) {
        header('Content-Type: application/json');
        $this->guardAuth();

        if (empty($usuario_id) || !is_numeric($usuario_id)) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
            exit;
        }
        $usuario_id = (int)$usuario_id;
        $this->guardUsuario($usuario_id);

        try {
            $planModulos = $this->getModulosPlan();
            $userDes     = $this->getUsuarioModulosDesactivados($usuario_id);
            $infoUsuario = $this->permisoModel->obtenerInfoUsuarioParaPermisos($usuario_id);

            if (!$infoUsuario) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                exit;
            }

            if (empty($planModulos)) {
                echo json_encode(['success' => false, 'message' => 'El plan no tiene módulos configurados']);
                exit;
            }

            $permisos = [];
            foreach ($planModulos as $slug) {
                $info       = self::MOD_INFO[$slug] ?? [ucfirst($slug), ''];
                $permisos[] = [
                    'id'               => $slug,
                    'nombre'           => $slug,
                    'activo'           => in_array($slug, $userDes) ? 0 : 1,
                    'nombre_formateado'=> $info[0],
                    'descripcion'      => $info[1],
                    'fecha_asignacion' => null,
                ];
            }

            $asignados = count(array_filter($permisos, fn($p) => $p['activo'] == 1));

            echo json_encode([
                'success'      => true,
                'usuario'      => $infoUsuario,
                'permisos'     => $permisos,
                'estadisticas' => [
                    'permisos_asignados'         => $asignados,
                    'total_permisos_disponibles' => count($permisos),
                ],
                'total_permisos' => count($permisos),
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
        exit;
    }

    // ── TOGGLE permiso popup ──────────────────────────────────────────────────

    public function togglePermisoPopup() {
        header('Content-Type: application/json');
        $this->guardAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $input    = json_decode($rawInput, true);
        if (!$input) { parse_str($rawInput, $input); }
        if (!$input) { $input = $_POST; }

        $usuario_id  = (int)($input['usuario_id']  ?? 0);
        $modulo_slug = trim($input['permiso_id']    ?? '');
        $nuevo_estado= (int)($input['nuevo_estado'] ?? 0);

        if (!$usuario_id || !$modulo_slug) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }
        $this->guardUsuario($usuario_id);

        // Validar que el slug es un módulo del plan
        $planModulos = $this->getModulosPlan();
        if (!in_array($modulo_slug, $planModulos)) {
            echo json_encode(['success' => false, 'message' => 'Módulo no disponible en el plan']);
            exit;
        }

        $desactivados = $this->getUsuarioModulosDesactivados($usuario_id);

        if ($nuevo_estado == 1) {
            $desactivados = array_values(array_diff($desactivados, [$modulo_slug]));
        } else {
            if (!in_array($modulo_slug, $desactivados)) {
                $desactivados[] = $modulo_slug;
            }
        }

        if (!$this->setUsuarioModulosDesactivados($usuario_id, $desactivados)) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar cambios']);
            exit;
        }

        $asignados = count(array_diff($planModulos, $desactivados));

        echo json_encode([
            'success' => true,
            'message' => $nuevo_estado ? '✅ Módulo habilitado' : '❌ Módulo deshabilitado',
            'nuevo_estado' => $nuevo_estado,
            'estadisticas' => [
                'permisos_asignados'         => $asignados,
                'total_permisos_disponibles' => count($planModulos),
            ],
        ]);
        exit;
    }

    public function getAllPermisos() {
        header('Content-Type: application/json');
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
        echo json_encode(['success' => true, 'permisos' => [], 'total' => 0]);
        exit;
    }
}
?>
