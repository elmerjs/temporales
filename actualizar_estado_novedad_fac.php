<?php
// Incluir archivo de conexión
include 'cn.php';
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'funciones.php';
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Obtener datos del POST
$id_novedad = $_POST['id_novedad'];
$estado = $_POST['estado'];
$motivo = isset($_POST['motivo']) ? $_POST['motivo'] : null;

// Validar datos
if (!isset($id_novedad, $estado)) {
    echo 'Datos insuficientes para actualizar.';
    exit;
}

if ($estado == 1 && empty($motivo)) {
    echo 'Debe proporcionar un motivo para rechazar.';
    exit;
}

//sacar los datos que estan con anterioraridad en solicitudes novedades : 

// Preparar la consulta con un WHERE
$sqlQuery = "SELECT * FROM solicitudes_novedades WHERE id_novedad = ? ";
$stmt = mysqli_prepare($con, $sqlQuery);

// Verificar si la preparación fue exitosa
if ($stmt) {
    // Vincular el parámetro a la consulta
    mysqli_stmt_bind_param($stmt, "i", $id_novedad); // "i" indica que el parámetro es un entero

    // Ejecutar la consulta
    mysqli_stmt_execute($stmt);

    // Obtener el resultado
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        // Procesar los datos obtenidos
        $rowData = mysqli_fetch_assoc($result);

        // Guardar los valores en variables
        $facultad_id = $rowData['facultad_id'];
        $departamento_id = $rowData['departamento_id'];
        $periodo_anio = $rowData['periodo_anio'];
        $tipo_docente = $rowData['tipo_docente'];
        $tipo_usuario = $rowData['tipo_usuario'];
        $tipo_novedad = $rowData['tipo_novedad'];
        $detalle_novedad = json_decode($rowData['detalle_novedad'], true); // Decodificar JSON
        $usuario_id = $rowData['usuario_id'];
        $fecha_creacion = $rowData['fecha_creacion'];
        $sn_acepta_fac = $rowData['sn_acepta_fac'];
        $sn_obs_fac = $rowData['sn_obs_fac'];
        $sn_acepta_vra = $rowData['sn_acepta_vra'];
        $sn_obs_vra = $rowData['sn_obs_vra'];

        // Mostrar las variables obtenidas (para depuración o uso posterior)
       /* echo "Facultad ID: $facultad_id<br>";
        echo "Departamento ID: $departamento_id<br>";
        echo "Período Año: $periodo_anio<br>";
        echo "Tipo Docente: $tipo_docente<br>";
        echo "Tipo Usuario: $tipo_usuario<br>";
        echo "Tipo Novedad: $tipo_novedad<br>";
        echo "Detalle Novedad (JSON):<br>";
        if (is_array($detalle_novedad)) {
            foreach ($detalle_novedad as $key => $value) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;$key: $value<br>";
            }
        } else {
            echo "No se pudo decodificar el JSON.<br>";
        }
        echo "Usuario ID: $usuario_id<br>";
        echo "Fecha Creación: $fecha_creacion<br>";
        echo "SN Acepta Facultad: $sn_acepta_fac<br>";
        echo "SN Observación Facultad: $sn_obs_fac<br>";
        echo "SN Acepta VRA: $sn_acepta_vra<br>";
        echo "SN Observación VRA: $sn_obs_vra<br>";*/
    } else {
        echo "No se encontró ningún registro con id_novedad = $id_novedad.";
    }

    // Cerrar la declaración
    mysqli_stmt_close($stmt);
} else {
    echo "Error al preparar la consulta: " . mysqli_error($con);
}

//termina sacarlos datos de soilkicitudes novedasdses

// Preparar y ejecutar consulta
$query = "UPDATE solicitudes_novedades SET sn_acepta_fac = ?, sn_obs_fac = ? WHERE id_novedad = ?";
$stmt = mysqli_prepare($con, $query);

if ($stmt) {
    // Enlazar parámetros y ejecutar
    mysqli_stmt_bind_param($stmt, 'isi', $estado, $motivo, $id_novedad);
    if (mysqli_stmt_execute($stmt)) {
        echo 'Estado actualizado correctamente. no olvide generar envío a V.R.A.';
        
        //generar word
       
if ($estado ==2){
$vra_email= 'elmerjs@gmail.com'; //pendiente enviar a usuarios tipo 1
} elseif($estado ==1) {
$vra_email= 'elmerjs@unicauca.edu.co'; //pendiente enviar a usuarios tipo 1
}
// Configuración de PHPMailer para el envío de correo
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ejurado@unicauca.edu.co'; // Cambia esto por tu correo
    $mail->Password   = 'Portivolare5+11'; // Cambia esto por tu contraseña
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
    $mail->setFrom('ejurado@unicauca.edu.co', 'solicitudes vinculación');
    $mail->addAddress($vra_email, 'Destinatario');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';  // Asegúrate de que el correo se envíe en UTF-8
    $mail->Subject = 'Notificación: Novedad en vinculación temporales facultad:' . $facultad_id;
 // Lógica para manejar los campos en función del tipo de novedad y tipo de docente
if ($tipo_novedad== 'adicionar' || $tipo_novedad == 'modificar') {
    if ($tipo_docente == 'Ocasional') {
        // Si es "Ocasional" y "adicionar", ocultar "horas" y "horas_r"
        unset($detalle_novedad['horas']);
        unset($detalle_novedad['horas_r']);
    } elseif ($tipo_docente== 'Cátedra') {
        // Si es "Cátedra" y "adicionar", ocultar "tipo_dedicacion" y "tipo_dedicacion_r"
        unset($detalle_novedad['tipo_dedicacion']);
        unset($detalle_novedad['tipo_dedicacion_r']);
    }
}

// Generar la lista de detalles de la novedad
$detalles_html = "";
if (!empty($detalle_novedad) && is_array($detalle_novedad)) {
    foreach ($detalle_novedad as $key => $value) {
        $detalles_html .= "<li><strong>" . ucfirst($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
    }
} else {
    $detalles_html = "<li>No especificado</li>";
}
if ($estado ==2){
// Configurar el cuerpo del correo
$mail->Body = "
    <p>Estimado/a,</p>
    <p>Se ha generado una Novedad de vinculación de profesores temporales desde la facultad <strong>{$facultad_id}</strong> para el periodo: {$periodo_anio}.</p>
    <p><strong>Detalles de la novedad:</strong></p>
    <ul>
        <li><strong>Tipo de docente:</strong> {$tipo_docente}</li>
        <li><strong>Tipo de novedad:</strong> {$tipo_novedad}</li>
        <li><strong>Detalle de la novedad:</strong></li>
        <ul>
            {$detalles_html}
        </ul>
        <li><strong>Observaciones de la facultad:</strong> {$sn_obs_fac}</li>
    </ul>
    <p>Por favor, revise la plataforma de solicitudes de vinculación en la siguiente dirección: 
    <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a>. 
    <em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
    <p>Saludos cordiales,</p>
    <p><strong>Vicerrectoría Académica</strong></p>
";
}elseif ($estado ==1)
{
    $mail->Body = "
    <p>Estimado/a,</p>
    <p>Se ha rechazado su  Novedad de vinculación de profesores temporales desde el departamento:  <strong>{$departamento_id}</strong> para el periodo: {$periodo_anio}.</p>
    <p><strong>Detalles:</strong></p>
      <ul>
        <li><strong>motivo:</strong> {$motivo}</li>
       
    </ul>
    <p>Por favor, revise la plataforma de solicitudes de vinculación en la siguiente dirección: 
    <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a>. 
    <em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
    <p>Saludos cordiales,</p>
    <p><strong>Vicerrectoría Académica</strong></p>
";
    
}
    // Enviar el correo
    $mail->send();
  //  echo "Correo enviado correctamente a $vra_email.";
} catch (Exception $e) {
  //  echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
}
        //termina generar word
    } else {
        echo 'Error al actualizar el estado: ' . mysqli_error($con);
    }
    mysqli_stmt_close($stmt);
} else {
    echo 'Error al preparar la consulta: ' . mysqli_error($con);
}

mysqli_close($con);
?>
