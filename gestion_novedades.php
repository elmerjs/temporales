<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Novedades</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .json-detail {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .json-detail p {
            margin: 5px 0;
        }
        .json-detail h5 {
            font-size: 16px;
            color: #007BFF;
        }
        .json-detail .key {
            font-weight: bold;
            color: #333;
        }
        .json-detail { 
            display: flex; gap: 10px; /* Espacio entre clave y valor */ margin-bottom: 5px; /* Espacio entre líneas */ 
        } .json-key { 
            font-weight: bold; color: cornflowerblue; /* Color azul para las claves */ } 
        .json-value { flex-grow: 1;
                /* Asegura que el valor ocupe todo el espacio disponible */ word-wrap: break-word; /* Permite que las palabras largas se dividan en varias líneas si es necesario */ 
        }
    </style>
</head>
<body>

<?php

require('include/headerz.php');
require 'funciones.php';

// Incluir el archivo de conexión
include('cn.php');

// Verificar si los parámetros fueron enviados
$facultadId = $_POST['facultad_id'] ?? null;
$departamentoId = $_POST['departamento_id'] ?? null;
$anioSemestre = $_POST['anio_semestre'] ?? null;
$tipoUsuario = $_POST['tipo_usuario'] ?? null;
    $cierreperiodonov = obtenerperiodonov($anioSemestre);

// Si el tipo de usuario es 3, mostrar las novedades correspondientes
    // Consulta SQL para obtener las solicitudes de novedades
   if ($tipoUsuario == 3) { // Nivel Departamento
    $query = "SELECT 
                f.nombre_fac_min AS facultad_nombre,
                d.depto_nom_propio AS departamento_nombre,
                s.periodo_anio,
                s.tipo_docente,
                s.tipo_usuario,
                s.tipo_novedad,
                s.usuario_id,
                s.fecha_creacion,
                s.id_novedad,
                s.detalle_novedad,
                s.sn_acepta_fac,sn_id_envio_fac,sn_envio_fac_of,
                s.sn_acepta_vra
              FROM 
                solicitudes_novedades s
              JOIN 
                deparmanentos d ON s.departamento_id = d.PK_DEPTO
              JOIN 
                facultad f ON d.FK_FAC = f.PK_FAC
              WHERE 
                s.departamento_id = ? 
                AND s.periodo_anio = ?";
    $params = ["is", $departamentoId, $anioSemestre];
} elseif ($tipoUsuario == 2) { // Nivel Facultad
    $query = "SELECT 
                f.nombre_fac_min AS facultad_nombre,
                d.depto_nom_propio AS departamento_nombre,
                s.periodo_anio,
                s.tipo_docente,
                s.tipo_usuario,
                s.tipo_novedad,
                s.usuario_id,
                s.fecha_creacion,
                s.id_novedad,
                s.detalle_novedad,
                s.sn_acepta_fac,sn_id_envio_fac,sn_envio_fac_of,
                s.sn_acepta_vra
              FROM 
                solicitudes_novedades s
              JOIN 
                deparmanentos d ON s.departamento_id = d.PK_DEPTO
              JOIN 
                facultad f ON d.FK_FAC = f.PK_FAC
              WHERE 
                d.FK_FAC = ? 
                AND s.periodo_anio = ?";
    $params = ["is", $facultadId, $anioSemestre];
} elseif ($tipoUsuario == 1) { // Nivel Administrador (ver todo)
    $query = "SELECT 
                f.nombre_fac_min AS facultad_nombre,
                d.depto_nom_propio AS departamento_nombre,
                s.periodo_anio,
                s.tipo_docente,
                s.tipo_usuario,
                s.tipo_novedad,
                s.usuario_id,
                s.fecha_creacion,
                s.id_novedad,
                s.detalle_novedad,
                s.sn_acepta_fac,sn_id_envio_fac,sn_envio_fac_of,
                s.sn_acepta_vra
              FROM 
                solicitudes_novedades s
              JOIN 
                deparmanentos d ON s.departamento_id = d.PK_DEPTO
              JOIN 
                facultad f ON d.FK_FAC = f.PK_FAC
              WHERE 
                s.periodo_anio = ? 
                 AND s.sn_id_envio_fac = 2";
    $params = ["s", $anioSemestre];
} else {
    echo "<p>No tienes permisos para ver las novedades de este departamento.</p>";
    exit;
}
       echo "det:. ".$departamentoId."periodo: ".$anioSemestre;

if ($stmt = mysqli_prepare($con, $query)) {
    // Enlazar parámetros dinámicamente
    mysqli_stmt_bind_param($stmt, ...$params);

    // Ejecutar la consulta
    mysqli_stmt_execute($stmt);

    // Obtener el resultado
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
            // Estilos CSS
            echo "<style>
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    font-size: 16px;
                    text-align: left;
                }
                th, td {
                    padding: 12px;
                    border: 1px solid #ddd;
                }
                th {
                    background-color: #f4f4f4;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                tr:hover {
                    background-color: #f1f1f1;
                }
                a {
                    color: #007BFF;
                    text-decoration: none;
                    cursor: pointer;
                }
                a:hover {
                    text-decoration: underline;
                }
            </style>";

            // Mostrar la tabla con los campos seleccionados
           echo "<div class='d-flex align-items-center justify-content-between'>
        <h2 class='mb-0'>Listado de Novedades</h2>";

if ($tipoUsuario == 2) {
    echo "<button type='button' id='btnOficioNovedades' class='btn btn-primary' data-toggle='modal' data-target='#modalEnviarSeleccionados' disabled>
            Oficio novedades V.R.A.
          </button>";
}

echo "</div>";
          
echo "<table id='tablaNovedades' class='display'>";
echo "<thead>";

            // Encabezado de la tabla
            echo "<tr>
                    <th>Facultad ID</th>
                    <th>Departamento ID</th>
                    <th>Periodo Año</th>
                    <th>Tipo Docente</th>
                    <th>Tipo Novedad</th>
                    <th>Fecha Creación</th>
                    <th>Detalle</th>
                    <th>Rta. Facultad</th>";

            if ($tipoUsuario == 2) {
                echo "<th>Enviar a V.R.A.</th>";
            }

             echo "<th>Rta. VRA</th>
                </tr></thead>";

            // Filas de datos
       while ($row = mysqli_fetch_assoc($result)) {
    $detalleNovedad = json_decode($row['detalle_novedad'], true);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['facultad_nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($row['departamento_nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($row['periodo_anio']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tipo_docente']) . "</td>";
    echo "<td>" . htmlspecialchars($row['tipo_novedad']) . "</td>";
    echo "<td>" . htmlspecialchars($row['fecha_creacion']) . "</td>";
    echo "<td><a href='#' data-toggle='modal' data-target='#modal" . $row['id_novedad'] . "'>Ver Detalle</a></td>";
   echo '
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
';

// Iconos con Font Awesome
$iconoPendiente = '<i class="fas fa-hourglass-half" style="color: orange;" title="Pendiente"></i>';
$iconoRechazada = '<i class="fas fa-times-circle" style="color: red;" title="Rechazada"></i>';
$iconoAceptada = '<i class="fas fa-check-circle" style="color: green;" title="Aceptada"></i>';


echo "<td style='text-align: center;'>";

// Mostrar el estado actual
if (is_null($row['sn_acepta_fac']) || $row['sn_acepta_fac'] == 0) {
    echo "<span> $iconoPendiente</span>";
} elseif ($row['sn_acepta_fac'] == 1) {
    echo "<span> $iconoRechazada</span>";
} elseif ($row['sn_acepta_fac'] == 2) {
    echo "<span> $iconoAceptada</span>";
}

// Mostrar las opciones de acción solo para el tipoUsuario == 2
if ($tipoUsuario == 2 && (is_null($row['sn_acepta_fac']) || $row['sn_acepta_fac'] == 0) ) {
    if ($cierreperiodonov == '1') {
        // Si el periodo está cerrado, mostrar los botones deshabilitados con un título explicativo
        echo "<form style='display:inline-block; margin-left: 10px;' class='form-estado'>";
        echo "<input type='hidden' name='id_novedad' value='" . htmlspecialchars($row['id_novedad']) . "'>";
        echo "<button type='button' class='btn btn-danger btn-sm btn-estado' disabled title='Cierre de periodo $anioSemestre para novedades'>Rechazar</button>";
        echo "<button type='button' class='btn btn-success btn-sm btn-estado' disabled title='Cierre de periodo $anioSemestre para novedades'>Aceptar</button>";
        echo "</form>";
    } else {
        // Si el periodo no está cerrado, mostrar los botones habilitados
        echo "<form style='display:inline-block; margin-left: 10px;' class='form-estado'>";
        echo "<input type='hidden' name='id_novedad' value='" . htmlspecialchars($row['id_novedad']) . "'>";
        echo "<button type='button' data-estado='1' class='btn btn-danger btn-sm btn-estado' title='Rechazar'>Rechazar</button>";
        echo "<button type='button' data-estado='2' class='btn btn-success btn-sm btn-estado' title='Aceptar'>Aceptar</button>";
        echo "</form>";
    }
}
if ($tipoUsuario == 2) {
   echo "<td>";
if ($row['sn_acepta_fac'] == 1) {
    // Si fue rechazada por la facultad, muestra un guion
    echo "-";
} else {
    // De lo contrario, muestra el checkbox con las condiciones actuales
    echo "<input type='checkbox' class='seleccionar-id' value='" . htmlspecialchars($row['id_novedad']) . "' " 
        . ($row['sn_id_envio_fac'] == 2 
            ? "checked disabled title='La novedad ya está registrada para envío a la V.R.A. según oficio: " . htmlspecialchars($row['sn_envio_fac_of']) . "'" 
            : ($row['sn_acepta_fac'] == 0 || is_null($row['sn_acepta_fac']) 
                ? "disabled title='Novedad pendiente, por favor ejecute aceptación o rechazo según corresponda antes de generar oficio.'" 
                : "")
          )
        . ($cierreperiodonov == '1' 
            ? "disabled title='Periodo $anioSemestre cerrado para novedades'" 
            : "") // Condición para inhabilitar el checkbox si el periodo está cerrado
        . ">";
}
echo "</td>";
}
echo "<td>";
if ($row['sn_acepta_fac'] == 1) {
    // Si fue rechazada por la facultad, no puede ser pendiente
    echo "-";
} elseif  (is_null($row['sn_acepta_vra']) || $row['sn_acepta_vra'] == 0) {
    echo " " . $iconoPendiente;
} elseif ($row['sn_acepta_vra'] == 1) {
    echo "Rechazada " . $iconoRechazada;
} elseif ($row['sn_acepta_vra'] == 2) {
    echo "Aceptada " . $iconoAceptada;
}
           
    
if ((is_null($row['sn_acepta_vra']) || $row['sn_acepta_vra'] == 0) && ($tipoUsuario == 1)) {
    // Si el usuario es tipo 1, incluir botones de aceptar/rechazar
   
        echo "<form style='display:inline-block; margin-left: 10px;' class='form-estado-vra'>";
        echo "<input type='hidden' name='id_novedad' value='" . htmlspecialchars($row['id_novedad']) . "'>";
        echo "<button type='button' data-estado='1' class='btn btn-danger btn-sm btn-estado-vra' title='Rechazar'>Rechazar</button>";
        echo "<button type='button' data-estado='2' class='btn btn-success btn-sm btn-estado-vra' title='Aceptar'>Aceptar</button>";
        echo "</form>";
    
} 
           
           
echo "</td>";
    echo "</tr>";

    // Modal con formato del detalle
    echo "
    <div class='modal fade' id='modal" . $row['id_novedad'] . "' tabindex='-1' role='dialog' aria-labelledby='modalLabel" . $row['id_novedad'] . "' aria-hidden='true'>
      <div class='modal-dialog' role='document'>
        <div class='modal-content'>
          <div class='modal-header'>
            <h5 class='modal-title' id='modalLabel" . $row['id_novedad'] . "'>Detalle de novedad a ".$row['tipo_novedad']."</h5>
            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
              <span aria-hidden='true'>&times;</span>
            </button>
          </div>
          <div class='modal-body'>
    ";

    // Comprobar si el tipo docente es "Ocasional" o "Cátedra" y tipo novedad "adicionar"
if ($row['tipo_novedad'] == 'adicionar' || $row['tipo_novedad'] == 'modificar' || $row['tipo_novedad'] == 'eliminar') {

    if ($row['tipo_docente'] == 'Ocasional') {
        // Si es "Ocasional", ocultar "hrs pop" y "hrs reg"
        unset($detalleNovedad['hrs pop']);
        unset($detalleNovedad['hrs reg']);
    } elseif ($row['tipo_docente'] == 'Catedra') {
        // Si es "Cátedra", ocultar "dedic pop" y "dedic reg"
        unset($detalleNovedad['dedic pop']);
        unset($detalleNovedad['dedic reg']);
    }
}
    // Formatear el detalle del JSON de manera legible
    if (is_array($detalleNovedad)) {
        foreach ($detalleNovedad as $key => $value) {
            echo "<div class='json-detail'>
                    <span class='json-key'>" . htmlspecialchars($key) . ":</span>
                    <span class='json-value'>" . htmlspecialchars($value) . "</span>
                  </div>";
        }
    } else {
        echo "<p>No se pudo procesar el detalle de la novedad.</p>";
    }

    echo "
          </div>
          <div class='modal-footer'>
            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Cerrar</button>
          </div>
        </div>
      </div>
    </div>";
}


            echo "</table>";
        } else {
        echo "<p>No hay novedades para el periodo seleccionado.</p>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<p>Error en la preparación de la consulta: " . mysqli_error($con) . "</p>";
}

// Cerrar la conexión a la base de datos
mysqli_close($con);
?>


<!-- Scripts de Bootstrap -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
   
<script>
    $(document).ready(function () {
        // Configuración de DataTables
        $('#tablaNovedades').DataTable({
            pageLength: 10, // Número de registros por página por defecto
            lengthMenu: [10, 25, 50, 100], // Opciones de paginación
            language: {
                search: "Buscar:", // Texto para el campo de búsqueda
                lengthMenu: "Mostrar _MENU_ registros por página",
                zeroRecords: "No se encontraron resultados",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "No hay registros disponibles",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                },
            },
            order: [[0, 'asc']] // Ordenar por la primera columna por defecto
        });

        // Función para habilitar/deshabilitar el botón según la selección de checkboxes
        function actualizarEstadoBoton() {
            const haySeleccionados = $('.seleccionar-id:checked:not(:disabled)').length > 0;
            $('#btnOficioNovedades').prop('disabled', !haySeleccionados);
        }

        // Detectar cambios en los checkboxes
        $(document).on('change', '.seleccionar-id', function () {
            actualizarEstadoBoton();
        });

        // Inicializar el estado del botón al cargar la página
        actualizarEstadoBoton();
    });
</script>
    <script>
    $(document).on('click', '.btn-estado', function () {
        const button = $(this);
        const form = button.closest('.form-estado');
        const idNovedad = form.find('input[name="id_novedad"]').val();
        const estado = button.data('estado');

        // Si se selecciona 'Rechazar' (estado 1), solicitar motivo
        if (estado === 1) {
            const motivo = prompt('Por favor, ingrese el motivo del rechazo:');
            if (!motivo) {
                alert('Debe ingresar un motivo para rechazar.');
                return; // No continuar si no se proporciona el motivo
            }

            // Proceder con la solicitud AJAX con el motivo incluido
            enviarSolicitud(idNovedad, estado, motivo);
        } else {
            // Proceder directamente con la solicitud AJAX sin motivo
            enviarSolicitud(idNovedad, estado);
        }
    });

    function enviarSolicitud(idNovedad, estado, motivo = null) {
        $.ajax({
            url: 'actualizar_estado_novedad_fac.php', // Archivo PHP que procesa la actualización
            type: 'POST',
            data: {
                id_novedad: idNovedad,
                estado: estado,
                motivo: motivo // Enviar el motivo si existe
            },
            beforeSend: function () {
                $('.btn-estado').prop('disabled', true); // Desactivar botón para evitar múltiples clics
            },
            success: function (response) {
                alert(response); // Mostrar mensaje de éxito o error
                location.reload(); // Recargar la página para actualizar el estado
            },
            error: function (xhr, status, error) {
                console.error('Error: ' + error);
                alert('No se pudo actualizar el estado. Intenta de nuevo.');
            },
            complete: function () {
                $('.btn-estado').prop('disabled', false); // Reactivar botón
            }
        });
    }
</script>

    <!--script para aceptar rechazar de vra>-->
 <script>
    $(document).on('click', '.btn-estado-vra', function () { // Clase única para los botones de VRA
        const button = $(this);
        const form = button.closest('.form-estado-vra'); // Clase única para el formulario de VRA
        const idNovedad = form.find('input[name="id_novedad"]').val();
        const estado = button.data('estado');

        // Si se selecciona 'Rechazar' (estado 1), solicitar motivo
        if (estado === 1) {
            const motivo = prompt('Por favor, ingrese el motivo del rechazo:');
            if (!motivo) {
                alert('Debe ingresar un motivo para rechazar.');
                return; // No continuar si no se proporciona el motivo
            }

            // Proceder con la solicitud AJAX con el motivo incluido
            enviarSolicitudVRA(idNovedad, estado, motivo);
        } else {
            // Proceder directamente con la solicitud AJAX sin motivo
            enviarSolicitudVRA(idNovedad, estado);
        }
    });

    function enviarSolicitudVRA(idNovedad, estado, motivo = null) { // Función única para solicitudes de VRA
        $.ajax({
            url: 'actualizar_estado_novedad_vra.php', // Archivo PHP específico para VRA
            type: 'POST',
            data: {
                id_novedad: idNovedad,
                estado: estado,
                motivo: motivo // Enviar el motivo si existe
            },
            beforeSend: function () {
                $('.btn-estado-vra').prop('disabled', true); // Desactivar botón para evitar múltiples clics
            },
            success: function (response) {
                alert(response); // Mostrar mensaje de éxito o error
                location.reload(); // Recargar la página para actualizar el estado
            },
            error: function (xhr, status, error) {
                console.error('Error: ' + error);
                alert('No se pudo actualizar el estado. Intenta de nuevo.');
            },
            complete: function () {
                $('.btn-estado-vra').prop('disabled', false); // Reactivar botón
            }
        });
    }
</script>   


<!-- Modal para ingresar número de oficio, fecha y remitente -->
<div class="modal fade" id="modalEnviarSeleccionados" tabindex="-1" role="dialog" aria-labelledby="modalEnviarSeleccionadosLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formEnviarSeleccionados">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEnviarSeleccionadosLabel">Enviar Seleccionados</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="numeroOficio">Número de Oficio</label>
            <input type="text" class="form-control" id="numeroOficio" name="numero_oficio" required>
          </div>
          <div class="form-group">
            <label for="fechaOficio">Fecha</label>
            <input type="date" class="form-control" id="fechaOficio" name="fecha_oficio" required>
          </div>
          <div class="form-group">
            <label for="remitente">Remitente</label>
            <input type="text" class="form-control" id="remitente" name="remitente" required>
          </div>
          <input type="hidden" id="idsSeleccionados" name="ids_seleccionados">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Enviar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
$(document).ready(function () {
    $('#formEnviarSeleccionados').on('submit', function (e) {
        e.preventDefault();

        const idsSeleccionados = [];
        $('.seleccionar-id:checked:not(:disabled)').each(function () {
            idsSeleccionados.push($(this).val());
        });

        if (idsSeleccionados.length === 0) {
            alert('Debe seleccionar al menos un ID para enviar.');
            return;
        }

        // Enviar datos al backend
        $.ajax({
            url: 'procesar_selec_of_nov_a_vra.php',
            type: 'POST',
            data: {
                ids_seleccionados: idsSeleccionados,
                numero_oficio: $('#numeroOficio').val(),
                fecha_oficio: $('#fechaOficio').val(),
                remitente: $('#remitente').val()
            },
            success: function (response) {
                alert(response); // Muestra el mensaje del backend

                // Cerrar el modal
                $('#modalEnviarSeleccionados').modal('hide');

                // Generar el archivo Word
                generarArchivoWord(idsSeleccionados);

                // Esperar 2 segundos antes de refrescar la página
                setTimeout(function () {
                    location.reload();
                }, 2000);
            },
            error: function (xhr, status, error) {
                console.error('Error: ' + error);
                alert('No se pudo procesar la solicitud. Intenta de nuevo.');
            }
        });
    });

    // Función para generar el archivo Word
    function generarArchivoWord(ids) {
        // Abrir una nueva ventana/tab para descargar el Word
        const url = 'generar_word_nov.php?ids_seleccionados=' + encodeURIComponent(ids.join(','));
        window.open(url, '_blank'); // Abrir en una nueva pestaña o ventana
    }
});
</script>


<script>
    $(document).ready(function () {
        $('#miTabla').DataTable({
            paging: true, // Activa la paginación
            searching: true, // Activa la barra de búsqueda
            pageLength: 10, // Registros mostrados por página
            lengthMenu: [5, 10, 25, 50], // Opciones de cantidad de registros
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" // Traducción al español
            }
        });
    });
</script>
    
    
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
