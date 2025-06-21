<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Registro</title>
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
    <h1>Agregar Nuevo Registro</h1>
    <?php var_dump($_GET['anio_semestre']);
 ?>
    <form action="procesar_nuevo_registro.php" method="POST" onsubmit="return validarFormulario()">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($_GET['facultad_id']); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($_GET['departamento_id']); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($_GET['tipo_docente']); ?>">

        <label for="cedula">Cédula</label>
        <input type="text" name="cedula" onblur="validarCedulaUnica(this); buscarTercero(this);" required>

        <label for="nombre">Nombre</label>
        <input type="text" name="nombre"   id="nombre" readonly required>
        
        <?php if ($_GET['tipo_docente'] == "Ocasional") { ?>
            <label for="tipo_dedicacion">Dedicación Popayán</label>
            <select name="tipo_dedicacion" onchange="limpiarTipoDedicacionR()">
                <option value=""></option>
                <option value="TC">TC</option>
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

        <div class="button-container">
            <button type="submit">Agregar</button>
            <button type="button" class="regresar-button" onclick="regresar()">Regresar</button>
        </div>
    </form>

    <!-- Formulario oculto para el botón de regresar -->
    <form id="redirectForm" action="consulta_todo_depto.php" method="POST" style="display: none;">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($_GET['departamento_id']); ?>">
        <input type="hidden"  id="anio_semestre" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
    </form>
</body>
</html>
