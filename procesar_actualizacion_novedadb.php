<?php
// Incluye el archivo de funciones y la conexión a la base de datos
require 'funciones.php';
require_once 'conn.php';

// Función para normalizar valores vacíos (asegura NULL para cadenas vacías o solo espacios)
if (!function_exists('normalize_empty_values')) {
    function normalize_empty_values($value) {
        $trimmed_value = trim(strval($value));
        return empty($trimmed_value) ? null : $trimmed_value;
    }
}

// Verifica si la solicitud es de tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Recopilar los datos enviados desde el formulario/modal ---
    $id_solicitud_original = $_POST['id_solicitud'] ?? null;
    $facultad_id = $_POST['facultad_id'] ?? null;
    $departamento_id = $_POST['departamento_id'] ?? null;
    $anio_semestre = $_POST['anio_semestre'] ?? null;

    // Campos que pueden ser modificados
    $tipo_docente = $_POST['tipo_docente'] ?? null;
    $cedula = $_POST['cedula'] ?? null;
    $nombre = $_POST['nombre'] ?? null;
    $tipo_dedicacion = normalize_empty_values($_POST['tipo_dedicacion'] ?? '');
    $tipo_dedicacion_r = normalize_empty_values($_POST['tipo_dedicacion_r'] ?? '');
    $horas = (int) ($_POST['horas'] ?? 0);  // Convierte a entero ('' o null → 0)
    $horas_r = (int) ($_POST['horas_r'] ?? 0);
    $anexa_hv_docente_nuevo = $_POST['anexa_hv_docente_nuevo'] ?? null;
    $actualiza_hv_antiguo = $_POST['actualiza_hv_antiguo'] ?? null;
    $anexos = normalize_empty_values($_POST['link_anexos'] ?? '');
    $s_observacion_user_input = normalize_empty_values($_POST['observacion'] ?? '');
    $tipo_reemplazo = normalize_empty_values($_POST['tipo_reemplazo'] ?? '');

    // Validar que los parámetros esenciales estén presentes
    if ($id_solicitud_original === null || $facultad_id === null || $departamento_id === null || $anio_semestre === null) {
        die("Error: Parámetros esenciales no proporcionados para la modificación.");
    }

    // --- Lógica para determinar la sede según el tipo de docente y horas ---
    $sede = null;
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
            $sede = null;
        }
    }

    // Verificar la conexión a la base de datos
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // --- PASO 1: Obtener los datos del registro original de la tabla 'solicitudes' ---
    // Necesitamos estos datos para crear el nuevo registro en 'solicitudes_working_copy'
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
        die("Error: No se encontró el registro original en la tabla 'solicitudes' para modificar. id_solicitud: $id_solicitud_original, anio_semestre: $anio_semestre, facultad_id: $facultad_id, departamento_id: $departamento_id");
    }
    $original_data = $result_original->fetch_assoc();
    $stmt_fetch->close();
// --- PASO 2: Construir la observación de cambios ---
$observacion_cambios_detectados = [];
$observacion_final_para_db = $s_observacion_user_input;

// Traducción de valores clave
$traducciones_valores = [
    'TC' => 'TC',
    'MT' => 'MT',
    'Ocasional' => 'Ocasional',
    'Catedra' => 'Cátedra'
];

// 1. Procesar cambios para docentes OCASIONALES (solo dedicaciones)
if ($tipo_docente == "Ocasional") {
    // Función para formatear cambios de dedicación
    $formatearDedicacion = function($valor, $tipo) use ($traducciones_valores) {
        if ($valor) {
            return $traducciones_valores[$valor] ?? $valor;
        }
        return '';
    };

    // Procesar dedicación Popayán
    $old_dedicacion = normalize_empty_values($original_data['tipo_dedicacion'] ?? '');
    $new_dedicacion = normalize_empty_values($tipo_dedicacion);
    
    if ($old_dedicacion != $new_dedicacion) {
        $old_display = $formatearDedicacion($old_dedicacion, 'Popayán');
        $new_display = $formatearDedicacion($new_dedicacion, 'Popayán');
        
        $representacion = "Dedicación Popayán: ";
        if ($old_display) $representacion .= "$old_display ";
        $representacion .= " a ";
        if ($new_display) $representacion .= " $new_display";
        
        $observacion_cambios_detectados[] = $representacion;
    }

    // Procesar dedicación Regionalización
    $old_dedicacion_r = normalize_empty_values($original_data['tipo_dedicacion_r'] ?? '');
    $new_dedicacion_r = normalize_empty_values($tipo_dedicacion_r);
    
    if ($old_dedicacion_r != $new_dedicacion_r) {
        $old_display = $formatearDedicacion($old_dedicacion_r, 'Regionalización');
        $new_display = $formatearDedicacion($new_dedicacion_r, 'Regionalización');
        
        $representacion = "Dedicación Regionalización: ";
        if ($old_display) $representacion .= "$old_display ";
        $representacion .= " a ";
        if ($new_display) $representacion .= " $new_display";
        
        $observacion_cambios_detectados[] = $representacion;
    }
} 
// 2. Procesar cambios para docentes CÁTEDRA (solo horas)
elseif ($tipo_docente == "Catedra") {
    // Función para procesar cambios de horas
    $procesarHoras = function($old_value, $new_value, $tipo) {
        // Convertir a float para comparación
        $old_float = ($old_value === null || $old_value === '') ? 0.0 : (float)$old_value;
        $new_float = ($new_value === null || $new_value === '') ? 0.0 : (float)$new_value;
        
        // Solo mostrar si hay cambio real y no es de 0 a 0
        if ($old_float != $new_float && ($old_float != 0 || $new_float != 0)) {
            $old_display = $old_float > 0 ? number_format($old_float, 1) : '';
            $new_display = $new_float > 0 ? number_format($new_float, 1) : '';
            
            $representacion = "Horas $tipo: ";
            if ($old_display) $representacion .= "$old_display ";
            $representacion .= " a ";
            if ($new_display) $representacion .= " $new_display";
            
            return $representacion;
        }
        return null;
    };

    // Procesar horas Popayán
    if ($cambio = $procesarHoras(
        $original_data['horas'] ?? null, 
        $horas, 
        'Popayán'
    )) {
        $observacion_cambios_detectados[] = $cambio;
    }

    // Procesar horas Regionalización
    if ($cambio = $procesarHoras(
        $original_data['horas_r'] ?? null, 
        $horas_r, 
        'Regionalización'
    )) {
        $observacion_cambios_detectados[] = $cambio;
    }
}

// Formatear y construir la observación final
if (!empty($observacion_cambios_detectados)) {
    $cambios_str = implode(" | ", $observacion_cambios_detectados);
    $observacion_generada = "Cambios: $cambios_str";
    
    // Si hay observación del usuario Y cambios detectados, combínalos.
    // Si solo hay cambios detectados, usa solo los cambios.
    $observacion_final_para_db = ($s_observacion_user_input ? "$s_observacion_user_input | " : "") . $observacion_generada;
}// --- PASO 3: Insertar el nuevo registro de "modificación" en solicitudes_working_copy ---
    $working_table_name = "solicitudes_working_copy";

    // Preparar la consulta INSERT para la novedad de modificación
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
            ?, ?, 'Modificar', ?, ?, ?,
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
    
    // Obtener el ID del aprobador de departamento desde la sesión
    $aprobador_depto_id = $_SESSION['user_id_depto'] ?? 1; 

    // Prepara los valores para bind_param
    $bind_params_array = [
        $id_solicitud_original,
        $anio_semestre,
        $facultad_id,
        $departamento_id,
        $tipo_docente,
        $cedula,
        $nombre,
        $tipo_dedicacion,
        $tipo_dedicacion_r,
        $horas,
        $horas_r,
        $sede,
        $anexa_hv_docente_nuevo,
        $actualiza_hv_antiguo,
        $original_data['visado'],
        $original_data['estado'],
        $original_data['puntos'],
        $observacion_final_para_db,
        $tipo_reemplazo,
        $original_data['costo'],
        $anexos,
        $original_data['pregrado'],
        $original_data['especializacion'],
        $original_data['maestria'],
        $original_data['doctorado'],
        $original_data['otro_estudio'],
        $original_data['experiencia_docente'],
        $original_data['experiencia_profesional'],
        $original_data['otra_experiencia'],
        $aprobador_depto_id
    ];

    // Corregimos la cadena de tipos para que coincida con los 30 parámetros
    $types = "isiisssssssssiisdssdsssssssssi";
    
    // Bind de parámetros de forma robusta
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
        // Redireccionar a la página principal con los parámetros en modo POST
        echo "<form id='redirectForm' action='consulta_todo_depto_novedad.php' method='POST'>";
        echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
        echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
        echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
        echo "</form>";
        echo "<script>document.getElementById('redirectForm').submit();</script>";
    } else {
        die("Error al insertar el registro de modificación: " . $stmt_insert->error);
    }

    $stmt_insert->close();
    $conn->close();

} else {
    echo "Acceso no autorizado.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesando Modificación</title>
</head>
<body>
    <!-- Contenido opcional si el script no redirige inmediatamente -->
</body>
</html>
