<?php
session_start();
require 'conn.php';

$tipo_vinculacion = $_POST['tipo_vinculacion'] ?? '';
$sede = $_POST['sede'] ?? '';

try {
    $ultimo_cdp = '';
    
    if ($tipo_vinculacion && $sede) {
        // Obtener el último CDP para este tipo/sede
        $sql = "SELECT movimiento.cdp
                FROM movimiento
                JOIN oficio ON oficio.id = movimiento.oficio_id
                JOIN detalle_vigencia ON detalle_vigencia.id = oficio.detalle_vigencia_id
               
                ORDER BY movimiento.id DESC
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $tipo_vinculacion, $sede);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $ultimo_cdp = $row['cdp'];
        }
    }
    
    echo json_encode(['ultimo_cdp' => $ultimo_cdp]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>