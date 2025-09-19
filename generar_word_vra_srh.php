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

// --- 3. OBTENER DATOS COMPLETOS (LÓGICA CORREGIDA) ---
$sql_inicial = "SELECT s.*, s_orig.puntos 
                FROM solicitudes_working_copy s
                LEFT JOIN solicitudes s_orig ON s_orig.id_novedad = s.id_solicitud
                WHERE s.id_solicitud IN ($ids_string)";
$resultado_inicial = $conn->query($sql_inicial);
if (!$resultado_inicial) die("Error en la consulta inicial: " . $conn->error);

$filas_completas = [];
$cedulas_a_verificar = [];

while ($fila = $resultado_inicial->fetch_assoc()) {
    $filas_completas[$fila['id_solicitud']] = $fila;
    if (in_array(strtolower($fila['novedad']), ['adicionar', 'eliminar'])) {
        $cedulas_a_verificar[] = $fila['cedula'];
    }
}

if (!empty($cedulas_a_verificar)) {
    $cedulas_string_unicas = "'" . implode("','", array_unique($cedulas_a_verificar)) . "'";
    $sql_parejas = "SELECT s.*, s_orig.puntos 
                    FROM solicitudes_working_copy s
                    LEFT JOIN solicitudes s_orig ON s_orig.id_novedad = s.id_solicitud
                    WHERE s.cedula IN ($cedulas_string_unicas) 
                    AND s.anio_semestre = '{$_SESSION['anio_semestre']}' 
                    AND s.estado_vra = 'APROBADO'";
                    
    $resultado_parejas = $conn->query($sql_parejas);
    if ($resultado_parejas) {
        while ($fila_pareja = $resultado_parejas->fetch_assoc()) {
            if (!isset($filas_completas[$fila_pareja['id_solicitud']])) {
                $filas_completas[$fila_pareja['id_solicitud']] = $fila_pareja;
            }
        }
    }
}

// --- 4. PROCESAR Y CONSOLIDAR DATOS (LÓGICA IDÉNTICA A LA PÁGINA PRINCIPAL) ---
$datos_para_word = [];
$solicitudes_agrupadas = [];
foreach ($filas_completas as $fila) {
    $cedula = $fila['cedula'];
    $novedad = strtolower($fila['novedad']);
    $solicitudes_agrupadas[$cedula][$novedad] = $fila;
}

foreach ($solicitudes_agrupadas as $cedula => $novedades) {
    if (isset($novedades['adicionar']) && isset($novedades['eliminar'])) {
        $fila_final = $novedades['adicionar'];
        $fila_inicial = $novedades['eliminar'];
        $fila_final['novedad_display'] = 'Modificar (Vinculación)';
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
        foreach ($novedades as $novedad => $fila) {
            switch (strtolower($fila['novedad'])) {
                case 'modificar': $fila['novedad_display'] = 'Modificar (Dedicación)'; break;
                case 'eliminar':  $fila['novedad_display'] = 'Liberar'; break;
                case 'adicionar': $fila['novedad_display'] = 'Vincular'; break;
                default: $fila['novedad_display'] = $fila['novedad'];
            }
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
    }
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
    if (isset($valores_editados[$id])) {
        $fila_procesada['puntos'] = $valores_editados[$id]['puntos'];
        $fila_procesada['tipo_reemplazo'] = $valores_editados[$id]['tipo_reemplazo'];
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
$section = $phpWord->addSection();
$section->addText("Solicitud de Trámite de Vinculación en RRHH", ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);
$section->addText("Generado el: " . date('d \d\e F \d\e Y, h:i A'), ['size' => 10, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 480]);

$tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80];
$phpWord->addTableStyle('VraTable', $tableStyle);
$headerCellStyle = ['bgColor' => 'F2F2F2', 'valign' => 'center'];
$headerTextStyle = ['bold' => true, 'size' => 9];
$headerParagraphStyle = ['alignment' => Jc::CENTER];
$cellTextStyle = ['size' => 9, 'font' => 'sans-serif'];
$cellParagraphStyle = ['alignment' => Jc::LEFT];

function crearTablaParaNovedad($section, $titulo, $datos, $headers, $styles) {
    if (empty($datos)) return;
    $section->addText($titulo, ['bold' => true, 'size' => 12, 'color' => '333333'], ['spaceBefore' => 360, 'spaceAfter' => 120]);
    $table = $section->addTable('VraTable');
    $table->addRow();
    foreach ($headers as $header) {
        $table->addCell(2000, $styles['headerCellStyle'])->addText($header, $styles['headerTextStyle'], $styles['headerParagraphStyle']);
    }
    foreach ($datos as $solicitud) {
        $table->addRow();
        $oficio_completo = $solicitud['oficio_fac'] . ' (' . $solicitud['fecha_oficio_fac'] . ')';
       // $depto_completo = $solicitud['depto_nom_propio'] . ' / ' . $solicitud['Nombre_fac_minb'];
        $depto_completo = $solicitud['depto_nom_propio'];
        $table->addCell()->addText(htmlspecialchars($oficio_completo), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($depto_completo), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($solicitud['cedula']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($solicitud['nombre']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($solicitud['dedicacion_unificada_inicial']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($solicitud['dedicacion_unificada_final']), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($solicitud['puntos'] ?? ''), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
        $table->addCell()->addText(htmlspecialchars($solicitud['tipo_reemplazo'] ?? ''), $styles['cellTextStyle'], $styles['cellParagraphStyle']);
    }
}

$headers = ['Oficio', 'Departamento', 'Cédula', 'Nombre', 'Vinc. Inicial', 'Vinc. Final', 'Puntos', 'Observación'];
$styles = [
    'headerCellStyle' => $headerCellStyle, 'headerTextStyle' => $headerTextStyle, 'headerParagraphStyle' => $headerParagraphStyle,
    'cellTextStyle' => $cellTextStyle, 'cellParagraphStyle' => $cellParagraphStyle
];

crearTablaParaNovedad($section, 'Modificar', $modificaciones, $headers, $styles);
crearTablaParaNovedad($section, 'Vincular', $vinculaciones, $headers, $styles);
crearTablaParaNovedad($section, 'Liberar', $liberaciones, $headers, $styles);

// --- 8. ENVIAR EL DOCUMENTO AL NAVEGADOR ---
$filename = "tramite_vra_srh_" . date('Ymd_His') . ".docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit;
?>