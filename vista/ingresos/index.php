<?php
// vista/ingresos/index.php
$titulo       = 'Ingresos — CHEFCONTROL';
$paginaActual = 'ingresos';
$basePath     = Config::getBasePath();
$baseUrl      = Config::getBaseUrl();

$jsExtra = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$badgeClass = [
    'Aceptado'  => 'badge-aceptado',
    'Pendiente' => 'badge-pendiente',
    'Anulado'   => 'badge-anulado',
];
?>

<div class="ing-wrap">

    <!-- Header -->
    <div class="ing-header">
        <div class="ing-header-left">
            <h1><i class="fas fa-money-bill-trend-up"></i> Ingresos</h1>
            <p>Registro y control de ingresos económicos</p>
        </div>
        <div class="ing-header-right">
            <form method="get" action="<?php echo $basePath; ?>/ingresos" class="ing-filter-form" id="filterForm">
                <div class="ing-filter-group">
                    <label>Desde</label>
                    <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>" class="ing-date-input">
                </div>
                <div class="ing-filter-group">
                    <label>Hasta</label>
                    <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>" class="ing-date-input">
                </div>
                <select name="estado" class="ing-select-filter">
                    <option value="">Todos los estados</option>
                    <option value="Aceptado"  <?php echo ($estado ?? '') === 'Aceptado'  ? 'selected' : ''; ?>>Aceptado</option>
                    <option value="Pendiente" <?php echo ($estado ?? '') === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Anulado"   <?php echo ($estado ?? '') === 'Anulado'   ? 'selected' : ''; ?>>Anulado</option>
                </select>
                <button type="submit" class="ing-btn-filtrar"><i class="fas fa-search"></i> Filtrar</button>
            </form>
            <button class="ing-btn-agregar" onclick="abrirModalNuevo()">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="ing-stats">
        <div class="ing-stat">
            <div class="ing-stat-icon" style="background:#eafaf1;color:#27ae60"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <div class="ing-stat-num"><?php echo $estadisticas['total']; ?></div>
                <div class="ing-stat-lbl">Ingresos del período</div>
            </div>
        </div>
        <div class="ing-stat">
            <div class="ing-stat-icon" style="background:#eaf4fb;color:#2980b9"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="ing-stat-num">$<?php echo number_format($estadisticas['suma'], 2); ?></div>
                <div class="ing-stat-lbl">Total ingresado</div>
            </div>
        </div>
        <div class="ing-stat">
            <div class="ing-stat-icon" style="background:#fdf2f2;color:#e74c3c"><i class="fas fa-ban"></i></div>
            <div>
                <div class="ing-stat-num"><?php echo $estadisticas['anulados']; ?></div>
                <div class="ing-stat-lbl">Anulados</div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="ing-panel">
        <div class="ing-panel-header">
            <h2><i class="fas fa-list"></i> Listado de ingresos</h2>
            <input type="text" id="searchInput" class="ing-search" placeholder="Buscar...">
        </div>

        <?php if (empty($ingresos)): ?>
        <div class="ing-empty">
            <i class="fas fa-inbox"></i>
            <p>No hay ingresos registrados en este período</p>
            <button class="ing-btn-agregar" onclick="abrirModalNuevo()" style="margin-top:14px">
                <i class="fas fa-plus"></i> Registrar primer ingreso
            </button>
        </div>
        <?php else: ?>
        <div class="ing-table-wrap">
            <table class="ing-table" id="ingresosTable">
                <thead>
                    <tr>
                        <th>Opciones</th>
                        <th>Fecha</th>
                        <th>Documento</th>
                        <th>Número</th>
                        <th>Concepto</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ingresos as $ing): ?>
                <tr data-search="<?php echo strtolower(htmlspecialchars($ing['numero'].' '.$ing['concepto'].' '.$ing['usuario_nombre'])); ?>">
                    <td>
                        <div class="ing-acciones">
                            <button class="ing-btn-accion ing-btn-ver"   title="Ver detalle"  onclick="verIngreso(<?php echo $ing['id']; ?>)"><i class="fas fa-eye"></i></button>
                            <button class="ing-btn-accion ing-btn-edit"  title="Editar"       onclick="editarIngreso(<?php echo $ing['id']; ?>)"><i class="fas fa-pencil"></i></button>
                            <button class="ing-btn-accion ing-btn-del"   title="Eliminar"     onclick="eliminarIngreso(<?php echo $ing['id']; ?>, '<?php echo htmlspecialchars(addslashes($ing['numero'] ?: 'este ingreso')); ?>')"><i class="fas fa-xmark"></i></button>
                        </div>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($ing['fecha'])); ?></td>
                    <td><?php echo htmlspecialchars($ing['tipo_documento']); ?></td>
                    <td>
                        <?php if ($ing['serie']): ?>
                            <span style="color:#95a5a6;font-size:12px;"><?php echo htmlspecialchars($ing['serie']); ?>-</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($ing['numero'] ?: '—'); ?>
                    </td>
                    <td class="ing-concepto"><?php echo htmlspecialchars(mb_strimwidth($ing['concepto'] ?? '', 0, 45, '...')); ?></td>
                    <td class="ing-total">$<?php echo number_format((float)$ing['total'], 2); ?></td>
                    <td><span class="ing-badge <?php echo $badgeClass[$ing['estado']] ?? ''; ?>"><?php echo $ing['estado']; ?></span></td>
                    <td class="ing-usuario"><?php echo htmlspecialchars($ing['usuario_nombre']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="ing-panel-footer">
            <span><i class="fas fa-info-circle"></i> Mostrando <strong id="countVisible"><?php echo count($ingresos); ?></strong> ingreso(s)</span>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- =================== MODAL FORMULARIO =================== -->
<div class="ing-modal-overlay" id="modalForm">
    <div class="ing-modal ing-modal-lg">
        <div class="ing-modal-header">
            <h2 id="modalFormTitle"><i class="fas fa-file-invoice-dollar"></i> Nuevo Ingreso</h2>
            <button class="ing-modal-close" onclick="cerrarModalForm()">&times;</button>
        </div>
        <div class="ing-modal-body">
            <input type="hidden" id="formId" value="0">

            <!-- Radicado + Proveedor -->
            <div class="ing-form-grid" style="grid-template-columns:1fr 1fr;">
                <div class="ing-form-group">
                    <label>Radicado</label>
                    <input type="text" id="fRadicado" class="ing-input" readonly
                           value="<?php echo htmlspecialchars($radicado); ?>"
                           style="background:#f8f9fa;color:#636e72;cursor:default;">
                </div>
                <div class="ing-form-group">
                    <label>Proveedor</label>
                    <div class="ing-ac-wrap">
                        <input type="text" id="acProvInput" class="ing-input" placeholder="Buscar proveedor..." autocomplete="off">
                        <input type="hidden" id="fProveedor" value="">
                        <div class="ing-ac-list" id="acProvList"></div>
                    </div>
                </div>
                <div class="ing-form-group ing-col-2">
                    <label>Concepto / Descripción</label>
                    <input type="text" id="fConcepto" class="ing-input" placeholder="Ej: Venta especial, Ingreso extra...">
                </div>
                <div class="ing-form-group">
                    <label>Aplicar Impuesto (%)</label>
                    <input type="number" id="fImpPct" class="ing-input" value="0" min="0" max="100" step="0.01">
                </div>
            </div>

            <!-- Artículos -->
            <div class="ing-items-section">
                <div class="ing-items-header">
                    <h3>Artículos</h3>
                    <button type="button" class="ing-btn-add-item" onclick="agregarFila()">
                        <i class="fas fa-plus"></i> Agregar Artículos
                    </button>
                </div>
                <div class="ing-items-table-wrap">
                    <table class="ing-items-table">
                        <thead>
                            <tr>
                                <th>Opciones</th>
                                <th>Artículo</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>

                <div class="ing-totales">
                    <div class="ing-total-row">
                        <span>SubTotal</span>
                        <span id="tSubtotal">$0.00</span>
                    </div>
                    <div class="ing-total-row">
                        <span>Impuesto (<span id="tPctLabel">0</span>%)</span>
                        <span id="tImpuesto">$0.00</span>
                    </div>
                    <div class="ing-total-row ing-total-final">
                        <span>TOTAL</span>
                        <span id="tTotal">$0.00</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="ing-modal-footer">
            <button class="ing-btn-cancel" onclick="cerrarModalForm()"><i class="fas fa-times"></i> Cancelar</button>
            <button class="ing-btn-guardar" id="btnGuardar" onclick="guardarIngreso()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- =================== MODAL DETALLE =================== -->
<div class="ing-modal-overlay" id="modalDetalle">
    <div class="ing-modal ing-modal-lg">
        <div class="ing-modal-header" style="background:linear-gradient(135deg,#1a6b38,#27ae60)">
            <h2><i class="fas fa-eye"></i> Detalle del Ingreso</h2>
            <button class="ing-modal-close" onclick="cerrarModalDetalle()">&times;</button>
        </div>
        <div class="ing-modal-body" id="detalleContent">
            <div style="text-align:center;padding:40px;color:#bdc3c7;">
                <i class="fas fa-spinner fa-spin" style="font-size:32px;"></i>
            </div>
        </div>
        <div class="ing-modal-footer">
            <button class="ing-btn-cancel" onclick="cerrarModalDetalle()">Cerrar</button>
        </div>
    </div>
</div>

<style>
/* ── Layout ─────────────────────────────────────────────────────────── */
.ing-wrap { display:flex; flex-direction:column; background:#f0f2f5; min-height:calc(100vh - 70px); }

/* Header */
.ing-header { background:linear-gradient(135deg,#1a3c2e,#1e6b46,#27ae60); color:#fff; padding:20px 28px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.ing-header h1 { margin:0; font-size:22px; display:flex; align-items:center; gap:10px; }
.ing-header p  { margin:4px 0 0; font-size:13px; opacity:.65; }
.ing-header-right { display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
.ing-filter-form { display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; }
.ing-filter-group { display:flex; flex-direction:column; gap:4px; }
.ing-filter-group label { font-size:11px; font-weight:700; opacity:.75; }
.ing-date-input,.ing-select-filter { border:none; border-radius:8px; padding:8px 12px; font-size:13px; color:#2c3e50; background:rgba(255,255,255,.92); outline:none; height:36px; }
.ing-btn-filtrar { background:rgba(255,255,255,.2); border:none; color:#fff; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; height:36px; display:flex; align-items:center; gap:6px; transition:.15s; }
.ing-btn-filtrar:hover { background:rgba(255,255,255,.35); }
.ing-btn-agregar { background:#fff; border:none; color:#27ae60; padding:8px 20px; border-radius:8px; font-size:13px; font-weight:800; cursor:pointer; height:36px; display:flex; align-items:center; gap:7px; transition:.15s; box-shadow:0 2px 8px rgba(0,0,0,.15); }
.ing-btn-agregar:hover { background:#eafaf1; }

/* Stats */
.ing-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; padding:20px 28px; }
.ing-stat { background:#fff; border-radius:14px; padding:18px 20px; box-shadow:0 2px 8px rgba(0,0,0,.06); display:flex; align-items:center; gap:14px; }
.ing-stat-icon { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.ing-stat-num { font-size:22px; font-weight:800; color:#2c3e50; line-height:1; }
.ing-stat-lbl { font-size:12px; color:#95a5a6; margin-top:3px; }

/* Panel tabla */
.ing-panel { margin:0 28px 28px; background:#fff; border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden; }
.ing-panel-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1.5px solid #f0f2f5; }
.ing-panel-header h2 { margin:0; font-size:16px; color:#2c3e50; display:flex; align-items:center; gap:8px; }
.ing-search { border:1.5px solid #e0e0e0; border-radius:8px; padding:8px 14px; font-size:13px; outline:none; width:220px; }
.ing-search:focus { border-color:#27ae60; }
.ing-panel-footer { padding:12px 20px; color:#7f8c8d; font-size:13px; border-top:1.5px solid #f0f2f5; }

.ing-empty { text-align:center; padding:60px 20px; color:#b2bec3; }
.ing-empty i { font-size:44px; display:block; margin-bottom:14px; color:#27ae60; }

/* Tabla */
.ing-table-wrap { overflow-x:auto; }
.ing-table { width:100%; border-collapse:collapse; font-size:13px; }
.ing-table thead th { background:#f8f9fa; padding:11px 14px; text-align:left; font-size:11px; font-weight:700; color:#636e72; text-transform:uppercase; letter-spacing:.4px; border-bottom:1.5px solid #e8ecf0; white-space:nowrap; }
.ing-table tbody td { padding:11px 14px; border-bottom:1px solid #f0f2f5; color:#2c3e50; vertical-align:middle; }
.ing-table tbody tr:last-child td { border-bottom:none; }
.ing-table tbody tr:hover td { background:#f9fffe; }
.ing-concepto { max-width:200px; color:#636e72; font-size:12px; }
.ing-total { font-weight:800; color:#27ae60; }
.ing-usuario { color:#95a5a6; font-size:12px; }

/* Badges */
.ing-badge { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.badge-aceptado  { background:#eafaf1; color:#1e8449; }
.badge-pendiente { background:#fef9e7; color:#d4ac0d; }
.badge-anulado   { background:#fdf2f2; color:#c0392b; }

/* Botones acción */
.ing-acciones { display:flex; gap:5px; }
.ing-btn-accion { width:30px; height:30px; border:none; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px; transition:.12s; }
.ing-btn-ver  { background:#eaf4fb; color:#2980b9; }
.ing-btn-edit { background:#fef5e7; color:#e67e22; }
.ing-btn-del  { background:#fdf2f2; color:#e74c3c; }
.ing-btn-ver:hover  { background:#2980b9; color:#fff; }
.ing-btn-edit:hover { background:#e67e22; color:#fff; }
.ing-btn-del:hover  { background:#e74c3c; color:#fff; }

/* Modal */
.ing-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; overflow-y:auto; }
.ing-modal-overlay.show { display:flex; }
.ing-modal { background:#fff; border-radius:16px; width:100%; max-width:540px; box-shadow:0 20px 60px rgba(0,0,0,.3); display:flex; flex-direction:column; max-height:90vh; }
.ing-modal-lg { max-width:820px; }
.ing-modal-header { display:flex; justify-content:space-between; align-items:center; padding:18px 24px; background:linear-gradient(135deg,#1a3c2e,#27ae60); color:#fff; border-radius:16px 16px 0 0; flex-shrink:0; }
.ing-modal-header h2 { margin:0; font-size:18px; display:flex; align-items:center; gap:10px; }
.ing-modal-close { background:rgba(255,255,255,.2); border:none; color:#fff; width:32px; height:32px; border-radius:50%; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.12s; }
.ing-modal-close:hover { background:rgba(255,255,255,.4); }
.ing-modal-body { padding:24px; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:20px; }
.ing-modal-footer { padding:16px 24px; display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #f0f2f5; flex-shrink:0; }

/* Form grid */
.ing-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.ing-form-group { display:flex; flex-direction:column; gap:5px; }
.ing-form-group label { font-size:12px; font-weight:700; color:#2c3e50; }
.ing-input { border:1.5px solid #e0e0e0; border-radius:9px; padding:9px 12px; font-size:13px; color:#2c3e50; outline:none; width:100%; box-sizing:border-box; }
.ing-input:focus { border-color:#27ae60; }
.ing-col-2 { grid-column:span 2; }

/* Items section */
.ing-items-section { display:flex; flex-direction:column; gap:12px; }
.ing-items-header { display:flex; justify-content:space-between; align-items:center; }
.ing-items-header h3 { margin:0; font-size:14px; color:#2c3e50; }
.ing-btn-add-item { background:#27ae60; border:none; color:#fff; padding:7px 16px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; transition:.12s; }
.ing-btn-add-item:hover { background:#1e8449; }
.ing-items-table-wrap { border:1.5px solid #e0e0e0; border-radius:10px; overflow:visible; }
.ing-items-table { width:100%; border-collapse:collapse; font-size:13px; }
.ing-items-table thead th { background:#f8f9fa; padding:9px 12px; text-align:left; font-size:11px; font-weight:700; color:#636e72; text-transform:uppercase; border-bottom:1.5px solid #e0e0e0; }
.ing-items-table tbody td { padding:8px 10px; border-bottom:1px solid #f0f2f5; vertical-align:middle; }
.ing-items-table tbody tr:last-child td { border-bottom:none; }
.ing-item-input { border:1.5px solid #e0e0e0; border-radius:6px; padding:6px 9px; font-size:12px; width:100%; box-sizing:border-box; outline:none; }
.ing-item-input:focus { border-color:#27ae60; }
.ing-btn-del-row { background:#fdf2f2; border:none; color:#e74c3c; width:26px; height:26px; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px; transition:.12s; }
.ing-btn-del-row:hover { background:#e74c3c; color:#fff; }
.ing-empty-items { text-align:center; padding:20px; color:#bdc3c7; font-size:13px; }

/* Totales */
.ing-totales { border:1.5px solid #e0e0e0; border-radius:10px; padding:14px 18px; display:flex; flex-direction:column; gap:8px; background:#fafafa; align-self:flex-end; min-width:260px; }
.ing-total-row { display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#636e72; }
.ing-total-final { font-size:16px; font-weight:800; color:#2c3e50; padding-top:8px; border-top:1.5px solid #e0e0e0; margin-top:4px; }

/* Botones footer */
.ing-btn-cancel  { background:#f0f2f5; border:none; padding:10px 20px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; color:#636e72; display:flex; align-items:center; gap:7px; }
.ing-btn-guardar { background:#27ae60; border:none; padding:10px 22px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; color:#fff; display:flex; align-items:center; gap:7px; transition:.15s; }
.ing-btn-guardar:hover:not(:disabled) { background:#1e8449; }
.ing-btn-guardar:disabled { opacity:.6; cursor:default; }

/* Detalle */
.det-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; }
.det-field { display:flex; flex-direction:column; gap:3px; }
.det-field label { font-size:11px; font-weight:700; color:#95a5a6; text-transform:uppercase; }
.det-field span  { font-size:14px; color:#2c3e50; }

/* Autocomplete proveedor y dropdown de insumos por fila */
.ing-ac-wrap { position:relative; }
.ing-ac-list { display:none; position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:2px solid #27ae60; border-radius:10px; max-height:200px; overflow-y:auto; z-index:999; box-shadow:0 6px 20px rgba(0,0,0,.12); }
.ing-ac-list.open { display:block; }
.ing-ac-item { padding:10px 14px; cursor:pointer; border-bottom:1px solid #f0f2f5; transition:background .1s; }
.ing-ac-item:last-child { border-bottom:none; }
.ing-ac-item:hover,.ing-ac-item.ac-active { background:#eafaf1; }
.ing-ac-main { font-weight:600; color:#2c3e50; font-size:13px; }
.ing-ac-sub  { font-size:11px; color:#95a5a6; margin-top:2px; }
.ing-ac-empty { padding:12px 14px; color:#bdc3c7; font-size:13px; text-align:center; }

/* Dropdown por fila de artículo */
.ing-row-drop { display:none; position:absolute; top:calc(100% + 2px); left:0; background:#fff; border:2px solid #27ae60; border-radius:10px; max-height:200px; overflow-y:auto; z-index:9999; box-shadow:0 8px 24px rgba(0,0,0,.15); min-width:280px; }
.ing-row-drop.open { display:block; }
.ing-precio-wrap { display:flex; align-items:center; gap:6px; }
.ing-unidad-tag { font-size:11px; color:#95a5a6; white-space:nowrap; background:#f0f2f5; border-radius:5px; padding:2px 7px; }

@media(max-width:768px) {
    .ing-header { flex-direction:column; align-items:flex-start; }
    .ing-panel { margin:0 14px 14px; }
    .ing-stats { padding:14px; }
    .ing-form-grid { grid-template-columns:1fr; }
    .ing-col-2 { grid-column:span 1; }
    .ing-modal-lg { max-width:100%; }
}
</style>

<script>
const BASE = '<?php echo $basePath; ?>';
let rowCount = 0;

/* ── Datos insumos ───────────────────────────────────────────────── */
const __insumos = <?php echo json_encode(array_values(array_map(function($i){
    return ['id'=>(int)$i['id'],'nombre'=>$i['nombre'],'unidad'=>$i['unidad_medida'],'precio'=>(float)$i['precio_unitario']];
}, $insumosLista ?? []))); ?>;

/* ── Datos proveedores ───────────────────────────────────────────── */
const __proveedores = <?php echo json_encode(array_values(array_map(function($p){
    return ['id'=>(int)$p['id'],'nombre'=>$p['nombre'],'empresa'=>$p['empresa']??''];
}, $proveedores ?? []))); ?>;

/* ── Autocomplete genérico ───────────────────────────────────────── */
function initAC({ inputId, listId, hiddenId, data, labelFn, subFn, onSelect }) {
    const input  = document.getElementById(inputId);
    const list   = document.getElementById(listId);
    const hidden = document.getElementById(hiddenId);
    if (!input || !list) return;
    let activeIdx = -1;

    function hl(text, q) {
        if (!q) return text;
        return text.replace(new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + ')','gi'),
            '<strong style="color:#27ae60">$1</strong>');
    }
    function matches(q) {
        const ql = q.toLowerCase();
        return q ? data.filter(d => labelFn(d).toLowerCase().includes(ql) || (subFn && subFn(d).toLowerCase().includes(ql)))
                 : data.slice(0, 10);
    }
    function render(q) {
        activeIdx = -1; list.innerHTML = '';
        const m = matches(q);
        if (!m.length) {
            list.innerHTML = '<div class="ing-ac-empty">Sin resultados</div>';
        } else {
            m.forEach(d => {
                const el = document.createElement('div');
                el.className = 'ing-ac-item';
                el.innerHTML = `<div class="ing-ac-main">${hl(labelFn(d), q)}</div>`
                    + (subFn && subFn(d) ? `<div class="ing-ac-sub">${hl(subFn(d), q)}</div>` : '');
                el.addEventListener('mousedown', e => { e.preventDefault(); pick(d); });
                list.appendChild(el);
            });
        }
        list.classList.add('open');
    }
    function pick(d) {
        hidden.value = d.id; input.value = labelFn(d);
        list.classList.remove('open'); activeIdx = -1;
        if (onSelect) onSelect(d);
    }
    function clear() { hidden.value = ''; if (onSelect) onSelect(null); }

    input.addEventListener('focus', () => render(input.value));
    input.addEventListener('input', () => { clear(); render(input.value); });
    input.addEventListener('blur',  () => setTimeout(() => list.classList.remove('open'), 150));
    input.addEventListener('keydown', e => {
        const items = list.querySelectorAll('.ing-ac-item');
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx+1, items.length-1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx-1, -1); }
        else if (e.key === 'Enter' && activeIdx >= 0) { e.preventDefault(); items[activeIdx]?.dispatchEvent(new MouseEvent('mousedown')); return; }
        else if (e.key === 'Escape') { list.classList.remove('open'); return; }
        items.forEach((el, i) => el.classList.toggle('ac-active', i === activeIdx));
        if (activeIdx >= 0) items[activeIdx].scrollIntoView({ block:'nearest' });
    });
}

initAC({
    inputId:  'acProvInput',
    listId:   'acProvList',
    hiddenId: 'fProveedor',
    data:     __proveedores,
    labelFn:  d => d.nombre,
    subFn:    d => d.empresa,
    onSelect: null,
});

/* ── Modal formulario ─────────────────────────────────────────────── */
function abrirModalNuevo() {
    document.getElementById('formId').value = '0';
    document.getElementById('modalFormTitle').innerHTML = '<i class="fas fa-file-invoice-dollar"></i> Nuevo Ingreso';
    document.getElementById('fConcepto').value  = '';
    document.getElementById('fImpPct').value    = '0';
    document.getElementById('acProvInput').value = '';
    document.getElementById('fProveedor').value  = '';
    // Generar radicado fresco desde el servidor no es posible en JS puro,
    // el radicado ya viene pre-llenado del PHP y se envía como hidden.
    document.getElementById('itemsBody').innerHTML = '';
    rowCount = 0;
    recalcular();
    document.getElementById('modalForm').classList.add('show');
}
function cerrarModalForm() {
    document.getElementById('modalForm').classList.remove('show');
}

/* ── Filas de artículos ───────────────────────────────────────────── */
function buildOpts(selectedId) {
    let o = '<option value="">— Seleccionar insumo —</option>';
    __insumos.forEach(ins => {
        o += `<option value="${ins.id}" data-precio="${ins.precio}" data-unidad="${escHtml(ins.unidad)}"${ins.id == selectedId ? ' selected' : ''}>${escHtml(ins.nombre)}</option>`;
    });
    return o;
}

function agregarFila(idInsumo='', cant=1, precio=0) {
    const tbody = document.getElementById('itemsBody');
    const idx   = rowCount++;
    // Si viene idInsumo, usar su precio; si no, usar el precio pasado
    const ins   = __insumos.find(i => i.id == idInsumo);
    const prec  = ins ? ins.precio : precio;
    const sub   = (cant * prec).toFixed(2);

    const tr = document.createElement('tr');
    tr.id = 'row_' + idx;
    tr.innerHTML = `
        <td><button type="button" class="ing-btn-del-row" onclick="eliminarFila(${idx})"><i class="fas fa-xmark"></i></button></td>
        <td>
            <div class="ing-ac-wrap">
                <input type="text" class="ing-item-input ing-sel-input" placeholder="Buscar insumo..."
                       value="${ins ? escHtml(ins.nombre) : ''}" autocomplete="off"
                       oninput="filtrarInsumo(this, ${idx})" onfocus="filtrarInsumo(this, ${idx})"
                       onblur="setTimeout(()=>cerrarDropdown(${idx}),160)">
                <input type="hidden" class="ing-hidden-id" name="articulo_id[]" value="${idInsumo}">
                <input type="hidden" class="ing-hidden-nombre" name="articulo[]" value="${ins ? escHtml(ins.nombre) : ''}">
                <div class="ing-row-drop" id="drop_${idx}"></div>
            </div>
        </td>
        <td>
            <input type="number" class="ing-item-input" name="cantidad[]" value="${cant}"
                   min="0.01" step="0.01" oninput="calcFilaSub(${idx})">
        </td>
        <td>
            <div class="ing-precio-wrap">
                <input type="number" class="ing-item-input ing-precio" name="precio_unitario[]"
                       value="${prec.toFixed(2)}" readonly
                       style="background:#f8f9fa;color:#636e72;cursor:default;flex:1;">
                <span class="ing-unidad-tag" id="unidad_${idx}">${ins ? escHtml(ins.unidad) : ''}</span>
            </div>
        </td>
        <td>
            <input type="number" class="ing-item-input ing-subtotal" name="item_subtotal[]"
                   value="${sub}" readonly
                   style="background:#f8f9fa;color:#636e72;cursor:default;">
        </td>
    `;
    tbody.appendChild(tr);
    recalcular();
}

function filtrarInsumo(input, idx) {
    const drop = document.getElementById('drop_' + idx);
    const q    = input.value.toLowerCase();
    const matches = q ? __insumos.filter(i => i.nombre.toLowerCase().includes(q)) : __insumos;

    drop.innerHTML = '';
    if (!matches.length) {
        drop.innerHTML = '<div class="ing-ac-empty">Sin resultados</div>';
    } else {
        matches.slice(0, 12).forEach(ins => {
            const el = document.createElement('div');
            el.className = 'ing-ac-item';
            el.innerHTML = `<div class="ing-ac-main">${escHtml(ins.nombre)}</div>
                            <div class="ing-ac-sub">$${ins.precio.toFixed(2)} / ${escHtml(ins.unidad)}</div>`;
            el.addEventListener('mousedown', e => {
                e.preventDefault();
                seleccionarInsumo(idx, ins);
            });
            drop.appendChild(el);
        });
    }
    drop.classList.add('open');
}

function seleccionarInsumo(idx, ins) {
    const row = document.getElementById('row_' + idx);
    if (!row) return;
    row.querySelector('.ing-sel-input').value   = ins.nombre;
    row.querySelector('.ing-hidden-id').value    = ins.id;
    row.querySelector('.ing-hidden-nombre').value= ins.nombre;
    row.querySelector('.ing-precio').value = ins.precio.toFixed(2);
    const utag = row.querySelector('.ing-unidad-tag');
    if (utag) utag.textContent = ins.unidad;
    cerrarDropdown(idx);
    calcFilaSub(idx);
}

function cerrarDropdown(idx) {
    const drop = document.getElementById('drop_' + idx);
    if (drop) drop.classList.remove('open');
}

function eliminarFila(idx) {
    const row = document.getElementById('row_' + idx);
    if (row) { row.remove(); recalcular(); }
}

function calcFilaSub(idx) {
    const row  = document.getElementById('row_' + idx);
    if (!row) return;
    const cant = parseFloat(row.querySelector('[name="cantidad[]"]').value)        || 0;
    const prec = parseFloat(row.querySelector('[name="precio_unitario[]"]').value) || 0;
    row.querySelector('.ing-subtotal').value = (cant * prec).toFixed(2);
    recalcular();
}
function recalcular() {
    let sub = 0;
    document.querySelectorAll('[name="item_subtotal[]"]').forEach(el => {
        sub += parseFloat(el.value) || 0;
    });
    const pct = parseFloat(document.getElementById('fImpPct').value) || 0;
    const imp = sub * pct / 100;
    document.getElementById('tSubtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('tImpuesto').textContent = '$' + imp.toFixed(2);
    document.getElementById('tTotal').textContent    = '$' + (sub + imp).toFixed(2);
    document.getElementById('tPctLabel').textContent = pct;

    const body = document.getElementById('itemsBody');
    if (!body.querySelector('tr')) {
        body.innerHTML = '<tr><td colspan="5" class="ing-empty-items"><i class="fas fa-box-open"></i> Sin artículos</td></tr>';
    } else {
        const empty = body.querySelector('.ing-empty-items');
        if (empty) empty.closest('tr').remove();
    }
}
document.getElementById('fImpPct').addEventListener('input', recalcular);

/* ── Guardar ──────────────────────────────────────────────────────── */
function guardarIngreso() {
    const id  = parseInt(document.getElementById('formId').value);
    const url = id > 0 ? BASE + '/ingresos/actualizar' : BASE + '/ingresos/crear';

    const fd = new FormData();
    if (id > 0) fd.append('id', id);
    fd.append('radicado',     document.getElementById('fRadicado').value);
    fd.append('concepto',     document.getElementById('fConcepto').value);
    fd.append('impuesto_pct', document.getElementById('fImpPct').value);
    fd.append('id_proveedor', document.getElementById('fProveedor').value);

    let sub = 0;
    document.querySelectorAll('#itemsBody tr[id]').forEach(tr => {
        const art  = tr.querySelector('.ing-hidden-nombre')?.value.trim();
        const artId= tr.querySelector('.ing-hidden-id')?.value || '';
        const cant = tr.querySelector('[name="cantidad[]"]')?.value;
        const prec = tr.querySelector('[name="precio_unitario[]"]')?.value;
        const stot = tr.querySelector('[name="item_subtotal[]"]')?.value;
        if (art) {
            fd.append('articulo[]',        art);
            fd.append('articulo_id[]',     artId);
            fd.append('cantidad[]',        cant);
            fd.append('precio_unitario[]', prec);
            fd.append('item_subtotal[]',   stot);
            sub += parseFloat(stot) || 0;
        }
    });
    fd.append('subtotal', sub.toFixed(2));

    const btn  = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    fetch(url, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            btn.disabled = false; btn.innerHTML = orig;
            if (d.success) {
                cerrarModalForm();
                Swal.fire({ icon:'success', title:'¡Guardado!', text:d.message, timer:1500, showConfirmButton:false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon:'error', title:'Error', text:d.message });
            }
        })
        .catch(() => {
            btn.disabled = false; btn.innerHTML = orig;
            Swal.fire({ icon:'error', title:'Error', text:'No se pudo conectar con el servidor.' });
        });
}

/* ── Editar ───────────────────────────────────────────────────────── */
function editarIngreso(id) {
    fetch(BASE + '/ingresos/get/' + id)
        .then(r => r.json())
        .then(d => {
            if (!d.success) { Swal.fire({ icon:'error', text:d.message }); return; }
            const ing = d.data;
            document.getElementById('formId').value = ing.id;
            document.getElementById('modalFormTitle').innerHTML = '<i class="fas fa-pencil"></i> Editar Ingreso';
            document.getElementById('fRadicado').value  = ing.radicado || '';
            document.getElementById('fConcepto').value  = ing.concepto || '';
            document.getElementById('fImpPct').value    = ing.subtotal > 0
                ? ((ing.impuesto / ing.subtotal) * 100).toFixed(2)
                : '0';
            // Proveedor
            document.getElementById('fProveedor').value  = ing.id_proveedor || '';
            document.getElementById('acProvInput').value = ing.proveedor_nombre && ing.id_proveedor ? ing.proveedor_nombre : '';
            document.getElementById('itemsBody').innerHTML = '';
            rowCount = 0;
            d.items.forEach(it => {
                const ins = it.id_insumo
                    ? __insumos.find(i => i.id == it.id_insumo)
                    : __insumos.find(i => i.nombre === it.articulo);
                agregarFila(ins ? ins.id : '', it.cantidad, it.precio_unitario);
                if (!ins) {
                    // insumo eliminado: mostrar nombre original en el campo texto
                    const lastRow = document.getElementById('row_' + (rowCount - 1));
                    if (lastRow) {
                        lastRow.querySelector('.ing-sel-input').value    = it.articulo;
                        lastRow.querySelector('.ing-hidden-nombre').value = it.articulo;
                    }
                }
            });
            document.getElementById('modalForm').classList.add('show');
        });
}

/* ── Ver detalle ──────────────────────────────────────────────────── */
function verIngreso(id) {
    document.getElementById('detalleContent').innerHTML =
        '<div style="text-align:center;padding:40px;color:#bdc3c7;"><i class="fas fa-spinner fa-spin" style="font-size:32px;"></i></div>';
    document.getElementById('modalDetalle').classList.add('show');

    fetch(BASE + '/ingresos/get/' + id)
        .then(r => r.json())
        .then(d => {
            if (!d.success) { document.getElementById('detalleContent').innerHTML = '<p style="color:red">Error al cargar</p>'; return; }
            const ing = d.data;
            const badgeMap = { Aceptado:'badge-aceptado', Pendiente:'badge-pendiente', Anulado:'badge-anulado' };
            let itemsHtml = d.items.length
                ? d.items.map(it => `<tr><td>${escHtml(it.articulo)}</td><td style="text-align:center">${parseFloat(it.cantidad).toFixed(2)}</td><td style="text-align:right">$${parseFloat(it.precio_unitario).toFixed(2)}</td><td style="text-align:right;font-weight:700">$${parseFloat(it.subtotal).toFixed(2)}</td></tr>`).join('')
                : '<tr><td colspan="4" style="text-align:center;color:#bdc3c7;padding:14px;">Sin artículos</td></tr>';
            document.getElementById('detalleContent').innerHTML = `
                <div class="det-grid">
                    <div class="det-field"><label>Tipo Documento</label><span>${escHtml(ing.tipo_documento)}</span></div>
                    <div class="det-field"><label>Fecha</label><span>${escHtml(ing.fecha)}</span></div>
                    <div class="det-field"><label>Serie</label><span>${escHtml(ing.serie || '—')}</span></div>
                    <div class="det-field"><label>Número</label><span>${escHtml(ing.numero || '—')}</span></div>
                    <div class="det-field" style="grid-column:span 2"><label>Concepto</label><span>${escHtml(ing.concepto || '—')}</span></div>
                    <div class="det-field"><label>Estado</label><span class="ing-badge ${badgeMap[ing.estado]}">${ing.estado}</span></div>
                </div>
                <table class="ing-items-table" style="margin-bottom:16px">
                    <thead><tr><th>Artículo</th><th style="text-align:center">Cant.</th><th style="text-align:right">P. Unit.</th><th style="text-align:right">Subtotal</th></tr></thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="ing-totales" style="align-self:flex-end">
                    <div class="ing-total-row"><span>SubTotal</span><span>$${parseFloat(ing.subtotal).toFixed(2)}</span></div>
                    <div class="ing-total-row"><span>Impuesto</span><span>$${parseFloat(ing.impuesto).toFixed(2)}</span></div>
                    <div class="ing-total-row ing-total-final"><span>TOTAL</span><span>$${parseFloat(ing.total).toFixed(2)}</span></div>
                </div>`;
        });
}
function cerrarModalDetalle() {
    document.getElementById('modalDetalle').classList.remove('show');
}

/* ── Eliminar ─────────────────────────────────────────────────────── */
function eliminarIngreso(id, label) {
    Swal.fire({
        title: '¿Eliminar ingreso?',
        html: `¿Seguro que deseas eliminar <strong>${label}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor:  '#95a5a6',
        confirmButtonText:  'Sí, eliminar',
        cancelButtonText:   'Cancelar'
    }).then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData(); fd.append('id', id);
        fetch(BASE + '/ingresos/eliminar', { method:'POST', body:fd })
            .then(res => res.json())
            .then(d => {
                if (d.success) {
                    Swal.fire({ icon:'success', title:'Eliminado', timer:1200, showConfirmButton:false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon:'error', title:'Error', text:d.message });
                }
            });
    });
}

/* ── Búsqueda ─────────────────────────────────────────────────────── */
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        let visible = 0;
        document.querySelectorAll('#ingresosTable tbody tr').forEach(row => {
            const match = (row.dataset.search || '').includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const cnt = document.getElementById('countVisible');
        if (cnt) cnt.textContent = visible;
    });
}

/* ── Utils ────────────────────────────────────────────────────────── */
function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

recalcular();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
