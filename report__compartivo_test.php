<?php
require('include/headerz.php');
require 'funciones.php'; // Asegúrate de que este archivo contiene funciones como obtenerperiodo, etc.

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Validación de sesión
if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
              <a href='index.html'>inicie sesión</a>
          </span>";
    exit();
}

$nombre_sesion = $_SESSION['name'];

// Validar y capturar el anio_semestre actual
if (isset($_POST['anio_semestre'])) {
    $anio_semestre = $_POST['anio_semestre'];
} elseif (isset($_GET['anio_semestre'])) {
    $anio_semestre = $_GET['anio_semestre'];
} else {
    die("Error: El parámetro 'anio_semestre' es obligatorio.");
}
$anio_semestre_anterior_default = '0'; // Or whatever your actual default is

    /// Validate and capture the anio_semestre_anterior
if (isset($_GET['anio_semestre_anterior'])) { // Use GET directly as your form uses GET
    $anio_semestre_anterior = $_GET['anio_semestre_anterior'];
} else {
    // If not provided in GET, use the default
echo  "no suministra  año anteiror";}


// Capturar departamento_id si se envía (opcional)
$departamento_id_param = isset($_POST['departamento_id'])
    ? $_POST['departamento_id']
    : (isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null);




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


// Obtener lista de facultades (para el admin)
// Obtener lista de facultades
$facultades = [];
// Esta consulta debe ejecutarse para que cualquier tipo de usuario que necesite el nombre de la facultad por ID
// tenga acceso a la lista de mapeo ID -> Nombre
$query_facultades = "SELECT PK_FAC, nombre_fac_minb FROM facultad ORDER BY nombre_fac_minb";
$result_facultades = $conn->query($query_facultades);
if ($result_facultades) { // Asegúrate de que la consulta fue exitosa
    while ($row = $result_facultades->fetch_assoc()) {
        $facultades[$row['PK_FAC']] = $row['nombre_fac_minb'];
    }
} else {
    // Opcional: Manejar el error si la consulta falla
    error_log("Error al obtener facultades: " . $conn->error);
}


// Lógica para inicializar $anio_semestre actual
$anio_semestre = isset($_POST['anio_semestre'])
    ? $_POST['anio_semestre']
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

// Si es admin y se ha seleccionado una facultad
$facultad_seleccionada = null;
if ($tipo_usuario == 1 && isset($_GET['facultad_id']) && $_GET['facultad_id'] != '') { // MODIFIED: Added check for empty string
    $facultad_seleccionada = $_GET['facultad_id'];
    $pk_fac = $facultad_seleccionada; // Sobreescribimos para las consultas
} else if ($tipo_usuario == 1 && !isset($_GET['facultad_id'])) {
    // Si es admin y no se ha seleccionado facultad (o se seleccionó "General"), no aplicar filtro
    $pk_fac = null;
}


if ($tipo_usuario == '1') {
    // No specific WHERE clause needed for tipo_usuario 1 (admin/full access)
    // If a faculty is selected, the faculty_id parameter in the SQL query will handle it.
    // If "General" is selected (facultad_id is null or empty), it will return all faculties.
    $where = "";
} elseif ($tipo_usuario == '2') {
    // For tipo_usuario 2, filter by faculty
    $where = " WHERE f.PK_FAC = '$pk_fac'";
} elseif ($tipo_usuario == '3') {

        // Fallback: if departamento_id wasn't passed, use the department linked to the user session
        $where = " WHERE d.PK_DEPTO = '$depto_user'";
    }


// Obtener los parámetros de la URL/POST
$facultad_id = $pk_fac ?? null; // MODIFIED: Use $pk_fac determined above
$departamento_id = $depto_user;// Obtener el período anterior

$anio_semestre = $_GET['anio_semestre'];
$periodo_anterior = $anio_semestre_anterior?? null;
$origen = $_POST['origen'] ?? null;

// --- Verificación y obtención de datos del PERIODO ACTUAL ---
if (empty($anio_semestre)) {
    die("Error: El parámetro anio_semestre no fue proporcionado.");
}

$consultaper = "SELECT * FROM periodo WHERE nombre_periodo = ?";
$stmt_per = $conn->prepare($consultaper);
if (!$stmt_per) {
    die("Error al preparar la consulta de periodo actual: " . $conn->error);
}
$stmt_per->bind_param("s", $anio_semestre);
$stmt_per->execute();
$resultadoper = $stmt_per->get_result();

if ($resultadoper->num_rows === 0) {
    die("Error: No se encontraron datos para el periodo actual: " . htmlspecialchars($anio_semestre));
}

$rowper = $resultadoper->fetch_assoc();
$fecha_ini_cat = $rowper['inicio_sem'];
$fecha_fin_cat = $rowper['fin_sem'];
$fecha_ini_ocas = $rowper['inicio_sem_oc'];
$fecha_fin_ocas = $rowper['fin_sem_oc'];
$valor_punto = $rowper['valor_punto'];
$smlv = $rowper['smlv'];
$stmt_per->close();

// Calculo de días y semanas para PERIODO ACTUAL
$fecha_inicio_cat_dt = new DateTime($fecha_ini_cat);
$fecha_fin_cat_dt = new DateTime($fecha_fin_cat);
$dias_catedra = $fecha_inicio_cat_dt->diff($fecha_fin_cat_dt)->days - 1; // Tu lógica PHP
$semanas_catedra = ceil($dias_catedra / 7);

$inicio_ocas_dt = new DateTime($fecha_ini_ocas);
$fin_ocas_dt = new DateTime($fecha_fin_ocas);
$dias_ocasional = $inicio_ocas_dt->diff($fin_ocas_dt)->days - 2; // Tu lógica PHP
$semanas_ocasional = ceil($dias_ocasional / 7);
$meses_ocasional = intval($semanas_ocasional / 4.33) - 1; // Tu lógica PHP

// Constantes de porcentajes (hardcodeadas en tu PHP y SQL)
$porcentaje_arl = 0.522 / 100;
$porcentaje_caja = 4.0 / 100;
$porcentaje_icbf = 3.0 / 100;

// Ajustes finales de porcentaje (para el PERIODO ACTUAL - se mantienen en 0)
$ajuste_catedra = 0;
$ajuste_ocasional = 0;

// --- Verificación y obtención de datos del PERIODO ANTERIOR ---
$dias_catedra_ant = 0;
$semanas_catedra_ant = 0;
$dias_ocasional_ant = 0;
$semanas_ocasional_ant = 0;
$meses_ocasional_ant = 0;
$valor_punto_ant = 0;
$smlv_ant = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparativa de Periodos</title>

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    </head>
<body>
 <?php   
echo "<link href='https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap' rel='stylesheet'>";

// Custom Styles for the headers
echo "<style>
 .card-header-custom {
    border-bottom: none;
    padding: 0.6rem 1.25rem; /* Reducido el padding vertical (antes 1rem) y horizontal (antes 1.5rem) */
    font-weight: 500; /* Un poco menos grueso que 600 */
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    font-family: 'Open Sans', sans-serif;
    min-height: auto; /* Asegura que no tenga altura mínima forzada */
}

.card-header-custom h2 {
    color: white;
    margin-bottom: 0;
    font-size: 1.25rem; /* Tamaño mediano (~20px) - ajustable */
    line-height: 1.3; /* Menor interlineado para compactar */
    font-weight: 600; /* Puedes mantener este peso para el título */
}

.bg-unicauca-blue-dark {
    background-color: #003366 !important;
    margin: 0 0 15px 0 !important; /* Reducido el margen inferior */
}
/* Contenedor para las dos tarjetas de cada facultad */
.faculty-cards-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin-bottom: 30px;
    width: 100%;
    /* Aumenta el ancho máximo del contenedor para dar más espacio a las tarjetas */
    max-width: 1600px; /* Incrementado de 900px para permitir tarjetas más anchas */
    margin-left: auto;
    margin-right: auto;
}

/* Ajustes para las tarjetas individuales */
.card {
    /* Mantén tus estilos actuales para la tarjeta */
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box; /* Crucial para que padding y border no aumenten el tamaño */
    min-width: 350px; /* Aumenta el ancho mínimo para que no se compriman demasiado */

    
    flex: 1 1 calc(50% - 10px + 30%); /* O 1 1 585px;  Si el contenedor padre es 1200px y quieres 585px por tarjeta */
 
    flex: 1 1 calc(49% - 10px); /* Esto las hará más anchas que el 45% anterior, aprovechando el nuevo max-width del padre */
    max-width: calc(600px - 10px); /* Limita el ancho máximo para evitar que crezcan demasiado si solo hay una */

   
}

/* Media query para pantallas más pequeñas (opcional, pero recomendado) */
@media (max-width: 768px) {
    .faculty-cards-row {
        flex-direction: column; /* Apila las tarjetas verticalmente en pantallas pequeñas */
        align-items: center; /* Centra las tarjetas cuando están apiladas */
    }

    .card {
        width: 95%; /* Ocupa casi todo el ancho disponible en pantallas pequeñas */
        max-width: 400px; /* Limita el ancho máximo para móviles */
        flex: 0 0 95%; /* Asegura que la tarjeta tome casi todo el ancho en móviles */
    }
}
</style>";

echo '<style>
    /* Estilos generales */
    body {
        font-family: "Segoe UI", "Roboto", sans-serif;
        background-color: #f8fafc;
        color: #333;
        margin: 0;
        padding: 15px;
        font-size: 14px;
    }


    .unicauca-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
font-family: "Open Sans", sans-serif;
    }

    /* Encabezado premium */

    h2 {
        color: white;
        font-size: 1.8rem;
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }

    h3 {
        color: #0056b3;
        font-size: 1.4rem;
        margin: 25px 0 15px;
    }

 



   

    /* Secciones de gráficos */
    .chart-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
/* Estilos para cada caja de gráfica individual */
.chart-box {
    flex: 1; /* Permite que la caja crezca y ocupe el espacio disponible */
    /* CAMBIO AQUI: Ajusta los anchos para acomodar 3 elementos en fila */
    min-width: 30%; /* Para 3 elementos, aproximadamente 30% cada uno (30*3=90%) */
    max-width: 32%; /* Un poco más de margen para el gap */
    /* Si quieres que sean exactamente 3 en fila, podrías usar calc() */
    /* width: calc(33.33% - 14px); /* (14px = 2/3 del gap de 20px para distribuir equitativamente) */
    box-sizing: border-box; /* Incluye padding y borde en el cálculo del ancho */
    padding: 15px; /* Espaciado interno */
    background-color: #fff; /* Fondo blanco */
    border: 1px solid #ddd; /* Borde suave */
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Sombra ligera */
    text-align: center; /* Centra el título del gráfico */
}

/* Para pantallas más pequeñas, que las gráficas se apilen */
@media (max-width: 992px) { /* Ajusta el breakpoint si lo deseas, 992px es un buen estándar para tabletas */
    .chart-box {
        min-width: 45%; /* En pantallas medianas, que se muestren 2 por fila */
        max-width: 48%;
    }
}

@media (max-width: 768px) {
    .chart-box {
        min-width: 90%; /* En pantallas pequeñas, que cada gráfica ocupe casi todo el ancho */
        max-width: 100%;
    }
}
/* Estilos para el contenedor de las gráficas */
.chart-grid {
    display: flex;
    flex-wrap: wrap; /* Permite que los elementos se envuelvan a la siguiente línea si no caben */
    justify-content: center; /* Centra las gráficas horizontalmente si el espacio lo permite */
    gap: 20px; /* Espacio entre las gráficas */
    width: 100%; /* Asegura que el contenedor ocupe todo el ancho disponible */
    max-width: 1600px; /* Opcional: Define un ancho máximo para el contenedor si es muy grande */
    margin: 20px auto; /* Centra el contenedor completo en la página y añade margen superior/inferior */
}

/* Estilos para cada caja de gráfica individual (CONSOLIDADO y AJUSTADO) */
.chart-box {
    /* Utiliza calc() para distribuir el ancho de forma precisa */
    /* Para 3 columnas: (100% - 2 * gap) / 3 */
    width: calc((100% - (2 * 20px)) / 3);
    
    /* Asegúrate de que flex-grow y flex-shrink permitan el ajuste */
    flex-grow: 1; /* Permite que la caja crezca si hay espacio extra */
    flex-shrink: 1; /* Permite que la caja se encoja si es necesario (pero el width es la prioridad) */
    flex-basis: auto; 

    box-sizing: border-box; /* Incluye padding y borde en el cálculo del ancho */
    padding: 20px; /* Mantuve el padding de 20px que tenías en el segundo bloque */
    background-color: #fff; /* Fondo blanco */
    border: 1px solid #ddd; /* Borde suave */
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Sombra ligera (mantuve la del segundo bloque) */
    text-align: center; /* Centra el título del gráfico */
    height: 450px; /* Altura fija para las gráficas */
    position: relative; /* Si necesitas posicionar elementos internos de forma absoluta */
}

/* Media Query para pantallas medianas (ej. tabletas): 2 columnas */
@media (max-width: 992px) { /* Puedes ajustar este breakpoint si es necesario */
    .chart-box {
        /* Para 2 columnas: (100% - 1 * gap) / 2 */
        width: calc((100% - 20px) / 2);
    }
}

/* Media Query para pantallas pequeñas (ej. móviles): 1 columna */
@media (max-width: 768px) {
    .chart-box {
        width: 100%; /* Cada gráfica ocupa el ancho completo */
    }
}

.chart-grid {
    display: flex;
    flex-wrap: wrap; /* Importante para que los elementos se envuelvan a la siguiente línea */
    gap: 20px; /* Espacio entre los cuadros de los gráficos */
    justify-content: center; /* O space-around, space-between, etc. */
    align-items: flex-start; /* Alinea los elementos en la parte superior */
}

.chart-box {
    flex: 1 1 calc(33.333% - 20px); /* Para 3 columnas con 20px de gap */
    /* O si quieres que sean flexibles y se ajusten */
    min-width: 300px; /* Ancho mínimo antes de que se envuelvan */
    max-width: 400px; /* Ancho máximo */
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px; /* Espacio entre filas */
}

/* Estilos específicos para las tarjetas de participación dentro del chart-box */
.chart-box div[style*="display: flex; justify-content: space-around;"] {
    /* Puedes añadir estilos aquí si es necesario para el layout interno */
}
    .chart-title {
        text-align: center;
        font-weight: 600;
        color: #004d99;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }
 /* Contenedor principal para las tablas en línea */

/* Estilo para cada periodo (caja) */
.period-box {
    flex: 1; /* Ocupa igual espacio */
    min-width: 0; /* Permite que se ajuste correctamente */
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    padding: 15px;
    border: 1px solid #e0e0e0;
    overflow: hidden;
}




.info-label {
    font-weight: 500;
    color: #555;
}




/* Estilo para valores monetarios */
.currency {
    font-family: "Roboto Mono", monospace;
    white-space: nowrap;
}




/* CONTENEDOR PRINCIPAL - TABLAS EN LÍNEA */



/* ESTILO COMPACTO PARA TABLAS */



/* ENLACE DE DEPARTAMENTO - ESTILO CLARO */
.departamento-link {
    background: none;
    border: none;
    color: #1a73e8 !important; /* Azul destacado */
    text-decoration: underline !important;
    text-underline-offset: 3px;
    cursor: pointer;
    padding: 0;
    font: inherit;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s;
}

.departamento-link:hover {
    color: #0d62c9 !important;
    text-decoration: none !important;
    background-color: rgba(26, 115, 232, 0.05);
}

/* Indicador visual (manita + flecha) */
.departamento-link:hover::after {
    content: "→";
    margin-left: 4px;
    font-size: 0.9em;
}

/* PERIODO BOX - CONTENEDOR DE CADA TABLA */


/* Cabecera de periodo */
.period-header {
    background-color: #f8f9fa;
    padding: 8px 12px;
    font-weight: 600;

    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
    font-size: 1.1em;
}


/* TABLA CONTAINER - AJUSTE DE SCROLL */
.table-container {
    max-height: 400px; /* Altura máxima */
    overflow-y: auto; /* Scroll vertical si es necesario */
}

/* INFO GRID COMPACTO */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 10px;
    font-size: 0.82em;
}

.info-item {
    display: flex;
    justify-content: space-between;
}

</style>';
    echo "<style>
/* Variables de color institucionales Unicauca */
:root {
  --unicauca-blue: #003366;
  --unicauca-gold: #FFCC00;
  --unicauca-blue-light: #1a4d80; /* Azul más claro, usado para hovers/focus */
  --unicauca-gray-light: #f8f9fa; /* Fondo muy claro para elementos */
  --unicauca-gray-medium: #e9ecef; /* Gris para bordes y separadores */
  --unicauca-gray-dark: #dee2e6; /* Gris más oscuro para bordes */
  --unicauca-text: #212529; /* Color de texto principal */
  --unicauca-text-light: #6c757d; /* Color de texto secundario */
  --unicauca-success: #28a745; /* Verde para estados positivos */
  --unicauca-danger: #dc3545; /* Rojo para estados negativos */
  --unicauca-warning: #ffc107; /* Amarillo para advertencias */
}

/* --- Contenedor principal del selector de facultad --- */
/* Este div nuevo (selector-facultad-container) envuelve todo el formulario para darle espacio y sombra */
.selector-facultad-container {
  background-color: white;
  border-radius: 8px;
  padding: 12px 15px; /* Ajuste para un look más compacto y centrado */
  margin: 15px 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid var(--unicauca-gray-dark);
  display: flex; /* Usamos flex para centrar el formulario dentro de este contenedor */
  justify-content: center; /* Centra el formulario horizontalmente */
  align-items: center;
}

/* --- Formulario del selector de facultad (donde están todos los elementos en línea) --- */
.selector-facultad-form {
  display: flex; /* Crucial para la alineación en línea */
  align-items: center; /* Alinea verticalmente todos los elementos (label, select, button) */
  flex-wrap: wrap; /* Permite que los elementos se envuelvan en pantallas más pequeñas */
  gap: 15px; /* Espacio entre los elementos */
  justify-content: center; /* Centra los elementos cuando hay salto de línea */
}


.selector-facultad-form .selector-label {
    font-weight: 600;
    color: var(--unicauca-text);
    font-size: 0.95em; /* Un poco más grande para mejor legibilidad */
    /* No necesitamos márgenes adicionales si usamos 'gap' en el contenedor flex */
}

/* --- Selector de opciones (Dropdown) --- */
.selector-facultad-form select {
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid var(--unicauca-gray-medium);
  font-size: 0.95em; /* Tamaño de fuente consistente */
  min-width: 220px; /* Ajuste el ancho mínimo si es necesario */
  max-width: 300px;
  background-color: var(--unicauca-gray-light);
  color: var(--unicauca-text);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  flex-grow: 1; /* Permite que el select ocupe espacio disponible */
}

.selector-facultad-form select:focus {
  border-color: var(--unicauca-blue);
  box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.2);
  outline: none;
  background-color: white;
}


.selector-facultad-form button {
  padding: 8px 18px; /* Ajuste para un botón más compacto */
  background-color: var(--unicauca-blue);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.95em; /* Tamaño de fuente consistente */
  font-weight: 500;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.selector-facultad-form button:hover {
  background-color: var(--unicauca-blue-light);
  transform: translateY(-1px);
}

.selector-facultad-form button:active {
  background-color: var(--unicauca-blue);
  transform: translateY(0);
}

/* --- Headers (para el caso del usuario no-admin) --- */
.card-header-custom {
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    color: white;
    font-family: 'Open Sans', sans-serif; /* Fuente más legible */
    margin-bottom: 16px;
    border-radius: 6px; /* Bordes redondeados */
    justify-content: center; /* Centra el texto del encabezado */
    text-align: center; /* Alineación de texto si se rompe la línea */
}

.card-header-custom h2 {
    margin: 0;
    font-size: 1.2rem; /* Tamaño de fuente ligeramente más grande para el título */
    font-weight: 600;
    letter-spacing: 0.5px; /* Un poco de espaciado para mejor lectura */
}

/* Colores de fondo de Unicauca para headers */
.bg-unicauca-blue-dark { background-color: var(--unicauca-blue); } /* Usando la variable */
.bg-unicauca-blue-light { background-color: #006699; } /* Manteniendo este color específico si es distinto del blue-light general */


/* --- Media Queries (Responsividad) --- */
@media (max-width: 768px) {
  /* Ajustes generales para pantallas más pequeñas */
  .selector-facultad-form {
    flex-direction: column; /* Apila los elementos en pantallas pequeñas */
    align-items: stretch; /* Estira los elementos para ocupar todo el ancho */
    gap: 10px; /* Reducir el espacio entre elementos apilados */
  }
  
  .selector-facultad-form .selector-label {
      width: 100%; /* La etiqueta ocupa todo el ancho */
      text-align: center; /* Centra el texto de la etiqueta */
      margin-bottom: 5px; /* Espacio debajo de la etiqueta */
  }

  .selector-facultad-form select,
  .selector-facultad-form button {
    min-width: 100%; /* Ocupa el 100% del ancho disponible */
    max-width: 100%; /* Evita que crezcan demasiado */
  }

  /* Ajustes para headers en móviles */
  .card-header-custom h2 {
      font-size: 1rem; /* Reducir tamaño de fuente del título en móviles */
  }
}
</style>";
function get_previous_year_period($periodo) {
    list($anio, $semestre) = explode('-', $periodo);
    $anio_anterior = (int)$anio - 1;
    return $anio_anterior . '-' . $semestre;
}

// Variables para manejar el estado del comparativo (original vs. espejo)
$is_espejo_active = isset($_GET['espejo']) && $_GET['espejo'] == 'true'; // Verifica si el modo espejo está activo
$original_anio_semestre_anterior = $periodo_anterior; // Guarda el periodo anterior original

// Si el modo espejo está activo, el periodo anterior se recalcula
if ($is_espejo_active) {
    $periodo_anterior = get_previous_year_period($anio_semestre);
}
    
echo "<div class='unicauca-container' id='secciongraf'>";

// Define la URL base para los enlaces del botón "comparativo espejo/anterior"
$base_url_params = [
    'anio_semestre' => $anio_semestre,
    'anio_semestre_anterior' => $original_anio_semestre_anterior // Siempre usa el original para alternar
];
if (isset($_GET['facultad_id'])) {
    $base_url_params['facultad_id'] = htmlspecialchars($_GET['facultad_id']);
}

// Construye la URL para activar/desactivar el modo espejo
$toggle_espejo_url_params = $base_url_params;
if ($is_espejo_active) {
    // Si está en espejo, el próximo clic desactiva el espejo
    // No añadir 'espejo=true', o establecerlo a 'false' si se prefiere una URL más explícita
    // Para simplificar, simplemente no incluimos el parámetro 'espejo'
} else {
    // Si no está en espejo, el próximo clic activa el espejo
    $toggle_espejo_url_params['espejo'] = 'true';
}

$toggle_url = '?' . http_build_query($toggle_espejo_url_params);

// Determina el texto del botón
$button_text = $is_espejo_active ? "Comparativo Original" : "Comparativo Espejo";
$button_class = $is_espejo_active ? "btn-switch-original" : "btn-switch-espejo";

// Mostrar selector de facultad para admin (Tipo de Usuario 1)
if ($tipo_usuario == 1) {
    echo "<div class='selector-facultad-container'>";
    echo "<form method='get' action='' class='selector-facultad-form'>";
    echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
    echo "<input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($original_anio_semestre_anterior) . "'>"; // Se mantiene el original aquí

    // Si el modo espejo está activo para el admin, también lo envía en el formulario para persistencia
    if ($is_espejo_active) {
        echo "<input type='hidden' name='espejo' value='true'>";
    }

    echo "<label for='facultad_id' class='selector-label'>Seleccione una Facultad</label>";
    echo "<select name='facultad_id' id='facultad_id'>";
    echo "<option value=''>Ver General</option>";
    foreach ($facultades as $id => $nombre) {
        echo "<option value='$id'" . ($facultad_seleccionada == $id ? ' selected' : '') . ">" . htmlspecialchars($nombre) . "</option>";
    }
    echo "</select>";
    echo "<button type='submit' class='btn-primary'>Ver Reporte</button>";
    echo "</form>";

    // Botón de comparativo espejo/anterior para admin
    echo "<a href='" . htmlspecialchars($toggle_url) . "' class='btn-switch $button_class'>" . htmlspecialchars($button_text) . "</a>";

    echo "</div>"; // Cierre del nuevo contenedor
} else {
    // Mostrar encabezado para usuario no-admin (Tipo de Usuario 2)
    echo "<div id='secciongraf' class='comparison-header'>";
    echo "<h2 class='comparison-title'>Comparativo " . htmlspecialchars($anio_semestre) . " - " . htmlspecialchars($periodo_anterior) . "</h2>";


    echo "<a href='#vertablas' class='view-tables-btn'>";
    echo "<svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>";
    echo "<path d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'></path>";
    echo "<polyline points='9 22 9 12 15 12 15 22'></polyline>";
    echo "</svg> Ver tablas";
    echo "</a>";
    
    // Botón de comparativo espejo/anterior para no-admin
    echo "<a href='" . htmlspecialchars($toggle_url) . "' class='btn-switch $button_class'>" . htmlspecialchars($button_text) . "</a>";
    echo "</div>";
}
echo "</div>"; // cierre unicauca-container

echo "<style>
 
/* Variables de color institucionales Unicauca */
:root {
  --unicauca-blue: #003366;
  --unicauca-gold: #FFCC00;
  --unicauca-blue-light: #1a4d80;
  --unicauca-gray-light: #f8f9fa;
  --unicauca-gray-medium: #e9ecef;
  --unicauca-gray-dark: #dee2e6;
  --unicauca-text: #212529;
  --unicauca-text-light: #6c757d;
  --unicauca-success: #28a745;
  --unicauca-danger: #dc3545;
  --unicauca-warning: #ffc107;
}

/* --- Selectores existentes (sin cambios significativos) --- */
/* (Mantén aquí todos los estilos de compact-table, departamento-link, currency, diff-good/bad, table-container, etc.) */

/* --- Contenedor principal del selector de facultad para admin --- */
/* Este contenedor ahora alojará el formulario y el nuevo botón de alternancia */
.selector-facultad-container {
  background-color: white;
  border-radius: 8px;
  padding: 12px 15px;
  margin: 15px 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid var(--unicauca-gray-dark);
  display: flex;
  flex-direction: column; /* Apila el formulario y el botón de alternancia */
  gap: 15px; /* Espacio entre el formulario y el botón */
  align-items: center; /* Centra los elementos horizontalmente */
}

/* --- Formulario del selector de facultad (para admin) --- */
.selector-facultad-form {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  justify-content: center;
  width: 100%; /* Ocupa el 100% del ancho del contenedor padre */
}

/* --- Etiqueta del selector de facultad --- */
.selector-facultad-form .selector-label {
    font-weight: 600;
    color: var(--unicauca-text);
    font-size: 0.95em;
    white-space: nowrap; /* Evita que el label se rompa en varias líneas */
}

/* --- Select y botón primario dentro del formulario --- */
.selector-facultad-form select,
.selector-facultad-form input[type='text'] {
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid var(--unicauca-gray-medium);
  font-size: 0.95em;
  min-width: 220px;
  max-width: 300px;
  background-color: var(--unicauca-gray-light);
  color: var(--unicauca-text);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  flex-grow: 1;
}

.selector-facultad-form select:focus,
.selector-facultad-form input[type='text']:focus {
  border-color: var(--unicauca-blue);
  box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.2);
  outline: none;
  background-color: white;
}

.selector-facultad-form .btn-primary { /* Usamos una clase más genérica para el botón principal */
  padding: 8px 18px;
  background-color: var(--unicauca-blue);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.95em;
  font-weight: 500;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.selector-facultad-form .btn-primary:hover {
  background-color: var(--unicauca-blue-light);
  transform: translateY(-1px);
}

.selector-facultad-form .btn-primary:active {
  background-color: var(--unicauca-blue);
  transform: translateY(0);
}

/* --- Nuevo Botón de Alternancia (Comparativo Espejo/Original) --- */
.btn-switch {
  display: inline-flex;
  align-items: center;
  justify-content: center; /* Centra el texto dentro del botón */
  gap: 5px;
  padding: 8px 18px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 0.95em;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 180px; /* Ancho mínimo para que el texto quepa bien */
  
}

/* Estilos específicos para el botón Comparativo Espejo */
.btn-switch-espejo {
  background-color: var(--unicauca-gold); /* Dorado de Unicauca */
  color: var(--unicauca-blue); /* Texto azul oscuro */
  border: 1px solid var(--unicauca-gold); /* Borde sutil */
}

.btn-switch-espejo:hover {
  background-color: #e6b800; /* Un dorado un poco más oscuro al hover */
  border-color: #e6b800;
  transform: translateY(-1px);
}

/* Estilos específicos para el botón Comparativo Original */
.btn-switch-original {
  background-color: var(--unicauca-danger); /* Azul más claro de Unicauca */
  color: white;
  border: 1px solid var(--unicauca-danger);
}

.btn-switch-original:hover {
  background-color: var(--unicauca-blue); /* Azul oscuro al hover */
  border-color: var(--unicauca-blue);
  transform: translateY(-1px);
}


/* --- Encabezado mejorado con botón (para usuario no-admin) --- */
.comparison-header {
    background: linear-gradient(to right, #006699, #004d80); /* Mantiene tu gradiente original */
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between; /* Mueve el título y los botones a los extremos */
    flex-wrap: wrap; /* Permite que los elementos se envuelvan */
    gap: 10px; /* Espacio entre los elementos */
}

.comparison-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    flex-grow: 1; /* Permite que el título ocupe el espacio disponible */
}

.view-tables-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.view-tables-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.view-tables-btn svg {
    margin-bottom: 1px;
}

/* --- Responsive para pantallas pequeñas --- */
@media (max-width: 768px) {
  /* Selector de Facultad (Admin) */
  .selector-facultad-container {
    flex-direction: column; /* Apila el formulario y el botón de alternancia */
    gap: 10px;
    padding: 10px; /* Padding reducido para móviles */
  }

  .selector-facultad-form {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .selector-facultad-form .selector-label {
      width: 100%;
      text-align: center;
      margin-bottom: 0; /* Ya tenemos gap */
  }

  .selector-facultad-form select,
  .selector-facultad-form .btn-primary,
  .btn-switch { /* Ahora el botón switch también es 100% en móvil */
    min-width: 100%;
    max-width: 100%;
  }

  /* Encabezado (No-Admin) */
  .comparison-header {
      flex-direction: column; /* Apila título y botones */
      align-items: stretch; /* Estira elementos */
      text-align: center;
      padding: 0.8rem 1rem; /* Padding reducido */
      gap: 10px;
  }

  .comparison-title {
      font-size: 1rem;
  }

  .view-tables-btn,
  .btn-switch {
      width: 100%; /* Botones ocupan todo el ancho */
      justify-content: center; /* Centra el texto dentro del botón */
  }
}</style>";

if (!empty($periodo_anterior)) {
    $consultaperant = "SELECT * FROM periodo WHERE nombre_periodo = ?";
    $stmt_per_ant = $conn->prepare($consultaperant);
    if (!$stmt_per_ant) {
        die("Error al preparar la consulta de periodo anterior: " . $conn->error);
    }
    $stmt_per_ant->bind_param("s", $periodo_anterior);
    $stmt_per_ant->execute();
    $resultadoperant = $stmt_per_ant->get_result();

    if ($resultadoperant->num_rows > 0) {
        $rowperant = $resultadoperant->fetch_assoc();
        $fecha_ini_catant = $rowperant['inicio_sem'];
        $fecha_fin_catant = $rowperant['fin_sem'];
        $fecha_ini_ocasant = $rowperant['inicio_sem_oc'];
        $fecha_fin_ocasant = $rowperant['fin_sem_oc'];
        $valor_punto_ant = $rowperant['valor_punto'];
        $smlv_ant = $rowperant['smlv'];

        // Calculo de días y semanas para PERIODO ANTERIOR
        $fecha_inicio_cat_ant_dt = new DateTime($fecha_ini_catant);
        $fecha_fin_cat_ant_dt = new DateTime($fecha_fin_catant);
        $dias_catedra_ant = $fecha_inicio_cat_ant_dt->diff($fecha_fin_cat_ant_dt)->days - 1;
        $semanas_catedra_ant = ceil($dias_catedra_ant / 7);

        $inicio_ocas_ant_dt = new DateTime($fecha_ini_ocasant);
        $fin_ocas_ant_dt = new DateTime($fecha_fin_ocasant);
        $dias_ocasional_ant = $inicio_ocas_ant_dt->diff($fin_ocas_ant_dt)->days - 2;
        $semanas_ocasional_ant = ceil($dias_ocasional_ant / 7);
        $meses_ocasional_ant = intval($semanas_ocasional_ant / 4.33) - 1;
    }
    $stmt_per_ant->close();
}


// --- Consulta SQL Parametrizada ---
$sql_query = "
WITH ProfessorFinancials AS (
    SELECT
        s.cedula,
        s.facultad_id,
        s.departamento_id,
        s.tipo_docente,
        s.puntos,
        s.horas,
        s.horas_r,
        s.tipo_dedicacion,
        s.tipo_dedicacion_r,
        -- Parámetros dinámicos desde PHP
        ? AS valor_punto_dyn,
        ? AS smlv_dyn,
        ? AS dias_catedra_dyn,
        ? AS semanas_catedra_dyn,
        ? AS dias_ocasional_dyn,
        ? AS semanas_ocasional_dyn,
        ? AS meses_ocas_dyn,
        ? AS porcentaje_arl_dyn,
        ? AS porcentaje_caja_dyn,
        ? AS porcentaje_icbf_dyn,
        ? AS ajuste_catedra_dyn,
        ? AS ajuste_ocasional_dyn,
-- NUEVA LÓGICA PARA total_horas_calculado
        CASE
            WHEN s.tipo_docente = 'Catedra' THEN (COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0))
            WHEN s.tipo_docente = 'Ocasional' THEN
                CASE
                    WHEN s.tipo_dedicacion = 'TC' OR s.tipo_dedicacion_r = 'TC' THEN 40
                    WHEN s.tipo_dedicacion = 'MT' OR s.tipo_dedicacion_r = 'MT' THEN 20
                    ELSE 0
                END
            ELSE 0
        END AS horas_por_profesor_calc, -- Horas por cada profesor individual

        -- Paso 1: Calcular Asignacion_Mensual y Asignacion_Total por profesor
        CASE
            WHEN s.tipo_docente = 'Catedra' THEN
                (s.puntos * ? * (COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0)) * 4)
            WHEN s.tipo_docente = 'Ocasional' THEN
                (s.puntos * ? * (
                    CASE
                        WHEN s.tipo_dedicacion = 'MT' OR s.tipo_dedicacion_r = 'MT' THEN 20
                        WHEN s.tipo_dedicacion = 'TC' OR s.tipo_dedicacion_r = 'TC' THEN 40
                        ELSE 0
                    END
                ) / 40)
            ELSE 0
        END AS asignacion_mes_calc,

        CASE
            WHEN s.tipo_docente = 'Catedra' THEN
                s.puntos * ? * (COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0)) * ?
            WHEN s.tipo_docente = 'Ocasional' THEN
                ROUND(s.puntos * ? * (
                    CASE
                        WHEN s.tipo_dedicacion = 'MT' OR s.tipo_dedicacion_r = 'MT' THEN 20
                        WHEN s.tipo_dedicacion = 'TC' OR s.tipo_dedicacion_r = 'TC' THEN 40
                        ELSE 0
                    END
                ) / 40.0, 0) * (? / 30.0) -- Asignacion_mes * dias_ocasional / 30
            ELSE 0
        END AS asignacion_total_calc
    FROM
        solicitudes AS s
    WHERE
        s.anio_semestre = ?
        AND (s.estado <> 'an' OR s.estado IS NULL)
),
DetailedFinancials AS (
    SELECT
        pf.*,
        -- Paso 2: Calcular todos los componentes financieros detallados
        -- Prima Navidad
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN pf.asignacion_mes_calc * 3 / 12
            ELSE pf.asignacion_mes_calc * pf.meses_ocas_dyn / 12
        END AS prima_navidad_calc,

        -- Indemnización Vacaciones
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN pf.asignacion_mes_calc * pf.dias_catedra_dyn / 360
            ELSE pf.asignacion_mes_calc * pf.dias_ocasional_dyn / 360
        END AS indem_vacaciones_calc,

        -- Indemnización Prima Vacaciones
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN (pf.asignacion_mes_calc * pf.dias_catedra_dyn / 360) * 2 / 3
            ELSE (pf.asignacion_mes_calc * pf.dias_ocasional_dyn / 360) * 2 / 3
        END AS indem_prima_vacaciones_calc,

        -- Cesantías
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN (pf.asignacion_total_calc + (pf.asignacion_mes_calc * 3 / 12)) / 12
            ELSE ROUND((pf.asignacion_total_calc + (pf.asignacion_mes_calc * pf.meses_ocas_dyn / 12)) / 12)
        END AS cesantias_calc,

        -- EPS
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * 0.085
                        ELSE ROUND(pf.asignacion_total_calc * 0.085, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 8.5) / 100, 0)
            ELSE 0
        END AS eps_calc,

        -- Pensión (AFP)
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * 0.12
                        ELSE ROUND(pf.asignacion_total_calc * 0.12, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 12) / 100, 0)
            ELSE 0
        END AS afp_calc,

        -- ARL
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * pf.porcentaje_arl_dyn
                        ELSE ROUND(pf.asignacion_total_calc * pf.porcentaje_arl_dyn, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 0.522) / 100, -2)
            ELSE 0
        END AS arl_calc,

        -- Caja de Compensación (Comfaucaua)
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * pf.porcentaje_caja_dyn
                        ELSE ROUND(pf.asignacion_total_calc * pf.porcentaje_caja_dyn, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 4) / 100, -2)
            ELSE 0
        END AS cajacomp_calc,

        -- ICBF
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * pf.porcentaje_icbf_dyn
                        ELSE ROUND(pf.asignacion_total_calc * pf.porcentaje_icbf_dyn, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 3) / 100, -2)
            ELSE 0
        END AS icbf_calc
    FROM
        ProfessorFinancials pf
),
AggregatedTotals AS (
    SELECT
        f.nombre_fac_minb AS nombre_facultad,
        d.depto_nom_propio AS nombre_departamento,
        d.PK_DEPTO,
        d.FK_FAC,
        df.tipo_docente,
        COUNT(DISTINCT df.cedula) AS total_profesores,
        SUM(CASE WHEN df.tipo_docente = 'Ocasional' AND (df.tipo_dedicacion = 'TC' OR df.tipo_dedicacion_r = 'TC') THEN 1 ELSE 0 END) AS total_ocasional_tc,
        SUM(CASE WHEN df.tipo_docente = 'Ocasional' AND (df.tipo_dedicacion = 'MT' OR df.tipo_dedicacion_r = 'MT') THEN 1 ELSE 0 END) AS total_ocasional_mt,
        SUM(df.horas_por_profesor_calc) AS total_horas_agregadas, -- NUEVA COLUMNA AGREGADA
        SUM(df.asignacion_mes_calc) AS total_asignacion_mensual_agregada,
        SUM(df.asignacion_total_calc) AS total_asignacion_total_agregada,
        SUM(
            CASE
                WHEN df.tipo_docente = 'Catedra' THEN
                    df.asignacion_total_calc + df.prima_navidad_calc + df.indem_vacaciones_calc + df.indem_prima_vacaciones_calc + df.cesantias_calc + df.eps_calc + df.afp_calc + df.arl_calc + df.cajacomp_calc + df.icbf_calc
                WHEN df.tipo_docente = 'Ocasional' THEN
                    (df.asignacion_total_calc + df.prima_navidad_calc + df.indem_vacaciones_calc + df.indem_prima_vacaciones_calc) + -- Total Empleado
                    (df.cesantias_calc + df.eps_calc + df.afp_calc + df.arl_calc + df.cajacomp_calc + df.icbf_calc) -- Total Entidades
                ELSE 0
            END
        ) AS gran_total_sin_ajuste,
        df.ajuste_catedra_dyn,
        df.ajuste_ocasional_dyn
    FROM
        DetailedFinancials df
    JOIN
        deparmanentos AS d ON d.PK_DEPTO = df.departamento_id
    JOIN
        facultad AS f ON f.PK_FAC = df.facultad_id
    WHERE
        ( ? IS NULL OR df.facultad_id = ? )
        AND ( ? IS NULL OR df.departamento_id = ? )
    GROUP BY
        f.nombre_fac_minb,
        d.depto_nom_propio,
        df.tipo_docente,
        df.ajuste_catedra_dyn,
        df.ajuste_ocasional_dyn
)
SELECT
     ata.nombre_facultad,
    ata.nombre_departamento,
    ata.PK_DEPTO,
    ata.FK_FAC,
    ata.tipo_docente,
    ata.total_ocasional_tc,
    ata.total_ocasional_mt,
    ata.total_profesores,
    ata.total_horas_agregadas, -- NUEVA COLUMNA EN LA SELECCIÓN FINAL
    ata.total_asignacion_mensual_agregada,
    ata.total_asignacion_total_agregada,
    -- Aplicar el ajuste final al gran_total_sin_ajuste con los porcentajes corregidos
    CASE
        WHEN ata.tipo_docente = 'Catedra' THEN ata.gran_total_sin_ajuste * (1 + ata.ajuste_catedra_dyn)
        WHEN ata.tipo_docente = 'Ocasional' THEN ata.gran_total_sin_ajuste * (1 + ata.ajuste_ocasional_dyn)
        ELSE ata.gran_total_sin_ajuste
    END AS gran_total_ajustado
FROM
    AggregatedTotals ata
ORDER BY
    ata.nombre_facultad,
    ata.nombre_departamento,
    ata.tipo_docente;
";

// --- Preparar y ejecutar la consulta para el PERIODO ACTUAL ---
// MODIFIED: $facultad_id for the current query might be null if "General" is selected by admin.
// The SQL query handles NULL gracefully.
$stmt_current = $conn->prepare($sql_query);
if (!$stmt_current) {
    die("Error al preparar la consulta para el periodo actual: " . $conn->error);
}

$bind_params_current = [
    // Parámetros para las constantes dinámicas del periodo actual
    $valor_punto, $smlv, $dias_catedra, $semanas_catedra, $dias_ocasional,
    $semanas_ocasional, $meses_ocasional, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
    $ajuste_catedra, $ajuste_ocasional, // Estos son 0 para el periodo actual
    // Parámetros para asignacion_mes_calc
    $valor_punto, $valor_punto,
    // Parámetros para asignacion_total_calc
    $valor_punto, $semanas_catedra, $valor_punto, $dias_ocasional,
    // Periodo anio_semestre
    $anio_semestre,
    // Filtros de WHERE para AggregatedTotals (facultad y departamento)
    $facultad_id, $facultad_id, $departamento_id, $departamento_id
];

// String de tipos para bind_param (d = double/float, i = int, s = string)
// 18 'd's + 1 's' + 4 'i's = 23 parámetros en total
// MODIFIED: Corrected the type string length for dynamic parameters
$types_current = str_repeat('d', 12) . str_repeat('d', 6) . 's'; 
$types_current = str_repeat('d', 12) . str_repeat('d', 6) . 's' . 'ssss'; // 12 doubles, 6 doubles, 1 string (anio_semestre), 4 strings (facultad_id, facultad_id, departamento_id, departamento_id)

// Adjust bind_params_current for NULL values
// For the filters, if $facultad_id is null, both parameters passed for `? IS NULL OR df.facultad_id = ?` should be null.
$bind_params_current[19] = $facultad_id; // First facultad_id
$bind_params_current[20] = $facultad_id; // Second facultad_id
$bind_params_current[21] = $departamento_id; // First departamento_id
$bind_params_current[22] = $departamento_id; // Second departamento_id

$stmt_current->bind_param($types_current, ...$bind_params_current);
$stmt_current->execute();
$result_current_period = $stmt_current->get_result();

$data_current_period = [];
while ($row = $result_current_period->fetch_assoc()) {
    $data_current_period[] = $row;
}
$stmt_current->close();


// --- Preparar y ejecutar la consulta para el PERIODO ANTERIOR (si existe) ---
$data_previous_period = [];
if (!empty($periodo_anterior) && $valor_punto_ant > 0) { // Asegúrate de tener datos del periodo anterior
    $stmt_previous = $conn->prepare($sql_query); // Reutilizamos la misma consulta SQL
    if (!$stmt_previous) {
        die("Error al preparar la consulta para el periodo anterior: " . $conn->error);
    }

    // Definir ajustes específicos para el periodo anterior
    $ajuste_catedra_anterior = 0; // Mantener en 0 si no hay ajuste para Catedra en el periodo anterior
    $ajuste_ocasional_anterior = 0.018; // Aplicar el 1.7% para Ocasional en el periodo anterior (was -0.01732 in original, updated to 0.017 as per your provided snippet)

    $bind_params_previous = [
        // Parámetros para las constantes dinámicas del periodo anterior
        $valor_punto_ant, $smlv_ant, $dias_catedra_ant, $semanas_catedra_ant, $dias_ocasional_ant,
        $semanas_ocasional_ant, $meses_ocasional_ant, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
        $ajuste_catedra_anterior, $ajuste_ocasional_anterior, // <--- Aquí se pasan los ajustes específicos
        // Parámetros para asignacion_mes_calc
        $valor_punto_ant, $valor_punto_ant,
        // Parámetros para asignacion_total_calc
        $valor_punto_ant, $semanas_catedra_ant, $valor_punto_ant, $dias_ocasional_ant,
        // Periodo anio_semestre (¡este es el del periodo anterior!)
        $periodo_anterior,
        // Filtros de WHERE para AggregatedTotals (facultad y departamento)
        $facultad_id, $facultad_id, $departamento_id, $departamento_id // MODIFIED: Use $facultad_id from above logic
    ];

    // Los tipos de parámetros son los mismos que para la consulta actual
    $stmt_previous->bind_param($types_current, ...$bind_params_previous);
    $stmt_previous->execute();
    $result_previous_period = $stmt_previous->get_result();

    while ($row = $result_previous_period->fetch_assoc()) {
        $data_previous_period[] = $row;
    }
    $stmt_previous->close();
}
    
    
    
// Query para el PERIODO ACTUAL - SIN FILTROS DE FACULTAD/DEPTO
$stmt_global_current = $conn->prepare($sql_query);
if (!$stmt_global_current) {
    die("Error al preparar la consulta GLOBAL para el periodo actual: " . $conn->error);
}

$bind_params_global_current = [
    $valor_punto, $smlv, $dias_catedra, $semanas_catedra, $dias_ocasional,
    $semanas_ocasional, $meses_ocasional, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
    $ajuste_catedra, $ajuste_ocasional,
    $valor_punto, $valor_punto,
    $valor_punto, $semanas_catedra, $valor_punto, $dias_ocasional,
    $anio_semestre,
    null, null, null, null // <<-- ESTO ES CLAVE: Pasar NULL para ignorar los filtros de facultad/departamento
];
$stmt_global_current->bind_param($types_current, ...$bind_params_global_current);
$stmt_global_current->execute();
$result_global_current_period = $stmt_global_current->get_result();

$facultades_data_for_global_sum_actual = [];
while ($row = $result_global_current_period->fetch_assoc()) {
    $facultad_name = $row['nombre_facultad'];
    if (!isset($facultades_data_for_global_sum_actual[$facultad_name])) {
        $facultades_data_for_global_sum_actual[$facultad_name] = [
            'total_profesores_actual' => 0,
            'gran_total_ajustado_actual' => 0
        ];
    }
    $facultades_data_for_global_sum_actual[$facultad_name]['total_profesores_actual'] += $row['total_profesores'];
    $facultades_data_for_global_sum_actual[$facultad_name]['gran_total_ajustado_actual'] += $row['gran_total_ajustado'];
}
$stmt_global_current->close();

// Query para el PERIODO ANTERIOR - SIN FILTROS DE FACULTAD/DEPTO
$facultades_data_for_global_sum_anterior = [];
if (!empty($periodo_anterior) && $valor_punto_ant > 0) {
    $stmt_global_previous = $conn->prepare($sql_query);
    if (!$stmt_global_previous) {
        die("Error al preparar la consulta GLOBAL para el periodo anterior: " . $conn->error);
    }

    $ajuste_catedra_anterior = 0;
    $ajuste_ocasional_anterior = 0.018;

    $bind_params_global_previous = [
        $valor_punto_ant, $smlv_ant, $dias_catedra_ant, $semanas_catedra_ant, $dias_ocasional_ant,
        $semanas_ocasional_ant, $meses_ocasional_ant, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
        $ajuste_catedra_anterior, $ajuste_ocasional_anterior,
        $valor_punto_ant, $valor_punto_ant,
        $valor_punto_ant, $semanas_catedra_ant, $valor_punto_ant, $dias_ocasional_ant,
        $periodo_anterior,
        null, null, null, null // <<-- ESTO ES CLAVE: Pasar NULL para ignorar los filtros
    ];

    $stmt_global_previous->bind_param($types_current, ...$bind_params_global_previous);
    $stmt_global_previous->execute();
    $result_global_previous_period = $stmt_global_previous->get_result();

    while ($row = $result_global_previous_period->fetch_assoc()) {
        $facultad_name = $row['nombre_facultad'];
        if (!isset($facultades_data_for_global_sum_anterior[$facultad_name])) {
            $facultades_data_for_global_sum_anterior[$facultad_name] = [
                'total_profesores_anterior' => 0,
                'gran_total_ajustado_anterior' => 0
            ];
        }
        $facultades_data_for_global_sum_anterior[$facultad_name]['total_profesores_anterior'] += $row['total_profesores'];
        $facultades_data_for_global_sum_anterior[$facultad_name]['gran_total_ajustado_anterior'] += $row['gran_total_ajustado'];
    }
    $stmt_global_previous->close();

    
}
    
// --- CALCULAR LOS TOTALES GLOBALES A PARTIR DE LOS DATOS SIN FILTRAR ---
$grand_total_ajustado_global_actual = 0;
$grand_total_profesores_global_actual = 0;
foreach ($facultades_data_for_global_sum_actual as $facultad_nombre => $data) {
    $grand_total_ajustado_global_actual += $data['gran_total_ajustado_actual'];
    $grand_total_profesores_global_actual += $data['total_profesores_actual'];
}

$grand_total_ajustado_global_anterior = 0;
$grand_total_profesores_global_anterior = 0;
foreach ($facultades_data_for_global_sum_anterior as $facultad_nombre => $data) {
    $grand_total_ajustado_global_anterior += $data['gran_total_ajustado_anterior'];
    $grand_total_profesores_global_anterior += $data['total_profesores_anterior'];
}

// --- FIN: OBTENER DATOS SIN FILTRAR PARA CÁLCULO DE TOTALES GLOBALES ---

// --- CSS profesional estilo Unicauca ---

// Include Google Fonts

// Logic for combining data for charts (MOVED UP FOR GENERAL COMPARATIVE)
$facultades_data = [];
// Process current period data
foreach ($data_current_period as $row) {
    $facultad = $row['nombre_facultad'];
    $departamento = $row['nombre_departamento'];
    $tipo = $row['tipo_docente'];
    if (!isset($facultades_data[$facultad])) {
        $facultades_data[$facultad] = [
            'departamentos' => [],
            'total_profesores_actual' => 0,
            'gran_total_ajustado_actual' => 0,
            'total_profesores_anterior' => 0,
            'gran_total_ajustado_anterior' => 0
        ];
    }
    if (!isset($facultades_data[$facultad]['departamentos'][$departamento])) {
        $facultades_data[$facultad]['departamentos'][$departamento] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'total_actual' => 0,
            'total_anterior' => 0
        ];
    }
    $facultades_data[$facultad]['departamentos'][$departamento]['profesores_actual'] += $row['total_profesores'];
    $facultades_data[$facultad]['departamentos'][$departamento]['total_actual'] += $row['gran_total_ajustado'];
    $facultades_data[$facultad]['total_profesores_actual'] += $row['total_profesores'];
    $facultades_data[$facultad]['gran_total_ajustado_actual'] += $row['gran_total_ajustado'];
}

// Process previous period data
foreach ($data_previous_period as $row) {
    $facultad = $row['nombre_facultad'];
    $departamento = $row['nombre_departamento'];
    // Ensure the faculty key exists before trying to access departments
    if (!isset($facultades_data[$facultad])) {
         $facultades_data[$facultad] = [
            'departamentos' => [],
            'total_profesores_actual' => 0, // Initialize as 0 for current period if not present
            'gran_total_ajustado_actual' => 0, // Initialize as 0 for current period if not present
            'total_profesores_anterior' => 0,
            'gran_total_ajustado_anterior' => 0
        ];
    }
    if (!isset($facultades_data[$facultad]['departamentos'][$departamento])) {
        $facultades_data[$facultad]['departamentos'][$departamento] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'total_actual' => 0,
            'total_anterior' => 0
        ];
    }
    $facultades_data[$facultad]['departamentos'][$departamento]['profesores_anterior'] += $row['total_profesores'];
    $facultades_data[$facultad]['departamentos'][$departamento]['total_anterior'] += $row['gran_total_ajustado'];
    $facultades_data[$facultad]['total_profesores_anterior'] += $row['total_profesores'];
    $facultades_data[$facultad]['gran_total_ajustado_anterior'] += $row['gran_total_ajustado'];
}


// Check if it's a specific faculty report OR if it's an admin viewing general report
if ($tipo_usuario == 1 && !$facultad_seleccionada) { // MODIFIED: Show general comparative if admin and no specific faculty selected
    // Gráfica comparativa de totales por facultad (these remain vertical)
    echo "<div style='margin: 40px 0; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>";
    echo "<h3 style='text-align: center;'>Comparativa General por Facultad</h3>";

    // Ensure faculties array is properly populated from $facultades_data keys for consistent labels
    $facultades_chart_labels = array_keys($facultades_data);
    sort($facultades_chart_labels); // Sort labels for consistency

    $totales_actual_general = [];
    $totales_anterior_general = [];
    $profesores_actual_total_general = [];
    $profesores_anterior_total_general = [];

    foreach ($facultades_chart_labels as $facultad_label) {
        $data = $facultades_data[$facultad_label];
        $totales_actual_general[] = $data['gran_total_ajustado_actual'];
        $totales_anterior_general[] = $data['gran_total_ajustado_anterior'];
        $profesores_actual_total_general[] = $data['total_profesores_actual'];
        $profesores_anterior_total_general[] = $data['total_profesores_anterior'];
    }

    if (!empty($facultades_data)) {
      ?>
<?php

$profesores_data_combined = [];
foreach ($facultades_chart_labels as $index => $label) {
    $profesores_data_combined[] = [
        'label' => $label,
        'actual' => $profesores_actual_total_general[$index],
        'anterior' => $profesores_anterior_total_general[$index]
    ];
}

// Sort by 'actual' count in descending order
usort($profesores_data_combined, function($a, $b) {
    return $b['actual'] <=> $a['actual'];
});

// Separate back into sorted arrays
$sorted_facultades_profesores_labels = array_column($profesores_data_combined, 'label');
$sorted_profesores_actual_total_general = array_column($profesores_data_combined, 'actual');
$sorted_profesores_anterior_total_general = array_column($profesores_data_combined, 'anterior');

// --- Sorting Logic for "Valor Proyectado por Facultad" Chart ---
// Combine data for sorting
$valores_data_combined = [];
foreach ($facultades_chart_labels as $index => $label) {
    $valores_data_combined[] = [
        'label' => $label,
        'actual' => $totales_actual_general[$index],
        'anterior' => $totales_anterior_general[$index]
    ];
}

// Sort by 'actual' value in descending order
usort($valores_data_combined, function($a, $b) {
    return $b['actual'] <=> $a['actual'];
});

// Separate back into sorted arrays
$sorted_facultades_valores_labels = array_column($valores_data_combined, 'label');
$sorted_totales_actual_general = array_column($valores_data_combined, 'actual');
$sorted_totales_anterior_general = array_column($valores_data_combined, 'anterior');

?>

<div style='display: flex;'>
    <div style='width: 50%; padding: 15px;'>
        <h4 style='text-align: center;'>Total de Profesores por Facultad</h4>
        <canvas id='chartTotalProfesoresFac' height='400'></canvas>
    </div>

    <div style='width: 50%; padding: 15px;'>
        <h4 style='text-align: center;'>Valor Proyectado por Facultad</h4>
        <canvas id='chartTotalValorFac' height='400'></canvas>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.register(ChartDataLabels);

    // Gráfica de Profesores por Facultad (Horizontal Bar)
    const ctxTotalProfFac = document.getElementById('chartTotalProfesoresFac').getContext('2d');
    new Chart(ctxTotalProfFac, {
        type: 'bar',
        data: {
            labels: <?= json_encode($sorted_facultades_profesores_labels) ?>,
            datasets: [
                {
                    label: 'Actual (<?= htmlspecialchars($anio_semestre) ?>)',
                    data: <?= json_encode($sorted_profesores_actual_total_general) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (<?= htmlspecialchars($periodo_anterior) ?>)',
                    data: <?= json_encode($sorted_profesores_anterior_total_general) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y', // <--- THIS MAKES IT HORIZONTAL
            responsive: true,
            scales: {
                x: { // <--- X-axis for horizontal bar charts
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Profesores'
                    }
                },
                y: { // <--- Y-axis for horizontal bar charts (labels)
                    title: {
                        display: true,
                        text: 'Facultades'
                    }
                }
            },
            plugins: {
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end', // Position data labels at the end of the bars
                    align: 'end', // Align them with the end of the bars
                    offset: 4,
                    formatter: function(value, context) {
                        return value.toLocaleString();
                    }
                },
                tooltip: {
                    enabled: true
                }
            }
        }
    });

    // Gráfica de Valor Proyectado por Facultad (Horizontal Bar)
    const ctxTotalValFac = document.getElementById('chartTotalValorFac').getContext('2d');
    new Chart(ctxTotalValFac, {
        type: 'bar',
        data: {
            labels: <?= json_encode($sorted_facultades_valores_labels) ?>,
            datasets: [
                {
                    label: 'Actual (<?= htmlspecialchars($anio_semestre) ?>)',
                    data: <?= json_encode($sorted_totales_actual_general) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (<?= htmlspecialchars($periodo_anterior) ?>)',
                    data: <?= json_encode($sorted_totales_anterior_general) ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y', // <--- THIS MAKES IT HORIZONTAL
            responsive: true,
            scales: {
                x: { // <--- X-axis for horizontal bar charts
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor Proyectado (en millones)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000000).toLocaleString(undefined, {maximumFractionDigits: 1}) + 'M';
                        }
                    }
                },
                y: { // <--- Y-axis for horizontal bar charts (labels)
                    title: {
                        display: true,
                        text: 'Facultades'
                    }
                }
            },
            plugins: {
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end', // Position data labels at the end of the bars
                    align: 'end', // Align them with the end of the bars
                    offset: 4,
                    formatter: function(value, context) {
                        return '$' + (value / 1000000).toLocaleString(undefined, {maximumFractionDigits: 1}) + 'M';
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Valor: $' + (context.raw / 1000000).toLocaleString(undefined, {maximumFractionDigits: 2}) + ' millones';
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php
    } else {
        echo "<div class='no-data'>No se encontraron datos para la comparativa general por facultad.</div>";
    }
    echo "</div>"; // Cierre del contenedor de comparativa general

} else if ($facultad_seleccionada || $tipo_usuario == 2 || $tipo_usuario == 3) { // Only show specific faculty/department report if a specific faculty is selected (or if not admin)
    // If it's a specific faculty report, show its header
if ($tipo_usuario ==1) {    
echo "<div class='faculty-header'>";
echo "<div class='faculty-title-container'>";
echo "<h2 class='faculty-title'>Datos de Facultad: " 
    . htmlspecialchars($facultades[$pk_fac] ?? 'No seleccionada') 
    . " - " . htmlspecialchars($anio_semestre) 
    . " - " . htmlspecialchars($periodo_anterior) 
    . "</h2>";

echo "</div>";
echo "<a href='#vertablas' class='view-tables-btn'>";
echo "<svg width='16' height='16' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg' class='table-icon'>";
echo "<path d='M3 5H21V21H3V5Z' stroke='currentColor' stroke-width='2'/>";
echo "<path d='M3 9H21M9 9V21' stroke='currentColor' stroke-width='2'/>";
echo "</svg>";
echo "Ver Tablas";
echo "</a>";
echo "</div>";

echo "<style>
/* Header moderno para facultad */
.faculty-header {
    background: linear-gradient(135deg, #003366 0%, #002244 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0, 51, 102, 0.2);
max-width: 1600px;margin: 0 auto;

}

/* Contenedor del título */
.faculty-title-container {
    flex-grow: 1;
}

/* Estilo del título */
.faculty-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Botón Ver Tablas */
.view-tables-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.view-tables-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.view-tables-btn:active {
    transform: translateY(0);
}

/* Icono de tabla */
.table-icon {
    margin-bottom: 1px;
    transition: transform 0.3s ease;
}

.view-tables-btn:hover .table-icon {
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .faculty-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .view-tables-btn {
        align-self: flex-end;
    }
}
</style>";
}

    if (!empty($data_current_period)) {
        foreach ($facultades_data as $facultad => $data) {
    // Calcular diferencias para profesores
    $prof_actual = $data['total_profesores_actual'];
    $prof_anterior = $data['total_profesores_anterior'];
    $diff_prof = $prof_actual - $prof_anterior;
    $porc_prof = ($prof_anterior != 0) ? (abs($diff_prof) / $prof_anterior * 100) : 0;
    $color_prof = ($diff_prof >= 0) ? '#e74c3c' : '#27ae60';
    $icon_prof = ($diff_prof >= 0) ? '▲' : '▼';

    // Calcular diferencias para valor proyectado
    $valor_actual = $data['gran_total_ajustado_actual'];
    $valor_anterior = $data['gran_total_ajustado_anterior'];
    $diff_valor = $valor_actual - $valor_anterior;
    $porc_valor = ($valor_anterior != 0) ? (abs($diff_valor) / $valor_anterior * 100) : 0;
    $color_valor = ($diff_valor >= 0) ? '#e74c3c' : '#27ae60';
    $icon_valor = ($diff_valor >= 0) ? '▲' : '▼';

    // Formatear valores monetarios
    $formatted_valor_actual = number_format($valor_actual, 0, ',', '.');
    $formatted_valor_anterior = number_format($valor_anterior, 0, ',', '.');
    $formatted_diff_valor = number_format(abs($diff_valor), 0, ',', '.');

    // INICIO DEL NUEVO CONTENEDOR PARA LAS DOS TARJETAS DE ESTA FACULTAD
   // echo "<div class='faculty-cards-row'>";



//    echo "</div>"; // FIN DEL NUEVO CONTENEDOR PARA LAS DOS TARJETAS DE ESTA FACULTAD
}
    
     } else {
        echo "<div class='no-data'>No se encontraron datos para el periodo actual.</div>"; // MODIFIED: More descriptive message
    }

    echo "<div class='chart-grid'>";
    echo "<div class='chart-box'>";
    echo "<h4 class='chart-title'>Profesores por vinculación</h4>";
    echo "<canvas id='chartProfesoresTipo' height='300'></canvas>";
    echo "</div>";
    echo "<div class='chart-box'>";
    echo "<h4 class='chart-title'>Valor Proyectado por Vinculación</h4>";
    echo "<canvas id='chartValorTipo' height='300'></canvas>";
    echo "</div>";
    echo "<div class='chart-box' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";
         if (isset($prof_actual)) {

    // Tarjeta de Profesores - Versión compacta
    echo "<div class='card' style='
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #3498db;
        padding: 16px;
    '>";
    
    echo "<div style='display: flex; align-items: center; margin-bottom: 12px;'>";
    
      
echo "<div style='width: 32px; height: 32px; background-color: #3498db20; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 10px;'>";
    echo "<svg width='16' height='16' viewBox='0 0 24 24' fill='#3498db' xmlns='http://www.w3.org/2000/svg'><path d='M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z'/><path d='M12 14C7.58172 14 4 17.5817 4 22H20C20 17.5817 16.4183 14 12 14Z'/></svg>";
    echo "</div>";
    echo "<h4 style='margin: 0; color: #2c3e50; font-size: 1rem; font-weight: 600;'>Profesores</h4>";
    echo "</div>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;'>";
    echo "<div>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Actual</div>";
    echo "<div style='font-weight: 700; font-size: 1.4rem; color: #2c3e50; line-height: 1;'>$prof_actual</div>";
    echo "</div>";
    
    echo "<div style='text-align: right;'>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Anterior</div>";
    echo "<div style='font-weight: 600; font-size: 1.1rem; color: #95a5a6; line-height: 1;'>$prof_anterior</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='
        background-color: #f8fafc;
        padding: 10px 12px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
    '>";
    echo "<div style='color: #7f8c8d; font-weight: 500;'>Variación</div>";
    echo "<div style='display: flex; align-items: center; gap: 6px;'>";
    echo "<span style='color: $color_prof; font-weight: 600;'>$icon_prof " . ($diff_prof >= 0 ? "+$diff_prof" : $diff_prof) . "</span>";
    echo "<span style='background-color: {$color_prof}15; color: $color_prof; padding: 2px 8px; border-radius: 10px; font-weight: 600;'>" . number_format($porc_prof, 1) . "%</span>";
    echo "</div>";
    echo "</div>";
    echo "</div>"; // Cierra tarjeta Profesores
    
    // Tarjeta de Valor Proyectado - Versión compacta
    echo "<div class='card' style='
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #9b59b6;
        padding: 16px;
    '>";
    
    echo "<div style='display: flex; align-items: center; margin-bottom: 12px;'>";
    echo "<div style='width: 32px; height: 32px; background-color: #9b59b620; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 10px;'>";
    echo "<svg width='16' height='16' viewBox='0 0 24 24' fill='#9b59b6' xmlns='http://www.w3.org/2000/svg'><path d='M12 1L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 1ZM12 11.99H19C18.47 16.11 15.72 19.78 12 20.93V12H5V6.3L12 3.19V11.99Z'/></svg>";
    echo "</div>";
    echo "<h4 style='margin: 0; color: #2c3e50; font-size: 1rem; font-weight: 600;'>Valor Proyectado</h4>";
    echo "</div>";
    
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;'>";
    echo "<div>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Actual</div>";
    echo "<div style='font-weight: 700; font-size: 1.4rem; color: #2c3e50; line-height: 1;'>$" . $formatted_valor_actual . "</div>";
    echo "</div>";
    
    echo "<div style='text-align: right;'>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Anterior</div>";
    echo "<div style='font-weight: 600; font-size: 1.1rem; color: #95a5a6; line-height: 1;'>$" . $formatted_valor_anterior . "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='
        background-color: #f8fafc;
        padding: 10px 12px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
    '>";
    echo "<div style='color: #7f8c8d; font-weight: 500;'>Variación</div>";
    echo "<div style='display: flex; align-items: center; gap: 6px;'>";
    echo "<span style='color: $color_valor; font-weight: 600;'>$icon_valor " . ($diff_valor >= 0 ? "+$" : "-$") . $formatted_diff_valor . "</span>";
    echo "<span style='background-color: {$color_valor}15; color: $color_valor; padding: 2px 8px; border-radius: 10px; font-weight: 600;'>" . number_format($porc_valor, 1) . "%</span>";
    echo "</div>";
    echo "</div>";
    echo "</div>"; // Cierra tarjeta Valor Proyectado
    }
echo "</div>"; // Cierra chart-box
    echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
    echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0'></script>";
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);

        const dataCurrent = " . json_encode($data_current_period) . ";
        const dataPrevious = " . json_encode($data_previous_period) . ";

        const processDataForCharts = (data) => {
            const result = {};
            data.forEach(row => {
                const tipo = row.tipo_docente;
                if (!result[tipo]) {
                    result[tipo] = { total_profesores: 0, gran_total_ajustado: 0 };
                }
                result[tipo].total_profesores += parseInt(row.total_profesores);
                result[tipo].gran_total_ajustado += parseFloat(row.gran_total_ajustado);
            });
            return result;
        };

        const currentPeriodSummary = processDataForCharts(dataCurrent);
        const previousPeriodSummary = processDataForCharts(dataPrevious);

        const labels = Array.from(new Set([...Object.keys(currentPeriodSummary), ...Object.keys(previousPeriodSummary)]));
        labels.sort();

        const profesoresActual = labels.map(label => currentPeriodSummary[label]?.total_profesores || 0);
        const profesoresAnterior = labels.map(label => previousPeriodSummary[label]?.total_profesores || 0);
        const valorActual = labels.map(label => currentPeriodSummary[label]?.gran_total_ajustado || 0);
        const valorAnterior = labels.map(label => previousPeriodSummary[label]?.gran_total_ajustado || 0);

        // Gráfica de Profesores por Tipo de Docente
        const ctxProfesoresTipo = document.getElementById('chartProfesoresTipo').getContext('2d');
        new Chart(ctxProfesoresTipo, {
            type: 'bar',
            data: {
                labels: labels,
               datasets: [
                {
                    label: 'Actual (" . htmlspecialchars($anio_semestre) . ")',
                    data: profesoresActual,
                    backgroundColor: 'rgba(75, 192, 192, 1)', // Cambiado a 1
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                    data: profesoresAnterior,
                    backgroundColor: 'rgba(153, 102, 255, 1)', // Cambiado a 1
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de Profesores'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tipo de Docente'
                        }
                    }
                },
                plugins: {
                    datalabels: {
                        display: true,
                        color: '#333',
                        anchor: 'end',
                        align: 'end',
                        formatter: function(value, context) {
                            return value.toLocaleString();
                        }
                    },
                    tooltip: {
                        enabled: true
                    }
                }
            }
        });
// Gráfica de Valor Proyectado por Tipo de Docente
const ctxValorTipo = document.getElementById('chartValorTipo').getContext('2d');
new Chart(ctxValorTipo, {
    type: 'bar',
    data: {
        labels: labels,
     datasets: [
                {
                    label: 'Actual (" . htmlspecialchars($anio_semestre) . ")',
                    data: valorActual,
                    backgroundColor: 'rgba(255, 159, 64, 1)', // Cambiado a 1
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                    data: valorAnterior,
                    backgroundColor: 'rgba(255, 99, 132, 1)', // Cambiado a 1
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Valor Proyectado (en millones)'
                },
                ticks: {
                    callback: function(value) {
                        return '$' + (value / 1000000).toFixed(1) + 'M';
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Tipo de Docente'
                }
            }
        },
        plugins: {
            datalabels: {
                display: true,
                // Cambiado a un tono de gris
                color: '#6c757d', // Un gris suave
                anchor: 'center',
                align: 'top',
                offset: -10,
                formatter: function(value) {
                    return '$' + (value / 1000000).toFixed(1) + 'M';
                },
                font: {
                    size: 10,
                    weight: 'bold'
                },
                clip: false
            }
        }
    }
});
    });
    </script>";
}
    
// Procesamos los datos para los gráficos
$departamentos_data = [];

// Procesar datos del periodo actual
foreach ($data_current_period as $row) {
    $depto = $row['nombre_departamento'];
    if (!isset($departamentos_data[$depto])) {
        $departamentos_data[$depto] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'valor_actual' => 0,
            'valor_anterior' => 0
        ];
    }
    $departamentos_data[$depto]['profesores_actual'] += $row['total_profesores'];
    $departamentos_data[$depto]['valor_actual'] += $row['gran_total_ajustado'];
}

// Procesar datos del periodo anterior
foreach ($data_previous_period as $row) {
    $depto = $row['nombre_departamento'];
    if (!isset($departamentos_data[$depto])) {
        $departamentos_data[$depto] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'valor_actual' => 0,
            'valor_anterior' => 0
        ];
    }
    $departamentos_data[$depto]['profesores_anterior'] += $row['total_profesores'];
    $departamentos_data[$depto]['valor_anterior'] += $row['gran_total_ajustado'];
}

// Ordenar departamentos por cantidad de profesores (mayor a menor)
uasort($departamentos_data, function($a, $b) {
    return $b['profesores_actual'] - $a['profesores_actual'];
});

// Estilos CSS para los gráficos
echo "<style>
    .chart-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 30px 0;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    .chart-wrapper {
        width: 100%;
        height: 100%;
        min-height: 400px;
        position: relative;
    }

    .chart-title {
        text-align: center;
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
    }

    @media (max-width: 768px) {
        .chart-container {
            grid-template-columns: 1fr;
        }
     
</style>";
// Mostrar los gráficos solo si hay datos
if (!empty($departamentos_data) || $facultad_seleccionada ) {
    // Obtener IDs de departamentos y facultades
    $depto_labels = array_keys($departamentos_data);
    $depto_ids = [];
    $facultad_ids = [];
    
    $query_deptos = "SELECT PK_DEPTO, depto_nom_propio, FK_FAC FROM deparmanentos 
                    WHERE depto_nom_propio IN ('" . implode("','", array_map([$conn, 'real_escape_string'], $depto_labels)) . "')";
    $result_deptos = $conn->query($query_deptos);
    
    $nombre_a_ids = [];
    while ($row = $result_deptos->fetch_assoc()) {
        $nombre_a_ids[$row['depto_nom_propio']] = [
            'PK_DEPTO' => $row['PK_DEPTO'],
            'PK_FAC' => $row['FK_FAC']
        ];
    }

    // Preparar arrays ordenados para los gráficos
    $depto_ids_ordenados = [];
    $facultad_ids_ordenados = [];
    foreach ($depto_labels as $depto_nombre) {
        $depto_ids_ordenados[] = $nombre_a_ids[$depto_nombre]['PK_DEPTO'] ?? null;
        $facultad_ids_ordenados[] = $nombre_a_ids[$depto_nombre]['PK_FAC'] ?? null;
    }
if ($tipo_usuario !=3 ) {

    echo "<div class='chart-grid'>";
   echo "<div class='chart-box'>";
        echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
        echo "<h3 class='chart-title'>Profesores por Departamento</h3>";
        echo "<button id='btnAmpliarProfesores' style='
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 13px;
            color: #374151;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        '
        onmouseover='this.style.borderColor=\"#9ca3af\"; this.style.backgroundColor=\"#f9fafb\"' 
        onmouseout='this.style.borderColor=\"#d1d5db\"; this.style.backgroundColor=\"#ffffff\"'>
        Ampliar
        </button>";
echo "</div>";
echo "<div class='chart-wrapper'>";
echo "<canvas id='chartProfesoresDepto'></canvas>";
echo "</div>";
echo "</div>";
    
 echo "<div class='chart-box'>";
echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
echo "<h3 class='chart-title'>Proyectado por Departamento</h3>";
echo "<button id='btnAmpliarValor' style='
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
'
onmouseover='this.style.borderColor=\"#9ca3af\"; this.style.backgroundColor=\"#f9fafb\"' 
onmouseout='this.style.borderColor=\"#d1d5db\"; this.style.backgroundColor=\"#ffffff\"'>
Ampliar
</button>";echo "</div>";
echo "<div class='chart-wrapper'>";
echo "<canvas id='chartValorDepto'></canvas>";
echo "</div>";
echo "</div>";
    
    // --- INICIO DE LÓGICA PARA DETERMINAR QUÉ FACULTAD SE VA A MOSTRAR EN LOS GRÁFICOS DE COMPARACIÓN ---

$faculty_id_for_display = null; // Inicializamos a null
$nombre_facultad_seleccionada = null; // Inicializamos a null

if ($tipo_usuario == 1) { // Si es un usuario administrador
    // Los administradores pueden seleccionar una facultad vía GET.
    // Si no seleccionan ninguna, esta variable permanecerá null,
    // y se les pedirá que seleccionen una facultad.
    if (isset($_GET['facultad_id']) && !empty($_GET['facultad_id'])) {
        $faculty_id_for_display = (int)$_GET['facultad_id'];
    }
} elseif ($tipo_usuario == 2) { // Si es un usuario de tipo 2 (ej. Decano/Jefe de Facultad)
    // Para este tipo de usuario, asumimos que su facultad_id está en la sesión
    // o se obtiene de su perfil de usuario al iniciar sesión.

        $faculty_id_for_display = $pk_fac;
    // Si $faculty_id_for_display sigue siendo null aquí, significa que el usuario de tipo 2
    // no tiene una facultad asignada en su sesión, y se le mostrará un mensaje de error.
}

// Una vez determinado $faculty_id_for_display, obtenemos el nombre y los datos
if ($faculty_id_for_display !== null) {
    $nombre_facultad_seleccionada = $facultades[$faculty_id_for_display] ?? null;

    // Verificar si se encontró el nombre de la facultad y sus datos en $facultades_data
    if ($nombre_facultad_seleccionada && isset($facultades_data[$nombre_facultad_seleccionada])) {
        $selected_faculty_data = $facultades_data[$nombre_facultad_seleccionada];
        
        $selected_faculty_profesores_actual = $selected_faculty_data['total_profesores_actual'];
        $selected_faculty_ajustado_actual = $selected_faculty_data['gran_total_ajustado_actual'];
        
        // Calcular porcentajes
        $porcentaje_profesores = ($grand_total_profesores_global_actual > 0)
            ? ($selected_faculty_profesores_actual / $grand_total_profesores_global_actual) * 100
            : 0;
        $porcentaje_valor = ($grand_total_ajustado_global_actual > 0)
            ? ($selected_faculty_ajustado_actual / $grand_total_ajustado_global_actual) * 100
            : 0;
            
        // Asegurarse de que el porcentaje no supere el 100% para la altura de la barra
        $porcentaje_profesores_display = min(100, max(0, $porcentaje_profesores));
        $porcentaje_valor_display = min(100, max(0, $porcentaje_valor));

    echo "<div class='chart-box' style='background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>";
    echo "<h3 class='chart-title' style='margin-top: 0; color: #2c3e50; text-align: center;'>Participación ".htmlspecialchars($nombre_facultad_seleccionada)." respecto al total de facultades (".$anio_semestre.")</h3>";

if ($faculty_id_for_display !== null && $nombre_facultad_seleccionada && isset($facultades_data[$nombre_facultad_seleccionada])) {
    // Cálculo de promedios (parte nueva que añade funcionalidad)
    $promedio_facultad = $selected_faculty_ajustado_actual > 0 && $selected_faculty_profesores_actual > 0 
        ? $selected_faculty_ajustado_actual / $selected_faculty_profesores_actual 
        : 0;
        
    $promedio_global = $grand_total_ajustado_global_actual > 0 && $grand_total_profesores_global_actual > 0
        ? $grand_total_ajustado_global_actual / $grand_total_profesores_global_actual
        : 0;
    ?>
    
    <!-- Diseño nuevo -->
    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin: 20px 0;">
        <!-- Tarjeta de Profesores -->
        <div style="flex: 1; min-width: 150px; background: #f8fafc; border-radius: 10px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                    <span style="color: #1976d2; font-weight: bold;"><?= number_format($porcentaje_profesores, 1) ?>%</span>
                </div>
                <div>
                    <div style="font-weight: 600; color: #2c3e50;">Profesores</div>
                    <div style="font-size: 0.8rem; color: #7f8c8d;">
                        <?= number_format($selected_faculty_profesores_actual, 0, ',', '.') ?> de <?= number_format($grand_total_profesores_global_actual, 0, ',', '.') ?>
                    </div>
                </div>
            </div>
            <div style="height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; width: <?= $porcentaje_profesores_display ?>%; background: #1976d2;"></div>
            </div>
        </div>
        
        <!-- Tarjeta de Valor Proyectado -->
        <div style="flex: 1; min-width: 150px; background: #f8fafc; border-radius: 10px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #f3e5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                    <span style="color: #8e24aa; font-weight: bold;"><?= number_format($porcentaje_valor, 1) ?>%</span>
                </div>
                <div>
                    <div style="font-weight: 600; color: #2c3e50;">Valor Proyectado</div>
                    <div style="font-size: 0.8rem; color: #7f8c8d;">
                        $<?= number_format($selected_faculty_ajustado_actual, 0, ',', '.') ?> de $<?= number_format($grand_total_ajustado_global_actual, 0, ',', '.') ?>
                    </div>
                </div>
            </div>
            <div style="height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; width: <?= $porcentaje_valor_display ?>%; background: #8e24aa;"></div>
            </div>
        </div>
    </div>
    
    <!-- Sección de promedios (nueva funcionalidad) -->
    <div style="margin-top: 25px; background: #f5f7fa; border-radius: 8px; padding: 15px;">
        <h4 style="margin-top: 0; margin-bottom: 15px; text-align: center; color: #2c3e50; font-size: 1rem;">Valor promedio por profesor</h4>
        
        <div style="display: flex; justify-content: center; gap: 30px; text-align: center;">
            <div>
                <div style="font-size: 0.8rem; color: #7f8c8d; margin-bottom: 5px;">Esta facultad</div>
                <div style="padding: 8px 15px; background: #fff; border-radius: 20px; font-weight: bold; color: #1976d2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block;">
                    $<?= number_format($promedio_facultad, 2, ',', '.') ?>
                </div>
            </div>
            
            <div>
                <div style="font-size: 0.8rem; color: #7f8c8d; margin-bottom: 5px;">Promedio general</div>
                <div style="padding: 8px 15px; background: #fff; border-radius: 20px; font-weight: bold; color: #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block;">
                    $<?= number_format($promedio_global, 2, ',', '.') ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    } else {
        // Mensaje si no se encuentran datos para la facultad (válida) seleccionada/asignada
        echo "<div class='alert alert-info text-center' style='margin-top: 20px; padding: 15px;'>No se encontraron datos para la facultad seleccionada/asignada.</div>";
    }
    }}   echo "</div>"; // Cierre del div.chart-box
    
    
    // Contenedor para el gráfico ampliado (inicialmente oculto)
echo "<div id='chartAmpliadoContainer' style='display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;'>";
echo "<div style='background-color: white; padding: 20px; border-radius: 8px; max-width: 90%; max-height: 90%; overflow: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative;'>";
echo "<button id='cerrarAmpliado' style='position: absolute; top: 15px; right: 15px; background: none; border: none; color: #6c757d; font-size: 1.5rem; cursor: pointer; padding: 5px; line-height: 1;' aria-label='Cerrar modal'>&times;</button>";echo "<h3 style='text-align: center; color: #2c3e50;'>Valor Proyectado por Departamento (Ampliado)</h3>";
echo "<canvas id='chartValorDeptoAmpliado' width='1200' height='600' style='max-width: 100%; max-height: 100%;'></canvas>";
echo "</div>";
echo "</div>"; //antesd echad grd se incluyo esto
    
   //y este es elotro para depto
// Modal para gráfico de Profesores ampliado
echo "<div id='profesoresAmpliadoContainer' style='display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;'>";
echo "<div style='background-color: white; padding: 20px; border-radius: 8px; width: 95%; max-width: 1200px; max-height: 90%; overflow: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative;'>";
echo "<button id='cerrarProfesoresAmpliado' style='position: absolute; top: 10px; right: 10px; background: none; border: none; color: #6c757d; font-size: 1.5rem; cursor: pointer; padding: 5px; line-height: 1;' aria-label='Cerrar modal'>&times;</button>";
echo "<h3 style='text-align: center; color: #2c3e50;'>Profesores por Departamento (Ampliado)</h3>";
echo "<canvas id='chartProfesoresDeptoAmpliado' style='max-width: 100%; max-height: 100%;'></canvas>";
echo "</div>";
echo "</div>"; 
}
    
    echo "</div>";
    
    echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
    echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0'></script>";
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);
        
        // Datos preparados desde PHP
        const labelsDepto = " . json_encode($depto_labels) . ";
        const deptoIds = " . json_encode($depto_ids_ordenados) . ";
        const facultadIds = " . json_encode($facultad_ids_ordenados) . ";
        const profesoresActual = " . json_encode(array_column($departamentos_data, 'profesores_actual')) . ";
        const profesoresAnterior = " . json_encode(array_column($departamentos_data, 'profesores_anterior')) . ";
        const valorActual = " . json_encode(array_column($departamentos_data, 'valor_actual')) . ";
        const valorAnterior = " . json_encode(array_column($departamentos_data, 'valor_anterior')) . ";
        const anioSemestre = '" . htmlspecialchars($anio_semestre) . "';
        const anioSemestreAnterior = '" . htmlspecialchars($periodo_anterior) . "';
        
        // Configuración común para ambos gráficos con eventos de clic
        const commonOptions = {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end',
                    align: 'end',
                    font: {
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        font: {
                            size: 10
                        },
                        callback: function(value, index) {
                            // Hacer que las etiquetas sean clickeables
                            return labelsDepto[index];
                        }
                    }
                }
            },
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const deptoId = deptoIds[index];
                    const facultadId = facultadIds[index];
                    
                    if (deptoId && facultadId) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'depto_comparativo.php';
                        
                        const campos = [
                            {name: 'facultad_id', value: facultadId},
                            {name: 'departamento_id', value: deptoId},
                            {name: 'anio_semestre', value: anioSemestre},
                            {name: 'anio_semestre_anterior', value: anioSemestreAnterior}
                        ];
                        
                        campos.forEach(campo => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = campo.name;
                            input.value = campo.value;
                            form.appendChild(input);
                        });
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            }
        };
        
     // Gráfico de Profesores por Departamento
// --- Función para crear el gráfico de Profesores (reutilizable) ---
function createProfesoresChart(ctx, labels, actual, anterior, anio, anioAnt) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
           datasets: [
                    {
                        label: 'Actual (' + anio + ')',
                        data: actual,
                        backgroundColor: 'rgba(54, 162, 235, 1)', // Cambiado a 1
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Anterior (' + anioAnt + ')',
                        data: anterior,
                        backgroundColor: 'rgba(255, 99, 132, 1)', // Cambiado a 1
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                datalabels: {
                    ...commonOptions.plugins.datalabels,
                    color: '#6c757d',
                    font: {
                        size: 11,
                        weight: 'normal'
                    },
                    formatter: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Profesores',
                        color: '#6c757d'
                    },
                    ticks: {
                        color: '#6c757d'
                    }
                },
                y: {
                    ticks: {
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Crear gráfico original de Profesores por Departamento
const ctxProfDepto = document.getElementById('chartProfesoresDepto');
let chartProfesoresDepto = null;
if (ctxProfDepto) {
    chartProfesoresDepto = createProfesoresChart(
        ctxProfDepto,
        labelsDepto,
        profesoresActual,
        profesoresAnterior,
        anioSemestre,
        anioSemestreAnterior
    );
}
// --- Lógica para el botón de Ampliar del gráfico de Profesores ---
const btnAmpliarProfesores = document.getElementById('btnAmpliarProfesores');
const profesoresAmpliadoContainer = document.getElementById('profesoresAmpliadoContainer');
const cerrarProfesoresAmpliado = document.getElementById('cerrarProfesoresAmpliado');
const chartProfesoresDeptoAmpliado = document.getElementById('chartProfesoresDeptoAmpliado');
let chartProfesoresAmpliado = null;

if (btnAmpliarProfesores && profesoresAmpliadoContainer && cerrarProfesoresAmpliado && chartProfesoresDeptoAmpliado) {
    btnAmpliarProfesores.addEventListener('click', function() {
        // Mostrar el contenedor del gráfico ampliado
        profesoresAmpliadoContainer.style.display = 'flex';
        
        // Configurar dimensiones del canvas
        chartProfesoresDeptoAmpliado.width = chartProfesoresDeptoAmpliado.offsetWidth;
        chartProfesoresDeptoAmpliado.height = chartProfesoresDeptoAmpliado.offsetHeight;
        
        // Destruir gráfico anterior si existe
        if (chartProfesoresAmpliado) {
            chartProfesoresAmpliado.destroy();
        }

        // Crear nuevo gráfico ampliado
        chartProfesoresAmpliado = createProfesoresChart(
            chartProfesoresDeptoAmpliado,
            labelsDepto,
            profesoresActual,
            profesoresAnterior,
            anioSemestre,
            anioSemestreAnterior
        );
    });

    cerrarProfesoresAmpliado.addEventListener('click', function() {
        profesoresAmpliadoContainer.style.display = 'none';
        if (chartProfesoresAmpliado) {
            chartProfesoresAmpliado.destroy();
            chartProfesoresAmpliado = null;
        }
    });

    profesoresAmpliadoContainer.addEventListener('click', function(event) {
        if (event.target === profesoresAmpliadoContainer) {
            profesoresAmpliadoContainer.style.display = 'none';
            if (chartProfesoresAmpliado) {
                chartProfesoresAmpliado.destroy();
                chartProfesoresAmpliado = null;
            }
        }
    });
}
     // Gráfico de Valor Proyectado por Departamento
// --- Función para crear el gráfico de Valor Proyectado (reutilizable) ---
function createValorChart(ctx, labels, actual, anterior, anio, anioAnt) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
          datasets: [
                    {
                        label: 'Actual (' + anio + ')',
                        data: actual,
                        backgroundColor: 'rgba(75, 192, 192, 1)', // Cambiado a 1
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Anterior (' + anioAnt + ')',
                        data: anterior,
                        backgroundColor: 'rgba(153, 102, 255, 1)', // Cambiado a 1
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }
                ]
     
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                datalabels: {
                    ...commonOptions.plugins.datalabels,
                    color: '#6c757d',
                    font: {
                        size: 11,
                        weight: 'normal'
                    },
                    formatter: function(value) {
                        return '$' + (value / 1000000).toLocaleString(undefined, {maximumFractionDigits: 2}) + 'M';
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor Proyectado (en millones)',
                        color: '#6c757d'
                    },
                    ticks: {
                        color: '#6c757d',
                        callback: function(value) {
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                },
                y: {
                    ticks: {
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Crear gráfico original de Valor Proyectado por Departamento
const ctxValorDepto = document.getElementById('chartValorDepto');
let chartValorDepto = null;
if (ctxValorDepto) {
    chartValorDepto = createValorChart(
        ctxValorDepto,
        labelsDepto,
        valorActual,
        valorAnterior,
        anioSemestre,
        anioSemestreAnterior
    );
}

// --- Lógica para el botón de Ampliar y el gráfico ampliado ---
const btnAmpliarValor = document.getElementById('btnAmpliarValor');
const chartAmpliadoContainer = document.getElementById('chartAmpliadoContainer');
const cerrarAmpliado = document.getElementById('cerrarAmpliado');
const chartValorDeptoAmpliado = document.getElementById('chartValorDeptoAmpliado');
let chartAmpliado = null; // Variable para almacenar la instancia del gráfico ampliado

if (btnAmpliarValor && chartAmpliadoContainer && cerrarAmpliado && chartValorDeptoAmpliado) {
    btnAmpliarValor.addEventListener('click', function() {
        // Mostrar el contenedor del gráfico ampliado
        chartAmpliadoContainer.style.display = 'flex';

        // Destruir el gráfico ampliado anterior si existe
        if (chartAmpliado) {
            chartAmpliado.destroy();
        }

        // Crear el gráfico ampliado
        chartAmpliado = createValorChart(
            chartValorDeptoAmpliado,
            labelsDepto,
            valorActual,
            valorAnterior,
            anioSemestre,
            anioSemestreAnterior
        );
    });

    cerrarAmpliado.addEventListener('click', function() {
        // Ocultar el contenedor del gráfico ampliado
        chartAmpliadoContainer.style.display = 'none';
        // Destruir el gráfico ampliado para liberar memoria
        if (chartAmpliado) {
            chartAmpliado.destroy();
            chartAmpliado = null;
        }
    });

    // Cerrar también al hacer clic fuera del contenido del modal
    chartAmpliadoContainer.addEventListener('click', function(event) {
        if (event.target === chartAmpliadoContainer) {
            chartAmpliadoContainer.style.display = 'none';
            if (chartAmpliado) {
                chartAmpliado.destroy();
                chartAmpliado = null;
            }
        }
    });
}
    });
    </script>";
} else {
    echo "<div class='alert alert-info'>No hay datos de departamentos para mostrar gráficos</div>";
}

$combined_data = [];

// Procesar datos del Periodo Actual
foreach ($data_current_period as $row) {
    $key = $row['nombre_facultad'] . '|' . $row['nombre_departamento'] . '|' . $row['tipo_docente'];
    if (!isset($combined_data[$key])) {
        $combined_data[$key] = [
            'nombre_facultad' => $row['nombre_facultad'],
            'nombre_departamento' => $row['nombre_departamento'],
            'PK_DEPTO' => $row['PK_DEPTO'],
            'FK_FAC' => $row['FK_FAC'],
            'tipo_docente' => $row['tipo_docente'],
            'current_period' => [], // Aquí guardaremos los datos del periodo actual
            'previous_period' => []  // Aquí guardaremos los datos del periodo anterior
        ];
    }
    $combined_data[$key]['current_period'] = [
        'total_profesores' => $row['total_profesores'],
        'total_ocasional_tc' => $row['total_ocasional_tc'],
        'total_ocasional_mt' => $row['total_ocasional_mt'],
        'total_horas_agregadas' => $row['total_horas_agregadas'],
        'gran_total_ajustado' => $row['gran_total_ajustado'] // ¡Aseguramos que esté aquí!
    ];
}

// Procesar datos del Periodo Anterior
foreach ($data_previous_period as $row) {
    $key = $row['nombre_facultad'] . '|' . $row['nombre_departamento'] . '|' . $row['tipo_docente'];
    if (!isset($combined_data[$key])) {
        // Si no existe en el periodo actual, lo creamos para el anterior
        $combined_data[$key] = [
            'nombre_facultad' => $row['nombre_facultad'],
            'nombre_departamento' => $row['nombre_departamento'],
            'PK_DEPTO' => $row['PK_DEPTO'],
            'FK_FAC' => $row['FK_FAC'],
            'tipo_docente' => $row['tipo_docente'],
            'current_period' => [],
            'previous_period' => []
        ];
    }
    $combined_data[$key]['previous_period'] = [
        'total_profesores' => $row['total_profesores'],
        'total_ocasional_tc' => $row['total_ocasional_tc'],
        'total_ocasional_mt' => $row['total_ocasional_mt'],
        'total_horas_agregadas' => $row['total_horas_agregadas'],
        'gran_total_ajustado' => $row['gran_total_ajustado'] // ¡Aseguramos que esté aquí!
    ];
}

// Opcional: Ordenar los datos combinados por Facultad, Departamento, Tipo Docente
// Esto asegura que la tabla se vea ordenada consistentemente
ksort($combined_data); // Ordena por la clave, que incluye facultad, depto, tipo


$combined_data = [];

// Procesar datos del Periodo Actual
foreach ($data_current_period as $row) {
    $key = $row['nombre_facultad'] . '|' . $row['nombre_departamento'] . '|' . $row['tipo_docente'];
    if (!isset($combined_data[$key])) {
        $combined_data[$key] = [
            'nombre_facultad' => $row['nombre_facultad'],
            'nombre_departamento' => $row['nombre_departamento'],
            'PK_DEPTO' => $row['PK_DEPTO'],
            'FK_FAC' => $row['FK_FAC'],
            'tipo_docente' => $row['tipo_docente'],
            'current_period' => [], // Aquí guardaremos los datos del periodo actual
            'previous_period' => []  // Aquí guardaremos los datos del periodo anterior
        ];
    }
    $combined_data[$key]['current_period'] = [
        'total_profesores' => $row['total_profesores'],
        'total_ocasional_tc' => $row['total_ocasional_tc'],
        'total_ocasional_mt' => $row['total_ocasional_mt'],
        'total_horas_agregadas' => $row['total_horas_agregadas'],
        'gran_total_ajustado' => $row['gran_total_ajustado']
    ];
}

// Procesar datos del Periodo Anterior
foreach ($data_previous_period as $row) {
    $key = $row['nombre_facultad'] . '|' . $row['nombre_departamento'] . '|' . $row['tipo_docente'];
    if (!isset($combined_data[$key])) {
        $combined_data[$key] = [
            'nombre_facultad' => $row['nombre_facultad'],
            'nombre_departamento' => $row['nombre_departamento'],
            'PK_DEPTO' => $row['PK_DEPTO'],
            'FK_FAC' => $row['FK_FAC'],
            'tipo_docente' => $row['tipo_docente'],
            'current_period' => [],
            'previous_period' => []
        ];
    }
    $combined_data[$key]['previous_period'] = [
        'total_profesores' => $row['total_profesores'],
        'total_ocasional_tc' => $row['total_ocasional_tc'],
        'total_ocasional_mt' => $row['total_ocasional_mt'],
        'total_horas_agregadas' => $row['total_horas_agregadas'],
        'gran_total_ajustado' => $row['gran_total_ajustado']
    ];
}

ksort($combined_data);

echo "<div class='period-container' id='vertablas'>";
echo "<div class='period-box full-width-table'>";
echo "<div class='period-comparison-header'>
        <div class='period-comparison-title'>
            Tablas Comparativas de Periodos: " . htmlspecialchars($anio_semestre) . " vs " . htmlspecialchars($periodo_anterior) . "
        </div>
        <a href='#secciongraf' class='back-to-top-button'>
            <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' class='back-to-top-icon'>
                <path d='M12 19V5M5 12l7-7 7 7'/>
            </svg>
            Volver arriba
        </a>
    </div>";

echo "<style>
    /* Estilos para el header de comparación */
 .period-comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    /* Colores y gradiente de .comparison-header */
    background: linear-gradient(to right, #006699, #004d80); /* Gradiente de Unicauca */
    color: white;
    padding: 0.8rem 1.5rem; /* Padding ajustado */
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1); /* Sombra más sutil */
    
    /* Propiedades flex adicionales de .comparison-header para mejor responsividad */
    flex-wrap: wrap; /* Permite que los elementos se envuelvan */
    gap: 10px; /* Espacio entre los elementos */
}

/* Puedes mantener .comparison-header tal cual si lo usas en otros lugares */
.comparison-header {
    background: linear-gradient(to right, #006699, #004d80); /* Mantiene tu gradiente original */
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between; /* Mueve el título y los botones a los extremos */
    flex-wrap: wrap; /* Permite que los elementos se envuelvan */
    gap: 10px; /* Espacio entre los elementos */
}

    .period-comparison-title {
        font-size: 1.1rem;
        font-weight: 500;
        letter-spacing: 0.3px;
    }

    /* Estilos para el botón de volver arriba */
    .back-to-top-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .back-to-top-button:hover {
        background-color: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
    }

    .back-to-top-button:active {
        transform: translateY(0);
    }

    .back-to-top-icon {
        transition: transform 0.3s ease;
    }

    .back-to-top-button:hover .back-to-top-icon {
        transform: translateY(-2px);
    }
</style>";
if (!empty($combined_data)) {
    echo "<div class='data-summary-container'>";
    
    // Encabezado informativo

    // Contenedor de tarjetas
    echo "<div class='data-cards-container'>";
    
    // Tarjeta para Semanas Cátedra
    echo "<div class='data-card'>";
    echo "<div class='card-header'>Semanas Cátedra ".$anio_semestre."</div>";
    echo "<div class='card-content'>";
    echo "<div class='current-value'>" . number_format($semanas_catedra, 0, ',', '.') . "</div>";
    echo "<div class='previous-value'>Anterior (".$periodo_anterior."): " . number_format($semanas_catedra_ant, 0, ',', '.') . "</div>";
    echo "</div>";
    echo "</div>";
    
    // Tarjeta para Semanas Ocasional
    echo "<div class='data-card'>";
    echo "<div class='card-header'>Semanas Ocasional ".$anio_semestre."</div>";
    echo "<div class='card-content'>";
    echo "<div class='current-value'>" . number_format($semanas_ocasional, 0, ',', '.') . "</div>";
    echo "<div class='previous-value'>Anterior(".$periodo_anterior."): " . number_format($semanas_ocasional_ant, 0, ',', '.') . "</div>";
    echo "</div>";
    echo "</div>";
    
    // Tarjeta para Valor Punto
    echo "<div class='data-card'>";
    echo "<div class='card-header'>Valor Punto ".$anio_semestre."</div>";
    echo "<div class='card-content'>";
    echo "<div class='current-value'>$" . number_format($valor_punto, 0, ',', '.') . "</div>";
    echo "<div class='previous-value'>Anterior (".$periodo_anterior."): $" . number_format($valor_punto_ant, 0, ',', '.') . "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>"; // cierre data-cards-container
    echo "</div>"; // cierre data-summary-container
    echo "<div class='table-container'>";
    echo "<table id='comparativeTable' class='compact-table comparative-table'>"; 
    echo "<thead>";
    echo "<tr>";
    echo "<th rowspan='2'>Facultad</th>";
    echo "<th rowspan='2'>Departamento</th>";
    echo "<th rowspan='2'>Tipo</th>";
    echo "<th colspan='5'>Periodo Actual (" . htmlspecialchars($anio_semestre) . ")</th>";
    echo "<th colspan='5'>Periodo Anterior (" . htmlspecialchars($periodo_anterior) . ")</th>";
    // Colspan ahora es 4 para las diferencias (Prof, Hrs, Proy, %)
    echo "<th colspan='4'>Diferencia</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<th>TC</th>";
    echo "<th>MT</th>";
    echo "<th>Prof.</th>";
    echo "<th>Total Hrs.</th>";
    echo "<th>Proyectado</th>";
    echo "<th>TC</th>";
    echo "<th>MT</th>";
    echo "<th>Prof.</th>";
    echo "<th>Total Hrs.</th>";
    echo "<th>Proyectado</th>";
    echo "<th>Prof.</th>";
    echo "<th>Hrs.</th>";
    echo "<th>Proy.</th>";
    echo "<th>%</th>"; // ¡NUEVO ENCABEZADO!
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";

    foreach ($combined_data as $key => $data) {
        $current = $data['current_period'];
        $previous = $data['previous_period'];

        $current_prof = isset($current['total_profesores']) ? $current['total_profesores'] : 0;
        $current_horas = isset($current['total_horas_agregadas']) ? $current['total_horas_agregadas'] : 0;
        $current_proy = isset($current['gran_total_ajustado']) ? $current['gran_total_ajustado'] : 0;

        $previous_prof = isset($previous['total_profesores']) ? $previous['total_profesores'] : 0;
        $previous_horas = isset($previous['total_horas_agregadas']) ? $previous['total_horas_agregadas'] : 0;
        $previous_proy = isset($previous['gran_total_ajustado']) ? $previous['gran_total_ajustado'] : 0;

        $diff_prof = $current_prof - $previous_prof;
        $diff_horas = $current_horas - $previous_horas;
        $diff_proy = $current_proy - $previous_proy;

        // Cálculo del Porcentaje de Cambio
        $percentage_change = null; // Usamos null para indicar que no hay cambio o no es aplicable
        if ($previous_proy !== 0) { // Evitar división por cero
            $percentage_change = ($diff_proy / $previous_proy) * 100;
        } elseif ($diff_proy !== 0) {
            // Si el anterior es 0 pero el actual no, indica un crecimiento "infinito" o muy grande
            $percentage_change = 100; // Podrías usar otro valor como null o "N/A"
        }

        // Lógica para determinar el icono y la clase CSS (color)
        $arrow_prof = '';
        $class_prof = '';
        if ($diff_prof > 0) {
            $arrow_prof = '&#x25B2;'; // Flecha arriba
            $class_prof = 'diff-bad'; // ROJO: Más profesores es MALO
        } elseif ($diff_prof < 0) {
            $arrow_prof = '&#x25BC;'; // Flecha abajo
            $class_prof = 'diff-good'; // VERDE: Menos profesores es BUENO
        }

        $arrow_horas = '';
        $class_horas = '';
        if ($diff_horas > 0) {
            $arrow_horas = '&#x25B2;'; // Flecha arriba
            $class_horas = 'diff-bad'; // ROJO: Más horas es MALO
        } elseif ($diff_horas < 0) {
            $arrow_horas = '&#x25BC;'; // Flecha abajo
            $class_horas = 'diff-good'; // VERDE: Menos horas es BUENO
        }

        $arrow_proy = '';
        $class_proy = '';
        if ($diff_proy < 0) { // Menos costo es bueno
            $arrow_proy = '&#x25BC;'; // Flecha abajo
            $class_proy = 'diff-good'; // VERDE
        } elseif ($diff_proy > 0) { // Más costo es malo
            $arrow_proy = '&#x25B2;'; // Flecha arriba
            $class_proy = 'diff-bad'; // ROJO
        }

        echo "<tr>";
        echo "<td>" . htmlspecialchars($data['nombre_facultad']) . "</td>";
        echo "<td>"; // Celda del departamento
        // Añade data-order para que DataTables lo use para ordenar por el nombre completo
        echo "<span data-order='" . htmlspecialchars($data['nombre_departamento']) . "'>";
        echo "<form action='depto_comparativo.php' method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($data['PK_DEPTO']) . "'>";
        echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($data['FK_FAC']) . "'>";
        echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
        echo "<input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>";
        echo "<button type='submit' class='departamento-link no-wrap-content'>"; // Añadimos una nueva clase aquí
        echo htmlspecialchars($data['nombre_departamento']);
        echo "</button>";
        echo "</form>";
        echo "</span>";
        echo "</td>";
        echo "<td>" . htmlspecialchars($data['tipo_docente']) . "</td>";

        // Datos del Periodo Actual
        if ($data['tipo_docente'] !== 'Catedra') {
            echo "<td data-order='" . (isset($current['total_ocasional_tc']) ? $current['total_ocasional_tc'] : 0) . "'>" . (isset($current['total_ocasional_tc']) && $current['total_ocasional_tc'] > 0 ? htmlspecialchars($current['total_ocasional_tc']) : '') . "</td>";
            echo "<td data-order='" . (isset($current['total_ocasional_mt']) ? $current['total_ocasional_mt'] : 0) . "'>" . (isset($current['total_ocasional_mt']) && $current['total_ocasional_mt'] > 0 ? htmlspecialchars($current['total_ocasional_mt']) : '') . "</td>";
        } else {
            echo "<td data-order='0'></td>";
            echo "<td data-order='0'></td>";
        }
        echo "<td data-order='" . $current_prof . "'>" . ($current_prof !== 0 ? htmlspecialchars($current_prof) : '') . "</td>";
        echo "<td data-order='" . $current_horas . "'>" . ($current_horas !== 0 ? number_format($current_horas, 0, ',', '.') : '') . "</td>";
        echo "<td class='currency' data-order='" . $current_proy . "'>$" . ($current_proy !== 0 ? number_format($current_proy / 1000000, 2, ',', '.') . "M" : '') . "</td>";

        // Datos del Periodo Anterior
        if ($data['tipo_docente'] !== 'Catedra') {
            echo "<td data-order='" . (isset($previous['total_ocasional_tc']) ? $previous['total_ocasional_tc'] : 0) . "'>" . (isset($previous['total_ocasional_tc']) && $previous['total_ocasional_tc'] > 0 ? htmlspecialchars($previous['total_ocasional_tc']) : '') . "</td>";
            echo "<td data-order='" . (isset($previous['total_ocasional_mt']) ? $previous['total_ocasional_mt'] : 0) . "'>" . (isset($previous['total_ocasional_mt']) && $previous['total_ocasional_mt'] > 0 ? htmlspecialchars($previous['total_ocasional_mt']) : '') . "</td>";
        } else {
            echo "<td data-order='0'></td>";
            echo "<td data-order='0'></td>";
        }
        echo "<td data-order='" . $previous_prof . "'>" . ($previous_prof !== 0 ? htmlspecialchars($previous_prof) : '') . "</td>";
        echo "<td data-order='" . $previous_horas . "'>" . ($previous_horas !== 0 ? number_format($previous_horas, 0, ',', '.') : '') . "</td>";
        echo "<td class='currency' data-order='" . $previous_proy . "'>$" . ($previous_proy !== 0 ? number_format($previous_proy / 1000000, 2, ',', '.') . "M" : '') . "</td>";

        // Celdas de Diferencias (Prof, Hrs, Proy, %)
        echo "<td class='" . $class_prof . "' data-order='" . $diff_prof . "'>" . ($diff_prof !== 0 ? htmlspecialchars($diff_prof) . " " . $arrow_prof : '') . "</td>";
        echo "<td class='" . $class_horas . "' data-order='" . $diff_horas . "'>" . ($diff_horas !== 0 ? number_format($diff_horas, 0, ',', '.') . " " . $arrow_horas : '') . "</td>";
        echo "<td class='currency " . $class_proy . "' data-order='" . $diff_proy . "'>$" . ($diff_proy !== 0 ? number_format($diff_proy / 1000000, 2, ',', '.') . "M" . " " . $arrow_proy : '') . "</td>";
        // ¡NUEVA CELDA: Porcentaje de Cambio!
        echo "<td class='" . $class_proy . "' data-order='" . ($percentage_change !== null ? $percentage_change : -999999999) . "'>"; // Use a very low number for null/empty for consistent sorting
        if ($percentage_change !== null && $diff_proy !== 0) { // Mostrar solo si hay un cambio y es calculable
            echo number_format($percentage_change, 1, ',', '.') . "%";
        }
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";
} else {
    echo "<div class='no-data'>No hay datos disponibles para la comparativa de periodos</div>";
}
echo "</div>";
echo "</div>";

echo "</div>"; // cierre unicauca-container
?>
    
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializa DataTables en tu tabla.
            // Asegúrate de que el ID de la tabla coincida con el que generas en PHP.
            $('#comparativeTable').DataTable({
                // DataTables automáticamente detecta el atributo data-order para la ordenación.
                // No necesitas configuración adicional si lo usas consistentemente.
            });
        });
    </script>
<style>
/* Variables de color institucionales Unicauca */
:root {
  --unicauca-blue: #003366;
  --unicauca-gold: #FFCC00;
  --unicauca-blue-light: #1a4d80;
  --unicauca-gray-light: #f8f9fa;
  --unicauca-gray-medium: #e9ecef;
  --unicauca-gray-dark: #dee2e6;
  --unicauca-text: #212529;
  --unicauca-text-light: #6c757d;
  --unicauca-success: #28a745;
  --unicauca-danger: #dc3545;
  --unicauca-warning: #ffc107;

  /* Colores para las tarjetas de resumen */
  --summary-text-dark: #2c3e50;
  --summary-text-light: #6c757d;
  --summary-period-current: #3498db;
  --summary-period-previous: #95a5a6;
  --summary-border-light: #e0e0e0;
  --summary-card-border: #3498db; /* Borde izquierdo de la tarjeta */
  --summary-change-positive-bg: rgba(46, 204, 113, 0.1);
  --summary-change-positive-color: #2ecc71;
  --summary-change-negative-bg: rgba(231, 76, 60, 0.1);
  --summary-change-negative-color: #e74c3c;
  --summary-change-neutral-bg: rgba(149, 165, 166, 0.1);
  --summary-change-neutral-color: #95a5a6;
}

/* Contenedor principal de tablas (si lo usas) */
.period-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    padding: 15px;
    max-width: 1700px;
    margin: 0 auto; /* Add this line to center the container itself */
}

/* Estilo profesional para tablas - UX Mejorado */
.compact-table {
  width: 100%;
  border-collapse: separate; /* Permite border-spacing y border-radius */
  border-spacing: 0; /* Elimina espacio entre bordes de celda */
  font-size: 1em;
  font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
  color: var(--unicauca-text);
  margin: 1rem 0; /* Espacio superior e inferior */
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* Sombra sutil para profundidad */
  border-radius: 8px; /* Bordes ligeramente redondeados para la tabla completa */
  overflow: hidden; /* Importante para que el border-radius se aplique bien */
}

/* Encabezados con estilo moderno */
.compact-table thead th {
  background-color:#003366;
  color: white;
  font-weight: 600;
  padding: 12px 15px;
  text-align: center;
  position: sticky; /* Encabezado fijo al hacer scroll */
  top: 0;
  z-index: 10; /* Asegura que el encabezado esté por encima del contenido */
  border: none; /* Elimina bordes individuales de los th */
  white-space: nowrap; /* Mantiene el texto del encabezado en una sola línea */
}

/* Bordes inferiores específicos para los encabezados */
.compact-table thead tr:first-child th {
  border-bottom: 2px solid var(--unicauca-blue-light);
}

.compact-table thead tr:last-child th {
  border-bottom: none;
}

/* Celdas con mejor espaciado y jerarquía visual */
.compact-table td {
  padding: 10px 15px !important; /* Más padding para que el contenido respire */
  border-bottom: 1px solid var(--unicauca-gray-dark); /* Línea divisoria suave */
  vertical-align: middle; /* Alineación vertical central */
  line-height: 1.4; /* Espaciado entre líneas para mejor lectura */
  white-space: nowrap; /* FUERZA EL CONTENIDO DE LAS CELDAS A UNA SOLA LÍNEA */
}

/* Efecto hover sutil en filas del cuerpo de la tabla */
.compact-table tbody tr:hover {
  background-color: rgba(0, 51, 102, 0.03); /* Ligero azul de Unicauca al pasar el ratón */
}

/* Filas alternas con contraste suave */
.compact-table tbody tr:nth-child(even) {
  background-color: var(--unicauca-gray-light);
}

/* Estilo para enlaces de departamento */
.departamento-link {
  background: none;
  border: none;
  color: var(--unicauca-blue);
  text-decoration: none; /* Sin subrayado por defecto */
  cursor: pointer;
  font-weight: 500;
  padding: 2px 6px;
  border-radius: 4px;
  transition: all 0.2s ease; /* Transiciones suaves para hover */
  display: inline-flex; /* Permite alinear texto y flecha */
  align-items: center;
}

.departamento-link:hover {
  background-color: rgba(0, 51, 102, 0.08); /* Fondo sutil al hover */
  color: var(--unicauca-blue-light);
}

.departamento-link:hover::after {
  content: "→"; /* Flecha de dirección */
  margin-left: 5px;
  font-size: 0.9em;
  transition: margin 0.2s ease;
}

/* Estilos para valores monetarios */
.currency {
  font-family: 'Roboto Mono', monospace, sans-serif; /* Fuente monoespaciada para alinear números */
  font-weight: 500;
  text-align: right;
  color: var(--unicauca-text);
  white-space: nowrap; /* Asegura que la cifra monetaria no se rompa */
}

/* Indicadores de diferencia mejorados (flechas y colores) */
.diff-good {
  color: var(--unicauca-success);
  font-weight: 600;
  position: relative; /* Para posicionar la flecha */
  padding-left: 18px; /* Espacio para la flecha */
  white-space: nowrap; /* Mantiene la diferencia en una línea */
}

.diff-good::before {
  content: "↑"; /* Flecha hacia arriba */
  position: absolute;
  left: 2px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1em;
}

.diff-bad {
  color: var(--unicauca-danger);
  font-weight: 600;
  position: relative;
  padding-left: 18px;
  white-space: nowrap; /* Mantiene la diferencia en una línea */
}

.diff-bad::before {
  content: "↓"; /* Flecha hacia abajo */
  position: absolute;
  left: 2px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1.1em;
}

.diff-neutral {
  color: var(--unicauca-text-light);
  font-weight: 400;
  white-space: nowrap; /* Mantiene la diferencia en una línea */
}

/* Contenedor de tabla con scroll horizontal y vertical */
.table-container {
  max-height: 600px; /* Altura máxima antes de activar el scroll vertical */
  overflow-y: auto; /* Scroll vertical */
  overflow-x: auto; /* Scroll horizontal crucial para nowrap */
  position: relative;
  border: 1px solid var(--unicauca-gray-dark);
  border-radius: 6px;
  margin: 1rem 0;
}

/* Estilo para la primera columna (Facultad), para que destaque */
.compact-table td:first-child {
  font-weight: 500;
  color: var(--unicauca-blue);
}

/* Efecto visual para celdas importantes (si se aplica esta clase en el HTML) */
.highlight-cell {
  background-color: rgba(255, 204, 0, 0.1); /* Ligero fondo dorado */
  font-weight: 600;
}

/* Scrollbar personalizada para navegadores Webkit (Chrome, Safari, Edge) */
.table-container::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.table-container::-webkit-scrollbar-track {
  background: #f1f1f1; /* Un gris muy claro para el fondo del riel */
}

.table-container::-webkit-scrollbar-thumb {
  background: #888; /* Un gris medio para el pulgar */
  border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
  background: #555; /* Un gris más oscuro al pasar el ratón para el pulgar */
}
/* Efecto de sombra al hacer scroll (requiere JS para añadir/quitar la clase 'scrolling') */
.table-container.scrolling::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 20px;
  background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(255,255,255,0.9));
  pointer-events: none;
}

/* Encabezado General para comparativos (ya estaba definido y es lo que querías estandarizar) */
.comparison-header,
.period-comparison-header { /* Aplicamos los mismos estilos a ambos */
background: linear-gradient(to right, #005a8c, #003366);    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

/* Contenedor principal del selector de facultad para admin */
.selector-facultad-container {
  background-color: white;
  border-radius: 8px;
  padding: 12px 15px;
  margin: 15px 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid var(--unicauca-gray-dark);
  display: flex;
  flex-direction: column;
  gap: 15px;
  align-items: center;
}

/* Formulario del selector de facultad (para admin) */
.selector-facultad-form {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  justify-content: center;
  width: 100%;
}

/* Etiqueta del selector de facultad */
.selector-facultad-form .selector-label {
    font-weight: 600;
    color: var(--unicauca-text);
    font-size: 0.95em;
    white-space: nowrap;
}

/* Select y botón primario dentro del formulario */
.selector-facultad-form select,
.selector-facultad-form input[type="text"] {
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid var(--unicauca-gray-medium);
  font-size: 0.95em;
  min-width: 220px;
  max-width: 300px;
  background-color: var(--unicauca-gray-light);
  color: var(--unicauca-text);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  flex-grow: 1;
}

.selector-facultad-form select:focus,
.selector-facultad-form input[type="text"]:focus {
  border-color: var(--unicauca-blue);
  box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.2);
  outline: none;
  background-color: white;
}

/* Botón principal (ej. "Ver Reporte") */
.selector-facultad-form .btn-primary {
  padding: 8px 18px;
  background-color: var(--unicauca-blue);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.95em;
  font-weight: 500;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.selector-facultad-form .btn-primary:hover {
  background-color: var(--unicauca-blue-light);
  transform: translateY(-1px);
}

.selector-facultad-form .btn-primary:active {
  background-color: var(--unicauca-blue);
  transform: translateY(0);
}

/* Nuevo Botón de Alternancia (Comparativo Espejo/Original) */
.btn-switch {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 8px 18px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 0.95em;F
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 180px;
}

/* Estilos específicos para el botón "Comparativo Espejo" */
.btn-switch-espejo {
  background-color: var(--unicauca-gold);
  color: var(--unicauca-blue);
  border: 1px solid var(--unicauca-gold);
}

.btn-switch-espejo:hover {
  background-color: #e6b800;
  border-color: #e6b800;
  transform: translateY(-1px);
}

/* Estilos específicos para el botón "Comparativo Original" */
.btn-switch-original {
  background-color: var(--unicauca-blue-light);
  color: white;
  border: 1px solid var(--unicauca-blue-light);
}

.btn-switch-original:hover {
  background-color: var(--unicauca-blue);
  border-color: var(--unicauca-blue);
  transform: translateY(-1px);
}

/* Botón "Ver Tablas" */
.view-tables-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background-color: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.view-tables-btn:hover {
    background-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.view-tables-btn svg {
    margin-bottom: 1px;
}

/* Contenedor principal para el resumen de datos (tarjetas) */
.data-summary-container {
    background-color: var(--unicauca-gray-light);
    border-radius: 8px;
    padding: 15px; /* Reducido de 20px a 15px */
    margin-bottom: 20px; /* Reducido de 30px a 20px */
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* Encabezado del resumen */
.summary-header {
    margin-bottom: 10px; /* Reducido de 20px a 10px */
    border-bottom: 1px solid var(--summary-border-light);
    padding-bottom: 10px; /* Reducido de 15px a 10px */
}

.summary-header h3 {
    color: var(--summary-text-dark);
    margin: 0 0 5px 0; /* Reducido de 10px a 5px */
    font-size: 1.3rem; /* Ligeramente reducido de 1.4rem a 1.3rem */
}

.period-info {
    display: flex;
    gap: 15px; /* Reducido de 20px a 15px */
    font-size: 0.85rem; /* Ligeramente reducido de 0.9rem a 0.85rem */
    color: var(--summary-text-light);
}

.current-period {
    color: var(--summary-period-current);
    font-weight: 500;
}

.previous-period {
    color: var(--summary-period-previous);
}

/* Contenedor de tarjetas */
.data-cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Min-width reducido para más columnas en pantallas grandes */
    gap: 10px; /* Reducido de 15px a 10px */
}

/* Tarjetas individuales */
.data-card {
    background: white;
    border-radius: 8px;
    padding: 12px; /* Reducido de 15px a 12px */
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-left: 4px solid var(--summary-card-border);
}

.card-header {
    font-weight: 600;
    color: var(--summary-text-dark);
    margin-bottom: 5px; /* Reducido de 10px a 5px */
    font-size: 0.95rem; /* Ligeramente reducido de 1rem a 0.95rem */
}

.card-content {
    display: flex;
    flex-direction: column;
}

.current-value {
    font-size: 1.4rem; /* Reducido de 1.5rem a 1.4rem */
    font-weight: 700;
    color: var(--summary-text-dark);
    margin-bottom: 3px; /* Reducido de 5px a 3px */
}

.previous-value {
    font-size: 0.8rem; /* Reducido de 0.85rem a 0.8rem */
    color: var(--summary-period-previous);
    display: flex;
    align-items: center;
}

/* Indicadores de cambio */
.change-indicator {
    margin-left: 6px; /* Ligeramente reducido de 8px a 6px */
    font-size: 0.75rem; /* Ligeramente reducido de 0.8rem a 0.75rem */
    padding: 1px 5px; /* Reducido de 2px 6px a 1px 5px */
    border-radius: 10px; /* Ligeramente reducido de 12px a 10px */
}

.change-positive {
    background-color: var(--summary-change-positive-bg);
    color: var(--summary-change-positive-color);
}

.change-negative {
    background-color: var(--summary-change-negative-bg);
    color: var(--summary-change-negative-color);
}

.change-neutral {
    background-color: var(--summary-change-neutral-bg);
    color: var(--summary-change-neutral-color);
}

/* Media queries para Responsividad en pantallas pequeñas */
@media (max-width: 768px) {
  .compact-table {
    font-size: 0.8em;
  }
  
  .compact-table th, 
  .compact-table td {
    padding: 8px 10px;
  }

  .data-cards-container {
      grid-template-columns: 1fr; /* Una columna en móviles */
      gap: 10px; /* Mantiene el gap reducido */
  }

  /* Selector de Facultad (Admin) */
  .selector-facultad-container {
    flex-direction: column;
    gap: 10px;
    padding: 10px;
  }

  .selector-facultad-form {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .selector-facultad-form .selector-label {
      width: 100%;
      text-align: center;
      margin-bottom: 0;
  }

  .selector-facultad-form select,
  .selector-facultad-form .btn-primary,
  .btn-switch {
    min-width: 100%;
    max-width: 100%;
  }

  /* Encabezado (No-Admin y Period Comparison Header) */
  .comparison-header,
  .period-comparison-header {
      flex-direction: column;
      align-items: stretch;
      text-align: center;
      padding: 0.8rem 1rem;
      gap: 10px;
  }

  .comparison-title {
      font-size: 1rem;
  }

  .view-tables-btn,
  .btn-switch {
      width: 100%;
      justify-content: center;
  }
}
</style>
    </body>
</html>