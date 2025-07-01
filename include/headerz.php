<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

<?php
session_start();
$currentYear = date("Y");
?>
<?php
require 'cn.php';
if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];
} else {
    $nombre_sesion = "elmer jurado";
}
// Obtener la fecha actual
$currentDate = new DateTime();

// Obtener el año actual
$currentYear = $currentDate->format('Y');

// Obtener el mes actual
$currentMonth = $currentDate->format('m');


// Determinar el período actual
if ($currentMonth >= 7) {
    $periodo_work = $currentYear . '-2';
    $nextPeriod = ($currentYear + 1) . '-1';
    $previousPeriod = $currentYear . '-1';
} else {
    $periodo_work = $currentYear . '-1';
    $nextPeriod = $currentYear . '-2';
    $previousPeriod = ($currentYear - 1) . '-2';
}
echo "nombre sesion: ". $nombre_sesion;
$consultaf = "SELECT * FROM users WHERE users.Name= '$nombre_sesion'";
$resultadof = $con->query($consultaf);

while ($row = $resultadof->fetch_assoc()) {
    $nombre_usuario = $row['Name'];
    $email_user = $row['Email'];
    $email_fac = $row['email_padre'];
    $tipo_usuario = $row['tipo_usuario'];
    $depto_user= $row['fk_depto_user'];
    $id_user= $row['Id'];


    $where = "";
    if ($tipo_usuario== 3) {
        $where = "WHERE email_fac LIKE '%$email_fac%' and PK_DEPTO = '$depto_user' ";
    } else if  ($tipo_usuario== 2) {
        $where = "WHERE email_fac LIKE '%$email_fac%'";
    }
}

// Conectar a la base de datos
$con = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($con->connect_error) {
    die("Conexión fallida: " . $con->connect_error);
}

if ($tipo_usuario != 1) {
    $result = $con->query("SELECT PK_FAC, nombre_fac_minb, deparmanentos.depto_nom_propio, deparmanentos.PK_DEPTO
                           FROM facultad, deparmanentos 
                           $where
                           AND deparmanentos.FK_FAC = facultad.PK_FAC");
} else {
    $result = $con->query("SELECT PK_FAC, nombre_fac_minb, deparmanentos.depto_nom_propio, deparmanentos.PK_DEPTO 
                           FROM facultad, deparmanentos 
                           WHERE deparmanentos.FK_FAC = facultad.PK_FAC");
}

$departamentos = [];
while ($row = $result->fetch_assoc()) {
    $departamentos[] = $row;
}

$con->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud Aval Temporales</title>
<style>
/* Colores institucionales Unicauca */
:root {
    --unicauca-azul: #000066;            /* Azul principal */
    --unicauca-azul-oscuro: #000b41;    /* Azul oscuro, usado para submenús */
    --unicauca-rojo: #A61717;            /* Rojo institucional */
    --unicauca-rojo-claro: #D32F2F;      /* Rojo más claro */
    --unicauca-blanco: #FFFFFF;          /* Blanco */
    --unicauca-gris: #6C757D;            /* Gris para textos */
    --unicauca-gris-claro: #F8F9FA;      /* Un gris muy claro para fondos sutiles */
    --unicauca-gris-medio: #E9ECEF;      /* Gris un poco más oscuro para bordes */
    --unicauca-success: #28a745;         /* Verde para éxito/descarga XLS */
    --unicauca-info: #17a2b8;            /* Azul claro para información o 'reimprimir' si no se usa el azul principal */
    --unicauca-blue-light: #2196F3;      /* Un azul más claro para hover en el botón de reimprimir si se usa el azul principal */
    --unicauca-orange: #FF5722;          /* Un color naranja para acciones externas, por ejemplo */
    --unicauca-orange-dark: #E64A19;

    /* Nuevas variables para los colores solicitados */
    --nuevo-fondo-menu: #ECF0FF;
    --nueva-letra-menu: #1F2124;
}

/* Opción 2 - Efecto de pulso con colores Unicauca */
.pulse-badge {
    display: inline-block;
    background-color: var(--unicauca-rojo-claro);
    color: var(--unicauca-blanco);
    font-size: 0.7em;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
    animation: pulse 2s infinite;
    vertical-align: middle;
    font-weight: bold;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Opción 1 - Badge estático */
.new-badge {
    background-color: var(--unicauca-rojo);
    color: var(--unicauca-blanco);
    font-size: 0.6em;
    padding: 2px 5px;
    border-radius: 3px;
    margin-left: 5px;
    vertical-align: middle;
    font-weight: bold;
}

/* Estilos del encabezado MEJORADO */
header {
    background: var(--nuevo-fondo-menu); /* Usamos la nueva variable para el fondo */
    width: 100%;
    height: 70px;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 99;
    color: var(--nueva-letra-menu); /* Cambiamos el color de texto general del header */
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Sombra más pronunciada */
    border-bottom: 3px solid var(--unicauca-rojo); /* Borde inferior más grueso y distintivo */
}

/* Estilos del título del encabezado */
header h1 {
    margin: 0;
    font-size: 18px; /* Un poco más grande */
    font-weight: bold;
    display: flex;
    align-items: center;
    color: var(--nueva-letra-menu); /* Asegura que el color del texto del título sea el nuevo */
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2); /* Sutil sombra de texto */
    letter-spacing: 0.5px; /* Ligeramente más espaciado */
    font-family: 'Open Sans', sans-serif; /* <-- Añadido aquí */

}

/* Estilos del menú principal */
nav {
    display: flex;
    align-items: center; /* Asegura que el menú esté alineado verticalmente */
}

nav ul {
    list-style: none;
    display: flex;
    padding: 0;
    margin: 0;
}

nav ul li {
    position: relative;
    /* Añadir un margen entre los elementos del menú para separarlos visualmente */
    margin: 0 8px;
}

nav ul li a {
    text-decoration: none;
    display: block;
    padding: 15px 18px; /* Ajustar padding para más espacio sin afectar el alto del header */
    color: var(--nueva-letra-menu); /* Cambiamos el color de la letra de los ítems del menú */
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    border-radius: 4px; /* Un poco de border-radius para un look más suave */
    font-size: 0.9em;
        font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */

}

/* MODIFICADO: Aplicar estilos de hover también al estado activo */
nav ul li a:hover,
nav ul li.active > a { /* Cuando el LI tiene la clase 'active' */
    background-color: rgba(0, 0, 0, 0.1); /* Fondo más oscuro y sutil al pasar el mouse/activo */
    color: var(--unicauca-azul); /* Puedes usar un color que contraste bien, por ejemplo el azul Unicauca */
    transform: translateY(-2px); /* Pequeño efecto de elevación */
}

/* MODIFICADO: Aplicar ::after de hover también al estado activo */
nav ul li a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: var(--unicauca-rojo);
    transition: all 0.3s ease;
}

nav ul li a:hover::after,
nav ul li.active > a::after { /* Cuando el LI tiene la clase 'active' */
    width: 100%;
    left: 0;
}

/* Submenús */
nav ul li ul.submenu {
    display: none; /* Ocultar por defecto */
    position: absolute;
    top: 100%;
    left: 0;
    list-style: none;
    padding: 0;
    margin: 0;
    background: var(--nuevo-fondo-menu); /* **CAMBIADO: Fondo del submenú** */
    border: 1px solid rgba(0, 0, 0, 0.15); /* **CAMBIADO: Borde del submenú (más oscuro para contraste)** */
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4); /* Sombra más profunda */
    z-index: 1000;
    min-width: 220px; /* Ancho mínimo para submenú */
    border-top: 4px solid var(--unicauca-rojo); /* Borde superior más grueso */
    border-radius: 0 0 6px 6px; /* Bordes redondeados solo abajo */
    overflow: hidden; /* Para que el border-radius funcione bien con el contenido */
}

nav ul li ul.submenu li {
    width: 100%;
    margin: 0; /* Eliminar margen extra en los ítems del submenú */
}

nav ul li ul.submenu li a {
    padding: 12px 20px;
    color: var(--nueva-letra-menu); /* **CAMBIADO: Color de la letra de los ítems del submenú** */
    border-bottom: 1px solid rgba(0, 0, 0, 0.08); /* **CAMBIADO: Borde inferior más notorio y acorde al nuevo fondo** */
}

nav ul li ul.submenu li:last-child a {
    border-bottom: none; /* Eliminar el borde en el último elemento del submenú */
}

nav ul li ul.submenu li a:hover {
    background-color: rgba(0, 0, 0, 0.05); /* **CAMBIADO: Fondo más claro al pasar el ratón en submenú** */
    padding-left: 25px;
    color: var(--unicauca-azul); /* **CAMBIADO: Color de la letra en hover del submenú (azul Unicauca)** */
}

/* MODIFICADO: Mostrar submenu al hacer hover en el padre O si el padre tiene la clase 'active' */
nav ul li:hover ul.submenu,
nav ul li.active > ul.submenu { /* Cuando el LI tiene la clase 'active' */
    display: block;
}

/* Estilos para los elementos 'a' dentro del submenu cuando están activos */
nav ul li ul.submenu li.active a {
    background-color: rgba(0, 0, 0, 0.1); /* **CAMBIADO: Fondo distintivo para el sub-ítem activo** */
    font-weight: bold; /* Hacer el texto más audaz */
    color: var(--unicauca-rojo-claro); /* Puedes usar un color diferente si lo deseas */
}

/* Submenús anidados */
nav ul li ul.submenu li ul.submenu {
    left: 100%;
    top: 0;
    background: var(--nuevo-fondo-menu); /* **CAMBIADO: Fondo del submenú anidado** */
    border-left: 4px solid var(--unicauca-rojo); /* Borde lateral para anidados */
    border-top: none; /* Eliminar borde superior duplicado */
    border-radius: 0 6px 6px 0; /* Bordes redondeados a la derecha */
}

/* Estilos del login/información de usuario MEJORADO */
#login {
    color: var(--nueva-letra-menu); /* Cambia el color del texto del usuario */
    font-family: Arial, sans-serif;
    font-size: 12px;
    display: flex;
    align-items: center;
    background-color: rgba(0, 0, 0, 0.1); /* Un fondo sutil para el área de login que contraste con el nuevo fondo */
    padding: 8px 15px;
    border-radius: 8px; /* Bordes más redondeados */
    box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1); /* Sombra interna sutil */
    gap: 15px; /* Espacio entre el texto de usuario y el botón de logout, y el botón de presupuesto */
}

#login i {
    font-style: normal; /* Para que la 'i' de "usuario" no se vea cursiva si no es necesario */
    color: var(--nueva-letra-menu); /* Color para el texto del usuario */
        font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */

}

#login a { /* Esto aplica a todos los <a> dentro de #login, incluyendo el de logout */
    color: var(--unicauca-blanco); /* Mantén el blanco para los botones dentro de login */
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
}

/* Estilos específicos para el botón de Logout */
#login .btn-logout {
    background-color: var(--unicauca-rojo);
    border: 1px solid var(--unicauca-rojo);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);    font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */

}

#login .btn-logout:hover {
    background-color: var(--unicauca-rojo-claro);
    border-color: var(--unicauca-rojo-claro);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

/* Nuevo estilo para el botón de Presupuesto (externo) */
.btn-external-link {
    background-color: var(--unicauca-azul) !important; /* Color distintivo para enlace externo */
    border: 1px solid var(--unicauca-azul-oscuro) !important;
    color: var(--unicauca-blanco) !important;
    text-decoration: none !important;
    padding: 6px 12px !important;
    border-radius: 6px !important;
    font-weight: 500 !important;
    transition: all 0.3s ease !important; /* Keep transition for smooth effect */
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
        font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */

}

.btn-external-link:hover {
    background-color: var(--nuevo-fondo-menu) !important;
    border-color: var(--unicauca-azul-oscuro) !important;
    color: var(--unicauca-azul-oscuro) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3) !important;
}

.btn-external-link .fas,
.btn-external-link .fa-solid { /* Asegurar que los iconos tengan margen */
    margin-right: 5px;
}

/* Contenido principal */
#contenido {
    margin-top: 70px;
    padding: 20px;
}

/* !!! CAMBIOS PRINCIPALES PARA EL ESTADO ACTIVO !!! */
/* Estilos para los elementos 'a' dentro del submenu cuando están activos */
nav ul li ul.submenu li.active a {
    background-color: rgba(0, 0, 0, 0.1); /* Fondo distintivo para el sub-ítem activo */
    font-weight: bold; /* Hacer el texto más audaz */
    color: var(--unicauca-rojo-claro); /* Puedes usar un color diferente si lo deseas */
}

/* Si quieres una línea diferente para los elementos de submenu activos */
nav ul li ul.submenu li.active a::after {
    content: '';
    position: absolute;
    top: 0; /* Coloca la línea arriba para un submenú */
    left: 0;
    width: 3px; /* Es una línea vertical */
    height: 100%;
    background: var(--unicauca-rojo);
}

#login a:hover {
    background-color: var(--unicauca-rojo-claro); /* Rojo más claro al pasar el ratón */
    border-color: var(--unicauca-rojo-claro);
    transform: translateY(-1px); /* Pequeña elevación */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

/* Contenido principal */
#contenido {
    margin-top: 70px;
    padding: 20px;
}

/* Efecto activo para el menú actual */
.current-menu-item {
    background-color: rgba(255, 255, 255, 0.15); /* Fondo más claro para el activo */
    border-radius: 4px; /* Asegurar que el activo también tenga bordes redondeados */
}

.current-menu-item a::after {
    width: 100% !important;
    left: 0 !important;
}

/* --- Botones Genéricos y de Acción (Ajustados para consistencia) --- */

/* Nuevo estilo para el botón "Reimprimir Oficio" */
.unacauca-btn-reprint {
    background-color: var(--unicauca-azul); /* Distinct color: Unicauca Blue */
    border-color: var(--unicauca-azul);
    color: white;
    font-weight: 600;
    padding: 12px 25px; /* Matches padding of other large buttons */
    border-radius: 12px; /* Applying the desired border-radius */
    transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.2s ease;
    font-size: 1.1em;
    width: 100%; /* Ensures it takes full width in d-grid */
    display: flex; /* Use flexbox to center content (text and icon) */
    align-items: center; /* Vertically center content */
    justify-content: center; /* Horizontally center content */
    text-decoration: none; /* In case it's ever used as an <a> tag */
}

.unacauca-btn-reprint:hover {
    background-color: var(--unicauca-azul-oscuro); /* Darker blue on hover */
    border-color: var(--unicauca-azul-oscuro);
    transform: translateY(-2px); /* Subtle lift effect */
}

/* Ensure other large buttons also have width: 100% and proper padding */
/* This is crucial for consistent sizing within d-grid */
.btn-unicauca-primary.btn-lg,
.btn-unicauca-success.btn-lg {
    padding: 12px 25px; /* Standardize padding if not already */
    width: 100%; /* Ensure full width in d-grid */
    border-radius: 12px; /* Ensure consistent border-radius */
    display: flex; /* For consistent centering of icon/text */
    align-items: center;
    justify-content: center;
}

/* Specific styling for the icons to ensure consistent spacing */
.btn-unicauca-primary.btn-lg .fas,
.btn-unicauca-success.btn-lg .fas,
.unacauca-btn-reprint .fas {
    margin-right: 8px; /* Consistent spacing for icons */
}
</style>
</head>
<body>
<header>
    <h1>Solicitud Aval - Profesores Temporales</h1>
    <nav>
        <ul>
            <li class="<?= ($active_menu_item == 'inicio') ? 'active' : '' ?>">
                <a href="../../temporales/menu_inicio.php">Inicio</a>
            </li>

            <?php if ($tipo_usuario == 3): ?>
                <li class="menu-item <?= ($active_menu_item == 'gestion_depto') ? 'active' : '' ?>">
                    <a href="#" title="Administrar solicitud inicial para el próximo periodo; dar visto bueno y enviarlas a la facultad para su revisión">
                        Gestión Depto
                    </a>
                    <ul class="submenu">
                        <?php foreach ($departamentos as $departamento): ?>
                            <?php if ($tipo_usuario == 1): // Usuario tipo 1: Mostrar todos los periodos ?>
                                <li class="<?= ($active_menu_item == 'gestion_depto' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                                    <a href="#" class="periodo-link"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"
                                        data-anio-semestre="<?php echo $previousPeriod; ?>"><?php echo $previousPeriod; ?></a>
                                </li>
                                <li class="<?= ($active_menu_item == 'gestion_depto' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                                    <a href="#" class="periodo-link"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"
                                        data-anio-semestre="<?php echo $periodo_work; ?>"><?php echo $periodo_work; ?></a>
                                </li>
                            <?php elseif ($tipo_usuario == 3): // Usuario tipo 3: Mostrar periodo actual y siguiente ?>
                                <li class="<?= ($active_menu_item == 'gestion_depto' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                                    <a href="#" class="periodo-link"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"
                                        data-anio-semestre="<?php echo $periodo_work; ?>"><?php echo $periodo_work; ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="<?= ($active_menu_item == 'gestion_depto' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                                <a href="#" class="periodo-link"
                                    data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                    data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"
                                    data-anio-semestre="<?php echo $nextPeriod; ?>"><?php echo $nextPeriod; ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($tipo_usuario == 1 || $tipo_usuario == 2): ?>
                <li class="menu-item <?= ($active_menu_item == 'gestion_facultad') ? 'active' : '' ?>">
                    <a href="#" title="Gestionar solicitud inicial de vinculación de temporales para el periodo siguiente">
                        Gestión Facultad
                    </a>
                    <ul class="submenu">
                        <?php if ($tipo_usuario == 1): // Mostrar todos los periodos para tipo 1 ?>
                            <li class="<?= ($active_menu_item == 'gestion_facultad' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                                <a href="#" class="report-link" data-facultad-id="<?php //echo $departamento['PK_FAC']; ?>" data-anio-semestre="<?php echo $previousPeriod; ?>">
                                    <?php echo $previousPeriod; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($tipo_usuario == 1 || $tipo_usuario == 2 || $tipo_usuario == 3): // Mostrar solo el próximo periodo para tipos 1, 2 y 3 ?>
                        <li class="<?= ($active_menu_item == 'gestion_facultad' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                                <a href="#" class="report-link" data-facultad-id="<?php echo $departamento['PK_FAC']; ?>" data-anio-semestre="<?php echo $periodo_work; ?>">
                                    <?php echo $periodo_work; ?>
                                </a>
                            </li>
                       
                            <li class="<?= ($active_menu_item == 'gestion_facultad' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                                <a href="#" class="report-link" data-facultad-id="<?php echo $departamento['PK_FAC']; ?>" data-anio-semestre="<?php echo $nextPeriod; ?>">
                                    <?php echo $nextPeriod; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <li class="menu-item <?= ($active_menu_item == 'comparativo') ? 'active' : '' ?>">
    <a href="#" title="comparativo profesores periodo actual vs anterior">
        Comparativo <span class="new-badge">New!</span>
    </a>
    <ul class="submenu">
        <?php if ($tipo_usuario == 1 || $tipo_usuario == 2 || $tipo_usuario == 3): // Mostrar todos los periodos para tipo 1 ?>
            <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                <a href="#" class="report-linkb"
                   data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                   data-anio-semestre="<?php echo $previousPeriod; ?>"
                   data-departamento-id="<?php echo $departamento['PK_DEPTO']; // Add this line ?>">
                    <?php echo $previousPeriod; ?>
                </a>
            </li>
            <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                <a href="#" class="report-linkb"
                   data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                   data-anio-semestre="<?php echo $periodo_work; ?>"
                   data-departamento-id="<?php echo $departamento['PK_DEPTO']; // Add this line ?>">
                    <?php echo $periodo_work; ?>
                </a>
            </li>
        <?php endif; ?>
        <?php if ($tipo_usuario == 1 || $tipo_usuario == 2 || $tipo_usuario == 3): // Mostrar solo el próximo periodo para tipos 1, 2 y 3 ?>
            <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                <a href="#" class="report-linkb"
                   data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                   data-anio-semestre="<?php echo $nextPeriod; ?>"
                   data-departamento-id="<?php echo $departamento['PK_DEPTO']; // Add this line ?>">
                    <?php echo $nextPeriod; ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</li>             <li class="submenu-container <?= ($active_menu_item == 'novedades') ? 'active' : '' ?>" style="display: none;">
                <a href="#" title="Novedades que se presentan para los profesores temporales vinculados en el periodo actual">
                    Novedades
                </a>
                <ul class="submenu novedades-submenu">
                    <?php
                    $periodosMostrados = [];
                    if ($tipo_usuario == 1):
                        foreach ($departamentos as $departamento):
                            if (!in_array($previousPeriod, $periodosMostrados)):
                                $periodosMostrados[] = $previousPeriod; ?>
                                <li class="<?= ($active_menu_item == 'novedades' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                                    <a href="#" class="novedades-periodo"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-anio-semestre="<?php echo $previousPeriod; ?>"
                                        data-tipo-usuario="<?php echo $tipo_usuario; ?>"
                                        data-email-user="<?php echo $email_user; ?>">
                                        <?php echo $previousPeriod; ?>
                                    </a>
                                </li>
                            <?php endif;
                            if (!in_array($periodo_work, $periodosMostrados)):
                                $periodosMostrados[] = $periodo_work; ?>
                                <li class="<?= ($active_menu_item == 'novedades' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                                    <a href="#" class="novedades-periodo"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-anio-semestre="<?php echo $periodo_work; ?>"
                                        data-tipo-usuario="<?php echo $tipo_usuario; ?>"
                                        data-email-user="<?php echo $email_user; ?>">
                                        <?php echo $periodo_work; ?>
                                    </a>
                                </li>
                            <?php endif;
                            if (!in_array($nextPeriod, $periodosMostrados)):
                                $periodosMostrados[] = $nextPeriod; ?>
                                <li class="<?= ($active_menu_item == 'novedades' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                                    <a href="#" class="novedades-periodo"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-anio-semestre="<?php echo $nextPeriod; ?>"
                                        data-tipo-usuario="<?php echo $tipo_usuario; ?>"
                                        data-email-user="<?php echo $email_user; ?>">
                                        <?php echo $nextPeriod; ?>
                                    </a>
                                </li>
                            <?php endif;
                        endforeach;
                    elseif ($tipo_usuario == 2 || $tipo_usuario == 3): // Caso para tipo usuario 2 o 3 - Solo ver $periodo_work (adaptado temporalmente)
                        foreach ($departamentos as $departamento):
                            if (!in_array($previousPeriod, $periodosMostrados)):
                                $periodosMostrados[] = $previousPeriod; ?>
                                <li class="<?= ($active_menu_item == 'novedades' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                                    <a href="#" class="novedades-periodo"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-anio-semestre="<?php echo $previousPeriod; ?>"
                                        data-tipo-usuario="<?php echo $tipo_usuario; ?>"
                                        data-email-user="<?php echo $email_user; ?>">
                                        <?php echo $previousPeriod; ?>
                                    </a>
                                </li>
                            <?php endif;
                            if (!in_array($periodo_work, $periodosMostrados)):
                                $periodosMostrados[] = $periodo_work; ?>
                                <li class="<?= ($active_menu_item == 'novedades' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                                    <a href="#" class="novedades-periodo"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-anio-semestre="<?php echo $periodo_work; ?>"
                                        data-tipo-usuario="<?php echo $tipo_usuario; ?>"
                                        data-email-user="<?php echo $email_user; ?>">
                                        <?php echo $periodo_work; ?>
                                    </a>
                                </li>
                            <?php endif;
                            if (!in_array($nextPeriod, $periodosMostrados)):
                                $periodosMostrados[] = $nextPeriod; ?>
                                <li class="<?= ($active_menu_item == 'novedades' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                                    <a href="#" class="novedades-periodo"
                                        data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                                        data-anio-semestre="<?php echo $nextPeriod; ?>"
                                        data-tipo-usuario="<?php echo $tipo_usuario; ?>"
                                        data-email-user="<?php echo $email_user; ?>">
                                        <?php echo $nextPeriod; ?>
                                    </a>
                                </li>
                            <?php endif;
                        endforeach;
                    endif; ?>
                </ul>
            </li>

            <?php if (
                $tipo_usuario == 1
                && (
                    $id_user == 92
                    || $id_user == 93
                    || $id_user == 94 || $id_user == 4
                )
            ): ?>
                <li class="menu-item <?= ($active_menu_item == 'observaciones') ? 'active' : '' ?>">
                    <a href="#" title="numero de observaciones labor">
                        Observaciones <span class="new-badge">New!</span>
                    </a>
                    <ul class="submenu">
                        <?php if ($tipo_usuario == 1): // Mostrar todos los periodos para tipo 1 ?>
                            <li class="<?= ($active_menu_item == 'observaciones' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                                <a href="#" class="report-linkc" data-facultad-id="<?php //echo $departamento['PK_FAC']; ?>" data-anio-semestre="<?php echo $previousPeriod; ?>">
                                    <?php echo $previousPeriod; ?>
                                </a>
                            </li>
                            <li class="<?= ($active_menu_item == 'observaciones' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                                <a href="#" class="report-linkc" data-facultad-id="<?php echo $departamento['PK_FAC']; ?>" data-anio-semestre="<?php echo $periodo_work; ?>">
                                    <?php echo $periodo_work; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if ($tipo_usuario == 1 || $tipo_usuario == 2 || $tipo_usuario == 3): // Mostrar solo el próximo periodo para tipos 1, 2 y 3 ?>
                            <li class="<?= ($active_menu_item == 'observaciones' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                                <a href="#" class="report-linkc" data-facultad-id="<?php echo $departamento['PK_FAC']; ?>" data-anio-semestre="<?php echo $nextPeriod; ?>">
                                    <?php echo $nextPeriod; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($tipo_usuario == 1 && ! in_array($id_user, [92, 93, 94])): ?>
                <li class="<?= ($active_menu_item == 'gestion_periodos') ? 'active' : '' ?>">
                    <a href="../../temporales/gestion_periodos.php">Gestión periodos</a>
                </li>
            <?php endif; ?>

            <?php if ($tipo_usuario == 1): ?>
                <li class="<?= ($active_menu_item == 'pb_graficos') ? 'active' : '' ?>">
                    <a href="../../temporales/powerbics.php">PB-Gráficos</a>
                </li>
            <?php endif; ?>

            <li class="<?= ($active_menu_item == 'video_tutorial') ? 'active' : '' ?>">
                <a href="../../temporales/tutorial.php?tipo_usuario=<?php echo urlencode($tipo_usuario); ?>">Video tutorial</a>
            </li>
        </ul>
    </nav>
    <?php
        // Definir los IDs de usuario permitidos (92, 93, 94, 4) para Presupuesto
        $usuarios_presupuesto_permitidos = [92, 4]; // Mantenemos los mismos de tu lógica original

        // MOVER el enlace de Presupuesto aquí
        if ($tipo_usuario == 1 && in_array($id_user, $usuarios_presupuesto_permitidos)):
    ?>
            <a href="/presupuesto_novedades/" class="btn-external-link" title="Módulo de novedades presupuestales" target="_blank" style="border-radius: 25px; padding: 10px 20px;">
                <i class="fas fa-money-bill-wave me-2"></i> Presupuesto
            </a>
    <?php endif; ?>
    <div id="login">
        <?php
            if (isset($_SESSION['loggedin'])) {
                if ($tipo_usuario==3) {
                    echo "<i>depto: ".$departamento['depto_nom_propio']. "</i> - " ;
                }
                echo "<i> usuario: " . $_SESSION['name'] . "</i> <a href='../../temporales/logout.php' class='btn-logout'>Logout</a>";
            } else {
                echo "SESSION loggedin: " . ($_SESSION['loggedin'] ? 'true' : 'false') . "<br>";

                echo "Email fac: " . $email_fac . "<br>";
                echo "<div class='alert alert-danger mt-4' role='alert'>
                    <h4>You need to login to access this page.</h4>
                    <p><a href='/temporales/index.html'>Login Here!</a></p>
                </div>";
            }
        ?>
    </div>
</header>
    <div id="contenido">
        </div>
   
    <script>
    // Ajustar evento para los enlaces de los periodos
document.querySelectorAll('.periodo-link').forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        var facultadId = this.dataset.facultadId;
        var departamentoId = this.dataset.departamentoId;
        var anioSemestre = this.dataset.anioSemestre;

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../temporales/consulta_todo_depto.php';

        var inputFacultad = document.createElement('input');
        inputFacultad.type = 'hidden';
        inputFacultad.name = 'facultad_id';
        inputFacultad.value = facultadId;
        form.appendChild(inputFacultad);

        var inputDepartamento = document.createElement('input');
        inputDepartamento.type = 'hidden';
        inputDepartamento.name = 'departamento_id';
        inputDepartamento.value = departamentoId;
        form.appendChild(inputDepartamento);

        var inputAnioSemestre = document.createElement('input');
        inputAnioSemestre.type = 'hidden';
        inputAnioSemestre.name = 'anio_semestre';
        inputAnioSemestre.value = anioSemestre;
        form.appendChild(inputAnioSemestre);

        document.body.appendChild(form);
        form.submit();
    });
});

// Evento para el enlace de novedades
document.querySelectorAll('.novedades-periodo').forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        
        var facultadId = this.dataset.facultadId;
        var departamentoId = this.dataset.departamentoId;
        var anioSemestre = this.dataset.anioSemestre;
        var tipoUsuario = this.dataset.tipoUsuario; // Nuevo dato
        var emailUser = this.dataset.emailUser;     // Nuevo dato

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../temporales/gestion_novedades.php'; // Página para gestionar novedades

        // Facultad
        if (facultadId) {
            var inputFacultad = document.createElement('input');
            inputFacultad.type = 'hidden';
            inputFacultad.name = 'facultad_id';
            inputFacultad.value = facultadId;
            form.appendChild(inputFacultad);
        }

        // Departamento
        if (departamentoId) {
            var inputDepartamento = document.createElement('input');
            inputDepartamento.type = 'hidden';
            inputDepartamento.name = 'departamento_id';
            inputDepartamento.value = departamentoId;
            form.appendChild(inputDepartamento);
        }

        // Año-Semestre
        var inputAnioSemestre = document.createElement('input');
        inputAnioSemestre.type = 'hidden';
        inputAnioSemestre.name = 'anio_semestre';
        inputAnioSemestre.value = anioSemestre;
        form.appendChild(inputAnioSemestre);

        // Tipo Usuario
        var inputTipoUsuario = document.createElement('input');
        inputTipoUsuario.type = 'hidden';
        inputTipoUsuario.name = 'tipo_usuario';
        inputTipoUsuario.value = tipoUsuario;
        form.appendChild(inputTipoUsuario);

        // Email User
        var inputEmailUser = document.createElement('input');
        inputEmailUser.type = 'hidden';
        inputEmailUser.name = 'email_user';
        inputEmailUser.value = emailUser;
        form.appendChild(inputEmailUser);

        document.body.appendChild(form);
        form.submit();
    });
});
        
        document.querySelectorAll('.report-link').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var facultadId = this.dataset.facultadId;
                var anioSemestre = this.dataset.anioSemestre;

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../temporales/report_depto_full.php';

                var inputFacultad = document.createElement('input');
                inputFacultad.type = 'hidden';
                inputFacultad.name = 'facultad_id';
                inputFacultad.value = facultadId;
                form.appendChild(inputFacultad);

                var inputAnioSemestre = document.createElement('input');
                inputAnioSemestre.type = 'hidden';
                inputAnioSemestre.name = 'anio_semestre';
                inputAnioSemestre.value = anioSemestre;
                form.appendChild(inputAnioSemestre);

                document.body.appendChild(form);
                form.submit();
            });
        });
        document.querySelectorAll('.report-linkb').forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            var facultadId = this.dataset.facultadId;
            var anioSemestre = this.dataset.anioSemestre;
            var departamentoId = this.dataset.departamentoId; // Get the new data attribute

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../temporales/report_depto_comparativo.php';

            var inputFacultad = document.createElement('input');
            inputFacultad.type = 'hidden';
            inputFacultad.name = 'facultad_id';
            inputFacultad.value = facultadId;
            form.appendChild(inputFacultad);

            var inputAnioSemestre = document.createElement('input');
            inputAnioSemestre.type = 'hidden';
            inputAnioSemestre.name = 'anio_semestre';
            inputAnioSemestre.value = anioSemestre;
            form.appendChild(inputAnioSemestre);

            // Create and append the hidden input for departamento_id
            var inputDepartamento = document.createElement('input');
            inputDepartamento.type = 'hidden';
            inputDepartamento.name = 'departamento_id';
            inputDepartamento.value = departamentoId;
            form.appendChild(inputDepartamento);

            document.body.appendChild(form);
            form.submit();
        });
    });
        
        
              document.querySelectorAll('.report-linkc').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var facultadId = this.dataset.facultadId;
                var anioSemestre = this.dataset.anioSemestre;

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../temporales/report_glosas.php';

                var inputFacultad = document.createElement('input');
                inputFacultad.type = 'hidden';
                inputFacultad.name = 'facultad_id';
                inputFacultad.value = facultadId;
                form.appendChild(inputFacultad);

                var inputAnioSemestre = document.createElement('input');
                inputAnioSemestre.type = 'hidden';
                inputAnioSemestre.name = 'anio_semestre';
                inputAnioSemestre.value = anioSemestre;
                form.appendChild(inputAnioSemestre);

                document.body.appendChild(form);
                form.submit();
            });
        });
                    document.querySelectorAll('.report-linkd').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var facultadId = this.dataset.facultadId;
                var anioSemestre = this.dataset.anioSemestre;

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../temporales/report_depto_comparativo_costos.php';

                var inputFacultad = document.createElement('input');
                inputFacultad.type = 'hidden';
                inputFacultad.name = 'facultad_id';
                inputFacultad.value = facultadId;
                form.appendChild(inputFacultad);

                var inputAnioSemestre = document.createElement('input');
                inputAnioSemestre.type = 'hidden';
                inputAnioSemestre.name = 'anio_semestre';
                inputAnioSemestre.value = anioSemestre;
                form.appendChild(inputAnioSemestre);

                document.body.appendChild(form);
                form.submit();
            });
        });
</script>

</body>
</html>
