<?php
require 'conn.php';
require 'funciones.php';
function prepararParaBusqueda($texto) {
    $buscar  = array('á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ');
    $reemplazo = array('A', 'E', 'I', 'O', 'U', 'A', 'E', 'I', 'O', 'U', 'N', 'N');
    $texto = str_replace($buscar, $reemplazo, $texto);
    return strtoupper(trim($texto));
}
$departamento_id = $_POST['departamento_id'] ?? '';
$anio_semestre   = $_POST['anio_semestre'] ?? '';
$perfiles_json   = $_POST['perfiles_busqueda'] ?? '[]';
$perfiles        = json_decode($perfiles_json, true) ?: [];

if (!$departamento_id || !$anio_semestre) {
    echo '<tr><td colspan="5" class="text-center">Error: parámetros faltantes.</td></tr>';
    exit;
}

$nombre_depto = obtenerNombreDepartamento($departamento_id);
$depto_busqueda = prepararParaBusqueda($nombre_depto);
$like_depto = "%$depto_busqueda%";

// Extraer palabras clave de los perfiles (solo el campo "Perfil")
$keywords = [];
foreach ($perfiles as $keyword) {
    $kw = prepararParaBusqueda($keyword);
    if ($kw) $keywords[] = $kw;
}
$keywords = array_unique(array_filter($keywords));

// Construir consulta con condiciones normalizadas
$sql = "SELECT 
            t.nombre_completo, 
            t.documento_tercero, 
            a.asp_titulos, 
            a.asp_correo, 
            a.asp_celular,
            (REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.asp_departamentos, 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U') LIKE ?) AS en_mi_depto
        FROM aspirante a
        JOIN tercero t ON a.fk_asp_doc_tercero = t.documento_tercero
        WHERE a.fk_asp_periodo = ? 
          AND (REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.asp_departamentos, 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U') LIKE ?
               OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.asp_titulos, 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U') LIKE ?";

foreach ($keywords as $key) {
    $sql .= " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.asp_titulos, 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U') LIKE '%$key%'";
}

$sql .= ") ORDER BY en_mi_depto DESC, t.nombre_completo ASC LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $like_depto, $anio_semestre, $like_depto, $like_depto);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo '<tr><td colspan="5" class="text-center py-3 text-muted">No se encontraron aspirantes sugeridos.</td></tr>';
    exit;
}

$idx = 1;
while ($row = $result->fetch_assoc()) {
    $badge = $row['en_mi_depto'] ? 'success' : 'warning';
    $badgeText = $row['en_mi_depto'] ? 'POSTULADO EN DEPARTAMENTO' : 'PERFIL AFÍN (NO POSTULADO)';
    echo '<tr data-cedula="' . htmlspecialchars($row['documento_tercero']) . '" data-nombre="' . htmlspecialchars(strtoupper($row['nombre_completo'])) . '">';
    echo '<td class="text-center align-middle"><input type="checkbox" class="form-check-input check-sug"></td>';
    echo '<td class="text-center fw-bold align-middle text-muted">' . $idx . '</td>';
    echo '<td class="nom-sug align-middle"><div class="fw-bold">' . htmlspecialchars($row['nombre_completo']) . '</div>';
    echo '<span class="text-muted x-small"><i class="fas fa-id-card"></i> ' . htmlspecialchars($row['documento_tercero']) . '</span><br>';
    echo '<span class="badge bg-' . $badge . '">' . $badgeText . '</span></td>';
    echo '<td class="tit-sug text-muted align-middle" style="font-size:0.72rem;">' . nl2br(htmlspecialchars($row['asp_titulos'])) . '</td>';
    echo '<td class="small align-middle"><i class="fas fa-envelope"></i> ' . htmlspecialchars($row['asp_correo']) . '<br><i class="fas fa-phone"></i> ' . htmlspecialchars($row['asp_celular']) . '</td>';
    echo '</tr>';
    $idx++;
}
?>