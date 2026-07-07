<?php
// vista/clientes/index.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Clientes — CHEFCONTROL';
$paginaActual = 'clientes';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';

// Datos para el modal de WhatsApp
$waCupones   = [];
$waDomLinks  = [];
try {
    require_once __DIR__ . '/../../modelo/cuponModel.php';
    require_once __DIR__ . '/../../modelo/domicilioModel.php';
    $hoy = date('Y-m-d');
    $waCupones = array_values(array_filter(
        (new CuponModel())->obtenerTodos(),
        fn($c) => $c['estado'] === 'activo'
               && (!$c['expira_en'] || $c['expira_en'] >= $hoy)
               && (int)$c['usos_actual'] < (int)$c['usos_max']
    ));
    $waDomLinks = array_values(array_filter(
        (new DomicilioModel())->obtenerLinks(),
        fn($l) => (int)$l['activo'] === 1
    ));
} catch (Exception $e) {}

$total   = $estadisticas['total']   ?? 0;
$activos = $estadisticas['activos'] ?? 0;
$nuevos  = $estadisticas['nuevos']  ?? 0;

function initials(string $nombre): string {
    $parts = array_values(array_filter(explode(' ', trim($nombre))));
    $a = strtoupper(mb_substr($parts[0] ?? '?', 0, 1));
    $b = strtoupper(mb_substr($parts[1] ?? '',  0, 1));
    return $a . $b;
}

function avatarColor(int $id): string {
    $colores = ['#3498db','#2ecc71','#9b59b6','#e67e22','#1abc9c','#e74c3c','#f39c12','#16a085'];
    return $colores[$id % count($colores)];
}
?>

<style>
/* ── Layout — ocupa todo el ancho que da contentWrapper ── */
.cli-wrap  { padding: 0 0 40px; }
.cli-top   { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
.cli-title h1 { font-size: 22px; font-weight: 800; color: #2c3e50; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
.cli-title p  { margin: 0; font-size: 13px; color: #95a5a6; }
.cli-title h1 i { color: #8e44ad; }

/* Stats — 4 tarjetas siempre visibles */
.cli-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
.cli-stat  { background: #fff; border-radius: 14px; padding: 20px 22px; box-shadow: 0 2px 8px rgba(0,0,0,.06); display: flex; align-items: center; gap: 16px; }
.cli-stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.cli-stat-num  { font-size: 28px; font-weight: 800; color: #2c3e50; line-height: 1; }
.cli-stat-lbl  { font-size: 12px; color: #95a5a6; margin-top: 4px; }

/* Toolbar */
.cli-toolbar { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
.cli-search  { flex: 1; min-width: 220px; position: relative; }
.cli-search i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #b2bec3; font-size: 14px; }
.cli-search input { width: 100%; padding: 11px 14px 11px 40px; border: 1.5px solid #e8ecf0; border-radius: 10px; font-size: 14px; outline: none; font-family: inherit; transition: border-color .2s; box-sizing: border-box; }
.cli-search input:focus { border-color: #8e44ad; }
.btn-primary { background: #8e44ad; color: #fff; border: none; padding: 11px 22px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: .15s; white-space: nowrap; }
.btn-primary:hover { background: #7d3c98; transform: translateY(-1px); }

/* Table — ocupa todo el ancho disponible */
.cli-table-wrap { background: #fff; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.06); overflow: hidden; width: 100%; }
.cli-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.cli-table th { padding: 13px 16px; text-align: left; font-size: 11px; font-weight: 800; color: #95a5a6; text-transform: uppercase; letter-spacing: .6px; background: #fafbfc; border-bottom: 1px solid #f0f2f5; white-space: nowrap; }
.cli-table td { padding: 14px 16px; font-size: 14px; color: #2c3e50; border-bottom: 1px solid #f8f9fa; vertical-align: middle; }
.cli-table tr:last-child td { border-bottom: none; }
.cli-table tr:hover td { background: #fafbfc; }
/* Anchos de columnas proporcionales */
.cli-table col.col-cliente   { width: 28%; }
.cli-table col.col-email     { width: 20%; }
.cli-table col.col-dir       { width: 22%; }
.cli-table col.col-fecha     { width: 12%; }
.cli-table col.col-estado    { width: 10%; }
.cli-table col.col-acciones  { width: 11%; }

/* Avatar + name cell */
.cli-name-cell { display: flex; align-items: center; gap: 12px; }
.cli-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; color: #fff; flex-shrink: 0; }
.cli-name    { font-weight: 700; color: #2c3e50; }
.cli-phone   { font-size: 12px; color: #95a5a6; margin-top: 2px; }
.cli-doc     { font-size: 11px; color: #b2bec3; margin-top: 2px; display: flex; align-items: center; gap: 4px; }

/* Badge status */
.cli-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
.cli-badge.active   { background: #eafaf1; color: #27ae60; }
.cli-badge.inactive { background: #fdf2f8; color: #95a5a6; }

/* Toggle switch */
.toggle-switch { position: relative; display: inline-flex; align-items: center; cursor: pointer; }
.toggle-switch input { display: none; }
.toggle-track { width: 36px; height: 20px; background: #e0e0e0; border-radius: 10px; transition: .2s; }
.toggle-thumb { position: absolute; left: 3px; width: 14px; height: 14px; background: #fff; border-radius: 50%; transition: .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.toggle-switch input:checked ~ .toggle-track { background: #27ae60; }
.toggle-switch input:checked ~ .toggle-thumb { transform: translateX(16px); }

/* ── Modal WhatsApp ── */
.wa-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:3000; align-items:flex-start; justify-content:center; padding:60px 20px 20px; }
.wa-overlay.show { display:flex; }
.wa-modal { background:#fff; border-radius:18px; width:100%; max-width:440px; box-shadow:0 20px 60px rgba(0,0,0,.22); overflow:hidden; animation:slideUp .22s ease; }
.wa-modal-head { display:flex; justify-content:space-between; align-items:center; padding:18px 22px; background:#25d366; }
.wa-modal-head h2 { margin:0; font-size:16px; font-weight:800; color:#fff; display:flex; align-items:center; gap:9px; }
.wa-modal-head h2 small { font-size:12px; font-weight:500; opacity:.85; }
.wa-modal-close { background:rgba(255,255,255,.2); border:none; cursor:pointer; color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:15px; transition:.15s; }
.wa-modal-close:hover { background:rgba(255,255,255,.35); }
.wa-modal-body { padding:22px; display:flex; flex-direction:column; gap:16px; }
.wa-field label { display:block; font-size:11px; font-weight:700; color:#636e72; margin-bottom:6px; text-transform:uppercase; letter-spacing:.4px; }
.wa-field select { width:100%; border:1.5px solid #e8ecf0; border-radius:10px; padding:10px 14px; font-size:14px; color:#2c3e50; outline:none; font-family:inherit; transition:border-color .2s; background:#fff; }
.wa-field select:focus { border-color:#25d366; }
.wa-items { display:flex; flex-direction:column; gap:8px; max-height:240px; overflow-y:auto; }
.wa-item { display:flex; align-items:center; gap:12px; padding:12px 14px; border:1.5px solid #e8ecf0; border-radius:12px; cursor:pointer; transition:.15s; }
.wa-item:hover { border-color:#25d366; background:#f0fdf4; }
.wa-item.selected { border-color:#25d366; background:#f0fdf4; }
.wa-item input[type=radio] { accent-color:#25d366; width:16px; height:16px; flex-shrink:0; }
.wa-item-body { flex:1; min-width:0; }
.wa-item-title { font-size:13px; font-weight:700; color:#2c3e50; }
.wa-item-sub   { font-size:11px; color:#95a5a6; margin-top:2px; }
.wa-item-badge { font-size:11px; font-weight:700; padding:3px 9px; border-radius:20px; background:#e9faf0; color:#25d366; flex-shrink:0; }
.wa-empty { text-align:center; padding:28px 16px; color:#b2bec3; font-size:13px; }
.wa-empty i { display:block; font-size:28px; margin-bottom:8px; }
.wa-modal-foot { padding:16px 22px; border-top:1px solid #f0f2f5; display:flex; gap:10px; justify-content:flex-end; }
.wa-btn-send { background:#25d366; color:#fff; border:none; padding:10px 22px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.15s; }
.wa-btn-send:hover { background:#1ebe5d; }
.wa-btn-send:disabled { opacity:.5; cursor:default; }

/* Phone prefix input */
.phone-wrapper { display: flex; align-items: stretch; border: 1.5px solid #e8ecf0; border-radius: 10px; overflow: hidden; transition: border-color .2s; background: #fff; }
.phone-wrapper:focus-within { border-color: #8e44ad; }
.phone-prefix { padding: 11px 10px 11px 14px; font-size: 14px; color: #b2bec3; background: #f8f9fa; font-weight: 600; white-space: nowrap; user-select: none; border-right: 1.5px solid #e8ecf0; display: flex; align-items: center; }
.phone-wrapper input { border: none !important; border-radius: 0 !important; outline: none !important; padding: 11px 14px !important; flex: 1; min-width: 0; font-size: 14px; font-family: inherit; }
.phone-wrapper input:focus { border: none !important; }

/* Action buttons */
.cli-actions { display: flex; gap: 6px; }
.cli-btn { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; transition: .15s; }
.cli-btn.whatsapp { background: #e9faf0; color: #25d366; }
.cli-btn.whatsapp:hover { background: #25d366; color: #fff; }
.cli-btn.edit   { background: #eaf4fb; color: #2980b9; }
.cli-btn.edit:hover   { background: #2980b9; color: #fff; }
.cli-btn.delete { background: #fdf2f2; color: #e74c3c; }
.cli-btn.delete:hover { background: #e74c3c; color: #fff; }

/* Overflow en celdas de texto largo */
.cli-td-overflow { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 0; }
.cli-table td.cli-name-cell-td { overflow: hidden; }

/* Email también puede ser largo */
.cli-table td:nth-child(2) { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 0; }

/* Empty state */
.cli-empty { text-align: center; padding: 60px 20px; color: #b2bec3; }
.cli-empty i { font-size: 48px; display: block; margin-bottom: 14px; }
.cli-empty p { margin: 0; font-size: 14px; }

/* ── Modal ── */
.cli-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
.cli-overlay.show { display: flex; }
.cli-modal { background: #fff; border-radius: 18px; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,.2); overflow: hidden; animation: slideUp .25s ease; }
@keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.cli-modal-head { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f2f5; }
.cli-modal-head h2 { margin: 0; font-size: 17px; font-weight: 800; color: #2c3e50; display: flex; align-items: center; gap: 8px; }
.cli-modal-close { background: none; border: none; cursor: pointer; font-size: 18px; color: #95a5a6; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: .15s; }
.cli-modal-close:hover { background: #f0f2f5; color: #2c3e50; }
.cli-modal-body { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
.cli-field label { display: block; font-size: 12px; font-weight: 700; color: #636e72; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
.cli-field input,
.cli-field select,
.cli-field textarea { width: 100%; border: 1.5px solid #e8ecf0; border-radius: 10px; padding: 11px 14px; font-size: 14px; color: #2c3e50; outline: none; font-family: inherit; resize: none; transition: border-color .2s; box-sizing: border-box; background: #fff; }
.cli-field input:focus,
.cli-field select:focus,
.cli-field textarea:focus { border-color: #8e44ad; }
.cli-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.cli-modal-foot { padding: 16px 24px; border-top: 1px solid #f0f2f5; display: flex; gap: 10px; justify-content: flex-end; }
.btn-cancel { background: #f0f2f5; color: #636e72; border: none; padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: .15s; }
.btn-cancel:hover { background: #e0e0e0; }
.btn-save { background: #8e44ad; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: .15s; display: flex; align-items: center; gap: 7px; }
.btn-save:hover { background: #7d3c98; }
.btn-save:disabled { opacity: .6; cursor: default; }

/* Delete modal */
.cli-del-modal { max-width: 380px; }
.cli-del-body  { padding: 24px; text-align: center; }
.cli-del-icon  { width: 64px; height: 64px; background: #fdf2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #e74c3c; margin: 0 auto 16px; }
.cli-del-body h3 { margin: 0 0 8px; font-size: 17px; color: #2c3e50; }
.cli-del-body p  { margin: 0; font-size: 14px; color: #95a5a6; }
.cli-del-foot { padding: 16px 24px; display: flex; gap: 10px; justify-content: center; border-top: 1px solid #f0f2f5; }
.btn-del { background: #e74c3c; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: .15s; }
.btn-del:hover { background: #c0392b; }

@media (max-width: 1024px) {
    .cli-stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .cli-stats { grid-template-columns: repeat(2, 1fr); }
    .cli-field-row { grid-template-columns: 1fr; }
    /* Ocultar dirección y fecha en móvil */
    .cli-table th:nth-child(3),
    .cli-table td:nth-child(3),
    .cli-table th:nth-child(4),
    .cli-table td:nth-child(4) { display: none; }
    .cli-table col.col-cliente  { width: 40%; }
    .cli-table col.col-email    { width: 32%; }
    .cli-table col.col-estado   { width: 16%; }
    .cli-table col.col-acciones { width: 12%; }
}
@media (max-width: 480px) {
    .cli-stats { grid-template-columns: 1fr 1fr; }
    /* Ocultar también email en móvil pequeño */
    .cli-table th:nth-child(2),
    .cli-table td:nth-child(2) { display: none; }
    .cli-table col.col-cliente  { width: 55%; }
    .cli-table col.col-estado   { width: 25%; }
    .cli-table col.col-acciones { width: 20%; }
}
</style>

<div class="cli-wrap">

    <!-- Top -->
    <div class="cli-top">
        <div class="cli-title">
            <h1><i class="fas fa-user-friends"></i> Clientes</h1>
            <p>Gestiona tu base de clientes</p>
        </div>
        <button class="btn-primary" onclick="abrirModal()">
            <i class="fas fa-user-plus"></i> Nuevo cliente
        </button>
    </div>

    <!-- Stats -->
    <div class="cli-stats">
        <div class="cli-stat">
            <div class="cli-stat-icon" style="background:#f5eef8;color:#8e44ad"><i class="fas fa-users"></i></div>
            <div>
                <div class="cli-stat-num"><?php echo $total; ?></div>
                <div class="cli-stat-lbl">Total clientes</div>
            </div>
        </div>
        <div class="cli-stat">
            <div class="cli-stat-icon" style="background:#eafaf1;color:#27ae60"><i class="fas fa-user-check"></i></div>
            <div>
                <div class="cli-stat-num"><?php echo $activos; ?></div>
                <div class="cli-stat-lbl">Activos</div>
            </div>
        </div>
        <div class="cli-stat">
            <div class="cli-stat-icon" style="background:#fdf2f8;color:#e74c3c"><i class="fas fa-user-slash"></i></div>
            <div>
                <div class="cli-stat-num"><?php echo $total - $activos; ?></div>
                <div class="cli-stat-lbl">Inactivos</div>
            </div>
        </div>
        <div class="cli-stat">
            <div class="cli-stat-icon" style="background:#eaf4fb;color:#2980b9"><i class="fas fa-user-plus"></i></div>
            <div>
                <div class="cli-stat-num"><?php echo $nuevos; ?></div>
                <div class="cli-stat-lbl">Nuevos este mes</div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="cli-toolbar">
        <div class="cli-search">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nombre, teléfono, email…" oninput="filtrar(this.value)">
        </div>
    </div>

    <!-- Table -->
    <div class="cli-table-wrap">
        <table class="cli-table" id="clienteTable">
            <colgroup>
                <col class="col-cliente">
                <col class="col-email">
                <col class="col-dir">
                <col class="col-fecha">
                <col class="col-estado">
                <col class="col-acciones">
            </colgroup>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th>Registrado</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="clienteBody">
            <?php if (empty($clientes)): ?>
                <tr id="emptyRow">
                    <td colspan="6">
                        <div class="cli-empty">
                            <i class="fas fa-user-friends"></i>
                            <p>Aún no hay clientes. ¡Agrega el primero!</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($clientes as $c): ?>
                <tr data-search="<?php echo strtolower(htmlspecialchars($c['nombre'] . ' ' . $c['telefono'] . ' ' . $c['email'] . ' ' . ($c['num_doc'] ?? ''))); ?>">
                    <td>
                        <div class="cli-name-cell">
                            <div class="cli-avatar" style="background:<?php echo avatarColor((int)$c['id']); ?>">
                                <?php echo initials($c['nombre']); ?>
                            </div>
                            <div>
                                <div class="cli-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                                <?php if ($c['telefono']): ?>
                                <div class="cli-phone"><i class="fas fa-phone" style="font-size:10px"></i> <?php echo htmlspecialchars($c['telefono']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($c['tipo_doc']) && !empty($c['num_doc'])): ?>
                                <div class="cli-doc"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars(strtoupper($c['tipo_doc']) . ' ' . $c['num_doc']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?php echo $c['email'] ? htmlspecialchars($c['email']) : '<span style="color:#b2bec3">—</span>'; ?></td>
                    <td class="cli-td-overflow">
                        <?php echo $c['direccion'] ? htmlspecialchars($c['direccion']) : '<span style="color:#b2bec3">—</span>'; ?>
                    </td>
                    <td style="white-space:nowrap;color:#95a5a6;font-size:13px">
                        <?php echo date('d/m/Y', strtotime($c['created_at'])); ?>
                    </td>
                    <td>
                        <label class="toggle-switch" title="<?php echo $c['activo'] ? 'Desactivar' : 'Activar'; ?>">
                            <input type="checkbox" <?php echo $c['activo'] ? 'checked' : ''; ?>
                                   onchange="toggleActivo(<?php echo $c['id']; ?>, this)">
                            <span class="toggle-track"></span>
                            <span class="toggle-thumb"></span>
                        </label>
                    </td>
                    <td>
                        <div class="cli-actions">
                            <?php if (!empty($c['telefono'])): ?>
                            <button class="cli-btn whatsapp" title="WhatsApp" onclick="abrirWhatsApp('<?php echo preg_replace('/\D/', '', $c['telefono']); ?>','<?php echo addslashes(htmlspecialchars($c['nombre'])); ?>')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                            <?php else: ?>
                            <button class="cli-btn whatsapp" title="Sin teléfono" disabled style="opacity:.35;cursor:default">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                            <?php endif; ?>
                            <button class="cli-btn edit" title="Editar" onclick="editarCliente(<?php echo $c['id']; ?>)">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="cli-btn delete" title="Eliminar" onclick="confirmarEliminar(<?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['nombre'])); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal WhatsApp -->
<div class="wa-overlay" id="waOverlay" onclick="if(event.target===this)cerrarWa()">
    <div class="wa-modal">
        <div class="wa-modal-head">
            <h2><i class="fab fa-whatsapp"></i> Enviar mensaje <small id="waClienteNombre"></small></h2>
            <button class="wa-modal-close" onclick="cerrarWa()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="wa-modal-body">
            <div class="wa-field">
                <label>¿Qué deseas enviar?</label>
                <select id="waTipo" onchange="cambiarTipoWa(this.value)">
                    <option value="">— Seleccionar —</option>
                    <option value="cupon">Código promocional</option>
                    <option value="domicilio">Enlace de domicilio</option>
                </select>
            </div>
            <!-- Lista de cupones -->
            <div id="waSeccCupon" style="display:none">
                <div class="wa-items" id="waListaCupones">
                    <?php if (empty($waCupones)): ?>
                    <div class="wa-empty"><i class="fas fa-ticket"></i>No hay códigos promocionales vigentes</div>
                    <?php else: ?>
                    <?php foreach ($waCupones as $cup): ?>
                    <label class="wa-item" onclick="seleccionarWaItem(this)">
                        <input type="radio" name="waCupon" value="<?php echo htmlspecialchars($cup['codigo']); ?>"
                               data-msg="<?php
                                   $desc = $cup['tipo'] === 'porcentaje'
                                       ? number_format($cup['descuento'],0) . '% de descuento'
                                       : ($cup['tipo'] === 'valor'
                                           ? '$' . number_format($cup['descuento'],0) . ' de descuento'
                                           : 'Producto gratis: ' . ($cup['receta_nombre'] ?? ''));
                                   $exp  = $cup['expira_en'] ? ' · Válido hasta ' . date('d/m/Y', strtotime($cup['expira_en'])) : '';
                                   echo htmlspecialchars("🎁 ¡Hola! Te enviamos tu código de descuento:\n\nCódigo: *{$cup['codigo']}*\n{$desc}{$exp}");
                               ?>">
                        <div class="wa-item-body">
                            <div class="wa-item-title"><?php echo htmlspecialchars($cup['codigo']); ?><?php echo $cup['nombre'] ? ' · ' . htmlspecialchars($cup['nombre']) : ''; ?></div>
                            <div class="wa-item-sub">
                                <?php
                                    echo $cup['tipo'] === 'porcentaje'
                                        ? number_format($cup['descuento'],0) . '% desc.'
                                        : ($cup['tipo'] === 'valor'
                                            ? '$' . number_format($cup['descuento'],0) . ' desc.'
                                            : 'Producto: ' . ($cup['receta_nombre'] ?? ''));
                                    if ($cup['expira_en']) echo ' · Vence ' . date('d/m/Y', strtotime($cup['expira_en']));
                                ?>
                            </div>
                        </div>
                        <span class="wa-item-badge"><?php echo (int)$cup['usos_max'] - (int)$cup['usos_actual']; ?> usos</span>
                    </label>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Lista de domicilios -->
            <div id="waSeccDomicilio" style="display:none">
                <div class="wa-items" id="waListaDomicilios">
                    <?php if (empty($waDomLinks)): ?>
                    <div class="wa-empty"><i class="fas fa-motorcycle"></i>No hay enlaces de domicilio activos</div>
                    <?php else: ?>
                    <?php foreach ($waDomLinks as $lk): ?>
                    <?php $urlDom = rtrim($baseUrl, '/') . '/pedido/' . $lk['token']; ?>
                    <label class="wa-item" onclick="seleccionarWaItem(this)">
                        <input type="radio" name="waDomicilio" value="<?php echo $lk['id']; ?>"
                               data-msg="<?php echo htmlspecialchars("🛵 ¡Hola! Aquí puedes hacer tu pedido a domicilio:\n\n*{$lk['nombre']}*\n{$urlDom}"); ?>">
                        <div class="wa-item-body">
                            <div class="wa-item-title"><?php echo htmlspecialchars($lk['nombre']); ?></div>
                            <div class="wa-item-sub"><?php echo $lk['descripcion'] ? htmlspecialchars($lk['descripcion']) : $urlDom; ?></div>
                        </div>
                        <span class="wa-item-badge" style="background:#eaf4fb;color:#2980b9">Activo</span>
                    </label>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="wa-modal-foot">
            <button class="btn-cancel" onclick="cerrarWa()">Cancelar</button>
            <button class="wa-btn-send" id="waBtnSend" onclick="enviarWa()" disabled>
                <i class="fab fa-whatsapp"></i> Enviar
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar / Editar -->
<div class="cli-overlay" id="modalOverlay" onclick="if(event.target===this)cerrarModal()">
    <div class="cli-modal">
        <div class="cli-modal-head">
            <h2 id="modalTitle"><i class="fas fa-user-plus" style="color:#8e44ad"></i> Nuevo cliente</h2>
            <button class="cli-modal-close" onclick="cerrarModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="cli-modal-body">
            <input type="hidden" id="cliId">
            <div class="cli-field">
                <label>Nombre o Razón Social <span style="color:#e74c3c">*</span></label>
                <input type="text" id="cliNombre" placeholder="Ej. Juan García / Empresa S.A.S." autocomplete="name">
            </div>
            <div class="cli-field-row">
                <div class="cli-field">
                    <label>Tipo de documento</label>
                    <select id="cliTipoDoc">
                        <option value="">— Seleccionar —</option>
                        <option value="cedula">Cédula</option>
                        <option value="pasaporte">Pasaporte</option>
                        <option value="nit">NIT</option>
                        <option value="rut">RUT</option>
                    </select>
                </div>
                <div class="cli-field">
                    <label>Número de documento</label>
                    <input type="text" id="cliNumDoc" placeholder="Ej. 123456789">
                </div>
            </div>
            <div class="cli-field-row">
                <div class="cli-field">
                    <label>Teléfono</label>
                    <div class="phone-wrapper">
                        <span class="phone-prefix">+57</span>
                        <input type="tel" id="cliTelefono" placeholder="300 123 4567" autocomplete="tel">
                    </div>
                </div>
                <div class="cli-field">
                    <label>Email</label>
                    <input type="email" id="cliEmail" placeholder="Ej. juan@email.com" autocomplete="email">
                </div>
            </div>
            <div class="cli-field">
                <label>Dirección</label>
                <input type="text" id="cliDireccion" placeholder="Calle, número, barrio…" autocomplete="street-address">
            </div>
            <div class="cli-field">
                <label>Notas</label>
                <textarea id="cliNotas" rows="3" placeholder="Preferencias, alergias, observaciones…"></textarea>
            </div>
        </div>
        <div class="cli-modal-foot">
            <button class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-save" id="btnGuardar" onclick="guardarCliente()">
                <i class="fas fa-check"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="cli-overlay" id="delOverlay" onclick="if(event.target===this)cerrarEliminar()">
    <div class="cli-modal cli-del-modal">
        <div class="cli-del-body">
            <div class="cli-del-icon"><i class="fas fa-trash"></i></div>
            <h3>¿Eliminar cliente?</h3>
            <p id="delNombre" style="font-weight:700;color:#2c3e50;margin:8px 0 4px"></p>
            <p>Esta acción no se puede deshacer.</p>
        </div>
        <div class="cli-del-foot">
            <button class="btn-cancel" onclick="cerrarEliminar()">Cancelar</button>
            <button class="btn-del" id="btnDel" onclick="ejecutarEliminar()">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<script>
const BASE = '<?php echo $basePath; ?>';
let delId = null;

// ── Filtro ────────────────────────────────────────────────────────────────────
function filtrar(q) {
    const term = q.toLowerCase().trim();
    document.querySelectorAll('#clienteBody tr[data-search]').forEach(row => {
        row.style.display = (!term || row.dataset.search.includes(term)) ? '' : 'none';
    });
}

// ── Modal ─────────────────────────────────────────────────────────────────────
function abrirModal(titulo = 'Nuevo cliente', icono = 'fa-user-plus') {
    document.getElementById('cliId').value        = '';
    document.getElementById('cliNombre').value    = '';
    document.getElementById('cliTipoDoc').value   = '';
    document.getElementById('cliNumDoc').value    = '';
    document.getElementById('cliTelefono').value  = '';
    document.getElementById('cliEmail').value     = '';
    document.getElementById('cliDireccion').value = '';
    document.getElementById('cliNotas').value     = '';
    document.getElementById('modalTitle').innerHTML =
        `<i class="fas ${icono}" style="color:#8e44ad"></i> ${titulo}`;
    document.getElementById('modalOverlay').classList.add('show');
    setTimeout(() => document.getElementById('cliNombre').focus(), 150);
}

function cerrarModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}

async function editarCliente(id) {
    const r = await fetch(BASE + '/clientes/get/' + id);
    const d = await r.json();
    if (!d.success) return;
    const c = d.cliente;
    document.getElementById('cliId').value        = c.id;
    document.getElementById('cliNombre').value    = c.nombre    ?? '';
    document.getElementById('cliTipoDoc').value   = c.tipo_doc  ?? '';
    document.getElementById('cliNumDoc').value    = c.num_doc   ?? '';
    document.getElementById('cliTelefono').value  = c.telefono  ?? '';
    document.getElementById('cliEmail').value     = c.email     ?? '';
    document.getElementById('cliDireccion').value = c.direccion ?? '';
    document.getElementById('cliNotas').value     = c.notas     ?? '';
    document.getElementById('modalTitle').innerHTML =
        `<i class="fas fa-pen" style="color:#8e44ad"></i> Editar cliente`;
    document.getElementById('modalOverlay').classList.add('show');
    setTimeout(() => document.getElementById('cliNombre').focus(), 150);
}

async function guardarCliente() {
    const nombre = document.getElementById('cliNombre').value.trim();
    if (!nombre) {
        document.getElementById('cliNombre').focus();
        return;
    }
    const id        = document.getElementById('cliId').value;
    const tipo_doc  = document.getElementById('cliTipoDoc').value;
    const num_doc   = document.getElementById('cliNumDoc').value.trim();
    const telefono  = document.getElementById('cliTelefono').value.trim();
    const email     = document.getElementById('cliEmail').value.trim();
    const direccion = document.getElementById('cliDireccion').value.trim();
    const notas     = document.getElementById('cliNotas').value.trim();

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const url      = id ? BASE + '/clientes/actualizar' : BASE + '/clientes/crear';
    const payload  = { nombre, tipo_doc, num_doc, telefono, email, direccion, notas };
    if (id) payload.id = parseInt(id);

    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            alert(d.message || 'Error al guardar');
        }
    } catch {
        alert('Error de conexión');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check"></i> Guardar';
}

// Enter en campos dispara guardar
document.querySelectorAll('#cliNombre,#cliTelefono,#cliEmail,#cliDireccion')
    .forEach(el => el.addEventListener('keydown', e => { if (e.key === 'Enter') guardarCliente(); }));

// ── Toggle activo ─────────────────────────────────────────────────────────────
async function toggleActivo(id, checkbox) {
    const prev = checkbox.checked;
    try {
        const r = await fetch(BASE + '/clientes/toggle-activo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const d = await r.json();
        if (!d.success) checkbox.checked = !prev; // revert
    } catch {
        checkbox.checked = !prev;
    }
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
function confirmarEliminar(id, nombre) {
    delId = id;
    document.getElementById('delNombre').textContent = nombre;
    document.getElementById('delOverlay').classList.add('show');
}

function cerrarEliminar() {
    delId = null;
    document.getElementById('delOverlay').classList.remove('show');
}

async function ejecutarEliminar() {
    if (!delId) return;
    const btn = document.getElementById('btnDel');
    btn.disabled = true;
    try {
        const r = await fetch(BASE + '/clientes/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: delId }),
        });
        const d = await r.json();
        if (d.success) {
            location.reload();
        } else {
            alert('No se pudo eliminar');
            btn.disabled = false;
        }
    } catch {
        alert('Error de conexión');
        btn.disabled = false;
    }
}

// ── WhatsApp ──────────────────────────────────────────────────────────────────
let waPhone = '';

function abrirWhatsApp(phone, nombre) {
    waPhone = phone.startsWith('57') ? phone : '57' + phone;
    document.getElementById('waClienteNombre').textContent = nombre ? '· ' + nombre.split(' ')[0] : '';
    document.getElementById('waTipo').value = '';
    document.getElementById('waSeccCupon').style.display = 'none';
    document.getElementById('waSeccDomicilio').style.display = 'none';
    document.getElementById('waBtnSend').disabled = true;
    document.querySelectorAll('.wa-item').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('input[name="waCupon"], input[name="waDomicilio"]').forEach(r => r.checked = false);
    document.getElementById('waOverlay').classList.add('show');
}

function cerrarWa() {
    document.getElementById('waOverlay').classList.remove('show');
}

function cambiarTipoWa(tipo) {
    document.getElementById('waSeccCupon').style.display    = tipo === 'cupon'     ? '' : 'none';
    document.getElementById('waSeccDomicilio').style.display = tipo === 'domicilio' ? '' : 'none';
    document.getElementById('waBtnSend').disabled = true;
    document.querySelectorAll('.wa-item').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('input[name="waCupon"], input[name="waDomicilio"]').forEach(r => r.checked = false);
}

function seleccionarWaItem(label) {
    const section = label.closest('.wa-items');
    section.querySelectorAll('.wa-item').forEach(el => el.classList.remove('selected'));
    label.classList.add('selected');
    label.querySelector('input[type=radio]').checked = true;
    document.getElementById('waBtnSend').disabled = false;
}

function enviarWa() {
    const radio = document.querySelector('input[name="waCupon"]:checked, input[name="waDomicilio"]:checked');
    if (!radio || !waPhone) return;
    const msg = radio.dataset.msg;
    const url = 'https://web.whatsapp.com/send?phone=' + waPhone + '&text=' + encodeURIComponent(msg);

    window.open(url, '_blank');

    cerrarWa();
}

// Cerrar modales con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarModal(); cerrarEliminar(); cerrarWa(); }
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
