<?php
// vista/reportes/index.php

require_once __DIR__ . '/../../config/security.php';

$titulo       = 'Reportes - CHEFCONTROL';
$paginaActual = 'reportes';
$baseUrl      = Config::getBaseUrl();
$basePath     = Config::getBasePath();

$cssExtra = '<link rel="stylesheet" href="' . $baseUrl . '/assets/css/reportes.css">';
$jsExtra  = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
';

require_once __DIR__ . '/../complementos/header.php';

$badgeCls  = ['entrada' => 'badge-entrada', 'salida' => 'badge-salida', 'ajuste' => 'badge-ajuste'];
$labelTipo = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'];

$f_desde     = htmlspecialchars($_GET['desde']     ?? date('Y-m-01'));
$f_hasta     = htmlspecialchars($_GET['hasta']     ?? date('Y-m-d'));
$f_tipo      = $_GET['tipo']      ?? '';
$f_insumo    = (int)($_GET['insumo']    ?? 0);
$f_categoria = $_GET['categoria'] ?? '';

$periodoLabel = date('d/m/Y', strtotime($f_desde)) . ' — ' . date('d/m/Y', strtotime($f_hasta));

$movJson = json_encode($movimientos ?? []);
?>

<div class="rep-container">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="rep-header">
        <div class="rep-header-left">
            <h1><i class="fas fa-chart-bar" style="margin-right:8px;opacity:.85;"></i>Reportes del Sistema</h1>
            <p>Consulta, filtra y exporta movimientos de inventario</p>
        </div>
        <div class="rep-header-right">
            <button class="btn-export btn-pdf" id="btnPdf">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button class="btn-export btn-excel" id="btnExcel">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <a class="btn-export btn-csv" id="btnCsv"
               href="<?php echo $basePath; ?>/reportes/exportar-csv?desde=<?php echo $f_desde; ?>&hasta=<?php echo $f_hasta; ?>&tipo=<?php echo urlencode($f_tipo); ?>&insumo=<?php echo $f_insumo; ?>&categoria=<?php echo urlencode($f_categoria); ?>">
                <i class="fas fa-file-csv"></i> CSV
            </a>
        </div>
    </div>

    <!-- ── Layout central (filtros izq | stats der) ───────────────── -->
    <div class="rep-top">

        <!-- Filtros -->
        <div class="rep-filtros-panel">
            <div class="rep-filtros-titulo">
                <i class="fas fa-filter"></i> Filtros de búsqueda
            </div>
            <div class="rep-filtros-body">
                <form id="filtroForm" method="GET" action="<?php echo $basePath; ?>/reportes">
                    <div class="filter-group">
                        <label>Desde</label>
                        <input type="date" name="desde" id="fDesde" class="filter-control" value="<?php echo $f_desde; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Hasta</label>
                        <input type="date" name="hasta" id="fHasta" class="filter-control" value="<?php echo $f_hasta; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Tipo de movimiento</label>
                        <select name="tipo" id="fTipo" class="filter-control">
                            <option value="">Todos los tipos</option>
                            <option value="entrada" <?php echo $f_tipo === 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                            <option value="salida"  <?php echo $f_tipo === 'salida'  ? 'selected' : ''; ?>>Salida</option>
                            <option value="ajuste"  <?php echo $f_tipo === 'ajuste'  ? 'selected' : ''; ?>>Ajuste</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Categoría de insumo</label>
                        <select name="categoria" id="fCategoria" class="filter-control">
                            <option value="">Todas las categorías</option>
                            <option value="carnes"   <?php echo $f_categoria === 'carnes'   ? 'selected' : ''; ?>>Carnes</option>
                            <option value="verduras" <?php echo $f_categoria === 'verduras' ? 'selected' : ''; ?>>Verduras</option>
                            <option value="lacteos"  <?php echo $f_categoria === 'lacteos'  ? 'selected' : ''; ?>>Lácteos</option>
                            <option value="granos"   <?php echo $f_categoria === 'granos'   ? 'selected' : ''; ?>>Granos</option>
                            <option value="especias" <?php echo $f_categoria === 'especias' ? 'selected' : ''; ?>>Especias</option>
                            <option value="bebidas"  <?php echo $f_categoria === 'bebidas'  ? 'selected' : ''; ?>>Bebidas</option>
                            <option value="otros"    <?php echo $f_categoria === 'otros'    ? 'selected' : ''; ?>>Otros</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Insumo específico</label>
                        <select name="insumo" id="fInsumo" class="filter-control">
                            <option value="0">Todos los insumos</option>
                            <?php foreach ($insumos as $ins): ?>
                                <option value="<?php echo $ins['id']; ?>" <?php echo $f_insumo == $ins['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ins['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filtrar">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button type="button" class="btn-limpiar" id="btnLimpiar">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats del sistema -->
        <div class="rep-stats-panel">
            <div class="rep-stats-grid">
                <div class="sg-card" style="border-left-color:#27ae60;">
                    <div class="sg-icon-wrap" style="background:#eafaf1;color:#27ae60;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="sg-info">
                        <div class="sg-num"><?php echo $generales['insumos'] ?? 0; ?></div>
                        <div class="sg-label">Insumos activos</div>
                    </div>
                </div>
                <div class="sg-card" style="border-left-color:#e67e22;">
                    <div class="sg-icon-wrap" style="background:#fef5ec;color:#e67e22;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="sg-info">
                        <div class="sg-num"><?php echo $generales['recetas'] ?? 0; ?></div>
                        <div class="sg-label">Recetas activas</div>
                    </div>
                </div>
                <div class="sg-card" style="border-left-color:#3498db;">
                    <div class="sg-icon-wrap" style="background:#eaf4fb;color:#3498db;">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="sg-info">
                        <div class="sg-num"><?php echo $generales['proveedores'] ?? 0; ?></div>
                        <div class="sg-label">Proveedores</div>
                    </div>
                </div>
                <div class="sg-card" style="border-left-color:#9b59b6;">
                    <div class="sg-icon-wrap" style="background:#f5eef8;color:#9b59b6;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="sg-info">
                        <div class="sg-num"><?php echo $generales['usuarios'] ?? 0; ?></div>
                        <div class="sg-label">Usuarios activos</div>
                    </div>
                </div>
                <div class="sg-card" style="border-left-color:#e74c3c;">
                    <div class="sg-icon-wrap" style="background:#fdedec;color:#e74c3c;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="sg-info">
                        <div class="sg-num"><?php echo ($generales['stock_bajo'] ?? 0) + ($generales['sin_stock'] ?? 0); ?></div>
                        <div class="sg-label">Alertas de stock</div>
                    </div>
                </div>
            </div>

            <!-- Resumen del período -->
            <div class="rep-periodo">
                <div class="rep-periodo-titulo">
                    <i class="fas fa-calendar-alt"></i>
                    Resumen del período: <span><?php echo $periodoLabel; ?></span>
                </div>
                <div class="rep-periodo-metrics">
                    <div class="periodo-metric">
                        <div class="pm-icon" style="background:#f0f2f5;color:#2c3e50;">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="pm-num" id="rTotal"><?php echo $resumen['total_movimientos'] ?? 0; ?></div>
                        <div class="pm-label">Movimientos</div>
                    </div>
                    <div class="periodo-metric">
                        <div class="pm-icon" style="background:#d5f5e3;color:#1e8449;">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                        <div class="pm-num" id="rEntradas" style="color:#1e8449;">
                            <?php echo number_format($resumen['total_entradas'] ?? 0, 1); ?>
                        </div>
                        <div class="pm-label">Uds. entrada</div>
                    </div>
                    <div class="periodo-metric">
                        <div class="pm-icon" style="background:#fadbd8;color:#c0392b;">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <div class="pm-num" id="rSalidas" style="color:#c0392b;">
                            <?php echo number_format($resumen['total_salidas'] ?? 0, 1); ?>
                        </div>
                        <div class="pm-label">Uds. salida</div>
                    </div>
                    <div class="periodo-metric">
                        <div class="pm-icon" style="background:#eaf4fb;color:#2471a3;">
                            <i class="fas fa-sliders"></i>
                        </div>
                        <div class="pm-num" id="rAjustes" style="color:#2471a3;"><?php echo $resumen['total_ajustes'] ?? 0; ?></div>
                        <div class="pm-label">Ajustes</div>
                    </div>
                    <div class="periodo-metric">
                        <div class="pm-icon" style="background:#f5eef8;color:#7d3c98;">
                            <i class="fas fa-carrot"></i>
                        </div>
                        <div class="pm-num" id="rInsumos" style="color:#7d3c98;"><?php echo $resumen['insumos_movidos'] ?? 0; ?></div>
                        <div class="pm-label">Insumos distintos</div>
                    </div>
                </div>
            </div>
        </div><!-- /rep-stats-panel -->

    </div><!-- /rep-top -->

    <!-- ── Tabla de movimientos ───────────────────────────────────── -->
    <div class="rep-tabla-card">
        <div class="rep-tabla-header">
            <div class="rep-tabla-title">
                <h2><i class="fas fa-list-alt"></i> Movimientos de Inventario</h2>
                <span class="rep-count" id="countLabel">
                    <?php echo count($movimientos ?? []); ?> registro(s)
                </span>
            </div>
        </div>

        <div class="rep-table-wrap">
            <table class="rep-table" id="tablaMovimientos">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Insumo</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Stock Ant.</th>
                        <th>Stock Nvo.</th>
                        <th>Motivo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody id="tbodyMovimientos">
                <?php if (!empty($movimientos)): ?>
                    <?php foreach ($movimientos as $i => $m):
                        $fecha    = date('d/m/Y', strtotime($m['fecha']));
                        $hora     = date('H:i',   strtotime($m['fecha']));
                        $signo    = $m['tipo'] === 'entrada' ? '+' : ($m['tipo'] === 'salida' ? '−' : '=');
                        $sClase   = 'signo-' . $m['tipo'];
                        $nvoClase = ($m['stock_nuevo'] <= 0) ? 'sn-crit' : 'sn-ok';
                    ?>
                    <tr>
                        <td style="color:#bdc3c7;"><?php echo $i + 1; ?></td>
                        <td style="white-space:nowrap;font-weight:600;"><?php echo $fecha; ?></td>
                        <td style="color:#95a5a6;"><?php echo $hora; ?></td>
                        <td><strong><?php echo htmlspecialchars($m['insumo']); ?></strong></td>
                        <td><span class="cat-badge"><?php echo ucfirst($m['categoria']); ?></span></td>
                        <td>
                            <span class="badge-tipo <?php echo $badgeCls[$m['tipo']] ?? ''; ?>">
                                <?php echo $labelTipo[$m['tipo']] ?? $m['tipo']; ?>
                            </span>
                        </td>
                        <td class="<?php echo $sClase; ?>">
                            <?php echo $signo . number_format($m['cantidad'], 2); ?>
                        </td>
                        <td style="color:#7f8c8d;"><?php echo htmlspecialchars($m['unidad']); ?></td>
                        <td class="stock-ant"><?php echo number_format($m['stock_anterior'], 2); ?></td>
                        <td class="stock-nvo <?php echo $nvoClase; ?>"><?php echo number_format($m['stock_nuevo'], 2); ?></td>
                        <td style="color:#7f8c8d;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="<?php echo htmlspecialchars($m['descripcion'] ?? ''); ?>">
                            <?php echo htmlspecialchars($m['descripcion'] ?? '—'); ?>
                        </td>
                        <td style="color:#7f8c8d;"><?php echo htmlspecialchars($m['usuario'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" class="no-data">
                            <i class="fas fa-inbox"></i>
                            No hay movimientos en el período seleccionado
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /rep-container -->

<!-- Loading -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <span style="font-weight:600;color:#2c3e50;">Generando archivo…</span>
</div>

<!-- Toast -->
<div class="toast" id="toast"><span id="toastMsg"></span></div>

<script>
(function () {
    const basePath = '<?php echo $basePath; ?>';
    let movimientos = <?php echo $movJson; ?>;

    // ── Limpiar filtros ───────────────────────────────────────────
    document.getElementById('btnLimpiar').addEventListener('click', function () {
        const hoy = new Date().toISOString().slice(0, 10);
        document.getElementById('fDesde').value     = hoy.slice(0, 7) + '-01';
        document.getElementById('fHasta').value     = hoy;
        document.getElementById('fTipo').value      = '';
        document.getElementById('fCategoria').value = '';
        document.getElementById('fInsumo').value    = '0';
    });

    // ── Link CSV dinámico ─────────────────────────────────────────
    document.getElementById('filtroForm').addEventListener('change', actualizarCsv);
    function actualizarCsv() {
        const desde     = document.getElementById('fDesde').value;
        const hasta     = document.getElementById('fHasta').value;
        const tipo      = document.getElementById('fTipo').value;
        const insumo    = document.getElementById('fInsumo').value;
        const categoria = document.getElementById('fCategoria').value;
        document.getElementById('btnCsv').href =
            basePath + '/reportes/exportar-csv?desde=' + desde +
            '&hasta=' + hasta + '&tipo=' + encodeURIComponent(tipo) +
            '&insumo=' + insumo + '&categoria=' + encodeURIComponent(categoria);
    }

    // ── Helpers ───────────────────────────────────────────────────
    function mostrarLoading() { document.getElementById('loadingOverlay').classList.add('show'); }
    function ocultarLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

    let toastTimer;
    function toast(msg, ok = true) {
        const el = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        el.className = 'toast show ' + (ok ? 't-ok' : 't-err');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => el.classList.remove('show'), 3000);
    }

    function getHeaders() {
        return ['#','Fecha','Hora','Insumo','Categoría','Tipo','Cantidad','Unidad','Stock Ant.','Stock Nvo.','Motivo','Usuario'];
    }

    function getRows() {
        return movimientos.map((m, i) => {
            const fecha = m.fecha ? m.fecha.slice(0,10).split('-').reverse().join('/') : '';
            const hora  = m.fecha ? m.fecha.slice(11,16) : '';
            const signo = m.tipo === 'entrada' ? '+' : m.tipo === 'salida' ? '-' : '=';
            return [
                i + 1, fecha, hora, m.insumo,
                m.categoria ? m.categoria.charAt(0).toUpperCase() + m.categoria.slice(1) : '',
                m.tipo      ? m.tipo.charAt(0).toUpperCase()      + m.tipo.slice(1)      : '',
                signo + parseFloat(m.cantidad).toFixed(2),
                m.unidad,
                parseFloat(m.stock_anterior).toFixed(2),
                parseFloat(m.stock_nuevo).toFixed(2),
                m.descripcion || '—',
                m.usuario     || '—',
            ];
        });
    }

    function getTitulo() {
        const desde = document.getElementById('fDesde').value;
        const hasta = document.getElementById('fHasta').value;
        return 'Reporte Inventario | ' + desde.split('-').reverse().join('/') + ' — ' + hasta.split('-').reverse().join('/');
    }

    // ── PDF ───────────────────────────────────────────────────────
    document.getElementById('btnPdf').addEventListener('click', function () {
        if (!movimientos.length) { toast('No hay datos para exportar', false); return; }
        mostrarLoading();
        setTimeout(() => {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ orientation:'landscape', unit:'mm', format:'a4' });

                doc.setFillColor(108, 52, 131);
                doc.rect(0, 0, 297, 20, 'F');
                doc.setTextColor(255,255,255);
                doc.setFontSize(13);
                doc.setFont('helvetica','bold');
                doc.text('ChefControl — ' + getTitulo(), 10, 13);
                doc.setFontSize(9);
                doc.text('Generado: ' + new Date().toLocaleString('es'), 220, 13);

                doc.setTextColor(44,62,80);
                doc.setFontSize(9);
                doc.text('Total registros: ' + movimientos.length, 10, 26);

                doc.autoTable({
                    startY: 30,
                    head:   [getHeaders()],
                    body:   getRows(),
                    styles: { fontSize: 8, cellPadding: 2.5, overflow:'ellipsize' },
                    headStyles: { fillColor:[108,52,131], textColor:255, fontStyle:'bold', halign:'center' },
                    alternateRowStyles: { fillColor:[248,249,250] },
                    columnStyles: {
                        0:{cellWidth:8,halign:'center'}, 1:{cellWidth:20},
                        2:{cellWidth:14}, 5:{cellWidth:18,halign:'center'},
                        6:{cellWidth:20,halign:'right'}, 7:{cellWidth:15,halign:'center'},
                        8:{cellWidth:22,halign:'right'}, 9:{cellWidth:22,halign:'right'},
                        10:{cellWidth:35},
                    },
                    didDrawCell: function (data) {
                        if (data.section !== 'body' || data.column.index !== 5) return;
                        const tipo = movimientos[data.row.index]?.tipo;
                        const c = { entrada:[213,245,227], salida:[250,219,216], ajuste:[234,244,251] };
                        if (c[tipo]) data.cell.styles.fillColor = c[tipo];
                    },
                    margin:{ left:8, right:8 },
                });

                const pages = doc.internal.getNumberOfPages();
                for (let p = 1; p <= pages; p++) {
                    doc.setPage(p);
                    doc.setFontSize(8); doc.setTextColor(150);
                    doc.text('Pág. ' + p + ' / ' + pages + '  —  ChefControl', 148, 205, {align:'center'});
                }

                const desde = document.getElementById('fDesde').value;
                const hasta = document.getElementById('fHasta').value;
                doc.save('reporte_' + desde + '_' + hasta + '.pdf');
                toast('PDF generado correctamente');
            } catch(e) { console.error(e); toast('Error al generar PDF', false); }
            finally { ocultarLoading(); }
        }, 80);
    });

    // ── Excel ─────────────────────────────────────────────────────
    document.getElementById('btnExcel').addEventListener('click', function () {
        if (!movimientos.length) { toast('No hay datos para exportar', false); return; }
        mostrarLoading();
        setTimeout(() => {
            try {
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.aoa_to_sheet([getHeaders(), ...getRows()]);
                ws['!cols'] = [
                    {wch:5},{wch:12},{wch:8},{wch:22},{wch:12},
                    {wch:10},{wch:12},{wch:8},{wch:14},{wch:14},{wch:28},{wch:18},
                ];
                XLSX.utils.book_append_sheet(wb, ws, 'Movimientos');

                const resData = [
                    ['Reporte ChefControl'], [getTitulo()],
                    ['Generado:', new Date().toLocaleString('es')], [],
                    ['Total registros',   movimientos.length],
                    ['Entradas',          movimientos.filter(m=>m.tipo==='entrada').length],
                    ['Salidas',           movimientos.filter(m=>m.tipo==='salida').length],
                    ['Ajustes',           movimientos.filter(m=>m.tipo==='ajuste').length],
                    ['Insumos distintos', [...new Set(movimientos.map(m=>m.insumo))].length],
                ];
                const wsRes = XLSX.utils.aoa_to_sheet(resData);
                wsRes['!cols'] = [{wch:22},{wch:18}];
                XLSX.utils.book_append_sheet(wb, wsRes, 'Resumen');

                const desde = document.getElementById('fDesde').value;
                const hasta = document.getElementById('fHasta').value;
                XLSX.writeFile(wb, 'reporte_' + desde + '_' + hasta + '.xlsx');
                toast('Excel generado correctamente');
            } catch(e) { console.error(e); toast('Error al generar Excel', false); }
            finally { ocultarLoading(); }
        }, 80);
    });

})();
</script>

<?php require_once __DIR__ . '/../complementos/footer.php'; ?>
