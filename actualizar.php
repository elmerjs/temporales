<?php
// Establecer conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de la solicitud
$id_solicitud = $_GET['id_solicitud'];

// Obtener los parámetros de la URL para redirigir después de la actualización
$facultad_id = $_GET['facultad_id'];
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$tipo_docente = $_GET['tipo_docente'];

// Realizar la consulta para obtener los datos actuales de la solicitud
$sql = "SELECT * FROM solicitudes WHERE id_solicitud = '$id_solicitud' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo "No se encontró el registro.";
    $conn->close();
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Solicitud</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #004080;
            color: white;
            font-size: 16px;
        }
    </style>
    <script>
        
 function validarHorasPHP() {
    var horas = parseFloat(document.getElementById("horas").value) || 0;
    var horas_r = parseFloat(document.getElementById("horas_r").value) || 0;

    if (horas + horas_r > 12) {
        alert("La suma de las horas no puede ser mayor a 12.");
        document.getElementById("horas").value = 0;
        document.getElementById("horas_r").value = 0;
    }
}

        
        function limpiarOtroSelect(seleccionado) {
            if (seleccionado === 'tipo_dedicacion') {
                document.querySelector('select[name="tipo_dedicacion_r"]').value = '';
            } else if (seleccionado === 'tipo_dedicacion_r') {
                document.querySelector('select[name="tipo_dedicacion"]').value = '';
            }
        }

        function sincronizarSelects() {
            var anexaHvNuevo = document.querySelector('select[name="anexa_hv_docente_nuevo"]').value;
            var actualizaHvAntiguo = document.querySelector('select[name="actualiza_hv_antiguo"]').value;

            if (anexaHvNuevo === 'Si') {
                document.querySelector('select[name="actualiza_hv_antiguo"]').value = 'No';
            }

            if (actualizaHvAntiguo === 'Si') {
                document.querySelector('select[name="anexa_hv_docente_nuevo"]').value = 'No';
                
            }
        }
    </script>
</head>
<body>
    <h1>Actualizar Solicitud</h1>
    <form action="procesar_actualizacion.php" method="POST">
        <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
        <input type="hidden" name="tipo_docente" value="<?php echo $tipo_docente; ?>">

        <label for="cedula">Cédula</label>
        <input type="text" name="cedula" value="<?php echo htmlspecialchars($row['cedula']); ?>" readonly required>

        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" value="<?php echo htmlspecialchars($row['nombre']); ?>" readonly required>

        <?php if ($tipo_docente == "Ocasional") { ?>
            <label for="tipo_dedicacion">Dedicación Popayán</label>
            <select name="tipo_dedicacion" onchange="limpiarOtroSelect('tipo_dedicacion')">
                <option value="" <?php if (empty($row['tipo_dedicacion'])) echo 'selected'; ?>></option>
                <option value="TC" <?php if ($row['tipo_dedicacion'] == 'TC') echo 'selected'; ?>>TC</option>
                <option value="MT" <?php if ($row['tipo_dedicacion'] == 'MT') echo 'selected'; ?>>MT</option>
            </select>

            <label for="tipo_dedicacion_r">Dedicación Regionalización</label>
            <select name="tipo_dedicacion_r" onchange="limpiarOtroSelect('tipo_dedicacion_r')">
                <option value="" <?php if (empty($row['tipo_dedicacion_r'])) echo 'selected'; ?>></option>
                <option value="TC" <?php if ($row['tipo_dedicacion_r'] == 'TC') echo 'selected'; ?>>TC</option>
                <option value="MT" <?php if ($row['tipo_dedicacion_r'] == 'MT') echo 'selected'; ?>>MT</option>
            </select>
        <?php } ?>

<?php if ($tipo_docente == "Catedra") { ?>
   <label for="horas">Horas Popayán</label>
<input type="number" id="horas" name="horas" min="0" max="12" step="0.1"
    value="<?php echo htmlspecialchars($row['horas']); ?>" onchange="validarHorasPHP()">

<label for="horas_r">Horas Regionalización</label>
<input type="number" id="horas_r" name="horas_r" min="0" max="12" step="0.1"
    value="<?php echo htmlspecialchars($row['horas_r']); ?>" onchange="validarHorasPHP()">

<?php } ?>



        <label for="anexa_hv_docente_nuevo">Anexa HV Nuevos</label>
        <select name="anexa_hv_docente_nuevo" onchange="sincronizarSelects()">
            <option value="<?php echo $row['anexa_hv_docente_nuevo'];?>" selected><?php echo $row['anexa_hv_docente_nuevo'];?></option>
            <option value="Si">Si</option>
            <option value="No">No</option>
    
        </select>

        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
        <select name="actualiza_hv_antiguo" onchange="sincronizarSelects()">
            <option value="<?php echo $row['actualiza_hv_antiguo'];?>" selected><?php echo $row['actualiza_hv_antiguo'];?></option>
            <option value="Si">Si</option>
            <option value="No">No</option>
     
        </select>

        <button type="submit">Actualizar</button>
        <button id="btnReturn" class="btn btn-secondary btn-lg" onclick="redirectToConsulta()">
            <i class="fas fa-rotate-left"></i> Regresar
        </button>
    </form>
</body>
</html>
<script>
function redirectToConsulta() {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'consulta_todo_depto.php';

    // Campos ocultos dinámicos
    var departamentoId = document.createElement('input');
    departamentoId.type = 'hidden';
    departamentoId.name = 'departamento_id';
    departamentoId.value = '<?php echo htmlspecialchars($depto_user); ?>';

    var anioSemestre = document.createElement('input');
    anioSemestre.type = 'hidden';
    anioSemestre.name = 'anio_semestre';
    anioSemestre.value = '<?php echo htmlspecialchars($anio_semestre); ?>';

    // Agregar campos al formulario
    form.appendChild(departamentoId);
    form.appendChild(anioSemestre);

    // Agregar el formulario al DOM y enviarlo
    document.body.appendChild(form);
    form.submit();
}
</script>
