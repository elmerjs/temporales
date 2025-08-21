<?php
$active_menu_item = 'gestion_facultad';

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
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
    exit(); // Detener toda la ejecución del script
}

$nombre_sesion = $_SESSION['name'];
// Obtener el año y el mes actuales
$anio_actual = date('Y'); // Año actual
$mes_actual = date('n');  // Mes actual (1-12)

// Determinar el siguiente período basado en la fecha actual
if ($mes_actual >= 1 && $mes_actual <= 6) {
    // Primer semestre: el siguiente es el segundo semestre del mismo año
    $anio_semestre_default = $anio_actual . '-2';
} else {
    // Segundo semestre: el siguiente es el primer semestre del año siguiente
    $anio_semestre_default = ($anio_actual + 1) . '-1';
}

// Lógica para inicializar $anio_semestre
$anio_semestre = isset($_POST['anio_semestre']) 
    ? $_POST['anio_semestre'] 
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

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
            SUM(CASE 
                WHEN fac_periodo.fp_estado IS NOT NULL AND fac_periodo.fp_estado != 0 THEN 1 
                ELSE 0 
            END) AS completed_facultades
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
   $sql = "
SELECT 
    depto_periodo.id_depto_periodo, 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio AS nombre_departamento,

    -- Docente Ocasional por sede
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_ocasional_popayan,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_regionalizacion,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán-Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_popayan_regionalizacion,

    depto_periodo.dp_estado_ocasional,

    -- Docente Cátedra por sede
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_catedra_popayan,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_catedra_regionalizacion,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán-Regionalización' THEN 1 ELSE 0 END) AS total_catedra_popayan_regionalizacion,

    depto_periodo.dp_estado_catedra,
    depto_periodo.dp_estado_total,
    depto_periodo.dp_acepta_fac,

    -- Total de filas
    COUNT(*) OVER() AS total_filas
FROM 
    depto_periodo
LEFT JOIN  
    solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo 
    AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
LEFT JOIN
    deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
WHERE 
    deparmanentos.FK_FAC = $facultad_id 
    AND depto_periodo.periodo = '$anio_semestre' 
    AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
GROUP BY 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio,
    depto_periodo.dp_estado_ocasional,
    depto_periodo.dp_estado_catedra,
    depto_periodo.dp_estado_total,
    depto_periodo.dp_acepta_fac;
";

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
//$total_facultades = count($facultades);

function obtenerenvioaFacultad($facultad_id,$anio_semestre) { //deberia llalmrse de la fac..a la vra
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

<!-- Bootstrap JS and dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

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
        font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */
 color: #000066;   
    }

    table th {
        text-align: left;
                font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */
 color: #000066;     }

    /* Contenedor general */
    .container {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 15px;
        box-sizing: border-box;
    }

    @media (max-width: 992px) {
        .container {
            max-width: 90%;
            padding: 10px;
        }
    }

    @media (max-width: 768px) {
        .container {
            max-width: 95%;
            padding: 5px;
        }
    }

    @media (max-width: 480px) {
        .container {
            max-width: 100%;
            padding: 5px;
        }
    }
.encabezado-facultad {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding: 3px;
    border: 1px solid #c2dcf0;  /* Borde azul claro institucional */
    /*background-color: #ECF0FF;  /* Fondo azul claro institucional (#16A8E1 con 10% de opacidad) */
    border-radius: 5px;
        font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */
}

.encabezado-facultad .nombre-facultad,
.encabezado-facultad .estado-envio {
    flex: 1;
    padding: 5px;
            font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */

}

.encabezado-facultad strong {
    color: #1F2124;  /* Azul oscuro institucional */
    margin-right: 5px;
}    

    .formulario-estado {
        background-color: transparent;
        padding: 0;
        border: none;
        border-radius: 5px;
        display: flex;
        align-items: center;
        margin-bottom: 1px;
        font-size: 14px;
        height: 42px;
        justify-content: flex-end;
    }

    .formulario-estado label {
        margin-right: 8px;
        font-size: 14px;
        color: #333;
    }

    .formulario-estado select,
    .formulario-estado input[type='submit'] {
        padding: 4px 8px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 5px;
        height: 28px;
        margin-right: 10px;
    }

    .formulario-estado input[type='submit'] {
        background-color: #007bff;
        color: white;
        cursor: pointer;
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

    .modal-contentb textarea {
        width: 100%;
        box-sizing: border-box;
        margin-top: 10px;
        resize: vertical;
    }

    button {
        padding: 10px;
        margin-top: 10px;
    }

    /* Estilo principal de la tabla */
    .table-bordered {
        border: 1px solid #dee2e6;
        width: 100%;
        margin-bottom: 1rem;
        background-color: transparent;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    .table-bordered thead {
        background-color: #00843D;
        color: white;
    }

    .table-bordered thead th {
        padding: 12px 15px;
        text-align: center;
        vertical-align: middle;
        font-weight: 600;
        border-bottom: 2px solid #00612D;
    }

    .table-bordered tbody td {
        padding: 10px 15px;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }

    .table-bordered tbody tr:hover {
        background-color: rgba(0, 132, 61, 0.05);
    }

    .table-bordered tbody tr[style*="background-color: #d4edda"] {
        background-color: rgba(0, 132, 61, 0.1) !important;
    }

    .table-bordered tbody tr[style*="background-color: #faeaea"] {
        background-color: rgba(220, 53, 69, 0.05) !important;
    }

    .table-bordered .btn-sm {
        padding: 4px 8px;
        font-size: 0.85rem;
        border-radius: 3px;
    }

    .table-bordered .fas {
        font-size: 1.1em;
    }

    .table-bordered .fa-eye {
        color: #00843D;
        transition: color 0.2s;
    }

    .table-bordered .fa-eye:hover {
        color: #00612D;
    }

    .fa-lock.text-success {
        color: #00843D !important;
    }

    .fa-lock-open.text-danger {
        color: #dc3545 !important;
    }

    .fa-check-circle.text-success {
        color: #00843D !important;
    }

    .fa-times-circle.text-danger {
        color: #dc3545 !important;
    }
  .fa-times.text-danger {
        color: #dc3545 !important;
    }
      .btn-success {
        background-color: #249337; /* Color institucional 6 */
        border-color: #249337;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .btn-success:hover {
        background-color: #1e7a2e; /* 10% más oscuro que #249337 */
        border-color: #1e7a2e;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .btn-danger {
        background-color: #E52724; /* Color institucional exacto */
        border-color: #E52724;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .btn-danger:hover {
        background-color: #C21F1D; /* 10% más oscuro */
        border-color: #C21F1D;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .text-success {
        color: #00843D !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .text-info {
        color: #17a2b8 !important;
    }

    .text-muted {
        color: #6c757d !important;
    }

    @media (max-width: 768px) {
        .table-bordered {
            font-size: 0.85rem;
        }

        .table-bordered thead th,
        .table-bordered tbody td {
            padding: 8px 10px;
        }
    }
</style>
    
  
    
    <!--verificar cuales sirven-->
      
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
    --unicauca-orange: #F8AE15; /* Naranja para advertencias */
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
            font-family: 'Open Sans', sans-serif; /* <-- This is the added line */

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
        background-color: var(--unicauca-blue-dark) !important;
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

/* Paleta institucional */
:root {
    --unicauca-primary: #000066;
    --unicauca-secondary: #0051C6;
    --unicauca-accent: #16A8E1;
    --unicauca-success: #249337;
    --unicauca-danger: #E52724;
    --unicauca-light: #e8f4ff;
    --unicauca-gray: #f0f4f8;
}

.table-institucional {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid var(--unicauca-accent);
    border-radius: 8px;
    overflow: hidden;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0, 42, 158, 0.08);
}

.table-institucional thead th {
    background: linear-gradient(to bottom, var(--unicauca-primary), var(--unicauca-secondary));
    color: white;
    text-align: center;
    padding: 12px 8px;
    font-weight: 600;
    border: none;
}

.table-institucional tbody td {
    padding: 10px 8px;
    border-bottom: 1px solid #dde5ed;
    text-align: center;
}

.table-institucional tbody tr:last-child td {
    border-bottom: none;
}

/* Eliminado el intercalado gris/blanco */
/* Mantenemos solo el hover para mejorar la usabilidad */
.table-institucional tbody tr:hover {
    background-color: var(--unicauca-light);
}

/* Estilos para botones y enlaces */
.btn-departamento {
    background: none;
    border: none;
    color: var(--unicauca-primary);
    cursor: pointer;
    font-weight: 600;
    text-align: left;
    padding: 0;
    width: 100%;
    transition: color 0.3s;
}

.btn-departamento:hover {
    color: var(--unicauca-accent);
    text-decoration: underline;
}

.btn-eye {
    background: none;
    border: none;
    color: var(--unicauca-secondary);
    cursor: pointer;
    font-size: 1.2em;
    transition: transform 0.3s, color 0.3s;
}

.btn-eye:hover {
    transform: scale(1.2);
    color: var(--unicauca-primary);
}

/* Estados */
.text-success {
    color: var(--unicauca-success) !important;
}
.text-successb {
    color: var(--unicauca-primary) !important;
}
.text-danger {
    color: var(--unicauca-danger) !important;
}

.text-warning {
    color: #F8AE15 !important; /* Amarillo institucional */
}

.text-info {
    color: var(--unicauca-accent) !important;
}

/* Botones de acción */
.btn-action {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.85rem;
    transition: all 0.3s;
}

.btn-accept {
    background-color: var(--unicauca-success);
    border: 1px solid var(--unicauca-success);
    color: white;
}

.btn-accept:hover {
    background-color: #1e7a2e;
    border-color: #1e7a2e;
}

.btn-reject {
    background-color: var(--unicauca-danger);
    border: 1px solid var(--unicauca-danger);
    color: white;
}

.btn-reject:hover {
    background-color: #c21f1d;
    border-color: #c21f1d;
}

.btn-disabled {
    background-color: #cccccc;
    border: 1px solid #aaaaaa;
    color: #777777;
    cursor: not-allowed;
}

/* Fondo para departamentos */
.bg-verde {
    background-color: rgba(233, 244, 235, 0.2); !important; /* Verde institucional claro */
}

.bg-rojo {
    background-color:rgba(252, 233, 233, 0.5) !important; /* Rojo institucional claro */
}

/* Iconos */
.fa-lock {
    color: var(--unicauca-success);
}

.fa-lock-open {
    color: var(--unicauca-danger);
}

.fa-check-circle {
    color: var(--unicauca-success);
}
.fa-check {
    color: var(--unicauca-success);
}
    
.fa-times-circle {
    color: var(--unicauca-danger);
}
.fa-times {
    color: var(--unicauca-danger);
}
/* Tooltip para números */
td[title]:hover::after {
    content: attr(title);
    position: absolute;
    background-color: rgba(0, 42, 158, 0.9);
    color: white;
    padding: 8px;
    border-radius: 4px;
    z-index: 100;
    font-size: 0.85rem;
    max-width: 300px;
    transform: translateY(-100%);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* Estilos específicos para las columnas sin intercalado */
.td-simple {
    background-color: white !important;
    border-bottom: 1px solid #e0e6ed;
    color: #000066;  /* Azul institucional medio */
    font-weight: normal;  /* Sin negrita */
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 0.95rem;
        font-family: 'Open Sans', sans-serif !important; /* <-- This is the added line */

}
</style>


</head>
<body>
    <br>
    <div class="container">
<div class="card card-plazo mb-4">

         <div class="card-header bg-unicauca-blue-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Consulta por Facultad y  Sede <?php echo $anio_semestre; ?></h5>
        </div>
    
        <div class="row g-3 p-3">  <!-- Agregamos espaciado entre columnas -->
            <div class="col-md-8">   
                             <div class="content-main bg-unicauca-light p-4 rounded shadow-sm h-100 border-start border-4">
   
                <?php
                $envio=0;
                $nombre_facultad='';
                $acepta_vra =NULL;
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
             $osbservacion_vrab = obtenerobs_vra($id_facultad, $anio_semestre);

   if ($acepta_vra === null || $acepta_vra == 0) {
    $acepta_estado = '<i class="fas fa-hourglass-half text-warning" style="color:red;"></i> <span style="color: #000066; font-weight: normal;">Pendiente</span>';
} elseif ($acepta_vra == 1) {
    $acepta_estado = '<i class="fas fa-times-circle" style="color:red;" title="' . htmlspecialchars($osbservacion_vrab) . '"></i> <span style="color: #000066; font-weight: normal;">Devuelto</span>';
} elseif ($acepta_vra == 2) {
    $acepta_estado = '<i class="fas fa-check-circle" style="color:green;"></i> <span style="color: #000066; font-weight: normal;">Aceptado</span>';
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

                        
              $fp_periodo = $anio_semestre;
              
                        
// Mostrar la facultad con los estados solo si el tipo de usuario es 1
                        
if ($tipo_usuario == 1) {
    

  echo "
<div class='encabezado-facultad'>
    <div class='nombre-facultad'>
    <strong>Facultad:</strong> <span style='color: #000066; font-weight: normal;'>$nombre_facultad</span>
</div>
    <div class='estado-envio'>
        <strong>Recibido V.R.A:</strong> $envio_estado
    </div>
    <div class='estado-envio'>
        <strong>Respuesta V.R.A:</strong> $acepta_estado
    </div>";
$cierreperiodo= obtenerperiodo($anio_semestre);
if ($envio == 1 && $cierreperiodo == 0) {
    // Botones habilitados
    echo "
    <div>
        <button class='btn btn-sm btn-success' onclick='handleAccept(\"$fp_periodo\", $id_facultad)'>Aceptar</button>
        <button class='btn btn-sm btn-danger' onclick='handleReject(\"$fp_periodo\", $id_facultad)'>Devolver</button>
    </div>";
} else {
    // Botones deshabilitados con título dinámico
    $titulo = ($cierreperiodo == 1) ? "Periodo cerrado" : "Pendiente de envío desde la facultad";
    echo "
    <div title='$titulo'>
        <button class='btn btn-sm btn-success' disabled>Aceptar</button>
        <button class='btn btn-sm btn-danger' disabled>Devolver</button>
    </div>";
}

echo "
    <!-- Modal para la observación al rechazar -->
    <div class='modal fade' id='rejectModal-$id_facultad' tabindex='-1' aria-labelledby='rejectModalLabel-$id_facultad' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='rejectModalLabel-$id_facultad'>Observación</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='rejectForm-$id_facultad'>
                        <div class='mb-3'>
                            <label for='observation-$id_facultad' class='form-label'>Detalle de la observación</label>
                            <textarea class='form-control' id='observation-$id_facultad' rows='3' required></textarea>
                        </div>
                        <button type='button' class='btn btn-primary' onclick='submitReject(\"$fp_periodo\", $id_facultad)'>Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>";

 // nuevo estado respusta vra $acepta_vra envio desde fac $envio  
    
  //  echo "<h3>Facultad: $nombre_facultad / Envíado a V.R.A: $envio_estado </h3>";//Respuesta V.R.A.: $acepta_estado</h3> (se quito para er si mejroa)
} else {
echo "
<div class='encabezado-facultad'>
  <div class='nombre-facultad'>
    <strong>Facultad:</strong> <span style='color: #000066; font-weight: normal;'>$nombre_facultad</span>
</div>
</div>
";}   
                 ?>
       <?php if ($tipo_usuario == 1): ?>
<button id="toggleTableBtn_<?php echo $id_facultad; ?>" 
        class="btn btn-link text-decoration-none p-0" 
        style="font-family: 'Lato', sans-serif; font-weight: 300; color: #007BFF; margin-top: -10px;" 
        onclick="toggleTable('<?php echo $id_facultad; ?>')">
    <i id="iconToggle_<?php echo $id_facultad; ?>" class="fas fa-chevron-down"></i>
</button>
<?php endif; ?>

<div id="tableContainer_<?php echo $id_facultad; ?>" <?php echo ($tipo_usuario == 1) ? "style='display: none;'" : ""; ?>>
    <table class="table-institucional">
        <thead>
            <tr>
                <th>Departamento</th>
                <th style="text-align: center;">Ver</th>
                <th>Tipo</th>
                <th>#</th>
                <th>Cierre</th>
                <th>Envío a Facultad</th>
                <th style="text-align: center">Acción Fac</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $nombre_departamento_anterior = null;

            foreach ($datos as $row) {
                $bg_verde = '#E9F4EB';
                $bg_rojo = '#FCE9E9';
                $bg_color = $row['dp_estado_total'] == 1 ? $bg_verde : $bg_rojo;
                $bg_class = $row['dp_estado_total'] == 1 ? 'bg-verde' : 'bg-rojo';
                
                if ($row['nombre_departamento'] !== $nombre_departamento_anterior) {
                    echo "<tr>";
                    echo "<td class='$bg_class' style='text-align: left;' rowspan='2'>
                        <form action='consulta_todo_depto.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['fk_depto_dp']) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'> 
                            <button type='submit' class='btn-departamento'>
                                " . htmlspecialchars($row['nombre_departamento']) . "
                            </button>
                        </form>
                    </td>";
                    echo "<td class='$bg_class' style='text-align: center;' rowspan='2'>
                        <form action='consulta_todo_depto.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['fk_depto_dp']) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'> 
                            <button type='submit' class='btn-eye'>
                                <i class='fas fa-eye'></i>
                            </button>
                        </form>
                    </td>";

                    $nombre_departamento_anterior = $row['nombre_departamento'];
                } else {
                    echo "<tr>";
                }
                ?>
                <td class="td-simple">Ocasional</td>
                <td class="td-simple" title="Popayán: <?php echo htmlspecialchars($row['total_ocasional_popayan']); ?>, Regionalización: <?php echo htmlspecialchars($row['total_ocasional_regionalizacion']); ?>, Popayán-Regionalización: <?php echo htmlspecialchars($row['total_ocasional_popayan_regionalizacion']); ?>">
                    <?php 
                        $total_ocasional = $row['total_ocasional_popayan'] + $row['total_ocasional_regionalizacion'] + $row['total_ocasional_popayan_regionalizacion'];
                        echo htmlspecialchars($total_ocasional); 
                    ?>
                </td>
                <td class="td-simple">
                    <?php
                    echo ($row['dp_estado_ocasional'] == 'ce') ? '<i class="fas fa-lock"></i>' : '<i class="fas fa-lock-open"></i>';
                    ?>
                </td>
                <td rowspan="2" class="align-middle text-center">
                    <form action="consulta_todo_depto.php" method="POST" style="display:inline;">
                        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($row['fk_depto_dp']); ?>">
                        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                        <button type="submit" style="background:none;border:none;cursor:pointer;">
                            <?php
                            echo ($row['dp_estado_total'] == 1) ? '<i class="fas fas fa-check"></i>' : '<i class="fas fa-times  "></i>';
                            ?>
                        </button>
                    </form>
                </td>
                <?php
                $td_disabled = ($row['dp_estado_total'] <> 1);
                ?>
                <td rowspan="2" class="align-middle text-center" 
                    style="<?= $td_disabled ? 'background-color: #f8f9fa; color: #6c757d; pointer-events: none;' : '' ?>">
                    <div class="mb-2">
                        <?php
                        if ($row['dp_acepta_fac'] === 'aceptar') {
                            echo '<span class="text-successb">Aceptada por la facultad</span>';
                        } elseif ($row['dp_acepta_fac'] === 'rechazar') {
                            echo '<span class="text-danger">No aceptada por la facultad</span>';
                        } elseif ($row['dp_acepta_fac'] === 'subsanado') {
                            echo '<span class="text-info">Subsanado-pendiente</span>';
                        } else {
                            echo '<span class="text-warning">Pendiente</span>';
                        }
                        ?>
                    </div>
                    <?php 
                    if (!$td_disabled) {
                        if ($row['dp_acepta_fac'] === 'rechazar') { 
                            echo '<span class="text-muted">Acciones no disponibles</span>';
                        } elseif ($row['dp_acepta_fac'] !== 'aceptar' || $acepta_vra  == 1|| $acepta_vra  == 0) {
                            if ($tipo_usuario == '2') {
                                if ($envio != 1) {
                                    ?>
                                    <form id="estadoForm_<?= $row['id_depto_periodo'] ?>" method="POST">
                                        <input type="hidden" name="estado" value="<?= $row['dp_acepta_fac'] ?>" />
                                        <input type="hidden" name="anio_semestre" value="<?= $anio_semestre ?>" />
                                        <button type="button" class="btn-action btn-accept" 
                                            onclick="actualizarEstado(<?= $row['id_depto_periodo'] ?>, 'aceptar', '<?= $anio_semestre ?>')">
                                            Aceptar
                                        </button>
                                        <button type="button" class="btn-action btn-reject" 
                                            onclick="actualizarEstado(<?= $row['id_depto_periodo'] ?>, 'rechazar', '<?= $anio_semestre ?>')">
                                            Devolver
                                        </button>
                                    </form>
                                    <?php 
                                } else { 
                                    echo '<span class="text-muted">Acciones no disponibles</span>';
                                }
                            } else {
                                echo '<span class="text-muted">Acciones no disponibles</span>';
                            }
                        } else {
                            echo '<span class="text-muted">Acciones no disponibles</span>';
                        }
                    } else {
                        echo '<span class="text-muted">Acciones no disponibles</span>';
                    }
                    ?>
                </td>
                <tr>
                <td class="td-simple">Cátedra</td>
                <td class="td-simple" title="Popayán: <?php echo htmlspecialchars($row['total_catedra_popayan']); ?>, Regionalización: <?php echo htmlspecialchars($row['total_catedra_regionalizacion']); ?>, Popayán-Regionalización: <?php echo htmlspecialchars($row['total_catedra_popayan_regionalizacion']); ?>">
                    <?php 
                        $total_catedra = $row['total_catedra_popayan'] + $row['total_catedra_regionalizacion'] + $row['total_catedra_popayan_regionalizacion'];
                        echo htmlspecialchars($total_catedra); 
                    ?>
                </td>
                <td class="td-simple">
                    <a>
                        <?php
                        echo ($row['dp_estado_catedra'] == 'ce') ? '<i class="fas fa-lock"></i>' : '<i class="fas fa-lock-open"></i>';
                        ?>
                    </a>
                </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php foreach ($datos_facultades as $id_facultad => $datos) { ?>
    document.getElementById('toggleTableBtn_<?php echo $id_facultad; ?>').addEventListener('click', function () {
        const tableContainer = document.getElementById('tableContainer_<?php echo $id_facultad; ?>');
        const iconToggle = document.getElementById('iconToggle_<?php echo $id_facultad; ?>');
        
        if (tableContainer.style.display === 'none' || tableContainer.style.display === '') {
            tableContainer.style.display = 'block';
            iconToggle.classList.remove('fa-chevron-down');
            iconToggle.classList.add('fa-chevron-up');
        } else {
            tableContainer.style.display = 'none';
            iconToggle.classList.remove('fa-chevron-up');
            iconToggle.classList.add('fa-chevron-down');
        }
    });
    <?php } ?>
});
</script>              <?php
                    }
                } else {
                    echo "<p>No se encontraron resultados.</p>";
                }
                ?>
            </div>
    </div>
            
            
            <style>
/* Paleta institucional */
:root {
    --unicauca-primary: #002A9E;
    --unicauca-secondary: #0051C6;
    --unicauca-accent: #16A8E1;
    --unicauca-success: #249337;
    --unicauca-danger: #E52724;
    --unicauca-warning: #F8AE15;
        --unicauca-light: #ECF0FF;

    --unicauca-lightb: /*#F5F7FF;*/
    --unicauca-dark: #1a1a2e;
}

/* Fondo y bordes */
.bg-unicauca-light {
    background-color: var(--unicauca-lightb);
}

.border-unicauca {
  /*  border-color: var(--unicauca-accent) !important;*/
}

/* Textos */
.text-unicauca-primary {
    color: var(--unicauca-primary);
}

.text-unicauca-secondary {
    color: var(--unicauca-secondary);
}

.text-unicauca-success {
    color: var(--unicauca-success);
}

.text-unicauca-danger {
    color: var(--unicauca-danger);
}

.text-unicauca-dark {
    color: var(--unicauca-dark);
}

/* Botones */
.btn-unicauca-primary {
    background-color: var(--unicauca-primary);
    border-color: var(--unicauca-primary);
    color: white;
}

.btn-unicauca-primary:hover {
    background-color: #001a7a;
    border-color: #001a7a;
    color: white;
}

.btn-unicauca-secondary {
    background-color: var(--unicauca-secondary);
    border-color: var(--unicauca-secondary);
    color: white;
}

.btn-unicauca-secondary:hover {
    background-color: #003a9e;
    border-color: #003a9e;
    color: white;
}

.btn-unicauca-success {
    background-color: var(--unicauca-success);
    border-color: var(--unicauca-success);
    color: white;
}

.btn-unicauca-success:hover {
    background-color: #1c7a2e;
    border-color: #1c7a2e;
    color: white;
}

.btn-unicauca-warning {
    background-color: var(--unicauca-warning);
    border-color: var(--unicauca-warning);
    color: #333;
}

.btn-unicauca-warning:hover {
    background-color: #e09e13;
    border-color: #e09e13;
    color: #333;
}

/* Badges */
.bg-unicauca-success {
    background-color: var(--unicauca-success);
}

.bg-unicauca-danger {
    background-color: var(--unicauca-danger);
}

.bg-unicauca-warning {
    background-color: var(--unicauca-warning);
}

/* Progress bar */
.bg-unicauca-gradient {
    background: linear-gradient(90deg, var(--unicauca-success), var(--unicauca-accent));
}

.bg-unicauca-gradient-secondary {
    background: linear-gradient(90deg, var(--unicauca-secondary), var(--unicauca-accent));
}

/* Tarjetas de estado */
.status-card {
    background-color: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 4px solid var(--unicauca-accent);
}

.observacion-box {
    background-color: rgba(22, 168, 225, 0.1);
    border-left: 3px solid var(--unicauca-accent);
}

/* Sección de reportes */
.report-section {
    border-top: 2px dashed var(--unicauca-accent);
    padding-top: 20px;
}
                /* Estilos para la caja de observación */
.observacion-box {
    background-color: rgba(22, 168, 225, 0.08);
    border-left: 3px solid var(--unicauca-accent);
    transition: all 0.3s ease;
}

.observacion-box:hover {
    background-color: rgba(22, 168, 225, 0.12);
    box-shadow: 0 2px 8px rgba(0, 42, 158, 0.05);
}

/* Enlace "ver más" */
.ver-mas-link {
    display: inline-block;
    margin-top: 5px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.ver-mas-link:hover {
    text-decoration: underline;
    color: var(--unicauca-primary) !important;
}

/* Contenido de la observación */
.observacion-content {
    position: relative;
    line-height: 1.5;
    color: var(--unicauca-dark);
}

.observacion-corta, .observacion-completa {
    display: inline;
}
                <style>
    /* Estilo para el botón Aceptar */
    .btn.btn-sm.btn-success {
        background-color: #28a745; /* Verde Unicauca mejorado */
        border-color: #28a745;
        color: white;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0.25rem;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }
    
    .btn.btn-sm.btn-success:hover:not(:disabled) {
        background-color: #218838;
        border-color: #1e7e34;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }
    
    .btn.btn-sm.btn-success:before {
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        content: "\f00c"; /* Icono check */
        margin-right: 5px;
    }
    
    /* Estilo para el botón Devolver */
    .btn.btn-sm.btn-danger {
        background-color: #dc3545; /* Rojo Unicauca mejorado */
        border-color: #dc3545;
        color: white;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0.25rem;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }
    
    .btn.btn-sm.btn-danger:hover:not(:disabled) {
        background-color: #c82333;
        border-color: #bd2130;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    }
    
    .btn.btn-sm.btn-danger:before {
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        content: "\f2ea"; /* Icono undo-alt */
        margin-right: 5px;
    }
    
    /* Estilo para botones deshabilitados */
    .btn.btn-sm:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
</style>
</style>
<div class="col-md-4">
    <div class="content-sidebar bg-unicauca-light p-4 rounded-3 border border-unicauca h-100 shadow-sm" style="font-family: 'Open Sans', sans-serif !important;">
        <div class="d-flex align-items-center justify-content-center mb-4">
        <i class="fas fa-tasks fa-2x text-unicauca-primary me-2"></i>
        <h4 class="text-center text-unicauca-primary fw-bold mb-0">Avance: Departamentos</h4>
    </div>

    <div id="avanceProgressBarContainer" class="mb-4">
        <div class="d-flex justify-content-between mb-1">
            <span class="fw-medium">Progreso:</span>
            <span class="fw-bold text-unicauca-primary" id="avancePorcentajeTexto"></span>
        </div>
        <div class="progress" id="avanceProgressWrapper" style="height: 25px;">
            <div id="avanceProgressBar" class="progress-bar bg-unicauca-gradient" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <?php if ($tipo_usuario == '1') : ?>
        <div class="d-flex align-items-center justify-content-center mb-4">
            <i class="fas fa-university fa-2x text-unicauca-secondary me-2"></i>
            <h4 class="text-center text-unicauca-secondary fw-bold mb-0">Avance: Facultades a V.R.A.</h4>
        </div>

        <div id="facultadesProgressBarContainer" class="mb-4">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-medium">Completadas:</span>
                <span class="fw-bold text-unicauca-secondary" id="facultadesPorcentajeTexto"></span>
            </div>
            <div class="progress" id="facultadesProgressWrapper" style="height: 25px;">
                <div id="facultadesProgressBar" class="progress-bar bg-unicauca-gradient-secondary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    <?php endif; ?>
        
        <div class="action-section">
            <?php if ($tipo_usuario != '1') : ?>
                <?php 
                $aceptacion = obtenerAceptacionFacultadfull($facultad_id, $anio_semestre);
                $envio = obtenerenvioaFacultad($facultad_id, $anio_semestre);
                ?>
                
                <?php if ($envio != 1) : ?>
                    <div class="d-grid mb-3">
                        <a href="#" 
                           class="btn btn-unicauca-primary btn-lg <?= ($aceptacion == 0) ? 'disabled' : '' ?>" 
                           <?= ($aceptacion == 0) ? 'tabindex="-1" aria-disabled="true" title="Debe existir al menos un departamento aceptado para enviar"' : '' ?> 
                           onclick="return confirmarAprobacion(<?= $aceptacion ?>)">
                             <i class="fas fa-file-download me-2"></i> Aprobar y Generar Documento
                        </a>
                    </div>
                <?php else : ?>
                    <div class="d-grid mb-3">
                        <a href="oficio_fac2_nuevaplantillatopcero_reimprimirb.php?facultad_id=<?= urlencode($facultad_id) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>" 
                           class="btn btn-unicauca-secondary btn-lg">
                             <i class="fas fa-print me-2"></i> Reimprimir Documento
                        </a>
                    </div>
                <?php endif; 
                
                                 $decano = obtenerDecano($facultad_id);
?>
                
                <div class="status-card mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-medium">Enviado a V.R.A.:</span>
                        <span class="badge bg-<?= ($envio == 1) ? 'unicauca-success' : 'unicauca-danger' ?>">
                            <?= ($envio == 1) ? 'OK ' : 'NO ' ?>
                            <i class="fas <?= ($envio == 1) ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        </span>
                    </div>
                </div>
                
                <div class="d-grid mb-4">
                    <a href="#"
                       class="btn btn-unicauca-warning <?= ($acepta_vra == 1) ? '' : 'disabled' ?>"
                       onclick="return <?= ($acepta_vra == 1) ? "confirmarDeshacerEnvio('$facultad_id', '$anio_semestre');" : "false;" ?>">
                       <i class="fas fa-undo-alt me-2"></i> Deshacer envío a V.R.A.
                    </a>
                </div>
                
                <?php 
                $observacion_vra = obtenerobs_vra($id_facultad, $anio_semestre);
                $icono = '';
                $color_class = '';
                $texto = '';

                switch($acepta_vra) {
                    case 0:
                        $icono = 'fa-clock';
                        $color_class = 'unicauca-warning';
                        $texto = 'Pendiente';
                        break;
                    case 1:
                        $icono = 'fa-exclamation-circle';
                        $color_class = 'unicauca-danger';
                        $texto = 'No aceptada';
                        break;
                    default:
                        $icono = 'fa-check-circle';
                        $color_class = 'unicauca-success';
                        $texto = 'Aceptada';
                }
                ?>
                
                   <div class="status-card mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-medium">Respuesta V.R.A.:</span>
                        <span class="badge bg-<?= $color_class ?>">
                            <?= $texto ?> <i class="fas <?= $icono ?>"></i>
                        </span>
                    </div>
                    
                    <?php if (!empty($observacion_vra)) : ?>
                        <?php
                        $extracto = strlen($observacion_vra) > 100 ? substr($observacion_vra, 0, 100) . '...' : $observacion_vra;
                        $resto = htmlspecialchars(substr($observacion_vra, 100));
                        $tiene_mas = strlen($observacion_vra) > 100;
                        ?>
                        
                        <div class="observacion-box mt-2 p-3 bg-unicauca-light rounded position-relative">
                            <p class="mb-1 fw-medium text-unicauca-dark">Observación:</p>
                            <div class="observacion-content">
                                <span class="observacion-corta"><?= htmlspecialchars($extracto) ?></span>
                                <?php if ($tiene_mas) : ?>
                                    <span class="observacion-completa d-none"><?= htmlspecialchars($resto) ?></span>
                                    <a href="#" class="ver-mas-link text-unicauca-accent" 
                                       onclick="toggleObservacion(this); return false;">
                                        [ver más]
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <script>
                        function toggleObservacion(element) {
                            const container = element.closest('.observacion-content');
                            const corta = container.querySelector('.observacion-corta');
                            const completa = container.querySelector('.observacion-completa');
                            
                            if (completa.classList.contains('d-none')) {
                                completa.classList.remove('d-none');
                                corta.classList.add('d-none');
                                element.textContent = '[ver menos]';
                            } else {
                                completa.classList.add('d-none');
                                corta.classList.remove('d-none');
                                element.textContent = '[ver más]';
                            }
                        }
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <div class="report-section mt-4">
    <h5 class="text-center text-unicauca-primary fw-bold mb-3">Reportes</h5>
    <div class="d-grid gap-2">
        <a href="excel_temporales.php?tipo_usuario=<?= htmlspecialchars($tipo_usuario) ?>&facultad_id=<?= htmlspecialchars($facultad_id) ?>&anio_semestre=<?= htmlspecialchars($anio_semestre) ?>" 
            class="btn btn-unicauca-success">
            <i class="fas fa-file-excel me-2"></i> Reporte General
        </a>
      <!--
        <a href="excel_temporales_fac.php?tipo_usuario=<?//= htmlspecialchars($tipo_usuario) ?>&facultad_id=<?//= htmlspecialchars($facultad_id) ?>&anio_semestre=<?//= htmlspecialchars($anio_semestre) ?>" 
            class="btn btn-unicauca-success">
            <i class="fas fa-file-pdf me-2"></i> Reporte Imprimible
        </a>-->
        
        <?php if ($tipo_usuario == '1') : ?>
            <a href="excel_temporales_novedades.php?tipo_usuario=<?= htmlspecialchars($tipo_usuario) ?>&facultad_id=<?= htmlspecialchars($facultad_id) ?>&anio_semestre=<?= htmlspecialchars($anio_semestre) ?>" 
                class="btn btn-unicauca-success">
                <i class="fas fa-file-alt me-2"></i> Reporte con Novedades
            </a>
        <?php endif; ?>

        <a href="gestion_vinculacion.php?tipo_usuario=<?= htmlspecialchars($tipo_usuario) ?>&facultad_id=<?= htmlspecialchars($facultad_id) ?>&anio_semestre=<?= htmlspecialchars($anio_semestre) ?>"
            class="btn btn-unicauca-primary"> <i class="fas fa-table me-2"></i> Listado profesores solicitados
        </a>
    </div>
</div>
        </div>
    </div>
</div>
    </div>

        
    <div id="modalObservacionInput" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0px 0px 10px gray; border-radius: 5px; z-index: 1000; width: 60%; max-height: 80%; overflow-y: auto;">
    <label for="observacionInput">Ingrese su observación:</label>
    <textarea id="observacionInput" style="width: 100%; height: 150px; margin-bottom: 10px;"></textarea>
    <div>
        <button onclick="guardarObservacion()" style="background: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Guardar</button>
        <button onclick="cerrarModalObservacion()" style="background: #ccc; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Cancelar</button>
    </div>
</div>

<div id="fondoModalInput" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;"></div>
 </div>           
     <script>
var idDeptoPeriodoGlobal = null;
var estadoGlobal = null;
var anioSemestreGlobal = null;
function actualizarEstado(idDeptoPeriodo, estado, anioSemestre) {
    var observacion = '';

    if (estado === 'rechazar') {
        var confirmarDevolucion = confirm("¿Está seguro que desea devolver la solicitud?");
        if (!confirmarDevolucion) {
            return;
        }

        // Mostrar el modal de observación directamente
        document.getElementById('modalObservacionInput').style.display = 'block';
        document.getElementById('fondoModalInput').style.display = 'block';

        // Guardar datos para usarlos luego
        window.idDeptoPeriodo = idDeptoPeriodo;
        window.estado = estado;
        window.anioSemestre = anioSemestre;
        return;
    }

    if (estado === 'aceptar') {
        var confirmarAceptar = confirm("¿Está seguro que desea aceptar esta solicitud?");
        if (!confirmarAceptar) {
            return;
        }
        // Enviar directamente
        enviarSolicitud(idDeptoPeriodo, estado, observacion, anioSemestre);
    }
}

function guardarObservacion() {
    var observacion = document.getElementById('observacionInput').value;

    if (observacion.trim() === "") {
        observacion = "Sin observación";
    }

    // Cerrar el modal
    cerrarModalObservacion();

    // Enviar la solicitud AJAX con la observación
    enviarSolicitud(window.idDeptoPeriodo, window.estado, observacion, window.anioSemestre);
}

function cerrarModalObservacion() {
    document.getElementById('modalObservacionInput').style.display = 'none';
    document.getElementById('fondoModalInput').style.display = 'none';
}

function enviarSolicitud(idDeptoPeriodo, estado, observacion, anioSemestre) {
    mostrarCargando();

    $.ajax({
        url: 'actualizar_aceptacion_fac.php',
        type: 'POST',
        data: {
            id_depto_periodo: idDeptoPeriodo,
            estado: estado,
            observacion: observacion,
            anio_semestre: anioSemestre
        },
        success: function(response) {
            var mensaje = (estado === 'aceptar')
                ? 'Solicitud de vinculación aceptada, en espera de la aprobación por parte de la Vicerrectoría Académica.'
                : 'Solicitud de vinculación no aceptada. Se ha notificado al departamento para su correspondiente corrección. Favor estar atento a su respuesta.';

            ocultarCargando();
            alert(mensaje);
            window.location.href = "report_depto_full.php?anio_semestre=" + encodeURIComponent(anioSemestre);
        },
        error: function(xhr, status, error) {
            ocultarCargando();
            alert('Error al actualizar el estado: ' + error);
        }
    });
}
// Función para mostrar el mensaje de carga
function mostrarCargando() {
    var loader = document.createElement("div");
    loader.id = "loadingOverlay";
    loader.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;">
            <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0px 0px 10px rgba(0,0,0,0.2);">
                <i class="fas fa-clock" style="font-size: 40px; color: #007bff; margin-bottom: 10px;"></i>
                <p style="font-size: 16px; font-weight: bold; color: #333;">Generando notificación automática a la dependencia...</p>
            </div>
        </div>
    `;
    document.body.appendChild(loader);
}

// Función para ocultar el mensaje de carga
function ocultarCargando() {
    var loader = document.getElementById("loadingOverlay");
    if (loader) {
        loader.remove();
    }
}

    // Tu función toggleObservacion se mantiene igual
    function toggleObservacion(element) {
        const container = element.closest('.observacion-content');
        const corta = container.querySelector('.observacion-corta');
        const completa = container.querySelector('.observacion-completa');
        
        if (completa.classList.contains('d-none')) {
            completa.classList.remove('d-none');
            corta.classList.add('d-none');
            element.textContent = '[ver menos]';
        } else {
            completa.classList.add('d-none');
            corta.classList.remove('d-none');
            element.textContent = '[ver más]';
        }
    }

     console.log("Script de avance de progreso ejecutándose y configurando tooltips en barras.");

    // Calcula y muestra el porcentaje de avance de Departamentos
    var totalDepartamentos = <?php echo $total_departamentos; ?>;
    var totalCeTotal = <?php echo $total_ce_total; ?>;
    var avanceProgressBar = document.getElementById('avanceProgressBar');
    var avancePorcentajeTexto = document.getElementById('avancePorcentajeTexto');
    // Nuevo: Referencia al contenedor de la barra de progreso
    var avanceProgressWrapper = document.getElementById('avanceProgressWrapper');

    if (totalDepartamentos > 0) {
        var porcentajeAvance = (totalCeTotal / totalDepartamentos * 100).toFixed(2);
        avanceProgressBar.style.width = porcentajeAvance + '%';
        avanceProgressBar.setAttribute('aria-valuenow', porcentajeAvance);
        avanceProgressBar.textContent = porcentajeAvance + '%';
        avancePorcentajeTexto.textContent = porcentajeAvance + '%';

        // *** AÑADIDO: Título para el contenedor de la barra de Departamentos ***
        var tooltipAvance = 'Completados: ' + totalCeTotal + ' de ' + totalDepartamentos + ' departamentos.';
        avanceProgressWrapper.setAttribute('title', tooltipAvance);
        avancePorcentajeTexto.setAttribute('title', tooltipAvance); // Opcional: mantiene el tooltip en el texto
    } else {
        avanceProgressBar.style.width = '0%';
        avanceProgressBar.setAttribute('aria-valuenow', 0);
        avanceProgressBar.textContent = 'N/A';
        avancePorcentajeTexto.textContent = 'N/A';
        // *** Título cuando no hay departamentos ***
        var tooltipAvanceNA = 'No hay departamentos para calcular el avance.';
        avanceProgressWrapper.setAttribute('title', tooltipAvanceNA);
        avancePorcentajeTexto.setAttribute('title', tooltipAvanceNA); // Opcional
    }

    <?php if ($tipo_usuario == '1') : ?>
        // Calcula y muestra el porcentaje de avance de Facultades
        var totalFacultades = <?php echo $total_facultades; ?>;
        var totalFacultadesCompletas = <?php echo $completed_facultades; ?>;
        var facultadesProgressBar = document.getElementById('facultadesProgressBar');
        var facultadesPorcentajeTexto = document.getElementById('facultadesPorcentajeTexto');
        // Nuevo: Referencia al contenedor de la barra de progreso
        var facultadesProgressWrapper = document.getElementById('facultadesProgressWrapper');

        if (totalFacultades > 0) {
            var porcentajeFacultades = (totalFacultadesCompletas / totalFacultades * 100).toFixed(2);
            facultadesProgressBar.style.width = porcentajeFacultades + '%';
            facultadesProgressBar.setAttribute('aria-valuenow', porcentajeFacultades);
            facultadesProgressBar.textContent = porcentajeFacultades + '%';
            facultadesPorcentajeTexto.textContent = porcentajeFacultades + '%';

            // *** AÑADIDO: Título para el contenedor de la barra de Facultades ***
            var tooltipFacultades = 'Completadas: ' + totalFacultadesCompletas + ' de ' + totalFacultades + ' facultades.';
            facultadesProgressWrapper.setAttribute('title', tooltipFacultades);
            facultadesPorcentajeTexto.setAttribute('title', tooltipFacultades); // Opcional
        } else {
            facultadesProgressBar.style.width = '0%';
            facultadesProgressBar.setAttribute('aria-valuenow', 0);
            facultadesProgressBar.textContent = 'N/A';
            facultadesPorcentajeTexto.textContent = 'N/A';
            // *** Título cuando no hay facultades ***
            var tooltipFacultadesNA = 'No hay facultades para calcular el avance.';
            facultadesProgressWrapper.setAttribute('title', tooltipFacultadesNA);
            facultadesPorcentajeTexto.setAttribute('title', tooltipFacultadesNA); // Opcional
        }
    <?php endif; ?>
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
                        <input type="date" class="form-control" id="fechaOficio" name="fecha_oficio" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="decano">Decano</label>
                        <input type="text" class="form-control" id="decano" name="decano" value="<?php echo htmlspecialchars($decano); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="elaboradoPor">Elaborado por</label>
                        <input type="text" class="form-control" id="elaboradoPor" name="elaborado_por" required>
                    </div>
                        <div class="form-group">
                        <label for="folios">Número de Folios</label>
                        <input type="number" class="form-control" id="folios" name="folios" min="1" required>
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
    function confirmarAprobacion(aceptacion) {
        if (aceptacion === 1) {
            const confirmacion = confirm("¿Está seguro de aprobar el envío para la Vicerrectoría Académica? Hay departamentos pendientes de aprobación.");
            if (!confirmacion) {
                // Si el usuario selecciona "Cancelar", no se hace nada.
                return false;
            }
        }
        // Si aceptación no es 1, o si el usuario confirma, se abre el modal.
        $('#oficioModal').modal('show');
        return false; // Previene el comportamiento por defecto del enlace.
    }

    function submitOficioForm() {
        // Obtén el formulario y envíalo (personalizar según necesidades).
        const form = document.getElementById('oficioForm');
        form.submit();
    }
</script>
<script>
function handleAccept(fp_periodo, id_facultad) {
    const confirmacion = confirm("¿Está seguro de que desea aceptar la solicitud?");
    if (!confirmacion) return;

    fetch('process_acept_vra.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'accept',
            fp_periodo: fp_periodo,
            id_facultad: id_facultad
        })
    }).then(response => response.json())
      .then(data => {
          console.log(data); // Muestra el objeto JSON completo
          alert(`Mensaje: ${data.message}\nPeriodo: ${data.fp_periodo}\nFacultad: ${data.id_facultad}`);
          window.location.reload(); // Actualiza la página
      })
      .catch(error => console.error('Error:', error));
}

function handleReject(fp_periodo, id_facultad) {
    const confirmacion = confirm("¿Está seguro de que desea devolver la solicitud?");
    if (!confirmacion) return;

    // Abrir el modal específico para este registro
    const modal = new bootstrap.Modal(document.getElementById(`rejectModal-${id_facultad}`));
    modal.show();
}

function submitReject(fp_periodo, id_facultad) {
    const observation = document.getElementById(`observation-${id_facultad}`).value;

    if (!observation.trim()) {
        alert('Por favor, ingresa una observación.');
        return;
    }

    fetch('process_acept_vra.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'reject',
            fp_periodo: fp_periodo,
            id_facultad: id_facultad,
            observation: observation
        })
    }).then(response => response.json())
      .then(data => {
          console.log(data); // Muestra el objeto JSON completo
          alert(`Mensaje: ${data.message}\nPeriodo: ${data.fp_periodo}\nFacultad: ${data.id_facultad}`);

          // Cerrar el modal antes de actualizar la página
          const modal = bootstrap.Modal.getInstance(document.getElementById(`rejectModal-${id_facultad}`));
          modal.hide();

          window.location.reload(); // Actualiza la página
      })
      .catch(error => console.error('Error:', error));
}

function submitOficioForm() {
    // Obtener valores del formulario
    var fechaOficio = document.getElementById('fechaOficio').value;
    var decano = document.getElementById('decano').value;
    var elaboradoPor = document.getElementById('elaboradoPor').value;
    var numeroOficio = document.getElementById('numeroOficio').value;
    var folios = document.getElementById('folios').value; // Nuevo campo agregado

    // Verificar que los campos no estén vacíos
    if (fechaOficio === '' || decano === '' || elaboradoPor === '' || numeroOficio === '' || folios === '') {
        alert('Por favor, llene todos los campos.');
        return;
    }

    // Obtener valores de las variables PHP
    var facultadId = "<?php echo urlencode($facultad_id); ?>";
    var anioSemestre = "<?php echo urlencode($anio_semestre); ?>";

    // Construir la URL con los parámetros
    var url = 'oficio_fac2_nuevaplantillatopcero.php?fecha_oficio=' + encodeURIComponent(fechaOficio) +
        '&decano=' + encodeURIComponent(decano) +
        '&elaborado_por=' + encodeURIComponent(elaboradoPor) +
        '&numero_oficio=' + encodeURIComponent(numeroOficio) +
        '&folios=' + encodeURIComponent(folios) + // Nuevo parámetro agregado
        '&facultad_id=' + facultadId +
        '&anio_semestre=' + anioSemestre;

    // Redireccionar a la URL
    window.location.href = url;

    // Cerrar el modal
    $('#oficioModal').modal('hide');

    // Espera 2 segundos y luego recarga la página
    setTimeout(function() {
        window.location.reload();
    }, 2000);
}

</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".ver-mas").forEach(function (enlace) {
        enlace.addEventListener("click", function (e) {
            e.preventDefault();
            const completa = this.previousElementSibling;
            const corta = completa.previousElementSibling;

            if (completa.style.display === "none") {
                completa.style.display = "inline";
                this.textContent = "[ver menos]";
            } else {
                completa.style.display = "none";
                this.textContent = "[ver más]";
            }
        });
    });
});
</script>
    </div></body>
</html>

<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>
