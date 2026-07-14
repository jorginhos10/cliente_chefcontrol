<?php
// config/config.php

class Config {
    const DB_HOST     = 'localhost';
    const DB_NAME     = 'jorginho_app-chefcontrol';
    const DB_NAME_SUP = 'jorginho_su-chefcontrol'; // DB del panel superadmin (planes, etc.)
    const DB_USER     = 'jorginho_app-chefcontrol';
    const DB_PASS     = 'jorginho10.';
    const DB_CHARSET  = 'utf8mb4';
    const SESSION_TIMEOUT = 1800;
    // Clave AES-256 para cifrar datos sensibles
    const ENCRYPT_KEY = 'chefcontrol_enc_k3y_s3cr3t_2024!';

    // ── ePayco ────────────────────────────────────────────────────────────────
    const EPAYCO_CUST_ID     = '1583910';
    const EPAYCO_P_KEY       = '293c30cf67f01c36f0e1fc7a70301102e8e6653c';
    const EPAYCO_PUBLIC_KEY  = 'd538aa5b2bdf556accec8ad6096d5c3b';
    const EPAYCO_PRIVATE_KEY = '45392070014b4905fccb419dcc07110a';
    const EPAYCO_TEST        = true;

    // ── Inalambria Express (SMS) ─────────────────────────────────────────────────
    const SMS_API_KEY = 'sk_live_xJzMgrEoJExJ1GppbDzJnuPBD0LEhkQhAYmHFZgD0O4';
    const SMS_API_URL = 'https://api.inalambria.express/v1/messages/send';

    // Envía un SMS de texto plano a un único destinatario (formato E.164, ej. +573001234567)
    public static function enviarSMS(string $telefono, string $mensaje): array {
        $ch = curl_init(self::SMS_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . self::SMS_API_KEY,
                'Content-Type: application/json',
            ],
            // async=true: el proveedor encola el envío y responde de inmediato (202 + jobId)
            // en vez de esperar a que el SMS se entregue de verdad, que puede tardar más de 15-20s.
            CURLOPT_POSTFIELDS => json_encode([
                'content'    => $mensaje,
                'recipients' => [$telefono],
                'async'      => true,
            ]),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        error_log("enviarSMS -> HTTP {$code} | err=" . ($err ?: '-') . " | resp=" . substr((string)$resp, 0, 500));

        if ($err) return ['ok' => false, 'msg' => $err];
        $data = json_decode((string)$resp, true) ?? [];
        // El proveedor confirma con cualquier 2xx; solo tratamos como error los códigos 4xx/5xx.
        if ($code >= 200 && $code < 300) return ['ok' => true];
        return ['ok' => false, 'msg' => $data['error'] ?? "No se pudo enviar el SMS (HTTP {$code})."];
    }

    public static function encrypt(string $data): string {
        $iv  = random_bytes(16);
        $enc = openssl_encrypt($data, 'AES-256-CBC', self::ENCRYPT_KEY, 0, $iv);
        return base64_encode($iv . base64_decode($enc));
    }

    public static function decrypt(string $data): string {
        $raw = base64_decode($data);
        $iv  = substr($raw, 0, 16);
        $enc = base64_encode(substr($raw, 16));
        return openssl_decrypt($enc, 'AES-256-CBC', self::ENCRYPT_KEY, 0, $iv) ?: '';
    }

    public static function maskPayment(string $decrypted): string {
        $len = mb_strlen($decrypted);
        if ($len <= 4) return str_repeat('*', $len);
        return str_repeat('*', $len - 4) . mb_substr($decrypted, -4);
    }

    public static function getBasePath(): string {
        return str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    }

    public static function getBaseUrl(): string {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return "{$https}://{$_SERVER['HTTP_HOST']}" . self::getBasePath();
    }
}

// ── Conexión singleton ────────────────────────────────────────────────────────
class DB {
    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                Config::DB_HOST, Config::DB_NAME, Config::DB_CHARSET
            );
            self::$pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,  // true prepared statements
            ]);
        }
        return self::$pdo;
    }
}

// ── Sesión y timeout ─────────────────────────────────────────────────────────
date_default_timezone_set('America/Mexico_City');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['logged_in'])) {
    $now  = time();
    $last = $_SESSION['last_activity'] ?? $now;
    if (($now - $last) > Config::SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['error'] = 'Tu sesión expiró por inactividad.';
        header('Location: ' . Config::getBasePath() . '/login');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}
