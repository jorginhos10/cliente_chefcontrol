<?php
// vista/usuarios/index.php - ARCHIVO COMPLETO

require_once __DIR__ . '/../../config/security.php';

$titulo = 'Gestión de Usuarios - CHEFCONTROL';
$tituloHeader = 'Gestión de Usuarios';
$subtituloHeader = 'Administra los usuarios del sistema';
$paginaActual = 'usuarios';

// Definir las variables que faltan
$baseUrl = Config::getBaseUrl();
$basePath = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/usuarios.css">';
$cssExtra .= '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/permisos.css">';
$cssExtra .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
$jsExtra = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$jsExtra .= '<script src="' . $baseUrl . '/assets/js/usuarios.js"></script>';
$jsExtra .= '<script src="' . $baseUrl . '/assets/js/permisos.js"></script>';

// Calcular estadísticas
$totalUsuarios = count($usuarios);
$usuariosActivos = array_filter($usuarios, fn($u) => $u['activo'] == 1);
$admins = array_filter($usuarios, fn($u) => $u['rol'] == 'admin');
$cocina = array_filter($usuarios, fn($u) => $u['rol'] == 'cocina');
$inventario = array_filter($usuarios, fn($u) => $u['rol'] == 'inventario');
$meseros = array_filter($usuarios, fn($u) => $u['rol'] == 'mesero');

require_once __DIR__ . '/../complementos/header.php';
?>

<meta name="base-url" content="<?php echo $baseUrl; ?>">
<meta name="current-user-id" content="<?php echo $_SESSION['usuario_id'] ?? '0'; ?>">

<div class="usuarios-container">
    <div class="usuarios-header">
        <div class="usuarios-title-section">
            <h1>Gestión de Usuarios</h1>
            <p>Administra los usuarios y permisos del sistema</p>
        </div>
        <button id="openModalBtn" class="btn-open-modal">
            <i class="fas fa-user-plus"></i>
            Agregar Nuevo Usuario
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

    <div class="usuarios-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color: #3498db;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo $totalUsuarios; ?></div>
            <div class="stat-label">Total de Usuarios</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #2ecc71;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-number" id="activeUsersCount"><?php echo count($usuariosActivos); ?></div>
            <div class="stat-label">Usuarios Activos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #e74c3c;">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-number" id="adminsCount"><?php echo count($admins); ?></div>
            <div class="stat-label">Administradores</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="color: #f39c12;">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="stat-number" id="loginsTodayCount">
                <?php 
                $hoy = date('Y-m-d');
                $loginsHoy = array_filter($usuarios, function($u) use ($hoy) {
                    return $u['ultimo_login'] && date('Y-m-d', strtotime($u['ultimo_login'])) == $hoy;
                });
                echo count($loginsHoy);
                ?>
            </div>
            <div class="stat-label">Logins Hoy</div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header">
            <h2 class="table-title">Lista de Usuarios</h2>
            <div class="table-actions">
                <input type="text" 
                       class="table-search" 
                       placeholder="Buscar usuario...">
                <span class="table-info">
                    <i class="fas fa-filter"></i>
                    Mostrando: <span class="filter-count"><?php echo $totalUsuarios; ?></span>
                </span>
            </div>
        </div>
        
        <div class="table-container">
            <table class="usuarios-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="username">
                            <span>Usuario</span>
                            <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="nombre">
                            <span>Nombre</span>
                            <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="email">
                            <span>Email</span>
                            <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="rol">
                            <span>Rol</span>
                            <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="estado">
                            <span>Estado</span>
                            <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="ultimo_login">
                            <span>Último Login</span>
                            <span class="sort-indicator"></span>
                        </th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentUserId = $_SESSION['usuario_id'] ?? 0;
                    foreach ($usuarios as $usuario): 
                        $isCurrentUser  = $usuario['id'] == $currentUserId;
                        $isSuperAdmin   = (bool)($usuario['propietario'] ?? false);
                        $canDelete      = !$isCurrentUser && !$isSuperAdmin;
                        $canEditStatus  = !$isCurrentUser && !$isSuperAdmin;
                        
                        $ultimoLoginFormatted = 'Nunca';
                        $ultimoLoginClass = 'never';
                        $ultimoLoginSortValue = '9999-12-31 23:59:59'; // Valor para ordenamiento
                        if ($usuario['ultimo_login']) {
                            $ultimoLoginFormatted = date('d/m/Y H:i', strtotime($usuario['ultimo_login']));
                            $ultimoLoginClass = 'recent';
                            $ultimoLoginSortValue = date('Y-m-d H:i:s', strtotime($usuario['ultimo_login']));
                            
                            if (date('Y-m-d', strtotime($usuario['ultimo_login'])) == date('Y-m-d')) {
                                $ultimoLoginFormatted = 'Hoy ' . date('H:i', strtotime($usuario['ultimo_login']));
                                $ultimoLoginClass = 'today';
                            }
                        }
                    ?>
                    <tr data-user-id="<?php echo $usuario['id']; ?>"
                        data-username="<?php echo htmlspecialchars(strtolower($usuario['username'])); ?>"
                        data-nombre="<?php echo htmlspecialchars(strtolower($usuario['nombre'])); ?>"
                        data-email="<?php echo htmlspecialchars(strtolower($usuario['email'])); ?>"
                        data-rol="<?php echo htmlspecialchars($usuario['rol']); ?>"
                        data-estado="<?php echo $usuario['activo'] ? 'activo' : 'inactivo'; ?>"
                        data-ultimo-login="<?php echo $ultimoLoginSortValue; ?>">
                        <td>
                            <div class="usuario-avatar">
                                <img src="<?php echo $baseUrl; ?>/assets/media/users/<?php echo htmlspecialchars($usuario['avatar']); ?>" 
                                     class="avatar-img"
                                     onerror="this.src='<?php echo $baseUrl; ?>/assets/media/users/default.png'"
                                     alt="<?php echo htmlspecialchars($usuario['username']); ?>">
                                <div class="usuario-info">
                                    <span class="usuario-username">
                                        <?php echo htmlspecialchars($usuario['username']); ?>
                                        <?php if ($isSuperAdmin): ?>
                                            <i class="fas fa-crown" style="color:#f59e0b;" title="Propietario del negocio"></i>
                                        <?php elseif ($usuario['rol'] === 'admin'): ?>
                                            <i class="fas fa-star" style="color:#3498db;" title="Administrador"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="usuario-email"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                            <?php if ($isCurrentUser): ?>
                                <br><small style="color: #3498db;"><i class="fas fa-user"></i> Tú</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($usuario['email']); ?>" 
                               style="color: #3498db; text-decoration: none;">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $usuario['rol']; ?>">
                                <?php 
                                switch($usuario['rol']) {
                                    case 'admin': echo 'Administrador'; break;
                                    case 'cocina': echo 'Cocina'; break;
                                    case 'inventario': echo 'Inventario'; break;
                                    case 'mesero': echo 'Mesero'; break;
                                    default: echo ucfirst($usuario['rol']);
                                }
                                ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label class="switch-table">
                                    <input type="checkbox" 
                                           class="status-switch"
                                           data-user-id="<?php echo $usuario['id']; ?>"
                                           data-user-name="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                           <?php echo $usuario['activo'] ? 'checked' : ''; ?>
                                           <?php echo !$canEditStatus ? 'disabled' : ''; ?>>
                                    <span class="slider-table"></span>
                                </label>
                            </div>
                        </td>
                        <td>
                            <span class="login-time <?php echo $ultimoLoginClass; ?>" data-sort="<?php echo $ultimoLoginSortValue; ?>">
                                <i class="fas fa-<?php echo $usuario['ultimo_login'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $ultimoLoginFormatted; ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones-container">
                                <!-- Botón Editar -->
                                <button class="btn-accion btn-editar"
                                        data-user-id="<?php echo $usuario['id']; ?>"
                                        title="Editar usuario"
                                        <?php if ($isSuperAdmin): ?>disabled style="opacity: 0.5; cursor: not-allowed;"<?php endif; ?>>
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Botón Eliminar -->
                                <form method="POST" action="<?php echo $basePath; ?>/usuarios/eliminar" 
                                      class="form-eliminar">
                                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                    <button type="submit" 
                                            class="btn-accion btn-eliminar btn-eliminar-form"
                                            data-user-id="<?php echo $usuario['id']; ?>"
                                            data-usuario-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                            title="Eliminar usuario"
                                            <?php echo !$canDelete ? 'disabled' : ''; ?>
                                            <?php echo !$canDelete ? 'style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                
                                <!-- Botón Restablecer Contraseña -->
                                <button class="btn-accion btn-reset-password"
                                        data-user-id="<?php echo $usuario['id']; ?>"
                                        data-user-name="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                        title="Restablecer contraseña"
                                        <?php if ($isSuperAdmin || $isCurrentUser): ?>disabled style="opacity: 0.5; cursor: not-allowed;"<?php endif; ?>>
                                    <i class="fas fa-key"></i>
                                </button>
                                
                                <!-- Botón Permisos -->
                                <button class="btn-accion btn-permisos"
                                        data-user-id="<?php echo $usuario['id']; ?>"
                                        data-user-name="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                        data-user-avatar="<?php echo htmlspecialchars($usuario['avatar']); ?>"
                                        title="Gestionar permisos"
                                        <?php if ($isSuperAdmin || $isCurrentUser): ?>disabled style="opacity: 0.5; cursor: not-allowed;"<?php endif; ?>>
                                    <i class="fas fa-user-lock"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="no-usuarios">
                                <i class="fas fa-users-slash"></i>
                                <h3>No hay usuarios registrados</h3>
                                <p>Comienza agregando un nuevo usuario</p>
                                <button id="openModalBtnEmpty" class="btn-open-modal" style="margin-top: 20px;">
                                    <i class="fas fa-user-plus"></i>
                                    Agregar Primer Usuario
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($usuarios)): ?>
        <div style="text-align: center; margin-top: 20px; padding: 15px; color: #7f8c8d; font-size: 14px; border-top: 2px solid #f8f9fa;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="fas fa-info-circle"></i>
                    Mostrando <strong><?php echo $totalUsuarios; ?></strong> usuario(s)
                </div>
                <div>
                    <span class="badge badge-admin" style="margin-right: 5px;">
                        <span id="adminsBadgeCount"><?php echo count($admins); ?></span> Admin
                    </span>
                    <span class="badge badge-cocina" style="margin-right: 5px;">
                        <span id="cocinaBadgeCount"><?php echo count($cocina); ?></span> Cocina
                    </span>
                    <span class="badge badge-inventario" style="margin-right: 5px;">
                        <span id="inventarioBadgeCount"><?php echo count($inventario); ?></span> Inventario
                    </span>
                    <span class="badge badge-mesero">
                        <span id="meserosBadgeCount"><?php echo count($meseros); ?></span> Mesero
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script de inicialización -->
<script>
// Inicialización del sistema de permisos
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de gestión de usuarios...');
    
    // Verificar que SweetAlert2 esté cargado
    if (typeof Swal === 'undefined') {
        console.error('❌ SweetAlert2 no está cargado');
    } else {
        console.log('✅ SweetAlert2 cargado correctamente');
    }
    
    // Verificar si el gestor de permisos está disponible
    if (typeof PermisosManager !== 'undefined') {
        console.log('✅ PermisosManager detectado, se inicializará automáticamente');
    } else {
        console.error('❌ PermisosManager no está definido. Verifica que permisos.js esté cargado.');
        
        // Intentar cargar permisos.js si no está cargado
        setTimeout(() => {
            if (typeof PermisosManager === 'undefined') {
                console.log('🔄 Intentando cargar permisos.js dinámicamente...');
                const script = document.createElement('script');
                script.src = '<?php echo $baseUrl; ?>/assets/js/permisos.js';
                script.onload = function() {
                    console.log('✅ permisos.js cargado dinámicamente');
                };
                script.onerror = function() {
                    console.error('❌ Error cargando permisos.js');
                };
                document.head.appendChild(script);
            }
        }, 1000);
    }
    
    // Configurar búsqueda
    const searchInput = document.querySelector('.table-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
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
    
    // Botón para abrir modal desde sección vacía
    const openModalBtnEmpty = document.getElementById('openModalBtnEmpty');
    if (openModalBtnEmpty) {
        openModalBtnEmpty.addEventListener('click', function() {
            const mainBtn = document.getElementById('openModalBtn');
            if (mainBtn) mainBtn.click();
        });
    }
    
    // Depuración: Verificar que los botones de permisos tengan los atributos correctos
    const permisosBtns = document.querySelectorAll('.btn-permisos');
    console.log(`🔍 Encontrados ${permisosBtns.length} botones de permisos`);
    
    permisosBtns.forEach((btn, index) => {
        const userId = btn.getAttribute('data-user-id');
        const userName = btn.getAttribute('data-user-name');
        const userAvatar = btn.getAttribute('data-user-avatar');
        console.log(`  Botón ${index + 1}: ID=${userId}, Nombre=${userName}, Avatar=${userAvatar}`);
        
        // Verificar que no esté deshabilitado para usuarios especiales
        const isCurrentUser = userId === '<?php echo $_SESSION['usuario_id'] ?? 0; ?>';
        const isSuperAdmin = userId === '1';
        
        if (isCurrentUser || isSuperAdmin) {
            console.log(`  ⚠️ Botón ${index + 1} deshabilitado (usuario especial)`);
        }
    });
    
    // Test de conexión de permisos (para debugging)
    window.testPermisos = function(userId) {
        if (!userId) {
            const btns = document.querySelectorAll('.btn-permisos:not([disabled])');
            if (btns.length > 0) {
                userId = btns[0].getAttribute('data-user-id');
            } else {
                console.error('No hay botones de permisos habilitados para probar');
                return;
            }
        }
        
        console.log(`🔗 Testeando conexión de permisos para usuario ID: ${userId}`);
        const url = `<?php echo $baseUrl; ?>/permisos/popup-get/${userId}`;
        console.log('URL de test:', url);
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    console.log(`✅ Test exitoso: ${data.permisos?.length || 0} permisos cargados`);
                } else {
                    console.error(`❌ Test fallido: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('❌ Error en test:', error);
            });
    };
    
    // Mostrar ayuda si hay errores
    setTimeout(() => {
        if (typeof PermisosManager === 'undefined') {
            console.warn('⚠️ PermisosManager aún no está disponible. Verifica:');
            console.warn('1. Que permisos.js esté cargado en el HTML');
            console.warn('2. Que no haya errores en la consola al cargar permisos.js');
            console.warn('3. Que la ruta a permisos.js sea correcta');
        }
    }, 2000);
});
</script>

<style>
/* Estilos para ordenamiento */
.sortable {
    cursor: pointer;
    position: relative;
    user-select: none;
}

.sortable:hover {
    background-color: #f8f9fa;
}

.sort-indicator {
    display: inline-block;
    margin-left: 5px;
    color: #3498db;
    font-size: 12px;
    opacity: 0.6;
}

.sortable.active .sort-indicator {
    opacity: 1;
}

.sortable.asc .sort-indicator::after {
    content: ' ↑';
}

.sortable.desc .sort-indicator::after {
    content: ' ↓';
}

/* Estilos para contadores actualizados */
.stat-card .stat-number {
    transition: all 0.3s ease;
}

.stat-card .stat-number.updated {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Estilos para debugging */
.debug-info {
    position: fixed;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 10px;
    border-radius: 5px;
    font-size: 12px;
    z-index: 9999;
}
</style>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>