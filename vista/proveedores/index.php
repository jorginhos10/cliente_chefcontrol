<?php
require_once __DIR__ . '/../../config/security.php';

$titulo = 'Proveedores - CHEFCONTROL';
$paginaActual = 'proveedores';

$baseUrl  = Config::getBaseUrl();
$basePath = Config::getBasePath();

$cssExtra  = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/proveedores.css">';
$jsExtra   = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$jsExtra  .= '<script src="' . $baseUrl . '/assets/js/proveedores.js"></script>';

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="proveedores-container">
    <div class="proveedores-header">
        <div class="proveedores-title-section">
            <h1>Gestión de Proveedores</h1>
            <p>Administra los proveedores del negocio</p>
        </div>
        <button id="openModalBtn" class="btn-open-modal">
            <i class="fas fa-truck"></i>
            Agregar Nuevo Proveedor
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
    <div class="proveedores-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color: #3498db;">
                <i class="fas fa-truck-loading"></i>
            </div>
            <div class="stat-number"><?php echo count($proveedores ?? []); ?></div>
            <div class="stat-label">Total Proveedores</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: #2ecc71;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-number"><?php echo count(array_filter($proveedores ?? [], fn($p) => ($p['activo'] ?? 1) == 1)); ?></div>
            <div class="stat-label">Proveedores Activos</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: #e74c3c;">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-number"><?php echo count(array_filter($proveedores ?? [], fn($p) => ($p['categoria'] ?? '') == 'A')); ?></div>
            <div class="stat-label">Categoría A</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: #f39c12;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-number"><?php echo count(array_filter($proveedores ?? [], fn($p) => ($p['categoria'] ?? '') == 'B')); ?></div>
            <div class="stat-label">Categoría B</div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header">
            <h2 class="table-title">Lista de Proveedores</h2>
            <div class="table-actions">
                <input type="text" class="table-search" placeholder="Buscar proveedor...">
                <span class="table-info">
                    <i class="fas fa-filter"></i>
                    Mostrando: <span class="filter-count"><?php echo count($proveedores ?? []); ?></span>
                </span>
            </div>
        </div>

        <div class="table-container">
            <table class="proveedores-table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Empresa</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proveedores)): ?>
                    <?php foreach ($proveedores as $proveedor):
                        $activo = $proveedor['activo'] ?? 1;
                    ?>
                    <tr>
                        <td>
                            <div class="proveedor-info">
                                <div class="proveedor-logo">
                                    <?php if (!empty($proveedor['foto']) && $proveedor['foto'] !== 'default.png'): ?>
                                        <img src="<?php echo $baseUrl; ?>/assets/media/proveedores/<?php echo htmlspecialchars($proveedor['foto']); ?>"
                                             class="logo-img"
                                             onerror="this.src='<?php echo $baseUrl; ?>/assets/media/proveedores/default.png'"
                                             alt="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <img class="avatar-proveedor"
                                                 src="<?php echo $baseUrl; ?>/assets/media/proveedores/default.png"
                                                 alt="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="proveedor-details">
                                    <span class="proveedor-nombre"><?php echo htmlspecialchars($proveedor['nombre']); ?></span>
                                    <?php if (!empty($proveedor['nit_rut'])): ?>
                                        <span class="proveedor-nit">NIT/RUT: <?php echo htmlspecialchars($proveedor['nit_rut']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($proveedor['empresa'] ?? ''); ?></td>
                        <td>
                            <a href="tel:<?php echo htmlspecialchars($proveedor['telefono']); ?>"
                               style="color: #3498db; text-decoration: none;">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($proveedor['telefono']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($proveedor['direccion'] ?? ''); ?></td>
                        <td>
                            <span class="badge badge-categoria-<?php echo htmlspecialchars($proveedor['categoria'] ?? ''); ?>">
                                <?php
                                switch ($proveedor['categoria'] ?? '') {
                                    case 'A': echo 'A - Principal';   break;
                                    case 'B': echo 'B - Secundario';  break;
                                    case 'C': echo 'C - Ocasional';   break;
                                    default:  echo htmlspecialchars($proveedor['categoria'] ?? '-');
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <label class="switch-table">
                                <input type="checkbox"
                                       class="status-switch"
                                       data-proveedor-id="<?php echo $proveedor['id']; ?>"
                                       data-proveedor-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>"
                                       <?php echo $activo ? 'checked' : ''; ?>>
                                <span class="slider-table"></span>
                            </label>
                        </td>
                        <td>
                            <div class="acciones-container">
                                <button class="btn-accion btn-info btn-ver-proveedor"
                                        data-proveedor-id="<?php echo $proveedor['id']; ?>"
                                        title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-accion btn-editar btn-editar-proveedor"
                                        data-proveedor-id="<?php echo $proveedor['id']; ?>"
                                        title="Editar proveedor">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST"
                                      action="<?php echo $basePath; ?>/proveedores/eliminar"
                                      class="form-eliminar">
                                    <input type="hidden" name="id" value="<?php echo $proveedor['id']; ?>">
                                    <button type="submit"
                                            class="btn-accion btn-eliminar"
                                            data-proveedor-id="<?php echo $proveedor['id']; ?>"
                                            data-proveedor-nombre="<?php echo htmlspecialchars($proveedor['nombre']); ?>"
                                            title="Eliminar proveedor">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="no-proveedores">
                                <i class="fas fa-truck-loading"></i>
                                <h3>No hay proveedores registrados</h3>
                                <p>Comienza agregando un nuevo proveedor</p>
                                <button id="openModalBtnEmpty" class="btn-open-modal" style="margin-top: 20px;">
                                    <i class="fas fa-truck"></i>
                                    Agregar Primer Proveedor
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($proveedores)): ?>
        <div class="table-footer">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:15px;color:#7f8c8d;font-size:14px;border-top:2px solid #f8f9fa;">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Mostrando <strong><?php echo count($proveedores); ?></strong> proveedor(es)
                </div>
                <div>
                    <span class="badge badge-categoria-A" style="margin-right:5px;">
                        <?php echo count(array_filter($proveedores, fn($p) => ($p['categoria'] ?? '') == 'A')); ?> A
                    </span>
                    <span class="badge badge-categoria-B" style="margin-right:5px;">
                        <?php echo count(array_filter($proveedores, fn($p) => ($p['categoria'] ?? '') == 'B')); ?> B
                    </span>
                    <span class="badge badge-categoria-C">
                        <?php echo count(array_filter($proveedores, fn($p) => ($p['categoria'] ?? '') == 'C')); ?> C
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal agregar / editar proveedor -->
<div class="modal-overlay" id="proveedorModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-truck"></i>
                <span id="modalTitle">Nuevo Proveedor</span>
            </h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="proveedorForm" enctype="multipart/form-data">
                <input type="hidden" id="proveedorId" name="id" value="0">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre del Proveedor *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="empresa" class="form-label">Empresa</label>
                        <input type="text" id="empresa" name="empresa" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="nit_rut" class="form-label">NIT/RUT</label>
                        <input type="text" id="nit_rut" name="nit_rut" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea id="direccion" name="direccion" class="form-control" rows="2"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select id="categoria" name="categoria" class="form-control">
                            <option value="A">A - Principal</option>
                            <option value="B">B - Secundario</option>
                            <option value="C">C - Ocasional</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="foto" class="form-label">Foto / Logo</label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                    <small class="form-text">Formatos: JPG, PNG, GIF. Máximo 2 MB</small>
                </div>

                <div class="form-group">
                    <label for="observacion" class="form-label">Observaciones</label>
                    <textarea id="observacion" name="observacion" class="form-control" rows="3"></textarea>
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
            <button type="button" class="btn-primary" id="saveBtn">
                <i class="fas fa-save"></i> Guardar Proveedor
            </button>
        </div>
    </div>
</div>

<!-- Modal ver detalles -->
<div class="modal-overlay" id="detalleProveedorModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-eye"></i>
                <span id="detalleProveedorTitle">Detalles del Proveedor</span>
            </h2>
            <button class="btn-close-modal" id="closeDetalleModalBtn">&times;</button>
        </div>
        <div class="modal-body">
            <div id="detalleProveedorContent"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="closeDetalleBtn">Cerrar</button>
            <button type="button" class="btn-primary" id="descargarDetalleBtn">
                <i class="fas fa-download"></i> Descargar Información
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const basePath = '<?php echo $basePath; ?>';
    const baseUrl  = '<?php echo $baseUrl; ?>';

    /* ── Abrir modal (crear) ── */
    document.getElementById('openModalBtn').addEventListener('click', abrirModalNuevo);

    const openEmpty = document.getElementById('openModalBtnEmpty');
    if (openEmpty) openEmpty.addEventListener('click', abrirModalNuevo);

    function abrirModalNuevo() {
        document.getElementById('modalTitle').textContent = 'Nuevo Proveedor';
        document.getElementById('proveedorId').value = '0';
        document.getElementById('proveedorForm').reset();
        document.getElementById('estadoLabel').textContent = 'Activo';
        document.getElementById('proveedorModal').classList.add('active');
    }

    /* ── Cerrar modal ── */
    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);
    function cerrarModal() {
        document.getElementById('proveedorModal').classList.remove('active');
    }

    /* ── Switch activo en modal ── */
    document.getElementById('activo').addEventListener('change', function () {
        document.getElementById('estadoLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
    });

    /* ── Guardar (crear o actualizar) ── */
    document.getElementById('saveBtn').addEventListener('click', function () {
        const form     = document.getElementById('proveedorForm');
        const formData = new FormData(form);
        const id       = document.getElementById('proveedorId').value;

        if (!formData.get('nombre') || !formData.get('telefono')) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Nombre y teléfono son obligatorios' });
            return;
        }

        const url = id > 0 ? `${basePath}/proveedores/actualizar` : `${basePath}/proveedores/crear`;

        const btn          = this;
        const originalHTML = btn.innerHTML;
        btn.innerHTML      = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        btn.disabled       = true;

        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled  = false;

                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, confirmButtonText: 'Aceptar' })
                        .then(() => location.reload());
                } else {
                    let msg = data.message;
                    if (data.errors) msg += '\n' + Object.values(data.errors).flat().join('\n');
                    Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonText: 'Aceptar' });
                }
            })
            .catch(() => {
                btn.innerHTML = originalHTML;
                btn.disabled  = false;
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor.' });
            });
    });

    /* ── Botones Editar ── */
    document.querySelectorAll('.btn-editar-proveedor').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-proveedor-id');
            fetch(`${basePath}/proveedores/get/${id}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        return;
                    }
                    const p = data.data;
                    document.getElementById('modalTitle').textContent     = 'Editar Proveedor';
                    document.getElementById('proveedorId').value          = p.id;
                    document.getElementById('nombre').value               = p.nombre    || '';
                    document.getElementById('empresa').value              = p.empresa   || '';
                    document.getElementById('telefono').value             = p.telefono  || '';
                    document.getElementById('nit_rut').value              = p.nit_rut   || '';
                    document.getElementById('direccion').value            = p.direccion || '';
                    document.getElementById('correo').value               = p.correo    || '';
                    document.getElementById('observacion').value          = p.observacion || '';
                    document.getElementById('categoria').value            = p.categoria || 'A';
                    const activoChk = document.getElementById('activo');
                    activoChk.checked = p.activo == 1;
                    document.getElementById('estadoLabel').textContent   = activoChk.checked ? 'Activo' : 'Inactivo';
                    document.getElementById('proveedorModal').classList.add('active');
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el proveedor.' }));
        });
    });

    /* ── Switches de estado en tabla ── */
    document.querySelectorAll('.status-switch').forEach(sw => {
        sw.addEventListener('change', function () {
            const id     = this.getAttribute('data-proveedor-id');
            const nombre = this.getAttribute('data-proveedor-nombre');
            const activo = this.checked ? 1 : 0;
            const self   = this;

            fetch(`${basePath}/proveedores/update-status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, activo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Estado actualizado',
                        text: `${nombre} ha sido ${activo ? 'activado' : 'desactivado'}`,
                        timer: 1500, showConfirmButton: false });
                } else {
                    self.checked = !self.checked;
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => { self.checked = !self.checked; });
        });
    });

    /* ── Botones Ver (ojito) ── */
    document.querySelectorAll('.btn-ver-proveedor').forEach(btn => {
        btn.addEventListener('click', function () {
            cargarDetalle(this.getAttribute('data-proveedor-id'));
        });
    });

    function cargarDetalle(id) {
        fetch(`${basePath}/proveedores/get/${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    mostrarDetalle(data.data);
                    document.getElementById('detalleProveedorModal').classList.add('active');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            });
    }

    function mostrarDetalle(p) {
        const foto = (p.foto && p.foto !== 'default.png')
            ? `${baseUrl}/assets/media/proveedores/${p.foto}`
            : `${baseUrl}/assets/media/proveedores/default.png`;

        const fecha = p.fecha_creacion
            ? new Date(p.fecha_creacion).toLocaleDateString('es-ES')
            : 'No disponible';

        const categoriaLabel = { A: 'A - Principal', B: 'B - Secundario', C: 'C - Ocasional' };

        document.getElementById('detalleProveedorContent').innerHTML = `
            <div class="detalle-proveedor">
                <div class="detalle-header">
                    <div class="detalle-foto">
                        <img src="${foto}" class="detalle-img"
                             onerror="this.src='${baseUrl}/assets/media/proveedores/default.png'"
                             alt="${p.nombre}">
                    </div>
                    <div class="detalle-info-principal">
                        <h3>${p.nombre}</h3>
                        <p class="detalle-empresa">${p.empresa || 'No especificada'}</p>
                        <p class="detalle-categoria">
                            <span class="badge badge-categoria-${p.categoria}">${categoriaLabel[p.categoria] || p.categoria}</span>
                        </p>
                    </div>
                </div>
                <div class="detalle-contenido">
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-id-card"></i> Identificación</h4>
                        <div class="detalle-grid">
                            <div class="detalle-item"><strong>NIT/RUT:</strong><span>${p.nit_rut || 'No especificado'}</span></div>
                        </div>
                    </div>
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-address-book"></i> Contacto</h4>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <strong><i class="fas fa-phone"></i> Teléfono:</strong>
                                <span><a href="tel:${p.telefono}">${p.telefono}</a></span>
                            </div>
                            <div class="detalle-item">
                                <strong><i class="fas fa-envelope"></i> Correo:</strong>
                                <span>${p.correo ? `<a href="mailto:${p.correo}">${p.correo}</a>` : 'No especificado'}</span>
                            </div>
                        </div>
                    </div>
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-map-marker-alt"></i> Dirección</h4>
                        <div class="detalle-item-full"><p>${p.direccion || 'No especificada'}</p></div>
                    </div>
                    ${p.observacion ? `
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-sticky-note"></i> Observaciones</h4>
                        <div class="detalle-item-full"><p>${p.observacion}</p></div>
                    </div>` : ''}
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-info-circle"></i> Sistema</h4>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <strong>Estado:</strong>
                                <span class="${p.activo == 1 ? 'estado-activo' : 'estado-inactivo'}">
                                    ${p.activo == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </div>
                            <div class="detalle-item"><strong>Registro:</strong><span>${fecha}</span></div>
                        </div>
                    </div>
                </div>
            </div>`;

        document.getElementById('detalleProveedorTitle').textContent = `Detalles: ${p.nombre}`;

        document.getElementById('descargarDetalleBtn').onclick = function () {
            const blob = new Blob([
                `PROVEEDOR: ${p.nombre}\nEmpresa: ${p.empresa || '-'}\nNIT/RUT: ${p.nit_rut || '-'}\n` +
                `Teléfono: ${p.telefono}\nCorreo: ${p.correo || '-'}\nDirección: ${p.direccion || '-'}\n` +
                `Categoría: ${categoriaLabel[p.categoria] || p.categoria}\nEstado: ${p.activo == 1 ? 'Activo' : 'Inactivo'}\n` +
                `Observaciones: ${p.observacion || '-'}\nFecha registro: ${fecha}\n`
            ], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a   = Object.assign(document.createElement('a'), { href: url, download: `proveedor_${p.nombre.replace(/\s+/g,'_')}.txt` });
            document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
            Swal.fire({ icon: 'success', title: 'Descargado', timer: 1500, showConfirmButton: false });
        };
    }

    /* ── Cerrar modal detalle ── */
    document.getElementById('closeDetalleModalBtn').addEventListener('click', () =>
        document.getElementById('detalleProveedorModal').classList.remove('active'));
    document.getElementById('closeDetalleBtn').addEventListener('click', () =>
        document.getElementById('detalleProveedorModal').classList.remove('active'));

    /* ── Búsqueda local ── */
    document.querySelector('.table-search').addEventListener('input', function () {
        const term  = this.value.toLowerCase();
        let visible = 0;
        document.querySelectorAll('.proveedores-table tbody tr').forEach(row => {
            const match = row.textContent.toLowerCase().includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const fc = document.querySelector('.filter-count');
        if (fc) fc.textContent = visible;
    });

    /* ── Confirmar eliminación ── */
    document.querySelectorAll('.form-eliminar').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombre = this.querySelector('button').getAttribute('data-proveedor-nombre');
            Swal.fire({
                title: '¿Eliminar proveedor?',
                html: `¿Seguro que deseas eliminar a <strong>${nombre}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(r => { if (r.isConfirmed) this.submit(); });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
