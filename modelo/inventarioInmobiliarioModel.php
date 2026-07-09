<?php
// modelo/inventarioInmobiliarioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class InventarioInmobiliarioModel extends BaseModel {

    public function __construct() {
        parent::__construct();
        $this->asegurarColumnaSeccion();
    }

    // La tabla ya existe en producción sin columna de sección; la agregamos
    // en caliente la primera vez que se detecta ausente (mismo enfoque que
    // InventarioSeccionModel::asegurarTabla para no depender de un paso manual).
    private function asegurarColumnaSeccion(): void {
        try {
            $col = $this->db->query("SHOW COLUMNS FROM inventario_inmobiliario LIKE 'seccion_id'")->fetch();
            if (!$col) {
                $this->db->exec("ALTER TABLE inventario_inmobiliario
                    ADD COLUMN seccion_id INT NULL AFTER foto,
                    ADD INDEX idx_cid_seccion (comercio_id, seccion_id)");
            }
        } catch (\Throwable $e) {
            error_log('InventarioInmobiliarioModel::asegurarColumnaSeccion — ' . $e->getMessage());
        }
    }

    public function obtenerTodos() {
        $this->requireCid();
        return $this->rows(
            "SELECT b.*,
                    s.nombre    AS seccion_nombre,
                    s.parent_id AS seccion_parent_id,
                    p.nombre    AS seccion_padre_nombre
             FROM inventario_inmobiliario b
             LEFT JOIN inventario_secciones s ON s.id = b.seccion_id AND s.comercio_id = ?
             LEFT JOIN inventario_secciones p ON p.id = s.parent_id
             WHERE b.comercio_id = ?
             ORDER BY b.created_at DESC",
            [$this->cid, $this->cid]
        );
    }

    public function obtenerPorId(int $id) {
        return $this->find('inventario_inmobiliario', $id);
    }

    public function obtenerEstadisticas() {
        $this->requireCid();
        return $this->row(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
                    COALESCE(SUM(CASE WHEN activo = 1 THEN valor_tasado ELSE 0 END), 0) AS valor_total
             FROM inventario_inmobiliario WHERE comercio_id = ?",
            [$this->cid]
        ) ?? ['total' => 0, 'activos' => 0, 'valor_total' => 0];
    }

    public function crear(array $datos): int {
        return $this->insert('inventario_inmobiliario', $datos);
    }

    public function actualizarEstado(int $id, int $activo): bool {
        return $this->update('inventario_inmobiliario', $id, ['activo' => $activo]);
    }

    public function eliminar(int $id): bool {
        return $this->delete('inventario_inmobiliario', $id);
    }
}
