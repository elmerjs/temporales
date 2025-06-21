<?php
include 'conn.php';

$departamento_id = $_POST['departamento_id'];
$anio_semestre = $_POST['anio_semestre'];
$visto_bueno_ids = isset($_POST['visto_bueno']) ? $_POST['visto_bueno'] : [];

if (!empty($visto_bueno_ids)) {
    $ids = implode(",", array_map('intval', $visto_bueno_ids));
    $sql = "UPDATE solicitudes SET visado = 1 WHERE id_solicitud IN ($ids)";
    if ($conn->query($sql) === TRUE) {
        echo "Records updated successfully";
    } else {
        echo "Error updating records: " . $conn->error;
    }
} else {
    echo "No records selected";
}

// Redirigir de vuelta a la página principal
header("Location: consulta_todo_depto.php?departamento_id=$departamento_id&anio_semestre=$anio_semestre");
exit();
?>
