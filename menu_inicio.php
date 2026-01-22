<?php
$active_menu_item = 'inicio'; // Define el menú activo para esta página

require('include/headerz.php');
require 'funciones.php';
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
    exit(); // Detener toda la ejecución del script
}

// Conexión a la base de datos (ajusta los parámetros según tu configuración)
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$nombre_sesion = $_SESSION['name'];

// Obtener el año y semestre actual
$currentDate = new DateTime();
$currentYear = $currentDate->format('Y');
$currentMonth = $currentDate->format('m');

// Determinar el período actual
if ($currentMonth >= 7) {
    $periodo_work = $currentYear . '-2';
    $nextPeriod = ($currentYear + 1) . '-1';
    $previousPeriod = $currentYear . '-1';
} else {
    $periodo_work = $currentYear . '-1';
    $nextPeriod = $currentYear . '-2';
    $previousPeriod = ($currentYear - 1) . '-2';
}

// Definir el periodo por defecto si no se pasa como parámetro
$anio_semestre = isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : (isset($_SESSION['anio_semestre']) ? $_SESSION['anio_semestre'] : $nextPeriod);
$_SESSION['anio_semestre'] = $anio_semestre;
$data = [];

$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_fac = $row['email_padre'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user = $row['fk_depto_user'];
    $fac_user = $row['fk_fac_user'];
}

$data = [];
if ($tipo_usuario == 3) {
    $query = "
        SELECT
            depto_periodo.*,
             fac_periodo.fp_obs_acepta,
            SUM(CASE WHEN solicitudes.tipo_docente = 'ocasional' THEN 1 ELSE 0 END) AS num_solicitudes_ocasional,
            SUM(CASE WHEN solicitudes.tipo_docente = 'catedra' THEN 1 ELSE 0 END) AS num_solicitudes_catedra,
            COUNT(solicitudes.tipo_docente) AS num_solicitudes, fac_periodo.fp_acepta_vra
        FROM
            depto_periodo
        LEFT JOIN
            solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo
                        AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
        LEFT JOIN
         fac_periodo ON (fac_periodo.fp_fk_fac = solicitudes.facultad_id AND fac_periodo.fp_periodo = depto_periodo.periodo)
        WHERE
            depto_periodo.fk_depto_dp = '$depto_user'
            AND depto_periodo.periodo = '$anio_semestre'
            AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
        GROUP BY
            depto_periodo.id_depto_periodo
    ";

    $result = $conn->query($query);
$data = [
    'envio' => '<span class="no-enviado"><i class="fas fa-times-circle"></i> No</span>',
    'estado_aceptacion' => '<span class="pendiente-aprobacion"><i class="fas fa-hourglass-half"></i> Pendiente de Aprobación</span>',
];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $num_solicitudes_ocasional = $row['num_solicitudes_ocasional'];
            $num_solicitudes_catedra = $row['num_solicitudes_catedra'];
            $dp_estado_ocasional = $row['dp_estado_ocasional'];
            $dp_estado_catedra = $row['dp_estado_catedra'];
            $dp_estado_total = $row['dp_estado_total'];
            $dp_acepta_fac = $row['dp_acepta_fac'];
            $dp_observacion = $row['dp_observacion'];
            $fp_acepta_vra = $row['fp_acepta_vra'];
            $fp_obs_acepta_vra = $row['fp_obs_acepta'];
          

           $data[] = [
                'Tipo de Docente' => 'Ocasional',
                '#Profesores' => $num_solicitudes_ocasional,
                'Estado de confirmación' => $dp_estado_ocasional == 'ce' ? '<span class="confirmado"><i class="fas fa-check-circle"></i> Cerrado</span>' : '<span class="pendiente"><i class="fas fa-exclamation-circle"></i> Pendiente</span>',
            ]; 
          $data[] = [
                'Tipo de Docente' => 'Cátedra',
                '#Profesores' => $num_solicitudes_catedra,
                'Estado de confirmación' => $dp_estado_catedra == 'ce' ? '<span class="confirmado"><i class="fas fa-check-circle"></i> Cerrado</span>' : '<span class="pendiente"><i class="fas fa-exclamation-circle"></i> Pendiente</span>',
            ];
           $data['estado_aceptacion'] = '
<div class="status-flow">
    <div class="status-line">
        <div class="status-item">
            <span class="status-label">Envío:</span>
            '.($dp_estado_total == 1 
                ? '<span class="status-badge sent"><i class="fas fa-paper-plane"></i> Enviado</span>'
                : '<span class="status-badge not-sent"><i class="fas fa-times-circle"></i> No enviado</span>').'
        </div>
        
        <div class="status-item">
            <span class="status-label">Facultad:</span>
            '.($dp_acepta_fac === 'aceptar'
                ? '<span class="status-badge approved"><i class="fas fa-check-circle"></i> Aceptada</span>'
                : ($dp_acepta_fac === 'rechazar'
                    ? '<span class="status-badge rejected"><i class="fas fa-times-circle"></i> Rechazada</span>'
                    : '<span class="status-badge pending"><i class="fas fa-hourglass-half"></i> Pendiente</span>')).'
        </div>
        
        <div class="status-item">
            <span class="status-label">VRA:</span>
            '.($fp_acepta_vra == 2
                ? '<span class="status-badge approved"><i class="fas fa-check-circle"></i> Aceptada</span>'
                : ($fp_acepta_vra == 1
                    ? '<span class="status-badge rejected"><i class="fas fa-times-circle"></i> Rechazada</span>'
                    : '<span class="status-badge pending"><i class="fas fa-hourglass-half"></i> Pendiente</span>')).'
        </div>
    </div>
    
    '.(!empty($dp_observacion)
        ? '<div class="observation-box">
            <div class="observation-header">
                <i class="fas fa-comment-alt"></i> Observación de la Facultad
            </div>
            <div class="observation-content">
                '.nl2br(htmlspecialchars($dp_observacion)).'
            </div>
           </div>'
        : '').'
        
'.(!empty($fp_obs_acepta_vra)
    ? '<div class="observation-box">
            <div class="observation-header">
                <i class="fas fa-user-tie"></i> Observación de la Vicerrectoría
            </div>
            <div class="observation-content">
                ' . nl2br(htmlspecialchars($fp_obs_acepta_vra)) . '
            </div>
        </div>'
    : '').'
';


            
        }
    } else {
        echo "No se encontraron resultados.";
    }

    $conn->close();
}

if ($tipo_usuario == 2) {
    
    //barra
    $queryb = "
        SELECT 
            COUNT(*) AS total_departments,
            SUM(CASE WHEN depto_periodo.dp_estado_total = 1 THEN 1 ELSE 0 END) AS completed_departments
        FROM 
            depto_periodo 
        JOIN 
            deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp 
        WHERE 
            deparmanentos.FK_FAC = '$fac_user' 
            AND depto_periodo.periodo = '$anio_semestre'
    ";
    
    $resultb = $conn->query($queryb);
    $progress = 0;
    
    if ($resultb->num_rows > 0) {
        $rowb = $resultb->fetch_assoc();
        $total_departments = $rowb['total_departments'];
        $completed_departments = $rowb['completed_departments'];
        
        // Calcular el progreso
        if ($total_departments > 0) {
            $progress = ($completed_departments / $total_departments) * 100;
        }
    }

    
    
    
    $queryn = "
        SELECT 
            deparmanentos.FK_FAC,           
            deparmanentos.depto_nom_propio, 
            depto_periodo.fk_depto_dp, 
            depto_periodo.dp_estado_catedra, 
            depto_periodo.dp_estado_ocasional, 
            depto_periodo.dp_estado_total, 
            depto_periodo.dp_fecha_envio
        FROM 
            depto_periodo 
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
        WHERE 
            depto_periodo.periodo = '$anio_semestre' 
            AND deparmanentos.FK_FAC = '$fac_user'
            AND depto_periodo.dp_estado_total IS NULL
        ORDER BY 
            depto_periodo.dp_estado_total DESC
    ";

    $resultn = $conn->query($queryn);
    $data_no_subidos = [];

    if ($resultn->num_rows > 0) {
        while ($rown = $resultn->fetch_assoc()) {
            $data_no_subidos[] = [
                'Departamento' => $rown['depto_nom_propio'],
                'Estado Ocasional' => $rown['dp_estado_ocasional'] == 'ce' ? '<span class="confirmado"><i class="fas fa-check-circle"></i> Confirmado</span>' : '<span class="pendiente"><i class="fas fa-exclamation-circle"></i> Pendiente</span>',
                'Estado Cátedra' => $rown['dp_estado_catedra'] == 'ce' ? '<span class="confirmado"><i class="fas fa-check-circle"></i> Confirmado</span>' : '<span class="pendiente"><i class="fas fa-exclamation-circle"></i> Pendiente</span>',
               
                   'Fecha Enviox' => $rown['dp_fecha_envio'] == '0000-00-00 00:00:00' ? 'retirada por el depto para modificación' :$rown['dp_fecha_envio'],

            ];
        }
    }

    $query = "
        SELECT 
            deparmanentos.FK_FAC, 
            deparmanentos.depto_nom_propio, 
            depto_periodo.fk_depto_dp, 
            depto_periodo.dp_estado_catedra, 
            depto_periodo.dp_estado_ocasional, 
            depto_periodo.dp_estado_total, 
            depto_periodo.num_oficio_depto, 
            depto_periodo.dp_folios, 
            depto_periodo.dp_fecha_envio, 
            depto_periodo.proyecta 
        FROM 
            depto_periodo 
        JOIN 
            deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp 
        WHERE 
            deparmanentos.FK_FAC = '$fac_user' 
            AND depto_periodo.periodo = '$anio_semestre'
            AND depto_periodo.dp_fecha_envio IS NOT NULL
            AND depto_periodo.dp_fecha_envio != '0000-00-00'
        ORDER BY 
            depto_periodo.dp_fecha_envio DESC
    ";

    $result = $conn->query($query);
    
    $current_date = new DateTime();
    $two_days_ago = $current_date->modify('-2 days')->format('Y-m-d');
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $depto_nom_propio = $row['depto_nom_propio'];
        $dp_estado_total = $row['dp_estado_total'];
        $num_oficio_depto = $row['num_oficio_depto'];
        $dp_folios = $row['dp_folios'];
        $dp_fecha_envio = $row['dp_fecha_envio'];
        $proyecta = $row['proyecta'];

        $is_new = (new DateTime($dp_fecha_envio)) >= (new DateTime($two_days_ago));

        $data[] = [
            'Departamento' => $depto_nom_propio,
            // CAMBIO AQUÍ: Ahora solo guarda el texto "Ok" o "Pendiente", sin el <span>
            'Estado Total' => $dp_estado_total == 1 ? 'Ok' : 'Pendiente',
            'Número de Oficio' => $num_oficio_depto,
            'Folios' => $dp_folios,
            'Fecha de Envío' => $dp_fecha_envio == '0000-00-00 00:00:00' ? 'En modificación' : $dp_fecha_envio,
            'Proyecta' => $proyecta,
            'Nuevo' => $is_new ? '<img src="img/new.png" alt="New" />' : '',
        ];
    }
} else {
          //  echo "<div style='max-width: 1800px; width: 100%; margin: auto;'>";
//echo "No se encontraron resultados."; echo "</div>"
    }

    $conn->close();
} 
if ($tipo_usuario == 1 || $tipo_usuario == 4) {
 
    
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
    //facultades
 $query_facultades = "
    SELECT 
        facultad.nombre_fac_minb, 
        fac_periodo.fp_estado, fecha_accion
    FROM 
        facultad 
    LEFT JOIN 
        fac_periodo ON facultad.PK_FAC = fac_periodo.fp_fk_fac 
        AND fac_periodo.fp_periodo = '$anio_semestre' order by fecha_accion desc
";
$result_facultades = $conn->query($query_facultades);
$facultades_recibidas = [];
$facultades_no_recibidas = [];


if ($result_facultades->num_rows > 0) {
    while ($row_facultades = $result_facultades->fetch_assoc()) {
        if ($row_facultades['fp_estado'] == 1) {
            $facultades_recibidas[] = [
                'nombre' => $row_facultades['nombre_fac_minb'],
                'fecha_accion' => $row_facultades['fecha_accion']
            ];
        } else {
            $facultades_no_recibidas[] = $row_facultades['nombre_fac_minb'];
        }
    }
}
    //deptos que no entregaron o a aestiempo
$query_destiempo = "
SELECT 
  facultad.nombre_fac_min,
  deparmanentos.depto_nom_propio,  depto_periodo.dp_estado_total, 
  depto_periodo.dp_fecha_envio, 
  periodo.plazo_jefe,
  depto_periodo.dp_acepta_fac,
  depto_periodo.dp_observacion
FROM depto_periodo
JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
JOIN periodo ON periodo.nombre_periodo = depto_periodo.periodo
WHERE (
    DATE(depto_periodo.dp_fecha_envio) > DATE(periodo.plazo_jefe)
    OR depto_periodo.dp_fecha_envio IS NULL
    OR depto_periodo.dp_fecha_envio = '0000-00-00 00:00:00'
       OR LOWER(TRIM(depto_periodo.dp_acepta_fac)) = 'rechazar'
) AND depto_periodo.periodo = '$anio_semestre'
ORDER BY facultad.nombre_fac_min, deparmanentos.depto_nom_propio
";

$result_destiempo = $conn->query($query_destiempo);
$departamentos_destiempo = [];

if ($result_destiempo->num_rows > 0) {
    while ($row = $result_destiempo->fetch_assoc()) {
        $departamentos_destiempo[] = [
            'facultad' => $row['nombre_fac_min'],
            'departamento' => $row['depto_nom_propio'],
            'estado_total' => $row['dp_estado_total'],
            'fecha_envio' => $row['dp_fecha_envio'],
            'plazo' => $row['plazo_jefe'],
            'acepta_fac' => $row['dp_acepta_fac'],
            'observacion' => $row['dp_observacion']
        ];
    }
}
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reporte de Solicitudes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
       
    /* --- */
:root {
    /* Azules */
    --unicauca-blue-primary: #002A9E; /* Azul principal */
    --unicauca-blue-secondary: #0051C6; /* Azul secundario */
    --unicauca-blue-dark: #001A6B; /* Azul más oscuro para encabezados */
    --unicauca-blue-subtle: #2A499A; /* Azul ligeramente menos intenso para textos */
    --unicauca-purple: #4C19AF; /* Púrpura */
    --unicauca-cyan: #16A8E1; /* Cian/Azul claro */
    
    /* Verdes */
    --unicauca-green-primary: #8CBD22; /* Verde principal */
    --unicauca-green-secondary: #249337; /* Verde más oscuro */
    --unicauca-green-dark: #1A6C2B; /* Verde más oscuro para encabezados */
    
    /* Rojos/Naranjas */
    --unicauca-red: #E52724; /* Rojo */
   /* --unicauca-orange: #F8AE15; /* Naranja para advertencias */
    --unicauca-dark-orange: #EC6C1F; /* Naranja oscuro/ladrillo para más detalles */
    
    /* Grises */
    --unicauca-gray: #f8f9fa; /* Gris claro para fondos/bordes */
    --unicauca-light-gray-subtle: #F0F2F5; /* Gris muy sutil para fondos */
    --unicauca-gray-medium: #E9ECEF; /* Gris para bordes o separadores */
    --unicauca-gray-dark: #6C757D; /* Gris oscuro para texto secundario */
    
    /* Tonos claros derivados */
    --unicauca-light-blue: #D6E0F4; 
    --unicauca-light-green: #e6f7ee; 
    --unicauca-light-red: #FCE7E7; 
    --unicauca-light-orange: #FFF8E1;
    --unicauca-light-purple: #EFE6FA; 
}
    
  /* Apply Open Sans to all text elements */
        body, h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th {
            font-family: 'Open Sans', sans-serif !important;
        }
    /* Estilos generales de tarjeta */
    .card {
        border-radius: 0.75rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        margin-bottom: 20px; /* Consolidado de las propiedades duplicadas */
    }
    .card-header {
        border-bottom: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white; /* Asegura el color del texto del header */
    }
    .card-header h5, .card-header h6 {
        color: white; /* Asegura el color del texto del header */
        margin-bottom: 0; /* Bootstrap suele tener margin-bottom en h tags */
    }

    /* Estilo para el botón de "Mostrar/Ocultar" en los headers de las tarjetas */
    .btn-header-outline {
        background-color: transparent;
        border: 1px solid rgba(255, 255, 255, 0.5); 
        color: white; 
        padding: 0.3rem 0.6rem;
        font-size: 0.85rem;
        transition: all 0.2s ease-in-out;
    }
    .btn-header-outline:hover {
        background-color: rgba(255, 255, 255, 0.2); 
        border-color: white;
    }
    .btn-header-outline:focus {
        box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.3); 
    }
    .btn-header-outline i {
        margin-left: 5px;
    }

    /* Estilos para la tarjeta "Plazos Clave del Proceso" (primer menú) */
    .card-plazo .card-body {
        padding: 1rem; /* Compacto */
    }
    .plazo-item {
        background-color: var(--unicauca-gray);
        border-left: 5px solid var(--unicauca-blue-primary);
        padding: 0.75rem; /* Compacto */
        border-radius: 0.5rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .plazo-item strong {
        color: var(--unicauca-blue-secondary);
        margin-bottom: 0.15rem; /* Compacto */
        font-size: 0.9rem; /* Compacto */
        display: block;
    }
    .plazo-fecha {
        font-size: 1rem; /* Compacto */
        font-weight: bold;
        color: #333;
    }
    .plazo-countdown {
        margin-top: 0.25rem; /* Compacto */
        font-size: 0.8rem; /* Compacto */
        color: #666;
        display: block;
    }

    /* Estilos para la tarjeta "Plazo remisión a Vicerrectoría Académica" (segundo menú) */
    .card-plazo-fac .card-header {
        background-color: var(--unicauca-blue-primary) !important;
        color: white;
    }
    .card-plazo-fac .card-body {
        padding: 1rem 1.5rem; 
    }
    .plazo-countdown-fac { 
        font-size: 0.85rem;
        color: var(--unicauca-blue-secondary); 
        margin-top: 0.5rem;
        display: block;
        font-weight: 500;
    }
/* Estilos para Departamentos Fuera de Plazo (Opción 1: Azul Suave y Clásico) */
.card-destiempo .card-header {
    background-color: #e0f2f7 !important; /* Azul cielo muy pálido, casi blanco azulado */
    color: #004d60; /* Azul oscuro suave, profesional */
    border-bottom: 1px solid #b3e0ed; /* Borde azul un poco más oscuro */
    font-weight: 600;
    padding: 0.75rem 1.25rem;
}

.card-destiempo .btn-warning-outline {
    background-color: transparent;
    border: 1px solid #0047AB; /* El azul principal de Unicauca para el borde */
    color: #0047AB; /* El azul principal de Unicauca para el texto */
    transition: all 0.2s ease-in-out;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.card-destiempo .btn-warning-outline:hover {
    background-color: #0047AB; /* Rellena con el azul principal al pasar el ratón */
    color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Estilos para la tarjeta contenedora */
.card-destiempo {
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
    overflow: hidden;
}
    /* Mejora de colores en el listado de departamentos fuera de plazo */
    .list-group-item.d-flex.flex-column.flex-md-row {
        border-left: 5px solid transparent; 
        transition: background-color 0.2s ease;
    }
    .list-group-item.d-flex.flex-column.flex-md-row:hover {
        background-color: var(--unicauca-gray);
    }
    .list-group-item .text-success { border-left-color: var(--unicauca-green-primary); }
    .list-group-item .text-danger { border-left-color: var(--unicauca-red); }
    .list-group-item .text-warning { border-left-color: var(--unicauca-orange); }
    .list-group-item .text-info { border-left-color: var(--unicauca-cyan); } 
    .list-group-item .text-unicauca-green { border-left-color: var(--unicauca-green-primary); }

    /* Barra de Progreso Personalizada (unificada para ambos progresos) */
    .progress-container-custom { 
        width: 100%;
        background-color: #e9ecef;
        border-radius: 1.5rem;
        height: 2.5rem;
        overflow: hidden;
        /* Consolidado: padding ahora solo en la barra de progreso */
        margin: 20px 0; /* Consolidado de .progress-containerc */
    }
    .progress-bar-custom {
        height: 100%;
        background-color: var(--unicauca-green-primary); 
        border-radius: 1.5rem;
        text-align: center;
        line-height: 2.5rem;
        color: white;
        font-weight: 700;
        transition: width 0.6s ease-in-out;
        white-space: nowrap;
        padding: 0 1rem;
    }

    /* Listas de Facultades Recibidas/Pendientes */
    .list-group-item-success-custom {
        background-color: var(--unicauca-light-green); 
        color: var(--unicauca-green-secondary); 
        border-left: 5px solid var(--unicauca-green-primary); 
        font-weight: 500;
        transition: background-color 0.2s ease;
    }
    .list-group-item-success-custom:hover {
        background-color: #d4edda; 
    }

    .list-group-item-danger-custom {
        background-color: var(--unicauca-light-red); 
        color: var(--unicauca-red); 
        border-left: 5px solid var(--unicauca-red); 
        font-weight: 500;
        transition: background-color 0.2s ease;
    }
    .list-group-item-danger-custom:hover {
        background-color: #F8B3B3; 
    }

    /* Estilos para los títulos (h2) */
    h2 {
        font-size: 1.75rem;
        color: var(--unicauca-blue-primary);
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--unicauca-blue-primary);
        padding-bottom: 0.5rem;
    }
    .row h2:last-child { /* Solo para el H2 "Facultades Pendientes" en la sección de cards */
        border-bottom-color: var(--unicauca-red); 
    }

    /* Estilo para encabezados de tabla unificados */
    .table-unicauca-header {
        background-color: var(--unicauca-blue-primary); /* Fondo azul principal */
        color: white; /* Texto blanco */
    }
    .table-unicauca-header th {
        border-color: rgba(255, 255, 255, 0.3) !important; /* Borde más claro entre columnas */
        font-weight: 600;
    }

    /* Estilo para la tabla de "Estado de Envío a VRA" */
    .table-status-vra thead th {
        text-align: center; /* Centrar el título de la tabla */
    }
    .table-status-vra td {
        vertical-align: middle;
    }
    .table-status-vra .badge {
        font-size: 0.9em; 
        padding: 0.5em 0.75em;
        border-radius: 0.3rem;
    }
    /* Definición de colores de badge basados en variables Unicauca */
    .badge.bg-unicauca-green-primary { background-color: var(--unicauca-green-primary) !important; }
    .badge.bg-unicauca-red { background-color: var(--unicauca-red) !important; }
    .badge.bg-unicauca-orange { background-color: var(--unicauca-orange) !important; }

    /* Fondo sutil para la observación VRA */
    .bg-unicauca-light-gray-subtle {
        background-color: var(--unicauca-light-gray-subtle) !important;
    }

    /* Estilo para los botones de filtro */
    .filter-buttons-container {
        margin-bottom: 2rem;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    .filter-button {
        background-color: var(--unicauca-blue-primary);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .filter-button:hover {
        background-color: var(--unicauca-blue-secondary);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }
    .filter-button:active {
        background-color: var(--unicauca-purple);
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .filter-button.active {
        background-color: var(--unicauca-green-primary);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .plazo-item {
            margin-bottom: 1rem;
        }
    }
.bg-custom-teal {
    background-color: #04B2B5; /* Este es el R:4, G:178, B:181 */
}
/* Sobreescritura de clases de Bootstrap para usar la paleta Unicauca */
/* Mantendremos las que ya tenías y ajustaremos sus colores a las nuevas variables */
.text-unicauca-blue { color: var(--unicauca-blue-primary) !important; }
.bg-unicauca-blue { background-color: var(--unicauca-blue-primary) !important; color: white; }
.bg-unicauca-blue-dark { background-color: var(--unicauca-blue-dark) !important; color: white; } /* Nuevo */
.text-unicauca-blue-subtle { color: var(--unicauca-blue-subtle) !important; } /* Nuevo */

.text-unicauca-green { color: var(--unicauca-green-primary) !important; }
.bg-unicauca-green { background-color: var(--unicauca-green-primary) !important; color: white; }
.bg-unicauca-green-dark { background-color: var(--unicauca-green-dark) !important; color: white; } /* Nuevo */

.bg-danger { background-color: var(--unicauca-red) !important; color: white !important; }
.text-danger { color: var(--unicauca-red) !important; }
.text-success { color: var(--unicauca-green-primary) !important; }
.text-warning { color: var(--unicauca-orange) !important; }
.text-info { color: var(--unicauca-cyan) !important; }

/* Ajustes a las alertas y otros elementos de Bootstrap */
.alert.alert-warning {
    background-color: var(--unicauca-light-orange) !important;
    color: #8D6B00 !important; /* Texto más oscuro para legibilidad */
    border-color: var(--unicauca-orange) !important;
}

/* --- */
/* Estilos Globales Revisados */
/* --- */
h2.section-title {
    font-size: 2.2rem;
    color: var(--unicauca-blue-dark); /* Un azul más oscuro para los títulos principales */
    margin-bottom: 0.5rem;
    font-weight: 700;
    text-align: center;
}

.section-divider {
    border: 0;
    height: 2px; /* Más delgado */
    background-image: linear-gradient(to right, rgba(0, 0, 0, 0), var(--unicauca-blue-secondary), rgba(0, 0, 0, 0)); /* Un azul ligeramente más suave */
    margin-bottom: 3rem; /* Más espacio para separar secciones */
}

/* Ajustes para la alerta de plazo */
.alert-custom-info {
    background-color: var(--unicauca-light-blue) !important;
    color: var(--unicauca-blue-dark) !important; /* Texto más oscuro para mayor profesionalismo */
    border: 0px solid var(--unicauca-blue-secondary) !important; /* Borde más definido */
    font-weight: 500;
    padding: 1.5rem 2rem; /* Más padding para airear */
    border-radius: 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 2.5rem; /* Más espacio antes de la tabla */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05); /* Sombra sutil */
}

.alert-custom-info strong {
    font-size: 1.2em; /* Un poco más grande */
    margin-bottom: 0.75rem;
    display: block;
}

.alert-custom-info p.lead { /* Estilo específico para el texto de la fecha */
    font-size: 1.5rem; /* Más grande para la fecha */
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.countdown-text {
    font-size: 1.2em;
    font-weight: bold;
    color: var(--unicauca-dark-orange); /* Naranja más oscuro para el countdown */
}

/* Estilos de las tarjetas (card) */
.card-report-subtle {
    border: 1px solid var(--unicauca-gray-medium); /* Borde sutil */
    border-radius: 0.75rem; /* Bordes ligeramente menos pronunciados */
    overflow: hidden;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); /* Sombra más suave */
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-report-subtle:hover {
    transform: translateY(-3px); /* Menos elevación */
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.1);
}

.header-report-subtle {
    padding: 1rem 1.5rem; /* Menos padding en el header */
    font-size: 1.15rem; /* Tamaño de fuente ligeramente más pequeño */
    font-weight: 600;
    display: flex;
    align-items: center;
    color: white;
    /* Un pequeño gradiente sutil para darle profundidad */
    background: linear-gradient(to right, var(--unicauca-blue-dark), var(--unicauca-blue-primary));
}

/* El segundo header para el flujo */
.card-report-subtle .bg-unicauca-green-dark {
    background: linear-gradient(to right, var(--unicauca-green-dark), var(--unicauca-green-secondary));
}

.header-report-subtle i {
    margin-right: 0.6rem; /* Menos espacio */
    font-size: 1.4rem; /* Ícono ligeramente más pequeño */
}

.card-body {
    padding: 1.5rem; /* Padding general para el body de las tarjetas */
}

/* Tabla principal de resumen */
.table-report-main th,
.table-report-main td {
    padding: 1rem 1.25rem; /* Ajuste de padding */
    vertical-align: middle;
    border-color: var(--unicauca-gray-medium); /* Borde de tabla más sutil */
}

.table-header-subtle {
    background-color: var(--unicauca-blue-primary); /* Se mantiene el azul principal */
    color: white;
}

.table-report-main thead th {
    font-weight: 600; /* Menos negrita */
    font-size: 1rem;
    border-bottom: 2px solid var(--unicauca-blue-dark); /* Borde inferior más pronunciado */
}

/* Estilos para badges de estado de confirmación en la tabla (más sutiles) */
.confirmado {
    color: var(--unicauca-green-secondary); /* Un verde más oscuro para el texto */
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    padding: 0.35em 0.7em; /* Padding más compacto */
    background-color: var(--unicauca-light-green);
    border-radius: 0.3rem; /* Menos redondeado */
    border: 1px solid var(--unicauca-green-primary); /* Borde sutil */
}

.confirmado i {
    margin-right: 0.4rem;
    font-size: 1em;
}

.pendiente {
    color: var(--unicauca-dark-orange); /* Naranja más oscuro para el texto */
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    padding: 0.35em 0.7em;
    background-color: var(--unicauca-light-orange);
    border-radius: 0.3rem;
    border: 1px solid var(--unicauca-orange); /* Borde sutil */
}

.pendiente i {
    margin-right: 0.4rem;
    font-size: 1em;
}

/* Flujo de estado (Más limpio y con bordes) */
.status-flow {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    background-color: var(--unicauca-gray-light); /* Fondo más claro */
    border-radius: 0.75rem;
    border: 1px solid var(--unicauca-gray-medium); /* Borde sutil */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.03); /* Sombra muy tenue */
}

.status-line {
    display: flex;
    justify-content: space-around;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 1.5rem; /* Más espacio antes de las observaciones */
}

.status-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 1;
    min-width: 130px; /* Ancho mínimo ligeramente mayor */
}

.status-label {
    font-weight: 500; /* Menos negrita */
    color: var(--unicauca-gray-dark); /* Un gris oscuro para las etiquetas */
    margin-bottom: 0.6rem;
    font-size: 0.9rem; /* Ligeramente más pequeño */
}

.status-badge {
    padding: 0.5em 1em; /* Menos padding */
    border-radius: 0.5rem; /* Menos redondeado */
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    font-size: 0.85em; /* Ligeramente más pequeño */
    box-shadow: none; /* Quitamos la sombra de los badges individuales */
    border: 1px solid transparent; /* Añadimos borde transparente para consistencia */
    white-space: nowrap;
}

.status-badge i {
    margin-right: 0.5em;
    font-size: 1.1em;
}

/* Colores de badges de estado (más sutiles) */
.status-badge.sent {
    background-color: var(--unicauca-light-blue);
    color: var(--unicauca-blue-secondary);
    border-color: var(--unicauca-blue-secondary);
}
.status-badge.not-sent {
    background-color: var(--unicauca-light-red);
    color: var(--unicauca-red);
    border-color: var(--unicauca-red);
}
.status-badge.approved {
    background-color: var(--unicauca-light-green);
    color: var(--unicauca-green-secondary);
    border-color: var(--unicauca-green-secondary);
}
.status-badge.rejected {
    background-color: var(--unicauca-light-red);
    color: var(--unicauca-red);
    border-color: var(--unicauca-red);
}
.status-badge.pending {
    background-color: var(--unicauca-light-orange);
    color: var(--unicauca-orange);
    border-color: var(--unicauca-orange);
}

/* Cajas de Observación (más limpias y profesionales) */
.observation-box {
    background-color: white;
    border: 1px solid var(--unicauca-gray-medium); /* Borde más estándar */
    border-radius: 0.5rem; /* Menos redondeado */
    padding: 1.25rem 1.5rem; /* Más padding */
    margin-top: 1rem; /* Menos espacio entre cajas de observación */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Sombra muy sutil */
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.observation-box:hover {
    border-color: var(--unicauca-blue-primary); /* Borde resaltado al pasar el mouse */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
}

.observation-header {
    font-weight: 600; /* Menos negrita */
    color: var(--unicauca-blue-dark); /* Azul más oscuro */
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    font-size: 1em; /* Más pequeño */
    border-bottom: 1px solid var(--unicauca-gray-medium); /* Línea divisoria más sutil */
    padding-bottom: 0.5rem;
}

.observation-header i {
    margin-right: 0.6rem;
    color: var(--unicauca-cyan); /* Se mantiene el cian para el ícono */
    font-size: 1.2em;
}

.observation-content {
    font-size: 0.9rem; /* Texto más pequeño para el contenido */
    line-height: 1.6;
    color: #495057; /* Gris estándar de Bootstrap para buena legibilidad */
    white-space: pre-wrap;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .section-title { font-size: 1.7rem; }
    .alert-custom-info { padding: 1rem 1.25rem; }
    .alert-custom-info p.lead { font-size: 1.3rem; }
    .status-line {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem; /* Menos espacio apilado */
    }
    .status-item {
        margin-bottom: 0.5rem;
        min-width: unset;
    }
    .status-badge {
        width: 100%;
        justify-content: center;
        padding: 0.6em 0.8em;
    }
    .card-body {
        padding: 1rem; /* Menos padding en móviles */
    }
    .table-report-main th,
    .table-report-main td {
        padding: 0.75rem;
    }
}
    .row {
  margin: 0 20px; /* 0 para el margen superior/inferior, 20px para el izquierdo/derecho */
}
</style>


</head>
<body>
    
    <div style="max-width: 1200px; width: 100%; margin: auto;">

<!-- Mostrar los botones de filtro si no se pasa el parámetro 'anio_semestre' -->
<div>
    <button class="filter-button <?php echo ($anio_semestre == '2024-2') ? 'active' : ''; ?>" data-period="2024-2">2024-2</button>
    <button class="filter-button <?php echo ($anio_semestre == '2025-1') ? 'active' : ''; ?>" data-period="2025-1">2025-1</button>
    <button class="filter-button <?php echo ($anio_semestre == '2025-2') ? 'active' : ''; ?>" data-period="2025-2">2025-2</button>
    <button class="filter-button <?php echo ($anio_semestre == '2026-1') ? 'active' : ''; ?>" data-period="2026-1">2026-1</button>
</div>
 <?php 
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain', 'es_ES', 'es'); // Configurar idioma
    
    $plazo_fecha = plazo_jefe($anio_semestre);
        $plazo_fac = plazo_fac($anio_semestre);
        $plazo_vra = plazo_vra($anio_semestre);

?>
    <?php if ($tipo_usuario == 3): ?>
        <div class="container mt-5">
    <h2 class="section-title">Reporte de Solicitudes Enviadas <?php echo $anio_semestre; ?></h2>
    <hr class="section-divider">

    <div class="alert alert-info alert-custom-info">
        <p class="mb-2"><strong>Plazo solicitud al Consejo de Facultad aval temporales:</strong></p>
        <p class="lead mb-2 text-unicauca-blue-subtle"><?php echo strftime("%d de %B de %Y", strtotime($plazo_fecha)); ?></p>
        <p class="countdown-text" id="countdown"></p>
    </div>

    <div class="card card-report-subtle mb-4">
        <div class="card-header header-report-subtle bg-unicauca-blue-dark">
            <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i> Resumen de Solicitudes</h5>
        </div>
        <div class="card-body p-0"> <div class="table-responsive">
                <table class="table table-striped table-hover table-report-main">
                    <thead class="table-header-subtle">
                        <tr>
                            <th>Tipo de Docente</th>
                            <th class="text-center">#Profesores</th>
                            <th class="text-center">Estado de Cierre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <?php if (is_array($row) && isset($row['Tipo de Docente'])): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['Tipo de Docente']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($row['#Profesores']); ?></td>
                                    <td class="text-center"><?php echo $row['Estado de confirmación']; ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-report-subtle mb-4">
        <div class="card-header header-report-subtle bg-unicauca-green-dark">
            <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i> Flujo de Aceptación y Observaciones</h5>
        </div>
        <div class="card-body">
            <?php
                echo $data['estado_aceptacion'];
            ?>
        </div>
    </div>
</div>
    <?php elseif ($tipo_usuario == 2): ?>
 <div class="container mt-5">
    <div class="card card-plazo-fac mb-4">
        <div class="card-header bg-unicauca-blue-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Plazo remisión a Vicerrectoría Académica</h5>
            <button class="btn btn-sm btn-header-outline text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlazoFac" aria-expanded="false" aria-controls="collapsePlazoFac">
                Mostrar/Ocultar <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="collapsePlazoFac">
            <div class="card-body">
                <strong>Fecha límite:</strong>
                <?php
                // Esta línea de PHP permanece intacta, solo se ajustó la etiqueta <strong> para estilo.
                echo strftime("%d de %B de %Y", strtotime($plazo_fac));
                ?>
                <br>
                <span id="countdownfac" class="plazo-countdown-fac"></span>
            </div>
        </div>
    </div>

    <h2>Progreso de solicitudes recibidas <?php echo $anio_semestre; ?></h2>
    <div class="progress-container-custom mb-4">
        <div class="progress-bar-custom" style="width: <?php echo number_format($progress, 2); ?>%;"><?php echo number_format($progress, 2); ?>%</div>
    </div>

    <h2 class="mb-3">Solicitudes recibidas de los departamentos</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover table-sm">
            <thead class="table-unicauca-header">
                <tr>
                    <th class="py-2">Departamento</th>
                    <th class="py-2">Estado Total</th>
                    <th class="py-2">Fecha de Envío</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): // El bucle foreach y la variable $data no se tocan. ?>
                    <tr class="align-middle">
                        <td class="py-2"><?php echo htmlspecialchars($row['Departamento']); ?></td>
                        <td class="py-2">
                            <?php
                                // La lógica PHP para determinar el estado permanece intacta.
                                // Solo se añaden clases CSS para el estilo de los colores de la paleta Unicauca.
                                $estado_clase = '';
                                if (isset($row['Estado Total'])) {
                                    $estado_total_lower = strtolower(trim($row['Estado Total']));
                                    if ($estado_total_lower === 'subsanado' || $estado_total_lower === 'aprobado' || $estado_total_lower === 'enviado') {
                                        $estado_clase = 'text-success'; // Se mapea a var(--unicauca-green-primary) en CSS
                                    } elseif ($estado_total_lower === 'pendiente' || $estado_total_lower === 'rechazado') {
                                        $estado_clase = 'text-danger'; // Se mapea a var(--unicauca-red) en CSS
                                    } else {
                                        $estado_clase = 'text-muted'; // Color por defecto si no coincide
                                    }
                                }
                            ?>
                            <span class="<?php echo $estado_clase; ?> fw-bold">
                                <?php echo htmlspecialchars($row['Estado Total']); ?>
                            </span>
                        </td>
                        <td class="py-2 small text-muted"><?php echo htmlspecialchars($row['Fecha de Envío']). (isset($row['Nuevo']) ? $row['Nuevo'] : ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="mt-4 mb-3">Solicitudes pendientes de envío</h2>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover table-sm">
            <thead class="table-unicauca-header">
                <tr>
                    <th class="py-2">Departamento</th>
                    <th class="py-2">Estado Ocasional</th>
                    <th class="py-2">Estado Cátedra</th>
                    <th class="py-2">Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_no_subidos as $row): // El bucle foreach y la variable $data_no_subidos no se tocan. ?>
                    <tr class="align-middle">
                        <td class="py-2"><?php echo htmlspecialchars($row['Departamento']); ?></td>
                        <td class="py-2"><?php echo $row['Estado Ocasional']; ?></td>
                        <td class="py-2"><?php echo $row['Estado Cátedra']; ?></td>
                        <td class="py-2 small text-muted"><?php echo $row['Fecha Enviox']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
            
    <h2>Estado de Envío a VRA</h2>
    <table class="table table-bordered table-status-vra mb-4">
        <thead class="table-unicauca-header">
            <tr>
                <th colspan="2" class="text-center">Estado del Proceso</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="30%"><strong>Envío a VRA:</strong></td>
                <td>
                    <?php
                    // Las llamadas a funciones PHP y las variables se mantienen intactas.
                    $enviof = obtenerenviof($fac_user, $anio_semestre);
                    $aceptacionvra = obteneraceptacionvra($fac_user, $anio_semestre);
                    $obtenerobsaceptacionvra = obtenerobsaceptacionvra($fac_user, $anio_semestre);
                    // Los 'badge' ahora usan las variables de color de Unicauca definidas en CSS.
                    echo ($enviof == 1)
                        ? '<span class="badge bg-unicauca-green-primary text-white"><i class="fas fa-check-circle me-1"></i> Enviado</span>'
                        : '<span class="badge bg-unicauca-red text-white"><i class="fas fa-times-circle me-1"></i> Pendiente</span>';
                    ?>
                </td>
            </tr>
            
            <tr>
                <td><strong>Respuesta VRA:</strong></td>
                <td>
                    <?php
                    // La lógica del switch y las llamadas a funciones PHP no se modifican.
                    switch($aceptacionvra) {
                        case 2:
                            echo '<span class="badge bg-unicauca-green-primary text-white"><i class="fas fa-check-circle me-1"></i> Aprobada</span>';
                            break;
                        case 1:
                            echo '<span class="badge bg-unicauca-red text-white"><i class="fas fa-times-circle me-1"></i> Rechazada</span>';
                            break;
                        default:
                            echo '<span class="badge bg-unicauca-orange text-dark"><i class="fas fa-hourglass-half me-1"></i> Pendiente</span>';
                    }
                    ?>
                </td>
            </tr>
            
            <?php if(!empty($obtenerobsaceptacionvra)): // La condición PHP se mantiene intacta. ?>
            <tr>
                <td><strong>Observación VRA:</strong></td>
                <td class="bg-unicauca-light-gray-subtle">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-comment-alt mt-1 me-2 text-unicauca-blue-secondary"></i>
                        <div><?= nl2br(htmlspecialchars($obtenerobsaceptacionvra)) ?></div>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php elseif ($tipo_usuario == 1 || $tipo_usuario == 4): ?>
<div class="container mt-5">
    <h2 class="text-center mb-4 text-unicauca-blue">Progreso de Facultades <?php echo $anio_semestre; ?></h2>

<div class="card card-plazo mb-4">
<div class="card-header bg-custom-teal text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Plazos Clave del Proceso</h5>
        <button class="btn btn-sm btn-header-outline text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlazos" aria-expanded="false" aria-controls="collapsePlazos">
            Mostrar/Ocultar <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse show" id="collapsePlazos"> <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="plazo-item">
                        <strong>Solicitud Aval Temporales (Jefes):</strong><br>
                        <span class="plazo-fecha"><?php echo strftime("%d de %B de %Y", strtotime($plazo_fecha)); ?></span><br>
                        <span id="countdown" class="plazo-countdown"></span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="plazo-item">
                        <strong>Remisión a Vicerrectoría Académica (Facultad):</strong><br>
                        <span class="plazo-fecha"><?php echo strftime("%d de %B de %Y", strtotime($plazo_fac)); ?></span><br>
                        <span id="countdownfac" class="plazo-countdown"></span>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="plazo-item">
                        <strong>Estudio y Aval Puntaje CIARP (VRA):</strong><br>
                        <span class="plazo-fecha"><?php echo strftime("%d de %B de %Y", strtotime($plazo_vra)); ?></span><br>
                        <span id="countdownvra" class="plazo-countdown"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <?php if (!empty($departamentos_destiempo)): ?>
    <div class="card card-destiempo mb-4">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0" style="color: #4A4A4A;">Departamentos Fuera de Plazo</h5>

            <button class="btn btn-sm btn-warning-outline" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDestiempo" aria-expanded="false" aria-controls="collapseDestiempo">
                Mostrar/Ocultar <i class="bi bi-chevron-down"></i>
            </button>
        </div>

        <div class="collapse" id="collapseDestiempo">
            <ul class="list-group list-group-flush">
                <?php foreach ($departamentos_destiempo as $dep): ?>
                    <?php
                        $estado_total = strtolower(trim($dep['estado_total']));
                        $estado_facultad = strtolower(trim($dep['acepta_fac']));
                        $no_enviado = ($dep['fecha_envio'] === null || $dep['fecha_envio'] === '0000-00-00 00:00:00');
                        
                        if ($estado_facultad === 'rechazar') $estado_facultad = 'rechazado';
                        if ($estado_facultad === 'aceptar') $estado_facultad = 'aceptado';

                        $clase_color = 'text-muted'; // Default para cualquier caso no contemplado
                        $estado_texto = "Estado no definido";
                        
                        if ($estado_facultad === 'subsanado') {
                            $clase_color = 'text-unicauca-green';
                            $estado_texto = 'Subsanado';
                        } elseif ($estado_facultad === 'aceptado') {
                            $clase_color = 'text-success';
                            $estado_texto = 'Aceptado';
                        } elseif ($estado_facultad === 'rechazado') {
                            $clase_color = 'text-danger';
                            $estado_texto = 'Rechazado';
                        } elseif (($estado_facultad === '' || $estado_facultad === null) && $estado_total == '1') {
                            $clase_color = 'text-info'; // Podría ser un color para "enviado a facultad"
                            $estado_texto = 'Enviado a Facultad';
                        } elseif ($no_enviado) {
                            $clase_color = 'text-warning'; // Fuera de plazo y no enviado
                            $estado_texto = 'Pendiente de Envío';
                        }
                    ?>
                    <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div>
                            <strong><?php echo htmlspecialchars($dep['facultad']); ?></strong> -
                            <span class="<?php echo $clase_color; ?> fw-bold">
                                <?php echo htmlspecialchars($dep['departamento']); ?>
                            </span>
                        </div>
                        <small class="text-muted text-md-end mt-2 mt-md-0">
                            <?php if ($no_enviado): ?>
                                No ha enviado
                            <?php else: ?>
                                Enviado: <?php echo date('d/m/Y', strtotime($dep['fecha_envio'])); ?>
                                (Plazo: <?php echo date('d/m/Y', strtotime($dep['plazo'])); ?>)
                            <?php endif; ?>
                            <?php if (!empty($dep['acepta_fac']) || !empty($dep['observacion'])): ?>
                                <br>
                                <?php if (!empty($dep['acepta_fac'])): ?>
                                    Estado: <span class="<?php echo $clase_color; ?>"><?php echo htmlspecialchars($estado_texto); ?>.</span>
                                <?php endif; ?>
                                <?php if (!empty($dep['observacion'])): ?>
                                    Observación: <?php echo htmlspecialchars($dep['observacion']); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

<div class="card card-progress mb-4">
    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #04B2B5;">
        <h5 class="mb-0">Progreso General de Facultades</h5>
        <button class="btn btn-sm btn-header-outline text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFacultades" aria-expanded="true" aria-controls="collapseFacultades">
            Mostrar/Ocultar <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    
    <div class="collapse" id="collapseFacultades"> <!-- Quitamos la clase "show" -->
        <div class="card-body">
            <div class="progress-container-custom">
                <div class="progress-bar-custom" style="width: <?php echo number_format($progress_facultades, 2); ?>%;">
                    <?php echo number_format($progress_facultades, 2); ?>%
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Facultades Pendientes</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (empty($facultades_no_recibidas)): ?>
                                    <li class="list-group-item text-muted">Todas las facultades han enviado su información.</li>
                                <?php else: ?>
                                    <?php foreach ($facultades_no_recibidas as $facultad): ?>
                                        <li class="list-group-item list-group-item-danger-custom">
                                            <?php echo htmlspecialchars($facultad); ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-unicauca-green text-white">
                            <h5 class="mb-0">Facultades Recibidas</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (empty($facultades_recibidas)): ?>
                                    <li class="list-group-item text-muted">Aún no hay facultades recibidas.</li>
                                <?php else: ?>
                                    <?php foreach ($facultades_recibidas as $facultad): ?>
                                        <li class="list-group-item list-group-item-success-custom d-flex justify-content-between align-items-center">
                                            <span><?php echo htmlspecialchars($facultad['nombre']); ?></span>
                                            <small class="text-muted"><?php echo htmlspecialchars($facultad['fecha_accion']); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
    <?php endif; ?>
    </div>
</body>
<script>
    // Usar localStorage para mantener el valor de $anio_semestre al hacer clic en los botones
    document.querySelectorAll('.filter-button').forEach(button => {
        button.addEventListener('click', function() {
            var periodo = this.getAttribute('data-period');
            
            // Almacenar el valor en localStorage
            localStorage.setItem('anio_semestre', periodo);

            // Redirigir a la misma página con el nuevo parámetro
            var url = new URL(window.location.href);
            url.searchParams.set('anio_semestre', periodo); // Añadimos el parámetro a la URL
            window.location.href = url; // Redirigimos a la nueva URL
        });
    });

    // Restaurar el valor de localStorage (si existe) al recargar la página
    window.onload = function() {
        if (localStorage.getItem('anio_semestre')) {
            var selectedPeriod = localStorage.getItem('anio_semestre');
            // Se podría hacer algo aquí si es necesario usar el valor seleccionado
            // Sin embargo, los botones seguirán visibles
        }
    };
</script>
    
    <script>
    // Función para calcular y mostrar el conteo regresivo
    function actualizarConteo() {
        const fechaPlazo = new Date("<?php echo $plazo_fecha; ?>T23:59:59").getTime();
        const ahora = new Date().getTime();
        const diferencia = fechaPlazo - ahora;

        if (diferencia <= 0) {
            document.getElementById("countdown").innerHTML = "Plazo finalizado";
            return;
        }

        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        document.getElementById("countdown").innerHTML = 
            `Tiempo restante: <strong>${dias} días, ${horas}h ${minutos}m ${segundos}s</strong>`;

        setTimeout(actualizarConteo, 1000); // Actualiza cada segundo
    }

    actualizarConteo(); // Iniciar conteo regresivo
</script>
        <script>
    // Función para calcular y mostrar el conteo regresivo
    function actualizarConteofac() {
        const fechafac = new Date("<?php echo $plazo_fac; ?>T23:59:59").getTime();
        const ahora = new Date().getTime();
        const diferencia = fechafac - ahora;

        if (diferencia <= 0) {
            document.getElementById("countdownfac").innerHTML = "El plazo ha vencido.";
            return;
        }

        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        document.getElementById("countdownfac").innerHTML = 
            `Tiempo restante: <strong>${dias} días, ${horas}h ${minutos}m ${segundos}s</strong>`;

        setTimeout(actualizarConteofac, 1000); // Actualiza cada segundo
    }

    actualizarConteofac(); // Iniciar conteo regresivo
</script>
     <script>
    // Función para calcular y mostrar el conteo regresivo
    function actualizarConteovra() {
        const fechavra = new Date("<?php echo $plazo_vra; ?>T23:59:59").getTime();
        const ahora = new Date().getTime();
        const diferencia = fechavra - ahora;

        if (diferencia <= 0) {
            document.getElementById("countdownvra").innerHTML = "El plazo ha vencido.";
            return;
        }

        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        document.getElementById("countdownvra").innerHTML = 
            `Tiempo restante: <strong>${dias} días, ${horas}h ${minutos}m ${segundos}s</strong>`;

        setTimeout(actualizarConteovra, 1000); // Actualiza cada segundo
    }

    actualizarConteovra(); // Iniciar conteo regresivo
</script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    
</html>
