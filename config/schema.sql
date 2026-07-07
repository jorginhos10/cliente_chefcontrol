-- ============================================================
--  ChefControl — Schema unificado multi-tenant
--  comercio_id en TODAS las tablas — aislamiento total
-- ============================================================

CREATE DATABASE IF NOT EXISTS `chefcontrol`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `chefcontrol`;

-- ── Catálogo global de permisos (única tabla sin comercio_id) ────────────────
CREATE TABLE IF NOT EXISTS `lista_permisos` (
    `id`     INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(60) NOT NULL UNIQUE,
    `estado` TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Tenants — registro + configuración del restaurante ──────────────────────
CREATE TABLE IF NOT EXISTS `comercios` (
    `id`                      INT AUTO_INCREMENT PRIMARY KEY,
    `slug`                    VARCHAR(64)      NOT NULL,
    `nombre`                  VARCHAR(150)     NOT NULL,
    `tipo`                    VARCHAR(60),
    `rut`                     VARCHAR(30),
    `direccion`               VARCHAR(255),
    `ciudad`                  VARCHAR(100),
    `telefono`                VARCHAR(30),
    `email`                   VARCHAR(100)     NOT NULL,
    `sitio_web`               VARCHAR(150),
    `eslogan`                 VARCHAR(255),
    `moneda`                  VARCHAR(10)      NOT NULL DEFAULT 'COP',
    `logo`                    VARCHAR(255),
    `horario_apertura`        VARCHAR(5)       NOT NULL DEFAULT '08:00',
    `horario_cierre`          VARCHAR(5)       NOT NULL DEFAULT '22:00',
    `btn_cancelar_venta`      TINYINT(1)       NOT NULL DEFAULT 0,
    `imprimir_comanda_auto`   TINYINT(1)       NOT NULL DEFAULT 0,
    `imprimir_factura_cobro`  TINYINT(1)       NOT NULL DEFAULT 0,
    `propina_activa`          TINYINT(1)       NOT NULL DEFAULT 0,
    `propina_porcentaje`      TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `propina_label_header`    TINYINT(1)       NOT NULL DEFAULT 1,
    `propina_distribucion`    VARCHAR(20)      NOT NULL DEFAULT 'individual',
    `propina_num_personas`    INT              NOT NULL DEFAULT 2,
    `propina_periodo_config`  TEXT,
    `activo`                  TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`              TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_slug`  (`slug`),
    UNIQUE KEY `uq_email` (`email`),
    INDEX `idx_activo`    (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Usuarios ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`  INT          NOT NULL,
    `username`     VARCHAR(50)  NOT NULL,
    `password`     VARCHAR(255) NOT NULL,
    `nombre`       VARCHAR(100) NOT NULL,
    `email`        VARCHAR(100),
    `rol`          VARCHAR(30)  NOT NULL DEFAULT 'empleado',
    `avatar`       VARCHAR(255),
    `activo`       TINYINT(1)   NOT NULL DEFAULT 1,
    `login_config` TEXT,
    `ultimo_login` DATETIME     DEFAULT NULL,
    `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user`         (`comercio_id`, `username`),
    INDEX `idx_cid`              (`comercio_id`),
    INDEX `idx_cid_activo`       (`comercio_id`, `activo`),
    CONSTRAINT `fk_usuarios_cid` FOREIGN KEY (`comercio_id`) REFERENCES `comercios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Permisos por usuario ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `detalle_permiso` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`      INT NOT NULL,
    `id_usuario`       INT NOT NULL,
    `id_permiso`       INT NOT NULL,
    `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_user_perm`  (`comercio_id`, `id_usuario`, `id_permiso`),
    INDEX `idx_cid`            (`comercio_id`),
    INDEX `idx_cid_usuario`    (`comercio_id`, `id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Proveedores ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `proveedores` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT          NOT NULL,
    `nombre`      VARCHAR(100) NOT NULL,
    `empresa`     VARCHAR(150),
    `telefono`    VARCHAR(30),
    `direccion`   VARCHAR(255),
    `correo`      VARCHAR(100),
    `categoria`   VARCHAR(60),
    `foto`        VARCHAR(255),
    `observacion` TEXT,
    `nit_rut`     VARCHAR(30),
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_activo` (`comercio_id`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Insumos ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `insumos` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`     INT           NOT NULL,
    `nombre`          VARCHAR(100)  NOT NULL,
    `descripcion`     TEXT,
    `categoria`       VARCHAR(60),
    `unidad_medida`   VARCHAR(30),
    `cantidad_stock`  DECIMAL(10,3) NOT NULL DEFAULT 0,
    `cantidad_minima` DECIMAL(10,3) NOT NULL DEFAULT 0,
    `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `id_proveedor`    INT           NULL,
    `activo`          TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_activo` (`comercio_id`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `movimientos_insumos` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`    INT           NOT NULL,
    `id_insumo`      INT           NOT NULL,
    `tipo`           VARCHAR(20)   NOT NULL,
    `cantidad`       DECIMAL(10,3) NOT NULL,
    `stock_anterior` DECIMAL(10,3) NOT NULL DEFAULT 0,
    `stock_nuevo`    DECIMAL(10,3) NOT NULL DEFAULT 0,
    `descripcion`    TEXT,
    `id_usuario`     INT           NULL,
    `id_proveedor`   INT           NULL,
    `fecha`          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_insumo` (`comercio_id`, `id_insumo`),
    INDEX `idx_cid_fecha`  (`comercio_id`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `perdidas` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT           NOT NULL,
    `id_insumo`   INT           NULL,
    `cantidad`    DECIMAL(10,3) NOT NULL DEFAULT 0,
    `motivo`      VARCHAR(150)  NOT NULL,
    `descripcion` TEXT,
    `id_usuario`  INT           NULL,
    `fecha`       DATE          NOT NULL,
    `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`       (`comercio_id`),
    INDEX `idx_cid_fecha` (`comercio_id`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Recetas ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `recetas` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`        INT           NOT NULL,
    `nombre`             VARCHAR(150)  NOT NULL,
    `descripcion`        TEXT,
    `categoria`          VARCHAR(60),
    `tiempo_preparacion` INT UNSIGNED  DEFAULT 0,
    `porciones`          INT UNSIGNED  DEFAULT 1,
    `precio_venta`       DECIMAL(10,2) NOT NULL DEFAULT 0,
    `activo`             TINYINT(1)    NOT NULL DEFAULT 1,
    `foto`               VARCHAR(255),
    `created_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`           (`comercio_id`),
    INDEX `idx_cid_activo`    (`comercio_id`, `activo`),
    INDEX `idx_cid_categoria` (`comercio_id`, `categoria`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- detalle de receta — comercio_id incluido para aislamiento total
CREATE TABLE IF NOT EXISTS `receta_insumos` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT           NOT NULL,
    `id_receta`   INT           NOT NULL,
    `id_insumo`   INT           NOT NULL,
    `cantidad`    DECIMAL(10,3) NOT NULL DEFAULT 0,
    UNIQUE KEY `uq_receta_insumo` (`comercio_id`, `id_receta`, `id_insumo`),
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_receta` (`comercio_id`, `id_receta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Mesas ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mesas` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT          NOT NULL,
    `numero`      INT          NOT NULL,
    `nombre`      VARCHAR(60),
    `capacidad`   INT UNSIGNED DEFAULT 4,
    `zona`        VARCHAR(60),
    `estado`      VARCHAR(20)  NOT NULL DEFAULT 'disponible',
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_numero`     (`comercio_id`, `numero`),
    INDEX `idx_cid`            (`comercio_id`),
    INDEX `idx_cid_estado`     (`comercio_id`, `estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Clientes ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT          NOT NULL,
    `nombre`      VARCHAR(150) NOT NULL,
    `telefono`    VARCHAR(30),
    `tipo_doc`    VARCHAR(20),
    `num_doc`     VARCHAR(60),
    `email`       VARCHAR(100),
    `direccion`   TEXT,
    `notas`       TEXT,
    `activo`      TINYINT(1)   DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_nombre` (`comercio_id`, `nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ventas ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ventas` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`  INT           NOT NULL,
    `numero_orden` VARCHAR(30)   NOT NULL,
    `total`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    `notas`        TEXT,
    `estado`       VARCHAR(20)   NOT NULL DEFAULT 'abierta',
    `id_usuario`   INT           NULL,
    `id_mesa`      INT           NULL,
    `cliente_id`   INT           NULL,
    `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_orden`      (`comercio_id`, `numero_orden`),
    INDEX `idx_cid`            (`comercio_id`),
    INDEX `idx_cid_estado`     (`comercio_id`, `estado`),
    INDEX `idx_cid_mesa`       (`comercio_id`, `id_mesa`, `estado`),
    INDEX `idx_cid_fecha`      (`comercio_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- detalle de venta — comercio_id incluido
CREATE TABLE IF NOT EXISTS `venta_detalle` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`     INT           NOT NULL,
    `id_venta`        INT           NOT NULL,
    `id_receta`       INT           NULL,
    `nombre_item`     VARCHAR(150),
    `cantidad`        INT UNSIGNED  NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_venta`  (`comercio_id`, `id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Propinas ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `propinas` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`  INT           NOT NULL,
    `monto`        DECIMAL(10,2) NOT NULL,
    `id_mesa`      INT           NULL,
    `mesa_numero`  INT           NULL,
    `mesa_nombre`  VARCHAR(60)   NULL,
    `numero_orden` VARCHAR(30)   NULL,
    `id_usuario`   INT           NULL,
    `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`       (`comercio_id`),
    INDEX `idx_cid_fecha` (`comercio_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ingresos ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ingresos` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`    INT           NOT NULL,
    `radicado`       VARCHAR(20)   NOT NULL,
    `fecha`          DATE          NOT NULL,
    `tipo_documento` VARCHAR(30),
    `serie`          VARCHAR(10),
    `numero`         VARCHAR(30),
    `concepto`       TEXT,
    `subtotal`       DECIMAL(10,2) NOT NULL DEFAULT 0,
    `impuesto`       DECIMAL(10,2) NOT NULL DEFAULT 0,
    `total`          DECIMAL(10,2) NOT NULL DEFAULT 0,
    `estado`         VARCHAR(20)   NOT NULL DEFAULT 'Activo',
    `id_usuario`     INT           NULL,
    `id_proveedor`   INT           NULL,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_fecha`  (`comercio_id`, `fecha`),
    INDEX `idx_cid_estado` (`comercio_id`, `estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- detalle de ingreso — comercio_id incluido
CREATE TABLE IF NOT EXISTS `ingresos_items` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`     INT           NOT NULL,
    `id_ingreso`      INT           NOT NULL,
    `articulo`        VARCHAR(150)  NOT NULL,
    `cantidad`        DECIMAL(10,3) NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX `idx_cid`         (`comercio_id`),
    INDEX `idx_cid_ingreso` (`comercio_id`, `id_ingreso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PQRS ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pqrs` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`  INT          NOT NULL,
    `nombre`       VARCHAR(100) NOT NULL,
    `email`        VARCHAR(100),
    `telefono`     VARCHAR(30),
    `tipo`         VARCHAR(20)  NOT NULL DEFAULT 'sugerencia',
    `calificacion` TINYINT(1)   NOT NULL DEFAULT 5,
    `mensaje`      TEXT         NOT NULL,
    `estado`       VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
    `respuesta`    TEXT,
    `leido`        TINYINT(1)   DEFAULT 0,
    `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_estado` (`comercio_id`, `estado`),
    INDEX `idx_cid_leido`  (`comercio_id`, `leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pqrs_config` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT         NOT NULL UNIQUE,
    `token`       VARCHAR(64) NOT NULL UNIQUE,
    INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cupones ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cupones` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT           NOT NULL,
    `codigo`      VARCHAR(8)    NOT NULL,
    `nombre`      VARCHAR(100),
    `tipo`        VARCHAR(20)   NOT NULL DEFAULT 'porcentaje',
    `descuento`   DECIMAL(10,2) NOT NULL DEFAULT 0,
    `usos_max`    INT           NOT NULL DEFAULT 1,
    `usos_actual` INT           NOT NULL DEFAULT 0,
    `estado`      VARCHAR(20)   NOT NULL DEFAULT 'activo',
    `expira_en`   DATE          NULL,
    `id_receta`   INT           NULL,
    `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_codigo`     (`comercio_id`, `codigo`),
    INDEX `idx_cid`            (`comercio_id`),
    INDEX `idx_cid_estado`     (`comercio_id`, `estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Domicilios ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dom_links` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`        INT          NOT NULL,
    `nombre`             VARCHAR(100) NOT NULL,
    `descripcion`        VARCHAR(255),
    `token`              VARCHAR(64)  NOT NULL,
    `activo`             TINYINT(1)   DEFAULT 1,
    `mostrar_sin_stock`  TINYINT(1)   DEFAULT 0,
    `horario_desde`      VARCHAR(5),
    `horario_hasta`      VARCHAR(5),
    `categorias_activas` TEXT,
    `created_at`         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_token`      (`token`),
    INDEX `idx_cid`            (`comercio_id`),
    INDEX `idx_cid_activo`     (`comercio_id`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dom_pedidos` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`     INT           NOT NULL,
    `link_id`         INT           NOT NULL,
    `token_pedido`    VARCHAR(64)   NOT NULL,
    `nombre_cliente`  VARCHAR(100)  NOT NULL,
    `telefono`        VARCHAR(30),
    `direccion`       TEXT,
    `notas`           TEXT,
    `tipo`            VARCHAR(20)   DEFAULT 'domicilio',
    `estado`          VARCHAR(20)   DEFAULT 'pendiente',
    `stock_reservado` TINYINT(1)    DEFAULT 0,
    `total`           DECIMAL(10,2) DEFAULT 0,
    `valor_domicilio` DECIMAL(10,2) DEFAULT NULL,
    `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_token_pedido` (`token_pedido`),
    INDEX `idx_cid`              (`comercio_id`),
    INDEX `idx_cid_estado`       (`comercio_id`, `estado`),
    INDEX `idx_cid_fecha`        (`comercio_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- items del pedido — comercio_id incluido
CREATE TABLE IF NOT EXISTS `dom_items` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT           NOT NULL,
    `pedido_id`   INT           NOT NULL,
    `receta_id`   INT           NULL,
    `nombre`      VARCHAR(100)  NOT NULL,
    `precio`      DECIMAL(10,2) DEFAULT 0,
    `cantidad`    INT           DEFAULT 1,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_pedido` (`comercio_id`, `pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- chat de pedido — comercio_id incluido
CREATE TABLE IF NOT EXISTS `dom_chat` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`  INT        NOT NULL,
    `token_pedido` VARCHAR(64) NOT NULL,
    `de`           VARCHAR(10) NOT NULL,
    `mensaje`      TEXT        NOT NULL,
    `leido`        TINYINT(1)  DEFAULT 0,
    `leido_at`     TIMESTAMP   NULL,
    `created_at`   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`             (`comercio_id`),
    INDEX `idx_cid_token_pedido`(`comercio_id`, `token_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- historial de estados — comercio_id incluido
CREATE TABLE IF NOT EXISTS `dom_historial` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT        NOT NULL,
    `pedido_id`   INT        NOT NULL,
    `estado_de`   VARCHAR(20),
    `estado_a`    VARCHAR(20) NOT NULL,
    `leido`       TINYINT(1)  DEFAULT 0,
    `created_at`  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`        (`comercio_id`),
    INDEX `idx_cid_pedido` (`comercio_id`, `pedido_id`),
    INDEX `idx_cid_leido`  (`comercio_id`, `leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Menús digitales ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `menus_digitales` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT          NOT NULL,
    `nombre`      VARCHAR(150) NOT NULL,
    `descripcion` TEXT,
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `token`       VARCHAR(64)  NOT NULL,
    `mesa_id`     INT          NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_token`      (`token`),
    INDEX `idx_cid`            (`comercio_id`),
    INDEX `idx_cid_activo`     (`comercio_id`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- items del menú — comercio_id incluido
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT NOT NULL,
    `menu_id`     INT NOT NULL,
    `receta_id`   INT NOT NULL,
    `orden`       INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uq_menu_receta` (`comercio_id`, `menu_id`, `receta_id`),
    INDEX `idx_cid`      (`comercio_id`),
    INDEX `idx_cid_menu` (`comercio_id`, `menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Chat interno ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mensajes_chat` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id`     INT        NOT NULL,
    `id_remitente`    INT        NOT NULL,
    `id_destinatario` INT        NOT NULL,
    `mensaje`         TEXT       NOT NULL,
    `leido`           TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cid`          (`comercio_id`),
    INDEX `idx_cid_conv`     (`comercio_id`, `id_remitente`, `id_destinatario`),
    INDEX `idx_cid_noread`   (`comercio_id`, `id_destinatario`, `leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_typing` (
    `id_usuario`      INT PRIMARY KEY,
    `comercio_id`     INT NOT NULL,
    `id_destinatario` INT NOT NULL,
    `last_typed`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cid_dest` (`comercio_id`, `id_destinatario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed global de permisos ──────────────────────────────────────────────────
INSERT IGNORE INTO `lista_permisos` (`id`, `nombre`, `estado`) VALUES
(1,'DASHBOARD',1),(2,'RECETAS',1),(3,'INVENTARIO',1),(4,'REPORTES',1),
(5,'CONFIGURACIONES',1),(6,'USUARIOS',1),(7,'PERMISOS',1);
