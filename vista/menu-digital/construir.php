<?php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Constructor de Menú — ' . htmlspecialchars($menuActual['nombre']);
$paginaActual = 'menu-digital';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$catLabels = [
    'entrada'      => 'Entradas',
    'plato_fuerte' => 'Platos fuertes',
    'postre'       => 'Postres',
    'bebida'       => 'Bebidas',
    'snack'        => 'Snacks',
    'otro'         => 'Otros',
];
$catColors = [
    'entrada'      => '#e67e22',
    'plato_fuerte' => '#e74c3c',
    'postre'       => '#9b59b6',
    'bebida'       => '#3498db',
    'snack'        => '#27ae60',
    'otro'         => '#95a5a6',
];
$catIcons = [
    'entrada'      => 'fa-leaf',
    'plato_fuerte' => 'fa-utensils',
    'postre'       => 'fa-ice-cream',
    'bebida'       => 'fa-glass-water',
    'snack'        => 'fa-cookie-bite',
    'otro'         => 'fa-tag',
];

// Pre-process for JS
$recetasJS = json_encode(array_map(function($r) use ($catColors, $catIcons) {
    $foto = null;
    if ($r['foto']) {
        $fp   = json_decode($r['foto'], true);
        $foto = is_array($fp) ? ($fp[0] ?? null) : ($r['foto'] ?: null);
    }
    return [
        'id'     => (int)$r['id'],
        'nombre' => $r['nombre'],
        'cat'    => $r['categoria'],
        'precio' => (float)$r['precio_venta'],
        'foto'   => $foto,
        'color'  => $catColors[$r['categoria']] ?? '#95a5a6',
        'icon'   => $catIcons[$r['categoria']]  ?? 'fa-tag',
    ];
}, $recetasDisponibles), JSON_UNESCAPED_UNICODE);

$itemsJS   = json_encode($itemsGuardados);
$catLabelsJ = json_encode($catLabels, JSON_UNESCAPED_UNICODE);

require_once __DIR__ . '/../complementos/header.php';
?>

<style>
.cb-page { padding: 24px 28px 56px; }

/* Header */
.cb-header {
    background: linear-gradient(135deg,#1a3a6e,#2471a3);
    border-radius: 16px; padding: 20px 24px; color:#fff;
    display:flex; align-items:center; gap:16px;
    margin-bottom:24px; box-shadow:0 4px 20px rgba(0,0,0,.12);
}
.cb-back {
    width:38px; height:38px; background:rgba(255,255,255,.18);
    border-radius:10px; display:flex; align-items:center; justify-content:center;
    color:#fff; text-decoration:none; font-size:15px; flex-shrink:0; transition:background .15s;
}
.cb-back:hover { background:rgba(255,255,255,.3); }
.cb-header-text { flex:1; min-width:0; }
.cb-header-sub  { font-size:10px; font-weight:700; opacity:.6; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }
.cb-header-text h1 { margin:0; font-size:20px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cb-header-actions { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.cb-status-badge {
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.2);
    border-radius:20px; padding:4px 12px; font-size:12px; font-weight:700; color:rgba(255,255,255,.7);
}
.cb-status-badge.active { background:rgba(52,199,89,.25); border-color:rgba(52,199,89,.4); color:#34c759; }
.cb-btn-save {
    background:#fff; color:#1a3a6e; border:none; border-radius:10px;
    padding:9px 18px; font-size:13px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; gap:7px; transition:opacity .15s;
}
.cb-btn-save:hover { opacity:.9; }
.cb-btn-save:disabled { opacity:.6; cursor:default; }

/* Layout */
.cb-builder {
    display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;
}
@media(max-width:900px){ .cb-builder { grid-template-columns:1fr; } }

/* Catalog */
.cb-catalog { background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.07); overflow:hidden; }
.cb-catalog-header {
    display:flex; align-items:center; justify-content:space-between; padding:18px 20px 0;
}
.cb-catalog-header h3 { margin:0; font-size:15px; font-weight:800; color:#1a1a2e; }
.cb-catalog-header-right { display:flex; align-items:center; gap:10px; }
.cb-catalog-count { font-size:12px; font-weight:600; color:#8e8e93; white-space:nowrap; }
.cb-btn-selall {
    border:none; background:#eaf4fb; color:#2471a3; border-radius:8px;
    padding:5px 10px; font-size:11px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; gap:5px; transition:background .15s; white-space:nowrap;
}
.cb-btn-selall:hover { background:#d6eaf8; }

.cb-search-box {
    margin:14px 20px 10px; display:flex; align-items:center; gap:9px;
    background:#f2f2f7; border-radius:10px; padding:9px 14px;
}
.cb-search-box i { color:#8e8e93; font-size:13px; }
.cb-search-box input { border:none; background:transparent; outline:none; font-size:13px; color:#1c1c1e; flex:1; width:100%; }

.cb-cats { display:flex; gap:6px; padding:0 20px 14px; flex-wrap:wrap; }
.cb-cat-chip {
    border:none; border-radius:20px; padding:5px 12px; font-size:12px; font-weight:600;
    cursor:pointer; background:#f2f2f7; color:#636366; transition:all .15s;
    display:flex; align-items:center; gap:5px;
}
.cb-cat-chip.active { background:#1a3a6e; color:#fff; }
.cb-cat-chip:not(.active):hover { background:#e5e5ea; }

.cb-products {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
    gap:12px; padding:4px 20px 20px;
    max-height:calc(100vh - 280px); overflow-y:auto;
}
.cb-products::-webkit-scrollbar { width:4px; }
.cb-products::-webkit-scrollbar-thumb { background:#d1d1d6; border-radius:4px; }

.cb-cat-group-header {
    grid-column:1/-1; display:flex; align-items:center; gap:8px;
    font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.3px;
    padding:14px 2px 4px; margin-top:2px; border-top:1px solid #f0f0f5;
}
.cb-cat-group-header:first-child { border-top:none; margin-top:0; padding-top:2px; }
.cb-cat-group-count {
    background:#f2f2f7; color:#8e8e93; border-radius:10px; font-size:11px;
    font-weight:700; padding:1px 8px; text-transform:none; letter-spacing:0;
}
.cb-cat-group-header.hidden { display:none; }

/* Product card */
.cb-product-card {
    border:2px solid #f0f0f5; border-radius:12px; overflow:hidden;
    cursor:pointer; transition:all .15s; position:relative; background:#fff;
}
.cb-product-card:hover { border-color:#2471a3; box-shadow:0 4px 12px rgba(36,113,163,.15); }
.cb-product-card.selected { border-color:#2471a3; background:#eaf4fb; }
.cb-product-card.hidden { display:none; }

.cb-prod-check {
    position:absolute; top:8px; right:8px; width:22px; height:22px; border-radius:50%;
    background:#d1d1d6; display:flex; align-items:center; justify-content:center;
    font-size:10px; color:transparent; transition:all .15s;
}
.cb-product-card.selected .cb-prod-check { background:#2471a3; color:#fff; }

.cb-prod-thumb {
    height:100px; display:flex; align-items:center; justify-content:center; font-size:32px;
}
.cb-prod-thumb img { width:100%; height:100%; object-fit:cover; }

.cb-prod-body { padding:10px 12px 12px; }
.cb-prod-name  { font-size:13px; font-weight:700; color:#1c1c1e; margin-bottom:4px; line-height:1.3; }
.cb-prod-cat   { font-size:11px; font-weight:600; display:flex; align-items:center; gap:4px; margin-bottom:6px; }
.cb-prod-price { font-size:14px; font-weight:800; color:#2471a3; }

.cb-no-results { padding:40px 20px; text-align:center; color:#8e8e93; font-size:13px; }
.cb-no-results i { font-size:36px; display:block; margin-bottom:12px; opacity:.3; }

/* Menu panel */
.cb-menu-panel {
    background:#fff; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,.07);
    position:sticky; top:20px;
}
.cb-menu-panel-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px; border-bottom:1px solid #f0f0f5;
}
.cb-menu-panel-header h3 { margin:0; font-size:15px; font-weight:800; color:#1a1a2e; display:flex; align-items:center; gap:8px; }
.cb-sel-count { background:#2471a3; color:#fff; border-radius:10px; font-size:11px; font-weight:700; padding:2px 8px; }
.cb-btn-clear {
    background:none; border:none; color:#c0392b; cursor:pointer;
    width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center;
    font-size:13px; transition:background .15s;
}
.cb-btn-clear:hover { background:#fdecea; }

.cb-menu-empty { padding:48px 20px; text-align:center; color:#8e8e93; }
.cb-menu-empty i { font-size:40px; display:block; margin-bottom:12px; opacity:.25; }
.cb-menu-empty p { font-size:13px; margin:0; line-height:1.6; }

.cb-menu-list {
    list-style:none; margin:0; padding:8px 0;
    max-height:calc(100vh - 240px); overflow-y:auto;
}
.cb-menu-list::-webkit-scrollbar { width:4px; }
.cb-menu-list::-webkit-scrollbar-thumb { background:#d1d1d6; border-radius:4px; }

.cb-menu-item {
    display:flex; align-items:center; gap:10px; padding:8px 16px;
    border-bottom:1px solid #f8f8fa; transition:background .1s;
}
.cb-menu-item:last-child { border-bottom:none; }
.cb-menu-item:hover { background:#f8f9fa; }

.cb-drag-handle { color:#c7c7cc; cursor:grab; font-size:13px; flex-shrink:0; padding:4px; }
.cb-drag-handle:active { cursor:grabbing; }

.cb-mi-thumb {
    width:38px; height:38px; border-radius:8px; flex-shrink:0; overflow:hidden;
    display:flex; align-items:center; justify-content:center; font-size:16px;
}
.cb-mi-thumb img { width:100%; height:100%; object-fit:cover; }

.cb-mi-info { flex:1; min-width:0; }
.cb-mi-name  { font-size:13px; font-weight:600; color:#1c1c1e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; }
.cb-mi-price { font-size:11px; color:#8e8e93; }

.cb-mi-remove {
    background:none; border:none; color:#c0392b; cursor:pointer;
    width:28px; height:28px; border-radius:6px; display:flex; align-items:center; justify-content:center;
    font-size:12px; opacity:.4; transition:all .15s; flex-shrink:0;
}
.cb-mi-remove:hover { opacity:1; background:#fdecea; }

.cb-sortable-ghost { opacity:.35; background:#eaf4fb; }
</style>

<div class="cb-page">

    <div class="cb-header">
        <a href="<?php echo $basePath; ?>/menu-digital" class="cb-back" title="Volver">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="cb-header-text">
            <div class="cb-header-sub">Constructor de menú</div>
            <h1><?php echo htmlspecialchars($menuActual['nombre']); ?></h1>
        </div>
        <div class="cb-header-actions">
            <span class="cb-status-badge <?php echo $menuActual['activo'] ? 'active' : ''; ?>">
                <?php echo $menuActual['activo'] ? 'Activo' : 'Inactivo'; ?>
            </span>
            <button class="cb-btn-save" id="btnGuardar" onclick="guardarMenu()">
                <i class="fas fa-floppy-disk"></i> Guardar menú
            </button>
        </div>
    </div>

    <div class="cb-builder">

        <!-- Catálogo -->
        <div class="cb-catalog">
            <div class="cb-catalog-header">
                <h3>Catálogo de productos</h3>
                <div class="cb-catalog-header-right">
                    <span class="cb-catalog-count" id="catalogCount"><?php echo count($recetasDisponibles); ?> productos</span>
                    <?php if (!empty($recetasDisponibles)): ?>
                    <button type="button" class="cb-btn-selall" id="btnSelAll" onclick="toggleSeleccionarTodos()">
                        <i class="fas fa-check-double"></i> Seleccionar todos
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cb-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar producto..." oninput="filtrar()">
            </div>

            <div class="cb-cats">
                <button class="cb-cat-chip active" data-cat="" onclick="filtrarCat(this)">
                    <i class="fas fa-th-large"></i> Todos
                </button>
                <?php
                $catsUsadas = array_unique(array_column($recetasDisponibles, 'categoria'));
                foreach ($catsUsadas as $k):
                    if (!isset($catLabels[$k])) continue;
                ?>
                <button class="cb-cat-chip" data-cat="<?php echo $k; ?>" onclick="filtrarCat(this)">
                    <i class="fas <?php echo $catIcons[$k]; ?>"></i>
                    <?php echo $catLabels[$k]; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <?php if (empty($recetasDisponibles)): ?>
            <div class="cb-no-results">
                <i class="fas fa-box-open"></i>
                No hay recetas activas.<br>
                <a href="<?php echo $basePath; ?>/recetas" style="color:#2471a3;font-weight:600;">Crear recetas</a>
            </div>
            <?php else:
                // Agrupar recetas por categoría, respetando el orden definido en $catLabels
                $porCategoria = [];
                foreach ($recetasDisponibles as $r) {
                    $porCategoria[$r['categoria']][] = $r;
                }
                $ordenCats = array_intersect_key($catLabels, $porCategoria) + $porCategoria;
            ?>
            <div class="cb-products" id="productsGrid">
                <?php foreach (array_keys($ordenCats) as $catKey):
                    if (empty($porCategoria[$catKey])) continue;
                    $catColor = $catColors[$catKey] ?? '#95a5a6';
                    $catIcon  = $catIcons[$catKey]  ?? 'fa-tag';
                    $catLabel = $catLabels[$catKey] ?? ucfirst($catKey);
                ?>
                <div class="cb-cat-group-header" data-cat="<?php echo htmlspecialchars($catKey); ?>" style="color:<?php echo $catColor; ?>">
                    <i class="fas <?php echo $catIcon; ?>"></i>
                    <?php echo htmlspecialchars($catLabel); ?>
                    <span class="cb-cat-group-count"><?php echo count($porCategoria[$catKey]); ?></span>
                </div>
                <?php foreach ($porCategoria[$catKey] as $r):
                    $fotoRaw = $r['foto'] ?? null;
                    $fp      = $fotoRaw ? json_decode($fotoRaw, true) : null;
                    $foto    = is_array($fp) ? ($fp[0] ?? null) : ($fotoRaw ?: null);
                    $color   = $catColors[$r['categoria']] ?? '#95a5a6';
                    $icon    = $catIcons[$r['categoria']]  ?? 'fa-tag';
                    $sel     = in_array((int)$r['id'], $itemsGuardados);
                ?>
                <div class="cb-product-card <?php echo $sel ? 'selected' : ''; ?>"
                     data-id="<?php echo (int)$r['id']; ?>"
                     data-nombre="<?php echo strtolower(htmlspecialchars($r['nombre'])); ?>"
                     data-cat="<?php echo htmlspecialchars($r['categoria']); ?>"
                     onclick="toggleProducto(<?php echo (int)$r['id']; ?>)">

                    <div class="cb-prod-check" id="chk-<?php echo (int)$r['id']; ?>">
                        <i class="fas fa-check"></i>
                    </div>

                    <div class="cb-prod-thumb" style="background:<?php echo $foto ? '#e8ecf0' : $color . '22'; ?>">
                        <?php if ($foto): ?>
                        <img src="<?php echo htmlspecialchars($foto); ?>" alt=""
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:32px;background:<?php echo $color . '22'; ?>">
                            <i class="fas <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                        </div>
                        <?php else: ?>
                        <i class="fas <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
                        <?php endif; ?>
                    </div>

                    <div class="cb-prod-body">
                        <div class="cb-prod-name"><?php echo htmlspecialchars($r['nombre']); ?></div>
                        <div class="cb-prod-cat" style="color:<?php echo $color; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <?php echo $catLabels[$r['categoria']] ?? ucfirst($r['categoria']); ?>
                        </div>
                        <div class="cb-prod-price">$<?php echo number_format((float)$r['precio_venta'], 0, ',', '.'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>
                <div id="noResults" class="cb-no-results" style="display:none;grid-column:1/-1;">
                    <i class="fas fa-search"></i> Sin resultados
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Panel menú -->
        <div class="cb-menu-panel">
            <div class="cb-menu-panel-header">
                <h3>Tu menú <span class="cb-sel-count" id="selCount">0</span></h3>
                <button class="cb-btn-clear" onclick="limpiarTodo()" title="Quitar todos">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div id="menuEmpty" class="cb-menu-empty">
                <i class="fas fa-clipboard-list"></i>
                <p>Selecciona productos del catálogo para armar tu menú.<br>Puedes reordenarlos arrastrando.</p>
            </div>
            <ul class="cb-menu-list" id="menuList"></ul>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const RECETAS    = <?php echo $recetasJS; ?>;
const SAVED_IDS  = <?php echo $itemsJS; ?>;
const BASE       = <?php echo json_encode($basePath); ?>;
const MENU_ID    = <?php echo (int)$menuActual['id']; ?>;
const CAT_LABELS = <?php echo $catLabelsJ; ?>;

const recMap = {};
RECETAS.forEach(r => recMap[r.id] = r);

let orden = [...SAVED_IDS];

const sortable = new Sortable(document.getElementById('menuList'), {
    animation: 150,
    handle: '.cb-drag-handle',
    ghostClass: 'cb-sortable-ghost',
    onEnd: () => {
        orden = [...document.querySelectorAll('#menuList .cb-menu-item')]
            .map(el => parseInt(el.dataset.id));
    }
});

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmt(p) {
    return '$' + Math.round(p).toLocaleString('es-CO');
}

function crearLi(r) {
    const li = document.createElement('li');
    li.className = 'cb-menu-item';
    li.dataset.id = r.id;
    const thumbInner = r.foto
        ? `<img src="${esc(r.foto)}" alt="" onerror="this.style.display='none'">`
        : `<i class="fas ${esc(r.icon)}" style="color:${esc(r.color)}"></i>`;
    li.innerHTML = `
        <span class="cb-drag-handle"><i class="fas fa-grip-vertical"></i></span>
        <div class="cb-mi-thumb" style="background:${r.foto ? '#e8ecf0' : r.color + '22'}">${thumbInner}</div>
        <div class="cb-mi-info">
            <span class="cb-mi-name">${esc(r.nombre)}</span>
            <span class="cb-mi-price">${fmt(r.precio)} · ${esc(CAT_LABELS[r.cat] || r.cat)}</span>
        </div>
        <button class="cb-mi-remove" onclick="toggleProducto(${r.id})" title="Quitar">
            <i class="fas fa-times"></i>
        </button>`;
    return li;
}

function actualizarPanel() {
    const list  = document.getElementById('menuList');
    const empty = document.getElementById('menuEmpty');
    list.innerHTML = '';
    orden.forEach(id => {
        const r = recMap[id];
        if (r) list.appendChild(crearLi(r));
    });
    document.getElementById('selCount').textContent = orden.length;
    empty.style.display = orden.length === 0 ? 'block' : 'none';
}

function toggleProducto(id) {
    const card = document.querySelector(`.cb-product-card[data-id="${id}"]`);
    const idx  = orden.indexOf(id);
    if (idx === -1) {
        orden.push(id);
        if (card) card.classList.add('selected');
    } else {
        orden.splice(idx, 1);
        if (card) card.classList.remove('selected');
    }
    actualizarPanel();
    actualizarBtnSelAll();
}

function limpiarTodo() {
    if (orden.length === 0) return;
    if (!confirm('¿Quitar todos los productos del menú?')) return;
    orden = [];
    document.querySelectorAll('.cb-product-card.selected').forEach(c => c.classList.remove('selected'));
    actualizarPanel();
    actualizarBtnSelAll();
}

// ── Seleccionar / deseleccionar todos los productos visibles (respeta filtro activo) ──
function toggleSeleccionarTodos() {
    const visibles = [...document.querySelectorAll('.cb-product-card:not(.hidden)')];
    if (!visibles.length) return;
    const todasSeleccionadas = visibles.every(c => c.classList.contains('selected'));

    visibles.forEach(card => {
        const id  = parseInt(card.dataset.id);
        const idx = orden.indexOf(id);
        if (todasSeleccionadas) {
            if (idx !== -1) { orden.splice(idx, 1); card.classList.remove('selected'); }
        } else {
            if (idx === -1) { orden.push(id); card.classList.add('selected'); }
        }
    });
    actualizarPanel();
    actualizarBtnSelAll();
}

function actualizarBtnSelAll() {
    const btn = document.getElementById('btnSelAll');
    if (!btn) return;
    const visibles = [...document.querySelectorAll('.cb-product-card:not(.hidden)')];
    const todasSeleccionadas = visibles.length > 0 && visibles.every(c => c.classList.contains('selected'));
    btn.innerHTML = todasSeleccionadas
        ? '<i class="fas fa-xmark"></i> Deseleccionar todos'
        : '<i class="fas fa-check-double"></i> Seleccionar todos';
}

let catActual = '';
let busqueda  = '';

function filtrar() {
    busqueda = document.getElementById('searchInput').value.toLowerCase().trim();
    aplicarFiltros();
}

function filtrarCat(btn) {
    document.querySelectorAll('.cb-cat-chip').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    catActual = btn.dataset.cat;
    aplicarFiltros();
}

function aplicarFiltros() {
    let visible = 0;
    document.querySelectorAll('.cb-product-card').forEach(card => {
        const ok = (!catActual || card.dataset.cat === catActual)
                && (!busqueda  || card.dataset.nombre.includes(busqueda));
        card.classList.toggle('hidden', !ok);
        if (ok) visible++;
    });
    // Ocultar encabezados de categoría que se quedaron sin productos visibles
    document.querySelectorAll('.cb-cat-group-header').forEach(header => {
        const hayVisibles = document.querySelector(`.cb-product-card[data-cat="${header.dataset.cat}"]:not(.hidden)`);
        header.classList.toggle('hidden', !hayVisibles);
    });
    document.getElementById('catalogCount').textContent = visible + ' producto' + (visible === 1 ? '' : 's');
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
    actualizarBtnSelAll();
}

async function guardarMenu() {
    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    try {
        const res = await fetch(`${BASE}/menu-digital/guardar-items/${MENU_ID}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: orden })
        });
        const d = await res.json();
        if (d.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Guardado';
            btn.style.cssText = 'background:#34c759;color:#fff;';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar menú';
                btn.style.cssText = '';
                btn.disabled = false;
            }, 2000);
        } else {
            alert('Error al guardar el menú');
            btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar menú';
            btn.disabled = false;
        }
    } catch(e) {
        alert('Error de conexión');
        btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar menú';
        btn.disabled = false;
    }
}

// Init
actualizarPanel();
actualizarBtnSelAll();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
