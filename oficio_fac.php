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
// Estilos para la celda del encabezado de la tabla
$headerCellStyle = array('bgColor' => '#f2f2f2');

// Crear una nueva instancia de PhpWord
$phpWord = new PhpWord();
$section = $phpWord->addSection();
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Obtener los parámetros de la URL
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];

// Encabezado del oficio
$encabezado_oficio = "6.2.2/05";
$section->addText($encabezado_oficio, array('size' => 12, 'bold' => false, 'name' => 'Arial'));
$section->addText('Popayán, 14 de febrero de 2024');
$section->addText('Doctora AIDA PATRICIA GONZALEZ VICERRECTORA ACADÉMICA');
$section->addText('Cordial saludo');
$section->addText('Adjunto relación de docentes temporales del Departamento X');

// Escapar las variables para prevenir inyecciones SQL
$departamento_id = $con->real_escape_string($departamento_id);
$anio_semestre = $con->real_escape_string($anio_semestre);



$consulta_depto = "SELECT DISTINCT NOMBRE_DEPTO_CORT 
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL);";

$resultadodepto = $con->query($consulta_depto);

if (!$resultadodepto) {
    die('Error en la consulta: ' . $con->error);
}

$nom_depto = ""; // Inicializar la variable para almacenar el nombre del departamento

while ($rowdepto = $resultadodepto->fetch_assoc()) {
    $nom_depto = $rowdepto['NOMBRE_DEPTO_CORT'];
}

$section->addText('DEPARTAMENTO DE ' . $nom_depto, array('bold' => true)); // Negrilla para el texto del departamento

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

    $section->addText('Docente ' . $tipo_d);

    // Estilo de la tabla
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 60, // Margen interno de las celdas (en unidades twips)
    );

    $phpWord->addTableStyle('ColspanRowspan', $styleTable);
    $table = $section->addTable('ColspanRowspan');

    // Encabezados de la tabla
    $row = $table->addRow();
    $row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('ID', $cellTextStyle, $paragraphStyle);
    $row->addCell(1000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cedula', $cellTextStyle, $paragraphStyle);
    $row->addCell(4000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedicacion/hr', $cellTextStyle, $paragraphStyle);
    $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Hoja de vida', $cellTextStyle, $paragraphStyle);

    $row = $table->addRow();
    $row->addCell(400, array('vMerge' => 'continue'));
    $row->addCell(1000, array('vMerge' => 'continue'));
    $row->addCell(4000, array('vMerge' => 'continue'));
    $row->addCell(350, $headerCellStyle)->addText('Popayán', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Regnlzn', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Anexa(N)', $cellTextStyleb, $paragraphStyle);
    $row->addCell(350, $headerCellStyle)->addText('Actualiz(A)', $cellTextStyleb, $paragraphStyle);

    $cont = 0;
while ($row = $resultadot->fetch_assoc()) {
    $cont++;

    $table->addRow();
    $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
    $table->addCell(1000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['cedula']), $cellTextStyle, $paragraphStyle);
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
}

// Pie de página
$piedepagina = "\n\nUniversitariamente";
$section->addText($piedepagina, array('size' => 12, 'italic' => true));

// Encabezados HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="document.docx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en formato DOCX y enviarlo al navegador
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');

exit; // Terminar el script para evitar cualquier salida adicional
?>
