<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminación Registro</title>
</head>
<body>
<?php
require 'funciones.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_solicitud      = $_POST['id_solicitud'];
    $facultad_id       = $_POST['facultad_id'];
    $departamento_id   = $_POST['departamento_id'];
    $anio_semestre     = $_POST['anio_semestre'];
    $anio_semestre_anterior     = $_POST['anio_semestre_anterior'];

    $tipo_docente      = $_POST['tipo_docente'];
    // Recoge el motivo, o cadena vacía si no viene
    $motivo = isset($_POST['motivo_eliminacion']) 
              ? trim($_POST['motivo_eliminacion']) 
              : '';
$tipoEliminacion = $_POST['tipo_eliminacion'] ?? '';

    $cierreperiodo = obtenerperiodo($anio_semestre);

    // Conexión
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Si el periodo está cerrado, muestro alerta y redirijo
    if ($cierreperiodo === '1') {
        echo '<script>alert("El periodo ' . addslashes($anio_semestre) . ' está cerrado.");</script>';
    } else {
     // Actualizo estado, s_observacion, tipo_reemplazo y novedad
            $stmt = $conn->prepare("
                UPDATE solicitudes 
                   SET estado = 'an',
                       s_observacion = ?,
                       tipo_reemplazo = ?,
                       novedad = 'eliminar'
                 WHERE id_solicitud = ?
            ");
            $stmt->bind_param("ssi", $motivo, $tipoEliminacion, $id_solicitud);

            if (!$stmt->execute()) {
                echo "Error al actualizar el registro: " . $stmt->error;
            }
            $stmt->close();
                }

    $conn->close();

    // Redirijo a depto_comparativo.php pasando las variables por POST
    echo "<form id='redirectForm' action='depto_comparativo.php' method='POST'>";
    echo "<input type='hidden' name='facultad_id'    value='" . htmlspecialchars($facultad_id,    ENT_QUOTES, 'UTF-8') . "'>";
    echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id,ENT_QUOTES, 'UTF-8') . "'>";
    echo "<input type='hidden' name='anio_semestre'   value='" . htmlspecialchars($anio_semestre,  ENT_QUOTES, 'UTF-8') . "'>";
        echo "<input type='hidden' name='anio_semestre_anterior'   value='" . htmlspecialchars($anio_semestre_anterior,  ENT_QUOTES, 'UTF-8') . "'>";

    echo "</form>";
    echo "<script>document.getElementById('redirectForm').submit();</script>";
    exit;
}
?>
</body>
</html>
