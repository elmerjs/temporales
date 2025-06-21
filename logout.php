/* Destroy current user session */

<?php
session_start();
session_unset(); // Elimina todas las variables de sesión
session_destroy();

header('location: index.html');
?>