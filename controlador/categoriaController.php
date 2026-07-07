<?php
// controlador/categoriaController.php

require_once 'config/config.php';
require_once 'modelo/categoriaRecetaModel.php';
require_once 'modelo/categoriaInsumoModel.php';

class CategoriaController {
    private $recetaModel;
    private $insumoModel;

    public function __construct() {
        $this->recetaModel = new CategoriaRecetaModel();
        $this->insumoModel = new CategoriaInsumoModel();
    }

    public function index() {
        $categoriasRecetas = $this->recetaModel->obtenerTodas();
        $categoriasInsumos = $this->insumoModel->obtenerTodas();
        require_once 'vista/categorias/index.php';
    }

    // ── Categorías de recetas ──────────────────────────────────────────────
    public function crearReceta() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }
        $res = $this->recetaModel->crear(
            $this->sanitizar($_POST['nombre'] ?? ''),
            $this->sanitizar($_POST['icono']  ?? 'fa-utensils')
        );
        $this->responder($res, 'Categoría creada exitosamente', 'Error al crear la categoría');
    }

    public function actualizarReceta() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->enviarError('ID no válido'); }
        $res = $this->recetaModel->editar(
            $id,
            $this->sanitizar($_POST['nombre'] ?? ''),
            $this->sanitizar($_POST['icono']  ?? 'fa-utensils')
        );
        $this->responder($res, 'Categoría actualizada exitosamente', 'Error al actualizar la categoría');
    }

    public function eliminarReceta() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->recetaModel->eliminar((int)($_POST['id'] ?? 0));
            $this->guardarFlash($res, 'Categoría eliminada exitosamente');
        }
        header("Location: " . Config::getBasePath() . "/categorias");
        exit;
    }

    // ── Categorías de insumos ───────────────────────────────────────────────
    public function crearInsumo() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }
        $res = $this->insumoModel->crear(
            $this->sanitizar($_POST['nombre'] ?? ''),
            $this->sanitizar($_POST['icono']  ?? 'fa-box')
        );
        $this->responder($res, 'Categoría creada exitosamente', 'Error al crear la categoría');
    }

    public function actualizarInsumo() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->enviarError('ID no válido'); }
        $res = $this->insumoModel->editar(
            $id,
            $this->sanitizar($_POST['nombre'] ?? ''),
            $this->sanitizar($_POST['icono']  ?? 'fa-box')
        );
        $this->responder($res, 'Categoría actualizada exitosamente', 'Error al actualizar la categoría');
    }

    public function eliminarInsumo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $this->insumoModel->eliminar((int)($_POST['id'] ?? 0));
            $this->guardarFlash($res, 'Categoría eliminada exitosamente');
        }
        header("Location: " . Config::getBasePath() . "/categorias");
        exit;
    }

    private function responder(array $res, string $msgOk, string $msgError): void {
        echo json_encode([
            'success' => $res['ok'],
            'message' => $res['ok'] ? $msgOk : ($res['msg'] ?? $msgError),
        ]);
        exit;
    }

    private function guardarFlash(array $res, string $msgOk): void {
        if ($res['ok']) {
            $_SESSION['success'] = $msgOk;
        } else {
            $_SESSION['error'] = $res['msg'] ?? 'Error al eliminar la categoría';
        }
    }

    private function sanitizar($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    private function enviarError($mensaje) {
        echo json_encode(['success' => false, 'message' => $mensaje]);
        exit;
    }
}
