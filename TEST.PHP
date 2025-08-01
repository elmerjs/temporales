<?php

$active_menu_item = 'gestion_depto';

require('include/headerz.php');
require 'funciones.php';
//require 'actualizar_usuario.php'; // <-- Incluir aquí
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
    exit(); // Detener toda la ejecución del script
}

    // Obtener los parámetros de la URL
$facultad_id = isset($_POST['facultad_id']) ? $_POST['facultad_id'] : null;
$anio_semestre = $_POST['anio_semestre'] ?? $_GET['anio_semestre'] ?? null;
$departamento_id = $_POST['departamento_id'] ?? $_GET['departamento_id'] ?? null;
 $aniose= $anio_semestre;
        $cierreperiodo = obtenerperiodo($anio_semestre);

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Solicitudes</title>
    
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<!-- jQuery y Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    
    
<!-- Cargar Bootstrap 5 y Font Awesome -->

<!-- jQuery (si es necesario) -->

<!-- Cargar solo Bootstrap 5 JS -->

            <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
    ..</style>
    <script>
        function confirmarEnvio(count, tipo) {
            return confirm(` Se confirman ${count} profesores de  ${tipo}. ¿Desea continuar?`);
        }
    </script>
    
</head>
<body>
   <!-- Barra superior -->
    <div style="max-width: 1800px; width: 100%; margin: auto;">

<div class="top-nav">
    <div class="container position-relative d-flex align-items-center justify-content-center"> <?php if ($tipo_usuario != 3): ?>
            <a href="report_depto_full.php?anio_semestre=<?= urlencode($anio_semestre) ?>" 
               class="btn-unicauca-light position-absolute start-0" title="Regresar a 'Gestión facultad'">
                <i class="fas fa-arrow-left me-2"></i> Regresar
            </a>
        <?php endif; ?>
        
        <div class="text-white fw-bold"> <i class="fas fa-university me-2"></i> Gestión Departamento
        </div>
        </div>
</div>
    <div class="container">
        
 <div class="institutional-card">
                 <div class="card-header-unicauca">
<div class="summary-header">
<div class="summary-container">
    <ul class="summary-data-list">
        <li>
            <span class="label-heading">Facultad:</span>
            <span class="data-value"><?php echo obtenerNombreFacultad($departamento_id); ?></span>
        </li>
        <li>
            <span class="label-heading">Departamento:</span>
            <span class="data-value"><?php echo obtenerNombreDepartamento($_POST['departamento_id']); ?></span>
        </li>
        <li>
            <span class="label-heading">Periodo:</span>
            <span class="data-value"><?php echo htmlspecialchars($_POST['anio_semestre']); ?></span>
        </li>
    </ul>
</div>
</div>


    <?php
                    $nombre_fac=obtenerNombreFacultad($departamento_id);

$facultad_id = obtenerIdFacultad($departamento_id);

    // Establecer conexión a la base de datos
    $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    require 'cn.php';
    // Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                  FROM solicitudes  where solicitudes.estado <> 'an' OR solicitudes.estado IS NULL;";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}
       
     $todosCerrados = true; // Inicializar bandera
            
            
    $obtenerDeptoCerrado = obtenerDeptoCerrado($departamento_id,$anio_semestre); // si cero   no cerrado si 1  cerrado
if ($obtenerDeptoCerrado!=1 && $tipo_usuario == 3) {
    echo "<div style='text-align: right;'>
            <label for='selectAll' style='cursor: pointer;'>
                <input type='checkbox' id='selectAll' 
                       title='Aplique este checkbox solo si está seguro de marcar masivamente los profesores' 
                       onclick='confirmSelection(this)'>
                <i>Visado masivo</i>
            </label>
          </div>";
}
  $totalItems = 0; // Inicializar el acumulador fuera del bucle principal
   $contadorHV = 0; // 🔹 Inicializar el contador
$acepta_vra = null;
while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_docente = $rowtipo['tipo_d'];

    $sql = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento
            FROM solicitudes
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL) order by solicitudes.nombre asc";

    $result = $conn->query($sql);

    // Generate a unique ID for the section that will be hidden/shown
    // Replace spaces and special characters to ensure a valid HTML ID
    $section_id = "section-" . strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $tipo_docente)));

    echo "<div class='box-gray'>";
    echo "<div class='estado-container'>";
    // Add onclick and an icon with a unique ID for rotation
echo "<h4 style='color: #000066; cursor: pointer;' onclick=\"toggleSection('" . htmlspecialchars($section_id) . "')\" title=\"Ocultar/Mostrar Detalles\">
            <i id=\"icon_" . htmlspecialchars($section_id) . "\" class=\"fas fa-caret-down\"></i>
            Vinculación: " . htmlspecialchars($tipo_docente) . " ("; // Applied style here
if ($tipo_docente == 'Catedra') {
    $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
    echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
} else {
    $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose);
    echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
}
echo ")</h4>";

    if ($estadoDepto != 'CERRADO') {
        if ($tipo_usuario == 3) {
            echo "
            <form action='nuevo_registro.php' method='GET'>
                <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>

                <div class='d-flex gap-2'>
                    <button type='submit' class='btn btn-success'>
                        <i class='fas fa-plus'></i> Agregar Profesor
                    </button>

                    <button type='button' class='btn btn-danger'
                        onclick=\"eliminarRegistros(
                            '" . htmlspecialchars($tipo_docente) . "',
                            '" . htmlspecialchars($facultad_id) . "',
                            '" . htmlspecialchars($departamento_id) . "',
                            '" . htmlspecialchars($anio_semestre) . "'
                        )\">
                        <i class='fas fa-trash-alt'></i> Eliminar todos
                    </button>
                </div>
            </form>";
        }

        $todosCerrados = false;
    }

    echo "</div>"; // Close estado-container

    // Obtener el conteo de profesores
    $sqlCount = "SELECT COUNT(*) as count FROM solicitudes WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
    $resultCount = $conn->query($sqlCount);
    $count = $resultCount->fetch_assoc()['count'];

    // Wrap the table content in a div with the unique ID
    echo "<div id='" . htmlspecialchars($section_id) . "' style='display: block;'>"; // Initially visible

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th rowspan='2'>Ítem</th>
                    <th rowspan='2'>Cédula</th>
                    <th rowspan='2'>Nombre</th>";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }
        echo "<th colspan='2'>Hojas de vida</th>";

        if ($estadoDepto != "CERRADO") {
            echo "<th colspan='3'>Acciones</th>";
        } else {
            echo "<th colspan='3' ></th>";
        }

        echo "</tr>";

        if ($tipo_docente == "Ocasional") {
            echo "<tr>
                    <th title='Sede Popayán'>Pop</th>
                    <th title='Sede Regionalización'>Reg</th>";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr>
                    <th title='Horas en Sede Popayán'>Horas Pop</th>
                    <th title='Horas en Sede Regionalización'>Horas Reg</th>";
        }
        echo "
            <th title='Anexa Hoja de Vida para nuevos aspirantes'>Anexa (nuevo)</th>
            <th title='Actualiza Hoja de Vida para aspirantes antiguos'>Actualiza (antiguo)</th>";

        if ($estadoDepto != "CERRADO") {
            echo "<th>Eliminar</th>
                  <th>Editar</th>";
            if ($tipo_usuario == 3) {
                echo "<th>Visado</th>";
                if ($tipo_usuario == 3) {
                    echo "<th style='display:none;'>FOR.45</th>";
                }
            } else {
                echo "<th>Visado</th>";
            }
        } else {
            echo "<th style='display:none;'>Eliminar</th>
                  <th style='display:none;'>Editar</th>
                  <th>Visado</th>";
           // if ($tipo_usuario == 3) {
                echo "<th style='text-align: center; vertical-align: middle;' title='Formato FOR 45 - Revisión Requisitos Vinculación Docente'>FOR.45</th>";
            //}
        }

        echo "</tr>";

        $item = 1;
        $todosLosRegistrosValidos = true;
        $datos_acta = obtener_acta($anio_semestre, $departamento_id);

        $num_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['acta_periodo']) : "";
        $fecha_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['fecha_acta']) : "";

      while ($row = $result->fetch_assoc()) {
    if ($row["anexa_hv_docente_nuevo"] == 'si' || $row["actualiza_hv_antiguo"] == 'si') {
        $contadorHV++;
    }
  // Obtenemos los datos del profesor (array asociativo o false)
$datosProfesor = datosProfesorCompleto($row["cedula"], $anio_semestre);

// Preparamos el tooltip
$tooltip = '';

if ($datosProfesor !== false) {
    // Escapamos todos los valores para HTML
    $datosSeguros = array_map('htmlspecialchars', $datosProfesor);
    
    // Construimos el tooltip con formato legible
    //quite del tootlip          <strong>postulado en Departamento(s):</strong> {$datosSeguros['departamento']}<br>        <strong>Teléfono:</strong> {$datosSeguros['telefono']}<br>


    $tooltip = "
     
        <strong>Email:</strong> {$datosSeguros['email']}<br>
        <strong>Títulos:</strong> {$datosSeguros['titulos']}<br>
        <strong>Celular:</strong> {$datosSeguros['celular']}<br>
        <strong>Trabaja actualmente:</strong> {$datosSeguros['trabaja_actualmente']}<br>
        <strong>Cargo actual:</strong> {$datosSeguros['cargo_actual']}
    ";
}
// Generamos la fila de la tabla
echo "<tr>
    <td class='td-simple'>" . $item . "</td> 
    <td class='td-simple' style='text-align: left;'>" . htmlspecialchars($row["cedula"]) . "</td>
    <td class='td-simple' style='text-align: left;' 
      data-toggle='tooltip' 
      data-html='true' 
      title='" . $tooltip . "'>
      " . htmlspecialchars($row["nombre"]) . "
    </td>
";

    if ($tipo_docente == "Ocasional") {
        echo "<td class='td-simple'>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td class='td-simple'>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
    if ($tipo_docente == "Catedra") {
        $horas = ($row["horas"] == 0) ? "" : htmlspecialchars($row["horas"]);
        $horas_r = ($row["horas_r"] == 0) ? "" : htmlspecialchars($row["horas_r"]);

        echo "<td class='td-simple'>" . $horas . "</td>
              <td class='td-simple'>" . $horas_r . "</td>";
    }

    // Verificar si hay un enlace válido en 'anexos'
    $anexos = trim($row["anexos"]);
    $hasValidLink = !empty($anexos) && preg_match('/^(https?:\/\/|www\.)/i', $anexos);
    
    // Mostrar anexa_hv_docente_nuevo como enlace si hay un enlace válido
    if ($row["anexa_hv_docente_nuevo"] == 'si' && $hasValidLink) {
        echo "<td class='td-simple'><a href='" . htmlspecialchars($anexos) . "' target='_blank' class='link-hv'>si</a></td>";
    } else {
        echo "<td class='td-simple'>" . htmlspecialchars($row["anexa_hv_docente_nuevo"]) . "</td>";
    }
    
    // Mostrar actualiza_hv_antiguo como enlace si hay un enlace válido
    if ($row["actualiza_hv_antiguo"] == 'si' && $hasValidLink) {
        echo "<td class='td-simple'><a href='" . htmlspecialchars($anexos) . "' target='_blank' class='link-hv'>si</a></td>";
    } else {
        echo "<td class='td-simple'>" . htmlspecialchars($row["actualiza_hv_antiguo"]) . "</td>";
    }
    
       if ($estadoDepto != "CERRADO") {
                echo "<td class='td-simple'>";
                if ($tipo_usuario == 3) {
                    echo "
                        <form action='eliminar.php' method='POST' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                            <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                            <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                            <button type='submit' class='delete-btn'><i class='fa-regular fa-trash-can'></i></button>
                        </form>";
                }
                echo "</td><td class='td-simple'>";
                if ($tipo_usuario == 3) {
                    echo "
                        <form action='actualizar.php' method='GET' style='display:inline;'>
                            <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                            <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                            <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                            <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                        </form>";
                }
                echo "</td><td class='td-simple'>";
                $disabled = ($tipo_usuario == 3) ? "" : "disabled";
                echo "
                    <form class='vistoBuenoForm' style='display:inline;'>
                        <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row['id_solicitud']) . "'>
                        <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                        <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                        <input type='checkbox' class='individualCheckbox' name='visto_bueno' value='1' " . ($row['visado'] ? 'checked' : '') . " $disabled>
                    </form>";
                echo "</td>";

            } else {
                echo "<td class='td-simple' style='display:none;'>
                            <form action='eliminar.php' method='POST' style='display:inline;'>
                                <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                                <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                                <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                                <button type='submit' class='delete-btn'><i class='fas fa-trash'></i></button>
                            </form>
                        </td>
                        <td class='td-simple' style='display:none;'>
                            <form action='actualizar.php' method='GET' style='display:inline;'>
                                <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                                <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                                <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                                <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                            </form>
                        </td>
                        <td class='td-simple'>
                            <form action='update_visto_bueno.php' method='POST' style='display:inline;'>
                                <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                                <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                                <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                                <input type='checkbox' disabled name='visto_bueno' value='1' " . ($row["visado"] ? "checked" : "") . " onchange='this.form.submit()'>
                            </form>
                        </td>";

                if ($tipo_usuario == 3) {
            echo "<td class='td-simple centered-column'>
        <button type='button' class='download-btn btn btn-sm'
            id='btn-solicitud-" . htmlspecialchars($row["id_solicitud"]) . "'
            data-id-solicitud='" . htmlspecialchars($row["id_solicitud"]) . "'
            data-departamento-id='" . htmlspecialchars($departamento_id) . "'
            data-anio-semestre='" . htmlspecialchars($anio_semestre) . "'
            data-numero-acta='" . htmlspecialchars($num_acta) . "'
            data-fecha-acta='" . htmlspecialchars($fecha_acta) . "'
            data-pregrado='" . htmlspecialchars($row["pregrado"] ?? '') . "'
            data-especializacion='" . htmlspecialchars($row["especializacion"] ?? '') . "'
            data-maestria='" . htmlspecialchars($row["maestria"] ?? '') . "'
            data-doctorado='" . htmlspecialchars($row["doctorado"] ?? '') . "'
            data-otro_estudio='" . htmlspecialchars($row["otro_estudio"] ?? '') . "'
            data-experiencia-docente='" . htmlspecialchars($row["experiencia_docente"] ?? '') . "'
            data-experiencia-profesional='" . htmlspecialchars($row["experiencia_profesional"] ?? '') . "'
            data-otra-experiencia='" . htmlspecialchars($row["otra_experiencia"] ?? '') . "'
            data-cedula-profesor='" . htmlspecialchars($row["cedula"] ?? '') . "'  data-bs-toggle='modal'
            data-bs-target='#actaModal'>
            <i class='fa-solid fa-file-arrow-down' style='font-size:16px; color:#1A73E8;'></i>
        </button>
    </td>";
                }
           else {
               
// Construir la URL con todas las variables
$web_view_url = "for45web.php?" .
    "id_solicitud=" . htmlspecialchars($row["id_solicitud"]) . "&" .
    "departamento_id=" . htmlspecialchars($departamento_id) . "&" .
    "anio_semestre=" . htmlspecialchars($anio_semestre) . "&" .
    "numero_acta=" . htmlspecialchars($num_acta) . "&" .
    "fecha_actab=" . htmlspecialchars($fecha_acta) . "&" . // Usar 'fecha_actab' para coincidir con el GET
    "pregrado=" . urlencode(htmlspecialchars($row["pregrado"] ?? '')) . "&" .
    "especializacion=" . urlencode(htmlspecialchars($row["especializacion"] ?? '')) . "&" .
    "maestria=" . urlencode(htmlspecialchars($row["maestria"] ?? '')) . "&" .
    "doctorado=" . urlencode(htmlspecialchars($row["doctorado"] ?? '')) . "&" .
    "otro_estudio=" . urlencode(htmlspecialchars($row["otro_estudio"] ?? '')) . "&" .
    "experiencia_docente=" . urlencode(htmlspecialchars($row["experiencia_docente"] ?? '')) . "&" .
    "experiencia_profesional=" . urlencode(htmlspecialchars($row["experiencia_profesional"] ?? '')) . "&" .
    "otra_experiencia=" . urlencode(htmlspecialchars($row["otra_experiencia"] ?? '')) . "&" .
    "cedula_profesor=" . urlencode(htmlspecialchars($row["cedula"] ?? ''));

// Aquí puedes usar este $web_view_url donde lo necesites, por ejemplo:

// Opción 1: Un botón o enlace al lado del botón de descarga de Word
echo "<td class='td-simple centered-column'>";


// Nuevo botón para la vista web
echo "<a href='" . $web_view_url . "' target='_blank' class=' btn-info' title='Ver en Web' style='padding-top: 0rem; padding-bottom: 0rem;'>
          <i class='fa-solid fa-eye' style='color: ; font-size: 0.8em;'></i>
      </a>";    
           }

            }
            echo "</tr>";
            $item++;
        }
        $totalItems += ($item - 1);

        echo "</table>";

    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";
        if ($tipo_usuario == 3) {
            if ($tipo_usuario == 3 && $estadoDepto != 'CERRADO') {
                echo '
                <div class="row mt-3">
                    <div class="col-md-12 text-center">
                        <a href="indexsolicitud.php?tipo_docente=' . urlencode($tipo_docente) . '&anio_semestre=' . urlencode($anio_semestre) . '" class="btn btn-cargue-masivo">
                            <i class="fas fa-upload"></i> Cargue Masivo - Tipo: ' . htmlspecialchars($tipo_docente) . '
                        </a>
                    </div>
                </div>';
            }
        }
    }

    echo "</div>"; // Close the section-id div
    ?>
    <div class="d-flex justify-content-between mt-3">

        <?php
        if ($estadoDepto == "ABIERTO" && $tipo_usuario == 3) {
            $mostrarFormulario = true;
        } else {
            $mostrarFormulario = false;
        }

        if ($mostrarFormulario):
            ?>
            <form id="confirmForm" action="confirmar_tipo_d_depto.php" method="GET" onsubmit="return confirmarEnvio(<?php echo $count; ?>, '<?php echo $tipo_docente; ?>');">
                <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
                <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-unlock"></i> Confirmar Profesores</button>
            </form>
        <?php endif; ?>

        <?php if ($estadoDepto == "CERRADO") {
            $envio_fac = obtenerenviof($facultad_id, $anio_semestre);
            $acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
            if ($tipo_usuario == 3) {
                ?>

                <form action="abrir_estado.php" method="POST">
                    <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                    <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
                    <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                    <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
                    <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($tipo_usuario); ?>">

                    <button type="submit" class="btn btn-warning" title="Lista cerrada — haga clic para abrir y editar.">
                        <i class="fas fa-lock"></i>
                    </button>
                </form>
            <?php
            }
        }
        // Moved the modal for FOR.45 and its script outside the main loop or ensure it's rendered only once
        // Also ensure the "Novedad" modal and its script are correctly placed.
        ?>

        <?php if ($acepta_vra === '2' && ($tipo_usuario == 3)) { ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novedadModal_<?php echo htmlspecialchars($tipo_docente); ?>" style="display: inline-block;">
                <i class="fas fa-file-alt"></i> Novedad
            </button>

       
        <?php } ?>
    </div></div><br>
<?php
} // End of while ($rowtipo = $resultadotipo->fetch_assoc())
?> <!-- Modal Rediseñado -->
    <div class='modal fade' id='actaModal' tabindex='-1' aria-labelledby='actaModalLabel' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='actaModalLabel'>FOR-45. Información del Acta y Datos Adicionales</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='actaForm' action='for_45.php' method='GET'>
                        <input type='hidden' name='id_solicitud' id='modal_id_solicitud'>
                        <input type='hidden' name='departamento_id' id='modal_departamento_id'>
                        <input type='hidden' name='anio_semestre' id='modal_anio_semestre'>

                        <div class='row mb-4'>
                            <div class='col-md-6'>
                                <div class="mb-3">
                                    <label for='numero_acta' class='form-label'>No. de Acta</label>
                                    <input type='text' class='form-control' id='numero_acta' name='numero_acta' required>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class="mb-3">
                                    <label for='fecha_actab' class='form-label'>Fecha Acta</label>
                                    <input type='date' class='form-control' id='fecha_actab' name='fecha_actab' required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Columna de Información Académica -->
                            <div class="col-md-7">
                                <div class="info-section">
                                    <div class="card-header mb-3">Información Académica (Verificar)</div>
                                    
                                    <div class="mb-3">
                                        <label for='pregrado' class='form-label'>Pregrado</label>
                                        <input type='text' class='form-control' id='pregrado' name='pregrado' placeholder="Título obtenido">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for='especializacion' class='form-label'>Especialización</label>
                                        <input type='text' class='form-control' id='especializacion' name='especializacion' placeholder="Título obtenido">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for='maestria' class='form-label'>Maestría</label>
                                        <input type='text' class='form-control' id='maestria' name='maestria' placeholder="Título obtenido">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for='doctorado' class='form-label'>Doctorado</label>
                                        <input type='text' class='form-control' id='doctorado' name='doctorado' placeholder="Título obtenido">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for='otro_estudio' class='form-label'>Otro Estudio*</label>
                                        <input type='text' class='form-control' id='otro_estudio' name='otro_estudio' placeholder="Especificar">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna de Experiencia -->
                            <div class="col-md-5 experience-col">
                                <div class="info-section">
                                    <div class="card-header mb-3">Experiencia (Años)</div>
                                    
                                    <div class="mb-3">
                                        <label for='experiencia_docente' class='form-label'>Experiencia Docente</label>
                                        <input type='text' class='form-control' id='experiencia_docente' name='experiencia_docente' placeholder="Años">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for='experiencia_profesional' class='form-label'>Experiencia Profesional</label>
                                        <input type='text' class='form-control' id='experiencia_profesional' name='experiencia_profesional' placeholder="Años ">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for='otra_experiencia' class='form-label'>Otra Experiencia</label>
                                        <input type='text' class='form-control' id='otra_experiencia' name='otra_experiencia' placeholder="Años">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-muted small">
                            * Por favor especificar cualquier otro estudio relevante no incluido en las categorías anteriores.
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button>
                    <button type='submit' form='actaForm' class='btn btn-primary'>Guardar y Descargar</button>
                </div>
            </div>
        </div>
    </div>
<script>
    // JavaScript function to toggle the visibility of the table and rotate the icon
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        const icon = document.getElementById('icon_' + sectionId);

        if (section.style.display === 'none') {
            section.style.display = 'block'; // Show the section
            icon.classList.remove('fa-caret-right'); // Change icon to down arrow
            icon.classList.add('fa-caret-down');
        } else {
            section.style.display = 'none'; // Hide the section
            icon.classList.remove('fa-caret-down'); // Change icon to right arrow
            icon.classList.add('fa-caret-right');
        }
    }

</script>
        
<script>
document.addEventListener('DOMContentLoaded', function() {
    const actaModal = document.getElementById('actaModal');

    if (actaModal) {
        actaModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            actaModal.currentButton = button; // Guardar referencia al botón que abrió el modal

            // Campos de la solicitud
            const id_solicitud = button.getAttribute('data-id-solicitud');
            const departamento_id = button.getAttribute('data-departamento-id');
            const anio_semestre = button.getAttribute('data-anio-semestre');
            const numero_acta = button.getAttribute('data-numero-acta');
            const fecha_acta = button.getAttribute('data-fecha-acta');

            // Campos de estudio y experiencia desde el botón (prioridad 1)
            let pregrado = button.getAttribute('data-pregrado');
            let especializacion = button.getAttribute('data-especializacion');
            let maestria = button.getAttribute('data-maestria');
            let doctorado = button.getAttribute('data-doctorado');
            let otro_estudio = button.getAttribute('data-otro_estudio');
            let exp_docente = button.getAttribute('data-experiencia-docente');
            let exp_profesional = button.getAttribute('data-experiencia-profesional');
            let otra_exp = button.getAttribute('data-otra-experiencia');

            // Obtener la cédula del profesor del nuevo data-attribute
            const cedulaProfesor = button.getAttribute('data-cedula-profesor'); 

            // Verificar si TODOS los campos de estudio están vacíos
            const allStudyFieldsEmpty =
                !pregrado && !especializacion && !maestria && !doctorado && !otro_estudio;

            // Si todos los campos de estudio están vacíos Y tenemos la cédula, hacemos la llamada AJAX
            if (allStudyFieldsEmpty && cedulaProfesor) {
                fetch(`get_profesor_data.php?cedula=${cedulaProfesor}&anioSemestre=${anio_semestre}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.titulos) {
                            const titulosStr = data.titulos;
                            const parsedTitulos = parseTitulos(titulosStr);

                            // Asignar los valores parseados solo si el campo correspondiente está vacío
                            document.getElementById('pregrado').value = parsedTitulos.pregrado || '';
                            document.getElementById('especializacion').value = parsedTitulos.especializacion || '';
                            document.getElementById('maestria').value = parsedTitulos.maestria || '';
                            document.getElementById('doctorado').value = parsedTitulos.doctorado || '';
                            // 'otro_estudio' de la función no se está parseando, se mantiene el comportamiento original
                        }
                    })
                    .catch(error => {
                        console.error('Error al obtener datos del profesor:', error);
                    });
            }

            // Setear valores en el formulario (siempre se asignan los que vienen del botón primero)
            document.getElementById('modal_id_solicitud').value = id_solicitud;
            document.getElementById('modal_departamento_id').value = departamento_id;
            document.getElementById('modal_anio_semestre').value = anio_semestre;
            document.getElementById('numero_acta').value = numero_acta || '';
            document.getElementById('fecha_actab').value = fecha_acta || '';

            // Setear campos de estudio y experiencia desde los data-attributes del botón
            document.getElementById('pregrado').value = pregrado || '';
            document.getElementById('especializacion').value = especializacion || '';
            document.getElementById('maestria').value = maestria || '';
            document.getElementById('doctorado').value = doctorado || '';
            document.getElementById('otro_estudio').value = otro_estudio || '';
            document.getElementById('experiencia_docente').value = exp_docente || '';
            document.getElementById('experiencia_profesional').value = exp_profesional || '';
            document.getElementById('otra_experiencia').value = otra_exp || '';
        });

        // Función para parsear el string de títulos (¡AHORA USA startsWith() Y PRIORIZA!)
        function parseTitulos(titulosStr) {
            const parsed = {
                pregrado: '',
                especializacion: '',
                maestria: '',
                doctorado: ''
            };

            // Definir palabras clave para cada tipo de estudio
            // Se han ajustado para ser más efectivas con startsWith()
            const keywords = {
                doctorado: ['DOCTORADO EN', 'DOCTOR', 'DOCTORA', 'PH.D.', 'PHD'],
                maestria: [
                    'MAESTRIA EN', 'MAESTRÍA EN', 'MAGISTER EN', 'MASTER EN',
                    'MAGISTER', 'MAESTRO', 'MASTER', 'MAGÍSTER', 'MAESTRÍA', 'MAESTRA', 'MÁSTER' 
                ],
                especializacion: ['ESPECIALIZACION', 'ESPECIALIZACIÓN', 'ESP.', 'ESPECIALISTA'],
                pregrado: [ // Palabras clave para pregrado, adaptadas para startsWith()
                   'TECNÓLOGA ', 'TECNÓLOGO ',
                    'LICENCIADO EN', 'LICENCIADA EN', 'LICENCIATURA EN', 
                    'PROFESIONAL EN', 'INGENIERO EN', 'INGENIERA EN',
                    'ABOGADO', 'ABOGADA', 'ADMINISTRADOR DE', 'ADMINISTRADORA DE', 
                    'BIOLOGO', 'BIOLOGA', 'QUIMICO', 'QUÍMICO', 'CIRUJANO', 'ANTROPOLOGO', 
                    'ENFERMERO', 'ENFERMERA', 'TECNICO EN', 'TÉCNICO EN', 'TECNOLOGO EN', 'TECNÓLOGO EN',
                    'MEDICO', 'MÉDICO', 'MATEMATICO', 'MATEMÁTICO', 'CONTADOR', 'ECONOMISTA', 
                    'BACHILLER', 'NORMALISTA', 'ARQUITECTO', 'ARQUITECTA', 'FILOSOFO', 'FILOSOFA', 
                    'PSICOLOGO', 'PSICOLOGA', 'CITOHISTOTECNOLOGO', 'BACTERIOLOGO', 'BACTERIOLOGA',
                    'LABORATORISTA', 'GEOTECNOLOGO', 'GEOTECNOLOGA', 'GEOGRAFO', 'GEOGRAFA', 
                    'ODONTOLOGO', 'ODONTOLOGA', 'NUTRICIONISTA', 'FISIOTERAPEUTA',
                    'COMUNICADOR', 'PERIODISTA', 'DISEÑADOR', 'SOCIOLOGO', 'HISTORIADOR',
                    'POLITOLOGO', 'QUÍMICO FARMACÉUTICO', 'ZOOTECNISTA', 'AGRONOMO',
                    // Palabras clave más cortas que son comunes al inicio de un título, pero menos genéricas que 'ARTE'
                    'INGENIERO', 'INGENIERA', // Aunque hay 'Ingeniero en', estas son genéricas al inicio
                    'LICENCIADO', 'LICENCIADA', 'LICENCIATURA',
                    'TECNICO', 'TÉCNICO', 'TECNOLOGO', 'TECNÓLOGO',
                    'ADMINISTRADOR', 'ADMINISTRADORA',
                    'BACHILLER', 'NORMALISTA',
                    'MÚSICA', 'ARTE', // Para casos donde el pregrado es simplemente "Música" o "Arte"
                    'GUIA' // Para Guianza Turística Bilingüe
                ]
            };

            // Dividir el string por saltos de línea, lo que nos dará cada título o frase
            const titlesArray = titulosStr.split(/[\r\n]+/); 

            for (const title of titlesArray) {
                const trimmedTitle = title.trim();
                if (!trimmedTitle) continue;

                const upperTrimmedTitle = trimmedTitle.toUpperCase();

                // 1. Intentar detectar Doctorado (máxima prioridad)
                if (!parsed.doctorado) { 
                    for (const keyword of keywords.doctorado) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.doctorado = trimmedTitle;
                            break; 
                        }
                    }
                    if (parsed.doctorado) continue; // Si se encontró, pasa al siguiente título del array y no busca más
                }

                // 2. Intentar detectar Maestría
                if (!parsed.maestria) { 
                    for (const keyword of keywords.maestria) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.maestria = trimmedTitle;
                            break; 
                        }
                    }
                    if (parsed.maestria) continue; // Si se encontró, pasa al siguiente título del array y no busca más
                }

                // 3. Intentar detectar Especialización
                if (!parsed.especializacion) { 
                    for (const keyword of keywords.especializacion) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.especializacion = trimmedTitle;
                            break; 
                        }
                    }
                    if (parsed.especializacion) continue; // Si se encontró, pasa al siguiente título del array y no busca más
                }

                // 4. Intentar detectar Pregrado (última prioridad)
                if (!parsed.pregrado) { 
                    for (const keyword of keywords.pregrado) {
                        // Usar startsWith para exigir que la palabra clave esté al inicio
                        if (upperTrimmedTitle.startsWith(keyword.toUpperCase())) {
                            parsed.pregrado = trimmedTitle;
                            break; 
                        }
                    }
                }
            }
            return parsed;
        }

        // El resto de tu script (envío de formulario, etc.) permanece igual.
        document.getElementById('actaForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const form = this;

            const newData = {
                numero_acta: document.getElementById('numero_acta').value,
                fecha_actab: document.getElementById('fecha_actab').value,
                pregrado: document.getElementById('pregrado').value,
                especializacion: document.getElementById('especializacion').value,
                maestria: document.getElementById('maestria').value,
                doctorado: document.getElementById('doctorado').value,
                otro_estudio: document.getElementById('otro_estudio').value,
                experiencia_docente: document.getElementById('experiencia_docente').value,
                experiencia_profesional: document.getElementById('experiencia_profesional').value,
                otra_experiencia: document.getElementById('otra_experiencia').value
            };

            if (actaModal.currentButton) {
                const button = actaModal.currentButton;

                button.setAttribute('data-numero-acta', newData.numero_acta);
                button.setAttribute('data-fecha-acta', newData.fecha_actab);
                button.setAttribute('data-pregrado', newData.pregrado);
                button.setAttribute('data-especializacion', newData.especializacion);
                button.setAttribute('data-maestria', newData.maestria);
                button.setAttribute('data-doctorado', newData.doctorado);
                button.setAttribute('data-otro_estudio', newData.otro_estudio);
                button.setAttribute('data-experiencia-docente', newData.experiencia_docente);
                button.setAttribute('data-experiencia-profesional', newData.experiencia_profesional);
                button.setAttribute('data-otra-experiencia', newData.otra_experiencia);
            }

            setTimeout(() => {
                form.submit();
            }, 500);
        });
    }
});
</script>
       
    </div>    </div>

      
<!-- Botón "Ver Departamento" -->
        <div class="institutional-cardb" >
        
            

   <script>
  // Mostrar el valor de la variable $tipo_docente en la consola
  console.log("Tipo de Docente: <?php echo htmlspecialchars($tipo_docente); ?>");
</script>         
    <?php        
            
// Consulta SQL para obtener los datos
$sqlb = "SELECT 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio AS nombre_departamento,

    -- Docente Ocasional por sede
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_ocasional_popayan,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_regionalizacion,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Ocasional' AND solicitudes.sede = 'Popayán-Regionalización' THEN 1 ELSE 0 END) AS total_ocasional_popayan_regionalizacion,

    depto_periodo.dp_estado_ocasional,
    depto_periodo.dp_estado_total,

    -- Docente Cátedra por sede
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán' THEN 1 ELSE 0 END) AS total_catedra_popayan,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Regionalización' THEN 1 ELSE 0 END) AS total_catedra_regionalizacion,
    SUM(CASE WHEN solicitudes.tipo_docente = 'Catedra' AND solicitudes.sede = 'Popayán-Regionalización' THEN 1 ELSE 0 END) AS total_catedra_popayan_regionalizacion,

    depto_periodo.dp_estado_catedra

FROM 
    depto_periodo
JOIN
    solicitudes ON solicitudes.anio_semestre = depto_periodo.periodo 
    AND solicitudes.departamento_id = depto_periodo.fk_depto_dp
JOIN 
    deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp

WHERE 
    fk_depto_dp = '$departamento_id' 
    AND depto_periodo.periodo = '$anio_semestre'
    AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)

GROUP BY 
    depto_periodo.fk_depto_dp, 
    deparmanentos.depto_nom_propio,
    depto_periodo.dp_estado_ocasional,
    depto_periodo.dp_estado_total,
    depto_periodo.dp_estado_catedra;
";


$resultb = $conn->query($sqlb);
//echo "consulta". $sql;
?>


<div class="">
    <div class="row">
        <div class="col-12">
            <!-- Encabezado del resumen -->
            <div class="summary-header">
                <div>
                    <h2 class="summary-title">
                        <i class="fas fa-building"></i>
                        <?php 
                        // Variables iniciales
                        $nombre_departamento = "Resumen Departamento";
                        $estado = null;
                        
                        if ($resultb->num_rows > 0) {
                            $row = $resultb->fetch_assoc();
                            $nombre_departamento = htmlspecialchars($row['nombre_departamento']);
                            $estado = $row['dp_estado_total'];
                        }
                        echo $nombre_departamento; 
                        ?>
                    </h2>
                </div>
                <div>
                    <?php 
                    // Mostrar siempre el estado cuando hay datos
                    if ($resultb->num_rows > 0): 
                        $clase_estado = ($estado == 1) ? 'status-success' : 'status-warning';
                        $icono = ($estado == 1) ? 'fa-check-circle' : 'fa-exclamation-circle';
                        $texto = ($estado == 1) ? 'ENVIADO' : 'PENDIENTE';
                    ?>
                        <div class="status-badge <?= $clase_estado ?>">
                            <i class="fas <?= $icono ?> me-2"></i>
                            <?= $texto ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
      
                    <?php
// Inicializar acumuladores por columna
$total_popayan = 0;
$total_regional = 0;
$total_ambas = 0;
$gran_total = 0;
?>

<table class="table-unicauca">
    <thead>
        <tr>
            <th></th>
            <th title="Profesores únicamente en la sede Popayán">Pop</th>
            <th title="Profesores únicamente en la sede Regionalización">Reg</th>
            <th title="Profesores que laboran en ambas sedes">Pop_Reg</th>
            <th title="Total profesores por tipo de vinculación">Total_tipo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td  class="td-simple">Ocasional</td>
            <?php
            if ($resultb->num_rows > 0) {
                $estado_ocasional = ($row['dp_estado_ocasional'] == 'ce') 
                    ? '<i class="fas fa-lock text-success" title="Cerrado"></i>' 
                    : '<i class="fas fa-unlock text-danger" title="Abierto"></i>';

                $popayan = (int)$row['total_ocasional_popayan'];
                $regional = (int)$row['total_ocasional_regionalizacion'];
                $ambas = (int)$row['total_ocasional_popayan_regionalizacion'];
                $total_ocasional = $popayan + $regional + $ambas;

                $total_popayan += $popayan;
                $total_regional += $regional;
                $total_ambas += $ambas;
                $gran_total += $total_ocasional;

                echo "<td class='td-simple'>$popayan</td>";
                echo "<td class='td-simple'>$regional</td>";
                echo "<td class='td-simple'>$ambas</td>";
                echo "<td class='fw-bold'>$total_ocasional</td>";
                echo "<td class='td-simple'>$estado_ocasional</td>";
            }
            ?>
        </tr>
        <tr>
            <td class="td-simple">Cátedra</td>
            <?php
            if ($resultb->num_rows > 0) {
                $icono_catedra = ($row['dp_estado_catedra'] == 'ce') 
                    ? '<i class="fas fa-lock text-success" title="Cerrado"></i>' 
                    : '<i class="fas fa-unlock text-danger" title="Abierto"></i>';

                $popayan = (int)$row['total_catedra_popayan'];
                $regional = (int)$row['total_catedra_regionalizacion'];
                $ambas = (int)$row['total_catedra_popayan_regionalizacion'];
                $total_catedra = $popayan + $regional + $ambas;

                $total_popayan += $popayan;
                $total_regional += $regional;
                $total_ambas += $ambas;
                $gran_total += $total_catedra;

                echo "<td class='td-simple'>$popayan</td>";
                echo "<td class='td-simple'>$regional</td>";
                echo "<td class='td-simple'>$ambas</td>";
                echo "<td class='fw-bold'>$total_catedra</td>";
                echo "<td class='td-simple'> $icono_catedra</td>";
            }
            ?>
        </tr>
        <!-- Fila de Totales por columna -->
        <tr class="table-secondary">
            <td class="td-simple">Total_x_sede</td>
            <td class="td-simple"><?= $total_popayan ?></td>
            <td class="td-simple">  <?= $total_regional ?></td>
            <td class="td-simple"><?= $total_ambas ?></td>
            <td class="td-simple"><?= $total_popayan + $total_regional + $total_ambas ?></td>
            <td></td>
        </tr>
    </tbody>
</table>
            </div>
        </div>
</div>    
<div class="row mt-3 unacauca-actions-section">
<?php if ($todosCerrados && $cierreperiodo != '1') { ?>
    <div class="col-md-12 text-center mb-3 d-grid">
        <?php if ($resultb->num_rows > 0) {
            if ($row['dp_estado_total'] == 1) {
                if ($tipo_usuario != 2) { ?>
                    <button class="btn unacauca-btn-reprint" onclick="reimprOficio_depto()" style="border-radius: 30px;">
                        Reimprimir Oficio
                    </button>
                <?php }
            } elseif ($acepta_vra == '2' && $tipo_usuario == 3) { ?>
                <button class="btn unacauca-btn-reprint" onclick="reimprOficio_depto()" style="border-radius: 30px;">
                    Reimprimir Oficio
                </button>
            <?php } elseif ($acepta_vra != '2' && $tipo_usuario == 3) { ?>
                <button class="btn btn-unicauca-primary btn-lg" data-bs-toggle="modal" data-bs-target="#myModal" style="border-radius: 30px;">
                    <i class="fas fa-file-download me-2"></i> Enviar a Facultad (Descargar Oficio)
                </button>
            <?php }
        } ?>
    </div>
<?php } else { ?>
    
        <div class="col-md-12 text-center mb-3 d-grid">
            <?php if ($tipo_usuario == 3) {
                // Mostrar botón de Reimprimir incluso si el periodo está cerrado
                if ($resultb->num_rows > 0 && ($acepta_vra == '2' || $row['dp_estado_total'] == 1)) { ?>
                    <button class="btn unacauca-btn-reprint" onclick="reimprOficio_depto()" style="border-radius: 30px;">
                        (solicitud enviada) Reimprimir Oficio
                    </button>
                <?php } elseif (!$todosCerrados) { ?>
                    <div data-bs-toggle="tooltip" data-bs-placement="top" title="Confirmar profesores para poder enviar">
                        <button class="btn btn-unicauca-primary btn-lg disabled" disabled style="border-radius: 30px;">
                            <i class="fas fa-file-download me-2"></i> Enviar a Facultad (Descargar Oficio)
                        </button>
                    </div>
                <?php } elseif ($cierreperiodo == '1') { ?>
                    <div data-bs-toggle="tooltip" data-bs-placement="top" title="Periodo <?= $anio_semestre ?> cerrado">
                        <button class="btn btn-unicauca-primary btn-lg disabled" disabled style="border-radius: 30px;">
                            <i class="fas fa-file-download me-2"></i> Enviar a Facultad (Descargar Oficio)
                        </button>
                    </div>
                <?php }
            } ?>
        </div>
   
<?php } 
    
    function obtenerPeriodoAnterior($anio_semestre) {
    list($anio, $semestre) = explode('-', $anio_semestre);
    if ($semestre == '1') {
        $anio--;
        $semestre = '2';
    } else {
        $semestre = '1';
    }
    return $anio . '-' . $semestre;
}
$anio_semestre_anterior= obtenerPeriodoAnterior($anio_semestre);
    ?>


<div class="col-md-12 text-center mb-3 d-grid">
    <a href="excel_temporales_fac.php?tipo_usuario=<?php echo htmlspecialchars($tipo_usuario); ?>&departamento_id=<?php echo htmlspecialchars($departamento_id); ?>&facultad_id=<?php echo htmlspecialchars($facultad_id); ?>&anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" 
       class="btn btn-unicauca-success"
       style="border-radius: 30px; padding: 0.6rem 1.2rem;">
        <i class="fas fa-file-excel me-2"></i> Descargar Reporte XLS
    </a>
    
    <!-- Botón "Ver Comparativo" (estilo similar al TD de departamento_comparativo.php) -->
<form action="depto_comparativo.php" method="POST" class="d-inline mt-2">
    <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
    <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
    <input type="hidden" name="anio_semestre_anterior" value="<?php echo htmlspecialchars($anio_semestre_anterior); ?>">
    <!-- Bandera oculta para identificar el origen -->
    <input type="hidden" name="envia" value="consulta_todo_depto">  <!-- ¡Nuevo campo! -->
    
    <form action="<?= $archivo_regreso ?>" method="post">
    <input type="hidden" name="anio_semestre" value="<?= htmlspecialchars($anio_semestre) ?>">
    <input type="hidden" name="departamento_id" value="<?= htmlspecialchars($departamento_id) ?>">

    <button type="submit" 
            class="btn btn-outline-primary" 
            style="border-radius: 30px; 
                   padding: 0.5rem 1.5rem;
                   position: relative;
                   transition: all 0.2s;
                   border: 1px solid #0d6efd;
                   background: none;
                   width: 100%;">
        <i class="fas fa-chart-bar me-2"></i>  
        Comparativo (<?= htmlspecialchars($anio_semestre) ?> vs <?= htmlspecialchars($anio_semestre_anterior) ?>)
        <span class="badge bg-primary bg-opacity-10 text-primary ms-2" 
              style="font-size: 0.7em; 
                     position: absolute;
                     right: 15px;
                     top: 50%;
                     transform: translateY(-50%);
                     opacity: 0;
                     transition: opacity 0.3s;">
            →
        </span>
    </button>
</form>

</form>
</div>
    <?php if ($tipo_usuario == 3) {
        $aceptacion_fac = obteneraceptacionfac($departamento_id, $anio_semestre);
        $aceptacion_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
        $osbservacion_fac = obtenerobs_fac($departamento_id, $anio_semestre);
        $osbservacion_vra = obtenerobs_vra($facultad_id, $anio_semestre);
    ?>
    <div class="col-md-12 text-center unacauca-status-section d-flex justify-content-center flex-wrap align-items-stretch gap-4 mb-4">
     <div class="unacauca-status-item p-3">
        <h6 class="unacauca-status-label mb-2">Respuesta Facultad:</h6>
        <div class="unacauca-status-badge-container d-flex align-items-center">
            <?php
            if ($aceptacion_fac === 'aceptar') {
                echo "<span class='unacauca-status-badge status-accepted'><i class='fas fa-check-circle me-2'></i> Aceptado</span>";
            } elseif ($aceptacion_fac === 'rechazar') {
                // Apply htmlspecialchars just ONCE for attribute safety
                echo "<span class='unacauca-status-badge status-rejected'>Devuelto</span>
                      <button class='btn unacauca-btn-icon btn-sm ms-2' data-bs-toggle='modal' data-bs-target='#modalObservacion' data-obs=\"" . htmlspecialchars($osbservacion_fac, ENT_QUOTES, 'UTF-8') . "\">
                          <i class='fa-solid fa-info-circle fa-lg'></i>
                      </button>";
            } else {
                echo "<span class='unacauca-status-badge status-pending'><i class='fas fa-hourglass-half me-2'></i> Pendiente</span>";
            }
            ?>
        </div>
    </div>

        <div class="unacauca-status-item p-3">
            <h6 class="unacauca-status-label mb-2">Respuesta Vice-Académica:</h6>
            <div class="unacauca-status-badge-container d-flex align-items-center">
                <?php
                if ($aceptacion_vra == 2) {
                    echo "<span class='unacauca-status-badge status-accepted'><i class='fas fa-check-circle me-2'></i> Aceptado</span>";
                } elseif ($aceptacion_vra == 1) {
                    echo "<span class='unacauca-status-badge status-rejected'>Devuelto</span>
                          <button class='btn unacauca-btn-icon btn-sm ms-2' data-bs-toggle='modal' data-bs-target='#modalObservacion' data-obs=\"" . htmlspecialchars($osbservacion_vra) . "\">
                              <i class='fa-solid fa-info-circle fa-lg'></i>
                          </button>";
                } else {
                    echo "<span class='unacauca-status-badge status-pending'><i class='fas fa-hourglass-half me-2'></i> Pendiente</span>";
                }
                ?>
            </div>
        </div>
    </div>
    <?php } ?>

<div class="modal fade" id="modalObservacion" tabindex="-1" role="dialog" aria-labelledby="modalObservacionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"> <div class="modal-content unacauca-modal-content">
            <div class="modal-header unacauca-modal-header">
                <h5 class="modal-title" id="modalObservacionLabel">Detalle de Observación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body unacauca-modal-body">
                <p id="observacionContent" class="unacauca-observation-text text-break"></p>
            </div>
            <div class="modal-footer unacauca-modal-footer">
                <button type="button" class="btn btn-secondary unacauca-btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content unacauca-modal-content">
                <div class="modal-header unacauca-modal-header">
                    <h5 class="modal-title" id="myModalLabel">Información Adicional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body unacauca-modal-body">
                    <form id="modalForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="num_oficio" class="form-label">Número de Oficio</label>
                                <input type="text" class="form-control unacauca-input" id="num_oficio" name="num_oficio" value="<?php echo obtenerTRDDepartamento($departamento_id) . '/'; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_oficio" class="form-label">Fecha de Oficio</label>
                                <input type="date" class="form-control unacauca-input" id="fecha_oficio" name="fecha_oficio" value="<?php echo date('Y-m-d', strtotime('next Monday', strtotime(date('Y-m-d')))); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="elaboro" class="form-label">Jefe de Departamento<sup>*</sup></label>
                            <input type="text" class="form-control unacauca-input" id="elaboro" name="elaboro" value="<?php echo $profe_en_cargo; ?>" placeholder="Ej. Pedro Perez" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="acta" class="form-label">Número de Acta<sup>*</sup></label>
                                <input type="text" class="form-control unacauca-input" id="acta" name="acta" placeholder="Ej. No. 02" value="<?php echo $num_acta; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_acta" class="form-label">Fecha del Acta<sup>*</sup></label>
                                <input type="date" class="form-control unacauca-input" id="fecha_acta" name="fecha_acta" value="<?php echo $fecha_acta; ?>" required>
                            </div>
                        </div>

                        <div class="form-group mt-4 unacauca-folio-section">
                            <h6 class="section-title text-unicauca-primary fw-bold mb-3">Distribución de Folios</h6>
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-8 text-start">
                                    <label for="folios1" class="mb-0 label-italic">FOR-59. Acta de Selección</label>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control folio-input unacauca-input" id="folios1" name="folios1" value="1" min="0" oninput="updateFoliosTotal()" required>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-8 text-start">
                                    <label for="folios2" class="mb-0 label-italic">FOR 45. Revisión Requisitos</label>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control folio-input unacauca-input" id="folios2" name="folios2" value="<?php echo $totalItems; ?>" min="0" oninput="updateFoliosTotal()" required>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-8 text-start">
                                    <label for="folios3" class="mb-0 label-italic">Otros: (hojas de vida y actualizaciones)</label>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control folio-input unacauca-input" id="folios3" name="folios3"
                                           placeholder="0" min="0" oninput="updateFoliosTotal()"
                                           onblur="if(this.value === '') this.value = 0;">
                                </div>
                            </div>
                            <div class="mt-3 unacauca-total-folios">
                                <label class="label-italic">Total de Folios:
                                    <span id="totalFoliosDisplay" class="label-italic">
                                        <strong><?php echo $totalItems + 1; ?></strong>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" id="folios" name="folios">
                    </form>
                </div>
               <div class="modal-footer unacauca-modal-footer">
    <button type="button" class="btn btn-secondary unacauca-btn-secondary modal-close-btn" data-bs-dismiss="modal">Cerrar</button>
    <button type="button" class="btn btn-primary unacauca-btn-primary modal-submit-btn" onclick="submitForm()">Enviar</button>
</div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // ... (Tu código existente para otros modales o funciones) ...

    const observacionModal = document.getElementById('modalObservacion');

    if (observacionModal) {
        observacionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // El botón que disparó el modal
            let observationText = button.getAttribute('data-obs'); // Obtiene el texto del atributo data-obs

            // *** EL TRUCO ESTÁ AQUÍ ***
            // Paso 1: Crea un elemento DIV temporal para decodificar cualquier entidad HTML.
            // Esto convierte "&lt;br /&gt;" a "<br />", "&amp;" a "&", etc.
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = observationText; // Se usa innerHTML para que el navegador decodifique
            let decodedContent = tempDiv.textContent || tempDiv.innerText || ""; // Obtiene el texto decodificado como texto plano

            // Paso 2: Ahora que tenemos el texto decodificado (con '\n' si existían o con '<br />' si la DB los guardó así),
            // convierte cualquier '\n' (salto de línea real) a la etiqueta HTML <br />.
            // Si tu base de datos ya guarda <br />, esta línea no hará cambios en ellos.
            const finalHtmlToDisplay = decodedContent.replace(/\n/g, '<br>');

            // Paso 3: Asigna el resultado final al innerHTML del párrafo del modal.
            // Esto hará que <br /> se interprete como un salto de línea HTML.
            const modalBodyP = observacionModal.querySelector('#observacionContent');
            modalBodyP.innerHTML = finalHtmlToDisplay;
        });
    }

});
    $(document).ready(function() {
        // Script for selectAll and individualCheckbox
        $('#selectAll').change(function() {
            const isChecked = $(this).is(':checked');
            $('.individualCheckbox').prop('checked', isChecked).trigger('change');
        });

        $('.individualCheckbox').change(function() {
            const form = $(this).closest('form');
            $.ajax({
                url: 'update_visto_bueno.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log('Estado actualizado exitosamente.');
                },
                error: function() {
                    alert('Error al actualizar el estado.');
                }
            });
        });

        // The tooltip initialization using jQuery for Bootstrap 4 was here: $('[data-toggle="tooltip"]').tooltip();
        // It has been replaced by the vanilla JS Bootstrap 5 initialization in DOMContentLoaded.

        // The observation modal logic using jQuery was here:
        // $('#modalObservacion').on('show.bs.modal', function(event) { ... });
        // It has been replaced by vanilla JS in DOMContentLoaded for Bootstrap 5.
    });

    // This jQuery listener for myModal 'shown.bs.modal' is now handled by vanilla JS in DOMContentLoaded
    // $('#myModal').on('shown.bs.modal', function (e) {
    //     updateFoliosTotal();
    // });

    // The functions below (`eliminarRegistros` and `reimprOficio_depto`)
    
</script>


<script>
    $(document).ready(function() {
        // Script for selectAll and individualCheckbox
        $('#selectAll').change(function() {
            const isChecked = $(this).is(':checked');
            $('.individualCheckbox').prop('checked', isChecked).trigger('change');
        });

        $('.individualCheckbox').change(function() {
            const form = $(this).closest('form');
            $.ajax({
                url: 'update_visto_bueno.php',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log('Estado actualizado exitosamente.');
                },
                error: function() {
                    alert('Error al actualizar el estado.');
                }
            });
        });

        // Bootstrap Tooltip Initialization
  $('[data-toggle="tooltip"]').tooltip({
        html: true,
        placement: 'auto', // Elige automáticamente la mejor posición
        fallbackPlacement: 'flip', // Si no cabe, invierte la posición
        boundary: 'viewport'
    });
        // JavaScript for handling the observation modal
        // Listen for the 'show.bs.modal' event on the observation modal
        $('#modalObservacion').on('show.bs.modal', function(event) {
            // Get the button that triggered the modal
            var button = $(event.relatedTarget);

            // Extract the observation from the 'data-obs' attribute of that button
            var observation = button.data('obs');

            // Debugging: Log the observation to the console
            console.log("Observation received:", observation);

            // Get the modal itself
            var modal = $(this);

            // Find the paragraph where the observation should be displayed
            // Use .text() for plain text. If observations contain HTML (e.g., <br>), use .html()
            modal.find('#textoObservacion').text(observation);

            // Alternative if .text() is not working or if you suspect HTML issues:
            // modal.find('#textoObservacion').html(observation);
            // If observation might have line breaks, ensure the CSS 'white-space: pre-wrap;' is applied to #textoObservacion
        });
    });
</script>
<script>

    document.addEventListener('DOMContentLoaded', function() {
        const fechaOficioInput = document.getElementById('fecha_oficio');
        let today = new Date();
        let day = today.getDay();

        // If today is Saturday (6) or Sunday (0), adjust date to next Monday
        if (day === 6) { // Saturday
            today.setDate(today.getDate() + 2);
        } else if (day === 0) { // Sunday
            today.setDate(today.getDate() + 1);
        }

        // Format date to YYYY-mm-dd for date input value
        let year = today.getFullYear();
        let month = String(today.getMonth() + 1).padStart(2, '0');
        let date = String(today.getDate()).padStart(2, '0');
        let formattedDate = `${year}-${month}-${date}`;

        fechaOficioInput.value = formattedDate;
    });
</script>
           
<?php
// Cerrar la conexión a la base de datos
$conn->close();
?>
    </div>    
            

<script>
    document.getElementById('confirmForm').addEventListener('submit', function() {
        setTimeout(function() {
            location.reload();
        }, 3000); // Espera 3 segundos antes de recargar la página
    });
</script>
    <script>
    // Script para cerrar el modal automáticamente después de enviar el formulario
    $('#oficioForm').on('submit', function() {
        $('#oficioModal').modal('hide');  // Cerrar el modal
    });

    $('#oficioFacultadForm').on('submit', function() {
        $('#oficioModal').modal('hide');  // Cerrar el modal
    });
</script>
   </div>
</body>
    
</html>
