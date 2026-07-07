<?php
// controlador/chatController.php
require_once 'config/config.php';
require_once 'modelo/chatModel.php';

class ChatController {
    private $model;

    public function __construct() {
        $this->model = new ChatModel();
    }

    public function index() {
        $id_yo          = (int)($_SESSION['usuario_id'] ?? 0);
        $conversaciones = $this->model->obtenerConversaciones($id_yo);
        require_once 'vista/chat/index.php';
    }

    public function enviar() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        $body            = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_remitente    = (int)($_SESSION['usuario_id'] ?? 0);
        $id_destinatario = (int)($body['id_destinatario'] ?? 0);
        $mensaje         = trim($body['mensaje'] ?? '');

        if (!$id_remitente || !$id_destinatario || $mensaje === '') {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
        }
        if ($id_remitente === $id_destinatario) {
            echo json_encode(['success' => false, 'message' => 'No puedes enviarte mensajes a ti mismo']); exit;
        }

        $id = $this->model->enviar($id_remitente, $id_destinatario, $mensaje);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }

    public function mensajes($id_con) {
        header('Content-Type: application/json');
        $id_yo  = (int)($_SESSION['usuario_id'] ?? 0);
        $id_con = (int)$id_con;
        $desde  = (int)($_GET['desde'] ?? 0);

        if (!$id_yo || !$id_con) {
            echo json_encode(['success' => false, 'data' => []]); exit;
        }

        $this->model->marcarLeidos($id_con, $id_yo);
        $msgs = $this->model->obtenerMensajes($id_yo, $id_con, $desde);
        echo json_encode(['success' => true, 'data' => $msgs]);
        exit;
    }

    public function conversaciones() {
        header('Content-Type: application/json');
        $id_yo = (int)($_SESSION['usuario_id'] ?? 0);
        if (!$id_yo) { echo json_encode(['success' => false, 'data' => []]); exit; }
        echo json_encode(['success' => true, 'data' => $this->model->obtenerConversaciones($id_yo)]);
        exit;
    }

    public function escribiendo(): void {
        header('Content-Type: application/json');
        $id_yo   = (int)($_SESSION['usuario_id'] ?? 0);
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_dest = (int)($body['id_destinatario'] ?? 0);
        if (!$id_yo || !$id_dest) { echo json_encode(['success' => false]); exit; }
        $this->model->registrarEscribiendo($id_yo, $id_dest);
        echo json_encode(['success' => true]);
        exit;
    }

    public function noLeidos() {
        header('Content-Type: application/json');
        $id    = (int)($_SESSION['usuario_id'] ?? 0);
        $count = $id ? $this->model->contarNoLeidos($id) : 0;
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    // ── Canal soporte: lee/escribe en sup_chat ────────────────────────────────
    public function soporte(string $sub = 'mensajes'): void {
        header('Content-Type: application/json');
        $cid = (int)($_SESSION['comercio_id'] ?? 0);
        if (!$cid) { echo json_encode(['success' => false]); exit; }

        if ($sub === 'mensajes') {
            $desde = (int)($_GET['desde'] ?? 0);
            // Marcar como leídos los mensajes del superadmin
            DB::get()->prepare(
                "UPDATE sup_chat SET leido=1 WHERE comercio_id=? AND emisor='superadmin' AND leido=0"
            )->execute([$cid]);
            $rows = DB::get()->prepare(
                "SELECT id, emisor, mensaje, leido, created_at
                 FROM sup_chat WHERE comercio_id=? AND id>? ORDER BY id ASC LIMIT 100"
            );
            $rows->execute([$cid, $desde]);
            echo json_encode(['success' => true, 'data' => $rows->fetchAll()]);
        } elseif ($sub === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $mensaje = mb_substr(trim($body['mensaje'] ?? ''), 0, 2000);
            if (!$mensaje) { echo json_encode(['success' => false, 'message' => 'Mensaje vacío']); exit; }
            $stmt = DB::get()->prepare(
                "INSERT INTO sup_chat (comercio_id, emisor, mensaje) VALUES (?, 'restaurante', ?)"
            );
            $stmt->execute([$cid, $mensaje]);
            echo json_encode(['success' => true, 'id' => (int)DB::get()->lastInsertId()]);
        } elseif ($sub === 'no-leidos') {
            $count = DB::get()->prepare(
                "SELECT COUNT(*) FROM sup_chat WHERE comercio_id=? AND emisor='superadmin' AND leido=0"
            );
            $count->execute([$cid]);
            echo json_encode(['success' => true, 'count' => (int)$count->fetchColumn()]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    public function stream($id_con) {
        $id_yo  = (int)($_SESSION['usuario_id'] ?? 0);
        $id_con = (int)$id_con;

        // Respetar Last-Event-ID del navegador al reconectar
        $lastEventId = isset($_SERVER['HTTP_LAST_EVENT_ID'])
            ? (int)$_SERVER['HTTP_LAST_EVENT_ID']
            : (int)($_GET['desde'] ?? 0);
        $desde = $lastEventId;

        if (!$id_yo || !$id_con) { http_response_code(403); exit; }

        session_write_close();

        // Deshabilitar todo buffering (Apache mod_deflate + PHP)
        if (function_exists('apache_setenv')) apache_setenv('no-gzip', '1');
        @ini_set('zlib.output_compression', 0);
        @ini_set('output_buffering', 0);
        @ini_set('implicit_flush', 1);
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        header('X-Content-Type-Options: nosniff');

        set_time_limit(0);
        ignore_user_abort(false);

        $tick            = 0;
        $prevEscribiendo = null;
        $prevMaxLeido    = -1;

        while (true) {
            if (connection_aborted()) break;
            $tick++;

            try {
                $this->model->marcarLeidos($id_con, $id_yo);

                $msgs = $this->model->obtenerMensajes($id_yo, $id_con, $desde);
                if (!empty($msgs)) {
                    $lastId = (int)end($msgs)['id'];
                    echo "id: {$lastId}\n";
                    echo "event: mensajes\n";
                    echo "data: " . json_encode($msgs) . "\n\n";
                    $desde = $lastId;
                }

                $escribiendo = $this->model->estaEscribiendo($id_con, $id_yo);
                if ($escribiendo !== $prevEscribiendo) {
                    echo "event: escribiendo\n";
                    echo "data: " . json_encode(['escribiendo' => $escribiendo]) . "\n\n";
                    $prevEscribiendo = $escribiendo;
                }

                $maxLeido = $this->model->obtenerUltimoLeidoPor($id_yo, $id_con);
                if ($maxLeido !== $prevMaxLeido) {
                    echo "event: leidos\n";
                    echo "data: " . json_encode(['max_id' => $maxLeido]) . "\n\n";
                    $prevMaxLeido = $maxLeido;
                }

                if ($tick % 5 === 0) {
                    $convs = $this->model->obtenerConversaciones($id_yo);
                    echo "event: convs\n";
                    echo "data: " . json_encode($convs) . "\n\n";
                }
            } catch (\Throwable $e) {
                // Error de DB: notificar cliente para que reconecte
                echo "event: error\n";
                echo "data: " . json_encode(['msg' => 'db_error']) . "\n\n";
            }

            echo ": ping\n\n";
            flush();

            if (connection_aborted()) break;
            sleep(1);
        }
        exit;
    }
}
?>
