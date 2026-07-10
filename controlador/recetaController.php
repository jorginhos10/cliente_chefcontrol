<?php
// controlador/recetaController.php

require_once 'config/config.php';
require_once 'modelo/recetaModel.php';
require_once 'core/FotoUtil.php';

class RecetaController {
    private $recetaModel;

    public function __construct() {
        $this->recetaModel = new RecetaModel();
    }

    public function index() {
        $recetas      = $this->recetaModel->obtenerTodasRecetas();
        $estadisticas = $this->recetaModel->obtenerEstadisticas();
        $insumos      = $this->recetaModel->obtenerInsumos();
        require_once 'vista/recetas/index.php';
    }

    public function galeria() {
        require_once 'vista/recetas/galeria.php';
    }

    public function crear() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }

        $datos = $this->extraerDatos();
        if (empty($datos['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la receta es obligatorio']);
            exit;
        }

        $fotoUrls         = self::normalizarFotoUrls((array)($_POST['foto_urls'] ?? []));
        $datos['foto']    = !empty($fotoUrls) ? json_encode($fotoUrls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $ingredientes     = $this->extraerIngredientes();

        $id = $this->recetaModel->crearReceta($datos, $ingredientes);
        if ($id) {
            echo json_encode(['success' => true, 'message' => 'Receta creada exitosamente', 'id' => $id]);
        } else {
            $this->enviarError('Error al guardar la receta');
        }
        exit;
    }

    public function actualizar() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->enviarError('Método no permitido'); }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->enviarError('ID no válido'); }

        $datos = $this->extraerDatos();
        if (empty($datos['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'El nombre de la receta es obligatorio']);
            exit;
        }

        $ingredientes   = $this->extraerIngredientes();
        $recetaActual   = $this->recetaModel->obtenerRecetaPorId($id);
        $fotoUrls       = self::normalizarFotoUrls((array)($_POST['foto_urls'] ?? []));
        $datos['foto']  = !empty($fotoUrls) ? json_encode($fotoUrls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ($recetaActual['foto'] ?? null);

        if ($this->recetaModel->actualizarReceta($id, $datos, $ingredientes)) {
            echo json_encode(['success' => true, 'message' => 'Receta actualizada exitosamente']);
        } else {
            $this->enviarError('Error al actualizar la receta');
        }
        exit;
    }


    public function subirImagen() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }
        if (empty($_FILES['imagen']['name']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen']); exit;
        }

        $file = $_FILES['imagen'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $permitidas, true)) {
            echo json_encode(['success' => false, 'message' => 'Formato no permitido. Usa JPG, PNG, GIF o WEBP']); exit;
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'La imagen no puede superar 3MB']); exit;
        }

        $slug = $this->slugComercio();
        $dir  = __DIR__ . '/../assets/uploads/recetas/' . $slug . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'receta_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']); exit;
        }

        $url = Config::getBaseUrl() . '/assets/uploads/recetas/' . $slug . '/' . $filename;
        echo json_encode(['success' => true, 'url' => $url]);
        exit;
    }

    public function bancoImagenes() {
        header('Content-Type: application/json');

        $slug = $this->slugComercio();
        $dir  = __DIR__ . '/../assets/uploads/recetas/' . $slug . '/';

        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imagenes   = [];

        if (is_dir($dir)) {
            $archivos = scandir($dir) ?: [];
            foreach ($archivos as $archivo) {
                $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                if (!in_array($ext, $permitidas, true)) continue;
                $imagenes[] = [
                    'url'  => Config::getBaseUrl() . '/assets/uploads/recetas/' . $slug . '/' . $archivo,
                    'time' => filemtime($dir . $archivo) ?: 0,
                ];
            }
        }

        usort($imagenes, fn($a, $b) => $b['time'] <=> $a['time']);
        $urls = array_column($imagenes, 'url');

        echo json_encode(['success' => true, 'imagenes' => $urls]);
        exit;
    }

    public function eliminarImagenBanco() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
        }

        $body     = json_decode(file_get_contents('php://input'), true);
        $filename = basename((string)($body['filename'] ?? ''));

        if ($filename === '' || $filename === '.' || $filename === '..') {
            echo json_encode(['success' => false, 'message' => 'Archivo no válido']); exit;
        }

        $slug    = $this->slugComercio();
        $dir     = __DIR__ . '/../assets/uploads/recetas/' . $slug . '/';
        $realDir = realpath($dir);
        $ruta    = $realDir !== false ? realpath($dir . $filename) : false;

        // Verifica que el archivo resuelto siga dentro de la carpeta del comercio (evita path traversal)
        if (!$realDir || !$ruta || strpos($ruta, $realDir) !== 0 || !is_file($ruta)) {
            echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']); exit;
        }

        if (!unlink($ruta)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la imagen']); exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    private function slugComercio(): string {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_SESSION['comercio_slug'] ?? ''));
        if ($slug === '') $slug = 'comercio-' . (int)($_SESSION['comercio_id'] ?? 0);
        return $slug;
    }

    public function get($id) {
        header('Content-Type: application/json');
        $receta = $this->recetaModel->obtenerRecetaPorId($id);
        if (!$receta) {
            echo json_encode(['success' => false, 'message' => 'Receta no encontrada']);
            exit;
        }
        // Decodifica foto a array limpio, aplanando JSON anidado si los datos estaban corruptos
        $receta['foto_urls'] = self::parseFotoUrls($receta['foto'] ?? '');
        $receta['ingredientes'] = $this->recetaModel->obtenerIngredientesReceta($id);
        echo json_encode(['success' => true, 'data' => $receta], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if ($this->recetaModel->eliminarReceta($id)) {
                $_SESSION['success'] = 'Receta eliminada exitosamente';
            } else {
                $_SESSION['error'] = 'Error al eliminar la receta';
            }
        }
        header("Location: " . Config::getBasePath() . "/recetas");
        exit;
    }

    public function updateStatus() {
        header('Content-Type: application/json');
        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id']     ?? 0);
        $activo = (int)($input['activo'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID no válido']); exit; }

        if ($this->recetaModel->actualizarEstado($id, $activo)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error actualizando estado']);
        }
        exit;
    }

    private function extraerDatos() {
        return [
            'nombre'             => $this->sanitizar($_POST['nombre']             ?? ''),
            'descripcion'        => $this->sanitizar($_POST['descripcion']        ?? ''),
            'categoria'          => $this->sanitizar($_POST['categoria']          ?? 'plato_fuerte'),
            'tiempo_preparacion' => (int)($_POST['tiempo_preparacion']            ?? 0),
            'porciones'          => max(1, (int)($_POST['porciones']              ?? 1)),
            'precio_venta'       => max(0, (float)($_POST['precio_venta']         ?? 0)),
            'activo'             => isset($_POST['activo']) ? 1 : 0,
        ];
    }

    private function extraerIngredientes() {
        $json = $_POST['ingredientes_json'] ?? '[]';
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private const MAX_FOTOS = 5;

    /**
     * Normaliza foto_urls[] del POST: aplana (recursivamente) cualquier elemento
     * que sea a su vez un JSON array anidado, auto-reparando datos corruptos de guardados anteriores.
     * Limita el resultado a MAX_FOTOS.
     */
    private static function normalizarFotoUrls(array $raw): array {
        return array_slice(FotoUtil::parseFotoUrls($raw), 0, self::MAX_FOTOS);
    }

    /**
     * Recibe el valor raw del campo `foto` y devuelve un array plano de URLs.
     * Maneja datos corruptos: JSON anidado, doble-encoded, plain URL, etc.
     */
    public static function parseFotoUrls(string $raw): array {
        return FotoUtil::parseFotoUrls($raw);
    }

    private function sanitizar($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    private function enviarError($mensaje) {
        echo json_encode(['success' => false, 'message' => $mensaje]);
        exit;
    }
}
?>
