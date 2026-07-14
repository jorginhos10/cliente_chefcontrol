<?php
// controlador/registroController.php

require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../modelo/comercioModel.php';
require_once __DIR__ . '/../modelo/registroModel.php';

class RegistroController {

    private function validarToken(string $token): bool {
        if (!$token) return false;
        try {
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            $db   = new PDO(
                "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                Config::DB_USER, Config::DB_PASS, $opts
            );
            $s = $db->prepare("SELECT id FROM registro_invitaciones WHERE token=? AND usado=0 AND expira_en>NOW() LIMIT 1");
            $s->execute([$token]);
            return (bool)$s->fetch();
        } catch (\Throwable $e) { return false; }
    }

    private function marcarTokenUsado(string $token): void {
        try {
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $db   = new PDO(
                "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                Config::DB_USER, Config::DB_PASS, $opts
            );
            $db->prepare("UPDATE registro_invitaciones SET usado=1 WHERE token=?")->execute([$token]);
        } catch (\Throwable $e) {}
    }

    private function registroWebActivo(): bool {
        try {
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            $db   = new PDO(
                "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                Config::DB_USER, Config::DB_PASS, $opts
            );
            $s = $db->prepare("SELECT valor FROM sup_config WHERE clave = 'registro_web' LIMIT 1");
            $s->execute();
            $row = $s->fetchColumn();
            return $row === false || $row === '1'; // si no existe, activo por defecto
        } catch (\Throwable $e) { return true; }
    }

    public function index(): void {
        if (!empty($_SESSION['logged_in'])) {
            header('Location: ' . Config::getBasePath() . '/dashboard');
            exit;
        }

        // Un link de invitación con token válido es independiente del switch
        // global de registro: funciona aunque el registro público esté cerrado.
        $token       = trim($_GET['token'] ?? '');
        $tokenValido = $token && $this->validarToken($token);

        if (!$this->registroWebActivo() && !$tokenValido) {
            require_once __DIR__ . '/../vista/registro/registro_cerrado.php';
            exit;
        }
        require_once __DIR__ . '/../vista/registro/registro.php';
    }

    public function crear(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . Config::getBasePath() . '/registro');
            exit;
        }

        // Token opcional — si viene y es válido se marcará usado y permite
        // registrarse aunque el registro público esté desactivado.
        $token        = trim($_POST['token'] ?? '');
        $tokenValido  = $token && $this->validarToken($token);

        if (!$this->registroWebActivo() && !$tokenValido) {
            header('Location: ' . Config::getBasePath() . '/registro');
            exit;
        }

        $nombreComercio = trim($_POST['nombre_comercio'] ?? '');
        $nombreAdmin    = trim($_POST['nombre_admin']    ?? '');
        $email          = trim($_POST['email']           ?? '');
        $username       = trim($_POST['username']        ?? '');
        $password       = $_POST['password']             ?? '';
        $passwordConf   = $_POST['password_confirm']     ?? '';
        $telefono       = trim($_POST['telefono']        ?? '');

        $errores = [];
        if (strlen($nombreComercio) < 3)
            $errores[] = 'El nombre del restaurante debe tener al menos 3 caracteres.';
        if (strlen($nombreAdmin) < 2)
            $errores[] = 'Ingresa tu nombre completo.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errores[] = 'El email no es válido.';
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username))
            $errores[] = 'El usuario debe tener 3-30 caracteres alfanuméricos o guiones bajos.';
        if (strlen($password) < 6)
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        if ($password !== $passwordConf)
            $errores[] = 'Las contraseñas no coinciden.';

        $backUrl = Config::getBasePath() . '/registro' . ($token ? '?token='.urlencode($token) : '');

        if (!empty($errores)) {
            $_SESSION['registro_error'] = implode('<br>', $errores);
            $_SESSION['registro_datos'] = compact('nombreComercio','nombreAdmin','email','username','telefono');
            header('Location: ' . $backUrl);
            exit;
        }

        $model     = new RegistroModel();
        $resultado = $model->registrar($nombreComercio, $nombreAdmin, $email, $username, $password, $telefono);

        if (!$resultado['success']) {
            $_SESSION['registro_error'] = $resultado['message'];
            $_SESSION['registro_datos'] = compact('nombreComercio','nombreAdmin','email','username','telefono');
            header('Location: ' . $backUrl);
            exit;
        }

        // Marcar token como usado solo si era válido
        if ($tokenValido) $this->marcarTokenUsado($token);

        // Auto-login — redirige a verificación de documentos
        $_SESSION['logged_in']        = true;
        $_SESSION['last_activity']    = time();
        $_SESSION['comercio_id']      = $resultado['comercio_id'];
        $_SESSION['comercio_slug']    = $resultado['slug'];
        $_SESSION['comercio_nombre']  = $nombreComercio;
        $_SESSION['usuario_id']       = $resultado['usuario_id'] ?? null;
        $_SESSION['usuario_username'] = $username;
        $_SESSION['usuario_nombre']   = $nombreAdmin;
        $_SESSION['usuario_email']    = $email;
        $_SESSION['usuario_rol']      = 'admin';

        header('Location: ' . Config::getBasePath() . '/verificacion');
        exit;
    }
}
