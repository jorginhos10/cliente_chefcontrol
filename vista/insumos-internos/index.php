<?php
// vista/insumos-internos/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Insumos de Uso Interno - CHEFCONTROL';
$paginaActual = 'insumos-internos';

$baseUrl  = Config::getBaseUrl();
$basePath = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/insumos.css">'
          . '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/inventario.css">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$categoriasInterno = [
    'limpieza'   => ['label' => 'Limpieza',    'icon' => 'fa-spray-can-sparkles'],
    'papeleria'  => ['label' => 'Papelería',   'icon' => 'fa-file-lines'],
    'oficina'    => ['label' => 'Oficina',     'icon' => 'fa-briefcase'],
    'empaques'   => ['label' => 'Empaques',    'icon' => 'fa-box-open'],
    'otros'      => ['label' => 'Otros',       'icon' => 'fa-box'],
];
$iconosPorCategoria = array_map(fn($c) => $c['icon'], $categoriasInterno);

function iiStockClass($stock, $minimo) {
    if ($stock <= 0)       return 'stock-critico';
    if ($stock <= $minimo) return 'stock-bajo';
    return 'stock-ok';
}
function iiStockLabel($stock, $minimo) {
    if ($stock <= 0)       return 'Sin stock';
    if ($stock <= $minimo) return 'Stock bajo';
    return 'OK';
}
?>

<div class="insumos-container">
    <div class="insumos-header">
        <div class="insumos-title-section">
            <h1><i class="fas fa-broom" style="color:#8e44ad;margin-right:10px;"></i>Insumos de Uso Interno</h1>
            <p>Productos que consume el negocio pero que no forman parte de las recetas (limpieza, papelería, etc.)</p>
        </div>
        <button id="openModalBtn" class="btn-open-modal" style="background:#8e44ad;">
            <i class="fas fa-plus"></i>
            Agregar Insumo Interno
        </button>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="insumos-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color:#8e44ad;"><i class="fas fa-broom"></i></div>
            <div class="stat-number"><?php echo $estadisticas['total'] ?? 0; ?></div>
            <div class="stat-label">Total Insumos</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#27ae60;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['activos'] ?? 0; ?></div>
            <div class="stat-label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#e67e22;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['stock_bajo'] ?? 0; ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#3498db;"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-number"><?php echo $estadisticas['mov_hoy'] ?? 0; ?></div>
            <div class="stat-label">Movimientos Hoy</div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header">
            <h2 class="table-title">Catálogo de Insumos Internos</h2>
            <div class="table-actions">
                <input type="text" class="table-search" placeholder="Buscar insumo...">
                <span class="table-info">
                    <i class="fas fa-filter"></i>
                    Mostrando: <span class="filter-count"><?php echo count($insumos ?? []); ?></span>
                </span>
            </div>
        </div>

        <div class="table-container">
            <table class="insumos-table">
                <thead>
                    <tr>
                        <th>Insumo</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Unidad</th>
                        <th>Precio Unit.</th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($insumos)): ?>
                        <?php foreach ($insumos as $insumo):
                            $icono = $iconosPorCategoria[$insumo['categoria']] ?? 'fa-box';
                            $stockClass = iiStockClass($insumo['cantidad_stock'], $insumo['cantidad_minima']);
                            $stockLabel = iiStockLabel($insumo['cantidad_stock'], $insumo['cantidad_minima']);
                        ?>
                        <tr>
                            <td>
                                <div class="insumo-info">
                                    <div class="insumo-icon" style="background:#8e44ad22;color:#8e44ad;">
                                        <i class="fas <?php echo $icono; ?>"></i>
                                    </div>
                                    <div>
                                        <span class="insumo-nombre"><?php echo htmlspecialchars($insumo['nombre']); ?></span>
                                        <?php if (!empty($insumo['descripcion'])): ?>
                                            <span class="insumo-desc"><?php echo htmlspecialchars(mb_strimwidth($insumo['descripcion'], 0, 40, '...')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-cat">
                                    <?php echo htmlspecialchars($categoriasInterno[$insumo['categoria']]['label'] ?? ucfirst($insumo['categoria'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="stock-badge <?php echo $stockClass; ?>">
                                    <?php echo number_format($insumo['cantidad_stock'], 2); ?> — <?php echo $stockLabel; ?>
                                </span>
                            </td>
                            <td>
                                <span class="unidad-tag"><?php echo htmlspecialchars($insumo['unidad_medida']); ?></span>
                            </td>
                            <td>
                                $<?php echo number_format($insumo['precio_unitario'], 2); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($insumo['proveedor_nombre'] ?? '—'); ?>
                            </td>
                            <td>
                                <label class="switch-table">
                                    <input type="checkbox"
                                           class="status-switch"
                                           data-id="<?php echo $insumo['id']; ?>"
                                           data-nombre="<?php echo htmlspecialchars($insumo['nombre']); ?>"
                                           <?php echo $insumo['activo'] ? 'checked' : ''; ?>>
                                    <span class="slider-table"></span>
                                </label>
                            </td>
                            <td>
                                <div class="acciones-container">
                                    <button class="btn-accion btn-info btn-editar-insumo"
                                            data-id="<?php echo $insumo['id']; ?>"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="<?php echo $basePath; ?>/insumos-internos/eliminar" class="form-eliminar" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $insumo['id']; ?>">
                                        <button type="submit"
                                                class="btn-accion btn-eliminar"
                                                data-nombre="<?php echo htmlspecialchars($insumo['nombre']); ?>"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="no-insumos">
                                    <i class="fas fa-broom"></i>
                                    <h3>No hay insumos internos registrados</h3>
                                    <p>Agrega productos de limpieza, papelería u otros insumos que uses internamente</p>
                                    <button id="openModalBtnEmpty" class="btn-open-modal" style="margin: 20px auto 0; width: fit-content; background:#8e44ad;">
                                        <i class="fas fa-plus"></i> Agregar Primer Insumo
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($insumos)): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:15px 20px;color:#7f8c8d;font-size:14px;border-top:2px solid #f8f9fa;">
            <div><i class="fas fa-info-circle"></i> Mostrando <strong><?php echo count($insumos); ?></strong> insumo(s)</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Registrar movimiento -->
    <div class="inv-form-card" style="max-width:520px;margin:24px auto 0;">
        <div class="inv-form-header">
            <h3><i class="fas fa-exchange-alt"></i> Registrar Movimiento</h3>
        </div>
        <div class="inv-form-body">

            <div class="form-group">
                <label class="form-label">Tipo de movimiento</label>
                <div class="tipo-selector" style="grid-template-columns:repeat(2,1fr);">
                    <button type="button" class="tipo-btn sel-entrada" data-tipo="entrada" id="iiTipoEntrada">
                        <i class="fas fa-arrow-down"></i> Entrada
                    </button>
                    <button type="button" class="tipo-btn" data-tipo="salida" id="iiTipoSalida">
                        <i class="fas fa-arrow-trend-down"></i> Salida
                    </button>
                </div>
                <p class="tipo-info" id="iiTipoInfo">Agrega unidades al stock existente</p>
            </div>

            <div class="form-group" id="iiGrupoProveedor" style="display:none;">
                <label class="form-label">
                    <i class="fas fa-truck" style="color:#8e44ad;margin-right:6px;"></i>Proveedor
                </label>
                <div class="ac-wrap">
                    <input type="text" id="iiAcProveedorInput" class="form-control ac-input"
                           placeholder="Busca por nombre o empresa..." autocomplete="off">
                    <input type="hidden" id="iiSelProveedor" value="">
                    <div class="ac-list" id="iiAcProveedorList"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Insumo *</label>
                <div class="ac-wrap">
                    <input type="text" id="iiAcInsumoInput" class="form-control ac-input"
                           placeholder="Busca un insumo interno..." autocomplete="off">
                    <input type="hidden" id="iiSelInsumo" value="">
                    <div class="ac-list" id="iiAcInsumoList"></div>
                </div>
            </div>

            <div class="ins-preview" id="iiInsPreview">
                <div class="ins-prev-nombre" id="iiPrevNombre">—</div>
                <div class="ins-prev-stock">
                    Stock actual: <span id="iiPrevStock">—</span>
                    <span id="iiPrevUnidad"></span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" id="iiCantidadLabel">Cantidad a agregar *</label>
                <input type="number" id="iiInputCantidad" class="form-control"
                       min="0.01" step="0.01" placeholder="0.00">
            </div>

            <div class="form-group">
                <label class="form-label">Motivo / Descripción</label>
                <input type="text" id="iiInputDesc" class="form-control" placeholder="Ej: Compra proveedor, Consumo interno...">
            </div>

            <button type="button" class="btn-registrar" id="iiBtnRegistrar" style="background:#8e44ad;">
                <i class="fas fa-save"></i> Registrar Movimiento
            </button>
        </div>
    </div>

    <!-- Historial de movimientos -->
    <div class="historial-card" style="margin-top:24px;">
        <div class="historial-header">
            <h2><i class="fas fa-history"></i> Historial de Movimientos</h2>
            <span style="font-size:13px;color:#7f8c8d;">Últimos 30 movimientos</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="hist-table" id="iiTablaHistorial">
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
                <tbody id="iiHistorialBody">
                    <?php if (!empty($movimientos)): ?>
                        <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td style="white-space:nowrap;color:#7f8c8d;">
                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($mov['insumo_nombre']); ?></strong></td>
                            <td>
                                <span class="badge-tipo <?php echo $mov['tipo'] === 'entrada' ? 'badge-entrada' : 'badge-salida'; ?>">
                                    <?php echo $mov['tipo'] === 'entrada' ? 'Entrada' : 'Salida'; ?>
                                </span>
                            </td>
                            <td style="font-weight:700;">
                                <?php echo $mov['tipo'] === 'entrada' ? '+' : '-'; ?>
                                <?php echo number_format($mov['cantidad'], 2); ?>
                                <?php echo htmlspecialchars($mov['unidad_medida']); ?>
                            </td>
                            <td>
                                <span class="stock-ant"><?php echo number_format($mov['stock_anterior'], 2); ?></span>
                                <span class="stock-arrow">→</span>
                                <span class="stock-nvo <?php echo $mov['stock_nuevo'] <= 0 ? 'stock-critico' : 'stock-ok'; ?>">
                                    <?php echo number_format($mov['stock_nuevo'], 2); ?>
                                </span>
                            </td>
                            <td style="color:#7f8c8d;"><?php echo htmlspecialchars($mov['descripcion'] ?? '—'); ?></td>
                            <td>
                                <?php if (!empty($mov['proveedor_nombre'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;color:#8e44ad;font-size:13px;">
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

<!-- ===================== MODAL AGREGAR / EDITAR ===================== -->
<div class="modal-overlay" id="insumoModal">
    <div class="modal">
        <div class="modal-header" style="background:linear-gradient(135deg,#6c3483,#8e44ad);">
            <h2 class="modal-title">
                <i class="fas fa-broom"></i>
                <span id="modalTitle">Nuevo Insumo Interno</span>
            </h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="insumoForm">
                <input type="hidden" id="insumoId" name="id" value="0">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Jabón desinfectante" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select id="categoria" name="categoria" class="form-control">
                            <?php foreach ($categoriasInterno as $slug => $cat): ?>
                                <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $slug === 'otros' ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="2" placeholder="Descripción opcional"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Unidad de medida</label>
                        <select id="unidad_medida" name="unidad_medida" class="form-control">
                            <option value="kg">Kilogramo (kg)</option>
                            <option value="g">Gramo (g)</option>
                            <option value="L">Litro (L)</option>
                            <option value="mL">Mililitro (mL)</option>
                            <option value="unidad" selected>Unidad</option>
                            <option value="paquete">Paquete</option>
                            <option value="caja">Caja</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio unitario ($)</label>
                        <input type="number" id="precio_unitario" name="precio_unitario" class="form-control" min="0" step="0.01" value="0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Cantidad mínima (alerta)</label>
                    <input type="number" id="cantidad_minima" name="cantidad_minima" class="form-control" min="0" step="0.01" value="0">
                    <small class="form-text">Se marcará como stock bajo si cae por debajo de este valor</small>
                </div>

                <div class="form-group">
                    <label class="switch-container">
                        <span class="switch-label">Activo:</span>
                        <label class="switch">
                            <input type="checkbox" id="activo" name="activo" checked>
                            <span class="slider"></span>
                        </label>
                        <span id="estadoLabel">Activo</span>
                    </label>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelBtn">Cancelar</button>
            <button type="button" class="btn-primary" id="saveBtn" style="background:#8e44ad;">
                <i class="fas fa-save"></i> Guardar Insumo
            </button>
        </div>
    </div>
</div>

<!-- Toast (registrar movimiento) -->
<div class="toast" id="iiToast"><span id="iiToastMsg"></span></div>

<script>
const __iiInsumos = <?php echo json_encode(array_values(array_map(function($i){
    return ['id'=>(int)$i['id'],'nombre'=>$i['nombre'],
            'stock'=>(float)$i['cantidad_stock'],'unidad'=>$i['unidad_medida']];
}, $insumos ?? []))); ?>;

const __iiProveedores = <?php echo json_encode(array_values(array_map(function($p){
    return ['id'=>(int)$p['id'],'nombre'=>$p['nombre'],'empresa'=>$p['empresa']??''];
}, $proveedores ?? []))); ?>;

document.addEventListener('DOMContentLoaded', function () {
    const basePath   = '<?php echo $basePath; ?>';
    const modalEl    = document.getElementById('insumoModal');
    const modalTitle = document.getElementById('modalTitle');
    const insumoId   = document.getElementById('insumoId');
    const form       = document.getElementById('insumoForm');
    const saveBtn    = document.getElementById('saveBtn');

    // ── CRUD de insumos internos ───────────────────────────────────────────
    document.getElementById('openModalBtn').addEventListener('click', abrirModalNuevo);
    const emptyBtn = document.getElementById('openModalBtnEmpty');
    if (emptyBtn) emptyBtn.addEventListener('click', abrirModalNuevo);

    function abrirModalNuevo() {
        modalTitle.textContent = 'Nuevo Insumo Interno';
        insumoId.value = '0';
        form.reset();
        document.getElementById('estadoLabel').textContent = 'Activo';
        modalEl.classList.add('active');
    }

    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);
    function cerrarModal() { modalEl.classList.remove('active'); }

    modalEl.addEventListener('click', function (e) {
        if (e.target === modalEl) cerrarModal();
    });

    document.getElementById('activo').addEventListener('change', function () {
        document.getElementById('estadoLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
    });

    saveBtn.addEventListener('click', function () {
        const formData = new FormData(form);
        const id = parseInt(insumoId.value);
        const url = id > 0
            ? basePath + '/insumos-internos/actualizar'
            : basePath + '/insumos-internos/crear';

        if (!formData.get('nombre').trim()) {
            Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'El nombre del insumo es obligatorio.' });
            return;
        }

        const orig = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                saveBtn.innerHTML = orig;
                saveBtn.disabled = false;
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                saveBtn.innerHTML = orig;
                saveBtn.disabled = false;
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' });
            });
    });

    document.querySelectorAll('.btn-editar-insumo').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            fetch(basePath + '/insumos-internos/get/' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { Swal.fire({ icon: 'error', title: 'Error', text: data.message }); return; }
                    const i = data.data;
                    modalTitle.textContent = 'Editar Insumo Interno';
                    insumoId.value = i.id;
                    document.getElementById('nombre').value          = i.nombre;
                    document.getElementById('descripcion').value     = i.descripcion    ?? '';
                    document.getElementById('categoria').value       = i.categoria;
                    document.getElementById('unidad_medida').value   = i.unidad_medida;
                    document.getElementById('cantidad_minima').value = i.cantidad_minima;
                    document.getElementById('precio_unitario').value = i.precio_unitario;
                    const activoCheck = document.getElementById('activo');
                    activoCheck.checked = i.activo == 1;
                    document.getElementById('estadoLabel').textContent = activoCheck.checked ? 'Activo' : 'Inactivo';
                    modalEl.classList.add('active');
                });
        });
    });

    document.querySelectorAll('.status-switch').forEach(sw => {
        sw.addEventListener('change', function () {
            const id     = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const activo = this.checked ? 1 : 0;
            const swRef  = this;

            fetch(basePath + '/insumos-internos/update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, activo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Estado actualizado', text: nombre, timer: 1200, showConfirmButton: false });
                } else {
                    swRef.checked = !swRef.checked;
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => { swRef.checked = !swRef.checked; });
        });
    });

    document.querySelectorAll('.form-eliminar').forEach(f => {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombre = this.querySelector('button').getAttribute('data-nombre');
            Swal.fire({
                title: '¿Eliminar insumo?',
                html: `¿Seguro que deseas eliminar <strong>${nombre}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor:  '#95a5a6',
                confirmButtonText:  'Sí, eliminar',
                cancelButtonText:   'Cancelar'
            }).then(r => { if (r.isConfirmed) this.submit(); });
        });
    });

    const searchInput = document.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            let visible = 0;
            document.querySelectorAll('.insumos-table tbody tr').forEach(row => {
                const match = row.textContent.toLowerCase().includes(term);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            document.querySelector('.filter-count').textContent = visible;
        });
    }

    // ── Registrar movimiento ────────────────────────────────────────────────
    const tipoInfo = {
        entrada: 'Agrega unidades al stock existente',
        salida:  'Descuenta unidades del stock por consumo interno',
    };
    const cantLabel = {
        entrada: 'Cantidad a agregar *',
        salida:  'Cantidad a retirar *',
    };
    let iiTipoActual = 'entrada';

    document.getElementById('iiGrupoProveedor').style.display = '';

    document.querySelectorAll('#iiTipoEntrada, #iiTipoSalida').forEach(btn => {
        btn.addEventListener('click', function () { setIiTipo(this.dataset.tipo); });
    });

    function setIiTipo(tipo) {
        iiTipoActual = tipo;
        document.getElementById('iiTipoEntrada').className = 'tipo-btn' + (tipo === 'entrada' ? ' sel-entrada' : '');
        document.getElementById('iiTipoSalida').className  = 'tipo-btn' + (tipo === 'salida'  ? ' sel-salida'  : '');
        document.getElementById('iiTipoInfo').textContent      = tipoInfo[tipo];
        document.getElementById('iiCantidadLabel').textContent = cantLabel[tipo];

        const grupoProveedor = document.getElementById('iiGrupoProveedor');
        if (tipo === 'entrada') {
            grupoProveedor.style.display = '';
        } else {
            grupoProveedor.style.display = 'none';
            document.getElementById('iiSelProveedor').value = '';
            document.getElementById('iiAcProveedorInput').value = '';
        }
    }

    function iiHighlight(text, query) {
        if (!query) return text;
        const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')', 'gi');
        return text.replace(re, '<span class="ac-mark">$1</span>');
    }

    function iiInitAC({ inputId, listId, hiddenId, data, labelFn, subFn, onSelect }) {
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
                matches.forEach(d => {
                    const el = document.createElement('div');
                    el.className = 'ac-item';
                    el.innerHTML = `<div class="ac-main">${iiHighlight(labelFn(d), q)}</div>`
                        + (subFn && subFn(d) ? `<div class="ac-sub">${iiHighlight(subFn(d), q)}</div>` : '');
                    el.addEventListener('mousedown', e => { e.preventDefault(); pick(d); });
                    list.appendChild(el);
                });
            }
            list.classList.add('open');
        }

        function pick(d) {
            hidden.value = d.id;
            input.value  = labelFn(d);
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
            if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, items.length - 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, -1); }
            else if (e.key === 'Enter' && activeIdx >= 0) { e.preventDefault(); items[activeIdx]?.dispatchEvent(new MouseEvent('mousedown')); return; }
            else if (e.key === 'Escape') { list.classList.remove('open'); return; }
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
            if (activeIdx >= 0) items[activeIdx].scrollIntoView({ block: 'nearest' });
        });
    }

    iiInitAC({
        inputId: 'iiAcProveedorInput', listId: 'iiAcProveedorList', hiddenId: 'iiSelProveedor',
        data: __iiProveedores, labelFn: d => d.nombre, subFn: d => d.empresa, onSelect: null,
    });

    const iiPreview = document.getElementById('iiInsPreview');
    function mostrarIiPreview(ins) {
        if (!ins) { iiPreview.classList.remove('visible'); return; }
        document.getElementById('iiPrevNombre').textContent = ins.nombre;
        document.getElementById('iiPrevStock').textContent  = parseFloat(ins.stock).toFixed(2);
        document.getElementById('iiPrevUnidad').textContent = ' ' + ins.unidad;
        iiPreview.classList.add('visible');
    }

    iiInitAC({
        inputId: 'iiAcInsumoInput', listId: 'iiAcInsumoList', hiddenId: 'iiSelInsumo',
        data: __iiInsumos, labelFn: d => d.nombre,
        subFn: d => `Stock: ${parseFloat(d.stock).toFixed(2)} ${d.unidad}`,
        onSelect: mostrarIiPreview,
    });

    document.getElementById('iiBtnRegistrar').addEventListener('click', function () {
        const id_insumo = document.getElementById('iiSelInsumo').value;
        const cantidad  = parseFloat(document.getElementById('iiInputCantidad').value);
        const desc      = document.getElementById('iiInputDesc').value.trim();

        if (!id_insumo) { iiToast('Selecciona un insumo', false); return; }
        if (!cantidad || cantidad <= 0) { iiToast('Ingresa una cantidad válida', false); return; }

        const btn  = this;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
        btn.disabled  = true;

        const fd = new FormData();
        fd.append('id_insumo',   id_insumo);
        fd.append('tipo',        iiTipoActual);
        fd.append('cantidad',    cantidad);
        fd.append('descripcion', desc);
        const provId = document.getElementById('iiSelProveedor').value;
        if (iiTipoActual === 'entrada' && provId) fd.append('id_proveedor', provId);

        fetch(basePath + '/insumos-internos/registrar-movimiento', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = orig;
                btn.disabled  = false;
                if (data.success) {
                    iiToast('✓ ' + data.message, true);
                    setTimeout(() => location.reload(), 900);
                } else {
                    iiToast('✗ ' + data.message, false);
                }
            })
            .catch(() => {
                btn.innerHTML = orig;
                btn.disabled  = false;
                iiToast('Error de conexión', false);
            });
    });

    let iiToastTimer;
    function iiToast(msg, ok) {
        const el = document.getElementById('iiToast');
        document.getElementById('iiToastMsg').textContent = msg;
        el.className = 'toast show ' + (ok ? 'toast-ok' : 'toast-err');
        clearTimeout(iiToastTimer);
        iiToastTimer = setTimeout(() => el.classList.remove('show'), 3200);
    }
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
