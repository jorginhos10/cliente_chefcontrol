<?php
require_once 'modelo/usuarioModel.php';

class UsuarioController {
    private UsuarioModel $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    // ── Helper: abortar si el usuario es propietario ──────────────────────────
    private function esProtegido(int $id): bool {
        return $this->model->esPropietario($id);
    }

    public function index(): void {
        $usuarios = $this->model->obtenerTodos();
        require_once 'vista/usuarios/index.php';
    }

    public function crear(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $_POST = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        $datos = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password']     ?? '',
            'nombre'   => trim($_POST['nombre']  ?? ''),
            'email'    => trim($_POST['email']   ?? ''),
            'rol'      => $_POST['rol']          ?? 'cocina',
            'avatar'   => 'default.png',
            'activo'   => isset($_POST['activo']) ? (int)$_POST['activo'] : 1,
        ];

        $errores = [];
        if (empty($datos['username'])) $errores['username'] = ['El nombre de usuario es obligatorio'];
        if (empty($datos['password'])) $errores['password'] = ['La contraseña es obligatoria'];
        elseif (strlen($datos['password']) < 6) $errores['password'] = ['Mínimo 6 caracteres'];
        if (empty($datos['nombre']))   $errores['nombre']   = ['El nombre completo es obligatorio'];
        if (empty($datos['email']))    $errores['email']    = ['El email es obligatorio'];
        elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores['email'] = ['Email inválido'];
        if (isset($_POST['password_confirmation']) && $datos['password'] !== $_POST['password_confirmation'])
            $errores['password_confirmation'] = ['Las contraseñas no coinciden'];

        if (!empty($datos['username']) && $this->model->existeUsername($datos['username']))
            $errores['username'] = ['El nombre de usuario ya está en uso en este negocio'];
        if (!empty($datos['email']) && $this->model->existeEmail($datos['email']))
            $errores['email'] = ['El email ya está en uso en este negocio'];

        if (!empty($errores)) {
            echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]); exit;
        }

        $id = $this->model->crearUsuario($datos);
        echo json_encode($id
            ? ['success' => true,  'message' => 'Usuario creado exitosamente']
            : ['success' => false, 'message' => 'Error al crear el usuario']);
        exit;
    }

    public function get(mixed $id): void {
        header('Content-Type: application/json');
        $id = (int)$id;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no válido']); exit; }
        $u = $this->model->obtenerPorId($id);
        echo json_encode($u
            ? ['success' => true, 'data' => $u]
            : ['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    public function updateStatus(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $id     = (int)($input['id']     ?? 0);
        $activo = (int)($input['activo'] ?? 0);

        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no válido']); exit; }
        if ($this->esProtegido($id)) {
            echo json_encode(['success' => false, 'message' => 'No se puede modificar al propietario del negocio']); exit;
        }
        if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio estado']); exit;
        }

        echo json_encode($this->model->actualizarEstado($id, $activo)
            ? ['success' => true,  'message' => 'Estado actualizado']
            : ['success' => false, 'message' => 'Error al actualizar']);
        exit;
    }

    public function update(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id    = (int)($input['id'] ?? 0);
        $datos = [
            'nombre' => trim($input['nombre'] ?? ''),
            'email'  => trim($input['email']  ?? ''),
            'rol'    => $input['rol']          ?? 'cocina',
            'activo' => isset($input['activo']) ? (int)$input['activo'] : 0,
        ];

        $errores = [];
        if (empty($datos['nombre'])) $errores['nombre'] = ['El nombre es obligatorio'];
        if (empty($datos['email']))  $errores['email']  = ['El email es obligatorio'];
        elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) $errores['email'] = ['Email inválido'];

        if (!empty($errores)) {
            echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]); exit;
        }

        if ($this->esProtegido($id)) {
            echo json_encode(['success' => false, 'message' => 'No se puede modificar al propietario del negocio']); exit;
        }

        if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
            $actual = $this->model->obtenerPorId($id);
            if ($actual && $actual['rol'] !== $datos['rol']) {
                echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio rol']); exit;
            }
        }

        echo json_encode($this->model->actualizarUsuario($id, $datos)
            ? ['success' => true,  'message' => 'Usuario actualizado']
            : ['success' => false, 'message' => 'Error al actualizar']);
        exit;
    }

    public function loginConfig(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int)($input['id'] ?? 0);
        if (!$id || $this->esProtegido($id)) {
            echo json_encode(['success' => false, 'message' => 'No válido']); exit;
        }
        $json = isset($input['login_config']) ? json_encode($input['login_config']) : null;
        echo json_encode($this->model->guardarLoginConfig($id, $json)
            ? ['success' => true,  'message' => 'Configuración guardada']
            : ['success' => false, 'message' => 'Error al guardar']);
        exit;
    }

    public function eliminar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);

            if ($this->esProtegido($id)) {
                $_SESSION['error'] = 'No se puede eliminar al propietario del negocio.';
                header('Location: ' . Config::getBasePath() . '/usuarios'); exit;
            }
            if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
                $_SESSION['error'] = 'No puedes eliminar tu propio usuario.';
                header('Location: ' . Config::getBasePath() . '/usuarios'); exit;
            }

            $this->model->eliminarUsuario($id)
                ? $_SESSION['success'] = 'Usuario eliminado.'
                : $_SESSION['error']   = 'Error al eliminar el usuario.';
        }
        header('Location: ' . Config::getBasePath() . '/usuarios'); exit;
    }

    public function editar(mixed $id): void {
        $usuario = $this->model->obtenerPorId((int)$id);
        if (!$usuario) {
            $_SESSION['error'] = 'Usuario no encontrado';
            header('Location: ' . Config::getBasePath() . '/usuarios'); exit;
        }
        require_once 'vista/usuarios/editar.php';
    }

    public function resetPassword(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id       = (int)($input['id']       ?? 0);
        $password = $input['password'] ?? '';

        if (!$id)                   { echo json_encode(['success' => false, 'message' => 'ID no válido']); exit; }
        if (strlen($password) < 6)  { echo json_encode(['success' => false, 'message' => 'Mínimo 6 caracteres']); exit; }
        if ($this->esProtegido($id)) {
            echo json_encode(['success' => false, 'message' => 'No se puede cambiar la contraseña del propietario desde aquí']); exit;
        }
        if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'Usa el perfil para cambiar tu propia contraseña']); exit;
        }

        echo json_encode($this->model->actualizarContrasena($id, $password)
            ? ['success' => true,  'message' => 'Contraseña restablecida']
            : ['success' => false, 'message' => 'Error al restablecer']);
        exit;
    }

    public function getStats(): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $this->model->obtenerEstadisticas()]);
        exit;
    }

    public function getRealTimeStats(): void {
        header('Content-Type: application/json');
        if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Sin permisos']); exit;
        }
        $usuarios  = $this->model->obtenerTodos();
        $stats     = $this->model->obtenerEstadisticas();
        $hoy       = date('Y-m-d');
        $loginsHoy = 0; $cocina = 0; $inventario = 0; $meseros = 0;

        foreach ($usuarios as $u) {
            if ($u['ultimo_login'] && date('Y-m-d', strtotime($u['ultimo_login'])) === $hoy) $loginsHoy++;
            if ($u['activo']) {
                if ($u['rol'] === 'cocina')     $cocina++;
                if ($u['rol'] === 'inventario') $inventario++;
                if ($u['rol'] === 'mesero')     $meseros++;
            }
        }

        $stats = array_merge($stats, [
            'logins_hoy' => $loginsHoy,
            'total'      => count($usuarios),
            'cocina'     => $cocina,
            'inventario' => $inventario,
            'meseros'    => $meseros,
        ]);

        echo json_encode(['success' => true, 'stats' => $stats, 'timestamp' => date('Y-m-d H:i:s')]);
        exit;
    }

    public function perfil(): void {
        $id      = (int)($_SESSION['usuario_id'] ?? 0);
        $usuario = $this->model->obtenerPorId($id);
        if (!$usuario) { header('Location: ' . Config::getBasePath() . '/dashboard'); exit; }
        require_once 'vista/perfil/index.php';
    }

    public function guardarPerfil(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $id     = (int)($_SESSION['usuario_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email']  ?? '');

        if (empty($nombre)) { echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']); exit; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success' => false, 'message' => 'Email inválido']); exit; }

        $actual = $this->model->obtenerPorId($id);
        if ($actual && $actual['email'] !== $email && $this->model->existeEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'El email ya está en uso']); exit;
        }

        $datos = ['nombre' => $nombre, 'email' => $email];

        if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['avatar'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $file['size'] <= 2 * 1024 * 1024) {
                $dir = __DIR__ . '/../assets/media/users/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (!empty($actual['avatar']) && $actual['avatar'] !== 'default.png'
                    && file_exists($dir . $actual['avatar'])) {
                    unlink($dir . $actual['avatar']);
                }
                $filename = 'avatar_' . $id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                    $datos['avatar'] = $filename;
                    $_SESSION['usuario_avatar'] = $filename;
                }
            }
        }

        if ($this->model->actualizarPerfilPropio($id, $datos)) {
            $_SESSION['usuario_nombre'] = $nombre;
            echo json_encode(['success' => true, 'message' => 'Perfil actualizado', 'avatar' => $datos['avatar'] ?? null]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar']);
        }
        exit;
    }

    public function cambiarPassword(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $input        = json_decode(file_get_contents('php://input'), true) ?? [];
        $id           = (int)($_SESSION['usuario_id'] ?? 0);
        $actual       = trim($input['password_actual']    ?? '');
        $nueva        = trim($input['password_nueva']     ?? '');
        $confirmacion = trim($input['password_confirmar'] ?? '');

        if (empty($actual) || empty($nueva)) { echo json_encode(['success' => false, 'message' => 'Completa todos los campos']); exit; }
        if (strlen($nueva) < 6)             { echo json_encode(['success' => false, 'message' => 'Mínimo 6 caracteres']); exit; }
        if ($nueva !== $confirmacion)        { echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden']); exit; }
        if (!$this->model->verificarContrasena($id, $actual)) {
            echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']); exit;
        }

        echo json_encode($this->model->actualizarContrasena($id, $nueva)
            ? ['success' => true,  'message' => 'Contraseña actualizada']
            : ['success' => false, 'message' => 'Error al actualizar']);
        exit;
    }
}
