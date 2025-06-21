<?php
require('include/headerz.php');
require 'funciones.php';
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
    
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px auto;
            padding: 20px;
            max-width: 80%; /* Establece el ancho máximo de la página */
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
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
              padding: 1px; /* Aumenta el espacio de relleno de las celdas */
        }
        th {
            background-color: #004080;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        button {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn {
            background-color: #004080;
            color: white;
        }
        .delete-btn {
            background-color: #f44336;
            color: white;
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
            gap: 20px; /* Espacio entre los divs */
            max-width: 1600px; /* Ancho máximo del contenedor */
            margin: 0 auto; /* Centra el contenedor horizontalmente */
            padding: 10px; /* Espaciado interno del contenedor */
        }
       .box {
            flex-grow: 1; /* Permite que los divs se expandan según su contenido */
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

         .box:nth-child(2) {
            flex-basis: 1%; /* Establece el ancho del segundo div */
        }
        .box-gray {
            background-color: #EAEAEA; /* Fondo gris claro */
            border-color: #ccc; /* Borde ligeramente más oscuro */
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

.btn-cargue-masivo {
    background: linear-gradient(to right, #fd7e14, #ff9800);
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease-in-out;
    border: none;
    display: inline-block;
    animation: inflateButton 1s ease-in-out;
}

.btn-cargue-masivo:hover {
    background: linear-gradient(to right, #e55300, #ff5722);
    transform: scale(1.05);
    box-shadow: 3px 3px 15px rgba(0, 0, 0, 0.3);
}
    </style>
    <script>
        function confirmarEnvio(count, tipo) {
            return confirm(` Se confirman ${count} profesores de  ${tipo}. ¿Desea continuar?`);
        }
    </script>
    
</head>
<body>
    
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
if ($todosCerrados && $tipo_usuario == 3) {
    echo "<div style='text-align: right;'>
            <label for='selectAll' style='cursor: pointer;'>
                <input type='checkbox' id='selectAll' 
                       title='Aplique este checkbox solo si está seguro de marcar masivamente los profesores' 
                       onclick='confirmSelection(this)'>
                <i>Visado masivo</i>
            </label>
          </div>";
}
            

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];
 
    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

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
            <button type='submit' class='btn btn-success'>
                <i class='fas fa-plus'></i> Agregar Registro
            </button>
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
                  <th>Pop</th>
                  <th>Reg</th>";
    } elseif ($tipo_docente == "Catedra") {
        echo "<tr>
                  <th>Horas Pop</th>
                  <th>Horas Reg</th>";
    }
    echo "
                  <th>Anexa(nuevo)</th>
                  <th>Actualiza(antiguo)</th>";

    // Mostrar/Ocultar las subcolumnas de acciones
    if ($estadoDepto != "CERRADO") {
      echo "<th>Eliminar</th> 
         <th>Editar</th>";
          if ($tipo_usuario == 3) { 
          //echo "<th> Visado <input type='checkbox' id='selectAll' title='Aplique este checkbox solo si está seguro de marcar masivamente los profesores' onclick='confirmSelection(this)'> </th>";
          echo "<th>Visado</th>"; 
          }
          else { echo "<th>Visado</th>"; }  
    } else {
        echo "<th style='display:none;'>Eliminar</th>
              <th style='display:none;'>Editar</th>
              <th>Visado</th>";
    }

    echo "</tr>";

    $item = 1; // Inicializar el contador de ítems
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $item . "</td> <!-- Usar el contador de ítems --> 
                <td>" . htmlspecialchars($row["cedula"]) . "</td>
                <td>" . htmlspecialchars($row["nombre"]) . "</td>";

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
        }
        echo "</tr>";
        $item++; // Incrementar el contador de ítems
    }
    echo "</table>";

    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";
        if ($tipo_usuario == 3) {
    if ($tipo_usuario == 3) {
echo '
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <a href="indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '" class="btn btn-cargue-masivo">
                <i class="fas fa-upload"></i> Cargue Masivo
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
        <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Confirmar Profesores</button>
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

        <button type="submit" class="btn btn-warning"><i class="fas fa-unlock"></i>Abrir Estado</button>
    </form>

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
            SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_ocasional_popayan,
            SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_regionalizacion,
            depto_periodo.dp_estado_ocasional,
                        depto_periodo.dp_estado_total,

            SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_catedra_popayan,
            SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_catedra_regionalizacion,
            depto_periodo.dp_estado_catedra
        FROM 
            depto_periodo
         join
            solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
         join 
            deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
        WHERE 
            fk_depto_dp = $departamento_id and depto_periodo.periodo = '$anio_semestre'
            AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
        GROUP BY 
            depto_periodo.fk_depto_dp, deparmanentos.depto_nom_propio";

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
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Popayán</th>
                            <th>Regionalización</th>
                            <th>Estado</th> <!-- Nueva columna para el estado -->
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ocasional</td>
                            <?php
                            if ($resultb->num_rows > 0) {
                                // Determinar el icono según el estado para Ocasional
                               $estado_ocasional = ($row['dp_estado_ocasional'] == 'ce') ? '<i class="fas fa-lock text-success"></i>' : '<i class="fas fa-unlock text-danger"></i>';

// Mostrar el icono dentro de un enlace a index.php con departamento_id
echo "<td>" . htmlspecialchars($row['total_ocasional_popayan']) . "</td>";
echo "<td>" . htmlspecialchars($row['total_ocasional_regionalizacion']) . "</td>";
echo '<td><a href="indexsolicitud.php?departamento_id=' . urlencode($departamento_id) . '">' . $estado_ocasional . '</a></td>';
                            }
                            ?>
                        </tr>
                        <tr>
                            <td>Cátedra</td>
                            <?php
                            if ($resultb->num_rows > 0) {
                                // Determinar el icono según el estado para Cátedra
                               $icono_catedra = ($row['dp_estado_catedra'] == 'ce') ? '<i class="fas fa-lock text-success"></i>' : '<i class="fas fa-unlock text-danger"></i>';

                                // Mostrar el icono dentro de un enlace a index.php con departamento_id
echo "<td>" . htmlspecialchars($row['total_catedra_popayan']) . "</td>";
echo "<td>" . htmlspecialchars($row['total_catedra_regionalizacion']) . "</td>";
echo '<td><a href="indexsolicitud.php?departamento_id=' . urlencode($departamento_id) . '">' . $icono_catedra . '</a></td>';


                            }
                            ?>
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
        ? "<span title='" . htmlspecialchars($osbservacion_fac) . "'>Aceptado <i class='fas fa-check-circle' style='color: green;'></i></span>" 
        : ($aceptacion_fac === 'rechazar' 
            ? "<span title='" . htmlspecialchars($osbservacion_fac) . "'>No aceptado <i class='fas fa-times-circle' style='color: red;'></i></span>" 
            : "<span title='" . htmlspecialchars($osbservacion_fac) . "'>Pendiente <i class='fas fa-hourglass-half' style='color: orange;'></i></span>")) 
    . "</h3>";
    $osbservacion_vra = obtenerobs_vra($facultad_id, $anio_semestre);
echo "<h3>Rta. VRA: " . 
    ($aceptacion_vra == 2 
        ? "<span title='" . htmlspecialchars($osbservacion_vra) . "'>Aceptado <i class='fas fa-check-circle' style='color: green;'></i></span>" 
        : ($aceptacion_vra == 1     
            ? "<span title='" . htmlspecialchars($osbservacion_vra) . "'>No Aceptado <i class='fas fa-times-circle' style='color: red;'></i></span>" 
            : "<span title='" . htmlspecialchars($osbservacion_vra) . "'>Pendiente <i class='fas fa-hourglass-half' style='color: orange;'></i></span>")) 
    . "</h3>";
            ?>
            <?php } 
            else  {
                ?>
                <div class="row mt-3">
    <div class="col-md-12 text-center">
        <a href="report_depto_full.php" class="btn btn-primary">
            Regresar a "Gestión facultad" <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
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
            <label for="elaboro">Jefe de Departamento</label>
            <input type="text" class="form-control" id="elaboro" name="elaboro" placeholder="Ej.Pedro Perez" required>
          </div>
          <div class="form-group">
            <label for="acta">Acta de selección</label>
            <input type="text" class="form-control" id="acta" name="acta" placeholder="Ej.No. 02 del 1 de diciembre de 2024" required>
          </div>
          <div class="form-group">
    <label for="folios">Número de folios</label>
    <input type="text" class="form-control" id="folios" name="folios" placeholder="0" oninput="validateNumberInput(this)" required>
</div>
            <script>
  function validateNumberInput(input) {
    // Remueve cualquier carácter no numérico
    input.value = input.value.replace(/[^0-9]/g, '');

    // Si el valor es negativo, lo convierte a positivo
    if (parseInt(input.value) < 0) {
      input.value = '';
    }
  }
</script>
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
function submitForm() {
    // Obtener valores del formulario
    var numOficio = document.getElementById('num_oficio').value;
    var fechaOficio = document.getElementById('fecha_oficio').value;
    var elaboro = document.getElementById('elaboro').value;
    var acta = document.getElementById('acta').value;
    var folios = document.getElementById('folios').value;

    // Verificar que los campos no estén vacíos
    if (numOficio === '' || fechaOficio === '' || elaboro === '' || acta === '') {
        alert('Por favor, llene todos los campos.');
        return;
    }
    
    // Obtener valores de las variables PHP
    var departamentoId = "<?php echo urlencode($departamento_id); ?>";
    var anioSemestre = "<?php echo urlencode($anio_semestre); ?>";
    var nombrefac = "<?php echo urlencode($nombre_fac); ?>";

    // Construir la URL con los parámetros
    var url = 'oficio_depto.php?folios=' + folios + '&departamento_id=' + departamentoId + '&anio_semestre=' + anioSemestre + '&nombre_fac=' + nombrefac + '&num_oficio=' + encodeURIComponent(numOficio) + '&fecha_oficio=' + encodeURIComponent(fechaOficio) + '&elaboro=' + encodeURIComponent(elaboro) + '&acta=' + encodeURIComponent(acta);
    
    // Redireccionar a la URL
    window.location.href = url;
    
   
    
    // Espera 2 segundos (2000 milisegundos) y luego recarga la página
    setTimeout(function() {
        window.location.reload();
    }, 1000); // Puedes ajustar el tiempo de espera según tus necesidades
}
</script>
<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>

    </div>
   </div>
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