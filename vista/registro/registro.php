<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();

$error = $_SESSION['registro_error'] ?? null;
$datos = $_SESSION['registro_datos'] ?? [];
unset($_SESSION['registro_error'], $_SESSION['registro_datos']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear restaurante — ChefControl</title>
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
            overflow-y: auto;
        }

        .reg-card {
            width: 100%;
            max-width: 520px;
            background: rgba(255,255,255,.97);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.18);
            overflow: hidden;
        }

        /* Cabecera */
        .reg-head {
            background: linear-gradient(135deg, #0f7a5a 0%, #14a07a 100%);
            padding: 28px 32px 24px;
            text-align: center;
            color: #fff;
        }
        .reg-head img { width: 56px; margin-bottom: 10px; }
        .reg-head h1 { font-size: 22px; font-weight: 800; margin: 0 0 4px; letter-spacing: .3px; }
        .reg-head p  { font-size: 13px; opacity: .85; margin: 0; }

        /* Cuerpo */
        .reg-body { padding: 24px 28px 28px; }

        /* Alerta error */
        .alert-err {
            background: #fee2e2; border: 1px solid #fca5a5;
            color: #991b1b; border-radius: 10px;
            padding: 11px 14px; font-size: 13px;
            margin-bottom: 18px;
            display: flex; align-items: flex-start; gap: 8px;
        }

        /* Sección */
        .section-header {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 12px;
        }
        .section-header .s-icon {
            width: 28px; height: 28px; border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }
        .s-icon.green  { background: #d1fae5; color: #059669; }
        .s-icon.indigo { background: #e0e7ff; color: #4338ca; }
        .section-header span {
            font-size: 11px; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: #6b7280;
        }

        /* Inputs */
        .fields { display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px; }
        .row2   { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .field {
            position: relative;
        }
        .field i.f-icon {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; font-size: 13px; pointer-events: none;
        }
        .field input {
            width: 100%; padding: 10px 12px 10px 36px;
            border: 1.5px solid #e5e7eb; border-radius: 9px;
            font-size: 14px; color: #1f2937;
            background: #f9fafb;
            transition: border-color .2s, background .2s;
            outline: none;
        }
        .field input:focus {
            border-color: #10b981; background: #fff;
        }
        .field input::placeholder { color: #9ca3af; }

        /* Prefijo teléfono */
        .field.phone i.f-icon { left: 46px; }
        .field.phone input    { padding-left: 72px; }
        .phone-prefix {
            position: absolute; left: 0; top: 0; bottom: 0;
            width: 44px; display: flex; align-items: center; justify-content: center;
            border-right: 1.5px solid #e5e7eb; border-radius: 9px 0 0 9px;
            font-size: 12px; font-weight: 600; color: #6b7280;
            background: #f3f4f6; pointer-events: none;
        }

        /* Toggle contraseña */
        .eye-btn {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #9ca3af; cursor: pointer; font-size: 13px; padding: 4px;
        }
        .eye-btn:hover { color: #10b981; }
        .field.has-eye input { padding-right: 36px; }

        /* Barra de fuerza */
        .strength-wrap { margin-top: 5px; }
        .strength-bar {
            height: 3px; background: #e5e7eb;
            border-radius: 2px; overflow: hidden;
        }
        .strength-bar .fill { height: 100%; width: 0; border-radius: 2px; transition: all .3s; }
        .strength-label { font-size: 10px; color: #9ca3af; margin-top: 3px; }

        /* Divider */
        .section-divider { border: none; border-top: 1.5px solid #f1f5f9; margin: 18px 0; }

        /* Botón */
        .btn-crear {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: opacity .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-crear:hover    { opacity: .9; }
        .btn-crear:disabled { opacity: .6; cursor: not-allowed; }

        .terms {
            text-align: center; font-size: 11px; color: #9ca3af; margin-top: 8px;
        }
        .login-link {
            text-align: center; font-size: 13px; color: #6b7280; margin-top: 16px;
        }
        .login-link a { color: #059669; font-weight: 600; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="background-blur"></div>

<div class="reg-card">

    <!-- Cabecera -->
    <div class="reg-head">
        <img src="<?= $baseUrl ?>/assets/media/src/logo.png" alt="ChefControl">
        <h1>ChefControl</h1>
        <p>Crea tu cuenta y empieza a gestionar tu restaurante</p>
    </div>

    <!-- Cuerpo -->
    <div class="reg-body">

        <?php if ($error): ?>
        <div class="alert-err">
            <i class="fas fa-exclamation-circle" style="margin-top:2px;flex-shrink:0;"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <form id="registroForm" method="POST" action="<?= $basePath ?>/registro/crear">

            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">

            <!-- ── Sección: Tu restaurante ── -->
            <div class="section-header">
                <div class="s-icon green"><i class="fas fa-store"></i></div>
                <span>Tu restaurante</span>
            </div>

            <div class="fields">
                <div class="field">
                    <i class="f-icon fas fa-utensils"></i>
                    <input type="text" name="nombre_comercio"
                           placeholder="Nombre del restaurante"
                           value="<?= htmlspecialchars($datos['nombreComercio'] ?? '') ?>"
                           required minlength="3" maxlength="100">
                </div>
                <div class="field phone">
                    <span class="phone-prefix">+57</span>
                    <i class="f-icon fas fa-phone"></i>
                    <input type="tel" name="telefono"
                           placeholder="Número de teléfono"
                           value="<?= htmlspecialchars($datos['telefono'] ?? '') ?>"
                           maxlength="20">
                </div>
            </div>

            <hr class="section-divider">

            <!-- ── Sección: Cuenta de administrador ── -->
            <div class="section-header">
                <div class="s-icon indigo"><i class="fas fa-user-tie"></i></div>
                <span>Cuenta de administrador</span>
            </div>

            <div class="fields">
                <div class="row2">
                    <div class="field">
                        <i class="f-icon fas fa-user"></i>
                        <input type="text" name="nombre_admin"
                               placeholder="Nombre completo"
                               value="<?= htmlspecialchars($datos['nombreAdmin'] ?? '') ?>"
                               required minlength="2" maxlength="100">
                    </div>
                    <div class="field">
                        <i class="f-icon fas fa-at"></i>
                        <input type="text" name="username"
                               placeholder="Usuario (ej: juan)"
                               value="<?= htmlspecialchars($datos['username'] ?? '') ?>"
                               required pattern="[a-zA-Z0-9_]{3,30}" maxlength="30"
                               title="Solo letras, números y guión bajo. 3-30 caracteres.">
                    </div>
                </div>
                <div class="field">
                    <i class="f-icon fas fa-envelope"></i>
                    <input type="email" name="email"
                           placeholder="Correo electrónico"
                           value="<?= htmlspecialchars($datos['email'] ?? '') ?>"
                           required maxlength="100">
                </div>
                <div class="row2">
                    <div class="field has-eye">
                        <i class="f-icon fas fa-lock"></i>
                        <input type="password" id="password" name="password"
                               placeholder="Contraseña" required minlength="6">
                        <button type="button" class="eye-btn" onclick="togglePass('password',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="strength-wrap">
                            <div class="strength-bar"><div class="fill" id="strengthFill"></div></div>
                            <div class="strength-label" id="strengthLabel"></div>
                        </div>
                    </div>
                    <div class="field has-eye">
                        <i class="f-icon fas fa-lock"></i>
                        <input type="password" id="password_confirm" name="password_confirm"
                               placeholder="Confirmar contraseña" required minlength="6">
                        <button type="button" class="eye-btn" onclick="togglePass('password_confirm',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-crear" id="btnRegistrar">
                <i class="fas fa-rocket"></i> Crear mi restaurante
            </button>
            <p class="terms">Al registrarte aceptas los términos de uso del sistema.</p>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="<?= $basePath ?>/login">Inicia sesión aquí</a>
        </div>

    </div><!-- /reg-body -->
</div><!-- /reg-card -->

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

const strengthLabels = ['', 'Muy débil', 'Débil', 'Regular', 'Fuerte', 'Muy fuerte'];
const strengthColors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#15803d'];

document.getElementById('password').addEventListener('input', function() {
    const v = this.value;
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let score = 0;
    if (v.length >= 6)           score++;
    if (v.length >= 10)          score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^a-zA-Z0-9]/.test(v))  score++;
    fill.style.width      = (score * 20) + '%';
    fill.style.background = strengthColors[score] || '#e5e7eb';
    label.textContent     = v.length ? (strengthLabels[score] || '') : '';
    label.style.color     = strengthColors[score] || '';
});

document.getElementById('registroForm').addEventListener('submit', function(e) {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('password_confirm').value;
    if (p1 !== p2) {
        e.preventDefault();
        alert('Las contraseñas no coinciden.');
        return;
    }
    const btn = document.getElementById('btnRegistrar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando restaurante...';
});
</script>
</body>
</html>
