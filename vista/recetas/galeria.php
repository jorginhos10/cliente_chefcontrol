<?php
// vista/recetas/galeria.php — ventana emergente: cuadro de carga + banco de fotos
require_once __DIR__ . '/../../config/security.php';

$basePath = Config::getBasePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Banco de fotos - CHEFCONTROL</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0; font-family: 'Segoe UI', Arial, sans-serif;
        background: #f4f6f8; color: #2c3e50;
    }
    .bandeja-header {
        background: linear-gradient(135deg, #2471a3, #3498db); color: #fff;
        padding: 14px 20px; display: flex; align-items: center; gap: 10px;
        font-size: 16px; font-weight: 700;
    }
    .bandeja-body { display: flex; gap: 18px; padding: 20px; align-items: flex-start; }

    .upload-box {
        flex: 0 0 170px; height: 170px;
        border: 2px dashed #b2bec3; border-radius: 12px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 6px; text-align: center; cursor: pointer; color: #7f8c8d;
        background: #fff; transition: border-color .15s, background .15s; padding: 10px;
    }
    .upload-box:hover, .upload-box.dragover { border-color: #2980b9; background: #f0f8ff; }
    .upload-box i     { font-size: 30px; color: #b2bec3; }
    .upload-box p     { margin: 0; font-size: 12px; }
    .upload-box small { color: #95a5a6; font-size: 10px; }
    .upload-box img   { max-width: 100%; max-height: 100%; border-radius: 8px; object-fit: cover; }

    .galeria-wrap { flex: 1; min-width: 0; }
    .galeria-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 10px; max-height: 420px; overflow-y: auto; padding: 2px;
    }
    .galeria-item {
        position: relative; width: 100%; aspect-ratio: 1; border-radius: 8px;
        overflow: hidden; border: 2px solid #e0e0e0; transition: border-color .15s;
        background: #fff;
    }
    .galeria-item:hover { border-color: #2980b9; }
    .galeria-item img { width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer; }

    .galeria-item-del {
        position: absolute; top: 4px; right: 4px; z-index: 2;
        width: 20px; height: 20px; border: none; border-radius: 50%;
        background: rgba(0,0,0,.55); color: #fff; font-size: 10px;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: background .15s;
    }
    .galeria-item-del:hover { background: #c0392b; }

    .galeria-item-overlay {
        position: absolute; inset: 0; background: rgba(0,0,0,0);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; pointer-events: none; transition: background .15s, opacity .15s;
    }
    .galeria-item.selected .galeria-item-overlay {
        background: rgba(0,0,0,.4); opacity: 1; pointer-events: auto;
    }
    .galeria-item-select {
        padding: 5px 10px; border: none; border-radius: 6px;
        background: #2980b9; color: #fff; font-size: 11px; font-weight: 700; cursor: pointer;
    }
    .galeria-item-select:hover { background: #2471a3; }

    .galeria-empty, .galeria-loading {
        text-align: center; color: #95a5a6; padding: 40px 10px; font-size: 13px; width: 100%;
    }
    .galeria-empty i, .galeria-loading i { font-size: 30px; display: block; margin-bottom: 8px; color: #dde0e4; }

    .bandeja-msg { padding: 0 20px 14px; font-size: 13px; }
</style>
</head>
<body>

<div class="bandeja-header">
    <i class="fas fa-images"></i> Banco de fotos
</div>

<div class="bandeja-body">
    <div class="upload-box" id="uploadBox">
        <input type="file" id="uploadFileInput" accept="image/png,image/jpeg,image/gif,image/webp" hidden>
        <div id="uploadBoxContent">
            <i class="fas fa-cloud-arrow-up"></i>
            <p>Cargar foto</p>
            <small>JPG, PNG, GIF, WEBP<br>máx. 3MB</small>
        </div>
    </div>

    <div class="galeria-wrap">
        <div class="galeria-grid" id="galeriaGrid">
            <div class="galeria-loading"><i class="fas fa-spinner fa-spin"></i><br>Cargando imágenes...</div>
        </div>
    </div>
</div>

<div class="bandeja-msg" id="bandejaMsg"></div>

<script>
const basePath = <?php echo json_encode($basePath); ?>;

const uploadBox        = document.getElementById('uploadBox');
const uploadBoxContent = document.getElementById('uploadBoxContent');
const uploadFileInput  = document.getElementById('uploadFileInput');
const galeriaGrid      = document.getElementById('galeriaGrid');
const bandejaMsg       = document.getElementById('bandejaMsg');

const UPLOAD_PLACEHOLDER = '<i class="fas fa-cloud-arrow-up"></i><p>Cargar foto</p><small>JPG, PNG, GIF, WEBP<br>máx. 3MB</small>';

function notificarOpener(url) {
    if (window.opener && !window.opener.closed && typeof window.opener.recetaFotoSeleccionada === 'function') {
        window.opener.recetaFotoSeleccionada(url);
    }
    window.close();
}

function cargarGaleria() {
    galeriaGrid.innerHTML = '<div class="galeria-loading"><i class="fas fa-spinner fa-spin"></i><br>Cargando imágenes...</div>';
    fetch(basePath + '/recetas/banco-imagenes')
        .then(r => r.json())
        .then(data => {
            const imagenes = (data.success && Array.isArray(data.imagenes)) ? data.imagenes : [];
            renderGaleria(imagenes);
        })
        .catch(() => {
            galeriaGrid.innerHTML = '<div class="galeria-empty"><i class="fas fa-triangle-exclamation"></i>Error al cargar el banco de fotos</div>';
        });
}

function renderGaleria(imagenes) {
    if (!imagenes.length) {
        galeriaGrid.innerHTML = '<div class="galeria-empty"><i class="fas fa-images"></i>Aún no has subido imágenes</div>';
        return;
    }
    galeriaGrid.innerHTML = imagenes.map(url => `
        <div class="galeria-item" data-url="${url}">
            <button type="button" class="galeria-item-del" title="Eliminar"><i class="fas fa-times"></i></button>
            <img src="${url}" alt="foto" loading="lazy">
            <div class="galeria-item-overlay">
                <button type="button" class="galeria-item-select">Seleccionar</button>
            </div>
        </div>`).join('');

    galeriaGrid.querySelectorAll('.galeria-item').forEach(item => {
        item.querySelector('img').addEventListener('click', () => alternarSeleccion(item));
        item.querySelector('.galeria-item-select').addEventListener('click', e => {
            e.stopPropagation();
            notificarOpener(item.dataset.url);
        });
        item.querySelector('.galeria-item-del').addEventListener('click', e => {
            e.stopPropagation();
            eliminarImagenBanco(item);
        });
    });
}

function alternarSeleccion(item) {
    const yaSeleccionado = item.classList.contains('selected');
    galeriaGrid.querySelectorAll('.galeria-item.selected').forEach(el => el.classList.remove('selected'));
    if (!yaSeleccionado) item.classList.add('selected');
}

function eliminarImagenBanco(item) {
    const url      = item.dataset.url;
    const filename = url.split('/').pop();
    item.style.opacity = '.5';
    bandejaMsg.innerHTML = '';

    fetch(basePath + '/recetas/eliminar-imagen-banco', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ filename }),
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                item.remove();
                if (!galeriaGrid.querySelector('.galeria-item')) {
                    galeriaGrid.innerHTML = '<div class="galeria-empty"><i class="fas fa-images"></i>Aún no has subido imágenes</div>';
                }
            } else {
                item.style.opacity = '1';
                bandejaMsg.innerHTML = `<span style="color:#c0392b">${data.message || 'Error al eliminar la imagen'}</span>`;
            }
        })
        .catch(() => {
            item.style.opacity = '1';
            bandejaMsg.innerHTML = '<span style="color:#c0392b">Error de red al eliminar la imagen.</span>';
        });
}

function manejarArchivo(file) {
    const permitidos = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    bandejaMsg.innerHTML = '';
    if (!permitidos.includes(file.type)) {
        bandejaMsg.innerHTML = '<span style="color:#c0392b">Formato no permitido. Usa JPG, PNG, GIF o WEBP.</span>';
        return;
    }
    if (file.size > 3 * 1024 * 1024) {
        bandejaMsg.innerHTML = '<span style="color:#c0392b">La imagen no puede superar 3MB.</span>';
        return;
    }

    uploadBoxContent.innerHTML = '<i class="fas fa-spinner fa-spin"></i><p>Subiendo...</p>';

    const fd = new FormData();
    fd.append('imagen', file);

    fetch(basePath + '/recetas/subir-imagen', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            uploadBoxContent.innerHTML = UPLOAD_PLACEHOLDER;
            uploadFileInput.value = '';
            if (data.success) {
                cargarGaleria(); // alimenta el banco, sin seleccionarla
            } else {
                bandejaMsg.innerHTML = `<span style="color:#c0392b">${data.message || 'Error al subir la imagen'}</span>`;
            }
        })
        .catch(() => {
            uploadBoxContent.innerHTML = UPLOAD_PLACEHOLDER;
            uploadFileInput.value = '';
            bandejaMsg.innerHTML = '<span style="color:#c0392b">Error de red al subir la imagen.</span>';
        });
}

uploadBox.addEventListener('click', () => uploadFileInput.click());
uploadBox.addEventListener('dragover', function (e) { e.preventDefault(); this.classList.add('dragover'); });
uploadBox.addEventListener('dragleave', function () { this.classList.remove('dragover'); });
uploadBox.addEventListener('drop', function (e) {
    e.preventDefault();
    this.classList.remove('dragover');
    if (e.dataTransfer.files.length) manejarArchivo(e.dataTransfer.files[0]);
});
uploadFileInput.addEventListener('change', function () {
    if (this.files.length) manejarArchivo(this.files[0]);
});

cargarGaleria();
</script>

</body>
</html>
