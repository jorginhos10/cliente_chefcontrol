<?php
// controlador/recuperarController.php

require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../modelo/recuperarModel.php';

class RecuperarController {

    public function index(): void {
        if (!empty($_SESSION['logged_in'])) {
            header('Location: ' . Config::getBasePath() . '/dashboard');
            exit;
        }
        require_once __DIR__ . '/../vista/recuperar/index.php';
    }

    public function enviar(): void {
        header('Content-Type: application/json');
        $telefono = trim($_POST['telefono'] ?? '');

        if (strlen(preg_replace('/\D/', '', $telefono)) < 10) {
            echo json_encode(['ok' => false, 'msg' => 'Ingresa un número de teléfono válido.']);
            exit;
        }

        $model = new RecuperarModel();
        echo json_encode($model->enviarCodigo($telefono));
        exit;
    }

    public function verificar(): void {
        header('Content-Type: application/json');
        $resetId = (int)($_POST['reset_id'] ?? 0);
        $codigo  = trim($_POST['codigo'] ?? '');

        if (!$resetId || !preg_match('/^\d{6}$/', $codigo)) {
            echo json_encode(['ok' => false, 'msg' => 'Código inválido.']);
            exit;
        }

        $model = new RecuperarModel();
        echo json_encode($model->verificarCodigo($resetId, $codigo));
        exit;
    }

    public function reset(): void {
        header('Content-Type: application/json');
        $resetId  = (int)($_POST['reset_id'] ?? 0);
        $codigo   = trim($_POST['codigo'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirmation'] ?? '';

        if (!$resetId || !preg_match('/^\d{6}$/', $codigo)) {
            echo json_encode(['ok' => false, 'msg' => 'Código inválido.']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres.']);
            exit;
        }
        if ($password !== $confirm) {
            echo json_encode(['ok' => false, 'msg' => 'Las contraseñas no coinciden.']);
            exit;
        }

        $model = new RecuperarModel();
        echo json_encode($model->verificarYResetear($resetId, $codigo, $password));
        exit;
    }
}
