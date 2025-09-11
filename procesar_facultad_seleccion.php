<?php
// procesar_facultad_seleccion.php

// ===== INICIA NUEVO BLOQUE: INCLUSIÓN DE PHPMailer =====
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que las rutas a los archivos sean correctas desde este script
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Cargar la configuración de correo
$config = require 'config_email.php';
// ===== TERMINA NUEVO BLOQUE =====

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

require_once('conn.php');

function responder_json($success, $message, $data = []) {
    ob_clean();
    die(json_encode(['success' => $success, 'message' => $message, 'data' => $data]));
}

// --- 1. VALIDACIÓN DE DATOS (Sin cambios) ---
$action = $_POST['action'] ?? '';
$selected_ids = $_POST['selected_ids'] ?? [];
$observacion = $_POST['observacion'] ?? '';
$anio_semestre = $_POST['anio_semestre'] ?? '';
$id_facultad = $_SESSION['id_facultad'] ?? null;
$aprobador_id_logged_in = $_SESSION['aprobador_id_logged_in'] ?? null;

if (empty($action) || empty($selected_ids) || is_null($id_facultad) || is_null($aprobador_id_logged_in)) {
    responder_json(false, 'La sesión o los datos de la solicitud son inválidos.');
}

// --- 2. LÓGICA DE PROCESAMIENTO ---
try {
    $conn->begin_transaction();

    $ids_a_procesar = $selected_ids;

    // ===========================================================================
    // ===== LÓGICA PARA "CAMBIO DE VINCULACIÓN" (SE MANTIENE 100% INTACTA) ======
    // ===========================================================================
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $types = str_repeat('i', count($selected_ids));
    $sql_find_cedulas = "SELECT cedula FROM solicitudes_working_copy WHERE id_solicitud IN ($placeholders) AND (novedad = 'Adicion' OR novedad = 'adicionar')";
    
    $stmt_cedulas = $conn->prepare($sql_find_cedulas);
    $stmt_cedulas->bind_param($types, ...$selected_ids);
    $stmt_cedulas->execute();
    $result_cedulas = $stmt_cedulas->get_result();
    $cedulas_de_adiciones = [];
    while ($row = $result_cedulas->fetch_assoc()) {
        $cedulas_de_adiciones[] = $row['cedula'];
    }
    $stmt_cedulas->close();

    if (!empty($cedulas_de_adiciones)) {
        $placeholders_cedulas = implode(',', array_fill(0, count($cedulas_de_adiciones), '?'));
        $types_cedulas = str_repeat('s', count($cedulas_de_adiciones));
        $sql_find_eliminar = "SELECT id_solicitud FROM solicitudes_working_copy WHERE cedula IN ($placeholders_cedulas) AND novedad = 'Eliminar' AND anio_semestre = ? AND facultad_id = ? AND estado_facultad = 'PENDIENTE'";
        $stmt_eliminar = $conn->prepare($sql_find_eliminar);
        
        $params_eliminar = array_merge($cedulas_de_adiciones, [$anio_semestre, $id_facultad]);
        $stmt_eliminar->bind_param($types_cedulas . 'si', ...$params_eliminar);
        
        $stmt_eliminar->execute();
        $result_eliminar = $stmt_eliminar->get_result();
        while ($row = $result_eliminar->fetch_assoc()) {
            $ids_a_procesar[] = $row['id_solicitud'];
        }
        $stmt_eliminar->close();
    }
    
    $ids_a_procesar = array_unique(array_map('intval', $ids_a_procesar));
    // ===========================================================================

    // ===== INICIA NUEVO BLOQUE: INICIALIZACIÓN PARA CORREOS =====
    $departamento_emails = []; 
    // ===== TERMINA NUEVO BLOQUE =====

    $new_status_facultad = ($action === 'avalar') ? 'APROBADO' : 'RECHAZADO';
    $success_count = 0;
    
    $update_sql = "UPDATE solicitudes_working_copy SET estado_facultad = ?, fecha_aprobacion_facultad = NOW(), aprobador_facultad_id = ?, observacion_facultad = ? WHERE id_solicitud = ? AND facultad_id = ?";
    $stmt_update = $conn->prepare($update_sql);

    foreach ($ids_a_procesar as $id) {
        $stmt_update->bind_param("sisii", $new_status_facultad, $aprobador_id_logged_in, $observacion, $id, $id_facultad);
        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            $success_count++;
            
            // ===== INICIA NUEVO BLOQUE: RECOPILACIÓN DE DATOS PARA EL CORREO (DENTRO DEL BUCLE) =====
            $stmt_info = $conn->prepare("
                SELECT s.nombre, s.cedula, s.anio_semestre, s.novedad, d.depto_nom_propio as nombre_depto, d.email_depto
                FROM solicitudes_working_copy s
                JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO
                WHERE s.id_solicitud = ?
            ");
            $stmt_info->bind_param("i", $id);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();

            if ($solicitud_data = $result_info->fetch_assoc()) {
                $email_depto_mail = 'elmerjs@unicauca.edu.co'; // Para pruebas
                // $email_depto_mail = $solicitud_data['email_depto']; // Línea para producción

                if (!empty($email_depto_mail)) {
                    if (!isset($departamento_emails[$email_depto_mail])) {
                        $departamento_emails[$email_depto_mail] = [
                            'nombre_depto' => $solicitud_data['nombre_depto'],
                            'solicitudes' => []
                        ];
                    }
                    $departamento_emails[$email_depto_mail]['solicitudes'][] = [
                        'nombre_profesor' => $solicitud_data['nombre'],
                        'cedula_profesor' => $solicitud_data['cedula'],
                        'anio_semestre'   => $solicitud_data['anio_semestre'],
                        'novedad'         => $solicitud_data['novedad'],
                        'estado_campo'    => $new_status_facultad,
                    ];
                }
            }
            $stmt_info->close();
            // ===== TERMINA NUEVO BLOQUE =====
        }
    }
    $stmt_update->close();
    
    $conn->commit();
    
    // ===== INICIA NUEVO BLOQUE: ENVÍO DE CORREOS CON PHPMailer (DESPUÉS DEL COMMIT) =====
    if ($success_count > 0 && !empty($departamento_emails)) {
        $email_vra = 'vra@unicauca.edu.co'; // Reemplazar con una consulta a la BD si es necesario

        foreach ($departamento_emails as $email_depto => $data) {
            $nombre_depto = $data['nombre_depto'];

            // --- Lógica para consolidar "Cambio de Vinculación" ---
            $solicitudes_consolidadas = [];
            $cedulas_procesadas = [];
            foreach ($data['solicitudes'] as $sol) {
                $cedula = $sol['cedula_profesor'];
                if (in_array($cedula, $cedulas_procesadas)) continue; // Ya fue parte de un par

                $es_adicion = (strtolower($sol['novedad']) === 'adicion' || strtolower($sol['novedad']) === 'adicionar');
                $par_encontrado = null;

                if ($es_adicion) {
                    foreach ($data['solicitudes'] as $posible_par) {
                        if ($posible_par['cedula_profesor'] === $cedula && strtolower($posible_par['novedad']) === 'eliminar') {
                            $par_encontrado = $posible_par;
                            break;
                        }
                    }
                }
                
                if ($par_encontrado) {
                    $sol['novedad'] = 'Cambio de Vinculación'; // Modificamos la novedad
                    $solicitudes_consolidadas[] = $sol;
                    $cedulas_procesadas[] = $cedula; // Marcamos ambas cédulas como procesadas
                } else {
                    $solicitudes_consolidadas[] = $sol;
                }
            }

            $tabla_html = "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'><thead><tr style='background-color: #f2f2f2;'><th>Profesor</th><th>Cédula</th><th>Periodo</th><th>Novedad</th><th>Resultado del Trámite</th></tr></thead><tbody>";
            foreach ($solicitudes_consolidadas as $sol) {
                $resultado = ($sol['estado_campo'] === 'APROBADO') ? 'Avalado' : 'No Avalado (Devuelto)';
                $tabla_html .= "<tr><td>" . htmlspecialchars($sol['nombre_profesor']) . "</td><td>" . htmlspecialchars($sol['cedula_profesor']) . "</td><td>" . htmlspecialchars($sol['anio_semestre']) . "</td><td>" . htmlspecialchars($sol['novedad']) . "</td><td><strong>" . $resultado . "</strong></td></tr>";
            }
            $tabla_html .= "</tbody></table>";

            $cuerpo_email = "<html><body><h2>Notificación de Trámite de Novedades</h2><p>La Facultad ha tramitado un grupo de novedades para el Departamento de <strong>" . htmlspecialchars($nombre_depto) . "</strong>.</p><p>A continuación se presenta el resumen:</p>" . $tabla_html . "<p><strong>Observaciones Generales de la Facultad:</strong> " . ($observacion ? htmlspecialchars($observacion) : 'No se registraron observaciones.') . "</p><p>Este es un correo generado automáticamente. Por favor, revise la plataforma para más detalles.</p></body></html>";
            
            $asunto = "Trámite de Novedades de Facultad para el Dpto. de " . $nombre_depto;
            
            $mail = new PHPMailer(true);
            try {
                // Configuración idéntica a la que ya te funciona
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_username'];
                $mail->Password   = $config['smtp_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp_port'];
                $mail->CharSet    = 'UTF-8';
                $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                // Remitente y Destinatarios
                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email_depto, $nombre_depto);
                $mail->addAddress($email_vra);
                
                // Contenido del Correo
                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body    = $cuerpo_email;

                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error al enviar a $email_depto: {$mail->ErrorInfo}");
            }
        }
    }
    // ===== TERMINA NUEVO BLOQUE =====

   if ($success_count > 0) {
        responder_json(true, "$success_count registros han sido procesados exitosamente (incluyendo contrapartes de 'Cambio de Vinculación').", ['processed_ids' => array_values($ids_a_procesar)]);
    } else {
        responder_json(false, 'Ningún registro pudo ser actualizado.');
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    responder_json(false, "Error crítico: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>