<?php
$active_menu_item = 'video_tutorial';
require('include/headerz.php');

// Obtener tipo de usuario (sanitizado)
$tipo_usuario = isset($_GET['tipo_usuario']) ? (int)$_GET['tipo_usuario'] : 3;

// Definir URLs de video según tipo de usuario
$video_url_aceptacion_envio = "https://www.youtube.com/embed/YlYGvVT5SiQ?si=0fzXKrahc9-hrDtm";
$video_url_devolucion = "https://www.youtube.com/embed/J_G73KqmPi0?si=xDtCNPz1NyssT8r6"; 
$video_url_tipo_3 = "https://www.youtube.com/embed/K9xK_DY7JIE?si=NoIwuSQAobIdhPQ1";
$video_url_gestion_novedades = "https://www.youtube.com/embed/m9b4UWGf-Bk?si=kmLXpWQNGF35L0gA";
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
                            <h4 class="mb-3">Aceptación y Envío</h4>
                            <div class="video-container">
                                <iframe src="<?php echo htmlspecialchars($video_url_aceptacion_envio); ?>" allowfullscreen loading="lazy"></iframe>
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
                                <iframe src="<?php echo htmlspecialchars($video_url_tipo_3); ?>"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">Novedades</h4>
                            <div class="video-container">
                                <iframe src="https://www.youtube.com/embed/bIvD2RBN0H4?si=5xOL63_UG9u-ud2o"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen loading="lazy"></iframe>
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
