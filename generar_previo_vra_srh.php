<?php
// generar_word_vra_srh.php (Versión Definitiva con Tablas Separadas)

// --- 1. CONFIGURACIÓN INICIAL Y LIBRERÍAS ---
require 'vendor/autoload.php';
require 'conn.php';
require 'funciones.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

// --- 2. RECEPCIÓN Y VALIDACIÓN DE DATOS ---
session_start();
if (!isset($_SESSION['name']) || $_SESSION['tipo_usuario'] != 1) {
    die("Acceso denegado.");
}

$seleccionados_json = $_POST['seleccionados'] ?? null;
if (!$seleccionados_json) die("No se recibieron datos.");
$seleccionados = json_decode($seleccionados_json, true);
if (empty($seleccionados)) die("Datos no válidos.");

$ids_seleccionados = [];
$valores_editados = [];
foreach ($seleccionados as $sel) {
    $id = intval($sel['id']);
    $ids_seleccionados[] = $id;
    $valores_editados[$id] = [
        'puntos' => $sel['puntos'],
        'tipo_reemplazo' => $sel['tipo_reemplazo']
    ];
}

if (empty($ids_seleccionados)) die("No se seleccionaron solicitudes.");
$ids_string = implode(',', $ids_seleccionados);
$sql_update_puntos = "UPDATE solicitudes_working_copy SET enviado_a_puntos = 1 WHERE id_solicitud IN ($ids_string)";
$conn->query($sql_update_puntos);
// --- 3. OBTENER DATOS COMPLETOS (VERSIÓN FINAL Y PRECISA) ---

// Paso 3.1: Obtener las filas que el usuario seleccionó explícitamente.
// --- 3. OBTENER DATOS COMPLETOS (VERSIÓN FINAL Y PRECISA) ---

// Paso 3.1: Obtener las filas que el usuario seleccionó explícitamente (los chulitos)
$sql_inicial = "SELECT s.*, s_orig.puntos 
                FROM solicitudes_working_copy s
                LEFT JOIN solicitudes s_orig ON s_orig.id_novedad = s.id_solicitud
                WHERE s.id_solicitud IN ($ids_string)";
$resultado_inicial = $conn->query($sql_inicial);
if (!$resultado_inicial) die("Error en la consulta inicial: " . $conn->error);

$filas_seleccionadas = [];
$oficios_de_cambios_seleccionados = [];
$cedulas_seleccionadas = []; // NUEVO: Para filtrar la pareja exacta

while ($fila = $resultado_inicial->fetch_assoc()) {
    $filas_seleccionadas[$fila['id_solicitud']] = $fila;
    $cedulas_seleccionadas[] = $fila['cedula']; // Guardamos la cédula
    
    // Si una fila seleccionada es parte de un cambio, guardamos su oficio
    if (in_array(strtolower($fila['novedad']), ['adicionar', 'adicion', 'eliminar']) && !empty($fila['oficio_con_fecha'])) {
        $oficios_de_cambios_seleccionados[] = $fila['oficio_con_fecha'];
    }
}

// Inicialmente, las filas a procesar son las seleccionadas
$filas_completas = $filas_seleccionadas;

// Paso 3.2: Buscar la pareja EXACTA (Misma Cédula + Mismo Oficio)
if (!empty($oficios_de_cambios_seleccionados) && !empty($cedulas_seleccionadas)) {
    $oficios_unicos = array_unique($oficios_de_cambios_seleccionados);
    $cedulas_unicas = array_unique($cedulas_seleccionadas);
    
    $placeholders_oficios = implode(',', array_fill(0, count($oficios_unicos), '?'));
    $placeholders_cedulas = implode(',', array_fill(0, count($cedulas_unicas), '?'));
    
    // SQL QUIRÚRGICO: Solo trae parejas si coinciden Oficio Y Cédula
    $sql_parejas = "SELECT s.*, s_orig.puntos 
                    FROM solicitudes_working_copy s
                    LEFT JOIN solicitudes s_orig ON s_orig.id_novedad = s.id_solicitud
                    WHERE s.oficio_con_fecha IN ($placeholders_oficios) 
                    AND s.cedula IN ($placeholders_cedulas)
                    AND s.anio_semestre = ? 
                    AND s.estado_vra = 'APROBADO'
                    AND s.archivado = 0";
    
    $stmt_parejas = $conn->prepare($sql_parejas);
    $types = str_repeat('s', count($oficios_unicos)) . str_repeat('s', count($cedulas_unicas)) . 's';
    $params = array_merge($oficios_unicos, $cedulas_unicas, [$_SESSION['anio_semestre']]);
    $stmt_parejas->bind_param($types, ...$params);
    
    $stmt_parejas->execute();
    $resultado_parejas = $stmt_parejas->get_result();
    
    if ($resultado_parejas) {
        while ($fila_pareja = $resultado_parejas->fetch_assoc()) {
            if (!isset($filas_completas[$fila_pareja['id_solicitud']])) {
                $filas_completas[$fila_pareja['id_solicitud']] = $fila_pareja;
            }
        }
    }
    $stmt_parejas->close();
}   
// --- 4. NUEVO: ACTUALIZAR REGISTROS A 'ARCHIVADO' ---
$todos_los_ids_para_archivar = array_keys($filas_completas);

// --- 4.2 PROCESAR Y CONSOLIDAR DATOS (LÓGICA IDÉNTICA A LA PÁGINA PRINCIPAL) ---
// --- 4.2 PROCESAR Y CONSOLIDAR DATOS (LÓGICA FINAL Y CORRECTA) ---
$datos_para_word = [];
$transacciones_agrupadas = [];
$otras_novedades = [];

// PASO 1: Clasificar por 'oficio' y LUEGO por 'cédula'
foreach ($filas_completas as $fila) {
    // Usamos 'oficio_con_fecha' como ID de transacción
    $id_transaccion = $fila['oficio_con_fecha'] ?? null;
    $cedula = $fila['cedula'] ?? null;
    $novedad = strtolower($fila['novedad']);

    // --- CAMBIO CLAVE: Evitar que los NN (222) se solapen ---
    // Si es 222, usamos el ID de solicitud para diferenciar a cada NN
    $identificador_persona = ($cedula === '222') ? '222_' . $fila['id_solicitud'] : $cedula;

    if ($id_transaccion && $cedula && ($novedad === 'adicionar' || $novedad === 'adicion' || $novedad === 'eliminar')) {
        // --- USAMOS EL NUEVO IDENTIFICADOR AQUÍ ---
        $transacciones_agrupadas[$id_transaccion][$identificador_persona][$novedad] = $fila;
    } else {
        // El resto (Modificar, etc.) va a otra lista
        $otras_novedades[] = $fila;
    }
}

// PASO 2: Procesar las transacciones doblemente agrupadas
foreach ($transacciones_agrupadas as $id_transaccion => $cedulas_en_oficio) {
    foreach ($cedulas_en_oficio as $cedula => $partes) {
        
        // Verificamos si para ESTA CÉDULA DENTRO DE ESTE OFICIO, existe el par completo
        $sol_adicion = $partes['adicion'] ?? $partes['adicionar'] ?? null;

        if ($sol_adicion && isset($partes['eliminar'])) {
            // ¡CASO 1: Es un verdadero "Cambio de Vinculación"!
            $fila_final = $sol_adicion;
            $fila_inicial = $partes['eliminar'];
            
            $fila_final['novedad_display'] = 'Modificar (Vinculación)';

            // --- Lógica para calcular vinculaciones (sin cambios) ---
            $vinculacion_inicial = '';
            if ($fila_inicial['tipo_docente'] === 'Ocasional') {
                $vinculacion_inicial = !empty($fila_inicial['tipo_dedicacion']) ? $fila_inicial['tipo_dedicacion'] : ($fila_inicial['tipo_dedicacion_r'] ?? '');
            } elseif ($fila_inicial['tipo_docente'] === 'Catedra') {
                $total_horas = floatval($fila_inicial['horas']) + floatval($fila_inicial['horas_r']);
                $vinculacion_inicial = ($total_horas > 0) ? $total_horas . ' hrs' : '';
            }
            $fila_final['dedicacion_unificada_inicial'] = $vinculacion_inicial;

            $vinculacion_final = '';
            if ($fila_final['tipo_docente'] === 'Ocasional') {
                $vinculacion_final = !empty($fila_final['tipo_dedicacion']) ? $fila_final['tipo_dedicacion'] : ($fila_final['tipo_dedicacion_r'] ?? '');
            } elseif ($fila_final['tipo_docente'] === 'Catedra') {
                $total_horas = floatval($fila_final['horas']) + floatval($fila_final['horas_r']);
                $vinculacion_final = ($total_horas > 0) ? $total_horas . ' hrs' : '';
            }
            $fila_final['dedicacion_unificada_final'] = $vinculacion_final;

            $datos_para_word[] = $fila_final;

        } else {
            // --- CASO 2: No hay par. Son solicitudes individuales. ---
            // Añadimos las partes que existan a la lista de "otras" para procesarlas después.
            if ($sol_adicion) $otras_novedades[] = $sol_adicion;
            if (isset($partes['eliminar'])) $otras_novedades[] = $partes['eliminar'];
        }
    }
}

// PASO 3: Procesar todas las solicitudes que no formaron un par (Modificar, y las individuales)
foreach ($otras_novedades as $fila) {
    switch (strtolower($fila['novedad'])) {
        case 'modificar': $fila['novedad_display'] = 'Modificar (Dedicación)'; break;
        case 'eliminar':  $fila['novedad_display'] = 'Liberar'; break;
        case 'adicionar':
        case 'adicion':
            $fila['novedad_display'] = 'Vincular'; break;
        default: $fila['novedad_display'] = $fila['novedad'];
    }
    
    // --- Lógica para calcular vinculaciones (sin cambios) ---
    $vinculacion_final = '';
    if ($fila['tipo_docente'] === 'Ocasional') {
        $vinculacion_final = !empty($fila['tipo_dedicacion']) ? $fila['tipo_dedicacion'] : ($fila['tipo_dedicacion_r'] ?? '');
    } elseif ($fila['tipo_docente'] === 'Catedra') {
        $total_horas = floatval($fila['horas']) + floatval($fila['horas_r']);
        $vinculacion_final = ($total_horas > 0) ? $total_horas . ' hrs' : '';
    }
    $fila['dedicacion_unificada_final'] = $vinculacion_final;

    $vinculacion_inicial = '';
    if (strtolower($fila['novedad']) === 'modificar') {
        if ($fila['tipo_docente'] === 'Ocasional') {
            $vinculacion_inicial = !empty($fila['tipo_dedicacion_inicial']) ? $fila['tipo_dedicacion_inicial'] : ($fila['tipo_dedicacion_r_inicial'] ?? '');
        } elseif ($fila['tipo_docente'] === 'Catedra') {
            $total_horas_inicial = floatval($fila['horas_inicial']) + floatval($fila['horas_r_inicial']);
            $vinculacion_inicial = ($total_horas_inicial > 0) ? $total_horas_inicial . ' hrs' : '';
        }
    }
    $fila['dedicacion_unificada_inicial'] = $vinculacion_inicial;
    
    $datos_para_word[] = $fila;
}
// --- 5. DATOS FINALES: AÑADIR INFO, SOBRESCRIBIR Y ORDENAR ---
$datos_completos = [];
foreach ($datos_para_word as $fila_procesada) {
    $stmt_info = $conn->prepare("SELECT d.depto_nom_propio, f.Nombre_fac_minb FROM deparmanentos d JOIN facultad f ON d.FK_FAC = f.PK_FAC WHERE d.PK_DEPTO = ?");
    $stmt_info->bind_param("i", $fila_procesada['departamento_id']);
    $stmt_info->execute();
    $info_adicional = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();
    $fila_procesada['depto_nom_propio'] = $info_adicional['depto_nom_propio'] ?? 'N/A';
    $fila_procesada['Nombre_fac_minb'] = $info_adicional['Nombre_fac_minb'] ?? 'N/A';
    $id = $fila_procesada['id_solicitud'];
   /* if (isset($valores_editados[$id])) {
        $fila_procesada['puntos'] = $valores_editados[$id]['puntos'];
        $fila_procesada['tipo_reemplazo'] = $valores_editados[$id]['tipo_reemplazo'];
    }*/
    
    if (isset($valores_editados[$id])) {
        // SOBRESCRIBIMOS los puntos porque el usuario pudo cambiarlos en la tabla
        $fila_procesada['puntos'] = $valores_editados[$id]['puntos'];
        
        // --- AQUÍ ESTÁ EL CAMBIO ---
        // NO sobrescribimos 'tipo_reemplazo' con lo que viene del formulario ($valores_editados)
        // ya que ese viene truncado con "..." desde la tabla visual.
        // Usamos el que ya trae $fila_procesada (que viene directo del SELECT de la DB)
        // $fila_procesada['tipo_reemplazo'] = $valores_editados[$id]['tipo_reemplazo']; // <--- COMENTA O ELIMINA ESTA LÍNEA
    }
    
    $datos_completos[] = $fila_procesada;
}

usort($datos_completos, function($a, $b) {
    $prioridad = ['Modificar (Vinculación)' => 1, 'Modificar (Dedicación)' => 1, 'Vincular' => 2, 'Liberar' => 3];
    $prioridadA = $prioridad[$a['novedad_display']] ?? 99;
    $prioridadB = $prioridad[$b['novedad_display']] ?? 99;
    if ($prioridadA !== $prioridadB) return $prioridadA <=> $prioridadB;
    $compFacultad = strcmp($a['Nombre_fac_minb'], $b['Nombre_fac_minb']);
    if ($compFacultad !== 0) return $compFacultad;
    $compDepto = strcmp($a['depto_nom_propio'], $b['depto_nom_propio']);
    if ($compDepto !== 0) return $compDepto;
    return strcmp($a['nombre'], $b['nombre']);
});

// --- 6. AGRUPAR DATOS FINALES POR TIPO DE NOVEDAD ---
$modificaciones = [];
$vinculaciones = [];
$liberaciones = [];

foreach ($datos_completos as $fila) {
    if (str_starts_with($fila['novedad_display'], 'Modificar')) {
        $modificaciones[] = $fila;
    } elseif ($fila['novedad_display'] === 'Vincular') {
        $vinculaciones[] = $fila;
    } elseif ($fila['novedad_display'] === 'Liberar') {
        $liberaciones[] = $fila;
    }
}

// --- 7. GENERAR EL DOCUMENTO WORD CON SECCIONES SEPARADAS ---
$phpWord = new PhpWord();
$phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('es-CO'));

date_default_timezone_set('America/Bogota');

// Configurar el idioma de las fechas a español
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'Spanish');
//$section = $phpWord->addSection();
$section = $phpWord->addSection(['marginLeft' => 600, 'marginRight' => 600, 'marginTop' => 600, 'marginBottom' => 600]);
$section->addText("Solicitud de Trámite de Vinculación en RRHH", ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

// Arreglo para traducir meses y días
$meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
$fecha_espanol = date('d') . " de " . $meses[date('n')-1] . " de " . date('Y, h:i A');

// Añadir al documento (reemplaza tu línea actual de addText)
$section->addText("Generado el: " . $fecha_espanol, ['size' => 10, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 480]);

//$section->addText("Generado el: " . date('d \d\e F \d\e Y, h:i A'), ['size' => 10, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 480]);

//$tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80];
$tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 40];
$phpWord->addTableStyle('VraTable', $tableStyle);
$headerCellStyle = ['bgColor' => 'F2F2F2', 'valign' => 'center'];
//$headerTextStyle = ['bold' => true, 'size' => 9];
$headerTextStyle = ['bold' => true, 'size' => 8, 'font' => 'Arial']; // Tamaño 8
$headerParagraphStyle = ['alignment' => Jc::CENTER];
//$cellTextStyle = ['size' => 9, 'font' => 'sans-serif'];
$cellTextStyle = ['size' => 8, 'font' => 'Arial']; // Tamaño 8
//$cellParagraphStyle = ['alignment' => Jc::LEFT];
$cellParagraphStyle = ['alignment' => Jc::LEFT, 'spaceAfter' => 0, 'spaceBefore' => 0];
$headerParagraphStyle = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'spaceBefore' => 0];

function crearTablaParaNovedad($section, $titulo, $datos, $headers, $styles, $cellWidths) {
    if (empty($datos)) return;
    $section->addText($titulo, ['bold' => true, 'size' => 12, 'color' => '333333'], ['spaceBefore' => 360, 'spaceAfter' => 120]);
    $table = $section->addTable('VraTable');
    $table->addRow();
    
    // Añadir celdas con anchos personalizados
    foreach ($headers as $index => $header) {
        $table->addCell($cellWidths[$index], $styles['headerCellStyle'])->addText($header, $styles['headerTextStyle'], $styles['headerParagraphStyle']);
    }
    
    //foreach ($datos as $solicitud) {
    //$table->addRow();
    
    foreach ($datos as $solicitud) {
    $table->addRow(200); // Fila compacta
    
    // Función de limpieza rápida
    $limpiar = function($txt) {
        return htmlspecialchars($txt ?? '', ENT_QUOTES, 'UTF-8');
    };

    $oficio_completo = $limpiar($solicitud['oficio_fac'] . ' (' . $solicitud['fecha_oficio_fac'] . ')');
    $depto_completo = $limpiar($solicitud['depto_nom_propio']);
    $nombre_completo = $limpiar($solicitud['nombre']);
    $observacion = $limpiar($solicitud['s_observacion'] ?? '');
    $tipoReemplazo = $limpiar($solicitud['tipo_reemplazo'] ?? '');
        
    $oficio_completo = $solicitud['oficio_fac'] . ' (' . $solicitud['fecha_oficio_fac'] . ')';
    $depto_completo = $solicitud['depto_nom_propio'];
    
    // Añadir celdas con anchos personalizados (primeras 7 columnas normales)
    $table->addCell($cellWidths[0])->addText(htmlspecialchars($oficio_completo), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[1])->addText(htmlspecialchars($depto_completo), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[2])->addText(htmlspecialchars($solicitud['cedula']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[3])->addText(htmlspecialchars($solicitud['nombre']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[4])->addText(htmlspecialchars($solicitud['dedicacion_unificada_inicial']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[5])->addText(htmlspecialchars($solicitud['dedicacion_unificada_final']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[6])->addText(htmlspecialchars($solicitud['puntos'] ?? ''), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    $table->addCell($cellWidths[7])->addText(htmlspecialchars($solicitud['s_observacion'] ?? ''), ['size' => 7], $styles['cellParagraphStyle']);
    // --- TRUCO FINAL: Columna 8 (Observación/tipo_reemplazo) ---
    $tipoReemplazo = $solicitud['tipo_reemplazo'] ?? '';
    
    // OPCIÓN A: Usar TextRun con fuente MÁS PEQUEÑA
    $textRun = $table->addCell($cellWidths[7])->addTextRun($styles['cellParagraphStyle']);
    $textRun->addText(htmlspecialchars($tipoReemplazo), ['size' => 7, 'font' => 'sans-serif']); // ¡7 puntos!
    
    /*
    // OPCIÓN B: Dividir en dos líneas si es muy largo
    $tipoReemplazo = $solicitud['tipo_reemplazo'] ?? '';
    if (strlen($tipoReemplazo) > 15) {
        $partes = explode(' ', $tipoReemplazo);
        $mitad = ceil(count($partes) / 2);
        $linea1 = implode(' ', array_slice($partes, 0, $mitad));
        $linea2 = implode(' ', array_slice($partes, $mitad));
        
        $textRun = $table->addCell($cellWidths[7])->addTextRun($styles['cellParagraphStyle']);
        $textRun->addText(htmlspecialchars($linea1), ['size' => 8]);
        $textRun->addTextBreak(); // Salto de línea
        $textRun->addText(htmlspecialchars($linea2), ['size' => 8]);
    } else {
        $table->addCell($cellWidths[7])->addText(htmlspecialchars($tipoReemplazo), ['size' => 8], $styles['cellParagraphStyle']);
    }
    */
    
    /*
    // OPCIÓN C: Forzar el texto completo sin truncamiento
    $cell = $table->addCell($cellWidths[7]);
    // Primero intenta con estilo normal
    $cell->addText(htmlspecialchars($tipoReemplazo), ['size' => 8], $styles['cellParagraphStyle']);
    // Si aún se trunca, agrega un espacio invisible al final para "engañar" a Word
    if (strlen($tipoReemplazo) > 20) {
        $cell->addText(' ', ['size' => 1]); // Carácter invisible
    }
    */
}
}

//$headers = ['Oficio', 'Departamento', 'Cédula', 'Nombre', 'Vin. Inic', 'Vin. Fin', 'Puntos', 'Observación'];
$headers = ['Oficio', 'Departamento', 'Cédula', 'Nombre', 'Vin. Inic', 'Vin. Fin', 'Puntos', 'Observ.', 'Tipo Trámite'];
// Definir anchos personalizados para cada columna (en twips, 1 cm ≈ 567 twips)
/*$cellWidths = [
    2200, // Oficio
    2200, // Departamento
    1300, // Cédula
    3500, // Nombre
    1200, // Vin. Inic
    1200, // Vin. Fin
    1200, // Puntos
    2500, // s_observacion (Obs. Depto) - NUEVA
    2500  // tipo_reemplazo (Tipo Trámite)
];
*/
$cellWidths = [
    1900, // Oficio
    1900, // Departamento
    1100, // Cédula
    3100, // Nombre
    950,  // Vin. Inic
    950,  // Vin. Fin
    800,  // Puntos
    2300, // Observación Depto
    2300  // Tipo Trámite
];

$styles = [
    'headerCellStyle' => $headerCellStyle, 'headerTextStyle' => $headerTextStyle, 'headerParagraphStyle' => $headerParagraphStyle,
    'cellTextStyle' => $cellTextStyle, 'cellParagraphStyle' => $cellParagraphStyle
];

crearTablaParaNovedad($section, 'Modificar', $modificaciones, $headers, $styles, $cellWidths);
crearTablaParaNovedad($section, 'Vincular', $vinculaciones, $headers, $styles, $cellWidths);
crearTablaParaNovedad($section, 'Liberar', $liberaciones, $headers, $styles, $cellWidths);

// --- 8. ENVIAR EL DOCUMENTO AL NAVEGADOR ---
$filename = "tramite_vra_srh_" . date('Ymd_His') . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit;
?>