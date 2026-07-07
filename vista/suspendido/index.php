<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();
$nombre   = htmlspecialchars($_SESSION['comercio_nombre'] ?? 'Tu restaurante');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta suspendida — ChefControl</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .susp-card {
            width: 100%;
            max-width: 440px;
            background: rgba(255,255,255,.98);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
            padding: 40px 36px 32px;
            text-align: center;
        }
        .susp-icon {
            width: 72px; height: 72px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: #dc2626;
        }
        .susp-card h2 {
            font-size: 20px;
            color: #1e293b;
            margin: 0 0 10px;
            font-weight: 700;
        }
        .susp-card .comercio {
            font-size: 15px;
            color: #6366f1;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .susp-card p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin: 0 0 24px;
        }
        .susp-card .info-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 13px;
            color: #b91c1c;
            margin-bottom: 24px;
            text-align: left;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .info-box i { margin-top: 1px; flex-shrink: 0; }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #f1f5f9;
            color: #475569;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-logout:hover { background: #e2e8f0; }
        .logo-top {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
            color: #e53935;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: .5px;
        }
        .logo-top i { font-size: 22px; }
    </style>
</head>
<body>
<div class="background-blur"></div>

<div class="susp-card">
    <div class="logo-top">
        <i class="fas fa-utensils"></i> CHEFCONTROL
    </div>

    <div class="susp-icon">
        <i class="fas fa-ban"></i>
    </div>

    <h2>Comercio suspendido</h2>
    <div class="comercio"><?= $nombre ?></div>

    <div class="info-box">
        <i class="fas fa-circle-exclamation"></i>
        <span>Su comercio se encuentra temporalmente suspendido. Para más información comuníquese con el soporte de ChefControl.</span>
    </div>

    <p>Si crees que esto es un error o necesitas reactivar tu cuenta, comunícate con nuestro equipo de soporte.</p>

    <a href="<?= $basePath ?>/logout" class="btn-logout">
        <i class="fas fa-right-from-bracket"></i> Cerrar sesión
    </a>
</div>
</body>
</html>
