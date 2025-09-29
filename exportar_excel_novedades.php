<?php
// exportar_excel_novedades.php (Versión con resaltado de filas rechazadas)

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

session_start();
require_once('conn.php');
// La función procesarCambiosVinculacion debe estar en funciones.php
require('funciones.php'); 

// 1. Obtener datos de la sesión
$anio_semestre = $_SESSION['anio_semestre'] ?? '';
$tipo_usuario = $_SESSION['tipo_usuario'] ?? null;
$id_facultad = $_SESSION['id_facultad'] ?? null;
$id_departamento = $_SESSION['id_departamento'] ?? null;

// 2. Verificación robusta para todos los tipos de usuario
if (empty($anio_semestre) || is_null($tipo_usuario)) {
    die("Error: La sesión es inválida o ha expirado. Por favor, vuelva a la página anterior e intente de nuevo.");
}
if ($tipo_usuario == 2 && is_null($id_facultad)) {
    die("Error: No se encontró el ID de la facultad en la sesión para el usuario de tipo Facultad.");
}
if ($tipo_usuario == 3 && is_null($id_departamento)) {
    die("Error: No se encontró el ID del departamento en la sesión para el usuario de tipo Departamento.");
}

function procesarCambiosVinculacion($solicitudes) {
    $transacciones = [];
    $otras_novedades = [];
    $resultado_final = [];

    // PASO 1: Clasificar solicitudes por 'oficio' Y LUEGO por 'cédula'.
    foreach ($solicitudes as $sol) {
        $id_transaccion = $sol['oficio_con_fecha'] ?? null;
        $cedula = $sol['cedula'] ?? null;
        $novedad = strtolower($sol['novedad']);

        if ($id_transaccion && $cedula && ($novedad === 'adicionar' || $novedad === 'adicion' || $novedad === 'eliminar')) {
            // Agrupamos por oficio, y dentro, por cédula.
            $transacciones[$id_transaccion][$cedula][$novedad] = $sol;
        } else {
            // El resto (Modificar, etc.) va a otra lista.
            $otras_novedades[] = $sol;
        }
    }

    // PASO 2: Procesar las transacciones doblemente agrupadas
    foreach ($transacciones as $id_transaccion => $cedulas_en_oficio) {
        foreach ($cedulas_en_oficio as $cedula => $partes) {
            
            $sol_adicion = $partes['adicion'] ?? $partes['adicionar'] ?? null;

            // Verificamos si para ESTA CÉDULA DENTRO DE ESTE OFICIO, existe el par completo
            if ($sol_adicion && isset($partes['eliminar'])) {
                // ¡Es un verdadero "Cambio de Vinculación"!
                $sol_eliminacion = $partes['eliminar'];

                // --- Lógica para construir la descripción (sin cambios) ---
                $tipo_docente_anterior = ($sol_eliminacion['tipo_docente'] === 'Catedra') ? 'Cátedra' : $sol_eliminacion['tipo_docente'];
                $estado_anterior = "Sale de " . $tipo_docente_anterior;
                if ($sol_eliminacion['tipo_docente'] === 'Ocasional') {
                    if ($sol_eliminacion['tipo_dedicacion']) $estado_anterior .= " " . $sol_eliminacion['tipo_dedicacion'] . " Popayán";
                    elseif ($sol_eliminacion['tipo_dedicacion_r']) $estado_anterior .= " " . $sol_eliminacion['tipo_dedicacion_r'] . " Regionalización";
                } elseif ($sol_eliminacion['tipo_docente'] === 'Catedra') {
                    if ($sol_eliminacion['horas'] && $sol_eliminacion['horas'] > 0) $estado_anterior .= " " . $sol_eliminacion['horas'] . " horas Popayán";
                    elseif ($sol_eliminacion['horas_r'] && $sol_eliminacion['horas_r'] > 0) $estado_anterior .= " " . $sol_eliminacion['horas_r'] . " horas Regionalización";
                }
                
                // Modificamos la solicitud de "adición" para que represente el cambio completo
                $sol_adicion['novedad'] = 'Cambio Vinculación';
                $observacion_existente = trim($sol_adicion['s_observacion']);
                $sol_adicion['s_observacion'] = $observacion_existente . ($observacion_existente ? ' ' : '') . '(' . $estado_anterior . ')';

                $resultado_final[] = $sol_adicion;

            } else {
                // Si no hay un par, se añaden las partes individuales a la lista de "otras"
                if ($sol_adicion) $otras_novedades[] = $sol_adicion;
                if (isset($partes['eliminar'])) $otras_novedades[] = $partes['eliminar'];
            }
        }
    }

    // Unimos los cambios procesados con el resto de novedades
    return array_merge($resultado_final, $otras_novedades);
}

// 4. Construir la consulta SQL
// 4. Construir la consulta SQL universal (Versión Segura)
$sql = "SELECT s.*, f.NOMBREF_FAC as nombre_facultad, d.depto_nom_propio as nombre_departamento
        FROM solicitudes_working_copy s
        JOIN facultad f ON s.facultad_id = f.PK_FAC
        JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO
        WHERE s.anio_semestre = ?";
$params = [$anio_semestre];
$types = "s";

// Filtros de seguridad (NECESARIOS)
if ($tipo_usuario == 2) { // Facultad
    $sql .= " AND s.facultad_id = ?";
    $params[] = $id_facultad;
    $types .= "i";
} elseif ($tipo_usuario == 3) { // Departamento
    $sql .= " AND s.departamento_id = ?";
    $params[] = $id_departamento;
    $types .= "i";
}
// NOTA: No añadimos ORDER BY aquí, lo haremos después en PHP para asegurar el orden.

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado_bruto = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($resultado_bruto)) {
    die("No se encontraron datos para exportar con los filtros aplicados.");
}

// 5. Procesar los datos (esto desordena el resultado de la BD)
$datos_procesados = procesarCambiosVinculacion($resultado_bruto);

// ==========================================================
// ===== ¡AQUÍ ESTÁ LA SOLUCIÓN! Ordenamos el array en PHP =====
// ==========================================================
// Se crean "columnas" temporales para poder ordenar el array
$col_facultad = [];
$col_depto = [];
$col_fecha = [];
$col_nombre = [];

// Se construyen las columnas manualmente para asegurar tamaños consistentes
foreach ($datos_procesados as $fila) {
    $col_facultad[] = $fila['nombre_facultad'] ?? '';
    $col_depto[] = $fila['depto_nom_propio'] ?? '';
    $col_fecha[] = $fila['fecha_oficio_depto'] ?? ''; // <-- Si no existe, se añade ''
    $col_nombre[] = $fila['nombre'] ?? '';
}

// Se ordena el array $datos_procesados usando las columnas como guía
// Esta línea ya no dará error
array_multisort(
    $col_facultad, SORT_ASC,
    $col_depto, SORT_ASC,
    $col_fecha, SORT_ASC,
    $col_nombre, SORT_ASC,
    $datos_procesados
);
// ==========================================================

// 6. Crear el archivo Excel (El resto del script no necesita cambios)
// ...
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Novedades ' . $anio_semestre);

// Encabezados y estilo (sin cambios)
$headers = [
    'Facultad', 'Departamento', 'Oficio Depto', 'Oficio Fac', 'Novedad', 'Cédula', 'Nombre Completo',
    'Tipo Docente', 'Dedicación/Horas Popayán', 'Dedicación/Horas Reg.', 'Estado Facultad', 'Estado VRA', 'Observación Facultad', 'Observación VRA',
    'Enviado RRHH' // <-- NUEVO ENCABEZADO
];
$sheet->fromArray($headers, NULL, 'A1');
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003366']]
];
// Cambiamos el rango para incluir la nueva columna O
$sheet->getStyle('A1:O1')->applyFromArray($headerStyle); 
// 7. Llenar el archivo con los datos
$row_num = 2;
foreach ($datos_procesados as $fila) {
    // Formatear dedicación/horas
    $dedicacion_pop = '';
    $dedicacion_reg = '';
    if (strtolower($fila['tipo_docente']) === 'ocasional') {
        $dedicacion_pop = $fila['tipo_dedicacion'];
        $dedicacion_reg = $fila['tipo_dedicacion_r'];
    } elseif (strtolower($fila['tipo_docente']) === 'catedra') {
        $dedicacion_pop = $fila['horas'] > 0 ? $fila['horas'] . 'h' : '';
        $dedicacion_reg = $fila['horas_r'] > 0 ? $fila['horas_r'] . 'h' : '';
    }

    $enviado_rrhh = ($fila['archivado'] ?? 0) == 1 ? 'OK' : 'NO';

$datos_fila = [
    $fila['nombre_facultad'], $fila['nombre_departamento'], $fila['oficio_con_fecha'], $fila['oficio_con_fecha_fac'],
    $fila['novedad'], $fila['cedula'], $fila['nombre'], $fila['tipo_docente'], $dedicacion_pop, $dedicacion_reg,
    $fila['estado_facultad'], $fila['estado_vra'], $fila['observacion_facultad'], $fila['observacion_vra'],
    $enviado_rrhh // <-- NUEVO DATO
];
    $sheet->fromArray($datos_fila, NULL, 'A' . $row_num);

    // ==========================================================
    // ===== ¡AQUÍ ESTÁ LA NUEVA LÓGICA DE RESALTADO! =====
    // ==========================================================
    // Comprobamos si alguno de los estados es 'RECHAZADO'
     if (strtoupper($fila['estado_facultad']) === 'RECHAZADO' || strtoupper($fila['estado_vra']) === 'RECHAZADO') {
        // Definimos el estilo de fondo rosa suave
        $rejectedStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF0F1'] // Color rosa claro
            ]
        ];
        // Aplicamos el estilo a toda la fila
        $sheet->getStyle('A' . $row_num . ':O' . $row_num)->applyFromArray($rejectedStyle);
    } 
    // Si no hay rechazos, comprobamos si fue APROBADO por VRA
    elseif (strtoupper($fila['estado_vra']) === 'APROBADO') {
        // Definimos el estilo de fondo verde claro
        $approvedStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0FFF4'] // Un color verde menta muy suave
            ]
        ];
        // Aplicamos el estilo a toda la fila
        $sheet->getStyle('A' . $row_num . ':O' . $row_num)->applyFromArray($approvedStyle);
    }
    // ==========================================================
    
    $row_num++;
}

// Auto-ajustar el ancho de las columnas
foreach(range('A', 'O') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}
// =================================================================
// ===== ¡AQUÍ SE AÑADE LA LEYENDA DE COLORES! =====
// =================================================================
// Dejamos dos filas de espacio
$row_num += 2;

// Título de la leyenda
$sheet->setCellValue('B' . $row_num, 'Leyenda de Colores');
$sheet->getStyle('B' . $row_num)->getFont()->setBold(true);
$row_num++;

// Fila para RECHAZADO (Fondo Rosa)
$sheet->setCellValue('B' . $row_num, 'NO APROBADAS (Por Facultad o Vicerrectoría)');
$sheet->getStyle('A' . $row_num)->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setRGB('FFF0F1');

$row_num++;

// Fila para APROBADO (Fondo Verde)
$sheet->setCellValue('B' . $row_num, 'APROBADAS (Por Vicerrectoría)');
$sheet->getStyle('A' . $row_num)->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setRGB('F0FFF4');

$row_num++;

// Fila para EN TRÁMITE (Sin Fondo)
$sheet->setCellValue('B' . $row_num, 'En Trámite');
$sheet->getStyle('A' . $row_num)->getFill() // Celda de ejemplo sin fondo
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setRGB('FFFFFF');
$sheet->getStyle('A' . $row_num)->getFont()->setBold(true); // Para que el borde sea visible
$sheet->getStyle('A' . $row_num)->getBorders()->getOutline()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


// =================================================================
// 8. Forzar la descarga del archivo
$writer = new Xlsx($spreadsheet);
$filename = 'Reporte_Novedades_' . $anio_semestre . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit();
?>