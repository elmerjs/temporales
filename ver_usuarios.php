<?php
include "conn.php";

$sql = "SELECT usuario, ultima_actividad FROM usuarios_conectados 
        WHERE ultima_actividad >= NOW() - INTERVAL 12 HOUR"; // Cambiamos de 5 MINUTE a 12 HOUR

$resultado = $conn->query($sql);

echo "<h3>Usuarios conectados en las últimas 12 horas:</h3><ul>";
while ($fila = $resultado->fetch_assoc()) {
    echo "<li>" . $fila["usuario"] . " (Última actividad: " . $fila["ultima_actividad"] . ")</li>";
}
echo "</ul>";

$conn->close();
?>