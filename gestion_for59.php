<?php
// Configuración de menú y seguridad
$active_menu_item = 'gestion_depto';
require('include/headerz.php'); 
require 'funciones.php';
require 'conn.php'; 

// 1. Recepción de parámetros y seguridad
$departamento_id = $_GET['departamento_id'] ?? null;
$anio_semestre = $_GET['anio_semestre'] ?? null;

function obtenerIdFacultadLocal($departamento_id, $conn) {
    $sql = "SELECT FK_FAC FROM deparmanentos WHERE PK_DEPTO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['FK_FAC'];
    }
    return null; 
}
// Función para obtener el Decano dinámicamente
function obtenerDecanoFacultad($departamento_id, $conn) {
    $sql = "SELECT f.decano 
            FROM facultad f
            INNER JOIN deparmanentos d ON f.PK_FAC = d.FK_FAC
            WHERE d.PK_DEPTO = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // mb_strtoupper convierte a mayúsculas respetando tildes (ej: 'Sebastián' -> 'SEBASTIÁN')
        return mb_strtoupper($row['decano'], 'UTF-8');
    }
    return ""; 
}
// Función para obtener el TRD del Departamento

if (!$departamento_id || !$anio_semestre) {
    echo "<div class='alert alert-danger'>Parámetros insuficientes.</div>";
    exit;
}

// 2. Obtención de datos
$nombre_depto = obtenerNombreDepartamento($departamento_id);
$nombre_facultad = obtenerNombreFacultadcort($departamento_id);
$jefe_actual = $profe_en_cargo; 
$decano_actual = obtenerDecanoFacultad($departamento_id, $conn);
$trd_depto = obtenerTRDDepartamento($departamento_id, $conn);


// 3. Consultar acta existente
$sql_check = "SELECT * FROM actas_seleccion_docente WHERE departamento_id = ? AND anio_semestre = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ss", $departamento_id, $anio_semestre);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 4. Conteo de visados


// Decodificar JSONs
$compromisos = isset($datos['compromisos_json']) ? json_decode($datos['compromisos_json'], true) : [];
$perfiles = isset($datos['perfiles_json']) ? json_decode($datos['perfiles_json'], true) : [];

// 1. Función para normalizar texto (Quitar tildes y pasar a MAYÚSCULAS)
function prepararParaBusqueda($texto) {
    $buscar  = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
    $reemplazo = array('A', 'E', 'I', 'O', 'U', 'A', 'E', 'I', 'O', 'U', 'N', 'N');
    $texto = str_replace($buscar, $reemplazo, $texto);
    return strtoupper(trim($texto));
}

// 2. Preparamos términos de búsqueda
$valor_acta_mostrar = $datos['numero_acta'] ?? ($trd_depto ? $trd_depto . '/' : '');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión FOR-59 - <?= htmlspecialchars($nombre_depto) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
                :root { --unicauca-blue: #002A9E; --unicauca-red: #8B0000; }
                body { background-color: #f4f7f6; font-family: 'Open Sans', sans-serif; }
                .card-header-unicauca { background-color: var(--unicauca-blue); color: white; font-weight: bold; }
                .ck-editor__editable { min-height: 200px; max-height: 400px; background-color: white !important; }
                .section-title { border-left: 5px solid var(--unicauca-red); padding-left: 15px; color: var(--unicauca-blue); }
                .instruccion-punto { font-size: 0.82rem; color: #666; font-style: italic; display: block; margin-bottom: 10px; }
                .nav-tabs .nav-link { color: #555; font-weight: 600; font-size: 0.85rem; }
                .nav-tabs .nav-link.active { color: var(--unicauca-blue); border-bottom: 3px solid var(--unicauca-blue); }
                #tablaSugerenciasSist thead th { 
            position: sticky;
            top: 0;
            background-color: #f8f9fa; 
            z-index: 1050; /* Z-Index alto para ganar la pelea de capas */
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
            border-bottom: 2px solid #dee2e6;
        }
        .id-perfil-col {
    width: 120px;
    font-weight: bold;
}
.id-perfil-input {
    font-weight: bold;
    text-align: center;
}
.perfil-table th {
    font-size: 0.8rem;
    white-space: nowrap;
}
.perfil-table td {
    vertical-align: middle;
}
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title">Acta de Selección de Profesores (PM-FO-4-FOR-59)</h2>
        <form action="consulta_todo_depto.php" method="POST">
            <input type="hidden" name="facultad_id" value="<?= obtenerIdFacultadLocal($departamento_id, $conn) ?>">
            <input type="hidden" name="departamento_id" value="<?= $departamento_id ?>">
            <input type="hidden" name="anio_semestre" value="<?= $anio_semestre ?>">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="guardarYRegresar()">
                <i class="fas fa-arrow-left"></i> Guardar y Regresar
            </button>
        </form>
    </div>

    <form action="guardar_for59.php" method="POST" id="formActa">

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
                           >
                 
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
                        <div class="row g-3 align-items-end"> 
                            <div class="col-md-4">
                                <label class="form-label small fw-bold mb-1">Coordinador de Programa</label>
                                <div class="alert alert-warning p-1 px-2 mb-1 border-warning text-dark" style="font-size: 0.65rem; line-height: 1.1;">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                                    Si el coordinador es un profesor <strong>ocasional</strong>, debe delegar a un profesor de <strong>planta</strong>.
                                </div>
                                <input type="text" name="m3_nom" class="form-control form-control-sm" placeholder="Nombre del Coordinador o Delegado" value="<?= $datos['miembro_3_nombre'] ?? '' ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold mb-1">Profesor Planta 1</label>
                                <div style="height: 38px;"></div> <input type="text" name="m4_nom" class="form-control form-control-sm" placeholder="Nombre Profesor Planta" value="<?= $datos['miembro_4_nombre'] ?? '' ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold mb-1">Profesor Planta 2</label>
                                <div style="height: 38px;"></div>
                                <input type="text" name="m5_nom" class="form-control form-control-sm" placeholder="Nombre Profesor Planta" value="<?= $datos['miembro_5_nombre'] ?? '' ?>">
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
                    <button class="nav-link active text-primary fw-bold" id="tabla-tab" data-bs-toggle="tab" data-bs-target="#tabla" type="button" role="tab">
                        <i class="fas fa-table me-1"></i> Usar Tabla Estructurada
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="libre-tab" data-bs-toggle="tab" data-bs-target="#libre" type="button" role="tab">Editor Libre</button>
                </li>
            </ul>

            <div class="tab-content border p-3 rounded bg-white shadow-sm">
                <div class="tab-pane fade show active" id="tabla" role="tabpanel">
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

                <div class="tab-pane fade" id="libre" role="tabpanel">
                    <textarea name="punto_3_perfiles" id="editor3"><?= $datos['punto_3_perfiles'] ?? '' ?></textarea>
                </div>
            </div>
        </div>
               <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-bold text-dark small mb-1">
                            4. Consultar Banco de Aspirantes y revisar perfiles postulados al periodo a vincular
                        </label>
                        <div class="text-muted fst-italic mb-2" style="font-size: 0.8rem;">
                            (enlistar todos los profesores postulados que cumplen el perfil)
                        </div>
                      <button type="button" class="btn btn-xs btn-outline-primary" onclick="cargarSugerenciasFor59()">
                            <i class="fas fa-search-plus"></i> Sugerencias del Sistema
                        </button>
                    </div>
                    
                    <textarea name="punto_4_aspirantes" id="editor4"><?= $datos['punto_4_aspirantes'] ?? '' ?></textarea>
                </div>
                
                <!--Calificacion  hoja de vida  con  sincronizacion-->
                <div class="mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                        <div style="max-width: 70%;">
                            <label class="fw-bold text-dark small mb-1">
                                5. Calificación de Hoja de Vida
                            </label>
                            <div class="text-muted fst-italic text-justify" style="font-size: 0.8rem; line-height: 1.2;">
                                (Acuerdo Superior 017 de 2009, art 7, “…Será competencia de los Consejos de Facultad establecer los criterios y ponderaciones para las calificaciones de las hojas de vida y los porcentajes para pruebas adicionales, si los hubiera…”.)
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <button type="button" class="btn btn-sm btn-primary shadow-sm d-flex align-items-center" 
                                    onclick="generarTablaEvaluacion()">
                                <i class="fas fa-sync-alt me-1"></i> Sincronizar aspirantes
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary shadow-sm d-flex align-items-center" 
                                    onclick="calcularTotalesP5()">
                                <i class="fas fa-calculator me-1"></i> Calcular Totales
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-2" role="alert" style="background-color: #e7f1ff; border-left: 4px solid #0d6efd;">
                        <i class="fas fa-info-circle me-1"></i> 
                        <strong>Sincronizar:</strong> crea la tabla o agrega solo los aspirantes nuevos del punto 4 (los existentes no se pierden).  
                        Luego ingrese valores y presione <strong>Calcular Totales</strong> para sumar automáticamente.
                    </div>
                    <textarea name="punto_5_calificacion" id="editor5"><?= $datos['punto_5_calificacion'] ?? '' ?></textarea>
                    <div class="mt-2 text-danger fw-bold" style="font-size: 0.85rem;">
                    <i class="fas fa-paperclip me-1"></i> 
                    NOTA: Adjuntar formato individual de calificación de Hoja de Vida según Resolución del Consejo de Facultad.
                </div>
                </div>
                <div class="mb-4">
                    <label class="fw-bold text-dark small">6. Entrevista (Opcional)</label>
                    <textarea name="punto_6_entrevista" id="editor6"><?= $datos['punto_6_entrevista'] ?? 'No aplica' ?></textarea>
                </div>

<?php
// --- 1. CONSULTA DE PROFESORES SELECCIONADOS ---
// CAMBIO CLAVE: ORDER BY tipo_docente DESC (Ocasional va antes que Catedra), nombre ASC

?>

<label class="fw-bold text-dark small mb-1">
    7. Selección de los profesores a solicitar vinculación
</label>
<div class="text-muted fst-italic mb-2" style="font-size: 0.8rem;">
    (enlistar todos los profesores que se van a solicitar para ser vinculados en el periodo académico, con nombre completo y número de identificación)
</div>

<div id="contenedorProfesoresFor59"></div>  
            </div>
        </div>

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
        <div class="d-flex gap-3 justify-content-center mt-5 mb-5 flex-wrap">
    <?php if (!empty($datos) && isset($datos['numero_acta'])): ?>
        <button type="button" 
                onclick="confirmarDescargaWord()" 
                class="btn btn-sm btn-outline-primary fw-bold shadow-sm d-flex align-items-center gap-2 px-4"
                title="Generar archivo Word">
            <i class="fas fa-file-word"></i> 
            <span>Generar Word</span>
        </button>
    <?php endif; ?>
    
    <button type="submit" name="accion" value="borrador" 
            class="btn btn-sm btn-warning fw-bold shadow-sm d-flex align-items-center gap-2 px-4">
        <i class="fas fa-save"></i> Guardar Borrador
    </button>
    
    <button type="submit" name="accion" value="finalizar" 
            class="btn btn-sm btn-success fw-bold shadow-sm d-flex align-items-center gap-2 px-4"   
            onclick="return confirm('¿Está seguro de FINALIZAR el Acta?');">
        <i class="fas fa-check-double"></i> Finalizar Acta
    </button>
</div>
    </form>
</div>

<script src="https://cdn.ckeditor.com/ckeditor5/34.0.0/classic/ckeditor.js"></script>
<script>
// ============================================================================
// CONFIGURACIÓN GLOBAL Y VARIABLES
// ============================================================================
const cfg = { toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'insertTable', 'undo', 'redo'] };
window.allEditors = {};

// ============================================================================
// FUNCIÓN: confirmarDescargaWord
// ============================================================================
function confirmarDescargaWord() {
    const mensaje = "Recuerde que para generar el Word con la información actualizada, primero debe haber hecho clic en 'Guardar Borrador' o 'Finalizar Acta'.\n\n¿Desea descargar el documento con los últimos cambios guardados?";
    if (confirm(mensaje)) {
        const url = `generar_word_for59.php?departamento_id=<?= urlencode($departamento_id) ?>&anio_semestre=<?= urlencode($anio_semestre) ?>`;
        window.location.href = url;
    }
}

// ============================================================================
// FUNCIÓN: importarAspirantes (MODIFICADA: incluye cédula en formato (Cédula: 123456))
// ============================================================================
function importarAspirantes() {
    let seleccionados = [];
    
    // 1. Obtener el contenido actual del Punto 4 para verificar duplicados
    const editor = window.allEditors['#editor4'];
    const contenidoActual = editor.getData();
    
    // 2. Extraer las cédulas que ya están en el Punto 4 (mejorado)
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = contenidoActual;
    const itemsExistentes = tempDiv.querySelectorAll('li');
    
    // Crear un Set de cédulas existentes (para búsqueda rápida)
    const cedulasExistentes = new Set();
    itemsExistentes.forEach(li => {
        const texto = li.innerText;
        // Buscar cualquier número de 6 a 10 dígitos (asumiendo formato de cédula colombiana)
        const matchCedula = texto.match(/\b(\d{6,10})\b/g);
        if (matchCedula) {
            matchCedula.forEach(cedula => {
                cedulasExistentes.add(cedula.trim());
            });
        }
    });
    
    // 3. Recorrer los aspirantes seleccionados en el modal
    let hayNuevos = false;
    let nuevosAgregados = 0;
    let duplicadosIgnorados = 0;
    
    document.querySelectorAll('#tablaSugerenciasSist tr').forEach(row => {
        const check = row.querySelector('.check-sug');
        
        if (check && check.checked) {
            // Obtener cédula del aspirante (del atributo data-cedula)
            const cedula = row.getAttribute('data-cedula');
            
            // Verificar si ya existe por cédula
            if (cedula && cedulasExistentes.has(cedula)) {
                duplicadosIgnorados++;
                return; // Ya existe, no lo agregamos (sin alerta)
            }
            
            // Si no tiene cédula en atributo, intentar extraer del contenido
            let cedulaParaVerificar = cedula;
            if (!cedulaParaVerificar) {
                const cedulaElement = row.querySelector('.text-muted.x-small');
                if (cedulaElement) {
                    const match = cedulaElement.textContent.match(/\b(\d{6,10})\b/);
                    if (match && match[1]) {
                        cedulaParaVerificar = match[1];
                        if (cedulasExistentes.has(cedulaParaVerificar)) {
                            duplicadosIgnorados++;
                            return; // Ya existe
                        }
                    }
                }
            }
            
            // Es nuevo, proceder a agregar
            hayNuevos = true;
            nuevosAgregados++;
            
            // Obtener nombre limpio (sin badges ni cédula)
            const nombreElement = row.querySelector('.nom-sug div.fw-bold');
            const nombreLimpio = nombreElement ? nombreElement.innerText.trim() : '';
            
            // Obtener títulos
            const titulos = row.querySelector('.tit-sug')?.innerText.replace(/\n/g, " - ").trim() || '';
            
            // Crear el ítem de la lista CON cédula en el formato requerido
            let itemTexto = '<li><strong>' + nombreLimpio + '</strong> (Cédula: ' + cedulaParaVerificar + '): ' + titulos + '</li>';
            seleccionados.push(itemTexto);
            
            // Agregar la cédula al Set para evitar duplicados en esta misma importación
            if (cedulaParaVerificar) {
                cedulasExistentes.add(cedulaParaVerificar);
            }
        }
    });

    if (seleccionados.length > 0) {
        const contenidoActual = editor.getData();
        
        // Determinar si ya hay una lista ordenada (<ol>) o desordenada (<ul>)
        let nuevoContenido = contenidoActual;
        
        if (contenidoActual.includes('<ol>') || contenidoActual.includes('<ul>')) {
            // Insertar al final de la lista existente (antes del cierre de </ol> o </ul>)
            if (contenidoActual.includes('</ol>')) {
                nuevoContenido = contenidoActual.replace('</ol>', seleccionados.join('') + '</ol>');
            } else if (contenidoActual.includes('</ul>')) {
                nuevoContenido = contenidoActual.replace('</ul>', seleccionados.join('') + '</ul>');
            } else {
                // Si no encuentra cierre, agregar al final
                nuevoContenido = contenidoActual + '<ol>' + seleccionados.join('') + '</ol>';
            }
        } else {
            // No hay lista existente, crear una nueva
            nuevoContenido = contenidoActual + '<ol>' + seleccionados.join('') + '</ol>';
        }
        
        editor.setData(nuevoContenido);
        
        // Cerrar modal
        const modalElement = document.getElementById('modalSugerencias');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) modalInstance.hide();

        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 300);

        // Desmarcar checkboxes
        document.querySelectorAll('.check-sug').forEach(c => c.checked = false);
        
        // Desmarcar "Seleccionar todos"
        const checkAll = document.getElementById('checkAllSugerencias');
        if (checkAll) checkAll.checked = false;
        
        // Actualizar marcas en punto 7
        marcarNoCalificadosEnPunto7For59();
        
    } else if (hayNuevos) {
        // Caso especial: se seleccionaron pero todos eran duplicados
        // Cerrar modal sin mensaje
        const modalElement = document.getElementById('modalSugerencias');
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (modalInstance) modalInstance.hide();

        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 300);
        
        // Limpiar selecciones
        document.querySelectorAll('.check-sug').forEach(c => c.checked = false);
        const checkAll = document.getElementById('checkAllSugerencias');
        if (checkAll) checkAll.checked = false;
    }
}

// ============================================================================
// FUNCIONES AUXILIARES DE TABLAS DINÁMICAS (conservadas)
// ============================================================================
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
    setTimeout(() => {
        tr.querySelector('input[name="perf_perfil[]"]').focus();
    }, 100);
}
function removerFilaPerfil(btn) {
    const tr = btn.closest('tr');
    tr.remove();
}
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
function removerFilaCompromiso(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    const tbody = document.querySelector('#tablaCompromisos tbody');
    tbody.querySelectorAll('tr').forEach((row, index) => {
        const tdNum = row.querySelector('td:first-child');
        if (tdNum) tdNum.textContent = index + 1;
    });
}
function renumerarTablaExistente() {
    const contenidoP5 = window.allEditors['#editor5'].getData();
    if (!contenidoP5.includes('<table')) {
        alert("No hay tabla para renumerar.");
        return;
    }
    const parser = new DOMParser();
    const docP5 = parser.parseFromString(contenidoP5, 'text/html');
    const tabla = docP5.querySelector('table');
    const tbody = tabla.querySelector('tbody');
    const filas = tbody.querySelectorAll('tr');
    let contador = 1;
    filas.forEach(fila => {
        const primeraCelda = fila.querySelector('td:first-child');
        if (primeraCelda) {
            primeraCelda.innerHTML = '<strong>' + contador + '</strong>';
            contador++;
        }
    });
    window.allEditors['#editor5'].setData(docP5.body.innerHTML);
    alert('Tabla renumerada. Total: ' + (contador-1) + ' ítems.');
}

// ============================================================================
// FUNCIÓN: busquedaInteligente (conservada con typo corregido)
// ============================================================================
function busquedaInteligente() {
    const input = document.getElementById('txtBuscarUnificado');
    const termino = input.value.trim().toUpperCase();
    const periodo = document.querySelector('input[name="anio_semestre"]').value;

    if (!termino) {
        alert("Ingrese un nombre o cédula para buscar.");
        return;
    }

    // --- PASO 1: BÚSQUEDA LOCAL (EN LA TABLA VISIBLE) ---
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
        input.value = '';
        return;
    }

    // --- PASO 2: VERIFICAR SI YA EXISTE EN EL PUNTO 4 ---
    const editor = window.allEditors['#editor4'];
    const contenidoP4 = editor ? editor.getData() : '';
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = contenidoP4;
    const cedulasP4 = new Set();
    
    tempDiv.querySelectorAll('li').forEach(li => {
        const texto = li.textContent;
        const matchCedula = texto.match(/Cédula:\s*(\d+)/i) || texto.match(/(\d{6,10})/);
        if (matchCedula && matchCedula[1]) {
            cedulasP4.add(matchCedula[1].trim());
        }
    });
    
    if (termino.match(/^\d+$/) && cedulasP4.has(termino)) {
        alert("Este aspirante ya está en el Punto 4 (búsqueda por cédula).");
        input.value = '';
        return;
    }

    // --- PASO 3: BÚSQUEDA REMOTA ---
    const btn = input.nextElementSibling;
    const btnTextoOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
    btn.disabled = true;

    fetch(`buscar_aspirante.php?cedula=${termino}&periodo=${periodo}`)
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                const cedulaAspirante = data.data.documento_tercero || termino;
                if (cedulasP4.has(cedulaAspirante)) {
                    alert("Este aspirante ya está en el Punto 4.");
                    input.value = '';
                    return;
                }
                
                let yaEnTabla = false;
                filas.forEach(fila => {
                    if(fila.id === 'rowSinResultados') return;
                    const cedulaFila = fila.getAttribute('data-cedula');
                    if (cedulaFila === cedulaAspirante) {
                        yaEnTabla = true;
                        const checkbox = fila.querySelector('.check-sug');
                        checkbox.checked = true;
                        fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        fila.classList.add('table-warning');
                        setTimeout(() => fila.classList.remove('table-warning'), 2000);
                    }
                });
                
                if (!yaEnTabla) {
                    agregarFilaManual(data.data, cedulaAspirante);
                    input.value = '';
                }
            } else {
                alert(`El aspirante con cédula/nombre "${termino}" no se encuentra en la lista sugerida ni en la base de datos de aspirantes para este periodo.`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("No se encontró en la lista local. Para traerlo externamente, por favor use el número de CÉDULA exacto.");
        })
        .finally(() => {
            btn.innerHTML = btnTextoOriginal;
            btn.disabled = false;
        });
}

// ============================================================================
// FUNCIÓN: agregarFilaManual (versión con dos parámetros, la correcta)
// ============================================================================
function agregarFilaManual(aspirante, cedulaBusqueda) {
    const tbody = document.querySelector('#tablaSugerenciasSist tbody');
    const rowVacia = document.getElementById('rowSinResultados');
    if(rowVacia) rowVacia.remove();

    const rowCount = tbody.querySelectorAll('tr').length + 1;
    const tr = document.createElement('tr');
    tr.className = "table-info";
    
    tr.setAttribute('data-cedula', cedulaBusqueda);
    tr.setAttribute('data-nombre', aspirante.nombre_completo.toUpperCase());

    tr.innerHTML = `
        <td class="text-center align-middle">
            <input type="checkbox" class="form-check-input check-sug" checked>
        </td>
        <td class="text-center fw-bold align-middle text-muted">${rowCount}</td>
        <td class="nom-sug align-middle">
            <div class="fw-bold text-uppercase">${aspirante.nombre_completo}</div>
            <span class="text-muted x-small" style="font-size:0.7em"><i class="fas fa-id-card"></i> ${cedulaBusqueda}</span>
            <span class="badge bg-info text-dark" style="font-size: 0.65rem;">
                <i class="fas fa-plus-circle"></i> AGREGADO MANUALMENTE
            </span>
        </td>
        <td class="tit-sug text-muted align-middle" style="font-size: 0.72rem;">${aspirante.asp_titulos}</td>
        <td class="small align-middle">
            <i class="fas fa-envelope text-muted"></i> ${aspirante.asp_correo}<br>
            <i class="fas fa-phone text-muted"></i> ${aspirante.asp_celular}
        </td>
    `;
    
    tbody.insertBefore(tr, tbody.firstChild);
    tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ============================================================================
// FUNCIÓN: cargarSugerenciasFor59 (conservada)
// ============================================================================
function cargarSugerenciasFor59() {
    const modal = new bootstrap.Modal(document.getElementById('modalSugerencias'));
    modal.show();
    const tbody = document.getElementById('cuerpoSugerenciasFor59');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border"></div></td></tr>';

    const perfiles = [];
    document.querySelectorAll('input[name="perf_perfil[]"]').forEach(input => {
        if (input.value.trim()) perfiles.push(input.value.trim());
    });

    fetch('ajax_sugerencias_for59.php', {
        method: 'POST',
        body: new URLSearchParams({
            departamento_id: '<?= $departamento_id ?>',
            anio_semestre: '<?= $anio_semestre ?>',
            perfiles_busqueda: JSON.stringify(perfiles)
        })
    })
    .then(r => r.text())
    .then(html => tbody.innerHTML = html)
    .catch(() => tbody.innerHTML = '<tr><td colspan="5" class="text-danger">Error</td></tr>');
}

// ============================================================================
// FUNCIÓN: cargarProfesoresFor59 (MODIFICADA: asigna ID a la tabla y llama al marcado)
// ============================================================================
function cargarProfesoresFor59() {
    const contenedor = document.getElementById('contenedorProfesoresFor59');
    fetch('ajax_profesores_for59.php', {
        method: 'POST',
        body: new URLSearchParams({
            departamento_id: '<?= $departamento_id ?>',
            anio_semestre: '<?= $anio_semestre ?>'
        })
    })
    .then(r => r.text())
    .then(html => {
        contenedor.innerHTML = html;
        // Asignar ID a la tabla si no lo tiene
        const tabla = contenedor.querySelector('table');
        if (tabla && !tabla.id) {
            tabla.id = 'tablaPunto7For59';
        }
        // Llamar al marcado de integridad
        marcarNoCalificadosEnPunto7For59();
    })
    .catch(() => contenedor.innerHTML = '<div class="alert alert-danger">Error</div>');
}

// ============================================================================
// FUNCIÓN: marcarNoCalificadosEnPunto7For59 (NUEVA)
// ============================================================================
function marcarNoCalificadosEnPunto7For59() {
    const editor5 = window.allEditors['#editor5'];
    if (!editor5) return; // Editor aún no listo

    const contenidoP5 = editor5.getData();
    const cedulasP5 = new Set();

    // Extraer cédulas de la tabla del punto 5 (formato "Cédula: 123456")
    if (contenidoP5.includes('<table')) {
        const parser = new DOMParser();
        const docP5 = parser.parseFromString(contenidoP5, 'text/html');
        const tablaP5 = docP5.querySelector('table');
        if (tablaP5) {
            tablaP5.querySelectorAll('tbody tr').forEach(fila => {
                fila.querySelectorAll('td').forEach(celda => {
                    const texto = celda.innerText;
                    const match = texto.match(/Cédula:\s*([\d\.]+)/i);
                    if (match) {
                        // Limpiar puntos y espacios
                        const cedulaLimpia = match[1].replace(/\./g, '');
                        cedulasP5.add(cedulaLimpia);
                    } else {
                        // Si no encuentra el formato, intentar capturar cualquier número largo
                        const matchNum = texto.match(/\b(\d{6,10})\b/);
                        if (matchNum) {
                            cedulasP5.add(matchNum[1]);
                        }
                    }
                });
            });
        }
    }

    const tablaP7 = document.getElementById('tablaPunto7For59');
    if (!tablaP7) return;

    // Limpiar marcas anteriores
    tablaP7.querySelectorAll('.badge-warning-custom').forEach(b => b.remove());
    tablaP7.querySelectorAll('tbody tr').forEach(f => f.classList.remove('table-warning'));

    let contadorNoCalificados = 0;

    tablaP7.querySelectorAll('tbody tr').forEach(fila => {
        // La segunda columna contiene nombre y cédula
        const celdaDocente = fila.querySelector('td:nth-child(2)');
        if (!celdaDocente) return;

        // Extraer cédula (puede estar en un span o en el texto)
        let cedula = '';
        const spanCedula = celdaDocente.querySelector('.text-muted');
        if (spanCedula) {
            const match = spanCedula.innerText.match(/\b(\d{6,10})\b/);
            if (match) cedula = match[1];
        } else {
            const match = celdaDocente.innerText.match(/\b(\d{6,10})\b/);
            if (match) cedula = match[1];
        }

        if (!cedula) return;

        // Limpiar puntos de la cédula extraída (por si acaso)
        const cedulaLimpia = cedula.replace(/\./g, '');

        if (!cedulasP5.has(cedulaLimpia)) {
            fila.classList.add('table-warning');
            const badge = document.createElement('span');
            badge.className = 'badge bg-warning text-dark ms-2 badge-warning-custom';
            badge.style.fontSize = '0.65rem';
            badge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>No calificado';
            celdaDocente.appendChild(badge);
            contadorNoCalificados++;
        }
    });

    // Actualizar contador visual
    let contadorDiv = document.getElementById('contadorNoCalificadosP7For59');
    if (!contadorDiv) {
        contadorDiv = document.createElement('div');
        contadorDiv.id = 'contadorNoCalificadosP7For59';
        contadorDiv.className = 'alert alert-warning py-1 px-2 mb-2 small';
        contadorDiv.style.fontSize = '0.8rem';
        tablaP7.parentNode.insertBefore(contadorDiv, tablaP7);
    }

    if (contadorNoCalificados > 0) {
        contadorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> ' + contadorNoCalificados + ' aspirante(s) sin calificación en el punto 5.';
        contadorDiv.style.display = 'block';
    } else {
        contadorDiv.style.display = 'none';
    }
}

// ============================================================================
// FUNCIÓN: calcularTotalesP5 (conservada)
// ============================================================================
function calcularTotalesP5() {
    const editor = window.allEditors['#editor5'];
    if (!editor) {
        alert('El editor del Punto 5 no está disponible.');
        return;
    }
    recalcularTotalesP5(editor);
    const notificacion = document.createElement('div');
    notificacion.className = 'alert alert-success position-fixed top-0 end-0 m-3 shadow';
    notificacion.style.zIndex = '9999';
    notificacion.innerHTML = '<i class="fas fa-check-circle me-2"></i> Totales recalculados.';
    document.body.appendChild(notificacion);
    setTimeout(() => notificacion.remove(), 2000);
}

// ============================================================================
// FUNCIÓN: recalcularTotalesP5 (conservada)
// ============================================================================
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
    const cabeceras = filasCabecera[0].querySelectorAll('th');

    let indiceColumnaTotal = -1;
    cabeceras.forEach((th, idx) => {
        const texto = th.innerText.toLowerCase().trim();
        if (texto.includes('total')) {
            indiceColumnaTotal = idx;
        }
    });
    if (indiceColumnaTotal === -1) {
        console.log('No se encontró columna TOTAL');
        return;
    }

    let indiceColumnaNombre = -1;
    cabeceras.forEach((th, idx) => {
        const texto = th.innerText.toLowerCase();
        if (texto.includes('nombre') || texto.includes('aspirante')) {
            indiceColumnaNombre = idx;
        }
    });
    if (indiceColumnaNombre === -1) {
        indiceColumnaNombre = 1;
    }

    let indicesColumnasValor = [];
    for (let i = indiceColumnaNombre + 1; i < indiceColumnaTotal; i++) {
        indicesColumnasValor.push(i);
    }
    if (indicesColumnasValor.length === 0) {
        console.log('No hay columnas entre NOMBRE y TOTAL');
        return;
    }

    const filas = tbody.querySelectorAll('tr');
    let filasActualizadas = false;
    filas.forEach((fila) => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length <= indiceColumnaTotal) return;

        let suma = 0;
        indicesColumnasValor.forEach(idxCol => {
            if (celdas[idxCol]) {
                const valorTexto = celdas[idxCol].innerText.trim();
                const numero = parseFloat(valorTexto.replace(/,/g, '.').replace(/[^\d.-]/g, ''));
                if (!isNaN(numero)) suma += numero;
            }
        });

        let sumaFormateada = suma.toFixed(3).replace(/\.?0+$/, '');
        if (sumaFormateada === '') sumaFormateada = '0';

        const celdaTotal = celdas[indiceColumnaTotal];
        if (celdaTotal.innerHTML !== sumaFormateada) {
            celdaTotal.innerHTML = sumaFormateada;
            filasActualizadas = true;
        }
    });

    if (filasActualizadas) {
        const nuevoHTML = doc.body.innerHTML;
        editor.setData(nuevoHTML);
        console.log('Totales recalculados y editor actualizado');
    } else {
        console.log('No hubo cambios en los totales');
    }
}

// ============================================================================
// FUNCIÓN: generarTablaEvaluacion (MODIFICADA: usa concatenación clásica)
// ============================================================================
function generarTablaEvaluacion() {
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
                textoCompleto: nombre + (cedula ? ' (Cédula: ' + cedula + ')' : '')
            });
        }
    });

    if (aspirantesP4.length === 0) {
        alert("El Punto 4 está vacío. No hay aspirantes para sincronizar.");
        return;
    }

    const contenidoP5 = window.allEditors['#editor5'].getData();

    // ESCENARIO A: YA EXISTE UNA TABLA
    if (contenidoP5.includes('<table')) {
        const parser = new DOMParser();
        const docP5 = parser.parseFromString(contenidoP5, 'text/html');
        const tabla = docP5.querySelector('table');
        const thead = tabla.querySelector('thead');
        const tbody = tabla.querySelector('tbody');
        const filasExistentes = tbody.querySelectorAll('tr');

        let indiceColumnaTotal = -1;
        let indicesColumnasValor = [];
        let indiceColumnaNombre = -1;
        let tieneColumnaNumero = false;
        const cabeceras = thead ? thead.querySelectorAll('th') : [];

        if (cabeceras.length > 0) {
            const primeraCabecera = cabeceras[0].innerText.toLowerCase();
            tieneColumnaNumero = primeraCabecera.includes('no') || primeraCabecera.includes('núm') || primeraCabecera.includes('#');

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

        // Convertir filas existentes en mapa de aspirantes
        let aspirantesP5 = [];
        let filasPorCedula = new Map();

        filasExistentes.forEach((tr) => {
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

        const aspirantesP5Map = new Map(aspirantesP5.map(a => [a.cedula || a.nombre, a]));
        const aspirantesP4Map = new Map(aspirantesP4.map(a => [a.cedula || a.nombre, a]));

        const nuevosCandidatos = aspirantesP4.filter(asp => !aspirantesP5Map.has(asp.cedula || asp.nombre));
        const eliminadosCandidatos = aspirantesP5.filter(asp => !aspirantesP4Map.has(asp.cedula || asp.nombre));

        if (nuevosCandidatos.length === 0 && eliminadosCandidatos.length === 0) {
            alert("La tabla ya está sincronizada. No hay aspirantes nuevos ni eliminados.");
            return;
        }

        if (!confirm('Se detectaron:\n• ' + nuevosCandidatos.length + ' aspirante(s) nuevo(s)\n• ' + eliminadosCandidatos.length + ' aspirante(s) eliminado(s)\n\n¿Desea sincronizar la tabla?')) {
            return;
        }

        // Eliminar filas de aspirantes que ya no están en P4
        eliminadosCandidatos.forEach(asp => {
            const key = asp.cedula || asp.nombre;
            const filaData = filasPorCedula.get(key);
            if (filaData && filaData.elemento) {
                filaData.elemento.remove();
            }
        });

        // Agregar filas nuevas al final
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
                tdNumero.innerHTML = '<strong>' + (maxNumero + index + 1) + '</strong>';
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
                primeraTd.innerHTML = '<strong>' + (maxNumero + index + 1) + '.</strong> ' + aspirante.textoCompleto;
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

        if (tieneColumnaNumero) {
            const todasFilas = tbody.querySelectorAll('tr');
            let contador = 1;
            todasFilas.forEach(fila => {
                const primeraCelda = fila.querySelector('td:first-child');
                if (primeraCelda) {
                    primeraCelda.innerHTML = '<strong>' + contador + '</strong>';
                    contador++;
                }
            });
        }

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

        window.allEditors['#editor5'].setData(docP5.body.innerHTML);
        alert('Tabla sincronizada.\n✓ Agregados: ' + nuevosCandidatos.length + '\n✓ Eliminados: ' + eliminadosCandidatos.length);
        marcarNoCalificadosEnPunto7For59();

    } else {
        // ESCENARIO B: NO HAY TABLA (CREAR NUEVA)
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
            tablaHtml += '<th style="background-color:#f2f2f2; padding: 6px;">' + col + '</th>';
        });
        
        tablaHtml += '</tr></thead><tbody>';

        aspirantesP4.forEach((aspirante, index) => {
            tablaHtml += '<tr>';
            tablaHtml += '<td style="padding: 6px; text-align: center; font-weight: bold;">' + (index + 1) + '</td>';
            tablaHtml += '<td style="padding: 6px;">' + aspirante.textoCompleto + '</td>';
            
            columnas.forEach(() => {
                tablaHtml += '<td style="padding: 6px;">&nbsp;</td>';
            });
            
            tablaHtml += '</tr>';
        });

        tablaHtml += '</tbody></table>';
        
        window.allEditors['#editor5'].setData(tablaHtml);
        marcarNoCalificadosEnPunto7For59();
    }
}

// ============================================================================
// FUNCIÓN: guardarYRegresar (conservada)
// ============================================================================
function guardarYRegresar() {
    const form = document.getElementById('formActa');
    if (!form) {
        alert('Error: No se encontró el formulario.');
        return;
    }
    
    let accionField = form.querySelector('input[name="accion"]');
    if (!accionField) {
        accionField = document.createElement('input');
        accionField.type = 'hidden';
        accionField.name = 'accion';
        form.appendChild(accionField);
    }
    accionField.value = 'salir';
    
    form.submit();
}

// ============================================================================
// INICIALIZACIÓN: DOMContentLoaded
// ============================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar CKEditor en todos los campos
    const promises = [];
    ['#editor3', '#editor4', '#editor5', '#editor6'].forEach(id => {
        const elemento = document.querySelector(id);
        if (elemento) {
            promises.push(
                ClassicEditor.create(elemento, cfg)
                    .then(editor => {
                        window.allEditors[id] = editor;
                        // Si es el editor del punto 5, actualizar marcas al cambiar
                        if (id === '#editor5') {
                            editor.model.document.on('change:data', () => {
                                marcarNoCalificadosEnPunto7For59();
                            });
                        }
                    })
                    .catch(err => console.error('Error al cargar editor:', id, err))
            );
        }
    });

    // Cuando todos los editores estén listos, cargar profesores y marcar
    Promise.all(promises).then(() => {
        cargarProfesoresFor59(); // Esta función ya llama a marcarNoCalificadosEnPunto7For59
    });

    // Limpieza del modal al cerrar
    const modalElement = document.getElementById('modalSugerencias');
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        });
    }

    // Lógica del checkbox "Seleccionar todos"
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
                }
            });
        });

        // Sincronización inversa
        const tabla = document.querySelector('#tablaSugerenciasSist');
        if (tabla) {
            tabla.addEventListener('change', function(e) {
                if (e.target.classList.contains('check-sug')) {
                    const fila = e.target.closest('tr');
                    if (e.target.checked) {
                        fila.classList.add('table-active'); 
                    } else {
                        fila.classList.remove('table-active');
                    }
                    
                    if (!e.target.checked && checkAll) {
                        checkAll.checked = false;
                    }
                }
            });
        }
    }
});
</script>
    
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
                        <thead class="table-light small">
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
                        <tbody id="cuerpoSugerenciasFor59" class="small">
                            <tr><td colspan="5" class="text-center py-4"><div class="spinner-border"></div><p>Cargando...</p></td></tr>
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
</body>
</html>