<?php
// controlador/dashboardController.php

require_once 'config/config.php';
require_once 'modelo/mesaModel.php';
require_once 'modelo/ventaModel.php';
require_once 'modelo/insumoModel.php';
require_once 'modelo/recetaModel.php';

class DashboardController {
    public function index() {
        $mesaModel   = new MesaModel();
        $ventaModel  = new VentaModel();
        $insumoModel = new InsumoModel();
        $recetaModel = new RecetaModel();

        $mesaStats    = $mesaModel->obtenerEstadisticas();
        $ventaStats   = $ventaModel->obtenerEstadisticasHoy();
        $insumoStats  = $insumoModel->obtenerEstadisticas();
        $recetaStats  = $recetaModel->obtenerEstadisticas();

        $ordenesActivas = $ventaModel->obtenerOrdenesActivas();
        $ultimasVentas  = $ventaModel->obtenerVentas(6);

        // Conteos por estado de cocina
        $pendientes    = count(array_filter($ordenesActivas, fn($o) => $o['estado'] === 'abierta'));
        $enPreparacion = count(array_filter($ordenesActivas, fn($o) => $o['estado'] === 'en_preparacion'));
        $listas        = count(array_filter($ordenesActivas, fn($o) => $o['estado'] === 'lista'));

        require_once 'vista/dashboard/index.php';
    }
}
?>
