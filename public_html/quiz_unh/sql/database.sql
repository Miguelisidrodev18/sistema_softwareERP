-- ============================================================
-- BASE DE DATOS: quiz_unh
-- Sistema de Quiz Gerencial — Universidad Nacional de Huancavelica
-- Ingeniería de Sistemas
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `quiz_unh`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `quiz_unh`;

-- ============================================================
-- TABLAS
-- ============================================================

CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `codigo_matricula` VARCHAR(20)      NOT NULL,
  `nombre`           VARCHAR(120)     NOT NULL,
  `apellidos`        VARCHAR(120)     NOT NULL,
  `activo`           TINYINT(1)       NOT NULL DEFAULT 1,
  `creado_en`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo_matricula` (`codigo_matricula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `docentes` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `codigo`     VARCHAR(20)   NOT NULL,
  `nombre`     VARCHAR(120)  NOT NULL,
  `apellidos`  VARCHAR(120)  NOT NULL,
  `contrasena` VARCHAR(255)  NOT NULL,
  `activo`     TINYINT(1)    NOT NULL DEFAULT 1,
  `creado_en`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo_docente` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `casos` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `titulo`      VARCHAR(200)  NOT NULL,
  `rubro`       VARCHAR(100)  NOT NULL,
  `descripcion` TEXT          NOT NULL,
  `activo`      TINYINT(1)    NOT NULL DEFAULT 1,
  `creado_en`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `situaciones` (
  `id`              INT UNSIGNED                              NOT NULL AUTO_INCREMENT,
  `caso_id`         INT UNSIGNED                              NOT NULL,
  `orden`           TINYINT                                   NOT NULL,
  `enunciado`       TEXT                                      NOT NULL,
  `tipo_correcto`   ENUM('Certeza','Riesgo','Incertidumbre') NOT NULL,
  `explicacion_ok`  TEXT                                      NOT NULL,
  `explicacion_fail` TEXT                                     NOT NULL,
  `puntos`          TINYINT                                   NOT NULL DEFAULT 4,
  PRIMARY KEY (`id`),
  KEY `idx_situaciones_caso` (`caso_id`),
  CONSTRAINT `fk_situaciones_caso`
    FOREIGN KEY (`caso_id`) REFERENCES `casos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `intentos` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `estudiante_id`  INT UNSIGNED  NOT NULL,
  `caso_id`        INT UNSIGNED  NOT NULL,
  `puntaje`        TINYINT       NOT NULL DEFAULT 0,
  `puntaje_max`    TINYINT       NOT NULL DEFAULT 16,
  `completado`     TINYINT(1)    NOT NULL DEFAULT 0,
  `iniciado_en`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finalizado_en`  TIMESTAMP     NULL     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_intentos_estudiante` (`estudiante_id`),
  KEY `idx_intentos_caso`       (`caso_id`),
  CONSTRAINT `fk_intentos_estudiante`
    FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_intentos_caso`
    FOREIGN KEY (`caso_id`) REFERENCES `casos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `respuestas` (
  `id`             INT UNSIGNED                              NOT NULL AUTO_INCREMENT,
  `intento_id`     INT UNSIGNED                              NOT NULL,
  `situacion_id`   INT UNSIGNED                              NOT NULL,
  `respuesta_dada` ENUM('Certeza','Riesgo','Incertidumbre') NOT NULL,
  `es_correcta`    TINYINT(1)                                NOT NULL DEFAULT 0,
  `respondido_en`  TIMESTAMP                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_respuestas_intento`   (`intento_id`),
  KEY `idx_respuestas_situacion` (`situacion_id`),
  CONSTRAINT `fk_respuestas_intento`
    FOREIGN KEY (`intento_id`) REFERENCES `intentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_respuestas_situacion`
    FOREIGN KEY (`situacion_id`) REFERENCES `situaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VISTAS
-- ============================================================

CREATE OR REPLACE VIEW `v_ranking` AS
SELECT
  c.titulo                                           AS caso,
  CONCAT(e.nombre, ' ', e.apellidos)                 AS nombre_completo,
  e.codigo_matricula,
  i.puntaje                                          AS puntos,
  ROUND((i.puntaje / i.puntaje_max) * 100, 1)        AS porcentaje,
  i.finalizado_en                                    AS fecha
FROM intentos i
JOIN estudiantes e ON e.id = i.estudiante_id
JOIN casos       c ON c.id = i.caso_id
WHERE i.completado = 1
ORDER BY i.puntaje DESC, i.finalizado_en ASC;

CREATE OR REPLACE VIEW `v_stats_situaciones` AS
SELECT
  c.id                                                              AS caso_id,
  c.titulo                                                          AS caso,
  s.orden                                                           AS numero,
  LEFT(s.enunciado, 80)                                             AS resumen,
  s.tipo_correcto,
  COUNT(r.id)                                                       AS total_respuestas,
  SUM(r.es_correcta)                                                AS total_aciertos,
  ROUND(
    IFNULL(SUM(r.es_correcta) / NULLIF(COUNT(r.id), 0) * 100, 0)
  , 1)                                                              AS porcentaje_acierto
FROM situaciones s
JOIN casos c ON c.id = s.caso_id
LEFT JOIN respuestas r ON r.situacion_id = s.id
GROUP BY s.id
ORDER BY c.id, s.orden;

-- ============================================================
-- DATOS SEED — DOCENTES
-- ============================================================
-- password_hash('docente123', PASSWORD_BCRYPT)
INSERT INTO `docentes` (`codigo`, `nombre`, `apellidos`, `contrasena`) VALUES
('DOC001', 'Carlos', 'Mendoza Rojas',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ============================================================
-- DATOS SEED — ESTUDIANTES
-- ============================================================
INSERT INTO `estudiantes` (`codigo_matricula`, `nombre`, `apellidos`) VALUES
('2021001', 'Ana',   'Torres Huanca'),
('2021002', 'Luis',  'Quispe Flores'),
('2021003', 'María', 'Ramos León');

-- ============================================================
-- DATOS SEED — CASO 1: Electrodomésticos
-- ============================================================
INSERT INTO `casos` (`titulo`, `rubro`, `descripcion`) VALUES
(
  'Apertura de nueva sucursal de electrodomésticos',
  'Comercio / Electrodomésticos',
  'El gerente de una empresa de electrodomésticos evalúa abrir una nueva sucursal. Firma contrato de alquiler por S/6,000 mensuales durante 3 años. Estudio de mercado indica probabilidad de aumento de ventas entre 15%-20% el primer año. Hay rumores de una cadena internacional ingresando a la ciudad sin información oficial. El gobierno analiza regulaciones de importación sin fecha ni impacto definidos.'
);

SET @caso1 = LAST_INSERT_ID();

INSERT INTO `situaciones` (`caso_id`,`orden`,`enunciado`,`tipo_correcto`,`explicacion_ok`,`explicacion_fail`,`puntos`) VALUES
(@caso1, 1,
 'Contrato de alquiler de S/6,000 mensuales por 3 años firmado con el propietario del local.',
 'Certeza',
 'El monto está en contrato firmado, resultado conocido con exactitud.',
 'El contrato firmado define un monto fijo e inamovible. Eso es certeza.',
 4),
(@caso1, 2,
 'Estudio de mercado indica probabilidad de aumento de ventas entre 15%-20% el primer año.',
 'Riesgo',
 'Resultados posibles con probabilidades estimadas por el estudio de mercado.',
 'El estudio permite asignar probabilidades a los posibles desenlaces: riesgo.',
 4),
(@caso1, 3,
 'Rumores de una cadena internacional ingresando a la ciudad, sin información oficial disponible.',
 'Incertidumbre',
 'Sin datos oficiales no se pueden estimar probabilidades. Incertidumbre total.',
 'Solo hay rumores sin respaldo. Sin probabilidades estimables: incertidumbre.',
 4),
(@caso1, 4,
 'Regulaciones de importación en análisis gubernamental, sin fecha definida ni impacto estimado.',
 'Incertidumbre',
 'Sin fecha ni magnitud, no se pueden calcular probabilidades confiables.',
 'La falta de información verificable impide calcular probabilidades.',
 4);

-- ============================================================
-- DATOS SEED — CASO 2: Clínica Privada
-- ============================================================
INSERT INTO `casos` (`titulo`, `rubro`, `descripcion`) VALUES
(
  'Expansión de clínica privada en zona rural',
  'Salud / Clínica Privada',
  'Directora de clínica privada evalúa instalar módulo de atención en zona rural. Convenio municipal garantiza S/8,500 mensuales por 2 años. Estudios epidemiológicos muestran 65% de probabilidad de superar 300 atenciones mensuales en los primeros 6 meses. Se rumorea que el Ministerio de Salud podría lanzar programa gratuito en la misma zona, sin anuncio oficial. Proveedor extranjero de equipos médicos expone a fluctuaciones del tipo de cambio sin dirección ni magnitud conocidas.'
);

SET @caso2 = LAST_INSERT_ID();

INSERT INTO `situaciones` (`caso_id`,`orden`,`enunciado`,`tipo_correcto`,`explicacion_ok`,`explicacion_fail`,`puntos`) VALUES
(@caso2, 1,
 'Convenio municipal garantiza S/8,500 mensuales por 2 años para el módulo de atención.',
 'Certeza',
 'Monto fijo en convenio firmado. Resultado futuro conocido con exactitud.',
 'El convenio firmado establece un pago garantizado. Eso es certeza.',
 4),
(@caso2, 2,
 'Estudios epidemiológicos indican 65% de probabilidad de superar 300 atenciones mensuales en los primeros 6 meses.',
 'Riesgo',
 'Datos estadísticos permiten asignar probabilidades a niveles de demanda.',
 'Los estudios permiten calcular probabilidades sobre resultados posibles: riesgo.',
 4),
(@caso2, 3,
 'Rumor de que el Ministerio de Salud podría lanzar programa de salud pública gratuita en la misma zona, sin anuncio oficial.',
 'Incertidumbre',
 'Sin anuncio oficial ni datos, no es posible estimar probabilidad del evento.',
 'Solo existe un rumor sin respaldo oficial. Sin datos cuantificables: incertidumbre.',
 4),
(@caso2, 4,
 'Proveedor extranjero de equipos médicos expone a fluctuaciones del tipo de cambio sin datos de dirección ni magnitud.',
 'Incertidumbre',
 'Sin proyecciones confiables del tipo de cambio, no se asignan probabilidades.',
 'La ausencia de proyecciones confiables impide calcular probabilidades.',
 4);

-- ============================================================
-- DATOS SEED — CASO 3: Agroexportación
-- ============================================================
INSERT INTO `casos` (`titulo`, `rubro`, `descripcion`) VALUES
(
  'Inversión en producción de arándanos para exportación',
  'Agricultura / Agroexportación',
  'Empresario agrícola de Huancavelica evalúa invertir en cultivo de arándanos para exportación. Firmó contrato con cooperativa local por suministro de agua de riego a S/2,200 mensuales durante 4 años. Reportes del sector indican 70% de probabilidad de precios internacionales favorables en los próximos 18 meses. Productores comentan que una nueva plaga podría llegar desde el norte, sin confirmación oficial. El gobierno debate reforma laboral agraria que podría encarecer mano de obra, sin proyecto de ley formal presentado.'
);

SET @caso3 = LAST_INSERT_ID();

INSERT INTO `situaciones` (`caso_id`,`orden`,`enunciado`,`tipo_correcto`,`explicacion_ok`,`explicacion_fail`,`puntos`) VALUES
(@caso3, 1,
 'Contrato firmado con cooperativa local por suministro de agua de riego a S/2,200 mensuales durante 4 años.',
 'Certeza',
 'El costo está fijado en contrato por 4 años. Resultado exacto conocido.',
 'El contrato firmado define un monto fijo. Conocer el resultado exacto es certeza.',
 4),
(@caso3, 2,
 'Reportes sectoriales indican 70% de probabilidad de precios internacionales favorables en los próximos 18 meses.',
 'Riesgo',
 'Los reportes permiten asignar probabilidad a un resultado específico: riesgo.',
 'Los reportes cuantifican la probabilidad del desenlace favorable: riesgo.',
 4),
(@caso3, 3,
 'Comentarios de productores sobre posible plaga llegando desde el norte, sin confirmación oficial ni técnica.',
 'Incertidumbre',
 'Sin confirmación técnica oficial, no se puede estimar probabilidad ni impacto.',
 'Sin datos oficiales ni técnicos, no es posible asignar probabilidades.',
 4),
(@caso3, 4,
 'Debate gubernamental sobre reforma laboral agraria que podría encarecer mano de obra, sin proyecto de ley formal presentado.',
 'Incertidumbre',
 'Sin proyecto formal ni datos concretos, no se proyectan impactos en costos.',
 'Un debate político sin proyecto formal no permite calcular probabilidades.',
 4);
