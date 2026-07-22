<?php
// vista/configuraciones/driver.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Driver — CHEFCONTROL';
$paginaActual = 'configuraciones';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="drv-container">

    <!-- Cabecera -->
    <div class="drv-header">
        <div class="drv-header-left">
            <a href="<?php echo $basePath; ?>/configuraciones" class="drv-back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="drv-header-icon"><i class="fas fa-microchip"></i></div>
            <div>
                <h1>Driver</h1>
                <p>Conexión con aplicativo externo para imprimir automáticamente</p>
            </div>
        </div>
    </div>

    <div class="drv-card">
        <div class="drv-card-head">
            <i class="fas fa-microchip drv-card-icon"></i>
            <span class="drv-badge">Próximamente</span>
        </div>
        <div class="drv-card-body">
            <h2>¿Qué es Driver?</h2>
            <p>
                Driver es un puente entre ChefControl y un aplicativo externo instalado en el
                dispositivo de impresión (celular, tablet o PC con impresora térmica conectada).
                Una vez configurado, las facturas, comandas y vauchers se envían e imprimen
                automáticamente, sin abrir el diálogo de impresión del navegador ni requerir
                que alguien confirme manualmente cada impresión.
            </p>

            <div class="drv-features">
                <div class="drv-feature">
                    <div class="drv-feature-icon"><i class="fas fa-print"></i></div>
                    <div>
                        <h4>Impresión automática</h4>
                        <p>Facturas, comandas y vauchers se imprimen solos al generarse, sin clics extra.</p>
                    </div>
                </div>
                <div class="drv-feature">
                    <div class="drv-feature-icon"><i class="fas fa-bluetooth-b"></i></div>
                    <div>
                        <h4>Impresoras térmicas Bluetooth/USB</h4>
                        <p>Compatible con los mismos formatos de papel ya configurados en Facturación (58mm, 80mm y Carta).</p>
                    </div>
                </div>
                <div class="drv-feature">
                    <div class="drv-feature-icon"><i class="fas fa-plug-circle-bolt"></i></div>
                    <div>
                        <h4>Sin intervención manual</h4>
                        <p>El navegador deja de mostrar la ventana de impresión — el driver se encarga de todo en segundo plano.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="drv-card-footer">
            <span class="drv-status"><i class="fas fa-circle"></i> No configurado</span>
            <button class="drv-btn" disabled>Configurar</button>
        </div>
    </div>

</div>

<style>
.drv-container {
    padding: 22px;
    background: #f0f2f5;
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── Header ── */
.drv-header {
    background: linear-gradient(135deg, #2c3e50, #4a6572);
    border-radius: 14px;
    padding: 22px 28px;
    color: #fff;
    box-shadow: 0 4px 18px rgba(44,62,80,.35);
}
.drv-header-left { display: flex; align-items: center; gap: 16px; }
.drv-back {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: rgba(255,255,255,.18);
    border: 1.5px solid rgba(255,255,255,.25);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    text-decoration: none;
    flex-shrink: 0;
    transition: background .15s;
}
.drv-back:hover { background: rgba(255,255,255,.3); }
.drv-header-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.25);
}
.drv-header h1 { margin: 0 0 3px; font-size: 22px; font-weight: 800; }
.drv-header p  { margin: 0; font-size: 13px; opacity: .8; }

/* ── Card ── */
.drv-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
    max-width: 760px;
}
.drv-card-head {
    height: 100px;
    background: linear-gradient(135deg,#2c3e50,#4a6572);
    display: flex;
    align-items: center;
    padding: 0 28px;
    position: relative;
}
.drv-card-icon { font-size: 42px; color: rgba(255,255,255,.95); }
.drv-badge {
    position: absolute;
    top: 16px; right: 20px;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .4px;
    background: rgba(255,255,255,.22);
    color: #fff;
    border: 1px solid rgba(255,255,255,.3);
}
.drv-card-body { padding: 26px 28px; }
.drv-card-body h2 { margin: 0 0 10px; font-size: 17px; font-weight: 800; color: #2c3e50; }
.drv-card-body > p { margin: 0 0 24px; font-size: 13.5px; color: #7f8c8d; line-height: 1.7; }

.drv-features { display: flex; flex-direction: column; gap: 18px; }
.drv-feature { display: flex; gap: 14px; align-items: flex-start; }
.drv-feature-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: #f0f2f5;
    color: #2c3e50;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.drv-feature h4 { margin: 0 0 3px; font-size: 13.5px; font-weight: 700; color: #2c3e50; }
.drv-feature p  { margin: 0; font-size: 12.5px; color: #95a5a6; line-height: 1.5; }

.drv-card-footer {
    padding: 16px 28px;
    border-top: 1.5px solid #f0f2f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.drv-status {
    font-size: 12.5px;
    font-weight: 700;
    color: #95a5a6;
    display: flex;
    align-items: center;
    gap: 6px;
}
.drv-status i { font-size: 8px; }
.drv-btn {
    padding: 9px 22px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    border: none;
    background: #f0f2f5;
    color: #bdc3c7;
    cursor: not-allowed;
}

@media (max-width: 640px) {
    .drv-header { flex-direction: column; align-items: flex-start; }
}
</style>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
