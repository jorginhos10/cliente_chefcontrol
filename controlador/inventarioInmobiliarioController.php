<?php
// controlador/inventarioInmobiliarioController.php

require_once 'config/config.php';
require_once 'modelo/inventarioInmobiliarioModel.php';
require_once 'modelo/inventarioSeccionModel.php';

class InventarioInmobiliarioController {
    private $model;
    private $seccionModel;
    private $uploadDir = __DIR__ . '/../assets/uploads/inventario_inmobiliario/';

    public function __construct() {
        $this->model        = new InventarioInmobiliarioModel();
        $this->seccionModel = new InventarioSeccionModel();
    }

    public function index() {
        $bienes          = $this->model->obtenerTodos();
        $estadisticas    = $this->model->obtenerEstadisticas();
        $secciones       = $this->seccionModel->obtenerArbol();
        $seccionesSelect = $this->seccionModel->obtenerParaSelect();
        require_once 'vista/inventario-inmobiliario/index.php';
    }

    public function crear() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        $nombre      = trim(htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8'));
        $valorTasado = (float) ($_POST['valor_tasado'] ?? 0);
        $seccionId   = !empty($_POST['seccion_id']) ? (int) $_POST['seccion_id'] : null;

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = ['El nombre es obligatorio'];
        }
        if ($valorTasado < 0) {
            $errores['valor_tasado'] = ['El valor evaluado no puede ser negativo'];
        }
        if (!empty($errores)) {
            echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]);
            exit;
        }

        $foto = $this->procesarFoto();

        $datos = [
            'nombre'       => $nombre,
            'valor_tasado' => $valorTasado,
            'foto'         => $foto,
            'seccion_id'   => $seccionId,
            'activo'       => 1,
        ];

        if ($this->model->crear($datos)) {
            echo json_encode(['success' => true, 'message' => 'Bien registrado exitosamente']);
        } else {
            $this->enviarError('Error al guardar el bien');
        }
        exit;
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($this->model->eliminar($id)) {
                $_SESSION['success'] = 'Bien eliminado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al eliminar el bien';
            }
        }
        header('Location: ' . Config::getBasePath() . '/inventario-inmobiliario');
        exit;
    }

    public function updateStatus() {
        header('Content-Type: application/json');

        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int) ($input['id']     ?? 0);
        $activo = (int) ($input['activo'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            exit;
        }

        if ($this->model->actualizarEstado($id, $activo)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error actualizando estado']);
        }
        exit;
    }

    // ── Secciones y subsecciones (partes del local) ────────────────────────

    public function secciones() {
        $arbol = $this->seccionModel->obtenerArbol();
        require_once 'vista/inventario-inmobiliario/secciones.php';
    }

    public function crearSeccion() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        $nombre   = $this->sanitizar($_POST['nombre'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $icono    = $this->sanitizar($_POST['icono'] ?? 'fa-door-open');

        $res = $this->seccionModel->crear($nombre, $parentId, $icono);
        echo json_encode([
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Sección creada exitosamente' : ($res['msg'] ?? 'Error al crear la sección'),
        ]);
        exit;
    }

    public function actualizarSeccion() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            $this->enviarError('ID no válido');
        }

        $nombre = $this->sanitizar($_POST['nombre'] ?? '');
        $icono  = $this->sanitizar($_POST['icono'] ?? 'fa-door-open');

        $res = $this->seccionModel->editar($id, $nombre, $icono);
        echo json_encode([
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Sección actualizada exitosamente' : ($res['msg'] ?? 'Error al actualizar la sección'),
        ]);
        exit;
    }

    public function eliminarSeccion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->seccionModel->eliminar((int) ($_POST['id'] ?? 0));
            if ($res['ok']) {
                $_SESSION['success'] = 'Sección eliminada exitosamente';
            } else {
                $_SESSION['error'] = $res['msg'] ?? 'Error al eliminar la sección';
            }
        }
        header('Location: ' . Config::getBasePath() . '/inventario-inmobiliario/secciones');
        exit;
    }

    private function procesarFoto(): ?string {
        if (empty($_FILES['foto']['name']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['foto'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) || $file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $filename = 'bien_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            return $filename;
        }
        return null;
    }

    private function sanitizar($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    private function enviarError($mensaje) {
        echo json_encode(['success' => false, 'message' => $mensaje]);
        exit;
    }
}
