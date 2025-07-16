<?php
session_start();
require 'cn.php'; // Asegúrate de que esta ruta sea correcta para tu archivo de conexión

header('Content-Type: application/json'); // Indica que la respuesta será en formato JSON

$response = ['success' => false, 'message' => ''];

// Verificar si el usuario está autenticado
if (!isset($_SESSION['loggedin']) || empty($_SESSION['id_user'])) {
    $response['message'] = 'Usuario no autenticado.';
    echo json_encode($response);
    exit();
}

$id_user = $_SESSION['id_user'];
$profesor_en_cargo = $_POST['profesor_en_cargo'] ?? '';
$email_personal = $_POST['email_personal'] ?? '';
$telefono_personal = $_POST['telefono_personal'] ?? '';

// Validar y limpiar los datos de entrada para prevenir inyecciones SQL
$profesor_en_cargo = $con->real_escape_string(trim($profesor_en_cargo));
$email_personal = $con->real_escape_string(trim($email_personal));
$telefono_personal = $con->real_escape_string(trim($telefono_personal));

// Preparar la consulta SQL para actualizar los datos
$stmt = $con->prepare("UPDATE users SET u_nombre_en_cargo = ?, u_email_en_cargo = ?, u_tel_en_cargo = ? WHERE Id = ?");

// Verificar si la preparación de la consulta fue exitosa
if ($stmt === false) {
    $response['message'] = 'Error en la preparación de la consulta: ' . $con->error;
    echo json_encode($response);
    exit();
}

// Vincular los parámetros a la consulta. "sssi" significa string, string, string, integer
$stmt->bind_param("sssi", $profesor_en_cargo, $email_personal, $telefono_personal, $id_user);

// Ejecutar la consulta
if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Perfil actualizado correctamente.';
} else {
    $response['message'] = 'Error al actualizar el perfil: ' . $stmt->error;
}

// Cerrar la declaración y la conexión a la base de datos
$stmt->close();
$con->close();

echo json_encode($response);
?>