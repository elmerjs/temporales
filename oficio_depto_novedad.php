<?php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos
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

// Estilos para la celda del encabezado de la tabla
$headerCellStyle = array('bgColor' => '#f2f2f2');

// Crear una nueva instancia de PhpWord
$phpWord = new PhpWord();
// Definir dimensiones de una página tamaño carta en twips
$pageWidth = 12240; // 21.59 cm (8.5 pulgadas) en twips
$pageHeight = 15840; // 27.94 cm (11 pulgadas) en twips

$section = $phpWord->addSection(array(
    'pageSizeW' => $pageWidth, 
    'pageSizeH' => $pageHeight,
    'marginLeft' => 1700,    // 3 cm a la izquierda (en twips)
    'marginRight' => 1700,  // 3 cm a la derecha (en twips)
));
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));
// Agregar el encabezado

// Obtener los parámetros de la URL
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$num_oficio = $_GET['num_oficio'];
$elaboro = $_GET['elaboro'];
$nombre_fac = $_GET['nombre_fac'];
$num_acta = $_GET['acta'] ?? '';
$fecha_acta = $_GET['fecha_acta'] ?? '';

// Formatear la fecha en el formato "día de mes de año"
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');

$fecha_acta_b = '';
if (!empty($fecha_acta)) {
    // Si la fecha no está vacía, intentar formatearla
    $timestamp = strtotime($fecha_acta);
    if ($timestamp !== false) {
        $fecha_acta_b = strftime('%d de %B de %Y', $timestamp);
    }
}

// Unir el número de acta con la fecha formateada
if (empty($num_acta) && empty($fecha_acta_b)) {
    $acta = 'Acta no especificada'; // O un valor vacío si prefieres que no se muestre nada
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
 // Función para obtener el nombre del departamento
    

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
    //'width' => 460, // Incrementar el ancho en un 10%
    'height' => 80, // Incrementar el alto en un 10%
    'marginTop' => -284, // Subir la imagen para compensar el esacio de margen superior de 1 cm
   // 'marginRight' => 1700, // Mover la imagen 3 cm más a la derecha (3 cm * 567 twips/cm)
         'align' => 'left', // Alinear a la derecha

));

$paragraphStylexz = array('lineHeight' => 0.8, 'spaceAfter' => 0, 'spaceBefore' => 0);

$fontStylecuerpo = array('name' => 'Arial', 'size' => 11);

$section->addText($encabezado_oficio, array('size' => 11, 'bold' => false, 'name' => 'Arial'),$paragraphStylexz);


// Obtener la fecha actual en español

$fecha_actual = strftime('%d de %B de %Y', strtotime($fecha_oficio));
$section->addText('Popayán, ' . $fecha_actual,$fontStylecuerpo,$paragraphStylexz);
$saltoLineaStyle = array('lineHeight' => 0.8); // Estilo personalizado para el salto de línea

$section->addTextBreak(1, $paragraphStylexz);

 // Agregar textos sin espacio entre ellos
$section->addText('Decano', $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));
$decano = mb_strtoupper($decano, 'UTF-8');
$section->addText($decano, $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));

$section->addText('Presidente Consejo de Facultad de ' . $nombre_fac, $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));
$section->addText('Universidad del Cauca', $fontStylecuerpo, array('spaceBefore' => 0, 'spaceAfter' => 0));

$section->addTextBreak(); // Inserta un salto de línea
$section->addText('Cordial saludo,',$fontStylecuerpo);

$styleParagraph = array('align' => 'both'); // Define el estilo para justificar el texto
$nombre_depto= obtenerNombreDepartamento($departamento_id);
    $section->addText(
        'Asunto: Solicitud Novedad(es) de vinculación Departamento de '.obtenerNombreDepartamento($departamento_id) . ' periodo '.$anio_semestre.'.',
        $fontStylecuerpo,
        $styleParagraph
    );

    
// Escapar las variables para prevenir inyecciones SQL
$departamento_id = $con->real_escape_string($departamento_id);
$anio_semestre = $con->real_escape_string($anio_semestre);
date_default_timezone_set('America/Bogota'); // Configurar la zona horaria de Colombia

$fecha_hora_envio = date('Y-m-d H:i:s');

// Realizar el SELECT para verificar el estado actual de dp_acepta_fac
$sql_select = "SELECT dp_acepta_fac 
                FROM depto_periodo 
                WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";

$result = $con->query($sql_select);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
$acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);

// Inicializamos la variable $sql_update_fac con un valor vacío
$sql_update_fac = '';


} else {
  //  echo "No se encontró el registro en depto_periodo";
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

$nom_depto = ""; // Inicializar la variable para almacenar el nombre del departamento
$paragraphStylec = array('spaceAfter' => 1);

while ($rowdepto = $resultadodepto->fetch_assoc()) {
$nom_depto = mb_strtoupper($rowdepto['depto_nom_propio'], 'UTF-8');
}
$nom_depto = mb_strtoupper($nombre_depto, 'UTF-8');

$section->addText('Departamento de ' . $nombre_depto, 
    array('bold' => true), 
    $paragraphStylec
);
// Negrilla para el texto del departamento

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
$iteracion = 0; // Inicializar un contador de iteraciones
$paragraphStyleb = array('spaceBefore' => 50, 'spaceAfter' => 10, 'lineHeight' => 1);
$fontStyleb = array('name' => 'Arial', 'size' => 10);
$cellTextStyle = array('name' => 'Arial', 'size' => 9);
$headerCellStyle = array('bgColor' => 'F2F2F2', 'valign' => VerticalJc::CENTER);
$paragraphStyle = array('alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0);

// NUEVO: Estilos para el texto de las observaciones
$paragraphStyleLeft = array('alignment' => Jc::LEFT, 'spaceAfter' => 0, 'spaceBefore' => 0);
$observationTextStyle = array('name' => 'Arial', 'size' => 10); // Puedes ajustar el estilo

// --- INICIO DE CAMBIOS PARA "CAMBIO DE VINCULACIÓN" ---

// 1. Identificar casos de "Cambio de vinculación"
$cambio_vinculacion_cedulas = []; // Para almacenar las cédulas de los profesores con cambio de vinculación
$cambio_vinculacion_data = []; // Para almacenar todos los datos de sus solicitudes

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
    $cambio_vinculacion_cedulas[] = $cedula; // Almacenar las cédulas para exclusión futura
    $cambio_vinculacion_data[] = $row_cambio; // Almacenar todos los datos
}

// Convertir el array de cédulas a una cadena para usar en el SQL IN clause
$cedulas_excluir_str = '';
if (!empty($cambio_vinculacion_cedulas)) {
    $cedulas_excluir_str = "'" . implode("','", $cambio_vinculacion_cedulas) . "'";
}

// 2. Sección para "Cambio de vinculación" si hay casos
if (!empty($cambio_vinculacion_data)) {
    $section->addTextBreak(1); // Espacio antes de esta nueva sección
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
            // **CAMBIO AQUÍ: Solo incluir si las horas son mayores a cero**
            if (isset($cambio_row['horas_eliminar']) && (float)$cambio_row['horas_eliminar'] > 0) {
                $salida_info[] = $cambio_row['horas_eliminar'] . 'hr (Popayán)';
            }
            if (isset($cambio_row['horas_r_eliminar']) && (float)$cambio_row['horas_r_eliminar'] > 0) {
                $salida_info[] = $cambio_row['horas_r_eliminar'] . 'hr (Regionalización)';
            }
        }
        
       // --- INICIO DE LA MODIFICACIÓN (PASO 1) ---
        // Texto que irá ARRIBA de la tabla (solo el nombre)
        $texto_profesor_arriba = "Profesor: {$nombre_profesor}";

        // Texto que irá DENTRO de la tabla (la descripción del cambio)
        $texto_cambio_dentro = "Cambia de: " . ($tipo_docente_eliminar ?: 'N/A');
        if (!empty($salida_info)) {
            $texto_cambio_dentro .= " - " . implode(" y ", $salida_info);
        }
        $texto_cambio_dentro = "($texto_cambio_dentro)"; // Lo encerramos en paréntesis
        // --- FIN DE LA MODIFICACIÓN (PASO 1) ---
        
$section->addText($texto_profesor_arriba, $observationTextStyle, $paragraphStyleLeft);
        /*if (!empty($observacion_adicionar)) {
            $section->addText('Observación: ' . $observacion_adicionar, $observationTextStyle, $paragraphStyleLeft);
        }*/
        $section->addTextBreak(0); // Pequeño espacio antes de la tabla de adición

        // Tabla de "Adicionar" para este profesor
        $styleTable = array(
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 60,
        );
        $phpWord->addTableStyle('CambioVinculacionTable', $styleTable);
        $table = $section->addTable('CambioVinculacionTable');
        $table->setWidth(100 * 50, \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT);

        // Encabezados de la tabla (igual que la de adicionar)
        $row_header = $table->addRow();
        $textrun_header = $row_header->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
        $textrun_header->addText('N', $cellTextStyle);
        $textrun_header->addText('o', array_merge($cellTextStyle, array('superScript' => true)));
        $row_header->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);
        $row_header->addCell(3600, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
        $row_header->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyle, $paragraphStyle);
        $row_header->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyle, $paragraphStyle);
        //$row_header->addCell(1000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Tipo Docente', $cellTextStyle, $paragraphStyle);
// --- CAMBIO 1: Encabezado de la tabla de "Cambio de Vinculación" ---
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
        $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(1, $cellTextStyle, $paragraphStyle); // Siempre 1 para esta tabla específica
        $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cedula_profesor, $cellTextStyle, $paragraphStyle);
        $table->addCell(3600, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($nombre_profesor, $cellTextStyle, $paragraphStyle);

if ($cambio_row['tipo_docente_adicionar'] == "Ocasional") {
    // --- LÓGICA PARA LA DEDICACIÓN EN POPAYÁN ---
    $dedicacion_popayan = strtoupper(trim($cambio_row['dedicacion_adicionar'] ?: ''));
    $display_popayan = ''; // Por defecto es vacío
    if ($dedicacion_popayan === 'MT') {
        $display_popayan = 'OMT';
    } elseif ($dedicacion_popayan === 'TC') {
        $display_popayan = 'OTC';
    } elseif (!empty($dedicacion_popayan)) {
        $display_popayan = 'O'; // Si hay algo, pero no es MT o TC
    }
    
    // --- LÓGICA PARA LA DEDICACIÓN EN REGIONALIZACIÓN ---
    $dedicacion_regional = strtoupper(trim($cambio_row['dedicacion_r_adicionar'] ?: ''));
    $display_regional = ''; // Por defecto es vacío
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
       /* $table->addCell(1000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(utf8_decode($cambio_row['tipo_docente_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);*/
        // --- CAMBIO 2: Celda de datos en la tabla de "Cambio de Vinculación" ---
// --- INICIO DE LA MODIFICACIÓN (PASO 3) ---
// Combinamos la observación existente con la nueva descripción del cambio
$observacion_existente = utf8_decode($cambio_row['observacion_adicionar'] ?: '');
$observacion_final = $observacion_existente;
if (!empty($observacion_existente)) {
    $observacion_final .= ' '; // Añadir espacio si hay texto
}
$observacion_final .= $texto_cambio_dentro;

// Añadimos la celda de Observación con el texto combinado y con estilos
$cell = $table->addCell(2200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0]);
$textrun = $cell->addTextRun($paragraphStyle);

// Si hay una observación original, la añadimos en texto normal
if (!empty($observacion_existente)) {
    $textrun->addText(htmlspecialchars($observacion_existente) . ' ', $cellTextStyle);
}
// Añadimos la descripción del cambio en itálica para diferenciarla
$textrun->addText(htmlspecialchars($texto_cambio_dentro), ['italic' => true, 'size' => 9]);
// --- FIN DE LA MODIFICACIÓN (PASO 3) ---
        $section->addTextBreak(0); // Espacio entre cada profesor de cambio de vinculación

        // Actualizar el estado de ambas solicitudes a 'ENVIADO'
        $id_solicitud_adicionar = $cambio_row['id_solicitud_adicionar'];
        $sql_update_adicionar = "
                UPDATE solicitudes_working_copy 
                SET 
                    estado_depto = 'ENVIADO',
                    oficio_depto = '$num_oficio',
                    fecha_oficio_depto = '$fecha_oficio',
                    oficio_con_fecha = '$oficio_con_fecha_depto'
                WHERE 
                    id_solicitud = '$id_solicitud_adicionar'
            ";
        $con->query($sql_update_adicionar); // Ejecutar actualización para la novedad "adicionar"

        $id_solicitud_eliminar = $cambio_row['id_solicitud_eliminar'];
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
        $con->query($sql_update_eliminar); // Ejecutar actualización para la novedad "eliminar"
    }
}

// Consulta para obtener los distintos tipos de novedad
// Ahora excluye las cédulas que ya se manejaron en "Cambio de vinculación"
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

// $iteracion = 0; // Para el addTextBreak entre bloques de novedad
// Mantén el $iteracion que ya tenías para la primera sección.

// Bucle principal: ahora itera por cada tipo de 'novedad'
while ($row_novedad_tipo = $resultado_novedad_tipos->fetch_assoc()) {
    $novedad_actual = $row_novedad_tipo['novedad'];

    // Si no es la primera iteración, añadir un salto de línea para separar las tablas
    if ($iteracion > 0) {
        $section->addTextBreak(1);
    }

    if ($novedad_actual === 'Modificar') {
    $novedad_mostrar = 'Modificación - Cambio de Dedicación';
} else {
    $novedad_mostrar = ucfirst($novedad_actual); // Primera letra en mayúscula
}

$section->addText('Novedad: ' . $novedad_mostrar, $fontStyleb, $paragraphStyleb);
    $iteracion++;

    // Consulta para obtener las solicitudes para la novedad actual
    // Incluye 's_observacion' para poder extraerlo antes de la tabla
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
                     AND estado_depto = 'PENDIENTE'"; // Mantener solo pendientes aquí

    if (!empty($cedulas_excluir_str)) {
        $consultat .= " AND cedula NOT IN ($cedulas_excluir_str)"; // Excluir casos de "Cambio de Vinculación"
    }
                   
    $consultat .= " ORDER BY solicitudes_working_copy.nombre ASC"; // Ordenar por nombre del profesor

    $resultadot = $con->query($consultat);

    if (!$resultadot) {
        die('Error en la consulta de solicitudes por novedad: ' . $con->error);
    }

    $unique_observations = []; 
    $temp_results = []; 
    $cont_for_observations = 0; 
    
    // Primero, recoger todas las observaciones y guardar los datos para la tabla
    while ($row = $resultadot->fetch_assoc()) {
        $cont_for_observations++; // Incrementa el contador para el número de ítem
        if (!empty($row['s_observacion'])) {
            $obs_text = $row['s_observacion'];
            if (!isset($unique_observations[$obs_text])) {
                $unique_observations[$obs_text] = [];
            }
            $unique_observations[$obs_text][] = $cont_for_observations; // Almacena el número de ítem
        }
        $temp_results[] = $row; // Guardar la fila para la segunda iteración (llenado de tabla)
    }

    // Mostrar las observaciones como un párrafo concatenado si existen
   /* if (!empty($unique_observations)) {
        $observation_text_output = '';
        $first_obs_output = true;
        foreach ($unique_observations as $obs_content => $indices_array) {
            if (!$first_obs_output) {
                $observation_text_output .= ' '; // Espacio entre observaciones distintas
            }
            // Formatear los índices: (1), (1) y (2), (1) (2) y (3)
            $formatted_indices = '';
            if (count($indices_array) > 1) {
                $last_index = array_pop($indices_array);
                $formatted_indices = '(' . implode(') (', $indices_array) . ') y (' . $last_index . ')';
            } else {
                $formatted_indices = '(' . $indices_array[0] . ')';
            }
            $observation_text_output .= $formatted_indices . ' ' . utf8_decode($obs_content);
            $first_obs_output = false;
        }
        $section->addText($observation_text_output, $observationTextStyle, $paragraphStyleLeft);
        $section->addTextBreak(0); // Añadir un salto de línea después de las observaciones y antes de la tabla
    }
*/
    // Estilo de la tabla
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 60, // Margen interno de las celdas (en unidades twips)
    );

    $phpWord->addTableStyle('ColspanRowspan', $styleTable);
    $table = $section->addTable('ColspanRowspan');
    // Mantiene el ancho de la tabla como estaba en tu código original
    $table->setWidth(100 * 50, \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT);


    // Encabezados de la tabla - Primera fila (¡Sin Observación!)
    $row = $table->addRow();
    
    // Nº
    $textrun = $row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
    $textrun->addText('N', $cellTextStyle);
    $textrun->addText('o', array_merge($cellTextStyle, array('superScript' => true)));
    
    // Cédula
    $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);
    
    // Nombre (ancho ajustado para compensar la eliminación de la columna Observación)
    $row->addCell(3400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle); // Aumentado el ancho del nombre
    
    // Dedicación/hr
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyle, $paragraphStyle);
    
    // Hoja de vida
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyle, $paragraphStyle);

    // Tipo Docente
  //  $row->addCell(1000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Tipo Docente', $cellTextStyle, $paragraphStyle);
    // --- CAMBIO 3: Encabezado de las tablas de novedades normales ---
$row->addCell(2200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Observación', $cellTextStyle, $paragraphStyle);

    // Encabezados de la tabla - Segunda fila (para celdas fusionadas, ¡Sin Observación!)
    $row = $table->addRow();
    // Celdas 'continue' para los vMerge 'restart' de la fila anterior
    $row->addCell(400, array('vMerge' => 'continue')); // Nº
    $row->addCell(1200, array('vMerge' => 'continue')); // Cédula
    $row->addCell(3400, array('vMerge' => 'continue')); // Nombre (ancho ajustado)
    
    // Sub-encabezados de Dedicación/hr
    $row->addCell(350, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);
    
    // Sub-encabezados de Hoja de vida
    $row->addCell(350, $headerCellStyle)->addText('Nuevo', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Antig', $cellTextStyleb, $paragraphStyle);

    // Celdas 'continue' para Tipo Docente
   // $row->addCell(1000, array('vMerge' => 'continue')); // Tipo Docente

$row->addCell(2200, array('vMerge' => 'continue')); // Celda 'continue' para Observación

    $cont = 0; // Contador de filas dentro de cada tabla de novedad
    // Ahora iteramos sobre los resultados almacenados temporalmente
    foreach ($temp_results as $row) {
        $cont++;
        // Asumiendo que 'facultad_id' y 'email_facultad' son necesarios para otras partes del oficio.
        // Si no, se pueden eliminar.
        $facultad_id = $row['facultad_id'];
        //$facultad_email = $row['email_facultad']; // Usar el de la BD si existe, sino el fallback
        
        $facultad_email = 'elmerjs@unicauca.edu.co';
        $table->addRow();
        
        // Columna Nº
        $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
        
        // Columna Cédula
        $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);
        
        // Columna Nombre
        $full_nombre = utf8_decode($row['nombre'] ?: '');
        $display_nombre_in_word = $full_nombre; 
        $table->addCell(3400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($display_nombre_in_word, $cellTextStyle, $paragraphStyle); // Ancho ajustado

        // Columnas de Dedicación/horas según el tipo de docente
        // Columnas de Dedicación/horas según el tipo de docente
if ($row['tipo_docente'] == "Ocasional") {
    // --- LÓGICA PARA LA DEDICACIÓN EN POPAYÁN ---
    $dedicacion_popayan = strtoupper(trim($row['tipo_dedicacion'] ?: ''));
    $display_popayan = 'O'; // Valor por defecto
    if ($dedicacion_popayan === 'MT') {
        $display_popayan = 'OMT';
    } elseif ($dedicacion_popayan === 'TC') {
        $display_popayan = 'OTC';
    }
    
    // --- LÓGICA PARA LA DEDICACIÓN EN REGIONALIZACIÓN ---
    $dedicacion_regional = strtoupper(trim($row['tipo_dedicacion_r'] ?: ''));
    $display_regional = 'O'; // Valor por defecto
    if ($dedicacion_regional === 'MT') {
        $display_regional = 'OMT';
    } elseif ($dedicacion_regional === 'TC') {
        $display_regional = 'OTC';
    }
    
    // Si la dedicación original estaba vacía, mostramos la celda vacía para no poner una "O" solitaria.
    if (empty($dedicacion_popayan)) {
        $display_popayan = '';
    }
    if (empty($dedicacion_regional)) {
        $display_regional = '';
    }

    // Añadir las celdas con los nuevos valores formateados
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

        // Columna Tipo Docente
        /*$table->addCell(1000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
            ->addText(utf8_decode($row['tipo_docente'] ?: ''), $cellTextStyle, $paragraphStyle); 
        */
        
        // --- CAMBIO 4: Celda de datos en las tablas de novedades normales ---
        $table->addCell(2200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
      ->addText(utf8_decode($row['s_observacion'] ?: ''), $cellTextStyle, $paragraphStyle);
        
        
        // --- INICIO: Nuevo UPDATE para cada registro mostrado en la tabla ---
        $id_solicitud_actual = $row['id_solicitud'];
        $sql_update_solicitudes_estado = "
                UPDATE solicitudes_working_copy 
                SET 
                    estado_depto = 'ENVIADO',
                    oficio_depto = '$num_oficio',
                    fecha_oficio_depto = '$fecha_oficio',
                    oficio_con_fecha = '$oficio_con_fecha_depto'
                WHERE 
                    id_solicitud = '$id_solicitud_actual'
            ";


        if ($con->query($sql_update_solicitudes_estado) === TRUE) {
            // echo "Estado de solicitud con ID {$id_solicitud_actual} actualizado a ENVIADO correctamente.";
        } else {
            // echo "Error al actualizar el estado de la solicitud con ID {$id_solicitud_actual}: " . $con->error;
        }
        // --- FIN: Nuevo UPDATE para cada registro mostrado en la tabla ---
    }
}
$fontStyleSmall = array('name' => 'Arial', 'size' => 7, 'italic' => true);
$paragraphStyleSmall = array('spaceBefore' => 0, 'spaceAfter' => 0);

$section->addText(
    'Dedic/hr=Dedicación (Ocasional) u Horas(Cátedra), nuevo=Anexa Hoja de vida, Antig = Actualiza Hoja de vida Antiguo, OTC = Ocasional Tiempo Completo, OMT = Ocasional Medio Tiempo', 
    $fontStyleSmall, 
    $paragraphStyleSmall
);
    $section->addTextBreak(); // Inserta un salto de línea

// Pie de página
//$piedepagina = "\n\nUniversitariamente";
$section->addText('Universitariamente, ',$fontStylecuerpo);

    $section->addTextBreak(); // Inserta un salto de línea
$section->addText(mb_strtoupper($elaboro, 'UTF-8'), $fontStylecuerpo,$paragraphStylexz);
$section->addText('Jefe de Departamento de ' . ($nombre_depto), $fontStylecuerpo);
// Definir estilo de fuente para texto en cursiva con tamaño 8
$fontStyle = array('italic' => true, 'size' => 8);
$paragraphStyle = array('indentation' => array('left' => 720)); // 720 twips = 0.5 pulgada

// Agregar el texto inicial con estilo de fuente definido, sin sangría
if ($folios == 1) {
    $section->addText('Anexo: (' . $folios . ') folio', $fontStyle);
} else if ($folios > 1) {
    $section->addText('Anexo: (' . $folios . ') folios', $fontStyle);
} else {
    $section->addText('Anexo: (  ) folio(s)', $fontStyle);
}
// Consulta a la base de datos para obtener datos relevantes



$consultacant = "
    SELECT COUNT(*) as cant_profesores
    FROM solicitudes_working_copy 
    WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes_working_copy.estado <> 'an' OR solicitudes_working_copy.estado IS NULL) and solicitudes_working_copy.novedad = 'adicionar'
";

// Excluir de nuevo las cédulas que ya se manejaron en el conteo total de profesores.
/*if (!empty($cedulas_excluir_str)) {
    $consultacant .= " AND cedula NOT IN ($cedulas_excluir_str)";
}*/

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

//$section->addText($piedepagina, array('size' => 12, 'italic' => true));

// Pie de página
$footer = $section->addFooter();
$footer->addImage($imgpie, array(
    'width' => 490, // Ajusta el ancho según sea necesario
    //'height' => 65, // Ajusta el alto según sea necesario
    'marginTop' => 0, // Ajusta el margen superior según sea necesario
    'marginRight' => 1000, // Ajusta el margen derecho según sea necesario
));

// Encabezados HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="document.docx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en formato DOCX y enviarlo al navegador
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');

//envio  a php email: 

// Enviar variables a otro archivo PHP (antes del exit)

// Configuración de PHPMailer para el envío de correo
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host         = 'smtp.gmail.com';
    $mail->SMTPAuth     = true;
    $mail->Username     =$config['smtp_username']; // Cambia esto por tu correo
    $mail->Password     = $config['smtp_password']; // Cambia esto por tu contraseña
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port         = 587;

    // Opciones SSL para mayor compatibilidad
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
$mail->addCC('ejurado@unicauca.edu.co'); // Enviar copia

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';    // Asegúrate de que el correo se envíe en UTF-8
    $mail->Subject = 'Notificación: solicitud de Novedades de vinculación temporales - ' . $nombre_depto;
    $mail->Body    = "
        <p>Cordial saludo, </p>
        <p>Se ha generado una solicitud Novedad de vinculación de profesores temporales desde el departamento <strong>{$nombre_depto} para el periodo {$anio_semestre}</strong>.</p>
        <p>Por favor, revise la plataforma solicitudes de vinculación, http://192.168.42.175/temporalesc/ para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
        <p>Universitariamente,</p>
        <p><strong>Vicerrectoría Académica</strong></p>
    ";
    // Enviar el correo
    $mail->send();
  //  echo "Correo enviado correctamente a $facultad_email.";
} catch (Exception $e) {
  //  echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
}


exit; // Terminar el script para evitar cualquier salida adicional
?>