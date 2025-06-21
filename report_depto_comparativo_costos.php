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


$consultaperant = "SELECT * FROM periodo where periodo.nombre_periodo ='$anio_semestre_anterior'";
$resultadoperant = $conn->query($consultaperant);
while ($rowper = $resultadoperant->fetch_assoc()) {
    $fecha_ini_catant = $rowper['inicio_sem'];
    $fecha_fin_catant = $rowper['fin_sem'];
    $fecha_ini_ocasant = $rowper['inicio_sem_oc'];
    $fecha_fin_ocasant = $rowper['fin_sem_oc'];
    $valor_puntoant = $rowper['valor_punto'];
   
}

if ($tipo_usuario != '1') {
        $where = " WHERE PK_FAC = '$pk_fac'";
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
     
     #tablaComparativo td {
  white-space: nowrap;
}

/* Estilos para valores positivos/negativos */
.text-success { color: #28a745; }
.text-danger { color: #dc3545; }

/* Mejorar el header fijo */
.fixedHeader-floating {
  top: 60px !important; /* Ajustar según tu navbar */
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
    
     // Semanas catedra anterior
    $fecha_inicioant = new DateTime($fecha_ini_catant);
$fecha_finant = new DateTime($fecha_fin_catant);
  $intervaloant = $fecha_inicioant->diff($fecha_finant);

// Obtener el total de días y convertir a semanas
$diasant = $intervaloant->days;
$semanas_catant = ceil($diasant / 7); // redondea hacia arriba
// Semanas ocasionales
$inicio_ocasant = new DateTime($fecha_ini_ocasant);
$fin_ocasant = new DateTime($fecha_fin_ocasant);
$dias_ocasant = $inicio_ocasant->diff($fin_ocasant)->days;
$semanas_ocasant = ceil($dias_ocasant / 7);
    
    
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
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_puntos ELSE 0 END) AS puntos_actual,

    -- Periodo anterior ($anio_semestre_anterior)
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) AS total_anterior,
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_tc ELSE 0 END) AS TC_anterior,
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_mt ELSE 0 END) AS MT_anterior,
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_horas ELSE 0 END) AS horas_anterior,
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_puntos ELSE 0 END) AS puntos_anterior,

    -- Diferencias
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) -
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) AS dif_total,

    SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_tc ELSE 0 END) -
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_tc ELSE 0 END) AS dif_tc,

    SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_mt ELSE 0 END) -
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_mt ELSE 0 END) AS dif_mt,

    SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_horas ELSE 0 END) -
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_horas ELSE 0 END) AS dif_horas,
    
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_puntos ELSE 0 END) - 
    SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_puntos ELSE 0 END) AS dif_puntos

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
        SUM(CASE
                WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'TC' OR tipo_dedicacion_r = 'TC') THEN COALESCE(puntos, 0)
                WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'MT' OR tipo_dedicacion_r = 'MT') THEN COALESCE(puntos, 0) / 2
                WHEN tipo_docente = 'Catedra' THEN COALESCE(puntos, 0)
                ELSE 0
            END) AS total_puntos
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

// Botón Comparativo Tradicional (Nuevo)
echo '<form action="report_depto_comparativo.php" method="GET" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<input type="hidden" name="anio_semestre_anterior" value="' . htmlspecialchars($anio_semestre_anterior) . '">';
echo '<button type="submit" class="btn-unicauca-light px-4">';
echo '<i class="fas fa-file-alt mr-2"></i>Comparativo Tradicional';
echo '</button>';
echo '</form>';

// Botón Comparativo Espejo
echo '<form action="comparativo_espejo.php" method="GET" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<button type="submit" class="btn-unicauca-secondary px-4">';
echo '<i class="fas fa-copy mr-2"></i>Comparativo Espejo';
echo '</button>';
echo '</form>';

// Botón Puntos y Costos
echo '<form action="report_depto_comparativo_costos.php" method="GET" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<button type="submit" class="btn-unicauca-primary px-4">';
echo '<i class="fas fa-calculator mr-2"></i>Puntos y Costos';
echo '</button>';
echo '</form>';

// Nuevo Botón Comparativo Costos Espejo
echo '<form action="report_depto_comparativo_costos_espejo.php" method="GET" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<button type="submit" class="btn-unicauca-info px-4">';
echo '<i class="fas fa-exchange-alt mr-2"></i>Costos Espejo';
echo '</button>';
echo '</form>';

// Botón Exportar Excel
echo '<form action="excel_compartivo.php" method="POST" class="mb-0">';
echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
echo '<input type="hidden" name="anio_semestre_anterior" value="' . htmlspecialchars($anio_semestre_anterior) . '">';
echo '<button type="submit" class="btn-unicauca-success px-4">';
echo '<i class="fas fa-file-excel mr-2"></i>Exportar';
echo '</button>';
echo '</form>';

echo '</div>';
echo '</div>';
    echo "<div class='table-container'>";
    
echo "<table id='tablaComparativo' class='table table-bordered table-sm table-striped'>";
echo "<thead class='table-light'>
    <tr>
        <th colspan='3'></th>
        <th colspan='6' class='current-period' style='text-align: center;'>Periodo en revisión: {$anio_semestre}</th>
        <th colspan='6' class='previous-period' style='text-align: center;'>Periodo anterior: {$anio_semestre_anterior}</th>
        <th colspan='6' class='difference' style='text-align: center;'>Diferencia</th>";
   
echo "</tr>
    <tr>
        <th style='font-size: 0.9em;'>Facultad</th>
        <th style='font-size: 0.9em;'>Departamento</th>
        <th style='font-size: 0.9em;'>Tipo</th>
        <th class='current-period' title='Total de profesores para el periodo {$anio_semestre}' style='text-align: center !important;'>Total</th>
        <th class='current-period' title='Cantidad de profesores de tiempo completo en el periodo {$anio_semestre}' style='text-align: center !important;'>TC</th>
        <th class='current-period' title='Cantidad de profesores de medio tiempo en el periodo {$anio_semestre}' style='text-align: center !important;'>MT</th>
        <th class='current-period' title='Total de horas asignadas en el periodo {$anio_semestre}' style='text-align: center !important;'>Horas</th>
        
        <th class='current-period' title='Promedio de Puntos asignados en el periodo {$anio_semestre}' style='text-align: center !important;'>X&#x0305;.Ptos</th>
            <th class='current-period' title='Promedio de Puntos asignados en el periodo {$anio_semestre}' style='text-align: center !important;'>\$Proyect.</th>

        <th class='previous-period' title='Total de profesores para el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>Total</th>
        <th class='previous-period' title='Cantidad de profesores de tiempo completo en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>TC</th>
        <th class='previous-period' title='Cantidad de profesores de medio tiempo en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>MT</th>
        <th class='previous-period' title='Total de horas asignadas en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>Horas</th>
        
        <th class='previous-period' title='Promedio de Puntos asignados en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>X&#x0305;.Ptos</th>
        
        
                <th class='previous-period' title='Promedio de Puntos asignados en el periodo {$anio_semestre_anterior}' style='text-align: center !important;'>\$Proyect</th>


        <th class='difference' title='Diferencia en el total de profesores entre {$anio_semestre} y {$anio_semestre_anterior}' style='text-align: center !important;'>Total</th>
        <th class='difference' title='Diferencia en cantidad de profesores de tiempo completo entre los dos periodos' style='text-align: center !important;'>TC</th>
        <th class='difference' title='Diferencia en cantidad de profesores de medio tiempo entre los dos periodos' style='text-align: center !important;'>MT</th>
        <th class='difference' title='Diferencia en horas asignadas entre los dos periodos' style='text-align: center !important;'>Horas</th>
        
        <th class='difference' title='Diferencia en  promedio puntos asignados entre los dos periodos' style='text-align: center !important;'>X&#x0305;.Ptos</th>
        
           <th class='difference' title='Diferencia en  promedio puntos asignados entre los dos periodos' style='text-align: center !important;'>\$Proyect</th>
        ";



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

  


 echo "<tr>
    <td style='font-size: 0.9em;'>{$row['facultad']}</td>
    <td class='$textoColor' style='text-align: left; font-size: 0.9em; position: relative;'>
        <form action='depto_comparativo.php' method='POST' class='d-inline'>
            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['PK_DEPTO']) . "'>
            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>   
                    <input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($anio_semestre_anterior) . "'>

            <input type='hidden' name='envia' value='rcc'>

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
     <td class='current-period' style='text-align: center !important; vertical-align: middle !important;'>".number_format($row['puntos_actual'], 2)."</td>";
// Primero verificamos si es cátedra u ocasional
if ($esCatedra) {
    // Cálculo para cátedra (lo que ya teníamos)
   // Cálculo para cátedra (lo que ya teníamos)
if (!empty($row['total_actual']) && $row['total_actual'] != 0) {
    $valor_base = $row['horas_periodo'] * $row['puntos_actual'] / $row['total_actual'] * $semanas_cat * $valor_punto;
} else {
    $valor_base = 0; // O el valor que tenga sentido en tu lógica de negocio
}
    $escalas = [
        ['limite' => 2000000, 'multiplicador' => 2.2],
        ['limite' => 2200000, 'multiplicador' => 1.91],
        ['limite' => 2900000, 'multiplicador' => 1.75],
        ['limite' => 3200000, 'multiplicador' => 1.7],
        ['limite' => 3500000, 'multiplicador' => 1.66],
        ['limite' => 3750000, 'multiplicador' => 1.61],
        ['limite' => 4250000, 'multiplicador' => 1.59],
        ['limite' => PHP_FLOAT_MAX, 'multiplicador' => 1.55]
    ];

    $valor_final = $valor_base * 1.55; // Valor por defecto

    foreach ($escalas as $escala) {
        if ($valor_base < $escala['limite']) {
            $valor_final = $valor_base * $escala['multiplicador'];
            break;
        }
    }
} else {
    // Cálculo para ocasionales
    $valor_final = ($valor_punto * $row['puntos_actual']) * 1.571 *
                   $dias_ocas / 30;
}
    $valor_actual=$valor_final; 

// Formateo común para ambos casos
$valor_en_millones = $valor_final / 1000000;
$valor_formateado = '$' . number_format($valor_en_millones, 2, ',', '.') . 'M';

// 1. Convertir a millones y redondear al entero más cercano (369.021.111 → 370)
$valor_en_millones = round($valor_final / 1000000);
$valor_formateado = $valor_en_millones . 'M';  // Ejemplo: 370M

// 2. Mostrar el resultado con tooltip (formato limpio y valor exacto en el hover)
echo "<td class='current-period' style='text-align: center !important; vertical-align: middle !important;'
     title='Valor exacto: $" . number_format($valor_final, 0, ',', '.') . 
     ($esCatedra ? " (Cátedra)" : " (Ocasional)") . "'>" . 
     $valor_formateado . 
     "</td>";
    
    
    echo "
    <!-- Periodo anterior -->
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$row['total_anterior']}</td>
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$tc_anterior}</td>
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$mt_anterior}</td>
    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>{$row['horas_anterior']}</td>
   

    <td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'>".number_format($row['puntos_anterior'], 2)."</td>";

// Primero verificamos si es cátedra u ocasional
if ($esCatedra) {
    // Cálculo para cátedra (lo que ya teníamos)
if (!empty($row['total_anterior']) && $row['total_anterior'] != 0) {
    $valor_base = $row['horas_anterior'] * $row['puntos_anterior'] / $row['total_anterior'] * $semanas_catant * $valor_puntoant;
} else {
    $valor_base = 0; // o lo que consideres apropiado en caso de total cero
}

    $escalas = [
        ['limite' => 2000000, 'multiplicador' => 2.2],
        ['limite' => 2200000, 'multiplicador' => 1.91],
        ['limite' => 2900000, 'multiplicador' => 1.75],
        ['limite' => 3200000, 'multiplicador' => 1.7],
        ['limite' => 3500000, 'multiplicador' => 1.66],
        ['limite' => 3750000, 'multiplicador' => 1.61],
        ['limite' => 4250000, 'multiplicador' => 1.59],
        ['limite' => PHP_FLOAT_MAX, 'multiplicador' => 1.55]
    ];

    $valor_final = $valor_base * 1.55; // Valor por defecto

    foreach ($escalas as $escala) {
        if ($valor_base < $escala['limite']) {
            $valor_final = $valor_base * $escala['multiplicador'];
            break;
        }
    }
} else {
    // Cálculo para ocasionales
    $valor_final = ($valor_puntoant * $row['puntos_anterior']) * 1.571 *
                   $dias_ocasant / 30;
}
    $valor_anterior=$valor_final; 

// Formateo común para ambos casos
$valor_en_millones = $valor_final / 1000000;
$valor_formateado = '$' . number_format($valor_en_millones, 2, ',', '.') . 'M';

// Mostramos el resultado con tooltip
$valor_en_millones = round($valor_final / 1000000);
$valor_formateado = $valor_en_millones . 'M';  // 370M (sin decimales)

// Mostrar el resultado con tooltip
echo "<td class='previous-period' style='text-align: center !important; vertical-align: middle !important;'
     title='Valor exacto: $" . number_format($valor_final, 0, ',', '.') . 
     ($esCatedra ? " (Cátedra)" : " (Ocasional)") . "'>" . 
     $valor_formateado . 
     "</td>";
    
    echo "   

    <!-- Diferencia -->
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$row['dif_total']}</td>
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$dif_tc}</td>
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$dif_mt}</td>
    <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>{$row['dif_horas']}</td>
 <td class='difference' style='text-align: center !important; vertical-align: middle !important;'>" . number_format($row['dif_puntos'], 2) . "</td>";
 $diferencia = $valor_actual - $valor_anterior;
$diferencia_millones = abs($diferencia) / 1000000;
$signo = ($diferencia > 0) ? '+' : (($diferencia < 0) ? '-' : '');
$color_clase = ($diferencia > 0) ? 'text-success' : (($diferencia < 0) ? 'text-danger' : '');

// 1. Calcular la diferencia en millones y redondear (ej: -3.780.000 → -4M)
$diferencia_millones_redondeada = round($diferencia / 1000000);

// 2. Formatear el signo (+/-) y el valor absoluto (para el tooltip)
$signo = ($diferencia >= 0) ? '+' : '-';
$valor_absoluto = abs($diferencia_millones_redondeada);

// 3. Mostrar la celda con tooltip
echo "<td class='diff-period $color_clase' 
     data-order='{$diferencia}'
     style='text-align: center !important; vertical-align: middle !important;'
     title='Diferencia exacta: {$signo}$" . number_format(abs($diferencia), 0, ',', '.') . "'>
     {$signo}$" . $valor_absoluto . "M
     </td>";
    echo "</tr>";

echo "</tr>";
}
echo "</tbody></table></div>";
    
     echo "
<script>
$(document).ready(function() {
    $('#tablaComparativo').DataTable({
        columnDefs: [
            {
                targets: [19], // Índice de la columna con valores en millones
                type: 'num', // Tipo numérico para ordenación
                render: function(data, type, row, meta) {
                    if (type === 'display') {
                        return data; // Mostrar el valor formateado tal cual
                    }
                    if (type === 'sort' || type === 'filter') {
                        // Extraer el valor numérico del atributo data-order
                        return $(data).data('order') || 0;
                    }
                    return data;
                }
            }
        ],
        pageLength: 100,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, \"Todos\"]],
        stateSave: true,
        fixedHeader: {
            header: true,
            headerOffset: $('#navbar').outerHeight()
        },
        scrollY: \"70vh\",
        scrollCollapse: true,
        language: {
            lengthMenu: \"Mostrar _MENU_ registros por página\",
            zeroRecords: \"No se encontraron resultados\",
            info: \"Mostrando página _PAGE_ de _PAGES_\",
            infoEmpty: \"No hay registros disponibles\",
            infoFiltered: \"(filtrado de _MAX_ registros totales)\",
            search: \"Buscar:\",
            paginate: {
                next: \"Siguiente\",
                previous: \"Anterior\"
            }
        },
        dom: '<\"top\"lf>rt<\"bottom\"ip>',
        initComplete: function() {
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
