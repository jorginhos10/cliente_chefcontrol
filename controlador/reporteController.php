<?php
// controlador/reporteController.php

require_once 'config/config.php';
require_once 'modelo/reporteModel.php';
require_once 'modelo/comercioModel.php';

class ReporteController {
    private $model;
    private $comercioModel;

    public function __construct() {
        $this->model         = new ReporteModel();
        $this->comercioModel = new ComercioModel();
    }

    public function index() {
        $hoy   = date('Y-m-d');
        $desde = $_GET['desde']     ?? date('Y-m-01');   // primer día del mes
        $hasta = $_GET['hasta']     ?? $hoy;
        $tipo  = $_GET['tipo']      ?? '';
        $insumo = (int)($_GET['insumo'] ?? 0);
        $categoria = $_GET['categoria'] ?? '';

        $movimientos  = $this->model->obtenerMovimientos($desde, $hasta, $tipo, $insumo, $categoria);
        $resumen      = $this->model->obtenerResumenFiltrado($desde, $hasta, $tipo, $insumo, $categoria);
        $generales    = $this->model->obtenerEstadisticasGenerales();
        $insumos      = $this->model->obtenerInsumos();
        $porDia       = $this->model->obtenerMovimientosPorDia($desde, $hasta);
        $topInsumos   = $this->model->obtenerTopInsumos($desde, $hasta);

        require_once 'vista/reportes/index.php';
    }

    public function filtrar() {
        header('Content-Type: application/json');

        $desde     = $_GET['desde']     ?? date('Y-m-01');
        $hasta     = $_GET['hasta']     ?? date('Y-m-d');
        $tipo      = $_GET['tipo']      ?? '';
        $insumo    = (int)($_GET['insumo'] ?? 0);
        $categoria = $_GET['categoria'] ?? '';

        $movimientos = $this->model->obtenerMovimientos($desde, $hasta, $tipo, $insumo, $categoria);
        $resumen     = $this->model->obtenerResumenFiltrado($desde, $hasta, $tipo, $insumo, $categoria);

        echo json_encode(['success' => true, 'movimientos' => $movimientos, 'resumen' => $resumen]);
        exit;
    }

    public function reporteX() {
        $fecha    = $_GET['fecha'] ?? date('Y-m-d');
        $datos    = $this->model->obtenerDatosReporteX($fecha);
        $comercio = $this->comercioModel->obtener();
        extract($datos);
        require_once 'vista/reportes/reporte_x.php';
    }

    public function reporteZ() {
        $historial        = $this->model->obtenerHistorialZ();
        $estadoMes        = $this->model->obtenerEstadisticasMesZ();
        $graficaAnual     = $this->model->obtenerGraficaAnualZ();
        $aniosDisponibles = $this->model->obtenerAniosConDatos();
        $comercio         = $this->comercioModel->obtener();
        $codigoFact       = $this->comercioModel->obtenerCodigoFacturacion();
        $papel            = ComercioModel::parametrosPapel($comercio['tamano_papel'] ?? '80mm');
        require_once 'vista/reportes/reporte_z.php';
    }

    public function graficaZ(): void {
        header('Content-Type: application/json');
        $anio = (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2000 || $anio > 2099) {
            echo json_encode(['success' => false]); exit;
        }
        $rows  = $this->model->obtenerGraficaAnioZ($anio);
        $meses = array_fill(1, 12, ['monto' => 0.0, 'cierres' => 0]);
        foreach ($rows as $r) {
            $meses[(int)$r['mes']] = ['monto' => (float)$r['monto'], 'cierres' => (int)$r['cierres']];
        }
        echo json_encode(['success' => true, 'data' => $meses]);
        exit;
    }

    public function generarZ() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success'=>false,'message'=>'Método no permitido']); exit;
        }
        $id_usuario = $_SESSION['usuario_id'] ?? null;
        $r = $this->model->generarCierreZ($id_usuario);
        if ($r['ok']) {
            echo json_encode(['success'=>true,'data'=>$r]);
        } else {
            echo json_encode(['success'=>false,'message'=>$r['msg']]);
        }
        exit;
    }

    public function verZ($id) {
        header('Content-Type: application/json');
        $z = $this->model->obtenerCierreZ((int)$id);
        if (!$z) { echo json_encode(['success'=>false,'message'=>'No encontrado']); exit; }
        echo json_encode(['success'=>true,'data'=>$z]);
        exit;
    }

    public function exportarCsv() {
        $desde     = $_GET['desde']     ?? date('Y-m-01');
        $hasta     = $_GET['hasta']     ?? date('Y-m-d');
        $tipo      = $_GET['tipo']      ?? '';
        $insumo    = (int)($_GET['insumo'] ?? 0);
        $categoria = $_GET['categoria'] ?? '';

        $movimientos = $this->model->obtenerMovimientos($desde, $hasta, $tipo, $insumo, $categoria);

        $nombre = 'reporte_inventario_' . $desde . '_' . $hasta . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        // BOM para que Excel abra UTF-8 correctamente
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, ['Fecha', 'Hora', 'Insumo', 'Categoría', 'Tipo', 'Cantidad', 'Unidad', 'Stock Anterior', 'Stock Nuevo', 'Motivo', 'Usuario'], ';');

        foreach ($movimientos as $m) {
            $fecha = date('d/m/Y', strtotime($m['fecha']));
            $hora  = date('H:i',   strtotime($m['fecha']));
            fputcsv($out, [
                $fecha,
                $hora,
                $m['insumo'],
                ucfirst($m['categoria']),
                ucfirst($m['tipo']),
                number_format($m['cantidad'],      2, '.', ''),
                $m['unidad'],
                number_format($m['stock_anterior'], 2, '.', ''),
                number_format($m['stock_nuevo'],    2, '.', ''),
                $m['descripcion'] ?? '',
                $m['usuario']     ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }
}
?>
