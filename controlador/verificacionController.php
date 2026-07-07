<?php
require_once __DIR__ . '/../config/config.php';

class VerificacionController {

    private int $cid;

    public function __construct() {
        $this->cid = (int)($_SESSION['comercio_id'] ?? 0);
    }

    public function index(): void {
        $datos = $this->obtenerComercio();
        require_once __DIR__ . '/../vista/verificacion/index.php';
    }

    public function subir(): void {
        $basePath = Config::getBasePath();
        if (!$this->cid) {
            header("Location: {$basePath}/login"); exit;
        }

        $docs    = ['cedula_frente','cedula_trasera','logo','foto_negocio'];
        $rutas   = [];
        $errores = [];
        $dir     = __DIR__ . "/../assets/docs/{$this->cid}/";

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $_SESSION['verif_error'] = 'No se pudo crear el directorio de documentos. Contacta al soporte.';
            header("Location: {$basePath}/verificacion"); exit;
        }

        // Obtener rutas actuales para eliminar archivos viejos al reemplazar
        $actual = $this->obtenerComercio();

        foreach ($docs as $campo) {
            $fileKey = "doc_{$campo}";
            if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) continue;

            $file = $_FILES[$fileKey];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errores[] = "Error al recibir '{$campo}' (código {$file['error']})."; continue;
            }

            $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allow = ['jpg','jpeg','png','webp','pdf'];

            if (!in_array($ext, $allow)) {
                $errores[] = "'{$campo}': formato no permitido (solo JPG, PNG, PDF)."; continue;
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                $errores[] = "'{$campo}': supera el límite de 5MB."; continue;
            }

            $nombre = $campo . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
                // Eliminar archivo anterior si existe
                $rutaVieja = $actual["doc_{$campo}"] ?? null;
                if ($rutaVieja) {
                    $archivoViejo = __DIR__ . '/../' . $rutaVieja;
                    if (is_file($archivoViejo)) @unlink($archivoViejo);
                }
                $rutas["doc_{$campo}"]          = "assets/docs/{$this->cid}/{$nombre}";
                $rutas["doc_{$campo}_rechazo"]  = null;
            } else {
                $errores[] = "No se pudo guardar '{$campo}'. Verifica permisos del servidor.";
            }
        }

        if ($errores) {
            $_SESSION['verif_error'] = implode('<br>', $errores);
            header("Location: {$basePath}/verificacion"); exit;
        }

        if (empty($rutas)) {
            $_SESSION['verif_error'] = 'Debes subir al menos un documento.';
            header("Location: {$basePath}/verificacion"); exit;
        }

        // Estado resultante: calcular sobre datos actuales + nuevos
        $comercio = $this->obtenerComercio();

        $merged = [];
        foreach ($docs as $c) {
            $merged["doc_{$c}"]         = $rutas["doc_{$c}"]         ?? $comercio["doc_{$c}"]         ?? null;
            $merged["doc_{$c}_rechazo"] = array_key_exists("doc_{$c}_rechazo", $rutas)
                                            ? null
                                            : ($comercio["doc_{$c}_rechazo"] ?? null);
        }

        $allPresent   = $merged['doc_cedula_frente'] && $merged['doc_cedula_trasera']
                     && $merged['doc_logo']          && $merged['doc_foto_negocio'];
        $noRejections = !$merged['doc_cedula_frente_rechazo']  && !$merged['doc_cedula_trasera_rechazo']
                     && !$merged['doc_logo_rechazo']           && !$merged['doc_foto_negocio_rechazo'];

        // Construir SET dinámico
        $sets   = [];
        $params = [];
        foreach ($rutas as $col => $val) {
            $sets[]   = "{$col} = ?";
            $params[] = $val;
        }

        if ($allPresent && $noRejections) {
            $sets[]   = "doc_estado = ?";
            $params[] = 'en_revision';
        }

        $params[] = $this->cid;

        try {
            DB::get()->prepare(
                "UPDATE comercios SET " . implode(', ', $sets) . " WHERE id = ?"
            )->execute($params);
        } catch (\Throwable $e) {
            error_log("VerificacionController::subir error: " . $e->getMessage());
            $_SESSION['verif_error'] = 'Error al guardar los documentos.';
            header("Location: {$basePath}/verificacion"); exit;
        }

        $_SESSION['verif_ok'] = true;
        header("Location: {$basePath}/verificacion"); exit;
    }

    private function obtenerComercio(): array {
        try {
            $s = DB::get()->prepare(
                "SELECT id, nombre,
                        doc_cedula_frente,         doc_cedula_frente_rechazo,
                        doc_cedula_trasera,        doc_cedula_trasera_rechazo,
                        doc_logo,                  doc_logo_rechazo,
                        doc_foto_negocio,          doc_foto_negocio_rechazo,
                        doc_estado, doc_rechazo_motivo
                 FROM comercios WHERE id = ?"
            );
            $s->execute([$this->cid]);
            return $s->fetch() ?: [];
        } catch (\Throwable $e) { return []; }
    }
}
