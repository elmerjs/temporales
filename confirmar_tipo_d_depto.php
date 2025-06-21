<?php
require 'cn.php';
require 'funciones.php';
// Obtener los parámetros de la URL
$facultad_id = $_GET['facultad_id'];
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$tipo_docente = $_GET['tipo_docente'];
        $verificaVisados = verificaVisados($anio_semestre, $departamento_id, $tipo_docente);
        $cierreperiodo = obtenerperiodo($anio_semestre);


    if ($verificaVisados<>'1'){
         echo '<script type="text/javascript">';
        echo 'alert("dar visto bueno (visado) a todos los profesores antes de confirmar");';
            echo '</script>';

            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
     echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
            echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
            echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
            echo "</form>";
            echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit; // Asegúrate de que el script no continúe ejecutándose
        
    } 

  if ($cierreperiodo==='1'){
         echo '<script type="text/javascript">';
        echo 'alert("Perido cerrado ' . $anio_semestre .', consultar a la V.R.A. ");';
            echo '</script>';

            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
     echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
            echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
            echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
            echo "</form>";
            echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit; // Asegúrate de que el script no continúe ejecutándose
        
    } 



else { 


// Realizar el INSERT o UPDATE en la tabla depto_periodo
$sql_check = "SELECT id_depto_periodo FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
$result_check = $con->query($sql_check);

if ($result_check->num_rows > 0) {
    // Si existe, hacer un UPDATE
    if ($tipo_docente === "Catedra") {
        $sql_update = "UPDATE depto_periodo SET dp_estado_catedra = 'ce' WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
    } else {
        $sql_update = "UPDATE depto_periodo SET dp_estado_ocasional = 'ce' WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
    }
    $con->query($sql_update);
} else {
    // Si no existe, hacer un INSERT
    if ($tipo_docente === "Catedra") {
        $sql_insert = "INSERT INTO depto_periodo (fk_depto_dp, periodo, dp_estado_catedra) VALUES ('$departamento_id', '$anio_semestre', 'ce')";
    } else {
        $sql_insert = "INSERT INTO depto_periodo (fk_depto_dp, periodo, dp_estado_ocasional) VALUES ('$departamento_id', '$anio_semestre', 'ce')";
    }
    $con->query($sql_insert);
}

// Consulta SQL
$consultat = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

$resultadot = $con->query($consultat);

$con->close();

// Redirección usando un formulario POST oculto
echo '<form id="redirectForm" method="post" action="consulta_todo_depto.php">
    <input type="hidden" name="facultad_id" value="' . htmlspecialchars($facultad_id) . '">
    <input type="hidden" name="departamento_id" value="' . htmlspecialchars($departamento_id) . '">
    <input type="hidden" name="anio_semestre" value="' . htmlspecialchars($anio_semestre) . '">
    <input type="hidden" name="tipo_docente" value="' . htmlspecialchars($tipo_docente) . '">
</form>
<script type="text/javascript">
    document.getElementById("redirectForm").submit();
</script>';

exit; // Terminar el script para evitar cualquier salida adicional
    }
?>
