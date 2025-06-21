<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminación de Registros</title>
</head>
<body>

<?php
require 'funciones.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener las variables desde POST
    $facultad_id = htmlspecialchars($_POST['facultad_id']);
    $departamento_id = htmlspecialchars($_POST['departamento_id']);
    $anio_semestre = htmlspecialchars($_POST['anio_semestre']);
    $tipo_docente = htmlspecialchars($_POST['tipo_docente']);

    // Verificar si el período está cerrado
    $cierreperiodo = obtenerperiodo($anio_semestre);

    if ($cierreperiodo == '1') {
        echo '<script>alert("El período está cerrado. No se pueden eliminar registros.");</script>';
        echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
        echo "<input type='hidden' name='facultad_id' value='$facultad_id'>";
        echo "<input type='hidden' name='departamento_id' value='$departamento_id'>";
        echo "<input type='hidden' name='anio_semestre' value='$anio_semestre'>";
        echo "</form>";
        echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit;
    } 

    // Conectar a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');

    // Verificar conexión
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Preparar la consulta SQL para eliminar registros según los criterios
    $sql = "DELETE FROM solicitudes 
            WHERE anio_semestre = ? 
            AND departamento_id = ? 
            AND tipo_docente = ?";

    // Usar prepared statements para mayor seguridad
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sis", $anio_semestre, $departamento_id, $tipo_docente);
        
        if ($stmt->execute()) {
            echo '<script>alert("Registros eliminados correctamente.");</script>';
        } else {
            echo '<script>alert("Error al eliminar registros: ' . $conn->error . '");</script>';
        }

        $stmt->close();
    } else {
        echo '<script>alert("Error en la preparación de la consulta: ' . $conn->error . '");</script>';
    }

    // Cerrar la conexión
    $conn->close();

    // Redireccionar después de la eliminación
    echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
    echo "<input type='hidden' name='facultad_id' value='$facultad_id'>";
    echo "<input type='hidden' name='departamento_id' value='$departamento_id'>";
    echo "<input type='hidden' name='anio_semestre' value='$anio_semestre'>";
    echo "</form>";
    echo "<script>document.getElementById('redirectForm').submit();</script>";
}
?>

</body>
</html>
