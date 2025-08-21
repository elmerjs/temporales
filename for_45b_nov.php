<?php

require_once 'vendor/autoload.php';
require 'funciones.php';
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos
use PhpOffice\PhpWord\Style\Language;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Style\Cell;
use PhpOffice\PhpWord\Style\Border;
use PhpOffice\PhpWord\Style\TableWidth;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

// *** CAMBIO CLAVE: Define la tabla de novedades ***
// Puedes cambiar 'solicitudes_novedades' a 'solicitudes_working_copy'
// si esa es la tabla que usas para las novedades.
$tabla_novedades = 'solicitudes_working_copy'; // O 'solicitudes_working_copy'
// *************************************************

// Obtener los valores de las variables (pueden ser null si no están presentes)
$id_solicitud = $_GET['modal_solicitud_id'];
$departamento_id = $_GET['modal_depto_id'];
$anio_semestre = $_GET['modal_anio_sem'];
$cedula_profesor = $_GET['modal_cedula_prof'];

$numero_acta = $_GET['numero_acta'];
  $fecha_acta = isset($_GET['fecha_actab']) ? $_GET['fecha_actab'] : null; // Asegúrate de usar el mismo 'name'
list($year, $month, $day) = explode('-', $fecha_acta);

  $numero_acta_bd = obtener_numero_acta($anio_semestre, $departamento_id);
/*
// Este bloque de código está comentado en el original, así que lo mantengo así.
$sql_update = '';

if ($numero_acta_bd === 0)  { // SI NO HAY DATOS DE ACTA EN DEPTOPERIODO
    $sql_update = "UPDATE depto_periodo
                       SET dp_acta_periodo  = '$numero_acta', dp_fecha_acta ='$fecha_acta'
                      WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
$dato_acta = "ok";
} else {$dato_acta = "falla";}

// Verificamos que $sql_update_fac no esté vacío antes de ejecutar la consulta
if (!empty($sql_update)) {
    if ($con->query($sql_update) === TRUE) {
        // La consulta se ejecutó correctamente
        // echo "Registro actualizado correctamente";
    } else {
        // En caso de error en la ejecución
     //   echo "Error al ejecutar la consulta: " . $con->error;
    }
} else {
   // echo "No se definió la consulta SQL.";
}
*/

// Variables para almacenar los resultados
$nombre_facultad = null;
$nombre_departamento = null;
$periodo_consulta = null;
$id_depto_periodo = null;
$numero_acta_db = null; // Renombrado para evitar conflicto con $_GET['numero_acta']
$fecha_acta_db = null;   // Renombrado para evitar conflicto con $_GET['fecha_acta']
$nombre_solicitante = null;
$cedula_solicitante = null;
$email_solicitante = null;
$tipo_docente = null;
$vinculacion_ocasional = null;
$vinculacion_ocasional_reg = null;
$horas_p = null;
$horas_r = null;
$anexa_hv_nuevo = null;
$actualiza_hv_antiguo = null;
$error_consulta = null;
// Nuevos campos recibidos del modal
$pregrado = $_GET['pregrado'] ?? null;
$especializacion = $_GET['especializacion'] ?? null;
$maestria = $_GET['maestria'] ?? null;
$doctorado = $_GET['doctorado'] ?? null;
$otro_estudio = $_GET['otro_estudio'] ?? null;
$experiencia_docente = $_GET['experiencia_docente'] ?? null;
$experiencia_profesional = $_GET['experiencia_profesional'] ?? null;
$otra_experiencia = $_GET['otra_experiencia'] ?? null;

// *** CAMBIO CLAVE: Actualizar los nuevos campos en la tabla de novedades ***
$sql_update_solicitud = "UPDATE {$tabla_novedades} SET
    pregrado = ?,
    especializacion = ?,
    maestria = ?,
    doctorado = ?,
    otro_estudio = ?,
    experiencia_docente = ?,
    experiencia_profesional = ?,
    otra_experiencia = ?
    WHERE id_solicitud = ?";

$stmt_update = $con->prepare($sql_update_solicitud);
if ($stmt_update) {
    $stmt_update->bind_param("ssssssssi",
        $pregrado,
        $especializacion,
        $maestria,
        $doctorado,
        $otro_estudio,
        $experiencia_docente,
        $experiencia_profesional,
        $otra_experiencia,
        $id_solicitud);
    $stmt_update->execute();
    $stmt_update->close();
}

// Verificar si las variables están definidas antes de usarlas
if (isset($anio_semestre) && isset($departamento_id) && isset($id_solicitud)) {
    // *** CAMBIO CLAVE: Consultar la tabla de novedades ***
    $sql = "SELECT
                facultad.Nombre_fac_minb,
                deparmanentos.depto_nom_propio,
                depto_periodo.periodo,
                depto_periodo.fk_depto_dp,
                depto_periodo.dp_acta_periodo,
                depto_periodo.dp_fecha_acta,
                {$tabla_novedades}.nombre,
                {$tabla_novedades}.cedula,
                tercero.email,
                {$tabla_novedades}.tipo_docente,
                {$tabla_novedades}.tipo_dedicacion AS vincul_ocasional,
                {$tabla_novedades}.tipo_dedicacion_r AS vicul_ocasional_reg,
                {$tabla_novedades}.horas,
                {$tabla_novedades}.horas_r,
                {$tabla_novedades}.anexa_hv_docente_nuevo,
                {$tabla_novedades}.actualiza_hv_antiguo,
                {$tabla_novedades}.pregrado,
                {$tabla_novedades}.especializacion,
                {$tabla_novedades}.maestria,
                {$tabla_novedades}.doctorado,
                {$tabla_novedades}.otro_estudio,
                {$tabla_novedades}.experiencia_docente,
                {$tabla_novedades}.experiencia_profesional,
                {$tabla_novedades}.otra_experiencia
            FROM depto_periodo
            JOIN deparmanentos ON deparmanentos.PK_DEPTO = depto_periodo.fk_depto_dp
            JOIN facultad ON facultad.PK_FAC = deparmanentos.FK_FAC
            JOIN {$tabla_novedades} ON ({$tabla_novedades}.anio_semestre = depto_periodo.periodo AND {$tabla_novedades}.departamento_id = depto_periodo.fk_depto_dp)
            JOIN tercero ON tercero.documento_tercero = {$tabla_novedades}.cedula
            WHERE depto_periodo.periodo = ?
              AND depto_periodo.fk_depto_dp = ?
              AND {$tabla_novedades}.id_solicitud = ?";

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
            $id_depto_periodo = $fila['fk_depto_dp'];
            $numero_acta_db = $fila['dp_acta_periodo']; // Usar desde DB
            $fecha_acta_db = $fila['dp_fecha_acta'];   // Usar desde DB
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
            // Los campos de pregrado, especialización, etc. ya están en $fila si la consulta fue exitosa
            // No es necesario re-asignarlos si ya se actualizó la BD.
        }

        $stmt->close();
    } else {
        $error_consulta = "Error al preparar la consulta: " . $con->error;
    }

    $con->close();
} else {
    $error_consulta = "Las variables \$anio_semestre, \$departamento_id y \$id_solicitud no están definidas.";
}


// Crear instancia de PHPWord
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->getSettings()->setThemeFontLang(new Language('es-CO'));

// Configurar la sección en **horizontal** (Landscape)
$section = $phpWord->addSection([
    'orientation' => 'landscape',
    'pageSizeW' => 15840, // 11 pulgadas
    'pageSizeH' => 12240, // 8.5 pulgadas
   'marginLeft' => 850, // Margen izquierdo = 1.5 cm (~850 twips)
'marginRight' => 1134, // Margen derecho = 1.5 cm
    'marginTop' => 300,  // Margen superior (casi al borde)
    'marginBottom' => 300, // Espacio para el pie de págin
        "footerHeight" => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0.5)

]);

// 📌 Agregar la imagen del **encabezado**
$section->addImage('img/encabezadofor45.png', [
    'width' => 695,  // Ajusta el ancho para que abarque toda la página
   // 'height' => 500,   // Ajusta la altura según necesites
    'alignment' => Jc::CENTER
]);

// 📌 Agregar Pie de Página
$footer = $section->addFooter();
$footer->addImage('img/icontec.png', [
    'width' => 80,  // Ancho del logo
    'alignment' => Jc::RIGHT,  // Alineado al extremo derecho
    'posVerticalRelTo' => 'margin', // Posición relativa al margen
    'posVertical' => 'bottom', // ✅ Corrección: Se usa 'bottom' en lugar de un número inválido
    'wrappingStyle' => 'behind' // Evita que la imagen afecte el texto
]);


$styleTable = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 10,
    'alignment' => Jc::CENTER
];
$phpWord->addTableStyle('tablaActa', $styleTable);
// Crear la tabla con autoajuste al ancho de la ventana
$tableStyle = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 50,
    'alignment' => Jc::LEFT,
    'width' => TblWidth::PERCENT,
    'unit' => TblWidth::PERCENT,
    'percentWidth' => 100
];
$table = $section->addTable($tableStyle);

// Estilo de párrafo "sin espaciado"
$paragraphStyle = new Paragraph([
    'spacing' => 0, // Espaciado entre líneas = 0
    'spaceBefore' => 0, // Espaciado antes del párrafo = 0
    'spaceAfter' => 0, // Espaciado después del párrafo = 0
        'size' => 9 // Tamaño de fuente 9

]);

// Fila de encabezados
$table->addRow(250, ['exactHeight' => true]);
$table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT])->addText('Facultad', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT])->addText('Departamento', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT])->addText('Número de Acta de Selección', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$cellFecha = $table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT, 'gridSpan' => 3]);
$cellFecha->addText('Fecha de Acta de Selección', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);

// Fila 2 (subencabezados para Día, Mes, Año)
$table->addRow(250, ['exactHeight' => true]);
$cellFacultad = $table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT, 'vMerge' => 'restart'])->addText($nombre_facultad, [], $paragraphStyle);
$cellDepartamento = $table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT, 'vMerge' => 'restart'])->addText($nombre_departamento, [], $paragraphStyle);
$paragraphStyle = ['alignment' => Jc::CENTER];

// Añadir la celda a la tabla con alineación vertical centrada
$cellNumeroActa = $table->addCell(
    25,
    [
        'width' => 25,
        'unit' => TblWidth::PERCENT,
        'vMerge' => 'restart',
        'valign' => VerticalJc::CENTER, // Alineación vertical centrada
    ]
);

// Añadir el texto a la celda con el estilo de párrafo definido
$cellNumeroActa->addText($numero_acta, [], $paragraphStyle); // Usa $numero_acta del GET
$table->addCell(8.33, ['width' => 8.33, 'unit' => TblWidth::PERCENT])->addText('Día', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$table->addCell(8.33, ['width' => 8.33, 'unit' => TblWidth::PERCENT])->addText('Mes', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$table->addCell(8.33, ['width' => 8.33, 'unit' => TblWidth::PERCENT])->addText('Año', ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);

// Fila 3 (datos correspondientes)
$table->addRow(250, ['exactHeight' => true]);
$table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT, 'vMerge' => 'continue']);
$table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT, 'vMerge' => 'continue']);
$table->addCell(25, ['width' => 25, 'unit' => TblWidth::PERCENT, 'vMerge' => 'continue']);
$table->addCell(8.33, ['width' => 8.33, 'unit' => TblWidth::PERCENT])->addText($day, [], $paragraphStyle); // Usa $day del GET
$table->addCell(8.33, ['width' => 8.33, 'unit' => TblWidth::PERCENT])->addText($month, [], $paragraphStyle); // Usa $month del GET
$table->addCell(8.33, ['width' => 8.33, 'unit' => TblWidth::PERCENT])->addText($year, [], $paragraphStyle); // Usa $year del GET

// Agregar un segunda tabla periodo
$section->addText('.', ['size' => 5, 'color' => 'FFFFFF'], ['spaceAfter' => 50]); // Reduce el espacio después

// Agregar una nueva tabla para el periodo académico
$tablePeriodo = $section->addTable([
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 50,
    'alignment' => Jc::LEFT // Alineación a la izquierda
]);

// Estilos de las celdas
$cellStyle = [
    'width' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(4), // Ancho de 3 cm
    'valign' => 'center' // Alinear verticalmente en el centro
];

// Estilo de párrafo sin espaciado
$paragraphStyle = [
    'spacing' => 0,
    'spaceBefore' => 0,
    'spaceAfter' => 0,    'size' => 9 // Tamaño de fuente 9

];

// Agregar la fila
$tablePeriodo->addRow(250); // Altura reducida de la fila

// Agregar las celdas
$tablePeriodo->addCell(null, $cellStyle)->addText('Periodo académico', ['bold' => true], $paragraphStyle);
$tablePeriodo->addCell(null, $cellStyle)->addText($periodo_consulta, [], $paragraphStyle);



//tabla 3 nombres docente
// Agregar un salto de línea con menor espacio
$section->addText('.', ['size' => 5, 'color' => 'FFFFFF'], ['spaceAfter' => 50]);

// Crear la tercera tabla con autoajuste a la ventana
$tableDocente = $section->addTable([
    'alignment' => Jc::LEFT,
    'width' => TblWidth::PERCENT, // Ancho de la tabla en porcentaje
    'unit' => TblWidth::PERCENT,
    'percentWidth' => 100, // La tabla ocupa el 100% del ancho
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 50
]);

// Definir anchos de columna en porcentaje
$anchoCol1 = 20; // 20% del ancho de la tabla
$anchoCol2 = 45; // 45% del ancho de la tabla
$anchoCol3 = 15; // 15% del ancho de la tabla
$anchoCol4 = 20; // 20% del ancho de la tabla

// Primera fila con 4 columnas
$tableDocente->addRow(200); // Altura de la fila
$tableDocente->addCell($anchoCol1, ['width' => $anchoCol1, 'unit' => TblWidth::PERCENT])
    ->addText('Nombre Docente', ['bold' => true], $paragraphStyle);
$tableDocente->addCell($anchoCol2, ['width' => $anchoCol2, 'unit' => TblWidth::PERCENT])
    ->addText($nombre_solicitante, [], $paragraphStyle);
$tableDocente->addCell($anchoCol3, ['width' => $anchoCol3, 'unit' => TblWidth::PERCENT])
    ->addText('Identificación', ['bold' => true], $paragraphStyle);
$tableDocente->addCell($anchoCol4, ['width' => $anchoCol4, 'unit' => TblWidth::PERCENT])
    ->addText($cedula_solicitante, [], $paragraphStyle);

// Segunda fila con 2 columnas (combinando columnas 2, 3 y 4)
$tableDocente->addRow(200);
$tableDocente->addCell($anchoCol1, ['width' => $anchoCol1, 'unit' => TblWidth::PERCENT])
    ->addText('Correo Electrónico', ['bold' => true], $paragraphStyle);

$cellCorreo = $tableDocente->addCell(80, ['width' => 80, 'unit' => TblWidth::PERCENT, 'gridSpan' => 3]); // Combina las 3 columnas restantes
$cellCorreo->addText($email_solicitante, [], $paragraphStyle);


// cuarta tabla  tipo viinculacion xx
$section->addText('.', ['size' => 5, 'color' => 'FFFFFF'], ['spaceAfter' => 50]); // Reduce el espacio después
// Crear la tabla alineada a la izquierda (no ajustada al ancho de la ventana)
$tableContrato = $section->addTable([
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 50,
    'alignment' => Jc::LEFT // Alineación a la izquierda
]);

// Estilos de las celdas
$cellStyle = [
    'width' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(3), // Ancho ajustado
    'valign' => 'center' // Alinear verticalmente en el centro
];

// Estilo de párrafo sin espaciado
$paragraphStyle = [
    'spacing' => 0,
    'spaceBefore' => 0,
    'spaceAfter' => 0,    'size' => 9 // Tamaño de fuente 9

];

// Primera fila (encabezado con negrilla)
$tableContrato->addRow(150); // Altura reducida de la fila
$cell1 = $tableContrato->addCell(null, ['gridSpan' => 2, 'valign' => 'center']);
$cell1->addText('                    Ocasional', ['bold' => true], $paragraphStyle);

$cell2 = $tableContrato->addCell(null, ['gridSpan' => 2, 'valign' => 'center']);
$cell2->addText('                       Planta', ['bold' => true], $paragraphStyle);

$tableContrato->addCell(null, $cellStyle)->addText('      Cátedra', ['bold' => true], $paragraphStyle);

// Segunda fila (MT, TC, etc.)
$tableContrato->addRow(150);
$tableContrato->addCell(null, $cellStyle)->addText('           MT', [], $paragraphStyle);
$tableContrato->addCell(null, $cellStyle)->addText('           TC', [], $paragraphStyle);
$tableContrato->addCell(null, $cellStyle)->addText('           MT', [], $paragraphStyle);
$tableContrato->addCell(null, $cellStyle)->addText('           TC', [], $paragraphStyle);
$tableContrato->addCell(null, $cellStyle)->addText(' Horas semana', [], $paragraphStyle);

// Tercera fila (checkbox y horas)
$tableContrato->addRow(150);
// Determinar el símbolo a mostrar según las variables
$simboloCheckbox = ($vinculacion_ocasional === 'MT' || $vinculacion_ocasional_reg === 'MT') ? '            ☑' : '            ☐';

// Agregar la celda con el símbolo correspondiente
$tableContrato->addCell(null, $cellStyle)->addText($simboloCheckbox, [], $paragraphStyle);

$simboloCasilla = ($vinculacion_ocasional === 'TC' || $vinculacion_ocasional_reg === 'TC') ? '            ☑' : '            ☐';

// Agregar la celda a la tabla con el símbolo correspondiente
$tableContrato->addCell(null, $cellStyle)->addText($simboloCasilla, [], $paragraphStyle);
$tableContrato->addCell(null, $cellStyle)->addText('            ☐', [], $paragraphStyle);
$tableContrato->addCell(null, $cellStyle)->addText('            ☐', [], $paragraphStyle);
if ($tipo_docente === 'Catedra') {
    // Calcular la suma de horas
    $suma_horas = $horas_p + $horas_r;
    // Convertir la suma a cadena para su visualización
    $texto_celda = (string) $suma_horas;
} else {
    // Si no es "Catedra", dejar la celda vacía o con un texto específico
    $texto_celda = ''; // O puedes asignar otro valor, por ejemplo: 'N/A'
}

// Añadir la celda a la tabla con el contenido determinado
$tableContrato->addCell(null, $cellStyle)->addText($texto_celda, [], $paragraphStyle);

// tabla  requisitos de estudiio


//tabl  requisitos  xxx
$section->addText('.', ['size' => 5, 'color' => 'FFFFFF'], ['spaceAfter' => 50]);

// Estilos de la tabla
// Estilos de la tabla justificada a la ventana (100% del ancho)
$tableStyle2 = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 0,
    'alignment' => Jc::LEFT,
    'width' => TblWidth::PERCENT,
    'unit' => TblWidth::PERCENT,
    'percentWidth' => 100
];

$phpWord->addTableStyle('tablaRequisitos', $tableStyle2);
$table2 = $section->addTable('tablaRequisitos');

// Definir ancho de columnas en porcentaje
$col1Width = 60;
$col2Width = 20;
$col3Width = 20;

// Estilo de párrafo "sin espaciado"
$paragraphStyle = new Paragraph([
    'spacing' => 0,
    'spaceBefore' => 0,
    'spaceAfter' => 0,    'size' => 9 // Tamaño de fuente 9

]);

// Altura fija para todas las filas
$rowHeight = 250;

// Fila 1 (Requisitos de estudio + Experiencia)
$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$cell1 = $row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT, 'valign' => 'center']);
$cell1->addText("Requisitos de estudio", ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$cell2 = $row->addCell($col2Width + $col3Width, ['width' => $col2Width + $col3Width, 'unit' => TblWidth::PERCENT, 'gridSpan' => 2, 'valign' => 'center', 'vMerge' => 'restart']); // Iniciar combinación vertical
$cell2->addText("Experiencia", ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);

// Fila 2 (Título(s) + Celda combinada con Experiencia)
$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$cell1 = $row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT, 'valign' => 'center']);
$cell1->addText("Título(s)", ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$row->addCell(null, ['gridSpan' => 2, 'vMerge' => 'continue']); // Continuar combinación vertical

// ... [código anterior]

// Fila 3 (Pregrado + Tipo + Años)
$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("Pregrado(s): " . ($pregrado ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT])->addText("Tipo", ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);
$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT])->addText("Años", ['bold' => true], ['alignment' => Jc::CENTER], $paragraphStyle);

// Fila 4 (Especialización + Experiencia Docente)
$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("Especialización(s): " . ($especializacion ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT])->addText("Docente: ", [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT])->addText( ($experiencia_docente ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);

// Fila 5 (Maestría + Experiencia Profesional)
$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("Maestría(s): " . ($maestria ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT])->addText("Profesional: ", [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT])->addText(($experiencia_profesional ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);

// Fila 6 y 7 combinadas (Doctorado + Otra Experiencia)
$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("Doctorado(s): " . ($doctorado ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT, 'vMerge' => 'restart'])->addText("Otra: ", [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT, 'vMerge' => 'restart'])->addText(($otra_experiencia ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);

$row = $table2->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("Otro: " . ($otro_estudio ?? ''), [], ['alignment' => Jc::LEFT], $paragraphStyle);
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT, 'vMerge' => 'continue']);
$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT, 'vMerge' => 'continue']);

// ... [código posterior]
//tabla   final.::
$section->addText('.', ['size' => 5, 'color' => 'FFFFFF'], ['spaceAfter' => 50]);
// Estilos de la tabla justificada a la ventana (100% del ancho)
$tableStyle2 = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 0,
    'alignment' => Jc::LEFT,
    'width' => TblWidth::PERCENT,
    'unit' => TblWidth::PERCENT,
    'percentWidth' => 100
];

$phpWord->addTableStyle('tablaFinal', $tableStyle2);
$table = $section->addTable('tablaFinal');

// Definir anchos de columnas en porcentaje
$col1Width = 85;
$col2Width = 5;
$col3Width = 5;
$col4Width = 5;

// Estilo de párrafo "sin espaciado"
$paragraphStyle = new Paragraph([
    'spacing' => 0,
    'spaceBefore' => 0,
    'spaceAfter' => 0,    'size' => 9 // Tamaño de fuente 9

]);

// Altura fija para todas las filas (ajusta según sea necesario)
$rowHeight = 280;

// Fila 1
$row = $table->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("El Docente ha estado vinculado con la Universidad del Cauca:                                                                                            SI ", ['bold' => true], ['alignment' => Jc::LEFT], $paragraphStyle);
// *** NOTA IMPORTANTE: Si la función 'existeSolicitudAnterior' también debe consultar la tabla de novedades,
// necesitarás modificar esa función en 'funciones.php' para aceptar un parámetro de tabla
// o crear una función similar específica para novedades.
$simboloCheckbox = existeSolicitudAnterior($cedula_solicitante, $anio_semestre) ? '☑' : '☐';

// Agregar la celda con el símbolo correspondiente
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT])
    ->addText($simboloCheckbox, [], ['alignment' => Jc::CENTER], $paragraphStyle);$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT])->addText("NO", [], ['alignment' => Jc::CENTER], $paragraphStyle);
$row->addCell($col4Width, ['width' => $col4Width, 'unit' => TblWidth::PERCENT])->addText("☐", [], ['alignment' => Jc::CENTER], $paragraphStyle);

// Fila 2
$row = $table->addRow($rowHeight, ['exactHeight' => true]);
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT])->addText("Se anexa historia laboral (hoja de vida):                                                                                                                                   SI", ['bold' => true], ['alignment' => Jc::LEFT], $paragraphStyle);

$simboloCasilla = ($anexa_hv_nuevo === 'si') ? '☒' : '☐';

// Añadir la celda a la fila con el símbolo correspondiente
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT])
     ->addText($simboloCasilla, [], ['alignment' => Jc::CENTER]);$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT])->addText("NO", [], ['alignment' => Jc::CENTER], $paragraphStyle);
$simboloCasilla = ($anexa_hv_nuevo === 'no') ? '☒' : '☐';

// Añadir la celda a la fila con el símbolo correspondiente
$row->addCell($col4Width, ['width' => $col4Width, 'unit' => TblWidth::PERCENT])
     ->addText($simboloCasilla, [], ['alignment' => Jc::CENTER]);


//tabla pegada  de anexos
// Estilos de la tabla justificada a la ventana (100% del ancho)
$tableStyle2 = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 0,
    'alignment' => Jc::LEFT,
    'width' => TblWidth::PERCENT,
    'unit' => TblWidth::PERCENT,
    'percentWidth' => 100
];

$phpWord->addTableStyle('tablaObservaciones', $tableStyle2);
$table = $section->addTable('tablaObservaciones');

// Definir anchos de columnas en porcentaje
$col1Width = 46;
$col2Width = 3;
$col3Width = 5;
$col4Width = 3;
$col5Width = 46;

// Estilo de párrafo "sin espaciado"
$paragraphStyle = new Paragraph([
    'spacing' => 0,
    'spaceBefore' => 0,
    'spaceAfter' => 0,    'size' => 9 // Tamaño de fuente 9

]);

// Altura fija para todas las filas (ajusta según sea necesario)
$rowHeight = 250;

// Fila 1 (5 columnas)
// Fila 1 (5 columnas) sin borde superior en toda la fila
$row = $table->addRow($rowHeight, ['exactHeight' => true]);

// No agregar borde superior a ninguna celda
$row->addCell($col1Width, ['width' => $col1Width, 'unit' => TblWidth::PERCENT, 'borderTopSize' => 0])->addText("Anexa actualización:                                                                 SI", [], ['alignment' => Jc::LEFT], $paragraphStyle);
$simboloCasilla = ($actualiza_hv_antiguo === 'si') ? '☒' : '☐';

// Añadir la celda a la fila con el símbolo correspondiente
$row->addCell($col2Width, ['width' => $col2Width, 'unit' => TblWidth::PERCENT, 'borderTopSize' => 0])
     ->addText($simboloCasilla, [], ['alignment' => Jc::CENTER]);
$row->addCell($col3Width, ['width' => $col3Width, 'unit' => TblWidth::PERCENT, 'borderTopSize' => 0])->addText("NO", [], ['alignment' => Jc::CENTER], $paragraphStyle);

$simboloCasilla = ($actualiza_hv_antiguo === 'no') ? '☒' : '☐';

// Añadir la celda a la fila con el símbolo correspondiente
$row->addCell($col4Width, ['width' => $col4Width, 'unit' => TblWidth::PERCENT, 'borderTopSize' => 0])
     ->addText($simboloCasilla, [], ['alignment' => Jc::CENTER]);

$row->addCell($col5Width, ['width' => $col5Width, 'unit' => TblWidth::PERCENT, 'borderTopSize' => 0])->addText("Cuál:", [], ['alignment' => Jc::LEFT], $paragraphStyle);// Fila 2 (1 columna combinada, doble alto)
$row = $table->addRow($rowHeight * 2, ['exactHeight' => true]);
$row->addCell(100, ['width' => 100, 'unit' => TblWidth::PERCENT, 'gridSpan' => 5])->addText("Observaciones:", ['bold' => true], ['alignment' => Jc::LEFT], $paragraphStyle);

// Agregar texto de Responsable y Jefe de Departamento
$section->addText("Responsable:", ['size' => 10], ['alignment' => Jc::LEFT, 'spaceAfter' => 300]); // Agregar más espacio después
$section->addText("_________________________", ['size' => 10], ['alignment' => Jc::LEFT, 'spaceAfter' => 0]);
$section->addText("Jefe de Departamento", ['size' => 10], ['alignment' => Jc::LEFT]);

// Guardar el documento
// Concatenar las variables para formar el nombre del archivo
// *** CAMBIO CLAVE: Cambiar el prefijo del archivo para novedades ***
$file = 'FOR-45_NOVEDADES_' . $nombre_solicitante . '_' . $periodo_consulta . '_' . $nombre_departamento . '.docx';

// Opcional: Reemplazar espacios por guiones bajos para evitar problemas en el nombre del archivo
$file = str_replace(' ', '_', $file);
header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save("php://output");
exit;
?>