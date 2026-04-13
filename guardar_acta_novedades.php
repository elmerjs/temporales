<?php
require 'conn.php'; 
require 'funciones.php';

function obtenerIdFacultadLocal($departamento_id, $conn) {
    $sql = "SELECT FK_FAC FROM deparmanentos WHERE PK_DEPTO = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['FK_FAC'];
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // =============================================
    // 1. VALIDACIÓN OBLIGATORIA: Número de Acta (debe ir PRIMERO)
    // =============================================
    $numero_acta = trim($_POST['numero_acta'] ?? '');
    if (empty($numero_acta)) {
        $isAjax = !empty($_POST['ajax']) && $_POST['ajax'] == 1;
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'El campo "Serie, Subserie / No de acta" es obligatorio.'
            ]);
            exit;
        } else {
            $departamento_id = $_POST['departamento_id'] ?? '';
            $anio_semestre   = $_POST['anio_semestre'] ?? '';
            $error_msg = urlencode('⚠️ El número de acta es obligatorio. Por favor, complételo antes de guardar.');
            header("Location: gestion_acta_novedades.php?departamento_id=$departamento_id&anio_semestre=$anio_semestre&error=$error_msg");
            exit;
        }
    }

    // 2. Resto de datos básicos (después de la validación)
    $id_acta         = $_POST['id_acta'] ?? '';
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre   = $_POST['anio_semestre'];
    $lugar_reunion   = $_POST['lugar_reunion'] ?? '';
    $fecha_reunion   = $_POST['fecha_reunion'] ?? '';
    // VALIDACIÓN: Si no llega acción, asumimos borrador
    $accion = $_POST['accion'] ?? 'borrador'; 

    // VALIDACIÓN DE SEGURIDAD (SOLO PARA FINALIZAR)
    // Evita que finalicen un acta sin candidatos
    if ($accion == 'finalizar') {
        $sql_validacion = "SELECT COUNT(*) as total 
                           FROM solicitudes_working_copy s1 
                           WHERE s1.departamento_id = ? 
                           AND s1.anio_semestre = ? 
                           AND s1.novedad = 'adicionar'
                           AND s1.estado_depto = 'PENDIENTE'
                           AND (s1.archivado = 0 OR s1.archivado IS NULL)
                           AND NOT EXISTS (
                               SELECT 1 FROM solicitudes_working_copy s2 
                               WHERE s2.cedula = s1.cedula 
                               AND s2.departamento_id = s1.departamento_id 
                               AND s2.anio_semestre = s1.anio_semestre 
                               AND s2.novedad = 'Eliminar' 
                               AND s2.estado_depto = 'PENDIENTE'
                           )";
        
        $stmt_val = $conn->prepare($sql_validacion);
        $stmt_val->bind_param("ss", $departamento_id, $anio_semestre);
        $stmt_val->execute();
        $conteo = $stmt_val->get_result()->fetch_assoc()['total'];
        $stmt_val->close();
        
        if ($conteo == 0) {
            echo "<script>
                alert('⛔ ERROR: No es posible FINALIZAR el acta porque no hay candidatos nuevos para vincular.');
                window.history.back(); 
            </script>";
            exit; 
        }
    }

    // Miembros
    $miembro_1_nombre = $_POST['m1_nom'] ?? '';
    $miembro_2_nombre = $_POST['m2_nom'] ?? '';
    $miembro_3_nombre = $_POST['m3_nom'] ?? '';
    $miembro_4_nombre = $_POST['m4_nom'] ?? '';
    $miembro_5_nombre = $_POST['m5_nom'] ?? '';

    // --- PUNTO 3: PROCESAR PERFILES ---
    $perfiles_array = [];
    // Prioridad a la nueva estructura perf_perfil[]
    if (isset($_POST['perf_perfil'])) {
        $count = count($_POST['perf_perfil']);
        for ($i = 0; $i < $count; $i++) {
            if (trim($_POST['perf_perfil'][$i]) !== '') {
                $perfiles_array[] = [
                    'item'          => $i + 1,
                    'perfil'        => $_POST['perf_perfil'][$i],
                    'nivel'         => $_POST['perf_nivel'][$i] ?? '',
                    'experiencia'   => $_POST['perf_experiencia'][$i] ?? '',
                    'productividad' => $_POST['perf_productividad'][$i] ?? ''
                ];
            }
        }
    } 
    // Compatibilidad hacia atrás (por si acaso)
    elseif (isset($_POST['perf_nombre'])) {
        $count = count($_POST['perf_nombre']);
        for ($i = 0; $i < $count; $i++) {
            if (trim($_POST['perf_nombre'][$i]) !== '') {
                $perfiles_array[] = [
                    'item'          => $i + 1,
                    'perfil'        => $_POST['perf_nombre'][$i],
                    'nivel'         => $_POST['perf_nivel'][$i] ?? '',
                    'experiencia'   => $_POST['perf_experiencia'][$i] ?? '',
                    'productividad' => $_POST['perf_formacion'][$i] ?? ''
                ];
            }
        }
    }
    
    $perfiles_json = json_encode($perfiles_array, JSON_UNESCAPED_UNICODE);
    $punto_3_perfiles = $_POST['punto_3_perfiles'] ?? '';

    // Otros puntos
    $punto_4_aspirantes = $_POST['punto_4_aspirantes'] ?? '';
    $punto_5_calificacion = $_POST['punto_5_calificacion'] ?? '';
    $punto_6_entrevista = $_POST['punto_6_entrevista'] ?? '';

    // Compromisos
   $compromisos_array = [];
if (isset($_POST['comp_desc'])) {
    $count = count($_POST['comp_desc']);
    for ($i = 0; $i < $count; $i++) {
        if (trim($_POST['comp_desc'][$i]) !== '') { // Solo guardar si hay descripción
            $compromisos_array[] = [
                'desc'                => $_POST['comp_desc'][$i],
                'resp'                => $_POST['comp_resp'][$i] ?? '',
                'fecha_compromiso'    => $_POST['comp_fecha_compromiso'][$i] ?? '',
                'fecha_realizacion'   => $_POST['comp_fecha_realizacion'][$i] ?? ''
            ];
        }
    }
}
$compromisos_json = json_encode($compromisos_array, JSON_UNESCAPED_UNICODE);
    $observaciones = $_POST['observaciones'] ?? '';

    // Estado: Si es 'finalizar', cambia estado. Si es 'borrador' o 'salir', sigue en borrador.
  // Estado: Si es 'finalizar', cambia estado. Si es 'borrador' o 'salir', sigue en borrador.
$estado_acta = ($accion == 'finalizar') ? 'finalizado' : 'borrador';

// 2. GUARDAR EN BD (INSERT O UPDATE)
if ($id_acta) {
    // UPDATE
    $sql = "UPDATE actas_seleccion_novedades SET 
            lugar_reunion=?, fecha_reunion=?, numero_acta=?, 
            miembro_1_nombre=?, miembro_2_nombre=?, miembro_3_nombre=?, miembro_4_nombre=?, miembro_5_nombre=?, 
            perfiles_json=?, punto_3_perfiles=?, punto_4_aspirantes=?, punto_5_calificacion=?, punto_6_entrevista=?, 
            compromisos_json=?, observaciones=?, estado_acta=? 
            WHERE id_acta=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssi", 
        $lugar_reunion, $fecha_reunion, $numero_acta,
        $miembro_1_nombre, $miembro_2_nombre, $miembro_3_nombre, $miembro_4_nombre, $miembro_5_nombre,
        $perfiles_json, $punto_3_perfiles, $punto_4_aspirantes, $punto_5_calificacion, $punto_6_entrevista,
        $compromisos_json, $observaciones, $estado_acta, 
        $id_acta
    );
} else {
    // INSERT
    $sql = "INSERT INTO actas_seleccion_novedades 
            (departamento_id, anio_semestre, lugar_reunion, fecha_reunion, numero_acta, 
             miembro_1_nombre, miembro_2_nombre, miembro_3_nombre, miembro_4_nombre, miembro_5_nombre, 
             perfiles_json, punto_3_perfiles, punto_4_aspirantes, punto_5_calificacion, punto_6_entrevista, 
             compromisos_json, observaciones, estado_acta) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssss", 
        $departamento_id, $anio_semestre, $lugar_reunion, $fecha_reunion, $numero_acta,
        $miembro_1_nombre, $miembro_2_nombre, $miembro_3_nombre, $miembro_4_nombre, $miembro_5_nombre,
        $perfiles_json, $punto_3_perfiles, $punto_4_aspirantes, $punto_5_calificacion, $punto_6_entrevista,
        $compromisos_json, $observaciones, $estado_acta
    );
}
    if ($stmt->execute()) {
        $facultad_id = obtenerIdFacultadLocal($departamento_id, $conn);
        $nuevo_id = !empty($id_acta) ? $id_acta : $stmt->insert_id;

        // --- PETICIÓN AJAX (guardado silencioso con respuesta JSON) ---
        if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id_acta' => $nuevo_id,
                'accion'  => $accion
            ]);
            exit;
        }

        // --- LÓGICA DE REDIRECCIÓN NORMAL (para formulario tradicional) ---
        if ($accion == 'finalizar' || $accion == 'salir') {
            $mensaje = ($accion == 'finalizar') 
                ? '✅ Acta de Novedades FINALIZADA correctamente.' 
                : '💾 Cambios guardados. Regresando al panel...';
            echo "
            <form id='returnForm' action='consulta_todo_depto_novedad.php' method='POST'>
                <input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>
                <input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>
                <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
            </form>
            <script>
                alert('$mensaje');
                document.getElementById('returnForm').submit();
            </script>";
        } else {
            echo "<script>
                alert('💾 Borrador guardado. Puede continuar editando.');
                window.location.href = 'gestion_acta_novedades.php?departamento_id=".urlencode($departamento_id)."&anio_semestre=".urlencode($anio_semestre)."&id_acta=".$nuevo_id."';
            </script>";
        }
    } else {
        echo "Error al guardar: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
}

?>