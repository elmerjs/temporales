<?php
// buscar_aspirante.php
require 'conn.php'; // Asegúrate de incluir tu conexión

$cedula = $_GET['cedula'] ?? '';
$periodo = $_GET['periodo'] ?? '';

if (!$cedula || !$periodo) {
    echo json_encode(['error' => 'Faltan datos']);
    exit;
}

// Buscamos solo si es aspirante en el periodo actual
$sql = "SELECT t.nombre_completo, a.asp_titulos, a.asp_correo, a.asp_celular 
        FROM aspirante a 
        JOIN tercero t ON a.fk_asp_doc_tercero = t.documento_tercero 
        WHERE a.fk_asp_doc_tercero = ? AND a.fk_asp_periodo = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $cedula, $periodo);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode(['found' => true, 'data' => $row]);
} else {
    echo json_encode(['found' => false]);
}
?>