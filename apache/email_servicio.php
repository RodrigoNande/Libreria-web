<?php
// email_servicio.php - Servicio de envío de emails
require_once 'conexion.php';

class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Configuración SMTP - Gmail
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_port = 587;
        $this->smtp_username = 'rodricampos882@gmail.com';
        $this->smtp_password = 'qrxx fqrb xinv ejmg'; // Tu contraseña de aplicación
        $this->from_email = 'rodricampos882@gmail.com';
        $this->from_name = 'Librería RL';
    }
    
    /**
     * Envía un email de verificación con código
     */
    public function enviarCodigoVerificacion($email, $nombre, $codigo) {
        $subject = 'Verificación de Email - Librería RL';
        $html_body = $this->generarPlantillaVerificacion($nombre, $codigo);
        $text_body = "Hola $nombre,\n\nTu código de verificación es: $codigo\n\nEste código expira en 15 minutos.\n\nSi no solicitaste esta verificación, ignora este mensaje.\n\nSaludos,\nEquipo de Librería RL";
        
        return $this->enviarEmail($email, $subject, $html_body, $text_body);
    }
    
    /**
     * Envía un email
     */
    private function enviarEmail($to_email, $subject, $html_body, $text_body = '') {
        try {
            // Intentar usar PHPMailer si está disponible
            if ($this->phpmailerDisponible()) {
                return $this->enviarConPHPMailer($to_email, $subject, $html_body, $text_body);
            } else {
                // Fallback usando la función mail() nativa de PHP
                return $this->enviarConMailNativo($to_email, $subject, $html_body, $text_body);
            }
        } catch (Exception $e) {
            error_log("Error enviando email: " . $e->getMessage());
            return [
                'exito' => false,
                'mensaje' => 'Error al enviar el email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica si PHPMailer está disponible
     */
    private function phpmailerDisponible() {
        // Verificar si PHPMailer está instalado con Composer
        if (file_exists('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
            return class_exists('PHPMailer\PHPMailer\PHPMailer');
        }
        
        // Verificar si PHPMailer está instalado manualmente
        if (file_exists('PHPMailer/src/PHPMailer.php') && 
            file_exists('PHPMailer/src/SMTP.php') && 
            file_exists('PHPMailer/src/Exception.php')) {
            
            require_once 'PHPMailer/src/PHPMailer.php';
            require_once 'PHPMailer/src/SMTP.php';
            require_once 'PHPMailer/src/Exception.php';
            
            return class_exists('PHPMailer\PHPMailer\PHPMailer');
        }
        
        return false;
    }
    
    /**
     * Envía email usando PHPMailer
     */
    private function enviarConPHPMailer($to_email, $subject, $html_body, $text_body) {
        // Cargar las clases de PHPMailer
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\SMTP;
        use PHPMailer\PHPMailer\Exception;
        
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Configuración adicional para debugging (comentar en producción)
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            // $mail->Debugoutput = 'html';
            
            // Remitente
            $mail->setFrom($this->from_email, $this->from_name);
            
            // Destinatario
            $mail->addAddress($to_email);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->AltBody = $text_body;
            
            $mail->send();
            
            // Log del email enviado (opcional)
            $this->logEmail($to_email, 'verificacion', $subject, 'enviado');
            
            return [
                'exito' => true,
                'mensaje' => 'Email enviado correctamente con PHPMailer'
            ];
            
        } catch (Exception $e) {
            // Log del error
            $error_message = $mail->ErrorInfo;
            $this->logEmail($to_email, 'verificacion', $subject, 'fallido', $error_message);
            
            error_log("Error enviando email a $to_email: " . $error_message);
            
            return [
                'exito' => false,
                'mensaje' => 'Error al enviar email con PHPMailer: ' . $error_message
            ];
        }
    }
    
    /**
     * Envía email usando la función mail() nativa (BÁSICO)
     */
    private function enviarConMailNativo($to_email, $subject, $html_body, $text_body) {
        $headers = [
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $headers_string = implode("\r\n", $headers);
        
        if (mail($to_email, $subject, $html_body, $headers_string)) {
            $this->logEmail($to_email, 'verificacion', $subject, 'enviado');
            return [
                'exito' => true,
                'mensaje' => 'Email enviado correctamente con mail() nativo'
            ];
        } else {
            $this->logEmail($to_email, 'verificacion', $subject, 'fallido', 'Error con mail() nativo');
            return [
                'exito' => false,
                'mensaje' => 'Error al enviar el email con mail() nativo'
            ];
        }
    }
    
    /**
     * Registra el email en la base de datos (opcional)
     */
    private function logEmail($email, $tipo, $asunto, $estado, $mensaje_error = null) {
        global $conn;
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO email_log (email_destino, tipo, asunto, estado, mensaje_error) 
                VALUES (?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("sssss", $email, $tipo, $asunto, $estado, $mensaje_error);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Error logging no debe interrumpir el flujo principal
            error_log("Error logging email: " . $e->getMessage());
        }
    }
    
    /**
     * Genera la plantilla HTML para el email de verificación
     */
    private function generarPlantillaVerificacion($nombre, $codigo) {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verificación de Email - Librería RL</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #f8f9fa;
                    padding: 20px;
                }
                .container {
                    background: white;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #120049, #4834d4);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .header p {
                    margin: 10px 0 0 0;
                    opacity: 0.9;
                    font-size: 16px;
                }
                .content {
                    padding: 40px 30px;
                }
                .greeting {
                    font-size: 18px;
                    margin-bottom: 20px;
                    color: #2c3e50;
                }
                .code-container {
                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                    border: 2px solid #4834d4;
                    border-radius: 12px;
                    padding: 30px;
                    text-align: center;
                    margin: 30px 0;
                }
                .verification-code {
                    font-size: 36px;
                    font-weight: bold;
                    color: #4834d4;
                    letter-spacing: 8px;
                    font-family: 'Courier New', monospace;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px 30px;
                    font-size: 14px;
                    color: #666;
                    text-align: center;
                    border-top: 1px solid #eee;
                }
                .warning {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    color: #856404;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⭐ Librería RL</h1>
                    <p>Verificación de Email</p>
                </div>
                
                <div class='content'>
                    <h2>¡Hola, " . htmlspecialchars($nombre) . "!</h2>
                    <p>Gracias por registrarte en Librería RL. Para completar tu registro, necesitamos verificar tu dirección de correo electrónico.</p>
                    
                    <p>Usa el siguiente código de verificación:</p>
                    
                    <div class='code-container'>
                        <div class='verification-code'>$codigo</div>
                        <p style='margin: 10px 0 0 0; color: #666;'>Código de Verificación</p>
                    </div>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                            <li>Este código expira en <strong>15 minutos</strong></li>
                            <li>No compartas este código con nadie</li>
                            <li>Si no solicitaste esta verificación, ignora este mensaje</li>
                        </ul>
                    </div>
                    
                    <p>Una vez verificado tu email, podrás acceder a todas las funcionalidades de nuestra tienda en línea.</p>
                    
                    <p>¡Esperamos que disfrutes de tu experiencia de compra con nosotros!</p>
                </div>
                
                <div class='footer'>
                    <p><strong>Librería RL</strong></p>
                    <p>Este es un mensaje automático, por favor no respondas a este email.</p>
                    <p style='margin-top: 15px; font-size: 12px;'>
                        Si tienes problemas con la verificación, contacta nuestro soporte.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>