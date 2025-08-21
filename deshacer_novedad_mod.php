<?php
session_start(); // Inicia la sesión si aún no está iniciada

// Incluye tu archivo de conexión a la base de datos
require 'conn.php';

// Verifica si la conexión se ha establecido correctamente
if (!isset($conn) || $conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verifica si el usuario ha iniciado sesión y tiene permisos
if (!isset($_SESSION['name'])) {
    header("Location: index.html"); // Redirige a la página de inicio de sesión
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_solicitud = $_POST['id_solicitud'] ?? null;
    $anio_semestre = $_POST['anio_semestre'] ?? null;
    $departamento_id = $_POST['departamento_id'] ?? null;

    // Sanear las entradas para prevenir inyección SQL
    $id_solicitud = filter_var($id_solicitud, FILTER_VALIDATE_INT);
    $anio_semestre = filter_var($anio_semestre, FILTER_SANITIZE_STRING);
    $departamento_id = filter_var($departamento_id, FILTER_VALIDATE_INT);

    if (empty($id_solicitud) || empty($anio_semestre) || empty($departamento_id)) {
        // Redirigir con error si faltan parámetros esenciales
        header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=parametros_faltantes");
        exit();
    }

    // --- VERIFICACIÓN DEL ESTADO DE FACULTAD ---
    $sql_check_status = "SELECT estado_facultad FROM solicitudes_working_copy WHERE id_solicitud = ?";
    $stmt_check = $conn->prepare($sql_check_status);

    if ($stmt_check === false) {
        error_log("Error al preparar la sentencia de verificación de estado: " . $conn->error);
        header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=error_prep_check");
        exit();
    }

    $stmt_check->bind_param("i", $id_solicitud);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();

    // Si el registro no existe
    if (!$row_check) {
        header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=registro_no_encontrado");
        exit();
    }

    $estado_facultad = strtolower(trim($row_check['estado_facultad']));

    // Si está aprobado por facultad → no permitir deshacer
    if ($estado_facultad === 'aprobado') {
        $error_message = "El registro ya ha sido APROBADO por facultad y no puede deshacerse.";
        header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=" . urlencode($error_message));
        exit();
    }
    // --- FIN VERIFICACIÓN ---

    // LÓGICA PRINCIPAL DE DESHACER
    if ($estado_facultad === 'rechazado') {
        // Si está rechazado → UPDATE archivado = 1
        $sql_update = "UPDATE solicitudes_working_copy SET archivado = 1 WHERE id_solicitud = ?";
        $stmt_update = $conn->prepare($sql_update);

        if ($stmt_update === false) {
            error_log("Error al preparar la sentencia UPDATE: " . $conn->error);
            header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=error_prep_update");
            exit();
        }

        $stmt_update->bind_param("i", $id_solicitud);

        if ($stmt_update->execute()) {
            header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&mensaje=novedad_archivada");
            exit();
        } else {
            error_log("Error al archivar novedad (UPDATE): " . $stmt_update->error);
            header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=error_archivar_novedad");
            exit();
        }
    } else {
        // Para otros casos (pendiente, etc.) → DELETE
        $sql_delete = "DELETE FROM solicitudes_working_copy WHERE id_solicitud = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        if ($stmt_delete === false) {
            error_log("Error al preparar la sentencia DELETE: " . $conn->error);
            header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=error_prep_delete");
            exit();
        }

        $stmt_delete->bind_param("i", $id_solicitud);

        if ($stmt_delete->execute()) {
            header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&mensaje=novedad_eliminada");
            exit();
        } else {
            error_log("Error al deshacer novedad (DELETE): " . $stmt_delete->error);
            header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=error_deshacer_novedad");
            exit();
        }
    }
} else {
    // Si la solicitud no es POST, redirigir
    header("Location: index.html");
    exit();
}

// Cierra la conexión al final del script
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>