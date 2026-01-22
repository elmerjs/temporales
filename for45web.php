<?php
// for45pdf.php
// Este script genera una versión PDF del formato FOR-45 usando FPDF

// Asegúrate de que la ruta a fpdf.php sea correcta
require('fpdf186/fpdf.php');
require 'funciones.php'; // Asegúrate de que este archivo contiene la función existeSolicitudAnterior si la usas
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos

// Obtener los valores de las variables (pueden ser null si no están presentes)
$id_solicitud = $_GET['id_solicitud'] ?? null;
$departamento_id = $_GET['departamento_id'] ?? null;
$anio_semestre = $_GET['anio_semestre'] ?? null;

$numero_acta = $_GET['numero_acta'] ?? null;
$fecha_acta_str = isset($_GET['fecha_actab']) ? $_GET['fecha_actab'] : null;

// Parsear la fecha para Día, Mes, Año si está presente
$day = $month = $year = '';
if ($fecha_acta_str) {
    list($year, $month, $day) = explode('-', $fecha_acta_str);
}

// Variables para almacenar los resultados de la consulta SQL (inicializar con valores predeterminados)
$nombre_facultad = '';
$nombre_departamento = '';
$periodo_consulta = '';
$email_solicitante = '';
$nombre_solicitante = '';
$cedula_solicitante = '';
$tipo_docente = '';
$vinculacion_ocasional = '';
$vinculacion_ocasional_reg = '';
$horas_p = 0;
$horas_r = 0;
$anexa_hv_nuevo = '';
$actualiza_hv_antiguo = '';

// Nuevos campos recibidos del modal (ya actualizados en la BD por el script principal)
$pregrado = $_GET['pregrado'] ?? '';
$especializacion = $_GET['especializacion'] ?? '';
$maestria = $_GET['maestria'] ?? '';
$doctorado = $_GET['doctorado'] ?? '';
$otro_estudio = $_GET['otro_estudio'] ?? '';
$experiencia_docente = $_GET['experiencia_docente'] ?? '';
$experiencia_profesional = $_GET['experiencia_profesional'] ?? '';
$otra_experiencia = $_GET['otra_experiencia'] ?? '';

// Realizar la consulta a la base de datos para obtener todos los datos
if (isset($anio_semestre) && isset($departamento_id) && isset($id_solicitud)) {
    $sql = "SELECT
                facultad.Nombre_fac_minb,
                deparmanentos.depto_nom_propio,
                depto_periodo.periodo,
                depto_periodo.dp_acta_periodo,
                depto_periodo.dp_fecha_acta,
                solicitudes.nombre,
                solicitudes.cedula,
                tercero.email,
                solicitudes.tipo_docente,
                solicitudes.tipo_dedicacion AS vincul_ocasional,
                solicitudes.tipo_dedicacion_r AS vicul_ocasional_reg,
                solicitudes.horas,
                solicitudes.horas_r,
                solicitudes.anexa_hv_docente_nuevo,
                solicitudes.actualiza_hv_antiguo,
                solicitudes.pregrado,
                solicitudes.especializacion,
                solicitudes.maestria,
                solicitudes.doctorado,
                solicitudes.otro_estudio,
                solicitudes.experiencia_docente,
                solicitudes.experiencia_profesional,
                solicitudes.otra_experiencia
            FROM depto_periodo
            JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
            JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
            JOIN solicitudes ON (solicitudes.anio_semestre = depto_periodo.periodo AND solicitudes.departamento_id = depto_periodo.fk_depto_dp)
            JOIN tercero ON tercero.documento_tercero = solicitudes.cedula
            WHERE depto_periodo.periodo = ?
              AND depto_periodo.fk_depto_dp = ?
              AND solicitudes.id_solicitud = ?";

    $stmt = $con->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sii", $anio_semestre, $departamento_id, $id_solicitud);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            $nombre_facultad = $fila['Nombre_fac_minb'];
            $nombre_departamento = $fila['depto_nom_propio'];
            $periodo_consulta = $fila['periodo'];
            $numero_acta = $fila['dp_acta_periodo'];
            $fecha_acta_str_db = $fila['dp_fecha_acta']; // Fecha de la BD
            
            // Si la fecha de la BD es más relevante, úsala
            if ($fecha_acta_str_db) {
                list($year, $month, $day) = explode('-', $fecha_acta_str_db);
            }

            $nombre_solicitante = $fila['nombre'];
            $cedula_solicitante = $fila['cedula'];
            $email_solicitante = $fila['email'];
            $tipo_docente = $fila['tipo_docente'];
            $vinculacion_ocasional = $fila['vincul_ocasional'];
            $vinculacion_ocasional_reg = $fila['vicul_ocasional_reg'];
            $horas_p = $fila['horas'];
            $horas_r = $fila['horas_r'];
            $anexa_hv_nuevo = $fila['anexa_hv_docente_nuevo'];
            $actualiza_hv_antiguo = $fila['actualiza_hv_antiguo'];

            // Estos campos ya vienen del GET y fueron actualizados en la BD, se usan los de la BD
            $pregrado = $fila['pregrado'];
            $especializacion = $fila['especializacion'];
            $maestria = $fila['maestria'];
            $doctorado = $fila['doctorado'];
            $otro_estudio = $fila['otro_estudio'];
            $experiencia_docente = $fila['experiencia_docente'];
            $experiencia_profesional = $fila['experiencia_profesional'];
            $otra_experiencia = $fila['otra_experiencia'];

            // ✅ NUEVO: Si los campos de estudio están vacíos, buscar en la tabla aspirante
            if (empty($pregrado) && empty($especializacion) && empty($maestria) && empty($doctorado) && empty($otro_estudio)) {
                $titulosAspirante = obtenerTitulosAspirante($cedula_solicitante, $anio_semestre, $con);
                if ($titulosAspirante) {
                    $pregrado = $titulosAspirante['pregrado'] ?? $pregrado;
                    $especializacion = $titulosAspirante['especializacion'] ?? $especializacion;
                    $maestria = $titulosAspirante['maestria'] ?? $maestria;
                    $doctorado = $titulosAspirante['doctorado'] ?? $doctorado;
                    // Nota: 'otro_estudio' no se está parseando en la función JavaScript, se mantiene igual aquí
                }
            }
        }
        $stmt->close();
    }
}
$con->close(); // Cerrar la conexión después de todas las consultas

// ✅ NUEVA FUNCIÓN: Obtener y parsear títulos desde la tabla aspirante
function obtenerTitulosAspirante($cedula, $periodo, $conexion) {
    $sql_aspirante = "SELECT asp_titulos FROM aspirante 
                      WHERE fk_asp_doc_tercero = ? AND fk_asp_periodo = ?";
    
    $stmt_asp = $conexion->prepare($sql_aspirante);
    if ($stmt_asp) {
        $stmt_asp->bind_param("ss", $cedula, $periodo);
        $stmt_asp->execute();
        $resultado_asp = $stmt_asp->get_result();
        
        if ($resultado_asp->num_rows > 0) {
            $fila_asp = $resultado_asp->fetch_assoc();
            $titulosStr = $fila_asp['asp_titulos'];
            
            if (!empty($titulosStr)) {
                return parsearTitulos($titulosStr);
            }
        }
        $stmt_asp->close();
    }
    return null;
}

// ✅ NUEVA FUNCIÓN: Parsear títulos (similar a la función JavaScript)
function parsearTitulos($titulosStr) {
    $parsed = [
        'pregrado' => '',
        'especializacion' => '',
        'maestria' => '',
        'doctorado' => ''
    ];

    // Definir palabras clave para cada tipo de estudio (igual que en JavaScript)
    $keywords = [
        'doctorado' => ['DOCTORADO EN', 'DOCTOR', 'DOCTORA', 'PH.D.', 'PHD'],
        'maestria' => [
            'MAESTRIA EN', 'MAESTRÍA EN', 'MAGISTER EN', 'MASTER EN',
            'MAGISTER', 'MAESTRO', 'MASTER', 'MAGÍSTER', 'MAESTRÍA', 'MAESTRA', 'MÁSTER' 
        ],
        'especializacion' => ['ESPECIALIZACION EN', 'ESPECIALIZACIÓN EN', 'ESP.', 'ESPECIALISTA'],
        'pregrado' => [
            'LICENCIADO EN', 'LICENCIADA EN', 'LICENCIATURA EN', 
            'PROFESIONAL EN', 'INGENIERO EN', 'INGENIERA EN',
            'ABOGADO', 'ABOGADA', 'ADMINISTRADOR DE', 'ADMINISTRADORA DE', 
            'BIOLOGO', 'BIOLOGA', 'QUIMICO', 'QUÍMICO', 'CIRUJANO', 'ANTROPOLOGO', 
            'ENFERMERO', 'ENFERMERA', 'TECNICO EN', 'TÉCNICO EN', 'TECNOLOGO EN', 'TECNÓLOGO EN',
            'MEDICO', 'MÉDICO', 'MATEMATICO', 'MATEMÁTICO', 'CONTADOR', 'ECONOMISTA', 
            'BACHILLER', 'NORMALISTA', 'ARQUITECTO', 'ARQUITECTA', 'FILOSOFO', 'FILOSOFA', 
            'PSICOLOGO', 'PSICOLOGA', 'CITOHISTOTECNOLOGO', 'BACTERIOLOGO', 'BACTERIOLOGA',
            'LABORATORISTA', 'GEOTECNOLOGO', 'GEOTECNOLOGA', 'GEOGRAFO', 'GEOGRAFA', 
            'ODONTOLOGO', 'ODONTOLOGA', 'NUTRICIONISTA', 'FISIOTERAPEUTA',
            'COMUNICADOR', 'PERIODISTA', 'DISEÑADOR', 'SOCIOLOGO', 'HISTORIADOR',
            'POLITOLOGO', 'QUÍMICO FARMACÉUTICO', 'ZOOTECNISTA', 'AGRONOMO',
            'INGENIERO', 'INGENIERA',
            'LICENCIADO', 'LICENCIADA', 'LICENCIATURA',
            'TECNICO', 'TÉCNICO', 'TECNOLOGO', 'TECNÓLOGO',
            'ADMINISTRADOR', 'ADMINISTRADORA',
            'BACHILLER', 'NORMALISTA',
            'MÚSICA', 'ARTE',
            'GUIA'
        ]
    ];

    // Dividir el string por saltos de línea
    $titlesArray = preg_split('/[\r\n]+/', $titulosStr);

    foreach ($titlesArray as $title) {
        $trimmedTitle = trim($title);
        if (empty($trimmedTitle)) continue;

        $upperTrimmedTitle = strtoupper($trimmedTitle);

        // 1. Intentar detectar Doctorado (máxima prioridad)
        if (empty($parsed['doctorado'])) { 
            foreach ($keywords['doctorado'] as $keyword) {
                if (strpos($upperTrimmedTitle, $keyword) === 0) {
                    $parsed['doctorado'] = $trimmedTitle;
                    break; 
                }
            }
            if (!empty($parsed['doctorado'])) continue;
        }

        // 2. Intentar detectar Maestría
        if (empty($parsed['maestria'])) { 
            foreach ($keywords['maestria'] as $keyword) {
                if (strpos($upperTrimmedTitle, $keyword) === 0) {
                    $parsed['maestria'] = $trimmedTitle;
                    break; 
                }
            }
            if (!empty($parsed['maestria'])) continue;
        }

        // 3. Intentar detectar Especialización
        if (empty($parsed['especializacion'])) { 
            foreach ($keywords['especializacion'] as $keyword) {
                if (strpos($upperTrimmedTitle, $keyword) === 0) {
                    $parsed['especializacion'] = $trimmedTitle;
                    break; 
                }
            }
            if (!empty($parsed['especializacion'])) continue;
        }

        // 4. Intentar detectar Pregrado (última prioridad)
        if (empty($parsed['pregrado'])) { 
            foreach ($keywords['pregrado'] as $keyword) {
                if (strpos($upperTrimmedTitle, $keyword) === 0) {
                    $parsed['pregrado'] = $trimmedTitle;
                    break; 
                }
            }
        }
    }
    return $parsed;
}

// ... el resto del código FPDF permanece igual ...

// --- Clase FPDF extendida para Header y Footer ---
class PDF extends FPDF
{
    private $header_image_path = 'img/encabezadofor45.png';
    private $footer_image_path = 'img/icontec.png';
    // Ancho útil de la página (279.4mm - 15mm - 15mm)
    public $page_content_width = 249.4; // CAMBIADO A PUBLIC

    function Header()
    {
        // Imagen del encabezado
        // Ajusta X, Y y Width según necesidad para que tome el ancho completo
        $this->Image($this->header_image_path, 15, 2, 250); 
        $this->SetY(45); // Establecer la posición Y después del encabezado
    }

    function Footer()
{
    $this->SetY(-20);                       // Punto de partida del footer
    $this->SetFont('Arial', 'B', 8);

    // ───── Responsable y línea ─────
    $this->SetX(15);
    $this->Cell(0, 4, utf8_decode('Responsable:'), 0, 1, 'L');

    $this->SetX(15);
    $this->Cell(0, 4, '_________________________', 0, 1, 'L');

    // Guarda la coordenada Y antes de imprimir “Jefe de Departamento”
    $yJefe = $this->GetY();

    $this->SetX(15);
    $this->Cell(0, 4, utf8_decode('Jefe de Departamento'), 0, 0, 'L');

    // ───── SUPERPONER “ORIGINAL FIRMADO” ─────
    $this->SetFont('Arial', 'B', 10);       // Fuente más grande
    $this->SetTextColor(150, 0, 0);         // Rojo oscuro (elige el color que quieras)
    $this->SetXY(15, $yJefe - 5);           // Misma X, 1 mm arriba para cubrir el texto
    $this->Cell(0, 4, 'ORIGINAL FIRMADO', 0, 0, 'L');

    // ───── Imagen de firma (opcional) ─────
    $imgX = $this->GetPageWidth() - 35;
    $imgY = $this->GetY() - 0;
    $this->Image($this->footer_image_path, $imgX, $imgY, 25);
}


    // Método para dibujar un checkbox
    function Checkbox($x, $y, $checked) {
        $size = 4; // Tamaño del cuadro del checkbox
        $this->Rect($x, $y, $size, $size, 'D'); // Dibuja el cuadro (Draw)
        if ($checked) {
            $this->SetFont('ZapfDingbats', '', $size + 2); // Usa una fuente con checkmark (ZapfDingbats)
            $this->Text($x + 0.5, $y + $size - 0.5, utf8_decode('4')); // '4' en ZapfDingbats es el checkmark
            $this->SetFont('Arial', '', $this->FontSizePt); // Vuelve a la fuente anterior
        }
    }
}

// --- Configuración de FPDF ---
$pdf = new PDF('L', 'mm', 'Letter'); // 'L' para horizontal (Landscape), 'mm' para unidades en milímetros, 'Letter' para tamaño carta
$pdf->AddPage();

// Márgenes se configuran en el constructor o en AddPage
 $pdf->SetMargins(15, 15, 15); // Ya se hace en AddPage por defecto si no se especifican en el constructor
$pdf->SetAutoPageBreak(true, 20); // Auto salto de página con margen inferior de 20mm (espacio para el footer)

// --- Contenido del Documento ---

// Fuente para el contenido
$pdf->SetFont('Arial', '', 9);

// Ancho total disponible para las tablas
$availableTableWidth = $pdf->page_content_width; // 249.4 mm


        // Primer Tabla: Facultad, Departamento, Acta, Fecha
        // Posición inicial de la tabla (ajusta Y según donde termina tu encabezado)
        $pdf->SetY(40); 

        // Anchos de las columnas en mm (aproximados para que sumen el ancho disponible)
        // Total 249.4mm
        $colWidth_fac = $availableTableWidth * 0.24; // ~60mm
        $colWidth_dep = $availableTableWidth * 0.24; // ~60mm
        $colWidth_acta = $availableTableWidth * 0.24; // ~60mm
        $colWidth_fecha_total = $availableTableWidth * 0.28; // El resto del ancho para la fecha ~69.4mm
        $colWidth_fecha_dia = $colWidth_fecha_total / 3;
        $colWidth_fecha_mes = $colWidth_fecha_total / 3;
        $colWidth_fecha_anio = $colWidth_fecha_total / 3;

        $rowHeight = 6; // Altura de las filas de la tabla

        // Encabezados de la tabla
        $pdf->SetFillColor(242, 242, 242); // Color de fondo para encabezados
        $pdf->SetFont('Arial', 'B', 9); // Negrita para encabezados
        $pdf->Cell($colWidth_fac, $rowHeight * 2, utf8_decode('Facultad'), 1, 0, 'C', true); // rowspan 2
        $pdf->Cell($colWidth_dep, $rowHeight * 2, utf8_decode('Departamento'), 1, 0, 'C', true); // rowspan 2
        $pdf->Cell($colWidth_acta, $rowHeight * 2, utf8_decode('Número de Acta de Selección'), 1, 0, 'C', true); // rowspan 2

        $x_fecha_col = $pdf->GetX(); // Guardar posición X para los sub-encabezados de fecha
        $y_fecha_row = $pdf->GetY(); // Guardar posición Y para los sub-encabezados de fecha

        $pdf->Cell($colWidth_fecha_total, $rowHeight, utf8_decode('Fecha de Acta de Selección'), 1, 1, 'C', true); // colspan 3

        // Sub-encabezados de fecha
        $pdf->SetY($y_fecha_row + $rowHeight); // Volver a la línea para la segunda parte de los encabezados
        $pdf->SetX($x_fecha_col); // Mover a la posición guardada
        $pdf->Cell($colWidth_fecha_dia, $rowHeight, utf8_decode('Día'), 1, 0, 'C', true);
        $pdf->Cell($colWidth_fecha_mes, $rowHeight, utf8_decode('Mes'), 1, 0, 'C', true);
        $pdf->Cell($colWidth_fecha_anio, $rowHeight, utf8_decode('Año'), 1, 1, 'C', true); // Salto de línea al final

        // Datos de la tabla 1
        $pdf->SetFont('Arial', '', 9); // Volver a fuente normal
        $pdf->Cell($colWidth_fac, $rowHeight, utf8_decode($nombre_facultad), 1, 0, 'C'); // Facultad
        $pdf->Cell($colWidth_dep, $rowHeight, utf8_decode($nombre_departamento), 1, 0, 'C'); // Departamento
        $pdf->Cell($colWidth_acta, $rowHeight, utf8_decode($numero_acta), 1, 0, 'C'); // Número de Acta
        $pdf->Cell($colWidth_fecha_dia, $rowHeight, utf8_decode($day), 1, 0, 'C'); // Día
        $pdf->Cell($colWidth_fecha_mes, $rowHeight, utf8_decode($month), 1, 0, 'C'); // Mes
        $pdf->Cell($colWidth_fecha_anio, $rowHeight, utf8_decode($year), 1, 1, 'C'); // Año y salto de línea
// Segunda Tabla: Periodo académico
$pdf->Ln(2); // Salto de línea de 2mm entre tablas
$pdf->SetFont('Arial', 'B', 9);
// Establece el ancho para ambas columnas igual al ancho original de la primera columna
$fixedColWidth = $availableTableWidth * 0.18;

$pdf->Cell($fixedColWidth, $rowHeight, utf8_decode('Periodo académico'), 1, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell($fixedColWidth, $rowHeight, utf8_decode($periodo_consulta), 1, 1, 'L'); // La segunda columna ahora tiene el mismo ancho que la primera

// Tercera Tabla: Datos del Docente
// Tercera Tabla: Datos del Docente
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 9);

// Anchos de las columnas para la primera fila
$width_label_nombre = $availableTableWidth * (5/24);
$width_data_nombre = $availableTableWidth * (11/24);
$width_label_id = $availableTableWidth * (4/24);
$width_data_id = $availableTableWidth * (4/24);

$pdf->Cell($width_label_nombre, $rowHeight, utf8_decode('Nombre Docente'), 1, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell($width_data_nombre, $rowHeight, utf8_decode($nombre_solicitante), 1, 0, 'L');
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($width_label_id, $rowHeight, utf8_decode('Identificación'), 1, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell($width_data_id, $rowHeight, utf8_decode($cedula_solicitante), 1, 1, 'L');

// Anchos de las columnas para la segunda fila (Correo Electrónico)
$width_label_email = $availableTableWidth * (5/24);
$width_data_email = $availableTableWidth * (19/24); // El resto del ancho (24/24 - 5/24 = 19/24)

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($width_label_email, $rowHeight, utf8_decode('Correo Electrónico'), 1, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell($width_data_email, $rowHeight, utf8_decode($email_solicitante), 1, 1, 'L');

// Cuarta Tabla: Tipo Vinculación
$pdf->Ln(2);

// Calcula el nuevo ancho total que esta tabla debe ocupar (3/5 del ancho disponible)
$table4_total_width = $availableTableWidth * (3/5);

// Ahora, divide este nuevo ancho total entre 5 para obtener la base de las columnas
$colWidth_vinculacion_base = $table4_total_width / 5; // Cada "quinto" será ahora un quinto de los 3/5 del ancho disponible

$pdf->SetFillColor(242, 242, 242);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($colWidth_vinculacion_base * 2, $rowHeight, utf8_decode('Ocasional'), 1, 0, 'C', true); // Colspan 2
$pdf->Cell($colWidth_vinculacion_base * 2, $rowHeight, utf8_decode('Planta'), 1, 0, 'C', true); // Colspan 2
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, utf8_decode('Cátedra'), 1, 1, 'C', true); // Colspan 1

// Segunda fila de encabezados
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, utf8_decode('MT'), 1, 0, 'C', true);
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, utf8_decode('TC'), 1, 0, 'C', true);
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, utf8_decode('MT'), 1, 0, 'C', true);
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, utf8_decode('TC'), 1, 0, 'C', true);
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, utf8_decode('Horas semana'), 1, 1, 'C', true);

// Datos de la tabla 4 con checkboxes
$pdf->SetFont('Arial', '', 9);
$checkbox_size = 4;
// Recalcula los offsets de los checkboxes para que sigan centrados en las nuevas columnas más estrechas
$checkbox_offset_x = ($colWidth_vinculacion_base - $checkbox_size) / 2;
$checkbox_offset_y = ($rowHeight - $checkbox_size) / 2;

// Ocasional MT
$x = $pdf->GetX(); $y = $pdf->GetY();
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, '', 1, 0, 'C');
$pdf->Checkbox($x + $checkbox_offset_x, $y + $checkbox_offset_y, ($vinculacion_ocasional === 'MT' || $vinculacion_ocasional_reg === 'MT'));

// Ocasional TC
$x = $pdf->GetX(); $y = $pdf->GetY();
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, '', 1, 0, 'C');
$pdf->Checkbox($x + $checkbox_offset_x, $y + $checkbox_offset_y, ($vinculacion_ocasional === 'TC' || $vinculacion_ocasional_reg === 'TC'));

// Planta MT
$x = $pdf->GetX(); $y = $pdf->GetY();
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, '', 1, 0, 'C');
// Asumiendo que no hay datos de Planta MT en tus variables, siempre false
$pdf->Checkbox($x + $checkbox_offset_x, $y + $checkbox_offset_y, false);

// Planta TC
$x = $pdf->GetX(); $y = $pdf->GetY();
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, '', 1, 0, 'C');
// Asumiendo que no hay datos de Planta TC en tus variables, siempre false
$pdf->Checkbox($x + $checkbox_offset_x, $y + $checkbox_offset_y, false);

// Horas semana (Cátedra)
$pdf->Cell($colWidth_vinculacion_base, $rowHeight, ($tipo_docente === 'Catedra' ? ($horas_p ?? 0) + ($horas_r ?? 0) : ''), 1, 1, 'C');

// Quinta Tabla: Requisitos de estudio y Experiencia
//$rowHeight = 9; // Aumentada la altura estándar de fila de 8 a 9
// $availableTableWidth = 190; // Ancho total disponible para la tabla

// Quinta Tabla: Requisitos de estudio y Experiencia
$pdf->Ln(2);

// Definir los anchos de columna
$colWidth_estudio = $availableTableWidth * 0.55;
$colWidth_tipo_exp = $availableTableWidth * 0.225;
$colWidth_anios_exp = $availableTableWidth * 0.225;
$ancho_disponible = $colWidth_tipo_exp + $colWidth_anios_exp;

$x_start_table = $pdf->GetX();
$y_header_row1_start = $pdf->GetY();

// --- MEJORA PARA "EXPERIENCIA" ---
$pdf->SetFont('Arial', 'B', 9);
$texto_experiencia = utf8_decode('Experiencia');

// Calcular altura necesaria para "Experiencia"
$num_lineas = ceil($pdf->GetStringWidth($texto_experiencia) / $ancho_disponible);
$altura_necesaria = $rowHeight * max(2, $num_lineas); // Mínimo 2 filas

// Fila de Encabezados 1
$pdf->Cell($colWidth_estudio, $altura_necesaria, utf8_decode('Requisitos de estudio'), 1, 0, 'C', true);
$pdf->Cell($ancho_disponible, $altura_necesaria, $texto_experiencia, 'LTR', 1, 'C', true);

// Fila de Encabezados 2
$pdf->SetXY($x_start_table, $y_header_row1_start + $altura_necesaria);
$pdf->Cell($colWidth_estudio, $rowHeight, utf8_decode('Título(s)'), 1, 0, 'C', true);
$pdf->Cell($ancho_disponible, $rowHeight, '', 'LRB', 1, 'C', true);

// --- Fin del bloque de encabezados ---

// Datos de la tabla
$pdf->SetFont('Arial', '', 9);
$line_height_multicell = $rowHeight;

// Pregrado(s)
$current_x_pregrado = $pdf->GetX();
$current_y_pregrado = $pdf->GetY();
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Pregrado(s): ' . $pregrado), 1, 'L');
$new_y_after_pregrado_multicell = $pdf->GetY();
$pdf->SetXY($current_x_pregrado + $colWidth_estudio, $current_y_pregrado);

$pdf->Cell($colWidth_tipo_exp, $line_height_multicell, utf8_decode('Docente:'), 1, 0, 'L');
$pdf->Cell($colWidth_anios_exp, $line_height_multicell, utf8_decode($experiencia_docente), 1, 1, 'L');
$pdf->SetY(max($new_y_after_pregrado_multicell, $pdf->GetY()));

// Especialización(s)
$current_x_especializacion = $pdf->GetX();
$current_y_especializacion = $pdf->GetY();
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Especialización(s): ' . $especializacion), 1, 'L');
$new_y_after_especializacion_multicell = $pdf->GetY();
$pdf->SetXY($current_x_especializacion + $colWidth_estudio, $current_y_especializacion);

$pdf->Cell($colWidth_tipo_exp, $line_height_multicell, utf8_decode('Profesional:'), 1, 0, 'L');
$pdf->Cell($colWidth_anios_exp, $line_height_multicell, utf8_decode($experiencia_profesional), 1, 1, 'L');
$pdf->SetY(max($new_y_after_especializacion_multicell, $pdf->GetY()));

// Maestría(s)
$current_x_maestria = $pdf->GetX();
$current_y_maestria = $pdf->GetY();
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Maestría(s): ' . $maestria), 1, 'L');
$y_after_maestria_multicell = $pdf->GetY();

// Experiencia Profesional
$pdf->SetXY($current_x_maestria + $colWidth_estudio, $current_y_maestria);
$pdf->Cell($colWidth_tipo_exp, $line_height_multicell, utf8_decode('Profesional:'), 1, 0, 'L');
$pdf->Cell($colWidth_anios_exp, $line_height_multicell, utf8_decode($experiencia_profesional), 1, 1, 'L');
$y_after_profesional_exp = $pdf->GetY();

// Asegurar posición
$y_start_doctorado_otro_block = max($y_after_maestria_multicell, $y_after_profesional_exp);
$pdf->SetY($y_start_doctorado_otro_block);

// Doctorado(s) y Otro estudio
$original_x_calc = $pdf->GetX();
$original_y_calc = $pdf->GetY();

// Calcular alturas
$temp_y_before_doctorado_calc = $pdf->GetY();
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Doctorado(s): ' . $doctorado), 0, 'L');
$h_doctorado = $pdf->GetY() - $temp_y_before_doctorado_calc;

$temp_y_before_otro_calc = $pdf->GetY();
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Otro: ' . $otro_estudio), 0, 'L');
$h_otro = $pdf->GetY() - $temp_y_before_otro_calc;

$pdf->SetXY($original_x_calc, $original_y_calc);

// Calcular altura total
$total_height_for_otra_exp = $h_doctorado + $h_otro;

// Dibujar experiencia "Otra"
$pdf->SetXY($original_x_calc + $colWidth_estudio, $original_y_calc);
$pdf->Cell($colWidth_tipo_exp, $total_height_for_otra_exp, utf8_decode('Otra:'), 1, 0, 'L');
$pdf->Cell($colWidth_anios_exp, $total_height_for_otra_exp, utf8_decode($otra_experiencia), 1, 1, 'L');
$y_after_otra_exp_drawn = $pdf->GetY();

// Dibujar Doctorado y Otro estudio
$pdf->SetXY($original_x_calc, $original_y_calc);
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Doctorado(s): ' . $doctorado), 1, 'L');
$pdf->SetX($original_x_calc);
$pdf->MultiCell($colWidth_estudio, $line_height_multicell, utf8_decode('Otro: ' . $otro_estudio), 1, 'L');

// Posición final
$pdf->SetY(max($y_after_otra_exp_drawn, $pdf->GetY()));

// Sexta Tabla: Vinculación Anterior y Anexos HV
$pdf->Ln(2);

// Ajustar anchos para que la tabla ocupe el 100% del ancho disponible
$colWidth_q = $availableTableWidth * 0.80;   // 70% para la pregunta
$colWidth_si = $availableTableWidth * 0.05;  // 5% para "SI"
$colWidth_check1 = $availableTableWidth * 0.05; // 5% para checkbox SI
$colWidth_no = $availableTableWidth * 0.05;  // 5% para "NO"
$colWidth_check2 = $availableTableWidth * 0.05; // 5% para checkbox NO

// Row 1: Ha estado vinculado con la Universidad del Cauca
$pdf->SetFont('Arial', '', 9); // Fuente normal tamaño 9
$pdf->Cell($colWidth_q, $rowHeight, utf8_decode('El Docente ha estado vinculado con la Universidad del Cauca:'), 1, 0, 'L');
$pdf->Cell($colWidth_si, $rowHeight, utf8_decode('SI'), 1, 0, 'C');

// Dibujar checkbox para SI
$check_si = (function_exists('existeSolicitudAnterior') && existeSolicitudAnterior($cedula_solicitante, $anio_semestre)) ? 'X' : '';
$pdf->Cell($colWidth_check1, $rowHeight, $check_si, 1, 0, 'C');

$pdf->Cell($colWidth_no, $rowHeight, utf8_decode('NO'), 1, 0, 'C');

// Dibujar checkbox para NO
$check_no = (!(function_exists('existeSolicitudAnterior') || !existeSolicitudAnterior($cedula_solicitante, $anio_semestre))) ? 'X' : '';
$pdf->Cell($colWidth_check2, $rowHeight, $check_no, 1, 1, 'C');

// Row 2: Se anexa historia laboral (hoja de vida)
$pdf->Cell($colWidth_q, $rowHeight, utf8_decode('Se anexa historia laboral (hoja de vida):'), 1, 0, 'L');
$pdf->Cell($colWidth_si, $rowHeight, utf8_decode('SI'), 1, 0, 'C');

// Dibujar checkbox para SI
$check_si_hv = ($anexa_hv_nuevo === 'si') ? 'X' : '';
$pdf->Cell($colWidth_check1, $rowHeight, $check_si_hv, 1, 0, 'C');

$pdf->Cell($colWidth_no, $rowHeight, utf8_decode('NO'), 1, 0, 'C');

// Dibujar checkbox para NO
$check_no_hv = ($anexa_hv_nuevo === 'no') ? 'X' : '';
$pdf->Cell($colWidth_check2, $rowHeight, $check_no_hv, 1, 1, 'C');

// Séptima Tabla: Anexa Actualización y Observaciones
$pdf->Ln(2);
$colWidth_anexa_q = $availableTableWidth * 0.4;
$colWidth_anexa_si_no = $availableTableWidth * 0.05;
$colWidth_anexa_check = $availableTableWidth * 0.05;
$colWidth_cual = $availableTableWidth - $colWidth_anexa_q - ($colWidth_anexa_si_no + $colWidth_anexa_check) * 2;

// Row 1: Anexa actualización - CORREGIDO
$pdf->SetFont('Arial', '', 9);
$pdf->Cell($colWidth_anexa_q, $rowHeight, utf8_decode('Anexa actualización:'), 1, 0, 'L');

// Celda SI - ahora centrada
$pdf->Cell($colWidth_anexa_si_no, $rowHeight, utf8_decode('SI'), 1, 0, 'C');  // Cambiado de 'R' a 'C'
$x = $pdf->GetX(); $y = $pdf->GetY();
$pdf->Cell($colWidth_anexa_check, $rowHeight, '', 1, 0, 'C');
$pdf->Checkbox($x + $checkbox_offset_x-7, $y + $checkbox_offset_y, ($actualiza_hv_antiguo === 'si'));

// Celda NO (mantenemos centrado)
$pdf->Cell($colWidth_anexa_si_no, $rowHeight, utf8_decode('NO'), 1, 0, 'C');
$x = $pdf->GetX(); $y = $pdf->GetY();
$pdf->Cell($colWidth_anexa_check, $rowHeight, '', 1, 0, 'C');
$pdf->Checkbox($x + $checkbox_offset_x-7, $y + $checkbox_offset_y, ($actualiza_hv_antiguo === 'no'));

// Celda Cuál - con fuente tamaño 9
$pdf->SetFont('Arial', '', 9);  // Fuerza tamaño 9
$pdf->Cell($colWidth_cual, $rowHeight, utf8_decode('Cuál:'), 1, 1, 'L');

// Row 2: Observaciones
$pdf->SetFont('Arial', 'B', 9);
$pdf->MultiCell($availableTableWidth, 20, utf8_decode('Observaciones:'), 1, 'L');// --- Output del PDF ---
$file_name = 'FOR-45_' . str_replace(' ', '_', $nombre_solicitante) . '_' . str_replace(' ', '_', $periodo_consulta) . '_' . str_replace(' ', '_', $nombre_departamento) . '.pdf';
$pdf->Output('I', $file_name); // 'I' para mostrar en el navegador, 'D' para forzar descarga

?>