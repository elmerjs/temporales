<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once 'cn.php';

// --- 1. Obtener y validar parámetros ---
$periodo = isset($_GET['periodo']) ? mysqli_real_escape_string($con, $_GET['periodo']) : die("Periodo requerido");
if (!preg_match('/^\d{4}-\d$/', $periodo)) {
    die("Formato de periodo inválido (YYYY-S)");
}

$facultad_seleccionada = $_GET['facultad'] ?? 'all'; // 'all' si no se selecciona ninguna

// --- 2. Construir la consulta SQL dinámicamente ---
$sql = "SELECT
            f.nombre_fac_min,
            d.depto_nom_propio,
            og.version_glosa,
            og.numero_oficio,
            og.fecha_oficio,
            og.descripcion as descripcion_oficio,
            g.Tipo_glosa,
            SUM(g.cantidad_glosas) AS cantidad_glosas
        FROM depto_periodo dp
        JOIN deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
        JOIN facultad f ON f.PK_FAC = d.FK_FAC
        LEFT JOIN glosas g ON g.fk_dp_glosa = dp.id_depto_periodo
        LEFT JOIN oficios_glosas og ON og.fk_dp_glosa = g.fk_dp_glosa AND og.version_glosa = g.version_glosa
        WHERE dp.periodo = '$periodo'"; // Empezamos con la condición de periodo

// Añadir la condición de facultad si no es 'all'
if ($facultad_seleccionada !== 'all') {
    // Es CRÍTICO sanear $facultad_seleccionada. Si sabes que es un entero, puedes usar intval.
    // Si no, mysqli_real_escape_string es esencial, pero idealmente se usarían sentencias preparadas.
    $facultad_id_saneada = mysqli_real_escape_string($con, $facultad_seleccionada);
    $sql .= " AND f.PK_FAC = '$facultad_id_saneada'"; // Agregamos la condición de facultad
}

// Continuar con el resto de la consulta (GROUP BY, HAVING, ORDER BY)
$sql .= " GROUP BY
            f.nombre_fac_min,
            d.depto_nom_propio,
            og.version_glosa,
            og.numero_oficio,
            og.fecha_oficio,
            og.descripcion,
            g.Tipo_glosa
        HAVING cantidad_glosas > 0
        ORDER BY f.nombre_fac_min, d.depto_nom_propio, og.version_glosa DESC";

// --- 3. Ejecutar la consulta ---
$result = mysqli_query($con, $sql);
if (!$result) {
    die("Error en consulta: " . mysqli_error($con) . " <br>SQL: " . $sql); // Mostrar la SQL para depuración
}

// --- 4. Crear nuevo Spreadsheet (El resto de tu código para generar el Excel) ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// Estilos
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004080']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];

$versionHeaderStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6E6E6']],
    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]]
];

$facultadStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => '004080']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']]
];

// Configurar hoja
$sheet->setTitle("Observaciones $periodo");
$sheet->getDefaultColumnDimension()->setWidth(15);

// Encabezado principal
$sheet->setCellValue('A1', 'UNIVERSIDAD DEL CAUCA - REPORTE DE OBSERVACIONES');
$sheet->mergeCells('A1:D1');
$sheet->getStyle('A1')->applyFromArray($headerStyle);

$sheet->setCellValue('A2', "PERIODO: $periodo");
$sheet->mergeCells('A2:D2');
$sheet->getStyle('A2')->getFont()->setBold(true);

// Encabezados de columnas
$sheet->setCellValue('A4', 'Facultad')->getStyle('A4:D4')->applyFromArray($headerStyle);
$sheet->setCellValue('B4', 'Departamento');
$sheet->setCellValue('C4', 'Tipo Observación');
$sheet->setCellValue('D4', 'Cantidad');

// Llenar datos
$row = 5;
$current_facultad = '';
$current_depto = '';
$current_version = '';
$total_general = 0;

while ($data = mysqli_fetch_assoc($result)) {
    // Nueva facultad
    if ($current_facultad != $data['nombre_fac_min']) {
        if ($current_facultad != '') {
            // Agregar subtotal facultad
            $sheet->setCellValue("A$row", "Subtotal $current_facultad");
            $sheet->mergeCells("A$row:C$row");
            $sheet->setCellValue("D$row", $subtotal_facultad);
            $sheet->getStyle("A$row:D$row")->applyFromArray($facultadStyle);
            $row++;
        }
        $current_facultad = $data['nombre_fac_min'];
        $subtotal_facultad = 0;
        $current_depto = '';
    }
    
    // Nuevo departamento
    if ($current_depto != $data['depto_nom_propio']) {
        if ($current_depto != '') {
            // Agregar subtotal departamento
            $sheet->setCellValue("A$row", "Subtotal $current_depto");
            $sheet->mergeCells("A$row:C$row");
            $sheet->setCellValue("D$row", $subtotal_depto);
            $sheet->getStyle("A$row:D$row")->getFont()->setItalic(true);
            $row++;
        }
        $current_depto = $data['depto_nom_propio'];
        $subtotal_depto = 0;
        $current_version = '';
    }
    
    // Nueva versión
    if ($current_version != $data['version_glosa']) {
        if ($current_version !== '') {
            $row++; // Espacio entre versiones
        }
        
        $current_version = $data['version_glosa'];
        
        // Encabezado de versión
        $sheet->setCellValue("A$row", "VERSIÓN: ".$data['version_glosa']);
        $sheet->setCellValue("B$row", "OFICIO: ".$data['numero_oficio']);
        $sheet->setCellValue("C$row", "FECHA: ".$data['fecha_oficio']);
        $sheet->mergeCells("C$row:D$row");
        $sheet->getStyle("A$row:D$row")->applyFromArray($versionHeaderStyle);
        $row++;
        
        // Descripción del oficio
        if (!empty($data['descripcion_oficio'])) {
            $sheet->setCellValue("A$row", "DESCRIPCIÓN:");
            $sheet->mergeCells("B$row:D$row");
            $sheet->setCellValue("B$row", $data['descripcion_oficio']);
            $sheet->getStyle("A$row:D$row")->getFont()->setItalic(true);
            $row++;
        }
    }
    
    // Datos de glosas
    $sheet->setCellValue("A$row", $data['nombre_fac_min']);
    $sheet->setCellValue("B$row", $data['depto_nom_propio']);
    $sheet->setCellValue("C$row", $data['Tipo_glosa']);
    $sheet->setCellValue("D$row", $data['cantidad_glosas']);
    
    $subtotal_depto += $data['cantidad_glosas'];
    $subtotal_facultad += $data['cantidad_glosas'];
    $total_general += $data['cantidad_glosas'];
    $row++;
}

// Agregar últimos subtotales
if ($current_depto != '') {
    $sheet->setCellValue("A$row", "Subtotal $current_depto");
    $sheet->mergeCells("A$row:C$row");
    $sheet->setCellValue("D$row", $subtotal_depto);
    $sheet->getStyle("A$row:D$row")->getFont()->setItalic(true);
    $row++;
}

if ($current_facultad != '') {
    $sheet->setCellValue("A$row", "Subtotal $current_facultad");
    $sheet->mergeCells("A$row:C$row");
    $sheet->setCellValue("D$row", $subtotal_facultad);
    $sheet->getStyle("A$row:D$row")->applyFromArray($facultadStyle);
    $row++;
}

// Total general
$sheet->setCellValue("A$row", "TOTAL GENERAL");
$sheet->mergeCells("A$row:C$row");
$sheet->setCellValue("D$row", $total_general);
$sheet->getStyle("A$row:D$row")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004080']]
]);

// Autoajustar columnas
foreach(range('A','D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Configurar respuesta
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_observaciones_'.$periodo.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;