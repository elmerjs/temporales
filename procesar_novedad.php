<?php
$tipo_novedad = $_POST['tipo_novedad'];
$facultad_id = $_POST['facultad_id'];
$departamento_id = $_POST['departamento_id'];
$anio_semestre = $_POST['anio_semestre'];
$tipo_docente = $_POST['tipo_docente'];
//echo "tipo docente en procesar novedad: ".$tipo_docente; exit;
$tipo_usuario = $_POST['tipo_usuario'];

switch ($tipo_novedad) {
    case 'Eliminar':
        header("Location: eliminar_novedad.php?" . 
            "facultad_id=" . urlencode($facultad_id) . 
            "&departamento_id=" . urlencode($departamento_id) . 
            "&anio_semestre=" . urlencode($anio_semestre) . 
            "&tipo_docente=" . urlencode($tipo_docente) . 
            "&tipo_usuario=" . urlencode($tipo_usuario));
        break;

    case 'Modificar':
       header("Location: actualizar_novedad.php?" . 
            "facultad_id=" . urlencode($facultad_id) . 
            "&departamento_id=" . urlencode($departamento_id) . 
            "&anio_semestre=" . urlencode($anio_semestre) . 
            "&tipo_docente=" . urlencode($tipo_docente) . 
            "&tipo_usuario=" . urlencode($tipo_usuario));
        break;

    case 'Adicionar':
        header("Location: adicionar_novedad.php?" . 
            "facultad_id=" . urlencode($facultad_id) . 
            "&departamento_id=" . urlencode($departamento_id) . 
            "&anio_semestre=" . urlencode($anio_semestre) . 
            "&tipo_docente=" . urlencode($tipo_docente) . 
            "&tipo_usuario=" . urlencode($tipo_usuario));
        break;

    default:
        echo "Tipo de novedad no válido.";
}
