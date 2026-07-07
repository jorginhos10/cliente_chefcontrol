// assets/js/proveedores.js

console.log('✅ proveedores.js cargado correctamente');

// Clase para manejar proveedores
class ProveedoresManager {
    constructor() {
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        this.basePath = document.querySelector('meta[name="base-path"]')?.content || '';
        this.init();
    }

    init() {
        console.log('🚀 Inicializando ProveedoresManager');
        this.setupEventListeners();
        this.setupSwitches();
    }

    setupEventListeners() {
        // Botones de ver detalles
        document.querySelectorAll('.btn-ver-proveedor').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const proveedorId = e.currentTarget.getAttribute('data-proveedor-id');
                this.verDetallesProveedor(proveedorId);
            });
        });

        // Switches de estado
        document.querySelectorAll('.status-switch').forEach(switchEl => {
            switchEl.addEventListener('change', (e) => {
                const proveedorId = e.target.getAttribute('data-proveedor-id');
                const proveedorNombre = e.target.getAttribute('data-proveedor-nombre');
                const activo = e.target.checked ? 1 : 0;
                this.actualizarEstadoProveedor(proveedorId, proveedorNombre, activo);
            });
        });

        // Confirmación para eliminar
        document.querySelectorAll('.form-eliminar').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const proveedorNombre = form.querySelector('button').getAttribute('data-proveedor-nombre');
                this.confirmarEliminacion(proveedorNombre, form);
            });
        });

        // Búsqueda
        const searchInput = document.querySelector('.table-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filtrarProveedores(e.target.value);
            });
        }
    }

    setupSwitches() {
        // Inicializar switches con estado actual
        document.querySelectorAll('.status-switch').forEach(switchEl => {
            const proveedorId = switchEl.getAttribute('data-proveedor-id');
            this.verificarEstadoInicial(proveedorId, switchEl);
        });
    }

    async verificarEstadoInicial(proveedorId, switchEl) {
        try {
            const response = await fetch(`${this.basePath}/proveedores/get/${proveedorId}`);
            const data = await response.json();
            
            if (data.success) {
                switchEl.checked = data.data.activo == 1;
            }
        } catch (error) {
            console.error('Error verificando estado:', error);
        }
    }

    async verDetallesProveedor(proveedorId) {
        try {
            this.mostrarLoading();
            
            const response = await fetch(`${this.basePath}/proveedores/get/${proveedorId}`);
            const data = await response.json();
            
            this.ocultarLoading();
            
            if (data.success) {
                this.mostrarModalDetalles(data.data);
            } else {
                this.mostrarError('Error', data.message);
            }
        } catch (error) {
            this.ocultarLoading();
            this.mostrarError('Error de conexión', 'No se pudieron cargar los detalles');
            console.error('Error:', error);
        }
    }

    mostrarModalDetalles(proveedor) {
        const fotoUrl = proveedor.foto && proveedor.foto !== 'default.png' 
            ? `${this.baseUrl}/assets/media/proveedores/${proveedor.foto}`
            : `${this.baseUrl}/assets/media/proveedores/default.png`;
        
        const fechaCreacion = proveedor.fecha_creacion 
            ? new Date(proveedor.fecha_creacion).toLocaleDateString('es-ES')
            : 'No disponible';
        
        const contenido = `
            <div class="detalle-proveedor">
                <div class="detalle-header">
                    <div class="detalle-foto">
                        <img src="${fotoUrl}" 
                             class="detalle-img"
                             onerror="this.src='${this.baseUrl}/assets/media/proveedores/default.png'"
                             alt="${proveedor.nombre}">
                    </div>
                    <div class="detalle-info-principal">
                        <h3>${proveedor.nombre}</h3>
                        <p class="detalle-empresa">${proveedor.empresa || 'No especificada'}</p>
                        <p class="detalle-categoria">
                            <span class="badge badge-categoria-${proveedor.categoria}">${proveedor.categoria}</span>
                        </p>
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
                            <div class="detalle-item">
                                <strong>ID del Proveedor:</strong>
                                <span>${proveedor.id}</span>
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
                                    <i class="fas fa-circle" style="font-size: 10px; margin-right: 5px;"></i>
                                    ${proveedor.activo == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </div>
                            <div class="detalle-item">
                                <strong>Fecha de registro:</strong>
                                <span>${fechaCreacion}</span>
                            </div>
                            <div class="detalle-item">
                                <strong>Última actualización:</strong>
                                <span>${proveedor.fecha_actualizacion ? new Date(proveedor.fecha_actualizacion).toLocaleDateString('es-ES') : 'No disponible'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Crear o actualizar modal de detalles
        let modal = document.getElementById('detalleProveedorModal');
        if (!modal) {
            modal = this.crearModalDetalles();
        }
        
        document.getElementById('detalleProveedorContent').innerHTML = contenido;
        document.getElementById('detalleProveedorTitle').textContent = `Detalles: ${proveedor.nombre}`;
        
        // Configurar botón de descarga
        document.getElementById('descargarDetalleBtn').onclick = () => {
            this.descargarInformacionProveedor(proveedor);
        };
        
        modal.classList.add('active');
    }

    crearModalDetalles() {
        const modalHTML = `
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
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Configurar eventos del modal
        document.getElementById('closeDetalleModalBtn').addEventListener('click', () => {
            document.getElementById('detalleProveedorModal').classList.remove('active');
        });
        
        document.getElementById('closeDetalleBtn').addEventListener('click', () => {
            document.getElementById('detalleProveedorModal').classList.remove('active');
        });
        
        return document.getElementById('detalleProveedorModal');
    }

    async actualizarEstadoProveedor(proveedorId, proveedorNombre, activo) {
        try {
            const response = await fetch(`${this.basePath}/proveedores/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: proveedorId,
                    activo: activo
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarExito('Estado actualizado', `El estado de ${proveedorNombre} ha sido actualizado`);
                
                // Actualizar contador de activos si existe
                this.actualizarContadores();
            } else {
                // Revertir el switch si hay error
                const switchEl = document.querySelector(`[data-proveedor-id="${proveedorId}"]`);
                if (switchEl) {
                    switchEl.checked = !switchEl.checked;
                }
                this.mostrarError('Error', data.message);
            }
        } catch (error) {
            // Revertir el switch si hay error
            const switchEl = document.querySelector(`[data-proveedor-id="${proveedorId}"]`);
            if (switchEl) {
                switchEl.checked = !switchEl.checked;
            }
            this.mostrarError('Error de conexión', 'No se pudo actualizar el estado');
            console.error('Error:', error);
        }
    }

    actualizarContadores() {
        // Actualizar contador de activos en las estadísticas
        const activosCount = document.querySelectorAll('.status-switch:checked').length;
        const statCard = document.querySelector('.stat-card:nth-child(2) .stat-number');
        if (statCard) {
            statCard.textContent = activosCount;
        }
    }

    filtrarProveedores(termino) {
        const rows = document.querySelectorAll('.proveedores-table tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(termino.toLowerCase())) {
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
    }

    confirmarEliminacion(proveedorNombre, form) {
        Swal.fire({
            title: '¿Eliminar proveedor?',
            html: `¿Estás seguro de eliminar al proveedor <strong>${proveedorNombre}</strong>?<br><small>Esta acción no se puede deshacer.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    descargarInformacionProveedor(proveedor) {
        const contenido = `
INFORMACIÓN DEL PROVEEDOR
=========================

DATOS PRINCIPALES
-----------------
Nombre: ${proveedor.nombre}
Empresa: ${proveedor.empresa || 'No especificada'}
Categoría: ${proveedor.categoria}
NIT/RUT: ${proveedor.nit_rut || 'No especificado'}

CONTACTO
--------
Teléfono: ${proveedor.telefono}
Correo: ${proveedor.correo || 'No especificado'}
Dirección: ${proveedor.direccion || 'No especificada'}

INFORMACIÓN DEL SISTEMA
-----------------------
ID: ${proveedor.id}
Estado: ${proveedor.activo == 1 ? 'Activo' : 'Inactivo'}
Fecha de registro: ${proveedor.fecha_creacion ? new Date(proveedor.fecha_creacion).toLocaleDateString('es-ES') : 'No disponible'}
Última actualización: ${proveedor.fecha_actualizacion ? new Date(proveedor.fecha_actualizacion).toLocaleDateString('es-ES') : 'No disponible'}

OBSERVACIONES
-------------
${proveedor.observacion || 'No hay observaciones'}

------------------------
Generado el: ${new Date().toLocaleString('es-ES')}
Sistema: CHEFCONTROL
        `;
        
        const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `proveedor_${proveedor.nombre.replace(/\s+/g, '_')}_${new Date().getTime()}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        this.mostrarExito('Descargado', 'La información se ha descargado correctamente');
    }

    mostrarLoading() {
        let loading = document.getElementById('proveedoresLoading');
        if (!loading) {
            loading = document.createElement('div');
            loading.id = 'proveedoresLoading';
            loading.className = 'loading-overlay';
            loading.innerHTML = `
                <div class="spinner-container">
                    <div class="spinner"></div>
                    <p>Cargando...</p>
                </div>
            `;
            document.body.appendChild(loading);
        }
        loading.style.display = 'flex';
    }

    ocultarLoading() {
        const loading = document.getElementById('proveedoresLoading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    mostrarExito(titulo, mensaje) {
        Swal.fire({
            icon: 'success',
            title: titulo,
            text: mensaje,
            timer: 2000,
            showConfirmButton: false
        });
    }

    mostrarError(titulo, mensaje) {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: mensaje,
            confirmButtonText: 'Aceptar'
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.proveedoresManager = new ProveedoresManager();
    console.log('✅ ProveedoresManager inicializado');
});

// Exportar para uso en otros archivos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProveedoresManager;
}