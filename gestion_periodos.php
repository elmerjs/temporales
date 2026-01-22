<?php
$active_menu_item = 'gestion_periodos';

require('include/headerz.php');
require 'conn.php';

function obtenerperiodo($anio_semestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT periodo.estado_periodo FROM periodo WHERE nombre_periodo = '$anio_semestre'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['estado_periodo'];
    } else {
        return "Período Desconocido";
    }
}

function obtenerperiodonov($anio_semestre) {
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    $sql = "SELECT periodo.estado_novedad FROM periodo WHERE nombre_periodo = '$anio_semestre'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['estado_novedad'];
    } else {
        return "Período Desconocido";
    }
}

// Obtener el último periodo creado
$sql_last_period = "SELECT MAX(periodo) as last_period FROM depto_periodo";
$result_last_period = $conn->query($sql_last_period);
$last_period = $result_last_period->fetch_assoc()['last_period'];

// Calcular el próximo periodo
if ($last_period) {
    list($year, $semester) = explode('-', $last_period);
    if ($semester == '1') {
        $next_period = $year . '-2';
    } else {
        $next_period = ($year + 1) . '-1';
    }
} else {
    $next_period = '2024-1'; // Valor por defecto si no hay periodos previos
}

// Insertar nuevo periodo al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $periodo = $_POST['periodo'];
    $accion = $_POST['accion'];

    if ($accion == 'crear') {
    // Verificar si el periodo ya existe en la tabla periodo
    $sql_check_periodo = "SELECT nombre_periodo FROM periodo WHERE nombre_periodo = ?";
    $stmt_check_periodo = $conn->prepare($sql_check_periodo);
    $stmt_check_periodo->bind_param("s", $periodo);
    $stmt_check_periodo->execute();
    $stmt_check_periodo->store_result();

    if ($stmt_check_periodo->num_rows > 0) {
        // Si ya existe, mostrar un mensaje de alerta
        echo '<div class="alert alert-danger">Ya se encuentra creado el periodo.</div>';
    } else {
        // Obtener todos los departamentos
        $sql_departamentos = "SELECT PK_DEPTO FROM deparmanentos";
        $result_departamentos = $conn->query($sql_departamentos);

        while ($row = $result_departamentos->fetch_assoc()) {
            $fk_depto_dp = $row['PK_DEPTO'];
            $sql_insert = "INSERT INTO depto_periodo (fk_depto_dp, periodo) VALUES (?, ?)";
            $stmt_insert_depto = $conn->prepare($sql_insert);
            $stmt_insert_depto->bind_param("is", $fk_depto_dp, $periodo);
            $stmt_insert_depto->execute();
            $stmt_insert_depto->close();
        }

        // Obtener todas las facultades
        $sql_facultades = "SELECT PK_FAC FROM facultad";
        $result_facultades = $conn->query($sql_facultades);

        while ($row = $result_facultades->fetch_assoc()) {
            $fk_fac = $row['PK_FAC'];
            $sql_insert_fac = "INSERT INTO fac_periodo (fp_fk_fac, fp_periodo) VALUES (?, ?)";
            $stmt_insert_fac = $conn->prepare($sql_insert_fac);
            $stmt_insert_fac->bind_param("is", $fk_fac, $periodo);
            $stmt_insert_fac->execute();
            $stmt_insert_fac->close();
        }
        // Extraer el año de los primeros 4 caracteres del nombre del periodo
            $anio_vigencia = substr($periodo, 0, 4);

            // Insertar en la tabla vigencia si no existe aún
            $sql_check_vigencia = "SELECT anio FROM vigencia WHERE anio = ?";
            $stmt_check_vigencia = $conn->prepare($sql_check_vigencia);
            $stmt_check_vigencia->bind_param("s", $anio_vigencia);
            $stmt_check_vigencia->execute();
            $stmt_check_vigencia->store_result();

            if ($stmt_check_vigencia->num_rows == 0) {
                $sql_insert_vigencia = "INSERT INTO vigencia (anio) VALUES (?)";
                $stmt_insert_vigencia = $conn->prepare($sql_insert_vigencia);
                $stmt_insert_vigencia->bind_param("s", $anio_vigencia);
                $stmt_insert_vigencia->execute();
                $stmt_insert_vigencia->close();
            }

$stmt_check_vigencia->close();
        // Insertar el nuevo periodo en la tabla periodo
        $sql_insert_periodo = "INSERT INTO periodo (nombre_periodo, estado_periodo) VALUES (?, '0')";
        $stmt_insert_periodo = $conn->prepare($sql_insert_periodo);
        $stmt_insert_periodo->bind_param("s", $periodo);
        $stmt_insert_periodo->execute();
        $stmt_insert_periodo->close();

        echo '<div class="alert alert-success">Periodo creado exitosamente.</div>';
    }

    $stmt_check_periodo->close();
}
    elseif ($accion == 'eliminar') {
    // Eliminar de depto_periodo si dp_estado_total es NULL
    $sql_delete = "DELETE FROM depto_periodo WHERE periodo = '$periodo' AND dp_estado_total IS NULL";
    $conn->query($sql_delete);

    // Eliminar de fac_periodo para el periodo correspondiente
    $sql_delete_fac = "DELETE FROM fac_periodo WHERE fp_periodo = '$periodo'";
    $conn->query($sql_delete_fac);

    // Eliminar de periodo
    $sql_deletepg = "DELETE FROM periodo WHERE periodo.nombre_periodo = '$periodo'";
    $conn->query($sql_deletepg);

    echo '<div class="alert alert-success">Periodo eliminado exitosamente.</div>';
} elseif ($accion == 'cerrar') {
        // Cerrar el periodo
        $sql_update = "UPDATE periodo SET estado_periodo = 1 WHERE nombre_periodo = '$periodo'";
        $conn->query($sql_update);

        echo '<div class="alert alert-success">Periodo cerrado exitosamente.</div>';
    }elseif ($accion == 'abrir') {
        // Cerrar el periodo
        $sql_update = "UPDATE periodo SET estado_periodo = 0 WHERE nombre_periodo = '$periodo'";
        $conn->query($sql_update);

        echo '<div class="alert alert-success">Periodo Abierto exitosamente.</div>';
    }
    elseif ($accion == 'cerrarnov') {
        // Cerrar el periodo
        $sql_update = "UPDATE periodo SET estado_novedad = 1 WHERE nombre_periodo = '$periodo'";
        $conn->query($sql_update);

        echo '<div class="alert alert-success">Periodo cerrado exitosamente para novedades.</div>';
    }elseif ($accion == 'abrirnov') {
        // Cerrar el periodo
        $sql_update = "UPDATE periodo SET estado_novedad = 0 WHERE nombre_periodo = '$periodo'";
        $conn->query($sql_update);

        echo '<div class="alert alert-success">Periodo Abierto exitosamente para novedades</div>';
    }
}

// Mostrar la lista de periodos existentes
$sql_existing_periods = "SELECT 
    p.nombre_periodo AS periodo, 
    p.plazo_jefe,
    p.plazo_fac,
    p.plazo_vra,
    COUNT(dp.fk_depto_dp) AS total_deptos, 
    COUNT(CASE WHEN dp.dp_estado_total = 1 AND fp.fp_acepta_vra = 2 THEN 1 END) AS processed_deptos, 
    p.estado_periodo, 
    p.estado_novedad,
    p.inicio_sem AS fecha_inicio_catedra,
    p.fin_sem AS fecha_fin_catedra,
    p.inicio_sem_oc AS fecha_inicio_ocasional,
    p.fin_sem_oc AS fecha_fin_ocasional,
    p.valor_punto,
    
    CASE 
        WHEN COUNT(s_check.departamento_id) > 0 THEN 'si' 
        ELSE 'no' 
    END AS trabajado
    
FROM 
    depto_periodo dp
JOIN 
    periodo p ON p.nombre_periodo = dp.periodo
JOIN 
    deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
JOIN 
    fac_periodo fp ON fp.fp_fk_fac = d.FK_FAC AND fp.fp_periodo = dp.periodo

-- Nuevo JOIN a una subconsulta de solicitudes
-- Usamos LEFT JOIN para no perder periodos que no tengan solicitudes
LEFT JOIN (
    -- Obtenemos solo las combinaciones únicas para evitar duplicados
    SELECT DISTINCT 
        anio_semestre, 
        departamento_id 
    FROM 
        solicitudes
) AS s_check ON s_check.anio_semestre = dp.periodo 
             AND s_check.departamento_id = dp.fk_depto_dp
             
GROUP BY 
    p.nombre_periodo, 
    p.plazo_jefe,
    p.plazo_fac,
    p.plazo_vra,
    p.estado_periodo, 
    p.estado_novedad,
    p.inicio_sem, 
    p.fin_sem,
    p.inicio_sem_oc,
    p.fin_sem_oc,
    p.valor_punto;";
$result_existing_periods = $conn->query($sql_existing_periods);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <title>Gestión de Periodos</title>    <title>Gestión de Periodos</title>
 <style>
    /* Paleta de colores institucional Unicauca */
    :root {
        --unicauca-azul: #001282;
        --unicauca-dorado: #F8AE15;
        --unicauca-blanco: #FFFFFF;
        --unicauca-gris: #F5F5F5;
        --unicauca-verde: #8CBD22;
        --unicauca-rojo: #AD0000;
    }
    
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
        background-color: var(--unicauca-blanco);
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        padding: 30px;
        margin-top: 30px;
        margin-bottom: 30px;
           max-width: 95%;  /* Puedes ajustar entre 90% y 100% */
    width: 100%;
    }
    
  
    h1 {
        padding-bottom: 10px;
        margin-bottom: 30px;
    }
    
    /* Estilos para la tabla */
    .table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        margin-bottom: 1rem;
        background-color: transparent;
    }
    
    .table thead th {
        background-color: var(--unicauca-azul);
        color: var(--unicauca-blanco);
        border: none;
        font-weight: 600;
        padding: 15px;
        text-align: center;
        vertical-align: middle;
    }
    
    .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }
    
    .table tbody tr:nth-child(even) {
        background-color: var(--unicauca-gris);
    }
    
    .table tbody tr:hover {
        background-color: rgba(0, 18, 130, 0.05);
    }
    
    /* Estilos para campos editables */
    .editable, .editable-date {
        cursor: pointer;
        position: relative;
        transition: all 0.2s ease;
        border-radius: 4px;
    }
    
    .editable:hover, .editable-date:hover {
        background-color: rgba(248, 174, 21, 0.2);
        box-shadow: 0 0 0 2px rgba(248, 174, 21, 0.3);
    }
    
    .editable:after, .editable-date:after {
        content: "\f303"; /* Icono de lápiz de FontAwesome */
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--unicauca-dorado);
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .editable:hover:after, .editable-date:hover:after {
        opacity: 1;
    }
    
    /* Estilo especial para campos de fecha */
    .editable-date {
        background-color: rgba(0, 18, 130, 0.05);
        padding-right: 25px;
    }
    
    /* Estilo para campos monetarios */
    .editable[data-field="valor_punto"] {
        font-weight: bold;
        color: var(--unicauca-verde);
    }
    
    /* Estilo para botones */
    .btn-primary {
        background-color: var(--unicauca-azul);
        border-color: var(--unicauca-azul);
    }
    
    .btn-danger {
        background-color: var(--unicauca-rojo);
        border-color: var(--unicauca-rojo);
    }
    
    .btn-warning {
        background-color: var(--unicauca-azul);
        border-color: var(--unicauca-azul);
        color: var(--unicauca-blanco);
    }
    
    .btn-gold {
        background-color: var(--unicauca-dorado);
        border-color: var(--unicauca-dorado);
        color: #000;
        font-weight: 600;
    }
    
    .btn-gray {
        background-color: #6c757d;
        border-color: #6c757d;
        color: var(--unicauca-blanco);
    }
    
    .btn-success {
        background-color: var(--unicauca-verde);
        border-color: var(--unicauca-verde);
    }
    
    /* Estilos para los botones de acción */
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.875rem;
        margin: 2px;
        min-width: 80px;
    }
    
    /* Estilos para los botones de carga */
    .cargar-aspirantes-btn, .cargar-puntos-btn {
    margin: 3px 5px 3px 0;
    width: auto; /* Permite que se ajusten al contenido */
    font-size: 0.8rem;
    display: inline-block; /* Asegura que estén en la misma línea */
}
    
    .cargar-aspirantes-btn {
        background-color: var(--unicauca-azul);
    }
    
    .cargar-puntos-btn {
        background-color: var(--unicauca-verde);
    }
    
    /* Estilos para el formulario */
    .form-group label {
        font-weight: 600;
        color: var(--unicauca-azul);
    }
    
    .periodo-input {
        width: 120px;
        display: inline-block;
        margin-right: 10px;
    }
    
    /* Estilos para estados */
    .estado-abierto {
        color: var(--unicauca-verde);
        font-weight: 600;
    }
    
    .estado-cerrado {
        color: var(--unicauca-rojo);
        font-weight: 600;
    }
    
    /* Responsividad */
    @media (max-width: 992px) {
        .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .container {
            padding: 15px;
        }
    }
     /* Estilos para badges de estado */
.estado-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    min-width: 70px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.estado-badge.cerrado {
    background-color: #AD0000; /* Rojo institucional Unicauca */
    color: white;
    border: 1px solid #8a0000;
}

.estado-badge.abierto {
    background-color: #8CBD22; /* Verde institucional Unicauca */
    color: white;
    border: 1px solid #7aab1a;
}

/* Opcional: Estilo para hover */
.estado-badge:hover {
    opacity: 0.9;
    transform: scale(1.02);
    transition: all 0.2s ease;
}
     /* Estilo para botón Eliminar */
.btn-danger {
    background-color: #AD0000; /* Rojo Unicauca */
    border-color: #8a0000;
    color: white;
}

/* Estilo para botón Eliminar deshabilitado */
.btn-secondary {
    background-color: #6c757d;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Estilos para botón de toggle Abrir/Cerrar */
.estado-toggle {
    min-width: 90px;
    font-weight: 600;
    transition: all 0.3s ease;
    border-width: 2px;
}

.btn-abrir {
    background-color: #8CBD22; /* Verde Unicauca */
    border-color: #7aab1a;
    color: white;
}

.btn-cerrar {
    background-color: #001282; /* Azul Unicauca */
    border-color: #001050;
    color: #F8AE15; /* Dorado Unicauca */
}

/* Efectos hover */
.btn-danger:hover {
    background-color: #9a0000;
    transform: translateY(-1px);
}

.estado-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
     /* Estilos específicos para botón de Novedades */
.estado-novedad {
    min-width: 110px;
    font-weight: 600;
    transition: all 0.3s ease;
    border-width: 2px;
    letter-spacing: 0.5px;
}

.btn-abrir-nov {
    background-color: #F8AE15; /* Dorado Unicauca */
    border-color: #e09e10;
    color: #001282; /* Azul Unicauca */
}

.btn-cerrar-nov {
    background-color: #6A0DAD; /* Violeta institucional */
    border-color: #5a0b95;
    color: white;
}

.estado-novedad:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    opacity: 0.9;
}
</style>
</head>
<body>
     <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Gestión de Periodos</h1>

<form method="POST" action="" class="mb-5">
    <div class="d-flex justify-content-between align-items-end flex-wrap" style="gap: 10px;">
        <!-- Input + Botón -->
        <div class="d-flex align-items-end" style="gap: 10px;">
            <div>
                <label for="periodo">Periodo:</label>
                <input type="text" class="form-control periodo-input" id="periodo" name="periodo" value="<?php echo $next_period; ?>" required>
            </div>
            <div>
                <label style="visibility: hidden;">Botón</label>
                <button type="submit" name="accion" value="crear" class="btn btn-gold">Crear Periodo</button>
            </div>
        </div>

        <!-- Enlaces a otros módulos -->
        <div class="d-flex" style="gap: 10px;">
            <a href="http://192.168.42.175/labor/dt_ss/index.php" class="btn btn-outline-secondary" target="_blank">
                Módulo - Resoluciones CIARP
            </a>
            <a href="vicerrectoria/listar_vicerrectores.php" class="btn btn-outline-primary">
                <i class="fas fa-user-tie"></i> Gestión de Vicerrectores
            </a>
        </div>
    </div>
</form>
        



        <h2 class="mb-4">Periodos Existentes</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Periodo</th>
            <th>Total</th>
            <th>Aceptados</th>
             <th>Inicio Cátedra</th>
            <th>Fin Cátedra</th>
            <th>Inicio Ocasional</th>
            <th>Fin Ocasional</th>
            <th>Plazos</th>
            <th>Valor Punto</th>
     <!--       <th>Estado</th>
            <th>Est. Novedad</th>-->
            <th>Acciones</th>
            <th>Novedades</th>
<th class="text-center">Cargar Datos</th>
            
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result_existing_periods->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['periodo']; ?></td>
                <td><?php echo $row['total_deptos']; ?></td>
                <td><?php echo $row['processed_deptos']; ?></td>
                
                      <!-- Nuevos campos editables -->
                <td class="text-center editable-date" data-field="inicio_sem" data-periodo="<?php echo htmlspecialchars($row['periodo']); ?>">
                    <?php echo !empty($row['fecha_inicio_catedra']) ? date('d/m/Y', strtotime($row['fecha_inicio_catedra'])) : 'No definida'; ?>
                </td>
                <td class="text-center editable-date" data-field="fin_sem" data-periodo="<?php echo htmlspecialchars($row['periodo']); ?>">
                    <?php echo !empty($row['fecha_fin_catedra']) ? date('d/m/Y', strtotime($row['fecha_fin_catedra'])) : 'No definida'; ?>
                </td>
                <td class="text-center editable-date" data-field="inicio_sem_oc" data-periodo="<?php echo htmlspecialchars($row['periodo']); ?>">
                    <?php echo !empty($row['fecha_inicio_ocasional']) ? date('d/m/Y', strtotime($row['fecha_inicio_ocasional'])) : 'No definida'; ?>
                </td>
                <td class="text-center editable-date" data-field="fin_sem_oc" data-periodo="<?php echo htmlspecialchars($row['periodo']); ?>">
                    <?php echo !empty($row['fecha_fin_ocasional']) ? date('d/m/Y', strtotime($row['fecha_fin_ocasional'])) : 'No definida'; ?>
                </td>
                 <td class="text-center">
                <!-- Botón que abre el modal -->
                <button type="button" class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalPlazos" 
                        data-nombre="<?php echo $row['periodo']; ?>"
                        data-jefe="<?php echo $row['plazo_jefe']; ?>"
                        data-fac="<?php echo $row['plazo_fac']; ?>"
                        data-vra="<?php echo $row['plazo_vra']; ?>">
                    <i class="fas fa-calendar-alt"></i> Plazos
                </button>
            </td>

                <td class="text-center editable" data-field="valor_punto" data-periodo="<?php echo htmlspecialchars($row['periodo']); ?>">
                    <?php echo isset($row['valor_punto']) ? '$' . number_format($row['valor_punto'], 0, ',', '.') : 'No definido'; ?>
                </td>
            <!--    <td>
    <span class="estado-badge <?php //echo ($row['estado_periodo'] == 1) ? 'cerrado' : 'abierto'; ?>">
        <?php //echo ($row['estado_periodo'] == 1) ? "Cerrado" : "Abierto"; ?>
    </span>
</td>
<td>
    <span class="estado-badge <?php //echo ($row['estado_novedad'] == 1) ? 'cerrado' : 'abierto'; ?>">
        <?php //echo ($row['estado_novedad'] == 1) ? "Cerrado" : "Abierto"; ?>
    </span>
</td>-->
                
        <td class="text-center">
    <!-- Botón Eliminar (condicional) -->
            <form method="POST" action="" style="display:inline-block; margin-right:5px;">
                <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">

                <button type="submit" name="accion" value="eliminar" 

                        class="btn btn-sm <?php echo $row['trabajado'] == 'si' ? 'btn-secondary' : 'btn-danger'; ?>"

                        <?php echo $row['trabajado'] == 'si' ? 'disabled' : ''; ?>>

                    <i class="fas fa-trash-alt"></i> Eliminar
                </button>
            </form>
    
    <!-- Botón Abrir/Cerrar -->
<form method="POST" action="" style="display:inline-block;">
    <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
    
    <?php 
        // Lógica INVERTIDA: 
        // Si el estado es 1, ahora lo mostraremos como "Cerrado"
        if ($row['estado_periodo'] == 1) {
            $textoEstado = "Cerrado";
            $claseCss    = "btn-cerrar";
            $icono       = "fa-lock";
            $siguienteAccion = "abrir"; // Al estar cerrado, la acción es abrir
        } else {
            $textoEstado = "Abierto";
            $claseCss    = "btn-abrir";
            $icono       = "fa-lock-open";
            $siguienteAccion = "cerrar"; // Al estar abierto, la acción es cerrar
        }
    ?>

    <button type="submit" name="accion" value="<?php echo $siguienteAccion; ?>" 
            class="btn btn-sm estado-toggle <?php echo $claseCss; ?>">
        <i class="fas <?php echo $icono; ?>"></i>
        <?php echo $textoEstado; ?>
    </button>
</form>
        </td>

       <td style="text-align:center;">
                <form method="POST" action="" style="display:inline-block;">
                    <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                    <button type="submit" name="accion" value="<?php echo $row['estado_novedad'] == 1 ? 'abrirnov' : 'cerrarnov'; ?>" 
                            class="btn btn-sm estado-novedad <?php echo $row['estado_novedad'] == 1 ? 'btn-abrir-nov' : 'btn-cerrar-nov'; ?>">
                        <i class="fas <?php echo $row['estado_novedad'] == 1 ? 'fa-envelope-open-text' : 'fa-envelope'; ?>"></i>
                        <?php echo $row['estado_novedad'] == 1 ? 'Cerrado' : 'Abierto'; ?>
                    </button>
                </form>
            </td>

                <td class="text-center">
                <!-- Botón para abrir el modal -->
                <button 
                    type="button" 
                    class="btn btn-primary btn-sm cargar-aspirantes-btn" 
                    data-bs-toggle="modal" 
                    data-bs-target="#cargarAspirantesModal" 
                    data-periodo="<?php echo $row['periodo']; ?>">
                    Cargar Aspirantes
                </button>

                <!-- Botón para abrir el modal de Cargar Puntos -->
                <button 
                    type="button" 
                    class="btn btn-success btn-sm cargar-puntos-btn" 
                    data-bs-toggle="modal" 
                    data-bs-target="#cargarPuntosModal" 
                    data-periodo="<?php echo $row['periodo']; ?>">
                    Cargar Puntos
                </button>
            </td>

            </tr>
        <?php } ?>
    </tbody>
</table>
<!-- Modal para Cargar Aspirantes -->
<div class="modal fade" id="cargarAspirantesModal" tabindex="-1" aria-labelledby="cargarAspirantesLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cargarAspirantesLabel">Cargar Aspirantes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="procesar_archivo_aspirantes.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="periodo" id="modalPeriodo">
                    <div class="mb-3">
                        <label for="archivoAspirantes" class="form-label">Seleccionar archivo</label>
                        <input type="file" class="form-control" id="archivoAspirantes" name="file" required>
                        <div class="form-text">Sube un archivo en formato CSV, XLSX, o similar.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cargar</button>
                </div>
            </form>
        </div>
    </div>
</div>
        <!-- Modal para Cargar Puntos -->
<div class="modal fade" id="cargarPuntosModal" tabindex="-1" aria-labelledby="cargarPuntosLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cargarPuntosLabel">Cargar Puntos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="procesar_archivo_puntos.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="periodo" id="modalPeriodoPuntos">
                    <div class="mb-3">
                        <label for="archivoPuntos" class="form-label">Seleccionar archivo</label>
                        <input type="file" class="form-control" id="archivoPuntos" name="archivo_excel" required>
                        <div class="form-text">Debe contener columnas: <strong>cedula</strong> y <strong>puntos</strong>.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Cargar</button>
                </div>
            </form>
        </div>
    </div>
</div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('cargarAspirantesModal');
        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Botón que abre el modal
            const periodo = button.getAttribute('data-periodo'); // Obtener el periodo del atributo data
            const inputPeriodo = modal.querySelector('#modalPeriodo');
            inputPeriodo.value = periodo; // Pasar el periodo al input oculto
        });
        
        
    // Para modal de Puntos
    const modalPuntos = document.getElementById('cargarPuntosModal');
    modalPuntos.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const periodo = button.getAttribute('data-periodo');
        const inputPeriodo = modalPuntos.querySelector('#modalPeriodoPuntos');
        inputPeriodo.value = periodo;
    });
        
    });
</script>
    
    
    
<!-- Modal de Edición -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarTitulo">Editar Campo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-edicion">
                    <input type="hidden" id="edit-periodo" name="periodo">
                    <input type="hidden" id="edit-field" name="field">
                    <div class="mb-3">
                        <label id="edit-label" class="form-label">Valor:</label>
                        <input type="text" class="form-control" id="edit-value">
                        <small class="text-muted" id="edit-hint"></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardar-cambios">Guardar</button>
            </div>
        </div>
    </div>
</div>

    
    
    
<!-- Modal Bootstrap  para plazos -->
<div class="modal fade" id="modalPlazos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="actualizar_plazos.php">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Gestión de Plazos - <span id="tituloPeriodo"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <!-- Campo oculto con el nombre del periodo -->
            <input type="hidden" name="nombre_periodo" id="nombre_periodo">

            <div class="mb-3">
                <label for="plazo_jefe" class="form-label">Plazo Jefes de Departamento</label>
                <input type="date" class="form-control" name="plazo_jefe" id="plazo_jefe">
            </div>
            <div class="mb-3">
                <label for="plazo_fac" class="form-label">Plazo Facultades</label>
                <input type="date" class="form-control" name="plazo_fac" id="plazo_fac">
            </div>
            <div class="mb-3">
                <label for="plazo_vra" class="form-label">Plazo VRA</label>
                <input type="date" class="form-control" name="plazo_vra" id="plazo_vra">
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="guardar_plazos" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </form>
  </div>
</div>
    
    
    
<!-- Script para pasar datos al modal  plaoos-->
<script>
var modalPlazos = document.getElementById('modalPlazos');
modalPlazos.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;

  var nombre = button.getAttribute('data-nombre');
  var jefe = button.getAttribute('data-jefe');
  var fac = button.getAttribute('data-fac');
  var vra = button.getAttribute('data-vra');

  // Pasar valores al modal
  modalPlazos.querySelector('#nombre_periodo').value = nombre;
  modalPlazos.querySelector('#tituloPeriodo').innerText = nombre;
  modalPlazos.querySelector('#plazo_jefe').value = jefe;
  modalPlazos.querySelector('#plazo_fac').value = fac;
  modalPlazos.querySelector('#plazo_vra').value = vra;
});
</script>
    
<script>
$(document).ready(function() {
    // Manejar clic en celdas editables
    $('.editable, .editable-date').click(function() {
        const periodo = $(this).data('periodo');
        const field = $(this).data('field');
        let currentValue = $(this).text().trim();
        
        // Limpiar valor actual para fechas
        if($(this).hasClass('editable-date') && currentValue !== 'No definida') {
            const parts = currentValue.split('/');
            currentValue = parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        
        // Limpiar valor actual para valor punto
        if(field === 'valor_punto' && currentValue !== 'No definido') {
            currentValue = currentValue.replace(/[^\d]/g, '');
        }
        
        // Configurar el modal según el tipo de campo
        $('#edit-periodo').val(periodo);
        $('#edit-field').val(field);
        $('#edit-value').val(currentValue);
        
        // Configurar etiquetas y ayudas
        let label = '', hint = '', inputType = 'text';
        switch(field) {
            case 'inicio_sem': 
                label = 'Fecha Inicio Cátedra';
                hint = 'Formato: DD/MM/AAAA';
                inputType = 'date';
                break;
            case 'fin_sem': 
                label = 'Fecha Fin Cátedra';
                hint = 'Formato: DD/MM/AAAA';
                inputType = 'date';
                break;
            case 'inicio_sem_oc': 
                label = 'Fecha Inicio Ocasional';
                hint = 'Formato: DD/MM/AAAA';
                inputType = 'date';
                break;
            case 'fin_sem_oc': 
                label = 'Fecha Fin Ocasional';
                hint = 'Formato: DD/MM/AAAA';
                inputType = 'date';
                break;
            case 'valor_punto': 
                label = 'Valor del Punto';
                hint = 'Ingrese solo números';
                inputType = 'number';
                break;
        }
        
        $('#modalEditarTitulo').text('Editar ' + label);
        $('#edit-label').text(label);
        $('#edit-hint').text(hint);
        $('#edit-value').attr('type', inputType);
        
        $('#editarModal').modal('show');
    });
    
    // Guardar cambios
    $('#guardar-cambios').click(function() {
        const periodo = $('#edit-periodo').val();
        const field = $('#edit-field').val();
        let newValue = $('#edit-value').val();
        
        // Validaciones básicas
if (field.includes('_sem') || field.includes('sem_oc')) {
            if(newValue && !isValidDate(newValue)) {
                alert('Por favor ingrese una fecha válida');
                return;
            }
        }
        
        if(field === 'valor_punto' && newValue && isNaN(newValue)) {
            alert('Por favor ingrese un valor numérico válido');
            return;
        }
        
        $.ajax({
            url: 'actualizar_periodos.php',
            method: 'POST',
            data: {
                periodo: periodo,
                field: field,
                value: newValue,
                accion: 'actualizar'
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Actualizar la celda visualmente
                    const cell = $(`[data-periodo="${periodo}"][data-field="${field}"]`);
                    
                    if((field.includes('_sem') || field.includes('sem_oc')) && newValue) {
const dateObj = new Date(newValue + 'T00:00:00');
                        const formattedDate = dateObj.toLocaleDateString('es-ES');
                        cell.text(formattedDate);
                    } else if(field === 'valor_punto' && newValue) {
                        cell.text('$' + parseFloat(newValue).toLocaleString('es-ES'));
                    } else {
                        cell.text(newValue || (field.includes('_sem') ? 'No definida' : 'No definido'));
                    }
                    
                    $('#editarModal').modal('hide');
                    toastr.success('Cambios guardados correctamente');
                } else {
                    toastr.error('Error al guardar: ' + (response.message || 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error en la conexión: ' + error);
            }
        });
    });
    
    function isValidDate(dateString) {
        // Validación simple de fecha (puedes mejorarla)
        return !isNaN(Date.parse(dateString));
    }
});
</script>


 
<script>

</script>

</body>
</html>
