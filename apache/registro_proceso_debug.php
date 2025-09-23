<?php
// registro_proceso_debug.php - Versión con debug activado
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['exito' => false, 'mensaje' => '', 'debug_info' => []];

function debugLog($mensaje, $datos = null) {
    global $response;
    $log_entry = date('[Y-m-d H:i:s] ') . $mensaje;
    if ($datos) {
        $log_entry .= ' - Datos: ' . json_encode($datos);
    }
    $response['debug_info'][] = $log_entry;
    error_log($log_entry);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("Iniciando proceso de registro");
    
    try {
        // Verificar conexión a la base de datos
        if (!$conn) {
            throw new Exception("Error de conexión a la base de datos: " . mysqli_connect_error());
        }
        debugLog("Conexión a BD exitosa");
        
        // Recoger datos del POST
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'contrasena' => $_POST['contrasena'] ?? ''
        ];
        
        debugLog("Datos recibidos", $datos);
        
        // Validaciones básicas
        $camposRequeridos = ['nombre', 'apellido', 'correo', 'usuario', 'contrasena'];
        foreach ($camposRequeridos as $campo) {
            if (empty($datos[$campo])) {
                $response['mensaje'] = "El campo $campo es obligatorio";
                debugLog("Validación fallida: campo $campo vacío");
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        debugLog("Validaciones básicas pasadas");
        
        // Validar email
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $response['mensaje'] = 'El formato del correo electrónico no es válido';
            debugLog("Validación fallida: email inválido");
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        debugLog("Email válido");
        
        // Validar contraseña
        if (strlen($datos['contrasena']) < 6) {
            $response['mensaje'] = 'La contraseña debe tener al menos 6 caracteres';
            debugLog("Validación fallida: contraseña muy corta");
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        debugLog("Contraseña válida");
        
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT IdUsuario FROM usuarios WHERE Correo = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta de email: " . $conn->error);
        }
        
        $stmt->bind_param("s", $datos['correo']);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta de email: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['mensaje'] = 'Este correo electrónico ya está registrado';
            debugLog("Email ya existe en BD");
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        debugLog("Email disponible");
        
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT IdUsuario FROM usuarios WHERE Usuario = ?");
        if (!$stmt) {
            throw new Exception("Error preparando consulta de usuario: " . $conn->error);
        }
        
        $stmt->bind_param("s", $datos['usuario']);
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta de usuario: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response['mensaje'] = 'Este nombre de usuario ya está en uso';
            debugLog("Usuario ya existe en BD");
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        debugLog("Usuario disponible");
        
        // Generar código de verificación
        $codigo_verificacion = sprintf("%06d", rand(100000, 999999));
        $expiracion_codigo = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        debugLog("Código generado", ['codigo' => $codigo_verificacion, 'expiracion' => $expiracion_codigo]);
        
        // Hashear contraseña
        $password_hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
        debugLog("Contraseña hasheada exitosamente");
        
        // Preparar valores para inserción (manejar NULLs)
        $telefono = !empty($datos['telefono']) ? $datos['telefono'] : null;
        $direccion = !empty($datos['direccion']) ? $datos['direccion'] : null;
        
        debugLog("Preparando inserción en BD");
        
        // Insertar usuario - VERSIÓN SIMPLIFICADA PARA DEBUG
        $stmt = $conn->prepare("
            INSERT INTO usuarios (
                Nombre, Apellido, Usuario, Correo, Contrasena, 
                Telefono, Direccion, email_verificado, codigo_verificacion, 
                codigo_expiracion, fecha_registro, activo
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW(), 1)
        ");
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta de inserción: " . $conn->error);
        }
        
        debugLog("Consulta preparada, ejecutando bind_param");
        
        $bind_result = $stmt->bind_param("sssssssss", 
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
        
        if (!$bind_result) {
            throw new Exception("Error en bind_param: " . $stmt->error);
        }
        
        debugLog("bind_param exitoso, ejecutando consulta");
        
        if ($stmt->execute()) {
            $usuario_id = $conn->insert_id;
            debugLog("Usuario insertado exitosamente", ['user_id' => $usuario_id]);
            
            // Intentar enviar email (simplificado para debug)
            $email_enviado = false;
            $error_email = '';
            
            try {
                if (file_exists('email_servicio.php')) {
                    require_once 'email_servicio.php';
                    if (class_exists('EmailService')) {
                        $emailService = new EmailService();
                        $resultado_email = $emailService->enviarCodigoVerificacion(
                            $datos['correo'], 
                            $datos['nombre'], 
                            $codigo_verificacion
                        );
                        $email_enviado = $resultado_email['exito'] ?? false;
                        $error_email = $resultado_email['mensaje'] ?? '';
                        debugLog("Intento de envío de email", $resultado_email);
                    } else {
                        debugLog("Clase EmailService no encontrada");
                    }
                } else {
                    debugLog("Archivo email_servicio.php no encontrado");
                }
            } catch (Exception $e) {
                debugLog("Error enviando email: " . $e->getMessage());
                $error_email = $e->getMessage();
            }
            
            // Respuesta exitosa
            $response = [
                'exito' => true,
                'mensaje' => 'Usuario registrado correctamente. ' . 
                           ($email_enviado ? 'Te enviamos un código a tu email.' : 'Error al enviar email, usa el código mostrado.'),
                'codigo' => $codigo_verificacion, // Mostrar código para debug
                'email_enviado' => $email_enviado,
                'debug_info' => $response['debug_info']
            ];
            
            if (!$email_enviado && !empty($error_email)) {
                $response['error_email'] = $error_email;
            }
            
            debugLog("Proceso completado exitosamente");
            
        } else {
            throw new Exception('Error al insertar usuario en BD: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        debugLog("EXCEPCIÓN CAPTURADA: " . $e->getMessage());
        debugLog("Stack trace: " . $e->getTraceAsString());
        
        $response['mensaje'] = 'Error interno del servidor: ' . $e->getMessage();
        $response['error_detallado'] = $e->getMessage();
        $response['linea_error'] = $e->getLine();
        $response['archivo_error'] = $e->getFile();
    }
    
} else {
    $response['mensaje'] = 'Método no permitido';
    debugLog("Método no permitido: " . $_SERVER['REQUEST_METHOD']);
}

debugLog("Enviando respuesta final");
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>