<?php
// vista/perfil/index.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Mi Perfil - CHEFCONTROL';
$paginaActual = 'perfil';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$jsExtra = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
require_once __DIR__ . '/../complementos/header.php';

$roles = [
    'admin'      => ['Administrador', '#8e44ad', 'fa-user-shield'],
    'mesero'     => ['Mesero',        '#16a085', 'fa-user-tie'],
    'cocina'     => ['Cocina',        '#e67e22', 'fa-fire'],
    'inventario' => ['Inventario',    '#2980b9', 'fa-boxes-stacked'],
];
[$rolLabel, $rolColor, $rolIcon] = $roles[$usuario['rol']] ?? [$usuario['rol'], '#7f8c8d', 'fa-user'];

$avatarSrc = $baseUrl . '/assets/media/users/' . ($usuario['avatar'] ?? 'default.png');
$ultimoLogin = $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca';
$miembro     = date('d/m/Y', strtotime($usuario['fecha_creacion']));
?>

<div class="prf-wrap">

    <!-- Sidebar de perfil -->
    <div class="prf-sidebar">
        <div class="prf-avatar-box">
            <div class="prf-avatar-ring">
                <img src="<?php echo $avatarSrc; ?>" alt="Avatar" id="avatarImg"
                     onerror="this.src='<?php echo $baseUrl; ?>/assets/media/users/default.png'">
                <label class="prf-avatar-edit" title="Cambiar foto">
                    <i class="fas fa-camera"></i>
                    <input type="file" id="avatarInput" accept="image/*" style="display:none">
                </label>
            </div>
        </div>
        <div class="prf-name"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
        <div class="prf-username">@<?php echo htmlspecialchars($usuario['username']); ?></div>
        <span class="prf-role-badge" style="background:<?php echo $rolColor; ?>20;color:<?php echo $rolColor; ?>">
            <i class="fas <?php echo $rolIcon; ?>"></i>
            <?php echo $rolLabel; ?>
        </span>

        <div class="prf-meta">
            <div class="prf-meta-row">
                <i class="fas fa-envelope"></i>
                <span><?php echo htmlspecialchars($usuario['email']); ?></span>
            </div>
            <div class="prf-meta-row">
                <i class="fas fa-clock"></i>
                <span>Último acceso: <?php echo $ultimoLogin; ?></span>
            </div>
            <div class="prf-meta-row">
                <i class="fas fa-calendar-plus"></i>
                <span>Miembro desde: <?php echo $miembro; ?></span>
            </div>
        </div>
    </div>

    <!-- Panel principal -->
    <div class="prf-main">

        <!-- Información personal -->
        <div class="prf-card">
            <div class="prf-card-head">
                <i class="fas fa-id-card"></i> Información personal
            </div>
            <form id="formPerfil" enctype="multipart/form-data">
                <input type="file" name="avatar" id="avatarFile" style="display:none" accept="image/*">
                <div class="prf-grid">
                    <div class="prf-field">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre" id="fNombre"
                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                               class="prf-input" required>
                    </div>
                    <div class="prf-field">
                        <label>Correo electrónico</label>
                        <input type="email" name="email" id="fEmail"
                               value="<?php echo htmlspecialchars($usuario['email']); ?>"
                               class="prf-input" required>
                    </div>
                    <div class="prf-field">
                        <label>Usuario</label>
                        <input type="text" value="<?php echo htmlspecialchars($usuario['username']); ?>"
                               class="prf-input" disabled>
                    </div>
                    <div class="prf-field">
                        <label>Rol</label>
                        <input type="text" value="<?php echo $rolLabel; ?>"
                               class="prf-input" disabled>
                    </div>
                </div>
                <div class="prf-actions">
                    <button type="submit" class="prf-btn-save">
                        <i class="fas fa-floppy-disk"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- Cambiar contraseña -->
        <div class="prf-card">
            <div class="prf-card-head">
                <i class="fas fa-lock"></i> Cambiar contraseña
            </div>
            <form id="formPassword">
                <div class="prf-grid">
                    <div class="prf-field prf-field-full">
                        <label>Contraseña actual</label>
                        <div class="prf-pass-wrap">
                            <input type="password" name="password_actual" id="fPassActual"
                                   class="prf-input" placeholder="Tu contraseña actual">
                            <button type="button" class="prf-eye" onclick="togglePass('fPassActual',this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="prf-field">
                        <label>Nueva contraseña</label>
                        <div class="prf-pass-wrap">
                            <input type="password" name="password_nueva" id="fPassNueva"
                                   class="prf-input" placeholder="Mínimo 6 caracteres">
                            <button type="button" class="prf-eye" onclick="togglePass('fPassNueva',this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="prf-field">
                        <label>Confirmar contraseña</label>
                        <div class="prf-pass-wrap">
                            <input type="password" name="password_confirmar" id="fPassConf"
                                   class="prf-input" placeholder="Repite la nueva contraseña">
                            <button type="button" class="prf-eye" onclick="togglePass('fPassConf',this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="prf-actions">
                    <button type="submit" class="prf-btn-save prf-btn-pass">
                        <i class="fas fa-key"></i> Actualizar contraseña
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
.prf-wrap { display:flex; gap:24px; padding:28px; background:#f0f2f5; min-height:calc(100vh - 70px); align-items:flex-start; }

/* Sidebar */
.prf-sidebar { width:260px; flex-shrink:0; background:#fff; border-radius:16px; padding:28px 20px; box-shadow:0 2px 12px rgba(0,0,0,.06); text-align:center; position:sticky; top:20px; }
.prf-avatar-box { margin-bottom:16px; }
.prf-avatar-ring { position:relative; display:inline-block; }
.prf-avatar-ring img { width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #e8ecf0; }
.prf-avatar-edit { position:absolute; bottom:2px; right:2px; background:#2c3e50; color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; cursor:pointer; transition:.15s; }
.prf-avatar-edit:hover { background:#e67e22; }
.prf-name { font-size:17px; font-weight:800; color:#2c3e50; margin-bottom:4px; }
.prf-username { font-size:13px; color:#95a5a6; margin-bottom:12px; }
.prf-role-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-bottom:20px; }
.prf-meta { text-align:left; border-top:1px solid #f0f2f5; padding-top:16px; display:flex; flex-direction:column; gap:10px; }
.prf-meta-row { display:flex; align-items:center; gap:8px; font-size:12px; color:#636e72; }
.prf-meta-row i { color:#b2bec3; width:14px; text-align:center; }

/* Main */
.prf-main { flex:1; display:flex; flex-direction:column; gap:20px; }
.prf-card { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.06); overflow:hidden; }
.prf-card-head { background:linear-gradient(135deg,#1a252f,#2c3e50); color:#fff; padding:14px 22px; font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; }
.prf-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:22px; }
.prf-field { display:flex; flex-direction:column; gap:6px; }
.prf-field-full { grid-column:1/-1; }
.prf-field label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#95a5a6; }
.prf-input { border:1.5px solid #dfe6e9; border-radius:9px; padding:9px 14px; font-size:14px; color:#2c3e50; outline:none; transition:.15s; }
.prf-input:focus { border-color:#2c3e50; }
.prf-input:disabled { background:#f8f9fa; color:#b2bec3; cursor:not-allowed; }
.prf-pass-wrap { position:relative; }
.prf-pass-wrap .prf-input { width:100%; box-sizing:border-box; padding-right:42px; }
.prf-eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:#95a5a6; cursor:pointer; font-size:14px; padding:4px; }
.prf-eye:hover { color:#2c3e50; }
.prf-actions { padding:0 22px 22px; display:flex; justify-content:flex-end; }
.prf-btn-save { background:#27ae60; color:#fff; border:none; border-radius:9px; padding:10px 22px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.15s; }
.prf-btn-save:hover { background:#219a52; }
.prf-btn-pass { background:#2980b9; }
.prf-btn-pass:hover { background:#1f6692; }

@media (max-width:768px) {
    .prf-wrap { flex-direction:column; padding:16px; }
    .prf-sidebar { width:100%; position:static; }
    .prf-grid { grid-template-columns:1fr; }
}
</style>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
const BASE_PATH = '<?php echo $basePath; ?>';

// Previsualizar avatar al seleccionar
document.querySelector('.prf-avatar-edit').addEventListener('click', () => {
    document.getElementById('avatarInput').click();
});

document.getElementById('avatarInput').addEventListener('change', function() {
    if (!this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatarImg').src = e.target.result;
        // Copiar al input del form
        const dt = new DataTransfer();
        dt.items.add(this.files[0]);
        document.getElementById('avatarFile').files = dt.files;
    };
    reader.readAsDataURL(this.files[0]);
});

// Guardar perfil
document.getElementById('formPerfil').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    // Incluir avatar si fue seleccionado
    const avatarFile = document.getElementById('avatarInput').files[0];
    if (avatarFile) fd.set('avatar', avatarFile);

    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    try {
        const res = await fetch(BASE_PATH + '/perfil/guardar', { method:'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            if (data.avatar) {
                document.getElementById('avatarImg').src = BASE_URL + '/assets/media/users/' + data.avatar;
                // Actualizar avatar del header
                document.querySelectorAll('.userAvatar img').forEach(img => {
                    img.src = BASE_URL + '/assets/media/users/' + data.avatar;
                });
            }
            Swal.fire({ icon:'success', title:'¡Guardado!', text: data.message, timer:2000, showConfirmButton:false });
        } else {
            Swal.fire({ icon:'error', title:'Error', text: data.message });
        }
    } catch {
        Swal.fire({ icon:'error', title:'Error', text:'Error de conexión' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar cambios';
    }
});

// Cambiar contraseña
document.getElementById('formPassword').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';

    const payload = {
        password_actual:    document.getElementById('fPassActual').value,
        password_nueva:     document.getElementById('fPassNueva').value,
        password_confirmar: document.getElementById('fPassConf').value,
    };

    try {
        const res  = await fetch(BASE_PATH + '/perfil/cambiar-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            this.reset();
            Swal.fire({ icon:'success', title:'¡Actualizada!', text: data.message, timer:2000, showConfirmButton:false });
        } else {
            Swal.fire({ icon:'error', title:'Error', text: data.message });
        }
    } catch {
        Swal.fire({ icon:'error', title:'Error', text:'Error de conexión' });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-key"></i> Actualizar contraseña';
    }
});

function togglePass(inputId, btn) {
    const inp = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') { inp.type = 'text';     icon.className = 'fas fa-eye-slash'; }
    else                         { inp.type = 'password'; icon.className = 'fas fa-eye'; }
}
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
