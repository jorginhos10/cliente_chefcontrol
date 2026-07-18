<?php
// controlador/domicilioController.php
require_once 'config/config.php';
require_once 'modelo/domicilioModel.php';

class DomicilioController {
    private DomicilioModel $model;

    public function __construct() {
        $this->model = new DomicilioModel();
    }

    // ── Admin (requiere sesión) ───────────────────────────────────────────────

    public function index(): void {
        $this->model->marcarHistorialLeido();
        $pedidos = $this->model->obtenerPedidosAdmin();
        require_once 'modelo/comercioModel.php';
        $comercio = (new ComercioModel())->obtener() ?: [];
        $papel    = ComercioModel::parametrosPapel($comercio['tamano_papel'] ?? '80mm');
        require_once 'vista/domicilios/index.php';
    }

    public function crear(): void {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre = trim($body['nombre'] ?? '');
        $desc   = trim($body['descripcion'] ?? '');

        if (!$nombre) {
            echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
            exit;
        }

        $id    = $this->model->crearLink($nombre, $desc);
        $links = $this->model->obtenerLinks();
        $link  = current(array_filter($links, fn($l) => $l['id'] == $id));
        echo json_encode(['success' => true, 'link' => $link]);
        exit;
    }

    public function config(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); exit; }
        unset($body['id']);
        $this->model->guardarConfigLink($id, $body);
        echo json_encode(['success' => true]);
        exit;
    }

    public function editar(): void {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $id     = (int)($body['id'] ?? 0);
        $nombre = trim($body['nombre'] ?? '');
        $desc   = trim($body['descripcion'] ?? '');
        if (!$id || !$nombre) { echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit; }
        $this->model->editarLink($id, $nombre, $desc);
        echo json_encode(['success' => true]);
        exit;
    }

    public function toggle(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); exit; }
        $this->model->toggleLink($id);
        echo json_encode(['success' => true]);
        exit;
    }

    public function eliminar(): void {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false]); exit; }
        $this->model->eliminarLink($id);
        echo json_encode(['success' => true]);
        exit;
    }

    public function cambiarEstado(): void {
        header('Content-Type: application/json');
        $body            = json_decode(file_get_contents('php://input'), true) ?? [];
        $pedido_id       = (int)($body['pedido_id'] ?? 0);
        $estado          = trim($body['estado'] ?? '');
        $valorDomicilio  = isset($body['valor_domicilio']) ? (float)$body['valor_domicilio'] : null;
        echo json_encode(['success' => $this->model->cambiarEstado($pedido_id, $estado, $valorDomicilio)]);
        exit;
    }

    public function pedidosJson(): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->model->obtenerPedidosAdmin()]);
        exit;
    }

    // ── Público (sin sesión) ─────────────────────────────────────────────────

    public function pedidoPublico(string $token): void {
        $link = $this->model->obtenerLinkPorToken($token);
        if (!$link) {
            http_response_code(404);
            die('Link no encontrado o inactivo.');
        }

        // Horario de disponibilidad
        $linkCerrado   = false;
        $horarioDesde  = $link['horario_desde'] ?? null;
        $horarioHasta  = $link['horario_hasta'] ?? null;
        if ($horarioDesde && $horarioHasta) {
            $ahora = date('H:i');
            $linkCerrado = !($ahora >= $horarioDesde && $ahora <= $horarioHasta);
        }

        // Categorías activas para este link
        $catActivas = null;
        if (!empty($link['categorias_activas'])) {
            $catActivas = json_decode($link['categorias_activas'], true) ?: null;
        }

        $mostrarSinStock = (bool)($link['mostrar_sin_stock'] ?? false);
        $recetas = $this->model->obtenerRecetas($mostrarSinStock, $catActivas);

        require_once 'modelo/comercioModel.php';
        $comercio = (new ComercioModel())->obtener() ?: [];

        require_once 'vista/domicilios/pedido.php';
    }

    public function hacerPedido(string $token): void {
        header('Content-Type: application/json');
        $link = $this->model->obtenerLinkPorToken($token);
        if (!$link) {
            echo json_encode(['success' => false, 'message' => 'Link inválido']);
            exit;
        }

        $body        = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre      = trim($body['nombre']       ?? '');
        $telefono    = trim($body['telefono']     ?? '');
        $direccion   = trim($body['direccion']    ?? '');
        $notas       = trim($body['notas']        ?? '');
        $tipo        = trim($body['tipo']         ?? 'domicilio');
        $items       = $body['items']             ?? [];
        $cuponCodigo = strtoupper(trim($body['cupon_codigo'] ?? ''));

        if (!$nombre || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Completa tu nombre y agrega al menos un plato']);
            exit;
        }
        if ($tipo === 'domicilio' && !$direccion) {
            echo json_encode(['success' => false, 'message' => 'La dirección es obligatoria para domicilio']);
            exit;
        }

        // Recalcular total desde los items (no confiar en el valor enviado por el cliente)
        $totalCalc = 0.0;
        foreach ($items as $item) {
            $totalCalc += (float)($item['precio'] ?? 0) * max(1, (int)($item['cantidad'] ?? 1));
        }

        $descuento = 0.0;
        $cuponId   = null;
        if ($cuponCodigo) {
            require_once 'modelo/cuponModel.php';
            $cuponModel = new CuponModel();
            $cupon = $cuponModel->validar($cuponCodigo);
            if ($cupon['ok']) {
                $cuponId = $cupon['id'];
                if ($cupon['tipo'] === 'porcentaje') {
                    $descuento = $totalCalc * $cupon['descuento'] / 100;
                } elseif ($cupon['tipo'] === 'producto' && !empty($cupon['id_receta'])) {
                    $subtotalProducto = 0.0;
                    foreach ($items as $item) {
                        if ((int)($item['receta_id'] ?? 0) === (int)$cupon['id_receta']) {
                            $subtotalProducto += (float)($item['precio'] ?? 0) * max(1, (int)($item['cantidad'] ?? 1));
                        }
                    }
                    $descuento = min($subtotalProducto * $cupon['descuento'] / 100, $totalCalc);
                } else {
                    $descuento = min((float)$cupon['descuento'], $totalCalc);
                }
            }
        }
        $total = max(0.0, $totalCalc - $descuento);

        // Validar stock disponible antes de crear el pedido
        $insuficientes = $this->model->verificarStock($items);
        if (!empty($insuficientes)) {
            $nombres = implode(', ', array_map(
                fn($i) => $i['nombre'] . ' (quedan ' . $i['disponibles'] . ')',
                $insuficientes
            ));
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente: ' . $nombres]);
            exit;
        }

        $token_pedido = $this->model->crearPedido(
            $link['id'], $nombre, $telefono, $direccion, $notas, $tipo, $items, $total
        );

        if ($cuponId) {
            $cuponModel->registrarUso($cuponId, $descuento);
        }

        echo json_encode(['success' => true, 'token_pedido' => $token_pedido]);
        exit;
    }

    public function estadoPedido(string $token_pedido): void {
        header('Content-Type: application/json');
        $pedido = $this->model->obtenerPedidoPorToken($token_pedido);
        if (!$pedido) { echo json_encode(['success' => false]); exit; }
        $items  = $this->model->obtenerItemsPedido($pedido['id']);
        $chatNl = $this->model->contarNoLeidosChat($token_pedido, 'cliente');
        echo json_encode(['success' => true, 'pedido' => $pedido, 'items' => $items, 'chat_no_leidos' => $chatNl]);
        exit;
    }

    // ── Chat público (sin sesión) ────────────────────────────────────────────

    public function chatClienteMensajes(string $token_pedido): void {
        header('Content-Type: application/json');
        if (!$this->model->obtenerPedidoPorToken($token_pedido)) {
            echo json_encode(['success' => false]); exit;
        }
        $desde = (int)($_GET['desde'] ?? 0);
        $this->model->marcarLeidosChat($token_pedido, 'cliente');
        echo json_encode(['success' => true, 'data' => $this->model->obtenerMensajesChat($token_pedido, $desde)]);
        exit;
    }

    public function chatClienteEnviar(string $token_pedido): void {
        header('Content-Type: application/json');
        if (!$this->model->obtenerPedidoPorToken($token_pedido)) {
            echo json_encode(['success' => false]); exit;
        }
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $mensaje = trim($body['mensaje'] ?? '');
        if (!$mensaje) { echo json_encode(['success' => false]); exit; }
        $id = $this->model->enviarMensajeChat($token_pedido, 'cliente', $mensaje);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    // ── Chat admin (requiere sesión) ─────────────────────────────────────────

    public function chatMensajes(string $token_pedido): void {
        header('Content-Type: application/json');
        $desde = (int)($_GET['desde'] ?? 0);
        $this->model->marcarLeidosChat($token_pedido, 'admin');
        echo json_encode(['success' => true, 'data' => $this->model->obtenerMensajesChat($token_pedido, $desde)]);
        exit;
    }

    public function chatListar(): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->model->obtenerResumenesChat()]);
        exit;
    }

    public function chatEnviar(): void {
        header('Content-Type: application/json');
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $token   = trim($body['token_pedido'] ?? '');
        $mensaje = trim($body['mensaje'] ?? '');
        if (!$token || !$mensaje) { echo json_encode(['success' => false]); exit; }
        $id = $this->model->enviarMensajeChat($token, 'admin', $mensaje);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }
}
?>
