<?php
// modelo/recuperarModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class RecuperarModel extends BaseModel {

    private const EXPIRA_MINUTOS  = 20;
    private const MAX_INTENTOS    = 5;
    private const COOLDOWN_SEGUNDOS = 60;

    private function normalizarTelefono(string $telefono): string {
        $digits = preg_replace('/\D/', '', $telefono);
        if (strlen($digits) === 10) $digits = '57' . $digits;
        return $digits;
    }

    private function buscarPropietario(string $telefono): ?array {
        $ultimos10 = substr($this->normalizarTelefono($telefono), -10);
        return $this->row(
            "SELECT c.id AS comercio_id, c.telefono, u.id AS usuario_id
             FROM comercios c
             INNER JOIN usuarios u ON u.comercio_id = c.id AND u.propietario = 1 AND u.activo = 1
             WHERE RIGHT(REPLACE(REPLACE(REPLACE(c.telefono,' ',''),'-',''),'+',''), 10) = ?
               AND c.activo = 1
             LIMIT 1",
            [$ultimos10]
        );
    }

    public function enviarCodigo(string $telefono): array {
        $prop = $this->buscarPropietario($telefono);
        if (!$prop) {
            return ['ok' => false, 'msg' => 'El número no está registrado a ningún comercio.'];
        }

        $reciente = $this->scalar(
            "SELECT COUNT(*) FROM password_resets
             WHERE comercio_id = ? AND usado = 0 AND created_at > (NOW() - INTERVAL ? SECOND)",
            [$prop['comercio_id'], self::COOLDOWN_SEGUNDOS]
        );
        if ($reciente) {
            return ['ok' => false, 'msg' => 'Ya enviamos un código a este número. Espera un momento antes de solicitar otro.'];
        }

        $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash   = password_hash($codigo, PASSWORD_DEFAULT);

        $this->query(
            "INSERT INTO password_resets (comercio_id, usuario_id, codigo_hash, expira_en)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))",
            [$prop['comercio_id'], $prop['usuario_id'], $hash, self::EXPIRA_MINUTOS]
        );
        $resetId = (int)$this->db->lastInsertId();

        $telefonoDestino = '+' . $this->normalizarTelefono($prop['telefono']);
        $envio = Config::enviarSMS(
            $telefonoDestino,
            "ChefControl: tu codigo de recuperacion es {$codigo}. Vence en " . self::EXPIRA_MINUTOS . " minutos."
        );

        if (!$envio['ok']) {
            // El código ya quedó insertado; lo invalidamos para no dejar huérfanos si el envío falló.
            $this->query("UPDATE password_resets SET usado = 1 WHERE id = ?", [$resetId]);
            return ['ok' => false, 'msg' => 'No pudimos enviar el SMS. Intenta de nuevo más tarde.'];
        }

        $this->registrarLogSms(
            $prop['comercio_id'],
            $telefonoDestino,
            "ChefControl: tu codigo de recuperacion es {$codigo}. Vence en " . self::EXPIRA_MINUTOS . " minutos."
        );

        // Máscara: conserva el indicativo y los últimos 2 dígitos, oculta el resto
        $digitos = $this->normalizarTelefono($prop['telefono']);
        $mascara = '+' . substr($digitos, 0, 2) . str_repeat('*', max(0, strlen($digitos) - 4)) . substr($digitos, -2);

        return [
            'ok'               => true,
            'reset_id'         => $resetId,
            'expira_segundos'  => self::EXPIRA_MINUTOS * 60,
            'telefono_mascara' => $mascara,
        ];
    }

    // Registra el envío en chefcontrol_sup.sms_log para que el panel de Mensajería
    // pueda mostrar a qué número se envió cada SMS (Inalambria no devuelve el destinatario).
    private function registrarLogSms(int $comercioId, string $telefono, string $mensaje): void {
        try {
            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $db   = new PDO(
                "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                Config::DB_USER, Config::DB_PASS, $opts
            );
            $db->exec(
                "CREATE TABLE IF NOT EXISTS sms_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    comercio_id INT NULL,
                    telefono VARCHAR(20) NOT NULL,
                    mensaje TEXT NOT NULL,
                    tipo VARCHAR(40) NOT NULL DEFAULT 'general',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_comercio (comercio_id),
                    INDEX idx_fecha (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $db->prepare(
                "INSERT INTO sms_log (comercio_id, telefono, mensaje, tipo) VALUES (?, ?, ?, 'recuperacion_password')"
            )->execute([$comercioId, $telefono, $mensaje]);
        } catch (\Throwable $e) {}
    }

    // Valida estado + código sin modificar nada más allá de contar intentos fallidos.
    // Devuelve la fila de password_resets si el código es correcto, o un array ['ok'=>false,'msg'=>...] si no.
    private function validarCodigo(int $resetId, string $codigo): array {
        $reset = $this->row("SELECT * FROM password_resets WHERE id = ? LIMIT 1", [$resetId]);
        if (!$reset) {
            return ['ok' => false, 'msg' => 'Solicitud de recuperación no encontrada.'];
        }
        if ((int)$reset['usado'] === 1) {
            return ['ok' => false, 'msg' => 'Este código ya fue utilizado. Solicita uno nuevo.'];
        }
        if (strtotime($reset['expira_en']) < time()) {
            return ['ok' => false, 'msg' => 'El código expiró. Solicita uno nuevo.'];
        }
        if ((int)$reset['intentos'] >= self::MAX_INTENTOS) {
            return ['ok' => false, 'msg' => 'Demasiados intentos fallidos. Solicita un nuevo código.'];
        }
        if (!password_verify($codigo, $reset['codigo_hash'])) {
            $this->query("UPDATE password_resets SET intentos = intentos + 1 WHERE id = ?", [$resetId]);
            return ['ok' => false, 'msg' => 'Código incorrecto.'];
        }
        return ['ok' => true, 'reset' => $reset];
    }

    public function verificarCodigo(int $resetId, string $codigo): array {
        $r = $this->validarCodigo($resetId, $codigo);
        if (!$r['ok']) return $r;
        return ['ok' => true];
    }

    public function verificarYResetear(int $resetId, string $codigo, string $password): array {
        $r = $this->validarCodigo($resetId, $codigo);
        if (!$r['ok']) return $r;
        $reset = $r['reset'];

        $this->query(
            "UPDATE usuarios SET password = ? WHERE id = ? AND comercio_id = ?",
            [password_hash($password, PASSWORD_DEFAULT), $reset['usuario_id'], $reset['comercio_id']]
        );
        $this->query("UPDATE password_resets SET usado = 1 WHERE id = ?", [$resetId]);

        return ['ok' => true, 'msg' => 'Contraseña actualizada correctamente.'];
    }
}
