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
        // Configuración SMTP - Puedes usar Gmail, Outlook, o cualquier proveedor SMTP
        $this->smtp_host = 'smtp.gmail.com'; // Para Gmail
        $this->smtp_port = 587; // Puerto TLS
        $this->smtp_username = 'rodricampos882@gmail.com'; // Tu email
        $this->smtp_password = 'qrxx fqrb xinv ejmg'; // Contraseña de aplicación de Gmail
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
     * Envía un email usando PHPMailer
     */
    private function enviarEmail($to_email, $subject, $html_body, $text_body = '') {
        try {
            // Si tienes PHPMailer instalado (recomendado)
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
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
     * Envía email usando PHPMailer (RECOMENDADO)
     */
    private function enviarConPHPMailer($to_email, $subject, $html_body, $text_body) {
        require_once 'vendor/autoload.php'; // Si instalaste PHPMailer con Composer
        // O si lo descargaste manualmente:
        // require_once 'PHPMailer/src/PHPMailer.php';
        // require_once 'PHPMailer/src/SMTP.php';
        // require_once 'PHPMailer/src/Exception.php';
        
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
            
            // Remitente
            $mail->setFrom($this->from_email, $this->from_name);
            
            // Destinatario
            $mail->addAddress($to_email);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->AltBody = $text_body;
            $mail->SMTPDebug = 2; // Muestra la depuración SMTP en pantalla
            $mail->Debugoutput = 'error_log'; // Envía la depuración al log de errores de PHP
            $mail->send();
            
            return [
                'exito' => true,
                'mensaje' => 'Email enviado correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al enviar email: ' . $mail->ErrorInfo
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
     * Genera la plantilla HTML para el email de verificación
     */
    private function generarPlantillaVerificacion($nombre, $codigo) {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verificación de Email</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #f4f4f4;
                }
                .container {
                    background: white;
                    margin: 20px;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #120049, #4834d4);
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .content {
                    padding: 40px 30px;
                }
                .code-container {
                    background: #f8f9fa;
                    border: 2px solid #4834d4;
                    border-radius: 10px;
                    padding: 20px;
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

/**
 * Función helper para enviar código de verificación
 */
function enviarCodigoVerificacion($email, $nombre, $codigo) {
    $emailService = new EmailService();
    return $emailService->enviarCodigoVerificacion($email, $nombre, $codigo);
}
?>