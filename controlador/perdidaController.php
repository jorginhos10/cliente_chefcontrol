<?php
// controlador/perdidaController.php
require_once 'config/config.php';
require_once 'modelo/perdidaModel.php';

class PerdidaController {
    private PerdidaModel $model;

    public function __construct() {
        $this->model = new PerdidaModel();
    }

    public function index(): void {
        $desde   = $_GET['desde'] ?? date('Y-m-01');
        $hasta   = $_GET['hasta'] ?? date('Y-m-d');
        $stats   = $this->model->estadisticas($desde, $hasta);
        $salidas = $this->model->obtenerSalidas($desde, $hasta);
        $insumos = $this->model->getInsumos();
        require_once 'vista/perdidas/index.php';
    }

    public function registrar(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $idInsumo   = (int)($_POST['id_insumo']  ?? 0);
        $cantidad   = (float)($_POST['cantidad']  ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $idUsuario  = (int)($_SESSION['usuario_id'] ?? 0) ?: null;

        if (!$idInsumo || $cantidad <= 0) {
            echo json_encode(['success' => false, 'message' => 'Selecciona un insumo y cantidad válida']); exit;
        }

        if ($this->model->registrarSalida($idInsumo, $cantidad, $descripcion, $idUsuario)) {
            echo json_encode(['success' => true, 'message' => 'Salida registrada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar la salida']);
        }
        exit;
    }
}
?>
