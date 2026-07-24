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
            <a href="<?php echo $basePath; ?>/configuraciones/integraciones" class="drv-back">
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

            <div class="drv-apikey-section">
                <h3>api_key</h3>
                <p>Usa esta clave para conectar el aplicativo externo con tu cuenta de ChefControl.</p>
                <div class="drv-apikey-box" id="drvApiKeyBox">
                    <?php if ($apiKeyDriver): ?>
                        <code id="drvApiKeyValue"><?php echo htmlspecialchars($apiKeyDriver); ?></code>
                        <button type="button" class="drv-copy-btn" onclick="copiarApiKey()" title="Copiar">
                            <i class="fas fa-copy"></i>
                        </button>
                    <?php else: ?>
                        <span class="drv-apikey-empty">Aún no has generado tu api_key.</span>
                        <button type="button" class="drv-btn-generar" id="btnGenerarApiKey" onclick="generarApiKeyDriver()">
                            <i class="fas fa-key"></i> Generar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="drv-card-footer">
            <span class="drv-status<?php echo $apiKeyDriver ? ' drv-status-on' : ''; ?>">
                <i class="fas fa-circle"></i> <?php echo $apiKeyDriver ? 'Configurado' : 'No configurado'; ?>
            </span>
        </div>
    </div>

</div>

<script>
const BASE_DRV = <?php echo json_encode($basePath); ?>;

function generarApiKeyDriver() {
    const btn = document.getElementById('btnGenerarApiKey');
    if (!btn) return;
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    fetch(BASE_DRV + '/configuraciones/generar-api-key-driver', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (!d.success) {
                btn.disabled = false;
                btn.innerHTML = orig;
                return;
            }
            const box = document.getElementById('drvApiKeyBox');
            box.innerHTML =
                '<code id="drvApiKeyValue">' + d.api_key + '</code>' +
                '<button type="button" class="drv-copy-btn" onclick="copiarApiKey()" title="Copiar"><i class="fas fa-copy"></i></button>';
            const statusEl = document.querySelector('.drv-status');
            if (statusEl) {
                statusEl.classList.add('drv-status-on');
                statusEl.innerHTML = '<i class="fas fa-circle"></i> Configurado';
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = orig;
        });
}

function copiarApiKey() {
    const el = document.getElementById('drvApiKeyValue');
    if (!el) return;
    navigator.clipboard.writeText(el.textContent).then(() => {
        const btn = document.querySelector('.drv-copy-btn');
        if (!btn) return;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 1200);
    });
}
</script>

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

/* ── API key ── */
.drv-apikey-section {
    margin-top: 26px;
    padding-top: 22px;
    border-top: 1.5px solid #f0f2f5;
}
.drv-apikey-section h3 { margin: 0 0 6px; font-size: 14px; font-weight: 800; color: #2c3e50; font-family: 'Courier New', monospace; }
.drv-apikey-section > p { margin: 0 0 14px; font-size: 12.5px; color: #95a5a6; line-height: 1.5; }
.drv-apikey-box {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8f9fa;
    border: 1.5px dashed #dce0e4;
    border-radius: 10px;
    padding: 14px 16px;
    flex-wrap: wrap;
}
.drv-apikey-box code {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: .5px;
    word-break: break-all;
}
.drv-apikey-empty { font-size: 13px; color: #95a5a6; flex: 1; }
.drv-copy-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: none;
    background: #fff;
    color: #7f8c8d;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
    cursor: pointer;
    box-shadow: 0 1px 4px rgba(0,0,0,.1);
    transition: background .15s, color .15s;
}
.drv-copy-btn:hover { background: #2c3e50; color: #fff; }
.drv-btn-generar {
    padding: 9px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    border: none;
    background: linear-gradient(135deg,#2c3e50,#4a6572);
    color: #fff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: opacity .15s;
}
.drv-btn-generar:hover { opacity: .88; }
.drv-btn-generar:disabled { opacity: .6; cursor: default; }

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
.drv-status-on { color: #27ae60; }
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
