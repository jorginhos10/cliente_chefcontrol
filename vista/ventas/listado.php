<?php
// vista/ventas/listado.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Ventas - CHEFCONTROL';
$paginaActual = 'ventas';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

require_once __DIR__ . '/../../modelo/comercioModel.php';
$papel = ComercioModel::parametrosPapel($comercio['tamano_papel'] ?? '80mm');

$cssExtra = '
<link rel="stylesheet" href="' . $baseUrl . '/assets/css/dashboard.css?v=2">
<style>
/* ── Ventas Listado ── */
.vl-wrap { display:flex; flex-direction:column; gap:0; background:#f0f2f5; min-height:calc(100vh - 70px); }
.vl-header { background:linear-gradient(135deg,#1a252f,#2c3e50); color:#fff; padding:20px 28px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; }
.vl-header h1 { margin:0; font-size:22px; display:flex; align-items:center; gap:10px; }
.vl-header p  { margin:4px 0 0; font-size:13px; opacity:.65; }
.vl-header-stats { display:flex; gap:28px; }
.vl-stat { text-align:center; }
.vl-stat-num   { font-size:22px; font-weight:800; }
.vl-stat-label { font-size:11px; opacity:.65; }
.vl-filters { display:flex; align-items:center; gap:10px; background:#fff; padding:14px 20px; flex-wrap:wrap; border-bottom:1px solid #e8ecf0; }
.vl-filter-group { display:flex; align-items:center; gap:6px; }
.vl-filter-icon { color:#95a5a6; font-size:13px; }
.vl-filter-sep  { color:#b2bec3; font-size:18px; line-height:1; }
.vl-input { border:1.5px solid #dfe6e9; border-radius:8px; padding:7px 12px; font-size:13px; color:#2c3e50; background:#fff; outline:none; }
.vl-input:focus { border-color:#2c3e50; }
.vl-input[name="buscar"] { width:220px; }
.vl-input-date { width:135px; }
.vl-select { border:1.5px solid #dfe6e9; border-radius:8px; padding:7px 10px; font-size:13px; color:#2c3e50; background:#fff; outline:none; cursor:pointer; }
.vl-btn-filter { background:#2c3e50; color:#fff; border:none; border-radius:8px; padding:8px 16px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.vl-btn-filter:hover { background:#1a252f; }
.vl-btn-clear  { color:#e74c3c; font-size:13px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:5px; padding:4px 8px; border-radius:6px; }
.vl-btn-clear:hover { background:#fdedec; }
.vl-table-wrap { flex:1; overflow-x:auto; background:#fff; }
.vl-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; table-layout:fixed; }
.vl-table thead tr { position:sticky; top:0; z-index:2; }
.vl-table th { background:#f8f9fa; color:#7f8c8d; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:12px 14px; border-bottom:2px solid #e8ecf0; white-space:nowrap; text-align:left; overflow:hidden; }
.vl-table td { padding:11px 14px; border-bottom:1px solid #f0f2f5; color:#2c3e50; vertical-align:middle; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.vl-table tr:hover td { background:#fafbfc; }
.vl-table tr:last-child td { border-bottom:none; }
.vl-th-center,.vl-td-center { text-align:center; }
.vl-th-right,.vl-td-monto   { text-align:right; }
.vl-td-orden { font-family:monospace; font-weight:700; font-size:12px; }
.vl-td-hora  { color:#7f8c8d; font-size:12px; }
.vl-td-mesa  { color:#7f8c8d; }
.vl-td-monto { font-weight:800; color:#27ae60; }
.vl-col-orden    { width:10%; }
.vl-col-fecha    { width:9%;  }
.vl-col-hora     { width:6%;  }
.vl-col-mesero   { width:11%; }
.vl-col-mesa     { width:8%;  }
.vl-col-platos   { width:5%;  }
.vl-col-total    { width:9%;  }
.vl-col-metodo   { width:9%;  }
.vl-col-cliente  { width:13%; }
.vl-col-estado   { width:10%; }
.vl-col-acciones { width:10%; min-width:110px; }
.vl-badge  { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; white-space:nowrap; }
.vl-ok     { background:#eafaf1; color:#27ae60; }
.vl-cancel { background:#fdedec; color:#e74c3c; }
.vl-comp   { background:#eaf4fb; color:#2980b9; }
.vl-pend   { background:#fef9e7; color:#e67e22; }
.vl-prep   { background:#fef5e7; color:#d35400; }
.vl-list   { background:#d5f5e3; color:#1e8449; }
.vl-pago-efectivo      { background:#eafaf1; color:#27ae60; }
.vl-pago-tarjeta       { background:#eaf4fb; color:#2980b9; }
.vl-pago-transferencia { background:#f5eafd; color:#8e44ad; }
.vl-pago-mixto         { background:#fef9e7; color:#e67e22; }
.vl-pago-na            { color:#b2bec3; font-style:italic; font-size:12px; }
.vl-btn-ver { background:#eaf4fb; color:#2980b9; border:none; border-radius:7px; width:32px; height:32px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:14px; transition:all .15s; }
.vl-btn-ver:hover { background:#2980b9; color:#fff; }
.vl-btn-cliente { background:#eafaf1; color:#27ae60; border:none; border-radius:7px; width:32px; height:32px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:13px; transition:all .15s; margin-left:4px; }
.vl-btn-cliente:hover { background:#27ae60; color:#fff; }
.vl-btn-cliente.asignado { background:#d5f5e3; color:#1e8449; cursor:default; pointer-events:none; opacity:.75; }
.vl-btn-cancelar-venta { background:#fdedec; color:#e74c3c; border:none; border-radius:7px; width:32px; height:32px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; font-size:13px; transition:all .15s; margin-left:4px; }
.vl-btn-cancelar-venta:hover { background:#e74c3c; color:#fff; }
.vl-cli-nombre  { font-size:13px; color:#2c3e50; font-weight:600; }
.vl-cli-ninguno { font-size:12px; color:#b2bec3; font-style:italic; }
.vl-modal-sm { max-width:420px; }
.cli-result-item { padding:10px 14px; border-bottom:1px solid #f0f2f5; cursor:pointer; display:flex; flex-direction:column; gap:2px; }
.cli-result-item:last-child { border-bottom:none; }
.cli-result-item:hover { background:#f0f9f5; }
.cli-result-nom { font-weight:600; color:#2c3e50; font-size:13px; }
.cli-result-tel { font-size:11px; color:#95a5a6; }
.vl-empty { text-align:center; padding:60px 20px; color:#b2bec3; background:#fff; }
.vl-empty i { font-size:40px; display:block; margin-bottom:12px; }
.vl-pagination { background:#fff; border-top:1px solid #e8ecf0; padding:14px 20px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.vl-pag-info { font-size:13px; color:#7f8c8d; }
.vl-pag-controls { display:flex; align-items:center; gap:4px; }
.vl-pag-btn { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:13px; font-weight:600; color:#2c3e50; text-decoration:none; border:1.5px solid #e8ecf0; background:#fff; transition:all .15s; }
.vl-pag-btn:hover:not(.disabled):not(.active) { background:#f0f2f5; border-color:#ccc; }
.vl-pag-btn.active { background:#2c3e50; color:#fff; border-color:#2c3e50; }
.vl-pag-btn.disabled { opacity:.35; cursor:default; pointer-events:none; }
.vl-pag-dots { font-size:13px; color:#b2bec3; padding:0 4px; }
.vl-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:9000; display:none; align-items:center; justify-content:center; padding:20px; }
.vl-modal-overlay.open { display:flex; }
.vl-modal { background:#fff; border-radius:16px; width:100%; max-width:580px; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; }
.vl-modal-header { background:linear-gradient(135deg,#1a252f,#2c3e50); color:#fff; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; }
.vl-modal-title { display:flex; align-items:center; gap:10px; }
.vl-modal-orden { font-family:monospace; font-size:16px; font-weight:800; }
.vl-modal-close { background:rgba(255,255,255,.15); border:none; color:#fff; width:32px; height:32px; border-radius:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:16px; }
.vl-modal-close:hover { background:rgba(255,255,255,.3); }
.vl-modal-meta { display:flex; gap:18px; padding:12px 22px; background:#f8f9fa; border-bottom:1px solid #e8ecf0; flex-wrap:wrap; font-size:13px; color:#636e72; }
.vl-modal-meta span { display:flex; align-items:center; gap:5px; }
.vl-modal-body { flex:1; overflow-y:auto; padding:20px 22px; }
.vl-modal-loading { text-align:center; padding:30px; color:#95a5a6; font-size:15px; }
.vl-modal-footer { padding:14px 22px; border-top:1px solid #e8ecf0; display:flex; justify-content:flex-end; gap:10px; background:#f8f9fa; }
.vl-items-table { width:100%; border-collapse:collapse; font-size:13px; }
.vl-items-table th { text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; color:#95a5a6; padding:8px 10px; border-bottom:2px solid #f0f2f5; }
.vl-items-table td { padding:10px 10px; border-bottom:1px solid #f8f8f8; }
.vl-items-table tr:last-child td { border-bottom:none; }
.vl-items-table .it-nombre { font-weight:600; color:#2c3e50; }
.vl-items-table .it-cat    { font-size:11px; color:#95a5a6; }
.vl-items-table .it-cant   { text-align:center; font-weight:700; }
.vl-items-table .it-precio { text-align:right; color:#636e72; }
.vl-items-table .it-sub    { text-align:right; font-weight:700; color:#2c3e50; }
.vl-total-box { margin-top:16px; background:#f0f2f5; border-radius:10px; padding:14px 18px; display:flex; justify-content:space-between; align-items:center; }
.vl-total-label { font-size:13px; color:#7f8c8d; font-weight:600; }
.vl-total-num   { font-size:22px; font-weight:800; color:#27ae60; }
.vl-btn-cerrar   { background:#f0f2f5; color:#636e72; border:none; border-radius:9px; padding:9px 20px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.vl-btn-cerrar:hover { background:#e0e4e8; }
.vl-btn-imprimir { background:#27ae60; color:#fff; border:none; border-radius:9px; padding:9px 20px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.vl-btn-imprimir:hover { background:#219a52; }
#vlTicket { display:none; }
@media print {
    /* display:none (no visibility:hidden) para que lo oculto NO ocupe espacio —
       así el ticket no necesita position:fixed para "escapar" del resto de la
       página. position:fixed se repetía en cada hoja cuando el recibo era más
       largo que una página, duplicando el contenido. Con flujo normal, si el
       recibo no cabe en una hoja simplemente continúa en la siguiente, sin
       repetirse.
       width:100% (no un mm fijo) para que ocupe todo el ancho de la página que
       use el driver de impresión real, ya sea que respete @page o no.
       pre-wrap en vez de pre: si una línea no cabe, se ajusta en vez de cortarse. */
    body * { display: none !important; }
    /* Los ancestros de #vlTicket (layout de header.php) también hay que
       revelarlos explícitamente — display:none en un padre oculta a sus hijos
       aunque el hijo tenga display:block, así que no basta con des-ocultar
       #vlTicket solo. Los DEMÁS hijos de esos ancestros (sidebar, header,
       modales, .vl-wrap) no están en esta lista y siguen ocultos. */
    .dashboardContainer, .mainContent, .contentWrapper,
    #vlTicket, #vlTicket * { display: revert !important; }
    #vlTicket {
        display: block !important;
        width: 100%;
        box-sizing: border-box;
        padding: 0 3mm;
        font-family: "Courier New", monospace;
        font-size: ' . $papel['fontSize'] . ';
        line-height: 1.3;
        word-break: break-word;
        color: #000;
        background: #fff;
    }
    /* Tabla en vez de texto con espacios calculados a mano: así las columnas
       quedan alineadas aunque la impresora térmica sustituya la fuente
       monoespaciada por otra de métricas distintas (motivo por el que el
       formato de texto plano se desalineaba en papel real). */
    #vlTicket .tk-center { text-align:center; }
    #vlTicket .tk-bold   { font-weight:700; }
    #vlTicket .tk-big    { font-size:1.15em; }
    #vlTicket .tk-sep, #vlTicket .tk-line { border-top:1px dashed #000; margin:4px 0; }
    #vlTicket .tk-notas  { margin:4px 0; word-break:break-word; }
    #vlTicket .tk-table  { width:100%; border-collapse:collapse; margin-top:2px; }
    #vlTicket .tk-table th, #vlTicket .tk-table td { padding:1px 2px; vertical-align:top; word-break:break-word; }
    #vlTicket .tk-table th { border-bottom:1px dashed #000; text-align:left; font-weight:700; }
    #vlTicket .tk-table td.r, #vlTicket .tk-table th.r { text-align:right; white-space:nowrap; }
    #vlTicket .tk-table td.c, #vlTicket .tk-table th.c { text-align:center; white-space:nowrap; }
    #vlTicket .tk-total  { width:100%; border-collapse:collapse; font-weight:700; }
    #vlTicket .tk-total td.r { text-align:right; }
    @page { size: ' . $papel['pageSize'] . '; margin: ' . $papel['margin'] . '; }
}
@media (max-width:600px) {
    .vl-modal { max-height:100vh; border-radius:0; }
    .vl-filters { flex-direction:column; align-items:stretch; }
    .vl-input[name="buscar"] { width:100%; }
    .vl-input-date { width:100%; }
}
</style>';

$jsExtra = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

require_once __DIR__ . '/../complementos/header.php';

$btnCancelar = (int)($comercio['btn_cancelar_venta'] ?? 0);

$hoy     = date('Y-m-d');
$fBuscar = htmlspecialchars($_GET['buscar'] ?? '');
$fEstado = $_GET['estado'] ?? '';
$fDesde  = $_GET['desde']  ?? $hoy;
$fHasta  = $_GET['hasta']  ?? $hoy;

$totalRegistros = (int)($totales['total']      ?? 0);
$sumaTotal      = (float)($totales['suma_total'] ?? 0);

$qParams = array_filter([
    'buscar' => $_GET['buscar'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'desde'  => $fDesde,
    'hasta'  => $fHasta,
]);

function paginaUrl($p) {
    global $basePath, $qParams;
    $q = array_merge($qParams, ['pagina' => $p]);
    return $basePath . '/ventas/listado?' . http_build_query($q);
}

$estados = [
    ''              => 'Todos los estados',
    'cobrada'       => 'Cobrada',
    'cancelada'     => 'Cancelada',
    'abierta'       => 'Abierta',
    'en_preparacion'=> 'En cocina',
    'lista'         => 'Lista',
    'completada'    => 'Completada',
];

$badges = [
    'cobrada'        => ['ok',     'Cobrada'],
    'cancelada'      => ['cancel', 'Cancelada'],
    'completada'     => ['comp',   'Completada'],
    'abierta'        => ['pend',   'Abierta'],
    'en_preparacion' => ['prep',   'En cocina'],
    'lista'          => ['list',   'Lista'],
];

$metodoPagoLabels = [
    'efectivo'      => 'Efectivo',
    'tarjeta'       => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'mixto'         => 'Mixto',
];
?>

<div class="vl-wrap">

    <!-- Cabecera -->
    <div class="vl-header">
        <div class="vl-header-left">
            <h1><i class="fas fa-list-check"></i> Listado de Ventas</h1>
            <p>Historial completo de todas las transacciones</p>
        </div>
        <div class="vl-header-stats">
            <div class="vl-stat">
                <div class="vl-stat-num"><?php echo number_format($totalRegistros); ?></div>
                <div class="vl-stat-label">Ventas<?php echo ($fBuscar || $fEstado || $fDesde || $fHasta) ? ' filtradas' : ' totales'; ?></div>
            </div>
            <div class="vl-stat">
                <div class="vl-stat-num">$<?php echo number_format($sumaTotal, 0); ?></div>
                <div class="vl-stat-label">Monto total</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" action="<?php echo $basePath; ?>/ventas/listado" class="vl-filters" id="filtrosForm">
        <div class="vl-filter-group">
            <i class="fas fa-search vl-filter-icon"></i>
            <input type="text" name="buscar" value="<?php echo $fBuscar; ?>"
                   placeholder="Buscar por N° orden o mesero…" class="vl-input" id="inputBuscar">
        </div>
        <div class="vl-filter-group">
            <i class="fas fa-filter vl-filter-icon"></i>
            <select name="estado" class="vl-select" onchange="this.form.submit()">
                <?php foreach ($estados as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $fEstado === $val ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vl-filter-group">
            <i class="fas fa-calendar vl-filter-icon"></i>
            <input type="date" name="desde" value="<?php echo htmlspecialchars($fDesde); ?>"
                   class="vl-input vl-input-date" title="Desde">
            <span class="vl-filter-sep">—</span>
            <input type="date" name="hasta" value="<?php echo htmlspecialchars($fHasta); ?>"
                   class="vl-input vl-input-date" title="Hasta">
        </div>
        <button type="submit" class="vl-btn-filter">
            <i class="fas fa-search"></i> Filtrar
        </button>
        <?php if ($fBuscar || $fEstado || $fDesde !== $hoy || $fHasta !== $hoy): ?>
        <a href="<?php echo $basePath; ?>/ventas/listado" class="vl-btn-clear">
            <i class="fas fa-xmark"></i> Limpiar
        </a>
        <?php endif; ?>
    </form>

    <!-- Tabla -->
    <div class="vl-table-wrap">
        <?php if (empty($ventas)): ?>
        <div class="vl-empty">
            <i class="fas fa-receipt"></i>
            <p>No se encontraron ventas con los filtros actuales</p>
        </div>
        <?php else: ?>
        <table class="vl-table">
            <colgroup>
                <col class="vl-col-orden">
                <col class="vl-col-fecha">
                <col class="vl-col-hora">
                <col class="vl-col-mesero">
                <col class="vl-col-mesa">
                <col class="vl-col-platos">
                <col class="vl-col-total">
                <col class="vl-col-metodo">
                <col class="vl-col-cliente">
                <col class="vl-col-estado">
                <col class="vl-col-acciones">
            </colgroup>
            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Mesero</th>
                    <th>Mesa</th>
                    <th class="vl-th-center">Platos</th>
                    <th class="vl-th-right">Total</th>
                    <th class="vl-th-center">Método</th>
                    <th>Cliente</th>
                    <th class="vl-th-center">Estado</th>
                    <th class="vl-th-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $v):
                    $ts    = strtotime($v['fecha_creacion']);
                    $fecha = date('d/m/Y', $ts);
                    $hora  = date('H:i',   $ts);
                    $est   = $v['estado'];
                    [$bClass, $bLabel] = $badges[$est] ?? ['pend', $est];
                    $esDom = str_starts_with($v['numero_orden'] ?? '', 'DOM-');
                    if ($esDom) {
                        $notasParts = explode('—', $v['notas'] ?? '', 2);
                        $clienteNom = isset($notasParts[1]) ? trim(explode('|', $notasParts[1])[0]) : 'Cliente';
                        $mesa   = '🛵 Dom.';
                        $mesero = $clienteNom;
                    } else {
                        $mesa   = $v['mesa_numero'] ? 'Mesa ' . $v['mesa_numero'] : '—';
                        $mesero = $v['usuario_nombre'] ?? 'Sistema';
                    }

                    $metodoPago  = $v['metodo_pago'] ?? '';
                    $metodoLabel = $metodoPagoLabels[$metodoPago] ?? '';
                    $metodoTitle = '';
                    if ($metodoPago === 'mixto') {
                        $partesPago = [];
                        if ((float)$v['pago_efectivo']      > 0) $partesPago[] = 'Efectivo $'      . number_format((float)$v['pago_efectivo'], 2);
                        if ((float)$v['pago_tarjeta']       > 0) $partesPago[] = 'Tarjeta $'       . number_format((float)$v['pago_tarjeta'], 2);
                        if ((float)$v['pago_transferencia'] > 0) $partesPago[] = 'Transferencia $' . number_format((float)$v['pago_transferencia'], 2);
                        $metodoTitle = implode(' + ', $partesPago);
                    }

                    $rowData = json_encode([
                        'id'              => $v['id'],
                        'orden'           => $v['numero_orden'],
                        'fecha'           => $fecha,
                        'hora'            => $hora,
                        'mesero'          => $mesero,
                        'mesa'            => $mesa,
                        'total'           => (float)$v['total'],
                        'platos'          => (int)$v['total_platos'],
                        'estado'          => $est,
                        'bClass'          => $bClass,
                        'bLabel'          => $bLabel,
                        'cliente_id'      => $v['cliente_id']      ? (int)$v['cliente_id'] : null,
                        'cliente_nombre'  => $v['cliente_nombre']  ?? null,
                        'cliente_tipo_doc'=> $v['cliente_tipo_doc']?? null,
                        'cliente_num_doc' => $v['cliente_num_doc'] ?? null,
                        'cliente_telefono'=> $v['cliente_telefono']?? null,
                        'notas'           => $esDom ? null : ($v['notas'] ?? null),
                    ], JSON_HEX_QUOT | JSON_HEX_APOS | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
                ?>
                <tr>
                    <td class="vl-td-orden"><?php echo htmlspecialchars($v['numero_orden']); ?></td>
                    <td><?php echo $fecha; ?></td>
                    <td class="vl-td-hora"><?php echo $hora; ?></td>
                    <td><?php echo htmlspecialchars($mesero); ?></td>
                    <td class="vl-td-mesa"><?php echo htmlspecialchars($mesa); ?></td>
                    <td class="vl-td-center"><?php echo (int)$v['total_platos']; ?></td>
                    <td class="vl-td-monto">$<?php echo number_format((float)$v['total'], 2); ?></td>
                    <td class="vl-td-center">
                        <?php if ($metodoLabel): ?>
                        <span class="vl-badge vl-pago-<?php echo $metodoPago; ?>"
                              <?php echo $metodoTitle ? 'title="' . htmlspecialchars($metodoTitle) . '"' : ''; ?>>
                            <?php echo $metodoLabel; ?>
                        </span>
                        <?php else: ?>
                        <span class="vl-pago-na">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="vl-td-cliente" data-venta="<?php echo $v['id']; ?>">
                        <?php if ($v['cliente_nombre']): ?>
                            <span class="vl-cli-nombre" title="<?php echo htmlspecialchars($v['cliente_nombre']); ?>">
                                <?php echo htmlspecialchars($v['cliente_nombre']); ?>
                            </span>
                        <?php else: ?>
                            <span class="vl-cli-ninguno">Ninguno</span>
                        <?php endif; ?>
                    </td>
                    <td class="vl-td-center"><span class="vl-badge vl-<?php echo $bClass; ?>"><?php echo $bLabel; ?></span></td>
                    <td class="vl-td-center">
                        <button class="vl-btn-ver" onclick='abrirDetalle(<?php echo $rowData; ?>)'
                                title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="vl-btn-cliente <?php echo $v['cliente_id'] ? 'asignado' : ''; ?>"
                                data-venta-id="<?php echo $v['id']; ?>"
                                onclick='abrirAsignarCliente(<?php echo $rowData; ?>)'
                                title="<?php echo $v['cliente_nombre'] ? htmlspecialchars($v['cliente_nombre']) : 'Asignar cliente'; ?>">
                            <i class="fas fa-user-<?php echo $v['cliente_id'] ? 'check' : 'plus'; ?>"></i>
                        </button>
                        <?php if ($btnCancelar && $est !== 'cancelada'): ?>
                        <button class="vl-btn-cancelar-venta"
                                data-venta-id="<?php echo $v['id']; ?>"
                                onclick='cancelarVenta(<?php echo $v["id"]; ?>, <?php echo json_encode($v["numero_orden"]); ?>)'
                                title="Cancelar venta">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <div class="vl-pagination">
        <div class="vl-pag-info">
            <?php if ($totalPags > 1): ?>
                Página <?php echo $pagina; ?> de <?php echo $totalPags; ?> &nbsp;·&nbsp;
            <?php endif; ?>
            <?php echo number_format($totalRegistros); ?> registro<?php echo $totalRegistros != 1 ? 's' : ''; ?>
            <?php if ($sumaTotal > 0): ?>
                &nbsp;·&nbsp; Total: <strong>$<?php echo number_format($sumaTotal, 2); ?></strong>
            <?php endif; ?>
        </div>
        <?php if ($totalPags > 1): ?>
        <div class="vl-pag-controls">
            <?php if ($pagina > 1): ?>
            <a href="<?php echo paginaUrl(1); ?>" class="vl-pag-btn" title="Primera"><i class="fas fa-angles-left"></i></a>
            <a href="<?php echo paginaUrl($pagina - 1); ?>" class="vl-pag-btn"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
            <span class="vl-pag-btn disabled"><i class="fas fa-angles-left"></i></span>
            <span class="vl-pag-btn disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php
            $start = max(1, $pagina - 2);
            $end   = min($totalPags, $pagina + 2);
            if ($start > 1) echo '<span class="vl-pag-dots">…</span>';
            for ($p = $start; $p <= $end; $p++):
            ?>
            <a href="<?php echo paginaUrl($p); ?>"
               class="vl-pag-btn<?php echo $p === $pagina ? ' active' : ''; ?>">
                <?php echo $p; ?>
            </a>
            <?php endfor;
            if ($end < $totalPags) echo '<span class="vl-pag-dots">…</span>';
            ?>

            <?php if ($pagina < $totalPags): ?>
            <a href="<?php echo paginaUrl($pagina + 1); ?>" class="vl-pag-btn"><i class="fas fa-chevron-right"></i></a>
            <a href="<?php echo paginaUrl($totalPags); ?>" class="vl-pag-btn" title="Última"><i class="fas fa-angles-right"></i></a>
            <?php else: ?>
            <span class="vl-pag-btn disabled"><i class="fas fa-chevron-right"></i></span>
            <span class="vl-pag-btn disabled"><i class="fas fa-angles-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ── Modal detalle ─────────────────────────────────────────────────────── -->
<div class="vl-modal-overlay" id="modalOverlay" onclick="cerrarModal(event)">
    <div class="vl-modal">
        <div class="vl-modal-header" id="mHeader">
            <div class="vl-modal-title">
                <span class="vl-modal-orden" id="mOrden"></span>
                <span class="vl-badge" id="mBadge"></span>
            </div>
            <button class="vl-modal-close" onclick="cerrarModal(true)"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="vl-modal-meta">
            <span id="mFecha"><i class="fas fa-calendar"></i> —</span>
            <span id="mHora"><i class="fas fa-clock"></i> —</span>
            <span id="mMesero"><i class="fas fa-user"></i> —</span>
            <span id="mMesa"><i class="fas fa-chair"></i> —</span>
            <span id="mCliente" style="display:none;color:#27ae60"><i class="fas fa-user-tag"></i> —</span>
        </div>
        <div class="vl-modal-body" id="mBody">
            <div class="vl-modal-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>
        <div class="vl-modal-footer">
            <button class="vl-btn-cerrar" onclick="cerrarModal(true)">
                <i class="fas fa-xmark"></i> Cerrar
            </button>
            <button class="vl-btn-imprimir" id="btnImprimir" onclick="imprimirFactura()">
                <i class="fas fa-print"></i> Imprimir factura
            </button>
        </div>
    </div>
</div>

<!-- ── Modal asignar cliente ──────────────────────────────────────────────── -->
<div class="vl-modal-overlay" id="modalCliente" onclick="cerrarModalCliente(event)">
    <div class="vl-modal vl-modal-sm">
        <div class="vl-modal-header">
            <div class="vl-modal-title">
                <i class="fas fa-user-tag" style="font-size:14px"></i>
                <span style="font-size:15px;font-weight:700">Asignar cliente a factura</span>
            </div>
            <button class="vl-modal-close" onclick="cerrarModalCliente(true)"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="vl-modal-body" style="padding:16px 20px">
            <input type="text" id="cliSearchInput" placeholder="Buscar por nombre, teléfono…"
                   class="vl-input" style="width:100%;box-sizing:border-box;margin-bottom:10px">
            <div id="cliResultList" style="max-height:280px;overflow-y:auto;border:1px solid #e8ecf0;border-radius:8px">
                <p style="color:#95a5a6;text-align:center;padding:24px;margin:0">Cargando...</p>
            </div>
        </div>
        <div class="vl-modal-footer">
            <button class="vl-btn-cerrar" onclick="cerrarModalCliente(true)">
                <i class="fas fa-xmark"></i> Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Ticket oculto para impresión -->
<div id="vlTicket"></div>


<script>
const BASE    = '<?php echo $basePath; ?>';
const BASEURL  = '<?php echo $baseUrl; ?>';
const COMERC   = <?php echo json_encode($comercio ?? []); ?>;
const TICKET_W = <?php echo (int)$papel['charWidth']; ?>;
const TICKET_ANGOSTO = <?php echo (($comercio['tamano_papel'] ?? '80mm') === '58mm') ? 'true' : 'false'; ?>;

let ventaActual = null;
let itemsActuales = [];

function abrirDetalle(v) {
    ventaActual = v;
    itemsActuales = [];

    // Rellenar cabecera
    document.getElementById('mOrden').textContent = v.orden;
    const badge = document.getElementById('mBadge');
    badge.textContent   = v.bLabel;
    badge.className     = 'vl-badge vl-' + v.bClass;

    document.getElementById('mFecha').innerHTML  = '<i class="fas fa-calendar"></i> ' + v.fecha;
    document.getElementById('mHora').innerHTML   = '<i class="fas fa-clock"></i> ' + v.hora;
    document.getElementById('mMesero').innerHTML = '<i class="fas fa-user"></i> ' + escH(v.mesero);
    document.getElementById('mMesa').innerHTML   = '<i class="fas fa-chair"></i> ' + escH(v.mesa);

    const mCliEl = document.getElementById('mCliente');
    if (v.cliente_nombre) {
        mCliEl.innerHTML = '<i class="fas fa-user-tag"></i> ' + escH(v.cliente_nombre);
        mCliEl.style.display = '';
    } else {
        mCliEl.style.display = 'none';
    }

    document.getElementById('mBody').innerHTML =
        '<div class="vl-modal-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

    document.getElementById('modalOverlay').classList.add('open');

    fetch(BASE + '/ventas/detalle/' + v.id)
        .then(r => r.json())
        .then(res => {
            if (!res.success) throw new Error('Error');
            itemsActuales = res.data;
            renderItems(res.data, v.total);
        })
        .catch(() => {
            document.getElementById('mBody').innerHTML =
                '<p style="color:#e74c3c;text-align:center">Error al cargar los detalles</p>';
        });
}

function renderItems(items, total) {
    if (!items.length) {
        document.getElementById('mBody').innerHTML = '<p style="color:#95a5a6;text-align:center">Sin ítems</p>';
        return;
    }

    const catLabels = {
        entrada:'Entrada', plato_fuerte:'Plato fuerte', postre:'Postre',
        bebida:'Bebida', snack:'Snack', otro:'Otro'
    };

    let rows = items.map(it => `
        <tr>
            <td>
                <div class="it-nombre">${escH(it.receta_nombre)}</div>
                <div class="it-cat">${catLabels[it.categoria] || it.categoria}</div>
            </td>
            <td class="it-cant">${it.cantidad}</td>
            <td class="it-precio">$${fmt(it.precio_unitario)}</td>
            <td class="it-sub">$${fmt(it.subtotal)}</td>
        </tr>`).join('');

    document.getElementById('mBody').innerHTML = `
        <table class="vl-items-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center">Cant.</th>
                    <th style="text-align:right">Precio unit.</th>
                    <th style="text-align:right">Subtotal</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
        <div class="vl-total-box">
            <span class="vl-total-label">TOTAL</span>
            <span class="vl-total-num">$${fmt(total)}</span>
        </div>`;
}

function cerrarModal(force) {
    if (force === true || force?.target === document.getElementById('modalOverlay')) {
        document.getElementById('modalOverlay').classList.remove('open');
        ventaActual  = null;
        itemsActuales = [];
    }
}

function imprimirFactura() {
    if (!ventaActual) return;
    const logoHtml = COMERC.logo
        ? `<div style="text-align:center;margin-bottom:6px;">
             <img src="${BASEURL}/assets/uploads/comercio/${COMERC.logo}"
                  style="max-width:120px;max-height:70px;object-fit:contain;">
           </div>`
        : '';
    document.getElementById('vlTicket').innerHTML = logoHtml + construirTicket(ventaActual, itemsActuales);
    setTimeout(() => window.print(), 80);
}

function construirTicket(v, items) {
    const neg = COMERC.nombre || 'CHEFCONTROL';
    const esl = COMERC.eslogan || '';
    const rut = COMERC.rut || '';

    let filas = '';
    items.forEach(it => {
        if (TICKET_ANGOSTO) {
            // 58mm: muy poco ancho para 4 columnas — nombre en su propia
            // fila, cantidad y subtotal debajo.
            filas += `<tr><td colspan="3">${escH(it.receta_nombre)}</td></tr>
                      <tr><td>x${it.cantidad}</td><td colspan="2" class="r">$${fmt(it.subtotal)}</td></tr>`;
        } else {
            filas += `<tr>
                <td>${escH(it.receta_nombre)}</td>
                <td class="r">$${fmt(it.precio_unitario)}</td>
                <td class="c">${it.cantidad}</td>
                <td class="r">$${fmt(it.subtotal)}</td>
            </tr>`;
        }
    });

    const colgroup = TICKET_ANGOSTO
        ? `<colgroup><col style="width:34%"><col style="width:33%"><col style="width:33%"></colgroup>`
        : `<colgroup><col style="width:46%"><col style="width:20%"><col style="width:12%"><col style="width:22%"></colgroup>`;
    const encabezadoTabla = TICKET_ANGOSTO
        ? `<tr><th colspan="3">Producto</th></tr>`
        : `<tr><th>Producto</th><th class="r">Unitario</th><th class="c">Cant</th><th class="r">Valor</th></tr>`;

    const clienteHtml = v.cliente_nombre ? `
        <div class="tk-line"></div>
        <div>Cliente: ${escH(v.cliente_nombre)}</div>
        ${(v.cliente_tipo_doc && v.cliente_num_doc) ? `<div>${escH(v.cliente_tipo_doc.toUpperCase())}: ${escH(v.cliente_num_doc)}</div>` : ''}
        ${v.cliente_telefono ? `<div>Tel: ${escH(v.cliente_telefono)}</div>` : ''}
    ` : '';

    const notasHtml = (v.notas && v.notas.trim())
        ? `<div class="tk-notas">Obs: ${escH(v.notas.trim())}</div>` : '';

    return `
        <div class="tk-center tk-bold tk-big">${escH(neg)}</div>
        ${esl ? `<div class="tk-center">${escH(esl)}</div>` : ''}
        ${rut ? `<div class="tk-center">RUT: ${escH(rut)}</div>` : ''}
        <div class="tk-sep"></div>
        <div>Orden:  <strong>${escH(v.orden)}</strong></div>
        <div>Fecha:  ${escH(v.fecha)} ${escH(v.hora)}</div>
        <div>Mesero: ${escH(v.mesero)}</div>
        <div>Mesa:   ${escH(v.mesa)}</div>
        <div>Estado: ${escH(v.bLabel)}</div>
        ${clienteHtml}
        <div class="tk-line"></div>
        <table class="tk-table">
            ${colgroup}
            <thead>${encabezadoTabla}</thead>
            <tbody>${filas}</tbody>
        </table>
        <div class="tk-line"></div>
        ${notasHtml}
        <table class="tk-total"><tr><td>TOTAL</td><td class="r">$${fmt(v.total)}</td></tr></table>
        <div class="tk-sep"></div>
        <div class="tk-center">¡Gracias por su visita!</div>
        <div class="tk-sep"></div>
        <div class="tk-center" style="margin-top:6px">CHEFCONTROL</div>
        <div class="tk-center">Creado por</div>
        <div class="tk-center">CLOUD CONTROL TECNOLOGYS</div>
    `;
}

// ── Asignar cliente ──────────────────────────────────────────────────────────
let ventaParaCliente = null;
let cliSearchTimer   = null;

function abrirAsignarCliente(v) {
    if (v.cliente_id) return; // ya tiene cliente, no permitir cambio
    ventaParaCliente = v;
    document.getElementById('cliSearchInput').value = '';
    document.getElementById('modalCliente').classList.add('open');
    buscarClientes('');
    setTimeout(() => document.getElementById('cliSearchInput').focus(), 60);
}

function cerrarModalCliente(force) {
    if (force === true || force?.target === document.getElementById('modalCliente')) {
        document.getElementById('modalCliente').classList.remove('open');
        ventaParaCliente = null;
    }
}

function buscarClientes(q) {
    fetch(BASE + '/clientes/buscar?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(res => {
            const list = document.getElementById('cliResultList');
            if (!res.success || !res.clientes.length) {
                list.innerHTML = '<p style="color:#95a5a6;text-align:center;padding:20px;margin:0">Sin resultados</p>';
                return;
            }
            list.innerHTML = res.clientes.map(c => `
                <div class="cli-result-item" data-id="${c.id}">
                    <span class="cli-result-nom">${escH(c.nombre)}</span>
                    ${c.telefono ? '<span class="cli-result-tel">' + escH(c.telefono) + '</span>' : ''}
                </div>
            `).join('');
            list.querySelectorAll('.cli-result-item').forEach((el, i) => {
                const c = res.clientes[i];
                el.addEventListener('click', () => seleccionarCliente(c.id, c.nombre, c.tipo_doc, c.num_doc, c.telefono));
            });
        });
}

function seleccionarCliente(clienteId, clienteNombre, clienteTipoDoc, clienteNumDoc, clienteTel) {
    if (!ventaParaCliente) return;
    fetch(BASE + '/ventas/asignar-cliente', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ venta_id: ventaParaCliente.id, cliente_id: clienteId })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return;

        ventaParaCliente.cliente_id       = clienteId;
        ventaParaCliente.cliente_nombre   = clienteNombre;
        ventaParaCliente.cliente_tipo_doc = clienteTipoDoc || null;
        ventaParaCliente.cliente_num_doc  = clienteNumDoc  || null;
        ventaParaCliente.cliente_telefono = clienteTel     || null;

        // Actualizar celda en la tabla
        const cell = document.querySelector(`.vl-td-cliente[data-venta="${ventaParaCliente.id}"]`);
        if (cell) cell.innerHTML = `<span class="vl-cli-nombre" title="${escH(clienteNombre)}">${escH(clienteNombre)}</span>`;

        // Actualizar botón (icono + clase)
        const btn = document.querySelector(`.vl-btn-cliente[data-venta-id="${ventaParaCliente.id}"]`);
        if (btn) {
            btn.querySelector('i').className = 'fas fa-user-check';
            btn.classList.add('asignado');
            btn.title = clienteNombre;
        }

        // Si el modal de detalle está abierto con esta misma venta, actualizar
        if (ventaActual && ventaActual.id === ventaParaCliente.id) {
            ventaActual.cliente_nombre   = clienteNombre;
            ventaActual.cliente_tipo_doc = clienteTipoDoc || null;
            ventaActual.cliente_num_doc  = clienteNumDoc  || null;
            ventaActual.cliente_telefono = clienteTel     || null;
            const mCliEl = document.getElementById('mCliente');
            mCliEl.innerHTML = '<i class="fas fa-user-tag"></i> ' + escH(clienteNombre);
            mCliEl.style.display = '';
        }
        cerrarModalCliente(true);
    });
}

document.getElementById('cliSearchInput').addEventListener('input', function() {
    clearTimeout(cliSearchTimer);
    cliSearchTimer = setTimeout(() => buscarClientes(this.value.trim()), 300);
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { cerrarModalCliente(true); cerrarModal(true); }
});

function fmt(n)  { return parseFloat(n).toFixed(2); }
function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

document.getElementById('inputBuscar').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') this.form.submit();
});

async function cancelarVenta(id, orden) {
    const conf = await Swal.fire({
        title: '¿Cancelar venta?',
        html: `La venta <b>${escH(orden)}</b> será marcada como cancelada.<br><small style="color:#95a5a6">Esta acción no se puede deshacer.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-ban"></i> Sí, cancelar',
        cancelButtonText: 'No, volver',
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#7f8c8d',
    });
    if (!conf.isConfirmed) return;

    try {
        const res  = await fetch(BASE + '/ventas/cancelar-venta', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ venta_id: id })
        });
        const data = await res.json();
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cancelar la venta' });
            return;
        }
        const btn = document.querySelector(`.vl-btn-cancelar-venta[data-venta-id="${id}"]`);
        const row = btn?.closest('tr');
        if (row) {
            const badge = row.querySelector('.vl-badge');
            if (badge) { badge.textContent = 'Cancelada'; badge.className = 'vl-badge vl-cancel'; }
            btn.remove();
        }
        Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: `Venta ${orden} cancelada`, timer: 2000, showConfirmButton: false });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error de red', text: e.message });
    }
}
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
