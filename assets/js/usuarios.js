// assets/js/usuarios.js - VERSIÓN COMPLETA Y CORREGIDA

class UsuariosManager {
    constructor() {
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        this.currentUserId = document.querySelector('meta[name="current-user-id"]')?.getAttribute('content') || '0';
        this.modalOpen = false;
        this.currentModalType = null;
        this.currentEditId = null;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.updateInterval = null;
        this.init();
    }
    
    init() {
        this.createModalsHTML();
        this.setupEventListeners();
        this.setupDeleteButtons();
        this.setupSearch();
        this.setupStatusSwitches();
        this.setupEditButtons();
        this.setupResetPasswordButtons();
        // setupPermisosButtons() ELIMINADO - Ahora está en permisos.js
        this.setupTableSorting();
        this.startAutoUpdate();
    }
    
    // Iniciar actualización automática de estadísticas
    startAutoUpdate() {
        // Limpiar intervalo anterior si existe
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
        
        // Actualizar estadísticas cada 30 segundos
        this.updateInterval = setInterval(() => {
            this.updateStats();
        }, 30000);
        
        // Actualizar inmediatamente
        this.updateStats();
    }
    
    // Actualizar estadísticas
    async updateStats() {
        try {
            const response = await fetch(`${this.baseUrl}/usuarios/get-stats`);
            if (!response.ok) return;
            
            const result = await response.json();
            if (result.success && result.stats) {
                this.updateStatsDisplay(result.stats);
            }
        } catch (error) {
            console.log('Error actualizando estadísticas:', error);
        }
    }
    
    // Actualizar display de estadísticas
    updateStatsDisplay(stats) {
        // Actualizar contador de usuarios activos
        const activeUsersElement = document.getElementById('activeUsersCount');
        if (activeUsersElement && stats.activos !== undefined) {
            // Solo actualizar si el valor es diferente
            const currentValue = parseInt(activeUsersElement.textContent) || 0;
            if (currentValue !== stats.activos) {
                activeUsersElement.textContent = stats.activos;
            }
        }
        
        // Actualizar contador de administradores
        const adminsElement = document.getElementById('adminsCount');
        if (adminsElement && stats.administradores !== undefined) {
            adminsElement.textContent = stats.administradores || '0';
        }
        
        // Actualizar contador de logins hoy
        const loginsTodayElement = document.getElementById('loginsTodayCount');
        if (loginsTodayElement && stats.logins_hoy !== undefined) {
            loginsTodayElement.textContent = stats.logins_hoy || '0';
        }
        
        // Actualizar contadores de badges
        const adminsBadge = document.getElementById('adminsBadgeCount');
        if (adminsBadge && stats.administradores !== undefined) {
            adminsBadge.textContent = stats.administradores;
        }
        
        const cocinaBadge = document.getElementById('cocinaBadgeCount');
        if (cocinaBadge && stats.cocina !== undefined) {
            cocinaBadge.textContent = stats.cocina;
        }
        
        const inventarioBadge = document.getElementById('inventarioBadgeCount');
        if (inventarioBadge && stats.inventario !== undefined) {
            inventarioBadge.textContent = stats.inventario;
        }
        
        const meserosBadge = document.getElementById('meserosBadgeCount');
        if (meserosBadge && stats.meseros !== undefined) {
            meserosBadge.textContent = stats.meseros;
        }
        
        // Actualizar filtro count
        const filterCount = document.querySelector('.filter-count');
        if (filterCount && stats.total !== undefined) {
            filterCount.textContent = stats.total;
        }
    }
    
    // Configurar ordenamiento de tabla
    setupTableSorting() {
        const headers = document.querySelectorAll('.usuarios-table thead th');
        headers.forEach((header, index) => {
            if (header.classList.contains('sortable')) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => this.sortTable(index));
            }
        });
    }
    
    // Ordenar tabla
    sortTable(columnIndex) {
        const table = document.querySelector('.usuarios-table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Si es la misma columna, invertir dirección
        if (this.sortColumn === columnIndex) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = columnIndex;
            this.sortDirection = 'asc';
        }
        
        // Remover indicadores anteriores
        document.querySelectorAll('.sort-indicator').forEach(ind => ind.remove());
        
        // Agregar indicador a la columna actual
        const header = table.querySelector(`thead th:nth-child(${columnIndex + 1})`);
        const indicator = document.createElement('span');
        indicator.className = 'sort-indicator';
        indicator.innerHTML = this.sortDirection === 'asc' ? ' ↑' : ' ↓';
        indicator.style.marginLeft = '5px';
        indicator.style.color = '#3498db';
        header.appendChild(indicator);
        
        // Ordenar filas
        rows.sort((a, b) => {
            const cellA = a.cells[columnIndex];
            const cellB = b.cells[columnIndex];
            
            let valueA = cellA.textContent.trim().toLowerCase();
            let valueB = cellB.textContent.trim().toLowerCase();
            
            // Para columnas especiales
            if (columnIndex === 0) { // Usuario (tiene imagen)
                valueA = cellA.querySelector('.usuario-username')?.textContent.trim().toLowerCase() || valueA;
                valueB = cellB.querySelector('.usuario-username')?.textContent.trim().toLowerCase() || valueB;
            } else if (columnIndex === 3) { // Rol (tiene badge)
                valueA = cellA.querySelector('.badge')?.textContent.trim().toLowerCase() || valueA;
                valueB = cellB.querySelector('.badge')?.textContent.trim().toLowerCase() || valueB;
            } else if (columnIndex === 4) { // Estado (tiene switch)
                const switchA = cellA.querySelector('.status-switch');
                const switchB = cellB.querySelector('.status-switch');
                valueA = switchA ? (switchA.checked ? 'activo' : 'inactivo') : valueA;
                valueB = switchB ? (switchB.checked ? 'activo' : 'inactivo') : valueB;
            } else if (columnIndex === 5) { // Último Login (tiene clase)
                const timeA = cellA.querySelector('.login-time');
                const timeB = cellB.querySelector('.login-time');
                valueA = timeA ? timeA.getAttribute('data-sort') || timeA.textContent.trim().toLowerCase() : valueA;
                valueB = timeB ? timeB.getAttribute('data-sort') || timeB.textContent.trim().toLowerCase() : valueB;
            }
            
            // Comparar
            if (valueA < valueB) return this.sortDirection === 'asc' ? -1 : 1;
            if (valueA > valueB) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        
        // Reordenar filas en la tabla
        rows.forEach(row => tbody.appendChild(row));
    }
    
    // Crear HTML de los modales
    createModalsHTML() {
        // Modal para crear usuario
        if (!document.getElementById('modalAgregarUsuario')) {
            const modalHTML = `
                <div class="modal-overlay" id="modalAgregarUsuario">
                    <div class="modal">
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="fas fa-user-plus"></i>
                                <span>Agregar Nuevo Usuario</span>
                            </h3>
                            <button class="btn-close-modal" id="closeModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="modal-body">
                            <div id="modalMessages"></div>
                            
                            <form id="formAgregarUsuario">
                                <div class="form-group">
                                    <label for="modalNombre" class="form-label">Nombre Completo *</label>
                                    <input type="text"
                                           id="modalNombre"
                                           name="nombre"
                                           class="form-control"
                                           required
                                           placeholder="Ej: Juan Pérez">
                                </div>

                                <div class="form-group">
                                    <label for="modalUsername" class="form-label">Nombre de Usuario *</label>
                                    <input type="text"
                                           id="modalUsername"
                                           name="username"
                                           class="form-control"
                                           required
                                           readonly
                                           style="background:#ecf0f1;cursor:not-allowed;color:#7f8c8d;"
                                           placeholder="Se llena automáticamente con el documento">
                                </div>

                                <div class="form-group">
                                    <label for="modalDocumento" class="form-label">
                                        Documento de Identidad *
                                        <small style="font-weight:400;color:#7f8c8d;margin-left:6px;">
                                            <i class="fas fa-info-circle"></i> Se usará para iniciar sesión
                                        </small>
                                    </label>
                                    <input type="text"
                                           id="modalDocumento"
                                           class="form-control"
                                           required
                                           placeholder="Ej: 12345678"
                                           maxlength="30">
                                </div>
                                
                                <div class="form-group">
                                    <label for="modalEmail" class="form-label">Email *</label>
                                    <input type="email" 
                                           id="modalEmail" 
                                           name="email" 
                                           class="form-control" 
                                           required
                                           placeholder="Ej: juan@ejemplo.com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="modalPassword" class="form-label">Contraseña *</label>
                                    <div class="password-container">
                                        <input type="password" 
                                               id="modalPassword" 
                                               name="password" 
                                               class="form-control" 
                                               required
                                               placeholder="Mínimo 6 caracteres">
                                        <button type="button" class="password-toggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="modalPasswordConfirm" class="form-label">Confirmar Contraseña *</label>
                                    <div class="password-container">
                                        <input type="password" 
                                               id="modalPasswordConfirm" 
                                               name="password_confirmation" 
                                               class="form-control" 
                                               required
                                               placeholder="Repite la contraseña">
                                        <button type="button" class="password-toggle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="modalRol" class="form-label">Rol *</label>
                                    <select id="modalRol" name="rol" class="form-select" required>
                                        <option value="">Selecciona un rol</option>
                                        <option value="cocina">Cocina</option>
                                        <option value="inventario">Inventario</option>
                                        <option value="mesero">Mesero</option>
                                        <option value="admin">Administrador</option>
                                    </select>
                                </div>
                                
                                <div class="switch-container">
                                    <label class="switch-label">Estado:</label>
                                    <label class="switch">
                                        <input type="checkbox" id="modalActivo" name="activo" checked>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </form>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" id="cancelModal">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </button>
                            <button type="button" class="btn-primary" id="submitModalForm">
                                <i class="fas fa-save"></i>
                                Guardar Usuario
                            </button>
                        </div>
                        
                        <div class="loading-overlay" id="modalLoading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
        
        // Modal para editar usuario
        if (!document.getElementById('modalEditarUsuario')) {
            const editModalHTML = `
                <div class="modal-overlay" id="modalEditarUsuario">
                    <div class="modal">
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="fas fa-edit"></i>
                                <span>Editar Usuario</span>
                            </h3>
                            <button class="btn-close-modal" id="closeEditModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="eu-tabs">
                            <button class="eu-tab eu-tab-active" data-tab="general">
                                <i class="fas fa-user"></i> General
                            </button>
                            <button class="eu-tab" data-tab="avanzado">
                                <i class="fas fa-shield-alt"></i> Avanzado
                            </button>
                        </div>

                        <div class="modal-body">
                            <div id="editModalMessages"></div>

                            <form id="formEditarUsuario">
                                <input type="hidden" id="editUserId" name="id">
                                <input type="hidden" id="editUserOriginalRol" name="original_rol">

                                <!-- Panel General -->
                                <div class="eu-panel" id="eu-panel-general">
                                    <div class="form-group">
                                        <label for="editNombre" class="form-label">Nombre Completo *</label>
                                        <input type="text" id="editNombre" name="nombre" class="form-control" required placeholder="Ej: Juan Pérez">
                                    </div>
                                    <div class="form-group">
                                        <label for="editUsername" class="form-label">Nombre de Usuario *</label>
                                        <input type="text" id="editUsername" name="username" class="form-control" required readonly style="background:#ecf0f1;cursor:not-allowed;">
                                    </div>
                                    <div class="form-group">
                                        <label for="editEmail" class="form-label">Email *</label>
                                        <input type="email" id="editEmail" name="email" class="form-control" required placeholder="Ej: juan@ejemplo.com">
                                    </div>
                                    <div class="form-group">
                                        <label for="editRol" class="form-label">Rol *</label>
                                        <select id="editRol" name="rol" class="form-select" required>
                                            <option value="">Selecciona un rol</option>
                                            <option value="cocina">Cocina</option>
                                            <option value="inventario">Inventario</option>
                                            <option value="mesero">Mesero</option>
                                            <option value="admin">Administrador</option>
                                        </select>
                                    </div>
                                    <div class="switch-container">
                                        <label class="switch-label">Estado:</label>
                                        <label class="switch">
                                            <input type="checkbox" id="editActivo" name="activo">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Panel Avanzado -->
                                <div class="eu-panel eu-panel-hidden" id="eu-panel-avanzado">
                                    <p class="eu-section-title"><i class="fas fa-clock"></i> Control de inicio de sesión</p>

                                    <div class="horario-opts">
                                        <label class="horario-opt">
                                            <input type="radio" name="lc_tipo" value="libre" checked>
                                            <span class="horario-opt-box">
                                                <i class="fas fa-unlock"></i>
                                                <strong>Libre</strong>
                                                <small>Sin restricciones</small>
                                            </span>
                                        </label>
                                        <label class="horario-opt">
                                            <input type="radio" name="lc_tipo" value="jornada">
                                            <span class="horario-opt-box">
                                                <i class="fas fa-briefcase"></i>
                                                <strong>Jornada laboral</strong>
                                                <small>Días y horario fijos</small>
                                            </span>
                                        </label>
                                        <label class="horario-opt">
                                            <input type="radio" name="lc_tipo" value="personalizado">
                                            <span class="horario-opt-box">
                                                <i class="fas fa-sliders-h"></i>
                                                <strong>Personalizado</strong>
                                                <small>Configuración manual</small>
                                            </span>
                                        </label>
                                    </div>

                                    <div id="lc-dias-wrap" class="eu-hidden">
                                        <label class="form-label" style="margin-bottom:8px;">Días permitidos</label>
                                        <div class="dia-btns">
                                            <button type="button" class="dia-btn" data-dia="lun">L</button>
                                            <button type="button" class="dia-btn" data-dia="mar">M</button>
                                            <button type="button" class="dia-btn" data-dia="mie">X</button>
                                            <button type="button" class="dia-btn" data-dia="jue">J</button>
                                            <button type="button" class="dia-btn" data-dia="vie">V</button>
                                            <button type="button" class="dia-btn" data-dia="sab">S</button>
                                            <button type="button" class="dia-btn" data-dia="dom">D</button>
                                        </div>
                                    </div>

                                    <div id="lc-horas-wrap" class="eu-hidden">
                                        <div class="lc-horas-row">
                                            <div class="form-group">
                                                <label class="form-label">Hora inicio</label>
                                                <input type="time" id="lc_hora_inicio" class="form-control" value="08:00">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Hora fin</label>
                                                <input type="time" id="lc_hora_fin" class="form-control" value="18:00">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="lc-jornada-presets" class="eu-hidden">
                                        <label class="form-label" style="margin-bottom:8px;">Jornada predefinida</label>
                                        <div class="horario-presets">
                                            <button type="button" class="preset-btn" data-dias="lun,mar,mie,jue,vie" data-hi="08:00" data-hf="17:00">L–V 8–17</button>
                                            <button type="button" class="preset-btn" data-dias="lun,mar,mie,jue,vie" data-hi="09:00" data-hf="18:00">L–V 9–18</button>
                                            <button type="button" class="preset-btn" data-dias="lun,mar,mie,jue,vie,sab" data-hi="08:00" data-hf="14:00">L–S 8–14</button>
                                            <button type="button" class="preset-btn" data-dias="lun,mar,mie,jue,vie,sab,dom" data-hi="00:00" data-hf="23:59">Todos los días</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" id="cancelEditModal">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </button>
                            <button type="button" class="btn-primary" id="submitEditForm">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                        </div>

                        <div class="loading-overlay" id="editModalLoading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', editModalHTML);

            // Tab switching
            document.querySelectorAll('.eu-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.eu-tab').forEach(b => b.classList.remove('eu-tab-active'));
                    document.querySelectorAll('.eu-panel').forEach(p => p.classList.add('eu-panel-hidden'));
                    btn.classList.add('eu-tab-active');
                    document.getElementById('eu-panel-' + btn.dataset.tab).classList.remove('eu-panel-hidden');
                });
            });

            // Login config radio logic
            document.querySelectorAll('input[name="lc_tipo"]').forEach(radio => {
                radio.addEventListener('change', () => this._updateLcUI());
            });

            // Day toggle buttons
            document.querySelectorAll('.dia-btn').forEach(btn => {
                btn.addEventListener('click', () => btn.classList.toggle('dia-btn-active'));
            });

            // Preset buttons
            document.querySelectorAll('.preset-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const dias = btn.dataset.dias.split(',');
                    document.querySelectorAll('.dia-btn').forEach(d => {
                        d.classList.toggle('dia-btn-active', dias.includes(d.dataset.dia));
                    });
                    document.getElementById('lc_hora_inicio').value = btn.dataset.hi;
                    document.getElementById('lc_hora_fin').value    = btn.dataset.hf;
                });
            });
        }

        // Modal de confirmación para eliminar
        if (!document.getElementById('modalConfirmacion')) {
            const confirmModalHTML = `
                <div class="modal-overlay" id="modalConfirmacion">
                    <div class="modal" style="max-width: 400px;">
                        <div class="modal-header">
                            <h3 class="modal-title">
                                <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i>
                                <span>Confirmar Eliminación</span>
                            </h3>
                            <button class="btn-close-modal" id="closeConfirmModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="modal-body">
                            <p style="color: #7f8c8d; line-height: 1.6;">
                                ¿Estás seguro de que deseas eliminar a <strong id="usuarioNombreEliminar"></strong>?
                                <br><br>
                                <small style="color: #e74c3c;">
                                    <i class="fas fa-info-circle"></i>
                                    Esta acción no se puede deshacer.
                                </small>
                            </p>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn-secondary" id="cancelEliminar">
                                Cancelar
                            </button>
                            <button type="button" class="btn-primary" id="confirmEliminar" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                                <i class="fas fa-trash"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', confirmModalHTML);
        }
    }
    
    // Configurar event listeners
    setupEventListeners() {
        // Abrir modal de crear
        const openModalBtn = document.getElementById('openModalBtn');
        if (openModalBtn) {
            openModalBtn.addEventListener('click', () => this.openModal('crear'));
        }
        
        // Cerrar modales
        this.setupModalCloseEvents();
        
        // Enviar formularios
        this.setupFormSubmitEvents();
        
        // Configurar toggles de contraseña
        this.setupPasswordToggle();
        
        // Cerrar modales con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modalOpen) {
                this.closeCurrentModal();
            }
        });
        
        // Cerrar modales al hacer clic fuera
        this.setupModalClickOutside();
    }
    
    setupModalCloseEvents() {
        // Modal crear
        const closeModalBtn = document.getElementById('closeModal');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => this.closeModal('crear'));
        }
        
        const cancelModalBtn = document.getElementById('cancelModal');
        if (cancelModalBtn) {
            cancelModalBtn.addEventListener('click', () => this.closeModal('crear'));
        }
        
        // Modal editar
        const closeEditModalBtn = document.getElementById('closeEditModal');
        if (closeEditModalBtn) {
            closeEditModalBtn.addEventListener('click', () => this.closeModal('editar'));
        }
        
        const cancelEditModalBtn = document.getElementById('cancelEditModal');
        if (cancelEditModalBtn) {
            cancelEditModalBtn.addEventListener('click', () => this.closeModal('editar'));
        }
    }
    
    setupFormSubmitEvents() {
        // Formulario crear
        const submitModalBtn = document.getElementById('submitModalForm');
        if (submitModalBtn) {
            submitModalBtn.addEventListener('click', () => this.submitModalForm('crear'));
        }
        
        // Formulario editar
        const submitEditBtn = document.getElementById('submitEditForm');
        if (submitEditBtn) {
            submitEditBtn.addEventListener('click', () => this.submitModalForm('editar'));
        }
    }
    
    setupModalClickOutside() {
        // Deshabilitado: los modales no se cierran al hacer clic fuera
    }
    
    // Vincular documento → username (se llama al abrir modal crear)
    setupDocumentoSync() {
        const docInput = document.getElementById('modalDocumento');
        const usernameInput = document.getElementById('modalUsername');
        if (!docInput || !usernameInput || docInput._syncBound) return;
        docInput._syncBound = true;
        docInput.addEventListener('input', function() {
            // Solo permite alfanumérico y guiones para el username
            usernameInput.value = this.value.trim().replace(/\s+/g, '');
        });
    }

    // Abrir modal
    openModal(type = 'crear', userId = null) {
        const modalId = type === 'crear' ? 'modalAgregarUsuario' : 'modalEditarUsuario';
        const modal = document.getElementById(modalId);

        if (modal) {
            modal.classList.add('active');
            this.modalOpen = true;
            this.currentModalType = type;
            document.body.style.overflow = 'hidden';

            if (type === 'crear') {
                this.clearModalForm('crear');
                this.setupDocumentoSync();
                setTimeout(() => {
                    const firstInput = document.getElementById('modalNombre');
                    if (firstInput) firstInput.focus();
                }, 300);
            } else if (type === 'editar' && userId) {
                this.currentEditId = userId;
                this.loadUserData(userId);
            }
        }
    }
    
    // Cerrar modal específico
    closeModal(type = 'crear') {
        const modalId = type === 'crear' ? 'modalAgregarUsuario' : 'modalEditarUsuario';
        const modal = document.getElementById(modalId);
        
        if (modal) {
            modal.classList.remove('active');
            this.modalOpen = false;
            this.currentModalType = null;
            document.body.style.overflow = '';
        }
    }
    
    // Cargar datos del usuario para editar
    async loadUserData(userId) {
        this.showLoading('editar', true);
        
        try {
            const response = await fetch(`${this.baseUrl}/usuarios/get/${userId}`);
            
            if (!response.ok) {
                throw new Error(`Error en la respuesta del servidor: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.fillEditForm(result.data);
            } else {
                this.showModalMessage('Error al cargar datos del usuario: ' + (result.message || 'Error desconocido'), 'error', 'editar');
            }
        } catch (error) {
            console.error('Error cargando datos del usuario:', error);
            this.showModalMessage('Error de conexión: ' + error.message, 'error', 'editar');
        } finally {
            this.showLoading('editar', false);
            
            // Enfocar primer campo
            setTimeout(() => {
                const firstInput = document.getElementById('editNombre');
                if (firstInput) firstInput.focus();
            }, 300);
        }
    }
    
    // Llenar formulario de edición
    fillEditForm(userData) {
        // Limpiar mensajes previos
        this.clearModalMessages('editar');
        
        // Llenar campos básicos
        document.getElementById('editUserId').value = userData.id || '';
        document.getElementById('editNombre').value = userData.nombre || '';
        document.getElementById('editUsername').value = userData.username || '';
        document.getElementById('editEmail').value = userData.email || '';
        document.getElementById('editUserOriginalRol').value = userData.rol || '';
        
        // Seleccionar rol
        const rolSelect = document.getElementById('editRol');
        if (rolSelect) {
            rolSelect.value = userData.rol || 'cocina';
            
            // Si es el usuario actual, deshabilitar cambio de rol
            if (userData.id == this.currentUserId) {
                rolSelect.disabled = true;
                rolSelect.title = 'No puedes cambiar tu propio rol';
                rolSelect.style.backgroundColor = '#ecf0f1';
                rolSelect.style.cursor = 'not-allowed';
            } else {
                rolSelect.disabled = false;
                rolSelect.title = '';
                rolSelect.style.backgroundColor = '';
                rolSelect.style.cursor = '';
            }
        }
        
        // Configurar switch de estado
        const activoCheckbox = document.getElementById('editActivo');
        if (activoCheckbox) {
            activoCheckbox.checked = userData.activo == 1;

            // Si es el usuario actual, deshabilitar cambio de estado
            if (userData.id == this.currentUserId) {
                activoCheckbox.disabled = true;
                activoCheckbox.parentElement.style.opacity = '0.5';
                activoCheckbox.parentElement.style.cursor = 'not-allowed';
            } else {
                activoCheckbox.disabled = false;
                activoCheckbox.parentElement.style.opacity = '';
                activoCheckbox.parentElement.style.cursor = '';
            }
        }

        // Volver siempre a la pestaña general al abrir
        document.querySelectorAll('.eu-tab').forEach(b => b.classList.remove('eu-tab-active'));
        document.querySelectorAll('.eu-panel').forEach(p => p.classList.add('eu-panel-hidden'));
        const tabGen = document.querySelector('.eu-tab[data-tab="general"]');
        if (tabGen) tabGen.classList.add('eu-tab-active');
        const panelGen = document.getElementById('eu-panel-general');
        if (panelGen) panelGen.classList.remove('eu-panel-hidden');

        // Poblar pestaña avanzada con login_config
        const cfg = (typeof userData.login_config === 'string')
            ? JSON.parse(userData.login_config || 'null')
            : (userData.login_config || null);

        const tipo = (cfg && cfg.tipo) ? cfg.tipo : 'libre';
        const radioEl = document.querySelector(`input[name="lc_tipo"][value="${tipo}"]`);
        if (radioEl) radioEl.checked = true;

        // Días
        const diasActivos = (cfg && cfg.dias) ? cfg.dias : [];
        document.querySelectorAll('.dia-btn').forEach(btn => {
            btn.classList.toggle('dia-btn-active', diasActivos.includes(btn.dataset.dia));
        });

        // Horas
        const hi = (cfg && cfg.hora_inicio) ? cfg.hora_inicio : '08:00';
        const hf = (cfg && cfg.hora_fin)    ? cfg.hora_fin    : '18:00';
        const hiEl = document.getElementById('lc_hora_inicio');
        const hfEl = document.getElementById('lc_hora_fin');
        if (hiEl) hiEl.value = hi;
        if (hfEl) hfEl.value = hf;

        this._updateLcUI();
    }
    
    // Configurar botones de edición
    setupEditButtons() {
        const editButtons = document.querySelectorAll('.btn-editar');
        
        editButtons.forEach(button => {
            // Remover event listeners previos
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Agregar nuevo event listener
            newButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const userId = newButton.getAttribute('data-user-id') || 
                              newButton.closest('tr')?.getAttribute('data-user-id');
                
                if (userId && userId !== 'undefined') {
                    this.openModal('editar', userId);
                } else {
                    console.error('No se pudo obtener el ID del usuario');
                    this.showModalMessage('Error: No se pudo obtener el ID del usuario', 'error', 'editar');
                }
            });
        });
    }
    
    // Método para enviar formulario
    async submitModalForm(type = 'crear') {
        const formId = type === 'crear' ? 'formAgregarUsuario' : 'formEditarUsuario';
        const form = document.getElementById(formId);
        
        if (!form) {
            console.error('Formulario no encontrado:', formId);
            return;
        }
        
        // Validar formulario
        if (!this.validateModalForm(type)) {
            return;
        }
        
        // Mostrar loading
        this.showLoading(type, true);
        
        try {
            // Preparar datos como FormData
            const formData = new FormData(form);
            
            // Convertir checkbox de activo a 1/0
            const activoCheckbox = form.querySelector('[name="activo"]');
            if (activoCheckbox) {
                formData.set('activo', activoCheckbox.checked ? '1' : '0');
            }
            
            // Determinar la URL
            let url;
            if (type === 'crear') {
                url = `${this.baseUrl}/usuarios/crear`;
            } else {
                url = `${this.baseUrl}/usuarios/update`;
            }
            
            // Enviar datos como JSON
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Si es edición, guardar también login_config
                if (type === 'editar') {
                    const userId = parseInt(document.getElementById('editUserId').value, 10);
                    await this._saveLoginConfig(userId);
                }

                this.showModalMessage(result.message || 'Operación completada exitosamente', 'success', type);

                setTimeout(() => {
                    this.closeModal(type);
                    window.location.reload();
                }, 1500);
            } else {
                this.showModalMessage(result.message || 'Error en la operación', 'error', type);

                if (result.errors) {
                    this.showFieldErrors(result.errors, type);
                }
            }
        } catch (error) {
            console.error('Error en submitModalForm:', error);
            this.showModalMessage('Error de conexión: ' + error.message, 'error', type);
        } finally {
            this.showLoading(type, false);
        }
    }

    async _saveLoginConfig(userId) {
        const tipo = document.querySelector('input[name="lc_tipo"]:checked')?.value || 'libre';
        let config = null;
        if (tipo !== 'libre') {
            const dias = [...document.querySelectorAll('.dia-btn.dia-btn-active')].map(b => b.dataset.dia);
            config = {
                tipo,
                dias,
                hora_inicio: document.getElementById('lc_hora_inicio')?.value || '08:00',
                hora_fin:    document.getElementById('lc_hora_fin')?.value    || '18:00'
            };
        }
        try {
            await fetch(`${this.baseUrl}/usuarios/login-config`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: userId, login_config: config })
            });
        } catch (e) { /* non-fatal */ }
    }
    
    // Validar formulario
    validateModalForm(type = 'crear') {
        const formId = type === 'crear' ? 'formAgregarUsuario' : 'formEditarUsuario';
        const form = document.getElementById(formId);
        
        if (!form) return false;
        
        let isValid = true;
        this.clearFieldErrors(type);
        
        // Validar campos requeridos (excluir username readonly — se valida vía documento)
        const requiredFields = form.querySelectorAll('input[required]:not([readonly]), select[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Este campo es requerido', type);
                isValid = false;
            }
        });

        // Validar documento de identidad (solo en crear, es el username real)
        if (type === 'crear') {
            const docField = document.getElementById('modalDocumento');
            if (docField !== null && !docField.value.trim()) {
                this.showFieldError(docField, 'El documento de identidad es requerido', type);
                isValid = false;
            }
        }
        
        // Validar email
        const emailField = type === 'crear' ? 
            document.getElementById('modalEmail') : 
            document.getElementById('editEmail');
            
        if (emailField && emailField.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value)) {
                this.showFieldError(emailField, 'Por favor ingresa un email válido', type);
                isValid = false;
            }
        }
        
        // Validar contraseña solo para crear
        if (type === 'crear') {
            const passwordField = document.getElementById('modalPassword');
            if (passwordField && passwordField.value && passwordField.value.length < 6) {
                this.showFieldError(passwordField, 'La contraseña debe tener al menos 6 caracteres', type);
                isValid = false;
            }
            
            // Validar confirmación de contraseña
            const passwordField2 = document.getElementById('modalPasswordConfirm');
            if (passwordField && passwordField2 && passwordField.value !== passwordField2.value) {
                this.showFieldError(passwordField2, 'Las contraseñas no coinciden', type);
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    // Mostrar error en campo
    showFieldError(field, message, type = 'crear') {
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        errorDiv.style.cssText = `
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            padding: 8px 12px;
            background: #f8d7da;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        
        field.parentNode.insertBefore(errorDiv, field.nextSibling);
    }
    
    clearFieldErrors(type = 'crear') {
        const formId = type === 'crear' ? 'formAgregarUsuario' : 'formEditarUsuario';
        const form = document.getElementById(formId);
        
        if (form) {
            const inputs = form.querySelectorAll('.form-control, .form-select');
            inputs.forEach(input => {
                input.classList.remove('error');
                const errorDiv = input.parentNode.querySelector('.field-error');
                if (errorDiv) errorDiv.remove();
            });
        }
    }
    
    showModalMessage(message, messageType = 'error', type = 'crear') {
        const messagesContainerId = type === 'crear' ? 'modalMessages' : 'editModalMessages';
        const messagesContainer = document.getElementById(messagesContainerId);
        
        if (!messagesContainer) return;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${messageType}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${messageType === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        messagesContainer.innerHTML = '';
        messagesContainer.appendChild(alertDiv);
    }
    
    clearModalMessages(type = 'crear') {
        const messagesContainerId = type === 'crear' ? 'modalMessages' : 'editModalMessages';
        const messagesContainer = document.getElementById(messagesContainerId);
        
        if (messagesContainer) {
            messagesContainer.innerHTML = '';
        }
    }
    
    clearModalForm(type = 'crear') {
        const formId = type === 'crear' ? 'formAgregarUsuario' : 'formEditarUsuario';
        const form = document.getElementById(formId);
        
        if (form) {
            form.reset();
            this.clearModalMessages(type);
            this.clearFieldErrors(type);
            
            // Asegurar que el checkbox de activo esté checked por defecto en crear
            if (type === 'crear') {
                const activoCheckbox = document.getElementById('modalActivo');
                if (activoCheckbox) activoCheckbox.checked = true;

                // Limpiar campos documento/username explícitamente
                const docInput = document.getElementById('modalDocumento');
                const usernameInput = document.getElementById('modalUsername');
                if (docInput) docInput.value = '';
                if (usernameInput) usernameInput.value = '';
            }
        }
    }
    
    showLoading(type = 'crear', show = true) {
        const loadingId = type === 'crear' ? 'modalLoading' : 'editModalLoading';
        const loadingOverlay = document.getElementById(loadingId);
        
        if (loadingOverlay) {
            if (show) {
                loadingOverlay.classList.add('active');
            } else {
                loadingOverlay.classList.remove('active');
            }
        }
    }
    
    setupPasswordToggle() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const input = button.parentNode.querySelector('input');
                if (input) {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                    }
                }
            });
        });
    }
    
    // ===== CONFIGURACIÓN DE SWITCHES DE ESTADO =====
    
    // Configurar switches de estado en la tabla
    setupStatusSwitches() {
        const statusSwitches = document.querySelectorAll('.status-switch');
        
        statusSwitches.forEach(switchEl => {
            // Remover event listeners previos
            const newSwitch = switchEl.cloneNode(true);
            switchEl.parentNode.replaceChild(newSwitch, switchEl);
            
            newSwitch.addEventListener('change', (e) => {
                const userId = newSwitch.getAttribute('data-user-id');
                const isActive = newSwitch.checked;
                const userName = newSwitch.getAttribute('data-user-name') || 'este usuario';
                
                // Verificar si es el usuario actual
                if (userId === this.currentUserId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No puedes cambiar tu propio estado',
                        text: 'Para cambiar el estado de tu cuenta, contacta a otro administrador.',
                        confirmButtonText: 'Entendido'
                    });
                    newSwitch.checked = !isActive;
                    return;
                }
                
                // Verificar si es usuario ID 1 (super admin)
                if (userId === '1') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Usuario protegido',
                        text: 'No puedes cambiar el estado del usuario administrador principal.',
                        confirmButtonText: 'Entendido'
                    });
                    newSwitch.checked = !isActive;
                    return;
                }
                
                // Mostrar confirmación
                const action = isActive ? 'activar' : 'desactivar';
                const title = isActive ? '¿Activar usuario?' : '¿Desactivar usuario?';
                const text = `¿Estás seguro de que deseas ${action} a ${userName}?`;
                
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, ' + action,
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.updateUserStatus(userId, isActive ? 1 : 0);
                    } else {
                        newSwitch.checked = !isActive;
                    }
                });
            });
        });
    }
    
    // Actualizar estado del usuario - VERSIÓN CORREGIDA
    async updateUserStatus(userId, status) {
        try {
            const response = await fetch(`${this.baseUrl}/usuarios/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: userId,
                    activo: status
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Mostrar notificación de éxito
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message || 'Estado actualizado correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // ========== ACTUALIZAR CONTADOR EN TIEMPO REAL ==========
                const activeUsersElement = document.getElementById('activeUsersCount');
                
                if (activeUsersElement) {
                    // Calcular el nuevo valor
                    const currentValue = parseInt(activeUsersElement.textContent) || 0;
                    const newValue = status == 1 ? currentValue + 1 : currentValue - 1;
                    
                    // Animar el cambio
                    this.animateCounter(activeUsersElement, newValue);
                }
                
                // Actualizar todas las estadísticas desde el servidor
                setTimeout(() => {
                    this.updateStats();
                }, 1000);
                
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message || 'Error al actualizar el estado'
                });
                // Revertir switch
                const switchEl = document.querySelector(`.status-switch[data-user-id="${userId}"]`);
                if (switchEl) {
                    switchEl.checked = status == 0;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
            // Revertir switch
            const switchEl = document.querySelector(`.status-switch[data-user-id="${userId}"]`);
            if (switchEl) {
                switchEl.checked = status == 0;
            }
        }
    }
    
    // Animar contador - VERSIÓN CORREGIDA (sin bucles infinitos)
    animateCounter(element, newValue) {
        const currentValue = parseInt(element.textContent) || 0;
        
        if (currentValue === newValue) return;
        
        // Detener cualquier animación previa
        if (element.animationTimer) {
            clearInterval(element.animationTimer);
        }
        
        // Calcular diferencia
        const diff = newValue - currentValue;
        if (diff === 0) return;
        
        const step = diff > 0 ? 1 : -1;
        const stepTime = 50; // ms entre cada paso
        const totalSteps = Math.abs(diff);
        
        // Animar el contador
        let current = currentValue;
        let stepsDone = 0;
        
        element.animationTimer = setInterval(() => {
            current += step;
            stepsDone++;
            
            element.textContent = current;
            
            // Cambiar color temporalmente
            element.style.color = step > 0 ? '#2ecc71' : '#e74c3c';
            
            if (stepsDone >= totalSteps) {
                clearInterval(element.animationTimer);
                element.animationTimer = null;
                element.textContent = newValue;
                
                // Restaurar color original después de un breve momento
                setTimeout(() => {
                    element.style.color = '#2c3e50';
                    
                    // Agregar animación de pulso
                    element.classList.add('updated');
                    setTimeout(() => {
                        element.classList.remove('updated');
                    }, 500);
                }, 300);
            }
        }, stepTime);
    }
    
    // ===== CONFIGURACIÓN DE BOTONES DE ELIMINAR =====
    
    // Configurar botones de eliminar
    setupDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.btn-eliminar-form');
        
        deleteButtons.forEach(button => {
            const userId = button.getAttribute('data-user-id') || 
                          button.closest('form')?.querySelector('input[name="id"]')?.value;
            
            // Deshabilitar si es usuario ID 1 (superadmin) o el usuario actual
            if (userId === '1' || userId === this.currentUserId) {
                button.disabled = true;
                button.classList.add('disabled');
                if (userId === '1') {
                    button.title = 'No se puede eliminar al administrador principal';
                } else {
                    button.title = 'No puedes eliminar tu propia cuenta';
                }
                return;
            }
            
            // Remover event listeners previos
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', (e) => {
                e.preventDefault();
                
                const form = newButton.closest('form');
                const usuarioNombre = newButton.getAttribute('data-usuario-nombre') || 'este usuario';
                
                this.showDeleteModal(usuarioNombre, () => {
                    form.submit();
                });
            });
        });
    }
    
    showDeleteModal(usuarioNombre, callback) {
        const modal = document.getElementById('modalConfirmacion');
        const nombreSpan = document.getElementById('usuarioNombreEliminar');
        const confirmBtn = document.getElementById('confirmEliminar');
        const cancelBtn = document.getElementById('cancelEliminar');
        const closeBtn = document.getElementById('closeConfirmModal');
        
        if (!modal || !nombreSpan) return;
        
        nombreSpan.textContent = usuarioNombre;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        const confirmHandler = () => {
            if (callback) callback();
            this.hideDeleteModal();
        };
        
        const cancelHandler = () => {
            this.hideDeleteModal();
        };
        
        const closeHandler = () => {
            this.hideDeleteModal();
        };
        
        // Remover listeners previos
        confirmBtn.replaceWith(confirmBtn.cloneNode(true));
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        closeBtn.replaceWith(closeBtn.cloneNode(true));
        
        // Agregar nuevos listeners
        document.getElementById('confirmEliminar').addEventListener('click', confirmHandler);
        document.getElementById('cancelEliminar').addEventListener('click', cancelHandler);
        document.getElementById('closeConfirmModal').addEventListener('click', closeHandler);
    }
    
    hideDeleteModal() {
        const modal = document.getElementById('modalConfirmacion');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // ===== CONFIGURACIÓN DE BÚSQUEDA =====
    
    setupSearch() {
        const searchInput = document.querySelector('.table-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.usuarios-table tbody tr');
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
    }
    
    // ===== CONFIGURACIÓN DE RESTABLECER CONTRASEÑA =====
    
    // Configurar botones de restablecer contraseña
    setupResetPasswordButtons() {
        const resetButtons = document.querySelectorAll('.btn-reset-password');
        
        resetButtons.forEach(button => {
            // Remover event listeners previos
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const userId = newButton.getAttribute('data-user-id');
                const userName = newButton.getAttribute('data-user-name') || 'este usuario';
                
                // Verificar si es el usuario actual
                if (userId === this.currentUserId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No puedes cambiar tu propia contraseña aquí',
                        text: 'Usa la opción de "Cambiar contraseña" en tu perfil.',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                
                // Verificar si es usuario ID 1 (super admin)
                if (userId === '1') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Usuario protegido',
                        text: 'No puedes cambiar la contraseña del administrador principal.',
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                
                // Mostrar modal para restablecer contraseña
                this.showResetPasswordModal(userId, userName);
            });
        });
    }
    
    // Mostrar modal para restablecer contraseña
    showResetPasswordModal(userId, userName) {
        Swal.fire({
            title: 'Restablecer Contraseña',
            html: `
                <div style="text-align: left;">
                    <p>¿Deseas restablecer la contraseña de <strong>${userName}</strong>?</p>
                    <div style="margin-top: 15px;">
                        <label for="newPassword" style="display: block; margin-bottom: 5px; font-weight: bold;">Nueva contraseña:</label>
                        <input type="password" id="newPassword" class="swal2-input" placeholder="Mínimo 6 caracteres" style="width: 100%;">
                    </div>
                    <div style="margin-top: 10px;">
                        <label for="confirmPassword" style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar contraseña:</label>
                        <input type="password" id="confirmPassword" class="swal2-input" placeholder="Confirmar contraseña" style="width: 100%;">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Restablecer',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: () => {
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                // Validaciones
                if (!newPassword) {
                    Swal.showValidationMessage('Por favor ingresa una nueva contraseña');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    Swal.showValidationMessage('Las contraseñas no coinciden');
                    return false;
                }
                
                return { newPassword };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.resetUserPassword(userId, result.value.newPassword);
            }
        });
    }
    
    // Restablecer contraseña del usuario
    async resetUserPassword(userId, newPassword) {
        try {
            const response = await fetch(`${this.baseUrl}/usuarios/reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: userId,
                    password: newPassword
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message || 'Contraseña restablecida correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message || 'Error al restablecer la contraseña'
                });
            }
        } catch (error) {
            console.error('Error detallado en resetUserPassword:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor.'
            });
        }
    }
    
    // ===== MÉTODOS AUXILIARES =====
    
    showFieldErrors(errors, type = 'crear') {
        const formId = type === 'crear' ? 'formAgregarUsuario' : 'formEditarUsuario';
        const form = document.getElementById(formId);
        
        if (!form) return;
        
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field && errors[fieldName] && errors[fieldName][0]) {
                this.showFieldError(field, errors[fieldName][0], type);
            }
        });
    }
    
    closeCurrentModal() {
        if (this.currentModalType === 'crear') {
            this.closeModal('crear');
        } else if (this.currentModalType === 'editar') {
            this.closeModal('editar');
        }
    }

    _updateLcUI() {
        const tipo = document.querySelector('input[name="lc_tipo"]:checked')?.value || 'libre';
        const diasWrap   = document.getElementById('lc-dias-wrap');
        const horasWrap  = document.getElementById('lc-horas-wrap');
        const jornadaPre = document.getElementById('lc-jornada-presets');
        const hiEl       = document.getElementById('lc_hora_inicio');
        const hfEl       = document.getElementById('lc_hora_fin');
        if (!diasWrap) return;

        if (tipo === 'libre') {
            diasWrap.classList.add('eu-hidden');
            horasWrap.classList.add('eu-hidden');
            jornadaPre.classList.add('eu-hidden');
        } else if (tipo === 'jornada') {
            diasWrap.classList.remove('eu-hidden');
            horasWrap.classList.remove('eu-hidden');
            jornadaPre.classList.add('eu-hidden');
            // Horas bloqueadas: solo se muestran, no se editan
            if (hiEl) { hiEl.readOnly = true; hiEl.classList.add('lc-hora-readonly'); }
            if (hfEl) { hfEl.readOnly = true; hfEl.classList.add('lc-hora-readonly'); }
        } else {
            diasWrap.classList.remove('eu-hidden');
            horasWrap.classList.remove('eu-hidden');
            jornadaPre.classList.add('eu-hidden');
            // Horas editables en modo personalizado
            if (hiEl) { hiEl.readOnly = false; hiEl.classList.remove('lc-hora-readonly'); }
            if (hfEl) { hfEl.readOnly = false; hfEl.classList.remove('lc-hora-readonly'); }
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Cargar SweetAlert2 si no está cargado
    if (typeof Swal === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = function() {
            window.usuariosManager = new UsuariosManager();
        };
        document.head.appendChild(script);
    } else {
        window.usuariosManager = new UsuariosManager();
    }
});