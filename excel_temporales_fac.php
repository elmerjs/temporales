<?php
// Incluir la librería PHPSpreadsheet y el archivo de conexión
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Obtener los valores de los filtros del formulario
$departamento_id = isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null;
$tipo_usuario = isset($_GET['tipo_usuario']) ? $_GET['tipo_usuario'] : null;
$facultad_id = isset($_GET['facultad_id']) ? $_GET['facultad_id'] : null;
$anio_semestre = isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : null;

// Determinar la cláusula WHERE basada en el tipo de usuario
$where = "";
if ($tipo_usuario == '1') {
    if (!is_null($departamento_id) && $departamento_id !== '0' && !is_null($facultad_id) && $facultad_id !== '0') {
        // Si vienen ambos valores (facultad y departamento)
        $where = "WHERE solicitudes.anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id' AND deparmanentos.PK_DEPTO ='$departamento_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    } else if (!is_null($facultad_id) && $facultad_id !== '0') {
        // Si viene solo facultad (departamento es null o cero)
        $where = "WHERE solicitudes.anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    } else {
        // Si no hay información de facultad ni departamento
        $where = "WHERE solicitudes.anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    }
} else if ($tipo_usuario == '2') {
    if (!is_null($departamento_id) && $departamento_id !== '0') {
        // Si  departamento tiene un valor
        $where = "WHERE solicitudes.anio_semestre = '$anio_semestre' AND deparmanentos.PK_DEPTO ='$departamento_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    } else {
        // Caso general para usuario tipo 2
        $where = "WHERE solicitudes.anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    }
} else if ($tipo_usuario == '3') {
    // Caso específico para usuario tipo 3
    $where = "WHERE solicitudes.anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id' AND deparmanentos.PK_DEPTO ='$departamento_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
}

$sql = "SELECT 
            solicitudes.cedula, 
            solicitudes.nombre, 
            solicitudes.tipo_docente,
            deparmanentos.depto_nom_propio AS departamento,  
            CASE 
                WHEN LOWER(solicitudes.tipo_docente) = 'ocasional' THEN solicitudes.tipo_dedicacion
                ELSE solicitudes.horas
            END AS dedicacion_horas,
            CASE 
                WHEN LOWER(solicitudes.tipo_docente) = 'catedra' THEN solicitudes.horas_r
                ELSE solicitudes.tipo_dedicacion_r
            END AS dedicacion_horas_r,
            solicitudes.anexa_hv_docente_nuevo,
            solicitudes.actualiza_hv_antiguo, 
      CASE 
        WHEN depto_periodo.dp_acepta_fac = 'aceptar' THEN 'Aceptado'
        WHEN depto_periodo.dp_acepta_fac = 'rechazar' THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_fac_status,
    CASE 
        WHEN fac_periodo.fp_acepta_vra = 2 THEN 'Aceptado'
        WHEN fac_periodo.fp_acepta_vra = 1 THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_vra_status
        FROM 
            solicitudes 
        JOIN 
            deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id 
        JOIN 
            facultad ON facultad.PK_FAC = deparmanentos.FK_FAC 
            LEFT JOIN 
    depto_periodo ON depto_periodo.periodo = solicitudes.anio_semestre 
                  AND depto_periodo.fk_depto_dp = solicitudes.departamento_id
LEFT JOIN  
    fac_periodo ON fac_periodo.fp_periodo = solicitudes.anio_semestre 
               AND fac_periodo.fp_fk_fac = solicitudes.facultad_id
        $where
        ORDER BY 
            deparmanentos.depto_nom_propio, 
            solicitudes.tipo_docente, 
            solicitudes.anio_semestre, 
            facultad.PK_FAC, 
            deparmanentos.NOMBRE_DEPTO_CORT, 
            solicitudes.nombre ASC";

// Ejecutar la consulta SQL
$result = $conn->query($sql);

// Verificar si hay resultados
if ($result->num_rows > 0) {
    // Crear un nuevo objeto Spreadsheet
    $spreadsheet = new Spreadsheet();

    // Obtener la hoja activa
    $sheet = $spreadsheet->getActiveSheet();

    // Configurar la orientación de la página a horizontal (landscape)
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

    // Inicializar variables para los grupos de registros
    $row = 1; // Empezamos en la fila 1 para los encabezados
    $current_department = '';
    $current_docente_type = '';
    $id_counter = 1;

    // Estilo de bordes
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ],
    ];

    // Estilo de alineación centrada
    $centeredStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    // Estilo de alineación a la izquierda
    $leftAlignedStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    // Estilo de alineación centrada
    $centeredHeaderStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ];

    // Recorrer los resultados y escribirlos en el archivo Excel
  while ($row_data = $result->fetch_assoc()) {
    // Verificar si el departamento cambió
    if ($row_data['departamento'] != $current_department) {
        // Añadir una fila para el nombre del departamento
        if ($current_department != '') {
            $row++; // Salto de línea antes del nuevo título
        }
        $sheet->setCellValue('A' . $row, mb_strtoupper('Departamento: ' . $row_data['departamento']. '. Periodo: '.$anio_semestre, 'UTF-8'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $row++;
        $current_departmentant = $current_department;
        $current_department = $row_data['departamento'];
        $id_counter = 1;
    }

    // Verificar si el tipo de docente cambió
    if ($row_data['tipo_docente'] != $current_docente_type) {
        if ($current_docente_type != '') {
            $row++;
        }

        $dedicacion_horas_label = ($row_data['tipo_docente'] == 'Ocasional') ? 'Dedicación' : 'Horas';
        $dedicacion_horas_r_label = ($row_data['tipo_docente'] == 'Catedra') ? 'Dedicación' : 'Horas';

        $sheet->setCellValue('A' . $row, 'Tipo Docente: ' . $row_data['tipo_docente']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, 'ID');
        $sheet->setCellValue('B' . $row, 'Cédula');
        $sheet->setCellValue('C' . $row, 'Nombre');
        $sheet->setCellValue('D' . $row, $dedicacion_horas_label);
        $sheet->setCellValue('E' . $row, $dedicacion_horas_r_label);
        $sheet->setCellValue('F' . $row, 'Hoja de Vida');
        $sheet->setCellValue('G' . $row, 'Actualiz Antiguo');

        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($borderStyle);
        $sheet->mergeCells('D' . $row . ':E' . $row);
        $sheet->mergeCells('F' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':G' . $row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A' . $row . ':G' . $row)->getAlignment()->setWrapText(true);
        $row++;

        $sheet->setCellValue('D' . $row, 'Popayán');
        $sheet->setCellValue('E' . $row, 'Regionalización');
        $sheet->setCellValue('F' . $row, 'Anexa Nuevo');
        $sheet->setCellValue('G' . $row, 'Actualiz Antiguo');

        $sheet->getStyle('D' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row . ':G' . $row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D' . $row . ':G' . $row)->getAlignment()->setWrapText(true);
        
        $row++;
        $current_docente_type = $row_data['tipo_docente'];
        $id_counter = 1;
    }

    // Reemplazar ceros por vacío
    $dedicacion_horas = ($row_data['dedicacion_horas'] == 0) ? '' : $row_data['dedicacion_horas'];
    $dedicacion_horas_r = ($row_data['dedicacion_horas_r'] == 0) ? '' : $row_data['dedicacion_horas_r'];

    // Añadir los datos de la fila
    $sheet->setCellValue('A' . $row, $id_counter++); 
    $sheet->setCellValue('B' . $row, $row_data['cedula']);
    $sheet->setCellValue('C' . $row, $row_data['nombre']);
    $sheet->setCellValue('D' . $row, $dedicacion_horas);
    $sheet->setCellValue('E' . $row, $dedicacion_horas_r);
    $sheet->setCellValue('F' . $row, $row_data['anexa_hv_docente_nuevo']);
    $sheet->setCellValue('G' . $row, $row_data['actualiza_hv_antiguo']);

    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($borderStyle);
    $sheet->getStyle('D' . $row . ':E' . $row)->applyFromArray($centeredStyle);
    $sheet->getStyle('F' . $row . ':G' . $row)->applyFromArray($centeredStyle);
    $sheet->getStyle('B' . $row)->applyFromArray($leftAlignedStyle);
    $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);

    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);

    $row++;
}


    // Guardar el archivo Excel
    $writer = new Xlsx($spreadsheet);
    $fileName = 'reporte.xlsx';
    $writer->save($fileName);

    // Descargar el archivo Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    readfile($fileName);
    unlink($fileName); // Eliminar el archivo después de la descarga
}
?>
