<?php
require_once 'conn.php';

if (isset($_POST['guardar_plazos'])) {
    $periodo = $_POST['nombre_periodo'];
    
    // 1. Recibimos el nuevo campo de inicio
    $ini_jefe = $_POST['ini_plazo_jefe']; 
    $jefe = $_POST['plazo_jefe'];
    $fac = $_POST['plazo_fac'];
    $vra = $_POST['plazo_vra'];

    // 2. Actualizamos la consulta SQL para incluir ini_plazo_jefe
    $sql = "UPDATE periodo 
            SET ini_plazo_jefe=?, plazo_jefe=?, plazo_fac=?, plazo_vra=? 
            WHERE nombre_periodo=?";
            
    $stmt = $conn->prepare($sql);

    // 3. Ajustamos el bind_param: ahora son 5 "s" (porque hay 5 variables)
    $stmt->bind_param("sssss", $ini_jefe, $jefe, $fac, $vra, $periodo);

    if ($stmt->execute()) {
        header("Location: gestion_periodos.php?msg=ok");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>