<?php
// modelo/usuarioModel.php

require_once __DIR__ . '/../core/BaseModel.php';

class UsuarioModel extends BaseModel {

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

    public function crearUsuario(array $datos): int {
        return $this->insert('usuarios', [
            'username'    => $datos['username'],
            'password'    => password_hash($datos['password'], PASSWORD_DEFAULT),
            'nombre'      => $datos['nombre'],
            'email'       => $datos['email']       ?? null,
            'rol'         => $datos['rol']         ?? 'empleado',
            'avatar'      => $datos['avatar']      ?? null,
            'activo'      => $datos['activo']      ?? 1,
            'propietario' => $datos['propietario'] ?? 0,
        ]);
    }

    public function actualizarUsuario(int $id, array $datos): bool {
        return $this->update('usuarios', $id, [
            'nombre' => $datos['nombre'],
            'email'  => $datos['email'],
            'rol'    => $datos['rol'],
            'activo' => $datos['activo'],
        ]);
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
