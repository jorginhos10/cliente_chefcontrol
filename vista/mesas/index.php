<?php
// vista/mesas/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Mesas - CHEFCONTROL';
$paginaActual = 'mesas';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/mesas.css">';
$jsExtra  = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$zonas = [
    'interior'  => ['label' => 'Interior',  'icon' => 'fa-house'],
    'terraza'   => ['label' => 'Terraza',   'icon' => 'fa-sun'],
    'bar'       => ['label' => 'Bar',        'icon' => 'fa-martini-glass'],
    'privado'   => ['label' => 'Privado',    'icon' => 'fa-lock'],
    'otro'      => ['label' => 'Otro',       'icon' => 'fa-circle-dot'],
];

$estados = [
    'disponible'    => ['label' => 'Disponible',    'icon' => 'fa-circle-check',   'color' => '#27ae60'],
    'ocupada'       => ['label' => 'Ocupada',        'icon' => 'fa-circle-xmark',   'color' => '#e74c3c'],
    'reservada'     => ['label' => 'Reservada',      'icon' => 'fa-circle-pause',   'color' => '#f39c12'],
    'mantenimiento' => ['label' => 'Mantenimiento',  'icon' => 'fa-circle-exclamation', 'color' => '#95a5a6'],
];
?>

<div class="mesas-container">

    <!-- Header -->
    <div class="mesas-header">
        <div class="mesas-title-section">
            <h1><i class="fas fa-chair" style="color:#16a085;margin-right:10px;"></i>Gestión de Mesas</h1>
            <p>Administra y monitorea el estado de las mesas del restaurante</p>
        </div>
        <button id="btnNuevaMesa" class="btn-nueva-mesa">
            <i class="fas fa-plus"></i> Nueva Mesa
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="mesas-stats">
        <div class="stat-card stat-total">
            <div class="stat-icon" style="color:#16a085;"><i class="fas fa-chair"></i></div>
            <div class="stat-number"><?php echo $estadisticas['total'] ?? 0; ?></div>
            <div class="stat-label">Total Mesas</div>
        </div>
        <div class="stat-card stat-disponible">
            <div class="stat-icon" style="color:#27ae60;"><i class="fas fa-circle-check"></i></div>
            <div class="stat-number"><?php echo $estadisticas['disponibles'] ?? 0; ?></div>
            <div class="stat-label">Disponibles</div>
        </div>
        <div class="stat-card stat-ocupada">
            <div class="stat-icon" style="color:#e74c3c;"><i class="fas fa-circle-xmark"></i></div>
            <div class="stat-number"><?php echo $estadisticas['ocupadas'] ?? 0; ?></div>
            <div class="stat-label">Ocupadas</div>
        </div>
        <div class="stat-card stat-reservada">
            <div class="stat-icon" style="color:#f39c12;"><i class="fas fa-circle-pause"></i></div>
            <div class="stat-number"><?php echo $estadisticas['reservadas'] ?? 0; ?></div>
            <div class="stat-label">Reservadas</div>
        </div>
        <div class="stat-card stat-mantenimiento">
            <div class="stat-icon" style="color:#95a5a6;"><i class="fas fa-wrench"></i></div>
            <div class="stat-number"><?php echo $estadisticas['mantenimiento'] ?? 0; ?></div>
            <div class="stat-label">Mantenimiento</div>
        </div>
    </div>

    <!-- Toolbar / Filtros -->
    <div class="mesas-toolbar">
        <span class="toolbar-label">Filtrar:</span>
        <div class="filter-group">
            <button class="filter-btn active" data-filter="all">Todas</button>
            <button class="filter-btn f-disponible"    data-filter="disponible">
                <i class="fas fa-circle" style="color:#27ae60;font-size:8px;"></i> Disponibles
            </button>
            <button class="filter-btn f-ocupada"       data-filter="ocupada">
                <i class="fas fa-circle" style="color:#e74c3c;font-size:8px;"></i> Ocupadas
            </button>
            <button class="filter-btn f-reservada"     data-filter="reservada">
                <i class="fas fa-circle" style="color:#f39c12;font-size:8px;"></i> Reservadas
            </button>
            <button class="filter-btn f-mantenimiento" data-filter="mantenimiento">
                <i class="fas fa-circle" style="color:#95a5a6;font-size:8px;"></i> Mantenimiento
            </button>
        </div>
        <div class="toolbar-sep"></div>
        <div class="filter-group">
            <button class="filter-btn" data-zona="">Todas las zonas</button>
            <?php foreach ($zonas as $key => $z): ?>
                <button class="filter-btn" data-zona="<?php echo $key; ?>">
                    <i class="fas <?php echo $z['icon']; ?>"></i> <?php echo $z['label']; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Grid de mesas -->
    <div class="mesas-grid" id="mesasGrid">
        <?php if (!empty($mesas)): ?>
            <?php foreach ($mesas as $mesa):
                $zonaDef  = $zonas[$mesa['zona']] ?? $zonas['otro'];
                $estadoDef= $estados[$mesa['estado']] ?? $estados['disponible'];
            ?>
            <div class="mesa-card estado-<?php echo $mesa['estado']; ?> <?php echo $mesa['activo'] ? '' : 'inactiva'; ?>"
                 data-id="<?php echo $mesa['id']; ?>"
                 data-estado="<?php echo $mesa['estado']; ?>"
                 data-zona="<?php echo $mesa['zona']; ?>"
                 data-activo="<?php echo $mesa['activo']; ?>">

                <!-- Área visual clickeable para cambiar estado -->
                <div class="mesa-visual" data-id="<?php echo $mesa['id']; ?>" data-nombre="<?php echo htmlspecialchars($mesa['nombre'] ?: 'Mesa ' . $mesa['numero']); ?>" title="Clic para cambiar estado">
                    <div class="mesa-shape">
                        <span class="mesa-numero"><?php echo $mesa['numero']; ?></span>
                    </div>
                    <div class="mesa-nombre-visual">
                        <?php echo $mesa['nombre'] ? htmlspecialchars($mesa['nombre']) : 'Mesa ' . $mesa['numero']; ?>
                    </div>
                    <span class="mesa-estado-badge"><?php echo $estadoDef['label']; ?></span>
                    <span class="click-hint"><i class="fas fa-hand-pointer"></i> clic para cambiar</span>
                </div>

                <!-- Info -->
                <div class="mesa-info">
                    <div class="mesa-meta">
                        <span class="mesa-zona-badge">
                            <i class="fas <?php echo $zonaDef['icon']; ?>"></i>
                            <?php echo $zonaDef['label']; ?>
                        </span>
                        <span class="mesa-capacidad">
                            <i class="fas fa-users"></i>
                            <?php echo $mesa['capacidad']; ?> pers.
                        </span>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mesa-footer">
                    <label class="switch-mesa" title="<?php echo $mesa['activo'] ? 'Activa' : 'Inactiva'; ?>">
                        <input type="checkbox" class="toggle-activo"
                               data-id="<?php echo $mesa['id']; ?>"
                               <?php echo $mesa['activo'] ? 'checked' : ''; ?>>
                        <span class="sw"></span>
                    </label>
                    <div class="mesa-acciones">
                        <button class="btn-mesa-accion btn-mesa-editar"
                                data-id="<?php echo $mesa['id']; ?>"
                                title="Editar mesa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-mesa-accion btn-mesa-eliminar"
                                data-id="<?php echo $mesa['id']; ?>"
                                data-nombre="<?php echo htmlspecialchars($mesa['nombre'] ?: 'Mesa ' . $mesa['numero']); ?>"
                                title="Eliminar mesa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-mesas">
                <i class="fas fa-chair"></i>
                <h3>No hay mesas registradas</h3>
                <p>Comienza agregando las mesas de tu restaurante</p>
                <button id="btnNuevaMesaEmpty" class="btn-nueva-mesa" style="margin:0 auto;">
                    <i class="fas fa-plus"></i> Agregar Primera Mesa
                </button>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ══════════════ MODAL CREAR / EDITAR ══════════════ -->
<div class="modal-overlay" id="modalMesa">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-chair"></i> <span id="modalTitulo">Nueva Mesa</span></h2>
            <button class="btn-close-modal" id="btnCerrarModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formMesa">
                <input type="hidden" id="mesaId" name="id" value="0">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Número de Mesa *</label>
                        <input type="number" id="numero" name="numero" class="form-control" min="1" placeholder="Ej: 1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre / Alias</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Ventana, VIP...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Capacidad (personas)</label>
                        <input type="number" id="capacidad" name="capacidad" class="form-control" min="1" value="4">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Zona</label>
                        <select id="zona" name="zona" class="form-control">
                            <?php foreach ($zonas as $key => $z): ?>
                                <option value="<?php echo $key; ?>"><?php echo $z['label']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Estado inicial</label>
                    <select id="estado" name="estado" class="form-control">
                        <?php foreach ($estados as $key => $e): ?>
                            <option value="<?php echo $key; ?>"><?php echo $e['label']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="switch-container">
                        <span class="switch-label">Mesa activa:</span>
                        <label class="switch">
                            <input type="checkbox" id="activo" name="activo" checked>
                            <span class="slider"></span>
                        </label>
                        <span id="activoLabel">Activa</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" id="btnCancelar">Cancelar</button>
            <button class="btn-primary"   id="btnGuardar">
                <i class="fas fa-save"></i> Guardar Mesa
            </button>
        </div>
    </div>
</div>

<!-- ══════════════ MODAL CAMBIAR ESTADO ══════════════ -->
<div class="modal-overlay" id="modalEstado">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header" style="background:linear-gradient(135deg,#0e7c67,#16a085);">
            <h2 class="modal-title"><i class="fas fa-exchange-alt"></i> Cambiar Estado</h2>
            <button class="btn-close-modal" id="btnCerrarEstado">&times;</button>
        </div>
        <div class="modal-body">
            <div class="mesa-estado-info" id="estadoMesaInfo">
                <i class="fas fa-info-circle" style="color:#16a085;"></i>
                <span id="estadoMesaNombre">Mesa</span>
            </div>
            <div class="estado-opciones">
                <div class="estado-opcion op-disponible"    data-estado="disponible">
                    <i class="fas fa-circle-check"></i> Disponible
                </div>
                <div class="estado-opcion op-ocupada"       data-estado="ocupada">
                    <i class="fas fa-circle-xmark"></i> Ocupada
                </div>
                <div class="estado-opcion op-reservada"     data-estado="reservada">
                    <i class="fas fa-circle-pause"></i> Reservada
                </div>
                <div class="estado-opcion op-mantenimiento" data-estado="mantenimiento">
                    <i class="fas fa-wrench"></i> Mantenimiento
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" id="btnCancelarEstado">Cancelar</button>
            <button class="btn-primary"   id="btnConfirmarEstado">
                <i class="fas fa-check"></i> Confirmar
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';

    // ─── Referencias DOM ────────────────────────────────────────
    const modalMesa    = document.getElementById('modalMesa');
    const modalEstado  = document.getElementById('modalEstado');
    const formMesa     = document.getElementById('formMesa');
    const mesaId       = document.getElementById('mesaId');
    const modalTitulo  = document.getElementById('modalTitulo');

    // ─── Abrir/cerrar modal de mesa ────────────────────────────
    function abrirModalNueva() {
        modalTitulo.textContent = 'Nueva Mesa';
        mesaId.value = '0';
        formMesa.reset();
        document.getElementById('capacidad').value = '4';
        document.getElementById('activoLabel').textContent = 'Activa';
        modalMesa.classList.add('active');
    }

    document.getElementById('btnNuevaMesa').addEventListener('click', abrirModalNueva);
    const btnEmpty = document.getElementById('btnNuevaMesaEmpty');
    if (btnEmpty) btnEmpty.addEventListener('click', abrirModalNueva);

    document.getElementById('btnCerrarModal').addEventListener('click', () => modalMesa.classList.remove('active'));
    document.getElementById('btnCancelar').addEventListener('click',    () => modalMesa.classList.remove('active'));
    modalMesa.addEventListener('click', e => { if (e.target === modalMesa) modalMesa.classList.remove('active'); });

    document.getElementById('activo').addEventListener('change', function () {
        document.getElementById('activoLabel').textContent = this.checked ? 'Activa' : 'Inactiva';
    });

    // ─── Editar mesa ────────────────────────────────────────────
    document.querySelectorAll('.btn-mesa-editar').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            fetch(basePath + '/mesas/get/' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { Swal.fire({ icon: 'error', title: 'Error', text: data.message }); return; }
                    const m = data.data;
                    modalTitulo.textContent = 'Editar Mesa';
                    mesaId.value = m.id;
                    document.getElementById('numero').value    = m.numero;
                    document.getElementById('nombre').value    = m.nombre ?? '';
                    document.getElementById('capacidad').value = m.capacidad;
                    document.getElementById('zona').value      = m.zona;
                    document.getElementById('estado').value    = m.estado;
                    const chk = document.getElementById('activo');
                    chk.checked = m.activo == 1;
                    document.getElementById('activoLabel').textContent = chk.checked ? 'Activa' : 'Inactiva';
                    modalMesa.classList.add('active');
                });
        });
    });

    // ─── Guardar mesa ───────────────────────────────────────────
    document.getElementById('btnGuardar').addEventListener('click', function () {
        const numero = parseInt(document.getElementById('numero').value);
        if (!numero || numero < 1) {
            Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'El número de mesa es obligatorio.' });
            return;
        }

        const formData = new FormData(formMesa);
        const id       = parseInt(mesaId.value);
        const url      = id > 0 ? basePath + '/mesas/actualizar' : basePath + '/mesas/crear';
        const btn      = this;
        const orig     = btn.innerHTML;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        btn.disabled  = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = orig;
                btn.disabled  = false;
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1400, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                btn.innerHTML = orig;
                btn.disabled  = false;
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' });
            });
    });

    // ─── Eliminar mesa ──────────────────────────────────────────
    document.querySelectorAll('.btn-mesa-eliminar').forEach(btn => {
        btn.addEventListener('click', function () {
            const id     = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            Swal.fire({
                title: '¿Eliminar mesa?',
                html: `¿Seguro que deseas eliminar <strong>${nombre}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor:  '#95a5a6',
                confirmButtonText:  'Sí, eliminar',
                cancelButtonText:   'Cancelar',
            }).then(r => {
                if (!r.isConfirmed) return;
                fetch(basePath + '/mesas/eliminar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Eliminada', text: data.message, timer: 1300, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                });
            });
        });
    });

    // ─── Toggle activo ──────────────────────────────────────────
    document.querySelectorAll('.toggle-activo').forEach(sw => {
        sw.addEventListener('change', function () {
            const id     = this.getAttribute('data-id');
            const activo = this.checked ? 1 : 0;
            const ref    = this;
            fetch(basePath + '/mesas/update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, activo })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    ref.checked = !ref.checked;
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                } else {
                    const card = ref.closest('.mesa-card');
                    if (card) {
                        activo ? card.classList.remove('inactiva') : card.classList.add('inactiva');
                        card.setAttribute('data-activo', activo);
                    }
                }
            })
            .catch(() => { ref.checked = !ref.checked; });
        });
    });

    // ─── Modal cambiar estado ───────────────────────────────────
    let estadoMesaId       = null;
    let estadoSeleccionado = null;

    document.querySelectorAll('.mesa-visual').forEach(vis => {
        vis.addEventListener('click', function () {
            estadoMesaId = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const card   = this.closest('.mesa-card');
            estadoSeleccionado = card ? card.getAttribute('data-estado') : null;

            document.getElementById('estadoMesaNombre').textContent = nombre;

            // Marcar opción actual
            document.querySelectorAll('.estado-opcion').forEach(op => {
                op.classList.toggle('selected', op.getAttribute('data-estado') === estadoSeleccionado);
            });

            modalEstado.classList.add('active');
        });
    });

    document.querySelectorAll('.estado-opcion').forEach(op => {
        op.addEventListener('click', function () {
            document.querySelectorAll('.estado-opcion').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            estadoSeleccionado = this.getAttribute('data-estado');
        });
    });

    document.getElementById('btnCerrarEstado').addEventListener('click',   () => modalEstado.classList.remove('active'));
    document.getElementById('btnCancelarEstado').addEventListener('click',  () => modalEstado.classList.remove('active'));
    modalEstado.addEventListener('click', e => { if (e.target === modalEstado) modalEstado.classList.remove('active'); });

    document.getElementById('btnConfirmarEstado').addEventListener('click', function () {
        if (!estadoMesaId || !estadoSeleccionado) return;

        const btn  = this;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled  = true;

        fetch(basePath + '/mesas/cambiar-estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: estadoMesaId, estado: estadoSeleccionado })
        })
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = orig;
            btn.disabled  = false;
            if (data.success) {
                modalEstado.classList.remove('active');
                // Actualizar tarjeta sin recargar
                const card = document.querySelector(`.mesa-card[data-id="${estadoMesaId}"]`);
                if (card) actualizarTarjetaEstado(card, data.estado);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        })
        .catch(() => {
            btn.innerHTML = orig;
            btn.disabled  = false;
            Swal.fire({ icon: 'error', title: 'Error de red' });
        });
    });

    const etiquetasEstado = {
        disponible:    'Disponible',
        ocupada:       'Ocupada',
        reservada:     'Reservada',
        mantenimiento: 'Mantenimiento',
    };

    function actualizarTarjetaEstado(card, nuevoEstado) {
        // Quitar clases de estado previas
        ['disponible','ocupada','reservada','mantenimiento'].forEach(e => card.classList.remove('estado-' + e));
        card.classList.add('estado-' + nuevoEstado);
        card.setAttribute('data-estado', nuevoEstado);

        // Actualizar badge y texto dentro del visual
        const badge = card.querySelector('.mesa-estado-badge');
        if (badge) badge.textContent = etiquetasEstado[nuevoEstado] || nuevoEstado;

        // Aplicar filtro activo si hay uno
        aplicarFiltros();
    }

    // ─── Filtros ─────────────────────────────────────────────────
    let filtroEstado = 'all';
    let filtroZona   = '';

    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtroEstado = this.getAttribute('data-filter');
            aplicarFiltros();
        });
    });

    document.querySelectorAll('[data-zona]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-zona]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtroZona = this.getAttribute('data-zona');
            aplicarFiltros();
        });
    });

    function aplicarFiltros() {
        document.querySelectorAll('.mesa-card').forEach(card => {
            const matchEstado = filtroEstado === 'all' || card.getAttribute('data-estado') === filtroEstado;
            const matchZona   = !filtroZona            || card.getAttribute('data-zona')   === filtroZona;
            card.style.display = (matchEstado && matchZona) ? '' : 'none';
        });
    }

})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
