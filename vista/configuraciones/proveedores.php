<?php
// vista/configuraciones/proveedores.php

require_once __DIR__ . '/../../config/security.php';

$titulo = 'Gestión de Proveedores - CHEFCONTROL';
$paginaActual = 'configuraciones/proveedores';

$baseUrl = Config::getBaseUrl();
$basePath = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/proveedores.css">';
$jsExtra = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$jsExtra .= '<script src="' . $baseUrl . '/assets/js/proveedores.js"></script>';

require_once __DIR__ . '/../complementos/header.php';
?>

<div class="proveedores-container">
    <div class="proveedores-header">
        <div class="proveedores-title-section">
            <h1>Gestión de Proveedores</h1>
            <p>Administra los proveedores del sistema</p>
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
                <input type="text" 
                       class="table-search" 
                       placeholder="Buscar proveedor...">
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
                                    <?php if (isset($proveedor['foto']) && $proveedor['foto'] != 'default.png'): ?>
                                        <img src="<?php echo $baseUrl; ?>/assets/media/proveedores/<?php echo htmlspecialchars($proveedor['foto']); ?>" 
                                             class="logo-img"
                                             onerror="this.src='<?php echo $baseUrl; ?>/assets/media/proveedores/default.png'"
                                             alt="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <img class="avatar-proveedor" src="<?php echo $baseUrl; ?>/assets/media/proveedores/<?php echo htmlspecialchars($proveedor['foto']); ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="proveedor-details">
                                    <span class="proveedor-nombre">
                                        <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                    </span>
                                    <?php if (!empty($proveedor['nit_rut'])): ?>
                                        <span class="proveedor-nit">NIT/RUT: <?php echo htmlspecialchars($proveedor['nit_rut']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($proveedor['empresa']); ?></td>
                        <td>
                            <a href="tel:<?php echo htmlspecialchars($proveedor['telefono']); ?>" 
                               style="color: #3498db; text-decoration: none;">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($proveedor['telefono']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($proveedor['direccion']); ?></td>
                        <td>
                            <span class="badge badge-categoria-<?php echo $proveedor['categoria']; ?>">
                                <?php 
                                switch($proveedor['categoria']) {
                                    case 'A': echo 'A - Principal'; break;
                                    case 'B': echo 'B - Secundario'; break;
                                    case 'C': echo 'C - Ocasional'; break;
                                    default: echo $proveedor['categoria'];
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <!-- Switch como en usuarios -->
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
                                <!-- Botón Ver (ojito) -->
                                <button class="btn-accion btn-info btn-ver-proveedor"
                                        data-proveedor-id="<?php echo $proveedor['id']; ?>"
                                        title="Ver detalles del proveedor">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <!-- Botón Editar -->
                                <button class="btn-accion btn-editar"
                                        data-proveedor-id="<?php echo $proveedor['id']; ?>"
                                        title="Editar proveedor">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Botón Eliminar -->
                                <form method="POST" action="<?php echo $basePath; ?>/proveedores/eliminar" 
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
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; color: #7f8c8d; font-size: 14px; border-top: 2px solid #f8f9fa;">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Mostrando <strong><?php echo count($proveedores); ?></strong> proveedor(es)
                </div>
                <div>
                    <span class="badge badge-categoria-A" style="margin-right: 5px;">
                        <?php echo count(array_filter($proveedores, fn($p) => ($p['categoria'] ?? '') == 'A')); ?> A
                    </span>
                    <span class="badge badge-categoria-B" style="margin-right: 5px;">
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

<!-- Modal para agregar/editar proveedor -->
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
                    <label for="foto" class="form-label">Foto/Logo</label>
                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                    <small class="form-text">Formatos aceptados: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
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
            <button type="button" class="btn-primary" id="saveBtn">Guardar Proveedor</button>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del proveedor -->
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
            <div id="detalleProveedorContent">
                <!-- Los detalles se cargarán aquí dinámicamente -->
            </div>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Sistema de proveedores cargado');
    
    // Abrir modal para agregar/editar
    document.getElementById('openModalBtn').addEventListener('click', function() {
        document.getElementById('proveedorModal').classList.add('active');
        document.getElementById('modalTitle').textContent = 'Nuevo Proveedor';
        document.getElementById('proveedorId').value = '0';
        document.getElementById('proveedorForm').reset();
    });
    
    // Cerrar modal agregar/editar
    document.getElementById('closeModalBtn').addEventListener('click', function() {
        document.getElementById('proveedorModal').classList.remove('active');
    });
    
    document.getElementById('cancelBtn').addEventListener('click', function() {
        document.getElementById('proveedorModal').classList.remove('active');
    });
    
    // Switch de estado en modal
    const estadoSwitch = document.getElementById('activo');
    const estadoLabel = document.getElementById('estadoLabel');
    
    if (estadoSwitch && estadoLabel) {
        estadoSwitch.addEventListener('change', function() {
            estadoLabel.textContent = this.checked ? 'Activo' : 'Inactivo';
        });
    }
    
    // Guardar proveedor
    document.getElementById('saveBtn').addEventListener('click', function() {
        const form = document.getElementById('proveedorForm');
        const formData = new FormData(form);
        
        // Validación básica
        if (!formData.get('nombre') || !formData.get('telefono')) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Nombre y teléfono son campos obligatorios',
                confirmButtonText: 'Aceptar'
            });
            return;
        }
        
        // Mostrar loading
        const saveBtn = this;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled = true;
        
        // URL CORRECTA
        const url = '<?php echo $basePath; ?>/proveedores/crear';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            // Restaurar botón
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                let errorMessage = data.message;
                if (data.errors) {
                    errorMessage += '\n' + Object.values(data.errors).flat().join('\n');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonText: 'Aceptar'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Restaurar botón
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor. Verifica tu conexión.',
                confirmButtonText: 'Aceptar'
            });
        });
    });
    
    // Switches de estado en la tabla (como en usuarios)
    const statusSwitches = document.querySelectorAll('.status-switch');
    statusSwitches.forEach(switchEl => {
        switchEl.addEventListener('change', function() {
            const proveedorId = this.getAttribute('data-proveedor-id');
            const proveedorNombre = this.getAttribute('data-proveedor-nombre');
            const activo = this.checked ? 1 : 0;
            
            fetch('<?php echo $basePath; ?>/proveedores/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: proveedorId,
                    activo: activo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        text: `El estado de ${proveedorNombre} ha sido actualizado`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    // Revertir el switch si hay error
                    this.checked = !this.checked;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revertir el switch si hay error
                this.checked = !this.checked;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo actualizar el estado',
                    confirmButtonText: 'Aceptar'
                });
            });
        });
    });
    
    // Botones para ver detalles (ojito)
    const verBtns = document.querySelectorAll('.btn-ver-proveedor');
    verBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const proveedorId = this.getAttribute('data-proveedor-id');
            cargarDetallesProveedor(proveedorId);
        });
    });
    
    // Función para cargar detalles del proveedor
    function cargarDetallesProveedor(proveedorId) {
        fetch(`<?php echo $basePath; ?>/proveedores/get/${proveedorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetallesProveedor(data.data);
                document.getElementById('detalleProveedorModal').classList.add('active');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonText: 'Aceptar'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar los detalles',
                confirmButtonText: 'Aceptar'
            });
        });
    }
    
    // Función para mostrar detalles del proveedor
    function mostrarDetallesProveedor(proveedor) {
        const fotoUrl = proveedor.foto && proveedor.foto !== 'default.png' 
            ? `<?php echo $baseUrl; ?>/assets/media/proveedores/${proveedor.foto}`
            : `<?php echo $baseUrl; ?>/assets/media/proveedores/default.png`;
        
        const fechaCreacion = proveedor.fecha_creacion 
            ? new Date(proveedor.fecha_creacion).toLocaleDateString('es-ES')
            : 'No disponible';
        
        let contenidoHTML = `
            <div class="detalle-proveedor">
                <div class="detalle-header">
                    <div class="detalle-foto">
                        <img src="${fotoUrl}" 
                             class="detalle-img"
                             onerror="this.src='<?php echo $baseUrl; ?>/assets/media/proveedores/default.png'"
                             alt="${proveedor.nombre}">
                    </div>
                    <div class="detalle-info-principal">
                        <h3>${proveedor.nombre}</h3>
                        <p class="detalle-empresa">${proveedor.empresa || 'No especificada'}</p>
                        <p class="detalle-categoria"><span class="badge badge-categoria-${proveedor.categoria}">${proveedor.categoria}</span></p>
                    </div>
                </div>
                
                <div class="detalle-contenido">
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-id-card"></i> Información de Identificación</h4>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <strong>NIT/RUT:</strong>
                                <span>${proveedor.nit_rut || 'No especificado'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-address-book"></i> Información de Contacto</h4>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <strong><i class="fas fa-phone"></i> Teléfono:</strong>
                                <span><a href="tel:${proveedor.telefono}">${proveedor.telefono}</a></span>
                            </div>
                            <div class="detalle-item">
                                <strong><i class="fas fa-envelope"></i> Correo:</strong>
                                <span>${proveedor.correo ? `<a href="mailto:${proveedor.correo}">${proveedor.correo}</a>` : 'No especificado'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-map-marker-alt"></i> Dirección</h4>
                        <div class="detalle-item-full">
                            <p>${proveedor.direccion || 'No especificada'}</p>
                        </div>
                    </div>
                    
                    ${proveedor.observacion ? `
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-sticky-note"></i> Observaciones</h4>
                        <div class="detalle-item-full">
                            <p>${proveedor.observacion}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="detalle-seccion">
                        <h4><i class="fas fa-info-circle"></i> Información del Sistema</h4>
                        <div class="detalle-grid">
                            <div class="detalle-item">
                                <strong>Estado:</strong>
                                <span class="${proveedor.activo == 1 ? 'estado-activo' : 'estado-inactivo'}">
                                    ${proveedor.activo == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </div>
                            <div class="detalle-item">
                                <strong>Fecha de registro:</strong>
                                <span>${fechaCreacion}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('detalleProveedorContent').innerHTML = contenidoHTML;
        document.getElementById('detalleProveedorTitle').textContent = `Detalles: ${proveedor.nombre}`;
    }
    
    // Cerrar modal de detalles
    document.getElementById('closeDetalleModalBtn').addEventListener('click', function() {
        document.getElementById('detalleProveedorModal').classList.remove('active');
    });
    
    document.getElementById('closeDetalleBtn').addEventListener('click', function() {
        document.getElementById('detalleProveedorModal').classList.remove('active');
    });
    
    // Descargar información del proveedor
    document.getElementById('descargarDetalleBtn').addEventListener('click', function() {
        const proveedorNombre = document.getElementById('detalleProveedorTitle').textContent.replace('Detalles: ', '');
        const contenido = document.getElementById('detalleProveedorContent').innerText;
        
        const blob = new Blob([`INFORMACIÓN DEL PROVEEDOR\n\n${contenido}`], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `proveedor_${proveedorNombre.replace(/\s+/g, '_')}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        Swal.fire({
            icon: 'success',
            title: 'Descargado',
            text: 'La información se ha descargado correctamente',
            timer: 1500,
            showConfirmButton: false
        });
    });
    
    // Búsqueda
    const searchInput = document.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.proveedores-table tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const filterCount = document.querySelector('.filter-count');
            if (filterCount) {
                filterCount.textContent = visibleCount;
            }
        });
    }
    
    // Botón para abrir modal desde sección vacía
    const openModalBtnEmpty = document.getElementById('openModalBtnEmpty');
    if (openModalBtnEmpty) {
        openModalBtnEmpty.addEventListener('click', function() {
            document.getElementById('openModalBtn').click();
        });
    }
    
    // Confirmación para eliminar
    const formsEliminar = document.querySelectorAll('.form-eliminar');
    formsEliminar.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const proveedorNombre = this.querySelector('button').getAttribute('data-proveedor-nombre');
            
            Swal.fire({
                title: '¿Eliminar proveedor?',
                html: `¿Estás seguro de eliminar al proveedor <strong>${proveedorNombre}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>