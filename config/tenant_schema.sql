-- ============================================================
--  ChefControl — Schema de tenant (se ejecuta en cada nueva BD de restaurante)
--  NO incluye CREATE DATABASE ni usuario admin — se crean en el registro
-- ============================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `username`       VARCHAR(50)  NOT NULL UNIQUE,
    `password`       VARCHAR(255) NOT NULL,
    `nombre`         VARCHAR(100) NOT NULL,
    `email`          VARCHAR(100),
    `rol`            VARCHAR(30)  NOT NULL DEFAULT 'empleado',
    `avatar`         VARCHAR(255),
    `activo`         TINYINT(1)   NOT NULL DEFAULT 1,
    `fecha_creacion` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `ultimo_login`   TIMESTAMP    NULL DEFAULT NULL,
    `login_config`   TEXT         NULL DEFAULT NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_activo`   (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `comercio` (
    `id`                      INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`                  VARCHAR(150),
    `tipo`                    VARCHAR(60),
    `rut`                     VARCHAR(30),
    `direccion`               VARCHAR(255),
    `ciudad`                  VARCHAR(100),
    `telefono`                VARCHAR(30),
    `email`                   VARCHAR(100),
    `sitio_web`               VARCHAR(150),
    `eslogan`                 VARCHAR(255),
    `moneda`                  VARCHAR(10)  DEFAULT 'USD',
    `logo`                    VARCHAR(255),
    `horario_apertura`        VARCHAR(5)   DEFAULT '08:00',
    `horario_cierre`          VARCHAR(5)   DEFAULT '22:00',
    `btn_cancelar_venta`      TINYINT(1)   NOT NULL DEFAULT 0,
    `imprimir_comanda_auto`   TINYINT(1)   NOT NULL DEFAULT 0,
    `imprimir_factura_cobro`  TINYINT(1)   NOT NULL DEFAULT 0,
    `propina_activa`          TINYINT(1)   NOT NULL DEFAULT 0,
    `propina_porcentaje`      TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `propina_label_header`    TINYINT(1)   NOT NULL DEFAULT 1,
    `propina_distribucion`    VARCHAR(20)  NOT NULL DEFAULT 'individual',
    `propina_num_personas`    INT          NOT NULL DEFAULT 2,
    `propina_periodo_config`  TEXT         NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `proveedores` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`         VARCHAR(100) NOT NULL,
    `empresa`        VARCHAR(150),
    `telefono`       VARCHAR(30),
    `direccion`      VARCHAR(255),
    `correo`         VARCHAR(100),
    `categoria`      VARCHAR(60),
    `foto`           VARCHAR(255),
    `observacion`    TEXT,
    `nit_rut`        VARCHAR(30),
    `activo`         TINYINT(1)   NOT NULL DEFAULT 1,
    `fecha_creacion` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `insumos` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`           VARCHAR(100) NOT NULL,
    `descripcion`      TEXT,
    `categoria`        VARCHAR(60),
    `unidad_medida`    VARCHAR(30),
    `cantidad_stock`   DECIMAL(10,3) NOT NULL DEFAULT 0,
    `cantidad_minima`  DECIMAL(10,3) NOT NULL DEFAULT 0,
    `precio_unitario`  DECIMAL(10,2) NOT NULL DEFAULT 0,
    `id_proveedor`     INT NULL DEFAULT NULL,
    `activo`           TINYINT(1)    NOT NULL DEFAULT 1,
    `fecha_creacion`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activo`   (`activo`),
    INDEX `idx_proveedor`(`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `movimientos_insumos` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `id_insumo`      INT          NOT NULL,
    `tipo`           VARCHAR(20)  NOT NULL,
    `cantidad`       DECIMAL(10,3) NOT NULL,
    `stock_anterior` DECIMAL(10,3) NOT NULL DEFAULT 0,
    `stock_nuevo`    DECIMAL(10,3) NOT NULL DEFAULT 0,
    `descripcion`    TEXT,
    `fecha`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `id_usuario`     INT          NULL DEFAULT NULL,
    `id_proveedor`   INT          NULL DEFAULT NULL,
    INDEX `idx_insumo`  (`id_insumo`),
    INDEX `idx_tipo`    (`tipo`),
    INDEX `idx_fecha`   (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `recetas` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`              VARCHAR(150) NOT NULL,
    `descripcion`         TEXT,
    `categoria`           VARCHAR(60),
    `tiempo_preparacion`  INT UNSIGNED DEFAULT 0,
    `porciones`           INT UNSIGNED DEFAULT 1,
    `precio_venta`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    `activo`              TINYINT(1)    NOT NULL DEFAULT 1,
    `foto`                VARCHAR(255),
    `fecha_creacion`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activo`    (`activo`),
    INDEX `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `receta_insumos` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `id_receta`  INT           NOT NULL,
    `id_insumo`  INT           NOT NULL,
    `cantidad`   DECIMAL(10,3) NOT NULL DEFAULT 0,
    INDEX `idx_receta` (`id_receta`),
    INDEX `idx_insumo` (`id_insumo`),
    UNIQUE KEY `uk_receta_insumo` (`id_receta`, `id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mesas` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `numero`         INT          NOT NULL,
    `nombre`         VARCHAR(60),
    `capacidad`      INT UNSIGNED DEFAULT 4,
    `zona`           VARCHAR(60),
    `estado`         VARCHAR(20)  NOT NULL DEFAULT 'disponible',
    `activo`         TINYINT(1)   NOT NULL DEFAULT 1,
    `fecha_creacion` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_numero` (`numero`),
    INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `clientes` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(150) NOT NULL,
    `telefono`   VARCHAR(30),
    `tipo_doc`   VARCHAR(20)  NULL DEFAULT NULL,
    `num_doc`    VARCHAR(60)  NULL DEFAULT NULL,
    `email`      VARCHAR(100),
    `direccion`  TEXT,
    `notas`      TEXT,
    `activo`     TINYINT(1)   DEFAULT 1,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_nombre` (`nombre`),
    INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ventas` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `numero_orden`   VARCHAR(30)   NOT NULL UNIQUE,
    `total`          DECIMAL(10,2) NOT NULL DEFAULT 0,
    `metodo_pago`        VARCHAR(20)   NULL DEFAULT NULL,
    `pago_efectivo`      DECIMAL(10,2) NOT NULL DEFAULT 0,
    `pago_tarjeta`       DECIMAL(10,2) NOT NULL DEFAULT 0,
    `pago_transferencia` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `notas`          TEXT,
    `estado`         VARCHAR(20)   NOT NULL DEFAULT 'abierta',
    `id_usuario`     INT           NULL DEFAULT NULL,
    `id_mesa`        INT           NULL DEFAULT NULL,
    `cliente_id`     INT           NULL DEFAULT NULL,
    `fecha_creacion` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_estado`  (`estado`),
    INDEX `idx_usuario` (`id_usuario`),
    INDEX `idx_mesa`    (`id_mesa`),
    INDEX `idx_fecha`   (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `venta_detalle` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `id_venta`        INT           NOT NULL,
    `id_receta`       INT           NULL DEFAULT NULL,
    `cantidad`        INT UNSIGNED  NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX `idx_venta`  (`id_venta`),
    INDEX `idx_receta` (`id_receta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `propinas` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `monto`          DECIMAL(10,2) NOT NULL,
    `id_mesa`        INT           NULL DEFAULT NULL,
    `mesa_numero`    INT           NULL DEFAULT NULL,
    `mesa_nombre`    VARCHAR(60)   NULL DEFAULT NULL,
    `numero_orden`   VARCHAR(30)   NULL DEFAULT NULL,
    `id_usuario`     INT           NULL DEFAULT NULL,
    `fecha_creacion` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_fecha` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ingresos` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `radicado`        VARCHAR(20)   NOT NULL,
    `fecha`           DATE          NOT NULL,
    `tipo_documento`  VARCHAR(30),
    `serie`           VARCHAR(10),
    `numero`          VARCHAR(30),
    `concepto`        TEXT,
    `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    `impuesto`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    `total`           DECIMAL(10,2) NOT NULL DEFAULT 0,
    `estado`          VARCHAR(20)   NOT NULL DEFAULT 'Activo',
    `id_usuario`      INT           NULL DEFAULT NULL,
    `id_proveedor`    INT           NULL DEFAULT NULL,
    INDEX `idx_fecha`     (`fecha`),
    INDEX `idx_estado`    (`estado`),
    INDEX `idx_proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ingresos_items` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `id_ingreso`      INT           NOT NULL,
    `id_insumo`       INT           NULL,
    `articulo`        VARCHAR(150)  NOT NULL,
    `cantidad`        DECIMAL(10,3) NOT NULL DEFAULT 1,
    `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX `idx_ingreso` (`id_ingreso`),
    INDEX `idx_insumo`  (`id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lista_permisos` (
    `id`     INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(60) NOT NULL UNIQUE,
    `estado` TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `detalle_permiso` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `id_usuario`       INT NOT NULL,
    `id_permiso`       INT NOT NULL,
    `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_usuario_permiso` (`id_usuario`, `id_permiso`),
    INDEX `idx_usuario` (`id_usuario`),
    INDEX `idx_permiso` (`id_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mensajes_chat` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `id_remitente`    INT       NOT NULL,
    `id_destinatario` INT       NOT NULL,
    `mensaje`         TEXT      NOT NULL,
    `leido`           TINYINT(1) NOT NULL DEFAULT 0,
    `fecha_creacion`  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_conv`  (`id_remitente`, `id_destinatario`),
    INDEX `idx_noread`(`id_destinatario`, `leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_typing` (
    `id_usuario`      INT PRIMARY KEY,
    `id_destinatario` INT NOT NULL,
    `last_typed`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_dest` (`id_destinatario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pqrs` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
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
    INDEX `idx_estado` (`estado`),
    INDEX `idx_tipo`   (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pqrs_config` (
    `id`    INT AUTO_INCREMENT PRIMARY KEY,
    `token` VARCHAR(64) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cupones` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `codigo`      VARCHAR(8)    NOT NULL UNIQUE,
    `nombre`      VARCHAR(100)  NULL,
    `tipo`        VARCHAR(20)   NOT NULL DEFAULT 'porcentaje',
    `descuento`   DECIMAL(10,2) NOT NULL DEFAULT 0,
    `usos_max`    INT           NOT NULL DEFAULT 1,
    `usos_actual` INT           NOT NULL DEFAULT 0,
    `estado`      VARCHAR(20)   NOT NULL DEFAULT 'activo',
    `expira_en`   DATE          NULL,
    `id_receta`   INT           NULL,
    `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_estado` (`estado`),
    INDEX `idx_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dom_links` (
    `id`                 INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`             VARCHAR(100) NOT NULL,
    `descripcion`        VARCHAR(255),
    `token`              VARCHAR(64)  NOT NULL UNIQUE,
    `activo`             TINYINT(1)   DEFAULT 1,
    `mostrar_sin_stock`  TINYINT(1)   DEFAULT 0,
    `horario_desde`      VARCHAR(5)   DEFAULT NULL,
    `horario_hasta`      VARCHAR(5)   DEFAULT NULL,
    `categorias_activas` TEXT         DEFAULT NULL,
    `created_at`         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dom_pedidos` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `link_id`         INT          NOT NULL,
    `token_pedido`    VARCHAR(64)  NOT NULL UNIQUE,
    `nombre_cliente`  VARCHAR(100) NOT NULL,
    `telefono`        VARCHAR(30),
    `direccion`       TEXT,
    `notas`           TEXT,
    `tipo`            VARCHAR(20)  DEFAULT 'domicilio',
    `estado`          VARCHAR(20)  DEFAULT 'pendiente',
    `stock_reservado` TINYINT(1)   DEFAULT 0,
    `total`           DECIMAL(10,2) DEFAULT 0,
    `valor_domicilio` DECIMAL(10,2) DEFAULT NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_link`  (`link_id`),
    INDEX `idx_estado`(`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dom_items` (
    `id`        INT AUTO_INCREMENT PRIMARY KEY,
    `pedido_id` INT          NOT NULL,
    `receta_id` INT          NULL,
    `nombre`    VARCHAR(100) NOT NULL,
    `precio`    DECIMAL(10,2) DEFAULT 0,
    `cantidad`  INT           DEFAULT 1,
    INDEX `idx_pedido` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dom_chat` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `token_pedido` VARCHAR(64) NOT NULL,
    `de`           VARCHAR(10) NOT NULL,
    `mensaje`      TEXT        NOT NULL,
    `leido`        TINYINT(1)  DEFAULT 0,
    `leido_at`     TIMESTAMP   NULL DEFAULT NULL,
    `created_at`   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_token` (`token_pedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dom_historial` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `pedido_id`  INT        NOT NULL,
    `estado_de`  VARCHAR(20),
    `estado_a`   VARCHAR(20) NOT NULL,
    `leido`      TINYINT(1)  DEFAULT 0,
    `created_at` TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_leido`  (`leido`),
    INDEX `idx_pedido` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menus_digitales` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(150) NOT NULL,
    `descripcion` TEXT         NULL,
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `token`       VARCHAR(64)  NOT NULL UNIQUE,
    `mesa_id`     INT          NULL DEFAULT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`        INT AUTO_INCREMENT PRIMARY KEY,
    `menu_id`   INT NOT NULL,
    `receta_id` INT NOT NULL,
    `orden`     INT NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_menu_receta` (`menu_id`, `receta_id`),
    INDEX `idx_menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos estructurales: permisos del sistema
INSERT IGNORE INTO `lista_permisos` (`id`, `nombre`, `estado`) VALUES
(1, 'DASHBOARD',       1),
(2, 'RECETAS',         1),
(3, 'INVENTARIO',      1),
(4, 'REPORTES',        1),
(5, 'CONFIGURACIONES', 1),
(6, 'USUARIOS',        1),
(7, 'PERMISOS',        1);
