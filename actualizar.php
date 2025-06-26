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
    <title>Actualizar Solicitud - Unicauca</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 64, 128, 0.15);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--unicauca-border);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        
        .card::before {
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
        
        .header-icon {
            background: white;
            color: var(--unicauca-blue);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--unicauca-blue);
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--unicauca-border);
            border-radius: 6px;
            font-family: 'Open Sans', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--unicauca-light-blue);
            box-shadow: 0 0 0 3px rgba(0, 89, 179, 0.15);
        }
        
        .form-control[readonly] {
            background-color: var(--unicauca-gray);
            cursor: not-allowed;
            color: #666;
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
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-family: 'Open Sans', sans-serif;
            font-size: 1rem;
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
        
        .link-container {
            margin-top: 20px;
            display: none;
            animation: fadeIn 0.4s ease-out;
        }
        
        .link-container.visible {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .info-box {
            background: var(--unicauca-light);
            border-left: 4px solid var(--unicauca-blue);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        
        .info-box p {
            margin-bottom: 0;
            color: var(--unicauca-dark);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--unicauca-blue);
            border-bottom: 2px solid var(--unicauca-light);
            padding-bottom: 8px;
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            background: var(--unicauca-light);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .university-logo {
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .logo-text {
            color: var(--unicauca-blue);
            font-weight: 700;
            font-size: 2.2rem;
            margin-top: 10px;
            letter-spacing: -0.5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            background: linear-gradient(45deg, #004080, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .logo-subtext {
            color: var(--unicauca-dark);
            font-weight: 400;
            font-size: 1rem;
            margin-top: -5px;
            letter-spacing: 1px;
        }
        
        .form-control[type="number"] {
            appearance: textfield;
        }
        
        .form-control[type="number"]::-webkit-outer-spin-button,
        .form-control[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .card {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
        }
        
        .footer-note {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9rem;
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
            var anexaHvNuevo = document.querySelector('select[name="anexa_hv_docente_nuevo"]');
            var actualizaHvAntiguo = document.querySelector('select[name="actualiza_hv_antiguo"]');
            var linkContainer = document.getElementById('link-container');

            // Si se selecciona "Si" en uno, poner "No" en el otro
            if (anexaHvNuevo.value === 'si') {
                actualizaHvAntiguo.value = 'no';
            }

            if (actualizaHvAntiguo.value === 'si') {
                anexaHvNuevo.value = 'no';
            }
            
            // Mostrar u ocultar el campo de link
            toggleLinkField();
        }
        
        function toggleLinkField() {
            var anexaHvNuevo = document.querySelector('select[name="anexa_hv_docente_nuevo"]').value;
            var actualizaHvAntiguo = document.querySelector('select[name="actualiza_hv_antiguo"]').value;
            var linkContainer = document.getElementById('link-container');
            var linkInput = document.getElementById('link_anexos');
            
            // Mostrar solo si alguno es "si"
            if (anexaHvNuevo === 'si' || actualizaHvAntiguo === 'si') {
                linkContainer.classList.add('visible');
            } else {
                linkContainer.classList.remove('visible');
            }
        }
        
        function redirectToConsulta() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'consulta_todo_depto.php';

            // Campos ocultos dinámicos
            var departamentoId = document.createElement('input');
            departamentoId.type = 'hidden';
            departamentoId.name = 'departamento_id';
            departamentoId.value = '<?php echo htmlspecialchars($departamento_id); ?>';

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
        
        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar el formulario con animación
            setTimeout(function() {
                document.querySelector('.card').style.opacity = '1';
                document.querySelector('.card').style.transform = 'translateY(0)';
            }, 100);
            
            // Configurar visibilidad del campo link
            toggleLinkField();
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="university-logo">
            <div class="logo-text">Universidad del Cauca</div>
            <div class="logo-subtext">Sistema de Vinculación Temporal</div>
        </div>
        
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-user-edit"></i> Actualizar Solicitud de Vinculación</h1>
                <div class="header-icon">
                    <i class="fas fa-university"></i>
                </div>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> Complete los campos necesarios para actualizar la información del profesor.</p>
            </div>
            
            <form action="procesar_actualizacion.php" method="POST">
                <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
                <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
                <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                <input type="hidden" name="tipo_docente" value="<?php echo $tipo_docente; ?>">

                <div class="section-title">
                    <i class="fas fa-id-card"></i>
                    <span>Datos del Profesor</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cedula">Cédula</label>
                        <input type="text" class="form-control" name="cedula" value="<?php echo htmlspecialchars($row['cedula']); ?>" readonly required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre Completo</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($row['nombre']); ?>" readonly required>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-clock"></i>
                    <span>Dedicación y Horas</span>
                </div>
                
                <?php if ($tipo_docente == "Ocasional") { ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tipo_dedicacion">Dedicación Popayán</label>
                            <select class="form-control" name="tipo_dedicacion" onchange="limpiarOtroSelect('tipo_dedicacion')">
                                <option value="" <?php if (empty($row['tipo_dedicacion'])) echo 'selected'; ?>>Seleccione...</option>
                                <option value="TC" <?php if ($row['tipo_dedicacion'] == 'TC') echo 'selected'; ?>>Tiempo Completo (TC)</option>
                                <option value="MT" <?php if ($row['tipo_dedicacion'] == 'MT') echo 'selected'; ?>>Medio Tiempo (MT)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_dedicacion_r">Dedicación Regionalización</label>
                            <select class="form-control" name="tipo_dedicacion_r" onchange="limpiarOtroSelect('tipo_dedicacion_r')">
                                <option value="" <?php if (empty($row['tipo_dedicacion_r'])) echo 'selected'; ?>>Seleccione...</option>
                                <option value="TC" <?php if ($row['tipo_dedicacion_r'] == 'TC') echo 'selected'; ?>>Tiempo Completo (TC)</option>
                                <option value="MT" <?php if ($row['tipo_dedicacion_r'] == 'MT') echo 'selected'; ?>>Medio Tiempo (MT)</option>
                            </select>
                        </div>
                    </div>
                <?php } ?>

                <?php if ($tipo_docente == "Catedra") { ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="horas">Horas Popayán</label>
                            <input type="number" class="form-control" id="horas" name="horas" min="0" max="12" step="0.1"
                                value="<?php echo htmlspecialchars($row['horas']); ?>" onchange="validarHorasPHP()">
                        </div>
                        
                        <div class="form-group">
                            <label for="horas_r">Horas Regionalización</label>
                            <input type="number" class="form-control" id="horas_r" name="horas_r" min="0" max="12" step="0.1"
                                value="<?php echo htmlspecialchars($row['horas_r']); ?>" onchange="validarHorasPHP()">
                        </div>
                    </div>
                <?php } ?>

                <div class="section-title">
                    <i class="fas fa-file-alt"></i>
                    <span>Documentación</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="anexa_hv_docente_nuevo">Anexa HV Nuevos</label>
                        <select class="form-control" name="anexa_hv_docente_nuevo" onchange="sincronizarSelects(); toggleLinkField();">
                            <option value="no" <?= ($row['anexa_hv_docente_nuevo'] == 'no') ? 'selected' : '' ?>>No</option>
                            <option value="si" <?= ($row['anexa_hv_docente_nuevo'] == 'si') ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
                        <select class="form-control" name="actualiza_hv_antiguo" onchange="sincronizarSelects(); toggleLinkField();">
                            <option value="no" <?= ($row['actualiza_hv_antiguo'] == 'no') ? 'selected' : '' ?>>No</option>
                            <option value="si" <?= ($row['actualiza_hv_antiguo'] == 'si') ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>
                </div>
                
                <div id="link-container" class="link-container">
                    <div class="form-group">
                        <label for="link_anexos">Link Drive/Nube (Documentos - Opcional)</label>
                        <input type="url" class="form-control" id="link_anexos" name="link_anexos" 
                               value="<?php echo htmlspecialchars($row['anexos']); ?>"
                               placeholder="https://drive.google.com/...">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Registro
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="redirectToConsulta()">
                        <i class="fas fa-arrow-left"></i> Regresar
                    </button>
                </div>
            </form>
        </div>
        
        <div class="info-box">
            <p><i class="fas fa-lightbulb"></i> El campo "Link Drive/Nube" es opcional, cuando se anexa o actualiza HV.</p>
        </div>
        
        <div class="footer-note">
            Sistema de Vinculación Temporal &copy; <?php echo date('Y'); ?> - Universidad del Cauca
        </div>
    </div>
</body>
</html>