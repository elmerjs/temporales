
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Menu</title>
	<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<link rel="stylesheet" href="/posgrados/css/estilos.css">
	<link rel="stylesheet" href="/posgrados/css/fontello.css">
	<script src="http://code.jquery.com/jquery-1.11.3.min.js"></script>
	
</head>
    <body>
        
        <header>
            <h1 align="left">Gestión Posgrados</h1> 
            <input type="checkbox" id="btn-menu">
            
            <label for="btn-menu" class="icon-menu"> </label>
            <nav class="menu">

                 <ul> 

            <li class="submenu"><a href="#" >Docente<span class="icon-down-open"></span></a>
                 <ul>
                    <li><a href="/posgrados/creardocente.php">Crear </a></li>
                    <!--<li><a href="/posgrados/buscardocente.php">Consulta</a></li>-->
                    <li><a href="/posgrados/dt_ss/">Consulta</a></li>
                     <li><a href="consultatercero.php">Edit</a></li>
                  </ul>
             
            <li class="submenu"><a href="#" >Programa<span class="icon-down-open"></span></a>
                <ul>
                <li><a href="/posgrados/crearprograma.php">Crear </a></li>
                <li><a href="/posgrados/buscarprograma.php">Consultar</a></li>
                 <li><a href="/posgrados/buscarcoordinador.php">AdminCoordinadores</a></li>
                
                
                
                </ul>
            </li>
            <li class="submenu"><a href="#" >Módulo<span class="icon-down-open"></span></a>
                <ul>
                <li><a href="/posgrados/crearmodulo.php">Crear </a></li>
                <li><a href="/posgrados/buscarmodulo.php">Consultar</a></li>
                </ul>
            </li>
            <li ><a href="/posgrados/crearadminv.php" >Admin</a></li>
            <li class="submenu"><a href="#" >Tesis<span class="icon-down-open"></span></a>
                <ul>
                    <li><a href="/posgrados/solicitudtesis.php">Crear </a></li>
                    <li><a href="/posgrados/consultatesis.php">Consultar</a></li>
                    <li><a href="/posgrados/buscarestudiante.php">estudiantes</a></li>
                </ul>
            </li>
            <li><a href="#" >Reportes</a></li>
            </ul>         
             
                
                
                
                
            </nav>
    
        </header>
        <script src= "menu.js"></script>
    </body>
</html>