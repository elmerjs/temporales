
<?php
//require('include/headerz.php');

require 'funciones.php';

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
session_start();

if (isset($_SESSION['errores']) && count($_SESSION['errores']) > 0) {
    $errores = implode("\\n", $_SESSION['errores']);
    echo "<script>alert('$errores');</script>";
    unset($_SESSION['errores']);
}

if (isset($_SESSION['mensaje_exito'])) {
    $mensaje = $_SESSION['mensaje_exito'];
    echo "<script>alert('$mensaje');</script>";
    unset($_SESSION['mensaje_exito']);
}

$currentYear = date("Y");
?>
<?php
require 'cn.php';
if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];
} else {
    $nombre_sesion = "elmer jurado";
}
//

$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $conn->query($consultaf);
while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_fac = $row['email_padre'];
    $pk_fac = $row['fk_fac_user'];
    $email_dp = $row['Email'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user= $row['fk_depto_user'];
      $id_user= $row['Id'];
  

   
}
// Obtener el ID del departamento-periodo con validación
$fk_dp_glosa = isset($_GET['fk_dp_glosa']) ? intval($_GET['fk_dp_glosa']) : 0;

// Verificar que el departamento-periodo existe
$sql_check = "SELECT dp.*, d.depto_nom_propio, f.nombre_fac_min 
              FROM depto_periodo dp
              JOIN deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
              JOIN facultad f ON f.PK_FAC = d.FK_FAC
              WHERE dp.id_depto_periodo = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $fk_dp_glosa);
$stmt_check->execute();
$depto_info = $stmt_check->get_result()->fetch_assoc();

if (!$depto_info) {
    die("<div class='alert alert-danger'>Error: El departamento-periodo especificado no existe.</div>");
}

// Obtener la última versión de glosas para este departamento
$sql_version = "SELECT MAX(version_glosa) as max_version FROM glosas WHERE fk_dp_glosa = ?";
$stmt_version = $conn->prepare($sql_version);
$stmt_version->bind_param("i", $fk_dp_glosa);
$stmt_version->execute();
$result_version = $stmt_version->get_result();
$row_version = $result_version->fetch_assoc();
$ultima_version = $row_version['max_version'] ? $row_version['max_version'] : 0;
$nueva_version = $ultima_version + 1;

// Tipos de glosas disponibles
$tipos_glosas = [
    'Acto Administrativo errado',
    'Actualizar año de capacitación',
    'Actualizar o depurar proyectos de Investigación',
    'Ajustar al total de horas del parámetro',
    'Diferencia de horas de contratación o tipo de vinculación',
    'Profesor No solicitado',
    'Registro de la actividad con error',
    'Número de semanas del periodo con error',
    'Número de semanas proyectos de Extensión con error',
    'Falta Plan de capacitación',
    'Inconsistencias Matriculas Académica',
    'No aplica la observación',
    'No cumple el total de Docencia Directa',
    'Número de horas Plan de Estudio erradas',
    'Sin visado por jefe y/o decano',
    'Supera el Factor Multiplicador',
    'Trabajo de grado finalizado o ajustar semanas',
    'Mayor número de horas de planeación y evaluación curricular',
    'Registro entre labor y oferta errado',
    'Registro labor pendiente',
    'No postulados en banco de aspirantes',
    'Otra'
];
// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['glosas'])) {
    $errores = [];
    
    $glosas_validas = 0; // Contador de glosas con cantidad > 0
    
    // Validar oficio
    $numero_oficio = trim($_POST['numero_oficio'] ?? '');
    if (empty($numero_oficio) || $numero_oficio === "4-31/") {
        $errores[] = "Debe ingresar un número de oficio válido.";
    }
    
    // Validar al menos una glosa > 0
    foreach ($tipos_glosas as $tipo) {
        if (($_POST['glosas'][$tipo] ?? 0) > 0) {
            $glosas_validas++;
        }
    }
    
    if ($glosas_validas === 0) {
        $errores[] = "Debe ingresar al menos una observación con cantidad mayor a cero.";
    }
    
    // Si hay errores, mostrar y no procesar
    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        header("Location: agregar_glosas.php?fk_dp_glosa=$fk_dp_glosa");
        exit();
    }
    
    // Procesar si no hay errores
    $conn->begin_transaction();
    try {
        // Insertar oficio
        $sql_oficio = "INSERT INTO oficios_glosas 
                      (fk_dp_glosa, version_glosa, numero_oficio, fecha_oficio, descripcion) 
                      VALUES (?, ?, ?, ?, ?)";
        $stmt_oficio = $conn->prepare($sql_oficio);
        $stmt_oficio->bind_param("iisss", $fk_dp_glosa, $nueva_version, $numero_oficio, $_POST['fecha_oficio'], $_POST['detalle_oficio']);
        $stmt_oficio->execute();
        $stmt_oficio->close();
        
        // Insertar glosas (solo las > 0)
        foreach ($tipos_glosas as $tipo) {
            $cantidad = max(0, intval($_POST['glosas'][$tipo] ?? 0)); // Asegurar número positivo
            
            $sql = "INSERT INTO glosas 
                   (version_glosa, Tipo_glosa, cantidad_glosas, fk_dp_glosa, fk_user) 
                   VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiii", $nueva_version, $tipo, $cantidad, $fk_dp_glosa, $id_user);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        $_SESSION['mensaje_exito'] = "Se han guardado $glosas_validas categorías  (Versión $nueva_version)";
        header("Location: agregar_glosas.php?fk_dp_glosa=$fk_dp_glosa");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['errores'] = ["Error al guardar: " . $e->getMessage()];
        header("Location: agregar_glosas.php?fk_dp_glosa=$fk_dp_glosa");
        exit();
    }
}

// Obtener glosas existentes para este departamento (agrupadas por versión)
$glosas_existentes = [];
$sql_glosas = "SELECT version_glosa, Tipo_glosa, SUM(cantidad_glosas) as cantidad 
               FROM glosas 
               WHERE fk_dp_glosa = ? AND cantidad_glosas > 0
               GROUP BY version_glosa, Tipo_glosa
               ORDER BY version_glosa DESC, Tipo_glosa";
$stmt_glosas = $conn->prepare($sql_glosas);
$stmt_glosas->bind_param("i", $fk_dp_glosa);
$stmt_glosas->execute();
$result_glosas = $stmt_glosas->get_result();
while ($row = $result_glosas->fetch_assoc()) {
    $glosas_existentes[$row['version_glosa']][] = $row;
}

// Consulta específica para evolución de versiones
$sql_evolucion = "SELECT SUM(cantidad_glosas) as total_glosas, version_glosa 
                  FROM glosas 
                  WHERE fk_dp_glosa = ?
                  GROUP BY version_glosa
                  ORDER BY version_glosa ASC";
$stmt_evolucion = $conn->prepare($sql_evolucion);
$stmt_evolucion->bind_param("i", $fk_dp_glosa);
$stmt_evolucion->execute();
$result_evolucion = $stmt_evolucion->get_result();

$versiones_evolucion = [];
while ($row = $result_evolucion->fetch_assoc()) {
    $versiones_evolucion[$row['version_glosa']] = $row['total_glosas'];
}

// Calcular variaciones
$variaciones = [];
$versiones_ordenadas = array_keys($versiones_evolucion);
sort($versiones_ordenadas); // Asegurar orden numérico correcto

foreach ($versiones_ordenadas as $index => $version) {
    if ($index === 0) {
        $variaciones[$version] = null; // No hay variación para la primera versión
    } else {
        $anterior = $versiones_evolucion[$versiones_ordenadas[$index-1]];
        $actual = $versiones_evolucion[$version];
        
        // Validar si $anterior es cero para evitar DivisionByZeroError
        if ($anterior != 0) {
            $variacion = (($actual - $anterior) / $anterior) * 100;
            $variaciones[$version] = round($variacion, 1);
        } else {
            // Manejar casos donde $anterior = 0:
            $variaciones[$version] = ($actual != 0) ? '∞' : 0; // ∞ (infinito) o 0% si ambos son cero
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Observaciones - Universidad del Cauca</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        
         /* Estilos para las tablas de dos columnas */
    .glosa-item {
        border-left: 3px solid #16A8E1;
    }
    
    .glosa-item:hover {
        background-color: rgba(22, 168, 225, 0.05);
    }
    
    .glosa-item td {
        padding: 0.25rem 0.5rem;
        vertical-align: middle;
    }
    
    .cantidad-input {
        width: 70px;
        text-align: center;
        padding: 0.25rem;
        height: calc(1.5em + 0.5rem);
    }
    
    /* Asegurar que ambas columnas tengan la misma altura */
    .row {
        display: flex;
        flex-wrap: wrap;
    }
    
    .col-md-6 {
        display: flex;
        flex-direction: column;
    }
    
    .table {
        margin-bottom: 0;
        flex-grow: 1;
    }
        :root {
            --unicauca-azul: #002A9E;
            --unicauca-rojo: #E52724;
            --unicauca-azul-claro: #16A8E1;
            --unicauca-verde: #249337;
            --unicauca-amarillo: #F8AE15;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--unicauca-azul) 0%, #0047AB 100%);
            color: white;
        }
        
        .badge-version {
            background-color: var(--unicauca-azul);
            color: white;
            font-size: 1rem;
        }
        
        .glosa-item {
            border-left: 4px solid var(--unicauca-azul-claro);
            transition: all 0.3s;
        }
        
        .glosa-item:hover {
            background-color: rgba(22, 168, 225, 0.05);
        }
        
        .cantidad-input {
            max-width: 100px;
            text-align: center;
        }
        
        .version-section {
            border-bottom: 2px solid var(--unicauca-azul);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn-unicauca {
            background-color: var(--unicauca-rojo);
            border-color: var(--unicauca-rojo);
            color: white;
        }
        
        .btn-unicauca:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .modal-page {
    /* ... otros estilos ... */
    max-width: 80%; /* O incluso 85% o 90% si necesitas más espacio */
    /* width: auto; /* Puedes mantener esto si no quieres un ancho fijo */
}
    </style>
</head>
<body>
<div class="container py-0">
    <div class="card shadow-lg">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Agregar Observaciones</h4>
                    <p class="mb-0 mt-2">
                        <strong>Departamento:</strong> <?= htmlspecialchars($depto_info['depto_nom_propio']) ?>
                        <span class="mx-2">|</span>
                        <strong>Facultad:</strong> <?= htmlspecialchars($depto_info['nombre_fac_min']) ?>
                    </p>
                </div>
                <span class="badge bg-light text-dark fs-6">
                    <i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($depto_info['periodo']) ?>
                </span>
            </div>
        </div>
        
     <div class="card-body">
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['mensaje_exito'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="version-section">
 <h5 class="d-flex align-items-center flex-wrap gap-3">
    <span class="badge badge-version">Versión <?= $nueva_version ?></span>
    <span>
        <?php
        switch($nueva_version) {
            case 1: echo "Primeras Observaciones"; break;
            case 2: echo "Segundas Observaciones"; break;
            case 3: echo "Terceras Observaciones"; break;
            case 4: echo "Cuartas Observaciones"; break;
            case 5: echo "Quintas Observaciones"; break;
            case 6:
        echo "Sextas Observaciones"; // Changed from Quintas
        break;
    case 7:
        echo "Séptimas Observaciones"; // Changed from Quintas
        break;
    case 8:
        echo "Octavas Observaciones";  // Changed from Quintas
        break;
    case 9:
        echo "Novena Observaciones";   // Added for completeness
        break;
    case 10:
        echo "Décimas Observaciones";  // Changed from Quintas
        break;
    case 11:
        echo "Onceavas Observaciones"; // Changed from Quintas
        break;
    case 12:
        echo "Doceavas Observaciones"; // Changed from Quintas
        break;
    case 13:
        echo "Treceavas Observaciones"; // Changed from Quintas
        break;
    // You can add a default case for any other version numbers

            default: echo "Nuevas Observaciones";
        }
        ?>
    </span>
    
    <!-- Separador y campos de oficio (se mantienen igual) -->
 
</h5>
        <p class="text-muted">Complete las cantidades para los tipos de Observaciones que apliquen:</p>  
        <?php// echo  "glosas validas.".$glosas_validas;
        ?>
        <form method="POST" action="">
    <div class="d-flex flex-wrap align-items-center gap-3 bg-light bg-opacity-10 p-2 rounded">
            <div class="d-flex align-items-center flex-nowrap"> 
                <label for="numero_oficio" class="me-2 mb-0 text-muted small text-nowrap">Oficio:</label> 
                <input type="text" class="form-control form-control-sm" id="numero_oficio" name="numero_oficio" value="4-31/" required> 
            </div>

            <div class="d-flex align-items-center flex-nowrap"> 
                <label for="fecha_oficio" class="me-2 mb-0 text-muted small text-nowrap">Fecha:</label> 
                <input type="date" class="form-control form-control-sm" id="fecha_oficio" name="fecha_oficio" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="d-flex align-items-center flex-nowrap flex-grow-1"> 
                <label for="detalle_oficio" class="me-2 mb-0 text-muted small text-nowrap">Detalle:</label> 
                <input type="text" class="form-control form-control-sm" id="detalle_oficio" name="detalle_oficio" placeholder="Detalle del oficio">
            </div>
        </div>
            <div class="row">
                <?php 
                // Dividir el array de tipos de glosas en dos partes
                $mitad = ceil(count($tipos_glosas) / 2);
                $columna1 = array_slice($tipos_glosas, 0, $mitad);
                $columna2 = array_slice($tipos_glosas, $mitad);
                ?>
                
                <!-- Primera columna -->
                <div class="col-md-6">
                    <table class="table table-hover table-sm">
                        <tbody>
                            <?php foreach ($columna1 as $tipo): ?>
                                <tr class="glosa-item">
                                    <td><?= htmlspecialchars($tipo) ?></td>
                                    <td class="text-center" width="100">
                                        <input type="number" 
                                               name="glosas[<?= htmlspecialchars($tipo) ?>]" 
                                               class="form-control form-control-sm cantidad-input" 
                                               min="0" 
                                               value="">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Segunda columna -->
                <div class="col-md-6">
                    <table class="table table-hover table-sm">
                        <tbody>
                            <?php foreach ($columna2 as $tipo): ?>
                                <tr class="glosa-item">
                                    <td><?= htmlspecialchars($tipo) ?></td>
                                    <td class="text-center" width="100">
                                        <input type="number" 
                                               name="glosas[<?= htmlspecialchars($tipo) ?>]" 
                                               class="form-control form-control-sm cantidad-input" 
                                               min="0" 
                                               value="">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                 <a href="report_glosas.php?anio_semestre=<?= htmlspecialchars($depto_info['periodo']) ?>" class="btn btn-outline-secondary btn-sm me-3">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
                <button type="submit" class="btn btn-unicauca btn-sm">
                    <i class="fas fa-save me-1"></i>Guardar Observaciones
                </button>
            </div>
        </form>
    </div>
 <?php if (!empty($glosas_existentes)): ?>
    <div class="mt-4">
        <h5 class="mb-3"><i class="fas fa-history me-2"></i>Historial de Observaciones</h5>

        <?php foreach ($glosas_existentes as $version => $glosas): ?>
             <?php
                // Tu consulta para obtener el oficio y el usuario
                $sql_oficio = "SELECT numero_oficio, fecha_oficio, glosas.fk_user, users.Name, oficios_glosas.descripcion
                                FROM oficios_glosas
                                join glosas on glosas.fk_dp_glosa = oficios_glosas.fk_dp_glosa
                                join users on users.Id = glosas.fk_user
                                WHERE oficios_glosas.fk_dp_glosa = ? AND oficios_glosas.version_glosa = ?
                                LIMIT 1";

                $stmt_oficio = $conn->prepare($sql_oficio);
                $stmt_oficio->bind_param("ii", $fk_dp_glosa, $version);
                $stmt_oficio->execute();
                $result_oficio = $stmt_oficio->get_result();
                $oficio = $result_oficio->fetch_assoc();

                $numero_oficio = $oficio['numero_oficio'] ?? 'No registrado';
                $fecha_oficio = $oficio['fecha_oficio'] ?? 'No registrado';
                $name_user = $oficio['Name'] ?? 'No registrado';
                // *** NUEVA LÍNEA: Obtener la descripción ***
                $descripcion_oficio = $oficio['descripcion'] ?? 'Sin descripción'; // Usamos un texto por defecto si no hay descripción
            $total = array_sum(array_column($glosas, 'cantidad'));
            $item_count = count($glosas);
            ?>

            <div class="card mb-3">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center flex-wrap"> <h6 class="mb-0 me-3">Versión <?= $version ?></h6>
                        <span class="badge bg-unicauca me-2 mb-1"> <i class="fas fa-file-alt me-1"></i> Oficio: <?= htmlspecialchars($numero_oficio) ?>
                        </span>
                        <span class="badge bg-secondary me-2 mb-1"> <i class="far fa-calendar-alt me-1"></i> Fecha: <?= htmlspecialchars($fecha_oficio) ?>
                        </span>
                        <span class="badge bg-info me-2 mb-1"> <i class="fas fa-user me-1"></i> Usuario: <?= htmlspecialchars($name_user) ?>
                        </span>
                        <span class="badge bg-primary mb-1">
                            <i class="fas fa-info-circle me-1"></i> <?= htmlspecialchars($descripcion_oficio) ?>
                        </span>
                        </div>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#editVersionModal<?= $version ?>">
                        <i class="fas fa-edit me-1"></i> Editar
                    </button>
                </div>
                <div class="card-body p-2">
                    <div class="row g-2 align-items-stretch">
                        <!-- Columna izquierda (30%) para la tabla -->
                        <div class="col-md-4 d-flex flex-column">
                            <div class="table-responsive flex-grow-1" style="height: <?= $max_table_height ?>px;">
                                <table class="table table-sm history-table">                                   <thead class="sticky-top bg-light">
                                        <tr>
                                            <th>Tipo de Obsevacion</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($glosas as $glosa): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($glosa['Tipo_glosa']) ?></td>
                                                <td class="text-center"><?= $glosa['cantidad'] ?></td>
                                                <td class="text-center"><?= $total > 0 ? round(($glosa['cantidad']/$total)*100, 1) : 0 ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-secondary fw-bold">
                                            <td>Total</td>
                                            <td class="text-center"><?= $total ?></td>
                                            <td class="text-center">100%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Columna derecha (70%) para el histograma -->
                        <div class="col-md-8">
                            <div class="h-100" style="min-height: <?= $max_table_height ?>px;">
                                <canvas id="histogramVersion<?= $version ?>"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          <!-- Modal para editar esta versión -->
<div class="modal fade" id="editVersionModal<?= $version ?>" tabindex="-1" aria-labelledby="editVersionModalLabel<?= $version ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div class="d-flex flex-column w-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="modal-title mb-0" id="editVersionModalLabel<?= $version ?>">
                            <i class="fas fa-edit me-2"></i>Editar Observaciones - Versión <?= $version ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            
            <!-- FORMULARIO PRINCIPAL (MOVÍ TODO DENTRO DEL FORM) -->
            <form method="POST" action="actualizar_glosas.php">
                <input type="hidden" name="version_glosa" value="<?= $version ?>">
                <input type="hidden" name="fk_dp_glosa" value="<?= $fk_dp_glosa ?>">
                
                <!-- SECCIÓN DE DATOS EDITABLES (AHORA DENTRO DEL FORM) -->
                <div class="modal-body border-bottom">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text text-dark">
                                    <i class="fas fa-file-alt me-1"></i> Oficio
                                </span>
                                <input type="text" 
                                       name="numero_oficio" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($numero_oficio) ?>"
                                       placeholder="Número de oficio"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-secondary text-white">
                                    <i class="far fa-calendar-alt me-1"></i> Fecha
                                </span>
                                <input type="date" 
                                       name="fecha_oficio" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($fecha_oficio) ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- EL RESTO DEL CONTENIDO DEL MODAL PERMANECE IGUAL -->
                <div class="modal-body">
                    <div class="alert alert-info mb-3 py-2">
                        <i class="fas fa-info-circle me-2"></i> Modifique las cantidades para todos los tipos de observación
                    </div>
                        
                        <div class="row">
                            <?php 
                            // Definimos todos los tipos de glosa
                            $tipos_glosas = [
                                'Acto Administrativo errado',
                                'Actualizar año de capacitación',
                                'Actualizar o depurar proyectos de Investigación',
                                'Ajustar al total de horas del parámetro',
                                'Diferencia de horas de contratación o tipo de vinculación',
                                'Profesor No solicitado',
                                'Registro de la actividad con error',
                                'Número de semanas del periodo con error',
                                'Número de semanas proyectos de Extensión con error',
                                'Falta Plan de capacitación',
                                'Inconsistencias Matriculas Académica',
                                'No aplica la observación',
                                'No cumple el total de Docencia Directa',
                                'Número de horas Plan de Estudio erradas',
                                'Sin visado por jefe y/o decano',
                                'Supera el Factor Multiplicador',
                                'Trabajo de grado finalizado o ajustar semanas',
                                'Mayor número de horas de planeación y evaluación curricular',
                                'Registro entre labor y oferta errado',
     'Registro labor pendiente',
    'No postulados en banco de aspirantes',
                                'Otra'
                            ];
                            
                            // Creamos un array combinado con todos los tipos
                            $glosas_completas = [];
                            foreach ($tipos_glosas as $tipo) {
                                $encontrado = false;
                                foreach ($glosas as $g) {
                                    if ($g['Tipo_glosa'] == $tipo) {
                                        $glosas_completas[] = $g;
                                        $encontrado = true;
                                        break;
                                    }
                                }
                                if (!$encontrado) {
                                    $glosas_completas[] = ['Tipo_glosa' => $tipo, 'cantidad' => 0];
                                }
                            }
                            
                            // Dividimos en dos columnas
                            $mitad = ceil(count($glosas_completas) / 2);
                            $columna1 = array_slice($glosas_completas, 0, $mitad);
                            $columna2 = array_slice($glosas_completas, $mitad);
                            ?>
                            
                            <!-- Primera columna -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header py-2 bg-light">
                                        <h6 class="mb-0">Tipos de Observación</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Tipo</th>
                                                    <th width="100" class="text-center">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($columna1 as $glosa): ?>
                                                    <tr class="glosa-item align-middle">
                                                        <td><?= htmlspecialchars($glosa['Tipo_glosa']) ?></td>
                                                        <td class="text-center">
                                                            <input type="number" 
                                                                   name="glosas[<?= htmlspecialchars($glosa['Tipo_glosa']) ?>]" 
                                                                   class="form-control form-control-sm cantidad-input text-center" 
                                                                   min="0" 
                                                                   value="<?= $glosa['cantidad'] ?>">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Segunda columna -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header py-2 bg-light">
                                        <h6 class="mb-0">Tipos de Observación</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Tipo</th>
                                                    <th width="100" class="text-center">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($columna2 as $glosa): ?>
                                                    <tr class="glosa-item align-middle">
                                                        <td><?= htmlspecialchars($glosa['Tipo_glosa']) ?></td>
                                                        <td class="text-center">
                                                            <input type="number" 
                                                                   name="glosas[<?= htmlspecialchars($glosa['Tipo_glosa']) ?>]" 
                                                                   class="form-control form-control-sm cantidad-input text-center" 
                                                                   min="0" 
                                                                   value="<?= $glosa['cantidad'] ?>">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <div class="d-flex justify-content-between w-100">
                            <div>
                                <span class="text-muted small">
                                    <i class="fas fa-info-circle me-1"></i> Versión <?= $version ?>
                                </span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-unicauca">
                                    <i class="fas fa-save me-1"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
        <?php endforeach; ?>
    </div>

        <!-- Gráfica de evolución entre versiones -->
        <div class="card mt-4">
            <div class="card-header bg-light py-2">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Evolución de Observaciones por Versión</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="versionEvolutionChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Versión</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Variación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($versiones_ordenadas as $index => $version): ?>
                                    <tr>
                                        <td>v<?= $version ?></td>
                                        <td class="text-end"><?= $versiones_evolucion[$version] ?></td>
                                        <td class="text-end">
                                            <?php if ($index === 0): ?>
                                                <span class="text-muted">-</span>
                                            <?php else: ?>
                                                <span class="<?= $variaciones[$version] >= 0 ? 'text-danger' : 'text-success' ?>">
                                                    <?= $variaciones[$version] >= 0 ? '↑' : '↓' ?>
<?= is_numeric($variaciones[$version]) ? abs($variaciones[$version]) . '%' : '-' ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        
    </div>

    <!-- Script para gráficos verticales optimizados -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.register(ChartDataLabels);
        
        <?php foreach ($glosas_existentes as $version => $glosas): ?>
            <?php 
            $total = array_sum(array_column($glosas, 'cantidad'));
            $item_count = count($glosas);
            ?>
            
            const ctx<?= $version ?> = document.getElementById('histogramVersion<?= $version ?>');
            if (ctx<?= $version ?>) {
                new Chart(ctx<?= $version ?>, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($glosas, 'Tipo_glosa')) ?>,
                        datasets: [{
                            data: <?= json_encode(array_column($glosas, 'cantidad')) ?>,
                            backgroundColor: [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                                '#858796', '#5a5c69', '#3a3b45', '#2e59d9', '#17a673'
                            ].slice(0, <?= $item_count ?>),
                            borderColor: 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'x', // Barras verticales (valor por defecto)
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    stepSize: <?= max(1, round(max(array_column($glosas, 'cantidad'))/5)) ?>,
                                    font: {
                                        size: 12
                                    }
                                },
                                grid: {
                                    display: true,
                                    drawBorder: true
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: <?= $item_count > 10 ? 10 : 12 ?>
                                    },
                                    callback: function(value) {
                                        // Acortar etiquetas largas
                                        const label = this.getLabelForValue(value);
                                        return label.length > 15 ? label.substr(0, 15) + '...' : label;
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    const percentage = <?= $total ?> > 0 
                                        ? Math.round((value/<?= $total ?>)*100) 
                                        : 0;
                                    return `${value}\n(${percentage}%)`;
                                },
                                color: '#2e59d9',
                                font: {
                                    weight: 'bold',
                                    size: <?= $item_count > 10 ? 10 : 12 ?>
                                },
                                padding: {
                                    top: 4
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label || ''}: ${context.raw} (${Math.round((context.raw/<?= $total ?>)*100)}%)`;
                                    }
                                }
                            }
                        },
                        layout: {
                            padding: {
                                top: 20,
                                right: 15,
                                bottom: 5,
                                left: 15
                            }
                        },
                        barPercentage: 0.8,
                        categoryPercentage: 0.9
                    },
                    plugins: [ChartDataLabels]
                });
            }
        <?php endforeach; ?>
    });
    </script>
<?php endif; ?>
         
 <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuración de colores
        const colorIncrease = 'rgba(220, 53, 69, 0.8)'; // Rojo para aumento
        const colorDecrease = 'rgba(25, 135, 84, 0.8)'; // Verde para disminución
        
        // Datos para la gráfica de evolución
        const evolutionLabels = <?= json_encode(array_map(fn($v) => "v$v", $versiones_ordenadas)) ?>;
        const evolutionData = <?= json_encode(array_values($versiones_evolucion)) ?>;
        const evolutionVariations = <?= json_encode(array_values($variaciones)) ?>;
        
        // Colores basados en aumento/disminución
        const backgroundColors = evolutionVariations.map(v => {
            if (v === null) return 'rgba(108, 117, 125, 0.8)'; // Gris para primera versión
            return v >= 0 ? colorIncrease : colorDecrease;
        });
        
        // Gráfico de evolución de versiones
        const evolutionCtx = document.getElementById('versionEvolutionChart');
        new Chart(evolutionCtx, {
            type: 'bar',
            data: {
                labels: evolutionLabels,
                datasets: [{
                    label: 'Total Glosas',
                    data: evolutionData,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(c => c.replace('0.8', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Total observaciones: ${context.raw}`;
                            },
                            afterLabel: function(context) {
                                if (context.dataIndex === 0) {
                                    return 'Versión inicial';
                                }
                                const variation = evolutionVariations[context.dataIndex];
                                const trend = variation >= 0 ? 'aumentó' : 'disminuyó';
                                return `Respecto a v${context.label.slice(1)-1}: ${trend} ${Math.abs(variation)}%`;
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Total de Observaciones'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Versiones'
                        }
                    }
                }
            }
        });
        
        // ... (código de los gráficos por versión si es necesario) ...
    });
    </script>

</div>         


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
        
        </script>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



        <script>
    document.querySelector('form').addEventListener('submit', function(e) {
        const numeroOficio = document.getElementById('numero_oficio').value.trim();
        if (!numeroOficio || numeroOficio === '4-31/') {
            alert("Por favor, ingrese un número de oficio válido.");
            e.preventDefault();
            return;
        }

       
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>