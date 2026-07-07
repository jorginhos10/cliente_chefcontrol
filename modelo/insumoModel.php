<?php
// modelo/insumoModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class InsumoModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodosInsumos() {
        $this->requireCid();
        $sql = "SELECT i.*, p.nombre AS proveedor_nombre
                FROM insumos i
                LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                WHERE i.comercio_id = {$this->cid}
                ORDER BY i.created_at DESC";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo insumos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerInsumoPorId($id) {
        $this->requireCid();
        $sql = "SELECT i.*, p.nombre AS proveedor_nombre
                FROM insumos i
                LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                WHERE i.id = :id AND i.comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo insumo: " . $e->getMessage());
            return false;
        }
    }

    public function crearInsumo($datos) {
        $this->requireCid();
        $sql = "INSERT INTO insumos
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
            error_log("Error creando insumo: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarInsumo($id, $datos) {
        $this->requireCid();
        $sql = "UPDATE insumos SET
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
            error_log("Error actualizando insumo: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarInsumo($id) {
        $this->requireCid();
        $sql = "DELETE FROM insumos WHERE id = :id AND comercio_id = {$this->cid}";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando insumo: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstado($id, $activo) {
        $this->requireCid();
        $sql = "UPDATE insumos SET activo = :activo WHERE id = :id AND comercio_id = {$this->cid}";
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

    public function buscarInsumos($termino) {
        $this->requireCid();
        $sql = "SELECT i.*, p.nombre AS proveedor_nombre
                FROM insumos i
                LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                WHERE i.comercio_id = {$this->cid}
                  AND (i.nombre LIKE :t1 OR i.categoria LIKE :t2 OR i.descripcion LIKE :t3)
                ORDER BY i.nombre";
        try {
            $stmt = $this->db->prepare($sql);
            $t = '%' . $termino . '%';
            $stmt->bindValue(':t1', $t);
            $stmt->bindValue(':t2', $t);
            $stmt->bindValue(':t3', $t);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error buscando insumos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEstadisticas() {
        $this->requireCid();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN cantidad_stock <= cantidad_minima AND activo = 1 THEN 1 ELSE 0 END) AS stock_bajo,
                    COUNT(DISTINCT categoria) AS categorias
                FROM insumos
                WHERE comercio_id = {$this->cid}";
        try {
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'stock_bajo' => 0, 'categorias' => 0];
        }
    }

    public function obtenerProveedores() {
        $this->requireCid();
        $sql = "SELECT id, nombre FROM proveedores
                WHERE activo = 1 AND comercio_id = {$this->cid}
                ORDER BY nombre";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo proveedores: " . $e->getMessage());
            return [];
        }
    }
}
?>
