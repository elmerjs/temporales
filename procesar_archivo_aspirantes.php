<?php
require 'vendor/autoload.php'; // Cargar PhpSpreadsheet
include 'cn.php'; // Conexión a la base de datos

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica si el archivo fue subido
    $file = $_FILES['file']['tmp_name'];

    try {
        // Cargar el archivo Excel
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Leer filas del Excel (empieza en la fila 2 para saltar encabezados)
        $rowIndex = 2;
        while ($sheet->getCell("B{$rowIndex}")->getValue() !== null) {
            $identificacion = trim($sheet->getCell("B{$rowIndex}")->getValue());
            $apellidos = mb_strtoupper(trim($sheet->getCell("D{$rowIndex}")->getValue())); // Columna D
            $nombres = mb_strtoupper(trim($sheet->getCell("C{$rowIndex}")->getValue()));   // Columna C
            $email = trim($sheet->getCell("I{$rowIndex}")->getValue());     // Deja el email tal como viene
            $departamentos = mb_strtoupper(trim($sheet->getCell("E{$rowIndex}")->getValue())); // Columna E
            $periodo = $_POST['periodo']; // Obtener el periodo del formulario
            $estado = 1;

            // Concatenar apellidos y nombres con espacio
            $nombreCompleto = $apellidos . " " . $nombres;

            // Separar nombres y apellidos
            $nombrePartes = explode(" ", $nombres);
            $apellidoPartes = explode(" ", $apellidos);

            $nombre1 = isset($nombrePartes[0]) ? $nombrePartes[0] : "";
            $nombre2 = isset($nombrePartes[1]) ? $nombrePartes[1] : "";
            $apellido1 = isset($apellidoPartes[0]) ? $apellidoPartes[0] : "";
            $apellido2 = isset($apellidoPartes[1]) ? $apellidoPartes[1] : "";

            // Verificar si la identificación ya existe en la tabla tercero
            $queryTercero = "SELECT documento_tercero FROM tercero WHERE documento_tercero = ?";
            $stmtTercero = $con->prepare($queryTercero);
            $stmtTercero->bind_param('s', $identificacion);
            $stmtTercero->execute();
            $resultTercero = $stmtTercero->get_result();

            if ($resultTercero->num_rows == 0) {
                // Si no existe en la tabla tercero, insertar nuevo registro
                $insertTercero = "INSERT INTO tercero (documento_tercero, nombre_completo, apellido1, apellido2, nombre1, nombre2, estado, email, fecha_ingreso, oferente_periodo)
                                  VALUES (?, ?, ?, ?, ?, ?, 'ac', ?, '2025-01-01', 1)";
                $stmtInsertTercero = $con->prepare($insertTercero);
                $stmtInsertTercero->bind_param('sssssss', $identificacion, $nombreCompleto, $apellido1, $apellido2, $nombre1, $nombre2, $email);
                $stmtInsertTercero->execute();
            }

            // Verificar si ya existe un registro en la tabla aspirante con la misma identificación y periodo
            $queryAspirante = "SELECT id_aspirante FROM aspirante WHERE fk_asp_doc_tercero = ? AND fk_asp_periodo = ?";
            $stmtAspirante = $con->prepare($queryAspirante);
            $stmtAspirante->bind_param('ss', $identificacion, $periodo);
            $stmtAspirante->execute();
            $resultAspirante = $stmtAspirante->get_result();

            if ($resultAspirante->num_rows > 0) {
                // Actualizar registro existente
                $updateAspirante = "UPDATE aspirante SET asp_departamentos = ?, asp_estado = ? WHERE fk_asp_doc_tercero = ? AND fk_asp_periodo = ?";
                $stmtUpdateAspirante = $con->prepare($updateAspirante);
                $stmtUpdateAspirante->bind_param('siss', $departamentos, $estado, $identificacion, $periodo);
                $stmtUpdateAspirante->execute();
            } else {
                
                // Insertar nuevo registro en aspirante
                $insertAspirante = "INSERT INTO aspirante (fk_asp_doc_tercero, fk_asp_periodo, asp_estado, asp_departamentos) VALUES (?, ?, ?, ?)";
                $stmtInsertAspirante = $con->prepare($insertAspirante);
                $stmtInsertAspirante->bind_param('ssis', $identificacion, $periodo, $estado, $departamentos);
                $stmtInsertAspirante->execute();
            }

            $rowIndex++;
        }

        echo "Archivo procesado correctamente.";
    } catch (Exception $e) {
        echo "Error al procesar el archivo: " . $e->getMessage();
    }

} else {
    echo "Método no permitido.";
}
