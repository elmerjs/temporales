<?php
session_start();
include "conn.php";  // Usar la conexión a la BD

// Verificar si hay un usuario en sesión
if (!isset($_SESSION['name'])) {
    exit(); // Si no hay sesión, no hacer nada
}

$usuario = $_SESSION['name'];  // Nombre del usuario en sesión

$sql = "INSERT INTO usuarios_conectados (usuario, ultima_actividad) 
        VALUES ('$usuario', NOW()) 
        ON DUPLICATE KEY UPDATE ultima_actividad = NOW()";

$conn->query($sql);

$conn->close();
?>
