<?php
require 'vendor/autoload.php'; // Incluye la configuración necesaria para PHPWord u otras librerías

// Conexión a la base de datos (ajusta los parámetros según tu configuración)
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

//$periodo = '2025-1'; // Ejemplo de periodo seleccionado
$facultad_id = isset($_POST['facultad_id']) ? intval($_POST['facultad_id']) : 0;
$periodo = isset($_POST['anio_semestre']) ? ($_POST['anio_semestre']) : '2025-1';

// Condición para la facultad
$facultad_condicion = $facultad_id != 0 ? "AND deparmanentos.FK_FAC = '$facultad_id' " : "";

// Obtener datos para el histograma por tipo de docente (recuento de id_solicitud por tipo_docente)
$sql_histograma_tipo_docente = "SELECT solicitudes.tipo_docente, COUNT(solicitudes.id_solicitud) AS total
                                FROM solicitudes JOIN deparmanentos ON
                                 solicitudes.departamento_id = deparmanentos.PK_DEPTO
                                WHERE 
                                solicitudes.anio_semestre = '$periodo' 
                                $facultad_condicion
                                GROUP BY solicitudes.tipo_docente";
$result_histograma_tipo_docente = $conn->query($sql_histograma_tipo_docente);
$data_histograma_tipo_docente = [];
while ($row = $result_histograma_tipo_docente->fetch_assoc()) {
    $data_histograma_tipo_docente[] = $row;
}

// Obtener datos para el histograma por departamento (recuento de id_solicitud por deparmanentos.NOMBRE_DEPTO_CORT)
$sql_histograma_departamentos = "SELECT deparmanentos.NOMBRE_DEPTO_CORT, COUNT(solicitudes.id_solicitud) AS total
                                 FROM solicitudes
                                 JOIN deparmanentos ON
                                 solicitudes.departamento_id = deparmanentos.PK_DEPTO
                                 WHERE 
                                 solicitudes.anio_semestre = '$periodo' 
                                 $facultad_condicion
                                 GROUP BY deparmanentos.NOMBRE_DEPTO_CORT";
$result_histograma_departamentos = $conn->query($sql_histograma_departamentos);
$data_histograma_departamentos = [];
while ($row = $result_histograma_departamentos->fetch_assoc()) {
    $data_histograma_departamentos[] = $row;
}

// Obtener datos para histogramas adicionales
$sql_histograma_ocasional = "SELECT 
                                SUM(CASE WHEN tipo_dedicacion = 'TC' THEN 1 ELSE 0 END) AS popayan_tc,
                                SUM(CASE WHEN tipo_dedicacion = 'MT' THEN 1 ELSE 0 END) AS popayan_mt,
                                SUM(CASE WHEN tipo_dedicacion_r = 'TC' THEN 1 ELSE 0 END) AS regional_tc,
                                SUM(CASE WHEN tipo_dedicacion_r = 'MT' THEN 1 ELSE 0 END) AS regional_mt
                            FROM solicitudes join  deparmanentos on deparmanentos.PK_DEPTO= solicitudes.departamento_id
                            WHERE tipo_docente = 'Ocasional' AND anio_semestre = '$periodo' $facultad_condicion";

$result_histograma_ocasional = $conn->query($sql_histograma_ocasional);
$data_histograma_ocasional = $result_histograma_ocasional->fetch_assoc();

$sql_histograma_catedra = "SELECT 
                                SUM(CASE WHEN horas BETWEEN 7 AND 12 THEN 1 ELSE 0 END) AS popayan_7_12,
                                SUM(CASE WHEN horas BETWEEN 1 AND 6 THEN 1 ELSE 0 END) AS popayan_1_6,
                                SUM(CASE WHEN horas_r BETWEEN 7 AND 12 THEN 1 ELSE 0 END) AS regional_7_12,
                                SUM(CASE WHEN horas_r BETWEEN 1 AND 6 THEN 1 ELSE 0 END) AS regional_1_6
                            FROM solicitudes join  deparmanentos on deparmanentos.PK_DEPTO= solicitudes.departamento_id
                            WHERE tipo_docente = 'Catedra' AND anio_semestre = '$periodo' $facultad_condicion";
$result_histograma_catedra = $conn->query($sql_histograma_catedra);
$data_histograma_catedra = $result_histograma_catedra->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gráficas de Solicitudes</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h2>Gráficas de Solicitudes</h2>

        <div class="row">
            <div class="col-md-6">
                <h3>Histograma de Solicitudes por Tipo de Docente</h3>
                <canvas id="histogramaTipoDocenteChart"></canvas>
            </div>
            <div class="col-md-6">
                <h3>Histograma de Solicitudes por Departamento</h3>
                <canvas id="histogramaDepartamentosChart"></canvas>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3>Histograma de Ocasional por Tipo de Dedicación</h3>
                <canvas id="ocasionalChart"></canvas>
            </div>
            <div class="col-md-6">
                <h3>Histograma de Cátedra por Rango de Horas</h3>
                <canvas id="catedraChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Datos para el histograma por Tipo de Docente
        var histogramaTipoDocenteData = <?php echo json_encode($data_histograma_tipo_docente); ?>;
        var labelsTipoDocente = [];
        var dataTipoDocente = [];

        histogramaTipoDocenteData.forEach(function(item) {
            labelsTipoDocente.push(item.tipo_docente);
            dataTipoDocente.push(item.total);
        });

        var ctxHistogramaTipoDocente = document.getElementById('histogramaTipoDocenteChart').getContext('2d');
        var histogramaTipoDocenteChart = new Chart(ctxHistogramaTipoDocente, {
            type: 'bar',
            data: {
                labels: labelsTipoDocente,
                datasets: [{
                    label: 'Cantidad de Solicitudes',
                    data: dataTipoDocente,
                    backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)'],
                    borderColor: ['rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Datos para el histograma por Departamentos
        var histogramaDepartamentosData = <?php echo json_encode($data_histograma_departamentos); ?>;
        var labelsDepartamentos = [];
        var dataDepartamentos = [];

        histogramaDepartamentosData.forEach(function(item) {
            labelsDepartamentos.push(item.NOMBRE_DEPTO_CORT);
            dataDepartamentos.push(item.total);
        });

        var ctxHistogramaDepartamentos = document.getElementById('histogramaDepartamentosChart').getContext('2d');
        var histogramaDepartamentosChart = new Chart(ctxHistogramaDepartamentos, {
            type: 'bar',
            data: {
                labels: labelsDepartamentos,
                datasets: [{
                    label: 'Cantidad de Solicitudes',
                    data: dataDepartamentos,
                    backgroundColor: 'rgba(255, 159, 64, 0.6)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Datos para el histograma de Ocasional
        var ocasionalData = <?php echo json_encode($data_histograma_ocasional); ?>;
        var ctxOcasional = document.getElementById('ocasionalChart').getContext('2d');
        var ocasionalChart = new Chart(ctxOcasional, {
            type: 'bar',
            data: {
                labels: ['Popayán TC', 'Popayán MT', 'Regional TC', 'Regional MT'],
                datasets: [{
                    label: 'Cantidad',
                    data: [
                        ocasionalData.popayan_tc,
                        ocasionalData.popayan_mt,
                        ocasionalData.regional_tc,
                        ocasionalData.regional_mt
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Datos para el histograma de Cátedra
        var catedraData = <?php echo json_encode($data_histograma_catedra); ?>;
        var ctxCatedra = document.getElementById('catedraChart').getContext('2d');
        var catedraChart = new Chart(ctxCatedra, {
            type: 'bar',
            data: {
                labels: ['Popayán 7-12 horas', 'Popayán 1-6 horas', 'Regional 7-12 horas', 'Regional 1-6 horas'],
                datasets: [{
                    label: 'Cantidad',
                    data: [
                        catedraData.popayan_7_12,
                        catedraData.popayan_1_6,
                        catedraData.regional_7_12,
                        catedraData.regional_1_6
                    ],
                    backgroundColor: 'rgba(255, 206, 86, 0.6)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
