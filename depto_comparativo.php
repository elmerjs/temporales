<?php
echo "<div id='seccionTablas'></div>";

require('include/headerz.php');
require 'funciones.php';
// Conexión a la base de datos (ajusta los parámetros según tu configuración)
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
//require 'actualizar_usuario.php'; // <-- Incluir aquí
 if (!isset($_SESSION['name']) || empty($_SESSION['name'])) {
    // Si no hay sesión activa, muestra un mensaje y redirige
    echo "<span style='color: red; text-align: left; font-weight: bold;'>
          <a href='index.html'>inicie sesión</a>
          </span>";
    exit(); // Detener toda la ejecución del script
}

    // Obtener los parámetros de la URL
$facultad_id = isset($_POST['facultad_id']) ? $_POST['facultad_id'] : null;
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre = $_POST['anio_semestre'];
    $periodo_anterior = $_POST['anio_semestre_anterior'];
$origen = $_POST['origen'] ?? null;

 $aniose= $anio_semestre;
function obtenerPeriodoAnterior($anio_semestre) {
    list($anio, $semestre) = explode('-', $anio_semestre);
    if ($semestre == '1') {
        $anio--;
        $semestre = '2';
    } else {
        $semestre = '1';
    }
    return $anio . '-' . $semestre;
}
//$periodo_anterior= obtenerPeriodoAnterior($aniose);

        $cierreperiodo = obtenerperiodo($anio_semestre);

$consultaper = "SELECT * FROM periodo where periodo.nombre_periodo ='$anio_semestre'";
$resultadoper = $conn->query($consultaper);
while ($rowper = $resultadoper->fetch_assoc()) {
    $fecha_ini_cat = $rowper['inicio_sem'];
    $fecha_fin_cat = $rowper['fin_sem'];
    $fecha_ini_ocas = $rowper['inicio_sem_oc'];
    $fecha_fin_ocas = $rowper['fin_sem_oc'];
    $valor_punto = $rowper['valor_punto'];
    $smlv = $rowper['smlv'];
  
}


$consultaperant = "SELECT * FROM periodo where periodo.nombre_periodo ='$periodo_anterior'";
$resultadoperant = $conn->query($consultaperant);
while ($rowper = $resultadoperant->fetch_assoc()) {
    $fecha_ini_catant = $rowper['inicio_sem'];
    $fecha_fin_catant = $rowper['fin_sem'];
    $fecha_ini_ocasant = $rowper['inicio_sem_oc'];
    $fecha_fin_ocasant = $rowper['fin_sem_oc'];
    $valor_puntoant = $rowper['valor_punto'];
    $smlvant = $rowper['smlv'];
   
}
  // Semanas catedra
    $fecha_inicio = new DateTime($fecha_ini_cat);
$fecha_fin = new DateTime($fecha_fin_cat);
  $intervalo = $fecha_inicio->diff($fecha_fin);

// Obtener el total de días y convertir a semanas
$dias = $intervalo->days -1;
$semanas_cat = ceil($dias / 7); // redondea hacia arriba
// Semanas ocasionales
$inicio_ocas = new DateTime($fecha_ini_ocas);
$fin_ocas = new DateTime($fecha_fin_ocas);
$dias_ocas = $inicio_ocas->diff($fin_ocas)->days-2;
$semanas_ocas = ceil($dias_ocas / 7);
    
     // Semanas catedra anterior
try {
    if (empty($fecha_ini_catant)) {
        throw new Exception("No se puede comparar");
    }

    $fecha_inicioant = new DateTime($fecha_ini_catant);

} catch (Exception $e) {
    echo "<strong>" . $e->getMessage() . "</strong>";
    return; // o exit; si estás fuera de una función
}
$fecha_finant = new DateTime($fecha_fin_catant);
  $intervaloant = $fecha_inicioant->diff($fecha_finant);

// Obtener el total de días y convertir a semanas
$diasant = $intervaloant->days - 1;
$semanas_catant = ceil($diasant / 7); // redondea hacia arriba
// Semanas ocasionales
$inicio_ocasant = new DateTime($fecha_ini_ocasant);
$fin_ocasant = new DateTime($fecha_fin_ocasant);
$dias_ocasant = $inicio_ocasant->diff($fin_ocasant)->days-1;
$semanas_ocasant = ceil($dias_ocasant / 7);
  
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Solicitudes</title>
         <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- jQuery y Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

         <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
   
    
<!-- Cargar Bootstrap 5 y Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- jQuery (si es necesario) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>


    
    <style>
      
.cedula-nueva {
    color: red;
    font-weight: bold;
}

/* Para mantener visibilidad en fondos amarillos */
.fondo-amarillo .cedula-nueva {
    color: mediumvioletred!important; /* Verde más oscuro */
        background-color: yellow; /* Amarillo claro */

}
         .cedula-eliminada {
        color:#28a745; /* Verde */!important;
        font-weight: bold;
    }
     
.cedula-en-otro-tipo {
    color: red;
    background-color: yellow;
}
        
        body {
            font-family: Arial, sans-serif;
            margin: 0px auto;
            padding: 20px;
            max-width: 95%; /* Establece el ancho máximo de la página */
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
            margin-top: 20px;border-radius: 8px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
              padding: 1px; /* Aumenta el espacio de relleno de las celdas */
            
        }
       th {
    background-color: #0066cc; /* Azul más claro */
    color: white;
}
        th {
    background-color: #F3F4F6; /* Gris claro neutro */
    color: #111827;
    font-weight: 600;
    border-bottom: 1px solid #E5E7EB;
}

     tr:nth-child(even) {
    background-color: #f9f9f9;
}
      
        .centered-column {
    text-align: center ;
}
        tr:hover {
    background-color: #e9f5ff;
    cursor: pointer;
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

/* Botón Editar - Usando azul institucional (#0066cc) con variantes */
.update-btn {
    color: #0066cc; /* Azul principal Unicauca */
    transition: all 0.3s ease;
}

.update-btn:hover {
    color: #004080; /* Azul más oscuro para hover */
    transform: scale(1.1);
}

/* Botón Eliminar - Usando rojo institucional (#cc0000) con variantes */
.delete-btn {
    color: #cc3333; /* Rojo institucional */
    transition: all 0.3s ease;
}

.delete-btn:hover {
    color: #990000; /* Rojo más oscuro para hover */
    transform: scale(1.1);
}

/* Opcional: Efecto adicional para mejor interactividad */
.update-btn:hover, .delete-btn:hover {
    text-shadow: 0 0 5px rgba(0,0,0,0.2);
    cursor: pointer;
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
            max-width: 95%; /* Ancho máximo del contenedor */
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
.box {
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
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
} /* Apply Open Sans to all text elements */
        body, h1, h2, h3, h4, h5, h6, p, span, div, a, li, td, th {
            font-family: 'Open Sans', sans-serif !important;
        }
    /* Estilos generales de tarjeta */
            
    /* NUEVOS ESTILOS PARA GRID DE COMPARACIÓN */
    .grid-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .grid-header {
        grid-column: 1 / span 2;
        padding: 10px;
        background: #f8f9fa;
        font-weight: bold;
        border-bottom: 2px solid #dee2e6;
        text-align: center;
        margin-bottom: 10px;
    }

    .grid-row {
        display: contents;
    }

    .grid-col {
        padding: 0;
    }
    </style>
    <script>
        function confirmarEnvio(count, tipo) {
            return confirm(` Se confirman ${count} profesores de  ${tipo}. ¿Desea continuar?`);
        }
    </script>
    
</head>
<body>
   <?php if ($tipo_usuario != 4): ?>
   <?php
if (isset($_POST['envia'])) {
    switch ($_POST['envia']) {
        case 'rcc':
            $archivo_regreso = 'report_depto_comparativo_costos.php';
            break;
        case 'ce':
            $archivo_regreso = 'comparativo_espejo.php';
            break;
        case 'rcec':
            $archivo_regreso = 'report_depto_comparativo_costos_espejo.php';
            break;
        case 'consulta_todo_depto':  // ¡Nuevo caso!
            $archivo_regreso = 'consulta_todo_depto.php';
            break;
        case 'report_depto_comparativo':  // ¡Nuevo caso!
            $archivo_regreso = 'report_depto_comparativo.php';
    }
} else {
    $archivo_regreso = 'report__compartivo_test.php';
}
?>


<?php endif; 
                   $huboCambioVinculacion = false; // <-- DECLARA E INICIALIZA LA BANDERA AQUÍ
 
    ?>
     <style>
        /* Definición de colores Unicauca (asegúrate de que estén aquí o en tu archivo CSS principal) */
       
       
        /* --- NUEVOS ESTILOS PARA EL ENCABEZADO DE NAVEGACIÓN --- */
        .navigation-header {
            background-color: var(--unicauca-azul); /* Fondo azul Unicauca */
            color: var(--unicauca-blanco); /* Texto blanco */
            padding: 10px 20px; /* Ajusta el padding para que se vea bien */
            border-radius: 8px; /* Bordes redondeados */
            margin-bottom: 20px; /* Espacio debajo del encabezado */
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra sutil */
        }

        .navigation-header .btn-back {
            background-color: #FDB12D; /* Azul más oscuro para el botón */
            color: var(--unicauca-blanco);
            border: 1px solid var(--unicauca-azul-oscuro);
            padding: 8px 15px; /* Ajusta el padding del botón */
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            font-size: 0.9em; /* Tamaño de fuente ligeramente más pequeño */
            display: inline-flex; /* Para alinear icono y texto */
            align-items: center;
        }

        .navigation-header .btn-back:hover {
            background-color: var(--unicauca-primary); /* Vuelve al azul principal en hover */
            border-color: var(--unicauca-primary);
        }

        .navigation-header h2 {
            color: var(--unicauca-blanco); /* Asegura que el texto h2 sea blanco */
            margin-bottom: 0; /* Elimina margen inferior por defecto de h2 */
        }
        /* Ajuste para el color del departamento si tiene otro color, pero dentro del encabezado azul */
        .navigation-header .text-muted-white {
            color: rgba(255, 255, 255, 0.7) !important; /* Gris claro para el slash y nombre facultad */
        }
         .puntos-no-actualizados {
    background-color: #FFF3CD; /* Un tono amarillo/naranja suave para indicar alerta */
    color: #856404; /* Color de texto más oscuro para contraste */
    font-weight: bold; /* Opcional: para que el texto resalte */
}
         /* Estilos para el botón "Agregar Profesor" - Naranja Unicaucano */
.btn-agregar-profesor {
    background-color: #FF6600; /* Un naranja vibrante y fuerte */
    border-color: #E65C00;     /* Un borde ligeramente más oscuro */
    color: #FFFFFF;            /* Texto blanco para un excelente contraste */
    
    /* Para asegurar el "alto super mínimo": */
    /* Bootstrap's btn-sm ya aplica un padding reducido. Si necesitas que sea
       aún más compacto, puedes descomentar y ajustar la siguiente línea: */
    /* padding: .15rem .4rem; /* Ejemplo de padding aún más pequeño */
}

.btn-agregar-profesor:hover {
    background-color: #E65C00; /* Naranja ligeramente más oscuro al pasar el ratón */
    border-color: #CC5200;
    color: #FFFFFF;
}

.btn-agregar-profesor:active {
    background-color: #CC5200; /* Naranja más oscuro al hacer clic */
    border-color: #B34700;
    color: #FFFFFF;
}

/* Ajuste para el ícono para asegurar "alto super mínimo" */
.btn-agregar-profesor .fas {
    /* Asegura que la altura de línea del ícono no genere espacio vertical extra. */
    /* '1' hace que la altura de línea sea igual al font-size del ícono. */
    line-height: 1; 
    
    /* Si el ícono aún se ve que agranda el botón, podrías intentar: */
    /* font-size: 0.9em; /* Para reducir ligeramente el tamaño del ícono relativo al texto */
}
         /* Mantener altura mínima consistente */
.estado-container {
    min-height: 38px; /* Altura de un input estándar */
    padding: 5px 0; /* Espaciado vertical mínimo */
}

/* Ajustes específicos para el botón */
.btn-agregar-profesor {
    padding-top: 0.15rem !important;
    padding-bottom: 0.15rem !important;
    line-height: 1.2;
}

.btn-agregar-profesor .fas {
    font-size: 0.8em;
    vertical-align: middle;
    margin-top: -2px;
}

/* Alinear verticalmente el texto del botón */
.btn-agregar-profesor span {
    display: inline-block;
    vertical-align: middle;
}
         /* --- ESTILOS PARA ENCABEZADOS DE TABLAS --- */

/* Contenedor principal del título de vinculación */
.estado-container {
    background: linear-gradient(135deg, #005c97, #003366);
    border-left: 5px solid #ffcc00;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Texto del título */
.estado-container h5 {
    font-weight: 600;
    font-size: 1.05rem;
    margin: 0;
    color: white;
}

/* Texto resaltado (tipo de docente) */
.estado-container h5 strong {
    color: #ffcc00;
}

/* Badge de semanas */
.estado-container .badge-semanas {
    background-color: white;
    color: #003366;
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    margin-left: 0.5rem;
    font-weight: 600;
}

/* Botón de agregar profesor */
.btn-agregar-profesor {
    background-color: #ff6600;
    border: none;
    color: white;
    padding: 0.25rem 0.75rem;
    font-size: 0.8rem;
    border-radius: 0.3rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.btn-agregar-profesor:hover {
    background-color: #e65c00;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-agregar-profesor:active {
    background-color: #cc5200;
    transform: translateY(0);
}

/* Icono del botón */
.btn-agregar-profesor .fas {
    font-size: 0.8em;
}

/* --- ESTILOS PARA ENCABEZADO SUPERIOR (Facultad/Departamento) --- */
.navigation-header {
    background: linear-gradient(135deg, #005c97, #003366);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #ffcc00;
}

.navigation-header h2 {
    font-weight: 600;
    margin: 0;
    color: white;
}

/* Botón de regresar */
.btn-back {
    background-color: #ffcc00;
    color: #003366;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.3rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-back:hover {
    background-color: #e6b800;
    color: #002b4d;
    text-decoration: none;
}

/* Texto secundario */
.text-muted-white {
    color: rgba(255, 255, 255, 0.7) !important;
}

/* Botón de gráficos */
.btn-graficos {
    background-color: #696FC7;
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.3rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-graficos:hover {
    background-color: #5a5fb3;
    color: white;
}

/* --- ESTILOS PARA ENCABEZADOS DE PERIODO --- */
.grid-header h4 {
    font-weight: 600;
    color: #003366;
    padding: 0.75rem 1rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    border-left: 4px solid #005c97;
    margin-bottom: 1rem;
}

/* --- RESPONSIVE --- */
@media (max-width: 768px) {
    .navigation-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .estado-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .btn-agregar-profesor {
        width: 100%;
        justify-content: center;
    }
}
     /* --- Nuevas variables para colores de estado (si no las tienes) --- */
:root {
    --unicauca-azul: #0066cc;
    --unicauca-azul-oscuro: #004080;
    --unicauca-gris-medio: #6c757d; /* Usado para bordes y texto secundario */
    --unicauca-gris-claro-bg: #f8f9fa; /* Fondo degradado 1 */
    --unicauca-gris-mas-claro-bg: #e9ecef; /* Fondo degradado 2 */
    --unicauca-verde-exito: #28a745; /* Color para estado "Abierto" */
    --unicauca-rojo-peligro: #dc3545; /* Color para estado "Cerrado" */
    --unicauca-azul-vinculacion: #005c97; /* Color específico para el tipo de vinculación */
    --unicauca-amarillo-boton: #FF6600; /* Naranja del botón */
    --unicauca-amarillo-boton-hover: #E65C00;
    --unicauca-negro: #111827; /* Para texto principal */
    --unicauca-blanco: #FFFFFF;
}

/* Contenedor general para el encabezado del período (revisión y anterior) */
.periodo-info-container {
    background: linear-gradient(135deg, var(--unicauca-gris-claro-bg), var(--unicauca-gris-mas-claro-bg));
    border-radius: 8px; /* Redondeo general para el contenedor */
    padding: 15px 20px; /* Más padding para contenido */
    margin-bottom: 20px; /* Espacio debajo del contenedor */
    display: flex;
    justify-content: space-between; /* Para espaciar el título y el botón */
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08); /* Sombra sutil */
    flex-wrap: wrap; /* Para responsividad */
    gap: 15px; /* Espacio entre elementos flexibles */
}

/* Borde izquierdo específico para cada período */
.periodo-actual-box {
    border-left: 4px solid var(--unicauca-azul); /* Borde azul para período actual */
}

.periodo-anterior-box {
    border-left: 4px solid var(--unicauca-gris-medio); /* Borde gris para período anterior */
}

/* Estilo para el título H5 dentro del contenedor */
.periodo-title-h5 {
    font-size: 1.15rem; /* Tamaño de fuente ligeramente más grande */
    margin: 0;
    display: flex;
    align-items: center;
    flex-wrap: wrap; /* Permite que los elementos se envuelvan */
    gap: 8px; /* Espacio entre los elementos del título */
    color: var(--unicauca-negro); /* Color de texto general para el título */
}

/* Iconos dentro del título */
.periodo-title-h5 .fas {
    font-size: 1.3em; /* Tamaño del icono */
    color: var(--unicauca-azul); /* Icono azul para el periodo actual */
}

.periodo-anterior-box .periodo-title-h5 .fas {
    color: var(--unicauca-gris-medio); /* Icono gris para el periodo anterior */
}

/* Estilos para las partes del texto dentro del título */
.periodo-label,
.vinculacion-label {
    font-weight: 600; /* Más negrita para las etiquetas */
    color: #343a40; /* Color oscuro para las etiquetas */
}

.periodo-value {
    font-weight: 700; /* Más negrita para el valor del período */
    color: var(--unicauca-azul-oscuro); /* Azul oscuro para el valor del período */
}

.vinculacion-type {
    font-weight: 700;
    color: var(--unicauca-azul-vinculacion); /* Azul específico de vinculación */
}

/* Separador */
.periodo-separator {
    color: var(--unicauca-gris-medio);
    margin: 0 0.5rem; /* Ajuste el margen si es necesario */
}

/* Badge para semanas (se aplica a .semanas-badge, no a .badge-secondary directamente) */
.semanas-badge {
    background-color: var(--unicauca-gris-medio); /* Fondo gris */
    color: var(--unicauca-blanco);
    padding: 0.25em 0.6em;
    font-size: 0.85em;
    font-weight: 600;
    border-radius: 10rem; /* Muy redondeado */
    white-space: nowrap; /* Evita que el badge se rompa en varias líneas */
}

/* Estilos para el estado "Abierto" y "Cerrado" */
.estado {
    font-weight: 700;
    padding: 0.2em 0.5em; /* Un poco de padding para que se vea como un tag */
    border-radius: 4px;
    white-space: nowrap;
}

.estado-abierto {
    color: var(--unicauca-verde-exito);
    background-color: rgba(40, 167, 69, 0.1); /* Fondo muy suave verde */
}

.estado-cerrado {
    color: var(--unicauca-rojo-peligro);
    background-color: rgba(220, 53, 69, 0.1); /* Fondo muy suave rojo */
}

/* Botón Agregar Profesor */
.btn-agregar-profesor {
    background-color: var(--unicauca-amarillo-boton);
    border: none;
    color: var(--unicauca-blanco);
    padding: 8px 15px; /* Ajuste padding */
    font-size: 0.9em;
    border-radius: 5px;
    transition: all 0.3s ease;
    font-weight: 600;
    display: flex; /* Para alinear icono y texto */
    align-items: center;
    gap: 5px; /* Espacio entre icono y texto */
}

.btn-agregar-profesor:hover {
    background-color: var(--unicauca-amarillo-boton-hover);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    color: var(--unicauca-blanco); /* Asegurar color de texto blanco en hover */
}

.btn-agregar-profesor .fas {
    font-size: 1em;
    vertical-align: middle;
    margin-top: -1px; /* Ajuste fino de alineación */
}

/* Responsividad para títulos largos */
@media (max-width: 768px) {
    .periodo-title-h5 {
        font-size: 1rem; /* Reducir tamaño de fuente en móviles */
        flex-direction: column; /* Apilar los elementos en pantallas pequeñas */
        align-items: flex-start; /* Alinear a la izquierda */
        gap: 5px;
    }
    .periodo-title-h5 .fas {
        margin-right: 5px; /* Pequeño margen para el icono */
    }
    .periodo-separator {
        display: none; /* Ocultar el separador vertical en móviles si se apilan */
    }
    .btn-agregar-profesor {
        width: 100%; /* Botón a ancho completo */
        justify-content: center; /* Centrar texto y icono */
        margin-top: 10px;
    }
}

/* --- ESTILOS ORIGINALES DE TU PROPORCIONADO, AHORA AJUSTADOS O ELIMINADOS SI SE SUPERPONEN --- */
/* Estas reglas se vuelven redundantes o menos específicas si usas las nuevas clases más arriba */
/*
.periodo-anterior-container {
    padding: 0.75rem 1rem; // Ya cubierto por .periodo-info-container
    border-radius: 0.5rem; // Ya cubierto por .periodo-info-container
    margin-bottom: 1rem; // Ya cubierto por .periodo-info-container
    display: flex; // Ya cubierto por .periodo-info-container
    align-items: center; // Ya cubierto por .periodo-info-container
}

.periodo-anterior-title {
    color: #495057; // Ya cubierto por .periodo-title-h5
    font-size: 1.05rem; // Ya cubierto por .periodo-title-h5
    margin: 0; // Ya cubierto por .periodo-title-h5
    display: flex; // Ya cubierto por .periodo-title-h5
    align-items: center; // Ya cubierto por .periodo-title-h5
    flex-wrap: wrap; // Ya cubierto por .periodo-title-h5
    gap: 0.5rem; // Ya cubierto por .periodo-title-h5
}

.badge-secondary { // Esta clase sigue siendo válida si la usas en otros lugares, pero para el badge de semanas, usa .semanas-badge
    background-color: #6c757d; // Cubierto por .semanas-badge
    color: white; // Cubierto por .semanas-badge
    padding: 0.25em 0.6em; // Cubierto por .semanas-badge
    font-size: 0.85em; // Cubierto por .semanas-badge
    font-weight: 600; // Cubierto por .semanas-badge
    border-radius: 10rem; // Cubierto por .semanas-badge
}
*/
         .card-plazo {
    max-width: 1800px;/*ece el ancho máximo para el contenedor de tarjetas */
    margin: 0 auto;    /* Centra el contenedor de tarjetas */
    gap: 20px; /* Mantén el espacio entre las tarjetas */
    margin-bottom: 30px;
    flex-wrap: wrap;
    width: 100%; /* Asegura que ocupe todo el ancho disponible hasta el max-width */
}
    </style>
<div class="card-plazo mb-4" >
    <div class="navigation-header">
     <div>
                <?php if (isset($_POST['envia']) && $_POST['envia'] === 'consulta_todo_depto'): ?>
                    <form action="<?= $archivo_regreso ?>" method="post" style="display:inline;">
                        <input type="hidden" name="anio_semestre" value="<?= htmlspecialchars($anio_semestre) ?>">
                        <input type="hidden" name="anio_semestre_anterior" value="<?= htmlspecialchars($periodo_anterior) ?>">
                        <input type="hidden" name="departamento_id" value="<?= htmlspecialchars($departamento_id) ?>">
                        <button type="submit">Volver</button>
                    </form>
                <?php else: ?>
                    <a href="<?= $archivo_regreso ?>?anio_semestre=<?= urlencode($anio_semestre) ?>&anio_semestre_anterior=<?= urlencode($periodo_anterior) ?>&departamento_id=<?= urlencode($departamento_id) ?>" class="btn-back">
                        <i class="fas fa-arrow-left me-2"></i> Regresar
                    </a>
                <?php endif; ?>
            </div>


        <div class="d-flex align-items-baseline gap-2">
    <h2 class="mb-0" style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        Fac. <?= mb_strimwidth(obtenerNombreFacultad($departamento_id), 0, 65, '...') ?>
    </h2>
    <span class="text-muted-white">/</span>
    <h2 class="mb-0" style="color: var(--unicauca-blanco); font-weight: 500;">
        <?= mb_strimwidth(obtenerNombreDepartamento($_POST['departamento_id']), 0, 65, '...') ?>
        <?php if (!empty($anio_semestre) && !empty($periodo_anterior)): ?>
            <span style="font-size: 1.2em; font-weight: 400; color: var(--unicauca-blanco-claro);">
                <?= htmlspecialchars($anio_semestre, ENT_QUOTES, 'UTF-8') ?> vs <?= htmlspecialchars($periodo_anterior, ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>
    </h2>
</div>
        <div>
           <a href="#seccionGraficos"
   class="btn btn-sm d-flex align-items-center gap-1"
   style="background-color: #696FC7; border-color: #696FC7; color: white;"
   title="Ir a la sección de gráficos">
    <i class="fas fa-chart-bar"></i> Gráficos -Departamento
</a>
            </div>
    </div>

   <!-- NUEVA ESTRUCTURA CON GRID PARA ALINEACIÓN -->
    <div class="grid-container" id="contentToToggle">
       <?php
/*
    echo '<div class="grid-col">
            <div class="grid-header">
                <h4 class=""><strong>Periodo en revisión: ' . htmlspecialchars($_POST['anio_semestre']) . '.</strong></h4>
            </div>
        </div>
        <div class="grid-col">
            <div class="grid-header">
                <h4 class=""><strong>Periodo anterior: ' . htmlspecialchars($periodo_anterior) . '</strong></h4>
            </div>
        </div>';
*/
?>

        
        <?php
        $facultad_id = obtenerIdFacultad($departamento_id);
        require 'cn.php';
        
        // Consulta SQL para obtener los tipos de docentes
        $consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                          FROM solicitudes  where solicitudes.estado <> 'an' OR solicitudes.estado IS NULL;";
        $resultadotipo = $con->query($consulta_tipo);
        
        if (!$resultadotipo) {
            die('Error en la consulta: ' . $con->error);
        }
        
        $todosCerrados = true;
        $obtenerDeptoCerrado = obtenerDeptoCerrado($departamento_id,$anio_semestre);
        $totalItems = 0;
        $contadorHV = 0;
        $contadorVerdes = 0;
        $contadorVerdesOc = 0;
        $contadorVerdesCa = 0;
        
        // Variables globales para ambos periodos
        $cedulasPeriodoAnteriorGlobal = [];
        $sqlPrevGlobal = "SELECT cedula, tipo_docente FROM solicitudes
                          WHERE facultad_id = '$facultad_id'
                          AND departamento_id = '$departamento_id'
                          AND anio_semestre = '$periodo_anterior'
                          AND (estado <> 'an' OR estado IS NULL)";
        $resultPrevGlobal = $conn->query($sqlPrevGlobal);
        if ($resultPrevGlobal) {
            while ($rowPrevGlobal = $resultPrevGlobal->fetch_assoc()) {
                $cedulasPeriodoAnteriorGlobal[$rowPrevGlobal['cedula']] = $rowPrevGlobal['tipo_docente'];
            }
        }
        
        $total_consolidado = 0;
        $contadorVerdes = 0;
        $contadorVerdesOc = 0;
        $contadorVerdesCa = 0;
        $totalProfesoresOcasional = 0;
        $totalProfesoresCatedra = 0;
        $totalhorasOcasional = 0;
        $totalhorasCatedra = 0;
        $totalProyectadoOcasional = 0;
        $totalProyectadoCatedra = 0;
        
        // Variables para periodo anterior
        $cedulasGlobalesPeriodoActual = [];
        $sqlCedulasGlobalesActuales = "SELECT cedula FROM solicitudes  
                              WHERE facultad_id = '$facultad_id' 
                              AND departamento_id = '$departamento_id' 
                              AND anio_semestre = '$anio_semestre'
                              AND (estado <> 'an' OR estado IS NULL)";
        $resultGlobales = $conn->query($sqlCedulasGlobalesActuales);
        while ($rowCedula = $resultGlobales->fetch_assoc()) {
            $cedulasGlobalesPeriodoActual[] = $rowCedula['cedula'];
        }       
        
        $totalProfesoresOcasionalAnterior = 0;
        $totalProfesoresCatedraAnterior = 0;
        $totalProyectadoOcasionalAnterior = 0;
        $totalProyectadoCatedraAnterior = 0;          
        $totalhorasOcasionalAnterior = 0;
        $totalhorasCatedraAnterior = 0;
       
                
                
                $total_cosolidado_ant = 0;
        $contadorRojos = 0;
        $contadorRojosOc = 0;
        $contadorRojosCa = 0;
        
        // --- LOOP THROUGH EACH DOCENTE TYPE ---
        while ($rowtipo = $resultadotipo->fetch_assoc()) {
            $tipo_docente = $rowtipo['tipo_d'];
            
            echo '<div class="grid-row">'; // Inicio de fila de grid para este tipo
            
            // ================= COLUMNA PERIODO ACTUAL =================
            echo '<div class="grid-col">';
            
            // --- Data specific to the CURRENT $tipo_docente for THIS loop iteration ---
            $cedulasPeriodoActualPorTipo = [];
            $sqlCedulasActualesPorTipo = "SELECT cedula FROM solicitudes
                                          WHERE facultad_id = '$facultad_id'
                                          AND departamento_id = '$departamento_id'
                                          AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_docente'
                                          AND (estado <> 'an' OR estado IS NULL)";
            $resultCedulasActualesPorTipo = $conn->query($sqlCedulasActualesPorTipo);
            if ($resultCedulasActualesPorTipo) {
                while ($rowCedula = $resultCedulasActualesPorTipo->fetch_assoc()) {
                    $cedulasPeriodoActualPorTipo[] = $rowCedula['cedula'];
                }
            }
            
            $cedulasPeriodoAnteriorPorTipoActual = [];
            $sqlCedulasAnterioresPorTipo = "SELECT cedula FROM solicitudes
                                            WHERE facultad_id = '$facultad_id'
                                            AND departamento_id = '$departamento_id'
                                            AND anio_semestre = '$periodo_anterior' AND tipo_docente = '$tipo_docente'
                                            AND (estado <> 'an' OR estado IS NULL)";
            $resultCedulasAnterioresPorTipo = $conn->query($sqlCedulasAnterioresPorTipo);
            if ($resultCedulasAnterioresPorTipo) {
                while ($rowCedula = $resultCedulasAnterioresPorTipo->fetch_assoc()) {
                    $cedulasPeriodoAnteriorPorTipoActual[] = $rowCedula['cedula'];
                }
            }
         $periodo_ant_real= obtenerPeriodoAnterior($anio_semestre);   
           // --- MAIN QUERY TO GET PROFESSORS FOR THE CURRENT TABLE ---
$sql = "SELECT
    s_actual.*,
    facultad.nombre_fac_minb AS nombre_facultad,
    deparmanentos.depto_nom_propio AS nombre_departamento,
    s_actual.puntos AS puntos_periodo_actual,
    COALESCE(
        s_anterior.puntos,
        CASE s_actual.tipo_docente
            WHEN 'Ocasional' THEN 380
            WHEN 'Catedra' THEN 3.5
            ELSE NULL -- O un valor por defecto si existen otros tipos de docente
        END
    ) AS puntos_periodo_anterior, -- Puntos del periodo anterior con valor por defecto
    s_anterior.tipo_docente AS tipo_docente_periodo_anterior
FROM
    solicitudes AS s_actual
JOIN
    deparmanentos ON (deparmanentos.PK_DEPTO = s_actual.departamento_id)
JOIN
    facultad ON (facultad.PK_FAC = s_actual.facultad_id)
LEFT JOIN
    solicitudes AS s_anterior ON (
        s_anterior.cedula = s_actual.cedula
        AND s_anterior.departamento_id = s_actual.departamento_id
        AND s_anterior.facultad_id = s_actual.facultad_id
        AND s_anterior.anio_semestre = '$periodo_ant_real'
                AND s_anterior.tipo_docente = s_actual.tipo_docente -- ¡NUEVA CONDICIÓN!

        AND (s_anterior.estado <> 'an' OR s_anterior.estado IS NULL)
    )
WHERE
    s_actual.facultad_id = '$facultad_id'
    AND s_actual.departamento_id = '$departamento_id'
    AND s_actual.anio_semestre = '$anio_semestre'
    AND s_actual.tipo_docente = '$tipo_docente' -- ¡CORREGIDO: Usando la variable PHP $tipo_docente!
    AND (s_actual.estado <> 'an' OR s_actual.estado IS NULL)
ORDER BY
    s_actual.nombre ASC;";
            
            $result = $conn->query($sql);
          // --- HTML OUTPUT FOR THE SECTION HEADER AND BUTTON ---
echo "<div class='box-gray'>";
// ================= COLUMNA PERIODO ACTUAL =================
echo '<div class="grid-col">';
echo "<div class='periodo-info-container periodo-actual-box'>"; // Usamos el contenedor unificado y el borde específico
echo "<h5 class='periodo-title-h5'>"; // Nueva clase para el h5
echo "<i class='fas fa-calendar-alt'></i>"; // Icono para el período actual (ajustado en CSS)
echo "<span class='periodo-label'>Período:</span> ";
echo "<span class='periodo-value'>" . htmlspecialchars($_POST['anio_semestre']) . "</span>";
echo "<span class='periodo-separator'>|</span>"; // Separador
echo "<span class='vinculacion-label'>Vinculación:</span> ";
echo "<span class='vinculacion-type'>" . $tipo_docente . "</span> (";

if ($tipo_docente == 'Catedra') {
    $estadoDepto = obtenerCierreDeptoCatedra($departamento_id, $aniose);
    $estadoClass = strtolower($estadoDepto) == 'abierto' ? 'estado-abierto' : 'estado-cerrado';
    echo "<span class='estado " . $estadoClass . "'>" . ucfirst(strtolower($estadoDepto)) . "</span>) - ";
    echo "<span class='semanas-badge'>" . $semanas_cat . " semanas</span>";
} else {
    $estadoDepto = obtenerCierreDeptoOcasional($departamento_id, $aniose); // Usar función correcta para Ocasional si existe
    $estadoClass = strtolower($estadoDepto) == 'abierto' ? 'estado-abierto' : 'estado-cerrado';
    echo "<span class='estado " . $estadoClass . "'>" . ucfirst(strtolower($estadoDepto)) . "</span>) - ";
    echo "<span class='semanas-badge'>" . $semanas_ocas . " semanas</span>";
}

echo "</h5>";

if ($tipo_usuario == 1) {
    echo "
    <div class='btn-container'>"; // Envuelve el botón en un div para mejor control flexbox
    echo "
        <form action='nuevo_registro_admin.php' method='GET' class='mb-0'>
            <input type='hidden' name='facultad_id' value='" . htmlspecialchars($facultad_id) . "'>
            <input type='hidden' name='departamento_id' value='" . htmlspecialchars($departamento_id) . "'>
            <input type='hidden' name='anio_semestre' value='" . htmlspecialchars($anio_semestre) . "'>
            <input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>
            <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
            <button type='submit' class='btn-agregar-profesor' title='Agregar Profesor'>
                <i class='fas fa-user-plus'></i> <span>Agregar</span>
            </button>
        </form>";
    echo "</div>"; // Cierre btn-container
}
echo "</div>"; // Cierre periodo-info-container
echo '</div>'; // Cierre grid-col
            
            // Obtener el conteo de profesores para este tipo_docente
            $sqlCount = "SELECT 
                COUNT(*) AS count,
                SUM(
                    CASE 
                        WHEN tipo_docente = 'Ocasional' THEN 
                            CASE 
                                WHEN tipo_dedicacion = 'TC' OR tipo_dedicacion_r = 'TC' THEN 40
                                WHEN tipo_dedicacion = 'MT' OR tipo_dedicacion_r = 'MT' THEN 20
                                ELSE 0
                            END
                        WHEN tipo_docente = 'Catedra' THEN IFNULL(horas, 0) + IFNULL(horas_r, 0)
                        ELSE 0
                    END
                ) AS horas
            FROM solicitudes
            WHERE 
                facultad_id = '$facultad_id' AND 
                departamento_id = '$departamento_id' AND 
                anio_semestre = '$anio_semestre' AND 
                tipo_docente = '$tipo_docente' AND 
                (estado <> 'an' OR estado IS NULL)
";
           $resultCount = $conn->query($sqlCount);
$row = $resultCount->fetch_assoc();

$count = $row['count'];
$horas = $row['horas']; // este ya viene calculado en SQL

if ($tipo_docente == 'Ocasional') {
    $totalProfesoresOcasional += $count;
    $totalhorasOcasional += $horas;

} elseif ($tipo_docente == 'Catedra') {
    $totalProfesoresCatedra += $count;
    $totalhorasCatedra += $horas;
}
                // --- TABLE STRUCTURE AND HEADERS ---
                if ($result && $result->num_rows > 0) {
                    echo "<table border='1'>
                    <thead>
                        <tr>
                            <th rowspan='2'>Ítem</th>
                            <th rowspan='2'>Cédula</th>
                            <th rowspan='2'>Nombre</th>";

                    if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
                        echo "<th colspan='2'>Dedicación</th>";
                    }
                    if ($tipo_usuario == 1) {
                        echo "<th colspan='2'>Acciones</th>";
                    }
                    echo "<th rowspan='2'>Puntos</th>";
                    echo "<th rowspan='2'>Proyec</th>";
                    echo "</tr>
                    <tr>";

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
                        echo "
                            <th>Supr</th>
                            <th>Edit</th>
                        ";
                    }
                    echo "</tr>
                    </thead>
                    <tbody>"; // Added tbody for better HTML structure

                    $item = 1; // Initialize item counter for this table
                    $todosLosRegistrosValidos = true; // Assuming this flag's purpose remains
                    $datos_acta = obtener_acta($anio_semestre, $departamento_id);
                    $num_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['acta_periodo']) : "";
                    $fecha_acta = ($datos_acta !== 0) ? htmlspecialchars($datos_acta['fecha_acta']) : "";
                    $total_proyect = 0; // Initialize variable for accumulating total
                $contadorCambioAOcasional = 0; // Contará los profesores que eran Cátedra y ahora son Ocasional
                        $contadorCambioACatedra = 0;   // Contará los profesores que eran Ocasional y ahora son Cátedra

                    // --- LOOP TO DISPLAY EACH PROFESSOR'S ROW ---
                    while ($row = $result->fetch_assoc()) {
                        $cedula = $row['cedula'];
                        $claseFila = '';    // Reset for each row
                        $claseTexto = '';   // Reset for each row
                        $tooltipText = '';  // Reset for each row

                        // --- DETERMINE PROFESSOR'S STATUS FOR THIS ROW ---
                        $cambioTipo = false;
                        if (isset($cedulasPeriodoAnteriorGlobal[$cedula])) {
                            if ($cedulasPeriodoAnteriorGlobal[$cedula] !== $tipo_docente) {
                                $cambioTipo = true;
                            }
                        }

                        // 2. Is this professor "new" to this specific type of vinculación in the current period?
                        $esNueva = !in_array($cedula, $cedulasPeriodoAnteriorPorTipoActual);

                        // --- APPLY CSS CLASSES AND SET THE SINGLE TOOLTIP BASED ON PRIORITY ---
                        if ($cambioTipo) {
                            $claseFila = 'fondo-amarillo';
                            $claseTexto = 'cedula-nueva';
                            $tooltipText = 'Este profesor tuvo vinculación temporal diferente en el periodo anterior ('.$periodo_anterior.')';


                        } elseif ($esNueva) {
                            $claseTexto = 'cedula-nueva';
                            $tooltipText = 'Profesor nuevo para este periodo, no registrado en el periodo anterior ('.$periodo_anterior.')';

                            $contadorVerdes++;
                            if ($tipo_docente == 'Ocasional') {
                                $contadorVerdesOc++;
                            } else {
                                $contadorVerdesCa++;
                            }
                        }

                        $titleAttribute = '';
                        if (!empty($tooltipText)) {
                            $titleAttribute = "title=\"" . htmlspecialchars($tooltipText) . "\"";
                        }

                        // --- OUTPUT THE TABLE ROW AND ITS CELLS ---
                        echo "<tr class='$claseFila' $titleAttribute>";
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
                                <input type='hidden' name='anio_semestre_anterior' value='".htmlspecialchars($periodo_anterior)."'>
                                <input type='hidden' name='tipo_docente' value='".htmlspecialchars($tipo_docente)."'>
                                <input type='hidden' name='motivo_eliminacion' class='motivo-input' value=''>
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
                                    <input type='hidden' name='anio_semestre_anterior' value='" . htmlspecialchars($periodo_anterior) . "'>
                                    <input type='hidden' name='tipo_docente' value='" . htmlspecialchars($tipo_docente) . "'>
                                    <button type='submit' class='update-btn'><i class='fas fa-edit'></i></button>
                                </form></td>";
                        }   
                            // --- Lógica para mostrar Puntos con indicadores de actualización ---
        $valorPuntos = 0; // Inicializar para evitar posibles advertencias
        $puntosCellClass = ''; // Clase CSS para la celda de puntos
        $puntosTooltip = ''; // Tooltip específico para la celda de puntos

                        // Si $row["puntos"] (que corresponde a puntos_periodo_actual) está vacío o es cero,
                        // se usa $row["puntos_periodo_anterior"]. De lo contrario, se usa $row["puntos"].
    if (empty($row["puntos_periodo_actual"]) || $row["puntos_periodo_actual"] == 0) {
            $valorPuntos = $row["puntos_periodo_anterior"];
            $puntosCellClass = " puntos-no-actualizados"; // Clase para el color de alerta
            $puntosTooltip = "Puntos tomados del periodo anterior (" . htmlspecialchars($periodo_ant_real) . "). Pendientes de actualización.";

            // Incrementar el contador global si se usa el valor anterior
            // Asegúrate de que $contadorPuntosPendientes se inicializa a 0 antes del bucle
            if (isset($contadorPuntosPendientes)) { // Verificar si la variable existe
                $contadorPuntosPendientes++;
            }

        } else {
            $valorPuntos = $row["puntos_periodo_actual"];
            // Si quieres un tooltip incluso cuando está actualizado:
            $puntosTooltip = "Puntos actualizados para el periodo actual.";
            // $puntosCellClass permanece vacío, sin color especial
        }

        // Imprimir la celda de Puntos con su clase y tooltip
        echo "<td class='" . $puntosCellClass . "' title='" . htmlspecialchars($puntosTooltip) . "'>" . $valorPuntos . "</td>";

                        // Cálculos de proyección
                        if ($tipo_docente == "Catedra")  {

                    $asignacion_total= $valorPuntos*$valor_punto*($row["horas"]+$row["horas_r"])*$semanas_cat;
                    $asignacion_mes  = $valorPuntos*$valor_punto*($row["horas"] +$row["horas_r"])*4;
                    $prima_navidad = $asignacion_mes*3/12;
                    $indem_vacaciones = $asignacion_mes*$dias/360;
                    $indem_prima_vacaciones = $indem_vacaciones*2/3;
                    $cesantias =($asignacion_total + $prima_navidad)/12;
                    $total_devengos=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones+$cesantias;
                 //eps
                    if ($asignacion_mes < $smlv){
                    $valor_base = ($smlv * $dias / 30) * 8.5 / 100;
                } else {
                    $valor_base = round($asignacion_total * 8.5 / 100, 0);
                }

                // Redondear al múltiplo de 100 más cercano
                $eps = round($valor_base, -2);

                        //pension

                // Cálculo principal
                if ($asignacion_mes < $smlv) {
                    $valor_base = (($smlv * $dias) / 30) * (12 / 100);
                } else {
                    $valor_base = round($asignacion_total * (12 / 100), 0);
                }

                // Redondear al múltiplo de 100 más cercano
                $afp = round($valor_base, -2);

                        //arp
                        $porcentaje = 0.522 / 100;

                // Lógica del cálculo
                if ($asignacion_mes < $smlv) {
                    $valor_base = (($smlv * $dias) / 30) * $porcentaje;
                } else {
                    $valor_base = round($asignacion_total * $porcentaje, 0);
                }

                // Redondeo al múltiplo de 100 más cercano
                $arl = round($valor_base, -2);

                        //comfaucaua
                // Porcentaje a aplicar
                $porcentaje = 4 / 100;

                // Cálculo condicional
                if ($asignacion_mes < $smlv) {
                    $valor_base = (($smlv * $dias) / 30) * $porcentaje;
                } else {
                    $valor_base = round($asignacion_total * $porcentaje, 0);
                }

                // Redondear al múltiplo de 100 más cercano
                $cajacomp = round($valor_base, -2);

                        // icbf
                $porcentaje = 3 / 100;

                // Cálculo condicional
                if ($asignacion_mes < $smlv) {
                    $valor_base = (($smlv * $dias) / 30) * $porcentaje;
                } else {
                    $valor_base = round($asignacion_total * $porcentaje, 0);
                }

                // Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
                $icbf = round($valor_base, -2);
                        $total_aportes= $eps +$afp+$arl+$cajacomp+$icbf;
                        $gran_total = $total_devengos+$total_aportes;

        }
       else {        
                 //calculo  si $tipo_docente <> "Catedra"

                    $horas = 0;
                    $mesesocas = intval($semanas_ocas / 4.33)-1; // 4.33 semanas ≈ 1 mes
                    // Asegurarse que los índices existen y son iguales a "MT" o "TC"
                    if (($row["tipo_dedicacion"] == "MT") || ($row["tipo_dedicacion_r"] == "MT")) {
                        $horas = 20;
                    } elseif (($row["tipo_dedicacion"] == "TC") || ($row["tipo_dedicacion_r"] == "TC")) {
                        $horas = 40;
                    }

                    // Calculo de la asignación mensual y total
    $asignacion_mes = round($valorPuntos * $valor_punto * ($horas / 40), 0);
                    $asignacion_total = $asignacion_mes * $dias_ocas / 30;


                    $prima_navidad = $asignacion_mes*$mesesocas/12;
                    $indem_vacaciones = $asignacion_mes*($dias_ocas)/360;
                    $indem_prima_vacaciones = $asignacion_mes*(2/3)*(($dias_ocas)/360);
                    $cesantias = round(($asignacion_total + $prima_navidad) / 12);
                    $total_empleado=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones;
                 //eps
                    $eps = round(($asignacion_total * 8.5) / 100);
                        //pension

                // Redondear al múltiplo de 100 más cercano
                 $afp = round(($asignacion_total * 12) / 100);


                // Redondeo al múltiplo de 100 más cercano
                $arl =round(($asignacion_total * 0.522) / 100,-2);

                        //comfaucaua

                // Redondear al múltiplo de 100 más cercano
                $cajacomp = round(($asignacion_total * 4) / 100,-2);

                        // icbf

                // Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
                        $icbf = round(($asignacion_total * 3) / 100,-2);
                            $total_entidades=$cesantias+ $eps +$afp+$arl+$cajacomp+$icbf;


                        $gran_total = $total_empleado+$total_entidades;

        }
       // Asignar valores condicionales si es de cátedra
    if ($tipo_docente == "Catedra") {
        $total_empleado_mostrar = $total_devengos;
        $total_entidades_mostrar = $total_aportes;
    } else {
        $total_empleado_mostrar = $total_empleado;
        $total_entidades_mostrar = $total_entidades;
    }

    $title =

        "Detalle salarial\n" .

            "mese ocasionaels: " .$dias_ocas . "\n" .

        "Asignación mensual: $" . number_format($asignacion_mes, 0, ',', '.') . "\n" .
        "Asignación total: $" . number_format($asignacion_total, 0, ',', '.') . "\n" .
        "Prima de Navidad: $" . number_format($prima_navidad, 0, ',', '.') . "\n" .
        "Indem. Vacaciones: $" . number_format($indem_vacaciones, 0, ',', '.') . "\n" .
        "Indem. Prima Vacaciones: $" . number_format($indem_prima_vacaciones, 0, ',', '.') . "\n" .
        "Total empleado: $" . number_format($total_empleado_mostrar, 0, ',', '.') . "\n\n" .
        "Cesantías: $" . number_format($cesantias, 0, ',', '.') . "\n" .

        "Aportes a entidades\n" .
        "EPS: $" . number_format($eps, 0, ',', '.') . "\n" .
        "Pensión: $" . number_format($afp, 0, ',', '.') . "\n" .
        "ARL: $" . number_format($arl, 0, ',', '.') . "\n" .
        "Caja Compensación: $" . number_format($cajacomp, 0, ',', '.') . "\n" .
        "ICBF: $" . number_format($icbf, 0, ',', '.') . "\n" .
        "Total entidades: $" . number_format($total_entidades_mostrar, 0, ',', '.') . "\n\n" .

        "GRAN TOTAL: $" . number_format($gran_total, 0, ',', '.');
    echo '<td  data-placement="right" title="' . htmlspecialchars($title, ENT_QUOTES) . '">
    $' . number_format($gran_total / 1000000, 2) . ' M</td>';

    $total_proyect += $gran_total;
        echo "</tr>";
        $item++;
    }

                    // Fila de subtotal
                    echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>";
                    echo "<td colspan='".($tipo_usuario == 1 ? ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ? 8 : 6) : ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ?  6: 4))."'>Subtotal</td>";
                    echo "<td>$".number_format($total_proyect/1000000, 2)." M</td>";
                    echo "</tr>";
                    echo "</table>";

                    // Acumular el total proyectado por tipo de nómina
                    if ($tipo_docente == 'Ocasional') {
                        $totalProyectadoOcasional += $total_proyect;
                    } elseif ($tipo_docente == 'Catedra') {
                        $totalProyectadoCatedra += $total_proyect;
                    }
                } else {
                    echo "<p style='text-align: center;'>No se encontraron resultados.</p>";
                }
    $total_consolidado += $total_proyect ?? 0;
                echo "</div>"; // Cierre de box-gray

                echo '</div>'; // Cierre de grid-col (periodo actual)

            // ================= COLUMNA PERIODO ANTERIOR =================
            echo '<div class="grid-col">';
            
            // --- MAIN QUERY TO GET PROFESSORS FOR THE PREVIOUS PERIOD ---
          $sql = "SELECT solicitudes.*, 
               facultad.nombre_fac_minb AS nombre_facultad, 
               deparmanentos.depto_nom_propio AS nombre_departamento 
        FROM solicitudes 
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
        JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
        WHERE facultad_id = '$facultad_id' 
          AND departamento_id = '$departamento_id' 
          AND anio_semestre = '$periodo_anterior' 
          AND tipo_docente = '$tipo_docente' 
          AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
        ORDER BY solicitudes.nombre ASC";

            
            $result = $conn->query($sql);
            
            // Si hay resultados para este tipo de docente en el periodo anterior, sumar al total
            if ($result) {
                $countAnterior = $result->num_rows;
                if ($tipo_docente == 'Ocasional') {
                    $totalProfesoresOcasionalAnterior += $countAnterior;
                } elseif ($tipo_docente == 'Catedra') {
                    $totalProfesoresCatedraAnterior += $countAnterior;
                }
            }
            // --- NEW QUERY TO CALCULATE HOURS FOR THE PREVIOUS PERIOD ---
// Necesitamos las mismas condiciones, pero ahora nos enfocamos en las columnas de horas/dedicación
$sql_horas = "SELECT tipo_docente, tipo_dedicacion, tipo_dedicacion_r, horas, horas_r 
              FROM solicitudes 
              WHERE facultad_id = '$facultad_id' 
                AND departamento_id = '$departamento_id' 
                AND anio_semestre = '$periodo_anterior' 
                AND tipo_docente = '$tipo_docente' 
                AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

$result_horas = $conn->query($sql_horas);

// Si hay resultados para este tipo de docente en el periodo anterior, sumar las horas
if ($result_horas) {
    $totalHorasPeriodoAnterior = 0; // Inicializar acumulador de horas para esta consulta
    
    while ($row_horas = $result_horas->fetch_assoc()) {
        if ($tipo_docente == 'Ocasional') {
            // Lógica para Ocasional
            if ($row_horas['tipo_dedicacion'] == 'MT' || $row_horas['tipo_dedicacion_r'] == 'MT') {
                $totalHorasPeriodoAnterior += 20;
            } elseif ($row_horas['tipo_dedicacion'] == 'TC' || $row_horas['tipo_dedicacion_r'] == 'TC') {
                $totalHorasPeriodoAnterior += 40;
            }
        } elseif ($tipo_docente == 'Catedra') {
            // Lógica para Cátedra: horas_r + horas
            // Aseguramos que los valores sean numéricos antes de sumar
            $horas_calculadas = (int)$row_horas['horas_r'] + (int)$row_horas['horas'];
            $totalHorasPeriodoAnterior += $horas_calculadas;
        }
    }
    
    // Asignar los totales de horas según el tipo de docente
    if ($tipo_docente == 'Ocasional') {
        $totalhorasOcasionalAnterior += $totalHorasPeriodoAnterior;
    } elseif ($tipo_docente == 'Catedra') {
        $totalhorasCatedraAnterior += $totalHorasPeriodoAnterior;
    }
    
    // Liberar el resultado de la consulta de horas
    $result_horas->free();
}
            echo "<div class='box-gray'>";
// ================= COLUMNA PERIODO ANTERIOR =================
echo '<div class="grid-col">';
echo "<div class='periodo-info-container periodo-anterior-box'>"; // Use unified container and specific border class
echo "<h5 class='periodo-title-h5'>"; // Use the new general title class
echo "<i class='fas fa-history'></i>"; // Icon for previous period (style controlled by CSS)
echo "<span class='periodo-label'>Período anterior:</span> "; // Consistent label
echo "<span class='periodo-value'>" . htmlspecialchars($periodo_anterior) . "</span>"; // Period value
echo "<span class='periodo-separator'>|</span>"; // Separator
echo "<span class='vinculacion-label'>Vinculación:</span> "; // Consistent label
echo "<span class='vinculacion-type'>" . $tipo_docente . "</span> - "; // Type of docente
echo "<span class='semanas-badge'>" . (($tipo_docente === "Ocasional") ? $semanas_ocasant : $semanas_catant) . " semanas</span>"; // Weeks badge (using new class)
echo "</h5>";
// No button in this section
echo "</div>"; // Close periodo-info-container
echo '</div>'; // Close grid-col
            
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
                echo "<th rowspan='2'>Proyec</th>";
                echo "</tr>";
            
                if ($tipo_docente == "Ocasional") {
                    echo "<tr><th>Pop</th><th>Reg</th></tr>";
                } elseif ($tipo_docente == "Catedra") {
                    echo "<tr><th>Pop</th><th>Reg</th></tr>";
                }
                
                $item = 1;
                $total_proyectant = 0;

                while ($row = $result->fetch_assoc()) {
                    $cedula = $row['cedula'];
                    $cedulaEliminada = !in_array($cedula, $cedulasPeriodoActualPorTipo);
                    $cedulaEstaEnOtroTipo = $cedulaEliminada && in_array($cedula, $cedulasGlobalesPeriodoActual);
                    $tooltipText = '';
                    
                    if ($cedulaEstaEnOtroTipo) {
                        $claseRoja = 'cedula-en-otro-tipo';
                        $tooltipText = 'Cambio de vinculación para el periodo actual ('.$anio_semestre.')';
                                $huboCambioVinculacion = true; // <-- ACTIVA LA BANDERA SI SE ENCUENTRA UN CAMBIO DE VINCULACIÓN

                    } elseif ($cedulaEliminada) {
                        $claseRoja = 'cedula-eliminada';
                        $tooltipText = 'Profesor no vinculado para el periodo actual ('.$anio_semestre.')';
                    } else {
                        $claseRoja = '';
                    }
                    
                    if ($cedulaEliminada) {
                        $contadorRojos++;
                        if ($tipo_docente == 'Ocasional') {
                            $contadorRojosOc++;
                        } else {
                            $contadorRojosCa++;
                        }
                    }
                    
                    echo "<tr>";
                    echo "<td>" . $item . "</td>";
                    $titleAttribute = !empty($tooltipText) ? "title='" . htmlspecialchars($tooltipText) . "'" : '';
                    echo "<td style='text-align: left;' class='$claseRoja' $titleAttribute>" . htmlspecialchars($cedula) . "</td>";
                    echo "<td style='text-align: left;' class='$claseRoja' $titleAttribute>" . htmlspecialchars($row["nombre"]) . "</td>";
            
                    if ($tipo_docente == "Ocasional") {
                        echo "<td>" . htmlspecialchars($row["tipo_dedicacion"]) . "</td>
                              <td>" . htmlspecialchars($row["tipo_dedicacion_r"]) . "</td>";
                    }
                    if ($tipo_docente == "Catedra") {
                        $horas = $row["horas"] == 0 ? "" : htmlspecialchars($row["horas"]);
                        $horas_r = $row["horas_r"] == 0 ? "" : htmlspecialchars($row["horas_r"]);
                        echo "<td>$horas</td><td>$horas_r</td>";
                    }
            
                    echo "<td>" . $row["puntos"] . "</td>";
                    
                    // Cálculos de proyección para periodo anterior
                   if ($tipo_docente == "Catedra") {   
    //calculo catedra si $tipo_docente == "Catedra"
   $asignacion_total= $row["puntos"]*$valor_puntoant *($row["horas"] + $row["horas_r"])*$semanas_catant;
     
         $mesescat = intval($semanas_catant / 4.33); // 4.33 semanas ≈ 1 mes

    $asignacion_mes=$row["puntos"]*$valor_puntoant *($row["horas"] +$row["horas_r"])*4;
    $prima_navidad = $asignacion_mes*$mesescat/12;
    $indem_vacaciones = $asignacion_mes*$diasant/360;
    $indem_prima_vacaciones = $indem_vacaciones*2/3;
    $cesantias =($asignacion_total + $prima_navidad)/12;
    $total_devengos=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones+$cesantias;
 //eps
    if ($asignacion_mes < $smlvant){
    $valor_base = ($smlvant * $diasant / 30) * 8.5 / 100;
} else {
    $valor_base = round($asignacion_total * 8.5 / 100, 0);
}

// Redondear al múltiplo de 100 más cercano
$eps = round($valor_base, -2);
        
        //pension

// Cálculo principal
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * (12 / 100);
} else {
    $valor_base = round($asignacion_total * (12 / 100), 0);
}

// Redondear al múltiplo de 100 más cercano
$afp = round($valor_base, -2);
        
        //arp
        $porcentaje = 0.522 / 100;

// Lógica del cálculo
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * $porcentaje;
} else {
    $valor_base = round($asignacion_total * $porcentaje, 0);
}

// Redondeo al múltiplo de 100 más cercano
$arl = round($valor_base, -2);
    
        //comfaucaua
// Porcentaje a aplicar
$porcentaje = 4 / 100;

// Cálculo condicional
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * $porcentaje;
} else {
    $valor_base = round($asignacion_total * $porcentaje, 0);
}

// Redondear al múltiplo de 100 más cercano
$cajacomp = round($valor_base, -2);
        
        // icbf
$porcentaje = 3 / 100;

// Cálculo condicional
if ($asignacion_mes < $smlvant) {
    $valor_base = (($smlvant * $diasant) / 30) * $porcentaje;
} else {
    $valor_base = round($asignacion_total * $porcentaje, 0);
}

// Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
$icbf = round($valor_base, -2);
        $total_aportes= $eps +$afp+$arl+$cajacomp+$icbf;
        $gran_total = $total_devengos+$total_aportes;
    
 }
    else {
    
    // si no  si ocasioan ::
 //calculo catedra si $tipo_docente <> "Catedra"
    // Cálculo principal
// Inicializar horas
    $horas = 0;
    $mesesocas = intval($semanas_ocasant / 4.33); // 4.33 semanas ≈ 1 mes
    // Asegurarse que los índices existen y son iguales a "MT" o "TC"
    if (($row["tipo_dedicacion"] == "MT") || ($row["tipo_dedicacion_r"] == "MT")) {
        $horas = 20;
    } elseif (($row["tipo_dedicacion"] == "TC") || ($row["tipo_dedicacion_r"] == "TC")) {
        $horas = 40;
    }

    // Calculo de la asignación mensual y total
    $asignacion_mes = $row["puntos"] * $valor_puntoant * ($horas / 40);
    $asignacion_total = $asignacion_mes * $dias_ocasant / 30;
    
    
    $prima_navidad = $asignacion_mes*$mesesocas/12;
    $indem_vacaciones = $asignacion_mes*$dias_ocasant/360;
    $indem_prima_vacaciones = $asignacion_mes*(2/3)*($dias_ocasant/360);
    $cesantias = round(($asignacion_total + $prima_navidad) / 12);
    $total_empleado=$asignacion_total + $prima_navidad+$indem_vacaciones+$indem_prima_vacaciones;
 //eps
    $eps = round(($asignacion_total * 8.5) / 100);

        //pension



// Redondear al múltiplo de 100 más cercano
 $afp = round(($asignacion_total * 12) / 100);
        
       
// Redondeo al múltiplo de 100 más cercano
$arl =round(($asignacion_total * 0.522) / 100,-2);
    
        //comfaucaua

// Redondear al múltiplo de 100 más cercano
$cajacomp = round(($asignacion_total * 4) / 100,-2);
        
        // icbf

// Redondear al múltiplo de 100 más cercano (como REDONDEAR(...;-2) en Excel)
        $icbf = round(($asignacion_total * 3) / 100,-2);
            $total_entidades=$cesantias+ $eps +$afp+$arl+$cajacomp+$icbf;
 

        $gran_total = $total_empleado+$total_entidades;
    
    }
$title =
    //"Variables base\n" .
    //"Horas: " . $horas . "\n" .
    //"Meses ocasionales: " . $mesesocas . "\n" .
    //"Valor punto: $" . number_format($valor_puntoant, 0, ',', '.') . "\n" .
    //"Semanas ocasional: " . $semanas_ocas . "\n" .
    //"días: " . $dias_ocas . "\n\n" .

    "Detalle salarial\n" .
    "Asignación mensual: $" . number_format($asignacion_mes ?? 0, 0, ',', '.') . "\n" .
    "Asignación total: $" . number_format($asignacion_total ?? 0, 0, ',', '.') . "\n" .
    "Prima de Navidad: $" . number_format($prima_navidad ?? 0, 0, ',', '.') . "\n" .
    "Indem. Vacaciones: $" . number_format($indem_vacaciones ?? 0, 0, ',', '.') . "\n" .
    "Indem. Prima Vacaciones: $" . number_format($indem_prima_vacaciones ?? 0, 0, ',', '.') . "\n" .
    "Cesantías: $" . number_format($cesantias ?? 0, 0, ',', '.') . "\n" .
    ($tipo_docente == "Catedra" ? "Total devengos" : "Total empleado") . ": $" . number_format($total_empleado_mostrar ?? 0, 0, ',', '.') . "\n\n" .

    "Aportes a entidades\n" .
    "EPS: $" . number_format($eps ?? 0, 0, ',', '.') . "\n" .
    "Pensión: $" . number_format($afp ?? 0, 0, ',', '.') . "\n" .
    "ARL: $" . number_format($arl ?? 0, 0, ',', '.') . "\n" .
    "Caja Compensación: $" . number_format($cajacomp ?? 0, 0, ',', '.') . "\n" .
    "ICBF: $" . number_format($icbf ?? 0, 0, ',', '.') . "\n" .
    ($tipo_docente == "Catedra" ? "Total aportes" : "Total entidades") . ": $" . number_format($total_entidades_mostrar ?? 0, 0, ',', '.') . "\n\n" .

    "GRAN TOTAL: $" . number_format($gran_total ?? 0, 0, ',', '.');


echo "<td title=\"" . htmlspecialchars($title) . "\">$" . number_format($gran_total / 1000000, 2) . " M</td>";
    $total_proyectant += $gran_total;

    echo "</tr>";
    $item++;
    
}
                
                // Fila de subtotal
                echo "<tr style='font-weight: bold; background-color: #f2f2f2;'>";
                echo "<td colspan='".($tipo_usuario == 1 ? ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ? 6 : 4) : ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra" ? 6 : 3))."'>Subtotal</td>";
                echo "<td>$".number_format($total_proyectant/1000000, 2)." M</td>";
                echo "</tr>";
                echo "</table>";
                
                // Acumular el total proyectado por tipo de nómina para el periodo anterior
                if ($tipo_docente == 'Ocasional') {
                    $totalProyectadoOcasionalAnterior += $total_proyectant;
                } elseif ($tipo_docente == 'Catedra') {
                    $totalProyectadoCatedraAnterior += $total_proyectant;
                }
            } else {
                echo "<p style='text-align: center;'>No se encontraron resultados para el periodo anterior.</p>";
            }
            $total_cosolidado_ant += $total_proyectant;
            echo "</div>"; // Cierre de box-gray
            echo '</div>'; // Cierre de grid-col (periodo anterior)
            
            echo '</div>'; // Cierre de grid-row (para este tipo de docente)
        }
        ?>
    </div> <!-- Cierre de grid-container -->
    <?php
// Reemplazar el código de los totales con este:

echo '<div class="grid-row" style="display: flex; justify-content: space-between; margin-top: 20px;">';
    
    // Columna izquierda - Total actual
    echo '<div style="flex: 1; margin-right: 10px;">';
    echo '<div style="font-weight: bold; background-color: #f2f2f2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '<div style="display: flex; justify-content: space-between;">';
    echo '<span>Total Consolidado Actual:</span>';
    echo '<span data-toggle="tooltip" data-placement="left" title="Valor total: $' . number_format($total_consolidado, 0, ',', '.') . '">';
    echo '$' . number_format($total_consolidado / 1000000, 2) . ' M';
    echo '</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Columna derecha - Total anterior
    echo '<div style="flex: 1; margin-left: 10px;">';
    echo '<div style="font-weight: bold; background-color: #f2f2f2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '<div style="display: flex; justify-content: space-between;">';
    echo '<span>Total Consolidado Anterior:</span>';
    echo '<span data-toggle="tooltip" data-placement="left" title="Valor total: $' . number_format($total_cosolidado_ant, 0, ',', '.') . '">';
    echo '$' . number_format($total_cosolidado_ant / 1000000, 2) . ' M';
    echo '</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

echo '</div>'; // Cierre de grid-row
// Inicializar tooltips (si usas Bootstrap)
echo '<script>
$(document).ready(function(){
    $(\'[data-toggle="tooltip"]\').tooltip(); 
});
</script>';
            
    
echo "<div style='margin-bottom: 10px; font-size: 0.9em;'>
  <strong>Nota:</strong> 
  <span style='color: red; font-weight: bold;'>En rojo:</span> Profesores nuevos; (Ocasionales: {$contadorVerdesOc}; Cátedra: {$contadorVerdesCa}) - Total: {$contadorVerdes} &nbsp;|&nbsp;
  <span style='color: green; font-weight: bold;'>En verde:</span> Profesores que ya no continúan. (Ocasionales: {$contadorRojosOc}, Cátedra: {$contadorRojosCa}) - Total: {$contadorRojos} &nbsp;|&nbsp;
  <span style='background-color: yellow; color: red; font-weight: bold;'>&nbsp;Cambio de vinculación&nbsp;</span>:  Profesores que cambian de tipo de vinculación en el periodo actual.
</div>  ";
    
// Calcular el porcentaje de cambio (manteniendo tus variables exactas)
$diferencia = $total_consolidado - $total_cosolidado_ant;
$porcentaje = ($total_cosolidado_ant != 0) 
    ? round(($diferencia / $total_cosolidado_ant) * 100, 1) 
    : 0;

// Determinar color y flecha (con colores invertidos como solicitaste)
if ($porcentaje > 0) {
    $color = "danger"; // Rojo para incremento
    $icono = "bi bi-arrow-up";
    $texto = "Incremento";
} elseif ($porcentaje < 0) {
    $color = "success"; // Verde para decremento
    $icono = "bi bi-arrow-down";
    $texto = "Decremento";
} else {
    $color = "secondary"; // Gris
    $icono = "bi bi-dash";
    $texto = "Estable";
}

// Mostrar el indicador (versión compacta en una sola línea)
echo <<<HTML
<div class="text-end mb-3"  id="seccionGraficos">
    <span class="text-muted me-2">Variación en proyecto presupuestal:</span>
    <span class="badge bg-{$color}-subtle text-{$color}">
        <i class="{$icono} me-1"></i>
        $texto: <strong>".abs($porcentaje)."%</strong>
    </span>
</div>
HTML;
?>
</div>    
   

            
<script>
    
document.querySelectorAll('.delete-form').forEach(function(form) {
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // 1. Confirmación básica
    if (!confirm('¿Está seguro de eliminar este registro permanentemente?')) {
      return;
    }

    // 2. Pedir motivo descriptivo
    const motivo = prompt('Evidencia/documento que justifica la eliminación (Ej: Oficio 123, email de solicitud):');
    if (motivo === null) return;
    if (motivo.trim() === '') {
      alert('Debe ingresar un motivo válido');
      return;
    }

    // 3. Mostrar selector de tipo de eliminación
    const tipoEliminacion = `
      <div style="padding:10px;background:#f8f9fa;border-radius:5px;">
    <h4 style="margin-top:0;">Tipo de Eliminación</h4>
    <select id="tipoEliminacionSelect" style="width:100%;padding:8px;margin-bottom:10px;">
        <option value="">-- Seleccione --</option>
        <option value="Ajuste de Matrículas">Ajuste de Matrículas</option>
        <option value="Decisión de no vincularse">Decisión de no vincularse</option>
        <option value="No necesidad docente">No necesidad docente</option>
        <option value="Jubilacion">Jubilacion</option>
        <option value="Fallecimiento">Fallecimiento</option>
        <option value="Enfermedad general">Enfermedad general</option>
        <option value="Renuncia">Renuncia</option>
        <option value="NN solicitado">NN solicitado</option>
        <option value="Otro">Otro</option>
        <option value="Por Decision de Consejo de Facultad">Por Decision de Consejo de Facultad</option>
        <option value="Ajustes VRA">Ajustes VRA</option>
    </select>
    <button onclick="confirmarEliminacion()" style="background:#dc3545;color:white;border:none;padding:8px 15px;border-radius:4px;">Confirmar</button>
    <button onclick="cancelarEliminacion()" style="background:#6c757d;color:white;border:none;padding:8px 15px;border-radius:4px;margin-left:5px;">Cancelar</button>
</div>
    `;

    // Crear modal temporal
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '50%';
    modal.style.left = '50%';
    modal.style.transform = 'translate(-50%, -50%)';
    modal.style.backgroundColor = 'white';
    modal.style.padding = '20px';
    modal.style.borderRadius = '5px';
    modal.style.boxShadow = '0 0 10px rgba(0,0,0,0.3)';
    modal.style.zIndex = '1000';
    modal.innerHTML = tipoEliminacion;
    modal.setAttribute('id', 'modalEliminacion');
    
    // Guardar referencia al formulario y motivo
    modal._form = form;
    modal._motivo = motivo;
    
    document.body.appendChild(modal);

    // Funciones para los botones del modal
    window.confirmarEliminacion = function() {
      const select = document.getElementById('tipoEliminacionSelect');
      const tipo = select.value;
      
      if (!tipo) {
        alert('Debe seleccionar un tipo de eliminación');
        return;
      }

      // Asignar valores al formulario
      const modal = document.getElementById('modalEliminacion');
      modal._form.querySelector('.motivo-input').value = modal._motivo;
      
      // Crear campo oculto para el tipo si no existe
      let tipoInput = modal._form.querySelector('input[name="tipo_eliminacion"]');
      if (!tipoInput) {
        tipoInput = document.createElement('input');
        tipoInput.type = 'hidden';
        tipoInput.name = 'tipo_eliminacion';
        modal._form.appendChild(tipoInput);
      }
      tipoInput.value = tipo;

      // Eliminar modal y enviar formulario
      document.body.removeChild(modal);
      modal._form.submit();
    };

    window.cancelarEliminacion = function() {
      document.body.removeChild(document.getElementById('modalEliminacion'));
    };
  });
});
</script>

<?php       // Función para obtener el cierreo no de departamento

echo "<div>

</div>  ";
?>
<div>
    <div class="dashboard-profesores">
       <h2 class="dashboard-title">
    Análisis Comparativo (<?= $anio_semestre ?> vs <?= $periodo_anterior ?>)
    <br>
    Departamento: <?= mb_strimwidth(obtenerNombreDepartamento($_POST['departamento_id']), 0, 65, '...') ?>
    / <?= mb_strimwidth(obtenerNombreFacultad($departamento_id), 0, 65, '...') ?>
</h2>

<div class="card-container">
  <div class="card">
    <div class="card-top-section">
        <?php
        $diffOcasional = $totalProfesoresOcasional - $totalProfesoresOcasionalAnterior;
        $diffOcasionalh = $totalhorasOcasional - $totalhorasOcasionalAnterior;

        $percentageOcasional = ($totalProfesoresOcasionalAnterior != 0) ? ($diffOcasional / $totalProfesoresOcasionalAnterior) * 100 : 0;
        $percentageOcasionalh = ($totalhorasOcasionalAnterior != 0) ? ($diffOcasionalh / $totalhorasOcasionalAnterior) * 100 : 0;

        $classOcasional = ($diffOcasional >= 0) ? 'positive-alert' : 'negative-favorable';
        $classOcasionalh = ($diffOcasionalh >= 0) ? 'positive-alert' : 'negative-favorable';

        $discrepanciaOcasional = $diffOcasional - ($contadorVerdesOc - $contadorRojosOc);
        ?>
        
        <div class="dual-percentage">
            <div class="card-percentage <?= $classOcasional ?>">
                <?= ($percentageOcasional >= 0 ? '+' : '') . number_format($percentageOcasional, 1) . '%' ?>
            </div>
        </div>
        
        <h3 class="card-title">Profesores Ocasionales (<?= $anio_semestre ?>)</h3>
        <div class="card-main-value">
            <?= $totalProfesoresOcasional ?>
            <span class="card-variation <?= $classOcasional ?>">
                <?= ($diffOcasional >= 0 ? '<i class="fas fa-arrow-up"></i> +' : '<i class="fas fa-arrow-down"></i> ') . $diffOcasional ?>
            </span>
        </div>
        <div class="card-subtext">
            <span class="new-count positive-alert">+<?= $contadorVerdesOc ?> nuevos</span>
            <span class="removed-count negative-favorable">-<?= $contadorRojosOc ?> no continúan</span>
            <?php if ($discrepanciaOcasional !== 0): ?>
                <span class="change-vinculacion">
                    <?= ($discrepanciaOcasional > 0 ? '+' : '') . $discrepanciaOcasional ?> cambian vinc.
                </span>
            <?php endif; ?>
            <span class="previous-count">Anterior (<?= $periodo_anterior ?>): <?= $totalProfesoresOcasionalAnterior ?></span>
        </div>
    </div>
    
    <!-- Línea divisoria reforzada -->
   <div class="hours-divider-title">
        <span class="divider-text">Equivalente en  horas:</span>
        <hr class="strong-divider">
    </div>    
    <!-- Sección de horas ultra compacta -->
    <div class="ultra-compact-hours">
        <div class="hours-grid">
            <div class="hour-item">
                <div class="hour-label">Horas:</div>
                <div class="hour-number"><?= number_format($totalhorasOcasional, 1) ?></div>
            </div>
            <div class="hour-item">
                <div class="hour-label">Anterior:</div>
                <div class="hour-number"><?= number_format($totalhorasOcasionalAnterior, 1) ?></div>
            </div>
            <div class="hour-item">
                <div class="hour-label">Dif:</div>
                <div class="hour-number <?= $classOcasionalh ?>">
                    <?= ($diffOcasionalh >= 0 ? '+' : '') . number_format($diffOcasionalh, 1) ?>
                </div>
            </div>
            <div class="hour-item">
                <div class="hour-label">%:</div>
                <div class="hour-number <?= $classOcasionalh ?>">
                    <?= ($percentageOcasionalh >= 0 ? '+' : '') . number_format($percentageOcasionalh, 1) . '%' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos generales de la tarjeta */
.card {
    position: relative;
    padding: 15px;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    min-height: auto;
}

/* Sección superior */
.card-top-section {
    margin-bottom: 5px;
}

.dual-percentage {
    position: absolute;
    right: 15px;
    top: 15px;
}

.card-percentage {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 600;
}

.card-title {
    font-size: 1.1em;
    margin-bottom: 10px;
    color: #333;
}

.card-main-value {
    font-size: 1.8em;
    font-weight: 700;
    margin-bottom: 5px;
}

.card-variation {
    font-size: 0.7em;
    font-weight: 600;
    margin-left: 5px;
}

.card-subtext {
    font-size: 0.8em;
    color: #666;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 5px;
}

/* Línea divisoria */
/* Estilo para el contenedor del título y la línea */
.hours-divider-title {
    position: relative;
    margin: 10px 0 8px;
    width: 100%;
}

/* Texto sobre la línea */
.divider-text {
    position: relative;
    padding-right: 10px;
    background: white; /* Mismo fondo que la tarjeta */
    color: #666;
    font-size: 0.75rem;
    font-weight: 500;
    z-index: 1;
}

/* Línea mejorada */
.strong-divider {
    border: none;
    border-top: 2px solid #c0c0c0;
    margin-top: -8px; /* Superpone la línea al texto */
    width: 100%;
    position: relative;
}
/* Sección de horas compacta */
.ultra-compact-hours {
    padding: 3px 0;
    height: auto;
}

.hours-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 5px;   
}

.hour-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.hour-label {
    font-size: 0.65em;
    color: #777;
    font-weight: 500;
    margin-bottom: 2px;
}

.hour-number {
    font-size: 0.75em;
    font-weight: 600;
}

/* Colores */
.positive-alert {
    color: #28a745;
    background-color: rgba(40, 167, 69, 0.1);
}

.negative-favorable {
    color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}

.new-count {
    color: #28a745;
}

.removed-count {
    color: #dc3545;
}

.change-vinculacion {
    color: #6c757d;
}

.previous-count {
    color: #6c757d;
}
</style>

<!-- Tarjeta de Profesores Cátedra -->
<div class="card">
    <div class="card-top-section">
        <?php
        $diffCatedra = $totalProfesoresCatedra - $totalProfesoresCatedraAnterior;
        $diffCatedrah = $totalhorasCatedra - $totalhorasCatedraAnterior;

        $percentageCatedra = ($totalProfesoresCatedraAnterior != 0) ? ($diffCatedra / $totalProfesoresCatedraAnterior) * 100 : 0;
        $percentageCatedrah = ($totalhorasCatedraAnterior != 0) ? ($diffCatedrah / $totalhorasCatedraAnterior) * 100 : 0;

        $classCatedra = ($diffCatedra >= 0) ? 'positive-alert' : 'negative-favorable';
        $classCatedrah = ($diffCatedrah >= 0) ? 'positive-alert' : 'negative-favorable';

        $discrepanciaCatedra = $diffCatedra - ($contadorVerdesCa - $contadorRojosCa);
        ?>
        
        <div class="dual-percentage">
            <div class="card-percentage <?= $classCatedra ?>">
                <?= ($percentageCatedra >= 0 ? '+' : '') . number_format($percentageCatedra, 1) . '%' ?>
            </div>
        </div>
        
        <h3 class="card-title">Profesores Cátedra (<?= $anio_semestre ?>)</h3>
        <div class="card-main-value">
            <?= $totalProfesoresCatedra ?>
            <span class="card-variation <?= $classCatedra ?>">
                <?= ($diffCatedra >= 0 ? '<i class="fas fa-arrow-up"></i> +' : '<i class="fas fa-arrow-down"></i> ') . $diffCatedra ?>
            </span>
        </div>
        <div class="card-subtext">
            <span class="new-count positive-alert">+<?= $contadorVerdesCa ?> nuevos</span>
            <span class="removed-count negative-favorable">-<?= $contadorRojosCa ?> no continúan</span>
            <?php if ($discrepanciaCatedra !== 0): ?>
                <span class="change-vinculacion">
                    <?= ($discrepanciaCatedra > 0 ? '+' : '') . $discrepanciaCatedra ?> cambian vinculación
                </span>
            <?php endif; ?>
            <span class="previous-count">Anterior (<?= $periodo_anterior ?>): <?= $totalProfesoresCatedraAnterior ?></span>
        </div>
    </div>
    
    <!-- Línea divisoria -->
   <div class="hours-divider-title">
        <span class="divider-text">Equivalente en  horas:</span>
        <hr class="strong-divider">
    </div>    
    <!-- Sección de horas compacta -->
    <div class="ultra-compact-hours">
        <div class="hours-grid">
            <div class="hour-item">
                <div class="hour-label">Horas:</div>
                <div class="hour-number"><?= number_format($totalhorasCatedra, 1) ?></div>
            </div>
            <div class="hour-item">
                <div class="hour-label">Anterior:</div>
                <div class="hour-number"><?= number_format($totalhorasCatedraAnterior, 1) ?></div>
            </div>
            <div class="hour-item">
                <div class="hour-label">Dif:</div>
                <div class="hour-number <?= $classCatedrah ?>">
                    <?= ($diffCatedrah >= 0 ? '+' : '') . number_format($diffCatedrah, 1) ?>
                </div>
            </div>
            <div class="hour-item">
                <div class="hour-label">%:</div>
                <div class="hour-number <?= $classCatedrah ?>">
                    <?= ($percentageCatedrah >= 0 ? '+' : '') . number_format($percentageCatedrah, 1) . '%' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tarjeta de Total Profesores -->
<div class="card total-card">
    <div class="card-top-section">
        <?php
        $totalProfesoresTotal = $totalProfesoresOcasional + $totalProfesoresCatedra;
        $totalProfesoresTotalAnterior = $totalProfesoresOcasionalAnterior + $totalProfesoresCatedraAnterior;
        $diffTotalProfesores = $totalProfesoresTotal - $totalProfesoresTotalAnterior;
        $totalHorasTotal = $totalhorasOcasional + $totalhorasCatedra;
        $totalHorasTotalAnterior = $totalhorasOcasionalAnterior + $totalhorasCatedraAnterior;
        $diffTotalHoras = $totalHorasTotal - $totalHorasTotalAnterior;
        
        $percentageTotalProfesores = ($totalProfesoresTotalAnterior != 0) ? ($diffTotalProfesores / $totalProfesoresTotalAnterior) * 100 : 0;
        $percentageTotalHoras = ($totalHorasTotalAnterior != 0) ? ($diffTotalHoras / $totalHorasTotalAnterior) * 100 : 0;
        
        $classTotalProfesores = ($diffTotalProfesores >= 0) ? 'positive-alert' : 'negative-favorable';
        $classTotalHoras = ($diffTotalHoras >= 0) ? 'positive-alert' : 'negative-favorable';
        ?>
        
        <div class="dual-percentage">
            <div class="card-percentage <?= $classTotalProfesores ?>">
                <?= ($percentageTotalProfesores >= 0 ? '+' : '') . number_format($percentageTotalProfesores, 1) . '%' ?>
            </div>
        </div>
        
        <h3 class="card-title">Total Profesores (<?= $anio_semestre ?>)</h3>
        <div class="card-main-value">
            <?= $totalProfesoresTotal ?>
            <span class="card-variation <?= $classTotalProfesores ?>">
                <?= ($diffTotalProfesores >= 0 ? '<i class="fas fa-arrow-up"></i> +' : '<i class="fas fa-arrow-down"></i> ') . $diffTotalProfesores ?>
            </span>
        </div>
        <div class="card-subtext">
            <span class="previous-count">Anterior (<?= $periodo_anterior ?>): <?= $totalProfesoresTotalAnterior ?></span>
        </div>
    </div>
    
    <!-- Línea divisoria -->
   <div class="hours-divider-title">
        <span class="divider-text">Equivalente en  horas:</span>
        <hr class="strong-divider">
    </div>  
    <!-- Sección de horas compacta -->
    <div class="ultra-compact-hours">
        <div class="hours-grid">
            <div class="hour-item">
                <div class="hour-label">Horas:</div>
                <div class="hour-number"><?= number_format($totalHorasTotal, 1) ?></div>
            </div>
            <div class="hour-item">
                <div class="hour-label">Anterior:</div>
                <div class="hour-number"><?= number_format($totalHorasTotalAnterior, 1) ?></div>
            </div>
            <div class="hour-item">
                <div class="hour-label">Dif:</div>
                <div class="hour-number <?= $classTotalHoras ?>">
                    <?= ($diffTotalHoras >= 0 ? '+' : '') . number_format($diffTotalHoras, 1) ?>
                </div>
            </div>
            <div class="hour-item">
                <div class="hour-label">%:</div>
                <div class="hour-number <?= $classTotalHoras ?>">
                    <?= ($percentageTotalHoras >= 0 ? '+' : '') . number_format($percentageTotalHoras, 1) . '%' ?>
                </div>
            </div>
        </div>
    </div>
</div>
        <div class="card-container">
            <div class="card">
                <?php
                $diffProyectadoOcasional = $totalProyectadoOcasional - $totalProyectadoOcasionalAnterior;
                $percentageProyectadoOcasional = ($totalProyectadoOcasionalAnterior != 0) ? ($diffProyectadoOcasional / $totalProyectadoOcasionalAnterior) * 100 : 0;
                $classProyectadoOcasional = ($diffProyectadoOcasional >= 0) ? 'positive-alert' : 'negative-favorable';
                ?>
                <div class="card-percentage <?= $classProyectadoOcasional ?>">
                    <?= ($percentageProyectadoOcasional >= 0 ? '+' : '') . number_format($percentageProyectadoOcasional, 1) . '%' ?>
                </div>
                <h3 class="card-title">Proyección Ocasional (<?= $anio_semestre ?>)</h3>
                <div class="card-main-value">
                    $<?= number_format($totalProyectadoOcasional, 0, ',', '.') ?>
                    <span class="card-variation <?= $classProyectadoOcasional ?>">
                        <?= ($diffProyectadoOcasional >= 0 ? '<i class="fas fa-arrow-up"></i> +' : '<i class="fas fa-arrow-down"></i> ') . number_format($diffProyectadoOcasional, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="card-subtext">
                    <span class="previous-count">Anterior (<?= $periodo_anterior ?>): $<?= number_format($totalProyectadoOcasionalAnterior, 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="card">
                <?php
                $diffProyectadoCatedra = $totalProyectadoCatedra - $totalProyectadoCatedraAnterior;
                $percentageProyectadoCatedra = ($totalProyectadoCatedraAnterior != 0) ? ($diffProyectadoCatedra / $totalProyectadoCatedraAnterior) * 100 : 0;
                $classProyectadoCatedra = ($diffProyectadoCatedra >= 0) ? 'positive-alert' : 'negative-favorable';
                ?>
                <div class="card-percentage <?= $classProyectadoCatedra ?>">
                    <?= ($percentageProyectadoCatedra >= 0 ? '+' : '') . number_format($percentageProyectadoCatedra, 1) . '%' ?>
                </div>
                <h3 class="card-title">Proyección Cátedra (<?= $anio_semestre ?>)</h3>
                <div class="card-main-value">
                    $<?= number_format($totalProyectadoCatedra, 0, ',', '.') ?>
                    <span class="card-variation <?= $classProyectadoCatedra ?>">
                        <?= ($diffProyectadoCatedra >= 0 ? '<i class="fas fa-arrow-up"></i> +' : '<i class="fas fa-arrow-down"></i> ') . number_format($diffProyectadoCatedra, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="card-subtext">
                    <span class="previous-count">Anterior (<?= $periodo_anterior ?>): $<?= number_format($totalProyectadoCatedraAnterior, 0, ',', '.') ?></span>
                </div>
            </div>

            <div class="card total-card">
                <?php
                $totalProyectadoTotal = $totalProyectadoOcasional + $totalProyectadoCatedra;
                $totalProyectadoTotalAnterior = $totalProyectadoOcasionalAnterior + $totalProyectadoCatedraAnterior;
                $diffTotalProyectado = $totalProyectadoTotal - $totalProyectadoTotalAnterior;
                $percentageTotalProyectado = ($totalProyectadoTotalAnterior != 0) ? ($diffTotalProyectado / $totalProyectadoTotalAnterior) * 100 : 0;
                $classTotalProyectado = ($diffTotalProyectado >= 0) ? 'positive-alert' : 'negative-favorable';
                ?>
                <div class="card-percentage <?= $classTotalProyectado ?>">
                    <?= ($percentageTotalProyectado >= 0 ? '+' : '') . number_format($percentageTotalProyectado, 1) . '%' ?>
                </div>
                <h3 class="card-title">Total Proyectado (<?= $anio_semestre ?>)</h3>
                <div class="card-main-value">
                    $<?= number_format($totalProyectadoTotal, 0, ',', '.') ?>
                    <span class="card-variation <?= $classTotalProyectado ?>">
                        <?= ($diffTotalProyectado >= 0 ? '<i class="fas fa-arrow-up"></i> +' : '<i class="fas fa-arrow-down"></i> ') . number_format($diffTotalProyectado, 0, ',', '.') ?>
                    </span>
                </div>
                <div class="card-subtext">
                    <span class="previous-count">Anterior (<?= $periodo_anterior ?>): $<?= number_format($totalProyectadoTotalAnterior, 0, ',', '.') ?></span>
                </div>
            </div>
        </div>

         <div class="dashboard-visual-section" id="seccionGraficos">
    <!-- Panel que contiene los dos gráficos -->
    <div class="charts-panel">
        <div class="grafico-card">
            <canvas id="profesorCantidadChart"></canvas>
        </div>
        <div class="grafico-card">
            <canvas id="valoresProyectadosChart"></canvas>
        </div>
    </div>
            <?php
            // MOSTRAR SECCIÓN DE INTERPRETACIONES
            // Asegúrate de que $interpretaciones esté definida y rellena ANTES de este punto en el código PHP.
            // (Asumo que tus cálculos de interpretaciones están ejecutándose antes de la salida HTML)
         
            ?>
              
        
        <?php
    // ... cálculos previos ...

    $porcentajeTotalProfesores = ($totalProfesoresTotalAnterior != 0) 
        ? ($diffTotalProfesores / $totalProfesoresTotalAnterior) * 100 
        : 0;
    $porcentajeTotalProyectado = ($totalProyectadoTotalAnterior != 0) 
        ? ($diffTotalProyectado / $totalProyectadoTotalAnterior) * 100 
        : 0;

    // CÁLCULOS ADICIONALES PARA INTERPRETACIONES
    $diferenciaSemanasCat = $semanas_cat - $semanas_catant;
    $diferenciaSemanasOc = $semanas_ocas - $semanas_ocasant;

    $porcentajeCambioSemanasCat = ($semanas_catant != 0) 
        ? round(($diferenciaSemanasCat / $semanas_catant) * 100, 1) 
        : 0;

    $porcentajeCambioSemanasOc = ($semanas_ocasant != 0) 
        ? round(($diferenciaSemanasOc / $semanas_ocasant) * 100, 1) 
        : 0;

    $cambioSemanasSignificativo = (abs($porcentajeCambioSemanasCat) > 5 || abs($porcentajeCambioSemanasOc) > 5);

    // DETERMINAR SI ES VIGENCIA DIFERENTE (año diferente)
    $anio_actual = explode('-', $anio_semestre)[0];
    $anio_anterior = explode('-', $periodo_anterior)[0];
    $vigencia_diferente = ($anio_actual != $anio_anterior);
    $ipc_estimado = 0.08; // 8% de inflación estimada

// CÁLCULOS ADICIONALES PARA INTERPRETACIONES
$diferenciaSemanasCat = $semanas_cat - $semanas_catant;
$diferenciaSemanasOc = $semanas_ocas - $semanas_ocasant;

$porcentajeCambioSemanasCat = ($semanas_catant != 0) 
    ? round(($diferenciaSemanasCat / $semanas_catant) * 100, 1) 
    : 0;

$porcentajeCambioSemanasOc = ($semanas_ocasant != 0) 
    ? round(($diferenciaSemanasOc / $semanas_ocasant) * 100, 1) 
    : 0;

$cambioSemanasSignificativo = (abs($porcentajeCambioSemanasCat) > 5 || abs($porcentajeCambioSemanasOc) > 5);

// GENERACIÓN DE INTERPRETACIONES
$interpretaciones = [];

                     // Escenario 0: Periodo nuevo (datos vacíos/ceros)
if ($totalProyectadoTotal=== 0 ) {
    $interpretaciones[] = [
        'icono' => 'fas fa-hourglass-start', // Icono de reloj de arena
        'titulo' => 'Periodo en configuración',
        'texto' => 'Este es un periodo nuevo sin datos históricos. La información se mostrará aquí conforme se registren las vinculaciones.',
        'tipo' => 'info' // Estilo azul informativo
    ];
}
                     // Escenario 1: Profesores bajan pero presupuesto sube
elseif ($diffTotalProfesores < 0 && $diffTotalProyectado > 0) {
    $interpretacion = "A pesar de la disminución de <strong>" . abs($diffTotalProfesores) . " profesores</strong> ";
    $interpretacion .= "(un <strong>" . number_format(abs($porcentajeTotalProfesores), 1) . "%</strong> menos), el presupuesto proyectado aumentó ";
    $interpretacion .= "en <strong>$" . number_format($diffTotalProyectado, 0, ',', '.') . "</strong>. "; // Formateo para millones si es grande, o el valor exacto

    $causas = [];

    // Causa 1: Cambio significativo en semanas
    if ($cambioSemanasSignificativo) {
        $semanas_info = [];
        if ($diferenciaSemanasCat > 0) {
            $semanas_info[] = "Cátedra (+" . number_format($diferenciaSemanasCat, 0) . " semanas, " . number_format($porcentajeCambioSemanasCat, 1) . "%)";
        }
        if ($diferenciaSemanasOc > 0) {
            $semanas_info[] = "Ocasional (+" . number_format($diferenciaSemanasOc, 0) . " semanas, " . number_format($porcentajeCambioSemanasOc, 1) . "%)";
        }
        if (!empty($semanas_info)) {
            $causas[] = "un incremento en las semanas de vinculación: " . implode(" y ", $semanas_info) . ", lo que indica una mayor intensidad horaria por profesor.";
        }
    }

    // Causa 2: Cambio de vinculación de profesores
    if ($huboCambioVinculacion) { // <-- USANDO LA BANDERA AQUÍ
        $causas[] = "profesores que cambiaron su tipo de vinculación (ej. de Cátedra a Ocasional), lo que puede implicar un ajuste en el valor";
    }
    
    // Causa 3: Aumento general en puntos/horas (si no hay otras causas específicas o como complemento)
    // Solo agrega esta causa si las anteriores no son la única explicación, o como una general
    if (empty($causas) || (count($causas) == 1 && strpos($causas[0], 'semanas de vinculación') === false)) {
        $causas[] = "un aumento en los puntos/horas asignados a los profesores activos.";
    }

    // Construir la frase final con las causas
    if (!empty($causas)) {
        $interpretacion .= "Esto puede atribuirse a: " . implode(" Además, ", $causas) . ".";
    } else {
        $interpretacion .= "Este comportamiento sugiere una reasignación interna de recursos o ajustes en la valoración de horas/puntos.";
    }
    
    $interpretaciones[] = [
        'icono' => 'fas fa-balance-scale', // Icono de balanza o redistribución
        'titulo' => 'Ajuste presupuestal con redistribución de planta',
        'texto' => $interpretacion,
        'tipo' => 'advertencia' // O 'info' si quieres un tono más neutral
    ];
}
              // Escenario 8: Profesores suben pero presupuesto baja
elseif ($diffTotalProfesores > 0 && $diffTotalProyectado < 0) {
    $interpretacion = "A pesar del incremento de <strong>" . $diffTotalProfesores . " profesores</strong> ";
    $interpretacion .= "(un <strong>" . number_format($porcentajeTotalProfesores, 1) . "%</strong> más), ";
    $interpretacion .= "el presupuesto proyectado disminuyó en <strong>$" . number_format(abs($diffTotalProyectado), 0, ',', '.') . "</strong> ";
    $interpretacion .= "(<strong>" . number_format(abs($porcentajeTotalProyectado), 1) . "%</strong> menos). ";

    $causas = [];

    // Causa 1: Cambios hacia modalidades más económicas
    if ($huboCambioVinculacion) {
        $causas[] = "migración hacia tipos de vinculación con menor valor por hora (ej: de Ocasional a Cátedra)";
    }

    // Causa 2: Reducción en semanas
    if ($cambioSemanasSignificativo) {
        $detalle_semanas = [];
        if ($diferenciaSemanasCat < 0) {
            $detalle_semanas[] = "Cátedra (" . $diferenciaSemanasCat . " semanas)";
        }
        if ($diferenciaSemanasOc < 0) {
            $detalle_semanas[] = "Ocasional (" . $diferenciaSemanasOc . " semanas)";
        }
        $causas[] = "reducción en semanas: " . implode(' y ', $detalle_semanas);
    }

    // Causa 3: Profesores con menor dedicación o puntos/hora
    $causas[] = "los nuevos profesores podrían tener menor dedicación o valor asignado en puntos/hora";

    // Causa 4: Reducción general si no hay otra causa clara
    if (empty($huboCambioVinculacion) && empty($cambioSemanasSignificativo)) {
        $causas[] = "una reducción general en los puntos/hora asignados";
    }

    $interpretacion .= "Esto podría indicar:<br>- " . implode("<br>- ", $causas);

    $interpretaciones[] = [
        'icono' => 'fas fa-exchange-alt',
        'titulo' => 'Reconfiguración de planta docente',
        'texto' => $interpretacion,
        'tipo' => 'advertencia'
    ];
}

// Escenario 2: Profesores constantes, presupuesto sube
elseif (abs($diffTotalProfesores) <= 2 && $diffTotalProyectado > 0) {
    $interpretacion_base = "Para el período actual, manteniendo una planta estable de profesores, el presupuesto ";
    $interpretacion_base .= "aumentó en $" . number_format($diffTotalProyectado / 1000000, 2) . " millones.";

    $causas = [];

    // Causa 1: Incremento en semanas (si es significativo)
    if ($cambioSemanasSignificativo) {
        $detalles = [];
        if ($diferenciaSemanasCat > 0) {
            $detalles[] = "Cátedra (+" . $diferenciaSemanasCat . " semanas)";
        }
        if ($diferenciaSemanasOc > 0) {
            $detalles[] = "Ocasional (+" . $diferenciaSemanasOc . " semanas)";
        }
        $causas[] = "Incremento en las semanas de vinculación para " . implode(" y ", $detalles) . ".";
    } else {
        // Causa 1b: Ajustes en puntos/horas si no hay cambio significativo en semanas
        // Esta se incluye como una causa general si las semanas no son el factor principal
        $causas[] = "Ajustes en los puntos y/o horas asignados a vinculaciones.";
    }

  
    if ($huboCambioVinculacion) {
        $causas[] = "Se identificaron cambios en el tipo de vinculación de algunos profesores, lo que afecta su valor.";
    }

    $interpretacion = $interpretacion_base;
    if (!empty($causas)) {
        $interpretacion .= " Las causas principales o factores relevantes incluyen:<ul>";
        foreach ($causas as $causa) {
            $interpretacion .= "<li>" . $causa . "</li>";
        }
        $interpretacion .= "</ul>";
    }

    $interpretacion .= "Este aumento global representa un " . abs($porcentaje) . "% adicional en el costo por profesor.";

    $interpretaciones[] = [
        'icono' => 'fas fa-arrow-up', // Icono de flecha hacia arriba (rojo si el tipo es negativo)
        'titulo' => 'Incremento de Costos', // Título cambiado
        'texto' => $interpretacion,
        'tipo' => 'negativo' // Interpretación cambiada a negativa
    ];
}
// Escenario 6: Presupuesto baja con profesores estables
elseif (abs($diffTotalProfesores) < 2 && $porcentajeTotalProyectado < 0) {
    $interpretacion = "Con una planta docente estable (variación de " . abs($diffTotalProfesores) . " profesor(es)), el presupuesto disminuyó en un <strong>" . number_format(abs($porcentajeTotalProyectado), 1) . "%</strong>. Posibles causas:";
    
    $interpretacion .= "<ul class='interpretacion-lista'>";
    
    // Causa 1: Reducción en semanas de vinculación
    if ($cambioSemanasSignificativo) {
        $interpretacion .= "<li>Reducción en semanas de vinculación: ";
        
        $semanas_detalles = [];
        if ($diferenciaSemanasCat < 0) {
            $semanas_detalles[] = "Cátedra (" . abs($diferenciaSemanasCat) . " semanas menos)";
        }
        if ($diferenciaSemanasOc < 0) {
            $semanas_detalles[] = "Ocasional (" . abs($diferenciaSemanasOc) . " semanas menos)";
        }
        $interpretacion .= implode(" y ", $semanas_detalles) . ".</li>";
    }
    
    // Causa 2 (NUEVA): Cambios de vinculación de profesores
    if ($huboCambioVinculacion) {
        $interpretacion .= "<li>Cambios en el tipo de vinculación de algunos profesores, lo que pudo resultar en contratos con menor valor.</li>";
    }

    // Causa 3: Ajustes en puntos/horas asignados
    $interpretacion .= "<li>Ajustes a la baja en los puntos/horas asignados por profesor.</li>";
    
    // Causa 4: Cambios en el valor del punto
   // $interpretacion .= "<li>Posibles cambios en el valor base del punto o de la hora.</li>";
    $interpretacion .= "</ul>";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-compress', // Icono de compresión o ajuste
        'titulo' => 'Optimización o Ajuste Presupuestal',
        'texto' => $interpretacion,
        'tipo' => 'neutro' // O 'info'
    ];
}
              
// Escenario 7: Ambos indicadores bajan (profesores y presupuesto)
elseif ($diffTotalProfesores < 0 && $diffTotalProyectado < 0) {
    $interpretacion = "Reducción </strong> en la planta docente temporal ";
    $interpretacion .= "(" . abs($diffTotalProfesores) . " profesores menos, " . number_format(abs($porcentajeTotalProfesores), 1) . "% menos) ";
    $interpretacion .= "y en el presupuesto proyectado ($" . number_format(abs($diffTotalProyectado), 0, ',', '.') . " menos, ";
    $interpretacion .= number_format(abs($porcentajeTotalProyectado), 1) . "% menos). ";
    
    $interpretacion .= " refleja:";
    
    $interpretacion .= "<ul class='interpretacion-lista'>";
    $interpretacion .= "<li>Una <strong>reducción general de carga académica</strong></li>";
    
    if ($cambioSemanasSignificativo) {
        $interpretacion .= "<li>Ajustes en las semanas de vinculación: ";
        if ($diferenciaSemanasCat < 0) {
            $interpretacion .= "Cátedra (" . $diferenciaSemanasCat . " semanas), ";
        }
        if ($diferenciaSemanasOc < 0) {
            $interpretacion .= "Ocasional (" . $diferenciaSemanasOc . " semanas)";
        }
        $interpretacion .= "</li>";
    }
    
    $interpretacion .= "<li>Posible <strong>optimización de recursos</strong> o disminución de necesidades académicas</li>";
    $interpretacion .= "<li>Relación costo-eficiencia: ";
    $costoPorProfesorAnterior = $totalProyectadoTotalAnterior / $totalProfesoresTotalAnterior;
$costoPorProfesorActual = $totalProfesoresTotal > 0 
    ? $totalProyectadoTotal / $totalProfesoresTotal 
    : 0;
    $variacionCosto = (($costoPorProfesorActual - $costoPorProfesorAnterior) / $costoPorProfesorAnterior) * 100;
    
    $interpretacion .= number_format($costoPorProfesorActual, 0, ',', '.') . " vs " . 
                      number_format($costoPorProfesorAnterior, 0, ',', '.') . " por profesor ";
    $interpretacion .= "(" . ($variacionCosto > 0 ? "+" : "") . number_format($variacionCosto, 1) . "%)</li>";
    $interpretacion .= "</ul>";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-arrow-down',
        'titulo' => 'Reducción de planta y presupuesto',
        'texto' => $interpretacion,
        'tipo' => 'neutro'
    ];
}
// Escenario 3: Profesores bajan, presupuesto se mantiene
elseif ($diffTotalProfesores < 0 && abs($porcentaje) < 5) {
    $ahorroEstimado = $totalProyectadoTotalAnterior * (abs($diffTotalProfesores) / $totalProfesoresTotalAnterior);
    
    $interpretacion = "A pesar de la reducción de " . abs($diffTotalProfesores) . " profesores, ";
    $interpretacion .= "el presupuesto se mantuvo estable. Esto podría indicar:";
    
    $interpretacion .= "<ul class='interpretacion-lista'>";
    $interpretacion .= "<li>Reasignación de horas a profesores existentes</li>";
    
    if ($cambioSemanasSignificativo) {
        $interpretacion .= "<li>Aumento compensatorio en semanas de vinculación</li>";
    }
    
    $interpretacion .= "<li>Potencial ahorro estimado: $" . number_format($ahorroEstimado / 1000000, 2) . " millones</li>";
    $interpretacion .= "</ul>";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-money-bill-wave',
        'titulo' => 'Estabilidad presupuestal con menor planta',
        'texto' => $interpretacion,
        'tipo' => 'neutro'
    ];
}

// Escenario 4: Profesores suben, presupuesto estable
elseif ($diffTotalProfesores > 0 && abs($porcentaje) < 5) {
    $interpretacion = "A pesar del incremento de " . $diffTotalProfesores . " profesores, ";
    $interpretacion .= "el presupuesto se mantuvo estable. Esto sugiere:";
    
    $interpretacion .= "<ul class='interpretacion-lista'>";
    $interpretacion .= "<li>Optimización en la asignación de horas/puntos</li>";
    
    if ($cambioSemanasSignificativo) {
        $interpretacion .= "<li>Reducción compensatoria en semanas de vinculación</li>";
    }
    
    $costoPromedio = $totalProyectadoTotal / $totalProfesoresTotal;
    $interpretacion .= "<li>Costo promedio por profesor: $" . number_format($costoPromedio / 1000000, 2) . " millones</li>";
    $interpretacion .= "</ul>";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-users',
        'titulo' => 'Crecimiento eficiente de planta',
        'texto' => $interpretacion,
        'tipo' => 'positivo'
    ];
}

// Escenario 5: Ambos suben proporcionalmente
elseif ($diffTotalProfesores > 0 && $diffTotalProyectado > 0 &&
        abs($porcentajeTotalProfesores - $porcentajeTotalProyectado) < 10) {

    $interpretacion = "El crecimiento de la planta docente (" . number_format($porcentajeTotalProfesores, 1) . "%) ";
    $interpretacion .= "y el aumento presupuestal (" . number_format($porcentajeTotalProyectado, 1) . "%) son proporcionales, ";
    $interpretacion .= "indicando una expansión equilibrada.";

    if ($cambioSemanasSignificativo) {
        $interpretacion .= " El cambio en semanas de vinculación contribuyó al ajuste presupuestal.";
    }

    // Nueva condición: Cambios de vinculación afectando el valor proyectado
    if ($huboCambioVinculacion) {
        $interpretacion .= " De todas maneras, se evidencia un cambio en el tipo de vinculación de algunos profesores, lo que puede afectar el valor proyectado.";
    }

    $interpretaciones[] = [
        'icono' => 'fas fa-expand',
        'titulo' => 'Crecimiento proporcional',
        'texto' => $interpretacion,
        'tipo' => 'positivo'
    ];
}


              // Escenario 9: Semanas cambian significativamente sin variación en otros indicadores
elseif (abs($diffTotalProfesores) < 2 && abs($porcentajeTotalProyectado) < 1 && $cambioSemanasSignificativo) {
    $interpretacion = "Aunque la planta docente y el presupuesto se mantuvieron estables, ";
    $interpretacion .= "se observan cambios significativos en las semanas de vinculación: ";
    
    $cambios = [];
    if (abs($porcentajeCambioSemanasCat) > 5) {
        $tendencia = ($diferenciaSemanasCat > 0) ? "aumento" : "reducción";
        $cambios[] = "Cátedra ({$tendencia} de " . abs($diferenciaSemanasCat) . " semanas, " . abs($porcentajeCambioSemanasCat) . "%)";
    }
    
    if (abs($porcentajeCambioSemanasOc) > 5) {
        $tendencia = ($diferenciaSemanasOc > 0) ? "aumento" : "reducción";
        $cambios[] = "Ocasional ({$tendencia} de " . abs($diferenciaSemanasOc) . " semanas, " . abs($porcentajeCambioSemanasOc) . "%)";
    }
    
    $interpretacion .= implode(' y ', $cambios) . ". ";
    $interpretacion .= "Esto sugiere una redistribución de cargas horarias entre modalidades.";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-retweet',
        'titulo' => 'Reasignación de cargas horarias',
        'texto' => $interpretacion,
        'tipo' => 'info'
    ];
}
              // Escenario 10: Estabilidad general (cambios mínimos en todos los indicadores)
elseif (abs($diffTotalProfesores) < 2 && 
        abs($porcentajeTotalProyectado) < 1 && 
        !$cambioSemanasSignificativo) {
    
    $interpretacion = "La planta docente, presupuesto proyectado y distribución de semanas ";
    $interpretacion .= "se mantuvieron estables con variaciones mínimas (< 1%). ";
    $interpretacion .= "Esto indica una operación consistente sin cambios significativos.";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-lock',
        'titulo' => 'Estabilidad operativa',
        'texto' => $interpretacion,
        'tipo' => 'neutro'
    ];
}
              // Escenario 11: Ambos indicadores suben significativamente (profesores y presupuesto)
elseif ($diffTotalProfesores > 0 && $diffTotalProyectado > 0 && 
        (abs($porcentajeTotalProfesores) >= 10 || abs($porcentajeTotalProyectado) >= 10)) {
    
    $interpretacion = "Se observa un <strong>incremento significativo</strong> tanto en la planta docente ";
    $interpretacion .= "(<strong>+" . $diffTotalProfesores . " profesores</strong>, un <strong>" . number_format($porcentajeTotalProfesores, 1) . "%</strong> más) ";
    $interpretacion .= "que incide en el presupuesto proyectado (<strong>$" . number_format($diffTotalProyectado, 0, ',', '.') . "</strong>, ";
    $interpretacion .= "un <strong>" . number_format($porcentajeTotalProyectado, 1) . "%</strong> más). ";
    $interpretacion .= "Otros factores:";
    
    $interpretacion .= "<ul class='interpretacion-lista'>";
    
    // Causa 1: Expansión académica general
    $interpretacion .= "<li><strong>Expansión académica</strong>: Mayor demanda de clases o nuevos programas</li>";
    
    // Causa 2: Cambios en la composición de la planta
    $causas = [];
    if ($huboCambioVinculacion) {
        $causas[] = "cambios en los tipos de vinculación hacia modalidades con mayor valor";
    }
    if ($cambioSemanasSignificativo) {
        $semanas_info = [];
        if ($diferenciaSemanasCat > 0) {
            $semanas_info[] = "Cátedra (+" . $diferenciaSemanasCat . " semanas)";
        }
        if ($diferenciaSemanasOc > 0) {
            $semanas_info[] = "Ocasional (+" . $diferenciaSemanasOc . " semanas)";
        }
        if (!empty($semanas_info)) {
            $causas[] = "aumento en semanas de vinculación: " . implode(" y ", $semanas_info);
        }
    }
    
    if (!empty($causas)) {
        $interpretacion .= "<li><strong>Cambios en la composición</strong>: " . implode("; ", $causas) . "</li>";
    }
    
    // Causa 3: Aumento en puntos/horas
   // $interpretacion .= "<li><strong>Incremento en puntos/horas</strong>: Asignación de mayor carga académica por profesor</li>";
    
    // Causa 4: Ajustes salariales
//    $interpretacion .= "<li><strong>Posibles ajustes</strong> en el valor del punto o salarios base</li>";
    
    $interpretacion .= "</ul>";
    
    // Calcular costo adicional por profesor nuevo
    $costoPorProfesorNuevo = $diffTotalProyectado / $diffTotalProfesores;
    $interpretacion .= "<div class='nota-destacada'>Cada nuevo profesor representa un costo adicional promedio de <strong>$" . number_format($costoPorProfesorNuevo, 0, ',', '.') . "</strong>.</div>";
    
    $interpretaciones[] = [
        'icono' => 'fas fa-chart-line', // Icono de gráfico creciente
        'titulo' => 'Crecimiento significativo de planta y presupuesto',
        'texto' => $interpretacion,
        'tipo' => 'negativo' // O 'advertencia' si el crecimiento es muy alto
    ];
}
    // MOSTRAR SECCIÓN DE INTERPRETACIONES
    // ESTE ES EL ÚNICO BLOQUE PHP QUE DEBE MOSTRAR LAS INTERPRETACIONES
    // Asegúrate de que $interpretaciones esté definida y rellena ANTES de este punto en el código PHP.
    // (Tus cálculos para llenar $interpretaciones deben estar antes de esta sección HTML)
    if (!empty($interpretaciones)) {
        echo '<div class="interpretaciones-panel">'; // Este div reemplaza el antiguo <div class="card"> para interpretaciones
        echo '<h3 class="interpretaciones-titulo-seccion"><i class="fas fa-brain"></i> Interpretación de Resultados</h3>';

        // Mostrar nota de cambio de vigencia si aplica
        if ($vigencia_diferente) {
            echo '<div class="vigencia-nota">';
            echo '<i class="fas fa-info-circle"></i> ';
            echo "Periodo actual ($anio_semestre) y anterior ($periodo_anterior) son de vigencias diferentes ($anio_anterior → $anio_actual). ";
            echo "IPC estimado: " . number_format($ipc_estimado * 100, 2) . '%.';
            echo '</div>';
        }

        foreach ($interpretaciones as $interpretacion_item) { // Renombrado a $interpretacion_item para evitar conflicto con el array principal
            $claseTipo = "interpretacion-{$interpretacion_item['tipo']}";

            echo <<<HTML
            <div class="interpretacion-card {$claseTipo}">
                <div class="interpretacion-header">
                    <i class="{$interpretacion_item['icono']}"></i>
                    <h4>{$interpretacion_item['titulo']}</h4>
                </div>
                <div class="interpretacion-body">
                    <p>{$interpretacion_item['texto']}</p>
                </div>
            </div>
            HTML;
        }echo '</div>'; // Cierre de interpretaciones-panel
    }
?>

<!-- CSS adicional para la nota de vigencia -->
<style>
.vigencia-nota {
    background-color: #e3f2fd;
    border-left: 4px solid #2196F3;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 0 8px 8px 0;
    font-size: 0.9em;
}
</style>
<!-- CSS adicional para la nota de vigencia -->
<style>
/* ============================================== */
/* ===  VARIABLES DE COLOR Y FUENTES UNICAUCA === */
/* ============================================== */
:root {
    /* Colores principales de la Unicauca */
    --unicauca-azul: #0066cc;
    --unicauca-azul-claro: #1A75FF;
    --unicauca-azul-oscuro: #004080;
    --unicauca-amarillo: #FDB12D; /* Amarillo/naranja fuerte de la Unicauca */
    --unicauca-amarillo-oscuro: #E65C00;
    --unicauca-blanco: #FFFFFF;
    --unicauca-negro: #111827; /* Para texto principal */
    --unicauca-rojo: #CC3333; /* Para eliminar/advertencia */
    --unicauca-verde: #28a745; /* Para "nueva cédula" o éxito */

    /* Grises y colores de fondo/borde */
    --unicauca-gris-claro: #F3F4F6; /* Para fondos de secciones/tablas */
    --unicauca-gris-medio: #E5E7EB; /* Para bordes */
    --unicauca-gris-oscuro: #6c757d; /* Gris para elementos secundarios (como iconos, texto muted) */
    --unicauca-gris-claro-bg: #f8f9fa; /* Fondo degradado 1 */
    --unicauca-gris-mas-claro-bg: #e9ecef; /* Fondo degradado 2 */

    /* Colores de estado y variación */
    --unicauca-verde-exito: #28a745; /* Color para estado "Abierto" */
    --unicauca-rojo-peligro: #dc3545; /* Color para estado "Cerrado" */
    --unicauca-azul-vinculacion: #005c97; /* Color específico para el tipo de vinculación */

    /* Colores para botones */
    --unicauca-amarillo-boton: #FF6600; /* Naranja del botón "Agregar Profesor" */
    --unicauca-amarillo-boton-hover: #E65C00;

    /* Variables genéricas de tarjetas/dashboard (tus definiciones anteriores) */
    --primary-color: #696FC7;
    --text-dark: #2c3e50;
    --text-muted: #6c757d;
    --positive-bg: rgba(40, 167, 69, 0.15); /* Verde claro */
    --positive-text: #28a745; /* Verde oscuro */
    --negative-bg: rgba(220, 53, 69, 0.15); /* Rojo claro */
    --negative-text: #dc3545; /* Rojo oscuro */
    --card-shadow: 0 4px 12px rgba(0,0,0,0.1);
    --total-card-bg: rgba(105, 111, 199, 0.1); /* Fondo claro para tarjeta total */
    --total-card-border: rgba(105, 111, 199, 0.8); /* Borde para tarjeta total */
}

/* ============================ */
/* ===  ESTILOS GLOBALES    === */
/* ============================ */

.dashboard-profesores {
    max-width: 1800px;
    margin: 20px auto;
    padding: 0 15px;
}

/* ================================= */
/* ===  ESTILOS DE ENCABEZADO    === */
/* ================================= */
.navigation-header {
    background-color: var(--unicauca-azul);
    color: var(--unicauca-blanco);
    padding: 15px 30px;
    border-radius: 10px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
    flex-wrap: wrap;
    gap: 15px;
}

.navigation-header .header-info {
    display: flex;
    align-items: baseline;
    gap: 10px;
    flex-wrap: wrap;
}

.navigation-header h2,
.navigation-header h3 {
    color: var(--unicauca-blanco);
    margin: 0;
    padding: 0;
    font-weight: 600;
    line-height: 1.2;
}
.navigation-header h2 {
    font-size: 1.6em;
}
.navigation-header h3 {
    font-size: 1.4em;
}

.navigation-header .text-muted-white {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 1.4em;
    font-weight: bold;
}



/* =========================== */
/* ===  ESTILOS DE GRID    === */
/* =========================== */
.grid-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.grid-col {
    padding: 0;
}

.grid-row {
    display: contents;
}

/* ======================================== */
/* ===  ESTILOS DE CABECERAS DE PERIODO === */
/* ======================================== */
.periodo-info-container {
    background: linear-gradient(135deg, var(--unicauca-gris-claro-bg), var(--unicauca-gris-mas-claro-bg));
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
    flex-wrap: wrap;
    gap: 15px;
}

.periodo-actual-box {
    border-left: 4px solid var(--unicauca-azul);
}
.periodo-anterior-box {
    border-left: 4px solid var(--unicauca-gris-oscuro);
}

.periodo-title-h5 {
    font-size: 1.15rem;
    margin: 0;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    color: var(--unicauca-negro);
}

.periodo-title-h5 .fas {
    font-size: 1.3em;
    color: var(--unicauca-azul);
}
.periodo-anterior-box .periodo-title-h5 .fas {
    color: var(--unicauca-gris-oscuro);
}

.periodo-label,
.vinculacion-label {
    font-weight: 600;
    color: #343a40;
}
.periodo-value {
    font-weight: 700;
    color: var(--unicauca-azul-oscuro);
}
.vinculacion-type {
    font-weight: 700;
    color: var(--unicauca-azul-vinculacion);
}

.periodo-separator {
    color: var(--unicauca-gris-oscuro);
    margin: 0 0.5rem;
}

.semanas-badge {
    background-color: var(--unicauca-gris-oscuro);
    color: var(--unicauca-blanco);
    padding: 0.25em 0.6em;
    font-size: 0.85em;
    font-weight: 600;
    border-radius: 10rem;
    white-space: nowrap;
}

.estado {
    font-weight: 700;
    padding: 0.2em 0.5em;
    border-radius: 4px;
    white-space: nowrap;
}
.estado-abierto {
    color: var(--unicauca-verde-exito);
    background-color: rgba(40, 167, 69, 0.1);
}
.estado-cerrado {
    color: var(--unicauca-rojo-peligro);
    background-color: rgba(220, 53, 69, 0.1);
}

/* ========================================== */
/* ===  ESTILOS DE CAJAS / GRIDS GENERALES === */
/* ========================================== */
.container {
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 20px;
    max-width: 95%;
    margin: 0 auto;
    padding: 10px;
}
.box {
    flex: 0 0 49%;
    max-width: 49%;
    box-sizing: border-box;
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}
.box-white {
    background-color: var(--unicauca-blanco);
    border-color: #ccc;
}
.estado-container {
    min-height: 38px;
    padding: 5px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* =================================== */
/* ===  ESTILOS DE DASHBOARD/TARJETAS (NO INTERPRETACIONES) === */
/* =================================== */
.dashboard-title {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 30px;
    font-size: 1.8rem;
    font-weight: 700;
}
.card-container {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}
.card { /* Estilo base de tarjeta para otras secciones del dashboard */
    flex: 1;
    min-width: 250px;
    background: var(--unicauca-blanco);
    border-radius: 10px;
    padding: 20px;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
}
.card.total-card {
    background-color: var(--total-card-bg);
    border: 1px solid var(--total-card-border);
}
.card.total-card .card-title,
.card.total-card .card-main-value,
.card.total-card .card-subtext {
    color: var(--text-dark);
}
.card-percentage {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 5px;
    z-index: 1;
}
.card-title {
    color: #333;
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.1rem;
    padding-right: 60px;
}
.card-main-value {
    font-size: 2.2rem;
    font-weight: bold;
    color: var(--text-dark);
    margin-bottom: 10px;
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
}
.card-variation {
    font-size: 1rem;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 8px;
    vertical-align: middle;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.positive-alert {
    background-color: var(--negative-bg);
    color: var(--negative-text);
}
.negative-favorable {
    background-color: var(--positive-bg);
    color: var(--positive-text);
}
.card-subtext {
    font-size: 0.9rem;
    color: var(--text-muted);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    position: relative;
    min-height: 40px;
    padding-bottom: 20px;
}
.new-count.positive-alert {
    color: var(--negative-text)!important;
    font-weight: 600;
}
.removed-count.negative-favorable {
    color: var(--positive-text)!important;
    font-weight: 600;
}
.previous-count {
    position: absolute;
    bottom: 10px;
    right: 15px;
    font-weight: 600;
    font-size: 0.85rem;
    color: #333;
    background-color: #e2e6ea;
    padding: 5px 10px;
    border-radius: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    white-space: nowrap;
    transition: background-color 0.3s ease;
    cursor: default;
}
.previous-count:hover {
    background-color: #d6d8db;
}

/* ========================================= */
/* ===  ESTILOS DE GRÁFICOS E INTERPRETACIONES === */
/* ========================================= */

/* Contenedor principal para ambos paneles (gráficos e interpretaciones) */
.dashboard-visual-section {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    align-items: stretch; /* CRUCIAL: Estira los hijos a la altura del más alto */
}

/* Panel que contiene los dos gráficos (clase 'charts-panel') */
.charts-panel {
    flex: 2; /* Este panel tomará el doble de espacio horizontal */
    min-width: 400px; /* Ancho mínimo para el panel de gráficos */
    display: grid; /* Los gráficos individuales dentro de este panel se organizarán en un grid */
    grid-template-columns: 1fr 1fr; /* Dos columnas para los gráficos */
    gap: 20px; /* Espacio entre los gráficos */
    align-items: stretch; /* Estira las tarjetas de gráficos internas */
    background: white; /* Añadido fondo para el contenedor del gráfico */
    border-radius: 10px;
    box-shadow: var(--card-shadow);
    padding: 20px;
}

/* Estilo para cada tarjeta individual de gráfico */
.grafico-card {
    background: white; /* Se mantiene, pero el fondo principal ya viene del .charts-panel */
    border-radius: 10px;
    padding: 20px;
    box-shadow: none; /* Elimina la sombra duplicada ya que la tiene el contenedor padre */
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 250px; /* Altura mínima prudente para que los gráficos sean legibles */
}

/* Estilo para el elemento canvas dentro de la tarjeta de gráfico */
.grafico-card canvas {
    width: 100% !important;
    height: auto !important; /* MUY IMPORTANTE: Permite que la altura del canvas se ajuste dinámicamente */
    max-height: 500px; /* Límite para evitar gráficos absurdamente altos */
}

/* Panel de Interpretaciones (clase 'interpretaciones-panel') */
.interpretaciones-panel {
    flex: 1; /* Este panel tomará una parte del espacio horizontal */
    min-width: 300px; /* Ancho mínimo para el panel de interpretaciones */
    background: white;
    border-radius: 10px;
    box-shadow: var(--card-shadow);
    padding: 25px;
    border-top: 4px solid var(--primary-color);
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Título de la sección de Interpretaciones */
.interpretaciones-titulo-seccion {
    font-size: 1.5rem;
    color: var(--text-dark);
    margin-top: 0;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    border-bottom: 2px solid #ecf0f1;
    padding-bottom: 15px;
}
.interpretaciones-titulo-seccion .fas {
    color: var(--unicauca-azul-oscuro);
}

/* Estilos para las tarjetas de interpretación individuales */
.interpretacion-card {
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 0; /* Controlado por 'gap' en el padre */
    background: var(--unicauca-gris-claro-bg);
    border: 1px solid var(--unicauca-gris-medio);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.interpretacion-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.interpretacion-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    color: var(--text-dark);
}
.interpretacion-header .fas {
    font-size: 1.4rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--unicauca-gris-mas-claro-bg);
    color: var(--unicauca-azul-oscuro);
}

/* Colores para iconos y fondos de tarjetas de interpretación según tipo */
.interpretacion-card.interpretacion-positivo {
    border-left: 4px solid var(--unicauca-verde-exito);
    background-color: rgba(40, 167, 69, 0.1);
}
.interpretacion-card.interpretacion-positivo .interpretacion-header i {
    color: var(--unicauca-verde-exito);
    background: rgba(40, 167, 69, 0.15);
}

.interpretacion-card.interpretacion-negativo {
    background-color: rgba(220, 53, 69, 0.1);
    border-left: 5px solid var(--unicauca-rojo-peligro);
}
.interpretacion-card.interpretacion-negativo .interpretacion-header i {
    color: var(--unicauca-rojo-peligro);
    background: rgba(220, 53, 69, 0.15);
}

.interpretacion-card.interpretacion-advertencia {
    border-left: 4px solid #ffc107;
    background-color: rgba(255, 193, 7, 0.1);
}
.interpretacion-card.interpretacion-advertencia .interpretacion-header i {
    color: #ffc107;
    background: rgba(255, 193, 7, 0.15);
}

.interpretacion-card.interpretacion-neutro,
.interpretacion-card.interpretacion-info {
    border-left: 4px solid #17a2b8;
    background-color: rgba(23, 162, 184, 0.1);
}
.interpretacion-card.interpretacion-neutro .interpretacion-header i,
.interpretacion-card.interpretacion-info .interpretacion-header i {
    color: #17a2b8;
    background: rgba(23, 162, 184, 0.15);
}

.interpretacion-body p {
    color: #495057;
    line-height: 1.7;
    margin-bottom: 0;
}
.interpretacion-lista {
    padding-left: 20px;
    margin-top: 10px;
    list-style-type: disc;
}
.interpretacion-lista li {
    margin-bottom: 8px;
}

/* Nota de vigencia */
.vigencia-nota {
    background-color: #e3f2fd;
    border-left: 4px solid #2196F3;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 0 8px 8px 0;
    font-size: 0.9em;
    color: #2196F3;
}

/* Clase para texto destacado en interpretaciones */
.nota-destacada {
    background-color: rgba(253, 177, 45, 0.15);
    border-left: 4px solid var(--unicauca-amarillo-oscuro);
    padding: 8px 12px;
    border-radius: 4px;
    margin-top: 15px;
    font-weight: 600;
    font-size: 0.95em;
    color: var(--unicauca-negro);
}

/* =================================== */
/* ===  ESTILOS DE DASHBOARD GENERAL === */
/* =================================== */
.card-subtext {
    font-size: 0.9rem;
    color: var(--text-muted);
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    position: relative;
    min-height: 40px;
    padding-bottom: 20px;
}
.previous-count {
    position: absolute;
    bottom: 10px;
    right: 15px;
    font-weight: 600;
    font-size: 0.85rem;
    color: #333;
    background-color: #e2e6ea;
    padding: 5px 10px;
    border-radius: 20px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    white-space: nowrap;
    transition: background-color 0.3s ease;
    cursor: default;
}
.previous-count:hover {
    background-color: #d6d8db;
}

/* ============================ */
/* ===  ANIMACIONES & MISC  === */
/* ============================ */
@keyframes inflateButton {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}
.label-italic {
    font-style: italic;
}
#textoObservacion {
    white-space: pre-line;
}

/* ============================ */
/* ===  MEDIA QUERIES PARA RESPONSIVIDAD === */
/* ============================ */
.card-container {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    width: 100%; /* Asegura que ocupe todo el ancho disponible */
}

/* Estilo para todas las tarjetas (asegurar consistencia) */
.card {
    flex: 1; /* Esto hará que las tarjetas se expandan igualmente */
    min-width: 250px; /* Ancho mínimo para responsividad */
    max-width: calc(33.333% - 20px); /* Para 3 tarjetas con gap de 20px */
    display: flex;
    flex-direction: column;
}

/* Ajustes responsivos */
@media (max-width: 992px) {
    .card-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        justify-content: center;
        gap: 15px;
    }
    
    .card {
        max-width: 100%; /* En móvil ocupa todo el ancho del grid */
    }
}

/* Asegurar que todas las tarjetas tengan la misma altura */
.card-top-section {
    flex: 1; /* Hace que esta sección ocupe el espacio disponible */
    min-height: 120px; /* Altura mínima para consistencia */
}
@media (max-width: 768px) {
    /* Encabezado de navegación en móviles */
    .navigation-header {
        flex-direction: column;
        align-items: flex-start;
        padding: 15px 20px;
    }
    .navigation-header .header-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    .navigation-header h2,
    .navigation-header h3 {
        font-size: 1.4em;
    }
    .navigation-header .btn-back,
    .navigation-header .btn-chart {
        width: 100%;
        justify-content: center;
        margin-top: 10px;
    }

    /* Tarjetas del dashboard en móviles */
    .card {
        min-width: 100%;
    }

    /* Sección de gráficos e interpretaciones en móviles */
    .dashboard-visual-section {
        flex-direction: column; /* Apila los paneles verticalmente */
    }
    .charts-panel { /* Panel de gráficos en móviles */
        grid-template-columns: 1fr; /* Una sola columna para los gráficos */
        min-width: 100%;
        flex: none; /* Deshabilita el crecimiento flexible cuando están apilados */
        padding: 15px; /* Ajusta el padding para móviles */
    }
    .interpretaciones-panel { /* Panel de interpretaciones en móviles */
        min-width: 100%;
        flex: none; /* Deshabilita el crecimiento flexible cuando están apilados */
        padding: 15px; /* Ajusta el padding para móviles */
    }

    /* Cabeceras de período en móviles */
    .periodo-title-h5 {
        font-size: 1rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    .periodo-title-h5 .fas {
        margin-right: 5px;
    }
    .periodo-separator {
        display: none;
    }
    .btn-agregar-profesor {
        width: 100%;
        justify-content: center;
        margin-top: 10px;
    }
}

</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables PHP para las etiquetas de período (¡Asegúrate de que estas variables estén disponibles en el scope PHP!)
        const periodoActual = '<?= $anio_semestre ?>';
        const periodoAnterior = '<?= $periodo_anterior ?>';

        // Configuración común para ambos gráficos
        const commonChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Oculta la leyenda por defecto
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toLocaleString('es-CO');
                            }
                            return label;
                        }
                    }
                },
                datalabels: {
                    display: true,
                    color: '#333',
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return value.toLocaleString('es-CO');
                    }
                }
            },
            layout: {
                padding: {
                    top: 20,
                    right: 20,
                    bottom: 20,
                    left: 20
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        callback: function(value) {
                            return value.toLocaleString('es-CO');
                        }
                    },
                    grid: {
                        display: false // Oculta las líneas de la cuadrícula en el eje Y
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    // Ajustes para agrupar más las barras
                    categoryPercentage: 0.8, // Porcentaje del espacio de la categoría que ocupan las barras
                    barPercentage: 0.9 // Porcentaje de la categoría que ocupa la barra
                }
            }
        };

        // --- Gráfico de Cantidad de Profesores ---
        new Chart(
            document.getElementById('profesorCantidadChart').getContext('2d'),
            {
                type: 'bar',
                data: {
                    labels: [
                        'Ocasional (' + periodoAnterior + ')', 'Ocasional (' + periodoActual + ')',
                        'Cátedra (' + periodoAnterior + ')', 'Cátedra (' + periodoActual + ')',
                        'Total (' + periodoAnterior + ')', 'Total (' + periodoActual + ')'
                    ],
                    datasets: [{
                        label: 'Cantidad de Profesores',
                        data: [
                            <?= $totalProfesoresOcasionalAnterior ?>,
                            <?= $totalProfesoresOcasional ?>,
                            <?= $totalProfesoresCatedraAnterior ?>,
                            <?= $totalProfesoresCatedra ?>,
                            <?= $totalProfesoresTotalAnterior ?>,
                            <?= $totalProfesoresTotal ?>
                        ],
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.3)',    // Rojo claro anterior (Ocasional)
                            'rgba(220, 53, 69, 0.7)',    // Rojo actual (Ocasional)
                            'rgba(40, 167, 69, 0.3)',    // Verde claro anterior (Cátedra)
                            'rgba(40, 167, 69, 0.7)',    // Verde actual (Cátedra)
                            'rgba(105, 111, 199, 0.4)',  // Azul claro anterior (Total)
                            'rgba(105, 111, 199, 0.8)'   // Azul oscuro actual (Total)
                        ],
                        borderColor: [
                            'rgba(220, 53, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(105, 111, 199, 1)',
                            'rgba(105, 111, 199, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonChartOptions,
                    plugins: {
                        ...commonChartOptions.plugins,
                        title: {
                            display: true,
                            text: 'Comparación de Cantidad de Profesores',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: {
                                bottom: 20
                            }
                        }
                    },
                    scales: {
                        ...commonChartOptions.scales,
                        y: {
                            ...commonChartOptions.scales.y,
                            ticks: {
                                precision: 0,
                                callback: function(value) {
                                    return value.toLocaleString('es-CO');
                                }
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            }
        );

        // --- Gráfico de Valores Proyectados ---
        new Chart(
            document.getElementById('valoresProyectadosChart').getContext('2d'),
            {
                type: 'bar',
                data: {
                    labels: [
                        'Ocasional (' + periodoAnterior + ')', 'Ocasional (' + periodoActual + ')',
                        'Cátedra (' + periodoAnterior + ')', 'Cátedra (' + periodoActual + ')',
                        'Total (' + periodoAnterior + ')', 'Total (' + periodoActual + ')'
                    ],
                    datasets: [{
                        label: 'Valor Proyectado',
                        data: [
                            <?= $totalProyectadoOcasionalAnterior / 1000000 ?>,
                            <?= $totalProyectadoOcasional / 1000000 ?>,
                            <?= $totalProyectadoCatedraAnterior / 1000000 ?>,
                            <?= $totalProyectadoCatedra / 1000000 ?>,
                            <?= $totalProyectadoTotalAnterior / 1000000 ?>,
                            <?= $totalProyectadoTotal / 1000000 ?>
                        ],
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.3)',    // Rojo claro anterior (Ocasional)
                            'rgba(220, 53, 69, 0.7)',    // Rojo actual (Ocasional)
                            'rgba(40, 167, 69, 0.3)',    // Verde claro anterior (Cátedra)
                            'rgba(40, 167, 69, 0.7)',    // Verde actual (Cátedra)
                            'rgba(105, 111, 199, 0.4)',  // Azul claro anterior (Total)
                            'rgba(105, 111, 199, 0.8)'   // Azul oscuro actual (Total)
                        ],
                        borderColor: [
                            'rgba(220, 53, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(105, 111, 199, 1)',
                            'rgba(105, 111, 199, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonChartOptions,
                    plugins: {
                        ...commonChartOptions.plugins,
                        title: {
                            display: true,
                            text: 'Comparación de Valores Proyectados (en millones)',
                            font: {
                                size: 16,
                                weight: 'bold'
                            },
                            padding: {
                                bottom: 20
                            }
                        },
                        datalabels: {
                            ...commonChartOptions.plugins.datalabels,
                            formatter: function(value) {
                                return '$' + value.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + 'M';
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' millones';
                                }
                            }
                        }
                    },
                    scales: {
                        ...commonChartOptions.scales,
                        y: {
                            ...commonChartOptions.scales.y,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-CO', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + 'M';
                                }
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            }
        );
    });
             </script></div></div></div><div style='text-align: right; margin-top: 30px;'>
<a href='#seccionTablas' style='
    display: inline-block;
    background-color: #0066cc;
    color: white;
    padding: 10px 18px;
    font-size: 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: background-color 0.3s ease;'
    onmouseover=\"this.style.backgroundColor='#004999';\"
    onmouseout=\"this.style.backgroundColor='#0066cc';\">
    ↑ Volver arriba
</a>
</div>

    </body>
</html>
<?php
function obtenerCierreDeptoCatedra($departamento_id,$aniose) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_periodo.dp_estado_catedra FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo ='$aniose'";
        $result = $conn->query($sql);   
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dp_estado_catedra'] == 'ce') ? "CERRADO" : "ABIERTO";
        } else {
            return "estado depto Desconocida";
        }
    } // ocasional
    function obtenerCierreDeptoOcasional($departamento_id,$aniose) {
        $conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
        $sql = "SELECT depto_periodo.dp_estado_ocasional FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' and periodo ='$aniose'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ($row['dp_estado_ocasional'] == 'ce') ? "CERRADO" : "ABIERTO";
        } else {
            return "estado depto Desconocida";
        }
    } 
?>