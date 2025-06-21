<?php
$host ="localhost";
 $user ="root";
 $pass ="";
 $db="contratacion_temporales";

 //funcion llamada conexion con (dominio,usuarios,contraseña,base_de_datos)
 $con = mysqli_connect($host,$user,$pass,$db)or die("Problemas al Conectar");
 mysqli_select_db($con,$db)or die("problemas al conectar con la base de datos");
    
    ?>