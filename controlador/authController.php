<?php
// controlador/authController.php

require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../modelo/comercioModel.php';
require_once __DIR__ . '/../modelo/usuarioModel.php';

class AuthController {

    public function login(): void {
        $identifier = trim($_POST['username'] ?? '');
        $password   = $_POST['password']      ?? '';

        if (empty($identifier) || empty($password)) {
            $_SESSION['error'] = 'Completa todos los campos.';
            header('Location: ' . Config::getBasePath() . '/login');
            exit;
        }

        // 1. Buscar usuario globalmente por username o email
        $resultado = ComercioModel::findUserGlobal($identifier);

        if (!$resultado) {
            $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
            header('Location: ' . Config::getBasePath() . '/login');
            exit;
        }

        if (!empty($resultado['ambiguo'])) {
            $_SESSION['error'] = 'Existen varias cuentas con ese nombre de usuario. Ingresa con tu correo electrónico.';
            header('Location: ' . Config::getBasePath() . '/login');
            exit;
        }

        $usuario  = $resultado;
        $comercio = $usuario['comercio'];

        // 2. Verificar contraseña primero
        if (!password_verify($password, $usuario['password'])) {
            $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
            header('Location: ' . Config::getBasePath() . '/login');
            exit;
        }

        // 3. Comprobar si el comercio está activo
        if (!(int)($comercio['activo'] ?? 0)) {
            $_SESSION['error'] = 'Su comercio se encuentra temporalmente suspendido. Contacte al soporte.';
            header('Location: ' . Config::getBasePath() . '/login');
            exit;
        }

        // 3. Fijar tenant y actualizar último login
        $_SESSION['comercio_id'] = (int)$usuario['comercio_id'];
        DB::get()->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$usuario['id']]);

        // 4. Verificar restricción de horario/día
        $usuarioModel = new UsuarioModel();
        $restriccion  = $usuarioModel->verificarRestriccionLogin($usuario);
        if ($restriccion) {
            unset($_SESSION['comercio_id']);
            $_SESSION['login_restriccion'] = $restriccion;
            header('Location: ' . Config::getBasePath() . '/login');
            exit;
        }

        // 5. Completar sesión (login directo — no es impersonación)
        unset($_SESSION['impersonando']);
        $_SESSION['logged_in']        = true;
        $_SESSION['last_activity']    = time();
        $_SESSION['usuario_id']       = $usuario['id'];
        $_SESSION['usuario_username'] = $usuario['username'];
        $_SESSION['usuario_nombre']   = $usuario['nombre'];
        $_SESSION['usuario_email']    = $usuario['email'];
        $_SESSION['usuario_rol']      = $usuario['rol'];
        $_SESSION['usuario_avatar']   = $usuario['avatar'];
        $_SESSION['comercio_nombre']  = $comercio['nombre'];
        $_SESSION['comercio_slug']    = $comercio['slug'];

        $redirect = $_SESSION['redirect_url'] ?? Config::getBasePath() . '/dashboard';
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirect);
        exit;
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        header('Location: ' . Config::getBasePath() . '/login');
        exit;
    }
}
