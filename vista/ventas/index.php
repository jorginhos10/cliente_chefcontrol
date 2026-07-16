<?php
// vista/ventas/index.php

require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../core/FotoUtil.php';

$titulo       = 'Ventas - CHEFCONTROL';
$paginaActual = 'venta-directa';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/mesa-pos.css?v=' . Config::assetVer('assets/css/mesa-pos.css') . '">
             <link rel="stylesheet" href="' . $baseUrl . '/assets/css/ventas.css?v=' . Config::assetVer('assets/css/ventas.css') . '">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$catConfig = [
    'entrada'      => ['label' => 'Entradas',     'icon' => 'fa-utensils',       'bg' => 'bg-entrada'],
    'plato_fuerte' => ['label' => 'Principales',  'icon' => 'fa-drumstick-bite', 'bg' => 'bg-plato_fuerte'],
    'postre'       => ['label' => 'Postres',       'icon' => 'fa-ice-cream',      'bg' => 'bg-postre'],
    'bebida'       => ['label' => 'Bebidas',       'icon' => 'fa-wine-glass',     'bg' => 'bg-bebida'],
    'snack'        => ['label' => 'Snacks',        'icon' => 'fa-cookie-bite',    'bg' => 'bg-snack'],
    'otro'         => ['label' => 'Otros',         'icon' => 'fa-bowl-food',      'bg' => 'bg-otro'],
];
$catColors = [
    'entrada'      => '#e67e22',
    'plato_fuerte' => '#e74c3c',
    'postre'       => '#9b59b6',
    'bebida'       => '#3498db',
    'snack'        => '#27ae60',
    'otro'         => '#7f8c8d',
];

// Datos completos para JavaScript
$recetasJson = json_encode($recetas ?? []);

require_once __DIR__ . '/../../modelo/comercioModel.php';
$papel = ComercioModel::parametrosPapel($comercio['tamano_papel'] ?? '80mm');
?>

<div class="pos-root">

  <!-- ══ BARRA SUPERIOR ══ -->
  <div class="pos-topbar">
    <div class="pos-topbar-mesa">
      <i class="fas fa-cash-register" style="color:#2c3e50;font-size:18px;"></i>
      <span class="pos-mesa-num">Venta Directa</span>
    </div>
    <div class="pos-topbar-meta">
      <span><i class="fas fa-receipt"></i> <?php echo $estadisticas['ventas_hoy'] ?? 0; ?> ventas hoy</span>
      <span><i class="fas fa-dollar-sign"></i> $<?php echo number_format($estadisticas['ingresos_hoy'] ?? 0, 2); ?> hoy</span>
    </div>
  </div>

  <!-- ══ CUERPO ══ -->
  <div class="pos-main">

    <!-- ── CATÁLOGO ── -->
    <div class="pos-catalog">
      <div class="pos-search-wrap">
        <i class="fas fa-search pos-search-ico"></i>
        <input type="text" id="menuSearch" class="pos-search-input" placeholder="Buscar plato...">
      </div>

      <div class="pos-cats-bar">
        <button class="pos-cat active" data-cat="" style="--cc:#2c3e50">
          <i class="fas fa-th-large"></i> Todo
        </button>
        <?php foreach ($catConfig as $k => $c): ?>
        <button class="pos-cat" data-cat="<?php echo $k; ?>" style="--cc:<?php echo $catColors[$k] ?? '#7f8c8d'; ?>">
          <i class="fas <?php echo $c['icon']; ?>"></i> <?php echo $c['label']; ?>
        </button>
        <?php endforeach; ?>
      </div>

      <div class="pos-products" id="menuGrid">
        <?php if (!empty($recetas)): ?>
          <?php foreach ($recetas as $r):
            $cfg = $catConfig[$r['categoria']] ?? $catConfig['otro'];
            $color = $catColors[$r['categoria']] ?? '#7f8c8d';
            $sin_stock      = false;
            $porciones_disp = null;
            foreach ($r['ingredientes'] as $ing) {
                $cant = (float)$ing['cantidad'];
                if ($cant <= 0) continue;
                $p = (int)floor((float)$ing['cantidad_stock'] / $cant);
                $porciones_disp = ($porciones_disp === null) ? $p : min($porciones_disp, $p);
                if ($p === 0) $sin_stock = true;
            }
            $fotoIconoV = FotoUtil::primeraFoto($r['foto'] ?? '') ?? '';
          ?>
          <div class="prod-tile receta-card<?php echo $sin_stock ? ' pt-blocked sin-stock' : ''; ?>"
               id="pt-<?php echo $r['id']; ?>"
               data-id="<?php echo $r['id']; ?>"
               data-cat="<?php echo $r['categoria']; ?>"
               data-nombre="<?php echo strtolower(htmlspecialchars($r['nombre'])); ?>"
               data-sin-stock="<?php echo $sin_stock ? '1' : '0'; ?>"
               style="--tc:<?php echo $color; ?>">
            <span class="qty-badge" id="badge-<?php echo $r['id']; ?>">1</span>
            <div class="pt-icon">
              <?php if ($fotoIconoV): ?>
                <img src="<?php echo htmlspecialchars($fotoIconoV); ?>" alt="<?php echo htmlspecialchars($r['nombre']); ?>">
              <?php else: ?>
                <i class="fas <?php echo $cfg['icon']; ?>" style="color:<?php echo $sin_stock ? '#95a5a6' : $color; ?>"></i>
              <?php endif; ?>
            </div>
            <div class="pt-info">
              <div class="pt-nombre"><?php echo htmlspecialchars($r['nombre']); ?></div>
              <?php if ($sin_stock): ?>
                <div class="pt-alerta"><i class="fas fa-ban"></i> Sin stock</div>
              <?php elseif ((float)$r['precio_venta'] <= 0): ?>
                <div class="pt-alerta"><i class="fas fa-triangle-exclamation"></i> Sin precio</div>
              <?php else: ?>
                <div class="pt-precio">$<?php echo number_format((float)$r['precio_venta'], 2); ?></div>
                <div class="pt-stock">
                  <?php if ($porciones_disp === null): ?>
                    ∞ Libre
                  <?php else: ?>
                    <i class="fas fa-box"></i> <?php echo $porciones_disp; ?> disp.
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <?php if (!$sin_stock && (float)$r['precio_venta'] > 0): ?>
            <button type="button" class="pt-add btn-add-receta" data-id="<?php echo $r['id']; ?>">
              <i class="fas fa-plus"></i>
            </button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#b2bec3">
            <i class="fas fa-book-open" style="font-size:36px;display:block;margin-bottom:12px"></i>
            No hay recetas activas. <a href="<?php echo $basePath; ?>/recetas" style="color:#2c3e50;">Ir a Recetas</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── CUENTA ── -->
    <div class="pos-bill">

      <div class="bill-head">
        <div class="bill-head-left">
          <div class="bill-title">Orden actual</div>
          <div class="bill-sub">Venta directa</div>
        </div>
        <div class="bill-head-right">
          <button class="btn-limpiar-orden" id="btnLimpiarOrden" style="background:none;border:none;color:#e74c3c;cursor:pointer;font-size:12px;font-weight:700">
            <i class="fas fa-trash"></i> Limpiar
          </button>
        </div>
      </div>

      <div class="bill-nuevo-header">
        <i class="fas fa-plus-circle"></i> Nueva orden
      </div>

      <div class="bill-empty" id="ordenVacia">
        <i class="fas fa-utensils"></i>
        <p>Toca un producto del menú para agregarlo</p>
      </div>
      <div class="bill-items" id="ordenItems"></div>

      <div class="bill-divider"></div>

      <div class="bill-notas">
        <label for="notasOrden"><i class="fas fa-pen-to-square"></i> Nota para cocina</label>
        <textarea id="notasOrden" placeholder="Ej: sin cebolla, bien cocido..."></textarea>
      </div>

      <div class="bill-divider"></div>

      <div class="bill-totals" id="ordenTotales" style="display:none">
        <div class="bill-total-row">
          <span>Nueva orden</span><span id="subtotalVal">$0.00</span>
        </div>
        <div class="bill-total-row" id="billDescuentoRow" style="display:none;color:#27ae60">
          <span><i class="fas fa-tag"></i> Descuento cupón</span>
          <span id="billDescuentoVal">−$0.00</span>
        </div>
        <div class="bill-total-row bill-total-main">
          <span>Total mesa</span><span id="totalVal">$0.00</span>
        </div>
      </div>

      <div class="bill-actions">
        <button type="button" class="ba-cobrar" id="btnCobrar" disabled>
          <i class="fas fa-check-circle"></i>
          <span>Cobrar todo</span>
          <strong id="btnCobrarMonto">$0.00</strong>
        </button>

        <a id="toggleCupon" href="#" onclick="event.preventDefault();toggleCuponInput()"
           style="display:block;text-align:center;font-size:13px;color:#95a5a6;text-decoration:none;user-select:none;padding:4px 0">
          <i class="fas fa-tag" id="iconCupon"></i> Agregar cupón
        </a>
        <div id="cuponWrap" style="display:none;margin-top:4px">
          <input type="text" id="cuponVentaInput" placeholder="Ingrese cupón de descuento"
                 maxlength="8" oninput="this.value=this.value.toUpperCase()"
                 style="width:100%;border:1.5px solid #e8ecf0;border-radius:10px;padding:9px 14px;font-size:13px;font-family:monospace;letter-spacing:2px;outline:none;box-sizing:border-box;">
          <div id="cuponVentaMsg" style="font-size:12px;margin-top:4px"></div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Ticket oculto para impresión (mismo formato que Ventas → Listado) -->
<style>
#vlTicket { display:none; }
@media print {
    body * { visibility: hidden; }
    #vlTicket, #vlTicket * { visibility: visible; }
    #vlTicket {
        display: block !important;
        position: fixed; top: 0; left: 0;
        width: 100%;
        box-sizing: border-box;
        padding: 0 3mm;
        font-family: "Courier New", monospace;
        font-size: <?php echo $papel['fontSize']; ?>;
        line-height: 1.35;
        white-space: pre-wrap;
        word-break: break-word;
        color: #000;
        background: #fff;
    }
    @page { size: <?php echo $papel['pageSize']; ?>; margin: <?php echo $papel['margin']; ?>; }
}
</style>
<div id="vlTicket"></div>

<!-- ── Modal ticket / recibo ─────────────────────────────────────────── -->
<div class="modal-overlay" id="ticketModal">
    <div class="ticket-modal">
        <div class="ticket-header">
            <div class="ticket-check"><i class="fas fa-check-circle"></i></div>
            <h2>¡Venta Registrada!</h2>
            <div class="ticket-numero" id="ticketNumero"></div>
        </div>
        <div class="ticket-body">
            <div class="ticket-section">
                <h4><i class="fas fa-list"></i> Platos</h4>
                <div id="ticketItems"></div>
                <div class="ticket-total">
                    <span>Total</span>
                    <span id="ticketTotal"></span>
                </div>
            </div>
            <div class="ticket-section">
                <h4><i class="fas fa-carrot"></i> Ingredientes descontados</h4>
                <div id="ticketIngredientes"></div>
            </div>
        </div>
        <div class="ticket-footer">
            <button class="btn-ticket btn-ticket-print" id="btnImprimir">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button class="btn-ticket btn-ticket-close" id="btnCerrarTicket">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';
    const BASEURL  = <?php echo json_encode($baseUrl); ?>;
    const COMERC   = <?php echo json_encode($comercio ?? []); ?>;
    const USUARIO_NOMBRE = <?php echo json_encode($_SESSION['usuario_nombre'] ?? 'Sistema'); ?>;
    const TICKET_W = <?php echo (int)$papel['charWidth']; ?>;
    const TICKET_ANGOSTO = <?php echo (($comercio['tamano_papel'] ?? '80mm') === '58mm') ? 'true' : 'false'; ?>;

    // ── Datos del servidor ────────────────────────────────────────────────
    const recetasData = <?php echo $recetasJson; ?>;

    // Mapa rápido id_receta → receta
    const recetaMap = {};
    recetasData.forEach(r => { recetaMap[r.id] = r; });

    // ── Estado del carrito ────────────────────────────────────────────────
    let carrito = {}; // { id_receta: cantidad }

    // ── Filtros del menú ──────────────────────────────────────────────────
    let catActual  = '';
    let termSearch = '';

    document.getElementById('menuSearch').addEventListener('input', function () {
        termSearch = this.value.toLowerCase();
        filtrarMenu();
    });

    document.querySelectorAll('.pos-cat').forEach(pill => {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.pos-cat').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            catActual = this.dataset.cat;
            filtrarMenu();
        });
    });

    function filtrarMenu() {
        document.querySelectorAll('.receta-card').forEach(card => {
            const matchCat    = !catActual  || card.dataset.cat    === catActual;
            const matchSearch = !termSearch || card.dataset.nombre.includes(termSearch);
            card.style.display = (matchCat && matchSearch) ? '' : 'none';
        });
    }

    // ── Agregar receta al carrito ─────────────────────────────────────────
    document.querySelectorAll('.receta-card').forEach(card => {
        card.addEventListener('click', function () {
            if (this.classList.contains('sin-stock') || this.classList.contains('pt-blocked')) return;
            agregar(this.dataset.id);
        });
    });

    function agregar(id) {
        id = String(id);
        if (carrito[id]) {
            carrito[id]++;
        } else {
            carrito[id] = 1;
        }
        actualizarUI();
    }

    function quitar(id) {
        id = String(id);
        if (!carrito[id]) return;
        carrito[id]--;
        if (carrito[id] <= 0) delete carrito[id];
        actualizarUI();
    }

    function eliminarItem(id) {
        delete carrito[String(id)];
        actualizarUI();
    }

    function limpiarCarrito() {
        carrito = {};
        actualizarUI();
    }

    document.getElementById('btnLimpiarOrden').addEventListener('click', limpiarCarrito);

    // ── Renderizar toda la UI ─────────────────────────────────────────────
    function actualizarUI() {
        renderOrden();
        actualizarBadges();
        actualizarEstadoStock(calcularConsumo());
    }

    function calcularConsumo() {
        const consumo = {};
        Object.keys(carrito).forEach(id => {
            const r = recetaMap[id];
            if (!r) return;
            const qty = carrito[id];
            (r.ingredientes || []).forEach(ing => {
                const iid = String(ing.id_insumo);
                if (!consumo[iid]) {
                    consumo[iid] = {
                        nombre:    ing.insumo_nombre,
                        unidad:    ing.unidad_medida,
                        stock:     parseFloat(ing.cantidad_stock),
                        minimo:    parseFloat(ing.cantidad_minima || 0),
                        necesario: 0,
                    };
                }
                consumo[iid].necesario += parseFloat(ing.cantidad) * qty;
            });
        });
        return consumo;
    }

    // ── Render items de la orden ──────────────────────────────────────────
    function renderOrden() {
        const container = document.getElementById('ordenItems');
        const vacia     = document.getElementById('ordenVacia');
        const totales   = document.getElementById('ordenTotales');
        const btnC      = document.getElementById('btnCobrar');

        const ids = Object.keys(carrito);

        if (ids.length === 0) {
            container.innerHTML = '';
            vacia.style.display = 'flex';
            totales.style.display = 'none';
            btnC.disabled = true;
            return;
        }

        vacia.style.display = 'none';
        totales.style.display = 'block';
        btnC.disabled = false;

        let html = '';
        let total = 0;

        ids.forEach(id => {
            const r   = recetaMap[id];
            if (!r) return;
            const qty = carrito[id];
            const sub = parseFloat(r.precio_venta) * qty;
            total += sub;

            let maxStock = Infinity;
            (r.ingredientes || []).forEach(ing => {
                const req = parseFloat(ing.cantidad);
                if (req <= 0) return;
                maxStock = Math.min(maxStock, Math.floor(parseFloat(ing.cantidad_stock) / req));
            });
            const maxLabel = maxStock === Infinity ? '∞' : maxStock;
            const atMax    = maxStock !== Infinity && qty >= maxStock;

            html += `
            <div class="bill-item" data-receta-id="${r.id}">
                <div class="bi-ctrl">
                    <button class="bi-btn" type="button" data-delta="-1">−</button>
                    <span class="bi-qty">${qty}</span>
                    <button class="bi-btn" type="button" data-delta="1" ${atMax ? 'disabled' : ''}>+</button>
                </div>
                <div class="bi-info">
                    <div class="bi-nombre">${r.nombre}</div>
                    <div class="bi-max">máx ${maxLabel}</div>
                </div>
                <div class="bi-sub">$${sub.toFixed(2)}</div>
            </div>`;
        });

        container.innerHTML = html;

        document.getElementById('subtotalVal').textContent = '$' + total.toFixed(2);
        document.getElementById('totalVal').textContent    = '$' + total.toFixed(2);
        const montoEl = document.getElementById('btnCobrarMonto');
        if (montoEl) montoEl.textContent = '$' + total.toFixed(2);
    }

    // Event delegation para los botones +/− de los items
    document.getElementById('ordenItems').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-delta]');
        if (!btn) return;
        const row = btn.closest('[data-receta-id]');
        if (!row) return;
        const id    = String(row.dataset.recetaId);
        const delta = +btn.dataset.delta;
        if (delta < 0) quitar(id); else agregar(id);
    });

    // Actualiza qué tarjetas son clickeables según stock restante
    function actualizarEstadoStock(consumo) {
        recetasData.forEach(r => {
            const card = document.querySelector(`.receta-card[data-id="${r.id}"]`);
            const btn  = document.querySelector(`.btn-add-receta[data-id="${r.id}"]`);
            if (!card || !btn) return;

            const qty = carrito[String(r.id)] || 0;
            if (qty > 0) return; // muestra "En orden", no tocar

            let puedeAgregar = true;
            (r.ingredientes || []).forEach(ing => {
                const cantNecesaria = parseFloat(ing.cantidad);
                if (cantNecesaria <= 0) return;
                const stock    = parseFloat(ing.cantidad_stock);
                const yaUsa    = consumo[String(ing.id_insumo)] ? consumo[String(ing.id_insumo)].necesario : 0;
                if (cantNecesaria > stock - yaUsa) puedeAgregar = false;
            });

            card.classList.toggle('sin-stock', !puedeAgregar);
            btn.disabled = !puedeAgregar;
            btn.classList.toggle('sin-stock', !puedeAgregar);
            if (!puedeAgregar) {
                btn.innerHTML = '<i class="fas fa-times-circle"></i> Sin stock';
            } else if (!btn.classList.contains('agregado')) {
                btn.innerHTML = '<i class="fas fa-plus"></i> Agregar';
            }
        });
    }

    // ── Actualizar badges en tarjetas ─────────────────────────────────────
    function actualizarBadges() {
        recetasData.forEach(r => {
            const badge = document.getElementById('badge-' + r.id);
            const card  = document.querySelector(`.receta-card[data-id="${r.id}"]`);
            const btn   = document.querySelector(`.btn-add-receta[data-id="${r.id}"]`);
            const qty   = carrito[String(r.id)] || 0;

            if (badge) {
                badge.textContent = qty;
                badge.classList.toggle('show', qty > 0);
            }
            if (card)  card.classList.toggle('en-carrito', qty > 0);
            if (btn) {
                btn.classList.toggle('agregado', qty > 0);
                btn.innerHTML = qty > 0
                    ? `<i class="fas fa-check"></i> En orden (${qty})`
                    : `<i class="fas fa-plus"></i> Agregar`;
            }
        });
    }

    // ── Toggle cupón ─────────────────────────────────────────────────────
    function toggleCuponInput() {
        const wrap  = document.getElementById('cuponWrap');
        const icon  = document.getElementById('iconCupon');
        const link  = document.getElementById('toggleCupon');
        const abierto = wrap.style.display !== 'none';
        wrap.style.display = abierto ? 'none' : 'block';
        icon.className = abierto ? 'fas fa-tag' : 'fas fa-times';
        link.style.color = abierto ? '#95a5a6' : '#e74c3c';
        if (!abierto) {
            document.getElementById('cuponVentaInput').focus();
        } else {
            document.getElementById('cuponVentaInput').value = '';
            document.getElementById('cuponVentaMsg').innerHTML = '';
        }
    }

    // ── Confirmar venta ───────────────────────────────────────────────────
    document.getElementById('btnCobrar').addEventListener('click', confirmarVenta);

    function confirmarVenta() {
        const ids = Object.keys(carrito);
        if (ids.length === 0) return;

        // Construir payload
        const items = ids.map(id => {
            const r = recetaMap[id];
            return {
                id_receta:       parseInt(id),
                nombre:          r.nombre,
                cantidad:        carrito[id],
                precio_unitario: parseFloat(r.precio_venta),
                ingredientes:    r.ingredientes,
            };
        });

        const notas = document.getElementById('notasOrden').value.trim();

        Swal.fire({
            title: '¿Confirmar venta?',
            html: `<strong>${items.length}</strong> plato(s) — <strong>Total: $${calcTotal().toFixed(2)}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#27ae60',
            cancelButtonColor:  '#95a5a6',
            confirmButtonText:  '✓ Sí, confirmar',
            cancelButtonText:   'Cancelar',
        }).then(res => {
            if (!res.isConfirmed) return;

            const btnC = document.getElementById('btnCobrar');
            const orig = btnC.innerHTML;
            btnC.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
            btnC.disabled  = true;

            fetch(basePath + '/ventas/registrar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ items, notas }),
            })
            .then(async r => {
                const text = await r.text();
                try { return JSON.parse(text); }
                catch(e) { throw new Error(text.substring(0, 400)); }
            })
            .then(data => {
                btnC.innerHTML = orig;
                btnC.disabled  = false;

                if (data.success) {
                    mostrarTicket(data, items);
                    limpiarCarrito();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(err => {
                btnC.innerHTML = orig;
                btnC.disabled  = false;
                Swal.fire({ icon: 'error', title: 'Error del servidor', text: err.message || 'No se pudo conectar.' });
            });
        });
    }

    function calcTotal() {
        return Object.keys(carrito).reduce((acc, id) => {
            const r = recetaMap[id];
            return acc + (r ? parseFloat(r.precio_venta) * carrito[id] : 0);
        }, 0);
    }

    // ── Ticket de venta ───────────────────────────────────────────────────
    let ventaImpresa = null; // { data, items } de la última venta registrada

    function mostrarTicket(data, items) {
        ventaImpresa = { data, items };
        document.getElementById('ticketNumero').textContent = data.numero + ' — ' + new Date().toLocaleString('es');
        document.getElementById('ticketTotal').textContent  = '$' + parseFloat(data.total).toFixed(2);

        // Items
        document.getElementById('ticketItems').innerHTML = items.map(it =>
            `<div class="ticket-item">
                <span class="ticket-item-nombre">${it.nombre} × ${it.cantidad}</span>
                <span class="ticket-item-precio">$${(it.precio_unitario * it.cantidad).toFixed(2)}</span>
            </div>`
        ).join('');

        // Consumo de ingredientes
        const consumo = {};
        items.forEach(it => {
            (it.ingredientes || []).forEach(ing => {
                const iid = ing.id_insumo;
                if (!consumo[iid]) consumo[iid] = { nombre: ing.insumo_nombre, unidad: ing.unidad_medida, total: 0 };
                consumo[iid].total += parseFloat(ing.cantidad) * it.cantidad;
            });
        });

        document.getElementById('ticketIngredientes').innerHTML =
            Object.values(consumo).map(c =>
                `<div class="ing-consumo-item">
                    <span>${c.nombre}</span>
                    <span>−${c.total.toFixed(2)} ${c.unidad}</span>
                </div>`
            ).join('') || '<p style="color:#bdc3c7;font-size:12px;">Sin ingredientes registrados</p>';

        document.getElementById('ticketModal').classList.add('active');
    }

    document.getElementById('btnCerrarTicket').addEventListener('click', () => {
        document.getElementById('ticketModal').classList.remove('active');
        location.reload();
    });

    document.getElementById('btnImprimir').addEventListener('click', imprimirFacturaVenta);

    // Mismo formato de recibo que Ventas → Listado (ver construirTicket() allá)
    const ESTADO_LABEL = {
        cobrada: 'Cobrada', cancelada: 'Cancelada', completada: 'Completada',
        abierta: 'Abierta', en_preparacion: 'En cocina', lista: 'Lista',
    };

    function imprimirFacturaVenta() {
        if (!ventaImpresa) return;
        const logoHtml = COMERC.logo
            ? `<div style="text-align:center;margin-bottom:6px;">
                 <img src="${BASEURL}/assets/uploads/comercio/${COMERC.logo}"
                      style="max-width:120px;max-height:70px;object-fit:contain;">
               </div>`
            : '';
        document.getElementById('vlTicket').innerHTML = logoHtml + construirTicketVenta(ventaImpresa.data, ventaImpresa.items);
        setTimeout(() => window.print(), 80);
    }

    function construirTicketVenta(data, items) {
        const W   = TICKET_W;
        const sep = '='.repeat(W);
        const lin = '-'.repeat(W);
        const neg = COMERC.nombre || 'CHEFCONTROL';
        const esl = COMERC.eslogan || '';
        const rut = COMERC.rut || '';
        const ahora = new Date();
        const fecha = ahora.toLocaleDateString('es');
        const hora  = ahora.toLocaleTimeString('es', {hour:'2-digit', minute:'2-digit'});

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
            words.forEach(w => {
                if ((cur + ' ' + w).trim().length <= max) cur = (cur + ' ' + w).trim();
                else { if (cur) lines.push(cur); cur = w; }
            });
            if (cur) lines.push(cur);
            return lines;
        }

        let t = '';
        t += sep + '\n';
        t += centro(neg) + '\n';
        if (esl) t += centro(esl) + '\n';
        if (rut) t += centro('RUT: ' + rut) + '\n';
        t += sep + '\n';
        t += 'Orden:   ' + data.numero + '\n';
        t += 'Fecha:   ' + fecha + ' ' + hora + '\n';
        t += 'Mesero:  ' + USUARIO_NOMBRE + '\n';
        t += 'Mesa:    —\n';
        t += 'Estado:  ' + (ESTADO_LABEL[data.estado] || data.estado) + '\n';
        t += lin + '\n';

        if (TICKET_ANGOSTO) {
            // 58mm: muy poco ancho para columnas — nombre completo, luego
            // cantidad y precio cada uno en su propia línea.
            items.forEach(it => {
                wrap(it.nombre, W).forEach(l => t += l + '\n');
                t += 'x' + it.cantidad + '\n';
                t += '$' + (it.precio_unitario * it.cantidad).toFixed(2) + '\n';
            });
        } else {
            t += fila('PRODUCTO', 'CANT  SUBTOT') + '\n';
            t += lin + '\n';
            items.forEach(it => {
                const nomLines = wrap(it.nombre, Math.max(10, W - 16));
                const sub  = '$' + (it.precio_unitario * it.cantidad).toFixed(2);
                const cant = 'x' + it.cantidad;
                t += fila(nomLines[0], cant + '  ' + sub) + '\n';
                for (let i = 1; i < nomLines.length; i++) t += nomLines[i] + '\n';
            });
        }

        t += lin + '\n';
        t += fila('TOTAL:', '$' + parseFloat(data.total).toFixed(2)) + '\n';
        t += sep + '\n';
        t += centro('¡Gracias por su visita!') + '\n';
        t += sep + '\n';
        t += '\n';
        t += centro('CHEFCONTROL') + '\n';
        t += centro('Creado por') + '\n';
        t += centro('CLOUD CONTROL TECNOLOGYS') + '\n';

        return t;
    }

    document.getElementById('ticketModal').addEventListener('click', function (e) {
        if (e.target === this) this.classList.remove('active');
    });

    // ── API pública (para los onclick inline) ─────────────────────────────
    window.VENTAS = { agregar, quitar, eliminarItem };

})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
