<?php
require 'vendor/autoload.php'; // Asegúrate de tener este archivo si usas Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'conn.php'; // Tu archivo de conexión a la BD

$anio_semestre = $_POST['anio_semestre'] ?? '';
// Conexión a la base de datos (ya la tienes en tu código)

// Ejecutar la misma consulta SQL que usas en tu tabla
$sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
        FROM solicitudes 
        JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
        JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
        WHERE 
         anio_semestre = '$anio_semestre' 
       
        AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL) 
        ORDER BY solicitudes.nombre ASC";

$result = $conn->query($sql);

// Crear un nuevo documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados (personalízalos según tus columnas)
$sheet->setCellValue('A1', 'N°');
$sheet->setCellValue('B1', 'Cédula');
$sheet->setCellValue('C1', 'Nombre');
$sheet->setCellValue('D1', 'Tipo Dedicación');
$sheet->setCellValue('E1', 'Tipo Dedicación R');
$sheet->setCellValue('F1', 'Puntos');
$sheet->setCellValue('G1', 'Total ($)');

// Llenar datos desde la consulta SQL
$rowNumber = 2; // Fila inicial para datos
while ($row = $result->fetch_assoc()) {
        $tipo_docente = $row['tipo_docente'];
    $sheet->setCellValue('A' . $rowNumber, $rowNumber - 1); // Número de fila
    $sheet->setCellValue('B' . $rowNumber, $row['cedula']);
    $sheet->setCellValue('C' . $rowNumber, $row['nombre']);
    
    if ($tipo_docente == "Ocasional") {
        $sheet->setCellValue('D' . $rowNumber, $row['tipo_dedicacion']);
        $sheet->setCellValue('E' . $rowNumber, $row['tipo_dedicacion_r']);
    } else {
        $sheet->setCellValue('D' . $rowNumber, $row['horas'] ?? '');
        $sheet->setCellValue('E' . $rowNumber, $row['horas_r'] ?? '');
    }
    
    $sheet->setCellValue('F' . $rowNumber, $row['puntos']);
    
    // Calcular el total (simplificado, ajusta según tu lógica)
if ($tipo_docente == "Catedra")   
    {

                  $asignacion_total= $row["puntos"]*$valor_punto *($row["horas"]+$row["horas_r"])*$semanas_cat;
                $asignacion_mes=$row["puntos"]*$valor_punto*($row["horas"] +$row["horas_r"])*4;
                $prima_navidad = $asignacion_mes*3/12;
                $indem_vacaciones = $asignacion_mes*$dias/360;
                $indem_prima_vacaciones = $indem_vacaciones*2/3;
                $cesantias =($asignacion_total + $prima_navidad)/12;
                $total_devengos=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones+$cesantias;
             //eps
                if ($asignacion_mes < $smlv){
                $valor_base = ($smlv * $dias / 30) * 8.5 / 100;
            } else {
                $valor_base = round($asignacion_total * 8.5 / 100, 0);
            }

            // Redondear al múltiplo de 100 más cercano
            $eps = round($valor_base, -2);

                    //pension

            // Cálculo principal
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * (12 / 100);
            } else {
                $valor_base = round($asignacion_total * (12 / 100), 0);
            }

            // Redondear al múltiplo de 100 más cercano
            $afp = round($valor_base, -2);

                    //arp
                    $porcentaje = 0.522 / 100;

            // Lógica del cálculo
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * $porcentaje;
            } else {
                $valor_base = round($asignacion_total * $porcentaje, 0);
            }

            // Redondeo al múltiplo de 100 más cercano
            $arl = round($valor_base, -2);

                    //comfaucaua
            // Porcentaje a aplicar
            $porcentaje = 4 / 100;

            // Cálculo condicional
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * $porcentaje;
            } else {
                $valor_base = round($asignacion_total * $porcentaje, 0);
            }

            // Redondear al múltiplo de 100 más cercano
            $cajacomp = round($valor_base, -2);

                    // icbf
            $porcentaje = 3 / 100;

            // Cálculo condicional
            if ($asignacion_mes < $smlv) {
                $valor_base = (($smlv * $dias) / 30) * $porcentaje;
            } else {
                $valor_base = round($asignacion_total * $porcentaje, 0);
            }

            // Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
            $icbf = round($valor_base, -2);
                    $total_aportes= $eps +$afp+$arl+$cajacomp+$icbf;
                    $gran_total = $total_devengos+$total_aportes;

    }
    
    
    $rowNumber++;
}

// Autoajustar columnas
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_' . $anio_semestre . '_' . $tipo_docente . '.xlsx"');
header('Cache-Control: max-age=0');

// Guardar y descargar
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;