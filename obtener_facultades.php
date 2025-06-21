<?php
header('Content-Type: application/json'); // Indicar que la respuesta es JSON

// Incluye tu archivo de conexión a la base de datos
// Asegúrate de que 'conn.php' tiene el código de conexión mysqli ($conn)
require_once 'conn.php';

$response = [];

try {
    // Usar el objeto de conexión $conn de mysqli
    $result = $conn->query("SELECT PK_FAC, nombre_fac_min FROM facultad WHERE 1 ORDER BY nombre_fac_min ASC");

    if ($result) { // Verificar si la consulta fue exitosa
        $facultades = [];
        while ($row = $result->fetch_assoc()) { // Iterar y obtener cada fila como array asociativo
            $facultades[] = $row;
        }
        $response = $facultades;
        $result->free(); // Liberar los resultados de la consulta
    } else {
        // Si la consulta falló, capturar el error de MySQLi
        throw new Exception("Error en la consulta SQL: " . $conn->error);
    }

} catch (Exception $e) {
    // En un entorno de producción, podrías loggear el error en lugar de mostrarlo
    error_log("Error al obtener facultades: " . $e->getMessage()); // Siempre es buena idea loggear
    $response = ['error' => 'Error al obtener las facultades. Por favor, inténtalo de nuevo más tarde.'];
    http_response_code(500); // Internal Server Error
} finally {
    // Es buena práctica cerrar la conexión cuando ya no se necesita, especialmente en scripts cortos.
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo json_encode($response);
?>