<?php
// procesar_seleccionados.php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente

// Incluye el archivo de conexión
include('cn.php');
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;

// Verifica si los datos fueron enviados correctamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibe los datos enviados desde el formulario o AJAX
    $idsSeleccionados = isset($_POST['ids_seleccionados']) ? $_POST['ids_seleccionados'] : null;
    $numeroOficio = isset($_POST['numero_oficio']) ? $_POST['numero_oficio'] : null;
    $fechaEnvio = isset($_POST['fecha_oficio']) ? $_POST['fecha_oficio'] : null;
    $enviadoPor = isset($_POST['remitente']) ? $_POST['remitente'] : null;

    // Verifica si todos los datos requeridos están presentes
    if (empty($idsSeleccionados) || empty($numeroOficio) || empty($fechaEnvio) || empty($enviadoPor)) {
        echo "Error: Faltan datos obligatorios.";
        exit;
    }

    // Convierte los IDs seleccionados en un array (si vienen como una cadena separada por comas)
    if (!is_array($idsSeleccionados)) {
        $idsSeleccionados = explode(',', $idsSeleccionados);
    }

    // Procesar cada ID seleccionado para hacer el UPDATE
    $errores = [];
    $exitos = 0;

    foreach ($idsSeleccionados as $id) {
        // Sanitiza el ID para evitar SQL Injection
        $id = intval($id);

        // Prepara la consulta SQL
        $sql = "UPDATE solicitudes_novedades
                SET sn_id_envio_fac = 2,
                    sn_fecha_envio_fac = ?,
                    sn_elaboro_fac = ?,
                    sn_envio_fac_of = ?
                WHERE id_novedad = ?";

        // Prepara la sentencia utilizando el sistema de prepared statements
        if ($stmt = $con->prepare($sql)) {
            // Vincula los parámetros a la consulta
            $stmt->bind_param("sssi", $fechaEnvio, $enviadoPor, $numeroOficio, $id);

            // Ejecuta la consulta
            if ($stmt->execute()) {
                $exitos++;
            } else {
                $errores[] = "Error al actualizar ID $id: " . $stmt->error;
            }

            // Cierra la sentencia
            $stmt->close();
        } else {
            $errores[] = "Error al preparar la consulta para ID $id: " . $con->error;
        }
    }

    // Muestra los resultados del procesamiento
    echo "<pre>";
    echo "Actualizaciones exitosas: $exitos<br>";
    if (!empty($errores)) {
        echo "Errores:<br>";
        print_r($errores);
    }
    echo "</pre>";
} else {
    echo "Error: Método no permitido.";
}
?>
