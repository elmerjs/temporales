<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Verificar si el email existe en la base de datos
    $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $sql = "SELECT Id, Name FROM users WHERE Email='$email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['Id'];
        $user_name = $row['Name'] ?? 'Usuario'; // Valor por defecto si no hay nombre
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));
        
        // Guardar token en la base de datos
        $sql = "INSERT INTO password_resets (user_id, token, expiry) VALUES ('$user_id', '$token', '$expiry') 
                ON DUPLICATE KEY UPDATE token='$token', expiry='$expiry'";
        
        if ($conn->query($sql)) {
            // Configurar PHPMailer
            $mail = new PHPMailer(true);
            $reset_link = "http://192.168.42.175/temporales/reset-password.php?token=$token";
            
            try {
                // Configuración del servidor SMTP (usando tus credenciales existentes)
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'notificacionesvra@unicauca.edu.co';
                $mail->Password   = 'jjnj yapg qgnl uybc';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                // Opciones SSL para mayor compatibilidad
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];

                // Configurar destinatarios
                $mail->setFrom('notificacionesvra@unicauca.edu.co', 'Sistema Temporales Unicauca');
                $mail->addAddress($email, $user_name);
                $mail->addBCC('ejurado@unicauca.edu.co'); // Copia oculta

                // Contenido del correo (versión HTML mejorada)
                $mail->isHTML(true);
                $mail->Subject = 'Recuperación de contraseña - Temporales Unicauca';
                
             $mail->Body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
    <h2 style='color: #0056b3;'>Recuperación de contraseña - Temporales Unicauca</h2>
    <p>Hola $user_name,</p>
    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el Sistema de Temporales Unicauca.</p>
    
    <div style='background-color: #fff8e1; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0;'>
        <p style='color: #ff6f00; font-weight: bold; margin: 0;'>
            <i class='fas fa-exclamation-triangle'></i> Importante:
        </p>
        <p style='margin: 8px 0 0 0;'>
            Para completar el proceso de recuperación,  acceder al siguiente enlace 
            <strong>desde la red interna de la Universidad del Cauca</strong> 
            (red cableada o WiFi institucional). Si estás usando un portátil, 
            conéctate a la red WiFi de la universidad.
        </p>
    </div>
    
    <p>Por favor, haz clic en el siguiente enlace para continuar con el proceso:</p>
    <p style='text-align: center; margin: 20px 0;'>
        <a href='$reset_link' style='background-color: #0056b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Restablecer contraseña</a>
    </p>
    
    <p>Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
    <code style='word-break: break-all;'>$reset_link</code></p>
    
    <p><strong>Este enlace expirará en 1 hora.</strong></p>
    <p>Si no solicitaste este cambio, por favor ignora este mensaje.</p>
    
    <hr style='border-top: 1px solid #eee; margin: 20px 0;'>
    
    <p style='font-size: 12px; color: #777;'>
        Este es un mensaje automático enviado desde el sistema de vinculación de profesores temporales. 
        Por favor, no responda a este correo ya que no es monitoreado por personas.
    </p>
</div>
";
                // Versión alternativa en texto plano
                $mail->AltBody = "Hola $user_name,\n\nPara restablecer tu contraseña, visita este enlace:\n\n$reset_link\n\nEste enlace expirará en 1 hora.\n\nSi no solicitaste este cambio, ignora este mensaje.";

                // Enviar el correo
                $mail->send();
                
                header("Location: forgot-password.php?success=1");
                exit();
                
            } catch (Exception $e) {
                // Log del error y redirección con mensaje de error
                error_log("Error al enviar correo de recuperación: " . $mail->ErrorInfo);
                header("Location: forgot-password.php?error=mail");
                exit();
            }
        } else {
            header("Location: forgot-password.php?error=db");
            exit();
        }
    } else {
        header("Location: forgot-password.php?error=1");
        exit();
    }
    
    $conn->close();
}
?>