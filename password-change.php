<!doctype html>
<html lang="en">
<head>
    <title>Password Change</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <link rel="stylesheet" href="css/custom.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="loginBox">
                    <h2>Cambiar contraseña</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="current_password">Contraseña actual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmar nueva contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </form>
                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        include 'conn.php';
                        
                        $email = $_POST['email'];
                        $current_password = $_POST['current_password'];
                        $new_password = $_POST['new_password'];
                        $confirm_password = $_POST['confirm_password'];
                        
                        // Check if new passwords match
                        if ($new_password != $confirm_password) {
                            echo "<div class='alert alert-danger mt-4' role='alert'>New passwords do not match!</div>";
                        } else {
                            $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
                            
                            // Check connection
                            if ($conn->connect_error) {
                                die("Connection failed: " . $conn->connect_error);
                            }
                            
                            $sql = "SELECT Password FROM users WHERE Email='$email'";
                            $result = $conn->query($sql);
                            
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                // Verify current password
                                if (password_verify($current_password, $row['Password'])) {
                                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                                    $sql_update = "UPDATE users SET Password='$new_password_hash' WHERE Email='$email'";
                                    
                                    if ($conn->query($sql_update) === TRUE) {
                                        echo "<div class='alert alert-success mt-4' role='alert'>Password successfully updated!</div>";
                                        echo "<script>setTimeout(function(){ window.location.href = 'index.html'; }, 2000);</script>";
                                    } else {
                                        echo "Error updating record: " . $conn->error;
                                    }
                                } else {
                                    echo "<div class='alert alert-danger mt-4' role='alert'>Current password is incorrect!</div>";
                                }
                            } else {
                                echo "<div class='alert alert-danger mt-4' role='alert'>Email not found!</div>";
                            }
                            
                            $conn->close();
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
</body>
</html>
