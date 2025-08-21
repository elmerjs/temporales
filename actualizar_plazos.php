<?php
require_once 'conn.php';

if (isset($_POST['guardar_plazos'])) {
    $periodo = $_POST['nombre_periodo'];
    $jefe = $_POST['plazo_jefe'];
    $fac = $_POST['plazo_fac'];
    $vra = $_POST['plazo_vra'];

    $sql = "UPDATE periodo 
            SET plazo_jefe=?, plazo_fac=?, plazo_vra=? 
            WHERE nombre_periodo=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $jefe, $fac, $vra, $periodo);

    if ($stmt->execute()) {
        header("Location: gestion_periodos.php?msg=ok");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
