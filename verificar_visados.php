<?php
// Incluir la función verificaVisados
include 'funciones.php'; // Asegúrate de que este archivo contenga la función verificaVisados

// Obtener los parámetros enviados por la solicitud POST
$anio_semestre = $_POST['anio_semestre'] ?? '';
$departamento_id = $_POST['departamento_id'] ?? '';
$tipo_docente = $_POST['tipo_docente'] ?? '';

// Llamar a la función verificaVisados
$verificaVisados = verificaVisados($anio_semestre, $departamento_id, $tipo_docente);

// Devolver el resultado
echo $verificaVisados;
?>
