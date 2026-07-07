<?php
// config/master_db.php — Conexión a la BD maestra multi-tenant (auto-inicializa si no existe)

require_once __DIR__ . '/config.php';

class MasterDB {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            self::$instance = self::conectar();
        }
        return self::$instance;
    }

    /** Conexión sin dbname, útil para CREATE DATABASE */
    public static function getRaw(): PDO {
        $dsn = "mysql:host=" . Config::DB_HOST . ";charset=" . Config::DB_CHARSET;
        $pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private static function conectar(): PDO {
        $dsn = "mysql:host=" . Config::DB_HOST
             . ";dbname=" . Config::MASTER_DB_NAME
             . ";charset=" . Config::DB_CHARSET;
        try {
            $pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            // Si la BD maestra no existe la creamos automáticamente
            if (strpos($e->getMessage(), 'Unknown database') !== false
             || $e->getCode() == 1049) {
                return self::inicializar();
            }
            throw $e;
        }
    }

    /** Crea la BD maestra y sus tablas la primera vez */
    private static function inicializar(): PDO {
        $raw = self::getRaw();
        $master = Config::MASTER_DB_NAME;

        $raw->exec("CREATE DATABASE IF NOT EXISTS `{$master}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $raw->exec("USE `{$master}`");

        $raw->exec("CREATE TABLE IF NOT EXISTS `comercios` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `nombre`     VARCHAR(150) NOT NULL,
            `email`      VARCHAR(100) NOT NULL,
            `db_name`    VARCHAR(64)  NOT NULL UNIQUE,
            `activo`     TINYINT(1)   NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_email`   (`email`),
            INDEX `idx_db_name` (`db_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $raw->exec("CREATE TABLE IF NOT EXISTS `usuarios_map` (
            `username`    VARCHAR(50)  NOT NULL,
            `email`       VARCHAR(100) DEFAULT NULL,
            `db_name`     VARCHAR(64)  NOT NULL,
            `comercio_id` INT          NOT NULL,
            PRIMARY KEY (`username`),
            INDEX `idx_db_name`    (`db_name`),
            INDEX `idx_comercio_id`(`comercio_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $raw->exec("CREATE TABLE IF NOT EXISTS `token_map` (
            `token`   VARCHAR(64) NOT NULL,
            `db_name` VARCHAR(64) NOT NULL,
            `tipo`    VARCHAR(20) NOT NULL DEFAULT 'general',
            PRIMARY KEY (`token`),
            INDEX `idx_db_name` (`db_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Registrar el tenant original si existe
        try {
            $raw->exec("INSERT IGNORE INTO `{$master}`.`comercios` (id, nombre, email, db_name)
                        VALUES (1, 'Mi Restaurante', 'admin@chefcontrol.com', 'chefcontrol')");
            $raw->exec("INSERT IGNORE INTO `{$master}`.`usuarios_map` (username, email, db_name, comercio_id)
                        VALUES ('admin', 'admin@chefcontrol.com', 'chefcontrol', 1)");
            // Migrar tokens existentes del tenant original
            $raw->exec("INSERT IGNORE INTO `{$master}`.`token_map` (token, db_name, tipo)
                        SELECT token, 'chefcontrol', 'domicilio' FROM `chefcontrol`.`dom_links`");
            $raw->exec("INSERT IGNORE INTO `{$master}`.`token_map` (token, db_name, tipo)
                        SELECT token, 'chefcontrol', 'menu' FROM `chefcontrol`.`menus_digitales`");
            $raw->exec("INSERT IGNORE INTO `{$master}`.`token_map` (token, db_name, tipo)
                        SELECT token, 'chefcontrol', 'pqrs' FROM `chefcontrol`.`pqrs_config`");
        } catch (\Throwable $e) {
            // Si chefcontrol no existe aún (instalación nueva), ignorar
        }

        $raw->exec("USE `{$master}`");
        $raw->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $raw;
    }

    // ── Métodos públicos ──────────────────────────────────────────────────────

    public static function findTenantByUsername(string $username): ?array {
        try {
            $stmt = self::get()->prepare(
                "SELECT db_name, comercio_id FROM usuarios_map WHERE username = :u LIMIT 1"
            );
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("MasterDB::findTenantByUsername: " . $e->getMessage());
            return null;
        }
    }

    public static function usernameExists(string $username): bool {
        try {
            $stmt = self::get()->prepare("SELECT COUNT(*) FROM usuarios_map WHERE username = :u");
            $stmt->execute([':u' => $username]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function emailComercioExists(string $email): bool {
        try {
            $stmt = self::get()->prepare("SELECT COUNT(*) FROM comercios WHERE email = :e");
            $stmt->execute([':e' => $email]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function registrarComercio(string $nombre, string $email, string $dbName): int {
        $stmt = self::get()->prepare(
            "INSERT INTO comercios (nombre, email, db_name) VALUES (:n, :e, :d)"
        );
        $stmt->execute([':n' => $nombre, ':e' => $email, ':d' => $dbName]);
        return (int)self::get()->lastInsertId();
    }

    public static function registrarUsuario(string $username, ?string $email, string $dbName, int $comercioId): bool {
        try {
            $stmt = self::get()->prepare(
                "INSERT INTO usuarios_map (username, email, db_name, comercio_id)
                 VALUES (:u, :e, :d, :c)
                 ON DUPLICATE KEY UPDATE db_name = :d2, comercio_id = :c2"
            );
            return $stmt->execute([
                ':u' => $username, ':e' => $email,
                ':d' => $dbName,   ':c' => $comercioId,
                ':d2' => $dbName,  ':c2' => $comercioId,
            ]);
        } catch (PDOException $e) {
            error_log("MasterDB::registrarUsuario: " . $e->getMessage());
            return false;
        }
    }

    public static function eliminarUsuario(string $username): void {
        try {
            self::get()->prepare("DELETE FROM usuarios_map WHERE username = :u")
                ->execute([':u' => $username]);
        } catch (PDOException $e) {
            error_log("MasterDB::eliminarUsuario: " . $e->getMessage());
        }
    }

    public static function getDbByToken(string $token): ?string {
        try {
            $stmt = self::get()->prepare(
                "SELECT db_name FROM token_map WHERE token = :t LIMIT 1"
            );
            $stmt->execute([':t' => $token]);
            $row = $stmt->fetch();
            return $row ? $row['db_name'] : null;
        } catch (PDOException $e) {
            error_log("MasterDB::getDbByToken: " . $e->getMessage());
            return null;
        }
    }

    public static function registrarToken(string $token, string $dbName, string $tipo = 'general'): void {
        try {
            self::get()->prepare(
                "INSERT IGNORE INTO token_map (token, db_name, tipo) VALUES (:t, :d, :tp)"
            )->execute([':t' => $token, ':d' => $dbName, ':tp' => $tipo]);
        } catch (PDOException $e) {
            error_log("MasterDB::registrarToken: " . $e->getMessage());
        }
    }

    public static function eliminarToken(string $token): void {
        try {
            self::get()->prepare("DELETE FROM token_map WHERE token = :t")
                ->execute([':t' => $token]);
        } catch (PDOException $e) {
            error_log("MasterDB::eliminarToken: " . $e->getMessage());
        }
    }
}
