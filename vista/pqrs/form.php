<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PQRS — <?php echo htmlspecialchars($comercio['nombre'] ?? 'ChefControl'); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#1a252f 0%,#2c3e50 50%,#1a252f 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.pq-card{background:#fff;border-radius:24px;width:100%;max-width:520px;box-shadow:0 24px 80px rgba(0,0,0,.35);overflow:hidden}
.pq-head{background:linear-gradient(135deg,#1a252f,#2c3e50);color:#fff;padding:28px 30px 24px;text-align:center}
.pq-logo{width:64px;height:64px;background:rgba(255,255,255,.12);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 14px}
.pq-head h1{font-size:20px;font-weight:800;margin-bottom:4px}
.pq-head p{font-size:13px;opacity:.65}
.pq-body{padding:28px 30px}
/* Tipo */
.pq-tipos{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:22px}
.pq-tipo{padding:10px 6px;border:2px solid #e8ecf0;border-radius:12px;cursor:pointer;text-align:center;transition:.18s;background:#fff}
.pq-tipo i{font-size:18px;display:block;margin-bottom:4px}
.pq-tipo span{font-size:11px;font-weight:700;display:block}
.pq-tipo:hover{border-color:#2c3e50;background:#f8f9fa}
.pq-tipo.sel-peticion  {border-color:#3498db;background:#eaf4fb;color:#2980b9}
.pq-tipo.sel-queja     {border-color:#e74c3c;background:#fdedec;color:#c0392b}
.pq-tipo.sel-reclamo   {border-color:#e67e22;background:#fef5e7;color:#d35400}
.pq-tipo.sel-sugerencia{border-color:#27ae60;background:#eafaf1;color:#1e8449}
/* Estrellas */
.pq-stars-wrap{text-align:center;margin-bottom:22px}
.pq-stars-wrap label{display:block;font-size:12px;font-weight:700;color:#636e72;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px}
.pq-stars{display:flex;justify-content:center;gap:6px}
.pq-star{font-size:38px;cursor:pointer;color:#dfe6e9;transition:color .15s,transform .15s;line-height:1}
.pq-star:hover,.pq-star.active{color:#f1c40f;transform:scale(1.15)}
/* Campos */
.pq-field{margin-bottom:16px}
.pq-field label{display:block;font-size:12px;font-weight:700;color:#636e72;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.pq-field input,.pq-field textarea{width:100%;border:1.5px solid #e8ecf0;border-radius:12px;padding:12px 14px;font-size:14px;color:#2c3e50;outline:none;font-family:inherit;resize:none;transition:border-color .2s;background:#fff}
.pq-field input:focus,.pq-field textarea:focus{border-color:#2c3e50}
.pq-field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.pq-required{color:#e74c3c}
.pq-hint{font-size:11px;color:#b2bec3;margin-top:4px}
/* Botón */
.pq-btn{width:100%;background:#2c3e50;color:#fff;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;cursor:pointer;transition:.18s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:6px}
.pq-btn:hover{background:#1a252f;transform:translateY(-1px)}
.pq-btn:disabled{opacity:.6;cursor:default;transform:none}
/* Éxito */
.pq-ok{display:none;text-align:center;padding:40px 30px}
.pq-ok-icon{width:80px;height:80px;background:#eafaf1;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:36px;color:#27ae60;margin:0 auto 20px}
.pq-ok h2{font-size:20px;font-weight:800;color:#2c3e50;margin-bottom:8px}
.pq-ok p{font-size:14px;color:#95a5a6;line-height:1.6}
.pq-ok .pq-btn{max-width:200px;margin:20px auto 0}
/* Required char-count */
input[required]:invalid.touched,textarea[required]:invalid.touched{border-color:#e74c3c}
@media(max-width:480px){
  body{padding:0;align-items:flex-end}
  .pq-card{border-radius:24px 24px 0 0;max-width:100%}
  .pq-tipos{grid-template-columns:repeat(2,1fr)}
  .pq-field-row{grid-template-columns:1fr}
}
</style>
</head>
<body>
<?php
$basePath = Config::getBasePath();
$negocio  = htmlspecialchars($comercio['nombre'] ?? 'ChefControl');
$eslogan  = htmlspecialchars($comercio['eslogan'] ?? 'Tu opinión nos hace crecer');
$tokenJs  = htmlspecialchars($token ?? '', ENT_QUOTES);
?>
<div class="pq-card">
    <div class="pq-head">
        <div class="pq-logo"><i class="fas fa-utensils"></i></div>
        <h1><?php echo $negocio; ?></h1>
        <p>Cuéntanos tu experiencia — cada opinión cuenta</p>
    </div>

    <!-- Formulario -->
    <div class="pq-body" id="pqForm">
        <input type="hidden" id="pqTipoVal" value="sugerencia">
        <input type="hidden" id="pqEstrellas" value="5">

        <!-- Tipo -->
        <div class="pq-tipos">
            <div class="pq-tipo sel-sugerencia" data-tipo="sugerencia" onclick="selTipo(this)">
                <i class="fas fa-lightbulb" style="color:#27ae60"></i>
                <span>Sugerencia</span>
            </div>
            <div class="pq-tipo" data-tipo="peticion" onclick="selTipo(this)">
                <i class="fas fa-hand-paper" style="color:#3498db"></i>
                <span>Petición</span>
            </div>
            <div class="pq-tipo" data-tipo="queja" onclick="selTipo(this)">
                <i class="fas fa-face-angry" style="color:#e74c3c"></i>
                <span>Queja</span>
            </div>
            <div class="pq-tipo" data-tipo="reclamo" onclick="selTipo(this)">
                <i class="fas fa-triangle-exclamation" style="color:#e67e22"></i>
                <span>Reclamo</span>
            </div>
        </div>

        <!-- Estrellas -->
        <div class="pq-stars-wrap">
            <label>¿Cómo calificarías tu experiencia?</label>
            <div class="pq-stars" id="starsRow">
                <span class="pq-star active" data-v="1">★</span>
                <span class="pq-star active" data-v="2">★</span>
                <span class="pq-star active" data-v="3">★</span>
                <span class="pq-star active" data-v="4">★</span>
                <span class="pq-star active" data-v="5">★</span>
            </div>
        </div>

        <!-- Nombre -->
        <div class="pq-field">
            <label>Nombre o Razón Social <span class="pq-required">*</span></label>
            <input type="text" id="pqNombre" placeholder="¿Cómo te llamas?" required>
        </div>

        <!-- Teléfono / Email -->
        <div class="pq-field-row">
            <div class="pq-field">
                <label>Teléfono</label>
                <input type="tel" id="pqTelefono" placeholder="Opcional">
            </div>
            <div class="pq-field">
                <label>Email</label>
                <input type="email" id="pqEmail" placeholder="Opcional">
            </div>
        </div>

        <!-- Mensaje -->
        <div class="pq-field">
            <label>Tu mensaje <span class="pq-required">*</span></label>
            <textarea id="pqMensaje" rows="4" placeholder="Cuéntanos con detalle tu experiencia…" required></textarea>
            <div class="pq-hint" id="charCount">0 / 1000 caracteres</div>
        </div>

        <button class="pq-btn" id="pqSubmit" onclick="enviarPqrs()">
            <i class="fas fa-paper-plane"></i> Enviar
        </button>
    </div>

    <!-- Éxito -->
    <div class="pq-ok" id="pqOk">
        <div class="pq-ok-icon"><i class="fas fa-check"></i></div>
        <h2>¡Gracias por tu opinión!</h2>
        <p>Tu mensaje fue recibido correctamente.<br>Nos comprometemos a revisarlo pronto.</p>
        <button class="pq-btn" onclick="resetForm()"><i class="fas fa-plus"></i> Nuevo envío</button>
    </div>
</div>

<script>
const BASE  = '<?php echo $basePath; ?>';
const TOKEN = '<?php echo $tokenJs; ?>';

// ── Tipo ──────────────────────────────────────────────────────────────────────
function selTipo(el) {
    document.querySelectorAll('.pq-tipo').forEach(t => {
        t.className = 'pq-tipo';
    });
    const tipo = el.dataset.tipo;
    el.classList.add('sel-' + tipo);
    document.getElementById('pqTipoVal').value = tipo;
}

// ── Estrellas ─────────────────────────────────────────────────────────────────
let estrellasSel = 5;
document.querySelectorAll('.pq-star').forEach(s => {
    s.addEventListener('mouseover', () => pintarEstrellas(+s.dataset.v));
    s.addEventListener('mouseout',  () => pintarEstrellas(estrellasSel));
    s.addEventListener('click',     () => {
        estrellasSel = +s.dataset.v;
        document.getElementById('pqEstrellas').value = estrellasSel;
        pintarEstrellas(estrellasSel);
    });
});

function pintarEstrellas(n) {
    document.querySelectorAll('.pq-star').forEach(s => {
        s.classList.toggle('active', +s.dataset.v <= n);
    });
}

// ── Contador de caracteres ────────────────────────────────────────────────────
document.getElementById('pqMensaje').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length + ' / 1000 caracteres';
    if (this.value.length > 1000) this.value = this.value.slice(0, 1000);
});

// ── Enviar ────────────────────────────────────────────────────────────────────
async function enviarPqrs() {
    const nombre  = document.getElementById('pqNombre').value.trim();
    const mensaje = document.getElementById('pqMensaje').value.trim();

    if (!nombre) { document.getElementById('pqNombre').focus(); return; }
    if (!mensaje) { document.getElementById('pqMensaje').focus(); return; }

    const btn = document.getElementById('pqSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando…';

    try {
        const r = await fetch(BASE + '/pqrs_form/' + TOKEN + '/enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre,
                telefono     : document.getElementById('pqTelefono').value.trim(),
                email        : document.getElementById('pqEmail').value.trim(),
                tipo         : document.getElementById('pqTipoVal').value,
                calificacion : +document.getElementById('pqEstrellas').value,
                mensaje,
            }),
        });
        const d = await r.json();
        if (d.success) {
            document.getElementById('pqForm').style.display = 'none';
            document.getElementById('pqOk').style.display   = 'block';
        } else {
            alert(d.message || 'Error al enviar');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
        }
    } catch {
        alert('Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
    }
}

function resetForm() {
    document.getElementById('pqForm').style.display = 'block';
    document.getElementById('pqOk').style.display   = 'none';
    document.getElementById('pqNombre').value        = '';
    document.getElementById('pqTelefono').value      = '';
    document.getElementById('pqEmail').value         = '';
    document.getElementById('pqMensaje').value       = '';
    document.getElementById('charCount').textContent = '0 / 1000 caracteres';
    estrellasSel = 5;
    pintarEstrellas(5);
    document.getElementById('pqSubmit').disabled = false;
    document.getElementById('pqSubmit').innerHTML = '<i class="fas fa-paper-plane"></i> Enviar';
    // reset tipo
    document.querySelectorAll('.pq-tipo').forEach(t => t.className = 'pq-tipo');
    document.querySelector('[data-tipo="sugerencia"]').classList.add('sel-sugerencia');
    document.getElementById('pqTipoVal').value = 'sugerencia';
}
</script>
</body>
</html>
