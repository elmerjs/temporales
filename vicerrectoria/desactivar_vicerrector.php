<?php
require_once '../conn.php';

if (!isset($_GET['id'])) {
    header("Location: listar_vicerrectores.php");
    exit();
}

$id = intval($_GET['id']);

// Solo desactivar (no eliminar)
$sql = "UPDATE vicerrectores SET activo = FALSE WHERE id = $id";

if ($conn->query($sql)) {
    header("Location: listar_vicerrectores.php?success=1");
} else {
    header("Location: listar_vicerrectores.php?error=1");
}

$conn->close();
?>