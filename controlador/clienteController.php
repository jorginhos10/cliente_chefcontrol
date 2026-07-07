<?php
// controlador/clienteController.php
require_once 'config/config.php';
require_once 'modelo/clienteModel.php';

class ClienteController {
    private ClienteModel $model;

    public function __construct() {
        $this->model = new ClienteModel();
    }

    public function index(): void {
        $clientes     = $this->model->obtenerTodos();
        $estadisticas = $this->model->estadisticas();
        require_once 'vista/clientes/index.php';
    }

    public function crear(): void {
        header('Content-Type: application/json');
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre    = trim($body['nombre']    ?? '');
        $telefono  = trim($body['telefono']  ?? '');
        $tipo_doc  = trim($body['tipo_doc']  ?? '');
        $num_doc   = trim($body['num_doc']   ?? '');
        $email     = trim($body['email']     ?? '');
        $direccion = trim($body['direccion'] ?? '');
        $notas     = trim($body['notas']     ?? '');

        if (!$nombre) {
            echo json_encode(['success' => false, 'message' => 'El nombre o razón social es obligatorio']);
            exit;
        }

        $id = $this->model->crear($nombre, $telefono, $tipo_doc, $num_doc, $email, $direccion, $notas);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    public function actualizar(): void {
        header('Content-Type: application/json');
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $id        = (int)($body['id']        ?? 0);
        $nombre    = trim($body['nombre']    ?? '');
        $telefono  = trim($body['telefono']  ?? '');
        $tipo_doc  = trim($body['tipo_doc']  ?? '');
        $num_doc   = trim($body['num_doc']   ?? '');
        $email     = trim($body['email']     ?? '');
        $direccion = trim($body['direccion'] ?? '');
        $notas     = trim($body['notas']     ?? '');

        if (!$id || !$nombre) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }

        echo json_encode(['success' => $this->model->actualizar($id, $nombre, $telefono, $tipo_doc, $num_doc, $email, $direccion, $notas)]);
        exit;
    }

    public function get(int $id): void {
        header('Content-Type: application/json');
        $c = $this->model->obtenerPorId($id);
        if (!$c) { echo json_encode(['success' => false]); exit; }
        echo json_encode(['success' => true, 'cliente' => $c]);
        exit;
    }

    public function eliminar(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); exit; }
        echo json_encode(['success' => $this->model->eliminar($id)]);
        exit;
    }

    public function buscar(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        echo json_encode(['success' => true, 'clientes' => $this->model->buscar($q)]);
        exit;
    }

    public function toggleActivo(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); exit; }
        $this->model->toggleActivo($id);
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
