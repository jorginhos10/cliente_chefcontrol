<?php
// vista/configuraciones/domicilios.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Links de Domicilio - CHEFCONTROL';
$paginaActual = 'configuraciones';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';

$catLabels = [
    'entrada'      => 'Entradas',
    'plato_fuerte' => 'Platos fuertes',
    'postre'       => 'Postres',
    'bebida'       => 'Bebidas',
    'snack'        => 'Snacks',
    'otro'         => 'Otros',
];
$catColors = [
    'entrada'      => '#e67e22',
    'plato_fuerte' => '#e74c3c',
    'postre'       => '#9b59b6',
    'bebida'       => '#3498db',
    'snack'        => '#27ae60',
    'otro'         => '#7f8c8d',
];
?>

<div class="dom-wrap">

    <!-- Encabezado -->
    <div class="dom-header">
        <div class="dom-header-left">
            <a href="<?php echo $basePath; ?>/configuraciones" class="dom-breadcrumb">
                <i class="fas fa-arrow-left"></i> Configuraciones
            </a>
            <h1><i class="fas fa-motorcycle"></i> Domicilios</h1>
            <p>Gestiona los links de pedido y canales de venta a domicilio</p>
        </div>
        <button class="dom-btn-new" id="btnNuevoLink">
            <i class="fas fa-plus"></i> Nuevo Link
        </button>
    </div>

    <!-- Grid de links -->
    <div class="dom-section-title"><i class="fas fa-link"></i> Links de pedido</div>
    <div class="dom-links-grid" id="domLinksGrid">
        <?php if (empty($links)): ?>
        <div class="dom-empty-links">
            <i class="fas fa-link"></i>
            <p>Aún no tienes links. Crea uno para comenzar.</p>
        </div>
        <?php else: ?>
        <?php foreach ($links as $lk): ?>
        <?php
            $url = rtrim($baseUrl, '/') . '/pedido/' . $lk['token'];
            $catActivasLink = null;
            if (!empty($lk['categorias_activas'])) {
                $catActivasLink = json_decode($lk['categorias_activas'], true) ?: null;
            }
        ?>
        <div class="dom-link-card <?php echo $lk['activo'] ? '' : 'dom-link-inactive'; ?>" id="lcard-<?php echo $lk['id']; ?>">
            <div class="dom-link-head">
                <div class="dom-link-icon"><i class="fas fa-store"></i></div>
                <div class="dom-link-info">
                    <div class="dom-link-name"><?php echo htmlspecialchars($lk['nombre']); ?></div>
                    <?php if ($lk['descripcion']): ?>
                    <div class="dom-link-desc"><?php echo htmlspecialchars($lk['descripcion']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="dom-link-status <?php echo $lk['activo'] ? 'active' : 'inactive'; ?>">
                    <?php echo $lk['activo'] ? 'Activo' : 'Inactivo'; ?>
                </div>
            </div>

            <div class="dom-link-stats">
                <div class="dom-link-stat" style="color:#e67e22">
                    <i class="fas fa-clock"></i>
                    <strong><?php echo $lk['pendientes']; ?></strong> pendientes
                </div>
                <div class="dom-link-stat" style="color:#2980b9">
                    <i class="fas fa-fire"></i>
                    <strong><?php echo $lk['activos']; ?></strong> activos
                </div>
            </div>

            <div class="dom-link-url"><?php echo $url; ?></div>

            <div class="dom-link-config">
                <!-- 1. Ver sin stock -->
                <div class="dom-lc-item">
                    <i class="fas fa-eye" style="color:#8e44ad"></i>
                    <span class="dom-lc-label">Ver productos sin stock</span>
                    <label class="dom-toggle" title="Mostrar productos agotados en la tienda">
                        <input type="checkbox" <?php echo $lk['mostrar_sin_stock'] ? 'checked' : ''; ?>
                               onchange="guardarConfig(<?php echo $lk['id']; ?>, {mostrar_sin_stock: this.checked ? 1 : 0})">
                        <span class="dom-tslider"></span>
                    </label>
                </div>
                <!-- 2. Horario -->
                <div class="dom-lc-item">
                    <i class="fas fa-clock" style="color:#2980b9"></i>
                    <span class="dom-lc-label">Horario</span>
                    <div class="dom-lc-horario">
                        <input type="time" class="dom-time-in" id="hd-<?php echo $lk['id']; ?>"
                               value="<?php echo htmlspecialchars($lk['horario_desde'] ?? ''); ?>"
                               onchange="guardarHorario(<?php echo $lk['id']; ?>)"
                               placeholder="Desde">
                        <span class="dom-time-sep">–</span>
                        <input type="time" class="dom-time-in" id="hh-<?php echo $lk['id']; ?>"
                               value="<?php echo htmlspecialchars($lk['horario_hasta'] ?? ''); ?>"
                               onchange="guardarHorario(<?php echo $lk['id']; ?>)"
                               placeholder="Hasta">
                        <?php if ($lk['horario_desde'] || $lk['horario_hasta']): ?>
                        <button class="dom-time-clear" title="Sin horario (siempre abierto)"
                                onclick="limpiarHorario(<?php echo $lk['id']; ?>)">
                            <i class="fas fa-xmark"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- 3. Categorías -->
                <?php if (!empty($catRecetas)): ?>
                <div class="dom-lc-item dom-lc-cats">
                    <i class="fas fa-tags" style="color:#f39c12"></i>
                    <span class="dom-lc-label" style="white-space:nowrap">Categorías</span>
                    <div class="dom-cat-chips" id="chips-<?php echo $lk['id']; ?>">
                        <?php foreach ($catRecetas as $cat):
                            $isOn = ($catActivasLink === null) || in_array($cat, $catActivasLink, true);
                            $col  = $catColors[$cat] ?? '#7f8c8d';
                        ?>
                        <button class="dom-cat-chip <?php echo $isOn ? 'on' : ''; ?>"
                                data-cat="<?php echo $cat; ?>"
                                data-color="<?php echo $col; ?>"
                                style="<?php echo $isOn ? "color:$col" : ''; ?>"
                                onclick="toggleCat(<?php echo $lk['id']; ?>, this)">
                            <?php echo $catLabels[$cat] ?? ucfirst($cat); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="dom-link-actions">
                <button class="dom-link-btn dom-btn-share"
                        onclick="copiarLink('<?php echo htmlspecialchars($url); ?>', this)"
                        title="Copiar link">
                    <i class="fas fa-share-nodes"></i> Compartir
                </button>
                <button class="dom-link-btn dom-btn-toggle" data-id="<?php echo $lk['id']; ?>"
                        onclick="toggleLink(<?php echo $lk['id']; ?>, this)">
                    <i class="fas fa-<?php echo $lk['activo'] ? 'toggle-on' : 'toggle-off'; ?>"></i>
                    <?php echo $lk['activo'] ? 'Activo' : 'Inactivo'; ?>
                </button>
                <button class="dom-link-btn dom-btn-edit" title="Editar"
                        onclick="abrirEditar(<?php echo $lk['id']; ?>, <?php echo htmlspecialchars(json_encode($lk['nombre'])); ?>, <?php echo htmlspecialchars(json_encode($lk['descripcion'] ?? '')); ?>)">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="dom-link-btn dom-btn-delete" onclick="eliminarLink(<?php echo $lk['id']; ?>, this)"
                        title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Nuevo Link -->
<div class="dom-modal-bg" id="modalNuevoLink">
    <div class="dom-modal">
        <div class="dom-modal-head">
            <h3><i class="fas fa-link"></i> Nuevo Link de Pedido</h3>
            <button class="dom-modal-close" onclick="cerrarModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dom-modal-body">
            <label>Nombre del canal <span style="color:#e74c3c">*</span></label>
            <input type="text" id="nlNombre" placeholder="Ej: Menú Principal, Zona Norte…" maxlength="100">
            <label style="margin-top:12px">Descripción <span style="color:#95a5a6;font-size:12px">(opcional)</span></label>
            <input type="text" id="nlDesc" placeholder="Ej: Domicilios zona centro" maxlength="255">
        </div>
        <div class="dom-modal-foot">
            <button class="dom-btn-cancel" onclick="cerrarModal()">Cancelar</button>
            <button class="dom-btn-save" id="btnGuardarLink" onclick="guardarLink()">
                <i class="fas fa-save"></i> Crear link
            </button>
        </div>
    </div>
</div>

<!-- Modal: Editar Link -->
<div class="dom-modal-bg" id="modalEditarLink">
    <div class="dom-modal">
        <div class="dom-modal-head">
            <h3><i class="fas fa-pen"></i> Editar Link</h3>
            <button class="dom-modal-close" onclick="cerrarEditar()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dom-modal-body">
            <input type="hidden" id="elId">
            <label>Nombre del canal <span style="color:#e74c3c">*</span></label>
            <input type="text" id="elNombre" placeholder="Ej: Menú Principal, Zona Norte…" maxlength="100">
            <label style="margin-top:12px">Descripción <span style="color:#95a5a6;font-size:12px">(opcional)</span></label>
            <input type="text" id="elDesc" placeholder="Ej: Domicilios zona centro" maxlength="255">
        </div>
        <div class="dom-modal-foot">
            <button class="dom-btn-cancel" onclick="cerrarEditar()">Cancelar</button>
            <button class="dom-btn-save" id="btnGuardarEdicion" onclick="guardarEdicionLink()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Compartir -->
<div class="dom-modal-bg" id="modalShare">
    <div class="dom-modal dom-modal-sm">
        <div class="dom-modal-head">
            <h3><i class="fas fa-share-nodes"></i> Compartir Link</h3>
            <button class="dom-modal-close" onclick="cerrarModalShare()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dom-modal-body">
            <p style="font-size:13px;color:#7f8c8d;margin-bottom:12px">
                Comparte este enlace con tus clientes para que puedan hacer pedidos a domicilio:
            </p>
            <div class="dom-share-url-box">
                <span id="shareUrlText"></span>
                <button onclick="copiarShareUrl()"><i class="fas fa-copy"></i></button>
            </div>
            <div class="dom-share-actions">
                <a id="shareWa" href="#" target="_blank" class="dom-share-wa">
                    <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.dom-wrap { padding:28px; background:#f0f2f5; min-height:calc(100vh - 70px); display:flex; flex-direction:column; gap:20px; }

/* Breadcrumb */
.dom-breadcrumb { color:#95a5a6; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:5px; margin-bottom:4px; transition:.15s; }
.dom-breadcrumb:hover { color:#2c3e50; }

/* Header */
.dom-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; }
.dom-header h1 { font-size:24px; font-weight:800; color:#2c3e50; margin:4px 0 0; display:flex; align-items:center; gap:10px; }
.dom-header p  { font-size:13px; color:#95a5a6; margin:4px 0 0; }
.dom-btn-new { background:#2c3e50; color:#fff; border:none; border-radius:10px; padding:10px 20px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.15s; margin-top:4px; }
.dom-btn-new:hover { background:#1a252f; }

/* Sección títulos */
.dom-section-title { font-size:14px; font-weight:800; color:#2c3e50; display:flex; align-items:center; gap:8px; }
.dom-section-title::before { content:''; display:block; width:4px; height:16px; background:#2c3e50; border-radius:2px; }

/* Links grid */
.dom-links-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
.dom-empty-links { grid-column:1/-1; text-align:center; padding:40px; color:#b2bec3; }
.dom-empty-links i { font-size:36px; margin-bottom:12px; display:block; }

.dom-link-card { background:#fff; border-radius:14px; border:2px solid #e8ecf0; padding:18px; display:flex; flex-direction:column; gap:12px; transition:.2s; }
.dom-link-card:hover { border-color:#bdc3c7; box-shadow:0 4px 16px rgba(0,0,0,.08); }
.dom-link-inactive { opacity:.6; }

.dom-link-head { display:flex; align-items:flex-start; gap:12px; }
.dom-link-icon { width:42px; height:42px; background:#eaf4fb; color:#2980b9; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.dom-link-info { flex:1; min-width:0; }
.dom-link-name { font-size:15px; font-weight:700; color:#2c3e50; }
.dom-link-desc { font-size:12px; color:#95a5a6; }
.dom-link-status { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; white-space:nowrap; flex-shrink:0; }
.dom-link-status.active   { background:#eafaf1; color:#27ae60; }
.dom-link-status.inactive { background:#fdf2f2; color:#e74c3c; }

.dom-link-stats { display:flex; gap:16px; }
.dom-link-stat  { font-size:12px; display:flex; align-items:center; gap:5px; }

.dom-link-url { font-size:11px; color:#95a5a6; background:#f8f9fa; border-radius:6px; padding:6px 10px; word-break:break-all; font-family:monospace; }

.dom-link-actions { display:flex; gap:8px; flex-wrap:wrap; }
.dom-link-btn { flex:1; min-width:0; border:1px solid #e8ecf0; background:#fff; border-radius:8px; padding:8px 10px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px; transition:.15s; }
.dom-btn-share  { color:#2980b9; border-color:#2980b9; }
.dom-btn-share:hover { background:#eaf4fb; }
.dom-btn-toggle { color:#27ae60; border-color:#27ae60; }
.dom-btn-toggle:hover { background:#eafaf1; }
.dom-btn-edit   { color:#f39c12; border-color:#f39c12; max-width:42px; }
.dom-btn-edit:hover   { background:#fef9e7; }
.dom-btn-delete { color:#e74c3c; border-color:#e74c3c; max-width:42px; }
.dom-btn-delete:hover { background:#fdf2f2; }

/* Configuración inline del link */
.dom-link-config { border-top:1px solid #f0f2f5; margin-top:4px; padding-top:12px; display:flex; flex-direction:column; gap:10px; }
.dom-lc-item { display:flex; align-items:center; gap:10px; font-size:13px; color:#636e72; }
.dom-lc-item > i { width:16px; text-align:center; flex-shrink:0; font-size:13px; }
.dom-lc-label { flex:1; font-weight:600; color:#2c3e50; font-size:12px; }
.dom-toggle { position:relative; display:inline-block; width:36px; height:20px; flex-shrink:0; cursor:pointer; }
.dom-toggle input { opacity:0; width:0; height:0; position:absolute; }
.dom-tslider { position:absolute; inset:0; background:#b2bec3; border-radius:20px; transition:.2s; }
.dom-tslider::before { content:''; position:absolute; height:14px; width:14px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.2s; }
.dom-toggle input:checked + .dom-tslider { background:#27ae60; }
.dom-toggle input:checked + .dom-tslider::before { transform:translateX(16px); }
.dom-lc-horario { display:flex; align-items:center; gap:6px; }
.dom-time-in { border:1.5px solid #e8ecf0; border-radius:8px; padding:4px 8px; font-size:12px; color:#2c3e50; outline:none; width:80px; font-family:inherit; background:#fff; }
.dom-time-in:focus { border-color:#2980b9; }
.dom-time-sep { color:#b2bec3; font-size:12px; }
.dom-time-clear { background:none; border:none; color:#b2bec3; cursor:pointer; font-size:12px; padding:2px 4px; border-radius:4px; }
.dom-time-clear:hover { color:#e74c3c; background:#fdf2f2; }
.dom-lc-cats { flex-wrap:wrap; align-items:flex-start; }
.dom-lc-cats > i { margin-top:3px; }
.dom-cat-chips { display:flex; flex-wrap:wrap; gap:5px; flex:1; }
.dom-cat-chip { border:1.5px solid #e8ecf0; background:#f8f9fa; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:700; color:#95a5a6; cursor:pointer; transition:.15s; }
.dom-cat-chip.on { border-color:currentColor; background:#fff; }
.dom-cat-chip:hover { opacity:.8; }

/* Modales */
.dom-modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9000; align-items:center; justify-content:center; padding:20px; }
.dom-modal-bg.show { display:flex; }
.dom-modal { background:#fff; border-radius:16px; width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.dom-modal-sm { max-width:420px; }
.dom-modal-head { display:flex; justify-content:space-between; align-items:center; padding:18px 20px; border-bottom:1px solid #e8ecf0; }
.dom-modal-head h3 { margin:0; font-size:16px; font-weight:800; color:#2c3e50; display:flex; align-items:center; gap:8px; }
.dom-modal-close { background:none; border:none; cursor:pointer; font-size:18px; color:#95a5a6; }
.dom-modal-body { padding:20px; display:flex; flex-direction:column; gap:6px; }
.dom-modal-body label { font-size:12px; font-weight:700; color:#636e72; }
.dom-modal-body input { border:1px solid #e8ecf0; border-radius:8px; padding:10px 12px; font-size:14px; color:#2c3e50; outline:none; width:100%; box-sizing:border-box; }
.dom-modal-body input:focus { border-color:#2980b9; }
.dom-modal-foot { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #e8ecf0; }
.dom-btn-cancel { background:#f0f2f5; color:#636e72; border:none; border-radius:8px; padding:10px 18px; font-size:14px; font-weight:700; cursor:pointer; }
.dom-btn-save   { background:#2c3e50; color:#fff; border:none; border-radius:8px; padding:10px 18px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:7px; }
.dom-btn-save:hover { background:#1a252f; }

/* Share */
.dom-share-url-box { display:flex; align-items:center; gap:8px; background:#f8f9fa; border-radius:8px; padding:10px 12px; word-break:break-all; }
.dom-share-url-box span { flex:1; font-size:12px; color:#2c3e50; font-family:monospace; }
.dom-share-url-box button { background:#2980b9; color:#fff; border:none; border-radius:6px; padding:6px 10px; cursor:pointer; flex-shrink:0; }
.dom-share-actions { margin-top:14px; display:flex; gap:10px; }
.dom-share-wa { flex:1; background:#25d366; color:#fff; border-radius:10px; padding:11px; text-align:center; font-size:14px; font-weight:700; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:7px; }
</style>

<script>
const BASE     = '<?php echo $basePath; ?>';
const BASE_URL = '<?php echo $baseUrl; ?>';

// ── Nuevo link ────────────────────────────────────────────────────────────────
document.getElementById('btnNuevoLink').addEventListener('click', () => {
    document.getElementById('nlNombre').value = '';
    document.getElementById('nlDesc').value   = '';
    document.getElementById('modalNuevoLink').classList.add('show');
    document.getElementById('nlNombre').focus();
});

function cerrarModal() {
    document.getElementById('modalNuevoLink').classList.remove('show');
}

async function guardarLink() {
    const nombre = document.getElementById('nlNombre').value.trim();
    if (!nombre) { document.getElementById('nlNombre').focus(); return; }

    const btn = document.getElementById('btnGuardarLink');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando…';

    try {
        const r = await fetch(BASE + '/domicilios/crear', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, descripcion: document.getElementById('nlDesc').value.trim() }),
        });
        const d = await r.json();
        if (d.success) {
            cerrarModal();
            location.reload();
        } else {
            alert(d.message || 'Error al crear');
        }
    } catch { alert('Error de red'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Crear link';
    }
}

// ── Editar link ───────────────────────────────────────────────────────────────
function abrirEditar(id, nombre, desc) {
    document.getElementById('elId').value     = id;
    document.getElementById('elNombre').value = nombre;
    document.getElementById('elDesc').value   = desc;
    document.getElementById('modalEditarLink').classList.add('show');
    setTimeout(() => document.getElementById('elNombre').focus(), 150);
}
function cerrarEditar() {
    document.getElementById('modalEditarLink').classList.remove('show');
}
async function guardarEdicionLink() {
    const id     = parseInt(document.getElementById('elId').value);
    const nombre = document.getElementById('elNombre').value.trim();
    const desc   = document.getElementById('elDesc').value.trim();
    if (!nombre) { document.getElementById('elNombre').focus(); return; }

    const btn = document.getElementById('btnGuardarEdicion');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    try {
        const r = await fetch(BASE + '/domicilios/editar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, descripcion: desc }),
        });
        const d = await r.json();
        if (d.success) {
            const card = document.getElementById('lcard-' + id);
            if (card) {
                card.querySelector('.dom-link-name').textContent = nombre;
                const descEl = card.querySelector('.dom-link-desc');
                if (desc) {
                    if (descEl) descEl.textContent = desc;
                    else {
                        const newDesc = document.createElement('div');
                        newDesc.className = 'dom-link-desc';
                        newDesc.textContent = desc;
                        card.querySelector('.dom-link-name').insertAdjacentElement('afterend', newDesc);
                    }
                } else if (descEl) {
                    descEl.remove();
                }
            }
            cerrarEditar();
        } else {
            alert(d.message || 'Error al guardar');
        }
    } catch { alert('Error de red'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Guardar';
    }
}

// ── Config link ───────────────────────────────────────────────────────────────
async function guardarConfig(id, campos) {
    await fetch(BASE + '/domicilios/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, ...campos }),
    });
}

async function guardarHorario(id) {
    const desde = document.getElementById('hd-' + id)?.value || null;
    const hasta = document.getElementById('hh-' + id)?.value || null;
    await guardarConfig(id, { horario_desde: desde, horario_hasta: hasta });
    const card = document.getElementById('lcard-' + id);
    let clearBtn = card.querySelector('.dom-time-clear');
    if ((desde || hasta) && !clearBtn) {
        clearBtn = document.createElement('button');
        clearBtn.className = 'dom-time-clear';
        clearBtn.title = 'Sin horario (siempre abierto)';
        clearBtn.innerHTML = '<i class="fas fa-xmark"></i>';
        clearBtn.onclick = () => limpiarHorario(id);
        card.querySelector('.dom-lc-horario').appendChild(clearBtn);
    } else if (!desde && !hasta && clearBtn) {
        clearBtn.remove();
    }
}

async function limpiarHorario(id) {
    document.getElementById('hd-' + id).value = '';
    document.getElementById('hh-' + id).value = '';
    await guardarConfig(id, { horario_desde: null, horario_hasta: null });
    document.getElementById('lcard-' + id).querySelector('.dom-time-clear')?.remove();
}

async function toggleCat(id, btn) {
    btn.classList.toggle('on');
    btn.style.color = btn.classList.contains('on') ? btn.dataset.color || '' : '';
    const chips  = document.querySelectorAll('#chips-' + id + ' .dom-cat-chip');
    const activas = [...chips].filter(c => c.classList.contains('on')).map(c => c.dataset.cat);
    await guardarConfig(id, { categorias_activas: activas.length === chips.length ? null : activas });
}

// ── Toggle / Eliminar link ────────────────────────────────────────────────────
async function toggleLink(id, btn) {
    btn.disabled = true;
    try {
        const r = await fetch(BASE + '/domicilios/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        if ((await r.json()).success) location.reload();
    } catch {}
    btn.disabled = false;
}

async function eliminarLink(id, btn) {
    if (!confirm('¿Eliminar este link? Se perderán los datos.')) return;
    btn.disabled = true;
    try {
        const r = await fetch(BASE + '/domicilios/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        if ((await r.json()).success) {
            document.getElementById('lcard-' + id)?.remove();
        }
    } catch {}
    btn.disabled = false;
}

// ── Compartir ─────────────────────────────────────────────────────────────────
let shareCurrentUrl = '';

function copiarLink(url) {
    shareCurrentUrl = url;
    document.getElementById('shareUrlText').textContent = url;
    document.getElementById('shareWa').href =
        'https://wa.me/?text=' + encodeURIComponent('¡Haz tu pedido aquí! ' + url);
    document.getElementById('modalShare').classList.add('show');
}

function cerrarModalShare() {
    document.getElementById('modalShare').classList.remove('show');
}

function copiarShareUrl() {
    navigator.clipboard.writeText(shareCurrentUrl).then(() => {
        const btn = document.querySelector('.dom-share-url-box button');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => btn.innerHTML = '<i class="fas fa-copy"></i>', 1500);
    });
}

// Cerrar modales al hacer click fuera
document.getElementById('modalNuevoLink').addEventListener('click', e => {
    if (e.target === e.currentTarget) cerrarModal();
});
document.getElementById('modalShare').addEventListener('click', e => {
    if (e.target === e.currentTarget) cerrarModalShare();
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
