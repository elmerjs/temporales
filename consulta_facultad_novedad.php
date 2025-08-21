<?php
$active_menu_item = 'novedades';

require('include/headerz.php');

require_once('conn.php');
require 'funciones.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';
$config = require 'config_email.php';

// --- Función para normalizar valores vacíos ---
function normalize_empty_values($value) {
    $trimmed_value = trim(strval($value));
    return empty($trimmed_value) ? null : $trimmed_value;
}

// --- Obtención del año/semestre actual ---
$anio_semestre_default = '2025-2';
$anio_semestre = isset($_POST['anio_semestre'])
    ? $_POST['anio_semestre']
    : (isset($_GET['anio_semestre']) ? $_GET['anio_semestre'] : $anio_semestre_default);

// --- Obtención del ID de la Facultad, ID del Departamento y Tipo de Usuario Logueado ---
$id_facultad = null;
$id_departamento = null; // Nuevo: para usuarios de departamento
$tipo_usuario = null;    // Nuevo: para almacenar el tipo de usuario
$aprobador_id_logged_in = null; // ID del usuario logueado, usado como aprobador para facultad o VRA

if (isset($_SESSION['name'])) {
    $nombre_sesion = $_SESSION['name'];

    // Modificado: Seleccionar también tipo_usuario y fk_depto_user
    $stmt_user = $conn->prepare("SELECT Id, fk_fac_user, fk_depto_user, tipo_usuario FROM users WHERE Name = ?");
    $stmt_user->bind_param("s", $nombre_sesion);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($result_user->num_rows > 0) {
        $user_row = $result_user->fetch_assoc();
        $aprobador_id_logged_in = $user_row['Id']; // ID del usuario que aprueba
        $tipo_usuario = $user_row['tipo_usuario'];

        // Asignar IDs basados en el tipo de usuario
        if ($tipo_usuario == 2) { // Usuario de Facultad
            $id_facultad = $user_row['fk_fac_user'];
        } elseif ($tipo_usuario == 3) { // Usuario de Departamento
            $id_facultad = $user_row['fk_fac_user']; // Se sigue obteniendo para consistencia, aunque no se use en filtros
            $id_departamento = $user_row['fk_depto_user'];
        }
        // Si tipo_usuario es 1 (Administrador), $id_facultad y $id_departamento permanecerán null,
        // lo que se usará para no aplicar filtros en las consultas principales.

    }
    $stmt_user->close();
}

// Validación de acceso (si no es admin, debe tener un ID de facultad o departamento válido)
if ($tipo_usuario === null) {
    die("Error: Sesión no iniciada o usuario no encontrado.");
} elseif ($tipo_usuario === 2 && is_null($id_facultad)) {
    die("Error: No se pudo determinar la facultad para el usuario logueado. Por favor, inicie sesión correctamente.");
} elseif ($tipo_usuario === 3 && is_null($id_departamento)) { // Simplificado según requerimiento: solo depto es suficiente
    die("Error: No se pudo determinar el departamento para el usuario logueado. Por favor, inicie sesión correctamente.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $selected_ids = isset($_POST['selected_solicitudes']) ? $_POST['selected_solicitudes'] : [];

    if (!empty($selected_ids)) {
        $success_count = 0;
        $error_count = 0;

        $new_status_facultad = '';
        $new_status_vra = '';

        if ($action === 'aprobar_seleccionados') { // Acción para Facultad
            $new_status_facultad = 'APROBADO';
        } elseif ($action === 'rechazar_seleccionados') { // Acción para Facultad
            $new_status_facultad = 'RECHAZADO';
        } elseif ($action === 'deshacer_aprobacion') { // Acción para Facultad
            $new_status_facultad = 'PENDIENTE';
        } elseif ($action === 'aprobar_admin') { // Acción para Admin (VRA)
            $new_status_vra = 'APROBADO';
        } elseif ($action === 'rechazar_admin') { // Acción para Admin (VRA)
            $new_status_vra = 'RECHAZADO';
        } elseif ($action === 'deshacer_admin') { // Acción para Admin (VRA)
            $new_status_vra = 'PENDIENTE';
        }

        // Array para agrupar solicitudes por departamento para el envío de correos consolidados
        $departamento_emails = [];

        if (!empty($new_status_facultad) || !empty($new_status_vra)) {
            foreach ($selected_ids as $id) {
                if ($tipo_usuario == 2) {
    $obs_key = "observacion_facultad_$id";
} elseif ($tipo_usuario == 1) {
    $obs_key = "observacion_vra_$id";
} else {
    $obs_key = null;
}

$observacion = $obs_key && isset($_POST[$obs_key]) ? $_POST[$obs_key] : '';

                $update_sql = "";
                $params_update = [];
                $types_update = "";

                // --- Acciones para Facultad (Tipo 2) ---
                if ($tipo_usuario == 2) {
                    if ($action === 'deshacer_aprobacion') {
                        $update_sql = "
                            UPDATE solicitudes_working_copy
                            SET
                                estado_facultad = ?,
                                fecha_aprobacion_facultad = NULL,
                                aprobador_facultad_id = NULL,
                                observacion_facultad = ?
                            WHERE
                                id_solicitud = ?
                                AND anio_semestre = ?
                                AND estado_depto = 'ENVIADO'
                                AND estado_facultad IN ('APROBADO', 'RECHAZADO')
                                AND estado_vra = 'PENDIENTE'
                                AND aprobador_facultad_id = ?
                                AND facultad_id = ?
                        ";
                        $params_update = [$new_status_facultad, $observacion, $id, $anio_semestre, $aprobador_id_logged_in, $id_facultad];
                        $types_update = "ssisii";

                    } else { // Aprobar o Rechazar para Facultad
                        $update_sql = "
                            UPDATE solicitudes_working_copy
                            SET
                                estado_facultad = ?,
                                fecha_aprobacion_facultad = NOW(),
                                aprobador_facultad_id = ?,
                                observacion_facultad = ?
                            WHERE
                                id_solicitud = ?
                                AND anio_semestre = ?
                                AND estado_depto = 'ENVIADO'
                                AND estado_facultad = 'PENDIENTE'
                                AND facultad_id = ?
                        ";
                        $params_update = [$new_status_facultad, $aprobador_id_logged_in, $observacion, $id, $anio_semestre, $id_facultad];
                        $types_update = "sisiis";
                    }
                }
                // --- Acciones para Administrador (Tipo 1) ---
                elseif ($tipo_usuario == 1) {
                    // Lógica para 'deshacer_admin'
                    if ($action === 'deshacer_admin') {
                        // Paso 1: Obtener la información de la solicitud de working_copy para identificar el id_solicitud_original
                        $sql_get_wc_info = "SELECT novedad, fk_id_solicitud_original FROM solicitudes_working_copy WHERE id_solicitud = ?";
                        $stmt_get_wc_info = $conn->prepare($sql_get_wc_info);
                        $stmt_get_wc_info->bind_param("i", $id);
                        $stmt_get_wc_info->execute();
                        $result_wc_info = $stmt_get_wc_info->get_result();
                        $row_wc_info = $result_wc_info->fetch_assoc();
                        $stmt_get_wc_info->close();

                        if ($row_wc_info) {
                            $novedad_wc = $row_wc_info['novedad'];
                            $fk_id_original = $row_wc_info['fk_id_solicitud_original'];

                            if ($novedad_wc === 'adicionar' && $fk_id_original === NULL) {
                                // Si es una novedad 'adicionar', simplemente se elimina de 'solicitudes'
                                // (Si se insertó, ya tendrá un id_novedad asociado a este $id de working_copy)
                                $sql_delete_added_solicitud = "DELETE FROM solicitudes WHERE id_novedad = ?";
                                $stmt_delete = $conn->prepare($sql_delete_added_solicitud);
                                $stmt_delete->bind_param("i", $id);
                                $stmt_delete->execute();
                                $stmt_delete->close();
                            } elseif (in_array($novedad_wc, ['Eliminar', 'Modificar']) && $fk_id_original) {
                                // Si es 'Eliminar' o 'Modificar', se restaura desde el historial
                                $sql_get_history = "SELECT * FROM solicitudes_history WHERE id_novedad_wc = ? ORDER BY change_timestamp DESC LIMIT 1";
                                $stmt_get_history = $conn->prepare($sql_get_history);
                                $stmt_get_history->bind_param("i", $id);
                                $stmt_get_history->execute();
                                $result_history = $stmt_get_history->get_result();

                                if ($row_history = $result_history->fetch_assoc()) {
                                    $sql_revert = "
                                        UPDATE solicitudes
                                        SET anio_semestre = ?, facultad_id = ?, departamento_id = ?,
                                            tipo_docente = ?, cedula = ?, nombre = ?, tipo_dedicacion = ?,
                                            tipo_dedicacion_r = ?, horas = ?, horas_r = ?, sede = ?,
                                            anexa_hv_docente_nuevo = ?, actualiza_hv_antiguo = ?, visado = ?,
                                            estado = ?, novedad = ?, puntos = ?, s_observacion = ?,
                                            tipo_reemplazo = ?, costo = ?, anexos = ?, pregrado = ?,
                                            especializacion = ?, maestria = ?, doctorado = ?, otro_estudio = ?,
                                            experiencia_docente = ?, experiencia_profesional = ?,
                                            otra_experiencia = ?,
                                            id_novedad = ?
                                        WHERE id_solicitud = ?
                                    ";
                                    $stmt_revert = $conn->prepare($sql_revert);

                                    // Se corrige la cadena de tipos
                                    $stmt_revert->bind_param(
                                        "siisssssddsssissdssdsssssssssii", // CADENA DE TIPOS CORREGIDA 
                                        $row_history['anio_semestre'],
                                        $row_history['facultad_id'],
                                        $row_history['departamento_id'],
                                        $row_history['tipo_docente'],
                                        $row_history['cedula'],
                                        $row_history['nombre'],
                                        $row_history['tipo_dedicacion'],
                                        $row_history['tipo_dedicacion_r'],
                                        $row_history['horas'],
                                        $row_history['horas_r'],
                                        $row_history['sede'],
                                        $row_history['anexa_hv_docente_nuevo'],
                                        $row_history['actualiza_hv_antiguo'],
                                        $row_history['visado'],
                                        $row_history['estado'],
                                        $row_history['novedad'],
                                        $row_history['puntos'],
                                        $row_history['s_observacion'],
                                        $row_history['tipo_reemplazo'],
                                        $row_history['costo'],
                                        $row_history['anexos'],
                                        $row_history['pregrado'],
                                        $row_history['especializacion'],
                                        $row_history['maestria'],
                                        $row_history['doctorado'],
                                        $row_history['otro_estudio'],
                                        $row_history['experiencia_docente'],
                                        $row_history['experiencia_profesional'],
                                        $row_history['otra_experiencia'],
                                        $row_history['id_novedad'], // Restaurar el id_novedad previo
                                        $fk_id_original
                                    );
                                    $stmt_revert->execute();
                                    $stmt_revert->close();
                                }
                                $stmt_get_history->close();
                            }
                        }

                        // Finalmente, se revierte el estado en solicitudes_working_copy
                        $update_sql = "
                            UPDATE solicitudes_working_copy
                            SET
                                estado_vra = ?,
                                fecha_aprobacion_vra = NULL,
                                aprobador_vra_id = NULL,
                                observacion_vra = ?
                            WHERE
                                id_solicitud = ?
                                AND anio_semestre = ?
                        ";
                        $params_update = [$new_status_vra, $observacion, $id, $anio_semestre];
                        $types_update = "ssis";

                    } elseif ($action === 'aprobar_admin') { // Aprobar VRA
                        $tipo_reemplazo_key = "tipo_reemplazo_" . $id;
                        $tipo_reemplazo_selected = isset($_POST[$tipo_reemplazo_key]) ? $_POST[$tipo_reemplazo_key] : null;
                        $update_sql = "
                           UPDATE solicitudes_working_copy
                                SET
                                    estado_vra = ?,
                                    fecha_aprobacion_vra = NOW(),
                                    aprobador_vra_id = ?,
                                    observacion_vra = ?,
                                    tipo_reemplazo = ?  
                                WHERE
                                    id_solicitud = ?
                                    AND anio_semestre = ?
                                    AND estado_facultad = 'APROBADO'
                            ";
                        
                         $params_update = [$new_status_vra, $aprobador_id_logged_in, $observacion, $tipo_reemplazo_selected, $id, $anio_semestre]; 
                         $types_update = "sissis";

                    } elseif ($action === 'rechazar_admin') { // Rechazar VRA
                        $update_sql = "
                            UPDATE solicitudes_working_copy
                            SET
                                estado_vra = ?,
                                fecha_aprobacion_vra = NOW(),
                                aprobador_vra_id = ?,
                                observacion_vra = ?
                            WHERE
                                id_solicitud = ?
                                AND anio_semestre = ?
                        ";
                        $params_update = [$new_status_vra, $aprobador_id_logged_in, $observacion, $id, $anio_semestre];
                        $types_update = "sisis";
                    }
                }
                // Los usuarios de tipo 3 no pueden ejecutar estas acciones, por lo que no hay lógica aquí.

                if (!empty($update_sql)) { // Solo ejecutar si se generó una SQL de actualización
                    $stmt_update = $conn->prepare($update_sql);
                    if ($stmt_update) {
                        $stmt_update->bind_param($types_update, ...$params_update);
                        if ($stmt_update->execute()) {
                            $success_count++;

                            // *** LÓGICA PARA ACTUALIZAR TABLA SOLICITUDES (SOLO PARA APROBACIÓN ADMIN) ***
                            if ($tipo_usuario == 1 && $action === 'aprobar_admin') {
                                // Obtener tipo de novedad y datos relevantes de la working copy
                                $sql_get_novedad = "SELECT 
                                    novedad,
                                    fk_id_solicitud_original,
                                    tipo_dedicacion,
                                    tipo_dedicacion_r,
                                    horas,
                                    horas_r,
                                    sede,
                                    anexa_hv_docente_nuevo,
                                    actualiza_hv_antiguo,
                                    s_observacion,
                                    tipo_reemplazo,
                                    anio_semestre,
                                    facultad_id,
                                    departamento_id,
                                    tipo_docente,
                                    cedula,
                                    nombre,
                                    visado,
                                    estado,
                                    puntos,
                                    costo,
                                    anexos,
                                    pregrado,
                                    especializacion,
                                    maestria,
                                    doctorado,
                                    otro_estudio,
                                    experiencia_docente,
                                    experiencia_profesional,
                                    otra_experiencia
                                FROM solicitudes_working_copy 
                                WHERE id_solicitud = ?";
                                $stmt_novedad = $conn->prepare($sql_get_novedad);
                                $stmt_novedad->bind_param("i", $id);
                                $stmt_novedad->execute();
                                $result_novedad = $stmt_novedad->get_result();
                                
                                if ($row_novedad_wc = $result_novedad->fetch_assoc()) {
                                    $novedad_wc_type = $row_novedad_wc['novedad'];
                                    $fk_id_original = $row_novedad_wc['fk_id_solicitud_original'];

                                    // 1. NOVEDAD "adicionar"
                                    if ($novedad_wc_type === 'adicionar') {
                                        // ** START: Lógica para evitar inserciones duplicadas para 'adicionar' **
                                        $sql_check_duplicate = "SELECT COUNT(*) FROM solicitudes WHERE id_novedad = ?";
                                        $stmt_check = $conn->prepare($sql_check_duplicate);
                                        $stmt_check->bind_param("i", $id);
                                        $stmt_check->execute();
                                        $result_check = $stmt_check->get_result();
                                        $row_check = $result_check->fetch_row();
                                        $exists = $row_check[0];
                                        $stmt_check->close();

                                        if ($exists == 0) { // Solo insertar si no existe un registro con este id_novedad
                                            // ** END: Lógica para evitar inserciones duplicadas para 'adicionar' **

                                            $sql_insert = "INSERT INTO solicitudes (
                                                anio_semestre, facultad_id, departamento_id, tipo_docente, cedula, nombre,
                                                tipo_dedicacion, tipo_dedicacion_r, horas, horas_r, sede, 
                                                anexa_hv_docente_nuevo, actualiza_hv_antiguo, visado, estado, novedad,
                                                puntos, s_observacion, tipo_reemplazo, costo, anexos, pregrado,
                                                especializacion, maestria, doctorado, otro_estudio, experiencia_docente,
                                                experiencia_profesional, otra_experiencia, id_novedad
                                            ) 
                                            SELECT 
                                                anio_semestre, facultad_id, departamento_id, tipo_docente, cedula, nombre,
                                                tipo_dedicacion, tipo_dedicacion_r, horas, horas_r, sede, 
                                                anexa_hv_docente_nuevo, actualiza_hv_antiguo, visado, estado, novedad,
                                                puntos, s_observacion, tipo_reemplazo, costo, anexos, pregrado,
                                                especializacion, maestria, doctorado, otro_estudio, experiencia_docente,
                                                experiencia_profesional, otra_experiencia, id_solicitud
                                            FROM solicitudes_working_copy 
                                            WHERE id_solicitud = ?";
                                            
                                            $stmt_insert = $conn->prepare($sql_insert);
                                            $stmt_insert->bind_param("i", $id);
                                            $stmt_insert->execute();
                                            $stmt_insert->close();
                                        }
                                        // Opcional: Log o manejar el caso donde se intenta re-insertar una solicitud 'adicionar'
                                        // error_log("Intento de re-insertar solicitud de adición ya existente (id_novedad: $id)");
                                    }
                                    // 2. NOVEDAD "eliminar" o "modificar"
                                    elseif (in_array($novedad_wc_type, ['Eliminar', 'Modificar']) && $fk_id_original) {
                                        // *** Guardar estado actual en historial ANTES de modificar/eliminar ***
                                        $sql_select_current = "SELECT * FROM solicitudes WHERE id_solicitud = ?";
                                        $stmt_select_current = $conn->prepare($sql_select_current);
                                        $stmt_select_current->bind_param("i", $fk_id_original);
                                        $stmt_select_current->execute();
                                        $result_current = $stmt_select_current->get_result();

                                        if ($row_current = $result_current->fetch_assoc()) {
                                            $sql_insert_history = "INSERT INTO solicitudes_history (
                                                id_solicitud_original, id_novedad_wc, change_type,
                                                anio_semestre, facultad_id, departamento_id, tipo_docente, cedula, nombre,
                                                tipo_dedicacion, tipo_dedicacion_r, horas, horas_r, sede,
                                                anexa_hv_docente_nuevo, actualiza_hv_antiguo, visado, estado, novedad,
                                                puntos, s_observacion, tipo_reemplazo, costo, anexos, pregrado,
                                                especializacion, maestria, doctorado, otro_estudio, experiencia_docente,
                                                experiencia_profesional, otra_experiencia, id_novedad
                                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

                                            $stmt_insert_history = $conn->prepare($sql_insert_history);
                                            $change_type_history = ($novedad_wc_type === 'Eliminar') ? 'ELIMINAR_APROBADO' : 'MODIFICAR_APROBADO';

                                            // Se corrige la cadena de tipos
                                            $stmt_insert_history->bind_param(
                                                "iissiisssssddsssiissdssdsddssssii", // CADENA DE TIPOS CORREGIDA
                                                $row_current['id_solicitud'],
                                                $id, // id de la working copy como id_novedad_wc
                                                $change_type_history,
                                                $row_current['anio_semestre'],
                                                $row_current['facultad_id'],
                                                $row_current['departamento_id'],
                                                $row_current['tipo_docente'],
                                                $row_current['cedula'],
                                                $row_current['nombre'],
                                                $row_current['tipo_dedicacion'],
                                                $row_current['tipo_dedicacion_r'],
                                                $row_current['horas'],
                                                $row_current['horas_r'],
                                                $row_current['sede'],
                                                $row_current['anexa_hv_docente_nuevo'],
                                                $row_current['actualiza_hv_antiguo'],
                                                $row_current['visado'],
                                                $row_current['estado'],
                                                $row_current['novedad'],
                                                $row_current['puntos'],
                                                $row_current['s_observacion'],
                                                $row_current['tipo_reemplazo'],
                                                $row_current['costo'],
                                                $row_current['anexos'],
                                                $row_current['pregrado'],
                                                $row_current['especializacion'],
                                                $row_current['maestria'],
                                                $row_current['doctorado'],
                                                $row_current['otro_estudio'],
                                                $row_current['experiencia_docente'],
                                                $row_current['experiencia_profesional'],
                                                $row_current['otra_experiencia'],
                                                $row_current['id_novedad'] 
                                            );
                                            $stmt_insert_history->execute();
                                            $stmt_insert_history->close();
                                        }
                                        $stmt_select_current->close();

                                        // Aplicar el cambio a la tabla 'solicitudes'
                                        if ($novedad_wc_type === 'Eliminar') {
                                            $sql_update_orig = "UPDATE solicitudes 
                                                SET estado = 'an',
                                                    s_observacion = ?,
                                                    tipo_reemplazo = ?,
                                                    novedad = ?,
                                                    id_novedad = ?
                                                WHERE id_solicitud = ?";

                                            $stmt_update_orig = $conn->prepare($sql_update_orig);
                                            $stmt_update_orig->bind_param(
                                                "sssii", 
                                                $row_novedad_wc['s_observacion'],
                                                $row_novedad_wc['tipo_reemplazo'],
                                                $novedad_wc_type,
                                                $id, // ID de la working_copy como id_novedad en solicitudes
                                                $fk_id_original
                                            );
                                        } elseif ($novedad_wc_type === 'Modificar') {
                                            $sql_update_orig = "UPDATE solicitudes 
                                                SET tipo_dedicacion = ?,
                                                    tipo_dedicacion_r = ?,
                                                    horas = ?,
                                                    horas_r = ?,
                                                    sede = ?,
                                                    anexa_hv_docente_nuevo = ?,
                                                    actualiza_hv_antiguo = ?,
                                                    s_observacion = ?,
                                                    tipo_reemplazo = ?,
                                                    novedad = ?,
                                                    id_novedad = ?
                                                WHERE id_solicitud = ?";

                                            $stmt_update_orig = $conn->prepare($sql_update_orig);
                                            $stmt_update_orig->bind_param(
                                                "ssddssssssii",
                                                $row_novedad_wc['tipo_dedicacion'],
                                                $row_novedad_wc['tipo_dedicacion_r'],
                                                $row_novedad_wc['horas'],
                                                $row_novedad_wc['horas_r'],
                                                $row_novedad_wc['sede'],
                                                $row_novedad_wc['anexa_hv_docente_nuevo'],
                                                $row_novedad_wc['actualiza_hv_antiguo'],
                                                $row_novedad_wc['s_observacion'],
                                                $row_novedad_wc['tipo_reemplazo'],
                                                $novedad_wc_type,
                                                $id, // ID de la working_copy como id_novedad en solicitudes
                                                $fk_id_original
                                            );
                                        }
                                        $stmt_update_orig->execute();
                                        $stmt_update_orig->close();
                                    }
                                }
                                $stmt_novedad->close();
                            }

                              // *** LÓGICA PARA RECOPILAR DATOS DEL CORREO CONSOLIDADO (PARA FACULTAD) ***
                         // *** LÓGICA PARA RECOPILAR DATOS DEL CORREO CONSOLIDADO (PARA FACULTAD Y VRA) ***
                            $should_collect_email_data = false;
                            $email_obs_field = ''; // Campo de observación a usar en el email
                            $email_status_field = ''; // Campo de estado a usar en el email
                            $email_status_label = ''; // Etiqueta del estado en la tabla del email

                            if ($tipo_usuario == 2 && ($new_status_facultad === 'APROBADO' || $new_status_facultad === 'RECHAZADO')) {
                                $should_collect_email_data = true;
                                $email_obs_field = $observacion; // $observacion ya contiene la de facultad
                                $email_status_field = $new_status_facultad;
                                $email_status_label = 'Estado Facultad';
                            } elseif ($tipo_usuario == 1 && ($new_status_vra === 'APROBADO' || $new_status_vra === 'RECHAZADO')) {
                                // Asume que $observacion y $new_status_vra contienen los valores correctos para VRA
                                $should_collect_email_data = true;
                                $email_obs_field = $observacion; // $observacion ya contiene la de VRA
                                $email_status_field = $new_status_vra;
                                $email_status_label = 'Estado VRA';
                            }

                            if ($should_collect_email_data) {
                                $stmt_info = $conn->prepare("
                                    SELECT
                                        s.anio_semestre,
                                        s.nombre,
                                        s.cedula,
                                        d.nombre_depto,
                                        d.email_depto,
                                        s.facultad_id  /* <--- ¡NUEVA LÍNEA AQUÍ! */
                                    FROM
                                        solicitudes_working_copy s
                                    JOIN
                                        deparmanentos d ON s.departamento_id = d.PK_DEPTO
                                    WHERE
                                        s.id_solicitud = ?
                                ");
                                $stmt_info->bind_param("i", $id);
                                $stmt_info->execute();
                                $result_info = $stmt_info->get_result();

                                if ($result_info->num_rows > 0) {
                                    $solicitud_data = $result_info->fetch_assoc();
                                    $email_depto_mail = 'elmerjs@unicauca.edu.co'; // Usar para pruebas
                                    // $email_depto_mail = $solicitud_data['email_depto']; // Línea real

                                    if (!empty($email_depto_mail)) {
                                        // Inicializar si es la primera solicitud para este departamento
                                        if (!isset($departamento_emails[$email_depto_mail])) {
                                            $departamento_emails[$email_depto_mail] = [
                                                'nombre_depto' => $solicitud_data['nombre_depto'],
                                                'solicitudes' => [],
                                                'facultad_id' => $solicitud_data['facultad_id'] /* <--- ¡NUEVA LÍNEA AQUÍ! */
                                            ];
                                        }
                                        $departamento_emails[$email_depto_mail]['solicitudes'][] = [
                                            'id_solicitud' => $id,
                                            'anio_semestre' => $solicitud_data['anio_semestre'],
                                            'nombre_profesor' => $solicitud_data['nombre'],
                                            'cedula_profesor' => $solicitud_data['cedula'],
                                            'observacion_campo' => $email_obs_field,
                                            'estado_campo' => $email_status_field,
                                            'estado_label' => $email_status_label
                                        ];
                                    }
                                }
                                $stmt_info->close();
                            }
                        } else {
                            $error_count++;
                            error_log("Error al actualizar solicitud $id: " . $conn->error);
                        }
                    } else {
                        $error_count++;
                        error_log("Error al preparar la declaración de actualización para id $id: " . $conn->error);
                    }

                    if (isset($stmt_update)) {
                        $stmt_update->close();
                    }
                }
            }
            
            
            
            
            // *** LÓGICA DE ENVÍO DE CORREOS CONSOLIDADOS (PARA ADMIN/VRA) ***
// *** LÓGICA DE ENVÍO DE CORREOS CONSOLIDADOS (PARA ADMIN/VRA) ***
if ($tipo_usuario == 1 && in_array($action, ['aprobar_admin', 'rechazar_admin']) && !empty($departamento_emails)) {
    // La obtención del correo de la Facultad ahora se moverá dentro del bucle foreach
    // Elimina el siguiente bloque de código:
    /*
    $sql_facultad = "SELECT nombre_fac_min, email_fac FROM facultad WHERE PK_FAC = ?";
    $stmt_facultad = $conn->prepare($sql_facultad);
    $stmt_facultad->bind_param("i", $id_facultad);
    $stmt_facultad->execute();
    $result_facultad = $stmt_facultad->get_result();
    $row_facultad = $result_facultad->fetch_assoc();
    $stmt_facultad->close();

    $nombre_facultad = $row_facultad['nombre_fac_min'] ?? 'Facultad';
    $email_facultad = $row_facultad['email_fac'] ?? '';
    */

     foreach ($departamento_emails as $email_depto => $depto_data) {
        $nombre_depto = $depto_data['nombre_depto'];
        $solicitudes_para_correo = $depto_data['solicitudes'];
        $id_facultad_del_depto = $depto_data['facultad_id'] ?? null;

        $nombre_facultad_para_correo = 'Facultad';
        $email_facultad_para_correo = '';

        if ($id_facultad_del_depto) {
            $sql_facultad_depto = "SELECT nombre_fac_min, email_fac FROM facultad WHERE PK_FAC = ?";
            $stmt_facultad_depto = $conn->prepare($sql_facultad_depto);
            if ($stmt_facultad_depto) {
                $stmt_facultad_depto->bind_param("i", $id_facultad_del_depto);
                $stmt_facultad_depto->execute();
                $result_facultad_depto = $stmt_facultad_depto->get_result();
                if ($row_facultad_depto = $result_facultad_depto->fetch_assoc()) {
                    $nombre_facultad_para_correo = $row_facultad_depto['nombre_fac_min'];
                    //$email_facultad_para_correo = $row_facultad_depto['email_fac']; //temporalemten se quita
                        $email_facultad_para_correo = 'elmerjs@gmail.com';

                }
                $stmt_facultad_depto->close();
            } else {
                error_log("Error al preparar la consulta de facultad para depto {$nombre_depto}: " . $conn->error);
            }
        }
        
        // Obtener el estado_label para el encabezado de la tabla
        // Asumimos que todas las solicitudes en este grupo tienen el mismo estado_label (VRA).
        // Si $solicitudes_para_correo no está vacío, podemos tomar el primero.
        $header_status_label = !empty($solicitudes_para_correo) ? $solicitudes_para_correo[0]['estado_label'] : 'Estado VRA';


        // Construir el cuerpo del correo
        $email_body = "
            <p>Cordial saludo,</p>
            <p>La Vicerrectoría Académica ha procesado las siguientes solicitudes de novedad de vinculación
            relacionadas con la <strong>{$nombre_facultad_para_correo}</strong>, para el departamento <strong>{$nombre_depto}</strong>:</p>
            <table style='width:100%; border-collapse: collapse; margin-top: 15px;'>
                <thead>
                    <tr style='background-color:#f2f2f2;'>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Profesor(a)</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Cédula</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Periodo</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>{$header_status_label}</th> <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Observación</th>
                    </tr>
                </thead>
                <tbody>
        ";

        foreach ($solicitudes_para_correo as $solicitud) {
            $obs_display = empty($solicitud['observacion_campo']) ? "Sin observación." : htmlspecialchars($solicitud['observacion_campo']);
            $email_body .= "
                <tr>
                    <td style='border: 1px solid #ddd; padding: 8px;'><strong>{$solicitud['nombre_profesor']}</strong></td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$solicitud['cedula_profesor']}</td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$solicitud['anio_semestre']}</td>
                    <td style='border: 1px solid #ddd; padding: 8px;'><strong>{$solicitud['estado_campo']}</strong></td>
                    <td style='border: 1px solid #ddd; padding: 8px;'>{$obs_display}</td>
                </tr>
            ";
        }

        $email_body .= "
                </tbody>
            </table>
            <p style='margin-top: 20px;'>Para más detalles, consulte la plataforma de solicitudes de vinculación:
            <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a></p>
            <p>Universitariamente,</p>
            <p><strong>Vicerrectoría Académica</strong></p>
        ";

        // Enviar correo
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom('ejurado@unicauca.edu.co', 'Sistema Vinculación Temporal');
            if (!empty($email_depto)) {
                //$mail->addAddress($email_depto, $nombre_depto);
                $mail->addAddress('elmerjs@unicauca.edu.co', 'Pruebas Sistema'); // Para pruebas
            }
            if (!empty($email_facultad_para_correo)) { // Usar el correo de la facultad del depto
                $mail->addCC($email_facultad_para_correo, $nombre_facultad_para_correo);
            }
            $mail->addCC('ejurado@unicauca.edu.co');

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "Notificación VRA: Solicitudes Procesadas de {$nombre_facultad_para_correo}";
            $mail->Body = $email_body;
            $mail->send();
        } catch (Exception $e) {
            error_log("No se pudo enviar correo VRA a {$nombre_depto} o facultad {$nombre_facultad_para_correo}: {$mail->ErrorInfo}");
        } finally {
            if (isset($mail)) {
                $mail->clearAddresses();
                $mail->clearCCs();
            }
        }
    }
}     
            
        // *** NUEVA LÓGICA AGREGADA AQUÍ ***
        // Después de procesar todas las solicitudes seleccionadas por la Facultad
        
if ($tipo_usuario == 2 && $action === 'aprobar_seleccionados' && $success_count > 0) {
    $ids_para_word = implode(',', $selected_ids);

    // Asegúrate de que $current_anio_semestre y $current_id_facultad contengan los valores correctos
    // De dónde vienen estos valores depende de tu lógica de negocio
    $anio_semestre_para_js = $anio_semestre ?? ''; // Reemplaza con tu variable real
    $id_facultad_para_js = $id_facultad ?? '';     // Reemplaza con tu variable real

echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (confirm('¿Desea generar el documento Word con las solicitudes avaladas?')) {
            var ids = '{$ids_para_word}';
            console.log('IDs que se enviarán a generar_word_solicitudes_seleccion.php:', ids);
            console.log('Cantidad de IDs:', ids.split(',').length);

            document.getElementById('wordGenSelectedIds').value = ids;
            document.getElementById('wordGenAnioSemestre').value = '{$anio_semestre_para_js}';
            document.getElementById('wordGenIdFacultad').value = '{$id_facultad_para_js}';
            
            document.getElementById('fecha_oficio').value =
                new Date().toISOString().split('T')[0];
            document.getElementById('wordGenModal').classList.remove('hidden');
        }
    });
</script>";

        }

            // *** LÓGICA DE ENVÍO DE CORREOS CONSOLIDADOS (PARA FACULTAD) ***
            // Esto se ejecuta después de procesar todas las solicitudes seleccionadas
            // *** LÓGICA DE ENVÍO DE CORREOS CONSOLIDADOS (PARA FACULTAD) ***
            // *** LÓGICA DE ENVÍO DE CORREOS CONSOLIDADOS (PARA FACULTAD) ***
            if ($tipo_usuario == 2 && !empty($departamento_emails)) {
                foreach ($departamento_emails as $email_depto => $depto_data) {
                    $nombre_depto = $depto_data['nombre_depto'];
                    $solicitudes_para_correo = $depto_data['solicitudes'];

                    // Obtener el estado_label para el encabezado de la tabla de forma segura
                    // Asumimos que todas las solicitudes en este grupo tienen el mismo estado_label (Facultad).
                    $header_status_label = !empty($solicitudes_para_correo) ? $solicitudes_para_correo[0]['estado_label'] : 'Estado Facultad';


                    // Construir el cuerpo del correo con todas las solicitudes de este departamento
                    $email_body = "
                        <p>Cordial saludo, </p>
                        <p>La Facultad ha procesado las siguientes solicitudes de novedad de vinculación para su departamento, <strong>{$nombre_depto}</strong>:</p>
                        <table style='width:100%; border-collapse: collapse; margin-top: 15px;'>
                            <thead>
                                <tr style='background-color:#f2f2f2;'>
                                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Profesor(a)</th>
                                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Cédula</th>
                                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Periodo</th>
                                    <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>{$header_status_label}</th> <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";

                    foreach ($solicitudes_para_correo as $solicitud) {
                        $obs_display = empty($solicitud['observacion_campo']) ? "Sin observación." : htmlspecialchars($solicitud['observacion_campo']);
                        $email_body .= "
                                <tr>
                                    <td style='border: 1px solid #ddd; padding: 8px;'><strong>{$solicitud['nombre_profesor']}</strong></td>
                                    <td style='border: 1px solid #ddd; padding: 8px;'>{$solicitud['cedula_profesor']}</td>
                                    <td style='border: 1px solid #ddd; padding: 8px;'>{$solicitud['anio_semestre']}</td>
                                    <td style='border: 1px solid #ddd; padding: 8px;'><strong>{$solicitud['estado_campo']}</strong></td>
                                    <td style='border: 1px solid #ddd; padding: 8px;'>{$obs_display}</td>
                                </tr>
                        ";
                    }

                    $email_body .= "
                            </tbody>
                        </table>
                        <p style='margin-top: 20px;'>Por favor, revise la plataforma solicitudes de vinculación, <a href='http://192.168.42.175/temporales/'>http://192.168.42.175/temporales/</a> para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
                        <p>Universitariamente,</p>
                        <p><strong>Decanatura de Facultad</strong></p>
                    ";

                    // Configurar y enviar el correo usando PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $config['smtp_username'];
                        $mail->Password   = $config['smtp_password'];
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true,
                            ],
                        ];

                        $mail->setFrom('ejurado@unicauca.edu.co', 'Sistema Vinculación Temporal');
                        if (!empty($email_depto)) {
                            //$mail->addAddress($email_depto, $nombre_depto);teamporalmente  solo pruebas
                            $mail->addAddress('elmerjs@unicauca.edu.co', 'Pruebas Sistema');

                        }
                        $mail->addCC('ejurado@unicauca.edu.co');

                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Subject = "Notificación Consolidada: Solicitudes de Novedad Procesadas por Facultad";
                        $mail->Body = $email_body;
                        $mail->send();
                        // error_log("Correo consolidado enviado correctamente al departamento {$nombre_depto} ({$email_depto}).");
                    } catch (Exception $e) {
                        error_log("No se pudo enviar el correo consolidado al departamento {$nombre_depto} ({$email_depto}): {$mail->ErrorInfo}");
                    } finally {
                        if (isset($mail)) {
                            $mail->clearAddresses();
                            $mail->clearCCs();
                        }
                    }
                }
            }
            $message = "Solicitudes actualizadas: $success_count";
            if ($error_count > 0) {
                $message .= " | Errores: $error_count";
            }
            echo "<script>alert('$message');</script>";
        }
    } else {
        echo "<script>alert('Por favor, seleccione al menos una solicitud.');</script>";
    }
}
// --- Obtener el filtro de estado de la URL ---
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : null;

// --- Obtener datos de solicitudes para la tabla ---
$solicitudes = [];
$total_pendientes_facultad = 0; // Estos conteos se recalcularán según el rol
$total_aprobadas_facultad = 0;
$total_rechazadas_facultad = 0;
$novedades_resumen = [
    'Adicion' => 0,
    'Modificacion' => 0,
    'Eliminacion' => 0
];

// --- Construcción dinámica de la query principal para obtener solicitudes ---
$sql_solicitudes_query = "
    SELECT
        sw.id_solicitud,
        sw.anio_semestre,
        sw.facultad_id,
        sw.departamento_id,
        sw.tipo_docente,
        sw.cedula,
        sw.nombre,
        sw.tipo_dedicacion,
        sw.tipo_dedicacion_r,
        sw.horas,
        sw.horas_r,
        sw.sede,
        sw.anexa_hv_docente_nuevo,
        sw.actualiza_hv_antiguo,
        sw.visado,
        sw.estado,
        sw.novedad,
        sw.puntos,
        sw.s_observacion,
        sw.tipo_reemplazo,
        sw.costo,
        sw.anexos,
        sw.pregrado,
        sw.especializacion,
        sw.maestria,
        sw.doctorado,
        sw.otro_estudio,
        sw.experiencia_docente,
        sw.experiencia_profesional,
        sw.otra_experiencia,
        sw.estado_depto,
        sw.oficio_depto,
        sw.fecha_envio_depto,
        sw.aprobador_depto_id,
        sw.estado_facultad,
        sw.observacion_facultad,
        sw.fecha_aprobacion_facultad,
        sw.aprobador_facultad_id,
        sw.estado_vra,
        sw.observacion_vra,
        sw.fecha_aprobacion_vra,
        sw.aprobador_vra_id,
        sw.fk_id_solicitud_original,
        f.nombre_fac_minb AS nombre_facultad,
        d.depto_nom_propio AS nombre_departamento,
        sw.novedad AS tipo_novedad
    FROM
        solicitudes_working_copy sw
    JOIN
        deparmanentos d ON (d.PK_DEPTO = sw.departamento_id)
    JOIN
        facultad f ON (f.PK_FAC = sw.facultad_id)
    WHERE
        sw.anio_semestre = ?
        AND sw.departamento_id IS NOT NULL
        AND sw.tipo_docente IS NOT NULL
        AND (sw.estado <> 'an' OR sw.estado IS NULL)
        AND sw.estado_depto = 'ENVIADO' -- Solo mostrar solicitudes ENVIADAS por departamento
        -- CAMBIO: Se eliminan las exclusiones de estado_facultad y estado_vra para mostrar rechazados
        -- AND sw.estado_facultad <> 'RECHAZADO'
        -- AND sw.estado_vra <> 'RECHAZADO'
";

$params_query = [$anio_semestre];
$types_query = "s"; // Start with 's' for anio_semestre

if ($tipo_usuario == 2 && !is_null($id_facultad)) { // Facultad
    $sql_solicitudes_query .= " AND sw.facultad_id = ?";
    $params_query[] = $id_facultad;
    $types_query .= "i";
} elseif ($tipo_usuario == 3 && !is_null($id_departamento)) { // Departamento: solo se filtra por departamento
    $sql_solicitudes_query .= " AND sw.departamento_id = ?";
    $params_query[] = $id_departamento;
    $types_query .= "i";
}
// If $tipo_usuario == 1 (Admin), no additional WHERE clauses for faculty/department are added.

if ($status_filter) {
    // Si es administrador, el filtro de estado_facultad se convierte en estado_vra
    if ($tipo_usuario == 1) {
        $sql_solicitudes_query .= " AND sw.estado_vra = ?";
    } else {
        $sql_solicitudes_query .= " AND sw.estado_facultad = ?";
    }
    $params_query[] = $status_filter;
    $types_query .= "s";
}

// Ordenar por facultad, luego por departamento, luego por nombre
$sql_solicitudes_query .= " ORDER BY sw.facultad_id ASC, sw.departamento_id ASC, sw.nombre ASC";

$stmt_solicitudes = $conn->prepare($sql_solicitudes_query);

if ($stmt_solicitudes) {
    $stmt_solicitudes->bind_param($types_query, ...$params_query);
    $stmt_solicitudes->execute();
    $result_solicitudes = $stmt_solicitudes->get_result();

    if ($result_solicitudes->num_rows > 0) {
        while ($row = $result_solicitudes->fetch_assoc()) {
            $solicitudes[] = $row;
            if (isset($novedades_resumen[$row['novedad']])) {
                $novedades_resumen[$row['novedad']]++;
            }
        }
    }
    $stmt_solicitudes->close();
} else {
    error_log("Error preparing main query: " . $conn->error);
}


// --- Construcción dinámica de la query para los conteos de las tarjetas ---
$sql_counts_base = "
    SELECT
        ";
// CAMBIO CLAVE: Usar estado_vra para conteos si es administrador, de lo contrario estado_facultad
if ($tipo_usuario == 1) {
    $sql_counts_base .= "estado_vra AS estado_relevante,";
} else {
    $sql_counts_base .= "estado_facultad AS estado_relevante,";
}
$sql_counts_base .= "
        COUNT(*) AS count
    FROM
        solicitudes_working_copy
    WHERE
        anio_semestre = ?
        AND estado_depto = 'ENVIADO' -- Solo contar solicitudes ENVIADAS por departamento
";

$params_counts = [$anio_semestre];
$types_counts = "s";

if ($tipo_usuario == 2 && !is_null($id_facultad)) { // Facultad
    $sql_counts_base .= " AND facultad_id = ?";
    $params_counts[] = $id_facultad;
    $types_counts .= "i";
} elseif ($tipo_usuario == 3 && !is_null($id_departamento)) { // Departamento: solo se filtra por departamento
    $sql_counts_base .= " AND departamento_id = ?";
    $params_counts[] = $id_departamento;
    $types_counts .= "i";
}
// Para el administrador, no se añade filtro adicional de facultad/departamento aquí,
// ya que las tarjetas deben mostrar el total general para el admin.

// CAMBIO CLAVE: Agrupar por el estado relevante (facultad o VRA)
$sql_counts_base .= " GROUP BY estado_relevante";

$stmt_counts = $conn->prepare($sql_counts_base);
if ($stmt_counts) {
    $stmt_counts->bind_param($types_counts, ...$params_counts);
    $stmt_counts->execute();
    $result_counts = $stmt_counts->get_result();

    while ($row_count = $result_counts->fetch_assoc()) {
        // CAMBIO CLAVE: Asignar conteos basados en el alias 'estado_relevante'
        if ($row_count['estado_relevante'] === 'PENDIENTE') {
            $total_pendientes_facultad = $row_count['count'];
        } elseif ($row_count['estado_relevante'] === 'APROBADO') {
            $total_aprobadas_facultad = $row_count['count'];
        } elseif ($row_count['estado_relevante'] === 'RECHAZADO') {
            $total_rechazadas_facultad = $row_count['count'];
        }
    }
    $stmt_counts->close();
} else {
    error_log("Error preparing count query: " . $conn->error);
}

     $decano = obtenerDecano($id_facultad);

$conn->close();?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Solicitudes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }
        .datatable-header {
            background-color: #e2e8f0;
            font-weight: 600;
        }
        .datatable-row:nth-child(even) {
            background-color: #f8fafc;
        }
        .status-tag {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block; /* Asegura que el padding funcione bien */
            white-space: nowrap; /* Evita que el texto se rompa */
        }
        .status-pendiente { background-color: #fcd34d; color: #92400e; }
        .status-aprobado { background-color: #a7f3d0; color: #065f46; }
        .status-rechazado { background-color: #fca5a5; color: #991b1b; }
        .novedad-adicion { background-color: #d1fae5; color: #065f46; }
        .novedad-modificacion { background-color: #fee2e2; color: #991b1b; }
        .novedad-eliminacion { background-color: #ffe4e6; color: #be123c; }
        .novedad-tag {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .tabla-condensada {
            font-size: 0.8rem;
        }
        .celda-ajustable {
            max-width: 150px;
            white-space: normal;
            word-wrap: break-word;
        }
        textarea.observacion {
            height: 2rem;
            font-size: 0.8rem;
            padding: 0.25rem;
        }
        .disabled-row {
            background-color: #f3f4f6;
            opacity: 0.8;
        }
        .disabled-row input[disabled],
        .disabled-row textarea[disabled] {
            cursor: not-allowed;
            background-color: #f9fafb;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .cursor-not-allowed {
            cursor: not-allowed;
        }
        .opacity-50 {
            opacity: 0.5;
        }
        .btn-deshacer {
            background-color: #f59e0b;
        }
        .btn-deshacer:hover {
            background-color: #d97706;
        }
        /* Estilos para el indicador de VRA INLINE */
        .vra-indicator-approved {
            background-color: #10b981; /* Tailwind green-500 */
            color: white;
        }
        .vra-indicator-rejected {
            background-color: #ef4444; /* Tailwind red-500 */
            color: white;
        }
        .vra-indicator-pending { /* Nuevo estilo para VRA PENDIENTE */
            background-color: #d1d5db; /* Tailwind gray-300 */
            color: #4b5563; /* Tailwind gray-700 */
        }
        /* Nuevos estilos para la fila completa según estado VRA */
        .bg-green-50\/50 { background-color: rgba(220, 252, 231, 0.5); } /* Tailwind green-50 with 50% opacity */
        .bg-red-50\/50 { background-color: rgba(254, 226, 226, 0.5); } /* Tailwind red-50 with 50% opacity */


        .card-link {
            cursor: pointer;
        }
        .card-link:hover .text-4xl {
            text-decoration: underline;
        }
        .active-filter-card {
            border: 2px solid;
        }
        .active-filter-card.blue-border { border-color: #3b82f6; } /* Tailwind blue-500 */
        .active-filter-card.green-border { border-color: #22c55e; } /* Tailwind green-500 */
        .active-filter-card.red-border { border-color: #ef4444; } /* Tailwind red-500 */
        .active-filter-card.purple-border { border-color: #a855f7; } /* Tailwind purple-500 */

        /* Estilos para la barra de progreso de estados */
        .progreso-estados {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0 10px 0; /* Ajustado el margen superior */
            font-size: 0.9rem;
            padding: 5px; /* Pequeño padding para visualización */
            background-color: #f0f4f8; /* Un fondo suave para la barra */
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .progreso-etapa {
            flex: 1;
            text-align: center;
            padding: 4px 6px; /* Ajustado el padding */
            border-radius: 6px; /* Redondeo ligeramente más pequeño */
            color: #fff;
            font-weight: bold;
            transition: background-color 0.3s ease; /* Suaviza el cambio de color */
        }
        .progreso-etapa.pendiente { background-color: #ffc107; color: #333; } /* Amarillo para pendiente */
        .progreso-etapa.aprobado { background-color: #28a745; } /* Verde para aprobado */
        .progreso-etapa.rechazado { background-color: #dc3545; } /* Rojo para rechazado */
        .progreso-separador {
            flex: 0.1; /* Más delgado */
            height: 3px; /* Más delgado */
            background-color: #a7b7c9; /* Un color de separador más integrado */
            margin: 0 4px; /* Espacio entre etapas y separadores */
        }
/* Estilos para el elemento que dispara el tooltip (el <span>) */
.status-tag {
    position: relative; /* CRUCIAL: El tooltip absoluto se posicionará relativo a este. */
    cursor: pointer;
    display: inline-block; /* O block, para asegurar que 'position: relative' funcione bien */
    /* Otros estilos de tu status-tag */
}

/* Estilo del contenido del tooltip (la burbuja ::after) */
.status-tag::after {
    content: attr(data-observacion); /* O el contenido real si usas JS */
    position: absolute; /* CLAVE: Saca el elemento del flujo normal, permitiendo que sobrepase al padre. */
    bottom: 125%; /* Ajusta la posición vertical (encima del elemento) */
    left: 50%;
    transform: translateX(-50%); /* Centra horizontalmente */

    background-color: #333; /* Color de fondo */
    color: #fff; /* Color de texto */
    padding: 10px 15px; /* Más padding para mejor UX */
    border-radius: 8px; /* Bordes más suaves */
    font-size: 0.9em;
    text-align: left; /* Alineación del texto dentro del tooltip */
    white-space: normal; /* PERMITE saltos de línea y que el texto se ajuste al max-width */

    min-width: 150px; /* Opcional: Ancho mínimo si la observación es muy corta */

    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease; /* Mejor transición */
    z-index: 1000; /* Asegura que el tooltip esté por encima de otros elementos */
    box-shadow: 0 4px 15px rgba(0,0,0,0.3); /* Sombra más pronunciada */
     max-width: 400px;  /* Aumenta este valor según necesites (ej: 800px, 1000px) */
    width: max-content; /* Permite que el ancho se ajuste al contenido */
    word-wrap: break-word; /* Rompe palabras largas si es necesario */
    overflow-wrap: break-word; /* Alternativa moderna para word-wrap */
    white-space: pre-line; /* Respeta saltos de línea y ajusta el texto */
}

/* Estilo del "triangulito" (flecha ::before) */
.status-tag::before {
    content: '';
    position: absolute;
    bottom: 180%; /* Alinea con el fondo del tooltip */
    left: 50%;
    transform: translateX(-50%);
    border-width: 8px; /* Tamaño del triángulo */
    border-style: solid;
    border-color: #333 transparent transparent transparent; /* Color del triángulo */
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 1001; /* Ligeramente superior al tooltip para que se vea bien */
}

/* Mostrar el tooltip al pasar el ratón */
.status-tag:hover::after,
.status-tag:hover::before {
    visibility: visible;
    opacity: 1;
    /* Efecto de "levantamiento" sutil al aparecer */
    transform: translateX(-50%) translateY(-5px);
}
table {
    overflow: visible !important;
}

td, th {
    position: relative; /* Necesario para que el tooltip no se corte */
    overflow: visible !important;
}
/* Opcional: Ajuste para tooltips muy largos o en bordes de la pantalla */
/* Puedes añadir JS para manejar la posición si el tooltip sale de la pantalla */
    </style>

</head>
<body class="bg-gray-100 text-gray-800">
    <div class="container pt-2 pb-8">
             <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <?php
                if ($tipo_usuario == 1) {
                    echo "Módulo de Administración";
                } elseif ($tipo_usuario == 2) {
                    echo "Módulo de Facultad";
                } elseif ($tipo_usuario == 3) {
                    echo "Módulo de Departamento";
                    $cierreperiodonov = obtenerperiodonov($anio_semestre);

                    // Botón para tipo de usuario 3
                    $url_novedad = "consulta_todo_depto_novedad.php?" .
                                 "facultad_id=" . urlencode($id_facultad) .
                                 "&anio_semestre=" . urlencode($anio_semestre) .
                                 "&departamento_id=" . urlencode($id_departamento);

                    if($cierreperiodonov <> 1) { // si está abierto novedades
                        echo ' <a href="'.htmlspecialchars($url_novedad).'" class="btn btn-primary" style="display: inline-block; margin-left: 15px;">
                                <i class="fas fa-file-alt"></i> Agregar/ver Novedades
                              </a>';
                    } else { // si está cerrado novedades
                        echo ' <a href="#" class="btn btn-secondary disabled-btn" style="display: inline-block; margin-left: 15px; cursor: not-allowed; opacity: 0.6;"
                               title="Período cerrado para novedades">
                                <i class="fas fa-file-alt"></i> Agregar/ver Novedades
                              </a>';
                    }
                } else {
                    echo "Módulo de Solicitudes";
                }
                ?>
            </h1>
        <p class="text-lg text-gray-700 mb-8">Bienvenido. Aquí puede revisar y gestionar las solicitudes de docentes.</p>

   <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-2 rounded-lg shadow-md flex flex-col items-center justify-center
        <?php echo ($status_filter === 'PENDIENTE') ? 'active-filter-card blue-border' : ''; ?>">
        <p class="text-gray-500 text-sm">Pendientes de Revisión</p>
        <a href="?anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>&status_filter=PENDIENTE" class="card-link">
            <p class="text-4xl font-bold text-blue-600"><?php echo $total_pendientes_facultad; ?></p>
        </a>
    </div>

    <div class="bg-white p-2 rounded-lg shadow-md flex flex-col items-center justify-center
        <?php echo ($status_filter === 'APROBADO') ? 'active-filter-card green-border' : ''; ?>">
        <p class="text-gray-500 text-sm">
            <?php echo ($tipo_usuario == 2) ? 'Avaladas' : 'Aprobadas'; ?>
        </p>
        <a href="?anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>&status_filter=APROBADO" class="card-link">
            <p class="text-4xl font-bold text-green-600"><?php echo $total_aprobadas_facultad; ?></p>
        </a>
    </div>

    <div class="bg-white p-2 rounded-lg shadow-md flex flex-col items-center justify-center
        <?php echo ($status_filter === 'RECHAZADO') ? 'active-filter-card red-border' : ''; ?>">
        <p class="text-gray-500 text-sm">
            <?php echo ($tipo_usuario == 2) ? 'No Avaladas' : 'No Aprobadas'; ?>
        </p>
        <a href="?anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>&status_filter=RECHAZADO" class="card-link">
            <p class="text-4xl font-bold text-red-600"><?php echo $total_rechazadas_facultad; ?></p>
        </a>
    </div>

    <div class="bg-white p-2 rounded-lg shadow-md flex flex-col items-center justify-center
        <?php echo is_null($status_filter) ? 'active-filter-card purple-border' : ''; ?>">
        <p class="text-gray-500 text-sm">Total Novedades (Dpto. Enviadas)</p>
        <a href="?anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" class="card-link">
            <p class="text-4xl font-bold text-purple-600"><?php echo count($solicitudes); ?></p>
        </a>
    </div>
</div>

        
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Solicitudes de Novedad de Docentes (Semestre: <?php echo htmlspecialchars($anio_semestre); ?>)</h2>

            <?php if ($tipo_usuario == 2 || $tipo_usuario == 1): // Solo mostrar botones para Facultad y Admin ?>
            <form method="POST" action="">
                <input type="hidden" name="anio_semestre" value="<?php echo htmlspecialchars($anio_semestre); ?>">
                <?php if ($status_filter): ?>
                    <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
                <div class="flex space-x-4 mb-4">
                    <?php if ($tipo_usuario == 2): // Botones para Facultad ?>
    <button type="submit" name="action" value="aprobar_seleccionados" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition duration-300">
        Avalar Seleccionados
    </button>
    <button type="submit" name="action" value="rechazar_seleccionados" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md transition duration-300">
        No Avalar Seleccionados
    </button>
    <button type="submit" name="action" value="deshacer_aprobacion" class="btn-deshacer hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md transition duration-300">
        Deshacer Aval
    </button>
                    
    <?php

                    $oficio_button_enabled = false;

    ?>
                    
                    

                    <?php elseif ($tipo_usuario == 1): // Botones para Administrador ?>
                        <button type="submit" name="action" value="aprobar_admin" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                            Aprobar (Admin)
                        </button>
                        <button type="submit" name="action" value="rechazar_admin" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                            Inadmitir (Admin)
                        </button>
                        <button type="submit" name="action" value="deshacer_admin" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                            Deshacer (Admin)
                        </button>
                    <?php endif; ?>

                    <?php if ($status_filter): ?>
                        <a href="?anio_semestre=<?php echo htmlspecialchars($anio_semestre); ?>" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-md transition duration-300">
                            Mostrar Todas
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; // Fin del bloque de botones de acción ?>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 tabla-condensada">
                       
                       <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($solicitudes)): ?>
                            <?php
                            $current_facultad_id = null;
                            $current_depto_id = null;
                            $depto_count = 0; // Para el data-depto del checkbox

                            foreach ($solicitudes as $solicitud):
                                $is_editable_facultad = ($solicitud['estado_facultad'] === 'PENDIENTE');
                                // Para Admin, la "editabilidad" se basa en si VRA NO ha actuado
                                $is_editable_admin_vra = ($solicitud['estado_vra'] === 'PENDIENTE');

                                $disabled_class = '';
                                $disabled_attr = '';
                                $can_checkbox_select = false; // Inicializamos a false por seguridad
                                $checkbox_title = '';

                                // Lógica para el indicador VRA y clase de fila
                                $vra_estado = $solicitud['estado_vra'];
                                $facultad_estado = $solicitud['estado_facultad']; // Obtener el estado de facultad

                                $vra_indicator_text = '';
                                $vra_indicator_class = '';
                                $vra_actuado = false; // Variable para controlar si VRA ha actuado
                                $row_vra_status_class = ''; // Nueva variable para la clase de la fila

                                if ($vra_estado === 'APROBADO') {
                                    $vra_indicator_text = 'APROBADO';
                                    $vra_indicator_class = 'vra-indicator-approved';
                                    $vra_actuado = true;
                                    $row_vra_status_class = 'bg-green-50/50';
                                } elseif ($vra_estado === 'RECHAZADO') {
                                    $vra_indicator_text = 'INADMITIDO';
                                    $vra_indicator_class = 'vra-indicator-rejected';
                                    $vra_actuado = true;
                                    $row_vra_status_class = 'bg-red-50/50';
                                }
                                // NUEVA LÓGICA AQUÍ:
                                elseif ($facultad_estado === 'RECHAZADO' && $vra_estado === 'PENDIENTE') {
                                    $vra_indicator_text = 'N/A'; // O '--' si prefieres
                                    $vra_indicator_class = 'vra-indicator-pending'; // Puedes usar el mismo estilo gris o crear uno nuevo si quieres.
                                    $vra_actuado = false; // VRA no ha actuado, y no se espera que lo haga
                                }
                                elseif ($vra_estado === 'PENDIENTE') {
                                    $vra_indicator_text = 'PENDIENTE';
                                    $vra_indicator_class = 'vra-indicator-pending';
                                    $vra_actuado = false; // Explicitly set to false for pending
                                }


                                // Lógica de deshabilitado y selección de checkbox
                                if ($tipo_usuario == 2) { // Facultad
                                    $can_undo_facultad = (!$is_editable_facultad && // No es editable si ya está APROBADO/RECHAZADO por facultad
                                                         $solicitud['aprobador_facultad_id'] == $aprobador_id_logged_in &&
                                                         !$vra_actuado); // Y VRA no ha actuado

                                    $can_checkbox_select = $is_editable_facultad || $can_undo_facultad;
                                    if (!$can_checkbox_select) {
                                        $disabled_class = 'disabled-row';
                                        $disabled_attr = 'disabled';
                                        $checkbox_title = $vra_actuado ? 'No puede deshacer: VRA ya actuó' : 'Esta solicitud ya fue gestionada por Facultad.'; // Mensaje más descriptivo
                                    }

                                } elseif ($tipo_usuario == 1) { // Administrador
                                    // Un administrador SÍ PUEDE seleccionar para aprobar VRA si Facultad la APROBÓ
                                    // O puede seleccionar para deshacer VRA si él mismo (u otro admin) la APROBÓ/RECHAZÓ
                                    $can_select_to_approve_vra = ($facultad_estado === 'APROBADO' && $vra_estado === 'PENDIENTE');

                                    $can_undo_admin = (!$is_editable_admin_vra && // Si VRA ya no está PENDIENTE
                                                         $solicitud['aprobador_vra_id'] == $aprobador_id_logged_in); // y fue actuado por el admin logeado

                                    // El checkbox es seleccionable si:
                                    // 1. Facultad APROBÓ y VRA está PENDIENTE (para que Admin la pueda procesar)
                                    // 2. VRA ya actuó Y el admin logeado fue quien actuó (para permitir deshacer)
                                    $can_checkbox_select = $can_select_to_approve_vra || $can_undo_admin;

                                    if (!$can_checkbox_select) {
                                        $disabled_class = 'disabled-row';
                                        $disabled_attr = 'disabled';
                                        // Definir mensajes más específicos para el Administrador
                                        if ($facultad_estado === 'RECHAZADO') {
                                            $checkbox_title = 'Facultad rechazó esta solicitud. VRA no puede gestionarla.';
                                        } elseif ($facultad_estado === 'PENDIENTE') {
                                            $checkbox_title = 'La Facultad debe aprobar esta solicitud antes de que VRA pueda gestionarla.';
                                        } elseif ($vra_estado === 'APROBADO' && $solicitud['aprobador_vra_id'] != $aprobador_id_logged_in) {
                                            $checkbox_title = 'Esta solicitud ya fue aprobada por VRA y no por usted.';
                                        } elseif ($vra_estado === 'RECHAZADO' && $solicitud['aprobador_vra_id'] != $aprobador_id_logged_in) {
                                            $checkbox_title = 'Esta solicitud ya fue rechazada por VRA y no por usted.';
                                        } else {
                                            $checkbox_title = 'Esta solicitud no puede ser seleccionada para acción de VRA.';
                                        }
                                    }

                                    // La observación para el admin siempre es editable si la solicitud no ha sido actuada por VRA.
                                    // O si el admin quiere modificar su propia observación
                                    // Esto controla el textarea de observación, no el checkbox
                                    // if (!$is_editable_admin_vra && $solicitud['aprobador_vra_id'] != $aprobador_id_logged_in) {
                                    //     $disabled_attr = 'disabled';
                                    //     $disabled_class = 'disabled-row'; // Esto también deshabilitaría la fila visualmente. Cuidado si no quieres esto.
                                    // }
                                }


                                // Encabezado de Facultad
                                if ($current_facultad_id !== $solicitud['facultad_id']) {
                                    $current_facultad_id = $solicitud['facultad_id'];
                                    echo '<tr><td colspan="12" class="px-2 py-1 bg-gray-200 text-gray-800 font-bold text-base rounded-md shadow-sm mt-4">Facultad: ' . htmlspecialchars($solicitud['nombre_facultad']) . '</td></tr>';
                                    // Resetear current_depto_id para que el encabezado de depto siempre aparezca al inicio de cada facultad
                                    $current_depto_id = null;
                                }

                                // Encabezado de Departamento
                                if ($current_depto_id !== $solicitud['departamento_id']) {
                                    $current_depto_id = $solicitud['departamento_id'];
                                    $depto_count++;

                                    echo '<tr><td colspan="12" class="px-2 py-1 bg-blue-100 text-blue-800 font-semibold text-sm rounded-md shadow-sm">Departamento: ' . htmlspecialchars($solicitud['nombre_departamento']) . '</td></tr>';

                                    // Encabezado de la tabla para cada departamento
                                    echo '<tr class="bg-gray-50">';
                                    if ($tipo_usuario == 2 || $tipo_usuario == 1) { // Checkbox solo para Facultad y Admin
                                        echo '<th class="w-8 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">';
                                        echo '<input type="checkbox" class="select-depto form-checkbox h-3 w-3 text-blue-600 rounded" data-depto="'.$depto_count.'">';
                                        echo '</th>';
                                    } else {
                                       // echo '<th class="w-8 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">Estado Proceso</th>'; // Celda vacía para Departamento
                                    }
                                    echo '<th class="w-24 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Cédula</th>';
                                    echo '<th class="w-40 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Nombre</th>';
                                    echo '<th class="w-20 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Novedad</th>';
                                    echo '<th class="w-24 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Tipo</th>';
                                    echo '<th class="w-48 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider celda-ajustable">Justificación</th>';
                                    echo '<th class="w-20 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Popayán</th>';
                                    echo '<th class="w-24 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Regional</th>';
                                    echo '<th class="w-24 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Estado Facultad</th>';
                                    echo '<th class="w-24 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider">Estado VRA</th>';
                                    echo '<th class="w-48 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider celda-ajustable">Obs. Facultad/VRA</th>';
                                    //echo '<th class="w-16 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">Acciones</th>';
                                    
                                       if ($tipo_usuario == 1) { // Checkbox solo para Facultad y Admin
                                    echo '<th class="w-48 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider celda-ajustable">Tipo Obs</th>';
                                        echo '</th>';
                                    } else {
                                       // echo '<th class="w-8 px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">Estado Proceso</th>'; // Celda vacía para Departamento
                                    }
                                    echo '</tr>';
                                }
                            ?>
                            <tr class="hover:bg-gray-50 <?php echo $disabled_class; ?> <?php echo $row_vra_status_class; ?>">
                                <?php if ($tipo_usuario == 2 || $tipo_usuario == 1): // Checkbox para Facultad y Admin ?>
                                    <td class="px-2 py-1 whitespace-nowrap">
                                        <input type="checkbox" name="selected_solicitudes[]"
                                               value="<?php echo htmlspecialchars($solicitud['id_solicitud']); ?>"
                                               class="form-checkbox h-3 w-3 text-blue-600 rounded solicitud-checkbox <?php echo $can_checkbox_select ? '' : 'cursor-not-allowed opacity-50'; ?>"
                                               data-depto="<?php echo $depto_count; ?>"
                                               <?php if (!$can_checkbox_select) echo 'disabled'; ?>
                                               title="<?php echo $checkbox_title; ?>">
                                    </td>
                                <?php else: // Para tipo_usuario == 3 (Departamento), mostrar barra de progreso aquí ?>
                                  
                                <?php endif; ?>
                                <td class="px-2 py-1"><?php echo htmlspecialchars($solicitud['cedula']); ?></td>
                                <td class="px-2 py-1 celda-ajustable relative group">
                                    <?php
                                    $nombre = htmlspecialchars($solicitud['nombre']);
                                    $nombreMostrar = strlen($nombre) > 16 ? substr($nombre, 0, 16) . '...' : $nombre;
                                    echo $nombreMostrar;
                                    ?>
                                    <?php if(strlen($nombre) > 16): ?>
                                        <span class="absolute z-10 invisible group-hover:visible bg-gray-800 text-white text-xs rounded py-1 px-2 bottom-full left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                                            <?php echo $nombre; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-2 py-1">
                                    <span class="status-tag <?php
                                        if ($solicitud['novedad'] === 'Adicion') echo 'novedad-adicion';
                                        elseif ($solicitud['novedad'] === 'Modificacion') echo 'novedad-modificacion';
                                        elseif ($solicitud['novedad'] === 'Eliminacion') echo 'novedad-eliminacion';
                                    ?>">
                                        <?php echo substr(htmlspecialchars($solicitud['novedad']), 0, 9); ?>
                                    </span>
                                </td>
                                <td class="px-2 py-1"><?php echo htmlspecialchars($solicitud['tipo_docente']); ?></td>
                            <td class="px-2 py-1 celda-ajustable" 
                                title="<?php echo htmlspecialchars($solicitud['s_observacion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php
                                $obsDepto = htmlspecialchars($solicitud['s_observacion'] ?? '', ENT_QUOTES, 'UTF-8');
                                echo mb_strlen($obsDepto, 'UTF-8') > 25 
                                    ? mb_substr($obsDepto, 0, 25, 'UTF-8') . '...' 
                                    : $obsDepto;
                                ?>
                            </td>
                                <td class="px-2 py-1">
                                    <?php if ($solicitud['tipo_docente'] === 'Ocasional'): ?>
                                        <?php echo htmlspecialchars($solicitud['tipo_dedicacion'] ?: '-'); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($solicitud['horas'] ?: '-'); ?>h
                                    <?php endif; ?>
                                </td>
                                <td class="px-2 py-1">
                                    <?php if ($solicitud['tipo_docente'] === 'Ocasional'): ?>
                                        <?php echo htmlspecialchars($solicitud['tipo_dedicacion_r'] ?: '-'); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($solicitud['horas_r'] ?: '-'); ?>h
                                    <?php endif; ?>
                                </td>
                            <td class="px-2 py-1">
                            <?php
                            // Mapeo de estados para mostrar al usuario
                            $estados_mostrar = [
                                'APROBADO'  => 'AVALADO',
                                'RECHAZADO' => 'NO AVALADO',
                                'PENDIENTE' => 'PENDIENTE'
                            ];

                            $estado_original = $solicitud['estado_facultad'];
                            $estado_visible = $estados_mostrar[$estado_original] ?? $estado_original;
                            ?>
                            <span class="status-tag status-facultad <?php
                                if ($estado_original === 'PENDIENTE') echo 'status-pendiente';
                                elseif ($estado_original === 'APROBADO') echo 'status-aprobado';
                                elseif ($estado_original === 'RECHAZADO') echo 'status-rechazado';
                            ?>"
                            title="<?php echo htmlspecialchars($solicitud['observacion_facultad'] ?? 'Sin observación.'); ?>"
                            data-observacion="<?php echo htmlspecialchars($solicitud['observacion_facultad'] ?? 'Sin observación.'); ?>">
                                <?php echo htmlspecialchars($estado_visible); ?>
                            </span>
                        </td>

                                <td class="px-2 py-1">
                                    <span class="status-tag status-vra <?php echo $vra_indicator_class; ?>"
                                          title="<?php echo htmlspecialchars($solicitud['observacion_vra'] ?? 'Sin observación.'); ?>"
                                          data-observacion="<?php echo htmlspecialchars($solicitud['observacion_vra'] ?? 'Sin observación.'); ?>">
                                        <?php echo htmlspecialchars($vra_indicator_text); ?>
                                    </span>
                                </td>
                               <td class="px-2 py-1 celda-ajustable">
    <?php
    $display_obs_facultad = htmlspecialchars($solicitud['observacion_facultad'] ?: '');
    $display_obs_vra = htmlspecialchars($solicitud['observacion_vra'] ?: '');

    $current_obs_val = '';
    $is_obs_disabled = false; // Mantener tu lógica existente de deshabilitado

    // Determina qué observación mostrar y si está deshabilitada por tu lógica actual
    if ($tipo_usuario == 2) { // Facultad
        $current_obs_val = $display_obs_facultad;
        $is_obs_disabled = !$is_editable_facultad;
    } elseif ($tipo_usuario == 1) { // Administrador (VRA)
        $current_obs_val = $display_obs_vra;
        $is_obs_disabled = !$is_editable_admin_vra;
    } else { // Departamento - solo para visualizar, no editar
        if (!empty($display_obs_facultad)) {
            $current_obs_val .= "Facultad: $display_obs_facultad";
        }
        if (!empty($display_obs_vra)) {
            if (!empty($current_obs_val)) {
                $current_obs_val .= "\n";
            }
            $current_obs_val .= "VRA: $display_obs_vra";
        }
        $is_obs_disabled = true; // Siempre deshabilitado para departamento
    }
    ?>
    <?php if ($tipo_usuario == 2 || $tipo_usuario == 1): ?>
        <textarea
    id="obs_<?php echo ($tipo_usuario == 2 ? 'facultad' : 'vra'); ?>_<?php echo htmlspecialchars($solicitud['id_solicitud']); ?>"
    name="observacion_<?php echo ($tipo_usuario == 2 ? 'facultad' : 'vra'); ?>_<?php echo htmlspecialchars($solicitud['id_solicitud']); ?>"
    class="observacion w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
    data-solicitud-id="<?php echo htmlspecialchars($solicitud['id_solicitud']); ?>"
    data-user-type="<?php echo htmlspecialchars($tipo_usuario); ?>"
    data-status-field="<?php echo ($tipo_usuario == 2 ? 'facultad' : 'vra'); ?>"
    <?php echo $is_obs_disabled ? 'disabled' : ''; ?>
    <?php echo !$is_obs_disabled ? 'required' : ''; ?> 
    placeholder="<?php echo !$is_obs_disabled ? 'Observación obligatoria' : ''; ?>"
><?php echo $current_obs_val; ?></textarea>
    <?php else: ?>
        <div class="border rounded-md p-1 bg-gray-50 text-gray-700 h-8 overflow-hidden text-sm celda-ajustable"
             title="<?php echo htmlspecialchars($current_obs_val); ?>">
            <?php echo htmlspecialchars(strlen($current_obs_val) > 25 ? substr($current_obs_val, 0, 25) . '...' : ($current_obs_val ?: 'N/A')); ?>
        </div>
    <?php endif; ?>
</td>
                            
                               <?php if ($tipo_usuario == 1): // Para el tipo de usuario 1 (Admin/VRA) ?>
    <td class="px-2 py-1 celda-ajustable" data-id-solicitud="<?= htmlspecialchars($solicitud['id_solicitud'] ?? '') ?>">
        <span class="display-tipo-reemplazo" style="display: none;"> <?php
            // Obtener el valor de tipo_reemplazo de forma segura, usando un string vacío si es null
            $reemplazo_val_display = htmlspecialchars($solicitud['tipo_reemplazo'] ?? '', ENT_QUOTES, 'UTF-8');
            
            // Mostrar el valor truncado si es más largo de 25 caracteres, o el valor completo
            echo mb_strlen($reemplazo_val_display, 'UTF-8') > 25
                 ? mb_substr($reemplazo_val_display, 0, 25, 'UTF-8') . '...'
                 : $reemplazo_val_display;
            ?>
        </span>

        <select class="form-control edit-tipo-reemplazo
                       block w-full px-2 py-1
                       text-base font-normal text-gray-700 bg-white bg-clip-padding
                       border border-solid border-gray-300 rounded
                       transition ease-in-out
                       m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
                name="tipo_reemplazo_<?= htmlspecialchars($solicitud['id_solicitud'] ?? '') ?>"
                id="tipo_reemplazo_<?= htmlspecialchars($solicitud['id_solicitud'] ?? '') ?>">
            
            <option value="">-- Seleccione una opción --</option>
            <?php 
            // Asegúrate de que 'novedad' y 'tipo_reemplazo' estén disponibles en $solicitud
            $current_novedad = $solicitud['novedad'] ?? ''; 
            $current_tipo_reemplazo = $solicitud['tipo_reemplazo'] ?? ''; 

            if ($current_novedad == 'adicionar' || $current_novedad == 'Modificar'): 
            ?>
                <option value="Ajuste de Matrículas" <?= ($current_tipo_reemplazo == 'Ajuste de Matrículas') ? 'selected' : '' ?>>Ajuste de Matrículas</option>
                <option value="No legalizó" <?= ($current_tipo_reemplazo == 'No legalizó') ? 'selected' : '' ?>>No legalizó</option>
                <option value="Otras fuentes de financiacion" <?= ($current_tipo_reemplazo == 'Otras fuentes de financiacion') ? 'selected' : '' ?>>Otras fuentes de financiación</option>
                <option value="Reemplazo" <?= ($current_tipo_reemplazo == 'Reemplazo') ? 'selected' : '' ?>>Reemplazo</option>
                <option value="Reemplazo jubilación" <?= ($current_tipo_reemplazo == 'Reemplazo jubilación') ? 'selected' : '' ?>>Reemplazo jubilación</option>
                <option value="Reemplazo necesidad docente" <?= ($current_tipo_reemplazo == 'Reemplazo necesidad docente') ? 'selected' : '' ?>>Reemplazo necesidad docente</option>
                <option value="Reemplazo por Fallecimiento" <?= ($current_tipo_reemplazo == 'Reemplazo por Fallecimiento') ? 'selected' : '' ?>>Reemplazo por Fallecimiento</option>
                <option value="Reemplazo por Licencia" <?= ($current_tipo_reemplazo == 'Reemplazo por Licencia') ? 'selected' : '' ?>>Reemplazo por Licencia</option>
                <option value="Reemplazo renuncia" <?= ($current_tipo_reemplazo == 'Reemplazo renuncia') ? 'selected' : '' ?>>Reemplazo renuncia</option>
                <option value="Reemplazos NN" <?= ($current_tipo_reemplazo == 'Reemplazos NN') ? 'selected' : '' ?>>Reemplazos NN</option>
                <option value="Ajuste Puntos" <?= ($current_tipo_reemplazo == 'Ajuste Puntos') ? 'selected' : '' ?>>Ajuste Puntos</option>
                <option value="Ajuste por VRA" <?= ($current_tipo_reemplazo == 'Ajuste por VRA') ? 'selected' : '' ?>>Ajuste por VRA</option>
                <option value="Otro" <?= ($current_tipo_reemplazo == 'Otro') ? 'selected' : '' ?>>Otro</option>
            <?php elseif ($current_novedad == 'Eliminar'): ?>
                <option value="Fallecimiento" <?= ($current_tipo_reemplazo == 'Fallecimiento') ? 'selected' : '' ?>>Fallecimiento</option>
                <option value="Renuncia" <?= ($current_tipo_reemplazo == 'Renuncia') ? 'selected' : '' ?>>Renuncia</option>
                <option value="Ajuste de Matrículas" <?= ($current_tipo_reemplazo == 'Ajuste de Matrículas') ? 'selected' : '' ?>>Ajuste de Matrículas</option>
                <option value="No legalizó" <?= ($current_tipo_reemplazo == 'No legalizó') ? 'selected' : '' ?>>No legalizó</option>
                <option value="Reemplazos NN" <?= ($current_tipo_reemplazo == 'Reemplazos NN') ? 'selected' : '' ?>>Reemplazos NN</option>
                <option value="Otro" <?= ($current_tipo_reemplazo == 'Otro') ? 'selected' : '' ?>>Otro</option>
            <?php else: ?>
                <option value="">No hay opciones disponibles</option>
            <?php endif; ?>
        </select>
    </td>

<?php else: // Para tipo_usuario == 3 (Departamento), mostrar barra de progreso aquí ?>
  
<?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="px-4 py-2 text-center text-gray-500">No hay solicitudes para el filtro actual.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            <?php if ($tipo_usuario == 2 || $tipo_usuario == 1): // Cerrar el formulario solo si los botones se mostraron ?>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Función para manejar la selección por departamento
        function setupDeptoSelectors() {
            const deptoCheckboxes = document.querySelectorAll('.select-depto');

            deptoCheckboxes.forEach(deptoCheckbox => {
                deptoCheckbox.addEventListener('change', function() {
                    const deptoId = this.getAttribute('data-depto');
                    // Solo seleccionar checkboxes que no estén deshabilitados
                    const solicitudes = document.querySelectorAll(`.solicitud-checkbox[data-depto="${deptoId}"]:not([disabled])`);

                    solicitudes.forEach(solicitud => {
                        solicitud.checked = this.checked;
                    });
                });
            });
        }

        document.addEventListener('DOMContentLoaded', setupDeptoSelectors);
    </script>
 <!-- === CSS: poner en <head> o arriba del modal (si usas Tailwind no importa) === -->
<style>
  /* overlay modal */
  #modalOficio {
    display: none;              /* hidden por defecto */
    position: fixed;
    inset: 0;                   /* top:0; right:0; bottom:0; left:0 */
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  /* mostrar: usaremos inline style .show para controlarlo */
  #modalOficio.show { display: flex; }

  /* contenido */
  #modalOficio .modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 720px;
    width: 100%;
    box-shadow: 0 8px 30px rgba(0,0,0,0.25);
    padding: 1.25rem;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
  }

  /* botón cerrar simple (si quieres un X) */
  #modalOficio .modal-close {
    position: absolute;
    top: 8px;
    right: 8px;
    background: transparent;
    border: 0;
    font-size: 1.2rem;
    cursor: pointer;
  }
</style>

<!-- === Tu modal (ya lo tienes, aquí por completitud) === 
<div id="modalOficio" class="modal hidden" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-content">
    <button class="modal-close" aria-label="Cerrar" data-modal-close>&times;</button>

    <h3 class="text-xl font-bold mb-4">Generar Oficio de Novedades Aprobadas</h3>
    <form id="oficioForm">
      <div class="form-group mb-3">
        <label for="numeroOficio">Número de Oficio</label>
                        <input type="text" class="form-control" id="numeroOficio" name="numero_oficio" value="<?php //echo obtenerTRDFacultad($id_facultad); ?>" required>
      </div>

     <div class="form-group mb-3">
          <label for="fechaOficio">Fecha de Oficio</label>
          <input type="date" id="fechaOficio" name="fecha_oficio" value="<?php //echo date('Y-m-d'); ?>" required>
        </div>

      <div class="form-group mb-3">
        <label for="decano">Decano</label>
       <input type="text" class="form-control" id="decano" name="decano" value="<?php //echo htmlspecialchars($decano); ?>" required>
      </div>

      <div class="form-group mb-3">
        <label for="elaboradoPor">Elaborado por</label>
        <input type="text" id="elaboradoPor" name="elaborado_por" required>
      </div>

      <div class="form-group mb-3">
        <label for="folios">Número de Folios</label>
        <input type="number" id="folios" name="folios" min="1" value="1" required>
      </div>

      <input type="hidden" name="anio_semestre" id="modalAnioSemestre" value="<?php //echo $anio_semestre; ?>">
      <input type="hidden" name="id_facultad" id="modalIdFacultad" value="<?php// echo $id_facultad; ?>">

      <div class="flex justify-end space-x-4 mt-6">
        <button type="button" data-modal-close class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md">
          Cancelar
        </button>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">
          Generar Oficio
        </button>
      </div>
    </form>
  </div>
</div>
-->

<!-- === JavaScript: pegar justo antes de </body> === -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const btnOpen = document.getElementById('btnGenerarOficio');
  const modal = document.getElementById('modalOficio');
  const formOficio = document.getElementById('oficioForm');

  function openModal() {
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }

    if (btnOpen) {
    // Solo añade el evento si el botón NO está deshabilitado
    if (!btnOpen.disabled) {
      btnOpen.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
      });
    } else {
      // Opcional: Para deshabilitar visualmente el cursor en JS si no lo cubre el CSS
      btnOpen.style.cursor = 'not-allowed';
    }
  }

  modal.querySelectorAll('[data-modal-close]').forEach(function(btn){
    btn.addEventListener('click', closeModal);
  });

  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  if (formOficio) {
    formOficio.addEventListener('submit', function (e) {
      e.preventDefault();
      const fd = new FormData(formOficio);

      fetch('generar_word_solicitudes_seleccion.php', {
        method: 'POST',
        body: fd
      })
      .then(response => {
        if (!response.ok) throw new Error('Error en servidor');
        return response.blob();
      })
      .then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'Oficio_Novedades.docx';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
        closeModal();
      })
      .catch(err => {
        console.error(err);
        alert('Error al generar el oficio.');
      });
    });
  }
});
</script>
<div id="wordGenModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-96">
        <h2 class="text-xl font-bold mb-4">Generar oficio facultad</h2>
        <form id="wordGenForm" action="generar_word_solicitudes_seleccion.php" method="POST" target="_blank">
            <input type="hidden" id="wordGenSelectedIds" name="selected_ids_for_word" value="">

            <div class="mb-4">
                <label for="oficio" class="block text-gray-700 text-sm font-bold mb-2">Oficio:</label>
                <input type="text" id="oficio" name="oficio" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo obtenerTRDFacultad($id_facultad); ?>" required>
            </div>
                        <div class="mb-4">
                <label for="fecha_oficio" class="block text-gray-700 text-sm font-bold mb-2">
                    Fecha Oficio:
                </label>
                <input type="date" id="fecha_oficio" name="fecha_oficio"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    required>
            </div>
            <div class="mb-4">
                <label for="decano" class="block text-gray-700 text-sm font-bold mb-2">Decano:</label>
                <input type="text" id="decano" name="decano" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($decano); ?>" required>
            </div>
            <div class="mb-4">
                <label for="elaboro" class="block text-gray-700 text-sm font-bold mb-2">Elaboró:</label>
                <input type="text" id="elaboro" name="elaboro" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-6">
                <label for="folios" class="block text-gray-700 text-sm font-bold mb-2">Folios:</label>
                <input type="number" id="folios" name="folios" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" min="1" required>
            </div>
            <input type="hidden" id="wordGenAnioSemestre" name="anio_semestre" value="">
<input type="hidden" id="wordGenIdFacultad" name="id_facultad" value="">
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Generar Word
                </button>
                <button type="button" onclick="document.getElementById('wordGenModal').classList.add('hidden');" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
    <script>// Antes de enviar
console.log("IDs seleccionados:", idsSeleccionados);
console.log("Cantidad de IDs:", idsSeleccionados.length);
</script>
</body>
</html>