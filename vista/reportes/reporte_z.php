<?php
// vista/reportes/reporte_z.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Cierre Z — CHEFCONTROL';
$paginaActual = 'reporte-z';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();
$jsExtra      = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

// Stats mes actual (desde modelo)
$mesCierres  = (int)($estadoMes['total_cierres']  ?? 0);
$mesMonto    = (float)($estadoMes['monto_acumulado'] ?? 0);
$mesVentas   = (int)($estadoMes['total_ventas']   ?? 0);
$mesPromedio = $mesCierres > 0 ? $mesMonto / $mesCierres : 0;
$mesNombre   = date_create()->format('F Y');

// Para el header
$ultimoCierre = $historial[0] ?? null;

// Construir datos de la gráfica anual (12 meses)
$mesesLabel = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$graficaMonto   = array_fill(0, 12, 0);
$graficaCierres = array_fill(0, 12, 0);
foreach ($graficaAnual as $row) {
    $idx = (int)$row['mes'] - 1;
    $graficaMonto[$idx]   = (float)$row['monto'];
    $graficaCierres[$idx] = (int)$row['cierres'];
}
$mesActualIdx = (int)date('n') - 1;
?>

<div class="rz-container">

    <!-- ══ HEADER CON GRADIENTE ══ -->
    <div class="rz-header">
        <div class="rz-header-left">
            <div class="rz-header-icon-wrap">
                <i class="fas fa-cash-register"></i>
            </div>
            <div>
                <h1>Cierre Z</h1>
                <p>Registro de cierres fiscales · Se pueden generar varios por jornada</p>
            </div>
        </div>
        <div class="rz-header-right">
            <?php if ($ultimoCierre): ?>
            <div class="rz-header-info">
                <span class="rz-hi-label">Último cierre</span>
                <span class="rz-hi-val">Z-<?php echo str_pad($ultimoCierre['numero_z'],4,'0',STR_PAD_LEFT); ?></span>
            </div>
            <div class="rz-header-info">
                <span class="rz-hi-label">Fecha</span>
                <span class="rz-hi-val"><?php echo date('d/m/Y', strtotime($ultimoCierre['fecha_hasta'])); ?></span>
            </div>
            <?php endif; ?>
            <button class="rz-btn-generar" id="btnGenerarZ">
                <i class="fas fa-lock"></i>
                Generar Cierre Z
            </button>
        </div>
    </div>

    <!-- ══ STAT CARDS — mes actual ══ -->
    <div class="rz-mes-label">
        <i class="fas fa-calendar-check"></i>
        <?php
        $mesesEs = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        echo 'Resumen de ' . $mesesEs[(int)date('n')] . ' ' . date('Y') . ' — se reinicia cada mes';
        ?>
    </div>
    <div class="rz-stats-grid">
        <div class="rz-stat-card" style="border-left-color:#e74c3c">
            <div class="rz-stat-icon" style="background:#fdedec;color:#e74c3c">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="rz-stat-body">
                <div class="rz-stat-num"><?php echo $mesCierres; ?></div>
                <div class="rz-stat-lbl">Cierres este mes</div>
            </div>
        </div>
        <div class="rz-stat-card" style="border-left-color:#27ae60">
            <div class="rz-stat-icon" style="background:#eafaf1;color:#27ae60">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="rz-stat-body">
                <div class="rz-stat-num">$<?php echo number_format($mesMonto, 0, ',', '.'); ?></div>
                <div class="rz-stat-lbl">Monto del mes</div>
            </div>
        </div>
        <div class="rz-stat-card" style="border-left-color:#3498db">
            <div class="rz-stat-icon" style="background:#ebf5fb;color:#3498db">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="rz-stat-body">
                <div class="rz-stat-num"><?php echo number_format($mesVentas); ?></div>
                <div class="rz-stat-lbl">Ventas del mes</div>
            </div>
        </div>
        <div class="rz-stat-card" style="border-left-color:#e67e22">
            <div class="rz-stat-icon" style="background:#fef5e7;color:#e67e22">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="rz-stat-body">
                <div class="rz-stat-num">$<?php echo number_format($mesPromedio, 0, ',', '.'); ?></div>
                <div class="rz-stat-lbl">Promedio por cierre</div>
            </div>
        </div>
    </div>

    <!-- ══ GRÁFICA ANUAL ══ -->
    <div class="rz-chart-card">
        <div class="rz-chart-header">
            <div class="rz-chart-title">
                <i class="fas fa-chart-line" id="rzChartIcon" style="color:#e74c3c"></i>
                <h2 id="rzChartTitle">Evolución de cierres — <?php echo date('Y'); ?></h2>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <div class="rz-chart-tabs">
                    <button class="rz-ctab rz-ctab-active" data-mode="monto">Monto</button>
                    <button class="rz-ctab" data-mode="cierres">Cierres</button>
                </div>
                <button class="rz-compare-btn" id="btnCompare" onclick="toggleComparacion()">
                    <i class="fas fa-code-compare"></i> Comparar meses
                </button>
            </div>
        </div>

        <!-- Selector de meses (oculto por defecto) -->
        <div id="rzMesesWrap" class="rz-meses-wrap" style="display:none">
            <div class="rz-comp-row rz-comp-row--top">
                <div class="rz-meses-hint">
                    <i class="fas fa-info-circle"></i>
                    Elige año y mes
                </div>
                <button class="rz-meses-clear" onclick="limpiarMeses()">
                    <i class="fas fa-xmark"></i> Limpiar
                </button>
            </div>
            <div class="rz-comp-row">
                <span class="rz-comp-label">Año:</span>
                <div class="rz-anios-pills" id="rzAniosPills"></div>
            </div>
            <div class="rz-comp-row">
                <span class="rz-comp-label">Mes:</span>
                <div class="rz-meses-pills" id="rzMesesPills"></div>
            </div>
            <div class="rz-seleccionados" id="rzSeleccionados" style="display:none">
                <span class="rz-comp-label">Comparando:</span>
                <div class="rz-sel-chips" id="rzSelChips"></div>
            </div>
        </div>

        <div class="rz-chart-wrap" id="rzChartWrap">
            <canvas id="rzChart" height="90"></canvas>
            <div id="rzArrows" class="rz-arrows-row"></div>
        </div>
    </div>

    <!-- ══ TABLA HISTORIAL ══ -->
    <div class="rz-table-card">
        <div class="rz-table-header">
            <div class="rz-table-title">
                <i class="fas fa-history" style="color:#e74c3c"></i>
                <h2>Historial de Cierres Z</h2>
                <?php if (!empty($historial)): ?>
                <span class="rz-count-pill"><?php echo count($historial); ?> registros</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($historial)): ?>
        <div class="rz-empty">
            <div class="rz-empty-ico"><i class="fas fa-file-invoice-dollar"></i></div>
            <h3>Sin cierres registrados</h3>
            <p>Genera el primer Cierre Z con el botón de arriba para comenzar el registro fiscal.</p>
        </div>
        <?php else: ?>
        <div class="rz-table-wrap">
            <table class="rz-table">
                <thead>
                    <tr>
                        <th style="width:100px">#</th>
                        <th>Período</th>
                        <th style="width:120px;text-align:center">Ventas</th>
                        <th style="width:160px;text-align:right">Total</th>
                        <th style="width:140px">Operador</th>
                        <th style="width:80px;text-align:center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $i => $z):
                    $esHoy = date('Y-m-d', strtotime($z['fecha_hasta'])) === date('Y-m-d');
                ?>
                <tr class="rz-tr<?php echo $esHoy ? ' rz-tr-hoy' : ''; ?>">
                    <td>
                        <div class="rz-num-cell">
                            <span class="rz-znum">Z-<?php echo str_pad($z['numero_z'],4,'0',STR_PAD_LEFT); ?></span>
                            <?php if ($i === 0): ?>
                            <span class="rz-tag">Último</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="rz-periodo-cell">
                            <div class="rz-periodo-main">
                                <i class="fas fa-calendar-day"></i>
                                <?php echo date('d/m/Y', strtotime($z['fecha_desde'])); ?>
                                <span class="rz-arrow">→</span>
                                <?php echo date('d/m/Y', strtotime($z['fecha_hasta'])); ?>
                            </div>
                            <div class="rz-periodo-horas">
                                <?php echo date('H:i', strtotime($z['fecha_desde'])); ?>
                                <span class="rz-arrow">→</span>
                                <?php echo date('H:i', strtotime($z['fecha_hasta'])); ?>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <span class="rz-ventas-badge">
                            <i class="fas fa-receipt"></i>
                            <?php echo (int)$z['total_ventas']; ?>
                        </span>
                    </td>
                    <td style="text-align:right">
                        <span class="rz-monto">$<?php echo number_format((float)$z['total_monto'], 0, ',', '.'); ?></span>
                    </td>
                    <td>
                        <div class="rz-op-cell">
                            <div class="rz-op-avatar">
                                <?php echo strtoupper(substr($z['usuario_nombre'] ?? 'S', 0, 1)); ?>
                            </div>
                            <span class="rz-op-name"><?php echo htmlspecialchars($z['usuario_nombre'] ?? 'Sistema'); ?></span>
                        </div>
                    </td>
                    <td style="text-align:center">
                        <button class="rz-btn-ver" onclick="verCierreZ(<?php echo (int)$z['id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ══ MODAL DETALLE Z ══ -->
<div id="zModal" class="zm-overlay">
    <div class="zm-dialog">
        <!-- Header del modal -->
        <div class="zm-header">
            <div class="zm-header-left">
                <div class="zm-header-icon"><i class="fas fa-cash-register"></i></div>
                <div>
                    <div class="zm-header-num" id="zmNum">Cierre Z</div>
                    <div class="zm-header-sub" id="zmSub">Cargando...</div>
                </div>
            </div>
            <button class="zm-close-btn" onclick="cerrarModal()">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <!-- Body scrollable -->
        <div class="zm-body" id="zModalContent">
            <div class="zm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando detalle...</div>
        </div>

        <!-- Footer -->
        <div class="zm-footer">
            <button class="zm-btn-sec" onclick="cerrarModal()">
                <i class="fas fa-xmark"></i> Cerrar
            </button>
            <button class="zm-btn-prim" onclick="imprimirZ()">
                <i class="fas fa-print"></i> Imprimir ticket
            </button>
        </div>
    </div>
</div>

<style>
/* ══════════════════════════════════════════
   CIERRE Z — mismo lenguaje visual del sistema
══════════════════════════════════════════ */
.rz-container {
    padding: 22px;
    background: #f0f2f5;
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── MES LABEL ── */
.rz-mes-label {
    font-size: 12.5px;
    font-weight: 700;
    color: #95a5a6;
    display: flex;
    align-items: center;
    gap: 7px;
    letter-spacing: .3px;
    margin-bottom: -8px;
}
.rz-mes-label i { color: #e74c3c; }

/* ── CHART CARD ── */
.rz-chart-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
}
.rz-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 2px solid #f0f2f5;
    flex-wrap: wrap;
    gap: 10px;
}
.rz-chart-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.rz-chart-title h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #2c3e50;
}
.rz-chart-tabs {
    display: flex;
    background: #f0f2f5;
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
}
.rz-ctab {
    background: none;
    border: none;
    border-radius: 6px;
    padding: 6px 16px;
    font-size: 12.5px;
    font-weight: 700;
    color: #7f8c8d;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.rz-ctab-active {
    background: #fff;
    color: #2c3e50;
    box-shadow: 0 1px 4px rgba(0,0,0,.1);
}
.rz-chart-wrap {
    padding: 20px 22px 52px;
    position: relative;
}
.rz-arrows-row {
    position: absolute;
    bottom: 6px;
    left: 0; right: 0;
    pointer-events: none;
}
.rz-arrow-cell {
    position: absolute;
    text-align: center;
    font-size: 10px;
    font-weight: 800;
    line-height: 1.3;
    transform: translateX(-50%);
    white-space: nowrap;
}

/* ── Botón comparar ── */
.rz-compare-btn {
    background: #f0f2f5;
    color: #636e72;
    border: 1.5px solid #e0e4e8;
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: .15s;
    white-space: nowrap;
}
.rz-compare-btn:hover,
.rz-compare-btn.active {
    background: #2c3e50;
    color: #fff;
    border-color: #2c3e50;
}

/* ── Selector comparación (año + mes) ── */
.rz-meses-wrap {
    padding: 12px 22px;
    background: #f8f9fa;
    border-top: 1.5px solid #f0f2f5;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.rz-comp-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.rz-comp-row--top {
    justify-content: space-between;
}
.rz-comp-label {
    font-size: 11px;
    font-weight: 800;
    color: #95a5a6;
    text-transform: uppercase;
    letter-spacing: .4px;
    white-space: nowrap;
    min-width: 38px;
}
.rz-meses-hint {
    font-size: 12px;
    color: #95a5a6;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}
.rz-anios-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.rz-anio-pill {
    padding: 4px 14px;
    border-radius: 20px;
    border: 1.5px solid #dfe6e9;
    background: #fff;
    color: #7f8c8d;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .15s;
    user-select: none;
}
.rz-anio-pill:hover  { border-color: #2c3e50; color: #2c3e50; }
.rz-anio-pill.active { background: #2c3e50; border-color: #2c3e50; color: #fff; }

.rz-meses-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    flex: 1;
}
.rz-mes-pill {
    padding: 5px 13px;
    border-radius: 20px;
    border: 1.5px solid #dfe6e9;
    background: #fff;
    color: #7f8c8d;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .15s;
    user-select: none;
}
.rz-mes-pill:hover { border-color: #2c3e50; color: #2c3e50; }
.rz-mes-pill.selected {
    border-color: var(--pill-color, #2c3e50);
    color: var(--pill-color, #2c3e50);
    background: #fff;
    box-shadow: inset 0 0 0 1.5px var(--pill-color, #2c3e50);
}
.rz-mes-pill.sin-datos { opacity: .35; cursor: default; pointer-events: none; }

.rz-seleccionados {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding-top: 4px;
    border-top: 1px solid #eaeef2;
}
.rz-sel-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.rz-sel-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    cursor: default;
}
.rz-sel-chip button {
    background: rgba(255,255,255,.35);
    border: none;
    border-radius: 50%;
    width: 16px; height: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px;
    cursor: pointer;
    color: #fff;
    padding: 0;
    line-height: 1;
    transition: background .15s;
}
.rz-sel-chip button:hover { background: rgba(255,255,255,.55); }

.rz-meses-clear {
    background: none;
    border: 1.5px solid #dfe6e9;
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 700;
    color: #95a5a6;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
    transition: .15s;
}
.rz-meses-clear:hover { border-color: #e74c3c; color: #e74c3c; }

/* ── HEADER ── */
.rz-header {
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
.rz-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.rz-header-icon-wrap {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.25);
}
.rz-header h1 { margin: 0 0 3px; font-size: 22px; font-weight: 800; }
.rz-header p  { margin: 0; font-size: 13px; opacity: .8; }

.rz-header-right {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.rz-header-info { text-align: center; }
.rz-hi-label { display: block; font-size: 11px; opacity: .7; text-transform: uppercase; letter-spacing: .5px; }
.rz-hi-val   { display: block; font-size: 15px; font-weight: 800; margin-top: 1px; }

.rz-btn-generar {
    background: #fff;
    color: #c0392b;
    border: none;
    border-radius: 10px;
    padding: 12px 22px;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    display: flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
    transition: transform .15s, box-shadow .15s;
    white-space: nowrap;
}
.rz-btn-generar:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(0,0,0,.2);
}

/* ── STAT CARDS ── */
.rz-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.rz-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 20px;
    border-left: 4px solid #e0e0e0;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform .2s, box-shadow .2s;
}
.rz-stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,.1); }
.rz-stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.rz-stat-num {
    font-size: 26px;
    font-weight: 800;
    color: #2c3e50;
    line-height: 1;
}
.rz-stat-lbl {
    font-size: 12px;
    color: #95a5a6;
    margin-top: 4px;
    font-weight: 600;
}

/* ── TABLE CARD ── */
.rz-table-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
}
.rz-table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    border-bottom: 2px solid #f0f2f5;
}
.rz-table-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.rz-table-title h2 {
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
    font-weight: 700;
}
.rz-count-pill {
    background: #f0f2f5;
    color: #7f8c8d;
    font-size: 12px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}
.rz-table-wrap { overflow-x: auto; }
.rz-table {
    width: 100%;
    border-collapse: collapse;
}
.rz-table thead tr {
    background: #2c3e50;
    color: #fff;
}
.rz-table th {
    padding: 13px 18px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}
.rz-table tbody tr { border-bottom: 1px solid #f5f6fa; transition: background .15s; }
.rz-table tbody tr:hover { background: #fef5f5; }
.rz-table td { padding: 14px 18px; vertical-align: middle; font-size: 13.5px; }
.rz-tr-hoy { background: #fffbf0; }
.rz-tr-hoy:hover { background: #fff8e6 !important; }

/* Celdas de tabla */
.rz-num-cell { display: flex; flex-direction: column; gap: 4px; }
.rz-znum {
    font-family: 'Courier New', monospace;
    font-size: 15px;
    font-weight: 900;
    color: #e74c3c;
    letter-spacing: .5px;
}
.rz-tag {
    display: inline-block;
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: #fff;
    font-size: 9px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .5px;
    width: fit-content;
}

.rz-periodo-cell { display: flex; flex-direction: column; gap: 3px; }
.rz-periodo-main {
    font-size: 13.5px;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 6px;
}
.rz-periodo-main i { color: #bdc3c7; font-size: 12px; }
.rz-periodo-horas { font-size: 12px; color: #95a5a6; display: flex; align-items: center; gap: 6px; padding-left: 18px; }
.rz-arrow { color: #bdc3c7; }

.rz-ventas-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #edf5ff;
    color: #2980b9;
    font-size: 13px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
}
.rz-ventas-badge i { font-size: 11px; }

.rz-monto {
    font-size: 18px;
    font-weight: 900;
    color: #27ae60;
}

.rz-op-cell { display: flex; align-items: center; gap: 9px; }
.rz-op-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.rz-op-name { font-size: 13px; color: #2c3e50; font-weight: 600; }

.rz-btn-ver {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: #2c3e50;
    color: #fff;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: background .15s, transform .15s;
}
.rz-btn-ver:hover { background: #1a252f; transform: scale(1.08); }

/* Empty */
.rz-empty {
    text-align: center;
    padding: 70px 20px;
}
.rz-empty-ico {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    font-size: 32px;
    color: #bdc3c7;
}
.rz-empty h3 { font-size: 18px; font-weight: 700; color: #2c3e50; margin: 0 0 8px; }
.rz-empty p  { font-size: 14px; color: #95a5a6; line-height: 1.7; margin: 0; }

/* ══ MODAL ══ */
.zm-overlay {
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
.zm-overlay.open { display: flex; }

.zm-dialog {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 30px 90px rgba(0,0,0,.3);
    overflow: hidden;
}

.zm-header {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.zm-header-left { display: flex; align-items: center; gap: 14px; }
.zm-header-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    color: #fff;
    border: 1.5px solid rgba(255,255,255,.25);
    flex-shrink: 0;
}
.zm-header-num {
    font-size: 18px;
    font-weight: 900;
    color: #fff;
    font-family: 'Courier New', monospace;
}
.zm-header-sub { font-size: 12px; color: rgba(255,255,255,.75); margin-top: 2px; }

.zm-close-btn {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 1.5px solid rgba(255,255,255,.3);
    background: rgba(255,255,255,.12);
    color: #fff;
    font-size: 17px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
}
.zm-close-btn:hover { background: rgba(255,255,255,.25); }

.zm-body {
    overflow-y: auto;
    flex: 1;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.zm-loading { text-align: center; padding: 48px; color: #95a5a6; font-size: 15px; }

/* Secciones del modal */
.zm-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.zm-kpi {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 16px 14px;
    text-align: center;
    border: 1px solid #f0f2f5;
}
.zm-kpi-n {
    font-size: 22px;
    font-weight: 900;
    color: #2c3e50;
    line-height: 1;
}
.zm-kpi-n--green { color: #27ae60; }
.zm-kpi-l {
    font-size: 11px;
    color: #95a5a6;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-top: 5px;
}

.zm-section { display: flex; flex-direction: column; gap: 2px; }
.zm-section-title {
    font-size: 11px;
    font-weight: 800;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: .7px;
    padding-bottom: 8px;
    border-bottom: 2px solid #f0f2f5;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
}
.zm-section-title i { color: #bdc3c7; }

.zm-row {
    display: flex;
    align-items: center;
    padding: 10px 2px;
    border-bottom: 1px solid #f8f9fa;
    gap: 8px;
}
.zm-row:last-child { border-bottom: none; }
.zm-row-rank {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #f0f2f5;
    color: #7f8c8d;
    font-size: 11px;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.zm-row-name {
    flex: 1;
    font-size: 13.5px;
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 7px;
}
.zm-row-sub { font-size: 11.5px; color: #bdc3c7; min-width: 52px; text-align: right; }
.zm-row-val { font-size: 14px; font-weight: 800; color: #27ae60; min-width: 86px; text-align: right; }

.zm-total {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    border-radius: 14px;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #fff;
    margin-top: 2px;
}
.zm-total-label { font-size: 12px; opacity: .7; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.zm-total-num   { font-size: 28px; font-weight: 900; letter-spacing: -1px; }

.zm-footer {
    padding: 16px 24px;
    border-top: 1px solid #f0f2f5;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-shrink: 0;
}
.zm-btn-prim {
    background: linear-gradient(135deg, #2c3e50, #34495e);
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
.zm-btn-prim:hover { opacity: .88; transform: translateY(-1px); }
.zm-btn-sec {
    background: #f0f2f5;
    color: #636e72;
    border: none;
    border-radius: 9px;
    padding: 11px 22px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    transition: background .15s;
}
.zm-btn-sec:hover { background: #dfe6e9; color: #2c3e50; }

/* Ticket impresión */
#zTicket { display: none; }
@page { size: 80mm auto; margin: 4mm; }
@media print {
    body * { visibility: hidden; }
    #zTicket {
        display: block !important;
        visibility: visible;
        position: fixed;
        top: 0; left: 0;
        width: 72mm;
        font-family: 'Courier New', Courier, monospace;
        font-size: 9.5pt;
        color: #000;
        white-space: pre;
        line-height: 1.45;
    }
}

@media (max-width: 640px) {
    .rz-stats-grid { grid-template-columns: 1fr 1fr; }
    .rz-header { flex-direction: column; align-items: flex-start; }
    .rz-header-right { width: 100%; justify-content: space-between; }
    .zm-kpis { grid-template-columns: 1fr 1fr; }
    .rz-table th:nth-child(5), .rz-table td:nth-child(5) { display: none; }
}
</style>

<div id="zTicket"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
(function(){
    const BP       = <?php echo json_encode($basePath); ?>;
    const COMERCIO = <?php echo json_encode($comercio ?? []); ?>;

    /* ── Gráfica anual ── */
    const MESES_LABEL     = <?php echo json_encode($mesesLabel); ?>;
    const GRAFICA_MONTO   = <?php echo json_encode(array_values($graficaMonto)); ?>;
    const GRAFICA_CIERRES = <?php echo json_encode(array_values($graficaCierres)); ?>;
    const MES_ACTUAL      = <?php echo $mesActualIdx; ?>;
    const ANIO_ACTUAL     = <?php echo date('Y'); ?>;

    // Paleta de colores para comparación
    const COLORES = [
        '#3498db','#e74c3c','#27ae60','#e67e22','#9b59b6',
        '#1abc9c','#f39c12','#2980b9','#c0392b','#16a085','#8e44ad','#d35400'
    ];

    // Caché de datos por año: { 2025: {monto:[...], cierres:[...]}, ... }
    const dataCache = {};
    dataCache[ANIO_ACTUAL] = { monto: GRAFICA_MONTO.slice(), cierres: GRAFICA_CIERRES.slice() };

    const ANIOS_DISPONIBLES = <?php echo json_encode(!empty($aniosDisponibles) ? $aniosDisponibles : [(int)date('Y')]); ?>;

    Chart.register(ChartDataLabels);

    const ctx = document.getElementById('rzChart').getContext('2d');

    const grad = ctx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(52,152,219,.22)');
    grad.addColorStop(1, 'rgba(52,152,219,.02)');

    let chartMode       = 'monto';
    let modoComparacion = false;

    function fmtVal(v) {
        if (v === 0) return '';
        if (v >= 1000000) return '$' + (v/1000000).toFixed(1) + 'M';
        if (v >= 1000)    return '$' + (v/1000).toFixed(0) + 'k';
        return '$' + v.toLocaleString('es-CO');
    }
    function fmtK(v) {
        const abs = Math.abs(v);
        if (abs >= 1000000) return '$' + (abs/1000000).toFixed(1)+'M';
        if (abs >= 1000)    return '$' + (abs/1000).toFixed(0)+'k';
        return '$' + abs.toLocaleString('es-CO');
    }

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: MESES_LABEL,
            datasets: [{
                label: 'Monto',
                data: GRAFICA_MONTO,
                borderColor: '#3498db',
                backgroundColor: grad,
                borderWidth: 3,
                pointRadius: MESES_LABEL.map((_, i) => i === MES_ACTUAL ? 8 : (GRAFICA_MONTO[i] > 0 ? 5 : 3)),
                pointBackgroundColor: MESES_LABEL.map((_, i) => i === MES_ACTUAL ? '#3498db' : '#fff'),
                pointBorderColor: '#3498db',
                pointBorderWidth: 2.5,
                tension: 0.42,
                fill: true,
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
                    bodyColor: '#aab7c4',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: c => {
                            const v = c.parsed.y;
                            return chartMode === 'monto'
                                ? ' $' + v.toLocaleString('es-CO', {minimumFractionDigits:0})
                                : ' ' + v + ' cierre' + (v !== 1 ? 's' : '');
                        }
                    }
                },
                datalabels: {
                    display: c => c.dataset.data[c.dataIndex] > 0,
                    anchor: 'end',
                    align: 'end',
                    offset: 4,
                    color: c => c.dataIndex === MES_ACTUAL ? '#2980b9' : '#5d6d7e',
                    font: { size: 10.5, weight: '700' },
                    formatter: (v) => chartMode === 'monto' ? fmtVal(v) : (v > 0 ? v : ''),
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: c => c.index === MES_ACTUAL ? '#2980b9' : '#95a5a6',
                        font: c => ({ size: 12, weight: c.index === MES_ACTUAL ? '800' : '500' })
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
                            ? '$' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'k' : v)
                            : v
                    },
                    border: { display: false }
                }
            },
            layout: { padding: { top: 22 } },
            animation: { onComplete: () => { if (!modoComparacion) buildArrows(currentData()); } }
        }
    });

    function currentData() {
        return chartMode === 'monto' ? GRAFICA_MONTO : GRAFICA_CIERRES;
    }

    function buildArrows(data) {
        const row = document.getElementById('rzArrows');
        if (!row) return;
        row.innerHTML = '';
        const meta = chart.getDatasetMeta(0);
        const ca   = chart.chartArea;
        if (!ca) return;

        data.forEach((val, i) => {
            const pt = meta.data[i];
            if (!pt) return;
            const x    = pt.x;
            const prev = i > 0 ? data[i - 1] : null;
            if (val === 0 && (prev === null || prev === 0)) return;

            const cell = document.createElement('div');
            cell.className = 'rz-arrow-cell';
            cell.style.left = x + 'px';

            const delta = prev !== null ? val - prev : null;

            if (delta === null || (prev === 0 && val > 0)) {
                cell.style.color = '#3498db';
                cell.innerHTML = '●';
            } else if (delta > 0) {
                cell.style.color = '#27ae60';
                cell.innerHTML = `↑<br><span style="color:#27ae60">+${fmtK(delta)}</span>`;
            } else if (delta < 0) {
                cell.style.color = '#e74c3c';
                cell.innerHTML = `↓<br><span style="color:#e74c3c">${fmtK(delta)}</span>`;
            } else {
                cell.style.color = '#3498db';
                cell.innerHTML = `=<br><span style="color:#3498db">${fmtK(val)}</span>`;
            }
            row.appendChild(cell);
        });
    }

    /* ── Modo comparación (cruzado año/mes) ─────────────────────────── */
    let anioViendo  = ANIO_ACTUAL;
    let seleccion   = []; // [{year, monthIdx, key:"YYYY-M", color}]

    function selKey(year, monthIdx) { return year + '-' + monthIdx; }

    async function fetchAnio(year) {
        if (dataCache[year]) return dataCache[year];
        try {
            const r = await fetch(BP + '/reportes/grafica-z?anio=' + year);
            const j = await r.json();
            if (j.success) {
                dataCache[year] = {
                    monto:   Array.from({length:12}, (_, i) => parseFloat(j.data[i+1]?.monto   ?? 0)),
                    cierres: Array.from({length:12}, (_, i) => parseInt(j.data[i+1]?.cierres ?? 0)),
                };
            }
        } catch(e) {}
        return dataCache[year] || { monto: Array(12).fill(0), cierres: Array(12).fill(0) };
    }

    window.toggleComparacion = function() {
        modoComparacion = !modoComparacion;
        const btn  = document.getElementById('btnCompare');
        const wrap = document.getElementById('rzMesesWrap');
        btn.classList.toggle('active', modoComparacion);
        wrap.style.display = modoComparacion ? 'flex' : 'none';
        document.getElementById('rzChartIcon').className = modoComparacion
            ? 'fas fa-chart-bar' : 'fas fa-chart-line';
        document.getElementById('rzChartIcon').style.color = modoComparacion ? '#2c3e50' : '#e74c3c';

        if (!modoComparacion) {
            seleccion = [];
            renderLineChart();
        } else {
            buildAniosPills();
            buildMesesPills();
            renderSelChips();
        }
    };

    function buildAniosPills() {
        const container = document.getElementById('rzAniosPills');
        container.innerHTML = '';
        const anios = ANIOS_DISPONIBLES.length ? ANIOS_DISPONIBLES : [ANIO_ACTUAL];
        anios.forEach(y => {
            const p = document.createElement('button');
            p.className = 'rz-anio-pill' + (y === anioViendo ? ' active' : '');
            p.textContent = y;
            p.addEventListener('click', async () => {
                anioViendo = y;
                document.querySelectorAll('.rz-anio-pill').forEach(x => x.classList.remove('active'));
                p.classList.add('active');
                await fetchAnio(y);
                buildMesesPills();
            });
            container.appendChild(p);
        });
    }

    function buildMesesPills() {
        const container = document.getElementById('rzMesesPills');
        container.innerHTML = '';
        const cache    = dataCache[anioViendo] || { monto: Array(12).fill(0), cierres: Array(12).fill(0) };
        const fullData = chartMode === 'monto' ? cache.monto : cache.cierres;

        MESES_LABEL.forEach((lbl, i) => {
            const key  = selKey(anioViendo, i);
            const sel  = seleccion.find(s => s.key === key);
            const sinD = fullData[i] === 0;

            const pill = document.createElement('button');
            pill.className = 'rz-mes-pill' + (sinD ? ' sin-datos' : '');
            pill.textContent = lbl;
            pill.dataset.key = key;
            if (sel) {
                pill.classList.add('selected');
                pill.style.setProperty('--pill-color', sel.color);
            }
            if (!sinD) {
                pill.addEventListener('click', () => toggleMes(anioViendo, i));
            }
            container.appendChild(pill);
        });
    }

    function toggleMes(year, monthIdx) {
        const key = selKey(year, monthIdx);
        const idx = seleccion.findIndex(s => s.key === key);
        if (idx >= 0) {
            seleccion.splice(idx, 1);
        } else {
            const color = COLORES[seleccion.length % COLORES.length];
            seleccion.push({ year, monthIdx, key, color });
        }
        buildMesesPills();
        renderSelChips();
        renderComparacion();
    }

    function renderSelChips() {
        const wrap   = document.getElementById('rzSeleccionados');
        const chips  = document.getElementById('rzSelChips');
        if (!seleccion.length) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'flex';
        chips.innerHTML = '';
        seleccion.forEach(s => {
            const chip = document.createElement('span');
            chip.className = 'rz-sel-chip';
            chip.style.background = s.color;
            const yr  = String(s.year).slice(-2);
            const lbl = MESES_LABEL[s.monthIdx] + " '" + yr;
            chip.innerHTML = lbl + ' <button title="Quitar">&times;</button>';
            chip.querySelector('button').addEventListener('click', () => {
                seleccion = seleccion.filter(x => x.key !== s.key);
                buildMesesPills();
                renderSelChips();
                renderComparacion();
            });
            chips.appendChild(chip);
        });
    }

    window.limpiarMeses = function() {
        seleccion = [];
        buildMesesPills();
        renderSelChips();
        renderLineChart();
    };

    function renderComparacion() {
        if (!seleccion.length) { renderLineChart(); return; }

        document.getElementById('rzArrows').innerHTML = '';
        const yr2 = y => "'" + String(y).slice(-2);
        const labels   = seleccion.map(s => MESES_LABEL[s.monthIdx] + ' ' + yr2(s.year));
        const vals     = seleccion.map(s => {
            const c = dataCache[s.year] || { monto: Array(12).fill(0), cierres: Array(12).fill(0) };
            return chartMode === 'monto' ? c.monto[s.monthIdx] : c.cierres[s.monthIdx];
        });
        const colors = seleccion.map(s => s.color);

        chart.config.type = 'bar';
        chart.data.labels   = labels;
        chart.data.datasets = [{
            label: chartMode === 'monto' ? 'Monto' : 'Cierres',
            data: vals,
            backgroundColor: colors.map(c => c + 'cc'),
            borderColor: colors,
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }];

        chart.options.plugins.datalabels = {
            display: true,
            anchor: 'end', align: 'end', offset: 4,
            color: c => seleccion[c.dataIndex]?.color ?? '#636e72',
            font: { size: 11, weight: '800' },
            formatter: v => chartMode === 'monto' ? fmtVal(v) : (v > 0 ? String(v) : ''),
        };
        chart.options.plugins.legend.display = false;
        chart.options.animation = {};
        chart.options.scales.x.ticks.color = c => seleccion[c.index]?.color ?? '#95a5a6';
        chart.options.scales.x.ticks.font  = () => ({ size: 12, weight: '700' });

        document.getElementById('rzChartTitle').textContent =
            'Comparando: ' + labels.join(' vs ');

        chart.update();
    }

    function renderLineChart() {
        document.getElementById('rzChartTitle').textContent =
            'Evolución de cierres — <?php echo date('Y'); ?>';

        const data = currentData();
        chart.config.type = 'line';
        chart.data.labels = MESES_LABEL;
        chart.data.datasets = [{
            label: chartMode === 'monto' ? 'Monto' : 'Cierres',
            data,
            borderColor: '#3498db',
            backgroundColor: grad,
            borderWidth: 3,
            pointRadius: MESES_LABEL.map((_, i) => i === MES_ACTUAL ? 8 : (data[i] > 0 ? 5 : 3)),
            pointBackgroundColor: MESES_LABEL.map((_, i) => i === MES_ACTUAL ? '#3498db' : '#fff'),
            pointBorderColor: '#3498db',
            pointBorderWidth: 2.5,
            tension: 0.42,
            fill: true,
        }];

        chart.options.plugins.datalabels = {
            display: c => c.dataset.data[c.dataIndex] > 0,
            anchor: 'end', align: 'end', offset: 4,
            color: c => c.dataIndex === MES_ACTUAL ? '#2980b9' : '#5d6d7e',
            font: { size: 10.5, weight: '700' },
            formatter: v => chartMode === 'monto' ? fmtVal(v) : (v > 0 ? v : ''),
        };
        chart.options.plugins.legend.display = false;
        chart.options.animation = { onComplete: () => { if (!modoComparacion) buildArrows(data); } };
        chart.options.scales.x.ticks.color = c => c.index === MES_ACTUAL ? '#2980b9' : '#95a5a6';
        chart.options.scales.x.ticks.font  = c => ({ size: 12, weight: c.index === MES_ACTUAL ? '800' : '500' });

        chart.update();
    }

    // Tab switch (monto / cierres)
    document.querySelectorAll('.rz-ctab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.rz-ctab').forEach(b => b.classList.remove('rz-ctab-active'));
            btn.classList.add('rz-ctab-active');
            chartMode = btn.dataset.mode;
            if (modoComparacion) {
                buildMesesPills();
                if (seleccion.length) renderComparacion();
                else renderLineChart();
            } else {
                const data = currentData();
                chart.data.datasets[0].data = data;
                chart.data.datasets[0].label = chartMode === 'monto' ? 'Monto' : 'Cierres';
                chart.data.datasets[0].pointRadius = MESES_LABEL.map((_, i) => i === MES_ACTUAL ? 8 : (data[i] > 0 ? 5 : 3));
                chart.options.plugins.datalabels.formatter = v => chartMode === 'monto' ? fmtVal(v) : (v > 0 ? String(v) : '');
                chart.update();
            }
        });
    });

    /* ── Generar Z + popup impresión ── */
    document.getElementById('btnGenerarZ').addEventListener('click', function () {
        Swal.fire({
            title: '¿Generar Cierre Z?',
            html: 'Se registrará un cierre fiscal con todas las ventas del período actual.<br><br><strong style="color:#e74c3c">Esta acción no se puede deshacer.</strong>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor:  '#95a5a6',
            confirmButtonText:  '<i class="fas fa-lock"></i> Sí, generar cierre',
            cancelButtonText:   'Cancelar',
        }).then(res => {
            if (!res.isConfirmed) return;
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
            fetch(BP + '/reportes/generar-z', { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock"></i> Generar Cierre Z';
                    if (data.success) {
                        const d    = data.data;
                        const numZ = 'Z-' + String(d.numero_z).padStart(4,'0');
                        const monto = parseFloat(d.totales?.total_monto || 0).toLocaleString('es-CO');
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cierre Z generado!',
                            html: `<strong>${numZ}</strong> — $${monto}<br><br>
                                   <span style="color:#7f8c8d;font-size:13px">¿Desea imprimir el reporte de cierre?</span>`,
                            showDenyButton: true,
                            showCancelButton: false,
                            confirmButtonText: '<i class="fas fa-print"></i> Sí, imprimir',
                            denyButtonText:    '<i class="fas fa-xmark"></i> No, continuar',
                            confirmButtonColor: '#2c3e50',
                            denyButtonColor:    '#95a5a6',
                        }).then(r2 => {
                            if (r2.isConfirmed) {
                                const zObj = {
                                    numero_z:      d.numero_z,
                                    fecha_desde:   d.desde,
                                    fecha_hasta:   d.hasta,
                                    usuario_nombre: '<?php echo addslashes($_SESSION['usuario_nombre'] ?? 'Sistema'); ?>',
                                    datos: {
                                        general:      d.totales,
                                        por_mesero:   d.porMesero   || [],
                                        por_producto: d.porProducto || []
                                    }
                                };
                                printTicketWindow(zObj);
                                setTimeout(() => location.reload(), 600);
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock"></i> Generar Cierre Z';
                    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor.' });
                });
        });
    });

    let currentZ = null;

    /* ── Ver detalle ── */
    window.verCierreZ = async function(id) {
        document.getElementById('zmNum').textContent = 'Cierre Z';
        document.getElementById('zmSub').textContent = 'Cargando...';
        document.getElementById('zModalContent').innerHTML = '<div class="zm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando detalle...</div>';
        document.getElementById('zModal').classList.add('open');
        try {
            const r    = await fetch(BP + '/reportes/ver-z/' + id);
            const data = await r.json();
            if (!data.success) throw new Error(data.message || 'Error');
            currentZ = data.data;
            renderZ(currentZ);
        } catch(e) {
            document.getElementById('zModalContent').innerHTML =
                '<div class="zm-loading" style="color:#e74c3c"><i class="fas fa-circle-exclamation"></i> Error al cargar el cierre</div>';
        }
    };

    function renderZ(z) {
        const d   = z.datos   || {};
        const g   = d.general || {};
        const mes = d.por_mesero   || [];
        const pro = d.por_producto || [];

        const num      = 'Z-' + String(z.numero_z).padStart(4,'0');
        const usuario  = z.usuario_nombre || 'Sistema';
        const totalV   = parseInt(g.total_ventas || 0);
        const totalM   = parseFloat(g.total_monto || 0);
        const prom     = totalV > 0 ? totalM / totalV : 0;

        document.getElementById('zmNum').textContent = num;
        document.getElementById('zmSub').textContent =
            fmtDate(z.fecha_desde) + ' → ' + fmtDate(z.fecha_hasta) + ' · Por: ' + usuario;

        const mesHTML = mes.length
            ? mes.map((m, i) => `
                <div class="zm-row">
                    <div class="zm-row-rank">${i+1}</div>
                    <div class="zm-row-name"><i class="fas fa-user-tie" style="color:#e67e22;font-size:12px"></i>${esc(m.mesero)}</div>
                    <div class="zm-row-sub">${m.ventas} vta${m.ventas!=1?'s':''}</div>
                    <div class="zm-row-val">$${fmt(m.monto)}</div>
                </div>`).join('')
            : '<div class="zm-row"><span style="color:#bdc3c7">Sin registros</span></div>';

        const proHTML = pro.length
            ? pro.map((p, i) => `
                <div class="zm-row">
                    <div class="zm-row-rank">${i+1}</div>
                    <div class="zm-row-name">${esc(p.nombre)}</div>
                    <div class="zm-row-sub">${p.unidades} uds</div>
                    <div class="zm-row-val">$${fmt(p.monto)}</div>
                </div>`).join('')
            : '<div class="zm-row"><span style="color:#bdc3c7">Sin productos</span></div>';

        document.getElementById('zModalContent').innerHTML = `
            <div class="zm-kpis">
                <div class="zm-kpi">
                    <div class="zm-kpi-n">${totalV}</div>
                    <div class="zm-kpi-l">Ventas</div>
                </div>
                <div class="zm-kpi">
                    <div class="zm-kpi-n zm-kpi-n--green">$${fmt(totalM)}</div>
                    <div class="zm-kpi-l">Total</div>
                </div>
                <div class="zm-kpi">
                    <div class="zm-kpi-n">$${fmt(prom)}</div>
                    <div class="zm-kpi-l">Promedio</div>
                </div>
            </div>

            <div class="zm-section">
                <div class="zm-section-title"><i class="fas fa-user-tie"></i> Por mesero / cajero</div>
                ${mesHTML}
            </div>

            <div class="zm-section">
                <div class="zm-section-title"><i class="fas fa-utensils"></i> Productos vendidos</div>
                ${proHTML}
            </div>

            <div class="zm-total">
                <div>
                    <div class="zm-total-label">Total del cierre</div>
                    <div style="font-size:13px;opacity:.6;margin-top:2px">${num}</div>
                </div>
                <div class="zm-total-num">$${fmt(totalM)}</div>
            </div>`;
    }

    function fmt(n) {
        return parseFloat(n||0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    function fmtDate(dt) {
        const d = new Date(String(dt).replace(' ','T'));
        return d.toLocaleDateString('es-MX', {day:'2-digit',month:'2-digit',year:'numeric'})
             + ' ' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    }
    function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    window.cerrarModal = () => {
        document.getElementById('zModal').classList.remove('open');
        currentZ = null;
    };
    document.getElementById('zModal').addEventListener('click', e => {
        if (e.target === document.getElementById('zModal')) cerrarModal();
    });

    /* ── Imprimir en ventana emergente (evita interferencia del DOM principal) ── */
    function printTicketWindow(z) {
        const content = buildTicket(z);
        const win = window.open('', '_blank', 'width=420,height=680,menubar=no,toolbar=no,scrollbars=yes');
        if (!win) { alert('Permite ventanas emergentes para imprimir.'); return; }
        win.document.write(
            '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
            '<title>Cierre Z-' + String(z.numero_z).padStart(4,'0') + '</title>' +
            '<style>' +
            '  body { margin:0; padding:4mm; font-family:"Courier New",Courier,monospace;' +
            '         font-size:9.5pt; line-height:1.45; color:#000; white-space:pre; }' +
            '  @page { size:80mm auto; margin:4mm; }' +
            '</style></head><body>' + escHtml(content) + '</body></html>'
        );
        win.document.close();
        win.focus();
        // Esperar a que cargue el contenido antes de imprimir
        win.onload = function() { win.print(); };
        // Fallback si onload ya disparó
        setTimeout(function() { try { win.print(); } catch(e){} }, 400);
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    window.imprimirZ = function() {
        if (!currentZ) return;
        printTicketWindow(currentZ);
    };

    function buildTicket(z) {
        const W   = 38;
        const SEP = '*'.repeat(W);
        const LIN = '-'.repeat(W);
        const d   = z.datos   || {};
        const g   = d.general || {};
        const mes = d.por_mesero   || [];
        const pro = d.por_producto || [];
        const num    = 'Z-' + String(z.numero_z).padStart(4,'0');
        const fDesde = tkDate(z.fecha_desde);
        const fHasta = tkDate(z.fecha_hasta);
        const user   = z.usuario_nombre || 'Sistema';
        const ahora  = tkDate(new Date().toISOString().replace('T',' ').slice(0,19));
        const totalV = parseInt(g.total_ventas || 0);
        const totalM = parseFloat(g.total_monto || 0);

        const center = s => { s=String(s); const p=Math.max(0,Math.floor((W-s.length)/2)); return ' '.repeat(p)+s; };
        const cols   = (l,r) => { l=String(l);r=String(r); const sp=Math.max(1,W-l.length-r.length); return l+' '.repeat(sp)+r; };
        function tkDate(dt) {
            const d=new Date(String(dt).replace(' ','T'));
            return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+String(d.getFullYear()).slice(-2)
                  +' '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');
        }
        const nombre = (COMERCIO.nombre||'MI NEGOCIO').toUpperCase();
        const lines  = [SEP, center(nombre)];
        if (COMERCIO.eslogan)   lines.push(center(COMERCIO.eslogan));
        if (COMERCIO.rut)       lines.push(center('RUT: '+COMERCIO.rut));
        if (COMERCIO.direccion) lines.push(center(COMERCIO.direccion));
        if (COMERCIO.telefono)  lines.push(center('Tel: '+COMERCIO.telefono));
        lines.push(SEP, center('INFORME ZETA NRO: '+String(z.numero_z).padStart(4,'0')), SEP);
        lines.push(cols(fDesde, 'CAJERO: '+user.split(' ')[0].slice(0,8)), '');
        lines.push('INFORME DE TOTALES FISCALES', LIN);
        lines.push(cols('VENTAS', String(totalV)));
        lines.push(cols('TOTAL MONTO', '$'+totalM.toFixed(2)));
        lines.push(cols('TICKET PROMEDIO', '$'+(totalV>0?(totalM/totalV).toFixed(2):'0.00')), '');
        lines.push('PERIODO', LIN, cols('DESDE:',fDesde), cols('HASTA:',fHasta), '');
        if (mes.length) {
            lines.push('TOTALES POR CAJERO/MESERO', LIN);
            mes.forEach(m => lines.push(cols(String(m.mesero).slice(0,18)+' ('+m.ventas+'vtas)', '$'+parseFloat(m.monto).toFixed(2))));
            lines.push('');
        }
        lines.push('PRODUCTOS VENDIDOS', LIN);
        if (!pro.length) { lines.push(center('Sin productos')); }
        else pro.forEach(p => {
            const nm=String(p.nombre).slice(0,22), uni=String(p.unidades)+'uds', mn='$'+parseFloat(p.monto).toFixed(2);
            if (nm.length+uni.length+mn.length+2>W) { lines.push(nm); lines.push(cols('  '+uni,mn)); }
            else lines.push(cols(nm+' '+uni,mn));
        });
        lines.push('', SEP, cols('TOTAL CIERRE '+num,'$'+totalM.toFixed(2)), SEP, '');
        lines.push(center('Impreso: '+ahora), center('** DOCUMENTO NO FISCAL **'), '');
        lines.push(SEP, center('CHEFCONTROL'), center('Creado por'), center('CLOUD CONTROL TECNOLOGYS'), SEP, '');
        return lines.join('\n');
    }
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
