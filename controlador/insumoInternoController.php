<?php
// controlador/insumoInternoController.php

require_once 'config/config.php';
require_once 'modelo/insumoInternoModel.php';

class InsumoInternoController {
    private $model;

    public function __construct() {
        $this->model = new InsumoInternoModel();
    }

    public function index() {
        $insumos      = $this->model->obtenerTodos();
        $estadisticas = $this->model->obtenerEstadisticas();
        $proveedores  = $this->model->obtenerProveedores();
        $movimientos  = $this->model->obtenerMovimientos(30);
        require_once 'vista/insumos-internos/index.php';
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

        if ($this->model->crear($datos)) {
            echo json_encode(['success' => true, 'message' => 'Insumo interno creado exitosamente']);
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

        if ($this->model->actualizar($id, $datos)) {
            echo json_encode(['success' => true, 'message' => 'Insumo interno actualizado exitosamente']);
        } else {
            $this->enviarError('Error al actualizar el insumo');
        }
        exit;
    }

    public function get($id) {
        header('Content-Type: application/json');

        $insumo = $this->model->obtenerPorId($id);
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
            if ($this->model->eliminar($id)) {
                $_SESSION['success'] = 'Insumo eliminado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al eliminar el insumo';
            }
        }
        header('Location: ' . Config::getBasePath() . '/insumos-internos');
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

        if ($this->model->actualizarEstado($id, $activo)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error actualizando estado']);
        }
        exit;
    }

    public function registrarMovimiento() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $id_insumo    = (int)($_POST['id_insumo']   ?? 0);
        $tipo         = trim($_POST['tipo']          ?? '');
        $cantidad     = (float)($_POST['cantidad']   ?? 0);
        $descripcion  = trim($_POST['descripcion']   ?? '');
        $id_proveedor = !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null;
        $id_usuario   = $_SESSION['usuario_id'] ?? null;

        if (!$id_insumo) {
            echo json_encode(['success' => false, 'message' => 'Selecciona un insumo']); exit;
        }
        if (!in_array($tipo, ['entrada', 'salida'], true)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de movimiento no válido']); exit;
        }
        if ($cantidad <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ingresa una cantidad válida']); exit;
        }

        $r = $this->model->registrarMovimiento($id_insumo, $tipo, $cantidad, $descripcion, $id_usuario, $id_proveedor);
        echo json_encode($r['ok']
            ? ['success' => true, 'message' => 'Movimiento registrado', 'stock_anterior' => $r['stock_anterior'], 'stock_nuevo' => $r['stock_nuevo']]
            : ['success' => false, 'message' => $r['msg']]
        );
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
