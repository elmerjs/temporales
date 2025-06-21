<?php
require('include/headerz.php');

  // Recuperar datos de la sesión
$facultad_id = $_SESSION['facultad_id'];
$departamento_id = $_SESSION['departamento_id'];
$anio_semestre = $_SESSION['anio_semestre'];
$tipo_docente = $_SESSION['tipo_docente'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Solicitudes</title>
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
         .estado-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px auto;
            padding: 20px;
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
       /* Contenedor que permite el desplazamiento horizontal */
.table-responsive {
    overflow-x: auto;
    width: 100%; /* Asegura que el contenedor ocupe todo el ancho disponible */
}

/* Estiliza la tabla como antes */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
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
    </style>
    <script>
        function confirmarEnvio(count, tipo) {
            return confirm(`Se enviarán ${count} profesores de tipo ${tipo}. ¿Desea continuar?`);
        }
        function enviarFormulario(formId) {
            document.getElementById(formId).submit();
        }
    </script>
</head>
    
<body>
    
    <h2>Facultad: <?php echo obtenerNombreFacultad($facultad_id). ' - '.obtenerNombreDepartamento($departamento_id). '. Periodo:'.htmlspecialchars($anio_semestre).'. '; ?></h2>

    
   
    <?php $aniose= $anio_semestre; ?>
  
    <h2>Vinculación: <?php echo $tipo_docente; ?></h2>
 <div class="estado-container">
        <h2>Estado: <?php 
            if ($tipo_docente=='Catedra'){
                $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
                echo $estadoDepto;
            } else {
                $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose);
                echo $estadoDepto;
            }
        ?></h2>
        <?php if ($estadoDepto != "CERRADO"): ?>
        <form action="nuevo_registro.php" method="GET">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
            <button type="submit" class="btn btn-success"><i class='fas fa-plus'></i> Agregar Nuevo Registro</button>
        </form>
        <?php endif; ?>
    </div>
    <?php
      // Función para obtener el cierreo no de departamento
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

    // Función para obtener el nombre de la facultad
    function obtenerNombreFacultad($facultad_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT nombre_fac_minb FROM facultad WHERE PK_FAC = '$facultad_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['nombre_fac_minb'];
        } else {
            return "Facultad Desconocida";
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

    // Establecer conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }


    // Realizar la consulta a la base de datos
    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

    $result = $conn->query($sql);

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

        echo "<th rowspan='2'>Anexa HV Nuevos</th>
              <th rowspan='2'>Actualiza HV Antiguos</th>
              <th rowspan='2'>Acciones</th>
          </tr>";

        if ($tipo_docente == "Ocasional") {
            echo "<tr>
                      <th>Popayán</th>
                      <th>Regionalización</th>
                  </tr>";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr>
                      <th>Horas Pop</th>
                      <th>Horas Reg</th>
                  </tr>";
        }

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
        <form id="confirmForm" action="confirmar_tipo_d_depto.php" method="GET" onsubmit="return confirmarEnvio(<?php echo $count; ?>, '<?php echo $tipo_docente; ?>');">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
            <button type="submit" class="btn btn-primary"> <i class="fas fa-lock"></i>Confirmar Datos(Cerrar Estado)</button>
        </form>
                <?php endif; ?>

        <?php if ($estadoDepto == "CERRADO") { ;?>
        <form action="abrir_estado.php" method="POST">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
            <button type="submit" class="btn btn-warning"><i class="fas fa-unlock"></i>Abrir Estado</button>
        </form>
        <?php } ?>
        <!-- Botón "Ver Ocasional y Catedra" -->
        <div>
            <!-- Botón "Ver Ocasional y Catedra" con formulario oculto -->
        <form id="verOcasionalCatedraForm" action="consulta_todo_depto.php" method="POST" style="display: none;">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
        </form>
        <button type="button" class="btn btn-danger" onclick="enviarFormulario('verOcasionalCatedraForm')"><i class="fas fa-eye"></i> Ver Ocasional y Catedra</button>
        </div>
          <!-- Botón "Volver a Solicitudes" -->
        <a href="indexsolicitud.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i>ir a Solicitudes</a>
         <!-- Botón "Ver Departamento" -->
       <!--  <div>
            <a href="report_depto.php?departamento_id=<?php //echo urlencode($departamento_id); ?>&anio_semestre=<?php //echo urlencode($anio_semestre); ?>" class="btn btn-info">Resumen para enviar</a>
        </div>-->
    </div>
    
<script>
    document.getElementById('confirmForm').addEventListener('submit', function() {
        setTimeout(function() {
            location.reload();
        }, 3000); // Espera 3 segundos antes de recargar la página
    });
</script>
    <?php 
    
    
    $conn->close();
    
    ?>
</body>
</html>
