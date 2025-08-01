<?php
require('include/headerz.php');
//require 'actualizar_usuario.php'; // <-- Incluir aquí
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
    exit(); // Detener toda la ejecución del script
}
?>
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
$anio_semestre_anterior = $_GET['anio_semestre_anterior'];

// Realizar la consulta para obtener los datos actuales de la solicitud
$sql = "SELECT * FROM solicitudes WHERE id_solicitud = '$id_solicitud' AND (estado <> 'an' OR estado IS NULL)";
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
        .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(100, 100, 100, 0.6); /* fondo gris semitransparente */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.modal-content {
    background-color: #f9f9f9;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.25);
    width: 90%;
    max-width: 600px;
    z-index: 1000;
}
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
        input[type="text"],input[type="number"] , select, textarea {
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
    var horasInput = document.getElementById("horas");
    var horasRInput = document.getElementById("horas_r");

    var horas = parseFloat(horasInput.value) || 0;
    var horas_r = parseFloat(horasRInput.value) || 0;

    // Redondear a 1 decimal
    horas = Math.round(horas * 10) / 10;
    horas_r = Math.round(horas_r * 10) / 10;

    // Actualizar los campos con el valor redondeado
    horasInput.value = horas;
    horasRInput.value = horas_r;

    if (horas + horas_r > 12) {
        alert("La suma de las horas no puede ser mayor a 12.");
        horasInput.value = 0;
        horasRInput.value = 0;
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

       function redirectToConsulta() {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'depto_comparativo.php';

    var departamentoId = document.createElement('input');
    departamentoId.type = 'hidden';
    departamentoId.name = 'departamento_id';
    departamentoId.value = '<?php echo htmlspecialchars($departamento_id); ?>';

    var anioSemestre = document.createElement('input');
    anioSemestre.type = 'hidden';
    anioSemestre.name = 'anio_semestre';
    anioSemestre.value = '<?php echo htmlspecialchars($anio_semestre); ?>';
    
    var anioSemestreAnterior = document.createElement('input');
    anioSemestreAnterior.type = 'hidden';
    anioSemestreAnterior.name = 'anio_semestre_anterior';
    anioSemestreAnterior.value = '<?php echo htmlspecialchars($anio_semestre_anterior); ?>';

    form.appendChild(departamentoId);
    form.appendChild(anioSemestre);
    form.appendChild(anioSemestreAnterior);

    document.body.appendChild(form);
    form.submit();
}
    </script>
</head>
<body>
   <div class="modal-overlay">
    <div class="modal-content">
        <h1>Actualizar Solicitud</h1>
        <form action="procesar_actualizacion.php" method="POST">
        <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            
            
        <input type="hidden" name="anio_semestre_anterior" value="<?php echo htmlspecialchars($anio_semestre_anterior); ?>">

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
                <input type="number" id="horas" name="horas" value="<?php echo htmlspecialchars($row['horas']); ?>" step="0.1" min="0" max="12" onchange="validarHorasPHP()">

                <label for="horas_r">Horas Regionalización</label>
                <input type="number" id="horas_r" name="horas_r" value="<?php echo htmlspecialchars($row['horas_r']); ?>" step="0.1" min="0" max="12" onchange="validarHorasPHP()">
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

<label for="observacion">Observación</label>
<textarea name="observacion" id="observacion" rows="4" style="width: 100%;" 
          placeholder="<?php echo empty($row['s_observacion']) ? 'Evidencia de que se solicitó el cambio al responsable, pero no fue atendido' : ''; ?>"
          oninput="detectarTipoReemplazo()"><?php echo htmlspecialchars($row['s_observacion']); ?></textarea>
<!-- Campo de Tipo de Reemplazo/Justificación actualizado -->
<label for="tipo_reemplazo">Tipo de Reemplazo/Justificación</label>
<select name="tipo_reemplazo" id="tipo_reemplazo" required>
    <option value="">-- Seleccione una opción --</option>
    <option value="Ajuste de Matrículas">Ajuste de Matrículas</option>
    <option value="Otras fuentes de financiacion">Otras fuentes de financiación</option>
    <option value="Reemplazo por ajuste de labor y/o necesidad docente (+)">Reemplazo por ajuste de labor y/o necesidad docente (+)</option>
    <option value="Reemplazo por Jubilación">Reemplazo por Jubilación</option>
    <option value="Reemplazo por Fallecimiento">Reemplazo por Fallecimiento</option>
    <option value="Reemplazor por Licencias de Maternidad">Reemplazor por Licencias de Maternidad</option>
    <option value="Reemplazo por enfermedad general">Reemplazo por enfermedad general</option>
    <option value="Reemplazo por renuncia">Reemplazo por renuncia</option>
    <option value="Reemplazo NN">Reemplazo NN</option>
    <option value="Ajuste de puntaje">Ajuste de puntaje</option>
    <option value="Otro">Otro</option>
    <option value="No puede asumir labor">No puede asumir labor</option>
    <option value="Ajustes VRA">Ajustes VRA</option>
</select>
        <button type="submit">Actualizar</button>
        <button type="button" onclick="redirectToConsulta()">Regresar</button>
    </form>
        </div>
</div>
    
    <script>
function detectarTipoReemplazo() {
    const observacion = document.getElementById('observacion').value.toLowerCase();
    const selectTipo = document.getElementById('tipo_reemplazo');
    
    const keywords = {
    'matrículas': 'Ajuste de Matrículas',
    'matriculas': 'Ajuste de Matrículas',
    // Ahora apunta a la opción más descriptiva
    'necesidad docente': 'Reemplazo por ajuste de labor y/o necesidad docente (+)',
    'requerimiento docente': 'Reemplazo por ajuste de labor y/o necesidad docente (+)',
    'falta de docente': 'Reemplazo por ajuste de labor y/o necesidad docente (+)',
    
    'no puede asumir': 'No puede asumir labor',
    'no asume': 'No puede asumir labor',
    'no continuará': 'No puede asumir labor',
    
    'financiación': 'Otras fuentes de financiacion',
    'financiamiento': 'Otras fuentes de financiacion',
    
    // Apunta a la opción actualizada
    'enfermedad': 'Reemplazo por enfermedad general',
    'incapacidad': 'Reemplazo por enfermedad general',
    
    'fallecimiento': 'Reemplazo por Fallecimiento',
    'falleció': 'Reemplazo por Fallecimiento',
    'murió': 'Reemplazo por Fallecimiento',

    // Nuevas para "Reemplazo por Jubilación"
    'jubilación': 'Reemplazo por Jubilación',
    'jubilado': 'Reemplazo por Jubilación',
    'jubiló': 'Reemplazo por Jubilación',
    'jubilada': 'Reemplazo por Jubilación',

    // Nuevas para "Reemplazor por Licencias de Maternidad"
    'maternidad': 'Reemplazor por Licencias de Maternidad',
    'licencia maternidad': 'Reemplazor por Licencias de Maternidad',

    // Nuevas para "Reemplazo por renuncia"
    'renuncia': 'Reemplazo por renuncia',
    'renunció': 'Reemplazo por renuncia',
    
    // Apunta a la opción actualizada
    'puntos': 'Ajuste de puntaje',
    'reajuste puntos': 'Ajuste de puntaje',
    'puntaje': 'Ajuste de puntaje', // Añadida, por si se usa

    // 'reemplazo' y 'sustitución' podrían apuntar a 'Reemplazo NN' si es el default para casos genéricos,
    // o a 'Reemplazo' si esa opción genérica existe.
    // Dadas tus últimas opciones, 'Reemplazo NN' parece el general.
    'NN': 'Reemplazo NN', // Aseguramos que 'NN' apunte aquí
    'reemplazo': 'Reemplazo NN', 
    'sustitución': 'Reemplazo NN', 
    'sustitucion': 'Reemplazo NN', 
    
    // Apunta a la opción actualizada
    '4-31/': 'Ajustes VRA',
    'VRA': 'Ajustes VRA', // Añadida, por si se usa
    'ajustes VRA': 'Ajustes VRA' // Añadida, por si se usa
};
    
    // Buscar coincidencias
    for (const [keyword, value] of Object.entries(keywords)) {
        if (observacion.includes(keyword)) {
            selectTipo.value = value;
            return;
        }
    }
    
    // Si no encuentra coincidencia exacta
    if (selectTipo.value === '') {
        selectTipo.value = 'Otro';
    }
}
</script>
    
</body>
</html>
