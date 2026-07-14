<?php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Mi Plan — CHEFCONTROL';
$paginaActual = 'suscripcion';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cid = (int)($_SESSION['comercio_id'] ?? 0);

// ── Plan actual del comercio ──────────────────────────────────────────────────
$planSlugActual = 'gratuito'; $planVence = null; $inicioSus = null;
try {
    $r = DB::get()->prepare("SELECT plan, plan_vence, created_at FROM comercios WHERE id=? LIMIT 1");
    $r->execute([$cid]);
    $com = $r->fetch(PDO::FETCH_ASSOC);
    if ($com) { $planSlugActual = $com['plan'] ?? 'gratuito'; $planVence = $com['plan_vence']; $inicioSus = $com['created_at']; }
} catch (\Throwable $e) {}

// ── Conexión a chefcontrol_sup ────────────────────────────────────────────────
$dbSup = null;
try {
    $opts  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
    $dbSup = new PDO("mysql:host=".Config::DB_HOST.";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                     Config::DB_USER, Config::DB_PASS, $opts);
} catch (\Throwable $e) {}

// ── Planes disponibles ────────────────────────────────────────────────────────
$planes = [];
if ($dbSup) {
    try {
        $planes = $dbSup->query("SELECT * FROM planes WHERE activo=1 ORDER BY orden ASC, precio ASC")->fetchAll();
    } catch (\Throwable $e) {}
}

// ── Plan actual desde tabla planes ───────────────────────────────────────────
$planActualData = null;
foreach ($planes as $p) { if ($p['slug'] === $planSlugActual) { $planActualData = $p; break; } }

// ── Método de pago guardado ───────────────────────────────────────────────────
$metodoPagoData = null;
$cobroAutomatico = 0;
try {
    $mp = DB::get()->prepare("SELECT metodo_pago_tipo, tarjeta_masked, cobro_automatico FROM comercios WHERE id=? LIMIT 1");
    $mp->execute([$cid]);
    $mpRow = $mp->fetch(PDO::FETCH_ASSOC);
    if ($mpRow) {
        $cobroAutomatico = (int)($mpRow['cobro_automatico'] ?? 0);
        if ($mpRow['tarjeta_masked']) {
            $metodoPagoData = [
                'tipo'   => $mpRow['metodo_pago_tipo'] ?? 'tarjeta_credito',
                'masked' => $mpRow['tarjeta_masked'],
            ];
        }
    }
} catch (\Throwable $e) {}

// ── Historial de pagos ────────────────────────────────────────────────────────
$historial = [];
if ($dbSup) {
    try {
        // Agregar columna estado si no existe
        try { $dbSup->exec("ALTER TABLE pagos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'pagado'"); } catch(\Throwable $e) {}
        try { $dbSup->exec("ALTER TABLE pagos ADD COLUMN periodo_desde DATE NULL"); } catch(\Throwable $e) {}

        $s = $dbSup->prepare(
            "SELECT id, monto, fecha, periodo_desde, periodo_hasta, metodo, referencia, notas, estado
             FROM pagos WHERE comercio_id=? ORDER BY fecha DESC LIMIT 36"
        );
        $s->execute([$cid]);
        $historial = $s->fetchAll();
    } catch (\Throwable $e) {}
}

// ── Cálculos ─────────────────────────────────────────────────────────────────
$mesesEs = ['','enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre'];
$fechaEs = function(string $fecha, string $fmt = 'd/m/Y') use ($mesesEs): string {
    $ts = strtotime($fecha);
    if ($fmt === 'largo') {
        return date('j', $ts) . ' de ' . $mesesEs[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
    }
    if ($fmt === 'dMY') {
        return date('d', $ts) . ' ' . ucfirst(substr($mesesEs[(int)date('n', $ts)], 0, 3)) . ' ' . date('Y', $ts);
    }
    return date($fmt, $ts);
};
$hoy          = new DateTime('today');
$totalPagado  = array_sum(array_column($historial, 'monto'));
$ultimoPago   = $historial[0] ?? null;
$diasRestantes = null;

if ($planSlugActual === 'gratuito' || !$planVence) {
    $estadoPlan = 'gratuito';
} else {
    $venceDate    = new DateTime($planVence);
    $diasRestantes = (int)$hoy->diff($venceDate)->days * ($venceDate >= $hoy ? 1 : -1);
    if ($diasRestantes < 0)      $estadoPlan = 'vencido';
    elseif ($diasRestantes <= 7) $estadoPlan = 'por_vencer';
    else                         $estadoPlan  = 'activo';
}

$planLabel   = $planActualData['nombre'] ?? ucfirst($planSlugActual);
$planColor   = $planActualData['color']  ?? '#e74c3c';
$planCars    = json_decode($planActualData['caracteristicas'] ?? '[]', true) ?: [];
$planModulos = json_decode($planActualData['modulos']         ?? '[]', true) ?: [];

$todosModulos = [
    'ventas'         => ['Ventas / Caja',     'fa-cash-register'],
    'cocina'         => ['Pantalla Cocina',    'fa-utensils'],
    'mesas'          => ['Mesas',              'fa-chair'],
    'domicilios'     => ['Domicilios',         'fa-motorcycle'],
    'clientes'       => ['Clientes',           'fa-users'],
    'cupones'        => ['Cupones',            'fa-tag'],
    'pqrs'           => ['PQRS',              'fa-headset'],
    'propinas'       => ['Propinas',           'fa-hand-holding-dollar'],
    'recetas'        => ['Recetas',            'fa-book-open'],
    'insumos'        => ['Insumos',            'fa-boxes-stacked'],
    'inventario'     => ['Inventario',         'fa-warehouse'],
    'proveedores'    => ['Proveedores',        'fa-truck'],
    'ingresos'       => ['Ingresos',           'fa-chart-line'],
    'perdidas'       => ['Pérdidas',           'fa-chart-line-down'],
    'reportes'       => ['Reportes',           'fa-file-chart-column'],
    'chat'           => ['Chat Soporte',       'fa-comments'],
    'notificaciones' => ['Notificaciones',     'fa-bell'],
];

// ── Progreso del período de facturación ───────────────────────────────────────
$progresoPct = 0;
$periodoTotalDias = 0;
$periodoDiasUsados = 0;
if ($ultimoPago && $planVence) {
    $inicio = new DateTime($ultimoPago['fecha']);
    $fin    = new DateTime($planVence);
    $periodoTotalDias  = max(1, (int)$inicio->diff($fin)->days);
    $periodoDiasUsados = max(0, min($periodoTotalDias, (int)$inicio->diff($hoy)->days));
    $progresoPct = min(100, round($periodoDiasUsados / $periodoTotalDias * 100));
}

// ── Factura actual y próxima ──────────────────────────────────────────────────
// Factura actual: mostrar siempre que haya plan activo o historial de pagos
$facturaActual = null;
if ($planSlugActual !== 'gratuito') {
    if ($ultimoPago) {
        $facturaActual = [
            'numero'        => 'F-' . str_pad($cid, 4, '0', STR_PAD_LEFT) . '-' . date('Ym', strtotime($ultimoPago['fecha'])),
            'fecha_emision' => $ultimoPago['fecha'],
            'periodo_desde' => $ultimoPago['periodo_desde'] ?? $ultimoPago['fecha'],
            'periodo_hasta' => $ultimoPago['periodo_hasta'] ?? $planVence,
            'monto'         => $ultimoPago['monto'],
            'metodo'        => $ultimoPago['metodo'] ?? 'efectivo',
            'referencia'    => $ultimoPago['referencia'] ?? null,
            'estado'        => $ultimoPago['estado'] ?? 'pagado',
        ];
    } elseif ($planVence) {
        // Plan asignado por superadmin sin pago registrado
        $facturaActual = [
            'numero'        => 'F-' . str_pad($cid, 4, '0', STR_PAD_LEFT) . '-' . date('Ym'),
            'fecha_emision' => $inicioSus ?? date('Y-m-d'),
            'periodo_desde' => $inicioSus ?? date('Y-m-d'),
            'periodo_hasta' => $planVence,
            'monto'         => 0,
            'metodo'        => 'efectivo',
            'referencia'    => null,
            'estado'        => 'pagado',
        ];
    }
}

// Próxima factura: mostrar 5 días antes del vencimiento
$facturaProxima = null;
if ($planSlugActual !== 'gratuito' && $planVence && $diasRestantes !== null
    && $diasRestantes >= 0 && $diasRestantes <= 5) {
    $venceObj   = new DateTime($planVence);
    $nextPeriod = clone $venceObj;
    $nextPeriod->modify('+30 days');
    $facturaProxima = [
        'numero'        => 'F-' . str_pad($cid, 4, '0', STR_PAD_LEFT) . '-' . date('Ym', strtotime($planVence . ' +1 day')),
        'fecha_emision' => date('Y-m-d', strtotime($planVence . ' +1 day')),
        'periodo_desde' => date('Y-m-d', strtotime($planVence . ' +1 day')),
        'periodo_hasta' => $nextPeriod->format('Y-m-d'),
        'monto'         => (float)($planActualData['precio'] ?? 0),
        'estado'        => 'pendiente',
    ];
}

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="sus-wrap">

<?php
$eColors = ['activo'=>'#16a34a','vencido'=>'#dc2626','por_vencer'=>'#d97706','gratuito'=>'#6b7280'];
$eBg     = ['activo'=>'#dcfce7','vencido'=>'#fee2e2','por_vencer'=>'#fef3c7','gratuito'=>'#f3f4f6'];
$eLabel  = ['activo'=>'Al día','vencido'=>'Vencido','por_vencer'=>'Por vencer','gratuito'=>'Sin plan'];
$eIcon   = ['activo'=>'fa-circle-check','vencido'=>'fa-circle-xmark','por_vencer'=>'fa-clock','gratuito'=>'fa-circle'];
$ec = $eColors[$estadoPlan] ?? '#6b7280';
$eb = $eBg[$estadoPlan]     ?? '#f3f4f6';
$el = $eLabel[$estadoPlan]  ?? '—';
$ei = $eIcon[$estadoPlan]   ?? 'fa-circle';
?>

<!-- ══ HERO ══ -->
<div class="sus-hero" style="--pc:<?= $planColor ?>;">
    <div class="sus-hero-left">
        <div class="sus-hero-icon">
            <i class="fas fa-crown"></i>
        </div>
        <div>
            <div class="sus-hero-title">Mi Plan</div>
            <div class="sus-hero-sub">Suscripción a ChefControl</div>
        </div>
    </div>
    <div class="sus-hero-right">
        <div class="sus-hero-plan-chip">
            <i class="fas fa-layer-group"></i> <?= htmlspecialchars($planLabel) ?>
        </div>
        <div class="sus-hero-status" style="background:<?= $eb ?>;color:<?= $ec ?>;">
            <i class="fas <?= $ei ?>"></i> <?= $el ?>
        </div>
    </div>
</div>

<!-- ══ GRID PRINCIPAL ══ -->
<div class="sus-grid-main">

    <!-- Columna izquierda: Plan + barra de progreso -->
    <div class="sus-plan-card-main" style="--pc:<?= $planColor ?>;">

        <!-- Cabecera del plan -->
        <div class="sus-plan-card-head">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="sus-plan-avatar">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <div class="sus-plan-main-name"><?= htmlspecialchars($planLabel) ?></div>
                        <?php if ((float)($planActualData['precio'] ?? 0) > 0): ?>
                        <div class="sus-plan-price">
                            $<?= number_format((float)$planActualData['precio'], 0, ',', '.') ?>
                            <span>/<?= htmlspecialchars($planActualData['periodo'] ?? 'mensual') ?></span>
                        </div>
                        <?php else: ?>
                        <div class="sus-plan-price">Plan gratuito</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button onclick="document.getElementById('modalComparar').style.display='flex'"
                            class="sus-btn-comparar">
                        <i class="fas fa-table-cells-large"></i> Comparar planes
                    </button>
                    <button onclick="document.getElementById('modalPlanes').style.display='flex'"
                            class="sus-btn-cambiar">
                        <i class="fas fa-arrow-up-right-dots"></i> Cambiar plan
                    </button>
                </div>
            </div>
        </div>

        <!-- Barra de progreso del período -->
        <?php if ($planVence && $estadoPlan !== 'gratuito'): ?>
        <div class="sus-progress-section">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span class="sus-prog-label">
                    <i class="fas fa-calendar-days"></i>
                    <?= $ultimoPago ? date('d M', strtotime($ultimoPago['fecha'])) : 'Inicio' ?>
                </span>
                <span class="sus-prog-center">
                    <?php if ($diasRestantes !== null && $diasRestantes >= 0): ?>
                        <strong style="color:<?= $diasRestantes <= 7 ? '#d97706' : 'var(--pc)' ?>;">
                            <?= $diasRestantes ?> días
                        </strong> restantes
                    <?php elseif ($diasRestantes < 0): ?>
                        <strong style="color:#dc2626;"><?= abs($diasRestantes) ?> días vencido</strong>
                    <?php endif; ?>
                </span>
                <span class="sus-prog-label" style="color:<?= $diasRestantes !== null && $diasRestantes <= 7 ? '#d97706' : 'inherit' ?>;">
                    <?= $fechaEs($planVence, 'dMY') ?>
                </span>
            </div>
            <div class="sus-progress-bar-bg">
                <div class="sus-progress-bar-fill"
                     style="width:<?= $progresoPct ?>%;
                            background:<?= $progresoPct > 85 ? 'linear-gradient(90deg,#f59e0b,#ef4444)' : 'linear-gradient(90deg,var(--pc),var(--pc)cc)' ?>;">
                </div>
            </div>
            <div style="font-size:11px;color:#9ca3af;margin-top:5px;text-align:right;">
                <?= $periodoDiasUsados ?> de <?= $periodoTotalDias ?> días usados
            </div>
        </div>
        <?php endif; ?>

        <!-- Características -->
        <?php if ($planCars): ?>
        <div class="sus-features-grid">
            <?php foreach ($planCars as $car): ?>
            <div class="sus-feat-item">
                <i class="fas fa-check-circle" style="color:var(--pc);"></i>
                <?= htmlspecialchars($car) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Módulos incluidos -->
        <?php if ($planModulos): ?>
        <div class="sus-modulos-section">
            <div class="sus-modulos-title">
                <i class="fas fa-puzzle-piece"></i> Módulos incluidos
            </div>
            <div class="sus-modulos-grid">
                <?php foreach ($planModulos as $slug): ?>
                <?php if (!isset($todosModulos[$slug])) continue; ?>
                <?php [$mLabel, $mIcon] = $todosModulos[$slug]; ?>
                <div class="sus-modulo-chip">
                    <i class="fas <?= $mIcon ?>"></i>
                    <span><?= htmlspecialchars($mLabel) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Fechas clave -->
        <?php
        $mostrarPagoInline = $planSlugActual !== 'gratuito'
                          && (float)($planActualData['precio'] ?? 0) > 0
                          && ($estadoPlan === 'por_vencer' || $estadoPlan === 'vencido');
        $precioInline = (float)($planActualData['precio'] ?? 0);
        ?>
        <div class="sus-dates-row<?= $mostrarPagoInline ? ' sus-dates-row--with-pay' : '' ?>">
            <div class="sus-dates-chips">
                <div class="sus-date-chip">
                    <i class="fas fa-calendar-plus" style="color:#9ca3af;"></i>
                    <div>
                        <div class="sus-date-chip-label">Registro</div>
                        <div class="sus-date-chip-val"><?= $inicioSus ? date('d/m/Y', strtotime($inicioSus)) : '—' ?></div>
                    </div>
                </div>
                <?php if ($ultimoPago): ?>
                <div class="sus-date-chip">
                    <i class="fas fa-receipt" style="color:#9ca3af;"></i>
                    <div>
                        <div class="sus-date-chip-label">Último pago</div>
                        <div class="sus-date-chip-val"><?= date('d/m/Y', strtotime($ultimoPago['fecha'])) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($planVence): ?>
                <div class="sus-date-chip">
                    <i class="fas fa-hourglass-half" style="color:<?= $diasRestantes !== null && $diasRestantes <= 7 ? '#d97706' : '#9ca3af' ?>;"></i>
                    <div>
                        <div class="sus-date-chip-label">Vence</div>
                        <div class="sus-date-chip-val" style="color:<?= $diasRestantes !== null && $diasRestantes <= 7 ? '#d97706' : 'inherit' ?>;">
                            <?= date('d/m/Y', strtotime($planVence)) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($mostrarPagoInline): ?>
            <div class="sus-inline-pay">
                <div class="sus-inline-pay-price">
                    $<?= number_format($precioInline, 0, ',', '.') ?>
                    <span>COP</span>
                </div>
                <button onclick="iniciarPago(<?= $precioInline ?>, '<?= htmlspecialchars($planSlugActual, ENT_QUOTES) ?>', '<?= htmlspecialchars($planLabel, ENT_QUOTES) ?>')"
                        class="sus-inline-pay-btn">
                    <i class="fas fa-credit-card"></i> Pagar ahora
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Columna derecha: Stats + Método de pago -->
    <div class="sus-col-right">

        <!-- Stats -->
        <div class="sus-stats-grid">
            <div class="sus-stat-card">
                <div class="sus-stat-icon" style="background:<?= $planColor ?>15;color:<?= $planColor ?>;">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="sus-stat-val"><?= count($historial) ?></div>
                <div class="sus-stat-key">Pagos</div>
            </div>
            <div class="sus-stat-card">
                <div class="sus-stat-icon" style="background:#16a34a15;color:#16a34a;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="sus-stat-val" style="font-size:16px;">
                    $<?= $totalPagado > 0 ? number_format($totalPagado, 0, ',', '.') : '0' ?>
                </div>
                <div class="sus-stat-key">Total pagado</div>
            </div>
            <div class="sus-stat-card">
                <div class="sus-stat-icon" style="background:<?= $ec ?>18;color:<?= $ec ?>;">
                    <i class="fas <?= $ei ?>"></i>
                </div>
                <div class="sus-stat-val" style="color:<?= $ec ?>;"><?= $el ?></div>
                <div class="sus-stat-key">Estado</div>
            </div>
            <div class="sus-stat-card">
                <div class="sus-stat-icon" style="background:<?= $diasRestantes !== null && $diasRestantes <= 7 ? '#fef3c7' : '#f0f9ff' ?>;
                                                              color:<?= $diasRestantes !== null && $diasRestantes <= 7 ? '#d97706' : '#0284c7' ?>;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="sus-stat-val" style="color:<?= $diasRestantes !== null && $diasRestantes <= 7 ? '#d97706' : '#2c3e50' ?>;">
                    <?= $diasRestantes !== null ? abs($diasRestantes) : '—' ?>
                </div>
                <div class="sus-stat-key">Días <?= $diasRestantes < 0 ? 'vencido' : 'restantes' ?></div>
            </div>
        </div>

        <!-- Método de pago -->
        <div class="sus-mp-card">
            <div class="sus-mp-header">
                <i class="fas fa-shield-halved" style="color:<?= $planColor ?>;"></i>
                <span>Método de pago</span>
                <?php if ($metodoPagoData): ?>
                <span class="sus-mp-enc-badge"><i class="fas fa-lock"></i> Tokenizado</span>
                <?php endif; ?>
            </div>

            <?php if ($metodoPagoData):
                $mpIconos = ['tarjeta_credito'=>['fa-credit-card','#6366f1','Tarjeta crédito'],
                             'tarjeta_debito' =>['fa-credit-card','#06b6d4','Tarjeta débito']];
                [$mpIco,$mpCol,$mpLbl] = $mpIconos[$metodoPagoData['tipo']] ?? ['fa-credit-card','#6b7280','Tarjeta'];
            ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div class="sus-mp-icon" style="background:<?= $mpCol ?>15;color:<?= $mpCol ?>;">
                    <i class="fas <?= $mpIco ?>"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;color:#1f2937;font-size:14px;"><?= $mpLbl ?></div>
                    <div style="font-family:monospace;color:#6b7280;font-size:13px;letter-spacing:1px;">
                        <?= htmlspecialchars($metodoPagoData['masked']) ?>
                    </div>
                </div>
                <button onclick="eliminarTarjeta()" title="Eliminar tarjeta"
                        style="background:none;border:1px solid #fca5a5;color:#ef4444;
                               border-radius:8px;padding:6px 10px;cursor:pointer;font-size:12px;flex-shrink:0;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <!-- Toggle cobro automático -->
            <div class="sus-auto-row">
                <div>
                    <div style="font-size:13px;font-weight:700;color:#1f2937;">Cobro automático</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:1px;">
                        Renovar al vencer el período
                    </div>
                </div>
                <label class="sus-toggle">
                    <input type="checkbox" id="chkCobroAuto"
                           <?= $cobroAutomatico ? 'checked' : '' ?>
                           onchange="toggleCobroAuto(this.checked)">
                    <span class="sus-toggle-slider" style="--tc:<?= $planColor ?>;"></span>
                </label>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:10px 0 14px;">
                <div style="width:52px;height:52px;border-radius:14px;background:#f3f4f6;
                            display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                    <i class="fas fa-credit-card" style="font-size:22px;color:#d1d5db;"></i>
                </div>
                <div style="color:#6b7280;font-size:13px;margin-bottom:14px;">Sin tarjeta registrada</div>
                <button onclick="abrirMetodoPago()" class="sus-mp-add-btn" style="background:<?= $planColor ?>;">
                    <i class="fas fa-plus"></i> Agregar tarjeta
                </button>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /col-right -->
</div><!-- /grid-main -->

<!-- ══ ALERTA PRÓXIMA FACTURA ══ -->
<?php if ($facturaProxima): ?>
<div class="sus-alert-proxima">
    <div class="sus-alert-icon"><i class="fas fa-triangle-exclamation"></i></div>
    <div style="flex:1;">
        <div style="font-weight:700;color:#92400e;font-size:14px;">
            Factura por vencer en <?= $diasRestantes ?> día<?= $diasRestantes !== 1 ? 's' : '' ?>
        </div>
        <div style="color:#a16207;font-size:12px;margin-top:2px;">
            <?= $facturaProxima['numero'] ?> ·
            Período <?= date('d/m/Y', strtotime($facturaProxima['periodo_desde'])) ?>
            — <?= date('d/m/Y', strtotime($facturaProxima['periodo_hasta'])) ?>
        </div>
    </div>
    <div style="font-size:22px;font-weight:900;color:#92400e;">
        $<?= number_format($facturaProxima['monto'], 0, ',', '.') ?>
        <div style="font-size:11px;font-weight:400;color:#a16207;">COP</div>
    </div>
    <button onclick="iniciarPago(<?= (float)$facturaProxima['monto'] ?>, '<?= htmlspecialchars($planSlugActual, ENT_QUOTES) ?>', '<?= htmlspecialchars($planLabel, ENT_QUOTES) ?>')"
            class="sus-alert-btn" id="btnPagarAhora">
        <i class="fas fa-credit-card"></i> Pagar ahora
    </button>
</div>
<?php endif; ?>


<!-- ══ HISTORIAL ══ -->
<div class="sus-hist-card">
    <div class="sus-hist-header">
        <i class="fas fa-clock-rotate-left" style="color:<?= $planColor ?>;"></i>
        Historial de pagos
        <?php if (!empty($historial)): ?>
        <span class="sus-hist-count"><?= count($historial) ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($historial)): ?>
    <div class="sus-hist-empty">
        <i class="fas fa-receipt"></i>
        <p>Sin pagos registrados aún</p>
    </div>
    <?php else: ?>
    <div class="sus-hist-list">

    <?php
    // ── Ítem "pendiente" o "vencida" al tope si no está en pagos ──────────────
    $estadosBadge = [
        'pagado'   => ['color'=>'#16a34a','bg'=>'#dcfce7','icon'=>'fa-circle-check',  'label'=>'Pagado'],
        'pendiente'=> ['color'=>'#d97706','bg'=>'#fef3c7','icon'=>'fa-clock',          'label'=>'Pendiente'],
        'vencida'  => ['color'=>'#dc2626','bg'=>'#fee2e2','icon'=>'fa-circle-exclamation','label'=>'Vencida'],
    ];
    $metIcon2 = ['efectivo'=>'fa-money-bill','transferencia'=>'fa-building-columns',
                 'tarjeta'=>'fa-credit-card','epayco'=>'fa-credit-card',
                 'nequi'=>'fa-mobile','daviplata'=>'fa-mobile','otro'=>'fa-receipt'];

    // Agregar ítem virtual "pendiente" si el plan está por vencer o vencido y no hay pago reciente
    $itemsExtra = [];
    if ($planSlugActual !== 'gratuito' && $planVence) {
        $venceTs = strtotime($planVence);
        $hoyTs   = strtotime('today');
        // ¿Ya hay un pago en el período actual?
        $hayPagoVigente = false;
        foreach ($historial as $p) {
            if ($p['periodo_hasta'] && strtotime($p['periodo_hasta']) >= $hoyTs) {
                $hayPagoVigente = true; break;
            }
        }
        if (!$hayPagoVigente) {
            $estado_extra = $venceTs < $hoyTs ? 'vencida' : 'pendiente';
            $itemsExtra[] = [
                'id'           => null,
                'monto'        => (float)($planActualData['precio'] ?? 0),
                'fecha'        => $planVence,
                'periodo_desde'=> null,
                'periodo_hasta'=> $planVence,
                'metodo'       => 'pendiente',
                'referencia'   => '',
                'notas'        => '',
                'estado'       => $estado_extra,
            ];
        }
    }
    $todosItems = array_merge($itemsExtra, $historial);
    ?>

    <?php foreach ($todosItems as $i => $p):
        $estado = $p['estado'] ?? 'pagado';
        $badge  = $estadosBadge[$estado] ?? $estadosBadge['pagado'];
        $monto  = (float)$p['monto'];
        $mIcon  = $metIcon2[$p['metodo']] ?? 'fa-receipt';
        $esPagado = $estado === 'pagado';
        $iconColor = $esPagado ? $planColor : $badge['color'];
    ?>
        <div class="sus-hist-item" data-hist-idx="<?= $i ?>">
            <!-- Icono método -->
            <div class="sus-hist-item-icon"
                 style="background:<?= $iconColor ?>15;color:<?= $iconColor ?>;">
                <i class="fas <?= $esPagado ? $mIcon : 'fa-file-invoice-dollar' ?>"></i>
            </div>

            <!-- Info -->
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;color:#1f2937;font-size:14px;">
                    Suscripción <?= htmlspecialchars($planLabel) ?>
                </div>
                <div style="color:#9ca3af;font-size:12px;margin-top:2px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                    <?php if ($p['periodo_desde'] && $p['periodo_hasta']): ?>
                        <span><?= date('d/m/Y', strtotime($p['periodo_desde'])) ?>
                        <i class="fas fa-arrow-right" style="font-size:9px;"></i>
                        <?= date('d/m/Y', strtotime($p['periodo_hasta'])) ?></span>
                    <?php elseif ($p['periodo_hasta']): ?>
                        <span>Válido hasta <?= date('d/m/Y', strtotime($p['periodo_hasta'])) ?></span>
                    <?php else: ?>
                        <span><?= date('d/m/Y', strtotime($p['fecha'])) ?></span>
                    <?php endif; ?>
                    <?php if ($esPagado && $p['metodo'] !== 'pendiente'): ?>
                    <span>· <?= ucfirst(htmlspecialchars($p['metodo'])) ?></span>
                    <?php endif; ?>
                    <?php if ($p['referencia']): ?>
                    <span style="font-family:monospace;">· <?= htmlspecialchars($p['referencia']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Monto + badge + botón -->
            <div style="text-align:right;flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <div style="font-size:16px;font-weight:800;color:<?= $esPagado ? '#16a34a' : $badge['color'] ?>;">
                    $<?= number_format($monto, 0, ',', '.') ?>
                </div>
                <div class="sus-hist-badge"
                     style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;">
                    <i class="fas <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                </div>
                <?php if (!$esPagado): ?>
                <button onclick="iniciarPago(<?= (float)$p['monto'] ?: (float)($planActualData['precio']??0) ?>, '<?= htmlspecialchars($planSlugActual, ENT_QUOTES) ?>', '<?= htmlspecialchars($planLabel, ENT_QUOTES) ?>')"
                        style="margin-top:2px;background:<?= $badge['color'] ?>;color:#fff;border:none;
                               border-radius:7px;padding:5px 12px;font-size:12px;font-weight:700;
                               cursor:pointer;display:flex;align-items:center;gap:5px;">
                    <i class="fas fa-credit-card"></i> Pagar
                </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    </div>

    <!-- Paginación -->
    <?php if (count($todosItems) > 5): ?>
    <div class="sus-hist-pag" id="histPag">
        <button class="sus-pag-btn" id="histPrev" onclick="histCambiarPag(-1)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <span class="sus-pag-info" id="histPagInfo"></span>
        <button class="sus-pag-btn" id="histNext" onclick="histCambiarPag(1)">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

</div><!-- /sus-wrap -->

<!-- ══ Modal: Registrar tarjeta ══ -->
<div id="modalMetodoPago" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
     z-index:9999;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:420px;
                box-shadow:0 28px 70px rgba(0,0,0,.22);overflow:hidden;">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,<?= $planColor ?>,<?= $planColor ?>cc);
                    padding:20px 22px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h3 style="color:#fff;margin:0 0 3px;font-size:17px;font-weight:800;">
                    <i class="fas fa-credit-card" style="margin-right:8px;"></i>Registrar tarjeta
                </h3>
                <p style="color:rgba(255,255,255,.75);margin:0;font-size:11px;">
                    Tus datos se tokenizan con ePayco · No se realiza ningún cobro
                </p>
            </div>
            <button onclick="cerrarMetodoPago()"
                    style="background:rgba(255,255,255,.2);border:none;color:#fff;
                           width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:15px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding:22px;">

            <!-- Tipo de tarjeta -->
            <div style="margin-bottom:16px;">
                <p style="color:#7f8c8d;font-size:11px;font-weight:700;text-transform:uppercase;
                           letter-spacing:.4px;margin:0 0 8px;">Tipo de tarjeta</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <label id="mpOpt-tarjeta_credito"
                           style="display:flex;align-items:center;gap:9px;padding:11px 12px;
                                  border:2px solid #6366f1;border-radius:11px;cursor:pointer;
                                  background:#6366f10d;transition:.15s;">
                        <input type="radio" name="mp_tipo" value="tarjeta_credito"
                               onchange="selMpTipo('tarjeta_credito')" checked style="display:none;">
                        <i class="fas fa-credit-card" style="color:#6366f1;font-size:15px;"></i>
                        <div>
                            <div style="font-weight:700;color:#2c3e50;font-size:13px;">Crédito</div>
                            <div style="font-size:10px;color:#95a5a6;">Visa · Master · Amex</div>
                        </div>
                    </label>
                    <label id="mpOpt-tarjeta_debito"
                           style="display:flex;align-items:center;gap:9px;padding:11px 12px;
                                  border:2px solid #e8ecef;border-radius:11px;cursor:pointer;
                                  background:#fff;transition:.15s;">
                        <input type="radio" name="mp_tipo" value="tarjeta_debito"
                               onchange="selMpTipo('tarjeta_debito')" style="display:none;">
                        <i class="fas fa-credit-card" style="color:#06b6d4;font-size:15px;"></i>
                        <div>
                            <div style="font-weight:700;color:#2c3e50;font-size:13px;">Débito</div>
                            <div style="font-size:10px;color:#95a5a6;">Débito bancario</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Número de tarjeta -->
            <div style="margin-bottom:13px;">
                <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                               letter-spacing:.4px;display:block;margin-bottom:5px;">Número de tarjeta</label>
                <div style="position:relative;">
                    <input id="mpNumero" type="text" inputmode="numeric" maxlength="19"
                           placeholder="1234 5678 9012 3456" autocomplete="cc-number"
                           oninput="formatCardNum(this)"
                           style="width:100%;padding:11px 40px 11px 14px;border:1.5px solid #e2e8f0;
                                  border-radius:10px;font-size:15px;font-family:monospace;
                                  letter-spacing:1px;outline:none;box-sizing:border-box;
                                  transition:border-color .15s;">
                    <i id="mpCardIcon" class="fas fa-credit-card"
                       style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                              color:#d1d5db;font-size:16px;pointer-events:none;"></i>
                </div>
            </div>

            <!-- Expiración + CVV -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:13px;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                                   letter-spacing:.4px;display:block;margin-bottom:5px;">Expiración</label>
                    <input id="mpExp" type="text" inputmode="numeric" maxlength="5"
                           placeholder="MM/AA" autocomplete="cc-exp"
                           oninput="formatExp(this)"
                           style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;
                                  border-radius:10px;font-size:14px;outline:none;
                                  box-sizing:border-box;transition:border-color .15s;">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                                   letter-spacing:.4px;display:block;margin-bottom:5px;">CVV</label>
                    <input id="mpCvv" type="password" inputmode="numeric" maxlength="4"
                           placeholder="•••" autocomplete="cc-csc"
                           style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;
                                  border-radius:10px;font-size:14px;outline:none;
                                  box-sizing:border-box;transition:border-color .15s;">
                </div>
            </div>

            <!-- Nombre + Apellido titular -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:13px;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                                   letter-spacing:.4px;display:block;margin-bottom:5px;">Nombre</label>
                    <input id="mpNombre" type="text" placeholder="Nombre" autocomplete="given-name"
                           style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;
                                  border-radius:10px;font-size:14px;outline:none;
                                  box-sizing:border-box;transition:border-color .15s;">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                                   letter-spacing:.4px;display:block;margin-bottom:5px;">Apellido</label>
                    <input id="mpApellido" type="text" placeholder="Apellido" autocomplete="family-name"
                           style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;
                                  border-radius:10px;font-size:14px;outline:none;
                                  box-sizing:border-box;transition:border-color .15s;">
                </div>
            </div>

            <!-- Tipo + Número de documento -->
            <div style="display:grid;grid-template-columns:auto 1fr;gap:10px;margin-bottom:16px;">
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                                   letter-spacing:.4px;display:block;margin-bottom:5px;">Tipo doc.</label>
                    <select id="mpDocTipo"
                            style="padding:11px 10px;border:1.5px solid #e2e8f0;border-radius:10px;
                                   font-size:14px;outline:none;background:#fff;cursor:pointer;">
                        <option value="NIT">NIT</option>
                        <option value="CC">CC</option>
                        <option value="CE">CE</option>
                        <option value="PP">Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
                                   letter-spacing:.4px;display:block;margin-bottom:5px;">Número de documento</label>
                    <input id="mpDocNum" type="text" inputmode="numeric" placeholder="123456789"
                           style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;
                                  border-radius:10px;font-size:14px;outline:none;
                                  box-sizing:border-box;transition:border-color .15s;">
                </div>
            </div>

            <!-- Seguridad -->
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:14px;
                        background:#f0fdf4;border-radius:9px;padding:9px 12px;">
                <i class="fas fa-shield-check" style="color:#16a34a;font-size:15px;flex-shrink:0;"></i>
                <div style="font-size:11px;color:#166534;line-height:1.4;">
                    <strong>Sin cobro</strong> · Solo se tokeniza tu tarjeta con ePayco.<br>
                    ChefControl nunca almacena números de tarjeta.
                </div>
            </div>

            <div id="mpError" style="display:none;background:#fef2f2;border:1px solid #fca5a5;
                 color:#dc2626;border-radius:8px;padding:9px 13px;font-size:13px;margin-bottom:12px;"></div>

            <button id="btnRegistrarTarjeta" onclick="registrarTarjeta()"
                    style="width:100%;background:<?= $planColor ?>;color:#fff;border:none;
                           border-radius:12px;padding:14px;font-size:15px;font-weight:700;
                           cursor:pointer;display:flex;align-items:center;
                           justify-content:center;gap:10px;transition:opacity .15s;">
                <i class="fas fa-lock"></i> Registrar tarjeta
            </button>
        </div>
    </div>
</div>

<!-- ══ Modal: Elegir método de pago ══ -->
<div id="modalElegirPago" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
     z-index:10000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:400px;
                box-shadow:0 28px 70px rgba(0,0,0,.22);overflow:hidden;">
        <div style="background:linear-gradient(135deg,<?= $planColor ?>,<?= $planColor ?>cc);
                    padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h3 style="color:#fff;margin:0 0 2px;font-size:16px;font-weight:800;">
                    <i class="fas fa-credit-card" style="margin-right:7px;"></i>¿Cómo deseas pagar?
                </h3>
                <p style="color:rgba(255,255,255,.75);margin:0;font-size:11px;" id="epModalDesc"></p>
            </div>
            <button onclick="cerrarElegirPago()"
                    style="background:rgba(255,255,255,.2);border:none;color:#fff;
                           width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:12px;">

            <!-- Opción 1: Tarjeta registrada -->
            <button id="btnPagarTarjetaGuardada" onclick="confirmarPagoTarjeta()"
                    style="display:flex;align-items:center;gap:14px;padding:16px 18px;
                           border:2px solid #e2e8f0;border-radius:14px;background:#fff;
                           cursor:pointer;text-align:left;transition:.15s;width:100%;"
                    onmouseover="this.style.borderColor='<?= $planColor ?>';this.style.background='<?= $planColor ?>08'"
                    onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#fff'">
                <div style="width:44px;height:44px;border-radius:12px;background:<?= $planColor ?>15;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-credit-card" style="color:<?= $planColor ?>;font-size:18px;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800;color:#1f2937;font-size:14px;">Tarjeta registrada</div>
                    <div id="epModalMasked" style="font-family:monospace;color:#6b7280;font-size:12px;margin-top:2px;"></div>
                </div>
                <i class="fas fa-chevron-right" style="color:#d1d5db;font-size:13px;"></i>
            </button>

            <!-- Opción 2: ePayco -->
            <button onclick="confirmarPagoEpayco()"
                    style="display:flex;align-items:center;gap:14px;padding:16px 18px;
                           border:2px solid #e2e8f0;border-radius:14px;background:#fff;
                           cursor:pointer;text-align:left;transition:.15s;width:100%;"
                    onmouseover="this.style.borderColor='#00bfa5';this.style.background='#00bfa508'"
                    onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#fff'">
                <div style="width:44px;height:44px;border-radius:12px;background:#00bfa515;
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-shield-check" style="color:#00bfa5;font-size:18px;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:800;color:#1f2937;font-size:14px;">Pagar con ePayco</div>
                    <div style="color:#6b7280;font-size:12px;margin-top:2px;">Pasarela segura · Todas las tarjetas</div>
                </div>
                <i class="fas fa-chevron-right" style="color:#d1d5db;font-size:13px;"></i>
            </button>

        </div>
    </div>
</div>

<!-- ══ Modal: Comparar planes ══ -->
<div id="modalComparar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
     z-index:9998;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:1000px;
                max-height:90vh;position:relative;box-shadow:0 24px 64px rgba(0,0,0,.25);
                display:flex;flex-direction:column;overflow:hidden;">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,#374151,#1f2937);padding:20px 26px;
                    display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div>
                <h2 style="color:#fff;margin:0 0 4px;font-size:19px;font-weight:800;">
                    <i class="fas fa-table-cells-large" style="margin-right:8px;"></i>Comparar planes
                </h2>
                <p style="color:rgba(255,255,255,.65);margin:0;font-size:12px;">Qué incluye cada plan, lado a lado</p>
            </div>
            <button onclick="document.getElementById('modalComparar').style.display='none'"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;width:34px;height:34px;
                           border-radius:8px;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Tabla comparativa (scroll si no cabe) -->
        <div style="overflow:auto;flex:1;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="position:sticky;left:0;top:0;background:#f9fafb;text-align:left;padding:14px 18px;
                                   font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.4px;
                                   border-bottom:2px solid #e5e7eb;min-width:190px;z-index:2;">
                            Característica
                        </th>
                        <?php foreach ($planes as $p):
                            $colorCmp = $p['color'] ?? '#6366f1';
                            $esActualCmp = $p['slug'] === $planSlugActual;
                        ?>
                        <th style="position:sticky;top:0;padding:14px 16px;text-align:center;border-bottom:2px solid <?= $colorCmp ?>;
                                   background:<?= $colorCmp ?>0d;min-width:140px;z-index:1;">
                            <div style="font-size:13px;font-weight:800;color:<?= $colorCmp ?>;">
                                <i class="fas fa-crown"></i> <?= htmlspecialchars($p['nombre']) ?>
                            </div>
                            <div style="font-size:15px;font-weight:900;color:#1f2937;margin-top:3px;">
                                <?= (float)$p['precio'] > 0 ? '$'.number_format((float)$p['precio'],0,',','.') : 'Gratis' ?>
                            </div>
                            <?php if ((float)$p['precio'] > 0): ?>
                            <div style="font-size:10px;color:#9ca3af;">/<?= htmlspecialchars($p['periodo']) ?></div>
                            <?php endif; ?>
                            <?php if ($esActualCmp): ?>
                            <div style="margin-top:6px;font-size:9px;font-weight:800;color:#fff;background:<?= $colorCmp ?>;
                                        border-radius:999px;padding:2px 8px;display:inline-block;text-transform:uppercase;">
                                Tu plan
                            </div>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todosModulos as $slugCmp => [$mLabelCmp, $mIconCmp]): ?>
                    <tr>
                        <td style="position:sticky;left:0;background:#fff;padding:11px 18px;font-size:13px;
                                   color:#374151;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">
                            <i class="fas <?= $mIconCmp ?>" style="color:#9ca3af;margin-right:8px;width:16px;text-align:center;"></i>
                            <?= htmlspecialchars($mLabelCmp) ?>
                        </td>
                        <?php foreach ($planes as $p):
                            $modsCmp    = json_decode($p['modulos'] ?? '[]', true) ?: [];
                            $incluyeCmp = in_array($slugCmp, $modsCmp);
                            $colorCmp2  = $p['color'] ?? '#6366f1';
                        ?>
                        <td style="text-align:center;padding:11px 16px;border-bottom:1px solid #f1f5f9;">
                            <?php if ($incluyeCmp): ?>
                            <i class="fas fa-check" style="color:<?= $colorCmp2 ?>;font-size:14px;"></i>
                            <?php else: ?>
                            <i class="fas fa-minus" style="color:#e5e7eb;font-size:12px;"></i>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div style="padding:16px 26px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;flex-shrink:0;">
            <button onclick="document.getElementById('modalComparar').style.display='none';
                             document.getElementById('modalPlanes').style.display='flex';"
                    class="sus-btn-cambiar" style="--pc:<?= $planColor ?>;">
                <i class="fas fa-arrow-up-right-dots"></i> Cambiar plan
            </button>
        </div>
    </div>
</div>

<!-- ══ Modal: Planes disponibles ══ -->
<div id="modalPlanes" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
     z-index:9998;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#f0f2f5;border-radius:20px;width:100%;max-width:900px;
                max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 24px 64px rgba(0,0,0,.25);">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,#922b21,#c0392b);border-radius:20px 20px 0 0;
                    padding:22px 28px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <h2 style="color:#fff;margin:0 0 4px;font-size:20px;font-weight:800;">
                    <i class="fas fa-layer-group" style="margin-right:8px;"></i>Elige tu plan
                </h2>
                <p style="color:rgba(255,255,255,.7);margin:0;font-size:13px;">Selecciona el plan que mejor se adapte a tu negocio</p>
            </div>
            <button onclick="document.getElementById('modalPlanes').style.display='none'"
                    style="background:rgba(255,255,255,.15);border:none;color:#fff;width:36px;height:36px;
                           border-radius:8px;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Tarjetas -->
        <div style="padding:24px;display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px;">
        <?php foreach ($planes as $p):
            $cars     = json_decode($p['caracteristicas'] ?? '[]', true) ?: [];
            $esActual = $p['slug'] === $planSlugActual;
            $color    = $p['color'] ?? '#6366f1';
            $precio   = (float)$p['precio'];
        ?>
            <div style="background:#fff;border-radius:16px;overflow:hidden;position:relative;
                        box-shadow:0 2px 12px rgba(0,0,0,.08);
                        border:2px solid <?= $esActual ? $color : 'transparent' ?>;
                        transition:transform .2s,box-shadow .2s;"
                 onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 10px 30px rgba(0,0,0,.15)'"
                 onmouseout="this.style.transform='';this.style.boxShadow='0 2px 12px rgba(0,0,0,.08)'">

                <?php if ((int)$p['destacado'] && !$esActual): ?>
                <div style="background:<?= $color ?>;color:#fff;text-align:center;font-size:10px;
                            font-weight:800;padding:4px;letter-spacing:.5px;text-transform:uppercase;">
                    <i class="fas fa-star"></i> Recomendado
                </div>
                <?php elseif ($esActual): ?>
                <div style="background:<?= $color ?>;color:#fff;text-align:center;font-size:10px;
                            font-weight:800;padding:4px;letter-spacing:.5px;text-transform:uppercase;">
                    <i class="fas fa-check"></i> Tu plan actual
                </div>
                <?php endif; ?>

                <!-- Cabecera de color -->
                <div style="background:<?= $color ?>18;padding:20px 20px 14px;border-bottom:2px solid <?= $color ?>22;">
                    <div style="font-size:15px;font-weight:800;color:<?= $color ?>;margin-bottom:6px;">
                        <i class="fas fa-crown"></i> <?= htmlspecialchars($p['nombre']) ?>
                    </div>
                    <div style="font-size:28px;font-weight:900;color:<?= $color ?>;line-height:1;">
                        <?= $precio > 0 ? '$'.number_format($precio,0,',','.') : 'Gratis' ?>
                        <?php if ($precio > 0): ?>
                        <span style="font-size:12px;font-weight:500;color:#aaa;">
                            /<?= htmlspecialchars($p['periodo']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($p['descripcion']): ?>
                    <div style="font-size:11px;color:#aaa;margin-top:5px;"><?= htmlspecialchars($p['descripcion']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Características -->
                <ul style="list-style:none;padding:14px 18px;margin:0;display:flex;flex-direction:column;gap:7px;flex:1;">
                    <?php foreach ($cars as $car): ?>
                    <li style="font-size:12px;color:#636e72;display:flex;align-items:flex-start;gap:7px;">
                        <i class="fas fa-check" style="color:<?= $color ?>;font-size:10px;margin-top:2px;flex-shrink:0;"></i>
                        <?= htmlspecialchars($car) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Botón -->
                <div style="padding:0 18px 18px;">
                    <?php if ($esActual): ?>
                    <button disabled style="width:100%;border:none;border-radius:9px;padding:11px;
                                            font-size:13px;font-weight:700;cursor:default;
                                            background:<?= $color ?>18;color:<?= $color ?>;">
                        <i class="fas fa-check-circle"></i> Plan actual
                    </button>
                    <?php else: ?>
                    <button onclick="document.getElementById('modalPlanes').style.display='none';
                                     solicitarPlan('<?= htmlspecialchars($p['slug'],ENT_QUOTES) ?>',
                                                   '<?= htmlspecialchars($p['nombre'],ENT_QUOTES) ?>',
                                                   '<?= $color ?>')"
                            style="width:100%;border:none;border-radius:9px;padding:11px;
                                   font-size:13px;font-weight:700;cursor:pointer;
                                   background:<?= $color ?>;color:#fff;
                                   transition:opacity .15s;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fas fa-arrow-right"></i> Seleccionar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- ══ Modal: Solicitar cambio de plan ══ -->
<div id="modalCambio" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:32px 28px;max-width:420px;width:100%;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <button onclick="document.getElementById('modalCambio').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:18px;color:#aaa;cursor:pointer;">
            <i class="fas fa-times"></i>
        </button>
        <div id="cambioIcono" style="width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:22px;">
            <i class="fas fa-crown"></i>
        </div>
        <h3 id="cambioTitulo" style="text-align:center;margin:0 0 6px;font-size:18px;color:#2c3e50;"></h3>
        <p style="text-align:center;color:#95a5a6;font-size:13px;margin:0 0 20px;line-height:1.5;">
            Tu solicitud de cambio de plan será enviada al equipo de ChefControl.<br>
            Te contactaremos para coordinar el pago.
        </p>
        <div id="cambioMsg" style="display:none;border-radius:8px;padding:12px 16px;margin-bottom:14px;font-size:13px;"></div>
        <button id="btnConfirmarCambio" onclick="confirmarCambio()"
                style="width:100%;border:none;border-radius:10px;padding:13px;font-size:14px;
                       font-weight:700;cursor:pointer;color:#fff;">
            <i class="fas fa-paper-plane"></i> Enviar solicitud
        </button>
        <button onclick="document.getElementById('modalCambio').style.display='none'"
                style="width:100%;margin-top:8px;background:none;border:1.5px solid #dfe6e9;
                       border-radius:10px;padding:11px;font-size:13px;color:#95a5a6;cursor:pointer;">
            Cancelar
        </button>
    </div>
</div>

<style>
/* ── Layout base ── */
.sus-wrap { padding:20px;background:#f1f5f9;min-height:calc(100vh - 70px);display:flex;flex-direction:column;gap:16px; }

/* ── Hero ── */
.sus-hero { background:linear-gradient(135deg,#1e1b4b,#312e81);border-radius:16px;padding:20px 24px;
            display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;
            box-shadow:0 4px 20px rgba(49,46,129,.3); }
.sus-hero-left { display:flex;align-items:center;gap:14px; }
.sus-hero-icon { width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,.12);
                 display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;
                 border:1.5px solid rgba(255,255,255,.2); }
.sus-hero-title { font-size:21px;font-weight:800;color:#fff;margin:0 0 2px; }
.sus-hero-sub   { font-size:13px;color:rgba(255,255,255,.6);margin:0; }
.sus-hero-right { display:flex;align-items:center;gap:10px;flex-wrap:wrap; }
.sus-hero-plan-chip { background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.25);
                      color:#fff;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;
                      display:flex;align-items:center;gap:6px; }
.sus-hero-status { padding:6px 14px;border-radius:20px;font-size:12.5px;font-weight:700;
                   display:flex;align-items:center;gap:5px; }

/* ── Grid principal ── */
.sus-grid-main { display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start; }

/* ── Plan card ── */
.sus-plan-card-main { background:#fff;border-radius:16px;overflow:hidden;
                      box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.06); }
.sus-plan-card-head { padding:20px 22px;border-bottom:1px solid #f1f5f9; }
.sus-plan-avatar { width:44px;height:44px;border-radius:12px;background:var(--pc,#6366f1)18;
                   display:flex;align-items:center;justify-content:center;font-size:20px;
                   color:var(--pc,#6366f1);border:1.5px solid var(--pc,#6366f1)33;flex-shrink:0; }
.sus-plan-main-name { font-size:18px;font-weight:800;color:#1f2937; }
.sus-plan-price { font-size:13px;color:#6b7280;margin-top:1px; }
.sus-plan-price strong { color:var(--pc);font-size:16px; }
.sus-btn-cambiar { background:var(--pc,#6366f1);color:#fff;border:none;border-radius:9px;
                   padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;
                   display:flex;align-items:center;gap:6px;white-space:nowrap;
                   box-shadow:0 3px 10px color-mix(in srgb, var(--pc) 35%, transparent);
                   transition:opacity .15s; }
.sus-btn-cambiar:hover { opacity:.88; }

.sus-btn-comparar { background:#fff;color:var(--pc,#6366f1);border:1.5px solid var(--pc,#6366f1);
                    border-radius:9px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;
                    display:flex;align-items:center;gap:6px;white-space:nowrap;
                    transition:background .15s; }
.sus-btn-comparar:hover { background:color-mix(in srgb, var(--pc,#6366f1) 8%, #fff); }

/* ── Progress bar ── */
.sus-progress-section { padding:16px 22px;border-bottom:1px solid #f1f5f9; }
.sus-prog-label { font-size:12px;color:#6b7280;font-weight:600; }
.sus-prog-center { font-size:13px;color:#374151; }
.sus-progress-bar-bg { height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;margin-top:2px; }
.sus-progress-bar-fill { height:100%;border-radius:99px;transition:width .6s ease; }

/* ── Características ── */
.sus-features-grid { display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:16px 22px;border-bottom:1px solid #f1f5f9; }
.sus-feat-item { display:flex;align-items:center;gap:7px;font-size:13px;color:#4b5563;font-weight:500; }
.sus-feat-item i { font-size:12px;flex-shrink:0; }

/* ── Módulos ── */
.sus-modulos-section { padding:16px 22px;border-bottom:1px solid #f1f5f9; }
.sus-modulos-title   { font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;
                        letter-spacing:.5px;margin-bottom:12px;display:flex;align-items:center;gap:6px; }
.sus-modulos-grid    { display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px; }
.sus-modulo-chip     { display:flex;align-items:center;gap:7px;background:#f8fafc;
                        border:1px solid #e5e7eb;border-radius:8px;padding:7px 10px;
                        font-size:12px;font-weight:600;color:#374151; }
.sus-modulo-chip i   { font-size:12px;color:var(--pc);flex-shrink:0; }

/* ── Fechas ── */
.sus-dates-row        { display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 22px; }
.sus-dates-chips      { display:flex;align-items:center;gap:0;flex-wrap:wrap; }
.sus-date-chip        { display:flex;align-items:center;gap:9px;padding:0 18px 0 0;border-right:1px solid #f1f5f9;margin-right:18px; }
.sus-date-chip:last-child { border-right:none;margin-right:0;padding-right:0; }
.sus-date-chip-label  { font-size:10px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.4px; }
.sus-date-chip-val    { font-size:13px;color:#1f2937;font-weight:700;margin-top:1px; }
.sus-inline-pay       { display:flex;align-items:center;gap:10px;flex-shrink:0; }
.sus-inline-pay-price { font-size:18px;font-weight:800;color:#1f2937;line-height:1; }
.sus-inline-pay-price span { font-size:11px;font-weight:600;color:#9ca3af;margin-left:2px; }
.sus-inline-pay-btn   { background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;
                         border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;
                         cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap; }
.sus-inline-pay-btn:hover { opacity:.9; }

/* ── Columna derecha ── */
.sus-col-right { display:flex;flex-direction:column;gap:14px; }

/* ── Stats ── */
.sus-stats-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
.sus-stat-card { background:#fff;border-radius:14px;padding:16px;
                 box-shadow:0 1px 3px rgba(0,0,0,.05),0 2px 8px rgba(0,0,0,.05);
                 display:flex;flex-direction:column;gap:6px; }
.sus-stat-icon { width:36px;height:36px;border-radius:9px;display:flex;align-items:center;
                 justify-content:center;font-size:15px;flex-shrink:0; }
.sus-stat-val { font-size:20px;font-weight:900;color:#1f2937;line-height:1; }
.sus-stat-key { font-size:11px;color:#9ca3af;font-weight:600;text-transform:uppercase;letter-spacing:.3px; }

/* ── Método de pago card ── */
.sus-mp-card { background:#fff;border-radius:14px;padding:16px 18px;
               box-shadow:0 1px 3px rgba(0,0,0,.05),0 2px 8px rgba(0,0,0,.05); }
.sus-mp-header { display:flex;align-items:center;gap:7px;font-size:12px;font-weight:700;
                 color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:14px; }
.sus-mp-enc-badge { margin-left:auto;background:#dcfce7;color:#16a34a;border-radius:5px;
                    padding:2px 8px;font-size:10px;font-weight:700;letter-spacing:.3px; }
.sus-mp-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;
               justify-content:center;font-size:18px;flex-shrink:0; }
.sus-mp-edit-btn { width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;color:#374151;
                   border-radius:9px;padding:9px;font-size:13px;font-weight:600;cursor:pointer;
                   display:flex;align-items:center;justify-content:center;gap:7px;transition:.15s; }
.sus-mp-edit-btn:hover { background:#f1f5f9; }
.sus-mp-add-btn { border:none;border-radius:9px;padding:10px 20px;font-size:13px;font-weight:700;
                  color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.15s; }
.sus-mp-add-btn:hover { opacity:.88; }

/* ── Alerta próxima factura ── */
.sus-alert-proxima { background:#fffbeb;border:1.5px solid #fcd34d;border-radius:14px;
                     padding:16px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap; }
.sus-alert-icon { width:40px;height:40px;border-radius:10px;background:#fef3c7;color:#d97706;
                  display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
.sus-alert-btn { background:#d97706;color:#fff;border:none;border-radius:9px;padding:9px 14px;
                 font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;
                 display:flex;align-items:center;gap:6px;transition:.15s;flex-shrink:0; }
.sus-alert-btn:hover { opacity:.88; }

/* ── Factura actual ── */
.sus-invoice-card { background:#fff;border-radius:16px;overflow:hidden;
                    box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.06); }
.sus-invoice-header { background:linear-gradient(135deg,var(--pc),color-mix(in srgb,var(--pc) 75%,#000));
                      padding:18px 24px;display:flex;align-items:center;gap:24px;flex-wrap:wrap; }
.sus-invoice-label { font-size:10px;font-weight:800;letter-spacing:.8px;color:rgba(255,255,255,.65);margin-bottom:4px; }
.sus-invoice-num { font-size:18px;font-weight:900;color:#fff;font-family:monospace;letter-spacing:.5px; }
.sus-invoice-body { padding:20px 24px; }
.sus-invoice-row { display:flex;align-items:center;gap:16px;flex-wrap:wrap; }

/* ── Historial ── */
.sus-hist-card { background:#fff;border-radius:16px;overflow:hidden;
                 box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.06); }
.sus-hist-header { padding:16px 22px;font-size:13px;font-weight:800;color:#374151;text-transform:uppercase;
                   letter-spacing:.5px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px; }
.sus-hist-count { background:#f1f5f9;color:#6b7280;border-radius:20px;padding:2px 9px;
                  font-size:11px;font-weight:700;margin-left:auto; }
.sus-hist-empty { padding:40px;text-align:center;color:#9ca3af; }
.sus-hist-empty i { font-size:36px;display:block;margin-bottom:10px;opacity:.3; }
.sus-hist-empty p { margin:0;font-size:14px; }
.sus-hist-list { display:flex;flex-direction:column; }
.sus-hist-item { display:flex;align-items:center;gap:14px;padding:16px 22px;border-bottom:1px solid #f8fafc;transition:.15s; }
.sus-hist-item:last-child { border-bottom:none; }
.sus-hist-item:hover { background:#fafafa; }
.sus-hist-item-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;
                      justify-content:center;font-size:16px;flex-shrink:0; }
.sus-hist-badge { border-radius:20px;padding:3px 10px;
                  font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px; }
.sus-hist-pag   { display:flex;align-items:center;justify-content:center;gap:12px;
                  padding:14px 22px;border-top:1px solid #f1f5f9; }
.sus-pag-btn    { background:#f3f4f6;border:none;color:#374151;width:32px;height:32px;border-radius:8px;
                  cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;transition:.15s; }
.sus-pag-btn:hover:not(:disabled) { background:#e5e7eb; }
.sus-pag-btn:disabled { opacity:.35;cursor:default; }
.sus-pag-info   { font-size:13px;color:#6b7280;font-weight:600;min-width:80px;text-align:center; }

/* Plan cards modal (mantener estilos) */
.sus-planes-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px; }
.sus-plan-card { background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.07);overflow:hidden;position:relative;transition:transform .18s; }
.sus-plan-card:hover { transform:translateY(-3px); }
.sus-plan-card--destacado { box-shadow:0 8px 30px rgba(0,0,0,.15); }
.sus-plan-card-badge { position:absolute;top:10px;right:10px;font-size:10px;font-weight:800;padding:3px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:.4px; }
.sus-plan-card-head { padding:20px 22px; }
.sus-plan-card-nombre { font-size:16px;font-weight:800;margin-bottom:6px; }
.sus-plan-card-precio { font-size:26px;font-weight:900; }
.sus-plan-card-precio span { font-size:13px;font-weight:500;opacity:.7; }
.sus-plan-card-features { list-style:none;padding:16px 22px;margin:0;display:flex;flex-direction:column;gap:9px; }
.sus-plan-card-features li { font-size:13px;color:#636e72;display:flex;align-items:center;gap:8px; }
.sus-plan-select-btn { width:calc(100% - 44px);margin:0 22px 22px;padding:12px;border-radius:10px;border:none;font-size:13.5px;font-weight:700;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center;gap:7px; }

/* ── Toggle cobro automático ── */
.sus-auto-row { display:flex;align-items:center;justify-content:space-between;gap:12px;
                padding:10px 14px;background:#f8fafc;border-radius:10px;margin-top:6px; }
.sus-toggle { position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0; }
.sus-toggle input { opacity:0;width:0;height:0; }
.sus-toggle-slider { position:absolute;inset:0;background:#d1d5db;border-radius:24px;cursor:pointer;transition:.25s; }
.sus-toggle-slider::before { content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;
                              background:#fff;border-radius:50%;transition:.25s;
                              box-shadow:0 1px 3px rgba(0,0,0,.25); }
.sus-toggle input:checked + .sus-toggle-slider { background:var(--tc,#6366f1); }
.sus-toggle input:checked + .sus-toggle-slider::before { transform:translateX(20px); }

/* ── Responsive ── */
@media(max-width:1024px) { .sus-grid-main { grid-template-columns:1fr; } .sus-col-right { display:grid;grid-template-columns:1fr 1fr;gap:14px; } }
@media(max-width:640px)  { .sus-col-right { grid-template-columns:1fr; } .sus-stats-grid { grid-template-columns:1fr 1fr; } .sus-features-grid { grid-template-columns:1fr; } .sus-dates-row { flex-wrap:wrap;gap:12px; } .sus-dates-chips { flex-wrap:wrap;gap:12px; } .sus-date-chip { border-right:none;margin-right:0;padding-right:0; } .sus-invoice-header { gap:14px; } .sus-inline-pay { width:100%;justify-content:space-between; } }
</style>

<script>
// ── Paginación historial ─────────────────────────────────────────────────────
(function() {
    const POR_PAG = 5;
    const items   = document.querySelectorAll('[data-hist-idx]');
    if (!items.length) return;
    const total   = items.length;
    const paginas = Math.ceil(total / POR_PAG);
    let pagActual = 1;

    function mostrar(pag) {
        pagActual = pag;
        const desde = (pag - 1) * POR_PAG;
        const hasta  = desde + POR_PAG;
        items.forEach((el, i) => {
            el.style.display = (i >= desde && i < hasta) ? '' : 'none';
        });
        const info = document.getElementById('histPagInfo');
        const prev = document.getElementById('histPrev');
        const next = document.getElementById('histNext');
        if (info) info.textContent = 'Pág. ' + pag + ' / ' + paginas;
        if (prev) prev.disabled = pag <= 1;
        if (next) next.disabled = pag >= paginas;
    }

    window.histCambiarPag = function(dir) { mostrar(pagActual + dir); };
    mostrar(1);
})();

let _planSolicitado = null;

function solicitarPlan(slug, nombre, color) {
    _planSolicitado = { slug, nombre, color };
    const ico = document.getElementById('cambioIcono');
    ico.style.background = color + '20';
    ico.style.color      = color;
    document.getElementById('cambioTitulo').textContent  = 'Cambiar a plan ' + nombre;
    document.getElementById('btnConfirmarCambio').style.background = color;
    document.getElementById('cambioMsg').style.display   = 'none';
    document.getElementById('modalCambio').style.display = 'flex';
}

async function confirmarCambio() {
    if (!_planSolicitado) return;
    const btn = document.getElementById('btnConfirmarCambio');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    const msg = `Solicito cambio al Plan ${_planSolicitado.nombre}. Por favor coordinen el proceso de pago.`;

    try {
        const res = await fetch('<?= $basePath ?>/chat/soporte/enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje: msg })
        });
        const d = await res.json();
        const el = document.getElementById('cambioMsg');
        el.style.display = 'block';
        if (d.success || d.ok) {
            el.style.cssText = 'display:block;background:#d5f5e3;color:#1e8449;border-radius:8px;padding:12px 16px;font-size:13px;';
            el.innerHTML = '<i class="fas fa-check-circle"></i> Solicitud enviada. El equipo de ChefControl te contactará pronto.';
            btn.style.display = 'none';
        } else {
            el.style.cssText = 'display:block;background:#fadbd8;color:#c0392b;border-radius:8px;padding:12px 16px;font-size:13px;';
            el.textContent = 'Error al enviar la solicitud. Intenta de nuevo.';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar solicitud';
        }
    } catch(e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar solicitud';
    }
}

document.getElementById('modalCambio').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('modalPlanes').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// ── Selección de método de pago ───────────────────────────────────────────────
const _tieneTarjeta  = <?= $metodoPagoData ? 'true' : 'false' ?>;
const _tarjetaMasked = <?= json_encode($metodoPagoData['masked'] ?? '') ?>;
let _pagoMonto = 0, _pagoPlan = '', _pagoPlanNombre = '';

<?php
$ep_precio = (float)($planActualData['precio'] ?? 0);
$ep_plan   = htmlspecialchars($planActualData['slug'] ?? $planSlugActual, ENT_QUOTES);
$ep_nombre = htmlspecialchars($planLabel, ENT_QUOTES);
$ep_email  = htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES);
$ep_user   = htmlspecialchars($_SESSION['usuario_nombre'] ?? '', ENT_QUOTES);
$ep_cid    = (int)($cid ?? 0);
$respUrl   = Config::getBaseUrl() . '/suscripcion/epayco/respuesta';
$confUrl   = Config::getBaseUrl() . '/suscripcion/epayco/confirmacion';
?>

function iniciarPago(monto, plan, planNombre) {
    _pagoMonto = monto; _pagoPlan = plan; _pagoPlanNombre = planNombre;
    if (!_tieneTarjeta) {
        // Sin tarjeta: ir directo a ePayco
        lanzarEpayco(monto, plan, planNombre); return;
    }
    // Con tarjeta: mostrar selector
    document.getElementById('epModalDesc').textContent    = '$' + Number(monto).toLocaleString('es-CO') + ' COP · ' + planNombre;
    document.getElementById('epModalMasked').textContent  = _tarjetaMasked;
    document.getElementById('modalElegirPago').style.display = 'flex';
}

function cerrarElegirPago() {
    document.getElementById('modalElegirPago').style.display = 'none';
}

function confirmarPagoTarjeta() {
    cerrarElegirPago();
    pagarConTarjeta(_pagoMonto, _pagoPlan, _pagoPlanNombre);
}

function confirmarPagoEpayco() {
    cerrarElegirPago();
    lanzarEpayco(_pagoMonto, _pagoPlan, _pagoPlanNombre);
}

function lanzarEpayco(monto, plan, planNombre) {
    const err = document.getElementById('mpError');
    if (err) err.style.display = 'none';

    const handler = window.ePayco?.checkout?.configure({
        key:  '<?= Config::EPAYCO_PUBLIC_KEY ?>',
        test: <?= Config::EPAYCO_TEST ? 'true' : 'false' ?>
    });
    if (!handler) { alert('Error al cargar ePayco. Recarga la página.'); return; }

    handler.open({
        name:             'ChefControl',
        description:      'Suscripción ' + (planNombre || '<?= $ep_nombre ?>'),
        invoice:          'CC-<?= $ep_cid ?>-' + Date.now(),
        currency:         'cop',
        amount:           String(monto || '<?= $ep_precio ?>'),
        tax_base:         '0',
        tax:              '0',
        country:          'CO',
        lang:             'es',
        external:         'false',
        response:         '<?= $respUrl ?>',
        confirmation:     '<?= $confUrl ?>',
        email_billing:    '<?= $ep_email ?>',
        name_billing:     '<?= $ep_user ?>',
        type_doc_billing: 'cc',
        p_cust_id:        '<?= Config::EPAYCO_CUST_ID ?>',
        extra2:           plan || '<?= $ep_plan ?>',
        extra3:           '<?= $ep_cid ?>',
    });
}

async function pagarConTarjeta(monto, plan, planNombre) {
    // Bloquear todos los botones de pago
    document.querySelectorAll('[onclick^="iniciarPago"]').forEach(b => {
        b.disabled = true;
        b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    });

    const fd = new FormData();
    fd.append('monto',      monto);
    fd.append('plan',       plan);
    fd.append('planNombre', planNombre);

    try {
        const res  = await fetch('<?= $basePath ?>/suscripcion/cobrar', { method:'POST', body:fd });
        const data = await res.json();

        if (data.ok) {
            // Mostrar resultado exitoso y recargar
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;display:flex;align-items:center;justify-content:center;';
            modal.innerHTML = `
                <div style="background:#fff;border-radius:20px;padding:36px 32px;max-width:380px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);">
                    <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;color:#16a34a;
                                display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 16px;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <h3 style="margin:0 0 8px;color:#16a34a;font-size:20px;">¡Pago exitoso!</h3>
                    <p style="color:#6b7280;font-size:14px;margin:0 0 6px;">
                        $${Number(monto).toLocaleString('es-CO')} COP · Plan ${planNombre}
                    </p>
                    <p style="color:#9ca3af;font-size:12px;margin:0 0 20px;">Ref: ${data.ref || '—'} · Vigente hasta ${data.hasta || '—'}</p>
                    <button onclick="location.reload()"
                            style="background:#16a34a;color:#fff;border:none;border-radius:10px;
                                   padding:12px 28px;font-size:14px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-refresh"></i> Actualizar
                    </button>
                </div>`;
            document.body.appendChild(modal);
        } else {
            let msg = data.msg || 'Error al procesar el pago.';
            if (data._raw) msg += '\n[debug] ' + JSON.stringify(data._raw);
            alert('❌ ' + msg);
            document.querySelectorAll('[onclick^="iniciarPago"]').forEach(b => {
                b.disabled = false;
                b.innerHTML = '<i class="fas fa-credit-card"></i> Pagar';
            });
        }
    } catch(e) {
        alert('Error de conexión. Intenta de nuevo.');
        document.querySelectorAll('[onclick^="iniciarPago"]').forEach(b => {
            b.disabled = false;
            b.innerHTML = '<i class="fas fa-credit-card"></i> Pagar';
        });
    }
}

// ── Método de pago · tokenización ────────────────────────────────────────────
let _mpTipo = 'tarjeta_credito';

function abrirMetodoPago() {
    document.getElementById('mpError').style.display = 'none';
    document.getElementById('mpNumero').value   = '';
    document.getElementById('mpExp').value      = '';
    document.getElementById('mpCvv').value      = '';
    document.getElementById('mpNombre').value   = '';
    document.getElementById('mpApellido').value = '';
    document.getElementById('mpDocNum').value   = '';
    selMpTipo('tarjeta_credito');
    document.querySelector('input[name="mp_tipo"][value="tarjeta_credito"]').checked = true;
    document.getElementById('modalMetodoPago').style.display = 'flex';
}

function cerrarMetodoPago() {
    document.getElementById('modalMetodoPago').style.display = 'none';
}

function selMpTipo(val) {
    _mpTipo = val;
    const cols = { tarjeta_credito:'#6366f1', tarjeta_debito:'#06b6d4' };
    ['tarjeta_credito','tarjeta_debito'].forEach(t => {
        const el = document.getElementById('mpOpt-' + t);
        if (!el) return;
        if (t === val) {
            el.style.borderColor = cols[t];
            el.style.background  = cols[t] + '0d';
        } else {
            el.style.borderColor = '#e8ecef';
            el.style.background  = '#fff';
        }
    });
}

function formatCardNum(el) {
    let v = el.value.replace(/\D/g, '').slice(0, 16);
    el.value = v.replace(/(.{4})/g, '$1 ').trim();
    // icono por tipo
    const ico = document.getElementById('mpCardIcon');
    if (ico) ico.style.color = v.length > 0 ? '<?= $planColor ?>' : '#d1d5db';
}

function formatExp(el) {
    let v = el.value.replace(/\D/g, '').slice(0, 4);
    if (v.length >= 3) v = v.slice(0, 2) + '/' + v.slice(2);
    el.value = v;
}

async function registrarTarjeta() {
    const err  = document.getElementById('mpError');
    const btn  = document.getElementById('btnRegistrarTarjeta');
    err.style.display = 'none';

    const num    = document.getElementById('mpNumero').value.replace(/\s/g, '');
    const expRaw = document.getElementById('mpExp').value.split('/');
    const expM   = (expRaw[0] || '').trim();
    const expY   = (expRaw[1] || '').trim();
    const cvc    = document.getElementById('mpCvv').value.trim();
    const nombre = document.getElementById('mpNombre').value.trim();

    if (num.length < 13) {
        err.style.display='block'; err.textContent='Número de tarjeta inválido.'; return;
    }
    if (!expM || !expY || expM.length !== 2) {
        err.style.display='block'; err.textContent='Fecha de expiración inválida (MM/AA).'; return;
    }
    if (cvc.length < 3) {
        err.style.display='block'; err.textContent='CVV inválido.'; return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Tokenizando...';

    try {
        const fd = new FormData();
        fd.append('numero',    num);
        fd.append('exp_month', expM);
        fd.append('exp_year',  expY.length === 2 ? '20' + expY : expY);
        fd.append('cvc',       cvc);
        fd.append('nombre',    nombre || 'Titular');
        fd.append('apellido',  document.getElementById('mpApellido').value.trim());
        fd.append('doc_tipo',  document.getElementById('mpDocTipo').value);
        fd.append('doc_num',   document.getElementById('mpDocNum').value.trim());
        fd.append('tipo',      _mpTipo);

        const res  = await fetch('<?= $basePath ?>/suscripcion/tokenizar', { method:'POST', body:fd });
        const data = await res.json();

        if (data.ok) {
            cerrarMetodoPago();
            // Actualizar UI sin recargar (sin recargar página)
            document.querySelectorAll('.sus-mp-card').forEach(c => {
                c.innerHTML = `
                    <div class="sus-mp-header">
                        <i class="fas fa-shield-halved" style="color:<?= $planColor ?>;"></i>
                        <span>Método de pago</span>
                        <span class="sus-mp-enc-badge"><i class="fas fa-lock"></i> Tokenizado</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                        <div class="sus-mp-icon" style="background:#6366f115;color:#6366f1;">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:700;color:#1f2937;font-size:14px;">
                                ${_mpTipo === 'tarjeta_credito' ? 'Tarjeta crédito' : 'Tarjeta débito'}
                            </div>
                            <div style="font-family:monospace;color:#6b7280;font-size:13px;letter-spacing:1px;">
                                ${data.masked}
                            </div>
                        </div>
                    </div>
                    <div class="sus-auto-row">
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#1f2937;">Cobro automático</div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:1px;">Renovar al vencer el período</div>
                        </div>
                        <label class="sus-toggle">
                            <input type="checkbox" id="chkCobroAuto" onchange="toggleCobroAuto(this.checked)">
                            <span class="sus-toggle-slider" style="--tc:<?= $planColor ?>;"></span>
                        </label>
                    </div>`;
            });
        } else {
            err.style.display = 'block';
            let msg = data.msg || 'Error al tokenizar la tarjeta.';
            if (data._raw) msg += '\n[debug] ' + JSON.stringify(data._raw);
            err.style.whiteSpace = 'pre-wrap';
            err.textContent = msg;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-lock"></i> Registrar tarjeta';
        }
    } catch(e) {
        err.style.display = 'block';
        err.textContent   = 'Error de conexión. Intenta de nuevo.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Registrar tarjeta';
    }
}

async function eliminarTarjeta() {
    if (!confirm('¿Eliminar la tarjeta registrada?')) return;
    const res  = await fetch('<?= $basePath ?>/suscripcion/eliminar-tarjeta', { method:'POST' });
    const data = await res.json();
    if (data.ok) location.reload();
    else alert('Error al eliminar: ' + (data.msg || ''));
}

async function toggleCobroAuto(activo) {
    try {
        const fd = new FormData();
        fd.append('activo', activo ? '1' : '0');
        await fetch('<?= $basePath ?>/suscripcion/cobro-automatico', { method:'POST', body:fd });
    } catch(e) {}
}

document.getElementById('modalMetodoPago').addEventListener('click', function(e) {
    if (e.target === this) cerrarMetodoPago();
});
document.getElementById('modalElegirPago').addEventListener('click', function(e) {
    if (e.target === this) cerrarElegirPago();
});
</script>

<!-- ePayco checkout SDK -->
<script src="https://checkout.epayco.co/checkout.js"></script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
