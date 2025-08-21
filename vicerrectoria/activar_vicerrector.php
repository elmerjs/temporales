<?php
require_once '../conn.php';

if (!isset($_GET['id'])) {
    header("Location: listar_vicerrectores.php");
    exit();
}

$id = intval($_GET['id']);

// Primero desactivar todos
$conn->query("UPDATE vicerrectores SET activo = FALSE");

// Luego activar el seleccionado
$sql = "UPDATE vicerrectores SET activo = TRUE WHERE id = $id";

if ($conn->query($sql)) {
    header("Location: listar_vicerrectores.php?success=1");
} else {
    header("Location: listar_vicerrectores.php?error=1");
}

$conn->close();
?>