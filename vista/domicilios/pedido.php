<?php
// vista/domicilios/pedido.php  —  Página pública (sin auth)
require_once __DIR__ . '/../../config/config.php';

$baseUrl  = Config::getBaseUrl();
$basePath = Config::getBasePath();

$nombreComercio = htmlspecialchars($comercio['nombre'] ?? 'ChefControl');
$logoComercio   = !empty($comercio['logo'])
    ? $baseUrl . '/assets/media/comercio/' . $comercio['logo']
    : null;

$categoriasLabel = [
    'entrada'      => 'Entradas',
    'plato_fuerte' => 'Platos fuertes',
    'postre'       => 'Postres',
    'bebida'       => 'Bebidas',
    'snack'        => 'Snacks',
    'otro'         => 'Otros',
];

$catIcons = [
    'entrada'      => ['icon' => 'fa-utensils',       'color' => '#e67e22'],
    'plato_fuerte' => ['icon' => 'fa-drumstick-bite', 'color' => '#e74c3c'],
    'postre'       => ['icon' => 'fa-ice-cream',      'color' => '#9b59b6'],
    'bebida'       => ['icon' => 'fa-wine-glass',     'color' => '#3498db'],
    'snack'        => ['icon' => 'fa-cookie-bite',    'color' => '#27ae60'],
    'otro'         => ['icon' => 'fa-bowl-food',      'color' => '#7f8c8d'],
];

// Agrupar recetas por categoría
$porCategoria = [];
foreach ($recetas as $r) {
    $cat = $r['categoria'] ?? 'otro';
    $porCategoria[$cat][] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido a domicilio — <?php echo $nombreComercio; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f0f2f5; color: #2c3e50; }

    /* ── Header sticky wrapper ── */
    .ped-header-sticky { position: sticky; top: 0; z-index: 200; }

    /* ── Top bar ── */
    .ped-topbar { background: #1a1d27; color: #fff; padding: 14px 20px; display: flex; align-items: center; gap: 14px; }
    .ped-logo   { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; }
    .ped-logo-icon { width: 38px; height: 38px; background: #2980b9; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .ped-biz-name { font-size: 17px; font-weight: 800; }
    .ped-biz-sub  { font-size: 12px; opacity: .6; }
    .ped-hamburger { margin-left: auto; background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 6px 8px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
    .ped-hamburger:hover { background: rgba(255,255,255,.12); }

    /* ── Search bar ── */
    .ped-searchbar { background: #fff; padding: 10px 16px; border-bottom: 1px solid #e8ecf0; }
    .ped-search-inner { display: flex; align-items: center; gap: 10px; background: #f0f2f5; border-radius: 10px; padding: 9px 14px; }
    .ped-search-inner > i { color: #95a5a6; font-size: 14px; flex-shrink: 0; }
    .ped-search-input { border: none; background: none; outline: none; font-size: 14px; color: #2c3e50; flex: 1; font-family: inherit; min-width: 0; }
    .ped-search-input::placeholder { color: #b2bec3; }
    .ped-search-clear { background: none; border: none; cursor: pointer; font-size: 14px; color: #95a5a6; padding: 2px; display: none; line-height: 1; }
    .ped-search-clear.show { display: block; }

    /* ── Sidenav ── */
    .ped-nav-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 600; }
    .ped-nav-overlay.show { display: block; }
    .ped-sidenav { position: fixed; top: 0; right: 0; bottom: 0; width: 55%; max-width: 280px; min-width: 200px; background: #fff; z-index: 601; transform: translateX(100%); transition: transform .3s cubic-bezier(.4,0,.2,1); display: flex; flex-direction: column; box-shadow: -8px 0 32px rgba(0,0,0,.18); }
    .ped-sidenav.show { transform: translateX(0); }
    .ped-sidenav-head { display: flex; justify-content: space-between; align-items: center; padding: 18px 16px 14px; border-bottom: 1px solid #f0f2f5; flex-shrink: 0; }
    .ped-sidenav-head span { font-size: 15px; font-weight: 800; color: #2c3e50; }
    .ped-sidenav-close { background: none; border: none; cursor: pointer; font-size: 18px; color: #95a5a6; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: background .1s; }
    .ped-sidenav-close:hover { background: #f0f2f5; color: #2c3e50; }
    .ped-sidenav-body { flex: 1; overflow-y: auto; padding: 8px 0; }
    .ped-nav-cat { width: 100%; display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: none; border: none; cursor: pointer; text-align: left; font-size: 14px; font-weight: 600; color: #2c3e50; transition: background .1s; }
    .ped-nav-cat:hover { background: #f8f9fa; }
    .ped-nav-cat-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .ped-nav-cat-count { margin-left: auto; font-size: 11px; color: #95a5a6; font-weight: 700; background: #f0f2f5; padding: 2px 8px; border-radius: 20px; }

    /* ── Cart FAB ── */
    .ped-cart-fab { position: fixed; bottom: 24px; right: 20px; z-index: 300; background: #27ae60; color: #fff; border: none; border-radius: 50px; padding: 14px 20px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 10px; box-shadow: 0 6px 24px rgba(39,174,96,.4); transition: .15s; }
    .ped-cart-fab:hover { background: #219a52; }
    .ped-cart-fab.hidden { display: none; }
    .ped-cart-count { background: #fff; color: #27ae60; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }

    /* ── Menú ── */
    .ped-content { max-width: 720px; margin: 0 auto; padding: 20px 16px 100px; }

    .ped-cat-title { font-size: 14px; font-weight: 800; margin: 24px 0 10px; display: flex; align-items: center; gap: 8px; }
    .ped-cat-title::before { content: ''; display: block; width: 4px; height: 16px; background: currentColor; border-radius: 2px; }

    /* ── Grid de tarjetas ── */
    .ped-menu-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 4px; }
    @media (min-width: 500px) { .ped-menu-grid { grid-template-columns: repeat(3, 1fr); } }

    .ped-item-card { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.07); display: flex; flex-direction: column; transition: box-shadow .15s; }
    .ped-item-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.13); }

    .ped-item-thumb { position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; flex-shrink: 0; }
    .ped-item-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .ped-item-icon-bg { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 42px; color: rgba(255,255,255,.9); }

    .ped-item-body { padding: 10px 10px 4px; flex: 1; }
    .ped-item-name  { font-size: 12px; font-weight: 700; color: #2c3e50; line-height: 1.35; }
    .ped-item-price { font-size: 14px; font-weight: 800; color: #27ae60; margin-top: 4px; }
    .ped-stock-badge { font-size: 10px; font-weight: 700; margin-top: 3px; display: inline-block; }
    .ped-stock-badge.low  { color: #e74c3c; }
    .ped-stock-badge.warn { color: #e67e22; }

    .ped-item-foot { padding: 0 8px 10px; }
    .ped-btn-add-full { width: 100%; background: #27ae60; color: #fff; border: none; border-radius: 8px; height: 34px; font-size: 17px; cursor: pointer; transition: background .15s; display: flex; align-items: center; justify-content: center; }
    .ped-btn-add-full:hover:not(:disabled) { background: #219a52; }
    .ped-btn-add-full:disabled { opacity: .35; cursor: default; }
    .ped-item-ctrl { display: flex; align-items: center; gap: 4px; }
    .ped-qty-btn   { width: 30px; height: 30px; border-radius: 50%; border: 2px solid #e8ecf0; background: #fff; cursor: pointer; font-size: 15px; font-weight: 700; display: flex; align-items: center; justify-content: center; color: #2c3e50; transition: .15s; flex-shrink: 0; }
    .ped-qty-btn.add  { background: #27ae60; border-color: #27ae60; color: #fff; }
    .ped-qty-btn.add:hover:not(:disabled) { background: #219a52; }
    .ped-qty-btn.sub:hover  { border-color: #e74c3c; color: #e74c3c; }
    .ped-qty-num   { font-size: 15px; font-weight: 700; flex: 1; text-align: center; }

    /* ── Drawer carrito ── */
    .ped-drawer-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 400; }
    .ped-drawer-bg.show { display: block; }
    .ped-drawer { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-radius: 20px 20px 0 0; max-height: 85vh; overflow-y: auto; z-index: 401; padding: 20px; transform: translateY(100%); transition: transform .3s ease; }
    .ped-drawer-bg.show .ped-drawer { transform: translateY(0); }
    .ped-drawer-handle { width: 40px; height: 4px; background: #e8ecf0; border-radius: 2px; margin: 0 auto 16px; }
    .ped-drawer-title  { font-size: 17px; font-weight: 800; margin-bottom: 16px; }
    .ped-cart-item     { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f0f2f5; }
    .ped-ci-name { flex: 1; font-size: 14px; font-weight: 600; }
    .ped-ci-price { font-size: 13px; color: #27ae60; font-weight: 700; }
    .ped-cart-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; padding: 14px 0 0; }
    .ped-checkout-btn { width: 100%; background: #27ae60; color: #fff; border: none; border-radius: 12px; padding: 14px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 14px; transition: .15s; }
    .ped-checkout-btn:hover { background: #219a52; }

    /* ── Formulario ── */
    .ped-form-section { display: none; }
    .ped-form-section.show { display: block; }
    .ped-form-title { font-size: 17px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .ped-form-group { margin-bottom: 14px; }
    .ped-form-group label { font-size: 12px; font-weight: 700; color: #636e72; display: block; margin-bottom: 5px; }
    .ped-form-group input, .ped-form-group textarea {
        width: 100%; border: 1.5px solid #e8ecf0; border-radius: 10px;
        padding: 11px 14px; font-size: 14px; color: #2c3e50; outline: none;
        font-family: inherit; resize: none;
    }
    .ped-form-group input:focus, .ped-form-group textarea:focus { border-color: #27ae60; }
    .ped-submit-btn { width: 100%; background: #27ae60; color: #fff; border: none; border-radius: 12px; padding: 15px; font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: .15s; margin-top: 6px; }
    .ped-submit-btn:hover:not(:disabled) { background: #219a52; }
    .ped-submit-btn:disabled { opacity: .6; cursor: default; }
    .ped-back-link { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #95a5a6; cursor: pointer; margin-bottom: 16px; }
    .ped-back-link:hover { color: #2c3e50; }

    /* ── Tracking ── */
    .ped-tracking { display: none; }
    .ped-tracking.show { display: block; }
    .ped-track-header { background: #fff; border-radius: 14px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .ped-track-num    { font-size: 13px; color: #95a5a6; }
    .ped-track-name   { font-size: 17px; font-weight: 800; margin: 4px 0; }
    .ped-track-status { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; padding: 5px 14px; border-radius: 20px; }

    .ped-timeline { background: #fff; border-radius: 14px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .ped-timeline-title { font-size: 14px; font-weight: 800; margin-bottom: 16px; color: #2c3e50; }
    .ped-step { display: flex; gap: 14px; position: relative; padding-bottom: 24px; }
    .ped-step:last-child { padding-bottom: 0; }
    .ped-step-line { position: absolute; left: 15px; top: 30px; bottom: 0; width: 2px; background: #e8ecf0; }
    .ped-step:last-child .ped-step-line { display: none; }
    .ped-step-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; z-index: 1; }
    .ped-step.done   .ped-step-icon { background: #27ae60; color: #fff; }
    .ped-step.done   .ped-step-line  { background: #27ae60; }
    .ped-step.active .ped-step-icon { background: #2980b9; color: #fff; animation: pulse-step 1.5s infinite; }
    .ped-step.pending .ped-step-icon { background: #f0f2f5; color: #b2bec3; }
    @keyframes pulse-step { 0%,100%{box-shadow:0 0 0 0 rgba(41,128,185,.4)} 50%{box-shadow:0 0 0 8px rgba(41,128,185,0)} }
    .ped-step-info { padding-top: 6px; }
    .ped-step-label { font-size: 14px; font-weight: 700; color: #2c3e50; }
    .ped-step.pending .ped-step-label { color: #b2bec3; }
    .ped-step-sub   { font-size: 12px; color: #95a5a6; margin-top: 2px; }

    .ped-track-items { background: #fff; border-radius: 14px; padding: 16px 20px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .ped-track-items-title { font-size: 13px; font-weight: 800; color: #636e72; margin-bottom: 10px; }
    .ped-track-item { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; border-bottom: 1px solid #f0f2f5; }
    .ped-track-item:last-child { border-bottom: none; }
    .ped-track-total { display: flex; justify-content: space-between; font-size: 15px; font-weight: 800; margin-top: 10px; }
    .ped-new-order-btn { width: 100%; margin-top: 16px; background: #f0f2f5; color: #636e72; border: none; border-radius: 12px; padding: 14px; font-size: 14px; font-weight: 700; cursor: pointer; }
    .ped-new-order-btn:hover { background: #e8ecf0; }

    /* Selector tipo pedido */
    .ped-tipo-wrap { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
    .ped-tipo-card { border: 2px solid #e8ecf0; border-radius: 14px; padding: 16px 12px; text-align: center; cursor: pointer; transition: .15s; display: flex; flex-direction: column; align-items: center; gap: 6px; background: #fff; }
    .ped-tipo-card i    { font-size: 26px; color: #b2bec3; transition: .15s; }
    .ped-tipo-card span { font-size: 15px; font-weight: 700; color: #7f8c8d; }
    .ped-tipo-card small{ font-size: 11px; color: #b2bec3; }
    .ped-tipo-card.active { border-color: #27ae60; background: #f0fdf4; }
    .ped-tipo-card.active i    { color: #27ae60; }
    .ped-tipo-card.active span { color: #2c3e50; }
    .ped-tipo-card.active small{ color: #27ae60; }
    .ped-tipo-card:hover:not(.active) { border-color: #bdc3c7; background: #fafafa; }

    /* Empty / Cerrado */
    .ped-menu-empty { text-align: center; padding: 60px 20px; color: #b2bec3; }
    .ped-menu-empty i { font-size: 48px; display: block; margin-bottom: 14px; }
    .ped-cerrado { text-align: center; padding: 60px 20px; }
    .ped-cerrado-icon { font-size: 52px; color: #e74c3c; margin-bottom: 16px; }
    .ped-cerrado h2 { font-size: 20px; font-weight: 800; color: #2c3e50; margin: 0 0 8px; }
    .ped-cerrado p  { font-size: 14px; color: #95a5a6; margin: 0; }
    .ped-horario-badge { display: inline-flex; align-items: center; gap: 8px; background: #eaf4fb; color: #2980b9; border-radius: 20px; padding: 8px 18px; font-size: 14px; font-weight: 700; margin-top: 16px; }
    /* Agotado */
    .ped-item-card.agotado .ped-item-thumb { filter: grayscale(.5); }
    .ped-stock-badge.agotado { color: #e74c3c; font-weight: 800; }

    /* ── Chat ── */
    .ped-chat-fab { position: fixed; bottom: 24px; left: 20px; z-index: 300; background: #2980b9; color: #fff; border: none; border-radius: 50%; width: 52px; height: 52px; font-size: 20px; cursor: pointer; display: none; align-items: center; justify-content: center; box-shadow: 0 6px 24px rgba(41,128,185,.4); transition: .15s; }
    .ped-chat-fab.show { display: flex; }
    .ped-chat-fab:hover { background: #2471a3; }
    .ped-chat-nr { position: absolute; top: -4px; right: -4px; background: #e74c3c; color: #fff; border-radius: 50%; width: 18px; height: 18px; display: none; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; }
    .ped-chat-nr.show { display: flex; }
    .ped-chat-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 490; }
    .ped-chat-overlay.show { display: block; }
    .ped-chat-drawer {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: #fff; border-radius: 20px 20px 0 0;
        height: 70vh; height: 70dvh;
        z-index: 500; transform: translateY(100%);
        transition: transform .3s ease;
        display: flex; flex-direction: column;
        box-shadow: 0 -8px 30px rgba(0,0,0,.15);
    }
    .ped-chat-drawer.show { transform: translateY(0); }
    @media (max-width: 600px) {
        .ped-chat-drawer { height: 90vh; height: 90dvh; border-radius: 14px 14px 0 0; }
    }
    .ped-chat-dhead { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f0f2f5; flex-shrink: 0; }
    .ped-chat-dhead h3 { margin: 0; font-size: 15px; font-weight: 800; color: #2c3e50; display: flex; align-items: center; gap: 8px; }
    .ped-chat-dhead p { margin: 2px 0 0; font-size: 12px; color: #95a5a6; }
    .ped-chat-close { background: none; border: none; cursor: pointer; font-size: 18px; color: #95a5a6; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
    .ped-chat-close:hover { background: #f0f2f5; }
    .ped-chat-msgs { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; -webkit-overflow-scrolling: touch; }
    .ped-chat-input-wrap { display: flex; gap: 8px; padding: 12px 16px; border-top: 1px solid #f0f2f5; flex-shrink: 0; }
    .ped-chat-input { flex: 1; border: 1.5px solid #e8ecf0; border-radius: 10px; padding: 10px 14px; font-size: 16px; outline: none; font-family: inherit; }
    .ped-chat-input:focus { border-color: #2980b9; }
    .ped-chat-send { background: #2980b9; color: #fff; border: none; border-radius: 10px; width: 44px; height: 44px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .ped-chat-send:hover { background: #2471a3; }
    .ped-chat-msg { display: flex; flex-direction: column; }
    .ped-chat-msg.admin { align-items: flex-start; }
    .ped-chat-msg.cliente { align-items: flex-end; }
    .ped-chat-bubble { max-width: 80%; padding: 10px 14px; border-radius: 16px; font-size: 14px; line-height: 1.4; word-break: break-word; }
    .ped-chat-msg.admin .ped-chat-bubble { background: #f0f2f5; color: #2c3e50; border-bottom-left-radius: 4px; }
    .ped-chat-msg.cliente .ped-chat-bubble { background: #27ae60; color: #fff; border-bottom-right-radius: 4px; }
    .ped-chat-time { font-size: 11px; color: #b2bec3; margin-top: 3px; display: flex; align-items: center; gap: 3px; }
    .ped-chat-receipt { font-size: 11px; display: inline-flex; align-items: center; gap: 2px; }
    .ped-chat-receipt.sent { color: #b2bec3; }
    .ped-chat-receipt.read { color: #34b7f1; }
    .ped-chat-empty { text-align: center; color: #b2bec3; font-size: 13px; padding: 40px 20px; }
    </style>
</head>
<body>

<div class="ped-header-sticky">
    <div class="ped-topbar">
        <?php if ($logoComercio): ?>
        <img src="<?php echo $logoComercio; ?>" class="ped-logo" alt="logo">
        <?php else: ?>
        <div class="ped-logo-icon"><i class="fas fa-utensils"></i></div>
        <?php endif; ?>
        <div>
            <div class="ped-biz-name"><?php echo $nombreComercio; ?></div>
            <div class="ped-biz-sub">Pedidos a domicilio</div>
        </div>
        <button class="ped-hamburger" id="hamburgerBtn" onclick="abrirNav()" aria-label="Categorías">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <div class="ped-searchbar" id="pedSearchbar">
        <div class="ped-search-inner">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" class="ped-search-input" id="searchInput"
                   placeholder="Buscar plato…" autocomplete="off"
                   oninput="buscarReceta(this.value)">
            <button class="ped-search-clear" id="searchClear" onclick="limpiarBusqueda()" aria-label="Limpiar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    </div>
</div>

<!-- Vista: Menú -->
<div class="ped-content" id="viewMenu">
    <?php if ($linkCerrado ?? false): ?>
    <div class="ped-cerrado">
        <div class="ped-cerrado-icon"><i class="fas fa-store-slash"></i></div>
        <h2>Estamos cerrados</h2>
        <p>En este momento no estamos recibiendo pedidos.</p>
        <?php if (!empty($horarioDesde) && !empty($horarioHasta)): ?>
        <div class="ped-horario-badge">
            <i class="fas fa-clock"></i>
            Horario: <?php echo htmlspecialchars($horarioDesde); ?> – <?php echo htmlspecialchars($horarioHasta); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif (empty($recetas)): ?>
    <div class="ped-menu-empty">
        <i class="fas fa-utensils"></i>
        <p>El menú no está disponible en este momento.</p>
    </div>
    <?php else: ?>
    <?php foreach ($porCategoria as $cat => $items):
        $ci = $catIcons[$cat] ?? $catIcons['otro'];
    ?>
    <div class="ped-cat-title" id="cat-<?php echo $cat; ?>" style="color:<?php echo $ci['color']; ?>">
        <i class="fas <?php echo $ci['icon']; ?>"></i>
        <span><?php echo $categoriasLabel[$cat] ?? ucfirst($cat); ?></span>
    </div>
    <div class="ped-menu-grid">
    <?php foreach ($items as $r):
        $fotoRaw = $r['foto'] ?? null;
        $fp      = is_string($fotoRaw) ? json_decode($fotoRaw, true) : null;
        $foto    = is_array($fp) ? ($fp[0] ?? null) : ($fotoRaw ?: null);
        $ud      = $r['unidades_disponibles'] ?? null;
        $udN     = ($ud !== null) ? max(0, (int)$ud) : null;
    ?>
    <div class="ped-item-card <?php echo ($udN !== null && $udN === 0) ? 'agotado' : ''; ?>" id="icard-<?php echo $r['id']; ?>">
        <div class="ped-item-thumb" style="background:<?php echo $foto ? '#e8ecf0' : $ci['color']; ?>">
            <?php if ($foto): ?>
            <img src="<?php echo htmlspecialchars($foto); ?>" class="ped-item-img" alt=""
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="ped-item-icon-bg" style="display:none;background:<?php echo $ci['color']; ?>">
                <i class="fas <?php echo $ci['icon']; ?>"></i>
            </div>
            <?php else: ?>
            <div class="ped-item-icon-bg">
                <i class="fas <?php echo $ci['icon']; ?>"></i>
            </div>
            <?php endif; ?>
        </div>
        <div class="ped-item-body">
            <div class="ped-item-name"><?php echo htmlspecialchars($r['nombre']); ?></div>
            <div class="ped-item-price">$<?php echo number_format((float)$r['precio'], 0, ',', '.'); ?></div>
            <?php if ($udN !== null && $udN > 0 && $udN <= 10): ?>
            <div class="ped-stock-badge <?php echo $udN <= 3 ? 'low' : 'warn'; ?>">
                Quedan <?php echo $udN; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="ped-item-foot">
            <button class="ped-btn-add-full" id="add-<?php echo $r['id']; ?>"
                    onclick="cambiarCantidad(<?php echo $r['id']; ?>, 1)"
                    <?php echo ($udN !== null && $udN === 0) ? 'disabled style="opacity:.35;cursor:default"' : ''; ?>>
                <i class="fas fa-plus"></i>
            </button>
            <div class="ped-item-ctrl" id="ctrl-<?php echo $r['id']; ?>" style="display:none">
                <button class="ped-qty-btn sub" id="sub-<?php echo $r['id']; ?>"
                        onclick="cambiarCantidad(<?php echo $r['id']; ?>, -1)">−</button>
                <span class="ped-qty-num" id="qty-<?php echo $r['id']; ?>">0</span>
                <button class="ped-qty-btn add" id="inc-<?php echo $r['id']; ?>"
                        onclick="cambiarCantidad(<?php echo $r['id']; ?>, 1)">+</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Vista: Formulario -->
<div class="ped-content ped-form-section" id="viewForm">
    <div class="ped-back-link" onclick="mostrarMenu()">
        <i class="fas fa-arrow-left"></i> Volver al menú
    </div>

    <!-- Selector de tipo de pedido -->
    <div class="ped-tipo-wrap">
        <div class="ped-tipo-card active" id="tipoDomicilio" onclick="selTipo('domicilio')">
            <i class="fas fa-motorcycle"></i>
            <span>Domicilio</span>
            <small>Te lo llevamos</small>
        </div>
        <div class="ped-tipo-card" id="tipoRecoger" onclick="selTipo('recoger')">
            <i class="fas fa-store"></i>
            <span>Recoger</span>
            <small>Lo recojo en el local</small>
        </div>
    </div>

    <div class="ped-form-title" id="formTitle"><i class="fas fa-motorcycle"></i> Datos del domicilio</div>

    <div class="ped-form-group">
        <label>Nombre completo <span style="color:#e74c3c">*</span></label>
        <input type="text" id="fNombre" placeholder="¿A nombre de quién?" autocomplete="name">
    </div>
    <div class="ped-form-group">
        <label>Teléfono</label>
        <input type="tel" id="fTelefono" placeholder="Tu número de contacto" autocomplete="tel">
    </div>
    <div class="ped-form-group" id="grupoDireccion">
        <label>Dirección de entrega <span style="color:#e74c3c">*</span></label>
        <input type="text" id="fDireccion" placeholder="Calle, número, barrio…" autocomplete="street-address">
    </div>
    <div class="ped-form-group">
        <label>Notas adicionales</label>
        <textarea id="fNotas" rows="3" placeholder="Instrucciones especiales, alergias…"></textarea>
    </div>

    <!-- Toggle cupón -->
    <a id="pedToggleCupon" href="#" onclick="event.preventDefault();togglePedCupon()"
       style="display:block;font-size:13px;color:#95a5a6;text-decoration:none;margin:-4px 0 12px">
        <i class="fas fa-tag" id="pedIconCupon"></i> Agregar cupón
    </a>
    <div id="pedCuponWrap" class="ped-form-group" style="display:none">
        <div style="display:flex;gap:8px">
            <input type="text" id="fCupon" placeholder="Ingrese cupón de descuento"
                   style="text-transform:uppercase;letter-spacing:2px;font-family:monospace"
                   maxlength="8" oninput="this.value=this.value.toUpperCase()">
            <button type="button" id="btnCupon" onclick="aplicarCupon()"
                    style="flex-shrink:0;background:#636e72;color:#fff;border:none;border-radius:10px;padding:0 16px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">
                Aplicar
            </button>
        </div>
        <div id="cuponMsg" style="font-size:12px;margin-top:5px"></div>
    </div>

    <!-- Resumen del pedido -->
    <div style="background:#f8f9fa;border-radius:12px;padding:14px;margin-bottom:14px" id="formResumen"></div>

    <button class="ped-submit-btn" id="btnSubmit" onclick="confirmarPedido()">
        <i class="fas fa-paper-plane"></i> Confirmar pedido
    </button>
</div>

<!-- Vista: Tracking -->
<div class="ped-content ped-tracking" id="viewTracking">
    <div class="ped-track-header">
        <div class="ped-track-num" id="trackNum"></div>
        <div class="ped-track-name" id="trackName"></div>
        <div class="ped-track-status" id="trackBadge"></div>
    </div>

    <div class="ped-timeline">
        <div class="ped-timeline-title">Estado de tu pedido</div>
        <div id="timelineSteps"></div>
    </div>

    <div class="ped-track-items">
        <div class="ped-track-items-title">TU PEDIDO</div>
        <div id="trackItems"></div>
        <div id="trackValorDom" style="display:none;justify-content:space-between;font-size:13px;font-weight:600;color:#16a085;margin-top:6px;padding:6px 0;border-top:1px dashed #e0e0e0;">
            <span>🛵 Domicilio</span>
            <span id="trackValorDomAmt"></span>
        </div>
        <div class="ped-track-total">
            <span>Total pedido</span>
            <span id="trackTotal"></span>
        </div>
    </div>

    <div id="pedidoFinalMsg"></div>
</div>

<!-- FAB del carrito -->
<button class="ped-cart-fab hidden" id="cartFab" onclick="abrirCarrito()">
    <i class="fas fa-shopping-cart"></i>
    <span id="cartFabTotal">$0</span>
    <span class="ped-cart-count" id="cartFabCount">0</span>
</button>

<!-- Drawer carrito -->
<div class="ped-drawer-bg" id="cartDrawerBg" onclick="cerrarCarrito()">
    <div class="ped-drawer" onclick="event.stopPropagation()">
        <div class="ped-drawer-handle"></div>
        <div class="ped-drawer-title"><i class="fas fa-shopping-cart"></i> Tu pedido</div>
        <div id="cartItems"></div>
        <div class="ped-cart-total">
            <span>Total</span>
            <span id="cartTotal">$0</span>
        </div>
        <button class="ped-checkout-btn" onclick="irAFormulario()">
            <i class="fas fa-motorcycle"></i> Pedir a domicilio
        </button>
    </div>
</div>

<!-- Chat FAB -->
<button class="ped-chat-fab" id="chatFab" onclick="abrirChat()">
    <i class="fas fa-comment-dots"></i>
    <span class="ped-chat-nr" id="chatNr"></span>
</button>

<!-- Chat drawer -->
<div class="ped-chat-overlay" id="chatOverlay" onclick="cerrarChat()"></div>
<div class="ped-chat-drawer" id="chatDrawer">
    <div class="ped-chat-dhead">
        <div>
            <h3><i class="fas fa-comment-dots" style="color:#2980b9"></i> Chat con el restaurante</h3>
            <p>Consulta o informa algo sobre tu pedido</p>
        </div>
        <button class="ped-chat-close" onclick="cerrarChat()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="ped-chat-msgs" id="chatMsgs">
        <div class="ped-chat-empty" id="chatEmpty">Sin mensajes aún.<br>Escríbenos si tienes alguna consulta.</div>
    </div>
    <div class="ped-chat-input-wrap">
        <input type="text" class="ped-chat-input" id="chatInput" placeholder="Escribe tu mensaje…"
               onkeydown="if(event.key==='Enter')enviarMensajeCliente()">
        <button class="ped-chat-send" onclick="enviarMensajeCliente()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
const BASE        = '<?php echo $basePath; ?>';
const LINK_TOKEN  = '<?php echo htmlspecialchars($link['token']); ?>';
const STORAGE_KEY = 'dom_pedido_' + LINK_TOKEN;
const COMERCIO_NOMBRE = '<?php echo htmlspecialchars($comercio['nombre'] ?? 'CHEFCONTROL', ENT_QUOTES); ?>';
const COMERCIO_TEL    = '<?php echo htmlspecialchars($comercio['telefono'] ?? '', ENT_QUOTES); ?>';

let ticketDescargado = false;

const RECETAS = <?php echo json_encode(array_values(array_map(fn($r) => [
    'id'       => (int)$r['id'],
    'nombre'   => $r['nombre'],
    'precio'   => (float)$r['precio'],
    'categoria'=> $r['categoria'],
    'stock'    => isset($r['unidades_disponibles']) && $r['unidades_disponibles'] !== null
                  ? (int)$r['unidades_disponibles'] : null,
], $recetas))); ?>;

// ── Estado ────────────────────────────────────────────────────────────────────
const cart = {};  // { receta_id: cantidad }
let cuponAplicado    = null; // { id, tipo, descuento, nombre }
let tipoSeleccionado = 'domicilio';
let trackingToken    = null;
let trackingInterval = null;
let chatDesdeId      = 0;
let chatInterval     = null;
let chatAbierto      = false;
let _chatScrollY     = 0;   // scroll del body al abrir el chat
const msgMap = new Map();   // msgId → { div, leido, de }

// ── Selector tipo ─────────────────────────────────────────────────────────────
function selTipo(tipo) {
    tipoSeleccionado = tipo;
    document.getElementById('tipoDomicilio').classList.toggle('active', tipo === 'domicilio');
    document.getElementById('tipoRecoger').classList.toggle('active',   tipo === 'recoger');

    const grupDir   = document.getElementById('grupoDireccion');
    const formTitle = document.getElementById('formTitle');

    if (tipo === 'recoger') {
        grupDir.style.display   = 'none';
        formTitle.innerHTML     = '<i class="fas fa-store"></i> Datos para recoger';
    } else {
        grupDir.style.display   = 'block';
        formTitle.innerHTML     = '<i class="fas fa-motorcycle"></i> Datos del domicilio';
    }
}

// ── Init ──────────────────────────────────────────────────────────────────────
(function init() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        trackingToken = stored;
        mostrarTracking(trackingToken);
    }
})();

// ── Carrito ───────────────────────────────────────────────────────────────────
function cambiarCantidad(id, delta) {
    const receta   = RECETAS.find(r => r.id == id);
    const maxStock = receta ? receta.stock : null;
    const actual   = cart[id] || 0;
    let nuevo      = Math.max(0, actual + delta);
    if (maxStock !== null && nuevo > maxStock) nuevo = maxStock;
    if (nuevo === 0) {
        delete cart[id];
    } else {
        cart[id] = nuevo;
    }
    actualizarUI(id);
    actualizarFAB();
}

function actualizarUI(id) {
    const qty      = cart[id] || 0;
    const receta   = RECETAS.find(r => r.id == id);
    const maxStock = receta ? receta.stock : null;
    const enMax    = maxStock !== null && qty >= maxStock;

    const addFull = document.getElementById('add-'  + id);
    const ctrl    = document.getElementById('ctrl-' + id);
    const qtyEl   = document.getElementById('qty-'  + id);
    const incBtn  = document.getElementById('inc-'  + id);

    if (addFull) {
        addFull.style.display = qty === 0 ? '' : 'none';
        addFull.disabled      = enMax;
        addFull.style.opacity = enMax ? '.35' : '';
        addFull.style.cursor  = enMax ? 'default' : '';
    }
    if (ctrl)   ctrl.style.display = qty > 0 ? 'flex' : 'none';
    if (qtyEl)  qtyEl.textContent  = qty;
    if (incBtn) {
        incBtn.disabled      = enMax;
        incBtn.style.opacity = enMax ? '.35' : '';
        incBtn.style.cursor  = enMax ? 'default' : '';
    }
}

function actualizarFAB() {
    const count = Object.values(cart).reduce((a, b) => a + b, 0);
    const total = calcTotal();
    const fab   = document.getElementById('cartFab');
    fab.classList.toggle('hidden', count === 0);
    document.getElementById('cartFabCount').textContent = count;
    document.getElementById('cartFabTotal').textContent = '$' + total.toLocaleString();
}

function calcTotal() {
    return Object.entries(cart).reduce((sum, [id, qty]) => {
        const r = RECETAS.find(r => r.id == id);
        return sum + (r ? r.precio * qty : 0);
    }, 0);
}

function calcDescuento() {
    if (!cuponAplicado) return 0;
    const base = calcTotal();
    if (cuponAplicado.tipo === 'porcentaje') return base * cuponAplicado.descuento / 100;
    if (cuponAplicado.tipo === 'producto' && cuponAplicado.id_receta) {
        let subtotalProd = 0;
        Object.entries(cart).forEach(([id, qty]) => {
            if (parseInt(id) === cuponAplicado.id_receta) {
                const rec = RECETAS.find(r => r.id === parseInt(id));
                if (rec) subtotalProd += rec.precio * qty;
            }
        });
        return Math.min(subtotalProd * cuponAplicado.descuento / 100, base);
    }
    return Math.min(cuponAplicado.descuento, base);
}

function calcTotalFinal() {
    return Math.max(0, calcTotal() - calcDescuento());
}

function abrirCarrito() {
    renderCarrito();
    document.getElementById('cartDrawerBg').classList.add('show');
}

function cerrarCarrito() {
    document.getElementById('cartDrawerBg').classList.remove('show');
}

function renderCarrito() {
    const items = Object.entries(cart).map(([id, qty]) => {
        const r = RECETAS.find(r => r.id == id);
        if (!r) return '';
        const atMax = r.stock !== null && qty >= r.stock;
        return `<div class="ped-cart-item">
            <div class="ped-ci-name">${esc(r.nombre)} <span style="color:#95a5a6">×${qty}</span></div>
            <div class="ped-ci-ctrl">
                <button class="ped-qty-btn sub" onclick="cambiarCantidad(${r.id},-1);renderCarrito()">−</button>
                <span class="ped-qty-num">${qty}</span>
                <button class="ped-qty-btn add" onclick="cambiarCantidad(${r.id},1);renderCarrito()"
                        ${atMax ? 'disabled style="opacity:.35;cursor:default"' : ''}>+</button>
            </div>
            <div class="ped-ci-price">$${(r.precio * qty).toLocaleString()}</div>
        </div>`;
    }).join('');
    document.getElementById('cartItems').innerHTML = items;
    document.getElementById('cartTotal').textContent = '$' + calcTotal().toLocaleString();
}

// ── Formulario ────────────────────────────────────────────────────────────────
function irAFormulario() {
    cerrarCarrito();
    actualizarResumenConCupon();
    mostrarVista('viewForm');
}

function actualizarResumenConCupon() {
    const resumen = Object.entries(cart).map(([id, qty]) => {
        const r = RECETAS.find(r => r.id == id);
        return r ? `<div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0">
            <span>${esc(r.nombre)} ×${qty}</span>
            <strong>$${(r.precio * qty).toLocaleString()}</strong>
        </div>` : '';
    }).join('');

    let descuentoHtml = '';
    if (cuponAplicado) {
        const desc = calcDescuento();
        descuentoHtml = `<div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0;color:#27ae60">
            <span><i class="fas fa-tag"></i> Descuento cupón</span>
            <strong>−$${desc.toLocaleString()}</strong>
        </div>`;
    }

    document.getElementById('formResumen').innerHTML =
        resumen +
        `<div style="border-top:1px solid #e8ecf0;margin-top:8px;padding-top:8px">` +
        descuentoHtml +
        `<div style="display:flex;justify-content:space-between;font-size:15px;font-weight:800;margin-top:4px">
            <span>Total</span><span style="color:#27ae60">$${calcTotalFinal().toLocaleString()}</span>
        </div></div>`;
}

function mostrarMenu() { mostrarVista('viewMenu'); }

async function confirmarPedido() {
    const nombre    = document.getElementById('fNombre').value.trim();
    const telefono  = document.getElementById('fTelefono').value.trim();
    const direccion = document.getElementById('fDireccion').value.trim();
    const notas     = document.getElementById('fNotas').value.trim();

    if (!nombre) { alert('El nombre es obligatorio'); document.getElementById('fNombre').focus(); return; }
    if (tipoSeleccionado === 'domicilio' && !direccion) {
        alert('La dirección es obligatoria para domicilio');
        document.getElementById('fDireccion').focus();
        return;
    }

    const items = Object.entries(cart).map(([id, qty]) => {
        const r = RECETAS.find(r => r.id == id);
        return r ? { receta_id: r.id, nombre: r.nombre, precio: r.precio, cantidad: qty } : null;
    }).filter(Boolean);

    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando…';

    try {
        const res = await fetch(BASE + '/pedido/' + LINK_TOKEN + '/hacer-pedido', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre, telefono, direccion, notas, tipo: tipoSeleccionado, items,
                total: calcTotalFinal(),
                cupon_codigo: cuponAplicado ? document.getElementById('fCupon').value.trim() : '',
            }),
        });
        const d = await res.json();
        if (d.success) {
            localStorage.setItem(STORAGE_KEY, d.token_pedido);
            trackingToken = d.token_pedido;
            mostrarTracking(d.token_pedido);
        } else {
            alert(d.message || 'Error al enviar el pedido');
        }
    } catch {
        alert('Error de conexión. Intenta de nuevo.');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Confirmar pedido';
}

// ── Tracking ──────────────────────────────────────────────────────────────────
const STEPS_DOMICILIO = [
    { estado: 'pendiente',   label: 'En aprobación',            sub: 'Tu pedido fue recibido',                           icon: 'fa-clock',      color: '#e67e22' },
    { estado: '_valor',      label: 'Valor del domicilio',       sub: 'El restaurante está calculando el costo de envío', icon: 'fa-calculator', color: '#16a085' },
    { estado: 'preparacion', label: 'En preparación',            sub: 'La cocina está preparando tu pedido',              icon: 'fa-fire',       color: '#2980b9' },
    { estado: 'listo',       label: 'Esperando al domiciliario', sub: 'Listo para ser recogido por el repartidor',        icon: 'fa-box-open',   color: '#8e44ad' },
    { estado: 'en_camino',   label: 'En camino',                 sub: '¡Tu pedido viene en camino!',                     icon: 'fa-motorcycle', color: '#27ae60' },
];
const STEPS_RECOGER = [
    { estado: 'pendiente',   label: 'En aprobación',   sub: 'Tu pedido fue recibido',              icon: 'fa-clock',        color: '#e67e22' },
    { estado: 'preparacion', label: 'En preparación',   sub: 'La cocina está preparando tu pedido', icon: 'fa-fire',         color: '#2980b9' },
    { estado: 'listo',       label: 'Listo para recoger', sub: '¡Tu pedido está listo! Puedes pasar a recogerlo', icon: 'fa-store', color: '#27ae60' },
];

let lastEstado = null;

function mostrarTracking(token) {
    mostrarVista('viewTracking');
    cargarEstado(token);
    if (!trackingInterval) {
        trackingInterval = setInterval(() => cargarEstado(token), 5000);
    }
}

async function cargarEstado(token) {
    try {
        const res = await fetch(BASE + '/pedido/' + LINK_TOKEN + '/estado/' + token);
        const d   = await res.json();
        if (!d.success) return;
        renderTracking(d.pedido, d.items);
        actualizarChatBadge(d.chat_no_leidos || 0);
    } catch {}
}

function renderTracking(pedido, items) {
    if (lastEstado && lastEstado !== pedido.estado) {
        notificarCambioEstado();
    }
    lastEstado = pedido.estado;

    const esRecoger = pedido.tipo === 'recoger';
    const STEPS     = esRecoger ? STEPS_RECOGER : STEPS_DOMICILIO;

    document.getElementById('trackNum').textContent  = 'Pedido DOM-' + pedido.id;
    document.getElementById('trackName').innerHTML   =
        pedido.nombre_cliente +
        ` <span style="font-size:12px;background:${esRecoger?'#eafaf1':'#eaf4fb'};color:${esRecoger?'#27ae60':'#2980b9'};border-radius:20px;padding:2px 10px;font-weight:700;margin-left:6px">
            <i class="fas fa-${esRecoger?'store':'motorcycle'}"></i> ${esRecoger?'Recoger en local':'Domicilio'}
        </span>`;

    const step = STEPS.find(s => s.estado === pedido.estado) || STEPS[STEPS.length - 1];
    document.getElementById('trackBadge').innerHTML         = `<i class="fas ${step.icon}"></i> ${step.label}`;
    document.getElementById('trackBadge').style.background  = step.color + '22';
    document.getElementById('trackBadge').style.color       = step.color;

    // Timeline
    // El paso '_valor' es virtual (no existe como estado en BD).
    // Se considera completado cuando el pedido ya pasó de 'pendiente'.
    const estadoOrdenDomicilio = { pendiente: 0, preparacion: 2, listo: 3, en_camino: 4, entregado: 5, cancelado: 5 };
    const estadoIdx = esRecoger
        ? STEPS.findIndex(s => s.estado === pedido.estado)
        : (estadoOrdenDomicilio[pedido.estado] ?? 0);

    document.getElementById('timelineSteps').innerHTML = STEPS.map((s, i) => {
        const cls = i < estadoIdx ? 'done' : i === estadoIdx ? 'active' : 'pending';
        const ico = i < estadoIdx ? 'fa-check' : s.icon;

        let subText;
        if (cls === 'done') {
            if (s.estado === '_valor' && pedido.valor_domicilio != null) {
                subText = `✓ $${parseFloat(pedido.valor_domicilio).toLocaleString()} de envío`;
            } else {
                subText = '✓ Completado';
            }
        } else if (cls === 'active') {
            subText = s.sub;
        } else {
            subText = 'Pendiente';
        }

        return `<div class="ped-step ${cls}">
            <div class="ped-step-line"></div>
            <div class="ped-step-icon"><i class="fas ${ico}"></i></div>
            <div class="ped-step-info">
                <div class="ped-step-label">${s.label}</div>
                <div class="ped-step-sub">${subText}</div>
            </div>
        </div>`;
    }).join('');

    // Items
    document.getElementById('trackItems').innerHTML = items.map(it =>
        `<div class="ped-track-item">
            <span>${esc(it.nombre)} ×${it.cantidad}</span>
            <strong>$${(it.precio * it.cantidad).toLocaleString()}</strong>
        </div>`
    ).join('');
    const domEl = document.getElementById('trackValorDom');
    if (!esRecoger && pedido.valor_domicilio != null) {
        const valorDom   = parseFloat(pedido.valor_domicilio);
        const totalFinal = parseFloat(pedido.total) + valorDom;
        document.getElementById('trackValorDomAmt').textContent = '$' + valorDom.toLocaleString();
        document.getElementById('trackTotal').textContent = '$' + totalFinal.toLocaleString();
        domEl.style.display = 'flex';
    } else {
        document.getElementById('trackTotal').textContent = '$' + parseFloat(pedido.total).toLocaleString();
        domEl.style.display = 'none';
    }

    // Estado final: limpiar localStorage y mostrar mensaje de cierre
    if (pedido.estado === 'entregado' || pedido.estado === 'cancelado') {
        clearInterval(trackingInterval);
        trackingInterval = null;
        localStorage.removeItem(STORAGE_KEY);
        trackingToken = null;
        document.getElementById('chatFab').classList.remove('show');
        if (chatAbierto) cerrarChat();
        const isOk = pedido.estado === 'entregado';
        if (isOk && !ticketDescargado) {
            ticketDescargado = true;
            descargarTicketCliente(pedido, items);
        }
        document.getElementById('pedidoFinalMsg').innerHTML = `
            <div style="margin-top:16px;padding:16px;border-radius:14px;display:flex;align-items:center;gap:12px;
                        background:${isOk ? '#eafaf1' : '#fdedec'};border:1.5px solid ${isOk ? '#27ae60' : '#e74c3c'}">
                <i class="fas ${isOk ? 'fa-circle-check' : 'fa-circle-xmark'}" style="font-size:24px;color:${isOk ? '#27ae60' : '#e74c3c'}"></i>
                <div>
                    <div style="font-weight:800;color:${isOk ? '#1e8449' : '#c0392b'}">${isOk ? '¡Pedido entregado!' : 'Pedido cancelado'}</div>
                    <div style="font-size:13px;color:#636e72;margin-top:2px">${isOk ? 'Gracias por tu compra. ¡Hasta pronto!' : 'Tu pedido no pudo ser procesado.'}</div>
                </div>
            </div>
            <button class="ped-new-order-btn" style="margin-top:12px" onclick="nuevoTrackingPedido()">
                <i class="fas fa-plus"></i> Hacer nuevo pedido
            </button>`;
    } else {
        document.getElementById('pedidoFinalMsg').innerHTML = '';
    }
}

function nuevoTrackingPedido() {
    localStorage.removeItem(STORAGE_KEY);
    trackingToken = null;
    clearInterval(trackingInterval);
    trackingInterval = null;
    lastEstado = null;
    // Limpiar carrito
    Object.keys(cart).forEach(k => delete cart[k]);
    RECETAS.forEach(r => {
        actualizarUI(r.id);
    });
    actualizarFAB();
    mostrarVista('viewMenu');
}

function notificarCambioEstado() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const g   = ctx.createGain();
        osc.connect(g); g.connect(ctx.destination);
        osc.frequency.value = 660;
        g.gain.setValueAtTime(0.2, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start(); osc.stop(ctx.currentTime + 0.5);
    } catch {}
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function mostrarVista(id) {
    ['viewMenu','viewForm','viewTracking'].forEach(v => {
        const el = document.getElementById(v);
        if (v === 'viewForm')          el.classList.toggle('show', v === id);
        else if (v === 'viewTracking') el.classList.toggle('show', v === id);
        else el.style.display = v === id ? 'block' : 'none';
    });
    document.getElementById('cartFab').classList.toggle('hidden',
        id !== 'viewMenu' || Object.keys(cart).length === 0);
    document.getElementById('chatFab').classList.toggle('show',
        id === 'viewTracking' && !!trackingToken);

    const isMenu = id === 'viewMenu';
    const sb = document.getElementById('pedSearchbar');
    const hb = document.getElementById('hamburgerBtn');
    if (sb) sb.style.display = isMenu ? '' : 'none';
    if (hb) hb.style.display = isMenu ? '' : 'none';
    if (!isMenu) {
        const si = document.getElementById('searchInput');
        if (si && si.value) { si.value = ''; buscarReceta(''); }
        cerrarNav();
    }
}

// ── Sidenav ────────────────────────────────────────────────────────────────────
function abrirNav() {
    document.getElementById('navOverlay').classList.add('show');
    document.getElementById('sidenav').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function cerrarNav() {
    document.getElementById('navOverlay').classList.remove('show');
    document.getElementById('sidenav').classList.remove('show');
    document.body.style.overflow = '';
}
function irACategoria(cat) {
    const el = document.getElementById('cat-' + cat);
    if (!el) return;
    const headerH = document.querySelector('.ped-header-sticky')?.offsetHeight ?? 0;
    const top = el.getBoundingClientRect().top + window.scrollY - headerH - 8;
    window.scrollTo({ top, behavior: 'smooth' });
    cerrarNav();
}

// ── Búsqueda ────────────────────────────────────────────────────────────────────
function buscarReceta(query) {
    const q       = query.trim().toLowerCase();
    const clearBtn = document.getElementById('searchClear');
    if (clearBtn) clearBtn.classList.toggle('show', q.length > 0);

    const catTitles = document.querySelectorAll('#viewMenu .ped-cat-title');
    const grids     = document.querySelectorAll('#viewMenu .ped-menu-grid');

    catTitles.forEach((title, i) => {
        const grid = grids[i];
        if (!grid) return;
        if (!q) {
            title.style.display = '';
            grid.style.display  = '';
            grid.querySelectorAll('.ped-item-card').forEach(c => c.style.display = '');
            return;
        }
        let any = false;
        grid.querySelectorAll('.ped-item-card').forEach(card => {
            const name  = (card.querySelector('.ped-item-name')?.textContent ?? '').toLowerCase();
            const match = name.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) any = true;
        });
        title.style.display = any ? '' : 'none';
        grid.style.display  = any ? '' : 'none';
    });
}
function limpiarBusqueda() {
    const input = document.getElementById('searchInput');
    if (input) { input.value = ''; buscarReceta(''); input.focus(); }
}

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Ticket PDF (descarga automática al entregar) ───────────────────────────
async function descargarTicketCliente(pedido, items) {
    try {
        if (!window.jspdf) {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                s.onload = resolve; s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        const { jsPDF } = window.jspdf;
        const esRecoger = pedido.tipo === 'recoger';
        const pageH = Math.max(160, 115 + items.length * 7
                      + (pedido.notas ? 14 : 0)
                      + (pedido.valor_domicilio != null && !esRecoger ? 10 : 0));

        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: [80, pageH] });
        const W = 80, M = 5, CX = W / 2;
        let y = 10;

        const hrLine = () => { doc.setLineWidth(0.2); doc.line(M, y, W - M, y); y += 4; };
        const dashes = () => {
            doc.setFont('Courier', 'normal'); doc.setFontSize(8);
            doc.text('-'.repeat(38), CX, y, { align: 'center' }); y += 4;
        };

        // Encabezado
        doc.setFont('Courier', 'bold'); doc.setFontSize(13);
        doc.text(COMERCIO_NOMBRE.toUpperCase(), CX, y, { align: 'center' }); y += 6;

        if (COMERCIO_TEL) {
            doc.setFont('Courier', 'normal'); doc.setFontSize(8);
            doc.text('Tel: ' + COMERCIO_TEL, CX, y, { align: 'center' }); y += 5;
        }

        doc.setFont('Courier', 'bold'); doc.setFontSize(10);
        doc.text('COMPROBANTE DE PEDIDO', CX, y, { align: 'center' }); y += 5;
        doc.setFontSize(14);
        doc.text('DOM-' + pedido.id, CX, y, { align: 'center' }); y += 5;

        doc.setFont('Courier', 'normal'); doc.setFontSize(8);
        const fecha = new Date(pedido.created_at.replace(' ', 'T'));
        doc.text(
            fecha.toLocaleDateString('es-CO') + '  ' +
            fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }),
            CX, y, { align: 'center' }
        ); y += 5;

        dashes();

        // Cliente
        doc.setFont('Courier', 'bold'); doc.setFontSize(8);
        doc.text('CLIENTE:', M, y); y += 4;
        doc.setFont('Courier', 'normal');
        doc.text(pedido.nombre_cliente, M, y); y += 4;

        if (!esRecoger && pedido.direccion) {
            const dirLines = doc.splitTextToSize('Dir: ' + pedido.direccion, W - M * 2);
            doc.text(dirLines, M, y); y += dirLines.length * 4;
        }
        if (pedido.telefono) { doc.text('Tel: ' + pedido.telefono, M, y); y += 4; }

        doc.setFont('Courier', 'bold');
        doc.text(esRecoger ? '[ RECOGIDO EN LOCAL ]' : '[ DOMICILIO ]', CX, y, { align: 'center' }); y += 5;

        dashes();

        // Items
        doc.setFontSize(8);
        doc.text('CANT  DESCRIPCION', M, y);
        doc.text('VALOR', W - M, y, { align: 'right' }); y += 3;
        hrLine();

        doc.setFont('Courier', 'normal');
        items.forEach(item => {
            const lbl = item.cantidad + 'x  ' + (item.nombre.length > 20 ? item.nombre.substring(0,19) + '.' : item.nombre);
            const val = '$' + (parseFloat(item.precio) * item.cantidad).toLocaleString('es-CO');
            doc.text(lbl, M, y);
            doc.text(val, W - M, y, { align: 'right' });
            y += 5;
        });

        hrLine();

        // Totales
        doc.setFont('Courier', 'bold');
        const subtotal = parseFloat(pedido.total);
        if (!esRecoger && pedido.valor_domicilio != null) {
            const domVal     = parseFloat(pedido.valor_domicilio);
            const totalFinal = subtotal + domVal;
            doc.setFontSize(9);
            doc.text('Subtotal', M, y);
            doc.text('$' + subtotal.toLocaleString('es-CO'), W - M, y, { align: 'right' }); y += 5;
            doc.text('Domicilio', M, y);
            doc.text('$' + domVal.toLocaleString('es-CO'), W - M, y, { align: 'right' }); y += 5;
            hrLine();
            doc.setFontSize(11);
            doc.text('TOTAL', M, y);
            doc.text('$' + totalFinal.toLocaleString('es-CO'), W - M, y, { align: 'right' }); y += 7;
        } else {
            doc.setFontSize(11);
            doc.text('TOTAL', M, y);
            doc.text('$' + subtotal.toLocaleString('es-CO'), W - M, y, { align: 'right' }); y += 7;
        }

        // Notas
        if (pedido.notas) {
            doc.setFont('Courier', 'normal'); doc.setFontSize(8);
            const notaLines = doc.splitTextToSize('Nota: ' + pedido.notas, W - M * 2);
            doc.text(notaLines, M, y); y += notaLines.length * 4 + 2;
        }

        dashes();

        doc.setFont('Courier', 'normal'); doc.setFontSize(8);
        doc.text('Gracias por tu compra!', CX, y, { align: 'center' }); y += 4;
        doc.text('Generado por ChefControl', CX, y, { align: 'center' });

        doc.save('Pedido-DOM-' + pedido.id + '.pdf');
    } catch (e) {
        console.warn('No se pudo generar el ticket:', e);
    }
}

// ── Chat cliente ──────────────────────────────────────────────────────────────
function actualizarChatBadge(count) {
    const nr = document.getElementById('chatNr');
    if (count > 0 && !chatAbierto) {
        nr.textContent = count;
        nr.classList.add('show');
    } else {
        nr.classList.remove('show');
    }
}

function abrirChat() {
    if (!trackingToken) return;
    chatAbierto = true;
    chatDesdeId = 0;

    // Bloquear scroll del body para evitar que la página salte al abrir el chat
    _chatScrollY = window.scrollY;
    document.body.style.overflow  = 'hidden';
    document.body.style.position  = 'fixed';
    document.body.style.top       = `-${_chatScrollY}px`;
    document.body.style.width     = '100%';

    document.getElementById('chatOverlay').classList.add('show');
    document.getElementById('chatDrawer').classList.add('show');
    document.getElementById('chatNr').classList.remove('show');
    document.getElementById('chatMsgs').innerHTML =
        '<div class="ped-chat-empty"><i class="fas fa-spinner fa-spin"></i></div>';
    cargarMensajesCliente(true);
    if (!chatInterval) {
        chatInterval = setInterval(cargarMensajesCliente, 3000);
    }

    // Ajuste para teclado virtual en móvil
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', _ajustarChatMovil);
    }

    setTimeout(() => document.getElementById('chatInput').focus(), 350);
}

function cerrarChat() {
    chatAbierto = false;

    // Restaurar scroll del body
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.top      = '';
    document.body.style.width    = '';
    window.scrollTo(0, _chatScrollY);

    // Limpiar tamaño inline que pudo haber puesto _ajustarChatMovil
    const drawer = document.getElementById('chatDrawer');
    drawer.style.height = '';

    document.getElementById('chatOverlay').classList.remove('show');
    drawer.classList.remove('show');
    clearInterval(chatInterval);
    chatInterval = null;

    if (window.visualViewport) {
        window.visualViewport.removeEventListener('resize', _ajustarChatMovil);
    }
}

function _ajustarChatMovil() {
    const drawer = document.getElementById('chatDrawer');
    if (!drawer.classList.contains('show')) return;
    const vp = window.visualViewport;
    // Ajusta la altura del drawer al espacio visible (excluye el teclado)
    drawer.style.height = vp.height + 'px';
    requestAnimationFrame(() => {
        const msgs = document.getElementById('chatMsgs');
        msgs.scrollTop = msgs.scrollHeight;
    });
}

async function cargarMensajesCliente(inicial = false) {
    if (!trackingToken) return;
    try {
        // Si hay mensajes enviados aún sin leer, recargar desde 0 para obtener el visto
        const hasPending = !inicial && [...msgMap.values()].some(m => m.de === 'cliente' && !m.leido);
        const desde = (inicial || hasPending) ? 0 : chatDesdeId;

        const r = await fetch(
            BASE + '/pedido/' + LINK_TOKEN + '/chat/' + trackingToken + '/mensajes?desde=' + desde
        );
        const d = await r.json();
        if (!d.success) return;
        const msgsEl = document.getElementById('chatMsgs');

        if (inicial) {
            msgsEl.innerHTML = '';
            msgMap.clear();
        }

        if (d.data.length) {
            if (msgsEl.querySelector('.ped-chat-empty')) msgsEl.innerHTML = '';
            let needsScroll = inicial;

            d.data.forEach(msg => {
                const msgId  = parseInt(msg.id);
                chatDesdeId  = Math.max(chatDesdeId, msgId);
                const isRead = parseInt(msg.leido) === 1;
                const leidoAt = msg.leido_at
                    ? new Date(msg.leido_at.replace(' ', 'T'))
                        .toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : '';

                if (msgMap.has(msgId)) {
                    // Actualizar visto si cambió
                    const entry = msgMap.get(msgId);
                    if (entry.de === 'cliente' && entry.leido !== isRead) {
                        entry.leido = isRead;
                        const receiptEl = entry.div.querySelector('.ped-chat-receipt');
                        if (receiptEl && isRead) {
                            receiptEl.className = 'ped-chat-receipt read';
                            receiptEl.innerHTML = `<i class="fas fa-check-double"></i>${leidoAt ? ' ' + leidoAt : ''}`;
                        }
                    }
                    return; // ya renderizado, solo actualizar visto
                }

                // Mensaje nuevo — crear elemento
                const hora = new Date(msg.created_at.replace(' ', 'T'))
                    .toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                let receipt = '';
                if (msg.de === 'cliente') {
                    receipt = isRead
                        ? `<span class="ped-chat-receipt read"><i class="fas fa-check-double"></i>${leidoAt ? ' ' + leidoAt : ''}</span>`
                        : `<span class="ped-chat-receipt sent"><i class="fas fa-check"></i></span>`;
                }

                const div = document.createElement('div');
                div.className = 'ped-chat-msg ' + msg.de;
                div.innerHTML =
                    `<div class="ped-chat-bubble">${esc(msg.mensaje)}</div>` +
                    `<div class="ped-chat-time">${msg.de === 'admin' ? 'Restaurante' : 'Tú'} · ${hora}${receipt}</div>`;
                msgsEl.appendChild(div);
                msgMap.set(msgId, { div, leido: isRead, de: msg.de });
                needsScroll = true;
            });

            if (needsScroll) msgsEl.scrollTop = msgsEl.scrollHeight;
        } else if (inicial) {
            msgsEl.innerHTML =
                '<div class="ped-chat-empty" id="chatEmpty">Sin mensajes aún.<br>Escríbenos si tienes alguna consulta.</div>';
        }
    } catch {}
}

// ── Toggle / Cupón ────────────────────────────────────────────────────────────
function togglePedCupon() {
    const wrap = document.getElementById('pedCuponWrap');
    const icon = document.getElementById('pedIconCupon');
    const link = document.getElementById('pedToggleCupon');
    const open = wrap.style.display !== 'none';
    wrap.style.display = open ? 'none' : 'block';
    icon.className     = open ? 'fas fa-tag' : 'fas fa-times';
    link.style.color   = open ? '#95a5a6'   : '#e74c3c';
    if (!open) {
        document.getElementById('fCupon').focus();
    } else {
        quitarCupon();
    }
}

async function aplicarCupon() {
    const input  = document.getElementById('fCupon');
    const msgEl  = document.getElementById('cuponMsg');
    const codigo = input.value.trim();
    if (!codigo) { msgEl.innerHTML = ''; return; }
    try {
        const res = await fetch(BASE + '/cupones/validar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo }),
        });
        const d = await res.json();
        if (d.ok) {
            cuponAplicado = d;
            let descStr;
            if (d.tipo === 'porcentaje') {
                descStr = d.descuento + '%';
            } else if (d.tipo === 'producto') {
                descStr = d.descuento + '%' + (d.receta_nombre ? ' en ' + esc(d.receta_nombre) : '');
            } else {
                descStr = '$' + Number(d.descuento).toLocaleString();
            }
            msgEl.innerHTML = `<span style="color:#27ae60"><i class="fas fa-check-circle"></i> Descuento de ${descStr} aplicado${d.nombre ? ' — ' + esc(d.nombre) : ''}</span>`;
            input.disabled = true;
            const btn = document.getElementById('btnCupon');
            btn.textContent = 'Quitar';
            btn.style.background = '#e74c3c';
            btn.onclick = quitarCupon;
        } else {
            cuponAplicado = null;
            msgEl.innerHTML = `<span style="color:#e74c3c"><i class="fas fa-times-circle"></i> ${esc(d.msg)}</span>`;
        }
        actualizarResumenConCupon();
    } catch {
        msgEl.innerHTML = '<span style="color:#e74c3c">Error de conexión</span>';
    }
}

function quitarCupon() {
    cuponAplicado = null;
    const input = document.getElementById('fCupon');
    input.value = '';
    input.disabled = false;
    document.getElementById('cuponMsg').innerHTML = '';
    const btn = document.getElementById('btnCupon');
    btn.textContent = 'Aplicar';
    btn.style.background = '#636e72';
    btn.onclick = aplicarCupon;
    actualizarResumenConCupon();
}

async function enviarMensajeCliente() {
    if (!trackingToken) return;
    const input   = document.getElementById('chatInput');
    const mensaje = input.value.trim();
    if (!mensaje) return;
    input.value = '';
    try {
        await fetch(BASE + '/pedido/' + LINK_TOKEN + '/chat/' + trackingToken + '/enviar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje }),
        });
        await cargarMensajesCliente();
    } catch {}
}
</script>

<!-- Sidenav overlay -->
<div class="ped-nav-overlay" id="navOverlay" onclick="cerrarNav()"></div>

<!-- Sidenav categorías -->
<div class="ped-sidenav" id="sidenav">
    <div class="ped-sidenav-head">
        <span><i class="fas fa-list" style="margin-right:8px;opacity:.6"></i>Categorías</span>
        <button class="ped-sidenav-close" onclick="cerrarNav()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="ped-sidenav-body">
        <?php foreach ($porCategoria as $cat => $catItems):
            $ci2 = $catIcons[$cat] ?? $catIcons['otro'];
        ?>
        <button class="ped-nav-cat" onclick="irACategoria('<?php echo $cat; ?>')">
            <span class="ped-nav-cat-icon" style="background:<?php echo $ci2['color']; ?>22;color:<?php echo $ci2['color']; ?>">
                <i class="fas <?php echo $ci2['icon']; ?>"></i>
            </span>
            <?php echo $categoriasLabel[$cat] ?? ucfirst($cat); ?>
            <span class="ped-nav-cat-count"><?php echo count($catItems); ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
