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
            margin: 20px auto;
            padding: 20px;
            max-width: 80%; /* Establece el ancho máximo de la página */
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px; /* Agrega un margen inferior al encabezado */
        }
        .header h1 {
            flex: 1;
            text-align: center;
        }
        .header h2, .header h3 {
            flex: 1;
            text-align: left;
            margin: 5px 0;
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
        .button-container {
            display: flex;
            justify-content: space-between;
        }
        .regresar-button {
            background-color: #cccccc;
            color: #333333;
        }
        .regresar-button:hover {
            background-color: #999999;
        }
     
    input[type="text"],
    select,
    textarea {
        width: 550px; /* o 100% si tus campos usan % */
        padding: 6px;
        font-size: 1rem;
        box-sizing: border-box;
    }

    textarea {
        resize: vertical;
        height: 80px;
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
        
    <h1>Agregar Nuevo Registro</h1>
   
    <form action="procesar_nuevo_registro.php" method="POST" onsubmit="return validarFormulario()">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($_GET['facultad_id']); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($_GET['departamento_id']); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
        <input type="hidden" name="anio_semestre_anterior" value="<?php echo htmlspecialchars($_GET['anio_semestre_anterior']); ?>">

        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($_GET['tipo_docente']); ?>">
        <input type="hidden" name="nombre_usuario" value="<?php echo htmlspecialchars($_SESSION['name']); ?>">

        <label for="cedula">Cédula</label>
        <input type="text" name="cedula" onblur="validarCedulaUnica(this); buscarTercero(this);" required>

        <label for="nombre">Nombre</label>
        <input type="text" name="nombre"   id="nombre" readonly required>
        
        <?php if ($_GET['tipo_docente'] == "Ocasional") { ?>
            <label for="tipo_dedicacion">Dedicación Popayán</label>
            <select name="tipo_dedicacion" onchange="limpiarTipoDedicacionR()">
                <option value=""></option>
                <option value="TC" selected>TC</option>
                <option value="MT">MT</option>
            </select>
            <label for="tipo_dedicacion_r">Dedicación Regionalización</label>
            <select name="tipo_dedicacion_r" onchange="limpiarTipoDedicacion()">
                <option value=""></option>
                <option value="TC">TC</option>
                <option value="MT">MT</option>
            </select>
        <?php } ?>
        
        <?php if ($_GET['tipo_docente'] == "Catedra") { ?>
            <label for="horas">Horas Popayán</label>
            <input type="text" name="horas">
            <label for="horas_r">Horas Regionalización</label>
            <input type="text" name="horas_r">
        <?php } ?>

        <label for="anexa_hv_docente_nuevo">Anexa HV Nuevos</label>
        <select name="anexa_hv_docente_nuevo" required>
            <option value="No">No</option>
            <option value="Si">Si</option>
        </select>

        <label for="actualiza_hv_antiguo">Actualiza HV Antiguos</label>
        <select name="actualiza_hv_antiguo" required>
            <option value="No">No</option>
            <option value="Si">Si</option>
        </select>
            <label for="observacion">Observación</label>
           <textarea name="observacion" id="observacion" rows="3" style="margin-bottom: 15px;" 
              placeholder="evidencia de que se solicitó el cambio al responsable, pero no fue atendido...Ej: Oficio 5.5./31 no se acepta cambio a ocasional, se mantiene como cátedra.."
              oninput="detectarTipoReemplazo()"></textarea>

    <!-- Nuevo campo de pestaña -->
    <label for="tipo_reemplazo">Tipo de Reemplazo/Justificación</label>
    <select name="tipo_reemplazo" id="tipo_reemplazo" required>
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

        <option value="Otro">Otro</option>
    </select>

        <div class="button-container">
            <button type="submit">Agregar</button>
            <button type="button" class="regresar-button" onclick="regresar()">Regresar</button>
        </div>
    </form>

    <!-- Formulario oculto para el botón de regresar -->
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
