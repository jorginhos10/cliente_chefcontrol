<?php
// modelo/ingresosModel.php
require_once __DIR__ . '/../core/BaseModel.php';

class IngresosModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos(string $desde = '', string $hasta = '', string $estado = ''): array {
        $this->requireCid();
        $where  = "i.comercio_id = {$this->cid}";
        $params = [];
        if ($desde)  { $where .= ' AND i.fecha >= :desde';  $params[':desde']  = $desde; }
        if ($hasta)  { $where .= ' AND i.fecha <= :hasta';  $params[':hasta']  = $hasta; }
        if ($estado) { $where .= ' AND i.estado = :estado'; $params[':estado'] = $estado; }

        $stmt = $this->db->prepare(
            "SELECT i.*, COALESCE(u.nombre, '—') AS usuario_nombre,
                    COALESCE(p.nombre, '—') AS proveedor_nombre
             FROM ingresos i
             LEFT JOIN usuarios u ON i.id_usuario = u.id AND u.comercio_id = {$this->cid}
             LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
             WHERE $where
             ORDER BY i.fecha DESC, i.id DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT i.*, COALESCE(p.nombre, '') AS proveedor_nombre
             FROM ingresos i
             LEFT JOIN proveedores p ON i.id_proveedor = p.id AND p.comercio_id = {$this->cid}
             WHERE i.id = :id AND i.comercio_id = {$this->cid}"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerItems(int $idIngreso): array {
        $this->requireCid();
        $stmt = $this->db->prepare(
            "SELECT * FROM ingresos_items
             WHERE id_ingreso = :id AND comercio_id = {$this->cid}
             ORDER BY id ASC"
        );
        $stmt->execute([':id' => $idIngreso]);
        return $stmt->fetchAll();
    }

    public function getInsumos(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT id, nombre, unidad_medida, precio_unitario
             FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function getProveedores(): array {
        $this->requireCid();
        return $this->db->query(
            "SELECT id, nombre, empresa FROM proveedores
             WHERE activo = 1 AND comercio_id = {$this->cid} ORDER BY nombre ASC"
        )->fetchAll();
    }

    public function generarRadicado(): string {
        $this->requireCid();
        $hoy  = date('Ymd');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM ingresos WHERE DATE(fecha) = CURDATE() AND comercio_id = {$this->cid}"
        );
        $stmt->execute();
        $n = (int)$stmt->fetchColumn() + 1;
        return $hoy . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    public function crear(array $datos, array $items): int {
        $this->requireCid();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO ingresos
                     (comercio_id, radicado, fecha, tipo_documento, serie, numero, concepto,
                      subtotal, impuesto, total, estado, id_usuario, id_proveedor)
                 VALUES
                     ({$this->cid}, :radicado, :fecha, :tipo_documento, :serie, :numero, :concepto,
                      :subtotal, :impuesto, :total, :estado, :id_usuario, :id_proveedor)"
            );
            $stmt->execute($datos);
            $id = (int)$this->db->lastInsertId();
            $this->insertarItems($id, $items);
            $this->db->commit();
            return $id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $datos, array $items): bool {
        $this->requireCid();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE ingresos SET concepto=:concepto, subtotal=:subtotal,
                 impuesto=:impuesto, total=:total, id_proveedor=:id_proveedor
                 WHERE id=:id AND comercio_id={$this->cid}"
            );
            $stmt->execute([
                ':concepto'     => $datos[':concepto'],
                ':subtotal'     => $datos[':subtotal'],
                ':impuesto'     => $datos[':impuesto'],
                ':total'        => $datos[':total'],
                ':id_proveedor' => $datos[':id_proveedor'],
                ':id'           => $id,
            ]);
            $this->db->prepare(
                "DELETE FROM ingresos_items WHERE id_ingreso = :id AND comercio_id = {$this->cid}"
            )->execute([':id' => $id]);
            $this->insertarItems($id, $items);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function eliminar(int $id): bool {
        $this->requireCid();
        return (bool)$this->db->prepare(
            "DELETE FROM ingresos WHERE id = :id AND comercio_id = {$this->cid}"
        )->execute([':id' => $id]);
    }

    public function estadisticas(string $desde = '', string $hasta = ''): array {
        $this->requireCid();
        $where  = "comercio_id = {$this->cid}";
        $params = [];
        if ($desde) { $where .= ' AND fecha >= :desde'; $params[':desde'] = $desde; }
        if ($hasta) { $where .= ' AND fecha <= :hasta'; $params[':hasta'] = $hasta; }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS suma
             FROM ingresos WHERE $where AND estado != 'Anulado'"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) AS anulados FROM ingresos WHERE $where AND estado = 'Anulado'"
        );
        $stmt2->execute($params);
        $row2 = $stmt2->fetch();

        return [
            'total'    => (int)$row['total'],
            'suma'     => (float)$row['suma'],
            'anulados' => (int)$row2['anulados'],
        ];
    }

    private function insertarItems(int $idIngreso, array $items): void {
        $stmt = $this->db->prepare(
            "INSERT INTO ingresos_items (comercio_id, id_ingreso, articulo, cantidad, precio_unitario, subtotal)
             VALUES ({$this->cid}, :id_ingreso, :articulo, :cantidad, :precio_unitario, :subtotal)"
        );
        foreach ($items as $item) {
            $stmt->execute([
                ':id_ingreso'      => $idIngreso,
                ':articulo'        => $item['articulo'],
                ':cantidad'        => (float)$item['cantidad'],
                ':precio_unitario' => (float)$item['precio_unitario'],
                ':subtotal'        => (float)$item['subtotal'],
            ]);
        }
    }
}
?>
