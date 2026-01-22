<?php

set_time_limit(0);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
require 'vendor/autoload.php'; // Cargar PhpSpreadsheet
include 'cn.php'; // Conexión a la base de datos

use PhpOffice\PhpSpreadsheet\IOFactory;

// Función para escribir logs detallados (solo para archivo, no para output)
function escribir_log($mensaje, $nivel = "INFO") {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$nivel] $mensaje\n";
    // Guardar en archivo de log
    file_put_contents('procesamiento_log.txt', $log_entry, FILE_APPEND | LOCK_EX);
}

// Función para mostrar solo información resumida
function mostrar_resumen($mensaje) {
    echo "[RESUMEN] $mensaje\n";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Inicializar estadísticas
    $estadisticas = [
        'total_registros' => 0,
        'nuevos_terceros' => 0,
        'terceros_existentes' => 0,
        'aspirantes_nuevos' => 0,
        'aspirantes_actualizados' => 0,
        'errores' => 0,
        'ids_procesados' => []
    ];
    
    $inicio_tiempo = microtime(true);
    mostrar_resumen("🚀 INICIANDO PROCESAMIENTO");
    mostrar_resumen("📅 Periodo: " . ($_POST['periodo'] ?? 'No especificado'));

    // Verifica si el archivo fue subido
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        mostrar_resumen("❌ Error: No se recibió el archivo o hay error en la subida");
        echo json_encode(['success' => false, 'message' => 'Error en la subida del archivo']);
        exit;
    }

    $file = $_FILES['file']['tmp_name'];
    $periodo = $_POST['periodo'] ?? '2026-1';

    try {
        // Cargar el archivo Excel
        escribir_log("Cargando archivo Excel...");
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        
        // Leer filas del Excel (empieza en la fila 2 para saltar encabezados)
        $rowIndex = 2;
        $total_procesado = 0;

        escribir_log("Iniciando lectura de registros...");

        while ($sheet->getCell("B{$rowIndex}")->getValue() !== null && $sheet->getCell("B{$rowIndex}")->getValue() !== '') {
            $total_procesado++;
            $identificacion = trim($sheet->getCell("B{$rowIndex}")->getValue());
            
            // Mostrar progreso cada 50 registros
            if ($total_procesado % 50 === 0) {
                mostrar_resumen("⏳ Procesados: $total_procesado registros...");
            }

            escribir_log("Procesando fila $rowIndex - ID: $identificacion");

            try {
                $apellidos = mb_strtoupper(trim($sheet->getCell("D{$rowIndex}")->getValue())); // Columna D
                $nombres = mb_strtoupper(trim($sheet->getCell("C{$rowIndex}")->getValue()));   // Columna C
                $email = trim($sheet->getCell("I{$rowIndex}")->getValue());
                $departamentos = mb_strtoupper(trim($sheet->getCell("E{$rowIndex}")->getValue())); // Columna E
                $estado = 1;

                // Nuevos campos
                $titulos = mb_strtoupper(trim($sheet->getCell("F{$rowIndex}")->getValue())); 
                $telefono = trim($sheet->getCell("G{$rowIndex}")->getValue());              // Columna G
                $celular = trim($sheet->getCell("H{$rowIndex}")->getValue());              // Columna H
                $correo = trim($sheet->getCell("I{$rowIndex}")->getValue());              // Columna I
                $trabaja_actual = mb_strtoupper(trim($sheet->getCell("J{$rowIndex}")->getValue())); 
                $cargo = mb_strtoupper(trim($sheet->getCell("K{$rowIndex}")->getValue())); // Columna K

                // Validaciones básicas
                if (empty($identificacion)) {
                    throw new Exception("Identificación vacía");
                }

                if (empty($nombres) || empty($apellidos)) {
                    throw new Exception("Nombres o apellidos vacíos");
                }

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
                                    VALUES (?, ?, ?, ?, ?, ?, 'ac', ?, CURDATE(), 1)";
                    $stmtInsertTercero = $con->prepare($insertTercero);
                    $stmtInsertTercero->bind_param('sssssss', $identificacion, $nombreCompleto, $apellido1, $apellido2, $nombre1, $nombre2, $email);
                    
                    if ($stmtInsertTercero->execute()) {
                        $estadisticas['nuevos_terceros']++;
                        escribir_log("Tercero insertado: $identificacion");
                    } else {
                        throw new Exception("Error insertando tercero: " . $stmtInsertTercero->error);
                    }
                } else {
                    $estadisticas['terceros_existentes']++;
                    escribir_log("Tercero existente: $identificacion");
                }

                // Verificar si ya existe un registro en la tabla aspirante con la misma identificación y periodo
                $queryAspirante = "SELECT id_aspirante FROM aspirante WHERE fk_asp_doc_tercero = ? AND fk_asp_periodo = ?";
                $stmtAspirante = $con->prepare($queryAspirante);
                $stmtAspirante->bind_param('ss', $identificacion, $periodo);
                $stmtAspirante->execute();
                $resultAspirante = $stmtAspirante->get_result();

                if ($resultAspirante->num_rows > 0) {
                    // Actualizar registro existente
                    $updateAspirante = "UPDATE aspirante SET 
                                        asp_departamentos = ?, 
                                        asp_estado = ?, 
                                        asp_titulos = ?, 
                                        asp_telefono = ?, 
                                        asp_celular = ?, 
                                        asp_correo = ?, 
                                        asp_trabaja_actual = ?, 
                                        asp_cargo = ? 
                                        WHERE fk_asp_doc_tercero = ? AND fk_asp_periodo = ?";
                    $stmtUpdateAspirante = $con->prepare($updateAspirante);
                    $stmtUpdateAspirante->bind_param(
                        'sissssssss', 
                        $departamentos, 
                        $estado, 
                        $titulos, 
                        $telefono, 
                        $celular, 
                        $correo, 
                        $trabaja_actual, 
                        $cargo, 
                        $identificacion, 
                        $periodo
                    );
                    
                    if ($stmtUpdateAspirante->execute()) {
                        $estadisticas['aspirantes_actualizados']++;
                        escribir_log("Aspirante actualizado: $identificacion");
                    } else {
                        throw new Exception("Error actualizando aspirante: " . $stmtUpdateAspirante->error);
                    }
                } else {
                    // Insertar nuevo registro
                    $insertAspirante = "INSERT INTO aspirante (
                                        fk_asp_doc_tercero, 
                                        fk_asp_periodo, 
                                        asp_estado, 
                                        asp_departamentos, 
                                        asp_titulos, 
                                        asp_telefono, 
                                        asp_celular, 
                                        asp_correo, 
                                        asp_trabaja_actual, 
                                        asp_cargo
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmtInsertAspirante = $con->prepare($insertAspirante);
                    $stmtInsertAspirante->bind_param(
                        'ssisssssss', 
                        $identificacion, 
                        $periodo, 
                        $estado, 
                        $departamentos, 
                        $titulos, 
                        $telefono, 
                        $celular, 
                        $correo, 
                        $trabaja_actual, 
                        $cargo
                    );
                    
                    if ($stmtInsertAspirante->execute()) {
                        $estadisticas['aspirantes_nuevos']++;
                        escribir_log("Aspirante insertado: $identificacion");
                    } else {
                        throw new Exception("Error insertando aspirante: " . $stmtInsertAspirante->error);
                    }
                }

                $estadisticas['ids_procesados'][] = $identificacion;

            } catch (Exception $e) {
                $estadisticas['errores']++;
                $error_msg = "Fila $rowIndex (ID: $identificacion): " . $e->getMessage();
                escribir_log("ERROR: $error_msg");
            }

            $rowIndex++;
        }

        $estadisticas['total_registros'] = $total_procesado;
        $tiempo_total = round(microtime(true) - $inicio_tiempo, 2);

        // Mostrar solo el resumen final conciso
        mostrar_resumen("=" . str_repeat("=", 50));
        mostrar_resumen("📊 RESUMEN FINAL");
        mostrar_resumen("=" . str_repeat("=", 50));
        mostrar_resumen("📈 ESTADÍSTICAS:");
        mostrar_resumen("   • Total registros procesados: " . $estadisticas['total_registros']);
        mostrar_resumen("   • Nuevos terceros: " . $estadisticas['nuevos_terceros']);
        mostrar_resumen("   • Terceros existentes: " . $estadisticas['terceros_existentes']);
        mostrar_resumen("   • Aspirantes nuevos: " . $estadisticas['aspirantes_nuevos']);
        mostrar_resumen("   • Aspirantes actualizados: " . $estadisticas['aspirantes_actualizados']);
        mostrar_resumen("   • Errores: " . $estadisticas['errores']);
        
        // Calcular y mostrar eficiencia
        if ($total_procesado > 0) {
            $exitosos = ($estadisticas['aspirantes_nuevos'] + $estadisticas['aspirantes_actualizados']);
            $eficiencia = ($exitosos / $total_procesado) * 100;
            mostrar_resumen("   • Eficiencia: " . number_format($eficiencia, 2) . "%");
        }
        
        mostrar_resumen("⏰ Tiempo total: " . $tiempo_total . " segundos");
        mostrar_resumen("✅ PROCESAMIENTO COMPLETADO");

        // Enviar respuesta JSON estructurada
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Archivo procesado correctamente',
            'estadisticas' => $estadisticas,
            'tiempo_ejecucion' => $tiempo_total
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $error_msg = "Error al procesar el archivo: " . $e->getMessage();
        mostrar_resumen("❌ ERROR CRÍTICO: $error_msg");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error_msg,
            'estadisticas' => $estadisticas ?? []
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

} else {
    mostrar_resumen("❌ Método no permitido");
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}