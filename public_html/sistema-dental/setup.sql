-- ============================================
-- SISTEMA DENTAL - Estructura de base de datos
-- Ejecutar esto en phpMyAdmin (dentro de tu base
-- de datos u188616411_dentistachl) ANTES de usar
-- el sistema.
-- ============================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('dentista','paciente') NOT NULL DEFAULT 'paciente',
    telefono VARCHAR(30) DEFAULT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clinica_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_clinica VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    horario VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    dentista_id INT DEFAULT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo VARCHAR(255) DEFAULT NULL,
    estado ENUM('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (dentista_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tratamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    dentista_id INT DEFAULT NULL,
    descripcion VARCHAR(255) NOT NULL,
    notas TEXT DEFAULT NULL,
    fecha DATE NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (dentista_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Info de la clinica (edita estos datos a los reales)
INSERT INTO clinica_info (nombre_clinica, direccion, telefono, email, horario)
VALUES ('Clinica Dental', 'Direccion pendiente', '+51 000 000 000', 'contacto@clinica.com', 'Lunes a Viernes 9:00 - 19:00');

-- Cuenta de dentista de prueba (usuario: dentista@clinica.com / clave: Dentintas12)
-- IMPORTANTE: cambia esta contrasena apenas entres por primera vez.
INSERT INTO usuarios (nombre, email, password_hash, rol, telefono)
VALUES ('Dr. Dentista', 'dentista@clinica.com', '$2b$12$rmfeLa20TUJ5bD6hDktIgu9m4SX/KOTp9HAziIZ4ikUJSWmr/hhLG', 'dentista', NULL);
