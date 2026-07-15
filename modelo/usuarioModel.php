<?php
// modelo/usuarioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class UsuarioModel extends BaseModel {

    private static bool $migrado = false;

    // Garantiza que exista usuarios.modulos_config antes de leerla/escribirla, sin
    // depender de que el popup de permisos haya corrido su propia migración antes.
    // Usa information_schema porque "ADD COLUMN IF NOT EXISTS" no es confiable en
    // la versión de MySQL/MariaDB del hosting.
    private function migrar(): void {
        if (self::$migrado) return;
        self::$migrado = true;
        try {
            $existe = $this->db->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
                   AND COLUMN_NAME = 'modulos_config'"
            )->fetchColumn();
            if (!$existe) {
                $this->db->exec("ALTER TABLE usuarios ADD COLUMN modulos_config TEXT NULL");
            }
        } catch (\Throwable $e) {
            error_log('UsuarioModel::migrar — ' . $e->getMessage());
        }
    }

    public function verificarUsuario(string $username, string $password): ?array {
        $this->requireCid();
        $u = $this->row(
            "SELECT id, username, nombre, email, password, rol, avatar, login_config
             FROM usuarios
             WHERE comercio_id = ? AND username = ? AND activo = 1 LIMIT 1",
            [$this->cid, $username]
        );
        if ($u && password_verify($password, $u['password'])) {
            $this->query("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?", [$u['id']]);
            unset($u['password']);
            return $u;
        }
        return null;
    }

    public function verificarRestriccionLogin(array $usuario): ?string {
        $cfg = json_decode($usuario['login_config'] ?? 'null', true);
        if (!$cfg || ($cfg['tipo'] ?? 'libre') === 'libre') return null;
        $diaMap = [1=>'lun',2=>'mar',3=>'mie',4=>'jue',5=>'vie',6=>'sab',7=>'dom'];
        $dias   = $cfg['dias'] ?? [];
        if (!empty($dias) && !in_array($diaMap[(int)date('N')] ?? '', $dias)) {
            return 'Tu jornada laboral no incluye el día de hoy.';
        }
        $hi  = $cfg['hora_inicio'] ?? '08:00';
        $hf  = $cfg['hora_fin']    ?? '18:00';
        $now = date('H:i');
        if ($now < $hi || $now >= $hf) {
            return "Tu jornada es de {$hi} a {$hf}. Ahora son las {$now}.";
        }
        return null;
    }

    public function obtenerTodos(): array {
        $this->requireCid();
        return $this->rows(
            "SELECT id, username, nombre, email, rol, avatar, activo, propietario, created_at, ultimo_login
             FROM usuarios WHERE comercio_id = ? ORDER BY propietario DESC, created_at ASC",
            [$this->cid]
        );
    }

    public function obtenerPorId(int $id): ?array {
        return $this->find('usuarios', $id);
    }

    public function esPropietario(int $id): bool {
        $this->requireCid();
        return (bool)$this->scalar(
            "SELECT propietario FROM usuarios WHERE id = ? AND comercio_id = ?",
            [$id, $this->cid]
        );
    }

    // Todos los módulos que existen en el sistema (deben coincidir con los
    // usados en el sidebar/permisoPopupController para que el cálculo de
    // "desactivados por defecto" sea correcto).
    private const TODOS_MODULOS = [
        'dashboard','ventas','cocina','mesas','menu-digital','domicilios','clientes',
        'cupones','pqrs','propinas','recetas','insumos','insumos-internos','inventario',
        'proveedores','inventario-inmobiliario','ingresos','perdidas','reportes',
        'chat','notificaciones',
    ];

    // Módulos habilitados por defecto según el rol (el resto queda desactivado).
    // El rol "admin" no se restringe nunca (ver sidebar.php), así que no necesita entrada.
    private const MODULOS_POR_ROL = [
        'cocina'     => ['dashboard','cocina','chat','notificaciones'],
        'inventario' => ['dashboard','insumos','insumos-internos','inventario',
                          'inventario-inmobiliario','proveedores','perdidas','chat','notificaciones'],
        'mesero'     => ['dashboard','ventas','mesas','domicilios','clientes',
                          'cupones','pqrs','propinas','chat','notificaciones'],
    ];

    // Calcula el JSON de módulos desactivados por defecto para un rol (null si no aplica).
    private function modulosConfigPorDefecto(string $rol): ?string {
        if (!isset(self::MODULOS_POR_ROL[$rol])) return null;
        $habilitados  = self::MODULOS_POR_ROL[$rol];
        $desactivados = array_values(array_diff(self::TODOS_MODULOS, $habilitados));
        return empty($desactivados) ? null : json_encode($desactivados);
    }

    public function crearUsuario(array $datos): int {
        $this->migrar();
        return $this->insert('usuarios', [
            'username'      => $datos['username'],
            'password'      => password_hash($datos['password'], PASSWORD_DEFAULT),
            'nombre'        => $datos['nombre'],
            'email'         => $datos['email']       ?? null,
            'rol'           => $datos['rol']         ?? 'empleado',
            'avatar'        => $datos['avatar']      ?? null,
            'activo'        => $datos['activo']      ?? 1,
            'propietario'   => $datos['propietario'] ?? 0,
            'modulos_config'=> $this->modulosConfigPorDefecto($datos['rol'] ?? ''),
        ]);
    }

    public function actualizarUsuario(int $id, array $datos): bool {
        $this->migrar();
        $update = [
            'nombre' => $datos['nombre'],
            'email'  => $datos['email'],
            'rol'    => $datos['rol'],
            'activo' => $datos['activo'],
        ];

        // Si el rol cambió, se resetean los permisos a los valores por defecto de ese
        // rol — los toggles manuales que tenía con el rol anterior ya no aplican.
        $actual = $this->obtenerPorId($id);
        if ($actual && $actual['rol'] !== $datos['rol']) {
            $update['modulos_config'] = $this->modulosConfigPorDefecto($datos['rol']);
        }

        return $this->update('usuarios', $id, $update);
    }

    public function eliminarUsuario(int $id): bool {
        return $this->delete('usuarios', $id);
    }

    public function actualizarEstado(int $id, int $activo): bool {
        return $this->update('usuarios', $id, ['activo' => $activo]);
    }

    public function actualizarContrasena(int $id, string $nueva): bool {
        return $this->update('usuarios', $id, [
            'password' => password_hash($nueva, PASSWORD_DEFAULT),
        ]);
    }

    public function actualizarAvatar(int $id, string $avatar): bool {
        return $this->update('usuarios', $id, ['avatar' => $avatar]);
    }

    public function actualizarPerfilPropio(int $id, array $datos): bool {
        $campos = ['nombre' => $datos['nombre'], 'email' => $datos['email']];
        if (!empty($datos['avatar'])) $campos['avatar'] = $datos['avatar'];
        return $this->update('usuarios', $id, $campos);
    }

    public function guardarLoginConfig(int $id, ?string $config): bool {
        return $this->update('usuarios', $id, ['login_config' => $config]);
    }

    public function verificarContrasena(int $id, string $contrasena): bool {
        $this->requireCid();
        $row = $this->row(
            "SELECT password FROM usuarios WHERE id = ? AND comercio_id = ?",
            [$id, $this->cid]
        );
        return $row && password_verify($contrasena, $row['password']);
    }

    public function existeUsername(string $username, int $excludeId = 0): bool {
        return $this->exists('usuarios', 'username', $username, $excludeId);
    }

    public function existeEmail(string $email, int $excludeId = 0): bool {
        return $this->exists('usuarios', 'email', $email, $excludeId);
    }

    public function obtenerEstadisticas(): array {
        $this->requireCid();
        return $this->row(
            "SELECT COUNT(*) total, SUM(activo=1) activos,
                    SUM(rol='admin') administradores,
                    SUM(rol='cocina') cocina,
                    SUM(rol='inventario') inventario
             FROM usuarios WHERE comercio_id = ?",
            [$this->cid]
        ) ?? ['total'=>0,'activos'=>0,'administradores'=>0,'cocina'=>0,'inventario'=>0];
    }

    public function buscar(string $termino): array {
        $this->requireCid();
        $t = "%{$termino}%";
        return $this->rows(
            "SELECT id, username, nombre, email, rol, avatar, activo
             FROM usuarios
             WHERE comercio_id = ? AND (username LIKE ? OR nombre LIKE ? OR email LIKE ?)
             ORDER BY nombre",
            [$this->cid, $t, $t, $t]
        );
    }

    public function obtenerRecientes(int $limite = 5): array {
        $this->requireCid();
        return $this->rows(
            "SELECT id, username, nombre, email, rol, avatar, created_at
             FROM usuarios WHERE comercio_id = ? ORDER BY created_at DESC LIMIT ?",
            [$this->cid, $limite]
        );
    }
}
