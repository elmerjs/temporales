<?php
require('include/headerz.php');
require 'funciones.php';
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
 $aniose= $anio_semestre;
        $cierreperiodo = obtenerperiodo($anio_semestre);

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
    
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
    /* Unicauca Color Palette (Retained for Minimalism) */
    :root {
        --unicauca-blue-primary: #0047AB; /* Un azul vibrante pero con presencia */
        --unicauca-blue-dark: #002D72; /* Azul oscuro para elementos clave */
        --unicauca-blue-light: #F0F5FA; /* Un azul muy, muy pálido para fondos sutiles */
        --unicauca-red-primary: #CC3333; /* Un rojo más directo para acciones críticas */
        --unicauca-gray-light: #F8F8F8; /* Gris casi blanco para filas alternas */
        --unicauca-gray-border: #E0E0E0; /* Borde muy fino y sutil */
        --unicauca-text-dark: #333333; /* Texto principal oscuro */
        --unicauca-text-light: #777777; /* Texto secundario más suave */
        --unicauca-orange-primary: #FF9933; /* Naranja para énfasis de botón */
        --unicauca-orange-dark: #E68A00; /* Naranja más oscuro para hover */
    }

    body {
        font-family: 'Segoe UI', 'Roboto', 'Arial', sans-serif; /* Prioriza fuentes limpias y comunes */
        margin: 15px auto;
        padding: 15px;
        max-width: 95%;
        color: var(--unicauca-text-dark);
        background-color: #fcfdfe;
        line-height: 1.4;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid var(--unicauca-gray-border);
        padding-bottom: 10px;
    }

    .header h1 {
        flex: 1;
        text-align: center;
        color: var(--unicauca-blue-dark);
        font-size: 1.8rem;
        margin: 0;
        font-weight: 600;
    }

    .header h2, .header h3 {
        flex: 1;
        text-align: left;
        margin: 3px 0;
        color: var(--unicauca-text-dark);
        font-size: 1rem;
        font-weight: 500;
    }

    /* --- Minimalist Table Style --- */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 0.9rem; /* Slightly larger text for table content */
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        border-radius: 4px;
        overflow: hidden;
    }

    th, td {
        border: 1px solid var(--unicauca-gray-border);
        padding: 6px 10px; /* Reduced vertical padding for more compact rows */
        text-align: center;
        line-height: 1.3; /* Adjusted line height for compactness */
    }

    th {
        background-color: var(--unicauca-blue-dark);
        color: white;
        font-weight: 600;
        font-size: 0.85rem; /* Slightly larger header font */
        padding: 8px 12px; /* Adjusted padding for headers */
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    /* Filas alternas - casi invisibles */
    tr:nth-child(even) {
        background-color: var(--unicauca-gray-light);
    }

    /* Efecto hover - muy discreto */
    tr:hover {
        background-color: rgba(0, 71, 171, 0.05);
        transition: background-color 0.15s ease;
    }

    /* --- Button Styles (More Compact) --- */
    button {
        padding: 6px 12px;
        margin: 2px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .update-btn {
        background-color: var(--unicauca-blue-primary);
        color: white;
    }
    .update-btn:hover {
        background-color: #003A90;
        transform: translateY(-0.5px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .delete-btn {
        background-color: var(--unicauca-red-primary);
        color: white;
    }
    .delete-btn:hover {
        background-color: #B32D2D;
        transform: translateY(-0.5px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .estado-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .container {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 15px;
        max-width: 100%;
        margin: 0 auto;
        padding: 10px;
    }

    .box {
        flex-grow: 1;
        padding: 15px;
        border: 1px solid var(--unicauca-gray-border);
        text-align: center;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        background-color: white;
    }

    .box-gray {
        background-color: var(--unicauca-gray-light);
        border-color: var(--unicauca-blue-primary);
        margin-bottom: 20px;
        padding: 12px 15px;
        border-radius: 6px;
    }

    .btn-primary {
        background-color: var(--unicauca-blue-primary);
        color: white;
        height: 36px;
        padding: 0 12px;
        font-size: 0.9rem;
        line-height: 36px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .btn-primary:hover {
        background-color: #003A90;
        transform: translateY(-0.5px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    /* --- Cargue Masivo Button (Compact) --- */
    @keyframes pulseEffect {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); }
        100% { transform: scale(1); }
    }

    .btn-cargue-masivo {
        background: linear-gradient(to right, var(--unicauca-orange-primary), var(--unicauca-orange-dark));
        color: white;
        font-weight: 500;
        font-size: 0.95rem;
        padding: 8px 15px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
        border: none;
        display: inline-block;
        animation: pulseEffect 1s ease-in-out infinite;
    }

    .btn-cargue-masivo:hover {
        background: linear-gradient(to right, var(--unicauca-orange-dark), #CC6600);
        transform: scale(1.02);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .label-italic {
        font-style: italic;
        color: var(--unicauca-text-light);
    }

    #textoObservacion {
        white-space: pre-line;
    }

    /* Specific styles for the "FOR.45" download button (Highly Minimalist) */
    .download-btn {
        background-color: transparent;
        box-shadow: none;
        padding: 3px;
        margin: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        min-height: 24px;
    }
    .download-btn:hover {
        background-color: rgba(0, 71, 171, 0.05);
        box-shadow: none;
        transform: none;
    }
    .download-btn i {
        font-size: 1.0em !important;
        color: var(--unicauca-blue-primary) !important;
    }

    /* --- Modal Styling (More Compact) --- */
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    .modal-header {
        background-color: var(--unicauca-blue-dark);
        color: white;
        border-bottom: none;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        padding: 12px 15px;
    }
    .modal-title {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .modal-footer {
        border-top: 1px solid var(--unicauca-gray-border);
        padding: 10px 15px;
    }
    .btn-close {
        filter: invert(1);
        font-size: 0.9rem;
        padding: 0;
        margin: 0;
    }
    .form-control {
        border-radius: 4px;
        border: 1px solid var(--unicauca-gray-border);
        padding: 7px 10px;
        font-size: 0.9rem;
    }
    .form-label {
        font-weight: 500;
        color: var(--unicauca-text-dark);
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Checkbox for Visado */
    .individualCheckbox {
        transform: scale(1);
        margin: 0 3px;
        cursor: pointer;
    }
    .individualCheckbox:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Ensure Font Awesome and Roboto are linked in your HTML <head> */
    /*
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlalHfaZzK6Q4J5q+e3q9E4y5eN8/u2T/6+Z5/JpL9l+a/mD6y2Xz5L2o8w/7uK2r2+Z6a3d9U8p5g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Segoe+UI:wght@300;400;600&display=swap" rel="stylesheet">
    */
</style> <script>
        function confirmarEnvio(count, tipo) {
            return confirm(` Se confirman ${count} profesores de  ${tipo}. ¿Desea continuar?`);
        }
    </script>
    
</head>
<body>
   <?php if ($tipo_usuario != 3): ?>
    <span style="display: inline-block; padding: 4px 8px; border: 1px solid #ccc; background-color: #f8f9fa; border-radius: 3px;">
    <a href="report_depto_full.php?anio_semestre=<?= urlencode($anio_semestre) ?>" class="btn btn-light" title="Regresar a 'Gestión facultad'" style="text-decoration: none; color: inherit; padding: 2px 5px;">
        Regresar <i class="fas fa-arrow-left"></i>
    </a>
</span>
<?php endif; ?>

    <div class="container">
        
        <div class="box" >

    <h3>Facultad: <?php echo obtenerNombreFacultad($departamento_id). ' - '.obtenerNombreDepartamento($_POST['departamento_id']). '. Periodo:'.htmlspecialchars($_POST['anio_semestre']).'. '; 
        
        $nombre_fac=obtenerNombreFacultad($departamento_id);
        ?></h3>
            
            
        


    <?php
$facultad_id = obtenerIdFacultad($departamento_id);

    // Función para obtener el nombre de la facultad
    function obtenerNombreFacultad($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT nombre_fac_minb FROM facultad,deparmanentos WHERE
        PK_FAC = FK_FAC AND 
        deparmanentos.PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['nombre_fac_minb'];
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

    // Establecer conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

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
if ($obtenerDeptoCerrado!=1 && $tipo_usuario == 3) {
    echo "<div style='text-align: right;'>
            <label for='selectAll' style='cursor: pointer;'>
                <input type='checkbox' id='selectAll' 
                       title='Aplique este checkbox solo si está seguro de marcar masivamente los profesores' 
                       onclick='confirmSelection(this)'>
                <i>Visado masivo</i>
            </label>
          </div>";
}
  $totalItems = 0; // Inicializar el acumulador fuera del bucle principal
   $contadorHV = 0; // 🔹 Inicializar el contador

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];
 
    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL) order by solicitudes.nombre asc";

    $result = $conn->query($sql);
    
       
           echo "<div class='box-gray'>";         
         echo "<div class='estado-container'>
        <h3>Vinculación: ".$tipo_docente." (";
            if ($tipo_docente=='Catedra'){
                $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
            } else {
                $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose);
echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
            }
        echo ")</h3>";

if ($estadoDepto != 'CERRADO') {
    if ($tipo_usuario == 3) {
echo "
    <form action='nuevo_registro.php' method='GET'>
        <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
        <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
        <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
        <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>

        <div class='d-flex gap-2'>
            <button type='submit' class='btn btn-success'>
                <i class='fas fa-plus'></i> Agregar Profesor
            </button>

            <button type='button' class='btn btn-danger' 
                onclick=\"eliminarRegistros(
                    '" . htmlspecialchars($tipo_docente) . "', 
                    '" . htmlspecialchars($facultad_id) . "', 
                    '" . htmlspecialchars($departamento_id) . "', 
                    '" . htmlspecialchars($anio_semestre) . "'
                )\">
                <i class='fas fa-trash-alt'></i> Eliminar todos
            </button>
        </div>
    </form>";


        
    }

    // Cambiar la bandera si alguno no está cerrado
    $todosCerrados = false;
      

    
}

 echo "</div>";
    
    // Obtener el conteo de profesores
    $sqlCount = "SELECT COUNT(*) as count FROM solicitudes WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    $resultCount = $conn->query($sqlCount);
    $count = $resultCount->fetch_assoc()['count'];

    if ($result->num_rows > 0) {
          echo "<table border='1'>
            <tr>
                <th rowspan='2'>Ítem</th>
                <th rowspan='2'>Cédula</th>
                <th rowspan='2'>Nombre</th>";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }
        echo "<th colspan='2'>Hojas de vida</th>";

        // Mostrar/Ocultar la columna de acciones basado en el estado del departamento
        if ($estadoDepto != "CERRADO") {
            echo "<th colspan='3'>Acciones</th>";
        } else {
            echo "<th colspan='3' ></th>";
        }

        echo "</tr>";

        if ($tipo_docente == "Ocasional") {
         echo "<tr>
        <th title='Sede Popayán'>Pop</th>
        <th title='Sede Regionalización'>Reg</th>";
        } elseif ($tipo_docente == "Catedra") {
           echo "<tr>
        <th title='Horas en Sede Popayán'>Horas Pop</th>
        <th title='Horas en Sede Regionalización'>Horas Reg</th>";
}
echo "
        <th title='Anexa Hoja de Vida para nuevos aspirantes'>Anexa (nuevo)</th>
        <th title='Actualiza Hoja de Vida para aspirantes antiguos'>Actualiza (antiguo)</th>";

        // Mostrar/Ocultar las subcolumnas de acciones
        if ($estadoDepto != "CERRADO") {
          echo "<th>Eliminar</th> 
             <th>Editar</th>";
              if ($tipo_usuario == 3) { 
              
              echo "<th>Visado</th>";
if ($tipo_usuario == 3) {
    echo "<th style='display:none;'>FOR.45</th>";
}
              }
              else { echo "<th>Visado</th>"; }  
        } else {
            echo "<th style='display:none;'>Eliminar</th>
                  <th style='display:none;'>Editar</th>
                  <th>Visado</th>";                          
if ($tipo_usuario == 3) {
    echo "<th style='text-align: center; vertical-align: middle;' title='Formato FOR 45 - Revisión Requisitos Vinculación Docente'>FOR.45</th>";
}
        }

        echo "</tr>";

        $item = 1; // Inicializar el contador de ítems
                $todosLosRegistrosValidos = true; // Bandera para validar todos los registros
       $datos_acta = obtener_acta($anio_semestre, $departamento_id);

        // Si encuentra datos, asigna los valores, si no, deja vacío
        $num_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['acta_periodo']) : "";
        $fecha_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['fecha_acta']) : "";

        while ($row = $result->fetch_assoc()) {
             if ($row["anexa_hv_docente_nuevo"] == 'si' || $row["actualiza_hv_antiguo"] == 'si') {
        $contadorHV++; // 🔹 Incrementar el contador
    }
            echo "<tr>
        <td>" . $item . "</td> <!-- Usar el contador de ítems --> 
        <td style='text-align: left;'>" . htmlspecialchars($row["cedula"]) . "</td>
        <td style='text-align: left;'>" . htmlspecialchars($row["nombre"]) . "</td>
      ";

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

            echo "<td>" . htmlspecialchars($row["anexa_hv_docente_nuevo"]) . "</td>
                  <td>" . htmlspecialchars($row["actualiza_hv_antiguo"]) . "</td>";

            // Mostrar/Ocultar celdas de acciones basado en el estado del departamento
         if ($estadoDepto != "CERRADO") {
        echo "<td>";
        if ($tipo_usuario == 3) {
            echo "
                <form action='eliminar.php' method='POST' style='display:inline;'>
                    <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                    <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                    <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                    <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                    <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                    <button type='submit' class='delete-btn'><i class='fas fa-trash'></i></button>
                </form>";
        }
        echo "</td><td>";
        if ($tipo_usuario == 3) {
            echo "
                <form action='actualizar.php' method='GET' style='display:inline;'>
                    <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                    <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                    <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                    <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                    <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                    <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                </form>";
        }
        echo "</td><td>";
        // Checkbox siempre visible, pero deshabilitado si el usuario no es tipo 3
        $disabled = ($tipo_usuario == 3) ? "" : "disabled";
        echo "
            <form class='vistoBuenoForm' style='display:inline;'>
                <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row['id_solicitud']) . "'>
                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                <input type='checkbox' class='individualCheckbox' name='visto_bueno' value='1' " . ($row['visado'] ? 'checked' : '') . " $disabled>
            </form>";
        echo "</td>";
             
    } else {
                echo "<td style='display:none;'>
                        <form action='eliminar.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                            <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                            <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                            <button type='submit' class='delete-btn'><i class='fas fa-trash'></i></button>
                        </form>
                      </td>
                      <td style='display:none;'>
                        <form action='actualizar.php' method='GET' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                            <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                            <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                            <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                        </form>
                      </td>
                      <td >
                        <form action='update_visto_bueno.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                            <input type='checkbox' disabled name='visto_bueno' value='1' " . ($row["visado"] ? "checked" : "") . " onchange='this.form.submit()'>
                        </form>
                      </td>";
             
             
             if ($tipo_usuario == 3) {
    echo "<td class='centered-column'>
        <button type='button' class='download-btn btn btn-sm' 
            data-id-solicitud='" . htmlspecialchars($row["id_solicitud"]) . "' 
            data-departamento-id='" . htmlspecialchars($departamento_id) . "' 
            data-anio-semestre='" . htmlspecialchars($anio_semestre) . "' 
            data-numero-acta='" . htmlspecialchars($num_acta) . "' 
            data-fecha-acta='" . htmlspecialchars($fecha_acta) . "' 
            data-bs-toggle='modal' data-bs-target='#actaModal'>
            <i class='fa-solid fa-file-arrow-down' style='font-size:16px; color:#1A73E8;'></i>
        </button>  
    </td>";
}
             
            }
            echo "</tr>";
            $item++; // Incrementar el contador de ítems
        }
            $totalItems += ($item - 1); // Acumular el número de ítems para el total

        echo "</table>";
       
    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";
        if ($tipo_usuario == 3) {
    if ($tipo_usuario == 3 && $estadoDepto != 'CERRADO') {
echo '
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <a href="indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '" class="btn btn-cargue-masivo">
                <i class="fas fa-upload"></i> Cargue Masivo - Tipo: ' . htmlspecialchars($tipo_docente) . '
            </a>
        </div>
    </div>';
}
}
    }

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
if ($mostrarFormulario):
?>
    <form id="confirmForm" action="confirmar_tipo_d_depto.php" method="GET" onsubmit="return confirmarEnvio(<?php echo $count; ?>, '<?php echo $tipo_docente; ?>');">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-unlock"></i> Confirmar Profesores</button>
    </form>
<?php endif; ?>
        
   <?php if ($estadoDepto == "CERRADO") { 
    $envio_fac = obtenerenviof($facultad_id, $anio_semestre);
            
    $acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
if ($tipo_usuario == 3) { 
?>

    <form action="abrir_estado.php" method="POST">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
        <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($tipo_usuario); ?>">

        <button type="submit" class="btn btn-warning" title="Lista cerrada — haga clic para abrir y editar.">
    <i class="fas fa-lock"></i>
</button>
    </form>
<!--modal for45-->
 <!-- Modal -->
<div class='modal fade' id='actaModal' tabindex='-1' aria-labelledby='actaModalLabel' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='actaModalLabel'>Información del Acta</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='actaForm' action='for_45.php' method='GET'>
                    <input type='hidden' name='id_solicitud' id='modal_id_solicitud'>
                    <input type='hidden' name='departamento_id' id='modal_departamento_id'>
                    <input type='hidden' name='anio_semestre' id='modal_anio_semestre'>

                    <div class='mb-3'>
                        <label for='numero_acta' class='form-label'>No. de Acta</label>
                        <input type='text' class='form-control' id='numero_acta' name='numero_acta' required>
                    </div>

                    <div class='mb-3'>
                        <label for='fecha_actab' class='form-label'>Fecha Acta</label>
                        <input type='date' class='form-control' id='fecha_actab' name='fecha_actab' required>
                    </div>
                </form>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button>
                <button type='submit' form='actaForm' class='btn btn-primary'>Descargar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actaModal = document.getElementById('actaModal');

        actaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id_solicitud = button.getAttribute('data-id-solicitud');
            const departamento_id = button.getAttribute('data-departamento-id');
            const anio_semestre = button.getAttribute('data-anio-semestre');
            const numero_acta = button.getAttribute('data-numero-acta');
            const fecha_acta = button.getAttribute('data-fecha-acta');

            actaModal.querySelector('#modal_id_solicitud').value = id_solicitud;
            actaModal.querySelector('#modal_departamento_id').value = departamento_id;
            actaModal.querySelector('#modal_anio_semestre').value = anio_semestre;

            // Si existe número de acta, lo pone en el campo; si no, deja vacío
            actaModal.querySelector('#numero_acta').value = numero_acta || '';

            // Si existe fecha de acta, lo pone en el campo; si no, deja vacío
            actaModal.querySelector('#fecha_actab').value = fecha_acta || '';
        });

        document.getElementById('actaForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const form = this;

            setTimeout(() => {
                form.submit(); // Envía el formulario normalmente
                // No es necesario ocultar el modal aquí, ya que la recarga de la página lo hará.
            }, 500);

            // Después de un breve retardo para permitir la descarga (si es inmediata),
            // recarga la página principal.
            setTimeout(() => {
                window.location.reload();
            }, 1000); // Ajusta este tiempo según sea necesario para la descarga
        });
    });
</script>
        
    <script>
        // Mostrar el valor de la variable $tipo_docente en la consola
        console.log("Tipo de Docente en abrir: <?php echo htmlspecialchars($tipo_docente); ?>");
    </script>
 <?php } ?>
    <?php if ($acepta_vra === '2' && ($tipo_usuario == 3)) { ?>

        <!-- Botón "Solicitud Novedad" -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novedadModal_<?php echo htmlspecialchars($tipo_docente); ?>" style="display: inline-block;">
            <i class="fas fa-file-alt"></i> Novedad
        </button>

        <!-- Modal Dinámico por Tipo de Docente -->
        <div class="modal fade" id="novedadModal_<?php echo htmlspecialchars($tipo_docente); ?>" tabindex="-1" aria-labelledby="novedadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="novedadModalLabel">Seleccione Tipo de Novedad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="procesar_novedad.php" method="POST" id="novedadForm_<?php echo htmlspecialchars($tipo_docente); ?>">
                            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
                            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                            <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
                            <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($tipo_usuario); ?>">

                            <div class="mb-3">
                                <label for="tipo_novedad_<?php echo htmlspecialchars($tipo_docente); ?>" class="form-label">Tipo de Novedad</label>
                                <select name="tipo_novedad" id="tipo_novedad_<?php echo htmlspecialchars($tipo_docente); ?>" class="form-select" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="Eliminar">Eliminar</option>
                                    <option value="Modificar">Modificar</option>
                                    <option value="Adicionar">Adicionar</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Continuar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Mostrar el valor de la variable $tipo_docente en la consola
            console.log("Tipo de Docente en modal: <?php echo htmlspecialchars($tipo_docente); ?>");
        </script>

    <?php } ?>

<?php } ?>

    </div>
            
    </div>      <br>   
    <?php 
    }
        
   // $conn->close();
    ?>
       
    </div>
      
<!-- Botón "Ver Departamento" -->
        <div class="box" >
        
            

   <script>
  // Mostrar el valor de la variable $tipo_docente en la consola
  console.log("Tipo de Docente: <?php echo htmlspecialchars($tipo_docente); ?>");
</script>         
    <?php        
            
// Consulta SQL para obtener los datos
$sqlb = "SELECT 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio AS nombre_departamento,

    -- Docente Ocasional por sede
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_ocasional_popayan,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_regionalizacion,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán-Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_popayan_regionalizacion,

    depto_periodo.dp_estado_ocasional,
    depto_periodo.dp_estado_total,

    -- Docente Cátedra por sede
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_catedra_popayan,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_catedra_regionalizacion,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán-Regionalización' THEN 1 ELSE 0 END) AS total_catedra_popayan_regionalizacion,

    depto_periodo.dp_estado_catedra

FROM 
    depto_periodo
JOIN
    solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo 
    AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
JOIN 
    deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp

WHERE 
    fk_depto_dp = '$departamento_id' 
    AND depto_periodo.periodo = '$anio_semestre'
    AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)

GROUP BY 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio,
    depto_periodo.dp_estado_ocasional,
    depto_periodo.dp_estado_total,
    depto_periodo.dp_estado_catedra;
";


$resultb = $conn->query($sqlb);
//echo "consulta". $sql;
?>



    <div class="container">
        <div class="row">
            <div ><br>
                <?php
                    if ($resultb->num_rows > 0) {
                        // Mostrar el nombre del departamento
                        $row = $resultb->fetch_assoc();
                      
                        echo "<h2>Resumen: " . htmlspecialchars($row['nombre_departamento']) . "</h2>   ";
                        echo "<h2>Enviado: " . 
    ($row['dp_estado_total'] == 1 
        ? "OK <i class='fas fa-check-circle' style='color: green;'></i>" 
        : "NO <i class='fas fa-exclamation-circle' style='color: red;'></i>") 
    . "</h2>";
                } else {
                    echo "<p>No se encontraron resultados.</p>";
                }
                ?>
                    <?php
// Inicializar acumuladores por columna
$total_popayan = 0;
$total_regional = 0;
$total_ambas = 0;
$gran_total = 0;
?>
<style>
/* Estilo para la tabla con identidad Unicauca */
.table-unicauca {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 15px 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-unicauca thead {
    background-color: #00843D; /* Verde Unicauca */
    color: white;
}

.table-unicauca th {
    padding: 10px 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.85rem;
    border-bottom: 2px solid #00612D; /* Borde más oscuro */
    position: relative;
}

.table-unicauca td {
    padding: 8px;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
    font-size: 0.9rem;
}

.table-unicauca tbody tr:hover {
    background-color: rgba(0, 132, 61, 0.05); /* Verde muy claro con transparencia */
}

/* Estilo para celdas importantes */
.fw-bold {
    font-weight: 600;
    color: #00612D; /* Verde oscuro Unicauca */
}

/* Iconos de estado */
.text-success {
    color: #00843D !important; /* Verde Unicauca */
}

.text-danger {
    color: #D32F2F !important; /* Rojo institucional */
}

/* Fila de totales */
.table-secondary {
    background-color: #f5f5f5;
    font-weight: 600;
}

/* Tooltips para cabeceras */
th[title] {
    cursor: help;
    position: relative;
}

th[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: #fff;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 100;
}

/* Efecto sutil para celdas */
.table-unicauca td {
    transition: background-color 0.2s ease;
}

/* Mejora para la primera columna */
.table-unicauca td:first-child {
    font-weight: 500;
    text-align: left;
    padding-left: 12px;
}
</style>

<table class="table-unicauca">
    <thead>
        <tr>
            <th></th>
            <th title="Profesores únicamente en la sede Popayán">Pop</th>
            <th title="Profesores únicamente en la sede Regionalización">Reg</th>
            <th title="Profesores que laboran en ambas sedes">Pop_Reg</th>
            <th title="Total profesores por tipo de vinculación">Total_tipo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Ocasional</td>
            <?php
            if ($resultb->num_rows > 0) {
                $estado_ocasional = ($row['dp_estado_ocasional'] == 'ce') 
                    ? '<i class="fas fa-lock text-success" title="Cerrado"></i>' 
                    : '<i class="fas fa-unlock text-danger" title="Abierto"></i>';

                $popayan = (int)$row['total_ocasional_popayan'];
                $regional = (int)$row['total_ocasional_regionalizacion'];
                $ambas = (int)$row['total_ocasional_popayan_regionalizacion'];
                $total_ocasional = $popayan + $regional + $ambas;

                $total_popayan += $popayan;
                $total_regional += $regional;
                $total_ambas += $ambas;
                $gran_total += $total_ocasional;

                echo "<td>$popayan</td>";
                echo "<td>$regional</td>";
                echo "<td>$ambas</td>";
                echo "<td class='fw-bold'>$total_ocasional</td>";
                echo "<td>$estado_ocasional</td>";
            }
            ?>
        </tr>
        <tr>
            <td>Cátedra</td>
            <?php
            if ($resultb->num_rows > 0) {
                $icono_catedra = ($row['dp_estado_catedra'] == 'ce') 
                    ? '<i class="fas fa-lock text-success" title="Cerrado"></i>' 
                    : '<i class="fas fa-unlock text-danger" title="Abierto"></i>';

                $popayan = (int)$row['total_catedra_popayan'];
                $regional = (int)$row['total_catedra_regionalizacion'];
                $ambas = (int)$row['total_catedra_popayan_regionalizacion'];
                $total_catedra = $popayan + $regional + $ambas;

                $total_popayan += $popayan;
                $total_regional += $regional;
                $total_ambas += $ambas;
                $gran_total += $total_catedra;

                echo "<td>$popayan</td>";
                echo "<td>$regional</td>";
                echo "<td>$ambas</td>";
                echo "<td class='fw-bold'>$total_catedra</td>";
                echo "<td>$icono_catedra</td>";
            }
            ?>
        </tr>
        <!-- Fila de Totales por columna -->
        <tr class="table-secondary">
            <td>Total_x_sede</td>
            <td><?= $total_popayan ?></td>
            <td><?= $total_regional ?></td>
            <td><?= $total_ambas ?></td>
            <td><?= $total_popayan + $total_regional + $total_ambas ?></td>
            <td></td>
        </tr>
    </tbody>
</table>
            </div>
        </div>
</div>  <?php if ($todosCerrados && $cierreperiodo != '1') { ?>
    <div class="row mt-3">
        <div class="col-md-12 text-center">
    <?php if ($resultb->num_rows > 0) { 
    // Si el estado es 1 (enviado), se muestra el botón de "Reimprimir Oficio"
    if ($row['dp_estado_total'] == 1) {  if ($tipo_usuario != 2)  {?>
        <button 
            class="btn" style="background-color: #e83e8c; color: #fff;"
            onclick="reimprOficio_depto()">
            Reimprimir Oficio
        </button>
    <?php }} 
    // Si acepta_vra es 2 y tipo_usuario es 3, también se muestra el botón "Reimprimir Oficio"
    elseif ($acepta_vra == '2' && $tipo_usuario == 3) { ?>
        <button 
            class="btn" style="background-color: #e83e8c; color: #fff;"
            onclick="reimprOficio_depto()">
            Reimprimir Oficio
        </button>
    <?php } 
    // Si acepta_vra no es 2 y tipo_usuario es 3, se muestra el botón "Enviar a Facultad"
    elseif ($acepta_vra != '2' && $tipo_usuario == 3) { ?>
        <button class="btn btn-danger" data-toggle="modal" data-target="#myModal">
            Enviar a Facultad (Descargar Oficio)
        </button>
    <?php }
}
 ?>
            
<?php } else { ?>
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <?php if (!$todosCerrados) { ?>
                <div data-bs-toggle="tooltip" data-bs-placement="top" title="Confirmar profesores para poder enviar">
                    <button class="btn btn-danger" disabled>Enviar a Facultad (Descargar Oficio)</button>
                </div>
            <?php } elseif ($cierreperiodo == '1') { ?>
                <div data-bs-toggle="tooltip" data-bs-placement="top" title="Periodo <?= $anio_semestre ?> cerrado">
                    <button class="btn btn-danger" disabled>Enviar a Facultad (Descargar Oficio)</button>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } ?> 
               <div class="col-md-12 text-center">

      
                        <a href="excel_temporales_fac.php?tipo_usuario=<?php echo htmlspecialchars($tipo_usuario); ?>&departamento_id=<?php echo htmlspecialchars($departamento_id); ?>&facultad_id=<?php echo htmlspecialchars($facultad_id); ?>&anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Descargar xls
                        </a>
                 
    </div>
            
             <?php if ($tipo_usuario == 3){ 
                $aceptacion_fac = obteneraceptacionfac($departamento_id, $anio_semestre);
$aceptacion_vra = obteneraceptacionvra($facultad_id, $anio_semestre);    
   
            ?> 
         
            <?php 
     $osbservacion_fac = obtenerobs_fac($departamento_id, $anio_semestre);
echo "<h3>Rta. facultad: " . 
    ($aceptacion_fac === 'aceptar' 
        ? "<span>Aceptado <i class='fas fa-check-circle' style='color: green;'></i></span>" 
        : ($aceptacion_fac === 'rechazar' 
            ? "<span>Devuelto</span> 
               <button class='btn-observacion' data-obs=\"" . htmlspecialchars($osbservacion_fac) . "\" 
                  style='background: none; border: none; cursor: pointer; margin-left: 5px;'>
                  <i class='fa-solid fa-magnifying-glass' style='color: #4a90e2;'></i>
               </button>" 
            : "<span>Pendiente <i class='fas fa-hourglass-half' style='color: orange;'></i></span>")) 
    . "</h3>";

$osbservacion_vra = obtenerobs_vra($facultad_id, $anio_semestre);

echo "<h3>Rta. VRA: " . 
    ($aceptacion_vra == 2 
        ? "<span>Aceptado <i class='fas fa-check-circle' style='color: green;'></i></span>" 
        : ($aceptacion_vra == 1     
            ? "<span>Devuelto</span> 
               <button class='btn-observacion' data-obs=\"" . htmlspecialchars($osbservacion_vra) . "\" 
                  style='background: none; border: none; cursor: pointer; margin-left: 5px;'>
                  <i class='fa-solid fa-magnifying-glass-plus' style='color: #4a90e2;'></i>
               </button>" 
            : "<span>Pendiente <i class='fas fa-hourglass-half' style='color: orange;'></i></span>")) 
    . "</h3>";

                ?>
            
            
<!-- Modal HTML -->
<div id="modalObservacion" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0px 0px 10px gray; border-radius: 5px; z-index: 1000;">
    <p id="textoObservacion"></p>
    <button onclick="cerrarModal()" style="background: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Cerrar</button>
</div>

<!-- Fondo Oscuro para el Modal -->
<div id="fondoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;"></div>

<!-- Script para manejar el Modal -->
<script>
document.querySelectorAll('.btn-observacion').forEach(button => {
    button.addEventListener('click', function() {
        let texto = this.getAttribute('data-obs').replace(/\n/g, '<br>');
        document.getElementById('textoObservacion').innerHTML = texto;
        document.getElementById('modalObservacion').style.display = 'block';
        document.getElementById('fondoModal').style.display = 'block';
    });
});

function cerrarModal() {
    document.getElementById('modalObservacion').style.display = 'none';
    document.getElementById('fondoModal').style.display = 'none';
}
</script>
            
            <?php } 
            else  {
                ?>
        
            <?php    
            }
            
            
            ?> 
            
            
            <style>
    ::-webkit-input-placeholder {
        font-style: italic;
                }</style>
<!-- Modal -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="myModalLabel">Información Adicional</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="modalForm">
          <div class="row">
            <div class="col-md-6">
              <label for="num_oficio">Número de Oficio</label>
              <input type="text" class="form-control" id="num_oficio" name="num_oficio" value="<?php echo obtenerTRDDepartamento($departamento_id).'/';?>" required>
            </div>
            <div class="col-md-6">
              <label for="fecha_oficio">Fecha de Oficio</label>
              <input type="date" class="form-control" id="fecha_oficio" name="fecha_oficio" value="<?php echo date('Y-m-d', strtotime('next Monday', strtotime(date('Y-m-d')))); ?>" required>
            </div>
          </div>
        <div class="form-group">
    <label for="elaboro">Jefe de Departamento<sup>*</sup></label>
    <input type="text" class="form-control" id="elaboro" name="elaboro" placeholder="Ej. Pedro Perez" required>
</div>
<div class="row">
    <div class="col-md-6">
        <label for="acta">Número de Acta<sup>*</sup></label>
        <input type="text" class="form-control" id="acta" name="acta" placeholder="Ej. No. 02" value="<?php echo $num_acta; ?>" required>
    </div>
    <div class="col-md-6">
        <label for="fecha_acta">Fecha del Acta<sup>*</sup></label>
        <input type="date" class="form-control" id="fecha_acta" name="fecha_acta" value="<?php echo $fecha_acta; ?>" required>
    </div>
</div>

          <!-- Sección de folios -->
       <!-- Sección de folios -->
<div class="form-group">
    <label>Distribución de Folios</label>
    <div class="row">
        <div class="col-md-8 text-start d-flex align-items-center">
            <label for="folios1" class="mb-0 label-italic">FOR-59. Acta de Selección</label>
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control folio-input" id="folios1" name="folios1" value="1" min="0" oninput="updateFoliosTotal()" required>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-md-8 text-start d-flex align-items-center">
            <label for="folios2" class="mb-0 label-italic">FOR 45. Revisión Requisitos</label>
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control folio-input" id="folios2" name="folios2" value="<?php echo $totalItems; ?>" min="0" oninput="updateFoliosTotal()" required>
        </div>
    </div>
  <div class="row mt-2">
    <div class="col-md-8 text-start d-flex align-items-center">
        <label for="folios3" class="mb-0 label-italic">Otros: (hojas de vida y actualizaciones)</label>
    </div>
    <div class="col-md-4">
        <input type="number" class="form-control folio-input" id="folios3" name="folios3" 
               placeholder="" min="0" oninput="updateFoliosTotal()" 
               onblur="if(this.value === '') this.value = 0;">
    </div>
</div>
    <div class="mt-3">
    <label class="label-italic">Total de Folios: 
        <span id="totalFoliosDisplay" class="label-italic">
            <strong><?php echo $totalItems + 1; ?></strong>
        </span>
    </label>
</div>
</div>  


          <!-- Campo oculto que almacena la suma total de folios -->
          <input type="hidden" id="folios" name="folios">

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" onclick="submitForm()">Enviar</button>
      </div>
    </div>
  </div>
</div>
  <script>
    $('#myModal').on('shown.bs.modal', function (e) {
  updateFoliosTotal();
});  
  function eliminarRegistros(tipoDocente, facultadId, departamentoId, anioSemestre) { 
    if (confirm("¿Estás seguro de que deseas eliminar todos los registros de " + tipoDocente + "?")) {
        $.ajax({
            url: 'eliminar_all_registros.php',
            type: 'POST',
            data: { 
                tipo_docente: tipoDocente,
                facultad_id: facultadId,
                departamento_id: departamentoId,
                anio_semestre: anioSemestre
            },
            success: function(response) {
                alert("Registros eliminados correctamente.");
                location.reload();
            },
            error: function(xhr, status, error) {
                alert("Error al eliminar registros: " + error);
            }
        });
    }
}
      
    function reimprOficio_depto() {
        // Variables dinámicas
        const departamento_id = encodeURIComponent('<?php echo $departamento_id; ?>');
        const anio_semestre = encodeURIComponent('<?php echo $anio_semestre; ?>');

        // Construir la URL con las variables
        const url = `oficio_depto_reimpr.php?departamento_id=${departamento_id}&anio_semestre=${anio_semestre}`;

        // Redirigir al archivo PHP
        window.location.href = url;
    }
</script>          
            
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Seleccionar/Deseleccionar todos los checkboxes
        $('#selectAll').change(function () {
            const isChecked = $(this).is(':checked');
            // Cambiar el estado de todos los checkboxes
            $('.individualCheckbox').prop('checked', isChecked).trigger('change');
        });

        // Actualizar estado individualmente al marcar/desmarcar
        $('.individualCheckbox').change(function () {
            const form = $(this).closest('form'); // Obtener el formulario asociado al checkbox
            $.ajax({
                url: 'update_visto_bueno.php', // El archivo que procesa la actualización
                type: 'POST',
                data: form.serialize(), // Serializar los datos del formulario
                success: function (response) {
                    console.log('Estado actualizado exitosamente.');
                    // Opcional: mostrar mensaje o realizar otras acciones
                },
                error: function () {
                    alert('Error al actualizar el estado.');
                }
            });
        });

        // Tooltip para mejorar la experiencia de usuario
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
            
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const fechaOficioInput = document.getElementById('fecha_oficio');
    
    let today = new Date();
    let day = today.getDay();
    
    // Si hoy es sábado (6) o domingo (0), ajustar la fecha al siguiente lunes
    if (day === 6) { // sábado
      today.setDate(today.getDate() + 2);
    } else if (day === 0) { // domingo
      today.setDate(today.getDate() + 1);
    }
    
    // Formatear la fecha a yyyy-mm-dd para el valor del input de fecha
    let year = today.getFullYear();
    let month = String(today.getMonth() + 1).padStart(2, '0');
    let date = String(today.getDate()).padStart(2, '0');
    let formattedDate = `${year}-${month}-${date}`;
    
    fechaOficioInput.value = formattedDate;
  });
</script>
<script>
function updateFoliosTotal() {
    // Obtener valores de los tres inputs de folios
    var folios1 = parseInt(document.getElementById('folios1').value) || 0;
    var folios2 = parseInt(document.getElementById('folios2').value) || 0;
    var folios3 = parseInt(document.getElementById('folios3').value) || 0;

    // Calcular la suma total
    var totalFolios = folios1 + folios2 + folios3;

    // Asignar el valor total al campo oculto
    document.getElementById('folios').value = totalFolios;

    // Actualizar el elemento que muestra el total, manteniendo el formato en negrita
    document.getElementById('totalFoliosDisplay').innerHTML = "<strong>" + totalFolios + "</strong>";
}
function submitForm() {
    // Llamar la función para asegurarse de que los valores de folios están actualizados
    updateFoliosTotal();

    // Obtener valores del formulario
    var numOficio = document.getElementById('num_oficio').value;
    var fechaOficio = document.getElementById('fecha_oficio').value;
    var elaboro = document.getElementById('elaboro').value;
    var acta = document.getElementById('acta').value;
    var fechaActa = document.getElementById('fecha_acta').value;
    var folios = document.getElementById('folios').value;
    var folios3 = document.getElementById('folios3').value; // Obtener el valor del campo folios3

    // Convertir a número (si está vacío, se toma como 0)
    folios3 = folios3.trim() === '' ? 0 : parseInt(folios3, 10);

    // Valor de PHP pasado a JavaScript usando inline PHP
    var contadorHV = <?php echo $contadorHV; ?>;

    // Validar si folios3 es menor a contadorHV
        /*if (folios3 < contadorHV) {
        var mensaje = contadorHV === 1 
            ? 'Verificar número de folios de ' + contadorHV + ' hoja de vida nueva y/o actualización informada.' 
            : 'Verificar número de folios de las ' + contadorHV + ' hojas de vida nuevas y/o actualizaciones informadas.';
        
        alert(mensaje);
        return;
    }*/


    // Verificar que los campos obligatorios no estén vacíos
    if (numOficio === '' || fechaOficio === '' || elaboro === '' || acta === '' || fechaActa === '') {
    alert('Por favor, diligencie los campos obligatorios (*).');
    return;
}

    // Obtener valores de las variables PHP
    var departamentoId = "<?php echo urlencode($departamento_id); ?>";
    var anioSemestre = "<?php echo urlencode($anio_semestre); ?>";
    var nombrefac = "<?php echo urlencode($nombre_fac); ?>";

    // Construir la URL con los parámetros
    var url = 'oficio_depto.php?folios=' + folios + '&departamento_id=' + departamentoId + '&anio_semestre=' + anioSemestre + '&nombre_fac=' + nombrefac + '&num_oficio=' + encodeURIComponent(numOficio) + '&fecha_oficio=' + encodeURIComponent(fechaOficio) + '&elaboro=' + encodeURIComponent(elaboro) + '&acta=' + encodeURIComponent(acta) + '&fecha_acta=' + encodeURIComponent(fechaActa);


    // Redireccionar a la URL
    window.location.href = url;

    // Espera 1 segundo y luego recarga la página
    setTimeout(function() {
        window.location.reload();
    }, 1000);
}

</script>
<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>

    </div>
   </div> </div>    
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.20/dist/js/bootstrap.bundle.min.js"></script>
            

<script>
    document.getElementById('confirmForm').addEventListener('submit', function() {
        setTimeout(function() {
            location.reload();
        }, 3000); // Espera 3 segundos antes de recargar la página
    });
</script>
    <script>
    // Script para cerrar el modal automáticamente después de enviar el formulario
    $('#oficioForm').on('submit', function() {
        $('#oficioModal').modal('hide');  // Cerrar el modal
    });

    $('#oficioFacultadForm').on('submit', function() {
        $('#oficioModal').modal('hide');  // Cerrar el modal
    });
</script>
   
</body>
    
</html>
<?php       // Función para obtener el cierreo no de departamento
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