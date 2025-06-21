<?php
// Incluir el archivo de conexión
include('cn.php');

// Obtener el ID de la novedad
$idNovedad = $_GET['id_novedad'] ?? null;

if ($idNovedad) {
    // Consulta SQL para obtener el detalle_novedad
    $query = "SELECT detalle_novedad FROM solicitudes_novedades WHERE id_novedad = ?";
    if ($stmt = mysqli_prepare($con, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $idNovedad);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            // Decodificar el JSON
            $detalleNovedad = json_decode($row['detalle_novedad'], true);

            // Incluir estilos y librería Bootstrap
            echo "<!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Detalle de Novedad</title>
                <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
                <style>
                    .modal-content {
                        border-radius: 10px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .modal-header {
                        background-color: #007BFF;
                        color: #fff;
                        border-top-left-radius: 10px;
                        border-top-right-radius: 10px;
                    }
                    .json-container {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 10px;
                        padding: 20px;
                    }
                    .json-item {
                        background-color: #f8f9fa;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                        padding: 10px;
                        text-align: left;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    }
                    .json-item h5 {
                        font-size: 14px;
                        color: #007BFF;
                        margin-bottom: 5px;
                    }
                    .json-item p {
                        font-size: 13px;
                        margin: 0;
                    }
                </style>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const modal = new bootstrap.Modal(document.getElementById('detalleModal'));
                        modal.show();

                        const closeModal = () => {
                            window.close();  // Cierra la pestaña del navegador
                        };

                        document.querySelector('.btn-close').addEventListener('click', closeModal);
                        document.querySelector('.btn-secondary').addEventListener('click', closeModal);
                    });
                </script>
            </head>
            <body>
                <!-- Modal -->
                <div class='modal fade' id='detalleModal' tabindex='-1' aria-labelledby='detalleModalLabel' aria-hidden='true'>
                    <div class='modal-dialog modal-lg'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <h5 class='modal-title' id='detalleModalLabel'>Detalle de Novedad</h5>
                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                            </div>
                            <div class='modal-body'>
            ";

            if (json_last_error() === JSON_ERROR_NONE && is_array($detalleNovedad)) {
                echo "<div class='json-container'>";
                foreach ($detalleNovedad as $key => $value) {
                    echo "<div class='json-item'>
                            <h5>" . htmlspecialchars($key) . "</h5>
                            <p>" . htmlspecialchars($value) . "</p>
                          </div>";
                }
                echo "</div>";
            } else {
                echo "<p>Error al decodificar el JSON o formato incorrecto.</p>";
            }

            echo "
                            </div>
                            <div class='modal-footer'>
                                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>";
        } else {
            echo "<p>No se encontró el detalle de la novedad.</p>";
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "<p>Error en la preparación de la consulta: " . mysqli_error($con) . "</p>";
    }
} else {
    echo "<p>ID de novedad no proporcionado.</p>";
}

// Cerrar la conexión a la base de datos
mysqli_close($con);
?>
