<?php
// vista/categorias/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Categorías de Recetas - CHEFCONTROL';
$paginaActual = 'categorias';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/insumos.css">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$iconosDisponibles = [
    'fa-utensils', 'fa-drumstick-bite', 'fa-ice-cream', 'fa-wine-glass', 'fa-cookie-bite',
    'fa-bowl-food', 'fa-pizza-slice', 'fa-burger', 'fa-fish', 'fa-egg', 'fa-mug-hot',
    'fa-glass-martini-alt', 'fa-leaf', 'fa-bread-slice', 'fa-cheese', 'fa-tag',
];
?>

<div class="insumos-container">
    <div class="insumos-header">
        <div class="insumos-title-section">
            <h1><i class="fas fa-tags" style="color:#9b59b6;margin-right:10px;"></i>Categorías de Recetas</h1>
            <p>Administra las categorías que se usan para clasificar el menú</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="<?php echo $basePath; ?>/recetas" class="btn-open-modal" style="background:#7f8c8d;">
                <i class="fas fa-book-open"></i> Ver recetas
            </a>
            <button id="openModalBtn" class="btn-open-modal">
                <i class="fas fa-plus"></i> Nueva Categoría
            </button>
        </div>
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

    <div class="table-section">
        <div class="table-header">
            <h2 class="table-title">Lista de Categorías</h2>
            <span class="table-info">
                <i class="fas fa-filter"></i>
                Total: <span class="filter-count"><?php echo count($categorias ?? []); ?></span>
            </span>
        </div>

        <div class="table-container">
            <table class="insumos-table">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Recetas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categorias)): ?>
                        <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td>
                                <div class="insumo-info">
                                    <div class="insumo-icon">
                                        <i class="fas <?php echo htmlspecialchars($cat['icono']); ?>"></i>
                                    </div>
                                    <div>
                                        <span class="insumo-nombre"><?php echo htmlspecialchars($cat['nombre']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-cat"><?php echo (int)$cat['total_recetas']; ?> receta(s)</span>
                            </td>
                            <td>
                                <div class="acciones-container">
                                    <button class="btn-accion btn-info btn-editar-cat"
                                            data-id="<?php echo $cat['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($cat['nombre']); ?>"
                                            data-icono="<?php echo htmlspecialchars($cat['icono']); ?>"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="<?php echo $basePath; ?>/categorias/eliminar" class="form-eliminar" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                        <button type="submit"
                                                class="btn-accion btn-eliminar"
                                                data-nombre="<?php echo htmlspecialchars($cat['nombre']); ?>"
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
                            <td colspan="3">
                                <div class="no-insumos">
                                    <i class="fas fa-tags"></i>
                                    <h3>No hay categorías registradas</h3>
                                    <p>Crea tu primera categoría para clasificar las recetas del menú</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== MODAL AGREGAR / EDITAR ===================== -->
<div class="modal-overlay" id="catModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-tags"></i>
                <span id="modalTitle">Nueva Categoría</span>
            </h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="catForm">
                <input type="hidden" id="catId" name="id" value="0">

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Ensaladas" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Icono</label>
                    <select id="icono" name="icono" class="form-control">
                        <?php foreach ($iconosDisponibles as $ic): ?>
                            <option value="<?php echo $ic; ?>"><?php echo $ic; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text"><i class="fas fa-eye"></i> Vista previa: <i class="fas" id="iconoPreview"></i></small>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelBtn">Cancelar</button>
            <button type="button" class="btn-primary" id="saveBtn">
                <i class="fas fa-save"></i> Guardar Categoría
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePath   = '<?php echo $basePath; ?>';
    const modalEl    = document.getElementById('catModal');
    const modalTitle = document.getElementById('modalTitle');
    const catId      = document.getElementById('catId');
    const form       = document.getElementById('catForm');
    const saveBtn    = document.getElementById('saveBtn');
    const iconoSel   = document.getElementById('icono');
    const iconoPrev  = document.getElementById('iconoPreview');

    function actualizarPreview() { iconoPrev.className = 'fas ' + iconoSel.value; }
    iconoSel.addEventListener('change', actualizarPreview);

    document.getElementById('openModalBtn').addEventListener('click', abrirModalNuevo);

    function abrirModalNuevo() {
        modalTitle.textContent = 'Nueva Categoría';
        catId.value = '0';
        form.reset();
        actualizarPreview();
        modalEl.classList.add('active');
    }

    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);
    function cerrarModal() { modalEl.classList.remove('active'); }

    modalEl.addEventListener('click', function (e) {
        if (e.target === modalEl) cerrarModal();
    });

    saveBtn.addEventListener('click', function () {
        const formData = new FormData(form);
        const id  = parseInt(catId.value);
        const url = id > 0 ? basePath + '/categorias/actualizar' : basePath + '/categorias/crear';

        if (!formData.get('nombre').trim()) {
            Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'El nombre de la categoría es obligatorio.' });
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

    document.querySelectorAll('.btn-editar-cat').forEach(btn => {
        btn.addEventListener('click', function () {
            modalTitle.textContent = 'Editar Categoría';
            catId.value = this.getAttribute('data-id');
            document.getElementById('nombre').value = this.getAttribute('data-nombre');
            iconoSel.value = this.getAttribute('data-icono');
            actualizarPreview();
            modalEl.classList.add('active');
        });
    });

    document.querySelectorAll('.form-eliminar').forEach(f => {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombre = this.querySelector('button').getAttribute('data-nombre');
            Swal.fire({
                title: '¿Eliminar categoría?',
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
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
