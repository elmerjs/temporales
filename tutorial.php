<?php
$active_menu_item = 'video_tutorial';
require('include/headerz.php');

// Obtener tipo de usuario (sanitizado)
$tipo_usuario = isset($_GET['tipo_usuario']) ? (int)$_GET['tipo_usuario'] : 3;

// Definir URLs de video
$video_url_aceptacion_envio = "https://www.youtube.com/embed/YlYGvVT5SiQ?si=0fzXKrahc9-hrDtm";
$video_url_devolucion = "https://drive.google.com/file/d/1b5Qd5Yvi2GaFkL4J2mcasMF7guKrHaRP/preview"; 
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
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        .proximamente-nota {
            font-size: 0.85rem;
            font-weight: 500;
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-12 text-center"> 
                <h2 class="mb-4">Módulos de Capacitación</h2>

                <?php if ($tipo_usuario == 2): ?>
                    <div class="row">
                        <div class="col-md-4">
                            <h4 class="mb-3">Aceptación y envío</h4>
                            <div class="video-container">
                                <iframe src="https://drive.google.com/file/d/1y_kGJA-5x50CY_xfmqdqjQDVB2BkKFiD/preview" allowfullscreen loading="lazy"></iframe>
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
                        <div class="col-md-4">
                            <h4 class="mb-3">Solicitud inicial</h4>
                            <div class="video-container">
                                <iframe src="https://drive.google.com/file/d/1hinNHPnNXRIPoHsjplEioWpxQlc6IoTg/preview" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="mb-3">Novedades</h4>
                            <div class="video-container">
                                <iframe src="https://drive.google.com/file/d/1NBX7qcQ69VzeJS84AFsxsXR-xVUDeSo8/preview" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h4 class="mb-3">Acta de Selección FOR-59</h4>
                            <div class="video-container" style="opacity: 0.5;">
                                <iframe src="https://drive.google.com/file/d/17o6SpOg4n5hh08OotfT-o0tJLMHR72fi/preview" allowfullscreen loading="lazy"></iframe>
                            </div>
                            <div class="proximamente-nota">
                                ⏳ Próximamente en funcionamiento
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <h4 class="mb-3">Video Tutorial General</h4>
                            <div class="video-container">
                                <iframe src="<?php echo htmlspecialchars($video_url_aceptacion_envio); ?>" allowfullscreen loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>