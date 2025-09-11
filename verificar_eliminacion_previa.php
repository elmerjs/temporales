<?php
// verificar_eliminacion_previa.php

header('Content-Type: application/json');
require_once('conn.php');

// 1. Recibir y sanitizar los datos de entrada
$cedula = $_GET['cedula'] ?? '';
$anio_semestre = $_GET['anio_semestre'] ?? '';
$departamento_id = $_GET['departamento_id'] ?? 0;

if (empty($cedula) || empty($anio_semestre) || empty($departamento_id)) {
    // Si faltan datos, no hacemos nada.
    echo json_encode(['encontrado' => false]);
    exit;
}

// 2. Preparar y ejecutar la consulta de forma segura
$sql = "SELECT s_observacion 
        FROM solicitudes_working_copy 
        WHERE cedula = ? 
          AND anio_semestre = ? 
          AND departamento_id = ?
          AND novedad = 'Eliminar'
          AND estado_depto = 'PENDIENTE'
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $cedula, $anio_semestre, $departamento_id);
$stmt->execute();
$result = $stmt->get_result();

$response = ['encontrado' => false];

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $response['encontrado'] = true;
    $response['observacion'] = $row['s_observacion'];
}

$stmt->close();
$conn->close();

// 3. Devolver el resultado en formato JSON
echo json_encode($response);
?>