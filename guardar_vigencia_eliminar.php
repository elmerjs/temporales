<?php
session_start();
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vigencia_id = $_POST['vigencia'];
    $ocasional_pop = $_POST['ocasional_pop'];
    $ocasional_regi = $_POST['ocasional_regi'];
    $catedra_pop = $_POST['catedra_pop'];
    $catedra_regi = $_POST['catedra_regi'];
    
    // Insertar/actualizar detalles de vigencia
    $sql = "INSERT INTO detalle_vigencia (vigencia_id, tipo_vinculacion, sede, saldo_inicial, saldo_actual) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                saldo_inicial = VALUES(saldo_inicial),
                saldo_actual = VALUES(saldo_actual)";
    
    $stmt = $conn->prepare($sql);
    
    // Definir los tipos y sedes con sus valores
    $registros = [
        ['OCASIONAL', 'POP', $ocasional_pop],
        ['OCASIONAL', 'REGI', $ocasional_regi],
        ['CATEDRA', 'POP', $catedra_pop],
        ['CATEDRA', 'REGI', $catedra_regi],
    ];
    
    foreach ($registros as $registro) {
        $tipo = $registro[0];
        $sede = $registro[1];
        $valor = $registro[2];
        
        $stmt->bind_param("issdd", $vigencia_id, $tipo, $sede, $valor, $valor);
        $stmt->execute();
    }
    
    $_SESSION['success'] = "Distribución actualizada correctamente";
    
    $stmt->close();
    $conn->close();
    
    header('Location: saldos_novedades.php');
    exit();
}