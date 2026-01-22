<?php
require('include/headerz.php');
require 'funciones.php';


// 1. Verificar sesión
if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    echo "<div class='alert alert-warning text-center'>Debe <a href='index.html' class='alert-link'>iniciar sesión</a> para continuar</div>";
    exit();
}

// 2. Configuración de la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// 3. Obtener parámetros con prioridad: GET > SESSION
$nombre_sesion = $_SESSION['name'];
$anio_semestre = isset($_GET['anio_semestre']) ? $conn->real_escape_string($_GET['anio_semestre']) : '2025-2';
$tipo_usuario = isset($_GET['tipo_usuario']) ? (int)$_GET['tipo_usuario'] : (isset($_SESSION['tipo_usuario']) ? (int)$_SESSION['tipo_usuario'] : 1);
$facultad_id = isset($_GET['facultad_id']) ? (int)$_GET['facultad_id'] : (isset($_SESSION['facultad_id']) ? (int)$_SESSION['facultad_id'] : null);
$departamento_id = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : (isset($_SESSION['departamento_id']) ? (int)$_SESSION['departamento_id'] : null);

// 4. Construir condición WHERE según tipo de usuario
$where = "WHERE solicitudes.anio_semestre = '" . $anio_semestre . "' ";
$where .= " AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

if ($tipo_usuario == 2 && $facultad_id !== null) {
    $where .= " AND facultad.PK_FAC = " . $facultad_id;
} elseif ($tipo_usuario == 3 && $facultad_id !== null && $departamento_id !== null) {
    $where .= " AND facultad.PK_FAC = " . $facultad_id .
              " AND deparmanentos.PK_DEPTO = " . $departamento_id;
}

// 5. Consulta SQL unificada

$sql = "SELECT
    solicitudes.anio_semestre,
    facultad.NOMBREC_FAC,
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
    facultad.NOMBREC_FAC,
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
    anio_semestre, PK_FAC, NOMBRE_DEPTO_CORT, nombre ASC;";// 6. Ejecutar consulta y preparar datos para DataTables
$result = $conn->query($sql);
$profesoresData = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profesoresData[] = $row;
    }
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado  de Vinculación de Docentes Temporales - Unicauca</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    
    <!-- Favicon Unicauca -->
    <link rel="icon" href="https://www.unicauca.edu.co/version7/sites/all/themes/unicauca/favicon.ico" type="image/x-icon">

    <style>
        :root {
            --unicauca-azul: #001282;
            --unicauca-rojo: #E52724;
            --unicauca-azul-claro: #16A8E1;
            --unicauca-verde: #249337;
            --unicauca-amarillo: #F8AE15;
            --unicauca-primary-btn: #002D72;
            --unicauca-primary-btn-hover: #001f50;
            --unicauca-gray: #f0f2f5;
            --unicauca-bg-light: #f8fafc;
        }
        
        body {
            background-color: var(--unicauca-bg-light);
            font-family: 'Open Sans', sans-serif;
            color: #333;
        }
        
        .container-main {
            padding: 0 4%;
            max-width: 1800px;
            margin: 0 auto;
        }
        
        /* Header institucional mejorado */
        .institutional-header {
            background: linear-gradient(135deg, var(--unicauca-azul) 0%, #0039a6 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-bottom: 4px solid var(--unicauca-amarillo);
        }
        
        .institutional-header .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .institutional-header .logo-img {
            height: 50px;
        }
        
        .institutional-header .header-text {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .institutional-header .header-title {
            font-weight: 700;
            margin: 0;
            font-size: 1.4rem;
        }
        
        /* Tarjeta principal */
        .main-card {
            border-radius: 12px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            border: none;
            margin-bottom: 3rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }
        
        .main-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-header-unicauca {
            background: linear-gradient(135deg, var(--unicauca-azul) 0%, #0039a6 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1.5rem;
            border-bottom: 3px solid var(--unicauca-amarillo);
        }
        
        .card-header-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Badge de periodo mejorado */
        .periodo-badge-unicauca {
            background-color: white;
            color: var(--unicauca-azul);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* Botones institucionales */
        .btn-unicauca {
            background-color: var(--unicauca-rojo);
            border-color: var(--unicauca-rojo);
            color: white;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-unicauca:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: white;
        }
        
        .btn-unicauca-outline {
            background-color: transparent;
            border: 2px solid var(--unicauca-azul);
            color: var(--unicauca-azul);
        }
        
        .btn-unicauca-outline:hover {
            background-color: var(--unicauca-azul);
            color: white;
        }
        /* Tabla de datos mejorada */
.table-container-unicauca {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    background: white;
    border: 1px solid rgba(0,0,0,0.05);
    margin:  0 10px; /* Margen vertical */
    margin: .5rem 0; /* Alternativa en rem (recomendado) */
}
        /* Añade esto a tu CSS */
.table-responsive {
    padding: 15px; /* Espacio interno */
    margin-top: 10px; /* Espacio superior */
}

/* O si estás usando tu clase personalizada: */
.table-container-unicauca {
    padding: 1rem;
    margin: 1rem 0;
}
        
        .table-unicauca thead th {
            background-color: var(--unicauca-azul);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            text-align: center;
            vertical-align: middle;
            border-bottom: 3px solid var(--unicauca-amarillo);
        }
        
        .table-unicauca tbody tr {
            transition: all 0.15s ease;
        }
        
        .table-unicauca tbody tr:hover {
            background-color: rgba(0, 18, 130, 0.03);
        }
        
        .table-unicauca td {
            padding: 0.9rem 1rem;
            vertical-align: middle;
            text-align: center;
            border-top: 1px solid rgba(0,0,0,0.03);
        }
        
        /* Estados mejorados */
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.7rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-accepted {
            background-color: rgba(36, 147, 55, 0.15);
            color: var(--unicauca-verde);
        }
        
        .status-rejected {
            background-color: rgba(229, 39, 36, 0.15);
            color: var(--unicauca-rojo);
        }
        
        .status-pending {
            background-color: rgba(248, 174, 21, 0.15);
            color: var(--unicauca-amarillo);
        }
        
        /* Mejoras para DataTables */
        .dataTables-controls {
            background: white;
            padding: .2rem 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
        }
        
        .dataTables-filter input {
            border-radius: 8px !important;
            padding: 0.65rem 1.25rem !important;
            border: 1px solid #ddd !important;
            transition: all 0.3s !important;
        }
        
        .dataTables-filter input:focus {
            border-color: var(--unicauca-azul) !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 18, 130, 0.1) !important;
        }
        
        /* Tooltip para nombres largos */
       .nombre-cell {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    position: relative;
}

/* Tooltip mejorado */
.nombre-cell:hover::before {
    content: attr(title);
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    bottom: 100%;
    background: var(--unicauca-azul);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    z-index: 1000;
    white-space: normal;
    width: max-content;
    max-width: 300px;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-bottom: 5px;
} /* Puntos destacados */
        .points-cell {
            font-weight: 700;
            color: var(--unicauca-azul);
            font-size: 1rem;
        }
        
        /* Footer institucional */
        .institutional-footer {
            background-color: var(--unicauca-azul);
            color: white;
            padding: 1.5rem 0;
            margin-top: 3rem;
            font-size: 0.9rem;
        }
        
        .footer-logo {
            height: 40px;
            opacity: 0.9;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        /* Responsividad mejorada */
        @media (max-width: 992px) {
            .card-header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .header-actions {
                justify-content: flex-start !important;
            }
            
            .container-main {
                padding: 0 2%;
            }
        }
        
        @media (max-width: 768px) {
            .dataTables-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .table-unicauca thead th {
                font-size: 0.75rem;
                padding: 0.75rem 0.5rem;
            }
            
            .table-unicauca td {
                padding: 0.75rem 0.5rem;
                font-size: 0.85rem;
            }
            
            .card-header-title {
                font-size: 1.3rem;
            }
        }
        .depto-cell {
    max-width: 120px; /* Ajusta según necesites */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    position: relative;
}

/* Tooltip para mostrar el texto completo */
.depto-cell:hover::after {
    content: attr(title);
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 100%;
    background: var(--unicauca-azul);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    z-index: 1000;
    white-space: normal;
    width: auto;
    min-width: 200px;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
    </style>
</head>
<body><main class="container-main">
        <div class="main-card">
            <div class="card-header-unicauca">
                <div class="d-flex justify-content-between align-items-center flex-wrap card-header-content">
                    <div>
                        <h2 class="card-header-title mb-0">
                            <i class="fas fa-users-gear"></i>
                            Listado de Profesores Temporales a vincular
                        </h2>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2 header-actions">
                        <span class="periodo-badge-unicauca">
                            <i class="fas fa-calendar-alt me-2"></i>Periodo: <?= htmlspecialchars($anio_semestre) ?>
                        </span>
                        
                     <form id="regresarForm" action="report_depto_full.php" method="POST" style="display: none;">
    <input type="hidden" name="anio_semestre" value="<?= htmlspecialchars($anio_semestre) ?>">
    <input type="hidden" name="tipo_usuario" value="<?= htmlspecialchars($tipo_usuario) ?>">
    <input type="hidden" name="facultad_id" value="<?= htmlspecialchars($facultad_id) ?>">
    <input type="hidden" name="departamento_id" value="<?= htmlspecialchars($departamento_id) ?>">
    </form>

<button type="button" class="btn btn-unicauca" onclick="document.getElementById('regresarForm').submit();">
    <i class="fas fa-arrow-left me-1"></i> Regresar
</button>
                    </div>
                </div>
            </div>
            
            <div class="card-body">

                
<div class="table-container-unicauca mt-1">  <!-- Reducimos MARGIN-TOP a 1 unidad (4px) -->
                    <table id="profesoresDatatable" class="table-unicauca table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th>Facultad</th>
                                <th>Depto.</th>
                                <th>Sede</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Dedicación</th>
                                <th>Horas</th>
                                <th>Rta. Fac.</th>
                                <th>Rta. VRA</th>
                                <th>HV.nuev </th>
                                <th>HV.Actlz</th>
                                <th>Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profesoresData as $profesor): ?>
                            <tr>
                                <td><?= htmlspecialchars($profesor['anio_semestre']) ?></td>
                                <td><?= htmlspecialchars($profesor['NOMBREC_FAC']) ?></td>
                    <td class="depto-cell" title="<?= htmlspecialchars($profesor['NOMBRE_DEPTO_CORT']) ?>">
    <?= htmlspecialchars(mb_substr($profesor['NOMBRE_DEPTO_CORT'], 0, 17)) . (mb_strlen($profesor['NOMBRE_DEPTO_CORT']) > 18 ? '...' : '') ?>
</td>            <td><?= htmlspecialchars($profesor['sede']) ?></td>
                                <td><?= htmlspecialchars($profesor['cedula']) ?></td>
                                <td class="nombre-cell" title="<?= htmlspecialchars($profesor['nombre']) ?>">
    <?= htmlspecialchars(mb_substr($profesor['nombre'], 0, 20)) . (mb_strlen($profesor['nombre']) > 20 ? '...' : '') ?>
</td><td><?= htmlspecialchars($profesor['tipo_docente']) ?></td>
                                <td><?= htmlspecialchars($profesor['dedicacion']) ?></td>
                                <td><?= htmlspecialchars($profesor['horas']) ?></td>
                                
                                <td>
    <?php if($profesor['acepta_fac_status'] === 'Aceptado'): ?>
        <span class="status-badge status-accepted">
            <i class="fas fa-check-circle me-1"></i> 
        </span>
    <?php elseif($profesor['acepta_fac_status'] === 'Rechazado'): ?>
        <span class="status-badge status-rejected">
            <i class="fas fa-times-circle me-1"></i> 
        </span>
    <?php else: ?>
        <span class="status-badge status-pending">
            <i class="fas fa-clock me-1"></i> 
        </span>
    <?php endif; ?>
</td>

<td>
    <?php if($profesor['acepta_vra_status'] === 'Aceptado'): ?>
        <span class="status-badge status-accepted">
            <i class="fas fa-check-circle me-1"></i> 
        </span>
    <?php elseif($profesor['acepta_vra_status'] === 'Rechazado'): ?>
        <span class="status-badge status-rejected">
            <i class="fas fa-times-circle me-1"></i> 
        </span>
    <?php else: ?>
        <span class="status-badge status-pending">
            <i class="fas fa-clock me-1"></i> 
        </span>
    <?php endif; ?>
</td>
                                
                                <td>
                                    <?= $profesor['anexa_hv_docente_nuevo'] == 1 
                                        ? '<span class="status-badge status-accepted"><i class="fas fa-check me-1"></i> Sí</span>'
                                        : '<span class="status-badge status-rejected"><i class="fas fa-times me-1"></i> No</span>' ?>
                                </td>
                                
                                <td>
                                    <?= $profesor['actualiza_hv_antiguo'] == 1 
                                        ? '<span class="status-badge status-accepted"><i class="fas fa-check me-1"></i> Sí</span>'
                                        : '<span class="status-badge status-rejected"><i class="fas fa-times me-1"></i> No</span>' ?>
                                </td>
                                
                                <td class="points-cell"><?= htmlspecialchars($profesor['puntos'] ?? '0') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer institucional 
    <footer class="institutional-footer">
        <div class="container-main">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <img src="https://www.unicauca.edu.co/version7/sites/all/themes/unicauca/logo-footer.png" alt="Logo Unicauca" class="footer-logo">
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="text-white mb-3">Contacto</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone-alt me-2"></i> Teléfono: (602) 8209800</li>
                        <li><i class="fas fa-envelope me-2"></i> Email: vicerrectoria@unicauca.edu.co</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Popayán, Cauca, Colombia</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="text-white mb-3">Enlaces</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="https://www.unicauca.edu.co" target="_blank">Sitio Web Unicauca</a></li>
                        <li><a href="https://vicerrectoria.unicauca.edu.co" target="_blank">Vicerrectoría Académica</a></li>
                        <li><a href="#" target="_blank">Políticas de Privacidad</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4 pt-3 border-top border-white-10">
                <small>&copy; <?= date('Y') ?> Universidad del Cauca. Todos los derechos reservados.</small>
            </div>
        </div>
    </footer>
-->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
   
    <script type="text/javascript" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    
    <script>
  $(document).ready(function() {
    $('#profesoresDatatable').DataTable({
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json",
            "search": "Buscar:", // Esto solo cambia la etiqueta, no habilita el campo si 'f' no está en dom
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron registros coincidentes",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "paginate": {
                "first": "Primera",
                "last": "Última",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        },
        // CAMBIO AQUÍ: Añade 'f' para incluir el campo de búsqueda
"dom": '<"row"<"col-md-6"l><"col-md-6 text-end"f>><"row"<"col-sm-12"t>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "responsive": true,
        "initComplete": function() {
            // Estas líneas ahora sí tendrán un efecto porque el input de búsqueda se creará
            $('.dataTables_filter input').attr('placeholder', 'Buscar por nombre, cédula, facultad...');
            $('.dataTables_filter input').addClass('form-control');
            $('.dataTables_length select').addClass('form-select form-select-sm');
        }
    });
    // ...
});
    </script>
</body>
</html>