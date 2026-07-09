<?php
// vista/login/login.php
require_once __DIR__ . '/../../config/config.php';
$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — ChefControl</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="background-blur"></div>

<div class="loginContainer">
    <div class="containerDivIdentidad">
        <img src="<?= $baseUrl ?>/assets/media/src/logo.png" alt="Logo">
        <h1 class="welcomeText">ChefControl</h1>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alertError">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alertSuccess">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="<?= $basePath ?>/login">

        <div class="formGroup">
            <input type="text" name="username" required
                   placeholder="Usuario o correo electrónico"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   autocomplete="username">
        </div>

        <div class="formGroup passwordGroup">
            <input type="password" name="password" id="password" required
                   placeholder="Contraseña"
                   autocomplete="current-password">
            <button type="button" class="eyeBtn" onclick="togglePass('password', this)" tabindex="-1">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <div style="text-align:right;margin-top:-8px;">
            <a href="<?= $basePath ?>/recuperar" style="font-size:13px;color:#666;text-decoration:none;">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <hr class="divider">

        <button type="submit" class="loginButton" id="btnLogin">
            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
        </button>
    </form>

    <div style="text-align:center;margin-top:16px;padding-top:14px;border-top:1px solid #e0e0e0;">
        <span style="font-size:13px;color:#666;">¿No tienes cuenta?</span>
        <a href="<?= $basePath ?>/registro"
           style="font-size:13px;font-weight:700;color:#28a745;text-decoration:none;margin-left:6px;">
            <i class="fas fa-store" style="margin-right:4px;"></i>Regístrate gratis
        </a>
    </div>
</div>

<?php if (!empty($_SESSION['login_restriccion'])): ?>
<div id="restriccionOverlay" style="position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;z-index:9999;">
    <div style="background:#fff;border-radius:16px;padding:36px 32px;max-width:360px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div style="width:60px;height:60px;border-radius:50%;background:#fff3cd;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <i class="fas fa-clock" style="font-size:26px;color:#e67e22;"></i>
        </div>
        <h3 style="color:#2c3e50;margin:0 0 10px;font-size:18px;">Acceso restringido</h3>
        <p style="color:#7f8c8d;line-height:1.6;margin:0 0 24px;font-size:14px;">
            <?= htmlspecialchars($_SESSION['login_restriccion']); unset($_SESSION['login_restriccion']); ?>
        </p>
        <button onclick="document.getElementById('restriccionOverlay').remove()"
                style="background:linear-gradient(135deg,#2ecc71,#27ae60);color:#fff;border:none;padding:12px 32px;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">
            Entendido
        </button>
    </div>
</div>
<?php endif; ?>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnLogin');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
});
</script>
<script src="<?= $baseUrl ?>/assets/js/login.js"></script>
</body>
</html>
