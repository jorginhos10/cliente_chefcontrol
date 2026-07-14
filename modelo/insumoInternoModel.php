<?php
// modelo/insumoInternoModel.php
// Insumos de uso interno del negocio (no ligados a recetas): limpieza, papelería, etc.

require_once __DIR__ . '/../core/BaseModel.php';

class InsumoInternoModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos() {
        $this->requireCid();
        $sql = "SELECT i.*, p.nombre AS proveedor_nombre
                FROM insumos_internos i
                LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                WHERE i.comercio_id = {$this->cid}
                ORDER BY i.created_at DESC";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo insumos internos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        $this->requireCid();
        $sql = "SELECT i.*, p.nombre AS proveedor_nombre
                FROM insumos_internos i
                LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                WHERE i.id = :id AND i.comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo insumo interno: " . $e->getMessage());
            return false;
        }
    }

    public function crear($datos) {
        $this->requireCid();
        $sql = "INSERT INTO insumos_internos
                    (comercio_id, nombre, descripcion, categoria, unidad_medida,
                     cantidad_stock, cantidad_minima, precio_unitario, id_proveedor, activo)
                VALUES
                    ({$this->cid}, :nombre, :descripcion, :categoria, :unidad_medida,
                     :cantidad_stock, :cantidad_minima, :precio_unitario, :id_proveedor, :activo)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre',          $datos['nombre']);
            $stmt->bindValue(':descripcion',     $datos['descripcion']);
            $stmt->bindValue(':categoria',       $datos['categoria']);
            $stmt->bindValue(':unidad_medida',   $datos['unidad_medida']);
            $stmt->bindValue(':cantidad_stock',  $datos['cantidad_stock']);
            $stmt->bindValue(':cantidad_minima', $datos['cantidad_minima']);
            $stmt->bindValue(':precio_unitario', $datos['precio_unitario']);
            $stmt->bindValue(':id_proveedor',    $datos['id_proveedor']);
            $stmt->bindValue(':activo',          $datos['activo'], PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creando insumo interno: " . $e->getMessage());
            return false;
        }
    }

    public function actualizar($id, $datos) {
        $this->requireCid();
        $sql = "UPDATE insumos_internos SET
                    nombre          = :nombre,
                    descripcion     = :descripcion,
                    categoria       = :categoria,
                    unidad_medida   = :unidad_medida,
                    cantidad_stock  = :cantidad_stock,
                    cantidad_minima = :cantidad_minima,
                    precio_unitario = :precio_unitario,
                    id_proveedor    = :id_proveedor,
                    activo          = :activo
                WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre',          $datos['nombre']);
            $stmt->bindValue(':descripcion',     $datos['descripcion']);
            $stmt->bindValue(':categoria',       $datos['categoria']);
            $stmt->bindValue(':unidad_medida',   $datos['unidad_medida']);
            $stmt->bindValue(':cantidad_stock',  $datos['cantidad_stock']);
            $stmt->bindValue(':cantidad_minima', $datos['cantidad_minima']);
            $stmt->bindValue(':precio_unitario', $datos['precio_unitario']);
            $stmt->bindValue(':id_proveedor',    $datos['id_proveedor']);
            $stmt->bindValue(':activo',          $datos['activo'], PDO::PARAM_INT);
            $stmt->bindValue(':id',              (int)$id,         PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando insumo interno: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id) {
        $this->requireCid();
        $sql = "DELETE FROM insumos_internos WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando insumo interno: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstado($id, $activo) {
        $this->requireCid();
        $sql = "UPDATE insumos_internos SET activo = :activo WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':activo', (int)$activo, PDO::PARAM_INT);
            $stmt->bindValue(':id',     (int)$id,     PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando estado: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas() {
        $this->requireCid();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN cantidad_stock <= cantidad_minima AND activo = 1 THEN 1 ELSE 0 END) AS stock_bajo,
                    (SELECT COUNT(*) FROM movimientos_insumos_internos WHERE comercio_id = {$this->cid} AND DATE(fecha) = CURDATE()) AS mov_hoy
                FROM insumos_internos
                WHERE comercio_id = {$this->cid}";
        try {
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas insumos internos: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'stock_bajo' => 0, 'mov_hoy' => 0];
        }
    }

    public function obtenerProveedores() {
        $this->requireCid();
        $sql = "SELECT id, nombre, empresa FROM proveedores
                WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY nombre";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo proveedores: " . $e->getMessage());
            return [];
        }
    }

    // ── Movimientos (entrada / salida de stock) ────────────────────────────
    public function registrarMovimiento($id_insumo, $tipo, $cantidad, $descripcion, $id_usuario, $id_proveedor = null) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT cantidad_stock FROM insumos_internos WHERE id = :id AND comercio_id = {$this->cid} FOR UPDATE"
            );
            $stmt->bindValue(':id', (int)$id_insumo, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();

            if (!$row) {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'Insumo no encontrado'];
            }

            $stock_anterior = (float)$row['cantidad_stock'];

            if ($tipo === 'entrada') {
                $stock_nuevo = $stock_anterior + $cantidad;
            } elseif ($tipo === 'salida') {
                if ($cantidad > $stock_anterior) {
                    $this->db->rollBack();
                    return ['ok' => false, 'msg' => "Stock insuficiente. Disponible: $stock_anterior"];
                }
                $stock_nuevo = $stock_anterior - $cantidad;
            } else {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'Tipo de movimiento no válido'];
            }

            $this->db->prepare(
                "UPDATE insumos_internos SET cantidad_stock = :nuevo WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':nuevo' => $stock_nuevo, ':id' => (int)$id_insumo]);

            $ins = $this->db->prepare(
                "INSERT INTO movimientos_insumos_internos
                     (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario, id_proveedor)
                 VALUES ({$this->cid}, :id_insumo, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :descripcion, :id_usuario, :id_proveedor)"
            );
            $ins->bindValue(':id_insumo',     (int)$id_insumo, PDO::PARAM_INT);
            $ins->bindValue(':tipo',           $tipo);
            $ins->bindValue(':cantidad',       $cantidad);
            $ins->bindValue(':stock_anterior', $stock_anterior);
            $ins->bindValue(':stock_nuevo',    $stock_nuevo);
            $ins->bindValue(':descripcion',    $descripcion);
            $ins->bindValue(':id_usuario',     $id_usuario);
            $ins->bindValue(':id_proveedor',   $id_proveedor);
            $ins->execute();

            $this->db->commit();
            return ['ok' => true, 'stock_nuevo' => $stock_nuevo, 'stock_anterior' => $stock_anterior];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error registrando movimiento insumo interno: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error interno al registrar movimiento'];
        }
    }

    public function obtenerMovimientos($limite = 30) {
        $this->requireCid();
        $sql = "SELECT m.*, i.nombre AS insumo_nombre, i.unidad_medida,
                       u.nombre AS usuario_nombre,
                       p.nombre AS proveedor_nombre
                FROM movimientos_insumos_internos m
                JOIN insumos_internos i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
                LEFT JOIN usuarios u ON m.id_usuario = u.id AND u.comercio_id = {$this->cid}
                LEFT JOIN proveedores p ON m.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                WHERE m.comercio_id = {$this->cid}
                ORDER BY m.fecha DESC
                LIMIT :limite";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo movimientos insumos internos: " . $e->getMessage());
            return [];
        }
    }
}
?>
