<?php
include 'conn.php';

$token = $_GET['token'] ?? '';

// Verificar token válido
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT user_id, expiry FROM password_resets WHERE token='$token'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $expiry = $row['expiry'];
    $user_id = $row['user_id'];
    
    // Verificar si el token ha expirado
    if (strtotime($expiry) < time()) {
        $error = "El enlace de recuperación ha expirado. Por favor solicita uno nuevo.";
        $valid_token = false;
    } else {
        $valid_token = true;
    }
} else {
    $error = "El enlace de recuperación no es válido.";
    $valid_token = false;
}

$conn->close();
?>

<!doctype html>
<html lang="es">
<head>
    <title>Restablecer Contraseña - Temporales Unicauca</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="login-page-bg">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 text-center mt-4">
                <h2 class="main-title">Restablecer Contraseña</h2>
            </div>
        </div>
        <div class="row justify-content-center mt-3">
            <div class="col-lg-4 col-md-6 col-sm-8">
                <div class="card login-card">
                    <div class="loginBox text-center">
                        <img src="images/logounicauca.png" class="img-fluid login-logo" alt="Logo Universidad del Cauca">
                        
                        <?php if (!$valid_token): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                            <a href="forgot-password.php" class="btn btn-primary unicauca-btn-primary-lg">Solicitar nuevo enlace</a>
                        <?php else: ?>
                            <form method="POST" action="process-reset-password.php">
                                <input type="hidden" name="token" value="<?php echo $token; ?>">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text unicauca-input-icon"><i class="fas fa-key"></i></span>
                                        </div>
                                        <input type="password" class="form-control unicauca-input" id="new_password" name="new_password" placeholder="Nueva contraseña" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text unicauca-input-icon"><i class="fas fa-check-circle"></i></span>
                                        </div>
                                        <input type="password" class="form-control unicauca-input" id="confirm_password" name="confirm_password" placeholder="Confirmar nueva contraseña" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block unicauca-btn-primary-lg">Restablecer Contraseña</button>
                            </form>
                        <?php endif; ?>
                        
                        <hr class="unicauca-hr">
                        <p><a href="index.html" class="unicauca-link"><i class="fas fa-arrow-left"></i> Volver al inicio</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
</body>
</html>