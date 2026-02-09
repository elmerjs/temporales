<?php
// procesar_facultad_seleccion.php (LISTO PARA PRUEBAS Y FÁCIL CAMBIO A PRODUCCIÓN)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

$config = require 'config_email.php';

ob_start();
ini_set('display_errors', 0); 
error_reporting(E_ALL);
header('Content-Type: application/json');

session_start();
require_once('conn.php');

function responder_json($success, $message, $data = []) {
    ob_end_clean();
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

try {
    // 1. CAPTURA DE DATOS
    $action = $_POST['action'] ?? '';
    $selected_ids = $_POST['selected_ids'] ?? [];
    $observacion = $_POST['observacion'] ?? '';
    $anio_semestre = $_POST['anio_semestre'] ?? '';
    
    // DATOS DEL OFICIO
    $numero_oficio = $_POST['numero_oficio'] ?? null;
    $fecha_oficio  = $_POST['fecha_oficio'] ?? null;
    $elaborado_por = $_POST['elaborado_por'] ?? null;
    $oficio_con_fecha_fac = ($numero_oficio && $fecha_oficio) ? "$numero_oficio $fecha_oficio" : null;

    $id_facultad = $_SESSION['id_facultad'] ?? null;
    $aprobador_id = $_SESSION['aprobador_id_logged_in'] ?? null;

    if (empty($action) || empty($selected_ids) || !$id_facultad) {
        responder_json(false, 'Datos incompletos o sesión inválida.');
    }

    if ($action === 'avalar' && empty($numero_oficio)) {
        responder_json(false, 'El número de oficio es obligatorio para avalar.');
    }

    $conn->begin_transaction();

    // 2. PREPARACIÓN DE IDs (Parejas Adición/Eliminación)
    $ids_a_procesar = $selected_ids;
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $types = str_repeat('i', count($selected_ids));
    
    $sql_c = "SELECT cedula FROM solicitudes_working_copy WHERE id_solicitud IN ($placeholders) AND (novedad = 'Adicion' OR novedad = 'adicionar')";
    $stmt_c = $conn->prepare($sql_c);
    $stmt_c->bind_param($types, ...$selected_ids);
    $stmt_c->execute();
    $res_c = $stmt_c->get_result();
    $cedulas = [];
    while ($row = $res_c->fetch_assoc()) $cedulas[] = $row['cedula'];
    $stmt_c->close();

    if (!empty($cedulas)) {
        $p_ced = implode(',', array_fill(0, count($cedulas), '?'));
        $t_ced = str_repeat('s', count($cedulas));
        $sql_elim = "SELECT id_solicitud FROM solicitudes_working_copy WHERE cedula IN ($p_ced) AND novedad = 'Eliminar' AND anio_semestre = ? AND facultad_id = ? AND estado_facultad = 'PENDIENTE'";
        $stmt_e = $conn->prepare($sql_elim);
        $params_e = array_merge($cedulas, [$anio_semestre, $id_facultad]);
        $stmt_e->bind_param($t_ced . 'si', ...$params_e);
        $stmt_e->execute();
        $res_e = $stmt_e->get_result();
        while ($row = $res_e->fetch_assoc()) $ids_a_procesar[] = $row['id_solicitud'];
        $stmt_e->close();
    }
    $ids_a_procesar = array_unique(array_map('intval', $ids_a_procesar));

    // 3. ACTUALIZACIÓN EN BD
    $estado_nuevo = ($action === 'avalar') ? 'APROBADO' : 'RECHAZADO';
    $success_count = 0;
    $departamento_emails = [];

    // Preparamos la consulta
    if ($action === 'avalar') {
        $sql = "UPDATE solicitudes_working_copy 
                SET estado_facultad = ?, fecha_aprobacion_facultad = NOW(), aprobador_facultad_id = ?, observacion_facultad = ?,
                    oficio_fac = ?, fecha_oficio_fac = ?, oficio_con_fecha_fac = ?, elaborado_por = ?
                WHERE id_solicitud = ? AND facultad_id = ?";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "UPDATE solicitudes_working_copy 
                SET estado_facultad = ?, fecha_aprobacion_facultad = NOW(), aprobador_facultad_id = ?, observacion_facultad = ?
                WHERE id_solicitud = ? AND facultad_id = ?";
        $stmt = $conn->prepare($sql);
    }

    foreach ($ids_a_procesar as $id) {
        if ($action === 'avalar') {
            $stmt->bind_param("sisssssii", $estado_nuevo, $aprobador_id, $observacion, $numero_oficio, $fecha_oficio, $oficio_con_fecha_fac, $elaborado_por, $id, $id_facultad);
        } else {
            $stmt->bind_param("sisii", $estado_nuevo, $aprobador_id, $observacion, $id, $id_facultad);
        }

        if ($stmt->execute()) {
            $success_count++;
            // Recopilar info para correo
            $q_info = "SELECT s.nombre, s.cedula, s.anio_semestre, s.novedad, d.depto_nom_propio, d.email_depto 
                       FROM solicitudes_working_copy s JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO 
                       WHERE s.id_solicitud = $id";
            $r_info = $conn->query($q_info);
            if ($d = $r_info->fetch_assoc()) {
                $email = $d['email_depto'];
                if ($email) {
                    $departamento_emails[$email]['nombre_depto'] = $d['depto_nom_propio'];
                    $departamento_emails[$email]['solicitudes'][] = [
                        'nombre_profesor' => $d['nombre'],
                        'cedula_profesor' => $d['cedula'],
                        'anio_semestre' => $d['anio_semestre'],
                        'novedad' => $d['novedad'],
                        'estado_campo' => $estado_nuevo
                    ];
                }
            }
        }
    }
    $stmt->close();
    $conn->commit();

    // 4. ENVÍO DE CORREOS (LÓGICA AJUSTABLE)
    if ($success_count > 0 && !empty($departamento_emails)) {
        
        // --- CONFIGURACIÓN DE DESTINATARIOS ---
        // MODO PRUEBA: Todo llega a tu correo
        //$email_destino_fijo = 'elmerjs@unicauca.edu.co'; 
        
        // MODO PRODUCCIÓN: Descomenta esto para usar los correos reales
         $email_destino_fijo = null; 
        
        $email_vra_copia = 'labor@unicauca.edu.co';

        foreach ($departamento_emails as $email_depto_real => $data) {
            $nombre_depto = $data['nombre_depto'];

            // Consolidación de Novedades (Parejas)
            $solicitudes_consolidadas = [];
            $cedulas_procesadas = [];
            foreach ($data['solicitudes'] as $sol) {
                $cedula = $sol['cedula_profesor'];
                if (in_array($cedula, $cedulas_procesadas)) continue;
                
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
                    $sol['novedad'] = 'Cambio de Vinculación'; 
                    $solicitudes_consolidadas[] = $sol;
                    $cedulas_procesadas[] = $cedula; 
                } else {
                    $solicitudes_consolidadas[] = $sol;
                }
            }

            // Construir Tabla HTML
            $tabla_html = "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'><thead><tr style='background-color: #f2f2f2;'><th>Profesor</th><th>Cédula</th><th>Periodo</th><th>Novedad</th><th>Resultado</th></tr></thead><tbody>";
            foreach ($solicitudes_consolidadas as $sol) {
                $resultado = ($sol['estado_campo'] === 'APROBADO') ? 'Avalado' : 'No Avalado';
                $tabla_html .= "<tr><td>{$sol['nombre_profesor']}</td><td>{$sol['cedula_profesor']}</td><td>{$sol['anio_semestre']}</td><td>{$sol['novedad']}</td><td><strong>$resultado</strong></td></tr>";
            }
            $tabla_html .= "</tbody></table>";

            $cuerpo_email = "<html><body><h2>Notificación de Trámite</h2><p>Facultad ha tramitado novedades para: <strong>$nombre_depto</strong>.</p>$tabla_html<p><strong>Observaciones:</strong> " . ($observacion ?: 'Ninguna.') . "</p></body></html>";
            
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $config['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['smtp_username'];
                $mail->Password = $config['smtp_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $config['smtp_port'];
                $mail->CharSet = 'UTF-8';
                $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                $mail->setFrom($config['from_email'], $config['from_name']);
                
                // --- LÓGICA DE ENVÍO (PRUEBA vs REAL) ---
                if ($email_destino_fijo) {
                    // EN MODO PRUEBA: Solo a ti
                    $mail->addAddress($email_destino_fijo, "PRUEBA (Orig: $nombre_depto)");
                    $asunto = "DEBUG: Trámite Novedades - $nombre_depto";
                } else {
                    // EN MODO REAL: Al Departamento y copia a VRA
                    $mail->addAddress($email_depto_real, $nombre_depto);
                    $mail->addAddress($email_vra_copia);
                    $asunto = "Trámite de Novedades de Facultad para el Dpto. de " . $nombre_depto;
                }

                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body = $cuerpo_email;
                $mail->send();
            } catch (Exception $e) {
                error_log("Error envío correo: {$mail->ErrorInfo}");
            }
        }
    }

    if ($success_count > 0) {
        responder_json(true, "Proceso exitoso. Registros actualizados: $success_count.", ['processed_ids' => array_values($ids_a_procesar)]);
    } else {
        responder_json(false, 'No se pudo actualizar ningún registro.');
    }

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    responder_json(false, "Error crítico: " . $e->getMessage());
} finally {
    if (isset($conn)) $conn->close();
}