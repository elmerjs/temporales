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


 $aniose= $anio_semestre;
function obtenerPeriodoAnterior($anio_semestre) {
 list($anio, $semestre) = explode('-', $anio_semestre);
    $anioAnterior = $anio - 1;
    return $anioAnterior . '-' . $semestre;
}
$periodo_anterior= obtenerPeriodoAnterior($aniose);

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
$dias_ocas = $inicio_ocas->diff($fin_ocas)->days-1;
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
            margin: 20px auto;
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

.update-btn:hover {
    color: #004080;
}

.delete-btn:hover {
    color: #f44336;
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
   <?php if ($tipo_usuario != 3): ?>
            <?php
$archivo_regreso = (isset($_POST['envia']) && $_POST['envia'] === 'rcec') 
    ? 'report_depto_comparativo_costos_espejo.php' 
    : 'comparativo_espejo.php';
?>

<span style="display: inline-block; padding: 4px 8px; border: 1px solid #ccc; background-color: #f8f9fa; border-radius: 3px;">
    <a href="<?= $archivo_regreso ?>?anio_semestre=<?= urlencode($anio_semestre) ?>" 
       class="btn btn-light" 
       title="Regresar a 'Gestión facultad'" 
       style="text-decoration: none; color: inherit; padding: 2px 5px;">
        Regresar <i class="fas fa-arrow-left"></i>
    </a>
</span>
<?php endif; 
    
    
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

    ?>

    <div class="container">
        
        <div class="box">

    <h3> <?php 
    $nombre_fac = obtenerNombreFacultad($departamento_id);
    $nombre_depto = mb_strimwidth(obtenerNombreDepartamento($_POST['departamento_id']), 0, 20, '...');
    echo $nombre_fac . ' - ' . $nombre_depto . '. Periodo: ' . htmlspecialchars($_POST['anio_semestre']) . '.';
?>
</h3>
            
            
        


    <?php
$facultad_id = obtenerIdFacultad($departamento_id);

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

  $totalItems = 0; // Inicializar el acumulador fuera del bucle principal
   $contadorHV = 0; // 🔹 Inicializar el contador
       $contadorVerdes = 0; // ▶ Inicializar contador de nuevos
$contadorVerdesOc = 0;$contadorVerdesCa = 0;
         
     // ▶ 1. Obtener cédulas del periodo anterior CON TIPO DIFERENTE al actual
$cedulasCambioTipo = [];
$sqlCambioTipo = "SELECT cedula FROM solicitudes 
                 WHERE facultad_id = '$facultad_id' 
                 AND departamento_id = '$departamento_id' 
                 AND anio_semestre = '$periodo_anterior'
                
                 AND (estado <> 'an' OR estado IS NULL)";
$resultCambioTipo = $conn->query($sqlCambioTipo);
while ($rowCambio = $resultCambioTipo->fetch_assoc()) {
    $cedulasCambioTipo[] = $rowCambio['cedula'];
}       
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

    // Mostrar "Dedicación" solo si es tipo Ocasional o Cátedra
    if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
        echo "<th colspan='2'>Dedicación</th>";
    }

echo "<th rowspan='2'>Puntos</th>";
              echo "<th rowspan='2'>Proyec</th>";

echo "</tr>";

// Fila de subcabeceras: dedicación y acciones


    if ($tipo_docente == "Ocasional") {
        echo "<tr>
                <th title='Sede Popayán'>Pop</th>
                <th title='Sede Regionalización'>Reg</th>";
    } elseif ($tipo_docente == "Catedra") {
        echo "<tr>
                <th title='Horas en Sede Popayán'>Horas Pop</th>
                <th title='Horas en Sede Regionalización'>Horas Reg</th>";
    }


    echo "</tr>";

    // Aquí puedes establecer el flag que determine si mostrar o no las columnas de hojas de vida
    $mostrar_hv = false; // Cambia este valor si deseas ocultar las columnas de hojas de vida.

    $item = 1; // Inicializar el contador de ítems
    $todosLosRegistrosValidos = true; // Bandera para validar todos los registros
    $datos_acta = obtener_acta($anio_semestre, $departamento_id);

    // Si encuentra datos, asigna los valores, si no, deja vacío
    $num_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['acta_periodo']) : "";
    $fecha_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['fecha_acta']) : "";

while ($row = $result->fetch_assoc()) {
         $cedula = $row['cedula'];

    // Verificar si es NUEVA (no existe en período anterior)
    $esNueva = !in_array($cedula, $cedulasPeriodoAnterior);
    $claseColor = $esNueva ? 'cedula-nueva' : '';
    
    // Verificar si cambió de tipo (existe en anterior pero con diferente tipo)
    $cambioTipo = in_array($cedula, $cedulasCambioTipo);
    
    // Aplicar clases CSS
    $claseFila = $cambioTipo ? 'fondo-amarillo' : '';
    $claseTexto = $esNueva ? 'cedula-nueva' : '';

    if ($esNueva) {
        $contadorVerdes++;
        if ($tipo_docente =='Ocasional') $contadorVerdesOc++; 
        else $contadorVerdesCa++;
    }
    
    echo "<tr class='$claseFila'>";
    echo "<td>" . $item . "</td>";
    echo "<td style='text-align: left;' class='$claseTexto'>" . htmlspecialchars($cedula) . "</td>";
    echo "<td style='text-align: left;' class='$claseTexto'>" . htmlspecialchars($row["nombre"]) . "</td>";

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
                $mesesocas = intval($semanas_ocas / 4.33); // 4.33 semanas ≈ 1 mes
                // Asegurarse que los índices existen y son iguales a "MT" o "TC"
                if (($row["tipo_dedicacion"] == "MT") || ($row["tipo_dedicacion_r"] == "MT")) {
                    $horas = 20;
                } elseif (($row["tipo_dedicacion"] == "TC") || ($row["tipo_dedicacion_r"] == "TC")) {
                    $horas = 40;
                }

                // Calculo de la asignación mensual y total
                $asignacion_mes = $row["puntos"] * $valor_punto * ($horas / 40);
                $asignacion_total = $asignacion_mes * $dias_ocas / 30;


                $prima_navidad = $asignacion_mes*$mesesocas/12;
                $indem_vacaciones = $asignacion_mes*$dias_ocas/360;
                $indem_prima_vacaciones = $asignacion_mes*(2/3)*($dias_ocas/360);
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
echo '<td data-toggle="tooltip" data-placement="right" title="' . htmlspecialchars($title, ENT_QUOTES) . '">
$' . number_format($gran_total / 1000000, 2) . ' M</td>';
    echo "</tr>";
    $item++;
}
    echo "</table>";
}
 else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";

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

?>
    

        
   <?php if ($estadoDepto == "CERRADO") { 
    $envio_fac = obtenerenviof($facultad_id, $anio_semestre);
            
    $acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
 } ?>

    </div>
            
    </div>      <br>   
    <?php 
    }
        
   // $conn->close();
            
    ?>
       
    </div>
      
<!-- Botón "Ver Departamento" -->
        <div class="box">
        <h3 style="margin-top:0px;">Periodo anterior: <?php echo htmlspecialchars($periodo_anterior); ?></h3>

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
          <h3>Vinculación: ".$tipo_docente." (Periodo anterior)</h3>
          </div>";

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th rowspan='2'>Ítem</th>
                    <th rowspan='2'>Cédula</th>
                    <th rowspan='2'>Nombre</th>";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }

          echo "<th rowspan='2'>Puntos</th>";
                        echo "<th rowspan='2'>Proyec</th>";

        echo "</tr>";

        if ($tipo_docente == "Ocasional") {
            echo "<tr><th>Pop</th><th>Reg</th></tr>";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr><th>Horas Pop</th><th>Horas Reg</th></tr>";
        }
    
        $item = 1;
       

while ($row = $result->fetch_assoc()) {
    $cedula = $row['cedula'];

    // ▶ Verificar si está en el tipo_docente actual
    $cedulaEliminada = !in_array($cedula, $cedulasPeriodoActual);

    // ▶ Verificar si está en otro tipo_docente (pero sí está en el global)
    $cedulaEstaEnOtroTipo = $cedulaEliminada && in_array($cedula, $cedulasGlobalesPeriodoActual);

    // ▶ Definir clase CSS según el caso
    if ($cedulaEstaEnOtroTipo) {
        $claseRoja = 'cedula-en-otro-tipo'; // fondo amarillo, texto rojo
    } elseif ($cedulaEliminada) {
        $claseRoja = 'cedula-eliminada'; // solo texto rojo
    } else {
        $claseRoja = '';
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
    echo "<td style='text-align: left;' class='$claseRoja'>" . htmlspecialchars($cedula) . "</td>";
    echo "<td style='text-align: left;' class='$claseRoja'>" . htmlspecialchars($row["nombre"]) . "</td>";
    if ($tipo_docente == "Ocasional") {
        echo "<td>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
     if ($tipo_docente == "Catedra") {
                $horas = $row["horas"] == 0 ? "" : htmlspecialchars($row["horas"]);
                $horas_r = $row["horas_r"] == 0 ? "" : htmlspecialchars($row["horas_r"]);
                echo "<td>$horas</td><td>$horas_r</td>";
            }

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
echo "</tr>";
    $item++;
}

        echo "</table>";
    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados para el periodo anterior.</p>";
    }

    echo "</div><br>";
    
}
?>
</div>    
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.20/dist/js/bootstrap.bundle.min.js"></script>
            

   
</body>
    
</html>
<?php       // Función para obtener el cierreo no de departamento
   
echo "<div style='margin-bottom: 10px; font-size: 0.9em;'>
  <strong>Nota:</strong> 
  <span style='color: green; font-weight: bold;'>En verde:</span> Profesores nuevos; (Ocasionales: {$contadorVerdesOc}; Cátedra: {$contadorVerdesCa}) - Total: {$contadorVerdes} &nbsp;|&nbsp;
  <span style='color: red; font-weight: bold;'>En rojo:</span> Profesores que ya no continúan. (Ocasionales: {$contadorRojosOc}, Cátedra: {$contadorRojosCa}) - Total: {$contadorRojos} &nbsp;|&nbsp;
  <span style='background-color: yellow; color: red; font-weight: bold;'>&nbsp;Cambio de vinculación&nbsp;</span>: Profesores que cambian de tipo de vinculación en el periodo actual.
</div>";
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