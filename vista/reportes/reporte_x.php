<?php
// vista/reportes/reporte_x.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Reporte X — CHEFCONTROL';
$paginaActual = 'reporte-x';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();
$jsExtra      = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$catColor = [
    'entrada'      => '#3498db',
    'plato_fuerte' => '#e74c3c',
    'postre'       => '#9b59b6',
    'bebida'       => '#16a085',
    'snack'        => '#f39c12',
    'otro'         => '#7f8c8d',
];

$maxUnidades = !empty($porProducto) ? max(array_column($porProducto, 'unidades')) : 1;
$maxMonto    = !empty($porHora)     ? max(array_column($porHora,     'monto'))    : 1;

// Armar mapa hora → datos para mostrar las 24h
$horaMap = [];
foreach ($porHora as $h) $horaMap[(int)$h['hora']] = $h;
?>

<div class="rx-wrap">

    <!-- Cabecera -->
    <div class="rx-header">
        <div class="rx-header-left">
            <h1><i class="fas fa-receipt" style="color:#e67e22"></i> Reporte X</h1>
            <p>Parcial sin cierre · datos en tiempo real</p>
        </div>
        <form method="GET" action="<?php echo $basePath; ?>/reportes/reporte-x" class="rx-fecha-form">
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" max="<?php echo date('Y-m-d'); ?>">
            <button type="submit" class="rx-btn-prim"><i class="fas fa-rotate-right"></i> Actualizar</button>
            <button type="button" class="rx-btn-sec"><i class="fas fa-print"></i> Imprimir ticket</button>
        </form>
    </div>

    <!-- Stat cards -->
    <div class="rx-stats">
        <div class="rx-stat">
            <div class="rx-stat-icon" style="background:#e67e2220;color:#e67e22"><i class="fas fa-receipt"></i></div>
            <div class="rx-stat-num"><?php echo (int)($general['total_ventas'] ?? 0); ?></div>
            <div class="rx-stat-lbl">Ventas del día</div>
        </div>
        <div class="rx-stat">
            <div class="rx-stat-icon" style="background:#27ae6020;color:#27ae60"><i class="fas fa-dollar-sign"></i></div>
            <div class="rx-stat-num">$<?php echo number_format((float)($general['total_monto'] ?? 0), 2); ?></div>
            <div class="rx-stat-lbl">Total recaudado</div>
        </div>
        <div class="rx-stat">
            <div class="rx-stat-icon" style="background:#3498db20;color:#3498db"><i class="fas fa-calculator"></i></div>
            <div class="rx-stat-num">$<?php echo number_format((float)($general['ticket_promedio'] ?? 0), 2); ?></div>
            <div class="rx-stat-lbl">Ticket promedio</div>
        </div>
        <div class="rx-stat">
            <div class="rx-stat-icon" style="background:#9b59b620;color:#9b59b6"><i class="fas fa-utensils"></i></div>
            <div class="rx-stat-num"><?php echo (int)($general['total_platos'] ?? 0); ?></div>
            <div class="rx-stat-lbl">Platos vendidos</div>
        </div>
    </div>

    <div class="rx-body">

        <!-- Por mesero -->
        <div class="rx-section">
            <div class="rx-section-title"><i class="fas fa-user-tie"></i> Por mesero</div>
            <?php if (empty($porMesero)): ?>
            <div class="rx-empty"><i class="fas fa-user-slash"></i> Sin ventas registradas</div>
            <?php else: ?>
            <table class="rx-table">
                <thead><tr><th>Mesero</th><th>Ventas</th><th>Platos</th><th>Monto</th><th>% del total</th></tr></thead>
                <tbody>
                <?php
                $totalMonto = (float)($general['total_monto'] ?? 0) ?: 1;
                foreach ($porMesero as $m):
                    $pct = round($m['monto'] / $totalMonto * 100, 1);
                ?>
                <tr>
                    <td><i class="fas fa-user-tie" style="color:#e67e22;margin-right:6px"></i><?php echo htmlspecialchars($m['mesero']); ?></td>
                    <td><?php echo (int)$m['ventas']; ?></td>
                    <td><?php echo (int)$m['platos']; ?></td>
                    <td class="rx-td-monto">$<?php echo number_format((float)$m['monto'], 2); ?></td>
                    <td>
                        <div class="rx-pct-bar"><div class="rx-pct-fill" style="width:<?php echo $pct; ?>%"></div></div>
                        <span class="rx-pct-lbl"><?php echo $pct; ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Por producto -->
        <div class="rx-section">
            <div class="rx-section-title"><i class="fas fa-ranking-star"></i> Top productos</div>
            <?php if (empty($porProducto)): ?>
            <div class="rx-empty"><i class="fas fa-bowl-food"></i> Sin ventas registradas</div>
            <?php else: ?>
            <div class="rx-prod-list">
            <?php foreach ($porProducto as $i => $p):
                $pct = round($p['unidades'] / $maxUnidades * 100);
                $col = $catColor[$p['categoria']] ?? '#7f8c8d';
            ?>
            <div class="rx-prod-row">
                <span class="rx-prod-rank" style="background:<?php echo $col; ?>20;color:<?php echo $col; ?>"><?php echo $i+1; ?></span>
                <div class="rx-prod-info">
                    <div class="rx-prod-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <div class="rx-prod-bar-wrap">
                        <div class="rx-prod-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $col; ?>"></div>
                    </div>
                </div>
                <div class="rx-prod-nums">
                    <span class="rx-prod-uni"><?php echo (int)$p['unidades']; ?> uds</span>
                    <span class="rx-prod-mnt">$<?php echo number_format((float)$p['monto'], 2); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Por hora -->
        <div class="rx-section rx-section-full">
            <div class="rx-section-title"><i class="fas fa-clock"></i> Ventas por hora</div>
            <?php if (empty($porHora)): ?>
            <div class="rx-empty"><i class="fas fa-clock"></i> Sin ventas registradas</div>
            <?php else: ?>
            <div class="rx-hora-grid">
            <?php for ($h = 0; $h < 24; $h++):
                $d   = $horaMap[$h] ?? null;
                $pct = $d ? round($d['monto'] / $maxMonto * 100) : 0;
                $lbl = sprintf('%02d:00', $h);
            ?>
            <div class="rx-hora-col <?php echo $d ? 'rx-hora-active' : ''; ?>">
                <div class="rx-hora-bar-wrap">
                    <div class="rx-hora-bar" style="height:<?php echo $pct; ?>%"></div>
                </div>
                <div class="rx-hora-lbl"><?php echo $lbl; ?></div>
                <?php if ($d): ?>
                <div class="rx-hora-val">$<?php echo number_format((float)$d['monto'], 0); ?></div>
                <div class="rx-hora-cnt"><?php echo (int)$d['ventas']; ?> vta<?php echo $d['ventas']!=1?'s':''; ?></div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
.rx-wrap { max-width: 1100px; margin: 0 auto; padding: 24px 20px; }

/* Header */
.rx-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
.rx-header h1 { font-size:24px; font-weight:800; color:#2c3e50; margin:0 0 4px; }
.rx-header p  { font-size:13px; color:#95a5a6; margin:0; }
.rx-fecha-form { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.rx-fecha-form label { font-size:13px; color:#636e72; font-weight:600; }
.rx-fecha-form input[type=date] { border:1px solid #dfe6e9; border-radius:8px; padding:7px 12px; font-size:13px; color:#2c3e50; }
.rx-btn-prim { background:#e67e22; color:#fff; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:700; cursor:pointer; display:flex;align-items:center;gap:6px; }
.rx-btn-sec  { background:#f0f0f0; color:#636e72; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:700; cursor:pointer; display:flex;align-items:center;gap:6px; }
.rx-btn-prim:hover { background:#d35400; }
.rx-btn-sec:hover  { background:#e0e0e0; }

/* Stats */
.rx-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.rx-stat  { background:#fff; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.06); display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; }
.rx-stat-icon { width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; }
.rx-stat-num  { font-size:22px; font-weight:800; color:#2c3e50; }
.rx-stat-lbl  { font-size:12px; color:#95a5a6; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }

/* Body grid */
.rx-body { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.rx-section { background:#fff; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.06); }
.rx-section-full { grid-column: 1 / -1; }
.rx-section-title { font-size:15px; font-weight:800; color:#2c3e50; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.rx-section-title i { color:#e67e22; }
.rx-empty { text-align:center; padding:30px; color:#bdc3c7; font-size:14px; display:flex; flex-direction:column; align-items:center; gap:8px; }
.rx-empty i { font-size:28px; }

/* Tabla mesero */
.rx-table { width:100%; border-collapse:collapse; font-size:13px; }
.rx-table th { text-align:left; font-weight:700; color:#95a5a6; font-size:11px; text-transform:uppercase; letter-spacing:.5px; padding:8px 10px; border-bottom:2px solid #f0f0f0; }
.rx-table td { padding:10px; border-bottom:1px solid #f8f8f8; color:#2c3e50; vertical-align:middle; }
.rx-table tr:last-child td { border-bottom:none; }
.rx-td-monto { font-weight:800; color:#27ae60; }
.rx-pct-bar  { background:#f0f0f0; border-radius:4px; height:6px; width:100px; display:inline-block; vertical-align:middle; margin-right:6px; overflow:hidden; }
.rx-pct-fill { height:100%; background:linear-gradient(90deg,#e67e22,#f39c12); border-radius:4px; }
.rx-pct-lbl  { font-size:11px; color:#7f8c8d; vertical-align:middle; }

/* Productos */
.rx-prod-list { display:flex; flex-direction:column; gap:10px; }
.rx-prod-row  { display:flex; align-items:center; gap:12px; }
.rx-prod-rank { width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; flex-shrink:0; }
.rx-prod-info { flex:1; min-width:0; }
.rx-prod-nombre { font-size:13px; font-weight:700; color:#2c3e50; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:4px; }
.rx-prod-bar-wrap { background:#f0f0f0; border-radius:4px; height:5px; overflow:hidden; }
.rx-prod-bar  { height:100%; border-radius:4px; transition:width .4s; }
.rx-prod-nums { display:flex; flex-direction:column; align-items:flex-end; gap:2px; flex-shrink:0; }
.rx-prod-uni  { font-size:11px; color:#7f8c8d; }
.rx-prod-mnt  { font-size:13px; font-weight:800; color:#27ae60; }

/* Por hora */
.rx-hora-grid { display:flex; gap:4px; align-items:flex-end; height:160px; padding-bottom:40px; position:relative; overflow-x:auto; }
.rx-hora-col  { display:flex; flex-direction:column; align-items:center; min-width:36px; flex:1; }
.rx-hora-bar-wrap { flex:1; display:flex; align-items:flex-end; width:100%; }
.rx-hora-bar  { width:100%; background:#e0e0e0; border-radius:4px 4px 0 0; min-height:2px; transition:height .4s; }
.rx-hora-active .rx-hora-bar { background:linear-gradient(180deg,#e67e22,#f39c12); }
.rx-hora-lbl  { font-size:9px; color:#95a5a6; margin-top:4px; font-weight:600; }
.rx-hora-val  { font-size:9px; color:#27ae60; font-weight:700; }
.rx-hora-cnt  { font-size:9px; color:#95a5a6; }

@media (max-width:700px) {
    .rx-body { grid-template-columns:1fr; }
    .rx-section-full { grid-column:1; }
    .rx-header { flex-direction:column; }
}

/* ── Ticket ── */
#xTicket { display: none; }
@page   { size: 80mm auto; margin: 4mm; }
@media print {
    body * { visibility: hidden; }
    #xTicket {
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
</style>

<!-- Ticket oculto solo para impresión -->
<div id="xTicket"></div>

<script>
(function(){
    const W   = 38;
    const SEP = '*'.repeat(W);
    const LIN = '-'.repeat(W);

    // Datos desde PHP
    const FECHA      = <?php echo json_encode($fecha); ?>;
    const GENERAL    = <?php echo json_encode($general ?? []); ?>;
    const POR_MESERO = <?php echo json_encode($porMesero ?? []); ?>;
    const POR_PROD   = <?php echo json_encode($porProducto ?? []); ?>;
    const POR_HORA   = <?php echo json_encode($porHora ?? []); ?>;
    const COMERCIO   = <?php echo json_encode($comercio ?? []); ?>;

    document.querySelector('.rx-btn-sec').addEventListener('click', function() {
        document.getElementById('xTicket').innerHTML = construirTicketX();
        setTimeout(() => window.print(), 80);
    });

    function center(s) {
        s = String(s);
        const pad = Math.max(0, Math.floor((W - s.length) / 2));
        return ' '.repeat(pad) + s;
    }
    function cols(l, r) {
        l = String(l); r = String(r);
        const sp = Math.max(1, W - l.length - r.length);
        return l + ' '.repeat(sp) + r;
    }
    function fmtFechaLegible(iso) {
        const d = new Date(String(iso).replace(' ','T'));
        return d.toLocaleDateString('es-MX', {day:'2-digit',month:'2-digit',year:'numeric'});
    }
    function ahora() {
        const d = new Date();
        const p = n => String(n).padStart(2,'0');
        return `${p(d.getDate())}/${p(d.getMonth()+1)}/${String(d.getFullYear()).slice(-2)} ${p(d.getHours())}:${p(d.getMinutes())}`;
    }

    function construirTicketX() {
        const g    = GENERAL    || {};
        const mes  = POR_MESERO || [];
        const pro  = POR_PROD   || [];
        const hora = POR_HORA   || [];

        const fechaLeg   = fmtFechaLegible(FECHA + 'T00:00:00');
        const totalMonto = parseFloat(g.total_monto     || 0);
        const totalVtas  = parseInt(g.total_ventas      || 0);
        const promedio   = totalVtas > 0 ? (totalMonto / totalVtas).toFixed(2) : '0.00';

        const nombreNegocio = (COMERCIO.nombre || 'MI NEGOCIO').toUpperCase();

        const lines = [];
        lines.push(SEP);
        lines.push(center(nombreNegocio));
        if (COMERCIO.eslogan)   lines.push(center(COMERCIO.eslogan));
        if (COMERCIO.rut)       lines.push(center('RUT: ' + COMERCIO.rut));
        if (COMERCIO.direccion) lines.push(center(COMERCIO.direccion));
        if (COMERCIO.telefono)  lines.push(center('Tel: ' + COMERCIO.telefono));
        lines.push(SEP);
        lines.push(center('REPORTE X — PARCIAL'));
        lines.push(center('Informe sin cierre de caja'));
        lines.push(SEP);
        lines.push(cols('FECHA:', fechaLeg));
        lines.push(cols('IMPRESION:', ahora()));
        lines.push('');
        lines.push('RESUMEN GENERAL');
        lines.push(LIN);
        lines.push(cols('VENTAS REALIZADAS',  String(totalVtas)));
        lines.push(cols('MONTO TOTAL',        '$' + totalMonto.toFixed(2)));
        lines.push(cols('TICKET PROMEDIO',    '$' + promedio));
        lines.push(cols('PLATOS VENDIDOS',    String(g.total_platos || 0)));
        lines.push('');

        if (mes.length > 0) {
            lines.push('VENTAS POR MESERO');
            lines.push(LIN);
            mes.forEach(m => {
                const nm = String(m.mesero).substring(0, 20);
                lines.push(cols(nm, '$' + parseFloat(m.monto).toFixed(2)));
                lines.push(cols('  Ventas: ' + m.ventas + '  Platos: ' + m.platos, ''));
            });
            lines.push('');
        }

        lines.push('PRODUCTOS VENDIDOS');
        lines.push(LIN);
        if (pro.length === 0) {
            lines.push(center('Sin ventas'));
        } else {
            pro.forEach((p, i) => {
                const nm  = String(i + 1) + '. ' + String(p.nombre).substring(0, 20);
                const uni = String(p.unidades) + 'uds';
                const mn  = '$' + parseFloat(p.monto).toFixed(2);
                if (nm.length + uni.length + mn.length + 2 > W) {
                    lines.push(nm);
                    lines.push(cols('   ' + uni, mn));
                } else {
                    lines.push(cols(nm + ' ' + uni, mn));
                }
            });
        }
        lines.push('');

        if (hora.length > 0) {
            lines.push('VENTAS POR HORA');
            lines.push(LIN);
            hora.forEach(h => {
                const lbl = String(h.hora).padStart(2,'0') + ':00';
                const det = h.ventas + ' vta' + (h.ventas != 1 ? 's' : '');
                lines.push(cols(lbl + '  ' + det, '$' + parseFloat(h.monto).toFixed(2)));
            });
            lines.push('');
        }

        lines.push(SEP);
        lines.push(cols('TOTAL DEL DIA', '$' + totalMonto.toFixed(2)));
        lines.push(SEP);
        lines.push('');
        lines.push(center('** REPORTE PARCIAL SIN CIERRE **'));
        lines.push(center('Los datos pueden cambiar'));
        lines.push(center('Impreso: ' + ahora()));
        lines.push('');
        lines.push(SEP);
        lines.push(center('CHEFCONTROL'));
        lines.push(center('Creado por'));
        lines.push(center('CLOUD CONTROL TECNOLOGYS'));
        lines.push(SEP);
        lines.push('');

        return lines.join('\n');
    }
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
