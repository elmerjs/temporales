<?php
session_start(); // Iniciar la sesión al principio del archivo
require 'funciones.php';

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Establecer conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }
    
    // Obtener los datos del formulario
    $anio_semestre = $_POST['anio_semestre'];
    $facultad_id = $_POST['facultad'];
    $departamento_id = $_POST['departamento'];
    $tipo_docente = $_POST['tipo_docente'];
    $num_docentes = $_POST['num_docentes'];
    $depto_user  = $_POST['depto_user'];
    $tipo_usuario  = $_POST['tipo_usuario'];
    $cedulas = $_POST['cedula'];

    // Verificar si las cédulas no están vacías
    if (empty($cedulas) || !is_array($cedulas) || count($cedulas) === 0) {
        echo '<script type="text/javascript">';
        echo 'alert("No hay registros para guardar.");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit; // Asegúrate de que el script no continúe ejecutándose
    }

    $cierreperiodo = obtenerperiodo($anio_semestre);
    $envio_fac = obtenerenviof($facultad_id, $anio_semestre);

    if ($tipo_usuario == 3 && $depto_user != $departamento_id && !in_array('222', $cedulas)) {
        echo '<script type="text/javascript">';
        echo 'alert("El departamento no corresponde al usuario.\\nAño semestre del usuario: ' . $anio_semestre . '\\nDepartamento seleccionado: ' . $departamento_id . '");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit; // Asegúrate de que el script no continúe ejecutándose
    } elseif ($cierreperiodo == '1') {
        echo '<script type="text/javascript">';
        echo 'alert("El periodo está cerrado.\\n' . $anio_semestre . '");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit; // Asegúrate de que el script no continúe ejecutándose
    } elseif ($envio_fac === '1') {
        echo '<script type="text/javascript">';
        echo 'alert("Informe de facultad enviado a VRA\\nNo se pueden hacer más cargues.");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit; // Asegúrate de que el script no continúe ejecutándose
    } else {
        require 'cn.php';

        $consultadepce = "SELECT * FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
        $resultadodepce = $con->query($consultadepce);
        if ($resultadodepce->num_rows > 0) {
            while ($rowdc = $resultadodepce->fetch_assoc()) {
                $dp_estado_catedra = $rowdc['dp_estado_catedra'];
                $dp_estado_ocasional = $rowdc['dp_estado_ocasional'];
                $dp_estado_total = $rowdc['dp_estado_total'];
            }
        } else {
            $dp_estado_catedra = null;
            $dp_estado_ocasional = null;
            $dp_estado_total = null;
        }

        if (($dp_estado_catedra == "ce" && $tipo_docente == "Catedra") || ($dp_estado_ocasional == "ce" && $tipo_docente == "Ocasional")) {
            echo "<script>
                alert('¡Departamento cerrado para el tipo de docente!');
                if (confirm('Libere cierre para modificar')) {
                    window.location.href = 'indexsolicitud.php?tipo_docente=" . urlencode($tipo_docente) . "&anio_semestre=" . urlencode($anio_semestre) . "';
                }
            </script>";
            // Guardar datos en la sesión
            $_SESSION['facultad_id'] = $facultad_id;
            $_SESSION['departamento_id'] = $departamento_id;
            $_SESSION['anio_semestre'] = $anio_semestre;
            $_SESSION['tipo_docente'] = $tipo_docente;
        } elseif ($dp_estado_total == '1') {
            echo "<script>
                alert('¡Departamento cerrado para docentes ocasionales y cátedra!');
                if (confirm('Libere cierre para modificar')) {
                    window.location.href = 'indexsolicitud.php?tipo_docente=" . urlencode($tipo_docente) . "&anio_semestre=" . urlencode($anio_semestre) . "';
                }
            </script>";

            // Guardar datos en la sesión
            $_SESSION['facultad_id'] = $facultad_id;
            $_SESSION['departamento_id'] = $departamento_id;
            $_SESSION['anio_semestre'] = $anio_semestre;
            $_SESSION['tipo_docente'] = $tipo_docente;
        } else {
            // Verificar si las cédulas ya existen en la base de datos
            $cedulas_existentes = cedulasExistentesall($conn, $anio_semestre, $departamento_id, $cedulas); // cambiamos cedulasExistentes x cedulasExistentesall    

            if (!empty($cedulas_existentes)) {
                $cedulas_existentes_msgs = [];
                foreach ($cedulas_existentes as $existente) {
                    if ($existente['departamento_nombre'] == $departamento_id) {
                        $cedulas_existentes_msgs[] = $existente['cedula'] . " (mismo departamento)";
                    } else {
                        $cedulas_existentes_msgs[] = $existente['cedula'] . " (departamento " . $existente['departamento_nombre'] . ")";
                    }
                }
                $cedulas_existentes_str = implode(',\n ', $cedulas_existentes_msgs);
                echo "<script>
                    alert('Las siguientes cédulas ya están registradas para este periodo: $cedulas_existentes_str');
                    window.location.href = 'indexsolicitud.php?tipo_docente=" . urlencode($tipo_docente) . "&anio_semestre=" . urlencode($anio_semestre) . "';
                </script>";

                exit; // Asegúrate de que el script no continúe ejecutándose
            }
            //si no estan en la bd  aspirantes::

            $cedulas_faltantes = validarCedulasEnPeriodo($cedulas, $anio_semestre);

            if (!empty($cedulas_faltantes)) {
                $mensaje = "Las siguientes cédulas no registran en la base de aspirantes para este periodo $anio_semestre:\\n";

                // Generar el mensaje con cédula y nombre
                foreach ($cedulas_faltantes as $cedula => $nombre) {
                    $mensaje .= "Cédula: $cedula - $nombre\\n";
                }

                echo "<script>
                    alert('$mensaje');
                </script>";
                // No detenemos la ejecución aquí, continuamos con el resto de las cédulas
            }

            // Filtrar las cédulas que sí están en la base de aspirantes
 $cedulas_validas = array_diff($cedulas, array_keys($cedulas_faltantes));

            // Insertar los datos de las cédulas válidas
            foreach ($cedulas_validas as $cedula) {
                $index = array_search($cedula, $cedulas); // Encontrar el índice original de la cédula
                $nombre = $_POST['nombre'][$index];
                $tipo_dedicacion = isset($_POST['tipo_dedicacion'][$index]) ? $_POST['tipo_dedicacion'][$index] : null;
                $tipo_dedicacion_r = isset($_POST['tipo_dedicacion_r'][$index]) ? $_POST['tipo_dedicacion_r'][$index] : null;
                $horas_r = isset($_POST['horas_r'][$index]) ? (int)$_POST['horas_r'][$index] : 0; // Conversión a entero
                $horas = isset($_POST['horas'][$index]) ? (int)$_POST['horas'][$index] : 0;       // Conversión a entero

                // Validación: suma de horas no puede ser mayor a 12
                if (($horas + $horas_r) > 12) {
                    echo "<script>
                        alert('El total de horas no puede ser mayor a 12 para el docente con cédula: $cedula');
                        window.location.href = 'indexsolicitud.php?tipo_docente=" . urlencode($tipo_docente) . "&anio_semestre=" . urlencode($anio_semestre) . "';
                    </script>";
                    exit;
                }
                if ($tipo_docente == "Ocasional") {
                    $sede = empty($tipo_dedicacion) ? "Regionalización" : "Popayán";
                } elseif ($tipo_docente == "Catedra") {
                    if (!empty($horas) && !empty($horas_r)) {
                        $sede = "Popayán-Regionalización";
                    } elseif (!empty($horas)) {
                        $sede = "Popayán";
                    } else {
                        $sede = "Regionalización";
                    }
                } else {
                    $sede = null;
                }
                $anexa_hv_docente_nuevo = $_POST['anexa_hv_docente_nuevo'][$index];
                $actualiza_hv_antiguo = $_POST['actualiza_hv_antiguo'][$index];

                $sql = "INSERT INTO solicitudes (anio_semestre, facultad_id, departamento_id, tipo_docente, cedula, nombre, tipo_dedicacion, tipo_dedicacion_r, horas, horas_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo) 
                        VALUES ('$anio_semestre', '$facultad_id', '$departamento_id', '$tipo_docente', '$cedula', '$nombre', '$tipo_dedicacion', '$tipo_dedicacion_r', '$horas', '$horas_r', '$sede', '$anexa_hv_docente_nuevo', '$actualiza_hv_antiguo')";
                if ($conn->query($sql) !== TRUE) {
                    echo "Error al insertar solicitud: " . $conn->error;
                }
            }

            // Guardar datos en la sesión
            $_SESSION['facultad_id'] = $facultad_id;
            $_SESSION['departamento_id'] = $departamento_id;
            $_SESSION['anio_semestre'] = $anio_semestre;
            $_SESSION['tipo_docente'] = $tipo_docente;

            // Cerrar conexión
            $conn->close();

            // Redirigir a consulta.php
            // Antes de redirigir, muestra un formulario oculto
            echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>
                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                <noscript>
                    <p>Para completar la redirección, por favor, habilite JavaScript en su navegador.</p>
                </noscript>
            </form>";

            echo "<script>
                document.getElementById('redirectForm').submit();
            </script>";
            exit;
        }
    }
}
?>