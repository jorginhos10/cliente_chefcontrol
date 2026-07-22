<?php
// vista/configuraciones/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Configuraciones - CHEFCONTROL';
$paginaActual = 'configuraciones';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/configuraciones.css?v=3">';

require_once __DIR__ . '/../complementos/header.php';

// Stats rápidas
$mTotal       = $mesaStats['total']       ?? 0;
$mDisponibles = $mesaStats['disponibles'] ?? 0;
$mOcupadas    = $mesaStats['ocupadas']    ?? 0;

$uTotal       = $usuarioStats['total']    ?? 0;
$uActivos     = $usuarioStats['activos']  ?? 0;
$uAdmins      = $usuarioStats['administradores'] ?? 0;
?>

<div class="conf-container">

    <!-- Cabecera -->
    <div class="conf-header">
        <div class="conf-header-icon"><i class="fas fa-cog"></i></div>
        <div>
            <h1>Configuraciones</h1>
            <p>Panel central de administración del sistema</p>
        </div>
    </div>

    <!-- Sección: Módulos operativos -->
    <div class="conf-section-label">
        <i class="fas fa-th-large"></i> Módulos del sistema
    </div>

    <div class="conf-modules-grid">

        <!-- Comercio (PRIMERO) -->
        <a href="<?php echo $basePath; ?>/configuraciones/comercio" class="conf-module-card card-comercio">
            <div class="card-color-top" style="background:linear-gradient(135deg,#3a28b0,#6c5ce7);">
                <?php if (!empty($comercio['logo'])): ?>
                    <img src="<?php echo $baseUrl; ?>/assets/uploads/comercio/<?php echo htmlspecialchars($comercio['logo']); ?>"
                         alt="Logo" style="width:100%;height:100%;object-fit:contain;padding:16px;filter:brightness(0) invert(1);">
                <?php else: ?>
                    <i class="fas fa-building card-big-icon" style="font-size:36px;color:rgba(255,255,255,.95);"></i>
                <?php endif; ?>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Comercio</h3>
                <p class="card-desc">
                    <?php echo !empty($comercio['nombre']) ? htmlspecialchars($comercio['nombre']) : 'Configura el nombre, logo y datos fiscales de tu negocio.'; ?>
                </p>
                <div class="card-stats">
                    <?php if (!empty($comercio['rut'])): ?>
                    <span class="stat-chip"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($comercio['rut']); ?></span>
                    <?php else: ?>
                    <span class="stat-chip"><i class="fas fa-id-card"></i> RUT/NIT</span>
                    <?php endif; ?>
                    <?php if (!empty($comercio['ciudad'])): ?>
                    <span class="stat-chip"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($comercio['ciudad']); ?></span>
                    <?php else: ?>
                    <span class="stat-chip"><i class="fas fa-location-dot"></i> Ciudad</span>
                    <?php endif; ?>
                    <span class="stat-chip"><i class="fas fa-coins"></i> <?php echo htmlspecialchars($comercio['moneda'] ?? 'USD'); ?></span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Editar información <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:<?php echo !empty($comercio['nombre']) ? '#27ae60' : '#e74c3c'; ?>"></span>
            </div>
        </a>

        <!-- Menú Digital -->
        <?php if (modOk('menu-digital')): ?>
        <a href="<?php echo $basePath; ?>/menu-digital" class="conf-module-card">
            <div class="card-color-top" style="background:linear-gradient(135deg,#1a1a2e,#4a4a8a);">
                <i class="fas fa-qrcode card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Menú Digital</h3>
                <p class="card-desc">Genera un QR para que tus clientes consulten el menú desde su celular sin necesidad de carta física.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-qrcode"></i> Código QR</span>
                    <span class="stat-chip"><i class="fas fa-mobile-screen"></i> Vista móvil</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver menú digital <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#27ae60;"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Facturación -->
        <a href="<?php echo $basePath; ?>/facturacion" class="conf-module-card card-facturacion">
            <div class="card-color-top" style="background:linear-gradient(135deg,#0a6e63,#0d8a7e);">
                <i class="fas fa-file-invoice-dollar card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Facturación</h3>
                <p class="card-desc">Gestiona la facturación electrónica y documentos fiscales del negocio.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-receipt"></i> Facturas</span>
                    <span class="stat-chip"><i class="fas fa-file-invoice"></i> Documentos</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver facturación <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>

        <!-- Mesas -->
        <?php if (modOk('mesas')): ?>
        <a href="<?php echo $basePath; ?>/mesas" class="conf-module-card card-mesas">
            <div class="card-color-top">
                <i class="fas fa-chair card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Mesas</h3>
                <p class="card-desc">Monitorea y cambia el estado de las mesas del restaurante en tiempo real.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-chair"></i> <?php echo $mTotal; ?> total</span>
                    <span class="stat-chip" style="color:#27ae60;border-color:#d5f5e3;background:#d5f5e3;">
                        <i class="fas fa-circle-check"></i> <?php echo $mDisponibles; ?> disponibles
                    </span>
                    <?php if ($mOcupadas > 0): ?>
                    <span class="stat-chip" style="color:#e74c3c;border-color:#fadbd8;background:#fadbd8;">
                        <i class="fas fa-circle-xmark"></i> <?php echo $mOcupadas; ?> ocupadas
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Gestionar mesas <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Usuarios -->
        <a href="<?php echo $basePath; ?>/usuarios" class="conf-module-card card-usuarios">
            <div class="card-color-top">
                <i class="fas fa-users card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Usuarios</h3>
                <p class="card-desc">Administra las cuentas de usuario, roles y permisos de acceso al sistema.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-users"></i> <?php echo $uTotal; ?> total</span>
                    <span class="stat-chip" style="color:#27ae60;border-color:#d5f5e3;background:#d5f5e3;">
                        <i class="fas fa-user-check"></i> <?php echo $uActivos; ?> activos
                    </span>
                    <span class="stat-chip" style="color:#2980b9;border-color:#d6eaf8;background:#d6eaf8;">
                        <i class="fas fa-user-shield"></i> <?php echo $uAdmins; ?> admin
                    </span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Gestionar usuarios <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>

        <!-- Recetas -->
        <?php if (modOk('recetas')): ?>
        <a href="<?php echo $basePath; ?>/recetas" class="conf-module-card card-recetas">
            <div class="card-color-top">
                <i class="fas fa-book-open card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Recetas</h3>
                <p class="card-desc">Crea y configura las recetas del menú con sus ingredientes y precios de venta.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-book"></i> Gestión de menú</span>
                    <span class="stat-chip"><i class="fas fa-tag"></i> Precios de venta</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Gestionar recetas <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Categorías de recetas (parte del módulo Recetas) -->
        <?php if (modOk('recetas')): ?>
        <a href="<?php echo $basePath; ?>/categorias" class="conf-module-card card-categorias">
            <div class="card-color-top" style="background:linear-gradient(135deg,#6c3483,#9b59b6);">
                <i class="fas fa-tags card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Categorías</h3>
                <p class="card-desc">Crea y organiza las categorías que clasifican las recetas del menú.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-tags"></i> Clasificación</span>
                    <span class="stat-chip"><i class="fas fa-book-open"></i> Usado en Recetas</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Gestionar categorías <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#27ae60"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Insumos -->
        <?php if (modOk('insumos')): ?>
        <a href="<?php echo $basePath; ?>/insumos" class="conf-module-card card-insumos">
            <div class="card-color-top">
                <i class="fas fa-carrot card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Insumos</h3>
                <p class="card-desc">Administra los ingredientes y productos disponibles, con proveedores y precios.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-boxes"></i> Ingredientes</span>
                    <span class="stat-chip"><i class="fas fa-truck"></i> Proveedores</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Gestionar insumos <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Insumos de uso interno -->
        <?php if (modOk('insumos-internos')): ?>
        <a href="<?php echo $basePath; ?>/insumos-internos" class="conf-module-card card-insumos-internos">
            <div class="card-color-top" style="background:linear-gradient(135deg,#37474f,#607d8b);">
                <i class="fas fa-broom card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Insumo de Uso Interno</h3>
                <p class="card-desc">Administra productos que consume el negocio pero no forman parte de las recetas, como limpieza o papelería.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-spray-can-sparkles"></i> Limpieza</span>
                    <span class="stat-chip"><i class="fas fa-file-lines"></i> Papelería</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Gestionar insumos internos <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#27ae60"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Inventario -->
        <?php if (modOk('inventario')): ?>
        <a href="<?php echo $basePath; ?>/inventario" class="conf-module-card card-inventario">
            <div class="card-color-top">
                <i class="fas fa-boxes-stacked card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Inventario</h3>
                <p class="card-desc">Registra entradas, salidas y ajustes de stock con historial de movimientos.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-arrow-down"></i> Entradas</span>
                    <span class="stat-chip"><i class="fas fa-arrow-up"></i> Salidas</span>
                    <span class="stat-chip"><i class="fas fa-sliders"></i> Ajustes</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver inventario <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Domicilios -->
        <?php if (modOk('domicilios')): ?>
        <a href="<?php echo $basePath; ?>/configuraciones/domicilios" class="conf-module-card">
            <div class="card-color-top" style="background:linear-gradient(135deg,#1a3c5e,#2980b9);">
                <i class="fas fa-motorcycle card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Domicilios</h3>
                <p class="card-desc">Configura los links de pedido, horarios y categorías para tus canales de venta a domicilio.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-link"></i> Links de pedido</span>
                    <span class="stat-chip"><i class="fas fa-clock"></i> Horarios</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Configurar domicilios <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#27ae60"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Integraciones -->
        <a href="<?php echo $basePath; ?>/configuraciones/integraciones" class="conf-module-card card-integraciones">
            <div class="card-color-top">
                <i class="fas fa-plug card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Integraciones</h3>
                <p class="card-desc">Conecta ChefControl con plataformas externas: WhatsApp, pasarelas de pago y servicios digitales.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                    <span class="stat-chip"><i class="fas fa-credit-card"></i> Pagos</span>
                    <span class="stat-chip"><i class="fas fa-code"></i> APIs</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver integraciones <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#27ae60"></span>
            </div>
        </a>

        <!-- Impresoras de cocina -->
        <a href="<?php echo $basePath; ?>/configuraciones/impresoras" class="conf-module-card card-impresoras">
            <div class="card-color-top" style="background:linear-gradient(135deg,#1a1a2e,#16213e);">
                <i class="fas fa-print card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Impresoras de cocina</h3>
                <p class="card-desc">Conecta impresoras térmicas para imprimir comandas automáticas al recibir un pedido.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-print"></i> Impresión automática</span>
                    <span class="stat-chip"><i class="fas fa-ruler-horizontal"></i> 80mm y 58mm</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver impresoras <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#f39c12"></span>
            </div>
        </a>

        <!-- Reportes -->
        <?php if (modOk('reportes')): ?>
        <a href="<?php echo $basePath; ?>/reportes" class="conf-module-card card-reportes">
            <div class="card-color-top">
                <i class="fas fa-chart-bar card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Reportes</h3>
                <p class="card-desc">Consulta y exporta reportes de movimientos con filtros por fecha, tipo e insumo.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-file-pdf"></i> PDF</span>
                    <span class="stat-chip"><i class="fas fa-file-excel"></i> Excel</span>
                    <span class="stat-chip"><i class="fas fa-file-csv"></i> CSV</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver reportes <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot"></span>
            </div>
        </a>
        <?php endif; ?>

        <!-- Inventario Inmobiliario -->
        <?php if (modOk('inventario-inmobiliario')): ?>
        <a href="<?php echo $basePath; ?>/inventario-inmobiliario" class="conf-module-card card-inmobiliario">
            <div class="card-color-top">
                <i class="fas fa-couch card-big-icon"></i>
                <div class="card-arrow-top"><i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="card-body">
                <h3 class="card-title">Inventario Inmobiliario</h3>
                <p class="card-desc">Registra los bienes muebles e inmuebles del negocio con foto y valor evaluado.</p>
                <div class="card-stats">
                    <span class="stat-chip"><i class="fas fa-camera"></i> Foto</span>
                    <span class="stat-chip"><i class="fas fa-sack-dollar"></i> Valor evaluado</span>
                </div>
            </div>
            <div class="card-footer">
                <span class="card-cta">Ver inventario inmobiliario <i class="fas fa-chevron-right"></i></span>
                <span class="card-status-dot" style="background:#27ae60"></span>
            </div>
        </a>
        <?php endif; ?>

    </div><!-- /conf-modules-grid -->

</div><!-- /conf-container -->

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
