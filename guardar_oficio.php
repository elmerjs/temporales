<?php
session_start();
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Iniciar transacción para integridad de datos
    $conn->begin_transaction();
    
    try {
        // 1. Recoger datos del formulario
        $numero_oficio = trim($_POST['numero_oficio']);
        $fecha_oficio = $_POST['fecha_oficio'];
        $vigencia_id = $_POST['vigencia_oficio'];
        $tipo_vinculacion = $_POST['tipo_vinculacion'];
        $sede = $_POST['sede'];
        
        if (empty($_POST['movimientos'])) {
            throw new Exception("Debe agregar al menos un movimiento");
        }

        // 2. Obtener detalle_vigencia_id
        $sql_detalle = "SELECT id, saldo_actual FROM detalle_vigencia 
                       WHERE vigencia_id = ? 
                       AND tipo_vinculacion = ? 
                       AND sede = ?";
        $stmt_detalle = $conn->prepare($sql_detalle);
        $stmt_detalle->bind_param("iss", $vigencia_id, $tipo_vinculacion, $sede);
        $stmt_detalle->execute();
        $result_detalle = $stmt_detalle->get_result();
        
        if ($result_detalle->num_rows == 0) {
            throw new Exception("No existe distribución para $tipo_vinculacion - $sede en esta vigencia");
        }
        
        $detalle_row = $result_detalle->fetch_assoc();
        $detalle_vigencia_id = $detalle_row['id'];
        $stmt_detalle->close();

        // 3. Verificar oficio existente en MISMA distribución
        $sql_check_oficio = "SELECT id FROM oficio 
                            WHERE numero = ? 
                            AND fecha = ?";
        $stmt_check_oficio = $conn->prepare($sql_check_oficio);
        $stmt_check_oficio->bind_param("ss", $numero_oficio, $fecha_oficio);
        $stmt_check_oficio->execute();
        $result_check_oficio = $stmt_check_oficio->get_result();
        
        if ($result_check_oficio->num_rows > 0) {
            // Usar oficio existente
            $oficio_row = $result_check_oficio->fetch_assoc();
            $oficio_id = $oficio_row['id'];
            
            // Actualizar fecha solo si es diferente
            $sql_update_fecha = "UPDATE oficio SET fecha = ? 
                                WHERE id = ? 
                                AND fecha != ?";
            $stmt_update = $conn->prepare($sql_update_fecha);
            $stmt_update->bind_param("sis", $fecha_oficio, $oficio_id, $fecha_oficio);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Insertar nuevo oficio
            $sql_oficio = "INSERT INTO oficio (numero, fecha, detalle_vigencia_id) 
                          VALUES (?, ?, ?)";
            $stmt_oficio = $conn->prepare($sql_oficio);
            $stmt_oficio->bind_param("ssi", $numero_oficio, $fecha_oficio, $detalle_vigencia_id);
            
            if (!$stmt_oficio->execute()) {
                throw new Exception("Error al guardar oficio: " . $stmt_oficio->error);
            }
            
            $oficio_id = $stmt_oficio->insert_id;
            $stmt_oficio->close();
        }
        $stmt_check_oficio->close();

        // 4. Procesar movimientos NUEVOS
        $movimientos = $_POST['movimientos'];
        $total_contrata = 0;
        $total_libera = 0;
        
        foreach ($movimientos as $mov) {
            $tipo_mov = $mov['tipo'];
            $cdp = $mov['cdp'];
            $valor = (float)$mov['valor'];
            
            // Insertar movimiento
            $sql_mov = "INSERT INTO movimiento (oficio_id, tipo_movimiento, cdp, valor) 
                        VALUES (?, ?, ?, ?)";
            $stmt_mov = $conn->prepare($sql_mov);
            $stmt_mov->bind_param("issd", $oficio_id, $tipo_mov, $cdp, $valor);
            
            if (!$stmt_mov->execute()) {
                throw new Exception("Error al guardar movimiento: " . $stmt_mov->error);
            }
            
            // Acumular para cálculo de saldo
            if ($tipo_mov == 'CONTRATA') {
                $total_contrata += $valor;
            } elseif ($tipo_mov == 'LIBERA') {
                $total_libera += $valor;
            }
            
            $stmt_mov->close();
        }
        
        // 5. Actualizar saldo (Libera - Contrata)
        $saldo_actualizado = $total_libera - $total_contrata;
        
        $sql_update_saldo = "UPDATE detalle_vigencia 
                            SET saldo_actual = saldo_actual + ? 
                            WHERE id = ?";
        $stmt_update_saldo = $conn->prepare($sql_update_saldo);
        $stmt_update_saldo->bind_param("di", $saldo_actualizado, $detalle_vigencia_id);
        
        if (!$stmt_update_saldo->execute()) {
            throw new Exception("Error al actualizar saldo: " . $stmt_update_saldo->error);
        }
        $stmt_update_saldo->close();
        
        // Confirmar transacción
        $conn->commit();
        $_SESSION['success'] = "Operación completada correctamente";
    } catch (Exception $e) {
        // Revertir en caso de error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
    
    $conn->close();
    header('Location: saldos_novedades.php');
    exit();
}
?>