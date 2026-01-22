<?php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos
require 'funciones.php';
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;


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
    'marginLeft' => 1700,   // 3 cm a la izquierda (en twips)
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
$num_acta = $_GET['acta'];
$fecha_acta = $_GET['fecha_acta'];

// Formatear la fecha en el formato "día de mes de año"
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');
$fecha_acta_b = strftime('%d de %B de %Y', strtotime($fecha_acta));

// Unir el número de acta con la fecha formateada
$acta = $num_acta . ' del ' . $fecha_acta_b;

$facultad_id= obteneridfac($departamento_id);
$fecha_oficio = $_GET['fecha_oficio'];

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

// Configurar la localización a español
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');

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
    'Una vez realizada  la revisión y selección de los postulados en el Banco de Aspirantes como consta en acta de selección de profesores temporales del departamento de ' .obtenerNombreDepartamento($departamento_id) . ' No. '.$acta.' en el periodo '.$anio_semestre.', en atención a las necesidades de profesores que se requieren y los servicios solicitados a este departamento, a continuación relaciono la información profesores temporales requeridos para iniciar trámites de aval de vinculación profesoral ante el Consejo de Facultad:',
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

if ($acepta_vra == '1') { // Si es rechazado en VRA
    $sql_update_fac = "UPDATE fac_periodo 
                       SET fp_acepta_vra = '0'
                       WHERE fp_fk_fac = '$facultad_id' AND fp_periodo = '$anio_semestre'";
}

// Verificamos que $sql_update_fac no esté vacío antes de ejecutar la consulta
if (!empty($sql_update_fac)) {
    if ($con->query($sql_update_fac) === TRUE) {
        // La consulta se ejecutó correctamente
        // echo "Registro actualizado correctamente";
    } else {
        // En caso de error en la ejecución
     //   echo "Error al ejecutar la consulta: " . $con->error;
    }
} else {
   // echo "No se definió la consulta SQL.";
}
    if ($row['dp_acepta_fac'] === 'rechazar') {
    // Incluir dp_acepta_fac = 'subsanado' en el UPDATE
    $sql_update = "UPDATE depto_periodo 
                   SET dp_estado_total = '1', 
                       num_oficio_depto = '$num_oficio', 
                       proyecta = '$elaboro', 
                       dp_folios = $folios, 
                       dp_fecha_envio = '$fecha_hora_envio', 
                       fecha_oficio_depto = '$fecha_oficio', 
                       dp_acta_periodo = '$num_acta',  
                       dp_fecha_acta = '$fecha_acta', 
                       dp_acepta_fac = 'subsanado' 
                   WHERE fk_depto_dp = '$departamento_id' 
                   AND periodo = '$anio_semestre'";
} else {
    // Sin modificar dp_acepta_fac
    $sql_update = "UPDATE depto_periodo 
                   SET dp_estado_total = '1', 
                       num_oficio_depto = '$num_oficio', 
                       proyecta = '$elaboro', 
                       dp_folios = $folios, 
                       dp_fecha_envio = '$fecha_hora_envio', 
                       fecha_oficio_depto = '$fecha_oficio', 
                       dp_acta_periodo = '$num_acta',  
                       dp_fecha_acta = '$fecha_acta' 
                   WHERE fk_depto_dp = '$departamento_id' 
                   AND periodo = '$anio_semestre'";
}
    // Ejecutar el UPDATE
    if ($con->query($sql_update) === TRUE) {
      //  echo "Registro actualizado correctamente";
    } else {
     //   echo "Error al actualizar el registro: " . $con->error;
    }
} else {
  //  echo "No se encontró el registro en depto_periodo";
}

$consulta_depto = "SELECT DISTINCT NOMBRE_DEPTO_CORT, depto_nom_propio 
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL);";

$resultadodepto = $con->query($consulta_depto);

if (!$resultadodepto) {
    die('Error en la consulta: ' . $con->error);
}

$nom_depto = ""; // Inicializar la variable para almacenar el nombre del departamento
$paragraphStylec = array('spaceAfter' => 1);

while ($rowdepto = $resultadodepto->fetch_assoc()) {
$nom_depto = mb_strtoupper($rowdepto['depto_nom_propio'], 'UTF-8');
}

$section->addText('DEPARTAMENTO DE ' . $nom_depto, 
        array('bold' => true), 
        $paragraphStylec
    );
// Negrilla para el texto del departamento

// Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL);";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}
$paragraphStyleb = array('spaceBefore' => 50, 'spaceAfter' => 10, 'lineHeight' => 1);
$fontStyleb = array('name' => 'Arial', 'size' => 10);
$iteracion = 0; // Inicializar un contador de iteraciones

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_d = $rowtipo['tipo_d'];
    $consultat = "SELECT solicitudes.*, facultad.nombre_fac_min AS nombre_facultad,
    facultad.email_fac AS email_facultad,
    deparmanentos.depto_nom_propio AS nombre_departamento 
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_d' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
                  ORDER BY solicitudes.nombre ASC";

    $resultadot = $con->query($consultat);



    if (!$resultadot) {
        die('Error en la consulta: ' . $con->error);
    }
if ($iteracion > 0) { // Agregar salto de línea solo si no es la primera iteración
        $section->addTextBreak(1);
    }
//$section->addText('Profesor(es) ' . $tipo_d, $fontStyleb, $paragraphStyleb);
    $tipo_mostrar = ($tipo_d === 'Catedra') ? 'Cátedra' : $tipo_d;
$section->addText('Profesor(es) ' . $tipo_mostrar, $fontStyleb, $paragraphStyleb);
$iteracion++;

    // Estilo de la tabla
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 60, // Margen interno de las celdas (en unidades twips)
    );

    $phpWord->addTableStyle('ColspanRowspan', $styleTable);
    $table = $section->addTable('ColspanRowspan');
$table->setWidth('100%');

    // Encabezados de la tabla
    $row = $table->addRow();
$textrun = $row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
$textrun->addText('N', $cellTextStyle);
$textrun->addText('o', array_merge($cellTextStyle, array('superScript' => true)));    $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);
    $row->addCell(4000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedicación/hr', $cellTextStyle, $paragraphStyle);
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Hoja de vida', $cellTextStyle, $paragraphStyle);

    $row = $table->addRow();
    $row->addCell(400, array('vMerge' => 'continue'));
    $row->addCell(1200, array('vMerge' => 'continue'));
    $row->addCell(4000, array('vMerge' => 'continue'));
        $row->addCell(350, $headerCellStyle)->addText('Pop.', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Reg.', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Anx.Nuevo', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Actz.Antg', $cellTextStyleb, $paragraphStyle);

    $cont = 0;
while ($row = $resultadot->fetch_assoc()) {
    $cont++;
    $facultad_id = $row['facultad_id'];
    $facultad_email = $row['email_facultad'];//pendiente
    //$facultad_email = 'ejurado@unicauca.edu.co';
    $table->addRow();
    $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
    $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);
    $table->addCell(4000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['nombre'] ?: ''), $cellTextStyle, $paragraphStyle);

    if ($tipo_d == "Ocasional") {
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['tipo_dedicacion'] ?: ''), $cellTextStyle, $paragraphStyle);
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['tipo_dedicacion_r'] ?: ''), $cellTextStyle, $paragraphStyle);
    } elseif ($tipo_d == "Catedra") {
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['horas'] ?: ''), $cellTextStyle, $paragraphStyle);
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['horas_r'] ?: ''), $cellTextStyle, $paragraphStyle);
    }

   $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
    ->addText(mb_strtoupper(utf8_decode($row['anexa_hv_docente_nuevo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

$table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
    ->addText(mb_strtoupper(utf8_decode($row['actualiza_hv_antiguo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

}

}

$fontStyleSmall = array('name' => 'Arial', 'size' => 7, 'italic' => true);
$paragraphStyleSmall = array('spaceBefore' => 0, 'spaceAfter' => 0);

$section->addText(
    'hr=Horas, Anx=Anexa, Actl.Antg = Actualiza Antiguo, TC = Tiempo Completo, MT = Medio Tiempo', 
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
    $section->addText('Anexo: (   ) folio(s)', $fontStyle);
}
// Consulta a la base de datos para obtener datos relevantes



$consultacant = "
    SELECT COUNT(*) as cant_profesores
    FROM solicitudes 
    WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
";

$resultadocant = $con->query($consultacant);

if (!$resultadocant) {
    die('Error en la consulta: ' . $con->error);
}

// Obtener el único resultado de la consulta
$rowcant = $resultadocant->fetch_assoc();
$cant_profesores = $rowcant['cant_profesores'];


    
$consultaanexo = "SELECT solicitudes.*, facultad.nombre_fac_min AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.anexa_hv_docente_nuevo = 'si' OR solicitudes.actualiza_hv_antiguo = 'si') AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

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
        $section->addText('Actualización Hoja de Vida de ' . $nombre . '', $fontStyle, ['spaceAfter' => 0]);
    }
}
$section->addText('Formato PM-FO-4-FOR-59. Acta de Selección: '.$acta, $fontStyle, array('spaceAfter' => 0));
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
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   =$config['smtp_username']; // Cambia esto por tu correo
    $mail->Password   = $config['smtp_password']; // Cambia esto por tu contraseña
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Opciones SSL para mayor compatibilidad
    $mail->SMTPOptions = [
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
    $mail->CharSet = 'UTF-8';  // Asegúrate de que el correo se envíe en UTF-8
    $mail->Subject = 'Notificación: solicitud de vinculación temporales el departamento ' . $nombre_depto;
    $mail->Body    = "
        <p>Cordial saludo, </p>
        <p>Se ha generado una solicitud de vinculación de profesores temporales desde el departamento <strong>{$nombre_depto} para el periodo {$anio_semestre}</strong>.</p>
        <p>Por favor, revise la plataforma solicitudes de vinculación, http://192.168.42.175/temporales/ para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
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
