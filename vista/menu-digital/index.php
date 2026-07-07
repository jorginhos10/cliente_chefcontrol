<?php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Menú Digital - CHEFCONTROL';
$paginaActual = 'menu-digital';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';
?>

<style>
.md-page { padding: 28px 32px 48px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }

/* Header */
.md-header {
    background: linear-gradient(135deg,#1a3a6e,#2471a3);
    border-radius: 16px; padding: 28px 32px; color: #fff;
    display: flex; align-items: center; gap: 20px;
    margin-bottom: 28px; box-shadow: 0 4px 20px rgba(0,0,0,.12);
}
.md-header-icon {
    width: 60px; height: 60px; background: rgba(255,255,255,.2);
    border-radius: 16px; display: flex; align-items: center;
    justify-content: center; font-size: 26px; flex-shrink: 0;
}
.md-header-text h1 { margin: 0 0 4px; font-size: 26px; font-weight: 800; }
.md-header-text p  { margin: 0; opacity: .75; font-size: 14px; }
.md-header-btn {
    margin-left: auto; background: rgba(255,255,255,.2); color: #fff;
    border: 1.5px solid rgba(255,255,255,.4); border-radius: 10px;
    padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
    transition: background .15s;
}
.md-header-btn:hover { background: rgba(255,255,255,.3); }

/* Grid */
.md-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
    gap: 20px;
}

/* Cards */
.md-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    overflow: hidden; display: flex; flex-direction: column;
    transition: box-shadow .2s, transform .2s;
}
.md-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.12); transform: translateY(-2px); }

.md-card-top {
    background: linear-gradient(135deg,#1a3a6e,#2471a3);
    padding: 22px 20px 18px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
}
.md-card-icon {
    width: 48px; height: 48px; background: rgba(255,255,255,.2);
    border-radius: 12px; display: flex; align-items: center;
    justify-content: center; font-size: 20px; color: #fff; flex-shrink: 0;
}
.md-card-badge {
    font-size: 11px; font-weight: 700; padding: 4px 10px;
    border-radius: 20px; letter-spacing: .3px;
}
.md-card-badge.on  { background: rgba(52,199,89,.25);  color: #34c759; border: 1px solid rgba(52,199,89,.4); }
.md-card-badge.off { background: rgba(255,255,255,.12); color: rgba(255,255,255,.6); border: 1px solid rgba(255,255,255,.2); }

.md-card-body { padding: 18px 20px 14px; flex: 1; }
.md-card-name { font-size: 16px; font-weight: 700; color: #1c1c1e; margin: 0 0 5px; }
.md-card-desc { font-size: 13px; color: #8e8e93; margin: 0 0 12px; line-height: 1.45; }
.md-card-url  {
    font-size: 11px; color: #aeaeb2; background: #f2f2f7; border-radius: 8px;
    padding: 6px 10px; word-break: break-all; font-family: monospace;
    border: 1px solid #e5e5ea;
}

.md-card-actions {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 16px; border-top: 1px solid #f2f2f7;
}
.md-btn {
    border: none; border-radius: 8px; padding: 7px 13px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 5px; transition: all .15s;
}
.md-btn-build  { background: #1a3a6e; color: #fff; }
.md-btn-build:hover  { background: #2471a3; }
.md-btn-qr     { background: #f0fdf4; color: #16a34a; }
.md-btn-qr:hover     { background: #dcfce7; }
.md-btn-toggle { background: #fff8e6; color: #c87d00; }
.md-btn-toggle:hover { background: #fef3c7; }
.md-btn-dup    { background: #f5f0ff; color: #7c3aed; }
.md-btn-dup:hover    { background: #ede9fe; }
.md-btn-delete { background: #fef2f2; color: #dc2626; margin-left: auto; }
.md-btn-delete:hover { background: #fee2e2; }

/* Gear button */
.md-btn-gear {
    width: 28px; height: 28px; border-radius: 7px; border: none;
    background: rgba(255,255,255,.18); color: rgba(255,255,255,.9);
    font-size: 13px; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.md-btn-gear:hover { background: rgba(255,255,255,.32); color: #fff; }

/* Mesa chip en card body */
.md-mesa-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600; padding: 3px 9px;
    border-radius: 20px; margin-bottom: 8px;
}
.md-mesa-chip.asignada { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
.md-mesa-chip.libre    { background: #fafafa;  color: #9e9e9e; border: 1px solid #e0e0e0; }

/* New card */
.md-card-new {
    border: 2px dashed #d1d1d6; border-radius: 16px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; cursor: pointer; min-height: 220px; color: #8e8e93;
    transition: all .15s;
}
.md-card-new:hover { border-color: #2471a3; color: #2471a3; background: #f0f6fc; }
.md-card-new i { font-size: 28px; }
.md-card-new span { font-size: 14px; font-weight: 600; }

/* Empty state */
.md-empty { grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #8e8e93; }
.md-empty i { font-size: 48px; display: block; margin-bottom: 14px; opacity: .35; }
.md-empty p { font-size: 15px; font-weight: 600; margin: 0 0 6px; }

/* Modal */
.md-backdrop {
    position: fixed; inset: 0; background: rgba(0,0,0,.45);
    z-index: 1000; display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity .2s;
}
.md-backdrop.show { opacity: 1; pointer-events: all; }
.md-modal {
    background: #fff; border-radius: 20px; padding: 32px;
    width: 100%; max-width: 460px; box-shadow: 0 20px 60px rgba(0,0,0,.2);
    transform: scale(.95) translateY(10px); transition: transform .2s;
}
.md-backdrop.show .md-modal { transform: scale(1) translateY(0); }
.md-modal h2 { margin: 0 0 22px; font-size: 19px; font-weight: 800; color: #1c1c1e; }
.md-field { margin-bottom: 16px; }
.md-field label { display: block; font-size: 12px; font-weight: 600; color: #8e8e93; margin-bottom: 6px; letter-spacing: .3px; text-transform: uppercase; }
.md-field input, .md-field textarea {
    width: 100%; box-sizing: border-box;
    border: 1.5px solid #e5e5ea; border-radius: 10px;
    padding: 10px 14px; font-size: 14px; color: #1c1c1e; outline: none;
    transition: border-color .15s; font-family: inherit;
}
.md-field input:focus, .md-field textarea:focus { border-color: #2471a3; }
.md-field textarea { resize: vertical; min-height: 80px; }
.md-toggle-row { display: flex; align-items: center; justify-content: space-between; }
.md-toggle-row label { font-size: 14px; font-weight: 600; color: #1c1c1e; text-transform: none; letter-spacing: 0; }
.md-switch { position: relative; width: 44px; height: 26px; }
.md-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.md-switch-slider {
    position: absolute; inset: 0; background: #dde1e7;
    border-radius: 13px; cursor: pointer; transition: background .2s;
}
.md-switch-slider::after {
    content: ''; position: absolute; top: 3px; left: 3px;
    width: 20px; height: 20px; background: #fff; border-radius: 50%;
    box-shadow: 0 1px 4px rgba(0,0,0,.2); transition: transform .2s;
}
.md-switch input:checked + .md-switch-slider { background: #2471a3; }
.md-switch input:checked + .md-switch-slider::after { transform: translateX(18px); }
.md-modal-actions { display: flex; gap: 10px; margin-top: 24px; }
.md-btn-save {
    flex: 1; background: #1a3a6e; color: #fff; border: none; border-radius: 10px;
    padding: 12px; font-size: 14px; font-weight: 700; cursor: pointer;
    transition: background .15s;
}
.md-btn-save:hover { background: #2471a3; }
.md-btn-cancel {
    background: #f2f2f7; color: #636366; border: none; border-radius: 10px;
    padding: 12px 20px; font-size: 14px; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.md-btn-cancel:hover { background: #e5e5ea; }

/* QR Modal */
.md-qr-box { text-align: center; }
.md-qr-box #qrCanvas { border-radius: 12px; margin: 16px 0; }
.md-qr-url {
    font-size: 11px; color: #8e8e93; background: #f2f2f7; border-radius: 8px;
    padding: 8px 12px; word-break: break-all; font-family: monospace;
    border: 1px solid #e5e5ea; margin-bottom: 16px; display: block;
}
.md-btn-copy {
    background: #e8f4fd; color: #2471a3; border: none; border-radius: 10px;
    padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px; margin-right: 8px;
    transition: background .15s;
}
.md-btn-copy:hover { background: #d0e8f8; }
.md-btn-dl {
    background: #f0fdf4; color: #16a34a; border: none; border-radius: 10px;
    padding: 10px 20px; font-size: 13px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: background .15s;
}
.md-btn-dl:hover { background: #dcfce7; }
</style>

<div class="md-page">

    <!-- Cabecera -->
    <div class="md-header">
        <div class="md-header-icon"><i class="fas fa-qrcode"></i></div>
        <div class="md-header-text">
            <h1>Menú Digital</h1>
            <p>Genera menús en línea con código QR para tus clientes</p>
        </div>
        <button class="md-header-btn" onclick="abrirModal()">
            <i class="fas fa-plus"></i> Nuevo Menú
        </button>
    </div>

    <!-- Grid de menús -->
    <div class="md-grid" id="mdGrid">

        <!-- Card "Nuevo" siempre primero -->
        <div class="md-card-new" onclick="abrirModal()">
            <i class="fas fa-plus"></i>
            <span>Nuevo Menú</span>
        </div>

        <?php if (empty($menus)): ?>
        <?php else: ?>
        <?php foreach ($menus as $m):
            $urlMenu = rtrim($baseUrl, '/') . '/menu/' . $m['token'];
        ?>
        <div class="md-card" id="mdCard-<?php echo $m['id']; ?>">
            <div class="md-card-top">
                <div class="md-card-icon"><i class="fas fa-utensils"></i></div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="md-card-badge <?php echo $m['activo'] ? 'on' : 'off'; ?>">
                        <?php echo $m['activo'] ? 'Activo' : 'Inactivo'; ?>
                    </span>
                    <button class="md-btn-gear" title="Configurar nombre y mesa"
                            onclick="abrirConfig(<?php echo $m['id']; ?>, <?php echo htmlspecialchars(json_encode($m['nombre'])); ?>, <?php echo (int)($m['mesa_id'] ?? 0); ?>)">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
            <div class="md-card-body">
                <div class="md-card-name"><?php echo htmlspecialchars($m['nombre']); ?></div>
                <?php if ($m['descripcion']): ?>
                <div class="md-card-desc"><?php echo htmlspecialchars($m['descripcion']); ?></div>
                <?php endif; ?>
                <?php if (!empty($m['mesa_label'])): ?>
                <div class="md-mesa-chip asignada">
                    <i class="fas fa-chair"></i> <?php echo htmlspecialchars($m['mesa_label']); ?>
                </div>
                <?php else: ?>
                <div class="md-mesa-chip libre">
                    <i class="fas fa-ban"></i> Sin mesa — solo consulta
                </div>
                <?php endif; ?>
                <div class="md-card-url"><?php echo $urlMenu; ?></div>
            </div>
            <div class="md-card-actions">
                <a class="md-btn md-btn-build"
                   href="<?php echo $basePath; ?>/menu-digital/construir/<?php echo $m['id']; ?>"
                   style="text-decoration:none;">
                    <i class="fas fa-hammer"></i> Construir
                </a>
                <button class="md-btn md-btn-qr" title="Ver código QR"
                        onclick="verQR(<?php echo htmlspecialchars(json_encode($urlMenu)); ?>, <?php echo htmlspecialchars(json_encode($m['nombre'])); ?>)">
                    <i class="fas fa-qrcode"></i>
                </button>
                <button class="md-btn md-btn-toggle" title="<?php echo $m['activo'] ? 'Desactivar' : 'Activar'; ?>"
                        onclick="toggleMenu(<?php echo $m['id']; ?>, this)">
                    <i class="fas fa-power-off"></i>
                </button>
                <button class="md-btn md-btn-dup" title="Duplicar menú"
                        onclick="duplicarMenu(<?php echo $m['id']; ?>)">
                    <i class="fas fa-copy"></i>
                </button>
                <button class="md-btn md-btn-delete" title="Eliminar"
                        onclick="eliminarMenu(<?php echo $m['id']; ?>)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div><!-- /mdGrid -->

</div><!-- /md-page -->

<!-- Modal configurar (nombre + mesa) -->
<div class="md-backdrop" id="configBackdrop">
    <div class="md-modal">
        <h2>Configurar menú</h2>
        <input type="hidden" id="cfgId">
        <div class="md-field">
            <label>Nombre del menú</label>
            <input type="text" id="cfgNombre" maxlength="150" placeholder="Ej: Menú principal…">
        </div>
        <div class="md-field">
            <label>Mesa asignada</label>
            <select id="cfgMesa" style="width:100%;box-sizing:border-box;border:1.5px solid #e5e5ea;border-radius:10px;padding:10px 14px;font-size:14px;color:#1c1c1e;outline:none;font-family:inherit;background:#fff;cursor:pointer;">
                <option value="">Ninguna — solo consulta</option>
                <?php foreach ($mesas as $mesa): ?>
                <option value="<?php echo $mesa['id']; ?>">
                    [<?php echo $mesa['numero']; ?>] <?php echo htmlspecialchars($mesa['nombre']); ?>
                    <?php echo $mesa['zona'] ? ' — ' . htmlspecialchars($mesa['zona']) : ''; ?>
                </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size:11px;color:#8e8e93;margin:6px 0 0;">
                <i class="fas fa-info-circle"></i>
                Si asignas una mesa, el cliente podrá hacer pedidos desde el link del menú y se sincronizarán con el salón.
            </p>
        </div>
        <div class="md-modal-actions">
            <button class="md-btn-save" onclick="guardarConfig()"><i class="fas fa-check"></i> Guardar</button>
            <button class="md-btn-cancel" onclick="cerrarConfig()">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal crear/editar -->
<div class="md-backdrop" id="modalBackdrop">
    <div class="md-modal">
        <h2 id="modalTitle">Nuevo Menú</h2>
        <input type="hidden" id="mdId" value="">
        <div class="md-field">
            <label>Nombre</label>
            <input type="text" id="mdNombre" placeholder="Ej: Menú verano, Carta de vinos…" maxlength="150">
        </div>
        <div class="md-field">
            <label>Descripción <span style="font-weight:400;text-transform:none;">(opcional)</span></label>
            <textarea id="mdDesc" placeholder="Breve descripción del menú…"></textarea>
        </div>
        <div class="md-field">
            <div class="md-toggle-row">
                <label>Menú activo</label>
                <label class="md-switch">
                    <input type="checkbox" id="mdActivo" checked>
                    <span class="md-switch-slider"></span>
                </label>
            </div>
        </div>
        <div class="md-modal-actions">
            <button class="md-btn-save" onclick="guardarMenu()"><i class="fas fa-check"></i> Guardar</button>
            <button class="md-btn-cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<!-- Modal QR -->
<div class="md-backdrop" id="qrBackdrop">
    <div class="md-modal" style="max-width:360px;">
        <h2 id="qrTitle" style="margin-bottom:4px;">Código QR</h2>
        <p style="font-size:13px;color:#8e8e93;margin:0 0 16px;" id="qrSubtitle"></p>
        <div class="md-qr-box">
            <canvas id="qrCanvas"></canvas>
            <span class="md-qr-url" id="qrUrlLabel"></span>
            <div>
                <button class="md-btn-copy" onclick="copiarLink()"><i class="fas fa-copy"></i> Copiar link</button>
                <button class="md-btn-dl" onclick="descargarQR()"><i class="fas fa-download"></i> Descargar</button>
            </div>
        </div>
        <div class="md-modal-actions" style="margin-top:20px;">
            <button class="md-btn-cancel" style="flex:1;" onclick="cerrarQR()">Cerrar</button>
        </div>
    </div>
</div>

<!-- QRCode.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const basePath = '<?php echo $basePath; ?>';
let qrInstance = null;
let currentQrUrl = '';

/* ── Modal crear/editar ── */
function abrirModal(id = '', nombre = '', desc = '', activo = 1) {
    document.getElementById('mdId').value      = id;
    document.getElementById('mdNombre').value  = nombre;
    document.getElementById('mdDesc').value    = desc;
    document.getElementById('mdActivo').checked = !!activo;
    document.getElementById('modalTitle').textContent = id ? 'Editar Menú' : 'Nuevo Menú';
    document.getElementById('modalBackdrop').classList.add('show');
    setTimeout(() => document.getElementById('mdNombre').focus(), 150);
}
function editarMenu(id, nombre, desc, activo) { abrirModal(id, nombre, desc, activo); }
function cerrarModal() { document.getElementById('modalBackdrop').classList.remove('show'); }

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

async function guardarMenu() {
    const id     = document.getElementById('mdId').value;
    const nombre = document.getElementById('mdNombre').value.trim();
    const desc   = document.getElementById('mdDesc').value.trim();
    const activo = document.getElementById('mdActivo').checked ? 1 : 0;

    if (!nombre) {
        document.getElementById('mdNombre').focus();
        document.getElementById('mdNombre').style.borderColor = '#dc2626';
        setTimeout(() => document.getElementById('mdNombre').style.borderColor = '', 1500);
        return;
    }

    const r = await fetch(basePath + '/menu-digital/guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id ? +id : 0, nombre, descripcion: desc, activo }),
    }).then(r => r.json());

    if (r.success) { cerrarModal(); location.reload(); }
    else alert(r.message || 'Error al guardar');
}

/* ── Eliminar ── */
async function eliminarMenu(id) {
    const { value: texto } = await Swal.fire({
        title: 'Eliminar menú',
        html: 'Esta acción <strong>no se puede deshacer</strong>.<br>Escribe <strong>eliminar</strong> para confirmar.',
        input: 'text',
        inputPlaceholder: 'eliminar',
        inputAttributes: { autocomplete: 'off', spellcheck: 'false' },
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        icon: 'warning',
        preConfirm: (val) => {
            if (val.trim().toLowerCase() !== 'eliminar') {
                Swal.showValidationMessage('Debes escribir exactamente <b>eliminar</b>');
                return false;
            }
            return true;
        },
    });

    if (!texto) return;

    const r = await fetch(basePath + '/menu-digital/eliminar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    }).then(r => r.json());

    if (r.success) {
        document.getElementById('mdCard-' + id)?.remove();
        Swal.fire({ title: 'Eliminado', icon: 'success', timer: 1500, showConfirmButton: false });
    } else {
        Swal.fire({ title: 'Error', text: 'No se pudo eliminar el menú.', icon: 'error' });
    }
}

/* ── Configurar (nombre + mesa) ── */
function abrirConfig(id, nombre, mesaId) {
    document.getElementById('cfgId').value     = id;
    document.getElementById('cfgNombre').value = nombre;
    document.getElementById('cfgMesa').value   = mesaId || '';
    document.getElementById('configBackdrop').classList.add('show');
    setTimeout(() => document.getElementById('cfgNombre').focus(), 150);
}
function cerrarConfig() { document.getElementById('configBackdrop').classList.remove('show'); }
document.getElementById('configBackdrop').addEventListener('click', function(e) {
    if (e.target === this) cerrarConfig();
});
async function guardarConfig() {
    const id     = +document.getElementById('cfgId').value;
    const nombre = document.getElementById('cfgNombre').value.trim();
    const mesaId = document.getElementById('cfgMesa').value;
    if (!nombre) {
        document.getElementById('cfgNombre').style.borderColor = '#dc2626';
        setTimeout(() => document.getElementById('cfgNombre').style.borderColor = '', 1500);
        return;
    }
    const r = await fetch(basePath + '/menu-digital/configurar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, nombre, mesa_id: mesaId !== '' ? +mesaId : '' }),
    }).then(r => r.json());
    if (r.success) { cerrarConfig(); location.reload(); }
    else alert('Error al guardar la configuración');
}

/* ── Duplicar ── */
async function duplicarMenu(id) {
    const r = await fetch(basePath + '/menu-digital/duplicar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    }).then(r => r.json());
    if (r.success) {
        Swal.fire({ title: '¡Duplicado!', text: 'El menú fue copiado exitosamente.', icon: 'success', timer: 1600, showConfirmButton: false })
            .then(() => location.reload());
    } else {
        Swal.fire({ title: 'Error', text: 'No se pudo duplicar el menú.', icon: 'error' });
    }
}

/* ── Toggle ── */
async function toggleMenu(id, btn) {
    const r = await fetch(basePath + '/menu-digital/toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
    }).then(r => r.json());
    if (r.success) location.reload();
    else alert('Error al cambiar estado');
}

/* ── QR ── */
function verQR(url, nombre) {
    currentQrUrl = url;
    document.getElementById('qrTitle').textContent    = nombre;
    document.getElementById('qrSubtitle').textContent = 'Escanea el código para ver el menú';
    document.getElementById('qrUrlLabel').textContent = url;

    const canvas = document.getElementById('qrCanvas');
    const ctx    = canvas.getContext('2d');
    canvas.width = 220; canvas.height = 220;
    ctx.clearRect(0, 0, 220, 220);

    if (qrInstance) { qrInstance.clear(); qrInstance = null; }

    const tmpDiv = document.createElement('div');
    qrInstance = new QRCode(tmpDiv, {
        text: url, width: 220, height: 220,
        colorDark: '#1a3a6e', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H,
    });
    setTimeout(() => {
        const img = tmpDiv.querySelector('img') || tmpDiv.querySelector('canvas');
        if (img) {
            const src = img.tagName === 'CANVAS' ? img.toDataURL() : img.src;
            const image = new Image();
            image.onload = () => ctx.drawImage(image, 0, 0, 220, 220);
            image.src = src;
        }
    }, 100);

    document.getElementById('qrBackdrop').classList.add('show');
}

function cerrarQR() { document.getElementById('qrBackdrop').classList.remove('show'); }
document.getElementById('qrBackdrop').addEventListener('click', function(e) {
    if (e.target === this) cerrarQR();
});

function copiarLink() {
    navigator.clipboard.writeText(currentQrUrl).then(() => {
        const btn = document.querySelector('.md-btn-copy');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
        setTimeout(() => btn.innerHTML = orig, 1800);
    });
}

function descargarQR() {
    const canvas = document.getElementById('qrCanvas');
    const a = document.createElement('a');
    a.download = 'menu-qr.png';
    a.href = canvas.toDataURL('image/png');
    a.click();
}

/* ── Enter en modal ── */
document.getElementById('mdNombre').addEventListener('keydown', e => {
    if (e.key === 'Enter') guardarMenu();
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
