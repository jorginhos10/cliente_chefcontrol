<?php
// controlador/inventarioController.php

require_once 'config/config.php';
require_once 'modelo/inventarioModel.php';

class InventarioController {
    private $model;

    public function __construct() {
        $this->model = new InventarioModel();
    }

    public function index() {
        $estadisticas = $this->model->obtenerEstadisticas();
        $movimientos  = $this->model->obtenerMovimientos(30);
        require_once 'vista/inventario/index.php';
    }

    public function registrar() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $id_insumo   = (int)($_POST['id_insumo']   ?? 0);
        $tipo        = trim($_POST['tipo']          ?? '');
        $cantidad    = (float)($_POST['cantidad']   ?? 0);
        $descripcion = trim($_POST['descripcion']   ?? '');
        $id_usuario  = $_SESSION['usuario_id']      ?? null;
        $id_proveedor = ($tipo === 'entrada' && !empty($_POST['id_proveedor']))
                        ? (int)$_POST['id_proveedor'] : null;

        if (!$id_insumo) {
            echo json_encode(['success' => false, 'message' => 'Selecciona un insumo válido']); exit;
        }
        if (!in_array($tipo, ['entrada', 'venta', 'salida'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo de movimiento inválido']); exit;
        }
        if ($cantidad <= 0) {
            echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a cero']); exit;
        }

        $resultado = $this->model->registrarMovimiento($id_insumo, $tipo, $cantidad, $descripcion, $id_usuario, $id_proveedor);

        if ($resultado['ok']) {
            echo json_encode([
                'success'         => true,
                'message'         => 'Movimiento registrado correctamente',
                'stock_nuevo'     => $resultado['stock_nuevo'],
                'stock_anterior'  => $resultado['stock_anterior'],
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $resultado['msg']]);
        }
        exit;
    }

    public function historial() {
        header('Content-Type: application/json');
        $id_insumo = isset($_GET['insumo']) ? (int)$_GET['insumo'] : null;
        $limite    = min((int)($_GET['limite'] ?? 50), 200);
        $movs      = $this->model->obtenerMovimientos($limite, $id_insumo);
        echo json_encode(['success' => true, 'data' => $movs]);
        exit;
    }
}
?>
