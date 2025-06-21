<?php
require('fpdf186/fpdf.php');

class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(-1);
        $this->Ln(20);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
    }
}

require 'cn.php';

// Obtener los parámetros de la URL
$facultad_id = $_GET['facultad_id'];
$departamento_id = $_GET['departamento_id'];
$anio_semestre = $_GET['anio_semestre'];
$tipo_docente = $_GET['tipo_docente'];

$pdf = new PDF('P', 'mm', 'Letter'); // Tamaño carta
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10); // Reducir márgenes para maximizar el espacio
$pdf->SetAutoPageBreak(true, 20); // Ajuste automático de salto de página
$pdf->AddPage();

$pdf->SetFont('Arial', '', 12);
$pdf->Multicell(195, 5, '4-55.6/', 0, 'L'); // Ajuste de ancho
$pdf->Ln();
$meses = array(
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
);

$dia = date('j');
$mes = date('n');
$ano = date('Y');

$fecha_hoy = $dia . ' de ' . $meses[$mes] . ' de ' . $ano;

$pdf->Multicell(195, 5, utf8_decode('Popayán, ' . $fecha_hoy), 0, 'L'); // Ajuste de ancho
$pdf->Ln();
$pdf->Ln();

$consultat = "SELECT solicitudes.*, facultad.nombre_fac_minb AS nombre_facultad, deparmanentos.depto_nom_propio AS nombre_departamento 
            FROM solicitudes 
            JOIN deparmanentos ON (deparmanentos.PK_DEPTO = solicitudes.departamento_id)
            JOIN facultad ON (facultad.PK_FAC = solicitudes.facultad_id)
            WHERE facultad_id = '$facultad_id' AND departamento_id = '$departamento_id' AND anio_semestre = '$anio_semestre' and tipo_docente = '$tipo_docente' AND (solicitudes.estado <> 'an' OR solicitudes.estado IS NULL)";

$resultadot = $con->query($consultat);

// Crear la tabla de datos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(10, 14, 'ID', 1);
$pdf->Cell(25, 14, utf8_decode('Cédula'), 1);
$pdf->Cell(35, 14, 'Nombre', 1);
if ($tipo_docente == "Ocasional" || $tipo_docente == "Catedra") {
    $pdf->Cell(40, 7, utf8_decode('Dedicación'), 1, 0, 'C');
}
$pdf->Cell(40, 14, 'Anexa HV Nuevos', 1);
$pdf->Cell(40, 7, 'Actualiza HV Antiguos', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
if ($tipo_docente == "Ocasional") {
    $pdf->Cell(70, 14, '', 0);
    $pdf->Cell(20, 7, utf8_decode('Popayán'), 1);
    $pdf->Cell(20, 7, utf8_decode('Regionaliz'), 1);
    
    $pdf->Ln();
} elseif ($tipo_docente == "Catedra") {
    $pdf->Cell(70, 14, '', 0);
    $pdf->Cell(20, 7, 'Horas Pop', 1);
    $pdf->Cell(20, 7, 'Horas Reg', 1);
    $pdf->Ln();
}

$result = $con->query($consultat);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(10, 7, utf8_decode($row['id_solicitud']), 1);
    $pdf->Cell(25, 7, utf8_decode($row['cedula']), 1);
    $pdf->Cell(35, 7, utf8_decode($row['nombre']), 1);
    if ($tipo_docente == "Ocasional") {
        $pdf->Cell(20, 7, utf8_decode($row['tipo_dedicacion']), 1);
        $pdf->Cell(20, 7, utf8_decode($row['tipo_dedicacion_r']), 1);
    }
    if ($tipo_docente == "Catedra") {
        $pdf->Cell(20, 7, utf8_decode($row['horas']), 1);
        $pdf->Cell(20, 7, utf8_decode($row['horas_r']), 1);
    }
    $pdf->Cell(40, 7, utf8_decode($row['anexa_hv_docente_nuevo']), 1);
    $pdf->Cell(40, 7, utf8_decode($row['actualiza_hv_antiguo']), 1);
    $pdf->Ln();
}

$pdf->Output();
?>
