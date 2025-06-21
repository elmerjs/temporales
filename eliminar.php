<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminación Exitosa</title>
</head>
<body>
    <?php

require 'funciones.php';
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_solicitud = $_POST['id_solicitud'];
        $facultad_id = $_POST['facultad_id'];
        $departamento_id = $_POST['departamento_id'];
        $anio_semestre = $_POST['anio_semestre'];
        $tipo_docente = $_POST['tipo_docente'];

        $cierreperiodo = obtenerperiodo($anio_semestre);

        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        if ($conn->connect_error) {
            die("Conexión fallida: " . $conn->connect_error);
        }
if ($cierreperiodo=='1'){
         echo '<script type="text/javascript">';
        echo 'alert("El perido cerrado .\\n' . $anio_semestre .'");';
            echo '</script>';

            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
     echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
            echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
            echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
            echo "</form>";
            echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit; // Asegúrate de que el script no continúe ejecutándose
        
    }  else {//pendiente  para periodo cerrado 
        $sql = "DELETE FROM solicitudes WHERE id_solicitud = '$id_solicitud'";
        if ($conn->query($sql) === TRUE) {
            // Redireccionar a consulta_todo_depto.php con las variables en modo POST
            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
            echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
            echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
            echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
            echo "</form>";
            echo "<script>document.getElementById('redirectForm').submit();</script>";
        } else {
            echo "Error al eliminar el registro: " . $conn->error;
        }
        
        $conn->close();}
    }
    ?>
</body>
</html>
