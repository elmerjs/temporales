<?php
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
    COUNT(dp.fk_depto_dp) AS total_deptos, 
    COUNT(CASE WHEN dp.dp_estado_total = 1 AND fp.fp_acepta_vra = 2 THEN 1 END) AS processed_deptos, 
    p.estado_periodo, 
    p.estado_novedad,
    p.inicio_sem AS fecha_inicio_catedra,
    p.fin_sem AS fecha_fin_catedra,
    p.inicio_sem_oc AS fecha_inicio_ocasional,
    p.fin_sem_oc AS fecha_fin_ocasional,
    p.valor_punto
FROM 
    depto_periodo dp
JOIN  
    periodo p ON p.nombre_periodo = dp.periodo
JOIN 
    deparmanentos d ON d.PK_DEPTO = dp.fk_depto_dp
JOIN  
    fac_periodo fp ON fp.fp_fk_fac = d.FK_FAC AND fp.fp_periodo = dp.periodo
GROUP BY 
    p.nombre_periodo, 
    p.estado_periodo, 
    p.estado_novedad,
    p.inicio_sem, 
    p.fin_sem,
    p.inicio_sem_oc,
    p.fin_sem_oc,
    p.valor_punto";
$result_existing_periods = $conn->query($sql_existing_periods);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- En el <head> de tu documento -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

    <title>Gestión de Periodos</title>
    <style>
        .periodo-input {
            width: auto;
            min-width: 100px;
            max-width: 150px;
        }
        .btn-primary {
            background-color: #001282; /* Azul oscuro */
            border-color: #001282;
        }
        .btn-danger {
            background-color: #AD0000; /* Rojo oscuro */
            border-color: #AD0000;
        }
        .btn-warning {
            background-color: #001282; /* Azul oscuro */
            border-color: #001282;
            color: white; /* Color del texto para mejor contraste */
        }
        .btn-gold {
            background-color: #F8AE15; /* Dorado */
            border-color: #F8AE15;
            color: black; /* Color del texto para mejor contraste */
        }
        .alert-success {
            background-color: #8CBD22; /* Color de fondo para la alerta de éxito */
            color: #249337; /* Color del texto para la alerta de éxito */
        }
        .alert-danger {
            background-color: #EC6C1F; /* Color de fondo para la alerta de error */
            color: black; /* Color del texto para la alerta de error */
        }
       
    .btn-gray {
        background-color: #6c757d; /* Color gris */
        border-color: #6c757d; /* Color gris */
        color: white; /* Color del texto */
    }
        .cargar-aspirantes-btn {
    background-color: #007bff;
    border: none;
    color: white;
}

.cargar-aspirantes-btn:hover {
    background-color: #0056b3;
}

    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Gestión de Periodos</h1>

        <form method="POST" action="" class="mb-5">
            <div class="form-group">
                <label for="periodo">Periodo:</label>
                <input type="text" class="form-control periodo-input" id="periodo" name="periodo" value="<?php echo $next_period; ?>" required>
            </div>
            <button type="submit" name="accion" value="crear" class="btn btn-gold">Crear Periodo</button>
        </form>

        <h2 class="mb-4">Periodos Existentes</h2>
   <table class="table table-bordered">
    <thead>
        <tr>
            <th>Periodo</th>
            <th>Total Departamentos</th>
            <th>Departamentos Procesados</th>
             <th>Inicio Cátedra</th>
            <th>Fin Cátedra</th>
            <th>Inicio Ocasional</th>
            <th>Fin Ocasional</th>
            <th>Valor Punto</th>
            <th>Estado</th>
            <th>Est. Novedad</th>
            <th>Acciones</th>
            <th>Acc. Novedades</th>
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
                <td class="text-center editable" data-field="valor_punto" data-periodo="<?php echo htmlspecialchars($row['periodo']); ?>">
                    <?php echo isset($row['valor_punto']) ? '$' . number_format($row['valor_punto'], 0, ',', '.') : 'No definido'; ?>
                </td>
                
                
                <td><?php echo ($row['estado_periodo'] == 1) ? "Cerrado" : "Abierto"; ?></td>
                <td><?php echo ($row['estado_novedad'] == 1) ? "Cerrado" : "Abierto"; ?></td>
                <td>
                    <?php if ($row['processed_deptos'] == 0) { ?>
                        <form method="POST" action="" style="display:inline-block;">
                            <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                            <button type="submit" name="accion" value="eliminar" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    <?php } else { ?>
                        <form method="POST" action="" style="display:inline-block;">
                            <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                            <button type="submit" name="accion" value="eliminar" class="btn btn-danger btn-sm" disabled>Eliminar</button>
                        </form>
                    <?php } ?>

                    <?php if ($row['estado_periodo'] == 1) { ?>
                        <form method="POST" action="" style="display:inline-block;">
                            <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                            <button type="submit" name="accion" value="abrir" class="btn btn-warning btn-sm">Abrir</button>
                        </form>
                    <?php } else { ?>
                        <form method="POST" action="" style="display:inline-block;">
                            <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                            <button type="submit" name="accion" value="cerrar" class="btn btn-gray btn-sm">Cerrar</button>
                        </form>
                    <?php } ?>
                </td>
                <td>
                    <?php if ($row['estado_novedad'] == 1) { ?>
                        <form method="POST" action="" style="display:inline-block;">
                            <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                            <button type="submit" name="accion" value="abrirnov" class="btn btn-warning btn-sm">Abrir Novedades</button>
                        </form>
                    <?php } else { ?>
                        <form method="POST" action="" style="display:inline-block;">
                            <input type="hidden" name="periodo" value="<?php echo $row['periodo']; ?>">
                            <button type="submit" name="accion" value="cerrarnov" class="btn btn-gray btn-sm">Cerrar Novedades</button>
                        </form>
                    <?php } ?>
                </td>
                <td>
                    <!-- Botón para abrir el modal -->
                    <button 
                        type="button" 
                        class="btn btn-primary btn-sm cargar-aspirantes-btn" 
                        data-bs-toggle="modal" 
                        data-bs-target="#cargarAspirantesModal" 
                        data-periodo="<?php echo    $row['periodo']; ?>">
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
                        const dateObj = new Date(newValue);
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

</body>
</html>
