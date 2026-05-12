<?php
session_start();
require 'funciones.php'; // Si existe alguna función auxiliar

// Verificar sesión (opcional según tu sistema)
if (!isset($_SESSION['name'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Leer datos JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['periodo']) || !isset($input['puntos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$periodo = $input['periodo'];
$puntos = $input['puntos']; // Array de objetos {cedula, puntos}

if (empty($puntos)) {
    echo json_encode(['success' => false, 'message' => 'No se enviaron registros']);
    exit;
}

// Conexión a BD
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$actualizados = 0;
$errores = [];

foreach ($puntos as $item) {
    $cedula = $conn->real_escape_string($item['cedula']);
    $punto = floatval($item['puntos']);
    
    // Verificar que exista la solicitud para ese periodo y cédula
    $sql_check = "SELECT id_solicitud FROM solicitudes WHERE anio_semestre = '$periodo' AND cedula = '$cedula'";
    $res = $conn->query($sql_check);
    if ($res && $res->num_rows > 0) {
        $sql_update = "UPDATE solicitudes SET puntos = $punto WHERE anio_semestre = '$periodo' AND cedula = '$cedula'";
        if ($conn->query($sql_update)) {
            $actualizados++;
        } else {
            $errores[] = "Cédula $cedula: " . $conn->error;
        }
    } else {
        $errores[] = "Cédula $cedula no encontrada en el periodo $periodo";
    }
}

$conn->close();

if (empty($errores)) {
    echo json_encode(['success' => true, 'message' => "$actualizados registro(s) actualizado(s)"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Algunos registros fallaron', 'errors' => $errores]);
}
?>