<?php
$active_menu_item = 'video_tutorial';


require('include/headerz.php');

// Define the default video URL
$loom_video_url = "https://www.loom.com/embed/c063bc951bbe42788cfac2f0390bd60b?sid=0d56cf27-9f8c-4275-8b72-b16055b780b4"; // Default for tipo_usuario = 3

// Check if 'tipo_usuario' parameter exists in the URL
if (isset($_GET['tipo_usuario'])) {
    $tipo_usuario_recibido = $_GET['tipo_usuario'];

    // Sanitize the input to ensure it's a safe integer
    $tipo_usuario_sanitized = filter_var($tipo_usuario_recibido, FILTER_VALIDATE_INT);

    // Check the value of the sanitized user type
    if ($tipo_usuario_sanitized == 2) {
        $loom_video_url = "https://www.loom.com/embed/7436c0ed129749f5b1b2526833239672?sid=9ad293f5-0ec7-4538-8829-bb66fcd344ae";
    }
    // Puedes agregar más condiciones si hay más tipos de usuario
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Video tutorial</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center">Video tutorial</h2>
    <div style="position: relative; padding-bottom: 56.25%; height: 0;">
        <iframe 
            src="<?php echo htmlspecialchars($loom_video_url); ?>" 
            frameborder="0" 
            webkitallowfullscreen 
            mozallowfullscreen 
            allowfullscreen 
            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
        </iframe>
    </div>
</div>


</body>
</html>