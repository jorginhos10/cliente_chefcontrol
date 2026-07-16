<?php
// modelo/comercioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class ComercioModel extends BaseModel {

    // ── Lectura del propio tenant ─────────────────────────────────────────────

    public function obtener(): ?array {
        return $this->row("SELECT * FROM comercios WHERE id=?", [$this->cid]);
    }

    // Parámetros de impresión (CSS @page + tamaño de letra + ancho en caracteres
    // del ticket monoespaciado) según el tamaño de papel configurado. Usado por
    // vista/ventas/index.php, vista/ventas/listado.php y vista/ventas/mesa.php
    // para que los 3 recibos usen el mismo tamaño de papel de forma consistente.
    public static function parametrosPapel(?string $tamano): array {
        // OJO: charWidth se ajustó con margen de seguridad tras probarlo en impresora
        // real — muchas apps/drivers de impresión térmica en Android no tienen
        // "Courier New" instalada y sustituyen una fuente monoespaciada más ancha,
        // así que caben bastantes menos caracteres por línea de lo que da la cuenta
        // "teórica" (ancho de papel ÷ tamaño de letra). Mejor un poco más angosto
        // que arriesgarse a que las líneas se corten y salgan partidas en dos.
        $presets = [
            '58mm'  => ['pageSize' => '58mm auto', 'margin' => '2mm', 'fontSize' => '11pt', 'charWidth' => 20],
            '80mm'  => ['pageSize' => '80mm auto', 'margin' => '3mm', 'fontSize' => '13pt', 'charWidth' => 26],
            'carta' => ['pageSize' => 'letter',    'margin' => '12mm','fontSize' => '12pt', 'charWidth' => 40],
        ];
        return $presets[$tamano] ?? $presets['80mm'];
    }

    // Indica si un módulo está habilitado para este comercio (override de admin + plan).
    // No considera restricciones por usuario individual, solo si el negocio "tiene" el módulo.
    public function moduloHabilitado(string $slug): bool {
        $this->requireCid();
        try {
            $row = $this->row("SELECT plan, modulos_config FROM comercios WHERE id=?", [$this->cid]);
            if (!$row) return true; // no se pudo verificar: no bloquear por precaución

            $desactivados = $row['modulos_config'] ? (json_decode($row['modulos_config'], true) ?? []) : [];
            if (in_array($slug, $desactivados, true)) return false;

            $opts  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
            $dbSup = new PDO("mysql:host=" . Config::DB_HOST . ";dbname=" . Config::DB_NAME_SUP . ";charset=utf8mb4",
                             Config::DB_USER, Config::DB_PASS, $opts);
            $ps = $dbSup->prepare("SELECT modulos FROM planes WHERE slug=? LIMIT 1");
            $ps->execute([$row['plan'] ?? 'gratuito']);
            $json = $ps->fetchColumn();
            if ($json === false || $json === null) return true; // plan sin configurar: sin restricción

            $modulos = json_decode($json, true) ?? [];
            return in_array($slug, $modulos, true);
        } catch (\Throwable $e) {
            error_log('ComercioModel::moduloHabilitado — ' . $e->getMessage());
            return true; // no se pudo verificar: no bloquear por precaución
        }
    }

    public function actualizar(array $datos): bool {
        $campos = [
            'nombre','tipo','rut','direccion','ciudad','telefono','email',
            'sitio_web','eslogan','moneda','horario_apertura','horario_cierre',
            'btn_cancelar_venta','imprimir_comanda_auto','imprimir_factura_cobro',
            'propina_activa','propina_porcentaje','propina_label_header',
            'propina_distribucion','propina_num_personas','propina_periodo_config',
        ];
        $sets   = array_map(fn($c) => "{$c}=?", $campos);
        $params = array_map(fn($c) => $datos[$c] ?? null, $campos);
        $params[] = $this->cid;
        return (bool)$this->query(
            "UPDATE comercios SET " . implode(',', $sets) . " WHERE id=?",
            $params
        );
    }

    public function actualizarLogo(string $logo): bool {
        return (bool)$this->query("UPDATE comercios SET logo=? WHERE id=?", [$logo, $this->cid]);
    }

    // Actualiza un único campo de configuración (usado por los toggles de Facturación)
    public function actualizarCampo(string $campo, $valor): bool {
        $permitidos = [
            'btn_cancelar_venta', 'imprimir_comanda_auto', 'imprimir_factura_cobro',
            'propina_activa', 'propina_porcentaje', 'propina_label_header',
            'propina_distribucion', 'propina_num_personas', 'propina_periodo_config',
            'cierre_auto_activo', 'cierre_auto_hora', 'tamano_papel',
        ];
        if (!in_array($campo, $permitidos, true)) return false;
        return (bool)$this->query("UPDATE comercios SET {$campo}=? WHERE id=?", [$valor, $this->cid]);
    }

    // ── Código de facturación (iniciales únicas por negocio) ─────────────────

    private static bool $migradoCodigo = false;

    private function migrarCodigoFacturacion(): void {
        if (self::$migradoCodigo) return;
        self::$migradoCodigo = true;
        try {
            $existe = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comercios'
                   AND COLUMN_NAME = 'codigo_facturacion'"
            )->fetchColumn();
            if (!$existe) {
                $this->db->exec("ALTER TABLE comercios ADD COLUMN codigo_facturacion VARCHAR(10) NULL DEFAULT NULL");
            }
            $existeIdx = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comercios'
                   AND INDEX_NAME = 'uq_codigo_facturacion'"
            )->fetchColumn();
            if (!$existeIdx) {
                $this->db->exec("ALTER TABLE comercios ADD UNIQUE INDEX uq_codigo_facturacion (codigo_facturacion)");
            }
        } catch (\Throwable $e) {
            error_log('ComercioModel::migrarCodigoFacturacion — ' . $e->getMessage());
        }
    }

    // Convierte el nombre del negocio en 2 iniciales:
    // - Ignora conectores cortos (el, la, los, las, de, del, y) SOLO si al quitarlos
    //   quedan al menos 2 palabras útiles (ej. "El Turrón de Azúcar" -> "Turrón","Azúcar" -> TA).
    // - Si al quitarlos queda menos de 2 palabras, usa las palabras originales sin filtrar
    //   (ej. "El Turrón" -> "El","Turrón" -> ET, porque filtrar dejaría solo 1 palabra).
    // - Si el nombre es una sola palabra, usa sus 2 primeras letras (ej. "Wingstop" -> WI).
    public static function calcularIniciales(string $nombre): string {
        $stopwords = ['el','la','los','las','de','del','y','e'];
        $palabras  = array_values(array_filter(preg_split('/\s+/', trim($nombre)), fn($p) => $p !== ''));
        if (empty($palabras)) return 'CC';

        $filtradas = array_values(array_filter(
            $palabras, fn($p) => !in_array(mb_strtolower($p), $stopwords, true)
        ));
        $usar = count($filtradas) >= 2 ? $filtradas : $palabras;

        $iniciales = '';
        foreach (array_slice($usar, 0, 2) as $palabra) {
            $iniciales .= mb_strtoupper(mb_substr($palabra, 0, 1));
        }
        if (mb_strlen($iniciales) < 2) {
            $iniciales = mb_strtoupper(mb_substr($usar[0], 0, 2));
        }
        return $iniciales ?: 'CC';
    }

    // Devuelve el código de facturación de este comercio (ej. "ET"), asignándolo
    // la primera vez a partir del nombre y garantizando que no se repita entre
    // restaurantes (si ya está tomado, agrega un sufijo numérico: ET2, ET3...).
    public function obtenerCodigoFacturacion(): string {
        $this->requireCid();
        $this->migrarCodigoFacturacion();

        $actual = $this->scalar("SELECT codigo_facturacion FROM comercios WHERE id=?", [$this->cid]);
        if ($actual) return $actual;

        $nombre = (string)$this->scalar("SELECT nombre FROM comercios WHERE id=?", [$this->cid]);
        $base   = self::calcularIniciales($nombre !== '' ? $nombre : 'Negocio');

        $codigo = $base;
        $sufijo = 1;
        while (true) {
            try {
                $this->query("UPDATE comercios SET codigo_facturacion=? WHERE id=?", [$codigo, $this->cid]);
                return $codigo;
            } catch (\PDOException $e) {
                // Choque de UNIQUE INDEX: otro comercio ya tiene ese código, probar el siguiente sufijo.
                $sufijo++;
                $codigo = $base . $sufijo;
                if ($sufijo > 50) return $base . uniqid(); // salvaguarda extrema, no debería llegar aquí
            }
        }
    }

    // ── Métodos estáticos (sin tenant en sesión) ──────────────────────────────

    public static function findBySlug(string $slug): ?array {
        $stmt = DB::get()->prepare(
            "SELECT id, slug, nombre, email, activo FROM comercios WHERE slug=? LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Busca un usuario activo por username o email en todos los tenants.
     * Devuelve [usuario_row] + 'comercio' key, o null si no encontrado.
     * Si hay múltiples coincidencias por username, pide usar email.
     */
    public static function findUserGlobal(string $identifier): array|null {
        $db   = DB::get();
        $col  = str_contains($identifier, '@') ? 'email' : 'username';
        $stmt = $db->prepare(
            "SELECT u.id, u.username, u.nombre, u.email, u.password, u.rol,
                    u.avatar, u.login_config, u.comercio_id
             FROM usuarios u
             JOIN comercios c ON c.id = u.comercio_id AND c.activo = 1
             WHERE u.{$col} = ? AND u.activo = 1"
        );
        $stmt->execute([$identifier]);
        $rows = $stmt->fetchAll();

        if (count($rows) === 0) return null;

        // Si busca por username y hay varios tenants con ese username, ambiguo
        if ($col === 'username' && count($rows) > 1) {
            return ['ambiguo' => true];
        }

        $u = $rows[0];
        $cid = (int)$u['comercio_id'];
        $stmtC = $db->prepare("SELECT id, slug, nombre, activo FROM comercios WHERE id = ?");
        $stmtC->execute([$cid]);
        $u['comercio'] = $stmtC->fetch();
        return $u;
    }

    public static function slugExiste(string $slug): bool {
        $stmt = DB::get()->prepare("SELECT COUNT(*) FROM comercios WHERE slug=?");
        $stmt->execute([$slug]);
        return (bool)$stmt->fetchColumn();
    }

    public static function emailExiste(string $email): bool {
        $stmt = DB::get()->prepare("SELECT COUNT(*) FROM comercios WHERE email=?");
        $stmt->execute([$email]);
        return (bool)$stmt->fetchColumn();
    }
}
