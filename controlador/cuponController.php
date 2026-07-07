<?php
// controlador/cuponController.php
require_once 'config/config.php';
require_once 'modelo/cuponModel.php';

class CuponController {
    private CuponModel $model;

    public function __construct() {
        $this->model = new CuponModel();
    }

    public function index(): void {
        $cupones       = $this->model->obtenerTodos();
        $stats         = $this->model->estadisticas();
        $recetas       = $this->model->obtenerRecetasParaSelector();
        $graficaMeses  = $this->model->graficaMensual();
        require_once 'vista/cupones/index.php';
    }

    public function generar(): void {
        header('Content-Type: application/json');
        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre    = trim($body['nombre']    ?? '');
        $tipo      = trim($body['tipo']      ?? 'porcentaje');
        $descuento = (float)($body['descuento'] ?? 0);
        $usos_max  = max(1, (int)($body['usos_max'] ?? 1));
        $expira_en = trim($body['expira_en'] ?? '');
        $id_receta = (int)($body['id_receta'] ?? 0);
        $codigoCustom = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($body['codigo'] ?? '')));

        if (!in_array($tipo, ['porcentaje', 'valor', 'producto'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo inválido']); exit;
        }
        if ($descuento <= 0) {
            echo json_encode(['success' => false, 'message' => 'El descuento debe ser mayor a 0']); exit;
        }
        if (in_array($tipo, ['porcentaje', 'producto']) && $descuento > 100) {
            echo json_encode(['success' => false, 'message' => 'El porcentaje no puede superar 100']); exit;
        }
        if ($tipo === 'producto' && !$id_receta) {
            echo json_encode(['success' => false, 'message' => 'Debes seleccionar un producto']); exit;
        }
        if ($codigoCustom !== '') {
            if (strlen($codigoCustom) < 3 || strlen($codigoCustom) > 8) {
                echo json_encode(['success' => false, 'message' => 'El código debe tener entre 3 y 8 caracteres']); exit;
            }
            if (!$this->model->codigoDisponible($codigoCustom)) {
                echo json_encode(['success' => false, 'message' => 'Ese código ya existe, elige otro']); exit;
            }
        }

        $cupon = $this->model->crear($nombre, $tipo, $descuento, $usos_max, $expira_en ?: null, $id_receta ?: null, $codigoCustom ?: null);
        echo json_encode(['success' => true, 'cupon' => $cupon]);
        exit;
    }

    public function verificarCodigo(): void {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $codigo = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($body['codigo'] ?? '')));
        if (strlen($codigo) < 3) {
            echo json_encode(['disponible' => null]); exit;
        }
        echo json_encode(['disponible' => $this->model->codigoDisponible($codigo)]);
        exit;
    }

    public function toggle(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); exit; }
        echo json_encode(['success' => $this->model->toggleEstado($id)]);
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

    public function validar(): void {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $codigo = trim($body['codigo'] ?? '');
        if (!$codigo) { echo json_encode(['ok' => false, 'msg' => 'Código vacío']); exit; }
        echo json_encode($this->model->validar($codigo));
        exit;
    }
}
?>
