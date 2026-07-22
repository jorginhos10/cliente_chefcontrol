<?php
// controlador/configuracionController.php

require_once 'config/config.php';
require_once 'modelo/mesaModel.php';
require_once 'modelo/usuarioModel.php';
require_once 'modelo/comercioModel.php';

class ConfiguracionController {
    private $mesaModel;
    private $usuarioModel;
    private $comercioModel;

    public function __construct() {
        $this->mesaModel     = new MesaModel();
        $this->usuarioModel  = new UsuarioModel();
        $this->comercioModel = new ComercioModel();
    }

    public function index() {
        $mesaStats    = $this->mesaModel->obtenerEstadisticas();
        $usuarioStats = $this->usuarioModel->obtenerEstadisticas();
        $comercio     = $this->comercioModel->obtener();
        require_once 'vista/configuraciones/index.php';
    }

    public function comercio() {
        $comercio = $this->comercioModel->obtener();
        require_once 'vista/configuraciones/comercio.php';
    }

    public function domicilios() {
        require_once 'modelo/domicilioModel.php';
        $domModel   = new DomicilioModel();
        $links      = $domModel->obtenerLinks();
        $catRecetas = $domModel->obtenerCategoriasRecetas();
        require_once 'vista/configuraciones/domicilios.php';
    }

    public function integraciones() {
        $comercio = $this->comercioModel->obtener();
        require_once 'vista/configuraciones/integraciones.php';
    }

    public function impresoras() {
        require_once 'vista/configuraciones/impresoras.php';
    }

    public function guardarComercio() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $actual = $this->comercioModel->obtener();

        $datos = [
            'nombre'    => trim($_POST['nombre']    ?? ''),
            'tipo'      => trim($_POST['tipo']      ?? ''),
            'rut'       => trim($_POST['rut']       ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'ciudad'    => trim($_POST['ciudad']    ?? ''),
            'telefono'  => trim($_POST['telefono']  ?? ''),
            'email'     => trim($_POST['email']     ?? ''),
            'sitio_web' => trim($_POST['sitio_web'] ?? ''),
            'eslogan'   => trim($_POST['eslogan']   ?? ''),
            'moneda'           => trim($_POST['moneda']           ?? 'USD'),
            'horario_apertura' => trim($_POST['horario_apertura'] ?? '08:00'),
            'horario_cierre'   => trim($_POST['horario_cierre']   ?? '22:00'),
            'logo'             => $actual['logo'] ?? null,
        ];

        if (empty($datos['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'El nombre del negocio es obligatorio']); exit;
        }

        // Subir logo
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['logo'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','svg'];
            if (in_array($ext, $allowed) && $file['size'] <= 2 * 1024 * 1024) {
                $dir = __DIR__ . '/../assets/uploads/comercio/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                if (!empty($actual['logo']) && file_exists($dir . $actual['logo'])) {
                    unlink($dir . $actual['logo']);
                }
                $filename = 'logo_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                    $datos['logo'] = $filename;
                }
            }
        }

        if ($this->comercioModel->guardar($datos)) {
            echo json_encode(['success' => true, 'message' => 'Información guardada correctamente', 'logo' => $datos['logo']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar']);
        }
        exit;
    }
}
?>
