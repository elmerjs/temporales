<?php
// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
session_start();
$currentYear = date("Y");

require 'cn.php';
if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];
} else {
    $nombre_sesion = "elmer jurado";
}

$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_fac = $row['email_padre'];
    $pk_fac = $row['fk_fac_user'];
    $email_dp = $row['Email'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user = $row['fk_depto_user'];
    $id_user = $row['Id'];
}

// Validar datos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['version_glosa']) || !isset($_POST['fk_dp_glosa']) || !isset($_POST['glosas'])) {
    die("<script>alert('Datos incompletos'); window.history.back();</script>");
}

$version_glosa = intval($_POST['version_glosa']);
$fk_dp_glosa = intval($_POST['fk_dp_glosa']);
$numero_oficio = isset($_POST['numero_oficio']) ? mysqli_real_escape_string($con, $_POST['numero_oficio']) : '';
$fecha_oficio = isset($_POST['fecha_oficio']) ? mysqli_real_escape_string($con, $_POST['fecha_oficio']) : null;

// Validar campos obligatorios
if (empty($numero_oficio)) {
    die("<script>alert('El número de oficio es requerido'); window.history.back();</script>");
}

// Definir tipos de glosas
$tipos_glosas = [
    'Acto Administrativo errado',
    'Actualizar año de capacitación',
    'Actualizar o depurar proyectos de Investigación',
    'Ajustar al total de horas del parámetro',
    'Diferencia de horas de contratación o tipo de vinculación',
    'Profesor No solicitado',
    'Registro de la actividad con error',
    'Número de semanas del periodo con error',
    'Número de semanas proyectos de Extensión con error',
    'Falta Plan de capacitación',
    'Inconsistencias Matriculas Académica',
    'No aplica la observación',
    'No cumple el total de Docencia Directa',
    'Número de horas Plan de Estudio erradas',
    'Sin visado por jefe y/o decano',
    'Supera el Factor Multiplicador',
    'Trabajo de grado finalizado o ajustar semanas',
    'Mayor número de horas de planeación y evaluación curricular',
    'Asignatura con código entre labor y oferta errado',
     'Registro labor pendiente',
    'No postulados en banco de aspirantes',
    'Otra'
];

// Iniciar transacción
mysqli_begin_transaction($con);

try {
    // 1. Actualizar o insertar en oficios_glosas
    $sql_check_oficio = "SELECT id_oficio FROM oficios_glosas 
                        WHERE fk_dp_glosa = $fk_dp_glosa AND version_glosa = $version_glosa";
    $result = mysqli_query($con, $sql_check_oficio);
    
    if (mysqli_num_rows($result) > 0) {
        // Actualizar registro existente
        $sql_oficio = "UPDATE oficios_glosas SET
                      numero_oficio = '$numero_oficio',
                      fecha_oficio = " . ($fecha_oficio ? "'$fecha_oficio'" : "NULL") . "
                      WHERE fk_dp_glosa = $fk_dp_glosa AND version_glosa = $version_glosa";
    } else {
        // Insertar nuevo registro
        $sql_oficio = "INSERT INTO oficios_glosas 
                      (fk_dp_glosa, version_glosa, numero_oficio, fecha_oficio)
                      VALUES
                      ($fk_dp_glosa, $version_glosa, '$numero_oficio', " . ($fecha_oficio ? "'$fecha_oficio'" : "NULL") . ")";
    }
    
    if (!mysqli_query($con, $sql_oficio)) {
        throw new Exception("Error al actualizar oficio: " . mysqli_error($con));
    }

    // 2. Eliminar las glosas existentes para esta versión
    $sql_delete = "DELETE FROM glosas WHERE fk_dp_glosa = $fk_dp_glosa AND version_glosa = $version_glosa";
    if (!mysqli_query($con, $sql_delete)) {
        throw new Exception("Error al eliminar glosas anteriores: " . mysqli_error($con));
    }

    // 3. Insertar las nuevas glosas
    $glosas_insertadas = 0;
    foreach ($tipos_glosas as $tipo) {
        $cantidad = isset($_POST['glosas'][$tipo]) ? intval($_POST['glosas'][$tipo]) : 0;
        $tipo_escaped = mysqli_real_escape_string($con, $tipo);
        
        $sql_insert = "INSERT INTO glosas (version_glosa, Tipo_glosa, cantidad_glosas, fk_dp_glosa, fk_user) 
                      VALUES ($version_glosa, '$tipo_escaped', $cantidad, $fk_dp_glosa, $id_user)";
        
        if (!mysqli_query($con, $sql_insert)) {
            throw new Exception("Error al insertar glosa: " . mysqli_error($con));
        }
        $glosas_insertadas++;
    }

    // Confirmar transacción
    mysqli_commit($con);
    
    // Éxito
    $_SESSION['success_message'] = "¡Datos actualizados correctamente! (Versión $version_glosa)";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();

} catch (Exception $e) {
    // Error - Rollback
    mysqli_rollback($con);
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}
?>