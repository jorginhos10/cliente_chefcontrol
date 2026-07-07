<?php
// controlador/ventaController.php

require_once 'config/config.php';
require_once 'modelo/ventaModel.php';

class VentaController {
    private $model;

    public function __construct() {
        $this->model = new VentaModel();
    }

    public function index() {
        $recetas     = $this->model->obtenerRecetasConIngredientes();
        $ventas      = $this->model->obtenerVentas(20);
        $estadisticas = $this->model->obtenerEstadisticasHoy();
        require_once 'vista/ventas/index.php';
    }

    public function registrar() {
        // Absorber cualquier warning/notice de PHP para que no corrompa el JSON
        while (ob_get_level() > 0) ob_end_clean();
        ob_start();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!is_array($body)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Solicitud inválida']); exit;
        }

        $items      = $body['items'] ?? [];
        $notas      = trim($body['notas'] ?? '');
        $id_usuario = $_SESSION['usuario_id'] ?? null;

        if (empty($items)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'La orden está vacía']); exit;
        }

        foreach ($items as $item) {
            if (empty($item['id_receta']) || empty($item['cantidad']) || $item['cantidad'] < 1) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Datos de orden inválidos']); exit;
            }
        }

        $resultado = $this->model->registrarVenta($items, $notas, $id_usuario);

        ob_end_clean();
        if ($resultado['ok']) {
            echo json_encode([
                'success'  => true,
                'message'  => 'Venta registrada correctamente',
                'numero'   => $resultado['numero'],
                'id_venta' => $resultado['id_venta'],
                'total'    => $resultado['total'],
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $resultado['msg']]);
        }
        exit;
    }

    public function detalle($id) {
        header('Content-Type: application/json');
        $detalle = $this->model->obtenerDetalleVenta((int)$id);
        echo json_encode(['success' => true, 'data' => $detalle]);
        exit;
    }

    public function salon() {
        $mesas        = $this->model->obtenerMesasConOrdenes();
        $estadisticas = $this->model->obtenerEstadisticasHoy();
        require_once 'vista/ventas/salon.php';
    }

    public function mesa($id_mesa) {
        require_once 'modelo/mesaModel.php';
        require_once 'modelo/comercioModel.php';
        $mesaModel = new MesaModel();
        $mesa = $mesaModel->obtenerMesaPorId((int)$id_mesa);
        if (!$mesa) {
            header('Location: ' . Config::getBasePath() . '/ventas/salon');
            exit;
        }
        $comercio = (new ComercioModel())->obtener();
        $ordenes  = $this->model->obtenerOrdenesActivasMesa((int)$id_mesa);
        $recetas  = $this->model->obtenerRecetasConIngredientes();
        require_once 'vista/ventas/mesa.php';
    }

    public function guardarOrden() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body       = json_decode(file_get_contents('php://input'), true);
        $id_mesa    = (int)($body['id_mesa'] ?? 0);
        $items      = $body['items'] ?? [];
        $notas      = trim($body['notas'] ?? '');
        $id_usuario = $_SESSION['usuario_id'] ?? null;

        if (!$id_mesa) {
            echo json_encode(['success' => false, 'message' => 'Mesa no especificada']); exit;
        }
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'La orden está vacía']); exit;
        }

        $r = $this->model->guardarOrdenMesa($id_mesa, $items, $notas, $id_usuario);
        echo json_encode($r['ok']
            ? ['success' => true,  'message' => 'Orden guardada', 'numero' => $r['numero'], 'id_venta' => $r['id_venta'], 'total' => $r['total']]
            : ['success' => false, 'message' => $r['msg']]
        );
        exit;
    }

    public function cobrar() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body         = json_decode(file_get_contents('php://input'), true);
        $id_mesa      = (int)($body['id_mesa'] ?? 0);
        $cuponCodigo  = strtoupper(trim($body['cupon_codigo'] ?? ''));
        $propinaAmt   = max(0.0, (float)($body['propina'] ?? 0));
        $id_usuario   = $_SESSION['usuario_id'] ?? null;

        if (!$id_mesa) {
            echo json_encode(['success' => false, 'message' => 'Mesa no especificada']); exit;
        }

        // Validar cupón antes de cobrar
        $cuponId   = null;
        $cuponData = null;
        $descuentoProducto = 0.0;
        if ($cuponCodigo) {
            require_once 'modelo/cuponModel.php';
            $cuponModel = new CuponModel();
            $cuponData  = $cuponModel->validar($cuponCodigo);
            if ($cuponData['ok']) {
                $cuponId = $cuponData['id'];
                if ($cuponData['tipo'] === 'producto' && !empty($cuponData['id_receta'])) {
                    // Pre-calculate product-specific discount from active orders
                    $ordenes = $this->model->obtenerOrdenesActivasMesa((int)$id_mesa);
                    $subtotalProducto = 0.0;
                    foreach ($ordenes as $orden) {
                        foreach ($orden['items'] ?? [] as $item) {
                            if ((int)$item['id_receta'] === (int)$cuponData['id_receta']) {
                                $subtotalProducto += (float)$item['precio_unit'] * (int)$item['cantidad'];
                            }
                        }
                    }
                    $descuentoProducto = $subtotalProducto * (float)$cuponData['descuento'] / 100;
                }
            }
        }

        $r = $this->model->cobrarTodasOrdenesMesa($id_mesa, $id_usuario);

        if ($r['ok'] && $cuponId && $cuponData) {
            $total = (float)$r['total'];
            if ($cuponData['tipo'] === 'porcentaje') {
                $descuento = $total * (float)$cuponData['descuento'] / 100;
            } elseif ($cuponData['tipo'] === 'producto') {
                $descuento = min($descuentoProducto, $total);
            } else {
                $descuento = min((float)$cuponData['descuento'], $total);
            }
            $r['total'] = max(0.0, $total - $descuento);
            $cuponModel->registrarUso($cuponId, $descuento);
        }

        // Guardar propina si la hay
        if ($r['ok'] && $propinaAmt > 0) {
            require_once 'modelo/propinaModel.php';
            require_once 'modelo/mesaModel.php';
            $mesaModel = new MesaModel();
            $mesaInfo  = $mesaModel->obtenerMesaPorId($id_mesa);
            (new PropinaModel())->registrar(
                $propinaAmt,
                $id_mesa,
                (int)($mesaInfo['numero'] ?? 0),
                $mesaInfo['nombre'] ?? null,
                $r['numero'] ?? null,
                $id_usuario
            );
        }

        echo json_encode($r['ok']
            ? ['success' => true, 'message' => 'Cobrado correctamente', 'numero' => $r['numero'], 'total' => $r['total'], 'ordenes' => $r['ordenes'], 'ids' => $r['ids'] ?? []]
            : ['success' => false, 'message' => $r['msg']]
        );
        exit;
    }

    public function cancelarOrden() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body    = json_decode(file_get_contents('php://input'), true);
        $id_mesa = (int)($body['id_mesa'] ?? 0);

        if (!$id_mesa) {
            echo json_encode(['success' => false, 'message' => 'Mesa no especificada']); exit;
        }

        $r = $this->model->cancelarOrdenesActivas($id_mesa);
        echo json_encode($r['ok']
            ? ['success' => true,  'message' => 'Órdenes canceladas']
            : ['success' => false, 'message' => $r['msg']]
        );
        exit;
    }

    public function getOrdenMesa($id_mesa) {
        header('Content-Type: application/json');
        $ordenes = $this->model->obtenerOrdenesActivasMesa((int)$id_mesa);
        echo json_encode(['success' => true, 'data' => $ordenes]);
        exit;
    }

    public function listado() {
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 25;
        $hoy     = date('Y-m-d');
        $filtros = [
            'buscar' => trim($_GET['buscar'] ?? ''),
            'estado' => trim($_GET['estado'] ?? ''),
            'desde'  => trim($_GET['desde']  ?? $hoy),
            'hasta'  => trim($_GET['hasta']  ?? $hoy),
        ];

        $ventas    = $this->model->obtenerVentasPaginadas($pagina, $porPagina, $filtros);
        $totales   = $this->model->contarVentas($filtros);
        $totalPags = max(1, (int)ceil($totales['total'] / $porPagina));

        require_once 'modelo/comercioModel.php';
        $comercio = (new ComercioModel())->obtener();

        require_once 'vista/ventas/listado.php';
    }

    public function asignarCliente(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $venta_id   = (int)($body['venta_id']   ?? 0);
        $cliente_id = (int)($body['cliente_id'] ?? 0);
        if (!$venta_id || !$cliente_id) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
        }
        echo json_encode(['success' => $this->model->asignarCliente($venta_id, $cliente_id)]);
        exit;
    }

    public function cancelarVentaRegistrada(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_venta = (int)($body['venta_id'] ?? 0);
        if (!$id_venta) {
            echo json_encode(['success' => false, 'message' => 'ID de venta requerido']); exit;
        }
        $r = $this->model->cancelarVentaRegistrada($id_venta);
        echo json_encode($r['ok']
            ? ['success' => true]
            : ['success' => false, 'message' => $r['msg'] ?? 'Error']
        );
        exit;
    }

    public function salonStream() {
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
            $mesas = $this->model->obtenerMesasConOrdenes();
            $hash  = md5(json_encode($mesas));

            if ($hash !== $ultimoHash) {
                echo "event: mesas\n";
                echo "data: " . json_encode($mesas) . "\n\n";
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
