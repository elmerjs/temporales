


<head>
---
    
    <style>
      
.cedula-nueva {
    color: #28a745; /* Verde */
    font-weight: bold;
}

/* Para mantener visibilidad en fondos amarillos */
.fondo-amarillo .cedula-nueva {
    color: #1a4d2b !important; /* Verde más oscuro */
        background-color: yellow; /* Amarillo claro */

}
         .cedula-eliminada {
        color: red !important;
        font-weight: bold;
    }
     
.cedula-en-otro-tipo {
    color: red;
    background-color: yellow;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
              padding: 1px; /* Aumenta el espacio de relleno de las celdas */
        }
        th {
            background-color: #004080;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        .centered-column {
    text-align: center ;
}
        button {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
       .update-btn, .delete-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    margin: 0 2px;
    font-size: 12px;
    line-height: 1;
    color: #555;
}

.update-btn:hover {
    color: #004080;
}

.delete-btn:hover {
    color: #f44336;
}
         .estado-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
         .container {
            display: flex;
            justify-content: space-between; /* Espacia los divs uniformemente */
            align-items: stretch; /* Asegura que los divs se estiren a la misma altura */
              flex-wrap: wrap;

             gap: 20px; /* Espacio entre los divs */
            max-width: 1600px; /* Ancho máximo del contenedor */
            margin: 0 auto; /* Centra el contenedor horizontalmente */
            padding: 10px; /* Espaciado interno del contenedor */
        }
     .box {
    flex: 0 0 49%; /* Fijo al 49% para dejar un pequeño espacio entre ellos */
    max-width: 49%;
    box-sizing: border-box; /* Incluye padding y borde dentro del ancho */
    /*height: 300px; /* O la altura fija que desees */
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}

        
        .box-gray {
          }
         .box-white {
            background-color: white; /* Fondo gris claro */
            border-color: #ccc; /* Borde ligeramente más oscuro */
        }
        .btn-primary {
    height: 38px; /* Ajusta según el botón "Abrir Estado" */
    padding: 0 10px; /* Reduce el espacio vertical */
    font-size: 14px;
    line-height: 38px; /* Centra el texto verticalmente */
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

     .label-italic {
  font-style: italic;
}
        
        #textoObservacion {
    white-space: pre-line;
}
            
    </style>
    
    
</head>
<body>
   <?php if ($tipo_usuario != 3): ?>
    <span style="display: inline-block; padding: 4px 8px; border: 1px solid #ccc; background-color: #f8f9fa; border-radius: 3px;">
    <a href="report_depto_comparativo.php?anio_semestre=<?= urlencode($anio_semestre) ?>" class="btn btn-light" title="Regresar a 'Gestión facultad'" style="text-decoration: none; color: inherit; padding: 2px 5px;">
        Regresar <i class="fas fa-arrow-left"></i>
    </a>
</span>

    
    

    <div class="container">
        
        <div class="box">

    <h3> <?php 
    $nombre_fac = obtenerNombreFacultad($departamento_id);
    $nombre_depto = mb_strimwidth(obtenerNombreDepartamento($_POST['departamento_id']), 0, 20, '...');
    echo $nombre_fac . ' - ' . $nombre_depto . '. Periodo: ' . htmlspecialchars($_POST['anio_semestre']) . '.';
?>
</h3>
            
     <?php
           echo "<div class='box-gray'>";         
        echo "<div class='estado-container d-flex justify-content-between align-items-center mb-3'>
    <h3 class='mb-0'>Vinculación: $tipo_docente (";

if ($tipo_docente == 'Catedra') {
    $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
    echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
} else {
    $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose);
    echo "<strong>" . ucfirst(strtolower($estadoDepto)) . "</strong>";
    }
echo ")</h3>";

// Mostrar el botón de "Agregar Profesor" si no está cerrado

    $todosCerrados = false;
if ($tipo_usuario == 1) {
    echo "
    <form action='nuevo_registro_admin.php' method='GET' class='mb-0'>
        <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
        <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
        <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
        <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
        <button type='submit' class='btn btn-outline-success btn-sm d-flex align-items-center gap-1' title='Agregar Profesor'>
            <i class='fas fa-user-plus'></i> Agregar Profesor
        </button>
    </form>";
}
echo "</div>";
    
    

   if ($result->num_rows > 0) {
    echo "<table border='1'>
    <tr>
        <th rowspan='2'>Ítem</th>
        <th rowspan='2'>Cédula</th>
        <th rowspan='2'>Nombre</th>
        
        ";

// Columna de dedicación (si aplica), con colspan=2 para los dos tipos de sede
if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
    echo "<th colspan='2'>Dedicación</th>";
}
if ($tipo_usuario == 1) {
// Columna acciones con 2 subcolumnas
echo "<th colspan='2'>Acciones</th>";
}
echo "<th rowspan='2'>Puntos</th>";
echo "</tr>";

// Fila de subcabeceras: dedicación y acciones
echo "<tr>";

// Subcolumnas dedicación
if ($tipo_docente == "Ocasional") {
    echo "
        <th title='Sede Popayán'>Pop</th>
        <th title='Sede Regionalización'>Reg</th>
    ";
} elseif ($tipo_docente == "Catedra") {
    echo "
        <th title='Horas en Sede Popayán'>Pop</th>
        <th title='Horas en Sede Regionalización'>Reg</th>
    ";
}
if ($tipo_usuario == 1) {
// Subcolumnas acciones (siempre)
echo "
    <th>Eliminar</th>
    <th>Editar</th>
";
}
echo "</tr>";


while ($row = $result->fetch_assoc()) {
     $cedula = $row['cedula'];
    
    // Verificar si es NUEVA (no existe en período anterior)
    $esNueva = !in_array($cedula, $cedulasPeriodoAnterior);
    $claseColor = $esNueva ? 'cedula-nueva' : '';
    
    // Verificar si cambió de tipo (existe en anterior pero con diferente tipo)
    $cambioTipo = in_array($cedula, $cedulasCambioTipo);
    
    // Aplicar clases CSS
    $claseFila = $cambioTipo ? 'fondo-amarillo' : '';
    $claseTexto = $esNueva ? 'cedula-nueva' : '';

    if ($esNueva) {
        $contadorVerdes++;
        if ($tipo_docente =='Ocasional') $contadorVerdesOc++; 
        else $contadorVerdesCa++;
    }
    
    echo "<tr class='$claseFila'>";
    echo "<td>" . $item . "</td>";
    echo "<td style='text-align: left;' class='$claseTexto'>" . htmlspecialchars($cedula) . "</td>";
    echo "<td style='text-align: left;' class='$claseTexto'>" . htmlspecialchars($row["nombre"]) . "</td>";

    if ($tipo_docente == "Ocasional") {
        echo "<td>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
    
    if ($tipo_docente == "Catedra") {
        $horas = ($row["horas"] == 0) ? "" : htmlspecialchars($row["horas"]);
        $horas_r = ($row["horas_r"] == 0) ? "" : htmlspecialchars($row["horas_r"]);
        echo "<td>" . $horas . "</td>
              <td>" . $horas_r . "</td>";
    }
    if ($tipo_usuario == 1) {
      echo "<td>";
   
echo "
<form action='eliminar_admin.php' method='POST' class='delete-form' style='display:inline;'>
    <input type='hidden' name='id_solicitud' value='".htmlspecialchars($row["id_solicitud"])."'>
    <input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>
    <input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>
    <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
    <input type='hidden' name='tipo_docente' value='".htmlspecialchars($tipo_docente)."'>
    <input type='hidden' name='motivo_eliminacion' class='motivo-input' value=''>
    <!-- tipo_eliminacion se agregará dinámicamente -->
    <button type='submit' class='delete-btn' title='Eliminar registro'>
        <i class='fas fa-trash fa-sm'></i>
    </button>
</form>";
   
        echo "</td><td>";
    
            echo "
                <form action='actualizar_admin.php' method='GET' style='display:inline;'>
                    <input type='hidden' name='id_solicitud' value='" . htmlspecialchars($row["id_solicitud"]) . "'>
                    <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
                    <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
                    <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
                    <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                    <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                </form></td>";
        
    }
            echo "<td>" . $row["puntos"] . "</td>";

    echo "</tr>";
    $item++;
}
    echo "</table>";
}
 else {
        echo "<p style='text-align: center;'>No se encontraron resultados.</p>";

    }

    // Cerrar conexión
    ?>
    <div class="d-flex justify-content-between mt-3">
     
         <?php
                 if ($estadoDepto == "ABIERTO" && $tipo_usuario == 3) {
    $mostrarFormulario = true;
} else {
    $mostrarFormulario = false;
}

// Vista

?>
    

        
   <?php if ($estadoDepto == "CERRADO") { 
    $envio_fac = obtenerenviof($facultad_id, $anio_semestre);
            
    $acepta_vra = obteneraceptacionvra($facultad_id, $anio_semestre);
 } ?>

    </div>
            
    </div>      <br>   
    <?php 
    }
        
   // $conn->close();
            
    ?>
       
    </div>
      
<!-- Botón "Ver Departamento" -->
        <div class="box">
        <h3 style="margin-top:0px;">Periodo anterior: <?php echo htmlspecialchars($periodo_anterior); ?></h3>

<?php

while ($rowtipo = $resultadotipo->fetch_assoc()) {
   
   ..
    echo "<div class='box-gray'>";
    echo "<div class='estado-container'>
          <h3>Vinculación: ".$tipo_docente." (Periodo anterior)</h3>
          </div>";

    if ($result->num_rows > 0) {
        echo "<table border='1'>
                <tr>
                    <th rowspan='2'>Ítem</th>
                    <th rowspan='2'>Cédula</th>
                    <th rowspan='2'>Nombre</th>
                  ";

        if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
            echo "<th colspan='2'>Dedicación</th>";
        }

        echo "<th rowspan='2'>Puntos</th>";
        echo "</tr>";

        if ($tipo_docente == "Ocasional") {
            echo "<tr><th>Pop</th><th>Reg</th></tr>";
        } elseif ($tipo_docente == "Catedra") {
            echo "<tr><th>Pop</th><th>Reg</th></tr>";
        }
  

while ($row = $result->fetch_assoc()) {
    
    // ▶ Definir clase CSS según el caso
    if ($cedulaEstaEnOtroTipo) {
        $claseRoja = 'cedula-en-otro-tipo'; // fondo amarillo, texto rojo
    } elseif ($cedulaEliminada) {
        $claseRoja = 'cedula-eliminada'; // solo texto rojo
    } else {
        $claseRoja = '';
    }


    echo "<tr>";
    echo "<td>" . $item . "</td>";
    echo "<td style='text-align: left;' class='$claseRoja'>" . htmlspecialchars($cedula) . "</td>";
    echo "<td style='text-align: left;' class='$claseRoja'>" . htmlspecialchars($row["nombre"]) . "</td>";
     

    if ($tipo_docente == "Ocasional") {
        echo "<td>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
              <td>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
    }
     if ($tipo_docente == "Catedra") {
                $horas = $row["horas"] == 0 ? "" : htmlspecialchars($row["horas"]);
                $horas_r = $row["horas_r"] == 0 ? "" : htmlspecialchars($row["horas_r"]);
                echo "<td>$horas</td><td>$horas_r</td>";
            }

    // Poner los puntos al final
echo "<td>" . $row["puntos"] . "</td>";
echo "</tr>";
    $item++;
}

        echo "</table>";
    } else {
        echo "<p style='text-align: center;'>No se encontraron resultados para el periodo anterior.</p>";
    }

    echo "</div><br>";
    
}
?>
</div>    
   