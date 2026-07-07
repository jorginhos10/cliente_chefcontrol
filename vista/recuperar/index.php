<?php
// vista/recuperar/index.php
require_once __DIR__ . '/../../config/config.php';
$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña — ChefControl</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="background-blur"></div>

<div class="loginContainer">
    <div class="containerDivIdentidad">
        <img src="<?= $baseUrl ?>/assets/media/src/logo.png" alt="Logo">
        <h1 class="welcomeText">Recuperar contraseña</h1>
    </div>

    <div id="alertBox" class="alert alertError" style="display:none;"></div>

    <!-- ── Paso 1: teléfono ─────────────────────────────────────────────────── -->
    <form id="formTelefono">
        <p style="color:#555;font-size:14px;margin-bottom:18px;text-align:center;">
            Ingresa el número de teléfono registrado con tu restaurante. Te enviaremos un código por SMS.
        </p>
        <div class="formGroup">
            <input type="tel" name="telefono" id="telefono" required
                   inputmode="numeric" maxlength="10" autofocus
                   placeholder="Número de teléfono" autocomplete="tel">
        </div>
        <button type="submit" class="loginButton" id="btnEnviar">
            <i class="fas fa-paper-plane"></i> Enviar código
        </button>
    </form>

    <!-- ── Paso 2: código + nueva contraseña ───────────────────────────────── -->
    <form id="formCodigo" style="display:none;">
        <p style="color:#555;font-size:14px;margin-bottom:6px;text-align:center;">
            Enviamos un código a <strong id="telefonoMascara"></strong>.
        </p>
        <p style="text-align:center;margin-bottom:18px;">
            <span id="countdown" style="color:#007bff;font-weight:bold;font-size:14px;"></span>
        </p>

        <div class="formGroup">
            <input type="text" name="codigo" id="codigo" required
                   maxlength="6" inputmode="numeric" pattern="\d{6}"
                   placeholder="Código de 6 dígitos" autocomplete="one-time-code"
                   style="text-align:center;letter-spacing:6px;font-size:20px;">
        </div>

        <div id="camposPassword" style="display:none;">
            <div class="formGroup">
                <input type="password" name="password" id="password"
                       minlength="6" placeholder="Nueva contraseña" autocomplete="new-password">
            </div>

            <div class="formGroup">
                <input type="password" name="password_confirmation" id="password_confirmation"
                       minlength="6" placeholder="Confirmar contraseña" autocomplete="new-password">
            </div>

            <button type="submit" class="loginButton" id="btnReset">
                <i class="fas fa-check-circle"></i> Cambiar contraseña
            </button>
        </div>

        <p style="text-align:center;">
            <a href="#" id="btnReenviar" style="font-size:13px;color:#666;text-decoration:none;">
                Solicitar otro código
            </a>
        </p>
    </form>

    <div id="successBox" class="alert alertSuccess" style="display:none;"></div>

    <div style="text-align:center;margin-top:16px;padding-top:14px;border-top:1px solid #e0e0e0;">
        <a href="<?= $basePath ?>/login" style="font-size:13px;font-weight:700;color:#007bff;text-decoration:none;">
            <i class="fas fa-arrow-left" style="margin-right:4px;"></i>Volver a iniciar sesión
        </a>
    </div>
</div>

<script>
const BP = '<?= $basePath ?>';
let resetId = null;
let countdownTimer = null;
let codigoValidado = null;

const alertBox   = document.getElementById('alertBox');
const successBox = document.getElementById('successBox');

function mostrarError(msg) {
    successBox.style.display = 'none';
    alertBox.textContent = msg;
    alertBox.style.display = 'block';
}

function ocultarError() {
    alertBox.style.display = 'none';
}

function iniciarCountdown(segundos) {
    clearInterval(countdownTimer);
    const el = document.getElementById('countdown');
    const fin = Date.now() + segundos * 1000;

    function tick() {
        const restante = Math.max(0, Math.round((fin - Date.now()) / 1000));
        const m = String(Math.floor(restante / 60)).padStart(2, '0');
        const s = String(restante % 60).padStart(2, '0');
        el.textContent = restante > 0 ? `El código vence en ${m}:${s}` : 'El código expiró, solicita uno nuevo.';
        el.style.color = restante > 0 ? '#007bff' : '#e74c3c';
        document.getElementById('btnReset').disabled = restante <= 0;
        if (restante <= 0) clearInterval(countdownTimer);
    }
    tick();
    countdownTimer = setInterval(tick, 1000);
}

document.getElementById('telefono').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
});

document.getElementById('codigo').addEventListener('input', async function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
    if (this.value.length < 6) return;

    ocultarError();
    this.disabled = true;

    try {
        const fd = new FormData();
        fd.append('reset_id', resetId);
        fd.append('codigo', this.value);
        const res = await fetch(`${BP}/recuperar/verificar`, { method: 'POST', body: fd });
        const d   = await res.json();

        if (!d.ok) {
            mostrarError(d.msg || 'Código incorrecto.');
            this.value = '';
            this.disabled = false;
            this.focus();
        } else {
            codigoValidado = this.value;
            document.getElementById('camposPassword').style.display = '';
            document.getElementById('password').focus();
        }
    } catch (ex) {
        mostrarError('Error de conexión. Intenta de nuevo.');
        this.disabled = false;
    }
});

document.getElementById('formTelefono').addEventListener('submit', async function(e) {
    e.preventDefault();
    ocultarError();
    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
        const fd  = new FormData(this);
        const res = await fetch(`${BP}/recuperar/enviar`, { method: 'POST', body: fd });
        const d   = await res.json();

        if (!d.ok) {
            mostrarError(d.msg || 'No se pudo enviar el código.');
        } else {
            resetId = d.reset_id;
            document.getElementById('telefonoMascara').textContent = d.telefono_mascara || '';
            this.style.display = 'none';
            document.getElementById('formCodigo').style.display = '';
            iniciarCountdown(d.expira_segundos || 1200);
            document.getElementById('codigo').focus();
        }
    } catch (ex) {
        mostrarError('Error de conexión. Intenta de nuevo.');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar código';
});

document.getElementById('formCodigo').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!codigoValidado) return;
    ocultarError();

    const password = document.getElementById('password').value;
    const confirm  = document.getElementById('password_confirmation').value;
    if (password.length < 6) { mostrarError('La contraseña debe tener al menos 6 caracteres.'); return; }
    if (password !== confirm) { mostrarError('Las contraseñas no coinciden.'); return; }

    const btn = document.getElementById('btnReset');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

    try {
        const fd = new FormData();
        fd.append('reset_id', resetId);
        fd.append('codigo', codigoValidado);
        fd.append('password', document.getElementById('password').value);
        fd.append('password_confirmation', document.getElementById('password_confirmation').value);
        const res = await fetch(`${BP}/recuperar/reset`, { method: 'POST', body: fd });
        const d   = await res.json();

        if (!d.ok) {
            mostrarError(d.msg || 'No se pudo cambiar la contraseña.');
        } else {
            clearInterval(countdownTimer);
            this.style.display = 'none';
            successBox.textContent = d.msg || 'Contraseña actualizada correctamente.';
            successBox.style.display = 'block';
            setTimeout(() => { window.location.href = `${BP}/login`; }, 2000);
        }
    } catch (ex) {
        mostrarError('Error de conexión. Intenta de nuevo.');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i> Cambiar contraseña';
});

document.getElementById('btnReenviar').addEventListener('click', function(e) {
    e.preventDefault();
    clearInterval(countdownTimer);
    codigoValidado = null;
    const codigoInput = document.getElementById('codigo');
    codigoInput.disabled = false;
    document.getElementById('camposPassword').style.display = 'none';
    document.getElementById('formCodigo').style.display = 'none';
    document.getElementById('formTelefono').style.display = '';
    document.getElementById('formCodigo').reset();
    ocultarError();
});
</script>
</body>
</html>
