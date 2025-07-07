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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background-color: #f8f9fa;
                padding: 20px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .card {
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                border: none;
                margin-top: 30px;
            }
            .card-header {
                background: linear-gradient(135deg, #2c3e50, #1a252f);
                color: white;
                border-radius: 10px 10px 0 0 !important;
                padding: 20px;
                font-weight: 600;
            }
            .form-container {
                padding: 25px;
            }
            .info-badge {
                background-color: #e3f2fd;
                color: #0d6efd;
                border-left: 4px solid #0d6efd;
                padding: 15px;
                border-radius: 0 5px 5px 0;
                margin-bottom: 20px;
                font-size: 0.95rem;
            }
            .form-label {
                font-weight: 600;
                color: #495057;
                margin-bottom: 8px;
            }
            .form-control, .form-select {
                border: 2px solid #e0e6ed;
                border-radius: 8px;
                padding: 10px 15px;
                transition: all 0.3s;
            }
            .form-control:focus, .form-select:focus {
                border-color: #86b7fe;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }
            .btn-primary {
                background: linear-gradient(135deg, #0d6efd, #0b5ed7);
                border: none;
                padding: 8px 20px;
                font-weight: 600;
            }
            .btn-secondary {
                background: linear-gradient(135deg, #6c757d, #5c636a);
                border: none;
                padding: 8px 20px;
                font-weight: 600;
            }
            .action-buttons {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 25px;
            }
            @media (max-width: 768px) {
                .action-buttons {
                    flex-direction: column;
                }
                .action-buttons .btn {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header text-center">
                            <h4><i class="fas fa-edit me-2"></i>Registro de Novedad</h4>
                            <p class="mb-0">Modificación de solicitud docente</p>
                        </div>
                        
                        <div class="form-container">
                            <!-- Información de contexto -->
                            <div class="info-badge">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Está registrando una novedad para:</strong>
                                <div class="mt-2">
                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($anio_semestre) ?></span>
                                    <span class="badge bg-secondary me-2">Tipo: <?= htmlspecialchars($tipo_docente) ?></span>
                                    <span class="badge bg-success">Usuario: <?= htmlspecialchars($tipo_usuario) ?></span>
                                </div>
                            </div>
                            
                            <!-- Formulario principal (funcionalidad preservada) -->
                            <form method="POST" action="actualizar_novedad_form.php">
                                <input type="hidden" name="facultad_id" value="<?= htmlspecialchars($facultad_id) ?>">
                                <input type="hidden" name="departamento_id" value="<?= htmlspecialchars($departamento_id) ?>">
                                <input type="hidden" name="anio_semestre" value="<?= htmlspecialchars($anio_semestre) ?>">
                                <input type="hidden" name="tipo_docente" value="<?= htmlspecialchars($tipo_docente) ?>">
                                <input type="hidden" name="tipo_usuario" value="<?= htmlspecialchars($tipo_usuario) ?>">
                                <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuario_id) ?>">

                                <div class="mb-4">
                                    <label for="id_solicitud" class="form-label">
                                        <i class="fas fa-file-alt me-2"></i>Seleccione la solicitud
                                    </label>
                                    <select class="form-select" id="id_solicitud" name="id_solicitud" required>
                                        <?php
                                        while ($row = $resultado->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($row['id_solicitud']) . "'>" .
                                                 htmlspecialchars($row['cedula']) . " - " . htmlspecialchars($row['nombre']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="motivo" class="form-label">
                                        <i class="fas fa-comment-dots me-2"></i>Motivo de la modificación
                                    </label>
                                    <textarea class="form-control" id="motivo" name="motivo" 
                                              rows="4" placeholder="Describa el motivo detalladamente..." required></textarea>
                                </div>
                                
                                <div class="action-buttons">
                                    <form method="POST" action="consulta_todo_depto.php" class="d-inline">
                                        <input type="hidden" name="facultad_id" value="<?= htmlspecialchars($facultad_id) ?>">
                                        <input type="hidden" name="departamento_id" value="<?= htmlspecialchars($departamento_id) ?>">
                                        <input type="hidden" name="anio_semestre" value="<?= htmlspecialchars($anio_semestre) ?>">
                                        <input type="hidden" name="mensaje" value="error_cancelar">
                                        <button type="submit" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </button>
                                    </form>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Novedad
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
