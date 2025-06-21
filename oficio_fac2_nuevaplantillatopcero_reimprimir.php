<?php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos
include 'funciones.php'; // Asegúrate de ajustar la ruta correctamente

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;

// Definir estilos de texto para las celdas de la tabla
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);

$cellTextStyle = array('size' => 9, 'name' => 'Arial');
$cellTextStyleb = array('size' => 8, 'name' => 'Arial');
$cellTextStylec = array('size' => 7, 'name' => 'Arial');

// Estilos para la celda del encabezado de la tabla
$headerCellStyle = array('bgColor' => '#f2f2f2');

// Crear una nueva instancia de PhpWord
$phpWord = new PhpWord();

// Definir dimensiones de una página tamaño carta en twips
$pageWidth = 12240; // 21.59 cm (8.5 pulgadas) en twips
$pageHeight = 15840; // 27.94 cm (11 pulgadas) en twips

// Agregar una sección con tamaño de página y márgenes personalizados
$section = $phpWord->addSection(array(
    'pageSizeW' => $pageWidth, 
    'pageSizeH' => $pageHeight,
    'marginLeft' => 1700,
    'marginRight' => 1700,  // 3 cm a la derecha (en twips)
    'marginTop' => 2268,    // 2 cm arriba (en twips)
    'marginBottom' => 1134,  // 2 cm abajo (en twips)
        "headerHeight" => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0),
    "footerHeight" => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0)


));
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Obtener los parámetros de la URL
//$decano_oficio= $_GET['decano'];
$facultad_id = $_GET['facultad_id'];
/*$fecha_oficio = $_GET['fecha_oficio'];*/
$anio_semestre = $_GET['anio_semestre'];
//$num_oficio = isset($_GET['numero_oficio']) ? $_GET['numero_oficio'] : '';
//$elaboro = isset($_GET['elaborado_por']) ? $_GET['elaborado_por'] : '';*/
$totalfolios=0;

$cierreperiodo = obtenerperiodo($anio_semestre);
error_log("Cierre de periodo: " . $cierreperiodo);
error_log("Facultad ID: " . $facultad_id);
error_log("Año-Semestre: " . $anio_semestre);

// Función para obtener el nombre del decano
/*function obtenerDecano($facultad_id) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $sql = "SELECT decano FROM facultad WHERE PK_FAC = '$facultad_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['decano'];
    } else {
        return "fac Desconocido";
    }
}*/

//$decano = obtenerDecano($facultad_id);
//$decano =$decano_oficio; //vamos a tomar el dato que viene del modal;  ahi se hiczo la verificacion

if ($cierreperiodo != '1'){//si no está cerrada

// Verificar si el registro ya existe
$sql_check = "SELECT * FROM fac_periodo WHERE fp_fk_fac = '$facultad_id' AND fp_periodo = '$anio_semestre'";
$result_check = $con->query($sql_check);

while ($row = $result_check->fetch_assoc()) {
    // Asignar los valores a variables
    $fp_estado = $row['fp_estado'];
    $fp_fk_fac = $row['fp_fk_fac'];
    $num_oficio = $row['fp_num_oficio'];
    $elaboro = $row['fp_elaboro'];
    $decano = $row['fp_decano'];
    $fp_periodo = $row['fp_periodo'];
    $fecha_accion = $row['fecha_accion'];
    $fecha_oficio = $row['fecha_oficio_fac'];
    $fp_acepta_vra = $row['fp_acepta_vra'];

    // Log en consola y salida de datos
    echo "Registro encontrado: ";
    error_log("Registro encontrado: Num_Oficio=$num_oficio, Decano=$decano, FechaOficio=$fecha_oficio");
}
} //se hace todo el update  solo si  el semestre está abierto de lo contrario solo lo que sigue
if($facultad_id =='0') {$where = "where anio_semestre = '$anio_semestre'";}
else {
    $where = "WHERE anio_semestre = '$anio_semestre' and facultad.PK_FAC ='$facultad_id'";
}
$facultades = [
    1 => ['encab' => 'img/encabezado_decanatura_artes.png', 'pie' => 'img/pieartes.png'],
    2 => ['encab' => 'img/encabezado_decanatura_agrarias.png', 'pie' => 'img/pieagro.png'],
    3 => ['encab' => 'img/encabezado_decanatura_salud.png', 'pie' => 'img/piesalud.png'],
    4 => ['encab' => 'img/encabezado_decanatura_fccea.png', 'pie' => 'img/piecontables.png'],
    5 => ['encab' => 'img/encabezado_decanatura_humanas.png', 'pie' => 'img/piehumanas.png'],
    6 => ['encab' => 'img/encabezado_decanatura_facned.png', 'pie' => 'img/piefacned.png'],
    7 => ['encab' => 'img/encabezado_decanatura_derecho.png', 'pie' => 'img/piederecho.png'],
    8 => ['encab' => 'img/encabezado_decanatura_civil.png', 'pie' => 'img/piecivil.png'],
    9 => ['encab' => 'img/encabezado_decanatura_fiet.png', 'pie' => 'img/piefiet.png']
];

if (isset($facultades[$facultad_id])) {
    $imgencabezado = $facultades[$facultad_id]['encab'];
    $imgpie = $facultades[$facultad_id]['pie'];
} else {
    $imgencabezado = 'img/encabezado_generico.png';
    $imgpie = 'img/piegenerico.png';
}

// Encabezado del oficio
$header = $section->addHeader();
$header->addImage($imgencabezado, array(
    //'width' => 560, // Incrementar el ancho en un 10%
    'height' => 115, // Incrementar el alto en un 10%
    'marginTop' => 0, // Subir la imagen para compensar el espacio de margen superior de 1 cm
   // 'marginRight' => 1700, // Mover la imagen 3 cm más a la derecha (3 cm * 567 twips/cm)
   // 'align' => 'right', // Alinear a la derecha
    'marginLeft' => round(\PhpOffice\PhpWord\Shared\Converter::cmToPixel(-2.4)),
    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
        'posHorizontal' => \PhpOffice\PhpWord\Style\Image::POSITION_ABSOLUTE,

    'wrappingStyle' => 'infront',
    'positioning' => \PhpOffice\PhpWord\Style\Image::POSITION_ABSOLUTE

));

$encabezado_oficio = $num_oficio;

$section->addText($encabezado_oficio, array('size' => 11, 'bold' => false, 'name' => 'Arial'));
// Obtener la fecha actual

// Configurar la localización a español
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');

// Obtener la fecha actual en español
//$fecha_actual = strftime('%d de %B de %Y'); // Formato: 14 de febrero de 2024
$fecha_actual = strftime('%d de %B de %Y', strtotime($fecha_oficio));

  
// Estilo de párrafo justificado
$justifiedStyle = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH);

$consulta_fac = "SELECT distinct facultad.PK_FAC as pk_fac, facultad.nombre_fac_minb AS nombre_facultad 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            $where";

$resultadofac = $con->query($consulta_fac);

while ($rowfac = $resultadofac->fetch_assoc()) {
     $pk_fac = $rowfac['pk_fac'];
     $fac = $rowfac['nombre_facultad'];
    
$consulta_depto = "SELECT DISTINCT NOMBRE_DEPTO_CORT, deparmanentos.PK_DEPTO, depto_periodo.num_oficio_depto, depto_periodo.dp_folios 
                  FROM solicitudes 
                join depto_periodo on depto_periodo.fk_depto_dp = solicitudes.departamento_id

                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE facultad_id = '$pk_fac' AND anio_semestre = '$anio_semestre' and depto_periodo.dp_estado_total=1;";

$resultadodepto = $con->query($consulta_depto);

if (!$resultadodepto) {
    die('Error en la consulta: ' . $con->error);
}

// Agregar texto con la fecha actual
$section->addText('Popayán, ' . $fecha_actual);
    $section->addTextBreak(); // Inserta un salto de línea

    $section->addText('Doctora');
        $section->addText('AIDA PATRICIA GONZALEZ');

    
        $section->addText('VICERRECTORA ACADÉMICA');
$section->addTextBreak(); // Inserta un salto de línea

    
$asunto = 'Asunto: Solicitud contratación Docentes Ocasionales y Catedra ' . $anio_semestre . ' de la Facultad de ' . $fac . '.';
$section->addText($asunto,null, $justifiedStyle);
$section->addTextBreak(); // Inserta un salto de línea


$section->addText('Cordial saludo');

// Agregar el cuerpo del texto
$cuerpo = 'Me permito solicitar la contratación de los Docentes Ocasionales y Catedráticos que se requieren para el ' . $anio_semestre . ', teniendo en cuenta el análisis de necesidades de la Facultad de Ciencias Humanas y Sociales.';
$section->addText($cuerpo, null, $justifiedStyle);

// Agregar el siguiente párrafo
$parrafo = 'A continuación, los Departamentos y Coordinaciones que solicitan contratación docente para el periodo ' . $anio_semestre . ' con el aval del Consejo de Facultad.';
$section->addText($parrafo, null, $justifiedStyle);

// Escapar las variables para prevenir inyecciones SQL
//$departamento_id = $con->real_escape_string($departamento_id);
$anio_semestre = $con->real_escape_string($anio_semestre);

$nom_depto = ""; // Inicializar la variable para almacenar el nombre del departamento

while ($rowdepto = $resultadodepto->fetch_assoc()) {
    $nom_depto = $rowdepto['NOMBRE_DEPTO_CORT'];
     $departamento_id = $rowdepto['PK_DEPTO'];
    $oficio_depto = $rowdepto['num_oficio_depto'];
    $folios = $rowdepto['dp_folios'];
    $totalfolios= $totalfolios+$folios;
$paragraphStylec = array('spaceAfter' => 1);

if ($folios > 1) {
    $section->addText(
        'DEPARTAMENTO DE ' . $nom_depto . ' (Oficio: ' . $oficio_depto . ') ' . $folios . ' folios', 
        array('bold' => true), 
        $paragraphStylec
    );
} elseif ($folios == 1) {
    $section->addText(
        'DEPARTAMENTO DE ' . $nom_depto . ' (Oficio: ' . $oficio_depto . ') ' . $folios . ' folio', 
        array('bold' => true), 
        $paragraphStylec
    );
} else {
    $section->addText(
        'DEPARTAMENTO DE ' . $nom_depto . ' (Oficio: ' . $oficio_depto . ') ', 
        array('bold' => true), 
        $paragraphStylec
    );
} 
// Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre';";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_d = $rowtipo['tipo_d'];
    $consultat = "SELECT solicitudes.*, facultad.nombre_fac_min AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_d'";

    $resultadot = $con->query($consultat);

    if (!$resultadot) {
        die('Error en la consulta: ' . $con->error);
    }

$paragraphStyleb = array('spaceAfter' => 0, 'lineHeight' => 1);
$fontStyleb = array('name' => 'Arial', 'size' => 12);

$section->addText('Docente ' . $tipo_d, $fontStyleb, $paragraphStyleb);
    // Estilo de la tabla
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 60, // Margen interno de las celdas (en unidades twips)
    );

   // Define paragraph style with minimal space after the text
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);

// Estilo para la tabla
$styleTable = array('borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80);
$phpWord->addTableStyle('ColspanRowspan', $styleTable);
$table = $section->addTable('ColspanRowspan');
$table->setWidth('100%');

// Encabezados de la tabla
$row = $table->addRow();
$row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('ID', $cellTextStyle, $paragraphStyle);
$row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cedula', $cellTextStyle, $paragraphStyle);
$row->addCell(4000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
$row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedicacion/hr', $cellTextStylec, $paragraphStyle);
$row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Hoja de vida', $cellTextStylec, $paragraphStyle);

$row = $table->addRow();
$row->addCell(400, array('vMerge' => 'continue'));
$row->addCell(1200, array('vMerge' => 'continue'));
$row->addCell(4000, array('vMerge' => 'continue'));
$row->addCell(350, $headerCellStyle)->addText('Pop', $cellTextStylec, $paragraphStyle);
$row->addCell(350, $headerCellStyle)->addText('Regnz', $cellTextStylec, $paragraphStyle);
$row->addCell(350, $headerCellStyle)->addText('Anex(Nuevo)', $cellTextStylec, $paragraphStyle);
$row->addCell(350, $headerCellStyle)->addText('Actlz(Antiguo)', $cellTextStylec, $paragraphStyle);

$cont = 0;
while ($row = $resultadot->fetch_assoc()) {
    $cont++;

    $table->addRow();
    $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
    $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['cedula']), $cellTextStyle, $paragraphStyle);
    $table->addCell(4000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['nombre']), $cellTextStyle, $paragraphStyle);

    if ($tipo_d == "Ocasional") {
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['tipo_dedicacion']), $cellTextStyle, $paragraphStyle);
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['tipo_dedicacion_r']), $cellTextStyle, $paragraphStyle);
    } elseif ($tipo_d == "Catedra") {
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['horas']), $cellTextStyle, $paragraphStyle);
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['horas_r']), $cellTextStyle, $paragraphStyle);
    }

    $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['anexa_hv_docente_nuevo']), $cellTextStyle, $paragraphStyle);
    $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['actualiza_hv_antiguo']), $cellTextStyle, $paragraphStyle);
}
$section->addText('', null, array('spaceAfter' => 1)); // Ajusta el espacio después de la línea


}

}}

$cuerpof = 'Universitariamente;';

$section->addText($cuerpof, null, $justifiedStyle);
$section->addText($decano, null);
function numberToWords($number) {
    $f = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    return $f->format($number);
}

$fontStyle = array('italic' => true, 'size' => 8);
$paragraphStyle = array('indentation' => array('left' => 720)); // 720 twips = 0.5 pulgada

$foliosEnLetras = numberToWords($totalfolios);

$section->addText('Anexo : ('.$totalfolios.') '. $foliosEnLetras.' folios', $fontStyle);

// Consulta a la base de datos para obtener datos relevantes
$consultaanexo = "SELECT solicitudes.*, facultad.nombre_fac_min AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
                  FROM solicitudes 
                JOIN depto_periodo on (depto_periodo.fk_depto_dp=solicitudes.departamento_id and depto_periodo.periodo = solicitudes.anio_semestre)

                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE facultad_id = '$pk_fac' AND anio_semestre = '$anio_semestre' AND (solicitudes.anexa_hv_docente_nuevo = 'si' OR solicitudes.actualiza_hv_antiguo = 'si') and depto_periodo.dp_estado_total=1 ";

$resultadoanexo = $con->query($consultaanexo);

if (!$resultadoanexo) {
    die('Error en la consulta: ' . $con->error);
}

while ($rowanexo = $resultadoanexo->fetch_assoc()) {
    $nombre = ucwords(strtolower($rowanexo['nombre']));
    $section->addText('Hoja de Vida ' . $nombre . ' con su respectiva lista de chequeo', $fontStyle, array('spaceAfter' => 0));
}



$consultacant = "
 SELECT COUNT(*) as cant_profesores
    FROM solicitudes 
    join deparmanentos on deparmanentos.PK_DEPTO= solicitudes.departamento_id
    JOIN depto_periodo on (depto_periodo.fk_depto_dp=solicitudes.departamento_id and depto_periodo.periodo = solicitudes.anio_semestre)

    WHERE deparmanentos.FK_FAC = '$pk_fac' AND anio_semestre = '$anio_semestre' and dp_estado_total = '1'";

$resultadocant = $con->query($consultacant);

if (!$resultadocant) {
    die('Error en la consulta: ' . $con->error);
}

// Obtener el único resultado de la consulta
$rowcant = $resultadocant->fetch_assoc();
$cant_profesores = $rowcant['cant_profesores'];


$consultaanexoact = "

SELECT * FROM depto_periodo
join deparmanentos  on (deparmanentos.PK_DEPTO= depto_periodo.fk_depto_dp)
WHERE depto_periodo.periodo ='$anio_semestre' and deparmanentos.FK_FAC='$pk_fac'  and dp_estado_total='1'";

$resultadoanexoact = $con->query($consultaanexoact);

if (!$resultadoanexoact) {
    die('Error en la consulta: ' . $con->error);
}







while ($rowanexoact = $resultadoanexoact->fetch_assoc()) {
    $nombre_deptoact = ucwords(strtolower($rowanexoact['depto_nom_propio']));
    $acta_act = ucwords(strtolower($rowanexoact['dp_acta_periodo']));
    $folios_act = $rowanexoact['dp_folios'];
    $section->addText('Acta de selección: ' . $acta_act . ' ('.$nombre_deptoact.')', $fontStyle, array('spaceAfter' => 0));
}
$section->addText('('.$cant_profesores.') Formatos PA-GA-5.1-FOR 45 Revisión Requisitos Vinculación Docente', $fontStyle, array('spaceAfter' => 0));

    $section->addTextBreak(); // Inserta un salto de línea

$section->addText('elaboró:'.$elaboro, $fontStyle);

// Pie de página
//$piedepagina = "Universitariamente";
//$section->addText($piedepagina, array('size' => 11, 'italic' => true));
// Pie de página
$footer = $section->addFooter();
$footer->addImage($imgpie, array(
    'width' => 490, // Ajusta el ancho según sea necesario
    //'height' => 65, // Ajusta el alto según sea necesario
    'marginTop' => 56.7, // Ajusta el margen superior para mover la imagen 2 cm más abajo
    'marginRight' => 1000, // Ajusta el margen derecho según sea necesario
));

// Encabezados HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="document.docx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en formato DOCX y enviarlo al navegador
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');

exit; // Terminar el script para evitar cualquier salida adicional
?>
