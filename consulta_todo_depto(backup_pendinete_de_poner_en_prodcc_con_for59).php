<?php
require 'vendor/autoload.php'; 
require 'cn.php'; 
require 'funciones.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Style\Cell;
use PhpOffice\PhpWord\Style\Border;
use PhpOffice\PhpWord\Style\TableWidth;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
$config = require 'config_email.php';

// Definir estilos de texto para las celdas de la tabla
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);
$cellTextStyle = array('size' => 9, 'name' => 'Arial');
$cellTextStyleb = array('size' => 8, 'name' => 'Arial');
$cellTextStylef = array('size' => 6, 'name' => 'Arial');
$headerCellStyle = array('bgColor' => '#f2f2f2');

// Crear una nueva instancia de PhpWord
$phpWord = new PhpWord();
$pageWidth = 12240; 
$pageHeight = 15840; 

$section = $phpWord->addSection(array(
    'pageSizeW' => $pageWidth, 
    'pageSizeH' => $pageHeight,
    'marginLeft' => 1700,    
    'marginRight' => 1700,  
));
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Obtener los parámetros de la URL
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$num_oficio = $_GET['num_oficio'];
$elaboro = $_GET['elaboro'];
$nombre_fac = $_GET['nombre_fac'];
$num_acta = $_GET['acta'] ?? '';
$fecha_acta = $_GET['fecha_acta'] ?? '';

// Formatear la fecha
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');
$fecha_acta_b = '';
if (!empty($fecha_acta)) {
    $timestamp = strtotime($fecha_acta);
    if ($timestamp !== false) {
        $fecha_acta_b = strftime('%d de %B de %Y', $timestamp);
    }
}

// Unir el número de acta con la fecha formateada
if (empty($num_acta) && empty($fecha_acta_b)) {
    $acta = 'Acta no especificada'; 
} elseif (empty($num_acta)) {
    $acta = 'Acta del ' . $fecha_acta_b;
} elseif (empty($fecha_acta_b)) {
    $acta = $num_acta . ' (fecha no especificada)';
} else {
    $acta = $num_acta . ' del ' . $fecha_acta_b;
}

$facultad_id= obteneridfac($departamento_id);
$fecha_oficio = $_GET['fecha_oficio'];
$oficio_con_fecha_depto = $num_oficio . " " . $fecha_oficio;
$folios = isset($_GET['folios']) && trim($_GET['folios']) !== '' ? trim($_GET['folios']) : 0;
$decano = obtenerDecano($facultad_id);

// Array de departamentos (Imágenes)
$departamentos = [
    1 => ['encabezado' => 'img/encabezado_artes_plasticas.png', 'pie' => 'img/pieartes.png'],
    2 => ['encabezado' => 'img/encabezado_diseno.png', 'pie' => 'img/pieartes.png'],
    3 => ['encabezado' => 'img/encabezado_musica.png', 'pie' => 'img/pieartes.png'],
    4 => ['encabezado' => 'img/encabezado_agroindustria.png', 'pie' => 'img/pieagro.png'],
    5 => ['encabezado' => 'img/encabezado_c_agropecuarias.png', 'pie' => 'img/pieagro.png'],
    6 => ['encabezado' => 'img/encabezado_anestesiologia.png', 'pie' => 'img/piesalud.png'],
    7 => ['encabezado' => 'img/encabezado_c_fisiologicas.png', 'pie' => 'img/piesalud.png'],
    8 => ['encabezado' => 'img/encabezado_c_quirurgicas.png', 'pie' => 'img/piesalud.png'],
    9 => ['encabezado' => 'img/encabezado_fisioterapia.png', 'pie' => 'img/piesalud.png'],
    10 => ['encabezado' => 'img/encabezado_fonoaudiologia.png', 'pie' => 'img/piesalud.png'],
    11 => ['encabezado' => 'img/encabezado_enfermeria.png', 'pie' => 'img/piesalud.png'],
    12 => ['encabezado' => 'img/encabezado_ginecologia.png', 'pie' => 'img/piesalud.png'],
    13 => ['encabezado' => 'img/encabezado_medicina_interna.png', 'pie' => 'img/piesalud.png'],
    14 => ['encabezado' => 'img/encabezado_medicina_social.png', 'pie' => 'img/piesalud.png'],
    15 => ['encabezado' => 'img/encabezado_morfologia.png', 'pie' => 'img/piesalud.png'],
    16 => ['encabezado' => 'img/encabezado_patologia.png', 'pie' => 'img/piesalud.png'],
    17 => ['encabezado' => 'img/encabezado_pediatria.png', 'pie' => 'img/piesalud.png'],
    18 => ['encabezado' => 'img/encabezadoc_administrativas.png', 'pie' => 'img/piecontables.png'],
    19 => ['encabezado' => 'img/encabezadoc_contables.png', 'pie' => 'img/piecontables.png'],
    20 => ['encabezado' => 'img/encabezadoc_turismo.png', 'pie' => 'img/piecontables.png'],
    21 => ['encabezado' => 'img/encabezadoc_economicas.png', 'pie' => 'img/piecontables.png'],
    22 => ['encabezado' => 'img/encabezado_antropologia.png', 'pie' => 'img/piehumanaseyl.png'],
    23 => ['encabezado' => 'img/encabezado_espanol.png', 'pie' => 'img/piehumanaseyl.png'],
    24 => ['encabezado' => 'img/encabezado_estudios_interculturales.png', 'pie' => 'img/piehumanaseyl.png'],
    25 => ['encabezado' => 'img/encabezado_filosofia.png', 'pie' => 'img/piehumanaseyl.png'],
    26 => ['encabezado' => 'img/encabezado_geografia.png', 'pie' => 'img/piehumanaseyl.png'],
    27 => ['encabezado' => 'img/encabezado_historia.png', 'pie' => 'img/piehumanaseyl.png'],
    28 => ['encabezado' => 'img/encabezado_lenguas.png', 'pie' => 'img/piehumanaseyl.png'],
    29 => ['encabezado' => 'img/encabezado_linguistica.png', 'pie' => 'img/piehumanaseyl.png'],
    30 => ['encabezado' => 'img/encabezado_fish.png', 'pie' => 'img/piehumanaseyl.png'],
    31 => ['encabezado' => 'img/encabezado_biologia.png', 'pie' => 'img/piefacned.png'],
    32 => ['encabezado' => 'img/encabezado_educacion_fisica.png', 'pie' => 'img/piefacned.png'],
    33 => ['encabezado' => 'img/encabezado_educacion_pedagogia.png', 'pie' => 'img/piefacned.png'],
    34 => ['encabezado' => 'img/encabezado_fisica.png', 'pie' => 'img/piefacned.png'],
    35 => ['encabezado' => 'img/encabezado_matematicas.png', 'pie' => 'img/piefacned.png'],
    36 => ['encabezado' => 'img/encabezado_quimica.png', 'pie' => 'img/piefacned.png'],
    37 => ['encabezado' => 'img/encabezado_c_politicas.png', 'pie' => 'img/piederecho.png'],
    38 => ['encabezado' => 'img/encabezado_comunicacion_social.png', 'pie' => 'img/piederecho.png'],
    39 => ['encabezado' => 'img/encabezado_derecho_laboral.png', 'pie' => 'img/piederecho.png'],
    40 => ['encabezado' => 'img/encabezado_derecho_penal.png', 'pie' => 'img/piederecho.png'],
    41 => ['encabezado' => 'img/encabezado_derecho_privado.png', 'pie' => 'img/piederecho.png'],
    42 => ['encabezado' => 'img/encabezado_derecho_publico.png', 'pie' => 'img/piederecho.png'],
    43 => ['encabezado' => 'img/encabezado_construccion.png', 'pie' => 'img/piecivil.png'],
    44 => ['encabezado' => 'img/encabezado_estructuras.png', 'pie' => 'img/piecivil.png'],
    45 => ['encabezado' => 'img/encabezado_geotecnica.png', 'pie' => 'img/piecivil.png'],
    46 => ['encabezado' => 'img/encabezado_hidraulica.png', 'pie' => 'img/piecivil.png'],
    47 => ['encabezado' => 'img/encabezado_ambiental.png', 'pie' => 'img/piecivil.png'],
    48 => ['encabezado' => 'img/encabezado_vias.png', 'pie' => 'img/piecivil.png'],
    49 => ['encabezado' => 'img/encabezado_telecomunicaciones.png', 'pie' => 'img/piefiet.png'],
    50 => ['encabezado' => 'img/encabezado_telematica.png', 'pie' => 'img/piefiet.png'],
    51 => ['encabezado' => 'img/encabezado_instrumentacion.png', 'pie' => 'img/piefiet.png'],
    52 => ['encabezado' => 'img/encabezado_sistemas.png', 'pie' => 'img/piefiet.png'],
    57 => ['encabezado' => 'img/encabezado_pfipng.png', 'pie' => 'img/piefiet.png']
];

if (array_key_exists($departamento_id, $departamentos)) {
    $imgencabezado = $departamentos[$departamento_id]['encabezado'];
    $imgpie = $departamentos[$departamento_id]['pie'];
}else {
    $imgencabezado = 'img/encabezado_generico.png';
    $imgpie = 'img/piegenerico.png';
}

$encabezado_oficio = $num_oficio;
$header = $section->addHeader();
$header->addImage($imgencabezado, array(
    'height' => 80, 
    'marginTop' => -284, 
    'align' => 'left', 
));

$paragraphStylexz = array('lineHeight' => 0.8, 'spaceAfter' => 0, 'spaceBefore' => 0);
$fontStylecuerpo = array('name' => 'Arial', 'size' => 11);

$section->addText($encabezado_oficio, array('size' => 11, 'bold' => false, 'name' => 'Arial'),$paragraphStylexz);

// Obtener la fecha actual en español
$fecha_actual = strftime('%d de %B de %Y', strtotime($fecha_oficio));
$section->addText('Popayán, ' . $fecha_actual,$fontStylecuerpo,$paragraphStylexz);
$saltoLineaStyle = array('lineHeight' => 0.8); 

$section->addTextBreak(1, $paragraphStylexz);

// Agregar textos sin espacio entre ellos
$section->addText('Decano', $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));
$decano = mb_strtoupper($decano, 'UTF-8');
$section->addText($decano, $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));

$section->addText('Presidente Consejo de Facultad de ' . $nombre_fac, $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));
$section->addText('Universidad del Cauca', $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));

$section->addTextBreak(); // Inserta un salto de línea
$section->addText('Cordial saludo,',$fontStylecuerpo);

$styleParagraph = array('align' => 'both'); 
$nombre_depto= obtenerNombreDepartamento($departamento_id);
    $section->addText(
        'Asunto: Solicitud Novedad(es) de vinculación Departamento de '.obtenerNombreDepartamento($departamento_id) . ' periodo '.$anio_semestre.'.',
        $fontStylecuerpo,
        $styleParagraph
    );

    
// Escapar las variables para prevenir inyecciones SQL
$departamento_id = $con->real_escape_string($departamento_id);
$anio_semestre = $con->real_escape_string($anio_semestre);
date_default_timezone_set('America/Bogota'); 

$fecha_hora_envio = date('Y-m-d H:i:s');

// Realizar el SELECT para verificar el estado actual de dp_acepta_fac
$sql_select = "SELECT dp_acepta_fac 
                FROM depto_periodo 
                WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";

$result = $con->query($sql_select);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
    $sql_update_fac = '';
} 

$consulta_depto = "SELECT DISTINCT NOMBRE_DEPTO_CORT, depto_nom_propio 
                    FROM solicitudes_working_copy 
                    JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes_working_copy.departamento_id
                    JOIN facultad ON facultad.PK_FAC = solicitudes_working_copy.facultad_id
                    WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre'";

$resultadodepto = $con->query($consulta_depto);

if (!$resultadodepto) {
    die('Error en la consulta: ' . $con->error);
}

$nom_depto = ""; 
$paragraphStylec = array('spaceAfter' => 1);

while ($rowdepto = $resultadodepto->fetch_assoc()) {
    $nom_depto = mb_strtoupper($rowdepto['depto_nom_propio'], 'UTF-8');
}
$nom_depto = mb_strtoupper($nombre_depto, 'UTF-8');

$section->addText('Departamento de ' . $nombre_depto, 
    array('bold' => true), 
    $paragraphStylec
);

// Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                    FROM solicitudes_working_copy 
                    JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes_working_copy.departamento_id
                    JOIN facultad ON facultad.PK_FAC = solicitudes_working_copy.facultad_id
                    WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre'
                        AND estado_depto = 'PENDIENTE'
                    ";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}
$paragraphStyleb = array('spaceBefore' => 50, 'spaceAfter' => 10, 'lineHeight' => 1);
$fontStyleb = array('name' => 'Arial', 'size' => 10);
$iteracion = 0; 
$paragraphStyleb = array('spaceBefore' => 50, 'spaceAfter' => 10, 'lineHeight' => 1);
$fontStyleb = array('name' => 'Arial', 'size' => 10);
$cellTextStyle = array('name' => 'Arial', 'size' => 9);
$headerCellStyle = array('bgColor' => 'F2F2F2', 'valign' => VerticalJc::CENTER);
$paragraphStyle = array('alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0);

// Estilos para el texto de las observaciones
$paragraphStyleLeft = array('alignment' => Jc::LEFT, 'spaceAfter' => 0, 'spaceBefore' => 0);
$observationTextStyle = array('name' => 'Arial', 'size' => 10); 

// --- INICIO DE CAMBIOS PARA "CAMBIO DE VINCULACIÓN" ---

// 1. Identificar casos de "Cambio de vinculación"
$cambio_vinculacion_cedulas = []; 
$cambio_vinculacion_data = []; 

$sql_cambio_vinculacion = "
    SELECT 
        T1.cedula, T1.novedad AS novedad_eliminar, T1.tipo_docente AS tipo_docente_eliminar, 
        T1.tipo_dedicacion AS dedicacion_eliminar, T1.tipo_dedicacion_r AS dedicacion_r_eliminar,
        T1.horas AS horas_eliminar, T1.horas_r AS horas_r_eliminar,
        T1.sede AS sede_eliminar, T1.nombre AS nombre_eliminar, T1.id_solicitud AS id_solicitud_eliminar,
           T2.novedad AS novedad_adicionar, T2.tipo_docente AS tipo_docente_adicionar, 
           T2.tipo_dedicacion AS dedicacion_adicionar, T2.tipo_dedicacion_r AS dedicacion_r_adicionar,
           T2.horas AS horas_adicionar, T2.horas_r AS horas_r_adicionar,
           T2.sede AS sede_adicionar, T2.nombre AS nombre_adicionar, T2.id_solicitud AS id_solicitud_adicionar, 
           T2.anexa_hv_docente_nuevo, T2.actualiza_hv_antiguo, T2.s_observacion AS observacion_adicionar
    FROM solicitudes_working_copy T1
    JOIN solicitudes_working_copy T2 ON T1.cedula = T2.cedula
    WHERE T1.departamento_id = '$departamento_id'
      AND T1.anio_semestre = '$anio_semestre'
      AND T2.departamento_id = '$departamento_id'
      AND T2.anio_semestre = '$anio_semestre'
      AND T1.novedad = 'Eliminar'
      AND T2.novedad = 'adicionar'
      AND T1.estado_depto = 'PENDIENTE'
      AND T2.estado_depto = 'PENDIENTE'
    ORDER BY T1.nombre ASC
";

$result_cambio_vinculacion = $con->query($sql_cambio_vinculacion);

if (!$result_cambio_vinculacion) {
    die('Error en la consulta de cambio de vinculación: ' . $con->error);
}

while ($row_cambio = $result_cambio_vinculacion->fetch_assoc()) {
    $cedula = $row_cambio['cedula'];
    $cambio_vinculacion_cedulas[] = $cedula; 
    $cambio_vinculacion_data[] = $row_cambio; 
}

// Convertir el array de cédulas a una cadena para usar en el SQL IN clause
$cedulas_excluir_str = '';
if (!empty($cambio_vinculacion_cedulas)) {
    $cedulas_excluir_str = "'" . implode("','", $cambio_vinculacion_cedulas) . "'";
}

// 2. Sección para "Cambio de vinculación" si hay casos
if (!empty($cambio_vinculacion_data)) {
    $section->addTextBreak(1); 
    $section->addText('Novedad: Modificación - Cambio de Vinculación', $fontStyleb, $paragraphStyleb);
    $iteracion++;

    foreach ($cambio_vinculacion_data as $cambio_row) {
        $nombre_profesor = mb_strtoupper($cambio_row['nombre_adicionar'], 'UTF-8');
        $cedula_profesor = utf8_decode($cambio_row['cedula']);
        $tipo_docente_eliminar = utf8_decode($cambio_row['tipo_docente_eliminar']);
        $sede_eliminar = utf8_decode($cambio_row['sede_eliminar']);
        $observacion_adicionar = utf8_decode($cambio_row['observacion_adicionar']);

        // Construir la cadena de "Sale de..."
        $salida_info = [];
        if ($tipo_docente_eliminar == "Ocasional") {
            if (!empty($cambio_row['dedicacion_eliminar'])) {
                $salida_info[] = $cambio_row['dedicacion_eliminar'] . ' (Popayán)';
            }
            if (!empty($cambio_row['dedicacion_r_eliminar'])) {
                $salida_info[] = $cambio_row['dedicacion_r_eliminar'] . ' (Regionalización)';
            }
        } elseif ($tipo_docente_eliminar == "Catedra") {
            if (isset($cambio_row['horas_eliminar']) && (float)$cambio_row['horas_eliminar'] > 0) {
                $salida_info[] = $cambio_row['horas_eliminar'] . 'hr (Popayán)';
            }
            if (isset($cambio_row['horas_r_eliminar']) && (float)$cambio_row['horas_r_eliminar'] > 0) {
                $salida_info[] = $cambio_row['horas_r_eliminar'] . 'hr (Regionalización)';
            }
        }
        
       // --- INICIO DE LA MODIFICACIÓN (PASO 1) ---
        $texto_profesor_arriba = "Profesor: {$nombre_profesor}";

        $texto_cambio_dentro = "Cambia de: " . ($tipo_docente_eliminar ?: 'N/A');
        if (!empty($salida_info)) {
            $texto_cambio_dentro .= " - " . implode(" y ", $salida_info);
        }
        $texto_cambio_dentro = "($texto_cambio_dentro)"; 
        // --- FIN DE LA MODIFICACIÓN (PASO 1) ---
        
        $section->addText($texto_profesor_arriba, $observationTextStyle, $paragraphStyleLeft);
        $section->addTextBreak(0); 

        // Tabla de "Adicionar" para este profesor
        $styleTable = array(
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 60,
        );
        $phpWord->addTableStyle('CambioVinculacionTable', $styleTable);
        $table = $section->addTable('CambioVinculacionTable');
        $table->setWidth(100 * 50, \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT);

        // Encabezados de la tabla 
        $row_header = $table->addRow();
        $textrun_header = $row_header->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
        $textrun_header->addText('N', $cellTextStyle);
        $textrun_header->addText('o', array_merge($cellTextStyle, array('superScript' => true)));
        $row_header->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);
        $row_header->addCell(3600, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
        $row_header->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyle, $paragraphStyle);
        $row_header->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyle, $paragraphStyle);
        // Encabezado de la tabla de "Cambio de Vinculación" 
        $row_header->addCell(2200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Observación', $cellTextStyle, $paragraphStyle);
        
        $row_subheader = $table->addRow();
        $row_subheader->addCell(400, array('vMerge' => 'continue'));
        $row_subheader->addCell(1200, array('vMerge' => 'continue'));
        $row_subheader->addCell(3600, array('vMerge' => 'continue'));
        $row_subheader->addCell(350, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
        $row_subheader->addCell(350, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);
        $row_subheader->addCell(350, $headerCellStyle)->addText('Nuevo', $cellTextStyleb, $paragraphStyle);
        $row_subheader->addCell(350, $headerCellStyle)->addText('Antig', $cellTextStyleb, $paragraphStyle);
        $row_subheader->addCell(1000, array('vMerge' => 'continue'));

        // Fila de datos para este profesor
        $table->addRow();
        $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(1, $cellTextStyle, $paragraphStyle); 
        $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cedula_profesor, $cellTextStyle, $paragraphStyle);
        $table->addCell(3600, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($nombre_profesor, $cellTextStyle, $paragraphStyle);

        if ($cambio_row['tipo_docente_adicionar'] == "Ocasional") {
            // --- LÓGICA PARA LA DEDICACIÓN EN POPAYÁN ---
            $dedicacion_popayan = strtoupper(trim($cambio_row['dedicacion_adicionar'] ?: ''));
            $display_popayan = ''; 
            if ($dedicacion_popayan === 'MT') {
                $display_popayan = 'OMT';
            } elseif ($dedicacion_popayan === 'TC') {
                $display_popayan = 'OTC';
            } elseif (!empty($dedicacion_popayan)) {
                $display_popayan = 'O'; 
            }
            
            // --- LÓGICA PARA LA DEDICACIÓN EN REGIONALIZACIÓN ---
            $dedicacion_regional = strtoupper(trim($cambio_row['dedicacion_r_adicionar'] ?: ''));
            $display_regional = ''; 
            if ($dedicacion_regional === 'MT') {
                $display_regional = 'OMT';
            } elseif ($dedicacion_regional === 'TC') {
                $display_regional = 'OTC';
            } elseif (!empty($dedicacion_regional)) {
                $display_regional = 'O';
            }

            // Añadir las celdas con los nuevos valores formateados
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($display_popayan), $cellTextStyle, $paragraphStyle);
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($display_regional), $cellTextStyle, $paragraphStyle);
        }
        elseif ($cambio_row['tipo_docente_adicionar'] == "Catedra") {
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($cambio_row['horas_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($cambio_row['horas_r_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
        } else {
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
        }

        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(mb_strtoupper($cambio_row['anexa_hv_docente_nuevo'] ?: '', 'UTF-8'), $cellTextStyle, $paragraphStyle);
        
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(mb_strtoupper($cambio_row['actualiza_hv_antiguo'] ?: '', 'UTF-8'), $cellTextStyle, $paragraphStyle);
        
        // --- INICIO DE LA MODIFICACIÓN (PASO 3) ---
        // Combinamos la observación existente con la nueva descripción del cambio
        $observacion_existente = utf8_decode($cambio_row['observacion_adicionar'] ?: '');
        $observacion_final = $observacion_existente;
        if (!empty($observacion_existente)) {
            $observacion_final .= ' '; 
        }
        $observacion_final .= $texto_cambio_dentro;

        // Añadimos la celda de Observación 
        $cell = $table->addCell(2200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0]);
        $textrun = $cell->addTextRun($paragraphStyle);

        // Si hay una observación original, la añadimos en texto normal
        if (!empty($observacion_existente)) {
            $textrun->addText(htmlspecialchars($observacion_existente) . ' ', $cellTextStyle);
        }
        // Añadimos la descripción del cambio en itálica para diferenciarla
        $textrun->addText(htmlspecialchars($texto_cambio_dentro), ['italic' => true, 'size' => 9]);
        // --- FIN DE LA MODIFICACIÓN (PASO 3) ---
        
        $section->addTextBreak(0); 

        // Actualizar el estado de ambas solicitudes a 'ENVIADO'
        $id_solicitud_adicionar = $cambio_row['id_solicitud_adicionar'];
        // PARA CAMBIO DE VINCULACIÓN: SI ES ADICIÓN, GUARDAMOS EL ACTA FOR-59
        // 1. Limpieza de datos
        $novedad_actual_add = 'adicionar'; 
        // 2. Manejo seguro de la fecha
        $fecha_acta_sql = empty($fecha_acta) ? "NULL" : "'$fecha_acta'";
        
        $sql_set_add = "estado_depto = 'ENVIADO', 
                        oficio_depto = '$num_oficio', 
                        fecha_oficio_depto = '$fecha_oficio', 
                        oficio_con_fecha = '$oficio_con_fecha_depto',
                        numero_acta59 = '$num_acta', 
                        fecha_acta59 = $fecha_acta_sql"; // SIEMPRE guardar acta en la parte de adición

        $sql_update_adicionar = "UPDATE solicitudes_working_copy SET $sql_set_add WHERE id_solicitud = '$id_solicitud_adicionar'";
        $con->query($sql_update_adicionar); 

        $id_solicitud_eliminar = $cambio_row['id_solicitud_eliminar'];
        // En la parte de eliminar NO guardamos acta (no es necesario o ya se guardó en la adición asociada)
        $sql_update_eliminar = "
                UPDATE solicitudes_working_copy 
                SET 
                    estado_depto = 'ENVIADO',
                    oficio_depto = '$num_oficio',
                    fecha_oficio_depto = '$fecha_oficio',
                    oficio_con_fecha = '$oficio_con_fecha_depto'
                WHERE 
                    id_solicitud = '$id_solicitud_eliminar'
            ";
        $con->query($sql_update_eliminar); 
    }
}

// Consulta para obtener los distintos tipos de novedad
$consulta_novedad_tipos = "SELECT DISTINCT novedad
                            FROM solicitudes_working_copy
                            WHERE departamento_id = '$departamento_id'
                            AND anio_semestre = '$anio_semestre'
                            AND novedad IS NOT NULL AND novedad != ''
                            AND estado_depto = 'PENDIENTE'";
                            
if (!empty($cedulas_excluir_str)) {
    $consulta_novedad_tipos .= " AND cedula NOT IN ($cedulas_excluir_str)";
}
$consulta_novedad_tipos .= " ORDER BY CASE WHEN novedad = 'Modificar' THEN 1 WHEN novedad = 'adicionar' THEN 2 ELSE 3 END";


$resultado_novedad_tipos = $con->query($consulta_novedad_tipos);

if (!$resultado_novedad_tipos) {
    die('Error en la consulta de novedades: ' . $con->error);
}

// Bucle principal: ahora itera por cada tipo de 'novedad'
while ($row_novedad_tipo = $resultado_novedad_tipos->fetch_assoc()) {
    $novedad_actual = $row_novedad_tipo['novedad'];

    if ($iteracion > 0) {
        $section->addTextBreak(1);
    }

    if ($novedad_actual === 'Modificar') {
        $novedad_mostrar = 'Modificación - Cambio de Dedicación';
    } else {
        $novedad_mostrar = ucfirst($novedad_actual); 
    }

    $section->addText('Novedad: ' . $novedad_mostrar, $fontStyleb, $paragraphStyleb);
    $iteracion++;

    $consultat = "SELECT solicitudes_working_copy.*,
                          facultad.nombre_fac_min AS nombre_facultad,
                          facultad.email_fac AS email_facultad,
                          deparmanentos.depto_nom_propio AS nombre_departamento
                    FROM solicitudes_working_copy
                    JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes_working_copy.departamento_id
                    JOIN facultad ON facultad.PK_FAC = solicitudes_working_copy.facultad_id
                    WHERE departamento_id = '$departamento_id'
                    AND anio_semestre = '$anio_semestre'
                    AND novedad = '$novedad_actual'
                      AND estado_depto = 'PENDIENTE'"; 

    if (!empty($cedulas_excluir_str)) {
        $consultat .= " AND cedula NOT IN ($cedulas_excluir_str)"; 
    }
                    
    $consultat .= " ORDER BY solicitudes_working_copy.nombre ASC"; 

    $resultadot = $con->query($consultat);

    if (!$resultadot) {
        die('Error en la consulta de solicitudes por novedad: ' . $con->error);
    }

    $unique_observations = []; 
    $temp_results = []; 
    $cont_for_observations = 0; 
    
    // Primero, recoger todas las observaciones y guardar los datos para la tabla
    while ($row = $resultadot->fetch_assoc()) {
        $cont_for_observations++; 
        if (!empty($row['s_observacion'])) {
            $obs_text = $row['s_observacion'];
            if (!isset($unique_observations[$obs_text])) {
                $unique_observations[$obs_text] = [];
            }
            $unique_observations[$obs_text][] = $cont_for_observations; 
        }
        $temp_results[] = $row; 
    }

    // Estilo de la tabla
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 60, 
    );

    $phpWord->addTableStyle('ColspanRowspan', $styleTable);
    $table = $section->addTable('ColspanRowspan');
    $table->setWidth(100 * 50, \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT);


    // Encabezados de la tabla - Primera fila
    $row = $table->addRow();
    
    // Nº
    $textrun = $row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
    $textrun->addText('N', $cellTextStyle);
    $textrun->addText('o', array_merge($cellTextStyle, array('superScript' => true)));
    
    // Cédula
    $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);
    
    // Nombre
    $row->addCell(3400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle); 
    
    // Dedicación/hr
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyle, $paragraphStyle);
    
    // Hoja de vida
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyle, $paragraphStyle);

    // Observación
    $row->addCell(2200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Observación', $cellTextStyle, $paragraphStyle);

    // Encabezados de la tabla - Segunda fila
    $row = $table->addRow();
    $row->addCell(400, array('vMerge' => 'continue')); 
    $row->addCell(1200, array('vMerge' => 'continue')); 
    $row->addCell(3400, array('vMerge' => 'continue')); 
    $row->addCell(350, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Nuevo', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Antig', $cellTextStyleb, $paragraphStyle);
    $row->addCell(2200, array('vMerge' => 'continue')); 

    $cont = 0; 
    // Iterar sobre los resultados almacenados temporalmente
    foreach ($temp_results as $row) {
        $cont++;
        // CORREO PRODUCCIÓN
        $facultad_email = $row['email_facultad']; 
        
        $table->addRow();
        
        // Columna Nº
        $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
        
        // Columna Cédula
        $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);
        
        // Columna Nombre
        $full_nombre = utf8_decode($row['nombre'] ?: '');
        $display_nombre_in_word = $full_nombre; 
        $table->addCell(3400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($display_nombre_in_word, $cellTextStyle, $paragraphStyle); 

        // Columnas de Dedicación/horas según el tipo de docente
        if ($row['tipo_docente'] == "Ocasional") {
            $dedicacion_popayan = strtoupper(trim($row['tipo_dedicacion'] ?: ''));
            $display_popayan = 'O'; 
            if ($dedicacion_popayan === 'MT') {
                $display_popayan = 'OMT';
            } elseif ($dedicacion_popayan === 'TC') {
                $display_popayan = 'OTC';
            }
            
            $dedicacion_regional = strtoupper(trim($row['tipo_dedicacion_r'] ?: ''));
            $display_regional = 'O'; 
            if ($dedicacion_regional === 'MT') {
                $display_regional = 'OMT';
            } elseif ($dedicacion_regional === 'TC') {
                $display_regional = 'OTC';
            }
            
            if (empty($dedicacion_popayan)) { $display_popayan = ''; }
            if (empty($dedicacion_regional)) { $display_regional = ''; }

            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($display_popayan), $cellTextStyle, $paragraphStyle);
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($display_regional), $cellTextStyle, $paragraphStyle);
        } elseif ($row['tipo_docente'] == "Catedra") {
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['horas'] ?: ''), $cellTextStyle, $paragraphStyle);
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['horas_r'] ?: ''), $cellTextStyle, $paragraphStyle);
        } else {
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
        }

        // Columnas de Hoja de Vida
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(mb_strtoupper($row['anexa_hv_docente_nuevo'] ?: '', 'UTF-8'), $cellTextStyle, $paragraphStyle);

        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(mb_strtoupper($row['actualiza_hv_antiguo'] ?: '', 'UTF-8'), $cellTextStyle, $paragraphStyle);

        // Celda de Observación
        $table->addCell(2200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(utf8_decode($row['s_observacion'] ?: ''), $cellTextStyle, $paragraphStyle);
        
        
        // --- INICIO: Actualización Segura con Acta (Solo para Adiciones Puras) ---
        $id_solicitud_actual = $row['id_solicitud'];
        
        // 1. Limpieza de datos
        $novedad_actual_row = strtolower(trim($row['novedad'] ?? ''));
        
        // 2. Manejo seguro de la fecha
        $fecha_acta_sql = empty($fecha_acta) ? "NULL" : "'$fecha_acta'";
        
        // 3. Construimos la parte BASE de la consulta
        $sql_set = "estado_depto = 'ENVIADO', 
                    oficio_depto = '$num_oficio', 
                    fecha_oficio_depto = '$fecha_oficio', 
                    oficio_con_fecha = '$oficio_con_fecha_depto'";

        // 4. CONDICIÓN: Si es 'adicionar', agregamos los campos del acta
        if ($novedad_actual_row == 'adicionar' || $novedad_actual_row == 'adicion') {
            $sql_set .= ", numero_acta59 = '$num_acta', fecha_acta59 = $fecha_acta_sql";
        }

        // 5. Ejecutamos
        $sql_update_solicitudes_estado = "UPDATE solicitudes_working_copy SET $sql_set WHERE id_solicitud = '$id_solicitud_actual'";
        $con->query($sql_update_solicitudes_estado);
        // --- FIN: Actualización Segura ---
    }
}
$fontStyleSmall = array('name' => 'Arial', 'size' => 7, 'italic' => true);
$paragraphStyleSmall = array('spaceBefore' => 0, 'spaceAfter' => 0);

$section->addText(
    'Dedic/hr=Dedicación (Ocasional) u Horas(Cátedra), nuevo=Anexa Hoja de vida, Antig = Actualiza Hoja de vida Antiguo, OTC = Ocasional Tiempo Completo, OMT = Ocasional Medio Tiempo', 
    $fontStyleSmall, 
    $paragraphStyleSmall
);
    $section->addTextBreak(); 

// Pie de página
$section->addText('Universitariamente, ',$fontStylecuerpo);

    $section->addTextBreak(); 
$section->addText(mb_strtoupper($elaboro, 'UTF-8'), $fontStylecuerpo,$paragraphStylexz);
$section->addText('Jefe de Departamento de ' . ($nombre_depto), $fontStylecuerpo);
// Definir estilo de fuente para texto en cursiva con tamaño 8
$fontStyle = array('italic' => true, 'size' => 8);
$paragraphStyle = array('indentation' => array('left' => 720)); 

// Agregar el texto inicial con estilo de fuente definido, sin sangría
if ($folios == 1) {
    $section->addText('Anexo: (' . $folios . ') folio', $fontStyle);
} else if ($folios > 1) {
    $section->addText('Anexo: (' . $folios . ') folios', $fontStyle);
} else {
    $section->addText('Anexo: (  ) folio(s)', $fontStyle);
}

$consultacant = "
    SELECT COUNT(*) as cant_profesores
    FROM solicitudes_working_copy 
    WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes_working_copy.estado <> 'an' OR solicitudes_working_copy.estado IS NULL) and solicitudes_working_copy.novedad = 'adicionar'
";

$resultadocant = $con->query($consultacant);

if (!$resultadocant) {
    die('Error en la consulta: ' . $con->error);
}

// Obtener el único resultado de la consulta
$rowcant = $resultadocant->fetch_assoc();
$cant_profesores = $rowcant['cant_profesores'];
    
$consultaanexo = "SELECT solicitudes_working_copy.*, facultad.nombre_fac_min AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
                    FROM solicitudes_working_copy 
                    JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes_working_copy.departamento_id
                    JOIN facultad ON facultad.PK_FAC = solicitudes_working_copy.facultad_id
                    WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes_working_copy.anexa_hv_docente_nuevo = 'si' OR solicitudes_working_copy.actualiza_hv_antiguo = 'si')  AND solicitudes_working_copy.novedad ='adicionar'  AND solicitudes_working_copy.estado_depto= 'PENDIENTE' ";

// Excluir de nuevo las cédulas que ya se manejaron
if (!empty($cedulas_excluir_str)) {
    $consultaanexo .= " AND cedula NOT IN ($cedulas_excluir_str)";
}


$resultadoanexo = $con->query($consultaanexo);

if (!$resultadoanexo) {
    die('Error en la consulta: ' . $con->error);
}

while ($rowanexo = $resultadoanexo->fetch_assoc()) { 
    $nombre = ucwords(strtolower($rowanexo['nombre']));

    if ($rowanexo['anexa_hv_docente_nuevo'] == 'si') {
        $section->addText('Hoja de Vida de ' . $nombre . ' con su respectiva lista de chequeo', $fontStyle, ['spaceAfter' => 0]);
    }

    if ($rowanexo['actualiza_hv_antiguo'] == 'si') {
        $section->addText('Actualización Hoja de Vida de ' . $nombre . ' con su respectiva lista de chequeo', $fontStyle, ['spaceAfter' => 0]);
    }
}
if ($acta !== 'Acta no especificada') {
    $section->addText('Formato PM-FO-4-FOR-59. Acta de Selección: ' . $acta, $fontStyle, array('spaceAfter' => 0));
}
$section->addText('('.$cant_profesores.') Formatos PA-GA-5.1-FOR 45 Revisión Requisitos Vinculación Docente', $fontStyle, array('spaceAfter' => 0));

$footer = $section->addFooter();
$footer->addImage($imgpie, array(
    'width' => 490, 
    'marginTop' => 0, 
    'marginRight' => 1000, 
));

// Encabezados HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="document.docx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');

// Configuración de PHPMailer para el envío de correo
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host         = 'smtp.gmail.com';
    $mail->SMTPAuth     = true;
    $mail->Username     =$config['smtp_username']; 
    $mail->Password     = $config['smtp_password']; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port         = 587;

    $mail->SMTPSoptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    // Configurar destinatarios
    $mail->setFrom('ejurado@unicauca.edu.co', 'solicitudes vinculación');
    $mail->addAddress($facultad_email, 'Destinatario');
    $mail->addCC('ejurado@unicauca.edu.co'); 

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';    
    $mail->Subject = 'Notificación: solicitud de Novedades de vinculación temporales - ' . $nombre_depto;
    $mail->Body    = "
        <p>Cordial saludo, </p>
        <p>Se ha generado una solicitud Novedad de vinculación de profesores temporales desde el departamento <strong>{$nombre_depto} para el periodo {$anio_semestre}</strong>.</p>
        <p>Por favor, revise la plataforma solicitudes de vinculación, http://192.168.42.175/temporales/ para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
        <p>Universitariamente,</p>
        <p><strong>Vicerrectoría Académica</strong></p>
    ";
    
    $mail->send();
  
} catch (Exception $e) {
  // Manejo de errores
}

exit; 
?>