<?php
require 'vendor/autoload.php'; // Asegúrate de tener este archivo si usas Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'conn.php'; // Tu archivo de conexión a la BD

$anio_semestre = $_POST['anio_semestre'] ?? '';
$anio_semestre_anterior = $_POST['anio_semestre_anterior'] ?? '';

// Ejecutar la misma consulta que ya tienes arriba
// Puedes copiar y pegar tu SQL aquí, adaptándolo como consulta normal con mysqli o PDO

// Ejemplo rápido con mysqli:
$sql = "SELECT 
  d.PK_DEPTO,
  f.NOMBREC_FAC AS facultad,
  d.NOMBRE_DEPTO_CORT AS departamento,
  t.tipo_docente AS tipo,
  dp.dp_analisis, dp.dp_devolucion, dp.dp_visado,
  -- Periodo actual ($anio_semestre)
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) AS total_actual,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_tc ELSE 0 END) AS TC_actual,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_mt ELSE 0 END) AS MT_actual,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_horas ELSE 0 END) AS horas_periodo,
  -- Periodo anterior ($anio_semestre_anterior)
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) AS total_anterior,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_tc ELSE 0 END) AS TC_anterior,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_mt ELSE 0 END) AS MT_anterior,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_horas ELSE 0 END) AS horas_anterior,
  -- Diferencias
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_profesores ELSE 0 END) -
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_profesores ELSE 0 END) AS dif_total,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_tc ELSE 0 END) -
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_tc ELSE 0 END) AS dif_tc,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_mt ELSE 0 END) -
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_mt ELSE 0 END) AS dif_mt,
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre' THEN t.total_horas ELSE 0 END) -
  SUM(CASE WHEN t.anio_semestre = '$anio_semestre_anterior' THEN t.total_horas ELSE 0 END) AS dif_horas
FROM (
  SELECT 
    anio_semestre,
    facultad_id,
    departamento_id,
    tipo_docente,
    COUNT(DISTINCT cedula) AS total_profesores,
    SUM(CASE 
          WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'TC' OR tipo_dedicacion_r = 'TC') THEN 1
          ELSE 0 
        END) AS total_tc,
    SUM(CASE 
          WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'MT' OR tipo_dedicacion_r = 'MT') THEN 1
          ELSE 0 
        END) AS total_mt,
    SUM(CASE 
          WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'TC' OR tipo_dedicacion_r = 'TC') THEN 40
          WHEN tipo_docente = 'Ocasional' AND (tipo_dedicacion = 'MT' OR tipo_dedicacion_r = 'MT') THEN 20
          WHEN tipo_docente = 'Catedra' THEN COALESCE(horas, 0) + COALESCE(horas_r, 0)
          ELSE 0
        END) AS total_horas
  FROM solicitudes
  WHERE anio_semestre IN ('$anio_semestre', '$anio_semestre_anterior')
  GROUP BY anio_semestre, facultad_id, departamento_id, tipo_docente
) AS t
JOIN deparmanentos d ON d.PK_DEPTO = t.departamento_id
JOIN facultad f ON f.PK_FAC = d.FK_FAC
LEFT JOIN depto_periodo dp 
  ON dp.fk_depto_dp = t.departamento_id 
 AND dp.periodo = '$anio_semestre' 
GROUP BY t.facultad_id, t.departamento_id, t.tipo_docente, dp.dp_analisis,dp.dp_devolucion,dp.dp_visado  
ORDER BY f.nombre_fac_min, d.depto_nom_propio, t.tipo_docente";


$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Escribe encabezados
$headers = ['Facultad', 'Departamento', 'Tipo', 'Total Actual', 'TC Actual', 'MT Actual', 'Horas Actual',
            'Total Anterior', 'TC Anterior', 'MT Anterior', 'Horas Anterior', 
            'Dif Total', 'Dif TC', 'Dif MT', 'Dif Horas', 'Nota', 'Devolucion', 'Visado'];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Ajustar anchos de columna personalizados
$sheet->getColumnDimension('B')->setWidth(20);  // Departamento
$sheet->getColumnDimension('P')->setWidth(40);  // Nota

// Escribe los datos
$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $rowNum, $row['facultad']);
    $sheet->setCellValue('B' . $rowNum, $row['departamento']);
    $sheet->setCellValue('C' . $rowNum, $row['tipo']);
    $sheet->setCellValue('D' . $rowNum, $row['total_actual']);
    $sheet->setCellValue('E' . $rowNum, $row['TC_actual']);
    $sheet->setCellValue('F' . $rowNum, $row['MT_actual']);
    $sheet->setCellValue('G' . $rowNum, $row['horas_periodo']);
    $sheet->setCellValue('H' . $rowNum, $row['total_anterior']);
    $sheet->setCellValue('I' . $rowNum, $row['TC_anterior']);
    $sheet->setCellValue('J' . $rowNum, $row['MT_anterior']);
    $sheet->setCellValue('K' . $rowNum, $row['horas_anterior']);
    $sheet->setCellValue('L' . $rowNum, $row['dif_total']);
    $sheet->setCellValue('M' . $rowNum, $row['dif_tc']);
    $sheet->setCellValue('N' . $rowNum, $row['dif_mt']);
    $sheet->setCellValue('O' . $rowNum, $row['dif_horas']);
    $sheet->setCellValue('P' . $rowNum, $row['dp_analisis']); // Nota al final
     $sheet->setCellValue('Q' . $rowNum, $row['dp_devolucion']); 
     $visado = ($row['dp_visado'] == 1) ? 'SI' : 'NO';
    $sheet->setCellValue('R' . $rowNum, $visado);
    $rowNum++;
}

// Exporta
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="comparativo_departamentos.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
