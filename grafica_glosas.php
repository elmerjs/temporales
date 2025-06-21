<?php
// Conexión a la base de datos
$pdo = new PDO('mysql:host=localhost;dbname=contratacion_temporales', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener el periodo desde un formulario o parámetro
$anio_semestre = isset($_GET['periodo']) ? $_GET['periodo'] : '2025-2';

// Consulta SQL
$stmt = $pdo->prepare("
    SELECT 
        f.nombre_fac_min AS facultad,
        d.depto_nom_propio AS departamento,
        g.version_glosa AS version,
        g.Tipo_glosa AS tipo_observacion,
        SUM(g.cantidad_glosas) AS total_glosas
    FROM depto_periodo dp
    JOIN deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
    JOIN facultad f ON f.PK_FAC = d.FK_FAC
    LEFT JOIN glosas g ON g.fk_dp_glosa = dp.id_depto_periodo
    JOIN users u ON u.Id = g.fk_user
    WHERE dp.periodo = :anio_semestre
    GROUP BY 
        f.nombre_fac_min,
        d.depto_nom_propio,
        g.version_glosa,
        g.Tipo_glosa
    ORDER BY f.nombre_fac_min, d.depto_nom_propio
");
$stmt->execute([':anio_semestre' => $anio_semestre]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos para gráficas
$facultades = [];
$porFacultad = [];
$porVersion = [];
$porTipo = [];

foreach ($data as $row) {
    // Datos por facultad
    if (!isset($porFacultad[$row['facultad']])) {
        $porFacultad[$row['facultad']] = 0;
    }
    $porFacultad[$row['facultad']] += $row['total_glosas'];
    
    // Datos por versión
    if (!isset($porVersion[$row['version']])) {
        $porVersion[$row['version']] = 0;
    }
    $porVersion[$row['version']] += $row['total_glosas'];
    
    // Datos por tipo
    if (!isset($porTipo[$row['tipo_observacion']])) {
        $porTipo[$row['tipo_observacion']] = 0;
    }
    $porTipo[$row['tipo_observacion']] += $row['total_glosas'];
    
    // Datos detallados por facultad y departamento
    $facultades[$row['facultad']][$row['departamento']] = ($facultades[$row['facultad']][$row['departamento']] ?? 0) + $row['total_glosas'];
}

// Incluir biblioteca de gráficas (ejemplo con Chart.js)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Glosas - <?= htmlspecialchars($anio_semestre) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            width: 80%;
            margin: 20px auto;
        }
        .chart {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <h1>Reporte de Glosas - Periodo <?= htmlspecialchars($anio_semestre) ?></h1>
    
    <form method="get">
        <label for="periodo">Seleccione período:</label>
        <input type="text" id="periodo" name="periodo" value="<?= htmlspecialchars($anio_semestre) ?>" 
               pattern="\d{4}-[12]" title="Formato AAAA-S (ej: 2025-1 o 2025-2)">
        <button type="submit">Generar Reporte</button>
    </form>
    
    <!-- Gráfica por Facultad -->
    <div class="chart-container">
        <h2>Glosas por Facultad</h2>
        <canvas id="facultadChart" class="chart"></canvas>
    </div>
    
    <!-- Gráfica por Versión (llamado) -->
    <div class="chart-container">
        <h2>Glosas por Versión (Llamado)</h2>
        <canvas id="versionChart" class="chart"></canvas>
    </div>
    
    <!-- Gráfica por Tipo de Observación -->
    <div class="chart-container">
        <h2>Glosas por Tipo de Observación</h2>
        <canvas id="tipoChart" class="chart"></canvas>
    </div>
    
    <!-- Gráfica detallada por Facultad y Departamento -->
    <div class="chart-container">
        <h2>Detalle por Facultad y Departamento</h2>
        <canvas id="detalleChart" class="chart"></canvas>
    </div>
    
    <script>
        // Gráfica por Facultad
        new Chart(document.getElementById('facultadChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($porFacultad)) ?>,
                datasets: [{
                    label: 'Total Glosas',
                    data: <?= json_encode(array_values($porFacultad)) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
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
        
        // Gráfica por Versión
        new Chart(document.getElementById('versionChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($porVersion)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($porVersion)) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
        
        // Gráfica por Tipo de Observación
        new Chart(document.getElementById('tipoChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($porTipo)) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($porTipo)) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
        
        // Gráfica detallada por Facultad y Departamento
        <?php
        // Preparar datos para gráfica detallada
        $labels = [];
        $datasets = [];
        $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
        $colorIndex = 0;
        
        foreach ($facultades as $facultad => $departamentos) {
            $datasets[] = [
                'label' => $facultad,
                'data' => array_values($departamentos),
                'backgroundColor' => $colors[$colorIndex % count($colors)],
                'borderColor' => $colors[$colorIndex % count($colors)],
                'borderWidth' => 1
            ];
            $colorIndex++;
            
            // Solo necesitamos las etiquetas una vez (nombres de departamentos)
            if (empty($labels)) {
                $labels = array_keys($departamentos);
            }
        }
        ?>
        
        new Chart(document.getElementById('detalleChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: <?= json_encode($datasets) ?>
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>