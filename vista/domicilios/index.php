<?php
// vista/domicilios/index.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Domicilios - CHEFCONTROL';
$paginaActual = 'domicilios';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../complementos/header.php';

$estadoLabel = [
    'pendiente'   => 'En aprobación',
    'preparacion' => 'En preparación',
    'listo'       => 'Esperando domiciliario',
    'en_camino'   => 'En camino',
];
$estadoColor = [
    'pendiente'   => '#e67e22',
    'preparacion' => '#2980b9',
    'listo'       => '#8e44ad',
    'en_camino'   => '#27ae60',
];
?>

<div class="dom-wrap">

    <!-- Encabezado -->
    <div class="dom-header">
        <div class="dom-header-left">
            <h1><i class="fas fa-motorcycle"></i> Domicilios</h1>
            <p>Órdenes activas de todos tus canales de venta</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="dom-btn-config dom-btn-add" id="btnAddDomicilio">
                <i class="fas fa-plus"></i> Añadir domicilio
            </button>
            <a href="<?php echo $basePath; ?>/configuraciones/domicilios" class="dom-btn-config">
                <i class="fas fa-link"></i> Configurar links
            </a>
        </div>
    </div>

    <!-- Pedidos activos -->
    <div class="dom-section-title" style="margin-top:8px">
        <i class="fas fa-list-check"></i> Órdenes activas
        <span class="dom-orders-count" id="ordersCount"><?php echo count($pedidos); ?></span>
        <button class="dom-refresh-btn" onclick="refreshPedidos()" title="Actualizar">
            <i class="fas fa-rotate"></i>
        </button>
    </div>

    <div class="dom-orders" id="domOrders">
        <?php foreach ($pedidos as $p): ?>
        <?php renderPedidoCard($p, $estadoLabel, $estadoColor, $basePath); ?>
        <?php endforeach; ?>
        <?php if (empty($pedidos)): ?>
        <div class="dom-orders-empty" id="ordersEmpty">
            <i class="fas fa-check-circle"></i>
            <p>Sin órdenes activas</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal: Añadir domicilio (pedido tomado por el propio negocio) -->
<div class="dom-modal-bg" id="modalAddDomicilio">
    <div class="dom-modal dom-modal-lg">
        <div class="dom-modal-head">
            <h3><i class="fas fa-motorcycle"></i> Añadir domicilio</h3>
            <button class="dom-modal-close" onclick="cerrarAddDomicilio()"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="dom-modal-body">
            <div class="ad-body">
                <div class="ad-col">
                    <div class="ad-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="adBuscarProd" placeholder="Buscar plato…">
                    </div>
                    <div class="ad-productos-list" id="adProductosList"></div>
                </div>
                <div class="ad-col">
                    <div class="ad-cart" id="adCart">
                        <div class="ad-cart-empty">Agrega platos del catálogo</div>
                    </div>
                    <div class="ad-cart-total">
                        <span>Total pedido</span>
                        <strong id="adCartTotal">$0</strong>
                    </div>

                    <div class="ad-field">
                        <label>Tipo</label>
                        <div class="ad-tipo-toggle">
                            <button type="button" class="ad-tipo-btn active" data-tipo="domicilio" onclick="adSetTipo('domicilio')">
                                <i class="fas fa-motorcycle"></i> Domicilio
                            </button>
                            <button type="button" class="ad-tipo-btn" data-tipo="recoger" onclick="adSetTipo('recoger')">
                                <i class="fas fa-store"></i> Recoger
                            </button>
                        </div>
                    </div>
                    <div class="ad-field">
                        <label>Cliente *</label>
                        <input type="text" id="adNombre" placeholder="Nombre del cliente" maxlength="100">
                    </div>
                    <div class="ad-field">
                        <label>Teléfono</label>
                        <input type="text" id="adTelefono" placeholder="Teléfono de contacto" maxlength="30">
                    </div>
                    <div class="ad-field" id="adDirWrap">
                        <label>Dirección *</label>
                        <input type="text" id="adDireccion" placeholder="Dirección de entrega" maxlength="255">
                    </div>
                    <div class="ad-field" id="adBarrioWrap">
                        <label>Barrio</label>
                        <input type="text" id="adBarrio" placeholder="Barrio" maxlength="100">
                    </div>
                    <div class="ad-field" id="adValorDomWrap">
                        <label>Valor del domicilio *</label>
                        <input type="number" id="adValorDomicilio" min="0" step="100" placeholder="0">
                    </div>
                    <div class="ad-field">
                        <label>Notas</label>
                        <textarea id="adNotas" class="ad-notas" placeholder="Ej: Sin cebolla, timbre no funciona…" maxlength="255" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="dom-modal-foot">
            <button class="dom-btn-cancel" onclick="cerrarAddDomicilio()">Cancelar</button>
            <button class="dom-btn-save" id="btnCrearInterno" onclick="crearPedidoInterno()">
                <i class="fas fa-check"></i> Crear pedido
            </button>
        </div>
    </div>
</div>

<!-- Panel de chat admin -->
<div class="dom-chat-overlay" id="domChatOverlay" onclick="cerrarChatAdmin()"></div>
<div class="dom-chat-panel" id="domChatPanel">
    <div class="dom-chat-head">
        <div>
            <div style="font-size:12px;color:#95a5a6;font-weight:600">Chat con cliente</div>
            <div style="font-size:15px;font-weight:800;color:#2c3e50" id="domChatNombre">—</div>
        </div>
        <button class="dom-chat-close" onclick="cerrarChatAdmin()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="dom-chat-msgs" id="domChatMsgs">
        <div class="dom-chat-empty">Sin mensajes aún.</div>
    </div>
    <div class="dom-chat-input-wrap">
        <input type="text" class="dom-chat-input" id="domChatInput"
               placeholder="Escribe tu respuesta…"
               onkeydown="if(event.key==='Enter')enviarMensajeAdmin()">
        <button class="dom-chat-send" onclick="enviarMensajeAdmin()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
.dom-wrap { padding:28px; background:#f0f2f5; min-height:calc(100vh - 70px); display:flex; flex-direction:column; gap:20px; }

/* Header */
.dom-header { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.dom-header h1 { font-size:24px; font-weight:800; color:#2c3e50; margin:0; display:flex; align-items:center; gap:10px; }
.dom-header p  { font-size:13px; color:#95a5a6; margin:4px 0 0; }
.dom-btn-config { background:#fff; color:#2c3e50; border:2px solid #e8ecf0; border-radius:10px; padding:9px 18px; font-size:13px; font-weight:700; text-decoration:none; display:flex; align-items:center; gap:7px; transition:.15s; cursor:pointer; }
.dom-btn-config:hover { border-color:#2c3e50; background:#f8f9fa; }
.dom-btn-add { background:#2c3e50; color:#fff; border-color:#2c3e50; }
.dom-btn-add:hover { background:#1a252f; border-color:#1a252f; }

/* Modal: Añadir domicilio (pedido interno) */
.dom-modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9200; align-items:center; justify-content:center; padding:20px; }
.dom-modal-bg.show { display:flex; }
.dom-modal { background:#fff; border-radius:16px; width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.2); max-height:90vh; display:flex; flex-direction:column; }
.dom-modal-lg { max-width:900px; max-height:96vh; }
.dom-modal-head { display:flex; justify-content:space-between; align-items:center; padding:18px 20px; border-bottom:1px solid #e8ecf0; flex-shrink:0; }
.dom-modal-head h3 { margin:0; font-size:16px; font-weight:800; color:#2c3e50; display:flex; align-items:center; gap:8px; }
.dom-modal-close { background:none; border:none; cursor:pointer; font-size:18px; color:#95a5a6; }
.dom-modal-body { padding:20px; overflow-y:auto; }
.dom-modal-foot { display:flex; justify-content:flex-end; gap:10px; padding:16px 20px; border-top:1px solid #e8ecf0; flex-shrink:0; }
.dom-btn-cancel { background:#f0f2f5; color:#636e72; border:none; border-radius:8px; padding:10px 18px; font-size:14px; font-weight:700; cursor:pointer; }
.dom-btn-save   { background:#2c3e50; color:#fff; border:none; border-radius:8px; padding:10px 18px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:7px; }
.dom-btn-save:hover:not(:disabled) { background:#1a252f; }
.dom-btn-save:disabled { opacity:.6; cursor:default; }

.ad-body { display:flex; gap:20px; flex-wrap:wrap; }
.ad-col { flex:1; min-width:260px; display:flex; flex-direction:column; gap:10px; }
.ad-search-wrap { position:relative; }
.ad-search-wrap i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#b2bec3; font-size:13px; }
.ad-search-wrap input { width:100%; box-sizing:border-box; border:1.5px solid #e8ecf0; border-radius:8px; padding:9px 12px 9px 34px; font-size:13px; outline:none; }
.ad-search-wrap input:focus { border-color:#2c3e50; }
.ad-productos-list { border:1px solid #e8ecf0; border-radius:10px; max-height:420px; overflow-y:auto; }
.ad-prod-row { display:flex; justify-content:space-between; align-items:center; gap:8px; padding:9px 12px; font-size:13px; color:#2c3e50; cursor:pointer; border-bottom:1px solid #f0f2f5; transition:.1s; }
.ad-prod-row:last-child { border-bottom:none; }
.ad-prod-row:hover { background:#f8f9fa; }
.ad-prod-nombre { flex:1; }
.ad-prod-precio { font-weight:700; color:#27ae60; white-space:nowrap; }
.ad-empty { padding:24px; text-align:center; color:#b2bec3; font-size:13px; }

.ad-cart { border:1px solid #e8ecf0; border-radius:10px; min-height:70px; max-height:200px; overflow-y:auto; }
.ad-cart-empty { padding:20px; text-align:center; color:#b2bec3; font-size:12px; }
.ad-cart-row { display:flex; align-items:center; gap:8px; padding:8px 10px; font-size:12px; border-bottom:1px solid #f0f2f5; }
.ad-cart-row:last-child { border-bottom:none; }
.ad-cart-nombre { flex:1; color:#2c3e50; font-weight:600; }
.ad-cart-qty { display:flex; align-items:center; gap:6px; }
.ad-cart-qty button { width:20px; height:20px; border:1px solid #e8ecf0; background:#fff; border-radius:5px; cursor:pointer; font-weight:700; color:#2c3e50; line-height:1; }
.ad-cart-qty button:hover { background:#f0f2f5; }
.ad-cart-sub { font-weight:700; color:#2c3e50; min-width:64px; text-align:right; }
.ad-cart-total { display:flex; justify-content:space-between; font-size:13px; font-weight:800; color:#2c3e50; padding:2px 2px; }

.ad-field { display:flex; flex-direction:column; gap:5px; }
.ad-field label { font-size:12px; font-weight:700; color:#636e72; }
.ad-field input { border:1.5px solid #e8ecf0; border-radius:8px; padding:9px 12px; font-size:13px; outline:none; box-sizing:border-box; }
.ad-field input:focus { border-color:#2c3e50; }
.ad-notas { border:1.5px solid #e8ecf0; border-radius:8px; padding:9px 12px; font-size:13px; outline:none; box-sizing:border-box; width:100%; resize:vertical; font-family:inherit; min-height:64px; }
.ad-notas:focus { border-color:#2c3e50; }
.ad-tipo-toggle { display:flex; gap:8px; }
.ad-tipo-btn { flex:1; border:1.5px solid #e8ecf0; background:#fff; color:#7f8c8d; border-radius:8px; padding:8px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:.15s; }
.ad-tipo-btn.active { border-color:#2c3e50; color:#2c3e50; background:#f8f9fa; }

/* Sección títulos */
.dom-section-title { font-size:14px; font-weight:800; color:#2c3e50; display:flex; align-items:center; gap:8px; }
.dom-section-title::before { content:''; display:block; width:4px; height:16px; background:#2c3e50; border-radius:2px; }
.dom-orders-count { background:#2c3e50; color:#fff; font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; }
.dom-refresh-btn { background:none; border:none; cursor:pointer; color:#95a5a6; font-size:14px; transition:.15s; }
.dom-refresh-btn:hover { color:#2c3e50; }

/* Pedidos */
.dom-orders { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:14px; }
.dom-orders-empty { grid-column:1/-1; text-align:center; padding:50px; color:#b2bec3; }
.dom-orders-empty i { font-size:40px; display:block; margin-bottom:12px; }

.dom-order-card { background:#fff; border-radius:14px; border-left:4px solid var(--oc); padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.05); }
.dom-oc-head  { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; gap:10px; }
.dom-oc-num   { font-size:13px; font-weight:800; color:#2c3e50; }
.dom-oc-link  { font-size:11px; color:#95a5a6; }
.dom-oc-badge { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; background:color-mix(in srgb,var(--oc) 15%,transparent); color:var(--oc); white-space:nowrap; }
.dom-oc-client { font-size:14px; font-weight:700; color:#2c3e50; display:flex; align-items:center; justify-content:space-between; gap:6px; }
.dom-oc-client-name { display:flex; align-items:center; gap:6px; min-width:0; }
.dom-oc-print-btn { flex-shrink:0; width:26px; height:26px; border:none; border-radius:50%; background:#f0f2f5; color:#636e72; display:flex; align-items:center; justify-content:center; font-size:11px; cursor:pointer; transition:background .15s,color .15s; }
.dom-oc-print-btn:hover { background:#2c3e50; color:#fff; }
.dom-oc-dir    { font-size:12px; color:#95a5a6; display:flex; align-items:center; gap:5px; margin-top:2px; }
.dom-oc-tel    { font-size:12px; color:#7f8c8d; display:flex; align-items:center; gap:5px; }
.dom-oc-items  { margin:10px 0; border-top:1px solid #f0f2f5; padding-top:10px; display:flex; flex-direction:column; gap:4px; }
.dom-oc-item   { display:flex; justify-content:space-between; font-size:12px; color:#636e72; }
.dom-oc-item span:last-child { font-weight:700; color:#2c3e50; }
.dom-oc-total  { font-size:13px; font-weight:800; color:#2c3e50; display:flex; justify-content:space-between; border-top:1px solid #e8ecf0; padding-top:8px; margin-top:4px; }
.dom-oc-notas  { font-size:12px; color:#e67e22; background:#fef9e7; border-radius:6px; padding:6px 10px; margin-top:6px; }
.dom-oc-time   { font-size:11px; color:#b2bec3; margin-top:6px; }
.dom-oc-btns   { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
.dom-oc-btn    { flex:1; border:none; border-radius:8px; padding:9px 12px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px; transition:.15s; }
.dom-oc-btn:disabled { opacity:.5; cursor:default; }
.dom-btn-aprobar   { background:#eafaf1; color:#27ae60; }
.dom-btn-aprobar:hover:not(:disabled) { background:#27ae60; color:#fff; }
.dom-btn-rechazar  { background:#fdf2f2; color:#e74c3c; }
.dom-btn-rechazar:hover:not(:disabled) { background:#e74c3c; color:#fff; }
.dom-btn-camino    { background:#eaf4fb; color:#2980b9; }
.dom-btn-camino:hover:not(:disabled)   { background:#2980b9; color:#fff; }
.dom-btn-entregado { background:#f5eef8; color:#8e44ad; }
.dom-btn-entregado:hover:not(:disabled){ background:#8e44ad; color:#fff; }

/* Chat panel */
.dom-chat-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.3); z-index:9050; }
.dom-chat-overlay.show { display:block; }
.dom-chat-panel { position:fixed; right:0; top:0; bottom:0; width:360px; max-width:100vw; background:#fff; box-shadow:-4px 0 30px rgba(0,0,0,.15); z-index:9100; display:flex; flex-direction:column; transform:translateX(100%); transition:transform .3s ease; }
.dom-chat-panel.open { transform:translateX(0); }
.dom-chat-head { display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid #e8ecf0; background:#f8f9fa; flex-shrink:0; }
.dom-chat-close { background:none; border:none; cursor:pointer; font-size:18px; color:#95a5a6; width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:8px; }
.dom-chat-close:hover { background:#f0f2f5; color:#2c3e50; }
.dom-chat-msgs { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:10px; }
.dom-chat-empty { text-align:center; color:#b2bec3; font-size:13px; padding:30px; }
.dom-chat-input-wrap { display:flex; gap:8px; padding:12px 16px; border-top:1px solid #e8ecf0; flex-shrink:0; }
.dom-chat-input { flex:1; border:1.5px solid #e8ecf0; border-radius:10px; padding:10px 14px; font-size:14px; outline:none; font-family:inherit; }
.dom-chat-input:focus { border-color:#2980b9; }
.dom-chat-send { background:#2980b9; color:#fff; border:none; border-radius:10px; width:42px; height:42px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.dom-chat-send:hover { background:#2471a3; }
.dom-chat-msg { display:flex; flex-direction:column; }
.dom-chat-msg.admin { align-items:flex-end; }
.dom-chat-msg.cliente { align-items:flex-start; }
.dom-chat-bubble { max-width:82%; padding:9px 13px; border-radius:16px; font-size:13px; line-height:1.4; word-break:break-word; }
.dom-chat-msg.admin .dom-chat-bubble { background:#2980b9; color:#fff; border-bottom-right-radius:4px; }
.dom-chat-msg.cliente .dom-chat-bubble { background:#f0f2f5; color:#2c3e50; border-bottom-left-radius:4px; }
.dom-chat-time { font-size:10px; color:#b2bec3; margin-top:3px; display:flex; align-items:center; gap:3px; }
.dom-chat-receipt { font-size:10px; display:inline-flex; align-items:center; gap:2px; }
.dom-chat-receipt.sent { color:#b2bec3; }
.dom-chat-receipt.read { color:#34b7f1; }
.dom-btn-chat { background:#eaf4fb; color:#2980b9; }
.dom-btn-chat:hover:not(:disabled) { background:#2980b9; color:#fff; }
.dom-chat-nr { display:inline-flex; align-items:center; justify-content:center; background:#e74c3c; color:#fff; border-radius:50%; min-width:18px; height:18px; font-size:11px; font-weight:800; margin-left:4px; padding:0 3px; }
</style>

<script>
const BASE          = '<?php echo $basePath; ?>';
const BASE_URL      = '<?php echo $baseUrl; ?>';
const COMERCIO_NOMBRE = '<?php echo htmlspecialchars($comercio['nombre'] ?? 'CHEFCONTROL', ENT_QUOTES); ?>';
const COMERCIO_TEL    = '<?php echo htmlspecialchars($comercio['telefono'] ?? '', ENT_QUOTES); ?>';
const PAPEL           = <?php echo json_encode($papel); ?>;
const TICKET_W        = <?php echo (int)$papel['charWidth']; ?>;
const TICKET_ANGOSTO  = <?php echo (($comercio['tamano_papel'] ?? '80mm') === '58mm') ? 'true' : 'false'; ?>;
// Si el módulo de Cocina no está habilitado, nadie más puede pasar el pedido
// de 'preparacion' a 'listo' (eso normalmente lo hace el tablero de Cocina) —
// en ese caso el botón de este estado debe permitir el cambio manualmente.
const COCINA_HABILITADA = <?php echo modOk('cocina') ? 'true' : 'false'; ?>;
const RECETAS_INTERNO   = <?php echo json_encode($recetasInterno ?? []); ?>;

let pedidosData = [];

// ── Añadir domicilio (pedido tomado por el propio negocio) ───────────────────
let adCartItems = []; // { id, nombre, precio, cantidad }
let adTipo      = 'domicilio';

document.getElementById('btnAddDomicilio').addEventListener('click', abrirAddDomicilio);

function abrirAddDomicilio() {
    adCartItems = [];
    adTipo = 'domicilio';
    document.getElementById('adNombre').value    = '';
    document.getElementById('adTelefono').value  = '';
    document.getElementById('adDireccion').value = '';
    document.getElementById('adBarrio').value    = '';
    document.getElementById('adNotas').value     = '';
    document.getElementById('adValorDomicilio').value = '';
    document.getElementById('adBuscarProd').value = '';
    document.querySelectorAll('.ad-tipo-btn').forEach(b => b.classList.toggle('active', b.dataset.tipo === 'domicilio'));
    document.getElementById('adDirWrap').style.display = '';
    document.getElementById('adBarrioWrap').style.display = '';
    document.getElementById('adValorDomWrap').style.display = '';
    renderAdProductos('');
    renderAdCart();
    document.getElementById('modalAddDomicilio').classList.add('show');
    setTimeout(() => document.getElementById('adNombre').focus(), 150);
}

function cerrarAddDomicilio() {
    document.getElementById('modalAddDomicilio').classList.remove('show');
}

function adSetTipo(t) {
    adTipo = t;
    document.querySelectorAll('.ad-tipo-btn').forEach(b => b.classList.toggle('active', b.dataset.tipo === t));
    document.getElementById('adDirWrap').style.display      = t === 'domicilio' ? '' : 'none';
    document.getElementById('adBarrioWrap').style.display    = t === 'domicilio' ? '' : 'none';
    document.getElementById('adValorDomWrap').style.display  = t === 'domicilio' ? '' : 'none';
}

function renderAdProductos(filtro) {
    const list = document.getElementById('adProductosList');
    const f = filtro.trim().toLowerCase();
    const items = RECETAS_INTERNO.filter(r => !r.sin_stock && (!f || r.nombre.toLowerCase().includes(f)));
    if (!items.length) {
        list.innerHTML = '<div class="ad-empty">Sin resultados</div>';
        return;
    }
    list.innerHTML = items.map(r => `
        <div class="ad-prod-row" onclick="adAgregarItem(${r.id})">
            <span class="ad-prod-nombre">${esc(r.nombre)}</span>
            <span class="ad-prod-precio">$${Number(r.precio).toLocaleString()}</span>
        </div>
    `).join('');
}

document.getElementById('adBuscarProd').addEventListener('input', function () {
    renderAdProductos(this.value);
});

function adAgregarItem(id) {
    const r = RECETAS_INTERNO.find(x => x.id === id);
    if (!r) return;
    const existente = adCartItems.find(it => it.id === id);
    if (existente) existente.cantidad++;
    else adCartItems.push({ id, nombre: r.nombre, precio: r.precio, cantidad: 1 });
    renderAdCart();
}

function adCambiarCantidad(id, delta) {
    const it = adCartItems.find(x => x.id === id);
    if (!it) return;
    it.cantidad += delta;
    if (it.cantidad <= 0) adCartItems = adCartItems.filter(x => x.id !== id);
    renderAdCart();
}

function renderAdCart() {
    const cart = document.getElementById('adCart');
    if (!adCartItems.length) {
        cart.innerHTML = '<div class="ad-cart-empty">Agrega platos del catálogo</div>';
    } else {
        cart.innerHTML = adCartItems.map(it => `
            <div class="ad-cart-row">
                <span class="ad-cart-nombre">${esc(it.nombre)}</span>
                <div class="ad-cart-qty">
                    <button type="button" onclick="adCambiarCantidad(${it.id},-1)">−</button>
                    <span>${it.cantidad}</span>
                    <button type="button" onclick="adCambiarCantidad(${it.id},1)">+</button>
                </div>
                <span class="ad-cart-sub">$${(it.precio * it.cantidad).toLocaleString()}</span>
            </div>
        `).join('');
    }
    const total = adCartItems.reduce((s, it) => s + it.precio * it.cantidad, 0);
    document.getElementById('adCartTotal').textContent = '$' + total.toLocaleString();
}

async function crearPedidoInterno() {
    const nombre    = document.getElementById('adNombre').value.trim();
    const telefono  = document.getElementById('adTelefono').value.trim();
    const direccion = document.getElementById('adDireccion').value.trim();
    const barrio    = document.getElementById('adBarrio').value.trim();
    const notas     = document.getElementById('adNotas').value.trim();
    const valorDomInput = document.getElementById('adValorDomicilio');
    const valorDom  = parseFloat(valorDomInput.value);

    if (!nombre)    { document.getElementById('adNombre').focus(); return; }
    if (!adCartItems.length) { alert('Agrega al menos un plato'); return; }
    if (adTipo === 'domicilio' && !direccion) { document.getElementById('adDireccion').focus(); return; }
    if (adTipo === 'domicilio' && (isNaN(valorDom) || valorDom < 0)) { valorDomInput.focus(); return; }

    const btn = document.getElementById('btnCrearInterno');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando…';

    try {
        const r = await fetch(BASE + '/domicilios/crear-interno', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre, telefono, direccion, notas, tipo: adTipo,
                barrio: adTipo === 'domicilio' ? barrio : '',
                valor_domicilio: adTipo === 'domicilio' ? valorDom : null,
                items: adCartItems.map(it => ({ receta_id: it.id, nombre: it.nombre, precio: it.precio, cantidad: it.cantidad })),
            }),
        });
        const d = await r.json();
        if (d.success) {
            cerrarAddDomicilio();
            await refreshPedidos();
        } else {
            alert(d.message || 'Error al crear el pedido');
        }
    } catch {
        alert('Error de red');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Crear pedido';
    }
}

document.getElementById('modalAddDomicilio').addEventListener('click', e => {
    if (e.target === e.currentTarget) cerrarAddDomicilio();
});

// ── Cambiar estado del pedido ────────────────────────────────────────────────
async function cambiarEstado(pedido_id, estado, btn, valor_domicilio = null) {
    btn.disabled = true;
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const body = { pedido_id, estado };
        if (valor_domicilio !== null) body.valor_domicilio = valor_domicilio;
        const r = await fetch(BASE + '/domicilios/cambiar-estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        if ((await r.json()).success) await refreshPedidos();
    } catch {}
    btn.disabled = false;
    btn.innerHTML = original;
}

// ── Aprobar domicilio con valor de flete ─────────────────────────────────────
function aprobarDomicilio(pedido_id) {
    const modal   = document.getElementById('modalValorDomicilio');
    const input   = document.getElementById('inputValorDomicilio');
    const btnConf = document.getElementById('btnConfirmarAprobar');

    input.value = '';
    modal.style.display = 'flex';
    input.focus();

    // Confirmar con botón
    btnConf.onclick = async () => {
        const valor = parseFloat(input.value);
        if (isNaN(valor) || valor < 0) {
            input.classList.add('dom-input-error');
            input.focus();
            return;
        }
        input.classList.remove('dom-input-error');
        modal.style.display = 'none';

        // Usar un botón temporal para el spinner
        const tmpBtn = document.createElement('button');
        document.body.appendChild(tmpBtn);
        await cambiarEstado(pedido_id, 'preparacion', tmpBtn, valor);
        tmpBtn.remove();
    };

    // Confirmar con Enter
    input.onkeydown = (e) => {
        if (e.key === 'Enter') btnConf.click();
        if (e.key === 'Escape') cerrarModalValor();
    };
}

function cerrarModalValor() {
    document.getElementById('modalValorDomicilio').style.display = 'none';
}

// ── Polling de pedidos ───────────────────────────────────────────────────────
async function refreshPedidos() {
    try {
        const r = await fetch(BASE + '/domicilios/pedidos-json');
        const d = await r.json();
        if (!d.success) return;
        renderPedidos(d.data);
    } catch {}
}

function renderPedidos(pedidos) {
    pedidosData = pedidos;
    const estadoLabel = {
        pendiente:'En aprobación', preparacion:'En preparación',
        listo:'Esp. domiciliario', en_camino:'En camino'
    };
    const estadoColor = {
        pendiente:'#e67e22', preparacion:'#2980b9', listo:'#8e44ad', en_camino:'#27ae60'
    };

    const container = document.getElementById('domOrders');
    document.getElementById('ordersCount').textContent = pedidos.length;

    if (!pedidos.length) {
        container.innerHTML = '<div class="dom-orders-empty"><i class="fas fa-check-circle"></i><p>Sin órdenes activas</p></div>';
        return;
    }

    container.innerHTML = pedidos.map(p => {
        const color = estadoColor[p.estado] || '#95a5a6';
        const label = estadoLabel[p.estado] || p.estado;
        const itemsHtml = p.items.map(it =>
            `<div class="dom-oc-item">
                <span>${esc(it.nombre)} × ${it.cantidad}</span>
                <span>$${parseFloat(it.precio * it.cantidad).toLocaleString()}</span>
            </div>`
        ).join('');

        const btns = buildBtns(p);
        const chatNrHtml = p.chat_no_leidos > 0
            ? `<span class="dom-chat-nr">${p.chat_no_leidos}</span>` : '';
        const notas = p.notas ? `<div class="dom-oc-notas"><i class="fas fa-note-sticky"></i> ${esc(p.notas)}</div>` : '';
        const mins = Math.round((Date.now() - new Date(p.created_at.replace(' ','T')).getTime()) / 60000);
        const tiempoStr = mins < 60 ? `${mins} min` : `${Math.floor(mins/60)}h ${mins%60}m`;

        const esRecoger  = p.tipo === 'recoger';
        const tipoIcon   = esRecoger ? 'fa-store'      : 'fa-motorcycle';
        const tipoColor  = esRecoger ? '#27ae60'        : '#2980b9';
        const tipoBg     = esRecoger ? '#eafaf1'        : '#eaf4fb';
        const tipoLabel  = esRecoger ? 'Recoger'        : 'Domicilio';
        const dirHtml    = (!esRecoger && p.direccion)
            ? `<div class="dom-oc-dir"><i class="fas fa-location-dot"></i> ${esc(p.direccion)}${p.barrio ? ' · ' + esc(p.barrio) : ''}</div>`
            : '';

        return `<div class="dom-order-card" style="--oc:${color}" data-id="${p.id}">
            <div class="dom-oc-head">
                <div>
                    <div class="dom-oc-num"><i class="fas ${tipoIcon}" style="color:${tipoColor}"></i> DOM-${p.id}</div>
                    <div class="dom-oc-link">${esc(p.link_nombre)}</div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                    <div class="dom-oc-badge">${label}</div>
                    <div style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;background:${tipoBg};color:${tipoColor}">
                        <i class="fas ${tipoIcon}"></i> ${tipoLabel}
                    </div>
                </div>
            </div>
            <div class="dom-oc-client">
                <span class="dom-oc-client-name"><i class="fas fa-user"></i> ${esc(p.nombre_cliente)}</span>
                <button type="button" class="dom-oc-print-btn" onclick="imprimirVoucherDomicilio(${p.id})" title="Imprimir vaucher de cocina">
                    <i class="fas fa-print"></i>
                </button>
            </div>
            ${dirHtml}
            ${p.telefono ? `<div class="dom-oc-tel"><i class="fas fa-phone"></i> ${esc(p.telefono)}</div>` : ''}
            <div class="dom-oc-items">${itemsHtml}</div>
            <div class="dom-oc-total"><span>Total pedido</span><span>$${parseFloat(p.total).toLocaleString()}</span></div>
            ${p.valor_domicilio != null ? `<div class="dom-oc-total" style="color:#2980b9;border-top:none;padding-top:2px"><span><i class="fas fa-motorcycle"></i> Domicilio</span><span>$${parseFloat(p.valor_domicilio).toLocaleString()}</span></div>` : ''}
            ${notas}
            <div class="dom-oc-time"><i class="fas fa-clock"></i> Hace ${tiempoStr}</div>
            <div class="dom-oc-btns">${btns}</div>
            <div class="dom-oc-btns" style="margin-top:4px">
                <button class="dom-oc-btn dom-btn-chat"
                        onclick="abrirChatAdmin('${p.token_pedido}','${esc(p.nombre_cliente)}')">
                    <i class="fas fa-comment-dots"></i> Chat${chatNrHtml}
                </button>
            </div>
        </div>`;
    }).join('');
}

function buildBtns(p) {
    const esRecoger = p.tipo === 'recoger';

    if (p.estado === 'pendiente') {
        // Si el domicilio ya trae valor (cargado desde el popup "Añadir domicilio"
        // al crearlo), no hace falta volver a preguntarlo al aprobar.
        const yaTieneValor = !esRecoger && p.valor_domicilio != null;
        const aprobarBtn = (esRecoger || yaTieneValor)
            ? `<button class="dom-oc-btn dom-btn-aprobar" onclick="cambiarEstado(${p.id},'preparacion',this)">
                   <i class="fas fa-check"></i> Aprobar
               </button>`
            : `<button class="dom-oc-btn dom-btn-aprobar" onclick="aprobarDomicilio(${p.id})">
                   <i class="fas fa-check"></i> Aprobar
               </button>`;
        return aprobarBtn +
               `<button class="dom-oc-btn dom-btn-rechazar" onclick="cambiarEstado(${p.id},'cancelado',this)">
                    <i class="fas fa-xmark"></i> Rechazar
                </button>`;
    }
    if (p.estado === 'preparacion') {
        if (!COCINA_HABILITADA) {
            return `<button class="dom-oc-btn dom-btn-camino" onclick="cambiarEstado(${p.id},'listo',this)">
                        <i class="fas fa-bell"></i> Marcar listo
                    </button>`;
        }
        return `<button class="dom-oc-btn" style="background:#f8f9fa;color:#7f8c8d" disabled>
                    <i class="fas fa-fire"></i> Preparando en cocina…
                </button>`;
    }
    if (p.estado === 'listo') {
        if (esRecoger) {
            return `<button class="dom-oc-btn dom-btn-entregado" onclick="cambiarEstado(${p.id},'entregado',this)">
                        <i class="fas fa-circle-check"></i> Entregado al cliente
                    </button>`;
        }
        return `<button class="dom-oc-btn dom-btn-camino" onclick="cambiarEstado(${p.id},'en_camino',this)">
                    <i class="fas fa-motorcycle"></i> Enviar a domicilio
                </button>`;
    }
    if (p.estado === 'en_camino') {
        return `<button class="dom-oc-btn dom-btn-entregado" onclick="cambiarEstado(${p.id},'entregado',this)">
                    <i class="fas fa-circle-check"></i> Marcar entregado
                </button>`;
    }
    return '';
}

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Imprimir vaucher de cocina de un pedido de domicilio ─────────────────────
// Mismo esquema de ticket en texto plano (centro/fila/wrap, TICKET_W) que usan
// las facturas de ventas/mesa — arriba nombre, dirección y teléfono del
// cliente; al final la observación del pedido (antes del total).
function imprimirVoucherDomicilio(id) {
    const p = pedidosData.find(x => String(x.id) === String(id));
    if (!p) return;

    const w = window.open('', '_blank', 'width=320,height=500,toolbar=0,scrollbars=0,status=0,menubar=0');
    if (!w) { console.warn('Popup bloqueado por el navegador'); return; }

    const W   = TICKET_W;
    const sep = '='.repeat(W);
    const lin = '-'.repeat(W);
    const fmt = n => parseFloat(n || 0).toFixed(2);

    function centro(txt) {
        txt = String(txt);
        const pad = Math.max(0, Math.floor((W - txt.length) / 2));
        return ' '.repeat(pad) + txt;
    }
    function fila(izq, der) {
        izq = String(izq); der = String(der);
        const espacio = Math.max(1, W - izq.length - der.length);
        return izq + ' '.repeat(espacio) + der;
    }
    function wrap(txt, max) {
        const words = String(txt).split(' ');
        const lines = []; let cur = '';
        words.forEach(word => {
            if ((cur + ' ' + word).trim().length <= max) cur = (cur + ' ' + word).trim();
            else { if (cur) lines.push(cur); cur = word; }
        });
        if (cur) lines.push(cur);
        return lines;
    }

    const ahora = p.created_at ? new Date(p.created_at.replace(' ','T')) : new Date();
    const fecha = ahora.toLocaleDateString('es', {day:'2-digit', month:'2-digit', year:'numeric'});
    const hora  = ahora.toLocaleTimeString('es', {hour:'2-digit', minute:'2-digit'});
    const esRecoger = p.tipo === 'recoger';

    let t = '';
    t += sep + '\n';
    t += centro(COMERCIO_NOMBRE || 'CHEFCONTROL') + '\n';
    t += sep + '\n';
    t += centro('COMANDA DOMICILIO') + '\n';
    t += sep + '\n';
    t += 'Orden:   DOM-' + p.id + '\n';
    t += 'Fecha:   ' + fecha + ' ' + hora + '\n';
    t += 'Cliente: ' + p.nombre_cliente + '\n';
    if (!esRecoger && p.direccion) wrap('Dir: ' + p.direccion, W).forEach(l => t += l + '\n');
    if (!esRecoger && p.barrio) wrap('Barrio: ' + p.barrio, W).forEach(l => t += l + '\n');
    if (p.telefono) t += 'Tel:     ' + p.telefono + '\n';
    t += 'Tipo:    ' + (esRecoger ? 'Recoger en local' : 'Domicilio') + '\n';
    t += lin + '\n';

    const items = p.items || [];
    if (TICKET_ANGOSTO) {
        items.forEach((it, i) => {
            if (i > 0) t += lin + '\n';
            wrap(it.nombre, W).forEach(l => t += l + '\n');
            t += 'x' + it.cantidad + '\n';
            t += '$' + fmt(it.precio * it.cantidad) + '\n';
        });
    } else {
        t += fila('PRODUCTO', 'CANT  SUBTOT') + '\n';
        t += lin + '\n';
        items.forEach((it, i) => {
            // Ancho reservado para "xN  $subtotal" calculado según el
            // contenido real de cada fila — un ancho fijo se queda corto con
            // precios/cantidades más largos y desalinea el resto del ticket.
            if (i > 0) t += lin + '\n';
            const sub  = '$' + fmt(it.precio * it.cantidad);
            const cant = 'x' + it.cantidad;
            const der  = cant + '  ' + sub;
            const nomLines = wrap(it.nombre, Math.max(6, W - der.length - 1));
            t += fila(nomLines[0], der) + '\n';
            for (let i2 = 1; i2 < nomLines.length; i2++) t += nomLines[i2] + '\n';
        });
    }

    t += lin + '\n';
    const domicilioVal = p.valor_domicilio != null ? parseFloat(p.valor_domicilio) : 0;
    if (p.valor_domicilio != null) t += fila('Domicilio:', '$' + fmt(domicilioVal)) + '\n';
    t += fila('TOTAL:', '$' + fmt((parseFloat(p.total) || 0) + domicilioVal)) + '\n';

    if (p.notas && p.notas.trim()) {
        t += lin + '\n';
        wrap('Obs: ' + p.notas.trim(), W).forEach(l => t += l + '\n');
    }

    t += sep + '\n';

    const css = `
        *{box-sizing:border-box;margin:0;padding:0;}
        @page{size:${PAPEL.pageSize};margin:${PAPEL.margin};}
        body{width:100%;padding:0 3mm;background:#fff;color:#000;}
        pre{font-family:'Courier New',monospace;font-size:${PAPEL.fontSize};line-height:1.35;
            white-space:pre-wrap;word-break:break-word;margin:0;}
    `;

    w.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><style>${css}</style></head><body><pre>${esc(t)}</pre><script>window.onload=function(){window.focus();window.print();setTimeout(function(){window.close();},500);}<\/script></body></html>`);
    w.document.close();
}

// ── Chat admin ───────────────────────────────────────────────────────────────
let chatTokenActual  = null;
let chatNombreActual = '';
let chatDesdeId      = 0;
let chatAdminInterval = null;
const domMsgMap = new Map(); // msgId → { div, leido, de }

function abrirChatAdmin(token, nombre) {
    chatTokenActual  = token;
    chatNombreActual = nombre;
    chatDesdeId      = 0;
    domMsgMap.clear();
    document.getElementById('domChatNombre').textContent = nombre;
    document.getElementById('domChatMsgs').innerHTML =
        '<div class="dom-chat-empty"><i class="fas fa-spinner fa-spin"></i></div>';
    document.getElementById('domChatPanel').classList.add('open');
    document.getElementById('domChatOverlay').classList.add('show');
    cargarMensajesAdmin(true);
    if (!chatAdminInterval) {
        chatAdminInterval = setInterval(cargarMensajesAdmin, 3000);
    }
    setTimeout(() => document.getElementById('domChatInput').focus(), 350);
}

function cerrarChatAdmin() {
    document.getElementById('domChatPanel').classList.remove('open');
    document.getElementById('domChatOverlay').classList.remove('show');
    clearInterval(chatAdminInterval);
    chatAdminInterval = null;
    chatTokenActual   = null;
    chatDesdeId       = 0;
    domMsgMap.clear();
}

async function cargarMensajesAdmin(inicial = false) {
    if (!chatTokenActual) return;
    try {
        // Si hay mensajes del admin aún sin leer por el cliente, recargar desde 0 para obtener el visto
        const hasPending = !inicial && [...domMsgMap.values()].some(m => m.de === 'admin' && !m.leido);
        const desde = (inicial || hasPending) ? 0 : chatDesdeId;

        const r = await fetch(BASE + '/domicilios/chat-mensajes/' + chatTokenActual + '?desde=' + desde);
        const d = await r.json();
        if (!d.success) return;
        const msgsEl = document.getElementById('domChatMsgs');

        if (inicial) {
            msgsEl.innerHTML = '';
            domMsgMap.clear();
        }

        if (d.data.length) {
            if (msgsEl.querySelector('.dom-chat-empty')) msgsEl.innerHTML = '';
            let needsScroll = inicial;

            d.data.forEach(msg => {
                const msgId  = parseInt(msg.id);
                chatDesdeId  = Math.max(chatDesdeId, msgId);
                const isRead = parseInt(msg.leido) === 1;
                const leidoAt = msg.leido_at
                    ? new Date(msg.leido_at.replace(' ', 'T'))
                        .toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : '';

                if (domMsgMap.has(msgId)) {
                    // Actualizar visto si cambió
                    const entry = domMsgMap.get(msgId);
                    if (entry.de === 'admin' && entry.leido !== isRead) {
                        entry.leido = isRead;
                        const receiptEl = entry.div.querySelector('.dom-chat-receipt');
                        if (receiptEl && isRead) {
                            receiptEl.className = 'dom-chat-receipt read';
                            receiptEl.innerHTML = `<i class="fas fa-check-double"></i>${leidoAt ? ' ' + leidoAt : ''}`;
                        }
                    }
                    return;
                }

                // Mensaje nuevo — crear elemento
                const hora = new Date(msg.created_at.replace(' ', 'T'))
                    .toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                let receipt = '';
                if (msg.de === 'admin') {
                    receipt = isRead
                        ? `<span class="dom-chat-receipt read"><i class="fas fa-check-double"></i>${leidoAt ? ' ' + leidoAt : ''}</span>`
                        : `<span class="dom-chat-receipt sent"><i class="fas fa-check"></i></span>`;
                }

                const div = document.createElement('div');
                div.className = 'dom-chat-msg ' + msg.de;
                div.innerHTML =
                    `<div class="dom-chat-bubble">${esc(msg.mensaje)}</div>` +
                    `<div class="dom-chat-time">${msg.de === 'admin' ? 'Tú' : chatNombreActual} · ${hora}${receipt}</div>`;
                msgsEl.appendChild(div);
                domMsgMap.set(msgId, { div, leido: isRead, de: msg.de });
                needsScroll = true;
            });

            if (needsScroll) msgsEl.scrollTop = msgsEl.scrollHeight;
        } else if (inicial) {
            msgsEl.innerHTML = '<div class="dom-chat-empty">Sin mensajes aún. Escribe algo.</div>';
        }
    } catch {}
}

async function enviarMensajeAdmin() {
    if (!chatTokenActual) return;
    const input   = document.getElementById('domChatInput');
    const mensaje = input.value.trim();
    if (!mensaje) return;
    input.value = '';
    try {
        await fetch(BASE + '/domicilios/chat-enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token_pedido: chatTokenActual, mensaje }),
        });
        await cargarMensajesAdmin();
    } catch {}
}

// Polling automático cada 8 segundos
setInterval(refreshPedidos, 8000);

// Renderizar inicial con datos PHP
renderPedidos(<?php echo json_encode($pedidos); ?>);
</script>

<!-- Modal: Valor del domicilio al aprobar -->
<div id="modalValorDomicilio" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px 28px 22px;width:340px;max-width:90vw;box-shadow:0 12px 40px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <span style="font-size:26px;">🛵</span>
            <div>
                <div style="font-weight:700;font-size:16px;color:#2c3e50;">Valor del domicilio</div>
                <div style="font-size:12px;color:#7f8c8d;margin-top:2px;">Ingresa el costo de envío para este pedido</div>
            </div>
        </div>
        <div style="position:relative;margin-bottom:18px;">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:700;color:#2c3e50;font-size:15px;">$</span>
            <input id="inputValorDomicilio" type="number" min="0" step="100" placeholder="0"
                style="width:100%;border:2px solid #e0e0e0;border-radius:8px;padding:10px 14px 10px 28px;font-size:18px;font-weight:700;color:#2c3e50;outline:none;box-sizing:border-box;transition:border-color .2s;"
                onfocus="this.style.borderColor='#3498db'"
                onblur="this.style.borderColor='#e0e0e0'"
            />
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="cerrarModalValor()"
                style="flex:1;padding:10px;border:2px solid #e0e0e0;border-radius:8px;background:#fff;color:#7f8c8d;font-weight:600;cursor:pointer;font-size:14px;">
                Cancelar
            </button>
            <button id="btnConfirmarAprobar"
                style="flex:2;padding:10px;border:none;border-radius:8px;background:#27ae60;color:#fff;font-weight:700;cursor:pointer;font-size:14px;">
                <i class="fas fa-check"></i> Aprobar pedido
            </button>
        </div>
    </div>
</div>

<style>
.dom-input-error { border-color: #e74c3c !important; animation: dom-shake .25s; }
@keyframes dom-shake {
    0%,100% { transform: translateX(0); }
    25%      { transform: translateX(-6px); }
    75%      { transform: translateX(6px); }
}
</style>

<?php
function renderPedidoCard(array $p, array $estadoLabel, array $estadoColor, string $basePath): void {
    // Rendered by JS — PHP initial render is handled via js renderPedidos()
}
?>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
