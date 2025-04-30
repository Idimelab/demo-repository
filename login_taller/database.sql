-- Crear la base de datos (opcional, puedes crearla manualmente si prefieres)
-- Asegúrate de que el cotejamiento (utf8mb4_unicode_ci) sea adecuado para tu idioma.
CREATE DATABASE IF NOT EXISTS `login_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar la base de datos recién creada o existente
USE `login_db`;

-- Crear la tabla 'users'
CREATE TABLE IF NOT EXISTS `users` (
                                       `id` INT AUTO_INCREMENT PRIMARY KEY,                    -- Identificador único del usuario
                                       `username` VARCHAR(50) NOT NULL UNIQUE,               -- Nombre de usuario, debe ser único
    `email` VARCHAR(100) NOT NULL UNIQUE,                 -- Correo electrónico, debe ser único
    `password` VARCHAR(255) NOT NULL,                     -- Contraseña hasheada (MUY IMPORTANTE que sea VARCHAR(255))
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP      -- Fecha de creación del registro
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar un usuario de ejemplo
-- La contraseña 'password123' está hasheada usando password_hash('password123', PASSWORD_DEFAULT) en PHP.
-- ¡DEBES generar tus propios hashes al crear usuarios!
INSERT INTO `users` (`username`, `email`, `password`) VALUES
    ('admin', 'admin@example.com', 'password123');
-- El hash anterior corresponde a 'password123'

