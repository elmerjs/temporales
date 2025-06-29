<?php
require('include/headerz.php');
require 'funciones.php'; // Asegúrate de que este archivo contiene funciones como obtenerperiodo, etc.

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Validación de sesión
if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
              <a href='index.html'>inicie sesión</a>
          </span>";
    exit();
}

$nombre_sesion = $_SESSION['name'];

// Lógica para inicializar $anio_semestre actual
$anio_semestre = isset($_POST['anio_semestre'])
    ? $_POST['anio_semestre']
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

// Capture departamento_id if it's sent
$departamento_id_param = isset($_POST['departamento_id'])
    ? $_POST['departamento_id']
    : (isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null);


$anio_semestre_anterior = '2025-1';


$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_fac = $row['email_padre'];
    $pk_fac = $row['fk_fac_user'];
    $email_dp = $row['Email'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user= $row['fk_depto_user'];
    $where = "";


}


// Obtener lista de facultades (para el admin)
$facultades = [];
if ($tipo_usuario == 1) {
    $query_facultades = "SELECT PK_FAC, nombre_fac_minb FROM facultad ORDER BY nombre_fac_minb";
    $result_facultades = $conn->query($query_facultades);
    while ($row = $result_facultades->fetch_assoc()) {
        $facultades[$row['PK_FAC']] = $row['nombre_fac_minb'];
    }
}

// Lógica para inicializar $anio_semestre actual
$anio_semestre = isset($_POST['anio_semestre']) 
    ? $_POST['anio_semestre'] 
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

// Si es admin y se ha seleccionado una facultad
$facultad_seleccionada = null;
if ($tipo_usuario == 1 && isset($_GET['facultad_id'])) {
    $facultad_seleccionada = $_GET['facultad_id'];
    $pk_fac = $facultad_seleccionada; // Sobreescribimos para las consultas
}

if ($tipo_usuario == '1') {
    // No specific WHERE clause needed for tipo_usuario 1 (admin/full access)
    $where = "";
} elseif ($tipo_usuario == '2') {
    // For tipo_usuario 2, filter by faculty
    $where = " WHERE f.PK_FAC = '$pk_fac'";
} elseif ($tipo_usuario == '3') {

        // Fallback: if departamento_id wasn't passed, use the department linked to the user session
        $where = " WHERE d.PK_DEPTO = '$depto_user'";
    }
// Obtener el período anterior (lógica duplicada, se mantiene para consistencia con el original)
list($anio, $semestre) = explode('-', $anio_semestre);

if ($semestre == '1') {
    $anio_anterior = $anio - 1;
    $semestre_anterior = '2';
} else {
    $anio_anterior = $anio;
    $semestre_anterior = '1';
}

$anio_semestre_anterior = $anio_anterior . '-' . $semestre_anterior;

// Obtener los parámetros de la URL/POST
$facultad_id = $pk_fac ?? null;
$departamento_id = $depto_user;// Obtener el período anterior

$anio_semestre = $_GET['anio_semestre'];
$periodo_anterior = $anio_semestre_anterior?? null;
$origen = $_POST['origen'] ?? null;

// --- Verificación y obtención de datos del PERIODO ACTUAL ---
if (empty($anio_semestre)) {
    die("Error: El parámetro anio_semestre no fue proporcionado.");
}

$consultaper = "SELECT * FROM periodo WHERE nombre_periodo = ?";
$stmt_per = $conn->prepare($consultaper);
if (!$stmt_per) {
    die("Error al preparar la consulta de periodo actual: " . $conn->error);
}
$stmt_per->bind_param("s", $anio_semestre);
$stmt_per->execute();
$resultadoper = $stmt_per->get_result();

if ($resultadoper->num_rows === 0) {
    die("Error: No se encontraron datos para el periodo actual: " . htmlspecialchars($anio_semestre));
}

$rowper = $resultadoper->fetch_assoc();
$fecha_ini_cat = $rowper['inicio_sem'];
$fecha_fin_cat = $rowper['fin_sem'];
$fecha_ini_ocas = $rowper['inicio_sem_oc'];
$fecha_fin_ocas = $rowper['fin_sem_oc'];
$valor_punto = $rowper['valor_punto'];
$smlv = $rowper['smlv'];
$stmt_per->close();

// Calculo de días y semanas para PERIODO ACTUAL
$fecha_inicio_cat_dt = new DateTime($fecha_ini_cat);
$fecha_fin_cat_dt = new DateTime($fecha_fin_cat);
$dias_catedra = $fecha_inicio_cat_dt->diff($fecha_fin_cat_dt)->days - 1; // Tu lógica PHP
$semanas_catedra = ceil($dias_catedra / 7);

$inicio_ocas_dt = new DateTime($fecha_ini_ocas);
$fin_ocas_dt = new DateTime($fecha_fin_ocas);
$dias_ocasional = $inicio_ocas_dt->diff($fin_ocas_dt)->days - 2; // Tu lógica PHP
$semanas_ocasional = ceil($dias_ocasional / 7);
$meses_ocasional = intval($semanas_ocasional / 4.33) - 1; // Tu lógica PHP

// Constantes de porcentajes (hardcodeadas en tu PHP y SQL)
$porcentaje_arl = 0.522 / 100;
$porcentaje_caja = 4.0 / 100;
$porcentaje_icbf = 3.0 / 100;

// Ajustes finales de porcentaje (para el PERIODO ACTUAL - se mantienen en 0)
$ajuste_catedra = 0;
$ajuste_ocasional = 0;

// --- Verificación y obtención de datos del PERIODO ANTERIOR ---
$dias_catedra_ant = 0;
$semanas_catedra_ant = 0;
$dias_ocasional_ant = 0;
$semanas_ocasional_ant = 0;
$meses_ocasional_ant = 0;
$valor_punto_ant = 0;
$smlv_ant = 0;

if (!empty($periodo_anterior)) {
    $consultaperant = "SELECT * FROM periodo WHERE nombre_periodo = ?";
    $stmt_per_ant = $conn->prepare($consultaperant);
    if (!$stmt_per_ant) {
        die("Error al preparar la consulta de periodo anterior: " . $conn->error);
    }
    $stmt_per_ant->bind_param("s", $periodo_anterior);
    $stmt_per_ant->execute();
    $resultadoperant = $stmt_per_ant->get_result();

    if ($resultadoperant->num_rows > 0) {
        $rowperant = $resultadoperant->fetch_assoc();
        $fecha_ini_catant = $rowperant['inicio_sem'];
        $fecha_fin_catant = $rowperant['fin_sem'];
        $fecha_ini_ocasant = $rowperant['inicio_sem_oc'];
        $fecha_fin_ocasant = $rowperant['fin_sem_oc'];
        $valor_punto_ant = $rowperant['valor_punto'];
        $smlv_ant = $rowperant['smlv'];

        // Calculo de días y semanas para PERIODO ANTERIOR
        $fecha_inicio_cat_ant_dt = new DateTime($fecha_ini_catant);
        $fecha_fin_cat_ant_dt = new DateTime($fecha_fin_catant);
        $dias_catedra_ant = $fecha_inicio_cat_ant_dt->diff($fecha_fin_cat_ant_dt)->days - 1;
        $semanas_catedra_ant = ceil($dias_catedra_ant / 7);

        $inicio_ocas_ant_dt = new DateTime($fecha_ini_ocasant);
        $fin_ocas_ant_dt = new DateTime($fecha_fin_ocasant);
        $dias_ocasional_ant = $inicio_ocas_ant_dt->diff($fin_ocas_ant_dt)->days - 2;
        $semanas_ocasional_ant = ceil($dias_ocasional_ant / 7);
        $meses_ocasional_ant = intval($semanas_ocasional_ant / 4.33) - 1;
    }
    $stmt_per_ant->close();
}


// --- Consulta SQL Parametrizada ---
$sql_query = "
WITH ProfessorFinancials AS (
    SELECT
        s.cedula,
        s.facultad_id,
        s.departamento_id,
        s.tipo_docente,
        s.puntos,
        s.horas,
        s.horas_r,
        s.tipo_dedicacion,
        s.tipo_dedicacion_r,
        -- Parámetros dinámicos desde PHP
        ? AS valor_punto_dyn,
        ? AS smlv_dyn,
        ? AS dias_catedra_dyn,
        ? AS semanas_catedra_dyn,
        ? AS dias_ocasional_dyn,
        ? AS semanas_ocasional_dyn,
        ? AS meses_ocas_dyn,
        ? AS porcentaje_arl_dyn,
        ? AS porcentaje_caja_dyn,
        ? AS porcentaje_icbf_dyn,
        ? AS ajuste_catedra_dyn,
        ? AS ajuste_ocasional_dyn,

        -- Paso 1: Calcular Asignacion_Mensual y Asignacion_Total por profesor
        CASE
            WHEN s.tipo_docente = 'Catedra' THEN
                (s.puntos * ? * (COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0)) * 4)
            WHEN s.tipo_docente = 'Ocasional' THEN
                (s.puntos * ? * (
                    CASE
                        WHEN s.tipo_dedicacion = 'MT' OR s.tipo_dedicacion_r = 'MT' THEN 20
                        WHEN s.tipo_dedicacion = 'TC' OR s.tipo_dedicacion_r = 'TC' THEN 40
                        ELSE 0
                    END
                ) / 40)
            ELSE 0
        END AS asignacion_mes_calc,

        CASE
            WHEN s.tipo_docente = 'Catedra' THEN
                s.puntos * ? * (COALESCE(s.horas, 0) + COALESCE(s.horas_r, 0)) * ?
            WHEN s.tipo_docente = 'Ocasional' THEN
                ROUND(s.puntos * ? * (
                    CASE
                        WHEN s.tipo_dedicacion = 'MT' OR s.tipo_dedicacion_r = 'MT' THEN 20
                        WHEN s.tipo_dedicacion = 'TC' OR s.tipo_dedicacion_r = 'TC' THEN 40
                        ELSE 0
                    END
                ) / 40.0, 0) * (? / 30.0) -- Asignacion_mes * dias_ocasional / 30
            ELSE 0
        END AS asignacion_total_calc
    FROM
        solicitudes AS s
    WHERE
        s.anio_semestre = ?
        AND (s.estado <> 'an' OR s.estado IS NULL)
),
DetailedFinancials AS (
    SELECT
        pf.*,
        -- Paso 2: Calcular todos los componentes financieros detallados
        -- Prima Navidad
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN pf.asignacion_mes_calc * 3 / 12
            ELSE pf.asignacion_mes_calc * pf.meses_ocas_dyn / 12
        END AS prima_navidad_calc,

        -- Indemnización Vacaciones
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN pf.asignacion_mes_calc * pf.dias_catedra_dyn / 360
            ELSE pf.asignacion_mes_calc * pf.dias_ocasional_dyn / 360
        END AS indem_vacaciones_calc,

        -- Indemnización Prima Vacaciones
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN (pf.asignacion_mes_calc * pf.dias_catedra_dyn / 360) * 2 / 3
            ELSE (pf.asignacion_mes_calc * pf.dias_ocasional_dyn / 360) * 2 / 3
        END AS indem_prima_vacaciones_calc,

        -- Cesantías
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN (pf.asignacion_total_calc + (pf.asignacion_mes_calc * 3 / 12)) / 12
            ELSE ROUND((pf.asignacion_total_calc + (pf.asignacion_mes_calc * pf.meses_ocas_dyn / 12)) / 12)
        END AS cesantias_calc,

        -- EPS
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * 0.085
                        ELSE ROUND(pf.asignacion_total_calc * 0.085, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 8.5) / 100, 0)
            ELSE 0
        END AS eps_calc,

        -- Pensión (AFP)
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * 0.12
                        ELSE ROUND(pf.asignacion_total_calc * 0.12, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 12) / 100, 0)
            ELSE 0
        END AS afp_calc,

        -- ARL
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * pf.porcentaje_arl_dyn
                        ELSE ROUND(pf.asignacion_total_calc * pf.porcentaje_arl_dyn, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 0.522) / 100, -2)
            ELSE 0
        END AS arl_calc,

        -- Caja de Compensación (Comfaucaua)
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * pf.porcentaje_caja_dyn
                        ELSE ROUND(pf.asignacion_total_calc * pf.porcentaje_caja_dyn, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 4) / 100, -2)
            ELSE 0
        END AS cajacomp_calc,

        -- ICBF
        CASE
            WHEN pf.tipo_docente = 'Catedra' THEN
                ROUND(
                    CASE
                        WHEN pf.asignacion_mes_calc < pf.smlv_dyn THEN (pf.smlv_dyn * pf.dias_catedra_dyn / 30) * pf.porcentaje_icbf_dyn
                        ELSE ROUND(pf.asignacion_total_calc * pf.porcentaje_icbf_dyn, 0)
                    END
                , -2)
            WHEN pf.tipo_docente = 'Ocasional' THEN
                ROUND((pf.asignacion_total_calc * 3) / 100, -2)
            ELSE 0
        END AS icbf_calc
    FROM
        ProfessorFinancials pf
),
AggregatedTotals AS (
    SELECT
        f.nombre_fac_minb AS nombre_facultad,
        d.depto_nom_propio AS nombre_departamento,
        df.tipo_docente,
        COUNT(DISTINCT df.cedula) AS total_profesores,
        SUM(df.asignacion_mes_calc) AS total_asignacion_mensual_agregada,
        SUM(df.asignacion_total_calc) AS total_asignacion_total_agregada,
        SUM(
            CASE
                WHEN df.tipo_docente = 'Catedra' THEN
                    df.asignacion_total_calc + df.prima_navidad_calc + df.indem_vacaciones_calc + df.indem_prima_vacaciones_calc + df.cesantias_calc + df.eps_calc + df.afp_calc + df.arl_calc + df.cajacomp_calc + df.icbf_calc
                WHEN df.tipo_docente = 'Ocasional' THEN
                    (df.asignacion_total_calc + df.prima_navidad_calc + df.indem_vacaciones_calc + df.indem_prima_vacaciones_calc) + -- Total Empleado
                    (df.cesantias_calc + df.eps_calc + df.afp_calc + df.arl_calc + df.cajacomp_calc + df.icbf_calc) -- Total Entidades
                ELSE 0
            END
        ) AS gran_total_sin_ajuste,
        df.ajuste_catedra_dyn,
        df.ajuste_ocasional_dyn
    FROM
        DetailedFinancials df
    JOIN
        deparmanentos AS d ON d.PK_DEPTO = df.departamento_id
    JOIN
        facultad AS f ON f.PK_FAC = df.facultad_id
    WHERE
        ( ? IS NULL OR df.facultad_id = ? )
        AND ( ? IS NULL OR df.departamento_id = ? )
    GROUP BY
        f.nombre_fac_minb,
        d.depto_nom_propio,
        df.tipo_docente,
        df.ajuste_catedra_dyn,
        df.ajuste_ocasional_dyn
)
SELECT
    ata.nombre_facultad,
    ata.nombre_departamento,
    ata.tipo_docente,
    ata.total_profesores,
    ata.total_asignacion_mensual_agregada,
    ata.total_asignacion_total_agregada,
    -- Aplicar el ajuste final al gran_total_sin_ajuste con los porcentajes corregidos
    CASE
        WHEN ata.tipo_docente = 'Catedra' THEN ata.gran_total_sin_ajuste * (1 + ata.ajuste_catedra_dyn)
        WHEN ata.tipo_docente = 'Ocasional' THEN ata.gran_total_sin_ajuste * (1 + ata.ajuste_ocasional_dyn)
        ELSE ata.gran_total_sin_ajuste
    END AS gran_total_ajustado
FROM
    AggregatedTotals ata
ORDER BY
    ata.nombre_facultad,
    ata.nombre_departamento,
    ata.tipo_docente;
";

// --- Preparar y ejecutar la consulta para el PERIODO ACTUAL ---
$stmt_current = $conn->prepare($sql_query);
if (!$stmt_current) {
    die("Error al preparar la consulta para el periodo actual: " . $conn->error);
}

$bind_params_current = [
    // Parámetros para las constantes dinámicas del periodo actual
    $valor_punto, $smlv, $dias_catedra, $semanas_catedra, $dias_ocasional,
    $semanas_ocasional, $meses_ocasional, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
    $ajuste_catedra, $ajuste_ocasional, // Estos son 0 para el periodo actual
    // Parámetros para asignacion_mes_calc
    $valor_punto, $valor_punto,
    // Parámetros para asignacion_total_calc
    $valor_punto, $semanas_catedra, $valor_punto, $dias_ocasional,
    // Periodo anio_semestre
    $anio_semestre,
    // Filtros de WHERE para AggregatedTotals (facultad y departamento)
    $facultad_id, $facultad_id, $departamento_id, $departamento_id
];

// String de tipos para bind_param (d = double/float, i = int, s = string)
// 18 'd's + 1 's' + 4 'i's = 23 parámetros en total
$types_current = "dddddddddddddddddssiiii";
// Asegúrate de que el número de '?' en el SQL coincide con el número de parámetros y el string de tipos

$stmt_current->bind_param($types_current, ...$bind_params_current);
$stmt_current->execute();
$result_current_period = $stmt_current->get_result();

$data_current_period = [];
while ($row = $result_current_period->fetch_assoc()) {
    $data_current_period[] = $row;
}
$stmt_current->close();


// --- Preparar y ejecutar la consulta para el PERIODO ANTERIOR (si existe) ---
$data_previous_period = [];
if (!empty($periodo_anterior) && $valor_punto_ant > 0) { // Asegúrate de tener datos del periodo anterior
    $stmt_previous = $conn->prepare($sql_query); // Reutilizamos la misma consulta SQL
    if (!$stmt_previous) {
        die("Error al preparar la consulta para el periodo anterior: " . $conn->error);
    }

    // Definir ajustes específicos para el periodo anterior
    $ajuste_catedra_anterior = 0; // Mantener en 0 si no hay ajuste para Catedra en el periodo anterior
    $ajuste_ocasional_anterior = 0.018; // Aplicar el 1.7% para Ocasional en el periodo anterior (was -0.01732 in original, updated to 0.017 as per your provided snippet)

    $bind_params_previous = [
        // Parámetros para las constantes dinámicas del periodo anterior
        $valor_punto_ant, $smlv_ant, $dias_catedra_ant, $semanas_catedra_ant, $dias_ocasional_ant,
        $semanas_ocasional_ant, $meses_ocasional_ant, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
        $ajuste_catedra_anterior, $ajuste_ocasional_anterior, // <--- Aquí se pasan los ajustes específicos
        // Parámetros para asignacion_mes_calc
        $valor_punto_ant, $valor_punto_ant,
        // Parámetros para asignacion_total_calc
        $valor_punto_ant, $semanas_catedra_ant, $valor_punto_ant, $dias_ocasional_ant,
        // Periodo anio_semestre (¡este es el del periodo anterior!)
        $periodo_anterior,
        // Filtros de WHERE para AggregatedTotals (facultad y departamento)
        $facultad_id, $facultad_id, $departamento_id, $departamento_id
    ];

    // Los tipos de parámetros son los mismos que para la consulta actual
    $stmt_previous->bind_param($types_current, ...$bind_params_previous);
    $stmt_previous->execute();
    $result_previous_period = $stmt_previous->get_result();

    while ($row = $result_previous_period->fetch_assoc()) {
        $data_previous_period[] = $row;
    }
    $stmt_previous->close();

}
// --- CSS profesional estilo Unicauca ---

// Include Google Fonts
echo "<link href='https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap' rel='stylesheet'>";

// Custom Styles for the headers
echo "<style>
    .card-header-custom { /* Using a custom class to avoid conflict with existing Bootstrap if any */
        border-bottom: none;
        padding: 1rem 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
        font-family: 'Open Sans', sans-serif;
    }
    .card-header-custom h2, .card-header-custom h3, .card-header-custom h5, .card-header-custom h6 {
        color: white;
        margin-bottom: 0;
    }
    .bg-unicauca-blue-dark {
        background-color: #004d60 !important; /* A professional dark blue */
    }
</style>";

echo '<style>
    /* Estilos generales */
    body {
        font-family: "Segoe UI", "Roboto", sans-serif;
        background-color: #f8fafc;
        color: #333;
        margin: 0;
        padding: 15px;
        font-size: 14px;
    }
    
   
    .unicauca-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
        font-family: "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif;
    }
    
    /* Encabezado premium */
 
    h2 {
        color: white;
        font-size: 1.8rem;
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    h3 {
        color: #0056b3;
        font-size: 1.4rem;
        margin: 25px 0 15px;
    }

    /* Tablas compactas profesionales */
    .table-container {
        overflow-x: auto;
        margin-bottom: 25px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .compact-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }
    
    .compact-table th {
        background-color: #004d99;
        color: white;
        font-weight: 600;
        padding: 8px 10px;
        text-align: left;
        position: sticky;
        top: 0;
        font-size: 13px;
    }
    
    .compact-table td {
        padding: 6px 10px;
        border-bottom: 1px solid #eaeaea;
        font-size: 13px;
        vertical-align: top;
    }
    
    .compact-table tr:nth-child(even) {
        background-color: #f8f9fa;
    }
    
    .compact-table tr:hover {
        background-color: #e9f0f7;
    }

    /* Secciones de gráficos */
    .chart-section {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }
    
    .chart-box {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        height: 350px;
    }
    
    .chart-title {
        text-align: center;
        font-weight: 600;
        color: #004d99;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }

    /* Period boxes */
    .period-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .period-box {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    
    .period-header {
        background-color: #e6f0ff;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-weight: 600;
        color: #004d99;
        border-left: 4px solid #004d99;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    .info-item {
        padding: 8px;
        background: #f8fafc;
        border-radius: 4px;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
        display: block;
        margin-bottom: 3px;
        font-size: 12px;
    }
    
    .currency {
        font-weight: 600;
        color: #006400;
    }
    
    .no-data {
        text-align: center;
        padding: 30px;
        color: #777;
        font-style: italic;
        background-color: #f8f9fa;
        border-radius: 6px;
        margin-top: 15px;
    }
</style>';
echo "<style>
    /* [Mantener todos los estilos CSS existentes] */
    .selector-facultad {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .selector-facultad select {
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
        font-size: 16px;
        min-width: 300px;
    }
    .selector-facultad button {
        padding: 10px 20px;
        background: #004d60;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
    }
    .selector-facultad button:hover {
        background: #003d50;
    }
</style>";

echo "<div class='unicauca-container'>";

// Mostrar selector de facultad para admin si no ha seleccionado una
if ($tipo_usuario == 1 && !$facultad_seleccionada) {
    echo "<div class='selector-facultad'>";
    echo "<h3>Seleccione una Facultad</h3>";
    echo "<form method='get' action=''>";
    echo "<input type='hidden' name='anio_semestre' value='$anio_semestre'>";
    echo "<select name='facultad_id'>";
    foreach ($facultades as $id => $nombre) {
        echo "<option value='$id'>$nombre</option>";
    }
    echo "</select>";
    echo "<button type='submit'>Ver Reporte</button>";
    echo "</form>";
    echo "</div>";
    
    // Mostrar siempre la comparativa general para admin
    echo "</div>"; // cierre unicauca-container
    //exit();
}

// Si es tipo_usuario = 2 o admin ha seleccionado facultad, mostrar el reporte normal
echo "<div class='card-header-custom bg-unicauca-blue-dark text-white d-flex justify-content-between align-items-center' style='margin-top: 30px;'>";
echo "<h2 class='mb-0'>Reporte de Facultad: " . htmlspecialchars($facultades[$pk_fac] ?? '') . "</h2>";
echo "</div>";

// --- Generar gráficas comparativas ---
echo "<h2 style='text-align: center; margin-top: 30px;'>Comparativa Gráfica por Facultad</h2>";

// Combinar datos de ambos periodos para las gráficas
$facultades_data = [];

// Procesar datos del periodo actual
foreach ($data_current_period as $row) {
    $facultad = $row['nombre_facultad'];
    $departamento = $row['nombre_departamento'];
    $tipo = $row['tipo_docente']; // This variable is not used in the provided snippet but kept for context

    if (!isset($facultades_data[$facultad])) {
        $facultades_data[$facultad] = [
            'departamentos' => [],
            'total_profesores_actual' => 0,
            'gran_total_ajustado_actual' => 0,
            'total_profesores_anterior' => 0,
            'gran_total_ajustado_anterior' => 0
        ];
    }

    if (!isset($facultades_data[$facultad]['departamentos'][$departamento])) {
        $facultades_data[$facultad]['departamentos'][$departamento] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'total_actual' => 0,
            'total_anterior' => 0
        ];
    }

    $facultades_data[$facultad]['departamentos'][$departamento]['profesores_actual'] += $row['total_profesores'];
    $facultades_data[$facultad]['departamentos'][$departamento]['total_actual'] += $row['gran_total_ajustado'];

    $facultades_data[$facultad]['total_profesores_actual'] += $row['total_profesores'];
    $facultades_data[$facultad]['gran_total_ajustado_actual'] += $row['gran_total_ajustado'];
}

// Procesar datos del periodo anterior
foreach ($data_previous_period as $row) {
    $facultad = $row['nombre_facultad'];
    $departamento = $row['nombre_departamento'];

    if (isset($facultades_data[$facultad]) && isset($facultades_data[$facultad]['departamentos'][$departamento])) {
        $facultades_data[$facultad]['departamentos'][$departamento]['profesores_anterior'] += $row['total_profesores'];
        $facultades_data[$facultad]['departamentos'][$departamento]['total_anterior'] += $row['gran_total_ajustado'];

        $facultades_data[$facultad]['total_profesores_anterior'] += $row['total_profesores'];
        $facultades_data[$facultad]['gran_total_ajustado_anterior'] += $row['gran_total_ajustado'];
    }
}

// Incluir Chart.js y el plugin de Datalabels
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
echo '<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>'; // Datalabels Plugin

// Generar gráficas para cada facultad
foreach ($facultades_data as $facultad => $data) {
    echo "<div style='margin: 40px 0; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>";
 

    // Preparar y ORDENAR datos para gráficas de departamentos
    $departamentos_prof_chart = []; // For professors chart
    $profesores_actual_prof_chart = [];
    $profesores_anterior_prof_chart = [];

    $departamentos_total_chart = []; // For total value chart
    $total_actual_total_chart = [];
    $total_anterior_total_chart = [];
    
    // Create temporary arrays for sorting
    $temp_prof_data = [];
    $temp_total_data = [];

    foreach ($data['departamentos'] as $depto_name => $depto_data) {
        $temp_prof_data[] = [
            'name' => $depto_name,
            'actual' => $depto_data['profesores_actual'],
            'anterior' => $depto_data['profesores_anterior']
        ];
        $temp_total_data[] = [
            'name' => $depto_name,
            'actual' => $depto_data['total_actual'],
            'anterior' => $depto_data['total_anterior']
        ];
    }

    // Sort professor data by 'actual' count in descending order
    usort($temp_prof_data, function($a, $b) {
        return $b['actual'] <=> $a['actual'];
    });

    // Populate sorted arrays for professor chart
    foreach ($temp_prof_data as $item) {
        $departamentos_prof_chart[] = $item['name'];
        $profesores_actual_prof_chart[] = $item['actual'];
        $profesores_anterior_prof_chart[] = $item['anterior'];
    }

    // Sort total value data by 'actual' total in descending order
    usort($temp_total_data, function($a, $b) {
        return $b['actual'] <=> $a['actual'];
    });

    // Populate sorted arrays for total value chart
    foreach ($temp_total_data as $item) {
        $departamentos_total_chart[] = $item['name'];
        $total_actual_total_chart[] = $item['actual'];
        $total_anterior_total_chart[] = $item['anterior'];
    }
    
    // Gráfica de Profesores (Horizontal Bar)
    echo "<div style='display: flex;'>";
    echo "<div style='width: 50%; padding: 15px;'>";
    echo "<h4 style='text-align: center;'>Cantidad de Profesores por Departamento</h4>";
    echo "<canvas id='chartProfesores_" . str_replace(' ', '_', $facultad) . "' height='300'></canvas>";
    echo "</div>";

    // Gráfica de Totales Proyectados (Horizontal Bar)
    echo "<div style='width: 50%; padding: 15px;'>";
    echo "<h4 style='text-align: center;'>Valor Proyectado por Departamento</h4>";
    echo "<canvas id='chartTotal_" . str_replace(' ', '_', $facultad) . "' height='300'></canvas>";
    echo "</div>";
    echo "</div>";

    // Script para gráficas
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Register the datalabels plugin
        Chart.register(ChartDataLabels);

        // Gráfica de Profesores (Horizontal Bar)
        const ctxProfesores = document.getElementById('chartProfesores_" . str_replace(' ', '_', $facultad) . "').getContext('2d');
        new Chart(ctxProfesores, {
            type: 'bar',
            data: {
                labels: " . json_encode($departamentos_prof_chart) . ",
                datasets: [
                    {
                        label: 'Periodo Actual (" . htmlspecialchars($anio_semestre) . ")',
                        data: " . json_encode($profesores_actual_prof_chart) . ",
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Periodo Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                        data: " . json_encode($profesores_anterior_prof_chart) . ",
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Profesores'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Departamentos'
                        }
                    }
                },
                plugins: {
                    datalabels: {
                        display: true,
                        color: '#333',
                        anchor: 'end', // Position the label at the end of the bar
                        align: 'end',  // Align it to the end of the bar (outside)
                        offset: 4,     // Small offset from the bar
                        formatter: function(value, context) {
                            return value.toLocaleString(); // Format number with local thousands separator
                        }
                    },
                    tooltip: { // Ensure tooltips are still enabled
                        enabled: true
                    }
                }
            }
        });

        // Gráfica de Totales Proyectados (Horizontal Bar)
        const ctxTotal = document.getElementById('chartTotal_" . str_replace(' ', '_', $facultad) . "').getContext('2d');
        new Chart(ctxTotal, {
            type: 'bar',
            data: {
                labels: " . json_encode($departamentos_total_chart) . ",
                datasets: [
                    {
                        label: 'Periodo Actual (" . htmlspecialchars($anio_semestre) . ")',
                        data: " . json_encode($total_actual_total_chart) . ",
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Periodo Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                        data: " . json_encode($total_anterior_total_chart) . ",
                        backgroundColor: 'rgba(153, 102, 255, 0.7)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Valor Proyectado'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Departamentos'
                        }
                    }
                },
                plugins: {
                    datalabels: {
                        display: true,
                        color: '#333',
                        anchor: 'end', // Position the label at the end of the bar
                        align: 'end',  // Align it to the end of the bar (outside)
                        offset: 4,     // Small offset from the bar
                        formatter: function(value, context) {
                            return '$' + value.toLocaleString(); // Format as currency
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Valor: $' + context.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
    </script>";

    echo "</div>"; // Cierre del contenedor de facultad
}

// Gráfica comparativa de totales por facultad (these remain vertical)
echo "<div style='margin: 40px 0; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>";
echo "<h3 style='text-align: center;'>Comparativa General por Facultad</h3>";

$facultades = array_keys($facultades_data);
$totales_actual = [];
$totales_anterior = [];
$profesores_actual_total = [];
$profesores_anterior_total = [];

foreach ($facultades_data as $facultad => $data) {
    $totales_actual[] = $data['gran_total_ajustado_actual'];
    $totales_anterior[] = $data['gran_total_ajustado_anterior'];
    $profesores_actual_total[] = $data['total_profesores_actual'];
    $profesores_anterior_total[] = $data['total_profesores_anterior'];
}

echo "<div style='display: flex;'>";
echo "<div style='width: 50%; padding: 15px;'>";
echo "<h4 style='text-align: center;'>Total de Profesores por Facultad</h4>";
echo "<canvas id='chartTotalProfesoresFac' height='400'></canvas>";
echo "</div>";

echo "<div style='width: 50%; padding: 15px;'>";
echo "<h4 style='text-align: center;'>Valor Proyectado por Facultad</h4>";
echo "<canvas id='chartTotalValorFac' height='400'></canvas>";
echo "</div>";
echo "</div>";

echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make sure ChartDataLabels is registered for these global charts too
    Chart.register(ChartDataLabels);

    // Gráfica de Profesores por Facultad (Vertical Bar)
    const ctxTotalProfFac = document.getElementById('chartTotalProfesoresFac').getContext('2d');
    new Chart(ctxTotalProfFac, {
        type: 'bar',
        data: {
            labels: " . json_encode($facultades) . ",
            datasets: [
                {
                    label: 'Periodo Actual (" . htmlspecialchars($anio_semestre) . ")',
                    data: " . json_encode($profesores_actual_total) . ",
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Periodo Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                    data: " . json_encode($profesores_anterior_total) . ",
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Profesores'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Facultades'
                    }
                }
            },
            plugins: {
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end', // Position the label at the top of the bar
                    align: 'center', // Center it horizontally
                    offset: 4,     // Small offset from the bar
                    formatter: function(value, context) {
                        return value.toLocaleString();
                    }
                },
                tooltip: {
                    enabled: true
                }
            }
        }
    });
    
    // Gráfica de Valor Proyectado por Facultad (Vertical Bar)
    const ctxTotalValFac = document.getElementById('chartTotalValorFac').getContext('2d');
    new Chart(ctxTotalValFac, {
        type: 'bar',
        data: {
            labels: " . json_encode($facultades) . ",
            datasets: [
                {
                    label: 'Periodo Actual (" . htmlspecialchars($anio_semestre) . ")',
                    data: " . json_encode($totales_actual) . ",
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Periodo Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                    data: " . json_encode($totales_anterior) . ",
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor Proyectado'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Facultades'
                    }
                }
            },
            plugins: {
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end', // Position the label at the top of the bar
                    align: 'center', // Center it horizontally
                    offset: 4,     // Small offset from the bar
                    formatter: function(value, context) {
                        return '$' + value.toLocaleString(); // Format as currency
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Valor: $' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>";

echo "</div>"; // Cierre del contenedor de comparativa general

echo "<div style='margin: 40px 0;'>";
echo "<h3 style='text-align: center; margin-bottom: 30px; color: #2c3e50; font-weight: 600; font-size: 1.8rem;'>Resumen Comparativo por Facultad</h3>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 25px;'>";

foreach ($facultades_data as $facultad => $data) {
    // Calcular diferencias para profesores
    $prof_actual = $data['total_profesores_actual'];
    $prof_anterior = $data['total_profesores_anterior'];
    $diff_prof = $prof_actual - $prof_anterior;
    $porc_prof = ($prof_anterior != 0) ? (abs($diff_prof) / $prof_anterior * 100) : 0;
    $color_prof = ($diff_prof >= 0) ? '#e74c3c' : '#27ae60';
    $icon_prof = ($diff_prof >= 0) ? '▲' : '▼';

    // Calcular diferencias para valor proyectado
    $valor_actual = $data['gran_total_ajustado_actual'];
    $valor_anterior = $data['gran_total_ajustado_anterior'];
    $diff_valor = $valor_actual - $valor_anterior;
    $porc_valor = ($valor_anterior != 0) ? (abs($diff_valor) / $valor_anterior * 100) : 0;
    $color_valor = ($diff_valor >= 0) ? '#e74c3c' : '#27ae60';
    $icon_valor = ($diff_valor >= 0) ? '▲' : '▼';

    // Formatear valores monetarios
    $formatted_valor_actual = number_format($valor_actual, 0, ',', '.');
    $formatted_valor_anterior = number_format($valor_anterior, 0, ',', '.');
    $formatted_diff_valor = number_format(abs($diff_valor), 0, ',', '.');

    // Tarjeta de Profesores - Estilo mejorado
    echo "<div class='card' style='
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid #3498db;
    '>";
    
    echo "<div style='padding: 25px;'>";
    echo "<h4 style='margin-top: 0; margin-bottom: 20px; color: #2c3e50; font-size: 1.2rem; border-bottom: 1px solid #f1f2f6; padding-bottom: 12px;'>
            <span style='font-weight: 600;'>$facultad</span> - Profesores
          </h4>";
    
    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 15px;'>";
    echo "<div>";
    echo "<div style='font-size: 0.9rem; color: #7f8c8d; margin-bottom: 4px;'>Actual ($anio_semestre)</div>";
    echo "<div style='font-weight: 700; font-size: 1.5rem; color: #2c3e50;'>$prof_actual</div>";
    echo "</div>";
    
    echo "<div style='text-align: right;'>";
    echo "<div style='font-size: 0.9rem; color: #7f8c8d; margin-bottom: 4px;'>Anterior ($periodo_anterior)</div>";
    echo "<div style='font-size: 1.1rem;'>$prof_anterior</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='
        background: linear-gradient(to right, {$color_prof}10, {$color_prof}08);
        padding: 14px;
        border-radius: 8px;
        border-left: 3px solid $color_prof;
        display: flex;
        justify-content: space-between;
        align-items: center;
    '>";
    echo "<span style='font-weight: 500; color: #34495e;'>Diferencia</span>";
    echo "<div style='display: flex; align-items: center; gap: 8px;'>";
    echo "<span style='color: $color_prof; font-weight: 700; font-size: 1.1rem;'>";
    echo $icon_prof . " " . ($diff_prof >= 0 ? "+$diff_prof" : $diff_prof);
    echo "</span>";
    echo "<span style='background: {$color_prof}15; color: $color_prof; padding: 4px 10px; border-radius: 12px; font-size: 0.9rem; font-weight: 600;'>";
    echo number_format($porc_prof, 2) . "%";
    echo "</span>";
    echo "</div>";
    echo "</div>";
    echo "</div></div>";

    // Tarjeta de Valor Proyectado - Estilo mejorado
    echo "<div class='card' style='
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 4px solid #9b59b6;
    '>";
    
    echo "<div style='padding: 25px;'>";
    echo "<h4 style='margin-top: 0; margin-bottom: 20px; color: #2c3e50; font-size: 1.2rem; border-bottom: 1px solid #f1f2f6; padding-bottom: 12px;'>
            <span style='font-weight: 600;'>$facultad</span> - Valor Proyectado
          </h4>";
    
    echo "<div style='display: flex; justify-content: space-between; margin-bottom: 15px;'>";
    echo "<div>";
    echo "<div style='font-size: 0.9rem; color: #7f8c8d; margin-bottom: 4px;'>Actual ($anio_semestre)</div>";
    echo "<div style='font-weight: 700; font-size: 1.5rem; color: #2c3e50;'>$" . $formatted_valor_actual . "</div>";
    echo "</div>";
    
    echo "<div style='text-align: right;'>";
    echo "<div style='font-size: 0.9rem; color: #7f8c8d; margin-bottom: 4px;'>Anterior ($periodo_anterior)</div>";
    echo "<div style='font-size: 1.1rem;'>$" . $formatted_valor_anterior . "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='
        background: linear-gradient(to right, {$color_valor}10, {$color_valor}08);
        padding: 14px;
        border-radius: 8px;
        border-left: 3px solid $color_valor;
        display: flex;
        justify-content: space-between;
        align-items: center;
    '>";
    echo "<span style='font-weight: 500; color: #34495e;'>Diferencia</span>";
    echo "<div style='display: flex; align-items: center; gap: 8px;'>";
    echo "<span style='color: $color_valor; font-weight: 700; font-size: 1.1rem;'>";
    echo $icon_valor . " " . ($diff_valor >= 0 ? "+$" : "-$") . $formatted_diff_valor;
    echo "</span>";
    echo "<span style='background: {$color_valor}15; color: $color_valor; padding: 4px 10px; border-radius: 12px; font-size: 0.9rem; font-weight: 600;'>";
    echo number_format($porc_valor, 2) . "%";
    echo "</span>";
    echo "</div>";
    echo "</div>";
    echo "</div></div>";
}

echo "</div>"; // cierre del grid
echo "</div>"; // cierre del contenedor
// --- Procesar y mostrar los resultados ---
// Aquí puedes empezar a generar tu HTML con la comparación

// --- Tablas compactas profesionales ---
echo "<h2>Detalle de Costos de Contratación por Periodo</h2>";
echo "<div class='period-container'>";

// Periodo Actual
echo "<div class='period-box'>";
echo "<div class='period-header'>Periodo Actual: " . htmlspecialchars($anio_semestre) . "</div>";
echo "<div class='info-grid'>";
echo "<div class='info-item'><span class='info-label'>Valor Punto:</span> <span class='currency'>$" . number_format($valor_punto, 0, ',', '.') . "</span></div>";
echo "<div class='info-item'><span class='info-label'>SMMLV:</span> <span class='currency'>$" . number_format($smlv, 0, ',', '.') . "</span></div>";
echo "<div class='info-item'><span class='info-label'>Días Cátedra:</span> " . $dias_catedra . "</div>";
echo "<div class='info-item'><span class='info-label'>Semanas:</span> " . $semanas_catedra . "</div>";
echo "<div class='info-item'><span class='info-label'>Días Ocasional:</span> " . $dias_ocasional . "</div>";
echo "<div class='info-item'><span class='info-label'>Meses:</span> " . $meses_ocasional . "</div>";
echo "</div>"; // cierre info-grid

if (!empty($data_current_period)) {
    echo "<div class='table-container'>";
    echo "<table class='compact-table'>";
    echo "<thead><tr>
            <th>Facultad</th>
            <th>Departamento</th>
            <th>Tipo</th>
            <th>Profesores</th>
            <th>Total Proyectado</th>
          </tr></thead>";
    echo "<tbody>";
    foreach ($data_current_period as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nombre_facultad']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre_departamento']) . "</td>";
        echo "<td>" . htmlspecialchars($row['tipo_docente']) . "</td>";
        echo "<td>" . htmlspecialchars($row['total_profesores']) . "</td>";
        echo "<td class='currency'>$" . number_format($row['gran_total_ajustado'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>"; // cierre table-container
} else {
    echo "<div class='no-data'>No hay datos disponibles para el periodo actual</div>";
}
echo "</div>"; // cierre period-box

// Periodo Anterior
echo "<div class='period-box'>";
echo "<div class='period-header'>Periodo Anterior: " . htmlspecialchars($periodo_anterior ?? 'N/A') . "</div>";

if ($valor_punto_ant > 0) {
    echo "<div class='info-grid'>";
    echo "<div class='info-item'><span class='info-label'>Valor Punto:</span> <span class='currency'>$" . number_format($valor_punto_ant, 0, ',', '.') . "</span></div>";
    echo "<div class='info-item'><span class='info-label'>SMMLV:</span> <span class='currency'>$" . number_format($smlv_ant, 0, ',', '.') . "</span></div>";
    echo "<div class='info-item'><span class='info-label'>Días Cátedra:</span> " . $dias_catedra_ant . "</div>";
    echo "<div class='info-item'><span class='info-label'>Semanas:</span> " . $semanas_catedra_ant . "</div>";
    echo "<div class='info-item'><span class='info-label'>Días Ocasional:</span> " . $dias_ocasional_ant . "</div>";
    echo "<div class='info-item'><span class='info-label'>Meses:</span> " . $meses_ocasional_ant . "</div>";
    echo "</div>"; // cierre info-grid

    if (!empty($data_previous_period)) {
        echo "<div class='table-container'>";
        echo "<table class='compact-table'>";
        echo "<thead><tr>
                <th>Facultad</th>
                <th>Departamento</th>
                <th>Tipo</th>
                <th>Profesores</th>
                <th>Total Proyectado</th>
              </tr></thead>";
        echo "<tbody>";
        foreach ($data_previous_period as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['nombre_facultad']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_departamento']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tipo_docente']) . "</td>";
            echo "<td>" . htmlspecialchars($row['total_profesores']) . "</td>";
            echo "<td class='currency'>$" . number_format($row['gran_total_ajustado'], 0, ',', '.') . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>"; // cierre table-container
    } else {
        echo "<div class='no-data'>No hay datos disponibles para el periodo anterior</div>";
    }
} else {
    echo "<div class='no-data'>No se encontraron datos para el periodo anterior</div>";
}
echo "</div>"; // cierre period-box
echo "</div>"; // cierre period-container

echo "</div>"; // cierre unicauca-container


// Siempre mostrar la comparativa general al final

echo "</div>"; // cierre unicauca-container
?>