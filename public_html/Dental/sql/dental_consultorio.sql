-- Tablas del sistema de login del consultorio dental.
-- En hosting compartido (Hostinger, etc.) la base de datos ya la creas
-- desde el panel (hPanel/cPanel) con su usuario asignado; este script NO
-- crea la base de datos, solo las tablas. En phpMyAdmin, selecciona tu
-- base (ej. u188616411_dental) antes de ejecutar este script.

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('dentista', 'paciente') NOT NULL DEFAULT 'paciente',
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Nota: la cuenta del dentista NO se crea aquí porque la contraseña
-- debe quedar encriptada con password_hash() de PHP.
-- Para crear la primera cuenta de dentista, visita una sola vez:
--   /setup/crear_dentista.php
-- y luego borra ese archivo (o la carpeta setup) por seguridad.

CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'completada') NOT NULL DEFAULT 'pendiente',
    notas_dentista TEXT DEFAULT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_citas_paciente FOREIGN KEY (paciente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_citas_fecha (fecha, hora)
) ENGINE=InnoDB;
