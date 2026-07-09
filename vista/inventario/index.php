<?php
// vista/inventario/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Inventario - CHEFCONTROL';
$paginaActual = 'inventario';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/inventario.css">';

require_once __DIR__ . '/../complementos/header.php';

$iconosCat = [
    'carnes'   => ['icon' => 'fa-drumstick-bite', 'bg' => 'bg-carnes'],
    'verduras' => ['icon' => 'fa-leaf',           'bg' => 'bg-verduras'],
    'lacteos'  => ['icon' => 'fa-cheese',         'bg' => 'bg-lacteos'],
    'granos'   => ['icon' => 'fa-seedling',       'bg' => 'bg-granos'],
    'especias' => ['icon' => 'fa-mortar-pestle',  'bg' => 'bg-especias'],
    'bebidas'  => ['icon' => 'fa-wine-bottle',    'bg' => 'bg-bebidas'],
    'otros'    => ['icon' => 'fa-box',            'bg' => 'bg-otros'],
];

function stockClass($stock, $minimo) {
    if ($stock <= 0)       return 'stock-critico';
    if ($stock <= $minimo) return 'stock-bajo';
    return 'stock-ok';
}
function barClass($stock, $minimo) {
    if ($stock <= 0)       return 'bar-critico';
    if ($stock <= $minimo) return 'bar-bajo';
    return 'bar-ok';
}
function barWidth($stock, $minimo) {
    if ($minimo <= 0) return min(100, $stock > 0 ? 100 : 0);
    $pct = ($stock / ($minimo * 3)) * 100;
    return min(100, max(0, round($pct)));
}

$badgeTipo = [
    'entrada' => 'badge-entrada',
    'venta'   => 'badge-venta',
    'salida'  => 'badge-salida',
    'ajuste'  => 'badge-ajuste',  // compatibilidad con registros anteriores
];
$labelTipo = [
    'entrada' => 'Entrada',
    'venta'   => 'Venta',
    'salida'  => 'Salida',
    'ajuste'  => 'Ajuste',
];
?>

<div class="inventario-container">

    <!-- Header -->
    <div class="inv-header">
        <div>
            <h1><i class="fas fa-warehouse" style="color:#2c3e50;margin-right:10px;"></i>Control de Inventario</h1>
            <p>Consulta el historial de movimientos de stock de tus insumos</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="inv-stats">
        <div class="stat-card ok">
            <div class="stat-icon" style="color:#27ae60;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['stock_ok'] ?? 0; ?></div>
            <div class="stat-label">Stock Normal</div>
        </div>
        <div class="stat-card bajo">
            <div class="stat-icon" style="color:#e67e22;"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['stock_bajo'] ?? 0; ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        <div class="stat-card critico">
            <div class="stat-icon" style="color:#e74c3c;"><i class="fas fa-times-circle"></i></div>
            <div class="stat-number"><?php echo $estadisticas['sin_stock'] ?? 0; ?></div>
            <div class="stat-label">Sin Stock</div>
        </div>
        <div class="stat-card hoy">
            <div class="stat-icon" style="color:#3498db;"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-number"><?php echo $estadisticas['mov_hoy'] ?? 0; ?></div>
            <div class="stat-label">Movimientos Hoy</div>
        </div>
    </div>

    <!-- Historial de movimientos -->
    <div class="historial-card">
        <div class="historial-header">
            <h2><i class="fas fa-history"></i> Historial de Movimientos</h2>
            <span style="font-size:13px;color:#7f8c8d;">Últimos 30 movimientos</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="hist-table" id="tablaHistorial">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock anterior → nuevo</th>
                        <th>Motivo</th>
                        <th>Proveedor</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody id="historialBody">
                    <?php if (!empty($movimientos)): ?>
                        <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td style="white-space:nowrap;color:#7f8c8d;">
                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($mov['insumo_nombre']); ?></strong></td>
                            <td>
                                <span class="badge-tipo <?php echo $badgeTipo[$mov['tipo']] ?? ''; ?>">
                                    <?php echo $labelTipo[$mov['tipo']] ?? $mov['tipo']; ?>
                                </span>
                            </td>
                            <td style="font-weight:700;">
                                <?php echo ($mov['tipo'] === 'entrada' ? '+' : ($mov['tipo'] === 'salida' ? '-' : '=')); ?>
                                <?php echo number_format($mov['cantidad'], 2); ?>
                                <?php echo htmlspecialchars($mov['unidad_medida']); ?>
                            </td>
                            <td>
                                <span class="stock-ant"><?php echo number_format($mov['stock_anterior'], 2); ?></span>
                                <span class="stock-arrow">→</span>
                                <span class="stock-nvo <?php echo stockClass($mov['stock_nuevo'], 0); ?>">
                                    <?php echo number_format($mov['stock_nuevo'], 2); ?>
                                </span>
                            </td>
                            <td style="color:#7f8c8d;"><?php echo htmlspecialchars($mov['descripcion'] ?? '—'); ?></td>
                            <td>
                                <?php if (!empty($mov['proveedor_nombre'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;color:#0d8a7e;font-size:13px;">
                                        <i class="fas fa-truck"></i>
                                        <?php echo htmlspecialchars($mov['proveedor_nombre']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#bdc3c7;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#7f8c8d;"><?php echo htmlspecialchars($mov['usuario_nombre'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="no-historial">
                            <i class="fas fa-history"></i> Sin movimientos registrados todavía
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
