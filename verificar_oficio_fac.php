<?php
// verificar_oficio.php

header('Content-Type: application/json');
require_once('conn.php'); // Asegúrate de que la ruta a tu conexión sea correcta

$response = ['existe' => false];

// Recibir los parámetros de la petición AJAX
$oficio = $_GET['oficio'] ?? '';
$anio_semestre = $_GET['anio_semestre'] ?? '';
$id_facultad = (int)($_GET['id_facultad'] ?? 0);

// Proceder solo si tenemos los datos necesarios
if (!empty($oficio) && !empty($anio_semestre) && $id_facultad > 0) {
    
    // La consulta busca un oficio con el mismo número, para la misma facultad y en el mismo período
    $sql = "SELECT COUNT(*) AS count 
            FROM solicitudes_working_copy 
            WHERE oficio_fac = ? 
            AND anio_semestre = ? 
            AND facultad_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $oficio, $anio_semestre, $id_facultad);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result && $result['count'] > 0) {
        $response['existe'] = true; // Si el conteo es mayor a 0, el oficio ya existe
    }
}

echo json_encode($response);
$conn->close();
?>