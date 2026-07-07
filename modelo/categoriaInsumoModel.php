<?php
// modelo/categoriaInsumoModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class CategoriaInsumoModel extends BaseModel {

    private const DEFAULTS = [
        ['nombre' => 'Carnes',                 'slug' => 'carnes',   'icono' => 'fa-drumstick-bite'],
        ['nombre' => 'Verduras',                'slug' => 'verduras', 'icono' => 'fa-leaf'],
        ['nombre' => 'Lácteos',                 'slug' => 'lacteos',  'icono' => 'fa-cheese'],
        ['nombre' => 'Granos y cereales',       'slug' => 'granos',   'icono' => 'fa-seedling'],
        ['nombre' => 'Especias y condimentos',  'slug' => 'especias', 'icono' => 'fa-mortar-pestle'],
        ['nombre' => 'Bebidas',                 'slug' => 'bebidas',  'icono' => 'fa-wine-bottle'],
        ['nombre' => 'Otros',                   'slug' => 'otros',    'icono' => 'fa-box'],
    ];

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS insumos_categorias (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    comercio_id INT NOT NULL,
                    nombre VARCHAR(60) NOT NULL,
                    slug VARCHAR(60) NOT NULL,
                    icono VARCHAR(40) NOT NULL DEFAULT 'fa-box',
                    orden INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_comercio (comercio_id),
                    UNIQUE KEY uniq_comercio_slug (comercio_id, slug)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {
            error_log('Error creando tabla insumos_categorias: ' . $e->getMessage());
        }
    }

    // Crea las 7 categorías clásicas la primera vez que un comercio usa este módulo,
    // para que los insumos ya existentes (categoria='carnes', etc.) sigan encajando.
    private function sembrarSiVacio(): void {
        $total = (int)$this->scalar("SELECT COUNT(*) FROM insumos_categorias WHERE comercio_id = ?", [$this->cid]);
        if ($total > 0) return;
        $orden = 0;
        foreach (self::DEFAULTS as $cat) {
            $this->query(
                "INSERT INTO insumos_categorias (comercio_id, nombre, slug, icono, orden) VALUES (?, ?, ?, ?, ?)",
                [$this->cid, $cat['nombre'], $cat['slug'], $cat['icono'], $orden++]
            );
        }
    }

    public function obtenerTodas(): array {
        $this->requireCid();
        $this->sembrarSiVacio();
        return $this->rows(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM insumos i WHERE i.comercio_id = c.comercio_id AND i.categoria = c.slug) AS total_insumos
             FROM insumos_categorias c
             WHERE c.comercio_id = ?
             ORDER BY c.orden ASC, c.id ASC",
            [$this->cid]
        );
    }

    // slug => ['label' => nombre, 'icon' => icono], listo para pintar el dropdown de Insumos
    public function obtenerParaSelect(): array {
        $this->requireCid();
        $this->sembrarSiVacio();
        $filas = $this->rows(
            "SELECT nombre, slug, icono FROM insumos_categorias WHERE comercio_id = ? ORDER BY orden ASC, id ASC",
            [$this->cid]
        );
        $out = [];
        foreach ($filas as $f) {
            $out[$f['slug']] = ['label' => $f['nombre'], 'icon' => $f['icono'] ?: 'fa-box'];
        }
        return $out;
    }

    private function slugify(string $texto): string {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto);
        return trim($texto, '_') ?: 'categoria';
    }

    private function slugUnico(string $base): string {
        $slug = $base;
        $i    = 2;
        while ((int)$this->scalar(
            "SELECT COUNT(*) FROM insumos_categorias WHERE comercio_id = ? AND slug = ?",
            [$this->cid, $slug]
        ) > 0) {
            $slug = $base . '_' . $i++;
        }
        return $slug;
    }

    public function crear(string $nombre, string $icono): array {
        $this->requireCid();
        $nombre = trim($nombre);
        if ($nombre === '') return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];

        $slug  = $this->slugUnico($this->slugify($nombre));
        $orden = (int)$this->scalar(
            "SELECT COALESCE(MAX(orden), -1) + 1 FROM insumos_categorias WHERE comercio_id = ?",
            [$this->cid]
        );

        try {
            $this->query(
                "INSERT INTO insumos_categorias (comercio_id, nombre, slug, icono, orden) VALUES (?, ?, ?, ?, ?)",
                [$this->cid, $nombre, $slug, $icono ?: 'fa-box', $orden]
            );
            return ['ok' => true, 'id' => (int)$this->db->lastInsertId()];
        } catch (\Throwable $e) {
            error_log('Error creando categoría de insumo: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'No se pudo crear la categoría.'];
        }
    }

    public function editar(int $id, string $nombre, string $icono): array {
        $this->requireCid();
        $nombre = trim($nombre);
        if ($nombre === '') return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];

        $cat = $this->find('insumos_categorias', $id);
        if (!$cat) return ['ok' => false, 'msg' => 'Categoría no encontrada.'];

        // El slug no cambia al renombrar: así los insumos ya guardados con
        // esa categoría no quedan huérfanos.
        $this->update('insumos_categorias', $id, [
            'nombre' => $nombre,
            'icono'  => $icono ?: 'fa-box',
        ]);
        return ['ok' => true];
    }

    public function eliminar(int $id): array {
        $this->requireCid();
        $cat = $this->find('insumos_categorias', $id);
        if (!$cat) return ['ok' => false, 'msg' => 'Categoría no encontrada.'];

        $enUso = (int)$this->scalar(
            "SELECT COUNT(*) FROM insumos WHERE comercio_id = ? AND categoria = ?",
            [$this->cid, $cat['slug']]
        );
        if ($enUso > 0) {
            return ['ok' => false, 'msg' => "No se puede eliminar: {$enUso} insumo(s) usan esta categoría."];
        }

        $this->delete('insumos_categorias', $id);
        return ['ok' => true];
    }
}
