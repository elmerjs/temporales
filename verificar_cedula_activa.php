<?php
// verificar_cedula_activa.php

header('Content-Type: application/json');

// --- PASO 1: Incluimos tu archivo de conexión ---
require_once('conn.php'); // ¡Perfecto! Esto crea la variable $conn por ti.

// --- Ya no necesitas definir las variables de conexión aquí ---

// Obtener los parámetros de la URL de forma segura
$cedula = isset($_GET['cedula']) ? $_GET['cedula'] : '';
$anio_semestre = isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : '';

// Si los parámetros están vacíos, no continuar
if (empty($cedula) || empty($anio_semestre)) {
    echo json_encode(['existe' => false]);
    exit;
}

// Preparar la consulta para evitar inyección SQL
$sql = "SELECT cedula FROM solicitudes WHERE anio_semestre = ? AND cedula = ? AND (estado <> 'an' OR estado IS NULL)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // "ss" significa que ambos parámetros son strings
    $stmt->bind_param("ss", $anio_semestre, $cedula);
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Almacenar el resultado
    $stmt->store_result();
    
    // Verificar si se encontró al menos una fila
    if ($stmt->num_rows > 0) {
        // La cédula ya existe y está activa
        echo json_encode(['existe' => true]);
    } else {
        // La cédula no existe o no está activa
        echo json_encode(['existe' => false]);
    }
    
    // Cerrar el statement
    $stmt->close();
} else {
    // Error en la preparación de la consulta
    echo json_encode(['error' => 'Error al preparar la consulta.']);
}

// Cerrar la conexión
$conn->close();
?>