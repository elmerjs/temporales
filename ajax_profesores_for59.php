<?php
require 'conn.php';
require 'funciones.php';

header('Content-Type: text/html; charset=utf-8');

$departamento_id = $_POST['departamento_id'] ?? '';
$anio_semestre   = $_POST['anio_semestre'] ?? '';

if (!$departamento_id || !$anio_semestre) {
    echo '<div class="alert alert-danger">Parámetros insuficientes.</div>';
    exit;
}

$sql = "SELECT nombre, cedula, tipo_docente, sede,
               tipo_dedicacion, tipo_dedicacion_r, 
               horas, horas_r
        FROM solicitudes 
        WHERE departamento_id = ? AND anio_semestre = ? 
          AND (estado <> 'an' OR estado IS NULL)
        ORDER BY tipo_docente ASC, nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $departamento_id, $anio_semestre);
$stmt->execute();
$res = $stmt->get_result();
$profesores = [];
while ($p = $res->fetch_assoc()) {
    $profesores[] = $p;
}
$total = count($profesores);
?>

<div class="card border-info mb-4">
    <div class="card-header bg-info text-white small fw-bold">
        <i class="fas fa-users-cog me-2"></i> Candidatos Detectados (Profesores Cargados)
    </div>
    <div class="card-body bg-light">
        <?php if ($total == 0): ?>
            <div class="alert alert-warning border-0 shadow-sm text-center py-4">
                <h6 class="text-danger fw-bold mb-3">
                    <i class="fas fa-info-circle fa-lg me-2"></i> Sin Profesores cargados
                </h6>
                <p class="small text-muted mb-4">
                    Actualmente no hay docentes en el acta.<br>
                    Para cargar, use el botón "Ir a Cargar Profesores" en la parte inferior.
                </p>
                <button type="submit" name="accion" value="guardar_e_ir_gestion" 
                        class="btn btn-outline-primary btn-sm px-4 shadow-sm fw-bold">
                    <i class="fas fa-arrow-right me-2"></i> Ir a Gestion Departamento
                </button>
            </div>
        <?php else: ?>
            <div class="alert alert-success border-0 shadow-sm mb-2 py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-check-circle me-2"></i> <strong><?= $total ?></strong> Docente(s) cargado(s).
                    </div>
                </div>
            </div>
            <div class="table-responsive bg-white border rounded shadow-sm" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-striped mb-0 small" id="tablaPunto7For59">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th>Docente</th>
                            <th>Vinculación / Dedicación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($profesores as $i => $prof): ?>
                            <?php 
                            $html_badges = '';
                            if ($prof['tipo_docente'] == 'Ocasional') {
                                if (!empty($prof['tipo_dedicacion'])) {
                                    $sigla = ($prof['tipo_dedicacion'] == 'TC') ? 'OTC' : 'OMT';
                                    $html_badges .= '<span class="badge bg-primary me-1">' . $sigla . ' (Pop)</span>';
                                }
                                if (!empty($prof['tipo_dedicacion_r'])) {
                                    $sigla = ($prof['tipo_dedicacion_r'] == 'TC') ? 'OTC' : 'OMT';
                                    $html_badges .= '<span class="badge bg-purple text-white" style="background-color: #6f42c1;">' . $sigla . ' (Reg)</span>';
                                }
                            } elseif ($prof['tipo_docente'] == 'Catedra') {
                                if (!empty($prof['horas']) && $prof['horas'] > 0) {
                                    $html_badges .= '<span class="badge bg-secondary me-1">' . (float)$prof['horas'] . ' HRS Pop</span>';
                                }
                                if (!empty($prof['horas_r']) && $prof['horas_r'] > 0) {
                                    $html_badges .= '<span class="badge bg-secondary border border-white" style="background-color: #5a6268;">' . (float)$prof['horas_r'] . ' HRS Reg</span>';
                                }
                            }
                            if (empty($html_badges)) {
                                $html_badges = '<span class="badge bg-light text-dark border">' . htmlspecialchars($prof['tipo_docente']) . '</span>';
                            }
                            ?>
                            <tr>
                                <td class="text-center align-middle"><?= $i + 1 ?></td>
                                <td class="align-middle">
                                    <strong class="text-primary text-uppercase"><?= htmlspecialchars($prof['nombre']) ?></strong><br>
                                    <span class="text-muted"><i class="fas fa-id-card me-1"></i> <?= $prof['cedula'] ?></span>
                                </td>
                                <td class="align-middle"><?= $html_badges ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>