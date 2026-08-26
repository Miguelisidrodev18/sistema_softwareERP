-- ============================================
-- HISTORIA CLÍNICA — tablas nuevas
-- Ejecutar en phpMyAdmin, dentro de tu base
-- u188616411_dentistachl, pestaña SQL.
-- (La tabla "tratamientos" ya existía y se sigue
-- usando como el historial de "Evolución").
-- ============================================

CREATE TABLE IF NOT EXISTS anamnesis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL UNIQUE,
    alergias TEXT DEFAULT NULL,
    enfermedades TEXT DEFAULT NULL,
    medicamentos TEXT DEFAULT NULL,
    antecedentes_familiares TEXT DEFAULT NULL,
    habitos TEXT DEFAULT NULL,
    observaciones TEXT DEFAULT NULL,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS odontogramas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL UNIQUE,
    datos TEXT DEFAULT NULL COMMENT 'JSON con el estado de cada diente',
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
