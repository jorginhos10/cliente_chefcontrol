<?php
// modelo/reporteModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class ReporteModel extends BaseModel {

    public function __construct() {
        parent::__construct();
        $this->migrar();
    }

    // La tabla cierres_z nunca quedó en schema.sql/migrate.sql — se crea sola
    // aquí si falta (CREATE TABLE IF NOT EXISTS sí es confiable en MySQL/MariaDB,
    // a diferencia de ADD COLUMN IF NOT EXISTS que ya nos ha fallado antes).
    private function migrar(): void {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS `cierres_z` (
                    `id`           INT AUTO_INCREMENT PRIMARY KEY,
                    `comercio_id`  INT NOT NULL,
                    `numero_z`     INT NOT NULL,
                    `fecha_desde`  DATETIME NOT NULL,
                    `fecha_hasta`  DATETIME NOT NULL,
                    `total_ventas` INT NOT NULL DEFAULT 0,
                    `total_monto`  DECIMAL(12,2) NOT NULL DEFAULT 0,
                    `id_usuario`   INT NULL,
                    `datos_json`   LONGTEXT NULL,
                    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_cid` (`comercio_id`),
                    INDEX `idx_cid_fecha` (`comercio_id`, `fecha_hasta`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {
            error_log('ReporteModel::migrar — ' . $e->getMessage());
        }
    }

    public function obtenerMovimientos($desde, $hasta, $tipo = '', $id_insumo = 0, $categoria = '') {
        $this->requireCid();
        $conditions = ["m.comercio_id = {$this->cid}", "DATE(m.fecha) BETWEEN :desde AND :hasta"];
        $params     = [':desde' => $desde, ':hasta' => $hasta];

        if ($tipo && in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
            $conditions[] = "m.tipo = :tipo"; $params[':tipo'] = $tipo;
        }
        if ($id_insumo > 0) {
            $conditions[] = "m.id_insumo = :id_insumo"; $params[':id_insumo'] = $id_insumo;
        }
        if ($categoria) {
            $conditions[] = "i.categoria = :categoria"; $params[':categoria'] = $categoria;
        }

        $where = implode(' AND ', $conditions);
        $sql   = "SELECT m.id, m.fecha, i.nombre AS insumo, i.categoria, i.unidad_medida AS unidad,
                         m.tipo, m.cantidad, m.stock_anterior, m.stock_nuevo, m.descripcion,
                         u.nombre AS usuario
                  FROM movimientos_insumos m
                  JOIN insumos i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
                  LEFT JOIN usuarios u ON m.id_usuario = u.id AND u.comercio_id = {$this->cid}
                  WHERE $where
                  ORDER BY m.fecha DESC";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error obtenerMovimientos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerResumenFiltrado($desde, $hasta, $tipo = '', $id_insumo = 0, $categoria = '') {
        $this->requireCid();
        $conditions = ["m.comercio_id = {$this->cid}", "DATE(m.fecha) BETWEEN :desde AND :hasta"];
        $params     = [':desde' => $desde, ':hasta' => $hasta];

        if ($tipo && in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
            $conditions[] = "m.tipo = :tipo"; $params[':tipo'] = $tipo;
        }
        if ($id_insumo > 0) {
            $conditions[] = "m.id_insumo = :id_insumo"; $params[':id_insumo'] = $id_insumo;
        }
        if ($categoria) {
            $conditions[] = "i.categoria = :categoria"; $params[':categoria'] = $categoria;
        }

        $where = implode(' AND ', $conditions);
        $sql   = "SELECT
                      COUNT(*)                                                        AS total_movimientos,
                      SUM(CASE WHEN m.tipo = 'entrada' THEN m.cantidad ELSE 0 END)   AS total_entradas,
                      SUM(CASE WHEN m.tipo = 'salida'  THEN m.cantidad ELSE 0 END)   AS total_salidas,
                      SUM(CASE WHEN m.tipo = 'ajuste'  THEN 1          ELSE 0 END)   AS total_ajustes,
                      COUNT(DISTINCT m.id_insumo)                                    AS insumos_movidos
                  FROM movimientos_insumos m
                  JOIN insumos i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
                  WHERE $where";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error resumen: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerEstadisticasGenerales() {
        $this->requireCid();
        try {
            return [
                'insumos'     => (int)$this->db->query("SELECT COUNT(*) FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid}")->fetchColumn(),
                'recetas'     => (int)$this->db->query("SELECT COUNT(*) FROM recetas WHERE activo = 1 AND comercio_id = {$this->cid}")->fetchColumn(),
                'proveedores' => (int)$this->db->query("SELECT COUNT(*) FROM proveedores WHERE activo = 1 AND comercio_id = {$this->cid}")->fetchColumn(),
                'usuarios'    => (int)$this->db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND comercio_id = {$this->cid}")->fetchColumn(),
                'stock_bajo'  => (int)$this->db->query("SELECT COUNT(*) FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid} AND cantidad_stock <= cantidad_minima AND cantidad_stock > 0")->fetchColumn(),
                'sin_stock'   => (int)$this->db->query("SELECT COUNT(*) FROM insumos WHERE activo = 1 AND comercio_id = {$this->cid} AND cantidad_stock <= 0")->fetchColumn(),
            ];
        } catch (PDOException $e) {
            error_log("Error estadísticas generales: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerInsumos() {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT id, nombre, categoria FROM insumos WHERE comercio_id = {$this->cid} ORDER BY nombre"
            )->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function obtenerMovimientosPorDia($desde, $hasta) {
        $this->requireCid();
        $sql = "SELECT DATE(fecha) AS dia,
                    SUM(CASE WHEN tipo='entrada' THEN cantidad ELSE 0 END) AS entradas,
                    SUM(CASE WHEN tipo='salida'  THEN cantidad ELSE 0 END) AS salidas,
                    COUNT(*) AS total
                FROM movimientos_insumos
                WHERE comercio_id = {$this->cid} AND DATE(fecha) BETWEEN :desde AND :hasta
                GROUP BY DATE(fecha)
                ORDER BY dia ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':desde', $desde);
            $stmt->bindValue(':hasta', $hasta);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function obtenerTopInsumos($desde, $hasta, $limite = 5) {
        $this->requireCid();
        $sql = "SELECT i.nombre, i.categoria, COUNT(m.id) AS veces, SUM(m.cantidad) AS volumen
                FROM movimientos_insumos m
                JOIN insumos i ON m.id_insumo = i.id AND i.comercio_id = {$this->cid}
                WHERE m.comercio_id = {$this->cid} AND DATE(m.fecha) BETWEEN :desde AND :hasta
                GROUP BY m.id_insumo
                ORDER BY veces DESC
                LIMIT :lim";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':desde', $desde);
            $stmt->bindValue(':hasta', $hasta);
            $stmt->bindValue(':lim',   $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function obtenerDatosReporteX($fecha = null) {
        $this->requireCid();
        if (!$fecha) $fecha = date('Y-m-d');
        $p = [':fecha' => $fecha];

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(DISTINCT v.id) AS total_ventas,
                        COALESCE(SUM(v.total), 0) AS total_monto,
                        COALESCE(AVG(v.total), 0) AS ticket_promedio,
                        COALESCE(SUM(vd.cantidad),0) AS total_platos
                 FROM ventas v
                 LEFT JOIN venta_detalle vd ON v.id = vd.id_venta AND vd.comercio_id = {$this->cid}
                 WHERE v.comercio_id = {$this->cid} AND DATE(v.created_at) = :fecha AND v.estado != 'cancelada'"
            );
            $stmt->execute($p); $general = $stmt->fetch();

            $stmt = $this->db->prepare(
                "SELECT COALESCE(u.nombre,'Sin asignar') AS mesero,
                        COUNT(DISTINCT v.id) AS ventas,
                        COALESCE(SUM(v.total),0) AS monto,
                        COALESCE(SUM(vd.cantidad),0) AS platos
                 FROM ventas v
                 LEFT JOIN usuarios u ON v.id_usuario = u.id AND u.comercio_id = {$this->cid}
                 LEFT JOIN venta_detalle vd ON v.id = vd.id_venta AND vd.comercio_id = {$this->cid}
                 WHERE v.comercio_id = {$this->cid} AND DATE(v.created_at) = :fecha AND v.estado != 'cancelada'
                 GROUP BY v.id_usuario ORDER BY monto DESC"
            );
            $stmt->execute($p); $porMesero = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                "SELECT r.nombre, r.categoria,
                        SUM(vd.cantidad) AS unidades, SUM(vd.subtotal) AS monto
                 FROM venta_detalle vd
                 JOIN recetas r ON vd.id_receta = r.id AND r.comercio_id = {$this->cid}
                 JOIN ventas  v ON vd.id_venta  = v.id AND v.comercio_id = {$this->cid}
                 WHERE vd.comercio_id = {$this->cid} AND DATE(v.created_at) = :fecha AND v.estado != 'cancelada'
                 GROUP BY vd.id_receta ORDER BY unidades DESC LIMIT 20"
            );
            $stmt->execute($p); $porProducto = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                "SELECT HOUR(v.created_at) AS hora,
                        COUNT(v.id) AS ventas, COALESCE(SUM(v.total),0) AS monto
                 FROM ventas v
                 WHERE v.comercio_id = {$this->cid} AND DATE(v.created_at) = :fecha AND v.estado != 'cancelada'
                 GROUP BY HOUR(v.created_at) ORDER BY hora ASC"
            );
            $stmt->execute($p); $porHora = $stmt->fetchAll();

            return compact('general', 'porMesero', 'porProducto', 'porHora');
        } catch (PDOException $e) {
            error_log("Error obtenerDatosReporteX: " . $e->getMessage());
            return ['general'=>[], 'porMesero'=>[], 'porProducto'=>[], 'porHora'=>[]];
        }
    }

    public function obtenerUltimoCierreZ() {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT * FROM cierres_z WHERE comercio_id = {$this->cid} ORDER BY id DESC LIMIT 1"
            )->fetch();
        } catch (PDOException $e) { return null; }
    }

    public function generarCierreZ($id_usuario) {
        $this->requireCid();
        try {
            $ultimoZ  = $this->obtenerUltimoCierreZ();
            $hoy      = date('Y-m-d');
            $desde    = ($ultimoZ && substr($ultimoZ['fecha_hasta'], 0, 10) === $hoy)
                      ? $ultimoZ['fecha_hasta']
                      : $hoy . ' 00:00:00';
            $hasta    = date('Y-m-d H:i:s');
            $numeroZ  = (int)$this->db->query(
                "SELECT COALESCE(MAX(numero_z),0)+1 FROM cierres_z WHERE comercio_id = {$this->cid}"
            )->fetchColumn();
            $p        = [':desde' => $desde, ':hasta' => $hasta];

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS total_ventas, COALESCE(SUM(total),0) AS total_monto
                 FROM ventas WHERE comercio_id = {$this->cid}
                   AND created_at BETWEEN :desde AND :hasta AND estado != 'cancelada'"
            );
            $stmt->execute($p); $totales = $stmt->fetch();

            $stmt = $this->db->prepare(
                "SELECT COALESCE(u.nombre,'Sin asignar') AS mesero,
                        COUNT(v.id) AS ventas, COALESCE(SUM(v.total),0) AS monto
                 FROM ventas v LEFT JOIN usuarios u ON v.id_usuario = u.id AND u.comercio_id = {$this->cid}
                 WHERE v.comercio_id = {$this->cid}
                   AND v.created_at BETWEEN :desde AND :hasta AND v.estado != 'cancelada'
                 GROUP BY v.id_usuario ORDER BY monto DESC"
            );
            $stmt->execute($p); $porMesero = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                "SELECT r.nombre, r.categoria,
                        SUM(vd.cantidad) AS unidades, SUM(vd.subtotal) AS monto
                 FROM venta_detalle vd
                 JOIN recetas r ON vd.id_receta = r.id AND r.comercio_id = {$this->cid}
                 JOIN ventas  v ON vd.id_venta  = v.id AND v.comercio_id = {$this->cid}
                 WHERE vd.comercio_id = {$this->cid}
                   AND v.created_at BETWEEN :desde AND :hasta AND v.estado != 'cancelada'
                 GROUP BY vd.id_receta ORDER BY unidades DESC"
            );
            $stmt->execute($p); $porProducto = $stmt->fetchAll();

            $datos = json_encode(['general'=>$totales,'por_mesero'=>$porMesero,'por_producto'=>$porProducto]);

            $ins = $this->db->prepare(
                "INSERT INTO cierres_z (comercio_id,numero_z,fecha_desde,fecha_hasta,total_ventas,total_monto,id_usuario,datos_json)
                 VALUES ({$this->cid},:nz,:desde,:hasta,:tv,:tm,:uid,:dj)"
            );
            $ins->execute([
                ':nz' => $numeroZ, ':desde' => $desde, ':hasta' => $hasta,
                ':tv' => $totales['total_ventas'], ':tm' => $totales['total_monto'],
                ':uid' => $id_usuario, ':dj' => $datos,
            ]);
            $id = $this->db->lastInsertId();

            return ['ok'=>true,'id'=>$id,'numero_z'=>$numeroZ,'desde'=>$desde,'hasta'=>$hasta,
                    'totales'=>$totales,'porMesero'=>$porMesero,'porProducto'=>$porProducto];
        } catch (\Throwable $e) {
            error_log("Error generarCierreZ: " . $e->getMessage());
            return ['ok'=>false,'msg'=>$e->getMessage()];
        }
    }

    public function obtenerHistorialZ() {
        $this->requireCid();
        try {
            return $this->db->query(
                "SELECT z.*, u.nombre AS usuario_nombre
                 FROM cierres_z z LEFT JOIN usuarios u ON z.id_usuario = u.id AND u.comercio_id = {$this->cid}
                 WHERE z.comercio_id = {$this->cid} ORDER BY z.id DESC"
            )->fetchAll();
        } catch (PDOException $e) { return []; }
    }

    public function obtenerCierreZ($id) {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT z.*, u.nombre AS usuario_nombre
                 FROM cierres_z z LEFT JOIN usuarios u ON z.id_usuario = u.id AND u.comercio_id = {$this->cid}
                 WHERE z.id = :id AND z.comercio_id = {$this->cid}"
            );
            $stmt->execute([':id' => $id]);
            $z = $stmt->fetch();
            if ($z) $z['datos'] = json_decode($z['datos_json'], true);
            return $z;
        } catch (PDOException $e) { return null; }
    }

    public function obtenerEstadisticasMesZ(): array {
        $this->requireCid();
        $mesDesde = date('Y-m-01 00:00:00');
        $mesFin   = date('Y-m-t 23:59:59');
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS total_cierres,
                        COALESCE(SUM(total_monto),0) AS monto_acumulado,
                        COALESCE(SUM(total_ventas),0) AS total_ventas
                 FROM cierres_z WHERE comercio_id = {$this->cid} AND fecha_hasta BETWEEN :desde AND :hasta"
            );
            $stmt->execute([':desde' => $mesDesde, ':hasta' => $mesFin]);
            return $stmt->fetch() ?: ['total_cierres'=>0,'monto_acumulado'=>0,'total_ventas'=>0];
        } catch (\Throwable $e) { return ['total_cierres'=>0,'monto_acumulado'=>0,'total_ventas'=>0]; }
    }

    public function obtenerGraficaAnualZ(): array {
        return $this->obtenerGraficaAnioZ((int)date('Y'));
    }

    public function obtenerGraficaAnioZ(int $anio): array {
        $this->requireCid();
        try {
            $stmt = $this->db->prepare(
                "SELECT MONTH(fecha_hasta) AS mes,
                        COUNT(*) AS cierres,
                        COALESCE(SUM(total_monto),0) AS monto,
                        COALESCE(SUM(total_ventas),0) AS ventas
                 FROM cierres_z
                 WHERE comercio_id = {$this->cid} AND YEAR(fecha_hasta) = :anio
                 GROUP BY MONTH(fecha_hasta) ORDER BY mes ASC"
            );
            $stmt->execute([':anio' => $anio]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) { return []; }
    }

    public function obtenerAniosConDatos(): array {
        $this->requireCid();
        try {
            $rows = $this->db->query(
                "SELECT DISTINCT YEAR(fecha_hasta) AS anio FROM cierres_z
                 WHERE comercio_id = {$this->cid} ORDER BY anio DESC"
            )->fetchAll();
            return array_map('intval', array_column($rows, 'anio'));
        } catch (\Throwable $e) { return [(int)date('Y')]; }
    }
}
?>
