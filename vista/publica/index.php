<?php
// vista/publica/index.php

// ⚠️ NO incluye security.php - Esta página es PÚBLICA ⚠️
$basePath = Config::getBasePath();
$baseUrl = Config::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Pública - CHEFCONTROL</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboardContainer">
        <nav class="sidebar">
            <div class="logo">
                <h1>CHEFCONTROL</h1>
            </div>
            <ul class="navMenu">
                <li class="navItem">
                    <a href="<?php echo $basePath; ?>/publica" class="navLink">
                        <i class="fas fa-globe"></i>
                        Página Pública
                    </a>
                </li>
                <li class="navItem">
                    <a href="<?php echo $basePath; ?>/login" class="navLink">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </a>
                </li>
            </ul>
        </nav>

        <main class="mainContent">
            <header class="header">
                <div class="welcomeSection">
                    <h1>¡Página Pública!</h1>
                    <p>Esta es una página PÚBLICA - Cualquiera puede verla sin iniciar sesión</p>
                </div>
                <div class="userMenu">
                    <?php if (Security::isLoggedIn()): ?>
                        <a href="<?php echo $basePath; ?>/dashboard" class="logoutBtn" style="background: #2ecc71;">
                            <i class="fas fa-tachometer-alt"></i>
                            Ir al Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $basePath; ?>/login" class="logoutBtn" style="background: #3498db;">
                            <i class="fas fa-sign-in-alt"></i>
                            Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <div class="statsGrid">
                <div class="statCard">
                    <div class="statIcon" style="color: #2ecc71;">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="statValue">Pública</div>
                    <div class="statLabel">Acceso libre para todos</div>
                </div>
                
                <div class="statCard">
                    <div class="statIcon" style="color: #e74c3c;">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="statValue">Privada</div>
                    <div class="statLabel">Requiere inicio de sesión</div>
                </div>
            </div>

            <div class="recentActivity">
                <h2 class="sectionTitle">Información de la Página</h2>
                <ul class="activityList">
                    <li class="activityItem">
                        <strong>Página Pública</strong>
                        <p>No requiere autenticación para ser vista</p>
                        <div class="activityTime">Acceso libre</div>
                    </li>
                    <li class="activityItem">
                        <strong>Página Privada</strong>
                        <p>Requiere haber iniciado sesión</p>
                        <div class="activityTime">Acceso restringido</div>
                    </li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>