<?php
include('conn.php');
require('include/headerz.php');
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
// Verificar si se enviaron los parámetros requeridos
if (isset($_GET['facultad_id'], $_GET['departamento_id'], $_GET['anio_semestre'], $_GET['tipo_docente'], $_GET['tipo_usuario'])) {
    $facultad_id = intval($_GET['facultad_id']);
    $departamento_id = intval($_GET['departamento_id']);
    $anio_semestre = htmlspecialchars($_GET['anio_semestre']);
    $tipo_docente = htmlspecialchars($_GET['tipo_docente']);
    $tipo_usuario = htmlspecialchars($_GET['tipo_usuario']);

    // Consulta para obtener cédulas, nombres e ID de las solicitudes
    $sqls = "SELECT id_solicitud, cedula, nombre 
             FROM solicitudes 
             WHERE anio_semestre = ? 
               AND departamento_id = ? 
               AND tipo_docente = ? 
               AND (estado IS NULL OR estado <> 'an')";

    if ($stmt_s = $conn->prepare($sqls)) {
        $stmt_s->bind_param("sis", $anio_semestre, $departamento_id, $tipo_docente);
        $stmt_s->execute();
        $resultado = $stmt_s->get_result();

        // Mostrar formulario con select si hay resultados
        if ($resultado->num_rows > 0) {
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Novedad - Modificar Solicitud</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            </head>
            <body>
                <div class="container mt-5">
                    <h2>Novedad - Modificar Solicitud</h2>
                    <form method="POST" action="actualizar_novedad_form.php">
                        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
                        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
                        <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($tipo_usuario); ?>">
                        <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario_id); ?>">

                        <div class="mb-3">
                            <label for="id_solicitud" class="form-label">Seleccione la solicitud</label>
                            <select class="form-control" id="id_solicitud" name="id_solicitud" required>
                                <?php
                                // Mostrar las opciones del select
                                while ($row = $resultado->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['id_solicitud']) . "'>" .
                                         htmlspecialchars($row['cedula']) . " - " . htmlspecialchars($row['nombre']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo</label>
                            <textarea class="form-control" id="motivo" name="motivo" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Modificar</button>
                      
                    </form>
                      <form method="POST" action="consulta_todo_depto.php">
            <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
            <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            <input type="hidden" name="mensaje" value="error_cancelar">
            <button type="submit" class="btn btn-secondary">Cancelar</button>
        </form>
                </div>
            </body>
            </html>
            <?php
        } else {
            echo "No se encontraron registros para esta solicitud.";
        }

        // Cerrar el statement
        $stmt_s->close();
    } else {
        echo "Error al ejecutar la consulta de solicitudes.";
    }
} else {
    echo "Faltan parámetros en la solicitud.";
}

// Cerrar conexión
$conn->close();
?>
