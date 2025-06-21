<?php
require('include/headerz.php');
?>
<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Solicitudes de Contratación Temporales</title>
        <style>
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
    font-size: 1.2em;
    cursor: pointer;
    padding: 0;
}

.eliminar-fila:hover {
    color: darkred;
}
            .add-container {
    display: inline-flex;
    align-items: center;
    margin-left: 5px; /* Espacio entre el input y el contenedor */
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


        </style>
        
        <script>
                
</script>
    </head>
    <body>
<div id ="contenido">
    
    
    
    <?php
         $facultadId =  $_GET['facultad']; 
         $departamentoId  =$_GET['departamento'];
require 'cn.php';
$nombre_sesion= $_SESSION['name'];
//echo $nombre_sesion;
$consultaf = "SELECT *  FROM users where users.Name= '$nombre_sesion'";
$resultadof = $con->query($consultaf);
while ($row = $resultadof -> fetch_assoc()){
    $nombre_usuario= $row['Name'];
    $email_fac= $row['Email'];
//echo $email_fac;
    $where = "";
   if ($email_fac <> 'elmerjs@gmail.com') {
    $where = "WHERE email_fac LIKE '%$email_fac%'";
}
}
?>
    
        <form action="guardar.php" method="POST">
       

              <div class="row">
            <label for="anio_semestre">Periodo:</label>
            <select id="anio_semestre" name="anio_semestre" required onchange="cargarDatosPeriodoAnterior()">
                <!-- Las opciones de año y semestre se generarán dinámicamente con JavaScript -->
            </select>
        </div>


            <div class="row">
                <label for="tipo_docente">Tipo de Docente:</label>
                <select id="tipo_docente" name="tipo_docente" required>
                    <option value="Ocasional">Ocasional</option>
                    <option value="Catedra">Cátedra</option>
                </select>
            </div>
            <div class="row">
    <label for="num_docentes">Número de Docentes a Ingresar:</label>
    <input type="number" id="num_docentes" name="num_docentes" min="1">
    
</div><div class="add-container">
        <span class="toggle-icon"style="margin-right: 5px; margin-top: 25px;">◄</span> <!-- Ícono de flecha hacia la izquierda -->
        <span style="margin-right: 5px; margin-top: 25px;">adicione aquí</span>
    </div>
            <br>

            <!-- Aquí agregamos los campos para los docentes -->
            <div id="docentes">
                <!-- Los campos de los docentes se generarán dinámicamente con JavaScript -->
            </div>
    <button type="button" class="custom-button" onclick="cargarDatosPeriodoAnterior()">Cargar datos del periodo anterior</button>

            <button type="submit">Continuar</button>
        </form>
        </div>

        <script>

            function cargarDatosPeriodoAnterior() {
        var periodoSeleccionado = document.getElementById('anio_semestre').value;
            var tipo_docente = document.getElementById('tipo_docente').value;

        if (!periodoSeleccionado ) {
            alert('Por favor seleccione un periodo');
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
        xhr.open('GET', 'obtener_datos_periodo_anterior.php?facultad_id=' + $facultadId + '&departamento_id=' + $departamentoId + '&anio_semestre=' + periodoAnterior+ '&tipo_docente=' + tipo_docente, true);
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
                <th class="tipo_dedicacion">Dedicación Pop</th>
                <th class="tipo_dedicacion_r">Dedicación Reg</th>
                <th class="horas">Horas Pop.</th>
                <th class="horas_r">Horas_Reg.</th>
                <th>Anexa HV Nuevo</th>
                <th>Actualiza HV Antiguo</th>
                <th>Eliminar</th>
            </tr>
        `;
        table.appendChild(thead);
        table.appendChild(tbody);

        datos.forEach((dato, i) => {
            var tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td><input type="text" id="cedula_${i}" name="cedula[]" value="${dato.cedula}" required oninput="buscarTercero(this)"></td>
                <td><input type="text" id="nombre_${i}" name="nombre[]" value="${dato.nombre}" required></td>
                <td class="tipo_dedicacion">
                    <select id="tipo_dedicacion_${i}" name="tipo_dedicacion[]">
                        <option value=""></option>
                        <option value="TC" ${dato.tipo_dedicacion === 'TC' ? 'selected' : ''}>TC</option>
                        <option value="MT" ${dato.tipo_dedicacion === 'MT' ? 'selected' : ''}>MT</option>
                    </select>
                </td>
                <td class="tipo_dedicacion_r">
                    <select id="tipo_dedicacion_r_${i}" name="tipo_dedicacion_r[]">
                        <option value=""></option>
                        <option value="TC" ${dato.tipo_dedicacion_r === 'TC' ? 'selected' : ''}>TC</option>
                        <option value="MT" ${dato.tipo_dedicacion_r === 'MT' ? 'selected' : ''}>MT</option>
                    </select>
                </td>
                <td class="horas">
                    <input id="horas_${i}" name="horas[]" type="number" value="${dato.horas}">
                </td>
                <td class="horas_r">
                    <input id="horas_r_${i}" name="horas_r[]" type="number" value="${dato.horas_r}">
                </td>
                <td>
                    <select id="anexa_hv_docente_nuevo_${i}" name="anexa_hv_docente_nuevo[]" required>
                        <option value="si" ${dato.anexa_hv_docente_nuevo === 'si' ? 'selected' : ''}>Sí</option>
                        <option value="no" ${dato.anexa_hv_docente_nuevo === 'no' ? 'selected' : ''}>No</option>
                        <option value="no aplica" ${dato.anexa_hv_docente_nuevo === 'no aplica' ? 'selected' : ''}>No Aplica</option>
                    </select>
                </td>
                <td>
                    <select id="actualiza_hv_antiguo_${i}" name="actualiza_hv_antiguo[]" required>
                        <option value="si" ${dato.actualiza_hv_antiguo === 'si' ? 'selected' : ''}>Sí</option>
                        <option value="no" ${dato.actualiza_hv_antiguo === 'no' ? 'selected' : ''}>No</option>
                        <option value="no aplica" ${dato.actualiza_hv_antiguo === 'no aplica' ? 'selected' : ''}>No Aplica</option>
                    </select>
                </td>
                <td>
    <button type="button" class="eliminar-fila" onclick="eliminarFila(this)">&#x2716;</button>
</td>

            `;
            tbody.appendChild(tr);
        });

        container.appendChild(table);
        actualizarCamposDocente();
        document.getElementById('num_docentes').value = datos.length;
    }


    // Función para eliminar la fila
    function eliminarFila(button) {
        var row = button.parentNode.parentNode;
        row.parentNode.removeChild(row);

        // Actualizar el número de filas restantes
        actualizarNumerosFila();
    }
            function actualizarNumerosFila() {
        var table = document.querySelector('#docentes table tbody');
        var rows = table.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelector('td:first-child').innerText = index + 1;
        });
        document.getElementById('num_docentes').value = rows.length;
    }
            // Función para generar las opciones de año y semestre
function generarOpcionesAnioSemestre() {
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

    // Seleccionar por defecto el primer elemento que es el siguiente al actual
    select.value = opciones[1]; // opcionalmente [1] si se quiere que sea el segundo

}


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
                    button.addEventListener('click', function() {
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
                    });
                    departamento_div.appendChild(button);
                });
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
                    <th class="horas_r">Horas_Reg.</th>
                    <th>Anexa HV Nuevo</th>
                    <th>Actualiza HV Antiguo</th>
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
                <td><input type="text" id="cedula_${i}" name="cedula[]" required oninput="buscarTercero(this)"></td>
                <td><input type="text" id="nombre_${i}" name="nombre[]" required></td>
                <td class="tipo_dedicacion">
                    <select id="tipo_dedicacion_${i}" name="tipo_dedicacion[]">
                        <option value=""></option>
                        <option value="TC">TC</option>
                        <option value="MT">MT</option>
                    </select>
                </td>
                <td class="tipo_dedicacion_r">
                    <select id="tipo_dedicacion_r_${i}" name="tipo_dedicacion_r[]">
                        <option value=""></option>
                        <option value="TC">TC</option>
                        <option value="MT">MT</option>
                    </select>
                </td>
                <td class="horas">
                    <input id="horas_${i}" name="horas[]" type="number">
                </td>
                <td class="horas_r">
                    <input id="horas_r_${i}" name="horas_r[]" type="number">
                </td>
                <td>
                    <select id="anexa_hv_docente_nuevo_${i}" name="anexa_hv_docente_nuevo[]" required>
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                        <option value="no aplica">No Aplica</option>
                    </select>
                </td>
                <td>
                    <select id="actualiza_hv_antiguo_${i}" name="actualiza_hv_antiguo[]" required>
                        <option value="si">Sí</option>
                        <option value="no">No</option>
                        <option value="no aplica">No Aplica</option>
                    </select>
                </td>
                <td>
    <button type="button" class="eliminar-fila" onclick="eliminarFila(this)">&#x2716;</button>
                </td>
            `;
            tbody.appendChild(tr);
        }

        actualizarCamposDocente();
    }


            function actualizarCamposDocente() {
                var tipoDocente = document.getElementById('tipo_docente').value;
                var numDocentes = document.getElementById('num_docentes').value;

                var tipoDedicacionCols = document.querySelectorAll('.tipo_dedicacion');
                var tipoDedicacion_rCols = document.querySelectorAll('.tipo_dedicacion_r');

                var sedeCols = document.querySelectorAll('.sede');
                var horasCols = document.querySelectorAll('.horas');
                var horasRCols = document.querySelectorAll('.horas_r');

                tipoDedicacionCols.forEach(col => col.style.display = tipoDocente === 'Ocasional' ? '' : 'none');
                tipoDedicacion_rCols.forEach(col => col.style.display = tipoDocente === 'Ocasional' ? '' : 'none');

                sedeCols.forEach(col => col.style.display = tipoDocente === 'Ocasional' ? '' : 'none');
                horasCols.forEach(col => col.style.display = tipoDocente === 'Catedra' ? '' : 'none');
                horasRCols.forEach(col => col.style.display = tipoDocente === 'Catedra' ? '' : 'none');

                for (var i = 0; i < numDocentes; i++) {
                    var tipoDedicacionSelect = document.getElementById(`tipo_dedicacion_${i}`);
                    var tipoDedicacion_rSelect = document.getElementById(`tipo_dedicacion_r_${i}`);

                    var sedeSelect = document.getElementById(`sede_${i}`);
                    var horasInput = document.getElementById(`horas_${i}`);
                    var horasRInput = document.getElementById(`horas_r_${i}`);

                    if (tipoDocente === 'Catedra') {
                        tipoDedicacionSelect.disabled = true;
                        tipoDedicacion_rSelect.disabled = true;

                        sedeSelect.disabled = true;
                        horasInput.disabled = false;
                        horasRInput.disabled = false;
                    } else if (tipoDocente === 'Ocasional') {
                        tipoDedicacionSelect.disabled = false;
                        tipoDedicacion_rSelect.disabled = false;

                        sedeSelect.disabled = false;
                        horasInput.disabled = true;
                        horasRInput.disabled = true;
                    }
                }
            }

      // Función para limpiar los campos de los docentes y el campo num_docentes
function limpiarCamposDocente() {
    var container = document.getElementById('docentes');
    var currentRows = container.querySelectorAll('tr').length - 1; // Excluimos el encabezado

    for (var i = currentRows; i > 0; i--) {
        container.removeChild(container.lastElementChild);
    }

    // Reiniciar el valor del campo num_docentes
    document.getElementById('num_docentes').value = ''; // o puedes asignarle un valor predeterminado, por ejemplo 1
}
// Función para limpiar el listado de profesores
function limpiarListadoProfesores() {
    var container = document.getElementById('docentes');
    container.innerHTML = ''; // Limpiar el contenido HTML del contenedor de docentes
}
// Event listener para cambios en el tipo de docente
document.getElementById('tipo_docente').addEventListener('change', function() {
    limpiarCamposDocente(); // Limpiar los campos al cambiar el tipo de docente
    actualizarCamposDocente(); // Actualizar campos según el tipo de docente seleccionado
});
            function buscarTercero(input) {
                var numDocumento = input.value;
                var nombreTerceroInput = input.parentElement.parentElement.querySelector('input[name="nombre[]"]');

                // Realizar una solicitud AJAX para buscar coincidencias en la tabla de terceros
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'buscar_tercero.php?num_documento=' + numDocumento, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var nombreTercero = xhr.responseText;
                        // Asignar el nombre del tercero al campo de entrada del nombre correspondiente
                        nombreTerceroInput.value = nombreTercero;
                    }
                };
                xhr.send();
            }
        </script>
    </body>
    </html>
