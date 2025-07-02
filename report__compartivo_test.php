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

// Validar y capturar el anio_semestre actual
if (isset($_POST['anio_semestre'])) {
    $anio_semestre = $_POST['anio_semestre'];
} elseif (isset($_GET['anio_semestre'])) {
    $anio_semestre = $_GET['anio_semestre'];
} else {
    die("Error: El parámetro 'anio_semestre' es obligatorio.");
}
$anio_semestre_anterior_default = '0'; // Or whatever your actual default is

    /// Validate and capture the anio_semestre_anterior
if (isset($_GET['anio_semestre_anterior'])) { // Use GET directly as your form uses GET
    $anio_semestre_anterior = $_GET['anio_semestre_anterior'];
} else {
    // If not provided in GET, use the default
echo  "no sinistra  año anteiror";}


// Capturar departamento_id si se envía (opcional)
$departamento_id_param = isset($_POST['departamento_id'])
    ? $_POST['departamento_id']
    : (isset($_GET['departamento_id']) ? $_GET['departamento_id'] : null);




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
// Obtener lista de facultades
$facultades = [];
// Esta consulta debe ejecutarse para que cualquier tipo de usuario que necesite el nombre de la facultad por ID
// tenga acceso a la lista de mapeo ID -> Nombre
$query_facultades = "SELECT PK_FAC, nombre_fac_minb FROM facultad ORDER BY nombre_fac_minb";
$result_facultades = $conn->query($query_facultades);
if ($result_facultades) { // Asegúrate de que la consulta fue exitosa
    while ($row = $result_facultades->fetch_assoc()) {
        $facultades[$row['PK_FAC']] = $row['nombre_fac_minb'];
    }
} else {
    // Opcional: Manejar el error si la consulta falla
    error_log("Error al obtener facultades: " . $conn->error);
}


// Lógica para inicializar $anio_semestre actual
$anio_semestre = isset($_POST['anio_semestre'])
    ? $_POST['anio_semestre']
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

// Si es admin y se ha seleccionado una facultad
$facultad_seleccionada = null;
if ($tipo_usuario == 1 && isset($_GET['facultad_id']) && $_GET['facultad_id'] != '') { // MODIFIED: Added check for empty string
    $facultad_seleccionada = $_GET['facultad_id'];
    $pk_fac = $facultad_seleccionada; // Sobreescribimos para las consultas
} else if ($tipo_usuario == 1 && !isset($_GET['facultad_id'])) {
    // Si es admin y no se ha seleccionado facultad (o se seleccionó "General"), no aplicar filtro
    $pk_fac = null;
}


if ($tipo_usuario == '1') {
    // No specific WHERE clause needed for tipo_usuario 1 (admin/full access)
    // If a faculty is selected, the faculty_id parameter in the SQL query will handle it.
    // If "General" is selected (facultad_id is null or empty), it will return all faculties.
    $where = "";
} elseif ($tipo_usuario == '2') {
    // For tipo_usuario 2, filter by faculty
    $where = " WHERE f.PK_FAC = '$pk_fac'";
} elseif ($tipo_usuario == '3') {

        // Fallback: if departamento_id wasn't passed, use the department linked to the user session
        $where = " WHERE d.PK_DEPTO = '$depto_user'";
    }


// Obtener los parámetros de la URL/POST
$facultad_id = $pk_fac ?? null; // MODIFIED: Use $pk_fac determined above
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
        d.PK_DEPTO,       -- ID del departamento
        d.FK_FAC,          -- ID de la facultad (relación)
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
     ata.PK_DEPTO,       -- Asegúrate de incluir esto
    ata.FK_FAC,         -- Asegúrate de incluir esto
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
// MODIFIED: $facultad_id for the current query might be null if "General" is selected by admin.
// The SQL query handles NULL gracefully.
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
// MODIFIED: Corrected the type string length for dynamic parameters
$types_current = str_repeat('d', 12) . str_repeat('d', 6) . 's'; 
$types_current = str_repeat('d', 12) . str_repeat('d', 6) . 's' . 'ssss'; // 12 doubles, 6 doubles, 1 string (anio_semestre), 4 strings (facultad_id, facultad_id, departamento_id, departamento_id)

// Adjust bind_params_current for NULL values
// For the filters, if $facultad_id is null, both parameters passed for `? IS NULL OR df.facultad_id = ?` should be null.
$bind_params_current[19] = $facultad_id; // First facultad_id
$bind_params_current[20] = $facultad_id; // Second facultad_id
$bind_params_current[21] = $departamento_id; // First departamento_id
$bind_params_current[22] = $departamento_id; // Second departamento_id

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
        $facultad_id, $facultad_id, $departamento_id, $departamento_id // MODIFIED: Use $facultad_id from above logic
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
    
    
    
// Query para el PERIODO ACTUAL - SIN FILTROS DE FACULTAD/DEPTO
$stmt_global_current = $conn->prepare($sql_query);
if (!$stmt_global_current) {
    die("Error al preparar la consulta GLOBAL para el periodo actual: " . $conn->error);
}

$bind_params_global_current = [
    $valor_punto, $smlv, $dias_catedra, $semanas_catedra, $dias_ocasional,
    $semanas_ocasional, $meses_ocasional, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
    $ajuste_catedra, $ajuste_ocasional,
    $valor_punto, $valor_punto,
    $valor_punto, $semanas_catedra, $valor_punto, $dias_ocasional,
    $anio_semestre,
    null, null, null, null // <<-- ESTO ES CLAVE: Pasar NULL para ignorar los filtros de facultad/departamento
];
$stmt_global_current->bind_param($types_current, ...$bind_params_global_current);
$stmt_global_current->execute();
$result_global_current_period = $stmt_global_current->get_result();

$facultades_data_for_global_sum_actual = [];
while ($row = $result_global_current_period->fetch_assoc()) {
    $facultad_name = $row['nombre_facultad'];
    if (!isset($facultades_data_for_global_sum_actual[$facultad_name])) {
        $facultades_data_for_global_sum_actual[$facultad_name] = [
            'total_profesores_actual' => 0,
            'gran_total_ajustado_actual' => 0
        ];
    }
    $facultades_data_for_global_sum_actual[$facultad_name]['total_profesores_actual'] += $row['total_profesores'];
    $facultades_data_for_global_sum_actual[$facultad_name]['gran_total_ajustado_actual'] += $row['gran_total_ajustado'];
}
$stmt_global_current->close();

// Query para el PERIODO ANTERIOR - SIN FILTROS DE FACULTAD/DEPTO
$facultades_data_for_global_sum_anterior = [];
if (!empty($periodo_anterior) && $valor_punto_ant > 0) {
    $stmt_global_previous = $conn->prepare($sql_query);
    if (!$stmt_global_previous) {
        die("Error al preparar la consulta GLOBAL para el periodo anterior: " . $conn->error);
    }

    $ajuste_catedra_anterior = 0;
    $ajuste_ocasional_anterior = 0.018;

    $bind_params_global_previous = [
        $valor_punto_ant, $smlv_ant, $dias_catedra_ant, $semanas_catedra_ant, $dias_ocasional_ant,
        $semanas_ocasional_ant, $meses_ocasional_ant, $porcentaje_arl, $porcentaje_caja, $porcentaje_icbf,
        $ajuste_catedra_anterior, $ajuste_ocasional_anterior,
        $valor_punto_ant, $valor_punto_ant,
        $valor_punto_ant, $semanas_catedra_ant, $valor_punto_ant, $dias_ocasional_ant,
        $periodo_anterior,
        null, null, null, null // <<-- ESTO ES CLAVE: Pasar NULL para ignorar los filtros
    ];

    $stmt_global_previous->bind_param($types_current, ...$bind_params_global_previous);
    $stmt_global_previous->execute();
    $result_global_previous_period = $stmt_global_previous->get_result();

    while ($row = $result_global_previous_period->fetch_assoc()) {
        $facultad_name = $row['nombre_facultad'];
        if (!isset($facultades_data_for_global_sum_anterior[$facultad_name])) {
            $facultades_data_for_global_sum_anterior[$facultad_name] = [
                'total_profesores_anterior' => 0,
                'gran_total_ajustado_anterior' => 0
            ];
        }
        $facultades_data_for_global_sum_anterior[$facultad_name]['total_profesores_anterior'] += $row['total_profesores'];
        $facultades_data_for_global_sum_anterior[$facultad_name]['gran_total_ajustado_anterior'] += $row['gran_total_ajustado'];
    }
    $stmt_global_previous->close();

    
}
    
// --- CALCULAR LOS TOTALES GLOBALES A PARTIR DE LOS DATOS SIN FILTRAR ---
$grand_total_ajustado_global_actual = 0;
$grand_total_profesores_global_actual = 0;
foreach ($facultades_data_for_global_sum_actual as $facultad_nombre => $data) {
    $grand_total_ajustado_global_actual += $data['gran_total_ajustado_actual'];
    $grand_total_profesores_global_actual += $data['total_profesores_actual'];
}

$grand_total_ajustado_global_anterior = 0;
$grand_total_profesores_global_anterior = 0;
foreach ($facultades_data_for_global_sum_anterior as $facultad_nombre => $data) {
    $grand_total_ajustado_global_anterior += $data['gran_total_ajustado_anterior'];
    $grand_total_profesores_global_anterior += $data['total_profesores_anterior'];
}

// --- FIN: OBTENER DATOS SIN FILTRAR PARA CÁLCULO DE TOTALES GLOBALES ---

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
/* Contenedor para las dos tarjetas de cada facultad */
.faculty-cards-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    margin-bottom: 30px;
    width: 100%;
    /* Aumenta el ancho máximo del contenedor para dar más espacio a las tarjetas */
    max-width: 1600px; /* Incrementado de 900px para permitir tarjetas más anchas */
    margin-left: auto;
    margin-right: auto;
}

/* Ajustes para las tarjetas individuales */
.card {
    /* Mantén tus estilos actuales para la tarjeta */
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box; /* Crucial para que padding y border no aumenten el tamaño */
    min-width: 350px; /* Aumenta el ancho mínimo para que no se compriman demasiado */

    
    flex: 1 1 calc(50% - 10px + 30%); /* O 1 1 585px;  Si el contenedor padre es 1200px y quieres 585px por tarjeta */
 
    flex: 1 1 calc(49% - 10px); /* Esto las hará más anchas que el 45% anterior, aprovechando el nuevo max-width del padre */
    max-width: calc(600px - 10px); /* Limita el ancho máximo para evitar que crezcan demasiado si solo hay una */

   
}

/* Media query para pantallas más pequeñas (opcional, pero recomendado) */
@media (max-width: 768px) {
    .faculty-cards-row {
        flex-direction: column; /* Apila las tarjetas verticalmente en pantallas pequeñas */
        align-items: center; /* Centra las tarjetas cuando están apiladas */
    }

    .card {
        width: 95%; /* Ocupa casi todo el ancho disponible en pantallas pequeñas */
        max-width: 400px; /* Limita el ancho máximo para móviles */
        flex: 0 0 95%; /* Asegura que la tarjeta tome casi todo el ancho en móviles */
    }
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
font-family: "Open Sans", sans-serif;
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
/* Estilos para cada caja de gráfica individual */
.chart-box {
    flex: 1; /* Permite que la caja crezca y ocupe el espacio disponible */
    /* CAMBIO AQUI: Ajusta los anchos para acomodar 3 elementos en fila */
    min-width: 30%; /* Para 3 elementos, aproximadamente 30% cada uno (30*3=90%) */
    max-width: 32%; /* Un poco más de margen para el gap */
    /* Si quieres que sean exactamente 3 en fila, podrías usar calc() */
    /* width: calc(33.33% - 14px); /* (14px = 2/3 del gap de 20px para distribuir equitativamente) */
    box-sizing: border-box; /* Incluye padding y borde en el cálculo del ancho */
    padding: 15px; /* Espaciado interno */
    background-color: #fff; /* Fondo blanco */
    border: 1px solid #ddd; /* Borde suave */
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Sombra ligera */
    text-align: center; /* Centra el título del gráfico */
}

/* Para pantallas más pequeñas, que las gráficas se apilen */
@media (max-width: 992px) { /* Ajusta el breakpoint si lo deseas, 992px es un buen estándar para tabletas */
    .chart-box {
        min-width: 45%; /* En pantallas medianas, que se muestren 2 por fila */
        max-width: 48%;
    }
}

@media (max-width: 768px) {
    .chart-box {
        min-width: 90%; /* En pantallas pequeñas, que cada gráfica ocupe casi todo el ancho */
        max-width: 100%;
    }
}
/* Estilos para el contenedor de las gráficas */
.chart-grid {
    display: flex;
    flex-wrap: wrap; /* Permite que los elementos se envuelvan a la siguiente línea si no caben */
    justify-content: center; /* Centra las gráficas horizontalmente si el espacio lo permite */
    gap: 20px; /* Espacio entre las gráficas */
    width: 100%; /* Asegura que el contenedor ocupe todo el ancho disponible */
    max-width: 1600px; /* Opcional: Define un ancho máximo para el contenedor si es muy grande */
    margin: 20px auto; /* Centra el contenedor completo en la página y añade margen superior/inferior */
}

/* Estilos para cada caja de gráfica individual (CONSOLIDADO y AJUSTADO) */
.chart-box {
    /* Utiliza calc() para distribuir el ancho de forma precisa */
    /* Para 3 columnas: (100% - 2 * gap) / 3 */
    width: calc((100% - (2 * 20px)) / 3);
    
    /* Asegúrate de que flex-grow y flex-shrink permitan el ajuste */
    flex-grow: 1; /* Permite que la caja crezca si hay espacio extra */
    flex-shrink: 1; /* Permite que la caja se encoja si es necesario (pero el width es la prioridad) */
    flex-basis: auto; 

    box-sizing: border-box; /* Incluye padding y borde en el cálculo del ancho */
    padding: 20px; /* Mantuve el padding de 20px que tenías en el segundo bloque */
    background-color: #fff; /* Fondo blanco */
    border: 1px solid #ddd; /* Borde suave */
    border-radius: 8px; /* Bordes redondeados */
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Sombra ligera (mantuve la del segundo bloque) */
    text-align: center; /* Centra el título del gráfico */
    height: 450px; /* Altura fija para las gráficas */
    position: relative; /* Si necesitas posicionar elementos internos de forma absoluta */
}

/* Media Query para pantallas medianas (ej. tabletas): 2 columnas */
@media (max-width: 992px) { /* Puedes ajustar este breakpoint si es necesario */
    .chart-box {
        /* Para 2 columnas: (100% - 1 * gap) / 2 */
        width: calc((100% - 20px) / 2);
    }
}

/* Media Query para pantallas pequeñas (ej. móviles): 1 columna */
@media (max-width: 768px) {
    .chart-box {
        width: 100%; /* Cada gráfica ocupa el ancho completo */
    }
}

.chart-grid {
    display: flex;
    flex-wrap: wrap; /* Importante para que los elementos se envuelvan a la siguiente línea */
    gap: 20px; /* Espacio entre los cuadros de los gráficos */
    justify-content: center; /* O space-around, space-between, etc. */
    align-items: flex-start; /* Alinea los elementos en la parte superior */
}

.chart-box {
    flex: 1 1 calc(33.333% - 20px); /* Para 3 columnas con 20px de gap */
    /* O si quieres que sean flexibles y se ajusten */
    min-width: 300px; /* Ancho mínimo antes de que se envuelvan */
    max-width: 400px; /* Ancho máximo */
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px; /* Espacio entre filas */
}

/* Estilos específicos para las tarjetas de participación dentro del chart-box */
.chart-box div[style*="display: flex; justify-content: space-around;"] {
    /* Puedes añadir estilos aquí si es necesario para el layout interno */
}
    .chart-title {
        text-align: center;
        font-weight: 600;
        color: #004d99;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }
 /* Contenedor principal para las tablas en línea */
.period-container {
    display: flex;
    gap: 20px; /* Espacio entre tablas */
    margin-bottom: 30px;
}

/* Estilo para cada periodo (caja) */
.period-box {
    flex: 1; /* Ocupa igual espacio */
    min-width: 0; /* Permite que se ajuste correctamente */
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    padding: 15px;
    border: 1px solid #e0e0e0;
}

/* Cabecera de periodo */
.period-header {
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
    font-size: 1.1em;
}




.info-label {
    font-weight: 500;
    color: #555;
}

/* Contenedor de tabla */
.table-container {
    overflow-x: auto;
    margin-top: 15px;
}

/* Estilo para tablas */
.compact-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9em;
}

.compact-table th {
    background-color: #f5f5f5;
    text-align: left;
    padding: 8px 12px;
    font-weight: 500;
    color: #444;
}

.compact-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

/* Mensaje cuando no hay datos */
.no-data {
    color: #666;
    font-style: italic;
    padding: 15px 0;
    text-align: center;
}

/* Estilo para valores monetarios */
.currency {
    font-family: "Roboto Mono", monospace;
    white-space: nowrap;
}




/* CONTENEDOR PRINCIPAL - TABLAS EN LÍNEA */
.period-container {
    display: flex;
    gap: 15px;
    align-items: flex-start; /* Alinea al tope */
}

/* ESTILO COMPACTO PARA TABLAS */
.compact-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85em; /* Texto más pequeño */
}

.compact-table th {
    background-color: #f8f9fa;
    padding: 6px 10px !important; /* Más compacto */
    font-weight: 500;
    border-bottom: 2px solid #dee2e6;
}

.compact-table td {
    padding: 5px 10px !important; /* Más compacto */
    border-bottom: 1px solid #eee;
    line-height: 1.3; /* Reduce espacio entre líneas */
}

/* ENLACE DE DEPARTAMENTO - ESTILO CLARO */
.departamento-link {
    background: none;
    border: none;
    color: #1a73e8 !important; /* Azul destacado */
    text-decoration: underline !important;
    text-underline-offset: 3px;
    cursor: pointer;
    padding: 0;
    font: inherit;
    display: inline-flex;
    align-items: center;
    transition: all 0.2s;
}

.departamento-link:hover {
    color: #0d62c9 !important;
    text-decoration: none !important;
    background-color: rgba(26, 115, 232, 0.05);
}

/* Indicador visual (manita + flecha) */
.departamento-link:hover::after {
    content: "→";
    margin-left: 4px;
    font-size: 0.9em;
}

/* PERIODO BOX - CONTENEDOR DE CADA TABLA */
.period-box {
    flex: 1;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
}

.period-header {
    background-color: #f8f9fa;
    padding: 8px 12px;
    font-weight: 600;
    border-bottom: 1px solid #e0e0e0;
}

/* TABLA CONTAINER - AJUSTE DE SCROLL */
.table-container {
    max-height: 400px; /* Altura máxima */
    overflow-y: auto; /* Scroll vertical si es necesario */
}

/* INFO GRID COMPACTO */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    padding: 10px;
    font-size: 0.82em;
}

.info-item {
    display: flex;
    justify-content: space-between;
}

</style>';
echo "<style> /* [Mantener todos los estilos CSS existentes] */ .selector-facultad { background: white; border-radius: 8px; padding: 10px; margin: 1Ss0px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); } .selector-facultad select { padding: 10px; border-radius: 4px; border: 1px solid #ddd; font-size: 16px; min-width: 300px; } .selector-facultad button { padding: 10px 20px; background: #004d60; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; } .selector-facultad button:hover { background: #003d50; } </style>";
echo "<div class='unicauca-container'>";

// Mostrar selector de facultad para admin
if ($tipo_usuario == 1) {
    echo "<div class='selector-facultad'>";
    
    echo "<h3>Seleccione una Facultad</h3>";
    echo "<form method='get' action=''>";
    echo "<input type='hidden' name='anio_semestre' value='$anio_semestre'>";
        echo "<input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($anio_semestre_anterior) . "'>";

    echo "<select name='facultad_id'>";
    // Added "Ver General" option
    echo "<option value=''>Ver General</option>"; // MODIFIED: Added general option
    foreach ($facultades as $id => $nombre) {
        // MODIFIED: Check for $facultad_seleccionada against the current $id
        echo "<option value='$id'" . ($facultad_seleccionada == $id ? ' selected' : '') . ">$nombre</option>";
    }
    echo "</select>";
    echo "<button type='submit'>Ver Reporte</button>";
    echo "</form>";
    echo "</div>";
}

// Logic for combining data for charts (MOVED UP FOR GENERAL COMPARATIVE)
$facultades_data = [];
// Process current period data
foreach ($data_current_period as $row) {
    $facultad = $row['nombre_facultad'];
    $departamento = $row['nombre_departamento'];
    $tipo = $row['tipo_docente'];
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

// Process previous period data
foreach ($data_previous_period as $row) {
    $facultad = $row['nombre_facultad'];
    $departamento = $row['nombre_departamento'];
    // Ensure the faculty key exists before trying to access departments
    if (!isset($facultades_data[$facultad])) {
         $facultades_data[$facultad] = [
            'departamentos' => [],
            'total_profesores_actual' => 0, // Initialize as 0 for current period if not present
            'gran_total_ajustado_actual' => 0, // Initialize as 0 for current period if not present
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
    $facultades_data[$facultad]['departamentos'][$departamento]['profesores_anterior'] += $row['total_profesores'];
    $facultades_data[$facultad]['departamentos'][$departamento]['total_anterior'] += $row['gran_total_ajustado'];
    $facultades_data[$facultad]['total_profesores_anterior'] += $row['total_profesores'];
    $facultades_data[$facultad]['gran_total_ajustado_anterior'] += $row['gran_total_ajustado'];
}


// Check if it's a specific faculty report OR if it's an admin viewing general report
if ($tipo_usuario == 1 && !$facultad_seleccionada) { // MODIFIED: Show general comparative if admin and no specific faculty selected
    // Gráfica comparativa de totales por facultad (these remain vertical)
    echo "<div style='margin: 40px 0; border: 1px solid #ddd; padding: 20px; border-radius: 8px;'>";
    echo "<h3 style='text-align: center;'>Comparativa General por Facultad</h3>";

    // Ensure faculties array is properly populated from $facultades_data keys for consistent labels
    $facultades_chart_labels = array_keys($facultades_data);
    sort($facultades_chart_labels); // Sort labels for consistency

    $totales_actual_general = [];
    $totales_anterior_general = [];
    $profesores_actual_total_general = [];
    $profesores_anterior_total_general = [];

    foreach ($facultades_chart_labels as $facultad_label) {
        $data = $facultades_data[$facultad_label];
        $totales_actual_general[] = $data['gran_total_ajustado_actual'];
        $totales_anterior_general[] = $data['gran_total_ajustado_anterior'];
        $profesores_actual_total_general[] = $data['total_profesores_actual'];
        $profesores_anterior_total_general[] = $data['total_profesores_anterior'];
    }

    if (!empty($facultades_data)) {
      ?>
<?php

$profesores_data_combined = [];
foreach ($facultades_chart_labels as $index => $label) {
    $profesores_data_combined[] = [
        'label' => $label,
        'actual' => $profesores_actual_total_general[$index],
        'anterior' => $profesores_anterior_total_general[$index]
    ];
}

// Sort by 'actual' count in descending order
usort($profesores_data_combined, function($a, $b) {
    return $b['actual'] <=> $a['actual'];
});

// Separate back into sorted arrays
$sorted_facultades_profesores_labels = array_column($profesores_data_combined, 'label');
$sorted_profesores_actual_total_general = array_column($profesores_data_combined, 'actual');
$sorted_profesores_anterior_total_general = array_column($profesores_data_combined, 'anterior');

// --- Sorting Logic for "Valor Proyectado por Facultad" Chart ---
// Combine data for sorting
$valores_data_combined = [];
foreach ($facultades_chart_labels as $index => $label) {
    $valores_data_combined[] = [
        'label' => $label,
        'actual' => $totales_actual_general[$index],
        'anterior' => $totales_anterior_general[$index]
    ];
}

// Sort by 'actual' value in descending order
usort($valores_data_combined, function($a, $b) {
    return $b['actual'] <=> $a['actual'];
});

// Separate back into sorted arrays
$sorted_facultades_valores_labels = array_column($valores_data_combined, 'label');
$sorted_totales_actual_general = array_column($valores_data_combined, 'actual');
$sorted_totales_anterior_general = array_column($valores_data_combined, 'anterior');

?>

<div style='display: flex;'>
    <div style='width: 50%; padding: 15px;'>
        <h4 style='text-align: center;'>Total de Profesores por Facultad</h4>
        <canvas id='chartTotalProfesoresFac' height='400'></canvas>
    </div>

    <div style='width: 50%; padding: 15px;'>
        <h4 style='text-align: center;'>Valor Proyectado por Facultad</h4>
        <canvas id='chartTotalValorFac' height='400'></canvas>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.register(ChartDataLabels);

    // Gráfica de Profesores por Facultad (Horizontal Bar)
    const ctxTotalProfFac = document.getElementById('chartTotalProfesoresFac').getContext('2d');
    new Chart(ctxTotalProfFac, {
        type: 'bar',
        data: {
            labels: <?= json_encode($sorted_facultades_profesores_labels) ?>,
            datasets: [
                {
                    label: 'Actual (<?= htmlspecialchars($anio_semestre) ?>)',
                    data: <?= json_encode($sorted_profesores_actual_total_general) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (<?= htmlspecialchars($periodo_anterior) ?>)',
                    data: <?= json_encode($sorted_profesores_anterior_total_general) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y', // <--- THIS MAKES IT HORIZONTAL
            responsive: true,
            scales: {
                x: { // <--- X-axis for horizontal bar charts
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Profesores'
                    }
                },
                y: { // <--- Y-axis for horizontal bar charts (labels)
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
                    anchor: 'end', // Position data labels at the end of the bars
                    align: 'end', // Align them with the end of the bars
                    offset: 4,
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

    // Gráfica de Valor Proyectado por Facultad (Horizontal Bar)
    const ctxTotalValFac = document.getElementById('chartTotalValorFac').getContext('2d');
    new Chart(ctxTotalValFac, {
        type: 'bar',
        data: {
            labels: <?= json_encode($sorted_facultades_valores_labels) ?>,
            datasets: [
                {
                    label: 'Actual (<?= htmlspecialchars($anio_semestre) ?>)',
                    data: <?= json_encode($sorted_totales_actual_general) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (<?= htmlspecialchars($periodo_anterior) ?>)',
                    data: <?= json_encode($sorted_totales_anterior_general) ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            indexAxis: 'y', // <--- THIS MAKES IT HORIZONTAL
            responsive: true,
            scales: {
                x: { // <--- X-axis for horizontal bar charts
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor Proyectado (en millones)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + (value / 1000000).toLocaleString(undefined, {maximumFractionDigits: 1}) + 'M';
                        }
                    }
                },
                y: { // <--- Y-axis for horizontal bar charts (labels)
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
                    anchor: 'end', // Position data labels at the end of the bars
                    align: 'end', // Align them with the end of the bars
                    offset: 4,
                    formatter: function(value, context) {
                        return '$' + (value / 1000000).toLocaleString(undefined, {maximumFractionDigits: 1}) + 'M';
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Valor: $' + (context.raw / 1000000).toLocaleString(undefined, {maximumFractionDigits: 2}) + ' millones';
                        }
                    }
                }
            }
        }
    });
});
</script>
<?php
    } else {
        echo "<div class='no-data'>No se encontraron datos para la comparativa general por facultad.</div>";
    }
    echo "</div>"; // Cierre del contenedor de comparativa general

} else if ($facultad_seleccionada || $tipo_usuario == 2 || $tipo_usuario == 3) { // Only show specific faculty/department report if a specific faculty is selected (or if not admin)
    // If it's a specific faculty report, show its header
if ($tipo_usuario ==1) {    
echo "<div class='card-header-custom bg-unicauca-blue-dark text-white d-flex justify-content-between align-items-center' style='margin: 0 0 20px 0;'>";
    echo "<h2 class='mb-0'>Datos de Facultad: " . htmlspecialchars($facultades[$pk_fac] ?? '') . "</h2>"; // MODIFIED: Added N/A for safety
    echo "</div>";
}

    if (!empty($data_current_period)) {
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

    // INICIO DEL NUEVO CONTENEDOR PARA LAS DOS TARJETAS DE ESTA FACULTAD
   // echo "<div class='faculty-cards-row'>";



//    echo "</div>"; // FIN DEL NUEVO CONTENEDOR PARA LAS DOS TARJETAS DE ESTA FACULTAD
}
    
     } else {
        echo "<div class='no-data'>No se encontraron datos para el periodo actual.</div>"; // MODIFIED: More descriptive message
    }

    echo "<div class='chart-grid'>";
    echo "<div class='chart-box'>";
    echo "<h4 class='chart-title'>Profesores por Tipo de Profesor</h4>";
    echo "<canvas id='chartProfesoresTipo' height='300'></canvas>";
    echo "</div>";
    echo "<div class='chart-box'>";
    echo "<h4 class='chart-title'>Valor Proyectado por Tipo Profesores</h4>";
    echo "<canvas id='chartValorTipo' height='300'></canvas>";
    echo "</div>";
    echo "<div class='chart-box' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;'>";
         if (isset($prof_actual)) {

    // Tarjeta de Profesores - Versión compacta
    echo "<div class='card' style='
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #3498db;
        padding: 16px;
    '>";
    
    echo "<div style='display: flex; align-items: center; margin-bottom: 12px;'>";
    
      
echo "<div style='width: 32px; height: 32px; background-color: #3498db20; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 10px;'>";
    echo "<svg width='16' height='16' viewBox='0 0 24 24' fill='#3498db' xmlns='http://www.w3.org/2000/svg'><path d='M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z'/><path d='M12 14C7.58172 14 4 17.5817 4 22H20C20 17.5817 16.4183 14 12 14Z'/></svg>";
    echo "</div>";
    echo "<h4 style='margin: 0; color: #2c3e50; font-size: 1rem; font-weight: 600;'>Profesores</h4>";
    echo "</div>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;'>";
    echo "<div>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Actual</div>";
    echo "<div style='font-weight: 700; font-size: 1.4rem; color: #2c3e50; line-height: 1;'>$prof_actual</div>";
    echo "</div>";
    
    echo "<div style='text-align: right;'>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Anterior</div>";
    echo "<div style='font-weight: 600; font-size: 1.1rem; color: #95a5a6; line-height: 1;'>$prof_anterior</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='
        background-color: #f8fafc;
        padding: 10px 12px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
    '>";
    echo "<div style='color: #7f8c8d; font-weight: 500;'>Variación</div>";
    echo "<div style='display: flex; align-items: center; gap: 6px;'>";
    echo "<span style='color: $color_prof; font-weight: 600;'>$icon_prof " . ($diff_prof >= 0 ? "+$diff_prof" : $diff_prof) . "</span>";
    echo "<span style='background-color: {$color_prof}15; color: $color_prof; padding: 2px 8px; border-radius: 10px; font-weight: 600;'>" . number_format($porc_prof, 1) . "%</span>";
    echo "</div>";
    echo "</div>";
    echo "</div>"; // Cierra tarjeta Profesores
    
    // Tarjeta de Valor Proyectado - Versión compacta
    echo "<div class='card' style='
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border-left: 4px solid #9b59b6;
        padding: 16px;
    '>";
    
    echo "<div style='display: flex; align-items: center; margin-bottom: 12px;'>";
    echo "<div style='width: 32px; height: 32px; background-color: #9b59b620; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-right: 10px;'>";
    echo "<svg width='16' height='16' viewBox='0 0 24 24' fill='#9b59b6' xmlns='http://www.w3.org/2000/svg'><path d='M12 1L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 1ZM12 11.99H19C18.47 16.11 15.72 19.78 12 20.93V12H5V6.3L12 3.19V11.99Z'/></svg>";
    echo "</div>";
    echo "<h4 style='margin: 0; color: #2c3e50; font-size: 1rem; font-weight: 600;'>Valor Proyectado</h4>";
    echo "</div>";
    
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;'>";
    echo "<div>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Actual</div>";
    echo "<div style='font-weight: 700; font-size: 1.4rem; color: #2c3e50; line-height: 1;'>$" . $formatted_valor_actual . "</div>";
    echo "</div>";
    
    echo "<div style='text-align: right;'>";
    echo "<div style='font-size: 0.75rem; color: #7f8c8d; margin-bottom: 4px;'>Anterior</div>";
    echo "<div style='font-weight: 600; font-size: 1.1rem; color: #95a5a6; line-height: 1;'>$" . $formatted_valor_anterior . "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='
        background-color: #f8fafc;
        padding: 10px 12px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8rem;
    '>";
    echo "<div style='color: #7f8c8d; font-weight: 500;'>Variación</div>";
    echo "<div style='display: flex; align-items: center; gap: 6px;'>";
    echo "<span style='color: $color_valor; font-weight: 600;'>$icon_valor " . ($diff_valor >= 0 ? "+$" : "-$") . $formatted_diff_valor . "</span>";
    echo "<span style='background-color: {$color_valor}15; color: $color_valor; padding: 2px 8px; border-radius: 10px; font-weight: 600;'>" . number_format($porc_valor, 1) . "%</span>";
    echo "</div>";
    echo "</div>";
    echo "</div>"; // Cierra tarjeta Valor Proyectado
    }
echo "</div>"; // Cierra chart-box
    echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
    echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0'></script>";
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);

        const dataCurrent = " . json_encode($data_current_period) . ";
        const dataPrevious = " . json_encode($data_previous_period) . ";

        const processDataForCharts = (data) => {
            const result = {};
            data.forEach(row => {
                const tipo = row.tipo_docente;
                if (!result[tipo]) {
                    result[tipo] = { total_profesores: 0, gran_total_ajustado: 0 };
                }
                result[tipo].total_profesores += parseInt(row.total_profesores);
                result[tipo].gran_total_ajustado += parseFloat(row.gran_total_ajustado);
            });
            return result;
        };

        const currentPeriodSummary = processDataForCharts(dataCurrent);
        const previousPeriodSummary = processDataForCharts(dataPrevious);

        const labels = Array.from(new Set([...Object.keys(currentPeriodSummary), ...Object.keys(previousPeriodSummary)]));
        labels.sort();

        const profesoresActual = labels.map(label => currentPeriodSummary[label]?.total_profesores || 0);
        const profesoresAnterior = labels.map(label => previousPeriodSummary[label]?.total_profesores || 0);
        const valorActual = labels.map(label => currentPeriodSummary[label]?.gran_total_ajustado || 0);
        const valorAnterior = labels.map(label => previousPeriodSummary[label]?.gran_total_ajustado || 0);

        // Gráfica de Profesores por Tipo de Docente
        const ctxProfesoresTipo = document.getElementById('chartProfesoresTipo').getContext('2d');
        new Chart(ctxProfesoresTipo, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Actual (" . htmlspecialchars($anio_semestre) . ")',
                        data: profesoresActual,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                        data: profesoresAnterior,
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
                            text: 'Número de Profesores'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tipo de Docente'
                        }
                    }
                },
                plugins: {
                    datalabels: {
                        display: true,
                        color: '#333',
                        anchor: 'end',
                        align: 'end',
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
// Gráfica de Valor Proyectado por Tipo de Docente
const ctxValorTipo = document.getElementById('chartValorTipo').getContext('2d');
new Chart(ctxValorTipo, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Actual (" . htmlspecialchars($anio_semestre) . ")',
                data: valorActual,
                backgroundColor: 'rgba(255, 159, 64, 0.7)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            },
            {
                label: 'Anterior (" . htmlspecialchars($periodo_anterior) . ")',
                data: valorAnterior,
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
                    text: 'Valor Proyectado (en millones)'
                },
                ticks: {
                    callback: function(value) {
                        return '$' + (value / 1000000).toFixed(1) + 'M';
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Tipo de Docente'
                }
            }
        },
        plugins: {
            datalabels: {
                display: true,
                // Cambiado a un tono de gris
                color: '#6c757d', // Un gris suave
                anchor: 'center',
                align: 'top',
                offset: -10,
                formatter: function(value) {
                    return '$' + (value / 1000000).toFixed(1) + 'M';
                },
                font: {
                    size: 10,
                    weight: 'bold'
                },
                clip: false
            }
        }
    }
});
    });
    </script>";
}
    
// Procesamos los datos para los gráficos
$departamentos_data = [];

// Procesar datos del periodo actual
foreach ($data_current_period as $row) {
    $depto = $row['nombre_departamento'];
    if (!isset($departamentos_data[$depto])) {
        $departamentos_data[$depto] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'valor_actual' => 0,
            'valor_anterior' => 0
        ];
    }
    $departamentos_data[$depto]['profesores_actual'] += $row['total_profesores'];
    $departamentos_data[$depto]['valor_actual'] += $row['gran_total_ajustado'];
}

// Procesar datos del periodo anterior
foreach ($data_previous_period as $row) {
    $depto = $row['nombre_departamento'];
    if (!isset($departamentos_data[$depto])) {
        $departamentos_data[$depto] = [
            'profesores_actual' => 0,
            'profesores_anterior' => 0,
            'valor_actual' => 0,
            'valor_anterior' => 0
        ];
    }
    $departamentos_data[$depto]['profesores_anterior'] += $row['total_profesores'];
    $departamentos_data[$depto]['valor_anterior'] += $row['gran_total_ajustado'];
}

// Ordenar departamentos por cantidad de profesores (mayor a menor)
uasort($departamentos_data, function($a, $b) {
    return $b['profesores_actual'] - $a['profesores_actual'];
});

// Estilos CSS para los gráficos
echo "<style>
    .chart-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 30px 0;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }

    .chart-wrapper {
        width: 100%;
        height: 100%;
        min-height: 400px;
        position: relative;
    }

    .chart-title {
        text-align: center;
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
    }

    @media (max-width: 768px) {
        .chart-container {
            grid-template-columns: 1fr;
        }
     
</style>";
// Mostrar los gráficos solo si hay datos
if (!empty($departamentos_data) || $facultad_seleccionada) {
    // Obtener IDs de departamentos y facultades
    $depto_labels = array_keys($departamentos_data);
    $depto_ids = [];
    $facultad_ids = [];
    
    $query_deptos = "SELECT PK_DEPTO, depto_nom_propio, FK_FAC FROM deparmanentos 
                    WHERE depto_nom_propio IN ('" . implode("','", array_map([$conn, 'real_escape_string'], $depto_labels)) . "')";
    $result_deptos = $conn->query($query_deptos);
    
    $nombre_a_ids = [];
    while ($row = $result_deptos->fetch_assoc()) {
        $nombre_a_ids[$row['depto_nom_propio']] = [
            'PK_DEPTO' => $row['PK_DEPTO'],
            'PK_FAC' => $row['FK_FAC']
        ];
    }

    // Preparar arrays ordenados para los gráficos
    $depto_ids_ordenados = [];
    $facultad_ids_ordenados = [];
    foreach ($depto_labels as $depto_nombre) {
        $depto_ids_ordenados[] = $nombre_a_ids[$depto_nombre]['PK_DEPTO'] ?? null;
        $facultad_ids_ordenados[] = $nombre_a_ids[$depto_nombre]['PK_FAC'] ?? null;
    }

    echo "<div class='chart-grid'>";
    echo "<div class='chart-box'>";
    echo "<h3 class='chart-title'>Profesores por Departamento</h3>";
    echo "<div class='chart-wrapper'>";
    echo "<canvas id='chartProfesoresDepto'></canvas>";
    echo "</div>";
    echo "</div>";
    
 echo "<div class='chart-box'>";
echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
echo "<h3 class='chart-title'>Proyectado por Departamento</h3>";
echo "<button id='btnAmpliarValor' style='
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: 13px;
    color: #374151;
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
'
onmouseover='this.style.borderColor=\"#9ca3af\"; this.style.backgroundColor=\"#f9fafb\"' 
onmouseout='this.style.borderColor=\"#d1d5db\"; this.style.backgroundColor=\"#ffffff\"'>
Ampliar
</button>";echo "</div>";
echo "<div class='chart-wrapper'>";
echo "<canvas id='chartValorDepto'></canvas>";
echo "</div>";
echo "</div>";
    
    // --- INICIO DE LÓGICA PARA DETERMINAR QUÉ FACULTAD SE VA A MOSTRAR EN LOS GRÁFICOS DE COMPARACIÓN ---

$faculty_id_for_display = null; // Inicializamos a null
$nombre_facultad_seleccionada = null; // Inicializamos a null

if ($tipo_usuario == 1) { // Si es un usuario administrador
    // Los administradores pueden seleccionar una facultad vía GET.
    // Si no seleccionan ninguna, esta variable permanecerá null,
    // y se les pedirá que seleccionen una facultad.
    if (isset($_GET['facultad_id']) && !empty($_GET['facultad_id'])) {
        $faculty_id_for_display = (int)$_GET['facultad_id'];
    }
} elseif ($tipo_usuario == 2) { // Si es un usuario de tipo 2 (ej. Decano/Jefe de Facultad)
    // Para este tipo de usuario, asumimos que su facultad_id está en la sesión
    // o se obtiene de su perfil de usuario al iniciar sesión.

        $faculty_id_for_display = $pk_fac;
    // Si $faculty_id_for_display sigue siendo null aquí, significa que el usuario de tipo 2
    // no tiene una facultad asignada en su sesión, y se le mostrará un mensaje de error.
}

// Una vez determinado $faculty_id_for_display, obtenemos el nombre y los datos
if ($faculty_id_for_display !== null) {
    $nombre_facultad_seleccionada = $facultades[$faculty_id_for_display] ?? null;

    // Verificar si se encontró el nombre de la facultad y sus datos en $facultades_data
    if ($nombre_facultad_seleccionada && isset($facultades_data[$nombre_facultad_seleccionada])) {
        $selected_faculty_data = $facultades_data[$nombre_facultad_seleccionada];
        
        $selected_faculty_profesores_actual = $selected_faculty_data['total_profesores_actual'];
        $selected_faculty_ajustado_actual = $selected_faculty_data['gran_total_ajustado_actual'];
        
        // Calcular porcentajes
        $porcentaje_profesores = ($grand_total_profesores_global_actual > 0)
            ? ($selected_faculty_profesores_actual / $grand_total_profesores_global_actual) * 100
            : 0;
        $porcentaje_valor = ($grand_total_ajustado_global_actual > 0)
            ? ($selected_faculty_ajustado_actual / $grand_total_ajustado_global_actual) * 100
            : 0;
            
        // Asegurarse de que el porcentaje no supere el 100% para la altura de la barra
        $porcentaje_profesores_display = min(100, max(0, $porcentaje_profesores));
        $porcentaje_valor_display = min(100, max(0, $porcentaje_valor));

    echo "<div class='chart-box' style='background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>";
    echo "<h3 class='chart-title' style='margin-top: 0; color: #2c3e50; text-align: center;'>Participación ".htmlspecialchars($nombre_facultad_seleccionada)." respecto al total de facultades (".$anio_semestre.")</h3>";

if ($faculty_id_for_display !== null && $nombre_facultad_seleccionada && isset($facultades_data[$nombre_facultad_seleccionada])) {
    // Cálculo de promedios (parte nueva que añade funcionalidad)
    $promedio_facultad = $selected_faculty_ajustado_actual > 0 && $selected_faculty_profesores_actual > 0 
        ? $selected_faculty_ajustado_actual / $selected_faculty_profesores_actual 
        : 0;
        
    $promedio_global = $grand_total_ajustado_global_actual > 0 && $grand_total_profesores_global_actual > 0
        ? $grand_total_ajustado_global_actual / $grand_total_profesores_global_actual
        : 0;
    ?>
    
    <!-- Diseño nuevo -->
    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin: 20px 0;">
        <!-- Tarjeta de Profesores -->
        <div style="flex: 1; min-width: 150px; background: #f8fafc; border-radius: 10px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                    <span style="color: #1976d2; font-weight: bold;"><?= number_format($porcentaje_profesores, 1) ?>%</span>
                </div>
                <div>
                    <div style="font-weight: 600; color: #2c3e50;">Profesores</div>
                    <div style="font-size: 0.8rem; color: #7f8c8d;">
                        <?= number_format($selected_faculty_profesores_actual, 0, ',', '.') ?> de <?= number_format($grand_total_profesores_global_actual, 0, ',', '.') ?>
                    </div>
                </div>
            </div>
            <div style="height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; width: <?= $porcentaje_profesores_display ?>%; background: #1976d2;"></div>
            </div>
        </div>
        
        <!-- Tarjeta de Valor Proyectado -->
        <div style="flex: 1; min-width: 150px; background: #f8fafc; border-radius: 10px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="width: 40px; height: 40px; background: #f3e5f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                    <span style="color: #8e24aa; font-weight: bold;"><?= number_format($porcentaje_valor, 1) ?>%</span>
                </div>
                <div>
                    <div style="font-weight: 600; color: #2c3e50;">Valor Proyectado</div>
                    <div style="font-size: 0.8rem; color: #7f8c8d;">
                        $<?= number_format($selected_faculty_ajustado_actual, 0, ',', '.') ?> de $<?= number_format($grand_total_ajustado_global_actual, 0, ',', '.') ?>
                    </div>
                </div>
            </div>
            <div style="height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; width: <?= $porcentaje_valor_display ?>%; background: #8e24aa;"></div>
            </div>
        </div>
    </div>
    
    <!-- Sección de promedios (nueva funcionalidad) -->
    <div style="margin-top: 25px; background: #f5f7fa; border-radius: 8px; padding: 15px;">
        <h4 style="margin-top: 0; margin-bottom: 15px; text-align: center; color: #2c3e50; font-size: 1rem;">Valor promedio por profesor</h4>
        
        <div style="display: flex; justify-content: center; gap: 30px; text-align: center;">
            <div>
                <div style="font-size: 0.8rem; color: #7f8c8d; margin-bottom: 5px;">Esta facultad</div>
                <div style="padding: 8px 15px; background: #fff; border-radius: 20px; font-weight: bold; color: #1976d2; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block;">
                    $<?= number_format($promedio_facultad, 2, ',', '.') ?>
                </div>
            </div>
            
            <div>
                <div style="font-size: 0.8rem; color: #7f8c8d; margin-bottom: 5px;">Promedio general</div>
                <div style="padding: 8px 15px; background: #fff; border-radius: 20px; font-weight: bold; color: #4caf50; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block;">
                    $<?= number_format($promedio_global, 2, ',', '.') ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    } else {
        // Mensaje si no se encuentran datos para la facultad (válida) seleccionada/asignada
        echo "<div class='alert alert-info text-center' style='margin-top: 20px; padding: 15px;'>No se encontraron datos para la facultad seleccionada/asignada.</div>";
    }
    }}   echo "</div>"; // Cierre del div.chart-box
    
    
    // Contenedor para el gráfico ampliado (inicialmente oculto)
echo "<div id='chartAmpliadoContainer' style='display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;'>";
echo "<div style='background-color: white; padding: 20px; border-radius: 8px; max-width: 90%; max-height: 90%; overflow: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative;'>";
echo "<button id='cerrarAmpliado' style='position: absolute; top: 15px; right: 15px; background: none; border: none; color: #6c757d; font-size: 1.5rem; cursor: pointer; padding: 5px; line-height: 1;' aria-label='Cerrar modal'>&times;</button>";echo "<h3 style='text-align: center; color: #2c3e50;'>Valor Proyectado por Departamento (Ampliado)</h3>";
echo "<canvas id='chartValorDeptoAmpliado' style='max-width: 100%; max-height: 100%;'></canvas>";
echo "</div>";
echo "</div>"; //antesd echad grd se incluyo esto
    echo "</div>";
    
    echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
    echo "<script src='https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0'></script>";
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);
        
        // Datos preparados desde PHP
        const labelsDepto = " . json_encode($depto_labels) . ";
        const deptoIds = " . json_encode($depto_ids_ordenados) . ";
        const facultadIds = " . json_encode($facultad_ids_ordenados) . ";
        const profesoresActual = " . json_encode(array_column($departamentos_data, 'profesores_actual')) . ";
        const profesoresAnterior = " . json_encode(array_column($departamentos_data, 'profesores_anterior')) . ";
        const valorActual = " . json_encode(array_column($departamentos_data, 'valor_actual')) . ";
        const valorAnterior = " . json_encode(array_column($departamentos_data, 'valor_anterior')) . ";
        const anioSemestre = '" . htmlspecialchars($anio_semestre) . "';
        const anioSemestreAnterior = '" . htmlspecialchars($periodo_anterior) . "';
        
        // Configuración común para ambos gráficos con eventos de clic
        const commonOptions = {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end',
                    align: 'end',
                    font: {
                        weight: 'bold'
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        autoSkip: false,
                        maxRotation: 0,
                        font: {
                            size: 10
                        },
                        callback: function(value, index) {
                            // Hacer que las etiquetas sean clickeables
                            return labelsDepto[index];
                        }
                    }
                }
            },
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const deptoId = deptoIds[index];
                    const facultadId = facultadIds[index];
                    
                    if (deptoId && facultadId) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'depto_comparativo.php';
                        
                        const campos = [
                            {name: 'facultad_id', value: facultadId},
                            {name: 'departamento_id', value: deptoId},
                            {name: 'anio_semestre', value: anioSemestre},
                            {name: 'anio_semestre_anterior', value: anioSemestreAnterior}
                        ];
                        
                        campos.forEach(campo => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = campo.name;
                            input.value = campo.value;
                            form.appendChild(input);
                        });
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            }
        };
        
     // Gráfico de Profesores por Departamento
const ctxProfDepto = document.getElementById('chartProfesoresDepto');
if (ctxProfDepto) {
    new Chart(ctxProfDepto, {
        type: 'bar',
        data: {
            labels: labelsDepto,
            datasets: [
                {
                    label: 'Actual (' + anioSemestre + ')',
                    data: profesoresActual,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (' + anioSemestreAnterior + ')',
                    data: profesoresAnterior,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                datalabels: {
                    ...commonOptions.plugins.datalabels,
                    color: '#6c757d', // Gris suave
                    font: {
                        size: 11,
                        weight: 'normal' // Sin negrita
                    },
                    formatter: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad de Profesores',
                        color: '#6c757d' // Gris consistente
                    },
                    ticks: {
                        color: '#6c757d' // Color gris para los ticks
                    }
                },
                y: {
                    ticks: {
                        color: '#6c757d' // Color gris para los ticks del eje Y
                    }
                }
            }
        }
    });
}
     // Gráfico de Valor Proyectado por Departamento
// --- Función para crear el gráfico de Valor Proyectado (reutilizable) ---
function createValorChart(ctx, labels, actual, anterior, anio, anioAnt) {
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Actual (' + anio + ')',
                    data: actual,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Anterior (' + anioAnt + ')',
                    data: anterior,
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                datalabels: {
                    ...commonOptions.plugins.datalabels,
                    color: '#6c757d',
                    font: {
                        size: 11,
                        weight: 'normal'
                    },
                    formatter: function(value) {
                        return '$' + (value / 1000000).toLocaleString(undefined, {maximumFractionDigits: 2}) + 'M';
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Valor Proyectado (en millones)',
                        color: '#6c757d'
                    },
                    ticks: {
                        color: '#6c757d',
                        callback: function(value) {
                            return '$' + (value / 1000000).toFixed(1) + 'M';
                        }
                    }
                },
                y: {
                    ticks: {
                        color: '#6c757d'
                    }
                }
            }
        }
    });
}

// Crear gráfico original de Valor Proyectado por Departamento
const ctxValorDepto = document.getElementById('chartValorDepto');
let chartValorDepto = null;
if (ctxValorDepto) {
    chartValorDepto = createValorChart(
        ctxValorDepto,
        labelsDepto,
        valorActual,
        valorAnterior,
        anioSemestre,
        anioSemestreAnterior
    );
}

// --- Lógica para el botón de Ampliar y el gráfico ampliado ---
const btnAmpliarValor = document.getElementById('btnAmpliarValor');
const chartAmpliadoContainer = document.getElementById('chartAmpliadoContainer');
const cerrarAmpliado = document.getElementById('cerrarAmpliado');
const chartValorDeptoAmpliado = document.getElementById('chartValorDeptoAmpliado');
let chartAmpliado = null; // Variable para almacenar la instancia del gráfico ampliado

if (btnAmpliarValor && chartAmpliadoContainer && cerrarAmpliado && chartValorDeptoAmpliado) {
    btnAmpliarValor.addEventListener('click', function() {
        // Mostrar el contenedor del gráfico ampliado
        chartAmpliadoContainer.style.display = 'flex';

        // Destruir el gráfico ampliado anterior si existe
        if (chartAmpliado) {
            chartAmpliado.destroy();
        }

        // Crear el gráfico ampliado
        chartAmpliado = createValorChart(
            chartValorDeptoAmpliado,
            labelsDepto,
            valorActual,
            valorAnterior,
            anioSemestre,
            anioSemestreAnterior
        );
    });

    cerrarAmpliado.addEventListener('click', function() {
        // Ocultar el contenedor del gráfico ampliado
        chartAmpliadoContainer.style.display = 'none';
        // Destruir el gráfico ampliado para liberar memoria
        if (chartAmpliado) {
            chartAmpliado.destroy();
            chartAmpliado = null;
        }
    });

    // Cerrar también al hacer clic fuera del contenido del modal
    chartAmpliadoContainer.addEventListener('click', function(event) {
        if (event.target === chartAmpliadoContainer) {
            chartAmpliadoContainer.style.display = 'none';
            if (chartAmpliado) {
                chartAmpliado.destroy();
                chartAmpliado = null;
            }
        }
    });
}
    });
    </script>";
} else {
    echo "<div class='alert alert-info'>No hay datos de departamentos para mostrar gráficos</div>";
}
echo "<div class='period-container'>";
          
// Sección de Periodo Actual
echo "<div class='period-box'>";
            echo "<div class='period-header'>Periodo Actual: " . htmlspecialchars($anio_semestre) . "</div>";
           /* echo "<div class='info-grid'>";
            echo "<div class='info-item'><span class='info-label'>Días Cátedra:</span> " . number_format($dias_catedra, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Semanas Cátedra:</span> " . number_format($semanas_catedra, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Días Ocasional:</span> " . number_format($dias_ocasional, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Semanas Ocasional:</span> " . number_format($semanas_ocasional, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Meses Ocasional:</span> " . number_format($meses_ocasional, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Valor Punto:</span> $" . number_format($valor_punto, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>SMLV:</span> $" . number_format($smlv, 0, ',', '.') . "</div>";
            echo "</div>"; // cierre info-grid
*/
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
        
        // Celda con enlace al departamento (usa PK_DEPTO y FK_FAC)
        echo "<td>";
echo "<form action='depto_comparativo.php' method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['PK_DEPTO']) . "'>";
        echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($row['FK_FAC']) . "'>";
        echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
        echo "<input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>";
    echo "<button type='submit' class='departamento-link'>";
echo htmlspecialchars($row['nombre_departamento']);
echo "</button>";
        echo "</form>";
        echo "</td>";
        
        echo "<td>" . htmlspecialchars($row['tipo_docente']) . "</td>";
        echo "<td>" . htmlspecialchars($row['total_profesores']) . "</td>";
        echo "<td class='currency'>$" . number_format($row['gran_total_ajustado'], 0, ',', '.') . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
} else {
                echo "<div class='no-data'>No hay datos disponibles para el periodo actual</div>";
            }
            echo "</div>"; // cierre period-box

            // Sección de Periodo Anterior
            echo "<div class='period-box'>";
            echo "<div class='period-header'>Periodo Anterior: " . htmlspecialchars($periodo_anterior) . "</div>";
          /*  echo "<div class='info-grid'>";
            echo "<div class='info-item'><span class='info-label'>Días Cátedra:</span> " . number_format($dias_catedra_ant, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Semanas Cátedra:</span> " . number_format($semanas_catedra_ant, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Días Ocasional:</span> " . number_format($dias_ocasional_ant, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Semanas Ocasional:</span> " . number_format($semanas_ocasional_ant, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Meses Ocasional:</span> " . number_format($meses_ocasional_ant, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>Valor Punto:</span> $" . number_format($valor_punto_ant, 0, ',', '.') . "</div>";
            echo "<div class='info-item'><span class='info-label'>SMLV:</span> $" . number_format($smlv_ant, 0, ',', '.') . "</div>";
            echo "</div>"; // cierre info-grid
            */
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
        
        // Celda con enlace al departamento (usando PK_DEPTO y FK_FAC)
        echo "<td>";
echo "<form action='depto_comparativo.php' method='POST' style='display: inline;'>";
        echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($row['PK_DEPTO']) . "'>";
        echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($row['FK_FAC']) . "'>";
        echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
        echo "<input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>";
     echo "<button type='submit' class='departamento-link'>";
echo htmlspecialchars($row['nombre_departamento']);
echo "</button>";
        echo "</form>";
        echo "</td>";
        
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
            echo "</div>"; // cierre period-box
            echo "</div>"; // cierre period-container
echo "</div>"; // cierre period-container

   
echo "</div>"; // cierre unicauca-container
?>