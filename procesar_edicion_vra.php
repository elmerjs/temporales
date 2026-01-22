<?php
// procesar_edicion_vra.php - Lógica de Edición VRA con Transformación de Novedad
session_start();
require_once('conn.php');

header('Content-Type: application/json');

// --- 1. VALIDACIONES Y SEGURIDAD ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso o método no permitido.']);
    exit;
}

// --- 2. RECEPCIÓN DE DATOS DEL MODAL ---
$id_solicitud = $_POST['id_solicitud'] ?? '';
$nuevo_tipo_docente = $_POST['tipo_docente'] ?? '';
$nueva_dedicacion = $_POST['tipo_dedicacion'] ?? null;
$nueva_dedicacion_r = $_POST['tipo_dedicacion_r'] ?? null;
$nuevas_horas = $_POST['horas'] ?? 0;
$nuevas_horas_r = $_POST['horas_r'] ?? 0;
$s_observacion = $_POST['s_observacion'] ?? '';

if (empty($id_solicitud) || empty($nuevo_tipo_docente)) {
    echo json_encode(['success' => false, 'error' => 'Datos esenciales incompletos.']);
    exit;
}

// --- 3. INICIO DE TRANSACCIÓN Y BÚSQUEDA ORIGINAL ---
$conn->begin_transaction();

try {
    // 3.1. Obtener todos los datos originales para posibles INSERT/Transformaciones
    $sql_fetch = "SELECT * FROM solicitudes_working_copy WHERE id_solicitud = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $id_solicitud);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();

    if ($result_fetch->num_rows === 0) { throw new Exception("Solicitud ID $id_solicitud no encontrada."); }
    $original_data = $result_fetch->fetch_assoc();
    
    // Normalización de datos para la lógica
    $novedad_original = $original_data['novedad'];
    $tipo_docente_original = $original_data['tipo_docente'];
    $tipo_docente_changed = ($nuevo_tipo_docente !== $tipo_docente_original);

    $success = false;
    
    // Campos comunes para la actualización de valores
    $update_fields = "tipo_docente = ?, tipo_dedicacion = ?, tipo_dedicacion_r = ?, horas = ?, horas_r = ?, s_observacion = ?";
    
    // --- 4. LÓGICA DE TRANSFORMACIÓN CONDICIONAL ---

    if ($novedad_original === 'Eliminar') {
        throw new Exception("La novedad 'Eliminar' no está disponible para edición.");
    }

    // 4.1. NOVEDAD "Modificar" (Escenario 2)
    if ($novedad_original === 'Modificar') {
        
        // Determinar qué valores usar para la actualización (incluyendo la limpieza de campos)
        $update_dedicacion = $nueva_dedicacion;
        $update_dedicacion_r = $nueva_dedicacion_r;
        $update_horas = $nuevas_horas;
        $update_horas_r = $nuevas_horas_r;

        // ** LÓGICA DE LIMPIEZA PARA ACTUALIZACIONES **
        if ($nuevo_tipo_docente === 'Ocasional') {
            // Ocasional usa dedicación, limpia horas
            $update_horas = null; 
            $update_horas_r = null;
        } else if ($nuevo_tipo_docente === 'Catedra') {
            // Cátedra usa horas, limpia dedicación
            $update_dedicacion = ''; // Cadena vacía para ENUM o VARCHAR
            $update_dedicacion_r = null; // NULL para VARCHAR
        }

        if (!$tipo_docente_changed) {
            // Caso A (Mismo Tipo Docente): UPDATE simple. Novedad se mantiene 'Modificar'.
            $sql = "UPDATE solicitudes_working_copy SET $update_fields, novedad = 'Modificar' WHERE id_solicitud = ?";
            $stmt = $conn->prepare($sql);
            // Parámetros: sssddsi (s: string, d: double/float/int, i: int)
            $stmt->bind_param("sssddsi", $nuevo_tipo_docente, $update_dedicacion, $update_dedicacion_r, $update_horas, $update_horas_r, $s_observacion, $id_solicitud);
            if (!$stmt->execute()) { throw new Exception("Error al actualizar (Modificar - Caso A): " . $stmt->error); }
            $success = true;
        } else {
            // Caso B (Cambio de Tipo Docente): Transformar a Eliminar + Crear Nuevo adicionar.

            // 1. Transformar a Eliminar: El registro actual se actualiza a 'Eliminar'.
            $sql_eliminar = "UPDATE solicitudes_working_copy SET novedad = 'Eliminar' WHERE id_solicitud = ?";
            $stmt_eliminar = $conn->prepare($sql_eliminar);
            $stmt_eliminar->bind_param("i", $id_solicitud);
            if (!$stmt_eliminar->execute()) { throw new Exception("Error al transformar a Eliminar (Modificar - Caso B): " . $stmt_eliminar->error); }

            // 2. Crear Nuevo: Se inserta un nuevo registro con novedad 'adicionar' y los nuevos valores.
            unset($original_data['id_solicitud']); 
            $original_data['novedad'] = 'adicionar';
            $original_data['tipo_docente'] = $nuevo_tipo_docente;
            $original_data['s_observacion'] = $s_observacion;
            $original_data['estado_vra'] = 'PENDIENTE'; // Vuelve a pendiente para nueva aprobación

            // ===================================================================================
            // ===== MEJORA: ASIGNACIÓN Y LIMPIEZA DE CAMPOS PARA EL NUEVO REGISTRO 'adicionar' =====
            // ===================================================================================
            if ($nuevo_tipo_docente === 'Ocasional') {
                // Ocasional usa dedicación, limpia horas.
                $original_data['tipo_dedicacion'] = $nueva_dedicacion;
                $original_data['tipo_dedicacion_r'] = $nueva_dedicacion_r;
                $original_data['horas'] = null;
                $original_data['horas_r'] = null;
            } else if ($nuevo_tipo_docente === 'Catedra') {
                // Cátedra usa horas, limpia dedicación.
                $original_data['tipo_dedicacion'] = ''; // Limpiar Dedicación (ENUM/VARCHAR)
                $original_data['tipo_dedicacion_r'] = null; // Limpiar Dedicación (VARCHAR)
                $original_data['horas'] = $nuevas_horas;
                $original_data['horas_r'] = $nuevas_horas_r;
            }
            // ===================================================================================

            $cols = implode(", ", array_keys($original_data));
            $placeholders = implode(", ", array_fill(0, count($original_data), '?'));
            $sql_insert = "INSERT INTO solicitudes_working_copy ($cols) VALUES ($placeholders)";
            
            $stmt_insert = $conn->prepare($sql_insert);
            
            // Lógica de bind_param dinámica por referencia (Esencial para INSERT genérico)
            $types = '';
            foreach ($original_data as $value) {
                // Asunción simplificada de tipos: 'd' para números, 's' para el resto
                // Se deben usar referencias para bind_param
                $types .= is_int($value) || is_float($value) || (is_numeric($value) && strpos($value, '.') !== false) ? 'd' : 's';
            }
            // Ajuste de tipos para que reflejen la estructura correcta: 'd' para horas, 's' para dedicación
            
            $bind_params = array_merge([$types], array_values($original_data));
            $refs = [];
            foreach ($bind_params as $key => $value) { $refs[$key] = &$bind_params[$key]; }
            
            if (!call_user_func_array([$stmt_insert, 'bind_param'], $refs)) { throw new Exception("Error al preparar INSERT (Modificar - Caso B)."); }
            if (!$stmt_insert->execute()) { throw new Exception("Error al insertar nueva solicitud (Modificar - Caso B): " . $stmt_insert->error); }
            $success = true;
        }
    }
    
    // 4.2. NOVEDAD "adicionar" (Escenario 1 & 3 - Cambio Vinculación)
    else if ($novedad_original === 'adicionar') {
        
        // ** LÓGICA DE LIMPIEZA PARA ACTUALIZACIONES **
        // Se determina qué valores usar en el UPDATE
        $update_dedicacion = $nueva_dedicacion;
        $update_dedicacion_r = $nueva_dedicacion_r;
        $update_horas = $nuevas_horas;
        $update_horas_r = $nuevas_horas_r;

        if ($nuevo_tipo_docente === 'Ocasional') {
            // Ocasional usa dedicación, limpia horas
            $update_horas = null; 
            $update_horas_r = null;
        } else if ($nuevo_tipo_docente === 'Catedra') {
            // Cátedra usa horas, limpia dedicación
            $update_dedicacion = ''; // Cadena vacía para ENUM o VARCHAR
            $update_dedicacion_r = null; // NULL para VARCHAR
        }

        $cedula = $original_data['cedula'];
        $anio_semestre = $original_data['anio_semestre'];

        // Buscar pareja 'Eliminar' para determinar si es un Cambio Vinculación
        $sql_find_pair = "SELECT id_solicitud, tipo_docente FROM solicitudes_working_copy WHERE cedula = ? AND anio_semestre = ? AND novedad = 'Eliminar' AND id_solicitud != ?";
        $stmt_find_pair = $conn->prepare($sql_find_pair);
        $stmt_find_pair->bind_param("ssi", $cedula, $anio_semestre, $id_solicitud);
        $stmt_find_pair->execute();
        $result_pair = $stmt_find_pair->get_result();
        
        if ($result_pair->num_rows === 0) {
            // ESCENARIO 1 (adicionar simple): UPDATE simple. Novedad se mantiene 'adicionar'.
            $sql = "UPDATE solicitudes_working_copy SET $update_fields, novedad = 'adicionar' WHERE id_solicitud = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssddsi", $nuevo_tipo_docente, $update_dedicacion, $update_dedicacion_r, $update_horas, $update_horas_r, $s_observacion, $id_solicitud);
            if (!$stmt->execute()) { throw new Exception("Error al actualizar (adicionar simple): " . $stmt->error); }
            $success = true;

        } else {
            // ESCENARIO 3 (Cambio Vinculación): El registro actual es el 'adicionar' del par.
            $pair_data = $result_pair->fetch_assoc();
            $id_pareja = $pair_data['id_solicitud'];
            $tipo_docente_pareja = $pair_data['tipo_docente']; // Tipo docente original (Eliminar)
            
            // Detectar reversión: si el admin cambia el tipo de docente para que coincida con el tipo original (el de la pareja Eliminar)
            $is_reversion = ($nuevo_tipo_docente === $tipo_docente_pareja);

            if (!$is_reversion) {
                // Caso A (Acepta cambio, edita valores): UPDATE simple al registro actual ('adicionar').
                $sql_update = "UPDATE solicitudes_working_copy SET $update_fields WHERE id_solicitud = ?";
                $stmt_update = $conn->prepare($sql_update);
                // Usamos las variables $update_ limpiadas
                $stmt_update->bind_param("sssddsi", $nuevo_tipo_docente, $update_dedicacion, $update_dedicacion_r, $update_horas, $update_horas_r, $s_observacion, $id_solicitud);
                if (!$stmt_update->execute()) { throw new Exception("Error al actualizar (Cambio Vinculación - Caso A): " . $stmt_update->error); }
                $success = true;

            } else {
                // Caso B (Revierte a vinculación original): Borrar actual ('adicionar') + Recuperar Pareja ('Eliminar') -> 'Modificar'.

                // 1. Borrar: DELETE del registro actual (el 'adicionar').
                $sql_delete = "DELETE FROM solicitudes_working_copy WHERE id_solicitud = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $id_solicitud);
                if (!$stmt_delete->execute()) { throw new Exception("Error al borrar registro 'adicionar' (Cambio Vinculación - Caso B): " . $stmt_delete->error); }

                // 2. Transformar Pareja: Actualizar novedad de 'Eliminar' a 'Modificar' y aplicar los nuevos valores editados.
                $sql_update_pair = "UPDATE solicitudes_working_copy SET novedad = 'Modificar', $update_fields WHERE id_solicitud = ?";
                $stmt_update_pair = $conn->prepare($sql_update_pair);
                 // Usamos las variables $update_ limpiadas
                $stmt_update_pair->bind_param("sssddsi", $nuevo_tipo_docente, $update_dedicacion, $update_dedicacion_r, $update_horas, $update_horas_r, $s_observacion, $id_pareja);
                if (!$stmt_update_pair->execute()) { throw new Exception("Error al actualizar pareja a 'Modificar' (Cambio Vinculación - Caso B): " . $stmt_update_pair->error); }
                $success = true;
            }
        }
    }
    
    // --- 5. CIERRE DE TRANSACCIÓN ---
    if ($success) {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Solicitud VRA actualizada y reestructurada correctamente.']);
    } else {
        throw new Exception("La operación no pudo completarse. Revise la lógica o el estado de la novedad original.");
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en procesar_edicion_vra.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en la transacción: ' . $e->getMessage()]);
}

$conn->close();
?>