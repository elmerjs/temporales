<?php
session_start();
require 'conn.php'; // Archivo con $conn = new mysqli(...)

// Colores institucionales UNICAUCA
$color_primario = "#0066CC";
$color_secundario = "#FFFFFF";

$yeard = date('Y');
$mes = date('n'); // mes numérico sin ceros iniciales (1 a 12)

$anio_actual = $yeard . '-' . ($mes <= 6 ? '1' : '2');

$vigencia_id_from_url = $_GET['vigencia_id'] ?? null;

// 1. Obtener la vigencia principal
$main_vigencia = [];

if ($vigencia_id_from_url) {
    $sql = "SELECT * FROM vigencia WHERE id = " . (int)$vigencia_id_from_url;
    $result = $conn->query($sql);
    $main_vigencia = $result->fetch_assoc();
}

if (empty($main_vigencia)) {
    $sql = "SELECT * FROM vigencia WHERE anio = '$anio_actual'";
    $result = $conn->query($sql);
    $main_vigencia = $result->fetch_assoc();
}

if (empty($main_vigencia)) {
    $main_vigencia = ['id' => 0, 'anio' => $anio_actual];
}

// 2. Obtener distribución existente
$distribucion = [];
if ($main_vigencia['id'] != 0) {
    $sql_dist = "SELECT tipo_vinculacion, sede, saldo_inicial
                 FROM detalle_vigencia
                 WHERE vigencia_id = {$main_vigencia['id']}";
    $result_dist = $conn->query($sql_dist);

    while($row = $result_dist->fetch_assoc()) {
        $key = $row['tipo_vinculacion'] . '_' . $row['sede'];
        $distribucion[$key] = $row['saldo_inicial'];
    }
}

// 3. Obtener saldos actuales para JavaScript
$saldos_js = [];
if ($main_vigencia['id'] != 0) {
    $sql_saldos = "SELECT CONCAT(tipo_vinculacion, '_', sede) as clave, saldo_actual
                   FROM detalle_vigencia
                   WHERE vigencia_id = {$main_vigencia['id']}";
    $result_saldos = $conn->query($sql_saldos);
    if ($result_saldos) {
        while($row = $result_saldos->fetch_assoc()) {
            $saldos_js[$row['clave']] = $row['saldo_actual'];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión Presupuestal - UNICAUCA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
      :root {
            --color-unicauca: #0066CC;
            --color-blanco: #FFFFFF;
        }
        .bg-unicauca {
            background-color: var(--color-unicauca);
            color: white;
        }
        .btn-unicauca {
            background-color: var(--color-unicauca);
            color: white;
        }
        .btn-unicauca:hover {
            background-color: #004d99;
            color: white;
        }
        .border-unicauca {
            border: 2px solid var(--color-unicauca);
        }
        .sidebar {
            background: linear-gradient(180deg, var(--color-unicauca) 0%, #0066cc 100%);
            min-height: 100vh;
        }
        .card-header-unicauca {
            background: var(--color-unicauca);
            color: white;
            font-weight: bold;
        }
        .logo-container {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .saldo-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .saldo-card-body {
            flex: 1;
            padding: 0.75rem;
        }
        .saldo-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        .saldo-value {
            font-size: 0.9rem;
            font-weight: bold;
            margin-top: 0.25rem;
        }
        .total-card {
            padding: 0.5rem;
        }
        .total-label {
            font-size: 0.9rem;
            font-weight: bold;
        }
        .total-value {
            font-size: 1.1rem;
            font-weight: bold;
        }   
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
         <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0">
                <div class="logo-container text-center">
                    <img src="images/ESCUDO2b.jpg" alt="Logo UNICAUCA" class="img-fluid">
<h5 class="mt-2 text-white">Vicerrectoría Académica</h5>
                </div>
                <div class="list-group list-group-flush mt-3">
                    <a href="#" class="list-group-item list-group-item-action bg-transparent text-white active">
                        <i class="bi bi-house-door me-2"></i>Dashboard
                    </a>
                    <a href="#" class="list-group-item list-group-item-action bg-transparent text-white">
                        <i class="bi bi-wallet2 me-2"></i>Vigencias
                    </a>
                    <a href="#" class="list-group-item list-group-item-action bg-transparent text-white">
                        <i class="bi bi-file-earmark-text me-2"></i>Oficios
                    </a>
                    <a href="#" class="list-group-item list-group-item-action bg-transparent text-white">
                        <i class="bi bi-graph-up me-2"></i>Reportes
                    </a>
                </div>
            </div>

            <!-- Contenido Principal -->
             <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold" style="color: var(--color-unicauca);">
                        <i class="bi bi-cash-coin me-2"></i>Presupuesto Ajustes Novedades
                    </h2>
                    <div class="d-flex align-items-center">
                        <span class="me-3">Bienvenido, <?= $_SESSION['name'] ?? 'Usuario' ?></span>
                        <div class="dropdown">
                            <button class="btn btn-unicauca dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i>Salir</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Panel de Control -->
       <div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-unicauca shadow-sm saldo-card">
            <div class="card-body">
                <h5 class="card-title">Vigencia Actual</h5>
                <h3 class="fw-bold text-center" style="color: var(--color-unicauca);">
                    <?= $main_vigencia['anio'] ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card border-unicauca shadow-sm saldo-card">
            <div class="card-body">
                <?php
                // Your original PHP logic for data fetching and total calculation
                $sql = "SELECT * FROM detalle_vigencia WHERE vigencia_id = {$main_vigencia['id']}";
                $result = $conn->query($sql);
                $total_saldos = 0; // Inicializar variable para el total
                ?>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="card-title mb-0">Saldos Actuales</h5>
                    <div class="total-card bg-light rounded p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="total-label fw-bold me-2">TOTAL:</span>
                            <span class="total-value text-success fw-bold">
                                <?php
                                    // Calculate total here if results exist, otherwise display 0
                                    if ($result && $result->num_rows > 0) {
                                        // Reset result pointer to reuse it for individual displays
                                        $result->data_seek(0);
                                        while($row_total = $result->fetch_assoc()) {
                                            $total_saldos += $row_total['saldo_actual'];
                                        }
                                        echo '$' . number_format($total_saldos, 0, ',', '.');
                                    } else {
                                        echo '$0';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row g-2">
                    <?php
                    // Check if result was successful and has rows before iterating
                    if ($result && $result->num_rows > 0) {
                        // Reset result pointer to the beginning for the individual display loop
                        $result->data_seek(0);
                        while($row = $result->fetch_assoc()):
                            $badge_class = $row['sede'] == 'POP' ? 'bg-primary' : 'bg-success';
                    ?>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 saldo-card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold small"><?= $row['tipo_vinculacion'] ?></span>
                                <span class="badge <?= $badge_class ?> saldo-badge"><?= $row['sede'] ?></span>
                            </div>
                            <div class="text-end mt-1">
                                <div class="saldo-value fw-bold">
                                    $<?= number_format($row['saldo_actual'], 0, ',', '.') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        endwhile;
                    } else {
                        echo '<div class="col-12"><div class="alert alert-warning">No hay datos de saldos para mostrar.</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

                <!-- Paso 1: Configuración Inicial -->
            <!-- Paso 1: Configuración Inicial -->
<!-- Paso 1: Configuración Inicial -->
            <div class="card border-unicauca shadow-sm mb-4">
    <div class="card-header card-header-unicauca">
        <i class="bi bi-wallet2 me-2"></i>Configuración Inicial de Vigencia
    </div>
    <div class="card-body">
        <form action="guardar_vigencia.php" method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Seleccionar Vigencia</label>
                    <select class="form-select" name="vigencia" id="select-vigencia" required>
                        <option value="">-- Seleccione año --</option>
                        <?php
                        $sql = "SELECT * FROM vigencia ORDER BY anio DESC";
                        $result = $conn->query($sql);
                        
                        while($row = $result->fetch_assoc()):
                            $selected = ($row['id'] == $main_vigencia['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $row['id'] ?>" <?= $selected ?>>
                            <?= $row['anio'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <div class="d-flex align-items-end h-100">
                        <button type="button" class="btn btn-unicauca" data-bs-toggle="modal" data-bs-target="#nuevaVigenciaModal">
                            <i class="bi bi-plus-circle me-2"></i>Crear Nueva Vigencia
                        </button>
                    </div>
                </div>
            </div>

            <!-- Botón para mostrar/ocultar la distribución -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold">Distribución Presupuestal Inicial</h5>
                <button type="button" class="btn btn-unicauca" id="toggle-distribucion">
                    <i class="bi bi-eye me-2"></i> Mostrar
                </button>
            </div>

            <!-- Contenedor de la distribución (inicialmente oculto) -->
            <div id="distribucion-container" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border-unicauca">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">OCASIONAL</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Sede Popayán (POP)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="ocasional_pop" 
                                               required step="0.01" min="0"
                                               value="<?= $distribucion['OCASIONAL_POP'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Regionalización (REGI)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="ocasional_regi" 
                                               required step="0.01" min="0"
                                               value="<?= $distribucion['OCASIONAL_REGI'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card border-unicauca">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">CÁTEDRA</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Sede Popayán (POP)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="catedra_pop" 
                                               required step="0.01" min="0"
                                               value="<?= $distribucion['CATEDRA_POP'] ?? '' ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Regionalización (REGI)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" name="catedra_regi" 
                                               required step="0.01" min="0"
                                               value="<?= $distribucion['CATEDRA_REGI'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-lg btn-unicauca">
                        <i class="bi bi-save me-2"></i>
                        <?= empty($distribucion) ? 'Guardar Distribución Inicial' : 'Actualizar Distribución' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Script para mostrar/ocultar la distribución
document.getElementById('toggle-distribucion').addEventListener('click', function() {
    const container = document.getElementById('distribucion-container');
    const icon = this.querySelector('i');
    
    if (container.style.display === 'none') {
        container.style.display = 'block';
        icon.className = 'bi bi-eye-slash me-2';
        this.innerHTML = icon.outerHTML + ' Ocultar';
    } else {
        container.style.display = 'none';
        icon.className = 'bi bi-eye me-2';
        this.innerHTML = icon.outerHTML + ' Mostrar';
    }
});
</script>    
                 
                <!-- Paso 2: Registro de Oficios y Movimientos -->
             <div class="card border-unicauca shadow-sm">
    <div class="card-header card-header-unicauca">
        <i class="bi bi-file-earmark-text me-2"></i>Registro de Oficios y Movimientos
    </div>
    <div class="card-body">
        <form action="guardar_oficio.php" method="POST" id="form-oficio">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Número de Oficio</label>
                    <input type="text" class="form-control" name="numero_oficio" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fecha de Oficio</label>
                    <input type="date" class="form-control" name="fecha_oficio" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Vigencia</label>
                    <select class="form-select" name="vigencia_oficio" id="vigencia-oficio" required>
                        <?php
                        $sql = "SELECT * FROM vigencia ORDER BY anio DESC";
                        $result = $conn->query($sql);
                        
                        while($row = $result->fetch_assoc()):
                            $selected = ($row['id'] == $main_vigencia['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $row['id'] ?>" <?= $selected ?>>
                            <?= $row['anio'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Tipo de Vinculación</label>
                    <select class="form-select" id="tipo_vinculacion" required>
                        <option value="">-- Seleccione --</option>
                        <option value="OCASIONAL">OCASIONAL</option>
                        <option value="CATEDRA">CÁTEDRA</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Sede</label>
                    <select class="form-select" id="sede" required>
                        <option value="">-- Seleccione --</option>
                        <option value="POP">Popayán (POP)</option>
                        <option value="REGI">Regionalización (REGI)</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="tipo_vinculacion" id="hidden-tipo" value="">
            <input type="hidden" name="sede" id="hidden-sede" value="">

            <div class="mb-3">
                <div class="alert alert-info">
                    <strong>Saldo actual:</strong> 
                    <span id="saldo-actual-text">Seleccione tipo y sede para ver el saldo</span>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo Movimiento</th>
                            <th>Número CDP</th>
                            <th>Valor</th>
                            <th>Saldo Anterior</th>
                            <th>Saldo Posterior</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="movimientos-container">
                        <!-- Las filas se agregarán dinámicamente -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-unicauca" id="btn-agregar-movimiento">
                    <i class="bi bi-plus-circle me-2"></i>Agregar Movimiento
                </button>
            </div>

            <div class="d-grid mt-3">
                <button type="submit" class="btn btn-lg btn-unicauca">
                    <i class="bi bi-send-check me-2"></i>Registrar Oficio y Movimientos
                </button>
            </div>
        </form>
    </div>
</div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Vigencia -->
    <div class="modal fade" id="nuevaVigenciaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-unicauca text-white">
                    <h5 class="modal-title">Crear Nueva Vigencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="crear_vigencia.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Año de Vigencia</label>
                            <input type="number" class="form-control" name="anio_vigencia" min="2000" max="2100" value="<?= date('Y') + 1 ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-unicauca">Crear Vigencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Datos de saldos actuales desde PHP
        const saldosActuales = <?= json_encode($saldos_js) ?>;
        
        // Variables globales
        let saldoActual = 0;
        let saldoTemporal = 0;
        let movimientos = [];
        
        // Función para formatear valores monetarios
        function formatMoney(value) {
            return '$' + value.toLocaleString('es-CO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Actualizar saldo actual al cambiar tipo o sede
        function actualizarSaldoActual() {
            const tipo = document.getElementById('tipo_vinculacion').value;
            const sede = document.getElementById('sede').value;
            
            if(tipo && sede) {
                const key = tipo + '_' + sede;
                saldoActual = saldosActuales[key] || 0;
                saldoTemporal = saldoActual;
                
                document.getElementById('saldo-actual-text').textContent = formatMoney(saldoActual);
                document.getElementById('hidden-tipo').value = tipo;
                document.getElementById('hidden-sede').value = sede;
            }
        }
        
        // Escuchar cambios en los selectores
        document.getElementById('tipo_vinculacion').addEventListener('change', actualizarSaldoActual);
        document.getElementById('sede').addEventListener('change', actualizarSaldoActual);
        
        // Función para agregar movimiento
        document.getElementById('btn-agregar-movimiento').addEventListener('click', function() {
            const tipoVinculacion = document.getElementById('tipo_vinculacion').value;
            const sede = document.getElementById('sede').value;
            
            if(!tipoVinculacion || !sede) {
                alert('Por favor seleccione tipo de vinculación y sede');
                return;
            }
            
            // Si es el primer movimiento, obtener saldo actual
            if(movimientos.length === 0) {
                actualizarSaldoActual();
            }
            
            const container = document.getElementById('movimientos-container');
            const rowId = Date.now(); // ID único para la fila
            
            // Calcular saldos
            const saldoAnterior = saldoTemporal;
            // Por defecto no hay movimiento, saldo posterior = saldo anterior
            const saldoPosterior = saldoAnterior;
            
            const newRow = document.createElement('tr');
            newRow.dataset.rowId = rowId;
            newRow.innerHTML = `
                <td>
                    <select class="form-select tipo-movimiento" name="movimientos[${rowId}][tipo]" required>
                        <option value="CONTRATA">CONTRATA</option>
                        <option value="LIBERA">LIBERA</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control cdp" name="movimientos[${rowId}][cdp]" required>
                </td>
                <td>
                    <input type="number" class="form-control valor" name="movimientos[${rowId}][valor]" 
                           step="0.01" min="0" required oninput="actualizarSaldos(${rowId})">
                </td>
                <td class="saldo-anterior text-end">${formatMoney(saldoAnterior)}</td>
                <td class="saldo-posterior text-end">${formatMoney(saldoPosterior)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger btn-eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            container.appendChild(newRow);
            
            // Guardar referencia al movimiento
            movimientos.push({
                id: rowId,
                tipo: 'CONTRATA',
                valor: 0,
                saldoAnterior: saldoAnterior,
                saldoPosterior: saldoPosterior
            });
            
            // Actualizar select para que dispare el cálculo
            newRow.querySelector('.tipo-movimiento').addEventListener('change', function() {
                actualizarSaldos(rowId);
            });
            
            // Agregar evento para eliminar fila
            newRow.querySelector('.btn-eliminar').addEventListener('click', function() {
                eliminarMovimiento(rowId);
            });
        });
        
        // Función para actualizar saldos cuando cambia un movimiento
        function actualizarSaldos(rowId) {
            const row = document.querySelector(`tr[data-row-id="${rowId}"]`);
            if (!row) return;
            
            // Obtener valores del movimiento
            const tipo = row.querySelector('.tipo-movimiento').value;
            const valor = parseFloat(row.querySelector('.valor').value) || 0;
            
            // Encontrar el movimiento en el array
            const movimientoIndex = movimientos.findIndex(m => m.id == rowId);
            if (movimientoIndex === -1) return;
            
            // Obtener saldo anterior (es el saldo posterior del movimiento anterior o el inicial)
            let saldoAnterior = 0;
            if (movimientoIndex === 0) {
                saldoAnterior = saldoActual;
            } else {
                saldoAnterior = movimientos[movimientoIndex - 1].saldoPosterior;
            }
            
            // Calcular saldo posterior
            let saldoPosterior = saldoAnterior;
            if (tipo === 'CONTRATA') {
                saldoPosterior = saldoAnterior - valor;
            } else if (tipo === 'LIBERA') {
                saldoPosterior = saldoAnterior + valor;
            }
            
            // Actualizar movimiento en el array
            movimientos[movimientoIndex] = {
                ...movimientos[movimientoIndex],
                tipo: tipo,
                valor: valor,
                saldoAnterior: saldoAnterior,
                saldoPosterior: saldoPosterior
            };
            
            // Actualizar la fila
            row.querySelector('.saldo-anterior').textContent = formatMoney(saldoAnterior);
            row.querySelector('.saldo-posterior').textContent = formatMoney(saldoPosterior);
            
            // Recalcular saldos para movimientos posteriores
            recalcularSaldosPosteriores(movimientoIndex + 1);
            
            // Actualizar saldo temporal global
            if (movimientos.length > 0) {
                saldoTemporal = movimientos[movimientos.length - 1].saldoPosterior;
            }
        }
        
        // Función para recalcular saldos posteriores
        function recalcularSaldosPosteriores(startIndex) {
            for (let i = startIndex; i < movimientos.length; i++) {
                const prevMovimiento = movimientos[i - 1];
                const currentMovimiento = movimientos[i];
                
                // El saldo anterior de este movimiento es el saldo posterior del anterior
                let saldoAnterior = prevMovimiento.saldoPosterior;
                let saldoPosterior = saldoAnterior;
                
                // Calcular nuevo saldo posterior
                if (currentMovimiento.tipo === 'CONTRATA') {
                    saldoPosterior = saldoAnterior - currentMovimiento.valor;
                } else if (currentMovimiento.tipo === 'LIBERA') {
                    saldoPosterior = saldoAnterior + currentMovimiento.valor;
                }
                
                // Actualizar movimiento
                movimientos[i] = {
                    ...currentMovimiento,
                    saldoAnterior: saldoAnterior,
                    saldoPosterior: saldoPosterior
                };
                
                // Actualizar la fila en la tabla
                const row = document.querySelector(`tr[data-row-id="${movimientos[i].id}"]`);
                if (row) {
                    row.querySelector('.saldo-anterior').textContent = formatMoney(saldoAnterior);
                    row.querySelector('.saldo-posterior').textContent = formatMoney(saldoPosterior);
                }
            }
        }
        
        // Función para eliminar un movimiento
        function eliminarMovimiento(rowId) {
            // Encontrar índice del movimiento
            const movimientoIndex = movimientos.findIndex(m => m.id == rowId);
            if (movimientoIndex === -1) return;
            
            // Eliminar movimiento del array
            movimientos.splice(movimientoIndex, 1);
            
            // Eliminar fila de la tabla
            const row = document.querySelector(`tr[data-row-id="${rowId}"]`);
            if (row) row.remove();
            
            // Si era el primer movimiento, resetear saldo temporal
            if (movimientoIndex === 0 && movimientos.length === 0) {
                saldoTemporal = saldoActual;
            }
            
            // Si hay movimientos después, recalcular
            if (movimientos.length > 0) {
                // Si eliminamos el primero, recalculamos desde el nuevo primero
                const startIndex = movimientoIndex === 0 ? 0 : movimientoIndex;
                recalcularSaldosPosteriores(startIndex);
                
                // Actualizar saldo temporal con el último movimiento
                saldoTemporal = movimientos[movimientos.length - 1].saldoPosterior;
            }
        }
        
        // Inicializar saldo si ya hay selección
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('tipo_vinculacion').value && 
                document.getElementById('sede').value) {
                actualizarSaldoActual();
            }
        });
    </script>
     <script>
        // Script para recargar la página al cambiar la vigencia - CORREGIDO
        document.getElementById('select-vigencia').addEventListener('change', function() {
            const vigenciaId = this.value;
            if (vigenciaId) {
                window.location.href = `saldos_novedades.php?vigencia_id=${vigenciaId}`;
            }
        });
    </script>
</body>
</html>