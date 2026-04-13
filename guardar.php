<?php
session_start(); // Iniciar la sesión al principio del archivo
require 'funciones.php';

/**
 * Parsea el string de títulos (separado por saltos de línea) y asigna cada título
 * a la categoría correspondiente: doctorado, maestría, especialización, pregrado,
 * y los no reconocidos a otro_estudio.
 *
 * @param string $titulosStr Texto con los títulos (usualmente de asp_titulos)
 * @return array Arreglo asociativo con las claves: pregrado, especializacion, maestria, doctorado, otro_estudio
 */
function parseTitulosPHP($titulosStr) {
    $parsed = [
        'pregrado' => '',
        'especializacion' => '',
        'maestria' => '',
        'doctorado' => '',
        'otro_estudio' => ''
    ];

    if (empty($titulosStr)) {
        return $parsed;
    }

    // Definir palabras clave para cada tipo de estudio (en mayúsculas para comparar)
    $keywords = [
        'doctorado' => ['DOCTORADO EN', 'DOCTOR', 'DOCTORA', 'PH.D.', 'PHD'],
        'maestria' => [
            'MAESTRIA EN', 'MAESTRÍA EN', 'MAGISTER EN', 'MASTER EN',
            'MAGISTER', 'MAESTRO', 'MASTER', 'MAGÍSTER', 'MAESTRÍA', 'MAESTRA', 'MÁSTER'
        ],
        'especializacion' => ['ESPECIALIZACION EN', 'ESPECIALIZACIÓN EN', 'ESP.', 'ESPECIALISTA'],
        'pregrado' => [
            'LICENCIADO EN', 'LICENCIADA EN', 'LICENCIATURA EN',
            'PROFESIONAL EN', 'INGENIERO EN', 'INGENIERA EN',
            'ABOGADO', 'ABOGADA', 'ADMINISTRADOR DE', 'ADMINISTRADORA DE',
            'BIOLOGO', 'BIOLOGA', 'QUIMICO', 'QUÍMICO', 'CIRUJANO', 'ANTROPOLOGO',
            'ENFERMERO', 'ENFERMERA', 'TECNICO EN', 'TÉCNICO EN', 'TECNOLOGO EN', 'TECNÓLOGO EN',
            'MEDICO', 'MÉDICO', 'MATEMATICO', 'MATEMÁTICO', 'CONTADOR', 'ECONOMISTA',
            'BACHILLER', 'NORMALISTA', 'ARQUITECTO', 'ARQUITECTA', 'FILOSOFO', 'FILOSOFA',
            'PSICOLOGO', 'PSICOLOGA', 'CITOHISTOTECNOLOGO', 'BACTERIOLOGO', 'BACTERIOLOGA',
            'LABORATORISTA', 'GEOTECNOLOGO', 'GEOTECNOLOGA', 'GEOGRAFO', 'GEOGRAFA',
            'ODONTOLOGO', 'ODONTOLOGA', 'NUTRICIONISTA', 'FISIOTERAPEUTA',
            'COMUNICADOR', 'PERIODISTA', 'DISEÑADOR', 'SOCIOLOGO', 'HISTORIADOR',
            'POLITOLOGO', 'QUÍMICO FARMACÉUTICO', 'ZOOTECNISTA', 'AGRONOMO',
            'INGENIERO', 'INGENIERA',
            'LICENCIADO', 'LICENCIADA', 'LICENCIATURA',
            'TECNICO', 'TÉCNICO', 'TECNOLOGO', 'TECNÓLOGO',
            'ADMINISTRADOR', 'ADMINISTRADORA',
            'BACHILLER', 'NORMALISTA',
            'MÚSICA', 'ARTE', 'GUIA'
        ]
    ];

    // Dividir por saltos de línea
    $lines = preg_split('/[\r\n]+/', $titulosStr);
    $unmatched = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') continue;

        $upperTrimmed = mb_strtoupper($trimmed);

        // 1. Doctorado (máxima prioridad)
        if (empty($parsed['doctorado'])) {
            foreach ($keywords['doctorado'] as $kw) {
                if (strpos($upperTrimmed, $kw) === 0) {
                    $parsed['doctorado'] = $trimmed;
                    break 2; // Salir del foreach y continuar con la siguiente línea
                }
            }
        }

        // 2. Maestría
        if (empty($parsed['maestria'])) {
            foreach ($keywords['maestria'] as $kw) {
                if (strpos($upperTrimmed, $kw) === 0) {
                    $parsed['maestria'] = $trimmed;
                    break 2;
                }
            }
        }

        // 3. Especialización
        if (empty($parsed['especializacion'])) {
            foreach ($keywords['especializacion'] as $kw) {
                if (strpos($upperTrimmed, $kw) === 0) {
                    $parsed['especializacion'] = $trimmed;
                    break 2;
                }
            }
        }

        // 4. Pregrado
        if (empty($parsed['pregrado'])) {
            foreach ($keywords['pregrado'] as $kw) {
                if (strpos($upperTrimmed, $kw) === 0) {
                    $parsed['pregrado'] = $trimmed;
                    break 2;
                }
            }
        }

        // Si no encaja en ninguna categoría, se acumula para otro_estudio
        $unmatched[] = $trimmed;
    }

    if (!empty($unmatched)) {
        $parsed['otro_estudio'] = implode("\n", $unmatched);
    }

    return $parsed;
}

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
        exit;
    }

    $cierreperiodo = obtenerperiodo($anio_semestre);
    $envio_fac = obtenerenviof($facultad_id, $anio_semestre);

    if ($tipo_usuario == 3 && $depto_user != $departamento_id && !in_array('222', $cedulas)) {
        echo '<script type="text/javascript">';
        echo 'alert("El departamento no corresponde al usuario.\\nAño semestre del usuario: ' . $anio_semestre . '\\nDepartamento seleccionado: ' . $departamento_id . '");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit;
    } elseif ($cierreperiodo == '1') {
        echo '<script type="text/javascript">';
        echo 'alert("El periodo está cerrado.\\n' . $anio_semestre . '");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit;
    } elseif ($envio_fac === '1') {
        echo '<script type="text/javascript">';
        echo 'alert("Informe de facultad enviado a VRA\\nNo se pueden hacer más cargues.");';
        echo 'window.location.href = "indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '";';
        echo '</script>';
        exit;
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
            $_SESSION['facultad_id'] = $facultad_id;
            $_SESSION['departamento_id'] = $departamento_id;
            $_SESSION['anio_semestre'] = $anio_semestre;
            $_SESSION['tipo_docente'] = $tipo_docente;
        } else {
            // Verificar si las cédulas ya existen en la base de datos
            $cedulas_existentes = cedulasExistentesall($conn, $anio_semestre, $departamento_id, $cedulas);

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
                exit;
            }

            $cedulas_faltantes = validarCedulasEnPeriodo($cedulas, $anio_semestre);

            if (!empty($cedulas_faltantes)) {
                $mensaje = "Las siguientes cédulas no registran en la base de aspirantes para este periodo $anio_semestre:\\n";
                foreach ($cedulas_faltantes as $cedula => $nombre) {
                    $mensaje .= "Cédula: $cedula - $nombre\\n";
                }
                echo "<script>alert('$mensaje');</script>";
            }

            // Filtrar las cédulas que sí están en la base de aspirantes
            $cedulas_validas = array_diff($cedulas, array_keys($cedulas_faltantes));

            // Insertar los datos de las cédulas válidas
            foreach ($cedulas_validas as $cedula) {
                $index = array_search($cedula, $cedulas);
                $nombre = $_POST['nombre'][$index];
                $tipo_dedicacion = isset($_POST['tipo_dedicacion'][$index]) ? $_POST['tipo_dedicacion'][$index] : null;
                $tipo_dedicacion_r = isset($_POST['tipo_dedicacion_r'][$index]) ? $_POST['tipo_dedicacion_r'][$index] : null;
                $horas_r = isset($_POST['horas_r'][$index]) ? (float)$_POST['horas_r'][$index] : 0;
                $horas = isset($_POST['horas'][$index]) ? (float)$_POST['horas'][$index] : 0;
              
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

                // --- Obtener datos de estudios desde aspirante ---
                $pregrado = '';
                $especializacion = '';
                $maestria = '';
                $doctorado = '';
                $otro_estudio = '';

                $anio = substr($anio_semestre, 0, 4); // Extrae "2026" de "2026-2"
                $sql_asp = "SELECT asp_titulos FROM aspirante 
                               WHERE fk_asp_doc_tercero = '$cedula' 
                                 AND fk_asp_periodo LIKE '$anio%' 
                               ORDER BY fk_asp_periodo DESC 
                               LIMIT 1";
                $result_asp = $conn->query($sql_asp);
                if ($result_asp && $result_asp->num_rows > 0) {
                    $row_asp = $result_asp->fetch_assoc();
                    $titulos_raw = $row_asp['asp_titulos'];
                    if (!empty($titulos_raw)) {
                        $estudios = parseTitulosPHP($titulos_raw);
                        $pregrado = $estudios['pregrado'];
                        $especializacion = $estudios['especializacion'];
                        $maestria = $estudios['maestria'];
                        $doctorado = $estudios['doctorado'];
                        $otro_estudio = $estudios['otro_estudio'];
                    }
                }

                // Escapar valores para evitar errores de sintaxis SQL (inyección básica)
                $cedula_esc = $conn->real_escape_string($cedula);
                $nombre_esc = $conn->real_escape_string($nombre);
                $tipo_dedicacion_esc = $conn->real_escape_string($tipo_dedicacion);
                $tipo_dedicacion_r_esc = $conn->real_escape_string($tipo_dedicacion_r);
                $sede_esc = $conn->real_escape_string($sede);
                $anexa_hv_esc = $conn->real_escape_string($anexa_hv_docente_nuevo);
                $actualiza_hv_esc = $conn->real_escape_string($actualiza_hv_antiguo);
                $pregrado_esc = $conn->real_escape_string($pregrado);
                $especializacion_esc = $conn->real_escape_string($especializacion);
                $maestria_esc = $conn->real_escape_string($maestria);
                $doctorado_esc = $conn->real_escape_string($doctorado);
                $otro_estudio_esc = $conn->real_escape_string($otro_estudio);

                $sql = "INSERT INTO solicitudes 
                        (anio_semestre, facultad_id, departamento_id, tipo_docente, cedula, nombre, 
                         tipo_dedicacion, tipo_dedicacion_r, horas, horas_r, sede, 
                         anexa_hv_docente_nuevo, actualiza_hv_antiguo,
                         pregrado, especializacion, maestria, doctorado, otro_estudio) 
                        VALUES (
                            '$anio_semestre', '$facultad_id', '$departamento_id', '$tipo_docente', 
                            '$cedula_esc', '$nombre_esc', 
                            '$tipo_dedicacion_esc', '$tipo_dedicacion_r_esc', '$horas', '$horas_r', '$sede_esc', 
                            '$anexa_hv_esc', '$actualiza_hv_esc',
                            '$pregrado_esc', '$especializacion_esc', '$maestria_esc', '$doctorado_esc', '$otro_estudio_esc'
                        )";

                if ($conn->query($sql) !== TRUE) {
                    echo "Error al insertar solicitud: " . $conn->error;
                }
            }

            $_SESSION['facultad_id'] = $facultad_id;
            $_SESSION['departamento_id'] = $departamento_id;
            $_SESSION['anio_semestre'] = $anio_semestre;
            $_SESSION['tipo_docente'] = $tipo_docente;

            $conn->close();

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