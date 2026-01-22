<?php
// Define un elemento activo para el menú (si se reincorporan los includes)
$active_menu_item = 'comparativo_general'; 
// Si usas un archivo headerz.php, asegúrate de que esté incluido aquí.
require('include/headerz.php'); 

// --------------------------------------------------------------------------------------
// 1. Conexión a la Base de Datos y Funciones
// --------------------------------------------------------------------------------------

// AJUSTAR SEGÚN TU CONFIGURACIÓN REAL DE BASE DE DATOS
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Lógica de sesión (mantenida del archivo report_depto_comparativo.php)
if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si la sesión no está activa, solo muestra el enlace. Si necesitas detener la ejecución, descomenta el 'exit()'.
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
}

/**
 * Función para encontrar el semestre inmediatamente anterior (P1)
 */
function get_periodo_anterior($conn, $p2) {
    $p2_safe = $conn->real_escape_string($p2);
    
    $sql = "
        SELECT anio_semestre
        FROM solicitudes 
        WHERE anio_semestre < '$p2_safe'
        AND cedula != '222'
        AND (estado IS NULL OR estado <> 'an')  -- FILTRAR ANULADOS
        GROUP BY anio_semestre
        ORDER BY anio_semestre DESC
        LIMIT 1
    ";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['anio_semestre'];
    }
    return null; 
}

// --------------------------------------------------------------------------------------
// 2. Manejo de Parámetros y Periodos
// --------------------------------------------------------------------------------------

$periodo_actual = isset($_POST['anio_semestre']) 
    ? $_POST['anio_semestre'] 
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : null);

$filtro_departamento = isset($_POST['filtro_departamento']) ? $_POST['filtro_departamento'] : (isset($_GET['filtro_departamento']) ? $_GET['filtro_departamento'] : null);
$filtro_tipo_docente = isset($_POST['filtro_tipo_docente']) ? $_POST['filtro_tipo_docente'] : (isset($_GET['filtro_tipo_docente']) ? $_GET['filtro_tipo_docente'] : null);

if (!$periodo_actual) {
    echo "<h1>Error</h1><p>Debe seleccionar un período actual (P2).</p>";
    exit();
}

$periodo_anterior = get_periodo_anterior($conn, $periodo_actual);

if (!$periodo_anterior) {
    echo "<h1>Advertencia</h1><p>No se encontró un período anterior (P1) para el período '{$periodo_actual}'.</p>";
    // No salimos con exit() para mostrar al menos el encabezado, aunque las tablas estén vacías
}

// Parámetros seguros para la consulta
$p2_safe = $conn->real_escape_string($periodo_actual);
$p1_safe = $conn->real_escape_string($periodo_anterior);

// Construcción de Cláusulas WHERE (Filtros Opcionales)
$filter_clauses = "";
if ($filtro_departamento) {
    $filter_clauses .= " AND s.departamento_id = " . $conn->real_escape_string($filtro_departamento);
}
if ($filtro_tipo_docente) {
    $filter_clauses .= " AND s.tipo_docente = '" . $conn->real_escape_string($filtro_tipo_docente) . "'";
}

// --------------------------------------------------------------------------------------
// 3. Consulta SQL Principal (Comparativo)
// --------------------------------------------------------------------------------------
$sql_comparativo = "
SELECT
    T1.cedula,
    T1.nombre_completo, 
    T1.departamento_nombre, 
    T1.departamento_id,
    T1.tipo_docente_p2,
    T1.tipo_docente_p1,
    T1.dedicacion_p2,
    T1.dedicacion_p1,
    T1.horas_p2,
    T1.horas_p1,
    CASE
        WHEN T1.en_p2 = 1 AND T1.en_p1 = 0 THEN 'NUEVO'             
        WHEN T1.en_p2 = 0 AND T1.en_p1 = 1 THEN 'DEJA DE VINCULARSE' 
        WHEN T1.en_p2 = 1 AND T1.en_p1 = 1 THEN 
            CASE 
                WHEN T1.tipo_docente_p2 <> T1.tipo_docente_p1 THEN 'CAMBIA VINCULACIÓN'
                WHEN T1.tipo_docente_p2 = 'Ocasional' AND T1.dedicacion_p2 <> T1.dedicacion_p1 THEN 'CAMBIA DEDICACIÓN'
                WHEN T1.tipo_docente_p2 = 'Catedra' AND (T1.horas_p2 <> T1.horas_p1) THEN 'CAMBIA HORAS'
                ELSE 'CONTINÚA'
            END
        ELSE 'ERROR' 
    END AS clasificacion,
    T1.en_p2,
    T1.en_p1
FROM (
    SELECT
        s.cedula,
        d.nombre_completo, 
        dep.depto_nom_propio AS departamento_nombre, 
        s.departamento_id,
        MAX(CASE WHEN s.anio_semestre = '$p2_safe' THEN s.tipo_docente END) AS tipo_docente_p2,
        MAX(CASE WHEN s.anio_semestre = '$p1_safe' THEN s.tipo_docente END) AS tipo_docente_p1,
        MAX(CASE WHEN s.anio_semestre = '$p2_safe' THEN COALESCE(NULLIF(s.tipo_dedicacion_r, ''), s.tipo_dedicacion) END) AS dedicacion_p2,
        MAX(CASE WHEN s.anio_semestre = '$p1_safe' THEN COALESCE(NULLIF(s.tipo_dedicacion_r, ''), s.tipo_dedicacion) END) AS dedicacion_p1,
        MAX(CASE WHEN s.anio_semestre = '$p2_safe' THEN COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0) END) AS horas_p2,
        MAX(CASE WHEN s.anio_semestre = '$p1_safe' THEN COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0) END) AS horas_p1,
        MAX(CASE WHEN s.anio_semestre = '$p2_safe' THEN 1 ELSE 0 END) AS en_p2,
        MAX(CASE WHEN s.anio_semestre = '$p1_safe' THEN 1 ELSE 0 END) AS en_p1
    FROM solicitudes s 
    LEFT JOIN tercero d ON TRIM(s.cedula) = TRIM(d.documento_tercero) 
    LEFT JOIN deparmanentos dep ON s.departamento_id = dep.PK_DEPTO 
    WHERE (s.estado IS NULL OR s.estado <> 'an') 
      AND (s.novedad IS NULL OR s.novedad <> 'eliminar')
      AND s.anio_semestre IN ('$p2_safe', '$p1_safe')
      AND TRIM(s.cedula) <> '222'   
      $filter_clauses 
    GROUP BY 
        s.cedula, 
        d.nombre_completo, 
        dep.depto_nom_propio, 
        s.departamento_id
) AS T1
WHERE T1.en_p2 = 1 OR T1.en_p1 = 1
ORDER BY clasificacion, T1.departamento_nombre, T1.nombre_completo
";

$result_comparativo = $conn->query($sql_comparativo);
$docentes_comparativo = [];
$conteo_general = [
    'NUEVO' => 0, 'DEJA DE VINCULARSE' => 0, 'CONTINÚA' => 0, 'CAMBIA VINCULACIÓN' => 0,
    'CAMBIA DEDICACIÓN' => 0, 'CAMBIA HORAS' => 0, 'TOTAL_P2' => 0, 'TOTAL_P1' => 0
];
$conteo_departamental = [];

// --------------------------------------------------------------------------------------
// 4.5. Precargar facultades para mejor rendimiento
// --------------------------------------------------------------------------------------

$facultades_map = []; // Array para mapear: PK_DEPTO => Nombre_fac_minb

// Una sola consulta para traer todos los departamentos con sus facultades
$sql_facultades = "SELECT d.PK_DEPTO, f.Nombre_fac_minb 
                  FROM deparmanentos d 
                  LEFT JOIN facultad f ON d.FK_FAC = f.PK_FAC";
$result_facultades = $conn->query($sql_facultades);

if ($result_facultades) {
    while ($row = $result_facultades->fetch_assoc()) {
        $facultades_map[$row['PK_DEPTO']] = $row['Nombre_fac_minb'] ?? 'SIN FACULTAD';
    }
}

// --------------------------------------------------------------------------------------
// 4.6. CONSULTAS PARA OBTENER DOCENTES ÚNICOS POR PERÍODO (para cálculo correcto)
// --------------------------------------------------------------------------------------

// CORRECCIÓN: FILTRAR ANULADOS en estas consultas también
$sql_cedulas_p1 = "SELECT DISTINCT cedula FROM solicitudes 
                   WHERE anio_semestre = '$p1_safe' 
                   AND (estado IS NULL OR estado <> 'an')  -- FILTRAR ANULADOS
                   AND (novedad IS NULL OR novedad <> 'eliminar')
                   AND cedula != '222'";
                   
$sql_cedulas_p2 = "SELECT DISTINCT cedula FROM solicitudes 
                   WHERE anio_semestre = '$p2_safe' 
                   AND (estado IS NULL OR estado <> 'an')  -- FILTRAR ANULADOS
                   AND (novedad IS NULL OR novedad <> 'eliminar')
                   AND cedula != '222'";

$result_cedulas_p1 = $conn->query($sql_cedulas_p1);
$result_cedulas_p2 = $conn->query($sql_cedulas_p2);

$cedulas_p1 = [];
if ($result_cedulas_p1) {
    while ($row = $result_cedulas_p1->fetch_assoc()) {
        $cedulas_p1[] = $row['cedula'];
    }
}

$cedulas_p2 = [];
if ($result_cedulas_p2) {
    while ($row = $result_cedulas_p2->fetch_assoc()) {
        $cedulas_p2[] = $row['cedula'];
    }
}

// Calcular los conteos REALES para las tarjetas globales
$conteo_general['TOTAL_P1'] = count($cedulas_p1);
$conteo_general['TOTAL_P2'] = count($cedulas_p2);

// Convertir arrays a formato clave-valor para uso más eficiente
$cedulas_p1_assoc = array_flip($cedulas_p1); // clave = cédula, valor = índice
$cedulas_p2_assoc = array_flip($cedulas_p2);

// --------------------------------------------------------------------------------------
// 4. Procesamiento de Resultados (Comparativo)
// --------------------------------------------------------------------------------------

// Array para rastrear cédulas procesadas en cada período
$cedulas_procesadas_p1 = [];
$cedulas_procesadas_p2 = [];

if ($result_comparativo) {
    while ($row = $result_comparativo->fetch_assoc()) {
        $docentes_comparativo[] = $row;
        $clasif = $row['clasificacion'];
        $cedula = $row['cedula'];
        $depto_key = $row['departamento_nombre'] ?? $row['departamento_id'] ?? 'SIN DEPARTAMENTO';
        
        // Obtener la facultad del departamento DESDE EL ARRAY PRECARGADO
        $facultad_nombre = $facultades_map[$row['departamento_id']] ?? 'SIN FACULTAD';
        
        // LÓGICA CORREGIDA: Solo contar como "NUEVO" o "SALIENTE" si realmente no existe en el otro período
        if ($clasif == 'NUEVO') {
            // Verificar si realmente NO está en P1
            if (!isset($cedulas_p1_assoc[$cedula])) {
                $conteo_general['NUEVO']++;
            }
        } elseif ($clasif == 'DEJA DE VINCULARSE') {
            // Verificar si realmente NO está en P2
            if (!isset($cedulas_p2_assoc[$cedula])) {
                $conteo_general['DEJA DE VINCULARSE']++;
            }
        } elseif (isset($conteo_general[$clasif])) {
            $conteo_general[$clasif]++;
        }

        // Para conteo departamental, contar todo (incluye movilidad interna)
        if (!isset($conteo_departamental[$depto_key])) {
            $conteo_departamental[$depto_key] = [
                'facultad_nombre' => $facultad_nombre,
                'NUEVO' => 0, 'DEJA DE VINCULARSE' => 0, 'CONTINÚA' => 0, 'CAMBIA VINCULACIÓN' => 0,
                'CAMBIA DEDICACIÓN' => 0, 'CAMBIA HORAS' => 0, 'TOTAL_P2' => 0, 'TOTAL_P1' => 0
            ];
        }
        
        $conteo_departamental[$depto_key][$clasif]++;
        
        // Contar docentes por período para el departamento
        if ((int)$row['en_p2'] === 1) { 
            $conteo_departamental[$depto_key]['TOTAL_P2']++; 
            $cedulas_procesadas_p2[$cedula] = true;
        }
        if ((int)$row['en_p1'] === 1) { 
            $conteo_departamental[$depto_key]['TOTAL_P1']++; 
            $cedulas_procesadas_p1[$cedula] = true;
        }
    }
}

// --------------------------------------------------------------------------------------
// 4.7. CÁLCULO FINAL DE "CONTINÚA" para tarjetas globales
// --------------------------------------------------------------------------------------
// Un docente "CONTINÚA" si está en AMBOS períodos (P1 y P2)
$continuan_reales = array_intersect($cedulas_p1, $cedulas_p2);
$conteo_general['CONTINÚA'] = count($continuan_reales);

// --------------------------------------------------------------------------------------
// 4.8. VERIFICACIÓN Y CORRECCIÓN FINAL
// --------------------------------------------------------------------------------------
// Asegurarnos de que los números coincidan
$nuevos_calculados = array_diff($cedulas_p2, $cedulas_p1);
$salientes_calculados = array_diff($cedulas_p1, $cedulas_p2);

// Si hay discrepancia, usar los cálculos basados en arrays
if (count($nuevos_calculados) != $conteo_general['NUEVO']) {
    $conteo_general['NUEVO'] = count($nuevos_calculados);
}

if (count($salientes_calculados) != $conteo_general['DEJA DE VINCULARSE']) {
    $conteo_general['DEJA DE VINCULARSE'] = count($salientes_calculados);
}

// --------------------------------------------------------------------------------------
// 4.9. MATRIZ FACULTAD vs TIPO DE NÓMINA (NUEVOS y SALIENTES)
// --------------------------------------------------------------------------------------

// Consulta para obtener la matriz de Facultad vs Tipo de Nómina para NUEVOS y SALIENTES
$sql_matriz = "
    SELECT 
        f.Nombre_fac_minb AS facultad,
        s.tipo_docente,
        CASE 
            WHEN s.anio_semestre = '$p2_safe' AND s.cedula NOT IN (
                SELECT DISTINCT cedula FROM solicitudes 
                WHERE anio_semestre = '$p1_safe' 
                AND (estado IS NULL OR estado <> 'an')
                AND cedula != '222'
            ) THEN 'NUEVO'
            WHEN s.anio_semestre = '$p1_safe' AND s.cedula NOT IN (
                SELECT DISTINCT cedula FROM solicitudes 
                WHERE anio_semestre = '$p2_safe' 
                AND (estado IS NULL OR estado <> 'an')
                AND cedula != '222'
            ) THEN 'SALIENTE'
            ELSE 'OTRO'
        END AS clasificacion,
        COUNT(DISTINCT s.cedula) AS cantidad
    FROM solicitudes s
    JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO
    JOIN facultad f ON d.FK_FAC = f.PK_FAC
    WHERE s.anio_semestre IN ('$p1_safe', '$p2_safe')
      AND (s.estado IS NULL OR s.estado <> 'an')
      AND (s.novedad IS NULL OR s.novedad <> 'eliminar')
      AND s.cedula != '222'
      AND (
          (s.anio_semestre = '$p2_safe' AND s.cedula NOT IN (
              SELECT DISTINCT cedula FROM solicitudes 
              WHERE anio_semestre = '$p1_safe' 
              AND (estado IS NULL OR estado <> 'an')
              AND cedula != '222'
          ))
          OR
          (s.anio_semestre = '$p1_safe' AND s.cedula NOT IN (
              SELECT DISTINCT cedula FROM solicitudes 
              WHERE anio_semestre = '$p2_safe' 
              AND (estado IS NULL OR estado <> 'an')
              AND cedula != '222'
          ))
      )
    GROUP BY f.Nombre_fac_minb, s.tipo_docente, clasificacion
    ORDER BY facultad, tipo_docente, clasificacion
";

$result_matriz = $conn->query($sql_matriz);
$matriz_facultad_tipo = [];

// Inicializar matriz con todas las facultades y tipos
$facultades_unicas = array_unique(array_column($conteo_departamental, 'facultad_nombre'));
$tipos_docente = ['Ocasional', 'Catedra'];
$clasificaciones = ['NUEVO', 'SALIENTE'];

foreach ($facultades_unicas as $facultad) {
    if (empty($facultad) || $facultad == 'SIN FACULTAD') continue;
    
    $matriz_facultad_tipo[$facultad] = [
        'Ocasional' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0],
        'Catedra' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0],
        'TOTAL' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0]
    ];
}

// Procesar resultados de la consulta
if ($result_matriz) {
    while ($row = $result_matriz->fetch_assoc()) {
        $facultad = $row['facultad'] ?? 'SIN FACULTAD';
        $tipo_docente = $row['tipo_docente'] ?? 'Otro';
        $clasificacion = $row['clasificacion'];
        $cantidad = (int)$row['cantidad'];
        
        if (!isset($matriz_facultad_tipo[$facultad])) {
            $matriz_facultad_tipo[$facultad] = [
                'Ocasional' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0],
                'Catedra' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0],
                'TOTAL' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0]
            ];
        }
        
        if (in_array($tipo_docente, $tipos_docente) && in_array($clasificacion, $clasificaciones)) {
            $matriz_facultad_tipo[$facultad][$tipo_docente][$clasificacion] = $cantidad;
            $matriz_facultad_tipo[$facultad][$tipo_docente]['TOTAL'] = 
                $matriz_facultad_tipo[$facultad][$tipo_docente]['NUEVO'] + 
                $matriz_facultad_tipo[$facultad][$tipo_docente]['SALIENTE'];
            
            $matriz_facultad_tipo[$facultad]['TOTAL'][$clasificacion] += $cantidad;
            $matriz_facultad_tipo[$facultad]['TOTAL']['TOTAL'] = 
                $matriz_facultad_tipo[$facultad]['TOTAL']['NUEVO'] + 
                $matriz_facultad_tipo[$facultad]['TOTAL']['SALIENTE'];
        }
    }
}

// Ordenar matriz por nombre de facultad
ksort($matriz_facultad_tipo);

// --------------------------------------------------------------------------------------
// 4.5. Mapa de Estado P2 y Departamento para Continuidad 
// --------------------------------------------------------------------------------------
$p2_info_map = []; // Contiene 'en_p2' y 'departamento_p2'
foreach ($docentes_comparativo as $docente) {
    $cedula = $docente['cedula'];
    
    // Solo si está vinculado en P2, guardamos el departamento de P2.
    if ((int)$docente['en_p2'] === 1) { 
        $p2_info_map[$cedula] = [
            'en_p2' => 1, 
            // Usamos el departamento_nombre de la consulta comparativa, que es el de P2
            'departamento_p2' => $docente['departamento_nombre']
        ];
    } else {
        // No vinculado en P2
        $p2_info_map[$cedula] = [
            'en_p2' => 0, 
            'departamento_p2' => null 
        ];
    }
}

// --------------------------------------------------------------------------------------
// 5. OBTENER HISTÓRICO Y PERIODOS
// --------------------------------------------------------------------------------------
$cedulas = array_column($docentes_comparativo, 'cedula');
$historico_docentes = [];
$todos_semestres = [];

if (!empty($cedulas)) {
    $cedulas_placeholder = implode(',', array_fill(0, count($cedulas), '?'));
     $sql_historico = "
        SELECT 
            S.cedula, S.anio_semestre,
            CONCAT(D.NOMBRE_DEPTO, ' (', S.tipo_docente, ' - ', 
                CASE 
                    WHEN S.tipo_docente = 'Catedra' THEN CONCAT(COALESCE(S.horas, 0) + COALESCE(S.horas_r, 0), 'h')
                    ELSE COALESCE(NULLIF(S.tipo_dedicacion_r, ''), S.tipo_dedicacion)
                END,
                ')' 
            ) AS vinculacion_info
        FROM solicitudes S
        JOIN deparmanentos D ON S.departamento_id = D.PK_DEPTO
        WHERE S.cedula IN ($cedulas_placeholder)
            AND S.cedula != '222'  
            AND (
                (S.estado IS NULL OR S.estado <> 'an')  -- FILTRAR ANULADOS
                OR (S.estado = 'an' AND S.anio_semestre <= '{$p1_safe}' AND (S.s_observacion LIKE '%eemplazo%' OR S.s_observacion LIKE '%jubilaci%' OR S.s_observacion LIKE '%incapacidad%' OR S.s_observacion LIKE '%Novedades docentes%'))
                OR (S.estado = 'an' AND S.anio_semestre > '{$p1_safe}' AND S.id_novedad IS NOT NULL)
            )
        ORDER BY S.cedula, S.anio_semestre DESC
    ";
    
    $stmt = $conn->prepare($sql_historico);
    if ($stmt) {
        $types = str_repeat('s', count($cedulas));
        $stmt->bind_param($types, ...$cedulas);
        $stmt->execute();
        $result_historico = $stmt->get_result();
        
        while ($row = $result_historico->fetch_assoc()) {
            $cedula = $row['cedula'];
            $semestre = $row['anio_semestre'];
            if (!isset($historico_docentes[$cedula])) { $historico_docentes[$cedula] = []; }
            
            $info_semestre = '';
             if (strpos($row['vinculacion_info'], 'Ocasional') !== false) {
                if (strpos($row['vinculacion_info'], 'TIEMPO COMPLETO') !== false || strpos($row['vinculacion_info'], 'TC') !== false) { $info_semestre = 'OTC'; } 
                elseif (strpos(strtoupper($row['vinculacion_info']), 'MEDIO TIEMPO') !== false || strpos($row['vinculacion_info'], 'MT') !== false) { $info_semestre = 'OMT'; } 
                else { $info_semestre = 'OCAS'; }
            } elseif (strpos($row['vinculacion_info'], 'Catedra') !== false) {
                preg_match('/(\d+)h/', $row['vinculacion_info'], $matches);
                $horas = $matches[1] ?? '0';
                $info_semestre = 'C.' . $horas . 'Hrs';
            } else { $info_semestre = 'VINC'; }
            
            $historico_docentes[$cedula][$semestre] = $info_semestre;
        }
        $stmt->close();
    }
    
    $periodos_sql = "SELECT nombre_periodo FROM periodo ORDER BY nombre_periodo ASC";
    $periodos_res = $conn->query($periodos_sql);
    while ($row = $periodos_res->fetch_assoc()) {
        $todos_semestres[] = $row['nombre_periodo'];
    }
}

// --------------------------------------------------------------------------------------
// 6. Análisis de Continuidad (CORRECCIÓN: Periodos totales son globales)
// --------------------------------------------------------------------------------------
// Se mantiene la lógica de score y horas para el ordenamiento en JS
$sql_continuidad = "
    SELECT
        s.cedula,
        d.nombre_completo,
        -- Usamos una función de agregación (MAX) para el departamento (solo para referencia)
        MAX(dep.depto_nom_propio) AS departamento_nombre, 
        
        -- CAMBIO CLAVE: COUNT global (agrupando solo por cedula)
        COUNT(DISTINCT s.anio_semestre) AS periodos_totales,
        
        -- Suma de puntos para Ocasional: TC=1, MT=0.5
        SUM(CASE 
            WHEN s.tipo_docente = 'Ocasional' AND UPPER(COALESCE(NULLIF(s.tipo_dedicacion_r, ''), s.tipo_dedicacion)) IN ('TIEMPO COMPLETO', 'TC') THEN 1
            WHEN s.tipo_docente = 'Ocasional' AND UPPER(COALESCE(NULLIF(s.tipo_dedicacion_r, ''), s.tipo_dedicacion)) IN ('MEDIO TIEMPO', 'MT') THEN 0.5
            ELSE 0
        END) AS ocasional_score,
        
        -- Suma de horas para Catedra
        SUM(CASE 
            WHEN s.tipo_docente = 'Catedra' THEN COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0)
            ELSE 0
        END) AS catedra_hours
        
    FROM solicitudes s 
    LEFT JOIN tercero d ON TRIM(s.cedula) = TRIM(d.documento_tercero)
    LEFT JOIN deparmanentos dep ON s.departamento_id = dep.PK_DEPTO 
    WHERE (s.estado IS NULL OR s.estado <> 'an')  -- FILTRAR ANULADOS
      AND (s.novedad IS NULL OR s.novedad <> 'eliminar')
      AND s.cedula != '222' -- <-- ¡FILTRO AÑADIDO AQUÍ!
    GROUP BY s.cedula, d.nombre_completo 
    ORDER BY periodos_totales DESC, d.nombre_completo ASC
";

$result_continuidad = $conn->query($sql_continuidad);
$full_continuidad_data = [];

if ($result_continuidad) {
    while ($row = $result_continuidad->fetch_assoc()) {
        $cedula = $row['cedula'];
        
        // Aseguramos que los valores sean numéricos para el JS
        $row['ocasional_score'] = (float)$row['ocasional_score'];
        $row['catedra_hours'] = (float)$row['catedra_hours'];
        $row['periodos_totales'] = (int)$row['periodos_totales'];
        
        // INYECCIÓN DEL ESTADO P2 Y DEPARTAMENTO P2 
        $info_p2 = $p2_info_map[$cedula] ?? ['en_p2' => 0, 'departamento_p2' => null];
        $row['en_p2'] = $info_p2['en_p2']; 
        $row['departamento_p2'] = $info_p2['departamento_p2']; 
        // FIN MODIFICACIÓN
        
        $full_continuidad_data[] = $row;
    }
}

// Cierre de Conexión
$conn->close();

// --------------------------------------------------------------------------------------
// 7. FUNCIÓN AUXILIAR (Formato de badge unificado)
// --------------------------------------------------------------------------------------

/**
 * Función para formatear el detalle unificado (Tipo + Detalle) en un solo badge.
 */
function formatearDetalleUnificado($tipo_docente, $dedicacion, $horas) {
    if (!$tipo_docente) {
        return '-';
    }
    
    $horas = (int)$horas; 
    $texto_badge = '';
    $clase_badge = 'badge ';

    if ($tipo_docente == 'Ocasional') {
        $clase_badge .= 'bg-primary text-white'; 
        
        $dedicacion_abbr = '??';
        if ($dedicacion) {
            $dedicacion_upper = strtoupper($dedicacion);
            if (strpos($dedicacion_upper, 'TIEMPO COMPLETO') !== false) {
                $dedicacion_abbr = 'TC';
            } elseif (strpos($dedicacion_upper, 'MEDIO TIEMPO') !== false) {
                 $dedicacion_abbr = 'MT';
            } else {
                $dedicacion_abbr = substr($dedicacion_upper, 0, 2);
            }
        }
        $texto_badge = 'O' . $dedicacion_abbr;

    } elseif ($tipo_docente == 'Catedra') {
        $clase_badge .= 'bg-warning text-dark'; 
        $horas_display = $horas > 0 ? $horas : 0;
        $texto_badge = 'C.' . $horas_display . 'Hrs';
        
    } else {
        $clase_badge .= 'bg-secondary text-white'; 
        $texto_badge = strtoupper(substr($tipo_docente, 0, 3)); 
    }

    return "<span class='{$clase_badge}'>{$texto_badge}</span>";
}

// --- LÓGICA PHP PARA OCULTAR COLUMNAS EN DATATABLES ---
$historico_indices_js = '[]'; 
if (!empty($todos_semestres)) {
    $num_periodos = count($todos_semestres);
    $first_historico_index = 6; 
    $last_historico_index = $first_historico_index + $num_periodos - 1;
    $historico_indices = range($first_historico_index, $last_historico_index);
    $historico_indices_js = json_encode($historico_indices);
}
// --------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comparativo General - UNICAUCA</title>

    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
 <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   
    <style>
        /* Estilos UNICAUCA o personalizados */
        .table-purple { background-color: #f0e6ff !important; }
        .bg-unicauca-gris { background-color: #6c757d !important; }
        .bg-unicauca-amarillo { background-color: #ffc107 !important; color: #000 !important; }
        .bg-unicauca-azul { background-color: #0056b3 !important; }
        .table-header-custom {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%) !important;
            color: white !important;
        }
        /* Estilos para la tabla de resumen */
        .depto-row { cursor: pointer; transition: all 0.3s ease; font-weight: 400;}
        .depto-row:hover { background-color: rgba(0, 123, 255, 0.05) !important;}
        .depto-row.selected {
            font-weight: 600 !important;
            background-color: rgba(0, 123, 255, 0.08) !important;
            border-left: 3px solid #0056b3;
        }
        
        #loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.95); /* Fondo más opaco para ser visible */
    z-index: 10000; /* Asegurar que esté por encima de todo */
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    color: #0056b3; /* Color UNICAUCA */
    font-size: 1.5rem;
    font-weight: bold;
}
.spinner-custom {
    width: 4rem;
    height: 4rem;
    border: 0.4em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border .75s linear infinite;
}
@keyframes spinner-border {
    to { transform: rotate(360deg); }
}
        
        /* Estilo para la fila seleccionada (color de fondo) */
/* Estilo para la fila seleccionada (color de fondo) */
.depto-row.selected {
    /* Mantén el color de fondo que te funcione */
    background-color: #007bff !important; 
}

/* Estilo para el texto de las columnas "Nuevos" y "Continúan" */
/* Si "Nuevos" es la 3ª, probablemente "Continúan" sea la 4ª o 5ª. Probemos con la 5ª. */
.depto-row.selected td:nth-child(3),
.depto-row.selected td:nth-child(5) { 
    color: white !important; /* ¡Ahora apuntamos a la 5ª columna! */
    font-weight: bold;       
}

/* Generalmente se recomienda que todas las celdas cambien de color al seleccionar la fila */
.depto-row.selected td {
    color: white !important; 
}

/* Estilos para la matriz */
.matrix-cell-nuevo {
    background-color: #d4edda !important; /* Verde claro */
    font-weight: bold;
    color: #155724;
}
.matrix-cell-saliente {
    background-color: #f8d7da !important; /* Rojo claro */
    font-weight: bold;
    color: #721c24;
}
.matrix-cell-total {
    background-color: #e2e3e5 !important; /* Gris claro */
    font-weight: bold;
    color: #383d41;
}
.matrix-header {
    background-color: #495057 !important;
    color: white !important;
    font-weight: bold;
}
.matrix-facultad-header {
    background-color: #6c757d !important;
    color: white !important;
    font-weight: bold;
}
      
        
        
        
    </style>
</head>
<body>
<div id="loading-overlay">
    <div class="spinner-custom mb-3" role="status"></div>
    <span>Cargando histórico de vinculación... Por favor espere (Primera Carga).</span>
    <small class="text-muted mt-2">Este mensaje desaparecerá automáticamente.</small>
</div>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 Análisis de Continuidad Histórica: 
            <span class="badge bg-unicauca-azul"><?= htmlspecialchars($periodo_actual); ?></span> vs 
            <span class="badge bg-unicauca-gris"><?= htmlspecialchars($periodo_anterior ?? 'N/A'); ?></span>
        </h2>
    </div>
    
    <h4 class="mb-3"><i class="fas fa-chart-bar text-unicauca-azul"></i> Resumen Global (Sin Movilidad Interna)</h4>
    <div class="row mb-5 g-3">
        <div class="col-md-2">
            <div class="card text-center bg-success text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-plus"></i> NUEVOS</h5>
                    <p class="display-4"><?= $conteo_general['NUEVO']; ?></p>
                    <small class="text-white-50">NO estaban en <?= htmlspecialchars($periodo_anterior ?? 'P1'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-danger text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-minus"></i> SALIENTES</h5>
                    <p class="display-4"><?= $conteo_general['DEJA DE VINCULARSE']; ?></p>
                    <small class="text-white-50">NO están en <?= htmlspecialchars($periodo_actual); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-check"></i> CONTINÚAN</h5>
                    <p class="display-4"><?= $conteo_general['CONTINÚA']; ?></p>
                    <small class="text-white-50">En AMBOS períodos</small>
                </div>
            </div>
        </div>
               <div class="col-md-3">
                <div class="card text-center bg-unicauca-amarillo text-dark shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total <?= $periodo_actual ?></h5>
                        <p class="display-4"><?= $conteo_general['TOTAL_P2']; ?></p>
                        <small class="text-muted">Docentes únicos en <?= $periodo_actual ?> (sin anulados)</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-unicauca-gris text-white shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i> Total <?= $periodo_anterior ?></h5>
                        <p class="display-4"><?= $conteo_general['TOTAL_P1']; ?></p>
                        <small class="text-white-50">Docentes únicos en <?= $periodo_anterior ?> (sin anulados)</small>
                    </div>
                </div>
            </div>
    </div>
    
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle"></i> <strong>Nota:</strong> Los conteos en las tarjetas superiores muestran solo los cambios reales en la nómina universitaria. No incluyen docentes que cambiaron de departamento o tipo de vinculación, los cuales se muestran en las tablas detalladas como "CAMBIA VINCULACIÓN", "CAMBIA DEDICACIÓN" o "CAMBIA HORAS". <strong>Tampoco incluyen solicitudes 222 "NN" ni anulados (estado = 'an').</strong>
    </div>
    
<hr>

<h4 class="mt-5 mb-3 d-flex justify-content-between align-items-center">
    <span><i class="fas fa-building text-unicauca-azul"></i> Resumen por Departamento (Incluye Movilidad Interna)</span>
    <div class="d-flex align-items-center gap-2">
        <a href="#" class="btn btn-outline-primary btn-sm" id="btnTop10">
            <i class="fas fa-trophy me-1"></i> Top 10 Departamentos
        </a>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseResumenDepto" aria-expanded="true" aria-controls="collapseResumenDepto">
            <i class="fas fa-compress-alt icon-toggle"></i> 
        </button>
    </div>
</h4>

<div class="collapse show" id="collapseResumenDepto">
    <div class="table-responsive">
        <table id="tablaResumenDepto" class="table table-striped table-hover" style="width:100%">
            <thead class="table-header-custom">
                <tr>
                    <th>Facultad</th>
                    <th>Departamento</th>
                    <th>Total P1 (<?= htmlspecialchars($periodo_anterior ?? 'N/A'); ?>)</th>
                    <th>Total P2 (<?= htmlspecialchars($periodo_actual); ?>)</th>
                    <th class="text-success">Nuevos</th>
                    <th class="text-primary">Continúan</th>
                    <th class="text-warning">Cambia Vinc.</th>
                    <th style="color:#6f42c1;">Cambia Ded.</th>
                    <th class="text-info">Cambia Horas</th>
                    <th class="text-danger">Salientes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conteo_departamental as $depto_nombre => $datos): ?>
                    <tr class="depto-row" data-depto="<?= htmlspecialchars($depto_nombre); ?>">
                        <td><?= htmlspecialchars($datos['facultad_nombre']); ?></td>
                        <td>
                            <span class="depto-name"><?= htmlspecialchars($depto_nombre); ?></span>
                        </td>
                        <td><?= $datos['TOTAL_P1']; ?></td>
                        <td><?= $datos['TOTAL_P2']; ?></td>
                        <td class="text-success fw-bold"><?= $datos['NUEVO']; ?></td>
                        <td class="text-primary"><?= $datos['CONTINÚA']; ?></td>
                        <td class="text-warning fw-bold"><?= $datos['CAMBIA VINCULACIÓN']; ?></td>
                        <td style="color:#6f42c1;" class="fw-bold"><?= $datos['CAMBIA DEDICACIÓN']; ?></td>
                        <td class="text-info fw-bold"><?= $datos['CAMBIA HORAS']; ?></td>
                        <td class="text-danger fw-bold"><?= $datos['DEJA DE VINCULARSE']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<hr>
       <!-- Modal Top 10 -->
<div class="modal fade" id="top10Modal" tabindex="-1" aria-labelledby="top10ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="top10ModalLabel">
                    <i class="fas fa-trophy me-2"></i> Rankings por Departamento - Top 10
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Top Nuevos -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0"><i class="fas fa-user-plus me-1"></i> Top Nuevos</h6>
                                <span class="badge bg-light text-dark"><?= $periodo_actual ?></span>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTopNuevos" height="300"></canvas>
                            </div>
                            <div class="card-footer bg-light">
                                <small class="text-muted">Departamentos con más docentes nuevos en <?= $periodo_actual ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Top Salientes -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0"><i class="fas fa-user-minus me-1"></i> Top Salientes</h6>
                                <span class="badge bg-light text-dark"><?= $periodo_anterior ?> → <?= $periodo_actual ?></span>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTopSalientes" height="300"></canvas>
                            </div>
                            <div class="card-footer bg-light">
                                <small class="text-muted">Departamentos con más docentes que dejaron de vincularse</small>
                            </div>
                        </div>
                    </div>

                    <!-- Top Cambios -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0"><i class="fas fa-exchange-alt me-1"></i> Top Cambios</h6>
                                <span class="badge bg-light text-dark">Total cambios</span>
                            </div>
                            <div class="card-body">
                                <canvas id="chartTopCambios" height="300"></canvas>
                            </div>
                            <div class="card-footer bg-light">
                                <small class="text-muted">Departamentos con más cambios (Vinculación + Dedicação + Horas)</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen estadístico -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-chart-pie me-2"></i>Resumen Estadístico</h6>
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <span class="h4 text-primary"><?= array_sum(array_column($conteo_departamental, 'NUEVO')) ?></span>
                                        <br><small class="text-muted">Total Nuevos (con movilidad)</small>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="h4 text-danger"><?= array_sum(array_column($conteo_departamental, 'DEJA DE VINCULARSE')) ?></span>
                                        <br><small class="text-muted">Total Salientes (con movilidad)</small>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="h4 text-warning"><?= array_sum(array_column($conteo_departamental, 'CAMBIA VINCULACIÓN')) + array_sum(array_column($conteo_departamental, 'CAMBIA DEDICACIÓN')) + array_sum(array_column($conteo_departamental, 'CAMBIA HORAS')) ?></span>
                                        <br><small class="text-muted">Total Cambios</small>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="h4 text-success"><?= count($conteo_departamental) ?></span>
                                        <br><small class="text-muted">Departamentos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="exportAllCharts">
                    <i class="fas fa-download me-1"></i> Exportar Gráficos
                </button>
            </div>
        </div>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mt-5 mb-3">
    <div class="d-flex align-items-center">
        <i class="fas fa-list text-unicauca-azul fa-2x me-3"></i>
        <div>
            <h4 class="mb-0">Lista Detallada por Clasificación</h4>
            <span id="currentDepto" class="text-muted small"></span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="export_detallado.php?p2=<?= urlencode($periodo_actual) ?>&p1=<?= urlencode($periodo_anterior) ?>" 
           class="btn btn-success btn-sm" 
           title="Exportar tabla completa con histórico a Excel">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
        <button class="btn btn-sm btn-outline-secondary" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#collapseDetallada" 
                aria-expanded="false" 
                aria-controls="collapseDetallada"
                title="Expandir/Contraer tabla">
            <i class="fas fa-expand-alt icon-toggle"></i>
        </button>
    </div>
</div>

<div class="collapse" id="collapseDetallada">
    <div class="table-responsive">
        <table id="tablaComparativoGeneral" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead class="table-secondary">
                <tr>
                    <th>Cédula</th>
                    <th>Nombre Docente</th>
                    <th>Departamento</th>
                    <th>Detalle <?= htmlspecialchars($periodo_actual); ?></th> 
                    <th>Detalle <?= htmlspecialchars($periodo_anterior ?? 'N/A'); ?></th>
                    <th>Clasificación (<?= htmlspecialchars($periodo_actual); ?> vs <?= htmlspecialchars($periodo_anterior ?? 'N/A'); ?>)</th>
                    <?php if (!empty($todos_semestres)): ?>
                        <?php foreach ($todos_semestres as $semestre): ?>
                            <th class="columna-historico"><?= htmlspecialchars($semestre); ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docentes_comparativo as $docente): 
                    $cedula = $docente['cedula'];
                    $historico_docente = $historico_docentes[$cedula] ?? [];
                    
                    $row_class = '';
                    switch ($docente['clasificacion']) {
                        case 'NUEVO': $row_class = 'table-success'; break;
                        case 'DEJA DE VINCULARSE': $row_class = 'table-danger'; break;
                        case 'CONTINÚA': $row_class = 'table-primary'; break;
                        case 'CAMBIA VINCULACIÓN': $row_class = 'table-warning'; break;
                        case 'CAMBIA DEDICACIÓN': $row_class = 'table-purple'; break; 
                        case 'CAMBIA HORAS': $row_class = 'table-info'; break;
                    }
                    
                    $detalle_p2 = formatearDetalleUnificado($docente['tipo_docente_p2'], $docente['dedicacion_p2'], $docente['horas_p2']);
                    $detalle_p1 = formatearDetalleUnificado($docente['tipo_docente_p1'], $docente['dedicacion_p1'], $docente['horas_p1']);
                    
                    $detalle_cambio = '';
                    $icon = '';
                    $tipo_docente_p1_display = htmlspecialchars($docente['tipo_docente_p1'] ?? '');
                    $tipo_docente_p2_display = htmlspecialchars($docente['tipo_docente_p2'] ?? '');
                    
                    switch ($docente['clasificacion']) {
                        case 'CAMBIA VINCULACIÓN':
                            $detalle_cambio = "<br><small class='text-muted'>Tipo: {$tipo_docente_p1_display} → {$tipo_docente_p2_display}</small>";
                            break;
                        case 'CAMBIA DEDICACIÓN':
                            $ded_p1_upper = strtoupper($docente['dedicacion_p1'] ?? '');
                            $ded_p2_upper = strtoupper($docente['dedicacion_p2'] ?? '');

                            if (($ded_p1_upper == 'MEDIO TIEMPO' || $ded_p1_upper == 'MT') && (strpos($ded_p2_upper, 'TIEMPO COMPLETO') !== false || $ded_p2_upper == 'TC')) { $icon = '<span class="text-up arrow-icon">⬆️</span>'; } 
                            elseif ((strpos($ded_p1_upper, 'TIEMPO COMPLETO') !== false || $ded_p1_upper == 'TC') && ($ded_p2_upper == 'MEDIO TIEMPO' || $ded_p2_upper == 'MT')) { $icon = '<span class="text-down arrow-icon">⬇️</span>'; }
                            $detalle_cambio = "<br><small class='text-muted'>Ded.: {$docente['dedicacion_p1']} → {$docente['dedicacion_p2']}</small>";
                            break;
                        case 'CAMBIA HORAS':
                            $horas_p1 = (float)($docente['horas_p1'] ?? 0);
                            $horas_p2 = (float)($docente['horas_p2'] ?? 0);
                            
                            if ($horas_p2 > $horas_p1) { $icon = '<span class="text-up arrow-icon">⬆️</span>'; } 
                            elseif ($horas_p2 < $horas_p1) { $icon = '<span class="text-down arrow-icon">⬇️</span>'; }
                            $detalle_cambio = "<br><small class='text-muted'>Horas: {$horas_p1} → {$horas_p2}</small>";
                            break;
                    }
                ?>
                    <tr class="<?= $row_class; ?>" data-depto="<?= htmlspecialchars($docente['departamento_nombre'] ?? ''); ?>">
                        <td>
                            <a href="#" class="cedula-link" 
                               data-cedula="<?= htmlspecialchars($docente['cedula']); ?>" 
                               data-nombre="<?= htmlspecialchars($docente['nombre_completo']); ?>">
                                <?= htmlspecialchars($docente['cedula']); ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($docente['nombre_completo'] ?? '[Nombre no encontrado]'); ?></td> 
                        <td><?= htmlspecialchars($docente['departamento_nombre'] ?? '[Depto no encontrado]'); ?></td>
                        <td><?= $detalle_p2; ?></td>
                        <td><?= $detalle_p1; ?></td>
                        <td>
                            <strong><?= htmlspecialchars($docente['clasificacion']); ?></strong>
                            <?= $icon; ?>
                            <?= $detalle_cambio; ?>
                        </td>
                        <?php if (!empty($todos_semestres)): ?>
                            <?php foreach ($todos_semestres as $semestre): 
                                $estado = $historico_docente[$semestre] ?? 'NO VINCULADO';
                                $badge_class = $estado != 'NO VINCULADO' ? 'badge bg-primary badge-historico' : 'badge bg-secondary badge-historico';
                            ?>
                                <td class="columna-historico">
                                    <span class="<?= $badge_class; ?>"><?= htmlspecialchars($estado); ?></span>
                                </td>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<hr>
    
    <h4 class="mt-5 mb-3 d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-history text-unicauca-azul"></i> 
            <span id="continuidadTitle">Docentes con Historial de Continuidad Histórica (Global)</span> 
        </span>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContinuidad" aria-expanded="false" aria-controls="collapseContinuidad">
            <i class="fas fa-expand-alt icon-toggle"></i> 
        </button>
    </h4>
    <div class="collapse" id="collapseContinuidad">
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table id="tablaContinuidad" class="table table-striped table-bordered table-hover" style="width:100%">
                        <thead class="table-header-custom">
                            <tr>
                                <th>#</th>
                                <th>Nombre Docente</th>
                                <th>Cédula</th>
                                <th>Períodos Totales Vinculado</th>
                                <th>Puntaje de Continuidad</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Cargando datos de continuidad...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- NUEVA TABLA: MATRIZ FACULTAD vs TIPO DE NÓMINA -->
    <hr>
    <h4 class="mt-5 mb-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-th text-unicauca-azul"></i> Matriz Facultad vs Tipo de Nómina (Nuevos y Salientes)</span>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMatriz" aria-expanded="false" aria-controls="collapseMatriz">
            <i class="fas fa-expand-alt icon-toggle"></i> 
        </button>
    </h4>
    
    <div class="collapse show" id="collapseMatriz">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle"></i> Esta matriz muestra la distribución de docentes <strong>NUEVOS</strong> (ingresan en <?= $periodo_actual ?> y no estaban en <?= $periodo_anterior ?>) y <strong>SALIENTES</strong> (estaban en <?= $periodo_anterior ?> y no están en <?= $periodo_actual ?>) por facultad y tipo de nómina.
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="tablaMatrizFacultad">
                <thead class="matrix-header">
                    <tr>
                        <th rowspan="2" class="text-center align-middle matrix-facultad-header">Facultad</th>
                        <th colspan="3" class="text-center">Docentes Ocasionales</th>
                        <th colspan="3" class="text-center">Docentes de Cátedra</th>
                        <th colspan="3" class="text-center">TOTAL</th>
                    </tr>
                    <tr>
                        <!-- Ocasionales -->
                        <th class="text-center matrix-cell-nuevo">Nuevos</th>
                        <th class="text-center matrix-cell-saliente">Salientes</th>
                        <th class="text-center" style="background-color: #e3f2fd !important;">Balance</th> <!-- Azul claro -->

                        <!-- Cátedra -->
                        <th class="text-center matrix-cell-nuevo">Nuevos</th>
                        <th class="text-center matrix-cell-saliente">Salientes</th>
                        <th class="text-center" style="background-color: #e3f2fd !important;">Balance</th> <!-- Azul claro -->

                        <!-- TOTAL -->
                        <th class="text-center matrix-cell-nuevo">Nuevos</th>
                        <th class="text-center matrix-cell-saliente">Salientes</th>
                        <th class="text-center" style="background-color: #e3f2fd !important;">Balance</th> <!-- Azul claro -->
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_general = [
                        'Ocasional' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0],
                        'Catedra' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0],
                        'TOTAL' => ['NUEVO' => 0, 'SALIENTE' => 0, 'TOTAL' => 0]
                    ];
                    
                    foreach ($matriz_facultad_tipo as $facultad => $datos): 
                        // Sumar a totales generales
                        $total_general['Ocasional']['NUEVO'] += $datos['Ocasional']['NUEVO'];
                        $total_general['Ocasional']['SALIENTE'] += $datos['Ocasional']['SALIENTE'];
                        $total_general['Ocasional']['TOTAL'] += $datos['Ocasional']['TOTAL'];
                        
                        $total_general['Catedra']['NUEVO'] += $datos['Catedra']['NUEVO'];
                        $total_general['Catedra']['SALIENTE'] += $datos['Catedra']['SALIENTE'];
                        $total_general['Catedra']['TOTAL'] += $datos['Catedra']['TOTAL'];
                        
                        $total_general['TOTAL']['NUEVO'] += $datos['TOTAL']['NUEVO'];
                        $total_general['TOTAL']['SALIENTE'] += $datos['TOTAL']['SALIENTE'];
                        $total_general['TOTAL']['TOTAL'] += $datos['TOTAL']['TOTAL'];
                    ?>
                    <tr>
                        <td class="fw-bold matrix-facultad-header"><?= htmlspecialchars($facultad); ?></td>

                        <!-- Ocasionales -->
                        <td class="text-center fw-bold matrix-cell-nuevo"><?= $datos['Ocasional']['NUEVO']; ?></td>
                        <td class="text-center fw-bold matrix-cell-saliente"><?= $datos['Ocasional']['SALIENTE']; ?></td>
                        <td class="text-center fw-bold" style="background-color: <?= ($datos['Ocasional']['NUEVO'] - $datos['Ocasional']['SALIENTE']) >= 0 ? '#d4edda' : '#f8d7da' ?>;">
                            <?= $datos['Ocasional']['NUEVO'] - $datos['Ocasional']['SALIENTE']; ?>
                        </td>

                        <!-- Cátedra -->
                        <td class="text-center fw-bold matrix-cell-nuevo"><?= $datos['Catedra']['NUEVO']; ?></td>
                        <td class="text-center fw-bold matrix-cell-saliente"><?= $datos['Catedra']['SALIENTE']; ?></td>
                        <td class="text-center fw-bold" style="background-color: <?= ($datos['Catedra']['NUEVO'] - $datos['Catedra']['SALIENTE']) >= 0 ? '#d4edda' : '#f8d7da' ?>;">
                            <?= $datos['Catedra']['NUEVO'] - $datos['Catedra']['SALIENTE']; ?>
                        </td>

                        <!-- TOTAL -->
                        <td class="text-center fw-bold matrix-cell-nuevo"><?= $datos['TOTAL']['NUEVO']; ?></td>
                        <td class="text-center fw-bold matrix-cell-saliente"><?= $datos['TOTAL']['SALIENTE']; ?></td>
                        <td class="text-center fw-bold" style="background-color: <?= ($datos['TOTAL']['NUEVO'] - $datos['TOTAL']['SALIENTE']) >= 0 ? '#d4edda' : '#f8d7da' ?>;">
                            <?= $datos['TOTAL']['NUEVO'] - $datos['TOTAL']['SALIENTE']; ?>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                    
                    <!-- Fila de TOTALES GENERALES -->
                    <tr class="table-active">
                        <td class="fw-bold bg-dark text-white">TOTAL GENERAL</td>

                        <!-- Ocasionales -->
                        <td class="text-center fw-bold bg-success text-white"><?= $total_general['Ocasional']['NUEVO']; ?></td>
                        <td class="text-center fw-bold bg-danger text-white"><?= $total_general['Ocasional']['SALIENTE']; ?></td>
                        <td class="text-center fw-bold <?= ($total_general['Ocasional']['NUEVO'] - $total_general['Ocasional']['SALIENTE']) >= 0 ? 'bg-success' : 'bg-danger' ?> text-white">
                            <?= $total_general['Ocasional']['NUEVO'] - $total_general['Ocasional']['SALIENTE']; ?>
                        </td>

                        <!-- Cátedra -->
                        <td class="text-center fw-bold bg-success text-white"><?= $total_general['Catedra']['NUEVO']; ?></td>
                        <td class="text-center fw-bold bg-danger text-white"><?= $total_general['Catedra']['SALIENTE']; ?></td>
                        <td class="text-center fw-bold <?= ($total_general['Catedra']['NUEVO'] - $total_general['Catedra']['SALIENTE']) >= 0 ? 'bg-success' : 'bg-danger' ?> text-white">
                            <?= $total_general['Catedra']['NUEVO'] - $total_general['Catedra']['SALIENTE']; ?>
                        </td>

                        <!-- TOTAL -->
                        <td class="text-center fw-bold bg-success text-white"><?= $total_general['TOTAL']['NUEVO']; ?></td>
                        <td class="text-center fw-bold bg-danger text-white"><?= $total_general['TOTAL']['SALIENTE']; ?></td>
                        <td class="text-center fw-bold <?= ($total_general['TOTAL']['NUEVO'] - $total_general['TOTAL']['SALIENTE']) >= 0 ? 'bg-success' : 'bg-danger' ?> text-white">
                            <?= $total_general['TOTAL']['NUEVO'] - $total_general['TOTAL']['SALIENTE']; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Balance Ocasionales</h6>
                        <div class="row">
                            <div class="col-4">
                                <span class="badge bg-success"><?= $total_general['Ocasional']['NUEVO']; ?></span><br>
                                <small>Nuevos</small>
                            </div>
                            <div class="col-4">
                                <span class="badge bg-danger"><?= $total_general['Ocasional']['SALIENTE']; ?></span><br>
                                <small>Salientes</small>
                            </div>
                            <div class="col-4">
                                <span class="badge <?= ($total_general['Ocasional']['NUEVO'] - $total_general['Ocasional']['SALIENTE']) >= 0 ? 'bg-primary' : 'bg-warning' ?>">
                                    <?= $total_general['Ocasional']['NUEVO'] - $total_general['Ocasional']['SALIENTE']; ?>
                                </span><br>
                                <small>Balance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Balance Cátedra</h6>
                        <div class="row">
                            <div class="col-4">
                                <span class="badge bg-success"><?= $total_general['Catedra']['NUEVO']; ?></span><br>
                                <small>Nuevos</small>
                            </div>
                            <div class="col-4">
                                <span class="badge bg-danger"><?= $total_general['Catedra']['SALIENTE']; ?></span><br>
                                <small>Salientes</small>
                            </div>
                            <div class="col-4">
                                <span class="badge <?= ($total_general['Catedra']['NUEVO'] - $total_general['Catedra']['SALIENTE']) >= 0 ? 'bg-primary' : 'bg-warning' ?>">
                                    <?= $total_general['Catedra']['NUEVO'] - $total_general['Catedra']['SALIENTE']; ?>
                                </span><br>
                                <small>Balance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="card-title">Balance Neto Total</h6>
                        <div class="display-4 fw-bold <?= (($total_general['TOTAL']['NUEVO'] - $total_general['TOTAL']['SALIENTE']) >= 0) ? 'text-success' : 'text-danger'; ?>">
                            <?= $total_general['TOTAL']['NUEVO'] - $total_general['TOTAL']['SALIENTE']; ?>
                        </div>
                        <small class="text-muted">Nuevos - Salientes</small>
                        <div class="mt-2">
                            <span class="badge bg-success">+<?= $total_general['TOTAL']['NUEVO']; ?> Nuevos</span>
                            <span class="badge bg-danger ms-1">-<?= $total_general['TOTAL']['SALIENTE']; ?> Salientes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

<script>
// --- DATOS GLOBALES DE CONTINUIDAD (TODOS LOS DOCENTES) ---
var FULL_CONTINUIDAD_DATA = <?= json_encode($full_continuidad_data); ?>;
// ---------------------------------------------------------

// Nueva función para actualizar el ícono después de que el colapso se completa.
function updateCollapseIcon(targetId, isShowing) {
    const icon = $(`[data-bs-target="#${targetId}"]`).find('.icon-toggle');
    if (isShowing) {
        icon.removeClass('fa-expand-alt').addClass('fa-compress-alt');
    } else {
        icon.removeClass('fa-compress-alt').addClass('fa-expand-alt');
    }
}

/**
 * Función para obtener el tipo de continuidad para el ordenamiento
 */
function getContinuityType(docente) {
    if (parseFloat(docente.ocasional_score) > 0) return 1; // Ocasional
    if (parseFloat(docente.catedra_hours) > 0) return 2;   // Catedra
    return 3; // Otro/Ninguno
}

/**
 * Función que realiza el filtrado, ordenamiento y renderizado de la tabla de Continuidad.
 */
function renderTopContinuity(deptoFilter) {
    let filteredData = FULL_CONTINUIDAD_DATA;
    
    let title = 'Docentes con Historial de Continuidad'; 
    let currentDepto = '';

    if (deptoFilter) {
        currentDepto = deptoFilter;
        title += ' (Filtrado por ' + deptoFilter + ')';
        
        // CORRECCIÓN DEL FILTRADO: Solo incluir aquellos vinculados en P2 y cuyo departamento P2 es el seleccionado.
        filteredData = FULL_CONTINUIDAD_DATA.filter(docente => 
            parseInt(docente.en_p2) === 1 && docente.departamento_p2 === deptoFilter
        );
    } else {
        title += ' (Global)';
    }

    // 1. Destruir DataTable si existe
    if ($.fn.DataTable.isDataTable('#tablaContinuidad')) {
        $('#tablaContinuidad').DataTable().destroy();
    }
    
    // 2. Ordenamiento complejo (Periodos, Tipo (Oc. > Cat.), Puntaje)
    filteredData.sort((a, b) => {
        const periodosA = b.periodos_totales - a.periodos_totales;
        if (periodosA !== 0) return periodosA;

        const typeA = getContinuityType(a);
        const typeB = getContinuityType(b);
        const typeSort = typeA - typeB; 
        if (typeSort !== 0) return typeSort;

        if (typeA === 1) { // Ocasional
            return parseFloat(b.ocasional_score) - parseFloat(a.ocasional_score);
        } else if (typeA === 2) { // Catedra
            return parseFloat(b.catedra_hours) - parseFloat(a.catedra_hours);
        }
        
        return a.nombre_completo.localeCompare(b.nombre_completo);
    });
    // ---------------------------------------------------------

    let tbodyHtml = '';
    if (filteredData.length === 0) {
        tbodyHtml = `<tr class="odd"><td colspan="5" class="dataTables_empty text-center text-muted">No se encontraron docentes con historial de vinculación para ${currentDepto || 'el criterio seleccionado'}.</td></tr>`;
    } else {
        filteredData.forEach((docente, index) => { 
            const nombre = docente.nombre_completo || 'N/A';
            const cedula = docente.cedula || 'N/A';
            const periodos = docente.periodos_totales || 0;
            const ocasionalScore = parseFloat(docente.ocasional_score) || 0;
            const catedraHours = parseFloat(docente.catedra_hours) || 0;
            const enP2 = parseInt(docente.en_p2) || 0; 
            
            // LÓGICA DE COLORACIÓN
            const deptoP2 = docente.departamento_p2;       
            const isDeptoFilterActive = !!deptoFilter;     
            
            let nombreHtml = nombre;
            let styleColor = '';
            
            if (enP2 === 0) {
                // 1. Caso: Docente NO vinculado en P2 -> ROJO (Saliente)
                styleColor = 'red'; 
            } else if (isDeptoFilterActive && deptoP2 && deptoP2 !== deptoFilter) {
                // 2. Caso: Docente SÍ vinculado, hay filtro, y el departamento es diferente -> MORADO
                styleColor = '#6f42c1'; 
            }

            if (styleColor) {
                nombreHtml = `<span style="color: ${styleColor}; font-weight: bold;">${nombre}</span>`;
            }
            // FIN LÓGICA DE COLORACIÓN

            let metricHtml = '';
            
            if (ocasionalScore > 0) {
                metricHtml = `<span class="badge bg-primary">Ocasional: ${ocasionalScore} Ptos</span>`;
            } else if (catedraHours > 0) {
                metricHtml = `<span class="badge bg-warning text-dark">Cátedra: ${catedraHours} Hrs</span>`;
            } else {
                metricHtml = '<span class="badge bg-secondary">N/A</span>';
            }
            
            tbodyHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${nombreHtml}</td> 
                    <td>
                        <a href="#" class="cedula-link" data-cedula="${cedula}" data-nombre="${nombre}">
                            ${cedula}
                        </a>
                    </td>
                    <td class="fw-bold text-center">
                        <span class="badge bg-success rounded-pill p-2">${periodos}</span>
                    </td>
                    <td class="text-center">${metricHtml}</td>
                </tr>
            `;
        });
    }

    $('#tablaContinuidad tbody').html(tbodyHtml);
    $('#continuidadTitle').text(title);
    
    // 3. Re-inicializar DataTable
    $('#tablaContinuidad').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "paging": true,
        "pageLength": 10,
        "lengthChange": true, 
        "ordering": true,
        "info": true, 
        "searching": true,
        "order": [[3, "desc"], [4, "desc"]], 
        "dom": 'lfrtip' 
    });
}

/**
 * Función que maneja la visualización de la carga y llama a la renderización.
 */
function loadAndRenderContinuity(deptoFilter) {
    // 1. Mostrar mensaje de carga profesional
    const loadingHtml = `
        <tr>
            <td colspan="5" class="text-center">
                <div class="d-flex align-items-center justify-content-center p-4 text-primary">
                    <div class="spinner-border me-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <strong>Calculando histórico de continuidad. Por favor, espere...</strong>
                </div>
            </td>
        </tr>
    `;
    $('#tablaContinuidad tbody').html(loadingHtml);
    $('#continuidadTitle').text('Docentes con Historial de Continuidad Histórica (Cargando...)');
    
    // 2. Usar setTimeout con 0ms para permitir que el DOM se actualice con el spinner antes de ejecutar la lógica pesada
    setTimeout(() => {
        renderTopContinuity(deptoFilter);
    }, 0);
}


$(document).ready(function() {
    
    // 1. Inicializar la tabla de Continuidad al cargar la página (Global)
    loadAndRenderContinuity(null); // Usar la nueva función de carga

    // 2. Inicialización de DataTables para la tabla detallada
    var tableDetallada = $('#tablaComparativoGeneral').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "paging": true,
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "Todos"] ],
        "ordering": true,
        "info": true,
        "searching": true,
        "order": [[5, "asc"], [2, "asc"], [1, "asc"]],
        "responsive": true,
"dom": '<"row"<"col-sm-12 col-md-6"lB><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',        // --- Lógica para ocultar columnas de histórico ---
        <?php if (!empty($todos_semestres)): ?>
        "columnDefs": [
            {
                "targets": <?= $historico_indices_js ?>, 
                "visible": false
            }
        ],
        <?php endif; ?>
        // ------------------------------------------------
        "buttons": [
            {
                extend: 'excelHtml5',
                text: 'Descargar Excel Detalle 📋',
                titleAttr: 'Exportar tabla detallada a Excel',
                className: 'btn btn-primary btn-sm',
                filename: 'Comparativo_Docentes_<?= $periodo_actual ?>_vs_<?= $periodo_anterior ?>_<?= date("Y-m-d") ?>'
            }
        ]
    });

    // 3. Inicialización de DataTables para la tabla de resumen departamental
// 3. Inicialización de DataTables para la tabla de resumen departamental
var tableResumen = $('#tablaResumenDepto').DataTable({
    "language": {
        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    },
    "paging": true, 
    "pageLength": 10,
    "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "Todos"] ],
    "ordering": true,
    "info": true,
    "searching": true,
    "order": [[0, "asc"]],
    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"B>><"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    "buttons": [
        {
            extend: 'excelHtml5',
            text: '<i class="fas fa-file-excel me-1"></i> Exportar Excel',
            titleAttr: 'Exportar resumen departamental a Excel',
            className: 'btn btn-success btn-sm',
            filename: 'Resumen_Departamental_<?= $periodo_actual ?>_vs_<?= $periodo_anterior ?>_<?= date("Y-m-d") ?>'
        }
    ]
});

    // 4. Configuración de Eventos de Bootstrap Collapse y DataTables
    
    $('#collapseResumenDepto').on('shown.bs.collapse', function () {
        updateCollapseIcon('collapseResumenDepto', true);
        tableResumen.columns.adjust().draw(); 
    }).on('hidden.bs.collapse', function () {
        updateCollapseIcon('collapseResumenDepto', false);
    });

    $('#collapseDetallada').on('shown.bs.collapse', function () {
        updateCollapseIcon('collapseDetallada', true);
        tableDetallada.columns.adjust().draw(); 
    }).on('hidden.bs.collapse', function () {
        updateCollapseIcon('collapseDetallada', false);
    });

    $('#collapseContinuidad').on('shown.bs.collapse', function () {
        updateCollapseIcon('collapseContinuidad', true);
        $('#tablaContinuidad').DataTable().columns.adjust().draw(); 
    }).on('hidden.bs.collapse', function () {
        updateCollapseIcon('collapseContinuidad', false);
    });

    $('#collapseMatriz').on('shown.bs.collapse', function () {
        updateCollapseIcon('collapseMatriz', true);
    }).on('hidden.bs.collapse', function () {
        updateCollapseIcon('collapseMatriz', false);
    });

    // Variable para almacenar el departamento seleccionado
    var selectedDepto = null;

    // Función para filtrar por departamento al hacer click
    $('.depto-row').on('click', function() {
        var departamento = $(this).data('depto');
        
        $('.depto-row').removeClass('selected');
        
        if (selectedDepto === departamento) {
            // Limpiar filtro
            selectedDepto = null;
            tableDetallada.column(2).search('').draw();
            $('#currentDepto').text('');
            loadAndRenderContinuity(null); // Quitar filtro en Continuidad
        } else {
            // Aplicar filtro
            selectedDepto = departamento;
            $(this).addClass('selected');
            tableDetallada.column(2).search(departamento).draw();
            $('#currentDepto').text('- Departamento: ' + departamento);
            loadAndRenderContinuity(departamento); // Aplicar filtro en Continuidad
        }
    });

    // Botón para limpiar filtros
    $('<button class="btn btn-outline-secondary btn-sm ms-2">Limpiar Filtros</button>')
        .appendTo('#tablaComparativoGeneral_wrapper .dataTables_filter')
        .on('click', function() {
            tableDetallada.search('').columns().search('').draw();
            $('#currentDepto').text('');
            $('.depto-row').removeClass('selected');
            selectedDepto = null;
            loadAndRenderContinuity(null); // Limpiar filtro en Continuidad
        });
        
    // Botón para filtrar por Salientes (DEJA DE VINCULARSE) - Se añadió en una conversación previa
    $('<button class="btn btn-danger btn-sm ms-2" id="filterSalientesBtn" title="Docentes que estaban en P1 y no continúan en P2"><i class="fas fa-user-minus me-1"></i> Salientes</button>')
        .appendTo('#tablaComparativoGeneral_wrapper .dataTables_filter')
        .on('click', function() {
            tableDetallada.column(5).search('DEJA DE VINCULARSE').draw(); // Columna 5 es la de Clasificación
            $('#currentDepto').text('- FILTRO: Salientes');
            $('.depto-row').removeClass('selected');
            selectedDepto = null;
            loadAndRenderContinuity(null); // Muestra la continuidad global sin filtrar por depto
        });
        
    // Botón para filtrar por Nuevos (NUEVO) - Se añadió en una conversación previa
    $('<button class="btn btn-success btn-sm ms-2" id="filterNuevosBtn" title="Docentes que están en P2 y no estaban en P1"><i class="fas fa-user-plus me-1"></i> Nuevos</button>')
        .appendTo('#tablaComparativoGeneral_wrapper .dataTables_filter')
        .on('click', function() {
            tableDetallada.column(5).search('NUEVO').draw(); // Columna 5 es la de Clasificación
            $('#currentDepto').text('- FILTRO: Nuevos');
            $('.depto-row').removeClass('selected');
            selectedDepto = null;
            loadAndRenderContinuity(null); 
        });


    // Manejar clic en cédula para histórico (modal)
    $('#tablaComparativoGeneral tbody, #tablaContinuidad tbody').on('click', '.cedula-link', function(e) {
        e.preventDefault();
        var cedula = $(this).data('cedula');
        var nombre = $(this).data('nombre') || $(this).data('nombre_completo'); 

        $('#docenteNombre').text(nombre + ' (' + cedula + ')');
        $('#historicoContent').html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Consultando histórico...</p></div>');
        
        var historicoModal = new bootstrap.Modal(document.getElementById('historicoDocenteModal'));
        historicoModal.show();
        
        $.ajax({
            url: 'get_historico.php', // Asumiendo que existe un archivo 'get_historico.php'
            type: 'POST',
            dataType: 'html',
            data: { cedula: cedula },
            success: function(response) {
                $('#historicoContent').html(response);
            },
            error: function() {
                $('#historicoContent').html('<div class="alert alert-danger" role="alert">Error al cargar el histórico del docente.</div>');
            }
        });
    });
});
    
    $('#loading-overlay').fadeOut('slow');
    // Variables globales para los gráficos
let chartTopNuevos = null;
let chartTopSalientes = null;
let chartTopCambios = null;

// 5. Gráficos Top por Departamento en Modal
function initTopCharts() {
    console.log('Inicializando gráficos Top 10...');
    
    // Destruir gráficos existentes
    [chartTopNuevos, chartTopSalientes, chartTopCambios].forEach(chart => {
        if (chart) chart.destroy();
    });

    const deptoData = <?= json_encode($conteo_departamental) ?>;
    console.log('Datos disponibles:', deptoData);

    // Verificar que hay datos
    if (Object.keys(deptoData).length === 0) {
        console.error('No hay datos para mostrar en los gráficos');
        return;
    }

    // Preparar datos para los rankings
    function getTop10(data, key) {
        return Object.entries(data)
            .sort(([, a], [, b]) => b[key] - a[key])
            .slice(0, 10)
            .map(([depto, datos]) => ({
                departamento: depto,
                valor: datos[key],
                facultad: datos.facultad_nombre
            }));
    }

    // Calcular cambios totales
    const deptosConCambios = Object.entries(deptoData).map(([depto, datos]) => ({
        departamento: depto,
        valor: datos['CAMBIA VINCULACIÓN'] + datos['CAMBIA DEDICACIÓN'] + datos['CAMBIA HORAS'],
        facultad: datos.facultad_nombre
    })).sort((a, b) => b.valor - a.valor).slice(0, 10);

    const topNuevos = getTop10(deptoData, 'NUEVO');
    const topSalientes = getTop10(deptoData, 'DEJA DE VINCULARSE');
    const topCambios = deptosConCambios;

    console.log('Top Nuevos:', topNuevos);
    console.log('Top Salientes:', topSalientes);
    console.log('Top Cambios:', topCambios);

    // Configuración común
    const commonOptions = {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `Cantidad: ${context.parsed.x}`;
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { stepSize: 1, precision: 0 }
            }
        }
    };

    // Crear gráficos
    try {
        chartTopNuevos = new Chart(document.getElementById('chartTopNuevos'), {
            type: 'bar',
            data: {
                labels: topNuevos.map(item => item.departamento),
                datasets: [{
                    label: 'Nuevos',
                    data: topNuevos.map(item => item.valor),
                    backgroundColor: '#28a745'
                }]
            },
            options: commonOptions
        });

        chartTopSalientes = new Chart(document.getElementById('chartTopSalientes'), {
            type: 'bar',
            data: {
                labels: topSalientes.map(item => item.departamento),
                datasets: [{
                    label: 'Salientes',
                    data: topSalientes.map(item => item.valor),
                    backgroundColor: '#dc3545'
                }]
            },
            options: commonOptions
        });

        chartTopCambios = new Chart(document.getElementById('chartTopCambios'), {
            type: 'bar',
            data: {
                labels: topCambios.map(item => item.departamento),
                datasets: [{
                    label: 'Cambios',
                    data: topCambios.map(item => item.valor),
                    backgroundColor: '#ffc107'
                }]
            },
            options: commonOptions
        });

        console.log('Gráficos inicializados correctamente');
    } catch (error) {
        console.error('Error al crear gráficos:', error);
    }
}

// Evento para abrir el modal
$('#btnTop10').click(function(e) {
    e.preventDefault();
    $('#top10Modal').modal('show');
});

// Evento cuando el modal se muestra
$('#top10Modal').on('shown.bs.modal', function () {
    // Pequeño delay para asegurar que el DOM esté listo
    setTimeout(() => {
        initTopCharts();
    }, 100);
});

// Evento cuando el modal se cierra - limpiar gráficos
$('#top10Modal').on('hidden.bs.modal', function () {
    if (chartTopNuevos) {
        chartTopNuevos.destroy();
        chartTopNuevos = null;
    }
    if (chartTopSalientes) {
        chartTopSalientes.destroy();
        chartTopSalientes = null;
    }
    if (chartTopCambios) {
        chartTopCambios.destroy();
        chartTopCambios = null;
    }
});

// Exportar todos los gráficos
$('#exportAllCharts').click(function() {
    // Crear un canvas combinado
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 1200;
    canvas.height = 1600;
    
    // Fondo blanco
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Título
    ctx.fillStyle = 'black';
    ctx.font = 'bold 24px Arial';
    ctx.fillText('Rankings por Departamento - Top 10', 50, 50);
    ctx.font = '16px Arial';
    ctx.fillText('Período: <?= $periodo_actual ?> vs <?= $periodo_anterior ?>', 50, 80);
    
    // Dibujar cada gráfico
    const charts = [
        { id: 'chartTopNuevos', title: 'Top Nuevos', y: 120 },
        { id: 'chartTopSalientes', title: 'Top Salientes', y: 600 },
        { id: 'chartTopCambios', title: 'Top Cambios', y: 1080 }
    ];
    
    charts.forEach((chartInfo, index) => {
        const chartCanvas = document.getElementById(chartInfo.id);
        ctx.drawImage(chartCanvas, 50, chartInfo.y, 1100, 450);
        
        // Título del gráfico
        ctx.fillStyle = 'black';
        ctx.font = 'bold 18px Arial';
        ctx.fillText(chartInfo.title, 50, chartInfo.y - 10);
    });
    
    // Descargar
    const link = document.createElement('a');
    link.download = `top10_departamentos_<?= $periodo_actual ?>.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
});
</script>
<!-- Librerías para exportación Excel -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<div class="modal fade" id="historicoDocenteModal" tabindex="-1" aria-labelledby="historicoDocenteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="historicoDocenteLabel">Histórico de Vinculación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="lead" id="docenteNombre"></p>
                <hr>
                <div id="historicoContent">
                    <p class="text-center">Cargando histórico...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>