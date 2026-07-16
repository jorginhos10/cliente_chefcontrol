<?php
// controlador/facturacionController.php

require_once 'modelo/comercioModel.php';

class FacturacionController {
    private $model;

    public function __construct() {
        $this->model = new ComercioModel();
        $this->migrar();
    }

    // Agrega las columnas del cierre de caja automático si aún no existen.
    // Usa information_schema en vez de "ADD COLUMN IF NOT EXISTS" porque esa
    // sintaxis no es soportada de forma confiable por la versión de MySQL/MariaDB
    // del hosting (ver hallazgo previo con planes.visibilidad en admin_chefcontrol).
    private function migrar(): void {
        try {
            $db = DB::get();
            $cols = ['cierre_auto_activo', 'cierre_auto_hora', 'cierre_auto_ultima_fecha', 'tamano_papel'];
            $existentes = $db->query(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comercios'
                   AND COLUMN_NAME IN ('" . implode("','", $cols) . "')"
            )->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('cierre_auto_activo', $existentes, true)) {
                $db->exec("ALTER TABLE comercios ADD COLUMN cierre_auto_activo TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (!in_array('cierre_auto_hora', $existentes, true)) {
                $db->exec("ALTER TABLE comercios ADD COLUMN cierre_auto_hora VARCHAR(5) NOT NULL DEFAULT '23:00'");
            }
            if (!in_array('cierre_auto_ultima_fecha', $existentes, true)) {
                $db->exec("ALTER TABLE comercios ADD COLUMN cierre_auto_ultima_fecha DATE NULL DEFAULT NULL");
            }
            if (!in_array('tamano_papel', $existentes, true)) {
                $db->exec("ALTER TABLE comercios ADD COLUMN tamano_papel VARCHAR(10) NOT NULL DEFAULT '80mm'");
            }
        } catch (\Throwable $e) {
            error_log('FacturacionController::migrar — ' . $e->getMessage());
        }
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
