<?php
// controlador/cocinaController.php

require_once 'config/config.php';
require_once 'modelo/ventaModel.php';
require_once 'modelo/domicilioModel.php';

class CocinaController {
    private VentaModel     $model;
    private DomicilioModel $domModel;

    public function __construct() {
        $this->model    = new VentaModel();
        $this->domModel = new DomicilioModel();
    }

    private function todasLasOrdenes(): array {
        $ordenes = array_merge(
            $this->model->obtenerOrdenesActivas(),
            $this->domModel->obtenerOrdenesParaCocina()
        );
        usort($ordenes, fn($a, $b) => strcmp($a['fecha_creacion'] ?? '', $b['fecha_creacion'] ?? ''));
        return $ordenes;
    }

    public function index(): void {
        $ordenes = $this->todasLasOrdenes();
        require_once 'vista/cocina/index.php';
    }

    public function getOrdenes(): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->todasLasOrdenes(), 'ts' => time()]);
        exit;
    }

    public function aceptar(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($body['id_venta'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); exit; }

        // ID negativo = pedido de domicilio
        if ($id < 0) {
            $ok = $this->domModel->cambiarEstado(-$id, 'preparacion');
            echo json_encode(['success' => $ok]); exit;
        }

        $r = $this->model->aceptarOrden($id);
        echo json_encode($r['ok'] ? ['success' => true] : ['success' => false, 'message' => $r['msg']]);
        exit;
    }

    public function marcarLista(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($body['id_venta'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); exit; }

        // ID negativo = pedido de domicilio → estado listo (esperando domiciliario)
        if ($id < 0) {
            $ok = $this->domModel->cambiarEstado(-$id, 'listo');
            echo json_encode(['success' => $ok]); exit;
        }

        $r = $this->model->marcarListaOrden($id);
        echo json_encode($r['ok'] ? ['success' => true] : ['success' => false, 'message' => $r['msg']]);
        exit;
    }

    public function cancelar(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($body['id_venta'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requerido']); exit; }

        if ($id < 0) {
            $ok = $this->domModel->cambiarEstado(-$id, 'cancelado');
            echo json_encode(['success' => $ok]); exit;
        }

        $r = $this->model->cancelarOrden($id);
        echo json_encode($r['ok'] ? ['success' => true] : ['success' => false, 'message' => $r['msg']]);
        exit;
    }

    public function stream(): void {
        set_time_limit(0);
        ignore_user_abort(false);
        session_write_close();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        while (ob_get_level() > 0) ob_end_flush();

        $ultimoHash = null;
        $tick       = 0;

        while (!connection_aborted()) {
            $ordenes = $this->todasLasOrdenes();
            $hash    = md5(json_encode($ordenes));

            if ($hash !== $ultimoHash) {
                echo "event: ordenes\n";
                echo "data: " . json_encode($ordenes) . "\n\n";
                flush();
                $ultimoHash = $hash;
            }

            $tick++;
            if ($tick % 5 === 0) {
                echo ": ping\n\n";
                flush();
            }

            sleep(5);
        }
    }
}
?>
