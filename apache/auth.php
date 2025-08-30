<?php
// auth.php - Funciones de Autenticación
require_once 'conexion.php';

// ========================================
// FUNCIONES DE REGISTRO Y VERIFICACIÓN
// ========================================

/**
 * Registrar nuevo usuario
 */
function registrarUsuario($datos) {
    global $conn;
    
    $response = ['exito' => false, 'mensaje' => '', 'codigo' => null];
    
    try {
        // Validar campos requeridos
        $campos_requeridos = ['nombre', 'apellido', 'correo', 'usuario', 'contrasena'];
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                $response['mensaje'] = "El campo $campo es requerido";
                return $response;
            }
        }
        
        // Validar formato de email
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $response['mensaje'] = 'El formato del correo no es válido';
            return $response;
        }
        
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT IdUsuario FROM usuarios WHERE Correo = ?");
        $stmt->bind_param("s", $datos['correo']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['mensaje'] = 'Este correo ya está registrado';
            return $response;
        }
        
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT IdUsuario FROM usuarios WHERE Usuario = ?");
        $stmt->bind_param("s", $datos['usuario']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['mensaje'] = 'Este nombre de usuario ya está en uso';
            return $response;
        }
        
        // Generar ID único para el usuario
        $idUsuario = generarIdUnico();
        
        // Hash de la contraseña
        $hashContrasena = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
        
        // Insertar usuario (sin verificar)
        $stmt = $conn->prepare("
            INSERT INTO usuarios (IdUsuario, Nombre, Apellido, Correo, Telefono, Direccion, Usuario, Contrasena, Rol, activo, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cliente', 1, NOW())
        ");
        
        // Antes de bind_param, asigna a variables:
        $nombre      = $datos['nombre'];
        $apellido    = $datos['apellido'];
        $correo      = $datos['correo'];
        $telefono    = $datos['telefono'] ?? '';
        $direccion   = $datos['direccion'] ?? '';
        $usuario     = $datos['usuario'];
        $contrasena  = $hashContrasena;

        // Ahora usa solo variables en bind_param:
        $stmt->bind_param("ssssssss", 
            $idUsuario,
            $nombre,
            $apellido,
            $correo,
            $telefono,
            $direccion,
            $usuario,
            $contrasena
        );
        
        if ($stmt->execute()) {
            // Crear código de verificación
            $codigo = generarCodigoVerificacion();
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            // Guardar verificación
            $stmt = $conn->prepare("
                INSERT INTO email_verificaciones (email, token, codigo, expira_en) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $datos['correo'], $token, $codigo, $expira);
            $stmt->execute();
            
            $response['exito'] = true;
            $response['mensaje'] = 'Usuario registrado. Revisa tu email para el código de verificación.';
            $response['codigo'] = $codigo; // Para desarrollo - quitar en producción
            $response['token'] = $token;
            
        } else {
            $response['mensaje'] = 'Error al registrar usuario';
        }
        
    } catch (Exception $e) {
        $response['mensaje'] = 'Error del servidor: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Verificar código de email
 */
function verificarEmail($email, $codigo) {
    global $conn;
    
    $response = ['exito' => false, 'mensaje' => ''];
    
    try {
        // Buscar verificación activa
        $stmt = $conn->prepare("
            SELECT id, intentos FROM email_verificaciones 
            WHERE email = ? AND codigo = ? AND expira_en > NOW() AND verificado = 0
        ");
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $verificacion = $result->fetch_assoc();
            
            // Marcar email como verificado
            $stmt = $conn->prepare("UPDATE email_verificaciones SET verificado = 1 WHERE id = ?");
            $stmt->bind_param("i", $verificacion['id']);
            $stmt->execute();
            
            // Marcar usuario como verificado
            $stmt = $conn->prepare("UPDATE usuarios SET email_verificado = 1 WHERE Correo = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            $response['exito'] = true;
            $response['mensaje'] = 'Email verificado correctamente';
            
        } else {
            // Incrementar intentos
            $stmt = $conn->prepare("
                UPDATE email_verificaciones 
                SET intentos = intentos + 1 
                WHERE email = ? AND expira_en > NOW() AND verificado = 0
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            $response['mensaje'] = 'Código inválido o expirado';
        }
        
    } catch (Exception $e) {
        $response['mensaje'] = 'Error del servidor: ' . $e->getMessage();
    }
    
    return $response;
}

// ========================================
// FUNCIONES DE LOGIN Y SESIÓN
// ========================================

/**
 * Login de usuario
 */
function loginUsuario($email, $contrasena, $recordar = false) {
    global $conn;
    
    $response = ['exito' => false, 'mensaje' => '', 'usuario' => null, 'es_admin' => false];
    
    try {
        // Buscar usuario por email
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, Apellido, Usuario, Contrasena, email_verificado, activo, Rol 
            FROM usuarios WHERE Correo = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($contrasena, $usuario['Contrasena'])) {
                
                // Verificar que esté activo
                if (!$usuario['activo']) {
                    $response['mensaje'] = 'Cuenta desactivada';
                    return $response;
                }
                
                // Verificar email (opcional - puedes comentar esto para desarrollo)
                if (!$usuario['email_verificado']) {
                    $response['mensaje'] = 'Debes verificar tu email primero';
                    return $response;
                }
                
                // Crear sesión
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = $usuario['IdUsuario'];
                $_SESSION['usuario_nombre'] = $usuario['Nombre'];
                $_SESSION['usuario_email'] = $email;
                $_SESSION['usuario_rol'] = $usuario['Rol']; // NUEVO: Guardar rol en sesión
                $_SESSION['login_time'] = time();
                
                // Actualizar último login
                $stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE IdUsuario = ?");
                $stmt->bind_param("s", $usuario['IdUsuario']);
                $stmt->execute();
                
                // Token "recordarme" si se solicitó
                if ($recordar) {
                    crearTokenRecordar($usuario['IdUsuario']);
                }
                
                // Migrar carrito de sesión a BD
                migrarCarritoSesionABD($usuario['IdUsuario']);
                
                $response['exito'] = true;
                $response['mensaje'] = 'Login exitoso';
                $response['es_admin'] = ($usuario['Rol'] === 'admin'); // NUEVO: Indicar si es admin
                $response['usuario'] = [
                    'id' => $usuario['IdUsuario'],
                    'nombre' => $usuario['Nombre'],
                    'apellido' => $usuario['Apellido'],
                    'usuario' => $usuario['Usuario'],
                    'rol' => $usuario['Rol'] // NUEVO: Incluir rol
                ];
                
            } else {
                $response['mensaje'] = 'Contraseña incorreta';
            }
        } else {
            $response['mensaje'] = 'Usuario no encontrado';
        }
        
    } catch (Exception $e) {
        $response['mensaje'] = 'Error del servidor: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Verificar si usuario está logueado
 */
function estaLogueado() {
    if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
        return true;
    }
    
    // Verificar token "recordarme"
    if (isset($_COOKIE['recordar_token']) && isset($_COOKIE['recordar_selector'])) {
        return verificarTokenRecordar($_COOKIE['recordar_selector'], $_COOKIE['recordar_token']);
    }
    
    return false;
}

/**
 * Logout
 */
function logout() {
    global $conn;
    
    // Eliminar token "recordarme" si existe
    if (isset($_COOKIE['recordar_selector'])) {
        $stmt = $conn->prepare("DELETE FROM tokens_recordar WHERE selector = ?");
        $stmt->bind_param("s", $_COOKIE['recordar_selector']);
        $stmt->execute();
        
        setcookie('recordar_selector', '', time() - 3600, '/', '', false, true);
        setcookie('recordar_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Limpiar sesión
    session_destroy();
    session_start(); // Reiniciar para mantener funcionalidad básica
}

// ========================================
// FUNCIONES DE SOPORTE
// ========================================

/**
 * Crear token "recordarme"
 */
function crearTokenRecordar($usuarioId) {
    global $conn;
    
    $selector = bin2hex(random_bytes(16));
    $token = bin2hex(random_bytes(32));
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    $expira = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Eliminar tokens anteriores del usuario
    $stmt = $conn->prepare("DELETE FROM tokens_recordar WHERE usuario_id = ?");
    $stmt->bind_param("s", $usuarioId);
    $stmt->execute();
    
    // Insertar nuevo token
    $stmt = $conn->prepare("
        INSERT INTO tokens_recordar (usuario_id, selector, token, expira_en) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $usuarioId, $selector, $hashedToken, $expira);
    $stmt->execute();
    
    // Configurar cookies
    setcookie('recordar_selector', $selector, strtotime('+30 days'), '/', '', false, true);
    setcookie('recordar_token', $token, strtotime('+30 days'), '/', '', false, true);
}

/**
 * Verificar token "recordarme"
 */
function verificarTokenRecordar($selector, $token) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT tr.usuario_id, tr.token, u.Nombre, u.Correo 
        FROM tokens_recordar tr
        JOIN usuarios u ON tr.usuario_id = u.IdUsuario
        WHERE tr.selector = ? AND tr.expira_en > NOW() AND tr.usado = 0 AND u.activo = 1
    ");
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        if (password_verify($token, $data['token'])) {
            // Regenerar sesión
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $data['usuario_id'];
            $_SESSION['usuario_nombre'] = $data['Nombre'];
            $_SESSION['usuario_email'] = $data['Correo'];
            $_SESSION['login_time'] = time();
            
            // Crear nuevo token por seguridad
            crearTokenRecordar($data['usuario_id']);
            
            return true;
        }
    }
    
    // Limpiar cookies inválidas
    setcookie('recordar_selector', '', time() - 3600, '/', '', false, true);
    setcookie('recordar_token', '', time() - 3600, '/', '', false, true);
    
    return false;
}

/**
 * Migrar carrito de sesión a base de datos
 */
function migrarCarritoSesionABD($usuarioId) {
    global $conn;
    
    if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $productoId => $cantidad) {
            // Insertar o actualizar carrito en BD
            $stmt = $conn->prepare("
                INSERT INTO carrito_usuarios (usuario_id, producto_id, cantidad) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)
            ");
            $stmt->bind_param("ssi", $usuarioId, $productoId, $cantidad);
            $stmt->execute();
        }
        
        // Limpiar carrito de sesión
        unset($_SESSION['carrito']);
    }
}

/**
 * Generar ID único
 */
function generarIdUnico() {
    return 'USR_' . strtoupper(bin2hex(random_bytes(8))) . '_' . time();
}

/**
 * Generar código de verificación de 6 dígitos
 */
function generarCodigoVerificacion() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    global $conn;
    
    if (!estaLogueado()) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT IdUsuario, Nombre, Apellido, Correo, Usuario, Telefono, Direccion 
        FROM usuarios WHERE IdUsuario = ?
    ");
    $stmt->bind_param("s", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}
/**
 * Verificar si el usuario actual es administrador
 */
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}
?>