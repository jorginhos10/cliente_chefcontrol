<?php
// modelo/categoriaRecetaModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class CategoriaRecetaModel extends BaseModel {

    private const DEFAULTS = [
        ['nombre' => 'Entrada',      'slug' => 'entrada',      'icono' => 'fa-utensils'],
        ['nombre' => 'Plato Fuerte', 'slug' => 'plato_fuerte', 'icono' => 'fa-drumstick-bite'],
        ['nombre' => 'Postre',       'slug' => 'postre',       'icono' => 'fa-ice-cream'],
        ['nombre' => 'Bebida',       'slug' => 'bebida',       'icono' => 'fa-wine-glass'],
        ['nombre' => 'Snack',        'slug' => 'snack',        'icono' => 'fa-cookie-bite'],
        ['nombre' => 'Otro',         'slug' => 'otro',         'icono' => 'fa-bowl-food'],
    ];

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS recetas_categorias (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    comercio_id INT NOT NULL,
                    nombre VARCHAR(60) NOT NULL,
                    slug VARCHAR(60) NOT NULL,
                    icono VARCHAR(40) NOT NULL DEFAULT 'fa-utensils',
                    orden INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_comercio (comercio_id),
                    UNIQUE KEY uniq_comercio_slug (comercio_id, slug)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (\Throwable $e) {
            error_log('Error creando tabla recetas_categorias: ' . $e->getMessage());
        }
    }

    // Crea las 6 categorías clásicas la primera vez que un comercio usa este módulo,
    // para que las recetas ya existentes (categoria='entrada', etc.) sigan encajando.
    private function sembrarSiVacio(): void {
        $total = (int)$this->scalar("SELECT COUNT(*) FROM recetas_categorias WHERE comercio_id = ?", [$this->cid]);
        if ($total > 0) return;
        $orden = 0;
        foreach (self::DEFAULTS as $cat) {
            $this->query(
                "INSERT INTO recetas_categorias (comercio_id, nombre, slug, icono, orden) VALUES (?, ?, ?, ?, ?)",
                [$this->cid, $cat['nombre'], $cat['slug'], $cat['icono'], $orden++]
            );
        }
    }

    public function obtenerTodas(): array {
        $this->requireCid();
        $this->sembrarSiVacio();
        return $this->rows(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM recetas r WHERE r.comercio_id = c.comercio_id AND r.categoria = c.slug) AS total_recetas
             FROM recetas_categorias c
             WHERE c.comercio_id = ?
             ORDER BY c.orden ASC, c.id ASC",
            [$this->cid]
        );
    }

    // slug => ['label' => nombre, 'icon' => icono], listo para pintar el dropdown de Recetas
    public function obtenerParaSelect(): array {
        $this->requireCid();
        $this->sembrarSiVacio();
        $filas = $this->rows(
            "SELECT nombre, slug, icono FROM recetas_categorias WHERE comercio_id = ? ORDER BY orden ASC, id ASC",
            [$this->cid]
        );
        $out = [];
        foreach ($filas as $f) {
            $out[$f['slug']] = ['label' => $f['nombre'], 'icon' => $f['icono'] ?: 'fa-utensils'];
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
            "SELECT COUNT(*) FROM recetas_categorias WHERE comercio_id = ? AND slug = ?",
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
            "SELECT COALESCE(MAX(orden), -1) + 1 FROM recetas_categorias WHERE comercio_id = ?",
            [$this->cid]
        );

        try {
            $this->query(
                "INSERT INTO recetas_categorias (comercio_id, nombre, slug, icono, orden) VALUES (?, ?, ?, ?, ?)",
                [$this->cid, $nombre, $slug, $icono ?: 'fa-utensils', $orden]
            );
            return ['ok' => true, 'id' => (int)$this->db->lastInsertId()];
        } catch (\Throwable $e) {
            error_log('Error creando categoría de receta: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'No se pudo crear la categoría.'];
        }
    }

    public function editar(int $id, string $nombre, string $icono): array {
        $this->requireCid();
        $nombre = trim($nombre);
        if ($nombre === '') return ['ok' => false, 'msg' => 'El nombre es obligatorio.'];

        $cat = $this->find('recetas_categorias', $id);
        if (!$cat) return ['ok' => false, 'msg' => 'Categoría no encontrada.'];

        // El slug no cambia al renombrar: así las recetas ya guardadas con
        // esa categoría no quedan huérfanas.
        $this->update('recetas_categorias', $id, [
            'nombre' => $nombre,
            'icono'  => $icono ?: 'fa-utensils',
        ]);
        return ['ok' => true];
    }

    public function eliminar(int $id): array {
        $this->requireCid();
        $cat = $this->find('recetas_categorias', $id);
        if (!$cat) return ['ok' => false, 'msg' => 'Categoría no encontrada.'];

        $enUso = (int)$this->scalar(
            "SELECT COUNT(*) FROM recetas WHERE comercio_id = ? AND categoria = ?",
            [$this->cid, $cat['slug']]
        );
        if ($enUso > 0) {
            return ['ok' => false, 'msg' => "No se puede eliminar: {$enUso} receta(s) usan esta categoría."];
        }

        $this->delete('recetas_categorias', $id);
        return ['ok' => true];
    }
}
