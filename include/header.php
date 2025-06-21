<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Comisiones Académicas Docentes Unicauca</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Estilos del menú lateral y el submenú */
        .submenu {
            display: none; /* Ocultar el submenú por defecto */
        }

        .submenu.visible {
            display: block; /* Mostrar el submenú cuando tiene la clase 'visible' */
        }

        /* Estilos del menú lateral */
        #menu-lateral {
            background-color: gray; /* Azul oscuro */
            width: 200px;
            height: 100%;
            position: fixed;s
            top: 0;
            left: 0;
            padding-top: 90px; /* Altura del encabezado disminuida */
            color: white;
            font-family: Arial, sans-serif;
            z-index: 99; /* Asegurar que esté detrás del contenido */
            transition: transform 0.3s ease;
        }

        #menu-lateral ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #menu-lateral li {
            margin-bottom: 10px;
        }

        #menu-lateral a {
            display: block;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }

        #menu-lateral a:hover {
            background-color: #001f33; /* Azul más claro */
        }

        /* Estilos del contenido */
        #contenido {
            margin-left: 220px; /* Aumentar el margen para dejar espacio al menú */
            padding: 20px;
            z-index: 1; /* Asegurar que esté adelante del menú */
            transition: margin-left 0.3s ease;
        }

        /* Estilos del encabezado */
        header {
            background: linear-gradient(#003366, #001f33); /* az */
            width: 100%;
            height: 40px; /* Altura del encabezado reducida */
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            color: white;
        }

        header h1 {
            margin: 0;
            font-size: 20px; /* Tamaño de fuente reducido */
            line-height: 40px; /* Altura del encabezado */
            padding-left: 20px;
            font-weight: bold; /* Texto más impactante */
        }

        /* Estilos del login */
        #login {
            position: fixed;
            top: 0;
            right: 0;
            padding: 10px;
            color: white; /* Texto blanco */
            font-family: Arial, sans-serif;
        }

        #login a {
            color: white; /* Color del enlace blanco */
        }

        /* Estilos para el botón de mostrar/ocultar menú */
        #toggle-menu {
            background-color: #B22222;
            color: white;
            border: none;
            padding: 5px;
            cursor: pointer;
            width: 80px;
            text-align: left;
            font-size: 16px;
            transition: background-color 0.3s ease;
            position: fixed;
            top: 40px;
            left: 0;
            z-index: 100;
        }

        #toggle-menu:hover {
            background-color: #8B0000 ;
        }

        @media screen and (max-width: 768px) {
            /* Estilos para pantallas pequeñas */
            #menu-lateral {
                width: 150px; /* Reducir el ancho del menú */
                padding-top: 80px; /* Altura del encabezado disminuida */
            }

            #contenido {
                margin-left: 170px; /* Aumentar el margen para dejar espacio al menú */
            }

            header {
                height: 70px; /* Altura del encabezado reducida */
                line-height: 70px; /* Ajustar línea de altura */
            }

            header h1 {
                font-size: 18px; /* Tamaño de fuente reducido */
                line-height: 70px; /* Ajustar línea de altura */
            }

            #toggle-menu {
                width: 150px;
            }
        }
    </style>
</head>
<body>
    <div id="menu-lateral">
        <nav>
            <ul>
                <li><a href="../../comisiones_academicas/comisiones.php">Comisiones</a></li>
                <li>
                    <a href="../../comisiones_academicas/report_terceros.php">Trámites por Profesor <i class="fas fa-chalkboard-teacher"></i></a>
                </li>
              
                <li><a href="../../comisiones_academicas/report_pendientes.php">Informes <i class="fas fa-exclamation-triangle"></i></a></li>
                <li><a href="../../comisiones_academicas/directivos.php">Gestionar Encargos <i class="fas fa-user-tie"></i></a></li>
                  <li><a href="../../comisiones_academicas/powerbics.php">PB-Gráficos <i class="fas fa-chart-pie"></i></a></li>
            </ul>
        </nav>
    </div>

    <button id="toggle-menu">☰ Menú</button>

    <header>
        <?php
        if (isset($_SESSION['loggedin'])) {  
        } else {
           echo "<div class='alert alert-danger mt-4' role='alert'>
    <h4 style='color: red;'>¡Necesita iniciar sesión para acceder a esta página!</h4>
    <p ><a href='/comisiones_academicas/index.html' style='color: red;'>¡Inicie sesión aquí!</a></p></div>";
            exit;
        }
        // checking the time now when check-login.php page starts
        $now = time();           
      if ($now > $_SESSION['expire']) {
    session_destroy();
    echo "<div class='alert alert-danger mt-4' role='alert'>
    <h4 style='color: red;'>¡Su sesión ha expirado!</h4>
    <p><a href='/comisiones_academicas/index.html'>Inicie sesión aquí</a></p></div>";
    exit;
}
?>

        <h1>Comisiones Académicas Unicauca</h1>
        <div id="login">
            <?php
                if (isset($_SESSION['loggedin'])) {  
                    echo "<i>" . $_SESSION['name'] . "</i> <i class='fas fa-sign-out-alt' style='font-size:12px;color:red'></i><a href='../../comisiones_academicas/logout.php'>Logout</a>";
                } else {
                    echo "<div class='alert alert-danger mt-4' role='alert'>
                        <h4>Necesitas logearte.</h4>
                        <p><a href='/comisiones_academicas/index.html'>Login Here!</a></p>
                    </div>";
                }
            ?>
        </div>
    </header>


    
    <script>
        document.getElementById('toggle-menu').addEventListener('click', function() {
            var menu = document.getElementById('menu-lateral');
            var contenido = document.getElementById('contenido');
            
            if (menu.style.transform === 'translateX(-200px)') {
                menu.style.transform = 'translateX(0)';
                contenido.style.marginLeft = '220px';
            } else {
                menu.style.transform = 'translateX(-200px)';
                contenido.style.marginLeft = '20px';
            }
        });
    </script>
</body>
</html>
