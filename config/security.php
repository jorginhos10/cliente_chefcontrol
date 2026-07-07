<?php
// config/security.php - VERSIÓN SIMPLIFICADA

// Verificar si el usuario está logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . Config::getBasePath() . '/login');
    exit();
}

// Para páginas de administración (usuarios), verificar si es admin
if (strpos($_SERVER['REQUEST_URI'], '/usuarios') !== false) {
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
        $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
        header('Location: ' . Config::getBasePath() . '/403');
        exit();
    }
}
?>