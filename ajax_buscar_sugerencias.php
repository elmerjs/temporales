<?php
// ajax_buscar_sugerencias.php
require 'conn.php';
require 'funciones.php'; 

/**
 * Función para limpiar el texto y convertirlo en un array de palabras clave
 */
function limpiarYTokenizar($texto) {
    $buscar  = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
    $reemplazo = array('a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N');
    $texto = str_replace($buscar, $reemplazo, $texto);
    $texto = strtolower(trim($texto));
    // Eliminar caracteres especiales y dejar solo letras, números y espacios
    $texto = preg_replace('/[^a-z0-9\s]/', '', $texto);
    return array_unique(array_filter(explode(' ', $texto)));
}

// 1. Recibir Datos
$departamento_id = $_POST['departamento_id'] ?? '';
$anio_semestre = $_POST['anio_semestre'] ?? '';
$perfiles_json = $_POST['perfiles_busqueda'] ?? '[]';
$perfiles = json_decode($perfiles_json, true);

// --- LÓGICA DE PERIODO FLEXIBLE ---
$solo_anio = explode('-', $anio_semestre)[0];
$like_periodo_anio = $solo_anio . '%'; 

// 2. Obtener y Normalizar Nombre del Departamento
$nombre_depto_raw = obtenerNombreDepartamento($departamento_id, $conn); 
$nombre_depto = mb_strtoupper($nombre_depto_raw, 'UTF-8');
$nombre_depto = str_replace(['Á','É','Í','Ó','Ú','Ñ'], ['A','E','I','O','U','N'], $nombre_depto);

$condiciones_sql = [];

// A. PRIORIDAD 1: Inscritos en el Departamento
if (!empty($nombre_depto)) {
    $condiciones_sql[] = "a.asp_departamentos LIKE '%$nombre_depto%'";
}

// B. PRIORIDAD 2: Coincidencias por Perfil (Tokenizado)
if (!empty($perfiles)) {
    foreach ($perfiles as $perfil_texto) {
        $palabras_clave = limpiarYTokenizar($perfil_texto);
        if (!empty($palabras_clave)) {
            $sub_condiciones = [];
            foreach ($palabras_clave as $palabra) {
                $sub_condiciones[] = "(a.asp_titulos LIKE '%$palabra%' OR a.asp_departamentos LIKE '%$palabra%')";
            }
            if (!empty($sub_condiciones)) {
                $condiciones_sql[] = "(" . implode(' AND ', $sub_condiciones) . ")";
            }
        }
    }
}

if (empty($condiciones_sql)) {
    echo '<tr><td colspan="5" class="text-center text-muted">No se pudo determinar el criterio de búsqueda. Defina un perfil en el Punto 3.</td></tr>';
    exit;
}

$sql_where = implode(' OR ', $condiciones_sql);

// Consulta con GROUP BY para evitar duplicados del mismo año
$sql = "SELECT 
            t.nombre_completo, 
            t.documento_tercero, 
            a.asp_titulos, 
            a.asp_correo, 
            a.asp_celular,
            MAX(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.asp_departamentos, 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U') LIKE ?) AS en_mi_depto,
            MAX(a.fk_asp_periodo = ?) AS es_periodo_exacto,
            MAX(a.fk_asp_periodo) AS periodo_encontrado
        FROM aspirante a
        JOIN tercero t ON a.fk_asp_doc_tercero = t.documento_tercero
        WHERE a.fk_asp_periodo LIKE ? 
          AND ($sql_where)
        GROUP BY t.documento_tercero
        ORDER BY en_mi_depto DESC, es_periodo_exacto DESC, t.nombre_completo ASC
        LIMIT 150";

$like_depto_param = "%$nombre_depto%"; 
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $like_depto_param, $anio_semestre, $like_periodo_anio);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<tr><td colspan="5" class="text-center text-muted">No se encontraron aspirantes en el año ' . $solo_anio . '.</td></tr>';
} else {
    $i = 0;
    while ($s = $result->fetch_assoc()) {
        $i++;
        $badgeClass = $s['en_mi_depto'] ? 'bg-success' : 'bg-warning text-dark';
        $badgeText = $s['en_mi_depto'] ? 'POSTULADO' : 'PERFIL AFÍN';
        $nota_periodo = (!$s['es_periodo_exacto']) ? ' <small class="text-primary">('.$s['periodo_encontrado'].')</small>' : '';

        echo '<tr data-cedula="'.htmlspecialchars($s['documento_tercero']).'" data-nombre="'.htmlspecialchars($s['nombre_completo']).'">
                <td class="text-center align-middle"><input type="checkbox" class="form-check-input check-sug"></td>
                <td class="text-center small">'.$i.'</td>
                <td class="nom-sug">
                    <div class="fw-bold text-uppercase">'.htmlspecialchars($s['nombre_completo']).'</div>
                    <span class="small text-muted"><i class="fas fa-id-card"></i> '.$s['documento_tercero'].'</span> 
                    <span class="badge '.$badgeClass.'" style="font-size:0.65rem">'.$badgeText.'</span>'.$nota_periodo.'
                </td>
                <td class="tit-sug small text-muted" style="font-size:0.75rem">'.nl2br(htmlspecialchars($s['asp_titulos'])).'</td>
                <td class="small">'.htmlspecialchars($s['asp_correo']).'<br>'.htmlspecialchars($s['asp_celular']).'</td>
              </tr>';
    }
}
$stmt->close();
$conn->close();
?>