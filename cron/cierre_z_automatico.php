<?php
// cron/cierre_z_automatico.php
//
// Genera el Reporte Z (cierre de caja) automáticamente para cada restaurante que
// tenga "cierre_auto_activo" encendido y cuya hora programada (cierre_auto_hora)
// coincida con la hora actual del servidor.
//
// Pensado para ejecutarse desde un Cron Job de cPanel, por ejemplo cada hora:
//   php /home/usuario/public_html/cron/cierre_z_automatico.php
// (si se llama por URL en vez de CLI, requiere ?token=Config::CRON_TOKEN)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../modelo/reporteModel.php';

if (PHP_SAPI !== 'cli') {
    if (($_GET['token'] ?? '') !== Config::CRON_TOKEN) {
        http_response_code(403);
        exit('No autorizado.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$horaActual = date('H:00');
$hoy        = date('Y-m-d');

try {
    $stmt = DB::get()->prepare(
        "SELECT id FROM comercios
         WHERE activo = 1 AND cierre_auto_activo = 1 AND cierre_auto_hora = ?
           AND (cierre_auto_ultima_fecha IS NULL OR cierre_auto_ultima_fecha != ?)"
    );
    $stmt->execute([$horaActual, $hoy]);
    $comercios = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\Throwable $e) {
    error_log('Cierre Z automático — error consultando comercios: ' . $e->getMessage());
    echo "Error consultando comercios.\n";
    exit;
}

$ok = 0; $fallidos = 0;
foreach ($comercios as $cid) {
    $_SESSION['comercio_id'] = (int)$cid;
    $model = new ReporteModel();
    $r = $model->generarCierreZ(null);

    if ($r['ok']) {
        try {
            DB::get()->prepare("UPDATE comercios SET cierre_auto_ultima_fecha=? WHERE id=?")
                     ->execute([$hoy, $cid]);
        } catch (\Throwable $e) {}
        error_log("Cierre Z automático — comercio {$cid}: OK (Z #{$r['numero_z']})");
        $ok++;
    } else {
        error_log("Cierre Z automático — comercio {$cid}: ERROR " . ($r['msg'] ?? ''));
        $fallidos++;
    }
}

echo "Hora: {$horaActual} — comercios encontrados: " . count($comercios) . " — OK: {$ok} — fallidos: {$fallidos}\n";
