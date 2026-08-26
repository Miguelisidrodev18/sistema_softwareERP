-- ============================================
-- ACTUALIZACIÓN V2 — columnas nuevas para el
-- formulario "Agregar paciente" del dentista.
-- Ejecutar en phpMyAdmin, pestaña SQL.
-- No borra ni afecta datos existentes.
-- ============================================

ALTER TABLE usuarios
    ADD COLUMN apellido VARCHAR(100) DEFAULT NULL AFTER nombre,
    ADD COLUMN fecha_nacimiento DATE DEFAULT NULL,
    ADD COLUMN tipo_documento VARCHAR(30) DEFAULT NULL,
    ADD COLUMN numero_documento VARCHAR(30) DEFAULT NULL,
    ADD COLUMN direccion VARCHAR(255) DEFAULT NULL,
    ADD COLUMN motivo_consulta VARCHAR(255) DEFAULT NULL;
