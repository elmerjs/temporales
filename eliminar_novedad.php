<?php
include('conn.php');
require('include/headerz.php');

//session_start();

// Verificar si ya se recibió el formulario con la cédula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'], $_POST['motivo'])) {
    // Capturar la cédula y el motivo ingresados por el usuario
    $cedula = htmlspecialchars($_POST['cedula']);
    $nombre = htmlspecialchars($_POST['nombre']);

    $motivo = htmlspecialchars($_POST['motivo']);
 // Verificar si los valores adicionales están disponibles
    $tipo_dedicacion = $_POST['tipo_dedicacion'] ?? null;
    $tipo_dedicacion_r = $_POST['tipo_dedicacion_r'] ?? null;
    $horas = $_POST['horas'] ?? null;
    $horas_r = $_POST['horas_r'] ?? null;
    $sede = $_POST['sede'] ?? null;
    $anexa_hv_docente_nuevo = $_POST['anexa_hv_docente_nuevo'] ?? null;
    $actualiza_hv_antiguo = $_POST['actualiza_hv_antiguo'] ?? null;
    
    
    // Verificar que los demás parámetros necesarios hayan sido enviados
    if (isset($_GET['facultad_id'], $_GET['departamento_id'], $_GET['anio_semestre'], $_GET['tipo_docente'], $_GET['tipo_usuario'])) {
        // Capturar parámetros de la URL
        $facultad_id = intval($_GET['facultad_id']);
        $departamento_id = intval($_GET['departamento_id']);
        $anio_semestre = htmlspecialchars($_GET['anio_semestre']);
        $tipo_docente = htmlspecialchars($_GET['tipo_docente']);
        $tipo_usuario = htmlspecialchars($_GET['tipo_usuario']);
        
        // Obtener el nombre del usuario de la sesión
        $nombre_sesion = $_SESSION['name']; // Aquí usamos el nombre almacenado en la sesión

        try {
            // Crear JSON con los cambios propuestos
           $cambios_propuestos = [
        'cedula' => $cedula,
        // Cambiar 'tipo_dedicacion' por 'dedic pop' y 'tipo_dedicacion_r' por 'dedic reg' si existe
                'nombre' => $nombre,

               'dedic pop' => $tipo_dedicacion,
        'dedic reg' => $tipo_dedicacion_r,
        // Cambiar 'horas' por 'hrs pop' y 'horas_r' por 'hrs reg' si existe
        'hrs pop' => $horas,
        'hrs reg' => $horas_r,
        'sede' => $sede,
        // Cambiar 'anexa_hv_docente_nuevo' y 'actualiza_hv_antiguo' según corresponda
        'anexa hv' => $anexa_hv_docente_nuevo,
        'actualiza hv' => $actualiza_hv_antiguo,
        'motivo' => $motivo,
       'fecha_sistema' => date('Y-m-d H:i:s') // Añadido: fecha y hora del sistema

    ];
            // Convertir a formato JSON
            $detalle_novedad_json = json_encode($cambios_propuestos);

            // Consulta para obtener el ID del usuario basado en su nombre
            $consulta_usuario = "SELECT Id FROM users WHERE Name = ?";
            if ($stmt = $conn->prepare($consulta_usuario)) {
                // Pasar el nombre del usuario
                $stmt->bind_param("s", $nombre_sesion);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($row = $resultado->fetch_assoc()) {
                    $usuario_id = $row['Id']; // Obtener el usuario_id desde la base de datos
                } else {
                    throw new Exception("No se encontró el usuario en la base de datos.");
                }
                
            }

            // Consulta para insertar la novedad en solicitudes_novedades
            $sql = "INSERT INTO solicitudes_novedades 
                    (facultad_id, departamento_id, periodo_anio, tipo_docente, tipo_usuario, tipo_novedad, detalle_novedad, usuario_id) 
                    VALUES (?, ?, ?, ?, ?, 'eliminar', ?, ?)";

            // Preparar la consulta
            if ($stmt = $conn->prepare($sql)) {
                // Pasar parámetros a la consulta
                $stmt->bind_param("iisssss", $facultad_id, $departamento_id, $anio_semestre, $tipo_docente, $tipo_usuario, $detalle_novedad_json, $usuario_id);

                // Ejecutar la consulta
                if ($stmt->execute()) {
                    // Redirigir usando formulario POST
                    echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
                    echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
                    echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
                    echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
                    echo "<input type='hidden' name='mensaje' value='eliminado_exitoso'>";
                    echo "</form>";
                    echo "<script>document.getElementById('redirectForm').submit();</script>";
                    exit();
                } else {
                    // Error al ejecutar
                    echo "<form id='redirectForm' action='consulta_todo_depto.php' method='POST'>";
                    echo "<input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>";
                    echo "<input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>";
                    echo "<input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>";
                    echo "<input type='hidden' name='mensaje' value='error_eliminar'>";
                    echo "</form>";
                    echo "<script>document.getElementById('redirectForm').submit();</script>";
                    exit();
                }
            } else {
                throw new Exception("Error al preparar la consulta.");
            }
        } catch (Exception $e) {
            // Mostrar error
            echo "Error: " . $e->getMessage();
        }
    } else {
        // Si faltan parámetros, mostrar mensaje de error
        echo "Faltan parámetros en la solicitud.";
    }
} else {
    // Obtener el departamento_id y anio_semestre de la URL
    if (isset($_GET['facultad_id'], $_GET['departamento_id'], $_GET['anio_semestre'], $_GET['tipo_docente'])) {
        $facultad_id = intval($_GET['facultad_id']);
        $departamento_id = intval($_GET['departamento_id']);
        $anio_semestre = htmlspecialchars($_GET['anio_semestre']);
        $tipo_docente = htmlspecialchars($_GET['tipo_docente']);

        // Consulta para obtener cédulas y nombres de solicitudes con los parámetros establecidos
        $sqls = "SELECT cedula, nombre, 
        
        
        
        tipo_dedicacion,	tipo_dedicacion_r,	horas,horas_r,sede,anexa_hv_docente_nuevo, actualiza_hv_antiguo
    
    
        FROM solicitudes 
        WHERE anio_semestre = ? 
        AND departamento_id = ? 
        AND tipo_docente = ? 
        AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

if ($stmt_s = $conn->prepare($sqls)) { // Usamos $stmt_s para esta consulta
    $stmt_s->bind_param("sis", $anio_semestre, $departamento_id, $tipo_docente);
    $stmt_s->execute();
    $resultado = $stmt_s->get_result();
    echo "tipo docente: ".$tipo_docente. "anio pe".$anio_semestre."depto: ".$departamento_id;
    // Mostrar formulario con select si hay resultados
    if ($resultado->num_rows > 0) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Novedad- solicutud eliminación de profesor</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        </head>
        <body>
            <div class="container mt-5">
                <h2>Novedad- solicutud eliminación de profesor</h2>
                <form method="POST" action="">
                    <div class="mb-3">
    <label for="cedula" class="form-label">Seleccione la cédula del tercero</label>
    <select class="form-control" id="cedula" name="cedula" required>
        <?php
        // Mostrar las opciones del select con atributos personalizados
        while ($row = $resultado->fetch_assoc()) {
            echo "<option value='" . htmlspecialchars($row['cedula']) . "' 
                data-nombre='" . htmlspecialchars($row['nombre']) . "' 
                data-tipo-dedicacion='" . htmlspecialchars($row['tipo_dedicacion']) . "' 
                data-tipo-dedicacion-r='" . htmlspecialchars($row['tipo_dedicacion_r']) . "' 
                data-horas='" . htmlspecialchars($row['horas']) . "' 
                data-horas-r='" . htmlspecialchars($row['horas_r']) . "' 
                data-sede='" . htmlspecialchars($row['sede']) . "' 
                data-anexa-hv-docente-nuevo='" . htmlspecialchars($row['anexa_hv_docente_nuevo']) . "' 
                data-actualiza-hv-antiguo='" . htmlspecialchars($row['actualiza_hv_antiguo']) . "'>"
                . htmlspecialchars($row['cedula']) . " - " . htmlspecialchars($row['nombre']) . 
            "</option>";
        }
        ?>
    </select>

    <!-- Campos ocultos -->
    <input type="hidden" name="nombre" id="nombre">
    <input type="hidden" name="tipo_dedicacion" id="tipo_dedicacion">
    <input type="hidden" name="tipo_dedicacion_r" id="tipo_dedicacion_r">
    <input type="hidden" name="horas" id="horas">
    <input type="hidden" name="horas_r" id="horas_r">
    <input type="hidden" name="sede" id="sede">
    <input type="hidden" name="anexa_hv_docente_nuevo" id="anexa_hv_docente_nuevo">
    <input type="hidden" name="actualiza_hv_antiguo" id="actualiza_hv_antiguo">
</div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo de la eliminación</label>
                        <textarea class="form-control" id="motivo" name="motivo" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Confirmar Eliminación</button>
<form id="redirectForm" action="consulta_todo_depto.php" method="POST">
    <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
    <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
    <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
    <input type="hidden" name="mensaje" value="error_cancelar">
    <button type="submit" class="btn btn-secondary">Cancelar</button>
</form>                </form>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "No se encontraron registros para esta solicitud.";
    }

    // Cerrar el statement después de usarlo
    $stmt_s->close();
} else {
    echo "Error al ejecutar la consulta de solicitudes.";
}
    } else {
        echo "Faltan parámetros en la solicitud.";
    }
}

// Cerrar conexión
$conn->close();
?>
<script>
    // Función para actualizar los campos ocultos según el elemento seleccionado
    function updateHiddenFields() {
        const select = document.getElementById('cedula');
        const selectedOption = select.options[select.selectedIndex];

        // Actualizar los campos ocultos con los valores de los atributos data
        document.getElementById('nombre').value = selectedOption.getAttribute('data-nombre');
        document.getElementById('tipo_dedicacion').value = selectedOption.getAttribute('data-tipo-dedicacion');
        document.getElementById('tipo_dedicacion_r').value = selectedOption.getAttribute('data-tipo-dedicacion-r');
        document.getElementById('horas').value = selectedOption.getAttribute('data-horas');
        document.getElementById('horas_r').value = selectedOption.getAttribute('data-horas-r');
        document.getElementById('sede').value = selectedOption.getAttribute('data-sede');
        document.getElementById('anexa_hv_docente_nuevo').value = selectedOption.getAttribute('data-anexa-hv-docente-nuevo');
        document.getElementById('actualiza_hv_antiguo').value = selectedOption.getAttribute('data-actualiza-hv-antiguo');
    }

    // Añade un evento change al select
    document.getElementById('cedula').addEventListener('change', updateHiddenFields);

    // Inicializa los campos ocultos al cargar la página
    window.onload = updateHiddenFields;
</script>

<script>
    // Script para actualizar los campos ocultos al cambiar la selección
    document.getElementById('cedula').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
              document.getElementById('nombre').value = selectedOption.getAttribute('data-nombre');
  
        document.getElementById('tipo_dedicacion').value = selectedOption.getAttribute('data-tipo-dedicacion');
        document.getElementById('tipo_dedicacion_r').value = selectedOption.getAttribute('data-tipo-dedicacion-r');
        document.getElementById('horas').value = selectedOption.getAttribute('data-horas');
        document.getElementById('horas_r').value = selectedOption.getAttribute('data-horas-r');
        document.getElementById('sede').value = selectedOption.getAttribute('data-sede');
        document.getElementById('anexa_hv_docente_nuevo').value = selectedOption.getAttribute('data-anexa-hv-docente-nuevo');
        document.getElementById('actualiza_hv_antiguo').value = selectedOption.getAttribute('data-actualiza-hv-antiguo');
    });
</script>
