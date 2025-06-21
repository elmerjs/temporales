<?php
require 'funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facultad_id = $_POST['facultad_id'];
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre = $_POST['anio_semestre'];
    $tipo_docente = $_POST['tipo_docente'];
    $tipo_usuario = $_POST['tipo_usuario'];

    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
        $cierreperiodo = obtenerperiodo($anio_semestre);
        $envio_fac = obtenerenviof($facultad_id,$anio_semestre);
        $dp_aceptacion_fac = obteneraceptacionfac($departamento_id,$anio_semestre);

    if ($cierreperiodo==='1'){
         echo '<script type="text/javascript">';
        echo 'alert("El perido cerrado ' . $anio_semestre .'");';
            echo '</script>';

            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
     echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
            echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
            echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
            echo "</form>";
            echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit; // Asegúrate de que el script no continúe ejecutándose
        
    }  elseif ($envio_fac==='1' && $tipo_usuario !='1'){
         echo '<script type="text/javascript">';
        echo 'alert("Proceso cerrado por facultad a VRA, solicitar apertura, o enviar novedad")';
        echo '</script>';

            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
     echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
            echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
            echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
            echo "</form>";
            echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit; // Asegúrate de que el script no continúe ejecutándose
        
    } 
    elseif ($dp_aceptacion_fac === 'aceptar' && $tipo_usuario !== '1') {
         echo '<script type="text/javascript">';
        echo 'alert("La solicitud ya fue aceptada por facultad a VRA, comunicarse con facultad, o enviar novedad una vez se autorice")';
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
    
    if ($tipo_docente === "Catedra") {
        $sql_update = "UPDATE depto_periodo 
                    SET dp_estado_catedra = NULL, dp_estado_total = NULL, dp_fecha_envio = '0000-00-00', dp_folios = NULL, fecha_oficio_depto = NULL
                    WHERE fk_depto_dp = '$departamento_id' 
                    AND periodo = '$anio_semestre'";
    } else {
        $sql_update = "UPDATE depto_periodo SET dp_estado_ocasional = NULL, dp_estado_total = NULL,dp_fecha_envio = '0000-00-00', dp_folios = NULL, fecha_oficio_depto = NULL WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
    }

    if ($conn->query($sql_update) === TRUE) {
        
        $sql_updatef = "update fac_periodo  set fac_periodo.fp_estado =0, fecha_accion = NOW()    WHERE fac_periodo.fp_periodo = '$anio_semestre' and fac_periodo.fp_fk_fac = '$facultad_id'";
    

        $conn->query($sql_updatef); // Ejecutar la consulta sin el if

        
        echo "Estado abierto exitosamente.";
    } else {
        echo "Error actualizando el estado: " . $conn->error;
    }
        
 
    
    $conn->close();
    
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
    exit();
}} else {
    echo "Método no permitido.";
}
?>
