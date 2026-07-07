<?php
// vista/configuraciones/comercio.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Mi Comercio — CHEFCONTROL';
$paginaActual = 'configuraciones';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();
$jsExtra      = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$c = $comercio ?? [];
$logoUrl = !empty($c['logo']) ? $baseUrl . '/assets/uploads/comercio/' . htmlspecialchars($c['logo']) : '';
?>

<div class="com-wrap">

    <!-- Cabecera -->
    <div class="com-header">
        <a href="<?php echo $basePath; ?>/configuraciones" class="com-back">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="com-header-icon" id="comLogoDisplay">
            <?php if ($logoUrl): ?>
                <img src="<?php echo $logoUrl; ?>" alt="Logo" id="logoImg">
            <?php else: ?>
                <i class="fas fa-store"></i>
            <?php endif; ?>
        </div>
        <div>
            <h1><?php echo !empty($c['nombre']) ? htmlspecialchars($c['nombre']) : 'Mi Comercio'; ?></h1>
            <p><?php echo !empty($c['eslogan']) ? htmlspecialchars($c['eslogan']) : 'Información y datos del negocio'; ?></p>
        </div>
    </div>

    <!-- Formulario -->
    <form id="comercioForm" enctype="multipart/form-data">

        <div class="com-grid">

            <!-- Columna izquierda: datos principales -->
            <div class="com-col">

                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-store"></i> Identidad del Negocio</div>

                    <div class="com-field">
                        <label>Nombre del negocio *</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($c['nombre'] ?? ''); ?>"
                               placeholder="Ej: Restaurante El Sabor" required>
                    </div>

                    <div class="com-row">
                        <div class="com-field">
                            <label>Tipo de negocio</label>
                            <select name="tipo">
                                <?php
                                $tipos = ['Restaurante','Cafetería','Bar','Pizzería','Comida rápida','Food truck','Panadería','Otro'];
                                foreach ($tipos as $t):
                                    $sel = (($c['tipo'] ?? '') === $t) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $t; ?>" <?php echo $sel; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="com-field">
                            <label>Moneda</label>
                            <select name="moneda">
                                <?php
                                $monedas = ['USD'=>'USD — Dólar','CLP'=>'CLP — Peso Chileno','MXN'=>'MXN — Peso Mexicano',
                                            'COP'=>'COP — Peso Colombiano','ARS'=>'ARS — Peso Argentino','PEN'=>'PEN — Sol','EUR'=>'EUR — Euro'];
                                foreach ($monedas as $k => $v):
                                    $sel = (($c['moneda'] ?? 'USD') === $k) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $k; ?>" <?php echo $sel; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="com-field">
                        <label>Eslogan / Descripción corta</label>
                        <input type="text" name="eslogan" value="<?php echo htmlspecialchars($c['eslogan'] ?? ''); ?>"
                               placeholder="Ej: El sabor que te hace volver">
                    </div>
                </div>

                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-clock"></i> Horario de Atención</div>

                    <div class="com-row">
                        <div class="com-field">
                            <label>Apertura</label>
                            <div class="com-input-icon">
                                <i class="fas fa-door-open"></i>
                                <input type="time" name="horario_apertura"
                                       value="<?php echo htmlspecialchars(substr($c['horario_apertura'] ?? '08:00', 0, 5)); ?>">
                            </div>
                        </div>
                        <div class="com-field">
                            <label>Cierre</label>
                            <div class="com-input-icon">
                                <i class="fas fa-door-closed"></i>
                                <input type="time" name="horario_cierre"
                                       value="<?php echo htmlspecialchars(substr($c['horario_cierre'] ?? '22:00', 0, 5)); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="com-horario-preview" id="horarioPreview">
                        <i class="fas fa-clock"></i>
                        Abierto de <strong id="hpApertura"><?php echo substr($c['horario_apertura'] ?? '08:00', 0, 5); ?></strong>
                        a <strong id="hpCierre"><?php echo substr($c['horario_cierre'] ?? '22:00', 0, 5); ?></strong>
                    </div>
                </div>

                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-id-card"></i> Datos Fiscales</div>

                    <div class="com-field">
                        <label>RUT / NIT / Identificación fiscal</label>
                        <input type="text" name="rut" value="<?php echo htmlspecialchars($c['rut'] ?? ''); ?>"
                               placeholder="Ej: 76.123.456-7">
                    </div>
                </div>

                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-location-dot"></i> Ubicación</div>

                    <div class="com-field">
                        <label>Dirección</label>
                        <input type="text" name="direccion" value="<?php echo htmlspecialchars($c['direccion'] ?? ''); ?>"
                               placeholder="Ej: Av. Principal 1234, Local 5">
                    </div>

                    <div class="com-field">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad" value="<?php echo htmlspecialchars($c['ciudad'] ?? ''); ?>"
                               placeholder="Ej: Santiago">
                    </div>
                </div>

            </div>

            <!-- Columna derecha: contacto + logo -->
            <div class="com-col">

                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-phone"></i> Contacto</div>

                    <div class="com-field">
                        <label>Teléfono</label>
                        <div class="com-input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="text" name="telefono" value="<?php echo htmlspecialchars($c['telefono'] ?? ''); ?>"
                                   placeholder="+56 9 1234 5678">
                        </div>
                    </div>

                    <div class="com-field">
                        <label>Correo electrónico</label>
                        <div class="com-input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($c['email'] ?? ''); ?>"
                                   placeholder="contacto@minegocio.com">
                        </div>
                    </div>

                    <div class="com-field">
                        <label>Sitio web</label>
                        <div class="com-input-icon">
                            <i class="fas fa-globe"></i>
                            <input type="url" name="sitio_web" value="<?php echo htmlspecialchars($c['sitio_web'] ?? ''); ?>"
                                   placeholder="https://minegocio.com">
                        </div>
                    </div>
                </div>

                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-image"></i> Logo del Negocio</div>

                    <div class="com-logo-area">
                        <div class="com-logo-preview" id="logoPreview">
                            <?php if ($logoUrl): ?>
                                <img src="<?php echo $logoUrl; ?>" alt="Logo actual" id="logoPreviewImg">
                            <?php else: ?>
                                <div class="com-logo-placeholder" id="logoPlaceholder">
                                    <i class="fas fa-store"></i>
                                    <span>Sin logo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="com-logo-controls">
                            <input type="file" name="logo" id="logoInput" accept="image/*" style="display:none">
                            <button type="button" class="com-btn-upload" onclick="document.getElementById('logoInput').click()">
                                <i class="fas fa-upload"></i> Subir logo
                            </button>
                            <?php if ($logoUrl): ?>
                            <button type="button" class="com-btn-remove" id="btnQuitarLogo" onclick="quitarLogo()">
                                <i class="fas fa-trash"></i> Quitar
                            </button>
                            <?php endif; ?>
                            <small>PNG, JPG, SVG · máx 2 MB<br>Recomendado: fondo transparente</small>
                        </div>
                    </div>
                </div>

                <!-- Vista previa del ticket -->
                <div class="com-section">
                    <div class="com-section-title"><i class="fas fa-receipt"></i> Vista previa en ticket</div>
                    <div class="com-ticket-preview" id="ticketPreview">
                        <div class="tp-line tp-center tp-bold" id="tpNombre"><?php echo htmlspecialchars($c['nombre'] ?? 'MI NEGOCIO'); ?></div>
                        <div class="tp-line tp-center" id="tpEslogan"><?php echo htmlspecialchars($c['eslogan'] ?? ''); ?></div>
                        <div class="tp-sep">- - - - - - - - - - - - - - - - - -</div>
                        <div class="tp-line" id="tpRut"><?php echo !empty($c['rut']) ? 'RUT: ' . htmlspecialchars($c['rut']) : ''; ?></div>
                        <div class="tp-line" id="tpDir"><?php echo htmlspecialchars($c['direccion'] ?? ''); ?></div>
                        <div class="tp-line" id="tpTel"><?php echo !empty($c['telefono']) ? 'Tel: ' . htmlspecialchars($c['telefono']) : ''; ?></div>
                        <div class="tp-sep">- - - - - - - - - - - - - - - - - -</div>
                        <div class="tp-line tp-center tp-gray">Gracias por su visita</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Botones -->
        <div class="com-actions">
            <a href="<?php echo $basePath; ?>/configuraciones" class="com-btn-sec">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button type="submit" class="com-btn-prim" id="saveBtn">
                <i class="fas fa-save"></i> Guardar información
            </button>
        </div>

    </form>
</div>

<style>
.com-wrap { max-width: 1000px; margin: 0 auto; padding: 24px 20px; }

/* Header */
.com-header { display:flex; align-items:center; gap:20px; margin-bottom:32px; }
.com-back { width:40px; height:40px; border-radius:50%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#636e72; text-decoration:none; font-size:16px; flex-shrink:0; transition:background .15s; }
.com-back:hover { background:#dfe6e9; }
.com-header-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#3a28b0,#6c5ce7); display:flex; align-items:center; justify-content:center; font-size:28px; color:#fff; flex-shrink:0; overflow:hidden; }
.com-header-icon img { width:100%; height:100%; object-fit:contain; padding:8px; }
.com-header h1 { font-size:22px; font-weight:800; color:#2c3e50; margin:0 0 4px; }
.com-header p  { font-size:13px; color:#95a5a6; margin:0; }

/* Grid */
.com-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
.com-col  { display:flex; flex-direction:column; gap:16px; }

/* Sections */
.com-section { background:#fff; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.06); }
.com-section-title { font-size:14px; font-weight:800; color:#2c3e50; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.com-section-title i { color:#6c5ce7; }

/* Fields */
.com-field { margin-bottom:14px; }
.com-field:last-child { margin-bottom:0; }
.com-field label { display:block; font-size:12px; font-weight:700; color:#636e72; text-transform:uppercase; letter-spacing:.4px; margin-bottom:6px; }
.com-field input, .com-field select {
    width:100%; border:1.5px solid #dfe6e9; border-radius:9px;
    padding:10px 14px; font-size:14px; color:#2c3e50;
    transition:border-color .2s, box-shadow .2s; box-sizing:border-box;
    background:#fff;
}
.com-field input:focus, .com-field select:focus { outline:none; border-color:#6c5ce7; box-shadow:0 0 0 3px rgba(108,92,231,.12); }
.com-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.com-input-icon { position:relative; }
.com-input-icon i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#b2bec3; font-size:14px; }
.com-input-icon input { padding-left:36px; }

/* Logo */
.com-logo-area { display:flex; gap:16px; align-items:flex-start; }
.com-logo-preview { width:110px; height:110px; border-radius:14px; border:2px dashed #dfe6e9; display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0; background:#f8f9fa; }
.com-logo-preview img { width:100%; height:100%; object-fit:contain; padding:8px; }
.com-logo-placeholder { display:flex; flex-direction:column; align-items:center; gap:6px; color:#b2bec3; font-size:12px; }
.com-logo-placeholder i { font-size:28px; }
.com-logo-controls { display:flex; flex-direction:column; gap:8px; flex:1; }
.com-btn-upload { background:#6c5ce7; color:#fff; border:none; border-radius:8px; padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; }
.com-btn-upload:hover { background:#5a4bd1; }
.com-btn-remove { background:#fdedec; color:#e74c3c; border:1px solid #f5b7b1; border-radius:8px; padding:7px 14px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; }
.com-btn-remove:hover { background:#f5b7b1; }
.com-logo-controls small { font-size:11px; color:#b2bec3; line-height:1.5; }

/* Ticket preview */
.com-ticket-preview { background:#fff9f0; border:1px dashed #f0c060; border-radius:10px; padding:14px 16px; font-family:'Courier New',monospace; font-size:11px; color:#2c3e50; }
.tp-line   { margin:2px 0; min-height:14px; }
.tp-center { text-align:center; }
.tp-bold   { font-weight:700; font-size:13px; }
.tp-sep    { color:#bdc3c7; text-align:center; margin:6px 0; }
.tp-gray   { color:#95a5a6; }

/* Horario preview */
.com-horario-preview {
    margin-top: 12px;
    background: #f0f8ff;
    border: 1px solid #d6eaf8;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    color: #2980b9;
    display: flex;
    align-items: center;
    gap: 8px;
}
.com-horario-preview i { color: #3498db; }
.com-horario-preview strong { color: #1a6fa0; }

/* Actions */
.com-actions { display:flex; gap:12px; justify-content:flex-end; padding-top:8px; }
.com-btn-prim { background:linear-gradient(135deg,#3a28b0,#6c5ce7); color:#fff; border:none; border-radius:10px; padding:12px 24px; font-size:14px; font-weight:800; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 14px rgba(108,92,231,.3); transition:transform .15s, box-shadow .15s; }
.com-btn-prim:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(108,92,231,.4); }
.com-btn-sec  { background:#f0f0f0; color:#636e72; border:none; border-radius:10px; padding:12px 20px; font-size:14px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; text-decoration:none; }
.com-btn-sec:hover { background:#dfe6e9; }

@media (max-width:700px) {
    .com-grid { grid-template-columns:1fr; }
    .com-row  { grid-template-columns:1fr; }
}
</style>

<script>
(function(){
    const BP = <?php echo json_encode($basePath); ?>;

    /* Preview en tiempo real */
    function bind(fieldName, tpId, prefix) {
        const el = document.querySelector(`[name="${fieldName}"]`);
        const tp = document.getElementById(tpId);
        if (!el || !tp) return;
        el.addEventListener('input', () => {
            tp.textContent = prefix ? prefix + el.value : el.value;
        });
    }
    bind('nombre',    'tpNombre', '');
    bind('eslogan',   'tpEslogan', '');
    bind('rut',       'tpRut',    'RUT: ');
    bind('direccion', 'tpDir',    '');
    bind('telefono',  'tpTel',    'Tel: ');

    /* Actualizar preview de horario */
    document.querySelector('[name="horario_apertura"]').addEventListener('input', function() {
        document.getElementById('hpApertura').textContent = this.value;
    });
    document.querySelector('[name="horario_cierre"]').addEventListener('input', function() {
        document.getElementById('hpCierre').textContent = this.value;
    });

    /* Preview logo */
    document.getElementById('logoInput').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const prev = document.getElementById('logoPreview');
            prev.innerHTML = `<img src="${e.target.result}" alt="Logo" id="logoPreviewImg">`;
            prev.style.borderStyle = 'solid';
            prev.style.borderColor = '#6c5ce7';
            // Mostrar botón quitar si no existe
            if (!document.getElementById('btnQuitarLogo')) {
                const btn = document.createElement('button');
                btn.type = 'button'; btn.id = 'btnQuitarLogo';
                btn.className = 'com-btn-remove';
                btn.innerHTML = '<i class="fas fa-trash"></i> Quitar';
                btn.onclick = quitarLogo;
                document.querySelector('.com-logo-controls').insertBefore(btn, document.querySelector('.com-logo-controls small'));
            }
        };
        reader.readAsDataURL(file);
    });

    window.quitarLogo = function() {
        const prev = document.getElementById('logoPreview');
        prev.innerHTML = `<div class="com-logo-placeholder" id="logoPlaceholder"><i class="fas fa-store"></i><span>Sin logo</span></div>`;
        prev.style.borderStyle = 'dashed';
        prev.style.borderColor = '#dfe6e9';
        document.getElementById('logoInput').value = '';
        const btnQ = document.getElementById('btnQuitarLogo');
        if (btnQ) btnQ.remove();
    };

    /* Guardar */
    document.getElementById('comercioForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveBtn');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        fetch(BP + '/configuraciones/guardar-comercio', {
            method: 'POST',
            body: new FormData(this),
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.innerHTML = orig;
            if (data.success) {
                Swal.fire({ icon:'success', title:'¡Guardado!', text: data.message, timer:1800, showConfirmButton:false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon:'error', title:'Error', text: data.message });
            }
        })
        .catch(() => {
            btn.disabled = false; btn.innerHTML = orig;
            Swal.fire({ icon:'error', title:'Error', text:'No se pudo conectar con el servidor.' });
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
