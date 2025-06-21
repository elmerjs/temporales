<?php
// 1. Configuración de la base de datos
define('DB_SERVER', 'localhost'); // Cambia esto por tu servidor de DB
define('DB_USERNAME', 'root'); // Cambia esto por tu usuario de DB
define('DB_PASSWORD', ''); // Cambia esto por tu password de DB
define('DB_NAME', 'contratacion_temporales'); // Cambia esto por el nombre de tu base de datos

// Crear conexión
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// 2. Obtener parámetros (o usar valores de prueba)
// Para el periodo de prueba y tipo_usuario
$anio_semestre = isset($_GET['periodo']) ? $conn->real_escape_string($_GET['periodo']) : '2025-2';
$tipo_usuario = isset($_GET['tipo_usuario']) ? (int)$_GET['tipo_usuario'] : 1;

// Estas variables son necesarias para tu consulta, aunque para tipo_usuario = 1 no se usen en el WHERE
$facultad_id = isset($_GET['facultad_id']) ? (int)$_GET['facultad_id'] : null;
$departamento_id = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : null;


// 3. Construir condición WHERE según tipo de usuario (tu lógica SQL)
$where = "WHERE solicitudes.anio_semestre = '" . $anio_semestre . "' ";
$where .= " AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)"; // Esta condición se repite en tus dos SELECT, la colocamos aquí

if ($tipo_usuario == 2) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id;
} elseif ($tipo_usuario == 3) {
    $where .= " AND facultad.PK_FAC = " . (int)$facultad_id .
              " AND deparmanentos.PK_DEPTO = " . (int)$departamento_id;
}

// 4. Definir la consulta SQL
$sql = "SELECT
    solicitudes.anio_semestre,
    facultad.NOMBREF_FAC,
    deparmanentos.NOMBRE_DEPTO_CORT,
    CASE
        WHEN solicitudes.sede = 'Popayán-Regionalización' THEN 'Popayán'
        ELSE solicitudes.sede
    END AS sede,
    solicitudes.cedula,
    solicitudes.nombre,
    solicitudes.tipo_docente,
    CASE
        WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN solicitudes.tipo_dedicacion
        WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN solicitudes.tipo_dedicacion_r
        WHEN solicitudes.tipo_docente = 'Catedra' THEN 'HRS'
    END AS dedicacion,
    CASE
        WHEN solicitudes.tipo_docente = 'Ocasional' AND (solicitudes.tipo_dedicacion = 'TC' OR solicitudes.tipo_dedicacion_r = 'TC') THEN 40
        WHEN solicitudes.tipo_docente = 'Ocasional' AND (solicitudes.tipo_dedicacion = 'MT' OR solicitudes.tipo_dedicacion_r = 'MT') THEN 20
        WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN solicitudes.horas
        WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN solicitudes.horas_r
        WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán-Regionalización' THEN solicitudes.horas
    END AS horas,
    CASE
        WHEN depto_periodo.dp_acepta_fac = 'aceptar' THEN 'Aceptado'
        WHEN depto_periodo.dp_acepta_fac = 'rechazar' THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_fac_status,
    CASE
        WHEN fac_periodo.fp_estado = 1 THEN 'Enviado'
        WHEN fac_periodo.fp_estado = 0  THEN 'No enviado'
        ELSE 'Pendiente'
    END AS envia_fac_status,
    CASE
        WHEN fac_periodo.fp_acepta_vra = 2 THEN 'Aceptado'
        WHEN fac_periodo.fp_acepta_vra = 1 THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_vra_status,
    facultad.PK_FAC,
    solicitudes.anexa_hv_docente_nuevo,
    solicitudes.actualiza_hv_antiguo,
    solicitudes.puntos

FROM
    solicitudes
JOIN
    deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
JOIN
    facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
LEFT JOIN
    depto_periodo ON depto_periodo.periodo = solicitudes.anio_semestre
                  AND depto_periodo.fk_depto_dp = solicitudes.departamento_id
LEFT JOIN
    fac_periodo ON fac_periodo.fp_periodo = solicitudes.anio_semestre
                 AND fac_periodo.fp_fk_fac = solicitudes.facultad_id
$where

UNION ALL

SELECT
    solicitudes.anio_semestre,
    facultad.NOMBREF_FAC,
    deparmanentos.NOMBRE_DEPTO_CORT,
    'Regionalización' AS sede,
    solicitudes.cedula,
    solicitudes.nombre,
    solicitudes.tipo_docente,
    'HRS' AS dedicacion,
    solicitudes.horas_r AS horas,
    CASE
        WHEN depto_periodo.dp_acepta_fac = 'aceptar' THEN 'Aceptado'
        WHEN depto_periodo.dp_acepta_fac = 'rechazar' THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_fac_status,
    CASE
        WHEN fac_periodo.fp_estado = 1 THEN 'Enviado'
        WHEN fac_periodo.fp_estado = 0  THEN 'No enviado'
        ELSE 'Pendiente'
    END AS envia_fac_status,
    CASE
        WHEN fac_periodo.fp_acepta_vra = 2 THEN 'Aceptado'
        WHEN fac_periodo.fp_acepta_vra = 1 THEN 'Rechazado'
        ELSE 'Pendiente'
    END AS acepta_vra_status,
    facultad.PK_FAC,
    solicitudes.anexa_hv_docente_nuevo,
    solicitudes.actualiza_hv_antiguo,
    solicitudes.puntos

FROM
    solicitudes
JOIN
    deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
JOIN
    facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
LEFT JOIN
    depto_periodo ON depto_periodo.periodo = solicitudes.anio_semestre
                  AND depto_periodo.fk_depto_dp = solicitudes.departamento_id
LEFT JOIN
    fac_periodo ON fac_periodo.fp_periodo = solicitudes.anio_semestre
                 AND fac_periodo.fp_fk_fac = solicitudes.facultad_id
$where
AND solicitudes.tipo_docente = 'Catedra'
AND solicitudes.horas > 0
AND solicitudes.horas_r > 0

ORDER BY
    anio_semestre, PK_FAC, NOMBRE_DEPTO_CORT, nombre ASC;";

// 5. Ejecutar la consulta
$result = $conn->query($sql);

$data = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    // Manejo de errores en caso de que la consulta falle
    // En un entorno de producción, evita mostrar errores detallados al usuario
    error_log("Error en la consulta SQL: " . $conn->error);
    // Podrías devolver un JSON con un mensaje de error si lo deseas
    // echo json_encode(['error' => 'Error al obtener los datos.']);
}

// 6. Cerrar conexión
$conn->close();

// 7. Devolver los resultados en formato JSON
header('Content-Type: application/json');
echo json_encode(['data' => $data]);
?>