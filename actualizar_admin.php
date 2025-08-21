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
/* Unicauca styles */
:root {
    --unicauca-blue: #004080;
    --unicauca-light-blue: #0059b3;
    --unicauca-dark: #003366;
    --unicauca-light: #f0f6ff;
    --unicauca-gray: #f5f5f5;
    --unicauca-border: #dcdcdc;
    --unicauca-success: #28a745;
    --unicauca-danger: #dc3545;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Open Sans', sans-serif;
    background: linear-gradient(135deg, #f8f9fa, #e6f2ff);
    color: #333;
    line-height: 1.6;
    padding: 20px;
    min-height: 100vh;
}
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(100, 100, 100, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999;
}
.modal-content {
    background: white;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0, 64, 128, 0.15);
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid var(--unicauca-border);
    width: 90%;
    max-width: 600px;
    position: relative;
    overflow: hidden;
}

.modal-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, var(--unicauca-blue), var(--unicauca-light-blue));
}

.header {
    background: var(--unicauca-blue);
    color: white;
    padding: 15px 20px;
    border-radius: 8px 8px 0 0;
    margin: -30px -30px 25px -30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--unicauca-blue);
    font-size: 0.9rem;
}

input[type="text"], input[type="number"],
select,
textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--unicauca-border);
    border-radius: 6px;
    font-family: 'Open Sans', sans-serif;
    font-size: 0.9rem;
    transition: all 0.3s;
}

input[type="text"]:focus, input[type="number"]:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--unicauca-light-blue);
    box-shadow: 0 0 0 3px rgba(0, 89, 179, 0.15);
}
input[type="text"][readonly] {
    background-color: var(--unicauca-gray);
    cursor: not-allowed;
    color: #666;
}

.btn-group {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    flex: 1;
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    font-family: 'Open Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: var(--unicauca-blue);
    color: white;
    box-shadow: 0 4px 8px rgba(0, 64, 128, 0.2);
}

.btn-primary:hover {
    background: var(--unicauca-light-blue);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 64, 128, 0.25);
}

.btn-secondary {
    background: #6c757d;
    color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.required {
    color: var(--unicauca-danger);
}
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
    .btn-group {
        flex-direction: column;
    }
    .modal-content {
        padding: 20px;
    }
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
        <div class="header">
            <h1><i class="fas fa-edit"></i> Actualizar Solicitud</h1>
        </div>
        <form action="procesar_actualizacion.php" method="POST">
        <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
            
        <input type="hidden" name="anio_semestre_anterior" value="<?php echo htmlspecialchars($anio_semestre_anterior); ?>">

        <input type="hidden" name="tipo_docente" value="<?php echo $tipo_docente; ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="cedula">Cédula</label>
                <input type="text" class="form-control" name="cedula" value="<?php echo htmlspecialchars($row['cedula']); ?>" readonly required>
            </div>
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($row['nombre']); ?>" readonly required>
            </div>
        </div>

        <?php if ($tipo_docente == "Ocasional") { ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_dedicacion">Dedicación Popayán</label>
                    <select class="form-control" name="tipo_dedicacion" onchange="limpiarOtroSelect('tipo_dedicacion')">
                        <option value="" <?php if (empty($row['tipo_dedicacion'])) echo 'selected'; ?>></option>
                        <option value="TC" <?php if ($row['tipo_dedicacion'] == 'TC') echo 'selected'; ?>>TC</option>
                        <option value="MT" <?php if ($row['tipo_dedicacion'] == 'MT') echo 'selected'; ?>>MT</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_dedicacion_r">Dedicación Regionalización</label>
                    <select class="form-control" name="tipo_dedicacion_r" onchange="limpiarOtroSelect('tipo_dedicacion_r')">
                        <option value="" <?php if (empty($row['tipo_dedicacion_r'])) echo 'selected'; ?>></option>
                        <option value="TC" <?php if ($row['tipo_dedicacion_r'] == 'TC') echo 'selected'; ?>>TC</option>
                        <option value="MT" <?php if ($row['tipo_dedicacion_r'] == 'MT') echo 'selected'; ?>>MT</option>
                    </select>
                </div>
            </div>
        <?php } ?>

       <?php if ($tipo_docente == "Catedra") { ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="horas">Horas Popayán</label>
                    <input type="number" class="form-control" id="horas" name="horas" value="<?php echo htmlspecialchars($row['horas']); ?>" step="0.1" min="0" max="12" onchange="validarHorasPHP()">
                </div>
                <div class="form-group">
                    <label for="horas_r">Horas Regionalización</label>
                    <input type="number" class="form-control" id="horas_r" name="horas_r" value="<?php echo htmlspecialchars($row['horas_r']); ?>" step="0.1" min="0" max="12" onchange="validarHorasPHP()">
                </div>
            </div>
        <?php } ?>
        <div class="form-row">
            <div class="form-group">
                <label for="anexa_hv_docente_nuevo">Anexa HV Nuevos</label>
                <select class="form-control" name="anexa_hv_docente_nuevo" onchange="sincronizarSelects()">
                    <option value="<?php echo $row['anexa_hv_docente_nuevo'];?>" selected><?php echo $row['anexa_hv_docente_nuevo'];?></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
            </div>
            <div class="form-group">
                <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
                <select class="form-control" name="actualiza_hv_antiguo" onchange="sincronizarSelects()">
                    <option value="<?php echo $row['actualiza_hv_antiguo'];?>" selected><?php echo $row['actualiza_hv_antiguo'];?></option>
                    <option value="Si">Si</option>
                    <option value="No">No</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="observacion">Observación</label>
            <textarea class="form-control" name="observacion" id="observacion" rows="4" 
                    placeholder="<?php echo empty($row['s_observacion']) ? 'Evidencia de que se solicitó el cambio al responsable, pero no fue atendido' : ''; ?>"
                    oninput="detectarTipoReemplazo()"><?php echo htmlspecialchars($row['s_observacion']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="tipo_reemplazo">Tipo de Reemplazo/Justificación</label>
            <select class="form-control" name="tipo_reemplazo" id="tipo_reemplazo" required>
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
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
            <button type="button" class="btn btn-secondary" onclick="redirectToConsulta()"><i class="fas fa-arrow-left"></i> Regresar</button>
        </div>
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
    'necesidad docente': 'Reemplazo por ajuste de labor y/o necesidad docente (+)',
    'requerimiento docente': 'Reemplazo por ajuste de labor y/o necesidad docente (+)',
    'falta de docente': 'Reemplazo por ajuste de labor y/o necesidad docente (+)',
    'no puede asumir': 'No puede asumir labor',
    'no asume': 'No puede asumir labor',
    'no continuará': 'No puede asumir labor',
    'financiación': 'Otras fuentes de financiacion',
    'financiamiento': 'Otras fuentes de financiacion',
    'enfermedad': 'Reemplazo por enfermedad general',
    'incapacidad': 'Reemplazo por enfermedad general',
    'fallecimiento': 'Reemplazo por Fallecimiento',
    'falleció': 'Reemplazo por Fallecimiento',
    'murió': 'Reemplazo por Fallecimiento',
    'jubilación': 'Reemplazo por Jubilación',
    'jubilado': 'Reemplazo por Jubilación',
    'jubiló': 'Reemplazo por Jubilación',
    'jubilada': 'Reemplazo por Jubilación',
    'maternidad': 'Reemplazor por Licencias de Maternidad',
    'licencia maternidad': 'Reemplazor por Licencias de Maternidad',
    'renuncia': 'Reemplazo por renuncia',
    'renunció': 'Reemplazo por renuncia',
    'puntos': 'Ajuste de puntaje',
    'reajuste puntos': 'Ajuste de puntaje',
    'puntaje': 'Ajuste de puntaje',
    'NN': 'Reemplazo NN',
    'reemplazo': 'Reemplazo NN',
    'sustitución': 'Reemplazo NN',
    'sustitucion': 'Reemplazo NN',
    '4-31/': 'Ajustes VRA',
    'VRA': 'Ajustes VRA',
    'ajustes VRA': 'Ajustes VRA'
    };
    
    for (const [keyword, value] of Object.entries(keywords)) {
        if (observacion.includes(keyword)) {
            selectTipo.value = value;
            return;
        }
    }
    
    if (selectTipo.value === '') {
        selectTipo.value = 'Otro';
    }
}
</script>
    
</body>
</html>
