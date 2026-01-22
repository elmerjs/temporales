<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_solicitud = $_POST['id_solicitud'];
    $facultad_id = $_POST['facultad_id'];
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre = $_POST['anio_semestre'];
    $anio_semestre_anterior = isset($_POST['anio_semestre_anterior']) ? $_POST['anio_semestre_anterior'] : '';

    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $tipo_dedicacion = isset($_POST['tipo_dedicacion']) ? $_POST['tipo_dedicacion'] : null;
    $tipo_dedicacion_r = isset($_POST['tipo_dedicacion_r']) ? $_POST['tipo_dedicacion_r'] : null;
    //$horas = isset($_POST['horas']) ? $_POST['horas'] : null;
    $horas = isset($_POST['horas']) && $_POST['horas'] !== '' ? (float)$_POST['horas'] : 0.0;
   // $horas_r = isset($_POST['horas_r']) ? $_POST['horas_r'] : null;
    $horas_r = isset($_POST['horas_r']) && $_POST['horas_r'] !== '' ? (float)$_POST['horas_r'] : 0.0;

    $tipo_docente = $_POST['tipo_docente'];

    // Determinar sede según tipo de docente y datos
    if ($tipo_docente == "Ocasional") {
        $sede = empty($tipo_dedicacion) ? "Regionalización" : "Popayán";
    } elseif ($tipo_docente == "Catedra") {
            if ($horas > 0 && $horas_r > 0) {
                $sede = "Popayán-Regionalización";
            } elseif ($horas > 0) {
                $sede = "Popayán";
            } elseif ($horas_r > 0) {
                $sede = "Regionalización";
            } else {
                $sede = "Popayán"; // o el valor por defecto que prefieras
            }
        }
    else {
        $sede = null;
    }

    $anexa_hv_docente_nuevo = $_POST['anexa_hv_docente_nuevo'];
    $actualiza_hv_antiguo = $_POST['actualiza_hv_antiguo'];
    $anexos = isset($_POST['link_anexos']) ? $_POST['link_anexos'] : null; // Nuevo campo anexos
    $s_observacion = isset($_POST['observacion']) ? $_POST['observacion'] : null;
    $tipo_reemplazo = isset($_POST['tipo_reemplazo']) ? $_POST['tipo_reemplazo'] : null;

    // Conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    
    $tieneObservacion = !empty(trim($s_observacion));
    if ($tieneObservacion) {
        // Actualizar con observación (admin)
        $sql = "UPDATE solicitudes SET 
                        cedula = ?, 
                        nombre = ?, 
                        tipo_dedicacion = ?, 
                        tipo_dedicacion_r = ?, 
                        horas = ?, 
                        horas_r = ?, 
                        sede = ?, 
                        anexa_hv_docente_nuevo = ?, 
                        actualiza_hv_antiguo = ?,
                        anexos = ?,                 
                        s_observacion = ?,
                        tipo_reemplazo = ?,
                        novedad = 'modificar'
                        WHERE id_solicitud = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssi", // 12 's' (para los campos) y 1 'i' (para id_solicitud)
            $cedula, 
            $nombre, 
            $tipo_dedicacion, 
            $tipo_dedicacion_r, 
            $horas, 
            $horas_r, 
            $sede, 
            $anexa_hv_docente_nuevo, 
            $actualiza_hv_antiguo,
            $anexos, // Nuevo parámetro
            $s_observacion,
            $tipo_reemplazo,
            $id_solicitud);
    } else {
        // Actualización normal (usuario)
        $sql = "UPDATE solicitudes SET 
                        cedula = ?, 
                        nombre = ?, 
                        tipo_dedicacion = ?, 
                        tipo_dedicacion_r = ?, 
                        horas = ?, 
                        horas_r = ?, 
                        sede = ?, 
                        anexa_hv_docente_nuevo = ?, 
                        actualiza_hv_antiguo = ?,
                        anexos = ?                  
                        WHERE id_solicitud = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssi", // 10 's' (para los campos) y 1 'i' (para id_solicitud)
            $cedula,
            $nombre,
            $tipo_dedicacion,
            $tipo_dedicacion_r,
            $horas,
            $horas_r,
            $sede,
            $anexa_hv_docente_nuevo,
            $actualiza_hv_antiguo,
            $anexos, // Nuevo parámetro
            $id_solicitud
        );
    }

    // Ejecutar y redirigir
   if ($stmt->execute()) {
        // Determinar a qué página redirigir
        $pagina_redireccion = $tieneObservacion ? 'depto_comparativo.php' : 'consulta_todo_depto.php';
        
        // Redirección mediante POST
        echo "<form id='redirectForm' action='".htmlspecialchars($pagina_redireccion)."' method='POST'>";
        echo "<input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>";
        echo "<input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>";
        echo "<input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>";
        echo "<input type='hidden' name='anio_semestre_anterior' value='".htmlspecialchars($anio_semestre_anterior)."'>";

        echo "</form>";
        echo "<script>document.getElementById('redirectForm').submit();</script>";
        exit();
    } else {
        echo "Error al actualizar el registro: " . $conn->error;
    }

    $stmt->close();
    $conn->close();    
}
?>