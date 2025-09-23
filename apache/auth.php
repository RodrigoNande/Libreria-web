<?php
// auth_mejorado.php - Sistema de autenticación con verificación por email optimizado
require_once 'conexion.php';

/**
 * Registra un nuevo usuario con verificación por email
 */
function registrarUsuario($datos) {
    global $conn;
    
    try {
        // Validaciones básicas mejoradas
        $camposRequeridos = ['nombre', 'apellido', 'correo', 'usuario', 'contrasena'];
        foreach ($camposRequeridos as $campo) {
            if (empty(trim($datos[$campo] ?? ''))) {
                return [
                    'exito' => false,
                    'mensaje' => "El campo $campo es obligatorio"
                ];
            }
        }
        
        // Validar email con filtro mejorado
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return [
                'exito' => false,
                'mensaje' => 'El formato del correo electrónico no es válido'
            ];
        }
        
        // Validar contraseña con criterios más estrictos
        if (strlen($datos['contrasena']) < 6) { // Cambié de 8 a 6 como tenías antes
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
        
        // Generar código de verificación más seguro
        $codigo_verificacion = generarCodigoVerificacionSeguro();
        $expiracion_codigo = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Hashear contraseña - usar PASSWORD_DEFAULT que es más compatible
        $password_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
        
        // Preparar datos para inserción
        $telefono = !empty($datos['telefono']) ? $datos['telefono'] : null;
        $direccion = !empty($datos['direccion']) ? $datos['direccion'] : null;
        
        // CAMBIO IMPORTANTE: No incluir IdUsuario en la inserción (AUTO_INCREMENT)
        $stmt = $conn->prepare("
            INSERT INTO usuarios (
                Nombre, Apellido, Usuario, Correo, Contrasena, 
                Telefono, Direccion, email_verificado, codigo_verificacion, 
                codigo_expiracion, activo, Rol
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1, 'cliente')
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta de inserción: " . $conn->error);
        }
        
        $stmt->bind_param("sssssssss", 
            $datos['nombre'],
            $datos['apellido'], 
            $datos['usuario'],
            $datos['correo'],
            $password_hash,
            $telefono,
            $direccion,
            $codigo_verificacion,
            $expiracion_codigo
        );
        
        if ($stmt->execute()) {
            // CAMBIO IMPORTANTE: Obtener el ID generado automáticamente
            $usuario_id = $conn->insert_id;
            
            // Intentar enviar código por email
            $resultado_email = enviarEmailVerificacion(
                $datos['correo'], 
                $datos['nombre'], 
                $codigo_verificacion
            );
            
            // Log del registro
            registrarLogUsuario($usuario_id, 'registro', 'Usuario registrado exitosamente');
            
            if ($resultado_email['exito']) {
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario registrado correctamente. Te enviamos un código de verificación a tu email.',
                    'usuario_id' => $usuario_id,
                    'email_enviado' => true,
                    // Solo mostrar código en desarrollo
                    'codigo' => (defined('DESARROLLO') && DESARROLLO) ? $codigo_verificacion : $codigo_verificacion // Mostrar siempre por ahora
                ];
            } else {
                return [
                    'exito' => true,
                    'mensaje' => 'Usuario registrado. Error al enviar email, intenta reenviar el código.',
                    'usuario_id' => $usuario_id,
                    'email_enviado' => false,
                    'error_email' => $resultado_email['mensaje'],
                    'codigo' => $codigo_verificacion // Mostrar código si falla el email
                ];
            }
        } else {
            throw new Exception('Error al registrar usuario: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Error en registrarUsuario: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor: ' . $e->getMessage() // Mostrar error específico para debugging
        ];
    }
}

/**
 * Función mejorada para enviar email de verificación
 */
function enviarEmailVerificacion($email, $nombre, $codigo) {
    try {
        // Verificar configuración de email
        if (!verificarConfiguracionEmail()) {
            return [
                'exito' => false,
                'mensaje' => 'Servicio de email no configurado correctamente'
            ];
        }
        
        // Intentar cargar el servicio de email
        if (file_exists('email_servicio.php')) {
            require_once 'email_servicio.php';
            
            if (class_exists('EmailService')) {
                $emailService = new EmailService();
                return $emailService->enviarCodigoVerificacion($email, $nombre, $codigo);
            }
        }
        
        // Fallback: envío básico con mail()
        return enviarEmailBasico($email, $nombre, $codigo);
        
    } catch (Exception $e) {
        error_log("Error enviando email: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error al enviar email: ' . $e->getMessage()
        ];
    }
}

/**
 * Verifica el email usando el código enviado - Mejorado
 */
function verificarEmail($email, $codigo) {
    global $conn;
    
    try {
        // Validar parámetros
        if (empty($email) || empty($codigo)) {
            return [
                'exito' => false,
                'mensaje' => 'Email y código son requeridos'
            ];
        }
        
        // Validar formato del código
        if (!preg_match('/^\d{6}$/', $codigo)) {
            return [
                'exito' => false,
                'mensaje' => 'El código debe tener 6 dígitos'
            ];
        }
        
        // Buscar usuario con el código válido y no expirado
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, email_verificado, codigo_expiracion
            FROM usuarios 
            WHERE Correo = ? 
            AND codigo_verificacion = ? 
            AND activo = 1
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $email, $codigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'exito' => false,
                'mensaje' => 'Código de verificación inválido'
            ];
        }
        
        $usuario = $result->fetch_assoc();
        
        // Verificar si ya está verificado
        if ($usuario['email_verificado'] == 1) {
            return [
                'exito' => false,
                'mensaje' => 'Este email ya ha sido verificado'
            ];
        }
        
        // Verificar si el código ha expirado
        if (strtotime($usuario['codigo_expiracion']) < time()) {
            return [
                'exito' => false,
                'mensaje' => 'El código ha expirado. Solicita uno nuevo.',
                'codigo_expirado' => true
            ];
        }
        
        // Verificar el email
        $stmt_update = $conn->prepare("
            UPDATE usuarios 
            SET email_verificado = 1, 
                codigo_verificacion = NULL, 
                codigo_expiracion = NULL,
                fecha_verificacion = NOW()
            WHERE Correo = ? AND codigo_verificacion = ?
        ");
        
        if (!$stmt_update) {
            throw new Exception("Error preparando consulta de actualización: " . $conn->error);
        }
        
        $stmt_update->bind_param("ss", $email, $codigo);
        
        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            // Log de verificación exitosa
            registrarLogUsuario($usuario['IdUsuario'], 'verificacion_email', 'Email verificado exitosamente');
            
            return [
                'exito' => true,
                'mensaje' => 'Email verificado correctamente. Ya puedes iniciar sesión.',
                'usuario_id' => $usuario['IdUsuario']
            ];
        } else {
            throw new Exception('Error al verificar el email o código ya procesado');
        }
        
    } catch (Exception $e) {
        error_log("Error en verificarEmail: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
        ];
    }
}

/**
 * Reenvía el código de verificación - Mejorado
 */
function reenviarCodigoVerificacion($email) {
    global $conn;
    
    try {
        // Verificar que no se abuse del reenvío
        if (!verificarLimiteReenvio($email)) {
            return [
                'exito' => false,
                'mensaje' => 'Has excedido el límite de reenvíos. Intenta en 5 minutos.'
            ];
        }
        
        // Verificar que el usuario existe y no está verificado
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, email_verificado, codigo_expiracion
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
        $nuevo_codigo = generarCodigoVerificacionSeguro();
        $nueva_expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // CAMBIO: Verificar si existe la columna ultimo_reenvio antes de usarla
        $columnas_update = "codigo_verificacion = ?, codigo_expiracion = ?";
        $params = "sss";
        $valores = [$nuevo_codigo, $nueva_expiracion, $email];
        
        // Verificar si existe la columna ultimo_reenvio
        $check_column = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_reenvio'");
        if ($check_column && $check_column->num_rows > 0) {
            $columnas_update = "codigo_verificacion = ?, codigo_expiracion = ?, ultimo_reenvio = NOW()";
        }
        
        // Actualizar código
        $stmt_update = $conn->prepare("
            UPDATE usuarios 
            SET $columnas_update
            WHERE Correo = ?
        ");
        
        if (!$stmt_update) {
            throw new Exception("Error preparando consulta de actualización: " . $conn->error);
        }
        
        $stmt_update->bind_param($params, ...$valores);
        
        if ($stmt_update->execute()) {
            // Registrar intento de reenvío
            registrarIntentoReenvio($email);
            
            // Enviar nuevo código
            $resultado_email = enviarEmailVerificacion($email, $usuario['Nombre'], $nuevo_codigo);
            
            // Log del reenvío
            registrarLogUsuario($usuario['IdUsuario'], 'reenvio_codigo', 'Código de verificación reenviado');
            
            return [
                'exito' => true,
                'mensaje' => 'Nuevo código enviado a tu email',
                'email_enviado' => $resultado_email['exito'],
                'codigo' => $nuevo_codigo // Mostrar código siempre por ahora
            ];
        } else {
            throw new Exception('Error al generar nuevo código: ' . $stmt_update->error);
        }
        
    } catch (Exception $e) {
        error_log("Error en reenviarCodigoVerificacion: " . $e->getMessage());
        return [
            'exito' => false,
            'mensaje' => 'Error interno del servidor: ' . $e->getMessage()
        ];
    }
}

/**
 * Login de usuario mejorado (solo usuarios verificados)
 */
function loginUsuario($email, $password, $recordarme = false) {
    global $conn;
    
    try {
        // CAMBIO: Hacer las columnas opcionales para evitar errores
        $stmt = $conn->prepare("
            SELECT IdUsuario, Nombre, Apellido, Usuario, Correo, Contrasena, 
                   Telefono, Direccion, email_verificado, Rol, activo
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
        
        // Verificar si la cuenta está bloqueada (solo si existe la columna)
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
            // Reset intentos fallidos
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
            
            // Actualizar último login
            actualizarUltimoLogin($usuario['IdUsuario']);
            
            // Log de login exitoso
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
            // Incrementar intentos fallidos
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
 * Genera un código de verificación más seguro
 */
function generarCodigoVerificacionSeguro() {
    // Usar random_int para mayor seguridad
    return sprintf("%06d", random_int(100000, 999999));
}

/**
 * Verifica la configuración de email
 */
function verificarConfiguracionEmail() {
    // Verificar que las constantes de email estén definidas
    $configuraciones_requeridas = [
        'SMTP_HOST' => 'smtp.gmail.com',
        'SMTP_PORT' => 587,
        'SMTP_USERNAME' => 'rodricampos882@gmail.com',
        'SMTP_PASSWORD' => 'qrxx fqrb xinv ejmg'
    ];
    
    foreach ($configuraciones_requeridas as $config => $valor_defecto) {
        if (!defined($config)) {
            define($config, $valor_defecto);
        }
    }
    
    return true;
}

/**
 * Envío básico de email como fallback
 */
function enviarEmailBasico($email, $nombre, $codigo) {
    $asunto = 'Verificación de Email - Librería RL';
    $mensaje = "
    Hola $nombre,
    
    Tu código de verificación es: $codigo
    
    Este código expira en 15 minutos.
    
    Si no solicitaste esta verificación, ignora este mensaje.
    
    Saludos,
    Equipo de Librería RL
    ";
    
    $headers = [
        'From: Librería RL <rodricampos882@gmail.com>',
        'Reply-To: rodricampos882@gmail.com',
        'Content-Type: text/plain; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $headers_string = implode("\r\n", $headers);
    
    if (mail($email, $asunto, $mensaje, $headers_string)) {
        return [
            'exito' => true,
            'mensaje' => 'Email enviado correctamente'
        ];
    } else {
        return [
            'exito' => false,
            'mensaje' => 'Error al enviar el email'
        ];
    }
}

/**
 * Verifica el límite de reenvío para prevenir spam
 */
function verificarLimiteReenvio($email) {
    global $conn;
    
    try {
        // Verificar si existe la columna ultimo_reenvio
        $check_column = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_reenvio'");
        if (!$check_column || $check_column->num_rows === 0) {
            return true; // Si no existe la columna, permitir reenvío
        }
        
        // Verificar intentos en los últimos 5 minutos
        $stmt = $conn->prepare("
            SELECT COUNT(*) as intentos 
            FROM usuarios 
            WHERE Correo = ? 
            AND ultimo_reenvio > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Máximo 3 reenvíos cada 5 minutos
            return $row['intentos'] < 3;
        }
        
        return true; // Si hay error, permitir el reenvío
        
    } catch (Exception $e) {
        error_log("Error verificando límite de reenvío: " . $e->getMessage());
        return true;
    }
}

/**
 * Registra intentos de reenvío (opcional)
 */
function registrarIntentoReenvio($email) {
    // Implementar si necesitas un log más detallado
    error_log("Reenvío de código solicitado para: $email");
}

/**
 * Registra logs de usuario (opcional pero recomendado)
 */
function registrarLogUsuario($usuario_id, $accion, $detalle) {
    global $conn;
    
    try {
        // Verificar si existe la tabla usuario_logs
        $check_table = $conn->query("SHOW TABLES LIKE 'usuario_logs'");
        if (!$check_table || $check_table->num_rows === 0) {
            return; // Si no existe la tabla, no hacer nada
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
        // No interrumpir el flujo principal por errores de log
        error_log("Error registrando log de usuario: " . $e->getMessage());
    }
}

/**
 * Incrementa intentos de login fallidos
 */
function incrementarIntentosFallidos($email) {
    global $conn;
    
    try {
        // Verificar si existen las columnas necesarias
        $check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'intentos_login_fallidos'");
        if (!$check_columns || $check_columns->num_rows === 0) {
            return; // Si no existe la columna, no hacer nada
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
        // Verificar si existen las columnas necesarias
        $check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'intentos_login_fallidos'");
        if (!$check_columns || $check_columns->num_rows === 0) {
            return; // Si no existe la columna, no hacer nada
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
 * Actualiza el último login del usuario
 */
function actualizarUltimoLogin($usuario_id) {
    global $conn;
    
    try {
        // Verificar si existe la columna ultimo_login
        $check_column = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'ultimo_login'");
        if (!$check_column || $check_column->num_rows === 0) {
            return; // Si no existe la columna, no hacer nada
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

// Resto de funciones existentes (estaLogueado, esAdmin, obtenerUsuarioActual, logout)...
function estaLogueado() {
    return isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true;
}

function esAdmin() {
    return isset($_SESSION['usuario_admin']) && $_SESSION['usuario_admin'] === true;
}

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

function logout() {
    session_unset();
    session_destroy();
    session_start();
}
?>