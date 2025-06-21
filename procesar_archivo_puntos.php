<?php
require 'vendor/autoload.php'; // Asegúrate de tener PhpSpreadsheet instalado

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Conexión a la base de datos (ajusta con tus credenciales)
$mysqli = new mysqli("localhost", "root", "", "contratacion_temporales");
if ($mysqli->connect_errno) {
    die("Fallo la conexión a MySQL: " . $mysqli->connect_error);
}

if (!isset($_POST['periodo'])) {
    die("Debe proporcionar el periodo (anio_semestre) en el formulario.");
}
$anio_semestre = $_POST['periodo'];

// Verificar que se subió el archivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo = $_FILES['archivo_excel']['tmp_name'];

    // Cargar el archivo Excel
    $spreadsheet = IOFactory::load($archivo);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Leer encabezados
    $encabezados = array_map('strtolower', $rows[0]);

    $colCedula = array_search('cedula', $encabezados);
    $colPuntos = array_search('puntos', $encabezados);

    if ($colCedula === false || $colPuntos === false) {
        die("El archivo debe tener columnas 'cedula' y 'puntos'");
    }

    // Procesar cada fila (desde la segunda)
    for ($i = 1; $i < count($rows); $i++) {
        $cedula = $mysqli->real_escape_string(trim($rows[$i][$colCedula]));
        $puntos = floatval($rows[$i][$colPuntos]);

        if ($cedula != '') {
            $sql = "UPDATE solicitudes SET puntos = $puntos 
                    WHERE anio_semestre = '$anio_semestre' AND cedula = '$cedula'";
            $mysqli->query($sql);
        }
    }

    echo "Actualización completada para el periodo $anio_semestre.";
} else {
    // Formulario para subir el archivo
    echo <<<HTML
    <form method="POST" enctype="multipart/form-data">
        <label>Selecciona archivo Excel (.xlsx):</label>
        <input type="file" name="archivo_excel" accept=".xlsx" required>
        <button type="submit">Subir y actualizar</button>
    </form>
HTML;
}
?>
