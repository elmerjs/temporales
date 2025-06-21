<?php
// Configuración de reporte de docentes temporales en Excel
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

// Validar y obtener parámetros
$departamento_id = filter_input(INPUT_GET, 'departamento_id', FILTER_VALIDATE_INT);
$tipo_usuario = filter_input(INPUT_GET, 'tipo_usuario', FILTER_VALIDATE_INT);
$facultad_id = filter_input(INPUT_GET, 'facultad_id', FILTER_VALIDATE_INT);
$anio_semestre = filter_input(INPUT_GET, 'anio_semestre', FILTER_SANITIZE_STRING);

// Verificar conexión a la base de datos
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Construir condición WHERE según tipo de usuario
$where = "WHERE anio_semestre = '" . $conn->real_escape_string($anio_semestre) . "' 
          AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

if ($tipo_usuario == 2) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id;
} elseif ($tipo_usuario == 3) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id . 
              " AND deparmanentos.PK_DEPTO = " . (int)$departamento_id;
}

// Definir la consulta SQL
$sql = "SELECT 
    solicitudes.anio_semestre, 
    facultad.NOMBREF_FAC, 
    deparmanentos.NOMBRE_DEPTO_CORT, 
    CASE 
        WHEN solicitudes.sede = 'Popayán-Regionalización' THEN 'Popayán'
        ELSE solicitudes.sede
    END AS sede, 
    solicitudes.cedula, 
    solicitudes.nombre, 
    solicitudes.tipo_docente, 
    CASE 
        WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN solicitudes.tipo_dedicacion
        WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN solicitudes.tipo_dedicacion_r
        WHEN solicitudes.tipo_docente = 'Catedra' THEN 'HRS'
    END AS dedicacion,
    CASE 
        WHEN solicitudes.tipo_docente = 'Ocasional' AND (solicitudes.tipo_dedicacion = 'TC' OR solicitudes.tipo_dedicacion_r = 'TC') THEN 40
        WHEN solicitudes.tipo_docente = 'Ocasional' AND (solicitudes.tipo_dedicacion = 'MT' OR solicitudes.tipo_dedicacion_r = 'MT') THEN 20
        WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN solicitudes.horas
        WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN solicitudes.horas_r
        WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán-Regionalización' THEN solicitudes.horas
    END AS horas,
    CASE 
        WHEN depto_periodo.dp_acepta_fac = 'aceptar' THEN 'Aceptado'
        WHEN depto_periodo.dp_acepta_fac = 'rechazar' THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_fac_status,
    CASE 
        WHEN fac_periodo.fp_estado = 1 THEN 'Enviado'
        WHEN fac_periodo.fp_estado = 0  THEN 'No enviado'
        ELSE 'Pendiente'
    END AS envia_fac_status,
    CASE 
        WHEN fac_periodo.fp_acepta_vra = 2 THEN 'Aceptado'
        WHEN fac_periodo.fp_acepta_vra = 1 THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_vra_status,
    facultad.PK_FAC,  -- Agregamos PK_FAC para ordenar
    solicitudes.anexa_hv_docente_nuevo, 
    solicitudes.actualiza_hv_antiguo,   
    solicitudes.puntos  -- Aquí agregamos el campo puntos

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
AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)

UNION ALL

SELECT 
    solicitudes.anio_semestre, 
    facultad.NOMBREF_FAC, 
    deparmanentos.NOMBRE_DEPTO_CORT, 
    'Regionalización' AS sede,  
    solicitudes.cedula, 
    solicitudes.nombre, 
    solicitudes.tipo_docente, 
    'HRS' AS dedicacion,  
    solicitudes.horas_r AS horas,  
    CASE 
        WHEN depto_periodo.dp_acepta_fac = 'aceptar' THEN 'Aceptado'
        WHEN depto_periodo.dp_acepta_fac = 'rechazar' THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_fac_status,
    CASE 
        WHEN fac_periodo.fp_estado = 1 THEN 'Enviado'
        WHEN fac_periodo.fp_estado = 0  THEN 'No enviado'
        ELSE 'Pendiente'
    END AS envia_fac_status,
    CASE 
        WHEN fac_periodo.fp_acepta_vra = 2 THEN 'Aceptado'
        WHEN fac_periodo.fp_acepta_vra = 1 THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_vra_status,
    facultad.PK_FAC,
    solicitudes.anexa_hv_docente_nuevo, 
    solicitudes.actualiza_hv_antiguo,
        solicitudes.puntos -- Aquí también puntos

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
AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
AND solicitudes.tipo_docente = 'Catedra' 
AND solicitudes.horas > 0 
AND solicitudes.horas_r > 0  

ORDER BY 
       anio_semestre, PK_FAC, NOMBRE_DEPTO_CORT, nombre ASC;
";


$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Estilo institucional para encabezados
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '003366'] // Azul institucional
        ]
    ];
    
    // Estilo para celdas de datos
    $cellStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DDDDDD']
            ]
        ],
        'alignment' => [
            'wrapText' => true
        ]
    ];
    
    // Encabezados mejorados
    $headers = [
        'Semestre', 'Facultad', 'Departamento', 'Sede', 
        'Cédula', 'Nombre Completo', 'Tipo Docente', 'Dedicación', 
        'Horas', 'Estado Facultad', 'Envío Facultad', 'Estado VRA',
        'ID Facultad', 'Anexa HV', 'Actualiza HV', 'Puntos'
    ];
    
    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);
    
    // Configuración de columnas
    $columnConfig = [
        'A' => 12, 'B' => 30, 'C' => 20, 'D' => 15, 
        'E' => 12, 'F' => 30, 'G' => 15, 'H' => 12, 
        'I' => 10, 'J' => 15, 'K' => 15, 'L' => 15,
        'M' => 12, 'N' => 10, 'O' => 12, 'P' => 8
    ];
    
    foreach ($columnConfig as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }
    
    // Llenar datos
    $row = 2;
    while ($data = $result->fetch_assoc()) {
        $sheet->fromArray(array_values($data), NULL, "A{$row}");
        $sheet->getStyle("A{$row}:P{$row}")->applyFromArray($cellStyle);
        $row++;
    }
    
    // Autoajustar altura de filas
    foreach (range(1, $row) as $rowID) {
        $sheet->getRowDimension($rowID)->setRowHeight(-1);
    }
    
    // Congelar paneles (fila de encabezados)
    $sheet->freezePane('A2');
    
    // Configurar página
    $sheet->getPageSetup()
        ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
        ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
        ->setFitToWidth(1)
        ->setFitToHeight(0);
    
    // Generar nombre de archivo
    $filename = "Reporte_Docentes_Temporales_{$anio_semestre}_" . date('Ymd_His') . ".xlsx";
    
    // Configurar headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"{$filename}\"");
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    
    // Guardar y enviar archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
} else {
    // Mensaje institucional cuando no hay datos
    echo '<div style="padding: 20px; margin: 20px; border: 1px solid #ddd; background: #f9f9f9; text-align: center;">
            <h3 style="color: #003366;">Universidad del Cauca</h3>
            <p>No se encontraron registros para los criterios seleccionados.</p>
            <p>Por favor, intente con otros parámetros de búsqueda.</p>
          </div>';
}

$conn->close();
?>