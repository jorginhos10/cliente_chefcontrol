<?php
// vista/perdidas/index.php
$titulo       = 'Pérdidas — CHEFCONTROL';
$paginaActual = 'perdidas';
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="pd-container">

    <!-- ══ HEADER ══ -->
    <div class="pd-header">
        <div class="pd-header-left">
            <div class="pd-header-icon-wrap">
                <i class="fas fa-arrow-trend-down"></i>
            </div>
            <div>
                <h1>Pérdidas</h1>
                <p>Registro de salidas y bajas de inventario</p>
            </div>
        </div>
        <div class="pd-header-right">
            <form method="get" action="<?php echo $basePath; ?>/perdidas" class="pd-date-form">
                <div class="pd-date-group">
                    <label>Desde</label>
                    <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>" class="pd-date-input">
                </div>
                <div class="pd-date-group">
                    <label>Hasta</label>
                    <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>" class="pd-date-input">
                </div>
                <button type="submit" class="pd-btn-filtrar"><i class="fas fa-search"></i> Filtrar</button>
            </form>
            <button class="pd-btn-nueva" onclick="abrirModal()">
                <i class="fas fa-plus"></i> Registrar salida
            </button>
        </div>
    </div>

    <!-- ══ STAT CARDS ══ -->
    <div class="pd-stats-grid">
        <div class="pd-stat-card" style="border-left-color:#e74c3c">
            <div class="pd-stat-icon" style="background:#fdedec;color:#e74c3c">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="pd-stat-body">
                <div class="pd-stat-num"><?php echo (int)$stats['total_salidas']; ?></div>
                <div class="pd-stat-lbl">Salidas registradas</div>
            </div>
        </div>
        <div class="pd-stat-card" style="border-left-color:#e67e22">
            <div class="pd-stat-icon" style="background:#fef5e7;color:#e67e22">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div class="pd-stat-body">
                <div class="pd-stat-num"><?php echo number_format((float)$stats['total_unidades'], 2, '.', ''); ?></div>
                <div class="pd-stat-lbl">Unidades perdidas</div>
            </div>
        </div>
        <div class="pd-stat-card" style="border-left-color:<?php echo $stats['top_insumo'] ? '#9b59b6' : '#bdc3c7'; ?>">
            <div class="pd-stat-icon" style="background:#f5eef8;color:#9b59b6">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="pd-stat-body">
                <div class="pd-stat-num pd-stat-text"><?php echo $stats['top_insumo'] ? htmlspecialchars($stats['top_insumo']) : '—'; ?></div>
                <div class="pd-stat-lbl">Insumo con más salidas</div>
            </div>
        </div>
    </div>

    <!-- ══ TABLA ══ -->
    <div class="pd-table-card">
        <div class="pd-table-header">
            <div class="pd-table-title">
                <i class="fas fa-list" style="color:#e74c3c"></i>
                <h2>Historial de salidas</h2>
                <?php if (!empty($salidas)): ?>
                <span class="pd-count-pill"><?php echo count($salidas); ?> registros</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($salidas)): ?>
        <div class="pd-empty">
            <div class="pd-empty-ico">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Sin pérdidas en este período</h3>
            <p>No hay salidas de inventario registradas entre las fechas seleccionadas.</p>
            <button class="pd-btn-nueva pd-btn-empty" onclick="abrirModal()">
                <i class="fas fa-plus"></i> Registrar primera salida
            </button>
        </div>
        <?php else: ?>
        <div class="pd-table-wrap">
            <table class="pd-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Insumo</th>
                        <th>Categoría</th>
                        <th style="text-align:center">Cantidad</th>
                        <th style="text-align:right">Stock ant.</th>
                        <th style="text-align:right">Stock nuevo</th>
                        <th>Motivo / Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $catIcons = [
                    'carnes'   => 'fa-drumstick-bite',
                    'verduras' => 'fa-leaf',
                    'lacteos'  => 'fa-cheese',
                    'granos'   => 'fa-seedling',
                    'especias' => 'fa-mortar-pestle',
                    'bebidas'  => 'fa-wine-bottle',
                    'otros'    => 'fa-box',
                ];
                foreach ($salidas as $s):
                    $icon = $catIcons[$s['categoria']] ?? 'fa-box';
                ?>
                <tr>
                    <td>
                        <div class="pd-fecha-main"><?php echo date('d/m/Y', strtotime($s['fecha'])); ?></div>
                        <div class="pd-fecha-hora"><?php echo date('H:i', strtotime($s['fecha'])); ?></div>
                    </td>
                    <td>
                        <div class="pd-insumo-cell">
                            <div class="pd-cat-icon"><i class="fas <?php echo $icon; ?>"></i></div>
                            <span><?php echo htmlspecialchars($s['insumo']); ?></span>
                        </div>
                    </td>
                    <td><span class="pd-cat"><?php echo htmlspecialchars(ucfirst($s['categoria'])); ?></span></td>
                    <td style="text-align:center">
                        <span class="pd-neg-badge">
                            <i class="fas fa-minus"></i>
                            <?php echo number_format((float)$s['cantidad'], 2, '.', ''); ?> <?php echo htmlspecialchars($s['unidad']); ?>
                        </span>
                    </td>
                    <td style="text-align:right;color:#95a5a6"><?php echo number_format((float)$s['stock_anterior'], 2, '.', ''); ?></td>
                    <td style="text-align:right;font-weight:700;color:#2c3e50"><?php echo number_format((float)$s['stock_nuevo'], 2, '.', ''); ?></td>
                    <td>
                        <div class="pd-motivo"><?php echo htmlspecialchars($s['motivo'] ?? '—'); ?></div>
                        <div class="pd-usuario"><i class="fas fa-user" style="font-size:9px;margin-right:3px"></i><?php echo htmlspecialchars($s['usuario']); ?></div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ══ MODAL: Registrar salida ══ -->
<div class="pd-overlay" id="peModal">
    <div class="pd-modal">
        <div class="pd-modal-header">
            <div class="pd-modal-header-left">
                <div class="pd-modal-icon"><i class="fas fa-arrow-down"></i></div>
                <div>
                    <div class="pd-modal-title">Registrar Salida</div>
                    <div class="pd-modal-sub">Baja de inventario</div>
                </div>
            </div>
            <button class="pd-modal-close" onclick="cerrarModal()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="pd-modal-body">
            <div class="pd-form-group">
                <label>Insumo *</label>
                <select id="peInsumo" class="pd-select">
                    <option value="">— Seleccionar insumo —</option>
                    <?php foreach ($insumos as $ins): ?>
                    <option value="<?php echo $ins['id']; ?>"
                            data-stock="<?php echo $ins['cantidad_stock']; ?>"
                            data-unidad="<?php echo htmlspecialchars($ins['unidad_medida']); ?>">
                        <?php echo htmlspecialchars($ins['nombre']); ?>
                        (Stock: <?php echo number_format((float)$ins['cantidad_stock'], 2, '.', ''); ?> <?php echo $ins['unidad_medida']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pd-form-group">
                <label>Cantidad a dar de baja *</label>
                <div class="pd-input-row">
                    <input type="number" id="peCantidad" class="pd-input" min="0.01" step="0.01" placeholder="0.00">
                    <span id="peUnidad" class="pd-unidad-label"></span>
                </div>
                <small id="peStockInfo" class="pd-stock-info"></small>
            </div>
            <div class="pd-form-group">
                <label>Motivo</label>
                <input type="text" id="peMotivo" class="pd-input" placeholder="Ej: vencido, derrame, robo...">
            </div>
        </div>
        <div class="pd-modal-footer">
            <button onclick="cerrarModal()" class="pd-btn-cancel"><i class="fas fa-xmark"></i> Cancelar</button>
            <button onclick="guardarSalida()" class="pd-btn-guardar" id="peBtnGuardar">
                <i class="fas fa-save"></i> Registrar salida
            </button>
        </div>
    </div>
</div>

<style>
/* ══════════════════════════════════════════
   PÉRDIDAS — mismo lenguaje visual del sistema
══════════════════════════════════════════ */
.pd-container {
    padding: 22px;
    background: #f0f2f5;
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── HEADER ── */
.pd-header {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    border-radius: 14px;
    padding: 22px 28px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 4px 18px rgba(192,57,43,.35);
}
.pd-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.pd-header-icon-wrap {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.25);
}
.pd-header h1 { margin: 0 0 3px; font-size: 22px; font-weight: 800; }
.pd-header p  { margin: 0; font-size: 13px; opacity: .8; }

.pd-header-right {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}
.pd-date-form {
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
.pd-date-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.pd-date-group label {
    font-size: 10px;
    font-weight: 700;
    opacity: .75;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.pd-date-input {
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: #2c3e50;
    background: rgba(255,255,255,.92);
    outline: none;
    height: 36px;
}
.pd-btn-filtrar {
    background: rgba(255,255,255,.2);
    border: 1.5px solid rgba(255,255,255,.35);
    color: #fff;
    padding: 0 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    height: 36px;
    transition: .15s;
    white-space: nowrap;
}
.pd-btn-filtrar:hover { background: rgba(255,255,255,.35); }

.pd-btn-nueva {
    background: #fff;
    color: #c0392b;
    border: none;
    border-radius: 10px;
    padding: 0 20px;
    height: 38px;
    font-size: 13.5px;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
    transition: transform .15s, box-shadow .15s;
    white-space: nowrap;
}
.pd-btn-nueva:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,0,0,.2);
}

/* ── STAT CARDS ── */
.pd-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.pd-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #e0e0e0;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform .2s, box-shadow .2s;
}
.pd-stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,.1); }
.pd-stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.pd-stat-num {
    font-size: 26px;
    font-weight: 800;
    color: #2c3e50;
    line-height: 1;
}
.pd-stat-text { font-size: 16px; }
.pd-stat-lbl {
    font-size: 12px;
    color: #95a5a6;
    margin-top: 4px;
    font-weight: 600;
}

/* ── TABLE CARD ── */
.pd-table-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
}
.pd-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    border-bottom: 2px solid #f0f2f5;
}
.pd-table-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.pd-table-title h2 {
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
    font-weight: 700;
}
.pd-count-pill {
    background: #f0f2f5;
    color: #7f8c8d;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}

/* ── EMPTY ── */
.pd-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 70px 20px;
    gap: 12px;
}
.pd-empty-ico {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: #eafaf1;
    display: flex; align-items: center; justify-content: center;
    font-size: 34px;
    color: #27ae60;
    margin-bottom: 8px;
}
.pd-empty h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}
.pd-empty p {
    font-size: 14px;
    color: #95a5a6;
    line-height: 1.7;
    margin: 0;
    max-width: 380px;
}
.pd-btn-empty {
    background: #e74c3c !important;
    color: #fff !important;
    margin-top: 8px;
    height: 42px !important;
    padding: 0 24px !important;
    box-shadow: 0 4px 14px rgba(231,76,60,.35) !important;
}

/* ── TABLE ── */
.pd-table-wrap { overflow-x: auto; }
.pd-table {
    width: 100%;
    border-collapse: collapse;
}
.pd-table thead tr {
    background: #2c3e50;
    color: #fff;
}
.pd-table th {
    padding: 13px 18px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}
.pd-table tbody tr { border-bottom: 1px solid #f5f6fa; transition: background .15s; }
.pd-table tbody tr:hover { background: #fef5f5; }
.pd-table td { padding: 13px 18px; vertical-align: middle; font-size: 13.5px; }
.pd-table tbody tr:last-child td { border-bottom: none; }

.pd-fecha-main { font-size: 13.5px; font-weight: 600; color: #2c3e50; }
.pd-fecha-hora { font-size: 11.5px; color: #95a5a6; margin-top: 2px; }

.pd-insumo-cell {
    display: flex;
    align-items: center;
    gap: 9px;
}
.pd-cat-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: #fdedec;
    color: #e74c3c;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.pd-cat {
    display: inline-block;
    background: #f5eef8;
    color: #8e44ad;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
}
.pd-neg-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fdedec;
    color: #e74c3c;
    font-size: 12.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
}
.pd-neg-badge i { font-size: 10px; }

.pd-motivo { font-size: 13px; color: #2c3e50; font-weight: 600; }
.pd-usuario { font-size: 11.5px; color: #95a5a6; margin-top: 2px; }

/* ── MODAL ── */
.pd-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10,15,25,.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 16px;
    backdrop-filter: blur(4px);
}
.pd-overlay.show { display: flex; }
.pd-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 30px 90px rgba(0,0,0,.3);
    overflow: hidden;
}
.pd-modal-header {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.pd-modal-header-left { display: flex; align-items: center; gap: 14px; }
.pd-modal-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    color: #fff;
    border: 1.5px solid rgba(255,255,255,.25);
    flex-shrink: 0;
}
.pd-modal-title { font-size: 17px; font-weight: 800; color: #fff; }
.pd-modal-sub   { font-size: 12px; color: rgba(255,255,255,.72); margin-top: 2px; }
.pd-modal-close {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 1.5px solid rgba(255,255,255,.3);
    background: rgba(255,255,255,.12);
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.pd-modal-close:hover { background: rgba(255,255,255,.25); }

.pd-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.pd-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f2f5;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.pd-form-group { display: flex; flex-direction: column; gap: 6px; }
.pd-form-group label { font-size: 13px; font-weight: 700; color: #2c3e50; }
.pd-select, .pd-input {
    border: 1.5px solid #e0e6ea;
    border-radius: 9px;
    padding: 10px 13px;
    font-size: 13.5px;
    color: #2c3e50;
    outline: none;
    width: 100%;
    transition: border-color .15s;
}
.pd-select:focus, .pd-input:focus { border-color: #e74c3c; }
.pd-input-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.pd-input-row .pd-input { flex: 1; }
.pd-unidad-label {
    color: #95a5a6;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    min-width: 50px;
}
.pd-stock-info {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 2px;
    display: block;
}
.pd-btn-cancel {
    background: #f0f2f5;
    color: #636e72;
    border: none;
    border-radius: 9px;
    padding: 11px 20px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    transition: background .15s;
}
.pd-btn-cancel:hover { background: #dfe6e9; color: #2c3e50; }
.pd-btn-guardar {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 11px 22px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    transition: opacity .15s, transform .15s;
}
.pd-btn-guardar:hover { opacity: .88; transform: translateY(-1px); }
.pd-btn-guardar:disabled { opacity: .55; cursor: default; transform: none; }

@media (max-width: 768px) {
    .pd-container { padding: 16px; gap: 16px; }
    .pd-header { flex-direction: column; align-items: flex-start; }
    .pd-header-right { width: 100%; }
    .pd-stats-grid { grid-template-columns: 1fr 1fr; }
    .pd-table th:nth-child(5),
    .pd-table td:nth-child(5) { display: none; }
}
</style>

<script>
const BASE = '<?php echo $basePath; ?>';

function abrirModal() {
    document.getElementById('peModal').classList.add('show');
}
function cerrarModal() {
    document.getElementById('peModal').classList.remove('show');
    document.getElementById('peInsumo').value    = '';
    document.getElementById('peCantidad').value  = '';
    document.getElementById('peMotivo').value    = '';
    document.getElementById('peUnidad').textContent    = '';
    document.getElementById('peStockInfo').textContent = '';
}

document.getElementById('peInsumo').addEventListener('change', function () {
    const opt   = this.options[this.selectedIndex];
    const unidad = opt.dataset.unidad || '';
    const stock  = opt.dataset.stock  || '';
    document.getElementById('peUnidad').textContent    = unidad;
    document.getElementById('peStockInfo').textContent = stock
        ? 'Stock disponible: ' + parseFloat(stock).toFixed(2) + ' ' + unidad
        : '';
});

function guardarSalida() {
    const idInsumo = document.getElementById('peInsumo').value;
    const cantidad = parseFloat(document.getElementById('peCantidad').value);
    const motivo   = document.getElementById('peMotivo').value.trim();
    const btn      = document.getElementById('peBtnGuardar');

    if (!idInsumo || !cantidad || cantidad <= 0) {
        alert('Selecciona un insumo y una cantidad válida');
        return;
    }

    const fd = new FormData();
    fd.append('id_insumo',   idInsumo);
    fd.append('cantidad',    cantidad);
    fd.append('descripcion', motivo);

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    fetch(BASE + '/perdidas/registrar', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                cerrarModal();
                location.reload();
            } else {
                alert(d.message || 'Error al registrar');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Registrar salida';
            }
        })
        .catch(() => {
            alert('Error de conexión');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Registrar salida';
        });
}

// Cerrar modal al hacer clic fuera
document.getElementById('peModal').addEventListener('click', function (e) {
    if (e.target === this) cerrarModal();
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
