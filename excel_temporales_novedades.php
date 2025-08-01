<?php
// Incluir la librería PHPSpreadsheet
require 'conn.php';
require 'excel/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Obtener los valores de los filtros del formulario
$departamento_id = isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null;
$tipo_usuario = isset($_GET['tipo_usuario']) ? $_GET['tipo_usuario'] : null;

$facultad_id = isset($_GET['facultad_id']) ? $_GET['facultad_id'] : null;
$anio_semestre = isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : null;

if ($tipo_usuario == '1') {
    $where = "WHERE anio_semestre = '$anio_semestre' ";
} else if ($tipo_usuario == '2') {
    $where = "WHERE anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id'";
} else if ($tipo_usuario == '3') {
    $where = "WHERE anio_semestre = '$anio_semestre' AND facultad.PK_FAC ='$facultad_id' AND deparmanentos.PK_DEPTO ='$departamento_id' ";
}

// Verificar la conexión a la base de datos
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Primero obtenemos todos los datos de solicitudes_novedades para el periodo
$sqlNovedades = "SELECT * FROM solicitudes_novedades WHERE periodo_anio = '$anio_semestre'";
$resultNovedades = $conn->query($sqlNovedades);
$novedades = [];
while ($row = $resultNovedades->fetch_assoc()) {
    $novedades[] = $row;
}

// Definir la consulta SQL principal
$sqle = "SELECT 
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
    solicitudes.anio_semestre, 
    facultad.NOMBREF_FAC, 
    deparmanentos.NOMBRE_DEPTO_CORT,         deparmanentos.PK_DEPTO AS departamento_id,  

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

// Ejecutar la consulta SQL principal
$result = $conn->query($sqle);

// Verificar si hay resultados
if ($result->num_rows > 0) {
    // Crear un nuevo objeto Spreadsheet
    $spreadsheet = new Spreadsheet();

    // Obtener la hoja activa
    $sheet = $spreadsheet->getActiveSheet();

    // Definir los estilos mejorados
    $headerStyle = [
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    // Definir los encabezados con la nueva columna para el detalle de novedades
    $headers = [
        'Semestre', 'Facultad', 'Departamento', 'id_depto', 'Sede', 'Cédula', 'Nombre', 
        'Tipo Docente', 'Dedicación', 'Horas', 'Estado_fac', 'Envio_fac',
        'Estado_vra', 'ID_FAC', 'AnexaHV', 'ActualizaHV', 'Estado', 'Novedad', 'Detalle Novedad', 'Observacion Novedad', 'Tipo Novedad'
    ];

    // Escribir los encabezados
    $sheet->fromArray($headers, NULL, 'A1');

    // Aplicar estilos a los encabezados
    $sheet->getStyle('A1:U1')->applyFromArray($headerStyle);

    // Establecer el ancho de las columnas
    $columnWidths = [
        'A' => 20, 'B' => 30, 'C' => 15, 'D' => 20, 'E' => 15, 
        'F' => 30, 'G' => 20, 'H' => 20, 'I' => 15, 'J' => 15, 
        'K' => 15, 'L' => 15, 'M' => 10, 'N' => 10, 'O' => 10,
        'P' => 15, 'Q' => 15, 'R' => 15 , 'S' => 45, 'T' => 35, 'U' => 15 // Ancho mayor para el detalle de novedades
    ];
    foreach ($columnWidths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }

    // Recorrer los resultados y escribirlos en el archivo Excel
    $row = 2;
while ($row_data = $result->fetch_assoc()) {
    // Buscar coincidencias en la tabla de novedades
    $detalle_novedad = '';
    foreach ($novedades as $novedad) {
        // Decodificar el JSON del detalle de novedad
        $detalle_json = json_decode($novedad['detalle_novedad'], true);
        
        if ($novedad['periodo_anio'] == $row_data['anio_semestre'] &&
            $novedad['departamento_id'] == $row_data['departamento_id'] &&
            isset($detalle_json['cedula']) &&
            $detalle_json['cedula'] == $row_data['cedula']) {
            
            // Formatear el JSON para mostrarlo con saltos de línea
            $detalle_formateado = '';
            foreach ($detalle_json as $key => $value) {
                // Reemplazar guiones bajos por espacios y capitalizar
                $key_formatted = ucwords(str_replace('_', ' ', $key));
                $detalle_formateado .= "$key_formatted: $value\n";
            }
            
            $detalle_novedad = trim($detalle_formateado);
            break;
        }
    }
    
    // Agregar el detalle de novedad formateado al array de datos
    $row_data['detalle_novedad'] = $detalle_novedad;
    
    // Escribir los datos en la fila
    $sheet->fromArray(array_values($row_data), NULL, 'A' . $row);
    
    // Configurar el estilo para ajustar texto en la columna del JSON
    // Asumiendo que 'detalle_novedad' es la última columna (ajusta la letra según tu caso)
    $last_col_letter = 'R'; // Cambia esto según tu estructura real de columnas
    $sheet->getStyle($last_col_letter.$row)->getAlignment()->setWrapText(true);
    
    // Ajustar altura de fila automáticamente para mostrar todo el contenido
    $sheet->getRowDimension($row)->setRowHeight(-1);
    
    // Aplicar estilos condicionales (manteniendo tu lógica original)
    $estado = $row_data['estado'] ?? '';
    $novedad = $row_data['novedad'] ?? '';
    
    $style = [];
    
    if (strtolower($estado) == 'an') {
        $style['fill'] = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FF0000']
        ];
    } elseif (strtolower($novedad) == 'adicionar') {
        $style['fill'] = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '00FF00']
        ];
    } elseif (strtolower($novedad) == 'modificar') {
        $style['fill'] = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFF00']
        ];
    }
    
    if (!empty($style)) {
        $sheet->getStyle('A'.$row.':'.$last_col_letter.$row)->applyFromArray($style);
    }
    
    $row++;
}

    // Guardar el archivo Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save('temporales.xlsx');

    // Descargar el archivo Excel generado
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="temporales.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
} else {
    echo "No se encontraron resultados.";
}

// Cerrar la conexión a la base de datos
$conn->close();
?>