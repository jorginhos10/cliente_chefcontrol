<?php
// vista/chat/index.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Chat - CHEFCONTROL';
$paginaActual = 'chat';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '
<style>
/* ── Override contentWrapper para chat full-height ── */
.contentWrapper { padding: 0 !important; overflow: hidden; background: #e5ddd5 !important; }
.cht-wrap { display: flex; height: calc(100vh - 70px); overflow: hidden; }
.cht-left { width: 320px; flex-shrink: 0; background: #fff; border-right: 1px solid #e8ecf0; display: flex; flex-direction: column; }
.cht-left-head { background: #f8f9fa; border-bottom: 1px solid #e8ecf0; padding: 14px 16px; }
.cht-me { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.cht-me-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e8ecf0; }
.cht-me-name   { font-size: 14px; font-weight: 700; color: #2c3e50; }
.cht-me-status { font-size: 12px; color: #27ae60; display: flex; align-items: center; gap: 5px; }
.cht-dot { width: 7px; height: 7px; background: #27ae60; border-radius: 50%; display: inline-block; }
.cht-search { display: flex; align-items: center; gap: 8px; background: #e8ecf0; border-radius: 20px; padding: 7px 14px; }
.cht-search i { color: #95a5a6; font-size: 13px; }
.cht-search input { border: none; background: none; outline: none; font-size: 13px; color: #2c3e50; width: 100%; }
.cht-scroll-area { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
.cht-section-label { padding: 7px 16px 5px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #b2bec3; background: #f8f9fa; border-bottom: 1px solid #f0f2f5; position: sticky; top: 0; z-index: 1; display: flex; align-items: center; gap: 5px; }
.cht-user-list { flex-shrink: 0; }
.cht-ui-dom-icon { width: 46px; height: 46px; border-radius: 50%; background: #eaf4fb; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.cht-user-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f5f5f5; transition: background .12s; position: relative; }
.cht-user-item:hover   { background: #f8f9fa; }
.cht-user-item.active  { background: #eaf4fb; border-left: 3px solid #2980b9; }
.cht-user-item.hidden  { display: none; }
.cht-ui-avatar { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.cht-ui-body { flex: 1; min-width: 0; }
.cht-ui-top  { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
.cht-ui-name { font-size: 14px; font-weight: 700; color: #2c3e50; }
.cht-ui-time { font-size: 11px; color: #95a5a6; white-space: nowrap; }
.cht-ui-bottom { display: flex; justify-content: space-between; align-items: center; }
.cht-ui-preview { font-size: 12px; color: #7f8c8d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
.cht-ui-badge { background: #27ae60; color: #fff; border-radius: 10px; font-size: 10px; font-weight: 700; padding: 2px 6px; min-width: 18px; text-align: center; }
.cht-right { flex: 1; display: flex; flex-direction: column; background: #e5ddd5; position: relative; overflow: hidden; min-height: 0; }
.cht-placeholder { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; color: #95a5a6; }
.cht-ph-icon { width: 80px; height: 80px; background: rgba(255,255,255,.6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; }
.cht-placeholder h3 { margin: 0; font-size: 20px; color: #7f8c8d; }
.cht-placeholder p  { margin: 0; font-size: 14px; }
.cht-convo { flex: 1; display: none; flex-direction: column; min-height: 0; }
.cht-convo.visible { display: flex; }
.cht-convo-head { background: #fff; padding: 12px 20px; border-bottom: 1px solid #e8ecf0; display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.cht-ch-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
.cht-ch-name { font-size: 15px; font-weight: 700; color: #2c3e50; }
.cht-ch-rol  { font-size: 12px; color: #95a5a6; }
.cht-messages { flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 2px; min-height: 0; }
.cht-msg { display: flex; margin-bottom: 1px; }
.cht-msg.out { justify-content: flex-end; }
.cht-msg.in  { justify-content: flex-start; }
.cht-bubble { max-width: 65%; padding: 8px 12px 6px; border-radius: 12px; font-size: 14px; line-height: 1.45; word-break: break-word; position: relative; }
.cht-msg.out .cht-bubble { background: #2c3e50; color: #fff; border-radius: 16px 16px 4px 16px; }
.cht-msg.in  .cht-bubble { background: #fff; color: #2c3e50; border-radius: 16px 16px 16px 4px; box-shadow: 0 1px 2px rgba(0,0,0,.1); }
.cht-msg-time { font-size: 10px; margin-top: 4px; text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: 3px; }
.cht-msg.out .cht-msg-time { color: rgba(255,255,255,.55); }
.cht-msg.in  .cht-msg-time { color: #95a5a6; }
.cht-check  { font-size: 11px; color: rgba(255,255,255,.5); line-height:1; }
.cht-visto  { font-size: 11px; color: #74b9ff; line-height:1; }
.cht-ch-sub { font-size: 12px; color: #95a5a6; min-height: 16px; }
.cht-typing-anim { color: #27ae60; display: inline-flex; align-items: center; gap: 5px; font-style: italic; }
.cht-typing-dots { display: inline-flex; gap: 3px; align-items: center; }
.cht-typing-dots span { width: 5px; height: 5px; background: #27ae60; border-radius: 50%; display: inline-block; animation: cht-bounce .9s ease-in-out infinite; }
.cht-typing-dots span:nth-child(2) { animation-delay: .18s; }
.cht-typing-dots span:nth-child(3) { animation-delay: .36s; }
@keyframes cht-bounce { 0%,60%,100% { transform:translateY(0); } 30% { transform:translateY(-5px); } }
.cht-date-sep { text-align: center; margin: 10px 0; }
.cht-date-sep span { background: rgba(255,255,255,.75); color: #7f8c8d; font-size: 11px; font-weight: 700; padding: 3px 12px; border-radius: 20px; }
.cht-input-bar { background: #fff; padding: 10px 16px; display: flex; align-items: center; gap: 10px; border-top: 1px solid #e8ecf0; flex-shrink: 0; min-height: 62px; }
.cht-input-bar input { flex: 1; background: #f0f2f5; border: none; border-radius: 22px; padding: 10px 18px; font-size: 14px; color: #2c3e50; outline: none; }
.cht-input-bar input::placeholder { color: #b2bec3; }
.cht-send-btn { width: 42px; height: 42px; background: #27ae60; color: #fff; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; transition: background .15s; }
.cht-send-btn:hover { background: #219a52; }
.cht-clear-btn { margin-left: auto; background: none; border: 1px solid #e74c3c; color: #e74c3c; border-radius: 8px; padding: 6px 13px; cursor: pointer; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all .2s; flex-shrink: 0; }
.cht-clear-btn:hover { background: #fadbd8; }
@media (max-width: 680px) {
    .cht-left { width: 72px; }
    .cht-ui-body, .cht-left-head .cht-me-info, .cht-search input { display: none; }
    .cht-search { justify-content: center; padding: 8px; }
    .cht-user-item { padding: 10px; justify-content: center; }
}
</style>';

$jsExtra = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$idYo       = (int)($_SESSION['usuario_id']  ?? 0);
$nombreYo   = $_SESSION['usuario_nombre']     ?? 'Yo';
$avatarYo   = $_SESSION['usuario_avatar']     ?? 'default.png';

$rolesLabel = [
    'admin'      => 'Administrador',
    'mesero'     => 'Mesero',
    'cocina'     => 'Cocina',
    'inventario' => 'Inventario',
];
?>

<div class="cht-wrap">

    <!-- Panel izquierdo: lista de usuarios -->
    <div class="cht-left">
        <div class="cht-left-head">
            <div class="cht-me">
                <img src="<?php echo $baseUrl; ?>/assets/media/users/<?php echo htmlspecialchars($avatarYo); ?>"
                     onerror="this.src='<?php echo $baseUrl; ?>/assets/media/users/default.png'"
                     class="cht-me-avatar">
                <div class="cht-me-info">
                    <div class="cht-me-name"><?php echo htmlspecialchars($nombreYo); ?></div>
                    <div class="cht-me-status"><span class="cht-dot"></span> En línea</div>
                </div>
            </div>
            <div class="cht-search">
                <i class="fas fa-search"></i>
                <input type="text" id="chtSearch" placeholder="Buscar usuario…" oninput="filtrarUsuarios(this.value)">
            </div>
        </div>

        <div class="cht-scroll-area">
            <!-- Soporte ChefControl -->
            <div class="cht-section-label"><i class="fas fa-headset"></i> Soporte</div>
            <div class="cht-user-item" id="soporteItem" onclick="abrirSoporte()">
                <div style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#4f46e5);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-headset" style="color:#fff;font-size:20px;"></i>
                </div>
                <div class="cht-ui-body">
                    <div class="cht-ui-top">
                        <span class="cht-ui-name">ChefControl</span>
                        <span class="cht-ui-time" id="soporteHora"></span>
                    </div>
                    <div class="cht-ui-bottom">
                        <span class="cht-ui-preview" id="soportePreview">Soporte técnico</span>
                        <span class="cht-ui-badge" id="soporteBadge" style="display:none;"></span>
                    </div>
                </div>
            </div>

            <div class="cht-section-label">Personal</div>
            <div id="chtUserList"></div>
            <div class="cht-section-label"><i class="fas fa-motorcycle"></i> Pedidos activos</div>
            <div id="chtDomList"></div>
        </div>
    </div>

    <!-- Panel derecho: conversación -->
    <div class="cht-right">

        <!-- Placeholder vacío -->
        <div class="cht-placeholder" id="chtPlaceholder">
            <div class="cht-ph-icon"><i class="fas fa-comments"></i></div>
            <h3>CHEFCONTROL Chat</h3>
            <p>Selecciona un usuario para comenzar a chatear</p>
        </div>

        <!-- Conversación activa (oculta al inicio) -->
        <div class="cht-convo" id="chtConvo">
            <div class="cht-convo-head" id="chtConvoHead">
                <!-- Rellena JS -->
            </div>
            <div class="cht-messages" id="chtMessages">
                <!-- Mensajes renderizados por JS -->
            </div>
            <div class="cht-input-bar">
                <input type="text" id="chtInput" placeholder="Escribe un mensaje…" autocomplete="off">
                <button class="cht-send-btn" id="chtSendBtn" onclick="enviarMensaje()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

    </div>
</div>


<script>
const BASE     = '<?php echo $basePath; ?>';
const BASE_URL = '<?php echo $baseUrl; ?>';
const YO_ID    = <?php echo $idYo; ?>;

let convs          = <?php echo json_encode($conversaciones ?? []); ?>;
let chatConId      = null;
let chatConRol     = '';
let ultimoMsgId    = 0;
let sseChat        = null;
let pollingConvs   = null;
let pollingMsgs    = null; // fallback polling cuando SSE falla
let ultimaFechaSep = null;
let enviando       = false;
let typingTimeout  = null;
const msgIdsRend   = new Set(); // evita duplicados SSE + fetch

// Dom chat state
let domChatToken    = null;
let domChatUltimoId = 0;
let domChatInterval = null;
let domChatNombre   = '';

// ── Init ────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    renderConversaciones(convs);

    // Event delegation — evita el bug de comillas en onclick inline
    document.getElementById('chtUserList').addEventListener('click', e => {
        const item = e.target.closest('.cht-user-item');
        if (!item) return;
        abrirChat(
            parseInt(item.dataset.id),
            item.dataset.nombre,
            item.dataset.avatar,
            item.dataset.rol
        );
    });

    document.getElementById('chtDomList').addEventListener('click', e => {
        const item = e.target.closest('[data-dom-token]');
        if (!item) return;
        abrirDomChat(
            item.dataset.domToken,
            item.dataset.domNombre,
            item.dataset.domId,
            item.dataset.domTipo
        );
    });

    document.getElementById('chtInput').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviarMensaje(); }
    });

    document.getElementById('chtInput').addEventListener('input', () => {
        if (!chatConId) return;
        clearTimeout(typingTimeout);
        fetch(BASE + '/chat/escribiendo', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id_destinatario: chatConId })
        }).catch(() => {});
        typingTimeout = setTimeout(() => {}, 3500);
    });

    document.getElementById('chtSendBtn').addEventListener('click', enviarMensaje);

    pollingConvs = setInterval(actualizarConversaciones, 8000);

    // Dom chats
    cargarDomChats();
    setInterval(cargarDomChats, 8000);

    // Auto-abrir conversación desde notificación (?con=id o ?dom=token)
    const params = new URLSearchParams(window.location.search);
    const conId  = params.get('con');
    const domTok = params.get('dom');

    if (conId) {
        const u = convs.find(c => String(c.id) === String(conId));
        if (u) {
            abrirChat(parseInt(u.id), u.nombre, u.avatar || 'default.png', u.rol || '');
        } else {
            // El usuario existe pero no tiene mensajes previos — igual abrirlo
            fetch(BASE + '/chat/conversaciones')
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return;
                    const u2 = d.data.find(c => String(c.id) === String(conId));
                    if (u2) abrirChat(parseInt(u2.id), u2.nombre, u2.avatar || 'default.png', u2.rol || '');
                }).catch(() => {});
        }
    } else if (domTok) {
        // Esperar a que cargarDomChats rellene la lista, luego abrir
        setTimeout(() => {
            const item = document.querySelector(`#chtDomList [data-dom-token="${CSS.escape(domTok)}"]`);
            if (item) {
                abrirDomChat(
                    item.dataset.domToken,
                    item.dataset.domNombre,
                    item.dataset.domId,
                    item.dataset.domTipo
                );
            }
        }, 600);
    }
});

// ── Renderizar lista de usuarios ────────────────────────────────────────────
function renderConversaciones(lista) {
    const container = document.getElementById('chtUserList');
    if (!lista || !lista.length) {
        container.innerHTML = '<div style="padding:24px;text-align:center;color:#b2bec3;font-size:13px">Sin usuarios disponibles</div>';
        return;
    }

    const rolesLabel = { admin:'Administrador', mesero:'Mesero', cocina:'Cocina', inventario:'Inventario' };

    container.innerHTML = lista.map(u => {
        const avatar  = u.avatar || 'default.png';
        const noLeidos = parseInt(u.no_leidos) || 0;
        const tiempo  = u.ultima_fecha ? fmtHora(u.ultima_fecha) : '';
        const preview = u.ultimo_mensaje
            ? u.ultimo_mensaje.substring(0, 40) + (u.ultimo_mensaje.length > 40 ? '…' : '')
            : 'Sin mensajes';
        const badge   = noLeidos > 0 ? `<span class="cht-ui-badge">${noLeidos}</span>` : '';
        const activo  = chatConId == u.id ? 'active' : '';

        // TODOS los datos van en data-* para evitar problemas de escapado en onclick
        return `
        <div class="cht-user-item ${activo}"
             data-id="${u.id}"
             data-nombre="${u.nombre.replace(/"/g,'&quot;')}"
             data-avatar="${avatar}"
             data-rol="${u.rol || ''}">
            <img src="${BASE_URL}/assets/media/users/${avatar}" class="cht-ui-avatar"
                 onerror="this.src='${BASE_URL}/assets/media/users/default.png'">
            <div class="cht-ui-body">
                <div class="cht-ui-top">
                    <span class="cht-ui-name">${u.nombre}</span>
                    <span class="cht-ui-time">${tiempo}</span>
                </div>
                <div class="cht-ui-bottom">
                    <span class="cht-ui-preview" style="color:${noLeidos>0?'#2c3e50':'#7f8c8d'};font-weight:${noLeidos>0?700:400}">${preview}</span>
                    ${badge}
                </div>
            </div>
        </div>`;
    }).join('');
}

function filtrarUsuarios(val) {
    const q = val.toLowerCase();
    document.querySelectorAll('.cht-user-item').forEach(el => {
        el.classList.toggle('hidden', q !== '' && !el.dataset.nombre.toLowerCase().includes(q));
    });
}

async function actualizarConversaciones() {
    try {
        const r = await fetch(BASE + '/chat/conversaciones');
        const d = await r.json();
        if (!d.success) return;
        convs = d.data;
        renderConversaciones(convs);
        const total = convs.reduce((s, u) => s + (parseInt(u.no_leidos) || 0), 0);
        const badge = document.getElementById('chatBadgeSb');
        if (badge) { badge.textContent = total > 99 ? '99+' : total; badge.style.display = total > 0 ? 'inline-flex' : 'none'; }
    } catch {}
}

// ── Abrir conversación ──────────────────────────────────────────────────────
function abrirChat(id, nombre, avatar, rol) {
    // Clear dom chat if active
    if (domChatToken) {
        clearInterval(domChatInterval); domChatInterval = null;
        domChatToken = null; domChatUltimoId = 0;
        document.querySelectorAll('#chtDomList .cht-user-item').forEach(el => el.classList.remove('active'));
    }

    if (chatConId === id) return;

    document.querySelectorAll('#chtUserList .cht-user-item').forEach(el => el.classList.remove('active'));
    const item = document.querySelector(`.cht-user-item[data-id="${id}"]`);
    if (item) item.classList.add('active');

    chatConId      = id;
    chatConRol     = { admin:'Administrador', mesero:'Mesero', cocina:'Cocina', inventario:'Inventario' }[rol] || rol;
    ultimoMsgId    = 0;
    ultimaFechaSep = null;
    msgIdsRend.clear();
    clearInterval(pollingMsgs); pollingMsgs = null;

    // Ocultar placeholder, mostrar conversación
    document.getElementById('chtPlaceholder').style.display = 'none';
    document.getElementById('chtConvo').classList.add('visible');

    // Header de la conversación
    document.getElementById('chtConvoHead').innerHTML = `
        <img src="${BASE_URL}/assets/media/users/${avatar}" class="cht-ch-avatar"
             onerror="this.src='${BASE_URL}/assets/media/users/default.png'">
        <div>
            <div class="cht-ch-name">${nombre}</div>
            <div class="cht-ch-sub" id="chtConvoSub">${chatConRol}</div>
        </div>
        <button class="cht-clear-btn" onclick="limpiarChat()">
            <i class="fas fa-broom"></i> Limpiar chat
        </button>`;

    // Spinner mientras carga
    const msgs = document.getElementById('chtMessages');
    msgs.innerHTML = '<div style="text-align:center;padding:30px;color:#95a5a6"><i class="fas fa-spinner fa-spin"></i></div>';

    // Cerrar SSE anterior si existe
    if (sseChat) { sseChat.close(); sseChat = null; }

    // Carga inicial por HTTP, luego abre SSE desde el último ID
    cargarMensajes(true).then(() => iniciarStream(id));

    document.getElementById('chtInput').focus();
}

// ── SSE: stream en tiempo real ──────────────────────────────────────────────
function iniciarStream(id) {
    if (sseChat) { sseChat.close(); sseChat = null; }

    const sse = new EventSource(`${BASE}/chat/stream/${id}?desde=${ultimoMsgId}`);

    sse.addEventListener('mensajes', e => {
        const msgs = JSON.parse(e.data);
        if (!msgs.length) return;

        const container = document.getElementById('chtMessages');
        const yaAbajo = container.scrollHeight - container.scrollTop - container.clientHeight < 100;

        const clearTime = getClearTime(chatConId);
        msgs.forEach(msg => {
            if (parseInt(msg.id) > ultimoMsgId) ultimoMsgId = parseInt(msg.id);
            if (msgIdsRend.has(parseInt(msg.id))) return; // deduplicar
            msgIdsRend.add(parseInt(msg.id));
            if (clearTime && new Date(msg.fecha_creacion.replace(' ','T')) <= clearTime) return;
            const fechaDia = msg.fecha_creacion.substring(0, 10);
            if (fechaDia !== ultimaFechaSep) {
                ultimaFechaSep = fechaDia;
                const sep = document.createElement('div');
                sep.className = 'cht-date-sep';
                sep.innerHTML = `<span>${fmtFecha(msg.fecha_creacion)}</span>`;
                container.appendChild(sep);
            }
            container.appendChild(burbuja(msg));
        });

        if (yaAbajo) container.scrollTop = container.scrollHeight;
    });

    sse.addEventListener('convs', e => {
        convs = JSON.parse(e.data);
        renderConversaciones(convs);
        const total = convs.reduce((s, u) => s + (parseInt(u.no_leidos) || 0), 0);
        const badge = document.getElementById('chatBadgeSb');
        if (badge) {
            badge.textContent = total > 99 ? '99+' : total;
            badge.style.display = total > 0 ? 'inline-flex' : 'none';
        }
    });

    sse.addEventListener('leidos', e => {
        const d = JSON.parse(e.data);
        if (!d.max_id) return;
        document.querySelectorAll('.cht-msg.out[data-msg-id]').forEach(el => {
            if (parseInt(el.dataset.msgId) <= d.max_id) {
                const check = el.querySelector('.cht-check');
                if (check) {
                    check.className = 'cht-visto';
                    check.innerHTML = '<i class="fas fa-check-double"></i>';
                }
            }
        });
    });

    sse.addEventListener('escribiendo', e => {
        const d   = JSON.parse(e.data);
        const sub = document.getElementById('chtConvoSub');
        if (!sub) return;
        if (d.escribiendo) {
            sub.innerHTML = `<span class="cht-typing-anim">
                <span class="cht-typing-dots"><span></span><span></span><span></span></span>
                Escribiendo...
            </span>`;
        } else {
            sub.textContent = chatConRol;
        }
    });

    let sseErrorCount = 0;
    sse.onerror = () => {
        sseErrorCount++;
        if (sseErrorCount >= 3) {
            sse.close();
            sseChat = null;
            if (!pollingMsgs) {
                pollingMsgs = setInterval(() => cargarMensajes(false), 3000);
            }
        }
    };
    sse.addEventListener('mensajes', () => { sseErrorCount = 0; });
    sse.addEventListener('error', e => {
        try {
            const d = JSON.parse(e.data);
            if (d.msg === 'db_error') { sse.close(); sseChat = null; iniciarStream(chatConId); }
        } catch {}
    });

    sseChat = sse;
}

// ── Cargar / pollear mensajes ───────────────────────────────────────────────
async function cargarMensajes(inicial = false) {
    if (!chatConId) return;
    try {
        const url = `${BASE}/chat/mensajes/${chatConId}?desde=${ultimoMsgId}`;
        const r   = await fetch(url);
        const d   = await r.json();
        if (!d.success || !d.data.length) return;

        const container = document.getElementById('chtMessages');
        if (inicial) { container.innerHTML = ''; ultimaFechaSep = null; }

        const yaAbajo = container.scrollHeight - container.scrollTop - container.clientHeight < 100;

        const clearTime = getClearTime(chatConId);
        d.data.forEach(msg => {
            if (parseInt(msg.id) > ultimoMsgId) ultimoMsgId = parseInt(msg.id);
            if (msgIdsRend.has(parseInt(msg.id))) return; // deduplicar
            msgIdsRend.add(parseInt(msg.id));
            if (clearTime && new Date(msg.fecha_creacion.replace(' ','T')) <= clearTime) return;
            const fechaDia = msg.fecha_creacion.substring(0, 10);
            if (fechaDia !== ultimaFechaSep) {
                ultimaFechaSep = fechaDia;
                const sep = document.createElement('div');
                sep.className = 'cht-date-sep';
                sep.innerHTML = `<span>${fmtFecha(msg.fecha_creacion)}</span>`;
                container.appendChild(sep);
            }
            container.appendChild(burbuja(msg));
        });

        if (inicial || yaAbajo) container.scrollTop = container.scrollHeight;
    } catch(e) { console.error(e); }
}

function burbuja(msg) {
    const soy = parseInt(msg.id_remitente) === YO_ID;
    const el  = document.createElement('div');
    el.className = 'cht-msg ' + (soy ? 'out' : 'in');
    if (soy) el.dataset.msgId = msg.id;

    let checkHtml = '';
    if (soy) {
        const leido = parseInt(msg.leido) === 1;
        checkHtml = leido
            ? '<span class="cht-visto"><i class="fas fa-check-double"></i></span>'
            : '<span class="cht-check"><i class="fas fa-check"></i></span>';
    }

    el.innerHTML = `<div class="cht-bubble">${nl2br(msg.mensaje)}<div class="cht-msg-time">${fmtHoraCorta(msg.fecha_creacion)}${checkHtml}</div></div>`;
    return el;
}

// ── Enviar ──────────────────────────────────────────────────────────────────
async function enviarMensaje() {
    if (enviando) return;
    const input = document.getElementById('chtInput');
    const texto = input.value.trim();
    if (!texto) return;
    if (!chatConId && !domChatToken) return;

    enviando = true;
    input.value = '';
    input.focus();

    try {
        if (domChatToken) {
            await fetch(BASE + '/domicilios/chat-enviar', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ token_pedido: domChatToken, mensaje: texto }),
            });
            await cargarDomMensajes();
        } else {
            const r = await fetch(BASE + '/chat/enviar', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ id_destinatario: chatConId, mensaje: texto }),
            });
            const d = await r.json();
            if (d.success) cargarMensajes(false);
        }
    } catch {}
    finally { enviando = false; }
}

// ── Formato ─────────────────────────────────────────────────────────────────
function fmtHora(str) {
    const d = new Date(str.replace(' ', 'T'));
    const h = new Date();
    if (d.toDateString() === h.toDateString())
        return d.toLocaleTimeString('es', { hour:'2-digit', minute:'2-digit' });
    return d.toLocaleDateString('es', { day:'2-digit', month:'2-digit' });
}
function fmtHoraCorta(str) {
    return new Date(str.replace(' ', 'T')).toLocaleTimeString('es', { hour:'2-digit', minute:'2-digit' });
}
function fmtFecha(str) {
    const d   = new Date(str.replace(' ', 'T'));
    const hoy = new Date();
    const ayer = new Date(); ayer.setDate(hoy.getDate() - 1);
    if (d.toDateString() === hoy.toDateString())  return 'Hoy';
    if (d.toDateString() === ayer.toDateString()) return 'Ayer';
    return d.toLocaleDateString('es', { day:'2-digit', month:'long', year:'numeric' });
}
function nl2br(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\n/g,'<br>');
}
function escChat(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Dom chat (pedidos a domicilio) ──────────────────────────────────────────
async function cargarDomChats() {
    try {
        const r = await fetch(BASE + '/domicilios/chat-listar');
        const d = await r.json();
        if (!d.success) return;
        renderDomChats(d.data);
    } catch {}
}

function renderDomChats(lista) {
    const container = document.getElementById('chtDomList');
    if (!lista.length) {
        container.innerHTML =
            '<div style="padding:14px 16px;font-size:12px;color:#b2bec3">Sin pedidos activos</div>';
        return;
    }
    container.innerHTML = lista.map(p => {
        const noLeidos = parseInt(p.no_leidos) || 0;
        const badge    = noLeidos > 0 ? `<span class="cht-ui-badge">${noLeidos}</span>` : '';
        const preview  = p.ultimo_mensaje
            ? escChat(p.ultimo_mensaje.substring(0, 38) + (p.ultimo_mensaje.length > 38 ? '…' : ''))
            : '<em style="color:#b2bec3">Sin mensajes</em>';
        const hora     = p.ultima_fecha ? fmtHora(p.ultima_fecha) : '';
        const activo   = domChatToken === p.token_pedido ? 'active' : '';
        const icon     = p.tipo === 'recoger' ? '🏪' : '🛵';
        return `
        <div class="cht-user-item ${activo}"
             data-dom-token="${p.token_pedido}"
             data-dom-nombre="${escChat(p.nombre_cliente).replace(/"/g,'&quot;')}"
             data-dom-id="${p.id}"
             data-dom-tipo="${p.tipo}">
            <div class="cht-ui-dom-icon">${icon}</div>
            <div class="cht-ui-body">
                <div class="cht-ui-top">
                    <span class="cht-ui-name">${escChat(p.nombre_cliente)}</span>
                    <span class="cht-ui-time">${hora}</span>
                </div>
                <div class="cht-ui-bottom">
                    <span class="cht-ui-preview"
                          style="color:${noLeidos>0?'#2c3e50':'#7f8c8d'};font-weight:${noLeidos>0?700:400}">
                        ${preview}
                    </span>
                    ${badge}
                </div>
            </div>
        </div>`;
    }).join('');
}

function abrirDomChat(token, nombre, pedidoId, tipo) {
    // Close SSE and user chat
    if (sseChat) { sseChat.close(); sseChat = null; }
    clearInterval(domChatInterval);
    domChatInterval = null;
    chatConId = null;
    document.querySelectorAll('#chtUserList .cht-user-item').forEach(el => el.classList.remove('active'));

    if (domChatToken === token) return;
    domChatToken    = token;
    domChatNombre   = nombre;
    domChatUltimoId = 0;

    // Mark item active
    document.querySelectorAll('#chtDomList .cht-user-item').forEach(el => el.classList.remove('active'));
    const item = document.querySelector(`#chtDomList [data-dom-token="${token}"]`);
    if (item) item.classList.add('active');

    // Show conversation panel
    document.getElementById('chtPlaceholder').style.display = 'none';
    document.getElementById('chtConvo').classList.add('visible');

    const icon = tipo === 'recoger' ? '🏪' : '🛵';
    document.getElementById('chtConvoHead').innerHTML = `
        <div style="width:42px;height:42px;border-radius:50%;background:#eaf4fb;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">${icon}</div>
        <div>
            <div class="cht-ch-name">${escChat(nombre)}</div>
            <div class="cht-ch-rol">DOM-${pedidoId} · Cliente</div>
        </div>`;

    document.getElementById('chtMessages').innerHTML =
        '<div style="text-align:center;padding:30px;color:#95a5a6"><i class="fas fa-spinner fa-spin"></i></div>';

    cargarDomMensajes(true);
    domChatInterval = setInterval(cargarDomMensajes, 3000);
    document.getElementById('chtInput').focus();
}

async function cargarDomMensajes(inicial = false) {
    if (!domChatToken) return;
    try {
        const r = await fetch(
            `${BASE}/domicilios/chat-mensajes/${domChatToken}?desde=${domChatUltimoId}`
        );
        const d = await r.json();
        if (!d.success) return;
        const container = document.getElementById('chtMessages');
        if (inicial) { container.innerHTML = ''; ultimaFechaSep = null; }

        if (!d.data.length) {
            if (inicial) container.innerHTML =
                '<div style="text-align:center;padding:30px;color:#95a5a6;font-size:13px">Sin mensajes aún.<br>Escribe para comenzar.</div>';
            return;
        }
        const yaAbajo = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
        // Remove placeholder if present
        const ph = container.querySelector('div[style*="text-align:center"]');
        if (ph && inicial) ph.remove();
        if (!inicial && container.children.length === 1 && container.children[0].style.textAlign === 'center') {
            container.innerHTML = ''; ultimaFechaSep = null;
        }

        d.data.forEach(msg => {
            const fechaDia = msg.created_at.substring(0, 10);
            if (fechaDia !== ultimaFechaSep) {
                ultimaFechaSep = fechaDia;
                const sep = document.createElement('div');
                sep.className = 'cht-date-sep';
                sep.innerHTML = `<span>${fmtFecha(msg.created_at)}</span>`;
                container.appendChild(sep);
            }
            container.appendChild(burbujaDom(msg));
            if (parseInt(msg.id) > domChatUltimoId) domChatUltimoId = parseInt(msg.id);
        });
        if (inicial || yaAbajo) container.scrollTop = container.scrollHeight;
    } catch {}
}

function burbujaDom(msg) {
    const esAdmin = msg.de === 'admin';
    const el = document.createElement('div');
    el.className = 'cht-msg ' + (esAdmin ? 'out' : 'in');
    const hora = fmtHoraCorta(msg.created_at);
    el.innerHTML = `<div class="cht-bubble">${nl2br(msg.mensaje)}<div class="cht-msg-time">${hora}</div></div>`;
    return el;
}

// ── Limpiar chat (solo para el usuario actual) ───────────────────────────────
function getClearTime(otherId) {
    const val = localStorage.getItem(`chat_cleared_${YO_ID}_${otherId}`);
    return val ? new Date(val) : null;
}
function setClearTime(otherId) {
    localStorage.setItem(`chat_cleared_${YO_ID}_${otherId}`, new Date().toISOString());
}

// ── Soporte ChefControl (sup_chat) ──────────────────────────────────────────
let soporteAbierto  = false;
let soporteUltimoId = 0;
let soporteInterval = null;

async function abrirSoporte() {
    // Cerrar chats activos
    if (sseChat)          { sseChat.close(); sseChat = null; }
    clearInterval(domChatInterval);
    clearInterval(soporteInterval);
    domChatInterval = null;
    chatConId       = null;
    domChatToken    = null;
    soporteAbierto  = true;
    soporteUltimoId = 0;

    document.querySelectorAll('#chtUserList .cht-user-item, #chtDomList .cht-user-item').forEach(el => el.classList.remove('active'));
    document.getElementById('soporteItem').classList.add('active');

    document.getElementById('chtPlaceholder').style.display = 'none';
    const convo = document.getElementById('chtConvo');
    convo.classList.add('visible');

    document.getElementById('chtConvoHead').innerHTML = `
        <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#4f46e5);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-headset" style="color:#fff;font-size:18px;"></i>
        </div>
        <div>
            <div class="cht-ch-name">ChefControl Soporte</div>
            <div class="cht-ch-sub" id="chtConvoSub">Equipo de soporte técnico</div>
        </div>`;

    // Quitar botón limpiar si existe, no aplica para soporte
    const inputBar = document.querySelector('.cht-input-bar');
    const clearBtn = inputBar.querySelector('.cht-clear-btn');
    if (clearBtn) clearBtn.remove();

    document.getElementById('chtMessages').innerHTML = '';
    await cargarMensajesSoporte(true);

    // Polling cada 3s
    soporteInterval = setInterval(() => cargarMensajesSoporte(false), 3000);
}

async function cargarMensajesSoporte(inicial = false) {
    try {
        const r = await fetch(`${BASE}/chat/soporte/mensajes?desde=${soporteUltimoId}`);
        const d = await r.json();
        if (!d.success || !d.data.length) return;

        const container = document.getElementById('chtMessages');
        if (inicial) container.innerHTML = '';

        const yaAbajo = container.scrollHeight - container.scrollTop - container.clientHeight < 100;

        d.data.forEach(msg => {
            if (parseInt(msg.id) <= soporteUltimoId && !inicial) return;
            soporteUltimoId = Math.max(soporteUltimoId, parseInt(msg.id));
            container.appendChild(crearBurbujaSoporte(msg));
        });

        if (inicial || yaAbajo) container.scrollTop = container.scrollHeight;

        // Actualizar preview en sidebar
        const ultimo = d.data[d.data.length - 1];
        const prev = document.getElementById('soportePreview');
        if (prev) prev.textContent = ultimo.mensaje.substring(0, 35) + (ultimo.mensaje.length > 35 ? '…' : '');
        const hora = document.getElementById('soporteHora');
        if (hora) hora.textContent = fmtHora(ultimo.created_at);
    } catch {}
}

function crearBurbujaSoporte(msg) {
    const esYo = msg.emisor === 'restaurante';
    const el   = document.createElement('div');
    el.className = 'cht-msg ' + (esYo ? 'out' : 'in');
    const hora = fmtHoraCorta(msg.created_at);
    el.innerHTML = `<div class="cht-bubble">${nl2br(msg.mensaje)}<div class="cht-msg-time">${hora}</div></div>`;
    return el;
}

// Override enviarMensaje para soporte
const _enviarOriginal = enviarMensaje;
function enviarMensaje() {
    if (soporteAbierto) { enviarMensajeSoporte(); return; }
    _enviarOriginal();
}

async function enviarMensajeSoporte() {
    const input   = document.getElementById('chtInput');
    const mensaje = input.value.trim();
    if (!mensaje || enviando) return;

    enviando = true;
    input.value = '';
    try {
        await fetch(`${BASE}/chat/soporte/enviar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje })
        });
        await cargarMensajesSoporte(false);
    } catch {}
    enviando = false;
}

// Polling no-leídos soporte (integrado en el contador global)
async function contarNoLeidosSoporte() {
    try {
        const r = await fetch(`${BASE}/chat/soporte/no-leidos`);
        const d = await r.json();
        const badge  = document.getElementById('soporteBadge');
        const count  = d.count || 0;
        if (badge) {
            badge.style.display = count > 0 ? 'inline-block' : 'none';
            badge.textContent   = count;
        }
        return count;
    } catch { return 0; }
}

// Incluir en el polling global de no-leídos (cada 10s)
setInterval(contarNoLeidosSoporte, 10000);
contarNoLeidosSoporte();

// Cerrar soporte al abrir otro chat
const _abrirChatOriginal = abrirChat;
function abrirChat(id, nombre, avatar, rol) {
    soporteAbierto = false;
    clearInterval(soporteInterval);
    soporteInterval = null;
    document.getElementById('soporteItem').classList.remove('active');
    _abrirChatOriginal(id, nombre, avatar, rol);
}

function limpiarChat() {
    if (!chatConId) return;
    Swal.fire({
        title: '¿Limpiar chat?',
        text: 'Se ocultarán los mensajes solo para ti. El otro usuario no verá ningún cambio.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    }).then(r => {
        if (!r.isConfirmed) return;
        setClearTime(chatConId);
        const container = document.getElementById('chtMessages');
        container.innerHTML = '<div style="text-align:center;padding:40px;color:#95a5a6;font-size:13px"><i class="fas fa-broom" style="font-size:28px;display:block;margin-bottom:10px;"></i>Chat limpiado. Los nuevos mensajes aparecerán aquí.</div>';
        ultimaFechaSep = null;
    });
}
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
