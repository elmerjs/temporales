
<?php
require('include/headerz.php');

require 'vendor/autoload.php'; // Incluye la configuración necesaria para PHPWord u otras librerías

// Conexión a la base de datos (ajusta los parámetros según tu configuración)
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si se recibió el parámetro departamento_id
if (!isset($_GET['departamento_id'])) {
    die("Error: Falta el parámetro departamento_id");
}
if (!isset($_GET['anio_semestre'])) {
    die("Error: Falta el parámetro anio_semestre");
}
// Obtener el departamento_id desde la URL
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];

// Consulta SQL para obtener los datos
$sql = "SELECT 
            depto_periodo.fk_depto_dp, 
            deparmanentos.depto_nom_propio AS nombre_departamento,
            SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_ocasional_popayan,
            SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_regionalizacion,
            depto_periodo.dp_estado_ocasional,
            SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_catedra_popayan,
            SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_catedra_regionalizacion,
            depto_periodo.dp_estado_catedra
        FROM 
            depto_periodo
         join
            solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
         join 
            deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
        WHERE 
            fk_depto_dp = $departamento_id and depto_periodo.periodo = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
        GROUP BY 
            depto_periodo.fk_depto_dp, deparmanentos.depto_nom_propio";

$result = $conn->query($sql);
//echo "consulta". $sql;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Totales por Tipo de Docente y Sede</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container"><br>
        <h2>Consulta de Totales por Tipo de Docente y Sede</h2>
        <div class="row">
            <div class="col-md-12">
                <?php
                    if ($result->num_rows > 0) {
                        // Mostrar el nombre del departamento
                        $row = $result->fetch_assoc();
                        echo "<h3>Departamento: " . htmlspecialchars($row['nombre_departamento']) . "</h3>";
                } else {
                    echo "<p>No se encontraron resultados.</p>";
                }
                ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Popayán</th>
                            <th>Regionalización</th>
                            <th>Estado</th> <!-- Nueva columna para el estado -->
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ocasional</td>
                            <?php
                            if ($result->num_rows > 0) {
                                // Determinar el icono según el estado para Ocasional
                                $estado_ocasional = ($row['dp_estado_ocasional'] == 'ce') ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>';
// Mostrar el icono dentro de un enlace a index.php con departamento_id
echo "<td>" . htmlspecialchars($row['total_ocasional_popayan']) . "</td>";
echo "<td>" . htmlspecialchars($row['total_ocasional_regionalizacion']) . "</td>";
echo '<td><a href="indexsolicitud.php?departamento_id=' . urlencode($departamento_id) . '">' . $estado_ocasional . '</a></td>';
                            }
                            ?>
                        </tr>
                        <tr>
                            <td>Cátedra</td>
                            <?php
                            if ($result->num_rows > 0) {
                                // Determinar el icono según el estado para Cátedra
                                $icono_catedra = ($row['dp_estado_catedra'] == 'ce') ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>';
                                // Mostrar el icono dentro de un enlace a index.php con departamento_id
echo "<td>" . htmlspecialchars($row['total_catedra_popayan']) . "</td>";
echo "<td>" . htmlspecialchars($row['total_catedra_regionalizacion']) . "</td>";
echo '<td><a href="indexsolicitud.php?departamento_id=' . urlencode($departamento_id) . '">' . $icono_catedra . '</a></td>';


                            }
                            ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
     <div class="row mt-3">
            <div class="col-md-12 text-center">
                <?php
                if ($result->num_rows > 0) {
    echo '<a href="oficio_depto.php?departamento_id=' . urlencode($departamento_id) . '&anio_semestre=' . urlencode($anio_semestre) . '" class="btn btn-primary">Enviar a Facultad</a>';
}
                ?>
            </div>
        </div>
</body>
</html>

<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>
