<?php
// modelo/inventarioInmobiliarioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class InventarioInmobiliarioModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    public function obtenerTodos() {
        $this->requireCid();
        return $this->rows(
            "SELECT * FROM inventario_inmobiliario WHERE comercio_id = ? ORDER BY created_at DESC",
            [$this->cid]
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
