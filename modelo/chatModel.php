<?php
// modelo/chatModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class ChatModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function registrarEscribiendo(int $id_usuario, int $id_destinatario): void {
        $this->requireCid();
        $this->db->prepare(
            "INSERT INTO chat_typing (comercio_id, id_usuario, id_destinatario, last_typed)
             VALUES ({$this->cid}, :u, :d, NOW())
             ON DUPLICATE KEY UPDATE id_destinatario = :d2, last_typed = NOW()"
        )->execute([':u' => $id_usuario, ':d' => $id_destinatario, ':d2' => $id_destinatario]);
    }

    public function estaEscribiendo(int $id_usuario, int $id_destinatario): bool {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM chat_typing
             WHERE id_usuario = :u AND id_destinatario = :d AND comercio_id = {$this->cid}
               AND last_typed > NOW() - INTERVAL 4 SECOND"
        );
        $stmt->execute([':u' => $id_usuario, ':d' => $id_destinatario]);
        return (bool)$stmt->fetchColumn();
    }

    public function obtenerUltimoLeidoPor(int $remitente, int $destinatario): int {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(id), 0) FROM mensajes_chat
             WHERE id_remitente = :r AND id_destinatario = :d AND leido = 1 AND comercio_id = {$this->cid}"
        );
        $stmt->execute([':r' => $remitente, ':d' => $destinatario]);
        return (int)$stmt->fetchColumn();
    }

    public function enviar($id_remitente, $id_destinatario, $mensaje) {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "INSERT INTO mensajes_chat (comercio_id, id_remitente, id_destinatario, mensaje)
             VALUES ({$this->cid}, :r, :d, :m)"
        );
        $stmt->execute([':r' => (int)$id_remitente, ':d' => (int)$id_destinatario, ':m' => trim($mensaje)]);
        return (int)$this->db->lastInsertId();
    }

    public function obtenerMensajes($id1, $id2, $desde_id = 0) {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT id, id_remitente, id_destinatario, mensaje, leido, fecha_creacion
             FROM mensajes_chat
             WHERE comercio_id = {$this->cid}
               AND ((id_remitente = :r1 AND id_destinatario = :d1)
                OR  (id_remitente = :r2 AND id_destinatario = :d2))
               AND id > :desde
             ORDER BY fecha_creacion ASC
             LIMIT 100"
        );
        $stmt->execute([
            ':r1' => (int)$id1, ':d1' => (int)$id2,
            ':r2' => (int)$id2, ':d2' => (int)$id1,
            ':desde' => (int)$desde_id,
        ]);
        return $stmt->fetchAll();
    }

    public function marcarLeidos($id_remitente, $id_destinatario) {
        $this->requireCid();
        $this->db->prepare(
            "UPDATE mensajes_chat SET leido = 1
             WHERE id_remitente = :r AND id_destinatario = :d AND leido = 0 AND comercio_id = {$this->cid}"
        )->execute([':r' => (int)$id_remitente, ':d' => (int)$id_destinatario]);
    }

    public function obtenerConversaciones($id_usuario) {
        $this->requireCid();
        $stmt = $this->db->prepare("
            SELECT u.id, u.nombre, u.avatar, u.rol,
                   lm.mensaje          AS ultimo_mensaje,
                   lm.fecha_creacion   AS ultima_fecha,
                   lm.id_remitente     AS ultimo_remitente,
                   COALESCE(nr.no_leidos, 0) AS no_leidos
            FROM usuarios u
            LEFT JOIN mensajes_chat lm ON lm.id = (
                SELECT mc2.id FROM mensajes_chat mc2
                WHERE mc2.comercio_id = {$this->cid}
                  AND ((mc2.id_remitente = :uid1 AND mc2.id_destinatario = u.id)
                   OR  (mc2.id_destinatario = :uid2 AND mc2.id_remitente = u.id))
                ORDER BY mc2.fecha_creacion DESC
                LIMIT 1
            )
            LEFT JOIN (
                SELECT id_remitente, COUNT(*) AS no_leidos
                FROM mensajes_chat
                WHERE id_destinatario = :uid3 AND leido = 0 AND comercio_id = {$this->cid}
                GROUP BY id_remitente
            ) nr ON nr.id_remitente = u.id
            WHERE u.id != :uid4 AND u.activo = 1 AND u.comercio_id = {$this->cid}
            ORDER BY (lm.fecha_creacion IS NULL), lm.fecha_creacion DESC, u.nombre ASC
        ");
        $stmt->execute([
            ':uid1' => (int)$id_usuario, ':uid2' => (int)$id_usuario,
            ':uid3' => (int)$id_usuario, ':uid4' => (int)$id_usuario,
        ]);
        return $stmt->fetchAll();
    }

    public function contarNoLeidos($id_destinatario) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM mensajes_chat
                 WHERE id_destinatario = :d AND leido = 0 AND comercio_id = {$this->cid}"
            );
            $stmt->execute([':d' => (int)$id_destinatario]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
?>
