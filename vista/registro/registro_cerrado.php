<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro cerrado — ChefControl</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing:border-box; }
        body { min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px; }
        .card { width:100%;max-width:420px;background:rgba(255,255,255,.98);border-radius:20px;
                box-shadow:0 20px 60px rgba(0,0,0,.15);padding:40px 36px;text-align:center; }
        .icon-wrap { width:72px;height:72px;border-radius:50%;background:#fee2e2;
                     display:flex;align-items:center;justify-content:center;margin:0 auto 20px; }
        .icon-wrap i { font-size:30px;color:#dc2626; }
        h1 { font-size:22px;font-weight:800;color:#1f2937;margin:0 0 10px; }
        p  { color:#6b7280;font-size:14px;line-height:1.6;margin:0 0 24px; }
        .btn-back { display:inline-flex;align-items:center;gap:8px;background:#1f2937;color:#fff;
                    text-decoration:none;border-radius:10px;padding:12px 24px;
                    font-size:14px;font-weight:600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <i class="fas fa-lock"></i>
        </div>
        <h1>Registro cerrado</h1>
        <p>El registro de nuevos restaurantes está temporalmente deshabilitado.<br>
           Contacta al administrador para más información.</p>
        <a href="<?= $basePath ?>/login" class="btn-back">
            <i class="fas fa-arrow-left"></i> Ir al inicio de sesión
        </a>
    </div>
</body>
</html>
