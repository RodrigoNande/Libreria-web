-- V5__edit_tablas.sql
-- Version simplificada - solo crear tabla email_log y usuario admin

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insertar usuario admin por defecto
