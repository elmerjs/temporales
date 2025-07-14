<?php
// for_45_web_elaborated.php
// Este script genera una versión HTML del formato FOR-45, replicando la estructura del .docx

require 'funciones.php'; // Asegúrate de que este archivo contiene la función existeSolicitudAnterior si la usas
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos

// Obtener los valores de las variables (pueden ser null si no están presentes)
$id_solicitud = $_GET['id_solicitud'] ?? null;
$departamento_id = $_GET['departamento_id'] ?? null;
$anio_semestre = $_GET['anio_semestre'] ?? null;

$numero_acta = $_GET['numero_acta'] ?? null;
$fecha_acta_str = isset($_GET['fecha_actab']) ? $_GET['fecha_actab'] : null;

// Parsear la fecha para Día, Mes, Año si está presente
$day = $month = $year = '';
if ($fecha_acta_str) {
    list($year, $month, $day) = explode('-', $fecha_acta_str);
}

// Variables para almacenar los resultados de la consulta SQL
$nombre_facultad = null;
$nombre_departamento = null;
$periodo_consulta = null;
$email_solicitante = null;
$nombre_solicitante = null;
$cedula_solicitante = null;
$tipo_docente = null;
$vinculacion_ocasional = null;
$vinculacion_ocasional_reg = null;
$horas_p = null;
$horas_r = null;
$anexa_hv_nuevo = null;
$actualiza_hv_antiguo = null;

// Nuevos campos recibidos del modal (ya actualizados en la BD por el script principal)
$pregrado = $_GET['pregrado'] ?? null;
$especializacion = $_GET['especializacion'] ?? null;
$maestria = $_GET['maestria'] ?? null;
$doctorado = $_GET['doctorado'] ?? null;
$otro_estudio = $_GET['otro_estudio'] ?? null;
$experiencia_docente = $_GET['experiencia_docente'] ?? null;
$experiencia_profesional = $_GET['experiencia_profesional'] ?? null;
$otra_experiencia = $_GET['otra_experiencia'] ?? null;

// Realizar la consulta a la base de datos para obtener todos los datos
if (isset($anio_semestre) && isset($departamento_id) && isset($id_solicitud)) {
    $sql = "SELECT
                facultad.Nombre_fac_minb,
                deparmanentos.depto_nom_propio,
                depto_periodo.periodo,
                depto_periodo.dp_acta_periodo,
                depto_periodo.dp_fecha_acta,
                solicitudes.nombre,
                solicitudes.cedula,
                tercero.email,
                solicitudes.tipo_docente,
                solicitudes.tipo_dedicacion AS vincul_ocasional,
                solicitudes.tipo_dedicacion_r AS vicul_ocasional_reg,
                solicitudes.horas,
                solicitudes.horas_r,
                solicitudes.anexa_hv_docente_nuevo,
                solicitudes.actualiza_hv_antiguo,
                solicitudes.pregrado,
                solicitudes.especializacion,
                solicitudes.maestria,
                solicitudes.doctorado,
                solicitudes.otro_estudio,
                solicitudes.experiencia_docente,
                solicitudes.experiencia_profesional,
                solicitudes.otra_experiencia
            FROM depto_periodo
            JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
            JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
            JOIN solicitudes ON (solicitudes.anio_semestre = depto_periodo.periodo AND solicitudes.departamento_id = depto_periodo.fk_depto_dp)
            JOIN tercero ON tercero.documento_tercero = solicitudes.cedula
            WHERE depto_periodo.periodo = ?
              AND depto_periodo.fk_depto_dp = ?
              AND solicitudes.id_solicitud = ?";

    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sii", $anio_semestre, $departamento_id, $id_solicitud);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $nombre_facultad = $fila['Nombre_fac_minb'];
            $nombre_departamento = $fila['depto_nom_propio'];
            $periodo_consulta = $fila['periodo'];
            $numero_acta = $fila['dp_acta_periodo'];
            $fecha_acta_str_db = $fila['dp_fecha_acta']; // Fecha de la BD
            
            // Si la fecha de la BD es más relevante, úsala
            if ($fecha_acta_str_db) {
                list($year, $month, $day) = explode('-', $fecha_acta_str_db);
            }

            $nombre_solicitante = $fila['nombre'];
            $cedula_solicitante = $fila['cedula'];
            $email_solicitante = $fila['email'];
            $tipo_docente = $fila['tipo_docente'];
            $vinculacion_ocasional = $fila['vincul_ocasional'];
            $vinculacion_ocasional_reg = $fila['vicul_ocasional_reg'];
            $horas_p = $fila['horas'];
            $horas_r = $fila['horas_r'];
            $anexa_hv_nuevo = $fila['anexa_hv_docente_nuevo'];
            $actualiza_hv_antiguo = $fila['actualiza_hv_antiguo'];

            // Estos campos ya vienen del GET y fueron actualizados en la BD, se usan los de la BD
            $pregrado = $fila['pregrado'];
            $especializacion = $fila['especializacion'];
            $maestria = $fila['maestria'];
            $doctorado = $fila['doctorado'];
            $otro_estudio = $fila['otro_estudio'];
            $experiencia_docente = $fila['experiencia_docente'];
            $experiencia_profesional = $fila['experiencia_profesional'];
            $otra_experiencia = $fila['otra_experiencia'];
        }
        $stmt->close();
    }
}
$con->close(); // Cerrar la conexión después de todas las consultas

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formato FOR-45 - Vista Web Elaborada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0.5cm 1.5cm; /* Margen superior/inferior 0.5cm, izq/der 1.5cm */
            font-size: 9pt;
            box-sizing: border-box;
            background-color: #f0f0f0; /* Fondo para que el "documento" resalte */
        }
        .document-page {
            width: 100%; /* Ocupa el ancho disponible */
            max-width: 27.94cm; /* Reducido a tamaño carta horizontal */
            background-color: white;
            border: 1px solid #ccc;
            padding: 0.5cm 1.5cm; /* Padding interno para simular márgenes del documento */
            box-sizing: border-box;
            margin: 20px auto; /* Centrar la "página" en la pantalla */
            box-shadow: 0 0 10px rgba(0,0,0,0.1); /* Sombra ligera */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px; /* Espacio más reducido entre tablas */
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 3px; /* Reducir padding en celdas */
            text-align: left;
            vertical-align: top;
            font-size: 9pt;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .text-right { /* Nuevo estilo para alinear a la derecha */
            text-align: right;
        }
        .header-img {
            width: 100%;
            /* Eliminado: max-width: 695px; */ /* Permite que la imagen tome todo el ancho del contenedor padre */
            display: block;
            margin: 0 auto 5px auto; /* Reducir margen inferior */
        }
        .footer-content {
            clear: both;
            padding-top: 10px;
            position: relative; /* Para posicionar la imagen del footer */
        }
        .footer-img {
            width: 80px;
            position: absolute;
            right: 0;
            bottom: 0;
        }
        .no-border {
            border: none;
        }
        .checkbox-symbol {
            font-size: 1em; /* Ajustar el tamaño del símbolo */
            line-height: 1;
        }
        .centered-checkbox-cell {
            text-align: center !important;
            padding: 0 !important; /* Elimina padding para centrado exacto del checkbox */
            vertical-align: middle;
        }
        .bold {
            font-weight: bold;
        }
        /* Estilos específicos para celdas de encabezado de tabla 1 para un ajuste visual */
        .table1-header-cell {
            height: 1.5cm; /* Aproximar altura de celdas de encabezado */
            vertical-align: middle;
        }
        /* Ajustes finos para que el texto "Título(s)" esté centrado verticalmente */
        .vertical-align-middle {
            vertical-align: middle;
        }
        .vertical-align-top {
            vertical-align: top;
        }
        .small-line-height {
            line-height: 1.2; /* Para reducir el espacio entre líneas si es necesario */
        }
        /* Ajuste para el footer */
        .footer-text-line {
            margin: 0; /* Eliminar márgenes predeterminados del párrafo */
            padding: 0;
            line-height: 1.0; /* Reducir aún más el interlineado */
        }
    </style>
</head>
<body>

    <div class="document-page">
        <img src="img/encabezadofor45.png" alt="Encabezado" class="header-img">

        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="table1-header-cell">Facultad</th>
                    <th rowspan="2" class="table1-header-cell">Departamento</th>
                    <th rowspan="2" class="table1-header-cell">Número de Acta de Selección</th>
                    <th colspan="3" class="table1-header-cell">Fecha de Acta de Selección</th>
                </tr>
                <tr>
                    <th>Día</th>
                    <th>Mes</th>
                    <th>Año</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td rowspan="1" class="vertical-align-middle"><?php echo htmlspecialchars($nombre_facultad ?? ''); ?></td>
                    <td rowspan="1" class="vertical-align-middle"><?php echo htmlspecialchars($nombre_departamento ?? ''); ?></td>
                    <td rowspan="1" class="text-center vertical-align-middle"><?php echo htmlspecialchars($numero_acta ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($day); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($month); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($year); ?></td>
                </tr>
            </tbody>
        </table>

        <div style="height: 5px;"></div>

        <table style="width: auto;">
            <tr>
                <td style="width: 150px; font-weight: bold; border-left: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000;">Periodo académico</td>
                <td style="width: 150px; border-right: 1px solid #000; border-top: 1px solid #000; border-bottom: 1px solid #000;"><?php echo htmlspecialchars($periodo_consulta ?? ''); ?></td>
            </tr>
        </table>
        
        <div style="height: 5px;"></div>

        <table>
            <tr>
                <td style="width: 20%;" class="bold">Nombre Docente</td>
                <td style="width: 45%;"><?php echo htmlspecialchars($nombre_solicitante ?? ''); ?></td>
                <td style="width: 15%;" class="bold">Identificación</td>
                <td style="width: 20%;"><?php echo htmlspecialchars($cedula_solicitante ?? ''); ?></td>
            </tr>
            <tr>
                <td style="width: 20%;" class="bold">Correo Electrónico</td>
                <td colspan="3"><?php echo htmlspecialchars($email_solicitante ?? ''); ?></td>
            </tr>
        </table>

        <div style="height: 5px;"></div>

        <table>
            <thead>
                <tr>
                    <th colspan="2">Ocasional</th>
                    <th colspan="2">Planta</th>
                    <th>Cátedra</th>
                </tr>
                <tr>
                    <th>MT</th>
                    <th>TC</th>
                    <th>MT</th>
                    <th>TC</th>
                    <th>Horas semana</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="centered-checkbox-cell">
                        <span class="checkbox-symbol"><?php echo (($vinculacion_ocasional === 'MT' || $vinculacion_ocasional_reg === 'MT') ? '&#9745;' : '&#9744;'); ?></span>
                    </td>
                    <td class="centered-checkbox-cell">
                        <span class="checkbox-symbol"><?php echo (($vinculacion_ocasional === 'TC' || $vinculacion_ocasional_reg === 'TC') ? '&#9745;' : '&#9744;'); ?></span>
                    </td>
                    <td class="centered-checkbox-cell">
                        <span class="checkbox-symbol">&#9744;</span>
                    </td>
                    <td class="centered-checkbox-cell">
                        <span class="checkbox-symbol">&#9744;</span>
                    </td>
                    <td class="text-center">
                        <?php
                        $suma_horas = '';
                        if ($tipo_docente === 'Catedra') {
                            $suma_horas = ($horas_p ?? 0) + ($horas_r ?? 0);
                        }
                        echo htmlspecialchars($suma_horas);
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="height: 5px;"></div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 60%;" class="vertical-align-middle">Requisitos de estudio</th>
                    <th colspan="2" style="width: 40%;" class="vertical-align-middle">Experiencia</th>
                </tr>
                <tr>
                    <th>Tipo</th>
                    <th>Años</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-left bold vertical-align-middle">Título(s)</td>
                    <td colspan="2"></td> </tr>
                <tr>
                    <td class="text-left">Pregrado(s): <?php echo htmlspecialchars($pregrado ?? ''); ?></td>
                    <td class="text-left">Docente:</td>
                    <td class="text-left"><?php echo htmlspecialchars($experiencia_docente ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="text-left">Especialización(s): <?php echo htmlspecialchars($especializacion ?? ''); ?></td>
                    <td class="text-left">Profesional:</td>
                    <td class="text-left"><?php echo htmlspecialchars($experiencia_profesional ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="text-left">Maestría(s): <?php echo htmlspecialchars($maestria ?? ''); ?></td>
                    <td class="text-left" rowspan="2" class="vertical-align-middle">Otra:</td>
                    <td class="text-left" rowspan="2" class="vertical-align-middle"><?php echo htmlspecialchars($otra_experiencia ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="text-left">Doctorado(s): <?php echo htmlspecialchars($doctorado ?? ''); ?></td>
                </tr>
                <tr>
                    <td class="text-left">Otro: <?php echo htmlspecialchars($otro_estudio ?? ''); ?></td>
                    <td colspan="2"></td> </tr>
            </tbody>
        </table>

        <div style="height: 5px;"></div>

        <table>
            <tr>
                <td style="width: 75%;" class="bold text-left">El Docente ha estado vinculado con la Universidad del Cauca:</td>
                <td style="width: 5%;" class="bold text-right">SI</td>
                <td class="centered-checkbox-cell" style="width: 5%;">
                    <span class="checkbox-symbol"><?php echo (function_exists('existeSolicitudAnterior') && existeSolicitudAnterior($cedula_solicitante, $anio_semestre) ? '&#9745;' : '&#9744;'); ?></span>
                </td>
                <td style="width: 5%;" class="bold text-center">NO</td>
                <td class="centered-checkbox-cell" style="width: 5%;">
                    <span class="checkbox-symbol"><?php echo (!(function_exists('existeSolicitudAnterior') && existeSolicitudAnterior($cedula_solicitante, $anio_semestre)) ? '&#9745;' : '&#9744;'); ?></span>
                </td>
            </tr>
            <tr>
                <td style="width: 75%;" class="bold text-left">Se anexa historia laboral (hoja de vida):</td>
                <td style="width: 5%;" class="bold text-right">SI</td>
                <td class="centered-checkbox-cell" style="width: 5%;">
                    <span class="checkbox-symbol"><?php echo (($anexa_hv_nuevo === 'si') ? '&#9745;' : '&#9744;'); ?></span>
                </td>
                <td style="width: 5%;" class="bold text-center">NO</td>
                <td class="centered-checkbox-cell" style="width: 5%;">
                    <span class="checkbox-symbol"><?php echo (($anexa_hv_nuevo === 'no') ? '&#9745;' : '&#9744;'); ?></span>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width: 46%;" class="text-left">Anexa actualización:</td>
                <td style="width: 3%;" class="bold text-right">SI</td>
                <td class="centered-checkbox-cell" style="width: 3%;">
                    <span class="checkbox-symbol"><?php echo (($actualiza_hv_antiguo === 'si') ? '&#9745;' : '&#9744;'); ?></span>
                </td>
                <td style="width: 5%;" class="bold text-center">NO</td>
                <td class="centered-checkbox-cell" style="width: 3%;">
                    <span class="checkbox-symbol"><?php echo (($actualiza_hv_antiguo === 'no') ? '&#9745;' : '&#9744;'); ?></span>
                </td>
                <td style="width: 40%;" class="text-left">Cuál:</td>
            </tr>
            <tr>
                <td colspan="6" style="height: 50px;" class="bold text-left vertical-align-top">Observaciones:</td>
            </tr>
        </table>

        <div class="footer-content">
            <p class="bold footer-text-line" style="margin-bottom: 2px;">Responsable:</p>
            <p class="footer-text-line">_________________________</p> 
            <p class="bold footer-text-line" style="margin-top: 0px;">Jefe de Departamento</p>
            <img src="img/icontec.png" alt="Icontec Logo" class="footer-img">
        </div>
    </div>

</body>
</html>