<?php
// auth.php - Sistema de autenticación sin verificación de email
require_once 'conexion.php';

/**
 * Registra un nuevo usuario (sin verificación)
 */
function registrarUsuario($datos) {
    global $conn;
    
    try {
        // Validaciones básicas
        $camposRequeridos = ['nombre', 'apellido', 'correo', 'usuario', 'contrasena'];
        foreach ($camposRequeridos as $campo) {
            if (empty(trim($datos[$campo] ?? ''))) {
                return [
                    'exito' => false,
                    'mensaje' => "El campo $campo es obligatorio"
                ];
            }
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
        $stmt = $conn->prepare("SELECT IdUsuario FROM usuarios WHERE Correo = ? AND activo = 1");
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
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
        $stmt = $conn->prepare("SELECT IdUsuario FROM usuarios WHERE Usuario = ? AND activo = 1");
        $stmt->bind_param("s", $datos['usuario']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'exito' => false,
                'mensaje' => 'Este nombre de usuario ya está en uso'
            ];
        }
        
        // Hashear contraseña
        $password_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
        
        // Preparar datos
        $telefono = !empty($datos['telefono']) ? $datos['telefono'] : null;
        $direccion = !empty($datos['direccion']) ? $datos['direccion'] : null;
        
        // Insertar usuario (email ya verificado por defecto)
        $stmt = $conn->prepare("
            INSERT INTO usuarios (
                Nombre, Apellido, Usuario, Correo, Contrasena, 
                Telefono, Direccion, email_verificado, activo, Rol
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, 'cliente')
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta de inserción: " . $conn->error);
        }
        
        $stmt->bind_param("sssssss", 
            $datos['nombre'],
            $datos['apellido'], 
            $datos['usuario'],
            $datos['correo'],
            $password_hash,
            $telefono,
            $direccion
        );
        
        if ($stmt->execute()) {
            $usuario_id = $conn->insert_id;
            
            // Log del registro
            registrarLogUsuario($usuario_id, 'registro', 'Usuario registrado exitosamente');
            
            return [
                'exito' => true,
                'mensaje' => 'Usuario registrado correctamente. Ya puedes iniciar sesión.',
                'usuario_id' => $usuario_id
            ];
        } else {
            throw new Exception('Error al registrar usuario: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Error en registrarUsuario: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
        ];
    }
}

/**
 * Login de usuario
 */
function loginUsuario($email, $password, $recordarme = false) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, Apellido, Usuario, Correo, Contrasena, 
                   Telefono, Direccion, Rol, activo
            FROM usuarios 
            WHERE Correo = ? AND activo = 1
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
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
        
        // Verificar si está bloqueado
        $check_blocked = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'bloqueado_hasta'");
        if ($check_blocked && $check_blocked->num_rows > 0) {
            $stmt_blocked = $conn->prepare("SELECT bloqueado_hasta FROM usuarios WHERE Correo = ?");
            $stmt_blocked->bind_param("s", $email);
            $stmt_blocked->execute();
            $blocked_result = $stmt_blocked->get_result();
            if ($blocked_result->num_rows > 0) {
                $blocked_data = $blocked_result->fetch_assoc();
                if ($blocked_data['bloqueado_hasta'] && strtotime($blocked_data['bloqueado_hasta']) > time()) {
                    return [
                        'exito' => false,
                        'mensaje' => 'Cuenta temporalmente bloqueada. Intenta más tarde.'
                    ];
                }
            }
        }
        
        // Verificar contraseña
        if (password_verify($password, $usuario['Contrasena'])) {
            reiniciarIntentosFallidos($email);
            
            // Iniciar sesión
            $_SESSION['usuario_id'] = $usuario['IdUsuario'];
            $_SESSION['usuario_nombre'] = $usuario['Nombre'];
            $_SESSION['usuario_email'] = $usuario['Correo'];
            $_SESSION['usuario_logueado'] = true;
            
            // Verificar si es admin
            $es_admin = in_array(strtolower($usuario['Rol']), ['admin', 'administrador']);
            if ($es_admin) {
                $_SESSION['usuario_admin'] = true;
            }
            
            actualizarUltimoLogin($usuario['IdUsuario']);
            registrarLogUsuario($usuario['IdUsuario'], 'login', 'Login exitoso');
            
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
            incrementarIntentosFallidos($email);
            
            return [
                'exito' => false,
                'mensaje' => 'Credenciales incorrectas'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error en loginUsuario: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
        ];
    }
}

/**
 * Registra logs de usuario
 */
function registrarLogUsuario($usuario_id, $accion, $detalle) {
    global $conn;
    
    try {
        $check_table = $conn->query("SHOW TABLES LIKE 'usuario_logs'");
        if (!$check_table || $check_table->num_rows === 0) {
            return;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO usuario_logs (usuario_id, accion, detalle, fecha) 
            VALUES (?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param("iss", $usuario_id, $accion, $detalle);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error registrando log de usuario: " . $e->getMessage());
    }
}

/**
 * Incrementa intentos de login fallidos
 */
function incrementarIntentosFallidos($email) {
    global $conn;
    
    try {
        $check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'intentos_login_fallidos'");
        if (!$check_columns || $check_columns->num_rows === 0) {
            return;
        }
        
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET intentos_login_fallidos = COALESCE(intentos_login_fallidos, 0) + 1,
                bloqueado_hasta = CASE 
                    WHEN COALESCE(intentos_login_fallidos, 0) + 1 >= 5 
                    THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE bloqueado_hasta
                END
            WHERE Correo = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error incrementando intentos fallidos: " . $e->getMessage());
    }
}

/**
 * Reinicia intentos de login fallidos
 */
function reiniciarIntentosFallidos($email) {
    global $conn;
    
    try {
        $check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'intentos_login_fallidos'");
        if (!$check_columns || $check_columns->num_rows === 0) {
            return;
        }
        
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET intentos_login_fallidos = 0, bloqueado_hasta = NULL
            WHERE Correo = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error reiniciando intentos fallidos: " . $e->getMessage());
    }
}

/**
 * Actualiza el último login
 */
function actualizarUltimoLogin($usuario_id) {
    global $conn;
    
    try {
        $check_column = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_login'");
        if (!$check_column || $check_column->num_rows === 0) {
            return;
        }
        
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET ultimo_login = NOW()
            WHERE IdUsuario = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error actualizando último login: " . $e->getMessage());
    }
}

/**
 * Verifica si el usuario está logueado
 */
function estaLogueado() {
    return isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true;
}

/**
 * Verifica si el usuario es admin
 */
function esAdmin() {
    return isset($_SESSION['usuario_admin']) && $_SESSION['usuario_admin'] === true;
}

/**
 * Obtiene los datos del usuario actual
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
    session_unset();
    session_destroy();
    session_start();
}
?>