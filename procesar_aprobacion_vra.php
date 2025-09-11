<?php
// procesar_aprobacion_vra.php (Versión Final con Notificación por Correo)

// ===== INICIO BLOQUE 1: INCLUSIÓN DE PHPMailer =====
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que las rutas a los archivos de PHPMailer sean correctas
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Cargar la configuración de correo desde tu archivo config_email.php
$config = require 'config_email.php';
// ===== FIN BLOQUE 1 =====

ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once('conn.php');

$response = ['success' => false, 'error' => 'Error desconocido.'];

try {
    if ($conn->connect_error) {
        throw new Exception('Fallo en la conexión a la base de datos: ' . $conn->connect_error);
    }
    session_start();

    // 1. Verificación de Permisos (sin cambios)
    if (!isset($_SESSION['name'])) throw new Exception('Sesión no iniciada.');
    $nombre_sesion = $_SESSION['name'];
    $stmt_user = $conn->prepare("SELECT Id, tipo_usuario FROM users WHERE Name = ?");
    $stmt_user->bind_param("s", $nombre_sesion);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 0) throw new Exception('Usuario de sesión no encontrado.');
    $user_row = $result_user->fetch_assoc();
    $aprobador_id = $user_row['Id'];
    if ($user_row['tipo_usuario'] != 1) throw new Exception('Acceso denegado.');
    $stmt_user->close();

    $accion = $_POST['accion'] ?? null;
    $solicitudes_json = $_POST['solicitudes'] ?? null;
    $anio_semestre = $_POST['anio_semestre'] ?? null;

    if (!$accion || !$solicitudes_json || !$anio_semestre) {
        throw new Exception('Datos incompletos.');
    }

    $solicitudes = json_decode($solicitudes_json, true);
    if (empty($solicitudes) || !is_array($solicitudes)) {
        throw new Exception('No se proporcionaron solicitudes válidas.');
    }
    
    $estado_db = ($accion === 'aprobar') ? 'APROBADO' : 'RECHAZADO';

    $conn->begin_transaction();

    // ===== INICIO BLOQUE 2: INICIALIZACIÓN PARA CORREOS =====
    $email_data_por_destinatario = [];
    $solicitudes_procesadas_para_email = [];
    // ===== FIN BLOQUE 2 =====

    $sql_update_wc = "UPDATE solicitudes_working_copy SET estado_vra = ?, observacion_vra = ?, tipo_reemplazo = ?, fecha_aprobacion_vra = NOW(), aprobador_vra_id = ? WHERE id_solicitud = ? AND estado_vra = 'PENDIENTE'";
    $stmt_update_wc = $conn->prepare($sql_update_wc);

    foreach ($solicitudes as $sol) {
        $id = $sol['id'] ?? null;
        $observacion = trim($sol['observacion'] ?? '');
    $tipo_reemplazo = $sol['tipo_reemplazo'] ?? 'Otro'; // Añade esta línea

        if (!is_numeric($id)) continue;
        if ($estado_db === 'RECHAZADO' && empty($observacion)) {
            throw new Exception("La observación es obligatoria para rechazar la solicitud ID: " . $id);
        }

        // --- PARTE 1: ACTUALIZAR WORKING_COPY (LÓGICA EXISTENTE) ---
$stmt_update_wc->bind_param("sssii", $estado_db, $observacion, $tipo_reemplazo, $aprobador_id, $id);
        if (!$stmt_update_wc->execute()) throw new Exception("Error al actualizar la solicitud ID: $id en working_copy.");

        // ... (Tu lógica existente para actualizar la pareja en 'Cambio de Vinculación' y para actualizar la tabla 'solicitudes' va aquí) ...
// Ahora, buscamos si es parte de un "Cambio de Vinculación" para actualizar su pareja
        $stmt_find_pair = $conn->prepare("SELECT cedula, novedad FROM solicitudes_working_copy WHERE id_solicitud = ?");
        $stmt_find_pair->bind_param("i", $id);
        $stmt_find_pair->execute();
        $pair_info = $stmt_find_pair->get_result()->fetch_assoc();
        $stmt_find_pair->close();

        if ($pair_info) {
            $novedad_actual = strtolower($pair_info['novedad']);
            $cedula_actual = $pair_info['cedula'];
            $novedad_pareja = '';

            if ($novedad_actual === 'adicionar') $novedad_pareja = 'eliminar';
            if ($novedad_actual === 'eliminar') $novedad_pareja = 'adicionar';

            if ($novedad_pareja) {
                $sql_update_pair = "UPDATE solicitudes_working_copy SET estado_vra = ?, observacion_vra = ?, fecha_aprobacion_vra = NOW(), aprobador_vra_id = ? 
                                    WHERE cedula = ? AND anio_semestre = ? AND LOWER(novedad) = ? AND estado_vra = 'PENDIENTE'";
                $stmt_update_pair = $conn->prepare($sql_update_pair);
                $stmt_update_pair->bind_param("ssisss", $estado_db, $observacion, $aprobador_id, $cedula_actual, $anio_semestre, $novedad_pareja);
                if (!$stmt_update_pair->execute()) {
                    throw new Exception("Error al actualizar la pareja de la solicitud ID: $id.");
                }
                $stmt_update_pair->close();
            }
        }

        // --- PARTE 2: APLICAR CAMBIOS A LA TABLA 'solicitudes' (SOLO SI SE APRUEBA) ---
        if ($accion === 'aprobar') {
            // Re-leemos los datos de la fila actual para asegurarnos de que tenemos todo
            $stmt_get_data = $conn->prepare("SELECT * FROM solicitudes_working_copy WHERE id_solicitud = ?");
            $stmt_get_data->bind_param("i", $id);
            $stmt_get_data->execute();
            $wc_row = $stmt_get_data->get_result()->fetch_assoc();
            $stmt_get_data->close();

            if ($wc_row) {
                $novedad = strtolower($wc_row['novedad']);
                $id_original = $wc_row['fk_id_solicitud_original'];

                if ($novedad === 'modificar') {
                    if (!$id_original) throw new Exception("ID original no encontrado para modificación (ID: $id).");
                    $sql_update_sol = "UPDATE solicitudes SET tipo_dedicacion = ?, tipo_dedicacion_r = ?, horas = ?, horas_r = ?, sede = ?, anexa_hv_docente_nuevo = ?, actualiza_hv_antiguo = ?, novedad = 'Modificar', id_novedad = ? WHERE id_solicitud = ?";
                    $stmt_update_sol = $conn->prepare($sql_update_sol);
                    $stmt_update_sol->bind_param("ssddssssi", $wc_row['tipo_dedicacion'], $wc_row['tipo_dedicacion_r'], $wc_row['horas'], $wc_row['horas_r'], $wc_row['sede'], $wc_row['anexa_hv_docente_nuevo'], $wc_row['actualiza_hv_antiguo'], $id_original, $id_original);
                    if (!$stmt_update_sol->execute()) throw new Exception("Error al modificar en 'solicitudes' para ID: $id_original.");
                    $stmt_update_sol->close();
                }
                
                elseif ($novedad === 'eliminar') {
                    if (!$id_original) throw new Exception("ID original no encontrado para eliminación (ID: $id).");
                    $sql_delete_sol = "UPDATE solicitudes SET estado = 'an', novedad = 'Eliminar', id_novedad = ? WHERE id_solicitud = ?";
                    $stmt_delete_sol = $conn->prepare($sql_delete_sol);
                    $stmt_delete_sol->bind_param("ii", $id_original, $id_original);
                    if (!$stmt_delete_sol->execute()) throw new Exception("Error al eliminar en 'solicitudes' para ID: $id_original.");
                    $stmt_delete_sol->close();
                }

                elseif ($novedad === 'adicion' || $novedad === 'adicionar') {
                    $sql_insert_sol = "INSERT INTO solicitudes (anio_semestre, facultad_id, departamento_id, tipo_docente, cedula, nombre, tipo_dedicacion, tipo_dedicacion_r, horas, horas_r, sede, anexa_hv_docente_nuevo, actualiza_hv_antiguo, s_observacion, pregrado, especializacion, maestria, doctorado, otro_estudio, experiencia_docente, experiencia_profesional, otra_experiencia, novedad, id_novedad) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Adicionar', ?)";
                    $stmt_insert_sol = $conn->prepare($sql_insert_sol);
                    // CORRECCIÓN: El bind_param debe tener 23 tipos, no 24.
                    $stmt_insert_sol->bind_param("siisssssddssssssssssssi", $wc_row['anio_semestre'], $wc_row['facultad_id'], $wc_row['departamento_id'], $wc_row['tipo_docente'], $wc_row['cedula'], $wc_row['nombre'], $wc_row['tipo_dedicacion'], $wc_row['tipo_dedicacion_r'], $wc_row['horas'], $wc_row['horas_r'], $wc_row['sede'], $wc_row['anexa_hv_docente_nuevo'], $wc_row['actualiza_hv_antiguo'], $wc_row['s_observacion'], $wc_row['pregrado'], $wc_row['especializacion'], $wc_row['maestria'], $wc_row['doctorado'], $wc_row['otro_estudio'], $wc_row['experiencia_docente'], $wc_row['experiencia_profesional'], $wc_row['otra_experiencia'], $wc_row['id_solicitud']);
                    if (!$stmt_insert_sol->execute()) throw new Exception("Error al adicionar en 'solicitudes' para ID: " . $wc_row['id_solicitud']);
                    $stmt_insert_sol->close();
                }
            }
        }
        // ===== INICIO BLOQUE 3: RECOPILACIÓN DE DATOS PARA EL CORREO (DENTRO DEL BUCLE) =====
        $stmt_info = $conn->prepare("
            SELECT s.*, d.depto_nom_propio as nombre_depto, d.email_depto, f.NOMBREF_FAC as nombre_fac, f.email_fac
            FROM solicitudes_working_copy s
            JOIN deparmanentos d ON s.departamento_id = d.PK_DEPTO
            JOIN facultad f ON s.facultad_id = f.PK_FAC
            WHERE s.id_solicitud = ?
        ");
        $stmt_info->bind_param("i", $id);
        $stmt_info->execute();
        if ($solicitud_data = $stmt_info->get_result()->fetch_assoc()) {
            $solicitudes_procesadas_para_email[] = $solicitud_data;
        }
        $stmt_info->close();
        // ===== FIN BLOQUE 3 =====
    }
    $stmt_update_wc->close();

    $conn->commit();
    
    // ===== INICIO BLOQUE 4: ENVÍO DE CORREOS (DESPUÉS DEL COMMIT) =====
    if (!empty($solicitudes_procesadas_para_email)) {
        
        // Agrupar solicitudes por destinatario (email de depto y fac)
        foreach ($solicitudes_procesadas_para_email as $sol) {
            $email_depto = 'elmerjs@unicauca.edu.co'; // Para pruebas. Descomentar la siguiente línea para producción.
            // $email_depto = $sol['email_depto'];
            $email_fac = 'elmerjs@gmail.com'; // Para pruebas. Descomentar la siguiente línea para producción.
            // $email_fac = $sol['email_fac'];

            $info_para_tabla = [
                'nombre_profesor' => $sol['nombre'], 'cedula_profesor' => $sol['cedula'],
                'anio_semestre' => $sol['anio_semestre'], 'novedad' => $sol['novedad'],
                'estado_final' => $estado_db, 'observacion_vra' => $sol['observacion_vra']
            ];

            if (!isset($email_data_por_destinatario[$email_depto])) {
                $email_data_por_destinatario[$email_depto] = ['nombre_entidad' => $sol['nombre_depto'], 'solicitudes' => []];
            }
            $email_data_por_destinatario[$email_depto]['solicitudes'][] = $info_para_tabla;

            if (!isset($email_data_por_destinatario[$email_fac])) {
                $email_data_por_destinatario[$email_fac] = ['nombre_entidad' => $sol['nombre_fac'], 'solicitudes' => []];
            }
            $email_data_por_destinatario[$email_fac]['solicitudes'][] = $info_para_tabla;
        }

        foreach ($email_data_por_destinatario as $email_destinatario => $data) {
            $nombre_entidad = $data['nombre_entidad'];
            
            $tabla_html = "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'><thead><tr style='background-color: #f2f2f2;'><th>Profesor</th><th>Cédula</th><th>Periodo</th><th>Novedad</th><th>Resultado VRA</th><th>Observación VRA</th></tr></thead><tbody>";
            foreach ($data['solicitudes'] as $sol_email) {
                $resultado = ($sol_email['estado_final'] === 'APROBADO') ? 'Aprobado' : 'Rechazado';
                $tabla_html .= "<tr><td>" . htmlspecialchars($sol_email['nombre_profesor']) . "</td><td>" . htmlspecialchars($sol_email['cedula_profesor']) . "</td><td>" . htmlspecialchars($sol_email['anio_semestre']) . "</td><td>" . htmlspecialchars($sol_email['novedad']) . "</td><td><strong>" . $resultado . "</strong></td><td>" . htmlspecialchars($sol_email['observacion_vra']) . "</td></tr>";
            }
            $tabla_html .= "</tbody></table>";

            $asunto = "Respuesta de Vicerrectoría a Novedades de Contratación - " . $nombre_entidad;
            $cuerpo_email = "<html><body><h2>Notificación de Trámite de Novedades</h2><p>La Vicerrectoría Académica ha dado respuesta a un grupo de novedades para su dependencia (<strong>" . htmlspecialchars($nombre_entidad) . "</strong>).</p><p>A continuación se presenta el resumen:</p>" . $tabla_html . "<p>Este es un correo generado automáticamente. Por favor, revise la plataforma para más detalles.</p></body></html>";
            
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_username'];
                $mail->Password   = $config['smtp_password'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp_port'];
                $mail->CharSet    = 'UTF-8';
                $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email_destinatario, $nombre_entidad);
                
                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body    = $cuerpo_email;
                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error al enviar a $email_destinatario: {$mail->ErrorInfo}");
            }
        }
    }
    // ===== FIN BLOQUE 4 =====

    $response['success'] = true;

} catch (Exception $e) {
    if (isset($conn) && $conn->ping()) $conn->rollback();
    $response['error'] = $e->getMessage();
} finally {
    if (isset($conn) && $conn->ping()) $conn->close();
}

ob_end_clean();
echo json_encode($response);
exit;