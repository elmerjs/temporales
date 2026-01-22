<?php
$facultad_id = $_GET['facultad_id'];
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$tipo_docente = $_GET['tipo_docente'];

$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Calcular el siguiente semestre
function obtenerSiguienteSemestre($anio_semestre) {
    list($anio, $semestre) = explode('-', $anio_semestre);
    
    if ($semestre == '1') {
        return $anio . '-2';
    } else {
        return (intval($anio) + 1) . '-1';
    }
}

// Determinar el tipo de docente alterno
function obtenerTipoDocenteAlterno($tipo_docente) {
    if ($tipo_docente == 'Ocasional') {
        return 'Catedra';
    } else {
        return 'Ocasional';
    }
}

$siguiente_semestre = obtenerSiguienteSemestre($anio_semestre);
$tipo_docente_alterno = obtenerTipoDocenteAlterno($tipo_docente);

// Consulta para obtener registros del siguiente semestre con tipo de docente alterno
$sql_verificacion = "SELECT solicitudes.cedula 
        FROM solicitudes 
        JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
        JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
        WHERE facultad_id = '$facultad_id' 
        AND departamento_id = '$departamento_id' 
        AND anio_semestre = '$siguiente_semestre' 
        AND tipo_docente = '$tipo_docente_alterno' 
        AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

$result_verificacion = $conn->query($sql_verificacion);

// Obtener cédulas de los registros que existen en el siguiente semestre
$cedulas_excluir = [];
if ($result_verificacion->num_rows > 0) {
    while ($row = $result_verificacion->fetch_assoc()) {
        $cedulas_excluir[] = $conn->real_escape_string($row['cedula']);
    }
}

// Consulta principal
$sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
        FROM solicitudes 
        JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
        JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
        WHERE facultad_id = '$facultad_id' 
        AND departamento_id = '$departamento_id' 
        AND anio_semestre = '$anio_semestre' 
        AND tipo_docente = '$tipo_docente' 
        AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

// Si hay cédulas para excluir, agregar condición a la consulta principal
if (!empty($cedulas_excluir)) {
    $cedulas_string = "'" . implode("','", $cedulas_excluir) . "'";
    $sql .= " AND solicitudes.cedula NOT IN ($cedulas_string)";
}

$result = $conn->query($sql);

$datos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
}

$conn->close();

echo json_encode($datos);
?>