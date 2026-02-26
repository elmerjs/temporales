<?php
// Configuración de menú y seguridad
$active_menu_item = 'novedades';
require('include/headerz.php'); 
require 'funciones.php';
require 'conn.php'; 

// 1. Recepción de parámetros
$departamento_id = $_GET['departamento_id'] ?? null;
$anio_semestre = $_GET['anio_semestre'] ?? null;
$id_acta = $_GET['id_acta'] ?? null; // Recibimos el ID si existe

// 2. CONSULTA INTELIGENTE DEL ACTA
$datos = []; // Inicializar vacío
if ($id_acta) {
    // CASO A: Editar una específica (la que viene en el botón)
    $sql_check = "SELECT * FROM actas_seleccion_novedades WHERE id_acta = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("i", $id_acta);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
// CASO B: Si no hay ID, $datos se queda vacío y el formulario aparece en blanco para crear una nueva.

// Funciones auxiliares locales
function obtenerIdFacultadLocal($departamento_id, $conn) {
    $sql = "SELECT FK_FAC FROM deparmanentos WHERE PK_DEPTO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['FK_FAC'] ?? null;
}

function obtenerDecanoFacultad($departamento_id, $conn) {
    $sql = "SELECT f.decano FROM facultad f
            INNER JOIN deparmanentos d ON f.PK_FAC = d.FK_FAC
            WHERE d.PK_DEPTO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res ? mb_strtoupper($res['decano'], 'UTF-8') : ""; 
}

// Función para normalizar texto (Búsquedas)
function prepararParaBusqueda($texto) {
    $buscar  = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
    $reemplazo = array('A', 'E', 'I', 'O', 'U', 'A', 'E', 'I', 'O', 'U', 'N', 'N');
    $texto = str_replace($buscar, $reemplazo, $texto);
    return strtoupper(trim($texto));
}

if (!$departamento_id || !$anio_semestre) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Parámetros insuficientes.</div></div>";
    exit;
}

// 3. Obtención de datos base para llenar el formulario
$nombre_depto = obtenerNombreDepartamento($departamento_id);
$jefe_actual = $profe_en_cargo; 
$decano_actual = obtenerDecanoFacultad($departamento_id, $conn);
$trd_depto = obtenerTRDDepartamento($departamento_id, $conn);

// Preparamos valores para mostrar
//$valor_acta_mostrar = $datos['numero_acta'] ?? ($trd_depto ? $trd_depto . '/' : '');
$valor_acta_mostrar = $datos['numero_acta'] ?? '';

// Decodificar JSONs
$compromisos = isset($datos['compromisos_json']) ? json_decode($datos['compromisos_json'], true) : [];
$perfiles = isset($datos['perfiles_json']) ? json_decode($datos['perfiles_json'], true) : [];

// --- LÓGICA DE SUGERENCIAS (MODAL) ---
// Ahora solo extraemos keywords de la columna "Perfil" (antes era 'enfasis' y 'formacion')
$sugerencias = []; // Dejamos el array vacío para que cargue rápido. Los datos vendrán por AJAX.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta Novedades - <?= htmlspecialchars($nombre_depto) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --unicauca-blue: #002A9E; --unicauca-red: #8B0000; }
        body { background-color: #f4f7f6; font-family: 'Open Sans', sans-serif; }
        .card-header-unicauca { background-color: var(--unicauca-blue); color: white; font-weight: bold; }
        .section-title { border-left: 5px solid var(--unicauca-red); padding-left: 15px; color: var(--unicauca-blue); }
        .instruccion-punto { font-size: 0.82rem; color: #666; font-style: italic; display: block; margin-bottom: 10px; }
        .ck-editor__editable { min-height: 150px; max-height: 300px; background-color: white !important; }
        .nav-tabs .nav-link { color: #555; font-weight: 600; font-size: 0.85rem; }
        .nav-tabs .nav-link.active { color: var(--unicauca-blue); border-bottom: 3px solid var(--unicauca-blue); }
        
        /* Asegura que el encabezado de la tabla siempre esté visible y encima de las filas */
        #tablaSugerenciasSist thead th {
            background-color: #f8f9fa; /* Gris claro */
            position: sticky;
            top: 0;
            z-index: 10; /* Fuerza a estar encima del contenido */
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1); /* Sombra sutil para distinguir */
        }
        
        /* Estilo para filas duplicadas */
        .fila-duplicada {
            background-color: #fff3cd !important;
            opacity: 0.7;
        }
        
        /* Estilo para la tabla de perfiles */
        .perfil-table th {
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .perfil-table td {
            vertical-align: middle;
        }
        .id-perfil-col {
            width: 120px;
            font-weight: bold;
        }
        .id-perfil-input {
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="section-title mb-0">PM-FO-4-FOR-59 Acta de Selección Docentes Temporales</h2>
            <small class="text-muted">Gestión de Vinculaciones (Novedades)</small>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="guardarYRegresar()">
            <i class="fas fa-arrow-left"></i> Guardar y Regresar a Novedades
        </button>
    </div>

    <form action="guardar_acta_novedades.php" method="POST" id="formActa">

        <input type="hidden" name="id_acta" value="<?= $id_acta ?>">
        
        <input type="hidden" name="departamento_id" value="<?= $departamento_id ?>">
        <input type="hidden" name="anio_semestre" value="<?= $anio_semestre ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-unicauca small">1. IDENTIFICACIÓN DE LA REUNIÓN</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Lugar</label>
                    <input type="text" name="lugar_reunion" class="form-control" value="<?= $datos['lugar_reunion'] ?? 'Oficina de Departamento' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Fecha</label>
                    <input type="date" name="fecha_reunion" class="form-control" value="<?= $datos['fecha_reunion'] ?? date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Serie, Subserie / No de acta</label>
                    <input type="text" name="numero_acta" class="form-control" 
       placeholder="Ej: 8.3.11-1.57/XX" 
       value="<?= htmlspecialchars($valor_acta_mostrar) ?>">
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header bg-white fw-bold text-primary border-bottom border-primary">DESARROLLO DE LA REUNIÓN</div>
            <div class="card-body">
                
                <div class="mb-4">
                    <label class="fw-bold text-dark small">1. Periodo académico</label>
                    <input type="text" class="form-control form-control-sm bg-light" style="width: 200px;" value="<?= htmlspecialchars($anio_semestre) ?>" readonly>
                </div>

              <div class="mb-4">
                <label class="fw-bold text-dark small mb-1">
                    2. Verificación de Asistencia de los integrantes del Comité de selección
                </label>

                <div class="text-muted fst-italic mb-2" style="font-size: 0.8rem; line-height: 1.3; text-align: justify;">
                    (Acuerdo Superior 017 de 2009, artículo 6: “…Se conformará un Comité de Selección de Docentes, que contará con cinco integrantes, a saber: el Decano o su delegado, el Jefe del Departamento, el Coordinador del programa y dos profesores de planta, preferiblemente del área, nombrados en reunión de Departamento…”)
                </div>

                <div class="p-3 border rounded bg-white shadow-sm">
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary mb-0">Decano o delegado:</label>
                            <input type="text" name="m1_nom" class="form-control form-control-sm" value="<?= $datos['miembro_1_nombre'] ?? $decano_actual ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary mb-0">Jefe de Departamento:</label>
                            <input type="text" name="m2_nom" class="form-control form-control-sm" value="<?= $datos['miembro_2_nombre'] ?? $jefe_actual ?>">
                        </div>
                    </div>
                    <div class="row g-3 align-items-end"> <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Coordinador de Programa</label>

                            <div class="alert alert-warning p-1 px-2 mb-1 border-warning text-dark" style="font-size: 0.65rem; line-height: 1.1;">
                                <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                Si el coordinador es un profesor <strong>ocasional</strong>, debe delegar a un profesor de <strong>planta</strong>.
                            </div>

                            <input type="text" name="m3_nom" class="form-control form-control-sm" 
                                   placeholder="Nombre del Coordinador o Delegado" 
                                   value="<?= $datos['miembro_3_nombre'] ?? '' ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Profesor Planta 1</label>
                            <div style="height: 38px;"></div> 
                            <input type="text" name="m4_nom" class="form-control form-control-sm" 
                                   placeholder="Nombre Profesor Planta" 
                                   value="<?= $datos['miembro_4_nombre'] ?? '' ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Profesor Planta 2</label>
                            <div style="height: 38px;"></div>
                            <input type="text" name="m5_nom" class="form-control form-control-sm" 
                                   placeholder="Nombre Profesor Planta" 
                                   value="<?= $datos['miembro_5_nombre'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

                <div class="mb-4">
                   <label class="fw-bold text-dark small mb-1">
                    3. Definición del Perfil o perfiles requeridos según la necesidad académica
                </label>

                <div class="text-muted fst-italic mb-2" style="font-size: 0.8rem;">
                    (Nivel Académico, Énfasis o Formación Particular y Experiencia)
                </div>

                <ul class="nav nav-tabs mb-2" id="perfilTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="tabla-tab" data-bs-toggle="tab" data-bs-target="#tabla" type="button">Tabla Estructurada</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="libre-tab" data-bs-toggle="tab" data-bs-target="#libre" type="button">Editor Libre</button>
                    </li>
                </ul>
                    <div class="tab-content border p-3 rounded bg-white shadow-sm">
                        <div class="tab-pane fade show active" id="tabla">
                            <table class="table table-bordered table-sm perfil-table" id="tablaPerfiles">
                                <thead class="table-light small text-center">
                                    <tr>
                                        <th class="id-perfil-col">Id_Perfil</th>
                                        <th>Perfil</th>
                                        <th>Nivel Máximo de Formación</th>
                                        <th>Experiencia</th>
                                        <th>Productividad</th>
                                        <th width="30"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($perfiles)): ?>
                                        <tr>
                                            <td class="id-perfil-col">
                                                <input type="text" name="perf_id[]" class="form-control form-control-sm id-perfil-input" value="Perfil 1">
                                            </td>
                                            <td><input type="text" name="perf_perfil[]" class="form-control form-control-sm" placeholder="Descripción específica del perfil"></td>
                                            <td>
                                                <select name="perf_nivel[]" class="form-control form-control-sm">
                                                    <option value="">Seleccionar...</option>
                                                    <option value="Doctorado">Doctorado</option>
                                                    <option value="Maestría">Maestría</option>
                                                    <option value="Especialización">Especialización</option>
                                                    <option value="Profesional">Profesional</option>
                                                    <option value="Tecnólogo">Tecnólogo</option>
                                                    <option value="Técnico">Técnico</option>
                                                </select>
                                            </td>
                                            <td><input type="text" name="perf_experiencia[]" class="form-control form-control-sm" placeholder="Ej: 5 años en docencia"></td>
                                            <td><input type="text" name="perf_productividad[]" class="form-control form-control-sm" placeholder="Ej: Publicaciones, investigaciones"></td>
                                            <td></td>
                                        </tr>
                                    <?php else: 
                                        $perfil_counter = 1;
                                        foreach($perfiles as $p): 
                                            // Mapeo de campos antiguos a nuevos si es necesario
                                            $id_perfil = $p['id_perfil'] ?? "Perfil $perfil_counter";
                                            $perfil = $p['perfil'] ?? $p['nombre'] ?? '';
                                            $nivel = $p['nivel'] ?? '';
                                            $experiencia = $p['experiencia'] ?? '';
                                            $productividad = $p['productividad'] ?? '';
                                    ?>
                                        <tr>
                                            <td class="id-perfil-col">
                                                <input type="text" name="perf_id[]" class="form-control form-control-sm id-perfil-input" value="<?= htmlspecialchars($id_perfil) ?>">
                                            </td>
                                            <td><input type="text" name="perf_perfil[]" class="form-control form-control-sm" value="<?= htmlspecialchars($perfil) ?>"></td>
                                            <td>
                                                <select name="perf_nivel[]" class="form-control form-control-sm">
                                                    <option value="">Seleccionar...</option>
                                                    <option value="Doctorado" <?= $nivel == 'Doctorado' ? 'selected' : '' ?>>Doctorado</option>
                                                    <option value="Maestría" <?= $nivel == 'Maestría' ? 'selected' : '' ?>>Maestría</option>
                                                    <option value="Especialización" <?= $nivel == 'Especialización' ? 'selected' : '' ?>>Especialización</option>
                                                    <option value="Profesional" <?= $nivel == 'Profesional' ? 'selected' : '' ?>>Profesional</option>
                                                    <option value="Tecnólogo" <?= $nivel == 'Tecnólogo' ? 'selected' : '' ?>>Tecnólogo</option>
                                                    <option value="Técnico" <?= $nivel == 'Técnico' ? 'selected' : '' ?>>Técnico</option>
                                                    <?php if ($nivel && !in_array($nivel, ['Doctorado', 'Maestría', 'Especialización', 'Profesional', 'Tecnólogo', 'Técnico'])): ?>
                                                        <option value="<?= htmlspecialchars($nivel) ?>" selected><?= htmlspecialchars($nivel) ?> (Personalizado)</option>
                                                    <?php endif; ?>
                                                </select>
                                            </td>
                                            <td><input type="text" name="perf_experiencia[]" class="form-control form-control-sm" value="<?= htmlspecialchars($experiencia) ?>"></td>
                                            <td><input type="text" name="perf_productividad[]" class="form-control form-control-sm" value="<?= htmlspecialchars($productividad) ?>"></td>
                                            <td><button type="button" class="btn btn-danger btn-sm p-0 px-1" onclick="removerFilaPerfil(this)">×</button></td>
                                        </tr>
                                    <?php 
                                        $perfil_counter++;
                                        endforeach; 
                                    endif; ?>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" onclick="agregarFilaPerfil()">+ Agregar Perfil</button>
                        </div>
                        <div class="tab-pane fade" id="libre">
                            <textarea name="punto_3_perfiles" id="editor3"><?= $datos['punto_3_perfiles'] ?? '' ?></textarea>
                        </div>
                    </div>
                </div>

             <div class="mb-4">
                <div class="d-flex justify-content-between align-items-end mb-2">

                    <div>
                        <label class="fw-bold text-dark small mb-1">
                            4. Consultar Banco de Aspirantes y revisar perfiles postulados al periodo a vincular
                        </label>
                        <div class="text-muted fst-italic" style="font-size: 0.8rem;">
                            (enlistar todos los profesores postulados que cumplen el perfil)
                        </div>
                    </div>

                    <button type="button" class="btn btn-xs btn-outline-primary shadow-sm ms-3" 
                            style="font-size: 0.75rem;" 
                            onclick="cargarSugerenciasActa()"> <i class="fas fa-search-plus me-1"></i> Listado sugerido de Aspirantes que cumplen el perfil
                    </button>
                </div>

                <textarea name="punto_4_aspirantes" id="editor4"><?= $datos['punto_4_aspirantes'] ?? '' ?></textarea>
                 
            </div>

            <div class="mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                    <div style="max-width: 70%;">
                        <label class="fw-bold text-dark small mb-1">5. Calificación de Hoja de Vida</label>
                        <div class="text-muted fst-italic text-justify" style="font-size: 0.8rem; line-height: 1.2;">
                            (Acuerdo Superior 017 de 2009, art 7, “…Será competencia de los Consejos de Facultad establecer los criterios y ponderaciones...”)
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2 mt-sm-0">
                        <button type="button" class="btn btn-sm btn-primary shadow-sm d-flex align-items-center" onclick="generarTablaEvaluacion()">
                            <i class="fas fa-sync-alt me-1"></i> Sincronizar aspirantes
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary shadow-sm d-flex align-items-center" onclick="calcularTotalesP5()">
                            <i class="fas fa-calculator me-1"></i> Calcular Totales
                        </button>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-2" role="alert" style="background-color: #e7f1ff; border-left: 4px solid #0d6efd;">
                    <i class="fas fa-info-circle me-1"></i> 
                    <strong>Sincronizar:</strong> Agregue aspirantes del punto 4. Use <strong>Calcular Totales</strong> para sumar automáticamente.
                </div>

                <textarea name="punto_5_calificacion" id="editor5"><?= $datos['punto_5_calificacion'] ?? '' ?></textarea>
                <div class="mt-2 text-danger fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-paperclip me-1"></i> 
                    NOTA: Adjuntar (en físico) formato individual de calificación de Hoja de Vida según Resolución del consejo de facultad.
                </div>
                
            </div>
                            <div class="mb-4">
                    <label class="fw-bold text-dark small">6. Entrevista</label>
                     <div class="text-muted fst-italic text-justify" style="font-size: 0.8rem; line-height: 1.2;">
                                (opcional)
                            </div>
                    <textarea name="punto_6_entrevista" id="editor6"><?= $datos['punto_6_entrevista'] ?? 'No aplica' ?></textarea>
                </div>

   <?php        
// --- PUNTO 7: CONSULTA SQL (NO BORRAR) ---
// Esta consulta busca los candidatos pendientes de "adicionar"

                // --- PUNTO 7: CONSULTA SQL (CORREGIDO SIN FORMS ANIDADOS) ---
                $sql_lista = "
                    SELECT s1.nombre, s1.cedula, s1.tipo_docente, s1.sede,
                           s1.tipo_dedicacion, s1.tipo_dedicacion_r, 
                           s1.horas, s1.horas_r
                    FROM solicitudes_working_copy s1
                    WHERE s1.departamento_id = ? 
                      AND s1.anio_semestre = ? 
                      AND s1.novedad = 'adicionar'
                      AND s1.estado_depto = 'PENDIENTE'
                      AND (s1.archivado = 0 OR s1.archivado IS NULL)
                      AND (s1.estado <> 'an' OR s1.estado IS NULL)
                      AND NOT EXISTS (
                          SELECT 1 FROM solicitudes_working_copy s2 
                          WHERE s2.cedula = s1.cedula 
                          AND s2.departamento_id = s1.departamento_id 
                          AND s2.anio_semestre = s1.anio_semestre 
                          AND s2.novedad = 'Eliminar' 
                          AND s2.estado_depto = 'PENDIENTE'
                      )
                    ORDER BY s1.tipo_docente DESC, s1.nombre ASC";

                $stmt_l = $conn->prepare($sql_lista);
                $stmt_l->bind_param("ss", $departamento_id, $anio_semestre);
                $stmt_l->execute();
                $res_lista = $stmt_l->get_result();
                $profesores_seleccionados = [];
                while($p = $res_lista->fetch_assoc()) {
                    $profesores_seleccionados[] = $p;
                }
                $total_candidatos = count($profesores_seleccionados);
                ?>

<div class="mb-4">
    <label class="fw-bold text-dark small mb-1">
        7. Selección de los profesores a solicitar vinculación
    </label>
    <div class="text-muted fst-italic mb-2" style="font-size: 0.8rem;">
        (enlistar todos los profesores que se van a solicitar para ser vinculados en el periodo académico, con nombre completo y número de identificación)
    </div>

    <div class="card border-info mb-4">
        <div class="card-header bg-info text-white small fw-bold">
            <i class="fas fa-users-cog me-2"></i> Candidatos Detectados (Novedades Pendientes)
        </div>
        <div class="card-body bg-light">
            <?php if ($total_candidatos == 0): ?>
                
                <div class="alert alert-warning border-0 shadow-sm text-center py-4">
                    <h6 class="text-danger fw-bold mb-3">
                        <i class="fas fa-info-circle fa-lg me-2"></i> Sin candidatos nuevos
                    </h6>
                    
                    <p class="small text-muted mb-4">
                        Actualmente no hay solicitudes de vinculación pendientes que requieran acta.<br>
                        Para agregar, diríjase al panel principal botón azul: 
                        <span class="bg-primary text-white fw-bold px-2 py-1 rounded shadow-sm" style="font-size: 0.85em;">
                            <i class="fas fa-plus-circle me-1"></i> solicitar profesor
                        </span>
                    </p>
                    
                    <button type="submit" name="accion" value="salir" class="btn btn-outline-primary btn-sm px-4 shadow-sm fw-bold" formnovalidate>
                        <i class="fas fa-save me-1"></i><i class="fas fa-external-link-alt me-2"></i> Ir a página principal-novedades
                    </button>
                    
                    <div class="mt-2 text-muted fst-italic" style="font-size: 0.75rem;">
                        (Sus cambios se guardarán automáticamente antes de salir)
                    </div>
                </div>

            <?php else: ?>
                
                <div class="alert alert-success border-0 shadow-sm mb-2 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle me-2"></i> <strong><?= $total_candidatos ?></strong> Candidatos listos para vincular.
                        </div>
                        
                        <button type="submit" name="accion" value="salir" class="btn btn-outline-primary btn-sm shadow-sm fw-bold px-3" formnovalidate>
                            <i class="fas fa-plus-circle me-2"></i> Insertar en la página principal
                        </button>
                    </div>
                </div>

                <div class="table-responsive bg-white border rounded shadow-sm">
                    <table class="table table-sm table-striped mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">#</th>
                                <th>Docente</th>
                                <th>Vinculación / Dedicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($profesores_seleccionados as $i => $prof): ?>
                                <?php 
                                $html_badges = '';
                                if ($prof['tipo_docente'] == 'Ocasional') {
                                    if (!empty($prof['tipo_dedicacion'])) $html_badges .= '<span class="badge bg-primary me-1">Oc. '.$prof['tipo_dedicacion'].'</span>';
                                    if (!empty($prof['tipo_dedicacion_r'])) $html_badges .= '<span class="badge bg-purple text-white" style="background-color: #6f42c1;">Oc. '.$prof['tipo_dedicacion_r'].' (Reg)</span>';
                                } elseif ($prof['tipo_docente'] == 'Catedra') {
                                    if (!empty($prof['horas']) && $prof['horas'] > 0) $html_badges .= '<span class="badge bg-secondary me-1">Cat. '.(float)$prof['horas'].'h</span>';
                                    if (!empty($prof['horas_r']) && $prof['horas_r'] > 0) $html_badges .= '<span class="badge bg-secondary border border-white" style="background-color: #5a6268;">Cat. '.(float)$prof['horas_r'].'h (Reg)</span>';
                                }
                                ?>
                                <tr>
                                    <td class="text-center align-middle"><?= $i + 1 ?></td>
                                    <td class="align-middle">
                                        <strong class="text-primary text-uppercase"><?= htmlspecialchars($prof['nombre']) ?></strong><br>
                                        <span class="text-muted"><i class="fas fa-id-card me-1"></i> <?= $prof['cedula'] ?></span>
                                    </td>
                                    <td class="align-middle"><?= $html_badges ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-text text-end mt-1 fst-italic x-small text-muted">
                    * El botón "Insertar" guardará su borrador y lo llevará al panel de gestión.
                </div>
            <?php endif; ?>
        </div>
    </div>
                </div></div></div>
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white small"> COMPROMISOS</div>
    <div class="card-body">
        <table class="table table-bordered table-sm" id="tablaCompromisos">
            <thead class="table-light small">
                <tr>
                    <th width="40">No.</th>
                    <th>Compromiso</th>
                    <th>Responsable</th>
                    <th>Fecha compromiso</th>
                    <th>Fecha realización</th>
                    <th width="30"></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $compromisos = $compromisos ?? [];
                $numRows = max(5, count($compromisos)); // Mínimo 5 filas visibles
                for ($i = 0; $i < $numRows; $i++):
                    $c = $compromisos[$i] ?? null;
                ?>
                <tr>
                    <td class="text-center align-middle"><?= $i + 1 ?></td>
                    <td><input type="text" name="comp_desc[]" class="form-control form-control-sm" value="<?= htmlspecialchars($c['desc'] ?? '') ?>"></td>
                    <td><input type="text" name="comp_resp[]" class="form-control form-control-sm" value="<?= htmlspecialchars($c['resp'] ?? '') ?>"></td>
                    <td><input type="date" name="comp_fecha_compromiso[]" class="form-control form-control-sm" value="<?= htmlspecialchars($c['fecha_compromiso'] ?? '') ?>"></td>
                    <td><input type="date" name="comp_fecha_realizacion[]" class="form-control form-control-sm" value="<?= htmlspecialchars($c['fecha_realizacion'] ?? '') ?>"></td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-danger btn-sm p-0 px-1" onclick="removerFilaCompromiso(this)">×</button>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-outline-dark btn-sm mt-1" onclick="agregarFilaCompromiso()">+ Agregar Compromiso</button>
    </div>
</div>
        <!-- Campo de observaciones -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white small"> OBSERVACIONES</div>
    <div class="card-body">
        <textarea name="observaciones" class="form-control" rows="3" placeholder="Observaciones generales..."><?= htmlspecialchars($datos['observaciones'] ?? '') ?></textarea>
    </div>
</div>

  <div class="d-flex gap-3 justify-content-center mt-5 mb-5">
    <button type="submit" name="accion" value="borrador" class="btn btn-warning px-5 fw-bold">
        <i class="fas fa-save me-2"></i>Guardar Borrador
    </button>
    
    <?php if ($id_acta): ?>
        <button type="button" 
                onclick="confirmarDescargaWord()" 
                class="btn btn-primary px-5 fw-bold shadow-sm" 
                title="Generar archivo Word">
            <i class="fas fa-file-word me-2"></i> Generar Word
        </button>
    <?php endif; ?>

           <?php if ($total_candidatos > 0): ?>
            <button type="submit" name="accion" value="finalizar" class="btn btn-success px-5 fw-bold" 
                    onclick="return confirmarFinalizacionConIntegridad();">
                <i class="fas fa-check-double me-2"></i>Finalizar Acta
            </button>
        <?php else: ?>
            <span data-bs-toggle="tooltip" title="Debe haber al menos 1 candidato nuevo para finalizar">
                <button type="button" class="btn btn-secondary px-5 fw-bold disabled">
                    <i class="fas fa-lock me-2"></i>Finalizar Acta
                </button>
            </span>
        <?php endif; ?>
</div>
    </form>
</div>

<div class="modal fade" id="modalSugerencias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white p-2 px-3">
                <h6 class="modal-title"><i class="fas fa-users me-2"></i> Aspirantes Sugeridos (Periodo <?= htmlspecialchars($anio_semestre) ?>)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                <div class="input-group input-group-sm mb-3 shadow-sm">
                    <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="txtBuscarUnificado" class="form-control" 
                           placeholder="Buscar en esta lista o traer por Cédula nueva..." 
                           onkeypress="if(event.keyCode==13) busquedaInteligente()">
                    <button class="btn btn-outline-primary" type="button" onclick="busquedaInteligente()">
                        <i class="fas fa-check me-1"></i> Verificar
                    </button>
                </div>
                <p class="x-small text-muted mb-2">Seleccione los aspirantes que desea importar al acta:</p>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-sm table-hover border" id="tablaSugerenciasSist">
                        <thead class="table-light sticky-top small">
                            <tr>
                                <th width="30" class="text-center align-middle">
                                    <input type="checkbox" id="checkAllSugerencias" class="form-check-input" style="cursor: pointer;" title="Seleccionar/Deseleccionar todos">
                                </th>
                                <th width="40" class="text-center">#</th>
                                <th>Nombre Completo</th>
                                <th>Títulos / Formación</th>
                                <th>Contacto</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if(empty($sugerencias)): ?>
                                <tr id="rowSinResultados"><td colspan="5" class="text-center py-3 text-muted">No se encontraron aspirantes sugeridos. Puede buscar manualmente.</td></tr>
                            <?php else: foreach ($sugerencias as $idx => $s): ?>
                                <tr data-cedula="<?= htmlspecialchars($s['documento_tercero']) ?>" data-nombre="<?= htmlspecialchars(strtoupper($s['nombre_completo'])) ?>">
                                    <td class="text-center align-middle"><input type="checkbox" class="form-check-input check-sug"></td>
                                    <td class="text-center fw-bold align-middle text-muted"><?= $idx + 1 ?></td>
                                    <td class="nom-sug align-middle">
                                        <div class="fw-bold text-uppercase"><?= htmlspecialchars($s['nombre_completo']) ?></div>
                                        <span class="text-muted x-small" style="font-size:0.7em"><i class="fas fa-id-card"></i> <?= htmlspecialchars($s['documento_tercero']) ?></span>
                                        <?php if ($s['en_mi_depto'] == 1): ?>
                                            <span class="badge bg-success" style="font-size: 0.65rem;">
                                                <i class="fas fa-check-circle"></i> POSTULADO EN DEPARTAMENTO
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">
                                                <i class="fas fa-search"></i> PERFIL AFÍN (NO POSTULADO)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="tit-sug text-muted align-middle" style="font-size: 0.72rem;"><?= nl2br(htmlspecialchars($s['asp_titulos'])) ?></td>
                                    <td class="small align-middle">
                                        <i class="fas fa-envelope text-muted"></i> <?= htmlspecialchars($s['asp_correo']) ?><br>
                                        <i class="fas fa-phone text-muted"></i> <?= htmlspecialchars($s['asp_celular']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer p-1">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="importarAspirantes()">Importar al Punto 4</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>
<script>
    function confirmarDescargaWord() {
    // Mensaje claro para el usuario
    const mensaje = "Recuerde que para generar el Word con la información actualizada, primero debe haber hecho clic en 'Guardar Borrador' o 'Finalizar Acta'.\n\n¿Desea descargar el documento con los últimos cambios guardados?";
    
    if (confirm(mensaje)) {
        // Si el usuario acepta, procedemos a la URL de descarga
        const urlWord = `generar_word_novedades.php?departamento_id=<?= urlencode($departamento_id) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>&id_acta=<?= $id_acta ?>`;
        window.location.href = urlWord;
    }
}
    function guardarYDescargarWord() {
    const form = document.getElementById('formActa'); // Asegúrate que tu <form id="formActa">
    const formData = new FormData(form);
    formData.append('accion', 'borrador'); 
    formData.append('ajax', '1'); 

    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';

    fetch('guardar_acta_novedades.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const urlWord = `generar_word_novedades.php?departamento_id=<?= urlencode($departamento_id) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>&id_acta=<?= $id_acta ?>`;
            window.location.href = urlWord;
        } else { alert('Error al guardar.'); }
    })
    .catch(error => { console.error('Error:', error); alert('Error en el guardado.'); })
    .finally(() => { 
        setTimeout(() => { btn.disabled = false; btn.innerHTML = originalHtml; }, 1500); 
    });
}
    
    // Función para guardar el acta y regresar al panel de novedades
function guardarYRegresar() {
    const form = document.getElementById('formActa');
    if (!form) {
        alert('Error: No se encontró el formulario.');
        return;
    }
    
    // Buscar o crear un campo oculto para 'accion'
    let accionField = form.querySelector('input[name="accion"]');
    if (!accionField) {
        accionField = document.createElement('input');
        accionField.type = 'hidden';
        accionField.name = 'accion';
        form.appendChild(accionField);
    }
    accionField.value = 'salir';
    
    // Enviar el formulario
    form.submit();
}
 // Función para recalcular totales manualmente (botón)
function calcularTotalesP5() {
    const editor = window.allEditors['#editor5'];
    if (!editor) {
        alert('El editor del Punto 5 no está disponible.');
        return;
    }
    recalcularTotalesP5(editor);
    // Notificación visual (opcional)
    const notificacion = document.createElement('div');
    notificacion.className = 'alert alert-success position-fixed top-0 end-0 m-3 shadow';
    notificacion.style.zIndex = '9999';
    notificacion.innerHTML = `<i class="fas fa-check-circle me-2"></i> Totales recalculados.`;
    document.body.appendChild(notificacion);
    setTimeout(() => notificacion.remove(), 2000);
}   
  // Función para recalcular totales en la tabla del Punto 5 (al perder foco) - VERSIÓN ROBUSTA
function recalcularTotalesP5(editor) {
    if (!editor) {
        console.log('recalcularTotalesP5: editor no válido');
        return;
    }
    const data = editor.getData();
    if (!data.includes('<table')) {
        console.log('recalcularTotalesP5: no hay tabla');
        return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(data, 'text/html');
    const tabla = doc.querySelector('table');
    if (!tabla) {
        console.log('recalcularTotalesP5: no se encontró tabla');
        return;
    }

    const thead = tabla.querySelector('thead');
    const tbody = tabla.querySelector('tbody');
    if (!thead || !tbody) {
        console.log('recalcularTotalesP5: no hay thead o tbody');
        return;
    }

    const filasCabecera = thead.querySelectorAll('tr');
    if (filasCabecera.length === 0) return;
    // Usamos la primera fila de encabezados
    const cabeceras = filasCabecera[0].querySelectorAll('th');

    // --- 1. DETECTAR COLUMNA "TOTAL" ---
    let indiceColumnaTotal = -1;
    cabeceras.forEach((th, idx) => {
        const texto = th.innerText.toLowerCase().trim();
        if (texto.includes('total')) {
            indiceColumnaTotal = idx;
            console.log(`Columna TOTAL encontrada en índice ${idx}`);
        }
    });
    if (indiceColumnaTotal === -1) {
        console.log('No se encontró columna TOTAL');
        return;
    }

    // --- 2. DETECTAR COLUMNA "NOMBRE" (puede llamarse "Nombre", "Aspirante", etc.) ---
    let indiceColumnaNombre = -1;
    cabeceras.forEach((th, idx) => {
        const texto = th.innerText.toLowerCase();
        if (texto.includes('nombre') || texto.includes('aspirante')) {
            indiceColumnaNombre = idx;
            console.log(`Columna NOMBRE encontrada en índice ${idx}`);
        }
    });
    if (indiceColumnaNombre === -1) {
        console.log('No se encontró columna NOMBRE, se usará la segunda columna como nombre');
        // Fallback: asumimos que la primera columna es número y la segunda es nombre
        indiceColumnaNombre = 1;
    }

    // --- 3. COLUMNAS DE VALOR: entre NOMBRE y TOTAL ---
    let indicesColumnasValor = [];
    for (let i = indiceColumnaNombre + 1; i < indiceColumnaTotal; i++) {
        indicesColumnasValor.push(i);
    }
    console.log('Índices de columnas de valor:', indicesColumnasValor);
    if (indicesColumnasValor.length === 0) {
        console.log('No hay columnas entre NOMBRE y TOTAL');
        return;
    }

    // --- 4. RECORRER FILAS Y CALCULAR SUMAS ---
    const filas = tbody.querySelectorAll('tr');
    let filasActualizadas = false;
    filas.forEach((fila, idxFila) => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length <= indiceColumnaTotal) {
            console.log(`Fila ${idxFila}: no tiene suficientes celdas`);
            return;
        }

        let suma = 0;
        indicesColumnasValor.forEach(idxCol => {
            if (celdas[idxCol]) {
                const valorTexto = celdas[idxCol].innerText.trim();
                // Extraer número (acepta punto decimal y coma como separador decimal)
                const numero = parseFloat(valorTexto.replace(/,/g, '.').replace(/[^\d.-]/g, ''));
                if (!isNaN(numero)) {
                    suma += numero;
                }
            }
        });

        // Formatear a máximo 3 decimales, sin ceros finales
        let sumaFormateada = suma.toFixed(3).replace(/\.?0+$/, '');
        if (sumaFormateada === '') sumaFormateada = '0';
        
        const celdaTotal = celdas[indiceColumnaTotal];
        if (celdaTotal.innerHTML !== sumaFormateada) {
            celdaTotal.innerHTML = sumaFormateada;
            filasActualizadas = true;
        }
    });

    // --- 5. ACTUALIZAR EDITOR SOLO SI HUBO CAMBIOS ---
    if (filasActualizadas) {
        const nuevoHTML = doc.body.innerHTML;
        editor.setData(nuevoHTML);
        console.log('Totales recalculados y editor actualizado');
    } else {
        console.log('No hubo cambios en los totales');
    }
}
    // 1. Inicialización de Editores
 // 1. Inicialización de Editores
// 1. Configuración global
const cfg = { 
    toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'insertTable', 'undo', 'redo'] 
};
window.allEditors = {}; 

// 2. Inicialización ÚNICA de Editores
document.addEventListener('DOMContentLoaded', function() {
    ['#editor3', '#editor4', '#editor5', '#editor6'].forEach(id => {
        const elemento = document.querySelector(id);
        if (elemento) {
            ClassicEditor.create(elemento, cfg)
            .then(editor => { 
                window.allEditors[id] = editor; 
                
                // Si es el editor del punto 5, activar la validación de integridad
                if (id === '#editor5') {
                    editor.model.document.on('change:data', () => {
                        verificarIntegridadP5yP7();
                    });
                    // Validación inicial tras un pequeño delay de carga
                    setTimeout(verificarIntegridadP5yP7, 1000);
                }
            })
            .catch(err => console.error('Error al cargar editor:', id, err));
        }
    });
});
 // 2. Función Agregar Perfil (Punto 3) - NUEVA ESTRUCTURA CON Id_Perfil
    function agregarFilaPerfil() {
        const tbody = document.querySelector('#tablaPerfiles tbody');
        const count = tbody.rows.length + 1;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="id-perfil-col">
                <input type="text" name="perf_id[]" class="form-control form-control-sm id-perfil-input" value="Perfil ${count}">
            </td>
            <td><input type="text" name="perf_perfil[]" class="form-control form-control-sm" placeholder="Descripción específica del perfil"></td>
            <td>
                <select name="perf_nivel[]" class="form-control form-control-sm">
                    <option value="">Seleccionar...</option>
                    <option value="Doctorado">Doctorado</option>
                    <option value="Maestría">Maestría</option>
                    <option value="Especialización">Especialización</option>
                    <option value="Profesional">Profesional</option>
                    <option value="Tecnólogo">Tecnólogo</option>
                    <option value="Técnico">Técnico</option>
                </select>
            </td>
            <td><input type="text" name="perf_experiencia[]" class="form-control form-control-sm" placeholder="Ej: 5 años en docencia"></td>
            <td><input type="text" name="perf_productividad[]" class="form-control form-control-sm" placeholder="Ej: Publicaciones, investigaciones"></td>
            <td><button type="button" class="btn btn-danger btn-sm p-0 px-1" onclick="removerFilaPerfil(this)">×</button></td>
        `;
        tbody.appendChild(tr);
        
        // Enfocar el campo del perfil para que el usuario pueda empezar a escribir
        setTimeout(() => {
            tr.querySelector('input[name="perf_perfil[]"]').focus();
        }, 100);
    }

    // Función para remover fila y reordenar Id_Perfil si es necesario
    function removerFilaPerfil(btn) {
        const tr = btn.closest('tr');
        tr.remove();
        // No reordenamos automáticamente los Id_Perfil porque el usuario puede haberlos personalizado
    }

    // Nueva Función: Agregar Compromiso (Punto 14)
    // Función Agregar Compromiso (con numeración automática)
// Función Agregar Compromiso (con numeración automática)
function agregarFilaCompromiso() {
    const tbody = document.querySelector('#tablaCompromisos tbody');
    const rowCount = tbody.rows.length + 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-center align-middle">${rowCount}</td>
        <td><input type="text" name="comp_desc[]" class="form-control form-control-sm"></td>
        <td><input type="text" name="comp_resp[]" class="form-control form-control-sm"></td>
        <td><input type="date" name="comp_fecha_compromiso[]" class="form-control form-control-sm"></td>
        <td><input type="date" name="comp_fecha_realizacion[]" class="form-control form-control-sm"></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-danger btn-sm p-0 px-1" onclick="removerFilaCompromiso(this)">×</button>
        </td>
    `;
    tbody.appendChild(tr);
}

// Función para remover fila y reordenar números
function removerFilaCompromiso(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    // Reordenar números de las filas restantes
    const tbody = document.querySelector('#tablaCompromisos tbody');
    tbody.querySelectorAll('tr').forEach((row, index) => {
        const tdNum = row.querySelector('td:first-child');
        if (tdNum) tdNum.textContent = index + 1;
    });
}
    // 3. Función Importar Aspirantes MEJORADA (Evita duplicados e incluye cédula)
    function importarAspirantes() {
        const editor = window.allEditors['#editor4'];
        if (!editor) {
            alert('Editor no encontrado');
            return;
        }
        
        // Obtener contenido actual del editor 4
        const contenidoActual = editor.getData();
        
        // Parsear contenido actual para extraer cédulas ya existentes
        const celdasExistentes = new Set();
        const nombresExistentes = new Set();
        
        // Crear un DOM temporal para analizar el contenido HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = contenidoActual;
        
        // Buscar todos los elementos <li> en el contenido actual
        const itemsActuales = tempDiv.querySelectorAll('li');
        itemsActuales.forEach(li => {
            const texto = li.innerText;
            // Extraer cédula si está en el formato "Cédula: XXXXX"
            const matchCedula = texto.match(/Cédula:\s*([\d\.]+)/i);
            if (matchCedula) {
                celdasExistentes.add(matchCedula[1].trim());
            }
            // Extraer nombre (antes de los dos puntos)
            const partes = texto.split(':');
            if (partes.length > 0) {
                const posibleNombre = partes[0].replace(/Cédula:\s*[\d\.]+/i, '').trim();
                if (posibleNombre) {
                    nombresExistentes.add(posibleNombre.toLowerCase());
                }
            }
        });
        
        let seleccionados = [];
        let duplicadosDetectados = [];
        
        document.querySelectorAll('#tablaSugerenciasSist tr').forEach(row => {
            const check = row.querySelector('.check-sug');
            if (check && check.checked) {
                const cedula = row.getAttribute('data-cedula');
                const nombreCompleto = row.getAttribute('data-nombre');
                
                // Verificar duplicados por cédula
                if (celdasExistentes.has(cedula)) {
                    row.classList.add('fila-duplicada');
                    duplicadosDetectados.push(`${nombreCompleto} (Cédula: ${cedula})`);
                    return; // Saltar este candidato
                }
                
                // Verificar duplicados por nombre (insensible a mayúsculas)
                const nombreSinAcentos = nombreCompleto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
                if (nombresExistentes.has(nombreSinAcentos)) {
                    row.classList.add('fila-duplicada');
                    duplicadosDetectados.push(`${nombreCompleto} (Cédula: ${cedula})`);
                    return; // Saltar este candidato
                }
                
                // Limpiar badges del nombre
                const celdaNombre = row.querySelector('.nom-sug').cloneNode(true);
                const badges = celdaNombre.querySelectorAll('.badge, span');
                badges.forEach(b => b.remove());
                const nombreLimpio = celdaNombre.innerText.trim();
                
                // Limpiar títulos
                const titulos = row.querySelector('.tit-sug').innerText.replace(/\n/g, " - ").trim();
                
                // Formato mejorado: Incluye cédula después del nombre
                seleccionados.push(`<li><strong>${nombreLimpio}</strong> (Cédula: ${cedula}): ${titulos}</li>`);
                
                // Agregar a los sets para evitar duplicados en la misma importación
                celdasExistentes.add(cedula);
                nombresExistentes.add(nombreSinAcentos);
            }
        });

        if (seleccionados.length > 0) {
            const nuevaLista = `<ol>${seleccionados.join('')}</ol>`;
            
            // Si ya hay contenido, agregar la nueva lista
            if (contenidoActual.trim()) {
                editor.setData(contenidoActual + nuevaLista);
            } else {
                editor.setData(nuevaLista);
            }
            
            editor.updateSourceElement();
            
            // Mostrar alerta si hubo duplicados
            if (duplicadosDetectados.length > 0) {
                setTimeout(() => {
                    alert(`Se omitieron ${duplicadosDetectados.length} candidatos que ya estaban en la lista:\n\n${duplicadosDetectados.join('\n')}`);
                }, 100);
            }
            
            // Cerrar modal
            const modalEl = document.getElementById('modalSugerencias');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            
            // AUTOGUARDADO SILENCIOSO
          /*  setTimeout(() => {
                const btnBorrador = document.querySelector('button[name="accion"][value="borrador"]');
                if(btnBorrador) {
                    btnBorrador.click();
                }
            }, 500);*/
        } else {
            if (duplicadosDetectados.length > 0) {
                alert(`Todos los candidatos seleccionados ya están en la lista. No se agregaron nuevos.`);
            } else {
                alert("Por favor, seleccione al menos un aspirante.");
            }
        }
    }

    // 4. Búsqueda Inteligente (Local y Remota)
    function busquedaInteligente() {
        const input = document.getElementById('txtBuscarUnificado');
        const termino = input.value.trim().toUpperCase();
        const periodo = document.querySelector('input[name="anio_semestre"]').value;

        if (!termino) { alert("Ingrese un nombre o cédula."); return; }

        const filas = document.querySelectorAll('#tablaSugerenciasSist tbody tr');
        let encontradoLocal = false;

        filas.forEach(fila => {
            if(fila.id === 'rowSinResultados') return;
            const cedula = fila.getAttribute('data-cedula');
            const nombre = fila.getAttribute('data-nombre');
            
            if ((cedula && cedula === termino) || (nombre && nombre.includes(termino))) {
                const checkbox = fila.querySelector('.check-sug');
                checkbox.checked = true;
                fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fila.classList.add('table-warning');
                setTimeout(() => fila.classList.remove('table-warning'), 2000);
                encontradoLocal = true;
            }
        });

        if (encontradoLocal) {
            input.value = ''; return;
        }

        const btn = input.nextElementSibling;
        const textoOriginal = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        fetch(`buscar_aspirante.php?cedula=${termino}&periodo=${periodo}`)
            .then(res => res.json())
            .then(data => {
                if (data.found) {
                    agregarFilaManual(data.data, termino);
                    input.value = '';
                } else {
                    alert(`El aspirante con cédula "${termino}" no se encuentra en la base de datos.`);
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                btn.innerHTML = textoOriginal;
                btn.disabled = false;
            });
    }

    // Función auxiliar para agregar fila manual (Al final de la tabla)
    function agregarFilaManual(aspirante, cedula) {
        const tbody = document.querySelector('#tablaSugerenciasSist tbody');
        const rowVacia = document.getElementById('rowSinResultados');
        if(rowVacia) rowVacia.remove();

        const count = tbody.querySelectorAll('tr').length + 1;
        const tr = document.createElement('tr');
        tr.className = "table-info";
        tr.setAttribute('data-cedula', cedula);
        tr.setAttribute('data-nombre', aspirante.nombre_completo.toUpperCase());

        tr.innerHTML = `
            <td class="text-center align-middle"><input type="checkbox" class="form-check-input check-sug" checked></td>
            <td class="text-center fw-bold align-middle text-muted">${count}</td>
            <td class="nom-sug align-middle">
                <div class="fw-bold text-uppercase">${aspirante.nombre_completo}</div>
                <span class="text-muted x-small"><i class="fas fa-id-card"></i> ${cedula}</span>
                <span class="badge bg-info text-dark" style="font-size:0.65rem">AGREGADO MANUAL</span>
            </td>
            <td class="tit-sug text-muted small">${aspirante.asp_titulos}</td>
            <td class="small"><i class="fas fa-envelope"></i> ${aspirante.asp_correo}<br><i class="fas fa-phone"></i> ${aspirante.asp_celular}</td>
        `;
        
        tbody.appendChild(tr);
        tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // 5. Generar Tabla Evaluación INTELIGENTE (Punto 5) - MEJORADO para manejar cédulas
    // 5. Generar Tabla Evaluación INTELIGENTE (Punto 5) - CON CÁLCULO AUTOMÁTICO DE TOTAL
// 5. Generar Tabla Evaluación INTELIGENTE (Punto 5) - SINCRONIZACIÓN COMPLETA (agrega nuevos, elimina los que ya no están)
function generarTablaEvaluacion() {
    // 1. Obtener lista de aspirantes del Punto 4
    const contenidoP4 = window.allEditors['#editor4'].getData();
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = contenidoP4;
    const items = tempDiv.querySelectorAll('li');
    
    let aspirantesP4 = [];
    items.forEach(li => {
        const texto = li.innerText;
        const strong = li.querySelector('strong');
        let nombre = strong ? strong.innerText : texto.split(':')[0];
        const matchCedula = texto.match(/Cédula:\s*([\d\.]+)/i);
        const cedula = matchCedula ? matchCedula[1] : '';
        nombre = nombre.trim();
        if (nombre) {
            aspirantesP4.push({
                nombre: nombre,
                cedula: cedula,
                textoCompleto: nombre + (cedula ? ` (Cédula: ${cedula})` : '')
            });
        }
    });

    if (aspirantesP4.length === 0) {
        alert("El Punto 4 está vacío. No hay aspirantes para sincronizar.");
        return;
    }

    // 2. Obtener contenido actual del Punto 5
    const contenidoP5 = window.allEditors['#editor5'].getData();

    // ---------------------------------------------------------
    // ESCENARIO A: YA EXISTE UNA TABLA (SINCRONIZAR)
    // ---------------------------------------------------------
    if (contenidoP5.includes('<table')) {
        
        const parser = new DOMParser();
        const docP5 = parser.parseFromString(contenidoP5, 'text/html');
        const tabla = docP5.querySelector('table');
        const thead = tabla.querySelector('thead');
        const tbody = tabla.querySelector('tbody');
        const filasExistentes = tbody.querySelectorAll('tr');

        // --- DETECTAR ESTRUCTURA DE COLUMNAS ---
        let indiceColumnaTotal = -1;
        let indicesColumnasValor = [];
        let indiceColumnaNombre = -1;
        let tieneColumnaNumero = false;
        const cabeceras = thead ? thead.querySelectorAll('th') : [];

        if (cabeceras.length > 0) {
            const primeraCabecera = cabeceras[0].innerText.toLowerCase();
            tieneColumnaNumero = primeraCabecera.includes('no') || 
                                 primeraCabecera.includes('núm') || 
                                 primeraCabecera.includes('#');

            cabeceras.forEach((th, idx) => {
                const texto = th.innerText.toLowerCase().trim();
                if (texto.includes('nombre') || texto.includes('aspirante')) {
                    indiceColumnaNombre = idx;
                }
                if (texto.includes('total')) {
                    indiceColumnaTotal = idx;
                }
            });
            
            if (indiceColumnaNombre !== -1 && indiceColumnaTotal !== -1 && indiceColumnaTotal > indiceColumnaNombre) {
                for (let i = indiceColumnaNombre + 1; i < indiceColumnaTotal; i++) {
                    indicesColumnasValor.push(i);
                }
            }
        }

        // --- 1. CONVERTIR FILAS EXISTENTES EN LISTA DE ASPIRANTES (CON CÉDULA) ---
        let aspirantesP5 = [];
        let filasPorCedula = new Map(); // para preservar las filas completas

        filasExistentes.forEach((tr, index) => {
            const celdas = tr.querySelectorAll('td');
            if (celdas.length < 2) return;

            let nombre = '';
            let cedula = '';
            let filaData = { elemento: tr, celdas: celdas };

            if (tieneColumnaNumero) {
                if (celdas[1]) {
                    const textoNombre = celdas[1].innerText.trim();
                    const matchCedula = textoNombre.match(/Cédula:\s*([\d\.]+)/i);
                    cedula = matchCedula ? matchCedula[1] : '';
                    nombre = textoNombre.replace(/\(Cédula:\s*[\d\.]+\)/i, '').trim();
                }
            } else {
                const textoPrimera = celdas[0].innerText.trim();
                const matchNombreCedula = textoPrimera.match(/^\d+\.?\s*(.*?)(?:\s*\(Cédula:\s*([\d\.]+)\))?$/);
                if (matchNombreCedula) {
                    nombre = (matchNombreCedula[1] || '').trim();
                    cedula = matchNombreCedula[2] || '';
                }
            }

            if (nombre) {
                aspirantesP5.push({ nombre, cedula, filaData });
                filasPorCedula.set(cedula || nombre, filaData);
            }
        });

        // --- 2. IDENTIFICAR NUEVOS Y ELIMINADOS ---
        const aspirantesP5Map = new Map(aspirantesP5.map(a => [a.cedula || a.nombre, a]));
        const aspirantesP4Map = new Map(aspirantesP4.map(a => [a.cedula || a.nombre, a]));

        const nuevosCandidatos = aspirantesP4.filter(asp => !aspirantesP5Map.has(asp.cedula || asp.nombre));
        const eliminadosCandidatos = aspirantesP5.filter(asp => !aspirantesP4Map.has(asp.cedula || asp.nombre));

        if (nuevosCandidatos.length === 0 && eliminadosCandidatos.length === 0) {
            alert("La tabla ya está sincronizada. No hay aspirantes nuevos ni eliminados.");
            return;
        }

        if (!confirm(`Se detectaron:\n• ${nuevosCandidatos.length} aspirante(s) nuevo(s)\n• ${eliminadosCandidatos.length} aspirante(s) eliminado(s)\n\n¿Desea sincronizar la tabla?`)) {
            return;
        }

        // --- 3. ELIMINAR FILAS DE ASPIRANTES QUE YA NO ESTÁN EN P4 ---
        eliminadosCandidatos.forEach(asp => {
            const key = asp.cedula || asp.nombre;
            const filaData = filasPorCedula.get(key);
            if (filaData && filaData.elemento) {
                filaData.elemento.remove();
            }
        });

        // --- 4. AGREGAR FILAS NUEVAS AL FINAL ---
        const filasRestantes = tbody.querySelectorAll('tr');
        let maxNumero = 0;
        filasRestantes.forEach(fila => {
            const primeraCelda = fila.querySelector('td:first-child');
            if (primeraCelda) {
                const match = primeraCelda.innerText.trim().match(/^(\d+)/);
                if (match) {
                    const num = parseInt(match[1]);
                    if (num > maxNumero) maxNumero = num;
                }
            }
        });

        nuevosCandidatos.forEach((aspirante, index) => {
            const nuevaFila = docP5.createElement('tr');
            if (tieneColumnaNumero) {
                const tdNumero = docP5.createElement('td');
                tdNumero.innerHTML = `<strong>${maxNumero + index + 1}</strong>`;
                nuevaFila.appendChild(tdNumero);
                
                const tdNombre = docP5.createElement('td');
                tdNombre.innerText = aspirante.textoCompleto;
                nuevaFila.appendChild(tdNombre);
                
                const columnasRestantes = cabeceras.length - 2;
                for (let i = 0; i < columnasRestantes; i++) {
                    const tdVacio = docP5.createElement('td');
                    tdVacio.innerHTML = '&nbsp;';
                    nuevaFila.appendChild(tdVacio);
                }
            } else {
                const primeraTd = docP5.createElement('td');
                primeraTd.innerHTML = `<strong>${maxNumero + index + 1}.</strong> ${aspirante.textoCompleto}`;
                nuevaFila.appendChild(primeraTd);
                
                const columnasRestantes = cabeceras.length - 1;
                for (let i = 0; i < columnasRestantes; i++) {
                    const tdVacio = docP5.createElement('td');
                    tdVacio.innerHTML = '&nbsp;';
                    nuevaFila.appendChild(tdVacio);
                }
            }
            tbody.appendChild(nuevaFila);
        });

        // --- 5. RENUMERAR TODAS LAS FILAS (si hay columna de número) ---
        if (tieneColumnaNumero) {
            const todasFilas = tbody.querySelectorAll('tr');
            let contador = 1;
            todasFilas.forEach(fila => {
                const primeraCelda = fila.querySelector('td:first-child');
                if (primeraCelda) {
                    primeraCelda.innerHTML = `<strong>${contador}</strong>`;
                    contador++;
                }
            });
        }

        // --- 6. RECALCULAR TOTALES SI HAY COLUMNA TOTAL ---
        if (indiceColumnaTotal !== -1 && indicesColumnasValor.length > 0) {
            const todasFilas = tbody.querySelectorAll('tr');
            todasFilas.forEach(fila => {
                const celdas = fila.querySelectorAll('td');
                if (celdas.length > indiceColumnaTotal) {
                    let suma = 0;
                    indicesColumnasValor.forEach(idx => {
                        if (celdas[idx]) {
                            const valorTexto = celdas[idx].innerText.trim();
                            const numero = parseFloat(valorTexto.replace(/,/g, '.').replace(/[^\d.-]/g, ''));
                            if (!isNaN(numero)) suma += numero;
                        }
                    });
                    let sumaFormateada = suma.toFixed(3).replace(/\.?0+$/, '');
                    if (sumaFormateada === '') sumaFormateada = '0';
                    celdas[indiceColumnaTotal].innerHTML = sumaFormateada;
                }
            });
        }

        // --- 7. ACTUALIZAR EDITOR ---
        window.allEditors['#editor5'].setData(docP5.body.innerHTML);
        alert(`Tabla sincronizada.\n✓ Agregados: ${nuevosCandidatos.length}\n✓ Eliminados: ${eliminadosCandidatos.length}`);

    } 
    // ---------------------------------------------------------
    // ESCENARIO B: NO HAY TABLA (CREAR NUEVA)
    // ---------------------------------------------------------
    else {
        const columnasInput = prompt(
            "Creando tabla nueva.\n¿Qué criterios desea evaluar? (Separados por comas).\n\nEj: Formación, Experiencia, Productividad, Total", 
            "Formación, Experiencia, Productividad, Total"
        );

        if (columnasInput === null) return;

        const columnas = columnasInput.split(',').map(c => c.trim());

        let tablaHtml = '<table border="1" cellspacing="0" cellpadding="4" style="border-collapse: collapse; width: 100%;">';
        
        tablaHtml += '<thead><tr>';
        tablaHtml += '<th style="background-color:#f2f2f2; padding: 6px; text-align: center; width: 50px;">No.</th>';
        tablaHtml += '<th style="background-color:#f2f2f2; padding: 6px;">Nombre del Aspirante</th>';
        
        columnas.forEach(col => {
            tablaHtml += `<th style="background-color:#f2f2f2; padding: 6px;">${col}</th>`;
        });
        
        tablaHtml += '</tr></thead><tbody>';

        aspirantesP4.forEach((aspirante, index) => {
            tablaHtml += `<tr>`;
            tablaHtml += `<td style="padding: 6px; text-align: center; font-weight: bold;">${index + 1}</td>`;
            tablaHtml += `<td style="padding: 6px;">${aspirante.textoCompleto}</td>`;
            
            columnas.forEach(() => {
                tablaHtml += '<td style="padding: 6px;">&nbsp;</td>';
            });
            
            tablaHtml += '</tr>';
        });

        tablaHtml += '</tbody></table>';
        
        window.allEditors['#editor5'].setData(tablaHtml);
    }
}
    
    // --- LÓGICA PARA EL CHECKBOX "SELECCIONAR TODOS" ---
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('checkAllSugerencias');
        
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const isChecked = this.checked;
                const checkboxes = document.querySelectorAll('#tablaSugerenciasSist .check-sug');
                
                checkboxes.forEach(cb => {
                    cb.checked = isChecked;
                    const fila = cb.closest('tr');
                    if (isChecked) {
                        fila.classList.add('table-active');
                    } else {
                        fila.classList.remove('table-active');
                        fila.classList.remove('fila-duplicada');
                    }
                });
            });
        }
    });
    // Función para cargar sugerencias SIN recargar la página
function cargarSugerenciasActa() {
    // 1. Abrir modal
    var myModal = new bootstrap.Modal(document.getElementById('modalSugerencias'));
    myModal.show();

    // 2. Mostrar "Cargando..."
    const tbody = document.querySelector('#tablaSugerenciasSist tbody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><br>Analizando aspirantes postulados y perfiles...</td></tr>';

    // 3. Obtener textos de los perfiles (inputs)
    let perfilesTexto = [];
    document.querySelectorAll('input[name="perf_perfil[]"]').forEach(input => {
        if(input.value.trim()) perfilesTexto.push(input.value.trim());
    });

    // 4. Pedir datos al servidor (AJAX)
    const formData = new FormData();
    formData.append('departamento_id', '<?= $departamento_id ?>');
    formData.append('anio_semestre', '<?= $anio_semestre ?>');
    formData.append('perfiles_busqueda', JSON.stringify(perfilesTexto));

    fetch('ajax_buscar_sugerencias.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        tbody.innerHTML = html; // Poner los resultados en la tabla
    })
    .catch(error => {
        console.error('Error:', error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error de conexión.</td></tr>';
    });
}
    
    
    /**
 * Función Maestra de Validación de Integridad
 * Escanea el editor P5 en busca de las cédulas del listado P7
 */
function verificarIntegridadP5yP7() {
    const editorP5 = window.allEditors['#editor5'];
    if (!editorP5) return;

    // 1. Limpiar el contenido del editor para la búsqueda (quitar HTML y normalizar)
    const contenidoP5 = editorP5.getData()
        .replace(/<[^>]*>/g, '')      // Quitar etiquetas
        .replace(/&nbsp;/g, ' ')      // Espacios HTML
        .replace(/\s+/g, '');         // Eliminar espacios para búsqueda exacta

    // 2. Localizar las filas de la tabla del Punto 7
    // Buscamos dentro de la tabla de candidatos detectados
    const filasCandidatos = document.querySelectorAll('.card.border-info table tbody tr');

    filasCandidatos.forEach(fila => {
        // Extraer la cédula usando Regex (secuencia de números larga)
        const textoFila = fila.innerText;
        const matchCedula = textoFila.match(/\d{5,15}/);

        if (matchCedula) {
            const cedula = matchCedula[0];
            const celdaNombre = fila.cells[1]; // Columna del nombre del docente

            // 3. Comparar cédula contra el contenido del editor
            if (!contenidoP5.includes(cedula)) {
                // Si NO está, creamos la alerta si no existe
                if (!fila.querySelector('.alerta-p5')) {
                    const alerta = document.createElement('span');
                    alerta.className = 'alerta-p5 badge bg-danger text-white ms-2 animate__animated animate__fadeIn';
                    alerta.style.fontSize = '0.65rem';
                    alerta.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No calificado en P5';
                    alerta.title = "Esta identificación no se encontró en el cuadro de calificación del Punto 5";
                    celdaNombre.appendChild(alerta);
                }
            } else {
                // Si YA está, removemos la alerta si existía
                const alertaExistente = fila.querySelector('.alerta-p5');
                if (alertaExistente) alertaExistente.remove();
            }
        }
    });
}

    function confirmarFinalizacionConIntegridad() {
    // 1. Verificar si existen alertas de integridad (los badges rojos)
    const alertas = document.querySelectorAll('.alerta-p5');
    
    if (alertas.length > 0) {
        // El sistema detectó que hay seleccionados sin calificar
        return confirm('⚠️ ATENCIÓN: Hay docentes seleccionados que no aparecen calificados en el Punto 5.\n\n¿Está seguro de que desea finalizar el acta de todas formas?');
    }

    // 2. Si todo está correcto, pedir la confirmación normal
    return confirm('¿Está seguro de FINALIZAR el Acta de Novedades?\n\nEsto dejará el documento listo para impresión.');
}
</script>
</body>
</html>