<?php
include('conn.php');

require('include/headerz.php');
    // Consulta para obtener el ID del usuario basado en su nombre
            $consulta_usuario = "SELECT Id FROM users WHERE Name = ?";
            if ($stmt = $conn->prepare($consulta_usuario)) {
                // Pasar el nombre del usuario
                $stmt->bind_param("s", $nombre_sesion);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($row = $resultado->fetch_assoc()) {
                    $usuario_id = $row['Id']; // Obtener el usuario_id desde la base de datos
                } else {
                    throw new Exception("No se encontró el usuario en la base de datos.");
                }
                
            }

?>
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
      /*  function buscarTercero(input) {
            var numDocumento = input.value;
            var nombreTerceroInput = input.parentElement.parentElement.querySelector('input[name="nombre"]');

            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'buscar_tercero.php?num_documento=' + numDocumento, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var nombreTercero = xhr.responseText.trim();
                    nombreTerceroInput.value = nombreTercero;
                    validarNombreTercero(nombreTerceroInput);
                }
            };
            xhr.send();
        }*//*<--esta es la que fucionaba antes del problema de adcionar novedad   */
            function buscarTercero(input) {
            var numDocumento = input.value;
            var nombreTerceroInput = input.parentElement.parentElement.querySelector('input[name="nombre"]');
            var anioSemestreInput = document.querySelector('input[name="anio_semestre"]');
            var anioSemestre = anioSemestreInput.value;
            var url = 'buscar_tercero.php?num_documento=' + numDocumento;

            if (anioSemestre.trim() !== "") {
                url += '&anio_semestre=' + anioSemestre;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var nombreTercero = xhr.responseText.trim();
                    nombreTerceroInput.value = nombreTercero;
                    validarNombreTercero(nombreTerceroInput);
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
    <h1>Novedad-  Nuevo Registro</h1>
    <form action="procesar_nuevo_registro_novedad.php" method="POST" onsubmit="return validarFormulario()">
        <input type="hidden" name="facultad_id" value="<?php echo htmlspecialchars($_GET['facultad_id']); ?>">
        <input type="hidden" name="departamento_id" value="<?php echo htmlspecialchars($_GET['departamento_id']); ?>">
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
        <input type="hidden" name="tipo_docente" value="<?php echo htmlspecialchars($_GET['tipo_docente']); ?>">
        <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($_GET['tipo_usuario']); ?>">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($usuario_id); ?>">

        <label for="cedula">Cédula</label>
        <input type="text" name="cedula" onblur="validarCedulaUnica(this); buscarTercero(this);" required>

        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" readonly required>
        
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
        <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($_GET['anio_semestre']); ?>">
    </form>
</body>
</html>
