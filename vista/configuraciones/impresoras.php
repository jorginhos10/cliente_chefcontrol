<?php
// vista/configuraciones/impresoras.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Impresoras de cocina — CHEFCONTROL';
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
            <div class="drv-header-icon"><i class="fas fa-print"></i></div>
            <div>
                <h1>Impresoras de cocina</h1>
                <p>Impresión automática de comandas al recibir un pedido</p>
            </div>
        </div>
    </div>

    <div class="drv-card">
        <div class="drv-card-head">
            <i class="fas fa-print drv-card-icon"></i>
            <span class="drv-badge">Próximamente</span>
        </div>
        <div class="drv-card-body">
            <h2>¿Qué son las impresoras de cocina?</h2>
            <p>
                Conecta una o varias impresoras térmicas en cocina para que las comandas se
                impriman automáticamente en cuanto entra un pedido — sin depender de que
                alguien esté pendiente de la pantalla del salón o de la campana de notificaciones.
            </p>

            <div class="drv-features">
                <div class="drv-feature">
                    <div class="drv-feature-icon"><i class="fas fa-print"></i></div>
                    <div>
                        <h4>Impresión automática</h4>
                        <p>Cada nueva orden se imprime sola en cocina, sin necesidad de un clic manual.</p>
                    </div>
                </div>
                <div class="drv-feature">
                    <div class="drv-feature-icon"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <h4>Múltiples estaciones</h4>
                        <p>Asigna impresoras distintas por categoría o estación (ej: bebidas, cocina caliente, postres).</p>
                    </div>
                </div>
                <div class="drv-feature">
                    <div class="drv-feature-icon"><i class="fas fa-ruler-horizontal"></i></div>
                    <div>
                        <h4>Escala de 80mm y 58mm</h4>
                        <p>Usa el mismo "Tamaño de papel" configurado en Facturación para calcular el ancho de la comanda.</p>
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
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border-radius: 14px;
    padding: 22px 28px;
    color: #fff;
    box-shadow: 0 4px 18px rgba(26,26,46,.35);
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
    background: linear-gradient(135deg,#1a1a2e,#16213e);
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
