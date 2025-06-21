<?php
require('include/headerz.php');
require 'vendor/autoload.php'; // Incluye la configuración necesaria para PHPWord u otras librerías
require 'funciones.php';
use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;

            require 'vendor/phpmailer/phpmailer/src/Exception.php';
            require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Conexión a la base de datos (ajusta los parámetros según tu configuración)
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$nombre_sesion = $_SESSION['name'];
$anio_semestre = isset($_POST['anio_semestre']) ? $_POST['anio_semestre'] : '2024-2';
//$facultad_id = isset($_POST['facultad_id']) ? $_POST['facultad_id'] : '';
//var_dump($_POST);

$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_fac = $row['email_padre'];
    $email_dp = $row['Email'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user= $row['fk_depto_user'];
    $where = "";

    if ($tipo_usuario != '1') {
        $where = "WHERE email_fac LIKE '%$email_fac%'";
    }
}

// Obtener facultades
$facultades = [];
$result = $conn->query("SELECT PK_FAC, nombre_fac_minb FROM facultad $where");
while ($rowf = $result->fetch_assoc()) {
    $facultades[] = $rowf;
}

// Inicializar contadores
$total_departamentos = 0;
$total_ce_ocasional = 0;
$total_ce_total = 0;
$total_facultades_completas = 0;

// Almacenar los resultados de cada facultad para la tabla
$datos_facultades = [];

// Recorrer cada facultad y realizar la consulta específica


    
    $query_progress = "
    SELECT 
        COUNT(*) AS total_facultades,
        SUM(CASE WHEN fac_periodo.fp_estado = 1 THEN 1 ELSE 0 END) AS completed_facultades
    FROM 
        facultad 
    LEFT JOIN 
        fac_periodo ON facultad.PK_FAC = fac_periodo.fp_fk_fac 
        AND fac_periodo.fp_periodo = '$anio_semestre'
";
$result_progress = $conn->query($query_progress);
$progress_facultades = 0;

if ($result_progress->num_rows > 0) {
    $row_progress = $result_progress->fetch_assoc();
    $total_facultades = $row_progress['total_facultades'];
    $completed_facultades = $row_progress['completed_facultades'];
    
    // Calcular el progreso
    if ($total_facultades > 0) {
        $progress_facultades = ($completed_facultades / $total_facultades) * 100;
    }
}


foreach ($facultades as $facultad) {
    $facultad_id = $facultad['PK_FAC'];
  //  echo "facultad  : ". $facultad_id;
    $sql = "SELECT 
                depto_periodo.id_depto_periodo, 
                depto_periodo.fk_depto_dp, 
                deparmanentos.depto_nom_propio AS nombre_departamento,
                SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_ocasional_popayan,
                SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_regionalizacion,
                depto_periodo.dp_estado_ocasional,
                SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_catedra_popayan,
                SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_catedra_regionalizacion,
                depto_periodo.dp_estado_catedra,
                depto_periodo.dp_estado_total,
                depto_periodo.dp_acepta_fac,
                COUNT(*) OVER() AS total_filas
            FROM 
                depto_periodo
            LEFT JOIN  
                solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
            LEFT JOIN
                deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
            WHERE 
                deparmanentos.FK_FAC = $facultad_id and depto_periodo.periodo ='$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
            GROUP BY 
                depto_periodo.fk_depto_dp, deparmanentos.depto_nom_propio";

    $result = $conn->query($sql);

    // Contar los departamentos y los que tienen "ce" en dp_estado_ocasional
    $departamentos_completos = 0;
        $total_filas = 0; // Variable para almacenar total_filas

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $total_departamentos++;
            if ($row['dp_estado_total'] == '1') {
                $total_ce_total++;
                $departamentos_completos++;
            }
         //   $datos_facultades[$facultad['nombre_fac_minb']][] = $row;
                $datos_facultades[$facultad_id][] = $row;
               if ($total_filas === 0) {
                $total_filas = $row['total_filas']*2;
            }
            

        }
        // Verificar si todos los departamentos de la facultad están completos
        if ($departamentos_completos == $result->num_rows) {
            $total_facultades_completas++;
        }
        
        
    }
}
   // echo "facultad  : ". $facultad_id;

// Total de facultades
$total_facultades = count($facultades);
function obtenerTRDFacultad($facultad_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT trd_fac FROM facultad WHERE PK_FAC = '$facultad_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['trd_fac'];
        } else {
            return "FAC Desconocido";
        }
    }
function obtenerenvioaFacultad($facultad_id,$anio_semestre) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT fp_estado FROM fac_periodo WHERE fp_fk_fac = '$facultad_id' and fp_periodo ='$anio_semestre' ";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['fp_estado'];
        } else {
            return 0;
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta por Profesor y Sede</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<!-- Bootstrap JS and dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        #avanceChartContainer, #facultadesChartContainer {
            width: 250px;
            height: 250px;
            margin: auto;
        }
        #avanceChart, #facultadesChart {
            width: 100% !important;
            height: 100% !important;
        }
        .table-bordered {
            border-width: 1px; /* Ancho de las líneas divisorias */
            border-color: #ddd; /* Color de las líneas divisorias */
        }
        .table-bordered th,
        .table-bordered td {
            border-width: 1px; /* Ancho de las líneas divisorias para las celdas */
            border-color: #ddd; /* Color de las líneas divisorias para las celdas */
        }
        
          .table-container {
        width: 100%;
        overflow-x: auto;
    }
        
        #avancePorcentaje, #facultadesPorcentaje {
            font-size: 24px;
            text-align: center;
            margin-top: 20px;
        }
       .warning-message {
    color: red;
    font-size: 12px;
    margin-top: 5px;
}
          table td {
        text-align: left;
    }
        
        /* Estilo base para el contenedor */
.container {
    width: 100%; /* Ocupa el 100% del ancho por defecto */
    max-width: 1600px; /* Limita el ancho máximo en pantallas grandes */
    margin: 0 auto; /* Centra el contenedor */
    padding: 15px; /* Espacio interno */
    box-sizing: border-box; /* Incluye padding en el ancho total */
}

/* Ajustes para tabletas */
@media (max-width: 992px) {
    .container {
        max-width: 90%; /* Reducir el ancho en pantallas medianas */
        padding: 10px;
    }
}

/* Ajustes para móviles */
@media (max-width: 768px) {
    .container {
        max-width: 95%; /* Aumentar el espacio en pantallas pequeñas */
        padding: 5px;
    }
}

/* Ajustes para dispositivos muy pequeños */
@media (max-width: 480px) {
    .container {
        max-width: 100%; /* Ocupa todo el ancho */
        padding: 5px;
    }
}
       .encabezado-facultad {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding: 3px;
    border: 1px solid #ddd;
    background-color: #f9f9f9;
    border-radius: 5px;
    font-family: Arial, sans-serif;
}

.encabezado-facultad .nombre-facultad,
.encabezado-facultad .estado-envio {
    flex: 1;
    padding: 5px;
}

.encabezado-facultad strong {
    color: #333;
    margin-right: 5px;
} 
       .formulario-estado {
    background-color: transparent; /* Eliminar el fondo */
    padding: 0; /* Eliminar padding */
    border: none; /* Eliminar el borde */
    border-radius: 5px;
    display: flex;
    align-items: center;
    margin-bottom: 1px;
    font-size: 14px; /* Mantener tamaño de fuente moderado */
          height: 42px; /* Mantener altura compacta */
         justify-content: flex-end; /* Alinear los elementos a la derecha */

}

.formulario-estado label {
    margin-right: 8px; /* Separar el label de los inputs */
    font-size: 14px;
    color: #333;
}

.formulario-estado select,
.formulario-estado input[type='submit'] {
    padding: 4px 8px; /* Mantener padding en el select y el botón */
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    height: 28px; /* Mantener altura compacta */
    margin-right: 10px; /* Separar el select del botón */
}

.formulario-estado input[type='submit'] {
    background-color: #007bff;
    color: white;
    cursor: pointer;
    height: 28px; /* Asegurar que el botón tenga la misma altura que el select */
}

.formulario-estado input[type='submit']:hover {
    background-color: #0056b3;
}
        
.observationModal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-contentb {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 300px;
    text-align: center;
}

button {
    padding: 10px;
    margin-top: 10px;
}
        .modal-contentb textarea {
    width: 100%;
    box-sizing: border-box; /* Asegura que el padding no aumente el ancho */
    margin-top: 10px;
    resize: vertical; /* Permite solo redimensionar verticalmente */
}
    </style>
</head>
<body>
    <br>
    <div class="container">
        <h2 align ="center">Consulta por Facultad y  Sede <?php echo $anio_semestre; ?></h2>
        
        <div class="row">
            <div class="col-md-8">
                <?php
                if (!empty($datos_facultades)) {
                    foreach ($datos_facultades as $id_facultad => $datos) {
                        $envio=obtenerenvioaFacultad($id_facultad,$anio_semestre);  
                        $acepta_vra = obteneraceptacionvra($id_facultad, $anio_semestre);
                         if ($tipo_usuario==1 || $tipo_usuario==2) {
                        if ($envio == 1) {
         $envio_estado = '<i class="fas fa-check-circle" style="color:green;"></i>';
    } else {
        $envio_estado = '<i class="fas fa-hourglass-half text-warning" style="color:red;"></i>';
    }
                             
                             if ($acepta_vra === null || $acepta_vra == 0) {
    $acepta_estado = '<i class="fas fa-hourglass-half text-warning" style="color:red;"></i> Pendiente';
} elseif ($acepta_vra == 1) {
    $acepta_estado = '<i class="fas fa-times-circle" style="color:red;"></i> Rechazado';
} elseif ($acepta_vra == 2) {
    $acepta_estado = '<i class="fas fa-check-circle" style="color:green;"></i> Aceptado';
}
                             
                         } else{$envio_estado='';$acepta_estado='';}                        
                        
                         $sql_nombre = "SELECT nombre_fac_minb, email_fac FROM facultad WHERE PK_FAC = $id_facultad";
    $result_nombre = $conn->query($sql_nombre);
    $nombre_facultadx = 'Desconocido'; // Valor predeterminado en caso de que no se encuentre el nombre

    if ($result_nombre->num_rows > 0) {
        $row_nombre = $result_nombre->fetch_assoc();
        $nombre_facultad = $row_nombre['nombre_fac_minb'];
                    $email_facultad = $row_nombre['email_fac'];

    }

                        
                
// Mostrar la facultad con los estados solo si el tipo de usuario es 1
if ($tipo_usuario == 1) {
    
    
    echo "
<div class='encabezado-facultad'>
    <div class='nombre-facultad'>
        <strong>Facultad:</strong> $nombre_facultad
    </div>
    <div class='estado-envio'>
        <strong>Recibido V.R.A:</strong> $envio_estado
            

    </div>
     <div class='estado-envio'>
     
                <strong>Respuesta V.R.A:</strong> $acepta_estado 

    </div>
</div>
";
 // nuevo estado respusta vra $acepta_vra envio desde fac $envio  
    
  //  echo "<h3>Facultad: $nombre_facultad / Envíado a V.R.A: $envio_estado </h3>";//Respuesta V.R.A.: $acepta_estado</h3> (se quito para er si mejroa)
} else {
echo "
<div class='encabezado-facultad'>
    <div class='nombre-facultad'>
        <strong>Facultad:</strong> $nombre_facultad
    </div>
</div>
";}           
if ($tipo_usuario == 1 && $envio == 1) {
    // Generamos el formulario para cada facultad con los valores correspondientes
    echo "
    <div class='formulario-estado'>
        <form id='estadoForm$id_facultad' action='' method='POST'>
            <input type='hidden' name='id_facultad' value='$id_facultad' />
            <input type='hidden' name='email_facultad' value='$email_facultad' />  <!-- Aseguramos que el email de la facultad sea enviado -->
            <input type='hidden' name='nombre_facultad' value='$nombre_facultad' />  <!-- Nombre de la facultad -->
                    <input type='hidden' name='envio_a_facultad' value='$envio' />  <!-- Aseguramos que el envio de la facultad sea enviado -->
            <input type='hidden' name='anio_semestre' value='$anio_semestre' />  <!-- Añadimos el campo del año-semestre -->

            <label for='estado_vra'>Estado:</label>
            <select name='estado_vra' id='estado_vra$id_facultad' onchange='showObservationPrompt($id_facultad, this.value);'>
                <option value='0' " . ($acepta_vra == 0 || $acepta_vra == null ? 'selected' : '') . ">Pendiente</option>
                <option value='1' " . ($acepta_vra == 1 ? 'selected' : '') . ">Rechazar</option>
                <option value='2' " . ($acepta_vra == 2 ? 'selected' : '') . ">Aceptar</option>
            </select>
            <input type='submit' value='Actualizar Estadox' id='submitButton$id_facultad' disabled />
        </form>
    </div>";

    // Verificar si se ha enviado el formulario para esta facultad
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['estado_vra']) && isset($_POST['id_facultad'])) {
        // Obtener el nuevo estado y la facultad desde el formulario
        $nuevo_estado = $_POST['estado_vra'];
        $id_facultad = $_POST['id_facultad'];
        $observacion = isset($_POST['observacion']) ? $_POST['observacion'] : ''; // Obtener la observación
        $nuevo_envio = $_POST['envio_a_facultad'];  // Correo de la facultad

        // Variables ocultas enviadas desde el formulario
        $email_facultad = $_POST['email_facultad'];  // Correo de la facultad
        $email_prueba = 'elmerjs@gmail.com';
        $nombre_facultad = $_POST['nombre_facultad'];  // Nombre de la facultad

        // Realizar la actualización en la base de datos
        $fp_periodo = $anio_semestre;
if ($nuevo_estado != 2) {
    $nuevo_envio = 0;
}
       // Prepara la consulta SQL para actualizar fp_acepta_vra, fp_obs_acepta y fp_estado
$sql_update = "UPDATE fac_periodo 
               SET fp_acepta_vra = ?, 
                   fp_obs_acepta = ?, 
                   fp_estado = ?
               WHERE fp_periodo = ? AND fp_fk_fac = ?";

// Ejecutar la consulta con los nuevos valores
if ($stmt = $conn->prepare($sql_update)) {
    $stmt->bind_param('issii', $nuevo_estado, $observacion, $nuevo_envio, $fp_periodo, $id_facultad);
    $stmt->execute();
    $stmt->close();

            // Enviar el correo si el estado ha cambiado a "Aceptar" o "Rechazar"
            if ($nuevo_estado == 1 || $nuevo_estado == 2) {
                if ($nuevo_estado == 2) {
                    $valor_estado = "aceptado";
                } elseif ($nuevo_estado == 1) {
                    $valor_estado = "rechazado";
                } else {
                    $valor_estado = "pendiente";
                }

                // Configuración de PHPMailer
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
                    $mail->addAddress($email_prueba, 'Destinatario'); //pendiente cambiar a email facultad

                    // Contenido del correo
                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';  // Asegúrate de que el correo se envíe en UTF-8
                    $mail->Subject = 'preuba Notificación de respuesta vinculación temporales facultad: ' . $nombre_facultad;
                    $mail->Body    = "
                        <p>Estimado/a,</p>{$email_facultad}
                        <p>Se ha generado una respuesta a vinculación de profesores temporales de la facultad <strong>{$nombre_facultad}</strong> para el periodo: {$anio_semestre}, estado: {$valor_estado}, observación: {$observacion}</p> 
                        <p>Por favor, revise la plataforma solicitudes de vinculación, <a href='http://192.168.42.175/temporales/'>aquí</a> para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
                        <p>Saludos cordiales,</p>
                        <p><strong>Vicerrectoría Académica</strong></p>
                    ";

                    // Enviar el correo
                    $mail->send();
                } catch (Exception $e) {
                    // Si ocurre un error al enviar el correo
                    echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
                }
            }

            // Redirigir a la página actual para evitar el reenvío de formulario
          echo "<script>
        window.location.href = 'report_depto_full.php?anio_semestre=" . $anio_semestre . "';
      </script>";
exit();
        } else {
            echo "<p>Error al actualizar el estado.</p>";
        }
    }

    // Modal para cada facultad: generamos el popup dinámicamente
    echo "
    <div id='observationModal$id_facultad' class='observationModal' style='display:none;'>
        <div class='modal-contentb'>
            <h3>Por favor, ingrese una observación para $anio_semestre la facultad $nombre_facultad:</h3>
<textarea id='observationText$id_facultad' anio: '$anio_semestre' rows='4' style='width: 100%;'></textarea>
            <br>
            <button onclick='submitFormWithObservation($id_facultad)'>Guardar</button>
            <button onclick='closeModal($id_facultad)'>Cancelar</button>
        </div>
    </div>";
}

                 ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Departamento</th>
                                    <th>Tipo Prof</th>
                                    <th>Popayán</th>
                                    <th>Regnlzn</th>
                                    <th>Cierre</th> <!-- Nueva columna para el estado -->
                                    
                                    <th>Envío a Facultad</th> <!-- Nueva columna para el estado -->
                                    <th style="text-align: center">Acción Fac</th> 

                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $nombre_departamento_anterior = null;

                            foreach ($datos as $row) {
                                $bg_color = $row['dp_estado_total'] == 1 ? '#d4edda' : '#f8d7da';

                                if ($row['nombre_departamento'] !== $nombre_departamento_anterior) {
                                    echo "<tr>";
                                 echo "<td style='background-color: $bg_color; text-align: left;' rowspan='2'>
                            <form action='consulta_todo_depto.php' method='POST' style='display:inline;'>
                                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['fk_depto_dp']) . "'>
                                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                                <button type='submit' style='background:none;border:none;color:#00008B;cursor:pointer;'>" . htmlspecialchars($row['nombre_departamento']) . "</button>
                            </form>
                          </td>";
                                    $nombre_departamento_anterior = $row['nombre_departamento'];
                                } else {
                                    echo "<tr>";
                                }
                                ?>
                                <td>Ocasional</td>
                                <td><?php echo htmlspecialchars($row['total_ocasional_popayan']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_ocasional_regionalizacion']); ?></td>
                               <td>
    <?php
    echo ($row['dp_estado_ocasional'] == 'ce') ? '<i class="fas fa-lock text-success"></i>' : '<i class="fas fa-lock-open text-danger"></i>';
    ?>
</td>
                                
                                
                                <td rowspan="2" class="align-middle text-center">
                    <form action="consulta_todo_depto.php" method="POST" style="display:inline;">
                        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($row['fk_depto_dp']); ?>">
                        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                        <button type="submit" style="background:none;border:none;cursor:pointer;">
                            <?php
                            echo ($row['dp_estado_total'] == 1) ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>';
                            ?>
                        </button>
                    </form>
                                                    </td> 
                                
                          <?php
$td_disabled = ($row['dp_estado_total'] <> 1); // Verificar si dp_estado_total es diferente de 1
?>
   <td rowspan="2" class="align-middle text-center" 
    style="<?= $td_disabled ? 'background-color: #f8f9fa; color: #6c757d; pointer-events: none;' : '' ?>">
    <!-- Mostrar el estado actual -->
    <div class="mb-2">
        <?php
        if ($row['dp_acepta_fac'] === 'aceptar') {
            echo '<span class="text-success">Aceptada por la facultad</span>';
        } elseif ($row['dp_acepta_fac'] === 'rechazar') {
            echo '<span class="text-danger">Rechazada por la facultad</span>';
        } elseif ($row['dp_acepta_fac'] === 'subsanado') {
            echo '<span class="text-info">Subsanado-pendiente</span>';
        } else {
            echo '<span class="text-warning">Pendiente</span>';
        }
        ?>
    </div>
    <?php 
    // Verificar si se deben mostrar los botones
    if (!$td_disabled) {
        if ($row['dp_acepta_fac'] === 'rechazar') { 
            // Prioridad: Si está rechazado, no mostrar botones
            echo '<span class="text-muted">Acciones no disponibles</span>';
        } elseif ($row['dp_acepta_fac'] !== 'aceptar' || $acepta_vra  == 1) {
            if ($tipo_usuario == '2') { // Mostrar botones solo si el tipo de usuario es '2'
                if ($envio != 1) { // Mostrar botones solo si envio <> 1
                    ?>
                    <!-- Formulario con un campo oculto para el estado -->
                    <form id="estadoForm_<?= $row['id_depto_periodo'] ?>" method="POST">
                        <input type="hidden" name="estado" value="<?= $row['dp_acepta_fac'] ?>" />
                        <input type="hidden" name="anio_semestre" value="<?= $anio_semestre ?>" /> <!-- Campo oculto -->
                        <button type="button" class="btn btn-success btn-sm" 
                            onclick="actualizarEstado(<?= $row['id_depto_periodo'] ?>, 'aceptar', '<?= $anio_semestre ?>')">
                            Aceptar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" 
                            onclick="actualizarEstado(<?= $row['id_depto_periodo'] ?>, 'rechazar', '<?= $anio_semestre ?>')">
                            Rechazar
                        </button>
                    </form>
                    <?php 
                } else { 
                    // Mensaje cuando envio es igual a 1
                    echo '<span class="text-muted">Acciones no disponibles</span>';
                }
            } else {
                // Mensaje si tipo_usuario no es '2'
                echo '<span class="text-muted">Acciones no disponibles</span>';
            }
        } else {
            // Mensaje si el estado es 'aceptar' y no aplica fp_acepta_vra
            echo '<span class="text-muted">Acciones no disponibles</span>';
        }
    } else {
        // Mensaje si está deshabilitado
        echo '<span class="text-muted">Acciones no disponibles</span>';
    }
    ?>
</td>

                                <tr>
                                <td>Cátedra</td>
                                <td><?php echo htmlspecialchars($row['total_catedra_popayan']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_catedra_regionalizacion']); ?></td>
                                <td>
                                    <a href="indexsolicitud.php?departamento_id=<?php echo urlencode($row['fk_depto_dp']); ?>">
                                        <?php
                                        echo ($row['dp_estado_catedra'] == 'ce') ? '<i class="fas fa-lock text-success"></i>' : '<i class="fas fa-lock-open text-danger"></i>';
                                        ?>
                                    </a>
                                </td>
                                 
                                
                                    
                                </tr>
                                <?php
                            }
                            ?>
                                
                                
                            </tbody>
                        </table>
                        <?php
                    }
                } else {
                    echo "<p>No se encontraron resultados.</p>";
                }
                ?>
            </div>
    
            <div class="col-md-4">
             <?php //echo htmlspecialchars($tipo_usuario); ?>
                
                <h3 align ="center">Avance Departamentos</h3>
                <div id="avanceChartContainer">
                    <canvas id="avanceChart"></canvas>
                </div>
                <div id="avancePorcentaje">
                    <!-- Aquí se mostrará dinámicamente el porcentaje de avance -->
                </div>   <?php 
                    if ($tipo_usuario == '1') {?>
                <br>
                
               <h3 align="center">Avance Facultades</h3>
<div id="facultadesChartContainer">
    <canvas id="facultadesChart"></canvas>
</div>

<div id="facultadesPorcentaje">
    <!-- Aquí se mostrará dinámicamente el porcentaje de facultades completas -->
</div>
<?php } ?>
<div align="center">
    <?php 
    if ($tipo_usuario == '1') {
        $facultad_id = 0;
    }
    ?>
    <?php 
    if ($tipo_usuario != '1') {
       // echo "tipo usuario.:". $tipo_usuario."facultad id:".$facultad_id;
        
         if ($envio != 1) { ?>
   
    <div>
        <br>
        <!-- Button to Open the Modal -->
       
       
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#oficioModal">
            <i class="fas fa-download"></i> Aprobar y Generar Documento para V.R.A
        </a> 
    </div>
    <?php  }
        else {  ?>
             <div>
        <a href="oficio_fac2_nuevaplantillatopcero_reimprimirb.php?facultad_id=<?php echo urlencode($facultad_id); ?>&anio_semestre=<?php echo urlencode($anio_semestre); ?>" 
   class="btn btn-secondary">
    <i class="fas fa-print"></i> Reimprimir Documento
</a>
    
    </div> 
                 <?php 
             }
    
    $envio = obtenerenvioaFacultad($facultad_id, $anio_semestre);
    $decano = obtenerDecano($nombre_facultad);

    echo "<h4 align='center'>Enviado a V.R.A.: " . 
        ($envio == 1 
            ? "OK <i class='fas fa-check-circle' style='color: green;'></i>" 
            : "NO <i class='fas fa-exclamation-circle' style='color: red;'></i>") 
        . "</h4>";
        
       
    ?>
    
    
    <a href="#" 
   class="btn btn-warning <?php echo ($envio == 1 && $acepta_vra != 2) ? '' : 'disabled'; ?>" 
   onclick="return <?php echo ($envio == 1 && $acepta_vra != 2) ? "confirmarDeshacerEnvio('$facultad_id', '$anio_semestre');" : "false;"; ?>">
    <i class="fas fa-undo-alt"></i> Deshacer envío a V.R.A (Habilitar Edición)
</a>
    <div class="warning-message <?php echo ($envio == 1) ? 'd-none' : ''; ?>">
        No existe aprobación previa para deshacer
    </div>
    <br>
    <?php }
   
    if ($tipo_usuario != '1')  {
    echo "<h4 align='center'>Respuesta V.R.A: " . 
    ($acepta_vra == 0 
        ? "<span style='text-decoration: underline; text-decoration-color: orange;'>Pendiente <i class='fas fa-clock' style='color: orange;'></i></span>" 
        : ($acepta_vra == 1 
            ? "<span style='text-decoration: underline; text-decoration-color: red;'>Rechazada <i class='fas fa-exclamation-circle' style='color: red;'></i></span>" 
            : "<span style='text-decoration: underline; text-decoration-color: green;'>OK <i class='fas fa-check-circle' style='color: green;'></i></span>")
    ) 
. "</h4>";
   }
    
    ?>
   
    <div class="report-buttons" style="display: flex; justify-content: center; gap: 20px;">
        <!-- Botón para descargar el reporte general -->
        <a href="excel_temporales.php?tipo_usuario=<?php echo htmlspecialchars($tipo_usuario); ?>&facultad_id=<?php echo htmlspecialchars($facultad_id); ?>&anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" class="btn btn-success">
            <i class="fas fa-download"></i> Reporte General
        </a>

        <!-- Botón para descargar el reporte estilizado -->
        <a href="excel_temporales_fac.php?tipo_usuario=<?php echo htmlspecialchars($tipo_usuario); ?>&facultad_id=<?php echo htmlspecialchars($facultad_id); ?>&anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" class="btn btn-success">
            <i class="fas fa-download"></i> Reporte Imprimible
        </a>
    </div>

        </div>
    </div>

     <script>
 function actualizarEstado(idDeptoPeriodo, estado, anioSemestre) { 
    // Preguntar si el usuario quiere incluir una observación
    var incluirObservacion = confirm("¿Desea incluir una observación? Esto es importante para saber el motivo de rechazo.");

    var observacion = '';  // Definir una variable vacía para la observación

    // Si el usuario desea incluir una observación
    if (incluirObservacion) {
        // Mostrar un cuadro de texto para ingresar la observación
        observacion = prompt("Por favor, ingrese su observación:");

        // Si el usuario no ingresa nada y cierra el cuadro de texto, la observación será vacía
        if (observacion === null || observacion.trim() === "") {
            observacion = "Sin observación"; // Si no se da observación, se guarda un valor por defecto
        }
    }

    // Realizar la solicitud AJAX para actualizar el estado en la base de datos
    $.ajax({
        url: 'actualizar_aceptacion_fac.php',
        type: 'POST',
        data: {
            id_depto_periodo: idDeptoPeriodo,
            estado: estado,
            observacion: observacion,  // Enviar la observación junto con el estado  
            anio_semestre: anioSemestre // Enviar también el año y semestre

        },
        success: function(response) {
            alert('Estado actualizado a ' + estado + '. Observación: ' + observacion);
            
            // Recargar la página después de actualizar el estado
            window.location.href = "report_depto_full.php?anio_semestre=" + encodeURIComponent(anioSemestre);
        },
        error: function(xhr, status, error) {
            alert('Error al actualizar el estado: ' + error);
        }
    });
}
 
        var ctxAvance = document.getElementById('avanceChart').getContext('2d');
        var avanceChart = new Chart(ctxAvance, {
            type: 'doughnut',
            data: {
                labels: ['Confirmado', 'Pendiente'],
                datasets: [{
                    label: 'Avance',
                    data: [<?php echo $total_ce_total; ?>, <?php echo $total_departamentos - $total_ce_total; ?>],
                    backgroundColor: ['#28a745', '#ff6384'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw;
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Calcula y muestra el porcentaje de avance
        var totalDepartamentos = <?php echo $total_departamentos; ?>;
        var totalCeTotal = <?php echo $total_ce_total; ?>;
        var avancePorcentaje = document.getElementById('avancePorcentaje');

     if (totalDepartamentos > 0) {
    var porcentajeAvance = (totalCeTotal / totalDepartamentos * 100).toFixed(2);
    avancePorcentaje.innerHTML = "<strong style='font-size: 20px;'>Porcentaje de Avance: </strong><span style='font-size: 20px;'>" + porcentajeAvance + "%</span>";
} else {
    avancePorcentaje.innerHTML = "<strong style='font-size: 20px;'>Porcentaje de Avance: </strong><span style='font-size: 20px;'>N/A</span>";
}

        var ctxFacultades = document.getElementById('facultadesChart').getContext('2d');
        var facultadesChart = new Chart(ctxFacultades, {
            type: 'doughnut',
            data: {
                labels: ['Completas', 'Incompletas'],
                datasets: [{
                    label: 'Facultades',
                    data: [<?php echo $completed_facultades; ?>, <?php echo $total_facultades - $total_facultades_completas; ?>],
                    backgroundColor: ['#007bff', '#ffc107'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw;
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // Calcula y muestra el porcentaje de facultades completas
        var totalFacultades = <?php echo $total_facultades; ?>;
        var totalFacultadesCompletas = <?php echo $completed_facultades; ?>;
        var facultadesPorcentaje = document.getElementById('facultadesPorcentaje');

        if (totalFacultades > 0) {
            var porcentajeFacultades = (totalFacultadesCompletas / totalFacultades * 100).toFixed(2);
            facultadesPorcentaje.innerHTML = "<strong>Porcentaje de Facultades Completas: </strong>" + porcentajeFacultades + "%";
        } else {
            facultadesPorcentaje.innerHTML = "<strong>Porcentaje de Facultades Completas: </strong> N/A";
        }
    </script>

<script>
function confirmarDeshacerEnvio(facultadId, anioSemestre) {
    var mensaje = "Deshacer envío, puede realizar modificaciones a los departamentos. ¿Desea continuar?";
    
    if (confirm(mensaje)) {
        // Crear un objeto XMLHttpRequest
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "deshacer_envio.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Manejar la respuesta del servidor
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status === "closed") {
                    alert(response.message);
                    return; // Salir de la función si el período está cerrado
                } else if (response.status === "success") {
                    // Recargar la página después de la solicitud exitosa
                    location.reload();
                } else {
                    // Mostrar mensaje de error si hay un problema
                    alert(response.message);
                }
            }
        };

        // Enviar la solicitud con los parámetros facultadId y anioSemestre
        xhr.send("facultad_id=" + facultadId + "&anio_semestre=" + anioSemestre);
    }
}
   
</script>

<script>
// Función para mostrar el popup cuando se selecciona "Aceptar" o "Rechazar"
function showObservationPrompt(id, value) {
    // Solo mostrar el popup si el valor no es "Pendiente"
    if (value != '0') {
        // Mostrar el mensaje emergente específico para esta facultad
        document.getElementById('observationModal' + id).style.display = 'block';
        // Deshabilitar el botón de envío hasta que se ingrese una observación
        document.getElementById('submitButton' + id).disabled = true;
    }
}

// Función para cerrar el popup
function closeModal(id) {
    // Ocultar el popup para esta facultad
    document.getElementById('observationModal' + id).style.display = 'none';
    // Habilitar el botón de envío
    document.getElementById('submitButton' + id).disabled = false;
}

// Función para enviar el formulario con la observación
function submitFormWithObservation(id) {
    const observation = document.getElementById('observationText' + id).value;
    if (observation.trim() === '') {
        alert('Por favor ingrese una observación.');
        return;
    }

    // Agregar la observación al formulario como un campo oculto
    const form = document.getElementById('estadoForm' + id);
    const observationInput = document.createElement('input');
    observationInput.type = 'hidden';
    observationInput.name = 'observacion';
    observationInput.value = observation;
    form.appendChild(observationInput);

    // Habilitar el botón de enviar
    document.getElementById('submitButton' + id).disabled = false;

    // Enviar el formulario
    form.submit();
}
</script>
</body>
<!-- Modal -->

<!-- The Modal -->
<!-- The Modal -->
<!-- Modal -->
<div class="modal fade" id="oficioModal" tabindex="-1" aria-labelledby="oficioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="oficioModalLabel">Información del Oficio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="oficioForm">
                    <div class="form-group">
                        <label for="numeroOficio">Número de Oficio</label>
                        <input type="text" class="form-control" id="numeroOficio" name="numero_oficio" value="<?php echo obtenerTRDFacultad($facultad_id); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fechaOficio">Fecha de Oficio</label>
                        <input type="date" class="form-control" id="fechaOficio" name="fecha_oficio" value="<?php 
$fecha_actual = date('Y-m-d');echo $fecha_actual; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="decano">Decano</label>
                        <input type="text" class="form-control" id="decano" name="decano" value="<?php echo htmlspecialchars($decano); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="elaboradoPor">Elaborado por</label>
                        <input type="text" class="form-control" id="elaboradoPor" name="elaborado_por" required>
                    </div>
                    <!-- Hidden inputs for existing parameters -->
                    <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                    <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="submitOficioForm()">Enviar</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitOficioForm() {
    // Obtener valores del formulario
    var fechaOficio = document.getElementById('fechaOficio').value;
    var decano = document.getElementById('decano').value;
    var elaboradoPor = document.getElementById('elaboradoPor').value; // Obtener el valor de 'elaboradoPor'
    var numeroOficio = document.getElementById('numeroOficio').value; // Obtener el valor de 'numeroOficio'

    // Verificar que los campos no estén vacíos
    if (fechaOficio === '' || decano === '' || elaboradoPor === '' || numeroOficio === '') {
        alert('Por favor, llene todos los campos.');
        return;
    }

    // Obtener valores de las variables PHP
    var facultadId = "<?php echo urlencode($facultad_id); ?>";
    var anioSemestre = "<?php echo urlencode($anio_semestre); ?>";

    // Construir la URL con los parámetros
    var url = 'oficio_fac2_nuevaplantillatopcero.php?fecha_oficio=' + encodeURIComponent(fechaOficio) +
        '&decano=' + encodeURIComponent(decano) +
        '&elaborado_por=' + encodeURIComponent(elaboradoPor) + // Agregar 'elaboradoPor' a la URL
        '&numero_oficio=' + encodeURIComponent(numeroOficio) + // Agregar 'numeroOficio' a la URL
        '&facultad_id=' + facultadId +
        '&anio_semestre=' + anioSemestre;

    // Redireccionar a la URL
    window.location.href = url;

    // Cerrar el modal
    $('#oficioModal').modal('hide');

    // Espera 2 segundos (2000 milisegundos) y luego recarga la página
    setTimeout(function() {
        window.location.reload();
    }, 2000); // Puedes ajustar el tiempo de espera según tus necesidades
}

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</html>

<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>
