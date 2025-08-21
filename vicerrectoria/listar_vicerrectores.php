<?php
require_once '../conn.php';

$sql = "SELECT * FROM vicerrectores ORDER BY activo DESC, apellidos";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vicerrectores - Unicauca</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            color: #005883; /* Azul Unicauca */ 
        }
        h3 { 
            color: #F0B310; /* Amarillo Unicauca */ 
            border-bottom: 2px solid #005883; 
            padding-bottom: 8px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        th, td { 
            padding: 10px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        th { 
            background-color: #005883; 
            color: white; 
        }
        .activo { 
            background-color: #f0fff0; /* Fondo verde claro para activos */
        }
        .inactivo {
            color: #999; /* Texto gris para inactivos */
        }
        .btn { 
            background-color: #005883; 
            color: white; 
            padding: 8px 12px; 
            border-radius: 4px; 
            text-decoration: none; 
            display: inline-block;
            margin-bottom: 10px;
        }
        .btn:hover { 
            background-color: #F0B310; 
        }
        .estado-activo {
            color: #28a745;
            font-weight: bold;
        }
        .estado-inactivo {
            color: #6c757d;
        }
    </style>
</head>
<body>
       <div style="text-align: right; padding: 10px;">
            <a href="../gestion_periodos.php" style="text-decoration: none; color: #0056b3; font-size: 24px;">
                &#127969; </a>
            </div>
    <h3>Gestión de Vicerrectores</h3>
    <a href="agregar_vicerrector.php" class="btn">+ Nuevo Vicerrector</a>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Sexo</th>
                <th>Encargo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr class="<?= $row['activo'] ? 'activo' : 'inactivo' ?>">
                <td><?= htmlspecialchars($row['nombres'].' '.$row['apellidos']) ?></td>
                <td><?= htmlspecialchars($row['documento']) ?></td>
                <td><?= $row['sexo'] == 'F' ? 'Femenino' : 'Masculino' ?></td>
                <td><?= htmlspecialchars($row['encargo']) ?></td>
                <td class="<?= $row['activo'] ? 'estado-activo' : 'estado-inactivo' ?>">
                    <?= $row['activo'] ? 'ACTIVO' : 'Inactivo' ?>
                    <?= $row['activo'] ? '⭐' : '' ?>
                </td>
                <td>
                    <a href="editar_vicerrector.php?id=<?= $row['id'] ?>">Editar</a> | 
                    <?php if($row['activo']): ?>
                        <a href="eliminar_vicerrector.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Desactivar este vicerrector?')">Desactivar</a>
                    <?php else: ?>
                        <a href="activar_vicerrector.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Activar este vicerrector?')">Activar</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
<?php $conn->close(); ?>