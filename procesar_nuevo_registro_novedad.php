<?php
// Establecer conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Obtener los datos del formulario
$facultad_id = $_POST['facultad_id'];
$departamento_id = $_POST['departamento_id'];
$anio_semestre = $_POST['anio_semestre'];
$tipo_docente = $_POST['tipo_docente'];
$tipo_usuario = $_POST['tipo_usuario'];
$usuario_id = $_POST['usuario_id'];

$cedula = $_POST['cedula'];
$nombre = $_POST['nombre'];

// Verificar si el documento del tercero está en la base de datos
$verificarDocumentoSql = "SELECT COUNT(*) AS count FROM tercero 
                           WHERE documento_tercero = ? AND oferente_periodo = 1";
$stmt = $conn->prepare($verificarDocumentoSql);
$stmt->bind_param("s", $cedula);
$stmt->execute();
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
$stmt->bind_param("si", $cedula, $anio_semestre);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Verificar si el tercero ya está en la tabla solicitudes_novedades con tipo_novedad = 'adicionar' y cédula en detalle_novedad
$verificarNovedadesSql = "SELECT COUNT(*) AS count FROM solicitudes_novedades 
                           WHERE JSON_UNQUOTE(JSON_EXTRACT(detalle_novedad, '$.cedula')) = ? 
                           AND tipo_novedad = 'adicionar' 
                           AND sn_acepta_fac <> 1 
                           AND periodo_anio = ?";
$stmt = $conn->prepare($verificarNovedadesSql);
$stmt->bind_param("si", $cedula, $anio_semestre);
$stmt->execute();
$result = $stmt->get_result();
$rowNovedades = $result->fetch_assoc();

if ($row['count'] > 0 || $rowNovedades['count'] > 0) {
    // Mostrar mensaje emergente y redirigir a adicionar_novedad.php
    echo "<script>
            alert('El tercero ya está registrado para este periodo o como novedad. Por favor, verifica los datos o contacta al administrador.');
            window.location.href = 'adicionar_novedad.php?facultad_id=" . htmlspecialchars($facultad_id) . 
                                  "&departamento_id=" . htmlspecialchars($departamento_id) . 
                                  "&anio_semestre=" . htmlspecialchars($anio_semestre) . 
                                  "&tipo_docente=" . htmlspecialchars($tipo_docente) . 
                                  "&tipo_usuario=" . htmlspecialchars($tipo_usuario) . "'; 
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
  $horas = isset($_POST['horas']) && !empty($_POST['horas']) ? $_POST['horas'] : 0;
$horas_r = isset($_POST['horas_r']) && !empty($_POST['horas_r']) ? $_POST['horas_r'] : 0;

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

// Preparar la consulta SQL para `solicitudes_novedades`
$sql = "INSERT INTO solicitudes_novedades (facultad_id, departamento_id, periodo_anio, tipo_docente, tipo_usuario, tipo_novedad, detalle_novedad, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Construir el detalle_novedad como un array
$detalle_novedad_array = [
    'cedula' => $cedula,
    'nombre' => $nombre,
    // Cambiar 'horas' por 'hrs pop' y 'horas_r' por 'hrs reg' si existe
    'hrs pop' => isset($horas) ? $horas : 0,
    'hrs reg' => isset($horas_r) ? $horas_r : 0,
    // Cambiar 'tipo_dedicacion' por 'dedic pop' y 'tipo_dedicacion_r' por 'dedic reg' si existe
    'dedic pop' => isset($tipo_dedicacion) ? $tipo_dedicacion : null,
    'dedic reg' => isset($tipo_dedicacion_r) ? $tipo_dedicacion_r : null,
    'sede' => $sede,
    // Cambiar los títulos 'anexa_hv_docente_nuevo' y 'actualiza_hv_antiguo' según corresponda
    'anexa hv' => $anexa_hv_docente_nuevo,
    'actlz hv' => $actualiza_hv_antiguo,
    'fecha_sistema' => date('Y-m-d H:i:s') 

];

// Serializar el array a JSON
//$detalle_novedad_json = json_encode($detalle_novedad_array);
$detalle_novedad_json = json_encode($detalle_novedad_array, JSON_UNESCAPED_UNICODE);

// Si necesitas imprimir o usar el JSON
$tipo_novedad = 'adicionar';

$stmt->bind_param(
    "iississi",
    $facultad_id,
    $departamento_id,
    $anio_semestre,
    $tipo_docente,
    $tipo_usuario, // Tipo de usuario
    $tipo_novedad, // Tipo de novedad
    $detalle_novedad_json,
    $usuario_id // Reemplaza con el ID del usuario que está haciendo la solicitud
);

// Ejecutar la consulta SQL
if ($stmt->execute()) {
    // Crear el formulario para redirigir
    echo '<form id="redirectForm" action="consulta_todo_depto.php" method="POST">';
    echo '<input type="hidden" name="departamento_id" value="' . htmlspecialchars($departamento_id) . '">';
    echo '<input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">';
    echo '</form>';

    // Mostrar mensaje emergente y luego enviar el formulario
    echo '<script>
        alert("Solicitud  creado exitosamente en novedades, esté atento a su aprobación.");
        document.getElementById("redirectForm").submit();
    </script>';
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
