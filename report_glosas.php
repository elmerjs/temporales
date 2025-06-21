<?php
require('include/headerz.php');
require 'vendor/autoload.php';
require 'funciones.php';
    
// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    echo "<div class='alert alert-warning text-center'>Debe <a href='index.html' class='alert-link'>iniciar sesión</a> para continuar</div>";
    exit();
}

$nombre_sesion = $_SESSION['name'];
$anio_semestre = isset($_POST['anio_semestre']) ? $_POST['anio_semestre'] : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : '2025-2');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Observaciones por Departamento - Universidad del Cauca</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    
    <style>
        :root {
            --unicauca-azul: #001282;
            --unicauca-rojo: #E52724;
            --unicauca-azul-claro: #16A8E1;
            --unicauca-verde: #249337;
            --unicauca-amarillo: #F8AE15;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .container-fluid {
            padding: 0 5%;
            
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 6px 10px rgba(0,0,0,0.08);
            border: none;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--unicauca-azul) 0%, #0047AB 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        
        .table thead th {
            background-color: var(--unicauca-azul);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(22, 168, 225, 0.1);
        }
        
        .badge-version {
            background-color: var(--unicauca-azul-claro);
            color: white;
        }
        
        .badge-glosa {
            background-color: var(--unicauca-verde);
            color: white;
            font-size: 0.9em;
            min-width: 40px;
        }
        
        .btn-unicauca {
            background-color: var(--unicauca-rojo);
            border-color: var(--unicauca-rojo);
            color: white;
            transition: all 0.3s;
        }
        
        .btn-unicauca:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-1px);
        }
        
        .btn-outline-unicauca {
            color: var(--unicauca-azul);
            border-color: var(--unicauca-azul);
        }
        
        .btn-outline-unicauca:hover {
            background-color: var(--unicauca-azul);
            color: white;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
            border: 1px solid #ddd;
            margin-left: 10px;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border-radius: 20px;
            padding: 5px 15px;
        }
        
        .periodo-badge {
            background-color: var(--unicauca-amarillo);
            color: #333;
            font-weight: 600;
        }
           /* Elimina el espacio superior completamente */
    .container-fluid {
        padding-top: 0 !important;
    }
    
    /* O ajusta solo el espacio sobre la card */
    .card:first-child {
        margin-top: 0.5rem !important;
    }
        #observacionSeleccionada {
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

#nombreObservacion {
    font-weight: normal;
    font-style: italic;
}
    </style>
</head>
    
    <?php
// Consulta SQL para obtener glosas por facultad
$sql_facultades = "SELECT 
                    dp.id_depto_periodo,
                    dp.periodo, 
                    f.nombre_fac_min, 
                    SUM(g.cantidad_glosas) AS glosas
                FROM depto_periodo dp
                JOIN deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
                JOIN facultad f ON f.PK_FAC = d.FK_FAC
                LEFT JOIN glosas g ON g.fk_dp_glosa = dp.id_depto_periodo
                WHERE dp.periodo = ?
                GROUP BY dp.periodo, f.nombre_fac_min
                ORDER BY f.nombre_fac_min";

$stmt_facultades = $conn->prepare($sql_facultades);
$periodo_actual = $anio_semestre; // Puedes hacer esto dinámico
$stmt_facultades->bind_param("s", $periodo_actual);
$stmt_facultades->execute();
$result_facultades = $stmt_facultades->get_result();

$facultades_data = [];
$total_glosas = 0;

while ($row = $result_facultades->fetch_assoc()) {
    $facultades_data[] = $row;
    $total_glosas += $row['glosas'];
}

// Calcular porcentajes
foreach ($facultades_data as &$facultad) {
    $facultad['porcentaje'] = $total_glosas > 0 ? round(($facultad['glosas'] / $total_glosas) * 100, 1) : 0;
}
unset($facultad); // Romper la referencia
?>
 
   
<body>
<div class="container-fluid py-2">
    <div class="card">
      <div class="card-header">
   <div class="d-flex justify-content-between align-items-center">
       <div>
        <h4 class="mb-0">
            <i class="fas fa-clipboard-list me-2"></i>Observaciones por Departamento
            <!-- Botón para mostrar/ocultar tabla -->
            <button class="btn btn-sm btn-link toggle-table" title="Mostrar/ocultar tabla">
                <i class="fas fa-chevron-down toggle-icon"></i>
            </button>
        </h4>
    </div>
    <div class="d-flex align-items-center">
        <span class="badge periodo-badge fs-6 py-2 px-3 me-3">
            <i class="fas fa-calendar-alt me-2"></i>Periodo: <?= htmlspecialchars($anio_semestre) ?>
        </span>
        
        <!-- Botón para exportar a Excel -->
   <button type="button" class="btn btn-success btn-sm me-3" data-bs-toggle="modal" data-bs-target="#exportExcelModal" title="Exportar reporte completo a Excel">
                    <i class="fas fa-file-excel me-1"></i> Xls Observaciones
                </button>
        
     <!-- Botón para abrir el modal -->
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#powerBIModal">
    <i class="fas fa-chart-bar me-1"></i> Gráficas Power BI
</button>
  <!--modal excel -->
        <style>/* Estilo para el título del modal de exportación a Excel - Intento con mayor especificidad */
.modal#exportExcelModal .modal-content .modal-header .modal-title {
    color: #002D72 !important; /* ¡Este debería funcionar! */
}</style>
<div class="modal fade" id="exportExcelModal" tabindex="-1" aria-labelledby="exportExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportExcelModalLabel">Generar Reporte Excel de Observaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="generar_reporte_excel_observaciones.php" method="GET">
                    <input type="hidden" name="periodo" value="<?= htmlspecialchars($anio_semestre) ?>">
                    
                    <div class="mb-3">
                        <label for="selectFacultad" class="form-label">Seleccionar Facultad:</label>
                        <select class="form-select" id="selectFacultad" name="facultad">
                            <option value="all">Todas las Facultades</option>
                            </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" form="exportForm" class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> Generar Excel
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="powerBIModal" tabindex="-1" aria-labelledby="powerBIModalLabel" aria-hidden="true" style="z-index: 9999;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="powerBIModalLabel">Reporte Power BI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe 
                    src="https://app.powerbi.com/view?r=eyJrIjoiNDg0ODNjODQtZGE3Mi00ZDMzLWFhMjUtM2FhMmMwZTNhMTI2IiwidCI6ImU4MjE0OTM3LTIzM2ItNGIzNi04NmJmLTBiNWYzMzM3YmVlMSIsImMiOjF9&pageName=282ee5a41849cca01e17" 
                    frameborder="0" 
                    allowfullscreen="true"
                    style="width: 100%; height: 75vh;"> <!-- 75vh = 75% del alto de la ventana -->
                </iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
</div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabla_glosas" class="table table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Facultad</th>
                            <th>Departamento</th>
                            <th class="text-center">Última Versión</th>
                            <th class="text-center">Total Observaciones</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
                                    dp.id_depto_periodo,
                                    dp.periodo, 
                                    f.nombre_fac_min, 
                                    d.depto_nom_propio,
                                    MAX(g.version_glosa) AS ultima_version,
                                    SUM(g.cantidad_glosas) AS glosas
                                FROM depto_periodo dp
                                JOIN deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
                                JOIN facultad f ON f.PK_FAC = d.FK_FAC
                                LEFT JOIN glosas g ON g.fk_dp_glosa = dp.id_depto_periodo
                                WHERE dp.periodo = ?
                                GROUP BY dp.id_depto_periodo, dp.periodo, f.nombre_fac_min, d.depto_nom_propio
                                ORDER BY f.nombre_fac_min, d.depto_nom_propio";
                                
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $anio_semestre);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nombre_fac_min']) ?></td>
                                <td><?= htmlspecialchars($row['depto_nom_propio']) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-version rounded-pill">
                                        <?= $row['ultima_version'] ? 'v'.htmlspecialchars($row['ultima_version']) : 'N/A' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success rounded-pill badge-glosa">
                                        <?= number_format($row['glosas'] ?: 0) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="agregar_glosas.php?fk_dp_glosa=<?= $row['id_depto_periodo'] ?>" 
                                       class="btn btn-sm btn-unicauca"
                                       title="Gestionar observaciones de este departamento">
                                        <i class="fas fa-edit me-1"></i> Gestionar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    <!-- Gráfico de barras agrupadas par atipo observacion -->

    <?php
// Conexión a la base de datos (usando tu configuración previa)
require 'cn.php';

    // **New Query: Total Observations**
    $sqle_total_observations = "SELECT SUM(glosas.cantidad_glosas) as grand_total
                                FROM glosas
                                JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
                                WHERE depto_periodo.periodo = '$anio_semestre'";
    $result_total_observations = mysqli_query($con, $sqle_total_observations);
    $total_observations_row = mysqli_fetch_assoc($result_total_observations);
    $grand_total_observations = $total_observations_row['grand_total'];
    // Consulta principal: sumatoria por Tipo_glosa
    $sqle_main = "SELECT
        glosas.Tipo_glosa,
        SUM(glosas.cantidad_glosas) as total
        FROM glosas
        JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
        WHERE depto_periodo.periodo = '$anio_semestre' 
        GROUP BY glosas.Tipo_glosa HAVING
        total > 0
        ORDER BY total DESC";
    $result_main = mysqli_query($con, $sqle_main);

    $labels = [];
    $data = [];
    $backgroundColors = [];
    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];

    
    
    while ($row = mysqli_fetch_assoc($result_main)) {
        $labels[] = $row['Tipo_glosa'];
        $data[] = $row['total'];
        $backgroundColors[] = $colors[count($labels) % count($colors)];
    }

    // Consulta de datos por versión y tipo
    $sqle_sub = "SELECT
        glosas.Tipo_glosa,
        glosas.version_glosa,
        SUM(glosas.cantidad_glosas) as total
        FROM glosas
        JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
        WHERE depto_periodo.periodo = '$anio_semestre'
        GROUP BY glosas.Tipo_glosa, glosas.version_glosa
        ORDER BY glosas.Tipo_glosa, glosas.version_glosa ASC";
    $result_sub = mysqli_query($con, $sqle_sub);

    $subChartData = [];
    $totalVersiones = [];

    while ($row_sub = mysqli_fetch_assoc($result_sub)) {
        $tipo_glosa = $row_sub['Tipo_glosa'];
        $version_glosa = $row_sub['version_glosa'];
        $total = $row_sub['total'];

        if (!isset($subChartData[$tipo_glosa])) {
            $subChartData[$tipo_glosa] = ['labels' => [], 'data' => []];
        }
        $subChartData[$tipo_glosa]['labels'][] = 'Versión ' . $version_glosa;
        $subChartData[$tipo_glosa]['data'][] = $total;

        if (!isset($totalVersiones[$version_glosa])) {
            $totalVersiones[$version_glosa] = 0;
        }
        $totalVersiones[$version_glosa] += $total;
    }

    // Consulta de datos por departamento y tipo
    $sqle_deptos = "SELECT
        glosas.Tipo_glosa,
        deparmanentos.NOMBRE_DEPTO_CORT,
        SUM(glosas.cantidad_glosas) as total
        FROM glosas
        JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
        WHERE depto_periodo.periodo = '$anio_semestre'
        GROUP BY glosas.Tipo_glosa, deparmanentos.NOMBRE_DEPTO_CORT
        HAVING total >0
        ORDER BY total DESC, glosas.Tipo_glosa";
    $result_deptos = mysqli_query($con, $sqle_deptos);

    $deptosChartData = [];
    $totalDeptos = [];

    while ($row_deptos = mysqli_fetch_assoc($result_deptos)) {
        $tipo_glosa = $row_deptos['Tipo_glosa'];
        $nombre_depto = $row_deptos['NOMBRE_DEPTO_CORT'];
        $total = $row_deptos['total'];

        if (!isset($deptosChartData[$tipo_glosa])) {
            $deptosChartData[$tipo_glosa] = ['labels' => [], 'data' => []];
        }
        $deptosChartData[$tipo_glosa]['labels'][] = $nombre_depto;
        $deptosChartData[$tipo_glosa]['data'][] = $total;

        if (!isset($totalDeptos[$nombre_depto])) {
            $totalDeptos[$nombre_depto] = 0;
        }
        $totalDeptos[$nombre_depto] += $total;
    }

    // Formatear datos totales por versión
    $totalVersionesChart = ['labels' => [], 'data' => []];
    foreach ($totalVersiones as $version => $total) {
        $totalVersionesChart['labels'][] = 'Versión ' . $version;
        $totalVersionesChart['data'][] = $total;
    }

    // Formatear datos totales por departamento
    $totalDeptosChart = ['labels' => [], 'data' => []];
    foreach ($totalDeptos as $depto => $total) {
        $totalDeptosChart['labels'][] = $depto;
        $totalDeptosChart['data'][] = $total;
    }




        // Consulta de datos por facultad y tipo
    $sqle_facultades = "SELECT
        glosas.Tipo_glosa,
        facultad.NOMBREC_FAC,
        SUM(glosas.cantidad_glosas) as total
        FROM glosas
        JOIN depto_periodo ON depto_periodo.id_depto_periodo = glosas.fk_dp_glosa
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
        JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
        WHERE depto_periodo.periodo = '$anio_semestre'
        GROUP BY glosas.Tipo_glosa, facultad.NOMBREC_FAC
        HAVING total >0
       ORDER BY total DESC
    ";
    $result_facultades = mysqli_query($con, $sqle_facultades);

    $facultadesChartData = [];
    $totalFacultades = [];

    while ($row_facultades = mysqli_fetch_assoc($result_facultades)) {
        $tipo_glosa = $row_facultades['Tipo_glosa'];
        $nombre_facultad = $row_facultades['NOMBREC_FAC'];
        $total = $row_facultades['total'];

        if (!isset($facultadesChartData[$tipo_glosa])) {
            $facultadesChartData[$tipo_glosa] = ['labels' => [], 'data' => []];
        }
        $facultadesChartData[$tipo_glosa]['labels'][] = $nombre_facultad;
        $facultadesChartData[$tipo_glosa]['data'][] = $total;

        if (!isset($totalFacultades[$nombre_facultad])) {
            $totalFacultades[$nombre_facultad] = 0;
        }
        $totalFacultades[$nombre_facultad] += $total;
    }

    // Calcular porcentajes por facultad
    $totalGeneral = array_sum($totalFacultades);
    $porcentajesFacultades = [];
    foreach ($totalFacultades as $facultad => $total) {
        $porcentaje = ($total / $totalGeneral) * 100;
        $porcentajesFacultades[$facultad] = number_format($porcentaje, 2) . '%';
    }

    // Formatear datos totales por facultad
    $totalFacultadesChart = ['labels' => [], 'data' => []];
    foreach ($totalFacultades as $facultad => $total) {
        $totalFacultadesChart['labels'][] = $facultad;
        $totalFacultadesChart['data'][] = $total;
    }

    
    ?>
    <div class="container-fluid mt-5 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Gestión de Observaciones - Periodo <?= $anio_semestre ?></h2>
            <div class="bg-light text-dark shadow-sm py-2 px-3 rounded-pill d-inline-flex align-items-center" style="min-width: 10rem; border: 1px solid #dadce0;">
                <div class="text-nowrap mr-2">
                    <small class="text-muted">Total Observaciones:</small>
                </div>
                <div class="h5 mb-0 font-weight-bold text-primary">
                    <?= $grand_total_observations ?>
                </div>
            </div>
        </div>
        <div id="observacionSeleccionada" class="alert alert-info py-1 mb-0" style="display: none;">
                <strong>Observación seleccionada:</strong> <span id="nombreObservacion"></span>
            </div>
        <div class="row">
            <!-- Columna Izquierda (Principal + Versiones) -->
            <div class="col-lg-7">
                <!-- Gráfico Principal (600px) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">Observaciones por Tipo</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 600px;">
                            <canvas id="horizontalBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Versiones (200px) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white" id="subChartTitle">Detalle por Versión</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 200px;">
                            <canvas id="subBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha (Facultades + Departamentos) -->
            <div class="col-lg-5">
                <!-- Facultades (400px) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white">Distribución por Facultad</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="facultadesBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Departamentos (400px) -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-white" id="deptosChartTitle">Detalle por Departamento</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="deptosBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Registrar el plugin
        Chart.register(ChartDataLabels);

        const ctx = document.getElementById('facultadesChart');
        const facultades = <?= json_encode(array_column($facultades_data, 'nombre_fac_min')) ?>;
        const glosas = <?= json_encode(array_column($facultades_data, 'glosas')) ?>;
        const porcentajes = <?= json_encode(array_column($facultades_data, 'porcentaje')) ?>;

        // Paleta de colores
        const backgroundColors = [
            'rgba(78, 115, 223, 0.8)',  // Azul
            'rgba(40, 167, 69, 0.8)',   // Verde
            'rgba(255, 193, 7, 0.8)',   // Amarillo
            'rgba(220, 53, 69, 0.8)',   // Rojo
            'rgba(23, 162, 184, 0.8)',  // Cyan
            'rgba(108, 117, 125, 0.8)', // Gris
            'rgba(111, 66, 193, 0.8)'   // Morado
        ].slice(0, facultades.length);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: facultades,
                datasets: [{
                    label: 'Observaciones por Facultad',
                    data: glosas,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(c => c.replace('0.8', '1')),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw} (${porcentajes[context.dataIndex]}%)`;
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: function(value, context) {
                            // Mostrar valor absoluto y porcentaje
                            return `${value}\n(${porcentajes[context.dataIndex]}%)`;
                        },
                        color: '#343a40',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        padding: {
                            top: 5
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Observaciones'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Facultades'
                        },
                        ticks: {
                            autoSkip: false
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    });
    </script>
    </div>

    <!-- jQuery, Bootstrap y DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#tabla_glosas').DataTable({
            dom: "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'B><'col-sm-12 col-md-4'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            responsive: true,
            order: [[0, 'asc'], [1, 'asc']],
            pageLength: 25,
            stateSave: true, // Guarda el estado (página, búsqueda, orden)
            stateDuration: -1, // Persistencia indefinida (usa localStorage)
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel me-2"></i>Excel',
                    className: 'btn btn-success'
                }
            ],
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: 1 },
                { responsivePriority: 3, targets: -1 }
            ]
        });
    });
    </script>
    <script>
        $(document).ready(function() {
            // Función para cargar las facultades en el select
            function cargarFacultades() {
                $.ajax({
                    url: 'obtener_facultades.php', // Este será el nuevo archivo PHP para obtener facultades
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        var select = $('#selectFacultad');
                        // select.empty(); // No es necesario si "Todas las Facultades" ya está
                        // select.append('<option value="all">Todas las Facultades</option>'); // Asegurar que siempre esté
                        $.each(data, function(index, facultad) {
                            select.append('<option value="' + facultad.PK_FAC + '">' + facultad.nombre_fac_min + '</option>');
                        });
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error al cargar facultades: " + textStatus, errorThrown);
                        // Opcional: mostrar un mensaje de error al usuario
                    }
                });
            }

            // Cargar facultades cuando el modal se abra por primera vez
            $('#exportExcelModal').on('show.bs.modal', function() {
                // Asegurarse de que las facultades se carguen solo una vez si no cambian
                if ($('#selectFacultad option').length <= 1) { // Si solo está "Todas las Facultades"
                    cargarFacultades();
                }
            });
        });
    </script>

    <!-- Script para el gráfico -->

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainChartLabels = <?= json_encode($labels) ?>;
        const mainChartData = <?= json_encode($data) ?>;
        const mainChartBackgroundColors = <?= json_encode($backgroundColors) ?>;
        const subChartDetailData = <?= json_encode($subChartData) ?>;
        const deptosChartDetailData = <?= json_encode($deptosChartData) ?>;
        const facultadesChartDetailData = <?= json_encode($facultadesChartData) ?>;
        const totalVersionesChart = <?= json_encode($totalVersionesChart) ?>;
        const totalDeptosChart = <?= json_encode($totalDeptosChart) ?>;
        const totalFacultadesChart = <?= json_encode($totalFacultadesChart) ?>;

        let subChartInstance = null;
        let deptosChartInstance = null;
        let facultadesChartInstance = null;

        const mainChartCanvas = document.getElementById('horizontalBarChart');
        const ctxMain = mainChartCanvas.getContext('2d');

        const mainChart = new Chart(ctxMain, {
            type: 'bar',
            data: {
                labels: mainChartLabels,
                datasets: [{
                    label: 'Cantidad de Observaciones',
                    data: mainChartData,
                    backgroundColor: mainChartBackgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                       callbacks: {
                    label: context => {
                        const label = context.label || '';
                        const value = context.parsed.x || 0;
                        mostrarObservacionSeleccionada(`${label} (${value} observaciones)`);
                        return `${value} observaciones`;
                    }
                },
                                    external: function(context) {
                            const tooltip = context.tooltip;
                            if (tooltip.opacity === 0) {
                                // Nada, se maneja con mouseout/click global
                            } else if (tooltip.dataPoints && tooltip.dataPoints.length > 0) {
                                const labelIndex = tooltip.dataPoints[0].dataIndex;
                                const hoveredLabel = mainChartLabels[labelIndex];
                                displaySubChart(hoveredLabel);
                                displayDeptosChart(hoveredLabel);
                                displayFacultadesChart(hoveredLabel);
                            }
                        }
                    },
                    datalabels: {
                        color: '#000000',
                        anchor: 'end',
                        align: 'right',
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        formatter: value => `${value}`
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Observaciones'
                        }
                    },
                    y: {
                        ticks: {
                            autoSkip: false,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
    // Función para mostrar la observación seleccionada
    let observacionActual = null;

    function mostrarObservacionSeleccionada(texto) {
        const contenedor = document.getElementById('observacionSeleccionada');
        const elemento = document.getElementById('nombreObservacion');

        if (texto) {
            observacionActual = texto; // Guarda la observación actual
            elemento.textContent = texto;
            contenedor.style.display = 'block';
        } else {
            observacionActual = null; // Limpia la observación actual
            contenedor.style.display = 'none';
        }
    }
        function displaySubChart(tipoGlosa) {
            const data = subChartDetailData[tipoGlosa];
            if (data) {
                updateSubChart(`Versiones - ${tipoGlosa}`, data.labels, data.data, '#17a2b8');
            }
        }

        function displayDeptosChart(tipoGlosa) {
            const data = deptosChartDetailData[tipoGlosa];
            if (data) {
                updateDeptosChart(`Departamentos - ${tipoGlosa}`, data.labels, data.data, '#6f42c1');
            }
        }

        function displayFacultadesChart(tipoGlosa) {
            const data = facultadesChartDetailData[tipoGlosa];
            if (data) {
                updateFacultadesChart(`Facultades - ${tipoGlosa}`, data.labels, data.data, '#ef6c99');
            }
        }

        function displayTotalVersiones() {
            updateSubChart('Totales por Versión', totalVersionesChart.labels, totalVersionesChart.data, '#17a2b8');
        }

        function displayTotalDeptos() {
            updateDeptosChart('Totales por Departamento', totalDeptosChart.labels, totalDeptosChart.data, '#6f42c1');
        }

        function displayTotalFacultades() {
            updateFacultadesChart('Totales por Facultad', totalFacultadesChart.labels, totalFacultadesChart.data, '#ef6c00');
        }

        function updateSubChart(title, labels, data, color) {
            document.getElementById('subChartTitle').textContent = title;
            const ctxSub = document.getElementById('subBarChart').getContext('2d');

            if (subChartInstance) subChartInstance.destroy();

            subChartInstance = new Chart(ctxSub, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: data,
                        backgroundColor: color,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.parsed.x} observaciones`
                            }
                        },
                        datalabels: {
                            color: '#000000',
                            anchor: 'end',
                            align: 'right',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: value => `${value}`
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Observaciones'
                            }
                        },
                        y: {
                            ticks: {
                                autoSkip: false,
                                font: { size: 10 }
                            }
                        }
                    }
                }
            });
        }

        function updateDeptosChart(title, labels, data, color) {
            document.getElementById('deptosChartTitle').textContent = title;
            const ctxDeptos = document.getElementById('deptosBarChart').getContext('2d');

            if (deptosChartInstance) deptosChartInstance.destroy();

            deptosChartInstance = new Chart(ctxDeptos, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: data,
                        backgroundColor: color,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.parsed.x} observaciones`
                            }
                        },
                        datalabels: {
                            color: '#000000',
                            anchor: 'end',
                            align: 'right',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            formatter: value => `${value}`
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Observaciones'
                            }
                        },
                        y: {
                            ticks: {
                                autoSkip: false,
                                font: { size: 10 }
                            }
                        }
                    }
                }
            });
        }

        function updateFacultadesChart(title, labels, data, color) {
            const ctxFacultades = document.getElementById('facultadesBarChart').getContext('2d');

            if (facultadesChartInstance) facultadesChartInstance.destroy();

            facultadesChartInstance = new Chart(ctxFacultades, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: data,
                        backgroundColor: color,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: context => `${context.parsed.x} observaciones`
                            }
                        },
                        datalabels: {
                            color: '#000000',
                            anchor: 'end',
                            align: 'right',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            formatter: value => `${value}`
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Observaciones'
                            }
                        },
                        y: {
                            ticks: {
                                autoSkip: false,
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        }

        // Mostrar totales al cargar
        displayTotalVersiones();
        displayTotalDeptos();
        displayTotalFacultades();

        // Cuando el mouse sale del canvas
        mainChartCanvas.addEventListener('mouseleave', () => {
            setTimeout(() => {
                const tooltipActive = mainChart.tooltip && mainChart.tooltip.dataPoints && mainChart.tooltip.dataPoints.length > 0;
                if (!tooltipActive) {
                    displayTotalVersiones();
                    displayTotalDeptos();
                    displayTotalFacultades();
                }
            }, 100);
        });

        // Cuando haces clic fuera del gráfico principal
        document.addEventListener('click', function (e) {
            if (!mainChartCanvas.contains(e.target)) {
                displayTotalVersiones();
                displayTotalDeptos();
                displayTotalFacultades();
            }
        });
        // Limpiar selección al hacer clic fuera
    document.addEventListener('click', function(e) {
        // Verifica si el clic fue fuera de todos los gráficos y del contenedor de selección
        if (!e.target.closest('.card') && !e.target.closest('#observacionSeleccionada')) {
            mostrarObservacionSeleccionada(null);

            // También restablece los gráficos secundarios si es necesario
            displayTotalVersiones();
            displayTotalDeptos();
            displayTotalFacultades();
        }
    });

    // Opcional: Limpiar también al hacer clic en el botón de cerrar (si añades uno)
    document.querySelector('#observacionSeleccionada .close')?.addEventListener('click', function() {
        mostrarObservacionSeleccionada(null);
    });
    });
    </script>
        <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.querySelector('.toggle-table');
        const toggleIcon = document.querySelector('.toggle-icon');
        const tableContainer = document.querySelector('.table-responsive');

        // Opcional: Iniciar con la tabla visible/oculta
        // tableContainer.style.display = 'none'; // Para iniciar oculta

        toggleBtn.addEventListener('click', function() {
            if (tableContainer.style.display === 'none') {
                tableContainer.style.display = 'block';
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            } else {
                tableContainer.style.display = 'none';
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            }
        });
    });
    </script>
    </body>
    </html>
<?php
$conn->close();
?>

