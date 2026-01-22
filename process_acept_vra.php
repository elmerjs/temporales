<?php
require 'funciones.php';

 use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
        
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'];
    $fp_periodo = $data['fp_periodo'];
    $id_facultad = $data['id_facultad'];
    $observation = isset($data['observation']) ? $data['observation'] : null;

    $sql_update = '';
    $fac_email=obteneremailfac($id_facultad);
    $fac_nombre=obtenernombrefac($id_facultad);
    //$fac_email =    penditen  de coloorar real
   // $fac_email= 'elmerjs@unicauca.edu.co'; //pendiente enviar a usuarios tipo 1

// Configuración de PHPMailer para el envío de correo
$mail = new PHPMailer(true);

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
    $mail->addAddress($fac_email, 'Destinatario');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';  // Asegúrate de que el correo se envíe en UTF-8
    $mail->Subject = 'Notificación:  Respuesta - Novedad en vinculación temporales facultad:' . $fac_nombre;


    
    if ($action === 'accept') {
        $sql_update = "UPDATE fac_periodo 
                       SET fp_acepta_vra = 2
                       WHERE fp_periodo = '$fp_periodo' AND fp_fk_fac = $id_facultad";
        $mail->Body = "
    <p>Cordial saludo,</p>
    <p>Se ha ACEPTADO su solicitud de vinculación de profesores temporales,facultad <strong>{$fac_nombre}</strong> para el periodo: {$fp_periodo}.</p>
    
    <p>Por favor, revise la plataforma de solicitudes de vinculación en la siguiente dirección: 
    <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a>. 
    <em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
    <p>Saludos cordiales,</p>
    <p><strong>Vicerrectoría Académica</strong></p>
";
    
        
        
        
    } elseif ($action === 'reject') {
        $sql_update = "UPDATE fac_periodo 
                       SET fp_acepta_vra = 1, fp_obs_acepta = '$observation'
                       WHERE fp_periodo = '$fp_periodo' AND fp_fk_fac = $id_facultad";
        
        $mail->Body = "
    <p>Estimado/a,</p>
    <p>Se ha devuelto su solicitud de vinculación de profesores temporales desde  {$fac_nombre}: para el periodo: {$fp_periodo}.</p>
    <p><strong>Observación:</strong> {$observation}</p>
    <p>Por favor, revise la plataforma de solicitudes de vinculación en la siguiente dirección: 
    <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a>. 
    <em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
    <p>Saludos cordiales,</p>
    <p><strong>Vicerrectoría Académica</strong></p>
";
        
    }
   $mail->send();
    
}catch (Exception $e) {
  //  echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
}
    // Conexión y ejecución del SQL
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->query($sql_update) === TRUE) {
    echo json_encode([
        'message' => 'Actualización exitosa',
        'fp_periodo' => $fp_periodo,
        'id_facultad' => $id_facultad
    ]);
}else {
        echo json_encode(['message' => 'Error al actualizar: ' . $conn->error]);
    }

    $conn->close();
}
?>
