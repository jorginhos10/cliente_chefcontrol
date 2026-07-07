<?php
// controlador/notificacionController.php
require_once 'config/config.php';
require_once 'modelo/domicilioModel.php';
require_once 'modelo/chatModel.php';
require_once 'modelo/pqrsModel.php';

class NotificacionController {
    private DomicilioModel $domModel;
    private ChatModel      $chatModel;
    private PqrsModel      $pqrsModel;

    public function __construct() {
        $this->domModel  = new DomicilioModel();
        $this->chatModel = new ChatModel();
        $this->pqrsModel = new PqrsModel();
    }

    public function resumen(): void {
        header('Content-Type: application/json');
        $id_yo  = (int)($_SESSION['usuario_id'] ?? 0);
        $notifs = [];
        $total  = 0;      // badge count = unread only
        $chatUnread = 0;  // for sidebar chat badge

        // ── 1. Pedidos domicilio pendientes de aprobación ─────────────────────
        foreach ($this->domModel->obtenerPedidosPendientes() as $p) {
            $icon = $p['tipo'] === 'recoger' ? 'fa-store' : 'fa-motorcycle';
            $notifs[] = [
                'tipo'   => 'pedido_nuevo',
                'icon'   => $icon,
                'color'  => '#e67e22',
                'titulo' => 'Nuevo pedido — ' . $p['nombre_cliente'],
                'texto'  => ($p['tipo'] === 'recoger' ? 'Recoger en local' : 'A domicilio') . ' · DOM-' . $p['id'],
                'tiempo' => $p['created_at'],
                'url'    => '/domicilios',
                'leido'  => false,
            ];
            $total++;
        }

        // ── 2. Historial de cambios de estado (últimas 24 h, leídos y no leídos)
        $estadoLabels = [
            'preparacion' => 'En preparación',
            'listo'       => 'Listo / Esp. domiciliario',
            'en_camino'   => 'En camino',
            'entregado'   => 'Entregado',
            'cancelado'   => 'Cancelado',
        ];
        $estadoColors = [
            'preparacion' => '#2980b9',
            'listo'       => '#8e44ad',
            'en_camino'   => '#27ae60',
            'entregado'   => '#27ae60',
            'cancelado'   => '#e74c3c',
        ];
        foreach ($this->domModel->obtenerHistorialReciente() as $h) {
            $label   = $estadoLabels[$h['estado_a']] ?? ucfirst($h['estado_a']);
            $color   = $estadoColors[$h['estado_a']] ?? '#636e72';
            $esLeido = (bool)(int)$h['leido'];
            $notifs[] = [
                'tipo'   => 'estado_cambio',
                'icon'   => 'fa-arrows-rotate',
                'color'  => $color,
                'titulo' => 'DOM-' . $h['pedido_id'] . ' — ' . $h['nombre_cliente'],
                'texto'  => 'Cambió a: ' . $label,
                'tiempo' => $h['created_at'],
                'url'    => '/domicilios',
                'leido'  => $esLeido,
            ];
            if (!$esLeido) $total++;
        }

        // ── 3. Mensajes de clientes domicilio sin leer ────────────────────────
        foreach ($this->domModel->obtenerResumenesChat() as $c) {
            if ((int)$c['no_leidos'] > 0) {
                $nl = (int)$c['no_leidos'];
                $notifs[] = [
                    'tipo'   => 'dom_chat',
                    'icon'   => 'fa-comment-dots',
                    'color'  => '#2980b9',
                    'titulo' => $c['nombre_cliente'] . ' te escribió',
                    'texto'  => mb_substr($c['ultimo_mensaje'] ?? '', 0, 60),
                    'tiempo' => $c['ultima_fecha'],
                    'url'    => '/chat?dom=' . urlencode($c['token_pedido']),
                    'leido'  => false,
                ];
                $total      += $nl;
                $chatUnread += $nl;
            }
        }

        // ── 4. Chat interno por conversación (personal) ───────────────────────
        if ($id_yo) {
            foreach ($this->chatModel->obtenerConversaciones($id_yo) as $conv) {
                $nl = (int)($conv['no_leidos'] ?? 0);
                if ($nl > 0) {
                    $notifs[] = [
                        'tipo'   => 'int_chat',
                        'icon'   => 'fa-comments',
                        'color'  => '#27ae60',
                        'titulo' => ($conv['nombre'] ?? 'Usuario') . ' te escribió',
                        'texto'  => mb_substr($conv['ultimo_mensaje'] ?? '', 0, 60),
                        'tiempo' => $conv['ultima_fecha'] ?? null,
                        'url'    => '/chat?con=' . $conv['id'],
                        'leido'  => false,
                    ];
                    $total      += $nl;
                    $chatUnread += $nl;
                }
            }
        }

        echo json_encode([
            'success'           => true,
            'total'             => min($total, 99),
            'data'              => $notifs,
            'sidebar_cocina'    => $this->domModel->contarOrdenesActivasCocina(),
            'sidebar_chat'      => min($chatUnread, 99),
            'sidebar_pqrs'      => $this->pqrsModel->contarPendientes(),
            'sidebar_domicilios'=> $this->domModel->contarPedidosActivos(),
        ]);
        exit;
    }

    public function marcarLeidas(): void {
        header('Content-Type: application/json');
        $this->domModel->marcarHistorialLeido();
        echo json_encode(['success' => true]);
        exit;
    }
}
?>
