<?php
// Incluye el archivo de funciones, que debería contener 'obtenerperiodo'
require 'funciones.php'; // Asegúrate de que este archivo exista y contenga getTipoReemplazoPorMotivo

// Asegúrate de que 'conn.php' esté en la ruta correcta y establezca la conexión $conn
// Es mejor incluirlo aquí para asegurar que la conexión esté disponible.
require_once 'conn.php';

// Función para determinar el tipo de reemplazo basado en el motivo
// Esta función es un ejemplo; ajusta las palabras clave según tus necesidades.
if (!function_exists('getTipoReemplazoPorMotivo')) {
    function getTipoReemplazoPorMotivo($motivo) {
        $motivo_lower = mb_strtolower(trim($motivo), 'UTF-8'); 
                   if (
                strpos($motivo_lower, 'fallecimiento') !== false ||
                strpos($motivo_lower, 'murio') !== false ||
                strpos($motivo_lower, 'falleció') !== false ||
                strpos($motivo_lower, 'fallece') !== false ||
                strpos($motivo_lower, 'deceso') !== false ||
                strpos($motivo_lower, 'muerte') !== false
            ) {
                return 'Fallecimiento';
            }
        
       elseif (strpos($motivo_lower, 'renuncia') !== false 
    || strpos($motivo_lower, 'voluntario') !== false 
    || strpos($motivo_lower, 'voluntaria') !== false) {
    return 'Renuncia';
}
        
        elseif (strpos($motivo_lower, 'ajuste de matriculas') !== false || strpos($motivo_lower, 'ajuste matricula') !== false) {
            return 'Ajuste de Matrículas';
        } 
        
       elseif (
                strpos($motivo_lower, 'no legalizo') !== false ||
                strpos($motivo_lower, 'no se legalizo') !== false ||
                strpos($motivo_lower, 'no legalizó') !== false ||
                strpos($motivo_lower, 'no se legalizó') !== false ||
                strpos($motivo_lower, 'sin legalizar') !== false ||
                strpos($motivo_lower, 'no legalización') !== false ||
                strpos($motivo_lower, 'no legalizacion') !== false ||
                strpos($motivo_lower, 'falta de legalización') !== false ||
                strpos($motivo_lower, 'falta de legalizacion') !== false ||
                strpos($motivo_lower, 'no legaliza') !== false
            ) {
                return 'No legalizó';
            }

        
        elseif (strpos($motivo_lower, 'reemplazo') !== false || strpos($motivo_lower, 'nn') !== false) {
            return 'Reemplazos NN';
        }
        return 'Otro'; // Valor por defecto
    }
}

// Función para normalizar valores vacíos (asegura NULL para cadenas vacías o solo espacios)
if (!function_exists('normalize_empty_values')) {
    function normalize_empty_values($value) {
        $trimmed_value = trim(strval($value));
        return empty($trimmed_value) ? null : $trimmed_value;
    }
}


// Verifica si la solicitud es de tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recopilar los datos enviados desde el formulario/modal
    // 'id_solicitud' aquí es el ID del registro ORIGINAL de la tabla 'solicitudes'
    $id_solicitud_original = $_POST['id_solicitud'] ?? null;
    $facultad_id = $_POST['facultad_id'] ?? null;
    $departamento_id = $_POST['departamento_id'] ?? null;
    $anio_semestre = $_POST['anio_semestre'] ?? null;
    $motivo_eliminacion = normalize_empty_values($_POST['motivo_eliminacion'] ?? ''); // APLICADO normalize_empty_values

    // Validar que los parámetros esenciales estén presentes
    if ($id_solicitud_original === null || $facultad_id === null || $departamento_id === null || $anio_semestre === null) {
        die("Error: Parámetros esenciales no proporcionados para la eliminación.");
    }

    // Obtener el tipo de reemplazo basado en el motivo
    $tipo_reemplazo_auto = getTipoReemplazoPorMotivo($motivo_eliminacion);

    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // --- PASO 1: Obtener los datos del registro original de la tabla 'solicitudes' ---
    // Necesitamos estos datos para copiar el registro completo a solicitudes_working_copy
    $sql_fetch_original = "
        SELECT * FROM `solicitudes` 
        WHERE `id_solicitud` = ? 
        AND `anio_semestre` = ? 
        AND `facultad_id` = ? 
        AND `departamento_id` = ?";
    
    $stmt_fetch = $conn->prepare($sql_fetch_original);
    if ($stmt_fetch === false) {
        die("Error al preparar la consulta de selección del original: " . $conn->error);
    }
    $stmt_fetch->bind_param("isii", $id_solicitud_original, $anio_semestre, $facultad_id, $departamento_id);
    $stmt_fetch->execute();
    $result_original = $stmt_fetch->get_result();

    if ($result_original->num_rows === 0) {
        die("Error: No se encontró el registro original en la tabla 'solicitudes' para eliminar. id_solicitud: $id_solicitud_original, anio_semestre: $anio_semestre, facultad_id: $facultad_id, departamento_id: $departamento_id");
    }
    $original_data = $result_original->fetch_assoc();
    $stmt_fetch->close();

    // --- PASO 2: Insertar el nuevo registro de "eliminación" en solicitudes_working_copy ---
    $working_table_name = "solicitudes_working_copy";

    // Preparar la consulta INSERT para la novedad de eliminación
    // Se copian la mayoría de los campos del registro original, y se establecen los de novedad.
    $sql_insert_novedad = "
        INSERT INTO `$working_table_name` (
            `fk_id_solicitud_original`, `anio_semestre`, `facultad_id`, `departamento_id`,
            `tipo_docente`, `cedula`, `nombre`, `tipo_dedicacion`, `tipo_dedicacion_r`,
            `horas`, `horas_r`, `sede`, `anexa_hv_docente_nuevo`, `actualiza_hv_antiguo`,
            `visado`, `estado`, `novedad`, `puntos`, `s_observacion`, `tipo_reemplazo`,
            `costo`, `anexos`, `pregrado`, `especializacion`, `maestria`, `doctorado`,
            `otro_estudio`, `experiencia_docente`, `experiencia_profesional`, `otra_experiencia`,
            `estado_depto`, `oficio_depto`, `fecha_envio_depto`, `aprobador_depto_id`,
            `estado_facultad`, `observacion_facultad`, `fecha_aprobacion_facultad`, `aprobador_facultad_id`,
            `estado_vra`, `observacion_vra`, `fecha_aprobacion_vra`, `aprobador_vra_id`
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, 'Eliminar', ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            'PENDIENTE', NULL, NOW(), ?, 
            'PENDIENTE', NULL, NULL, NULL,
            'PENDIENTE', NULL, NULL, NULL
        )";

    $stmt_insert = $conn->prepare($sql_insert_novedad);

    if ($stmt_insert === false) {
        die("Error al preparar la consulta de inserción de novedad: " . $conn->error);
    }

    // Asegúrate de que $aprobador_depto_id esté definido (por ejemplo, desde la sesión del usuario del departamento)
    // Para este ejemplo, se usa un valor de sesión o un placeholder 1. AJUSTA ESTO A TU LÓGICA REAL DE AUTENTICACIÓN.
    $aprobador_depto_id = $_SESSION['user_id_depto'] ?? 1; 

    // Prepara los valores para bind_param, normalizando los que puedan ser NULL/vacío
    $bind_fk_id_solicitud_original = $original_data['id_solicitud'];
    $bind_anio_semestre = $original_data['anio_semestre'];
    $bind_facultad_id = $original_data['facultad_id'];
    $bind_departamento_id = $original_data['departamento_id'];
    $bind_tipo_docente = $original_data['tipo_docente'];
    $bind_cedula = $original_data['cedula'];
    $bind_nombre = $original_data['nombre'];
    $bind_tipo_dedicacion = normalize_empty_values($original_data['tipo_dedicacion']); // Normalizado
    $bind_tipo_dedicacion_r = normalize_empty_values($original_data['tipo_dedicacion_r']); // Normalizado
    $bind_horas = $original_data['horas'];
    $bind_horas_r = $original_data['horas_r'];
    $bind_sede = $original_data['sede'];
    $bind_anexa_hv_docente_nuevo = $original_data['anexa_hv_docente_nuevo'];
    $bind_actualiza_hv_antiguo = $original_data['actualiza_hv_antiguo'];
    $bind_visado = $original_data['visado'];
    $bind_estado = $original_data['estado'];
    $bind_puntos = normalize_empty_values($original_data['puntos']); // Normalizado
    $bind_s_observacion = $motivo_eliminacion; 
    $bind_tipo_reemplazo = $tipo_reemplazo_auto; 
    $bind_costo = normalize_empty_values($original_data['costo']); // Normalizado
    $bind_anexos = $original_data['anexos'];
    $bind_pregrado = $original_data['pregrado'];
    $bind_especializacion = $original_data['especializacion'];
    $bind_maestria = $original_data['maestria'];
    $bind_doctorado = $original_data['doctorado'];
    $bind_otro_estudio = $original_data['otro_estudio'];
    $bind_experiencia_docente = $original_data['experiencia_docente'];
    $bind_experiencia_profesional = $original_data['experiencia_profesional'];
    $bind_otra_experiencia = $original_data['otra_experiencia'];
    $bind_aprobador_depto_id = $aprobador_depto_id;

    // Cadena de tipos corregida.
    // Se ha eliminado la 's' correspondiente a 'novedad' que ahora es un literal en el SQL.
    // La cadena de tipos original era: isississddsssiisdsdsisssssssssi (31 caracteres)
    // La nueva cadena de tipos es: isississddsssiidsdsdsisssssssssi (29 caracteres)
    $types = "isiisssssddsssisdssdsssssssssi"; // Esta cadena tiene 30 caracteres.
 
    // Collect all bind parameters into an array
    $bind_params_array = [
        $bind_fk_id_solicitud_original,
        $bind_anio_semestre,
        $bind_facultad_id,
        $bind_departamento_id,
        $bind_tipo_docente,
        $bind_cedula,
        $bind_nombre,
        $bind_tipo_dedicacion,
        $bind_tipo_dedicacion_r,
        $bind_horas,
        $bind_horas_r,
        $bind_sede,
        $bind_anexa_hv_docente_nuevo,
        $bind_actualiza_hv_antiguo,
        $bind_visado,
        $bind_estado,
        $bind_puntos,
        $bind_s_observacion,
        $bind_tipo_reemplazo,
        $bind_costo,
        $bind_anexos,
        $bind_pregrado,
        $bind_especializacion,
        $bind_maestria,
        $bind_doctorado,
        $bind_otro_estudio,
        $bind_experiencia_docente,
        $bind_experiencia_profesional,
        $bind_otra_experiencia,
        $bind_aprobador_depto_id
    ];

    // DEBUGGING: Print values right before bind_param
    error_log("DEBUG: Types string: " . $types . " (Length: " . strlen($types) . ")");
    error_log("DEBUG: Number of bind variables in array: " . count($bind_params_array));
    error_log("DEBUG: Bind params array: " . var_export($bind_params_array, true));

    // Bind parameters using call_user_func_array for robustness
    $refs = [];
    foreach ($bind_params_array as $key => $value) {
        $refs[$key] = &$bind_params_array[$key];
    }

    $bind_result = call_user_func_array([$stmt_insert, 'bind_param'], array_merge([$types], $refs));

    if ($bind_result === false) {
        die("Error al vincular parámetros: " . $stmt_insert->error);
    }

    // Ejecutar la consulta de inserción
    if ($stmt_insert->execute()) {
        // Redireccionar a consulta_todo_depto.php con las variables en modo POST
        echo "<form id='redirectForm' action='consulta_todo_depto_novedad.php' method='POST'>";
        echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
        echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
        echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
        echo "</form>";
        echo "<script>document.getElementById('redirectForm').submit();</script>";
    } else {
        die("Error al insertar el registro de eliminación: " . $stmt_insert->error);
    }

    // Cerrar el statement
    $stmt_insert->close();
    // Cerrar la conexión
    $conn->close();

} else {
    // Si no es una solicitud POST, redirigir o mostrar un mensaje de error
    echo "Acceso no autorizado.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesando Eliminación</title>
</head>
<body>
    <!-- Contenido opcional si el script no redirige inmediatamente -->
</body>
</html>
