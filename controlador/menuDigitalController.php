<?php
// controlador/menuDigitalController.php

require_once 'config/config.php';
require_once 'modelo/menuDigitalModel.php';

class MenuDigitalController {
    private $model;

    public function __construct() {
        $this->model = new MenuDigitalModel();
    }

    // ── Panel admin: listado ──────────────────────────────────────────────
    public function index() {
        $menus = $this->model->obtenerTodos();
        $mesas = $this->model->obtenerMesas();
        require 'vista/menu-digital/index.php';
    }

    // ── Panel admin: constructor de items ─────────────────────────────────
    public function construir($id) {
        $id = (int)$id;
        $menuActual = $this->model->obtenerPorId($id);
        if (!$menuActual) {
            header('Location: ' . Config::getBasePath() . '/menu-digital');
            exit;
        }
        $recetasDisponibles = $this->model->obtenerRecetasActivas();
        $itemsGuardados      = $this->model->obtenerItems($id);
        require 'vista/menu-digital/construir.php';
    }

    public function guardar() {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id          = (int)($body['id'] ?? 0);
        $nombre      = trim($body['nombre']      ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $activo      = !empty($body['activo']) ? 1 : 0;

        if ($nombre === '') {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
            exit;
        }

        if ($id > 0) {
            $ok = $this->model->actualizar($id, $nombre, $descripcion, $activo);
            echo json_encode(['success' => $ok]);
        } else {
            $r = $this->model->crear($nombre, $descripcion, $activo);
            echo json_encode(['success' => true, 'id' => $r['id']]);
        }
        exit;
    }

    public function eliminar() {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        echo json_encode(['success' => $id > 0 && $this->model->eliminar($id)]);
        exit;
    }

    public function configurar() {
        header('Content-Type: application/json');
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $id     = (int)($body['id'] ?? 0);
        $nombre = trim($body['nombre'] ?? '');
        $mesaId = (isset($body['mesa_id']) && $body['mesa_id'] !== '') ? (int)$body['mesa_id'] : null;

        if ($id <= 0 || $nombre === '') {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }
        echo json_encode(['success' => $this->model->actualizarNombreYMesa($id, $nombre, $mesaId)]);
        exit;
    }

    public function duplicar() {
        header('Content-Type: application/json');
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int)($body['id'] ?? 0);
        $nuevoId = $id > 0 ? $this->model->duplicar($id) : null;
        echo json_encode(['success' => $nuevoId !== null, 'id' => $nuevoId]);
        exit;
    }

    public function toggle() {
        header('Content-Type: application/json');
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        echo json_encode(['success' => $id > 0 && $this->model->toggleActivo($id)]);
        exit;
    }

    public function guardarItems($id) {
        header('Content-Type: application/json');
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $items = array_map('intval', $body['items'] ?? []);
        $this->model->guardarItems((int)$id, $items);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── Público: vista del menú (QR) ──────────────────────────────────────
    public function verPublico(string $token) {
        $menuPublico = $this->model->obtenerPorToken($token);
        if (!$menuPublico || !$menuPublico['activo']) {
            http_response_code(404);
            die('Este menú no está disponible.');
        }
        $itemsMenu    = $this->model->obtenerItemsConDetalle((int)$menuPublico['id']);
        require_once 'modelo/comercioModel.php';
        $comercioMenu = (new ComercioModel())->obtener() ?: [];
        require 'vista/menu/publica.php';
    }

    // ── Público: confirmar pedido ──────────────────────────────────────────
    public function pedir(string $token) {
        header('Content-Type: application/json');
        $menu = $this->model->obtenerPorToken($token);
        if (!$menu || !$menu['activo']) {
            echo json_encode(['ok' => false, 'msg' => 'Menú no disponible']); exit;
        }
        if (empty($menu['mesa_id'])) {
            echo json_encode(['ok' => false, 'msg' => 'Este menú es solo de consulta']); exit;
        }

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $itemsIn = $body['items'] ?? [];
        $notas   = trim($body['notas'] ?? '');
        if (empty($itemsIn)) {
            echo json_encode(['ok' => false, 'msg' => 'El pedido está vacío']); exit;
        }

        $ids     = array_map(fn($it) => (int)($it['id_receta'] ?? 0), $itemsIn);
        $precios = $this->model->obtenerPreciosValidos((int)$menu['id'], $ids);

        $items = [];
        foreach ($itemsIn as $it) {
            $rid  = (int)($it['id_receta'] ?? 0);
            $cant = max(1, (int)($it['cantidad'] ?? 0));
            if (!isset($precios[$rid])) continue; // no pertenece al menú o ya no está activo
            $items[] = ['id_receta' => $rid, 'cantidad' => $cant, 'precio_unitario' => $precios[$rid]];
        }
        if (empty($items)) {
            echo json_encode(['ok' => false, 'msg' => 'Los productos ya no están disponibles']); exit;
        }

        require_once 'modelo/ventaModel.php';
        $ventaModel = new VentaModel();
        $res = $ventaModel->guardarOrdenMesa((int)$menu['mesa_id'], $items, $notas, null);
        echo json_encode($res);
        exit;
    }

    // ── Público: total acumulado de la mesa ────────────────────────────────
    public function mesaTotal(string $token) {
        header('Content-Type: application/json');
        $menu = $this->model->obtenerPorToken($token);
        if (!$menu || empty($menu['mesa_id'])) {
            echo json_encode(['ok' => false]); exit;
        }
        require_once 'modelo/ventaModel.php';
        $resumen = (new VentaModel())->obtenerResumenMesa((int)$menu['mesa_id']);
        echo json_encode(['ok' => true] + $resumen);
        exit;
    }

    // ── Público: estado de una orden (polling) ─────────────────────────────
    public function estado(string $token, $ventaId = null) {
        header('Content-Type: application/json');
        $menu = $this->model->obtenerPorToken($token);
        if (!$menu || !$ventaId) {
            echo json_encode(['ok' => false]); exit;
        }
        require_once 'modelo/ventaModel.php';
        $orden = (new VentaModel())->obtenerEstadoOrden((int)$ventaId);
        if (!$orden) {
            echo json_encode(['ok' => false]); exit;
        }
        echo json_encode(['ok' => true, 'estado' => $orden['estado']]);
        exit;
    }

    // ── Público: factura combinada de la mesa ──────────────────────────────
    public function facturaMesa(string $token) {
        $menu = $this->model->obtenerPorToken($token);
        if (!$menu || empty($menu['mesa_id'])) {
            http_response_code(404);
            die('Sin cuenta activa para esta mesa.');
        }
        require_once 'modelo/ventaModel.php';
        $ordenMesa = (new VentaModel())->obtenerOrdenCompletaMesa((int)$menu['mesa_id']);
        require_once 'modelo/comercioModel.php';
        $comercioFac = (new ComercioModel())->obtener() ?: [];
        require 'vista/menu/factura_mesa.php';
    }
}
?>
