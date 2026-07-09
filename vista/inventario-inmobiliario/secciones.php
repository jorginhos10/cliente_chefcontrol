<?php
// vista/inventario-inmobiliario/secciones.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Secciones - Inventario Inmobiliario - CHEFCONTROL';
$paginaActual = 'inventario-inmobiliario';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/inventario_inmobiliario.css?v=3">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$iconosDisponibles = [
    'fa-door-open', 'fa-kitchen-set', 'fa-utensils', 'fa-mug-hot', 'fa-wine-glass',
    'fa-cash-register', 'fa-boxes-stacked', 'fa-warehouse', 'fa-couch', 'fa-store',
    'fa-building', 'fa-restroom', 'fa-parking', 'fa-blender', 'fa-fire-burner',
    'fa-snowflake', 'fa-broom', 'fa-toolbox', 'fa-users', 'fa-stairs',
];
?>

<div class="inm-container">

    <div class="inm-header">
        <div class="inm-title-section">
            <h1>
                <a href="<?php echo $basePath; ?>/inventario-inmobiliario" style="color:#8d6e63;margin-right:10px;text-decoration:none;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <i class="fas fa-door-open" style="color:#8d6e63;margin-right:10px;"></i>Secciones del Local
            </h1>
            <p>Organiza el inventario inmobiliario por partes del local (ej: Cocina) y sus subsecciones</p>
        </div>
        <button id="openModalBtn" class="btn-open-modal">
            <i class="fas fa-plus"></i> Nueva Sección
        </button>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="sec-lista">
        <?php if (!empty($arbol)): ?>
            <?php foreach ($arbol as $seccion): ?>
                <div class="sec-card">
                    <div class="sec-card-row">
                        <div class="sec-info">
                            <span class="sec-icon"><i class="fas <?php echo htmlspecialchars($seccion['icono']); ?>"></i></span>
                            <span class="sec-nombre"><?php echo htmlspecialchars($seccion['nombre']); ?></span>
                        </div>
                        <div class="sec-acciones">
                            <button class="btn-accion btn-info btn-add-sub"
                                    data-parent-id="<?php echo $seccion['id']; ?>"
                                    data-parent-nombre="<?php echo htmlspecialchars($seccion['nombre']); ?>"
                                    title="Agregar subsección">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="btn-accion btn-editar btn-editar-sec"
                                    data-id="<?php echo $seccion['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($seccion['nombre']); ?>"
                                    data-icono="<?php echo htmlspecialchars($seccion['icono']); ?>"
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="<?php echo $basePath; ?>/inventario-inmobiliario/eliminar-seccion" class="form-eliminar-sec">
                                <input type="hidden" name="id" value="<?php echo $seccion['id']; ?>">
                                <button type="submit" class="btn-accion btn-eliminar"
                                        data-nombre="<?php echo htmlspecialchars($seccion['nombre']); ?>" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($seccion['subsecciones'])): ?>
                        <div class="sec-subsecciones">
                            <?php foreach ($seccion['subsecciones'] as $sub): ?>
                                <div class="sec-card-row sec-sub-row">
                                    <div class="sec-info">
                                        <span class="sec-icon sec-icon-sm"><i class="fas <?php echo htmlspecialchars($sub['icono']); ?>"></i></span>
                                        <span class="sec-nombre"><?php echo htmlspecialchars($sub['nombre']); ?></span>
                                    </div>
                                    <div class="sec-acciones">
                                        <button class="btn-accion btn-editar btn-editar-sec"
                                                data-id="<?php echo $sub['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($sub['nombre']); ?>"
                                                data-icono="<?php echo htmlspecialchars($sub['icono']); ?>"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="<?php echo $basePath; ?>/inventario-inmobiliario/eliminar-seccion" class="form-eliminar-sec">
                                            <input type="hidden" name="id" value="<?php echo $sub['id']; ?>">
                                            <button type="submit" class="btn-accion btn-eliminar"
                                                    data-nombre="<?php echo htmlspecialchars($sub['nombre']); ?>" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="inm-vacio">
                <i class="fas fa-door-open"></i>
                <h3>No hay secciones registradas</h3>
                <p>Crea las partes de tu local (ej: Cocina, Salón, Bodega) para organizar el inventario</p>
                <button id="openModalBtnEmpty" class="btn-open-modal" style="margin:20px auto 0;width:fit-content;">
                    <i class="fas fa-plus"></i> Crear Primera Sección
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ======================== MODAL SECCIÓN ======================== -->
<div class="modal-overlay" id="secModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-door-open"></i>
                <span id="secModalTitle">Nueva Sección</span>
            </h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="secForm">
                <input type="hidden" id="secId" name="id" value="0">
                <input type="hidden" id="parentId" name="parent_id" value="">
                <input type="hidden" id="icono" name="icono" value="fa-door-open">

                <p id="parentInfo" class="form-text" style="display:none;margin-bottom:14px;"></p>

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" id="secNombre" name="nombre" class="form-control" placeholder="Ej: Cocina, Salón, Bodega..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">Ícono</label>
                    <div class="icon-picker">
                        <?php foreach ($iconosDisponibles as $ic): ?>
                            <div class="icon-picker-item" data-icono="<?php echo $ic; ?>">
                                <i class="fas <?php echo $ic; ?>"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelBtn">Cancelar</button>
            <button type="button" class="btn-primary" id="saveBtn">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<style>
.sec-lista { display: flex; flex-direction: column; gap: 14px; }
.sec-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #f0f0f0; overflow: hidden; }
.sec-card-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; gap: 12px; flex-wrap: wrap; }
.sec-sub-row { padding: 10px 18px 10px 40px; border-top: 1px solid #f5f5f5; background: #fafafa; }
.sec-subsecciones { display: flex; flex-direction: column; }
.sec-info { display: flex; align-items: center; gap: 12px; min-width: 0; }
.sec-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #4e342e, #8d6e63); color: white;
    display: flex; align-items: center; justify-content: center; font-size: 17px;
}
.sec-icon-sm { width: 32px; height: 32px; font-size: 14px; background: #ddd0c8; color: #4e342e; }
.sec-nombre { font-weight: 700; color: #2c3e50; font-size: 15px; }
.sec-acciones { display: flex; gap: 6px; }
.btn-info { background: #eaf4fb; color: #2471a3; }

.icon-picker { display: grid; grid-template-columns: repeat(auto-fill, minmax(44px, 1fr)); gap: 8px; }
.icon-picker-item {
    width: 44px; height: 44px; border-radius: 8px; border: 2px solid #e0e0e0;
    display: flex; align-items: center; justify-content: center; cursor: pointer;
    color: #7f8c8d; font-size: 16px; transition: all 0.15s;
}
.icon-picker-item:hover { border-color: #8d6e63; color: #8d6e63; }
.icon-picker-item.selected { border-color: #8d6e63; background: #8d6e63; color: white; }
</style>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';

    const modalEl   = document.getElementById('secModal');
    const form      = document.getElementById('secForm');
    const modalTitle = document.getElementById('secModalTitle');
    const secId     = document.getElementById('secId');
    const parentId  = document.getElementById('parentId');
    const iconoInput = document.getElementById('icono');
    const parentInfo = document.getElementById('parentInfo');

    document.getElementById('openModalBtn').addEventListener('click', () => abrirModal());
    const emptyBtn = document.getElementById('openModalBtnEmpty');
    if (emptyBtn) emptyBtn.addEventListener('click', () => abrirModal());

    document.querySelectorAll('.btn-add-sub').forEach(btn => {
        btn.addEventListener('click', function () {
            abrirModal(this.getAttribute('data-parent-id'), this.getAttribute('data-parent-nombre'));
        });
    });

    document.querySelectorAll('.btn-editar-sec').forEach(btn => {
        btn.addEventListener('click', function () {
            modalTitle.textContent = 'Editar Sección';
            secId.value    = this.getAttribute('data-id');
            parentId.value = '';
            parentInfo.style.display = 'none';
            document.getElementById('secNombre').value = this.getAttribute('data-nombre');
            seleccionarIcono(this.getAttribute('data-icono'));
            modalEl.classList.add('active');
        });
    });

    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);

    function abrirModal(parentIdVal, parentNombre) {
        modalTitle.textContent = parentIdVal ? 'Nueva Subsección' : 'Nueva Sección';
        form.reset();
        secId.value    = '0';
        parentId.value = parentIdVal || '';
        if (parentIdVal) {
            parentInfo.style.display = 'block';
            parentInfo.innerHTML = 'Subsección de <strong>' + parentNombre + '</strong>';
        } else {
            parentInfo.style.display = 'none';
        }
        seleccionarIcono('fa-door-open');
        modalEl.classList.add('active');
    }

    function cerrarModal() { modalEl.classList.remove('active'); }

    function seleccionarIcono(icono) {
        iconoInput.value = icono;
        document.querySelectorAll('.icon-picker-item').forEach(el => {
            el.classList.toggle('selected', el.getAttribute('data-icono') === icono);
        });
    }

    document.querySelectorAll('.icon-picker-item').forEach(el => {
        el.addEventListener('click', function () { seleccionarIcono(this.getAttribute('data-icono')); });
    });

    document.getElementById('saveBtn').addEventListener('click', function () {
        const nombre = document.getElementById('secNombre').value.trim();
        if (!nombre) {
            Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'El nombre es obligatorio.' });
            return;
        }

        const id      = parseInt(secId.value);
        const url     = basePath + '/inventario-inmobiliario/' + (id > 0 ? 'actualizar-seccion' : 'crear-seccion');
        const formData = new FormData(form);
        const saveBtn = document.getElementById('saveBtn');
        const orig    = saveBtn.innerHTML;

        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled  = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                saveBtn.innerHTML = orig;
                saveBtn.disabled  = false;
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                saveBtn.innerHTML = orig;
                saveBtn.disabled  = false;
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' });
            });
    });

    document.querySelectorAll('.form-eliminar-sec').forEach(f => {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombre = this.querySelector('button').getAttribute('data-nombre');
            Swal.fire({
                title: '¿Eliminar sección?',
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
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
