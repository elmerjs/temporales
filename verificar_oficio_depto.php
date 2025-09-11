<?php
// verificar_oficio_depto.php

header('Content-Type: application/json');
require_once('conn.php'); // Asegúrate de que la ruta a tu conexión sea correcta

$response = ['existe' => false];

// Recibir los parámetros de la petición AJAX
$oficio = $_GET['oficio'] ?? '';
$anio_semestre = $_GET['anio_semestre'] ?? '';
$id_departamento = (int)($_GET['departamento_id'] ?? 0);

if (!empty($oficio) && !empty($anio_semestre) && $id_departamento > 0) {
    
    // La consulta busca en la columna 'oficio_depto'
    $sql = "SELECT COUNT(*) AS count 
            FROM solicitudes_working_copy 
            WHERE oficio_depto = ? 
            AND anio_semestre = ? 
            AND departamento_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $oficio, $anio_semestre, $id_departamento);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result && $result['count'] > 0) {
        $response['existe'] = true;
    }
}

echo json_encode($response);
$conn->close();
?>