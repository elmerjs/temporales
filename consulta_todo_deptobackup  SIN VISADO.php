<?php
require('include/headerz.php');

    // Obtener los parámetros de la URL
$facultad_id = isset($_POST['facultad_id']) ? $_POST['facultad_id'] : null;
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre = $_POST['anio_semestre'];
 $aniose= $anio_semestre;
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
                  FROM solicitudes   ;";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}
        $todosCerrados = true; // Inicializar bandera

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];
    //$tipo_docente = $_GET['tipo_docente'];

    // Realizar la consulta a la base de datos
    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente'";

    $result = $conn->query($sql);
                  
    //echo "<h3   >Vinculación: ".$tipo_docente."</h3>";
    
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
    echo "
    <form action='nuevo_registro.php' method='GET'>
        <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
        <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
        <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
        <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
        <button type='submit' class='btn btn-success'><i class='fas fa-plus'></i> Agregar Nuevo Registro</button>
    </form>";
        $todosCerrados = false; // Cambiar la bandera si alguno no está cerrado

} echo "</div>";
    
    // Obtener el conteo de profesores
    $sqlCount = "SELECT COUNT(*) as count FROM solicitudes WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente'";
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

        echo "
              <th rowspan='2'>Acciones</th>
          </tr>";
 
        if ($tipo_docente == "Ocasional") {
            echo "<tr>
                      <th>Pop</th>
                      <th>Reg</th>
                 ";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr>
                      <th>Horas Pop</th>
                      <th>Horas Reg</th>
                  ";
        }
 echo "
                      <th>Anexa(nuevo)</th>
                      <th>Actualiza(antiguo)</th>
                  </tr>";
        $item = 1; // Inicializar el contador de ítems
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . $item . "</td> <!-- Usar el contador de ítems --> 
                    <td>" . $row["cedula"] . "</td>
                    <td>" . $row["nombre"] . "</td>";

            if ($tipo_docente == "Ocasional") {
                echo "<td>" . $row["tipo_dedicacion"] . "</td>";
                echo "<td>" . $row["tipo_dedicacion_r"] . "</td>";
            }
            if ($tipo_docente == "Catedra") {
                echo "<td>" . $row["horas"] . "</td>";
                echo "<td>" . $row["horas_r"] . "</td>";
            }

            echo " 
                    <td>" . $row["anexa_hv_docente_nuevo"] . "</td>
                    <td>" . $row["actualiza_hv_antiguo"] . "</td>
                    <td>";
              if ($estadoDepto != "CERRADO") {
                echo "<form action='eliminar.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . $row["id_solicitud"] . "'>
                            <input type='hidden' name='facultad_id' value='" . $facultad_id . "'>
                            <input type='hidden' name='departamento_id' value='" . $departamento_id . "'>
                            <input type='hidden' name='anio_semestre' value='" . $anio_semestre . "'>
                            <input type='hidden' name='tipo_docente' value='" . $tipo_docente . "'>
                           <button type='submit' class='delete-btn'><i class='fas fa-trash'></i></button>
                        </form>
                        <form action='actualizar.php' method='GET' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . $row["id_solicitud"] . "'>
                            <input type='hidden' name='facultad_id' value='" . $facultad_id . "'>
                            <input type='hidden' name='departamento_id' value='" . $departamento_id . "'>
                            <input type='hidden' name='anio_semestre' value='" . $anio_semestre . "'>
                            <input type='hidden' name='tipo_docente' value='" . $tipo_docente . "'>
                            <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                        </form>";
            }
            echo "</td>
                  </tr>";
            $item++; // Incrementar el contador de ítems
        }
        echo "</table>";

    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";
    }

    // Cerrar conexión
    ?>
    <div class="d-flex justify-content-between mt-3">
     
         <?php
                 if ($estadoDepto == "ABIERTO"):   

        
        ?>
        
        <form id="confirmForm"  action="confirmar_tipo_d_depto.php" method="GET" onsubmit="return confirmarEnvio(<?php echo $count; ?>, '<?php echo $tipo_docente; ?>');">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
<button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Confirmar Profesores</button>
        </form>
      <?php endif; ?>
        
        <?php if ($estadoDepto == "CERRADO") { ;?>
        <form action="abrir_estado.php" method="POST">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
            <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($tipo_usuario); ?>">

            <button type="submit" class="btn btn-warning"><i class="fas fa-unlock"></i>Abrir Estado</button>
            
        </form>
        <?php } ?>
    </div>
    <?php 
    }
        
   // $conn->close();
    ?>
       
    </div>
      
<!-- Botón "Ver Departamento" -->
        <div class="box" >
             
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
    </div>  <?php if ($todosCerrados) { ?>
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <?php if ($resultb->num_rows > 0) { ?>
                <button class="btn btn-danger" data-toggle="modal" data-target="#myModal">Enviar a Facultad (Descargar Oficio)</button>
            <?php } ?>
               <div class="col-md-12 text-center">

      
                        <a href="excel_temporales.php?tipo_usuario=<?php echo htmlspecialchars($tipo_usuario); ?>&departamento_id=<?php echo htmlspecialchars($departamento_id); ?>&facultad_id=<?php echo htmlspecialchars($facultad_id); ?>&anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" class="btn btn-success">
                            <i class="fas fa-download"></i> Descargar xls
                        </a>
                 
    </div>
<?php } else { ?>
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <button class="btn btn-danger" disabled data-toggle="tooltip" data-placement="top" title="Confirmar profesores para poder enviar">Enviar a Facultad (Descargar Oficio)</button>
        </div>
    </div>
<?php } ?>           <div class="row mt-3">
 <div class="col-md-12 text-center">

              <a href="indexsolicitud.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i>ir a Solicitudes</a>
            </div></div>
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
          <div class="form-group">
            <label for="num_oficio">Número de Oficio</label>
            <input type="text" class="form-control" id="num_oficio" name="num_oficio" value = "<?php echo obtenerTRDDepartamento($departamento_id).'/';?>" required>
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
    <input type="number" class="form-control" id="folios" name="folios" placeholder="0">
</div>
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
function submitForm() {
    // Obtener valores del formulario
    var numOficio = document.getElementById('num_oficio').value;
    var elaboro = document.getElementById('elaboro').value;
    var acta = document.getElementById('acta').value;
    var folios = document.getElementById('folios').value;

    // Verificar que los campos no estén vacíos
    if (numOficio === '' || elaboro === '' || acta === '') {
        alert('Por favor, llene todos los campos.');
        return;
    }
    
    // Obtener valores de las variables PHP
    var departamentoId = "<?php echo urlencode($departamento_id); ?>";
    var anioSemestre = "<?php echo urlencode($anio_semestre); ?>";
    var nombrefac = "<?php echo urlencode($nombre_fac); ?>";

    // Construir la URL con los parámetros
    var url = 'oficio_depto.php?folios=' + folios + '&departamento_id=' + departamentoId + '&anio_semestre=' + anioSemestre  + '&nombre_fac=' + nombrefac + '&num_oficio=' + encodeURIComponent(numOficio) + '&elaboro=' + encodeURIComponent(elaboro) + '&acta=' + encodeURIComponent(acta);
    
    // Redireccionar a la URL
    window.location.href = url;
    
    // Cerrar el modal
    
        // Espera 2 segundos (2000 milisegundos) y luego recarga la página
        setTimeout(function() {
            window.location.reload();
        }, 2000); // Puedes ajustar el tiempo de espera según tus necesidades
   F
    $('#myModal').modal('hide');
}
</script>
<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>

    </div>
   </div>
   
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