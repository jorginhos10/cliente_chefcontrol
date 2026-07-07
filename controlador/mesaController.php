<?php
// controlador/mesaController.php

require_once 'config/config.php';
require_once 'modelo/mesaModel.php';

class MesaController {
    private $mesaModel;

    public function __construct() {
        $this->mesaModel = new MesaModel();
    }

    public function index() {
        $mesas        = $this->mesaModel->obtenerTodasMesas();
        $estadisticas = $this->mesaModel->obtenerEstadisticas();
        require_once 'vista/mesas/index.php';
    }

    public function crear() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }

        $datos = $this->extraerDatos();

        if ($datos['numero'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'El número de mesa es obligatorio']);
            exit;
        }

        if ($this->mesaModel->numeroExiste($datos['numero'])) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una mesa con ese número']);
            exit;
        }

        $id = $this->mesaModel->crearMesa($datos);
        if ($id) {
            echo json_encode(['success' => true, 'message' => 'Mesa creada exitosamente', 'id' => $id]);
        } else {
            $this->enviarError('Error al guardar la mesa');
        }
        exit;
    }

    public function actualizar() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->enviarError('ID no válido'); }

        $datos = $this->extraerDatos();

        if ($datos['numero'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'El número de mesa es obligatorio']);
            exit;
        }

        if ($this->mesaModel->numeroExiste($datos['numero'], $id)) {
            echo json_encode(['success' => false, 'message' => 'Ya existe una mesa con ese número']);
            exit;
        }

        if ($this->mesaModel->actualizarMesa($id, $datos)) {
            echo json_encode(['success' => true, 'message' => 'Mesa actualizada exitosamente']);
        } else {
            $this->enviarError('Error al actualizar la mesa');
        }
        exit;
    }

    public function get($id) {
        header('Content-Type: application/json');
        $mesa = $this->mesaModel->obtenerMesaPorId($id);
        if (!$mesa) {
            echo json_encode(['success' => false, 'message' => 'Mesa no encontrada']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $mesa]);
        exit;
    }

    public function cambiarEstado() {
        header('Content-Type: application/json');
        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id']     ?? 0);
        $estado = trim($input['estado']  ?? '');

        if (!$id || !$estado) { echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit; }

        if ($this->mesaModel->cambiarEstado($id, $estado)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado', 'estado' => $estado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado']);
        }
        exit;
    }

    public function updateStatus() {
        header('Content-Type: application/json');
        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id']     ?? 0);
        $activo = (int)($input['activo'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no válido']); exit; }

        if ($this->mesaModel->actualizarActivo($id, $activo)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error actualizando estado']);
        }
        exit;
    }

    public function eliminar() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no válido']); exit; }

        if ($this->mesaModel->eliminarMesa($id)) {
            echo json_encode(['success' => true, 'message' => 'Mesa eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la mesa']);
        }
        exit;
    }

    private function extraerDatos() {
        return [
            'numero'    => (int)($_POST['numero']    ?? 0),
            'nombre'    => $this->sanitizar($_POST['nombre']    ?? ''),
            'capacidad' => max(1, (int)($_POST['capacidad'] ?? 1)),
            'zona'      => $this->sanitizar($_POST['zona']      ?? 'interior'),
            'estado'    => $this->sanitizar($_POST['estado']    ?? 'disponible'),
            'activo'    => isset($_POST['activo']) ? 1 : 0,
        ];
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
