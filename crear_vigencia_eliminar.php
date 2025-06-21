<?php
session_start();
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $anio_vigencia = $_POST['anio_vigencia'];
    
    // Verificar si ya existe la vigencia
    $sql_check = "SELECT id FROM vigencia WHERE anio = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $anio_vigencia);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $_SESSION['error'] = "Ya existe una vigencia para el año $anio_vigencia";
    } else {
        // Insertar nueva vigencia
        $sql = "INSERT INTO vigencia (anio) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $anio_vigencia);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Vigencia $anio_vigencia creada exitosamente";
            
            // Crear registros vacíos en detalle_vigencia
            $new_id = $stmt->insert_id;
            $tipos = ['OCASIONAL', 'CATEDRA'];
            // Agregar FSALUD a las sedes
            $sedes = ['POP', 'REGI', 'FSALUD'];
            
            $sql_detalle = "INSERT INTO detalle_vigencia (vigencia_id, tipo_vinculacion, sede, saldo_inicial, saldo_actual) VALUES (?, ?, ?, 0, 0)";
            $stmt_detalle = $conn->prepare($sql_detalle);
            
            foreach ($tipos as $tipo) {
                foreach ($sedes as $sede) {
                    $stmt_detalle->bind_param("iss", $new_id, $tipo, $sede);
                    $stmt_detalle->execute();
                }
            }
            $stmt_detalle->close();
        } else {
            $_SESSION['error'] = "Error al crear vigencia: " . $stmt->error;
        }
        $stmt->close();
    }
    
    $stmt_check->close();
    $conn->close();
    
    header('Location: saldos_novedades.php');
    exit();
}
?>