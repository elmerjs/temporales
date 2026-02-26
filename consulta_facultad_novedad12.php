<?php
// --- INCLUDES Y CONFIGURACIÓN INICIAL ---
$active_menu_item = 'novedades';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require('include/headerz.php');
require_once('conn.php');
require('funciones.php');

// --- VARIABLES ESENCIALES ---
$anio_semestre = $_POST['anio_semestre'] 
                 ?? $_GET['anio_semestre'] 
                 ?? $_SESSION['anio_semestre'] // <--- AGREGA ESTO AQUÍ
                 ?? '2026-1'; // <--- Tu nuevo año por defecto

// Actualizamos la memoria
$_SESSION['anio_semestre'] = $anio_semestre;
// --- VARIABLES ESENCIALES ---

// ===== INICIA EL NUEVO CÓDIGO DE FILTRO =====
// Leemos el filtro desde la URL. Por defecto, será 'all'.
$rawFiltro = $_GET['filtro'] ?? 'all';
// Creamos una "lista blanca" de filtros permitidos para seguridad.
$allowed_filters = ['all', 'fac-pending', 'vra-pending'];
// Si el filtro de la URL no es válido, usamos 'all' por defecto.
$filtro = in_array($rawFiltro, $allowed_filters) ? $rawFiltro : 'all';

// Si el usuario NO es Jefe de Departamento (tipo 3), forzamos el filtro a 'all'.
if ((int)$tipo_usuario !== 3) {
    $filtro = 'all';
}
// ===== TERMINA EL NUEVO CÓDIGO DE FILTRO =====
// Obtenemos los datos del usuario logueado
$id_facultad = null;
$id_departamento = null;
$tipo_usuario = null;
$aprobador_id_logged_in = null;

// Reemplaza el bloque de obtención de datos del usuario con este
if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];
    $stmt_user = $conn->prepare("SELECT Id, fk_fac_user, fk_depto_user, tipo_usuario FROM users WHERE Name = ?");
    $stmt_user->bind_param("s", $nombre_sesion);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $user_row = $result_user->fetch_assoc();
        
        // Asignar a variables locales para usar en ESTA página
        $aprobador_id_logged_in = $user_row['Id'];
        $tipo_usuario = $user_row['tipo_usuario'];
        $id_facultad = $user_row['fk_fac_user'];
        $id_departamento = $user_row['fk_depto_user'];

        // ===== INICIO DE LA CORRECCIÓN =====
        // Guardar TODOS los datos en la SESIÓN para que otros scripts los puedan usar
        $_SESSION['aprobador_id_logged_in'] = $user_row['Id'];
        $_SESSION['tipo_usuario'] = $user_row['tipo_usuario'];     // <-- LÍNEA CRUCIAL FALTANTE
        $_SESSION['id_facultad'] = $user_row['fk_fac_user'];
        $_SESSION['id_departamento'] = $user_row['fk_depto_user']; // <-- LÍNEA CRUCIAL FALTANTE
        // ===== FIN DE LA CORRECCIÓN =====
    }
    $stmt_user->close();
}
$nombre_decano=obtenerDecano($id_facultad);
$trd_fac= obtenerTRDFacultad($id_facultad);
// --- VALIDACIÓN DE ACCESO ---
if ($tipo_usuario === null) {
    die("Error: Sesión no iniciada o usuario no encontrado.");
} elseif ($tipo_usuario == 2 && is_null($id_facultad)) {
    die("Error: No se pudo determinar la facultad para el usuario logueado.");
} elseif ($tipo_usuario == 3 && is_null($id_departamento)) {
    die("Error: No se pudo determinar el departamento para el usuario logueado.");
}


// --- FUNCIÓN DE PROCESAMIENTO DE DATOS ---
function procesarCambiosVinculacion($solicitudes) {
    $transacciones = [];
    $otras_novedades = [];
    $resultado_final = [];

    foreach ($solicitudes as $sol) {
        $id_transaccion = $sol['oficio_con_fecha'] ?? null;
        $cedula = $sol['cedula'] ?? null;
        $novedad = strtolower($sol['novedad']);

        // --- CAMBIO CLAVE: Si es NN (222), usamos el ID para que no se agrupen ---
        // Esto permite que cada NN se trate como una persona distinta
        $identificador_unico = ($cedula === '222') ? 'NN_' . $sol['id_solicitud'] : $cedula;

        if ($id_transaccion && $cedula && ($novedad === 'adicionar' || $novedad === 'adicion' || $novedad === 'eliminar')) {
            // Usamos el identificador_unico en lugar de solo la cédula
            $transacciones[$id_transaccion][$identificador_unico][$novedad] = $sol;
        } else {
            $otras_novedades[] = $sol;
        }
    }

    foreach ($transacciones as $id_transaccion => $grupos_en_oficio) {
        foreach ($grupos_en_oficio as $id_key => $partes) {
            
            $sol_adicion = $partes['adicion'] ?? $partes['adicionar'] ?? null;

            if ($sol_adicion && isset($partes['eliminar'])) {
                // Caso Cambio de Vinculación (Solo ocurrirá si NO es '222' o si alguien enviara un par con el mismo ID)
                $sol_eliminacion = $partes['eliminar'];
                $tipo_docente_anterior = ($sol_eliminacion['tipo_docente'] === 'Catedra') ? 'Cátedra' : $sol_eliminacion['tipo_docente'];
                $estado_anterior = "Sale de " . $tipo_docente_anterior;
                
                if ($sol_eliminacion['tipo_docente'] === 'Ocasional') {
                    if ($sol_eliminacion['tipo_dedicacion']) $estado_anterior .= " " . $sol_eliminacion['tipo_dedicacion'];
                } elseif ($sol_eliminacion['tipo_docente'] === 'Catedra') {
                    if ($sol_eliminacion['horas'] > 0) $estado_anterior .= " " . $sol_eliminacion['horas'] . " hrs";
                }
                
                $sol_adicion['novedad'] = 'Modificar Vinculación';
                $obs = trim($sol_adicion['s_observacion']);
                $sol_adicion['s_observacion'] = $obs . ($obs ? ' ' : '') . '(' . $estado_anterior . ')';

                $resultado_final[] = $sol_adicion;
            } else {
                // Aquí entrarán todos tus NN porque ahora tienen IDs diferentes y no forman "pares"
                if ($sol_adicion) $otras_novedades[] = $sol_adicion;
                if (isset($partes['eliminar'])) $otras_novedades[] = $partes['eliminar'];
            }
        }
    }

    return array_merge($resultado_final, $otras_novedades);
}

// --- LÓGICA PRINCIPAL (ROUTING POR TIPO DE USUARIO) ---

$solicitudes_json = '[]';
$statuses_json = '[]';
if ($tipo_usuario == 3) {
    // --- LÓGICA PARA JEFE DE DEPARTAMENTO ---
    $sql_oficios = "SELECT DISTINCT oficio_con_fecha FROM solicitudes_working_copy WHERE departamento_id = ? AND anio_semestre = ? AND oficio_con_fecha IS NOT NULL ORDER BY fecha_oficio_depto,oficio_depto asc";
    $stmt_oficios = $conn->prepare($sql_oficios);
    $stmt_oficios->bind_param("is", $id_departamento, $anio_semestre);
    $stmt_oficios->execute();
    $oficios = $stmt_oficios->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_oficios->close();

    $sql_solicitudes = "SELECT * FROM solicitudes_working_copy WHERE departamento_id = ? AND anio_semestre = ?";
    $stmt_solicitudes = $conn->prepare($sql_solicitudes);
    $stmt_solicitudes->bind_param("is", $id_departamento, $anio_semestre);
    $stmt_solicitudes->execute();
    $todas_las_solicitudes = $stmt_solicitudes->get_result()->fetch_all(MYSQLI_ASSOC);

    $solicitudes_por_oficio = [];
    foreach ($todas_las_solicitudes as $sol) {
        if($sol['oficio_con_fecha']) $solicitudes_por_oficio[$sol['oficio_con_fecha']][] = $sol;
    }

    // ===== INICIA EL BLOQUE CORREGIDO =====
    // --- Lógica de cálculo de estados (VERSIÓN MEJORADA Y COMPLETA) ---
    $oficio_statuses = [];
    foreach ($solicitudes_por_oficio as $oficio_fecha => $solicitudes_del_oficio) {
        
        
    // --- LÓGICA MEJORADA PARA ESTADO DE FACULTAD ---
    $pendientes_fac_count = 0;
    $aprobados_fac_count = 0;
    $rechazados_fac_count = 0;
    
    foreach ($solicitudes_del_oficio as $sol) {
        if ($sol['estado_facultad'] === 'PENDIENTE') {
            $pendientes_fac_count++;
        } elseif ($sol['estado_facultad'] === 'APROBADO') {
            $aprobados_fac_count++;
        } elseif ($sol['estado_facultad'] === 'RECHAZADO') {
            $rechazados_fac_count++;
        }
    }
    
    $estado_facultad_oficio = '';
    if ($pendientes_fac_count > 0) {
        $estado_facultad_oficio = 'En Proceso';
    } elseif ($rechazados_fac_count > 0 && $aprobados_fac_count > 0) {
        $estado_facultad_oficio = 'Finalizado Mixto';
    } elseif ($rechazados_fac_count > 0 && $aprobados_fac_count == 0) {
        $estado_facultad_oficio = 'Rechazado Total';
    } elseif ($aprobados_fac_count > 0 && $rechazados_fac_count == 0) {
        $estado_facultad_oficio = 'Aprobado Total';
    } else {
        $estado_facultad_oficio = 'Finalizado'; // Caso por defecto
    }
        // --- Lógica MEJORADA para estado de VRA ---
        $estado_vra_oficio = '';
        
    
            // SEGUNDO: Si no, calcular el estado detallado de VRA
            $pendientes_vra_count = 0;
            $aprobados_vra_count = 0;
            $rechazados_vra_count = 0;
            $solicitudes_relevantes_vra = 0;

            foreach ($solicitudes_del_oficio as $sol) {
                if ($sol['estado_facultad'] !== 'RECHAZADO') {
                    $solicitudes_relevantes_vra++;
                    if ($sol['estado_vra'] === 'PENDIENTE') {
                        $pendientes_vra_count++;
                    } elseif ($sol['estado_vra'] === 'APROBADO') {
                        $aprobados_vra_count++;
                    } elseif ($sol['estado_vra'] === 'RECHAZADO') {
                        $rechazados_vra_count++;
                    }
                }
            }
            
            if ($pendientes_vra_count > 0) {
                $estado_vra_oficio = 'En Proceso';
            } elseif ($solicitudes_relevantes_vra === 0) {
                $estado_vra_oficio = 'N/A';
            } elseif ($rechazados_vra_count > 0 && $aprobados_vra_count > 0) {
                $estado_vra_oficio = 'Finalizado Mixto VRA';
            } elseif ($rechazados_vra_count > 0 && $aprobados_vra_count == 0) {
                $estado_vra_oficio = 'Rechazado Total VRA';
            } elseif ($aprobados_vra_count > 0 && $rechazados_vra_count == 0) {
                $estado_vra_oficio = 'Aprobado Total VRA';
            } else {
                $estado_vra_oficio = 'Finalizado';
            }
        
        
        $oficio_statuses[$oficio_fecha] = ['facultad' => $estado_facultad_oficio, 'vra' => $estado_vra_oficio];
    }
    // ===== TERMINA EL BLOQUE CORREGIDO =====

    $solicitudes_procesadas = procesarCambiosVinculacion($todas_las_solicitudes);
    $solicitudes_json = json_encode($solicitudes_procesadas);

}
elseif ($tipo_usuario == 2) {
    // --- LÓGICA PARA FACULTAD ---
    $sql_facultad = "SELECT d.depto_nom_propio AS nombre_departamento, s.*, a.id_acta AS id_acta_vinculada
                 FROM solicitudes_working_copy s 
                 JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO 
                 LEFT JOIN actas_seleccion_novedades a 
                     ON s.numero_acta59 = a.numero_acta 
                     AND s.departamento_id = a.departamento_id 
                     AND s.anio_semestre = a.anio_semestre
                 WHERE s.facultad_id = ? AND s.anio_semestre = ? AND s.oficio_con_fecha IS NOT NULL 
                 ORDER BY d.depto_nom_propio ASC, s.fecha_oficio_depto ASC, s.oficio_depto ASC";
    $stmt_facultad = $conn->prepare($sql_facultad);
    $stmt_facultad->bind_param("is", $id_facultad, $anio_semestre);
    $stmt_facultad->execute();
    $todas_las_solicitudes_facultad = $stmt_facultad->get_result()->fetch_all(MYSQLI_ASSOC);

    $datos_agrupados_facultad = [];
    foreach ($todas_las_solicitudes_facultad as $sol) {
        $datos_agrupados_facultad[$sol['nombre_departamento']][$sol['oficio_con_fecha']][] = $sol;
    }
    
    // --- Lógica de cálculo de DOS estados ---
    $oficio_statuses_facultad = [];
    foreach ($datos_agrupados_facultad as $nombre_depto => $oficios_depto) {
            foreach ($oficios_depto as $oficio_fecha => $solicitudes_del_oficio) {

                $pendientes_count = 0;
                $rechazados_count = 0;
                $aprobados_count = 0;
                $total_count = count($solicitudes_del_oficio);

                foreach ($solicitudes_del_oficio as $sol) {
                    if ($sol['estado_facultad'] === 'PENDIENTE') {
                        $pendientes_count++;
                    } elseif ($sol['estado_facultad'] === 'RECHAZADO') {
                        $rechazados_count++;
                    } elseif ($sol['estado_facultad'] === 'APROBADO') {
                        $aprobados_count++;
                    }
                }

                $estado_final_facultad = '';
                if ($pendientes_count > 0) {
                    $estado_final_facultad = 'En Proceso';
                } else {
                    // Si no hay pendientes, determinamos el tipo de "Finalizado"
                    if ($rechazados_count > 0 && $aprobados_count > 0) {
                        $estado_final_facultad = 'Finalizado Mixto'; // Hay aprobados y rechazados
                    } elseif ($rechazados_count > 0 && $aprobados_count == 0) {
                        $estado_final_facultad = 'Rechazado Total'; // Todos son rechazados
                    } elseif ($aprobados_count > 0 && $rechazados_count == 0) {
                        $estado_final_facultad = 'Aprobado Total'; // Todos son aprobados
                    } else {
                        $estado_final_facultad = 'Finalizado'; // Caso raro, ej: oficio vacío ya procesado
                    }
                }

                // La lógica para el estado de VRA no cambia
             // ===== INICIA EL CÓDIGO MEJORADO =====
$estado_vra_oficio = '';

// Si el trámite fue rechazado en su totalidad por la facultad, VRA no aplica.
if ($estado_final_facultad === 'Rechazado Total') {
    $estado_vra_oficio = 'N/A';
} else {
    // Si no fue rechazado, contamos los estados de VRA para las solicitudes relevantes
    $pendientes_vra_count = 0;
    $aprobados_vra_count = 0;
    $rechazados_vra_count = 0;
    $solicitudes_relevantes_vra = 0;

    foreach ($solicitudes_del_oficio as $sol) {
        // Solo consideramos las solicitudes que la facultad NO rechazó
        if ($sol['estado_facultad'] !== 'RECHAZADO') {
            $solicitudes_relevantes_vra++;
            if ($sol['estado_vra'] === 'PENDIENTE') {
                $pendientes_vra_count++;
            } elseif ($sol['estado_vra'] === 'APROBADO') {
                $aprobados_vra_count++;
            } elseif ($sol['estado_vra'] === 'RECHAZADO') {
                $rechazados_vra_count++;
            }
        }
    }

    // Determinamos el estado final de VRA basado en los conteos
    if ($pendientes_vra_count > 0) {
        $estado_vra_oficio = 'En Proceso';
    } elseif ($solicitudes_relevantes_vra === 0) {
        // Si no quedó ninguna solicitud para VRA (ej: todas fueron rechazadas por facultad individualmente)
        $estado_vra_oficio = 'N/A';
    } elseif ($rechazados_vra_count > 0 && $aprobados_vra_count > 0) {
        $estado_vra_oficio = 'Finalizado Mixto VRA';
    } elseif ($rechazados_vra_count > 0 && $aprobados_vra_count == 0) {
        $estado_vra_oficio = 'Rechazado Total VRA';
    } elseif ($aprobados_vra_count > 0 && $rechazados_vra_count == 0) {
        $estado_vra_oficio = 'Aprobado Total VRA';
    } else {
        $estado_vra_oficio = 'Finalizado'; // Un caso por defecto si no hay aprobados ni rechazados
    }
}

$oficio_statuses_facultad[$nombre_depto][$oficio_fecha] = [
    'facultad' => $estado_final_facultad,
    'vra' => $estado_vra_oficio
];
// ===== TERMINA EL CÓDIGO MEJORADO =====
            }
        }
 // ===================================================================
// ===== INICIA CORRECCIÓN: Procesar cada oficio por separado =====
// ===================================================================

$solicitudes_procesadas_final = [];
// Recorremos la estructura que ya está agrupada por departamento y oficio ($datos_agrupados_facultad)
foreach ($datos_agrupados_facultad as $nombre_depto => $oficios_depto) {
    foreach ($oficios_depto as $oficio_fecha => $solicitudes_del_oficio) {
        
        // Aplicamos la función de procesamiento SÓLO a las solicitudes de ESTE oficio
        $solicitudes_procesadas_del_oficio = procesarCambiosVinculacion($solicitudes_del_oficio);
        
        // Unimos los resultados procesados de cada oficio en una sola lista plana
        $solicitudes_procesadas_final = array_merge($solicitudes_procesadas_final, $solicitudes_procesadas_del_oficio);
    }
}

// Creamos el JSON final a partir de la lista correctamente procesada
$solicitudes_json = json_encode($solicitudes_procesadas_final);

// ===================================================================
// ===== FIN DE LA CORRECCIÓN =====
// ===================================================================
    $statuses_json = json_encode($oficio_statuses_facultad);
    
    
    // ===================================================================
    $sql_historico = "SELECT 
        s.oficio_fac, 
        s.fecha_oficio_fac, 
        COUNT(s.id_solicitud) AS cantidad_novedades, 
        GROUP_CONCAT(DISTINCT d.depto_nom_propio SEPARATOR ', ') AS departamentos_incluidos, 
        MAX(s.elaborado_por) AS elaborado_por
    FROM solicitudes_working_copy s
    LEFT JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO
    WHERE s.facultad_id = ? AND s.anio_semestre = ? AND s.oficio_fac IS NOT NULL AND s.oficio_fac != ''
    GROUP BY s.oficio_fac, s.fecha_oficio_fac
    ORDER BY s.fecha_oficio_fac DESC, s.oficio_fac DESC";

    $stmt_historico = $conn->prepare($sql_historico);
    $stmt_historico->bind_param("is", $id_facultad, $anio_semestre);
    $stmt_historico->execute();
    $historico_oficios = $stmt_historico->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_historico->close();
}
$count_pendientes = 0;
if ($tipo_usuario == 3 && isset($con) && !$con->connect_error) {
    $sql_count_pendientes = "SELECT COUNT(DISTINCT cedula) AS total_pendientes 
                             FROM solicitudes_working_copy
                             WHERE departamento_id = ?
                             AND anio_semestre = ?
                             AND estado_depto = 'PENDIENTE'";
                             
    if ($stmt_cnt = $conn->prepare($sql_count_pendientes)) {
        $stmt_cnt->bind_param("is", $id_departamento, $anio_semestre);
        $stmt_cnt->execute();
        $res_cnt = $stmt_cnt->get_result();
        $data_cnt = $res_cnt->fetch_assoc();
        $count_pendientes = $data_cnt['total_pendientes'] ?? 0;
        $stmt_cnt->close();
    }
}

// PASO 2: Ahora sí puedes cerrar la conexión si lo deseas, 
// aunque lo recomendable es hacerlo al final del archivo.
// $con->close();

//$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Novedades</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<style>
 .bg-pink-highlight {
        background-color: rgba(252, 231, 243, 0.7);
 /* Rosa suave de Tailwind - pink-100 */
        border-left: 4px solid #ec4899; /* Borde rosa más oscuro */
    }
     .accordion-header .accordion-button {
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
        font-size: 0.9rem; /* Opcional: reduce un poco el tamaño de la fuente */
    }
.border-red-300 {
  border-color: #ec4899;
}
    .rotate-180 {
    transform: rotate(180deg);
}

.transition-transform {
    transition: transform 0.3s ease;
}

.border-blue-500 {
    border-color: #3B82F6;
}

.bg-blue-50 {
    background-color: #EFF6FF;
}

.hover\:bg-blue-100:hover {
    background-color: #DBEAFE;
}
  
    /* Estilos para la Notificación Temporal (Toast) */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #2f855a; /* Un verde oscuro y profesional */
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        z-index: 100;
        
        /* Oculto por defecto */
        opacity: 0;
        visibility: hidden;
        
        /* Transición suave */
        transition: opacity 0.5s, visibility 0.5s, transform 0.5s;
        transform: translateY(-20px);
    }

    .toast-container.show {
        /* Visible cuando tiene la clase 'show' */
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
</style>
<body class="bg-gray-100">
<div id="toast-notification" class="toast-container">
    <p id="toast-message">Mensaje de éxito</p>
</div>
    <div class="container mx-auto p-8">
<div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
        <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
            
            <h1 class="text-3xl font-bold text-gray-800">
                Novedades Solicitadas (según Oficio Departamento)
                <span class="text-3xl font-normal text-gray-500">(<?= htmlspecialchars($anio_semestre) ?>)</span>
            </h1>

            <?php
            // --- LÓGICA INTEGRADA DEL BOTÓN DE NOVEDADES (SOLO PARA USUARIO TIPO 3) ---
           if ($tipo_usuario == 3) {
                $cierreperiodonov = obtenerperiodonov($anio_semestre);
                $url_novedad = "consulta_todo_depto_novedad.php?" .
                               "&anio_semestre=" . urlencode($anio_semestre) .
                               "&departamento_id=" . urlencode($id_departamento);

                if ($cierreperiodonov <> 1) { ?>
                    <div class="flex flex-col items-center md:items-start gap-2">
                        <a href="<?= htmlspecialchars($url_novedad); ?>" 
                           class="w-full md:w-auto inline-flex items-center justify-center px-5 py-2 border border-transparent text-base font-medium rounded-md text-white bg-[#003366] hover:bg-[#002244] shadow-sm transition-colors group">
                            
                            <i class="fas fa-edit mr-2"></i>
                            <span>Preparar Novedades</span>

                            <?php if ($count_pendientes > 0): ?>
                                <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-400 text-black shadow-sm">
                                    <?= $count_pendientes ?> en borrador
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <p class="text-xs text-gray-500 italic mt-1 max-w-xs text-center md:text-left">
                            <i class="fas fa-info-circle text-blue-500"></i> 
                            Aquí puedes <strong>crear novedades</strong> o revisar las que tienes <strong>pendientes de envío</strong> a Facultad.
                        </p>
                    </div>
                <?php } else { ?>
                    <div class="flex flex-col items-center md:items-start gap-2">
                        <button disabled class="w-full md:w-auto inline-flex items-center justify-center px-5 py-2 border border-transparent text-base font-medium rounded-md text-white bg-gray-400 cursor-not-allowed">
                            <i class="fas fa-lock mr-2"></i>
                            Preparar Novedades
                        </button>
                        <p class="text-xs text-red-400 italic mt-1">Período de novedades cerrado.</p>
                    </div>
                <?php }
            }
            // --- FIN DE LA LÓGICA DEL BOTÓN ---
            ?>
        </div>

        <?php if ($tipo_usuario == 3): ?>
   <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-6 flex justify-between items-center">
    
    <div class="flex items-center space-x-2">
        <span class="font-semibold text-gray-600 text-sm mr-2">Mostrar:</span>
        
        <a href="?anio_semestre=<?= htmlspecialchars($anio_semestre) ?>&filtro=all" class="px-3 py-1 text-sm font-medium rounded-md transition-all duration-200 <?= ($filtro === 'all') ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-list-ul mr-1 opacity-80"></i> Todos
        </a>
        
        <a href="?anio_semestre=<?= htmlspecialchars($anio_semestre) ?>&filtro=fac-pending" class="px-3 py-1 text-sm font-medium rounded-md transition-all duration-200 <?= ($filtro === 'fac-pending') ? 'bg-orange-500 text-white shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-hourglass-half mr-1 opacity-80"></i> Pendientes Facultad
        </a>
        
        <a href="?anio_semestre=<?= htmlspecialchars($anio_semestre) ?>&filtro=vra-pending" class="px-3 py-1 text-sm font-medium rounded-md transition-all duration-200 <?= ($filtro === 'vra-pending') ? 'bg-orange-500 text-white shadow' : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
            <i class="fas fa-hourglass-half mr-1 opacity-80"></i> Pendientes VRA
        </a>
    </div>

    <a href="exportar_excel_novedades.php" 
   class="inline-flex items-center px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-md shadow hover:bg-green-600 transition-colors"
   target="_blank">
    <i class="fas fa-file-excel mr-2"></i>
    Exportar a Excel
</a>

</div>
        <?php endif; ?>
        <?php if ($tipo_usuario == 3): ?>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div id="cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (!empty($oficios)): ?>
                <?php foreach ($oficios as $oficio): ?>
                    <?php
                    $oficio_fecha = $oficio['oficio_con_fecha'];
                    $status = $oficio_statuses[$oficio_fecha] ?? 'Desconocido';
                    $status_color_class = ($status === 'En Proceso') ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800';
                    $status_fac = $oficio_statuses[$oficio_fecha]['facultad'];
                    $status_vra = $oficio_statuses[$oficio_fecha]['vra'];

                    // ===== INICIA LA NUEVA LÓGICA DE FILTRADO =====
                    if ($filtro === 'fac-pending' && $status_fac !== 'En Proceso') {
                        continue; 
                    }
                    if ($filtro === 'vra-pending' && $status_vra !== 'En Proceso') {
                        continue; 
                    }
                    // ===== TERMINA LA NUEVA LÓGICA DE FILTRADO =====

                    // --- 🕵️‍♂️ DETECTIVE DE ACTAS: BUSCAR SI ESTE OFICIO TIENE ACTA ---
                    $acta_id_para_descarga = null;
                    $tooltip_acta = "";
                    
                    // Paso 1: Buscar si alguna solicitud en este oficio tiene numero_acta59
                    $sql_detectar_acta = "SELECT numero_acta59 
                                          FROM solicitudes_working_copy 
                                          WHERE departamento_id = ? 
                                          AND anio_semestre = ? 
                                          AND oficio_con_fecha = ? 
                                          AND numero_acta59 IS NOT NULL 
                                          AND numero_acta59 != '' 
                                          LIMIT 1";
                    
                    if ($stmt_da = $conn->prepare($sql_detectar_acta)) {
                        $stmt_da->bind_param("iss", $id_departamento, $anio_semestre, $oficio_fecha);
                        $stmt_da->execute();
                        $res_da = $stmt_da->get_result();
                        
                        if ($fila_acta = $res_da->fetch_assoc()) {
                            $num_acta_encontrada = $fila_acta['numero_acta59'];
                            $tooltip_acta = "Acta N° " . htmlspecialchars($num_acta_encontrada);
                            
                            // Paso 2: Si hay número, buscamos el ID real en la tabla de actas
                            $sql_id_acta = "SELECT id_acta 
                                            FROM actas_seleccion_novedades 
                                            WHERE departamento_id = ? 
                                            AND anio_semestre = ? 
                                            AND numero_acta = ? 
                                            LIMIT 1";
                            
                            if ($stmt_ia = $conn->prepare($sql_id_acta)) {
                                $stmt_ia->bind_param("iss", $id_departamento, $anio_semestre, $num_acta_encontrada);
                                $stmt_ia->execute();
                                $res_ia = $stmt_ia->get_result();
                                if ($fila_id = $res_ia->fetch_assoc()) {
                                    $acta_id_para_descarga = $fila_id['id_acta'];
                                }
                                $stmt_ia->close();
                            }
                        }
                        $stmt_da->close();
                    }
                    // -------------------------------------------------------------

                    // Definición de Estilos y Colores
                    $borderColorClass = ($status_fac === 'En Proceso') ? 'border-red-300' : 'border-[#003366]';
                    ?>

                    <div class="bg-[#F0F4F9] rounded-lg shadow-md p-6 border-l-4 <?= $borderColorClass ?> hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Oficio</h3>
                                <div class="flex flex-col items-end space-y-1">
                                    <?php
                                    // Lógica visual Facultad
                                    $color_fac = ''; $icon_fac = ''; $text_fac = 'Facultad';
                                    if ($status_fac === 'En Proceso') { $color_fac = 'bg-orange-100 text-orange-800'; $icon_fac = '<i class="fas fa-hourglass-half"></i>'; $text_fac = 'En Proceso Facultad'; }
                                    elseif ($status_fac === 'Aprobado Total') { $color_fac = 'bg-green-100 text-green-800'; $icon_fac = '<i class="fas fa-check-circle"></i>'; $text_fac = 'Aprobado Facultad'; }
                                    elseif ($status_fac === 'Rechazado Total') { $color_fac = 'bg-red-100 text-red-800'; $icon_fac = '<i class="fas fa-times-circle"></i>'; $text_fac = 'Rechazado Facultad'; }
                                    elseif ($status_fac === 'Finalizado Mixto') { $color_fac = 'bg-blue-100 text-blue-800'; $icon_fac = '<i class="fas fa-check-double"></i>'; $text_fac = 'Finalizado Mixto Facultad'; }
                                    else { $color_fac = 'bg-blue-100 text-blue-800'; $icon_fac = '<i class="fas fa-check"></i>'; $text_fac = 'Finalizado Facultad'; }
                                    ?>
                                    <span title="Estado Facultad: <?= $status_fac ?>" class="px-2 py-0.5 text-xs font-bold rounded-full flex items-center space-x-1 <?= $color_fac ?>"><?= $icon_fac ?> <span><?= $text_fac ?></span></span>

                                    <?php
                                    // Lógica visual VRA
                                    $text_vra = 'VRA';
                                    if ($status_vra === 'N/A') {
                                        $color_vra = 'bg-gray-200 text-gray-500'; $icon_vra = '<i class="fas fa-ban"></i>'; $text_vra = 'VRA N/A';
                                    } else {
                                        if ($status_vra === 'En Proceso') { $color_vra = 'bg-orange-100 text-orange-800'; $icon_vra = '<i class="fas fa-hourglass-half"></i>'; $text_vra = 'En Proceso VRA'; }
                                        elseif ($status_vra === 'Aprobado Total VRA') { $color_vra = 'bg-green-100 text-green-800'; $icon_vra = '<i class="fas fa-check-circle"></i>'; $text_vra = 'Finalizado VRA'; }
                                        elseif ($status_vra === 'Rechazado Total VRA') { $color_vra = 'bg-red-100 text-red-800'; $icon_vra = '<i class="fas fa-times-circle"></i>'; $text_vra = 'Finalizado Rechazado VRA'; }
                                        elseif ($status_vra === 'Finalizado Mixto VRA') { $color_vra = 'bg-blue-100 text-blue-800'; $icon_vra = '<i class="fas fa-check-double"></i>'; $text_vra = 'Finalizado Mixto VRA'; }
                                        else { $color_vra = 'bg-green-100 text-green-800'; $icon_vra = '<i class="fas fa-check-circle"></i>'; $text_vra = 'Finalizado VRA'; }
                                    }
                                    ?>
                                    <span title="Estado VRA: <?= $status_vra ?>" class="px-2 py-0.5 text-xs font-bold rounded-full flex items-center space-x-1 <?= $color_vra ?>"><?= $icon_vra ?> <span><?= $text_vra ?></span></span>
                                </div>
                                
                                <?php  
                                list($codigo, $fecha_str) = explode(" ", $oficio_fecha);
                                $fecha = DateTime::createFromFormat("Y-m-d", $fecha_str);
                                $meses = ['01'=>'ene.', '02'=>'feb.', '03'=>'mar.', '04'=>'abr.', '05'=>'may.', '06'=>'jun.', '07'=>'jul.', '08'=>'ago.', '09'=>'sept.', '10'=>'oct.', '11'=>'nov.', '12'=>'dic.'];
                                $dia = (int)$fecha->format('d');
                                $mes = $meses[$fecha->format('m')];
                                $anio = $fecha->format('Y');
                                $codigo_negrita = "<strong>" . htmlspecialchars($codigo) . "</strong>";
                                $fecha_normal = "<span class=\"font-normal\">($dia de $mes de $anio)</span>";
                                ?>
                            </div>
                            <p class="text-lg text-gray-800 my-2 truncate" title="<?= htmlspecialchars($oficio_fecha) ?>">
                                <?= $codigo_negrita ?> <?= $fecha_normal ?>
                            </p>
                        </div>

                        <div class="flex space-x-2 mt-4">
                            <button data-oficio="<?= htmlspecialchars($oficio_fecha) ?>" 
                                    class="ver-detalles-btn flex-grow bg-[#003366] hover:bg-[#002244] text-white font-bold py-2 px-4 rounded-md transition-colors duration-200 text-sm flex items-center justify-center">
                                <i class="fas fa-eye mr-2"></i> Ver Solicitudes
                            </button>

                            <?php 
                            $partes_oficio = explode(" ", $oficio_fecha);
                            $solo_num_oficio = $partes_oficio[0];
                            $solo_fecha_oficio = $partes_oficio[1] ?? date('Y-m-d');
                            ?>

                            <a href="reimprimir_oficio_depto_novedad.php?num_oficio=<?= urlencode($solo_num_oficio) ?>&departamento_id=<?= urlencode($id_departamento) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>&nombre_fac=<?= urlencode($nombre_facultad_user ?? 'Facultad') ?>&fecha_oficio=<?= urlencode($solo_fecha_oficio) ?>" 
                               title="Reimprimir Oficio Remisorio del deparamento"
                               class="w-10 flex-none bg-gray-200 hover:bg-gray-300 text-gray-700 flex items-center justify-center rounded-md border border-gray-300 transition-colors">
                                <i class="fas fa-print"></i>
                            </a>

                            <?php if ($acta_id_para_descarga): ?>
                            <a href="generar_word_novedades.php?id_acta=<?= $acta_id_para_descarga ?>&departamento_id=<?= urlencode($id_departamento) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>" 
                               title="Descargar Acta FOR59 (<?= htmlspecialchars($tooltip_acta) ?>)"
                               class="flex items-center gap-2 px-3 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 rounded-md border border-yellow-300 transition-colors text-sm font-medium">
                                <i class="fas fa-file-contract text-yellow-600"></i>
                                <span>FOR59</span>
                            </a>
                        <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (!empty($todas_las_solicitudes)): ?>
                    <div class="col-span-full text-center p-10 bg-blue-50 border-2 border-dashed border-blue-200 rounded-lg">
                        <h3 class="text-xl font-semibold text-blue-800">Pendiente Enviar Oficio</h3>
                        <p class="text-blue-600 mt-2">Existen novedades guardadas que aún no han sido enviadas a la Facultad.</p>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 col-span-full text-center p-10">No se encontraron novedades para este periodo.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

        <?php elseif ($tipo_usuario == 2): ?>
           
            
      <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
    <div class="flex justify-between items-center">
        
        <div class="flex items-center space-x-6">
            <span class="font-semibold text-gray-700">Filtrar por estado:</span>
            <div class="flex items-center">
                <input type="radio" id="filtro_todos" name="filtro_estado" value="todos" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" checked>
                <label for="filtro_todos" class="ml-2 block text-sm text-gray-900">
                    Todos
                </label>
            </div>
            <div class="flex items-center">
                <input type="radio" id="filtro_pendientes" name="filtro_estado" value="pendientes" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <label for="filtro_pendientes" class="ml-2 block text-sm text-gray-900">
                    Ver solo con pendientes de trámite en Facultad
                </label>
            </div>
        </div>

   <a href="exportar_excel_novedades.php?anio_semestre=<?php echo urlencode($anio_semestre); ?>" 
   class="inline-flex items-center px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-lg shadow hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors no-underline"
   target="_blank">
    <i class="fas fa-file-excel mr-2"></i>
    Exportar a Excel
</a>



    </div>
</div>
            <div class="space-y-4" id="lista-departamentos">
                <?php if (!empty($datos_agrupados_facultad)): ?>
                    <?php foreach ($datos_agrupados_facultad as $nombre_depto => $oficios_depto): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <button class="accordion-header w-full text-left py-3 px-6 flex justify-between items-center hover:bg-gray-50 focus:outline-none">
                                <span class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($nombre_depto); ?></span>
                                <svg class="w-6 h-6 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="accordion-body hidden p-2 bg-gray-50 border-t border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center p-10">No hay novedades enviadas por los departamentos de esta facultad.</p>
                <?php endif; ?>
            </div>
            
            <div id="mensaje-no-pendientes" class="hidden text-center mt-8 p-10 bg-green-50 border-2 border-dashed border-green-200 rounded-lg">
                <h3 class="text-2xl font-semibold text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>¡Estás al día!
                </h3>
                <p class="text-green-700 mt-2">No se encontraron oficios con estado 'Pendiente' en ningún departamento.</p>
            </div>
        </div>
           <div class="mt-8 bg-white rounded-lg border border-gray-200 shadow-sm opacity-95">
                <div class="px-5 py-3.5 flex justify-between items-center cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors rounded-t-lg" 
                     onclick="document.getElementById('historico-content').classList.toggle('hidden'); document.getElementById('historico-icon').classList.toggle('rotate-180');">
                    <h2 class="text-lg font-semibold text-gray-600">
                        <i class="fas fa-history text-gray-400 mr-2"></i>Histórico de Oficios Generados por Decanatura hacia Vicerrectoría Académica
                    </h2>
                    <i id="historico-icon" class="fas fa-chevron-down text-gray-400 transition-transform duration-300 text-base"></i>
                </div>
                
                <div id="historico-content" class="hidden px-5 pb-5 overflow-x-auto border-t border-gray-200 pt-3">
                    <table class="min-w-full divide-y divide-gray-200 table-auto">
                        <thead class="bg-transparent">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"># Oficio</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Departamentos Incluidos</th>
                                <th scope="col" class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Novedades</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Elaborado por</th>
                                <th scope="col" class="px-4 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (!empty($historico_oficios)): ?>
                                <?php foreach ($historico_oficios as $hist): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-2 whitespace-nowrap text-sm font-semibold text-gray-600">
                                            <?= htmlspecialchars($hist['oficio_fac'] ?? 'S/N') ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($hist['fecha_oficio_fac'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($hist['departamentos_incluidos'] ?? '') ?>">
                                            <?= htmlspecialchars($hist['departamentos_incluidos'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center">
                                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                                <?= htmlspecialchars($hist['cantidad_novedades']) ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($hist['elaborado_por'] ?? '') ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm font-medium">
                                            <a href="reimpr_novedades_fac.php?facultad_id=<?= urlencode($id_facultad) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>&oficio_fac=<?= urlencode($hist['oficio_fac']) ?>" 
                                               target="_blank"
                                               title="Reimprimir Oficio"
                                               class="inline-flex items-center px-2.5 py-1 bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-700 rounded transition-colors border border-gray-300 shadow-sm text-xs">
                                                <i class="fas fa-print mr-1"></i> Reimprimir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-400">
                                        No se han generado oficios de facultad en este periodo.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php endif; ?>
    
        
    </div>

        <div id="detailsModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 h-full w-full hidden z-50">
            <div class="relative top-40 mx-auto p-5 border w-11/12 lg:w-4/5 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-2xl font-semibold" id="modalTitle"></h3>
                    <button id="closeModalBtn" class="text-black text-3xl hover:text-gray-600">&times;</button>
                </div>
                <div class="mt-3 overflow-y-auto" style="max-height: 70vh;">
                    <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <?php if ($tipo_usuario == 2): // Condición: Solo mostrar para Facultad ?>
                            <th rowspan="2" class="px-4 py-2 text-center align-middle">
                                <input type="checkbox" id="selectAllCheckbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </th>
                            <?php endif; ?>

                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Novedad</th>
                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Justificación</th>
                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Nombre</th>
                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Cédula</th>
                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Tipo</th>
                            <th colspan="2" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase">Dedicación</th>
                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Rta. Facultad</th>
                             <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">
            Detalle Fac
        </th>
                            <th rowspan="2" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase align-middle">Rta. VRA</th>
                        </tr>
                        <tr>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase bg-gray-100">Pop</th>
                            <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase bg-gray-100">Reg</th>
                        </tr>
                    </thead>
                                            <tbody id="modalTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                </div>
            </div>
        </div>
<div id="actionPanel" class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t border-gray-200 transform translate-y-full transition-transform duration-300 ease-in-out z-50">
            <div class="container mx-auto p-4">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <span class="font-bold text-lg text-gray-800" id="selectionCount">0</span>
                        <span class="text-gray-600">solicitudes seleccionadas</span>
                    </div>
                    <div>
                           <button id="btn-limpiar-seleccion" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                                <i class="fas fa-eraser mr-1"></i> Limpiar Selección
                        </button>
                        <button id="btn-avalar-seleccionados" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                            Avalar Seleccionados
                        </button>
                        <button id="btn-no-avalar-seleccionados" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md transition duration-300 ml-2">
                            No Avalar Seleccionados
                        </button>
                    </div>
                </div>
                <div id="selectionList" class="max-h-32 overflow-y-auto border-t border-gray-200 pt-2 space-y-1">
                    </div>
            </div>
        </div>
    
    
    
    <div id="wordGenModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 h-full w-full hidden z-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Generar Oficio de Facultad</h2>
        <p class="text-sm text-gray-600 mb-4">Las solicitudes han sido AVALADAS. Por favor, completa los siguientes datos para generar el documento oficial.</p>
        
        <form id="wordGenForm" action="generar_word_solicitudes_seleccion.php" method="POST" target="_blank">
            <input type="hidden" id="wordGenSelectedIds" name="selected_ids_for_word" value="">
            <input type="hidden" id="wordGenAnioSemestre" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" id="wordGenIdFacultad" name="id_facultad" value="<?php echo htmlspecialchars($id_facultad); ?>">

            <div class="mb-4">
                <label for="oficio" class="block text-gray-700 text-sm font-bold mb-2">Número de Oficio:</label>
                <input type="text" id="oficio" name="oficio" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($trd_fac) ?>" required>

            </div>
            <div class="mb-4">
                <label for="fecha_oficio" class="block text-gray-700 text-sm font-bold mb-2">Fecha Oficio:</label>
                <input type="date" id="fecha_oficio" name="fecha_oficio" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
            </div>
         
            <div class="mb-4">
                <label for="decano" class="block text-gray-700 text-sm font-bold mb-2">Decano(a):</label>
                <input type="text" id="decano" name="decano" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($nombre_decano) ?>" required>
            </div>
            <div class="mb-4">
                <label for="elaborado_por" class="block text-gray-700 text-sm font-bold mb-2">Elaborado por:</label>
                <input type="text" id="elaborado_por" name="elaborado_por" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"  required>
            </div>
            <div class="mb-6">
                <label for="folios" class="block text-gray-700 text-sm font-bold mb-2">Folios:</label>
                <input type="number" id="folios" name="folios" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" min="1" required>
            </div>
            
            <div class="flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-file-word mr-2"></i>
                    Generar Oficio y Finalizar
                </button>
            </div>
        </form>
    </div>
</div>
    
    
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-800 bg-opacity-75 h-full w-full hidden z-50 flex items-center justify-center" style="z-index: 100;">
    <div class="bg-white rounded-lg p-8 flex items-center space-x-4 shadow-xl">
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="loadingMessage" class="text-lg font-semibold text-gray-700">Procesando...</span>
    </div>
</div>

<script>
    // --- 1. DECLARACIÓN DE VARIABLES Y ELEMENTOS DEL DOM ---
    const tipoUsuario = <?php echo json_encode($tipo_usuario); ?>;
    const anioSemestre = <?php echo json_encode($anio_semestre); ?>; // Guardamos el año/semestre

    let todasLasSolicitudes;

    const modal = document.getElementById('detailsModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalTableBody = document.getElementById('modalTableBody');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const actionPanel = document.getElementById('actionPanel');
    const selectionCount = document.getElementById('selectionCount');
    const selectionList = document.getElementById('selectionList');

    let solicitudesSeleccionadas = [];

    // --- 2. DEFINICIÓN DE TODAS LAS FUNCIONES AUXILIARES ---

function actualizarPanelDeAcciones() {
    // Esta función solo se ejecuta para el usuario de Facultad (tipo 2)
    if (tipoUsuario != 2) return;

    const count = solicitudesSeleccionadas.length;
    selectionCount.textContent = count;
    selectionList.innerHTML = ''; // Siempre limpiamos la lista para reconstruirla

    if (count > 0) {
        // --- MOSTRAR EL PANEL Y AÑADIR ESPACIO ---
        actionPanel.classList.remove('translate-y-full');

        // Usamos un pequeño delay para que el navegador calcule la altura del panel después de que sea visible
        setTimeout(() => {
            document.body.style.paddingBottom = actionPanel.offsetHeight + 'px';
        }, 300); // 300ms coincide con la duración de la transición de entrada del panel

        // --- CONSTRUIR LA LISTA DETALLADA DE SELECCIONADOS ---
        solicitudesSeleccionadas.forEach(id => {
            const sol = todasLasSolicitudes.find(s => s.id_solicitud == id);
            if (sol) {
                let dedicacion = '';
                if (sol.tipo_docente === 'Ocasional') {
                    dedicacion = sol.tipo_dedicacion || sol.tipo_dedicacion_r || '';
                } else if (sol.tipo_docente === 'Catedra') {
                    dedicacion = (sol.horas > 0 ? `${sol.horas}h Pop` : '') + (sol.horas_r > 0 ? ` ${sol.horas_r}h Reg` : '');
                }
                const tipoDocenteDisplay = sol.tipo_docente === 'Catedra' ? 'Cátedra' : sol.tipo_docente;

                const itemHtml = `
                    <div class="flex items-center text-xs p-1.5 bg-gray-50 rounded space-x-2">
                        <span class="font-bold text-blue-800 w-1/4 truncate" title="${sol.nombre_departamento}">${sol.nombre_departamento}</span>
                        <span class="font-medium text-gray-600 w-1/4 truncate" title="${sol.oficio_con_fecha}">${sol.oficio_con_fecha}</span>
                        <span class="font-semibold text-gray-800 w-1/4 truncate" title="${sol.novedad}">${sol.novedad}</span>
                        <span class="text-gray-700 w-1/2 truncate" title="${sol.nombre}">${sol.nombre}</span>
                        <span class="text-gray-600 w-1/4 text-right truncate" title="${tipoDocenteDisplay} ${dedicacion}">${tipoDocenteDisplay} ${dedicacion}</span>
                    </div>`;
                selectionList.innerHTML += itemHtml;
            }
        });
    } else {
        // --- OCULTAR EL PANEL Y QUITAR EL ESPACIO ---
        actionPanel.classList.add('translate-y-full');
        document.body.style.paddingBottom = '0px';
    }

    // Actualizar el estado del checkbox "Seleccionar Todo" (si existe)
    if (selectAllCheckbox) {
        const checkboxesVisibles = modalTableBody.querySelectorAll('.solicitud-checkbox');
        const todosSeleccionados = checkboxesVisibles.length > 0 && Array.from(checkboxesVisibles).every(cb => cb.checked);
        selectAllCheckbox.checked = todosSeleccionados;
    }
}
    
    function limpiarSeleccion() {
    // 1. Vaciar el array de selección
    solicitudesSeleccionadas = [];

    // 2. Desmarcar visualmente todas las casillas en el modal
    modalTableBody.querySelectorAll('.solicitud-checkbox:checked').forEach(cb => {
        cb.checked = false;
        cb.closest('tr').classList.remove('bg-blue-50');
    });

    // 3. Actualizar el panel (esto lo ocultará y pondrá el contador a cero)
    actualizarPanelDeAcciones();
}
    // El resto de las funciones (manejarClickCheckbox, crearEtiquetaEstado, llenarModal) se mantienen igual
    function manejarClickCheckbox(checkbox) {
        const id = parseInt(checkbox.dataset.id);
        const fila = checkbox.closest('tr');
        if (checkbox.checked) {
            if (!solicitudesSeleccionadas.includes(id)) solicitudesSeleccionadas.push(id);
            fila.classList.add('bg-blue-50');
        } else {
            solicitudesSeleccionadas = solicitudesSeleccionadas.filter(selId => selId !== id);
            fila.classList.remove('bg-blue-50');
        }
        actualizarPanelDeAcciones();
    }

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

function llenarModal(oficio) {
    // Limpiamos la tabla antes de llenarla
    modalTableBody.innerHTML = '';
    
    // 1. Primero filtramos las solicitudes
    const solicitudesFiltradas = todasLasSolicitudes.filter(sol => sol.oficio_con_fecha === oficio);

    // 2. Extraemos el nombre del departamento (si hay resultados)
    let nombreDepartamento = '';
    if (solicitudesFiltradas.length > 0) {
        // Extraemos el nombre. Si por alguna razón no viene en el JSON, evitamos que diga "undefined"
        const nombre = solicitudesFiltradas[0].nombre_departamento || solicitudesFiltradas[0].depto_nom_propio || '';
        if (nombre !== '') {
            nombreDepartamento = nombre + ' - ';
        }
    }

    // 3. Asignamos el título dinámico con el nombre y el oficio
    modalTitle.textContent = 'Solicitudes según Oficio del Departamento: ' + nombreDepartamento + oficio;

    // ===================================================================
    // ===== INICIA EL BLOQUE MODIFICADO: LÓGICA DE ORDENAMIENTO =====
    // ===================================================================
    const ordenNovedades = ['modificacion', 'cambio de vinculacion', 'adicionar', 'eliminar'];

    solicitudesFiltradas.sort((a, b) => {
        const novedadA = (a.novedad || '').toLowerCase();
        const novedadB = (b.novedad || '').toLowerCase();

        const indexA = ordenNovedades.indexOf(novedadA) !== -1 ? ordenNovedades.indexOf(novedadA) : 999;
        const indexB = ordenNovedades.indexOf(novedadB) !== -1 ? ordenNovedades.indexOf(novedadB) : 999;

        return indexA - indexB;
    });
    // ===================================================================
    // ===== FIN DEL BLOQUE MODIFICADO =====
    // ===================================================================

    if (solicitudesFiltradas.length > 0) {
        solicitudesFiltradas.forEach(sol => {
            const id = parseInt(sol.id_solicitud);
            const isChecked = solicitudesSeleccionadas.includes(id);

            const isProcessed = sol.estado_facultad !== 'PENDIENTE';
            const disabledAttribute = isProcessed ? 'disabled' : '';
            const rowClass = isProcessed ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : (isChecked ? 'bg-blue-50' : '');

            let checkboxHtml = '';
            if (tipoUsuario == 2) {
                checkboxHtml = `<td class="px-4 py-2 text-center"><input type="checkbox" data-id="${id}" class="solicitud-checkbox h-4 w-4 border-gray-300 rounded focus:ring-blue-500" ${isChecked ? 'checked' : ''} ${disabledAttribute}></td>`;
            }

            let popayanData = '<span class="text-gray-400">N/A</span>';
            let regionalizacionData = '<span class="text-gray-400">N/A</span>';
            if (sol.tipo_docente === 'Ocasional') {
                if (sol.tipo_dedicacion) popayanData = `<span class="bg-gray-200 px-2 py-1 rounded">${sol.tipo_dedicacion}</span>`;
                if (sol.tipo_dedicacion_r) regionalizacionData = `<span class="bg-gray-200 px-2 py-1 rounded">${sol.tipo_dedicacion_r}</span>`;
            } else if (sol.tipo_docente === 'Catedra') {
                if (sol.horas && sol.horas > 0) popayanData = `<span class="bg-blue-100 px-2 py-1 rounded">${sol.horas} hrs</span>`;
                if (sol.horas_r && sol.horas_r > 0) regionalizacionData = `<span class="bg-blue-100 px-2 py-1 rounded">${sol.horas_r} hrs</span>`;
            }
            
            const tipoDocenteDisplay = (sol.tipo_docente === 'Catedra') ? 'Cátedra' : sol.tipo_docente;
            const estadoFacultadHtml = crearEtiquetaEstado(sol.estado_facultad, sol.observacion_facultad, 'facultad');
            let estadoVraHtml;

            if (sol.estado_facultad === 'RECHAZADO') {
                estadoVraHtml = `<span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700" title="No aplica para trámite en VRA, fue devuelta por la Facultad.">--</span>`;
            } else {
                estadoVraHtml = crearEtiquetaEstado(sol.estado_vra, sol.observacion_vra, 'vra');
            }

            let detalleFacultadHtml = '<td class="px-6 py-2 whitespace-nowrap text-gray-700">--</td>';
            if (sol.estado_facultad === 'APROBADO') {
                const oficioFac = sol.oficio_con_fecha_fac || 'No asignado';
                detalleFacultadHtml = `<td class="px-6 py-2 whitespace-nowrap text-xs text-gray-600" title="Oficio Facultad: ${oficioFac}">Avalado Oficio: ${oficioFac}</td>`;
            } else if (sol.estado_facultad === 'RECHAZADO') {
                const observacionFac = sol.observacion_facultad || 'Sin justificación.';
                detalleFacultadHtml = `<td class="px-6 py-2 whitespace-normal max-w-xs break-words text-xs text-red-700 font-semibold" title="${observacionFac}">${observacionFac}</td>`;
            }

            // Lógica para mostrar novedades
            let novedadDisplay = sol.novedad || '';
            const novedadLower = novedadDisplay.toLowerCase();
            
            if (novedadLower.includes('modificar') && !novedadLower.includes('vinculación') && !novedadLower.includes('vinculacion')) {
                novedadDisplay = 'Modificar Dedicación';
            } else if (novedadLower === 'modificacion') {
                novedadDisplay = 'Cambio de dedicacion';
            }

            const filaHTML = `<tr class="${rowClass}">
                ${checkboxHtml}
                <td class="px-6 py-2 whitespace-nowrap">${novedadDisplay}</td>
                <td class="px-6 py-2 whitespace-normal max-w-xs break-words text-gray-700">${sol.s_observacion || ''}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${sol.nombre}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${sol.cedula}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${tipoDocenteDisplay}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${popayanData}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${regionalizacionData}</td>
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${estadoFacultadHtml}</td>
                ${detalleFacultadHtml}
                <td class="px-6 py-2 whitespace-nowrap text-gray-700">${estadoVraHtml}</td>
            </tr>`;
            modalTableBody.innerHTML += filaHTML;
        });
    } else {
        const colspan = (tipoUsuario == 2) ? 11 : 10;
        modalTableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-4">No se encontraron solicitudes para este oficio.</td></tr>`;
    }

    if (tipoUsuario == 2) {
        modalTableBody.querySelectorAll('.solicitud-checkbox').forEach(cb => {
            cb.addEventListener('change', () => manejarClickCheckbox(cb));
        });
    }

    actualizarPanelDeAcciones();
    
    // Asegúrate de que la variable "modal" esté definida globalmente en tu código. 
    // Si se llama diferente (ej: document.getElementById('miModal')), debes ajustarlo aquí.
    modal.classList.remove('hidden');
}
    // --- 3. INICIALIZACIÓN ---
   // Lógica para cerrar el modal con el botón 'x'
    
       document.addEventListener('DOMContentLoaded', () => {
document.getElementById('closeModalBtn').addEventListener('click', () => {
    modal.classList.add('hidden');
});

// ===== ¡NUEVO! Lógica para cerrar el modal al hacer clic fuera =====
modal.addEventListener('click', (event) => {
    // Si el elemento donde se hizo clic (event.target) es el fondo del modal mismo...
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
});
    if (tipoUsuario == 3) {
        todasLasSolicitudes = <?php echo $solicitudes_json; ?>;
        document.querySelectorAll('.ver-detalles-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                llenarModal(btn.dataset.oficio);
            });
        });
    }

  if (tipoUsuario == 2) {
    // --- 1. REFERENCIAS A DATOS Y ELEMENTOS GLOBALES ---
    todasLasSolicitudes = <?php echo $solicitudes_json; ?>;
    const datosAgrupados = <?php echo json_encode($datos_agrupados_facultad ?? []); ?>;
    const statusesFacultad = <?php echo $statuses_json; ?>;
    const filtroRadios = document.querySelectorAll('input[name="filtro_estado"]');

    // --- 2. FUNCIÓN CENTRAL PARA DIBUJAR LAS TARJETAS (¡LA NUEVA LÓGICA!) ---
function renderizarContenidoAcordeon(headerElement) {
    const deptoName = headerElement.querySelector('span').textContent;
    const body = headerElement.nextElementSibling;
    const cardsContainer = body.querySelector('.grid');
    cardsContainer.innerHTML = ''; // Limpiamos el contenido anterior

    const oficiosDepto = datosAgrupados[deptoName];
    const statusesDepto = statusesFacultad[deptoName];
    const filtroSeleccionado = document.querySelector('input[name="filtro_estado"]:checked').value;

    if (!oficiosDepto || Object.keys(oficiosDepto).length === 0) {
        cardsContainer.innerHTML = '<p class="text-gray-500 col-span-full">Este departamento no tiene oficios enviados.</p>';
        return;
    }

    // Verificar si este departamento tiene pendientes para mantener el resaltado
    let tienePendientes = false;
    if (statusesDepto) {
        for (const oficio in statusesDepto) {
            if (statusesDepto[oficio].facultad === 'En Proceso') {
                tienePendientes = true;
                break;
            }
        }
    }
    
    if (tienePendientes) {
        headerElement.classList.add('bg-pink-highlight');
    } else {
        headerElement.classList.remove('bg-pink-highlight');
    }

    let tarjetasRenderizadas = 0;
    for (const oficio in oficiosDepto) {
        const statusOficio = statusesDepto[oficio];
        if (filtroSeleccionado === 'pendientes' && (!statusOficio || statusOficio.facultad !== 'En Proceso')) {
            continue;
        }

        tarjetasRenderizadas++;

        // Obtener las solicitudes de este oficio para buscar el acta
        const solicitudesOficio = oficiosDepto[oficio];
        let idActa = null;
        let numeroActa = null;
        let deptoId = null;
        if (solicitudesOficio.length > 0) {
            deptoId = solicitudesOficio[0].departamento_id; // todas comparten el mismo
            const solicitudConActa = solicitudesOficio.find(s => s.id_acta_vinculada);
            if (solicitudConActa) {
                idActa = solicitudConActa.id_acta_vinculada;
                numeroActa = solicitudConActa.numero_acta59; // CORRECCIÓN: variable correcta de la BD
            }
        }

        // Dividir el número de oficio y la fecha para los enlaces
        const { codigo, fechaFormateada } = dividirOficio(oficio);
        const partesOficio = oficio.split(" ");
        const fechaOficioRaw = partesOficio[1] || '';

        // 1. CONSTRUIR BOTÓN DE REIMPRIMIR OFICIO (Color Blanco/Gris)
        const botonReimprimirHtml = `
            <a href="reimprimir_oficio_depto_novedad.php?num_oficio=${encodeURIComponent(codigo)}&departamento_id=${deptoId}&anio_semestre=${encodeURIComponent(anioSemestre)}&fecha_oficio=${encodeURIComponent(fechaOficioRaw)}" 
               title="Reimprimir Oficio Remisorio del departamento"
               class="w-10 flex-none bg-white hover:bg-gray-100 text-gray-700 flex items-center justify-center rounded-md border border-gray-300 transition-colors shadow-sm">
                <i class="fas fa-print"></i>
            </a>`;

        // 2. CONSTRUIR BOTÓN DEL ACTA FOR59 (Color Amarillo, solo si existe)
        let botonActaHtml = '';
        if (idActa) {
            botonActaHtml = `
                <a href="generar_word_novedades.php?id_acta=${idActa}&departamento_id=${deptoId}&anio_semestre=${encodeURIComponent(anioSemestre)}" 
                   title="Descargar Acta FOR59 (Acta N° ${numeroActa || 'S/N'})"
                   class="flex items-center gap-2 px-3 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 rounded-md border border-yellow-300 transition-colors text-sm font-medium shadow-sm">
                    <i class="fas fa-file-contract text-yellow-600"></i>
                    <span class="hidden xl:inline">FOR59</span>
                </a>`;
        }

        const statusObj = statusOficio || { facultad: 'Desconocido', vra: 'Desconocido' };
        const status_fac = statusObj.facultad;
        const borderColorClass = (status_fac === 'En Proceso') ? 'border-red-300' : 'border-[#003366]';

        // Colores para facultad
        let color_fac = 'bg-gray-200 text-gray-700', icon_fac = '<i class="fas fa-eye"></i>', text_fac = 'Facultad';
        if (status_fac === 'En Proceso') { color_fac = 'bg-orange-100 text-orange-800'; icon_fac = '<i class="fas fa-hourglass-half"></i>'; text_fac = 'En Proceso Facultad'; }
        else if (status_fac === 'Aprobado Total') { color_fac = 'bg-green-100 text-green-800'; icon_fac = '<i class="fas fa-check"></i>'; text_fac = 'Tramitado OK Facultad'; }
        else if (status_fac === 'Rechazado Total') { color_fac = 'bg-red-100 text-red-800'; icon_fac = '<i class="fas fa-times"></i>'; text_fac = 'No Avalado por Facultad'; }
        else if (status_fac === 'Finalizado Mixto') { color_fac = 'bg-blue-100 text-blue-800'; icon_fac = '<i class="fas fa-check"></i>'; text_fac = 'Tramitado (incluye devolución)'; }

        // Colores para VRA
        const status_vra = statusObj.vra;
        let color_vra, icon_vra, text_vra;
        if (status_vra === 'N/A') {
            color_vra = 'bg-gray-200 text-gray-500'; icon_vra = '<i class="fas fa-ban"></i>'; text_vra = 'VRA N/A';
        } else if (status_vra === 'En Proceso') {
            color_vra = 'bg-orange-100 text-orange-800'; icon_vra = '<i class="fas fa-hourglass-half"></i>'; text_vra = 'En Proceso VRA';
        } else if (status_vra === 'Aprobado Total VRA' || status_vra === 'Finalizado VRA' || status_vra === 'Finalizado') {
            color_vra = 'bg-green-100 text-green-800'; icon_vra = '<i class="fas fa-check-circle"></i>'; text_vra = 'Finalizado VRA';
        } else if (status_vra === 'Rechazado Total VRA') {
            color_vra = 'bg-red-100 text-red-800'; icon_vra = '<i class="fas fa-times-circle"></i>'; text_vra = 'Rechazado VRA';
        } else if (status_vra === 'Finalizado Mixto VRA') {
            color_vra = 'bg-blue-100 text-blue-800'; icon_vra = '<i class="fas fa-check-double"></i>'; text_vra = 'Finalizado Mixto VRA';
        }

        // Tarjeta final inyectando todos los botones
        const cardHtml = `
<div class="bg-[#F0F4F9] rounded-lg shadow-md p-6 border-l-4 ${borderColorClass} flex flex-col justify-between oficio-card" data-depto="${deptoName}">
    <div>
        <div class="flex justify-between items-start mb-2">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Oficio Departamento</h3>
            <div class="flex flex-col items-end space-y-1">
                <span title="${status_fac}" class="px-2 py-0.5 text-xs font-bold rounded-full flex items-center space-x-1 ${color_fac}">${icon_fac} <span>${text_fac}</span></span>
                <span title="${status_vra}" class="px-2 py-0.5 text-xs font-bold rounded-full flex items-center space-x-1 ${color_vra}">${icon_vra} <span>${text_vra}</span></span>
            </div>
        </div>
        <p class="text-lg text-gray-800 my-2 truncate" title="${codigo} ${fechaFormateada}">
            <strong>${codigo}</strong> <span class="font-normal">${fechaFormateada}</span>
        </p>
    </div>
    
    <div class="flex space-x-2 mt-4">
        <button data-oficio="${oficio}" class="ver-detalles-btn flex-grow bg-[#003366] hover:bg-[#002244] text-white font-bold py-2 px-4 rounded-md transition-colors duration-200 text-sm flex items-center justify-center">
            <i class="fas fa-eye mr-2"></i> Ver
        </button>
       <!-- ${botonReimprimirHtml} -->
        ${botonActaHtml}
    </div>
</div>`;
        cardsContainer.innerHTML += cardHtml;
    }

    if (tarjetasRenderizadas === 0) {
        cardsContainer.innerHTML = `<p class="text-gray-500 col-span-full text-center py-4">No se encontraron oficios con estado 'Pendiente' en este departamento.</p>`;
    }
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
// --- FUNCIÓN PARA APLICAR EL FILTRO GLOBAL (VERSIÓN FINAL Y COMPLETA) ---
function aplicarFiltroGlobal() {
    // 1. Seleccionamos los elementos necesarios del DOM
    const todosLosContenedores = document.querySelectorAll('#lista-departamentos > .bg-white.rounded-lg');
    const filtroSeleccionado = document.querySelector('input[name="filtro_estado"]:checked').value;
    const mensajeNoPendientes = document.getElementById('mensaje-no-pendientes');
    let departamentosVisibles = 0;

    // 2. Recorremos cada contenedor de departamento para decidir si se muestra o no
    todosLosContenedores.forEach(contenedor => {
        const header = contenedor.querySelector('.accordion-header');
        const body = header.nextElementSibling;
        const deptoName = header.querySelector('span').textContent;
        const statusesDepto = statusesFacultad[deptoName];
        let tienePendientes = false;

        // 3. Verificamos si el departamento tiene algún oficio pendiente
        if (statusesDepto) {
            for (const oficio in statusesDepto) {
                if (statusesDepto[oficio].facultad === 'En Proceso') {
                    tienePendientes = true;
                    break;
                }
            }
        }
        
        // 4. Aplicamos o quitamos el resaltado rosa (lógica que ya tenías)
        // (Asegúrate de tener una clase CSS como bg-pink-50 o la que uses para 'bg-pink-highlight')
        if (tienePendientes) {
            header.classList.add('bg-pink-50'); 
        } else {
            header.classList.remove('bg-pink-50');
        }

        // 5. Lógica principal para mostrar u ocultar el departamento completo
        if (filtroSeleccionado === 'pendientes' && !tienePendientes) {
            contenedor.style.display = 'none';
        } else {
            contenedor.style.display = 'block';
            departamentosVisibles++;
        }

        // 6. Si un acordeón está abierto, actualizamos su contenido interno
        if (!body.classList.contains('hidden')) {
            renderizarContenidoAcordeon(header);
        }
    });

    // 7. Lógica final para mostrar el mensaje de "Estás al día"
    // Si el filtro es 'pendientes' y no quedó ningún departamento visible, muestra el mensaje.
    if (filtroSeleccionado === 'pendientes' && departamentosVisibles === 0) {
        mensajeNoPendientes.classList.remove('hidden');
    } else {
        // En cualquier otro caso (filtro 'todos' o si hay pendientes visibles), lo oculta.
        mensajeNoPendientes.classList.add('hidden');
    }
}
    // --- 4. ASIGNACIÓN DE EVENTOS ---
    
    // Evento para abrir/cerrar un acordeón
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            
            // --- ESTE BLOQUE ES EL QUE CIERRA LOS DEMÁS ---
        document.querySelectorAll('.accordion-header').forEach(otherHeader => {
            if (otherHeader !== header) {
                otherHeader.nextElementSibling.classList.add('hidden');
                otherHeader.querySelector('svg').classList.remove('rotate-180');
            }
        });
        // --- FIN DEL BLOQUE A ELIMINAR ---
            const body = header.nextElementSibling;
            const icon = header.querySelector('svg');
            
            // Si el cuerpo está a punto de abrirse y está vacío, lo renderizamos.
            if (body.classList.contains('hidden') && body.querySelector('.grid').innerHTML.trim() === '') {
                renderizarContenidoAcordeon(header);
            }
            
            body.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        });
    });

    // Evento para los botones "Ver Solicitudes" (se asigna dinámicamente)
    document.querySelector('.space-y-4').addEventListener('click', function(event) {
        if (event.target && event.target.classList.contains('ver-detalles-btn')) {
             event.stopPropagation();
             llenarModal(event.target.dataset.oficio);
        }
    });

    // Evento para los radio buttons del filtro
    filtroRadios.forEach(radio => radio.addEventListener('change', aplicarFiltroGlobal));
    
    // --- 5. CÓDIGO PARA CHECKBOXES Y BOTONES DE ACCIÓN (MANTENER IGUAL) ---
    // ===== Lógica del checkbox "Seleccionar Todo" (MEJORADA) =====
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            // Seleccionamos solo los checkboxes que NO están deshabilitados
            const checkboxesVisibles = modalTableBody.querySelectorAll('.solicitud-checkbox:not(:disabled)');
            
            // El resto de la lógica es la versión eficiente que ya tenías
            const idsVisibles = Array.from(checkboxesVisibles).map(cb => parseInt(cb.dataset.id));

            if (selectAllCheckbox.checked) {
                idsVisibles.forEach(id => {
                    if (!solicitudesSeleccionadas.includes(id)) {
                        solicitudesSeleccionadas.push(id);
                    }
                });
                checkboxesVisibles.forEach(cb => {
                    cb.checked = true;
                    cb.closest('tr').classList.add('bg-blue-50');
                });
            } else {
                solicitudesSeleccionadas = solicitudesSeleccionadas.filter(id => !idsVisibles.includes(id));
                checkboxesVisibles.forEach(cb => {
                    cb.checked = false;
                    cb.closest('tr').classList.remove('bg-blue-50');
                });
            }
            actualizarPanelDeAcciones();
        });
    }
    
    // =========================================================================
    // ===== ¡NUEVO! LÓGICA PARA LOS BOTONES DE ACCIÓN =========================
    // =========================================================================
    
// =========================================================================
// LÓGICA UNIFICADA Y SEGURA (ESTADO + OFICIO + DESCARGA)
// =========================================================================

const btnAvalar = document.getElementById('btn-avalar-seleccionados');
const btnNoAvalar = document.getElementById('btn-no-avalar-seleccionados');
const wordGenModal = document.getElementById('wordGenModal');
const wordGenForm = document.getElementById('wordGenForm');

// ===== FUNCIÓN DE CARGA (Mantenemos la tuya) =====
function toggleLoading(show, message = 'Procesando...') {
    const overlay = document.getElementById('loadingOverlay');
    const messageEl = document.getElementById('loadingMessage');
    const actionButtons = [
        document.getElementById('btn-avalar-seleccionados'),
        document.getElementById('btn-no-avalar-seleccionados'),
        document.getElementById('btn-limpiar-seleccion')
    ];

    if (show) {
        messageEl.textContent = message;
        overlay.classList.remove('hidden');
        actionButtons.forEach(btn => btn && (btn.disabled = true));
    } else {
        overlay.classList.add('hidden');
        actionButtons.forEach(btn => btn && (btn.disabled = false));
    }
}

// ===== BOTÓN LIMPIAR =====
const btnLimpiar = document.getElementById('btn-limpiar-seleccion');
if (btnLimpiar) {
    btnLimpiar.addEventListener('click', limpiarSeleccion);
}

// =========================================================================
// 1. BOTÓN AVALAR (SOLO ABRE EL MODAL, NO GUARDA NADA AÚN)
// =========================================================================
if (btnAvalar) {
    btnAvalar.addEventListener('click', () => {
        if (solicitudesSeleccionadas.length === 0) {
            return alert('Por favor, seleccione al menos una solicitud para avalar.');
        }

        // A. Preparar datos visuales
        document.getElementById('wordGenSelectedIds').value = solicitudesSeleccionadas.join(',');
        document.getElementById('fecha_oficio').value = new Date().toISOString().split('T')[0];

        // B. Abrir modal
        wordGenModal.classList.remove('hidden');
    });
}

// =========================================================================
// 2. FORMULARIO DEL MODAL (HACE EL TRABAJO DURO: BD + WORD)
// =========================================================================
// =========================================================================
// 2. FORMULARIO DEL MODAL (VERSIÓN FINAL SIN MENSAJES MOLESTOS)
// =========================================================================
// =========================================================================
    // 2. FORMULARIO DEL MODAL (CORREGIDO PARA DETENERSE SI EXISTE DUPLICADO)
    // =========================================================================
    if (wordGenForm) {
        wordGenForm.onsubmit = async function(event) {
            // 1. Detener envío automático
            event.preventDefault();
            
            // 2. Bloquear pantalla para evitar doble clic
            toggleLoading(true, 'Verificando número de oficio...');

            const oficioInput = document.getElementById('oficio');
            const oficioValue = oficioInput.value.trim();
            const anioSemestre = document.getElementById('wordGenAnioSemestre').value;
            const idFacultad = document.getElementById('wordGenIdFacultad').value;

            // Validaciones visuales
            if (!oficioValue) {
                toggleLoading(false); // Desbloquear
                return alert('El número de oficio es obligatorio.');
            }

            try {
                // --- A. VERIFICAR DUPLICADO ---
                // Agregamos "&nocache=" para asegurar que la validación sea real y no de memoria
                const checkUrl = `verificar_oficio_fac.php?oficio=${encodeURIComponent(oficioValue)}&anio_semestre=${anioSemestre}&id_facultad=${idFacultad}&nocache=${Date.now()}`;
                
                const checkRes = await fetch(checkUrl);
                const checkData = await checkRes.json();

                // SI EXISTE: ALERTA Y FRENO TOTAL
                if (checkData.existe) {
                    toggleLoading(false); // <--- IMPORTANTE: Desbloqueamos la pantalla para que puedas corregir
                    alert('⛔ ERROR: Este número de oficio YA FUE REGISTRADO anteriormente.\n\nPor favor, cambie el consecutivo e intente de nuevo.');
                    return; // <--- AQUÍ SE DETIENE EL CÓDIGO. NO PASA A GUARDAR.
                }

                // --- B. SI NO EXISTE: GUARDAR EN BD ---
                document.getElementById('loadingMessage').textContent = 'Guardando y generando documento...';

                const formData = new FormData();
                formData.append('action', 'avalar');
                formData.append('anio_semestre', anioSemestre);
                formData.append('numero_oficio', oficioValue);
                formData.append('fecha_oficio', document.getElementById('fecha_oficio').value);
                formData.append('elaborado_por', document.getElementById('elaborado_por').value);
                
                const ids = document.getElementById('wordGenSelectedIds').value.split(',');
                ids.forEach(id => formData.append('selected_ids[]', id));

                const response = await fetch('./procesar_facultad_seleccion.php', { method: 'POST', body: formData });
                const responseText = await response.text();
                let data;
                try { data = JSON.parse(responseText); } catch(e) { throw new Error("Respuesta inválida del servidor."); }

                if (data.success) {
                    // --- C. ÉXITO: DESCARGAR WORD ---
                    showToast('¡Guardado exitoso! Descargando...', 3000);

                    setTimeout(() => {
                        this.action = 'generar_word_solicitudes_seleccion.php';
                        this.method = 'POST';
                        HTMLFormElement.prototype.submit.call(this); // Descarga forzada
                    }, 500);

                    // Recarga final
                    setTimeout(() => {
                        toggleLoading(false);
                        wordGenModal.classList.add('hidden');
                        window.location.href = window.location.href; 
                    }, 2500);

                } else {
                    throw new Error(data.message || 'Error al guardar.');
                }

            } catch (error) {
                console.error(error);
                toggleLoading(false); // Desbloquear siempre en caso de error
                
                const msg = error.message || '';
                // Ignorar errores de red por la descarga
                if (!msg.includes('Network') && !msg.includes('fetch')) {
                    alert('Atención: ' + msg);
                }
            }
        };
    }
// =========================================================================
// 3. BOTÓN NO AVALAR (ESTE SÍ ENVÍA DIRECTO)
// =========================================================================
if (btnNoAvalar) {
    btnNoAvalar.addEventListener('click', () => {
        if (solicitudesSeleccionadas.length === 0) return alert('Seleccione al menos una solicitud.');

        const observacion = prompt("Por favor, ingrese la justificación para el NO AVAL (obligatorio):");
        if (observacion === null) return;
        if (observacion.trim() === '') return alert('La justificación es obligatoria.');

        toggleLoading(true, 'Procesando devolución... Enviando correo...');

        const formData = new FormData();
        formData.append('action', 'no_avalar');
        formData.append('observacion', observacion);
        formData.append('anio_semestre', anioSemestre); // Variable global PHP/JS
        solicitudesSeleccionadas.forEach(id => formData.append('selected_ids[]', id));

        fetch('procesar_facultad_seleccion.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error técnico al procesar.');
        })
        .finally(() => {
            toggleLoading(false);
        });
    });
}

aplicarFiltroGlobal();
      
// ... justo después del cierre del `if (tipoUsuario == 2)`
}   
    });
    
      function showToast(message, duration = 3000) {
    const toast = document.getElementById('toast-notification');
    const toastMessage = document.getElementById('toast-message');

    if (toast) {
        // Ponemos el mensaje y añadimos la clase 'show' para hacerlo visible
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        // Creamos un temporizador para quitar la clase 'show' y ocultarlo de nuevo
        setTimeout(() => {
            toast.classList.remove('show');
        }, duration);
    }
}
</script>
   
</body>
</html>