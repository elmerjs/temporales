<?php
require 'vendor/autoload.php'; // Asegúrate de cargar PHPWord correctamente
require 'cn.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\SimpleType\Jc;

$phpWord = new PhpWord();
$section = $phpWord->addSection();
$phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

// Obtener los parámetros de la URL
$facultad_id = $_GET['facultad_id'];
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$tipo_docente = $_GET['tipo_docente'];

// Realizar el INSERT o UPDATE en la tabla depto_periodo
$sql_check = "SELECT id_depto_periodo FROM depto_periodo WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
$result_check = $con->query($sql_check);

if ($result_check->num_rows > 0) {
    // Si existe, hacer un UPDATE
   
    if($tipo_docente==="Catedra"){
    $sql_update = "UPDATE depto_periodo SET dp_estado_catedra = 'ce' WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
        } else {
             $sql_update = "UPDATE depto_periodo SET dp_estado_ocasional= 'ce' WHERE fk_depto_dp = '$departamento_id' AND periodo = '$anio_semestre'";
        }
    $con->query($sql_update);

} else {
    // Si no existe, hacer un INSERT
    if($tipo_docente==="Catedra"){
    $sql_insert = "INSERT INTO depto_periodo (fk_depto_dp, periodo, dp_estado_catedra) VALUES ('$departamento_id', '$anio_semestre', 'ce')";
    } else {
         $sql_insert = "INSERT INTO depto_periodo (fk_depto_dp, periodo, dp_estado_ocasional) VALUES ('$departamento_id', '$anio_semestre', 'ce')";
    }
    $con->query($sql_insert);
}
// Consulta SQL
$consultat = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

$resultadot = $con->query($consultat);
// Encabezado del oficio
$encabezado_oficio = "6.2.2/05";


$section->addText($encabezado_oficio, array('size' => 12, '' => false, 'name' => 'Times New Roman'));
$paragraphStyle = array('alignment' => Jc::BOTH);
$textRun = $section->addTextRun($paragraphStyle);

$textRun->addText('Popayán, 14 de febrero de 2024', null);
       
  $textRun = $section->addTextRun($paragraphStyle);
     $textRun->addText('Doctora', null);
          
  $textRun = $section->addTextRun($paragraphStyle);
     $textRun->addText('AIDA PATRICIA GONZALEZ', null);
 $textRun->addText('VICERRECTORA ACADÉMICA', null);
 $textRun->addText('Cordial saludo', null);

 $textRun->addText('Adjunto relación de docentes temporales del Departamento X', null);

$section->addText('Tabla con colspan y rowspan');
$styleTable = array('borderSize' => 6, 'borderColor' => '999999');
$phpWord->addTableStyle('ColspanRowspan', $styleTable);
$table = $section->addTable('ColspanRowspan');

$row = $table->addRow();
$row->addCell(1000, array('vMerge' => 'restart'))->addText('ID');
$row->addCell(1000, array('vMerge' => 'restart'))->addText('Cedula');
$row->addCell(1000, array('vMerge' => 'restart'))->addText('Nombre');
$row->addCell(1000, array('gridSpan' => 2, 'vMerge' => 'restart'))->addText('Dedicacion');
$row->addCell(1000, array('vMerge' => 'restart'))->addText('Anexa HV Nuevos');

$row->addCell(1000, array('vMerge' => 'restart'))->addText('Actualiza HV Antiguos');


$row = $table->addRow();
$row->addCell(1000, array('vMerge' => 'continue'));
$row->addCell(1000, array('vMerge' => 'continue'));
$row->addCell(1000, array('vMerge' => 'continue'));
$row->addCell(1000)->addText('Popayán');
$row->addCell(1000)->addText('Regionalización');
$row->addCell(1000, array('vMerge' => 'continue'));
$row->addCell(1000, array('vMerge' => 'continue'));


$result = $con->query($consultat);
while ($row = $result->fetch_assoc()) {
    $table->addRow();
    $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['id_solicitud']));
    $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['cedula']));
    $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['nombre']));
    if ($tipo_docente == "Ocasional") {
        $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['tipo_dedicacion']));
        $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['tipo_dedicacion_r']));
    } elseif ($tipo_docente == "Catedra") {
        $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['horas']));
        $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['horas_r']));
    }
    $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['anexa_hv_docente_nuevo']));
    $table->addCell(1000, ['borderTopSize' => 1, 'borderBottomSize' => 1, 'borderLeftSize' => 1, 'borderRightSize' => 1])->addText(utf8_decode($row['actualiza_hv_antiguo']));
}
$piedepagina = "\n\nUniversitariamente";
$section->addText($piedepagina, array('size' => 12, 'italic' => true));
    
// Encabezados HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="document.docx"');
header('Cache-Control: max-age=0');

// Guardar el archivo en formato DOCX y enviarlo al navegador
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
$con->close();
exit; // Terminar el script para evitar cualquier salida adicional

?>
