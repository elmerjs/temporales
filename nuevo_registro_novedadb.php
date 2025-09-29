<?php
// Asegúrate de que estas rutas sean correctas y que headerz.php maneje session_start()
require_once('conn.php'); // Incluye tu archivo de conexión a la base de datos

// Si headerz.php no inicia la sesión, hazlo aquí:
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// Verifica la autenticación

// Obtener los parámetros de la URL para rellenar los campos ocultos de forma segura
$facultad_id = htmlspecialchars($_GET['facultad_id'] ?? '');
$departamento_id = htmlspecialchars($_GET['departamento_id'] ?? '');
$anio_semestre = htmlspecialchars($_GET['anio_semestre'] ?? '');
$anio_semestre_anterior = htmlspecialchars($_GET['anio_semestre_anterior'] ?? ''); // Asegúrate de que este parámetro se pase si es necesario
$tipo_docente = htmlspecialchars($_GET['tipo_docente'] ?? '');
$nombre_usuario = htmlspecialchars($_SESSION['name'] ?? ''); // No se usa directamente en hidden inputs, pero puede ser útil
?>
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
            font-size: 1rem;
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
            font-size: 0.9rem;
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
            font-size: 0.8rem;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--unicauca-border);
            border-radius: 6px;
            font-family: 'Open Sans', sans-serif;
            font-size: 0.8rem;
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
            font-size: 0.8rem;
        }
        
        .info-box p {
            margin-bottom: 0;
            color: var(--unicauca-dark);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .section-title {
            font-size: 0.9rem;
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
            font-size: 1.6rem;
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
            font-size: 0.8rem;
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
                font-size: 1rem;
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
        function validarCedulaActiva(input) {
        var numDocumento = input.value.trim();
        
        // Si el campo está vacío, no hacer nada para no mostrar errores innecesarios
        if (!numDocumento) {
            return;
        }
        
        var anioSemestre = document.getElementById('anio_semestre').value;
        var nombreTerceroInput = document.getElementById('nombre');

        // Realizar una solicitud AJAX al nuevo archivo PHP
        var xhr = new XMLHttpRequest();
        xhr.open(
            'GET',
            'verificar_cedula_activa.php?cedula=' + encodeURIComponent(numDocumento) + '&anio_semestre=' + encodeURIComponent(anioSemestre),
            true
        );
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    // Procesamos la respuesta JSON del servidor
                    var response = JSON.parse(xhr.responseText);

                    if (response.existe) {
                        // Si la cédula ya existe, mostramos una alerta y limpiamos los campos
                        alert('Error: La cédula ' + numDocumento + ' ya tiene una solicitud activa para el período ' + anioSemestre + '. No se puede agregar nuevamente.');
                        input.value = ''; // Limpiar campo de cédula
                        nombreTerceroInput.value = ''; // Limpiar campo de nombre
                        input.focus(); // Devolver el foco al campo de cédula para que lo corrijan
                    }
                } catch (e) {
                    console.error("Error al procesar la respuesta del servidor:", e);
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
            var horasInput = document.querySelector('input[name="horas"]');
            var horasRInput = document.querySelector('input[name="horas_r"]');
            var horas = parseFloat(horasInput ? horasInput.value : '0') || 0;
            var horasR = parseFloat(horasRInput ? horasRInput.value : '0') || 0;
            var tipoDocente = document.querySelector('input[name="tipo_docente"]').value;
            var cedula = document.querySelector('input[name="cedula"]').value;
            var nombre = document.getElementById('nombre').value;
            var observacion = document.getElementById('observacion').value.trim();
            var tipoReemplazo = document.getElementById('tipo_reemplazo').value;
            
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
              // Valida que al menos un campo de horas tenga valor
                    if ((horas === 0 && horasR === 0) || (isNaN(horas) || isNaN(horasR))) {
                        alert('Debe ingresar al menos un valor para Horas.');
                        return false;
                    }

                    // --- ¡NUEVA VALIDACIÓN! ---
                    // Verifica que si las horas son mayores a 0, también sean mayores o iguales a 2.
                    if ((horas > 0 && horas < 2) || (horasR > 0 && horasR < 2)) {
                        alert('Si ingresa un valor para las horas, este no puede ser menor a 2.');
                        return false;
                    }

                    // Mantiene la validación del máximo de horas
                    if (horas > 12 || horasR > 12) {
                        alert('Las horas no pueden ser mayores de 12.');
                        return false;
                    }

                    // Mantiene la validación de la suma total de horas
                    if (horas + horasR > 12) {
                        alert('La suma de las horas no puede ser mayor a 12.');
                        return false;
                    }
            }

            // Validar campos de Observación y Tipo de Reemplazo (si son obligatorios)
            if (!observacion) {
                alert('El campo Observación es obligatorio.');
                return false;
            }
            if (!tipoReemplazo) {
                alert('Debe seleccionar un Tipo de Reemplazo/Justificación.');
                return false;
            }
            
            // Limpiar campos excluyentes (se mantienen las funciones limpiarTipoDedicacionR y limpiarTipoDedicacion)
            if (tipoDedicacion && tipoDedicacion.value) {
                limpiarTipoDedicacionR();
            }
            if (tipoDedicacionR && tipoDedicacionR.value) {
                limpiarTipoDedicacion();
            }
            
            return true;
        }

        function regresar() {
            document.getElementById('redirectForm').submit();
        }

        // Función para detectar el Tipo de Reemplazo/Justificación basada en la observación
        function detectarTipoReemplazo() {
            const observacion = document.getElementById('observacion').value.toLowerCase();
            const selectTipo = document.getElementById('tipo_reemplazo');
            
            const keywords = {
                'nn': 'Reemplazos NN',
                'jubilación': 'Reemplazo jubilación',
                'jubilado': 'Reemplazo jubilación',
                'jubiló': 'Reemplazo jubilación',
                'jubilada': 'Reemplazo jubilación',
                'licencia': 'Reemplazo por Licencia',
                'maternidad': 'Reemplazo por Licencia',
                'paternidad': 'Reemplazo por Licencia',
                'enfermedad': 'Reemplazo por Licencia',
                'fallecimiento': 'Reemplazo por Fallecimiento',
                'falleció': 'Reemplazo por Fallecimiento',
                'murió': 'Reemplazo por Fallecimiento',
                'renuncia': 'Reemplazo renuncia',
                'renunció': 'Reemplazo renuncia',
                'necesidad docente': 'Reemplazo necesidad docente',
                'requerimiento docente': 'Reemplazo necesidad docente',
                'falta de docente': 'Reemplazo necesidad docente',
                'no legalizó': 'No legalizó',
                'no legalizo': 'No legalizó',
                'legalizar': 'No legalizó',
                'financiación': 'Otras fuentes de financiacion',
                'financiamiento': 'Otras fuentes de financiacion',
                'matrículas': 'Ajuste de Matrículas',
                'ajuste': 'Ajuste de Matrículas',
                'matriculas': 'Ajuste de Matrículas',
                'reemplazo': 'Reemplazo',
                'sustitución': 'Reemplazo',
                'sustitucion': 'Reemplazo',
                '4-31/': 'Ajuste por VRA'
            };
            
            for (const [keyword, value] of Object.entries(keywords)) {
                if (observacion.includes(keyword)) {
                    selectTipo.value = value;
                    return;
                }
            }
            
            if (observacion.includes('reemplazo')) { // General 'reemplazo' if specific not found
                selectTipo.value = 'Reemplazo';
            } else if (selectTipo.value === '') { // If still nothing, default to 'Otro'
                selectTipo.value = 'Otro';
            }
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
                <h1><i class="fas fa-user-plus"></i> Novedad - Solicitar Profesor</h1>
                <div class="header-icon">
                    <i class="fas fa-university"></i>
                </div>
            </div>
            
          
            
            <form action="procesar_nuevo_registro_novedad_b.php" method="POST" onsubmit="return validarFormulario()">
                <input type="hidden" name="facultad_id" value="<?php echo $facultad_id; ?>">
                <input type="hidden" name="departamento_id" value="<?php echo $departamento_id; ?>">
                <input type="hidden" name="anio_semestre" id="anio_semestre" value="<?php echo $anio_semestre; ?>">
                <input type="hidden" name="anio_semestre_anterior" value="<?php echo $anio_semestre_anterior; ?>">
                <input type="hidden" name="tipo_docente" value="<?php echo $tipo_docente; ?>">
                <div class="section-title">
                    <i class="fas fa-id-card"></i>
                    <span>Datos del Profesor <span class="required">*</span></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cedula">Cédula <span class="required">*</span></label>
                        <input type="text" class="form-control" name="cedula" onblur="validarCedulaUnica(this); buscarTercero(this); validarCedulaActiva(this);" required>

                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre Completo <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nombre" id="nombre" readonly required>
                    </div>
                </div>
                
             
                
                <?php if ($tipo_docente == "Ocasional") { ?>
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
                
                <?php if ($tipo_docente == "Catedra") { ?>
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

                <div class="section-title">
                    <i class="fas fa-clipboard"></i>
                    <span>Detalles de Novedad <span class="required">*</span></span>
                </div>

                <div class="form-group">
                    <label for="observacion">Justificación <span class="required">*</span></label>
                    <textarea class="form-control" name="observacion" id="observacion" rows="3"
                              placeholder="Ej: Reemplazo por incapacidad del profsoer de planta....."
                              oninput="detectarTipoReemplazo()" required></textarea>
                </div>

                         <div class="form-group" hidden> <label for="tipo_reemplazo">Tipo de Reemplazo/Justificación <span class="required">*</span></label>
                            <select class="form-control" name="tipo_reemplazo" id="tipo_reemplazo" required>
                                <option value="">-- Seleccione una opción --</option>
                                <option value="Ajuste de Matrículas">Ajuste de Matrículas</option>
                                <option value="No legalizó">No legalizó</option>
                                <option value="Otras fuentes de financiacion">Otras fuentes de financiación</option>
                                <option value="Reemplazo">Reemplazo</option>
                                <option value="Reemplazo jubilación">Reemplazo jubilación</option>
                                <option value="Reemplazo necesidad docente">Reemplazo necesidad docente</option>
                                <option value="Reemplazo por Fallecimiento">Reemplazo por Fallecimiento</option>
                                <option value="Reemplazo por Licencia">Reemplazo por Licencia</option>
                                <option value="Reemplazo renuncia">Reemplazo renuncia</option>
                                <option value="Reemplazos NN">Reemplazos NN</option>
                                <option value="Ajuste Puntos">Ajuste Puntos</option>
                                <option value="Ajuste por VRA">Ajuste por VRA</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Agregar Profesor
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="regresar()">
                        <i class="fas fa-arrow-left"></i> Regresar
                    </button>
                </div>
            </form>
        </div>
        
        <div class="info-box" id="info-box-mensajes">
    <p><i class="fas fa-lightbulb"></i> El campo "Link Drive/Nube" es opcional, cuando se anexa o actualiza HV.</p>
</div>
     
        <form id="redirectForm" action="consulta_todo_depto_novedad.php" method="POST" style="display: none;">
            <input type="hidden" name="departamento_id" value="<?php echo $departamento_id; ?>">
            <input type="hidden" name="anio_semestre" value="<?php echo $anio_semestre; ?>">
            <input type="hidden" name="anio_semestre_anterior" value="<?php echo $anio_semestre_anterior; ?>">
        </form>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', () => {
    const cedulaInput = document.querySelector('input[name="cedula"]');
    const observacionTextarea = document.getElementById('observacion');
    // ↓↓↓ NUEVO: Seleccionamos el contenedor de mensajes ↓↓↓
    const infoBox = document.getElementById('info-box-mensajes');

    if (!cedulaInput || !observacionTextarea || !infoBox) {
        console.error('No se encontraron los campos necesarios en el formulario.');
        return;
    }

    // Variable para no mostrar el mensaje repetidamente
    let mensajeMostrado = false;

    cedulaInput.addEventListener('blur', () => {
        const cedula = cedulaInput.value.trim();
        const anioSemestre = document.querySelector('input[name="anio_semestre"]').value;
        const departamentoId = document.querySelector('input[name="departamento_id"]').value;

        if (cedula === '') return;

        fetch(`verificar_eliminacion_previa.php?cedula=${cedula}&anio_semestre=${anioSemestre}&departamento_id=${departamentoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.encontrado) {
                    observacionTextarea.value = data.observacion;
                    observacionTextarea.style.backgroundColor = '#eef2ff';
                    observacionTextarea.dispatchEvent(new Event('input'));

                    // --- LÓGICA PARA MOSTRAR LA ALERTA ---
                    if (!mensajeMostrado) {
                        // 1. Creamos un nuevo elemento <p> para nuestro mensaje
                        const alertaParrafo = document.createElement('p');
                        alertaParrafo.style.color = '#4f46e5'; // Color índigo
                        alertaParrafo.style.fontWeight = 'bold';
                        alertaParrafo.innerHTML = `<i class="fas fa-info-circle"></i> Se detectó  <strong>Cambio de Vinculación</strong>. La justificación ha sido pre-cargada.`;
                        
                        // 2. Añadimos el mensaje al principio del info-box
                        infoBox.prepend(alertaParrafo);
                        mensajeMostrado = true; // Marcamos que ya se mostró
                    }

                } else {
                    observacionTextarea.style.backgroundColor = '';
                }
            })
            .catch(error => console.error('Error al verificar:', error));
    });
});
</script>
</body>
</html>