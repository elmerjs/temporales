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

// Función para calcular el semestre anterior
function obtenerSemestreAnterior($semestre_actual) {
    list($anio, $semestre) = explode('-', $semestre_actual);
    $anio = intval($anio);
    $semestre = intval($semestre);
    
    if ($semestre == 1) {
        return ($anio - 1) . '-2';
    } else {
        return $anio . '-1';
    }
}
$anio_semestre_anterior = obtenerSemestreAnterior($anio_semestre);

// Construir condición WHERE según tipo de usuario
$where = "WHERE s.anio_semestre = '" . $conn->real_escape_string($anio_semestre) . "' 
          AND (s.estado <> 'an' OR s.estado IS NULL)";

if ($tipo_usuario == 2) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id;
} elseif ($tipo_usuario == 3) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id . 
              " AND deparmanentos.PK_DEPTO = " . (int)$departamento_id;
} elseif ($tipo_usuario == 1 && $facultad_id > 0) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id;
}

// CONSULTA CORREGIDA - Primero obtenemos todos los registros base sin duplicados
$sql_base = "SELECT 
    s.id_solicitud,
    s.anio_semestre, 
    facultad.NOMBREF_FAC, 
    deparmanentos.NOMBRE_DEPTO_CORT, 
    s.cedula, 
    s.nombre, 
    s.tipo_docente,
    s.tipo_dedicacion,
    s.tipo_dedicacion_r,
    s.horas,
    s.horas_r,
    s.sede,
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
    s.anexa_hv_docente_nuevo, 
    s.actualiza_hv_antiguo,   
    s.puntos,
    (
        SELECT puntos 
        FROM solicitudes s_ant
        WHERE s_ant.cedula = s.cedula 
          AND s_ant.anio_semestre = '" . $conn->real_escape_string($anio_semestre_anterior) . "'
          AND (s_ant.estado <> 'an' OR s_ant.estado IS NULL)
          AND s_ant.departamento_id = s.departamento_id
        LIMIT 1
    ) AS puntos_anterior

FROM solicitudes s
JOIN deparmanentos ON deparmanentos.PK_DEPTO = s.departamento_id 
JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
LEFT JOIN depto_periodo ON depto_periodo.periodo = s.anio_semestre 
                      AND depto_periodo.fk_depto_dp = s.departamento_id
LEFT JOIN fac_periodo ON fac_periodo.fp_periodo = s.anio_semestre 
                     AND fac_periodo.fp_fk_fac = s.facultad_id
$where
GROUP BY s.id_solicitud
ORDER BY s.anio_semestre, facultad.PK_FAC, deparmanentos.NOMBRE_DEPTO_CORT, s.tipo_docente, s.nombre ASC";

$result_base = $conn->query($sql_base);

if ($result_base->num_rows > 0) {
    // Procesar los resultados base y generar las filas según las reglas
    $datos_procesados = [];
    
    while ($row = $result_base->fetch_assoc()) {
        // Para cada registro, determinamos cuántas filas generar
        
        if ($row['tipo_docente'] == 'Ocasional') {
            // Docentes ocasionales: solo una fila
            $sede = ($row['sede'] == 'Popayán-Regionalización') ? 'Popayán' : $row['sede'];
            $dedicacion = ($row['sede'] == 'Popayán') ? $row['tipo_dedicacion'] : 
                         (($row['sede'] == 'Regionalización') ? $row['tipo_dedicacion_r'] : $row['tipo_dedicacion']);
            
            $horas = 0;
            if (in_array($dedicacion, ['TC', 'TC'])) {
                $horas = 40;
            } elseif (in_array($dedicacion, ['MT', 'MT'])) {
                $horas = 20;
            }
            
            $datos_procesados[] = [
                'anio_semestre' => $row['anio_semestre'],
                'NOMBREF_FAC' => $row['NOMBREF_FAC'],
                'NOMBRE_DEPTO_CORT' => $row['NOMBRE_DEPTO_CORT'],
                'sede' => $sede,
                'cedula' => $row['cedula'],
                'nombre' => $row['nombre'],
                'tipo_docente' => $row['tipo_docente'],
                'dedicacion' => $dedicacion,
                'horas' => $horas,
                'acepta_fac_status' => $row['acepta_fac_status'],
                'envia_fac_status' => $row['envia_fac_status'],
                'acepta_vra_status' => $row['acepta_vra_status'],
                'PK_FAC' => $row['PK_FAC'],
                'anexa_hv_docente_nuevo' => $row['anexa_hv_docente_nuevo'],
                'actualiza_hv_antiguo' => $row['actualiza_hv_antiguo'],
                'puntos' => $row['puntos'],
                'puntos_anterior' => $row['puntos_anterior']
            ];
            
        } elseif ($row['tipo_docente'] == 'Catedra') {
            // Docentes de cátedra: pueden generar 1 o 2 filas
            
            // Siempre generar fila para Popayán si tiene horas
            if ($row['horas'] > 0) {
                $datos_procesados[] = [
                    'anio_semestre' => $row['anio_semestre'],
                    'NOMBREF_FAC' => $row['NOMBREF_FAC'],
                    'NOMBRE_DEPTO_CORT' => $row['NOMBRE_DEPTO_CORT'],
                    'sede' => 'Popayán',
                    'cedula' => $row['cedula'],
                    'nombre' => $row['nombre'],
                    'tipo_docente' => $row['tipo_docente'],
                    'dedicacion' => 'HRS',
                    'horas' => $row['horas'],
                    'acepta_fac_status' => $row['acepta_fac_status'],
                    'envia_fac_status' => $row['envia_fac_status'],
                    'acepta_vra_status' => $row['acepta_vra_status'],
                    'PK_FAC' => $row['PK_FAC'],
                    'anexa_hv_docente_nuevo' => $row['anexa_hv_docente_nuevo'],
                    'actualiza_hv_antiguo' => $row['actualiza_hv_antiguo'],
                    'puntos' => $row['puntos'],
                    'puntos_anterior' => $row['puntos_anterior']
                ];
            }
            
            // Generar fila para Regionalización si tiene horas_r
            if ($row['horas_r'] > 0) {
                $datos_procesados[] = [
                    'anio_semestre' => $row['anio_semestre'],
                    'NOMBREF_FAC' => $row['NOMBREF_FAC'],
                    'NOMBRE_DEPTO_CORT' => $row['NOMBRE_DEPTO_CORT'],
                    'sede' => 'Regionalización',
                    'cedula' => $row['cedula'],
                    'nombre' => $row['nombre'],
                    'tipo_docente' => $row['tipo_docente'],
                    'dedicacion' => 'HRS',
                    'horas' => $row['horas_r'],
                    'acepta_fac_status' => $row['acepta_fac_status'],
                    'envia_fac_status' => $row['envia_fac_status'],
                    'acepta_vra_status' => $row['acepta_vra_status'],
                    'PK_FAC' => $row['PK_FAC'],
                    'anexa_hv_docente_nuevo' => $row['anexa_hv_docente_nuevo'],
                    'actualiza_hv_antiguo' => $row['actualiza_hv_antiguo'],
                    'puntos' => $row['puntos'],
                    'puntos_anterior' => $row['puntos_anterior']
                ];
            }
            
            // Caso especial: si tiene sede 'Popayán-Regionalización' y horas > 0
            if ($row['sede'] == 'Popayán-Regionalización' && $row['horas'] > 0) {
                // Ya generamos Popayán, pero si no tiene horas_r, también generar Regionalización
                if ($row['horas_r'] == 0) {
                    $datos_procesados[] = [
                        'anio_semestre' => $row['anio_semestre'],
                        'NOMBREF_FAC' => $row['NOMBREF_FAC'],
                        'NOMBRE_DEPTO_CORT' => $row['NOMBRE_DEPTO_CORT'],
                        'sede' => 'Regionalización',
                        'cedula' => $row['cedula'],
                        'nombre' => $row['nombre'],
                        'tipo_docente' => $row['tipo_docente'],
                        'dedicacion' => 'HRS',
                        'horas' => $row['horas'],
                        'acepta_fac_status' => $row['acepta_fac_status'],
                        'envia_fac_status' => $row['envia_fac_status'],
                        'acepta_vra_status' => $row['acepta_vra_status'],
                        'PK_FAC' => $row['PK_FAC'],
                        'anexa_hv_docente_nuevo' => $row['anexa_hv_docente_nuevo'],
                        'actualiza_hv_antiguo' => $row['actualiza_hv_antiguo'],
                        'puntos' => $row['puntos'],
                        'puntos_anterior' => $row['puntos_anterior']
                    ];
                }
            }
        }
    }
    
    // Ahora generamos el Excel con los datos procesados
    $spreadsheet = new Spreadsheet();
    
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
            'startColor' => ['rgb' => '003366']
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
    
    // Encabezados (sin ID Facultad)
    $headers = [
        'Semestre', 'Facultad', 'Departamento', 'Sede', 
        'Cédula', 'Nombre Completo', 'Tipo Docente', 'Dedicación', 
        'Horas', 'Estado Facultad', 'Envío Facultad', 'Estado VRA',
        'Anexa HV', 'Actualiza HV', 'Puntos', 'Puntos Anterior'
    ];
    
    // Configuración de columnas
    $columnConfig = [
        'A' => 12, 'B' => 30, 'C' => 20, 'D' => 15, 
        'E' => 12, 'F' => 30, 'G' => 15, 'H' => 12, 
        'I' => 10, 'J' => 15, 'K' => 15, 'L' => 15,
        'M' => 10, 'N' => 12, 'O' => 8, 'P' => 12
    ];
    
    if ($tipo_usuario == 1) {
        // USUARIO TIPO 1 - Separar en hojas
        $spreadsheet->removeSheetByIndex(0);
        
        // Separar datos por tipo de docente
        $datos_ocasional = [];
        $datos_catedra = [];
        
        foreach ($datos_procesados as $data) {
            // Remover PK_FAC
            unset($data['PK_FAC']);
            
            if ($data['tipo_docente'] == 'Ocasional') {
                $datos_ocasional[] = array_values($data);
            } else {
                $datos_catedra[] = array_values($data);
            }
        }
        
        // Crear hoja para DOCENTES OCASIONALES
        if (!empty($datos_ocasional)) {
            $sheetOcasional = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Ocasional');
            $spreadsheet->addSheet($sheetOcasional, 0);
            
            $sheetOcasional->fromArray($headers, NULL, 'A1');
            $sheetOcasional->fromArray($datos_ocasional, NULL, 'A2');
            
            $sheetOcasional->getStyle('A1:P1')->applyFromArray($headerStyle);
            $sheetOcasional->getStyle('A2:P' . (count($datos_ocasional) + 1))->applyFromArray($cellStyle);
            
            foreach ($columnConfig as $col => $width) {
                $sheetOcasional->getColumnDimension($col)->setWidth($width);
            }
            
            $sheetOcasional->freezePane('A2');
            
            foreach (range(1, count($datos_ocasional) + 1) as $rowID) {
                $sheetOcasional->getRowDimension($rowID)->setRowHeight(-1);
            }
        }
        
        // Crear hoja para DOCENTES CÁTEDRA
        if (!empty($datos_catedra)) {
            $sheetCatedra = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Cátedra');
            $spreadsheet->addSheet($sheetCatedra, 1);
            
            $sheetCatedra->fromArray($headers, NULL, 'A1');
            $sheetCatedra->fromArray($datos_catedra, NULL, 'A2');
            
            $sheetCatedra->getStyle('A1:P1')->applyFromArray($headerStyle);
            $sheetCatedra->getStyle('A2:P' . (count($datos_catedra) + 1))->applyFromArray($cellStyle);
            
            foreach ($columnConfig as $col => $width) {
                $sheetCatedra->getColumnDimension($col)->setWidth($width);
            }
            
            $sheetCatedra->freezePane('A2');
            
            foreach (range(1, count($datos_catedra) + 1) as $rowID) {
                $sheetCatedra->getRowDimension($rowID)->setRowHeight(-1);
            }
        }
        
    } else {
        // USUARIOS TIPO 2 y 3 - Hoja única
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Docentes Temporales');
        
        // Preparar todos los datos
        $allData = [];
        foreach ($datos_procesados as $data) {
            unset($data['PK_FAC']);
            $allData[] = array_values($data);
        }
        
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->fromArray($allData, NULL, 'A2');
        
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);
        $sheet->getStyle('A2:P' . (count($allData) + 1))->applyFromArray($cellStyle);
        
        foreach ($columnConfig as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        
        $sheet->freezePane('A2');
        
        foreach (range(1, count($allData) + 1) as $rowID) {
            $sheet->getRowDimension($rowID)->setRowHeight(-1);
        }
    }
    
    // Configurar página para todas las hojas
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
    }
    
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