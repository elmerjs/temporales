<?php
require_once 'vendor/autoload.php';
require_once 'conn.php';
require 'funciones.php'; // Asegúrate de que aquí estén obtenerDecano() y formatearVicerrectorParaOficio()

date_default_timezone_set('America/Bogota');

use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');

// --- 1. PARÁMETROS RECIBIDOS POR URL (MÉTODO GET PARA REIMPRESIÓN) ---
$anio_semestre = $_GET['anio_semestre'] ?? '';
$id_facultad = (int)($_GET['facultad_id'] ?? 0);
$oficio_fac = $_GET['oficio_fac'] ?? '';

if (empty($anio_semestre) || $id_facultad === 0 || empty($oficio_fac)) {
    die("Faltan parámetros en la URL (anio_semestre, facultad_id u oficio_fac) para generar el documento.");
}

// --- 2. OBTENER METADATOS DEL OFICIO DIRECTAMENTE DE LA BASE DE DATOS ---
$fecha_oficio = date('Y-m-d');
$elaborado_por = 'S/N';
$numero_acta = '';
$folios = 'S/N';
$decano_nombre = obtenerDecano($id_facultad) ?? 'Decano/a'; 

$sql_meta = "SELECT fecha_oficio_fac, elaborado_por 
             FROM solicitudes_working_copy 
             WHERE oficio_fac = ? AND facultad_id = ? AND anio_semestre = ? LIMIT 1";
$stmt_meta = $conn->prepare($sql_meta);
if ($stmt_meta) {
    $stmt_meta->bind_param("sis", $oficio_fac, $id_facultad, $anio_semestre);
    $stmt_meta->execute();
    $res_meta = $stmt_meta->get_result();
    if ($row_meta = $res_meta->fetch_assoc()) {
        $fecha_oficio = $row_meta['fecha_oficio_fac'] ?? date('Y-m-d');
        $elaborado_por = $row_meta['elaborado_por'] ?? 'S/N';
        $numero_acta = $row_meta['acta'] ?? '';
    }
    $stmt_meta->close();
}

$numero_oficio = $oficio_fac;

// ===================================================================
// LÓGICA PARA DETECTAR Y PROCESAR "CAMBIO DE VINCULACIÓN"
// ===================================================================
$cambio_vinculacion_data = [];
$ids_cambio_vinculacion = []; 

$sql_cambio_vinculacion = "
    SELECT
        t1.id_solicitud AS id_eliminar, t1.cedula, t1.departamento_id, t1.nombre AS nombre_eliminar,
        t1.tipo_dedicacion AS dedicacion_eliminar, t1.tipo_dedicacion_r AS dedicacion_eliminar_r,
        t2.oficio_con_fecha as oficio_depto, t1.horas AS horas_eliminar, t1.horas_r AS horas_r_eliminar,
        t1.s_observacion AS observacion_eliminar, t2.id_solicitud AS id_adicionar, t2.nombre AS nombre_adicionar,
        t1.tipo_docente as tipo_docente_eliminar, t2.tipo_docente, t2.tipo_dedicacion AS dedicacion_adicionar,
        t2.horas AS horas_adicionar, t2.tipo_dedicacion_r, t2.horas_r, t1.sede as sede_eliminar,
        t2.sede as sede_adicionar, t2.s_observacion AS observacion_adicionar, t2.anexa_hv_docente_nuevo,
        t2.actualiza_hv_antiguo, f.nombre_fac_minb AS nombre_facultad, d.depto_nom_propio AS nombre_departamento
    FROM solicitudes_working_copy t1
    JOIN solicitudes_working_copy t2
        ON t1.cedula = t2.cedula
        AND t1.departamento_id = t2.departamento_id
        AND t1.anio_semestre = t2.anio_semestre
    JOIN facultad f ON t1.facultad_id = f.PK_FAC
    JOIN deparmanentos d ON t1.departamento_id = d.PK_DEPTO
    WHERE t1.novedad = 'eliminar'
      AND t2.novedad = 'adicionar'
      AND t1.anio_semestre = ?
      AND t1.facultad_id = ?
      AND t1.oficio_fac = ?
      AND t2.oficio_fac = ?
    ORDER BY d.depto_nom_propio ASC, t1.nombre ASC
";

$stmt_cambio_vinculacion = $conn->prepare($sql_cambio_vinculacion);
if ($stmt_cambio_vinculacion) {
    $stmt_cambio_vinculacion->bind_param("siss", $anio_semestre, $id_facultad, $oficio_fac, $oficio_fac);
    $stmt_cambio_vinculacion->execute();
    $result_cambio_vinculacion = $stmt_cambio_vinculacion->get_result();
    while ($row = $result_cambio_vinculacion->fetch_assoc()) {
        $cambio_vinculacion_data[] = $row;
        $ids_cambio_vinculacion[] = $row['id_eliminar'];
        $ids_cambio_vinculacion[] = $row['id_adicionar'];
    }
    $stmt_cambio_vinculacion->close();
}

$ids_excluir = array_unique($ids_cambio_vinculacion);

// === CONSULTA SQL PRINCIPAL PARA NOVEDADES REGULARES ===
$sql = "
    SELECT
        sw.id_solicitud, sw.cedula, sw.nombre, sw.novedad, sw.tipo_docente, sw.tipo_dedicacion,
        sw.horas, sw.oficio_con_fecha, sw.tipo_dedicacion_r, sw.horas_r, sw.s_observacion,
        sw.observacion_facultad, sw.costo, sw.anexa_hv_docente_nuevo, sw.actualiza_hv_antiguo,
        f.nombre_fac_minb AS nombre_facultad, d.depto_nom_propio AS nombre_departamento
    FROM solicitudes_working_copy sw
    JOIN facultad f ON sw.facultad_id = f.PK_FAC
    JOIN deparmanentos d ON sw.departamento_id = d.PK_DEPTO
    WHERE sw.anio_semestre = ?
      AND sw.facultad_id = ?
      AND sw.oficio_fac = ?
";

if (!empty($ids_excluir)) {
    $placeholders_excluir = implode(',', array_fill(0, count($ids_excluir), '?'));
    $sql .= " AND sw.id_solicitud NOT IN ($placeholders_excluir)";
}

$sql .= " ORDER BY d.depto_nom_propio ASC, 
          CASE 
            WHEN sw.novedad = 'adicionar' THEN 1 
            WHEN sw.novedad = 'Eliminar' THEN 2 
            WHEN sw.novedad = 'eliminar' THEN 2 
            ELSE 3 
          END ASC, 
          sw.nombre ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $params = [$anio_semestre, $id_facultad, $oficio_fac];
    $types = 'sis';
    
    if (!empty($ids_excluir)) {
        $types .= str_repeat('i', count($ids_excluir));
        $params = array_merge($params, $ids_excluir);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

if (empty($solicitudes) && empty($cambio_vinculacion_data)) {
    die("No se encontraron solicitudes registradas para el Oficio $oficio_fac.");
}

$nombre_facultad_principal = ($solicitudes[0]['nombre_facultad'] ?? $cambio_vinculacion_data[0]['nombre_facultad']) ?? 'Facultad Desconocida';

$grouped_solicitudes = []; 
foreach ($solicitudes as $sol) {
    $grouped_solicitudes[$sol['nombre_departamento']][$sol['novedad']][] = $sol;
}

$grouped_cambio_vinculacion = []; 
foreach ($cambio_vinculacion_data as $cambio) {
    $grouped_cambio_vinculacion[$cambio['nombre_departamento']][] = $cambio;
}

// ==================================================
// CONFIGURACIÓN DE WORD (IDÉNTICO AL ORIGINAL)
// ==================================================
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

$phpWord->addFontStyle('boldSize12', ['bold' => true, 'size' => 12]);
$phpWord->addFontStyle('normalSize11', ['size' => 11]);
$phpWord->addFontStyle('normalSize9', ['size' => 9]);
$phpWord->addParagraphStyle('center', ['alignment' => Jc::CENTER]);
$phpWord->addParagraphStyle('justify', ['alignment' => Jc::BOTH]);
$phpWord->addParagraphStyle('left', ['alignment' => Jc::LEFT]);

$styleTable = array(
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 60,
    'alignment' => Jc::CENTER
);
$phpWord->addTableStyle('ColspanRowspan', $styleTable);

$cellTextStyle = ['size' => 8, 'bold' => false];
$cellTextStyleb = ['size' => 9, 'bold' => true];
$paragraphStyle = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
$headerCellStyle = ['bgColor' => 'F2F2F2', 'borderSize' => 6, 'borderColor' => '999999', 'valign' => 'center'];

$fontStyleb = ['bold' => true, 'size' => 10];
$observationTextStyle = ['size' => 11];
$paragraphStyleLeft = ['alignment' => Jc::LEFT];

$facultades = [
    1 => ['encab' => 'img/encabezado_decanatura_artes.png', 'pie' => 'img/pieartes.png'],
    2 => ['encab' => 'img/encabezado_decanatura_agrarias.png', 'pie' => 'img/pieagro.png'],
    3 => ['encab' => 'img/encabezado_decanatura_salud.png', 'pie' => 'img/piesalud.png'],
    4 => ['encab' => 'img/encabezado_decanatura_fccea.png', 'pie' => 'img/piecontables.png'],
    5 => ['encab' => 'img/encabezado_decanatura_humanas.png', 'pie' => 'img/piehumanas.png'],
    6 => ['encab' => 'img/encabezado_decanatura_facnedx.png', 'pie' => 'img/piefacned.png'],
    7 => ['encab' => 'img/encabezado_decanatura_derecho.png', 'pie' => 'img/piederecho.png'],
    8 => ['encab' => 'img/encabezado_decanatura_civil.png', 'pie' => 'img/piecivil.png'],
    9 => ['encab' => 'img/encabezado_decanatura_fiet.png', 'pie' => 'img/piefiet.png']
];

if (isset($facultades[$id_facultad])) {
    $imgencabezado = $facultades[$id_facultad]['encab'];
    $imgpie = $facultades[$id_facultad]['pie'];
} else {
    $imgencabezado = 'img/encabezado_generico.png';
    $imgpie = 'img/piegenerico.png';
}

$section = $phpWord->addSection([
    'marginTop'    => 1200,
    'marginBottom' => 1000,
    'marginLeft'   => 1700,
    'marginRight'  => 1700
]);

$header = $section->addHeader();
$header->addImage($imgencabezado, ['width' => 200, 'alignment' => Jc::LEFT]);

$footer = $section->addFooter();
$footer->addImage($imgpie, ['width' => 450, 'alignment' => Jc::CENTER]);

$section->addTextBreak(0);
$paragraphStylexz = array('lineHeight' => 0.8, 'spaceAfter' => 0, 'spaceBefore' => 0);
$styleNoSpace = ['align' => 'left', 'spaceAfter' => 0];

$fontStylecuerpo = array('name' => 'Arial', 'size' => 11);
$fecha_actual = strftime('%d de %B de %Y', strtotime($fecha_oficio));

$section->addText($numero_oficio, ['size' => 11], $styleNoSpace);
$section->addText('Popayán, ' . $fecha_actual, $fontStylecuerpo, $paragraphStylexz);

$section->addTextBreak(1);

$vicerrector = formatearVicerrectorParaOficio();

$section->addText($vicerrector['titulo'], ['size' => 11], $styleNoSpace);
$section->addText($vicerrector['nombre'], ['size' => 11], $styleNoSpace);
$section->addText($vicerrector['cargo_completo'], ['size' => 11], $styleNoSpace);
$section->addText($vicerrector['institucion'], ['size' => 11], $styleNoSpace);
$section->addTextBreak(1);

$section->addText('Asunto: Novedades de Vinculación Profesores temporales ' . $anio_semestre . '.', [ 'size' => 11], 'left');
$section->addTextBreak(1);

$section->addText('Cordial saludo,', 'normalSize11', 'left');
$section->addTextBreak(0);

$section->addText('Para su conocimiento y trámite pertinente remito solicitud de novedades de la Facultad de ' . $nombre_facultad_principal . '; periodo: ' . $anio_semestre . ',  para los siguientes profesores:', 'normalSize11', 'justify');
$section->addTextBreak(1);

$todos_los_departamentos = array_unique(array_merge(array_keys($grouped_solicitudes), array_keys($grouped_cambio_vinculacion)));
sort($todos_los_departamentos);

foreach ($todos_los_departamentos as $departamento_nombre) {
    $section->addText('Departamento de ' . htmlspecialchars($departamento_nombre), ['bold' => true, 'size' => 12], ['spaceAfter' => 120, 'keepNext' => true]);

    if (isset($grouped_cambio_vinculacion[$departamento_nombre])) {
        $section->addText('Novedad: Modificación - Cambio de Vinculación', ['bold' => true, 'size' => 11]);
        $section->addTextBreak(0);

        foreach ($grouped_cambio_vinculacion[$departamento_nombre] as $cambio) {
            if (!empty($cambio['observacion_adicionar'])) {
                $section->addText(htmlspecialchars($cambio['observacion_adicionar']), $observationTextStyle, $paragraphStyleLeft);
            }

            $tipo_docente_eliminar = $cambio['tipo_docente_eliminar'];
            $salida_part = ''; 
            $sede_eliminar = htmlspecialchars($cambio['sede_eliminar'] ?: ''); 
            
            if ($tipo_docente_eliminar == "Ocasional") {
                $dedicacion_eliminar_val = !empty($cambio['dedicacion_eliminar']) ? $cambio['dedicacion_eliminar'] : $cambio['dedicacion_eliminar_r'];
                $dedicacion_eliminar_str = '';
                if (!empty($dedicacion_eliminar_val)) {
                    $dedicacion_eliminar_str = str_replace(['MT', 'TC'], ['Medio Tiempo', 'Tiempo Completo'], htmlspecialchars($dedicacion_eliminar_val));
                    if (!empty($sede_eliminar)) { $dedicacion_eliminar_str .= " - Sede {$sede_eliminar}"; }
                }
                $salida_part = $dedicacion_eliminar_str;
            } else if ($tipo_docente_eliminar == "Catedra") {
                $horas_eliminar_p_val = null; 
                $horas_eliminar_r_val = null;

                if (isset($cambio['horas_eliminar']) && is_numeric($cambio['horas_eliminar'])) {
                    $temp_p = floatval($cambio['horas_eliminar']);
                    if ($temp_p > 0) { $horas_eliminar_p_val = htmlspecialchars((string)$temp_p); }
                }

                if (isset($cambio['horas_r_eliminar']) && is_numeric($cambio['horas_r_eliminar'])) {
                    $temp_r = floatval($cambio['horas_r_eliminar']);
                    if ($temp_r > 0) { $horas_eliminar_r_val = htmlspecialchars((string)$temp_r); }
                }
                
                $horas_eliminar_str = '';
                if ($horas_eliminar_p_val !== null && $horas_eliminar_r_val !== null) { $horas_eliminar_str = "{$horas_eliminar_p_val} (P) / {$horas_eliminar_r_val} (R) horas"; }
                elseif ($horas_eliminar_p_val !== null) { $horas_eliminar_str = "{$horas_eliminar_p_val} horas"; }
                elseif ($horas_eliminar_r_val !== null) { $horas_eliminar_str = "{$horas_eliminar_r_val} horas"; }
                
                $salida_part = $horas_eliminar_str;
                if (!empty($sede_eliminar) && !empty($salida_part)) { $salida_part .= " - Sede {$sede_eliminar}"; }
            } else {
                $salida_part = '';
            }

            $sede_adicionar = htmlspecialchars($cambio['sede_adicionar'] ?: ''); 
            $tipo_docente_str = htmlspecialchars($cambio['tipo_docente'] ?: '');
            $nueva_vinculacion_dedicacion_horas = '';

            if ($tipo_docente_str === "Ocasional") {
                $dedicacion_val_adicionar = '';
                if (!empty($cambio['dedicacion_adicionar'])) { $dedicacion_val_adicionar = $cambio['dedicacion_adicionar']; }
                elseif (!empty($cambio['tipo_dedicacion_r'])) { $dedicacion_val_adicionar = $cambio['tipo_dedicacion_r']; }

                if (!empty($dedicacion_val_adicionar)) {
                    $nueva_vinculacion_dedicacion_horas = str_replace(['MT', 'TC'], ['Medio Tiempo', 'Tiempo Completo'], htmlspecialchars($dedicacion_val_adicionar));
                    if (!empty($sede_adicionar)) { $nueva_vinculacion_dedicacion_horas .= " - Sede {$sede_adicionar}"; }
                }
            } elseif ($tipo_docente_str === "Catedra") {
                $horas_val_adicionar_numeric = null; 
                if (isset($cambio['horas_adicionar']) && is_numeric($cambio['horas_adicionar'])) {
                    $temp_horas_adicionar = floatval($cambio['horas_adicionar']);
                    if ($temp_horas_adicionar > 0) { $horas_val_adicionar_numeric = $temp_horas_adicionar; }
                }
                if ($horas_val_adicionar_numeric === null && isset($cambio['horas_r']) && is_numeric($cambio['horas_r'])) {
                    $temp_horas_r = floatval($cambio['horas_r']);
                    if ($temp_horas_r > 0) { $horas_val_adicionar_numeric = $temp_horas_r; }
                }

                if ($horas_val_adicionar_numeric !== null) {
                    $nueva_vinculacion_dedicacion_horas = htmlspecialchars((string)$horas_val_adicionar_numeric) . ' horas';
                    if (!empty($sede_adicionar)) { $nueva_vinculacion_dedicacion_horas .= " - Sede {$sede_adicionar}"; }
                }
            }

            $nombre_profesor_final = htmlspecialchars($cambio['nombre_adicionar'] ?: $cambio['nombre_eliminar']);
            $texto_profesor_arriba = "Profesor: {$nombre_profesor_final}";

            $texto_cambio_dentro = "Pasa de {$tipo_docente_eliminar}";
            if (!empty($salida_part)) { $texto_cambio_dentro .= " - {$salida_part}"; }
            $texto_cambio_dentro .= " a {$tipo_docente_str}";
            if (!empty($nueva_vinculacion_dedicacion_horas)) { $texto_cambio_dentro .= " {$nueva_vinculacion_dedicacion_horas}"; }
            $texto_cambio_dentro = "($texto_cambio_dentro)";

            $section->addTextBreak(0);
            $section->addText($texto_profesor_arriba, $observationTextStyle, $paragraphStyleLeft);

            $table_cambio = $section->addTable('ColspanRowspan');
            $table_cambio->setWidth(100 * 50, TblWidth::PERCENT);

            $row = $table_cambio->addRow();
            $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyleb, $paragraphStyle);
            $row->addCell(3200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyleb, $paragraphStyle);
            $row->addCell(1000, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyleb, $paragraphStyle);
            $row->addCell(1000, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyleb, $paragraphStyle);
            $row->addCell(2200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Observación', $cellTextStyleb, $paragraphStyle);

            $row = $table_cambio->addRow();
            $row->addCell(1200, array('vMerge' => 'continue', 'borderSize' => 1)); 
            $row->addCell(3200, array('vMerge' => 'continue', 'borderSize' => 1)); 
            $row->addCell(500, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
            $row->addCell(500, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);
            $row->addCell(500, $headerCellStyle)->addText('Nuev', $cellTextStyleb, $paragraphStyle);
            $row->addCell(500, $headerCellStyle)->addText('Antg', $cellTextStyleb, $paragraphStyle);
            $row->addCell(2200, array('vMerge' => 'continue', 'borderSize' => 1)); 

            $table_cambio->addRow();
            $table_cambio->addCell(1200, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);
            $table_cambio->addCell(3200, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['nombre_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
            
            if ($cambio['tipo_docente'] == "Ocasional") {
                $dedicacion_popayan = strtoupper(trim($cambio['dedicacion_adicionar'] ?: ''));
                $display_popayan = ''; 
                if ($dedicacion_popayan === 'MT') { $display_popayan = 'OMT'; }
                elseif ($dedicacion_popayan === 'TC') { $display_popayan = 'OTC'; }
                elseif (!empty($dedicacion_popayan)) { $display_popayan = 'O' . htmlspecialchars($dedicacion_popayan); }
                
                $dedicacion_regional = strtoupper(trim($cambio['tipo_dedicacion_r'] ?: ''));
                $display_regional = ''; 
                if ($dedicacion_regional === 'MT') { $display_regional = 'OMT'; }
                elseif ($dedicacion_regional === 'TC') { $display_regional = 'OTC'; }
                elseif (!empty($dedicacion_regional)) { $display_regional = 'O' . htmlspecialchars($dedicacion_regional); }

                $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])->addText($display_popayan, $cellTextStyle, $paragraphStyle);
                $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])->addText($display_regional, $cellTextStyle, $paragraphStyle);
            } elseif ($cambio['tipo_docente'] == "Catedra") {
                $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['horas_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
                $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['horas_r'] ?: ''), $cellTextStyle, $paragraphStyle);
            } else {
                $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])->addText('', $cellTextStyle, $paragraphStyle);
                $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])->addText('', $cellTextStyle, $paragraphStyle);
            }

            $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])
                ->addText(mb_strtoupper(htmlspecialchars($cambio['anexa_hv_docente_nuevo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);
            $table_cambio->addCell(500, ['borderSize' => 1, 'valign' => 'center'])
                ->addText(mb_strtoupper(htmlspecialchars($cambio['actualiza_hv_antiguo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

            $observacion_existente = htmlspecialchars($cambio['observacion_adicionar'] ?: '');
            $cell = $table_cambio->addCell(2200, ['borderSize' => 1, 'valign' => 'center']);
            $textrun = $cell->addTextRun($paragraphStyle); 
            if (!empty($observacion_existente)) { $textrun->addText($observacion_existente . ' ', $cellTextStyle); }
            $textrun->addText($texto_cambio_dentro, ['italic' => true, 'size' => 8]);
            
            $section->addTextBreak(1); 
        }
        $section->addTextBreak(1);
    }

    if (isset($grouped_solicitudes[$departamento_nombre])) {
        $solicitudes_novedades = $grouped_solicitudes[$departamento_nombre];

        if (isset($solicitudes_novedades['Modificar'])) {
            $modificar = ['Modificar' => $solicitudes_novedades['Modificar']];
            unset($solicitudes_novedades['Modificar']);
            $solicitudes_novedades = $modificar + $solicitudes_novedades;
        }

        foreach ($solicitudes_novedades as $novedad_tipo => $solicitudes_por_novedad) {
            $novedad_key = strtolower($novedad_tipo); 
            
            // --- ESTE ES EL CAMBIO CLAVE PARA QUE COINCIDA CON TU ORIGINAL ---
            // --- TRADUCCIÓN DE TÉRMINOS PARA EL DOCUMENTO ---
            switch ($novedad_key) {
                case 'modificar':
                    $novedad_mostrar = 'Modificación - Cambio de Dedicación';
                    break;
                case 'adicionar':
                    $novedad_mostrar = 'Vincular';
                    break;
                case 'eliminar':
                    $novedad_mostrar = 'Desvincular';
                    break;
                default:
                    $novedad_mostrar = ucfirst($novedad_tipo);
                    break;
            }

            $section->addText('Novedad: ' . htmlspecialchars($novedad_mostrar), $fontStyleb, $paragraphStyleLeft);
            $section->addTextBreak(0);

            $table = $section->addTable('ColspanRowspan');
            $table->setWidth(100 * 50, TblWidth::PERCENT);

            $row = $table->addRow();
            $textrun = $row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
            $textrun->addText('N', $cellTextStyle);
            $textrun->addText('o', array_merge($cellTextStyle, array('superScript' => true)));
            $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);
            $row->addCell(3200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
            $row->addCell(1400, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyle, $paragraphStyle);
            $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyle, $paragraphStyle);
            $row->addCell(2200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Observación', $cellTextStyle, $paragraphStyle);

            $row = $table->addRow();
            $row->addCell(400, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); 
            $row->addCell(1200, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); 
            $row->addCell(3200, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); 
            $row->addCell(700, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
            $row->addCell(700, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);
            $row->addCell(350, $headerCellStyle)->addText('Nuev', $cellTextStyle, $paragraphStyle);
            $row->addCell(350, $headerCellStyle)->addText('Antg', $cellTextStyle, $paragraphStyle);
            $row->addCell(2200, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); 

            $cont = 0; 
            foreach ($solicitudes_por_novedad as $sol) {
                $cont++;
                $table->addRow();
                $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
                $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);
                
                $full_nombre = htmlspecialchars($sol['nombre'] ?: '');
                $table->addCell(3200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($full_nombre, $cellTextStyle, $paragraphStyle);

               if ($sol['tipo_docente'] == "Ocasional") {
                    $dedicacion_popayan = strtoupper(trim($sol['tipo_dedicacion'] ?: ''));
                    $display_popayan = ''; 
                    if ($dedicacion_popayan === 'MT') { $display_popayan = 'OMT'; }
                    elseif ($dedicacion_popayan === 'TC') { $display_popayan = 'OTC'; }
                    elseif (!empty($dedicacion_popayan)) { $display_popayan = 'O' . htmlspecialchars($dedicacion_popayan); }

                    $dedicacion_regional = strtoupper(trim($sol['tipo_dedicacion_r'] ?: ''));
                    $display_regional = ''; 
                    if ($dedicacion_regional === 'MT') { $display_regional = 'OMT'; }
                    elseif ($dedicacion_regional === 'TC') { $display_regional = 'OTC'; }
                    elseif (!empty($dedicacion_regional)) { $display_regional = 'O' . htmlspecialchars($dedicacion_regional); }

                    $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($display_popayan, $cellTextStyle, $paragraphStyle);
                    $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($display_regional, $cellTextStyle, $paragraphStyle);
                } elseif ($sol['tipo_docente'] == "Catedra") {
                    $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['horas'] ?: ''), $cellTextStyle, $paragraphStyle);
                    $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['horas_r'] ?: ''), $cellTextStyle, $paragraphStyle);
                } else {
                    $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
                    $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
                }

                $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
                    ->addText(mb_strtoupper(htmlspecialchars($sol['anexa_hv_docente_nuevo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);
                $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
                    ->addText(mb_strtoupper(htmlspecialchars($sol['actualiza_hv_antiguo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

                $observacion = htmlspecialchars($sol['s_observacion'] ?: '');
                $oficio = htmlspecialchars($sol['oficio_con_fecha'] ?: ''); 

                $texto_final = $observacion;
                if (!empty($oficio)) {
                    $texto_final .= " (oficio depto: {$oficio})";
                }

                $table->addCell(2200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
                      ->addText($texto_final, $cellTextStyle, $paragraphStyle);
            }
            $section->addTextBreak(1); 
        }
    }
}

$section->addTextBreak(1);
$fontStyleSmall = array('name' => 'Arial', 'size' => 7, 'italic' => true);
$paragraphStyleSmall = array('spaceBefore' => 0, 'spaceAfter' => 0);

$section->addText(
    'Dedic/hr=Dedicación (Ocasional) u Horas(Cátedra), nuev=Anexa Hoja de vida, Antg = Actualiza Hoja de vida Antiguo, OTC = Ocasional Tiempo Completo, OMT = Ocasional Medio Tiempo', 
    $fontStyleSmall, 
    $paragraphStyleSmall
);

$section->addText('Universitariamente,', 'normalSize11', 'left');
$section->addTextBreak(2);

$section->addText($decano_nombre, ['bold' => true, 'size' => 11], 'left');
$section->addText('Decano/a de la Facultad de ' . $nombre_facultad_principal, 'normalSize11', 'left');
$section->addTextBreak(1);
$section->addText('Elaborado por: ' . $elaborado_por, 'normalSize9', 'left');
$section->addText('Folios: ' . $folios, 'normalSize9', 'left');

$fileName = 'Reimpresion_Oficio_' . str_replace('/', '-', $numero_oficio) . '_' . date('Ymd_His') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');

$conn->close();
?>