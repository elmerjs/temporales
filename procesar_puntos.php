<?php
require 'vendor/autoload.php'; // Asegúrate de tener PhpSpreadsheet instalado y esta línea

use PhpOffice\PhpSpreadsheet\IOFactory;

// Conexión a la base de datos (ajusta con tus credenciales)
$mysqli = new mysqli("localhost", "root", "", "contratacion_temporales");
if ($mysqli->connect_errno) {
    die("Fallo la conexión a MySQL: " . $mysqli->connect_error);
}

// Verificar que se haya enviado el periodo y el archivo
if (!isset($_POST['periodo']) || !isset($_FILES['file'])) {
    http_response_code(400); // Bad Request
    die("Error: Debe proporcionar el periodo (anio_semestre) y el archivo Excel.");
}

$anio_semestre = $_POST['periodo'];
$archivo_temporal = $_FILES['file']['tmp_name'];

try {
    // Cargar el archivo Excel
    $spreadsheet = IOFactory::load($archivo_temporal);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    $actualizaciones_exitosas = 0;
    
    // Procesar cada fila (desde la segunda, ignorando la primera de encabezados)
    // Las columnas en PhpSpreadsheet se indexan desde 0
    // Columna D (Identificación) = índice 3
    // Columna S (Total Puntos) = índice 18
    for ($i = 1; $i < count($rows); $i++) {
        $cedula = $mysqli->real_escape_string(trim($rows[$i][3])); // Columna D
        $puntos = floatval($rows[$i][18]); // Columna S
        
        // --- DEPURACIÓN: Imprime los valores que se están leyendo ---
        //echo "Depuración: Fila $i - Intentando actualizar cédula: $cedula con puntos: $puntos para el periodo: $anio_semestre\n";
        
        if ($cedula != '') {
            $sql = "
                    UPDATE solicitudes 
                    SET puntos = ?
                    WHERE anio_semestre = ? 
                      AND cedula = ?
                      AND (puntos IS NULL OR puntos = '' OR puntos = 0)
                      
                ";

            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("dss", $puntos, $anio_semestre, $cedula);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $actualizaciones_exitosas++;
                echo "✅ Éxito: Registro actualizado para la cédula $cedula.\n";
            } else {
              //  echo "❌ Fallo: No se actualizó ningún registro para la cédula $cedula. Puede que no cumpla las condiciones del WHERE.\n";
            }
        }
    }

    echo "Actualización completada para el periodo " . htmlspecialchars($anio_semestre) . ". Se actualizaron $actualizaciones_exitosas registros.";

} catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    http_response_code(500); // Internal Server Error
    die("Error al cargar el archivo Excel: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    die("Error inesperado: " . $e->getMessage());
} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>