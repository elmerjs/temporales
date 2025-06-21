<?php
// Conectar a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obtener los datos del formulario
$id_solicitud = $_POST['id_solicitud'];
$visto_bueno = isset($_POST['visto_bueno']) ? 1 : 0;

// Actualizar el estado del visto bueno
$sql = "UPDATE solicitudes SET visado = ? WHERE id_solicitud = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $visto_bueno, $id_solicitud);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Estado actualizado']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado']);
}

$stmt->close();
$conn->close();
?>