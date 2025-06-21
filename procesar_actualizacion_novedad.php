<?php
// Obtener los parámetros enviados por el formulario
$id_solicitud = isset($_POST['id_solicitud']) ? intval($_POST['id_solicitud']) : null;
$facultad_id = isset($_POST['facultad_id']) ? intval($_POST['facultad_id']) : null;
echo  "facultad: " .$facultad_id;
$departamento_id = isset($_POST['departamento_id']) ? intval($_POST['departamento_id']) : null;
echo  "de: " .$departamento_id;

$anio_semestre = isset($_POST['anio_semestre']) ? htmlspecialchars($_POST['anio_semestre']) : null;
$tipo_docente = isset($_POST['tipo_docente']) ? htmlspecialchars($_POST['tipo_docente']) : null;
$motivo = isset($_POST['motivo']) ? htmlspecialchars($_POST['motivo']) : null;
$usuario_id = isset($_POST['usuario_id']) ? htmlspecialchars($_POST['usuario_id']) : null;

$cedula = isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : null;
$nombre = isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : null;
$tipo_dedicacion = isset($_POST['tipo_dedicacion']) ? htmlspecialchars($_POST['tipo_dedicacion']) : null;
$tipo_dedicacion_r = isset($_POST['tipo_dedicacion_r']) ? htmlspecialchars($_POST['tipo_dedicacion_r']) : null;
$horas = isset($_POST['horas']) && !empty($_POST['horas']) ? $_POST['horas'] : 0;
$horas_r = isset($_POST['horas_r']) && !empty($_POST['horas_r']) ? $_POST['horas_r'] : 0;
if (isset($_POST['cambios'])) {
    $cambios = $_POST['cambios'];
    // Puedes registrar los cambios en un archivo de log, base de datos, o mostrarlos.
    //echo "Cambios realizados: " . htmlspecialchars($cambios);
}
if (($horas + $horas_r) > 12) {
  echo "<script>
        alert('El total de horas no puede ser mayor a 12 para el docente con cédula: $cedula');
        window.location.href = 'adicionar_novedad.php?facultad_id=" . htmlspecialchars($facultad_id) . "&departamento_id=" . htmlspecialchars($departamento_id) . "&anio_semestre=" . htmlspecialchars($anio_semestre) . "&tipo_docente=" . htmlspecialchars($tipo_docente) . "&tipo_usuario=" . htmlspecialchars($tipo_usuario) . "';
      </script>";

    exit;
}
$anexa_hv_docente_nuevo = isset($_POST['anexa_hv_docente_nuevo']) ? htmlspecialchars($_POST['anexa_hv_docente_nuevo']) : null;
$actualiza_hv_antiguo = isset($_POST['actualiza_hv_antiguo']) ? htmlspecialchars($_POST['actualiza_hv_antiguo']) : null;

// Determinar la sede en función del tipo de docente y dedicación
$sede = null;
if ($tipo_docente === "Ocasional") {
    $sede = empty($tipo_dedicacion) ? "Regionalización" : "Popayán";
} elseif ($tipo_docente === "Catedra") {
    if (!empty($horas) && !empty($horas_r)) {
        $sede = "Popayán-Regionalización";
    } elseif (!empty($horas)) {
        $sede = "Popayán";
    } else {
        $sede = "Regionalización";
    }
}

// Conectar a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Preparar la consulta SQL para `solicitudes_novedades`
$sql = "INSERT INTO solicitudes_novedades (facultad_id, departamento_id, periodo_anio, tipo_docente, tipo_usuario, tipo_novedad, detalle_novedad, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
// Construir el detalle_novedad como un array con claves modificadas
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
    'actualiza hv' => $actualiza_hv_antiguo,
    'motivo' => $motivo,
    'cambios' => $cambios,
    'fecha_sistema' => date('Y-m-d H:i:s') 

];

// Serializar el array a JSON
$detalle_novedad_json = json_encode($detalle_novedad_array);

$tipo_novedad = 'modificar';

// Reemplaza $tipo_usuario y $usuario_id con los valores correspondientes
$tipo_usuario = 3; // Ejemplo: puedes ajustar este valor según tu lógica

$stmt->bind_param(
    "iississi",
    $facultad_id,
    $departamento_id,
    $anio_semestre,
    $tipo_docente,
    $tipo_usuario, // Tipo de usuario
    $tipo_novedad, // Tipo de novedad
    $detalle_novedad_json,
    $usuario_id // ID del usuario que realiza la solicitud
);

// Ejecutar la consulta
if ($stmt->execute()) {
    echo "Registro insertado correctamente en solicitudes_novedades.";
    // Redirigir a la página de consulta con los parámetros originales
    echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
    echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
    echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
    echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
    echo "</form>";
    echo "<script>document.getElementById('redirectForm').submit();</script>";
    exit();
} else {
    echo "Error al insertar el registro: " . $stmt->error;
}

// Cerrar la conexión
$stmt->close();
$conn->close();
?>
