<?php
// vista/insumos/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo      = 'Gestión de Insumos - CHEFCONTROL';
$paginaActual = 'insumos';

$baseUrl  = Config::getBaseUrl();
$basePath = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/insumos.css">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$iconosPorCategoria = [
    'carnes'    => 'fa-drumstick-bite',
    'verduras'  => 'fa-leaf',
    'lacteos'   => 'fa-cheese',
    'granos'    => 'fa-seedling',
    'especias'  => 'fa-mortar-pestle',
    'bebidas'   => 'fa-wine-bottle',
    'otros'     => 'fa-box',
];

function getStockClass($stock, $minimo) {
    if ($stock <= 0)       return 'stock-critico';
    if ($stock <= $minimo) return 'stock-bajo';
    return 'stock-ok';
}

function getStockLabel($stock, $minimo) {
    if ($stock <= 0)       return 'Sin stock';
    if ($stock <= $minimo) return 'Stock bajo';
    return 'OK';
}
?>

<div class="insumos-container">
    <div class="insumos-header">
        <div class="insumos-title-section">
            <h1><i class="fas fa-boxes" style="color:#27ae60;margin-right:10px;"></i>Gestión de Insumos</h1>
            <p>Administra los productos e ingredientes del inventario</p>
        </div>
        <button id="openModalBtn" class="btn-open-modal">
            <i class="fas fa-plus"></i>
            Agregar Insumo
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
            <div class="stat-icon" style="color:#3498db;"><i class="fas fa-boxes"></i></div>
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
            <div class="stat-icon" style="color:#9b59b6;"><i class="fas fa-tags"></i></div>
            <div class="stat-number"><?php echo $estadisticas['categorias'] ?? 0; ?></div>
            <div class="stat-label">Categorías</div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header">
            <h2 class="table-title">Lista de Insumos</h2>
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
                            $stockClass = getStockClass($insumo['cantidad_stock'], $insumo['cantidad_minima']);
                            $stockLabel = getStockLabel($insumo['cantidad_stock'], $insumo['cantidad_minima']);
                        ?>
                        <tr>
                            <td>
                                <div class="insumo-info">
                                    <div class="insumo-icon">
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
                                    <?php echo ucfirst(htmlspecialchars($insumo['categoria'])); ?>
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
                                    <form method="POST" action="<?php echo $basePath; ?>/insumos/eliminar" class="form-eliminar" style="display:inline;">
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
                                    <i class="fas fa-boxes"></i>
                                    <h3>No hay insumos registrados</h3>
                                    <p>Comienza agregando tu primer insumo o ingrediente</p>
                                    <button id="openModalBtnEmpty" class="btn-open-modal" style="margin: 20px auto 0; width: fit-content;">
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
</div>

<!-- ===================== MODAL AGREGAR / EDITAR ===================== -->
<div class="modal-overlay" id="insumoModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-boxes"></i>
                <span id="modalTitle">Nuevo Insumo</span>
            </h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="insumoForm">
                <input type="hidden" id="insumoId" name="id" value="0">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Harina de trigo" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select id="categoria" name="categoria" class="form-control">
                            <option value="carnes">Carnes</option>
                            <option value="verduras">Verduras</option>
                            <option value="lacteos">Lácteos</option>
                            <option value="granos">Granos y cereales</option>
                            <option value="especias">Especias y condimentos</option>
                            <option value="bebidas">Bebidas</option>
                            <option value="otros" selected>Otros</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="2" placeholder="Descripción opcional del insumo"></textarea>
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
            <button type="button" class="btn-primary"   id="saveBtn">
                <i class="fas fa-save"></i> Guardar Insumo
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePath   = '<?php echo $basePath; ?>';
    const modalEl    = document.getElementById('insumoModal');
    const modalTitle = document.getElementById('modalTitle');
    const insumoId   = document.getElementById('insumoId');
    const form       = document.getElementById('insumoForm');
    const saveBtn    = document.getElementById('saveBtn');

    // Abrir modal nuevo
    document.getElementById('openModalBtn').addEventListener('click', abrirModalNuevo);
    const emptyBtn = document.getElementById('openModalBtnEmpty');
    if (emptyBtn) emptyBtn.addEventListener('click', abrirModalNuevo);

    function abrirModalNuevo() {
        modalTitle.textContent = 'Nuevo Insumo';
        insumoId.value = '0';
        form.reset();
        document.getElementById('estadoLabel').textContent = 'Activo';
        modalEl.classList.add('active');
    }

    // Cerrar modal
    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);
    function cerrarModal() { modalEl.classList.remove('active'); }

    // Click fuera del modal
    modalEl.addEventListener('click', function (e) {
        if (e.target === modalEl) cerrarModal();
    });

    // Label del switch de estado
    document.getElementById('activo').addEventListener('change', function () {
        document.getElementById('estadoLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
    });

    // Guardar insumo (crear o editar)
    saveBtn.addEventListener('click', function () {
        const formData = new FormData(form);
        const id = parseInt(insumoId.value);
        const url = id > 0
            ? basePath + '/insumos/actualizar'
            : basePath + '/insumos/crear';

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

    // Botones editar
    document.querySelectorAll('.btn-editar-insumo').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            fetch(basePath + '/insumos/get/' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { Swal.fire({ icon: 'error', title: 'Error', text: data.message }); return; }
                    const i = data.data;
                    modalTitle.textContent = 'Editar Insumo';
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

    // Switches de estado en tabla
    document.querySelectorAll('.status-switch').forEach(sw => {
        sw.addEventListener('change', function () {
            const id     = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const activo = this.checked ? 1 : 0;
            const swRef  = this;

            fetch(basePath + '/insumos/update-status', {
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

    // Confirmación eliminar
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

    // Búsqueda en tabla
    document.querySelector('.table-search').addEventListener('input', function () {
        const term = this.value.toLowerCase();
        let visible = 0;
        document.querySelectorAll('.insumos-table tbody tr').forEach(row => {
            const match = row.textContent.toLowerCase().includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        document.querySelector('.filter-count').textContent = visible;
    });
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
