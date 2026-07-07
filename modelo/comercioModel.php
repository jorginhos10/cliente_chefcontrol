<?php
// modelo/comercioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class ComercioModel extends BaseModel {

    // ── Lectura del propio tenant ─────────────────────────────────────────────

    public function obtener(): ?array {
        return $this->row("SELECT * FROM comercios WHERE id=?", [$this->cid]);
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
