<?php
// core/BaseModel.php

require_once __DIR__ . '/../config/config.php';

abstract class BaseModel {

    protected PDO $db;
    protected int $cid;

    public function __construct() {
        $this->db  = DB::get();
        $this->cid = (int)($_SESSION['comercio_id'] ?? 0);
    }

    // ── Guard — lanza excepción si no hay tenant en sesión ────────────────────
    // Llamar al inicio de cualquier método que lea o escriba datos de tenant.
    protected function requireCid(): void {
        if ($this->cid === 0) {
            error_log('BaseModel::requireCid — intento de query sin comercio_id en sesión. Clase: ' . static::class);
            http_response_code(403);
            exit('Acceso no autorizado.');
        }
    }

    // ── Helpers de query ──────────────────────────────────────────────────────

    // Ejecuta un statement y devuelve el objeto
    protected function query(string $sql, array $p = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt;
    }

    // Fetch una fila
    protected function row(string $sql, array $p = []): ?array {
        $r = $this->query($sql, $p)->fetch();
        return $r ?: null;
    }

    // Fetch todas las filas
    protected function rows(string $sql, array $p = []): array {
        return $this->query($sql, $p)->fetchAll();
    }

    // Fetch un escalar
    protected function scalar(string $sql, array $p = []): mixed {
        return $this->query($sql, $p)->fetchColumn();
    }

    // ── Métodos seguros con comercio_id garantizado ───────────────────────────

    // Busca un registro por id verificando que pertenezca al tenant activo
    protected function find(string $tabla, int $id): ?array {
        $this->requireCid();
        return $this->row(
            "SELECT * FROM `{$tabla}` WHERE id = ? AND comercio_id = ? LIMIT 1",
            [$id, $this->cid]
        );
    }

    // Inserta un array de datos inyectando comercio_id automáticamente
    protected function insert(string $tabla, array $datos): int {
        $this->requireCid();
        $datos['comercio_id'] = $this->cid;
        $cols   = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($datos)));
        $placeholders = implode(', ', array_fill(0, count($datos), '?'));
        $this->query("INSERT INTO `{$tabla}` ({$cols}) VALUES ({$placeholders})", array_values($datos));
        return (int)$this->db->lastInsertId();
    }

    // Actualiza por id garantizando que sea del tenant activo
    protected function update(string $tabla, int $id, array $datos): bool {
        $this->requireCid();
        $sets   = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($datos)));
        $params = array_values($datos);
        $params[] = $id;
        $params[] = $this->cid;
        return (bool)$this->query(
            "UPDATE `{$tabla}` SET {$sets} WHERE id = ? AND comercio_id = ?",
            $params
        );
    }

    // Elimina por id garantizando que sea del tenant activo
    protected function delete(string $tabla, int $id): bool {
        $this->requireCid();
        return (bool)$this->query(
            "DELETE FROM `{$tabla}` WHERE id = ? AND comercio_id = ?",
            [$id, $this->cid]
        );
    }

    // Verifica existencia de un campo con valor en el tenant activo
    protected function exists(string $tabla, string $campo, mixed $valor, int $excludeId = 0): bool {
        $this->requireCid();
        return (bool)$this->scalar(
            "SELECT COUNT(*) FROM `{$tabla}` WHERE comercio_id = ? AND `{$campo}` = ? AND id != ?",
            [$this->cid, $valor, $excludeId]
        );
    }
}
