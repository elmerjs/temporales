<?php
require('include/headerz.php');

require 'vendor/autoload.php'; // Incluye la configuración necesaria para PHPWord u otras librerías
require 'funciones.php';
    
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

// Lógica para inicializar $anio_semestre actual
$anio_semestre = isset($_POST['anio_semestre']) 
    ? $_POST['anio_semestre'] 
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

// Capture departamento_id if it's sent
$departamento_id_param = isset($_POST['departamento_id'])
    ? $_POST['departamento_id']
    : (isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null);

// Obtener el período anterior
list($anio, $semestre) = explode('-', $anio_semestre);

if ($semestre == '1') {
    $anio_anterior = $anio - 1;
    $semestre_anterior = '2';
} else {
    $anio_anterior = $anio;
    $semestre_anterior = '1';
}

$anio_semestre_anterior = $anio_anterior . '-' . $semestre_anterior;


$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_fac = $row['email_padre'];
    $pk_fac = $row['fk_fac_user'];
    $email_dp = $row['Email'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user= $row['fk_depto_user'];
    $where = "";

   
}

$consultaper = "SELECT * FROM periodo where periodo.nombre_periodo ='$anio_semestre'";
$resultadoper = $conn->query($consultaper);
while ($rowper = $resultadoper->fetch_assoc()) {
    $fecha_ini_cat = $rowper['inicio_sem'];
    $fecha_fin_cat = $rowper['fin_sem'];
    $fecha_ini_ocas = $rowper['inicio_sem_oc'];
    $fecha_fin_ocas = $rowper['fin_sem_oc'];
    $valor_punto = $rowper['valor_punto'];
   
}

if ($tipo_usuario == '1') {
    // No specific WHERE clause needed for tipo_usuario 1 (admin/full access)
    $where = "";
} elseif ($tipo_usuario == '2') {
    // For tipo_usuario 2, filter by faculty
    $where = " WHERE f.PK_FAC = '$pk_fac'";
} elseif ($tipo_usuario == '3') {
 
        // Fallback: if departamento_id wasn't passed, use the department linked to the user session
        $where = " WHERE d.PK_DEPTO = '$depto_user'";
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
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

 <style>
/* ===== COLORS ===== */
:root {
    /* Colores institucionales Unicauca */
    --unicauca-azul: #001282;       /* Azul principal */
    --unicauca-azul-oscuro: #000b41; /* Azul oscuro */
    --unicauca-rojo: #A61717;       /* Rojo institucional */
    --unicauca-rojo-claro: #D32F2F;  /* Rojo más claro */
    --unicauca-blanco: #FFFFFF;      /* Blanco */
    --unicauca-gris: #6C757D;        /* Gris para textos */
    
    /* Colores contextuales */
    --color-success: #28a745;
    --color-warning: #ffc107;
    --color-danger: #dc3545;
    --color-info: #17a2b8;
}
.dataTables_scrollBody {
    overflow-x: auto !important;
}
     #tablaComparativo {
    width: 100% !important;
    table-layout: auto;
}
        
/* ===== BASE TABLE STYLES ===== */
.table-container {
    min-width: 100%;
    overflow-x: auto;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
    border-radius: 0.25rem;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
    padding: 0.75rem;
    vertical-align: middle;
}

.table thead th {
    background: linear-gradient(var(--unicauca-azul), var(--unicauca-azul-oscuro));
    color: var(--unicauca-blanco);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--unicauca-rojo);
}

.table tbody tr:nth-child(even) {
    background-color: rgba(0, 18, 130, 0.03);
}

.table tbody tr:hover {
    background-color: rgba(0, 18, 130, 0.08);
}

/* ===== CELL ALIGNMENT ===== */
.table td {
    text-align: left;
}

.text-center {
    text-align: center !important;
}

.text-right {
    text-align: right !important;
}

/* ===== CONTEXTUAL CLASSES ===== */
.current-period {
    background-color: rgba(0, 123, 255, 0.1) !important;
    font-weight: 500;
}

.previous-period {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.difference {
    background-color: rgba(108, 117, 125, 0.05) !important;
    font-weight: 500;
}

.positive-difference {
    color: var(--unicauca-rojo) !important;
    font-weight: 600;
}

.positive-differenceb {
    color: #e67e22 !important; /* Naranja Unicauca */
    font-weight: 600;
}

.negative-difference {
    color: var(--color-success) !important;
    font-weight: 600;
}

/* ===== INTERACTIVE ELEMENTS ===== */
button.departamento-link {
    background: none;
    border: none;
    color: var(--unicauca-azul);
    padding: 0;
    font: inherit;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    font-weight: 500;
}

button.departamento-link:hover {
    color: var(--unicauca-rojo) !important;
    text-decoration: underline !important;
}

button.departamento-link::after {
    content: '→';
    margin-left: 5px;
    opacity: 0;
    transition: opacity 0.3s;
    color: var(--unicauca-rojo);
}

button.departamento-link:hover::after {
    opacity: 1;
}

/* ===== NOTES & INDICATORS ===== */
.table-notes {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    border-left: 4px solid var(--unicauca-gris);
    font-size: 0.85rem;
}

.color-sample {
    display: inline-block;
    width: 15px;
    height: 15px;
    border-radius: 3px;
    margin-right: 8px;
    vertical-align: middle;
}

.color-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.red { background-color: var(--unicauca-rojo); }
.orange { background-color: #e67e22; }
.black { background-color: #333; }

/* ===== FORM ELEMENTS ===== */
textarea.form-control {
    resize: both;
    min-height: 24px;
    transition: all 0.2s ease;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

textarea.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 18, 130, 0.25);
    border-color: var(--unicauca-azul);
}

.position-relative {
    min-width: 200px;
}

select.form-select-sm {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

/* ===== DISABLED STATES ===== */
textarea:disabled, 
select:disabled {
    background-color: #f8f9fa;
    opacity: 0.7;
    cursor: not-allowed;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ===== RESPONSIVE ADJUSTMENTS ===== */
@media (max-width: 992px) {
    .container {
        max-width: 90%;
        padding: 10px;
    }
    
    option {
        padding-right: 10px;
    }
}

@media (max-width: 768px) {
    .container {
        max-width: 95%;
        padding: 5px;
    }
    
    textarea.form-control {
        resize: vertical;
    }
    
    .table thead {
        display: none;
    }
    
    .table, .table tbody, .table tr, .table td {
        display: block;
        width: 100%;
    }
    
    .table tr {
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    
    .table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
        border: none;
        border-bottom: 1px solid #dee2e6;
    }
    
    .table td::before {
        content: attr(data-label);
        position: absolute;
        left: 1rem;
        width: calc(50% - 1rem);
        padding-right: 1rem;
        text-align: left;
        font-weight: bold;
        color: var(--unicauca-azul);
    }
}

@media (max-width: 480px) {
    .container {
        max-width: 100%;
        padding: 5px;
    }
}
     /* Colores institucionales Universidad del Cauca */
:root {
  --unicauca-primary: #0056b3;  /* Azul principal */
  --unicauca-secondary: #6c757d; /* Gris para secundarios */
  --unicauca-success: #28a745;   /* Verde para acciones positivas */
  --unicauca-text: #333333;      /* Color de texto principal */
}

/* Estilos para botones */
.btn-unicauca-primary {
  background-color: var(--unicauca-primary);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.3s ease;
  display: inline-flex;
  align-items: center;
}

.btn-unicauca-primary:hover {
  background-color: #003d7a;
}

.btn-unicauca-secondary {
  background-color: var(--unicauca-secondary);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.3s ease;
  display: inline-flex;
  align-items: center;
}

.btn-unicauca-secondary:hover {
  background-color: #5a6268;
}

.btn-unicauca-success {
  background-color: var(--unicauca-success);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.3s ease;
  display: inline-flex;
  align-items: center;
}

.btn-unicauca-success:hover {
  background-color: #218838;
}

.text-unicauca-primary {
  color: var(--unicauca-primary);
  font-weight: 600;
}
     .btn-unicauca-light {
  background-color: #f8f9fa;
  color: #212529;
  border: 1px solid #dee2e6;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
}

.btn-unicauca-light:hover {
  background-color: #e2e6ea;
  border-color: #d1d7dc;
}
        .btn-unicauca-info {
  background-color: #17a2b8; /* Color azul claro/info */
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.3s ease;
  display: inline-flex;
  align-items: center;
}

.btn-unicauca-info:hover {
  background-color: #138496;
}
.btn-activo {
    border: 2px solid var(--unicauca-azul); /* Uses the main Unicauca blue for a strong border */
    background-color: var(--unicauca-azul-oscuro); /* Dark Unicauca blue background */
    font-weight: bold;
    color: white; /* White text for contrast */
    font-family: 'Open Sans', sans-serif; /* Set font to Open Sans */
    padding: 0.75rem 1.5rem; /* Consistent padding for original height */
    border-radius: 0.5rem; /* Slightly rounded corners for a modern look */
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* Smooth transitions for interaction */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Adds a subtle shadow for depth */
}

.btn-activo:hover {
    background-color: var(--unicauca-primary); /* Slightly lighter dark blue on hover for depth */
    color: white; /* Text remains white on hover */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); /* Shadow expands slightly on hover */
    cursor: pointer; /* Indicates it's clickable */
}

.btn-activo:active {
    background-color: var(--unicauca-azul-oscuro); /* Even darker blue when pressed */
    border-color: var(--unicauca-azul); /* Border stays the main blue */
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2); /* Inset shadow for a "pressed" effect */
    transform: translateY(1px); /* Slight downward shift when pressed */
}

.btn-activo:disabled {
    border-color: var(--unicauca-gray-medium); /* Lighter, subtle border for disabled state */
    background-color: var(--unicauca-light-gray-subtle); /* Very light gray background */
    color: var(--unicauca-gray-dark); /* Muted text color */
    cursor: not-allowed;
    opacity: 0.7;
    box-shadow: none;
}

</style>
    <!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

</head>
<body>

<div class="container-fluid" style="max-width: 1800px; margin: 0 auto; padding: 0 2rem;">


<?php
    // Semanas catedra
    $fecha_inicio = new DateTime($fecha_ini_cat);
$fecha_fin = new DateTime($fecha_fin_cat);
  $intervalo = $fecha_inicio->diff($fecha_fin);

// Obtener el total de días y convertir a semanas
$dias = $intervalo->days;
$semanas_cat = ceil($dias / 7); // redondea hacia arriba
// Semanas ocasionales
$inicio_ocas = new DateTime($fecha_ini_ocas);
$fin_ocas = new DateTime($fecha_fin_ocas);
$dias_ocas = $inicio_ocas->diff($fin_ocas)->days;
$semanas_ocas = ceil($dias_ocas / 7);
// Ejecutar la consulta principal
$sql = "
    SELECT
        d.PK_DEPTO,
        f.NOMBREC_FAC AS facultad,
        d.NOMBRE_DEPTO_CORT AS departamento,
        t.tipo_docente AS tipo,
        dp.dp_analisis,
        dp.dp_devolucion,
        dp.dp_visado,

        -- Periodo actual ($anio_semestre)
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) AS total_actual,
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_tc ELSE 0 END) AS TC_actual,
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_mt ELSE 0 END) AS MT_actual,
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_horas ELSE 0 END) AS horas_periodo,
        CASE 
            WHEN SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) = 0 THEN 0
            ELSE SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_puntos ELSE 0 END) / 
                 SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END)
        END AS puntos_actual,

        -- Periodo anterior ($anio_semestre_anterior)
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) AS total_anterior,
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_tc ELSE 0 END) AS TC_anterior,
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_mt ELSE 0 END) AS MT_anterior,
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_horas ELSE 0 END) AS horas_anterior,
        CASE 
            WHEN SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) = 0 THEN 0
            ELSE SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_puntos ELSE 0 END) / 
                 SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END)
        END AS puntos_anterior,

        -- Diferencias
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) -
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) AS dif_total,

        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_tc ELSE 0 END) -
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_tc ELSE 0 END) AS dif_tc,

        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_mt ELSE 0 END) -
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_mt ELSE 0 END) AS dif_mt,

        SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_horas ELSE 0 END) -
        SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_horas ELSE 0 END) AS dif_horas,

        CASE 
            WHEN SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) = 0 THEN 0
            ELSE SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_puntos ELSE 0 END) / 
                 SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END)
        END -
        CASE 
            WHEN SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) = 0 THEN 0
            ELSE SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_puntos ELSE 0 END) / 
                 SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END)
        END AS dif_puntos

    FROM (
        SELECT
            anio_semestre,
            facultad_id,
            departamento_id,
            tipo_docente,
            COUNT(DISTINCT cedula) AS total_profesores,
            SUM(CASE
                    WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'TC' OR tipo_dedicacion_r = 'TC') THEN 1
                    ELSE 0
                END) AS total_tc,
            SUM(CASE
                    WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'MT' OR tipo_dedicacion_r = 'MT') THEN 1
                    ELSE 0
                END) AS total_mt,
            SUM(CASE
                    WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'TC' OR tipo_dedicacion_r = 'TC') THEN 40
                    WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'MT' OR tipo_dedicacion_r = 'MT') THEN 20
                    WHEN tipo_docente = 'Catedra' THEN COALESCE(horas, 0) + COALESCE(horas_r, 0)
                    ELSE 0
                END) AS total_horas,
            SUM(COALESCE(puntos, 0)) AS total_puntos
        FROM solicitudes
        WHERE anio_semestre IN ('$anio_semestre', '$anio_semestre_anterior')
          AND (estado IS NULL OR estado != 'an')
        GROUP BY anio_semestre, facultad_id, departamento_id, tipo_docente
    ) AS t

    JOIN deparmanentos d ON d.PK_DEPTO = t.departamento_id
    JOIN facultad f ON f.PK_FAC = d.FK_FAC
    LEFT JOIN depto_periodo dp
        ON dp.fk_depto_dp = t.departamento_id
        AND dp.periodo = '$anio_semestre'

    $where

    GROUP BY t.facultad_id, t.departamento_id, t.tipo_docente, dp.dp_analisis, dp.dp_devolucion, dp.dp_visado
    ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.tipo_docente
";


$resultado = $conn->query($sql);
if ($resultado && $resultado->num_rows > 0) {
echo '<div class="d-flex justify-content-between align-items-center mb-4">';
echo '<div class="d-flex align-items-center">';
echo '<h3 class="mb-0 mr-3 text-unicauca-primary">Comparativo ' . htmlspecialchars($anio_semestre) . ' vs ' . htmlspecialchars($anio_semestre_anterior) . '</h3>';
echo '</div>';
echo '<div class="d-flex align-items-center gap-3">';

// Obtener el nombre del script actual
$current_page = basename($_SERVER['PHP_SELF']);

// Función para determinar si está activo
function botonActivo($archivo) {
    global $current_page;
    return $current_page === $archivo ? ' btn-activo' : '';
}

// Botón Comparativo Tradicional
echo '<form action="report_depto_comparativo.php" method="GET" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<button type="submit" class="btn-unicauca-light px-4' . botonActivo('report_depto_comparativo.php') . '">';
echo '<i class="fas fa-file-alt mr-2"></i>Comparativo Tradicional';
echo '</button>';
echo '</form>';

// Botón Comparativo Espejo
echo '<form action="comparativo_espejo.php" method="GET" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<button type="submit" class="btn-unicauca-light px-4' . botonActivo('comparativo_espejo.php') . '">';
echo '<i class="fas fa-copy mr-2"></i>Comparativo Espejo';
echo '</button>';
echo '</form>';

if ($tipo_usuario != '3') {
    // Botón Puntos y Costos
    echo '<form action="report_depto_comparativo_costos.php" method="GET" class="mb-0">';
    echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
    echo '<button type="submit" class="btn-unicauca-light px-4' . botonActivo('report_depto_comparativo_costos.php') . '">';
    echo '<i class="fas fa-calculator mr-2"></i>Puntos y Costos';
    echo '</button>';
    echo '</form>';

    // Botón Comparativo Costos Espejo
    echo '<form action="report_depto_comparativo_costos_espejo.php" method="GET" class="mb-0">';
    echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
    echo '<button type="submit" class="btn-unicauca-light px-4' . botonActivo('report_depto_comparativo_costos_espejo.php') . '">';
    echo '<i class="fas fa-exchange-alt mr-2"></i>Costos Espejo';
    echo '</button>';
    echo '</form>';

    // Botón Exportar Excel (NO se marca como activo)
    echo '<form action="excel_compartivo.php" method="POST" class="mb-0">';
    echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
    echo '<input type="hidden" name="anio_semestre_anterior" value="' . htmlspecialchars($anio_semestre_anterior) . '">';
    echo '<button type="submit" class="btn-unicauca-success px-4">';
    echo '<i class="fas fa-file-excel mr-2"></i>Exportar';
    echo '</button>';
    echo '</form>';
}

echo '</div>';

echo '</div>';
    echo "<div class='table-container'>";
    
echo "<table id='tablaComparativo' class='table table-bordered table-sm table-striped'>";
echo "<thead class='table-light'>
    <tr>
        <th colspan='3'></th>
        <th colspan='4' class='current-period' style='text-align: center;'>Periodo en revisión: {$anio_semestre}</th>
        <th colspan='4' class='previous-period' style='text-align: center;'>Periodo anterior: {$anio_semestre_anterior}</th>
        <th colspan='4' class='difference' style='text-align: center;'>Diferencia</th>";
    if ($tipo_usuario == 1) {
        echo "<th colspan='3' style='text-align: center;'>Acciones</th>";
    }
echo "</tr>
    <tr>
        <th style='font-size: 0.9em;'>Facultad</th>
        <th style='font-size: 0.9em;'>Departamento</th>
        <th style='font-size: 0.9em;'>Tipo</th>
        <th class='current-period' title='Total de profesores para el periodo {$anio_semestre}' style='text-align: center !important;'>Total</th>
        <th class='current-period' title='Cantidad de profesores de tiempo completo en el periodo {$anio_semestre}' style='text-align: center !important;'>TC</th>
        <th class='current-period' title='Cantidad de profesores de medio tiempo en el periodo {$anio_semestre}' style='text-align: center !important;'>MT</th>
        <th class='current-period' title='Total de horas asignadas en el periodo {$anio_semestre}' style='text-align: center !important;'>Horas</th>
        <!--
        <th class='current-period' title='Promedio de Puntos asignados en el periodo {$anio_semestre}' style='text-align: center !important;'>X&#x0305;.Ptos</th>
        -->
        <th class='previous-period' title='Total de profesores para el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>Total</th>
        <th class='previous-period' title='Cantidad de profesores de tiempo completo en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>TC</th>
        <th class='previous-period' title='Cantidad de profesores de medio tiempo en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>MT</th>
        <th class='previous-period' title='Total de horas asignadas en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>Horas</th>
        <!--
        <th class='previous-period' title='Promedio de Puntos asignados en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>X&#x0305;.Ptos</th>
        -->
        <th class='difference' title='Diferencia en el total de profesores entre {$anio_semestre} y {$anio_semestre_anterior}' style='text-align: center !important;'>Total</th>
        <th class='difference' title='Diferencia en cantidad de profesores de tiempo completo entre los dos periodos' style='text-align: center !important;'>TC</th>
        <th class='difference' title='Diferencia en cantidad de profesores de medio tiempo entre los dos periodos' style='text-align: center !important;'>MT</th>
        <th class='difference' title='Diferencia en horas asignadas entre los dos periodos' style='text-align: center !important;'>Horas</th>
        <!--
        <th class='difference' title='Diferencia en  promedio puntos asignados entre los dos periodos' style='text-align: center !important;'>X&#x0305;.Ptos</th>
        -->";


    // Solo mostrar columnas si usuario es tipo 1
if ($tipo_usuario == 1) {
    echo "<th style='font-size: 0.9em;'>Nota</th>
          <th style='font-size: 0.9em;'>Devolución</th>
          <th style='font-size: 0.9em; width: 80px;'>Visado</th>";
}
echo "      
    </tr>
</thead><tbody>";

$analisis_mostrado_depto = []; // Arreglo para rastrear los departamentos mostrados

while ($row = $resultado->fetch_assoc()) {
    $departamento_periodo = $row['PK_DEPTO'] . '_' . $anio_semestre;

    $esCatedra = ($row['tipo'] === 'Catedra');

    $tc_actual     = $esCatedra ? "" : $row['TC_actual'];
    $mt_actual     = $esCatedra ? "" : $row['MT_actual'];
    $tc_anterior   = $esCatedra ? "" : $row['TC_anterior'];
    $mt_anterior   = $esCatedra ? "" : $row['MT_anterior'];
    $dif_tc        = $esCatedra ? "" : $row['dif_tc'];
    $dif_mt        = $esCatedra ? "" : $row['dif_mt'];
    $dif_total     = $row['dif_total'];

    // Definir la clase de color dependiendo de las condiciones
    if ($row['dif_total'] > 0) {
        $textoColor = 'positive-difference'; // Rojo
    } elseif ($row['dif_horas'] > 0) {
        $textoColor = 'positive-differenceb'; // Naranja
    } else {
        $textoColor = ''; // Sin color especial
    }

    // Mostrar el formulario de análisis solo si no se ha mostrado para este departamento
    $nota_celda = "";$devolucion_celda = "";$visado_celda = "";
    if (!isset($analisis_mostrado_depto[$departamento_periodo])) {
        $mostrar_analisis = $row['dp_analisis'];
            $mostrar_devolucion = $row['dp_devolucion'] ?? '';
            $mostrar_visado = $row['dp_visado'] ?? 0;
$disabled = ($mostrar_visado == 1) ? 'disabled' : '';


       // Formulario de análisis con redimensionamiento manual
$nota_celda = "
    <td class='align-middle'>
        <form action='guardar_analisis.php' method='POST' class='d-flex align-items-start'>
            <input type='hidden' name='departamento_id' value='".htmlspecialchars($row['PK_DEPTO'])."'>
            <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
            <input type='hidden' name='tipo' value='analisis'>
            <div class='flex-grow-1 me-2 position-relative'>
                <textarea name='dp_analisis' class='form-control form-control-sm' 
                          rows='1' style='min-width: 160px; min-height: 24px;  height: auto;'
                          placeholder='Escriba su nota aquí' $disabled>".htmlspecialchars($mostrar_analisis)."</textarea>
            </div>
            <button type='submit' class='btn btn-sm btn-primary align-self-center' $disabled>
                <i class='fas fa-save'></i>
            </button>
        </form>
    </td>";

$devolucion_celda = "
    <td class='align-middle'>
        <form action='guardar_analisis.php' method='POST' class='form-inline d-flex align-items-center'>
            <input type='hidden' name='departamento_id' value='".htmlspecialchars($row['PK_DEPTO'])."'>
            <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
            <input type='hidden' name='tipo' value='devolucion'>
            <div class='input-group' style='width: 120px;'>
                <select name='dp_devolucion' class='form-select form-select-sm' $disabled>
                    <option value=''".($mostrar_devolucion == '' ? ' selected' : '').">-- Seleccione --</option>
                    <option value='Dedic_vinculacion'".($mostrar_devolucion == 'Dedic_vinculacion' ? ' selected' : '').">Dedic/Vinc.</option>
                    <option value='Soportes'".($mostrar_devolucion == 'Soportes' ? ' selected' : '').">Soportes</option>
                    <option value='Ambos'".($mostrar_devolucion == 'Ambos' ? ' selected' : '').">Ambos (Dedic y Soportes)</option>
                </select>
            </div>
            <button type='submit' class='btn btn-sm btn-success ms-2' $disabled>
                <i class='fas fa-check'></i>
            </button>
        </form>
    </td>";

         // Formulario de visado (checkbox automático)
    $visado_celda = "
    <td class='align-middle text-center'>
        <form action='guardar_analisis.php' method='POST'>
            <input type='hidden' name='departamento_id' value='".htmlspecialchars($row['PK_DEPTO'])."'>
            <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
            <input type='hidden' name='tipo' value='visado'>
            <div class='form-check form-switch d-inline-block'>
                <input type='checkbox' class='form-check-input' name='dp_visado' value='1' 
                       ".($mostrar_visado ? 'checked' : '')."
                       onchange='this.form.submit()'>
            </div>
        </form>
    </td>";
        $analisis_mostrado_depto[$departamento_periodo] = true;
    } else {
        $nota_celda = "<td></td>"; // Celda vacía si ya se mostró
        $devolucion_celda = "<td></td>";
    $visado_celda = "<td></td>";

    }


 echo "<tr>
    <td style='font-size: 0.9em;'>{$row['facultad']}</td>
    <td class='$textoColor' style='text-align: left; font-size: 0.9em; position: relative;'>
        <form action='depto_comparativo.php' method='POST' class='d-inline'>
            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['PK_DEPTO']) . "'>
            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
            <input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($anio_semestre_anterior) . "'>
            <button type='submit' 
                    class='departamento-link' 
                    style='background: none; 
                           border: none; 
                           cursor: pointer; 
                           padding: 0; 
                           text-align: left; 
                           color: inherit;
                           position: relative;
                           transition: all 0.2s;'>
                " . htmlspecialchars($row['departamento']) . "
                <span class='badge bg-primary bg-opacity-10 text-primary ms-2' 
                      style='font-size: 0.7em; 
                             position: absolute;
                             right: -25px;
                             top: 50%;
                             transform: translateY(-50%);
                             opacity: 0;
                             transition: opacity 0.3s;'>
                    Ver →
                </span>
            </button>
        </form>
    </td>
    <td class='$textoColor' style='font-size: 0.9em;'>{$row['tipo']}</td>

    <!-- Periodo actual -->
    <td class='current-period' style='text-align: center !important; vertical-align: middle !important;'>{$row['total_actual']}</td>
    <td class='current-period' style='text-align: center !important; vertical-align: middle !important;'>{$tc_actual}</td>
    <td class='current-period' style='text-align: center !important; vertical-align: middle !important;'>{$mt_actual}</td>
    <td class='current-period' style='text-align: center !important; vertical-align: middle !important;'>{$row['horas_periodo']}</td>
    <!-- <td class='current-period' style='text-align: center !important; vertical-align: middle !important;'>".number_format($row['puntos_actual'], 2)."</td> -->

    <!-- Periodo anterior -->
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$row['total_anterior']}</td>
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$tc_anterior}</td>
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$mt_anterior}</td>
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$row['horas_anterior']}</td>
    <!-- <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>".number_format($row['puntos_anterior'], 2)."</td> -->

    <!-- Diferencia -->
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$row['dif_total']}</td>
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$dif_tc}</td>
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$dif_mt}</td>
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$row['dif_horas']}</td>
    <!-- <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>" . number_format($row['dif_puntos'], 2) . "</td> -->";

    
if ($tipo_usuario == 1) {
    echo "{$nota_celda}{$devolucion_celda}{$visado_celda}";

}

echo "</tr>";
}
echo "</tbody></table></div>";
    
     echo "
<script>
$(document).ready(function() {
    $('#tablaComparativo').DataTable({
        \"pageLength\": 100,
        \"lengthMenu\": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, \"Todos\"] ],
        \"stateSave\": true,
        \"fixedHeader\": { // Configuración para headers fijos
            \"header\": true,
            \"headerOffset\": $('#navbar').outerHeight() // Ajusta si tienes navbar fijo
        },
        \"scrollY\": \"70vh\", // Altura scrollable
        \"scrollCollapse\": true,
        \"language\": {
            \"lengthMenu\": \"Mostrar _MENU_ registros por página\",
            \"zeroRecords\": \"No se encontraron resultados\",
            \"info\": \"Mostrando página _PAGE_ de _PAGES_\",
            \"infoEmpty\": \"No hay registros disponibles\",
            \"infoFiltered\": \"(filtrado de _MAX_ registros totales)\",
            \"search\": \"Buscar:\",
            \"paginate\": {
                \"next\": \"Siguiente\",
                \"previous\": \"Anterior\"
            }
        },
        \"dom\": '<\"top\"lf>rt<\"bottom\"ip>', // Mejor organización de controles
        \"initComplete\": function() {
            // Ajuste adicional para headers
            this.api().columns.adjust().fixedHeader.relayout();
        }
    });
    
});
</script>";

} else {
    echo "<div class='alert alert-warning'>No se encontraron datos para mostrar.</div>";
}
?>
 <div class="table-notes">
        <p class="note"><span class="color-indicator red"></span> <strong>Resaltado en rojo:</strong> Indica un incremento en el número de profesores respecto al periodo anterior.</p>
        <p class="note"><span class="color-indicator orange"></span> <strong>Resaltado en naranja:</strong> Indica un incremento en las horas asignadas respecto al periodo anterior.</p>
        <p class="note"><span class="color-indicator black"></span> <strong>Sin resaltado:</strong> No hay cambios significativos respecto al periodo anterior.</p>
    </div>
     <p class='mb-0 text-muted'><small>Pase el cursor sobre el nombre del departamento para ver opciones de detalle</small></p>
</div> <!-- cierre container -->
    <!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

</body>
</html>

<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>
