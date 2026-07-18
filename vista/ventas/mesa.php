<?php
// vista/ventas/mesa.php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../core/FotoUtil.php';

$titulo       = 'Mesa ' . ($mesa['numero'] ?? '') . ' — CHEFCONTROL';
$paginaActual = 'salon';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/mesa-pos.css?v=' . Config::assetVer('assets/css/mesa-pos.css') . '">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
require_once __DIR__ . '/../complementos/header.php';

require_once __DIR__ . '/../../modelo/comercioModel.php';
$papel = ComercioModel::parametrosPapel($comercio['tamano_papel'] ?? '80mm');

$catCfg = [
    'entrada'      => ['label'=>'Entradas',    'icon'=>'fa-utensils',       'color'=>'#e67e22'],
    'plato_fuerte' => ['label'=>'Principales', 'icon'=>'fa-drumstick-bite', 'color'=>'#e74c3c'],
    'postre'       => ['label'=>'Postres',     'icon'=>'fa-ice-cream',      'color'=>'#9b59b6'],
    'bebida'       => ['label'=>'Bebidas',     'icon'=>'fa-wine-glass',     'color'=>'#3498db'],
    'snack'        => ['label'=>'Snacks',      'icon'=>'fa-cookie-bite',    'color'=>'#27ae60'],
    'otro'         => ['label'=>'Otros',       'icon'=>'fa-bowl-food',      'color'=>'#7f8c8d'],
];

$ordenes        = $ordenes ?? [];
$cats           = array_unique(array_column($recetas ?? [], 'categoria'));
$id_mesa        = (int)$mesa['id'];
$totalExistente = (float)array_sum(array_column($ordenes, 'total'));
$hayOrdenes     = !empty($ordenes);

// Cuántas unidades de cada receta ya están en órdenes activas (pendiente / cocina / lista)
// Esto descuenta del stock disponible al mostrar el catálogo
$yaOrdenado = []; // [ id_receta => cantidad_total ]
foreach ($ordenes as $o) {
    foreach ($o['items'] as $it) {
        $rid = (int)$it['id_receta'];
        $yaOrdenado[$rid] = ($yaOrdenado[$rid] ?? 0) + (int)$it['cantidad'];
    }
}

// Subtotal por receta en las órdenes existentes (para cupones por producto)
$subtotalPorReceta = [];
foreach ($ordenes as $o) {
    foreach ($o['items'] as $it) {
        $rid = (int)$it['id_receta'];
        $subtotalPorReceta[$rid] = ($subtotalPorReceta[$rid] ?? 0.0) + (float)$it['precio_unitario'] * (int)$it['cantidad'];
    }
}

$estadoLabel = ['disponible'=>'Libre','ocupada'=>'Ocupada','reservada'=>'Reservada','mantenimiento'=>'Mantenim.'][$mesa['estado']] ?? $mesa['estado'];
$estadoColor = ['disponible'=>'#27ae60','ocupada'=>'#e74c3c','reservada'=>'#f39c12','mantenimiento'=>'#95a5a6'][$mesa['estado']] ?? '#95a5a6';

$ordenEstadoLabel = ['abierta'=>'Pendiente','en_preparacion'=>'En cocina','lista'=>'Lista'];
$ordenEstadoCls   = ['abierta'=>'bon-pend','en_preparacion'=>'bon-prep','lista'=>'bon-list'];
?>

<div class="pos-root">

  <!-- ══ BARRA SUPERIOR ══ -->
  <div class="pos-topbar">
    <a href="<?php echo $basePath; ?>/ventas/salon" class="pos-back">
      <i class="fas fa-arrow-left"></i>
    </a>

    <div class="pos-topbar-mesa">
      <span class="pos-mesa-num">Mesa <?php echo (int)$mesa['numero']; ?></span>
      <?php if (!empty($mesa['nombre']) && $mesa['nombre'] !== 'Mesa '.$mesa['numero']): ?>
        <span class="pos-mesa-sep">—</span>
        <span class="pos-mesa-name"><?php echo htmlspecialchars($mesa['nombre']); ?></span>
      <?php endif; ?>
    </div>

    <div class="pos-topbar-meta">
      <?php if (!empty($mesa['zona'])): ?>
        <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($mesa['zona']); ?></span>
      <?php endif; ?>
      <span><i class="fas fa-users"></i> <?php echo (int)$mesa['capacidad']; ?> personas</span>
      <span class="pos-estado-chip" style="background:<?php echo $estadoColor; ?>20;color:<?php echo $estadoColor; ?>;border-color:<?php echo $estadoColor; ?>40">
        <?php echo $estadoLabel; ?>
      </span>
    </div>
  </div>

  <!-- ══ CUERPO PRINCIPAL ══ -->
  <div class="pos-main">

    <!-- ── CATÁLOGO ── -->
    <div class="pos-catalog">

      <div class="pos-cats-bar">
        <button class="pos-cat active" data-cat="all">
          <i class="fas fa-th-large"></i> Todo
        </button>
        <?php foreach ($cats as $cat):
          $c = $catCfg[$cat] ?? ['label'=>ucfirst($cat),'icon'=>'fa-bowl-food','color'=>'#7f8c8d'];
        ?>
        <button class="pos-cat" data-cat="<?php echo $cat; ?>"
                style="--cc:<?php echo $c['color']; ?>">
          <i class="fas <?php echo $c['icon']; ?>"></i> <?php echo $c['label']; ?>
        </button>
        <?php endforeach; ?>
      </div>

      <div class="pos-products" id="posProducts">
        <?php foreach ($recetas ?? [] as $r):
          $sinPrecio = ((float)$r['precio_venta'] <= 0);

          // Porciones brutas según stock actual
          $porciones  = PHP_INT_MAX;
          $tieneIngrs = false;
          foreach ($r['ingredientes'] as $ing) {
            $req = (float)$ing['cantidad'];
            if ($req <= 0) continue;
            $tieneIngrs = true;
            $p = floor((float)$ing['cantidad_stock'] / $req);
            if ($p < $porciones) $porciones = $p;
          }
          $libre = !$tieneIngrs; // sin ingredientes → stock ilimitado
          if (!$libre) {
            if ($porciones === PHP_INT_MAX) $porciones = 0;
            // Restar lo ya comprometido en órdenes activas de esta mesa
            $porciones = max(0, $porciones - ($yaOrdenado[$r['id']] ?? 0));
          }
          $sinStock  = !$libre && ($porciones <= 0);
          $bloqueado = $sinStock || $sinPrecio;
          $c = $catCfg[$r['categoria']] ?? ['icon'=>'fa-bowl-food','color'=>'#7f8c8d'];
        ?>
        <?php
          // json_encode genera "..." — htmlspecialchars convierte " en &quot; para no romper el atributo HTML
          $maxParaJs  = $libre ? 9999 : $porciones;
          $onclickAdd = $bloqueado ? '' : sprintf(
            'onclick="agregarItem(%d,%s,%s,%d,%s)"',
            $r['id'],
            htmlspecialchars(json_encode($r['nombre']), ENT_QUOTES),
            json_encode((float)$r['precio_venta']),
            $maxParaJs,
            $libre ? 'true' : 'false'
          );
        ?>
        <div class="prod-tile <?php echo $bloqueado ? 'pt-blocked' : ''; ?>"
             id="pt-<?php echo $r['id']; ?>"
             data-id="<?php echo $r['id']; ?>"
             data-cat="<?php echo htmlspecialchars($r['categoria']); ?>"
             data-porciones="<?php echo $libre ? 9999 : $porciones; ?>"
             data-libre="<?php echo $libre ? '1' : '0'; ?>"
             <?php echo $onclickAdd; ?>
             style="--tc:<?php echo $c['color']; ?>">

          <div class="pt-icon">
            <?php
              $fotoIcono = FotoUtil::primeraFoto($r['foto'] ?? '') ?? '';
            ?>
            <?php if ($fotoIcono): ?>
              <img src="<?php echo htmlspecialchars($fotoIcono); ?>"
                   alt="<?php echo htmlspecialchars($r['nombre']); ?>">
            <?php else: ?>
              <i class="fas <?php echo $c['icon']; ?>"></i>
            <?php endif; ?>
          </div>

          <div class="pt-info">
            <div class="pt-nombre" title="<?php echo htmlspecialchars($r['nombre']); ?>"><?php echo htmlspecialchars($r['nombre']); ?></div>
            <?php if ($sinPrecio): ?>
              <div class="pt-alerta"><i class="fas fa-triangle-exclamation"></i> Sin precio</div>
            <?php elseif ($sinStock): ?>
              <div class="pt-alerta"><i class="fas fa-ban"></i> Sin stock</div>
            <?php else: ?>
              <div class="pt-precio">$<?php echo number_format((float)$r['precio_venta'], 2); ?></div>
              <?php if ($libre): ?>
                <div class="pt-stock" id="ps-<?php echo $r['id']; ?>">∞ Libre</div>
              <?php else: ?>
                <div class="pt-stock" id="ps-<?php echo $r['id']; ?>"><i class="fas fa-box"></i> <?php echo $porciones; ?> disp.</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <?php if (!$bloqueado): ?>
          <button type="button" class="pt-add">
            <i class="fas fa-plus"></i>
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── CUENTA / TICKET ── -->
    <div class="pos-bill">

      <!-- Cabecera -->
      <div class="bill-head">
        <div class="bill-head-left">
          <div class="bill-title">Cuenta · Mesa <?php echo (int)$mesa['numero']; ?></div>
          <div class="bill-sub" id="billOrdenCount">
            <?php echo $hayOrdenes ? count($ordenes).' orden'.( count($ordenes)!==1?'es':'' ).' activa'.( count($ordenes)!==1?'s':'' ) : 'Sin órdenes'; ?>
          </div>
        </div>
        <div class="bill-head-right">
          <div class="bill-items-count" id="billHeadTotal">
            $<?php echo number_format($totalExistente, 2); ?>
          </div>
        </div>
      </div>

      <!-- Cuerpo scrollable -->
      <div class="bill-body">

      <!-- Órdenes ya enviadas (solo lectura) -->
      <div class="bill-ordenes" id="billOrdenes">
        <?php foreach ($ordenes as $o):
          $est   = $o['estado'];
          $eLbl  = $ordenEstadoLabel[$est] ?? ucfirst($est);
          $eCls  = $ordenEstadoCls[$est]   ?? 'bon-pend';
        ?>
        <div class="bill-orden">
          <div class="bill-orden-head">
            <span class="bon-num"><?php echo htmlspecialchars($o['numero_orden']); ?></span>
            <span class="bon-badge <?php echo $eCls; ?>"><?php echo $eLbl; ?></span>
            <span class="bon-total">$<?php echo number_format((float)$o['total'], 2); ?></span>
          </div>
          <div class="bill-orden-items">
            <?php foreach ($o['items'] as $it): ?>
            <div class="boi-row">
              <span class="boi-qty"><?php echo (int)$it['cantidad']; ?>×</span>
              <span class="boi-nom"><?php echo htmlspecialchars($it['receta_nombre']); ?></span>
              <span class="boi-sub">$<?php echo number_format((float)$it['subtotal'], 2); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Nueva orden (editable) -->
      <div class="bill-nuevo-header">
        <i class="fas fa-plus-circle"></i> Nueva orden
      </div>
      <div class="bill-empty" id="billEmpty">
        <i class="fas fa-utensils"></i>
        <p>Toca un producto del menú para agregarlo</p>
      </div>
      <div class="bill-items" id="billItems"></div>

      <div class="bill-divider"></div>

      <!-- Notas -->
      <div class="bill-notas">
        <label for="posNotas"><i class="fas fa-pen-to-square"></i> Nota para cocina</label>
        <textarea id="posNotas" placeholder="Ej: sin cebolla, bien cocido..."></textarea>
      </div>

      <div class="bill-divider"></div>

      <!-- Totales -->
      <div class="bill-totals">
        <?php if ($hayOrdenes): ?>
        <div class="bill-total-row">
          <span>Órdenes enviadas</span>
          <span>$<?php echo number_format($totalExistente, 2); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($comercio['propina_activa'])): ?>
        <div class="bill-total-row bill-propina-row">
          <span><i class="fas fa-hand-holding-heart"></i> Propina (<?php echo (int)($comercio['propina_porcentaje'] ?? 10); ?>%)</span>
          <input type="number" id="inputPropinaValor" min="0" step="0.01" class="bill-propina-input"
                 value="<?php echo number_format($totalExistente * ($comercio['propina_porcentaje'] ?? 10) / 100, 2, '.', ''); ?>">
        </div>
        <?php endif; ?>
        <div class="bill-total-row">
          <span>Nueva orden</span>
          <span id="billSub">$0.00</span>
        </div>
        <div class="bill-total-row" id="billDescuentoRow" style="display:none;color:#27ae60">
          <span><i class="fas fa-tag"></i> Descuento cupón</span>
          <span id="billDescuentoVal">−$0.00</span>
        </div>
        <div class="bill-total-row bill-total-main">
          <span>Total mesa</span>
          <span id="billTotal">$<?php
            $propInic = !empty($comercio['propina_activa']) ? $totalExistente * ($comercio['propina_porcentaje'] ?? 10) / 100 : 0;
            echo number_format($totalExistente + $propInic, 2);
          ?></span>
        </div>
      </div>

      </div><!-- /bill-body -->

      <!-- Acciones -->
      <div class="bill-actions">
        <button type="button" class="ba-guardar" id="btnGuardar" onclick="ordenar()" disabled>
          <i class="fas fa-paper-plane"></i> Ordenar
        </button>

        <button type="button" class="ba-cobrar" id="btnCobrar" onclick="cobrar()" <?php echo $hayOrdenes ? '' : 'disabled'; ?>>
          <i class="fas fa-check-circle"></i>
          <span>Cobrar todo</span>
          <strong id="btnCobrarMonto">$<?php
            $propInic2 = !empty($comercio['propina_activa']) ? $totalExistente * ($comercio['propina_porcentaje'] ?? 10) / 100 : 0;
            echo number_format($totalExistente + $propInic2, 2);
          ?></strong>
        </button>

        <!-- Toggle cupón -->
        <a id="mesaToggleCupon" href="#" onclick="event.preventDefault();mesaToggleCuponFn()"
           style="display:block;text-align:center;font-size:13px;color:#95a5a6;text-decoration:none;user-select:none;padding:4px 0">
          <i class="fas fa-tag" id="mesaIconCupon"></i> Agregar cupón
        </a>
        <div id="mesaCuponWrap" style="display:none;margin-top:4px">
          <input type="text" id="mesaCuponInput"
                 placeholder="Ingrese cupón de descuento"
                 maxlength="8"
                 style="width:100%;border:1.5px solid #e8ecf0;border-radius:10px;padding:9px 14px;font-size:13px;font-family:monospace;letter-spacing:2px;outline:none;box-sizing:border-box;transition:border-color .2s">
          <div id="mesaCuponMsg" style="font-size:12px;margin-top:4px"></div>
        </div>

        <?php if ($hayOrdenes): ?>
        <button type="button" class="ba-cancelar" onclick="cancelar()">
          <i class="fas fa-xmark"></i> Cancelar todas las órdenes
        </button>
        <?php endif; ?>
      </div>

    </div><!-- /pos-bill -->
  </div><!-- /pos-main -->
</div><!-- /pos-root -->

<script>
(function () {
  const BP       = <?php echo json_encode($basePath); ?>;
  const BASEURL  = <?php echo json_encode($baseUrl); ?>;
  const ID_MESA  = <?php echo $id_mesa; ?>;
  let   totalEx              = <?php echo $totalExistente; ?>;
  const SUBTOTAL_POR_RECETA  = <?php echo json_encode($subtotalPorReceta); ?>;
  const ORDENES_INIT         = <?php echo json_encode($ordenes); ?>;
  const COMERC               = <?php echo json_encode($comercio ?? []); ?>;
  const IMPRIMIR_COMANDA     = <?php echo (int)($comercio['imprimir_comanda_auto']  ?? 0); ?>;
  const IMPRIMIR_FACTURA_COB = <?php echo (int)($comercio['imprimir_factura_cobro'] ?? 0); ?>;
  const PROPINA_ACTIVA       = <?php echo (int)($comercio['propina_activa']         ?? 0); ?>;
  const PROPINA_PCT          = <?php echo (int)($comercio['propina_porcentaje']     ?? 10); ?>;
  const MESA_NUM             = <?php echo (int)$mesa['numero']; ?>;
  const MESA_NOM             = <?php echo json_encode($mesa['nombre'] ?? ''); ?>;
  const TICKET_W             = <?php echo (int)$papel['charWidth']; ?>;
  const TICKET_ANGOSTO       = <?php echo (($comercio['tamano_papel'] ?? '80mm') === '58mm') ? 'true' : 'false'; ?>;

  let nuevosItems      = []; // {id_receta, nombre, precio_unitario, cantidad}
  let mesaCupon        = null; // { id, tipo, descuento, id_receta? } — cupón validado
  let propinaUserSet   = false; // true si el usuario modificó el input manualmente

  if (PROPINA_ACTIVA) {
    const propInput = document.getElementById('inputPropinaValor');
    if (propInput) {
      propInput.addEventListener('input', () => {
        propinaUserSet = true;
        actualizarTotales(nuevosItems.reduce((s, it) => s + it.precio_unitario * it.cantidad, 0));
      });
    }
  }

  /* ── Filtro categoría ── */
  document.querySelectorAll('.pos-cat').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.pos-cat').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const cat = this.dataset.cat;
      document.querySelectorAll('.prod-tile').forEach(t => {
        t.style.display = (cat === 'all' || t.dataset.cat === cat) ? '' : 'none';
      });
    });
  });

  /* ── Agregar producto al pedido (llamado desde onclick del tile) ── */
  window.agregarItem = function (id, nombre, precio, max, libre) {
    if (nuevosItems.find(o => o.id_receta === id)) return; // ya en la orden
    if (max <= 0) return;
    nuevosItems.push({ id_receta: id, nombre, precio_unitario: precio, cantidad: 1, maxStock: max, libre: !!libre });
    render();
  };

  /* ── Cambiar cantidad ── */
  function changeQty(id, delta) {
    const idx = nuevosItems.findIndex(o => o.id_receta === id);
    if (idx === -1) return;
    const item = nuevosItems[idx];
    const nueva = item.cantidad + delta;
    if (nueva <= 0) { nuevosItems.splice(idx, 1); render(); return; }
    if (nueva > item.maxStock) return; // no superar stock
    item.cantidad = nueva;
    render();
  }

  /* Event delegation: botones +/- */
  const billItemsEl = document.getElementById('billItems');
  billItemsEl.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-delta]');
    if (!btn) return;
    const row = btn.closest('[data-receta-id]');
    if (!row) return;
    changeQty(+row.dataset.recetaId, +btn.dataset.delta);
  });

  /* ── Render ── */
  function render() {
    const empty     = document.getElementById('billEmpty');
    const container = document.getElementById('billItems');
    const btnG      = document.getElementById('btnGuardar');

    // Actualizar stock disponible en tiles del catálogo
    document.querySelectorAll('.prod-tile').forEach(tile => {
      const id       = +tile.dataset.id;
      const it       = nuevosItems.find(o => o.id_receta === id);
      const qty      = it ? it.cantidad : 0;
      const esLibre  = tile.dataset.libre === '1';
      const maxOrig  = +tile.dataset.porciones;
      const restante = maxOrig - qty;
      const stockEl  = document.getElementById('ps-' + id);
      if (stockEl) {
        if (esLibre) {
          stockEl.innerHTML = '∞ Libre';
          stockEl.style.color = '';
        } else {
          stockEl.innerHTML = `<i class="fas fa-box"></i> ${restante} disp.`;
          stockEl.style.color = restante <= 0 ? '#e74c3c' : '';
        }
      }
      if (qty > 0) {
        tile.classList.add('pt-active');
      } else {
        tile.classList.remove('pt-active');
      }
    });

    if (nuevosItems.length === 0) {
      container.innerHTML = '';
      empty.style.display = 'flex';
      actualizarTotales(0);
      btnG.disabled = true;
      return;
    }

    empty.style.display = 'none';
    let subtotalNuevo = 0;
    let html = '';
    nuevosItems.forEach(it => {
      const sub      = it.precio_unitario * it.cantidad;
      const atMax    = !it.libre && it.cantidad >= it.maxStock;
      const maxLabel = it.libre ? '∞' : it.maxStock;
      subtotalNuevo += sub;
      html += `
      <div class="bill-item" data-receta-id="${it.id_receta}">
        <div class="bi-ctrl">
          <button class="bi-btn" type="button" data-delta="-1">−</button>
          <span class="bi-qty">${it.cantidad}</span>
          <button class="bi-btn" type="button" data-delta="1" ${atMax ? 'disabled' : ''}>+</button>
        </div>
        <div class="bi-info">
          <div class="bi-nombre">${esc(it.nombre)}</div>
          <div class="bi-max">máx ${maxLabel}</div>
        </div>
        <div class="bi-sub">$${sub.toFixed(2)}</div>
      </div>`;
    });
    container.innerHTML = html;
    actualizarTotales(subtotalNuevo);
    btnG.disabled = false;
  }

  function calcDescuentoMesa(totalBruto, subtotalNuevo) {
    if (!mesaCupon) return 0;
    if (mesaCupon.tipo === 'porcentaje') return totalBruto * mesaCupon.descuento / 100;
    if (mesaCupon.tipo === 'producto' && mesaCupon.id_receta) {
      let subtotalProd = SUBTOTAL_POR_RECETA[mesaCupon.id_receta] || 0;
      const nuevoItem = nuevosItems.find(it => it.id_receta === mesaCupon.id_receta);
      if (nuevoItem) subtotalProd += nuevoItem.precio_unitario * nuevoItem.cantidad;
      return Math.min(subtotalProd * mesaCupon.descuento / 100, totalBruto);
    }
    return Math.min(+mesaCupon.descuento, totalBruto);
  }

  function actualizarTotales(subtotalNuevo) {
    const totalBruto = totalEx + subtotalNuevo;
    const descuento  = calcDescuentoMesa(totalBruto, subtotalNuevo);
    const totalFinal = Math.max(0, totalBruto - descuento);

    // Propina: auto-recalcular si el usuario no la modificó manualmente
    let propina = 0;
    if (PROPINA_ACTIVA && PROPINA_PCT > 0) {
      const propInput = document.getElementById('inputPropinaValor');
      if (propInput) {
        if (!propinaUserSet) propInput.value = (totalFinal * PROPINA_PCT / 100).toFixed(2);
        propina = Math.max(0, parseFloat(propInput.value) || 0);
      }
    }

    const totalConPropina = totalFinal + propina;

    document.getElementById('billSub').textContent   = '$' + subtotalNuevo.toFixed(2);
    document.getElementById('billTotal').textContent = '$' + totalConPropina.toFixed(2);

    const descRow = document.getElementById('billDescuentoRow');
    if (descuento > 0) {
      descRow.style.display = '';
      document.getElementById('billDescuentoVal').textContent = '−$' + descuento.toFixed(2);
    } else {
      descRow.style.display = 'none';
    }

    const btnC = document.getElementById('btnCobrar');
    document.getElementById('btnCobrarMonto').textContent = '$' + totalConPropina.toFixed(2);
    btnC.disabled = (totalBruto <= 0);
  }

  /* ── Cupón: auto-validar al digitar el 8º carácter ── */
  function mesaCuponOnInput(input) {
    input.value = input.value.toUpperCase();
    if (input.value.length === 8) {
      mesaCuponAutoValidar(input);
    } else {
      input.style.borderColor = '#e8ecf0';
      if (mesaCupon) {
        mesaCupon = null;
        document.getElementById('mesaCuponMsg').innerHTML = '';
        actualizarTotales(nuevosItems.reduce((s,it) => s + it.precio_unitario * it.cantidad, 0));
      }
    }
  }

  async function mesaCuponAutoValidar(input) {
    input = input || document.getElementById('mesaCuponInput');
    const codigo = input.value.trim();
    try {
      const r = await fetch(BP + '/cupones/validar', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ codigo }),
      });
      const d = await r.json();
      if (d.ok) {
        mesaCupon = d;
        input.style.borderColor = '#27ae60';
        let descStr;
        if (d.tipo === 'porcentaje') {
          descStr = d.descuento + '%';
        } else if (d.tipo === 'producto') {
          descStr = d.descuento + '%' + (d.receta_nombre ? ' en ' + d.receta_nombre : '');
        } else {
          descStr = '$' + Number(d.descuento).toLocaleString();
        }
        document.getElementById('mesaCuponMsg').innerHTML =
          `<span style="color:#27ae60"><i class="fas fa-check-circle"></i> ${descStr} de descuento aplicado</span>`;
      } else {
        mesaCupon = null;
        input.style.borderColor = '#e74c3c';
        document.getElementById('mesaCuponMsg').innerHTML =
          `<span style="color:#e74c3c"><i class="fas fa-times-circle"></i> ${esc(d.msg)}</span>`;
      }
      actualizarTotales(nuevosItems.reduce((s,it) => s + it.precio_unitario * it.cantidad, 0));
    } catch(e) {
      mesaCupon = null;
      input.style.borderColor = '#e74c3c';
    }
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ── Listener cupón (dentro del IIFE para acceder a las funciones) ── */
  document.getElementById('mesaCuponInput').addEventListener('input', function () {
    mesaCuponOnInput(this);
  });

  /* ── Ordenar (crea nueva orden) ── */
  window.ordenar = async function () {
    if (!nuevosItems.length) return;

    const sinPrecio = nuevosItems.filter(it => it.precio_unitario <= 0);
    if (sinPrecio.length) {
      Swal.fire({
        icon:'warning', title:'Productos sin precio',
        html: sinPrecio.map(it => `<b>${esc(it.nombre)}</b>`).join('<br>') +
              '<br><small style="color:#7f8c8d;margin-top:6px;display:block">Configura el precio antes de ordenar.</small>',
      });
      return;
    }

    // Abrir popup ANTES del fetch — mientras sigue siendo acción del usuario
    const printWin = IMPRIMIR_COMANDA ? abrirVentanaTicket() : null;

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    try {
      const res  = await fetch(BP + '/ventas/guardar-orden', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
          id_mesa: ID_MESA,
          notas:   document.getElementById('posNotas').value,
          items:   nuevosItems.map(it => ({
            id_receta: it.id_receta,
            cantidad:  it.cantidad,
            precio_unitario: it.precio_unitario,
          })),
        }),
      });
      const data = await res.json();
      if (data.success) {
        if (IMPRIMIR_COMANDA && printWin) imprimirComanda(data.numero, nuevosItems, printWin);
        await Swal.fire({
          toast:true, position:'bottom-end', icon:'success',
          title: `Orden ${data.numero} enviada a cocina`,
          timer:2000, showConfirmButton:false,
        });
        window.location.reload();
      } else {
        if (printWin) printWin.close();
        Swal.fire({icon:'error', title:'Error', text:data.message});
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Ordenar';
      }
    } catch(e) {
      if (printWin) printWin.close();
      Swal.fire({icon:'error', title:'Error de red', text:e.message});
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Ordenar';
    }
  };

  <?php $bf = (int)$papel['fontSize']; ?>
  /* ── CSS compartido para tickets ──
     width:100% (no un mm fijo) para que ocupe todo el ancho de la página que
     use el driver de impresión real, sea o no que respete @page. Tamaños
     escalados según el "Tamaño de papel" configurado en Facturación. */
  const TICKET_CSS = `
    *{box-sizing:border-box;margin:0;padding:0;}
    @page{size:<?php echo $papel['pageSize']; ?>;margin:<?php echo $papel['margin']; ?>;}
    body{font-family:'Courier New',monospace;font-size:<?php echo $bf; ?>pt;width:100%;background:#fff;color:#000;
         padding:0 2mm;overflow-wrap:break-word;word-break:break-word;}
    .t-center{text-align:center;}
    .t-negocio{font-size:<?php echo $bf + 3; ?>pt;font-weight:900;margin-bottom:3px;}
    .t-titulo{font-size:<?php echo max(8, $bf - 2); ?>pt;letter-spacing:2px;margin-bottom:5px;}
    .t-sep{border:none;border-top:1px dashed #000;margin:6px 0;}
    .t-meta{font-size:<?php echo max(9, $bf - 1); ?>pt;margin:3px 0;}
    .t-total{display:flex;justify-content:space-between;font-size:<?php echo $bf + 1; ?>pt;font-weight:900;margin-top:5px;}
    table{width:100%;border-collapse:collapse;font-size:<?php echo max(9, $bf - 1); ?>pt;}
    td{vertical-align:top;}
    pre{font-family:'Courier New',monospace;font-size:<?php echo $bf; ?>pt;line-height:1.35;
        white-space:pre-wrap;word-break:break-word;margin:0;}
  `;

  /* ── Helpers de ticket en texto plano (mismo esquema que listado.php /
     ventas/index.php: columnas justificadas a TICKET_W). Evita el layout con
     <table> que en algunos drivers de impresión térmica calcula mal el ancho
     de columna y termina cortando el nombre del producto letra por letra. ── */
  function ticketCentro(txt, w) {
    txt = String(txt);
    const pad = Math.max(0, Math.floor((w - txt.length) / 2));
    return ' '.repeat(pad) + txt;
  }
  function ticketFila(izq, der, w) {
    izq = String(izq); der = String(der);
    const espacio = Math.max(1, w - izq.length - der.length);
    return izq + ' '.repeat(espacio) + der;
  }
  function ticketWrap(txt, max) {
    const words = String(txt).split(' ');
    const lines = []; let cur = '';
    words.forEach(word => {
      if ((cur + ' ' + word).trim().length <= max) cur = (cur + ' ' + word).trim();
      else { if (cur) lines.push(cur); cur = word; }
    });
    if (cur) lines.push(cur);
    return lines;
  }

  function abrirVentanaTicket() {
    return window.open('', '_blank', 'width=320,height=500,toolbar=0,scrollbars=0,status=0,menubar=0');
  }

  function abrirTicketPopup(bodyHtml, w) {
    if (!w || w.closed) w = abrirVentanaTicket();
    if (!w) { console.warn('Popup bloqueado por el navegador'); return; }
    w.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><style>${TICKET_CSS}</style></head><body>${bodyHtml}<script>window.onload=function(){window.focus();window.print();setTimeout(function(){window.close();},500);}<\/script></body></html>`);
    w.document.close();
  }

  /* ── Imprimir comanda ── */
  function imprimirComanda(numOrden, items, w) {
    const W       = TICKET_W;
    const sep     = '='.repeat(W);
    const lin     = '-'.repeat(W);
    const ahora   = new Date();
    const fecha   = ahora.toLocaleDateString('es', {day:'2-digit', month:'2-digit', year:'numeric'});
    const hora    = ahora.toLocaleTimeString('es', {hour:'2-digit', minute:'2-digit'});
    const negocio = COMERC.nombre || 'CHEFCONTROL';
    const mesaTxt = 'Mesa ' + MESA_NUM + (MESA_NOM && MESA_NOM !== 'Mesa ' + MESA_NUM ? ' · ' + MESA_NOM : '');
    const total   = items.reduce((s, it) => s + it.precio_unitario * it.cantidad, 0);

    let t = '';
    t += sep + '\n';
    t += ticketCentro(negocio, W) + '\n';
    t += sep + '\n';
    t += ticketCentro('— COMANDA —', W) + '\n';
    t += sep + '\n';
    t += 'Orden:   ' + numOrden + '\n';
    t += 'Fecha:   ' + fecha + ' ' + hora + '\n';
    t += 'Mesa:    ' + mesaTxt + '\n';
    t += lin + '\n';

    if (TICKET_ANGOSTO) {
      items.forEach(it => {
        ticketWrap(it.nombre, W).forEach(l => t += l + '\n');
        t += 'x' + it.cantidad + '\n';
        t += '$' + (it.precio_unitario * it.cantidad).toFixed(2) + '\n';
      });
    } else {
      t += ticketFila('PRODUCTO', 'CANT  SUBTOT', W) + '\n';
      t += lin + '\n';
      items.forEach(it => {
        const nomLines = ticketWrap(it.nombre, Math.max(10, W - 16));
        const sub  = '$' + (it.precio_unitario * it.cantidad).toFixed(2);
        const cant = 'x' + it.cantidad;
        t += ticketFila(nomLines[0], cant + '  ' + sub, W) + '\n';
        for (let i = 1; i < nomLines.length; i++) t += nomLines[i] + '\n';
      });
    }

    t += lin + '\n';
    t += ticketFila('TOTAL:', '$' + total.toFixed(2), W) + '\n';
    t += sep + '\n';

    abrirTicketPopup(`<pre>${esc(t)}</pre>`, w);
  }

  /* ── Imprimir factura completa al cobrar ── */
  async function imprimirFacturaMesa(cobrarData, w) {
    try {
      const ids = cobrarData.ids || [];
      const responses = await Promise.all(
        ids.map(id => fetch(BP + '/ventas/detalle/' + id).then(r => r.json()))
      );
      const todosItems = responses.flatMap(r => r.success ? r.data : []);

      const ahora   = new Date();
      const fecha   = ahora.toLocaleDateString('es', {day:'2-digit', month:'2-digit', year:'numeric'});
      const hora    = ahora.toLocaleTimeString('es', {hour:'2-digit', minute:'2-digit'});
      const negocio = COMERC.nombre || 'CHEFCONTROL';
      const eslogan = COMERC.eslogan || '';
      const rut     = COMERC.rut     || '';
      const mesaTxt = 'Mesa ' + MESA_NUM + (MESA_NOM && MESA_NOM !== 'Mesa ' + MESA_NUM ? ' · ' + MESA_NOM : '');
      const catLabels = {entrada:'Entrada',plato_fuerte:'Plato fuerte',postre:'Postre',bebida:'Bebida',snack:'Snack',otro:'Otro'};

      const rows = todosItems.map(it => `
        <tr>
          <td style="padding:3px 0;font-weight:900;white-space:nowrap;width:22px;">${it.cantidad}×</td>
          <td style="padding:3px 6px;">${esc(it.receta_nombre)}<br><span style="font-size:8pt;color:#666;">${catLabels[it.categoria]||it.categoria}</span></td>
          <td style="padding:3px 0;text-align:right;white-space:nowrap;">$${parseFloat(it.subtotal).toFixed(2)}</td>
        </tr>`).join('');

      const total   = parseFloat(cobrarData.total);
      const propina = PROPINA_ACTIVA && PROPINA_PCT > 0 ? total * PROPINA_PCT / 100 : 0;
      const metodoLabels = {efectivo:'Efectivo', tarjeta:'Tarjeta', transferencia:'Transferencia', mixto:'Mixto'};
      let metodoTxt = metodoLabels[cobrarData.metodo_pago] || '';
      if (cobrarData.metodo_pago === 'mixto') {
        const partes = [];
        if (parseFloat(cobrarData.pago_efectivo)      > 0) partes.push('Efectivo $'      + parseFloat(cobrarData.pago_efectivo).toFixed(2));
        if (parseFloat(cobrarData.pago_tarjeta)       > 0) partes.push('Tarjeta $'       + parseFloat(cobrarData.pago_tarjeta).toFixed(2));
        if (parseFloat(cobrarData.pago_transferencia) > 0) partes.push('Transferencia $' + parseFloat(cobrarData.pago_transferencia).toFixed(2));
        metodoTxt = partes.join(' + ');
      }

      const logoHtml = COMERC.logo
        ? `<div class="t-center" style="margin-bottom:4px;"><img src="${BASEURL}/assets/uploads/comercio/${COMERC.logo}" style="max-width:110px;max-height:60px;object-fit:contain;"></div>`
        : '';

      abrirTicketPopup(`
        ${logoHtml}
        <div class="t-center t-negocio">${esc(negocio)}</div>
        ${eslogan ? `<div class="t-center" style="font-size:9pt;">${esc(eslogan)}</div>` : ''}
        ${rut     ? `<div class="t-center" style="font-size:9pt;">RUT: ${esc(rut)}</div>` : ''}
        <div class="t-center t-titulo">— FACTURA —</div>
        <hr class="t-sep">
        <div class="t-meta"><b>${mesaTxt}</b></div>
        <div class="t-meta">Orden(es): <b>${esc(cobrarData.numero)}</b></div>
        <div class="t-meta">${fecha} &nbsp; ${hora}</div>
        <hr class="t-sep">
        <table>${rows}</table>
        <hr class="t-sep">
        <div class="t-total"><span>TOTAL</span><span>$${total.toFixed(2)}</span></div>
        ${propina > 0 ? `<div style="display:flex;justify-content:space-between;font-size:9pt;color:#666;margin-top:3px;"><span>Propina sugerida (${PROPINA_PCT}%)</span><span>$${propina.toFixed(2)}</span></div>` : ''}
        ${metodoTxt ? `<div class="t-meta" style="margin-top:3px;">Pago: <b>${esc(metodoTxt)}</b></div>` : ''}
        <hr class="t-sep">
        <div class="t-center" style="font-size:9pt;">¡Gracias por su visita!</div>
      `, w);
    } catch(e) {
      if (w && !w.closed) w.close();
      console.error('Error al imprimir factura:', e);
    }
  }

  /* ── Toggle cupón ── */
  window.mesaToggleCuponFn = function () {
    const wrap  = document.getElementById('mesaCuponWrap');
    const icon  = document.getElementById('mesaIconCupon');
    const link  = document.getElementById('mesaToggleCupon');
    const open  = wrap.style.display !== 'none';
    wrap.style.display = open ? 'none' : 'block';
    icon.className     = open ? 'fas fa-tag' : 'fas fa-times';
    link.style.color   = open ? '#95a5a6'   : '#e74c3c';
    if (!open) {
      document.getElementById('mesaCuponInput').focus();
    } else {
      document.getElementById('mesaCuponInput').value = '';
      document.getElementById('mesaCuponMsg').innerHTML = '';
    }
  };

  /* ── Cobrar todo ── */
  window.cobrar = async function () {
    const totalBruto = totalEx + nuevosItems.reduce((s,it) => s + it.precio_unitario*it.cantidad, 0);
    if (totalBruto <= 0) {
      Swal.fire({
        icon: 'info',
        title: 'Sin pedidos',
        text: 'Debe ordenar un pedido para poder facturar.',
        confirmButtonColor: '#2c3e50',
      });
      return;
    }
    if (nuevosItems.length > 0) {
      const ok = await Swal.fire({
        icon:'info', title:'Tienes ítems sin enviar',
        text:'Hay productos en la nueva orden que no fueron enviados a cocina. ¿Cobrar de todas formas?',
        showCancelButton:true, confirmButtonText:'Sí, cobrar todo', cancelButtonText:'Cancelar',
      });
      if (!ok.isConfirmed) return;
    }

    const cuponCodigo = document.getElementById('mesaCuponInput').value.trim();
    const cuponData   = mesaCupon; // ya validado por auto-validar
    const subtotalNuevoLocal = nuevosItems.reduce((s,it) => s + it.precio_unitario*it.cantidad, 0);
    const descuento   = calcDescuentoMesa(totalBruto, subtotalNuevoLocal);
    const totalFinal  = Math.max(0, totalBruto - descuento);

    const descHtml = cuponData
      ? `<div style="font-size:13px;color:#27ae60;margin-bottom:4px">
           <i class="fas fa-tag"></i> Descuento cupón: −$${descuento.toFixed(2)}
         </div>`
      : '';

    const pagoBtnCss = 'flex:1;padding:10px 4px;border:2px solid #e8ecf0;border-radius:10px;background:#fff;cursor:pointer;font-size:12px;color:#2c3e50;display:flex;flex-direction:column;align-items:center;gap:4px;transition:.15s';

    const ok = await Swal.fire({
      title:'¿Cobrar toda la mesa?',
      html: `${descHtml}
             <div style="font-size:34px;font-weight:900;color:#27ae60;margin:8px 0">$${totalFinal.toFixed(2)}</div>
             <div id="pagoMetodos" style="display:flex;gap:8px;justify-content:center;margin:14px 0 6px">
               <button type="button" class="pago-op" data-m="efectivo" style="${pagoBtnCss}"><i class="fas fa-money-bill-wave" style="font-size:16px"></i>Efectivo</button>
               <button type="button" class="pago-op" data-m="tarjeta" style="${pagoBtnCss}"><i class="fas fa-credit-card" style="font-size:16px"></i>Tarjeta</button>
               <button type="button" class="pago-op" data-m="transferencia" style="${pagoBtnCss}"><i class="fas fa-right-left" style="font-size:16px"></i>Transfer.</button>
               <button type="button" class="pago-op" data-m="mixto" style="${pagoBtnCss}"><i class="fas fa-layer-group" style="font-size:16px"></i>Mixto</button>
             </div>
             <div id="pagoMixtoBox" style="display:none;text-align:left;margin-top:6px">
               <label style="font-size:12px;color:#7f8c8d">Efectivo</label>
               <input type="number" id="pagoEfectivoInput" min="0" step="0.01" max="${totalFinal.toFixed(2)}" value="0"
                      style="width:100%;border:1.5px solid #e8ecf0;border-radius:8px;padding:8px 10px;font-size:14px;margin:4px 0 8px;box-sizing:border-box">
               <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                 <select id="pagoOtroMetodo" style="border:1.5px solid #e8ecf0;border-radius:8px;padding:8px 10px;font-size:13px">
                   <option value="tarjeta">Tarjeta</option>
                   <option value="transferencia">Transferencia</option>
                 </select>
                 <span style="font-size:13px;color:#7f8c8d">Resto: <b id="pagoRestoLabel" style="color:#2c3e50">$${totalFinal.toFixed(2)}</b></span>
               </div>
             </div>
             <small>Se cerrarán todas las órdenes y se descontará el stock</small>`,
      showCancelButton:true, confirmButtonColor:'#27ae60', cancelButtonColor:'#95a5a6',
      confirmButtonText:'<i class="fas fa-check"></i> Confirmar cobro', cancelButtonText:'Cancelar',
      focusConfirm:false,
      didOpen: (popup) => {
        let metodoSel = 'efectivo';
        const btns         = popup.querySelectorAll('#pagoMetodos .pago-op');
        const mixtoBox      = popup.querySelector('#pagoMixtoBox');
        const efectivoInput = popup.querySelector('#pagoEfectivoInput');
        const otroSelect     = popup.querySelector('#pagoOtroMetodo');
        const restoLabel     = popup.querySelector('#pagoRestoLabel');

        function marcar(m) {
          metodoSel = m;
          btns.forEach(b => {
            const activo = b.dataset.m === m;
            b.style.borderColor = activo ? '#27ae60' : '#e8ecf0';
            b.style.color       = activo ? '#27ae60' : '#2c3e50';
            b.style.background  = activo ? '#eafaf1' : '#fff';
          });
          mixtoBox.style.display = (m === 'mixto') ? 'block' : 'none';
        }
        function actualizarResto() {
          const ef = Math.max(0, Math.min(totalFinal, parseFloat(efectivoInput.value) || 0));
          restoLabel.textContent = '$' + (totalFinal - ef).toFixed(2);
        }
        btns.forEach(b => b.addEventListener('click', () => marcar(b.dataset.m)));
        efectivoInput.addEventListener('input', actualizarResto);
        otroSelect.addEventListener('change', actualizarResto);
        marcar('efectivo');

        popup._obtenerPago = () => {
          if (metodoSel !== 'mixto') {
            return {
              metodo_pago: metodoSel,
              pago_efectivo:      metodoSel === 'efectivo'      ? totalFinal : 0,
              pago_tarjeta:       metodoSel === 'tarjeta'       ? totalFinal : 0,
              pago_transferencia: metodoSel === 'transferencia' ? totalFinal : 0,
            };
          }
          const ef    = Math.max(0, Math.min(totalFinal, parseFloat(efectivoInput.value) || 0));
          const resto = +(totalFinal - ef).toFixed(2);
          const otro  = otroSelect.value;
          return {
            metodo_pago: 'mixto',
            pago_efectivo:      ef,
            pago_tarjeta:       otro === 'tarjeta'       ? resto : 0,
            pago_transferencia: otro === 'transferencia' ? resto : 0,
          };
        };
      },
      preConfirm: () => Swal.getPopup()._obtenerPago(),
    });
    if (!ok.isConfirmed) return;
    const pagoInfo = ok.value;

    // Abrir popup ANTES del fetch — acción de usuario todavía activa
    const printWinCobro = IMPRIMIR_FACTURA_COB ? abrirVentanaTicket() : null;

    const btn = document.getElementById('btnCobrar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Procesando...</span><strong></strong>';

    try {
      const res  = await fetch(BP + '/ventas/cobrar', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
          id_mesa:      ID_MESA,
          cupon_codigo: cuponCodigo,
          propina:      PROPINA_ACTIVA ? (parseFloat(document.getElementById('inputPropinaValor')?.value) || 0) : 0,
          metodo_pago:        pagoInfo.metodo_pago,
          pago_efectivo:      pagoInfo.pago_efectivo,
          pago_tarjeta:       pagoInfo.pago_tarjeta,
          pago_transferencia: pagoInfo.pago_transferencia,
        }),
      });
      const data = await res.json();
      if (data.success) {
        if (IMPRIMIR_FACTURA_COB && printWinCobro) await imprimirFacturaMesa(data, printWinCobro);
        await Swal.fire({
          icon:'success', title:'¡Cobrado!',
          html:`<b>${data.ordenes} orden${data.ordenes!==1?'es':''} cerrada${data.ordenes!==1?'s':''}</b><br>
                Total: <b style="color:#27ae60">$${Math.round(parseFloat(data.total)).toLocaleString('es-CO')}</b>`,
          confirmButtonColor:'#27ae60', confirmButtonText:'Volver al salón',
        });
        window.location.href = BP + '/ventas/salon';
      } else {
        if (printWinCobro && !printWinCobro.closed) printWinCobro.close();
        Swal.fire({icon:'error', title:'Error al cobrar', text:data.message});
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i><span>Cobrar todo</span><strong id="btnCobrarMonto">$'+totalFinal.toFixed(2)+'</strong>';
      }
    } catch(e) {
      if (printWinCobro && !printWinCobro.closed) printWinCobro.close();
      Swal.fire({icon:'error', title:'Error de red', text:e.message});
    }
  };

  /* ── Cancelar todas las órdenes ── */
  window.cancelar = async function () {
    const ok = await Swal.fire({
      title:'¿Cancelar todas las órdenes?',
      text:'La mesa quedará libre y no se descontará stock.',
      icon:'warning', showCancelButton:true,
      confirmButtonColor:'#e74c3c', confirmButtonText:'Sí, cancelar todo', cancelButtonText:'No',
    });
    if (!ok.isConfirmed) return;
    try {
      const res  = await fetch(BP + '/ventas/cancelar-orden', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id_mesa: ID_MESA}),
      });
      const data = await res.json();
      if (data.success) window.location.href = BP + '/ventas/salon';
      else Swal.fire({icon:'error', title:'Error', text:data.message});
    } catch(e) {
      Swal.fire({icon:'error', title:'Error de red', text:e.message});
    }
  };

  /* ── Polling en tiempo real (nuevas órdenes desde menú digital) ── */
  const ORDEN_ESTADO_LABEL = { abierta:'Pendiente', en_preparacion:'En cocina', lista:'Lista' };
  const ORDEN_ESTADO_CLS   = { abierta:'bon-pend',  en_preparacion:'bon-prep',  lista:'bon-list' };
  let _ordenesSignature = '';

  function ordenesSignature(ordenes) {
    return ordenes.map(o => o.id + ':' + o.estado + ':' + o.total).sort().join('|');
  }

  function renderOrdenesHTML(ordenes) {
    let html = '';
    for (const o of ordenes) {
      const eLbl = ORDEN_ESTADO_LABEL[o.estado] || o.estado;
      const eCls = ORDEN_ESTADO_CLS[o.estado]   || 'bon-pend';
      html += '<div class="bill-orden">'
        + '<div class="bill-orden-head">'
        +   '<span class="bon-num">'   + esc(o.numero_orden) + '</span>'
        +   '<span class="bon-badge ' + eCls + '">' + eLbl + '</span>'
        +   '<span class="bon-total">$' + parseFloat(o.total).toFixed(2) + '</span>'
        + '</div><div class="bill-orden-items">';
      for (const it of (o.items || [])) {
        html += '<div class="boi-row">'
          + '<span class="boi-qty">' + parseInt(it.cantidad) + '×</span>'
          + '<span class="boi-nom">' + esc(it.receta_nombre) + '</span>'
          + '<span class="boi-sub">$' + parseFloat(it.subtotal).toFixed(2) + '</span>'
          + '</div>';
      }
      html += '</div></div>';
    }
    return html;
  }

  async function pollOrdenesMesa() {
    try {
      const res  = await fetch(BP + '/ventas/get-orden-mesa/' + ID_MESA);
      const data = await res.json();
      if (!data.success) return;
      const ordenes  = data.data || [];
      const sig      = ordenesSignature(ordenes);
      const prevSig  = _ordenesSignature;
      if (sig === prevSig) return;
      _ordenesSignature = sig;

      // Actualizar bloque de órdenes
      document.getElementById('billOrdenes').innerHTML = renderOrdenesHTML(ordenes);

      // Actualizar totalEx y todos los totales dependientes
      totalEx = ordenes.reduce((s, o) => s + parseFloat(o.total || 0), 0);
      const subtNuevo = nuevosItems.reduce((s, it) => s + it.precio_unitario * it.cantidad, 0);
      actualizarTotales(subtNuevo);

      // Cabecera: conteo y total
      const n = ordenes.length;
      document.getElementById('billOrdenCount').textContent =
        n > 0 ? n + ' orden' + (n !== 1 ? 'es' : '') + ' activa' + (n !== 1 ? 's' : '') : 'Sin órdenes';
      document.getElementById('billHeadTotal').textContent = '$' + totalEx.toFixed(2);

      // Propina: recalcular si el usuario no la tocó
      if (PROPINA_ACTIVA && !propinaUserSet) {
        const inp = document.getElementById('inputPropinaValor');
        if (inp) inp.value = (totalEx * PROPINA_PCT / 100).toFixed(2);
      }

      // Habilitar/deshabilitar cobrar
      document.getElementById('btnCobrar').disabled = (ordenes.length === 0 && nuevosItems.length === 0);

      // Toast solo cuando llegaron más órdenes (no en cambios de estado ni primera carga)
      if (prevSig !== '' && ordenes.length > prevCount) {
        Swal.fire({ toast:true, position:'bottom-end', icon:'info',
          title:'Nueva orden recibida en la mesa', timer:2500, showConfirmButton:false });
      }
    } catch(e) {}
  }

  // Firma inicial desde los datos PHP para que el primer poll no dispare re-render falso
  let prevCount     = ORDENES_INIT.length;
  _ordenesSignature = ordenesSignature(ORDENES_INIT);
  // Lanzar polling cada 4 segundos
  setInterval(function() {
    prevCount = (document.querySelectorAll('#billOrdenes .bill-orden') || []).length;
    pollOrdenesMesa();
  }, 4000);

})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
