<?php
// Include your database connection and common functions
include 'cn.php'; // Adjust path as needed

header('Content-Type: application/json');

$filterBy = $_GET['filter_by'] ?? '';
$facultyName = $_GET['faculty_name'] ?? '';
$anio_semestre = $_GET['periodo'] ?? ''; // Ensure this matches your session variable or input

$response = [
    'mainChart' => ['labels' => [], 'data' => []],
    'totalVersiones' => ['labels' => [], 'data' => []],
    'totalDeptos' => ['labels' => [], 'data' => []],
    'totalObservationsFaculty' => 0 // Sum for the selected faculty
];

if ($filterBy === 'faculty' && !empty($facultyName) && !empty($anio_semestre)) {
    // 1. Get PK_FAC for the given faculty name
    $stmt_fac_id = $con->prepare("SELECT PK_FAC FROM facultad WHERE NOMBREC_FAC = ?");
    $stmt_fac_id->bind_param("s", $facultyName);
    $stmt_fac_id->execute();
    $result_fac_id = $stmt_fac_id->get_result();
    $row_fac_id = $result_fac_id->fetch_assoc();
    $pk_fac = $row_fac_id['PK_FAC'] ?? null;
    $stmt_fac_id->close();

    if ($pk_fac) {
        // Total observations for the selected faculty
        $sqle_total_faculty = "SELECT SUM(glosas.cantidad_glosas) as grand_total
                               FROM glosas
                               JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
                               JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
                               WHERE depto_periodo.periodo = ? AND deparmanentos.FK_FAC = ?";
        $stmt_total_faculty = $con->prepare($sqle_total_faculty);
        $stmt_total_faculty->bind_param("si", $anio_semestre, $pk_fac);
        $stmt_total_faculty->execute();
        $result_total_faculty = $stmt_total_faculty->get_result();
        $total_faculty_row = $result_total_faculty->fetch_assoc();
        $response['totalObservationsFaculty'] = $total_faculty_row['grand_total'] ?? 0;
        $stmt_total_faculty->close();


        // Main chart data (Tipo_glosa) filtered by faculty
        $sqle_main_filtered = "SELECT
                                glosas.Tipo_glosa,
                                SUM(glosas.cantidad_glosas) as total
                                FROM glosas
                                JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
                                JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
                                WHERE depto_periodo.periodo = ? AND deparmanentos.FK_FAC = ?
                                GROUP BY glosas.Tipo_glosa
                                HAVING total > 0
                                ORDER BY total DESC";
        $stmt_main_filtered = $con->prepare($sqle_main_filtered);
        $stmt_main_filtered->bind_param("si", $anio_semestre, $pk_fac);
        $stmt_main_filtered->execute();
        $result_main_filtered = $stmt_main_filtered->get_result();
        while ($row = $result_main_filtered->fetch_assoc()) {
            $response['mainChart']['labels'][] = $row['Tipo_glosa'];
            $response['mainChart']['data'][] = $row['total'];
        }
        $stmt_main_filtered->close();

        // Total versions data filtered by faculty
        $sqle_total_versions_filtered = "SELECT
                                        glosas.version_glosa,
                                        SUM(glosas.cantidad_glosas) as total
                                        FROM glosas
                                        JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
                                        JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
                                        WHERE depto_periodo.periodo = ? AND deparmanentos.FK_FAC = ?
                                        GROUP BY glosas.version_glosa
                                        ORDER BY glosas.version_glosa ASC";
        $stmt_total_versions_filtered = $con->prepare($sqle_total_versions_filtered);
        $stmt_total_versions_filtered->bind_param("si", $anio_semestre, $pk_fac);
        $stmt_total_versions_filtered->execute();
        $result_total_versions_filtered = $stmt_total_versions_filtered->get_result();
        while ($row = $result_total_versions_filtered->fetch_assoc()) {
            $response['totalVersiones']['labels'][] = 'Versión ' . $row['version_glosa'];
            $response['totalVersiones']['data'][] = $row['total'];
        }
        $stmt_total_versions_filtered->close();

        // Total departments data filtered by faculty
        $sqle_total_deptos_filtered = "SELECT
                                        deparmanentos.NOMBRE_DEPTO_CORT,
                                        SUM(glosas.cantidad_glosas) as total
                                        FROM glosas
                                        JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
                                        JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
                                        WHERE depto_periodo.periodo = ? AND deparmanentos.FK_FAC = ?
                                        GROUP BY deparmanentos.NOMBRE_DEPTO_CORT
                                        HAVING total > 0
                                        ORDER BY total DESC";
        $stmt_total_deptos_filtered = $con->prepare($sqle_total_deptos_filtered);
        $stmt_total_deptos_filtered->bind_param("si", $anio_semestre, $pk_fac);
        $stmt_total_deptos_filtered->execute();
        $result_total_deptos_filtered = $stmt_total_deptos_filtered->get_result();
        while ($row = $result_total_deptos_filtered->fetch_assoc()) {
            $response['totalDeptos']['labels'][] = $row['NOMBRE_DEPTO_CORT'];
            $response['totalDeptos']['data'][] = $row['total'];
        }
        $stmt_total_deptos_filtered->close();
    }
}

echo json_encode($response);

// Close connection if it was opened here, otherwise it's handled by your db_connection.php
// $con->close();
?>