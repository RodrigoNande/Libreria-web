-- V5__fix_usuarios_table.sql
-- Corregir la tabla usuarios para que funcione con el sistema de autenticación

-- Primero, eliminar las claves foráneas que referencian usuarios
ALTER TABLE tokens_recordar DROP FOREIGN KEY tokens_recordar_ibfk_1;
ALTER TABLE carrito_usuarios DROP FOREIGN KEY carrito_usuarios_ibfk_1;
ALTER TABLE sesiones_activas DROP FOREIGN KEY sesiones_activas_ibfk_1;
ALTER TABLE ventas DROP FOREIGN KEY ventas_ibfk_1;


-- Recrear las claves foráneas con el nuevo tipo
ALTER TABLE tokens_recordar 
MODIFY COLUMN usuario_id INT NOT NULL,
ADD CONSTRAINT tokens_recordar_ibfk_1 
FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE;

ALTER TABLE carrito_usuarios 
MODIFY COLUMN usuario_id INT NOT NULL,
ADD CONSTRAINT carrito_usuarios_ibfk_1 
FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE;

ALTER TABLE sesiones_activas 
MODIFY COLUMN usuario_id INT NOT NULL,
ADD CONSTRAINT sesiones_activas_ibfk_1 
FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE;


-- Agregar campos faltantes si no existen
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS codigo_verificacion VARCHAR(6) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS codigo_expiracion DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS fecha_verificacion DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS ultimo_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1;

-- Crear tabla email_log si no existe
CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_destino VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    asunto VARCHAR(255),
    estado ENUM('enviado', 'fallido') NOT NULL,
    mensaje_error TEXT,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email_destino),
    INDEX idx_fecha (fecha_envio)
);

-- Insertar usuario admin por defecto
INSERT IGNORE INTO usuarios (
    Nombre,
    Apellido,
    Correo,
    Telefono,
    Direccion,
    Usuario,
    Contrasena,
    Rol,
    email_verificado,
    activo
) VALUES (
    'Administrador',
    'Sistema',
    'hernandezrodri83@gmail.com',
    '',
    '',
    'admin',
    '$2y$10$l7Wts2OWVnEAalpCs7gWvexBIcUSrkhTZxOQPhZLq8w34Ekl5G7yC',
    'admin',
    1,
    1
);