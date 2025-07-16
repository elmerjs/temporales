<?php
session_start();
require 'cn.php';

header('Content-Type: application/json');

// Verificar sesión y existencia de id_user
if (!isset($_SESSION['loggedin']) || empty($_SESSION['id_user'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Usuario no autenticado'
    ]);
    exit();
}

$id_user = $_SESSION['id_user']; // Usar directamente el ID de sesión

$stmt = $con->prepare("SELECT u_nombre_en_cargo, u_email_en_cargo, u_tel_en_cargo FROM users WHERE Id = ?");
if ($stmt === false) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error en preparación: ' . $con->error
    ]);
    exit();
}

$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'u_nombre_en_cargo' => $user_data['u_nombre_en_cargo'],
        'u_email_en_cargo' => $user_data['u_email_en_cargo'],
        'u_tel_en_cargo' => $user_data['u_tel_en_cargo']
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Usuario no encontrado'
    ]);
}

$stmt->close();
$con->close();
?>