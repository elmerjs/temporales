<?php
// ------------------------------------------------------------
// 1. BUFFER Y ERRORES
// ------------------------------------------------------------
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'vendor/autoload.php';
require_once 'conn.php';
require 'funciones.php';

use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Shared\Converter;

// ------------------------------------------------------------
// 2. FUNCIONES DE LIMPIEZA EXTREMA
// ------------------------------------------------------------
/**
 * Elimina caracteres de control y escapa caracteres especiales XML.
 * Esta función es OBLIGATORIA para cualquier texto que se inserte en Word.
 */
function limpiarTextoXML($texto) {
    if (!is_string($texto) || $texto === '') return '';

    // 1. Forzar UTF-8
    $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8');

    // 2. Eliminar caracteres de control (excepto tab, LF, CR)
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $texto);

    // 3. Reemplazar &nbsp; por espacio normal
    $texto = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $texto);

    // 4. Escapar caracteres especiales XML (&, <, >, ", ')
    $texto = htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');

    // 5. Normalizar espacios múltiples
    $texto = preg_replace('/\s+/u', ' ', $texto);

    return trim($texto);
}

/**
 * Prepara contenido HTML para PHPWord cuando es inevitable usar addHtml.
 * Solo se usa en punto 3 (editor libre) si existe.
 */
function limpiarHTMLparaWordSeguro($html) {
    if (empty($html) || trim($html) === '') {
        return '<body><p>No aplica</p></body>';
    }

    $html = stripslashes($html);
    $html = html_entity_decode($html, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $html);
    $html = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $html);
    $html = str_replace('<table', '<table border="1" style="border-collapse:collapse;"', $html);

    if (stripos($html, '<body') === false) {
        $html = '<body>' . $html . '</body>';
    }
    return $html;
}

// ------------------------------------------------------------
// 3. RECEPCIÓN DE PARÁMETROS
// ------------------------------------------------------------
$departamento_id = $_GET['departamento_id'] ?? null;
$anio_semestre   = $_GET['anio_semestre'] ?? null;
$id_acta         = $_GET['id_acta'] ?? null;

if (!$departamento_id || !$anio_semestre) die("Parámetros insuficientes.");

try {
    // ------------------------------------------------------------
    // 4. CONSULTA DE DATOS
    // ------------------------------------------------------------
    if ($id_acta) {
        $sql = "SELECT * FROM actas_seleccion_novedades WHERE id_acta = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_acta);
    } else {
        $sql = "SELECT * FROM actas_seleccion_novedades WHERE departamento_id = ? AND anio_semestre = ? ORDER BY id_acta DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $departamento_id, $anio_semestre);
    }
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_assoc();

    if (!$datos) throw new Exception("No se encontró el Acta de Novedades solicitada.");

    $nombre_depto    = limpiarTextoXML(obtenerNombreDepartamento($departamento_id));
    $nombre_facultad = limpiarTextoXML(obtenerNombreFacultadcort($departamento_id));

    // ------------------------------------------------------------
    // 5. CONFIGURACIÓN DEL DOCUMENTO
    // ------------------------------------------------------------
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

    $section = $phpWord->addSection([
        'paperSize'    => 'Letter', // <--- CAMBIO CRÍTICO: De A4 a Carta
        'marginTop'    => Converter::cmToTwip(1),
        'marginRight'  => Converter::cmToTwip(2),
        'marginBottom' => Converter::cmToTwip(2),
        'marginLeft'   => Converter::cmToTwip(2),
    ]);

    // Pie de página (con verificación de existencia)
    $footer = $section->addFooter();
    if (file_exists('img/icontec.png')) {
        $footer->addImage('img/icontec.png', ['width' => 80, 'alignment' => Jc::LEFT]);
    }

    // Estilos generales
    $fontBold   = ['name' => 'Arial', 'size' => 10, 'bold' => true];
    $fontNormal = ['name' => 'Arial', 'size' => 10];
    $fontSmall  = ['name' => 'Arial', 'size' => 8];
    $fontTable  = ['name' => 'Arial', 'size' => 9]; // Tamaño 9 para todas las tablas
    $fontBoldItalic = ['name' => 'Arial', 'size' => 10, 'bold' => true, 'italic' => true];
    $fontCursiva = ['name' => 'Arial', 'size' => 10, 'italic' => true];
    $noSpace    = ['spaceAfter' => 0, 'spaceBefore' => 0, 'lineHeight' => 1.0];
    $estiloComprimidoz = ['spaceBefore' => 0, 'spaceAfter' => 0, 'lineHeight' => 1.0];

    $estiloComprimido = [
        'spaceBefore' => 60,  // Pequeño espacio antes del título
        'spaceAfter'  => 0,   // Sin espacio después (para que la tabla o lista quede pegada)
        'lineHeight'  => 1.0, // Interlineado sencillo
        'alignment'   => Jc::LEFT
    ];   
     $sinSombra  = ['bgColor' => null, 'valign' => 'center'];
    $alignCenter = ['alignment' => Jc::CENTER];
    $altoMinimo = Converter::cmToTwip(0.4); // Altura mínima de fila

    // ------------------------------------------------------------
    // 6. ENCABEZADO
    // ------------------------------------------------------------
    if (file_exists('img/encabezado_for59.png')) {
        $section->addImage('img/encabezado_for59.png', ['width' => 495, 'alignment' => Jc::CENTER]);
    }
    $section->addTextBreak(0);

    // ------------------------------------------------------------
    // 7. TABLA DE DATOS DE LA REUNIÓN
    // ------------------------------------------------------------
    $tableDatos = $section->addTable([
        'borderSize'  => 2,
        'borderColor' => '000000',
        'cellMargin'  => 30,
        'width'       => 99 * 50,
        'unit'        => TblWidth::PERCENT
    ]);

    // Facultad
    $row = $tableDatos->addRow($altoMinimo, ['exactHeight' => true]);
    $row->addCell(Converter::cmToTwip(6), $sinSombra)->addText("Facultad", $fontBold, $estiloComprimidoz);
    $row->addCell(null, ['gridSpan' => 3, 'valign' => 'center'])->addText($nombre_facultad, $fontNormal, $estiloComprimidoz);

    // Departamento
    $row = $tableDatos->addRow($altoMinimo, ['exactHeight' => true]);
    $row->addCell(Converter::cmToTwip(6), $sinSombra)->addText("Departamento", $fontBold, $estiloComprimidoz);
    $row->addCell(null, ['gridSpan' => 3, 'valign' => 'center'])->addText($nombre_depto, $fontNormal, $estiloComprimidoz);

    // Lugar
    $row = $tableDatos->addRow($altoMinimo, ['exactHeight' => true]);
    $row->addCell(Converter::cmToTwip(6), $sinSombra)->addText("Lugar de desarrollo de la reunión", $fontBold, $estiloComprimidoz);
    $row->addCell(null, ['gridSpan' => 3, 'valign' => 'center'])->addText(limpiarTextoXML($datos['lugar_reunion'] ?? ''), $fontNormal, $estiloComprimidoz);

    // Fecha
    $row = $tableDatos->addRow($altoMinimo, ['exactHeight' => true]);
    $row->addCell(Converter::cmToTwip(6), array_merge($sinSombra, ['vMerge' => 'restart']))->addText("Fecha", $fontBold, $estiloComprimidoz);
    $row->addCell(Converter::cmToTwip(3.3), $sinSombra)->addText("Día", $fontBold, $alignCenter + $estiloComprimidoz);
    $row->addCell(Converter::cmToTwip(3.3), $sinSombra)->addText("Mes", $fontBold, $alignCenter + $estiloComprimidoz);
    $row->addCell(Converter::cmToTwip(3.4), $sinSombra)->addText("Año", $fontBold, $alignCenter + $estiloComprimidoz);

    $fecha_reunion = $datos['fecha_reunion'] ?? date('Y-m-d');
    $row = $tableDatos->addRow($altoMinimo, ['exactHeight' => true]);
    $row->addCell(Converter::cmToTwip(6), ['vMerge' => 'continue']);
    $row->addCell(null, ['valign' => 'center'])->addText(date('d', strtotime($fecha_reunion)), $fontNormal, $alignCenter + $estiloComprimidoz);
    $row->addCell(null, ['valign' => 'center'])->addText(date('m', strtotime($fecha_reunion)), $fontNormal, $alignCenter + $estiloComprimidoz);
    $row->addCell(null, ['valign' => 'center'])->addText(date('Y', strtotime($fecha_reunion)), $fontNormal, $alignCenter + $estiloComprimidoz);

    // Número de Acta
    $row = $tableDatos->addRow($altoMinimo, ['exactHeight' => true]);
    $row->addCell(Converter::cmToTwip(6), $sinSombra)->addText("Serie, Subserie / No de acta", $fontBold, $estiloComprimidoz);
    $row->addCell(null, ['gridSpan' => 3, 'valign' => 'center'])->addText(limpiarTextoXML($datos['numero_acta'] ?? ''), $fontNormal, $estiloComprimidoz);

    // ------------------------------------------------------------
    // 8. ORDEN DEL DÍA
    // ------------------------------------------------------------
    $section->addTextBreak(1);
    $section->addText("ORDEN DEL DÍA", $fontBold, $noSpace);
    $estiloOrdenDia = ['indent' => 0.28, 'spaceAfter' => 0, 'spaceBefore' => 0, 'lineHeight' => 1.0];
    $items = [
        "1. Periodo académico",
        "2. Verificación de Asistencia",
        "3. Definición del Perfil (Nuevas Necesidades)",
        "4. Consultar Banco de Aspirantes",
        "5. Calificación de hoja de vida",
        "6. Entrevista",
        "7. Selección de los profesores a solicitar vinculación"
    ];
    foreach ($items as $item) {
        $section->addText($item, $fontBold, $estiloOrdenDia);
    }

    // ------------------------------------------------------------
    // 9. DESARROLLO DE LA REUNIÓN (SIN TABLA CONTENEDORA)
    // ------------------------------------------------------------
// 9. DESARROLLO DE LA REUNIÓN (CON RECUADRO)
// ------------------------------------------------------------
$section->addTextBreak(1);
$section->addText("DESARROLLO DE LA REUNIÓN", $fontBold, ['alignment' => Jc::CENTER]);

// --- TABLA CONTENEDORA CON BORDE (UNA SOLA CELDA) ---
$tablaDesarrollo = $section->addTable([
    'borderSize'  => 2,
    'borderColor' => '000000',
    'cellMargin'  => 80,          // Reducido de 100 a 80
    'width'       => 98 * 50,     // 98% en twips (igual que tabla de datos)
    'unit'        => TblWidth::TWIP   // Usar TWIP para control exacto
]);
$celdaDesarrollo = $tablaDesarrollo->addRow()->addCell();

// ========================================================
//  CONTENIDO DEL DESARROLLO (PUNTOS 1 al 7)
// ========================================================
$fontCursiva = ['name' => 'Arial', 'size' => 10, 'italic' => true];

// --- PUNTO 1 Y 2 ---
$celdaDesarrollo->addText("1. Periodo académico: " . $anio_semestre, $fontBold, $estiloComprimido);
$celdaDesarrollo->addTextBreak(1);
$textRun2 = $celdaDesarrollo->addTextRun($estiloComprimido);
$textRun2->addText("2. Verificación de Asistencia de los integrantes del Comité de selección ", $fontBold);
$textRun2->addText("(Acuerdo Superior 017 de 2009, artículo 6: “…Se conformará un Comité de Selección de Docentes, que contará con cinco integrantes, a saber: el Decano o su delegado, el Jefe del Departamento, el Coordinador del programa y dos profesores de planta, preferiblemente del área, nombrados en reunión de Departamento…”) ",  $fontCursiva);

// Tabla de Asistencia
$tableAsis = $celdaDesarrollo->addTable([
    'borderSize' => 2,
    'cellMargin' => 40,
    'width'      => 100 * 50,
    'unit'       => TblWidth::PERCENT
]);
$r = $tableAsis->addRow($altoMinimo);
$r->addCell(Converter::cmToTwip(1), ['bgColor' => 'F2F2F2', 'valign' => 'center'])->addText("No", $fontTable, $alignCenter + $estiloComprimido);
$r->addCell(Converter::cmToTwip(5), ['bgColor' => 'F2F2F2', 'valign' => 'center'])->addText("CARGO", $fontTable, $alignCenter + $estiloComprimido);
$r->addCell(null, ['bgColor' => 'F2F2F2', 'valign' => 'center'])->addText("NOMBRE", $fontTable, $alignCenter + $estiloComprimido);

$asistentes = [
    ["1", "Decano/Delegado", limpiarTextoXML($datos['miembro_1_nombre'] ?? '')],
    ["2", "Jefe del Departamento", limpiarTextoXML($datos['miembro_2_nombre'] ?? '')],
    ["3", "Coordinador programa", limpiarTextoXML($datos['miembro_3_nombre'] ?? '')],
    ["4", "Profesor de planta", limpiarTextoXML($datos['miembro_4_nombre'] ?? '')],
    ["5", "Profesor de planta", limpiarTextoXML($datos['miembro_5_nombre'] ?? '')]
];
foreach ($asistentes as $asis) {
    $rowAsis = $tableAsis->addRow($altoMinimo);
    $rowAsis->addCell()->addText($asis[0], $fontTable, $alignCenter + $estiloComprimido);
    $rowAsis->addCell()->addText($asis[1], $fontTable, $estiloComprimido);
    $rowAsis->addCell()->addText($asis[2], $fontTable, $estiloComprimido);
}

$celdaDesarrollo->addTextBreak(1);

// --- PUNTO 3: PERFILES ---
 $textRun = $celdaDesarrollo->addTextRun($estiloComprimido);
        $textRun->addText("3. Definición del Perfil o perfiles requeridos según la necesidad académica ", $fontBold);
        $textRun->addText("(Nivel Académico, Énfasis o Formación Particular y Experiencia)", $fontBoldItalic);

// Contenido HTML del punto 3 (editor libre) - SOLO SI EXISTE
if (!empty($datos['punto_3_perfiles'])) {
    $htmlSeguro = limpiarHTMLparaWordSeguro($datos['punto_3_perfiles']);
    try {
        \PhpOffice\PhpWord\Shared\Html::addHtml($celdaDesarrollo, $htmlSeguro, false, false);
    } catch (Exception $e) {
        $celdaDesarrollo->addText("[Error al mostrar contenido HTML]", ['color' => 'FF0000']);
    }
    $celdaDesarrollo->addTextBreak(0);
}

// Tabla de perfiles desde JSON
$perfiles_data = json_decode($datos['perfiles_json'] ?? '', true);
if (!empty($perfiles_data) && is_array($perfiles_data)) {
    $tableP = $celdaDesarrollo->addTable([
        'borderSize'  => 2,
        'borderColor' => '000000',
        'cellMargin'  => 40,
        'width'       => 100 * 50,
        'unit'        => TblWidth::PERCENT
    ]);
    // Encabezados
    $hP = $tableP->addRow($altoMinimo);
    $hP->addCell(Converter::cmToTwip(1.5), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
       ->addText("Id", $fontTable, $alignCenter + $estiloComprimido);
    $hP->addCell(Converter::cmToTwip(5.0), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
       ->addText("Perfil", $fontTable, $alignCenter + $estiloComprimido);
    $hP->addCell(Converter::cmToTwip(3.5), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
       ->addText("Nivel Máx. Formación", $fontTable, $alignCenter + $estiloComprimido);
    $hP->addCell(Converter::cmToTwip(3.5), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
       ->addText("Experiencia", $fontTable, $alignCenter + $estiloComprimido);
    $hP->addCell(Converter::cmToTwip(3.0), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
       ->addText("Productividad", $fontTable, $alignCenter + $estiloComprimido);

    $contador = 1;
    foreach ($perfiles_data as $p) {
        $row = $tableP->addRow($altoMinimo);
        $id_perfil = $p['id_perfil'] ?? (isset($p['item']) ? "Perfil " . $p['item'] : "Perfil " . $contador);        $perfil    = $p['perfil'] ?? $p['nombre'] ?? '';
        $nivel     = $p['nivel'] ?? '';
        $experiencia = $p['experiencia'] ?? '';
        $productividad = $p['productividad'] ?? '';

        $row->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($id_perfil), $fontTable, $alignCenter + $estiloComprimido);
        $row->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($perfil), $fontTable, $estiloComprimido);
        $row->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($nivel), $fontTable, $estiloComprimido);
        $row->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($experiencia), $fontTable, $estiloComprimido);
        $row->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($productividad), $fontTable, $estiloComprimido);
        $contador++;
    }
} elseif (empty($datos['punto_3_perfiles'])) {
    $celdaDesarrollo->addText("No se definieron perfiles.", $fontNormal, $estiloComprimido);
}

$celdaDesarrollo->addTextBreak(1);
// ---------- PUNTO 4: LISTADO NUMERADO DE ASPIRANTES (CON SANGRÍA MODERADA Y TAMAÑO 9) ----------
$textRun = $celdaDesarrollo->addTextRun($estiloComprimido);
        $textRun->addText("4. Consultar Banco de Aspirantes y revisar perfiles postulados al periodo a vincular ", $fontBold);
        $textRun->addText("(enlistar todos los profesores postulados que cumplen el perfil)", $fontBoldItalic);

// Estilo específico para la lista (tamaño 9)
$fontListBold   = ['name' => 'Arial', 'size' => 9, 'bold' => true];
$fontListNormal = ['name' => 'Arial', 'size' => 9];

$htmlP4 = $datos['punto_4_aspirantes'] ?? '';
if (!empty($htmlP4) && trim(strip_tags($htmlP4)) !== '') {
    // Limpieza básica
    $htmlP4 = stripslashes($htmlP4);
    $htmlP4 = html_entity_decode($htmlP4, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $htmlP4 = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $htmlP4);
    $htmlP4 = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $htmlP4);

    // Extraer todos los <li>
    preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $htmlP4, $matches);
    if (!empty($matches[1])) {
        $contador = 0;
        $limite = 200;
        
        // Estilo de párrafo con sangría izquierda de 0.25 cm (142 twips)
        $estiloListaConSangria = [
            'alignment'   => Jc::BOTH,
            'spaceAfter'  => 0,
            'spaceBefore' => 0,
            'lineHeight'  => 1.0,
            'indentation' => ['left' => 142] // ← Sintaxis correcta y valor moderado
        ];

        foreach ($matches[1] as $itemHtml) {
            if ($contador >= $limite) {
                $restantes = count($matches[1]) - $limite;
                $celdaDesarrollo->addText("... y $restantes aspirantes más.", $fontListNormal, $estiloListaConSangria);
                break;
            }

            // Extraer nombre (contenido de <strong>)
            preg_match('/<strong>(.*?)<\/strong>/i', $itemHtml, $nombreMatch);
            $nombre = trim($nombreMatch[1] ?? '');

            // Extraer cédula
            preg_match('/Cédula:\s*([\d\.]+)/i', $itemHtml, $cedulaMatch);
            $cedula = trim($cedulaMatch[1] ?? '');

            // Extraer títulos
            $titulos = '';
            if (preg_match('/:\s*(.*)$/', $itemHtml, $titulosMatch)) {
                $titulos = trim(strip_tags($titulosMatch[1]));
                if (!empty($cedula)) {
                    $titulos = preg_replace('/Cédula:\s*' . preg_quote($cedula, '/') . '\s*/i', '', $titulos);
                    $titulos = preg_replace('/\s*' . preg_quote($cedula, '/') . '\s*/', '', $titulos);
                }
                $titulos = preg_replace('/^\s*\):\s*/', '', $titulos);
                $titulos = preg_replace('/^\s*:\s*/', '', $titulos);
            }

            if (empty($nombre)) {
                $nombre = trim(strip_tags($itemHtml));
                $titulos = '';
            }

            // Limpiar caracteres especiales XML
            $nombre = limpiarTextoXML($nombre);
            $cedula = limpiarTextoXML($cedula);
            $titulos = limpiarTextoXML($titulos);

            // Construir párrafo con sangría
            $numero = $contador + 1;
            $textRun = $celdaDesarrollo->addTextRun($estiloListaConSangria);

            $textRun->addText($numero . ". ", $fontListBold);
            $textRun->addText($nombre, $fontListBold);
            if (!empty($cedula)) {
                $textRun->addText(" ($cedula)", $fontListNormal);
            }
            if (!empty($titulos)) {
                $textRun->addText(": $titulos", $fontListNormal);
            }

            $contador++;
        }
    } else {
        $celdaDesarrollo->addText(strip_tags($htmlP4), $fontListNormal, ['indentation' => ['left' => 142]]);
    }
} else {
    $celdaDesarrollo->addText("No se enlistaron aspirantes.", $fontListNormal, ['indentation' => ['left' => 142]]);
}
$celdaDesarrollo->addTextBreak(1);
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------

// --- PUNTO 5: CALIFICACIÓN DE HOJA DE VIDA (TABLA NATIVA) ---
// --- PUNTO 5: CALIFICACIÓN DE HOJA DE VIDA (TABLA NATIVA, TAMAÑO 8, VALORES ALINEADOS A DERECHA) ---
$textRun = $celdaDesarrollo->addTextRun($estiloComprimido);
    $textRun->addText("5. Calificación de hoja de vida ", $fontBold);
    $textRun->addText("(Acuerdo Superior 017 de 2009, art 7, “…Será competencia de los Consejos de Facultad establecer los criterios y ponderaciones para las calificaciones de las hojas de vida y los porcentajes para pruebas adicionales, si los hubiera…”.)", $fontBoldItalic);$contenidoP5 = $datos['punto_5_calificacion'] ?? '';

// Estilo específico para tabla de calificaciones: tamaño 8
$fontCalif = ['name' => 'Arial', 'size' => 8];

if (!empty($contenidoP5) && trim(strip_tags($contenidoP5)) !== '') {
    $contenidoP5 = stripslashes($contenidoP5);
    $contenidoP5 = html_entity_decode($contenidoP5, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $contenidoP5 = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $contenidoP5);
    $contenidoP5 = str_replace(['&nbsp;', "\xc2\xa0"], ' ', $contenidoP5);

    if (strpos($contenidoP5, '<table') !== false) {
        try {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $contenidoP5, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            libxml_use_internal_errors(false);

            $tablas = $dom->getElementsByTagName('table');
            if ($tablas->length > 0) {
                $tablaWord = $celdaDesarrollo->addTable([
                    'borderSize'  => 2,
                    'borderColor' => '000000',
                    'cellMargin'  => 40,
                    'width'       => 100 * 50,
                    'unit'        => TblWidth::PERCENT
                ]);

                $filas = $tablas->item(0)->getElementsByTagName('tr');
                $rowIndex = 0;
                foreach ($filas as $fila) {
                    $rowWord = $tablaWord->addRow($altoMinimo);
                    $celdas = $fila->childNodes;
                    $colIndex = 0;
                    foreach ($celdas as $celda) {
                        if ($celda->nodeName == 'td' || $celda->nodeName == 'th') {
                            $esEncabezado = ($rowIndex === 0) || ($celda->nodeName == 'th');
                            $bg = $esEncabezado ? 'F2F2F2' : null;
                            $texto = trim($celda->textContent);
                            
                            // Determinar alineación según tipo de columna
                            if ($esEncabezado) {
                                $alineacion = $alignCenter; // Encabezados centrados
                            } else {
                                // Celda de datos:
                                // - Primera columna (número) → centrada
                                // - Segunda columna (nombre) → izquierda (por defecto)
                                // - Demás columnas (valores, total) → derecha
                                if ($colIndex == 0) {
                                    $alineacion = $alignCenter;
                                } elseif ($colIndex == 1) {
                                    $alineacion = []; // alineación izquierda (valor por defecto)
                                } else {
                                    $alineacion = ['alignment' => Jc::RIGHT];
                                }
                            }

                            $rowWord->addCell(null, ['bgColor' => $bg, 'valign' => 'center'])
                                ->addText(limpiarTextoXML($texto), $fontCalif, $alineacion + $estiloComprimido);
                            $colIndex++;
                        }
                    }
                    $rowIndex++;
                }
            } else {
                $celdaDesarrollo->addText(strip_tags($contenidoP5), $fontNormal);
            }
        } catch (Exception $e) {
            $celdaDesarrollo->addText("[Error al procesar tabla de calificaciones]", ['color' => 'FF0000']);
        }
    } else {
        $celdaDesarrollo->addText(strip_tags($contenidoP5), $fontNormal);
    }
} else {
    $celdaDesarrollo->addText("No aplica", $fontNormal);
}
$celdaDesarrollo->addTextBreak(1);

// --- PUNTO 6: ENTREVISTA ---
$celdaDesarrollo->addText("6. Entrevista (opcional)", $fontBold, $estiloComprimido);
$htmlP6 = limpiarHTMLparaWordSeguro($datos['punto_6_entrevista'] ?? '');
if (trim(strip_tags($htmlP6)) !== '<p>No aplica</p>') {
    try {
        \PhpOffice\PhpWord\Shared\Html::addHtml($celdaDesarrollo, $htmlP6, false, false);
    } catch (Exception $e) {
        $celdaDesarrollo->addText("No aplica", $fontNormal);
    }
} else {
    $celdaDesarrollo->addText("No aplica", $fontNormal);
}
$celdaDesarrollo->addTextBreak(1);

// --- PUNTO 7: SELECCIÓN DE PROFESORES (TABLA NATIVA) ---
$textRun = $celdaDesarrollo->addTextRun($estiloComprimido);
    $textRun->addText("7. Selección de los profesores a solicitar vinculación ", $fontBold);
    $textRun->addText("(enlistar todos los profesores que se van a solicitar para ser vinculados en el periodo académico, con nombre completo y número de identificación)", $fontBoldItalic);
   
$tableProfs = $celdaDesarrollo->addTable([
    'borderSize' => 2,
    'cellMargin' => 40,
    'width'      => 100 * 50,
    'unit'       => TblWidth::PERCENT
]);
$h = $tableProfs->addRow($altoMinimo);
$h->addCell(Converter::cmToTwip(7), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
  ->addText("Nombre Completo", $fontTable, $alignCenter + $estiloComprimido);
$h->addCell(Converter::cmToTwip(3), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
  ->addText("Identificación", $fontTable, $alignCenter + $estiloComprimido);
$h->addCell(Converter::cmToTwip(6), ['bgColor' => 'F2F2F2', 'valign' => 'center'])
  ->addText("Dedicación", $fontTable, $alignCenter + $estiloComprimido);

$numero_acta_actual = $datos['numero_acta'] ?? '';
$sql_p = "SELECT s1.nombre, s1.cedula, s1.tipo_docente,
                 s1.tipo_dedicacion, s1.tipo_dedicacion_r,
                 s1.horas, s1.horas_r
          FROM solicitudes_working_copy s1
          WHERE s1.departamento_id = ?
            AND s1.anio_semestre = ?
            AND s1.novedad = 'adicionar'
            AND ((s1.estado_depto = 'PENDIENTE') OR (s1.numero_acta59 = ?))
            AND (s1.archivado = 0 OR s1.archivado IS NULL)
            AND NOT EXISTS (
                SELECT 1 FROM solicitudes_working_copy s2
                WHERE s2.cedula = s1.cedula
                  AND s2.departamento_id = s1.departamento_id
                  AND s2.anio_semestre = s1.anio_semestre
                  AND s2.novedad = 'Eliminar'
                  AND s2.estado_depto = 'PENDIENTE'
            )
          ORDER BY s1.tipo_docente DESC, s1.nombre ASC";
$stmt_p = $conn->prepare($sql_p);
$stmt_p->bind_param("sss", $departamento_id, $anio_semestre, $numero_acta_actual);
$stmt_p->execute();
$res_p = $stmt_p->get_result();

while ($p = $res_p->fetch_assoc()) {
    $rowP = $tableProfs->addRow($altoMinimo);
    $rowP->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($p['nombre'] ?? ''), $fontTable, $estiloComprimido);
    $rowP->addCell(null, ['valign' => 'center'])->addText(limpiarTextoXML($p['cedula'] ?? ''), $fontTable, $alignCenter + $estiloComprimido);
    $partes = [];
    if ($p['tipo_docente'] == 'Ocasional') {
        if (!empty($p['tipo_dedicacion'])) {
            $sigla = ($p['tipo_dedicacion'] == 'TC') ? 'OTC' : 'OMT';
            $partes[] = limpiarTextoXML($sigla . " (Pop)");
        }
        if (!empty($p['tipo_dedicacion_r'])) {
            $sigla = ($p['tipo_dedicacion_r'] == 'TC') ? 'OTC' : 'OMT';
            $partes[] = limpiarTextoXML($sigla . " (Reg)");
        }
    } elseif ($p['tipo_docente'] == 'Catedra') {
        if (!empty($p['horas']) && $p['horas'] > 0) $partes[] = limpiarTextoXML((float)$p['horas'] . " HRS Pop");
        if (!empty($p['horas_r']) && $p['horas_r'] > 0) $partes[] = limpiarTextoXML((float)$p['horas_r'] . " HRS Reg");
    }
    $textoFinal = implode(" / ", $partes);
    $rowP->addCell(null, ['valign' => 'center'])->addText($textoFinal, $fontTable, $alignCenter + $estiloComprimido);
}
// ------------------------------------------------------------
// 10. FIRMAS (CON ANCHOS AJUSTADOS Y DISEÑO FIJO)
// ------------------------------------------------------------
$section->addTextBreak(1);
$section->addText("COMITÉ DE SELECCIÓN", $fontBold);

// --- CÁLCULO DE ANCHOS (Total aproximado para hoja A4 con márgenes de 2cm es ~17cm) ---
$anchoNo     = Converter::cmToTwip(0.8);  // Columna pequeña para el número
$anchoCargo  = Converter::cmToTwip(5.0);  // Reducido (antes era 6.0)
$anchoNombre = Converter::cmToTwip(6.0);  // Espacio estándar para nombres
$anchoFirma  = Converter::cmToTwip(5.2);  // Aumentado para que sea más ancha

$tableFirmas = $section->addTable([
    'borderSize'  => 1,
    'borderColor' => '000000',
    'cellMargin'  => 40,
    'width'       => 100 * 50,
    'unit'        => TblWidth::PERCENT,
    'layout'      => 'fixed' // OBLIGA a respetar los anchos y evita desbordes
]);
// Si no los tienes definidos arriba:
$fontBold = ['name' => 'Arial', 'size' => 9, 'bold' => true];
$alignCenter = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceBefore' => 0, 'spaceAfter' => 0];
// --- ENCABEZADO DE FIRMAS ---
// Usamos una altura de 0.5 o 0.6 cm
$hF = $tableFirmas->addRow(Converter::cmToTwip(0.6)); 

$hF->addCell($anchoNo,     ['valign' => 'center'])->addText("No", $fontBold, $alignCenter);
$hF->addCell($anchoCargo,  ['valign' => 'center'])->addText("CARGO", $fontBold, $alignCenter);
$hF->addCell($anchoNombre, ['valign' => 'center'])->addText("NOMBRE Y APELLIDO", $fontBold, $alignCenter);
$hF->addCell($anchoFirma,  ['valign' => 'center'])->addText("FIRMA (manuscrita)", $fontBold, $alignCenter);
$miembros = [
    ["1", "Decano/Delegado", limpiarTextoXML($datos['miembro_1_nombre'] ?? '')],
    ["2", "Jefe del Departamento", limpiarTextoXML($datos['miembro_2_nombre'] ?? '')],
    ["3", "Un Coordinador programa (si el coordinador es un profesor ocasional, debe delegar a un profesor de planta)", limpiarTextoXML($datos['miembro_3_nombre'] ?? '')],
    ["4", "Profesor de planta", limpiarTextoXML($datos['miembro_4_nombre'] ?? '')],
    ["5", "Profesor de planta", limpiarTextoXML($datos['miembro_5_nombre'] ?? '')]
];

// --- FILAS DE MIEMBROS ---
foreach ($miembros as $m) {
    // Si el texto es el del Coordinador (punto 3), permitimos que la fila crezca en alto
    $altoFila = ($m[0] == "3") ? null : Converter::cmToTwip(0.7);
    
    $rowF = $tableFirmas->addRow($altoFila); 
    $rowF->addCell($anchoNo,     ['valign' => 'center'])->addText($m[0], $fontSmall, $alignCenter);
    
    // Cargo: Hará salto de línea si es muy largo
    $rowF->addCell($anchoCargo,  ['valign' => 'center'])->addText($m[1], $fontSmall, $estiloComprimido);
    
    $rowF->addCell($anchoNombre, ['valign' => 'center'])->addText($m[2], $fontSmall, $estiloComprimido);
    
    // Firma: Columna ahora más ancha
    $rowF->addCell($anchoFirma,  ['valign' => 'center'])->addText("", $fontSmall);
}
// ------------------------------------------------------------
// 11. COMPROMISOS (ANCHOS FIJOS, ALTO VARIABLE EN FILAS CON DATOS, ALTO FIJO EN VACÍAS)
// ------------------------------------------------------------
$compromisos = json_decode($datos['compromisos_json'] ?? '', true);
if (!is_array($compromisos)) $compromisos = [];

$numCompromisos = count($compromisos);
$filasMostrar = max(5, $numCompromisos); // Mínimo 5 filas visibles

$section->addTextBreak(1);
$section->addText("COMPROMISOS", $fontBold);

// --- ANCHOS FIJOS (en twips, usando Converter::cmToTwip) ---
$wNo    = Converter::cmToTwip(0.8);
$wDesc  = Converter::cmToTwip(6.0);
$wResp  = Converter::cmToTwip(3.5);
$wFechaComp = Converter::cmToTwip(3.0);
$wFechaReal = Converter::cmToTwip(3.0);

$tablaComp = $section->addTable([
    'borderSize'  => 2,
    'borderColor' => '000000',
    'cellMargin'  => 40,
    'width'       => 100 * 50,
    'unit'        => TblWidth::PERCENT,
    'layout'      => 'fixed'
]);

// --- ENCABEZADOS (alto fijo, negrita) ---
// --- ENCABEZADOS CORREGIDOS (Sección 11) ---
// Se aumenta la altura a 0.8 cm y se quita 'exactHeight' para permitir centrado vertical real
// Estilo para celdas con poco espacio vertical
$estiloHeaderTabla = [
    'spaceBefore' => 0, 
    'spaceAfter'  => 0, 
    'lineHeight'  => 1.0, 
    'alignment'   => Jc::CENTER
];
$altoEncabezadoReducido = Converter::cmToTwip(0.6); 
$hdr = $tablaComp->addRow($altoEncabezadoReducido); 

// No.
$hdr->addCell($wNo, ['valign' => 'center'])->addText("No.", $fontBold, $estiloHeaderTabla);

// Compromiso
$hdr->addCell($wDesc, ['valign' => 'center'])->addText("COMPROMISO", $fontBold, $estiloHeaderTabla);

// Responsable
$hdr->addCell($wResp, ['valign' => 'center'])->addText("RESPONSABLE", $fontBold, $estiloHeaderTabla);

// Fecha compromiso (Usamos una fuente un punto más pequeña para que quepa en 0.6cm)
$fontHdrSmall = ['name' => 'Arial', 'size' => 9, 'bold' => true];

$celdaFC = $hdr->addCell($wFechaComp, ['valign' => 'center']);
$celdaFC->addText("FECHA", $fontHdrSmall, $estiloHeaderTabla);
$celdaFC->addText("COMPROMISO", $fontHdrSmall, $estiloHeaderTabla);

// Fecha realización
$celdaFR = $hdr->addCell($wFechaReal, ['valign' => 'center']);
$celdaFR->addText("FECHA", $fontHdrSmall, $estiloHeaderTabla);
$celdaFR->addText("REALIZACIÓN", $fontHdrSmall, $estiloHeaderTabla);

// --- FILAS DE DATOS Y RELLENO ---
for ($i = 1; $i <= $filasMostrar; $i++) {
    if ($i <= $numCompromisos) {
        // --- FILA CON DATOS: ALTURA VARIABLE (se expande si el texto es largo) ---
        $row = $tablaComp->addRow();
    } else {
        // --- FILA VACÍA (RELLENO): ALTURA MÍNIMA FIJA ---
        $row = $tablaComp->addRow($altoMinimo, ['exactHeight' => true]);
    }
    
    // Número consecutivo
    $row->addCell($wNo, ['valign' => 'center'])->addText($i, $fontTable, $alignCenter + $estiloComprimido);
    
    if ($i <= $numCompromisos) {
        $c = $compromisos[$i - 1];
        $row->addCell($wDesc, ['valign' => 'center'])->addText(limpiarTextoXML($c['desc'] ?? ''), $fontTable, $estiloComprimido);
        $row->addCell($wResp, ['valign' => 'center'])->addText(limpiarTextoXML($c['resp'] ?? ''), $fontTable, $estiloComprimido);
        $row->addCell($wFechaComp, ['valign' => 'center'])->addText(limpiarTextoXML($c['fecha_compromiso'] ?? ''), $fontTable, $alignCenter + $estiloComprimido);
        $row->addCell($wFechaReal, ['valign' => 'center'])->addText(limpiarTextoXML($c['fecha_realizacion'] ?? ''), $fontTable, $alignCenter + $estiloComprimido);
    } else {
        // Filas vacías: celdas vacías, altura fija
        $row->addCell($wDesc, ['valign' => 'center'])->addText("", $fontTable);
        $row->addCell($wResp, ['valign' => 'center'])->addText("", $fontTable);
        $row->addCell($wFechaComp, ['valign' => 'center'])->addText("", $fontTable);
        $row->addCell($wFechaReal, ['valign' => 'center'])->addText("", $fontTable);
    }
}
    // ------------------------------------------------------------
    // 12. OBSERVACIONES
    // ------------------------------------------------------------
  // ------------------------------------------------------------
// 12. OBSERVACIONES
// ------------------------------------------------------------
$section->addTextBreak(1);
$section->addText("OBSERVACIONES", $fontBold);
$tableObs = $section->addTable([
    'borderSize'  => 2,
    'borderColor' => '000000',
    'width'       => 100 * 50,
    'unit'        => TblWidth::PERCENT
]);
$rowObs = $tableObs->addRow(Converter::cmToTwip(1.5), ['exactHeight' => true]);
$textoObs = limpiarTextoXML($datos['observaciones'] ?? '');
$rowObs->addCell()->addText($textoObs, $fontNormal);
    // ------------------------------------------------------------
    // 13. NOTA FINAL
    // ------------------------------------------------------------
    $section->addTextBreak(1);
    $section->addText(
        "NOTA: este documento no debe ser modificado, de lo contrario no es válido para el proceso de selección de vinculación de profesores temporales.",
        $fontNormal
    );

    // ------------------------------------------------------------
    // 14. LIMPIAR BUFFER Y ENVIAR ARCHIVO
    // ------------------------------------------------------------
    ob_end_clean();

    $filename = "Acta_Novedades_FOR59_" . $departamento_id . ".docx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;

} catch (Exception $e) {
    ob_end_clean();
    error_log("Error FATAL en generar_word_novedades.php: " . $e->getMessage());
    echo "<h3 style='color:red;'>Error al generar el documento Word.</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>