<?php
// Establecer conexión a la base de datos
$nombre_sesion = isset($_POST['nombre_usuario']) ? $_POST['nombre_usuario'] : '';

$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $tipo_usuario = $row['tipo_usuario'];
}

// Obtener los datos del formulario
$facultad_id = $_POST['facultad_id'];
$departamento_id = $_POST['departamento_id'];
$anio_semestre = $_POST['anio_semestre'];
$anio_semestre_anterior = isset($_POST['anio_semestre_anterior']) ? $_POST['anio_semestre_anterior'] : '';
$tipo_docente = $_POST['tipo_docente'];
$cedula = $_POST['cedula'];
$nombre = $_POST['nombre'];
$observacion = isset($_POST['observacion']) ? $_POST['observacion'] : null;
$tipo_reemplazo = isset($_POST['tipo_reemplazo']) ? $_POST['tipo_reemplazo'] : null;

// Obtener el campo anexos si existe
$anexos = isset($_POST['link_anexos']) ? $_POST['link_anexos'] : null;

// Extraer los primeros 4 dígitos del año_semestre
$anio = substr($anio_semestre, 0, 4);

// Consulta para verificar el documento del tercero en la base de datos
$verificarDocumentoSql = "
    SELECT COUNT(*) AS count 
    FROM aspirante 
    WHERE fk_asp_doc_tercero = ? 
    AND LEFT(fk_asp_periodo, 4) = ?";

$stmt = $conn->prepare($verificarDocumentoSql);
if ($stmt === false) {
    die("Error al preparar la consulta: " . $conn->error);
}

$stmt->bind_param("ss", $cedula, $anio);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "<script>
            alert('El tercero no se encuentra como aspirante en la base de datos para este periodo. Por favor, verifica los datos o contacta al administrador.');
            window.location.href = 'nuevo_registro_novedadb.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
          </script>";
    $stmt->close();
    $conn->close();
    exit();
}



// Verificar si la cédula no es '222'
if ($cedula !== '222') {
    $verificarSolicitudSql = "SELECT COUNT(*) AS count FROM solicitudes_working_copy
                              WHERE cedula = ?
                                AND anio_semestre = ?
                                AND (solicitudes_working_copy.estado <> 'an' OR solicitudes_working_copy.estado IS NULL)
                                AND (solicitudes_working_copy.novedad IS NULL OR solicitudes_working_copy.novedad <> 'Eliminar')"; // <--- ¡CAMBIO AQUÍ!
    $stmt = $conn->prepare($verificarSolicitudSql);
    $stmt->bind_param("ss", $cedula, $anio_semestre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo "<script>
                  alert('El tercero ya está registrado para este periodo con una novedad activa diferente a la de eliminación. Por favor, verifica los datos o contacta al administrador.'); // <--- Mensaje actualizado
                  window.location.href = 'nuevo_registro_novedadb.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        $stmt->close();
        $conn->close();
        exit();
    }
    // Si $row['count'] es 0, significa que no hay registros activos no-Eliminar para esa cédula/período,
    // por lo tanto, se permitirá el registro (incluso si hay un registro "Eliminar" activo).
}

// ... (El resto de tu código para insertar la nueva solicitud continuaría aquí) ...


// Validaciones adicionales según el tipo de docente
if ($tipo_docente == "Ocasional") {
    $tipo_dedicacion = $_POST['tipo_dedicacion'];
    $tipo_dedicacion_r = $_POST['tipo_dedicacion_r'];

    if (empty($tipo_dedicacion) && empty($tipo_dedicacion_r)) {
        echo "<script>
                alert('Por favor diligencie al menos uno de los campos de tipo de dedicación.');
                window.location.href = 'nuevo_registro_novedadb.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        $conn->close();
        exit();
    }

    $sede = empty($tipo_dedicacion) ? "Regionalización" : "Popayán";

} elseif ($tipo_docente == "Catedra") {
   $horas = (is_numeric($_POST['horas']) && $_POST['horas'] !== '') ? $_POST['horas'] : 0;
   $horas_r = (is_numeric($_POST['horas_r']) && $_POST['horas_r'] !== '') ? $_POST['horas_r'] : 0;

   if (($horas + $horas_r) > 12) {
        echo "<script>
                alert('El total de horas no puede ser mayor a 12 para el docente con cédula: $cedula');
                 window.location.href = 'nuevo_registro_novedadb.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        exit;
    }
          
    if (empty($horas) && empty($horas_r)) {
        echo "<script>
                alert('Por favor diligencie al menos uno de los campos de horas de dedicación.');
                window.location.href = 'nuevo_registro_novedadb.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        $conn->close();
        exit();
    }

    $sede = (!empty($horas) && !empty($horas_r)) ? "Popayán-Regionalización" : (!empty($horas) ? "Popayán" : "Regionalización");
} else {
    $sede = null;
}

$anexa_hv_docente_nuevo = $_POST['anexa_hv_docente_nuevo'];
$actualiza_hv_antiguo = $_POST['actualiza_hv_antiguo'];
$tieneObservacion = !empty(trim($observacion));

// Preparar la consulta SQL con el nuevo campo anexos
if ($tipo_docente == "Ocasional") {

        $novedad = "adicionar";
        $sql = "INSERT INTO solicitudes_working_copy (facultad_id, departamento_id, anio_semestre, tipo_docente, cedula, nombre, 
                tipo_dedicacion, tipo_dedicacion_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, 
                s_observacion, tipo_reemplazo, novedad, anexos)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssssssssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $cedula, $nombre, 
                          $tipo_dedicacion, $tipo_dedicacion_r, $sede, $anexa_hv_docente_nuevo, $actualiza_hv_antiguo, 
                          $observacion, $tipo_reemplazo, $novedad, $anexos);

} else {
 
    $novedad = "adicionar";
    $sql = "INSERT INTO solicitudes_working_copy (facultad_id, departamento_id, anio_semestre, tipo_docente, cedula, nombre, 
            horas, horas_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, s_observacion, 
            tipo_reemplazo, novedad, anexos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssssssssssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $cedula, $nombre, 
                      $horas, $horas_r, $sede, $anexa_hv_docente_nuevo, $actualiza_hv_antiguo, 
                      $observacion, $tipo_reemplazo, $novedad, $anexos);

}

if ($stmt->execute()) {
    $target_page = (isset($tipo_usuario) && $tipo_usuario == 1) ? 'consulta_todo_depto_novedad.php' : 'consulta_todo_depto_novedad.php';
    
    echo '<form id="redirectForm" action="'.$target_page.'" method="POST">
          <input type="hidden" name="departamento_id" value="'.htmlspecialchars($departamento_id).'">
          <input type="hidden" name="anio_semestre" value="'.htmlspecialchars($anio_semestre).'">
          <input type="hidden" name="anio_semestre_anterior" value="'.htmlspecialchars($anio_semestre_anterior).'">
          </form>
          <script>
              alert("Registro creado exitosamente.");
              document.getElementById("redirectForm").submit();
          </script>';
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
