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
            echo json_encode(['success' => true, 'message' => 'Actualización exitosa', 'sql' => $sql_consola]);
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