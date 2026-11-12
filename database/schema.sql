-- ========================================================================
-- SISTEMA COTIZA-ELEV — ESQUEMA RELACIONAL FÍSICO (INCREMENTO 6 / v0.6.0)
-- Motor: MySQL 8.0 / InnoDB — Juego de caracteres: utf8mb4_unicode_ci
-- Normalización: Tercera Forma Normal (3FN)
-- Regla de Integridad Referencial: ON DELETE RESTRICT (Sin cascadas)
-- ========================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `bitacora_auditoria`;
DROP TABLE IF EXISTS `seguimiento_propuestas`;
DROP TABLE IF EXISTS `partidas_cotizacion`;
DROP TABLE IF EXISTS `cotizaciones`;
DROP TABLE IF EXISTS `solicitudes`;
DROP TABLE IF EXISTS `catalogo_conceptos`;
DROP TABLE IF EXISTS `equipos_activos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABLA: usuarios (RF-01..RF-07)
CREATE TABLE `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre_completo` VARCHAR(150) NOT NULL,
  `correo_electronico` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin_ventas', 'tecnico') NOT NULL DEFAULT 'tecnico',
  `intentos_fallidos` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `bloqueado_hasta` DATETIME NULL,
  `reset_token` VARCHAR(64) NULL,
  `reset_token_expira` DATETIME NULL,
  `estatus` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuarios_correo` (`correo_electronico`),
  INDEX `idx_usuarios_rol` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA: clientes (RF-08..RF-12)
CREATE TABLE `clientes` (
  `id_cliente` INT AUTO_INCREMENT PRIMARY KEY,
  `clave_cliente` VARCHAR(20) NOT NULL UNIQUE,
  `razon_social` VARCHAR(180) NOT NULL,
  `rfc` VARCHAR(13) NOT NULL UNIQUE,
  `nombre_contacto` VARCHAR(150) NOT NULL,
  `correo` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `direccion_fiscal` TEXT NOT NULL,
  `estatus` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_clientes_rfc` (`rfc`),
  INDEX `idx_clientes_clave` (`clave_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA: equipos_activos (RF-13..RF-15)
CREATE TABLE `equipos_activos` (
  `id_activo` INT AUTO_INCREMENT PRIMARY KEY,
  `id_cliente` INT NOT NULL,
  `numero_serie` VARCHAR(50) NOT NULL UNIQUE,
  `tipo_equipo` ENUM('pasajeros', 'carga', 'montaautos') NOT NULL DEFAULT 'pasajeros',
  `marca` VARCHAR(80) NOT NULL,
  `modelo` VARCHAR(80) NOT NULL,
  `capacidad_kg` INT UNSIGNED NOT NULL,
  `numero_paradas` TINYINT UNSIGNED NOT NULL,
  `ubicacion_instalacion` VARCHAR(255) NOT NULL,
  `estatus` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_activos_cliente` (`id_cliente`),
  INDEX `idx_activos_serie` (`numero_serie`),
  CONSTRAINT `fk_activos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA: catalogo_conceptos (RF-16..RF-19)
CREATE TABLE `catalogo_conceptos` (
  `id_concepto` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_sku` VARCHAR(30) NOT NULL UNIQUE,
  `tipo_concepto` ENUM('refaccion', 'mano_obra', 'poliza', 'modernizacion', 'equipo_nuevo') NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `unidad_medida` VARCHAR(30) NOT NULL DEFAULT 'pieza',
  `precio_base` DECIMAL(12,2) NOT NULL,
  `vigente_desde` DATE NOT NULL,
  `vigente_hasta` DATE NULL,
  `estatus` ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_catalogo_sku` (`codigo_sku`),
  INDEX `idx_catalogo_tipo` (`tipo_concepto`),
  INDEX `idx_catalogo_vigencia` (`vigente_desde`, `vigente_hasta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA: solicitudes (RF-20..RF-24)
CREATE TABLE `solicitudes` (
  `id_solicitud` INT AUTO_INCREMENT PRIMARY KEY,
  `folio_solicitud` VARCHAR(20) NOT NULL UNIQUE,
  `id_tecnico` INT NOT NULL,
  `id_cliente` INT NOT NULL,
  `id_activo` INT NOT NULL,
  `tipo_servicio` ENUM('preventivo', 'correctivo', 'modernizacion') NOT NULL,
  `diagnostico_falla` TEXT NOT NULL,
  `piezas_requeridas` TEXT NOT NULL,
  `urgencia` ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
  `estado` ENUM('pendiente', 'cotizada', 'rechazada') NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_solicitudes_folio` (`folio_solicitud`),
  INDEX `idx_solicitudes_estado` (`estado`),
  INDEX `idx_solicitudes_tecnico` (`id_tecnico`),
  CONSTRAINT `fk_solicitudes_tecnico` FOREIGN KEY (`id_tecnico`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_solicitudes_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_solicitudes_activo` FOREIGN KEY (`id_activo`) REFERENCES `equipos_activos` (`id_activo`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLA: cotizaciones (RF-25..RF-34)
CREATE TABLE `cotizaciones` (
  `id_cotizacion` INT AUTO_INCREMENT PRIMARY KEY,
  `folio_cotizacion` VARCHAR(20) NOT NULL UNIQUE,
  `id_cliente` INT NOT NULL,
  `id_activo` INT NOT NULL,
  `id_usuario_creador` INT NOT NULL,
  `id_solicitud` INT NULL,
  `tipo_cotizacion` ENUM('equipo_nuevo', 'modernizacion', 'preventivo', 'correctivo') NOT NULL,
  `estado` ENUM('borrador', 'enviada', 'aceptada', 'rechazada', 'vencida') NOT NULL DEFAULT 'borrador',
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `porcentaje_descuento` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `monto_descuento` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `moneda` VARCHAR(5) NOT NULL DEFAULT 'MXN',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cotizaciones_folio` (`folio_cotizacion`),
  INDEX `idx_cotizaciones_estado` (`estado`),
  INDEX `idx_cotizaciones_cliente` (`id_cliente`),
  CONSTRAINT `fk_cotizaciones_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cotizaciones_activo` FOREIGN KEY (`id_activo`) REFERENCES `equipos_activos` (`id_activo`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cotizaciones_usuario` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cotizaciones_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLA: partidas_cotizacion (RF-27..RF-32)
CREATE TABLE `partidas_cotizacion` (
  `id_partida` INT AUTO_INCREMENT PRIMARY KEY,
  `id_cotizacion` INT NOT NULL,
  `id_concepto` INT NULL,
  `numero_item` SMALLINT UNSIGNED NOT NULL,
  `descripcion_concepto` VARCHAR(255) NOT NULL,
  `cantidad` DECIMAL(8,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `total_partida` DECIMAL(12,2) NOT NULL,
  INDEX `idx_partidas_cotizacion` (`id_cotizacion`),
  CONSTRAINT `fk_partidas_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_partidas_concepto` FOREIGN KEY (`id_concepto`) REFERENCES `catalogo_conceptos` (`id_concepto`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABLA: seguimiento_propuestas (RF-35..RF-38)
CREATE TABLE `seguimiento_propuestas` (
  `id_seguimiento` INT AUTO_INCREMENT PRIMARY KEY,
  `id_cotizacion` INT NOT NULL,
  `folio_propuesta` VARCHAR(20) NOT NULL,
  `version_pdf` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `ruta_archivo_pdf` VARCHAR(255) NOT NULL,
  `fecha_envio_cliente` DATETIME NULL,
  `medio_envio` VARCHAR(50) NOT NULL DEFAULT 'correo',
  `destinatario_correo` VARCHAR(150) NOT NULL,
  `resultado` ENUM('en_espera', 'ganada', 'perdida') NOT NULL DEFAULT 'en_espera',
  `motivo_rechazo` TEXT NULL,
  `fecha_cierre` DATETIME NULL,
  `registrado_por` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_seguimiento_cotizacion` (`id_cotizacion`),
  INDEX `idx_seguimiento_folio` (`folio_propuesta`),
  CONSTRAINT `fk_seguimiento_cotizacion` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id_cotizacion`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_seguimiento_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABLA: bitacora_auditoria (RF-41..RF-43)
CREATE TABLE `bitacora_auditoria` (
  `id_log` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NULL,
  `modulo_afectado` VARCHAR(50) NOT NULL,
  `accion` ENUM('LOGIN', 'INSERT', 'UPDATE', 'DELETE', 'STATUS_CHANGE', 'SECURITY_ALERT') NOT NULL,
  `ip_origen` VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT 'CLI/Browser',
  `detalle_evento` TEXT NOT NULL,
  `payload_previo` JSON NULL,
  `payload_nuevo` JSON NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_auditoria_usuario` (`id_usuario`),
  INDEX `idx_auditoria_modulo` (`modulo_afectado`),
  INDEX `idx_auditoria_accion` (`accion`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
