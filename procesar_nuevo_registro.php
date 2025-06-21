<?php
// Establecer conexión a la base de datos
$nombre_sesion= $_POST['nombre_usuario'];

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
$anio_semestre_anterior = $_POST['anio_semestre_anterior'];

$tipo_docente = $_POST['tipo_docente'];
$cedula = $_POST['cedula'];
$nombre = $_POST['nombre'];
$observacion = isset($_POST['observacion']) ? $_POST['observacion'] : null; // Obtener observación o null si no existe
$tipo_reemplazo = isset($_POST['tipo_reemplazo']) ? $_POST['tipo_reemplazo'] : null;

// Extraer los primeros 4 dígitos del año_semestre
$anio = substr($anio_semestre, 0, 4);

// Consulta para verificar el documento del tercero en la base de datos
$verificarDocumentoSql = "
    SELECT COUNT(*) AS count 
    FROM aspirante 
    WHERE fk_asp_doc_tercero = ? 
    AND LEFT(fk_asp_periodo, 4) = ?";

// Preparar la consulta
$stmt = $conn->prepare($verificarDocumentoSql);

// Verificar si la preparación fue exitosa
if ($stmt === false) {
    die("Error al preparar la consulta: " . $conn->error);
}

// Asociar parámetros
$stmt->bind_param("ss", $cedula, $anio);

// Ejecutar la consulta
$stmt->execute();

// Obtener resultados
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Mostrar mensaje emergente y redirigir a nuevo_registro.php
    echo "<script>
            alert('El tercero no se encuentra como aspirante en la base de datos para este periodo. Por favor, verifica los datos o contacta al administrador.');
            window.location.href = 'nuevo_registro.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
          </script>";
    $stmt->close();
    $conn->close();
    exit(); // Detener la ejecución del script
}

// Verificar si el tercero ya está en la tabla solicitudes para el mismo periodo
// Verificar si la cédula es '222'
if ($cedula === '222') {
    // Omitir la verificación y proceder sin mensaje emergente
} else {
    // Verificar si el tercero ya está en la tabla solicitudes para el mismo periodo
    $verificarSolicitudSql = "SELECT COUNT(*) AS count FROM solicitudes 
                               WHERE cedula = ? AND anio_semestre = ? AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    $stmt = $conn->prepare($verificarSolicitudSql);
    $stmt->bind_param("ss", $cedula, $anio_semestre);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        // Mostrar mensaje emergente y redirigir a nuevo_registro.php
        echo "<script>
                alert('El tercero ya está registrado para este periodo. Por favor, verifica los datos o contacta al administrador.');
                window.location.href = 'nuevo_registro.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        $stmt->close();
        $conn->close();
        exit(); // Detener la ejecución del script
    }
}


// Validaciones adicionales según el tipo de docente
if ($tipo_docente == "Ocasional") {
    $tipo_dedicacion = $_POST['tipo_dedicacion'];
    $tipo_dedicacion_r = $_POST['tipo_dedicacion_r'];

    // Verificar que al menos uno de los campos tipo_dedicacion o tipo_dedicacion_r tenga valor
    if (empty($tipo_dedicacion) && empty($tipo_dedicacion_r)) {
        echo "<script>
                alert('Por favor diligencie al menos uno de los campos de tipo de dedicación.');
                window.location.href = 'nuevo_registro.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        $conn->close();
        exit(); // Detener la ejecución del script
    }

    $sede = empty($tipo_dedicacion) ? "Regionalización" : "Popayán";

} elseif ($tipo_docente == "Catedra") {
   $horas = (is_numeric($_POST['horas']) && $_POST['horas'] !== '') ? $_POST['horas'] : 0;
$horas_r = (is_numeric($_POST['horas_r']) && $_POST['horas_r'] !== '') ? $_POST['horas_r'] : 0;

if (($horas + $horas_r) > 12) {
        echo "<script>
                alert('El total de horas no puede ser mayor a 12 para el docente con cédula: $cedula');
                 window.location.href = 'nuevo_registro.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        exit;
    }
          
    // Verificar que al menos uno de los campos horas o horas_r tenga valor
    if (empty($horas) && empty($horas_r)) {
        echo "<script>
                alert('Por favor diligencie al menos uno de los campos de horas de dedicación.');
                window.location.href = 'nuevo_registro.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "';
              </script>";
        $conn->close();
        exit(); // Detener la ejecución del script
    }

    $sede = (!empty($horas) && !empty($horas_r)) ? "Popayán-Regionalización" : (!empty($horas) ? "Popayán" : "Regionalización");
} else {
    $sede = null; // Valor predeterminado si no coincide con los tipos de docente esperados
}

$anexa_hv_docente_nuevo = $_POST['anexa_hv_docente_nuevo'];
$actualiza_hv_antiguo = $_POST['actualiza_hv_antiguo'];
// Verificamos si hay observación
$tieneObservacion = !empty(trim($observacion));

if ($tipo_docente == "Ocasional") {
    if ($tieneObservacion) {
        $novedad = "adicionar";
        $sql = "INSERT INTO solicitudes (facultad_id, departamento_id, anio_semestre, tipo_docente, cedula, nombre, tipo_dedicacion, tipo_dedicacion_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, s_observacion, tipo_reemplazo,  novedad)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssssssssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $cedula, $nombre, $tipo_dedicacion, $tipo_dedicacion_r, $sede, $anexa_hv_docente_nuevo, $actualiza_hv_antiguo, $observacion,$tipo_reemplazo, $novedad);
    } else {
        $sql = "INSERT INTO solicitudes (facultad_id, departamento_id, anio_semestre, tipo_docente, cedula, nombre, tipo_dedicacion, tipo_dedicacion_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, s_observacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssssssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $cedula, $nombre, $tipo_dedicacion, $tipo_dedicacion_r, $sede, $anexa_hv_docente_nuevo, $actualiza_hv_antiguo, $observacion);
    }
} else {
   if ($tieneObservacion) {
    $novedad = "adicionar";
    $sql = "INSERT INTO solicitudes (facultad_id, departamento_id, anio_semestre, tipo_docente, cedula, nombre, horas, horas_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, s_observacion, tipo_reemplazo, novedad)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssssssssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $cedula, $nombre, $horas, $horas_r, $sede, $anexa_hv_docente_nuevo, $actualiza_hv_antiguo, $observacion, $tipo_reemplazo, $novedad);
}else {
        $sql = "INSERT INTO solicitudes (facultad_id, departamento_id, anio_semestre, tipo_docente, cedula, nombre, horas, horas_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, s_observacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssssssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $cedula, $nombre, $horas, $horas_r, $sede, $anexa_hv_docente_nuevo, $actualiza_hv_antiguo, $observacion);
    }
}
if ($stmt->execute()) {
    // Determinar la página de destino según el tipo de usuario
    $target_page = ($tipo_usuario != 1) ? 'consulta_todo_depto.php' : 'depto_comparativo.php';
    
    // Crear el formulario de redirección
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
