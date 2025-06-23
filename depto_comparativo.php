<?php
require('include/headerz.php');
require 'funciones.php';
// Conexión a la base de datos (ajusta los parámetros según tu configuración)
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
//require 'actualizar_usuario.php'; // <-- Incluir aquí
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
    exit(); // Detener toda la ejecución del script
}

    // Obtener los parámetros de la URL
$facultad_id = isset($_POST['facultad_id']) ? $_POST['facultad_id'] : null;
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre = $_POST['anio_semestre'];
    $periodo_anterior = $_POST['anio_semestre_anterior'];

 $aniose= $anio_semestre;
function obtenerPeriodoAnterior($anio_semestre) {
    list($anio, $semestre) = explode('-', $anio_semestre);
    if ($semestre == '1') {
        $anio--;
        $semestre = '2';
    } else {
        $semestre = '1';
    }
    return $anio . '-' . $semestre;
}
//$periodo_anterior= obtenerPeriodoAnterior($aniose);

        $cierreperiodo = obtenerperiodo($anio_semestre);

$consultaper = "SELECT * FROM periodo where periodo.nombre_periodo ='$anio_semestre'";
$resultadoper = $conn->query($consultaper);
while ($rowper = $resultadoper->fetch_assoc()) {
    $fecha_ini_cat = $rowper['inicio_sem'];
    $fecha_fin_cat = $rowper['fin_sem'];
    $fecha_ini_ocas = $rowper['inicio_sem_oc'];
    $fecha_fin_ocas = $rowper['fin_sem_oc'];
    $valor_punto = $rowper['valor_punto'];
    $smlv = $rowper['smlv'];
  
}


$consultaperant = "SELECT * FROM periodo where periodo.nombre_periodo ='$periodo_anterior'";
$resultadoperant = $conn->query($consultaperant);
while ($rowper = $resultadoperant->fetch_assoc()) {
    $fecha_ini_catant = $rowper['inicio_sem'];
    $fecha_fin_catant = $rowper['fin_sem'];
    $fecha_ini_ocasant = $rowper['inicio_sem_oc'];
    $fecha_fin_ocasant = $rowper['fin_sem_oc'];
    $valor_puntoant = $rowper['valor_punto'];
    $smlvant = $rowper['smlv'];
   
}
  // Semanas catedra
    $fecha_inicio = new DateTime($fecha_ini_cat);
$fecha_fin = new DateTime($fecha_fin_cat);
  $intervalo = $fecha_inicio->diff($fecha_fin);

// Obtener el total de días y convertir a semanas
$dias = $intervalo->days -1;
$semanas_cat = ceil($dias / 7); // redondea hacia arriba
// Semanas ocasionales
$inicio_ocas = new DateTime($fecha_ini_ocas);
$fin_ocas = new DateTime($fecha_fin_ocas);
$dias_ocas = $inicio_ocas->diff($fin_ocas)->days-2;
$semanas_ocas = ceil($dias_ocas / 7);
    
     // Semanas catedra anterior
    $fecha_inicioant = new DateTime($fecha_ini_catant);
$fecha_finant = new DateTime($fecha_fin_catant);
  $intervaloant = $fecha_inicioant->diff($fecha_finant);

// Obtener el total de días y convertir a semanas
$diasant = $intervaloant->days - 1;
$semanas_catant = ceil($diasant / 7); // redondea hacia arriba
// Semanas ocasionales
$inicio_ocasant = new DateTime($fecha_ini_ocasant);
$fin_ocasant = new DateTime($fecha_fin_ocasant);
$dias_ocasant = $inicio_ocasant->diff($fin_ocasant)->days-1;
$semanas_ocasant = ceil($dias_ocasant / 7);
  
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Solicitudes</title>
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- jQuery y Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    
<!-- Cargar Bootstrap 5 y Font Awesome -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- jQuery (si es necesario) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Cargar solo Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    
    <style>
      
.cedula-nueva {
    color: #28a745; /* Verde */
    font-weight: bold;
}

/* Para mantener visibilidad en fondos amarillos */
.fondo-amarillo .cedula-nueva {
    color: #1a4d2b !important; /* Verde más oscuro */
        background-color: yellow; /* Amarillo claro */

}
         .cedula-eliminada {
        color: red !important;
        font-weight: bold;
    }
     
.cedula-en-otro-tipo {
    color: red;
    background-color: yellow;
}
        
        body {
            font-family: Arial, sans-serif;
            margin: 0px auto;
            padding: 20px;
            max-width: 95%; /* Establece el ancho máximo de la página */
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px; /* Agrega un margen inferior al encabezado */
        }
        .header h1 {
            flex: 1;
            text-align: center;
        }
        .header h2, .header h3 {
            flex: 1;
            text-align: left;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;border-radius: 8px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
              padding: 1px; /* Aumenta el espacio de relleno de las celdas */
            
        }
       th {
    background-color: #0066cc; /* Azul más claro */
    color: white;
}
        th {
    background-color: #F3F4F6; /* Gris claro neutro */
    color: #111827;
    font-weight: 600;
    border-bottom: 1px solid #E5E7EB;
}

     tr:nth-child(even) {
    background-color: #f9f9f9;
}
      
        .centered-column {
    text-align: center ;
}
        tr:hover {
    background-color: #e9f5ff;
    cursor: pointer;
}
        button {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
       .update-btn, .delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    margin: 0 2px;
    font-size: 12px;
    line-height: 1;
    color: #555;
}

/* Botón Editar - Usando azul institucional (#0066cc) con variantes */
.update-btn {
    color: #0066cc; /* Azul principal Unicauca */
    transition: all 0.3s ease;
}

.update-btn:hover {
    color: #004080; /* Azul más oscuro para hover */
    transform: scale(1.1);
}

/* Botón Eliminar - Usando rojo institucional (#cc0000) con variantes */
.delete-btn {
    color: #cc3333; /* Rojo institucional */
    transition: all 0.3s ease;
}

.delete-btn:hover {
    color: #990000; /* Rojo más oscuro para hover */
    transform: scale(1.1);
}

/* Opcional: Efecto adicional para mejor interactividad */
.update-btn:hover, .delete-btn:hover {
    text-shadow: 0 0 5px rgba(0,0,0,0.2);
    cursor: pointer;
}
         .estado-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
         .container {
            display: flex;
            justify-content: space-between; /* Espacia los divs uniformemente */
            align-items: stretch; /* Asegura que los divs se estiren a la misma altura */
              flex-wrap: wrap;

             gap: 20px; /* Espacio entre los divs */
            max-width: 95%; /* Ancho máximo del contenedor */
            margin: 0 auto; /* Centra el contenedor horizontalmente */
            padding: 10px; /* Espaciado interno del contenedor */
        }
     .box {
    flex: 0 0 49%; /* Fijo al 49% para dejar un pequeño espacio entre ellos */
    max-width: 49%;
    box-sizing: border-box; /* Incluye padding y borde dentro del ancho */
    /*height: 300px; /* O la altura fija que desees */
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
.box {
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
        
        .box-gray {
          }
         .box-white {
            background-color: white; /* Fondo gris claro */
            border-color: #ccc; /* Borde ligeramente más oscuro */
        }
        .btn-primary {
    height: 38px; /* Ajusta según el botón "Abrir Estado" */
    padding: 0 10px; /* Reduce el espacio vertical */
    font-size: 14px;
    line-height: 38px; /* Centra el texto verticalmente */
}
        
        @keyframes inflateButton {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.15);
    }
    100% {
        transform: scale(1);
    }
}

     .label-italic {
  font-style: italic;
}
        
        #textoObservacion {
    white-space: pre-line;
}
            
    </style>
    <script>
        function confirmarEnvio(count, tipo) {
            return confirm(` Se confirman ${count} profesores de  ${tipo}. ¿Desea continuar?`);
        }
    </script>
    
</head>
<body>
   <?php if ($tipo_usuario != 4): ?>
   <?php
if (isset($_POST['envia'])) {
    if ($_POST['envia'] === 'rcc') {
        $archivo_regreso = 'report_depto_comparativo_costos.php';
    } elseif ($_POST['envia'] === 'ce') {
        $archivo_regreso = 'comparativo_espejo.php';
    } elseif ($_POST['envia'] === 'rcec') {
        $archivo_regreso = 'report_depto_comparativo_costos_espejo.php';
    } else {
        $archivo_regreso = 'report_depto_comparativo.php';
    }
} else {
    $archivo_regreso = 'report_depto_comparativo.php';
}
?>



<?php endif; 
    
    
    // Función para obtener el nombre de la facultad
    function obtenerNombreFacultad($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT nombre_fac_min FROM facultad,deparmanentos WHERE
        PK_FAC = FK_FAC AND 
        deparmanentos.PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['nombre_fac_min'];
        } else {
            return "Facultad Desconocida";
        }
    }
             // Función para obtener el nombre de la facultad
    function obtenerIdFacultad($departamento_id)  {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT deparmanentos.FK_FAC  FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['FK_FAC'];
        } else {
            return "Departamento Desconocido";
        }
    }

    // Función para obtener el nombre del departamento
    function obtenerNombreDepartamento($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_nom_propio FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['depto_nom_propio'];
        } else {
            return "Departamento Desconocido";
        }
    }
function obtenerTRDDepartamento($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT trd_depto FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['trd_depto'];
        } else {
            return "Departamento Desconocido";
        }
    }

    ?>
     <style>
        /* Definición de colores Unicauca (asegúrate de que estén aquí o en tu archivo CSS principal) */
       
       
        /* --- NUEVOS ESTILOS PARA EL ENCABEZADO DE NAVEGACIÓN --- */
        .navigation-header {
            background-color: var(--unicauca-azul); /* Fondo azul Unicauca */
            color: var(--unicauca-blanco); /* Texto blanco */
            padding: 10px 20px; /* Ajusta el padding para que se vea bien */
            border-radius: 8px; /* Bordes redondeados */
            margin-bottom: 20px; /* Espacio debajo del encabezado */
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra sutil */
        }

        .navigation-header .btn-back {
            background-color: #e74c3c; /* Azul más oscuro para el botón */
            color: var(--unicauca-blanco);
            border: 1px solid var(--unicauca-azul-oscuro);
            padding: 8px 15px; /* Ajusta el padding del botón */
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            font-size: 0.9em; /* Tamaño de fuente ligeramente más pequeño */
            display: inline-flex; /* Para alinear icono y texto */
            align-items: center;
        }

        .navigation-header .btn-back:hover {
            background-color: var(--unicauca-primary); /* Vuelve al azul principal en hover */
            border-color: var(--unicauca-primary);
        }

        .navigation-header h2 {
            color: var(--unicauca-blanco); /* Asegura que el texto h2 sea blanco */
            margin-bottom: 0; /* Elimina margen inferior por defecto de h2 */
        }
        /* Ajuste para el color del departamento si tiene otro color, pero dentro del encabezado azul */
        .navigation-header .text-muted-white {
            color: rgba(255, 255, 255, 0.7) !important; /* Gris claro para el slash y nombre facultad */
        }
    </style>

 <div class="navigation-header">
        <div>
            <a href="<?= $archivo_regreso ?>?anio_semestre=<?= urlencode($anio_semestre) ?>"
               class="btn-back"
               title="Regresar a 'Gestión facultad'">
                <i class="fas fa-arrow-left me-2"></i> Regresar
            </a>
        </div>
        <div class="d-flex align-items-baseline gap-2">
            <h2 class="mb-0" style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                Fac. <?= mb_strimwidth(obtenerNombreFacultad($departamento_id), 0, 65, '...') ?>
            </h2>
            <span class="text-muted-white">/</span>
            <h2 class="mb-0" style="color: var(--unicauca-blanco); font-weight: 500;">
                <?= mb_strimwidth(obtenerNombreDepartamento($_POST['departamento_id']), 0, 65, '...') ?>
            </h2>
        </div>
        <div></div> </div>

    <div class="container">
        
        <div class="box">
<h4 class=""> <strong>
    <?php echo 'Periodo en revisión: ' . htmlspecialchars($_POST['anio_semestre']) . '.'; ?></strong>
</h4>       
            
        


    <?php
$facultad_id = obtenerIdFacultad($departamento_id);


    require 'cn.php';
    // Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                  FROM solicitudes  where solicitudes.estado <> 'an' OR solicitudes.estado IS NULL;";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}
       
     $todosCerrados = true; // Inicializar bandera
            
            
    $obtenerDeptoCerrado = obtenerDeptoCerrado($departamento_id,$anio_semestre); // si cero   no cerrado si 1  cerrado

            $totalItems = 0; // Inicializar el acumulador fuera del bucle principal
            $contadorHV = 0; // 🔹 Inicializar el contador
            $contadorVerdes = 0; // ▶ Inicializar contador de nuevos
            $contadorVerdesOc = 0;
            $contadorVerdesCa = 0;
//general para  fondo amarillo
            
       $cedulasPeriodoAnteriorGlobal = []; // Format: ['cedula' => 'tipo_docente_anterior']
$sqlPrevGlobal = "SELECT cedula, tipo_docente FROM solicitudes
                  WHERE facultad_id = '$facultad_id'
                  AND departamento_id = '$departamento_id'
                  AND anio_semestre = '$periodo_anterior'
                  AND (estado <> 'an' OR estado IS NULL)";
$resultPrevGlobal = $conn->query($sqlPrevGlobal);
if ($resultPrevGlobal) {
    while ($rowPrevGlobal = $resultPrevGlobal->fetch_assoc()) {
        $cedulasPeriodoAnteriorGlobal[$rowPrevGlobal['cedula']] = $rowPrevGlobal['tipo_docente'];
    }
} else {
    error_log("Error fetching cedulasPeriodoAnteriorGlobal: " . $conn->error);
}

$total_consolidado = 0;

$contadorVerdes = 0;
$contadorVerdesOc = 0; // For 'Ocasional' new professors
$contadorVerdesCa = 0; // For 'Catedra' new professors

// --- LOOP THROUGH EACH DOCENTE TYPE ---
while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];

    // --- Data specific to the CURRENT $tipo_docente for THIS loop iteration ---

    // Get all cedulas for the CURRENT tipo_docente in the CURRENT period.
    // This is useful for future checks like 'eliminada' status within this specific type.
    $cedulasPeriodoActualPorTipo = [];
    $sqlCedulasActualesPorTipo = "SELECT cedula FROM solicitudes
                                  WHERE facultad_id = '$facultad_id'
                                  AND departamento_id = '$departamento_id'
                                  AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_docente'
                                  AND (estado <> 'an' OR estado IS NULL)";
    $resultCedulasActualesPorTipo = $conn->query($sqlCedulasActualesPorTipo);
    if ($resultCedulasActualesPorTipo) {
        while ($rowCedula = $resultCedulasActualesPorTipo->fetch_assoc()) {
            $cedulasPeriodoActualPorTipo[] = $rowCedula['cedula'];
        }
    } else {
        error_log("Error fetching cedulasPeriodoActualPorTipo for $tipo_docente: " . $conn->error);
    }

    // Get all cedulas for the CURRENT tipo_docente in the PREVIOUS period.
    // This is specifically used to determine if a professor is 'NEW' for *this type of vinculación*.
    $cedulasPeriodoAnteriorPorTipoActual = [];
    $sqlCedulasAnterioresPorTipo = "SELECT cedula FROM solicitudes
                                    WHERE facultad_id = '$facultad_id'
                                    AND departamento_id = '$departamento_id'
                                    AND anio_semestre = '$periodo_anterior' AND tipo_docente = '$tipo_docente'
                                    AND (estado <> 'an' OR estado IS NULL)";
    $resultCedulasAnterioresPorTipo = $conn->query($sqlCedulasAnterioresPorTipo);
    if ($resultCedulasAnterioresPorTipo) {
        while ($rowCedula = $resultCedulasAnterioresPorTipo->fetch_assoc()) {
            $cedulasPeriodoAnteriorPorTipoActual[] = $rowCedula['cedula'];
        }
    } else {
        error_log("Error fetching cedulasPeriodoAnteriorPorTipoActual for $tipo_docente: " . $conn->error);
    }


    // --- MAIN QUERY TO GET PROFESSORS FOR THE CURRENT TABLE ---
    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento
            FROM solicitudes
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
            ORDER BY solicitudes.nombre ASC";

    $result = $conn->query($sql);


    // --- HTML OUTPUT FOR THE SECTION HEADER AND BUTTON ---
    echo "<div class='box-gray'>";
    echo "<div class='estado-container'>
        <h5 class='mb-0'>Vinculación: $tipo_docente (";

    if ($tipo_docente == 'Catedra') {
        $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
        echo "<strong>" . ucfirst(strtolower($estadoDepto)) . ")</strong> - " . $semanas_cat . " semanas ";
    } else {
        $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose);
        echo "<strong>" . ucfirst(strtolower($estadoDepto)) . ")</strong> - " . $semanas_ocas . " semanas ";
    }
    echo "</h5>";

    if ($tipo_usuario == 1) { // Assuming $tipo_usuario is defined
        echo "
        <form action='nuevo_registro_admin.php' method='GET' class='mb-0'>
            <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
            <input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>
            <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
            <button type='submit' class='btn btn-outline-success btn-sm d-flex align-items-center gap-1' title='Agregar Profesor'>
                <i class='fas fa-user-plus'></i> Agregar Profesor
            </button>
        </form>";
    }
    echo "</div>";

    // Obtener el conteo de profesores para este tipo_docente
    $sqlCount = "SELECT COUNT(*) as count FROM solicitudes WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    $resultCount = $conn->query($sqlCount);
    $count = $resultCount->fetch_assoc()['count'];

    // --- TABLE STRUCTURE AND HEADERS ---
    if ($result && $result->num_rows > 0) { // Ensure query was successful and has rows
        echo "<table border='1'>
        <thead>
            <tr>
                <th rowspan='2'>Ítem</th>
                <th rowspan='2'>Cédula</th>
                <th rowspan='2'>Nombre</th>";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }
        if ($tipo_usuario == 1) {
            echo "<th colspan='2'>Acciones</th>";
        }
        echo "<th rowspan='2'>Puntos</th>";
        echo "<th rowspan='2'>Proyec</th>";
        echo "</tr>
        <tr>";

        if ($tipo_docente == "Ocasional") {
            echo "
                <th title='Sede Popayán'>Pop</th>
                <th title='Sede Regionalización'>Reg</th>
            ";
        } elseif ($tipo_docente == "Catedra") {
            echo "
                <th title='Horas en Sede Popayán'>Pop</th>
                <th title='Horas en Sede Regionalización'>Reg</th>
            ";
        }
        if ($tipo_usuario == 1) {
            echo "
                <th>Supr</th>
                <th>Edit</th>
            ";
        }
        echo "</tr>
        </thead>
        <tbody>"; // Added tbody for better HTML structure

        $item = 1; // Initialize item counter for this table
        $todosLosRegistrosValidos = true; // Assuming this flag's purpose remains

        // Assuming obtener_acta function exists and returns an array or 0
        $datos_acta = obtener_acta($anio_semestre, $departamento_id);
        $num_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['acta_periodo']) : "";
        $fecha_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['fecha_acta']) : "";
        $total_proyect = 0; // Initialize variable for accumulating total

        // --- LOOP TO DISPLAY EACH PROFESSOR'S ROW ---
        while ($row = $result->fetch_assoc()) {
            $cedula = $row['cedula'];
            $claseFila = '';    // Reset for each row
            $claseTexto = '';   // Reset for each row
            $tooltipText = '';  // Reset for each row

            // --- DETERMINE PROFESSOR'S STATUS FOR THIS ROW ---

            // 1. Is this a "cambio de vinculación"?
            //    - Check if the professor existed in the previous period (globally, regardless of type)
            //    - AND if their previous type was DIFFERENT from the current $tipo_docente for this table.
            $cambioTipo = false;
            if (isset($cedulasPeriodoAnteriorGlobal[$cedula])) {
                if ($cedulasPeriodoAnteriorGlobal[$cedula] !== $tipo_docente) {
                    $cambioTipo = true;
                }
            }

            // 2. Is this professor "new" to this specific type of vinculación in the current period?
            //    - Check if they were NOT present in the previous period's list for THIS specific $tipo_docente.
            $esNueva = !in_array($cedula, $cedulasPeriodoAnteriorPorTipoActual);

            // --- APPLY CSS CLASSES AND SET THE SINGLE TOOLTIP BASED ON PRIORITY ---
            if ($cambioTipo) {
                // Priority 1: Type change (yellow background for row, darker green text)
                $claseFila = 'fondo-amarillo';
                $claseTexto = 'cedula-nueva'; // Your CSS will make this dark green
                $tooltipText = 'Este profesor tuvo vinculación temporal diferente en el periodo anterior ('.$periodo_anterior.')';
            } elseif ($esNueva) {
                // Priority 2: New professor (only light green text) - only if NOT a type change
                $claseTexto = 'cedula-nueva'; // Apply light green text class
                $tooltipText = 'Profesor nuevo para este periodo, no registrado en el periodo anterior ('.$periodo_anterior.')';

                // Increment counters for 'new' professors.
                // Ensure these counters are initialized with a scope that makes sense for your needs.
                $contadorVerdes++;
                if ($tipo_docente == 'Ocasional') {
                    $contadorVerdesOc++;
                } else {
                    $contadorVerdesCa++;
                }
            }
            // If neither condition is met, $claseFila, $claseTexto, and $tooltipText remain empty.

            // --- PREPARE THE 'title' ATTRIBUTE ---
            $titleAttribute = '';
            if (!empty($tooltipText)) {
                $titleAttribute = "title=\"" . htmlspecialchars($tooltipText) . "\"";
            }

            // --- OUTPUT THE TABLE ROW AND ITS CELLS ---
            echo "<tr class='$claseFila' $titleAttribute>"; // Apply row class and tooltip
            echo "<td>" . $item . "</td>";
            echo "<td style='text-align: left;' class='$claseTexto'>" . htmlspecialchars($cedula) . "</td>"; // Apply text class
            echo "<td style='text-align: left;' class='$claseTexto'>" . htmlspecialchars($row["nombre"]) . "</td>"; // Apply text class
    if ($tipo_docente == "Ocasional") {
        echo "<td>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
    
    if ($tipo_docente == "Catedra") {
        $horas = ($row["horas"] == 0) ? "" : htmlspecialchars($row["horas"]);
        $horas_r = ($row["horas_r"] == 0) ? "" : htmlspecialchars($row["horas_r"]);
        echo "<td>" . $horas . "</td>
              <td>" . $horas_r . "</td>";
    }
    if ($tipo_usuario == 1) {
      echo "<td>";
   
echo "
<form action='eliminar_admin.php' method='POST' class='delete-form' style='display:inline;'>
    <input type='hidden' name='id_solicitud' value='".htmlspecialchars($row["id_solicitud"])."'>
    <input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>
    <input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>
    <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
    <input type='hidden' name='anio_semestre_anterior' value='".htmlspecialchars($periodo_anterior)."'>

    <input type='hidden' name='tipo_docente' value='".htmlspecialchars($tipo_docente)."'>
    <input type='hidden' name='motivo_eliminacion' class='motivo-input' value=''>
    <!-- tipo_eliminacion se agregará dinámicamente -->
    <button type='submit' class='delete-btn' title='Eliminar registro'>
        <i class='fas fa-trash fa-sm'></i>
    </button>
</form>";
   
        echo "</td><td>";
    
            echo "
                <form action='actualizar_admin.php' method='GET' style='display:inline;'>
                    <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                    <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                    <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                    <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                   <input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>

                    <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                    <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                </form></td>";
        
    }
            echo "<td>" . $row["puntos"] . "</td>";
 if ($tipo_docente == "Catedra")   
    {

                  $asignacion_total= $row["puntos"]*$valor_punto *($row["horas"]+$row["horas_r"])*$semanas_cat;
                $asignacion_mes=$row["puntos"]*$valor_punto*($row["horas"] +$row["horas_r"])*4;
                $prima_navidad = $asignacion_mes*3/12;
                $indem_vacaciones = $asignacion_mes*$dias/360;
                $indem_prima_vacaciones = $indem_vacaciones*2/3;
                $cesantias =($asignacion_total + $prima_navidad)/12;
                $total_devengos=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones+$cesantias;
             //eps
                if ($asignacion_mes < $smlv){
                $valor_base = ($smlv * $dias / 30) * 8.5 / 100;
            } else {
                $valor_base = round($asignacion_total * 8.5 / 100, 0);
            }

            // Redondear al múltiplo de 100 más cercano
            $eps = round($valor_base, -2);

                    //pension

            // Cálculo principal
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * (12 / 100);
            } else {
                $valor_base = round($asignacion_total * (12 / 100), 0);
            }

            // Redondear al múltiplo de 100 más cercano
            $afp = round($valor_base, -2);

                    //arp
                    $porcentaje = 0.522 / 100;

            // Lógica del cálculo
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * $porcentaje;
            } else {
                $valor_base = round($asignacion_total * $porcentaje, 0);
            }

            // Redondeo al múltiplo de 100 más cercano
            $arl = round($valor_base, -2);

                    //comfaucaua
            // Porcentaje a aplicar
            $porcentaje = 4 / 100;

            // Cálculo condicional
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * $porcentaje;
            } else {
                $valor_base = round($asignacion_total * $porcentaje, 0);
            }

            // Redondear al múltiplo de 100 más cercano
            $cajacomp = round($valor_base, -2);

                    // icbf
            $porcentaje = 3 / 100;

            // Cálculo condicional
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * $porcentaje;
            } else {
                $valor_base = round($asignacion_total * $porcentaje, 0);
            }

            // Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
            $icbf = round($valor_base, -2);
                    $total_aportes= $eps +$afp+$arl+$cajacomp+$icbf;
                    $gran_total = $total_devengos+$total_aportes;

    }
    
    
    else {        
             //calculo  si $tipo_docente <> "Catedra"

                $horas = 0;
                $mesesocas = intval($semanas_ocas / 4.33)-1; // 4.33 semanas ≈ 1 mes
                // Asegurarse que los índices existen y son iguales a "MT" o "TC"
                if (($row["tipo_dedicacion"] == "MT") || ($row["tipo_dedicacion_r"] == "MT")) {
                    $horas = 20;
                } elseif (($row["tipo_dedicacion"] == "TC") || ($row["tipo_dedicacion_r"] == "TC")) {
                    $horas = 40;
                }

                // Calculo de la asignación mensual y total
$asignacion_mes = round($row["puntos"] * $valor_punto * ($horas / 40), 0);
                $asignacion_total = $asignacion_mes * $dias_ocas / 30;


                $prima_navidad = $asignacion_mes*$mesesocas/12;
                $indem_vacaciones = $asignacion_mes*($dias_ocas)/360;
                $indem_prima_vacaciones = $asignacion_mes*(2/3)*(($dias_ocas)/360);
                $cesantias = round(($asignacion_total + $prima_navidad) / 12);
                $total_empleado=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones;
             //eps
                $eps = round(($asignacion_total * 8.5) / 100);

                    //pension



            // Redondear al múltiplo de 100 más cercano
             $afp = round(($asignacion_total * 12) / 100);


            // Redondeo al múltiplo de 100 más cercano
            $arl =round(($asignacion_total * 0.522) / 100,-2);

                    //comfaucaua

            // Redondear al múltiplo de 100 más cercano
            $cajacomp = round(($asignacion_total * 4) / 100,-2);

                    // icbf

            // Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
                    $icbf = round(($asignacion_total * 3) / 100,-2);
                        $total_entidades=$cesantias+ $eps +$afp+$arl+$cajacomp+$icbf;


                    $gran_total = $total_empleado+$total_entidades;
    
    }
   // Asignar valores condicionales si es de cátedra
if ($tipo_docente == "Catedra") {
    $total_empleado_mostrar = $total_devengos;
    $total_entidades_mostrar = $total_aportes;
} else {
    $total_empleado_mostrar = $total_empleado;
    $total_entidades_mostrar = $total_entidades;
}

$title =

    "Detalle salarial\n" .

        "mese ocasionaels: " .$dias_ocas . "\n" .

    "Asignación mensual: $" . number_format($asignacion_mes, 0, ',', '.') . "\n" .
    "Asignación total: $" . number_format($asignacion_total, 0, ',', '.') . "\n" .
    "Prima de Navidad: $" . number_format($prima_navidad, 0, ',', '.') . "\n" .
    "Indem. Vacaciones: $" . number_format($indem_vacaciones, 0, ',', '.') . "\n" .
    "Indem. Prima Vacaciones: $" . number_format($indem_prima_vacaciones, 0, ',', '.') . "\n" .
    "Total empleado: $" . number_format($total_empleado_mostrar, 0, ',', '.') . "\n\n" .
    "Cesantías: $" . number_format($cesantias, 0, ',', '.') . "\n" .

    "Aportes a entidades\n" .
    "EPS: $" . number_format($eps, 0, ',', '.') . "\n" .
    "Pensión: $" . number_format($afp, 0, ',', '.') . "\n" .
    "ARL: $" . number_format($arl, 0, ',', '.') . "\n" .
    "Caja Compensación: $" . number_format($cajacomp, 0, ',', '.') . "\n" .
    "ICBF: $" . number_format($icbf, 0, ',', '.') . "\n" .
    "Total entidades: $" . number_format($total_entidades_mostrar, 0, ',', '.') . "\n\n" .

    "GRAN TOTAL: $" . number_format($gran_total, 0, ',', '.');
echo '<td  data-placement="right" title="' . htmlspecialchars($title, ENT_QUOTES) . '">
$' . number_format($gran_total / 1000000, 2) . ' M</td>';

$total_proyect += $gran_total;
    echo "</tr>";
    $item++;
}
       echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>";
echo "<td colspan='".($tipo_usuario == 1 ? ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ? 8 : 6) : ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ?  6: 4))."'>Subtotal</td>";
echo "<td>$".number_format($total_proyect/1000000, 2)." M</td>";
echo "</tr>";
    echo "</table>";

}
 else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";

    }
                   $total_consolidado += $total_proyect;

    // Cerrar conexión
    ?>
    <div class="d-flex justify-content-between mt-3">
     
         <?php
                 if ($estadoDepto == "ABIERTO" && $tipo_usuario == 3) {
    $mostrarFormulario = true;
} else {
    $mostrarFormulario = false;
}
// Vista

?>
    

        
   <?php if ($estadoDepto == "CERRADO") { 
    $envio_fac = obtenerenviof($facultad_id, $anio_semestre);
            
    $acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
 } ?>

    </div>
            
    </div>      <br>   
    <?php 
    }
// ... (código anterior, después del cierre de la tabla)

echo '<div style="display: table; width: 100%; margin-top: 5px;">
        <div style="display: table-row; font-weight: bold; background-color: #f2f2f2;">
            <div style="display: table-cell; text-align: left; padding: 8px; border: 1px solid #ddd;">
                 Total Consolidado:
            </div>
            <div style="display: table-cell; text-align: right; padding: 8px; border: 1px solid #ddd;">
                <span data-toggle="tooltip" data-placement="left" title="Valor total: $' . number_format($total_consolidado, 0, ',', '.') . '">
                    $' . number_format($total_consolidado / 1000000, 2) . ' M
                </span>
            </div>
        </div>
      </div>';

// Inicializar tooltips (si usas Bootstrap)
echo '<script>
$(document).ready(function(){
    $(\'[data-toggle="tooltip"]\').tooltip(); 
});
</script>'; 
   // $conn->close();
            
    ?>
       
    </div>
      
<!-- Botón "Ver Departamento" -->
        <div class="box">
            <h4 class=""><strong> Periodo anterior: <?php echo htmlspecialchars($periodo_anterior); ?></strong></h4>

<?php
// Segundo ciclo para mostrar el periodo anterior
$resultadotipo->data_seek(0); // Reinicia el puntero del resultado para volver a recorrerlo
    $contadorRojos = 0; // ▶ Inicializar contador de eliminados
 $contadorRojosOc= 0;
        $contadorRojosCa= 0;
            
     $cedulasGlobalesPeriodoActual = [];
$sqlCedulasGlobalesActuales = "SELECT cedula FROM solicitudes  
                      WHERE facultad_id = '$facultad_id' 
                      AND departamento_id = '$departamento_id' 
                      AND anio_semestre = '$anio_semestre'
                      AND (estado <> 'an' OR estado IS NULL)";
$resultGlobales = $conn->query($sqlCedulasGlobalesActuales);
while ($rowCedula = $resultGlobales->fetch_assoc()) {
    $cedulasGlobalesPeriodoActual[] = $rowCedula['cedula'];
}       
            $total_cosolidado_ant=0;

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];
    // ▶ 1. Obtener todas las cédulas del período actual para comparar
$cedulasPeriodoActual = [];
$sqlCedulasActuales = "SELECT cedula FROM solicitudes 
                      WHERE facultad_id = '$facultad_id' 
                      AND departamento_id = '$departamento_id' 
                      AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente'
                      AND (estado <> 'an' OR estado IS NULL)";
$resultCedulasActuales = $conn->query($sqlCedulasActuales);
while ($rowCedula = $resultCedulasActuales->fetch_assoc()) {
    $cedulasPeriodoActual[] = $rowCedula['cedula'];
}          
 // 1. Obtener cédulas del período ANTERIOR (para comparación)
$cedulasPeriodoAnterior = [];
$sqlCedulasAnteriores = "SELECT cedula FROM solicitudes 
                        WHERE facultad_id = '$facultad_id' 
                        AND departamento_id = '$departamento_id' 
                        AND anio_semestre = '$periodo_anterior' and tipo_docente = '$tipo_docente'
                        AND (estado <> 'an' OR estado IS NULL)";
$resultCedulasAnteriores = $conn->query($sqlCedulasAnteriores);
while ($rowCedula = $resultCedulasAnteriores->fetch_assoc()) {
    $cedulasPeriodoAnterior[] = $rowCedula['cedula'];
}           
    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$periodo_anterior' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL) 
            ORDER BY solicitudes.nombre ASC";

    $result = $conn->query($sql);

    echo "<div class='box-gray'>";
    echo "<div class='estado-container'>
        <h5 class='mb-0'>Vinculación: " . $tipo_docente . " (Periodo anterior) - " . 
        (($tipo_docente === "Ocasional") ? $semanas_ocasant : $semanas_catant) . " semanas" . 
        "</h5>
      </div>";
    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th rowspan='2'>Ítem</th>
                    <th rowspan='2'>Cédula</th>
                    <th rowspan='2'>Nombre</th>
                  ";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }

        echo "<th rowspan='2'>Puntos</th>";
                echo "<th rowspan='2'>Proyec</th>";

        echo "</tr>";

        if ($tipo_docente == "Ocasional") {
            echo "<tr><th>Pop</th><th>Reg</th></tr>";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr><th>Pop</th><th>Reg</th></tr>";
        }
    
        $item = 1;
       $total_proyectant = 0; // Inicializar variable para acumular el total


while ($row = $result->fetch_assoc()) {
    $cedula = $row['cedula'];

    // ▶ Verificar si está en el tipo_docente actual
    $cedulaEliminada = !in_array($cedula, $cedulasPeriodoActual);

    // ▶ Verificar si está en otro tipo_docente (pero sí está en el global)
    $cedulaEstaEnOtroTipo = $cedulaEliminada && in_array($cedula, $cedulasGlobalesPeriodoActual);

    // ▶ Definir clase CSS y texto del tooltip según el caso
    $tooltipText = ''; // Initialize tooltip text
    if ($cedulaEstaEnOtroTipo) {
        $claseRoja = 'cedula-en-otro-tipo'; // fondo amarillo, texto rojo
        $tooltipText = 'Cambio de vinculación para el periodo actual ('.$anio_semestre.')'; // Tooltip for 'cedula-en-otro-tipo'
    } elseif ($cedulaEliminada) {
        $claseRoja = 'cedula-eliminada'; // solo texto rojo
        $tooltipText = 'Profesor no vinculado para el periodo actual ('.$anio_semestre.')'; // Tooltip for 'cedula-eliminada'
    } else {
        $claseRoja = '';
        // $tooltipText remains empty for no special class
    }

    // ▶ Contadores
    if ($cedulaEliminada) {
        $contadorRojos++;
        if ($tipo_docente == 'Ocasional') {
            $contadorRojosOc++;
        } else {
            $contadorRojosCa++;
        }
    }

    echo "<tr>";
    echo "<td>" . $item . "</td>";

    // *** MODIFICADO: AÑADIR EL ATRIBUTO 'title' DINÁMICAMENTE ***
    // Solo añade el atributo title si hay texto para el tooltip
    $titleAttribute = !empty($tooltipText) ? "title='" . htmlspecialchars($tooltipText) . "'" : '';

    echo "<td style='text-align: left;' class='$claseRoja' $titleAttribute>" . htmlspecialchars($cedula) . "</td>";
    echo "<td style='text-align: left;' class='$claseRoja' $titleAttribute>" . htmlspecialchars($row["nombre"]) . "</td>";

    if ($tipo_docente == "Ocasional") {
        echo "<td>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
     if ($tipo_docente == "Catedra") {
                $horas = $row["horas"] == 0 ? "" : htmlspecialchars($row["horas"]);
                $horas_r = $row["horas_r"] == 0 ? "" : htmlspecialchars($row["horas_r"]);
                echo "<td>$horas</td><td>$horas_r</td>";
            }

    // Poner los puntos al final
echo "<td>" . $row["puntos"] . "</td>";
 if ($tipo_docente == "Catedra") {   
    //calculo catedra si $tipo_docente == "Catedra"
   $asignacion_total= $row["puntos"]*$valor_puntoant *($row["horas"] + $row["horas_r"])*$semanas_catant;
     
         $mesescat = intval($semanas_catant / 4.33); // 4.33 semanas ≈ 1 mes

    $asignacion_mes=$row["puntos"]*$valor_puntoant *($row["horas"] +$row["horas_r"])*4;
    $prima_navidad = $asignacion_mes*$mesescat/12;
    $indem_vacaciones = $asignacion_mes*$diasant/360;
    $indem_prima_vacaciones = $indem_vacaciones*2/3;
    $cesantias =($asignacion_total + $prima_navidad)/12;
    $total_devengos=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones+$cesantias;
 //eps
    if ($asignacion_mes < $smlvant){
    $valor_base = ($smlvant * $diasant / 30) * 8.5 / 100;
} else {
    $valor_base = round($asignacion_total * 8.5 / 100, 0);
}

// Redondear al múltiplo de 100 más cercano
$eps = round($valor_base, -2);
        
        //pension

// Cálculo principal
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * (12 / 100);
} else {
    $valor_base = round($asignacion_total * (12 / 100), 0);
}

// Redondear al múltiplo de 100 más cercano
$afp = round($valor_base, -2);
        
        //arp
        $porcentaje = 0.522 / 100;

// Lógica del cálculo
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * $porcentaje;
} else {
    $valor_base = round($asignacion_total * $porcentaje, 0);
}

// Redondeo al múltiplo de 100 más cercano
$arl = round($valor_base, -2);
    
        //comfaucaua
// Porcentaje a aplicar
$porcentaje = 4 / 100;

// Cálculo condicional
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * $porcentaje;
} else {
    $valor_base = round($asignacion_total * $porcentaje, 0);
}

// Redondear al múltiplo de 100 más cercano
$cajacomp = round($valor_base, -2);
        
        // icbf
$porcentaje = 3 / 100;

// Cálculo condicional
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * $porcentaje;
} else {
    $valor_base = round($asignacion_total * $porcentaje, 0);
}

// Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
$icbf = round($valor_base, -2);
        $total_aportes= $eps +$afp+$arl+$cajacomp+$icbf;
        $gran_total = $total_devengos+$total_aportes;
    
 }
    else {
    
    // si no  si ocasioan ::
 //calculo catedra si $tipo_docente <> "Catedra"
    // Cálculo principal
// Inicializar horas
    $horas = 0;
    $mesesocas = intval($semanas_ocasant / 4.33); // 4.33 semanas ≈ 1 mes
    // Asegurarse que los índices existen y son iguales a "MT" o "TC"
    if (($row["tipo_dedicacion"] == "MT") || ($row["tipo_dedicacion_r"] == "MT")) {
        $horas = 20;
    } elseif (($row["tipo_dedicacion"] == "TC") || ($row["tipo_dedicacion_r"] == "TC")) {
        $horas = 40;
    }

    // Calculo de la asignación mensual y total
    $asignacion_mes = $row["puntos"] * $valor_puntoant * ($horas / 40);
    $asignacion_total = $asignacion_mes * $dias_ocasant / 30;
    
    
    $prima_navidad = $asignacion_mes*$mesesocas/12;
    $indem_vacaciones = $asignacion_mes*$dias_ocasant/360;
    $indem_prima_vacaciones = $asignacion_mes*(2/3)*($dias_ocasant/360);
    $cesantias = round(($asignacion_total + $prima_navidad) / 12);
    $total_empleado=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones;
 //eps
    $eps = round(($asignacion_total * 8.5) / 100);

        //pension



// Redondear al múltiplo de 100 más cercano
 $afp = round(($asignacion_total * 12) / 100);
        
       
// Redondeo al múltiplo de 100 más cercano
$arl =round(($asignacion_total * 0.522) / 100,-2);
    
        //comfaucaua

// Redondear al múltiplo de 100 más cercano
$cajacomp = round(($asignacion_total * 4) / 100,-2);
        
        // icbf

// Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
        $icbf = round(($asignacion_total * 3) / 100,-2);
            $total_entidades=$cesantias+ $eps +$afp+$arl+$cajacomp+$icbf;
 

        $gran_total = $total_empleado+$total_entidades;
    
    }
$title =
    //"Variables base\n" .
    //"Horas: " . $horas . "\n" .
    //"Meses ocasionales: " . $mesesocas . "\n" .
    //"Valor punto: $" . number_format($valor_puntoant, 0, ',', '.') . "\n" .
    //"Semanas ocasional: " . $semanas_ocas . "\n" .
    //"días: " . $dias_ocas . "\n\n" .

    "Detalle salarial\n" .
    "Asignación mensual: $" . number_format($asignacion_mes, 0, ',', '.') . "\n" .
    "Asignación total: $" . number_format($asignacion_total, 0, ',', '.') . "\n" .
    "Prima de Navidad: $" . number_format($prima_navidad, 0, ',', '.') . "\n" .
    "Indem. Vacaciones: $" . number_format($indem_vacaciones, 0, ',', '.') . "\n" .
    "Indem. Prima Vacaciones: $" . number_format($indem_prima_vacaciones, 0, ',', '.') . "\n" .
    "Cesantías: $" . number_format($cesantias, 0, ',', '.') . "\n" .
    ($tipo_docente == "Catedra" ? "Total devengos" : "Total empleado") . ": $" . number_format($total_empleado_mostrar, 0, ',', '.') . "\n\n" .

    "Aportes a entidades\n" .
    "EPS: $" . number_format($eps, 0, ',', '.') . "\n" .
    "Pensión: $" . number_format($afp, 0, ',', '.') . "\n" .
    "ARL: $" . number_format($arl, 0, ',', '.') . "\n" .
    "Caja Compensación: $" . number_format($cajacomp, 0, ',', '.') . "\n" .
    "ICBF: $" . number_format($icbf, 0, ',', '.') . "\n" .
    ($tipo_docente == "Catedra" ? "Total aportes" : "Total entidades") . ": $" . number_format($total_entidades_mostrar, 0, ',', '.') . "\n\n" .

    "GRAN TOTAL: $" . number_format($gran_total, 0, ',', '.');

echo "<td title=\"" . htmlspecialchars($title) . "\">$" . number_format($gran_total / 1000000, 2) . " M</td>";
    $total_proyectant += $gran_total;

    echo "</tr>";
    $item++;
    
}
// Fila de subtotal
echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>";
echo "<td colspan='".($tipo_usuario == 1 ? ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ? 6 : 4) : ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ? 6 : 3))."'>Subtotal</td>";
echo "<td>$".number_format($total_proyectant/1000000, 2)." M</td>";
echo "</tr>";
        echo "</table>";
        

    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados para el periodo anterior.</p>";
    }
                   $total_cosolidado_ant += $total_proyectant;

    echo "</div><br>";
    
}
            
            // ... (código anterior, después del cierre de la tabla)

echo '<div style="display: table; width: 100%; margin-top: 5px;">
        <div style="display: table-row; font-weight: bold; background-color: #f2f2f2;">
            <div style="display: table-cell; text-align: left; padding: 8px; border: 1px solid #ddd;">
                 Total Consolidado:
            </div>
            <div style="display: table-cell; text-align: right; padding: 8px; border: 1px solid #ddd;">
                <span data-toggle="tooltip" data-placement="left" title="Valor total: $' . number_format($total_cosolidado_ant, 0, ',', '.') . '">
                    $' . number_format($total_cosolidado_ant / 1000000, 2) . ' M
                </span>
            </div>
        </div>
      
      </div>';

// Inicializar tooltips (si usas Bootstrap)
echo '<script>
$(document).ready(function(){
    $(\'[data-toggle="tooltip"]\').tooltip(); 
});
</script>';
            

?>
</div>    
   
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.20/dist/js/bootstrap.bundle.min.js"></script>
            
<script>
    
document.querySelectorAll('.delete-form').forEach(function(form) {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // 1. Confirmación básica
    if (!confirm('¿Está seguro de eliminar este registro permanentemente?')) {
      return;
    }

    // 2. Pedir motivo descriptivo
    const motivo = prompt('Evidencia/documento que justifica la eliminación (Ej: Oficio 123, email de solicitud):');
    if (motivo === null) return;
    if (motivo.trim() === '') {
      alert('Debe ingresar un motivo válido');
      return;
    }

    // 3. Mostrar selector de tipo de eliminación
    const tipoEliminacion = `
      <div style="padding:10px;background:#f8f9fa;border-radius:5px;">
        <h4 style="margin-top:0;">Tipo de Eliminación</h4>
        <select id="tipoEliminacionSelect" style="width:100%;padding:8px;margin-bottom:10px;">
          <option value="">-- Seleccione --</option>
          <option value="Ajuste de Matrículas">Ajuste de Matrículas</option>
          <option value="Fallecimiento">Fallecimiento</option>
          <option value="No legalizó">No legalizó</option>
          <option value="Otro">Otro</option>
          <option value="Reemplazos NN">Reemplazos NN</option>
          <option value="Renuncia">Renuncia</option>
<option value="Ajuste por VRA">Ajuste por VRA</option>
        </select>
        <button onclick="confirmarEliminacion()" style="background:#dc3545;color:white;border:none;padding:8px 15px;border-radius:4px;">Confirmar</button>
        <button onclick="cancelarEliminacion()" style="background:#6c757d;color:white;border:none;padding:8px 15px;border-radius:4px;margin-left:5px;">Cancelar</button>
      </div>
    `;

    // Crear modal temporal
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '50%';
    modal.style.left = '50%';
    modal.style.transform = 'translate(-50%, -50%)';
    modal.style.backgroundColor = 'white';
    modal.style.padding = '20px';
    modal.style.borderRadius = '5px';
    modal.style.boxShadow = '0 0 10px rgba(0,0,0,0.3)';
    modal.style.zIndex = '1000';
    modal.innerHTML = tipoEliminacion;
    modal.setAttribute('id', 'modalEliminacion');
    
    // Guardar referencia al formulario y motivo
    modal._form = form;
    modal._motivo = motivo;
    
    document.body.appendChild(modal);

    // Funciones para los botones del modal
    window.confirmarEliminacion = function() {
      const select = document.getElementById('tipoEliminacionSelect');
      const tipo = select.value;
      
      if (!tipo) {
        alert('Debe seleccionar un tipo de eliminación');
        return;
      }

      // Asignar valores al formulario
      const modal = document.getElementById('modalEliminacion');
      modal._form.querySelector('.motivo-input').value = modal._motivo;
      
      // Crear campo oculto para el tipo si no existe
      let tipoInput = modal._form.querySelector('input[name="tipo_eliminacion"]');
      if (!tipoInput) {
        tipoInput = document.createElement('input');
        tipoInput.type = 'hidden';
        tipoInput.name = 'tipo_eliminacion';
        modal._form.appendChild(tipoInput);
      }
      tipoInput.value = tipo;

      // Eliminar modal y enviar formulario
      document.body.removeChild(modal);
      modal._form.submit();
    };

    window.cancelarEliminacion = function() {
      document.body.removeChild(document.getElementById('modalEliminacion'));
    };
  });
});
</script>
    
</body>
    
</html>
<?php       // Función para obtener el cierreo no de departamento
    
echo "<div style='margin-bottom: 10px; font-size: 0.9em;'>
  <strong>Nota:</strong> 
  <span style='color: green; font-weight: bold;'>En verde:</span> Profesores nuevos; (Ocasionales: {$contadorVerdesOc}; Cátedra: {$contadorVerdesCa}) - Total: {$contadorVerdes} &nbsp;|&nbsp;
  <span style='color: red; font-weight: bold;'>En rojo:</span> Profesores que ya no continúan. (Ocasionales: {$contadorRojosOc}, Cátedra: {$contadorRojosCa}) - Total: {$contadorRojos} &nbsp;|&nbsp;
  <span style='background-color: yellow; color: red; font-weight: bold;'>&nbsp;Cambio de vinculación&nbsp;</span>:  Profesores que cambian de tipo de vinculación en el periodo actual.
</div>  ";
// Calcular el porcentaje de cambio (manteniendo tus variables exactas)
$diferencia = $total_consolidado - $total_cosolidado_ant;
$porcentaje = ($total_cosolidado_ant != 0) 
    ? round(($diferencia / $total_cosolidado_ant) * 100, 1) 
    : 0;

// Determinar color y flecha (con colores invertidos como solicitaste)
if ($porcentaje > 0) {
    $color = "danger"; // Rojo para incremento
    $icono = "bi bi-arrow-up";
    $texto = "Incremento";
} elseif ($porcentaje < 0) {
    $color = "success"; // Verde para decremento
    $icono = "bi bi-arrow-down";
    $texto = "Decremento";
} else {
    $color = "secondary"; // Gris
    $icono = "bi bi-dash";
    $texto = "Estable";
}

// Mostrar el indicador (versión compacta en una sola línea)
echo <<<HTML
<div class="text-end mb-3">
    <span class="text-muted me-2">Variación en proyecto presupuestal:</span>
    <span class="badge bg-{$color}-subtle text-{$color}">
        <i class="{$icono} me-1"></i>
        $texto: <strong>".abs($porcentaje)."%</strong>
    </span>
</div>
HTML;
echo "<div>

</div>  ";


function obtenerCierreDeptoCatedra($departamento_id,$aniose) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_periodo.dp_estado_catedra FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo ='$aniose'";
        $result = $conn->query($sql);   
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dp_estado_catedra'] == 'ce') ? "CERRADO" : "ABIERTO";
        } else {
            return "estado depto Desconocida";
        }
    } // ocasional
    function obtenerCierreDeptoOcasional($departamento_id,$aniose) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_periodo.dp_estado_ocasional FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo ='$aniose'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dp_estado_ocasional'] == 'ce') ? "CERRADO" : "ABIERTO";
        } else {
            return "estado depto Desconocida";
        }
    } 
?>