<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Configuración de conexión
$host = 'localhost';
$dbname = 'contratacion_temporales';
$username = 'root';
$password = '';
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

// Configuración de archivo de log
$log_file = 'log.txt'; // El archivo donde se almacenarán los logs

// Obtener los datos enviados por AJAX
$id_depto_periodo = $_POST['id_depto_periodo'];
$estado = $_POST['estado'];
//$observacion = $_POST['observacion'];  // Recibir la observación
$observacion = isset($_POST['observacion']) ? nl2br(trim($_POST['observacion'])) : "Sin observación";
 
$anioSemestre = $_POST['anio_semestre']; // Recibir el año y semestre

// Registrar el inicio del proceso
error_log("Inicio del proceso para id_depto_periodo: $id_depto_periodo", 3, $log_file);
//echo  "datos recibidos ".$anioSemestre;
error_log("Datos recibidos: id_depto_periodo=$id_depto_periodo, estado=$estado, observacion=$observacion, anio_semestre=$anioSemestre\n", 3, $log_file);

// Verificar que el estado no sea null
if ($estado !== null) {
    $dp_estado_total = ($estado === 'aceptar') ? 1 : (($estado === 'rechazar') ? null : NULL);

    try {
        // Iniciar transacción
        $conn->beginTransaction();

        // (1) Obtener fk_depto_dp desde depto_periodo
        $query_fk = "SELECT fk_depto_dp FROM depto_periodo WHERE id_depto_periodo = :id_depto_periodo";
        $stmt_fk = $conn->prepare($query_fk);
        $stmt_fk->bindParam(':id_depto_periodo', $id_depto_periodo);
        $stmt_fk->execute();
        $fk_depto_dp = $stmt_fk->fetchColumn();

        if ($fk_depto_dp === false) {
            throw new Exception("No se encontró fk_depto_dp para id_depto_periodo: $id_depto_periodo");
        }
        // Log del valor de fk_depto_dp
        error_log("fk_depto_dp obtenido: $fk_depto_dp", 3, $log_file);

        // (2) Obtener el Email desde users usando fk_depto_dp
        $query_email = "SELECT Email FROM users WHERE fk_depto_user = :fk_depto_dp";
        $stmt_email = $conn->prepare($query_email);
        $stmt_email->bindParam(':fk_depto_dp', $fk_depto_dp);
        $stmt_email->execute();
        $email_depto = $stmt_email->fetchColumn();
        $email_deptob = $email_depto;

        if ($email_depto === false) {
            throw new Exception("No se encontró un email para fk_depto_user: $fk_depto_dp");
        }
        // Log del valor de email_depto
        error_log("Email obtenido: $email_depto", 3, $log_file);

        // (3) Actualizar el estado y la observación en depto_periodo
        $query_update = "UPDATE depto_periodo 
                 SET dp_acepta_fac = :estado, 
                     dp_observacion = :observacion, 
                     dp_estado_total = :dp_estado_total 
                 WHERE id_depto_periodo = :id_depto_periodo 
                   AND periodo = :anio_semestre"; // Condición adicional para filtrar por periodo

$stmt_update = $conn->prepare($query_update);

// Vincular parámetros
$stmt_update->bindParam(':estado', $estado);
$stmt_update->bindParam(':observacion', $observacion);
$stmt_update->bindParam(':dp_estado_total', $dp_estado_total);
$stmt_update->bindParam(':id_depto_periodo', $id_depto_periodo);
$stmt_update->bindParam(':anio_semestre', $anioSemestre); // Vincular el año y semestre

// Ejecutar la consulta
$stmt_update->execute();

// Confirmar la transacción
$conn->commit();

        // Responder con el email encontrado
      //  echo "Estado y observación actualizados correctamente. Email del departamento: $email_depto";

        // Log de finalización exitosa
        error_log("Proceso finalizado correctamente para id_depto_periodo: $id_depto_periodo\n", 3, $log_file);
        
        
        $estadob = ($estado === 'aceptar') ? "aceptado" : "devuelto";

        
        // Configuración de PHPMailer para el envío de correo
$mail = new PHPMailer(true);
//$email_depto= 'elmerjs@gmail.com'; //temporal
        
try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'notificacionesvra@unicauca.edu.co'; // Cambia esto por tu correo
    $mail->Password   = 'jjnj yapg qgnl uybc'; // Cambia esto por tu contraseña
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Opciones SSL para mayor compatibilidad
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    // Configurar destinatarios
    $mail->setFrom('notificacionesvra@unicauca.edu.co', 'solicitudes vinculación');
    $mail->addAddress($email_depto, 'Destinatario');
$mail->addCC('ejurado@unicauca.edu.co'); // Enviar copia

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';  // Asegúrate de que el correo se envíe en UTF-8
    $mail->Subject = 'Notificación: solicitud de vinculación temporales con estado: ' . $estado;
   $mail->Body = "
    <p>Estimado/a,</p>
    <p>Se ha <strong>{$estadob}</strong> su solicitud de vinculación de profesores temporales, para el periodo {$anioSemestre}.</p>
    <p>Motivo: <strong>{$observacion}</strong></p>
    <p>Por favor, revise la plataforma de solicitudes de vinculación: <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a> 
    para más detalles. <em>(Acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
    <hr>
    <p style='color:gray; font-size:small;'>
        Este es un mensaje automático enviado desde el sistema de vinculación de profesores temporales. Por favor, no responda a este correo ya que no es monitoreado por personas.
    </p>
";
    // Enviar el correo
    $mail->send();
   // echo "Correo enviado correctamente a $facultad_email.";
} catch (Exception $e) {
    echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
}
        
        
        
        

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conn->rollBack();
        error_log("Error: " . $e->getMessage() . "\n", 3, $log_file);
        echo "Error: " . $e->getMessage();
    }
} else {
    error_log("Estado no válido para id_depto_periodo: $id_depto_periodo\n", 3, $log_file);
    echo "Estado no válido";
}
?>
