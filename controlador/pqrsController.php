<?php
// controlador/pqrsController.php
require_once 'config/config.php';
require_once 'modelo/pqrsModel.php';

class PqrsController {
    private PqrsModel $model;

    public function __construct() {
        $this->model = new PqrsModel();
    }

    public function index(): void {
        $filtros  = ['tipo' => $_GET['tipo'] ?? '', 'estado' => $_GET['estado'] ?? '', 'q' => trim($_GET['q'] ?? '')];
        $pqrsList = $this->model->obtenerTodos($filtros);
        $stats    = $this->model->estadisticas();
        $token    = $this->model->obtenerToken();
        require_once 'modelo/comercioModel.php';
        $comercio = (new ComercioModel())->obtener();
        require_once 'vista/pqrs/index.php';
    }

    public function formPorToken(string $token): void {
        if ($token !== $this->model->obtenerToken()) {
            http_response_code(404);
            die('Enlace no encontrado.');
        }
        require_once 'modelo/comercioModel.php';
        $comercio = (new ComercioModel())->obtener();
        require_once 'vista/pqrs/form.php';
    }

    public function enviar(): void {
        header('Content-Type: application/json');
        $body         = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre       = trim($body['nombre']       ?? '');
        $email        = trim($body['email']        ?? '');
        $telefono     = trim($body['telefono']     ?? '');
        $tipo         = trim($body['tipo']         ?? 'sugerencia');
        $calificacion = max(1, min(5, (int)($body['calificacion'] ?? 5)));
        $mensaje      = trim($body['mensaje']      ?? '');

        if (!$nombre || !$mensaje) {
            echo json_encode(['success' => false, 'message' => 'Nombre y mensaje son obligatorios']); exit;
        }
        if (!in_array($tipo, ['peticion','queja','reclamo','sugerencia'])) $tipo = 'sugerencia';

        $id = $this->model->crear($nombre, $email, $telefono, $tipo, $calificacion, $mensaje);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    public function cambiarEstado(): void {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $id     = (int)($body['id']     ?? 0);
        $estado = trim($body['estado']  ?? '');
        if (!$id || !in_array($estado, ['pendiente','en_revision','resuelto'])) {
            echo json_encode(['success' => false]); exit;
        }
        echo json_encode(['success' => $this->model->cambiarEstado($id, $estado)]);
        exit;
    }

    public function responder(): void {
        header('Content-Type: application/json');
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $id        = (int)($body['id']        ?? 0);
        $respuesta = trim($body['respuesta']  ?? '');
        if (!$id || !$respuesta) { echo json_encode(['success' => false]); exit; }
        echo json_encode(['success' => $this->model->responder($id, $respuesta)]);
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

    public function json(): void {
        header('Content-Type: application/json');
        $filtros = ['tipo' => $_GET['tipo'] ?? '', 'estado' => $_GET['estado'] ?? '', 'q' => $_GET['q'] ?? ''];
        echo json_encode(['success' => true, 'data' => $this->model->obtenerTodos($filtros),
                          'stats'   => $this->model->estadisticas()]);
        exit;
    }
}
?>
