<?php
// Incluir la conexión a la base de datos
include 'conn.php';

// Incluir la función obtenerperiodo si no está en el mismo archivo
include 'funciones.php'; // Asegúrate de ajustar la ruta correctamente

// Archivo de log
$log_file = 'log_script.txt';

// Registrar el inicio del script
error_log("Inicio del script\n", 3, $log_file);

// Obtener los parámetros enviados por AJAX
$facultad_id = $_POST['facultad_id'];
$anio_semestre = $_POST['anio_semestre'];

// Log de parámetros recibidos
error_log("Parámetros recibidos - facultad_id: $facultad_id, anio_semestre: $anio_semestre\n", 3, $log_file);

// Obtener el estado del período
$cierreperiodo = obtenerperiodo($anio_semestre);

// Log del estado del período
error_log("Estado del período obtenido: $cierreperiodo\n", 3, $log_file);

// Verificar si el período está cerrado
if ($cierreperiodo == '1') {
    $response = array("status" => "closed", "message" => "El período está cerrado y no se puede deshacer el envío.");
    error_log("El período está cerrado. Respuesta enviada: " . json_encode($response) . "\n", 3, $log_file);
    echo json_encode($response);
} else {
    // Consulta de actualización
    $sql_update = "UPDATE fac_periodo 
                   SET fp_estado = '0', 
                       fp_num_oficio = NULL, 
                       fp_elaboro = NULL, 
                       fp_decano = NULL, 
                       fecha_accion = NULL 
                   WHERE fp_fk_fac = ? AND fp_periodo = ?";

    $stmt = $conn->prepare($sql_update);

    // Log de preparación de la consulta
    if (!$stmt) {
        error_log("Error preparando la consulta: " . $conn->error . "\n", 3, $log_file);
        echo json_encode(array("status" => "error", "message" => "Error preparando la consulta."));
        exit;
    }

    $stmt->bind_param("ss", $facultad_id, $anio_semestre);

    // Ejecutar la consulta
    if ($stmt->execute() === TRUE) {
        $response = array("status" => "success");
        error_log("Consulta ejecutada correctamente. Respuesta enviada: " . json_encode($response) . "\n", 3, $log_file);
        echo json_encode($response);
    } else {
        $response = array("status" => "error", "message" => "Error al actualizar el registro: " . $stmt->error);
        error_log("Error ejecutando la consulta: " . $stmt->error . "\n", 3, $log_file);
        echo json_encode($response);
    }

    // Cerrar el statement
    $stmt->close();
}

// Cerrar la conexión
$conn->close();

// Log de cierre del script
error_log("Script finalizado\n", 3, $log_file);
?>
