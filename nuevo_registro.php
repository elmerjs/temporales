<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Registro - Universidad del Cauca</title>
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
        
        .required {
            color: var(--unicauca-danger);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar campos
            toggleLinkField();
        });

        function buscarTercero(input) {
            var numDocumento = input.value;
            
            // Si el campo está vacío, no ejecutar la búsqueda
            if (!numDocumento) {
                return;
            }
            
            var nombreTerceroInput = document.getElementById('nombre');
            var anioSemestre = document.getElementById('anio_semestre').value;

            // Verificar que se haya seleccionado un valor de anio_semestre
            if (!anioSemestre) {
                alert('Por favor, selecciona un año y semestre antes de buscar.');
                input.value = '';
                input.focus();
                return;
            }

            // Realizar una solicitud AJAX para buscar coincidencias en la tabla de terceros
            var xhr = new XMLHttpRequest();
            xhr.open(
                'GET',
                'buscar_tercero.php?num_documento=' + encodeURIComponent(numDocumento) + '&anio_semestre=' + encodeURIComponent(anioSemestre),
                true
            );
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var responseText = xhr.responseText.trim();
                    if (responseText === 'verificar aspirante') {
                        // Informar que no está en la base de datos para el periodo específico
                        alert(
                            `El número de documento no está en la base de datos de aspirantes para el periodo ${anioSemestre}.`
                        );
                        input.value = '';
                        nombreTerceroInput.value = '';
                        input.focus();
                    } else {
                        // Asignar el nombre del tercero al campo de entrada del nombre correspondiente
                        nombreTerceroInput.value = responseText;
                    }
                }
            };
            xhr.send();
        }

        function validarCedulaUnica(input) {
            var cedulas = document.querySelectorAll('input[name="cedula"]');
            var cedulasArray = Array.from(cedulas).map(function(element) {
                return element.value.trim();
            });

            var currentCedula = input.value.trim();
            var count = cedulasArray.filter(function(cedula) {
                return cedula === currentCedula;
            }).length;

            if (count > 1) {
                alert('Esta cédula ya ha sido ingresada.');
                input.value = '';
            }
        }

        function limpiarTipoDedicacionR() {
            document.querySelector('select[name="tipo_dedicacion_r"]').value = '';
        }

        function limpiarTipoDedicacion() {
            document.querySelector('select[name="tipo_dedicacion"]').value = '';
        }
        
        function toggleLinkField() {
            var anexaHvNuevo = document.querySelector('select[name="anexa_hv_docente_nuevo"]').value;
            var actualizaHvAntiguo = document.querySelector('select[name="actualiza_hv_antiguo"]').value;
            var linkContainer = document.getElementById('link-container');
            
            // Mostrar solo si alguno es "si"
            if (anexaHvNuevo === 'si' || actualizaHvAntiguo === 'si') {
                linkContainer.classList.add('visible');
            } else {
                linkContainer.classList.remove('visible');
            }
        }

        function validarFormulario() {
            var tipoDedicacion = document.querySelector('select[name="tipo_dedicacion"]');
            var tipoDedicacionR = document.querySelector('select[name="tipo_dedicacion_r"]');
            var horas = parseFloat(document.querySelector('input[name="horas"]').value) || 0;
            var horasR = parseFloat(document.querySelector('input[name="horas_r"]').value) || 0;
            var tipoDocente = document.querySelector('input[name="tipo_docente"]').value;
            var cedula = document.querySelector('input[name="cedula"]').value;
            var nombre = document.getElementById('nombre').value;
            
            // Validar campos obligatorios
            if (!cedula || !nombre) {
                alert('Los campos de Cédula y Nombre son obligatorios.');
                return false;
            }
            
            // Validar tipo de docente
            if (tipoDocente === "Ocasional") {
                if (!tipoDedicacion.value && !tipoDedicacionR.value) {
                    alert('Por favor diligencie al menos uno de los campos de tipo de dedicación.');
                    return false;
                }
            } else if (tipoDocente === "Catedra") {
                if ((horas === 0 && horasR === 0) || (isNaN(horas) || isNaN(horasR))) {
                    alert('Debe ingresar al menos un valor para Horas.');
                    return false;
                }
                
                if (horas < 0 || horas > 12 || horasR < 0 || horasR > 12) {
                    alert('Las horas no pueden ser menores de 0 o mayores de 12.');
                    return false;
                }
                
                if (horas + horasR > 12) {
                    alert('La suma de las horas no puede ser mayor a 12.');
                    return false;
                }
            }
            
            // Limpiar campos excluyentes
            if (tipoDedicacion.value) {
                limpiarTipoDedicacionR();
            }
            if (tipoDedicacionR.value) {
                limpiarTipoDedicacion();
            }
            
            return true;
        }

        function regresar() {
            document.getElementById('redirectForm').submit();
        }
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
                <h1><i class="fas fa-user-plus"></i> Nuevo Registro de Profesor</h1>
                <div class="header-icon">
                    <i class="fas fa-university"></i>
                </div>
            </div>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> Complete los campos para agregar un nuevo profesor.</p>
            </div>
            
            <form action="procesar_nuevo_registro.php" method="POST" onsubmit="return validarFormulario()">
                <input type="hidden" name="facultad_id" value="<?php echo isset($_GET['facultad_id']) ? htmlspecialchars($_GET['facultad_id']) : ''; ?>">
                <input type="hidden" name="departamento_id" value="<?php echo isset($_GET['departamento_id']) ? htmlspecialchars($_GET['departamento_id']) : ''; ?>">
                <input type="hidden" name="anio_semestre" id="anio_semestre" value="<?php echo isset($_GET['anio_semestre']) ? htmlspecialchars($_GET['anio_semestre']) : ''; ?>">
                <input type="hidden" name="tipo_docente" value="<?php echo isset($_GET['tipo_docente']) ? htmlspecialchars($_GET['tipo_docente']) : ''; ?>">

                <div class="section-title">
                    <i class="fas fa-id-card"></i>
                    <span>Datos del Profesor <span class="required">*</span></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cedula">Cédula <span class="required">*</span></label>
                        <input type="text" class="form-control" name="cedula" onblur="validarCedulaUnica(this); buscarTercero(this);" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre Completo <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="nombre" readonly required>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-clock"></i>
                    <span>Dedicación y Horas</span>
                </div>
                
                <?php if (isset($_GET['tipo_docente']) && $_GET['tipo_docente'] == "Ocasional") { ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tipo_dedicacion">Dedicación Popayán</label>
                            <select class="form-control" name="tipo_dedicacion" onchange="limpiarTipoDedicacionR()">
                                <option value="">Seleccione...</option>
                                <option value="TC">Tiempo Completo (TC)</option>
                                <option value="MT">Medio Tiempo (MT)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_dedicacion_r">Dedicación Regionalización</label>
                            <select class="form-control" name="tipo_dedicacion_r" onchange="limpiarTipoDedicacion()">
                                <option value="">Seleccione...</option>
                                <option value="TC">Tiempo Completo (TC)</option>
                                <option value="MT">Medio Tiempo (MT)</option>
                            </select>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if (isset($_GET['tipo_docente']) && $_GET['tipo_docente'] == "Catedra") { ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="horas">Horas Popayán</label>
                            <input type="number" class="form-control" name="horas" min="0" max="12" step="0.1">
                        </div>
                        
                        <div class="form-group">
                            <label for="horas_r">Horas Regionalización</label>
                            <input type="number" class="form-control" name="horas_r" min="0" max="12" step="0.1">
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
                        <select class="form-control" name="anexa_hv_docente_nuevo" required onchange="toggleLinkField()">
                            <option value="no" selected>No</option>
                            <option value="si">Sí</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
                        <select class="form-control" name="actualiza_hv_antiguo" required onchange="toggleLinkField()">
                            <option value="no" selected>No</option>
                            <option value="si">Sí</option>
                        </select>
                    </div>
                </div>
                
                <div id="link-container" class="link-container">
                    <div class="form-group">
                        <label for="link_anexos">Link Drive/Nube (Documentos - Opcional)</label>
                        <input type="url" class="form-control" id="link_anexos" name="link_anexos" 
                               placeholder="https://drive.google.com/...">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button ftype="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Agregar Profesor
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="regresar()">
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
        
        <!-- Formulario oculto para el botón de regresar -->
        <form id="redirectForm" action="consulta_todo_depto.php" method="POST" style="display: none;">
            <input type="hidden" name="departamento_id" value="<?php echo isset($_GET['departamento_id']) ? htmlspecialchars($_GET['departamento_id']) : ''; ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo isset($_GET['anio_semestre']) ? htmlspecialchars($_GET['anio_semestre']) : ''; ?>">
        </form>
    </div>
</body>
</html>