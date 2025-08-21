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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Registro</title>
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
/* Reemplazo de .modal-overlay para que coincida con .container en la apariencia */
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
/* Reemplazo de .modal-content con el estilo .card del ejemplo */
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
    font-size: 1.5rem; /* Ajuste para un título más grande */
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
    font-size: 0.9rem; /* Ajuste para mayor legibilidad */
}

input[type="text"],
select,
textarea {
    width: 100%; /* Ajustado para que ocupe el 100% del contenedor */
    padding: 10px 12px;
    border: 1px solid var(--unicauca-border);
    border-radius: 6px;
    font-family: 'Open Sans', sans-serif;
    font-size: 0.9rem; /* Ajuste para mayor legibilidad */
    transition: all 0.3s;
}

input[type="text"]:focus,
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

.button-container {
    display: flex;
    justify-content: space-between;
    gap: 15px; /* Separación entre botones */
    margin-top: 30px;
}

.button-container button {
    flex: 1; /* Para que ocupen el mismo ancho */
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    font-family: 'Open Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.button-container button[type="submit"] {
    background: var(--unicauca-blue);
    color: white;
    box-shadow: 0 4px 8px rgba(0, 64, 128, 0.2);
}

.button-container button[type="submit"]:hover {
    background: var(--unicauca-light-blue);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 64, 128, 0.25);
}

.regresar-button {
    background: #6c757d;
    color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.regresar-button:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.required {
    color: var(--unicauca-danger);
}
</style>
    <script>
  function buscarTercero(input) {
    var numDocumento = input.value;
    
    // Si el campo está vacío, no ejecutar la búsqueda
    if (!numDocumento) {
        return;
    }
    
    var nombreTerceroInput = input.parentElement.parentElement.querySelector('input[name="nombre"]');
    var anioSemestre = document.getElementById('anio_semestre').value; // Obtener el valor de anio_semestre

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

        function validarNombreTercero(input) {
            if (input.value.trim() === "") {
                alert("No es oferente");
                input.value = "";
                input.previousElementSibling.focus();
            }
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

        function validarFormulario() {
            var tipoDedicacion = document.querySelector('select[name="tipo_dedicacion"]').value;
            var tipoDedicacionR = document.querySelector('select[name="tipo_dedicacion_r"]').value;
            var horas = parseFloat(document.querySelector('input[name="horas"]').value);
            var horasR = parseFloat(document.querySelector('input[name="horas_r"]').value);
            var tipoDocente = document.querySelector('input[name="tipo_docente"]').value;

            if (tipoDocente === "Ocasional" && (tipoDedicacion.trim() === "" && tipoDedicacionR.trim() === "")) {
                alert('Por favor diligencie al menos uno de los campos de tipo de dedicación.');
                return false;
            }

            if ((isNaN(horas) || horas < 0 || horas > 12) && (isNaN(horasR) || horasR < 0 || horasR > 12)) {
                alert('Las horas no pueden ser menores de 0 o mayores de 12.');
                return false;
            }

            if (tipoDedicacion) {
                limpiarTipoDedicacionR();
            }
            if (tipoDedicacionR) {
                limpiarTipoDedicacion();
            }

            if (!horas && !horasR) {
                alert('Debe ingresar al menos un valor para Horas.');
                return false;
            }

            return true;
        }

        function regresar() {
            document.getElementById('redirectForm').submit();
        }
    </script>
</head>
<body>
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="header">
                <h1><i class="fas fa-user-plus"></i> Agregar Nuevo Registro</h1>
            </div>
            <form action="procesar_nuevo_registro.php" method="POST" onsubmit="return validarFormulario()">
                <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($_GET['facultad_id']); ?>">
                <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($_GET['departamento_id']); ?>">
                <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
                <input type="hidden" name="anio_semestre_anterior" value="<?php echo htmlspecialchars($_GET['anio_semestre_anterior']); ?>">
                <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($_GET['tipo_docente']); ?>">
                <input type="hidden" name="nombre_usuario" value="<?php echo htmlspecialchars($_SESSION['name']); ?>">

                <div class="form-group">
                    <label for="cedula">Cédula <span class="required">*</span></label>
                    <input type="text" class="form-control" name="cedula" onblur="validarCedulaUnica(this); buscarTercero(this);" required>
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre <span class="required">*</span></label>
                    <input type="text" class="form-control" name="nombre" id="nombre" readonly required>
                </div>

                <?php if ($_GET['tipo_docente'] == "Ocasional") { ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tipo_dedicacion">Dedicación Popayán</label>
                            <select class="form-control" name="tipo_dedicacion" onchange="limpiarTipoDedicacionR()">
                                <option value=""></option>
                                <option value="TC" selected>TC</option>
                                <option value="MT">MT</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tipo_dedicacion_r">Dedicación Regionalización</label>
                            <select class="form-control" name="tipo_dedicacion_r" onchange="limpiarTipoDedicacion()">
                                <option value=""></option>
                                <option value="TC">TC</option>
                                <option value="MT">MT</option>
                            </select>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if ($_GET['tipo_docente'] == "Catedra") { ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="horas">Horas Popayán</label>
                            <input type="text" class="form-control" name="horas">
                        </div>
                        <div class="form-group">
                            <label for="horas_r">Horas Regionalización</label>
                            <input type="text" class="form-control" name="horas_r">
                        </div>
                    </div>
                <?php } ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="anexa_hv_docente_nuevo">Anexa HV Nuevos <span class="required">*</span></label>
                        <select class="form-control" name="anexa_hv_docente_nuevo" required>
                            <option value="No">No</option>
                            <option value="Si">Si</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos <span class="required">*</span></label>
                        <select class="form-control" name="actualiza_hv_antiguo" required>
                            <option value="No">No</option>
                            <option value="Si">Si</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacion">Observación <span class="required">*</span></label>
                    <textarea name="observacion" id="observacion" rows="3" class="form-control"
                        placeholder="evidencia de que se solicitó el cambio al responsable, pero no fue atendido...Ej: Oficio 5.5./31 no se acepta cambio a ocasional, se mantiene como cátedra.."
                        oninput="detectarTipoReemplazo()"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="tipo_reemplazo">Tipo de Reemplazo/Justificación <span class="required">*</span></label>
                    <select name="tipo_reemplazo" id="tipo_reemplazo" class="form-control" required>
                        <option value="">-- Seleccione una opción --</option>
                        <option value="Ajuste de Matrículas">Ajuste de Matrículas</option>
                        <option value="Otras fuentes de financiacion">Otras fuentes de financiación</option>
                        <option value="Reemplazo">Reemplazo</option>
                        <option value="Reemplazo por Jubilación">Reemplazo por Jubilación</option>
                        <option value="Reemplazo por Necesidad Docente">Reemplazo por Necesidad Docente</option>
                        <option value="Reemplazo por Fallecimiento">Reemplazo por Fallecimiento</option>
                        <option value="Reemplazo por Licencia">Reemplazo por Licencia</option>
                        <option value="Reemplazo por Renuncia">Reemplazo por Renuncia</option>
                        <option value="Reemplazos NN">Reemplazos NN</option>
                        <option value="Necesidad docente">Necesidad docente</option>
                        <option value="Reemplazo por enfermedad general">Reemplazo por enfermedad general</option>
                        <option value="Ajustes VRA">Ajustes VRA</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="button-container">
                    <button type="submit">Agregar</button>
                    <button type="button" class="regresar-button" onclick="regresar()">Regresar</button>
                </div>
            </form>
        </div>
    </div>
    
    <form id="redirectForm" action="depto_comparativo.php" method="POST" style="display: none;">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($_GET['departamento_id']); ?>">
        <input type="hidden" id="anio_semestre" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
        <input type="hidden" id="anio_semestre_anterior" name="anio_semestre_anterior" value="<?php echo htmlspecialchars($_GET['anio_semestre_anterior']); ?>">
    </form>

  </div>
    </div>
    
    
<script>
function detectarTipoReemplazo() {
    const observacion = document.getElementById('observacion').value.toLowerCase();
    const selectTipo = document.getElementById('tipo_reemplazo');
    
    // Palabras clave para cada tipo
    const keywords = {
    'NN': 'Reemplazos NN',
    // Ahora apuntan a 'Reemplazo por Jubilación'
    'jubilación': 'Reemplazo por Jubilación',
    'jubilado': 'Reemplazo por Jubilación',
    'jubiló': 'Reemplazo por Jubilación',
    'jubilada': 'Reemplazo por Jubilación',
    // 'licencia', 'maternidad', 'paternidad' apuntan a 'Reemplazo por Licencia'
    'licencia': 'Reemplazo por Licencia',
    'maternidad': 'Reemplazo por Licencia',
    'paternidad': 'Reemplazo por Licencia',
    // 'enfermedad' sigue apuntando a la opción más específica
    'enfermedad': 'Reemplazo por enfermedad general', 
    'fallecimiento': 'Reemplazo por Fallecimiento',
    'falleció': 'Reemplazo por Fallecimiento',
    'murió': 'Reemplazo por Fallecimiento',
    // Ahora apuntan a 'Reemplazo por Renuncia'
    'renuncia': 'Reemplazo por Renuncia',
    'renunció': 'Reemplazo por Renuncia',
    // Ahora apuntan a 'Reemplazo por Necesidad Docente'
    'necesidad docente': 'Reemplazo por Necesidad Docente',
    'requerimiento docente': 'Reemplazo por Necesidad Docente',
    'falta de docente': 'Reemplazo por Necesidad Docente',
    // Eliminadas porque no están en el select
    // 'no legalizó': 'No legalizó',
    // 'no legalizo': 'No legalizó',
    // 'legalizar': 'No legalizó',
    'financiación': 'Otras fuentes de financiacion',
    'financiamiento': 'Otras fuentes de financiacion',
    'matrículas': 'Ajuste de Matrículas',
    'ajuste': 'Ajuste de Matrículas',
    'matriculas': 'Ajuste de Matrículas',
    'reemplazo': 'Reemplazo',
    'sustitución': 'Reemplazo',
    'sustitucion': 'Reemplazo',
    // Apunta a 'Ajustes VRA'
    '4-31/': 'Ajustes VRA', 
    // Eliminada porque no está en el select
    // 'Cambio vinculacion': 'Cambio vinculacion',
    // Mantenemos 'Necesidad docente' como un valor directo si no lleva "Reemplazo por"
    'Necesidad docente': 'Necesidad docente'
};
    
    // Buscar coincidencias
    for (const [keyword, value] of Object.entries(keywords)) {
        if (observacion.includes(keyword)) {
            selectTipo.value = value;
            return;
        }
    }
    
    // Si no encuentra coincidencia exacta, verificar si menciona "reemplazo" genérico
    if (observacion.includes('reemplazo') || observacion.includes('reemplazo')) {
        selectTipo.value = 'Reemplazo';
    } else if (selectTipo.value === '') {
        selectTipo.value = 'Otro';
    }
}
</script>
    
            </body>
</html>
