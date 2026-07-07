<?php
// vista/pqrs/index.php
require_once __DIR__ . '/../../config/security.php';

$titulo       = 'PQRS — CHEFCONTROL';
$paginaActual = 'pqrs';
$basePath     = Config::getBasePath();
$baseUrl      = Config::getBaseUrl();

require_once __DIR__ . '/../complementos/header.php';

$fTipo   = $_GET['tipo']   ?? '';
$fEstado = $_GET['estado'] ?? '';
$fQ      = $_GET['q']      ?? '';

$enlacePublico = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
               . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/pqrs/' . ($token ?? '');

function pqrsColor(int $id): string {
    $c = ['#e74c3c','#3498db','#2ecc71','#9b59b6','#e67e22','#1abc9c','#f39c12','#16a085'];
    return $c[$id % count($c)];
}
function pqrsInitials(string $n): string {
    $p = array_values(array_filter(explode(' ', trim($n))));
    return strtoupper(mb_substr($p[0] ?? '?', 0, 1)) . strtoupper(mb_substr($p[1] ?? '', 0, 1));
}
function tiempoRelativo(string $fecha): string {
    $d = time() - strtotime($fecha);
    if ($d < 60)       return 'Hace un momento';
    if ($d < 3600)     return 'Hace ' . floor($d/60)       . ' min';
    if ($d < 86400)    return 'Hace ' . floor($d/3600)      . ' h';
    if ($d < 2592000)  return 'Hace ' . floor($d/86400)     . ' días';
    if ($d < 31536000) return 'Hace ' . floor($d/2592000)   . ' meses';
    return 'Hace '                    . floor($d/31536000)  . ' años';
}
function estrellas(int $n): string {
    return '<span style="color:#f1c40f">' . str_repeat('★', $n) . '</span>'
         . '<span style="color:#dfe6e9">' . str_repeat('★', 5 - $n) . '</span>';
}

$tipoMeta = [
    'peticion'   => ['label' => 'Petición',   'color' => '#3498db', 'bg' => '#eaf4fb', 'icon' => 'fa-hand-paper'],
    'queja'      => ['label' => 'Queja',       'color' => '#e74c3c', 'bg' => '#fdedec', 'icon' => 'fa-face-angry'],
    'reclamo'    => ['label' => 'Reclamo',     'color' => '#e67e22', 'bg' => '#fef5e7', 'icon' => 'fa-triangle-exclamation'],
    'sugerencia' => ['label' => 'Sugerencia',  'color' => '#27ae60', 'bg' => '#eafaf1', 'icon' => 'fa-lightbulb'],
];
$estadoMeta = [
    'pendiente'   => ['label' => 'Pendiente',   'color' => '#e67e22', 'bg' => '#fef5e7'],
    'en_revision' => ['label' => 'En revisión', 'color' => '#2980b9', 'bg' => '#eaf4fb'],
    'resuelto'    => ['label' => 'Resuelto',    'color' => '#27ae60', 'bg' => '#eafaf1'],
];
?>

<div class="pq-wrap">

    <!-- Cabecera -->
    <div class="pq-header">
        <div>
            <h1><i class="fas fa-comment-dots"></i> PQRS</h1>
            <p>Peticiones, Quejas, Reclamos y Sugerencias de tus clientes</p>
        </div>
        <div class="pq-link-box">
            <span class="pq-link-label"><i class="fas fa-link"></i> Enlace público</span>
            <span class="pq-link-url" id="enlacePublico" title="<?php echo htmlspecialchars($enlacePublico); ?>">
                <?php echo htmlspecialchars($enlacePublico); ?>
            </span>
            <button class="pq-btn-copy" onclick="copiarEnlace()" title="Copiar enlace">
                <i class="fas fa-copy" id="copyIcon"></i>
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="pq-stats">
        <div class="pq-stat" id="statTotal">
            <div class="pq-stat-icon" style="background:#f5eef8;color:#8e44ad"><i class="fas fa-comment-dots"></i></div>
            <div><div class="pq-stat-num"><?php echo $stats['total']; ?></div><div class="pq-stat-lbl">Total</div></div>
        </div>
        <div class="pq-stat" id="statPend">
            <div class="pq-stat-icon" style="background:#fef5e7;color:#e67e22"><i class="fas fa-clock"></i></div>
            <div><div class="pq-stat-num"><?php echo $stats['pendientes']; ?></div><div class="pq-stat-lbl">Pendientes</div></div>
        </div>
        <div class="pq-stat" id="statRes">
            <div class="pq-stat-icon" style="background:#eafaf1;color:#27ae60"><i class="fas fa-check-circle"></i></div>
            <div><div class="pq-stat-num"><?php echo $stats['resueltos']; ?></div><div class="pq-stat-lbl">Resueltos</div></div>
        </div>
        <div class="pq-stat" id="statProm">
            <div class="pq-stat-icon" style="background:#fef9e7;color:#f1c40f"><i class="fas fa-star"></i></div>
            <div><div class="pq-stat-num"><?php echo $stats['promedio']; ?></div><div class="pq-stat-lbl">Promedio ⭐</div></div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" action="<?php echo $basePath; ?>/pqrs" class="pq-filters" id="filtrosForm">
        <div class="pq-filter-group">
            <i class="fas fa-search pq-fi"></i>
            <input type="text" name="q" value="<?php echo htmlspecialchars($fQ); ?>"
                   placeholder="Buscar nombre o mensaje…" class="pq-input" id="pqBuscar">
        </div>
        <div class="pq-filter-group">
            <i class="fas fa-tag pq-fi"></i>
            <select name="tipo" class="pq-select" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipoMeta as $val => $m): ?>
                <option value="<?php echo $val; ?>" <?php echo $fTipo === $val ? 'selected' : ''; ?>>
                    <?php echo $m['label']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="pq-filter-group">
            <i class="fas fa-filter pq-fi"></i>
            <select name="estado" class="pq-select" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <?php foreach ($estadoMeta as $val => $m): ?>
                <option value="<?php echo $val; ?>" <?php echo $fEstado === $val ? 'selected' : ''; ?>>
                    <?php echo $m['label']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="pq-btn-filter"><i class="fas fa-search"></i> Filtrar</button>
        <?php if ($fQ || $fTipo || $fEstado): ?>
        <a href="<?php echo $basePath; ?>/pqrs" class="pq-btn-clear"><i class="fas fa-xmark"></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Lista de cards -->
    <div class="pq-list" id="pqList">
    <?php if (empty($pqrsList)): ?>
        <div class="pq-empty">
            <i class="fas fa-comment-slash"></i>
            <p>No hay PQRS <?php echo ($fTipo||$fEstado||$fQ) ? 'con los filtros aplicados' : 'registradas aún'; ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($pqrsList as $p):
            $tm  = $tipoMeta[$p['tipo']]   ?? $tipoMeta['sugerencia'];
            $em  = $estadoMeta[$p['estado']] ?? $estadoMeta['pendiente'];
            $cal = max(1, min(5, (int)$p['calificacion']));
        ?>
        <div class="pq-card" id="card-<?php echo $p['id']; ?>">
            <div class="pq-card-top">
                <!-- Avatar -->
                <div class="pq-avatar" style="background:<?php echo pqrsColor((int)$p['id']); ?>">
                    <?php echo pqrsInitials($p['nombre']); ?>
                </div>
                <!-- Info -->
                <div class="pq-card-info">
                    <div class="pq-card-name"><?php echo htmlspecialchars($p['nombre']); ?></div>
                    <div class="pq-card-meta">
                        <span class="pq-stars"><?php echo estrellas($cal); ?></span>
                        <span class="pq-dot">·</span>
                        <span class="pq-time"><?php echo tiempoRelativo($p['created_at']); ?></span>
                        <?php if ($p['email'] || $p['telefono']): ?>
                        <span class="pq-dot">·</span>
                        <span class="pq-contact">
                            <?php if ($p['telefono']): ?><i class="fas fa-phone"></i> <?php echo htmlspecialchars($p['telefono']); ?><?php endif; ?>
                            <?php if ($p['email']): ?><i class="fas fa-envelope" style="margin-left:8px"></i> <?php echo htmlspecialchars($p['email']); ?><?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Badges + acciones -->
                <div class="pq-card-right">
                    <span class="pq-badge" style="color:<?php echo $tm['color']; ?>;background:<?php echo $tm['bg']; ?>">
                        <i class="fas <?php echo $tm['icon']; ?>"></i> <?php echo $tm['label']; ?>
                    </span>
                    <span class="pq-badge" style="color:<?php echo $em['color']; ?>;background:<?php echo $em['bg']; ?>">
                        <?php echo $em['label']; ?>
                    </span>
                    <div class="pq-actions">
                        <?php if ($p['estado'] === 'pendiente'): ?>
                        <button class="pq-btn-act review" onclick="cambiarEstado(<?php echo $p['id']; ?>,'en_revision')" title="Marcar en revisión">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php elseif ($p['estado'] === 'en_revision'): ?>
                        <button class="pq-btn-act seguimiento" onclick="abrirSeguimiento(<?php echo $p['id']; ?>)" title="Registrar seguimiento y resolver">
                            <i class="fas fa-clipboard-check"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mensaje -->
            <div class="pq-mensaje" id="msg-<?php echo $p['id']; ?>">
                <?php echo nl2br(htmlspecialchars($p['mensaje'])); ?>
            </div>

            <!-- Seguimiento registrado (solo resueltos) -->
            <?php if ($p['respuesta']): ?>
            <div class="pq-seguimiento-block">
                <div class="pq-seg-head"><i class="fas fa-clipboard-check"></i> Seguimiento registrado</div>
                <div class="pq-seg-text"><?php echo nl2br(htmlspecialchars($p['respuesta'])); ?></div>
            </div>
            <?php endif; ?>

            <!-- Caja seguimiento (oculta, solo en_revision) -->
            <?php if ($p['estado'] === 'en_revision'): ?>
            <div class="pq-seg-box" id="seg-<?php echo $p['id']; ?>" style="display:none">
                <div class="pq-seg-box-label"><i class="fas fa-clipboard-list"></i> ¿Cómo se resolvió la inconformidad?</div>
                <textarea class="pq-seg-input" id="segText-<?php echo $p['id']; ?>"
                          rows="3" placeholder="Describe las acciones tomadas para resolver el caso…"></textarea>
                <div class="pq-seg-foot">
                    <button class="pq-seg-cancel" onclick="cerrarSeguimiento(<?php echo $p['id']; ?>)">Cancelar</button>
                    <button class="pq-seg-send" onclick="enviarSeguimiento(<?php echo $p['id']; ?>)">
                        <i class="fas fa-clipboard-check"></i> Marcar como resuelto
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>


<style>
.pq-wrap { display:flex; flex-direction:column; gap:0; background:#f0f2f5; min-height:calc(100vh - 70px); }

/* Header */
.pq-header { background:linear-gradient(135deg,#1a252f,#2c3e50); color:#fff; padding:20px 28px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.pq-header h1 { margin:0; font-size:22px; display:flex; align-items:center; gap:10px; }
.pq-header p  { margin:4px 0 0; font-size:13px; opacity:.65; }

/* Enlace público */
.pq-link-box { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,.1); border-radius:12px; padding:10px 14px; min-width:0; max-width:420px; }
.pq-link-label { font-size:12px; font-weight:700; opacity:.7; white-space:nowrap; }
.pq-link-url { font-size:12px; color:#74b9ff; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1; min-width:0; }
.pq-btn-copy { background:rgba(255,255,255,.15); border:none; color:#fff; width:32px; height:32px; border-radius:8px; cursor:pointer; flex-shrink:0; font-size:14px; display:flex; align-items:center; justify-content:center; transition:.15s; }
.pq-btn-copy:hover { background:rgba(255,255,255,.3); }

/* Stats */
.pq-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; padding:20px 28px; }
.pq-stat  { background:#fff; border-radius:14px; padding:18px 20px; box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; align-items:center; gap:14px; }
.pq-stat-icon { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.pq-stat-num  { font-size:26px; font-weight:800; color:#2c3e50; line-height:1; }
.pq-stat-lbl  { font-size:12px; color:#95a5a6; margin-top:3px; }

/* Filtros */
.pq-filters { display:flex; align-items:center; gap:10px; background:#fff; padding:12px 28px; flex-wrap:wrap; border-bottom:1px solid #e8ecf0; }
.pq-filter-group { display:flex; align-items:center; gap:6px; }
.pq-fi { color:#95a5a6; font-size:13px; }
.pq-input { border:1.5px solid #dfe6e9; border-radius:8px; padding:7px 12px; font-size:13px; color:#2c3e50; background:#fff; outline:none; width:200px; }
.pq-input:focus { border-color:#2c3e50; }
.pq-select { border:1.5px solid #dfe6e9; border-radius:8px; padding:7px 10px; font-size:13px; color:#2c3e50; background:#fff; outline:none; cursor:pointer; }
.pq-btn-filter { background:#2c3e50; color:#fff; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.pq-btn-filter:hover { background:#1a252f; }
.pq-btn-clear { color:#e74c3c; font-size:13px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:5px; padding:4px 8px; border-radius:6px; }
.pq-btn-clear:hover { background:#fdedec; }

/* Lista */
.pq-list { padding:20px 28px; display:flex; flex-direction:column; gap:14px; }
.pq-empty { text-align:center; padding:60px 20px; color:#b2bec3; background:#fff; border-radius:16px; }
.pq-empty i { font-size:44px; display:block; margin-bottom:14px; }

/* Card */
.pq-card { background:#fff; border-radius:16px; padding:20px 22px; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:box-shadow .2s; }
.pq-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.1); }
.pq-card-top { display:flex; align-items:flex-start; gap:14px; }
.pq-avatar { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:800; color:#fff; flex-shrink:0; }
.pq-card-info { flex:1; min-width:0; }
.pq-card-name { font-weight:800; color:#2c3e50; font-size:14px; }
.pq-card-meta { display:flex; align-items:center; flex-wrap:wrap; gap:5px; margin-top:4px; font-size:12px; color:#95a5a6; }
.pq-stars  { font-size:14px; letter-spacing:1px; }
.pq-dot    { opacity:.5; }
.pq-contact { font-size:11px; }
.pq-card-right { display:flex; align-items:center; gap:8px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end; }
.pq-badge { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; white-space:nowrap; display:inline-flex; align-items:center; gap:5px; }

/* Acciones */
.pq-actions { display:flex; gap:5px; }
.pq-btn-act { width:30px; height:30px; border:none; border-radius:7px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:13px; transition:.15s; }
.pq-btn-act.review { background:#fef5e7; color:#e67e22; }
.pq-btn-act.review:hover { background:#e67e22; color:#fff; }
.pq-btn-act.seguimiento { background:#eafaf1; color:#27ae60; }
.pq-btn-act.seguimiento:hover { background:#27ae60; color:#fff; }

/* Mensaje */
.pq-mensaje { font-size:13.5px; color:#2c3e50; line-height:1.65; margin-top:14px; padding-left:56px; }

/* Seguimiento registrado */
.pq-seguimiento-block { margin-top:12px; margin-left:56px; background:#eafaf1; border-left:3px solid #27ae60; border-radius:0 10px 10px 0; padding:12px 14px; }
.pq-seg-head { font-size:11px; font-weight:700; color:#27ae60; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.pq-seg-text { font-size:13px; color:#2c3e50; line-height:1.6; }

/* Caja seguimiento */
.pq-seg-box { margin-top:14px; margin-left:56px; background:#f8fffe; border:1.5px solid #a9dfbf; border-radius:12px; padding:14px; }
.pq-seg-box-label { font-size:12px; font-weight:700; color:#27ae60; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.pq-seg-input { width:100%; border:1.5px solid #d5f5e3; border-radius:10px; padding:10px 12px; font-size:13px; font-family:inherit; resize:none; outline:none; transition:border-color .2s; background:#fff; }
.pq-seg-input:focus { border-color:#27ae60; }
.pq-seg-foot { display:flex; gap:8px; justify-content:flex-end; margin-top:10px; }
.pq-seg-cancel { background:#f0f2f5; color:#636e72; border:none; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; }
.pq-seg-cancel:hover { background:#e0e4e8; }
.pq-seg-send { background:#27ae60; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.pq-seg-send:hover { background:#1e8449; }
.pq-seg-send:disabled { opacity:.6; cursor:default; }

/* Indicador nuevo registro */
.pq-card.nuevo { animation:newCard .6s ease; border-left:3px solid #27ae60; }
@keyframes newCard { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }

@media(max-width:1024px) { .pq-stats { grid-template-columns:repeat(2,1fr); padding:16px 20px; } }
@media(max-width:768px) {
    .pq-header { flex-direction:column; align-items:flex-start; }
    .pq-link-box { max-width:100%; width:100%; }
    .pq-list { padding:16px; }
    .pq-card-top { flex-wrap:wrap; }
    .pq-card-right { width:100%; margin-top:8px; }
    .pq-mensaje,.pq-seguimiento-block,.pq-seg-box { padding-left:0; margin-left:0; }
}
</style>

<script>
const BASE = '<?php echo $basePath; ?>';
let lastCount = <?php echo count($pqrsList); ?>;

// ── Copiar enlace ─────────────────────────────────────────────────────────────
function copiarEnlace() {
    const url = document.getElementById('enlacePublico').title;
    navigator.clipboard.writeText(url).then(() => {
        const icon = document.getElementById('copyIcon');
        icon.className = 'fas fa-check';
        setTimeout(() => icon.className = 'fas fa-copy', 2000);
    });
}

// ── Seguimiento ───────────────────────────────────────────────────────────────
function abrirSeguimiento(id) {
    document.getElementById('seg-' + id).style.display = 'block';
    document.getElementById('segText-' + id).focus();
}
function cerrarSeguimiento(id) {
    document.getElementById('seg-' + id).style.display = 'none';
    document.getElementById('segText-' + id).value = '';
}
async function enviarSeguimiento(id) {
    const texto = document.getElementById('segText-' + id).value.trim();
    if (!texto) { document.getElementById('segText-' + id).focus(); return; }
    const btn = document.querySelector('#seg-' + id + ' .pq-seg-send');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';
    try {
        const r = await fetch(BASE + '/pqrs/responder', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, respuesta: texto })
        });
        const d = await r.json();
        if (d.success) location.reload();
        else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-clipboard-check"></i> Marcar como resuelto'; }
    } catch {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-clipboard-check"></i> Marcar como resuelto';
    }
}

// ── Cambiar estado ────────────────────────────────────────────────────────────
async function cambiarEstado(id, estado) {
    const r = await fetch(BASE + '/pqrs/cambiar-estado', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id, estado })
    });
    const d = await r.json();
    if (d.success) location.reload();
}

// ── Polling dinámico (cada 30 s) ──────────────────────────────────────────────
setInterval(async () => {
    try {
        const params = new URLSearchParams(window.location.search);
        const r = await fetch(BASE + '/pqrs/json?' + params.toString());
        const d = await r.json();
        if (!d.success) return;

        document.querySelector('#statTotal .pq-stat-num').textContent = d.stats.total;
        document.querySelector('#statPend  .pq-stat-num').textContent = d.stats.pendientes;
        document.querySelector('#statRes   .pq-stat-num').textContent = d.stats.resueltos;
        document.querySelector('#statProm  .pq-stat-num').textContent = d.stats.promedio;

        if (d.data.length !== lastCount) {
            lastCount = d.data.length;
            location.reload();
        }
    } catch {}
}, 30000);

// ── Filtro Enter ──────────────────────────────────────────────────────────────
document.getElementById('pqBuscar').addEventListener('keydown', e => {
    if (e.key === 'Enter') e.target.form.submit();
});
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
