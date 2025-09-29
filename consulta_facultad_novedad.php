<?php
// consulta_vra.php
// ARCHIVO DEDICADO Y CORREGIDO EXCLUSIVAMENTE PARA EL USUARIO TIPO 1 (VICERRECTORÍA)
$active_menu_item = 'novedades';

// --- INCLUDES Y CONFIGURACIÓN INICIAL ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

require('include/headerz.php');
require_once('conn.php');
require('funciones.php');

// --- VARIABLES ESENCIALES ---
$anio_semestre = $_POST['anio_semestre'] ?? $_GET['anio_semestre'] ?? '2025-2';
$_SESSION['anio_semestre'] = $anio_semestre;
$_SESSION['tipo_usuario'] = $tipo_usuario;
// Obtenemos los datos del usuario logueado
$tipo_usuario = null;
if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];
    $stmt_user = $conn->prepare("SELECT Id, fk_fac_user, fk_depto_user, tipo_usuario FROM users WHERE Name = ?");
    $stmt_user->bind_param("s", $nombre_sesion);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $user_row = $result_user->fetch_assoc();
        $tipo_usuario = $user_row['tipo_usuario'];
    }
    $stmt_user->close();
}

// --- VALIDACIÓN DE ACCESO ---
if ($tipo_usuario === null) {
    die('<div class="container mx-auto p-8 text-center"><h1 class="text-2xl font-bold text-red-600">Error: Sesión no iniciada o usuario no encontrado.</h1></div>');
} elseif ($tipo_usuario != 1) {
    die('<div class="container mx-auto p-8 text-center"><h1 class="text-2xl font-bold text-red-600">Error: Acceso denegado.</h1><p class="text-gray-600 mt-2">Esta página es solo para Vicerrectoría Académica.</p></div>');
}

// *** INICIO DE LA MODIFICACIÓN ***
function procesarCambiosVinculacion($solicitudes) {
    $adiciones = [];
    $eliminaciones = [];
    $otras_novedades = [];
    $resultado_final = [];

    // --- PASO 1: Clasificar todas las solicitudes ---
    foreach ($solicitudes as $sol) {
        $cedula = $sol['cedula'];
        $novedad = strtolower($sol['novedad']);

        // Usamos un array para cada cédula para manejar múltiples novedades por persona
        if (!isset($adiciones[$cedula])) $adiciones[$cedula] = [];
        if (!isset($eliminaciones[$cedula])) $eliminaciones[$cedula] = [];

        if ($novedad === 'adicion' || $novedad === 'adicionar') {
            // Se guarda la solicitud de adición usando su ID único como clave
            $adiciones[$cedula][$sol['id_solicitud']] = $sol;
        } elseif ($novedad === 'eliminar') {
            // Se guarda la solicitud de eliminación
            $eliminaciones[$cedula][$sol['id_solicitud']] = $sol;
        } else {
            // El resto (Modificar, etc.) va a otra lista
            $otras_novedades[] = $sol;
        }
    }

    // --- PASO 2: Buscar pares (Adición/Eliminación) que compartan el MISMO OFICIO ---
    foreach ($adiciones as $cedula => $solicitudes_adicion) {
        foreach ($solicitudes_adicion as $id_adicion => $sol_adicion) {
            $oficio_adicion = $sol_adicion['oficio_con_fecha'];
            $par_encontrado = false;

            if (!empty($eliminaciones[$cedula])) {
                foreach ($eliminaciones[$cedula] as $id_eliminacion => $sol_eliminacion) {
                    // ¡LA CLAVE! El par solo es válido si la cédula Y el oficio coinciden
                    if ($sol_eliminacion['oficio_con_fecha'] === $oficio_adicion) {
                        
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

                        // Se marca como encontrado y se elimina de la lista de pendientes
                        $par_encontrado = true;
                        unset($eliminaciones[$cedula][$id_eliminacion]);
                        break; // Salimos del bucle de eliminaciones
                    }
                }
            }
            
            // Si después de buscar no se encontró pareja, se trata como una adición normal
            if (!$par_encontrado) {
                $resultado_final[] = $sol_adicion;
            }
        }
    }

    // --- PASO 3: Añadir las eliminaciones que quedaron sin pareja ---
    foreach ($eliminaciones as $cedula => $solicitudes_eliminacion) {
        foreach ($solicitudes_eliminacion as $sol_eliminacion) {
            $resultado_final[] = $sol_eliminacion;
        }
    }

    // Unimos los cambios procesados con las otras novedades.
    return array_merge($resultado_final, $otras_novedades);
}

// --- LÓGICA PARA VICERRECTORÍA ACADÉMICA (VRA) ---
$sql_vra = "
    SELECT
        f.NOMBREF_FAC AS nombre_facultad,
        d.depto_nom_propio AS nombre_departamento, 
                s.id_solicitud AS solicitud_id, 
        s.*
    FROM solicitudes_working_copy s
    JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO
    JOIN facultad f ON s.facultad_id = f.PK_FAC
    WHERE s.anio_semestre = ?
      AND s.oficio_con_fecha_fac IS NOT NULL 
      AND s.estado_facultad = 'APROBADO'
      AND s.oficio_con_fecha_fac != ''
    ORDER BY f.NOMBREF_FAC ASC, d.depto_nom_propio ASC, s.oficio_con_fecha_fac ASC
";

$stmt_vra = $conn->prepare($sql_vra);
$stmt_vra->bind_param("s", $anio_semestre);
$stmt_vra->execute();
$todas_las_solicitudes_vra_raw = $stmt_vra->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_vra->close();

// 1. Agrupar todas las solicitudes por departamento para poder procesarlas
$solicitudes_por_depto = [];
foreach ($todas_las_solicitudes_vra_raw as $sol) {
    $solicitudes_por_depto[$sol['nombre_facultad']][$sol['nombre_departamento']][] = $sol;
}

// 2. Procesar cada grupo de departamento y reconstruir las estructuras de datos finales
$solicitudes_procesadas_flat = []; // Para el JSON plano que usa el modal
$datos_agrupados_vra = []; // Para el JSON anidado que usa la UI de acordeones

foreach ($solicitudes_por_depto as $nombre_facultad => $departamentos) {
    foreach ($departamentos as $nombre_departamento => $solicitudes_depto) {
        
        // Aplicamos la función de procesamiento aquí
        $solicitudes_procesadas_depto = procesarCambiosVinculacion($solicitudes_depto);

        // Reconstruimos las dos estructuras de datos con la información ya procesada
        foreach ($solicitudes_procesadas_depto as $sol_procesada) {
            $solicitudes_procesadas_flat[] = $sol_procesada;
            
            $oficio = $sol_procesada['oficio_con_fecha_fac'];
            $datos_agrupados_vra[$nombre_facultad][$nombre_departamento][$oficio][] = $sol_procesada;
        }
    }
}

// Se crean los JSON con los datos ya procesados y consolidados
$solicitudes_json = json_encode($solicitudes_procesadas_flat);
$estructura_vra_json = json_encode($datos_agrupados_vra);

// *** FIN DE LA MODIFICACIÓN ***

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Novedades - VRA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
     .rotate-180 { transform: rotate(180deg); }
    .transition-transform { transition: transform 0.3s ease; }

    /* Estilo para el botón de DEPARTAMENTO abierto */
    .active-accordion {
        background-color: #eef2ff; /* Índigo claro */
        border-left-width: 4px;
        border-left-color: #4f46e5;
    }
    .active-accordion span {
        font-weight: bold;
    }

    /* --- NUEVO ESTILO PARA LA FACULTAD con hijos abiertos --- */
    .active-parent-accordion {
        background-color: #f5f3ff; /* Violeta muy, muy claro */
    }
        
    /* Estilo para el contenedor de la FACULTAD que tiene pendientes */
    .has-pending-fac {
        background-color: #fef2f2; /* Un amarillo muy suave (Color de Tailwind: yellow-50) */
        border-left: 4px solid #b91c1c; /* Borde amarillo (yellow-400) */
    }

    /* Estilo para el botón del DEPARTAMENTO que tiene pendientes */
    .has-pending-depto {
        background-color: #fef2f2; /* Un rojo/rosa muy suave (red-50) */
    }
    .has-pending-depto span {
         font-weight: 600; /* semi-bold */
         color: #b91c1c; /* red-700 */
    }
        
        /* --- ESTILO MODERNO PARA LA DATATABLE DE VRA --- */

/* Reseteo general de la tabla para quitar bordes de Bootstrap */
#tablaAprobadosVRA, 
#tablaAprobadosVRA th, 
#tablaAprobadosVRA td {
    border: none !important;
}

/* Estilo para la cabecera (thead) */
#tablaAprobadosVRA thead th {
    background-color: #F9FAFB;  /* Fondo gris muy claro */
    color: #6B7280;             /* Texto gris oscuro */
    font-size: 0.75rem;         /* Letra más pequeña */
    text-transform: uppercase;  /* Mayúsculas */
    letter-spacing: 0.05em;     /* Espacio entre letras */
    font-weight: 600;
    padding: 0.75rem 1rem;      /* Relleno para más aire */
    border-bottom: 2px solid #E5E7EB; /* Borde inferior más grueso */
    text-align: left;
}

/* Estilo para las celdas del cuerpo (tbody) */
/* Estilo para las celdas del cuerpo (tbody) */
#tablaAprobadosVRA tbody td {
    background-color: #FFFFFF;
    /* --- ESTA ES LA LÍNEA QUE CAMBIA --- */
    color: #374151;             /* <-- Un gris oscuro, casi negro, muy elegante */
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #E5E7EB;
    vertical-align: middle;
    font-family: sans-serif;
    font-size: 0.85rem;
}

/* Efecto hover: la fila cambia de color al pasar el ratón */
#tablaAprobadosVRA tbody tr:hover td {
    background-color: #F3F4F6; /* Un gris un poco más oscuro que el de la cabecera */
}

/* Estilo para el checkbox de la cabecera */
#tablaAprobadosVRA thead th input[type="checkbox"] {
    cursor: pointer;
}
    </style>
</head>
<body class="bg-gray-100">
<div id="loadingOverlay" class="fixed inset-0 bg-gray-800 bg-opacity-75 h-full w-full hidden z-50 flex items-center justify-center" style="z-index: 100;">
    <div class="bg-white rounded-lg p-8 flex items-center space-x-4 shadow-xl">
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="loadingMessage" class="text-lg font-semibold text-gray-700">Procesando...</span>
    </div>
</div>
    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Novedades Aprobadas por Facultad (<?php echo htmlspecialchars($anio_semestre); ?>)</h1>
          <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
    <div class="flex justify-between items-center">
        
        <!-- Filtros -->
        <div class="flex items-center space-x-6">
            <span class="font-semibold text-gray-700">Filtrar por estado:</span>
            <div class="flex items-center">
                <input type="radio" id="filtro_todos" name="filtro_estado" value="todos" 
                       class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" checked>
                <label for="filtro_todos" class="ml-2 block text-sm text-gray-900">
                    Mostrar Todos
                </label>
            </div>
            <div class="flex items-center">
                <input type="radio" id="filtro_pendientes" name="filtro_estado" value="pendientes" 
                       class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <label for="filtro_pendientes" class="ml-2 block text-sm text-gray-900">
                    Mostrar solo con trámites pendientes en VRA
                </label>
            </div>
        </div>

        <!-- Botón Exportar -->
        <a href="exportar_excel_novedades.php" 
           class="inline-flex items-center px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-lg shadow hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors no-underline"
           target="_blank">
            <i class="fas fa-file-excel mr-2"></i>
            Exportar a Excel
        </a>

    </div>
</div>

        <div class="space-y-4" id="lista-facultades">
            <?php if (!empty($datos_agrupados_vra)): ?>
                <?php foreach ($datos_agrupados_vra as $nombre_facultad => $departamentos): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden" data-facultad-container="<?= htmlspecialchars($nombre_facultad) ?>">
                        <button class="accordion-facultad w-full text-left py-3 px-6 flex justify-between items-center hover:bg-gray-50 focus:outline-none">
                            <span class="text-2xl font-semibold text-gray-800"><?= htmlspecialchars($nombre_facultad) ?></span>
                            <svg class="w-6 h-6 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="accordion-body-facultad hidden p-2 md:p-6 bg-gray-50 border-t border-gray-200">
                            <div class="space-y-4">
                                <?php foreach ($departamentos as $nombre_departamento => $oficios): ?>
                                    <div class="bg-white rounded-lg shadow-sm overflow-hidden" data-depto-container="<?= htmlspecialchars($nombre_departamento) ?>">
                                        <button 
                                            class="accordion-departamento w-full text-left py-3 px-6 flex justify-between items-center hover:bg-gray-50 focus:outline-none" 
                                            data-facultad="<?= htmlspecialchars($nombre_facultad) ?>" 
                                            data-depto="<?= htmlspecialchars($nombre_departamento) ?>">
                                            <span class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($nombre_departamento) ?></span>
                                            <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                        <div class="accordion-body-departamento hidden p-2 bg-gray-100 border-t border-gray-200">
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 oficios-container">
                                                </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center p-10">No hay novedades aprobadas por las facultades para este periodo.</p>
            <?php endif; ?>
        </div>
        
        
    
    <?php
// --- INICIO: CÓDIGO PARA LA NUEVA DATATABLE DE APROBADOS ---

$sql_aprobados = "
    SELECT 
        s.id_solicitud, f.Nombre_fac_minb, d.depto_nom_propio,
        s.tipo_docente, s.cedula, s.nombre, s.novedad,
        
        -- === CAMBIO CLAVE: Tomamos los puntos de la tabla 'solicitudes' ===
        s_orig.puntos, 
        
        s.tipo_reemplazo, s.oficio_fac, s.fecha_oficio_fac, s.oficio_con_fecha_fac,
        s.tipo_dedicacion, s.tipo_dedicacion_r,
        s.horas, s.horas_r,
        s.tipo_dedicacion_inicial, s.tipo_dedicacion_r_inicial,
        s.horas_inicial, s.horas_r_inicial
    FROM 
        solicitudes_working_copy s
    JOIN 
        deparmanentos d ON d.PK_DEPTO = s.departamento_id
    JOIN 
        facultad f ON f.PK_FAC = d.FK_FAC
    -- === CAMBIO CLAVE: Unimos con la tabla 'solicitudes' para obtener los puntos correctos ===
    LEFT JOIN 
        solicitudes s_orig ON s_orig.id_novedad = s.id_solicitud
    WHERE 
        s.estado_vra = 'APROBADO' 
        AND (s.archivado IS NULL OR s.archivado = 0)
    ORDER BY
        s.novedad, f.Nombre_fac_minb, d.depto_nom_propio, s.nombre;
";

$resultado_aprobados = $conn->query($sql_aprobados);

$datos_para_tabla = [];

if ($resultado_aprobados && $resultado_aprobados->num_rows > 0) {
    // PASO 1: Agrupar todas las solicitudes por cédula
    $solicitudes_agrupadas = [];
    while ($fila = $resultado_aprobados->fetch_assoc()) {
        $cedula = $fila['cedula'];
        $oficioKey = $fila['oficio_con_fecha_fac'];  // identificador único por oficio
        $novedad = strtolower($fila['novedad']);
        $id = $fila['id_solicitud']; // siempre único

        $solicitudes_agrupadas[$cedula][$oficioKey][$novedad][$id] = $fila;
    }

   // PASO 2: Procesar los grupos con una lógica de un solo paso (VERSIÓN FINAL)
foreach ($solicitudes_agrupadas as $cedula => $oficios) {
    foreach ($oficios as $oficioKey => $novedades) {

        // --- CASO 1: Es un "Cambio de Vinculación" completo (adicionar + eliminar) ---
        if (!empty($novedades['adicionar']) && !empty($novedades['eliminar'])) {
            // Asumimos un par 1 a 1 por oficio y cédula
            $fila_final = reset($novedades['adicionar']);
            $fila_inicial = reset($novedades['eliminar']);

            $fila_final['novedad_display'] = 'Modificar (Vinculación)';

            // --- Lógica para calcular vinculaciones (sin cambios) ---
            $vinculacion_inicial = '';
            if ($fila_inicial['tipo_docente'] === 'Ocasional') {
                $vinculacion_inicial = !empty($fila_inicial['tipo_dedicacion']) ? $fila_inicial['tipo_dedicacion'] : ($fila_inicial['tipo_dedicacion_r'] ?? '');
            } elseif ($fila_inicial['tipo_docente'] === 'Catedra') {
                $total_horas = floatval($fila_inicial['horas']) + floatval($fila_inicial['horas_r']);
                $vinculacion_inicial = $total_horas . ' hrs';
            }
            $fila_final['dedicacion_unificada_inicial'] = $vinculacion_inicial;
            
            $vinculacion_final = '';
            if ($fila_final['tipo_docente'] === 'Ocasional') {
                $vinculacion_final = !empty($fila_final['tipo_dedicacion']) ? $fila_final['tipo_dedicacion'] : ($fila_final['tipo_dedicacion_r'] ?? '');
            } elseif ($fila_final['tipo_docente'] === 'Catedra') {
                $total_horas = floatval($fila_final['horas']) + floatval($fila_final['horas_r']);
                $vinculacion_final = $total_horas . ' hrs';
            }
            $fila_final['dedicacion_unificada_final'] = $vinculacion_final;

            $datos_para_tabla[] = $fila_final;
        
        } else {
            // --- CASO 2: No es un dúo, procesar cada novedad individualmente ---
            foreach ($novedades as $tipoNovedad => $grupo) {
                foreach ($grupo as $fila) {
                    
                    switch ($tipoNovedad) {
                        case 'modificar':
                            $fila['novedad_display'] = 'Modificar (Dedicación)';
                            break;
                        case 'eliminar':
                            $fila['novedad_display'] = 'Liberar';
                            break;
                        case 'adicionar':
                            $fila['novedad_display'] = 'Vincular';
                            break;
                        default:
                            $fila['novedad_display'] = $fila['novedad'];
                    }

                    // --- Lógica para calcular vinculaciones (sin cambios) ---
                    $fila['dedicacion_unificada_final'] = '';
                    if ($fila['tipo_docente'] === 'Ocasional') {
                        $fila['dedicacion_unificada_final'] = !empty($fila['tipo_dedicacion']) ? $fila['tipo_dedicacion'] : ($fila['tipo_dedicacion_r'] ?? '');
                    } elseif ($fila['tipo_docente'] === 'Catedra') {
                        $total_horas = floatval($fila['horas']) + floatval($fila['horas_r']);
                        $fila['dedicacion_unificada_final'] = $total_horas . ' hrs';
                    }

                    $fila['dedicacion_unificada_inicial'] = '';
                    if ($tipoNovedad === 'modificar') {
                        if ($fila['tipo_docente'] === 'Ocasional') {
                            $fila['dedicacion_unificada_inicial'] = !empty($fila['tipo_dedicacion_inicial']) ? $fila['tipo_dedicacion_inicial'] : ($fila['tipo_dedicacion_r_inicial'] ?? '');
                        } elseif ($fila['tipo_docente'] === 'Catedra') {
                            $total_horas_inicial = floatval($fila['horas_inicial']) + floatval($fila['horas_r_inicial']);
                            $fila['dedicacion_unificada_inicial'] = $total_horas_inicial . ' hrs';
                        }
                    }

                    $datos_para_tabla[] = $fila;
                }
            }
        }
    }
}
    
    // --- INICIO: NUEVO BLOQUE DE ORDENAMIENTO PERSONALIZADO ---
    usort($datos_para_tabla, function($a, $b) {
        // 1. Asignamos una prioridad numérica a cada tipo de novedad
        $prioridad = [
            'Modificar (Vinculación)' => 1,
            'Modificar (Dedicación)' => 1,
            'Vincular' => 2,
            'Liberar'  => 3
        ];
        
        // Obtenemos la prioridad para A y B (si no existe, le damos una prioridad baja)
        $prioridadA = $prioridad[$a['novedad_display']] ?? 99;
        $prioridadB = $prioridad[$b['novedad_display']] ?? 99;

        // 2. Comparamos primero por la prioridad de la novedad
        if ($prioridadA !== $prioridadB) {
            return $prioridadA <=> $prioridadB; // El operador <=> hace la comparación por nosotros
        }
        
        // 3. Si la prioridad es la misma, usamos los criterios originales para desempatar
        // (Facultad -> Departamento -> Nombre del profesor)
        $compFacultad = strcmp($a['Nombre_fac_minb'], $b['Nombre_fac_minb']);
        if ($compFacultad !== 0) {
            return $compFacultad;
        }

        $compDepto = strcmp($a['depto_nom_propio'], $b['depto_nom_propio']);
        if ($compDepto !== 0) {
            return $compDepto;
        }

        return strcmp($a['nombre'], $b['nombre']);
    });
    // --- FIN: NUEVO BLOQUE DE ORDENAMIENTO ---
}
$conn->close();


?>
    
<div class="bg-white rounded-lg shadow-md overflow-hidden mt-5">
   <?php
// --- LÓGICA PARA BOTÓN DINÁMICO (VERSIÓN CORREGIDA) ---
$tieneDatos = !empty($datos_para_tabla);

if (!$tieneDatos) {
    // Estado normal: Hay datos, clases azules, botón 100% funcional.
    $headerClasses = 'bg-blue-50 hover:bg-blue-100 text-blue-800';
    $iconClasses   = 'fa-check-circle text-blue-800';
    $arrowClasses  = 'text-blue-800';
    $extraAttrs    = ''; 
} else {
    // Estado vacío: Clases rosa, PERO EL BOTÓN SIGUE SIENDO FUNCIONAL para mostrar el mensaje.
    // 1. Eliminamos 'cursor-not-allowed' y añadimos un hover rosado.
    $headerClasses = 'bg-pink-50 hover:bg-pink-100 text-pink-800';
    $iconClasses   = 'fa-info-circle text-pink-800';
    $arrowClasses  = 'text-pink-800'; // Hacemos que la flecha coincida con el texto.
    // 2. ELIMINAMOS el atributo 'disabled'.
    $extraAttrs    = 'title="No hay solicitudes pendientes. Haga clic para ver detalles."'; 
}
?>
 <button class="accordion-header-vra w-full text-left py-3 px-6 flex justify-between items-center focus:outline-none transition-colors duration-200 <?= $headerClasses ?>" <?= $extraAttrs ?>>
    <span class="text-xl font-semibold">
        <i class="fas <?= $iconClasses ?> me-3"></i>
        Solicitar Trámite de vinculación en RRHH
    </span>
    <svg class="w-6 h-6 transform transition-transform <?= $arrowClasses ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
</button>

<div class="accordion-body-vra hidden p-4 border-t border-gray-200">

    <?php if ($tieneDatos): ?>
        <table id="tablaAprobadosVRA" class="table" style="width:100%">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Novedad</th>
                    <th>Oficio</th>
                    <th>Departamento</th>
                    <th>Tipo</th>
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Vinc. Inicial</th> <th>Vinc. Final</th> <th>Puntos</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos_para_tabla as $solicitud): ?>
                    <?php
                        // --- Preparamos los datos combinados y truncados ---
                        $oficio_completo = htmlspecialchars($solicitud['oficio_fac'] . ' (' . $solicitud['fecha_oficio_fac'] . ')');
                        $oficio_corto = mb_strimwidth($oficio_completo, 0, 15, '…');
                        $depto_completo = htmlspecialchars($solicitud['depto_nom_propio'] . ' / ' . $solicitud['Nombre_fac_minb']);
                        $nombre_completo = htmlspecialchars($solicitud['nombre']);
                        $nombre_corto = mb_strimwidth($nombre_completo, 0, 15, '…');
                        $observacion_completa = htmlspecialchars($solicitud['tipo_reemplazo'] ?? '');
                        $observacion_corta = mb_strimwidth($observacion_completa, 0, 15, '…');
                    ?>
                    <tr data-id="<?= htmlspecialchars($solicitud['id_solicitud']) ?>">
                        <td><input type="checkbox" class="row-checkbox"></td>
                        <td><?= htmlspecialchars($solicitud['novedad_display']) ?></td>
                        <td title="<?= $oficio_completo ?>"><?= $oficio_corto ?></td>
                        <td><?= $depto_completo ?></td>
                        <td><?= htmlspecialchars($solicitud['tipo_docente']) ?></td>
                        <td><?= htmlspecialchars($solicitud['cedula']) ?></td>
                        <td title="<?= $nombre_completo ?>"><?= $nombre_corto ?></td>
                        <td><?= htmlspecialchars($solicitud['dedicacion_unificada_inicial']) ?></td>
                        <td><?= htmlspecialchars($solicitud['dedicacion_unificada_final']) ?></td>
                        <td contenteditable="true" class="editable-cell" data-field="puntos"><?= htmlspecialchars($solicitud['puntos'] ?? '') ?></td>
                        <td contenteditable="true" class="editable-cell" data-field="tipo_reemplazo" title="<?= $observacion_completa ?>"><?= $observacion_corta ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
   <div class="mt-4 flex items-center space-x-3">
    <button id="btnGenerarPrevio" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
        <i class="fas fa-eye mr-2"></i>
        Generar Previo
    </button>
    
    <button id="btnGenerarWord" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
        <i class="fas fa-file-word mr-2"></i>
        Generar Oficio Definitivo
    </button>
</div>

   <?php else: ?>
        <div class="text-center text-gray-500 py-6">
            <i class="fas fa-folder-open fa-3x mb-3 text-gray-400"></i>
            <p class="font-semibold">No hay solicitudes aprobadas pendientes de trámite.</p>
            
            <p class="text-sm">Cuando las solicitudes sean aprobadas, aparecerán en esta sección.</p>
        </div>
    <?php endif; ?>

</div>
    </div>
</div>
    
   
    
    <div id="detailsModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 h-full w-full hidden z-50">
        <div class="relative top-40 mx-auto p-5 border w-11/12 lg-w-4/5 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-2xl font-semibold" id="modalTitle"></h3>
                <button id="closeModalBtn" class="text-black text-3xl hover:text-gray-600">&times;</button>
            </div>

            <div class="mt-3 overflow-y-auto" style="max-height: 65vh;">
             <table class="min-w-full divide-y divide-y-gray-200">
                <thead class="bg-gray-50">
    <tr>
        <th class="px-4 py-2 text-left">
            <input type="checkbox" id="selectAllCheckbox">
        </th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Oficio Depto.</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Novedad</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Justificación Depto.</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Nombre</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Cédula</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Tipo</th>
        <th colspan="2" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase">Dedicación</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Estado Facultad</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Estado VRA</th>
        <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Observación VRA</th>
    <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Tipo Observ.</th>

    </tr>
    <tr>
        <th></th>
        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase bg-gray-100">Popayán</th>
        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase bg-gray-100">Regionalización</th>
    </tr>
</thead>
                <tbody id="modalTableBody" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
            </div>

            <div id="modalActionFooter" class="mt-4 pt-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                 
                    <div class="flex items-end space-x-3">
                        <button id="aprobarVraBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none">
                            <i class="fas fa-check mr-2"></i> Aprobar
                        </button>
                        <button id="rechazarVraBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none">
                            <i class="fas fa-times mr-2"></i> Rechazar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


   <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- 1. VARIABLES GLOBALES Y ELEMENTOS DEL DOM ---
        const estructuraVRA = <?php echo $estructura_vra_json; ?>;
        const todasLasSolicitudes = <?php echo $solicitudes_json; ?>;
        const modal = document.getElementById('detailsModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalTableBody = document.getElementById('modalTableBody');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');

        // =============================================================
    // ===== NUEVA FUNCIÓN PARA ACTUALIZAR OFICIOS VISIBLES =====
    // =============================================================
    function actualizarOficiosVisibles() {
        // Buscar todos los departamentos que están abiertos
        const departamentosAbiertos = document.querySelectorAll('.accordion-body-departamento:not(.hidden)');
        
        departamentosAbiertos.forEach(bodyDepto => {
            const headerDepto = bodyDepto.previousElementSibling;
            const nombreDepto = headerDepto.dataset.depto;
            const nombreFacultad = headerDepto.dataset.facultad;
            
            // Volver a renderizar los oficios para este departamento
            renderOficiosEnDepto(bodyDepto, nombreFacultad, nombreDepto);
        });
    }
         // =============================================================
            // ===== INICIA NUEVA LÓGICA PARA EL FILTRO =====
            // =============================================================
            const radiosFiltro = document.querySelectorAll('input[name="filtro_estado"]');

            function tienePendientesVRA(oficios) {
                for (const nombreOficio in oficios) {
                    if (oficios[nombreOficio].some(sol => sol.estado_vra === 'PENDIENTE')) {
                        return true; // Si encuentra al menos uno, retorna verdadero
                    }
                }
                return false; // Si recorre todo y no encuentra, retorna falso
            }
        // ======================================================================
    // ===== INICIA NUEVA FUNCIÓN PARA PINTAR FONDOS DE PENDIENTES =====
    // ======================================================================
    function actualizarEstilosPendientes() {
        const facultadesContainers = document.querySelectorAll('[data-facultad-container]');

        facultadesContainers.forEach(facContainer => {
            const nombreFacultad = facContainer.dataset.facultadContainer;
            let facultadTienePendientes = false;

            const deptosContainers = facContainer.querySelectorAll('[data-depto-container]');
            
            deptosContainers.forEach(deptoContainer => {
                const nombreDepto = deptoContainer.dataset.deptoContainer;
                const oficios = estructuraVRA[nombreFacultad]?.[nombreDepto] || {};

                // Buscamos el botón dentro del contenedor para aplicarle el estilo
                const deptoBoton = deptoContainer.querySelector('.accordion-departamento');
                
                if (tienePendientesVRA(oficios)) {
                    facultadTienePendientes = true;
                    if (deptoBoton) deptoBoton.classList.add('has-pending-depto');
                } else {
                    if (deptoBoton) deptoBoton.classList.remove('has-pending-depto');
                }
            });

            // Aplicamos o quitamos la clase a la facultad entera
            if (facultadTienePendientes) {
                facContainer.classList.add('has-pending-fac');
            } else {
                facContainer.classList.remove('has-pending-fac');
            }
        });
    }
    // ======================================================================
    // ===== FIN DE LA NUEVA FUNCIÓN =====
    // ======================================================================


         function aplicarFiltro() {
        const filtroSeleccionado = document.querySelector('input[name="filtro_estado"]:checked').value;
        const facultadesContainers = document.querySelectorAll('[data-facultad-container]');

        facultadesContainers.forEach(facContainer => {
            const nombreFacultad = facContainer.dataset.facultadContainer;
            let facultadTieneDeptosVisibles = false;

            const deptosContainers = facContainer.querySelectorAll('[data-depto-container]');

            deptosContainers.forEach(deptoContainer => {
                if (filtroSeleccionado === 'pendientes') {
                    const nombreDepto = deptoContainer.dataset.deptoContainer;
                    const oficios = estructuraVRA[nombreFacultad]?.[nombreDepto] || {};

                    if (tienePendientesVRA(oficios)) {
                        deptoContainer.style.display = 'block';
                        facultadTieneDeptosVisibles = true;
                    } else {
                        deptoContainer.style.display = 'none';
                    }
                } else {
                    deptoContainer.style.display = 'block';
                    facultadTieneDeptosVisibles = true;
                }
            });

            // Ocultar o mostrar la facultad según si tiene departamentos visibles
            if (facultadTieneDeptosVisibles) {
                facContainer.style.display = 'block';
            } else {
                facContainer.style.display = 'none';
            }
        });
        
        // ACTUALIZACIÓN: Llamar a la función para actualizar oficios visibles
        actualizarOficiosVisibles();
        actualizarEstilosPendientes();
    }
            // Añadimos el evento a los botones de radio para que ejecuten el filtro
            radiosFiltro.forEach(radio => {
                radio.addEventListener('change', aplicarFiltro);
            });
            // =============================================================
            // ===== FIN DE LA LÓGICA PARA EL FILTRO =====
            // =============================================================

        // --- 2. FUNCIONES AUXILIARES ---
        // funcion para  mensaje  envios...
function toggleLoading(show, message = 'Procesando...') {
    const overlay = document.getElementById('loadingOverlay');
    const messageEl = document.getElementById('loadingMessage');

    if (show) {
        messageEl.textContent = message;
        overlay.classList.remove('hidden');
    } else {
        overlay.classList.add('hidden');
    }
}
//crear  eeitquetse
        function crearEtiquetaEstado(estado, observacion, tipo = 'facultad') {
            let texto = estado || 'PENDIENTE';
            let clasesColor = 'bg-yellow-100 text-yellow-800';
            let tooltip = observacion ? `title="${observacion}"` : '';
            if (tipo === 'facultad') {
                if (estado === 'APROBADO') { texto = 'AVALADO'; clasesColor = 'bg-green-100 text-green-800'; }
                else if (estado === 'RECHAZADO') { texto = 'NO AVALADO'; clasesColor = 'bg-red-100 text-red-800'; }
            } else {
                if (estado === 'APROBADO') { clasesColor = 'bg-green-100 text-green-800'; }
                else if (estado === 'RECHAZADO') { clasesColor = 'bg-red-100 text-red-800'; }
            }
            return `<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${clasesColor}" ${tooltip}>${texto}</span>`;
        }

// Reemplaza tu función llenarModalVRA con esta
function llenarModalVRA(oficio_fac) {
    modalTitle.textContent = 'Solicitudes del Oficio de facultad # ' + oficio_fac;
    modalTableBody.innerHTML = '';
    selectAllCheckbox.checked = false;
    const solicitudesFiltradas = todasLasSolicitudes.filter(sol => sol.oficio_con_fecha_fac === oficio_fac);

    // ===================================================================
    // ===== INICIA EL BLOQUE MODIFICADO: LÓGICA DE ORDENAMIENTO =====
    // ===================================================================
    // 1. Define el orden exacto y personalizado que deseas para las novedades.
    const ordenNovedades = ['modificacion', 'cambio de vinculacion', 'adicionar', 'eliminar'];

    // 2. Ordena el array 'solicitudesFiltradas' según el orden definido.
    solicitudesFiltradas.sort((a, b) => {
        const novedadA = (a.novedad || '').toLowerCase();
        const novedadB = (b.novedad || '').toLowerCase();

        // Encuentra la posición de cada novedad en tu lista de orden.
        // Si una novedad no está en la lista, se le da un valor alto (999) para enviarla al final.
        const indexA = ordenNovedades.indexOf(novedadA) !== -1 ? ordenNovedades.indexOf(novedadA) : 999;
        const indexB = ordenNovedades.indexOf(novedadB) !== -1 ? ordenNovedades.indexOf(novedadB) : 999;

        // Compara las posiciones para determinar el orden.
        return indexA - indexB;
    });
    // ===================================================================
    // ===== FIN DEL BLOQUE MODIFICADO =====
    // ===================================================================

    if (solicitudesFiltradas.length > 0) {
        solicitudesFiltradas.forEach(sol => {
            let popayanData = '<span class="text-gray-400">N/A</span>';
            let regionalizacionData = '<span class="text-gray-400">N/A</span>';
            if (sol.tipo_docente === 'Ocasional') { /* ... (código sin cambios) ... */ } 
            else if (sol.tipo_docente === 'Catedra') { /* ... (código sin cambios) ... */ }

            const tipoDocenteDisplay = (sol.tipo_docente === 'Catedra') ? 'Cátedra' : sol.tipo_docente;
            const estadoFacultadHtml = crearEtiquetaEstado(sol.estado_facultad, sol.observacion_facultad, 'facultad');
            const estadoVraHtml = crearEtiquetaEstado(sol.estado_vra, sol.observacion_vra, 'vra');
            const observacionGuardada = sol.observacion_vra || '';
            
            // ===== INICIO DE LA NUEVA LÓGICA PARA TIPO REEMPLAZO =====
            let tipoReemplazoHtml = `<span class="text-sm text-gray-600">${sol.tipo_reemplazo || 'No definido'}</span>`;
            if (sol.estado_vra === 'PENDIENTE') {
                const currentNovedad = (sol.novedad || '').toLowerCase();
                const currentTipoReemplazo = sol.tipo_reemplazo || 'Otro'; // 'Otro' por defecto
                let optionsHtml = '';

                if (currentNovedad === 'adicionar' || currentNovedad === 'modificar' || currentNovedad === 'cambio vinculación') {
                    const options = ["Ajuste de Matrículas", "No legalizó", "Otras fuentes de financiacion", "Reemplazo", "Reemplazo jubilación", "Reemplazo necesidad docente", "Reemplazo por Fallecimiento", "Reemplazo por Licencia", "Reemplazo renuncia", "Reemplazos NN", "Ajuste Puntos", "Ajuste por VRA", "Otro"];
                    options.forEach(opt => {
                        optionsHtml += `<option value="${opt}" ${currentTipoReemplazo === opt ? 'selected' : ''}>${opt}</option>`;
                    });
                } else if (currentNovedad === 'eliminar') {
                    const options = ["Fallecimiento", "Renuncia", "Ajuste de Matrículas", "No legalizó", "Reemplazos NN", "Otro"];
                    options.forEach(opt => {
                        optionsHtml += `<option value="${opt}" ${currentTipoReemplazo === opt ? 'selected' : ''}>${opt}</option>`;
                    });
                } else {
                    optionsHtml = '<option value="">No hay opciones</option>';
                }
                
                // El data-id es crucial para vincularlo con el checkbox
                tipoReemplazoHtml = `<select class="w-full border-gray-300 rounded-md shadow-sm text-sm tipo-reemplazo-select" data-id="${sol.solicitud_id}">${optionsHtml}</select>`;
            }
            // ===== FIN DE LA NUEVA LÓGICA =====

            // ===================================================================
            // ===== BLOQUE MODIFICADO: LÓGICA PARA MOSTRAR NOVEDADES =====
            // ===================================================================
            // Si la novedad contiene "modificar" pero NO contiene "vinculación", mostrar "Modificar dedicación"
            let novedadDisplay = sol.novedad || '';
            const novedadLower = novedadDisplay.toLowerCase();
            
            if (novedadLower.includes('modificar') && !novedadLower.includes('vinculación') && !novedadLower.includes('vinculacion')) {
                novedadDisplay = 'Modificar dedicación';
            } else if (novedadDisplay === 'modificacion') {
                novedadDisplay = 'Cambio de dedicacion';
            }
            // ===================================================================
            // ===== FIN DEL BLOQUE MODIFICADO =====
            // ===================================================================

            const filaHTML = `<tr class="${sol.estado_vra !== 'PENDIENTE' ? 'bg-gray-200 opacity-60' : ''}">
                <td class="px-4 py-2">${sol.estado_vra === 'PENDIENTE' ? `<input type="checkbox" class="solicitud-checkbox" value="${sol.solicitud_id}">` : ''}</td>
                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-600">${sol.oficio_con_fecha || ''}</td>
                <td class="px-6 py-2 whitespace-nowrap">${novedadDisplay}</td>
                <td class="px-6 py-2 whitespace-normal max-w-xs break-words text-gray-700">${sol.s_observacion || ''}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${sol.nombre}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${sol.cedula}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${tipoDocenteDisplay}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${popayanData}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${regionalizacionData}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${estadoFacultadHtml}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${estadoVraHtml}</td>
                <td class="px-6 py-2 whitespace-nowrap">
                    ${sol.estado_vra === 'PENDIENTE' 
                        ? `<input type="text" class="observacion-vra-input w-full border-gray-300 rounded-md shadow-sm" data-id="${sol.solicitud_id}">` 
                        : '...'}
                </td>
                <td class="px-6 py-2 whitespace-nowrap">${tipoReemplazoHtml}</td>
            </tr>`;
            modalTableBody.innerHTML += filaHTML;
        });
    } else {
        modalTableBody.innerHTML = `<tr><td colspan="14" class="text-center py-4">No se encontraron solicitudes.</td></tr>`;
    }
    modal.classList.remove('hidden');
}
 function procesarSeleccion(accion) {   
    const checkboxes = modalTableBody.querySelectorAll('.solicitud-checkbox:checked');
    let solicitudesData = [];
    let observacionFaltante = false;

    checkboxes.forEach(cb => {
        const id = cb.value;
        
        // ==========================================================
        // ===== ¡AQUÍ ESTÁ LA CORRECCIÓN! =====
        // ==========================================================
        // 1. Encontramos la fila (<tr>) a la que pertenece el checkbox.
        const fila = cb.closest('tr'); 
        
        // 2. Buscamos los campos SOLO DENTRO de esa fila. Esto es mucho más preciso.
           // Buscar elementos dentro de la fila específica
        const observacionInput = fila.querySelector('.observacion-vra-input');
        const tipoReemplazoSelect = fila.querySelector('.tipo-reemplazo-select');

        const observacion = observacionInput ? observacionInput.value.trim() : '';
        const tipoReemplazo = tipoReemplazoSelect ? tipoReemplazoSelect.value : '';

        if (accion === 'rechazar' && observacion === '') {
            observacionFaltante = true;
            if (observacionInput) observacionInput.style.borderColor = 'red';
        }
        
        solicitudesData.push({ id: id, observacion: observacion, tipo_reemplazo: tipoReemplazo });
    });

    if (solicitudesData.length === 0) {
        alert('Por favor, seleccione al menos una solicitud para procesar.');
        return;
    }

    if (observacionFaltante) {
        alert('La observación es obligatoria para cada solicitud que se va a rechazar.');
        return;
    }

       let mensaje = `¿Está seguro de que desea ${accion} ${solicitudesData.length} solicitud(es)?`;

if (accion.toLowerCase() === "aprobar") {
    mensaje += `\n\nRecuerde definir los tipos de observación correspondientes antes de continuar.`;
}

if (!confirm(mensaje)) {
    return;
}


    const mensajeCarga = (accion === 'aprobar') 
        ? 'Procesando aprobaciones y enviando notificaciones...' 
        : 'Procesando rechazos y enviando notificaciones...';
    toggleLoading(true, mensajeCarga);

    const formData = new FormData();
    formData.append('accion', accion);
    formData.append('anio_semestre', '<?php echo $anio_semestre; ?>');
    formData.append('solicitudes', JSON.stringify(solicitudesData)); 

    fetch('procesar_aprobacion_vra.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error(`Error del servidor: ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Las solicitudes han sido procesadas exitosamente.');
            location.reload();
        } else {
            alert('Ocurrió un error al procesar las solicitudes: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error en la petición fetch:', error);
        alert('Ocurrió un error de conexión. Verifique la consola (F12) para más detalles.');
    })
    .finally(() => {
        toggleLoading(false);
    });
}

        // --- 3. ASIGNACIÓN DE EVENTOS (EVENT LISTENERS) ---
        
        // Eventos para cerrar el modal
        document.getElementById('closeModalBtn').addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', (event) => { if (event.target === modal) modal.classList.add('hidden'); });

        // Eventos para los botones de acción del modal
        document.getElementById('aprobarVraBtn').addEventListener('click', () => procesarSeleccion('aprobar'));
        document.getElementById('rechazarVraBtn').addEventListener('click', () => procesarSeleccion('rechazar'));
        
        // Evento para el checkbox "Seleccionar todos"
        selectAllCheckbox.addEventListener('change', (e) => {
            const checkboxes = modalTableBody.querySelectorAll('.solicitud-checkbox');
            checkboxes.forEach(checkbox => { checkbox.checked = e.target.checked; });
        });

        // Función para renderizar oficios en un departamento
        function renderOficiosEnDepto(bodyDepto, facultadName, deptoName) {
            const cardsContainer = bodyDepto.querySelector('.oficios-container');
            const oficios = estructuraVRA[facultadName]?.[deptoName] || {};
            const filtroActivo = document.querySelector('input[name="filtro_estado"]:checked').value === 'pendientes';
            
            cardsContainer.innerHTML = '';
            
            for (const oficio_fac in oficios) {
                const solicitudesDelOficio = oficios[oficio_fac];
                
                // Si el filtro está activo y no hay pendientes, saltar este oficio
                if (filtroActivo && !solicitudesDelOficio.some(sol => sol.estado_vra === 'PENDIENTE')) {
                    continue;
                }
                
                const totalSolicitudes = solicitudesDelOficio.length;
                const pendientesCount = solicitudesDelOficio.filter(sol => sol.estado_vra === 'PENDIENTE').length;
                const rechazadosCount = solicitudesDelOficio.filter(sol => sol.estado_vra === 'RECHAZADO').length;
                const aprobadosCount = solicitudesDelOficio.filter(sol => sol.estado_vra === 'APROBADO').length;

                let estadoVRA = '';
                let colorVRA = '';
                let iconVRA = '';

                if (pendientesCount > 0) {
                    estadoVRA = 'En Proceso';
                    colorVRA = 'bg-orange-100 text-orange-800';
                    iconVRA = '<i class="fas fa-hourglass-half"></i>';
                } else {
                    if (rechazadosCount > 0 && rechazadosCount === totalSolicitudes) {
                        estadoVRA = 'Finalizado (Rechazado)';
                        colorVRA = 'bg-red-100 text-red-800';
                        iconVRA = '<i class="fas fa-times-circle"></i>';
                    } else if (rechazadosCount > 0) {
                        estadoVRA = 'Finalizado (Mixto)';
                        colorVRA = 'bg-blue-100 text-blue-800';
                        iconVRA = '<i class="fas fa-check-double"></i>';
                    } else {
                        estadoVRA = 'Finalizado (Aprobado)';
                        colorVRA = 'bg-green-100 text-green-800';
                        iconVRA = '<i class="fas fa-check-circle"></i>';
                    }
                }
                
                const { codigo, fechaFormateada } = dividirOficio(oficio_fac);
                
                const cardHtml = `<div class="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase">Oficio Facultad</h3>
                            <span title="Estado en VRA: ${estadoVRA}" class="px-2 py-0.5 text-xs font-bold rounded-full flex items-center space-x-1 ${colorVRA}">
                                ${iconVRA} <span>${estadoVRA}</span>
                            </span>
                        </div>
                        <p class="text-md text-gray-800 my-2 truncate" title="${oficio_fac}">
                            <strong>${codigo}</strong> <span class="font-normal">${fechaFormateada}</span>
                        </p>
                    </div>
                    <button data-oficio-fac="${oficio_fac}" class="ver-detalles-btn-vra w-full mt-3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded-md text-sm">
                        Ver Solicitudes
                    </button>
                </div>`;
                
                cardsContainer.innerHTML += cardHtml;
            }
            
            if (cardsContainer.innerHTML.trim() === '') {
                cardsContainer.innerHTML = '<p class="col-span-full text-gray-500">No hay oficios de facultad para este departamento.</p>';
            }
            
            // Añadir event listeners a los nuevos botones
            cardsContainer.querySelectorAll('.ver-detalles-btn-vra').forEach(btn => {
                btn.addEventListener('click', () => {
                    llenarModalVRA(btn.dataset.oficioFac);
                });
            });
        }
        
        function dividirOficio(oficio) {
            const partes = oficio.split(" ");
            if (partes.length < 2) return { codigo: oficio, fechaFormateada: "" };

            const codigo = partes[0];
            const fecha = partes[1];

            const [anio, mes, dia] = fecha.split("-");
            const meses = {
                "01": "ene.", "02": "feb.", "03": "mar.", "04": "abr.",
                "05": "may.", "06": "jun.", "07": "jul.", "08": "ago.",
                "09": "sept.", "10": "oct.", "11": "nov.", "12": "dic."
            };

            const mesEsp = meses[mes] || mes;
            const fechaFormateada = `(${parseInt(dia)} de ${mesEsp} de ${anio})`;

            return { codigo, fechaFormateada };
        }

        // Evento principal delegado para los acordeones y botones "Ver Solicitudes"
        const listaFacultades = document.getElementById('lista-facultades');
        if (listaFacultades) {
            listaFacultades.addEventListener('click', function(event) {
            
                // Manejador para el Acordeón de Facultad (Nivel 1)
                const headerFacultad = event.target.closest('.accordion-facultad');
                if (headerFacultad) {
                    document.querySelectorAll('.accordion-facultad').forEach(otherHeader => {
                        if (otherHeader !== headerFacultad) {
                            otherHeader.nextElementSibling.classList.add('hidden');
                            otherHeader.querySelector('svg').classList.remove('rotate-180');
                            // NUEVO: Asegurarse de quitar los estilos de padre activo a las otras facultades
                            otherHeader.classList.remove('active-parent-accordion');
                        }
                    });

                    const bodyFacultad = headerFacultad.nextElementSibling;
                    const iconFacultad = headerFacultad.querySelector('svg');
                    bodyFacultad.classList.toggle('hidden');
                    iconFacultad.classList.toggle('rotate-180');
                    
                    // NUEVO: Si estamos CERRANDO la facultad, reseteamos todos sus hijos
                    if (bodyFacultad.classList.contains('hidden')) {
                        headerFacultad.classList.remove('active-parent-accordion');
                        bodyFacultad.querySelectorAll('.accordion-departamento').forEach(deptoHeader => {
                            deptoHeader.classList.remove('active-accordion');
                            deptoHeader.nextElementSibling.classList.add('hidden');
                            deptoHeader.querySelector('svg').classList.remove('rotate-180');
                        });
                    }
                }

                // Manejador para el Acordeón de Departamento (Nivel 2)
                const headerDepto = event.target.closest('.accordion-departamento');
                if (headerDepto) {
                    const parentFacultadBody = headerDepto.closest('.accordion-body-facultad');
                    if (parentFacultadBody) {
                        parentFacultadBody.querySelectorAll('.accordion-departamento').forEach(otherHeader => {
                            if (otherHeader !== headerDepto) {
                                otherHeader.nextElementSibling.classList.add('hidden');
                                otherHeader.querySelector('svg').classList.remove('rotate-180');
                                otherHeader.classList.remove('active-accordion');
                            }
                        });
                    }
                    
                    const bodyDepto = headerDepto.nextElementSibling;
                    const iconDepto = headerDepto.querySelector('svg');
                    bodyDepto.classList.toggle('hidden');
                    iconDepto.classList.toggle('rotate-180');
                    headerDepto.classList.toggle('active-accordion');
                    
                    // --- NUEVA LÓGICA PARA ACTUALIZAR EL PADRE ---
                    const parentHeaderFacultad = parentFacultadBody.previousElementSibling;
                    const tieneHijosActivos = parentFacultadBody.querySelector('.active-accordion');

                    if (tieneHijosActivos) {
                        parentHeaderFacultad.classList.add('active-parent-accordion');
                    } else {
                        parentHeaderFacultad.classList.remove('active-parent-accordion');
                    }
                    // --- FIN DE LA NUEVA LÓGICA ---

                    if (!bodyDepto.classList.contains('hidden') && bodyDepto.querySelector('.oficios-container').innerHTML.trim() === '') {
                        const deptoName = headerDepto.dataset.depto;
                        const facultadName = headerDepto.dataset.facultad;
                        renderOficiosEnDepto(bodyDepto, facultadName, deptoName);
                    }
                }

                // Manejador para el botón "Ver Solicitudes" que abre el modal
                const verDetallesBtn = event.target.closest('.ver-detalles-btn-vra');
                if (verDetallesBtn) {
                    llenarModalVRA(verDetallesBtn.dataset.oficioFac);
                }
            });
        }
        
        actualizarEstilosPendientes(); 
    });
       
       
</script>
    
<script>
document.addEventListener('DOMContentLoaded', () => {
    // =================================================================
    // PARTE 1: INICIALIZACIÓN Y DEPURACIÓN DE DATOS (LA CLAVE)
    // =================================================================
    const statusesVRA = <?php echo $statuses_json; ?>;

    // --- Inicio del Bloque de Depuración ---
    console.log("--- INICIO DEPURACIÓN DEL FILTRO ---");
    console.log("1. El objeto de estados (statusesVRA) que se recibió de PHP es:");
    console.log(statusesVRA);
    console.log("2. Las claves (nombres de facultad) disponibles en el objeto son:");
    console.log(Object.keys(statusesVRA));
    console.log("--- FIN DEPURACIÓN ---");
    // --- Fin del Bloque de Depuración ---


    // ==================================================
    // PARTE 2: LÓGICA DEL ACORDEÓN (SIN CAMBIOS)
    // ==================================================
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const body = header.nextElementSibling;
            const icon = header.querySelector('svg');
            body.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        });
    });

    // ==================================================
    // PARTE 3: LÓGICA DEL FILTRO (CON DEPURACIÓN ADICIONAL)
    // ==================================================
    const filtroRadios = document.querySelectorAll('input[name="filtro_estado"]');
    const mensajeNoPendientes = document.getElementById('mensaje-no-pendientes');

    function aplicarFiltroGlobal() {
        const filtroSeleccionado = document.querySelector('input[name="filtro_estado"]:checked').value;
        const todasLasFacultades = document.querySelectorAll('#lista-facultades > .bg-white.rounded-lg');
        let facultadesVisibles = 0;

        console.log(`\n--- Aplicando filtro: '${filtroSeleccionado}' ---`);

        todasLasFacultades.forEach(contenedor => {
            const header = contenedor.querySelector('.accordion-header');
            const facName = header.querySelector('span').textContent.trim();
            
            // Depuración: mostramos qué nombre estamos buscando
            console.log(`Buscando estados para la facultad: "'${facName}'"`);

            const statusesFacultad = statusesVRA[facName];
            let tienePendientes = false;
            
            // Depuración: verificamos si encontramos la facultad en el objeto
            if (statusesFacultad) {
                console.log(" -> ¡Encontrada! Revisando sus oficios...");
                for (const oficio in statusesFacultad) {
                    if (statusesFacultad[oficio].vra === 'En Proceso VRA') {
                        tienePendientes = true;
                        break;
                    }
                }
            } else {
                console.log(" -> X No encontrada en el objeto de estados.");
            }

            if (filtroSeleccionado === 'pendientes' && !tienePendientes) {
                contenedor.style.display = 'none';
            } else {
                contenedor.style.display = 'block';
                facultadesVisibles++;
            }
        });

        if (filtroSeleccionado === 'pendientes' && facultadesVisibles === 0) {
            if (mensajeNoPendientes) mensajeNoPendientes.classList.remove('hidden');
        } else {
            if (mensajeNoPendientes) mensajeNoPendientes.classList.add('hidden');
        }
        
        
    }

    filtroRadios.forEach(radio => {
        radio.addEventListener('change', aplicarFiltroGlobal);
    });
});
    
        aplicarFiltro();
// Añadir el evento a los botones de radio para que ejecuten el filtro
    radiosFiltro.forEach(radio => {
        radio.addEventListener('change', aplicarFiltro);
    });
</script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<script>
$(document).ready(function() {
    // 1. Inicializamos la DataTable (sin cambios)
    var table = $('#tablaAprobadosVRA').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true
    });

    // 2. Lógica para el checkbox "Seleccionar Todo" (sin cambios)
    $('#selectAll').on('click', function() {
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input.row-checkbox', rows).prop('checked', this.checked);
    });

    // 3. Lógica para que "Seleccionar Todo" se desmarque (sin cambios)
    $('#tablaAprobadosVRA tbody').on('change', 'input.row-checkbox', function() {
        if (!this.checked) {
            var el = $('#selectAll').get(0);
            if (el && el.checked && ('indeterminate' in el)) {
                el.indeterminate = true;
            }
        }
    });

    // 4. Funcionalidad del botón "Generar Oficio Definitivo"
    $('#btnGenerarWord').on('click', function() {
        var seleccionados = [];
        table.rows({ 'search': 'applied' }).every(function() {
            var rowNode = this.node();
            if ($(rowNode).find('input.row-checkbox').is(':checked')) {
                var fila = {
                    id: $(rowNode).data('id'),
                    puntos: $(rowNode).find('td[data-field="puntos"]').text(),
                    tipo_reemplazo: $(rowNode).find('td[data-field="tipo_reemplazo"]').text()
                };
                seleccionados.push(fila);
            }
        });

        if (seleccionados.length === 0) {
            alert('Por favor, seleccione al menos una solicitud para generar el oficio.');
            return;
        }

        const dataToSend = JSON.stringify(seleccionados);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generar_word_vra_srh.php';
        form.target = '_blank';

        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'seleccionados';
        hiddenField.value = dataToSend;
        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
    
    // 5. Funcionalidad del NUEVO botón "Generar Previo"
    $('#btnGenerarPrevio').on('click', function() {
        var seleccionados = [];
        table.rows({ 'search': 'applied' }).every(function() {
            var rowNode = this.node();
            if ($(rowNode).find('input.row-checkbox').is(':checked')) {
                var fila = {
                    id: $(rowNode).data('id'),
                    puntos: $(rowNode).find('td[data-field="puntos"]').text(),
                    tipo_reemplazo: $(rowNode).find('td[data-field="tipo_reemplazo"]').text()
                };
                seleccionados.push(fila);
            }
        });

        if (seleccionados.length === 0) {
            alert('Por favor, seleccione al menos una solicitud para generar el previo.');
            return;
        }

        const dataToSend = JSON.stringify(seleccionados);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'generar_previo_vra_srh.php'; 
        form.target = '_blank';

        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'seleccionados';
        hiddenField.value = dataToSend;
        form.appendChild(hiddenField);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });

// --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
// Este es el cierre correcto para $(document).ready()
// Debe ir al final, después de haber definido la función para AMBOS botones.
}); 

     // --- NUEVO SCRIPT PARA EL ACORDEÓN DE SOLICITUDES APROBADAS ---
    document.querySelector('.accordion-header-vra').addEventListener('click', function() {
        const accordionBody = this.nextElementSibling; // El div que contiene la tabla
        const arrowIcon = this.querySelector('svg');   // La flecha

        // Muestra u oculta el cuerpo del acordeón
        accordionBody.classList.toggle('hidden');

        // Gira la flecha 180 grados
        arrowIcon.classList.toggle('rotate-180');
    });
</script>
</body>
</html>