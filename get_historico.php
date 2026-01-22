<?php
// Configuración de errores para desarrollo (útil para depurar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --------------------------------------------------------------------------------------
// 1. Conexión a la Base de Datos - ¡AJUSTAR ESTO!
// --------------------------------------------------------------------------------------
$conn = new mysqli('localhost', 'root', '', 'contratacion_temporales');
if ($conn->connect_error) {
    http_response_code(500);
    die('<div class="alert alert-danger">Error de conexión a la base de datos: ' . $conn->connect_error . '</div>');
}

// --------------------------------------------------------------------------------------
// 2. Procesamiento de la Solicitud AJAX
// --------------------------------------------------------------------------------------
if (isset($_POST['cedula']) && !empty($_POST['cedula'])) {
    $cedula = $conn->real_escape_string($_POST['cedula']);
    
    // 2.1. Obtener TODOS los periodos históricos (ordenados) de la tabla 'periodo'
    $periodos_sql = "SELECT nombre_periodo FROM periodo ORDER BY nombre_periodo ASC";
    $periodos_res = $conn->query($periodos_sql);
    $periodos = [];
    while ($row = $periodos_res->fetch_assoc()) {
        $periodos[] = $row['nombre_periodo'];
    }
    
    // 2.2. Obtener periodos, departamento y DETALLES DE DEDICACIÓN (SQL AJUSTADO A SUS NOMBRES)
    $cedula_safe = $conn->real_escape_string($cedula);
$periodo_historico_limite = '2025-2';

$vinculacion_sql = "
    SELECT 
        S.anio_semestre, 
        GROUP_CONCAT(
            CONCAT(
                D.NOMBRE_DEPTO, 
                ' (', 
                S.tipo_docente, 
                ' - ', 
                -- Lógica para mostrar Horas si es Cátedra, o Dedicación si es Ocasional
                CASE 
                    -- Se suman horas y horas_r para mayor precisión
                    WHEN S.tipo_docente = 'Catedra' THEN CONCAT(COALESCE(S.horas, 0) + COALESCE(S.horas_r, 0), 'h')
                    -- Se usa el campo de dedicación reportada (tipo_dedicacion_r) si existe, sino el principal
                    ELSE COALESCE(NULLIF(S.tipo_dedicacion_r, ''), S.tipo_dedicacion)
                END,
                ')'
            ) 
            ORDER BY S.departamento_id -- Opcional: ordenar detalles dentro del grupo
            SEPARATOR ' | '
        ) AS vinculacion_detalles 
    FROM Solicitudes S
    JOIN deparmanentos D ON S.departamento_id = D.PK_DEPTO
    
    WHERE S.cedula = '$cedula_safe' 
    -- ------------------------------------------------------------------------------------------------
    -- CONDICIÓN DE ESTADO MEJORADA Y CONDICIONAL
    -- ------------------------------------------------------------------------------------------------
    AND (
        -- Regla 1: Incluir solicitudes activas (estado NULL o no 'anulado')
        (S.estado IS NULL OR S.estado <> 'anulado') 
        
        -- Regla 2: Incluir solicitudes 'anuladas' con motivos específicos (Histórico <= 2025-2)
        OR (
            S.estado = 'anulado' 
            AND S.anio_semestre <= '$periodo_historico_limite' 
            AND (
                S.s_observacion LIKE '%eemplazo%' 
                OR S.s_observacion LIKE '%jubilaci%' 
                OR S.s_observacion LIKE '%incapacidad%' 
                OR S.s_observacion LIKE '%Novedades docentes%'
            )
        )
        
        -- Regla 3: Incluir solicitudes 'anuladas' con cualquier id_novedad (Reciente > 2025-2)
        OR (
            S.estado = 'anulado' 
            AND S.anio_semestre > '$periodo_historico_limite' 
            AND S.id_novedad IS NOT NULL
        )
    )
    -- ------------------------------------------------------------------------------------------------
    
    GROUP BY S.anio_semestre
    ORDER BY S.anio_semestre ASC
";
    
    $vinculacion_res = $conn->query($vinculacion_sql);
    
    // Almacena el periodo y los detalles de la vinculación
    $vinculados = [];
    while ($row = $vinculacion_res->fetch_assoc()) {
        $vinculados[$row['anio_semestre']] = $row['vinculacion_detalles']; 
    }

    // --------------------------------------------------------------------------------------
    // 3. Generar la Línea de Tiempo (Visual, Departamento y Dedicación)
    // --------------------------------------------------------------------------------------
    
    if (empty($periodos)) {
        echo '<div class="alert alert-info">No se encontraron periodos académicos en el sistema.</div>';
        $conn->close();
        exit;
    }

    echo '<ul class="list-group">';
    
    foreach ($periodos as $periodo) {
        $is_vinculado = isset($vinculados[$periodo]);
        
        if ($is_vinculado) {
            $clase = 'list-group-item-success'; // Fondo verde para VINCULADO
            $texto = 'VINCULADO';
            $detalles_info = htmlspecialchars($vinculados[$periodo]);
            
            // Reemplazar el separador (' | ') por un salto de línea en el HTML para listar múltiples contratos
            $detalles_info_html = str_replace(' | ', '<br>', $detalles_info);
            
            // Generación de cada elemento VINCULADO
            echo "<li class='list-group-item $clase' style='border-left: 5px solid #198754; margin-bottom: 5px;'>";
            echo "    <div class='d-flex justify-content-between align-items-start'>";
            echo "        <div class='fw-bold'>$periodo</div>";
            echo "        <span class='text-success fw-bold'>✅ $texto</span>"; 
            echo "    </div>";
            
            // Detalle de las vinculaciones
            echo "    <div class='mt-2 ps-3'>";
            echo "        <small class='text-dark'>$detalles_info_html</small>";
            echo "    </div>";
            echo "</li>";
            
        } else {
            $clase = 'list-group-item-light text-muted'; // Fondo gris/claro para NO VINCULADO
            $texto = 'NO VINCULADO';

            // Generación de cada elemento NO VINCULADO
            echo "<li class='list-group-item $clase d-flex justify-content-between align-items-center' style='border-left: 5px solid #6c757d; margin-bottom: 5px;'>";
            echo "    <div class='fw-bold'>$periodo</div>";
            echo "    <span class='text-secondary'>❌ $texto</span>";
            echo "</li>";
        }
    }
    
    echo '</ul>';
    
} else {
    http_response_code(400);
    echo '<div class="alert alert-warning">Cédula no proporcionada.</div>';
}

$conn->close();
?>