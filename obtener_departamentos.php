<?php
// obtener_departamentos.php

// Conectar a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de la facultad seleccionada desde la solicitud GET
$facultad_id = $_GET['facultad_id'];

// Obtener los departamentos correspondientes a la facultad seleccionada
$sql = "SELECT PK_DEPTO, depto_nom_propio FROM deparmanentos WHERE FK_FAC = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $facultad_id);
$stmt->execute();
$result = $stmt->get_result();

// Crear un array para almacenar los departamentos
$departamentos = array();
while ($row = $result->fetch_assoc()) {
    $departamentos[] = array(
        'id' => $row['PK_DEPTO'],
        'nombre' => $row['depto_nom_propio']
    );
}

// Devolver los departamentos como JSON
echo json_encode($departamentos);

// Cerrar la conexión
$stmt->close();
$conn->close();
?>
