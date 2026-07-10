<?php
// Vista publica del menu digital - sin autenticacion
require_once __DIR__ . '/../../core/FotoUtil.php';
$nombreMenu   = htmlspecialchars($menuPublico['nombre'] ?? 'Menu');
$mesaId       = $menuPublico['mesa_id'] ?? null;
$mesaNombre   = $mesaId ? ('[' . ($menuPublico['mesa_numero'] ?? '') . '] ' . ($menuPublico['mesa_nombre'] ?? '')) : null;
$basePath     = Config::getBasePath();
$baseUrl      = Config::getBaseUrl();
$token        = $menuPublico['token'];
$soloConsulta = !$mesaId;

$propinaActiva = (int)($comercioMenu['propina_activa']    ?? 0);
$propinaPct    = (int)($comercioMenu['propina_porcentaje'] ?? 10);

$catLabels = [
    'entrada'      => 'Entradas',
    'plato_fuerte' => 'Platos fuertes',
    'postre'       => 'Postres',
    'bebida'       => 'Bebidas',
    'snack'        => 'Snacks',
    'otro'         => 'Otros',
];
$catIcons = [
    'entrada'      => 'fa-leaf',
    'plato_fuerte' => 'fa-utensils',
    'postre'       => 'fa-ice-cream',
    'bebida'       => 'fa-glass-water',
    'snack'        => 'fa-cookie-bite',
    'otro'         => 'fa-tag',
];
$catColors = [
    'entrada'      => '#e67e22',
    'plato_fuerte' => '#e74c3c',
    'postre'       => '#9b59b6',
    'bebida'       => '#3498db',
    'snack'        => '#27ae60',
    'otro'         => '#95a5a6',
];

$porCategoria = [];
foreach ($itemsMenu as $r) {
    $porCategoria[$r['categoria']][] = $r;
}

$itemsJS = json_encode(array_map(function($r) {
    $fotos = FotoUtil::parseFotoUrls($r['foto'] ?? '');
    $foto  = $fotos[0] ?? null;
    $ud  = $r['unidades_disponibles'] ?? null;
    $udN = ($ud !== null) ? max(0, (int)$ud) : null;
    return [
        'id'     => (int)$r['id'],
        'nombre' => $r['nombre'],
        'precio' => (float)$r['precio_venta'],
        'foto'   => $foto,
        'fotos'  => $fotos,
        'stock'  => $udN,
    ];
}, $itemsMenu), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $nombreMenu; ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f4f6fa;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;min-height:100vh;}

.mp-header{background:linear-gradient(135deg,#1a3a6e,#2471a3);color:#fff;padding:20px 20px 16px;
    position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.15);}
.mp-header-top{display:flex;align-items:center;gap:12px;margin-bottom:4px;}
.mp-icon{width:40px;height:40px;background:rgba(255,255,255,.2);border-radius:10px;
    display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.mp-title{font-size:18px;font-weight:800;}
.mp-mesa-tag{font-size:12px;font-weight:600;opacity:.85;display:flex;align-items:center;gap:5px;margin-left:auto;flex-shrink:0;}
.mp-subtitle{font-size:12px;opacity:.65;padding-left:52px;}
.mp-consulta-banner{background:#fff8e1;color:#f57c00;border:1px solid #ffe082;border-radius:10px;
    padding:10px 16px;margin:16px 16px 0;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;}

.mp-content{padding:16px 16px 140px;}
.mp-empty{text-align:center;padding:60px 20px;color:#8e8e93;}
.mp-empty i{font-size:48px;display:block;margin-bottom:14px;opacity:.25;}
.mp-cat-title{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:800;
    text-transform:uppercase;letter-spacing:.5px;padding:14px 0 8px;border-bottom:2px solid;margin-bottom:10px;}
.mp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:6px;}

.mp-card{background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.07);}
.mp-card-thumb{height:110px;display:flex;align-items:center;justify-content:center;font-size:36px;}
.mp-card-thumb img{width:100%;height:100%;object-fit:cover;}
.mp-card-body{padding:10px 12px 12px;}
.mp-card-name{font-size:13px;font-weight:700;color:#1c1c1e;margin-bottom:4px;line-height:1.3;}
.mp-card-price{font-size:15px;font-weight:800;color:#2471a3;margin-bottom:8px;}
.mp-card-desc{font-size:11px;color:#8e8e93;margin-bottom:8px;line-height:1.4;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}

.mp-stock-low{display:inline-block;background:#fff3e0;color:#e65100;border:1px solid #ffcc80;
    border-radius:6px;font-size:10px;font-weight:700;padding:2px 7px;margin-bottom:6px;}
.mp-card.agotado .mp-card-thumb{filter:grayscale(1);opacity:.7;}
.mp-card.agotado .mp-card-name,.mp-card.agotado .mp-card-price{color:#8e8e93;}
.mp-badge-agotado{display:inline-block;background:#f2f2f7;color:#8e8e93;border-radius:6px;
    font-size:10px;font-weight:700;padding:2px 7px;margin-bottom:6px;}

.mp-add-btn{width:100%;border:none;border-radius:9px;padding:8px;font-size:13px;font-weight:700;
    cursor:pointer;background:#2471a3;color:#fff;display:flex;align-items:center;justify-content:center;gap:6px;transition:background .15s;}
.mp-add-btn:hover{background:#1a5a8a;}
.mp-add-btn:disabled{background:#c7d3df;cursor:default;}
.mp-ctrl{display:none;justify-content:space-between;align-items:center;background:#f0f6fc;border-radius:9px;padding:4px;}
.mp-qty-btn{width:32px;height:32px;border:none;border-radius:7px;background:#2471a3;color:#fff;
    font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.mp-qty-num{font-size:15px;font-weight:800;color:#1c1c1e;}

.mp-cart{position:fixed;bottom:0;left:0;right:0;z-index:200;background:#fff;border-top:1px solid #e5e5ea;
    padding:14px 20px 20px;transform:translateY(100%);transition:transform .3s ease;box-shadow:0 -4px 20px rgba(0,0,0,.12);}
.mp-cart.visible{transform:translateY(0);}
.mp-cart-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.mp-cart-title{font-size:15px;font-weight:800;color:#1c1c1e;display:flex;align-items:center;gap:8px;}
.mp-cart-badge{background:#2471a3;color:#fff;border-radius:10px;font-size:11px;font-weight:700;padding:2px 8px;}
.mp-cart-total{font-size:18px;font-weight:800;color:#2471a3;}
.mp-order-btn{width:100%;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;
    cursor:pointer;background:#2471a3;color:#fff;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .15s;}
.mp-order-btn:hover{background:#1a5a8a;}
.mp-order-btn:disabled{opacity:.6;cursor:default;}

.mp-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:300;display:none;align-items:flex-end;}
.mp-overlay.show{display:flex;}
.mp-sheet{background:#fff;border-radius:22px 22px 0 0;width:100%;max-height:90vh;overflow-y:auto;padding:24px 20px 32px;}
.mp-sheet-handle{width:40px;height:4px;background:#e5e5ea;border-radius:2px;margin:0 auto 18px;}
.mp-sheet-title{font-size:17px;font-weight:800;color:#1c1c1e;margin-bottom:16px;}
.mp-sum-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f2f2f7;}
.mp-sum-item:last-child{border:none;}
.mp-sum-qty{width:26px;height:26px;background:#2471a3;color:#fff;border-radius:8px;
    font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.mp-sum-name{flex:1;font-size:14px;font-weight:600;color:#1c1c1e;}
.mp-sum-price{font-size:14px;font-weight:700;color:#2471a3;}
.mp-sum-divider{border:none;border-top:1px dashed #e5e5ea;margin:12px 0;}
.mp-sum-row{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:14px;color:#3a3a3c;}
.mp-sum-total{font-size:18px;font-weight:800;color:#1c1c1e;}
.mp-propina-wrap{display:flex;align-items:center;gap:10px;padding:4px 0;}
.mp-propina-label{font-size:14px;color:#3a3a3c;flex:1;}
.mp-propina-input{width:100px;border:1.5px solid #d1d1d6;border-radius:9px;padding:7px 10px;
    font-size:14px;font-weight:700;color:#2471a3;text-align:right;outline:none;}
.mp-propina-input:focus{border-color:#2471a3;}
.mp-notas{width:100%;border:1.5px solid #e5e5ea;border-radius:10px;padding:9px 14px;
    font-size:13px;font-family:inherit;outline:none;resize:none;margin:12px 0 16px;}
.mp-notas:focus{border-color:#2471a3;}
.mp-btn-confirm{width:100%;border:none;border-radius:12px;padding:15px;font-size:15px;font-weight:700;
    cursor:pointer;background:#27ae60;color:#fff;display:flex;align-items:center;justify-content:center;gap:8px;}
.mp-btn-confirm:disabled{opacity:.6;cursor:default;}
.mp-btn-cancel{width:100%;border:none;background:none;padding:12px;font-size:14px;color:#8e8e93;cursor:pointer;margin-top:6px;}

.mp-tracking{position:fixed;inset:0;z-index:400;background:#f4f6fa;display:none;flex-direction:column;overflow-y:auto;}
.mp-tracking.show{display:flex;}
.mp-track-header{background:linear-gradient(135deg,#1a3a6e,#2471a3);color:#fff;padding:24px 20px 20px;text-align:center;}
.mp-track-icon{width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 10px;}
.mp-track-title{font-size:20px;font-weight:800;margin-bottom:4px;}
.mp-track-sub{font-size:13px;opacity:.75;}
.mp-track-num{font-size:13px;font-weight:700;background:rgba(255,255,255,.15);
    border-radius:20px;padding:4px 14px;display:inline-block;margin-top:8px;}
.mp-track-body{padding:20px;}
.mp-steps{display:flex;flex-direction:column;gap:0;}
.mp-step{display:flex;align-items:flex-start;gap:14px;padding:14px 0;position:relative;}
.mp-step:not(:last-child)::after{content:'';position:absolute;left:19px;top:48px;bottom:-14px;width:2px;background:#e5e5ea;}
.mp-step.done::after{background:#27ae60;}
.mp-step-dot{width:40px;height:40px;border-radius:50%;border:2px solid #e5e5ea;background:#fff;
    display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;color:#c7c7cc;position:relative;z-index:1;}
.mp-step.done .mp-step-dot{background:#27ae60;border-color:#27ae60;color:#fff;}
.mp-step.active .mp-step-dot{background:#2471a3;border-color:#2471a3;color:#fff;box-shadow:0 0 0 4px #2471a322;}
.mp-step-info{padding-top:8px;}
.mp-step-label{font-size:15px;font-weight:700;color:#1c1c1e;}
.mp-step.pending .mp-step-label{color:#8e8e93;}
.mp-step-sub{font-size:12px;color:#8e8e93;margin-top:2px;}
.mp-step.active .mp-step-sub{color:#2471a3;font-weight:600;}

.mp-pagado-card{background:#fff;border-radius:16px;padding:20px;margin-top:16px;
    box-shadow:0 2px 12px rgba(0,0,0,.07);text-align:center;}
.mp-pagado-icon{width:64px;height:64px;background:#e8f5e9;border-radius:50%;
    display:flex;align-items:center;justify-content:center;font-size:28px;color:#27ae60;margin:0 auto 12px;}
.mp-pagado-title{font-size:18px;font-weight:800;color:#1c1c1e;margin-bottom:6px;}
.mp-pagado-sub{font-size:13px;color:#636366;line-height:1.6;}
.mp-btn-factura{margin-top:16px;border:none;border-radius:12px;padding:13px 24px;
    font-size:14px;font-weight:700;cursor:pointer;background:#2471a3;color:#fff;
    display:inline-flex;align-items:center;gap:8px;}
.mp-btn-nuevo{margin-top:10px;border:none;border-radius:12px;padding:11px 24px;
    font-size:14px;font-weight:700;cursor:pointer;background:#f2f2f7;color:#3a3a3c;
    display:inline-flex;align-items:center;gap:8px;}

/* Lightbox */
.mp-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:600;
    display:none;flex-direction:column;}
.mp-lightbox.show{display:flex;}

.mp-lb-topbar{position:relative;display:flex;align-items:center;justify-content:center;
    padding:14px 56px 0;flex-shrink:0;}
.mp-lb-name{color:#fff;font-size:15px;font-weight:700;text-align:center;
    text-shadow:0 1px 4px rgba(0,0,0,.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mp-lb-close{position:absolute;right:14px;top:10px;width:40px;height:40px;
    background:rgba(255,255,255,.13);border:none;border-radius:50%;color:#fff;
    font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;
    transition:background .15s;}
.mp-lb-close:hover{background:rgba(255,255,255,.28);}
.mp-lb-counter{position:absolute;left:14px;top:14px;color:rgba(255,255,255,.5);
    font-size:12px;font-weight:600;}

.mp-lb-main{flex:1;display:flex;align-items:center;justify-content:center;
    position:relative;overflow:hidden;padding:16px 56px;}
.mp-lb-img{max-width:100%;max-height:100%;object-fit:contain;
    border-radius:6px;transition:opacity .26s ease;user-select:none;}
.mp-lb-img.fade{opacity:0;}

.mp-lb-arrow{position:absolute;top:50%;transform:translateY(-50%);
    width:44px;height:44px;background:rgba(255,255,255,.13);border:none;border-radius:50%;
    color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;
    justify-content:center;transition:background .15s;z-index:10;flex-shrink:0;}
.mp-lb-arrow:hover{background:rgba(255,255,255,.28);}
.mp-lb-arrow.lb-prev{left:8px;}
.mp-lb-arrow.lb-next{right:8px;}
.mp-lb-arrow[hidden]{display:none;}

.mp-lb-thumbs{display:flex;gap:8px;padding:10px 16px 18px;
    overflow-x:auto;justify-content:center;flex-shrink:0;}
.mp-lb-thumbs::-webkit-scrollbar{height:3px;}
.mp-lb-thumbs::-webkit-scrollbar-thumb{background:rgba(255,255,255,.25);border-radius:2px;}
.mp-lb-thumb{width:68px;height:68px;border-radius:8px;object-fit:cover;
    cursor:pointer;opacity:.45;border:2.5px solid transparent;
    transition:opacity .2s,border-color .2s,transform .15s;flex-shrink:0;}
.mp-lb-thumb:hover{opacity:.8;}
.mp-lb-thumb.active{opacity:1;border-color:#fff;transform:scale(1.06);}

.mp-card-thumb.has-foto{cursor:pointer;overflow:hidden;}
.mp-card-thumb.has-foto img{transition:transform .25s;}
.mp-card-thumb.has-foto:hover img{transform:scale(1.06);}

/* Tarjeta resumen de mesa (estado lista) */
.mp-mesa-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.08);border-left:4px solid #27ae60;}
.mp-mesa-card-title{font-size:15px;font-weight:800;color:#1c1c1e;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.mp-mesa-card-title i{color:#27ae60;}
.mp-mesa-hint{margin-top:14px;background:#fff8e1;border-radius:10px;padding:10px 14px;font-size:13px;color:#e65100;display:flex;align-items:center;gap:8px;font-weight:600;}
.mp-mesa-loading{text-align:center;padding:24px;color:#8e8e93;font-size:20px;}
.mp-propina-sugerida{display:flex;justify-content:space-between;align-items:center;padding:6px 0;font-size:14px;color:#e67e22;}
</style>
</head>
<body>

<div class="mp-header">
    <div class="mp-header-top">
        <div class="mp-icon"><i class="fas fa-utensils"></i></div>
        <div class="mp-title"><?php echo $nombreMenu; ?></div>
        <?php if ($mesaNombre): ?>
        <div class="mp-mesa-tag"><i class="fas fa-chair"></i> <?php echo htmlspecialchars($mesaNombre); ?></div>
        <?php endif; ?>
    </div>
    <?php if (!$soloConsulta): ?>
    <div class="mp-subtitle">Selecciona tus platos y haz tu pedido</div>
    <?php else: ?>
    <div class="mp-subtitle">Consulta nuestro menu</div>
    <?php endif; ?>
</div>

<?php if ($soloConsulta): ?>
<div class="mp-consulta-banner">
    <i class="fas fa-eye"></i> Este menu es solo de consulta - no se pueden hacer pedidos
</div>
<?php endif; ?>

<div class="mp-content">
    <?php if (empty($itemsMenu)): ?>
    <div class="mp-empty">
        <i class="fas fa-utensils"></i>
        <p style="font-size:16px;font-weight:700;color:#3a3a3c;">Este menu aun no tiene productos</p>
    </div>
    <?php else: ?>
    <?php foreach ($porCategoria as $cat => $items):
        $color = $catColors[$cat] ?? '#95a5a6';
        $icon  = $catIcons[$cat]  ?? 'fa-tag';
    ?>
    <div class="mp-cat-title" style="color:<?php echo $color; ?>;border-color:<?php echo $color . '44'; ?>">
        <i class="fas <?php echo $icon; ?>"></i>
        <?php echo $catLabels[$cat] ?? ucfirst($cat); ?>
    </div>
    <div class="mp-grid">
    <?php foreach ($items as $r):
        $fotos = FotoUtil::parseFotoUrls($r['foto'] ?? '');
        $foto  = $fotos[0] ?? null;
        $ud      = $r['unidades_disponibles'] ?? null;
        $udN     = ($ud !== null) ? max(0, (int)$ud) : null;
        $agotado = ($udN !== null && $udN <= 0);
        $fotosAttr = count($fotos) ? htmlspecialchars(json_encode($fotos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES) : '';
    ?>
    <div class="mp-card<?php echo $agotado ? ' agotado' : ''; ?>">
        <div class="mp-card-thumb<?php echo $foto ? ' has-foto' : ''; ?>"
             style="background:<?php echo $foto ? '#e8ecf0' : $color . '22'; ?>"
             <?php if ($foto): ?>
             onclick="abrirLightbox(this)"
             data-fotos="<?php echo $fotosAttr; ?>"
             data-nombre="<?php echo htmlspecialchars($r['nombre'], ENT_QUOTES); ?>"
             <?php endif; ?>>
            <?php if ($foto): ?>
            <img src="<?php echo htmlspecialchars($foto); ?>" alt=""
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:36px;background:<?php echo $color . '22'; ?>">
                <i class="fas <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
            </div>
            <?php else: ?>
            <i class="fas <?php echo $icon; ?>" style="color:<?php echo $color; ?>"></i>
            <?php endif; ?>
        </div>
        <div class="mp-card-body">
            <div class="mp-card-name"><?php echo htmlspecialchars($r['nombre']); ?></div>
            <?php if ($r['descripcion']): ?>
            <div class="mp-card-desc"><?php echo htmlspecialchars($r['descripcion']); ?></div>
            <?php endif; ?>
            <div class="mp-card-price">$<?php echo number_format((float)$r['precio_venta'], 0, ',', '.'); ?></div>
            <?php if ($agotado): ?>
            <div class="mp-badge-agotado"><i class="fas fa-times-circle"></i> Sin stock</div>
            <?php elseif ($udN !== null && $udN <= 10): ?>
            <div class="mp-stock-low"><i class="fas fa-exclamation-circle"></i> Quedan <?php echo $udN; ?></div>
            <?php endif; ?>
            <?php if (!$soloConsulta): ?>
            <button class="mp-add-btn" id="add-<?php echo $r['id']; ?>"
                    <?php echo $agotado ? 'disabled' : 'onclick="agregar(' . $r['id'] . ')"'; ?>>
                <i class="fas fa-plus"></i> <?php echo $agotado ? 'Sin stock' : 'Agregar'; ?>
            </button>
            <div class="mp-ctrl" id="ctrl-<?php echo $r['id']; ?>">
                <button class="mp-qty-btn" onclick="cambiar(<?php echo $r['id']; ?>, -1)">&#8722;</button>
                <span class="mp-qty-num" id="qty-<?php echo $r['id']; ?>">1</span>
                <button class="mp-qty-btn" onclick="cambiar(<?php echo $r['id']; ?>, 1)">+</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!$soloConsulta): ?>
<!-- Carrito flotante -->
<div class="mp-cart" id="cart">
    <div class="mp-cart-header">
        <div class="mp-cart-title">
            <i class="fas fa-shopping-bag"></i> Tu pedido
            <span class="mp-cart-badge" id="cartCount">0</span>
        </div>
        <div class="mp-cart-total" id="cartTotal">$0</div>
    </div>
    <button class="mp-order-btn" id="orderBtn" onclick="abrirResumen()">
        <i class="fas fa-receipt"></i> Ver resumen y pedir
    </button>
</div>

<!-- Modal resumen / confirmacion -->
<div class="mp-overlay" id="overlayResumen" onclick="cerrarResumen(event)">
    <div class="mp-sheet" id="sheetResumen">
        <div class="mp-sheet-handle"></div>
        <div class="mp-sheet-title"><i class="fas fa-receipt" style="color:#2471a3;margin-right:8px"></i>Resumen de tu pedido</div>
        <div id="sumItems"></div>
        <hr class="mp-sum-divider">
        <div class="mp-sum-row mp-sum-total"><span>Total mi pedido</span><span id="sumTotal" style="color:#2471a3"></span></div>
        <textarea class="mp-notas" id="cartNotas" rows="2" placeholder="Notas adicionales (opcional)..."></textarea>
        <button class="mp-btn-confirm" id="btnConfirmar" onclick="hacerPedido()">
            <i class="fas fa-paper-plane"></i> Confirmar pedido
        </button>
        <button class="mp-btn-cancel" onclick="cerrarResumen()">Cancelar</button>
    </div>
</div>

<!-- Pantalla de seguimiento -->
<div class="mp-tracking" id="viewTracking">
    <div class="mp-track-header">
        <div class="mp-track-icon"><i class="fas fa-utensils"></i></div>
        <div class="mp-track-title">Pedido enviado</div>
        <div class="mp-track-sub">Sigue el estado de tu orden</div>
        <div class="mp-track-num" id="trackNumero"></div>
    </div>
    <div class="mp-track-body">
        <div class="mp-steps" id="trackSteps"></div>
        <!-- Resumen de mesa: aparece cuando el pedido está listo -->
        <div id="mesaResumenWrap" style="display:none;margin-top:20px;"></div>
        <div id="pagadoCard" style="display:none">
            <div class="mp-pagado-card">
                <div class="mp-pagado-icon"><i class="fas fa-check-circle"></i></div>
                <div class="mp-pagado-title">Mesa pagada</div>
                <div class="mp-pagado-sub">Tu cuenta fue cobrada.<br>Gracias por tu visita!</div>
                <br>
                <button class="mp-btn-factura" onclick="descargarFactura()">
                    <i class="fas fa-download"></i> Descargar factura
                </button>
                <br>
                <button class="mp-btn-nuevo" onclick="nuevoPedido()">
                    <i class="fas fa-plus"></i> Nuevo pedido
                </button>
            </div>
        </div>
        <!-- Boton para hacer otro pedido mientras se esta en tracking (no pagado) -->
        <div id="btnOtroPedidoWrap" style="margin-top:24px;text-align:center;display:none">
            <button class="mp-btn-nuevo" onclick="nuevoPedido()">
                <i class="fas fa-arrow-left"></i> Agregar mas items
            </button>
        </div>
    </div>
</div>

<script>
const ITEMS       = <?php echo $itemsJS; ?>;
const BASE        = <?php echo json_encode($basePath); ?>;
const TOKEN       = <?php echo json_encode($token); ?>;
const MESA_ID     = <?php echo (int)($menuPublico['mesa_id'] ?? 0); ?>;
const PROPINA_PCT = <?php echo $propinaActiva ? $propinaPct : 0; ?>;
const STORAGE_KEY = 'chefmenu_' + TOKEN;
const itemsMap    = {};
ITEMS.forEach(r => { itemsMap[r.id] = r; });

let carrito       = {};
let ventaId       = null;
let trackInterval = null;

function fmt(n) { return '$' + Math.round(n).toLocaleString('es-CO'); }

/* ---- Persistencia localStorage ---- */
function guardarSesion(id, numero, estado) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify({ ventaId: id, numero: numero, estado: estado })); } catch(e) {}
}
function limpiarSesion() {
    try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
}
function leerSesion() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY)); } catch(e) { return null; }
}

/* ---- Carrito ---- */
function agregar(id) {
    const r = itemsMap[id];
    if (!r || (r.stock !== null && r.stock <= 0)) return;
    carrito[id] = 1;
    document.getElementById('add-' + id).style.display = 'none';
    document.getElementById('ctrl-' + id).style.display = 'flex';
    document.getElementById('qty-' + id).textContent = 1;
    actualizarCarrito();
}

function cambiar(id, delta) {
    const r   = itemsMap[id];
    const max = (r && r.stock !== null) ? r.stock : Infinity;
    let nuevo = (carrito[id] || 0) + delta;
    if (delta > 0 && nuevo > max) nuevo = max;
    carrito[id] = nuevo;
    if (carrito[id] <= 0) {
        delete carrito[id];
        document.getElementById('add-' + id).style.display = '';
        document.getElementById('ctrl-' + id).style.display = 'none';
    } else {
        document.getElementById('qty-' + id).textContent = carrito[id];
    }
    actualizarCarrito();
}

function subtotalCarrito() {
    let t = 0;
    for (const [id, qty] of Object.entries(carrito)) {
        const r = itemsMap[+id];
        if (r) t += r.precio * qty;
    }
    return t;
}

function actualizarCarrito() {
    let count = 0;
    for (const q of Object.values(carrito)) count += q;
    document.getElementById('cartCount').textContent = count;
    document.getElementById('cartTotal').textContent  = fmt(subtotalCarrito());
    document.getElementById('cart').classList.toggle('visible', count > 0);
}

/* ---- Resumen modal ---- */
function abrirResumen() {
    const sub = subtotalCarrito();
    let html  = '';
    for (const [id, qty] of Object.entries(carrito)) {
        const r = itemsMap[+id];
        if (!r) continue;
        html += '<div class="mp-sum-item">'
            + '<div class="mp-sum-qty">' + qty + '</div>'
            + '<div class="mp-sum-name">' + r.nombre + '</div>'
            + '<div class="mp-sum-price">' + fmt(r.precio * qty) + '</div>'
            + '</div>';
    }
    document.getElementById('sumItems').innerHTML = html;
    document.getElementById('sumTotal').textContent = fmt(sub);
    document.getElementById('overlayResumen').classList.add('show');
}

function recalcularTotal() {
    document.getElementById('sumTotal').textContent = fmt(subtotalCarrito());
}

function cerrarResumen(e) {
    if (e && e.target !== document.getElementById('overlayResumen')) return;
    document.getElementById('overlayResumen').classList.remove('show');
}

/* ---- Confirmar pedido ---- */
async function hacerPedido() {
    const items = Object.entries(carrito).map(function(entry) {
        const r = itemsMap[+entry[0]];
        return { id_receta: +entry[0], cantidad: entry[1], precio_unitario: r ? r.precio : 0 };
    });
    if (!items.length) return;
    const notas = document.getElementById('cartNotas').value.trim();
    const btn   = document.getElementById('btnConfirmar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    try {
        const res = await fetch(BASE + '/menu/' + TOKEN + '/pedir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: items, notas: notas })
        });
        const d = await res.json();
        if (d.ok) {
            ventaId = d.id_venta;
            guardarSesion(ventaId, d.numero, 'abierta');
            document.getElementById('overlayResumen').classList.remove('show');
            iniciarTracking(d.numero, 'abierta');
        } else {
            alert(d.msg || 'Error al enviar el pedido');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Confirmar pedido';
        }
    } catch(err) {
        alert('Error de conexion');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Confirmar pedido';
    }
}

/* ---- Tracking ---- */
const STEPS = [
    { estado: 'abierta',        label: 'Recibido',       sub: 'Tu pedido fue recibido',               icon: 'fa-clock' },
    { estado: 'en_preparacion', label: 'En preparacion', sub: 'La cocina esta preparando tu pedido',   icon: 'fa-fire' },
    { estado: 'lista',          label: 'Listo',          sub: 'Tu pedido esta listo en la mesa',       icon: 'fa-bell' },
    { estado: 'cobrada',        label: 'Pagado',         sub: 'La cuenta fue cobrada',                 icon: 'fa-check-circle' }
];
const ESTADO_IDX = { abierta: 0, en_preparacion: 1, lista: 2, cobrada: 3 };

function iniciarTracking(numero, estadoInicial) {
    document.getElementById('trackNumero').textContent = 'Orden #' + numero;
    document.getElementById('viewTracking').classList.add('show');
    renderSteps(estadoInicial || 'abierta');

    if (estadoInicial === 'cobrada') {
        mostrarPagado(false);
        return;
    }
    // Mostrar boton "agregar mas" cuando el pedido ya fue enviado
    document.getElementById('btnOtroPedidoWrap').style.display = 'block';

    if (ventaId && !trackInterval) {
        trackInterval = setInterval(consultarEstado, 5000);
    }
}

function renderSteps(estadoActual) {
    const idx = ESTADO_IDX[estadoActual] !== undefined ? ESTADO_IDX[estadoActual] : 0;
    let html  = '';
    STEPS.forEach(function(s, i) {
        const cls = i < idx ? 'done' : (i === idx ? 'active' : 'pending');
        const ico = i < idx ? 'fa-check' : s.icon;
        const sub = i < idx ? 'Completado' : s.sub;
        html += '<div class="mp-step ' + cls + '">'
            + '<div class="mp-step-dot"><i class="fas ' + ico + '"></i></div>'
            + '<div class="mp-step-info">'
            +   '<div class="mp-step-label">' + s.label + '</div>'
            +   '<div class="mp-step-sub">' + sub + '</div>'
            + '</div></div>';
    });
    document.getElementById('trackSteps').innerHTML = html;

    // Mostrar resumen de mesa (con propina) solo cuando el pedido está listo
    if (estadoActual === 'lista') {
        mostrarResumenMesa();
    } else {
        const wrap = document.getElementById('mesaResumenWrap');
        if (wrap) wrap.style.display = 'none';
    }
}

async function mostrarResumenMesa() {
    const wrap = document.getElementById('mesaResumenWrap');
    if (!wrap) return;
    wrap.style.display = 'block';
    wrap.innerHTML = '<div class="mp-mesa-loading"><i class="fas fa-spinner fa-spin"></i></div>';
    try {
        const res = await fetch(BASE + '/menu/' + TOKEN + '/mesa-total');
        const d   = await res.json();
        if (!d.ok || !d.items || !d.items.length) { wrap.style.display = 'none'; return; }

        const itemsHtml = d.items.map(function(it) {
            return '<div class="mp-sum-item">'
                + '<div class="mp-sum-qty">' + it.cantidad + '</div>'
                + '<div class="mp-sum-name">' + it.producto + '</div>'
                + '<div class="mp-sum-price">' + fmt(it.subtotal) + '</div>'
                + '</div>';
        }).join('');

        const propinaSug = PROPINA_PCT > 0 ? Math.round(d.total * PROPINA_PCT / 100) : 0;
        const propinaHtml = PROPINA_PCT > 0
            ? '<div class="mp-propina-sugerida">'
              + '<span><i class="fas fa-hand-holding-heart" style="margin-right:6px"></i>Propina (' + PROPINA_PCT + '%)</span>'
              + '<input type="number" id="mesaPropinaInput" class="mp-propina-input" min="0" value="' + propinaSug + '" oninput="recalcTotalMesa(' + d.total + ')">'
              + '</div>'
            : '';

        wrap.innerHTML = '<div class="mp-mesa-card">'
            + '<div class="mp-mesa-card-title"><i class="fas fa-receipt"></i> Total de tu mesa</div>'
            + itemsHtml
            + '<hr class="mp-sum-divider">'
            + propinaHtml
            + '<div class="mp-sum-row mp-sum-total" style="margin-top:6px"><span>Total mesa</span>'
            +   '<span id="mesaTotalVal" style="color:#2471a3">' + fmt(d.total + propinaSug) + '</span></div>'
            + '<div class="mp-mesa-hint"><i class="fas fa-bell"></i> Tu pedido está listo — solicita la cuenta al mesero</div>'
            + '</div>';
    } catch(e) { wrap.style.display = 'none'; }
}

function recalcTotalMesa(base) {
    const inp     = document.getElementById('mesaPropinaInput');
    const totalEl = document.getElementById('mesaTotalVal');
    if (!inp || !totalEl) return;
    const propina = Math.max(0, parseInt(inp.value) || 0);
    totalEl.textContent = fmt(base + propina);
}

async function consultarEstado() {
    if (!ventaId) return;
    try {
        const res = await fetch(BASE + '/menu/' + TOKEN + '/estado/' + ventaId);
        const d   = await res.json();
        if (!d.ok) return;
        const sesion = leerSesion();
        if (sesion) guardarSesion(ventaId, sesion.numero, d.estado);
        renderSteps(d.estado);
        if (d.estado === 'cobrada') {
            clearInterval(trackInterval);
            trackInterval = null;
            document.getElementById('btnOtroPedidoWrap').style.display = 'none';
            mostrarPagado(true);
        }
    } catch(e) {}
}

function mostrarPagado(autoDescarga) {
    document.getElementById('pagadoCard').style.display = 'block';
    if (autoDescarga) setTimeout(descargarFactura, 1000);
}

function descargarFactura() {
    window.open(BASE + '/menu/' + TOKEN + '/factura-mesa', '_blank');
}

function nuevoPedido() {
    limpiarSesion();
    clearInterval(trackInterval);
    trackInterval = null;
    ventaId = null;
    carrito = {};
    document.getElementById('viewTracking').classList.remove('show');
    document.getElementById('pagadoCard').style.display = 'none';
    document.getElementById('btnOtroPedidoWrap').style.display = 'none';
    const btn = document.getElementById('btnConfirmar');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Confirmar pedido'; }
    actualizarCarrito();
}

/* ---- Restaurar sesion al cargar pagina ---- */
(function() {
    const sesion = leerSesion();
    if (!sesion || !sesion.ventaId) return;
    ventaId = sesion.ventaId;
    iniciarTracking(sesion.numero, sesion.estado);
    if (sesion.estado !== 'cobrada' && !trackInterval) {
        trackInterval = setInterval(consultarEstado, 5000);
    }
})();
</script>
<?php endif; ?>
<!-- Lightbox de fotos -->
<div class="mp-lightbox" id="mpLightbox">
    <!-- Barra superior -->
    <div class="mp-lb-topbar">
        <span class="mp-lb-counter" id="lbCounter"></span>
        <span class="mp-lb-name"    id="lbName"></span>
        <button class="mp-lb-close" id="lbClose"><i class="fas fa-times"></i></button>
    </div>
    <!-- Imagen principal + flechas -->
    <div class="mp-lb-main" id="lbMain">
        <button class="mp-lb-arrow lb-prev" id="lbPrev"><i class="fas fa-chevron-left"></i></button>
        <img class="mp-lb-img" id="lbImg" src="" alt="">
        <button class="mp-lb-arrow lb-next" id="lbNext"><i class="fas fa-chevron-right"></i></button>
    </div>
    <!-- Miniaturas -->
    <div class="mp-lb-thumbs" id="lbThumbs"></div>
</div>

<script>
/* ---- Lightbox ---- */
(function(){
    let lbFotos = [];
    let lbIdx   = 0;
    let lbTimer = null;

    const overlay  = document.getElementById('mpLightbox');
    const imgEl    = document.getElementById('lbImg');
    const nameEl   = document.getElementById('lbName');
    const countEl  = document.getElementById('lbCounter');
    const thumbsEl = document.getElementById('lbThumbs');
    const prevBtn  = document.getElementById('lbPrev');
    const nextBtn  = document.getElementById('lbNext');

    /* El thumb llama abrirLightbox(this) — lee directamente del data-attribute */
    window.abrirLightbox = function(el) {
        var fotos = [];
        try { fotos = JSON.parse(el.dataset.fotos || '[]'); } catch(e) {}
        if (!fotos.length) return;
        lbFotos = fotos;
        lbIdx   = 0;
        nameEl.textContent = el.dataset.nombre || '';

        // Construir miniaturas (solo si hay más de 1)
        if (lbFotos.length > 1) {
            thumbsEl.innerHTML = lbFotos.map(function(url, i){
                return '<img class="mp-lb-thumb' + (i === 0 ? ' active' : '') + '" src="' +
                       url + '" alt="" onclick="lbGoTo(' + i + ')">';
            }).join('');
            thumbsEl.style.display = 'flex';
        } else {
            thumbsEl.innerHTML    = '';
            thumbsEl.style.display = 'none';
        }

        lbRender(false);
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        lbStartAuto();
    };

    function lbRender(fade) {
        // Flechas
        prevBtn.hidden = (lbFotos.length < 2);
        nextBtn.hidden = (lbFotos.length < 2);

        // Contador
        if (lbFotos.length > 1) {
            countEl.textContent = (lbIdx + 1) + ' / ' + lbFotos.length;
        } else {
            countEl.textContent = '';
        }

        // Imagen con fade
        if (fade) {
            imgEl.classList.add('fade');
            setTimeout(function(){
                imgEl.src = lbFotos[lbIdx];
                imgEl.classList.remove('fade');
            }, 260);
        } else {
            imgEl.src = lbFotos[lbIdx];
        }

        // Resaltar miniatura activa
        thumbsEl.querySelectorAll('.mp-lb-thumb').forEach(function(t, i){
            t.classList.toggle('active', i === lbIdx);
        });
        // Scroll para que la activa quede visible
        const activThumb = thumbsEl.children[lbIdx];
        if (activThumb) activThumb.scrollIntoView({ block:'nearest', inline:'center', behavior:'smooth' });
    }

    window.lbGoTo = function(i) {
        clearInterval(lbTimer);
        lbIdx = i;
        lbRender(true);
        lbStartAuto();
    };

    function lbStartAuto() {
        clearInterval(lbTimer);
        if (lbFotos.length < 2) return;
        lbTimer = setInterval(function(){
            lbIdx = (lbIdx + 1) % lbFotos.length;
            lbRender(true);
        }, 5000);
    }

    function cerrar() {
        clearInterval(lbTimer);
        lbTimer = null;
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    prevBtn.addEventListener('click', function(){
        lbGoTo((lbIdx - 1 + lbFotos.length) % lbFotos.length);
    });
    nextBtn.addEventListener('click', function(){
        lbGoTo((lbIdx + 1) % lbFotos.length);
    });
    document.getElementById('lbClose').addEventListener('click', cerrar);

    // Click en fondo oscuro cierra
    overlay.addEventListener('click', function(e){
        if (e.target === overlay || e.target === document.getElementById('lbMain')) cerrar();
    });

    // Swipe móvil
    let tsX = null;
    overlay.addEventListener('touchstart', function(e){ tsX = e.touches[0].clientX; }, { passive: true });
    overlay.addEventListener('touchend', function(e){
        if (tsX === null || lbFotos.length < 2) return;
        const dx = e.changedTouches[0].clientX - tsX;
        tsX = null;
        if (Math.abs(dx) < 40) return;
        lbGoTo(dx < 0 ? (lbIdx + 1) % lbFotos.length : (lbIdx - 1 + lbFotos.length) % lbFotos.length);
    }, { passive: true });

    // Teclado
    document.addEventListener('keydown', function(e){
        if (!overlay.classList.contains('show')) return;
        if (e.key === 'Escape')      cerrar();
        if (e.key === 'ArrowRight' && lbFotos.length > 1) lbGoTo((lbIdx + 1) % lbFotos.length);
        if (e.key === 'ArrowLeft'  && lbFotos.length > 1) lbGoTo((lbIdx - 1 + lbFotos.length) % lbFotos.length);
    });
})();
</script>
</body>
</html>
