<?php
// vista/inventario-inmobiliario/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Inventario Inmobiliario - CHEFCONTROL';
$paginaActual = 'inventario-inmobiliario';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/inventario_inmobiliario.css?v=2">';
$jsExtra  = '
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
';

require_once __DIR__ . '/../complementos/header.php';

$bienesJson = json_encode(array_map(function ($b) {
    return [
        'nombre'  => $b['nombre'],
        'seccion' => $b['seccion_padre_nombre']
            ? $b['seccion_padre_nombre'] . ' » ' . $b['seccion_nombre']
            : ($b['seccion_nombre'] ?? ''),
        'valor'   => (float) $b['valor_tasado'],
        'estado'  => $b['activo'] ? 'Activo' : 'Inactivo',
    ];
}, $bienes ?? []));
?>

<div class="inm-container">

    <div class="inm-header">
        <div class="inm-title-section">
            <h1><i class="fas fa-couch" style="color:#8d6e63;margin-right:10px;"></i>Inventario Inmobiliario</h1>
            <p>Registra los bienes muebles e inmuebles del negocio con su valor evaluado</p>
        </div>
        <div style="display:flex;gap:10px;">
            <button id="btnPdf" class="btn-open-modal" style="background:#c0392b;">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <a href="<?php echo $basePath; ?>/inventario-inmobiliario/secciones" class="btn-open-modal" style="background:#7f8c8d;">
                <i class="fas fa-door-open"></i> Secciones
            </a>
            <button id="openModalBtn" class="btn-open-modal">
                <i class="fas fa-plus"></i> Nuevo Bien
            </button>
        </div>
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
    <div class="inm-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color:#8d6e63;"><i class="fas fa-couch"></i></div>
            <div class="stat-number"><?php echo $estadisticas['total'] ?? 0; ?></div>
            <div class="stat-label">Bienes Registrados</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#27ae60;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['activos'] ?? 0; ?></div>
            <div class="stat-label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#2980b9;"><i class="fas fa-sack-dollar"></i></div>
            <div class="stat-number">$<?php echo number_format((float)($estadisticas['valor_total'] ?? 0), 2); ?></div>
            <div class="stat-label">Valor Evaluado Total</div>
        </div>
    </div>

    <?php if (!empty($seccionesSelect)): ?>
    <!-- Toolbar -->
    <div class="inm-toolbar">
        <label class="form-label" style="margin:0;">Filtrar por sección:</label>
        <select id="filtroSeccion" class="form-control" style="max-width:280px;">
            <option value="">Todas las secciones</option>
            <?php foreach ($seccionesSelect as $op): ?>
                <option value="<?php echo $op['id']; ?>">
                    <?php echo $op['nivel'] > 0 ? '— ' . htmlspecialchars($op['label']) : htmlspecialchars($op['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- Grid de bienes -->
    <div class="inm-grid" id="inmGrid">
        <?php if (!empty($bienes)): ?>
            <?php foreach ($bienes as $bien): ?>
            <div class="inm-card" data-seccion="<?php echo (int)($bien['seccion_id'] ?? 0); ?>">
                <div class="inm-card-header">
                    <?php if (!empty($bien['foto'])): ?>
                        <img src="<?php echo $baseUrl; ?>/assets/uploads/inventario_inmobiliario/<?php echo htmlspecialchars($bien['foto']); ?>"
                             alt="<?php echo htmlspecialchars($bien['nombre']); ?>" class="inm-foto-thumb">
                    <?php else: ?>
                        <div class="inm-foto-placeholder"><i class="fas fa-couch"></i></div>
                    <?php endif; ?>
                </div>
                <div class="inm-card-body">
                    <h3 class="inm-nombre"><?php echo htmlspecialchars($bien['nombre']); ?></h3>
                    <span class="inm-valor-badge">
                        <i class="fas fa-sack-dollar"></i> Evaluado en $<?php echo number_format((float)$bien['valor_tasado'], 2); ?>
                    </span>
                    <?php if (!empty($bien['seccion_nombre'])): ?>
                        <span class="inm-seccion-badge">
                            <i class="fas fa-door-open"></i>
                            <?php
                                echo !empty($bien['seccion_padre_nombre'])
                                    ? htmlspecialchars($bien['seccion_padre_nombre']) . ' &raquo; ' . htmlspecialchars($bien['seccion_nombre'])
                                    : htmlspecialchars($bien['seccion_nombre']);
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="inm-card-footer">
                    <label class="switch-table">
                        <input type="checkbox"
                               class="status-switch"
                               data-id="<?php echo $bien['id']; ?>"
                               data-nombre="<?php echo htmlspecialchars($bien['nombre']); ?>"
                               <?php echo $bien['activo'] ? 'checked' : ''; ?>>
                        <span class="slider-table"></span>
                    </label>
                    <form method="POST" action="<?php echo $basePath; ?>/inventario-inmobiliario/eliminar" class="form-eliminar">
                        <input type="hidden" name="id" value="<?php echo $bien['id']; ?>">
                        <button type="submit" class="btn-accion btn-eliminar"
                                data-nombre="<?php echo htmlspecialchars($bien['nombre']); ?>"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="inm-vacio">
                <i class="fas fa-couch"></i>
                <h3>No hay bienes registrados</h3>
                <p>Comienza agregando el primer bien de tu inventario inmobiliario</p>
                <button id="openModalBtnEmpty" class="btn-open-modal" style="margin:20px auto 0;width:fit-content;">
                    <i class="fas fa-plus"></i> Agregar Primer Bien
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ======================== MODAL NUEVO BIEN ======================== -->
<div class="modal-overlay" id="bienModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-couch"></i> Nuevo Bien</h2>
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
        </div>

        <div class="modal-body">
            <form id="bienForm">
                <div class="form-group">
                    <label class="form-label">Foto</label>
                    <div class="foto-upload-area">
                        <div class="foto-preview-box" id="fotoPreviewBox">
                            <i class="fas fa-camera"></i>
                            <span>Sin foto</span>
                        </div>
                        <div class="foto-upload-controls">
                            <input type="file" id="foto" name="foto" accept="image/*" class="form-control">
                            <small class="form-text">JPG, PNG, GIF o WEBP. Máximo 2MB.</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Nevera industrial, Local comercial..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">Valor Evaluado *</label>
                    <div class="input-precio-wrap">
                        <span class="input-precio-prefix">$</span>
                        <input type="number" id="valor_tasado" name="valor_tasado" class="form-control input-precio" min="0" step="0.01" value="0" placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Sección / Ubicación</label>
                    <select id="seccion_id" name="seccion_id" class="form-control">
                        <option value="">Sin sección</option>
                        <?php foreach ($seccionesSelect as $op): ?>
                            <option value="<?php echo $op['id']; ?>">
                                <?php echo $op['nivel'] > 0 ? '— ' . htmlspecialchars($op['label']) : htmlspecialchars($op['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($seccionesSelect)): ?>
                        <small class="form-text">Aún no tienes secciones creadas. <a href="<?php echo $basePath; ?>/inventario-inmobiliario/secciones">Crea una aquí</a>.</small>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelBtn">Cancelar</button>
            <button type="button" class="btn-primary" id="saveBtn">
                <i class="fas fa-save"></i> Guardar Bien
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';
    const bienes   = <?php echo $bienesJson; ?>;

    document.getElementById('btnPdf').addEventListener('click', function () {
        if (!bienes.length) {
            Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'No hay bienes registrados para exportar.' });
            return;
        }
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

            doc.setFillColor(78, 52, 46);
            doc.rect(0, 0, 210, 20, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(13);
            doc.setFont('helvetica', 'bold');
            doc.text('ChefControl — Inventario Inmobiliario', 10, 13);
            doc.setFontSize(9);
            doc.text('Generado: ' + new Date().toLocaleString('es'), 140, 13);

            doc.setTextColor(44, 62, 80);
            doc.setFontSize(9);
            const valorTotal = bienes.reduce((s, b) => s + (parseFloat(b.valor) || 0), 0);
            doc.text('Total de bienes: ' + bienes.length + '   —   Valor evaluado total: $' + valorTotal.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 }), 10, 26);

            doc.autoTable({
                startY: 30,
                head: [['Nombre', 'Sección', 'Valor Evaluado', 'Estado']],
                body: bienes.map(b => [
                    b.nombre,
                    b.seccion || '—',
                    '$' + parseFloat(b.valor).toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                    b.estado,
                ]),
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [78, 52, 46], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [248, 249, 250] },
                columnStyles: {
                    2: { halign: 'right' },
                    3: { halign: 'center' },
                },
                margin: { left: 10, right: 10 },
            });

            const pages = doc.internal.getNumberOfPages();
            for (let p = 1; p <= pages; p++) {
                doc.setPage(p);
                doc.setFontSize(8);
                doc.setTextColor(150);
                doc.text('Pág. ' + p + ' / ' + pages + '  —  ChefControl', 105, 290, { align: 'center' });
            }

            doc.save('inventario_inmobiliario_' + new Date().toISOString().slice(0, 10) + '.pdf');
        } catch (e) {
            console.error(e);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el PDF.' });
        }
    });

    const modalEl = document.getElementById('bienModal');
    const form    = document.getElementById('bienForm');

    document.getElementById('openModalBtn').addEventListener('click', abrirModal);
    const emptyBtn = document.getElementById('openModalBtnEmpty');
    if (emptyBtn) emptyBtn.addEventListener('click', abrirModal);

    document.getElementById('closeModalBtn').addEventListener('click', cerrarModal);
    document.getElementById('cancelBtn').addEventListener('click', cerrarModal);

    function abrirModal() {
        form.reset();
        resetFotoPreview();
        modalEl.classList.add('active');
    }

    function cerrarModal() { modalEl.classList.remove('active'); }

    document.getElementById('foto').addEventListener('change', function () {
        const file = this.files[0];
        const box  = document.getElementById('fotoPreviewBox');
        if (!file) { resetFotoPreview(); return; }
        const reader = new FileReader();
        reader.onload = e => {
            box.innerHTML = `<img src="${e.target.result}" alt="preview">`;
            box.classList.add('has-foto');
        };
        reader.readAsDataURL(file);
    });

    function resetFotoPreview() {
        const box = document.getElementById('fotoPreviewBox');
        box.innerHTML = '<i class="fas fa-camera"></i><span>Sin foto</span>';
        box.classList.remove('has-foto');
    }

    document.getElementById('saveBtn').addEventListener('click', function () {
        const nombre = document.getElementById('nombre').value.trim();
        if (!nombre) {
            Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'El nombre del bien es obligatorio.' });
            return;
        }

        const formData = new FormData(form);
        const saveBtn  = document.getElementById('saveBtn');
        const orig     = saveBtn.innerHTML;

        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveBtn.disabled  = true;

        fetch(basePath + '/inventario-inmobiliario/crear', { method: 'POST', body: formData })
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

    document.querySelectorAll('.status-switch').forEach(sw => {
        sw.addEventListener('change', function () {
            const id     = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const activo = this.checked ? 1 : 0;
            const ref    = this;
            fetch(basePath + '/inventario-inmobiliario/update-status', {
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

    const filtroSeccion = document.getElementById('filtroSeccion');
    if (filtroSeccion) {
        filtroSeccion.addEventListener('change', function () {
            const val = this.value;
            document.querySelectorAll('#inmGrid .inm-card').forEach(card => {
                card.style.display = (!val || card.dataset.seccion === val) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.form-eliminar').forEach(f => {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            const nombre = this.querySelector('button').getAttribute('data-nombre');
            Swal.fire({
                title: '¿Eliminar bien?',
                html: `¿Seguro que deseas eliminar <strong>${nombre}</strong> del inventario?`,
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
