<?php
// controlador/proveedoresController.php

require_once 'config/config.php';
require_once 'modelo/proveedorModel.php';

class proveedoresController {
    private $proveedorModel;

    public function __construct() {
        try {
            $this->proveedorModel = new proveedorModel();
        } catch (Exception $e) {
            $this->enviarError("Error iniciando modelo: " . $e->getMessage());
        }
    }

    public function index() {
        try {
            $proveedores = $this->proveedorModel->obtenerTodosProveedores();
            $categorias  = $this->proveedorModel->obtenerCategorias();
            require_once 'vista/proveedores/index.php';
        } catch (Exception $e) {
            die("Error cargando proveedores: " . $e->getMessage());
        }
    }

    public function crear() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        try {
            $datos = $this->extraerDatos();

            $errores = $this->validar($datos);
            if (!empty($errores)) {
                echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]);
                exit;
            }

            if ($this->proveedorModel->crearProveedor($datos)) {
                echo json_encode(['success' => true, 'message' => 'Proveedor creado exitosamente']);
            } else {
                $this->enviarError('Error al crear el proveedor en la base de datos');
            }
        } catch (Exception $e) {
            $this->enviarError('Excepción en crear: ' . $e->getMessage());
        }
        exit;
    }

    public function actualizar() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->enviarError('Método no permitido');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->enviarError('ID de proveedor no válido');
        }

        try {
            $datos = $this->extraerDatos(false);

            $errores = $this->validar($datos);
            if (!empty($errores)) {
                echo json_encode(['success' => false, 'message' => 'Errores de validación', 'errors' => $errores]);
                exit;
            }

            if ($this->proveedorModel->actualizarProveedor($id, $datos)) {
                echo json_encode(['success' => true, 'message' => 'Proveedor actualizado exitosamente']);
            } else {
                $this->enviarError('Error al actualizar el proveedor');
            }
        } catch (Exception $e) {
            $this->enviarError('Excepción en actualizar: ' . $e->getMessage());
        }
        exit;
    }

    public function get($id) {
        header('Content-Type: application/json');

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID no válido']);
            exit;
        }

        try {
            $proveedor = $this->proveedorModel->obtenerProveedorPorId($id);
            if ($proveedor) {
                echo json_encode(['success' => true, 'data' => $proveedor]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function getCategorias() {
        header('Content-Type: application/json');
        
        try {
            $categorias = $this->proveedorModel->obtenerCategorias();
            
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function updateStatus() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $activo = $input['activo'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID de proveedor no válido']);
                exit;
            }
            
            if ($this->proveedorModel->actualizarEstado($id, $activo)) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function eliminar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            if ($this->proveedorModel->eliminarProveedor($id)) {
                $_SESSION['success'] = 'Proveedor eliminado exitosamente';
            } else {
                $_SESSION['error'] = 'Error al eliminar el proveedor';
            }
        }
        header("Location: " . Config::getBasePath() . "/proveedores");
        exit;
    }

    public function buscar() {
        header('Content-Type: application/json');
        
        $termino = $_GET['q'] ?? '';
        
        try {
            $proveedores = $this->proveedorModel->buscarProveedores($termino);
            
            echo json_encode([
                'success' => true,
                'data' => $proveedores,
                'total' => count($proveedores)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    private function extraerDatos(bool $conFoto = true) {
        $foto = $conFoto ? $this->manejarFoto($_FILES['foto'] ?? null) : null;
        $datos = [
            'nombre'      => $this->sanitizar($_POST['nombre']      ?? ''),
            'empresa'     => $this->sanitizar($_POST['empresa']     ?? ''),
            'telefono'    => $this->sanitizar($_POST['telefono']    ?? ''),
            'direccion'   => $this->sanitizar($_POST['direccion']   ?? ''),
            'correo'      => $this->sanitizar($_POST['correo']      ?? ''),
            'categoria'   => $this->sanitizar($_POST['categoria']   ?? 'A'),
            'observacion' => $this->sanitizar($_POST['observacion'] ?? ''),
            'nit_rut'     => $this->sanitizar($_POST['nit_rut']     ?? ''),
            'activo'      => isset($_POST['activo']) ? 1 : 0,
        ];
        if ($foto !== null) $datos['foto'] = $foto;
        return $datos;
    }

    private function validar(array $datos) {
        $errores = [];
        if (empty($datos['nombre'])) {
            $errores['nombre'] = ['El nombre del proveedor es obligatorio'];
        }
        if (empty($datos['telefono'])) {
            $errores['telefono'] = ['El teléfono es obligatorio'];
        }
        if (!empty($datos['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = ['Email inválido'];
        }
        return $errores;
    }

    private function sanitizar($input) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }

    private function manejarFoto($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return 'default.png';
        }
        
        // Validar tipo de archivo
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensionesPermitidas)) {
            return 'default.png';
        }
        
        // Validar tamaño (2MB máximo)
        if ($file['size'] > 2 * 1024 * 1024) {
            return 'default.png';
        }
        
        $nombreArchivo = 'proveedor_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $destino = __DIR__ . '/../../assets/media/proveedores/' . $nombreArchivo;
        
        // Crear directorio si no existe
        $directorio = dirname($destino);
        if (!is_dir($directorio)) {
            if (!mkdir($directorio, 0777, true)) {
                error_log("No se pudo crear el directorio: $directorio");
                return 'default.png';
            }
        }
        
        if (move_uploaded_file($file['tmp_name'], $destino)) {
            return $nombreArchivo;
        }
        
        error_log("Error moviendo archivo: " . $file['tmp_name'] . " a " . $destino);
        return 'default.png';
    }

    private function enviarError($mensaje) {
        error_log("ERROR proveedoresController: " . $mensaje);
        echo json_encode([
            'success' => false,
            'message' => $mensaje
        ]);
        exit;
    }
}
?>