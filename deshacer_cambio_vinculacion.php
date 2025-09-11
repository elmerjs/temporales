<?php
// deshacer_cambio_vinculacion.php (Versión corregida)

session_start();
require 'conn.php';

// --- Verificación de Seguridad y Parámetros ---
if (!isset($_SESSION['name'])) {
    header("Location: index.html");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.html");
    exit();
}

$id_adicion = $_POST['id_solicitud'] ?? null;
$anio_semestre = $_POST['anio_semestre'] ?? null;
$departamento_id = $_POST['departamento_id'] ?? null;

if (empty($id_adicion) || empty($anio_semestre) || empty($departamento_id)) {
    header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=parametros_faltantes");
    exit();
}

// --- VERIFICACIÓN DEL ESTADO DE FACULTAD ---
$stmt_check = $conn->prepare("SELECT estado_facultad, cedula FROM solicitudes_working_copy WHERE id_solicitud = ?");
$stmt_check->bind_param("i", $id_adicion);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row_check = $result_check->fetch_assoc();
$stmt_check->close();

if (!$row_check) {
    header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=registro_no_encontrado");
    exit();
}

$estado_facultad = strtolower(trim($row_check['estado_facultad']));
$cedula_profesor = $row_check['cedula'];
error_log("Intentando eliminar registros para cédula: $cedula_profesor, período: $anio_semestre, depto: $departamento_id");

if ($estado_facultad === 'aprobado') {
    $error_message = "Un cambio de vinculación ya APROBado por facultad no puede deshacerse.";
    header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=" . urlencode($error_message));
    exit();
}

// --- LÓGICA PRINCIPAL DE DESHACER EL PAR ---
$conn->begin_transaction();

try {
    // 1. Eliminar AMBOS registros (adicionar y eliminar) usando la cédula.
    // Consulta corregida para manejar diferentes formatos de mayúsculas/minúsculas
    $sql_delete_pair = "DELETE FROM solicitudes_working_copy 
                        WHERE cedula = ? 
                        AND anio_semestre = ? 
                        AND departamento_id = ? 
                        AND (LOWER(TRIM(novedad)) = 'adicionar' 
                             OR LOWER(TRIM(novedad)) = 'Eliminar'
                             OR LOWER(TRIM(novedad)) = 'adicion'
                             OR LOWER(TRIM(novedad)) = 'eliminacion')";
                        
    $stmt_delete = $conn->prepare($sql_delete_pair);
    $stmt_delete->bind_param("ssi", $cedula_profesor, $anio_semestre, $departamento_id);
error_log("Ejecutando consulta: $sql_delete_pair");

    
    if (!$stmt_delete->execute()) {
        throw new Exception("Error al ejecutar la eliminación del par: " . $stmt_delete->error);
    }

    // Verificar cuántas filas fueron afectadas
    $rows_affected = $stmt_delete->affected_rows;
    
    if ($rows_affected === 0) {
        throw new Exception("No se encontraron registros para eliminar con cédula: $cedula_profesor");
    }

    $conn->commit();
    header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&mensaje=cambio_deshecho_ok&filas_afectadas=" . $rows_affected);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error al deshacer cambio de vinculación: " . $e->getMessage());
    header("Location: consulta_todo_depto_novedad.php?anio_semestre=" . urlencode($anio_semestre) . "&departamento_id=" . urlencode($departamento_id) . "&error=error_deshacer_cambio&detalle=" . urlencode($e->getMessage()));
    exit();
}

$conn->close();
?>