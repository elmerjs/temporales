<?php
require('include/headerz.php');
require 'vendor/autoload.php';
require 'funciones.php';

if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    echo "<div class='alert alert-warning text-center'>Debe <a href='index.html' class='alert-link'>iniciar sesión</a> para continuar</div>";
    exit();
}

$nombre_sesion = $_SESSION['name'];
$anio_semestre = isset($_POST['anio_semestre']) ? $_POST['anio_semestre'] : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : '2025-2');

$tipo_usuario = isset($_SESSION['tipo_usuario']) ? (int)$_SESSION['tipo_usuario'] : 1;
$facultad_id = isset($_SESSION['facultad_id']) ? (int)$_SESSION['facultad_id'] : null;
$departamento_id = isset($_SESSION['departamento_id']) ? (int)$_SESSION['departamento_id'] : null;

$data_url = "get_profesores.php?periodo=" . urlencode($anio_semestre) .
            "&tipo_usuario=" . $tipo_usuario;

if ($tipo_usuario == 2 && $facultad_id !== null) {
    $data_url .= "&facultad_id=" . $facultad_id;
} elseif ($tipo_usuario == 3 && $facultad_id !== null && $departamento_id !== null) {
    $data_url .= "&facultad_id=" . $facultad_id . "&departamento_id=" . $departamento_id;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vinculación de Temporales</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">

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
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-fluid {
            padding: 0 5%;
            padding-top: 0 !important;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            border: none;
            margin-top: 2rem;
            margin-bottom: 3rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .card:first-child {
            margin-top: 1rem !important;
        }

        .card-header {
            background: linear-gradient(135deg, var(--unicauca-azul) 0%, #0039a6 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            border-bottom: 3px solid var(--unicauca-amarillo);
        }
        
        .table thead th {
            background-color: var(--unicauca-azul);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--unicauca-amarillo);
        }
        
        .table-hover tbody tr {
            transition: all 0.1s ease;
        }
        
        .table-hover tbody tr:hover {
       
        }
        
        .status-icon {
            font-size: 1.25rem;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .status-icon:hover {
            transform: scale(1.15);
        }
        
        .status-accepted {
            color: var(--unicauca-verde);
            background-color: rgba(36, 147, 55, 0.15);
        }
        
        .status-rejected {
            color: var(--unicauca-rojo);
            background-color: rgba(229, 39, 36, 0.15);
        }
        
        .status-pending {
            color: var(--unicauca-amarillo);
            background-color: rgba(248, 174, 21, 0.15);
        }
        
        .status-info {
            color: var(--unicauca-azul-claro);
            background-color: rgba(22, 168, 225, 0.15);
        }
        
        .periodo-badge {
            background-color: var(--unicauca-amarillo);
            color: #333;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-unicauca {
            background-color: var(--unicauca-rojo);
            border-color: var(--unicauca-rojo);
            color: white;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0.5rem 1.2rem;
        }
        .btn-unicauca:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-unicauca-success {
            background-color: var(--unicauca-verde);
            border-color: var(--unicauca-verde);
            color: white;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0.5rem 1.2rem;
        }
        .btn-unicauca-success:hover {
            background-color: #1e7e34;
            border-color: #1c7430;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-unicauca-primary {
            background-color: var(--unicauca-primary-btn);
            color: white;
            border: 1px solid var(--unicauca-primary-btn);
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 0.5rem 1.2rem;
        }
        .btn-unicauca-primary:hover {
            background-color: var(--unicauca-primary-btn-hover);
            color: white;
            border: 1px solid var(--unicauca-primary-btn-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* MEJORAS PARA LOS CONTROLES DE DATATABLES */
        .dataTables_wrapper .row:first-child {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            background: white;
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 1rem;
        }

        .dataTables_wrapper .dataTables_length {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #555;
            flex-grow: 0;
        }
        
        .dataTables_wrapper .dataTables_length label {
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ddd;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
            margin: 0 0.5rem;
            font-size: 0.9rem;
            height: calc(1.5em + 0.75rem + 2px);
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        
        .dataTables_wrapper .dataTables_length select:focus {
            border-color: var(--unicauca-azul-claro);
            box-shadow: 0 0 0 0.25rem rgba(22, 168, 225, 0.25);
            outline: none;
            background-color: white;
        }

        /* CAMPO DE BÚSQUEDA MEJORADO */
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #555;
            flex-grow: 1;
            justify-content: flex-end;
            width: 100%;
        }
        
        .dataTables_wrapper .dataTables_filter label {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 30px;
            padding: 0.65rem 1.25rem 0.65rem 3rem;
            border: 1px solid #ddd;
            margin-left: 0.5rem;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            font-size: 0.95rem;
            height: calc(1.5em + 0.75rem + 2px);
            width: 100%;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--unicauca-azul-claro);
            box-shadow: 0 0 0 0.25rem rgba(22, 168, 225, 0.25);
            outline: none;
            background-color: white;
        }
        
        .dataTables_wrapper .dataTables_filter::before {
            content: "\f002";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            z-index: 10;
        }

        /* Ajuste para pantallas pequeñas */
        @media (max-width: 992px) {
            .card-header {
                flex-direction: column;
                text-align: center;
            }
            
            .card-header .d-flex {
                margin-top: 1rem;
                justify-content: center;
            }
            
            .periodo-badge {
                margin: 0.5rem auto;
            }
            
            .container-fluid {
                padding: 0 2%;
            }
        }
        
        @media (max-width: 768px) {
            .dataTables_wrapper .row:first-child {
                flex-direction: column;
                align-items: stretch;
                gap: 1.2rem;
            }
            
            .dataTables_wrapper .dataTables_length {
                width: 100%;
                justify-content: space-between;
            }
            
            .dataTables_wrapper .dataTables_filter {
                width: 100%;
            }
            
            .dataTables_wrapper .dataTables_filter label {
                max-width: 100%;
            }
            
            .table-responsive {
                border-radius: 10px;
                overflow: hidden;
                border: 1px solid rgba(0,0,0,0.05);
            }
        }

        .dt-buttons {
            display: none !important;
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 0.9rem;
            color: #666;
            padding-top: 0.75em;
            padding-left: 1rem;
        }
        
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 0.75em;
            padding-right: 1rem;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px;
            margin: 0 0.15rem;
            padding: 0.5rem 0.9rem;
            min-width: 36px;
            text-align: center;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 0.875rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: var(--unicauca-azul);
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background-color: var(--unicauca-azul);
            color: white !important;
            border-color: var(--unicauca-azul);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current) {
            background-color: var(--unicauca-azul-claro);
            color: white !important;
            border-color: var(--unicauca-azul-claro);
            transform: translateY(-1px);
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            background-color: #e9ecef;
            color: #adb5bd !important;
            border-color: #dee2e6;
            cursor: not-allowed;
        }

        .modal-xl {
            --bs-modal-width: 90vw;
        }
        
        .modal-body.p-0 iframe {
            height: 80vh;
        }
        
        .table-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            background: white;
        }
        
        .table > :not(:first-child) {
            border-top: 2px solid rgba(0,0,0,0.05);
        }
        
        .table td {
            vertical-align: middle;
            padding: 0.8rem 1rem;
        }
        
        .table th {
            padding: 1rem;
        }
        
        .points-cell {
            font-weight: 700;
            color: var(--unicauca-azul);
            font-size: 1.1rem;
        }
        
        .header-title {
            font-weight: 700;
            letter-spacing: 0.5px;
            position: relative;
            padding-left: 1.5rem;
        }
        
        .header-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 4px;
            background: var(--unicauca-amarillo);
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4 class="mb-0 header-title">
                        <i class="fas fa-users-cog me-2"></i>Gestión de Vinculación de Profesores Temporales
                    </h4>
                </div>
                <div class="d-flex align-items-center flex-wrap">
                    <span class="badge periodo-badge fs-6 py-2 px-3 me-3">
                        <i class="fas fa-calendar-alt me-2"></i>Periodo: <?= htmlspecialchars($anio_semestre) ?>
                    </span>
                    
                    <button id="exportExcelBtn" class="btn btn-unicauca-success btn-sm me-3" title="Exportar reporte completo a Excel">
                        <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                    </button>
                  
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-container">
                <table id="profesoresDatatable" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Facultad</th>
                            <th>Depto.</th>
                            <th>Sede</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Tipo Docente</th>
                            <th>Dedicación</th>
                            <th>Horas</th>
                            <th>Acep. Fac.</th>
                            <th>Acep. VRA</th>
                            <th>HV Nuevo</th>
                            <th>HV Antiguo</th>
                            <th>Puntos</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="powerBIModal" tabindex="-1" aria-labelledby="powerBIModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="powerBIModalLabel">Reporte Power BI de Vinculación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe
                        src="https://app.powerbi.com/view?r=eyJrIjoiNDg0ODNjODQtZGE3Mi00ZDMzLWFhMjUtM2FhMmMwZTNhMTI2IiwidCI6ImU4MjE0OTM3LTIzM2ItNGIzNi04NmJmLTBiNWYzMzM3YmVlMSIsImMiOjF9&pageName=282ee5a41849cca01e17"
                        frameborder="0"
                        allowfullscreen="true"
                        style="width: 100%; height: 75vh;">
                    </iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script type="text/javascript" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#profesoresDatatable').DataTable({
        "ajax": {
            "url": "<?= $data_url ?>",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "anio_semestre", "className": "text-center" },
            { "data": "NOMBREF_FAC" },
            { "data": "NOMBRE_DEPTO_CORT", "className": "text-center" },
            { "data": "sede", "className": "text-center" },
            { "data": "cedula", "className": "text-center" },
            { "data": "nombre" },
            { "data": "tipo_docente", "className": "text-center" },
            { "data": "dedicacion", "className": "text-center" },
            { "data": "horas", "className": "text-center" },
            // Acep. Fac. con íconos visuales
            { 
                "data": "acepta_fac_status",
                "render": function(data, type, row) {
                    let icon = '';
                    let color = '';
                    let title = '';
                    if (data === 'Aceptado') {
                        icon = 'fa-check-circle';
                        color = 'status-accepted';
                        title = '';
                    } else if (data === 'Rechazado') {
                        icon = 'fa-times-circle';
                        color = 'status-rejected';
                        title = '';
                    } else {
                        icon = 'fa-clock';
                        color = 'status-pending';
                        title = '';
                    }
                    return '<div class="d-flex align-items-center justify-content-center"><div class="status-icon ' + color + '"><i class="fas ' + icon + '"></i></div><span class="ms-2 d-none d-md-inline">' + title + '</span></div>';
                },
                "className": "text-center"
            },
            // Acep. VRA con íconos visuales
            { 
                "data": "acepta_vra_status",
                "render": function(data, type, row) {
                    let icon = '';
                    let color = '';
                    let title = '';
                    if (data === 'Aceptado') {
                        icon = 'fa-check-circle';
                        color = 'status-accepted';
                        title = '';
                    } else if (data === 'Rechazado') {
                        icon = 'fa-times-circle';
                        color = 'status-rejected';
                        title = '';
                    } else {
                        icon = 'fa-clock';
                        color = 'status-pending';
                        title = '';
                    }
                    return '<div class="d-flex align-items-center justify-content-center"><div class="status-icon ' + color + '"><i class="fas ' + icon + '"></i></div><span class="ms-2 d-none d-md-inline">' + title + '</span></div>';
                },
                "className": "text-center"
            },
            { 
                "data": "anexa_hv_docente_nuevo",
                "render": function(data, type, row) {
                    return data == 1 
                        ? '<div class="status-icon status-accepted"><i class="fas fa-check"></i></div>' 
                        : '<div class="status-icon status-rejected"><i class="fas fa-times"></i></div>';
                },
                "className": "text-center"
            },
            { 
                "data": "actualiza_hv_antiguo",
                "render": function(data, type, row) {
                    return data == 1 
                        ? '<div class="status-icon status-accepted"><i class="fas fa-check"></i></div>' 
                        : '<div class="status-icon status-rejected"><i class="fas fa-times"></i></div>';
                },
                "className": "text-center"
            },
            { 
                "data": "puntos", 
                "className": "text-center points-cell",
                "render": function(data) {
                    return data || '0';
                }
            }
        ],
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json"
        },
        "dom": '<"row"<"col-md-6"l><"col-md-6"f>><"row"<"col-sm-12"t>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "buttons": [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                titleAttr: 'Exportar a Excel',
                exportOptions: {
                    columns: ':visible'
                },
                filename: 'Reporte_Vinculacion_Temporales_<?= $anio_semestre ?>'
            }
        ],
        "searching": true,
        "paging": true,
        "ordering": true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
        "initComplete": function() {
            // Añadir margen superior a la tabla
            $('.dataTables_wrapper').css('margin-top', '1.5rem');
        }
    });

    $('#exportExcelBtn').on('click', function() {
        table.button('.buttons-excel').trigger();
    });

    // Enfocar automáticamente el campo de búsqueda al cargar la página
    $('.dataTables_filter input').focus();
    
    // Animación para las filas al cargar
    setTimeout(function() {
        $('tbody tr').each(function(i) {
            $(this).delay(50 * i).animate({
                opacity: 1
            }, 200);
        });
    }, 500);
});
</script>
</body>
</html>