<?php
session_start();
include 'conn.php'; // Asegúrate de incluir tu archivo de conexión

if (empty($_SESSION['loggedin']) || empty($_SESSION['id_user'])) {
    header("Location: /temporales/index.html");
    exit();
}

$id_user = $_SESSION['id_user'];
$mensaje = '';
$tipo_mensaje = ''; // success, danger, etc.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validar que las nuevas contraseñas coincidan
    if ($new_password != $confirm_password) {
        $mensaje = 'Las nuevas contraseñas no coinciden!';
        $tipo_mensaje = 'danger';
    } else {
        $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $sql = "SELECT Password FROM users WHERE Id='$id_user'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Verificar contraseña actual
            if (password_verify($current_password, $row['Password'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET Password='$new_password_hash' WHERE Id='$id_user'";
                
                if ($conn->query($sql_update) === TRUE) {
                    $mensaje = 'Contraseña actualizada correctamente!';
                    $tipo_mensaje = 'success';
                    
                    // Redirigir después de 2 segundos
                    echo "<script>
                            setTimeout(function(){ 
                                window.history.back();
                            }, 2000);
                          </script>";
                } else {
                    $mensaje = 'Error al actualizar la contraseña: '.$conn->error;
                    $tipo_mensaje = 'danger';
                }
            } else {
                $mensaje = 'La contraseña actual es incorrecta!';
                $tipo_mensaje = 'danger';
            }
        } else {
            $mensaje = 'Usuario no encontrado!';
            $tipo_mensaje = 'danger';
        }
        
        $conn->close();
    }
}
?>

<!doctype html>
<html lang="es">
<head>
    <title>Cambio de Contraseña - Temporales Unicauca</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        .login-page-bg {
            background-color: #f8f9fa;
            min-height: 100vh;
            padding-top: 50px;
        }
        .login-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .login-logo {
            max-width: 180px;
            margin-bottom: 20px;
        }
        .unicauca-input {
            border-left: none;
            padding: 12px 15px;
        }
        .unicauca-input-icon {
            background-color: white;
            border-right: none;
        }
        .unicauca-btn-primary-lg {
            padding: 10px;
            font-weight: 600;
            border-radius: 5px;
            background-color: #4a6baf;
            border-color: #4a6baf;
        }
        .unicauca-hr {
            border-top: 1px solid #e9ecef;
            margin: 20px 0;
        }
        .unicauca-link {
            color: #4a6baf;
            font-weight: 500;
        }
        .main-title {
            color: #343a40;
            font-weight: 700;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="login-page-bg">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 text-center">
                <h2 class="main-title">Cambio de Contraseña</h2>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 col-sm-8">
                <div class="card login-card">
                    <div class="loginBox text-center">
                        <img src="images/logounicauca.png" class="img-fluid login-logo" alt="Logo Universidad del Cauca">
                        
                        <?php if ($mensaje): ?>
                            <div class="alert alert-<?php echo $tipo_mensaje; ?> mt-4" role="alert">
                                <i class="fas <?php echo $tipo_mensaje == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> 
                                <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text unicauca-input-icon"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input type="password" class="form-control unicauca-input" id="current_password" name="current_password" placeholder="Contraseña actual" required>
                                </div>
                            </div>
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

                            <button type="submit" class="btn btn-primary btn-block unicauca-btn-primary-lg">Actualizar Contraseña</button>
                        </form>
                        
                        <hr class="unicauca-hr">
                        <p><a href="menu_inicio.php"><i class="fas fa-arrow-left"></i> Volver</a></p>
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