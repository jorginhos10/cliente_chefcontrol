<?php
// vista/cupones/index.php
$titulo       = 'Descuentos — CHEFCONTROL';
$paginaActual = 'cupones';
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="cu-wrap">

    <!-- Cabecera -->
    <div class="cu-header">
        <div>
            <h1><i class="fas fa-ticket"></i> Descuentos</h1>
            <p>Genera y administra cupones de descuento</p>
        </div>
        <button class="cu-btn-nuevo" onclick="abrirModal()">
            <i class="fas fa-plus"></i> Nuevo cupón
        </button>
    </div>

    <!-- Stats -->
    <div class="cu-stats">
        <div class="cu-stat">
            <div class="cu-stat-icon" style="background:#eaf4fb;color:#2980b9"><i class="fas fa-ticket"></i></div>
            <div><div class="cu-stat-num" id="stTotal"><?php echo $stats['total']; ?></div><div class="cu-stat-lbl">Total</div></div>
        </div>
        <div class="cu-stat">
            <div class="cu-stat-icon" style="background:#eafaf1;color:#27ae60"><i class="fas fa-check-circle"></i></div>
            <div><div class="cu-stat-num" id="stActivos"><?php echo $stats['activos']; ?></div><div class="cu-stat-lbl">Activos</div></div>
        </div>
        <div class="cu-stat">
            <div class="cu-stat-icon" style="background:#fdf2f2;color:#e74c3c"><i class="fas fa-ban"></i></div>
            <div><div class="cu-stat-num" id="stUsados"><?php echo $stats['usados']; ?></div><div class="cu-stat-lbl">Usados</div></div>
        </div>
        <div class="cu-stat">
            <div class="cu-stat-icon" style="background:#f8f9fa;color:#95a5a6"><i class="fas fa-pause-circle"></i></div>
            <div><div class="cu-stat-num" id="stInactivos"><?php echo $stats['inactivos']; ?></div><div class="cu-stat-lbl">Inactivos</div></div>
        </div>
    </div>

    <!-- Gráfica de descuentos por mes -->
    <div class="cu-chart-section">
        <div class="cu-chart-card">
            <div class="cu-chart-header">
                <div class="cu-chart-title">
                    <i class="fas fa-chart-bar" style="color:#8e44ad"></i>
                    <div>
                        <h3>Descuentos otorgados — <?php echo date('Y'); ?></h3>
                        <p>Saldo regalado en cupones por mes</p>
                    </div>
                </div>
                <div class="cu-chart-tabs">
                    <button class="cu-ctab cu-ctab-active" data-mode="monto">$ Monto</button>
                    <button class="cu-ctab" data-mode="usos">Usos</button>
                </div>
            </div>
            <div class="cu-chart-wrap">
                <canvas id="cuChart" height="80"></canvas>
            </div>
            <div class="cu-chart-resumen" id="cuChartResumen"></div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="cu-table-section">
        <div class="cu-table-wrap" id="tablaCupones">
            <?php if (empty($cupones)): ?>
            <div class="cu-empty">
                <i class="fas fa-ticket"></i>
                <p>Aún no hay cupones generados</p>
                <button class="cu-btn-nuevo" onclick="abrirModal()" style="margin-top:14px">
                    <i class="fas fa-plus"></i> Generar primer cupón
                </button>
            </div>
            <?php else: ?>
            <table class="cu-table">
                <colgroup>
                    <col style="width:16%">
                    <col style="width:22%">
                    <col style="width:14%">
                    <col style="width:10%">
                    <col style="width:12%">
                    <col style="width:13%">
                    <col style="width:13%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre / Descripción</th>
                        <th>Descuento</th>
                        <th>Usos</th>
                        <th>Estado</th>
                        <th>Vence</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyCupones">
                <?php foreach ($cupones as $c): ?>
                <?php
                    $estadoClass = ['activo' => 'est-activo', 'usado' => 'est-usado', 'inactivo' => 'est-inactivo'][$c['estado']] ?? 'est-inactivo';
                    $estadoLabel = ['activo' => 'Activo', 'usado' => 'Usado', 'inactivo' => 'Inactivo'][$c['estado']] ?? $c['estado'];
                    $vence = $c['expira_en'] ? date('d/m/Y', strtotime($c['expira_en'])) : '—';
                    if ($c['tipo'] === 'porcentaje') {
                        $descStr = number_format((float)$c['descuento'], 0).'%';
                    } elseif ($c['tipo'] === 'producto') {
                        $descStr = number_format((float)$c['descuento'], 0).'% · '.htmlspecialchars($c['receta_nombre'] ?? '—');
                    } else {
                        $descStr = '$'.number_format((float)$c['descuento'], 0, ',', '.');
                    }
                ?>
                <tr id="row-<?php echo $c['id']; ?>">
                    <td>
                        <div class="cu-code-cell">
                            <span class="cu-code"><?php echo htmlspecialchars($c['codigo']); ?></span>
                            <button class="cu-copy-btn" onclick="copiarCodigo('<?php echo htmlspecialchars($c['codigo']); ?>',this)" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td class="cu-nombre"><?php echo $c['nombre'] ? htmlspecialchars($c['nombre']) : '<span class="cu-sin-nombre">Sin nombre</span>'; ?></td>
                    <?php $badgeCls = ['porcentaje'=>'desc-pct','valor'=>'desc-val','producto'=>'desc-prod'][$c['tipo']] ?? 'desc-val'; ?>
                    <td><span class="cu-desc-badge <?php echo $badgeCls; ?>"><?php echo $descStr; ?></span></td>
                    <td style="text-align:center"><?php echo (int)$c['usos_actual']; ?> / <?php echo (int)$c['usos_max']; ?></td>
                    <td><span class="cu-estado <?php echo $estadoClass; ?>"><?php echo $estadoLabel; ?></span></td>
                    <td class="cu-vence"><?php echo $vence; ?></td>
                    <td>
                        <div class="cu-actions">
                            <?php if ($c['estado'] !== 'usado'): ?>
                            <button class="cu-act-btn toggle" onclick="toggleCupon(<?php echo $c['id']; ?>,this)"
                                    title="<?php echo $c['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas <?php echo $c['estado'] === 'activo' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                            </button>
                            <?php endif; ?>
                            <button class="cu-act-btn del" onclick="eliminarCupon(<?php echo $c['id']; ?>)" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal nuevo cupón -->
<div class="cu-overlay" id="cuOverlay" onclick="if(event.target===this)cerrarModal()">
    <div class="cu-modal">
        <div class="cu-modal-head">
            <div class="cu-modal-icon"><i class="fas fa-ticket"></i></div>
            <div>
                <h2>Nuevo cupón</h2>
                <p id="cuModalSubtitle">El código se genera automáticamente</p>
            </div>
            <button class="cu-modal-close" onclick="cerrarModal()"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="cu-modal-body">

            <!-- Tipo de código -->
            <div class="cu-radio-group">
                <label class="cu-radio-opt" id="radioDefaultLabel">
                    <input type="radio" name="cuTipoCodigo" value="default" checked onchange="onTipoCodigoChange()">
                    <span class="cu-radio-box">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span>Código automático</span>
                    </span>
                </label>
                <label class="cu-radio-opt" id="radioCustomLabel">
                    <input type="radio" name="cuTipoCodigo" value="personalizado" onchange="onTipoCodigoChange()">
                    <span class="cu-radio-box">
                        <i class="fas fa-pen"></i>
                        <span>Código personalizado</span>
                    </span>
                </label>
            </div>

            <!-- Input código personalizado -->
            <div class="cu-field" id="cuCodigoWrap" style="display:none">
                <label>Código del cupón <span class="cu-req">*</span></label>
                <div class="cu-code-input-wrap">
                    <input type="text" id="cuCodigoCustom" maxlength="8"
                           placeholder="Ej: VERANO24"
                           oninput="onCodigoInput(this)"
                           autocomplete="off" spellcheck="false">
                    <span class="cu-code-status" id="cuCodigoStatus"></span>
                </div>
                <div class="cu-hint">3–8 caracteres, solo letras y números. <span id="cuCodigoContador" class="cu-contador">0/8</span></div>
            </div>

            <div class="cu-field">
                <label>Nombre o descripción <span class="cu-opt">(opcional)</span></label>
                <input type="text" id="cuNombre" placeholder="Ej: Promoción lanzamiento, Cumpleaños…">
            </div>

            <div class="cu-field-row">
                <div class="cu-field">
                    <label>Tipo de descuento</label>
                    <select id="cuTipo" onchange="actualizarPlaceholder()">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="valor">Valor fijo ($)</option>
                        <option value="producto">Por producto (%)</option>
                    </select>
                </div>
                <div class="cu-field">
                    <label>Valor del descuento <span class="cu-req">*</span></label>
                    <div class="cu-input-prefix-wrap">
                        <span class="cu-prefix" id="cuPrefix">%</span>
                        <input type="number" id="cuDescuento" placeholder="10" min="1" max="100" step="0.5">
                    </div>
                </div>
            </div>

            <div class="cu-field" id="cuProductoWrap" style="display:none">
                <label>Producto al que aplica <span class="cu-req">*</span></label>
                <select id="cuReceta">
                    <option value="">— Seleccionar producto —</option>
                    <?php foreach ($recetas as $r): ?>
                    <option value="<?php echo (int)$r['id']; ?>"><?php echo htmlspecialchars($r['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cu-field-row">
                <div class="cu-field">
                    <label>Usos máximos</label>
                    <input type="number" id="cuUsosMax" value="1" min="1" max="9999">
                    <div class="cu-hint">1 = cupón de un solo uso</div>
                </div>
                <div class="cu-field">
                    <label>Fecha de vencimiento <span class="cu-opt">(opcional)</span></label>
                    <input type="date" id="cuExpira" min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
        </div>

        <div class="cu-modal-foot">
            <button class="cu-cancel-btn" onclick="cerrarModal()">Cancelar</button>
            <button class="cu-generar-btn" id="btnGenerar" onclick="generarCupon()">
                <i class="fas fa-wand-magic-sparkles"></i> Generar cupón
            </button>
        </div>
    </div>
</div>

<!-- Toast de éxito -->
<div class="cu-toast" id="cuToast"></div>

<style>
.cu-wrap { display:flex; flex-direction:column; background:#f0f2f5; min-height:calc(100vh - 70px); }

/* Header */
.cu-header { background:linear-gradient(135deg,#1a252f,#2c3e50); color:#fff; padding:20px 28px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.cu-header h1 { margin:0; font-size:22px; display:flex; align-items:center; gap:10px; }
.cu-header p  { margin:4px 0 0; font-size:13px; opacity:.65; }
.cu-btn-nuevo { background:#fff; color:#2c3e50; border:none; padding:10px 20px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.15s; }
.cu-btn-nuevo:hover { background:#f0f2f5; }

/* Stats */
.cu-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; padding:20px 28px; }
.cu-stat  { background:#fff; border-radius:14px; padding:18px 20px; box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; align-items:center; gap:14px; }
.cu-stat-icon { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.cu-stat-num  { font-size:26px; font-weight:800; color:#2c3e50; line-height:1; }
.cu-stat-lbl  { font-size:12px; color:#95a5a6; margin-top:3px; }

/* Tabla */
.cu-table-section { padding:0 28px 28px; }
.cu-table-wrap { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.cu-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:13px; }
.cu-table thead th { background:#f8f9fa; padding:12px 14px; text-align:left; font-size:11px; font-weight:700; color:#636e72; text-transform:uppercase; letter-spacing:.4px; border-bottom:1.5px solid #e8ecf0; }
.cu-table tbody td { padding:13px 14px; border-bottom:1px solid #f0f2f5; color:#2c3e50; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; vertical-align:middle; }
.cu-table tbody tr:last-child td { border-bottom:none; }
.cu-table tbody tr:hover td { background:#fafbfc; }

/* Código */
.cu-code-cell { display:flex; align-items:center; gap:6px; }
.cu-code { font-family:'Courier New',monospace; font-size:14px; font-weight:800; color:#2c3e50; letter-spacing:2px; background:#f0f2f5; padding:4px 8px; border-radius:6px; }
.cu-copy-btn { background:none; border:none; color:#95a5a6; cursor:pointer; padding:4px; border-radius:5px; font-size:12px; transition:.15s; }
.cu-copy-btn:hover { color:#2c3e50; background:#e8ecf0; }
.cu-copy-btn.copiado { color:#27ae60; }

/* Badge descuento */
.cu-desc-badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:800; }
.desc-pct  { background:#f5eef8; color:#8e44ad; }
.desc-val  { background:#eaf4fb; color:#2980b9; }
.desc-prod { background:#fef9ec; color:#d68910; }

/* Estado */
.cu-estado { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.est-activo   { background:#eafaf1; color:#27ae60; }
.est-usado    { background:#fdf2f2; color:#e74c3c; }
.est-inactivo { background:#f8f9fa; color:#95a5a6; }

.cu-nombre { color:#636e72; }
.cu-sin-nombre { color:#b2bec3; font-style:italic; }
.cu-vence { color:#95a5a6; font-size:12px; }
.cu-opt { color:#b2bec3; font-weight:400; }
.cu-req { color:#e74c3c; }

/* Acciones tabla */
.cu-actions { display:flex; gap:5px; }
.cu-act-btn { width:30px; height:30px; border:none; border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:13px; transition:.15s; }
.cu-act-btn.toggle { background:#eafaf1; color:#27ae60; }
.cu-act-btn.toggle:hover { background:#27ae60; color:#fff; }
.cu-act-btn.toggle.off { background:#f8f9fa; color:#95a5a6; }
.cu-act-btn.toggle.off:hover { background:#95a5a6; color:#fff; }
.cu-act-btn.del { background:#fdf2f2; color:#e74c3c; }
.cu-act-btn.del:hover { background:#e74c3c; color:#fff; }

/* Empty */
.cu-empty { text-align:center; padding:60px 20px; color:#b2bec3; }
.cu-empty i { font-size:44px; display:block; margin-bottom:14px; }

/* Modal */
.cu-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:2000; align-items:center; justify-content:center; padding:20px; }
.cu-overlay.show { display:flex; }
.cu-modal { background:#fff; border-radius:20px; width:100%; max-width:480px; box-shadow:0 24px 80px rgba(0,0,0,.25); overflow:hidden; }
.cu-modal-head { background:linear-gradient(135deg,#1a252f,#2c3e50); color:#fff; padding:20px 24px; display:flex; align-items:center; gap:14px; }
.cu-modal-icon { width:44px; height:44px; background:rgba(255,255,255,.15); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.cu-modal-head h2 { margin:0; font-size:17px; font-weight:800; }
.cu-modal-head p  { margin:3px 0 0; font-size:12px; opacity:.65; }
.cu-modal-close { margin-left:auto; background:none; border:none; color:#fff; font-size:18px; cursor:pointer; opacity:.7; padding:4px; }
.cu-modal-close:hover { opacity:1; }
.cu-modal-body { padding:22px 24px; display:flex; flex-direction:column; gap:16px; }
.cu-modal-foot { padding:16px 24px; background:#f8f9fa; display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #e8ecf0; }

/* Campos modal */
.cu-field { display:flex; flex-direction:column; gap:6px; flex:1; }
.cu-field label { font-size:12px; font-weight:700; color:#636e72; text-transform:uppercase; letter-spacing:.4px; }
.cu-field input, .cu-field select { border:1.5px solid #e8ecf0; border-radius:10px; padding:10px 12px; font-size:14px; color:#2c3e50; outline:none; background:#fff; transition:border-color .2s; font-family:inherit; }
.cu-field input:focus, .cu-field select:focus { border-color:#2c3e50; }
.cu-field-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.cu-hint { font-size:11px; color:#b2bec3; }
.cu-input-prefix-wrap { display:flex; align-items:center; border:1.5px solid #e8ecf0; border-radius:10px; overflow:hidden; background:#fff; transition:border-color .2s; }
.cu-input-prefix-wrap:focus-within { border-color:#2c3e50; }
.cu-prefix { padding:0 10px; font-size:14px; font-weight:700; color:#95a5a6; background:#f8f9fa; border-right:1.5px solid #e8ecf0; height:100%; display:flex; align-items:center; min-height:42px; }
.cu-input-prefix-wrap input { border:none; border-radius:0; flex:1; outline:none; padding:10px 12px; }
.cu-input-prefix-wrap input:focus { border:none; }

/* Botones modal */
.cu-cancel-btn { background:#f0f2f5; color:#636e72; border:none; padding:10px 20px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; }
.cu-cancel-btn:hover { background:#e0e4e8; }
.cu-generar-btn { background:#2c3e50; color:#fff; border:none; padding:10px 22px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; }
.cu-generar-btn:hover { background:#1a252f; }
.cu-generar-btn:disabled { opacity:.6; cursor:default; }

/* Radio grupo tipo código */
.cu-radio-group { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.cu-radio-opt input[type=radio] { display:none; }
.cu-radio-box { display:flex; align-items:center; gap:8px; padding:10px 14px; border:2px solid #e8ecf0; border-radius:10px; cursor:pointer; font-size:13px; font-weight:600; color:#636e72; transition:.15s; background:#fafbfc; }
.cu-radio-opt input:checked + .cu-radio-box { border-color:#2c3e50; background:#f0f2f5; color:#2c3e50; }
.cu-radio-box i { font-size:14px; }

/* Input código personalizado */
.cu-code-input-wrap { display:flex; align-items:center; border:1.5px solid #e8ecf0; border-radius:10px; overflow:hidden; background:#fff; transition:border-color .2s; }
.cu-code-input-wrap:focus-within { border-color:#2c3e50; }
.cu-code-input-wrap input { flex:1; border:none; outline:none; padding:10px 12px; font-size:15px; font-family:'Courier New',monospace; font-weight:800; letter-spacing:2px; text-transform:uppercase; color:#2c3e50; }
.cu-code-status { width:36px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.cu-code-status.ok  { color:#27ae60; }
.cu-code-status.err { color:#e74c3c; }
.cu-code-status.checking { color:#95a5a6; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.cu-contador { font-weight:700; color:#95a5a6; }
.cu-contador.warn { color:#e74c3c; }

/* Gráfica */
.cu-chart-section { padding: 0 28px; }
.cu-chart-card { background:#fff; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; margin-bottom: 4px; }
.cu-chart-header { padding:18px 22px 14px; border-bottom:2px solid #f0f2f5; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.cu-chart-title { display:flex; align-items:center; gap:12px; }
.cu-chart-title h3 { margin:0; font-size:14px; font-weight:800; color:#2c3e50; }
.cu-chart-title p  { margin:2px 0 0; font-size:12px; color:#b2bec3; }
.cu-chart-wrap { padding:18px 22px 10px; }
.cu-chart-tabs { display:flex; background:#f0f2f5; border-radius:8px; padding:3px; gap:2px; }
.cu-ctab { background:none; border:none; border-radius:6px; padding:5px 14px; font-size:12px; font-weight:700; color:#7f8c8d; cursor:pointer; transition:.15s; }
.cu-ctab.cu-ctab-active { background:#fff; color:#2c3e50; box-shadow:0 1px 4px rgba(0,0,0,.1); }
.cu-chart-resumen { display:flex; gap:0; border-top:1.5px solid #f0f2f5; }
.cu-resumen-item { flex:1; padding:12px 16px; text-align:center; border-right:1px solid #f0f2f5; }
.cu-resumen-item:last-child { border-right:none; }
.cu-resumen-num { font-size:18px; font-weight:900; color:#2c3e50; }
.cu-resumen-num.purple { color:#8e44ad; }
.cu-resumen-lbl { font-size:11px; color:#b2bec3; font-weight:600; margin-top:2px; }

/* Toast */
.cu-toast { position:fixed; bottom:24px; right:24px; background:#2c3e50; color:#fff; padding:12px 20px; border-radius:12px; font-size:13px; font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,.2); opacity:0; transform:translateY(10px); transition:opacity .25s,transform .25s; pointer-events:none; z-index:3000; display:flex; align-items:center; gap:8px; }
.cu-toast.show { opacity:1; transform:translateY(0); }

@media(max-width:1024px) { .cu-stats { grid-template-columns:repeat(2,1fr); } }
@media(max-width:600px)  {
    .cu-stats { padding:16px; }
    .cu-table-section { padding:0 16px 16px; }
    .cu-field-row { grid-template-columns:1fr; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartDataLabels);
const BASE = '<?php echo $basePath; ?>';

// ── Modal ─────────────────────────────────────────────────────────────────────
function abrirModal() {
    document.getElementById('cuOverlay').classList.add('show');
    document.getElementById('cuNombre').focus();
}
function cerrarModal() {
    document.getElementById('cuOverlay').classList.remove('show');
    document.getElementById('cuNombre').value      = '';
    document.getElementById('cuDescuento').value   = '';
    document.getElementById('cuUsosMax').value     = '1';
    document.getElementById('cuExpira').value      = '';
    document.getElementById('cuTipo').value        = 'porcentaje';
    document.getElementById('cuReceta').value      = '';
    // reset código personalizado
    document.querySelector('input[name=cuTipoCodigo][value=default]').checked = true;
    document.getElementById('cuCodigoCustom').value = '';
    document.getElementById('cuCodigoWrap').style.display = 'none';
    document.getElementById('cuCodigoStatus').className = 'cu-code-status';
    document.getElementById('cuCodigoStatus').innerHTML = '';
    document.getElementById('cuCodigoContador').textContent = '0/8';
    document.getElementById('cuModalSubtitle').textContent = 'El código se genera automáticamente';
    _codigoValido = null;
    actualizarPlaceholder();
    const btn = document.getElementById('btnGenerar');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar cupón';
}

// ── Radio tipo código ─────────────────────────────────────────────────────────
let _codigoValido = null; // null=auto, true=ok, false=error
let _debounceTimer = null;

function onTipoCodigoChange() {
    const esPersonalizado = document.querySelector('input[name=cuTipoCodigo]:checked').value === 'personalizado';
    document.getElementById('cuCodigoWrap').style.display = esPersonalizado ? 'flex' : 'none';
    document.getElementById('cuModalSubtitle').textContent = esPersonalizado
        ? 'Define el código que quieres usar'
        : 'El código se genera automáticamente';
    if (esPersonalizado) {
        _codigoValido = false;
        document.getElementById('cuCodigoCustom').focus();
    } else {
        _codigoValido = null;
    }
}

function onCodigoInput(input) {
    // forzar mayúsculas y solo alfanumérico
    input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    const len = input.value.length;
    const contador = document.getElementById('cuCodigoContador');
    contador.textContent = len + '/8';
    contador.className = 'cu-contador' + (len === 8 ? ' warn' : '');

    const status = document.getElementById('cuCodigoStatus');
    if (len < 3) {
        status.className = 'cu-code-status';
        status.innerHTML = '';
        _codigoValido = false;
        return;
    }

    // spinner mientras espera
    status.className = 'cu-code-status checking';
    status.innerHTML = '<i class="fas fa-circle-notch"></i>';
    _codigoValido = false;

    clearTimeout(_debounceTimer);
    _debounceTimer = setTimeout(() => verificarCodigoDisponible(input.value), 420);
}

async function verificarCodigoDisponible(codigo) {
    const status = document.getElementById('cuCodigoStatus');
    try {
        const r = await fetch(BASE + '/cupones/verificar-codigo', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ codigo })
        });
        const d = await r.json();
        if (d.disponible === true) {
            status.className = 'cu-code-status ok';
            status.innerHTML = '<i class="fas fa-check-circle"></i>';
            _codigoValido = true;
        } else if (d.disponible === false) {
            status.className = 'cu-code-status err';
            status.innerHTML = '<i class="fas fa-times-circle"></i>';
            _codigoValido = false;
        } else {
            status.className = 'cu-code-status';
            status.innerHTML = '';
            _codigoValido = false;
        }
    } catch {
        status.className = 'cu-code-status';
        status.innerHTML = '';
        _codigoValido = false;
    }
}

function actualizarPlaceholder() {
    const tipo = document.getElementById('cuTipo').value;
    const prefix    = document.getElementById('cuPrefix');
    const input     = document.getElementById('cuDescuento');
    const prodWrap  = document.getElementById('cuProductoWrap');
    if (tipo === 'porcentaje') {
        prefix.textContent = '%';
        input.max = '100';
        input.placeholder = '10';
        prodWrap.style.display = 'none';
    } else if (tipo === 'producto') {
        prefix.textContent = '%';
        input.max = '100';
        input.placeholder = '10';
        prodWrap.style.display = 'flex';
    } else {
        prefix.textContent = '$';
        input.removeAttribute('max');
        input.placeholder = '5000';
        prodWrap.style.display = 'none';
    }
}

// ── Generar ───────────────────────────────────────────────────────────────────
async function generarCupon() {
    const descuento = parseFloat(document.getElementById('cuDescuento').value);
    if (!descuento || descuento <= 0) {
        document.getElementById('cuDescuento').focus(); return;
    }
    const tipo = document.getElementById('cuTipo').value;
    if (tipo === 'producto' && !document.getElementById('cuReceta').value) {
        document.getElementById('cuReceta').focus(); return;
    }

    // Validar código personalizado
    const esPersonalizado = document.querySelector('input[name=cuTipoCodigo]:checked').value === 'personalizado';
    const codigoCustom = document.getElementById('cuCodigoCustom').value.trim();
    if (esPersonalizado) {
        if (codigoCustom.length < 3) {
            mostrarToast('<i class="fas fa-xmark"></i> El código debe tener al menos 3 caracteres', true); return;
        }
        if (_codigoValido !== true) {
            mostrarToast('<i class="fas fa-xmark"></i> Ese código ya está en uso, elige otro', true); return;
        }
    }

    const btn = document.getElementById('btnGenerar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando…';

    try {
        const r = await fetch(BASE + '/cupones/generar', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                nombre    : document.getElementById('cuNombre').value.trim(),
                tipo,
                descuento,
                usos_max  : parseInt(document.getElementById('cuUsosMax').value) || 1,
                expira_en : document.getElementById('cuExpira').value || null,
                id_receta : tipo === 'producto' ? parseInt(document.getElementById('cuReceta').value) || 0 : 0,
                codigo    : esPersonalizado ? codigoCustom : '',
            })
        });
        const d = await r.json();
        if (d.success) {
            cerrarModal();
            insertarFila(d.cupon);
            actualizarStats(1, 0, 0, 0);
            mostrarToast('<i class="fas fa-check"></i> Cupón ' + d.cupon.codigo + ' creado');
        } else {
            mostrarToast('<i class="fas fa-xmark"></i> ' + (d.message || 'Error'), true);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar cupón';
        }
    } catch {
        mostrarToast('<i class="fas fa-xmark"></i> Error de conexión', true);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generar cupón';
    }
}

// ── Insertar fila en tabla ────────────────────────────────────────────────────
function insertarFila(c) {
    // Si hay mensaje de vacío, reemplazar por tabla
    const wrap = document.getElementById('tablaCupones');
    if (wrap.querySelector('.cu-empty')) {
        wrap.innerHTML = `<table class="cu-table">
            <colgroup>
                <col style="width:16%"><col style="width:22%"><col style="width:14%">
                <col style="width:10%"><col style="width:12%"><col style="width:13%"><col style="width:13%">
            </colgroup>
            <thead><tr>
                <th>Código</th><th>Nombre / Descripción</th><th>Descuento</th>
                <th>Usos</th><th>Estado</th><th>Vence</th><th>Acciones</th>
            </tr></thead>
            <tbody id="tbodyCupones"></tbody></table>`;
    }
    const tbody = document.getElementById('tbodyCupones');
    let desc;
    if (c.tipo === 'porcentaje') {
        desc = '<span class="cu-desc-badge desc-pct">' + parseFloat(c.descuento).toFixed(0) + '%</span>';
    } else if (c.tipo === 'producto') {
        desc = '<span class="cu-desc-badge desc-prod">' + parseFloat(c.descuento).toFixed(0) + '% · ' + escH(c.receta_nombre || '—') + '</span>';
    } else {
        desc = '<span class="cu-desc-badge desc-val">$' + Number(c.descuento).toLocaleString('es-CO') + '</span>';
    }
    const vence = c.expira_en ? fmtFecha(c.expira_en) : '—';
    const nombre = c.nombre
        ? escH(c.nombre)
        : '<span class="cu-sin-nombre">Sin nombre</span>';

    const tr = document.createElement('tr');
    tr.id = 'row-' + c.id;
    tr.innerHTML = `
        <td><div class="cu-code-cell">
            <span class="cu-code">${escH(c.codigo)}</span>
            <button class="cu-copy-btn" onclick="copiarCodigo('${escH(c.codigo)}',this)" title="Copiar"><i class="fas fa-copy"></i></button>
        </div></td>
        <td class="cu-nombre">${nombre}</td>
        <td>${desc}</td>
        <td style="text-align:center">0 / ${c.usos_max}</td>
        <td><span class="cu-estado est-activo">Activo</span></td>
        <td class="cu-vence">${vence}</td>
        <td><div class="cu-actions">
            <button class="cu-act-btn toggle" onclick="toggleCupon(${c.id},this)" title="Desactivar">
                <i class="fas fa-toggle-on"></i>
            </button>
            <button class="cu-act-btn del" onclick="eliminarCupon(${c.id})" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </div></td>`;
    tbody.prepend(tr);
}

// ── Toggle estado ─────────────────────────────────────────────────────────────
async function toggleCupon(id, btn) {
    const r = await fetch(BASE + '/cupones/toggle', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    const d = await r.json();
    if (!d.success) return;
    const row      = document.getElementById('row-' + id);
    const estadoEl = row.querySelector('.cu-estado');
    const icon     = btn.querySelector('i');
    if (estadoEl.classList.contains('est-activo')) {
        estadoEl.className = 'cu-estado est-inactivo';
        estadoEl.textContent = 'Inactivo';
        icon.className = 'fas fa-toggle-off';
        btn.classList.add('off');
        btn.title = 'Activar';
        actualizarStats(0, -1, 0, 1);
    } else {
        estadoEl.className = 'cu-estado est-activo';
        estadoEl.textContent = 'Activo';
        icon.className = 'fas fa-toggle-on';
        btn.classList.remove('off');
        btn.title = 'Desactivar';
        actualizarStats(0, 1, 0, -1);
    }
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
async function eliminarCupon(id) {
    if (!confirm('¿Eliminar este cupón?')) return;
    const r = await fetch(BASE + '/cupones/eliminar', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    const d = await r.json();
    if (d.success) {
        const row = document.getElementById('row-' + id);
        const estado = row.querySelector('.cu-estado');
        if (estado.classList.contains('est-activo'))   actualizarStats(-1, -1, 0, 0);
        else if (estado.classList.contains('est-usado')) actualizarStats(-1, 0, -1, 0);
        else actualizarStats(-1, 0, 0, -1);
        row.remove();
    }
}

// ── Copiar código ─────────────────────────────────────────────────────────────
function copiarCodigo(codigo, btn) {
    navigator.clipboard.writeText(codigo).then(() => {
        btn.classList.add('copiado');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        mostrarToast('<i class="fas fa-copy"></i> Código copiado: ' + codigo);
        setTimeout(() => {
            btn.classList.remove('copiado');
            btn.innerHTML = '<i class="fas fa-copy"></i>';
        }, 2000);
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function actualizarStats(dTotal, dActivos, dUsados, dInactivos) {
    ['stTotal','stActivos','stUsados','stInactivos'].forEach((id, i) => {
        const el = document.getElementById(id);
        const delta = [dTotal, dActivos, dUsados, dInactivos][i];
        el.textContent = Math.max(0, parseInt(el.textContent) + delta);
    });
}

function mostrarToast(html, error = false) {
    const t = document.getElementById('cuToast');
    t.innerHTML = html;
    t.style.background = error ? '#e74c3c' : '#2c3e50';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function fmtFecha(str) {
    const [y,m,d] = str.split('-');
    return d + '/' + m + '/' + y;
}

function escH(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarModal();
});

// ── Gráfica de descuentos por mes ─────────────────────────────────────────────
(function() {
    const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const DATA_MONTO = <?php echo json_encode(array_values(array_map(fn($m) => $m['total'], $graficaMeses))); ?>;
    const DATA_USOS  = <?php echo json_encode(array_values(array_map(fn($m) => $m['usos'],  $graficaMeses))); ?>;
    const MES_HOY    = <?php echo (int)date('n') - 1; ?>;

    let chartMode = 'monto';

    const totalAnio  = DATA_MONTO.reduce((a, v) => a + v, 0);
    const totalUsos  = DATA_USOS.reduce((a, v) => a + v, 0);
    const mesMax     = DATA_MONTO.indexOf(Math.max(...DATA_MONTO));
    const promedioMs = totalAnio > 0 ? totalAnio / DATA_MONTO.filter(v => v > 0).length : 0;

    function fmt(v) {
        if (v >= 1000000) return '$' + (v/1000000).toFixed(1) + 'M';
        if (v >= 1000)    return '$' + (v/1000).toFixed(0) + 'k';
        return '$' + v.toLocaleString('es-CO');
    }

    function buildResumen() {
        const el = document.getElementById('cuChartResumen');
        if (!el) return;
        el.innerHTML = `
            <div class="cu-resumen-item">
                <div class="cu-resumen-num purple">${fmt(totalAnio)}</div>
                <div class="cu-resumen-lbl">Total regalado ${new Date().getFullYear()}</div>
            </div>
            <div class="cu-resumen-item">
                <div class="cu-resumen-num">${totalUsos}</div>
                <div class="cu-resumen-lbl">Cupones canjeados</div>
            </div>
            <div class="cu-resumen-item">
                <div class="cu-resumen-num">${mesMax >= 0 && DATA_MONTO[mesMax] > 0 ? MESES[mesMax] : '—'}</div>
                <div class="cu-resumen-lbl">Mes con más descuentos</div>
            </div>
            <div class="cu-resumen-item">
                <div class="cu-resumen-num">${promedioMs > 0 ? fmt(promedioMs) : '$0'}</div>
                <div class="cu-resumen-lbl">Promedio por mes activo</div>
            </div>`;
    }
    buildResumen();

    const canvas = document.getElementById('cuChart');
    if (!canvas) return;
    const ctxC = canvas.getContext('2d');

    const gradPurple = ctxC.createLinearGradient(0, 0, 0, 200);
    gradPurple.addColorStop(0, 'rgba(142,68,173,.28)');
    gradPurple.addColorStop(1, 'rgba(142,68,173,.03)');

    const colores = MESES.map((_, i) => i === MES_HOY ? '#8e44ad' : '#c39bd3');

    const cuChart = new Chart(ctxC, {
        type: 'bar',
        data: {
            labels: MESES,
            datasets: [{
                label: 'Descuento',
                data: DATA_MONTO,
                backgroundColor: colores,
                borderRadius: 7,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a2332',
                    titleColor: '#fff',
                    bodyColor: '#d7b8e8',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: c => chartMode === 'monto'
                            ? ' $' + c.parsed.y.toLocaleString('es-CO')
                            : ' ' + c.parsed.y + ' uso' + (c.parsed.y !== 1 ? 's' : '')
                    }
                },
                datalabels: {
                    display: c => c.dataset.data[c.dataIndex] > 0,
                    anchor: 'end', align: 'end', offset: 3,
                    color: '#8e44ad',
                    font: { size: 10, weight: '700' },
                    formatter: v => chartMode === 'monto' ? fmt(v) : (v > 0 ? v : ''),
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: c => c.index === MES_HOY ? '#8e44ad' : '#95a5a6',
                        font:  c => ({ size: 12, weight: c.index === MES_HOY ? '800' : '500' })
                    },
                    border: { display: false }
                },
                y: {
                    min: 0,
                    grid: { color: '#f0f2f5' },
                    ticks: {
                        color: '#95a5a6',
                        font: { size: 11 },
                        callback: v => chartMode === 'monto'
                            ? '$' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                            : v
                    },
                    border: { display: false }
                }
            },
            layout: { padding: { top: 20 } }
        }
    });

    // Tab switch
    document.querySelectorAll('.cu-ctab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cu-ctab').forEach(b => b.classList.remove('cu-ctab-active'));
            btn.classList.add('cu-ctab-active');
            chartMode = btn.dataset.mode;
            cuChart.data.datasets[0].data = chartMode === 'monto' ? DATA_MONTO : DATA_USOS;
            cuChart.options.plugins.datalabels.formatter =
                v => chartMode === 'monto' ? fmt(v) : (v > 0 ? String(v) : '');
            cuChart.update();
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
