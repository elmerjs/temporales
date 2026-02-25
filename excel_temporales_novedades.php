<?php
// excel_temporales_novedades.php (Versión Final: Sin Duplicados + Historial Aprobado + Obs. Inteligente)

require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Obtener los valores de los filtros
$departamento_id = isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null;
$tipo_usuario = isset($_GET['tipo_usuario']) ? $_GET['tipo_usuario'] : null;
$facultad_id = isset($_GET['facultad_id']) ? $_GET['facultad_id'] : null;
$anio_semestre = isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : null;

// Construcción del WHERE
if ($tipo_usuario == '1') {
    $where = "WHERE anio_semestre = '$anio_semestre' ";
} else if ($tipo_usuario == '2') {
    $where = "WHERE anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id'";
} else if ($tipo_usuario == '3') {
    $where = "WHERE anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id' AND deparmanentos.PK_DEPTO ='$departamento_id' ";
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Obtener JSON de novedades
$sqlNovedades = "SELECT * FROM solicitudes_novedades WHERE periodo_anio = '$anio_semestre'";
$resultNovedades = $conn->query($sqlNovedades);
$novedades = [];
while ($row = $resultNovedades->fetch_assoc()) {
    $novedades[] = $row;
}

// 2. Consulta SQL Principal
$sqle = "SELECT 
    solicitudes.id_solicitud, 
    solicitudes.anio_semestre, 
    facultad.NOMBREF_FAC, 
    deparmanentos.NOMBRE_DEPTO_CORT, 
    deparmanentos.PK_DEPTO AS departamento_id,  
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
    facultad.PK_FAC,
    solicitudes.anexa_hv_docente_nuevo, solicitudes.actualiza_hv_antiguo, 
    solicitudes.estado, solicitudes.novedad,
    '' AS detalle_novedad, 
    solicitudes.s_observacion AS observación_novedad,
    solicitudes.tipo_reemplazo AS tipo_de_novedad
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

UNION ALL

SELECT 
    solicitudes.id_solicitud, 
    solicitudes.anio_semestre, 
    facultad.NOMBREF_FAC, 
    deparmanentos.NOMBRE_DEPTO_CORT, 
    deparmanentos.PK_DEPTO AS departamento_id,  
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
    solicitudes.anexa_hv_docente_nuevo, solicitudes.actualiza_hv_antiguo, 
    solicitudes.estado, solicitudes.novedad,
    '' AS detalle_novedad,
    solicitudes.s_observacion AS observación_novedad,
    solicitudes.tipo_reemplazo AS tipo_de_novedad
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
    AND solicitudes.tipo_docente = 'Catedra' 
    AND solicitudes.horas > 0 
    AND solicitudes.horas_r > 0  

ORDER BY 
    anio_semestre, PK_FAC, NOMBRE_DEPTO_CORT, nombre ASC;
";

$result = $conn->query($sqle);

// 3. Pre-cargar historial de observaciones (SOLO APROBADOS POR VRA)
$historial_observaciones = [];
$sqlHist = "SELECT fk_id_solicitud_original, s_observacion, novedad 
            FROM solicitudes_working_copy 
            WHERE anio_semestre = '$anio_semestre' 
            AND fk_id_solicitud_original IS NOT NULL 
            AND fk_id_solicitud_original > 0 
            AND estado_vra = 'APROBADO' 
            ORDER BY id_solicitud ASC"; 

$resHist = $conn->query($sqlHist);
if ($resHist) {
    while ($h = $resHist->fetch_assoc()) {
        $id_padre = $h['fk_id_solicitud_original'];
        $texto = "[" . ucfirst($h['novedad']) . "]: " . trim($h['s_observacion']);
        $historial_observaciones[$id_padre][] = $texto;
    }
}

if ($result->num_rows > 0) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headerStyle = [
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    $headers = [
        'Semestre', 'Facultad', 'Departamento', 'id_depto', 'Sede', 'Cédula', 'Nombre', 
        'Tipo Docente', 'Dedicación', 'Horas', 'Estado_fac', 'Envio_fac',
        'Estado_vra', 'ID_FAC', 'AnexaHV', 'ActualizaHV', 'Estado', 'Novedad', 'Detalle Novedad', 'Observacion Novedad', 'Tipo Novedad'
    ];

    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->getStyle('A1:U1')->applyFromArray($headerStyle);

    $columnWidths = [
        'A' => 20, 'B' => 30, 'C' => 15, 'D' => 20, 'E' => 15, 
        'F' => 30, 'G' => 20, 'H' => 20, 'I' => 15, 'J' => 15, 
        'K' => 15, 'L' => 15, 'M' => 10, 'N' => 10, 'O' => 10,
        'P' => 15, 'Q' => 15, 'R' => 15 , 'S' => 45, 'T' => 45, 'U' => 15 
    ];
    foreach ($columnWidths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }

    $row = 2;
    $ids_procesados = []; // --- NUEVO: Array para evitar duplicados ---

    while ($row_data = $result->fetch_assoc()) {
        $id_actual = $row_data['id_solicitud'];

        // --- VALIDACIÓN ANTI-DUPLICADOS ---
        // Si el ID ya fue procesado en este ciclo, lo saltamos
        if (in_array($id_actual, $ids_procesados)) {
            continue;
        }
        // Si no, lo agregamos a la lista de procesados
        $ids_procesados[] = $id_actual;
        // ----------------------------------

        // --- PROCESAMIENTO DE OBSERVACIONES ---
        $obs_original = trim($row_data['observación_novedad']);
        $lineas_observacion = [];
        $contador = 1;

        if (!empty($obs_original)) {
            $lineas_observacion[] = "($contador) INICIAL: " . $obs_original;
            $contador++;
        }

        if (isset($historial_observaciones[$id_actual])) {
            foreach ($historial_observaciones[$id_actual] as $obs_extra) {
                $lineas_observacion[] = "($contador) " . $obs_extra;
                $contador++;
            }
        }
        
        $row_data['observación_novedad'] = implode("\n", $lineas_observacion);

        // --- ELIMINAR ID ---
        unset($row_data['id_solicitud']);

        // --- DETALLE JSON ---
        $detalle_novedad = '';
        foreach ($novedades as $novedad) {
            $detalle_json = json_decode($novedad['detalle_novedad'], true);
            
            if ($novedad['periodo_anio'] == $row_data['anio_semestre'] &&
                $novedad['departamento_id'] == $row_data['departamento_id'] &&
                isset($detalle_json['cedula']) &&
                $detalle_json['cedula'] == $row_data['cedula']) {
                
                $detalle_formateado = '';
                foreach ($detalle_json as $key => $value) {
                    $key_formatted = ucwords(str_replace('_', ' ', $key));
                    $detalle_formateado .= "$key_formatted: $value\n";
                }
                
                $detalle_novedad = trim($detalle_formateado);
                break;
            }
        }
        $row_data['detalle_novedad'] = $detalle_novedad;
        
        // Escribir fila
        $sheet->fromArray(array_values($row_data), NULL, 'A' . $row);
        
        // Estilos
        $last_col_letter = 'U'; 
        $sheet->getStyle('S'.$row)->getAlignment()->setWrapText(true);
        $sheet->getStyle('T'.$row)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(-1);
        
        // Colores condicionales
        $estado = $row_data['estado'] ?? '';
        $novedad = $row_data['novedad'] ?? '';
        $style = [];
        if (strtolower($estado) == 'an') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']];
        } elseif (strtolower($novedad) == 'adicionar') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']];
        } elseif (strtolower($novedad) == 'modificar') {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']];
        }
        
        if (!empty($style)) {
            $sheet->getStyle('A'.$row.':'.$last_col_letter.$row)->applyFromArray($style);
        }
        
        $row++;
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save('temporales.xlsx');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="temporales.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
} else {
    echo "No se encontraron resultados.";
}

$conn->close();
?>