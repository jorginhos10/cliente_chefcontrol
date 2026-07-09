<?php
// modelo/inventarioSeccionModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class InventarioSeccionModel extends BaseModel {

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS inventario_secciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                comercio_id INT NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                parent_id INT NULL,
                icono VARCHAR(40) NOT NULL DEFAULT 'fa-door-open',
                orden INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_cid (comercio_id),
                INDEX idx_cid_parent (comercio_id, parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {
            error_log('InventarioSeccionModel::asegurarTabla — ' . $e->getMessage());
        }
    }

    // Secciones de primer nivel, cada una con su array 'subsecciones' anidado
    public function obtenerArbol(): array {
        $this->requireCid();
        $filas = $this->rows(
            "SELECT * FROM inventario_secciones WHERE comercio_id = ? ORDER BY orden, nombre",
            [$this->cid]
        );

        $porId = [];
        foreach ($filas as $fila) {
            $fila['subsecciones'] = [];
            $porId[$fila['id']] = $fila;
        }
        foreach ($porId as $id => $fila) {
            if (!empty($fila['parent_id']) && isset($porId[$fila['parent_id']])) {
                $porId[$fila['parent_id']]['subsecciones'][] = $fila;
            }
        }

        $arbol = [];
        foreach ($porId as $fila) {
            if (empty($fila['parent_id'])) $arbol[] = $fila;
        }
        return $arbol;
    }

    // Lista plana id => [label, nivel] para poblar el <select> del formulario de bienes
    public function obtenerParaSelect(): array {
        $opciones = [];
        foreach ($this->obtenerArbol() as $seccion) {
            $opciones[] = ['id' => $seccion['id'], 'label' => $seccion['nombre'], 'nivel' => 0];
            foreach ($seccion['subsecciones'] as $sub) {
                $opciones[] = ['id' => $sub['id'], 'label' => $sub['nombre'], 'nivel' => 1];
            }
        }
        return $opciones;
    }

    public function crear(string $nombre, ?int $parentId, string $icono): array {
        $this->requireCid();
        $nombre = trim($nombre);
        if ($nombre === '') return ['ok' => false, 'msg' => 'El nombre es obligatorio'];

        if ($parentId) {
            $padre = $this->find('inventario_secciones', $parentId);
            if (!$padre) return ['ok' => false, 'msg' => 'La sección padre no existe'];
            if (!empty($padre['parent_id'])) return ['ok' => false, 'msg' => 'Solo se permiten dos niveles: sección y subsección'];
        }

        $orden = (int)$this->scalar(
            "SELECT COALESCE(MAX(orden),0)+1 FROM inventario_secciones WHERE comercio_id = ? AND " .
                ($parentId ? "parent_id = ?" : "parent_id IS NULL"),
            $parentId ? [$this->cid, $parentId] : [$this->cid]
        );

        $id = $this->insert('inventario_secciones', [
            'nombre'    => $nombre,
            'parent_id' => $parentId ?: null,
            'icono'     => $icono ?: 'fa-door-open',
            'orden'     => $orden,
        ]);
        return ['ok' => (bool)$id, 'id' => $id];
    }

    public function editar(int $id, string $nombre, string $icono): array {
        $nombre = trim($nombre);
        if ($nombre === '') return ['ok' => false, 'msg' => 'El nombre es obligatorio'];
        $ok = $this->update('inventario_secciones', $id, ['nombre' => $nombre, 'icono' => $icono ?: 'fa-door-open']);
        return ['ok' => $ok];
    }

    public function eliminar(int $id): array {
        $this->requireCid();

        $hijos = (int)$this->scalar(
            "SELECT COUNT(*) FROM inventario_secciones WHERE parent_id = ? AND comercio_id = ?",
            [$id, $this->cid]
        );
        if ($hijos > 0) {
            return ['ok' => false, 'msg' => 'No puedes eliminar una sección que tiene subsecciones. Elimina primero las subsecciones.'];
        }

        $enUso = (int)$this->scalar(
            "SELECT COUNT(*) FROM inventario_inmobiliario WHERE seccion_id = ? AND comercio_id = ?",
            [$id, $this->cid]
        );
        if ($enUso > 0) {
            return ['ok' => false, 'msg' => 'No puedes eliminar una sección que tiene bienes asignados.'];
        }

        return ['ok' => $this->delete('inventario_secciones', $id)];
    }
}
