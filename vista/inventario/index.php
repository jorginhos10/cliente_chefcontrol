<?php
// vista/inventario/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Inventario - CHEFCONTROL';
$paginaActual = 'inventario';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/inventario.css">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$iconosCat = [
    'carnes'   => ['icon' => 'fa-drumstick-bite', 'bg' => 'bg-carnes'],
    'verduras' => ['icon' => 'fa-leaf',           'bg' => 'bg-verduras'],
    'lacteos'  => ['icon' => 'fa-cheese',         'bg' => 'bg-lacteos'],
    'granos'   => ['icon' => 'fa-seedling',       'bg' => 'bg-granos'],
    'especias' => ['icon' => 'fa-mortar-pestle',  'bg' => 'bg-especias'],
    'bebidas'  => ['icon' => 'fa-wine-bottle',    'bg' => 'bg-bebidas'],
    'otros'    => ['icon' => 'fa-box',            'bg' => 'bg-otros'],
];

function stockClass($stock, $minimo) {
    if ($stock <= 0)       return 'stock-critico';
    if ($stock <= $minimo) return 'stock-bajo';
    return 'stock-ok';
}
function barClass($stock, $minimo) {
    if ($stock <= 0)       return 'bar-critico';
    if ($stock <= $minimo) return 'bar-bajo';
    return 'bar-ok';
}
function barWidth($stock, $minimo) {
    if ($minimo <= 0) return min(100, $stock > 0 ? 100 : 0);
    $pct = ($stock / ($minimo * 3)) * 100;
    return min(100, max(0, round($pct)));
}

$badgeTipo = [
    'entrada' => 'badge-entrada',
    'venta'   => 'badge-venta',
    'salida'  => 'badge-salida',
    'ajuste'  => 'badge-ajuste',  // compatibilidad con registros anteriores
];
$labelTipo = [
    'entrada' => 'Entrada',
    'venta'   => 'Venta',
    'salida'  => 'Salida',
    'ajuste'  => 'Ajuste',
];
?>

<div class="inventario-container">

    <!-- Header -->
    <div class="inv-header">
        <div>
            <h1><i class="fas fa-warehouse" style="color:#2c3e50;margin-right:10px;"></i>Control de Inventario</h1>
            <p>Registra entradas, salidas y ajustes de stock de tus insumos</p>
        </div>
        <button id="btnAgregarMercancia" class="btn-agregar-mercancia">
            <i class="fas fa-truck-ramp-box"></i> Agregar mercancía
        </button>
    </div>

    <!-- Stats -->
    <div class="inv-stats">
        <div class="stat-card ok">
            <div class="stat-icon" style="color:#27ae60;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['stock_ok'] ?? 0; ?></div>
            <div class="stat-label">Stock Normal</div>
        </div>
        <div class="stat-card bajo">
            <div class="stat-icon" style="color:#e67e22;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['stock_bajo'] ?? 0; ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        <div class="stat-card critico">
            <div class="stat-icon" style="color:#e74c3c;"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['sin_stock'] ?? 0; ?></div>
            <div class="stat-label">Sin Stock</div>
        </div>
        <div class="stat-card hoy">
            <div class="stat-icon" style="color:#3498db;"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-number"><?php echo $estadisticas['mov_hoy'] ?? 0; ?></div>
            <div class="stat-label">Movimientos Hoy</div>
        </div>
    </div>

    <!-- Panel principal: solo formulario -->
    <div class="inv-panel" style="display:block;">

        <!-- Formulario de movimiento -->
        <div class="inv-form-card" style="max-width:520px;margin:0 auto;">
            <div class="inv-form-header">
                <h3><i class="fas fa-exchange-alt"></i> Registrar Movimiento</h3>
            </div>
            <div class="inv-form-body">

                <!-- Tipo de movimiento -->
                <div class="form-group">
                    <label class="form-label">Tipo de movimiento</label>
                    <div class="tipo-selector" style="grid-template-columns:repeat(2,1fr);">
                        <button type="button" class="tipo-btn sel-entrada" data-tipo="entrada" id="tipoEntrada">
                            <i class="fas fa-arrow-down"></i> Entrada
                        </button>
                        <button type="button" class="tipo-btn" data-tipo="salida" id="tipoSalida">
                            <i class="fas fa-arrow-trend-down"></i> Salida
                        </button>
                    </div>
                    <p class="tipo-info" id="tipoInfo">Agrega unidades al stock existente</p>
                </div>

                <!-- Proveedor (solo en Entrada) -->
                <div class="form-group" id="grupoProveedor" style="display:none;">
                    <label class="form-label">
                        <i class="fas fa-truck" style="color:#0d8a7e;margin-right:6px;"></i>Proveedor
                    </label>
                    <div class="ac-wrap">
                        <input type="text" id="acProveedorInput" class="form-control ac-input"
                               placeholder="Busca por nombre o empresa..." autocomplete="off">
                        <input type="hidden" id="selProveedor" value="">
                        <div class="ac-list" id="acProveedorList"></div>
                    </div>
                </div>

                <!-- Insumo -->
                <div class="form-group">
                    <label class="form-label">Insumo *</label>
                    <div class="ac-wrap">
                        <input type="text" id="acInsumoInput" class="form-control ac-input"
                               placeholder="Busca un insumo..." autocomplete="off">
                        <input type="hidden" id="selInsumo" value="">
                        <div class="ac-list" id="acInsumoList"></div>
                    </div>
                </div>

                <!-- Preview del insumo seleccionado -->
                <div class="ins-preview" id="insPreview">
                    <div class="ins-prev-nombre" id="prevNombre">—</div>
                    <div class="ins-prev-stock">
                        Stock actual: <span id="prevStock">—</span>
                        <span id="prevUnidad"></span>
                    </div>
                </div>

                <!-- Cantidad -->
                <div class="form-group">
                    <label class="form-label" id="cantidadLabel">Cantidad a agregar *</label>
                    <input type="number" id="inputCantidad" class="form-control"
                           min="0.01" step="0.01" placeholder="0.00">
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label class="form-label">Motivo / Descripción</label>
                    <input type="text" id="inputDesc" class="form-control" placeholder="Ej: Compra proveedor, Merma, Inventario físico...">
                </div>

                <button type="button" class="btn-registrar" id="btnRegistrar">
                    <i class="fas fa-save"></i> Registrar Movimiento
                </button>
            </div>
        </div>
    </div>

    <!-- Historial de movimientos -->
    <div class="historial-card">
        <div class="historial-header">
            <h2><i class="fas fa-history"></i> Historial de Movimientos</h2>
            <span style="font-size:13px;color:#7f8c8d;">Últimos 30 movimientos</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="hist-table" id="tablaHistorial">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock anterior → nuevo</th>
                        <th>Motivo</th>
                        <th>Proveedor</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody id="historialBody">
                    <?php if (!empty($movimientos)): ?>
                        <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td style="white-space:nowrap;color:#7f8c8d;">
                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($mov['insumo_nombre']); ?></strong></td>
                            <td>
                                <span class="badge-tipo <?php echo $badgeTipo[$mov['tipo']] ?? ''; ?>">
                                    <?php echo $labelTipo[$mov['tipo']] ?? $mov['tipo']; ?>
                                </span>
                            </td>
                            <td style="font-weight:700;">
                                <?php echo ($mov['tipo'] === 'entrada' ? '+' : ($mov['tipo'] === 'salida' ? '-' : '=')); ?>
                                <?php echo number_format($mov['cantidad'], 2); ?>
                                <?php echo htmlspecialchars($mov['unidad_medida']); ?>
                            </td>
                            <td>
                                <span class="stock-ant"><?php echo number_format($mov['stock_anterior'], 2); ?></span>
                                <span class="stock-arrow">→</span>
                                <span class="stock-nvo <?php echo stockClass($mov['stock_nuevo'], 0); ?>">
                                    <?php echo number_format($mov['stock_nuevo'], 2); ?>
                                </span>
                            </td>
                            <td style="color:#7f8c8d;"><?php echo htmlspecialchars($mov['descripcion'] ?? '—'); ?></td>
                            <td>
                                <?php if (!empty($mov['proveedor_nombre'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;color:#0d8a7e;font-size:13px;">
                                        <i class="fas fa-truck"></i>
                                        <?php echo htmlspecialchars($mov['proveedor_nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#bdc3c7;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#7f8c8d;"><?php echo htmlspecialchars($mov['usuario_nombre'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="no-historial">
                            <i class="fas fa-history"></i> Sin movimientos registrados todavía
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"><span id="toastMsg"></span></div>

<style>
.btn-agregar-mercancia {
    background: #27ae60;
    border: none;
    color: #fff;
    padding: 10px 22px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background .15s;
    white-space: nowrap;
}
.btn-agregar-mercancia:hover { background: #219a52; }

.ac-wrap { position: relative; }
.ac-list {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0; right: 0;
    background: #fff;
    border: 2px solid #3498db;
    border-radius: 8px;
    max-height: 220px;
    overflow-y: auto;
    z-index: 999;
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
}
.ac-list.open { display: block; }
.ac-item {
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f0f2f5;
    transition: background .12s;
}
.ac-item:last-child { border-bottom: none; }
.ac-item:hover, .ac-item.active { background: #eaf4fb; }
.ac-item .ac-main { font-weight: 600; color: #2c3e50; font-size: 14px; }
.ac-item .ac-sub  { font-size: 12px; color: #95a5a6; margin-top: 2px; }
.ac-item .ac-mark { color: #2980b9; font-weight: 700; }
.ac-input { padding-right: 32px; }
.ac-empty { padding: 12px 14px; color: #bdc3c7; font-size: 13px; text-align: center; }
</style>

<script>
/* ── Datos desde PHP ───────────────────────────────────────────────── */
const __insumos = <?php echo json_encode(array_values(array_map(function($i){
    return ['id'=>(int)$i['id'],'nombre'=>$i['nombre'],
            'stock'=>(float)$i['cantidad_stock'],'unidad'=>$i['unidad_medida']];
}, $insumos))); ?>;

const __proveedores = <?php echo json_encode(array_values(array_map(function($p){
    return ['id'=>(int)$p['id'],'nombre'=>$p['nombre'],'empresa'=>$p['empresa']??''];
}, $proveedores ?? []))); ?>;

(function () {
    const basePath = '<?php echo $basePath; ?>';

    // ── Tipo de movimiento ────────────────────────────────────────────────
    const tipoInfo = {
        entrada: 'Agrega unidades al stock existente',
        salida:  'Descuenta unidades del stock por pérdida o merma',
    };
    const cantLabel = {
        entrada: 'Cantidad a agregar *',
        salida:  'Cantidad a retirar *',
    };

    let tipoActual = 'entrada';

    // Mostrar proveedor al inicio (tipo por defecto = entrada)
    document.getElementById('grupoProveedor').style.display = '';

    document.querySelectorAll('.tipo-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            setTipo(this.dataset.tipo);
        });
    });

    function setTipo(tipo) {
        tipoActual = tipo;
        document.querySelectorAll('.tipo-btn').forEach(b => {
            b.className = 'tipo-btn';
            if (b.dataset.tipo === tipo) b.classList.add('sel-' + tipo);
        });
        document.getElementById('tipoInfo').textContent      = tipoInfo[tipo];
        document.getElementById('cantidadLabel').textContent = cantLabel[tipo];

        // Proveedor: solo visible en Entrada
        const grupoProveedor = document.getElementById('grupoProveedor');
        if (tipo === 'entrada') {
            grupoProveedor.style.display = '';
        } else {
            grupoProveedor.style.display  = 'none';
            document.getElementById('selProveedor').value    = '';
            document.getElementById('acProveedorInput').value = '';
        }
    }

    // ── Autocompletado ────────────────────────────────────────────────────
    function highlight(text, query) {
        if (!query) return text;
        const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return text.replace(re, '<span class="ac-mark">$1</span>');
    }

    function initAC({ inputId, listId, hiddenId, data, labelFn, subFn, onSelect }) {
        const input  = document.getElementById(inputId);
        const list   = document.getElementById(listId);
        const hidden = document.getElementById(hiddenId);
        let activeIdx = -1;

        function getMatches(q) {
            if (!q) return data.slice(0, 8);
            const ql = q.toLowerCase();
            return data.filter(d => labelFn(d).toLowerCase().includes(ql)
                                 || (subFn && subFn(d).toLowerCase().includes(ql)));
        }

        function render(q) {
            const matches = getMatches(q);
            activeIdx = -1;
            list.innerHTML = '';
            if (!matches.length) {
                list.innerHTML = '<div class="ac-empty"><i class="fas fa-search"></i> Sin resultados</div>';
            } else {
                matches.forEach((d, i) => {
                    const el = document.createElement('div');
                    el.className = 'ac-item';
                    el.innerHTML = `<div class="ac-main">${highlight(labelFn(d), q)}</div>`
                        + (subFn && subFn(d) ? `<div class="ac-sub">${highlight(subFn(d), q)}</div>` : '');
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        pick(d);
                    });
                    list.appendChild(el);
                });
            }
            list.classList.add('open');
        }

        function pick(d) {
            hidden.value  = d.id;
            input.value   = labelFn(d);
            list.classList.remove('open');
            activeIdx = -1;
            if (onSelect) onSelect(d);
        }

        function clearSelection() {
            hidden.value = '';
            if (onSelect) onSelect(null);
        }

        input.addEventListener('focus', () => render(input.value));
        input.addEventListener('input', () => { clearSelection(); render(input.value); });
        input.addEventListener('blur',  () => setTimeout(() => list.classList.remove('open'), 150));

        input.addEventListener('keydown', e => {
            const items = list.querySelectorAll('.ac-item');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIdx = Math.min(activeIdx + 1, items.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIdx = Math.max(activeIdx - 1, -1);
            } else if (e.key === 'Enter' && activeIdx >= 0) {
                e.preventDefault();
                items[activeIdx]?.dispatchEvent(new MouseEvent('mousedown'));
                return;
            } else if (e.key === 'Escape') {
                list.classList.remove('open');
                return;
            }
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
            if (activeIdx >= 0) items[activeIdx].scrollIntoView({ block: 'nearest' });
        });
    }

    // ── Init: Proveedor ───────────────────────────────────────────────────
    initAC({
        inputId:  'acProveedorInput',
        listId:   'acProveedorList',
        hiddenId: 'selProveedor',
        data:     __proveedores,
        labelFn:  d => d.nombre,
        subFn:    d => d.empresa,
        onSelect: null,
    });

    // ── Init: Insumo ──────────────────────────────────────────────────────
    const preview = document.getElementById('insPreview');

    function mostrarPreviewInsumo(ins) {
        if (!ins) { preview.classList.remove('visible'); return; }
        document.getElementById('prevNombre').textContent = ins.nombre;
        document.getElementById('prevStock').textContent  = parseFloat(ins.stock).toFixed(2);
        document.getElementById('prevUnidad').textContent = ' ' + ins.unidad;
        preview.classList.add('visible');
    }

    initAC({
        inputId:  'acInsumoInput',
        listId:   'acInsumoList',
        hiddenId: 'selInsumo',
        data:     __insumos,
        labelFn:  d => d.nombre,
        subFn:    d => `Stock: ${parseFloat(d.stock).toFixed(2)} ${d.unidad}`,
        onSelect: mostrarPreviewInsumo,
    });

    function selectInsumo(id) {
        const ins = __insumos.find(i => i.id == id);
        if (!ins) return;
        document.getElementById('selInsumo').value   = id;
        document.getElementById('acInsumoInput').value = ins.nombre;
        mostrarPreviewInsumo(ins);
    }

    // ── Botón Agregar mercancía ───────────────────────────────────────────
    document.getElementById('btnAgregarMercancia').addEventListener('click', function () {
        setTipo('entrada');
        document.getElementById('acInsumoInput').value = '';
        document.getElementById('selInsumo').value     = '';
        mostrarPreviewInsumo(null);
        document.getElementById('inputCantidad').value = '';
        document.getElementById('inputDesc').value     = '';
        document.querySelector('.inv-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => document.getElementById('acProveedorInput').focus(), 400);
    });

    // ── Registrar movimiento ──────────────────────────────────────────────
    document.getElementById('btnRegistrar').addEventListener('click', function () {
        const id_insumo = document.getElementById('selInsumo').value;
        const cantidad  = parseFloat(document.getElementById('inputCantidad').value);
        const desc      = document.getElementById('inputDesc').value.trim();

        if (!id_insumo) { mostrarToast('Selecciona un insumo', false); return; }
        if (!cantidad || cantidad <= 0) { mostrarToast('Ingresa una cantidad válida', false); return; }

        const btn  = this;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
        btn.disabled  = true;

        const fd = new FormData();
        fd.append('id_insumo',   id_insumo);
        fd.append('tipo',        tipoActual);
        fd.append('cantidad',    cantidad);
        fd.append('descripcion', desc);
        const provId = document.getElementById('selProveedor').value;
        if (tipoActual === 'entrada' && provId) {
            fd.append('id_proveedor', provId);
        }

        fetch(basePath + '/inventario/registrar', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = orig;
                btn.disabled  = false;

                if (data.success) {
                    mostrarToast('✓ ' + data.message, true);
                    actualizarStockEnTabla(id_insumo, data.stock_nuevo);
                    agregarFilaHistorial(id_insumo, tipoActual, cantidad, data.stock_anterior, data.stock_nuevo, desc);
                    document.getElementById('inputCantidad').value = '';
                    document.getElementById('inputDesc').value     = '';
                    // Actualizar stock en el array local
                    const ins = __insumos.find(i => i.id == id_insumo);
                    if (ins) {
                        ins.stock = data.stock_nuevo;
                        document.getElementById('prevStock').textContent = parseFloat(data.stock_nuevo).toFixed(2);
                    }
                } else {
                    mostrarToast('✗ ' + data.message, false);
                }
            })
            .catch(() => {
                btn.innerHTML = orig;
                btn.disabled  = false;
                mostrarToast('Error de conexión', false);
            });
    });

    // ── Actualizar stock en array local ──────────────────────────────────
    function actualizarStockEnTabla(id_insumo, stock_nuevo) {
        const ins = __insumos.find(i => i.id == id_insumo);
        if (ins) ins.stock = stock_nuevo;
    }

    // ── Agregar fila al historial ─────────────────────────────────────────
    function agregarFilaHistorial(id_insumo, tipo, cantidad, stock_ant, stock_nvo, desc) {
        const body  = document.getElementById('historialBody');
        const vacio = body.querySelector('.no-historial');
        if (vacio) vacio.closest('tr').remove();

        const ins    = __insumos.find(i => i.id == id_insumo);
        const nombre = ins ? ins.nombre : '—';
        const unidad = ins ? ins.unidad : '';
        const signo    = tipo === 'entrada' ? '+' : '-';
        const badgeCls = { entrada:'badge-entrada', venta:'badge-venta', salida:'badge-salida', ajuste:'badge-ajuste' };
        const label    = { entrada:'Entrada', venta:'Venta', salida:'Salida', ajuste:'Ajuste' };
        const ahora    = new Date();
        const fecha    = ahora.toLocaleDateString('es', {day:'2-digit',month:'2-digit',year:'numeric'})
                       + ' ' + ahora.toLocaleTimeString('es', {hour:'2-digit',minute:'2-digit'});
        const nvoClass = parseFloat(stock_nvo) <= 0 ? 'stock-critico' : 'stock-ok';

        // Proveedor seleccionado (solo si es entrada)
        const provId  = document.getElementById('selProveedor').value;
        const provObj = __proveedores.find(p => p.id == provId);
        let proveedorHtml = '<span style="color:#bdc3c7;">—</span>';
        if (tipo === 'entrada' && provObj) {
            const provLabel = provObj.nombre + (provObj.empresa ? ' — ' + provObj.empresa : '');
            proveedorHtml = `<span style="display:inline-flex;align-items:center;gap:5px;color:#0d8a7e;font-size:13px;">
                <i class="fas fa-truck"></i>${provLabel}
            </span>`;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="white-space:nowrap;color:#7f8c8d;">${fecha}</td>
            <td><strong>${nombre}</strong></td>
            <td><span class="badge-tipo ${badgeCls[tipo]}">${label[tipo]}</span></td>
            <td style="font-weight:700;">${signo}${parseFloat(cantidad).toFixed(2)} ${unidad}</td>
            <td>
                <span class="stock-ant">${parseFloat(stock_ant).toFixed(2)}</span>
                <span class="stock-arrow">→</span>
                <span class="stock-nvo ${nvoClass}">${parseFloat(stock_nvo).toFixed(2)}</span>
            </td>
            <td style="color:#7f8c8d;">${desc || '—'}</td>
            <td>${proveedorHtml}</td>
            <td style="color:#7f8c8d;">—</td>
        `;
        body.insertBefore(tr, body.firstChild);
    }

    // ── Toast ─────────────────────────────────────────────────────────────
    let toastTimer;
    function mostrarToast(msg, ok) {
        const el = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        el.className = 'toast show ' + (ok ? 'toast-ok' : 'toast-err');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 3200);
    }


})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
