<?php
$active_menu_item = 'video_tutorial';
require('include/headerz.php');

// Obtener tipo de usuario (sanitizado)
$tipo_usuario = isset($_GET['tipo_usuario']) ? (int)$_GET['tipo_usuario'] : 3;

// Definir URLs de video según tipo de usuario
$video_url_aceptacion_envio = "https://www.youtube.com/embed/YlYGvVT5SiQ?si=0fzXKrahc9-hrDtm";

// Cambiamos este al de Google Drive para devoluciones tipo 2
$video_url_devolucion = "https://drive.google.com/file/d/1b5Qd5Yvi2GaFkL4J2mcasMF7guKrHaRP/preview"; 

$video_url_tipo_3 = "https://www.youtube.com/embed/K9xK_DY7JIE?si=NoIwuSQAobIdhPQ1";

// Cambiamos este al de Google Drive para novedades tipo 2
$video_url_gestion_novedades = "https://drive.google.com/file/d/165vSrq7SoW9fnSea9KyetFbXQjORy90W/preview";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Tutorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 */
            height: 0;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-12 text-center"> 
                <h2 class="mb-4">Video Tutorial</h2>

                <?php if ($tipo_usuario == 2): ?>
                    <div class="row">
                        <div class="col-md-4">
                            <h4 class="mb-3">Aceptación y envío</h4>
    <div class="video-container">
        <iframe src="https://drive.google.com/file/d/1y_kGJA-5x50CY_xfmqdqjQDVB2BkKFiD/preview"
                allowfullscreen loading="lazy"></iframe>
    </div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="mb-3">Devolución (Rechazo)</h4>
                            <div class="video-container">
                                <iframe src="<?php echo htmlspecialchars($video_url_devolucion); ?>" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="mb-3">Gestión de Novedades</h4>
                            <div class="video-container">
                                <iframe src="<?php echo htmlspecialchars($video_url_gestion_novedades); ?>" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>

                <?php elseif ($tipo_usuario == 3): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="mb-3">Solicitud inicial del periodo</h4>
                            <div class="video-container">
                                <iframe src="https://drive.google.com/file/d/1hinNHPnNXRIPoHsjplEioWpxQlc6IoTg/preview"
                                        width="640" height="480"
                                        allow="autoplay" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">Novedades</h4>
                            <div class="video-container">
                                <iframe src="https://drive.google.com/file/d/1eQo7YlnHOSnH_PTikIEt12EABirhR015/preview" 
                                        width="640" height="480" 
                                        allow="autoplay"></iframe>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="video-container">
                        <iframe src="<?php echo htmlspecialchars($video_url_aceptacion_envio); ?>" allowfullscreen loading="lazy"></iframe>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
