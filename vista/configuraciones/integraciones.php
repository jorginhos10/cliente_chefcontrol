<?php
// vista/configuraciones/integraciones.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Integraciones — CHEFCONTROL';
$paginaActual = 'configuraciones';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="int-container">

    <!-- Cabecera -->
    <div class="int-header">
        <div class="int-header-left">
            <a href="<?php echo $basePath; ?>/configuraciones" class="int-back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="int-header-icon"><i class="fas fa-plug"></i></div>
            <div>
                <h1>Integraciones</h1>
                <p>Conecta ChefControl con plataformas y servicios externos</p>
            </div>
        </div>
    </div>

    <!-- ── SECCIÓN: NOTIFICACIONES ── -->
    <div class="int-section-label">
        <i class="fas fa-bell"></i> Notificaciones y mensajería
    </div>
    <div class="int-grid">

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#075e54,#128c7e)">
                <i class="fab fa-whatsapp int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>WhatsApp Business</h3>
                <p>Envía confirmaciones de pedido, alertas de estado y notificaciones a clientes directamente por WhatsApp.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Confirmación automática de pedidos</li>
                    <li><i class="fas fa-check-circle"></i> Notificación de domicilios</li>
                    <li><i class="fas fa-check-circle"></i> Mensajes personalizados</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#1877f2,#42a5f5)">
                <i class="fab fa-facebook-messenger int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Facebook Messenger</h3>
                <p>Recibe pedidos y consultas desde tu página de Facebook con respuestas automáticas.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Bot de pedidos</li>
                    <li><i class="fas fa-check-circle"></i> Menú interactivo</li>
                    <li><i class="fas fa-check-circle"></i> Historial de conversaciones</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#2c2d72,#00b0f0)">
                <i class="fab fa-telegram int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Telegram Bot</h3>
                <p>Conecta un bot de Telegram para recibir alertas del sistema y notificar a tu equipo en tiempo real.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Alertas de nuevos pedidos</li>
                    <li><i class="fas fa-check-circle"></i> Reportes diarios automáticos</li>
                    <li><i class="fas fa-check-circle"></i> Comandos de control</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

    </div>

    <!-- ── SECCIÓN: PAGOS ── -->
    <div class="int-section-label">
        <i class="fas fa-credit-card"></i> Pasarelas de pago
    </div>
    <div class="int-grid">

        <!-- Nequi -->
        <div class="int-card">
            <div class="int-card-head int-card-head--nequi">
                <div class="int-brand-logo">
                    <span class="int-brand-letter" style="background:#fff;color:#6c00ad;">N</span>
                    <span class="int-brand-name" style="color:#fff;">Nequi</span>
                </div>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Nequi</h3>
                <p>Recibe pagos por Nequi desde el celular de tus clientes. Ideal para domicilios y ventas rápidas en Colombia.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Pago por QR en mesa o domicilio</li>
                    <li><i class="fas fa-check-circle"></i> Confirmación automática</li>
                    <li><i class="fas fa-check-circle"></i> Solo en pesos colombianos (COP)</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <!-- Bancolombia -->
        <div class="int-card">
            <div class="int-card-head int-card-head--bancolombia">
                <div class="int-brand-logo">
                    <span class="int-brand-letter" style="background:#fff;color:#fdda24;">B</span>
                    <span class="int-brand-name" style="color:#fff;">Bancolombia</span>
                </div>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Bancolombia</h3>
                <p>Integra la pasarela de Bancolombia para aceptar pagos con botón PSE, QR y transferencias desde la app.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Botón de pago PSE</li>
                    <li><i class="fas fa-check-circle"></i> QR Bancolombia a la mano</li>
                    <li><i class="fas fa-check-circle"></i> Conciliación automática COP</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <!-- Daviplata -->
        <div class="int-card">
            <div class="int-card-head int-card-head--daviplata">
                <div class="int-brand-logo">
                    <span class="int-brand-letter" style="background:#fff;color:#e31837;">D</span>
                    <span class="int-brand-name" style="color:#fff;">Daviplata</span>
                </div>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Daviplata</h3>
                <p>Acepta pagos con Daviplata, la billetera digital de Davivienda, muy usada para pagos informales y domicilios.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Pago por número de celular</li>
                    <li><i class="fas fa-check-circle"></i> QR en punto de venta</li>
                    <li><i class="fas fa-check-circle"></i> Sin cuenta bancaria requerida</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

    </div>

    <!-- ── SECCIÓN: DELIVERY ── -->
    <div class="int-section-label">
        <i class="fas fa-motorcycle"></i> Plataformas de delivery
    </div>
    <div class="int-grid">

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#e63e00,#ff6b35)">
                <i class="fas fa-fire int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Rappi</h3>
                <p>Sincroniza tu menú con Rappi y gestiona los pedidos desde un solo panel.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Sincronización de menú</li>
                    <li><i class="fas fa-check-circle"></i> Pedidos en tiempo real</li>
                    <li><i class="fas fa-check-circle"></i> Estado de preparación</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#ff2c55,#ff9500)">
                <i class="fas fa-bag-shopping int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>PedidosYa</h3>
                <p>Integra PedidosYa para gestionar los pedidos online directamente en ChefControl.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Menú en tiempo real</li>
                    <li><i class="fas fa-check-circle"></i> Aceptar / rechazar pedidos</li>
                    <li><i class="fas fa-check-circle"></i> Reportes de ventas</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <!-- DiDi Food -->
        <div class="int-card">
            <div class="int-card-head int-card-head--didifood">
                <div class="int-brand-logo">
                    <span class="int-brand-letter" style="background:#fff;color:#ff6700;">D</span>
                    <span class="int-brand-name" style="color:#fff;">DiDi Food</span>
                </div>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>DiDi Food</h3>
                <p>Conecta con DiDi Food para recibir y gestionar pedidos de la plataforma directamente en ChefControl.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Sincronización de menú</li>
                    <li><i class="fas fa-check-circle"></i> Pedidos en tiempo real</li>
                    <li><i class="fas fa-check-circle"></i> Estado de preparación</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

    </div>

    <!-- ── SECCIÓN: ANALÍTICA ── -->
    <div class="int-section-label">
        <i class="fas fa-chart-line"></i> Analítica y herramientas
    </div>
    <div class="int-grid">

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#e37400,#fbbc04)">
                <i class="fab fa-google int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Google Analytics</h3>
                <p>Analiza el comportamiento de los clientes en tu menú digital con Google Analytics.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Tráfico del menú QR</li>
                    <li><i class="fas fa-check-circle"></i> Productos más vistos</li>
                    <li><i class="fas fa-check-circle"></i> Conversión de pedidos</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#004d60,#0097a7)">
                <i class="fas fa-code int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>API REST propia</h3>
                <p>Accede a los datos de ChefControl desde sistemas externos mediante una API con autenticación por token.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Endpoints de ventas y recetas</li>
                    <li><i class="fas fa-check-circle"></i> Autenticación Bearer</li>
                    <li><i class="fas fa-check-circle"></i> Webhooks de eventos</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

    </div>

    <!-- ── SECCIÓN: IMPRESIONES ── -->
    <div class="int-section-label">
        <i class="fas fa-print"></i> Impresiones
    </div>
    <div class="int-grid">

        <!-- Driver -->
        <div class="int-card">
            <div class="int-card-head" style="background:linear-gradient(135deg,#2c3e50,#4a6572)">
                <i class="fas fa-microchip int-card-icon"></i>
                <span class="int-badge int-badge-pronto">Próximamente</span>
            </div>
            <div class="int-card-body">
                <h3>Driver</h3>
                <p>Conexión con aplicativo externo (driver de impresión) para imprimir tickets y comandas automáticamente, sin diálogos manuales del navegador.</p>
                <ul class="int-features">
                    <li><i class="fas fa-check-circle"></i> Impresión automática de facturas y comandas</li>
                    <li><i class="fas fa-check-circle"></i> Compatible con impresoras térmicas Bluetooth/USB</li>
                    <li><i class="fas fa-check-circle"></i> Sin intervención manual del navegador</li>
                </ul>
            </div>
            <div class="int-card-footer">
                <span class="int-status int-status-inactivo"><i class="fas fa-circle"></i> No configurado</span>
                <button class="int-btn int-btn-disabled" disabled>Configurar</button>
            </div>
        </div>

    </div>

</div>

<style>
.int-container {
    padding: 22px;
    background: #f0f2f5;
    min-height: calc(100vh - 70px);
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── Header ── */
.int-header {
    background: linear-gradient(135deg, #004d60, #0097a7);
    border-radius: 14px;
    padding: 22px 28px;
    color: #fff;
    box-shadow: 0 4px 18px rgba(0,77,96,.35);
}
.int-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.int-back {
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
.int-back:hover { background: rgba(255,255,255,.3); }
.int-header-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.25);
}
.int-header h1 { margin: 0 0 3px; font-size: 22px; font-weight: 800; }
.int-header p  { margin: 0; font-size: 13px; opacity: .8; }

/* ── Section label ── */
.int-section-label {
    font-size: 12.5px;
    font-weight: 800;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: .5px;
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: -8px;
}
.int-section-label i { color: #0097a7; }

/* ── Grid ── */
.int-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 18px;
}

/* ── Card ── */
.int-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .2s, box-shadow .2s;
}
.int-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }

.int-card-head {
    height: 80px;
    display: flex;
    align-items: center;
    padding: 0 20px;
    position: relative;
    justify-content: flex-start;
}
.int-card-icon {
    font-size: 34px;
    color: rgba(255,255,255,.95);
}
.int-badge {
    position: absolute;
    top: 12px; right: 14px;
    font-size: 10px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.int-badge-pronto {
    background: rgba(255,255,255,.22);
    color: #fff;
    border: 1px solid rgba(255,255,255,.3);
}
.int-badge-activo {
    background: #d5f5e3;
    color: #1e8449;
}

.int-card-body {
    padding: 18px 20px;
    flex: 1;
}
.int-card-body h3 {
    margin: 0 0 8px;
    font-size: 15px;
    font-weight: 800;
    color: #2c3e50;
}
.int-card-body p {
    margin: 0 0 12px;
    font-size: 13px;
    color: #7f8c8d;
    line-height: 1.6;
}
.int-features {
    list-style: none;
    padding: 0; margin: 0;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.int-features li {
    font-size: 12.5px;
    color: #636e72;
    display: flex;
    align-items: center;
    gap: 7px;
}
.int-features li i {
    color: #0097a7;
    font-size: 11px;
    flex-shrink: 0;
}

.int-card-footer {
    padding: 14px 20px;
    border-top: 1.5px solid #f0f2f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.int-status {
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
}
.int-status i { font-size: 8px; }
.int-status-activo  { color: #27ae60; }
.int-status-inactivo { color: #95a5a6; }

.int-btn {
    padding: 7px 18px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: .15s;
}
.int-btn-primary {
    background: linear-gradient(135deg, #004d60, #0097a7);
    color: #fff;
}
.int-btn-primary:hover { opacity: .88; transform: translateY(-1px); }
.int-btn-disabled {
    background: #f0f2f5;
    color: #bdc3c7;
    cursor: not-allowed;
}

/* ── Logos de marcas colombianas ── */
.int-card-head--nequi       { background: linear-gradient(135deg, #4a0080, #9b23d4); }
.int-card-head--bancolombia { background: linear-gradient(135deg, #a07800, #fdda24); }
.int-card-head--daviplata   { background: linear-gradient(135deg, #8b0000, #e31837); }
.int-card-head--didifood    { background: linear-gradient(135deg, #c44d00, #ff6700); }

.int-brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
}
.int-brand-letter {
    width: 44px; height: 44px;
    border-radius: 12px;
    font-size: 26px;
    font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 3px 10px rgba(0,0,0,.2);
}
.int-brand-name {
    font-size: 20px;
    font-weight: 900;
    letter-spacing: -.3px;
    text-shadow: 0 1px 4px rgba(0,0,0,.25);
}

@media (max-width: 640px) {
    .int-grid { grid-template-columns: 1fr; }
    .int-header { flex-direction: column; align-items: flex-start; }
}
</style>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
