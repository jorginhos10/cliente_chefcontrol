<?php
// controlador/propinaController.php

require_once 'config/config.php';
require_once 'modelo/propinaModel.php';

class PropinaController {
    private PropinaModel $model;

    public function __construct() {
        $this->model = new PropinaModel();
    }

    public function index(): void {
        $model         = $this->model;
        $acumuladoUsuarios = null; // calculado en la vista después de leer filtros
        require_once 'vista/propinas/index.php';
    }
}
?>
