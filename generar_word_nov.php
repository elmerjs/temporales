<?php
require 'vendor/autoload.php'; // Cargar PHPWord o cualquier librería de generación de Word

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;

// Validar los parámetros recibidos
if (isset($_GET['ids_seleccionados'])) {
    $ids = explode(',', $_GET['ids_seleccionados']);
    $pdo = new PDO('mysql:host=localhost;dbname=contratacion_temporales', 'root', '');

// Definir estilos de texto para las celdas de la tabla
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);

$cellTextStyle = array('size' => 9, 'name' => 'Arial');
$cellTextStyleb = array('size' => 8, 'name' => 'Arial');
$cellTextStylec = array('size' => 7, 'name' => 'Arial');

// Estilos para la celda del encabezado de la tabla
$headerCellStyle = array('bgColor' => '#f2f2f2');

    
    // Consulta ajustada para obtener todos los datos relevantes
    $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
    $stmt = $pdo->prepare("
        SELECT 
            facultad_id, departamento_id, tipo_docente, tipo_novedad, detalle_novedad 
        FROM solicitudes_novedades 
        WHERE id_novedad IN ($placeholders)
        ORDER BY facultad_id, departamento_id, tipo_docente, tipo_novedad
    ");
    $stmt->execute($ids);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear el documento Word
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

    // Obtener facultad del primer resultado
    $facultad = $results[0]['facultad_id'] ?? 'No especificado';
    $imgEncabezado = $facultades[$facultad]['encab'] ?? 'img/encabezado_generico.png';
    $imgpie = $facultades[$facultad]['pie'];

    // Agregar encabezado
    $header = $section->addHeader();
    $header->addImage($imgEncabezado, [
        'height' => 115,
        'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
    ]);

    // Encabezado del documento
    $section->addText('Reporte de Novedades', ['bold' => true, 'size' => 16], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
    $section->addTextBreak();

    // Agrupar información y agregar al documento
    $currentDepartamento = null;
    $currentTipoDocente = null;
    $currentTipoNovedad = null;

foreach ($results as $row) {
    if ($currentDepartamento !== $row['departamento_id']) {
        $currentDepartamento = $row['departamento_id'];
        $section->addText("Departamento: $currentDepartamento", ['bold' => true, 'size' => 12]);

        // Reiniciar el control de subtítulos cuando cambia el departamento
        $currentTipoDocente = null;
        $currentTipoNovedad = null;
    }

    if ($currentTipoDocente !== $row['tipo_docente']) {
        $currentTipoDocente = $row['tipo_docente'];
        $section->addText("  Tipo de Docente: $currentTipoDocente", ['size' => 11]);

        // Reiniciar el control de subtítulos cuando cambia el tipo de docente
        $currentTipoNovedad = null;
    }

    if ($currentTipoNovedad !== $row['tipo_novedad']) {
        $currentTipoNovedad = $row['tipo_novedad'];
        $section->addText("    Tipo de Novedad: $currentTipoNovedad", ['italic' => true, 'size' => 10]);
    }

   // Obtener los detalles de la novedad
$detalle = $row['detalle_novedad'] ?? 'No especificado';

// Decodificar el JSON si es válido
$datos = json_decode($detalle, true);

// Verificar si la decodificación fue exitosa y si contiene datos
if (is_array($datos)) {
    // Modificar los datos según el tipo de docente
if ($currentTipoDocente == 'Ocasional') {
    unset($datos['hrs pop'], $datos['hrs reg']);
} elseif ($currentTipoDocente == 'Catedra') {
    unset($datos['dedic pop'], $datos['dedic reg']);
}

    // Crear una tabla
    $table = $section->addTable([
        'borderSize' => 6,
        'borderColor' => 'black',
        'alignment' => 'center',
        'cellMargin' => 50,
    ]);

    // Agregar la fila de encabezados dinámicamente con las claves del JSON
    $table->addRow();
    foreach (array_keys($datos) as $key) {
        $table->addCell(2000)->addText(strtoupper($key), ['bold' => true, 'size' => 9]);
    }

    // Agregar la fila de datos dinámicamente con los valores del JSON
    $table->addRow();
    foreach ($datos as $valor) {
        $table->addCell(2000)->addText($valor !== null ? htmlspecialchars($valor) : '', ['size' => 9]);
    }
} else {
    // En caso de que el JSON no sea válido, mostrar el texto original
    $section->addText("Detalle: $detalle", ['size' => 9]);
}
}
    // Guardar el archivo temporalmente
    $fileName = 'reporte_novedades_' . date('Ymd_His') . '.docx';
    $filePath = sys_get_temp_dir() . '/' . $fileName;
    $phpWord->save($filePath, 'Word2007');

    // Enviar el archivo al navegador para su descarga
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);

    // Eliminar el archivo temporal
    unlink($filePath);
    exit;
} else {
    echo 'No se recibieron IDs válidos para generar el documento.';
}
