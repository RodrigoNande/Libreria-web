<?php
// auth.php - Sistema de autenticación con envío de emails
require_once 'conexion.php';
require_once 'email_servicio.php'; // Incluir el servicio de emails

/**
 * Registra un nuevo usuario con verificación por email
 */
function registrarUsuario($datos) {
    global $conn;
    
    try {
        // Validaciones básicas
        if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['correo']) || 
            empty($datos['usuario']) || empty($datos['contrasena'])) {
            return [
                'exito' => false,
                'mensaje' => 'Todos los campos obligatorios deben estar completos'
            ];
        }
        
        // Validar email
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return [
                'exito' => false,
                'mensaje' => 'El formato del correo electrónico no es válido'
            ];
        }
        
        // Validar contraseña
        if (strlen($datos['contrasena']) < 6) {
            return [
                'exito' => false,
                'mensaje' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }
        
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT IdUsuario FROM Usuario WHERE Correo = ?");
        $stmt->bind_param("s", $datos['correo']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'exito' => false,
                'mensaje' => 'Este correo electrónico ya está registrado'
            ];
        }
        
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT IdUsuario FROM Usuario WHERE Usuario = ?");
        $stmt->bind_param("s", $datos['usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'exito' => false,
                'mensaje' => 'Este nombre de usuario ya está en uso'
            ];
        }
        
        // Generar código de verificación
        $codigo_verificacion = generarCodigoVerificacion(); // Descomenta esta línea
        $expiracion_codigo = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Expira en 15 minutos
        
        // Hashear contraseña
        $password_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
        
        // Insertar usuario (sin verificar inicialmente)
        $stmt = $conn->prepare("
            INSERT INTO Usuario (Nombre, Apellido, Usuario, Correo, Contrasena, Telefono, Direccion, 
                               email_verificado, codigo_verificacion, codigo_expiracion, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
        ");
        
        $stmt->bind_param("sssssssss", 
            $datos['nombre'],
            $datos['apellido'], 
            $datos['usuario'],
            $datos['correo'],
            $password_hash,
            $datos['telefono'],
            $datos['direccion'],
            $codigo_verificacion,
            $expiracion_codigo
        );
        
        if ($stmt->execute()) {
            // Enviar código por email
            $resultado_email = enviarCodigoVerificacion(
                $datos['correo'], 
                $datos['nombre'], 
                $codigo_verificacion
            );
            
            if ($resultado_email['exito']) {
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario registrado correctamente. Te enviamos un código de verificación a tu email.',
                    'codigo' => $codigo_verificacion, // Solo para desarrollo - quitar en producción
                    'email_enviado' => true
                ];
            } else {
                // Si falla el envío del email, eliminar el usuario registrado
                $stmt_delete = $conn->prepare("DELETE FROM Usuario WHERE Correo = ?");
                $stmt_delete->bind_param("s", $datos['correo']);
                $stmt_delete->execute();
                
                return [
                    'exito' => false,
                    'mensaje' => 'Error al enviar el código de verificación: ' . $resultado_email['mensaje']
                ];
            }
        } else {
            return [
                'exito' => false,
                'mensaje' => 'Error al registrar usuario: ' . $conn->error
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error en registrarUsuario: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor'
        ];
    }
}

/**
 * Verifica el email usando el código enviado
 */
function verificarEmail($email, $codigo) {
    global $conn;
    
    try {
        // Buscar usuario con el código válido y no expirado
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre 
            FROM Usuario 
            WHERE Correo = ? 
            AND codigo_verificacion = ? 
            AND codigo_expiracion > NOW() 
            AND email_verificado = 0
        ");
        
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'exito' => false,
                'mensaje' => 'Código de verificación inválido o expirado'
            ];
        }
        
        $usuario = $result->fetch_assoc();
        
        // Verificar el email
        $stmt_update = $conn->prepare("
            UPDATE Usuario 
            SET email_verificado = 1, 
                codigo_verificacion = NULL, 
                codigo_expiracion = NULL,
                fecha_verificacion = NOW()
            WHERE Correo = ?
        ");
        
        $stmt_update->bind_param("s", $email);
        
        if ($stmt_update->execute()) {
            return [
                'exito' => true,
                'mensaje' => 'Email verificado correctamente. Ya puedes iniciar sesión.'
            ];
        } else {
            return [
                'exito' => false,
                'mensaje' => 'Error al verificar el email'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error en verificarEmail: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor'
        ];
    }
}

/**
 * Reenvía el código de verificación
 */
function reenviarCodigoVerificacion($email) {
    global $conn;
    
    try {
        // Verificar que el usuario existe y no está verificado
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, email_verificado 
            FROM Usuario 
            WHERE Correo = ?
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'exito' => false,
                'mensaje' => 'Usuario no encontrado'
            ];
        }
        
        $usuario = $result->fetch_assoc();
        
        if ($usuario['email_verificado'] == 1) {
            return [
                'exito' => false,
                'mensaje' => 'Este email ya está verificado'
            ];
        }
        
        // Generar nuevo código
        $nuevo_codigo = generarCodigoVerificacion();
        $nueva_expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Actualizar código
        $stmt_update = $conn->prepare("
            UPDATE Usuario 
            SET codigo_verificacion = ?, 
                codigo_expiracion = ?
            WHERE Correo = ?
        ");
        
        $stmt_update->bind_param("sss", $nuevo_codigo, $nueva_expiracion, $email);
        
        if ($stmt_update->execute()) {
            // Enviar nuevo código
            $resultado_email = enviarCodigoVerificacion($email, $usuario['Nombre'], $nuevo_codigo);
            
            if ($resultado_email['exito']) {
                return [
                    'exito' => true,
                    'mensaje' => 'Nuevo código enviado a tu email',
                    'codigo' => $nuevo_codigo // Solo para desarrollo
                ];
            } else {
                return [
                    'exito' => false,
                    'mensaje' => 'Error al enviar el nuevo código: ' . $resultado_email['mensaje']
                ];
            }
        } else {
            return [
                'exito' => false,
                'mensaje' => 'Error al generar nuevo código'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error en reenviarCodigoVerificacion: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor'
        ];
    }
}

/**
 * Login de usuario (solo usuarios verificados)
 */
function loginUsuario($email, $password, $recordarme = false) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, Apellido, Usuario, Correo, Contrasena, 
                   Telefono, Direccion, email_verificado, Tipo_Usuario 
            FROM Usuario 
            WHERE Correo = ?
        ");
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'exito' => false,
                'mensaje' => 'Credenciales incorrectas'
            ];
        }
        
        $usuario = $result->fetch_assoc();
        
        // Verificar que el email esté verificado
        if ($usuario['email_verificado'] != 1) {
            return [
                'exito' => false,
                'mensaje' => 'Debes verificar tu email antes de iniciar sesión',
                'requiere_verificacion' => true,
                'email' => $email
            ];
        }
        
        // Verificar contraseña
        if (password_verify($password, $usuario['Contrasena'])) {
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['IdUsuario'];
            $_SESSION['usuario_nombre'] = $usuario['Nombre'];
            $_SESSION['usuario_email'] = $usuario['Correo'];
            $_SESSION['usuario_logueado'] = true;
            
            // Verificar si es admin
            $es_admin = ($usuario['Tipo_Usuario'] === 'admin' || $usuario['Tipo_Usuario'] === 'administrador');
            if ($es_admin) {
                $_SESSION['usuario_admin'] = true;
            }
            
            // Configurar "recordarme" si está activado
            if ($recordarme) {
                $token = bin2hex(random_bytes(32));
                $expira = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Guardar token en BD
                $stmt_token = $conn->prepare("
                    INSERT INTO tokens_recordar (usuario_id, token, expira) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE token = ?, expira = ?
                ");
                $stmt_token->bind_param("issss", $usuario['IdUsuario'], $token, $expira, $token, $expira);
                $stmt_token->execute();
                
                // Crear cookie
                setcookie('recordar_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }
            
            return [
                'exito' => true,
                'mensaje' => 'Login exitoso',
                'usuario' => [
                    'id' => $usuario['IdUsuario'],
                    'nombre' => $usuario['Nombre'],
                    'email' => $usuario['Correo']
                ],
                'es_admin' => $es_admin
            ];
        } else {
            return [
                'exito' => false,
                'mensaje' => 'Credenciales incorrectas'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error en loginUsuario: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor'
        ];
    }
}

/**
 * Genera un código de verificación de 6 dígitos
 */
function generarCodigoVerificacion() {
    return sprintf("%06d", mt_rand(100000, 999999));
}

/**
 * Verifica si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true;
}

/**
 * Verifica si el usuario es administrador
 */
function esAdmin() {
    return isset($_SESSION['usuario_admin']) && $_SESSION['usuario_admin'] === true;
}

/**
 * Obtiene información del usuario actual
 */
function obtenerUsuarioActual() {
    if (!estaLogueado()) {
        return null;
    }
    
    return [
        'IdUsuario' => $_SESSION['usuario_id'] ?? null,
        'Nombre' => $_SESSION['usuario_nombre'] ?? null,
        'Correo' => $_SESSION['usuario_email'] ?? null
    ];
}

/**
 * Cierra la sesión del usuario
 */
function logout() {
    // Eliminar cookie de recordar si existe
    if (isset($_COOKIE['recordar_token'])) {
        global $conn;
        
        $token = $_COOKIE['recordar_token'];
        $stmt = $conn->prepare("DELETE FROM tokens_recordar WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        setcookie('recordar_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Limpiar sesión
    session_unset();
    session_destroy();
    
    // Iniciar nueva sesión
    session_start();
}

/**
 * Verifica login automático usando token de "recordarme"
 */
function verificarLoginAutomatico() {
    if (estaLogueado() || !isset($_COOKIE['recordar_token'])) {
        return false;
    }
    
    global $conn;
    
    $token = $_COOKIE['recordar_token'];
    
    $stmt = $conn->prepare("
        SELECT u.IdUsuario, u.Nombre, u.Correo, u.Tipo_Usuario
        FROM tokens_recordar tr
        JOIN Usuario u ON tr.usuario_id = u.IdUsuario
        WHERE tr.token = ? AND tr.expira > NOW()
    ");
    
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // Restaurar sesión
        $_SESSION['usuario_id'] = $usuario['IdUsuario'];
        $_SESSION['usuario_nombre'] = $usuario['Nombre'];
        $_SESSION['usuario_email'] = $usuario['Correo'];
        $_SESSION['usuario_logueado'] = true;
        
        if ($usuario['Tipo_Usuario'] === 'admin' || $usuario['Tipo_Usuario'] === 'administrador') {
            $_SESSION['usuario_admin'] = true;
        }
        
        return true;
    }
    
    // Token inválido, eliminar cookie
    setcookie('recordar_token', '', time() - 3600, '/', '', true, true);
    return false;
}

// Verificar login automático al cargar el archivo
verificarLoginAutomatico();
?>