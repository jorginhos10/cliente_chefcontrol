<?php
// vista/complementos/header.php

// Incluir config para usar la clase Config
require_once __DIR__ . '/../../config/config.php';

$basePath = Config::getBasePath();
$baseUrl = Config::getBaseUrl();
$usuarioLogueado = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Calcula el rango de fechas del período vigente según la configuración guardada
function _headerPeriodoVigente(string $configJson): array {
    $hoy     = new DateTime();
    $dia     = (int)$hoy->format('j');
    $diasMes = (int)$hoy->format('t');
    $mesStr  = $hoy->format('Y-m');

    $periodos = [];
    try { $periodos = json_decode($configJson, true) ?: []; } catch (Exception $e) {}

    // 1. Buscar en rangos personalizados (del día X al Y de cada mes)
    foreach ($periodos as $p) {
        if (($p['tipo'] ?? '') === 'personalizado') {
            $d = (int)$p['desde'];
            $h = min((int)$p['hasta'], $diasMes);
            if ($dia >= $d && $dia <= $h) {
                return [
                    'desde' => $mesStr . '-' . str_pad($d, 2, '0', STR_PAD_LEFT),
                    'hasta' => $mesStr . '-' . str_pad($h, 2, '0', STR_PAD_LEFT),
                ];
            }
        }
    }

    // 2. Buscar en períodos predefinidos (cada N días desde el día 1)
    foreach ($periodos as $p) {
        if (($p['tipo'] ?? '') === 'predefinido') {
            $n        = max(1, (int)$p['valor']);
            $startDay = (int)(floor(($dia - 1) / $n) * $n) + 1;
            $endDay   = min($startDay + $n - 1, $diasMes);
            return [
                'desde' => $mesStr . '-' . str_pad($startDay, 2, '0', STR_PAD_LEFT),
                'hasta' => $mesStr . '-' . str_pad($endDay,   2, '0', STR_PAD_LEFT),
            ];
        }
    }

    // 3. Sin config: mes completo hasta hoy
    return ['desde' => $mesStr . '-01', 'hasta' => $hoy->format('Y-m-d')];
}

// Propinas acumuladas del usuario en el período vigente
$propinasAcumuladas  = 0;
$propinasDesde       = '';
$propinasHasta       = '';
if ($usuarioLogueado && !empty($_SESSION['usuario_id'])) {
    try {
        $dsn = "mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME . ";charset=" . Config::DB_CHARSET;
        $pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $configRow    = $pdo->query("SELECT propina_periodo_config, propina_label_header, propina_distribucion, propina_num_personas FROM comercio LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $configJson   = $configRow['propina_periodo_config'] ?? '[]';
        $distribucion = $configRow['propina_distribucion']   ?? 'individual';
        $numPersonas  = max(2, (int)($configRow['propina_num_personas'] ?? 2));

        if (!(int)($configRow['propina_label_header'] ?? 1)) {
            $propinasAcumuladas = -1; // señal para ocultar el label
        } else {

        $periodo       = _headerPeriodoVigente($configJson);
        $propinasDesde = $periodo['desde'];
        $propinasHasta = $periodo['hasta'];

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(monto),0) FROM propinas
             WHERE id_usuario = :uid
               AND DATE(fecha_creacion) BETWEEN :desde AND :hasta"
        );
        $stmt->execute([':uid' => (int)$_SESSION['usuario_id'], ':desde' => $propinasDesde, ':hasta' => $propinasHasta]);
        $totalPropinas = (float)$stmt->fetchColumn();
        // Aplicar distribución colectiva
        $propinasAcumuladas = ($distribucion === 'colectiva' && $numPersonas > 1)
            ? $totalPropinas / $numPersonas
            : $totalPropinas;
        } // cierre del else
    } catch (Exception $e) {
        $propinasAcumuladas = 0;
    }
}

// Obtener avatar del usuario o usar default
$avatar = $usuarioLogueado ? ($_SESSION['usuario_avatar'] ?? 'default.png') : 'default.png';
$avatarUrl = $baseUrl . '/assets/media/users/' . $avatar;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo ?? 'CHEFCONTROL'; ?></title>
    <meta name="base-path" content="<?php echo $basePath; ?>">
    <link rel="manifest" href="<?php echo $baseUrl; ?>/manifest.json">
    <meta name="theme-color" content="#1a1d27">
    <link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>/assets/media/src/logo.png">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/dashboard.css?v=<?php echo Config::assetVer('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/header.css?v=<?php echo Config::assetVer('assets/css/header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <?php echo $cssExtra ?? ''; ?>
</head>
<body>
    <div class="dashboardContainer">
        <!-- Incluir Sidebar -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="mainContent">
            <!-- Nuevo Header -->
            <header class="mainHeader">
                <!-- Logo a la izquierda -->
                <div class="headerLeft">
                    <div class="logoHeader">
                        <i class="fas fa-utensils"></i>
                        <span>CHEFCONTROL</span>
                    </div>
                </div>

                <!-- Información de usuario a la derecha -->
                <div class="headerRight">
                    <?php if ($usuarioLogueado): ?>

                    <!-- Campana de notificaciones -->
                    <div class="notif-bell" id="notifBell">
                        <button class="notif-btn" id="notifBtn" aria-label="Notificaciones">
                            <i class="fas fa-bell"></i>
                            <span class="notif-badge" id="notifBadge"></span>
                        </button>
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-drop-head">
                                <span>Notificaciones</span>
                                <span class="notif-count-badge" id="notifDropCount"></span>
                            </div>
                            <div class="notif-drop-body" id="notifDropBody">
                                <div class="notif-empty">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>Sin notificaciones nuevas</p>
                                </div>
                            </div>
                            <div class="notif-drop-footer">
                                <a href="<?php echo $basePath; ?>/domicilios">
                                    <i class="fas fa-motorcycle"></i> Ir a Domicilios
                                </a>
                            </div>
                        </div>
                    </div>

                        <div class="userDropdown">
                            <button class="userDropdownBtn">
                                <div class="userAvatar">
                                    <img src="<?php echo $avatarUrl; ?>" 
                                         alt="<?php echo $_SESSION['usuario_nombre']; ?>"
                                         onerror="this.src='<?php echo $baseUrl; ?>/assets/media/users/default.png'">
                                </div>
                                <div class="userInfo">
                                    <span class="userName"><?php echo $_SESSION['usuario_nombre']; ?></span>
                                    <span class="userRole"><?php echo ucfirst($_SESSION['usuario_rol']); ?></span>
                                </div>
                                <i class="fas fa-chevron-down dropdownArrow"></i>
                            </button>
                            
                            <div class="dropdownMenu">
                                <a href="<?php echo $basePath; ?>/perfil" class="dropdownItem">
                                    <i class="fas fa-user"></i>
                                    <span>Mi Perfil</span>
                                </a>
                                <a href="<?php echo $basePath; ?>/configuracion" class="dropdownItem">
                                    <i class="fas fa-cog"></i>
                                    <span>Configuración</span>
                                </a>
                                <a href="<?php echo $basePath; ?>/suscripcion" class="dropdownItem">
                                    <i class="fas fa-crown"></i>
                                    <span>Mi Plan</span>
                                </a>
                                <div class="dropdownDivider"></div>
                                <?php if ($propinasAcumuladas >= 0 && modOk('propinas')): ?>
                                <!-- Propinas período vigente -->
                                <a href="<?php echo $basePath; ?>/propinas" class="dropdownItem" style="text-decoration:none;">
                                    <i class="fas fa-hand-holding-dollar" style="color:#c87d00;"></i>
                                    <div style="display:flex;flex-direction:column;gap:1px;flex:1;">
                                        <span style="font-size:11px;color:#6b6b6b;font-weight:500;">
                                            Mis propinas<?php
                                                if ($propinasDesde && $propinasHasta) {
                                                    $d1 = date('d/m', strtotime($propinasDesde));
                                                    $d2 = date('d/m', strtotime($propinasHasta));
                                                    echo ' · ' . ($d1 === $d2 ? $d1 : "$d1 – $d2");
                                                }
                                                if (isset($distribucion) && $distribucion === 'colectiva') {
                                                    echo ' · entre ' . $numPersonas . ' personas';
                                                }
                                            ?>
                                        </span>
                                        <span style="font-size:16px;font-weight:800;color:#c87d00;letter-spacing:-.3px;">
                                            $<?php echo number_format($propinasAcumuladas, 2); ?>
                                        </span>
                                    </div>
                                </a>
                                <div class="dropdownDivider"></div>
                                <?php endif; ?>
                                <a href="<?php echo $basePath; ?>/logout" class="dropdownItem">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Cerrar Sesión</span>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="guestActions">
                            <a href="<?php echo $basePath; ?>/login" class="loginBtn">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Iniciar Sesión</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Contenido principal de la página -->
            <div class="contentWrapper">