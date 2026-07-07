<?php
// modelo/recetaModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class RecetaModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodasRecetas() {
        $this->requireCid();
        $sql = "SELECT r.*,
                    COUNT(ri.id) AS total_ingredientes
                FROM recetas r
                LEFT JOIN receta_insumos ri ON r.id = ri.id_receta AND ri.comercio_id = {$this->cid}
                WHERE r.comercio_id = {$this->cid}
                GROUP BY r.id
                ORDER BY r.created_at DESC";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo recetas: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerRecetaPorId($id) {
        $this->requireCid();
        $sql = "SELECT r.*, COUNT(ri.id) AS total_ingredientes
                FROM recetas r
                LEFT JOIN receta_insumos ri ON r.id = ri.id_receta AND ri.comercio_id = {$this->cid}
                WHERE r.id = :id AND r.comercio_id = {$this->cid}
                GROUP BY r.id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error obteniendo receta: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerIngredientesReceta($id_receta) {
        $this->requireCid();
        $sql = "SELECT ri.id, ri.cantidad,
                    i.id AS id_insumo, i.nombre AS insumo_nombre,
                    i.unidad_medida, i.categoria
                FROM receta_insumos ri
                JOIN insumos i ON ri.id_insumo = i.id AND i.comercio_id = {$this->cid}
                WHERE ri.id_receta = :id_receta AND ri.comercio_id = {$this->cid}
                ORDER BY i.nombre";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id_receta', (int)$id_receta, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo ingredientes: " . $e->getMessage());
            return [];
        }
    }

    public function crearReceta($datos, $ingredientes) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO recetas
                        (comercio_id, nombre, descripcion, categoria, tiempo_preparacion,
                         porciones, precio_venta, activo, foto)
                    VALUES
                        ({$this->cid}, :nombre, :descripcion, :categoria, :tiempo_preparacion,
                         :porciones, :precio_venta, :activo, :foto)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre',             $datos['nombre']);
            $stmt->bindValue(':descripcion',        $datos['descripcion']);
            $stmt->bindValue(':categoria',          $datos['categoria']);
            $stmt->bindValue(':tiempo_preparacion', $datos['tiempo_preparacion'], PDO::PARAM_INT);
            $stmt->bindValue(':porciones',          $datos['porciones'],          PDO::PARAM_INT);
            $stmt->bindValue(':precio_venta',       $datos['precio_venta']);
            $stmt->bindValue(':activo',             $datos['activo'],             PDO::PARAM_INT);
            $stmt->bindValue(':foto',               $datos['foto'] ?? null);
            $stmt->execute();
            $id_receta = $this->db->lastInsertId();

            $this->insertarIngredientes($id_receta, $ingredientes);

            $this->db->commit();
            return $id_receta;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error creando receta: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarFoto($id, $foto) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "UPDATE recetas SET foto = :foto WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmt->bindValue(':foto', $foto);
            $stmt->bindValue(':id',   (int)$id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizarFoto: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarReceta($id, $datos, $ingredientes) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $sql = "UPDATE recetas SET
                        nombre             = :nombre,
                        descripcion        = :descripcion,
                        categoria          = :categoria,
                        tiempo_preparacion = :tiempo_preparacion,
                        porciones          = :porciones,
                        precio_venta       = :precio_venta,
                        activo             = :activo,
                        foto               = :foto
                    WHERE id = :id AND comercio_id = {$this->cid}";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':nombre',             $datos['nombre']);
            $stmt->bindValue(':descripcion',        $datos['descripcion']);
            $stmt->bindValue(':categoria',          $datos['categoria']);
            $stmt->bindValue(':tiempo_preparacion', $datos['tiempo_preparacion'], PDO::PARAM_INT);
            $stmt->bindValue(':porciones',          $datos['porciones'],          PDO::PARAM_INT);
            $stmt->bindValue(':precio_venta',       $datos['precio_venta']);
            $stmt->bindValue(':activo',             $datos['activo'],             PDO::PARAM_INT);
            $stmt->bindValue(':foto',               $datos['foto'] ?? null);
            $stmt->bindValue(':id',                 (int)$id,                     PDO::PARAM_INT);
            $stmt->execute();

            $del = $this->db->prepare(
                "DELETE FROM receta_insumos WHERE id_receta = :id AND comercio_id = {$this->cid}"
            );
            $del->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $del->execute();

            $this->insertarIngredientes($id, $ingredientes);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error actualizando receta: " . $e->getMessage());
            return false;
        }
    }

    private function insertarIngredientes($id_receta, $ingredientes) {
        if (empty($ingredientes)) return;
        $sql  = "INSERT INTO receta_insumos (comercio_id, id_receta, id_insumo, cantidad)
                 VALUES ({$this->cid}, :id_receta, :id_insumo, :cantidad)";
        $stmt = $this->db->prepare($sql);
        foreach ($ingredientes as $ing) {
            $id_insumo = (int)($ing['id_insumo'] ?? 0);
            $cantidad  = (float)($ing['cantidad'] ?? 0);
            if ($id_insumo <= 0 || $cantidad <= 0) continue;
            $stmt->bindValue(':id_receta', (int)$id_receta, PDO::PARAM_INT);
            $stmt->bindValue(':id_insumo', $id_insumo,      PDO::PARAM_INT);
            $stmt->bindValue(':cantidad',  $cantidad);
            $stmt->execute();
        }
    }

    public function eliminarReceta($id) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM recetas WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error eliminando receta: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstado($id, $activo) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "UPDATE recetas SET activo = :activo WHERE id = :id AND comercio_id = {$this->cid}"
            );
            $stmt->bindValue(':activo', (int)$activo, PDO::PARAM_INT);
            $stmt->bindValue(':id',     (int)$id,     PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizando estado receta: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas() {
        $this->requireCid();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activas,
                    COUNT(DISTINCT categoria) AS categorias,
                    (SELECT COUNT(*) FROM receta_insumos WHERE comercio_id = {$this->cid}) AS total_ingredientes_usados
                FROM recetas
                WHERE comercio_id = {$this->cid}";
        try {
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            error_log("Error estadísticas recetas: " . $e->getMessage());
            return ['total' => 0, 'activas' => 0, 'categorias' => 0, 'total_ingredientes_usados' => 0];
        }
    }

    public function obtenerInsumos() {
        $this->requireCid();
        $sql = "SELECT id, nombre, unidad_medida, categoria
                FROM insumos
                WHERE activo = 1 AND comercio_id = {$this->cid}
                ORDER BY nombre";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo insumos para recetas: " . $e->getMessage());
            return [];
        }
    }
}
?>
