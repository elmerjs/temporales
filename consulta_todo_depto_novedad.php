<?php

$active_menu_item = 'novedades';

require('include/headerz.php');
require 'funciones.php';
//require 'actualizar_usuario.php'; // <-- Incluir aquí
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
              <a href='index.html'>inicie sesión</a>
              </span>";
    exit(); // Detener toda la ejecución del script
}

    // Obtener los parámetros de la URL
$facultad_id = isset($_POST['facultad_id']) ? $_POST['facultad_id'] : null;
$anio_semestre = $_POST['anio_semestre'] ?? $_GET['anio_semestre'] ?? null;
$departamento_id = $_POST['departamento_id'] ?? $_GET['departamento_id'] ?? null;
/*
// --- INICIO DEPURACIÓN ---
echo "<h3>Valores de Parámetros Recibidos:</h3>";
echo "Año/Semestre: **" . htmlspecialchars($anio_semestre) . "** (Tipo: " . gettype($anio_semestre) . ")<br>";
echo "Departamento ID: **" . htmlspecialchars($departamento_id) . "** (Tipo: " . gettype($departamento_id) . ")<br><br>";
// --- FIN DEPURACIÓN ---
*/
 $aniose= $anio_semestre;
        $cierreperiodo = obtenerperiodo($anio_semestre);


require_once('conn.php'); // Asegúrate de que 'conn.php' esté en la ruta correcta

// El objeto $conn ya está disponible aquí gracias a conn.php
// Verificar si la conexión falló (aunque conn.php ya lo maneja con die(), es buena práctica)
if ($conn->connect_error) {
    die("Conexión fallida al incluir conn.php: " . $conn->connect_error);
}
// Validar que los parámetros esenciales estén presentes
if ($anio_semestre === null || $departamento_id === null) {
    echo "Error: Parámetros de año/semestre o departamento no proporcionados.";
    exit();
}

// --- Nombre de la tabla de trabajo (clon permanente) ---
$working_table_name = "solicitudes_working_copy";


// Función para obtener datos filtrados por tipo de novedad
// ====================================================================================
// ===== PASO 1: REEMPLAZA TU FUNCIÓN 'fetchData' Y SUS LLAMADAS POR ESTE BLOQUE =====
// ====================================================================================
// ====================================================================================
// ===== PASO 1: REEMPLAZA TU FUNCIÓN 'fetchData' Y SUS LLAMADAS POR ESTE BLOQUE =====
// ====================================================================================

// --- Nombre de la tabla de trabajo ---
$working_table_name = "solicitudes_working_copy";

// Función para obtener TODAS las novedades propuestas de una vez
function fetchAllNovedadesPropuestas($conn, $working_table_name, $anio_semestre, $departamento_id) {
    $data = [];
    // La consulta ya no filtra por una novedad específica
    $sql = "SELECT
                id_solicitud, cedula, nombre, tipo_docente, sede, tipo_dedicacion, tipo_dedicacion_r, horas, horas_r,
                novedad, s_observacion, tipo_reemplazo, anexa_hv_docente_nuevo, actualiza_hv_antiguo, estado_depto,
                estado_facultad, observacion_facultad
            FROM `$working_table_name`
            WHERE anio_semestre = ? AND departamento_id = ?
            AND (archivado = 0 OR archivado IS NULL)
            AND estado_depto = 'PENDIENTE'
            ORDER BY nombre ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error al preparar la consulta de todas las novedades: " . $conn->error);
        return [];
    }
    // La consulta ahora solo necesita 2 parámetros
    $stmt->bind_param("si", $anio_semestre, $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// Se llama a la función UNA SOLA VEZ para obtener TODAS las novedades
$todas_las_novedades_propuestas = fetchAllNovedadesPropuestas($conn, $working_table_name, $anio_semestre, $departamento_id);

// ====================================================================================
// ====================================================================================
// =================================================================
// ===== PASO 3: APLICA LA FUNCIÓN Y SEPARA LOS DATOS PROCESADOS =====
// =================================================================
// =================================================================
// ===== PASO 3: APLICA LA FUNCIÓN Y SEPARA LOS DATOS PROCESADOS =====
// =================================================================
$novedades_procesadas = procesarCambiosParaVista($todas_las_novedades_propuestas);

$data_adicionar = [];
$data_eliminar = [];
$data_modificar = [];
$data_cambios_vinculacion = [];

foreach ($novedades_procesadas as $sol) {
    // Usamos strcasecmp para una comparación segura sin importar mayúsculas/minúsculas
    if (strcasecmp(trim($sol['novedad'] ?? ''), 'Cambio de Vinculación') == 0) {
        $data_cambios_vinculacion[] = $sol;
    } elseif (strcasecmp(trim($sol['novedad'] ?? ''), 'adicionar') == 0 || strcasecmp(trim($sol['novedad'] ?? ''), 'adicion') == 0) {
        $data_adicionar[] = $sol;
    } elseif (strcasecmp(trim($sol['novedad'] ?? ''), 'eliminar') == 0) {
        $data_eliminar[] = $sol;
    } elseif (strcasecmp(trim($sol['novedad'] ?? ''), 'modificar') == 0) {
        $data_modificar[] = $sol;
    }
}
// =================================================================

// Función para obtener el conteo de registros PENDIENTES por tipo de novedad
function countPending($conn, $working_table_name, $anio_semestre, $departamento_id, $novedad_type) {
    $count = 0;
    $sql = "SELECT COUNT(*) AS total
            FROM `$working_table_name`
            WHERE anio_semestre = ?
              AND departamento_id = ?
              AND novedad = ?
              AND (archivado = 0 OR archivado IS NULL)
              AND estado_depto = 'PENDIENTE'";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error al preparar conteo para novedad '$novedad_type': " . $conn->error);
        return 0;
    }
    $stmt->bind_param("sis", $anio_semestre, $departamento_id, $novedad_type);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $count = (int)$row['total'];
    }
    $stmt->close();
    return $count;
}

// Ejemplos de uso:
$count_adicionar_pend = countPending($conn, $working_table_name, $anio_semestre, $departamento_id, 'adicionar');
$count_eliminar_pend  = countPending($conn, $working_table_name, $anio_semestre, $departamento_id, 'eliminar');
$count_modificar_pend = countPending($conn, $working_table_name, $anio_semestre, $departamento_id, 'modificar');

$conn->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Solicitudes</title>
    
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<!-- jQuery y Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    
    
<!-- Cargar Bootstrap 5 y Font Awesome -->

<!-- jQuery (si es necesario) -->

<!-- Cargar solo Bootstrap 5 JS -->

            <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <script>
        function confirmarEnvio(count, tipo) {
            return confirm(` Se confirman ${count} profesores de  ${tipo}. ¿Desea continuar?`);
        }
    </script>
    <style>
    /* Unicauca Color Palette (Retained for Minimalism) */
    :root {
        --unicauca-blue-primary: #0047AB; /* Un azul vibrante pero con presencia */
        --unicauca-blue-dark: #002D72; /* Azul oscuro para elementos clave */
        --unicauca-blue-light: #F0F5FA; /* Un azul muy, muy pálido para fondos sutiles */
        --unicauca-red-primary: #CC3333; /* Un rojo más directo para acciones críticas */
        --unicauca-gray-light: #F8F8F8; /* Gris casi blanco para filas alternas */
        --unicauca-gray-border: #E0E0E0; /* Borde muy fino y sutil */
        --unicauca-text-dark: #333333; /* Texto principal oscuro */
        --unicauca-text-light: #777777; /* Texto secundario más suave */
        --unicauca-orange-primary: #FF9933; /* Naranja para énfasis de botón */
        --unicauca-orange-dark: #E68A00; /* Naranja más oscuro para hover */

        /* Colores institucionales definidos más abajo y priorizados */
        --unicauca-primary: #002A9E;
        --unicauca-secondary: #0051C6;
        --unicauca-accent: #16A8E1;
        --unicauca-success: #249337;
        --unicauca-warning: #F8AE15;
        --unicauca-danger: #E52724;
        --unicauca-light: #e8f4ff;
        --unicauca-gray: #f0f4f8;
        --unicauca-dark: #1a1a2e;

        /* Colores del banner y menú superior */
        --unicauca-main-dark-blue: #001A4E;
        --unicauca-main-dark-blue-light-variation: #002D7A;
        --unicauca-banner-start-color: #00226B;
        --unicauca-banner-end-color: #00368D;
        --unicauca-accent-gold: #F0C239;
        --unicauca-accent-red: #DC3545;
    }

    body {
        margin: 15px auto;
        padding: 15px;
        max-width: 95%;
        color: var(--unicauca-text-dark);
        background-color: #fcfdfe;
        line-height: 1.4;
        font-family: 'Open Sans', sans-serif !important; /* Priorizado */
        background-color: #f8fafc; /* Priorizado */
        padding: 20px; /* Priorizado */
    }

    /* Apply Open Sans to all text elements */
    body, h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th {
        font-family: 'Open Sans', sans-serif !important;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid var(--unicauca-gray-border);
        padding-bottom: 10px;
    }

    .header h1 {
        flex: 1;
        text-align: center;
        color: var(--unicauca-blue-dark);
        font-size: 1.8rem;
        margin: 0;
        font-weight: 600;
    }

    .header h2, .header h3 {
        flex: 1;
        text-align: left;
        margin: 3px 0;
        color: var(--unicauca-text-dark);
        font-size: 1rem;
        font-weight: 500;
    }

    /* --- Minimalist Table Style (General) --- */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 0.9rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        border-radius: 4px;
        overflow: hidden;
    }

    /* Estilos compactos para celdas */
    th, td {
        border: 1px solid var(--unicauca-gray-border);
        padding: 4px 8px;
        text-align: center;
        line-height: 1.2;
        min-height: 20px;
        font-size: 0.8rem;
    }

    /* Encabezados más compactos también */
    th {
        background-color: var(--unicauca-blue-dark);
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 6px 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    /* Filas alternas - casi invisibles */
    tr:nth-child(even) {
        background-color: var(--unicauca-gray-light);
    }

    /* Efecto hover - muy discreto */
    tr:hover {
        background-color: rgba(0, 71, 171, 0.05);
        transition: background-color 0.15s ease;
    }

    /* --- Button Styles (More Compact) --- */
    button {
        padding: 4px 10px;
        margin: 1px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: background-color 0.15s ease, transform 0.1s ease, box-shadow 0.1s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
.update-btn {
    background-color: #FFC107; /* Amarillo/Naranja de Bootstrap (Warning) */
    color: black; /* Texto negro para asegurar buen contraste sobre el naranja claro */
}
.update-btn:hover {
    background-color: #DAC001; /* Mantiene el azul oscuro de Unicauca para el hover */
    transform: translateY(-0.5px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

    .delete-btn {
        background-color: var(--unicauca-red-primary);
        color: white;
    }
    .delete-btn:hover {
        background-color: #B32D2D;
        transform: translateY(-0.5px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .estado-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .estado-container h4 {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .estado-container h4 .fas {
        font-size: 1.5em;
        color: #AD0000;
        transition: transform 0.2s ease-in-out;
    }

    .estado-container h4 .fas:hover {
        color: #DB141C;
        transform: scale(1.1);
    }

    .estado-container h4 .fa-caret-right {
        transform: rotate(0deg);
    }

    .estado-container h4 .fa-caret-down {
        transform: rotate(0deg);
    }

    .container {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 15px;
        max-width: 100%;
        margin: 0 auto;
        padding: 10px;
    }

    .box {
        flex-grow: 1;
        padding: 15px;
        border: 1px solid var(--unicauca-gray-border);
        text-align: center;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        background-color: white;
    }

    .box-gray {
        background-color: var(--unicauca-gray-light);
        border-color: var(--unicauca-blue-primary);
        margin-bottom: 20px;
        padding: 12px 15px;
        border-radius: 6px;
    }

    .btn-primary {
        background-color: var(--unicauca-blue-primary);
        color: white;
        height: 36px;
        padding: 0 12px;
        font-size: 0.9rem;
        line-height: 36px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .btn-primary:hover {
        background-color: #003A90;
        transform: translateY(-0.5px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    /* --- Cargue Masivo Button (Compact) --- */
    @keyframes pulseEffect {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); }
        100% { transform: scale(1); }
    }

    .btn-cargue-masivo {
        background: linear-gradient(to right, var(--unicauca-orange-primary), var(--unicauca-orange-dark));
        color: white;
        font-weight: 500;
        font-size: 0.95rem;
        padding: 8px 15px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
        border: none;
        display: inline-block;
        animation: pulseEffect 1s ease-in-out infinite;
    }

    .btn-cargue-masivo:hover {
        background: linear-gradient(to right, var(--unicauca-orange-dark), #CC6600);
        transform: scale(1.02);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
    }

    .label-italic {
        font-style: italic;
        color: var(--unicauca-text-light);
    }

    #textoObservacion {
        white-space: pre-line;
    }

    /* Specific styles for the "FOR.45" download button (Highly Minimalist) */
    .download-btn {
        background-color: transparent;
        box-shadow: none;
        padding: 3px;
        margin: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        min-height: 24px;
    }
    .download-btn:hover {
        background-color: rgba(0, 71, 171, 0.05);
        box-shadow: none;
        transform: none;
    }
    .download-btn i {
        font-size: 1.0em !important;
        color: var(--unicauca-blue-primary) !important;
    }

    /* --- Modal Styling (More Compact) - Initial Bootstrap overrides --- */
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    .modal-header {
        background-color: var(--unicauca-blue-dark);
        color: white;
        border-bottom: none;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        padding: 12px 15px;
    }
    .modal-title {
        font-weight: 600;
        font-size: 1.1rem;
    }
    .modal-footer {
        border-top: 1px solid var(--unicauca-gray-border);
        padding: 10px 15px;
    }
    .btn-close {
        filter: invert(1);
        font-size: 0.9rem;
        padding: 0;
        margin: 0;
    }
    .form-control {
        border-radius: 4px;
        border: 1px solid var(--unicauca-gray-border);
        padding: 7px 10px;
        font-size: 0.9rem;
    }
    .form-label {
        font-weight: 500;
        color: var(--unicauca-text-dark);
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Checkbox for Visado */
    .individualCheckbox {
        transform: scale(1);
        margin: 0 3px;
        cursor: pointer;
    }
    .individualCheckbox:disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* --- Universidad del Cauca Styling (Prioritized) --- */

    .top-nav {
        background: linear-gradient(135deg, var(--unicauca-banner-start-color) 0%, var(--unicauca-banner-end-color) 100%);
        padding: 4px 0;
        border-radius: 10px;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        color: var(--unicauca-text-light);
    }

    .btn-unicauca-light {
        background-color: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        transition: all 0.3s;
        padding: 8px 15px;
        border-radius: 30px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .btn-unicauca-light:hover {
        background-color: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .institutional-card {
        flex: 0 0 calc(50% - 10px);
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 42, 158, 0.1);
        border: 1px solid #e0e6ed;
        margin-bottom: 25px;
        overflow: hidden;
        border-top: 4px solid var(--unicauca-primary);
    }

    .institutional-cardb { /* Priorizado */
        flex: 0 0 calc(50% - 10px);
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 42, 158, 0.1);
        border: 1px solid #e0e6ed;
        margin-bottom: 25px;
        overflow: hidden;
        border-top: 4px solid var(--unicauca-primary);
        padding: 25px; /* Priorizado */
    }

    .card-header-unicauca {
        background: linear-gradient(to right, var(--unicauca-light), white);
        padding: 20px 25px;
        border-bottom: 1px solid #e0e6ed;
    }

    .institutional-title {
        color: var(--unicauca-primary);
        font-weight: 700;
        font-size: 1.8rem;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .institutional-subtitle {
        color: var(--unicauca-secondary);
        font-weight: 600;
        font-size: 1.3rem;
        margin-bottom: 0;
    }

    .periodo-badge {
        background-color: var(--unicauca-accent);
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 3px 8px rgba(22, 168, 225, 0.3);
    }

    .status-indicator {
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        margin-right: 10px;
        margin-bottom: 10px;
    }

    .status-success {
        background-color: rgba(36, 147, 55, 0.15);
        color: var(--unicauca-success);
        border: 1px solid rgba(36, 147, 55, 0.3);
    }

    .status-warning {
        background-color: rgba(248, 174, 21, 0.15);
        color: var(--unicauca-warning);
        border: 1px solid rgba(248, 174, 21, 0.3);
    }

    .status-danger {
        background-color: rgba(229, 39, 36, 0.15);
        color: var(--unicauca-danger);
        border: 1px solid rgba(229, 39, 36, 0.3);
    }

    .status-icon {
        margin-right: 8px;
        font-size: 1.2em;
    }

    .card-content {
        padding: 25px;
    }

    .section-title {
        color: var(--unicauca-primary);
        border-bottom: 2px solid var(--unicauca-accent);
        padding-bottom: 10px;
        margin-bottom: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
    }

    .section-title i {
        margin-right: 10px;
        color: var(--unicauca-secondary);
    }

    .summary-card {
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e0e6ed;
        padding: 20px;
        margin-bottom: 20px;
        background: white;
    }


    .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--unicauca-secondary);
        margin-bottom: 5px;
    }

    .summary-label {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .sede-container {
        display: flex;
        justify-content: space-around;
        margin: 15px 0;
        text-align: center;
    }

    .sede-box {
        padding: 10px;
        border-radius: 8px;
        background-color: var(--unicauca-light);
        flex: 1;
        margin: 0 5px;
    }

    .sede-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--unicauca-primary);
    }

    .status-badge { /* Priorizado, el de arriba es para status-indicator */
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }

    .badge-success {
        background-color: rgba(36, 147, 55, 0.15);
        color: var(--unicauca-success);
        border: 1px solid rgba(36, 147, 55, 0.3);
    }

    .badge-warning {
        background-color: rgba(248, 174, 21, 0.15);
        color: var(--unicauca-warning);
        border: 1px solid rgba(248, 174, 21, 0.3);
    }

    @media (max-width: 768px) {
        .institutional-title {
            font-size: 1.5rem;
        }

        .institutional-subtitle {
            font-size: 1.1rem;
        }

        .sede-container {
            flex-direction: column;
        }

        .sede-box {
            margin: 5px 0;
        }
    }

    .summary-title .label-space {
        margin-right: 15px;
    }

    /* --- Summary Container --- */
    .summary-container {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-left: 5px solid #A52A2A;
        padding: 15px 20px;
        margin-bottom: 0px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* Lista de datos del resumen */
    .summary-data-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        font-size: 1.1em;
    }

    .summary-data-list li {
        display: flex;
        align-items: center;
        margin-right: 25px;
        white-space: nowrap;
        position: relative;
        margin-bottom: 0px;
    }

    /* Pseudo-elemento para el separador '|' */
    .summary-data-list li:not(:last-child)::after {
        content: "|";
        color: #A52A2A;
        margin-left: 20px;
        font-weight: normal;
        font-size: 1.1em;
    }

    /* Estilos para las etiquetas (Facultad, Departamento, Periodo) */
    .summary-data-list .label-heading {
        color: #000066;
        font-weight: bold;
        margin-right: 8px;
    }

    /* Estilos para los valores de los datos */
    .summary-data-list .data-value {
        color: #343a40;
        font-weight: 500;
    }

    /* Media Queries para pantallas más pequeñas */
    @media (max-width: 768px) {
        .summary-data-list {
            flex-direction: column;
            align-items: flex-start;
        }
        .summary-data-list li {
            margin-right: 0;
            margin-bottom: 8px;
        }
        .summary-data-list li:not(:last-child)::after {
            content: none;
        }
    }

    .link-hv {
        color: #004080;
        text-decoration: none;
        font-weight: bold;
        position: relative;
    }

    .link-hv:hover {
        text-decoration: underline;
    }

    .link-hv::after {
        content: "🔗";
        margin-left: 4px;
        font-size: 0.8em;
    }

    /* --- Modal Styles (for Bootstrap Modals) - Prioritized --- */
    .modal-content { /* Priorizado */
        border-radius: 10px;
        overflow: hidden;
    }
    .modal-header { /* Priorizado */
        background-color: #1A73E8; /* Usando este color, ya que es el último definido */
        color: white;
        padding: 15px 20px;
    }
    .card-header {
        background-color: #f8f9fa;
        font-weight: 600;
        padding: 10px 15px;
    }
    .info-section {
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        margin-bottom: 15px;
    }
    .form-label { /* Priorizado */
        font-weight: 500;
        color: #333;
        margin-bottom: 5px;
    }
    .form-control { /* Priorizado */
        border-radius: 5px;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        transition: all 0.3s;
    }
    .form-control:focus {
        border-color: #1A73E8;
        box-shadow: 0 0 0 0.2rem rgba(26, 115, 232, 0.25);
    }
    .experience-col {
        padding-left: 20px;
    }
    @media (max-width: 768px) {
        .experience-col {
            padding-left: 0;
            margin-top: 20px;
        }
    }

    .btn.btn-sm.btn-info .fa-eye {
        font-size: 9px !important;
        color: white !important;
        line-height: 1;
        vertical-align: middle;
    }

    .btn.btn-sm.btn-primary .fa-file-arrow-down {
        font-size: 9px !important;
        color: #1A73E8 !important;
        line-height: 1;
        vertical-align: middle;
    }

    /* Encabezado del resumen */
    .summary-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px dashed var(--unicauca-accent);
    }

    .summary-title {
        color: var(--unicauca-primary);
        font-size: 1.6rem;
        margin: 0;
        font-weight: normal;
        display: flex;
        align-items: center;
    }

    .summary-title strong {
        font-weight: 700;
        margin-right: 5px;
    }

    .summary-title i {
        margin-right: 10px;
        color: var(--unicauca-secondary);
    }

    /* Tabla institucional */
    .table-unicauca { /* Priorizado, este tiene un verde principal */
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-family: 'Open Sans', sans-serif !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
        margin: 15px 0; /* Asegura un margen apropiado */
    }

    .table-unicauca thead { /* Priorizado */
        background-color: #00843D; /* Verde Unicauca */
        color: white;
    }

    .table-unicauca th { /* Priorizado */
        padding: 10px 8px;
        text-align: center;
        font-weight: 600;
        font-size: 0.85rem;
        border-bottom: 2px solid #00612D; /* Borde más oscuro */
        position: relative;
    }

    .table-unicauca td { /* Priorizado */
        padding: 4px 8px;
        min-height: 24px;
        line-height: 1.2;
        text-align: center;
        border-bottom: 1px solid #e0e0e0;
        font-size: 0.85rem;
    }

    .table-unicauca tbody tr:last-child td {
        border-bottom: none;
    }

    .table-unicauca tbody tr:nth-child(even) { /* Priorizado */
        background-color: var(--unicauca-light); /* Usando el unicauca-light de la segunda paleta */
    }

    .table-unicauca tbody tr:hover { /* Priorizado */
        background-color: rgba(0, 132, 61, 0.05); /* Verde muy claro con transparencia */
    }

    .table-unicauca .table-secondary { /* Priorizado */
        background-color: var(--unicauca-gray); /* Usando el unicauca-gray de la segunda paleta */
        font-weight: 600;
    }

    .text-success { /* Priorizado */
        color: #00843D !important; /* Verde Unicauca */
    }

    .text-danger { /* Priorizado */
        color: #D32F2F !important; /* Rojo institucional */
    }

    /* Botones institucionales */
    .btn-unicauca-primary {
        background-color: var(--unicauca-primary);
        border-color: var(--unicauca-primary);
        color: white;
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-unicauca-primary:hover {
        background-color: #001a7a;
        border-color: #001a7a;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-unicauca-success {
        background-color: var(--unicauca-success);
        border-color: var(--unicauca-success);
        color: white;
        border-radius: 30px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-unicauca-success:hover {
        background-color: #1c7a2e;
        border-color: #1c7a2e;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-unicauca-danger {
        background-color: var(--unicauca-danger);
        border-color: var(--unicauca-danger);
        color: white;
        border-radius: 30px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-unicauca-danger:hover {
        background-color: #c21f1d;
        border-color: #c21f1d;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-unicauca-accent {
        background-color: var(--unicauca-accent);
        border-color: var(--unicauca-accent);
        color: white;
        border-radius: 30px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-unicauca-accent:hover {
        background-color: #1290c9;
        border-color: #1290c9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Sección de respuesta */
    .response-section {
        background-color: var(--unicauca-light);
        border-radius: 8px;
        padding: 15px;
        margin: 20px 0;
        border-left: 4px solid var(--unicauca-accent);
    }

    .response-section h3 {
        color: var(--unicauca-primary);
        font-weight: 600;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }

    .response-section .btn-observacion {
        background: none;
        border: none;
        cursor: pointer;
        margin-left: 5px;
        color: var(--unicauca-secondary);
        font-size: 1.1rem;
    }

    /* Sección de botones */
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: center;
        margin: 20px 0;
    }

    .action-button-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .button-tooltip {
        font-size: 0.8rem;
        color: #6c757d;
        text-align: center;
        max-width: 300px;
    }

    /* Mejora para la primera columna de .table-unicauca */
    .table-unicauca td:first-child {
        font-weight: 500;
        text-align: left;
        padding-left: 12px;
    }

    /* Estilos específicos para las columnas sin intercalado */
    .td-simple {
        border-bottom: 1px solid #e0e6ed;
        color: #343A40;
        font-weight: normal;
        font-family: 'Open Sans', sans-serif !important;
        font-size: 0.8rem;
    }

    /* --- Universidad del Cauca Actions Section --- */
    .unacauca-actions-section {
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    /* Primary Button (Enviar a Facultad) */
    .unacauca-btn-primary {
        background-color: #8B0000 !important;
        color: #fff !important;
        border-color: #8B0000 !important;
        font-weight: bold;
        padding: 10px 25px;
        border-radius: 5px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .unacauca-btn-primary:hover {
        background-color: #a00000 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* Reprint Button */
    .unacauca-btn-reprint {
        background-color: #FFD700 !important;
        color: #8B0000 !important;
        border-color: #FFD700 !important;
        font-weight: bold;
        padding: 10px 25px;
        border-radius: 5px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .unacauca-btn-reprint:hover {
        background-color: #e5c100 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* Disabled Button */
    .unacauca-btn-disabled {
        background-color: #cccccc !important;
        color: #666666 !important;
        border-color: #cccccc !important;
        cursor: not-allowed;
        padding: 10px 25px;
        border-radius: 5px;
    }

    /* Download Button */
    .unacauca-btn-download {
        background-color: #4CAF50 !important;
        color: #fff !important;
        border-color: #4CAF50 !important;
        font-weight: bold;
        padding: 10px 25px;
        border-radius: 5px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .unacauca-btn-download:hover {
        background-color: #45a049 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* --- Status Section (Rta. Facultad, Rta. VRA) - Minimalist Styles --- */
    .unacauca-status-section {
        display: flex;
        flex-direction: column;
        gap: 25px;
        padding: 20px;
        background-color: #fcfcfc;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        border: 1px solid #f0f0f0;
    }

    .unacauca-status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 0 10px;
        border-bottom: 1px dashed #e9e9e9;
        padding-bottom: 15px;
    }

    .unacauca-status-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .unacauca-status-label-minimal {
        font-size: 1.15rem;
        font-weight: 600;
        color: #555;
        margin-right: 15px;
        white-space: nowrap;
        text-transform: capitalize;
    }

    /* Para pantallas más pequeñas, apilar la etiqueta y el badge */
    @media (max-width: 767px) {
        .unacauca-status-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .unacauca-status-label-minimal {
            margin-right: 0;
            margin-bottom: 5px;
        }
    }

    .unacauca-status-badge-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .unacauca-status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 125px;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* Colores para status badges */
    .unacauca-status-badge.status-accepted {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .unacauca-status-badge.status-rejected {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .unacauca-status-badge.status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    /* Icons within badges */
    .unacauca-status-badge .fas,
    .unacauca-status-badge .fa-solid {
        font-size: 0.9rem;
    }

    /* Observation Button - Integrated Look */
    .unacauca-btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease-in-out, color 0.2s ease-in-out;
    }

    .unacauca-btn-icon i {
        color: #007bff;
        font-size: 1.1rem;
    }

    .unacauca-btn-icon:hover i {
        color: #0056b3;
    }

    .unacauca-btn-icon:hover {
        transform: scale(1.1);
    }

    /* Optional: Make observation modal text a bit more prominent */
    .unacauca-observation-text {
        font-size: 1.05rem;
        line-height: 1.7;
        color: #333;
        padding: 10px;
        background-color: #eef4f8;
        border-radius: 6px;
        border: 1px solid #d6e2ea;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    /* --- Modal Styles (for the custom observation modal) --- */
    .unacauca-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        z-index: 1050;
        max-width: 1600px;
        width: 400px;
        text-align: center;
    }

    .unacauca-modal-backdrop {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1040;
    }

    .unacauca-modal-close-btn {
        background-color: #8B0000;
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }

    .unacauca-modal-close-btn:hover {
        background-color: #a00000;
    }

    /* --- Bootstrap Modal Overrides (for #myModal) --- */
    .unacauca-modal-content {
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .unacauca-modal-header { /* Priorizado */
        background-color: #8B0000; /* Unacauca Red */
        color: #fff;
        border-bottom: 1px solid #7a0000;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .unacauca-modal-header .modal-title {
        color: #fff;
        font-weight: bold;
    }

    .unacauca-modal-close {
        color: #fff;
        opacity: 0.8;
        text-shadow: none;
        font-size: 1.5rem;
    }

    .unacauca-modal-close:hover {
        color: #FFD700;
        opacity: 1;
    }

    .unacauca-modal-body {
        padding: 25px;
    }

    .unacauca-modal-body label {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .unacauca-input {
        border-color: #ced4da;
        border-radius: 5px;
        padding: 8px 12px;
    }

    .unacauca-input:focus {
        border-color: #FFD700;
        box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
    }

    .unacauca-modal-body .form-group {
        margin-bottom: 1.5rem;
    }

    .unacauca-folio-section {
        background-color: #f2f2f2;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .unacauca-folio-section .section-title {
        font-size: 1.15rem;
        font-weight: bold;
        color: #8B0000;
        margin-bottom: 15px;
        display: block;
        text-align: center;
    }

    .unacauca-folio-section .label-italic {
        font-style: italic;
        color: #555;
    }

    .unacauca-total-folios {
        font-size: 1.1rem;
        font-weight: bold;
        color: #333;
        margin-top: 15px;
        border-top: 1px dashed #ddd;
        padding-top: 10px;
    }

    .unacauca-total-folios strong {
        color: #8B0000;
    }


    .unacauca-modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 15px 25px;
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    .unacauca-btn-secondary {
        background-color: #6c757d !important;
        color: #fff !important;
        border-color: #6c757d !important;
        font-weight: normal;
        padding: 8px 20px;
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }

    .unacauca-btn-secondary:hover {
        background-color: #5a6268 !important;
        border-color: #5a6268 !important;
    }

    /* Placeholder italic style (existing) */
    ::-webkit-input-placeholder {
        font-style: italic;
    }
    ::-moz-placeholder {
        font-style: italic;
    }
    :-ms-input-placeholder {
        font-style: italic;
    }
    :-moz-placeholder {
        font-style: italic;
    }

    /* Tooltip style (optional, if default isn't matching) */
    .tooltip-inner {
        background-color: rgba(139, 0, 0, 0.8);
        color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    .bs-tooltip-top .tooltip-arrow::before {
        border-top-color: #8B0000;
    }
    .bs-tooltip-right .tooltip-arrow::before {
        border-right-color: #8B0000;
    }
    .bs-tooltip-bottom .tooltip-arrow::before {
        border-bottom-color: #8B0000;
    }
    .bs-tooltip-left .tooltip-arrow::before {
        border-left-color: #8B0000;
    }
    .modal-close-btn {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px 15px;
        line-height: 1;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .modal-close-btn:hover {
        background-color: #5a6268;
    }

    /* --- */
    /* Estilo específico para el botón de "Enviar" en los modales (repetido para referencia) */
    /* --- */
    .modal-submit-btn {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px 15px;
        line-height: 1;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .modal-submit-btn:hover {
        background-color: #0056b3;
    }
        
        
        <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding-top: 5px;
                    max-width: 1600px !important; /* Define el ancho máximo deseado */

        }
        .card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            border-radius: 15px 15px 0 0 !important;
            padding: .2rem .5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h4 {
            font-weight: 600;
            color: white; /* Default for primary/danger */
        }
        .card-header.bg-warning h4 {
            color: #343a40; /* Darker text for warning background */
        }
        .table-responsive {
            padding: 5px;
        }
        table.dataTable thead th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
        }
        table.dataTable tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        table.dataTable tbody tr:hover {
            background-color: #e2e6ea;
        }
        /* Custom styles for status badges (if still needed, though replaced by DataTables) */
        .status-badge {
            padding: .5em .75em;
            border-radius: 20px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
        .status-warning {
            background-color: #fff3cd;
            color: #856404;
        }
          
                

    /* Estilos adicionales para los botones si es necesario */
    .delete-btn {
        background: none;
        border: none;
        color: #dc3545; /* Color rojo de Bootstrap para peligro */
        cursor: pointer;
        padding: 0;
        font-size: 1.2em; /* Tamaño del icono */
    }
    .delete-btn:hover {
        color: #c82333; /* Color más oscuro al pasar el mouse */
    }
        
       .nombre-novedad-eliminar {
    color: #dc3545 !important;
    font-weight: bold;
    text-decoration: line-through;
    opacity: 0.7; /* <-- Le baja un poco la intensidad al texto y al tachado */
}
        .nombre-novedad-adicionar {
            color: #0d6efd !important; /* Azul de Bootstrap Primary */
            font-weight: bold;
        }
        .nombre-novedad-modificar {
            color: #FFC207 !important; /* Un tono de naranja/ladrillo fuerte para "Modificar" */
            font-weight: bold;
        }
        .delete-btn[disabled],
.update-btn[disabled] {
    opacity: 0.5; /* Hace que el botón se vea semi-transparente (más desvanecido) */
    cursor: not-allowed; /* Cambia el cursor a un símbolo de "prohibido" */
    filter: grayscale(80%); /* Opcional: Hace que los colores del icono se vean en escala de grises */
    /* background-color: #cccccc; */ /* Opcional: Puedes cambiar el color de fondo */
    /* border-color: #999999; */ /* Opcional: Puedes cambiar el color del borde */
}

/* Si usas Font Awesome (como parece), también puedes desvanecer el icono */
.delete-btn[disabled] i,
.update-btn[disabled] i {
    color: inherit; /* Asegura que el color del icono herede del botón y se vea afectado por la opacidad/grayscale */
}
  .section-subtitleb {
  margin-top: -1.3rem !important;
        margin-bottom: 1.3rem !important;

}   
        /* La nueva clase para el estilo "píldora" */
/* Estilo para la píldora/cápsula */
.header-pill {
    display: inline-block;
    width: auto;
    border-radius: 50px;       /* Lo hace ovalado */
    padding: 0.4rem 1.5rem;    /* Controla el espacio: 0.4rem arriba/abajo y 1.5rem a los lados */
    line-height: 0.2;          /* Ajusta la altura de la línea del texto */
}

/* Regla CLAVE: Elimina los márgenes del texto que está adentro */
.header-pill h5 {
    margin: 0;                 /* ¡Esto quita el espacio extra que infla la cápsula! */
    font-size: 1rem;           /* Opcional: Puedes ajustar el tamaño del texto si es necesario */
}
        
         .columna-nombre {
        width: 30%; /* Dale un 30% del ancho total de la tabla. ¡Puedes ajustar este valor! */
        min-width: 250px; /* Opcional: un ancho mínimo para que no se encoja demasiado */
    }
        
        /* Si hay pendientes, el botón "vibra" o resalta para llamar la atención */
    .btn-highlight {
        border: 2px solid #dc3545 !important;
        box-shadow: 0 0 15px rgba(220, 53, 69, 0.5);
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
</style>
</head>
<body>
   <!-- Barra superior -->
    <div style="max-width: 1800px;  margin: auto;">
<style>
.top-nav .text-white.fw-bold {
    font-size: 1.8rem;
}
</style>
<div class="top-nav">
    <div class="container position-relative d-flex align-items-center justify-content-center"> <?php if ($tipo_usuario != 3): ?>
            <a href="report_depto_full.php?anio_semestre=<?= urlencode($anio_semestre) ?>" 
               class="btn-unicauca-light position-absolute start-0" title="Regresar a 'Gestión facultad'">
                <i class="fas fa-arrow-left me-2"></i> Regresar
            </a>
        <?php endif; ?>
        
        <div class="text-white fw-bold">
    <i class="fas fa-university me-2"></i>
    Gestión Novedades del Departamento de 
    <?= obtenerNombreDepartamento($departamento_id); ?> - 
    Facultad de <?= obtenerNombreFacultad($departamento_id); ?>, 
    Periodo <?= htmlspecialchars($anio_semestre); ?>
</div>

        </div>
</div>
    <div class="container">
        
 <div class="institutional-card">
                 <div class="   card-header-unicauca">
<h2 class="section-subtitle">Estado actual</h2>



    <?php
                    $nombre_fac=obtenerNombreFacultad($departamento_id);

$facultad_id = obtenerIdFacultad($departamento_id);

    // Función para obtener el nombre de la facultad
    function obtenerNombreFacultad($departamento_id) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT nombre_fac_min FROM facultad,deparmanentos WHERE
        PK_FAC = FK_FAC AND 
        deparmanentos.PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {    
            $row = $result->fetch_assoc();
            return $row['nombre_fac_min'];
        } else {
            return "Facultad Desconocida";
        }
    }
             // Función para obtener el nombre de la facultad
    function obtenerIdFacultad($departamento_id)  {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT deparmanentos.FK_FAC  FROM deparmanentos WHERE PK_DEPTO = '$departamento_id'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['FK_FAC'];
        } else {
            return "Departamento Desconocido";
        }
    }



    // Establecer conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

// =================================================================
// ===== PASO 2: AÑADE ESTA FUNCIÓN AL INICIO DEL ARCHIVO =====
// ... tus require ...

// =================================================================
// ===== PASO 2: AÑADE ESTA FUNCIÓN AL INICIO DEL ARCHIVO =====
// =================================================================
function procesarCambiosParaVista($solicitudes) {
    $adiciones = [];
    $eliminaciones = [];
    $otras_novedades = [];
    $resultado_final = [];

    foreach ($solicitudes as $sol) {
        $cedula = $sol['cedula'];
        $novedad = strtolower(trim($sol['novedad'] ?? ''));

        // --- CAMBIO CLAVE: Si es NN (222), usamos el ID de la solicitud para que no se pisen ---
        $key = ($cedula === '222') ? 'NN_' . $sol['id_solicitud'] : $cedula;

        if ($novedad === 'adicionar' || $novedad === 'adicion') {
            $adiciones[$key] = $sol;
        } elseif ($novedad === 'eliminar') {
            $eliminaciones[$key] = $sol;
        } else {
            $otras_novedades[] = $sol;
        }
    }

    foreach ($adiciones as $key => $sol_adicion) {
        // Ahora solo intentará unir si la llave coincide (docentes reales con misma cédula)
        if (isset($eliminaciones[$key])) {
            $sol_eliminacion = $eliminaciones[$key];

            $info_anterior = "Sale de " . $sol_eliminacion['tipo_docente'];
            if ($sol_eliminacion['tipo_docente'] === 'Ocasional') {
                $info_anterior .= " " . ($sol_eliminacion['tipo_dedicacion'] ?: $sol_eliminacion['tipo_dedicacion_r']);
            } else {
                $info_anterior .= " " . ($sol_eliminacion['horas'] ?: $sol_eliminacion['horas_r']) . " horas";
            }

            $sol_adicion['novedad'] = 'Cambio de Vinculación';
            $obs_existente = trim($sol_adicion['s_observacion']);
            $sol_adicion['s_observacion'] = $obs_existente . ($obs_existente ? ' ' : '') . '(' . $info_anterior . ')';

            $resultado_final[] = $sol_adicion;
            unset($eliminaciones[$key]);
        } else {
            $resultado_final[] = $sol_adicion;
        }
    }
    
    // Devolvemos todo: las adiciones procesadas, las eliminaciones restantes (incluyendo los NN) y otras novedades
    return array_merge($resultado_final, array_values($eliminaciones), $otras_novedades);
}
// =================================================================

// =================================================================
    require 'cn.php';
    // Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                  FROM solicitudes  where solicitudes.estado <> 'an' OR solicitudes.estado IS NULL;";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}
       
     $todosCerrados = true; // Inicializar bandera
            
            
    $obtenerDeptoCerrado = obtenerDeptoCerrado($departamento_id,$anio_semestre); // si cero   no cerrado si 1  cerrado

  $totalItems = 0; // Inicializar el acumulador fuera del bucle principal
   $contadorHV = 0; // 🔹 Inicializar el contador

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];


$sql = "
SELECT
    s.id_solicitud,
    s.anio_semestre,
    s.facultad_id,
    s.departamento_id,
    s.tipo_docente,
    s.cedula,
    s.nombre,
    s.tipo_dedicacion,     -- ✅ Original dedicacion from 'solicitudes'
    s.tipo_dedicacion_r,   -- ✅ Original dedicacion_r from 'solicitudes'
    s.horas,               -- ✅ Original hours from 'solicitudes'
    s.horas_r,             -- ✅ Original hours_r from 'solicitudes'
    s.sede,
    s.anexa_hv_docente_nuevo,
    s.actualiza_hv_antiguo,
    s.visado,
    s.estado,
    -- Determine the 'novedad' based on pending changes in swc
    CASE
        WHEN swc.novedad = 'Modificar' AND swc.estado_depto IN ('ENVIADO', 'PENDIENTE')
             AND swc.estado_facultad <> 'RECHAZADO' AND swc.estado_vra <> 'RECHAZADO'
             AND NOT (swc.estado_facultad = 'APROBADO' AND swc.estado_vra = 'APROBADO')
        THEN 'Modificar'
        WHEN swc.novedad = 'Eliminar' AND swc.estado_depto IN ('ENVIADO', 'PENDIENTE')
             AND swc.estado_facultad <> 'RECHAZADO' AND swc.estado_vra <> 'RECHAZADO'
             AND NOT (swc.estado_facultad = 'APROBADO' AND swc.estado_vra = 'APROBADO')
        THEN 'Eliminar'
        ELSE s.novedad -- Default to original 'novedad' if no active pending change
    END AS novedad,
    s.puntos,
    s.s_observacion,
    s.tipo_reemplazo,
    s.costo,
    s.anexos,
    s.pregrado,
    s.especializacion,
    s.maestria,
    s.doctorado,
    s.otro_estudio,
    s.experiencia_docente,
    s.experiencia_profesional,
    s.otra_experiencia,
    -- Get status fields from swc if a relevant novelty exists, else NULL/PENDIENTE
    COALESCE(swc.estado_depto, 'APROBADO') AS estado_depto, -- Assuming 'APROBADO' if no active novelty in SWC
    COALESCE(swc.oficio_depto, NULL) AS oficio_depto,
    COALESCE(swc.fecha_envio_depto, NULL) AS fecha_envio_depto,
    COALESCE(swc.aprobador_depto_id, NULL) AS aprobador_depto_id,
    COALESCE(swc.estado_facultad, 'APROBADO') AS estado_facultad, -- Assuming 'APROBADO' if no active novelty in SWC
    COALESCE(swc.observacion_facultad, NULL) AS observacion_facultad,
    COALESCE(swc.fecha_aprobacion_facultad, NULL) AS fecha_aprobacion_facultad,
    COALESCE(swc.aprobador_facultad_id, NULL) AS aprobador_facultad_id,
    COALESCE(swc.estado_vra, 'APROBADO') AS estado_vra, -- Assuming 'APROBADO' if no active novelty in SWC
    COALESCE(swc.observacion_vra, NULL) AS observacion_vra,
    COALESCE(swc.fecha_aprobacion_vra, NULL) AS fecha_aprobacion_vra,
    COALESCE(swc.aprobador_vra_id, NULL) AS aprobador_vra_id,
    s.id_solicitud AS fk_id_solicitud_original, -- Original ID
    f.nombre_fac_minb AS nombre_facultad,
    d.depto_nom_propio AS nombre_departamento,
    'original_or_modified_or_deleted' AS tipo_registro_para_debug
FROM
    solicitudes s
JOIN
    deparmanentos d ON (d.PK_DEPTO = s.departamento_id)
JOIN
    facultad f ON (f.PK_FAC = s.facultad_id)
LEFT JOIN
    solicitudes_working_copy swc ON (
        swc.fk_id_solicitud_original = s.id_solicitud
        AND swc.anio_semestre = s.anio_semestre
        AND swc.departamento_id = s.departamento_id
        AND swc.tipo_docente = s.tipo_docente -- Match on type and other filters too
        AND swc.cedula = s.cedula -- Match on cedula
        AND swc.novedad IN ('Modificar', 'Eliminar') -- Only link relevant novelties
        AND (swc.estado <> 'an' OR swc.estado IS NULL) -- Not cancelled in working copy
        -- Filter for pending/sent states, not yet fully approved or rejected
        AND (swc.estado_depto = 'ENVIADO' OR swc.estado_depto = 'PENDIENTE')
        AND swc.estado_facultad <> 'RECHAZADO'
        AND swc.estado_vra <> 'RECHAZADO'
        AND NOT (swc.estado_facultad = 'APROBADO' AND swc.estado_vra = 'APROBADO')
    )
WHERE
    s.facultad_id = '$facultad_id'
    AND s.departamento_id = '$departamento_id'
    AND s.anio_semestre = '$anio_semestre'
    AND s.tipo_docente = '$tipo_docente'
    AND (s.estado <> 'an' OR s.estado IS NULL) -- Exclude original records that might have been cancelled
    -- Exclude original records if they are marked for deletion AND that deletion has been approved
    AND NOT EXISTS (
        SELECT 1
        FROM solicitudes_working_copy swc_approved_del
        WHERE
            swc_approved_del.fk_id_solicitud_original = s.id_solicitud
            AND swc_approved_del.novedad = 'Eliminar'
            AND swc_approved_del.estado_facultad = 'APROBADO'
            AND swc_approved_del.estado_vra = 'APROBADO'
            AND swc_approved_del.anio_semestre = '$anio_semestre'
            AND swc_approved_del.departamento_id = '$departamento_id'
            AND swc_approved_del.tipo_docente = '$tipo_docente'
    )
    -- Ensure this record isn't an 'adicionar' that's already shown via the UNION ALL below
    AND NOT EXISTS (
        SELECT 1
        FROM solicitudes_working_copy swc_add
        WHERE
            swc_add.cedula = s.cedula AND swc_add.tipo_docente = s.tipo_docente
            AND swc_add.anio_semestre = s.anio_semestre AND swc_add.departamento_id = s.departamento_id
            AND swc_add.novedad = 'adicionar'
            AND (swc_add.estado_depto = 'ENVIADO' OR swc_add.estado_depto = 'PENDIENTE')
            AND swc_add.estado_facultad <> 'RECHAZADO'
            AND swc_add.estado_vra <> 'RECHAZADO'
            AND NOT (swc_add.estado_facultad = 'APROBADO' AND swc_add.estado_vra = 'APROBADO')
    )

UNION ALL

-- Records that are new additions from working_copy (only 'adicionar' type)
SELECT
    sw.id_solicitud,
    sw.anio_semestre,
    sw.facultad_id,
    sw.departamento_id,
    sw.tipo_docente,
    sw.cedula,
    sw.nombre,
    sw.tipo_dedicacion,     -- ✅ Use working_copy dedication for additions
    sw.tipo_dedicacion_r,
    sw.horas,               -- ✅ Use working_copy hours for additions
    sw.horas_r,
    sw.sede,
    sw.anexa_hv_docente_nuevo,
    sw.actualiza_hv_antiguo,
    sw.visado,
    sw.estado,
    sw.novedad, -- This will be 'adicionar'
    sw.puntos,
    sw.s_observacion,
    sw.tipo_reemplazo,
    sw.costo,
    sw.anexos,
    sw.pregrado,
    sw.especializacion,
    sw.maestria,
    sw.doctorado,
    sw.otro_estudio,
    sw.experiencia_docente,
    sw.experiencia_profesional,
    sw.otra_experiencia,
    sw.estado_depto,
    sw.oficio_depto,
    sw.fecha_envio_depto,
    sw.aprobador_depto_id,
    sw.estado_facultad,
    sw.observacion_facultad,
    sw.fecha_aprobacion_facultad,
    sw.aprobador_facultad_id,
    sw.estado_vra,
    sw.observacion_vra,
    sw.fecha_aprobacion_vra,
    sw.aprobador_vra_id,
    sw.fk_id_solicitud_original, -- This will be NULL for new additions
    f.nombre_fac_minb AS nombre_facultad,
    d.depto_nom_propio AS nombre_departamento,
    'adicionar' AS tipo_registro_para_debug
FROM
    solicitudes_working_copy sw
JOIN
    deparmanentos d ON (d.PK_DEPTO = sw.departamento_id)
JOIN
    facultad f ON (f.PK_FAC = sw.facultad_id)
WHERE
    sw.facultad_id = '$facultad_id'
    AND sw.departamento_id = '$departamento_id'
    AND sw.anio_semestre = '$anio_semestre'
    AND sw.tipo_docente = '$tipo_docente'
    AND (sw.estado <> 'an' OR sw.estado IS NULL)
    AND sw.novedad = 'adicionar' -- Only bring 'adicionar' novelties here
    AND (sw.estado_depto = 'ENVIADO' OR sw.estado_depto = 'PENDIENTE')
    AND sw.estado_facultad <> 'RECHAZADO'
    AND sw.estado_vra <> 'RECHAZADO'
    AND NOT (sw.estado_facultad = 'APROBADO' AND sw.estado_vra = 'APROBADO') -- Exclude if fully approved

ORDER BY nombre ASC;
";

$result = $conn->query($sql);

    // Generate a unique ID for the section that will be hidden/shown
    // Replace spaces and special characters to ensure a valid HTML ID
    $section_id = "section-" . strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $tipo_docente)));

    echo "<div class='box-gray'>";
    echo "<div class='estado-container'>";
    // Add onclick and an icon with a unique ID for rotation
echo "<h4 style='color: #000066; cursor: pointer;' onclick=\"toggleSection('" . htmlspecialchars($section_id) . "')\" title=\"Ocultar/Mostrar Detalles\">
            <i id=\"icon_" . htmlspecialchars($section_id) . "\" class=\"fas fa-caret-down\"></i>
            Vinculación: " . htmlspecialchars($tipo_docente == 'Catedra' ? 'Cátedra' : $tipo_docente)
 . " ("; // Applied style here
if ($tipo_docente == 'Catedra') {
    $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
    echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
} else {
    $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose);
    echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
}
echo ")</h4>";

        if ($tipo_usuario == 3) {
            echo "
            <form action='nuevo_registro_novedadb.php' method='GET'>
                <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>

                <div class='d-flex gap-2'>
                    <button type='submit' class='btn btn-primary'>
                        <i class='fas fa-plus'></i> solicitar  profesor (nueva vinculación)
                    </button>

                
                </div>
            </form>";
        }


    


    //termina agregar novedad
    echo "</div>"; // Close estado-container

    // Obtener el conteo de profesores
    $sqlCount = "SELECT COUNT(*) as count FROM solicitudes WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' ";
    $resultCount = $conn->query($sqlCount);
    $count = $resultCount->fetch_assoc()['count'];

    // Wrap the table content in a div with the unique ID
    echo "<div id='" . htmlspecialchars($section_id) . "' style='display: block;'>"; // Initially visible

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th rowspan='2'>Ítem</th>
                    <th rowspan='2'>Cédula</th>
<th rowspan='2' class='columna-nombre'>Nombre</th>";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }
        //echo "<th colspan='2'>Hojas de vida</th>";

        if ($estadoDepto != "CERRADO") {
            echo "<th colspan='3'>Acciones</th>";
        } else {
            echo "<th colspan='3' >Acciones</th>";
        }

        echo "</tr>";

        if ($tipo_docente == "Ocasional") {
            echo "<tr>
                    <th title='Sede Popayán'>Pop</th>
                    <th title='Sede Regionalización'>Reg</th>";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr>
                    <th title='Horas en Sede Popayán'>Hrs.Pop</th>
                    <th title='Horas en Sede Regionalización'>Hrs.Reg</th>";
        }
     /*   echo "
            <th title='Anexa Hoja de Vida para nuevos aspirantes'>Anexa</th>
            <th title='Actualiza Hoja de Vida para aspirantes antiguos'>Actualz</th>";
*/
            echo "<th>Desvincular</th>
                  <th>Modificar</th><th>for45</th>";
          
        

        echo "</tr>";

        $item = 1;
        $todosLosRegistrosValidos = true;
        $datos_acta = obtener_acta($anio_semestre, $departamento_id);
//pendiente  cambiar esto que era para oficio inicial por nuevos datos  de oficio novedad
        $num_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['acta_periodo']) : "";
        $fecha_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['fecha_acta']) : "";
        $num_acta = 0;
        $fecha_acta = "";

      while ($row = $result->fetch_assoc()) {
    if ($row["anexa_hv_docente_nuevo"] == 'si' || $row["actualiza_hv_antiguo"] == 'si') {
        $contadorHV++;
    }
  // Obtenemos los datos del profesor (array asociativo o false)
$datosProfesor = datosProfesorCompleto($row["cedula"], $anio_semestre);

$tooltip = '';

if ($datosProfesor !== false) {
    // Escapamos todos los valores para HTML
    $datosSeguros = array_map('htmlspecialchars', $datosProfesor);
    
    // Construimos el contenido del tooltip (sin el nombre aún)
    $tooltip_content = "
        <strong>Email:</strong> {$datosSeguros['email']}<br>
        <strong>Títulos:</strong> {$datosSeguros['titulos']}<br>
        <strong>Celular:</strong> {$datosSeguros['celular']}<br>
        <strong>Trabaja actualmente:</strong> {$datosSeguros['trabaja_actualmente']}<br>
        <strong>Cargo actual:</strong> {$datosSeguros['cargo_actual']}
    ";
} else {
    $tooltip_content = ''; // O algún mensaje por defecto si no hay datos del profesor
}

// Obtener el nombre completo
$full_nombre = htmlspecialchars($row["nombre"]);
// Obtener los primeros 10 caracteres del nombre para mostrar en la celda
$display_nombre = mb_substr($full_nombre, 0, 35); // mb_substr para caracteres multi-byte
// Si el nombre es más largo que 10 caracteres, añadir puntos suspensivos
if (mb_strlen($full_nombre) > 35) {
    $display_nombre .= '...';
}

// Ahora, anteponemos el nombre completo al contenido del tooltip
$tooltip = "<strong>Nombre:</strong> " . $full_nombre . "<br>" . $tooltip_content;


// Determinar la clase para el nombre según la novedad
$nombre_class = '';
// Primero, verifica si estado_vra es APROBADO. Si lo es, no aplicamos ninguna clase de novedad.
if (($row["estado_vra"] ?? '') === 'APROBADO') {
    $nombre_class = ''; // No aplicar ninguna clase si la VRA ya aprobó
} else {
    // Si la VRA no ha aprobado, entonces aplica las clases basadas en la novedad
    switch ($row["novedad"] ?? '') { // Usar null coalescing para manejar si 'novedad' es null
        case 'Eliminar':
            $nombre_class = 'nombre-novedad-eliminar';
            break;
        case 'adicionar':
            $nombre_class = 'nombre-novedad-adicionar';
            break;
        case 'Modificar':
            $nombre_class = 'nombre-novedad-modificar';
            break;
        default:
            $nombre_class = ''; // No aplicar ninguna clase si no hay novedad específica
            break;
    }
}
echo "<tr>
        <td class='td-simple'>" . $item . "</td>
        <td class='td-simple' style='text-align: left;'>" . htmlspecialchars($row["cedula"]) . "</td>
      <td class='td-simple columna-nombre " . $nombre_class . "' style='text-align: left;'
            data-toggle='tooltip'
            data-html='true'
            title='" . $tooltip . "'>
            " . $display_nombre . "
        </td>";

// ... (resto de tu código de fila) ...
    if ($tipo_docente == "Ocasional") {
        echo "<td class='td-simple'>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td class='td-simple'>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
    if ($tipo_docente == "Catedra") {
        $horas = ($row["horas"] == 0) ? "" : htmlspecialchars($row["horas"]);
        $horas_r = ($row["horas_r"] == 0) ? "" : htmlspecialchars($row["horas_r"]);

        echo "<td class='td-simple'>" . $horas . "</td>
              <td class='td-simple'>" . $horas_r . "</td>";
    }

    // Verificar si hay un enlace válido en 'anexos'
  /*  $anexos = trim($row["anexos"]);
    $hasValidLink = !empty($anexos) && preg_match('/^(https?:\/\/|www\.)/i', $anexos);
    
    // Mostrar anexa_hv_docente_nuevo como enlace si hay un enlace válido
    if ($row["anexa_hv_docente_nuevo"] == 'si' && $hasValidLink) {
        echo "<td class='td-simple'><a href='" . htmlspecialchars($anexos) . "' target='_blank' class='link-hv'>si</a></td>";
    } else {
        echo "<td class='td-simple'>" . htmlspecialchars($row["anexa_hv_docente_nuevo"]) . "</td>";
    }
    
    // Mostrar actualiza_hv_antiguo como enlace si hay un enlace válido
    if ($row["actualiza_hv_antiguo"] == 'si' && $hasValidLink) {
        echo "<td class='td-simple'><a href='" . htmlspecialchars($anexos) . "' target='_blank' class='link-hv'>si</a></td>";
    } else {
        echo "<td class='td-simple'>" . htmlspecialchars($row["actualiza_hv_antiguo"]) . "</td>";
    }
    */

$disabled_attr = '';
$title_message = '';

if (!empty($nombre_class)) { // Si hay una novedad activa (nombre_class no está vacío)
    $disabled_attr = 'disabled'; // Establece el atributo 'disabled'
    $title_message = 'No es posible registrar una nueva novedad porque este registro ya tiene una novedad activa'; // Mensaje para el tooltip
}
  
echo "<td class='td-simple'>";
// Mostrar el botón de eliminar si el usuario es tipo 3
if ($tipo_usuario == 3) {
    echo "
        <button type='button' class='delete-btn'
                data-bs-toggle='modal'
                data-bs-target='#confirmDeleteModal'
                data-id_solicitud='" . htmlspecialchars($row["id_solicitud"]) . "'
                data-facultad_id='" . htmlspecialchars($facultad_id) . "'
                data-departamento_id='" . htmlspecialchars($departamento_id) . "'
                data-anio_semestre='" . htmlspecialchars($anio_semestre) . "'
                data-tipo_docente='" . htmlspecialchars($tipo_docente) . "'
                data-nombre_docente='" . htmlspecialchars($row["nombre"]) . "'
                " . $disabled_attr . "                           title='" . htmlspecialchars($title_message) . "' >
            <i class='fa-regular fa-trash-can'></i>
        </button>";
}
echo "</td>";
?>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación de Novedad</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteNovedadForm" action="eliminar_novedadb.php" method="POST">
                <div class="modal-body">
                    <p class="mb-3">¿Está seguro de que desea solicitar la desvinculación del profesor <strong id="docenteNombre"></strong>?</p>
                    <div class="mb-3">
                        <label for="motivoEliminacion" class="form-label">Motivo de la solicitud de desvinculación:</label>
                        <textarea class="form-control" id="motivoEliminacion" name="motivo_eliminacion" rows="3" required placeholder="Describe brevemente la razón de la desvinculación(eliminación)."></textarea>
                    </div>
                    <input type="hidden" name="id_solicitud" id="modal_id_solicitud_del">
                    <input type="hidden" name="facultad_id" id="modal_facultad_id_del">
                    <input type="hidden" name="departamento_id" id="modal_departamento_id_del">
                    <input type="hidden" name="anio_semestre" id="modal_anio_semestre_del">
                    <input type="hidden" name="tipo_docente" id="modal_tipo_docente_del">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt me-2"></i>Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
          
            echo "<td class='td-simple'>";
            // Mostrar el botón de actualizar si el usuario es tipo 3
            // La condición `empty($nombre_class)` se ha eliminado para que el botón siempre se renderice
            if ($tipo_usuario == 3) {
                echo "
                    <form action='actualizar_novedadb.php' method='GET' style='display:inline;'>
                        <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                        <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                        <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                        <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                        <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                        <button type='submit' class='update-btn'
                                " . $disabled_attr . "                           title='" . htmlspecialchars($title_message) . "' >
                            <i class='fas fa-edit'></i>
                        </button>
                    </form>";
            }
            echo "</td>";
// 1. Normalizamos el valor a minúsculas y quitamos espacios
$novedad_valor = strtolower(trim($row["novedad"] ?? ''));

// 2. Ahora comparamos siempre contra minúsculas
if (
    $tipo_usuario == 3 && 
    ($novedad_valor == 'adicionar' || $novedad_valor == 'modificar') && 
    strtoupper(trim($row["estado_vra"] ?? '')) !== 'APROBADO'
) {
    echo "<td class='td-simple centered-column'>
        <button type='button' class='download-btn btn btn-sm'
            id='btn-solicitud-" . htmlspecialchars($row["id_solicitud"]) . "'
            data-id-solicitud='" . htmlspecialchars($row["id_solicitud"]) . "'
            data-departamento-id='" . htmlspecialchars($departamento_id) . "'
            data-anio-semestre='" . htmlspecialchars($anio_semestre) . "'
            data-numero-acta='" . htmlspecialchars($num_acta) . "'
            data-fecha-acta='" . htmlspecialchars($fecha_acta) . "'
            data-pregrado='" . htmlspecialchars($row["pregrado"] ?? '') . "'
            data-especializacion='" . htmlspecialchars($row["especializacion"] ?? '') . "'
            data-maestria='" . htmlspecialchars($row["maestria"] ?? '') . "'
            data-doctorado='" . htmlspecialchars($row["doctorado"] ?? '') . "'
            data-otro_estudio='" . htmlspecialchars($row["otro_estudio"] ?? '') . "'
            data-experiencia-docente='" . htmlspecialchars($row["experiencia_docente"] ?? '') . "'
            data-experiencia-profesional='" . htmlspecialchars($row["experiencia_profesional"] ?? '') . "'
            data-otra-experiencia='" . htmlspecialchars($row["otra_experiencia"] ?? '') . "'
            data-cedula-profesor='" . htmlspecialchars($row["cedula"] ?? '') . "'  
            data-bs-toggle='modal'
            data-bs-target='#actaModal'>
            <i class='fa-solid fa-file-arrow-down' style='font-size:16px; color:#1A73E8;'></i>
        </button>
    </td>";
} else {
    echo "<td class='td-simple centered-column'></td>";
}

            
            echo "</tr>";
            $item++;
        }
        $totalItems += ($item - 1);

        echo "</table>";

    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";
      
    }

    echo "</div>"; // Close the section-id div
    ?>
 </div><br>
<?php
} // End of while ($rowtipo = $resultadotipo->fetch_assoc())
?> <!-- Modal Rediseñado -->
<div class='modal fade' id='actaModal' tabindex='-1' aria-labelledby='actaModalLabel' aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='actaModalLabel'>FOR-45. Información del Acta y Datos Adicionales</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='actaForm' action='for_45b_nov.php' method='GET'>
                   <input type='hidden' name='modal_solicitud_id' id='modal_id_solicitud'>
                    <input type='hidden' name='modal_depto_id' id='modal_departamento_id'>
                    <input type='hidden' name='modal_anio_sem' id='modal_anio_semestre'>
                    <input type='hidden' name='modal_cedula_prof' id='modal_cedula_profesor'>
                             
                    <div class='row mb-4'>
                        <div class='col-md-6'>
                            <div class="mb-3">
                                <label for='numero_acta' class='form-label'>No. de Acta</label>
                                <input type='text' class='form-control' id='numero_acta' name='numero_acta' required>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class="mb-3">
                                <label for='fecha_actab' class='form-label'>Fecha Acta</label>
                                <input type='date' class='form-control' id='fecha_actab' name='fecha_actab' required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-7">
                            <div class="info-section">
                                <div class="card-header mb-3">Información Académica (Verificar)</div>
                                <div class="mb-3">
                                    <label for='pregrado' class='form-label'>Pregrado</label>
                                    <input type='text' class='form-control' id='pregrado' name='pregrado' placeholder="Título obtenido">
                                </div>
                                <div class="mb-3">
                                    <label for='especializacion' class='form-label'>Especialización</label>
                                    <input type='text' class='form-control' id='especializacion' name='especializacion' placeholder="Título obtenido">
                                </div>
                                <div class="mb-3">
                                    <label for='maestria' class='form-label'>Maestría</label>
                                    <input type='text' class='form-control' id='maestria' name='maestria' placeholder="Título obtenido">
                                </div>
                                <div class="mb-3">
                                    <label for='doctorado' class='form-label'>Doctorado</label>
                                    <input type='text' class='form-control' id='doctorado' name='doctorado' placeholder="Título obtenido">
                                </div>
                                <div class="mb-3">
                                    <label for='otro_estudio' class='form-label'>Otro Estudio*</label>
                                    <input type='text' class='form-control' id='otro_estudio' name='otro_estudio' placeholder="Especificar">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-5 experience-col">
                            <div class="info-section">
                                <div class="card-header mb-3">Experiencia (Años)</div>
                                <div class="mb-3">
                                    <label for='experiencia_docente' class='form-label'>Experiencia Docente</label>
                                    <input type='text' class='form-control' id='experiencia_docente' name='experiencia_docente' placeholder="Años">
                                </div>
                                <div class="mb-3">
                                    <label for='experiencia_profesional' class='form-label'>Experiencia Profesional</label>
                                    <input type='text' class='form-control' id='experiencia_profesional' name='experiencia_profesional' placeholder="Años ">
                                </div>
                                <div class="mb-3">
                                    <label for='otra_experiencia' class='form-label'>Otra Experiencia</label>
                                    <input type='text' class='form-control' id='otra_experiencia' name='otra_experiencia' placeholder="Años">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-muted small">
                        * Por favor especificar cualquier otro estudio relevante no incluido en las categorías anteriores.
                    </div>
                </form>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button>
                <button type='submit' form='actaForm' class='btn btn-primary'>Guardar y Descargar</button>
            </div>
        </div>
    </div>
</div>
<script>
    // JavaScript function to toggle the visibility of the table and rotate the icon
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        const icon = document.getElementById('icon_' + sectionId);

        if (section.style.display === 'none') {
            section.style.display = 'block'; // Show the section
            icon.classList.remove('fa-caret-right'); // Change icon to down arrow
            icon.classList.add('fa-caret-down');
        } else {
            section.style.display = 'none'; // Hide the section
            icon.classList.remove('fa-caret-down'); // Change icon to right arrow
            icon.classList.add('fa-caret-right');
        }
    }

</script>
        
<script>
document.addEventListener('DOMContentLoaded', function() {
    const actaModal = document.getElementById('actaModal');

  if (actaModal) {
        actaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            actaModal.currentButton = button;

            // --- 1. CAPTURAR TODOS LOS DATOS DEL BOTÓN ---
            const id_solicitud = button.getAttribute('data-id-solicitud');
            const departamento_id = button.getAttribute('data-departamento-id');
            const anio_semestre = button.getAttribute('data-anio-semestre');
            const cedulaProfesor = button.getAttribute('data-cedula-profesor');
            const numero_acta = button.getAttribute('data-numero-acta');
            const fecha_acta = button.getAttribute('data-fecha-acta');
            
            // Datos de estudio y experiencia del botón
            const pregrado_btn = button.getAttribute('data-pregrado');
            const especializacion_btn = button.getAttribute('data-especializacion');
            const maestria_btn = button.getAttribute('data-maestria');
            const doctorado_btn = button.getAttribute('data-doctorado');
            const otro_estudio_btn = button.getAttribute('data-otro_estudio');
            const exp_docente_btn = button.getAttribute('data-experiencia-docente');
            const exp_profesional_btn = button.getAttribute('data-experiencia-profesional');
            const otra_exp_btn = button.getAttribute('data-otra-experiencia');

            // --- 2. ASIGNAR VALORES QUE NO DEPENDEN DEL AJAX ---
            document.getElementById('modal_id_solicitud').value = id_solicitud || '';
            document.getElementById('modal_departamento_id').value = departamento_id || '';
            document.getElementById('modal_anio_semestre').value = anio_semestre || '';
            document.getElementById('modal_cedula_profesor').value = cedulaProfesor || '';
            document.getElementById('numero_acta').value = numero_acta || '';
            document.getElementById('fecha_actab').value = fecha_acta || '';

            // --- 3. LÓGICA CONDICIONAL PARA LLENAR LOS CAMPOS ---
            const allStudyFieldsEmpty = !pregrado_btn && !especializacion_btn && !maestria_btn && !doctorado_btn;

            // CASO A: Si los campos de estudio en el botón están vacíos, usar AJAX
            if (allStudyFieldsEmpty && cedulaProfesor) {
                // Limpiar campos por si tenían datos de un modal anterior
                document.getElementById('pregrado').value = '';
                document.getElementById('especializacion').value = '';
                document.getElementById('maestria').value = '';
                document.getElementById('doctorado').value = '';

                fetch(`get_profesor_data.php?cedula=${cedulaProfesor}&anioSemestre=${anio_semestre}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.titulos) {
                            const parsedTitulos = parseTitulos(data.titulos);
                            // Llenar el formulario con los datos del AJAX
                            document.getElementById('pregrado').value = parsedTitulos.pregrado || '';
                            document.getElementById('especializacion').value = parsedTitulos.especializacion || '';
                            document.getElementById('maestria').value = parsedTitulos.maestria || '';
                            document.getElementById('doctorado').value = parsedTitulos.doctorado || '';
                        }
                    })
                    .catch(error => console.error('Error al obtener datos del profesor:', error));
            
            // CASO B: Si los campos tienen datos, usarlos directamente
            } else {
                document.getElementById('pregrado').value = pregrado_btn || '';
                document.getElementById('especializacion').value = especializacion_btn || '';
                document.getElementById('maestria').value = maestria_btn || '';
                document.getElementById('doctorado').value = doctorado_btn || '';
            }

            // Asignar siempre los otros campos que no dependen de la condición
            document.getElementById('otro_estudio').value = otro_estudio_btn || '';
            document.getElementById('experiencia_docente').value = exp_docente_btn || '';
            document.getElementById('experiencia_profesional').value = exp_profesional_btn || '';
            document.getElementById('otra_experiencia').value = otra_exp_btn || '';
        });

        // Función para parsear el string de títulos (¡AHORA USA startsWith() Y PRIORIZA!)
        function parseTitulos(titulosStr) {
            const parsed = {
                pregrado: '',
                especializacion: '',
                maestria: '',
                doctorado: ''
            };

            // Definir palabras clave para cada tipo de estudio
            // Se han ajustado para ser más efectivas con startsWith()
            const keywords = {
                doctorado: ['DOCTORADO EN', 'DOCTOR', 'DOCTORA', 'PH.D.', 'PHD'],
                maestria: [
                    'MAESTRIA EN', 'MAESTRÍA EN', 'MAGISTER EN', 'MASTER EN',
                    'MAGISTER', 'MAESTRO', 'MASTER', 'MAGÍSTER', 'MAESTRÍA', 'MAESTRA', 'MÁSTER' 
                ],
                especializacion: ['ESPECIALIZACION', 'ESPECIALIZACIÓN', 'ESP.', 'ESPECIALISTA'],
                pregrado: [ // Palabras clave para pregrado, adaptadas para startsWith()
                   'TECNÓLOGA ', 'TECNÓLOGO ',
                    'LICENCIADO EN', 'LICENCIADA EN', 'LICENCIATURA EN', 
                    'PROFESIONAL EN', 'INGENIERO EN', 'INGENIERA EN',
                    'ABOGADO', 'ABOGADA', 'ADMINISTRADOR DE', 'ADMINISTRADORA DE', 
                    'BIOLOGO', 'BIOLOGA', 'QUIMICO', 'QUÍMICO', 'CIRUJANO', 'ANTROPOLOGO', 
                    'ENFERMERO', 'ENFERMERA', 'TECNICO EN', 'TÉCNICO EN', 'TECNOLOGO EN', 'TECNÓLOGO EN',
                    'MEDICO', 'MÉDICO', 'MATEMATICO', 'MATEMÁTICO', 'CONTADOR', 'ECONOMISTA', 
                    'BACHILLER', 'NORMALISTA', 'ARQUITECTO', 'ARQUITECTA', 'FILOSOFO', 'FILOSOFA', 
                    'PSICOLOGO', 'PSICOLOGA', 'CITOHISTOTECNOLOGO', 'BACTERIOLOGO', 'BACTERIOLOGA',
                    'LABORATORISTA', 'GEOTECNOLOGO', 'GEOTECNOLOGA', 'GEOGRAFO', 'GEOGRAFA', 
                    'ODONTOLOGO', 'ODONTOLOGA', 'NUTRICIONISTA', 'FISIOTERAPEUTA',
                    'COMUNICADOR', 'PERIODISTA', 'DISEÑADOR', 'SOCIOLOGO', 'HISTORIADOR',
                    'POLITOLOGO', 'QUÍMICO FARMACÉUTICO', 'ZOOTECNISTA', 'AGRONOMO',
                    // Palabras clave más cortas que son comunes al inicio de un título, pero menos genéricas que 'ARTE'
                    'INGENIERO', 'INGENIERA', // Aunque hay 'Ingeniero en', estas son genéricas al inicio
                    'LICENCIADO', 'LICENCIADA', 'LICENCIATURA',
                    'TECNICO', 'TÉCNICO', 'TECNOLOGO', 'TECNÓLOGO',
                    'ADMINISTRADOR', 'ADMINISTRADORA',
                    'BACHILLER', 'NORMALISTA',
                    'MÚSICA', 'ARTE', // Para casos donde el pregrado es simplemente "Música" o "Arte"
                    'GUIA' // Para Guianza Turística Bilingüe
                ]
            };

            // Dividir el string por saltos de línea, lo que nos dará cada título o frase
            const titlesArray = titulosStr.split(/[\r\n]+/); 

            for (const title of titlesArray) {
                const trimmedTitle = title.trim();
                if (!trimmedTitle) continue;

                const upperTrimmedTitle = trimmedTitle.toUpperCase();

                // 1. Intentar detectar Doctorado (máxima prioridad)
                if (!parsed.doctorado) { 
                    for (const keyword of keywords.doctorado) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.doctorado = trimmedTitle;
                            break; 
                        }
                    }
                    if (parsed.doctorado) continue; // Si se encontró, pasa al siguiente título del array y no busca más
                }

                // 2. Intentar detectar Maestría
                if (!parsed.maestria) { 
                    for (const keyword of keywords.maestria) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.maestria = trimmedTitle;
                            break; 
                        }
                    }
                    if (parsed.maestria) continue; // Si se encontró, pasa al siguiente título del array y no busca más
                }

                // 3. Intentar detectar Especialización
                if (!parsed.especializacion) { 
                    for (const keyword of keywords.especializacion) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.especializacion = trimmedTitle;
                            break; 
                        }
                    }
                    if (parsed.especializacion) continue; // Si se encontró, pasa al siguiente título del array y no busca más
                }

                // 4. Intentar detectar Pregrado (última prioridad)
                if (!parsed.pregrado) { 
                    for (const keyword of keywords.pregrado) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.pregrado = trimmedTitle;
                            break; 
                        }
                    }
                }
            }
            return parsed;
        }

        // El resto de tu script (envío de formulario, etc.) permanece igual.
        document.getElementById('actaForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const form = this;

            const newData = {
                numero_acta: document.getElementById('numero_acta').value,
                fecha_actab: document.getElementById('fecha_actab').value,
                pregrado: document.getElementById('pregrado').value,
                especializacion: document.getElementById('especializacion').value,
                maestria: document.getElementById('maestria').value,
                doctorado: document.getElementById('doctorado').value,
                otro_estudio: document.getElementById('otro_estudio').value,
                experiencia_docente: document.getElementById('experiencia_docente').value,
                experiencia_profesional: document.getElementById('experiencia_profesional').value,
                otra_experiencia: document.getElementById('otra_experiencia').value
            };

            if (actaModal.currentButton) {
                const button = actaModal.currentButton;

                button.setAttribute('data-numero-acta', newData.numero_acta);
                button.setAttribute('data-fecha-acta', newData.fecha_actab);
                button.setAttribute('data-pregrado', newData.pregrado);
                button.setAttribute('data-especializacion', newData.especializacion);
                button.setAttribute('data-maestria', newData.maestria);
                button.setAttribute('data-doctorado', newData.doctorado);
                button.setAttribute('data-otro_estudio', newData.otro_estudio);
                button.setAttribute('data-experiencia-docente', newData.experiencia_docente);
                button.setAttribute('data-experiencia-profesional', newData.experiencia_profesional);
                button.setAttribute('data-otra-experiencia', newData.otra_experiencia);
            }

            setTimeout(() => {
                form.submit();
            }, 500);
        });
    }
});
</script>
       
    </div>    </div>

      
<!-- Botón "Ver Departamento" -->
        <div class="institutional-cardb" >
        
            

   <script>
  // Mostrar el valor de la variable $tipo_docente en la consola
  console.log("Tipo de Docente: <?php echo htmlspecialchars($tipo_docente); ?>");
</script>         
    <?php        
            
// Consulta SQL para obtener los datos
$sqlb = "SELECT 
    depto_periodo.fk_depto_dp
FROM 
    depto_periodo
JOIN
    solicitudes_working_copy ON solicitudes_working_copy.anio_semestre = depto_periodo.periodo 
    AND solicitudes_working_copy.departamento_id = depto_periodo.fk_depto_dp
JOIN 
    deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp

WHERE 
    fk_depto_dp = '$departamento_id' 
    AND depto_periodo.periodo = '$anio_semestre'
    AND (solicitudes_working_copy.estado <> 'an' OR solicitudes_working_copy.estado IS NULL)

GROUP BY 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio,
    depto_periodo.dp_estado_ocasional,
    depto_periodo.dp_estado_total,
    depto_periodo.dp_estado_catedra;
";


$resultb = $conn->query($sqlb);
//echo "consulta". $sql;
            
            
            
            
$count_pendientes = 0; // Inicializar en 0

// Consulta para contar las solicitudes en estado 'PENDIENTE'
$sql_count_pendientes = "SELECT COUNT(DISTINCT cedula) AS total_pendientes 
                         FROM solicitudes_working_copy
                         WHERE departamento_id = ?
                         AND anio_semestre = ?
                         AND estado_depto = 'PENDIENTE'";

$stmt_count = $conn->prepare($sql_count_pendientes);
if ($stmt_count) {
    $stmt_count->bind_param("ss", $departamento_id, $anio_semestre);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $count_pendientes = $row_count['total_pendientes'];
    $stmt_count->close();
}
?>

<div class="">
 
    
    
    
<div class="container-fluid mt-1">
<h2 class="section-subtitleb">Novedades propuestas en el oficio</h2>
  
<div class="card mb-5 shadow-lg">
    <div class="card-header text-white" style="background-color: #FFA726;">
        <h4 class="mb-0"><i class="fas fa-pencil-alt me-3"></i>Modificar</h4>
    </div>
  <?php
// 1. UNIFICAR LOS DATOS EN UN SOLO ARRAY
$data_combinada = [];

// Añadir los datos de 'cambios_vinculacion' con un identificador de origen
if (!empty($data_cambios_vinculacion)) {
    foreach ($data_cambios_vinculacion as $row) {
        $row['origen'] = 'cambio_vinculacion'; // Clave para identificar el tipo de fila
        $data_combinada[] = $row;
    }
}

// Añadir los datos de 'modificar' con su propio identificador
if (!empty($data_modificar)) {
    foreach ($data_modificar as $row) {
        $row['origen'] = 'modificar'; // Clave para identificar el tipo de fila
        $data_combinada[] = $row;
    }
}
?>

<div class="card-body p-2">
    <?php if (!empty($data_combinada)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Sede</th>
                        <th>Dedic/Hrs</th>
                        <th>Observación</th>
                        <th>Deshacer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_combinada as $row): ?>
                        <?php
                        // Lógica común para aplicar estilo a la fila si está pendiente
                        $row_class = '';
                        if (strtolower(trim($row['estado_depto'] ?? '')) === 'pendiente') {
                            $row_class = 'fw';
                        }
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td><?= htmlspecialchars($row['cedula']) ?></td>
                            <td title="<?= htmlspecialchars($row['nombre']) ?>">
                                <?= htmlspecialchars(mb_strimwidth($row['nombre'], 0, 14, '…')) ?>
                            </td>
                            <td><?= htmlspecialchars($row['tipo_docente']) ?></td>
                            <td>
                                <?php
                                // Lógica para abreviar la sede (común para ambas tablas)
                                $sede = $row['sede'] ?? '';
                                if ($sede === 'Popayán') echo 'Pop';
                                elseif ($sede === 'Regionalización') echo 'Reg';
                                elseif ($sede === 'Popayán-Regionalización') echo 'Pop-Reg';
                                else echo htmlspecialchars($sede);
                                ?>
                            </td>
                            <td>
                                <?php
                                // Lógica para la columna "Dedic/Hrs"
                                $tipo_docente = $row['tipo_docente'] ?? '';
                                if ($tipo_docente === 'Ocasional') {
                                    if (($row['sede'] ?? '') === 'Regionalización') {
                                        echo htmlspecialchars($row['tipo_dedicacion_r'] ?? '');
                                    } else {
                                        echo htmlspecialchars($row['tipo_dedicacion'] ?? '');
                                    }
                                } elseif ($tipo_docente === 'Catedra') {
                                    $horas_pop = $row['horas'] ?? '';
                                    $horas_reg = $row['horas_r'] ?? '';
                                    if (($row['sede'] ?? '') === 'Popayán') {
                                        echo htmlspecialchars($horas_pop);
                                    } elseif (($row['sede'] ?? '') === 'Regionalización') {
                                        echo htmlspecialchars($horas_reg);
                                    } elseif (($row['sede'] ?? '') === 'Popayán-Regionalización') {
                                        echo htmlspecialchars($horas_pop) . '; ' . htmlspecialchars($horas_reg);
                                    } else {
                                        echo htmlspecialchars($horas_pop);
                                    }
                                } else {
                                    echo htmlspecialchars($row['tipo_dedicacion'] ?? '');
                                }
                                ?>
                            </td>
                            <td title="<?= htmlspecialchars($row['s_observacion']) ?>">
                                <?= htmlspecialchars(mb_strimwidth($row['s_observacion'], 0, 14, '…')) ?>
                            </td>
                            <td class="td-simple" style="padding: 0; vertical-align: middle;">
                                <?php if ($tipo_usuario == 3): ?>
                                    <?php
                                    // Lógica común para deshabilitar el botón
                                    $estado_facultad = strtolower(trim($row['estado_facultad'] ?? ''));
                                    $estado_depto = strtolower(trim($row['estado_depto'] ?? ''));
                                    $deshacer_disabled_attr = '';
                                    $deshacer_title_message = '';
                                    
                                    if ($estado_facultad === 'aprobado') {
                                        $deshacer_disabled_attr = 'disabled';
                                        $deshacer_title_message = 'No se puede deshacer una novedad ya aprobada por la facultad.';
                                    } elseif ($estado_depto === 'enviado' && $estado_facultad !== 'rechazado') {
                                        $deshacer_disabled_attr = 'disabled';
                                        $deshacer_title_message = 'No se puede deshacer una novedad ya enviada (a menos que esté rechazada).';
                                    }
                                    
                                    // Determinar el formulario y mensaje específico según el origen de los datos
                                    if ($row['origen'] === 'cambio_vinculacion') {
                                        $form_action = 'deshacer_cambio_vinculacion.php';
                                        $form_id = 'formDeshacerCambio_' . htmlspecialchars($row["id_solicitud"]);
                                        $confirm_message = 'Cambio de Vinculación';
                                        if (empty($deshacer_disabled_attr)) $deshacer_title_message = 'Deshacer Cambio de Vinculación';
                                    } else { // Origen es 'modificar'
                                        $form_action = 'deshacer_novedad_mod.php';
                                        $form_id = 'formDeshacerMod_' . htmlspecialchars($row["id_solicitud"]);
                                        $confirm_message = 'Modificación';
                                        if (empty($deshacer_disabled_attr)) $deshacer_title_message = 'Deshacer Modificación';
                                    }
                                    ?>
                                    <form action='<?= $form_action ?>' method='POST' style='display:inline;' id='<?= $form_id ?>'>
                                        <input type='hidden' name='id_solicitud' value='<?= htmlspecialchars($row["id_solicitud"]) ?>'>
                                        <input type='hidden' name='anio_semestre' value='<?= htmlspecialchars($anio_semestre) ?>'>
                                        <input type='hidden' name='departamento_id' value='<?= htmlspecialchars($departamento_id) ?>'>
                                        <?php if ($row['origen'] === 'modificar'): ?>
                                            <input type='hidden' name='novedad_a_deshacer' value='modificar'>
                                        <?php endif; ?>
                                        
                                        <button type='button'
                                                title='<?= htmlspecialchars($deshacer_title_message) ?>'
                                                style="padding: 0; margin: 0; border: none; background: none; line-height: 1; font-size: 14px;"
                                                <?= $deshacer_disabled_attr ?>
                                                onclick="confirmDeshacer('<?= $confirm_message ?>', '<?= $form_id ?>');">
                                            <i class='fas fa-undo'></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            No se han detectado cambios de vinculación ni modificaciones.
        </div>
    <?php endif; ?>
</div></div>
<div class="card mb-5 shadow-lg">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">
            <i class="fas fa-plus-circle me-3"></i>Vincular
            <?php
            // Obtener el número de elementos en el array $data_adicionar
            // Esto te dará el conteo exacto de las novedades de tipo 'Adicionar'
            $conteo_adicionar = count($data_adicionar);
            $conteo_adicionar_pend= $count_adicionar_pend;
            ?>
            <?php if ($conteo_adicionar > 0): ?>
               

            <?php endif; ?>
            
             <?php if ($conteo_adicionar_pend > 0): ?><?php endif; ?>
        </h4>
    </div>
    <div class="card-body p-2">

        <?php if (!empty($data_adicionar)): ?>
            <div class="table-responsive">
                <table id="tableAdicionar" class="table table-striped table-hover table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Sede</th>
                            <?php
                            // Determinar si al menos un docente es Ocasional o Catedra para mostrar los encabezados
                            $hasOcasional = false;
                            $hasCatedra = false;
                            $hojas_vida_adicionar_count = 0;
                            foreach ($data_adicionar as $row_check) {
                                if (($row_check['tipo_docente'] ?? '') === 'Ocasional') {
                                    $hasOcasional = true;
                                } elseif (($row_check['tipo_docente'] ?? '') === 'Catedra') {
                                    $hasCatedra = true;
                                }

                                $anexa_hv_nuevo = strtolower(trim($row_check['anexa_hv_docente_nuevo'] ?? ''));
                                $actualiza_hv_antiguo = strtolower(trim($row_check['actualiza_hv_antiguo'] ?? ''));

                                if ($anexa_hv_nuevo === 'si' || $actualiza_hv_antiguo === 'si') {
                                    $hojas_vida_adicionar_count++;
                                }
                            }

                            if ($hasOcasional && !$hasCatedra) { // Solo Ocasional en esta tabla
                                echo '<th>Dedic</th>';
                            } elseif ($hasCatedra && !$hasOcasional) { // Solo Catedra en esta tabla
                                echo '<th>Horas</th>';
                            } else { // Ambos tipos o ninguno, mostramos ambos encabezados
                                echo '<th>Dedic/hrs</th>';
                            }
                            ?>
                            <th>Observación</th>
                            
                           <th>Deshacer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_adicionar as $row): ?>
                            <?php
                            // Definir la clase para la fila basada en el estado_depto
                            $row_class = '';
                            $estado_depto_current = strtolower(trim($row['estado_depto'] ?? ''));
                            if ($estado_depto_current === 'pendiente') {
                                $row_class = 'fw'; // Clase de Bootstrap 5 para texto en negrilla
                            }
                            ?>
                            <tr class="<?= $row_class ?>">
                                                        <td><?= htmlspecialchars($row['cedula']) ?></td>
                                <td title="<?= htmlspecialchars($row['nombre']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($row['nombre'], 0, 14, '…')) ?>
                                </td>
                                <td><?= htmlspecialchars($row['tipo_docente']) ?></td>
<td>
                                <?php
                                    $sede = $row['sede'];
                                    if ($sede === 'Popayán') {
                                        echo 'Pop';
                                    } elseif ($sede === 'Regionalización') {
                                        echo 'Reg';
                                    } elseif ($sede === 'Popayán-Regionalización') {
                                        echo 'Pop-Reg';
                                    } else {
                                        echo htmlspecialchars($sede); // por si aparece otra sede
                                    }
                                ?>
                                </td> 
                                <?php if (($row['tipo_docente'] ?? '') === 'Ocasional'): ?>
                                    <?php if (($row['sede'] ?? '') === 'Popayán'): ?>
                                        <td><?= htmlspecialchars($row['tipo_dedicacion']) ?></td>
                                        <td style="display:none;"></td>
                                    <?php elseif (($row['sede'] ?? '') === 'Regionalización'): ?>
                                        <td><?= htmlspecialchars($row['tipo_dedicacion_r']) ?></td>
                                        <td style="display:none;"></td>
                                    <?php else: ?>
                                        <td><?= htmlspecialchars($row['tipo_dedicacion']) ?></td>
                                        <td style="display:none;"></td>
                                    <?php endif; ?>
                                <?php elseif (($row['tipo_docente'] ?? '') === 'Catedra'): ?>
                                    <?php if (($row['sede'] ?? '') === 'Popayán'): ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas']) ?></td>
                                    <?php elseif (($row['sede'] ?? '') === 'Regionalización'): ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas_r']) ?></td>
                                    <?php elseif (($row['sede'] ?? '') === 'Popayán-Regionalización'): ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas']) . '; ' . htmlspecialchars($row['horas_r']) ?></td>
                                    <?php else: ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas']) ?></td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($row['tipo_dedicacion']) ?></td>
                                    <td><?= htmlspecialchars($row['horas']) ?></td>
                                <?php endif; ?>

                                <td title="<?= htmlspecialchars($row['s_observacion']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($row['s_observacion'], 0, 14, '…')) ?>
                                </td>

                              
                                
                                              <?php
                        // Asegúrate de que $estado_facultad y $estado_depto están definidos y normalizados
                        $estado_facultad = strtolower(trim($row['estado_facultad'] ?? ''));
                        $estado_depto = strtolower(trim($row['estado_depto'] ?? ''));

                        // === Variables para el estado del botón Deshacer Adición ===
                        $deshacer_adicion_disabled_attr = '';
                        $deshacer_adicion_title_message = 'Deshacer Adición'; // Título predeterminado

                        // Deshabilitar si el estado_facultad es 'aprobado'
                        if ($estado_facultad === 'aprobado') {
                            $deshacer_adicion_disabled_attr = 'disabled';
                            $deshacer_adicion_title_message = 'No se puede deshacer una novedad de adición que ya ha sido aprobada por la facultad.';
                        } 
                        // Deshabilitar si el estado_depto es 'enviado' Y no está rechazado por facultad
                        elseif ($estado_depto === 'enviado' && $estado_facultad !== 'rechazado') {
                            $deshacer_adicion_disabled_attr = 'disabled';
                            $deshacer_adicion_title_message = 'No se puede deshacer una novedad de adición ya enviada a la facultad (a menos que esté rechazada).';
                        }
                        // Si está rechazado por facultad → Botón habilitado aunque estado_depto sea "enviado"
                        ?>
                        <td class="td-simple" style="padding: 0; vertical-align: middle;">
                            <?php if ($tipo_usuario == 3): ?>
                                <form action='deshacer_novedad_mod.php' method='POST' style='display:inline;' id='formDeshacerAdicion_<?= htmlspecialchars($row["id_solicitud"]) ?>'>
                                    <input type='hidden' name='id_solicitud' value='<?= htmlspecialchars($row["id_solicitud"]) ?>'>
                                    <input type='hidden' name='anio_semestre' value='<?= htmlspecialchars($anio_semestre) ?>'>
                                    <input type='hidden' name='departamento_id' value='<?= htmlspecialchars($departamento_id) ?>'>
                                    <input type='hidden' name='novedad_a_deshacer' value='adicionar'>
                                    <button type='button'
                                            title='<?= htmlspecialchars($deshacer_adicion_title_message) ?>'
                                            style="padding: 0; margin: 0; border: none; background: none; line-height: 1; font-size: 14px;"
                                            <?= $deshacer_adicion_disabled_attr ?>
                                            onclick="confirmDeshacer('Adición', 'formDeshacerAdicion_<?= htmlspecialchars($row["id_solicitud"]) ?>');"
                                    >
                                        <i class='fas fa-undo'></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                                                                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>No hay novedades de tipo "Vicular" (nuevo profesor) para este período y departamento.
            </div>
        <?php endif; 
        
        ?>
    </div>
</div>

<hr>

<div class="card mb-5 shadow-lg">
    <div class="card-header bg-danger text-white">
        <h4 class="mb-0"><i class="fas fa-minus-circle me-3"></i>Desvincular</h4>
    </div>
    <div class="card-body p-2">
        <?php if (!empty($data_eliminar)): ?>
            <div class="table-responsive">
                <table id="tableEliminar" class="table table-striped table-hover table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Sede</th>
                            <?php
                            $hasOcasional = false;
                            $hasCatedra = false;
                            foreach ($data_eliminar as $row_check) {
                                if (($row_check['tipo_docente'] ?? '') === 'Ocasional') {
                                    $hasOcasional = true;
                                } elseif (($row_check['tipo_docente'] ?? '') === 'Catedra') {
                                    $hasCatedra = true;
                                }
                            }

                            if ($hasOcasional && !$hasCatedra) {
                                echo '<th>Dedic</th>';
                            } elseif ($hasCatedra && !$hasOcasional) {
                                echo '<th>Horas</th>';
                            } else {
                                echo '<th>Dedic/hrs</th>';
                            }
                            ?>
                            <th>Observación</th>
                           
                           <th>Deshacer</th>
                        </tr>
                    </thead>
                    <tbody>
                       <?php foreach ($data_eliminar as $row): ?>
    <?php
    $row_class = '';
    $estado_depto_current = strtolower(trim($row['estado_depto'] ?? ''));
    if ($estado_depto_current === 'pendiente') {
        $row_class = 'fw'; // Clase de Bootstrap 5 para texto en negrilla
    }
    ?>
    <tr class="<?= $row_class ?>">
                                <td><?= htmlspecialchars($row['cedula']) ?></td>
                                <td title="<?= htmlspecialchars($row['nombre']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($row['nombre'], 0, 14, '…')) ?>
                                </td>
                                <td><?= htmlspecialchars($row['tipo_docente']) ?></td>
<td>
                                <?php
                                    $sede = $row['sede'];
                                    if ($sede === 'Popayán') {
                                        echo 'Pop';
                                    } elseif ($sede === 'Regionalización') {
                                        echo 'Reg';
                                    } elseif ($sede === 'Popayán-Regionalización') {
                                        echo 'Pop-Reg';
                                    } else {
                                        echo htmlspecialchars($sede); // por si aparece otra sede
                                    }
                                ?>
                                </td> 
                                <?php if (($row['tipo_docente'] ?? '') === 'Ocasional'): ?>
                                    <?php if (($row['sede'] ?? '') === 'Popayán'): ?>
                                        <td><?= htmlspecialchars($row['tipo_dedicacion']) ?></td>
                                        <td style="display:none;"></td>
                                    <?php elseif (($row['sede'] ?? '') === 'Regionalización'): ?>
                                        <td><?= htmlspecialchars($row['tipo_dedicacion_r']) ?></td>
                                        <td style="display:none;"></td>
                                    <?php else: ?>
                                        <td><?= htmlspecialchars($row['tipo_dedicacion']) ?></td>
                                        <td style="display:none;"></td>
                                    <?php endif; ?>
                                <?php elseif (($row['tipo_docente'] ?? '') === 'Catedra'): ?>
                                    <?php if (($row['sede'] ?? '') === 'Popayán'): ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas']) ?></td>
                                    <?php elseif (($row['sede'] ?? '') === 'Regionalización'): ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas_r']) ?></td>
                                    <?php elseif (($row['sede'] ?? '') === 'Popayán-Regionalización'): ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas']) . '; ' . htmlspecialchars($row['horas_r']) ?></td>
                                    <?php else: ?>
                                        <td style="display:none;"></td>
                                        <td><?= htmlspecialchars($row['horas']) ?></td>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($row['tipo_dedicacion']) ?></td>
                                    <td><?= htmlspecialchars($row['horas']) ?></td>
                                <?php endif; ?>

                                <td title="<?= htmlspecialchars($row['s_observacion']) ?>">
                                    <?= htmlspecialchars(mb_strimwidth($row['s_observacion'], 0, 14, '…')) ?>
                                </td>

                              
                                
                                    <?php
                        // Asegúrate de que $estado_facultad y $estado_depto se definen y normalizan ANTES de este bloque
                        $estado_facultad = strtolower(trim($row['estado_facultad'] ?? ''));
                        $estado_depto = strtolower(trim($row['estado_depto'] ?? ''));

                        // === Variables para el estado del botón Deshacer Eliminación ===
                        $deshacer_eliminar_disabled_attr = '';
                        $deshacer_eliminar_title_message = 'Deshacer Eliminación'; // Título predeterminado

                        // Deshabilitar si el estado_facultad es 'aprobado'
                        if ($estado_facultad === 'aprobado') {
                            $deshacer_eliminar_disabled_attr = 'disabled';
                            $deshacer_eliminar_title_message = 'No se puede deshacer una novedad de desvinculación que ya ha sido aprobada por la facultad.';
                        } 
                        // Deshabilitar si el estado_depto es 'enviado' Y no está rechazado por facultad
                        elseif ($estado_depto === 'enviado' && $estado_facultad !== 'rechazado') {
                            $deshacer_eliminar_disabled_attr = 'disabled';
                            $deshacer_eliminar_title_message = 'No se puede deshacer una novedad de desvinculación ya enviada a la facultad (a menos que esté rechazada).';
                        }
                        // Si está rechazado por facultad → Botón habilitado aunque estado_depto sea "enviado"
                        ?>
                        <td class="td-simple" style="padding: 0; vertical-align: middle;">
                            <?php if ($tipo_usuario == 3): ?>
                                <form action='deshacer_novedad_mod.php' method='POST' style='display:inline;' id='formDeshacerEliminar_<?= htmlspecialchars($row["id_solicitud"]) ?>'>
                                    <input type='hidden' name='id_solicitud' value='<?= htmlspecialchars($row["id_solicitud"]) ?>'>
                                    <input type='hidden' name='anio_semestre' value='<?= htmlspecialchars($anio_semestre) ?>'>
                                    <input type='hidden' name='departamento_id' value='<?= htmlspecialchars($departamento_id) ?>'>
                                    <input type='hidden' name='novedad_a_deshacer' value='eliminar'>
                                    <button type='button'
                                            title='<?= htmlspecialchars($deshacer_eliminar_title_message) ?>'
                                            style="padding: 0; margin: 0; border: none; background: none; line-height: 1; font-size: 14px;"
                                            <?= $deshacer_eliminar_disabled_attr ?>
                                            onclick="confirmDeshacer('Eliminación', 'formDeshacerEliminar_<?= htmlspecialchars($row["id_solicitud"]) ?>');"
                                    >
                                        <i class='fas fa-undo'></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                                                                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>No hay novedades de tipo "Desvincular" para este período y departamento.
            </div>
        <?php endif; ?>
    </div>
</div>

<hr>


<div class="row mt-3 unacauca-actions-section">
    

    <div class="col-md-12 text-center mb-3 d-grid">
  
<?php if ($tipo_usuario == 3 && $count_pendientes > 0): ?>
    <div class="alert alert-info shadow-sm border-0 mb-4" role="alert" style="background-color: #f0f7ff; border-left: 4px solid #0d6efd !important;">
        <div class="d-flex align-items-center">
            <i class="fas fa-layer-group me-3" style="font-size: 1.5rem; color: #0d6efd;"></i>
            <div class="text-start">
                <h6 class="fw-bold mb-1" style="color: #084298;">Borrador de novedades en curso</h6>
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    Tienes <strong><?php echo $count_pendientes; ?></strong> docente(s) con novedades pendientes. 
                    Puedes seguir realizando adiciones, cambios o eliminaciones para <strong>consolidar tu grupo de solicitudes</strong> antes del envío final.
                </p>
                <div class="mt-2 py-1 px-2 d-inline-block" style="background-color: rgba(13, 110, 253, 0.08); border-radius: 4px;">
                    <p class="mb-0" style="font-size: 0.85rem; color: #084298;">
                        <i class="fas fa-info-circle me-1"></i> 
                        Cuando el lote esté completo, usa el botón <span class="badge bg-primary">Enviar a Facultad</span> para tramitarlos todos en un mismo oficio.
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

        <button class="btn btn-unicauca-primary btn-lg <?= ($count_pendientes > 0) ? 'btn-highlight' : '' ?>" 
                id="btnEnviarFacultad" 
                style="border-radius: 30px; font-weight: bold;">
            <i class="fas fa-file-download me-2"></i> 
            ENVIAR A FACULTAD (Descargar Oficio)
        </button>

        <div class="col-md-12 text-center mb-3 d-grid">
            <a href="consulta_facultad_novedad12.php?departamento_id=<?= $departamento_id ?>&anio_semestre=<?= $anio_semestre ?>" 
               class="mt-2 d-block small text-muted">
                Ver listado completo de novedades
            </a>
        </div>

<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content unacauca-modal-content">
            <div class="modal-header unacauca-modal-header">
                <h5 class="modal-title" id="myModalLabel">Información Adicional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body unacauca-modal-body">
                <form id="modalForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="num_oficio" class="form-label">Número de Oficio</label>
                            <input type="text" class="form-control unacauca-input" id="num_oficio" name="num_oficio" value="<?php echo obtenerTRDDepartamento($departamento_id) . '/'; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_oficio" class="form-label">Fecha de Oficio</label>
                            <input type="date" class="form-control unacauca-input" id="fecha_oficio" name="fecha_oficio" value="<?php echo date('Y-m-d', strtotime('next Monday', strtotime(date('Y-m-d')))); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="elaboro" class="form-label">Jefe de Departamento<sup>*</sup></label>
                        <input type="text" class="form-control unacauca-input" id="elaboro" name="elaboro" value="<?php echo $profe_en_cargo; ?>" placeholder="Ej. Pedro Perez" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="acta" class="form-label">Número de Acta<sup>*</sup></label>
                            <input type="text" class="form-control unacauca-input" id="acta" name="acta" placeholder="Ej. No. 02" value="<?php echo $num_acta; ?>" >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_acta" class="form-label">Fecha del Acta</label>
                            <input type="date" class="form-control unacauca-input" id="fecha_acta" name="fecha_acta" value="<?php echo $fecha_acta; ?>" >
                        </div>
                    </div>

                    <div class="form-group mt-4 unacauca-folio-section">
                        <h6 class="section-title text-unicauca-primary fw-bold mb-3">Distribución de Folios</h6>
                        <div class="row mb-2 align-items-center">
                            <div class="col-md-8 text-start">
                                <label for="folios1" class="mb-0 label-italic">FOR-59. Acta de Selección</label>
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control folio-input unacauca-input" id="folios1" name="folios1" value="1" min="0" oninput="updateFoliosTotal()" required>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center">
                            <div class="col-md-8 text-start">
                                <label for="folios2" class="mb-0 label-italic">FOR 45. Revisión Requisitos</label>
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control folio-input unacauca-input" id="folios2" name="folios2" value="<?php echo $conteo_adicionar_pend; ?>" min="0" oninput="updateFoliosTotal()" required>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center">
                            <div class="col-md-8 text-start">
                                <label for="folios3" class="mb-0 label-italic">Otros: (hojas de vida y actualizaciones)</label>
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control folio-input unacauca-input" id="folios3" name="folios3"
        value="<?= $hojas_vida_adicionar_count; ?>" min="0" oninput="updateFoliosTotal()"
        onblur="if(this.value === '') this.value = 0;">
                            </div>
                        </div>
                        <div class="mt-3 unacauca-total-folios">
                            <label class="label-italic">Total de Folios:
                                <span id="totalFoliosDisplay" class="label-italic">
                                    <strong>
                                        <?php
                                        // Asegúrate de que estas variables PHP tengan valores numéricos antes de usarlas
                                        // folios1 por defecto es 1 en el input HTML
                                        $folios1_inicial = 1;
                                        // Estas deben ser las variables PHP que contienen los conteos
                                        $folios2_inicial = isset($conteo_adicionar_pend) ? (int)$conteo_adicionar_pend : 0;
                                        $folios3_inicial = isset($hojas_vida_adicionar_count) ? (int)$hojas_vida_adicionar_count : 0;

                                        $totalFoliosCalculadoInicialmente = $folios1_inicial + $folios2_inicial + $folios3_inicial;
                                        echo $totalFoliosCalculadoInicialmente;
                                        ?>
                                    </strong>
                                </span>
                            </label>
                        </div>
                    </div>
                    <input type="hidden" id="folios" name="folios">
                </form>
            </div>
            <div class="modal-footer unacauca-modal-footer">
                <button type="button" class="btn btn-secondary unacauca-btn-secondary modal-close-btn" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary unacauca-btn-primary modal-submit-btn" onclick="submitForm()">Enviar</button>
            </div>
        </div>
    </div>
</div>
    </div>
    
<!-- Incluir jQuery (necesario para DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Incluir JS de Bootstrap (si no está en headerz.php) -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
<!-- Incluir JS de DataTables y sus extensiones -->
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
   <!-- 
    <script>
       function submitForm() {
        updateFoliosTotal();

        var numOficio = document.getElementById('num_oficio').value;
        var fechaOficio = document.getElementById('fecha_oficio').value;
        var elaboro = document.getElementById('elaboro').value;
        var acta = document.getElementById('acta').value;
        var fechaActa = document.getElementById('fecha_acta').value;
        var folios = document.getElementById('folios').value;
        var folios3 = document.getElementById('folios3').value;

        folios3 = folios3.trim() === '' ? 0 : parseInt(folios3, 10);

        // var contadorHV = <?php //echo $contadorHV; ?>; // Uncomment if needed

        // Re-enable validation if needed
        /*if (folios3 < contadorHV) {
            var mensaje = contadorHV === 1
                ? 'Verificar número de folios de ' + contadorHV + ' hoja de vida nueva y/o actualización informada.'
                : 'Verificar número de folios de las ' + contadorHV + ' hojas de vida nuevas y/o actualizaciones informadas.';
            alert(mensaje);
            return;
        }*/

        if (numOficio === '' || fechaOficio === '' || elaboro === '' || acta === '' || fechaActa === '') {
            alert('Por favor, diligencie los campos obligatorios (*).');
            return;
        }

        var departamentoId = "<?php //echo urlencode($departamento_id); ?>";
        var anioSemestre = "<?php //echo urlencode($anio_semestre); ?>";
        var nombrefac = "<?php //echo urlencode($nombre_fac); ?>";

        var url = 'oficio_depto_novedad.php?folios=' + folios + '&departamento_id=' + departamentoId + '&anio_semestre=' + anioSemestre + '&nombre_fac=' + nombrefac + '&num_oficio=' + encodeURIComponent(numOficio) + '&fecha_oficio=' + encodeURIComponent(fechaOficio) + '&elaboro=' + encodeURIComponent(elaboro) + '&acta=' + encodeURIComponent(acta) + '&fecha_acta=' + encodeURIComponent(fechaActa);

        window.location.href = url;

        setTimeout(function() {
            window.location.reload();
        }, 1000);
    }
</script>-->
<script>
    $(document).ready(function() {
        // Inicializar DataTables para cada tabla
        // Configuración común para DataTables
        const dataTableOptions = {
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.3/i18n/es_es.json" // Archivo de idioma español
            },
            "responsive": true, // Habilitar diseño responsivo
            "paging": true,     // Habilitar paginación
            "searching": true,  // Habilitar búsqueda
            "info": true,       // Mostrar información de la tabla
            "ordering": true    // Habilitar ordenación de columnas
        };

        $('#tableAdicionar').DataTable(dataTableOptions);
        $('#tableEliminar').DataTable(dataTableOptions);
        $('#tableModificar').DataTable(dataTableOptions);
    });
</script>
    
 


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
    // Variable PHP pasada a JavaScript
    // Asegúrate de que esta línea PHP está correctamente configurada en tu archivo para pasar el conteo.
    const totalPendientes = <?php echo $count_pendientes ?? 0; ?>; // Usamos ?? 0 para manejar si $count_pendientes no está definido

    document.addEventListener('DOMContentLoaded', function() {
        // ... (Tu código existente para otros modales o funciones, como observacionModal) ...

        const observacionModal = document.getElementById('modalObservacion');

        if (observacionModal) {
            observacionModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // El botón que disparó el modal
                let observationText = button.getAttribute('data-obs'); // Obtiene el texto del atributo data-obs

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = observationText;
                let decodedContent = tempDiv.textContent || tempDiv.innerText || "";

                const finalHtmlToDisplay = decodedContent.replace(/\n/g, '<br>');

                const modalBodyP = observacionModal.querySelector('#observacionContent');
                modalBodyP.innerHTML = finalHtmlToDisplay;
            });
        }

        // === NUEVA LÓGICA PARA EL BOTÓN "Enviar a Facultad" ===
        const btnEnviarFacultad = document.getElementById('btnEnviarFacultad');
        const myModalElement = document.getElementById('myModal');
        let myModalBootstrapInstance = null; // Para almacenar la instancia del modal

        // Función para obtener/crear la instancia de Bootstrap Modal
        const getModalInstance = () => {
            if (!myModalBootstrapInstance) {
                myModalBootstrapInstance = new bootstrap.Modal(myModalElement);
            }
            return myModalBootstrapInstance;
        };

        // Event Listener para el botón "Enviar a Facultad"
        if (btnEnviarFacultad) { // Asegúrate de que el botón existe
            btnEnviarFacultad.addEventListener('click', function() {
                if (totalPendientes > 0) {
                    getModalInstance().show(); // Abre el modal
                } else {
                    alert('No existen solicitudes con estado PENDIENTE para enviar a Facultad en este período. No hay registros para generar el oficio.');
                }
            });
        }
        // === FIN DE LA NUEVA LÓGICA ===

        // ... (El resto de tu JavaScript: updateFoliosTotal, submitForm, reimprOficio_depto, etc.) ...
    }); // Fin de DOMContentLoaded

    // JavaScript for updating total folios (esta función ya estaba fuera de DOMContentLoaded)
    function updateFoliosTotal() {
        const folios1 = parseInt(document.getElementById('folios1').value) || 0;
        const folios2 = parseInt(document.getElementById('folios2').value) || 0;
        const folios3 = parseInt(document.getElementById('folios3').value) || 0;
        const total = folios1 + folios2 + folios3;
        document.getElementById('totalFoliosDisplay').innerHTML = `<strong>${total}</strong>`;
        document.getElementById('folios').value = total;
    }

    // Placeholder functions (estas funciones ya estaban fuera de DOMContentLoaded)
    function reimprOficio_depto() {
        const departamento_id = encodeURIComponent('<?php echo $departamento_id; ?>');
        const anio_semestre = encodeURIComponent('<?php echo $anio_semestre; ?>');
        const url = `oficio_depto_reimpr.php?departamento_id=${departamento_id}&anio_semestre=${anio_semestre}`;
        window.location.href = url;
    }
function submitForm() {
    const form = document.getElementById('modalForm');
    const numOficioInput = document.getElementById('num_oficio');
    const numOficio = numOficioInput.value.trim();

    // Validación: que después del "/" haya un número válido
    const partes = numOficio.split('/');
    const ultimo = partes[partes.length - 1].trim();

    if (ultimo === '' || isNaN(ultimo)) {
        alert('Debe ingresar un número válido después del "/" en el número de oficio.');
        numOficioInput.focus();
        return false; // Detiene envío
    }

    if (form.checkValidity()) {
        alert('Formulario de envío de Oficio de Departamento enviado.');
        // Aquí iría el envío (AJAX, fetch, etc.)
        
        var myModalInstance = bootstrap.Modal.getInstance(document.getElementById('myModal'));
        if (myModalInstance) {
            myModalInstance.hide();
        }
    } else {
        form.reportValidity();
    }
}

    // Existing jQuery-dependent scripts (mantener estos si dependen de jQuery + Bootstrap JS)
    $(document).ready(function() {
        $('#selectAll').change(function() {
            const isChecked = $(this).is(':checked');
            $('.individualCheckbox').prop('checked', isChecked).trigger('change');
        });

        $('.individualCheckbox').change(function() {
            const form = $(this).closest('form');
            $.ajax({
                url: 'update_visto_bueno.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log('Estado actualizado exitosamente.');
                },
                error: function() {
                    alert('Error al actualizar el estado.');
                }
            });
        });

    });

    // Esta función reimprOficio_depto ya estaba definida dos veces; he eliminado la duplicada arriba.
    // Solo se necesita una definición.
</script>


<script>
    $(document).ready(function() {
        // Script for selectAll and individualCheckbox
        $('#selectAll').change(function() {
            const isChecked = $(this).is(':checked');
            $('.individualCheckbox').prop('checked', isChecked).trigger('change');
        });

        $('.individualCheckbox').change(function() {
            const form = $(this).closest('form');
            $.ajax({
                url: 'update_visto_bueno.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log('Estado actualizado exitosamente.');
                },
                error: function() {
                    alert('Error al actualizar el estado.');
                }
            });
        });

        // Bootstrap Tooltip Initialization
  $('[data-toggle="tooltip"]').tooltip({
        html: true,
        placement: 'auto', // Elige automáticamente la mejor posición
        fallbackPlacement: 'flip', // Si no cabe, invierte la posición
        boundary: 'viewport'
    });
        // JavaScript for handling the observation modal
        // Listen for the 'show.bs.modal' event on the observation modal
        $('#modalObservacion').on('show.bs.modal', function(event) {
            // Get the button that triggered the modal
            var button = $(event.relatedTarget);

            // Extract the observation from the 'data-obs' attribute of that button
            var observation = button.data('obs');

            // Debugging: Log the observation to the console
            console.log("Observation received:", observation);

            // Get the modal itself
            var modal = $(this);

            // Find the paragraph where the observation should be displayed
            // Use .text() for plain text. If observations contain HTML (e.g., <br>), use .html()
            modal.find('#textoObservacion').text(observation);

            // Alternative if .text() is not working or if you suspect HTML issues:
            // modal.find('#textoObservacion').html(observation);
            // If observation might have line breaks, ensure the CSS 'white-space: pre-wrap;' is applied to #textoObservacion
        });
    });
</script>
<script>

    document.addEventListener('DOMContentLoaded', function() {
        const fechaOficioInput = document.getElementById('fecha_oficio');
        let today = new Date();
        let day = today.getDay();

        // If today is Saturday (6) or Sunday (0), adjust date to next Monday
        if (day === 6) { // Saturday
            today.setDate(today.getDate() + 2);
        } else if (day === 0) { // Sunday
            today.setDate(today.getDate() + 1);
        }

        // Format date to YYYY-mm-dd for date input value
        let year = today.getFullYear();
        let month = String(today.getMonth() + 1).padStart(2, '0');
        let date = String(today.getDate()).padStart(2, '0');
        let formattedDate = `${year}-${month}-${date}`;

        fechaOficioInput.value = formattedDate;
    });
</script>

<script>
    function updateFoliosTotal() {
        var folios1 = parseInt(document.getElementById('folios1').value) || 0;
        var folios2 = parseInt(document.getElementById('folios2').value) || 0;
        var folios3 = parseInt(document.getElementById('folios3').value) || 0;

        var totalFolios = folios1 + folios2 + folios3;

        document.getElementById('folios').value = totalFolios;
        document.getElementById('totalFoliosDisplay').innerHTML = "<strong>" + totalFolios + "</strong>";
    }
// Reemplaza tu función submitForm() existente con esta
async function submitForm() {
    updateFoliosTotal(); // Mantenemos esta línea

    const numOficioInput = document.getElementById('num_oficio');
    const numOficio = (numOficioInput.value || '').trim();

    // VALIDACIÓN 1: Formato del oficio (la que ya tenías)
    const tieneNumeroAlFinal = /\/\s*\d+\s*$/.test(numOficio);
    if (!tieneNumeroAlFinal) {
        alert('Debe ingresar un número válido después del "/" en el campo Número de Oficio. Ej: TRD-DEP001/123');
        numOficioInput.focus();
        return;
    }

    // VALIDACIÓN 2: Verificar si el oficio ya existe en la BD
    const departamentoId = "<?php echo urlencode($departamento_id); ?>";
    const anioSemestre = "<?php echo urlencode($anio_semestre); ?>";
    const checkUrl = `verificar_oficio_depto.php?oficio=${encodeURIComponent(numOficio)}&anio_semestre=${anioSemestre}&departamento_id=${departamentoId}`;

    try {
        const response = await fetch(checkUrl);
        const data = await response.json();

        // Si el oficio ya existe, mostrar alerta y detener
        if (data.existe) {
            alert('Error: El número de oficio ya ha sido utilizado en este período para este departamento. Por favor, ingrese un número diferente.');
            return;
        }

        // Si todas las validaciones pasan, procedemos a construir la URL y redirigir
        const fechaOficio = document.getElementById('fecha_oficio').value;
        const elaboro = document.getElementById('elaboro').value;
        const acta = document.getElementById('acta').value;
        const fechaActa = document.getElementById('fecha_acta').value;
        const folios = document.getElementById('folios').value;
        const nombrefac = "<?php echo urlencode($nombre_fac); ?>";

        // Construimos la URL para el script que genera el Word
        const url = 'oficio_depto_novedad.php?folios=' + folios +
            '&departamento_id=' + departamentoId +
            '&anio_semestre=' + anioSemestre +
            '&nombre_fac=' + nombrefac +
            '&num_oficio=' + encodeURIComponent(numOficio) +
            '&fecha_oficio=' + encodeURIComponent(fechaOficio) +
            '&elaboro=' + encodeURIComponent(elaboro) +
            '&acta=' + encodeURIComponent(acta) +
            '&fecha_acta=' + encodeURIComponent(fechaActa);
        
        // Redirigimos a la página para generar el Word
        window.location.href = url;

        // Opcional: recargar la página después de un tiempo
        setTimeout(function() {
            window.location.reload();
        }, 1000);

    } catch (error) {
        console.error('Error al verificar el oficio:', error);
        alert('No se pudo verificar el número de oficio. Intente de nuevo.');
    }
}
</script>
            
<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>
    </div>    
            

<script>
    document.getElementById('confirmForm').addEventListener('submit', function() {
        setTimeout(function() {
            location.reload();
        }, 3000); // Espera 3 segundos antes de recargar la página
    });
</script>
    <script>
    // Script para cerrar el modal automáticamente después de enviar el formulario
    $('#oficioForm').on('submit', function() {
        $('#oficioModal').modal('hide');  // Cerrar el modal
    });

    $('#oficioFacultadForm').on('submit', function() {
        $('#oficioModal').modal('hide');  // Cerrar el modal
    });
</script>
  </div>

<script>
    $(document).ready(function() {
        // Cuando el modal de confirmación se muestra
        $('#confirmDeleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botón que activó el modal

            // Extraer información de los atributos data-* del botón
            var id_solicitud = button.data('id_solicitud');
            var facultad_id = button.data('facultad_id');
            var departamento_id = button.data('departamento_id');
            var anio_semestre = button.data('anio_semestre');
            var tipo_docente = button.data('tipo_docente');
            var nombre_docente = button.data('nombre_docente');

            // Actualizar los campos ocultos del formulario dentro del modal
            var modal = $(this);
            modal.find('#modal_id_solicitud_del').val(id_solicitud);
            modal.find('#modal_facultad_id_del').val(facultad_id);
            modal.find('#modal_departamento_id_del').val(departamento_id);
            modal.find('#modal_anio_semestre_del').val(anio_semestre);
            modal.find('#modal_tipo_docente_del').val(tipo_docente);
            modal.find('#docenteNombre').text(nombre_docente); // Mostrar el nombre del docente en el párrafo
            modal.find('#motivoEliminacion').val(''); // Limpiar el campo del motivo cada vez que se abre
        });
    });
</script>
        </div></div></div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const errorMessage = urlParams.get('error');
    const successMessage = urlParams.get('mensaje');

    if (errorMessage) {
        alert(decodeURIComponent(errorMessage));
        // Opcional: Eliminar el parámetro de la URL para que no aparezca al recargar
        urlParams.delete('error');
        history.replaceState(null, '', '?' + urlParams.toString());
    }
    if (successMessage) {
        alert(decodeURIComponent(successMessage));
        // Opcional: Eliminar el parámetro de la URL para que no aparezca al recargar
        urlParams.delete('mensaje');
        history.replaceState(null, '', '?' + urlParams.toString());
    }
});
        // Nueva función para confirmar el deshacer novedad
    function confirmDeshacer(tipoNovedad, formId) {
        // Encuentra el formulario por su ID
        const form = document.getElementById(formId);
        if (!form) {
            console.error('Formulario no encontrado con ID:', formId);
            return;
        }

        const confirmacion = confirm(`¿Está seguro que desea deshacer esta solicitud de novedad de ${tipoNovedad}?`);
        if (confirmacion) {
            form.submit(); // Si el usuario confirma, envía el formulario
        } else {
            // Si el usuario cancela, no hace nada
            alert('Acción de deshacer cancelada.');
        }
    }
</script>
    
    </body>
    
</html>
<?php       // Función para obtener el cierreo no de departamento
    function obtenerCierreDeptoCatedra($departamento_id,$aniose) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_periodo.dp_estado_catedra FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo ='$aniose'";
        $result = $conn->query($sql);   
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dp_estado_catedra'] == 'ce') ? "CERRADO" : "ABIERTO";
        } else {
            return "estado depto Desconocida";
        }
    } // ocasional
    function obtenerCierreDeptoOcasional($departamento_id,$aniose) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_periodo.dp_estado_ocasional FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo ='$aniose'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dp_estado_ocasional'] == 'ce') ? "CERRADO" : "ABIERTO";
        } else {
            return "estado depto Desconocida";
        }
    } 


?>
