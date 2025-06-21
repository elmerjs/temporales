<?php
// Connection variables
$dbhost	= "localhost";	   // localhost or IP
$dbuser	= "root";		  // database username
$dbpass	= "";		     // database password
$dbname	= "contratacion_temporales";    // database name


$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

            // Verificar la conexión
            if ($conn->connect_error) {
                die("Conexión fallida: " . $conn->connect_error);
            }

?>