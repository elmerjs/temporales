<?php
require 'conn.php'; 
require 'funciones.php';

// Funciones de apoyo para la redirección
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
    // 1. Datos básicos
    $departamento_id = $_POST['departamento_id'];
    $anio_semestre   = $_POST['anio_semestre'];
    $lugar_reunion   = $_POST['lugar_reunion'];
    $fecha_reunion   = $_POST['fecha_reunion'];
    $numero_acta     = $_POST['numero_acta'];
    $accion          = $_POST['accion']; // Capturamos qué botón presionó

    // =================================================================================
    // 🛑 VALIDACIÓN DE SEGURIDAD (SOLO PARA FINALIZAR)
    // =================================================================================
    if ($accion == 'finalizar') {
        $sql_validacion = "SELECT COUNT(*) as total 
                           FROM solicitudes 
                           WHERE departamento_id = ? 
                           AND anio_semestre = ? 
                           AND (estado <> 'an' OR estado IS NULL)";
        
        $stmt_val = $conn->prepare($sql_validacion);
        $stmt_val->bind_param("ss", $departamento_id, $anio_semestre);
        $stmt_val->execute();
        $conteo = $stmt_val->get_result()->fetch_assoc()['total'];
        
        if ($conteo == 0) {
            echo "<script>
                alert('⛔ ERROR: No es posible FINALIZAR el acta porque el Punto 7 está vacío.\\n\\nEl sistema no detecta profesores cargados.\\nPor favor, haga clic en \"Ir a Cargar Docentes\" antes de finalizar.');
                window.history.back(); 
            </script>";
            exit; 
        }
    }
    // =================================================================================

    // 2. Miembros del Comité
    $m1_nom = $_POST['m1_nom'];
    $m2_nom = $_POST['m2_nom'];
    $m3_nom = $_POST['m3_nom'];
    $m4_nom = $_POST['m4_nom'];
    $m5_nom = $_POST['m5_nom'];

    // 3. Procesar Tabla de Perfiles
    // 3. Procesar Tabla de Perfiles (nueva estructura)
$perfiles_arr = [];
if (isset($_POST['perf_perfil'])) {
    $count = count($_POST['perf_perfil']);
    for ($i = 0; $i < $count; $i++) {
        if (trim($_POST['perf_perfil'][$i]) !== '') {
            $perfiles_arr[] = [
                'id_perfil'     => $_POST['perf_id'][$i] ?? ('Perfil ' . ($i+1)),
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
            $perfiles_arr[] = [
                'id_perfil'     => 'Perfil ' . ($i+1),
                'perfil'        => $_POST['perf_nombre'][$i],
                'nivel'         => $_POST['perf_nivel'][$i] ?? '',
                'experiencia'   => $_POST['perf_experiencia'][$i] ?? '',
                'productividad' => $_POST['perf_formacion'][$i] ?? ''
            ];
        }
    }
}
$perfiles_json = json_encode($perfiles_arr, JSON_UNESCAPED_UNICODE);

    // 4. Puntos de desarrollo
    $punto_3 = $_POST['punto_3_perfiles'];
    $punto_4 = $_POST['punto_4_aspirantes'];
    $punto_5 = $_POST['punto_5_calificacion'];
    $punto_6 = $_POST['punto_6_entrevista'] ?? 'No aplica';

    // 5. Procesar Compromisos
    // 5. Procesar Compromisos (con dos fechas)
$compromisos_arr = [];
if (isset($_POST['comp_desc'])) {
    $count = count($_POST['comp_desc']);
    for ($i = 0; $i < $count; $i++) {
        if (!empty(trim($_POST['comp_desc'][$i]))) {
            $compromisos_arr[] = [
                'desc'                => $_POST['comp_desc'][$i],
                'resp'                => $_POST['comp_resp'][$i] ?? '',
                'fecha_compromiso'    => $_POST['comp_fecha_compromiso'][$i] ?? '',
                'fecha_realizacion'   => $_POST['comp_fecha_realizacion'][$i] ?? ''
            ];
        }
    }
}
$compromisos_json = json_encode($compromisos_arr, JSON_UNESCAPED_UNICODE);
$observaciones = $_POST['observaciones'] ?? '';
    

    // 6. Determinar Estado
    $estado = ($accion == 'finalizar') ? 'finalizado' : 'borrador';

    // 7. Consulta UPSERT
    $sql = "INSERT INTO actas_seleccion_docente (
            departamento_id, anio_semestre, lugar_reunion, fecha_reunion, numero_acta,
            miembro_1_nombre, miembro_2_nombre, miembro_3_nombre, miembro_4_nombre, miembro_5_nombre,
            punto_3_perfiles, perfiles_json, punto_4_aspirantes, punto_5_calificacion, 
            punto_6_entrevista, compromisos_json, observaciones, estado_acta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            lugar_reunion = VALUES(lugar_reunion),
            fecha_reunion = VALUES(fecha_reunion),
            numero_acta = VALUES(numero_acta),
            miembro_1_nombre = VALUES(miembro_1_nombre),
            miembro_2_nombre = VALUES(miembro_2_nombre),
            miembro_3_nombre = VALUES(miembro_3_nombre),
            miembro_4_nombre = VALUES(miembro_4_nombre),
            miembro_5_nombre = VALUES(miembro_5_nombre),
            punto_3_perfiles = VALUES(punto_3_perfiles),
            perfiles_json = VALUES(perfiles_json),
            punto_4_aspirantes = VALUES(punto_4_aspirantes),
            punto_5_calificacion = VALUES(punto_5_calificacion),
            punto_6_entrevista = VALUES(punto_6_entrevista),
            compromisos_json = VALUES(compromisos_json),
            observaciones = VALUES(observaciones),
            estado_acta = VALUES(estado_acta)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssssss", 
            $departamento_id, $anio_semestre, $lugar_reunion, $fecha_reunion, $numero_acta,
            $m1_nom, $m2_nom, $m3_nom, $m4_nom, $m5_nom,
            $punto_3, $perfiles_json, $punto_4, $punto_5, $punto_6, $compromisos_json, $observaciones, $estado
        );

    if ($stmt->execute()) {
        $facultad_id = obtenerIdFacultadLocal($departamento_id, $conn);
        
        // --- LÓGICA DE REDIRECCIÓN ---

        // CASO 1: Guardar e ir a Gestionar Docentes -> SALE
        if ($accion == 'guardar_e_ir_gestion') {
            echo "
            <form id='redirectForm' action='consulta_todo_depto.php' method='POST'>
                <input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>
                <input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>
                <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
            </form>
            <script>document.getElementById('redirectForm').submit();</script>";
        } 
        // CASO 1.5: Guardar y salir (regresar al panel)
elseif ($accion == 'salir') {
    echo "
    <form id='redirectForm' action='consulta_todo_depto.php' method='POST'>
        <input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>
        <input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>
        <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
    </form>
    <script>document.getElementById('redirectForm').submit();</script>";
}
        // CASO 2: Finalizar Acta -> SALE (Con mensaje)
        elseif ($accion == 'finalizar') {
            echo "
            <form id='returnForm' action='consulta_todo_depto.php' method='POST'>
                <input type='hidden' name='facultad_id' value='".htmlspecialchars($facultad_id)."'>
                <input type='hidden' name='departamento_id' value='".htmlspecialchars($departamento_id)."'>
                <input type='hidden' name='anio_semestre' value='".htmlspecialchars($anio_semestre)."'>
            </form>
            <script>
                alert('✅ Acta FINALIZADA correctamente. Ya está disponible para impresión.');
                document.getElementById('returnForm').submit();
            </script>";
        }

        // CASO 3: Guardar Borrador -> SE QUEDA (Recarga gestion_for59.php)
        else { // $accion == 'borrador'
            echo "<script>
                alert('💾 Borrador guardado. Puede continuar editando.');
                window.location.href = 'gestion_for59.php?departamento_id=".urlencode($departamento_id)."&anio_semestre=".urlencode($anio_semestre)."';
            </script>";
        }

    } else {
        echo "Error al guardar: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
}
?>