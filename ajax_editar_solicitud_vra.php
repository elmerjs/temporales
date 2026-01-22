<?php
// ajax_editar_solicitud_vra.php
require_once('conn.php'); // Tu conexión a BD

// Respuesta JSON por defecto
header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Recibimos los datos
    $id_solicitud = $_POST['id_solicitud'] ?? null;
    $tipo_docente = $_POST['tipo_docente'] ?? null;
    
    // Datos opcionales según el tipo
    $horas = $_POST['horas'] ?? null;
    $horas_r = $_POST['horas_r'] ?? null;
    $dedicacion = $_POST['dedicacion'] ?? null;
    $dedicacion_r = $_POST['dedicacion_r'] ?? null;
    
    if (!$id_solicitud) throw new Exception('Falta el ID de la solicitud');

    // Construimos la consulta dinámica según lo que se envíe
    // NOTA: Trabajamos sobre solicitudes_working_copy
    $sql = "UPDATE solicitudes_working_copy SET ";
    $params = [];
    $types = "";

    if ($tipo_docente === 'Catedra') {
        $sql .= "horas = ?, horas_r = ? ";
        $params[] = $horas;
        $params[] = $horas_r;
        $types .= "ii"; // integer, integer (o 's' si son string)
    } elseif ($tipo_docente === 'Ocasional') {
        $sql .= "tipo_dedicacion = ?, tipo_dedicacion_r = ? ";
        $params[] = $dedicacion;
        $params[] = $dedicacion_r;
        $types .= "ss";
    } else {
        throw new Exception('Tipo de docente no válido para edición');
    }

    $sql .= "WHERE id_solicitud = ?";
    $params[] = $id_solicitud;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Solicitud actualizada correctamente'];
    } else {
        throw new Exception('Error al actualizar en BD: ' . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $response = ['message' => $e->getMessage()];
}

echo json_encode($response);
?>