-- Tabla para verificaciones de email
CREATE TABLE email_verificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    codigo VARCHAR(6) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expira_en TIMESTAMP NOT NULL,
    verificado BOOLEAN DEFAULT FALSE,
    intentos INT DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla para tokens "recordarme" 
CREATE TABLE tokens_recordar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    selector VARCHAR(255) NOT NULL UNIQUE,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expira_en TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    INDEX idx_selector (selector),
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla para el carrito persistente
CREATE TABLE carrito_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id VARCHAR(50) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    agregado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario_producto (usuario_id, producto_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla para sesiones activas
CREATE TABLE sesiones_activas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Añadir campos faltantes a usuarios (SIN IF NOT EXISTS)
ALTER TABLE usuarios ADD COLUMN email_verificado BOOLEAN DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE usuarios ADD COLUMN ultimo_login TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN activo BOOLEAN DEFAULT TRUE;
ALTER TABLE usuarios ADD COLUMN ultimo_reenvio TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN intentos_login_fallidos INT DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN bloqueado_hasta TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN fecha_verificacion TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN codigo_verificacion VARCHAR(100) NULL;
ALTER TABLE usuarios ADD COLUMN codigo_expiracion TIMESTAMP NULL;

-- Agregar foreign keys AL FINAL
ALTER TABLE tokens_recordar
    ADD CONSTRAINT tokens_recordar_ibfk_1 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE;

ALTER TABLE carrito_usuarios
    ADD CONSTRAINT carrito_usuarios_ibfk_1 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE,
    ADD CONSTRAINT carrito_usuarios_ibfk_2 
    FOREIGN KEY (producto_id) REFERENCES articulo(IdProducto) ON DELETE CASCADE;

ALTER TABLE sesiones_activas
    ADD CONSTRAINT sesiones_activas_ibfk_1 
    FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE;