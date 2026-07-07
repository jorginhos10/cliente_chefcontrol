<?php
// modelo/inventarioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class InventarioModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerProveedoresActivos() {
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

    public function obtenerInsumosConStock() {
        $this->requireCid();
        $sql = "SELECT id, nombre, descripcion, categoria, unidad_medida,
                       cantidad_stock, cantidad_minima, precio_unitario, activo
                FROM insumos
                WHERE comercio_id = {$this->cid}
                ORDER BY categoria, nombre";
        try {
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo insumos inventario: " . $e->getMessage());
            return [];
        }
    }

    public function registrarMovimiento($id_insumo, $tipo, $cantidad, $descripcion, $id_usuario, $id_proveedor = null) {
        $this->requireCid();
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT cantidad_stock FROM insumos WHERE id = :id AND comercio_id = {$this->cid} FOR UPDATE"
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
            } elseif ($tipo === 'venta' || $tipo === 'salida') {
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
                "UPDATE insumos SET cantidad_stock = :nuevo WHERE id = :id AND comercio_id = {$this->cid}"
            )->execute([':nuevo' => $stock_nuevo, ':id' => (int)$id_insumo]);

            $ins = $this->db->prepare(
                "INSERT INTO movimientos_insumos
                     (comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario, id_proveedor)
                 VALUES ({$this->cid}, :id_insumo, :tipo, :cantidad, :stock_anterior, :stock_nuevo, :descripcion, :id_usuario, :id_proveedor)"
            );
            $ins->bindValue(':id_insumo',      (int)$id_insumo,    PDO::PARAM_INT);
            $ins->bindValue(':tipo',            $tipo);
            $ins->bindValue(':cantidad',        $cantidad);
            $ins->bindValue(':stock_anterior',  $stock_anterior);
            $ins->bindValue(':stock_nuevo',     $stock_nuevo);
            $ins->bindValue(':descripcion',     $descripcion);
            $ins->bindValue(':id_usuario',      $id_usuario);
            $ins->bindValue(':id_proveedor',    $id_proveedor);
            $ins->execute();

            $this->db->commit();
            return ['ok' => true, 'stock_nuevo' => $stock_nuevo, 'stock_anterior' => $stock_anterior];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error registrando movimiento: " . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error interno al registrar movimiento'];
        }
    }

    public function obtenerMovimientos($limite = 50, $id_insumo = null) {
        $this->requireCid();
        $where = "WHERE m.comercio_id = {$this->cid}";
        if ($id_insumo) $where .= " AND m.id_insumo = :id_insumo";

        $sql = "SELECT m.*, i.nombre AS insumo_nombre, i.unidad_medida,
                       u.nombre AS usuario_nombre,
                       p.nombre AS proveedor_nombre
                FROM movimientos_insumos m
                JOIN insumos i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
                LEFT JOIN usuarios u ON m.id_usuario = u.id AND u.comercio_id = {$this->cid}
                LEFT JOIN proveedores p ON m.id_proveedor = p.id AND p.comercio_id = {$this->cid}
                $where
                ORDER BY m.fecha DESC
                LIMIT :limite";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            if ($id_insumo) $stmt->bindValue(':id_insumo', (int)$id_insumo, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obteniendo movimientos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEstadisticas() {
        $this->requireCid();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN cantidad_stock > cantidad_minima THEN 1 ELSE 0 END) AS stock_ok,
                    SUM(CASE WHEN cantidad_stock > 0 AND cantidad_stock <= cantidad_minima THEN 1 ELSE 0 END) AS stock_bajo,
                    SUM(CASE WHEN cantidad_stock <= 0 THEN 1 ELSE 0 END) AS sin_stock,
                    (SELECT COUNT(*) FROM movimientos_insumos WHERE comercio_id = {$this->cid} AND DATE(fecha) = CURDATE()) AS mov_hoy
                FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid}";
        try {
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            error_log("Error estadísticas inventario: " . $e->getMessage());
            return ['total' => 0, 'stock_ok' => 0, 'stock_bajo' => 0, 'sin_stock' => 0, 'mov_hoy' => 0];
        }
    }
}
?>
