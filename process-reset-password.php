<?php
include 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones básicas
    if (empty($new_password) || empty($confirm_password)) {
        header("Location: reset-password.php?token=$token&error=Las contraseñas no pueden estar vacías");
        exit();
    }
    
    if ($new_password != $confirm_password) {
        header("Location: reset-password.php?token=$token&error=Las contraseñas no coinciden");
        exit();
    }
    
    if (strlen($new_password) < 8) {
        header("Location: reset-password.php?token=$token&error=La contraseña debe tener al menos 8 caracteres");
        exit();
    }
    
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // 1. Verificar token válido
    $stmt = $conn->prepare("SELECT user_id, expiry FROM password_resets WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $stmt->close();
        
        // 2. Verificar si el usuario existe (usando Id en lugar de ID)
        $check_user = $conn->prepare("SELECT Id FROM users WHERE Id=?");
        $check_user->bind_param("i", $user_id);
        $check_user->execute();
        $user_result = $check_user->get_result();
        
        if ($user_result->num_rows == 0) {
            header("Location: reset-password.php?token=$token&error=Usuario no encontrado");
            exit();
        }
        $check_user->close();
        
        // 3. Actualizar contraseña (usando Id)
        $passHash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET Password=? WHERE Id=?");
        $update_stmt->bind_param("si", $passHash, $user_id);
        $update_stmt->execute();
        
        if ($update_stmt->affected_rows > 0) {
            // Eliminar el token usado
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE token=?");
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            header("Location: index.html?password_change=success");
        } else {
            // Verificar contraseña actual
            $check_pass = $conn->prepare("SELECT Password FROM users WHERE Id=?");
            $check_pass->bind_param("i", $user_id);
            $check_pass->execute();
            $pass_result = $check_pass->get_result();
            $db_pass = $pass_result->fetch_assoc()['Password'];
            
            if (password_verify($new_password, $db_pass)) {
                header("Location: reset-password.php?token=$token&error=La nueva contraseña no puede ser igual a la actual");
            } else {
                error_log("Error al actualizar: UserId: $user_id, Token: $token");
                header("Location: reset-password.php?token=$token&error=Error al actualizar. Contacte al administrador");
            }
            $check_pass->close();
        }
        $update_stmt->close();
    } else {
        header("Location: reset-password.php?error=Token inválido o expirado");
    }
    
    mysqli_close($conn);
    exit();
}