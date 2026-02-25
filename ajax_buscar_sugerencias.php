<?php
// ajax_buscar_sugerencias.php
require 'conn.php';
require 'funciones.php'; 

// Función Tokenizadora Mejorada
function limpiarYTokenizar($texto) {
    $texto = mb_strtoupper($texto, 'UTF-8');
    $buscar = ['Á','É','Í','Ó','Ú','Ñ'];
    $reempl = ['A','E','I','O','U','N'];
    $texto = str_replace($buscar, $reempl, $texto);
    $texto = preg_replace('/[^A-Z0-9\s]/', '', $texto);
    $palabras = explode(' ', $texto);
    
    // Palabras basura que NO queremos buscar
    $ignorar = ['DE', 'LA', 'EL', 'LOS', 'LAS', 'EN', 'Y', 'O', 'CON', 'PARA', 'UN', 'UNA', 'DEL', 'AL', 
                'DOCENTE', 'PROFESOR', 'MAGISTER', 'ESPECIALISTA', 'TITULO', 'CANDIDATO', 'ASPIRANTE'];
    
    $tokens = [];
    foreach($palabras as $p) {
        $p = trim($p);
        if(strlen($p) > 2 && !in_array($p, $ignorar)) { 
            $tokens[] = $p;
        }
    }
    return array_unique($tokens);
}

// 1. Recibir Datos
$departamento_id = $_POST['departamento_id'] ?? '';
$anio_semestre = $_POST['anio_semestre'] ?? '';
$perfiles_json = $_POST['perfiles_busqueda'] ?? '[]';
$perfiles = json_decode($perfiles_json, true);

// 2. Obtener Nombre del Departamento
$nombre_depto_raw = obtenerNombreDepartamento($departamento_id, $conn); 
$nombre_depto = mb_strtoupper($nombre_depto_raw, 'UTF-8');
$nombre_depto = str_replace(['Á','É','Í','Ó','Ú','Ñ'], ['A','E','I','O','U','N'], $nombre_depto);

$condiciones_sql = [];

// A. PRIORIDAD 1: Inscritos en el Departamento (INSCRITOS)
// Estos siempre salen porque se postularon explícitamente a tu departamento
if (!empty($nombre_depto)) {
    $condiciones_sql[] = "a.asp_departamentos LIKE '%$nombre_depto%'";
}

// B. PRIORIDAD 2: Coincidencias por Perfil (AFINES)
if (!empty($perfiles)) {
    foreach ($perfiles as $perfil_texto) {
        $palabras_clave = limpiarYTokenizar($perfil_texto);
        
        if (!empty($palabras_clave)) {
            $sub_condiciones = [];
            foreach ($palabras_clave as $palabra) {
                // Buscamos la palabra en Título O en Departamentos
                $sub_condiciones[] = "(a.asp_titulos LIKE '%$palabra%' OR a.asp_departamentos LIKE '%$palabra%')";
            }
            
            if (!empty($sub_condiciones)) {
                // --- EL CAMBIO CLAVE ESTÁ AQUÍ ---
                // Antes decía ' OR ', por eso traía cualquier Ingeniero.
                // Ahora dice ' AND '. Obliga a cumplir TODAS las palabras de ese perfil.
                // Ejemplo: Debe tener "INGENIERO" Y "AMBIENTAL".
                $condiciones_sql[] = "(" . implode(' AND ', $sub_condiciones) . ")";
            }
        }
    }
}

// Validación de seguridad
if (empty($condiciones_sql)) {
    echo '<tr><td colspan="5" class="text-center text-muted">No se pudo determinar el criterio de búsqueda.</td></tr>';
    exit;
}

// Unimos los grupos grandes con OR
// (Es del depto) O (Cumple Perfil 1) O (Cumple Perfil 2)
$sql_where = implode(' OR ', $condiciones_sql);

$sql = "SELECT t.nombre_completo, t.documento_tercero, a.asp_titulos, a.asp_correo, a.asp_celular,
        (a.asp_departamentos LIKE ?) AS en_mi_depto
        FROM aspirante a
        JOIN tercero t ON a.fk_asp_doc_tercero = t.documento_tercero
        WHERE a.fk_asp_periodo = ? 
        AND ($sql_where)
        ORDER BY en_mi_depto DESC, t.nombre_completo ASC
        LIMIT 300";

$like_depto_param = "%$nombre_depto%"; 
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $like_depto_param, $anio_semestre);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<tr><td colspan="5" class="text-center text-muted">No se encontraron aspirantes que coincidan. Intente palabras clave más generales.</td></tr>';
} else {
    $i = 0;
    while ($s = $result->fetch_assoc()) {
        $i++;
        
        // Estilos
        if ($s['en_mi_depto']) {
            $badge = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> POSTULADO</span>';
            $clase_fila = ''; 
        } else {
            $badge = '<span class="badge bg-warning text-dark"><i class="fas fa-search"></i> PERFIL AFÍN</span>';
            $clase_fila = 'table-light'; 
        }
        
        // Celda con clase tit-sug para importar correctamente
        echo '<tr class="'.$clase_fila.'" data-cedula="'.htmlspecialchars($s['documento_tercero']).'" data-nombre="'.htmlspecialchars($s['nombre_completo']).'">
                <td class="text-center align-middle"><input type="checkbox" class="form-check-input check-sug"></td>
                <td class="text-center small">'.$i.'</td>
                <td class="nom-sug">
                    <div class="fw-bold text-uppercase">'.htmlspecialchars($s['nombre_completo']).'</div>
                    <span class="small text-muted"><i class="fas fa-id-card"></i> '.$s['documento_tercero'].'</span> '.$badge.'
                </td>
                <td class="tit-sug small text-muted" style="font-size:0.75rem">'.htmlspecialchars($s['asp_titulos']).'</td>
                <td class="small">'.htmlspecialchars($s['asp_celular']).'</td>
              </tr>';
    }
}
$stmt->close();
$conn->close();
?>