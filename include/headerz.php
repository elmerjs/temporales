<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">

<?php
session_start();
$currentYear = date("Y");
?>
<?php
require 'cn.php';
if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];
        $fk_fac_user = $_SESSION['fk_fac_user'];

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
//echo "nombre sesion: ". $nombre_sesion;
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
    $result = $con->query("SELECT PK_FAC, nombre_fac_min, deparmanentos.depto_nom_propio, deparmanentos.PK_DEPTO
                           FROM facultad, deparmanentos 
                           $where
                           AND deparmanentos.FK_FAC = facultad.PK_FAC");
} else {
    $result = $con->query("SELECT PK_FAC, nombre_fac_min, deparmanentos.depto_nom_propio, deparmanentos.PK_DEPTO 
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
    :root {
    --unicauca-azul: #000066;            /* Azul principal */
    --unicauca-azul-oscuro: #000b41;    /* Azul oscuro */
    --unicauca-rojo: #A61717;            /* Rojo institucional */
    --unicauca-rojo-claro: #D32F2F;      /* Rojo más claro */
    --unicauca-blanco: #FFFFFF;          /* Blanco */
    --unicauca-gris: #6C757D;            /* Gris para textos */
    --unicauca-gris-claro: #F8F9FA;      /* Un gris muy claro para fondos sutiles */
    --unicauca-gris-medio: #E9ECEF;      /* Gris un poco más oscuro para bordes */

    /* Nuevas variables para los colores solicitados */
    --nuevo-fondo-menu: #ECF0FF; /* Fondo claro para la barra de menú */
    --nueva-letra-menu: #1F2124; /* Letra oscura para los menús */
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
    background: white; /* Fondo blanco para el header */
    width: 100%;
    height: 120px; /* La altura total del header */
    position: fixed;
    top: 0;
    left: 0;
    z-index: 99;
    color: var(--nueva-letra-menu);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    border-bottom: 3px solid var(--unicauca-gris-claro);

    display: grid; /* ¡Clave! Usamos Grid para organizar las filas */
    grid-template-rows: 60px 60px; /* Dos filas, cada una de 60px para sumar los 120px */
    grid-template-columns: 1fr; /* Una columna que ocupe todo el ancho */
    padding: 0; /* Quita el padding del header principal para que las filas internas lo manejen */
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
        margin-left: auto; /* <-- Añade esta línea */

}

nav ul {
    list-style: none;
    display: flex;
    padding: 0;
    margin: 0;
        justify-content: flex-end ; /* <-- Añade esta línea */

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
    transition: all 0.3s ease;
    position: relative;
    border-radius: 4px; /* Un poco de border-radius para un look más suave */
    font-size: 0.9em;
        font-family: 'Open Sans', sans-serif; /* <-- Añade/Modifica esta línea */

}
    nav ul li a {
    padding: 10px 18px; /* AJUSTADO AQUÍ: Ajusta el padding para la altura de 60px de la fila inferior */
    font-weight: normal; /* <--- Esto quitará la negrita o la hará más suave */
    color: var(--nueva-letra-menu); /* Color de letra del menú principal */
    /* Otros estilos... */
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
nav ul li:hover ul.submenu{ /* Cuando el LI tiene la clase 'active' */
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
    margin-top: 120px;
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
    .logo-container {
    padding: 1.5rem 1rem;
    border-bottom: 1px solid #e0e0e0; /* Borde gris claro */
   /* background-color:  #003366; /* Fondo azul Unicauca */
    margin-bottom: 1rem;
}

.unicauca-logo {
    max-width: 100px;
    height: auto;
    filter: brightness(0) invert(1); /* Logo blanco */
}

.logo-container h5 {
    font-weight: 500;
    margin-top: 1rem;
    color: white;
    font-size: 1rem;
}
    .header-top-row {
    display: flex; /* Usamos flexbox para alinear elementos horizontalmente */
    justify-content: space-between; /* Espacio entre el logo/título y los user-controls */
    align-items: center; /* Centrar verticalmente los elementos */
    padding: 0 20px; /* Padding horizontal para la fila superior */
    width: 100%;
}

.logo-and-title {
    display: flex;
    align-items: center;
    gap: 15px; /* Espacio entre el logo-container y el h1 */
}

/* Modifica .logo-container (asegúrate de que no tenga bordes o márgenes que afecten el diseño) */
.logo-container {
    padding: 0; /* Quitar padding si el logo ya tiene tamaño definido */
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: none; /* Asegurar que no tenga este borde aquí */
    margin-bottom: 0; /* Asegurar que no tenga este margen aquí */
}
.logo-container h5 { /* Ajusta el color si tu fondo de header es blanco */
    color: var(--nueva-letra-menu); /* O un color específico como #333 */
    margin-top: 0;
    font-size: 0.9rem;
}

/* Estilos del título del encabezado (h1) */
header h1 {
    font-size: 1.3rem; /* Tamaño del título */
    font-weight: 700;
    color: var(--unicauca-azul); /* Color del título principal */
    text-shadow: none; /* Si no quieres sombra */
    letter-spacing: normal;
}

/* Estilos para el contenedor de Presupuesto y Login */
.user-controls {
    display: flex;
    align-items: center; /* Centrar verticalmente */
    gap: 15px; /* Espacio entre el botón de presupuesto y el login */
        padding: 0 40px; /* <--- Cambia este valor */

}

/* Ajusta los estilos de #login para que se vea bien en la fila superior */
#login {
    background-color: rgba(0, 0, 0, 0.05); /* Fondo sutil para el área de login */
    padding: 6px 12px;
    border-radius: 6px;
    box-shadow: none;
    gap: 8px;
    font-size: 0.85em;
}
#login i {
    color: var(--nueva-letra-menu); /* Color del texto del usuario */
    font-weight: normal;
}
#login .btn-logout {
    padding: 4px 10px;
    font-size: 0.8em;
}

/* Ajusta el botón de Presupuesto */
.btn-external-link {
    padding: 8px 15px !important;
    border-radius: 6px !important;
    font-size: 0.85em !important;
    background-color: var(--unicauca-azul) !important;
    color: var(--unicauca-blanco) !important;
}
.btn-external-link:hover {
    background-color: var(--unicauca-azul-oscuro) !important;
}


/* Estilos para la fila inferior del encabezado (MENÚ PRINCIPAL) */
.header-bottom-row {
    width: 100%;
    display: flex;
    justify-content: space-between; /* <--- ¡CAMBIADO! Esto alinea el primer elemento a la izquierda y el último a la derecha */
    align-items: center; /* Centrar verticalmente los ítems del menú */
    background: white;/*var(--nuevo-fondo-menu); /* Fondo para la barra de menú */
    border-top: 1px solid var(--unicauca-gris-claro); /* Borde superior para separarlo visualmente */
    padding: 0 80px; /* Padding horizontal para el menú */
    
}

/* Asegúrate de que nav dentro de .header-bottom-row no rompa el layout */
nav { /* Esta regla CSS puede ser más específica si tienes otras etiquetas <nav> */
    width: 100%; /* Ocupar todo el ancho disponible */
    display: flex;
    justify-content: center; /* Asegura que la lista <ul> se centre */
    flex-wrap: wrap; /* Permitir que los ítems se envuelvan si no hay espacio */
}

/* Modifica las reglas existentes para los elementos del menú principal */

/* ESTILOS DE HOVER Y ACTIVO PARA MENU PRINCIPAL */
nav ul li a:hover,
nav ul li.active > a {
    background-color: var(--unicauca-azul); /* Fondo azul Unicauca al pasar el mouse o activo */
    color: var(--unicauca-blanco); /* Letra blanca en hover/activo */
    transform: none; /* Eliminar la elevación para este estilo */
    border-radius: 4px; /* Mantener bordes redondeados */
}

nav ul li a::after { /* Línea debajo de los items */
    background: var(--unicauca-rojo-claro); /* Usa un rojo más claro para la línea */
    height: 2px; /* Línea más delgada */
    bottom: 0; /* Asegurar que esté abajo */
    left: 50%;
    width: 0;
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

nav ul li a:hover::after,
nav ul li.active > a::after {
    width: 80%; /* Ancho de la línea al pasar el mouse/activo */
    left: 10%; /* Ajusta si es necesario para centrar */
    transform: none; /* Eliminar transform si es necesario para el nuevo left */
}

/* Submenús */
nav ul li ul.submenu {
    background: white; /* Fondo blanco para submenú */
    border: 1px solid var(--unicauca-gris-medio); /* Borde más suave */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); /* Sombra más suave */
    border-top: 4px solid var(--unicauca-rojo); /* Borde superior con el color institucional */
    border-radius: 0 0 6px 6px;
    padding: 0; /* Asegurar que el padding interno no empuje los elementos */
}

nav ul li ul.submenu li a {
    color: var(--nueva-letra-menu); /* Letra oscura para submenú */
    border-bottom: 1px solid var(--unicauca-gris-claro); /* Borde sutil */
    padding: 10px 20px; /* Padding interno para los ítems del submenú */
}

nav ul li ul.submenu li a:hover {
    background-color: var(--unicauca-gris-claro); /* Fondo gris claro en hover de submenú */
    color: var(--unicauca-azul); /* Letra azul Unicauca en hover de submenú */
    padding-left: 25px; /* Ajustar el padding al hacer hover */
}

nav ul li ul.submenu li.active a {
    background-color: var(--unicauca-rojo-claro); /* Fondo rojo claro para sub-ítem activo */
    color: var(--unicauca-blanco); /* Letra blanca para sub-ítem activo */
    font-weight: bold;
}

nav ul li ul.submenu li.active a::after { /* Opcional: línea para el sub-ítem activo */
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px; /* Es una línea vertical */
    height: 100%;
    background: var(--unicauca-azul-oscuro); /* Línea azul oscura para sub-ítem activo */
}
    .header-bottom-row h1 {
    margin-left: 40px;
    font-weight: normal; /* <--- Para quitar la negrita o hacerla más ligera */
    font-size: 1.2em;    /* <--- Para ajustar el tamaño de la letra (puedes usar 'em', 'px', etc.) */
    color: #333; /* <--- (Opcional) Para un color menos prominente, como un gris oscuro */
}
    /* Para el título h1 dentro del contenedor del logo */
.small-logo {
    height: 100px; /* <--- Aumenta la altura para que sea más grande */
    width: auto; /* Mantiene la proporción de la imagen */
    margin-right: -10px; /* <--- Margen negativo para que se superponga un poco con el texto */
    /*box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3); /* <--- Añade una sombra para efecto 3D */
    z-index: 1; /* <--- Asegura que la imagen esté por encima del texto si hay superposición */
    position: relative; /* Necesario para que z-index funcione */
        top: 25px; /* <--- Añade esta línea para moverlo hacia abajo. Ajusta el valor según necesites (ej. 5px, 10px) */

}
/* Ajustes para el h1 dentro del contenedor del logo si es necesario */
.logo-container h1 {
   /* Si el logo ya tiene un margin-right, el margin-left del h1 podría ser 0 */
    margin-left: 20px; /* Asegúrate de que el 'px' esté ahí */
    
    font-weight: normal; /* <--- Esto quita la negrita o la hace más ligera (equivalente a 400) */
    font-size: 1.2em;    /* <--- Ajusta este valor para el tamaño de la letra. Puedes usar 'em', 'px', o 'rem' */
    color: #333; /* <--- (Opcional) Puedes ajustar el color para que sea menos prominente, por ejemplo, un gris oscuro */
    }
</style>
</head>
<body>
<header>
    <div class="header-top-row">
 <div class="logo-and-title">
    <div class="logo-container text-center">
        <a href="../../temporales/menu_inicio.php" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
            <img src="images/logounicaucat.png" alt="Logo Unicauca" class="small-logo">
            <h1>Vicerrectoría Académica</h1>
        </a>
    </div>
</div>
        
        
        
    <div class="user-controls">
        
        <?php
                $usuarios_presupuesto_permitidos = [92, 4]; // Mantenemos los mismos de tu lógica original

        if ($tipo_usuario == 1 && in_array($id_user, $usuarios_presupuesto_permitidos)): ?>
            <a href="/presupuesto_novedades/" class="btn-external-link" title="Módulo de novedades presupuestales" target="_blank" style="border-radius: 25px; padding: 10px 20px;">
                <i class="fas fa-money-bill-wave me-2"></i> Presupuesto
            </a>
        <?php endif; ?>
        <div id="login">
            <?php
                if (isset($_SESSION['loggedin'])) {
                    if (!empty($departamentos)) {                 // hay resultados
                        $deptoNom = $departamentos[0]['depto_nom_propio'];

                        $facultad_impresa = false;

                    foreach ($departamentos as $dept) {

                        // ► Usuario 2: mostrar facultad solo la primera vez
                        if ($tipo_usuario == 2 && !$facultad_impresa) {
                            echo '<b>Facultad: ' . htmlspecialchars($dept['nombre_fac_min']) . '</b>';
                            $facultad_impresa = true;
                        }

                        // ► Usuario 3: mostrar cada departamento
                        if ($tipo_usuario == 3) {
                            echo '<i>depto: ' . htmlspecialchars($dept['depto_nom_propio']) . '</i> - ';
                        }
                    }
                                        }
                                        echo "<i> usuario: " . $_SESSION['name'] . "</i> <a href='../../temporales/logout.php' class='btn-logout'>Logout</a>";
                } else {
                    // Puedes añadir aquí el mensaje o lógica para usuarios no autenticados si es diferente al de tu código original.
                    echo "SESSION loggedin: " . ($_SESSION['loggedin'] ? 'true' : 'false') . "<br>";
                    echo "Email fac: " . $email_fac . "<br>";
                    echo "<div class='alert alert-danger mt-4' role='alert'><h4>You need to login to access this page.</h4><p><a href='/temporales/index.html'>Login Here!</a></p></div>";
                }
            ?>
        </div>
    </div>
        
        
        
       </div>
    <nav class="header-bottom-row">
          <h1>Solicitud Aval - Profesores Temporales</h1>
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
    <?php if ($tipo_usuario == 1): ?>
            <li class="menu-item <?= ($active_menu_item == 'comparativo') ? 'active' : '' ?>">
                <a href="#" title="comparativo profesores periodo actual vs anterior">
                    Comparativo <span class="new-badge">New!</span>
                </a>
                <ul class="submenu">
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
                        <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                            <a href="#" class="report-linkb"
                               data-facultad-id="<?php echo $departamento['PK_FAC']; ?>"
                               data-anio-semestre="<?php echo $nextPeriod; ?>"
                               data-departamento-id="<?php echo $departamento['PK_DEPTO']; // Add this line ?>">
                                <?php echo $nextPeriod; ?>
                            </a>
                        </li>
                </ul>
            </li>         
                            <?php endif; ?>
    
     
            
            
            
                <?php
            function get_immediate_previous_period($currentPeriod) {
    list($anio, $semestre) = explode('-', $currentPeriod);
    $anio = (int)$anio;
    $semestre = (int)$semestre;

    if ($semestre == 1) {
        return ($anio - 1) . '-2';
    } else {
        return $anio . '-1';
    }
}

                     /* COMPARATIVO GRAFICO PARA 2 Y 3 */
        if ($tipo_usuario != 1): ?>
            <li class="menu-item <?= ($active_menu_item == 'comparativo') ? 'active' : '' ?>">
                <a href="#" title="comparativo profesores periodo actual vs anterior">
                    Comparativo <span class="new-badge">New!</span>
                </a>
                <ul class="submenu">
                    <?php
                    // Calcular el 'anio_semestre_anterior' para cada opción de menú
                    $previousPeriod_anterior = get_immediate_previous_period($previousPeriod);
                    $periodo_work_anterior = get_immediate_previous_period($periodo_work);
                    $nextPeriod_anterior = get_immediate_previous_period($nextPeriod);
                
                    ?>

                    <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $previousPeriod) ? 'active' : '' ?>">
                        <a href="#" class="report-linkg"
   data-facultad-id="<?php echo isset($departamento['PK_FAC']) ? htmlspecialchars($departamento['PK_FAC']) : ''; ?>"
   data-anio-semestre="<?php echo htmlspecialchars($previousPeriod); ?>"
   data-anio-semestre-anterior="<?php echo htmlspecialchars($previousPeriod_anterior); ?>"
   data-departamento-id="<?php echo isset($departamento['PK_DEPTO']) ? htmlspecialchars($departamento['PK_DEPTO']) : ''; ?>">
    <?php echo htmlspecialchars($previousPeriod); ?>
</a>
                    </li>
                    <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $periodo_work) ? 'active' : '' ?>">
                        <a href="#" class="report-linkg"
                           data-facultad-id="<?php echo htmlspecialchars($departamento['PK_FAC']); ?>"
                           data-anio-semestre="<?php echo htmlspecialchars($periodo_work); ?>"
                           data-anio-semestre-anterior="<?php echo htmlspecialchars($periodo_work_anterior); ?>"
                           data-departamento-id="<?php echo htmlspecialchars($departamento['PK_DEPTO']); ?>">
                            <?php echo htmlspecialchars($periodo_work); ?>
                        </a>
                    </li>
                    <li class="<?= ($active_menu_item == 'comparativo' && $selected_period == $nextPeriod) ? 'active' : '' ?>">
                        <a href="#" class="report-linkg"
                           data-facultad-id="<?php echo htmlspecialchars($departamento['PK_FAC']); ?>"
                           data-anio-semestre="<?php echo htmlspecialchars($nextPeriod); ?>"
                           data-anio-semestre-anterior="<?php echo htmlspecialchars($nextPeriod_anterior); ?>"
                           data-departamento-id="<?php echo htmlspecialchars($departamento['PK_DEPTO']); ?>">
                            <?php echo htmlspecialchars($nextPeriod); ?>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>


            
            
            <li class="submenu-container <?= ($active_menu_item == 'novedades') ? 'active' : '' ?>" style="display: none;">
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
                                           data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"
 
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
                                           data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"

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
                                         data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"

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
                                      data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"

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
    data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"

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
                                            data-departamento-id="<?php echo $departamento['PK_DEPTO']; ?>"
    
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
                <li class="<?= ($active_menu_item == 'powerbics') ? 'active' : '' ?>">
                    <a href="../../temporales/powerbics.php">PB-Gráficos</a>
                </li>
            <?php endif; ?>

            <li class="<?= ($active_menu_item == 'video_tutorial') ? 'active' : '' ?>">
                <a href="../../temporales/tutorial.php?tipo_usuario=<?php echo urlencode($tipo_usuario); ?>">Video tutorial</a>
            </li>
        </ul>
    </nav>

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
        
          document.querySelectorAll('.report-linkg').forEach(function(link) {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        var facultadId = this.dataset.facultadId;
        var anioSemestre = this.dataset.anioSemestre;
        var anioSemestreAnterior = this.dataset.anioSemestreAnterior; // ¡Aquí lo obtenemos!
        var departamentoId = this.dataset.departamentoId;

        var form = document.createElement('form');
        form.method = 'GET'; // Tu script report__compartivo_test.php espera POST
        form.action = '../../temporales/report__compartivo_test.php';

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

        // ¡Añadimos el input oculto para anio_semestre_anterior!
        var inputAnioSemestreAnterior = document.createElement('input');
        inputAnioSemestreAnterior.type = 'hidden';
        inputAnioSemestreAnterior.name = 'anio_semestre_anterior';
        inputAnioSemestreAnterior.value = anioSemestreAnterior;
        form.appendChild(inputAnioSemestreAnterior);

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
