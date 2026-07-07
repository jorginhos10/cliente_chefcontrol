<?php
// controlador/ingresosController.php
require_once 'config/config.php';
require_once 'modelo/ingresosModel.php';

class IngresosController {
    private IngresosModel $model;

    public function __construct() {
        $this->model = new IngresosModel();
    }

    public function index(): void {
        $desde  = $_GET['desde']  ?? date('Y-m-01');
        $hasta  = $_GET['hasta']  ?? date('Y-m-d');
        $estado = $_GET['estado'] ?? '';

        $ingresos     = $this->model->obtenerTodos($desde, $hasta, $estado);
        $estadisticas = $this->model->estadisticas($desde, $hasta);
        $proveedores  = $this->model->getProveedores();
        $insumosLista = $this->model->getInsumos();
        $radicado     = $this->model->generarRadicado();

        require_once 'vista/ingresos/index.php';
    }

    public function crear(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $datos = $this->extraerDatos();
        $items = $this->extraerItems();

        if (empty($datos[':fecha'])) {
            echo json_encode(['success' => false, 'message' => 'La fecha es obligatoria']); exit;
        }
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Agrega al menos un artículo']); exit;
        }

        try {
            $id = $this->model->crear($datos, $items);
            echo json_encode(['success' => true, 'message' => 'Ingreso registrado correctamente', 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el ingreso']);
        }
        exit;
    }

    public function actualizar(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $id    = (int)($_POST['id'] ?? 0);
        $datos = $this->extraerDatos();
        $items = $this->extraerItems();

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
        }
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Agrega al menos un artículo']); exit;
        }

        $ok = $this->model->actualizar($id, $datos, $items);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Ingreso actualizado' : 'Error al actualizar']);
        exit;
    }

    public function get(int $id): void {
        header('Content-Type: application/json');
        $ingreso = $this->model->obtenerPorId($id);
        if (!$ingreso) {
            echo json_encode(['success' => false, 'message' => 'No encontrado']); exit;
        }
        $items = $this->model->obtenerItems($id);
        echo json_encode(['success' => true, 'data' => $ingreso, 'items' => $items]);
        exit;
    }

    public function eliminar(): void {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); exit; }
        $ok = $this->model->eliminar($id);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Eliminado' : 'Error al eliminar']);
        exit;
    }

    private function extraerDatos(): array {
        $subtotal    = (float)($_POST['subtotal'] ?? 0);
        $impPct      = (float)($_POST['impuesto_pct'] ?? 0);
        $impuesto    = round($subtotal * $impPct / 100, 2);
        $idProveedor = (int)($_POST['id_proveedor'] ?? 0) ?: null;
        return [
            ':radicado'       => trim($_POST['radicado'] ?? $this->model->generarRadicado()),
            ':fecha'          => date('Y-m-d'),
            ':tipo_documento' => 'Boleta',
            ':serie'          => '',
            ':numero'         => '',
            ':concepto'       => trim($_POST['concepto'] ?? ''),
            ':subtotal'       => $subtotal,
            ':impuesto'       => $impuesto,
            ':total'          => $subtotal + $impuesto,
            ':estado'         => 'Aceptado',
            ':id_usuario'     => (int)($_SESSION['usuario_id'] ?? 0) ?: null,
            ':id_proveedor'   => $idProveedor,
        ];
    }

    private function extraerItems(): array {
        $articulos  = $_POST['articulo']        ?? [];
        $cantidades = $_POST['cantidad']         ?? [];
        $precios    = $_POST['precio_unitario']  ?? [];
        $subtotales = $_POST['item_subtotal']    ?? [];

        $items = [];
        foreach ($articulos as $i => $art) {
            $art = trim($art);
            if (!$art) continue;
            $cant    = (float)($cantidades[$i] ?? 1);
            $precio  = (float)($precios[$i]    ?? 0);
            $items[] = [
                'articulo'        => $art,
                'cantidad'        => $cant,
                'precio_unitario' => $precio,
                'subtotal'        => (float)($subtotales[$i] ?? ($cant * $precio)),
            ];
        }
        return $items;
    }
}
?>
