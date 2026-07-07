-- ============================================================
--  ChefControl — Script de migración a schema unificado
--  Ejecutar DESPUÉS de crear la nueva BD con schema.sql
--  Migra datos de: chefcontrol (original) + cc_* (tenants)
-- ============================================================

USE `chefcontrol`;

-- ── 1. Migrar tenant original (chefcontrol) ─────────────────────────────────
-- Insertar el comercio original desde su tabla `comercio`
INSERT IGNORE INTO `comercios` (
    id, slug, nombre, tipo, rut, direccion, ciudad, telefono, email,
    sitio_web, eslogan, moneda, logo, horario_apertura, horario_cierre,
    btn_cancelar_venta, imprimir_comanda_auto, imprimir_factura_cobro,
    propina_activa, propina_porcentaje, propina_label_header,
    propina_distribucion, propina_num_personas, propina_periodo_config,
    activo
)
SELECT
    1 AS id,
    LOWER(REGEXP_REPLACE(IFNULL(nombre,'mi-restaurante'), '[^a-zA-Z0-9]', '-')) AS slug,
    nombre, tipo, rut, direccion, ciudad, telefono, email,
    sitio_web, eslogan, moneda, logo, horario_apertura, horario_cierre,
    btn_cancelar_venta, imprimir_comanda_auto, imprimir_factura_cobro,
    propina_activa, propina_porcentaje, propina_label_header,
    propina_distribucion, propina_num_personas, propina_periodo_config,
    1
FROM `chefcontrol`.`comercio`
LIMIT 1;

-- Migrar usuarios del tenant original
INSERT IGNORE INTO `usuarios`
    (comercio_id, username, password, nombre, email, rol, avatar, activo, login_config, ultimo_login, created_at)
SELECT
    1, username, password, nombre, email, rol, avatar, activo, login_config, ultimo_login, fecha_creacion
FROM `chefcontrol`.`usuarios`;

-- Migrar recetas
INSERT IGNORE INTO `recetas`
    (id, comercio_id, nombre, descripcion, categoria, tiempo_preparacion, porciones, precio_venta, activo, foto, created_at)
SELECT id, 1, nombre, descripcion, categoria, tiempo_preparacion, porciones, precio_venta, activo, foto, fecha_creacion
FROM `chefcontrol`.`recetas`;

INSERT IGNORE INTO `receta_insumos` SELECT * FROM `chefcontrol`.`receta_insumos`;

INSERT IGNORE INTO `insumos`
    (id, comercio_id, nombre, descripcion, categoria, unidad_medida, cantidad_stock, cantidad_minima, precio_unitario, id_proveedor, activo, created_at)
SELECT id, 1, nombre, descripcion, categoria, unidad_medida, cantidad_stock, cantidad_minima, precio_unitario, id_proveedor, activo, fecha_creacion
FROM `chefcontrol`.`insumos`;

INSERT IGNORE INTO `movimientos_insumos`
    (id, comercio_id, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario, id_proveedor, fecha)
SELECT id, 1, id_insumo, tipo, cantidad, stock_anterior, stock_nuevo, descripcion, id_usuario, id_proveedor, fecha
FROM `chefcontrol`.`movimientos_insumos`;

INSERT IGNORE INTO `mesas`
    (id, comercio_id, numero, nombre, capacidad, zona, estado, activo, created_at)
SELECT id, 1, numero, nombre, capacidad, zona, estado, activo, fecha_creacion
FROM `chefcontrol`.`mesas`;

INSERT IGNORE INTO `clientes`
    (id, comercio_id, nombre, telefono, tipo_doc, num_doc, email, direccion, notas, activo, created_at, updated_at)
SELECT id, 1, nombre, telefono, tipo_doc, num_doc, email, direccion, notas, activo, created_at, updated_at
FROM `chefcontrol`.`clientes`;

INSERT IGNORE INTO `ventas`
    (id, comercio_id, numero_orden, total, notas, estado, id_usuario, id_mesa, cliente_id, created_at)
SELECT id, 1, numero_orden, total, notas, estado, id_usuario, id_mesa, cliente_id, fecha_creacion
FROM `chefcontrol`.`ventas`;

INSERT IGNORE INTO `venta_detalle` SELECT * FROM `chefcontrol`.`venta_detalle`;

INSERT IGNORE INTO `propinas`
    (id, comercio_id, monto, id_mesa, mesa_numero, mesa_nombre, numero_orden, id_usuario, created_at)
SELECT id, 1, monto, id_mesa, mesa_numero, mesa_nombre, numero_orden, id_usuario, fecha_creacion
FROM `chefcontrol`.`propinas`;

INSERT IGNORE INTO `ingresos`
    (id, comercio_id, radicado, fecha, tipo_documento, serie, numero, concepto, subtotal, impuesto, total, estado, id_usuario, id_proveedor)
SELECT id, 1, radicado, fecha, tipo_documento, serie, numero, concepto, subtotal, impuesto, total, estado, id_usuario, id_proveedor
FROM `chefcontrol`.`ingresos`;

INSERT IGNORE INTO `ingresos_items` SELECT * FROM `chefcontrol`.`ingresos_items`;

INSERT IGNORE INTO `proveedores`
    (id, comercio_id, nombre, empresa, telefono, direccion, correo, categoria, foto, observacion, nit_rut, activo, created_at)
SELECT id, 1, nombre, empresa, telefono, direccion, correo, categoria, foto, observacion, nit_rut, activo, fecha_creacion
FROM `chefcontrol`.`proveedores`;

INSERT IGNORE INTO `pqrs`
    (id, comercio_id, nombre, email, telefono, tipo, calificacion, mensaje, estado, respuesta, leido, created_at, updated_at)
SELECT id, 1, nombre, email, telefono, tipo, calificacion, mensaje, estado, respuesta, leido, created_at, updated_at
FROM `chefcontrol`.`pqrs`;

INSERT IGNORE INTO `pqrs_config` (comercio_id, token)
SELECT 1, token FROM `chefcontrol`.`pqrs_config` LIMIT 1;

INSERT IGNORE INTO `cupones`
    (id, comercio_id, codigo, nombre, tipo, descuento, usos_max, usos_actual, estado, expira_en, id_receta, created_at)
SELECT id, 1, codigo, nombre, tipo, descuento, usos_max, usos_actual, estado, expira_en, id_receta, created_at
FROM `chefcontrol`.`cupones`;

INSERT IGNORE INTO `dom_links`
    (id, comercio_id, nombre, descripcion, token, activo, mostrar_sin_stock, horario_desde, horario_hasta, categorias_activas, created_at)
SELECT id, 1, nombre, descripcion, token, activo, mostrar_sin_stock, horario_desde, horario_hasta, categorias_activas, created_at
FROM `chefcontrol`.`dom_links`;

INSERT IGNORE INTO `dom_pedidos`
    (id, comercio_id, link_id, token_pedido, nombre_cliente, telefono, direccion, notas, tipo, estado, stock_reservado, total, valor_domicilio, created_at, updated_at)
SELECT id, 1, link_id, token_pedido, nombre_cliente, telefono, direccion, notas, tipo, estado, stock_reservado, total, valor_domicilio, created_at, updated_at
FROM `chefcontrol`.`dom_pedidos`;

INSERT IGNORE INTO `dom_items` SELECT * FROM `chefcontrol`.`dom_items`;
INSERT IGNORE INTO `dom_chat` SELECT * FROM `chefcontrol`.`dom_chat`;
INSERT IGNORE INTO `dom_historial` SELECT * FROM `chefcontrol`.`dom_historial`;

INSERT IGNORE INTO `menus_digitales`
    (id, comercio_id, nombre, descripcion, activo, token, mesa_id, created_at)
SELECT id, 1, nombre, descripcion, activo, token, mesa_id, created_at
FROM `chefcontrol`.`menus_digitales`;

INSERT IGNORE INTO `menu_items` SELECT * FROM `chefcontrol`.`menu_items`;

INSERT IGNORE INTO `mensajes_chat`
    (id, comercio_id, id_remitente, id_destinatario, mensaje, leido, created_at)
SELECT id, 1, id_remitente, id_destinatario, mensaje, leido, fecha_creacion
FROM `chefcontrol`.`mensajes_chat`;

INSERT IGNORE INTO `detalle_permiso`
    (id, comercio_id, id_usuario, id_permiso, fecha_asignacion)
SELECT id, 1, id_usuario, id_permiso, fecha_asignacion
FROM `chefcontrol`.`detalle_permiso`;

-- ── Nota para cc_* tenants ────────────────────────────────────────────────────
-- Para migrar cada cc_* repite el bloque anterior cambiando:
--   - La BD fuente: `chefcontrol` → `cc_donderochi_0ac8b6`
--   - El comercio_id: 1 → (el id asignado en la tabla comercios)
-- El INSERT de `comercios` para esos tenants viene de `chefcontrol_master`.`comercios`
-- Ejemplo:
/*
INSERT IGNORE INTO `comercios` (id, slug, nombre, email, activo)
SELECT id,
       LOWER(REGEXP_REPLACE(nombre, '[^a-zA-Z0-9]', '-')),
       nombre, email, activo
FROM `chefcontrol_master`.`comercios`
WHERE db_name = 'cc_donderochi_0ac8b6';

-- Luego copiar tablas de `cc_donderochi_0ac8b6` igual que arriba con el comercio_id correcto
*/
