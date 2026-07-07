<?php
// vista/cocina/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Cocina - CHEFCONTROL';
$paginaActual = 'cocina';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/cocina.css">';
require_once __DIR__ . '/../complementos/header.php';

// Separar por estado
$pendientes   = array_filter($ordenes ?? [], fn($o) => $o['estado'] === 'abierta');
$enPreparacion = array_filter($ordenes ?? [], fn($o) => $o['estado'] === 'en_preparacion');
$listas       = array_filter($ordenes ?? [], fn($o) => $o['estado'] === 'lista');

$catLabel = [
    'entrada'      => 'Entrada',
    'plato_fuerte' => 'Fuerte',
    'postre'       => 'Postre',
    'bebida'       => 'Bebida',
    'snack'        => 'Snack',
    'otro'         => 'Otro',
];

function timerClass(int $mins): string {
    if ($mins < 10) return 'timer-ok';
    if ($mins < 20) return 'timer-warn';
    return 'timer-crit';
}

function fmtTime(int $mins): string {
    if ($mins < 60) return "{$mins}m";
    $h = floor($mins / 60); $m = $mins % 60;
    return "{$h}h {$m}m";
}
?>

<div class="kds-shell">

    <!-- Barra superior -->
    <div class="kds-bar">
        <div class="kds-bar-left">
            <h1><i class="fas fa-fire"></i> Cocina</h1>
            <span class="kds-clock" id="kdsClock">--:--:--</span>
        </div>

        <div class="kds-counters">
            <div class="kds-counter kdc-pend">
                <span class="num" id="cntPend"><?php echo count($pendientes); ?></span>
                Pendientes
            </div>
            <div class="kds-counter kdc-prep">
                <span class="num" id="cntPrep"><?php echo count($enPreparacion); ?></span>
                En cocina
            </div>
            <div class="kds-counter kdc-list">
                <span class="num" id="cntList"><?php echo count($listas); ?></span>
                Listas
            </div>
        </div>

        <div class="kds-bar-right">
            <span class="kds-refresh-dot" id="refreshDot" title="Conexión en tiempo real"></span>
            <a href="<?php echo $basePath; ?>/ventas/salon" class="kds-btn kds-btn-ghost">
                <i class="fas fa-store"></i> Salón
            </a>
        </div>
    </div>

    <!-- Columnas -->
    <div class="kds-body">

        <!-- ── PENDIENTES ── -->
        <div class="kds-col col-pendiente">
            <div class="kds-col-head">
                <i class="fas fa-clock"></i> Pendientes
                <span class="kds-col-count" id="headPend"><?php echo count($pendientes); ?></span>
            </div>
            <div class="kds-cards" id="colPend">
                <?php if (empty($pendientes)): ?>
                <div class="kds-empty">
                    <i class="fas fa-check-circle"></i>
                    <p>Sin órdenes pendientes</p>
                </div>
                <?php else: ?>
                <?php foreach ($pendientes as $o): ?>
                <?php echo renderCard($o, 'pendiente', $catLabel, $basePath); ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── EN PREPARACIÓN ── -->
        <div class="kds-col col-preparacion">
            <div class="kds-col-head">
                <i class="fas fa-fire"></i> En preparación
                <span class="kds-col-count" id="headPrep"><?php echo count($enPreparacion); ?></span>
            </div>
            <div class="kds-cards" id="colPrep">
                <?php if (empty($enPreparacion)): ?>
                <div class="kds-empty">
                    <i class="fas fa-utensils"></i>
                    <p>Nada en cocina ahora</p>
                </div>
                <?php else: ?>
                <?php foreach ($enPreparacion as $o): ?>
                <?php echo renderCard($o, 'preparacion', $catLabel, $basePath); ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── LISTAS ── -->
        <div class="kds-col col-lista">
            <div class="kds-col-head">
                <i class="fas fa-circle-check"></i> Listas para servir
                <span class="kds-col-count" id="headList"><?php echo count($listas); ?></span>
            </div>
            <div class="kds-cards" id="colList">
                <?php if (empty($listas)): ?>
                <div class="kds-empty">
                    <i class="fas fa-bell"></i>
                    <p>Ninguna orden lista aún</p>
                </div>
                <?php else: ?>
                <?php foreach ($listas as $o): ?>
                <?php echo renderCard($o, 'lista', $catLabel, $basePath); ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /kds-body -->
</div>

<?php
function renderCard(array $o, string $tipo, array $catLabel, string $basePath): string {
    $id           = (int)$o['id'];
    $esDom        = isset($o['source']) && $o['source'] === 'domicilio';
    $esVentaDirect = !$esDom && empty($o['id_mesa']);
    $mesaNum      = $esVentaDirect ? '⚡' : ($o['mesa_numero'] ?? '?');
    $mesaNom      = $esVentaDirect
                    ? 'Venta Directa'
                    : htmlspecialchars($o['mesa_nombre'] ?? 'Mesa ' . ($o['mesa_numero'] ?? '?'));
    $mesaZona     = htmlspecialchars($o['mesa_zona'] ?? '');
    $numero       = htmlspecialchars($o['numero_orden']);
    $mins       = (int)($o['minutos_espera'] ?? 0);
    $timerCls   = timerClass($mins);
    $timerTxt   = fmtTime($mins);
    $notas      = htmlspecialchars($o['notas'] ?? '');
    $items      = $o['items'] ?? [];

    ob_start();
    ?>
    <div class="kds-card" id="kcard-<?php echo $id; ?>" data-id="<?php echo $id; ?>" data-estado="<?php echo htmlspecialchars($o['estado']); ?>">

        <!-- Cabecera -->
        <div class="kc-head <?php echo $esDom ? 'kc-head-dom' : ($esVentaDirect ? 'kc-head-vd' : ''); ?>">
            <div class="kc-mesa">
                <div class="kc-mesa-num"><?php echo $esDom ? '&#x1F6F5;' : $mesaNum; ?></div>
                <div class="kc-info">
                    <span><?php echo $mesaNom; ?><?php if ($esDom): ?> <span class="kc-dom-tag">Domicilio</span><?php endif; ?></span>
                    <span class="kc-orden-num"><?php echo $numero; ?></span>
                    <?php if ($mesaZona): ?><span class="kc-zona" title="<?php echo $mesaZona; ?>"><?php echo mb_strimwidth($mesaZona, 0, 28, '…'); ?></span><?php endif; ?>
                </div>
            </div>
            <div class="kc-timer <?php echo $timerCls; ?>" id="timer-<?php echo $id; ?>" data-mins="<?php echo $mins; ?>">
                <?php echo $timerTxt; ?>
            </div>
        </div>

        <!-- Items -->
        <div class="kc-items">
            <?php foreach ($items as $it): ?>
            <div class="kc-item">
                <span class="kc-item-qty"><?php echo (int)$it['cantidad']; ?>×</span>
                <span><?php echo htmlspecialchars($it['receta_nombre']); ?></span>
                <span class="kc-item-cat"><?php echo $catLabel[$it['categoria']] ?? $it['categoria']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Notas -->
        <?php if ($notas): ?>
        <div class="kc-notas">
            <i class="fas fa-triangle-exclamation"></i> <?php echo $notas; ?>
        </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="kc-actions">
            <?php if ($tipo === 'pendiente'): ?>
                <button class="kc-btn kc-btn-aceptar" onclick="kdsAction(<?php echo $id; ?>, 'aceptar')">
                    <i class="fas fa-<?php echo $esDom ? 'motorcycle' : 'fire'; ?>"></i>
                    <?php echo $esDom ? 'Aprobar pedido' : 'Aceptar'; ?>
                </button>
                <button class="kc-btn kc-btn-cancelar" onclick="kdsAction(<?php echo $id; ?>, 'cancelar')" title="Cancelar">
                    <i class="fas fa-xmark"></i>
                </button>
            <?php elseif ($tipo === 'preparacion'): ?>
                <button class="kc-btn kc-btn-lista" onclick="kdsAction(<?php echo $id; ?>, 'lista')">
                    <i class="fas fa-bell"></i>
                    <?php echo $esDom ? 'Listo para domicilio' : ($esVentaDirect ? 'Listo · Entregado' : 'Lista para servir'); ?>
                </button>
                <button class="kc-btn kc-btn-cancelar" onclick="kdsAction(<?php echo $id; ?>, 'cancelar')" title="Cancelar">
                    <i class="fas fa-xmark"></i>
                </button>
            <?php else: ?>
                <button class="kc-btn" style="background:rgba(39,174,96,0.15);color:#27ae60;border:1px solid rgba(39,174,96,0.3);" disabled>
                    <i class="fas fa-circle-check"></i>
                    <?php echo $esDom ? 'Esperando al domiciliario' : 'Esperando al mesero'; ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<script>
(function () {
    const BASEPATH = <?php echo json_encode($basePath); ?>;
    let   knownIds = new Set([<?php echo implode(',', array_column($ordenes ?? [], 'id')); ?>]);

    /* ── Reloj ── */
    function tick() {
        const now = new Date();
        document.getElementById('kdsClock').textContent =
            String(now.getHours()).padStart(2,'0') + ':' +
            String(now.getMinutes()).padStart(2,'0') + ':' +
            String(now.getSeconds()).padStart(2,'0');
    }
    tick();
    setInterval(tick, 1000);

    /* ── Timers en vivo ── */
    setInterval(() => {
        document.querySelectorAll('.kc-timer[data-mins]').forEach(el => {
            const mins = parseInt(el.dataset.mins) + 1;
            el.dataset.mins = mins;
            el.textContent = mins < 60 ? `${mins}m` : `${Math.floor(mins/60)}h ${mins%60}m`;
            el.className = 'kc-timer ' + (mins < 10 ? 'timer-ok' : mins < 20 ? 'timer-warn' : 'timer-crit');
        });
    }, 60000);

    /* ── Fetch puntual (post-acción) ── */
    async function poll() {
        try {
            const res  = await fetch(BASEPATH + '/cocina/get-ordenes');
            const data = await res.json();
            if (!data.success) return;
            procesarOrdenes(data.data);
        } catch(e) {
            console.error('Poll error:', e);
        }
    }

    function procesarOrdenes(incoming) {
        let hayNuevas = false;
        incoming.forEach(o => {
            if (!knownIds.has(+o.id)) { hayNuevas = true; knownIds.add(+o.id); }
        });
        actualizarColumnas(incoming);
        if (hayNuevas) notificarNueva();
    }

    /* ── Server-Sent Events (tiempo real) ── */
    const dot = document.getElementById('refreshDot');

    function conectarSSE() {
        const es = new EventSource(BASEPATH + '/cocina/stream');

        es.addEventListener('ordenes', function (e) {
            dot.classList.remove('off');
            try {
                procesarOrdenes(JSON.parse(e.data));
            } catch(err) {
                console.error('SSE parse error', err);
            }
        });

        es.onopen = function () {
            dot.classList.remove('off');
            dot.title = 'Conectado — tiempo real';
        };

        es.onerror = function () {
            dot.classList.add('off');
            dot.title = 'Reconectando...';
            // El navegador reconecta automáticamente; si falla varios intentos
            // cerramos y reabrimos manualmente para evitar bucles rápidos.
        };
    }

    conectarSSE();

    /* ── Actualizar columnas en orden de llegada ── */
    function actualizarColumnas(ordenes) {
        const columns = [
            { colId: 'colPend', estado: 'abierta',         cntId: 'cntPend', headId: 'headPend', icon: 'fa-check-circle', msg: 'Sin órdenes pendientes' },
            { colId: 'colPrep', estado: 'en_preparacion',  cntId: 'cntPrep', headId: 'headPrep', icon: 'fa-utensils',     msg: 'Nada en cocina ahora'   },
            { colId: 'colList', estado: 'lista',            cntId: 'cntList', headId: 'headList', icon: 'fa-bell',         msg: 'Ninguna orden lista aún' },
        ];

        columns.forEach(({ colId, estado, cntId, headId, icon, msg }) => {
            const col    = document.getElementById(colId);
            if (!col) return;
            const orders = ordenes.filter(o => o.estado === estado);

            document.getElementById(cntId).textContent  = orders.length;
            document.getElementById(headId).textContent = orders.length;

            if (orders.length === 0) {
                col.innerHTML = `<div class="kds-empty"><i class="fas ${icon}"></i><p>${msg}</p></div>`;
                return;
            }

            // Determinar si el orden actual coincide con el nuevo
            const currentIds = [...col.querySelectorAll('.kds-card')].map(c => +c.dataset.id);
            const newIds     = orders.map(o => +o.id);
            const sameOrder  = currentIds.length === newIds.length && currentIds.every((id, i) => id === newIds[i]);

            if (!sameOrder) {
                // Reconstruir columna en orden de llegada (más antiguo arriba)
                col.innerHTML = orders.map(o => buildCard(o)).join('');
            }
        });
    }

    function buildCard(o) {
        const mins    = parseInt(o.minutos_espera || 0);
        const timerCls = mins < 10 ? 'timer-ok' : mins < 20 ? 'timer-warn' : 'timer-crit';
        const timerTxt = mins < 60 ? `${mins}m` : `${Math.floor(mins/60)}h ${mins%60}m`;
        const estado   = o.estado;
        const isDom    = o.source === 'domicilio';
        const mesaNum  = isDom ? '🛵' : (o.mesa_numero || '?');

        const domTag   = isDom ? ' <span class="kc-dom-tag">Domicilio</span>' : '';
        const zona     = o.mesa_zona ? `<span class="kc-zona" title="${esc(o.mesa_zona)}">${esc(o.mesa_zona.substring(0,28))}${o.mesa_zona.length>28?'…':''}</span>` : '';

        const itemsHtml = (o.items || []).map(it =>
            `<div class="kc-item">
                <span class="kc-item-qty">${it.cantidad}×</span>
                <span>${esc(it.receta_nombre)}</span>
            </div>`
        ).join('');

        const notasHtml = o.notas
            ? `<div class="kc-notas"><i class="fas fa-triangle-exclamation"></i> ${esc(o.notas)}</div>`
            : '';

        let btns = '';
        if (estado === 'abierta') {
            btns = `<button class="kc-btn kc-btn-aceptar" onclick="kdsAction(${o.id},'aceptar')">
                        <i class="fas fa-${isDom ? 'motorcycle' : 'fire'}"></i> ${isDom ? 'Aprobar pedido' : 'Aceptar'}
                    </button>
                    <button class="kc-btn kc-btn-cancelar" onclick="kdsAction(${o.id},'cancelar')"><i class="fas fa-xmark"></i></button>`;
        } else if (estado === 'en_preparacion') {
            btns = `<button class="kc-btn kc-btn-lista" onclick="kdsAction(${o.id},'lista')">
                        <i class="fas fa-bell"></i> ${isDom ? 'Listo para domicilio' : 'Lista para servir'}
                    </button>
                    <button class="kc-btn kc-btn-cancelar" onclick="kdsAction(${o.id},'cancelar')"><i class="fas fa-xmark"></i></button>`;
        } else {
            const listaLabel = isDom ? 'Esperando al domiciliario' : 'Esperando al mesero';
            btns = `<button class="kc-btn" style="background:rgba(39,174,96,0.15);color:#27ae60;border:1px solid rgba(39,174,96,0.3);" disabled>
                        <i class="fas fa-circle-check"></i> ${listaLabel}
                    </button>`;
        }

        const headCls = isDom ? 'kc-head kc-head-dom' : 'kc-head';
        return `<div class="kds-card" id="kcard-${o.id}" data-id="${o.id}" data-estado="${estado}" data-source="${o.source || ''}">
            <div class="${headCls}">
                <div class="kc-mesa">
                    <div class="kc-mesa-num">${mesaNum}</div>
                    <div class="kc-info">
                        <span>${esc(o.mesa_nombre || 'Mesa ' + mesaNum)}${domTag}</span>
                        <span class="kc-orden-num">${esc(o.numero_orden)}</span>
                        ${zona}
                    </div>
                </div>
                <div class="kc-timer ${timerCls}" id="timer-${o.id}" data-mins="${mins}">${timerTxt}</div>
            </div>
            <div class="kc-items">${itemsHtml}</div>
            ${notasHtml}
            <div class="kc-actions">${btns}</div>
        </div>`;
    }

    /* ── Acciones ── */
    window.kdsAction = async function (id, accion) {
        const card = document.getElementById('kcard-' + id);
        if (card) card.style.opacity = '0.5';

        const endpoints = {
            aceptar:  '/cocina/aceptar',
            lista:    '/cocina/marcar-lista',
            cancelar: '/cocina/cancelar',
        };

        try {
            const res  = await fetch(BASEPATH + endpoints[accion], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_venta: id }),
            });
            const data = await res.json();
            if (data.success) {
                await poll(); // refrescar columnas inmediatamente
            } else {
                if (card) card.style.opacity = '1';
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        } catch(e) {
            if (card) card.style.opacity = '1';
            alert('Error de red: ' + e.message);
        }
    };

    /* ── Notificación nueva orden ── */
    function notificarNueva() {
        // Sonido (beep simple con AudioContext)
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.4);
        } catch(e) {}

        // Badge visual
        const badge = document.createElement('div');
        badge.className = 'kds-new-badge';
        badge.innerHTML = '<i class="fas fa-bell"></i> Nueva orden recibida';
        badge.onclick = () => badge.remove();
        document.body.appendChild(badge);
        setTimeout(() => badge.remove(), 4000);
    }

    function esc(s) {
        if (!s) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
