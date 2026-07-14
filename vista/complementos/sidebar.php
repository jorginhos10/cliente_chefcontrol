<?php
// vista/complementos/sidebar.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../modelo/permisoModel.php';

$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();
$logueado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$esAdmin  = isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
$pag      = $paginaActual ?? '';

$permIds = [];
if ($logueado && isset($_SESSION['usuario_id'])) {
    try {
        $pm = new PermisoModel();
        foreach ($pm->obtenerPermisosUsuario($_SESSION['usuario_id']) as $p) {
            if ($p['activo'] == 1) $permIds[] = $p['id'];
        }
    } catch (Exception $e) {}
}

// Macros de permisos
define('PID_DASHBOARD', 1);
define('PID_RECETAS',   2);
define('PID_INVENTARIO',3);
define('PID_REPORTES',  4);
define('PID_CONFIG',    5);

function perm(int $id, array $ids, bool $admin): bool {
    return $admin || in_array($id, $ids);
}

// ── Módulos habilitados según plan ────────────────────────────────────────────
$GLOBALS['_sbModDesactivados'] = [];
if ($logueado) {
    try {
        $cid = (int)($_SESSION['comercio_id'] ?? 0);
        if ($cid) {
            $row = DB::get()->prepare("SELECT plan, modulos_config FROM comercios WHERE id=? LIMIT 1");
            $row->execute([$cid]);
            $comRow = $row->fetch(PDO::FETCH_ASSOC);

            // Módulos desactivados por admin (override por restaurante)
            $adminDes = $comRow['modulos_config']
                ? (json_decode($comRow['modulos_config'], true) ?? [])
                : [];

            // Módulos habilitados por el plan
            $planDes = [];
            $planSlug = $comRow['plan'] ?? 'gratuito';
            try {
                $opts  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
                $dbSup = new PDO("mysql:host=".Config::DB_HOST.";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                                 Config::DB_USER, Config::DB_PASS, $opts);
                $ps = $dbSup->prepare("SELECT modulos FROM planes WHERE slug=? LIMIT 1");
                $ps->execute([$planSlug]);
                $planModulosJson = $ps->fetchColumn();
                // OJO: no usar !empty($planModulos) aquí — un array vacío ([]) es una
                // configuración válida y explícita (el admin quitó todos los módulos del
                // plan), distinta de "nunca se configuró" (columna NULL). Si se filtra por
                // empty(), un plan con 0 módulos se trata como "sin restricción" y todo
                // queda visible, que es justo el bug que estamos evitando.
                if ($planModulosJson !== false && $planModulosJson !== null) {
                    $planModulos = json_decode($planModulosJson, true) ?? [];
                    $todosSlugs = ['ventas','cocina','mesas','menu-digital','domicilios','clientes','cupones',
                                   'pqrs','propinas','recetas','insumos','insumos-internos','inventario','proveedores',
                                   'ingresos','perdidas','reportes','chat','notificaciones'];
                    $planDes = array_values(array_diff($todosSlugs, $planModulos));
                }
            } catch (\Throwable $e) {
                error_log('Sidebar plan/modulo — no se pudo verificar chefcontrol_sup: ' . $e->getMessage());
            }

            $merged = array_unique(array_merge($adminDes, $planDes));

            // Restricciones del usuario logueado (solo si no es admin)
            if (!$esAdmin) {
                $uid = (int)($_SESSION['usuario_id'] ?? 0);
                if ($uid) {
                    try {
                        $rusr = DB::get()->prepare("SELECT modulos_config FROM usuarios WHERE id=? AND comercio_id=? LIMIT 1");
                        $rusr->execute([$uid, $cid]);
                        $ujson = $rusr->fetchColumn();
                        $userDes = $ujson ? (json_decode($ujson, true) ?? []) : [];
                        $merged = array_unique(array_merge($merged, $userDes));
                    } catch (\Throwable $e) {}
                }
            }

            $GLOBALS['_sbModDesactivados'] = $merged;
        }
    } catch (\Throwable $e) {}
}

function modOk(string $slug): bool {
    return !in_array($slug, $GLOBALS['_sbModDesactivados'] ?? []);
}
?>
<nav class="sidebar" id="sidebar">
    <div class="sb-logo">
        <i class="fas fa-utensils sb-logo-icon"></i>
        <span class="sb-logo-text">ChefControl</span>
    </div>

    <ul class="sb-menu">
        <?php if ($logueado): ?>

        <li class="sb-item <?php echo $pag === 'dashboard' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/dashboard" class="sb-link" data-tip="Dashboard">
                <i class="fas fa-home"></i><span>Dashboard</span>
            </a>
        </li>

        <!-- GRUPO: Operaciones -->
        <li class="sb-sep">Operaciones</li>

        <?php if (modOk('ventas')): ?>
        <li class="sb-item <?php echo $pag === 'salon' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/ventas/salon" class="sb-link" data-tip="Salón">
                <i class="fas fa-store"></i><span>Salón</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('cocina')): ?>
        <li class="sb-item <?php echo $pag === 'cocina' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/cocina" class="sb-link" data-tip="Cocina">
                <i class="fas fa-fire"></i><span class="sb-dot" id="cocinaDotSb"></span><span>Cocina <span class="sb-badge" id="cocinaBadgeSb" style="display:none">0</span></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('chat')): ?>
        <li class="sb-item <?php echo $pag === 'chat' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/chat" class="sb-link" data-tip="Chat">
                <i class="fas fa-comments"></i><span class="sb-dot" id="chatDotSb"></span>
                <span>Chat <span class="sb-badge" id="chatBadgeSb" style="display:none">0</span></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('ventas')): ?>
        <li class="sb-item <?php echo $pag === 'ventas' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/ventas/listado" class="sb-link" data-tip="Ventas">
                <i class="fas fa-cash-register"></i><span>Ventas</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('domicilios')): ?>
        <li class="sb-item <?php echo $pag === 'domicilios' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/domicilios" class="sb-link" data-tip="Domicilios">
                <i class="fas fa-motorcycle"></i><span class="sb-dot" id="domiciliosDotSb"></span><span>Domicilios <span class="sb-badge" id="domiciliosBadgeSb" style="display:none">0</span></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('clientes')): ?>
        <li class="sb-item <?php echo $pag === 'clientes' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/clientes" class="sb-link" data-tip="Clientes">
                <i class="fas fa-user-friends"></i><span>Clientes</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('menu-digital')): ?>
        <li class="sb-item <?php echo $pag === 'menu-digital' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/menu-digital" class="sb-link" data-tip="Menú Digital">
                <i class="fas fa-qrcode"></i><span>Menú Digital</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- GRUPO: Gestión -->
        <?php
        $hayGestion = modOk('cupones') || modOk('pqrs') || modOk('propinas')
                   || (modOk('recetas') && perm(PID_RECETAS,$permIds,$esAdmin))
                   || (modOk('insumos') && perm(PID_INVENTARIO,$permIds,$esAdmin))
                   || modOk('ingresos');
        if ($hayGestion): ?>
        <li class="sb-sep">Gestión</li>
        <?php endif; ?>

        <?php if (modOk('cupones')): ?>
        <li class="sb-item <?php echo $pag === 'cupones' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/cupones" class="sb-link" data-tip="Descuentos">
                <i class="fas fa-ticket"></i><span>Descuentos</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('pqrs')): ?>
        <li class="sb-item <?php echo $pag === 'pqrs' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/pqrs" class="sb-link" data-tip="PQRS">
                <i class="fas fa-comment-dots"></i>
                <span>PQRS <span class="sb-badge" id="pqrsBadgeSb" style="display:none">0</span></span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('propinas')): ?>
        <li class="sb-item <?php echo $pag === 'propinas' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/propinas" class="sb-link" data-tip="Propinas">
                <i class="fas fa-hand-holding-dollar"></i><span>Propinas</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (modOk('recetas') && perm(PID_RECETAS, $permIds, $esAdmin)): ?>
        <li class="sb-item <?php echo $pag === 'recetas' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/recetas" class="sb-link" data-tip="Recetas">
                <i class="fas fa-book-open"></i><span>Recetas</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (perm(PID_INVENTARIO, $permIds, $esAdmin)): ?>
        <?php if (modOk('insumos')): ?>
        <li class="sb-item <?php echo $pag === 'insumos' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/insumos" class="sb-link" data-tip="Insumos">
                <i class="fas fa-carrot"></i><span>Insumos</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (modOk('insumos-internos')): ?>
        <li class="sb-item <?php echo $pag === 'insumos-internos' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/insumos-internos" class="sb-link" data-tip="Insumo de Uso Interno">
                <i class="fas fa-broom"></i><span>Uso Interno</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (modOk('inventario')): ?>
        <li class="sb-item <?php echo $pag === 'inventario' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/inventario" class="sb-link" data-tip="Inventario">
                <i class="fas fa-boxes-stacked"></i><span>Inventario</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (modOk('inventario-inmobiliario')): ?>
        <li class="sb-item <?php echo $pag === 'inventario-inmobiliario' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/inventario-inmobiliario" class="sb-link" data-tip="Inventario Inmobiliario">
                <i class="fas fa-couch"></i><span>Inv. Inmobiliario</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (modOk('proveedores')): ?>
        <li class="sb-item <?php echo $pag === 'proveedores' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/proveedores" class="sb-link" data-tip="Proveedores">
                <i class="fas fa-truck"></i><span>Proveedores</span>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (modOk('ingresos')): ?>
        <li class="sb-item <?php echo $pag === 'ingresos' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/ingresos" class="sb-link" data-tip="Ingresos">
                <i class="fas fa-money-bill-trend-up"></i><span>Ingresos</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- GRUPO: Análisis -->
        <?php
        $hayAnalisis = perm(PID_REPORTES, $permIds, $esAdmin)
                    && (modOk('perdidas') || modOk('reportes'));
        if ($hayAnalisis): ?>
        <li class="sb-sep">Análisis</li>
        <?php if (modOk('perdidas')): ?>
        <li class="sb-item <?php echo $pag === 'perdidas' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/perdidas" class="sb-link" data-tip="Pérdidas">
                <i class="fas fa-arrow-trend-down"></i><span>Pérdidas</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (modOk('reportes')): ?>
        <li class="sb-item <?php echo $pag === 'reportes' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/reportes" class="sb-link" data-tip="Reportes">
                <i class="fas fa-chart-bar"></i><span>Reportes</span>
            </a>
        </li>
        <li class="sb-item <?php echo $pag === 'reporte-x' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/reportes/reporte-x" class="sb-link" data-tip="Reporte X">
                <i class="fas fa-receipt"></i><span>Reporte X</span>
            </a>
        </li>
        <li class="sb-item <?php echo $pag === 'reporte-z' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/reportes/reporte-z" class="sb-link" data-tip="Cierre Z">
                <i class="fas fa-cash-register"></i><span>Cierre Z</span>
            </a>
        </li>
        <?php endif; ?>
        <?php endif; ?>

        <!-- GRUPO: Admin -->
        <?php if (perm(PID_CONFIG, $permIds, $esAdmin)): ?>
        <li class="sb-sep">Admin</li>
        <li class="sb-item <?php echo in_array($pag, ['configuraciones','configuracion','mesas','usuarios']) ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/configuraciones" class="sb-link" data-tip="Configuraciones">
                <i class="fas fa-cog"></i><span>Configuraciones</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Logout -->
        <li class="sb-sep"></li>
        <li class="sb-item">
            <a href="<?php echo $basePath; ?>/logout" class="sb-link sb-logout" data-tip="Cerrar sesión">
                <i class="fas fa-right-from-bracket"></i><span>Cerrar sesión</span>
            </a>
        </li>

        <?php else: ?>
        <li class="sb-item <?php echo $pag === 'login' ? 'active' : ''; ?>">
            <a href="<?php echo $basePath; ?>/login" class="sb-link" data-tip="Iniciar sesión">
                <i class="fas fa-right-to-bracket"></i><span>Iniciar sesión</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/sidebar.css">
