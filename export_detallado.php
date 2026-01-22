<?php
// Configuración de errores para depuración (eliminar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ==============================================================================
// 1. CARGAR PHPSPREADSHEET PARA GENERAR EXCEL
// ==============================================================================

require 'vendor/autoload.php'; // Asegúrate de que PhpSpreadsheet esté instalado via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ==============================================================================
// 2. CONEXIÓN A LA BASE DE DATOS
// ==============================================================================

$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// ==============================================================================
// 3. OBTENCIÓN DE PARÁMETROS
// ==============================================================================

$p2_actual = isset($_GET['p2']) ? $conn->real_escape_string($_GET['p2']) : null;
$p1_anterior = isset($_GET['p1']) ? $conn->real_escape_string($_GET['p1']) : null;

if (!$p2_actual || !$p1_anterior) {
    die("Error: Faltan parámetros de período (p2 o p1).");
}

$filename = "Detalle_Comparativo_Historico_" . $p2_actual . "_vs_" . $p1_anterior . ".xlsx";

// ==============================================================================
// 4. CONSULTA DE PERIODOS HISTÓRICOS Y DATOS BASE
// ==============================================================================

// Obtener todos los periodos históricos para las columnas dinámicas
$sql_periodos = "SELECT nombre_periodo FROM periodo ORDER BY nombre_periodo ASC";
$res_periodos = $conn->query($sql_periodos);
$periodos = [];
while ($row = $res_periodos->fetch_assoc()) {
    $periodos[] = $row['nombre_periodo'];
}

// Query principal para obtener la data base de la tabla detallada
$sql_base = "
    SELECT
        s.cedula,
        s.nombre AS 'Nombre Docente',
        (SELECT f.NOMBREF_FAC FROM facultad f WHERE f.PK_FAC = s.facultad_id) AS 'Facultad',
        (SELECT d.NOMBRE_DEPTO FROM Deparmanentos d WHERE d.PK_DEPTO = s.departamento_id) AS 'Departamento',
        
        -- DETALLE P2 (CONSOLIDACIÓN)
        COALESCE(
            (CASE s2.tipo_docente
                WHEN 'Catedra' THEN CONCAT(COALESCE(s2.horas, 0) + COALESCE(s2.horas_r, 0), 'h')
                ELSE COALESCE(NULLIF(s2.tipo_dedicacion_r, ''), s2.tipo_dedicacion)
            END), 
            'NO VINCULADO'
        ) AS 'Detalle_P2',
        
        -- DETALLE P1 (CONSOLIDACIÓN)
        COALESCE(
            (CASE s1.tipo_docente
                WHEN 'Catedra' THEN CONCAT(COALESCE(s1.horas, 0) + COALESCE(s1.horas_r, 0), 'h')
                ELSE COALESCE(NULLIF(s1.tipo_dedicacion_r, ''), s1.tipo_dedicacion)
            END), 
            'NO VINCULADO'
        ) AS 'Detalle_P1',
        
        -- CLASIFICACIÓN BASE
        CASE
            WHEN s2.tipo_docente IS NULL AND s1.tipo_docente IS NULL THEN 'NO VINCULADO -> NO VINCULADO'
            WHEN s2.tipo_docente IS NOT NULL AND s1.tipo_docente IS NULL THEN 'NUEVO'
            WHEN s2.tipo_docente IS NULL AND s1.tipo_docente IS NOT NULL THEN 'SALE'
            ELSE 'REVISAR'
        END AS 'Clasificacion_Base'
        
    FROM Solicitudes s
    LEFT JOIN Solicitudes s2 ON s.cedula = s2.cedula AND s2.anio_semestre = '$p2_actual'
    LEFT JOIN Solicitudes s1 ON s.cedula = s1.cedula AND s1.anio_semestre = '$p1_anterior'
    
    WHERE s.anio_semestre = '$p2_actual' OR s.anio_semestre = '$p1_anterior'
    GROUP BY s.cedula
    ORDER BY Departamento, s.nombre
";

$res_base = $conn->query($sql_base);

if ($res_base === false) {
    die("Error en la consulta base: " . $conn->error);
}

$data_base = [];
$cedulas = [];
while ($row = $res_base->fetch_assoc()) {
    $data_base[] = $row;
    $cedulas[] = $row['cedula'];
}

if (empty($cedulas)) {
    die("No se encontraron docentes para el comparativo.");
}

// Obtener las dedicaciones históricas de TODOS los docentes encontrados
$cedula_list = "'" . implode("','", $cedulas) . "'";
$periodo_list = "'" . implode("','", $periodos) . "'";

$sql_historico = "
    SELECT 
        cedula, 
        anio_semestre, 
        (CASE tipo_docente
            WHEN 'Catedra' THEN CONCAT(COALESCE(horas, 0) + COALESCE(horas_r, 0), 'h')
            ELSE COALESCE(NULLIF(tipo_dedicacion_r, ''), tipo_dedicacion)
        END) AS Dedicacion_Consolidada
    FROM Solicitudes
    WHERE cedula IN ($cedula_list)
    AND anio_semestre IN ($periodo_list)
";

$res_historico = $conn->query($sql_historico);
$historico_map = [];
while ($row = $res_historico->fetch_assoc()) {
    $cedula = $row['cedula'];
    $periodo = $row['anio_semestre'];
    $dedicacion = $row['Dedicacion_Consolidada'];
    $historico_map[$cedula][$periodo] = $dedicacion;
}

// ==============================================================================
// 5. CREACIÓN DEL ARCHIVO EXCEL
// ==============================================================================

// Crear nuevo documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir los encabezados
$headers_base = ['Cédula', 'Nombre Docente', 'Facultad', 'Departamento', 'Detalle ' . $p2_actual, 'Detalle ' . $p1_anterior, 'Clasificación (' . $p2_actual . ' vs ' . $p1_anterior . ')'];
$final_headers = array_merge($headers_base, $periodos);

// Escribir encabezados
$col = 'A';
foreach ($final_headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Estilo para encabezados
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:' . $col . '1')->applyFromArray($headerStyle);

// Escribir datos
$row = 2;
foreach ($data_base as $data) {
    $cedula = $data['cedula'];
    
    $detalle_p2 = $data['Detalle_P2'];
    $detalle_p1 = $data['Detalle_P1'];
    $clasificacion = $data['Clasificacion_Base'];
    
    // Determinar clasificación final
    if ($clasificacion === 'REVISAR') {
        if ($detalle_p2 === $detalle_p1) {
            $clasificacion = 'MISMA DEDICACIÓN';
        } else {
            $clasificacion = 'CAMBIA DEDICACIÓN';
        }
    }

    // Datos base
    $sheet->setCellValue('A' . $row, $data['cedula']);
    $sheet->setCellValue('B' . $row, $data['Nombre Docente']);
    $sheet->setCellValue('C' . $row, $data['Facultad']);
    $sheet->setCellValue('D' . $row, $data['Departamento']);
    $sheet->setCellValue('E' . $row, $detalle_p2);
    $sheet->setCellValue('F' . $row, $detalle_p1);
    $sheet->setCellValue('G' . $row, $clasificacion);

    // Datos históricos
    $hist_col = 'H';
    foreach ($periodos as $periodo) {
        $status = $historico_map[$cedula][$periodo] ?? 'NO VINCULADO';
        $sheet->setCellValue($hist_col . $row, $status);
        $hist_col++;
    }
    
    $row++;
}

// Aplicar bordes a todos los datos
$last_col = chr(ord('A') + count($final_headers) - 1);
$dataStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:' . $last_col . ($row-1))->applyFromArray($dataStyle);

// Congelar paneles (fijar encabezados)
$sheet->freezePane('A2');

// ==============================================================================
// 6. ENVÍO DEL ARCHIVO EXCEL
// ==============================================================================

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit();
?>