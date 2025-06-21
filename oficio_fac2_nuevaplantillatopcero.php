<?php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'cn.php'; // Asegúrate de que este archivo contiene la conexión a la base de datos
include 'funciones.php'; // Asegúrate de ajustar la ruta correctamente

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// Definir estilos de texto para las celdas de la tabla
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);

$cellTextStyle = array('size' => 9, 'name' => 'Arial');
$cellTextStyleb = array('size' => 8, 'name' => 'Arial');
$cellTextStylec = array('size' => 7, 'name' => 'Arial');

// Estilos para la celda del encabezado de la tabla
$headerCellStyle = array('bgColor' => '#f2f2f2');

// Crear una nueva instancia de PhpWord
$phpWord = new PhpWord();

// Definir dimensiones de una página tamaño carta en twips
$pageWidth = 12240; // 21.59 cm (8.5 pulgadas) en twips
$pageHeight = 15840; // 27.94 cm (11 pulgadas) en twips

// Agregar una sección con tamaño de página y márgenes personalizados
$section = $phpWord->addSection(array(
    'pageSizeW' => $pageWidth, 
    'pageSizeH' => $pageHeight,
    'marginLeft' => 1700,
    'marginRight' => 1700,  // 3 cm a la derecha (en twips)
    'marginTop' => 2268,    // 2 cm arriba (en twips)
    'marginBottom' => 1134,  // 2 cm abajo (en twips)
      //  "headerHeight" => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0),
    "footerHeight" => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0)


));
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Obtener los parámetros de la URL
$decano_oficio= $_GET['decano'];
$facultad_id = $_GET['facultad_id'];

$fecha_oficio = $_GET['fecha_oficio'];
$anio_semestre = $_GET['anio_semestre'];
$num_oficio = isset($_GET['numero_oficio']) ? $_GET['numero_oficio'] : '';
$elaboro = isset($_GET['elaborado_por']) ? $_GET['elaborado_por'] : '';
$totalfolios=0;
$totalfoliosr = isset($_GET['folios']) ? $_GET['folios'] : 0; // Nueva variable para folios


$cierreperiodo = obtenerperiodo($anio_semestre);

$decano =$decano_oficio; //vamos a tomar el dato que viene del modal;  ahi se hiczo la verificacion

if ($cierreperiodo != '1'){//si no está cerrada

// Verificar si el registro ya existe
$sql_check = "SELECT * FROM fac_periodo WHERE fp_fk_fac = '$facultad_id' AND fp_periodo = '$anio_semestre'";
$result_check = $con->query($sql_check);

if ($result_check->num_rows > 0) {
    // El registro existe, realizar actualización
    $sql_update = "UPDATE fac_periodo SET fp_estado = '1', fp_num_oficio = '$num_oficio', fp_elaboro='$elaboro', fp_decano = '$decano', fecha_accion = NOW(), fecha_oficio_fac = '$fecha_oficio', fp_acepta_vra = 0 WHERE fp_fk_fac = '$facultad_id' AND fp_periodo = '$anio_semestre'";
    if ($con->query($sql_update) === TRUE) {
        //echo "Registro actualizado correctamente.";
    } else {
        echo "Error al actualizar el registro: " . $con->error;
    }
} else {
    // El registro no existe, realizar inserción
    $sql_insert = "INSERT INTO fac_periodo (fp_fk_fac, fp_periodo, fp_estado, fp_num_oficio, fp_elaboro, fp_decano,fecha_accion,fecha_oficio_fac) VALUES ('$facultad_id', '$anio_semestre', '1', '$num_oficio', '$elaboro', '$decano',NOW(),'$fecha_oficio')";
    if ($con->query($sql_insert) === TRUE) {
    //    echo "Registro insertado correctamente.";
    } else {
        echo "Error al insertar el registro: " . $con->error;
    }
}
} //se hace todo el update  solo si  el semestre está abierto de lo contrario solo lo que sigue
if($facultad_id =='0') {$where = "where anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";}
else {$where = "WHERE anio_semestre = '$anio_semestre' and facultad.PK_FAC ='$facultad_id' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";}
$facultades = [
    1 => ['encab' => 'img/encabezado_decanatura_artes.png', 'pie' => 'img/pieartes.png'],
    2 => ['encab' => 'img/encabezado_decanatura_agrarias.png', 'pie' => 'img/pieagro.png'],
    3 => ['encab' => 'img/encabezado_decanatura_salud.png', 'pie' => 'img/piesalud.png'],
    4 => ['encab' => 'img/encabezado_decanatura_fccea.png', 'pie' => 'img/piecontables.png'],
    5 => ['encab' => 'img/encabezado_decanatura_humanas.png', 'pie' => 'img/piehumanas.png'],
    6 => ['encab' => 'img/encabezado_decanatura_facnedx.png', 'pie' => 'img/piefacned.png'],
    7 => ['encab' => 'img/encabezado_decanatura_derecho.png', 'pie' => 'img/piederecho.png'],
    8 => ['encab' => 'img/encabezado_decanatura_civil.png', 'pie' => 'img/piecivil.png'],
    9 => ['encab' => 'img/encabezado_decanatura_fiet.png', 'pie' => 'img/piefiet.png']
];

if (isset($facultades[$facultad_id])) {
    $imgencabezado = $facultades[$facultad_id]['encab'];
    $imgpie = $facultades[$facultad_id]['pie'];
} else {
    $imgencabezado = 'img/encabezado_generico.png';
    $imgpie = 'img/piegenerico.png';
}

// Encabezado del oficio
$header = $section->addHeader();
$header->addImage($imgencabezado, array(
    'height' => 80,
    'marginLeft' => 10, // Ajusta este valor según sea necesario
    'align' => 'left',
));

$paragraphStylexz = array('lineHeight' => 1, 'spaceAfter' => 0, 'spaceBefore' => 0);
$fontStylecuerpo = array('name' => 'Arial', 'size' => 11);
$encabezado_oficio = $num_oficio;

$section->addText($encabezado_oficio, array('size' => 11, 'bold' => false, 'name' => 'Arial'),$paragraphStylexz);
// Obtener la fecha actual

// Configurar la localización a español
setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252');

// Obtener la fecha actual en español
//$fecha_actual = strftime('%d de %B de %Y'); // Formato: 14 de febrero de 2024
$fecha_actual = strftime('%d de %B de %Y', strtotime($fecha_oficio));

  
// Estilo de párrafo justificado
$justifiedStyle = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH);

$consulta_fac = "SELECT distinct facultad.PK_FAC as pk_fac, facultad.nombre_fac_min AS nombre_facultad 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            $where";

$resultadofac = $con->query($consulta_fac);

while ($rowfac = $resultadofac->fetch_assoc()) {
     $pk_fac = $rowfac['pk_fac'];
     $fac = $rowfac['nombre_facultad'];
    
$consulta_depto = "SELECT DISTINCT NOMBRE_DEPTO_CORT, deparmanentos.PK_DEPTO, depto_periodo.num_oficio_depto, depto_periodo.dp_folios, depto_nom_propio 
                  FROM solicitudes 
                join depto_periodo on depto_periodo.fk_depto_dp = solicitudes.departamento_id

                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE facultad_id = '$pk_fac' AND anio_semestre = '$anio_semestre' and depto_periodo.dp_estado_total=1 AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL) AND depto_periodo.periodo = '$anio_semestre';";

$resultadodepto = $con->query($consulta_depto);

if (!$resultadodepto) {
    die('Error en la consulta: ' . $con->error);
}

// Agregar texto con la fecha actual
$section->addText('Popayán, ' . $fecha_actual,$paragraphStylexz);
    $section->addTextBreak(); // Inserta un salto de línea

    $section->addText('Doctora', $fontStylecuerpo,$paragraphStylexz);
        $section->addText('AIDA PATRICIA GONZALEZ',$fontStylecuerpo,$paragraphStylexz);

    
        $section->addText('Vicerrectora Académica', $fontStylecuerpo,$paragraphStylexz);
     $section->addText('Universidad del Cauca', $fontStylecuerpo,$paragraphStylexz);
$section->addTextBreak(); // Inserta un salto de línea

    
$asunto = 'Asunto: Solicitud contratación Docentes Ocasionales y Catedra ' . $anio_semestre . ' de la Facultad de ' . $fac . '.';
$section->addText($asunto,null, $justifiedStyle);
$section->addTextBreak(); // Inserta un salto de línea


$section->addText('Cordial saludo');

// Agregar el cuerpo del texto
$cuerpo = 'Para su conocimiento y trámite pertinente remito las solicitudes de vinculación de profesores temporales, para el periodo ' . $anio_semestre . ', con previa revisión de la labor en el aplicativo SIMCA, actas de selección y aval del Consejo de '. $fac;
    
    
        
$section->addText($cuerpo, null, $justifiedStyle);

// Agregar el siguiente párrafo
$parrafo = 'A continuación relaciono los profesores temporales requeridos para iniciar trámites de aval de vinculación para el periodo' . $anio_semestre . 'por la Vicerrectoría Académica:';
$section->addText($parrafo, null, $justifiedStyle);

// Escapar las variables para prevenir inyecciones SQL
//$departamento_id = $con->real_escape_string($departamento_id);
$anio_semestre = $con->real_escape_string($anio_semestre);

$nom_depto = ""; // Inicializar la variable para almacenar el nombre del departamento

while ($rowdepto = $resultadodepto->fetch_assoc()) {
//    $nom_depto = $rowdepto['depto_nom_propio'];
    $nom_depto = mb_strtoupper($rowdepto['depto_nom_propio'], 'UTF-8');

     $departamento_id = $rowdepto['PK_DEPTO'];
    $oficio_depto = $rowdepto['num_oficio_depto'];
    $folios = $rowdepto['dp_folios'];
    $totalfolios= $totalfolios+$folios;
$paragraphStylec = array('spaceAfter' => 1);

if ($folios > 1) {
    $section->addText(
        'DEPARTAMENTO DE ' . $nom_depto . ' (Oficio: ' . $oficio_depto . ') ' . $folios . ' folios', 
        array('bold' => true), 
        $paragraphStylec
    );
} elseif ($folios == 1) {
    $section->addText(
        'DEPARTAMENTO DE ' . $nom_depto . ' (Oficio: ' . $oficio_depto . ') ' . $folios . ' folio', 
        array('bold' => true), 
        $paragraphStylec
    );
} else {
    $section->addText(
        'DEPARTAMENTO DE ' . $nom_depto . ' (Oficio: ' . $oficio_depto . ') ', 
        array('bold' => true), 
        $paragraphStylec
    );
} 
// Consulta SQL para obtener los tipos de docentes
$consulta_tipo = "SELECT DISTINCT tipo_docente AS tipo_d
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL);";

$resultadotipo = $con->query($consulta_tipo);

if (!$resultadotipo) {
    die('Error en la consulta: ' . $con->error);
}

while ($rowtipo = $resultadotipo->fetch_assoc()) {
    $tipo_d = $rowtipo['tipo_d'];
    $consultat = "SELECT solicitudes.*, facultad.nombre_fac_min AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
                  FROM solicitudes 
                  JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
                  JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
                  WHERE departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' AND tipo_docente = '$tipo_d' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

    $resultadot = $con->query($consultat);

    if (!$resultadot) {
        die('Error en la consulta: ' . $con->error);
    }

$paragraphStyleb = array('spaceAfter' => 0, 'lineHeight' => 1);
$fontStyleb = array('name' => 'Arial', 'size' => 12);

//$section->addText('Docente ' . $tipo_d, $fontStyleb, $paragraphStyleb);
    $tipo_mostrar = ($tipo_d === 'Catedra') ? 'Cátedra' : $tipo_d;
$section->addText('Profesor(es) ' . $tipo_mostrar, $fontStyleb, $paragraphStyleb);
    
    // Estilo de la tabla
    $styleTable = array(
        'borderSize' => 6,
        'borderColor' => '999999',
        'cellMargin' => 60, // Margen interno de las celdas (en unidades twips)
    );

   // Define paragraph style with minimal space after the text
$paragraphStyle = array('spaceAfter' => 0, 'spaceBefore' => 0, 'spacing' => 0);

// Estilo para la tabla
$styleTable = array('borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80);
$phpWord->addTableStyle('ColspanRowspan', $styleTable);
$table = $section->addTable('ColspanRowspan');
$table->setWidth('100%');

// Encabezados de la tabla
$row = $table->addRow();
$row->addCell(400, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('ID', $cellTextStyle, $paragraphStyle);
$row->addCell(1200, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Cedula', $cellTextStyle, $paragraphStyle);
$row->addCell(4000, array_merge($headerCellStyle, array('vMerge' => 'restart')))->addText('Nombre', $cellTextStyle, $paragraphStyle);
$row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Dedicacion/hr', $cellTextStylec, $paragraphStyle);
$row->addCell(700, array_merge($headerCellStyle, array('alignment' => Jc::CENTER, 'gridSpan' => 2, 'vMerge' => 'restart')))->addText('Hoja de vida', $cellTextStylec, $paragraphStyle);

$row = $table->addRow();
$row->addCell(400, array('vMerge' => 'continue'));
$row->addCell(1200, array('vMerge' => 'continue'));
$row->addCell(4000, array('vMerge' => 'continue'));
$row->addCell(350, $headerCellStyle)->addText('Pop', $cellTextStylec, $paragraphStyle);
$row->addCell(350, $headerCellStyle)->addText('Regnz', $cellTextStylec, $paragraphStyle);
$row->addCell(350, $headerCellStyle)->addText('Anex(Nuevo)', $cellTextStylec, $paragraphStyle);
$row->addCell(350, $headerCellStyle)->addText('Actlz(Antiguo)', $cellTextStylec, $paragraphStyle);

$cont = 0;
while ($row = $resultadot->fetch_assoc()) {
    $cont++;

    $table->addRow();
    $table->addCell(400, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($cont, $cellTextStyle, $paragraphStyle);
    $table->addCell(1200, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['cedula']), $cellTextStyle, $paragraphStyle);
    $table->addCell(4000, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['nombre']), $cellTextStyle, $paragraphStyle);

    if ($tipo_d == "Ocasional") {
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['tipo_dedicacion']), $cellTextStyle, $paragraphStyle);
        $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['tipo_dedicacion_r']), $cellTextStyle, $paragraphStyle);
    } elseif ($tipo_d == "Catedra") {
    // Validar si horas y horas_r son 0 para mostrar vacíos
    $horas = ($row['horas'] == 0) ? "" : utf8_decode($row['horas']);
    $horas_r = ($row['horas_r'] == 0) ? "" : utf8_decode($row['horas_r']);

    $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($horas, $cellTextStyle, $paragraphStyle);
    $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText($horas_r, $cellTextStyle, $paragraphStyle);
}

    $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['anexa_hv_docente_nuevo']), $cellTextStyle, $paragraphStyle);
    $table->addCell(350, ['borderSize' => 1, 'marginTop' => 0, 'marginBottom' => 0])->addText(utf8_decode($row['actualiza_hv_antiguo']), $cellTextStyle, $paragraphStyle);
}
$section->addText('', null, array('spaceAfter' => 1)); // Ajusta el espacio después de la línea


}

}}

$cuerpof = 'Universitariamente;';

$section->addText($cuerpof, null, $justifiedStyle);
$section->addText(mb_strtoupper($decano, 'UTF-8'), null);
function numberToWords($number) {
    $f = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    return $f->format($number);
}

$fontStyle = array('italic' => true, 'size' => 8);
$paragraphStyle = array('indentation' => array('left' => 720)); // 720 twips = 0.5 pulgada

$foliosEnLetras = numberToWords($totalfoliosr);

$section->addText('Anexo : ('.$totalfoliosr.') '. $foliosEnLetras.' folios', $fontStyle);


$consultaanexoact = "

SELECT * FROM depto_periodo
join deparmanentos  on (deparmanentos.PK_DEPTO= depto_periodo.fk_depto_dp)
WHERE depto_periodo.periodo ='$anio_semestre' and deparmanentos.FK_FAC='$pk_fac'  and dp_estado_total='1'";

$resultadoanexoact = $con->query($consultaanexoact);

if (!$resultadoanexoact) {
    die('Error en la consulta: ' . $con->error);
}



while ($rowanexoact = $resultadoanexoact->fetch_assoc()) {
    $nombre_deptoact = ucwords(mb_strtolower($rowanexoact['depto_nom_propio'], 'UTF-8'));
    $acta_act = ucwords(mb_strtolower($rowanexoact['dp_acta_periodo'], 'UTF-8'));
    $folios_act = $rowanexoact['dp_folios'];
    $oficio_depto = $rowanexoact['num_oficio_depto'];
    $depto_id = $rowanexoact['fk_depto_dp']; // ID del departamento actual

    // === CONSULTA: cantidad de profesores por departamento ===
    $consultacantdepto = "
        SELECT COUNT(*) as cant_profesores
        FROM solicitudes 
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
        JOIN depto_periodo ON depto_periodo.fk_depto_dp = solicitudes.departamento_id 
             AND depto_periodo.periodo = solicitudes.anio_semestre
        WHERE deparmanentos.FK_FAC = '$pk_fac' 
          AND solicitudes.anio_semestre = '$anio_semestre'
          AND depto_periodo.dp_estado_total = '1'
          AND depto_periodo.fk_depto_dp = '$depto_id'
          AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
    ";
    $resultadocantdepto = $con->query($consultacantdepto);
    if (!$resultadocantdepto) {
        die('Error en la consulta de conteo por depto: ' . $con->error);
    }
    $rowcantdepto = $resultadocantdepto->fetch_assoc();
    $cant_profesores_depto = $rowcantdepto['cant_profesores'];

    // === IMPRESIÓN DEL GRUPO ===
$section->addText('Departamento de ' . $nombre_deptoact, ['bold' => true] + $fontStyle, $paragraphStylec);
   $section->addText('Acta de selección: ' . $acta_act . ' (' . $rowanexoact['dp_fecha_acta'] . ')', $fontStyle, ['spaceAfter' => 0]);
$section->addText('Oficio: ' . $oficio_depto . ' (' . $rowanexoact['fecha_oficio_depto'] . ')', $fontStyle, $paragraphStylec);
    $section->addText('(' . $cant_profesores_depto . ') Formatos PA-GA-5.1-FOR 45 Revisión Requisitos Vinculación Docente', $fontStyle, array('spaceAfter' => 0));

    // === CONSULTA E IMPRESIÓN DE HVS DEL DEPARTAMENTO ===
    $consultaanexo = "
        SELECT solicitudes.*, facultad.nombre_fac_min AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
        FROM solicitudes 
        JOIN depto_periodo ON depto_periodo.fk_depto_dp = solicitudes.departamento_id 
             AND depto_periodo.periodo = solicitudes.anio_semestre
        JOIN deparmanentos ON deparmanentos.PK_DEPTO = solicitudes.departamento_id
        JOIN facultad ON facultad.PK_FAC = solicitudes.facultad_id
        WHERE facultad_id = '$pk_fac' 
          AND anio_semestre = '$anio_semestre' 
          AND depto_periodo.dp_estado_total = 1 
          AND solicitudes.departamento_id = '$depto_id'
          AND (solicitudes.anexa_hv_docente_nuevo = 'si' OR solicitudes.actualiza_hv_antiguo = 'si') 
          AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)
    ";
    $resultadoanexo = $con->query($consultaanexo);
    if (!$resultadoanexo) {
        die('Error en la consulta de anexos por departamento: ' . $con->error);
    }

    while ($rowanexo = $resultadoanexo->fetch_assoc()) {
        $nombre = ucwords(strtolower($rowanexo['nombre']));
        $section->addText('Hoja de Vida ' . $nombre . ' con su respectiva lista de chequeo', $fontStyle, array('spaceAfter' => 0));
    }
}
    $section->addTextBreak(); // Inserta un salto de línea

$section->addText('elaboró:'.$elaboro, $fontStyle);

// Pie de página
//$piedepagina = "Universitariamente";
//$section->addText($piedepagina, array('size' => 11, 'italic' => true));
// Pie de página
$footer = $section->addFooter();
$footer->addImage($imgpie, array(
    'width' => 490, // Ajusta el ancho según sea necesario
    //'height' => 65, // Ajusta el alto según sea necesario
    'marginTop' => 56.7, // Ajusta el margen superior para mover la imagen 2 cm más abajo
    'marginRight' => 1000, // Ajusta el margen derecho según sea necesario
));

// Encabezados HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="document.docx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en formato DOCX y enviarlo al navegador
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');


$vra_email= 'elmerjs@gmail.com'; //pendiente enviar a usuarios tipo 1
// Configuración de PHPMailer para el envío de correo
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ejurado@unicauca.edu.co'; // Cambia esto por tu correo
    $mail->Password   = 'Portivolare5+11'; // Cambia esto por tu contraseña
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Opciones SSL para mayor compatibilidad
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    // Configurar destinatarios
    $mail->setFrom('ejurado@unicauca.edu.co', 'solicitudes vinculación');
    $mail->addAddress($vra_email, 'Destinatario');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';  // Asegúrate de que el correo se envíe en UTF-8
    $mail->Subject = 'Notificación: solicitud de vinculación temporales facultad:' . $fac;
    $mail->Body    = "
        <p>Estimado/a,</p>
        <p>Se ha generado una solicitud de vinculación de profesores temporales desde la facultad <strong>{$fac}</strong> para el periodo: {$anio_semestre}.</p>
        <p>Por favor, revise la plataforma solicitudes de vinculación, http://192.168.42.175/temporales/ para más detalles.<em>(acceso restringido a dispositivos dentro de la red interna de la Universidad del Cauca)</em></p>
        <p>Saludos cordiales,</p>
        <p><strong>Vicerrectoría Académica</strong></p>
    ";
    
    // Enviar el correo
    $mail->send();
  //  echo "Correo enviado correctamente a $vra_email.";
} catch (Exception $e) {
  //  echo "No se pudo enviar el correo: {$mail->ErrorInfo}";
}



exit; // Terminar el script para evitar cualquier salida adicional
?>
