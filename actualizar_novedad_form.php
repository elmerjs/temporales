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
        function limpiarOtroSelect(seleccionado) {
            if (seleccionado === 'tipo_dedicacion') {
                document.querySelector('select[name="tipo_dedicacion_r"]').value = '';
            } else if (seleccionado === 'tipo_dedicacion_r') {
                document.querySelector('select[name="tipo_dedicacion"]').value = '';
            }
        }

        function sincronizarSelects() {
            const anexaHvNuevo = document.querySelector('select[name="anexa_hv_docente_nuevo"]').value;
            const actualizaHvAntiguo = document.querySelector('select[name="actualiza_hv_antiguo"]').value;
            
            if (anexaHvNuevo === 'si') {
                document.querySelector('select[name="actualiza_hv_antiguo"]').value = 'no';
            }

            if (actualizaHvAntiguo === 'si') {
                document.querySelector('select[name="anexa_hv_docente_nuevo"]').value = 'no';
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Mostrar el formulario con animación
            setTimeout(function() {
                document.querySelector('.card').style.opacity = '1';
                document.querySelector('.card').style.transform = 'translateY(0)';
            }, 100);
            
            // Configurar el control de cambios
            const form = document.querySelector("form");
            const cambiosInput = document.createElement("input");
            cambiosInput.type = "hidden";
            cambiosInput.name = "cambios";
            form.appendChild(cambiosInput);

            const initialValues = {};
            form.querySelectorAll("select, input").forEach((field) => {
                if (field.name) {
                    initialValues[field.name] = field.value;
                }
            });

            form.addEventListener("submit", function(event) {
                const cambios = [];
                let hayCambios = false;
                
                form.querySelectorAll("select, input").forEach((field) => {
                    if (field.name && initialValues[field.name] !== field.value) {
                        cambios.push(`${field.name}: ${initialValues[field.name]} -> ${field.value}`);
                        hayCambios = true;
                    }
                });

                if (!hayCambios) {
                    alert("Deben hacerse cambios para continuar.");
                    event.preventDefault();
                }
                
                cambiosInput.value = cambios.join(", ");
            });
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
            
            <form action="procesar_actualizacion_novedad.php" method="POST">
                <input type="hidden" name="id_solicitud" value="<?php echo htmlspecialchars($row['id_solicitud']); ?>">
                <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($facultad_id); ?>">
                <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($departamento_id); ?>">
                <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($tipo_docente); ?>">
                <input type="hidden" name="motivo" value="<?php echo htmlspecialchars($motivo); ?>">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario_id); ?>">

                <div class="section-title">
                    <i class="fas fa-id-card"></i>
                    <span>Datos del Profesor</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cedula">Cédula</label>
                        <input type="text" class="form-control" name="cedula" value="<?php echo htmlspecialchars($row['cedula']); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre Completo</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($row['nombre']); ?>" readonly>
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
                            <input type="number" class="form-control" name="horas" value="<?php echo htmlspecialchars($row['horas']); ?>" min="0" max="12" step="0.1">
                        </div>
                        
                        <div class="form-group">
                            <label for="horas_r">Horas Regionalización</label>
                            <input type="number" class="form-control" name="horas_r" value="<?php echo htmlspecialchars($row['horas_r']); ?>" min="0" max="12" step="0.1">
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
                        <select class="form-control" name="anexa_hv_docente_nuevo" onchange="sincronizarSelects()">
                            <option value="no" <?= ($row['anexa_hv_docente_nuevo'] == 'no') ? 'selected' : '' ?>>No</option>
                            <option value="si" <?= ($row['anexa_hv_docente_nuevo'] == 'si') ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
                        <select class="form-control" name="actualiza_hv_antiguo" onchange="sincronizarSelects()">
                            <option value="no" <?= ($row['actualiza_hv_antiguo'] == 'no') ? 'selected' : '' ?>>No</option>
                            <option value="si" <?= ($row['actualiza_hv_antiguo'] == 'si') ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Registro
                    </button>
                </div>
            </form>
        </div>
        
        <div class="footer-note">
            Sistema de Vinculación Temporal &copy; <?php echo date('Y'); ?> - Universidad del Cauca
        </div>
    </div>
</body>
</html>