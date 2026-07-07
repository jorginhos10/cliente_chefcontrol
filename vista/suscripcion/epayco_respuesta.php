<?php
require_once __DIR__ . '/../../config/security.php';

$ref    = htmlspecialchars($_GET['ref_payco'] ?? $_POST['x_ref_payco'] ?? '');
$estado = strtolower($_GET['x_transaction_state'] ?? $_POST['x_transaction_state'] ?? '');
$monto  = htmlspecialchars($_GET['x_amount'] ?? $_POST['x_amount'] ?? '0');
$plan   = htmlspecialchars($_GET['x_extra2']  ?? $_POST['x_extra2']  ?? '');

$basePath = Config::getBasePath();
$ok = ($estado === 'aceptada');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $ok ? 'Pago exitoso' : 'Pago no completado' ?> — ChefControl</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif;
               min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 20px; padding: 40px 36px; text-align: center;
                max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.12); }
        .icon { width: 72px; height: 72px; border-radius: 50%; display: inline-flex;
                align-items: center; justify-content: center; font-size: 30px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        p  { color: #7f8c8d; font-size: 14px; line-height: 1.6; }
        .ref { background: #f8f9fc; border-radius: 8px; padding: 10px 14px; margin: 16px 0;
               font-family: monospace; font-size: 13px; color: #2c3e50; }
        .btn { display: inline-block; margin-top: 20px; padding: 13px 28px; border-radius: 10px;
               font-size: 14px; font-weight: 700; text-decoration: none; transition: .15s; }
    </style>
</head>
<body>
<div class="card">
    <?php if ($ok): ?>
    <div class="icon" style="background:#dcfce7;color:#16a34a;">
        <i class="fas fa-circle-check"></i>
    </div>
    <h1 style="color:#16a34a;">¡Pago exitoso!</h1>
    <p>Tu suscripción al plan <strong><?= ucfirst($plan) ?></strong> ha sido activada correctamente.</p>
    <?php if ($ref): ?>
    <div class="ref"><i class="fas fa-receipt" style="color:#95a5a6;margin-right:6px;"></i>Ref: <?= $ref ?></div>
    <?php endif; ?>
    <p style="font-size:13px;">Monto pagado: <strong style="color:#16a34a;">$<?= number_format((float)$monto, 0, ',', '.') ?> COP</strong></p>
    <a href="<?= $basePath ?>/suscripcion" class="btn" style="background:#16a34a;color:#fff;">
        <i class="fas fa-crown"></i> Ver mi plan
    </a>
    <?php else: ?>
    <div class="icon" style="background:#fee2e2;color:#dc2626;">
        <i class="fas fa-circle-xmark"></i>
    </div>
    <h1 style="color:#dc2626;">Pago no completado</h1>
    <p>El pago no pudo procesarse. Estado: <strong><?= htmlspecialchars($estado ?: 'desconocido') ?></strong></p>
    <?php if ($ref): ?>
    <div class="ref"><i class="fas fa-receipt" style="color:#95a5a6;margin-right:6px;"></i>Ref: <?= $ref ?></div>
    <?php endif; ?>
    <p style="font-size:13px;margin-top:8px;">Puedes intentarlo de nuevo desde tu panel.</p>
    <a href="<?= $basePath ?>/suscripcion" class="btn" style="background:#ef4444;color:#fff;">
        <i class="fas fa-arrow-left"></i> Volver a Mi Plan
    </a>
    <?php endif; ?>
</div>
</body>
</html>
