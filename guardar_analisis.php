<?php
// guardar_analisis.php

include('cn.php'); // Archivo de conexión a la base de datos

// Verificación básica de datos
if (!isset($_POST['departamento_id']) || !isset($_POST['anio_semestre']) || !isset($_POST['tipo'])) {
    die(json_encode(['success' => false, 'message' => 'Datos incompletos']));
}

$departamento_id = intval($_POST['departamento_id']);
$anio_semestre = $con->real_escape_string($_POST['anio_semestre']);
$tipo = $_POST['tipo'];

// Procesamiento según el tipo de operación
switch ($tipo) {
    case 'analisis':
        if (!isset($_POST['dp_analisis'])) {
            die(json_encode(['success' => false, 'message' => 'Falta texto de análisis']));
        }
        $valor = trim($_POST['dp_analisis']);
        $campo = 'dp_analisis';
        break;
        
    case 'devolucion':
        if (!isset($_POST['dp_devolucion'])) {
            die(json_encode(['success' => false, 'message' => 'Falta tipo de devolución']));
        }
        $valor = $_POST['dp_devolucion'];
        $opciones_validas = ['', 'Dedic_vinculacion', 'Soportes', 'Ambos'];
        if (!in_array($valor, $opciones_validas)) {
            die(json_encode(['success' => false, 'message' => 'Tipo de devolución no válido']));
        }
        $campo = 'dp_devolucion';
        break;
        
    case 'visado':
        $valor = isset($_POST['dp_visado']) ? 1 : 0;
        $campo = 'dp_visado';
        break;
        
    default:
        die(json_encode(['success' => false, 'message' => 'Tipo de operación no válido']));
}

// Consulta para verificar existencia
$check_sql = "SELECT id_depto_periodo FROM depto_periodo WHERE fk_depto_dp = ? AND periodo = ?";
$check_stmt = $con->prepare($check_sql);

if (!$check_stmt) {
    die(json_encode(['success' => false, 'message' => 'Error en preparación: ' . $con->error]));
}

$check_stmt->bind_param('is', $departamento_id, $anio_semestre);
$check_stmt->execute();
$exists = $check_stmt->get_result()->num_rows > 0;
$check_stmt->close();

// Construcción dinámica de la consulta
if ($exists) {
    $sql = "UPDATE depto_periodo SET $campo = ? WHERE fk_depto_dp = ? AND periodo = ?";
    $stmt = $con->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sis', $valor, $departamento_id, $anio_semestre);
    }
} else {
    $sql = "INSERT INTO depto_periodo (fk_depto_dp, periodo, $campo) VALUES (?, ?, ?)";
    $stmt = $con->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iss', $departamento_id, $anio_semestre, $valor);
    }
}

// Ejecución y respuesta
if ($stmt && $stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        header('Location: report_depto_comparativo.php?anio_semestre=' . urlencode($anio_semestre));
        exit;
    } else {
        die(json_encode(['success' => false, 'message' => 'No se realizaron cambios']));
    }
} else {
    $error = $stmt ? $stmt->error : $con->error;
    die(json_encode(['success' => false, 'message' => 'Error en ejecución: ' . $error]));
}

// Cierre de conexiones
if ($stmt) $stmt->close();
$con->close();
?>