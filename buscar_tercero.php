     <?php
// Conectar a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el número de documento de la solicitud AJAX
$numDocumento = $_GET['num_documento'];
$anioSemestre = $_GET['anio_semestre'];

// Consulta para buscar el nombre del tercero por el número de documento
$sql = "
    SELECT nombre_completo 
    FROM tercero 
    JOIN aspirante ON documento_tercero = fk_asp_doc_tercero
    WHERE documento_tercero = '$numDocumento' 
    AND LEFT(fk_asp_periodo, 4) = LEFT('$anioSemestre', 4)";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Si se encontró un tercero con el número de documento proporcionado, devolver el nombre
    $row = $result->fetch_assoc();
    echo $row['nombre_completo'];
} else {
    // Si no se encontró ningún tercero, devolver una cadena vacía
    echo "verificar aspirante";
}

$conn->close();
?>
