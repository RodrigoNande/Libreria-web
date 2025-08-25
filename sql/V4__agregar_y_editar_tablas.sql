-- Tabla para verificaciones de email
CREATE TABLE email_verificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    codigo VARCHAR(6) NOT NULL, -- Código de 6 dígitos para verificación
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expira_en TIMESTAMP NOT NULL,
    verificado BOOLEAN DEFAULT FALSE,
    intentos INT DEFAULT 0, -- Contador de intentos fallidos
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_codigo (codigo)
);

-- Tabla para tokens "recordarme" 
CREATE TABLE tokens_recordar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    selector VARCHAR(255) NOT NULL UNIQUE, -- Para mayor seguridad
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expira_en TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE,
    INDEX idx_selector (selector),
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id)
);

-- Tabla para el carrito persistente (opcional - para reemplazar $_SESSION['carrito'])
CREATE TABLE carrito_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  VARCHAR(100) NOT NULL,
    producto_id VARCHAR(100) NOT NULL, -- Coincide con IdProducto de articulo
    cantidad INT NOT NULL DEFAULT 1,
    agregado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES articulo(IdProducto) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_producto (usuario_id, producto_id),
    INDEX idx_usuario (usuario_id)
);

-- Tabla para sesiones activas (opcional - para mejor control)
CREATE TABLE sesiones_activas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  VARCHAR(100) NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45), -- IPv6 compatible
    user_agent TEXT,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(IdUsuario) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_session (session_id)
);

-- Añadir campos faltantes a tu tabla usuarios existente
ALTER TABLE usuarios ADD COLUMN email_verificado BOOLEAN DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE usuarios ADD COLUMN ultimo_login TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN activo BOOLEAN DEFAULT TRUE;