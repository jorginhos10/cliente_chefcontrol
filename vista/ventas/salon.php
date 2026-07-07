<?php
// vista/ventas/salon.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Salón - CHEFCONTROL';
$paginaActual = 'salon';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/salon.css">';
require_once __DIR__ . '/../complementos/header.php';

// Stats
$total = count($mesas ?? []);
$disp = $ocup = $res = $ingresos = 0;
foreach ($mesas ?? [] as $m) {
    if ($m['estado'] === 'disponible')    $disp++;
    if ($m['estado'] === 'ocupada')       $ocup++;
    if ($m['estado'] === 'reservada')     $res++;
    $ingresos += (float)($m['orden_total'] ?? 0);
}
?>

<div class="salon-wrap">

    <!-- Barra superior -->
    <div class="salon-topbar">
        <div class="salon-topbar-left">
            <h1><i class="fas fa-store"></i> Salón</h1>
            <p>Selecciona una mesa para gestionar su orden</p>
        </div>
        <div class="salon-topbar-right">
            <span class="salon-rt-dot" id="salonDot" title="Conectando..."></span>
            <button class="s-btn s-btn-outline" onclick="location.reload()">
                <i class="fas fa-rotate-right"></i> Actualizar
            </button>
            <a href="<?php echo $basePath; ?>/ventas" class="s-btn s-btn-outline">
                <i class="fas fa-cash-register"></i> Venta directa
            </a>
        </div>
    </div>

    <!-- Stats chips -->
    <div class="salon-statbar" id="salonStatbar">
        <div class="ss-chip"><i class="fas fa-chair"></i> <?php echo $total; ?> mesas</div>
        <div class="ss-chip c-disp"><i class="fas fa-circle"></i> <?php echo $disp; ?> libres</div>
        <div class="ss-chip c-ocup"><i class="fas fa-circle"></i> <?php echo $ocup; ?> ocupadas</div>
        <?php if ($res): ?>
        <div class="ss-chip c-res"><i class="fas fa-circle"></i> <?php echo $res; ?> reservadas</div>
        <?php endif; ?>
        <?php if ($ingresos > 0): ?>
        <div class="ss-chip c-ing"><i class="fas fa-receipt"></i> $<?php echo number_format($ingresos, 2); ?> en curso</div>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="salon-filters">
        <button class="salon-pill active"  data-f="all">Todas</button>
        <button class="salon-pill p-disp"  data-f="disponible">Libres</button>
        <button class="salon-pill p-ocup"  data-f="ocupada">Ocupadas</button>
        <button class="salon-pill p-res"   data-f="reservada">Reservadas</button>
    </div>

    <!-- Grid de mesas -->
    <div class="salon-grid" id="salonGrid">
        <?php if (empty($mesas)): ?>
        <div class="salon-empty">
            <i class="fas fa-chair"></i>
            <p>No hay mesas configuradas.<br>
               <a href="<?php echo $basePath; ?>/mesas" style="color:#e74c3c;">Ir a configurar mesas</a>
            </p>
        </div>
        <?php else: ?>
        <?php foreach ($mesas as $m):
            $est      = htmlspecialchars($m['estado']);
            $nombre   = htmlspecialchars($m['nombre'] ?? 'Mesa ' . $m['numero']);
            $zona     = htmlspecialchars($m['zona'] ?? '');
            $tieneOrden   = !empty($m['venta_id']);
            $montoOrden   = (float)($m['orden_total'] ?? 0);
            $items        = (int)($m['items_count'] ?? 0);
            $ordenEstado  = $m['orden_estado'] ?? '';

            $classTile  = ['disponible'=>'mt-disp','ocupada'=>'mt-ocup','reservada'=>'mt-res','mantenimiento'=>'mt-mant'][$est] ?? 'mt-mant';
            $labelBadge = ['disponible'=>'Libre','ocupada'=>'Ocupada','reservada'=>'Reservada','mantenimiento'=>'Mantenimiento'][$est] ?? ucfirst($est);

            $ctaTxt = 'Ver mesa';
            if ($est === 'disponible' && !$tieneOrden) $ctaTxt = 'Abrir orden';
            elseif ($tieneOrden) $ctaTxt = 'Ver orden';

            // Badge extra de cocina
            $cocinaBadge = '';
            if ($ordenEstado === 'abierta')        $cocinaBadge = '<span class="mt-cocina-badge cb-pend">⏳ Pendiente</span>';
            elseif ($ordenEstado === 'en_preparacion') $cocinaBadge = '<span class="mt-cocina-badge cb-prep">🍳 En cocina</span>';
            elseif ($ordenEstado === 'lista')       $cocinaBadge = '<span class="mt-cocina-badge cb-list">✅ Lista para servir</span>';

            $tiempoStr = '';
            if ($tieneOrden && !empty($m['orden_inicio'])) {
                $diff = time() - strtotime($m['orden_inicio']);
                $h = floor($diff / 3600); $min = floor(($diff % 3600) / 60);
                $tiempoStr = $h > 0 ? "{$h}h {$min}m" : "{$min}m";
            }
        ?>
        <a href="<?php echo $basePath; ?>/ventas/mesa/<?php echo $m['id']; ?>"
           class="mesa-tile <?php echo $classTile; ?>"
           data-estado="<?php echo $est; ?>"
           data-mesa-id="<?php echo (int)$m['id']; ?>"
           data-orden-estado="<?php echo htmlspecialchars($ordenEstado); ?>">

            <button class="mt-eye" type="button"
                    onclick="verOrdenes(event,<?php echo (int)$m['id']; ?>,<?php echo (int)$m['numero']; ?>)"
                    title="Ver órdenes">
                <i class="fas fa-eye"></i>
            </button>

            <div class="mt-top">
                <span class="mt-badge"><?php echo $labelBadge; ?></span>

                <div class="mt-circle"><?php echo (int)$m['numero']; ?></div>

                <div class="mt-nombre">
                    <?php echo $nombre; ?>
                    <?php if ($zona): ?><br><small style="font-weight:400;color:#7f8c8d"><?php echo $zona; ?></small><?php endif; ?>
                </div>

                <?php if ($tieneOrden): ?>
                <div class="mt-deuda">
                    <div class="mt-deuda-monto">$<?php echo number_format($montoOrden, 2); ?></div>
                    <div class="mt-deuda-meta">
                        <?php echo $items; ?> plato<?php echo $items != 1 ? 's' : ''; ?>
                        <?php if ($tiempoStr): ?> · <?php echo $tiempoStr; ?><?php endif; ?>
                    </div>
                </div>
                <?php echo $cocinaBadge; ?>
                <?php endif; ?>
            </div>

            <div class="mt-footer">
                <span class="mt-cap"><i class="fas fa-users"></i> <?php echo (int)$m['capacidad']; ?></span>
                <span class="mt-cta"><?php echo $ctaTxt; ?> <i class="fas fa-chevron-right"></i></span>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- ── Modal de órdenes ── -->
<div id="mesaPopup" class="mp-overlay">
    <div class="mp-box">
        <button class="mp-close" onclick="cerrarPopup()"><i class="fas fa-xmark"></i></button>
        <div id="mpContent"></div>
    </div>
</div>

<style>
/* ── Botón ojo ── */
.mt-eye {
    position: absolute;
    top: 8px; left: 8px;
    width: 30px; height: 30px;
    border-radius: 50%;
    background: rgba(255,255,255,0.85);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; color: #636e72;
    z-index: 3;
    transition: background .15s, transform .15s;
    backdrop-filter: blur(4px);
}
.mt-eye:hover { background: #fff; color: #2c3e50; transform: scale(1.15); }

/* ── Overlay ── */
.mp-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 9999;
    align-items: center; justify-content: center;
}
.mp-overlay.open { display: flex; }

/* ── Caja ── */
.mp-box {
    background: #fff;
    border-radius: 16px;
    width: 100%; max-width: 420px;
    max-height: 85vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    position: relative;
    padding: 24px 20px 20px;
}
.mp-close {
    position: absolute; top: 12px; right: 14px;
    background: none; border: none; cursor: pointer;
    font-size: 18px; color: #95a5a6;
}
.mp-close:hover { color: #2c3e50; }

/* ── Contenido ── */
.mp-title {
    font-size: 18px; font-weight: 800; color: #2c3e50;
    margin-bottom: 4px;
    display: flex; align-items: center; gap: 10px;
}
.mp-subtitle { font-size: 13px; color: #95a5a6; margin-bottom: 16px; }
.mp-loading  { text-align: center; padding: 30px; color: #95a5a6; font-size: 15px; }
.mp-empty    { text-align: center; padding: 30px 20px; color: #95a5a6; }
.mp-empty i  { font-size: 36px; display: block; margin-bottom: 8px; }

/* Tarjeta de orden */
.mp-orden {
    border: 1px solid #eee; border-radius: 10px;
    margin-bottom: 12px; overflow: hidden;
}
.mp-orden-head {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}
.mp-orden-num  { font-weight: 700; color: #2c3e50; flex: 1; }
.mp-orden-tot  { font-weight: 800; color: #27ae60; }
.mp-st { padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.mp-st.sp { background:#fef9e7; color:#e67e22; }
.mp-st.sk { background:#fef0f0; color:#e74c3c; }
.mp-st.sl { background:#eafaf1; color:#27ae60; }
.mp-items { padding: 8px 14px; }
.mp-item  {
    display: flex; align-items: center; gap: 8px;
    padding: 5px 0; font-size: 13px; color: #2c3e50;
    border-bottom: 1px solid #f0f0f0;
}
.mp-item:last-child { border-bottom: none; }
.mp-item-qty { font-weight: 700; min-width: 24px; color: #7f8c8d; }
.mp-item-nom { flex: 1; }
.mp-item-sub { font-weight: 600; color: #2c3e50; }

/* Total general */
.mp-total {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 14px;
    background: #2c3e50; border-radius: 10px; margin-top: 4px;
    font-weight: 800; color: #fff; font-size: 15px;
}
</style>

<style>
.salon-rt-dot{width:10px;height:10px;border-radius:50%;background:#27ae60;display:inline-block;box-shadow:0 0 0 0 rgba(39,174,96,.4);animation:salonPulse 2s infinite}
.salon-rt-dot.off{background:#e74c3c;animation:none}
@keyframes salonPulse{0%{box-shadow:0 0 0 0 rgba(39,174,96,.4)}70%{box-shadow:0 0 0 7px rgba(39,174,96,0)}100%{box-shadow:0 0 0 0 rgba(39,174,96,0)}}
@keyframes tileFlash{0%{box-shadow:0 0 0 0 rgba(241,196,15,.8)}50%{box-shadow:0 0 14px 4px rgba(241,196,15,.5)}100%{box-shadow:0 2px 10px rgba(0,0,0,.07)}}
.mt-flash{animation:tileFlash .9s ease-out}
@keyframes tileFlashLista{0%,100%{box-shadow:0 2px 10px rgba(0,0,0,.07)}30%,70%{box-shadow:0 0 20px 6px rgba(39,174,96,.65)}}
.mt-flash-lista{animation:tileFlashLista 1.2s ease-out}
</style>

<script>
(function () {
    const BP  = <?php echo json_encode($basePath); ?>;
    const dot = document.getElementById('salonDot');

    /* ── Filtros ── */
    let filtroActivo = 'all';
    document.querySelectorAll('.salon-pill').forEach(p => {
        p.addEventListener('click', function () {
            document.querySelectorAll('.salon-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtroActivo = this.dataset.f;
            aplicarFiltro();
        });
    });
    function aplicarFiltro() {
        document.querySelectorAll('.mesa-tile').forEach(t => {
            t.style.display = (filtroActivo === 'all' || t.dataset.estado === filtroActivo) ? '' : 'none';
        });
    }

    /* ── Estado previo ── */
    const prevState = {};
    document.querySelectorAll('.mesa-tile[data-mesa-id]').forEach(t => {
        prevState[t.dataset.mesaId] = { estado: t.dataset.estado, ordenEstado: t.dataset.ordenEstado || '' };
    });

    /* ── SSE ── */
    function conectar() {
        const es = new EventSource(BP + '/ventas/salon-stream');
        es.addEventListener('mesas', function (ev) {
            dot.classList.remove('off'); dot.title = 'Tiempo real';
            try { procesarMesas(JSON.parse(ev.data)); } catch(e) { console.error(e); }
        });
        es.onopen  = () => { dot.classList.remove('off'); dot.title = 'Conectado'; };
        es.onerror = () => { dot.classList.add('off'); dot.title = 'Reconectando...'; };
    }
    conectar();

    /* ── Procesar datos del SSE ── */
    function procesarMesas(mesas) {
        let hayLista = false, hayNuevaOcupada = false, hayCambio = false;
        mesas.forEach(m => {
            const id   = String(m.id);
            const prev = prevState[id] || { estado:'', ordenEstado:'' };
            const curr = { estado: m.estado, ordenEstado: m.orden_estado || '' };
            const cambio = prev.estado !== curr.estado || prev.ordenEstado !== curr.ordenEstado;
            if (cambio) {
                hayCambio = true;
                if (curr.ordenEstado === 'lista'   && prev.ordenEstado !== 'lista')   hayLista = true;
                if (curr.estado      === 'ocupada' && prev.estado      !== 'ocupada') hayNuevaOcupada = true;
            }
            prevState[id] = curr;
            actualizarTile(m, cambio, hayLista && curr.ordenEstado === 'lista');
        });
        actualizarStatbar(mesas);
        aplicarFiltro();
        if (popupMesaId !== null) cargarPopup();
        if (hayLista)         { sonarListaServir(); vibrar([300,100,300,100,300]); }
        else if (hayNuevaOcupada) { sonarNuevaOrden(); vibrar([200,100,200]); }
        else if (hayCambio)   { sonarCambio(); }
    }

    /* ── Actualizar un tile en el DOM ── */
    function actualizarTile(m, cambio, esLista) {
        const tile = document.querySelector(`.mesa-tile[data-mesa-id="${m.id}"]`);
        if (!tile) return;
        const est    = m.estado;
        const clsMap = { disponible:'mt-disp', ocupada:'mt-ocup', reservada:'mt-res', mantenimiento:'mt-mant' };
        const lblMap = { disponible:'Libre', ocupada:'Ocupada', reservada:'Reservada', mantenimiento:'Mantenimiento' };

        tile.classList.remove('mt-disp','mt-ocup','mt-res','mt-mant');
        tile.classList.add(clsMap[est] || 'mt-mant');
        tile.dataset.estado      = est;
        tile.dataset.ordenEstado = m.orden_estado || '';

        const badge = tile.querySelector('.mt-badge');
        if (badge) badge.textContent = lblMap[est] || est;

        const tieneOrden = !!m.venta_id;
        const mtTop      = tile.querySelector('.mt-top');

        // Deuda
        let deudaEl = tile.querySelector('.mt-deuda');
        if (tieneOrden) {
            const monto = parseFloat(m.orden_total || 0);
            const items = parseInt(m.items_count  || 0);
            let ts = '';
            if (m.orden_inicio) {
                const diff = Math.floor((Date.now() - new Date(m.orden_inicio.replace(' ','T')).getTime()) / 1000);
                const h = Math.floor(diff/3600), min = Math.floor((diff%3600)/60);
                ts = h > 0 ? `${h}h ${min}m` : `${min}m`;
            }
            if (!deudaEl) { deudaEl = document.createElement('div'); deudaEl.className = 'mt-deuda'; mtTop.appendChild(deudaEl); }
            deudaEl.innerHTML = `<div class="mt-deuda-monto">$${fmt(monto)}</div>
                <div class="mt-deuda-meta">${items} plato${items!==1?'s':''}${ts?' · '+ts:''}</div>`;
        } else if (deudaEl) { deudaEl.remove(); }

        // Cocina badge
        const cb = tile.querySelector('.mt-cocina-badge');
        if (cb) cb.remove();
        if (tieneOrden && m.orden_estado) {
            const cMap = { abierta:'<span class="mt-cocina-badge cb-pend">⏳ Pendiente</span>', en_preparacion:'<span class="mt-cocina-badge cb-prep">🍳 En cocina</span>', lista:'<span class="mt-cocina-badge cb-list">✅ Lista para servir</span>' };
            if (cMap[m.orden_estado]) mtTop.insertAdjacentHTML('beforeend', cMap[m.orden_estado]);
        }

        // CTA
        const cta = tile.querySelector('.mt-cta');
        if (cta) {
            const txt = est === 'disponible' && !tieneOrden ? 'Abrir orden' : tieneOrden ? 'Ver orden' : 'Ver mesa';
            cta.innerHTML = `${txt} <i class="fas fa-chevron-right"></i>`;
        }

        // Flash
        if (cambio) {
            tile.classList.remove('mt-flash','mt-flash-lista');
            void tile.offsetWidth;
            tile.classList.add(esLista ? 'mt-flash-lista' : 'mt-flash');
            setTimeout(() => tile.classList.remove('mt-flash','mt-flash-lista'), 1300);
        }
    }

    /* ── Stats bar ── */
    function actualizarStatbar(mesas) {
        let disp=0, ocup=0, res=0, ing=0;
        mesas.forEach(m => {
            if (m.estado==='disponible') disp++;
            if (m.estado==='ocupada')    ocup++;
            if (m.estado==='reservada')  res++;
            ing += parseFloat(m.orden_total||0);
        });
        const sb = document.getElementById('salonStatbar');
        if (!sb) return;
        let h = `<div class="ss-chip"><i class="fas fa-chair"></i> ${mesas.length} mesas</div>
                 <div class="ss-chip c-disp"><i class="fas fa-circle"></i> ${disp} libres</div>
                 <div class="ss-chip c-ocup"><i class="fas fa-circle"></i> ${ocup} ocupadas</div>`;
        if (res > 0) h += `<div class="ss-chip c-res"><i class="fas fa-circle"></i> ${res} reservadas</div>`;
        if (ing > 0) h += `<div class="ss-chip c-ing"><i class="fas fa-receipt"></i> $${fmt(ing)} en curso</div>`;
        sb.innerHTML = h;
    }

    function fmt(n) { return parseFloat(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

    /* ── Sonidos ── */
    function beep(freq, dur, vol) {
        try {
            const ctx=new(window.AudioContext||window.webkitAudioContext)();
            const osc=ctx.createOscillator(), g=ctx.createGain();
            osc.connect(g); g.connect(ctx.destination);
            osc.frequency.value=freq;
            g.gain.setValueAtTime(vol||0.22, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime+dur);
            osc.start(ctx.currentTime); osc.stop(ctx.currentTime+dur);
        } catch(e){}
    }
    function sonarCambio()     { beep(660, 0.18, 0.12); }
    function sonarNuevaOrden() { beep(880,0.18); setTimeout(()=>beep(1100,0.22),210); }
    function sonarListaServir(){ beep(1046,0.14); setTimeout(()=>beep(1318,0.14),175); setTimeout(()=>beep(1568,0.26),350); }
    function vibrar(p)         { if(navigator.vibrate) navigator.vibrate(p); }

    /* ── Popup ── */
    const overlay = document.getElementById('mesaPopup');
    const content = document.getElementById('mpContent');
    let popupMesaId  = null;
    let popupMesaNum = null;

    overlay.addEventListener('click', e => { if (e.target===overlay) cerrarPopup(); });
    window.cerrarPopup = () => {
        overlay.classList.remove('open');
        popupMesaId = popupMesaNum = null;
    };

    window.verOrdenes = function (e, idMesa, numMesa) {
        e.preventDefault(); e.stopPropagation();
        popupMesaId  = idMesa;
        popupMesaNum = numMesa;
        content.innerHTML = '<div class="mp-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
        overlay.classList.add('open');
        cargarPopup();
    };

    async function cargarPopup() {
        if (popupMesaId === null) return;
        try {
            const res  = await fetch(BP + '/ventas/get-orden-mesa/' + popupMesaId);
            const data = await res.json();
            renderPopup(data.data ?? []);
        } catch(err) {
            content.innerHTML = '<div class="mp-empty"><i class="fas fa-triangle-exclamation" style="color:#e74c3c"></i> Error al cargar</div>';
        }
    }

    function renderPopup(ords) {
        const numMesa = popupMesaNum;
        if (!ords || ords.length === 0) {
            content.innerHTML = `<div class="mp-title"><i class="fas fa-chair"></i> Mesa ${numMesa}</div>
                <div class="mp-empty"><i class="fas fa-check-circle" style="color:#27ae60"></i> Sin órdenes activas</div>`;
            return;
        }
        const stLabel = { abierta:'⏳ Pendiente', en_preparacion:'🍳 En cocina', lista:'✅ Lista' };
        const stCls   = { abierta:'sp', en_preparacion:'sk', lista:'sl' };
        let html = `<div class="mp-title"><i class="fas fa-chair"></i> Mesa ${numMesa}</div>
            <div class="mp-subtitle">${ords.length} orden${ords.length!==1?'es':''} activa${ords.length!==1?'s':''}</div>`;
        let totalMesa = 0;
        ords.forEach(o => {
            const tot = parseFloat(o.total||0); totalMesa += tot;
            html += `<div class="mp-orden"><div class="mp-orden-head">
                <span class="mp-orden-num">${esc(o.numero_orden)}</span>
                <span class="mp-st ${stCls[o.estado]??'sp'}">${stLabel[o.estado]??o.estado}</span>
                <span class="mp-orden-tot">$${tot.toFixed(2)}</span></div>
                <div class="mp-items">${(o.items||[]).map(it=>`<div class="mp-item">
                    <span class="mp-item-qty">${it.cantidad}×</span>
                    <span class="mp-item-nom">${esc(it.receta_nombre)}</span>
                    <span class="mp-item-sub">$${parseFloat(it.subtotal).toFixed(2)}</span>
                </div>`).join('')}</div></div>`;
        });
        if (ords.length > 1) html += `<div class="mp-total"><span>Total mesa</span><span>$${totalMesa.toFixed(2)}</span></div>`;
        content.innerHTML = html;
    }

    function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
