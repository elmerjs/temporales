<?php
$active_menu_item = 'powerbics';

require('include/headerz.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gráficas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
   
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            /* La propiedad overflow: hidden; en html, body puede ocultar barras de desplazamiento
               si el contenido del iframe es muy grande. Se recomienda no usarla a menos que sea estrictamente necesario. */
            /* overflow: hidden; */
        }
       
        /* Estilos para el contenedor responsivo del Power BI */
        .powerbi-responsive-container {
            position: relative;
            padding-bottom: 56.25%; /* Esto define una relación de aspecto 16:9 (altura/ancho * 100).
                                        Puedes ajustar este valor (ej: 75% para 4:3) si tu reporte Power BI tiene otra relación de aspecto. */
            height: 0; /* Necesario para que padding-bottom funcione como altura */
            overflow: hidden; /* Oculta cualquier desbordamiento del iframe */
            max-width: 100%; /* Asegura que el contenedor no sea más ancho que su padre */
            margin: 20px auto; /* Centra el contenedor y añade margen vertical */
            border-radius: 8px; /* Opcional: esquinas redondeadas */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); /* Opcional: sombra sutil */
        }

        .powerbi-responsive-container iframe {
            position: absolute; /* Posiciona el iframe de forma absoluta dentro del contenedor */
            top: 0;
            left: 0;
            width: 100%; /* El iframe ocupa el 100% del ancho del contenedor */
            height: 100%; /* El iframe ocupa el 100% de la altura del contenedor (definida por padding-bottom) */
            border: none; /* Elimina el borde predeterminado del iframe */
        }
    </style>
</head>
<body>
    <div class="container py-4"> <div class="powerbi-responsive-container">
            <iframe title="temporales"
                    src="https://app.powerbi.com/view?r=eyJrIjoiZTljNGFjZjItZTU2NC00MzFiLWEyNjktM2IzYzhmMjczOTg0IiwidCI6ImU4MjE0OTM3LTIzM2ItNGIzNi04NmJmLTBiNWYzMzM3YmVlMSIsImMiOjF9"
                    frameborder="0"
                    allowFullScreen="true">
            </iframe>
        </div>
    </div>
</body>
</html>