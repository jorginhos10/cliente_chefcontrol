<?php
// vista/recetas/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Gestión de Recetas - CHEFCONTROL';
$paginaActual = 'recetas';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra  = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/recetas.css">';
$jsExtra   = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$categorias = [
    'entrada'      => ['label' => 'Entrada',       'icon' => 'fa-utensils'],
    'plato_fuerte' => ['label' => 'Plato Fuerte',  'icon' => 'fa-drumstick-bite'],
    'postre'       => ['label' => 'Postre',         'icon' => 'fa-ice-cream'],
    'bebida'       => ['label' => 'Bebida',         'icon' => 'fa-wine-glass'],
    'snack'        => ['label' => 'Snack',           'icon' => 'fa-cookie-bite'],
    'otro'         => ['label' => 'Otro',            'icon' => 'fa-bowl-food'],
];

$iconosPorCategInsumo = [
    'carnes'   => 'fa-drumstick-bite',
    'verduras' => 'fa-leaf',
    'lacteos'  => 'fa-cheese',
    'granos'   => 'fa-seedling',
    'especias' => 'fa-mortar-pestle',
    'bebidas'  => 'fa-wine-bottle',
    'otros'    => 'fa-box',
];

// JSON de insumos para JavaScript
$insumosJson = json_encode($insumos ?? []);
?>

<div class="recetas-container">

    <div class="recetas-header">
        <div class="recetas-title-section">
            <h1><i class="fas fa-book-open" style="color:#e67e22;margin-right:10px;"></i>Gestión de Recetas</h1>
            <p>Crea y administra las recetas del menú con sus ingredientes</p>
        </div>
        <button id="openModalBtn" class="btn-open-modal">
            <i class="fas fa-plus"></i> Nueva Receta
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

    <!-- Estadísticas -->
    <div class="recetas-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color:#e67e22;"><i class="fas fa-book"></i></div>
            <div class="stat-number"><?php echo $estadisticas['total'] ?? 0; ?></div>
            <div class="stat-label">Total Recetas</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#27ae60;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['activas'] ?? 0; ?></div>
            <div class="stat-label">Recetas Activas</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#9b59b6;"><i class="fas fa-tags"></i></div>
            <div class="stat-number"><?php echo $estadisticas['categorias'] ?? 0; ?></div>
            <div class="stat-label">Categorías</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#3498db;"><i class="fas fa-carrot"></i></div>
            <div class="stat-number"><?php echo $estadisticas['total_ingredientes_usados'] ?? 0; ?></div>
            <div class="stat-label">Ingredientes Configurados</div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="recetas-toolbar">
        <input type="text" class="toolbar-search" placeholder="Buscar receta..." id="searchInput">
        <div class="toolbar-filter">
            <button class="filter-btn active" data-cat="">Todas</button>
            <?php foreach ($categorias as $key => $cat): ?>
                <button class="filter-btn" data-cat="<?php echo $key; ?>">
                    <i class="fas <?php echo $cat['icon']; ?>"></i> <?php echo $cat['label']; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Grid de recetas -->
    <div class="recetas-grid" id="recetasGrid">
        <?php if (!empty($recetas)): ?>
            <?php foreach ($recetas as $receta):
                $cat    = $receta['categoria'];
                $catDef = $categorias[$cat] ?? $categorias['otro'];
                $icono  = $catDef['icon'];
                $label  = $catDef['label'];
            ?>
            <?php
                $fotoRaw = $receta['foto'] ?? '';
                $fotoParsed = json_decode($fotoRaw, true);
                $fotoUrl = '';
                if (is_array($fotoParsed) && !empty($fotoParsed[0])) {
                    $fotoUrl = htmlspecialchars($fotoParsed[0]);
                } elseif (!empty($fotoRaw)) {
                    $fotoUrl = htmlspecialchars($fotoRaw);
                }
            ?>
            <div class="receta-card" data-cat="<?php echo $cat; ?>" data-nombre="<?php echo strtolower(htmlspecialchars($receta['nombre'])); ?>">
                <div class="receta-card-header cat-<?php echo $cat; ?>" <?php if ($fotoUrl): ?>style="padding:0;overflow:hidden"<?php endif; ?>>
                    <?php if ($fotoUrl): ?>
                        <img src="<?php echo $fotoUrl; ?>" alt="<?php echo htmlspecialchars($receta['nombre']); ?>" class="receta-foto-thumb">
                        <div class="receta-foto-overlay">
                            <h3 class="receta-nombre"><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                            <span class="receta-categoria-badge"><?php echo $label; ?></span>
                        </div>
                    <?php else: ?>
                        <span class="receta-icono"><i class="fas <?php echo $icono; ?>"></i></span>
                        <h3 class="receta-nombre"><?php echo htmlspecialchars($receta['nombre']); ?></h3>
                        <span class="receta-categoria-badge"><?php echo $label; ?></span>
                    <?php endif; ?>
                </div>

                <div class="receta-card-body">
                    <p class="receta-desc">
                        <?php echo !empty($receta['descripcion'])
                            ? htmlspecialchars(mb_strimwidth($receta['descripcion'], 0, 80, '...'))
                            : '<em style="color:#bdc3c7;">Sin descripción</em>'; ?>
                    </p>

                    <?php if (!empty($receta['precio_venta']) && $receta['precio_venta'] > 0): ?>
                    <div class="receta-precio">
                        <i class="fas fa-tag"></i>
                        $<?php echo number_format((float)$receta['precio_venta'], 2); ?>
                    </div>
                    <?php else: ?>
                    <div class="receta-precio receta-precio-sin">
                        <i class="fas fa-tag"></i> Sin precio
                    </div>
                    <?php endif; ?>

                    <div class="receta-meta">
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <?php echo $receta['tiempo_preparacion'] > 0 ? $receta['tiempo_preparacion'] . ' min' : '—'; ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <?php echo $receta['porciones']; ?> porción(es)
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-carrot"></i>
                            <?php echo $receta['total_ingredientes']; ?> ingrediente(s)
                        </div>
                    </div>
                </div>

                <div class="receta-card-footer">
                    <label class="switch-table">
                        <input type="checkbox"
                               class="status-switch"
                               data-id="<?php echo $receta['id']; ?>"
                               data-nombre="<?php echo htmlspecialchars($receta['nombre']); ?>"
                               <?php echo $receta['activo'] ? 'checked' : ''; ?>>
                        <span class="slider-table"></span>
                    </label>

                    <div class="acciones-receta">
                        <button class="btn-accion btn-ver btn-ver-receta"
                                data-id="<?php echo $receta['id']; ?>"
                                title="Ver ingredientes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-accion btn-editar btn-editar-receta"
                                data-id="<?php echo $receta['id']; ?>"
                                title="Editar receta">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" action="<?php echo $basePath; ?>/recetas/eliminar" class="form-eliminar" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $receta['id']; ?>">
                            <button type="submit" class="btn-accion btn-eliminar"
                                    data-nombre="<?php echo htmlspecialchars($receta['nombre']); ?>"
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-recetas">
                <i class="fas fa-book-open"></i>
                <h3>No hay recetas registradas</h3>
                <p>Comienza creando tu primera receta con sus ingredientes</p>
                <button id="openModalBtnEmpty" class="btn-open-modal" style="margin:20px auto 0;width:fit-content;">
                    <i class="fas fa-plus"></i> Crear Primera Receta
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ======================== MODAL CREAR / EDITAR ======================== -->
<div class="modal-overlay" id="recetaModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-book-open"></i>
                <span id="modalTitle">Nueva Receta</span>
            </h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="recetaForm">
                <input type="hidden" id="recetaId" name="id" value="0">
                <input type="hidden" id="ingredientes_json" name="ingredientes_json" value="[]">

                <!-- Sección 1: Información básica -->
                <div class="modal-section">
                    <p class="section-title"><i class="fas fa-info-circle"></i> Información de la Receta</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Pasta Carbonara" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoría</label>
                            <select id="categoria" name="categoria" class="form-control">
                                <option value="entrada">Entrada</option>
                                <option value="plato_fuerte" selected>Plato Fuerte</option>
                                <option value="postre">Postre</option>
                                <option value="bebida">Bebida</option>
                                <option value="snack">Snack</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="2" placeholder="Breve descripción del platillo..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tiempo de preparación (minutos)</label>
                            <input type="number" id="tiempo_preparacion" name="tiempo_preparacion" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Porciones</label>
                            <input type="number" id="porciones" name="porciones" class="form-control" min="1" value="1">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Precio de Venta *</label>
                        <div class="input-precio-wrap">
                            <span class="input-precio-prefix">$</span>
                            <input type="number" id="precio_venta" name="precio_venta" class="form-control input-precio" min="0" step="0.01" value="0" placeholder="0.00">
                        </div>
                        <small class="form-text">Este precio se usará en el módulo de ventas.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Foto del platillo</label>
                        <div class="foto-upload-area">
                            <div class="foto-preview-box" id="fotoPreviewBox">
                                <i class="fas fa-camera"></i>
                                <span>Sin foto</span>
                            </div>
                            <div class="foto-upload-controls">
                                <div id="fotoUrlList">
                                    <div class="foto-url-row">
                                        <span class="foto-url-badge badge-icon" title="Imagen principal (icono)">Icono</span>
                                        <input type="text" name="foto_urls[]" class="form-control foto-url-input"
                                               placeholder="https://ejemplo.com/imagen.jpg" oninput="onFotoUrlChange()">
                                    </div>
                                </div>
                                <button type="button" class="btn-add-foto" onclick="addFotoRow()">
                                    <i class="fas fa-plus"></i> Agregar imagen
                                </button>
                                <small style="color:#95a5a6;margin-top:2px;">La primera imagen se usa como icono de la tarjeta</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="switch-container">
                            <span class="switch-label">Estado:</span>
                            <label class="switch">
                                <input type="checkbox" id="activo" name="activo" checked>
                                <span class="slider"></span>
                            </label>
                            <span id="estadoLabel">Activo</span>
                        </label>
                    </div>
                </div>

                <!-- Sección 2: Ingredientes -->
                <div class="modal-section">
                    <p class="section-title"><i class="fas fa-carrot"></i> Ingredientes / Insumos</p>

                    <div id="ingredientes-lista">
                        <p class="ingredientes-vacio" id="vacioPH">
                            <i class="fas fa-plus-circle"></i> Aún no hay ingredientes. Agrega uno abajo.
                        </p>
                    </div>

                    <button type="button" class="btn-add-ingrediente" id="addIngBtn">
                        <i class="fas fa-plus"></i> Agregar Ingrediente
                    </button>
                    <small class="form-text" style="margin-top:8px;">Selecciona el insumo e indica la cantidad necesaria para esta receta.</small>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelBtn">Cancelar</button>
            <button type="button" class="btn-primary"   id="saveBtn">
                <i class="fas fa-save"></i> Guardar Receta
            </button>
        </div>
    </div>
</div>

<!-- ======================== MODAL VER DETALLE ======================== -->
<div class="modal-overlay" id="detalleModal">
    <div class="modal">
        <div class="modal-header" id="detalleHeader" style="background:linear-gradient(135deg,#d35400,#e67e22);">
            <h2 class="modal-title">
                <i class="fas fa-eye"></i>
                <span id="detalleTitulo">Detalle de Receta</span>
            </h2>
            <button class="btn-close-modal" id="closeDetalleBtn">&times;</button>
        </div>
        <div class="modal-body" id="detalleContenido"></div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cerrarDetalleBtn">Cerrar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';

    // ── Datos de insumos disponibles ──────────────────────────────────────
    const insumosDisponibles = <?php echo $insumosJson; ?>;

    const iconosCatInsumo = <?php echo json_encode($iconosPorCategInsumo); ?>;

    const categoriasCss = {
        entrada:      'cat-entrada',
        plato_fuerte: 'cat-plato_fuerte',
        postre:       'cat-postre',
        bebida:       'cat-bebida',
        snack:        'cat-snack',
        otro:         'cat-otro',
    };

    const categoriaColores = {
        entrada:      '#2980b9',
        plato_fuerte: '#d35400',
        postre:       '#8e44ad',
        bebida:       '#16a085',
        snack:        '#d4ac0d',
        otro:         '#7f8c8d',
    };

    // ── Referencias DOM ───────────────────────────────────────────────────
    const modalEl      = document.getElementById('recetaModal');
    const modalTitle   = document.getElementById('modalTitle');
    const recetaId     = document.getElementById('recetaId');
    const form         = document.getElementById('recetaForm');
    const ingLista     = document.getElementById('ingredientes-lista');
    const vacioPH      = document.getElementById('vacioPH');
    const jsonField    = document.getElementById('ingredientes_json');
    let   rowCounter   = 0;

    // ── Abrir / cerrar modal crear-editar ─────────────────────────────────
    document.getElementById('openModalBtn').addEventListener('click', abrirModalNuevo);
    const emptyBtn = document.getElementById('openModalBtnEmpty');
    if (emptyBtn) emptyBtn.addEventListener('click', abrirModalNuevo);

    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);
    // click-outside deshabilitado

    function abrirModalNuevo() {
        modalTitle.textContent = 'Nueva Receta';
        recetaId.value = '0';
        form.reset();
        document.getElementById('precio_venta').value = '0';
        limpiarIngredientes();
        document.getElementById('estadoLabel').textContent = 'Activo';
        resetFotoPreview();
        modalEl.classList.add('active');
    }

    // ── Foto: lista múltiple de URLs ──────────────────────────────────────
    window.onFotoUrlChange = function onFotoUrlChange() {
        const first = document.querySelector('#fotoUrlList .foto-url-input');
        const url   = first ? first.value.trim() : '';
        const box   = document.getElementById('fotoPreviewBox');
        if (url) {
            box.innerHTML = `<img src="${url}" alt="preview" onerror="this.style.display='none'">`;
            box.classList.add('has-foto');
        } else {
            box.innerHTML = '<i class="fas fa-camera"></i><span>Sin foto</span>';
            box.classList.remove('has-foto');
        }
    }

    window.addFotoRow = function () {
        const list  = document.getElementById('fotoUrlList');
        const num   = list.querySelectorAll('.foto-url-row').length + 1;
        const row   = document.createElement('div');
        row.className = 'foto-url-row';
        row.innerHTML = `
            <span class="foto-url-badge">${num}</span>
            <input type="text" name="foto_urls[]" class="form-control foto-url-input"
                   placeholder="https://ejemplo.com/imagen.jpg" oninput="onFotoUrlChange()">
            <button type="button" class="btn-foto-rm-row" onclick="removeFotoRow(this)">
                <i class="fas fa-times"></i>
            </button>`;
        list.appendChild(row);
    };

    window.removeFotoRow = function (btn) {
        btn.closest('.foto-url-row').remove();
        // Renumerar badges
        document.querySelectorAll('#fotoUrlList .foto-url-row').forEach((row, i) => {
            const badge = row.querySelector('.foto-url-badge');
            if (i === 0) { badge.textContent = 'Icono'; badge.className = 'foto-url-badge badge-icon'; }
            else         { badge.textContent = i + 1;   badge.className = 'foto-url-badge'; }
        });
        onFotoUrlChange();
    };

    function setFotoPreview(src) {
        const box = document.getElementById('fotoPreviewBox');
        box.innerHTML = `<img src="${src}" alt="preview" onerror="this.style.display='none'">`;
        box.classList.add('has-foto');
    }

    function resetFotoPreview() {
        document.getElementById('fotoUrlList').innerHTML = `
            <div class="foto-url-row">
                <span class="foto-url-badge badge-icon" title="Imagen principal (icono)">Icono</span>
                <input type="text" name="foto_urls[]" class="form-control foto-url-input"
                       placeholder="https://ejemplo.com/imagen.jpg" oninput="onFotoUrlChange()">
            </div>`;
        const box = document.getElementById('fotoPreviewBox');
        box.innerHTML = '<i class="fas fa-camera"></i><span>Sin foto</span>';
        box.classList.remove('has-foto');
    }

    window.quitarFoto = resetFotoPreview;

    function cerrarModal() { modalEl.classList.remove('active'); }

    // Switch label
    document.getElementById('activo').addEventListener('change', function () {
        document.getElementById('estadoLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
    });

    // ── Ingredientes dinámicos ────────────────────────────────────────────
    document.getElementById('addIngBtn').addEventListener('click', () => agregarFilaIngrediente());

    function agregarFilaIngrediente(id_insumo = '', cantidad = '') {
        ocultarVacio();
        rowCounter++;
        const row = document.createElement('div');
        row.className = 'ingrediente-row';
        row.dataset.row = rowCounter;

        const selectHTML = `<select class="form-control insumo-select" data-row="${rowCounter}">
            <option value="">— Seleccionar insumo —</option>
            ${insumosDisponibles.map(ins =>
                `<option value="${ins.id}" data-unidad="${ins.unidad_medida}" ${ins.id == id_insumo ? 'selected' : ''}>${ins.nombre}</option>`
            ).join('')}
        </select>`;

        const unidadInicial = (() => {
            const found = insumosDisponibles.find(i => i.id == id_insumo);
            return found ? found.unidad_medida : '—';
        })();

        row.innerHTML = `
            ${selectHTML}
            <input type="number" class="form-control cantidad-input" min="0.01" step="0.01"
                   placeholder="Cant." value="${cantidad}" data-row="${rowCounter}">
            <span class="unidad-lbl" id="unidad-${rowCounter}">${unidadInicial}</span>
            <button type="button" class="btn-remove-row" data-row="${rowCounter}"><i class="fas fa-times"></i></button>
        `;

        ingLista.appendChild(row);

        // Actualizar unidad al cambiar select
        row.querySelector('.insumo-select').addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById(`unidad-${this.dataset.row}`).textContent = opt.dataset.unidad || '—';
        });

        // Remover fila
        row.querySelector('.btn-remove-row').addEventListener('click', function () {
            row.remove();
            if (ingLista.querySelectorAll('.ingrediente-row').length === 0) mostrarVacio();
        });
    }

    function limpiarIngredientes() {
        ingLista.querySelectorAll('.ingrediente-row').forEach(r => r.remove());
        mostrarVacio();
    }

    function mostrarVacio() { if (vacioPH) vacioPH.style.display = 'block'; }
    function ocultarVacio() { if (vacioPH) vacioPH.style.display = 'none';  }

    function recolectarIngredientes() {
        const ingredientes = [];
        ingLista.querySelectorAll('.ingrediente-row').forEach(row => {
            const id_insumo = row.querySelector('.insumo-select').value;
            const cantidad  = parseFloat(row.querySelector('.cantidad-input').value);
            if (id_insumo && cantidad > 0) {
                ingredientes.push({ id_insumo, cantidad });
            }
        });
        return ingredientes;
    }

    // ── Guardar receta ────────────────────────────────────────────────────
    document.getElementById('saveBtn').addEventListener('click', function () {
        const nombre = document.getElementById('nombre').value.trim();
        if (!nombre) {
            Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'El nombre de la receta es obligatorio.' });
            return;
        }

        const ingredientes = recolectarIngredientes();
        if (ingredientes.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Sin ingredientes', text: '¿Seguro que quieres guardar sin ingredientes?', showCancelButton: true, confirmButtonText: 'Sí, guardar', cancelButtonText: 'Agregar ingredientes' })
                .then(r => { if (r.isConfirmed) enviarFormulario(ingredientes); });
            return;
        }
        enviarFormulario(ingredientes);
    });

    function enviarFormulario(ingredientes) {
        jsonField.value = JSON.stringify(ingredientes);
        const formData  = new FormData(form);
        const id        = parseInt(recetaId.value);
        const url       = id > 0 ? basePath + '/recetas/actualizar' : basePath + '/recetas/crear';
        const saveBtn   = document.getElementById('saveBtn');
        const orig      = saveBtn.innerHTML;

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
    }

    // ── Cargar datos para editar ──────────────────────────────────────────
    document.querySelectorAll('.btn-editar-receta').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            fetch(basePath + '/recetas/get/' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { Swal.fire({ icon: 'error', title: 'Error', text: data.message }); return; }
                    const r = data.data;
                    modalTitle.textContent = 'Editar Receta';
                    recetaId.value = r.id;
                    document.getElementById('nombre').value              = r.nombre;
                    document.getElementById('descripcion').value         = r.descripcion    ?? '';
                    document.getElementById('categoria').value           = r.categoria;
                    document.getElementById('tiempo_preparacion').value  = r.tiempo_preparacion;
                    document.getElementById('porciones').value           = r.porciones;
                    document.getElementById('precio_venta').value        = r.precio_venta   ?? 0;
                    const activoChk = document.getElementById('activo');
                    activoChk.checked = r.activo == 1;
                    document.getElementById('estadoLabel').textContent = activoChk.checked ? 'Activo' : 'Inactivo';

                    limpiarIngredientes();
                    (r.ingredientes || []).forEach(ing => agregarFilaIngrediente(ing.id_insumo, ing.cantidad));

                    // Foto — foto_urls llega como array limpio desde el API
                    resetFotoPreview();
                    const fotoUrls = Array.isArray(r.foto_urls) ? r.foto_urls.filter(u => u) : [];
                    if (fotoUrls.length) {
                        const list = document.getElementById('fotoUrlList');
                        fotoUrls.forEach((url, i) => {
                            if (i === 0) {
                                list.querySelector('.foto-url-input').value = url;
                            } else {
                                window.addFotoRow();
                                const inputs = list.querySelectorAll('.foto-url-input');
                                inputs[inputs.length - 1].value = url;
                            }
                        });
                        setFotoPreview(fotoUrls[0]);
                    }

                    modalEl.classList.add('active');
                });
        });
    });

    // ── Ver detalle ───────────────────────────────────────────────────────
    document.querySelectorAll('.btn-ver-receta').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            fetch(basePath + '/recetas/get/' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    mostrarDetalle(data.data);
                });
        });
    });

    function mostrarDetalle(r) {
        const color = categoriaColores[r.categoria] || '#e67e22';
        document.getElementById('detalleHeader').style.background = `linear-gradient(135deg, ${color}cc, ${color})`;
        document.getElementById('detalleTitulo').textContent = r.nombre;

        const catLabels = { entrada:'Entrada', plato_fuerte:'Plato Fuerte', postre:'Postre', bebida:'Bebida', snack:'Snack', otro:'Otro' };
        const catIcons  = { entrada:'fa-utensils', plato_fuerte:'fa-drumstick-bite', postre:'fa-ice-cream', bebida:'fa-wine-glass', snack:'fa-cookie-bite', otro:'fa-bowl-food' };

        const ingsHTML = (r.ingredientes || []).map(ing => {
            const icon = iconosCatInsumo[ing.categoria] || 'fa-box';
            return `<div class="ingrediente-item">
                <div class="ing-icon-small" style="background:linear-gradient(135deg,${color}cc,${color})">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="ing-info">
                    <div class="ing-nombre">${ing.insumo_nombre}</div>
                    <div class="ing-cat-mini">${ing.categoria ?? ''}</div>
                </div>
                <span class="ing-cantidad">${parseFloat(ing.cantidad)} ${ing.unidad_medida}</span>
            </div>`;
        }).join('') || '<p style="color:#bdc3c7;text-align:center;padding:16px;">Sin ingredientes configurados</p>';

        document.getElementById('detalleContenido').innerHTML = `
            <div class="detalle-receta">
                <div class="detalle-header-info">
                    <div class="detalle-cat-icon" style="background:linear-gradient(135deg,${color}cc,${color})">
                        <i class="fas ${catIcons[r.categoria] || 'fa-book'}"></i>
                    </div>
                    <div>
                        <h3 class="detalle-nombre">${r.nombre}</h3>
                        <div class="detalle-meta">
                            <span class="detalle-meta-item"><i class="fas fa-tag"></i> ${catLabels[r.categoria] || r.categoria}</span>
                            <span class="detalle-meta-item"><i class="fas fa-clock"></i> ${r.tiempo_preparacion > 0 ? r.tiempo_preparacion + ' min' : '—'}</span>
                            <span class="detalle-meta-item"><i class="fas fa-users"></i> ${r.porciones} porción(es)</span>
                            <span class="detalle-meta-item"><i class="fas fa-carrot"></i> ${(r.ingredientes||[]).length} ingrediente(s)</span>
                            <span class="detalle-meta-item detalle-precio"><i class="fas fa-dollar-sign"></i> ${r.precio_venta > 0 ? '$' + parseFloat(r.precio_venta).toFixed(2) : 'Sin precio'}</span>
                        </div>
                    </div>
                </div>
                ${r.descripcion ? `<div class="detalle-desc">${r.descripcion}</div>` : ''}
                <div class="detalle-ingredientes-titulo"><i class="fas fa-list"></i> Lista de Ingredientes</div>
                ${ingsHTML}
            </div>`;

        document.getElementById('detalleModal').classList.add('active');
    }

    document.getElementById('closeDetalleBtn').addEventListener('click',  () => document.getElementById('detalleModal').classList.remove('active'));
    document.getElementById('cerrarDetalleBtn').addEventListener('click', () => document.getElementById('detalleModal').classList.remove('active'));
    // click-outside deshabilitado

    // ── Switches de estado ────────────────────────────────────────────────
    document.querySelectorAll('.status-switch').forEach(sw => {
        sw.addEventListener('change', function () {
            const id     = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const activo = this.checked ? 1 : 0;
            const ref    = this;
            fetch(basePath + '/recetas/update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, activo })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Estado actualizado', text: nombre, timer: 1200, showConfirmButton: false });
                } else {
                    ref.checked = !ref.checked;
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => { ref.checked = !ref.checked; });
        });
    });

    // ── Confirmar eliminar ────────────────────────────────────────────────
    document.querySelectorAll('.form-eliminar').forEach(f => {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombre = this.querySelector('button').getAttribute('data-nombre');
            Swal.fire({
                title: '¿Eliminar receta?',
                html: `¿Seguro que deseas eliminar <strong>${nombre}</strong> y sus ingredientes configurados?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor:  '#95a5a6',
                confirmButtonText:  'Sí, eliminar',
                cancelButtonText:   'Cancelar'
            }).then(r => { if (r.isConfirmed) this.submit(); });
        });
    });

    // ── Búsqueda y filtro por categoría ──────────────────────────────────
    const searchInput = document.getElementById('searchInput');
    const filterBtns  = document.querySelectorAll('.filter-btn');
    let   filtroActual = '';

    searchInput.addEventListener('input', aplicarFiltros);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtroActual = this.getAttribute('data-cat');
            aplicarFiltros();
        });
    });

    function aplicarFiltros() {
        const term = searchInput.value.toLowerCase();
        document.querySelectorAll('.receta-card').forEach(card => {
            const matchCat    = !filtroActual || card.dataset.cat === filtroActual;
            const matchSearch = !term || card.dataset.nombre.includes(term);
            card.style.display = (matchCat && matchSearch) ? '' : 'none';
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
