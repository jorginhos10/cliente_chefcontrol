// assets/js/permisos.js - ARCHIVO COMPLETO ACTUALIZADO

class PermisosManager {
    constructor() {
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        this.currentUserId = document.querySelector('meta[name="current-user-id"]')?.getAttribute('content') || '0';
        this.currentUsuarioId = null;
        this.currentUsuarioNombre = null;
        this.currentUsuarioAvatar = null;
        this.init();
    }
    
    init() {
        // Crear HTML del popup
        this.crearPopupHTML();
        
        // Configurar event listeners
        this.configurarEventListeners();
        
        // Configurar botones de permisos
        this.configurarBotonesPermisos();
        
        console.log('✅ PermisosManager inicializado correctamente');
    }
    
    // Configurar botones de permisos en la tabla de usuarios
    configurarBotonesPermisos() {
        console.log('🔧 Configurando botones de permisos...');
        
        // Usar delegación de eventos para manejar clics dinámicos
        document.addEventListener('click', (e) => {
            const permisoBtn = e.target.closest('.btn-permisos');
            
            if (permisoBtn && !permisoBtn.disabled) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('🖱️ Botón de permisos clickeado:', permisoBtn);
                
                const userId = permisoBtn.getAttribute('data-user-id');
                const userName = permisoBtn.getAttribute('data-user-name');
                const userAvatar = permisoBtn.getAttribute('data-user-avatar') || 'default.png';
                
                if (!userId || !userName) {
                    console.error('❌ Datos del usuario incompletos');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo obtener la información del usuario.'
                    });
                    return;
                }
                
                console.log(`👤 Usuario seleccionado: ID=${userId}, Nombre=${userName}`);
                
                // Verificar si es el usuario actual
                if (userId === this.currentUserId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No puedes ver tus propios permisos aquí',
                        text: 'Para ver tus permisos, usa la opción en tu perfil.',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                
                // Verificar si es superadmin (ID 1)
                if (userId === '1') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Usuario protegido',
                        text: 'No se pueden ver/modificar los permisos del administrador principal.',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                
                // Abrir popup de permisos
                this.abrirPopupPermisos(userId, userName, userAvatar);
            }
        });
    }
    
    // Crear HTML del popup
    crearPopupHTML() {
        if (document.getElementById('permisosPopup')) return;
        
        console.log('🛠️ Creando HTML del popup de permisos...');
        
        const popupHTML = `
            <div class="popup-overlay" id="permisosPopupOverlay"></div>
            <div class="permisos-popup" id="permisosPopup">
                <div class="popup-header">
                    <h3>
                        <i class="fas fa-user-lock"></i>
                        <span id="popupUsuarioNombre">Gestión de Permisos</span>
                    </h3>
                    <button class="btn-cerrar-popup" id="cerrarPopupPermisos">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="popup-body">
                    <!-- Información del usuario -->
                    <div class="usuario-info-popup">
                        <div class="usuario-avatar-popup">
                            <img id="popupUsuarioAvatar" src="" alt="Avatar" 
                                 onerror="this.src='${this.baseUrl}/assets/media/users/default.png'">
                        </div>
                        <div class="usuario-datos-popup">
                            <h4 id="popupUsuarioNombreCompleto"></h4>
                            <p id="popupUsuarioInfo"></p>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="permisos-stats-popup">
                        <div class="stat-popup">
                            <div class="stat-icon-popup">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content-popup">
                                <div class="stat-number-popup" id="permisosAsignadosCount">0</div>
                                <div class="stat-label-popup">Asignados</div>
                            </div>
                        </div>
                        <div class="stat-popup">
                            <div class="stat-icon-popup">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="stat-content-popup">
                                <div class="stat-number-popup" id="totalPermisosCount">0</div>
                                <div class="stat-label-popup">Disponibles</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de permisos -->
                    <div class="permisos-lista-container">
                        <h4>
                            <i class="fas fa-cubes"></i>
                            Módulos del plan
                            <small style="font-size: 12px; color: #7f8c8d; margin-left: 10px;">
                                Activa o desactiva el acceso por módulo
                            </small>
                        </h4>
                        <div id="permisosListaPopup">
                            <div class="loading-permisos">
                                <div class="spinner"></div>
                                <p>Cargando permisos activos...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensajes -->
                    <div id="popupMensajes"></div>
                </div>
                
                <div class="popup-footer">
                    <button type="button" class="btn-secundario-popup" id="cancelarPopupPermisos">
                        <i class="fas fa-times"></i>
                        Cerrar
                    </button>
                </div>
                
                <!-- Loading overlay -->
                <div class="loading-overlay-popup" id="popupLoading">
                    <div class="spinner"></div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', popupHTML);
        console.log('✅ HTML del popup creado correctamente');
    }
    
    // Configurar event listeners del popup
    configurarEventListeners() {
        console.log('🔧 Configurando event listeners del popup...');
        
        // Cerrar popup
        document.addEventListener('click', (e) => {
            if (e.target.id === 'cerrarPopupPermisos' ||
                e.target.id === 'cancelarPopupPermisos') {
                this.cerrarPopupPermisos();
            }
        });
        
        // Cerrar con ESC
        document.addEventListener('keydown', (e) => {
            const popup = document.getElementById('permisosPopup');
            if (e.key === 'Escape' && popup && popup.classList.contains('active')) {
                this.cerrarPopupPermisos();
            }
        });
    }
    
    // Abrir popup de permisos
    async abrirPopupPermisos(userId, userName, userAvatar) {
        console.log(`🔓 Abriendo popup de permisos para usuario ID: ${userId}`);
        
        this.currentUsuarioId = userId;
        this.currentUsuarioNombre = userName;
        this.currentUsuarioAvatar = userAvatar;
        
        const popup = document.getElementById('permisosPopup');
        const overlay = document.getElementById('permisosPopupOverlay');
        
        if (!popup) {
            console.error('❌ Popup no encontrado');
            return;
        }
        
        // Mostrar popup
        popup.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Mostrar loading
        this.mostrarLoading(true);
        
        // Actualizar información básica del usuario
        this.actualizarInfoUsuarioPopup(userName, userAvatar);
        
        // Cargar datos de permisos
        await this.cargarPermisosUsuario(userId);
        
        // Ocultar loading
        this.mostrarLoading(false);
        
        console.log('✅ Popup abierto correctamente');
    }
    
    // Cerrar popup
    cerrarPopupPermisos() {
        console.log('🔒 Cerrando popup de permisos');
        
        const popup = document.getElementById('permisosPopup');
        const overlay = document.getElementById('permisosPopupOverlay');
        
        if (popup) popup.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Limpiar datos
        this.currentUsuarioId = null;
        this.currentUsuarioNombre = null;
        this.currentUsuarioAvatar = null;
        
        // Limpiar mensajes
        this.limpiarMensajesPopup();
    }
    
    // Actualizar información del usuario en el popup
    actualizarInfoUsuarioPopup(userName, userAvatar) {
        console.log('👤 Actualizando info del usuario en popup:', userName);
        
        const nombreElement = document.getElementById('popupUsuarioNombre');
        const nombreCompletoElement = document.getElementById('popupUsuarioNombreCompleto');
        const avatarImg = document.getElementById('popupUsuarioAvatar');
        
        if (nombreElement) {
            nombreElement.textContent = `Permisos de ${userName}`;
        }
        
        if (nombreCompletoElement) {
            nombreCompletoElement.textContent = userName;
        }
        
        if (avatarImg) {
            avatarImg.src = `${this.baseUrl}/assets/media/users/${userAvatar}`;
        }
    }
    
    // Mostrar/ocultar loading
    mostrarLoading(mostrar) {
        const loading = document.getElementById('popupLoading');
        if (loading) {
            if (mostrar) {
                loading.classList.add('active');
            } else {
                loading.classList.remove('active');
            }
        }
    }
    
    // Cargar permisos del usuario
    async cargarPermisosUsuario(userId) {
        console.log(`📋 Cargando permisos ACTIVOS para usuario ID: ${userId}`);
        
        try {
            const url = `${this.baseUrl}/permisos/popup-get/${userId}`;
            console.log('🌐 URL de solicitud:', url);
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('📦 Respuesta del servidor:', result);
            
            if (result.success) {
                this.renderizarPermisos(result.permisos, result.usuario, result.estadisticas);
            } else {
                console.error('❌ Error del servidor:', result.message);
                this.mostrarMensajePopup(result.message, 'error');
                
                // Cerrar popup después de 3 segundos
                setTimeout(() => {
                    this.cerrarPopupPermisos();
                }, 3000);
            }
        } catch (error) {
            console.error('❌ Error cargando permisos:', error);
            this.mostrarMensajePopup('Error de conexión: ' + error.message, 'error');
            
            // Mostrar datos de ejemplo para debugging
            this.mostrarDatosEjemplo();
        }
    }
    
    // Renderizar permisos en el popup
    renderizarPermisos(permisos, usuarioInfo, estadisticas) {
        console.log('🎨 Renderizando permisos ACTIVOS:', permisos);
        
        // Actualizar estadísticas
        this.actualizarEstadisticas(estadisticas);
        
        // Actualizar información adicional del usuario
        if (usuarioInfo) {
            const infoElement = document.getElementById('popupUsuarioInfo');
            if (infoElement) {
                infoElement.innerHTML = `
                    <strong>Usuario:</strong> ${usuarioInfo.username}<br>
                    <strong>Rol:</strong> ${usuarioInfo.rol_formateado || usuarioInfo.rol}
                `;
            }
        }
        
        // Renderizar lista de permisos
        const container = document.getElementById('permisosListaPopup');
        
        if (!container) {
            console.error('❌ Contenedor de permisos no encontrado');
            return;
        }
        
        // Filtrar solo permisos activos (si por algún motivo llegan inactivos)
        const permisosActivos = permisos.filter(permiso => permiso.activo !== undefined);
        
        if (!permisosActivos || permisosActivos.length === 0) {
            container.innerHTML = `
                <div class="no-permisos">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>No hay permisos activos disponibles</h4>
                    <p>No se encontraron permisos con estado = 1 en el sistema</p>
                    <small>Contacta al administrador para activar permisos en lista_permisos</small>
                </div>
            `;
            return;
        }
        
        let html = '';
        
        permisosActivos.forEach(permiso => {
            const tienePermiso = permiso.activo == 1;
            const switchId = `permiso_popup_${permiso.id}`;
            const nombreMostrar = permiso.nombre_formateado || permiso.nombre;
            const descripcion = permiso.descripcion || '';
            
            // Determinar clase CSS según si está asignado o no
            const estadoClase = tienePermiso ? 'activo' : 'inactivo';
            const estadoTexto = tienePermiso ? '✅ Asignado' : '❌ No asignado';
            const fechaAsignacion = permiso.fecha_asignacion ? 
                new Date(permiso.fecha_asignacion).toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) : null;
            
            html += `
                <div class="permiso-item-popup ${estadoClase}" data-permiso-id="${permiso.id}">
                    <div class="permiso-info-popup">
                        <div class="permiso-nombre-popup">
                            <i class="fas fa-${tienePermiso ? 'check-circle' : 'times-circle'}"></i>
                            ${nombreMostrar}
                        </div>
                        ${descripcion ? 
                            `<div class="permiso-descripcion-popup">${descripcion}</div>` : ''
                        }
                        ${fechaAsignacion ? 
                            `<div class="permiso-fecha-popup">
                                <i class="fas fa-calendar-alt"></i>
                                Asignado: ${fechaAsignacion}
                            </div>` : ''
                        }
                    </div>
                    <div class="permiso-switch-container-popup">
                        <span class="permiso-status-popup ${estadoClase}">
                            ${estadoTexto}
                        </span>
                        <label class="permiso-switch-popup">
                            <input type="checkbox" 
                                   id="${switchId}"
                                   class="permiso-checkbox"
                                   data-permiso-id="${permiso.id}"
                                   ${tienePermiso ? 'checked' : ''}>
                            <span class="permiso-slider-popup"></span>
                        </label>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Configurar event listeners para los switches
        this.configurarSwitchesPermisos();
        
        console.log(`✅ ${permisosActivos.length} permisos activos renderizados correctamente`);
    }
    
    // Actualizar estadísticas
    actualizarEstadisticas(estadisticas) {
        console.log('📊 Actualizando estadísticas:', estadisticas);
        
        const asignadosElement = document.getElementById('permisosAsignadosCount');
        const totalesElement = document.getElementById('totalPermisosCount');
        
        if (asignadosElement && estadisticas.permisos_asignados !== undefined) {
            asignadosElement.textContent = estadisticas.permisos_asignados;
        }
        
        if (totalesElement && estadisticas.total_permisos_disponibles !== undefined) {
            totalesElement.textContent = estadisticas.total_permisos_disponibles;
        }
    }
    
    // Configurar switches de permisos
    configurarSwitchesPermisos() {
        const switches = document.querySelectorAll('.permiso-switch-popup input');
        console.log(`🔧 Configurando ${switches.length} switches de permisos...`);
        
        switches.forEach(switchEl => {
            switchEl.addEventListener('change', async (e) => {
                const permisoId = e.target.getAttribute('data-permiso-id');
                const nuevoEstado = e.target.checked ? 1 : 0;
                const permisoItem = e.target.closest('.permiso-item-popup');
                
                console.log(`🔄 Alternando permiso ID: ${permisoId} a estado: ${nuevoEstado}`);
                
                if (!this.currentUsuarioId) {
                    console.error('❌ No hay usuario seleccionado');
                    this.mostrarMensajePopup('Error: No se pudo identificar al usuario', 'error');
                    e.target.checked = !e.target.checked;
                    return;
                }
                
                // Deshabilitar temporalmente el switch durante la operación
                e.target.disabled = true;
                permisoItem.classList.add('processing');
                
                try {
                    const url = `${this.baseUrl}/permisos/popup-toggle`;
                    console.log('🌐 URL de toggle:', url);
                    
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            usuario_id: this.currentUsuarioId,
                            permiso_id: permisoId,
                            nuevo_estado: nuevoEstado
                        })
                    });
                    
                    const result = await response.json();
                    console.log('📦 Respuesta del toggle:', result);
                    
                    if (result.success) {
                        // Actualizar estadísticas
                        if (result.estadisticas) {
                            this.actualizarEstadisticas(result.estadisticas);
                            this.animarContador('permisosAsignadosCount', result.estadisticas.permisos_asignados);
                        }
                        
                        // Actualizar estado visual
                        this.actualizarEstadoPermisoVisual(permisoId, nuevoEstado);
                        
                        // Mostrar mensaje de éxito
                        this.mostrarMensajePopup(result.message, 'success', true);
                        
                        // Animar el cambio
                        permisoItem.classList.add('changed');
                        setTimeout(() => {
                            permisoItem.classList.remove('changed');
                        }, 1000);
                        
                        console.log(`✅ Permiso ${nuevoEstado ? 'asignado' : 'removido'} correctamente`);
                    } else {
                        // Revertir switch
                        e.target.checked = !nuevoEstado;
                        this.mostrarMensajePopup(result.message, 'error');
                        console.error('❌ Error en la respuesta:', result.message);
                    }
                } catch (error) {
                    console.error('❌ Error alternando permiso:', error);
                    e.target.checked = !nuevoEstado;
                    this.mostrarMensajePopup('Error de conexión al servidor', 'error');
                } finally {
                    // Rehabilitar el switch
                    e.target.disabled = false;
                    permisoItem.classList.remove('processing');
                }
            });
        });
    }
    
    // Actualizar estado visual del permiso
    actualizarEstadoPermisoVisual(permisoId, nuevoEstado) {
        const permisoItem = document.querySelector(`.permiso-item-popup[data-permiso-id="${permisoId}"]`);
        if (permisoItem) {
            // Cambiar clase CSS
            permisoItem.className = `permiso-item-popup ${nuevoEstado ? 'activo' : 'inactivo'}`;
            
            // Actualizar icono
            const icono = permisoItem.querySelector('.permiso-nombre-popup i');
            if (icono) {
                icono.className = `fas fa-${nuevoEstado ? 'check-circle' : 'times-circle'}`;
            }
            
            // Actualizar texto de estado
            const statusSpan = permisoItem.querySelector('.permiso-status-popup');
            if (statusSpan) {
                statusSpan.textContent = nuevoEstado ? '✅ Asignado' : '❌ No asignado';
                statusSpan.className = `permiso-status-popup ${nuevoEstado ? 'activo' : 'inactivo'}`;
            }
            
            // Actualizar fecha si se asignó ahora
            if (nuevoEstado) {
                const fechaContainer = permisoItem.querySelector('.permiso-fecha-popup');
                if (!fechaContainer) {
                    const fechaHtml = `
                        <div class="permiso-fecha-popup">
                            <i class="fas fa-calendar-alt"></i>
                            Asignado: ${new Date().toLocaleDateString('es-ES')}
                        </div>
                    `;
                    const infoContainer = permisoItem.querySelector('.permiso-info-popup');
                    if (infoContainer) {
                        const descripcion = infoContainer.querySelector('.permiso-descripcion-popup');
                        if (descripcion) {
                            descripcion.insertAdjacentHTML('afterend', fechaHtml);
                        } else {
                            const nombre = infoContainer.querySelector('.permiso-nombre-popup');
                            if (nombre) {
                                nombre.insertAdjacentHTML('afterend', fechaHtml);
                            }
                        }
                    }
                }
            } else {
                // Remover fecha si se desasignó
                const fechaContainer = permisoItem.querySelector('.permiso-fecha-popup');
                if (fechaContainer) {
                    fechaContainer.remove();
                }
            }
        }
    }
    
    // Animar contador
    animarContador(elementId, nuevoValor) {
        const elemento = document.getElementById(elementId);
        if (!elemento) return;
        
        const valorActual = parseInt(elemento.textContent) || 0;
        
        if (valorActual === nuevoValor) return;
        
        elemento.classList.add('updating');
        
        setTimeout(() => {
            elemento.textContent = nuevoValor;
            elemento.classList.remove('updating');
            elemento.classList.add('updated');
            
            setTimeout(() => {
                elemento.classList.remove('updated');
            }, 500);
        }, 300);
    }
    
    // Mostrar mensaje en popup
    mostrarMensajePopup(mensaje, tipo = 'success', autoOcultar = true) {
        const contenedor = document.getElementById('popupMensajes');
        if (!contenedor) return;
        
        const mensajeDiv = document.createElement('div');
        mensajeDiv.className = `mensaje-popup ${tipo}`;
        mensajeDiv.innerHTML = `
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${mensaje}</span>
        `;
        
        contenedor.innerHTML = '';
        contenedor.appendChild(mensajeDiv);
        
        if (autoOcultar) {
            setTimeout(() => {
                mensajeDiv.style.opacity = '0';
                mensajeDiv.style.transform = 'translateY(-10px)';
                mensajeDiv.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    if (mensajeDiv.parentNode) {
                        mensajeDiv.parentNode.removeChild(mensajeDiv);
                    }
                }, 300);
            }, 3000);
        }
    }
    
    // Limpiar mensajes del popup
    limpiarMensajesPopup() {
        const contenedor = document.getElementById('popupMensajes');
        if (contenedor) {
            contenedor.innerHTML = '';
        }
    }
    
    // Método para debugging - mostrar datos de ejemplo
    mostrarDatosEjemplo() {
        console.log('🛠️ Mostrando datos de ejemplo para debugging');
        
        const permisosEjemplo = [
            { id: 1, nombre: 'crear_usuarios', activo: 1, fecha_asignacion: '2024-01-15', nombre_formateado: 'Crear Usuarios', descripcion: 'Permite crear nuevos usuarios en el sistema' },
            { id: 2, nombre: 'editar_usuarios', activo: 1, fecha_asignacion: '2024-01-15', nombre_formateado: 'Editar Usuarios', descripcion: 'Permite editar información de usuarios existentes' },
            { id: 3, nombre: 'eliminar_usuarios', activo: 0, fecha_asignacion: null, nombre_formateado: 'Eliminar Usuarios', descripcion: 'Permite eliminar usuarios del sistema' },
            { id: 4, nombre: 'ver_reportes', activo: 1, fecha_asignacion: '2024-02-20', nombre_formateado: 'Ver Reportes', descripcion: 'Permite ver reportes y estadísticas del sistema' },
            { id: 5, nombre: 'gestionar_inventario', activo: 0, fecha_asignacion: null, nombre_formateado: 'Gestionar Inventario', descripcion: 'Permite gestionar el inventario de productos' },
            { id: 6, nombre: 'ver_dashboard', activo: 1, fecha_asignacion: '2024-01-10', nombre_formateado: 'Ver Dashboard', descripcion: 'Permite ver el panel principal de control' },
            { id: 7, nombre: 'configurar_sistema', activo: 0, fecha_asignacion: null, nombre_formateado: 'Configurar Sistema', descripcion: 'Permite configurar ajustes del sistema' }
        ];
        
        const estadisticasEjemplo = {
            permisos_asignados: 4,
            total_permisos_disponibles: 7
        };
        
        this.renderizarPermisos(permisosEjemplo, null, estadisticasEjemplo);
        this.mostrarMensajePopup('⚠️ Mostrando datos de ejemplo - Verifica la conexión con el servidor', 'error');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando PermisosManager...');
    window.permisosManager = new PermisosManager();
});