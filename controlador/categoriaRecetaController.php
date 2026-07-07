<?php
// controlador/categoriaRecetaController.php

require_once 'config/config.php';
require_once 'modelo/categoriaRecetaModel.php';

class CategoriaRecetaController {
    private $model;

    public function __construct() {
        $this->model = new CategoriaRecetaModel();
    }

    public function index() {
        $categorias = $this->model->obtenerTodas();
        require_once 'vista/categorias/index.php';
    }

    public function crear() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }

        $nombre = $this->sanitizar($_POST['nombre'] ?? '');
        $icono  = $this->sanitizar($_POST['icono']  ?? 'fa-utensils');

        $res = $this->model->crear($nombre, $icono);
        echo json_encode([
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Categoría creada exitosamente' : ($res['msg'] ?? 'Error al crear la categoría'),
        ]);
        exit;
    }

    public function actualizar() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->enviarError('ID no válido'); }

        $nombre = $this->sanitizar($_POST['nombre'] ?? '');
        $icono  = $this->sanitizar($_POST['icono']  ?? 'fa-utensils');

        $res = $this->model->editar($id, $nombre, $icono);
        echo json_encode([
            'success' => $res['ok'],
            'message' => $res['ok'] ? 'Categoría actualizada exitosamente' : ($res['msg'] ?? 'Error al actualizar la categoría'),
        ]);
        exit;
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id  = (int)($_POST['id'] ?? 0);
            $res = $this->model->eliminar($id);
            if ($res['ok']) {
                $_SESSION['success'] = 'Categoría eliminada exitosamente';
            } else {
                $_SESSION['error'] = $res['msg'] ?? 'Error al eliminar la categoría';
            }
        }
        header("Location: " . Config::getBasePath() . "/categorias");
        exit;
    }

    private function sanitizar($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    private function enviarError($mensaje) {
        echo json_encode(['success' => false, 'message' => $mensaje]);
        exit;
    }
}
