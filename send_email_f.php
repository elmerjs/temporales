<?php
require 'vendor/autoload.php'; // Cargar PHPMailer y dependencias

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['file'], $_GET['fac'], $_GET['anio_semestre'], $_GET['email'])) {
    $file_path = $_GET['file'];
    $fac = urldecode($_GET['fac']);
    $anio_semestre = urldecode($_GET['anio_semestre']);
    $vra_email = urldecode($_GET['email']);

    try {
        // Configuración de PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ejurado@unicauca.edu.co'; // Cambia esto por tu correo
        $mail->Password = 'Portivolare5+11';         // Cambia esto por tu contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        // Configurar destinatarios
        $mail->setFrom('ejurado@unicauca.edu.co', 'solicitudes vinculación');
        $mail->addAddress($vra_email, 'Destinatario');

        // Contenido del correo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Notificación: solicitud de vinculación temporales facultad:' . $fac;
        $mail->Body = "
            <p>Estimado/a,</p>
            <p>Se ha generado una solicitud de vinculación de profesores temporales desde la facultad <strong>{$fac}</strong> para el periodo: {$anio_semestre}.</p>
            <p>Por favor, revise la plataforma solicitudes de vinculación, http://192.168.42.175/temporales/ para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
            <p>Saludos cordiales,</p>
            <p><strong>Vicerrectoría Académica</strong></p>
        ";

        // Adjuntar el archivo
        $mail->addAttachment($file_path, 'document.docx');

        // Enviar el correo
        $mail->send();
        echo "Correo enviado correctamente a $vra_email.";

        // Eliminar el archivo temporal
        unlink($file_path);
    } catch (Exception $e) {
        echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
    }
} else {
    echo "Faltan parámetros para procesar el envío.";
}
?>
