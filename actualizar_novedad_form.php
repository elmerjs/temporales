<?php
// Establecer conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID de la solicitud
$id_solicitud = isset($_POST['id_solicitud']) ? intval($_POST['id_solicitud']) : null;

// Verificar si se recibió el ID
if (!$id_solicitud) {
    echo "No se especificó una solicitud válida.";
    exit();
}

// Obtener los parámetros enviados por el formulario
$facultad_id = isset($_POST['facultad_id']) ? intval($_POST['facultad_id']) : null;
echo  "facultad: " .$facultad_id;

$departamento_id = isset($_POST['departamento_id']) ? intval($_POST['departamento_id']) : null;
$anio_semestre = isset($_POST['anio_semestre']) ? htmlspecialchars($_POST['anio_semestre']) : null;
$tipo_docente = isset($_POST['tipo_docente']) ? htmlspecialchars($_POST['tipo_docente']) : null;
$motivo = isset($_POST['motivo']) ? htmlspecialchars($_POST['motivo']) : null;
$usuario_id = isset($_POST['usuario_id']) ? htmlspecialchars($_POST['usuario_id']) : null;

// Realizar la consulta para obtener los datos actuales de la solicitud
$sql = "SELECT * FROM solicitudes WHERE id_solicitud = ? AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_solicitud);
$stmt->execute();
$result = $stmt->get_result();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Solicitud</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
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
        button:hover {
            background-color: #003060;
        }
    </style>
   <script> function limpiarOtroSelect(seleccionado) { if (seleccionado === 'tipo_dedicacion') { document.querySelector('select[name="tipo_dedicacion_r"]').value = ''; } else if (seleccionado === 'tipo_dedicacion_r') { document.querySelector('select[name="tipo_dedicacion"]').value = ''; } } function sincronizarSelects() { const anexaHvNuevo = document.querySelector('select[name="anexa_hv_docente_nuevo"]').value; const actualizaHvAntiguo = document.querySelector('select[name="actualiza_hv_antiguo"]').value; if (anexaHvNuevo === 'Si') { document.querySelector('select[name="actualiza_hv_antiguo"]').value = 'No'; } if (actualizaHvAntiguo === 'Si') { document.querySelector('select[name="anexa_hv_docente_nuevo"]').value = 'No'; } } document.addEventListener("DOMContentLoaded", function() { const form = document.querySelector("form"); const cambiosInput = document.createElement("input"); cambiosInput.type = "hidden"; cambiosInput.name = "cambios"; form.appendChild(cambiosInput); const initialValues = {}; form.querySelectorAll("select, input").forEach((field) => { if (field.name) { initialValues[field.name] = field.value; } }); form.addEventListener("submit", function(event) { const cambios = []; let hayCambios = false; form.querySelectorAll("select, input").forEach((field) => { if (field.name && initialValues[field.name] !== field.value) { cambios.push(`${field.name}: ${initialValues[field.name]} -> ${field.value}`); hayCambios = true; } }); if (!hayCambios) { alert("Deben hacerse cambios para continuar."); event.preventDefault(); } cambiosInput.value = cambios.join(", "); }); });
    </script>
</head>
<body>
    <h1>Actualizar Solicitud</h1>
    <form action="procesar_actualizacion_novedad.php" method="POST">
        <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
       
        <!-- Campos ocultos -->
        <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
        <input type="hidden" name="motivo" value="<?php echo htmlspecialchars($motivo); ?>">
        <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario_id); ?>">
        <label for="cedula">Cédula</label>
        <input type="text" name="cedula" value="<?php echo htmlspecialchars($row['cedula']); ?>" readonly>

        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" value="<?php echo htmlspecialchars($row['nombre']); ?>" readonly>

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
            <input type="text" name="horas" value="<?php echo htmlspecialchars($row['horas']); ?>">

            <label for="horas_r">Horas Regionalización</label>
            <input type="text" name="horas_r" value="<?php echo htmlspecialchars($row['horas_r']); ?>">
        <?php } ?>

        <label for="anexa_hv_docente_nuevo">Anexa HV Nuevos</label>
        <select name="anexa_hv_docente_nuevo" onchange="sincronizarSelects()">
            <option value="si" <?php if ($row['anexa_hv_docente_nuevo'] == 'si') echo 'selected'; ?>>Si</option>
            <option value="no" <?php if ($row['anexa_hv_docente_nuevo'] == 'no') echo 'selected'; ?>>No</option>
        </select>

        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
        <select name="actualiza_hv_antiguo" onchange="sincronizarSelects()">
            <option value="si" <?php if ($row['actualiza_hv_antiguo'] == 'si') echo 'selected'; ?>>Si</option>
            <option value="no" <?php if ($row['actualiza_hv_antiguo'] == 'no') echo 'selected'; ?>>No</option>
        </select>

        <button type="submit">Actualizar Solicitud</button>
    </form>
</body>
</html>
