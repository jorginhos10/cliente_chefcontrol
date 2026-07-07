<?php
require_once __DIR__ . '/../../config/config.php';
$basePath = Config::getBasePath();
$baseUrl  = Config::getBaseUrl();

$estado  = $datos['doc_estado']         ?? 'pendiente';
$nombre  = htmlspecialchars($datos['nombre'] ?? $_SESSION['comercio_nombre'] ?? 'Tu restaurante');
$ok      = !empty($_SESSION['verif_ok']);   unset($_SESSION['verif_ok']);
$error   = $_SESSION['verif_error'] ?? null; unset($_SESSION['verif_error']);

$docs = [
    'cedula_frente'  => ['label' => 'Cédula frente',   'icon' => 'fa-id-card'],
    'cedula_trasera' => ['label' => 'Cédula trasera',  'icon' => 'fa-id-card-clip'],
    'logo'           => ['label' => 'Logo del negocio','icon' => 'fa-image'],
    'foto_negocio'   => ['label' => 'Foto del negocio','icon' => 'fa-store'],
];

$docInfo = [];
foreach ($docs as $key => $d) {
    $ruta    = $datos["doc_{$key}"]         ?? null;
    $rechazo = $datos["doc_{$key}_rechazo"] ?? null;
    $docInfo[$key] = [
        'label'   => $d['label'],
        'icon'    => $d['icon'],
        'ruta'    => $ruta,
        'rechazo' => $rechazo,
        'necesita' => !$ruta || !empty($rechazo),
    ];
}

$totalSubidos = count(array_filter($docInfo, fn($d) => !empty($d['ruta'])));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación — ChefControl</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            overflow-y: auto;
        }
        .verif-wrap {
            width: 100%;
            max-width: 520px;
            background: rgba(255,255,255,.98);
            border-radius: 18px;
            box-shadow: 0 16px 48px rgba(0,0,0,.14);
            padding: 28px 28px 22px;
        }

        /* Header */
        .verif-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid #f1f5f9;
        }
        .header-icon {
            width: 48px; height: 48px; flex-shrink: 0;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .header-icon.pending  { background: #fef3c7; color: #d97706; }
        .header-icon.review   { background: #dbeafe; color: #2563eb; }
        .header-icon.rejected { background: #fee2e2; color: #dc2626; }
        .header-text h2 { font-size: 17px; color: #1e293b; margin: 0 0 3px; font-weight: 700; }
        .header-text p  { font-size: 12px; color: #64748b; margin: 0; line-height: 1.4; }

        /* Progreso */
        .progress-bar {
            display: flex; gap: 6px; margin-bottom: 18px;
        }
        .pb-step {
            flex: 1; height: 3px; border-radius: 2px; background: #e2e8f0;
            transition: background .3s;
        }
        .pb-step.done   { background: #10b981; }
        .pb-step.active { background: #6366f1; }

        /* Alertas */
        .alert {
            border-radius: 8px; padding: 10px 14px;
            margin-bottom: 14px; font-size: 13px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-ok  { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-err { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* Banner rechazo */
        .rechazo-banner {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
            padding: 10px 14px; margin-bottom: 14px;
            font-size: 12px; color: #b91c1c;
            display: flex; align-items: center; gap: 8px;
        }

        /* Filas de documentos */
        .doc-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px; }

        .doc-row {
            display: flex; align-items: center; gap: 12px;
            border: 1.5px solid #e2e8f0; border-radius: 12px;
            padding: 10px 12px; background: #f8fafc;
            transition: border-color .2s, background .2s;
        }
        .doc-row.rechazado { border-color: #fca5a5; background: #fff5f5; }
        .doc-row.aprobado  { border-color: #6ee7b7; background: #f0fdf4; }
        .doc-row.pendiente { border-color: #e2e8f0; }
        .doc-row.con-nuevo { border-color: #818cf8; background: #f5f3ff; }

        /* Icono del doc */
        .doc-row-icon {
            width: 36px; height: 36px; flex-shrink: 0; border-radius: 9px;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .doc-row.rechazado .doc-row-icon { background: #fee2e2; color: #ef4444; }
        .doc-row.aprobado  .doc-row-icon { background: #d1fae5; color: #10b981; }
        .doc-row.pendiente .doc-row-icon { background: #f1f5f9; color: #94a3b8; }
        .doc-row.con-nuevo .doc-row-icon { background: #ede9fe; color: #7c3aed; }

        /* Info del doc */
        .doc-row-info { flex: 1; min-width: 0; }
        .doc-row-label { font-size: 13px; font-weight: 600; color: #1e293b; }
        .doc-row-status {
            font-size: 11px; margin-top: 2px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .status-ok       { color: #10b981; }
        .status-pending  { color: #94a3b8; }
        .status-rechazado{ color: #ef4444; }
        .status-nuevo    { color: #7c3aed; }

        /* Motivo de rechazo (expandido) */
        .rechazo-motivo {
            font-size: 11px; color: #dc2626; margin-top: 4px;
            background: #fee2e2; border-radius: 4px; padding: 3px 7px;
            line-height: 1.4;
        }

        /* Preview miniatura */
        .doc-preview {
            width: 38px; height: 38px; border-radius: 7px; object-fit: cover;
            flex-shrink: 0; border: 1px solid #e2e8f0;
        }
        .doc-preview-pdf {
            width: 38px; height: 38px; border-radius: 7px;
            background: #fef2f2; display: flex; align-items: center; justify-content: center;
            color: #ef4444; font-size: 18px; flex-shrink: 0;
        }

        /* Botón subir */
        .btn-upload {
            flex-shrink: 0; position: relative;
            padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
            cursor: pointer; border: none; white-space: nowrap;
            transition: opacity .2s; display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-upload.primary { background: #6366f1; color: #fff; }
        .btn-upload.outline { background: #fff; color: #6366f1; border: 1.5px solid #6366f1; }
        .btn-upload:hover   { opacity: .85; }
        .btn-upload input[type=file] {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            opacity: 0; cursor: pointer; font-size: 0;
        }

        /* Botón submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: opacity .2s; display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }
        .btn-submit:hover    { opacity: .88; }
        .btn-submit:disabled { opacity: .5; cursor: not-allowed; }

        /* Estado en revisión */
        .review-card {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: 12px; padding: 20px; text-align: center;
            margin-bottom: 18px;
        }
        .review-card h3 { color: #1e40af; margin: 10px 0 6px; font-size: 16px; }
        .review-card p  { color: #3b82f6; font-size: 13px; margin: 0; }
        .doc-thumbs {
            display: grid; grid-template-columns: repeat(4,1fr);
            gap: 8px; margin-top: 14px;
        }
        .doc-thumb {
            border-radius: 8px; overflow: hidden; background: #dbeafe;
            aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
        }
        .doc-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .doc-thumb i   { font-size: 20px; color: #93c5fd; }

        .logout-link {
            display: block; text-align: center; margin-top: 16px;
            color: #94a3b8; font-size: 12px; text-decoration: none;
        }
        .logout-link:hover { color: #6366f1; }
    </style>
</head>
<body>
<div class="background-blur"></div>

<div class="verif-wrap">

    <!-- Header -->
    <div class="verif-header">
        <?php if ($estado === 'en_revision'): ?>
            <div class="header-icon review"><i class="fas fa-clock"></i></div>
            <div class="header-text">
                <h2>Documentos en revisión</h2>
                <p>Estamos verificando tu información. Te notificaremos pronto.</p>
            </div>
        <?php elseif ($estado === 'rechazado'): ?>
            <div class="header-icon rejected"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="header-text">
                <h2>Documentos rechazados</h2>
                <p>Solo sube los documentos marcados en rojo.</p>
            </div>
        <?php else: ?>
            <div class="header-icon pending"><i class="fas fa-file-shield"></i></div>
            <div class="header-text">
                <h2>Verificación de identidad</h2>
                <p>Sube los documentos para activar <strong><?= $nombre ?></strong>.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Progreso -->
    <div class="progress-bar">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="pb-step <?= $totalSubidos >= $i ? 'done' : ($totalSubidos >= $i - 1 && $totalSubidos < 4 ? 'active' : '') ?>"></div>
        <?php endfor; ?>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-ok"><i class="fas fa-check-circle"></i> Documentos guardados correctamente.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <?php if ($estado === 'en_revision'): ?>
    <!-- Estado: en revisión -->
    <div class="review-card">
        <i class="fas fa-hourglass-half" style="font-size:36px;color:#3b82f6;"></i>
        <h3>Revisión en proceso</h3>
        <p>Nuestro equipo está revisando tus documentos.<br>Una vez aprobados podrás acceder al sistema.</p>
        <div class="doc-thumbs">
            <?php foreach ($docInfo as $key => $d): ?>
            <div class="doc-thumb" title="<?= $d['label'] ?>">
                <?php if ($d['ruta'] && !str_ends_with($d['ruta'], '.pdf')): ?>
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($d['ruta']) ?>" alt="<?= $d['label'] ?>">
                <?php else: ?>
                    <i class="fas <?= $d['icon'] ?>"></i>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- Formulario de subida -->
    <form method="POST" action="<?= $basePath ?>/verificacion/subir" enctype="multipart/form-data">
        <div class="doc-list">
        <?php foreach ($docInfo as $key => $d):
            $yaSubido  = !empty($d['ruta']);
            $esPdf     = $yaSubido && str_ends_with($d['ruta'], '.pdf');
            $rechazado = !empty($d['rechazo']);
            $rowClass  = $rechazado ? 'rechazado' : ($d['necesita'] ? 'pendiente' : 'aprobado');
        ?>
        <div class="doc-row <?= $rowClass ?>" id="row-<?= $key ?>">

            <!-- Icono -->
            <div class="doc-row-icon">
                <i class="fas <?= $d['icon'] ?>"></i>
            </div>

            <!-- Info -->
            <div class="doc-row-info">
                <div class="doc-row-label"><?= $d['label'] ?></div>
                <?php if (!$d['necesita']): ?>
                    <div class="doc-row-status status-ok"><i class="fas fa-check-circle"></i> Aprobado</div>
                <?php elseif ($rechazado): ?>
                    <div class="doc-row-status status-rechazado"><i class="fas fa-xmark-circle"></i> Rechazado</div>
                    <div class="rechazo-motivo" id="motivo-<?= $key ?>"><?= htmlspecialchars($d['rechazo']) ?></div>
                <?php else: ?>
                    <div class="doc-row-status status-pending" id="status-<?= $key ?>"><i class="fas fa-upload"></i> Pendiente</div>
                <?php endif; ?>
            </div>

            <!-- Preview o badge OK -->
            <?php if (!$d['necesita']): ?>
                <?php if (!$esPdf): ?>
                    <img src="<?= $baseUrl ?>/<?= htmlspecialchars($d['ruta']) ?>"
                         class="doc-preview" id="prev-<?= $key ?>" alt="">
                <?php else: ?>
                    <div class="doc-preview-pdf"><i class="fas fa-file-pdf"></i></div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Preview oculto hasta que seleccionen archivo -->
                <img class="doc-preview" id="prev-<?= $key ?>" alt="" style="display:none;">
                <div class="doc-preview-pdf" id="pdf-icon-<?= $key ?>" style="display:none;"><i class="fas fa-file-pdf"></i></div>

                <!-- Botón subir -->
                <label class="btn-upload <?= $rechazado ? 'outline' : 'primary' ?>" id="btnlabel-<?= $key ?>">
                    <span id="btntext-<?= $key ?>"><i class="fas fa-upload"></i> Subir</span>
                    <input type="file" name="doc_<?= $key ?>"
                           accept=".jpg,.jpeg,.png,.webp,.pdf"
                           onchange="previewFile(this, '<?= $key ?>')">
                </label>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
        </div>

        <button type="submit" class="btn-submit" id="btnSubir">
            <i class="fas fa-cloud-upload-alt"></i>
            <?= $estado === 'rechazado' ? 'Enviar documentos corregidos' : 'Enviar para verificación' ?>
        </button>
    </form>
    <?php endif; ?>

    <a href="<?= $basePath ?>/logout" class="logout-link">
        <i class="fas fa-right-from-bracket"></i> Cerrar sesión
    </a>
</div>

<script>
function previewFile(input, key) {
    const row     = document.getElementById('row-' + key);
    const prev    = document.getElementById('prev-' + key);
    const pdfIcon = document.getElementById('pdf-icon-' + key);
    const btnLbl  = document.getElementById('btnlabel-' + key);
    const btnText = document.getElementById('btntext-' + key);
    const status  = document.getElementById('status-' + key);
    const motivo  = document.getElementById('motivo-' + key);
    const file    = input.files[0];
    if (!file) return;

    row.classList.remove('rechazado', 'pendiente');
    row.classList.add('con-nuevo');

    if (file.type.startsWith('image/')) {
        if (pdfIcon) pdfIcon.style.display = 'none';
        const reader = new FileReader();
        reader.onload = e => { prev.src = e.target.result; prev.style.display = 'block'; };
        reader.readAsDataURL(file);
    } else {
        prev.style.display = 'none';
        if (pdfIcon) pdfIcon.style.display = 'flex';
    }

    // Solo actualizar el span de texto — el input queda intacto con el archivo
    if (btnLbl) btnLbl.className = 'btn-upload outline';
    if (btnText) {
        const nombre = file.name.length > 14 ? file.name.substring(0, 14) + '…' : file.name;
        btnText.innerHTML = '<i class="fas fa-check"></i> ' + nombre;
    }
    if (status) { status.className = 'doc-row-status status-nuevo'; status.innerHTML = '<i class="fas fa-check"></i> Listo para subir'; }
    if (motivo) motivo.style.display = 'none';
}

const form = document.querySelector('form[action*="verificacion/subir"]');
if (form) {
    form.addEventListener('submit', function() {
        const btn = document.getElementById('btnSubir');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
        }
    });
}
</script>
</body>
</html>
