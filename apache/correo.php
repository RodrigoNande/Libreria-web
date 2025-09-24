<?php
// Incluir PHPMailer (asegúrate de haber instalado con composer)
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============ CONFIGURACIÓN DE EMAIL ============
// ¡IMPORTANTE! Cambia estos datos por los tuyos
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'libreriarl202509@gmail.com');  // Tu email
define('SMTP_PASSWORD', 'ncsvplubwueasrpb');      // Tu contraseña de aplicación
define('FROM_EMAIL', 'libreriarl202509@gmail.com');
define('FROM_NAME', 'CORREO');

// Función para generar código aleatorio
function generarCodigo($longitud = 6) {
    $caracteres = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codigo = '';
    for ($i = 0; $i < $longitud; $i++) {
        $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

// Función para enviar email con PHPMailer
function enviarCodigo($email, $codigo) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Configuración del charset
        $mail->CharSet = 'UTF-8';
        
        // Destinatarios
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($email);
        $mail->addReplyTo(FROM_EMAIL, FROM_NAME);
        
        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = '🔐 Tu código de verificación';
        
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .codigo { background: #fff; border: 2px dashed #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .codigo-numero { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 5px; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Código de Verificación</h1>
                    <p>Tu código de acceso ha sido generado</p>
                </div>
                <div class='content'>
                    <h2>¡Hola!</h2>
                    <p>Has solicitado un código de verificación. Aquí tienes tu código:</p>
                    
                    <div class='codigo'>
                        <p><strong>Tu código es:</strong></p>
                        <div class='codigo-numero'>$codigo</div>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong>
                        <ul>
                            <li>Este código es válido por <strong>15 minutos</strong></li>
                            <li>No compartas este código con nadie</li>
                            <li>Si no solicitaste este código, ignora este mensaje</li>
                        </ul>
                    </div>
                    
                    <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                    <p><strong>¡Gracias!</strong></p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este email.</p>
                    <p>© " . date('Y') . " Tu Aplicación. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $htmlBody;
        
        // Versión texto plano como alternativa
        $mail->AltBody = "Tu código de verificación es: $codigo\n\nEste código es válido por 15 minutos.\nSi no solicitaste este código, puedes ignorar este mensaje.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando email: {$mail->ErrorInfo}");
        return false;
    }
}

$mensaje = '';
$tipo_mensaje = '';

// Iniciar sesión
session_start();

// Procesar formulario
if ($_POST) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $codigo = generarCodigo(6);
        
        if (enviarCodigo($email, $codigo)) {
            $mensaje = "🎉 ¡Código enviado exitosamente a $email!";
            $tipo_mensaje = 'success';
            
            // Guardar datos en sesión para verificación posterior
            $_SESSION['codigo_enviado'] = $codigo;
            $_SESSION['email_codigo'] = $email;
            $_SESSION['tiempo_codigo'] = time();
            
            // Log para desarrollo (quitar en producción)
            error_log("Código generado para $email: $codigo");
        } else {
            $mensaje = "❌ Error al enviar el código. Verifica tu configuración SMTP.";
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = "⚠️ Por favor ingresa un email válido.";
        $tipo_mensaje = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envío de Códigos - PHPMailer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .icon {
            font-size: 48px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .mensaje {
            margin-top: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .mensaje.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .mensaje.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-card {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #90caf9;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .info-card h3 {
            color: #1565c0;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-list {
            color: #333;
            font-size: 14px;
        }
        
        .info-list li {
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .info-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #1565c0;
            font-weight: bold;
        }
        
        .config-warning {
            background: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .config-warning strong {
            color: #f57c00;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h1>Envío de Código</h1>
            <p>Sistema de verificación con PHPMailer</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Correo Electrónico:
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="ejemplo@correo.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Enviar Código
            </button>
        </form>
        
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-card">
            <h3>
                <i class="fas fa-info-circle"></i> Información del Sistema
            </h3>
            <ul class="info-list">
                <li>Código alfanumérico de 6 caracteres</li>
                <li>Válido por 15 minutos</li>
                <li>Envío mediante SMTP seguro</li>
                <li>Compatible con Gmail, Outlook, etc.</li>
            </ul>
        </div>
        
        <div class="config-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Configuración:</strong>
            Recuerda configurar tus credenciales SMTP en las constantes del archivo PHP.
        </div>
    </div>
    
    <script>
        // Pequeña animación para el botón
        document.querySelector('.btn').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        });
    </script>
</body>
</html>