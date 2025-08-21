<?php

require_once '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = $conn->real_escape_string($_POST['nombres']);
    $apellidos = $conn->real_escape_string($_POST['apellidos']);
    $documento = $conn->real_escape_string($_POST['documento']);
    $sexo = $conn->real_escape_string($_POST['sexo']);
    $encargo = $conn->real_escape_string($_POST['encargo']);

    // Iniciar transacción para asegurar la integridad de los datos
    $conn->begin_transaction();
    
    try {
        // 1. Primero desactivar todos los vicerrectores existentes
        $conn->query("UPDATE vicerrectores SET activo = FALSE");
        
        // 2. Insertar el nuevo vicerrector como activo
        $sql = "INSERT INTO vicerrectores (nombres, apellidos, documento, sexo, encargo, activo) 
                VALUES ('$nombres', '$apellidos', '$documento', '$sexo', '$encargo', TRUE)";
        
        if ($conn->query($sql)) {
            $conn->commit();
            header("Location: listar_vicerrectores.php?success=1");
            exit();
        } else {
            throw new Exception("Error al insertar: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error en la operación: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agregar Vicerrector - Unicauca</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #005883; }
        h3 { color: #F0B310; border-bottom: 2px solid #005883; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background-color: #005883; color: white; padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background-color: #F0B310; }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #005883;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
        <div class="container">

    <div style="text-align: right; padding: 10px;">
            <a href="../gestion_periodos.php" style="text-decoration: none; color: #0056b3; font-size: 24px;">
                &#127969; </a>
            </div>
    <h3>Agregar Vicerrector</h3>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    </div>    
    <div class="info-box">
        <strong>Nota:</strong> Al agregar un nuevo vicerrector, todos los demás serán marcados como inactivos automáticamente.
    </div>

    <form method="POST">
        <div class="form-group">
            <label>Nombres:</label>
            <input type="text" name="nombres" required>
        </div>
        <div class="form-group">
            <label>Apellidos:</label>
            <input type="text" name="apellidos" required>
        </div>
        <div class="form-group">
            <label>Documento:</label>
            <input type="text" name="documento" required>
        </div>
        <div class="form-group">
            <label>Sexo:</label>
            <select name="sexo" required>
                <option value="F">Femenino</option>
                <option value="M">Masculino</option>
            </select>
        </div>
        <div class="form-group">
            <label>Encargo:</label>
            <select name="encargo" required>
                <option value="Propiedad">En propiedad</option>
                <option value="Encargado">Encargado</option>
                <option value="Delegado">Delegado</option>
            </select>
        </div>
        <button type="submit" class="btn">Guardar</button>
        <a href="listar_vicerrectores.php" style="margin-left: 10px;">Cancelar</a>
    </form>
</body>
</html>