<?php
require('include/headerz.php');
$tipo_docente = isset($_GET['tipo_docente']) ? $_GET['tipo_docente'] : '';
$anio_semestre = isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : '';


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
        <title>Solicitudes de Vinculación Temporales</title>

         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>

        <style>
            /* Puedes ajustar estos valores según el estilo que ya manejes */
.alto-field {
    height: 38px; /* Altura estándar de campos de formulario */
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.alto-field:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.alto-field[readonly] {
    background-color: #e9ecef;
    opacity: 1;
}
     .readonly-input {
        background-color: #f0f0f0;
        color: #666;
        border: none; /* Quita el borde */
        cursor: not-allowed; /* Cambia el cursor para indicar que no es interactuable */
        padding: 8px; /* Agrega un poco de padding para que se vea mejor */
    }
            .custom-button {
        background-color: darkred; /* Color de fondo */
        color: white; /* Color del texto */
        padding: 10px 20px; /* Padding interior del botón */
        border: none; /* Sin borde */
        border-radius: 4px; /* Borde redondeado */
        cursor: pointer; /* Cursor tipo puntero */
        font-size: 16px; /* Tamaño de la fuente */
        transition: background-color 0.3s ease; /* Transición suave para el color de fondo */
        margin-bottom: 15px; /* Espaciado inferior */
    }

    .custom-button:hover {
        background-color: #0056b3; /* Color de fondo al pasar el mouse */
    }
                    .custom-buttonv {
        background-color:darkgreen; /* Color de fondo */
        color: white; /* Color del texto */
        padding: 10px 20px; /* Padding interior del botón */
        border: none; /* Sin borde */
        border-radius: 4px; /* Borde redondeado */
        cursor: pointer; /* Cursor tipo puntero */
        font-size: 16px; /* Tamaño de la fuente */
        transition: background-color 0.3s ease; /* Transición suave para el color de fondo */
        margin-bottom: 15px; /* Espaciado inferior */
    }

    .custom-buttonv:hover {
        background-color: palegreen; /* Color de fondo al pasar el mouse */
    }
            .row {
                display: inline-block;
                margin-right: 20px;
                vertical-align: top;
            }
            th {
                font-size: 12px; /* Ajustar tamaño de las cabeceras */
                font-weight: normal;
            }
            th:nth-child(3), td:nth-child(3) {
                width: 400px; /* Ajusta este valor según sea necesario */
            }
            td:nth-child(1) {
                font-size: 10px; /* Ajusta este valor según sea necesario */
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 10px;
            }
            form {
                max-width: 1000px;
                margin: auto;
                padding: 20px;
                border: 1px solid #ccc;
                border-radius: 5px;
            }
            label {
                display: block;
                margin-bottom: 10px;
            }
            select, input[type="text"], input[type="number"] {
                width: 100%;
                padding: 8px;
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .button-group {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
            }
            .button-group button {
                padding: 10px 15px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background-color: #f1f1f1;
                cursor: pointer;
            }
            .button-group button.selected {
                background-color: #004080;
                color: white;
                border-color: #004080;
            }
            button[type="submit"] {
                background-color: #004080;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            button[type="submit"]:hover {
                background-color: #004080;
            }
            .eliminar-fila {
    background: none;
    border: none;
    color: red;
    font-size: 2.0em;
    cursor: pointer;         /* Ajusta el tamaño del icono */
top: -5px; left: 10px;
    padding: 0;position: relative;

}

.eliminar-fila:hover {
    color: darkred;
}
            .add-container {
    display: inline-flex;
    align-items: center;
    margin-left: 10px; /* Espacio entre el input y el contenedor */
}

.toggle-icon {
    color: green;
    font-size: 1.2em;
    cursor: pointer;
    margin-right: 5px; /* Espacio entre el icono y el texto */
}

.toggle-icon:hover {
    color: darkgreen;
}
 /* Estilo para el campo de entrada de números */
input[type="number"] {
    height: 30px; /* Ajusta el alto del input */
    font-size: 20px; /* Aumenta el tamaño del texto */
    padding-right: 15px; /* Asegura espacio para los botones */
    position: relative; /* Añade posición relativa para el posicionamiento absoluto de los botones */
}

/* Estilos para navegadores WebKit (Chrome, Safari, Edge) */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    width:44px; /* Ajusta el ancho de los botones */
    height: 100%; /* Ajusta el alto de los botones para que cubran toda la altura del input */
    position: absolute; /* Posición absoluta para los botones */
    top: 0; /* Alinea los botones arriba */
    right: 0; /* Alinea los botones a la derecha */
    padding: 0; /* Elimina cualquier padding para asegurar que los botones estén ajustados */
}

/* Estilos para Firefox */
input[type="number"] {
    -moz-appearance: textfield; /* Asegura que Firefox use un estilo de campo de texto estándar */
}

input[type="number"]::-moz-number-spin-box,
input[type="number"]::-moz-number-spin-up,
input[type="number"]::-moz-number-spin-down {
    font-size: 20px; /* Ajusta el tamaño de la fuente de los botones en Firefox */
    position: absolute; /* Posición absoluta para los botones en Firefox */
    top: 0; /* Alinea los botones arriba */
    right: 0; /* Alinea los botones a la derecha */
}
              .readonly-field {
        background-color: #f0f0f0; /* Color gris claro de fondo */
        border: 1px solid #dcdcdc; /* Color gris para el borde */
        color: #666666; /* Color gris para el texto */
        cursor: not-allowed; /* Cambia el cursor para indicar que es solo lectura */
                          height: 37px; /* Ajusta la altura del campo */

    }
             .alto-field {

                          height: 37px; /* Ajusta la altura del campo */

    }

            @keyframes inflateButton {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.15);
    }
    100% {
        transform: scale(1);
    }
}
.custom-button {
    background: linear-gradient(to right, #ff9800, #ff5722);
    color: white;
    font-weight: bold;
    font-size: 1rem; /* Ajusta el tamaño del texto */
    padding: 22px 16px; /* Ajusta el padding para igualar la altura */
    border: none;
    border-radius: 5px; /* Ajusta los bordes si es necesario */
    cursor: pointer;
    box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease-in-out;
    animation: inflateButton 1s ease-in-out;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px; /* Ajusta manualmente la altura */
}

.custom-button:hover {
    background: linear-gradient(to right, #e55300, #d84315);
    transform: scale(1.05);
    box-shadow: 3px 3px 15px rgba(0, 0, 0, 0.3);
}

/* New styles for the link container */

.link-anexos-container input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}
/* Add this to your <style> block */

/* Ensure consistent vertical alignment for table cell content */
td {
    vertical-align: top; /* Align content to the top within the cell */
    padding-top: 0; /* Remove default padding from the top if any */
    padding-bottom: 0; /* Remove default padding from the bottom if any */
}

td > * {
    display: flex; /* Use flexbox for direct children of table cells */
    align-items: center; /* Vertically align items in the middle */
    height: 100%; /* Make child elements take full height of the cell */
    margin: 0; /* Remove any default margins */
}

.link-anexos-container {
    display: none; /* Hidden by default - JavaScript will change this to 'flex' when needed */
    margin-top: 0; /* Removed the problematic margin-top */
    height: 100%;
    align-items: center; /* This applies when display is set to flex by JS */
}

/* Adjust input height if needed, ensuring it's consistent */
.alto-field {
    height: 38px; /* Standard height for inputs */
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
    line-height: 1.5; /* Important for vertical alignment of text within input */
    color: #495057;
    background-color: #fff;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
}

/* Ensure select elements also adhere to the same height */
td select {
    height: 38px; /* Match the height of .alto-field */
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
    line-height: 1.5;
    box-sizing: border-box;
}

/* For the delete button to align better */
.eliminar-fila {
    height: 38px; /* Match other field heights */
    display: flex;
    align-items: center;
    justify-content: center;
}

        </style>

        <script>

</script>
          <script>
        function validarFormulario(event) {
            // Obtener valores de los campos ocultos
          //  var tipoUsuario = document.getElementById('tipo_usuario').value;
                 var tipoUsuario = '<?php echo $tipo_usuario; ?>';

            var emailUser = '<?php echo $email_user; ?>';
            var emailDepto = '<?php echo $email_depto; ?>';

            // Verificar las condiciones
            if (tipoUsuario == 3 && emailUser != emailDepto) {
                alert('No tienes permiso para guardar en este departamento.');
                event.preventDefault(); // Prevenir el envío del formulario
                return false;
            }

            // Si las condiciones se cumplen, permitir el envío del formulario
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Asignar el evento de clic al botón de guardar
            document.getElementById('btnGuardar').addEventListener('click', validarFormulario);
        });
    </script>
    </head>
    <body>

    <div class="container">



    <?php

require 'cn.php';
$nombre_sesion= $_SESSION['name'];
//echo $nombre_sesion;
$consultaf = "SELECT * FROM users where users.Name= '$nombre_sesion'";
$resultadof = $con->query($consultaf);
while ($row = $resultadof -> fetch_assoc()){
    $nombre_usuario= $row['Name'];
    $tipo_usuario = $row['tipo_usuario'];
    $email_fac= $row['email_padre'];
    $email_user= $row['Email'];

$depto_user= $row['fk_depto_user'];
    $email_depto = obtenerEmailDepartamentoUser($email_user);
//echo $email_fac;
    $where = "";
if ($tipo_usuario!= 1) {
    $where = "WHERE email_fac LIKE '%$email_fac%'";
}
}
   // echo "el email depot  de la funcion: ".$email_depto."el emial  de usuauirio seseion es: ".$email_user."y el tipo de ".$tipo_usuario;
      // Función para obtener el ID del departamento
    function obtenerEmailDepartamentoUser($email_user) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT email_depto FROM deparmanentos WHERE email_depto = '$email_user'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['email_depto'];
        } else {
            return "Departamento Desconocido";
        }
    }
?>
        <span style="display: inline-block; padding: 4px 8px; border: 1px solid #ccc; background-color: #f8f9fa; border-radius: 3px;">
    <button id="btnReturn" class="btn btn-light" onclick="redirectToConsulta()" style="text-decoration: none; color: inherit; padding: 2px 5px; border: none; background: none;">
        Regresar <i class="fas fa-arrow-left"></i>
    </button>
</span>


        <form action="guardar.php" method="POST">

            <div id="facultad-group" class="button-group">
                <?php
                // Conectar a la base de datos
                $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
                if ($conn->connect_error) {
                    die("Conexión fallida: " . $conn->connect_error);
                }
                // Obtener facultades
                $result = $conn->query("SELECT PK_FAC, nombre_fac_minb FROM facultad $where");
                while ($row = $result->fetch_assoc()) {
                    echo '<button type="button" class="facultad-btn" id="selected-facultad"  data-value="' . $row['PK_FAC'] . '">' . $row['nombre_fac_minb'] . '</button>';
                }
                $conn->close();
                ?>
            </div>
                <script>
    // Simular clic en el botón de la facultad al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        const selectedButton = document.getElementById('selected-facultad');
        if (selectedButton) {
            selectedButton.click(); // Simular clic
        }
    });
</script>
          <input type="hidden" id="depto_user" name="depto_user" value ="<?php echo $depto_user;?>" required>
                      <input type="hidden" id="nombre_usuario" name="nombre_usuario" value ="<?php echo $nombre_usuario;?>" required>

 <input type="hidden" id="tipo_usuario" name="tipo_usuario" value ="<?php echo $tipo_usuario;?>" required>
             <input type="hidden" id="email_user" name="email_user" value ="<?php echo $email_user;?>" required>
             <input type="hidden" id="email_depto" name="email_depto" value ="<?php echo $email_depto;?>" required>

            <input type="hidden" id="facultad" name="facultad" required><br>

            <label for="departamento">Departamento:</label>
            <div id="departamento-group" class="button-group">
                </div>
            <input type="hidden" id="departamento" name="departamento" required><br>

             <div class="row">
    <label for="anio_semestre">Periodo:</label>
    <select id="anio_semestre" name="anio_semestre" required
            onfocus="this.blur()" style="pointer-events: none; background-color: #f5f5f5;">
        </select>
</div>

       <div class="row">
    <label for="tipo_docente">Tipo de Profesor:</label>
    <select id="tipo_docente" name="tipo_docente" required
            onfocus="this.blur()" style="pointer-events: none; background-color: #f5f5f5;">
        <option value="Ocasional" <?php echo ($tipo_docente == 'Ocasional') ? 'selected' : ''; ?>>Ocasional</option>
        <option value="Catedra" <?php echo ($tipo_docente == 'Catedra') ? 'selected' : ''; ?>>Cátedra</option>
    </select>
</div>
            <div class="row">
    <label for="num_docentes">Cantidad de Profesores a Ingresar:</label>
    <input type="number" id="num_docentes" name="num_docentes" min="1" readonly class="readonly-input">

</div><br>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <button type="button" class="custom-button" onclick="cargarDatosPeriodoAnterior()">Importar del periodo anterior</button>


<button type="button"  class="custom-buttonv" id="insertar_registro"> <i class="fas fa-plus" style="margin-right: 5px;"></i>Añadir Aspirante</button>

            <div id="docentes">
                </div>

            <button type="submit" id="btnGuardar">Cargar profesores</button>
        </form>




        </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>


    document.getElementById('btnGuardar').addEventListener('click', function(event) {
    var filas = document.querySelectorAll('#docentes tbody tr');
    var mensajeError = '';

    for (var i = 0; i < filas.length; i++) {
        var dedicacionOcasional = filas[i].querySelector(`[id^="tipo_dedicacion_"]`).value.trim();
        var horasOcasional = filas[i].querySelector(`[id^="horas_"]`).value.trim();
        var dedicacionCatedra = filas[i].querySelector(`[id^="tipo_dedicacion_r_"]`).value.trim();
        var horasCatedra = filas[i].querySelector(`[id^="horas_r_"]`).value.trim();

        if ((dedicacionOcasional === '' && horasOcasional === '') && (dedicacionCatedra === '' && horasCatedra === '')) {
            mensajeError += `Debe incluir dedicación en la fila ${i + 1}.\n`;
        }
    }

    if (mensajeError !== '') {
        alert(mensajeError);
        event.preventDefault(); // Evita el envío del formulario
    }
});
function redirectToConsulta() {
    const numDocentes = parseInt(document.getElementById('num_docentes').value, 10);

    if (numDocentes > 0) {
        const confirmacion = confirm("Aún no han guardado los cambios. ¿Desea continuar?\n\nUse 'Cargar Profesores' para guardar.");
        if (!confirmacion) {
            return; // El usuario canceló
        }
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'consulta_todo_depto.php';

    // Campos ocultos dinámicos
    var departamentoId = document.createElement('input');
    departamentoId.type = 'hidden';
    departamentoId.name = 'departamento_id';
    departamentoId.value = '<?php echo htmlspecialchars($depto_user); ?>';

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
</script>
        <script>

             function validarHoras(i) {
                    var horas = parseFloat(document.getElementById(`horas_${i}`).value) || 0;
                    var horas_r = parseFloat(document.getElementById(`horas_r_${i}`).value) || 0;

                    if (horas + horas_r > 12) {
                        alert("La suma de Horas Pop. y Horas Reg. no puede superar 12.");

                        // Restablecer el campo modificado
                        if (event.target.id === `horas_${i}`) {
                            document.getElementById(`horas_${i}`).value = 0;
                        } else {
                            document.getElementById(`horas_r_${i}`).value = 0;
                        }
                    }
                }


            function validarAntesDeGuardar() {
                var filas = document.querySelectorAll("#docentes tbody tr");
                for (let i = 0; i < filas.length; i++) {
                    // Usamos parseFloat en lugar de parseInt para manejar decimales
                    var horas = parseFloat(document.getElementById(`horas_${i}`).value) || 0;
                    var horas_r = parseFloat(document.getElementById(`horas_r_${i}`).value) || 0;

                    // Redondeamos a 1 decimal para evitar problemas de precisión con números flotantes
                    var suma = Math.round((horas + horas_r) * 10) / 10;

                    if (suma > 12) {
                        alert(`La suma de horas en la fila ${i + 1} (${suma}) excede el máximo permitido (12).`);
                        return false;
                    }
                }
                return true; // Permite guardar si todo está correcto
            }


document.getElementById('insertar_registro').addEventListener('click', function() {
    var numDocentesInput = document.getElementById('num_docentes');
    var numDocentes = parseInt(numDocentesInput.value) || 0;

    // Incrementar el número de docentes
    numDocentes++;
    numDocentesInput.value = numDocentes;

    var container = document.getElementById('docentes');
    var table = container.querySelector('table');

    // Crear la tabla si no existe
    if (!table) {
        table = document.createElement('table');
        var thead = document.createElement('thead');
        var tbody = document.createElement('tbody');

        // Encabezado de la tabla
        thead.innerHTML = `
            <tr>
                <th>#</th>
                <th>Cédula</th>
                <th>Nombre</th>
               <th class="tipo_dedicacion" title="Dedicación en Sede Popayán">Dedicación Pop</th>
<th class="tipo_dedicacion_r" title="Dedicación en Sede Regionalización">Dedicación Reg</th>
                <th class="horas">Horas Pop.</th>
                <th class="horas_r">Horas Reg.</th>
                <th>Anexa HV Nuevo</th>
                <th>Actualiza HV Antiguo</th>
                <th class="link-anexos-header">Link Anexos</th>
                <th>Eliminar</th>
            </tr>
        `;
        table.appendChild(thead);
        table.appendChild(tbody);
        container.appendChild(table);
    } else {
        var tbody = table.querySelector('tbody');
    }

   // Crear una nueva fila
var tr = document.createElement('tr');
tr.innerHTML = `
    <td>${numDocentes}</td>
    <td><input type="text" id="cedula_${numDocentes - 1}" name="cedula[]" required onchange="buscarTercero(this); validarCedulaUnica(this)" class="alto-field"></td>
    <td><input type="text" id="nombre_${numDocentes - 1}" name="nombre[]" readonly class="readonly-field"></td>
    <td class="tipo_dedicacion">
        <select id="tipo_dedicacion_${numDocentes - 1}" name="tipo_dedicacion[]" onchange="limpiarDedicacionReg(${numDocentes - 1})">
            <option value=""></option>
            <option value="TC">TC</option>
            <option value="MT">MT</option>
        </select>
    </td>
    <td class="tipo_dedicacion_r">
        <select id="tipo_dedicacion_r_${numDocentes - 1}" name="tipo_dedicacion_r[]" onchange="limpiarDedicacionPop(${numDocentes - 1})">
            <option value=""></option>
            <option value="TC">TC</option>
            <option value="MT">MT</option>
        </select>
    </td>
    <td class="horas">
        <input id="horas_${numDocentes - 1}" name="horas[]" type="number" min="0" max="12" step="0.1" onchange="validarHoras(${numDocentes - 1})">
    </td>
    <td class="horas_r">
        <input id="horas_r_${numDocentes - 1}" name="horas_r[]" type="number" min="0" max="12" step="0.1" onchange="validarHoras(${numDocentes - 1})">
    </td>
    <td>
        <select id="anexa_hv_docente_nuevo_${numDocentes - 1}" name="anexa_hv_docente_nuevo[]" required onchange="toggleLinkAnexos(${numDocentes - 1})">
            <option value="si">Sí</option>
            <option value="no" selected>No</option>
            <option value="no aplica">No Aplica</option>
        </select>
    </td>
    <td>
        <select id="actualiza_hv_antiguo_${numDocentes - 1}" name="actualiza_hv_antiguo[]" required onchange="toggleLinkAnexos(${numDocentes - 1})">
            <option value="si">Sí</option>
            <option value="no" selected>No</option>
            <option value="no aplica">No Aplica</option>
        </select>
    </td>
    <td class="link-anexos-cell">
        <div id="link_anexos_container_${numDocentes - 1}" class="link-anexos-container">
            <input type="url" id="link_anexos_${numDocentes - 1}" name="link_anexos[]" placeholder="Opcional Link Drive/Nube" class="alto-field">
        </div>
    </td>
    <td>
        <button type="button" class="eliminar-fila" onclick="eliminarFila(this)">&#128465;</button>
    </td>
`;
    tbody.appendChild(tr);
tr.scrollIntoView({ behavior: "smooth", block: "end" });

    actualizarCamposDocente();
        actualizarVisibilidadEncabezadoLink(); // Añadir al final

});

            function cargarDatosPeriodoAnterior() {
        var periodoSeleccionado = document.getElementById('anio_semestre').value;
        var facultadId = document.getElementById('facultad').value;
        var departamentoId = document.getElementById('departamento').value;
        var depto_user = document.getElementById('depto_user').value;
        var tipo_usuario = document.getElementById('tipo_usuario').value;
        var nombre_usuario = document.getElementById('nombre_usuario').value;

            var tipo_docente = document.getElementById('tipo_docente').value;

        if (!periodoSeleccionado || !facultadId || !departamentoId) {
            alert('Por favor seleccione un periodo, facultad y departamento.');
            return;
        }
                  else if (departamentoId !== depto_user && tipo_usuario == 3) {
                alert('Por favor seleccione su departamento. Usuario actual: ' + nombre_usuario  + depto_user+departamentoId);
            return;
       }

        var anio = parseInt(periodoSeleccionado.split('-')[0]);
        var semestre = parseInt(periodoSeleccionado.split('-')[1]);
        var periodoAnterior;

        if (semestre === 1) {
            periodoAnterior = (anio - 1) + '-2';
        } else {
            periodoAnterior = anio + '-1';
        }

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'obtener_datos_periodo_anterior.php?facultad_id=' + facultadId + '&departamento_id=' + departamentoId + '&anio_semestre=' + periodoAnterior+ '&tipo_docente=' + tipo_docente, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var datos = JSON.parse(xhr.responseText);
                generarCamposDocenteConDatos(datos);
            } else {
                alert('No se pudieron obtener los datos del periodo anterior.');
            }
        };
        xhr.send();
    }



    function generarCamposDocenteConDatos(datos) {
    var container = document.getElementById('docentes');
    container.innerHTML = '';

    // Crear la tabla
    var table = document.createElement('table');
    var thead = document.createElement('thead');
    var tbody = document.createElement('tbody');

    // Encabezado de la tabla
    thead.innerHTML = `
        <tr>
            <th>#</th>
            <th>Cédula</th>
            <th>Nombre</th>
          <th class="tipo_dedicacion" title="Dedicación en Sede Popayán">Dedicación Pop</th>
<th class="tipo_dedicacion_r" title="Dedicación en Sede Regionalización">Dedicación Reg</th>

          <th class="horas" title="Horas en Sede Popayán">Horas Pop.</th>
<th class="horas_r" title="Horas en Sede Regionalización">Horas Reg.</th>
<th title="Anexa Hoja de Vida para nuevos aspirantes">Anexa HV Nuevo</th>
<th title="Actualiza Hoja de Vida para aspirantes antiguos">Actualiza HV Antiguo</th>
            <th class="link-anexos-header">Link Anexos</th>
            <th>Eliminar</th>
        </tr>
    `;
    table.appendChild(thead);
    table.appendChild(tbody);

   datos.forEach((dato, i) => {
    var tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${i + 1}</td>
        <td><input type="text" id="cedula_${i}" name="cedula[]" value="${dato.cedula}" required onchange="buscarTercero(this)" class="alto-field"></td>
        <td><input type="text" id="nombre_${i}" name="nombre[]" value="${dato.nombre}" readonly class="readonly-field"></td>
        <td class="tipo_dedicacion">
            <select id="tipo_dedicacion_${i}" name="tipo_dedicacion[]" onchange="limpiarDedicacionReg(${i})">
                <option value=""></option>
                <option value="TC" ${dato.tipo_dedicacion === 'TC' ? 'selected' : ''}>TC</option>
                <option value="MT" ${dato.tipo_dedicacion === 'MT' ? 'selected' : ''}>MT</option>
            </select>
        </td>
        <td class="tipo_dedicacion_r">
            <select id="tipo_dedicacion_r_${i}" name="tipo_dedicacion_r[]" onchange="limpiarDedicacionPop(${i})">
                <option value=""></option>
                <option value="TC" ${dato.tipo_dedicacion_r === 'TC' ? 'selected' : ''}>TC</option>
                <option value="MT" ${dato.tipo_dedicacion_r === 'MT' ? 'selected' : ''}>MT</option>
            </select>
        </td>
        <td class="horas">
            <input id="horas_${i}" name="horas[]" type="number" value="${dato.horas}" min="0" max="12" step="0.1" onchange="validarHoras(${i})">
        </td>
        <td class="horas_r">
            <input id="horas_r_${i}" name="horas_r[]" type="number" value="${dato.horas_r}" min="0" max="12" step="0.1" onchange="validarHoras(${i})">
        </td>
        <td>
            <select id="anexa_hv_docente_nuevo_${i}" name="anexa_hv_docente_nuevo[]" required onchange="toggleLinkAnexos(${i})">
                <option value="si" ${dato.anexa_hv_docente_nuevo === 'si' ? 'selected' : ''}>Sí</option>
                <option value="no" ${dato.anexa_hv_docente_nuevo === 'no' ? 'selected' : ''}>No</option>
                <option value="no aplica" ${dato.anexa_hv_docente_nuevo === 'no aplica' ? 'selected' : ''}>No Aplica</option>
            </select>
        </td>
        <td>
            <select id="actualiza_hv_antiguo_${i}" name="actualiza_hv_antiguo[]" required onchange="toggleLinkAnexos(${i})">
                <option value="si" ${dato.actualiza_hv_antiguo === 'si' ? 'selected' : ''}>Sí</option>
                <option value="no" ${dato.actualiza_hv_antiguo === 'no' ? 'selected' : ''}>No</option>
                <option value="no aplica" ${dato.actualiza_hv_antiguo === 'no aplica' ? 'selected' : ''}>No Aplica</option>
            </select>
        </td>
        <td class="link-anexos-cell">
            <div id="link_anexos_container_${i}" class="link-anexos-container">
                <input type="url" id="link_anexos_${i}" name="link_anexos[]" value="${dato.link_anexos || ''}" placeholder="Opcional Link Drive/Nube" class="alto-field">
            </div>
        </td>
        <td>
            <button type="button" class="eliminar-fila" onclick="eliminarFila(this)">&#128465;</button>
        </td>
    `;
    tbody.appendChild(tr);
    // Call toggleLinkAnexos for each row after it's added to set initial visibility
    toggleLinkAnexos(i);
});
    container.appendChild(table);
    actualizarCamposDocente();
    document.getElementById('num_docentes').value = datos.length;               actualizarVisibilidadEncabezadoLink(); // Añadir esto

}

    // Función para eliminar la fila
   function eliminarFila(button) {
    if (confirm("⚠️ Se eliminará el registro. ¿Desea continuar?")) {
        var row = button.parentNode.parentNode;
        row.parentNode.removeChild(row);

        // Actualizar el número de filas restantes
        actualizarNumerosFila();
    } else {
        console.log("🚫 Eliminación cancelada.");
    }
       
               actualizarVisibilidadEncabezadoLink(); // Añadir esto

}   function actualizarNumerosFila() {
        var table = document.querySelector('#docentes table tbody');
        var rows = table.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelector('td:first-child').innerText = index + 1;
             // Update IDs and names based on new row index
            const oldIndex = parseInt(row.querySelector('input[name="cedula[]"]').id.split('_')[1]);
            if (oldIndex !== index) {
                row.querySelector(`[id^="cedula_"]`).id = `cedula_${index}`;
                row.querySelector(`[id^="nombre_"]`).id = `nombre_${index}`;
                row.querySelector(`[id^="tipo_dedicacion_"]`).id = `tipo_dedicacion_${index}`;
                row.querySelector(`[id^="tipo_dedicacion_r_"]`).id = `tipo_dedicacion_r_${index}`;
                row.querySelector(`[id^="horas_"]`).id = `horas_${index}`;
                row.querySelector(`[id^="horas_r_"]`).id = `horas_r_${index}`;
                row.querySelector(`[id^="anexa_hv_docente_nuevo_"]`).id = `anexa_hv_docente_nuevo_${index}`;
                row.querySelector(`[id^="actualiza_hv_antiguo_"]`).id = `actualiza_hv_antiguo_${index}`;
                row.querySelector(`[id^="link_anexos_container_"]`).id = `link_anexos_container_${index}`;
                row.querySelector(`[id^="link_anexos_"]`).id = `link_anexos_${index}`;

                // Update onchange attributes with new index
                row.querySelector(`[id^="tipo_dedicacion_"]`).setAttribute('onchange', `limpiarDedicacionReg(${index})`);
                row.querySelector(`[id^="tipo_dedicacion_r_"]`).setAttribute('onchange', `limpiarDedicacionPop(${index})`);
                row.querySelector(`[id^="horas_"]`).setAttribute('onchange', `validarHoras(${index})`);
                row.querySelector(`[id^="horas_r_"]`).setAttribute('onchange', `validarHoras(${index})`);
                row.querySelector(`[id^="anexa_hv_docente_nuevo_"]`).setAttribute('onchange', `toggleLinkAnexos(${index})`);
                row.querySelector(`[id^="actualiza_hv_antiguo_"]`).setAttribute('onchange', `toggleLinkAnexos(${index})`);

                // Ensure initial visibility of the link field is correct for the updated row
                toggleLinkAnexos(index);
            }
        });
        document.getElementById('num_docentes').value = rows.length;
    }
            // Función para generar las opciones de año y semestre

    function generarOpcionesAnioSemestre(valorPorDefecto) {
        var anioActual = new Date().getFullYear();
        var mesActual = new Date().getMonth() + 1;
        var opciones = [];

        // Incluir el periodo actual y los siguientes dos periodos
        if (mesActual >= 7) {
            opciones.push(anioActual + '-2');       // Periodo actual segundo semestre
            opciones.push((anioActual + 1) + '-1'); // Periodo siguiente primer semestre
            opciones.push((anioActual + 1) + '-2'); // Periodo siguiente segundo semestre
            opciones.push((anioActual + 2) + '-1'); // Periodo después del siguiente primer semestre
        } else {
            opciones.push(anioActual + '-1');       // Periodo actual primer semestre
            opciones.push(anioActual + '-2');       // Periodo actual segundo semestre
            opciones.push((anioActual + 1) + '-1'); // Periodo siguiente primer semestre
            opciones.push((anioActual + 1) + '-2'); // Periodo siguiente segundo semestre
        }

        var select = document.getElementById('anio_semestre');
        select.innerHTML = '';
        opciones.forEach(function(opcion) {
            var option = document.createElement('option');
            option.text = opcion;
            option.value = opcion;
            select.appendChild(option);
        });

        // Si hay un valor por defecto, seleccionarlo; de lo contrario, usar el segundo elemento
        if (valorPorDefecto && opciones.includes(valorPorDefecto)) {
            select.value = valorPorDefecto;
        } else {
            select.value = opciones[1]; // Seleccionar el segundo elemento como predeterminado
        }
    }

    // Llamar a la función pasando el valor recibido en GET (si existe)
    document.addEventListener('DOMContentLoaded', function() {
        // Obtener el valor de la variable GET
        const urlParams = new URLSearchParams(window.location.search);
        const anioSemestrePorDefecto = urlParams.get('anio_semestre'); // Obtiene la variable
        generarOpcionesAnioSemestre(anioSemestrePorDefecto);
    });


// Llamar a la función al cargar la página o cuando sea necesario
generarOpcionesAnioSemestre();


           // Función para manejar la selección de facultad
document.querySelectorAll('.facultad-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        // Desmarcar todos los botones
        document.querySelectorAll('.facultad-btn').forEach(function(btn) {
            btn.classList.remove('selected');
        });
        // Marcar el botón seleccionado
        this.classList.add('selected');

        // Asignar el valor al campo oculto
        document.getElementById('facultad').value = this.getAttribute('data-value');

        // Limpiar selección de departamento
        document.getElementById('departamento').value = '';

        // Limpiar número de docentes a ingresar
        document.getElementById('num_docentes').value = '';

        // Limpiar listado de profesores
        limpiarListadoProfesores();

        // Cargar departamentos
        var facultad_id = this.getAttribute('data-value');
        var departamento_div = document.getElementById('departamento-group');
        departamento_div.innerHTML = ''; // Limpiar el div de departamentos

        // Realizar una solicitud AJAX para obtener los departamentos de la facultad seleccionada
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'obtener_departamentos.php?facultad_id=' + facultad_id, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var departamentos = JSON.parse(xhr.responseText);
                    departamentos.forEach(function(depto) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'departamento-btn';
            button.textContent = depto.nombre;
            button.setAttribute('data-value', depto.id);

            // Si el tipo de usuario es 3 y el departamento no coincide, deshabilitar el botón
            var tipoUsuario = '<?php echo $tipo_usuario; ?>';
            var departamentoIdUsuario = '<?php echo $depto_user; ?>';

            if (tipoUsuario == 3 && depto.id != departamentoIdUsuario) {
                button.disabled = true; // Deshabilita el botón
                button.classList.add('disabled'); // Clase para estilizar botones deshabilitados si es necesario
            }

            button.addEventListener('click', function() {
                // Solo permitir interacción si el botón no está deshabilitado
                if (!this.disabled) {
                    // Desmarcar todos los botones
                    document.querySelectorAll('.departamento-btn').forEach(function(btn) {
                        btn.classList.remove('selected');
                    });
                    // Marcar el botón seleccionado
                    this.classList.add('selected');
                    // Asignar el valor al campo oculto
                    document.getElementById('departamento').value = this.getAttribute('data-value');

                    // Limpiar número de docentes a ingresar
                    document.getElementById('num_docentes').value = '';

                    // Limpiar listado de profesores
                    limpiarListadoProfesores();
                }
            });

            departamento_div.appendChild(button);
        });

                // Seleccionar el departamento por defecto si el tipo de usuario es 3
                                   var tipoUsuario = '<?php echo $tipo_usuario; ?>';

                        if (tipoUsuario == 3) {
                           // var departamento_id = $depto_user; // Reemplaza con el ID del departamento correspondiente
                           var departamento_id = '<?php echo $depto_user; ?>';

                            var defaultButton = document.querySelector(`.departamento-btn[data-value='${departamento_id}']`);

                            if (defaultButton) {
                                defaultButton.click();
                            }
                        }

            }
        };
        xhr.send();
    });
});

            // Función para manejar la selección del tipo de docente
            document.getElementById('tipo_docente').addEventListener('change', function() {
                limpiarCamposDocente(); // Limpiar los campos al cambiar el tipo de docente
                actualizarCamposDocente();
            });

            // Event listener para cambios en el número de docentes
            document.getElementById('num_docentes').addEventListener('change', function() {
                generarCamposDocente();
            });
function generarCamposDocente() {
    var num = parseInt(document.getElementById('num_docentes').value);
    var container = document.getElementById('docentes');

    // Crear la tabla si no existe
    var table = container.querySelector('table');
    if (!table) {
        table = document.createElement('table');
        var thead = document.createElement('thead');
        var tbody = document.createElement('tbody');

        // Encabezado de la tabla
        thead.innerHTML = `
            <tr>
                <th>#</th>
                <th>Cédula</th>
                <th>Nombre</th>
                <th class="tipo_dedicacion">Dedicación Pop</th>
                <th class="tipo_dedicacion_r">Dedicación Reg</th>
                <th class="horas">Horas Pop.</th>
                <th class="horas_r">Horas Reg.</th>
                <th>Anexa HV Nuevo</th>
                <th>Actualiza HV Antiguo</th>
                <th class="link-anexos-header">Link Anexos</th>
                <th>Eliminar</th>
            </tr>
        `;
        table.appendChild(thead);
        table.appendChild(tbody);
        container.appendChild(table);
    } else {
        var tbody = table.querySelector('tbody');
    }

    var currentRows = tbody.children.length;

 for (var i = currentRows; i < num; i++) {
    var tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${i + 1}</td>
        <td><input type="text" id="cedula_${i}" name="cedula[]" required onchange="buscarTercero(this); validarCedulaUnica(this)" class="alto-field"></td>
        <td><input type="text" id="nombre_${i}" name="nombre[]" readonly class="readonly-field"></td>
        <td class="tipo_dedicacion">
            <select id="tipo_dedicacion_${i}" name="tipo_dedicacion[]" onchange="limpiarDedicacionReg(${i})">
                <option value=""></option>
                <option value="TC">TC</option>
                <option value="MT">MT</option>
            </select>
        </td>
        <td class="tipo_dedicacion_r">
            <select id="tipo_dedicacion_r_${i}" name="tipo_dedicacion_r[]" onchange="limpiarDedicacionPop(${i})">
                <option value=""></option>
                <option value="TC">TC</option>
                <option value="MT">MT</option>
            </select>
        </td>
        <td class="horas">
            <input id="horas_${i}" name="horas[]" type="number" min="0" max="12" step="0.1" onchange="validarHoras(${i})">
        </td>
        <td class="horas_r">
            <input id="horas_r_${i}" name="horas_r[]" type="number" min="0" max="12" step="0.1" onchange="validarHoras(${i})">
        </td>
        <td>
            <select id="anexa_hv_docente_nuevo_${i}" name="anexa_hv_docente_nuevo[]" required onchange="toggleLinkAnexos(${i})">
                <option value="si">Sí</option>
                <option value="no" selected>No</option>
                <option value="no aplica">No Aplica</option>
            </select>
        </td>
        <td>
            <select id="actualiza_hv_antiguo_${i}" name="actualiza_hv_antiguo[]" required onchange="toggleLinkAnexos(${i})">
                <option value="si">Sí</option>
                <option value="no" selected>No</option>
                <option value="no aplica">No Aplica</option>
            </select>
        </td>
        <td class="link-anexos-cell">
            <div id="link_anexos_container_${i}" class="link-anexos-container">
                <input type="url" id="link_anexos_${i}" name="link_anexos[]" placeholder="Opcional Link Drive/Nube" class="alto-field">
            </div>
        </td>
        <td>
            <button type="button" class="eliminar-fila" onclick="eliminarFila(this)">&#128465;</button>
        </td>
    `;
    tbody.appendChild(tr);
    toggleLinkAnexos(i); // Set initial visibility for the new row
}
    actualizarCamposDocente();
}

// Función para limpiar el campo de Dedicación Reg cuando se selecciona un valor en Dedicación Pop
function limpiarDedicacionReg(index) {
    var tipoDedicacionPopSelect = document.getElementById(`tipo_dedicacion_${index}`);
    var tipoDedicacionRegSelect = document.getElementById(`tipo_dedicacion_r_${index}`);

    // Limpiar el otro campo
    tipoDedicacionRegSelect.value = '';
}

// Función para limpiar el campo de Dedicación Pop cuando se selecciona un valor en Dedicación Reg
function limpiarDedicacionPop(index) {
    var tipoDedicacionPopSelect = document.getElementById(`tipo_dedicacion_${index}`);
    var tipoDedicacionRegSelect = document.getElementById(`tipo_dedicacion_r_${index}`);

    // Limpiar el otro campo
    tipoDedicacionPopSelect.value = '';
}
                  function validarCedulaUnica(input) {
    var cedulas = document.querySelectorAll('input[name="cedula[]"]');
    var cedulasArray = Array.from(cedulas).map(function(element) {
        return element.value.trim();
    });

    var currentCedula = input.value.trim();

    // Permitir la cédula 222 sin restricciones
    if (currentCedula === '222') {
        return;
    }

    var count = cedulasArray.filter(function(cedula) {
        return cedula === currentCedula;
    }).length;

    if (count > 1) {
        alert('Esta cédula ya ha sido ingresada.');
        input.value = '';
    }
}

            function actualizarCamposDocente() {
                var tipoDocente = document.getElementById('tipo_docente').value;
                var numDocentes = document.getElementById('num_docentes').value;

                var tipoDedicacionCols = document.querySelectorAll('.tipo_dedicacion');
                var tipoDedicacion_rCols = document.querySelectorAll('.tipo_dedicacion_r');

                var sedeCols = document.querySelectorAll('.sede'); // This seems to be a typo, 'sede' is not used in the HTML structure
                var horasCols = document.querySelectorAll('.horas');
                var horasRCols = document.querySelectorAll('.horas_r');

                tipoDedicacionCols.forEach(col => col.style.display = tipoDocente === 'Ocasional' ? '' : 'none');
                tipoDedicacion_rCols.forEach(col => col.style.display = tipoDocente === 'Ocasional' ? '' : 'none');

                // Corrected: 'sedeCols' elements do not exist in your HTML, so this line might cause an error or simply do nothing.
                // If you intend to have a 'sede' column, you need to add it to your table HTML.
                // sedeCols.forEach(col => col.style.display = tipoDocente === 'Ocasional' ? '' : 'none');

                horasCols.forEach(col => col.style.display = tipoDocente === 'Catedra' ? '' : 'none');
                horasRCols.forEach(col => col.style.display = tipoDocente === 'Catedra' ? '' : 'none');

                // Loop through existing rows to apply display logic
                for (var i = 0; i < numDocentes; i++) {
                    var tipoDedicacionSelect = document.getElementById(`tipo_dedicacion_${i}`);
                    var tipoDedicacion_rSelect = document.getElementById(`tipo_dedicacion_r_${i}`);

                    // var sedeSelect = document.getElementById(`sede_${i}`); // This ID doesn't exist in your HTML
                    var horasInput = document.getElementById(`horas_${i}`);
                    var horasRInput = document.getElementById(`horas_r_${i}`);

                    if (tipoDocente === 'Catedra') {
                        if (tipoDedicacionSelect) tipoDedicacionSelect.disabled = true;
                        if (tipoDedicacion_rSelect) tipoDedicacion_rSelect.disabled = true;

                        // if (sedeSelect) sedeSelect.disabled = true; // No 'sede' select
                        if (horasInput) horasInput.disabled = false;
                        if (horasRInput) horasRInput.disabled = false;
                    } else if (tipoDocente === 'Ocasional') {
                        if (tipoDedicacionSelect) tipoDedicacionSelect.disabled = false;
                        if (tipoDedicacion_rSelect) tipoDedicacion_rSelect.disabled = false;

                        // if (sedeSelect) sedeSelect.disabled = false; // No 'sede' select
                        if (horasInput) horasInput.disabled = true;
                        if (horasRInput) horasRInput.disabled = true;
                    }
                     // Ensure the link anexos field is toggled based on the current state
                    toggleLinkAnexos(i);
                }
            }

      // Función para limpiar los campos de los docentes y el campo num_docentes
function limpiarCamposDocente() {
    var container = document.getElementById('docentes');
    // Remove all rows except the header
    var tbody = container.querySelector('tbody');
    if (tbody) {
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }
    }
    // Remove the whole table if it exists
    var table = container.querySelector('table');
    if (table) {
        container.removeChild(table);
    }

    // Reiniciar el valor del campo num_docentes
    document.getElementById('num_docentes').value = '';
}
// Función para limpiar el listado de profesores
function limpiarListadoProfesores() {
    var container = document.getElementById('docentes');
    container.innerHTML = ''; // Limpiar el contenido HTML del contenedor de docentes
    document.getElementById('num_docentes').value = ''; // Also clear the count
}
// Event listener para cambios en el tipo de docente
document.getElementById('tipo_docente').addEventListener('change', function() {
    limpiarCamposDocente(); // Limpiar los campos al cambiar el tipo de docente
    actualizarCamposDocente(); // Actualizar campos según el tipo de docente seleccionado
});
function buscarTercero(input) {
    var numDocumento = input.value;
    var nombreTerceroInput = input.parentElement.parentElement.querySelector('input[name="nombre[]"]');
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

// Function to toggle visibility of the 'Link Drive/Nube' field
function toggleLinkAnexos(index) {
    const anexaHvNuevoSelect = document.getElementById(`anexa_hv_docente_nuevo_${index}`);
    const actualizaHvAntiguoSelect = document.getElementById(`actualiza_hv_antiguo_${index}`);
    const linkAnexosContainer = document.getElementById(`link_anexos_container_${index}`);
    const linkAnexosInput = document.getElementById(`link_anexos_${index}`);

    if (!anexaHvNuevoSelect || !actualizaHvAntiguoSelect || !linkAnexosContainer || !linkAnexosInput) {
        return; // Asegurarse que los elementos existan
    }

    // Verificar si ALGUNO de los campos está en "Sí"
    const showLink = anexaHvNuevoSelect.value === 'si' || actualizaHvAntiguoSelect.value === 'si';

    if (showLink) {
        linkAnexosContainer.style.display = 'flex'; // Mostrar si hay un "Sí"
        linkAnexosInput.required = false; // Hacerlo obligatorio
    } else {
        linkAnexosContainer.style.display = 'none'; // Ocultar si ambos son "No"
        linkAnexosInput.required = false; // No obligatorio
    }

    // Actualizar visibilidad del encabezado de la tabla
    actualizarVisibilidadEncabezadoLink();
}

// Nueva función para actualizar el encabezado de la tabla
function actualizarVisibilidadEncabezadoLink() {
    const linkAnexosHeaders = document.querySelectorAll('.link-anexos-header');
    let anyLinkFieldVisible = false;

    // Revisar todos los contenedores de link
    document.querySelectorAll('.link-anexos-container').forEach(container => {
        if (container.style.display === 'flex') {
            anyLinkFieldVisible = true;
        }
    });

    // Mostrar u ocultar encabezados
    linkAnexosHeaders.forEach(header => {
        header.style.display = anyLinkFieldVisible ? '' : 'none';
    });
}

// Initial call to toggleLinkAnexos for existing rows on page load (if any are pre-filled)
document.addEventListener('DOMContentLoaded', function() {
    // This will handle the case when data is loaded from the previous period
    var numDocentes = parseInt(document.getElementById('num_docentes').value) || 0;
    for (let i = 0; i < numDocentes; i++) {
        toggleLinkAnexos(i);
    }

    // Also ensure the header is initially hidden if no rows are present or none require the link
    const linkAnexosHeaders = document.querySelectorAll('.link-anexos-header');
    let anyLinkFieldVisible = false;
    document.querySelectorAll('.link-anexos-container').forEach(container => {
       if (container.style.display === 'flex') { // Updated to check for 'flex'
    anyLinkFieldVisible = true;
}
    });
    linkAnexosHeaders.forEach(header => {
        header.style.display = anyLinkFieldVisible ? '' : 'none';
    });
});
            
            
 </script>

    </body>
    </html>