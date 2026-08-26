-- ============================================================
-- SISTEMA DE CONTROL DE ASISTENCIA Y PUNTUACION
-- Semana Universitaria - EPIS / Universidad Nacional de Huancavelica
-- ============================================================
-- Ejecutar este script en phpMyAdmin o cliente MySQL
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Ediciones de la Semana Universitaria (escalable por año)
CREATE TABLE IF NOT EXISTS `ediciones` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(200) NOT NULL,
  `anio`        YEAR NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_inicio` DATE DEFAULT NULL,
  `fecha_fin`   DATE DEFAULT NULL,
  `activa`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ciclos academicos (totalmente dinamicos, sin ciclos fijos)
CREATE TABLE IF NOT EXISTS `ciclos` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `orden`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuarios del sistema (delegados de ciclo y delegado pleno/admin)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`        VARCHAR(100) NOT NULL UNIQUE,
  `password`        VARCHAR(255) NOT NULL,
  `nombres`         VARCHAR(150) NOT NULL,
  `apellidos`       VARCHAR(150) NOT NULL,
  `email`           VARCHAR(200) DEFAULT NULL,
  `telefono`        VARCHAR(20) DEFAULT NULL,
  `rol`             ENUM('delegado_pleno','delegado_ciclo') NOT NULL DEFAULT 'delegado_ciclo',
  `ciclo_propio_id` INT UNSIGNED DEFAULT NULL COMMENT 'Ciclo al que pertenece el delegado (no puede evaluar este ciclo)',
  `activo`          TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_acceso`   TIMESTAMP NULL DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`ciclo_propio_id`) REFERENCES `ciclos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ciclos asignados a cada delegado (ciclos que PUEDE evaluar)
CREATE TABLE IF NOT EXISTS `delegado_ciclos` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `delegado_id` INT UNSIGNED NOT NULL,
  `ciclo_id`    INT UNSIGNED NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `ux_delegado_ciclo` (`delegado_id`, `ciclo_id`),
  FOREIGN KEY (`delegado_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estudiantes
CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo`      VARCHAR(20) NOT NULL UNIQUE,
  `apellidos`   VARCHAR(150) NOT NULL,
  `nombres`     VARCHAR(150) NOT NULL,
  `ciclo_id`    INT UNSIGNED NOT NULL,
  `seccion`     VARCHAR(10) NOT NULL DEFAULT 'A',
  `activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eventos de la Semana Universitaria
CREATE TABLE IF NOT EXISTS `eventos` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `edicion_id`          INT UNSIGNED DEFAULT NULL,
  `nombre`              VARCHAR(200) NOT NULL,
  `descripcion`         TEXT DEFAULT NULL,
  `fecha`               DATE NOT NULL,
  `hora_inicio`         TIME NOT NULL,
  `hora_cierre`         TIME NOT NULL,
  `minutos_tolerancia`  INT UNSIGNED NOT NULL DEFAULT 15 COMMENT 'Minutos para asistencia puntual desde hora_inicio',
  `minutos_tardanza`    INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Minutos maximos para tardanza desde hora_inicio',
  `puntaje_asistio`     TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `puntaje_tardanza`    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `puntaje_falta`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `estado`              ENUM('programado','activo','finalizado','cancelado') NOT NULL DEFAULT 'programado',
  `creado_por`          INT UNSIGNED NOT NULL,
  `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id`),
  FOREIGN KEY (`edicion_id`) REFERENCES `ediciones`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de asistencias
CREATE TABLE IF NOT EXISTS `asistencias` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `estudiante_id`   INT UNSIGNED NOT NULL,
  `evento_id`       INT UNSIGNED NOT NULL,
  `estado`          ENUM('asistio','tardanza','falta') NOT NULL,
  `puntos`          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `hora_llegada`    TIME DEFAULT NULL,
  `observacion`     VARCHAR(255) DEFAULT NULL,
  `registrado_por`  INT UNSIGNED NOT NULL,
  `fecha_registro`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `ux_estudiante_evento` (`estudiante_id`, `evento_id`),
  FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`evento_id`) REFERENCES `eventos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`registrado_por`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuracion del sistema (clave-valor, configurable por delegado pleno)
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `clave`       VARCHAR(100) NOT NULL UNIQUE,
  `valor`       VARCHAR(500) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historial de auditoria
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`  INT UNSIGNED DEFAULT NULL,
  `accion`      VARCHAR(100) NOT NULL,
  `tabla`       VARCHAR(50) DEFAULT NULL,
  `registro_id` INT UNSIGNED DEFAULT NULL,
  `detalle`     TEXT DEFAULT NULL,
  `ip`          VARCHAR(45) DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Edicion actual
INSERT IGNORE INTO `ediciones` (`id`, `nombre`, `anio`, `descripcion`, `fecha_inicio`, `fecha_fin`, `activa`) VALUES
(1, 'Semana Universitaria EPIS 2025', 2025, 'Semana Universitaria de la Escuela Profesional de Ingeniería de Sistemas', '2025-11-10', '2025-11-15', 1);

-- Ciclos de ejemplo (el admin puede agregar/editar/eliminar)
INSERT IGNORE INTO `ciclos` (`id`, `nombre`, `descripcion`, `orden`, `activo`) VALUES
(1,  'I Ciclo',    'Primer ciclo academico',   1, 1),
(2,  'II Ciclo',   'Segundo ciclo academico',  2, 1),
(3,  'III Ciclo',  'Tercer ciclo academico',   3, 1),
(4,  'IV Ciclo',   'Cuarto ciclo academico',   4, 1),
(5,  'V Ciclo',    'Quinto ciclo academico',   5, 1),
(6,  'VI Ciclo',   'Sexto ciclo academico',    6, 1),
(7,  'VII Ciclo',  'Septimo ciclo academico',  7, 1),
(8,  'VIII Ciclo', 'Octavo ciclo academico',   8, 1),
(9,  'IX Ciclo',   'Noveno ciclo academico',   9, 1),
(10, 'X Ciclo',    'Decimo ciclo academico',   10, 1);

-- Configuracion por defecto
INSERT IGNORE INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('nombre_sistema',      'Sistema de Asistencia - Semana Universitaria EPIS', 'Nombre del sistema'),
('nombre_universidad',  'Universidad Nacional de Huancavelica',               'Nombre de la universidad'),
('nombre_facultad',     'Escuela Profesional de Ingeniería de Sistemas',       'Nombre de la facultad'),
('puntaje_asistio',     '3',  'Puntos por asistencia puntual'),
('puntaje_tardanza',    '1',  'Puntos por tardanza'),
('puntaje_falta',       '0',  'Puntos por falta'),
('minutos_tolerancia',  '15', 'Minutos de tolerancia para asistencia puntual'),
('minutos_tardanza',    '30', 'Minutos maximos para tardanza');

-- NOTA: El usuario administrador se crea con install.php
-- Credenciales por defecto al usar install.php:
--   Usuario: admin
--   Contraseña: Admin2025!
-- Cambia la contraseña despues de la primera sesion.
