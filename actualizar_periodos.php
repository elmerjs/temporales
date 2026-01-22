<?php
require 'conn.php';
header('Content-Type: application/json');

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Validación de datos requeridos
if (!isset($_POST['periodo'], $_POST['field'], $_POST['value'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$periodo = $_POST['periodo'];
$field = $_POST['field'];
$value = $_POST['value'];

// Validar campos permitidos
$campos_permitidos = ['inicio_sem', 'fin_sem', 'inicio_sem_oc', 'fin_sem_oc', 'valor_punto'];
if (!in_array($field, $campos_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Campo no permitido']);
    exit;
}

// Función para calcular semanas entre dos fechas
function calcularSemanas($conn, $periodo) {
    $query = $conn->prepare("SELECT inicio_sem, fin_sem FROM periodo WHERE nombre_periodo = ?");
    $query->bind_param("s", $periodo);
    $query->execute();
    $result = $query->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $inicio_sem = $row['inicio_sem'];
        $fin_sem = $row['fin_sem'];
        
        // Si alguna fecha es NULL, no se puede calcular
        if (empty($inicio_sem) || empty($fin_sem)) {
            return false;
        }
        
        // Convertir a objetos DateTime
        $fecha_inicio = new DateTime($inicio_sem);
        $fecha_fin = new DateTime($fin_sem);
        
        // Calcular diferencia en días
        $diferencia = $fecha_inicio->diff($fecha_fin);
        $dias = $diferencia->days;
        
        // Calcular semanas (redondeando hacia arriba)
        $semanas = ceil($dias / 7);
        
        return $semanas;
    }
    
    return false;
}

try {
    // Construir el SQL para mostrar en consola
    $sql_consola = "UPDATE periodo SET $field = ";
    
    if ($value === '') {
        $valor_nulo = null;
        $sql_consola .= "NULL";
        $stmt = $conn->prepare("UPDATE periodo SET $field = ? WHERE nombre_periodo = ?");
        $stmt->bind_param("ss", $valor_nulo, $periodo);
    } else {
        if (in_array($field, ['inicio_sem', 'fin_sem', 'inicio_sem_oc', 'fin_sem_oc'])) {
            // Validar formato de fecha
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido. Use AAAA-MM-DD']);
                exit;
            }
            $sql_consola .= "'$value'";
            $stmt = $conn->prepare("UPDATE periodo SET $field = ? WHERE nombre_periodo = ?");
            $stmt->bind_param("ss", $value, $periodo);
        } elseif ($field === 'valor_punto') {
            if (!is_numeric($value)) {
                echo json_encode(['success' => false, 'message' => 'El valor del punto debe ser numérico']);
                exit;
            }
            $valor_float = floatval($value);
            $sql_consola .= $valor_float;
            $stmt = $conn->prepare("UPDATE periodo SET $field = ? WHERE nombre_periodo = ?");
            $stmt->bind_param("ds", $valor_float, $periodo);
        }
    }
    
    $sql_consola .= " WHERE nombre_periodo = '$periodo'";
    
    // Registrar el SQL en el log de errores de PHP (visible en la consola)
    error_log("SQL a ejecutar: ".$sql_consola);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            
            // CALCULAR Y ACTUALIZAR SEMANAS_C SI SE MODIFICÓ inicio_sem O fin_sem
            if (in_array($field, ['inicio_sem', 'fin_sem'])) {
                $semanas_c = calcularSemanas($conn, $periodo);
                
                if ($semanas_c !== false) {
                    $update_semanas = $conn->prepare("UPDATE periodo SET semanas_c = ? WHERE nombre_periodo = ?");
                    $update_semanas->bind_param("is", $semanas_c, $periodo);
                    
                    if ($update_semanas->execute()) {
                        $sql_consola .= "; UPDATE periodo SET semanas_c = $semanas_c WHERE nombre_periodo = '$periodo'";
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Actualización exitosa y semanas calculadas', 
                            'sql' => $sql_consola,
                            'semanas_calculadas' => $semanas_c
                        ]);
                    } else {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Actualización exitosa pero error al calcular semanas', 
                            'sql' => $sql_consola,
                            'error_semanas' => $update_semanas->error
                        ]);
                    }
                    $update_semanas->close();
                } else {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Actualización exitosa pero no se pudieron calcular semanas (faltan fechas)',
                        'sql' => $sql_consola
                    ]);
                }
            } else {
                echo json_encode(['success' => true, 'message' => 'Actualización exitosa', 'sql' => $sql_consola]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios', 'sql' => $sql_consola]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error en la ejecución: ' . $stmt->error,
            'sql' => $sql_consola,
            'error_info' => $stmt->error_info()
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'sql' => $sql_consola ?? 'No generado'
    ]);
}

$conn->close();