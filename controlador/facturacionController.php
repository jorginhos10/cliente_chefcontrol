<?php
// controlador/facturacionController.php

require_once 'modelo/comercioModel.php';

class FacturacionController {
    private $model;

    public function __construct() {
        $this->model = new ComercioModel();
    }

    public function index() {
        $comercioConfig = $this->model->obtener() ?? [];
        require_once 'vista/facturacion/index.php';
    }

    public function guardarConfig() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $campo = trim($body['campo'] ?? '');
        $valor = $body['valor'] ?? null;

        if ($campo === '') {
            echo json_encode(['success' => false, 'message' => 'Campo no especificado']);
            exit;
        }

        $ok = $this->model->actualizarCampo($campo, $valor);
        echo json_encode(['success' => $ok]);
        exit;
    }
}
