<?php
include('conn.php'); // tu conexión MySQL

$sql = "SELECT *
FROM solicitudes
WHERE (
        (horas > 0 AND (horas_r <= 0 OR horas_r IS NULL))
     OR (horas_r > 0 AND (horas <= 0 OR horas IS NULL))
      )
  AND sede = 'Popayán-Regionalización'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Verificación automática</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f8f9fa;
      color: #333;
      text-align: center;
      margin-top: 20%;
    }
    .ok {
      font-size: 22px;
      color: #28a745;
      font-weight: bold;
    }
  </style>
</head>
<body>

<script>
// Repetir la verificación cada 10 minutos (600000 ms)
setTimeout(() => {
  location.reload();
}, 600000); // 10 minutos
</script>

<?php
if ($result->num_rows > 0) {
    // ⚠️ Mostrar alerta solo si hay inconsistencias
    echo "
    <script>
      Swal.fire({
        icon: 'warning',
        title: 'Alerta de inconsistencias',
        text: 'Se encontraron {$result->num_rows} registros inconsistentes en solicitudes.',
        confirmButtonText: 'Revisar ahora',
        confirmButtonColor: '#d33'
      });
    </script>
    ";
} else {
    // ✅ Mostrar mensaje en pantalla si todo está bien
    echo "<div class='ok'>✅ Sin problemas</div>";
}
?>

</body>
</html>
