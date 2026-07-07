<?php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Propinas - CHEFCONTROL';
$paginaActual = 'propinas';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<style>
:root {
    --bg:       #f2f2f7;
    --card:     #ffffff;
    --accent:   #f0a500;
    --accent-d: #c87d00;
    --green:    #34c759;
    --label:    #8e8e93;
    --body:     #1c1c1e;
    --sep:      rgba(60,60,67,.12);
    --radius:   16px;
    --font:     -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", sans-serif;
}
.pr-page { font-family:var(--font); padding:28px 32px 48px; color:var(--body); background:var(--bg); min-height:calc(100vh - 56px); }

/* ── Top row: hero + stat cards ── */
.pr-top { display:grid; grid-template-columns:1fr auto auto auto; gap:16px; margin-bottom:20px; align-items:stretch; }
@media(max-width:860px){ .pr-top { grid-template-columns:1fr; } }

/* ── Hero card ── */
.pr-hero {
    background:linear-gradient(145deg,#c87d00,#f0a500);
    border-radius:22px;
    padding:28px 30px;
    display:flex; align-items:center; gap:20px;
    box-shadow:0 8px 32px rgba(240,165,0,.28);
}
.pr-hero-icon { width:54px; height:54px; background:rgba(255,255,255,.22); border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; color:#fff; flex-shrink:0; }
.pr-hero-text h1 { margin:0 0 4px; font-size:24px; font-weight:700; color:#fff; letter-spacing:-.5px; }
.pr-hero-text p  { margin:0; font-size:13px; color:rgba(255,255,255,.75); }

/* ── Stat mini-cards ── */
.pr-stat {
    background:var(--card);
    border-radius:18px;
    padding:22px 28px;
    display:flex; flex-direction:column; justify-content:center;
    box-shadow:0 1px 0 var(--sep), 0 2px 14px rgba(0,0,0,.06);
    min-width:170px;
    text-align:center;
}
.pr-stat .st-label { font-size:11px; font-weight:600; color:var(--label); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.pr-stat .st-val   { font-size:28px; font-weight:800; color:var(--body); letter-spacing:-.6px; line-height:1; }
.pr-stat .st-sub   { font-size:12px; color:var(--label); margin-top:6px; }
.pr-stat.accent .st-val { color:var(--accent-d); }

/* ── Filter bar ── */
.pr-filters {
    background:var(--card);
    border-radius:var(--radius);
    padding:16px 22px;
    display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap;
    margin-bottom:20px;
    box-shadow:0 1px 0 var(--sep), 0 2px 12px rgba(0,0,0,.05);
    width:100%;
    box-sizing:border-box;
}
.pr-field label { display:block; font-size:11px; font-weight:600; color:var(--label); margin-bottom:5px; letter-spacing:.3px; }
.pr-field input, .pr-field select {
    border:1.5px solid rgba(60,60,67,.18);
    border-radius:10px;
    padding:8px 12px;
    font-size:13px;
    font-family:var(--font);
    color:var(--body);
    background:#fff;
    outline:none;
    transition:border-color .15s;
}
.pr-field input:focus, .pr-field select:focus { border-color:var(--accent); }
.pr-field select { min-width:155px; cursor:pointer; }
.pr-btn-filter {
    background:var(--accent); color:#fff; border:none; border-radius:10px;
    padding:9px 20px; font-size:13px; font-weight:600; font-family:var(--font);
    cursor:pointer; display:flex; align-items:center; gap:7px;
    transition:background .15s;
}
.pr-btn-filter:hover { background:var(--accent-d); }
.pr-btn-today {
    background:rgba(60,60,67,.08); color:var(--label); border:none; border-radius:10px;
    padding:9px 16px; font-size:13px; font-weight:600; font-family:var(--font);
    cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:6px;
    transition:background .15s;
}
.pr-btn-today:hover { background:rgba(60,60,67,.14); }
.pr-total-pill {
    margin-left:auto;
    background:#fff9ed; border:1.5px solid rgba(240,165,0,.35);
    border-radius:12px; padding:10px 20px;
    display:flex; align-items:center; gap:12px;
}
.pr-total-pill .pill-label { font-size:11px; color:var(--accent-d); font-weight:700; letter-spacing:.3px; text-transform:uppercase; }
.pr-total-pill .pill-val   { font-size:22px; font-weight:800; color:var(--accent-d); letter-spacing:-.4px; }

/* ── Section card ── */
.pr-card {
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:0 1px 0 var(--sep), 0 2px 14px rgba(0,0,0,.06);
    overflow:hidden;
    margin-bottom:20px;
}
.pr-card-head {
    padding:16px 22px;
    border-bottom:1px solid var(--sep);
    display:flex; align-items:center; gap:10px;
}
.pr-card-head h2 { margin:0; font-size:15px; font-weight:700; color:var(--body); letter-spacing:-.2px; }
.pr-card-head .head-meta { font-size:12px; color:var(--label); margin-left:4px; }
.pr-count-badge { margin-left:auto; background:rgba(60,60,67,.08); border-radius:20px; padding:3px 12px; font-size:12px; font-weight:600; color:var(--label); }

/* ── Table ── */
.pr-table { width:100%; border-collapse:collapse; }
.pr-table thead tr { background:#fafafa; }
.pr-table th {
    padding:10px 20px;
    font-size:11px; font-weight:600; color:var(--label);
    text-align:left; letter-spacing:.3px;
    border-bottom:1px solid var(--sep);
    white-space:nowrap;
}
.pr-table th.r, .pr-table td.r { text-align:right; }
.pr-table th.c, .pr-table td.c { text-align:center; }
.pr-table tbody tr { border-bottom:1px solid var(--sep); transition:background .1s; }
.pr-table tbody tr:last-child { border-bottom:none; }
.pr-table tbody tr:hover { background:#fafafa; }
.pr-table td { padding:13px 20px; font-size:13px; color:var(--body); vertical-align:middle; }
.pr-table tfoot td { padding:13px 20px; font-size:13px; font-weight:700; color:var(--body); background:#f9f9fb; border-top:1px solid var(--sep); }

/* ── Chips ── */
.chip-mesa  { display:inline-block; background:#fff3cd; color:#c87d00; font-size:12px; font-weight:700; padding:3px 11px; border-radius:20px; }
.chip-orden { display:inline-block; background:#e3f2fd; color:#1565c0; font-size:12px; font-weight:600; padding:3px 11px; border-radius:20px; font-family:monospace; letter-spacing:.4px; }

/* ── Avatar ── */
.av { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#f0a500,#c87d00); display:flex; align-items:center; justify-content:center; color:#fff; font-size:13px; font-weight:700; flex-shrink:0; }
.av-sm { width:26px; height:26px; font-size:11px; }

/* ── Amounts ── */
.amt     { font-size:15px; font-weight:700; color:var(--green); letter-spacing:-.2px; }
.amt-lg  { font-size:18px; font-weight:800; color:var(--green); letter-spacing:-.3px; }

/* ── Progress bar ── */
.prog-wrap { background:rgba(60,60,67,.1); border-radius:20px; height:5px; width:110px; overflow:hidden; margin-top:5px; }
.prog-fill  { background:var(--accent); height:100%; border-radius:20px; }

/* ── Empty state ── */
.pr-empty { padding:64px 32px; text-align:center; }
.pr-empty i { font-size:44px; color:rgba(60,60,67,.2); margin-bottom:16px; display:block; }
.pr-empty p { margin:0 0 6px; font-size:15px; font-weight:600; color:var(--label); }
.pr-empty small { font-size:13px; color:var(--label); opacity:.7; }

/* ── Pagination ── */
.pr-pag { padding:14px 20px; display:flex; justify-content:center; gap:6px; border-top:1px solid var(--sep); }
.pr-pag a { padding:6px 13px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; color:var(--label); background:rgba(60,60,67,.07); transition:background .12s; }
.pr-pag a:hover { background:rgba(60,60,67,.14); }
.pr-pag a.on { background:var(--accent); color:#fff; }
</style>';

require_once __DIR__ . '/../complementos/header.php';

$hoy     = date('Y-m-d');
$filtros = [
    'desde' => trim($_GET['desde'] ?? $hoy),
    'hasta' => trim($_GET['hasta'] ?? $hoy),
];
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 20;

$propinas          = $model->obtenerPaginadas($pagina, $porPagina, $filtros);
$totalRegistros    = $model->contar($filtros);
$totalMonto        = $model->totalPorRango($filtros);
$resumenHoy        = $model->obtenerResumenHoy();
$acumuladoUsuarios = $model->acumuladoPorUsuario($filtros);
$totalPags         = max(1, (int)ceil($totalRegistros / $porPagina));

// Período anterior: mismo número de días, inmediatamente antes
$dDesde     = new DateTime($filtros['desde']);
$dHasta     = new DateTime($filtros['hasta']);
$diasRango  = (int)$dDesde->diff($dHasta)->days;
$prevHasta  = (clone $dDesde)->modify('-1 day');
$prevDesde  = (clone $prevHasta)->modify("-{$diasRango} days");
$filtrosPrev       = ['desde' => $prevDesde->format('Y-m-d'), 'hasta' => $prevHasta->format('Y-m-d')];
$totalMontoPrev    = $model->totalPorRango($filtrosPrev);
$totalRegistrosPrev = $model->contar($filtrosPrev);
?>

<div class="pr-page">

    <!-- ── Top row ── -->
    <div class="pr-top">
        <div class="pr-hero">
            <div class="pr-hero-icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="pr-hero-text">
                <h1>Propinas</h1>
                <p>Registro y distribución de propinas por mesa y persona</p>
            </div>
        </div>
        <div class="pr-stat accent">
            <div class="st-label">Hoy</div>
            <div class="st-val">$<?php echo number_format((float)$resumenHoy['total_monto'], 2); ?></div>
            <div class="st-sub"><?php echo (int)$resumenHoy['total_registros']; ?> cobro<?php echo $resumenHoy['total_registros'] != 1 ? 's' : ''; ?></div>
        </div>
        <div class="pr-stat">
            <div class="st-label">Este período</div>
            <div class="st-val">$<?php echo number_format($totalMonto, 2); ?></div>
            <div class="st-sub"><?php echo $totalRegistros; ?> registro<?php echo $totalRegistros != 1 ? 's' : ''; ?></div>
        </div>
        <div class="pr-stat">
            <div class="st-label">Período anterior</div>
            <div class="st-val" style="color:#636366;">$<?php echo number_format($totalMontoPrev, 2); ?></div>
            <div class="st-sub">
                <?php echo $totalRegistrosPrev; ?> registro<?php echo $totalRegistrosPrev != 1 ? 's' : ''; ?>
                <br><span style="font-size:10px;opacity:.7;"><?php echo $prevDesde->format('d/m') . ' – ' . $prevHasta->format('d/m'); ?></span>
            </div>
        </div>
    </div>

    <!-- ── Filtros ── -->
    <form method="GET" class="pr-filters">
        <div class="pr-field">
            <label>Desde</label>
            <input type="date" name="desde" value="<?php echo htmlspecialchars($filtros['desde']); ?>">
        </div>
        <div class="pr-field">
            <label>Hasta</label>
            <input type="date" name="hasta" value="<?php echo htmlspecialchars($filtros['hasta']); ?>">
        </div>
        <div class="pr-field">
            <label>Detalle por</label>
            <select id="selectDetalle" onchange="cambiarVista(this.value)">
                <option value="persona">Por persona</option>
                <option value="cobros">Por cobros</option>
                <option value="dividir">Dividir entre personas</option>
            </select>
        </div>
        <div class="pr-field" id="wrapPersonas" style="display:none;">
            <label>Cantidad de personas</label>
            <input type="number" id="inputPersonas" min="1" value="2"
                   style="width:80px;"
                   oninput="calcularDivision()">
        </div>
        <button type="submit" class="pr-btn-filter">
            <i class="fas fa-magnifying-glass"></i> Aplicar filtro
        </button>
        <?php if ($filtros['desde'] !== $hoy || $filtros['hasta'] !== $hoy): ?>
        <a href="<?php echo $basePath; ?>/propinas" class="pr-btn-today">
            <i class="fas fa-arrow-uturn-left"></i> Hoy
        </a>
        <?php endif; ?>
    </form>

    <!-- ── Sección: Por persona ── -->
    <div id="seccionPersona">
    <?php if (!empty($acumuladoUsuarios)): ?>
    <div class="pr-card">
        <div class="pr-card-head">
            <i class="fas fa-person" style="color:var(--accent);font-size:15px;"></i>
            <h2>Acumulado por persona</h2>
            <span class="head-meta"><?php echo htmlspecialchars($filtros['desde']); ?> &ndash; <?php echo htmlspecialchars($filtros['hasta']); ?></span>
            <span class="pr-count-badge"><?php echo count($acumuladoUsuarios); ?> persona<?php echo count($acumuladoUsuarios) != 1 ? 's' : ''; ?></span>
        </div>
        <table class="pr-table">
            <thead>
                <tr>
                    <th>Persona</th>
                    <th class="c">Cobros</th>
                    <th>Primer cobro</th>
                    <th>Último cobro</th>
                    <th>Participación</th>
                    <th class="r">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($acumuladoUsuarios as $u):
                $pct = $totalMonto > 0 ? round((float)$u['total'] / $totalMonto * 100, 1) : 0;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div class="av"><?php echo mb_strtoupper(mb_substr($u['usuario_nombre'], 0, 1)); ?></div>
                        <span style="font-weight:600;"><?php echo htmlspecialchars($u['usuario_nombre']); ?></span>
                    </div>
                </td>
                <td class="c">
                    <span style="background:rgba(60,60,67,.08);border-radius:20px;padding:3px 11px;font-size:13px;font-weight:600;color:var(--label);"><?php echo (int)$u['cantidad']; ?></span>
                </td>
                <td style="color:var(--label);font-size:12px;"><?php echo date('d/m/Y H:i', strtotime($u['primera'])); ?></td>
                <td style="color:var(--label);font-size:12px;"><?php echo date('d/m/Y H:i', strtotime($u['ultima'])); ?></td>
                <td>
                    <div style="font-size:12px;color:var(--label);margin-bottom:4px;"><?php echo $pct; ?>%</div>
                    <div class="prog-wrap"><div class="prog-fill" style="width:<?php echo $pct; ?>%"></div></div>
                </td>
                <td class="r"><span class="amt"><?php echo '$' . number_format((float)$u['total'], 2); ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="color:var(--label);">Total acumulado del período</td>
                    <td class="r"><span class="amt-lg"><?php echo '$' . number_format($totalMonto, 2); ?></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php else: ?>
    <div class="pr-card">
        <div class="pr-empty">
            <i class="fas fa-hand-holding-dollar"></i>
            <p>Sin propinas en este período</p>
            <small>Las propinas se registran automáticamente al cobrar una mesa.</small>
        </div>
    </div>
    <?php endif; ?>
    </div><!-- /seccionPersona -->

    <!-- ── Sección: Por cobros ── -->
    <div id="seccionCobros" style="display:none;">
    <div class="pr-card">
        <div class="pr-card-head">
            <i class="fas fa-receipt" style="color:var(--accent);font-size:15px;"></i>
            <h2>Detalle de cobros</h2>
            <span class="pr-count-badge"><?php echo $totalRegistros; ?> registro<?php echo $totalRegistros != 1 ? 's' : ''; ?></span>
        </div>

        <?php if (empty($propinas)): ?>
        <div class="pr-empty">
            <i class="fas fa-hand-holding-dollar"></i>
            <p>Sin propinas en este período</p>
            <small>Las propinas se registran automáticamente al cobrar una mesa.</small>
        </div>
        <?php else: ?>
        <table class="pr-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>N.º Orden</th>
                    <th>Mesa</th>
                    <th>Atendido por</th>
                    <th class="r">Propina</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($propinas as $p): ?>
            <tr>
                <td style="font-weight:600;"><?php echo date('d/m/Y', strtotime($p['fecha_creacion'])); ?></td>
                <td style="color:var(--label);"><?php echo date('H:i', strtotime($p['fecha_creacion'])); ?></td>
                <td>
                    <?php if (!empty($p['numero_orden'])): ?>
                    <span class="chip-orden"><?php echo htmlspecialchars($p['numero_orden']); ?></span>
                    <?php else: ?>
                    <span style="color:var(--label);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['mesa_numero']): ?>
                    <span class="chip-mesa">Mesa <?php echo (int)$p['mesa_numero']; ?></span>
                    <?php else: ?>
                    <span style="color:var(--label);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['usuario_nombre']): ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="av av-sm"><?php echo mb_strtoupper(mb_substr($p['usuario_nombre'], 0, 1)); ?></div>
                        <span><?php echo htmlspecialchars($p['usuario_nombre']); ?></span>
                    </div>
                    <?php else: ?>
                    <span style="color:var(--label);">—</span>
                    <?php endif; ?>
                </td>
                <td class="r"><span class="amt"><?php echo '$' . number_format((float)$p['monto'], 2); ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="color:var(--label);">Total &mdash; <?php echo $totalRegistros; ?> registro<?php echo $totalRegistros != 1 ? 's' : ''; ?></td>
                    <td class="r"><span class="amt-lg"><?php echo '$' . number_format($totalMonto, 2); ?></span></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($totalPags > 1): ?>
        <div class="pr-pag">
            <?php for ($pg = 1; $pg <= $totalPags; $pg++): ?>
            <a href="?<?php echo http_build_query(array_merge($filtros, ['pagina' => $pg])); ?>"
               class="<?php echo $pg === $pagina ? 'on' : ''; ?>">
                <?php echo $pg; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    </div><!-- /seccionCobros -->

    <!-- ── Sección: División ── -->
    <div id="seccionDivisionCard" style="display:none;">
        <div class="pr-card" style="overflow:visible;">
            <div style="padding:28px 32px;display:flex;align-items:center;gap:28px;flex-wrap:wrap;">
                <div style="width:56px;height:56px;background:linear-gradient(135deg,#e8f8f7,#c0ece8);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#0a6e63;flex-shrink:0;">
                    <i class="fas fa-people-group"></i>
                </div>
                <div style="flex:1;min-width:220px;">
                    <div style="font-size:13px;color:var(--label);margin-bottom:6px;line-height:1.5;">
                        Total <strong style="color:var(--body);" id="divTotalLabel">$<?php echo number_format($totalMonto, 2); ?></strong>
                        dividido entre <strong style="color:var(--body);" id="divNLabel">2</strong> personas
                    </div>
                    <div style="font-size:38px;font-weight:800;color:#0a6e63;letter-spacing:-.8px;line-height:1;" id="divResultado">
                        $<?php echo number_format($totalMonto > 0 ? $totalMonto / 2 : 0, 2); ?>
                    </div>
                    <div style="font-size:13px;color:var(--label);margin-top:6px;">por persona</div>
                </div>
                <?php if ($totalRegistros > 0): ?>
                <div style="background:#f2f2f7;border-radius:14px;padding:16px 22px;text-align:center;min-width:130px;">
                    <div style="font-size:11px;font-weight:600;color:var(--label);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Registros</div>
                    <div style="font-size:22px;font-weight:700;color:var(--body);"><?php echo $totalRegistros; ?></div>
                    <div style="font-size:11px;color:var(--label);">cobro<?php echo $totalRegistros != 1 ? 's' : ''; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /pr-page -->

<script>
const TOTAL_PERIODO = <?php echo json_encode((float)$totalMonto); ?>;

function calcularDivision() {
    const n = Math.max(1, parseInt(document.getElementById('inputPersonas').value) || 1);
    const porPersona = n > 0 ? TOTAL_PERIODO / n : 0;
    document.getElementById('divNLabel').textContent = n;
    document.getElementById('divTotalLabel').textContent = '$' + TOTAL_PERIODO.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('divResultado').textContent  = '$' + porPersona.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function cambiarVista(val) {
    document.getElementById('seccionPersona').style.display    = val === 'persona' ? '' : 'none';
    document.getElementById('seccionCobros').style.display     = (val === 'cobros' || val === 'dividir') ? '' : 'none';
    document.getElementById('seccionDivisionCard').style.display = val === 'dividir' ? '' : 'none';
    document.getElementById('wrapPersonas').style.display      = val === 'dividir' ? '' : 'none';
    if (val === 'dividir') calcularDivision();
    localStorage.setItem('propinas_vista', val);
}

(function () {
    const saved = localStorage.getItem('propinas_vista') || 'persona';
    document.getElementById('selectDetalle').value = saved;
    cambiarVista(saved);
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
