<?php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos

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
    'marginLeft' => 1700,   // 3 cm a la izquierda (en twips)
    'marginRight' => 1700,  // 3 cm a la derecha (en twips)
    'marginTop' => 2268,    // 2 cm arriba (en twips)
    'marginBottom' => 1134  // 2 cm abajo (en twips)
    
));
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Obtener los parámetros de la URL
$facultad_id = $_GET['facultad_id'];
$anio_semestre = $_GET['anio_semestre'];
$num_oficio = isset($_GET['numero_oficio']) ? $_GET['numero_oficio'] : '';
$elaboro = isset($_GET['elaborado_por']) ? $_GET['elaborado_por'] : '';
$totalfolios=0;
// Función para obtener el nombre del decano
function obtenerDecano($facultad_id) {
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
}

$decano = obtenerDecano($facultad_id);



// Verificar si el registro ya existe
$sql_check = "SELECT * FROM fac_periodo WHERE fp_fk_fac = '$facultad_id' AND fp_periodo = '$anio_semestre'";
$result_check = $con->query($sql_check);

if ($result_check->num_rows > 0) {
    // El registro existe, realizar actualización
    $sql_update = "UPDATE fac_periodo SET fp_estado = '1', fp_num_oficio = '$num_oficio', fp_elaboro='$elaboro', fp_decano = '$decano', fecha_accion = NOW()  WHERE fp_fk_fac = '$facultad_id' AND fp_periodo = '$anio_semestre'";
    if ($con->query($sql_update) === TRUE) {
        //echo "Registro actualizado correctamente.";
    } else {
        echo "Error al actualizar el registro: " . $con->error;
    }
} else {
    // El registro no existe, realizar inserción
    $sql_insert = "INSERT INTO fac_periodo (fp_fk_fac, fp_periodo, fp_estado, fp_num_oficio, fp_elaboro, fp_decano,fecha_accion) VALUES ('$facultad_id', '$anio_semestre', '1', '$num_oficio', '$elaboro', '$decano',NOW())";
    if ($con->query($sql_insert) === TRUE) {
    //    echo "Registro insertado correctamente.";
    } else {
        echo "Error al insertar el registro: " . $con->error;
    }
}

if($facultad_id =='0') {$where = "where anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";}
else {$where = "WHERE anio_semestre = '$anio_semestre' and facultad.PK_FAC ='$facultad_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";}
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
    'height' => 80, // Incrementar el alto en un 10%
    'marginTop' => -84, // Subir la imagen para compensar el espacio de margen superior de 1 cm
   // 'marginRight' => 1700, // Mover la imagen 3 cm más a la derecha (3 cm * 567 twips/cm)
    'align' => 'right', // Alinear a la derecha

));

$encabezado_oficio = $num_oficio;

$section->addText($encabezado_oficio, array('size' => 11, 'bold' => false, 'name' => 'Arial'));
// Obtener la fecha actual

// Configurar la localización a español
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');

// Obtener la fecha actual en español
$fecha_actual = strftime('%d de %B de %Y'); // Formato: 14 de febrero de 2024

  
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
                  WHERE facultad_id = '$pk_fac' AND anio_semestre = '$anio_semestre' and depto_periodo.dp_estado_total=1 AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL);";

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
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL);";

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
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_d' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

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
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE facultad_id = '$pk_fac' AND anio_semestre = '$anio_semestre' AND (solicitudes.anexa_hv_docente_nuevo = 'si' OR solicitudes.actualiza_hv_antiguo = 'si') AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

$resultadoanexo = $con->query($consultaanexo);

if (!$resultadoanexo) {
    die('Error en la consulta: ' . $con->error);
}

while ($rowanexo = $resultadoanexo->fetch_assoc()) {
    $nombre = ucwords(strtolower($rowanexo['nombre']));
    $section->addText('Hoja de Vida ' . $nombre . ' con su respectiva lista de chequeo', $fontStyle, array('spaceAfter' => 0));
}
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
