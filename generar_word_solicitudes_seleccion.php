<?php
require_once 'vendor/autoload.php';
require_once 'conn.php';
require 'funciones.php';

date_default_timezone_set('America/Bogota');

use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es');

// Parámetros recibidos
$anio_semestre = $_POST['anio_semestre'] ?? '';
$id_facultad = (int)($_POST['id_facultad'] ?? 0);
$numero_oficio = $_POST['oficio'] ?? 'S/N';
$fecha_oficio = $_POST['fecha_oficio'] ?? date('Y-m-d');
$decano_nombre = $_POST['decano'] ?? 'Decano/a';
$elaborado_por = $_POST['elaborado_por'] ?? 'Responsable Facultad';
$folios = (int)($_POST['folios'] ?? 0);
$numero_acta = $_POST['numero_acta'] ?? '';

// === NUEVA LÓGICA PARA RECIBIR Y PROCESAR LOS IDs SELECCIONADOS ===
$selected_ids_str = $_POST['selected_ids_for_word'] ?? '';
$selected_ids_array = [];
if (!empty($selected_ids_str)) {
    // Convertir la cadena de IDs de nuevo a un array de enteros
    $selected_ids_array = array_map('intval', explode(',', $selected_ids_str));
    // Asegurarse de que el array no esté vacío después de la conversión
    if (empty($selected_ids_array)) {
        die("No se proporcionaron IDs de solicitud válidos.");
    }
} else {
    die("Debe proporcionar los IDs de solicitud para generar el oficio.");
}
// ===================================================================

if (empty($anio_semestre) || $id_facultad === 0) {
    die("Debe proporcionar el año-semestre y la facultad para generar el oficio.");
}

// Mapeo de imágenes por facultad
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

// Determinar imágenes según facultad
if (isset($facultades[$id_facultad])) {
    $imgencabezado = $facultades[$id_facultad]['encab'];
    $imgpie = $facultades[$id_facultad]['pie'];
} else {
    $imgencabezado = 'img/encabezado_generico.png';
    $imgpie = 'img/piegenerico.png';
}

// Función auxiliar para bind_param, necesaria cuando se usa call_user_func_array
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) //PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}

// ===================================================================
// LÓGICA PARA DETECTAR Y PROCESAR "CAMBIO DE VINCULACIÓN"
// ===================================================================
$cambio_vinculacion_data = [];
$cedulas_cambio_vinculacion = [];
$ids_cambio_vinculacion = []; // IDs de las solicitudes que son "Cambio de Vinculación"

// Primero, identificar los pares de "eliminar" y "adicionar" dentro de los IDs seleccionados
$placeholders_ids_seleccionados = implode(',', array_fill(0, count($selected_ids_array), '?'));

$sql_cambio_vinculacion = "
    SELECT
        t1.id_solicitud AS id_eliminar,
        t1.cedula,
        t1.departamento_id,
        t1.nombre AS nombre_eliminar,
        t1.tipo_dedicacion AS dedicacion_eliminar,
        t1.tipo_dedicacion_r AS dedicacion_eliminar_r,

        t1.horas AS horas_eliminar,
        t1.horas_r AS horas_r_eliminar,

        t1.s_observacion AS observacion_eliminar,
        t2.id_solicitud AS id_adicionar,
        t2.nombre AS nombre_adicionar,
        t1.tipo_docente as tipo_docente_eliminar,
        t2.tipo_docente,
        t2.tipo_dedicacion AS dedicacion_adicionar,
        t2.horas AS horas_adicionar,
        t2.tipo_dedicacion_r,
        t2.horas_r,
        t1.sede as sede_eliminar,
        t2.sede as sede_adicionar,
        t2.s_observacion AS observacion_adicionar,
        t2.anexa_hv_docente_nuevo,
        t2.actualiza_hv_antiguo,
        f.nombre_fac_minb AS nombre_facultad,
        d.depto_nom_propio AS nombre_departamento
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
      AND t1.estado_facultad = 'APROBADO'
      AND t1.estado_vra = 'PENDIENTE'
      AND t2.estado_facultad = 'APROBADO'
      AND t2.estado_vra = 'PENDIENTE'
      AND t1.id_solicitud IN ($placeholders_ids_seleccionados)
      AND t2.id_solicitud IN ($placeholders_ids_seleccionados)
    ORDER BY d.depto_nom_propio ASC, t1.nombre ASC
";

$stmt_cambio_vinculacion = $conn->prepare($sql_cambio_vinculacion);
if ($stmt_cambio_vinculacion) {
    $types_cambio = 'si' . str_repeat('i', count($selected_ids_array) * 2); // 'si' para anio_semestre, id_facultad, luego 'i' por cada ID en los dos IN clauses
    $params_cambio = array_merge([$types_cambio, $anio_semestre, $id_facultad], $selected_ids_array, $selected_ids_array);
    call_user_func_array([$stmt_cambio_vinculacion, 'bind_param'], refValues($params_cambio));
    $stmt_cambio_vinculacion->execute();
    $result_cambio_vinculacion = $stmt_cambio_vinculacion->get_result();
    while ($row = $result_cambio_vinculacion->fetch_assoc()) {
        $cambio_vinculacion_data[] = $row;
        $cedulas_cambio_vinculacion[] = $row['cedula'];
        $ids_cambio_vinculacion[] = $row['id_eliminar'];
        $ids_cambio_vinculacion[] = $row['id_adicionar'];
    }
    $stmt_cambio_vinculacion->close();
} else {
    die("Error al preparar la consulta de cambio de vinculación: " . $conn->error);
}

// Convertir las cédulas de cambio de vinculación y los IDs a un string para la cláusula IN
// Solo únicos para evitar problemas con la cláusula IN
$cedulas_excluir = array_unique($cedulas_cambio_vinculacion);
$cedulas_excluir_str = !empty($cedulas_excluir) ? "'" . implode("','", $cedulas_excluir) . "'" : "'_NONE_'"; // Usa un valor que no exista si el array está vacío

$ids_excluir = array_unique($ids_cambio_vinculacion);
$ids_excluir_str = !empty($ids_excluir) ? implode(',', $ids_excluir) : '0'; // Usa 0 si el array está vacío


// === MODIFICACIÓN DE LA CONSULTA SQL PRINCIPAL PARA EXCLUIR CAMBIOS DE VINCULACIÓN ===
$sql = "
    SELECT
        sw.id_solicitud,
        sw.cedula,
        sw.nombre,
        sw.novedad,
        sw.tipo_docente,
        sw.tipo_dedicacion,
        sw.horas,
        sw.tipo_dedicacion_r,
        sw.horas_r,
        sw.s_observacion,
        sw.observacion_facultad,
        sw.costo,
        sw.anexa_hv_docente_nuevo,
        sw.actualiza_hv_antiguo,
        f.nombre_fac_minb AS nombre_facultad,
        d.depto_nom_propio AS nombre_departamento
    FROM solicitudes_working_copy sw
    JOIN facultad f ON sw.facultad_id = f.PK_FAC
    JOIN deparmanentos d ON sw.departamento_id = d.PK_DEPTO
    WHERE sw.anio_semestre = ?
      AND sw.facultad_id = ?
      AND sw.estado_facultad = 'APROBADO'
      AND sw.estado_vra = 'PENDIENTE'
      AND sw.id_solicitud IN ($placeholders_ids_seleccionados)
      AND sw.id_solicitud NOT IN ($ids_excluir_str) -- ¡Excluir IDs de Cambio de Vinculación!
    ORDER BY d.depto_nom_propio ASC, sw.novedad ASC, sw.nombre ASC
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    // Reconstruir los parámetros para la consulta principal
    $types = 'si' . str_repeat('i', count($selected_ids_array));
    $params = array_merge([$types, $anio_semestre, $id_facultad], $selected_ids_array);

    call_user_func_array([$stmt, 'bind_param'], refValues($params));

    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error al preparar la consulta de solicitudes: " . $conn->error);
}

if (empty($solicitudes) && empty($cambio_vinculacion_data)) {
    die("No se encontraron solicitudes APROBADAS por Facultad y PENDIENTES por VRA para el periodo, facultad y IDs seleccionados.");
}

$nombre_facultad_principal = ($solicitudes[0]['nombre_facultad'] ?? $cambio_vinculacion_data[0]['nombre_facultad']) ?? 'Facultad Desconocida';
$departamentos_nombres = [];
$sql_departamentos = "SELECT depto_nom_propio FROM deparmanentos WHERE PK_DEPTO IN (
    SELECT DISTINCT departamento_id 
    FROM solicitudes_working_copy 
    WHERE anio_semestre = ? AND facultad_id = ? AND estado_facultad = 'APROBADO' AND estado_vra = 'PENDIENTE'
)"; // Filtramos solo los departamentos con solicitudes relevantes

$stmt_departamentos = $conn->prepare($sql_departamentos);
if ($stmt_departamentos) {
    $stmt_departamentos->bind_param('si', $anio_semestre, $id_facultad);
    $stmt_departamentos->execute();
    $result_departamentos = $stmt_departamentos->get_result();
    while ($row = $result_departamentos->fetch_assoc()) {
        $departamentos_nombres[] = htmlspecialchars($row['depto_nom_propio']);
    }
    $stmt_departamentos->close();
} else {
    die("Error al preparar la consulta de departamentos: " . $conn->error);
}

$departamentos_frase = '';
if (!empty($departamentos_nombres)) {
    // Eliminar duplicados y reindexar (si es necesario)
    $departamentos_nombres = array_unique($departamentos_nombres);
    $num_deptos = count($departamentos_nombres);

    if ($num_deptos === 1) {
        $departamentos_frase = $departamentos_nombres[0];
    } elseif ($num_deptos > 1) {
        $ultimo_depto = array_pop($departamentos_nombres);
        $departamentos_frase = implode(', ', $departamentos_nombres) . ' y ' . $ultimo_depto;
    }
}
// Agrupar solicitudes por departamento y luego por novedad, recolectando observaciones
$grouped_solicitudes = [];
$grouped_observations = [];
foreach ($solicitudes as $sol) {
    $departamento = $sol['nombre_departamento'];
    $novedad_tipo = $sol['novedad'];

    if (!isset($grouped_solicitudes[$departamento])) {
        $grouped_solicitudes[$departamento] = [];
        $grouped_observations[$departamento] = [];
    }
    if (!isset($grouped_solicitudes[$departamento][$novedad_tipo])) {
        $grouped_solicitudes[$departamento][$novedad_tipo] = [];
        $grouped_observations[$departamento][$novedad_tipo] = [];
    }
    $grouped_solicitudes[$departamento][$novedad_tipo][] = $sol;

    if (!empty($sol['s_observacion']) && !in_array($sol['s_observacion'], $grouped_observations[$departamento][$novedad_tipo])) {
        $grouped_observations[$departamento][$novedad_tipo][] = $sol['s_observacion'];
    }
}

// Generar el documento de Word usando PHPWord
$phpWord = new \PhpOffice\PhpWord\PhpWord();
// Configurar idioma español (Colombia)
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Estilos
$phpWord->addFontStyle('boldSize12', ['bold' => true, 'size' => 12]);
$phpWord->addFontStyle('normalSize11', ['size' => 11]);
$phpWord->addFontStyle('normalSize9', ['size' => 9]);
$phpWord->addParagraphStyle('center', ['alignment' => Jc::CENTER]);
$phpWord->addParagraphStyle('justify', ['alignment' => Jc::BOTH]);
$phpWord->addParagraphStyle('left', ['alignment' => Jc::LEFT]);

// Estilos para tabla
$styleTable = array(
    'borderSize' => 6,
    'borderColor' => '999999',
    'cellMargin' => 60,
    'alignment' => Jc::CENTER
);
$phpWord->addTableStyle('ColspanRowspan', $styleTable);

$cellTextStyle = ['size' => 9, 'bold' => false];
$cellTextStyleb = ['size' => 9, 'bold' => true];
$paragraphStyle = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];
$headerCellStyle = [
    'bgColor' => 'F2F2F2',
    'borderSize' => 6,
    'borderColor' => '999999',
    'valign' => 'center'
];

$fontStyleb = ['bold' => true, 'size' => 10];
$observationTextStyle = ['size' => 11];
$paragraphStyleLeft = ['alignment' => Jc::LEFT];

// ==================================================
// SECCIÓN CON MÁRGENES PARA ENCABEZADO/PIE
// ==================================================
$section = $phpWord->addSection([
    'marginTop'    => 1200,  // Espacio para encabezado (1200 twips = ~2.12 cm)
    'marginBottom' => 1000,  // Espacio para pie de página
    'marginLeft'   => 1000,
    'marginRight'  => 1000
]);

// ==================================================
// ENCABEZADO CON IMAGEN
// ==================================================
$header = $section->addHeader();
$header->addImage($imgencabezado, [
    'width'       => 200,
    'alignment'   => Jc::LEFT
]);

// ==================================================
// PIE DE PÁGINA CON IMAGEN
// ==================================================
$footer = $section->addFooter();
$footer->addImage($imgpie, [
    'width'       => 450,
    'alignment'   => Jc::CENTER
]);

// ==================================================
// CONTENIDO DEL DOCUMENTO
// ==================================================
$section->addTextBreak(0);  // Espacio después del encabezado
$paragraphStylexz = array('lineHeight' => 0.8, 'spaceAfter' => 0, 'spaceBefore' => 0);
$styleNoSpace = ['align' => 'left', 'spaceAfter' => 0];

$fontStylecuerpo = array('name' => 'Arial', 'size' => 11);
$fecha_actual = strftime('%d de %B de %Y', strtotime($fecha_oficio));

$section->addText($numero_oficio, ['size' => 11], $styleNoSpace);
$section->addText('Popayán, ' . $fecha_actual,$fontStylecuerpo,$paragraphStylexz);
if (!empty($numero_acta)) {
    $section->addText('Número de Acta: ' . $numero_acta, 'normalSize11', 'center');
}
$section->addTextBreak(1);

// Destinatario (Vicerrectoría Académica)
// Obtener datos del vicerrector
$vicerrector = formatearVicerrectorParaOficio();

// Destinatario (Vicerrectoría Académica)
$section->addText($vicerrector['titulo'], ['size' => 11], $styleNoSpace);
$section->addText($vicerrector['nombre'], ['size' => 11], $styleNoSpace);
$section->addText($vicerrector['cargo_completo'], ['size' => 11], $styleNoSpace);
$section->addText($vicerrector['institucion'], ['size' => 11], $styleNoSpace);
$section->addTextBreak(1);
/*
$section->addText('Doctora', ['size' => 11], $styleNoSpace);
$section->addText('AIDA PATRICIA GONZALEZ NIEVA', ['size' => 11], $styleNoSpace);
$section->addText('Vicerrectora Académica', ['size' => 11], $styleNoSpace);
$section->addText('Universidad del Cauca', ['size' => 11], $styleNoSpace);
$section->addTextBreak(1);*/

$section->addText(
    'Asunto: Novedades de Vinculación Profesores temporales ' . $anio_semestre . '.',
    [ 'size' => 11], 'left'
);
$section->addTextBreak(1);

$section->addText('Cordial saludo,', 'normalSize11', 'left');
$section->addTextBreak(0);

$section->addText(
    'Para su conocimiento y trámite pertinente remito solicitud de novedades de la Facultad de ' . $nombre_facultad_principal . ' departamento: '.$departamentos_frase . '; periodo: ' . $anio_semestre . ',  para los siguientes profesores:',
    'normalSize11', 'justify'
);
$section->addTextBreak(1);

// ==================================================
// SECCIÓN: Novedad Cambio de Vinculación
// ==================================================
if (!empty($cambio_vinculacion_data)) {
    $section->addText('Novedad: Cambio de Vinculación', ['bold' => true, 'size' => 11]);
    $section->addTextBreak(0);

 
foreach ($cambio_vinculacion_data as $cambio) {
    // 1. Añadir la observación general/contexto al inicio (si existe)
    if (!empty($cambio['observacion_adicionar'])) {
        $section->addText(htmlspecialchars($cambio['observacion_adicionar']), $observationTextStyle, $paragraphStyleLeft);
    }

    // 2. Construir la frase narrativa de transición del profesor
    $tipo_docente_eliminar = $cambio['tipo_docente_eliminar'];
    $salida_part = ''; // Inicializar para la parte de la salida
    $sede_eliminar = htmlspecialchars($cambio['sede_eliminar'] ?: ''); // Obtener el valor de la sede a eliminar aquí
    
    // Determinar los valores para la vinculación que se elimina
    if ($tipo_docente_eliminar == "Ocasional") {
        // Para Ocasional, usar dedicación (MT o TC) - priorizar el campo principal, sino el _r
        $dedicacion_eliminar_val = !empty($cambio['dedicacion_eliminar']) ? $cambio['dedicacion_eliminar'] : $cambio['dedicacion_eliminar_r'];
        $dedicacion_eliminar_str = '';
        
        if (!empty($dedicacion_eliminar_val)) {
            // Convertir abreviaciones a texto completo
            $dedicacion_eliminar_str = str_replace(
                ['MT', 'TC'], 
                ['Medio Tiempo', 'Tiempo Completo'], 
                htmlspecialchars($dedicacion_eliminar_val)
            );
             // MODIFICACIÓN AQUÍ para Ocasional Eliminación: Añadir la sede si existe
            if (!empty($sede_eliminar)) {
                $dedicacion_eliminar_str .= " - Sede {$sede_eliminar}";
            }
        }
        
        $salida_part = $dedicacion_eliminar_str;
    } else if ($tipo_docente_eliminar == "Catedra") {
    $horas_eliminar_p_val = null; // Usaremos null para saber si el valor es válido y > 0
    $horas_eliminar_r_val = null;

    // Evaluar horas_eliminar (propuesta)
    if (isset($cambio['horas_eliminar']) && is_numeric($cambio['horas_eliminar'])) {
        $temp_p = floatval($cambio['horas_eliminar']);
        if ($temp_p > 0) {
            $horas_eliminar_p_val = htmlspecialchars((string)$temp_p);
        }
    }

    // Evaluar horas_r_eliminar (regular/regional)
    // Asumiendo que 'horas_r_eliminar' es el campo correcto para las horas regulares a eliminar
    if (isset($cambio['horas_r_eliminar']) && is_numeric($cambio['horas_r_eliminar'])) {
        $temp_r = floatval($cambio['horas_r_eliminar']);
        if ($temp_r > 0) {
            $horas_eliminar_r_val = htmlspecialchars((string)$temp_r);
        }
    }
    
    $horas_eliminar_str = '';
    if ($horas_eliminar_p_val !== null && $horas_eliminar_r_val !== null) {
        // Ambas tienen valores válidos y > 0
        $horas_eliminar_str = "{$horas_eliminar_p_val} (P) / {$horas_eliminar_r_val} (R) horas";
    } elseif ($horas_eliminar_p_val !== null) {
        // Solo horas_eliminar tiene un valor válido y > 0
        $horas_eliminar_str = "{$horas_eliminar_p_val} horas";
    } elseif ($horas_eliminar_r_val !== null) {
        // Solo horas_r_eliminar tiene un valor válido y > 0
        $horas_eliminar_str = "{$horas_eliminar_r_val} horas";
    }
    
    $salida_part = $horas_eliminar_str;
    
    // Concatenar la sede si existe y hay alguna hora válida para mostrar
    if (!empty($sede_eliminar) && !empty($salida_part)) { // Se agregó !empty($salida_part) para que la sede solo se añada si hay horas
        $salida_part .= " - Sede {$sede_eliminar}";
    }
} else {
        $salida_part = '';
    }
    $sede_adicionar = htmlspecialchars($cambio['sede_adicionar'] ?: ''); // Obtener el valor de la sede aquí

    // --- Lógica para determinar los valores de la NUEVA vinculación (adicionar) ---
    $tipo_docente_str = htmlspecialchars($cambio['tipo_docente'] ?: '');
    $nueva_vinculacion_dedicacion_horas = '';

    if ($tipo_docente_str === "Ocasional") {
        $dedicacion_val_adicionar = '';
        if (!empty($cambio['dedicacion_adicionar'])) {
            $dedicacion_val_adicionar = $cambio['dedicacion_adicionar'];
        } elseif (!empty($cambio['tipo_dedicacion_r'])) {
            $dedicacion_val_adicionar = $cambio['tipo_dedicacion_r'];
        }

        if (!empty($dedicacion_val_adicionar)) {
            $nueva_vinculacion_dedicacion_horas = str_replace(
                ['MT', 'TC'],
                ['Medio Tiempo', 'Tiempo Completo'],
                htmlspecialchars($dedicacion_val_adicionar)
            );
             // **MODIFICACIÓN AQUÍ para Ocasional:** Añadir la sede si existe
            if (!empty($sede_adicionar)) {
                $nueva_vinculacion_dedicacion_horas .= " - Sede {$sede_adicionar}";
            }
        }
    } elseif ($tipo_docente_str === "Catedra") {
        $horas_val_adicionar_numeric = null; // Almacenará el valor numérico válido y > 0

        // Evaluar horas_adicionar
        if (isset($cambio['horas_adicionar']) && is_numeric($cambio['horas_adicionar'])) {
            $temp_horas_adicionar = floatval($cambio['horas_adicionar']);
            if ($temp_horas_adicionar > 0) {
                $horas_val_adicionar_numeric = $temp_horas_adicionar;
            }
        }

        // Si horas_adicionar no es válido o es 0, evaluar horas_r
        if ($horas_val_adicionar_numeric === null && isset($cambio['horas_r']) && is_numeric($cambio['horas_r'])) {
            $temp_horas_r = floatval($cambio['horas_r']);
            if ($temp_horas_r > 0) {
                $horas_val_adicionar_numeric = $temp_horas_r;
            }
        }

        if ($horas_val_adicionar_numeric !== null) {
            $nueva_vinculacion_dedicacion_horas = htmlspecialchars((string)$horas_val_adicionar_numeric) . ' horas';
            // **MODIFICACIÓN AQUÍ para Catedra:** Añadir la sede si existe
            if (!empty($sede_adicionar)) {
                $nueva_vinculacion_dedicacion_horas .= " - Sede {$sede_adicionar}";
            }
        }
    }
    // Fin de la lógica para ADICIONAR
    $nombre_profesor = htmlspecialchars($cambio['nombre_adicionar'] ?: $cambio['nombre_eliminar']);
$section->addTextBreak(0);

    // Construcción del texto narrativo con lógica mejorada
    $narrative_text = "Con el fin de atender esta necesidad, solicitamos comedidamente  el profesor {$nombre_profesor}";
    
    // Parte de la vinculación actual (que se elimina)
    $narrative_text .= " que pasa de {$tipo_docente_eliminar}";
    if (!empty($salida_part)) {
        $narrative_text .= " - {$salida_part}";
    }
    
    // Parte de la nueva vinculación
    $narrative_text .= " a {$tipo_docente_str}";
    if (!empty($nueva_vinculacion_dedicacion_horas)) {
        $narrative_text .= " {$nueva_vinculacion_dedicacion_horas}";
    }
    $narrative_text .= ".";

    $section->addText($narrative_text, ['size' => 11], $paragraphStyleLeft);
    $section->addTextBreak(0); // Pequeña separación

    // 3. Tabla para la "Nueva Vinculación"
   $section->addText('Nueva Vinculación:', ['bold' => true, 'size' => 9], $paragraphStyleLeft);
    $table_cambio = $section->addTable('ColspanRowspan');
    $table_cambio->setWidth(100 * 50, TblWidth::PERCENT);

    // Encabezados de tabla - Primera fila (para las celdas que abarcan dos filas)
    $row = $table_cambio->addRow();

    // Cédula
    $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyleb, $paragraphStyle);

    // Nombre
    $row->addCell(4000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyleb, $paragraphStyle);

    // Dedicación/hr (cabecera que abarca dos columnas)
    $row->addCell(2600, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyleb, $paragraphStyle);
    
    // Hoja de vida (cabecera que abarca dos columnas)
    $row->addCell(2000, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyleb, $paragraphStyle);

    // Tipo Docente (última columna)
    $row->addCell(1000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Tipo Docente', $cellTextStyleb, $paragraphStyle);

    // Encabezados de tabla - Segunda fila (para sub-encabezados y 'continue' de vMerge)
    $row = $table_cambio->addRow();

    // Celdas 'continue' para Cédula y Nombre (y Nº si la tuvieras)
    $row->addCell(1200, array('vMerge' => 'continue', 'borderSize' => 1)); // Cédula
    $row->addCell(4000, array('vMerge' => 'continue', 'borderSize' => 1)); // Nombre

    // Sub-encabezados de Dedicación/hr
    $row->addCell(1300, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
    $row->addCell(1300, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);

    // Sub-encabezados de Hoja de vida
    $row->addCell(1000, $headerCellStyle)->addText('Nuevo', $cellTextStyleb, $paragraphStyle);
    $row->addCell(1000, $headerCellStyle)->addText('Antig', $cellTextStyleb, $paragraphStyle);

    // Celda 'continue' para Tipo Docente
    $row->addCell(1000, array('vMerge' => 'continue', 'borderSize' => 1)); // Tipo Docente

    // Fila de datos
    $table_cambio->addRow();
    // Datos de las nuevas columnas
    $table_cambio->addCell(1200, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);
    $table_cambio->addCell(4000, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['nombre_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
    
    // Columnas de Dedicación/horas según el tipo de docente
    if ($cambio['tipo_docente'] == "Ocasional") {
        $table_cambio->addCell(1300, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['dedicacion_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
        $table_cambio->addCell(1300, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['tipo_dedicacion_r'] ?: ''), $cellTextStyle, $paragraphStyle);
    } elseif ($cambio['tipo_docente'] == "Catedra") {
        $table_cambio->addCell(1300, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['horas_adicionar'] ?: ''), $cellTextStyle, $paragraphStyle);
        $table_cambio->addCell(1300, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['horas_r'] ?: ''), $cellTextStyle, $paragraphStyle);
    } else {
        $table_cambio->addCell(1300, ['borderSize' => 1, 'valign' => 'center'])->addText('', $cellTextStyle, $paragraphStyle);
        $table_cambio->addCell(1300, ['borderSize' => 1, 'valign' => 'center'])->addText('', $cellTextStyle, $paragraphStyle);
    }

    // Columnas de Hoja de Vida
    $table_cambio->addCell(1000, ['borderSize' => 1, 'valign' => 'center'])
        ->addText(mb_strtoupper(htmlspecialchars($cambio['anexa_hv_docente_nuevo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);
    $table_cambio->addCell(1000, ['borderSize' => 1, 'valign' => 'center'])
        ->addText(mb_strtoupper(htmlspecialchars($cambio['actualiza_hv_antiguo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

    // Columna Tipo Docente
    $table_cambio->addCell(1000, ['borderSize' => 1, 'valign' => 'center'])->addText(htmlspecialchars($cambio['tipo_docente'] ?: ''), $cellTextStyle, $paragraphStyle);
    
    $section->addTextBreak(1); // Un salto de línea después de cada cambio de vinculación
}
// La línea siguiente estaba fuera del foreach, se mantiene así si es tu intención
$section->addTextBreak(1);
}
// Iterar por departamentos y luego por novedades dentro de cada departamento (EXCLUYENDO los de cambio de vinculación)
foreach ($grouped_solicitudes as $departamento_nombre => $novedades_por_depto) {
   // $section->addText('Departamento de ' . htmlspecialchars($departamento_nombre), ['bold' => true, 'size' => 11]);
    //$section->addTextBreak(0);

    foreach ($novedades_por_depto as $novedad_tipo => $solicitudes_por_novedad) {
        $novedad_mostrar = ucfirst($novedad_tipo);
        $section->addText('Novedad: ' . htmlspecialchars($novedad_mostrar), $fontStyleb, $paragraphStyleLeft);

        if (!empty($grouped_observations[$departamento_nombre][$novedad_tipo])) {
            $observation_text = '';
            foreach ($grouped_observations[$departamento_nombre][$novedad_tipo] as $index => $obs) {
                $observation_text .= '(' . ($index + 1) . ') ' . htmlspecialchars($obs) . ' ';
            }
            $section->addText($observation_text, $observationTextStyle, $paragraphStyleLeft);
            $section->addTextBreak(0);
        }
        $section->addTextBreak(0);

        $table = $section->addTable('ColspanRowspan');
        $table->setWidth(100 * 50, TblWidth::PERCENT);

        // Encabezados de la tabla - Primera fila
        $row = $table->addRow();

        // Nº
        $textrun = $row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addTextRun($paragraphStyle);
        $textrun->addText('N', $cellTextStyle);
        $textrun->addText('o', array_merge($cellTextStyle, array('superScript' => true)));

        // Cédula
        $row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cédula', $cellTextStyle, $paragraphStyle);

        // Nombre
        $row->addCell(4000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);

        // Dedicación/hr
        $row->addCell(1400, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedic/hr', $cellTextStyle, $paragraphStyle);

        // Hoja de vida
        $row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('H.de Vida', $cellTextStyle, $paragraphStyle);

        // Tipo Docente
        $row->addCell(1000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Tipo Docente', $cellTextStyle, $paragraphStyle);


        // Encabezados de la tabla - Segunda fila
        $row = $table->addRow();
        // Celdas 'continue' para los vMerge 'restart' de la fila anterior
        $row->addCell(400, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); // Nº
        $row->addCell(1200, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); // Cédula
        $row->addCell(4000, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); // Nombre

        // Sub-encabezados de Dedicación/hr
        $row->addCell(700, $headerCellStyle)->addText('Pop', $cellTextStyleb, $paragraphStyle);
        $row->addCell(700, $headerCellStyle)->addText('Reg', $cellTextStyleb, $paragraphStyle);

        // Sub-encabezados de Hoja de vida
        $row->addCell(350, $headerCellStyle)->addText('Nuevo', $cellTextStyleb, $paragraphStyle);
        $row->addCell(350, $headerCellStyle)->addText('Antig', $cellTextStyleb, $paragraphStyle);

        // Celdas 'continue' para Tipo Docente
        $row->addCell(1000, array('vMerge' => 'continue', 'borderSize' => 6, 'borderColor' => '999999')); // Tipo Docente

        $cont = 0; // Contador de filas dentro de cada tabla de novedad
        foreach ($solicitudes_por_novedad as $sol) {
            $cont++;
            $table->addRow();

            // Columna Nº
            $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);

            // Columna Cédula
            $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['cedula'] ?: ''), $cellTextStyle, $paragraphStyle);

            // Columna Nombre
            $full_nombre = htmlspecialchars($sol['nombre'] ?: '');
            $table->addCell(4000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($full_nombre, $cellTextStyle, $paragraphStyle);

            // Columnas de Dedicación/horas según el tipo de docente
            if ($sol['tipo_docente'] == "Ocasional") {
                $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['tipo_dedicacion'] ?: ''), $cellTextStyle, $paragraphStyle);
                $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['tipo_dedicacion_r'] ?: ''), $cellTextStyle, $paragraphStyle);
            } elseif ($sol['tipo_docente'] == "Catedra") {
                $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['horas'] ?: ''), $cellTextStyle, $paragraphStyle);
                $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(htmlspecialchars($sol['horas_r'] ?: ''), $cellTextStyle, $paragraphStyle);
            } else {
                $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
                $table->addCell(700, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText('', $cellTextStyle, $paragraphStyle);
            }

            // Columnas de Hoja de Vida
            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
                ->addText(mb_strtoupper(htmlspecialchars($sol['anexa_hv_docente_nuevo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

            $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
                ->addText(mb_strtoupper(htmlspecialchars($sol['actualiza_hv_antiguo'] ?: ''), 'UTF-8'), $cellTextStyle, $paragraphStyle);

            // Columna Tipo Docente
            $table->addCell(1000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])
                ->addText(htmlspecialchars($sol['tipo_docente'] ?: ''), $cellTextStyle, $paragraphStyle);
        }
        $section->addTextBreak(1); // Espacio entre tablas de novedad
    }
}

$section->addTextBreak(1);
/*$section->addText(
    'Agradezco su atención al presente. Para cualquier inquietud, no dude en contactarme.',
    'normalSize11', 'justify'
);*/
//$section->addTextBreak(1);

// Firma (Decano)
$section->addText(
    'Universitariamente,',
    'normalSize11', 'left'
);
$section->addTextBreak(2);

$section->addText(
    $decano_nombre,
    ['bold' => true, 'size' => 11], 'left'
);
$section->addText(
    'Decano/a de la Facultad de ' . $nombre_facultad_principal,
    'normalSize11', 'left'
);
$section->addTextBreak(1);
$section->addText(
    'Elaborado por: ' . $elaborado_por,
    'normalSize9', 'left'
);
$section->addText(
    'Folios: ' . $folios,
    'normalSize9', 'left'
);


// Configurar el nombre del archivo y las cabeceras para la descarga
$fileName = 'Novedades_' . $anio_semestre . '_Facultad_' . $nombre_facultad_principal . '_' . date('Ymd') . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Guardar el documento en la salida
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');

// Cerrar la conexión a la base de datos
$conn->close();
?>