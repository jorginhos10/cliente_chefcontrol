<?php
// vista/dashboard/index.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Dashboard - CHEFCONTROL';
$paginaActual = 'dashboard';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';

$nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$hora   = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
?>

<div class="dash-wrap">

    <!-- Bienvenida -->
    <div class="dash-welcome">
        <div>
            <h1><?php echo $saludo; ?>, <?php echo htmlspecialchars($nombre); ?> 👋</h1>
            <p><?php echo date('l d \d\e F \d\e Y'); ?></p>
        </div>
        <div class="dash-welcome-actions">
            <a href="<?php echo $basePath; ?>/ventas/salon" class="dwa-btn dwa-primary">
                <i class="fas fa-store"></i> Ir al salón
            </a>
            <a href="<?php echo $basePath; ?>/cocina" class="dwa-btn dwa-dark">
                <i class="fas fa-fire-burner"></i> Cocina
            </a>
        </div>
    </div>

    <!-- Stats principales -->
    <div class="dash-stats">

        <!-- Mesas -->
        <div class="ds-card" style="--accent:#16a085">
            <div class="ds-icon" style="background:#e8f8f5;color:#16a085"><i class="fas fa-chair"></i></div>
            <div class="ds-body">
                <div class="ds-num"><?php echo (int)($mesaStats['disponibles'] ?? 0); ?> <span>/ <?php echo (int)($mesaStats['total'] ?? 0); ?></span></div>
                <div class="ds-label">Mesas libres</div>
            </div>
            <a href="<?php echo $basePath; ?>/ventas/salon" class="ds-link"><i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Órdenes activas -->
        <div class="ds-card" style="--accent:#f39c12">
            <div class="ds-icon" style="background:#fef9e7;color:#f39c12"><i class="fas fa-receipt"></i></div>
            <div class="ds-body">
                <div class="ds-num"><?php echo count($ordenesActivas ?? []); ?></div>
                <div class="ds-label">Órdenes activas</div>
                <div class="ds-sub">
                    <?php if ($pendientes > 0): ?><span class="dss pend"><?php echo $pendientes; ?> pendiente<?php echo $pendientes!=1?'s':''; ?></span><?php endif; ?>
                    <?php if ($enPreparacion > 0): ?><span class="dss prep"><?php echo $enPreparacion; ?> en cocina</span><?php endif; ?>
                    <?php if ($listas > 0): ?><span class="dss list"><?php echo $listas; ?> lista<?php echo $listas!=1?'s':''; ?></span><?php endif; ?>
                </div>
            </div>
            <a href="<?php echo $basePath; ?>/cocina" class="ds-link"><i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Ventas hoy -->
        <div class="ds-card" style="--accent:#27ae60">
            <div class="ds-icon" style="background:#eafaf1;color:#27ae60"><i class="fas fa-dollar-sign"></i></div>
            <div class="ds-body">
                <div class="ds-num">$<?php echo number_format($ventaStats['ingresos_hoy'] ?? 0, 0); ?></div>
                <div class="ds-label">Ventas hoy</div>
                <div class="ds-sub"><span class="dss ok"><?php echo (int)($ventaStats['ventas_hoy'] ?? 0); ?> venta<?php echo ($ventaStats['ventas_hoy']??0)!=1?'s':''; ?></span></div>
            </div>
            <a href="<?php echo $basePath; ?>/reportes" class="ds-link"><i class="fas fa-arrow-right"></i></a>
        </div>

        <!-- Stock bajo -->
        <div class="ds-card" style="--accent:#e74c3c">
            <div class="ds-icon" style="background:#fdedec;color:#e74c3c"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="ds-body">
                <div class="ds-num"><?php echo (int)($insumoStats['stock_bajo'] ?? 0); ?></div>
                <div class="ds-label">Stock bajo</div>
                <div class="ds-sub"><span><?php echo (int)($insumoStats['total'] ?? 0); ?> insumos totales</span></div>
            </div>
            <a href="<?php echo $basePath; ?>/insumos" class="ds-link"><i class="fas fa-arrow-right"></i></a>
        </div>

    </div>

    <!-- Cuerpo: órdenes activas + últimas ventas -->
    <div class="dash-body">

        <!-- Órdenes activas ahora -->
        <div class="dash-panel">
            <div class="dp-head">
                <span><i class="fas fa-fire"></i> En cocina ahora</span>
                <a href="<?php echo $basePath; ?>/cocina" class="dp-ver">Ver cocina <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($ordenesActivas)): ?>
            <div class="dp-empty"><i class="fas fa-check-circle"></i> Sin órdenes activas</div>
            <?php else: ?>
            <div class="dp-list">
                <?php foreach (array_slice($ordenesActivas, 0, 5) as $o):
                    $estClass = ['abierta'=>'pend','en_preparacion'=>'prep','lista'=>'list'][$o['estado']] ?? 'pend';
                    $estLabel = ['abierta'=>'Pendiente','en_preparacion'=>'En cocina','lista'=>'Lista ✓'][$o['estado']] ?? $o['estado'];
                    $mins     = (int)($o['minutos_espera'] ?? 0);
                ?>
                <div class="dp-item">
                    <div class="dp-mesa">
                        <span class="dp-mnum"><?php echo $o['mesa_numero'] ?? '?'; ?></span>
                    </div>
                    <div class="dp-info">
                        <span class="dp-nombre"><?php echo htmlspecialchars($o['mesa_nombre'] ?? 'Mesa'); ?></span>
                        <span class="dp-num"><?php echo htmlspecialchars($o['numero_orden']); ?></span>
                    </div>
                    <div class="dp-items"><?php echo count($o['items']); ?> plato<?php echo count($o['items'])!=1?'s':''; ?></div>
                    <div class="dp-timer"><?php echo $mins < 60 ? $mins.'m' : floor($mins/60).'h '.($mins%60).'m'; ?></div>
                    <span class="dp-badge <?php echo $estClass; ?>"><?php echo $estLabel; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Últimas ventas -->
        <div class="dash-panel">
            <div class="dp-head">
                <span><i class="fas fa-clock-rotate-left"></i> Últimas ventas</span>
                <a href="<?php echo $basePath; ?>/ventas/listado" class="dp-ver">Ver todas <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($ultimasVentas)): ?>
            <div class="dp-empty"><i class="fas fa-receipt"></i> Sin ventas registradas</div>
            <?php else: ?>
            <div class="dp-list">
                <?php foreach ($ultimasVentas as $v):
                    $estv = $v['estado'];
                    $vClass = $estv === 'cobrada' ? 'ok' : ($estv === 'cancelada' ? 'cancel' : 'pend');
                    $vLabel = ['cobrada'=>'Cobrada','cancelada'=>'Cancelada','completada'=>'Completada','abierta'=>'Abierta','en_preparacion'=>'En cocina','lista'=>'Lista'][$estv] ?? $estv;
                ?>
                <div class="dp-item">
                    <div class="dp-mesa" style="background:#f0f2f5">
                        <span class="dp-mnum" style="color:#2c3e50;font-size:10px"><?php echo htmlspecialchars(substr($v['numero_orden'],0,6)); ?></span>
                    </div>
                    <div class="dp-info">
                        <span class="dp-nombre"><?php echo htmlspecialchars($v['usuario_nombre'] ?? 'Sistema'); ?></span>
                        <span class="dp-num"><?php echo date('d/m H:i', strtotime($v['fecha_creacion'])); ?></span>
                    </div>
                    <div class="dp-items"><?php echo (int)$v['total_platos']; ?> plato<?php echo $v['total_platos']!=1?'s':''; ?></div>
                    <div class="dp-timer" style="font-weight:800;color:#2c3e50">$<?php echo number_format($v['total'],2); ?></div>
                    <span class="dp-badge <?php echo $vClass; ?>"><?php echo $vLabel; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Acceso rápido a módulos -->
    <div class="dash-mods-title">Módulos del sistema</div>
    <div class="dash-mods">
        <a href="<?php echo $basePath; ?>/ventas/salon" class="dmod" style="--mc:#16a085">
            <i class="fas fa-store"></i><span>Salón</span>
        </a>
        <a href="<?php echo $basePath; ?>/cocina" class="dmod" style="--mc:#e74c3c">
            <i class="fas fa-fire-burner"></i><span>Cocina</span>
        </a>
        <a href="<?php echo $basePath; ?>/ventas" class="dmod" style="--mc:#2980b9">
            <i class="fas fa-cash-register"></i><span>Ventas</span>
        </a>
        <a href="<?php echo $basePath; ?>/recetas" class="dmod" style="--mc:#d35400">
            <i class="fas fa-book-open"></i><span>Recetas</span>
        </a>
        <a href="<?php echo $basePath; ?>/insumos" class="dmod" style="--mc:#27ae60">
            <i class="fas fa-carrot"></i><span>Insumos</span>
        </a>
        <a href="<?php echo $basePath; ?>/inventario" class="dmod" style="--mc:#8e44ad">
            <i class="fas fa-boxes-stacked"></i><span>Inventario</span>
        </a>
        <a href="<?php echo $basePath; ?>/reportes" class="dmod" style="--mc:#2c3e50">
            <i class="fas fa-chart-bar"></i><span>Reportes</span>
        </a>
        <a href="<?php echo $basePath; ?>/configuraciones" class="dmod" style="--mc:#7f8c8d">
            <i class="fas fa-cog"></i><span>Config</span>
        </a>
    </div>

</div>

<style>
/* ── Ventas ── */
.dash-ventas-wrap { background:#fff; border-radius:16px; padding:20px 24px; box-shadow:0 2px 10px rgba(0,0,0,.06); margin-top:24px; }
.dv-search-wrap { display:flex; gap:10px; align-items:center; }
.dv-search { border:1.5px solid #dfe6e9; border-radius:8px; padding:7px 14px; font-size:13px; color:#2c3e50; width:220px; }
.dv-search:focus { outline:none; border-color:#e67e22; }
.dv-select { border:1.5px solid #dfe6e9; border-radius:8px; padding:7px 12px; font-size:13px; color:#636e72; background:#fff; }
.dv-select:focus { outline:none; }
.dv-table-wrap { overflow-x:auto; margin-top:14px; }
.dv-table { width:100%; border-collapse:collapse; font-size:13px; }
.dv-table th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:#95a5a6; padding:10px 12px; border-bottom:2px solid #f0f0f0; white-space:nowrap; }
.dv-table td { padding:11px 12px; border-bottom:1px solid #f8f8f8; color:#2c3e50; vertical-align:middle; }
.dv-table tr:last-child td { border-bottom:none; }
.dv-table tr:hover td { background:#fafbfc; }
.dv-td-orden { font-weight:700; font-family:monospace; font-size:12px; }
.dv-td-center { text-align:center; }
.dv-td-monto { font-weight:800; color:#27ae60; }
.dv-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.dv-ok     { background:#eafaf1; color:#27ae60; }
.dv-cancel { background:#fdedec; color:#e74c3c; }
.dv-comp   { background:#eaf4fb; color:#2980b9; }
.dv-pend   { background:#fef9e7; color:#e67e22; }
.dv-prep   { background:#fef5e7; color:#d35400; }
.dv-list   { background:#d5f5e3; color:#1e8449; }
.dv-count  { font-size:12px; color:#b2bec3; text-align:right; margin-top:10px; }
.dv-hidden { display:none !important; }
</style>

<script>
(function(){
    const search = document.getElementById('ventaSearch');
    const select = document.getElementById('ventaFiltroEstado');
    const count  = document.getElementById('ventaCount');
    if (!search) return;

    function filtrar() {
        const term   = search.value.toLowerCase();
        const estado = select.value;
        let visible  = 0;
        document.querySelectorAll('#ventasTable tbody tr').forEach(tr => {
            const matchTerm   = !term   || tr.dataset.orden.includes(term) || tr.dataset.mesero.includes(term);
            const matchEstado = !estado || tr.dataset.estado === estado;
            const show = matchTerm && matchEstado;
            tr.classList.toggle('dv-hidden', !show);
            if (show) visible++;
        });
        if (count) count.textContent = visible + ' ventas';
    }

    search.addEventListener('input',  filtrar);
    select.addEventListener('change', filtrar);
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
