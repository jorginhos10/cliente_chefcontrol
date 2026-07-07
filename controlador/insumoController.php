<?php
// controlador/insumoController.php

require_once 'config/config.php';
require_once 'modelo/insumoModel.php';

class InsumoController {
    private $insumoModel;

    public function __construct() {
        $this->insumoModel = new InsumoModel();
    }

    public function index() {
        $insumos      = $this->insumoModel->obtenerTodosInsumos();
        $estadisticas = $this->insumoModel->obtenerEstadisticas();
        $proveedores  = $this->insumoModel->obtenerProveedores();
        require_once 'vista/insumos/index.php';
    }

    public function crear() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        $datos = [
            'nombre'          => $this->sanitizar($_POST['nombre']          ?? ''),
            'descripcion'     => $this->sanitizar($_POST['descripcion']     ?? ''),
            'categoria'       => $this->sanitizar($_POST['categoria']       ?? 'otros'),
            'unidad_medida'   => $this->sanitizar($_POST['unidad_medida']   ?? 'unidad'),
            'cantidad_stock'  => (float) ($_POST['cantidad_stock']          ?? 0),
            'cantidad_minima' => (float) ($_POST['cantidad_minima']         ?? 0),
            'precio_unitario' => (float) ($_POST['precio_unitario']         ?? 0),
            'id_proveedor'    => !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null,
            'activo'          => isset($_POST['activo']) ? 1 : 0,
        ];

        $errores = [];
        if (empty($datos['nombre'])) {
            $errores['nombre'] = ['El nombre es obligatorio'];
        }
        if ($datos['cantidad_stock'] < 0) {
            $errores['cantidad_stock'] = ['El stock no puede ser negativo'];
        }

        if (!empty($errores)) {
            echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]);
            exit;
        }

        if ($this->insumoModel->crearInsumo($datos)) {
            echo json_encode(['success' => true, 'message' => 'Insumo creado exitosamente']);
        } else {
            $this->enviarError('Error al guardar el insumo');
        }
        exit;
    }

    public function actualizar() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            $this->enviarError('ID no válido');
        }

        $datos = [
            'nombre'          => $this->sanitizar($_POST['nombre']          ?? ''),
            'descripcion'     => $this->sanitizar($_POST['descripcion']     ?? ''),
            'categoria'       => $this->sanitizar($_POST['categoria']       ?? 'otros'),
            'unidad_medida'   => $this->sanitizar($_POST['unidad_medida']   ?? 'unidad'),
            'cantidad_stock'  => (float) ($_POST['cantidad_stock']          ?? 0),
            'cantidad_minima' => (float) ($_POST['cantidad_minima']         ?? 0),
            'precio_unitario' => (float) ($_POST['precio_unitario']         ?? 0),
            'id_proveedor'    => !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null,
            'activo'          => isset($_POST['activo']) ? 1 : 0,
        ];

        if (empty($datos['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
            exit;
        }

        if ($this->insumoModel->actualizarInsumo($id, $datos)) {
            echo json_encode(['success' => true, 'message' => 'Insumo actualizado exitosamente']);
        } else {
            $this->enviarError('Error al actualizar el insumo');
        }
        exit;
    }

    public function get($id) {
        header('Content-Type: application/json');

        $insumo = $this->insumoModel->obtenerInsumoPorId($id);
        if ($insumo) {
            echo json_encode(['success' => true, 'data' => $insumo]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insumo no encontrado']);
        }
        exit;
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($this->insumoModel->eliminarInsumo($id)) {
                $_SESSION['success'] = 'Insumo eliminado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al eliminar el insumo';
            }
        }
        header("Location: " . Config::getBasePath() . "/insumos");
        exit;
    }

    public function updateStatus() {
        header('Content-Type: application/json');

        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id']     ?? 0);
        $activo = (int)($input['activo'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            exit;
        }

        if ($this->insumoModel->actualizarEstado($id, $activo)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error actualizando estado']);
        }
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
?>
