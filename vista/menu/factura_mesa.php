<?php
// Factura combinada de mesa — todos los pedidos del día
$nombreComercio = htmlspecialchars($comercioFac['nombre'] ?? 'ChefControl');
$dirComercio    = htmlspecialchars($comercioFac['direccion'] ?? '');
$telComercio    = htmlspecialchars($comercioFac['telefono']  ?? '');
$numMesa        = $ordenMesa['mesa_numero'] ?? '—';
$nomMesa        = htmlspecialchars($ordenMesa['mesa_nombre'] ?? '');
$fecha          = date('d/m/Y H:i');

// Agrupar items por nombre para sumar cantidades duplicadas
$agrupados = [];
foreach ($ordenMesa['items'] as $it) {
    $nom = $it['receta_nombre'];
    if (!isset($agrupados[$nom])) {
        $agrupados[$nom] = ['nombre' => $nom, 'cantidad' => 0, 'subtotal' => 0.0];
    }
    $agrupados[$nom]['cantidad'] += (int)$it['cantidad'];
    $agrupados[$nom]['subtotal'] += (float)$it['subtotal'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura Mesa <?php echo $numMesa; ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:monospace;max-width:320px;margin:0 auto;padding:20px 16px;background:#fff;color:#111;font-size:13px;}
h1{font-size:17px;text-align:center;margin-bottom:2px;}
.sub{text-align:center;font-size:11px;color:#555;margin-bottom:3px;}
hr{border:none;border-top:1px dashed #aaa;margin:10px 0;}
.row{display:flex;justify-content:space-between;padding:2px 0;}
.row .name{flex:1;padding-right:8px;}
.row .qty{width:24px;text-align:center;}
.row .price{text-align:right;min-width:60px;}
.total-row{display:flex;justify-content:space-between;padding:3px 0;font-weight:700;font-size:15px;}
.footer{text-align:center;font-size:11px;color:#777;margin-top:10px;}
@media print{body{padding:0;} @page{margin:6mm;}}
</style>
</head>
<body>
<h1><?php echo $nombreComercio; ?></h1>
<?php if ($dirComercio): ?><div class="sub"><?php echo $dirComercio; ?></div><?php endif; ?>
<?php if ($telComercio): ?><div class="sub">Tel: <?php echo $telComercio; ?></div><?php endif; ?>
<hr>
<div class="row"><span>Mesa:</span><span><b>[<?php echo $numMesa; ?>] <?php echo $nomMesa; ?></b></span></div>
<div class="row"><span>Fecha:</span><span><?php echo $fecha; ?></span></div>
<hr>
<div class="row" style="font-weight:700;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:4px;">
    <span class="name">Producto</span><span class="qty">Ud</span><span class="price">Subtotal</span>
</div>
<?php foreach ($agrupados as $it): ?>
<div class="row">
    <span class="name"><?php echo htmlspecialchars($it['nombre']); ?></span>
    <span class="qty"><?php echo (int)$it['cantidad']; ?></span>
    <span class="price">$<?php echo number_format((float)$it['subtotal'], 0, ',', '.'); ?></span>
</div>
<?php endforeach; ?>
<hr>
<div class="total-row"><span>TOTAL</span><span>$<?php echo number_format((float)$ordenMesa['total'], 0, ',', '.'); ?></span></div>
<hr>
<div class="footer">¡Gracias por tu visita!<br><?php echo $fecha; ?></div>
<script>window.onload=function(){window.print();}</script>
</body>
</html>
