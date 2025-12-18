<?php
// generar_pdf.php

// 1. Seguridad
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Incluir FPDF y Conexión
require('libs/fpdf/fpdf.php'); 
include 'config/db.php';

// 3. Obtener ID
$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orden_id === 0) die("ID inválido");

// 4. Consulta de Datos (Adaptada a FUNCIONARIOS_MUNI)
$sql_orden = "
    SELECT 
        op.*, 
        CONCAT(f.NOMBRE, ' ', f.APELLIDO) AS Nombre_Solicitante,
        f.DEPTO AS Nombre_Departamento
    FROM Orden_Pedido op
    LEFT JOIN FUNCIONARIOS_MUNI f ON op.Solicitante_Id = f.ID
    WHERE op.Id = ?
";
$stmt = $conn->prepare($sql_orden);
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("Orden no encontrada");
$orden = $res->fetch_assoc();

// 5. Consulta de Items
$sql_items = "SELECT * FROM Orden_Item WHERE Orden_Id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $orden_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

// --- INICIO DE FPDF ---

class PDF extends FPDF {
    function Header() {
        // Logo opcional
        // $this->Image('assets/img/logo.png',10,6,30); 
        $this->SetFont('Arial','B',15);
        $this->Cell(80);
        $this->Cell(30,10,'ORDEN DE PEDIDO',0,0,'C');
        $this->Ln(20);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// --- SECCIÓN 1: DATOS GENERALES ---
$pdf->SetFillColor(240, 240, 240); 
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0, 8, utf8_decode('1. Datos Generales'), 1, 1, 'L', true);
$pdf->SetFont('Arial','',10);

// Fila 1
$pdf->Cell(35, 7, utf8_decode('N° Orden:'), 1);
$pdf->Cell(60, 7, $orden['Id'], 1);
$pdf->Cell(35, 7, utf8_decode('Fecha:'), 1);
$pdf->Cell(60, 7, date("d/m/Y", strtotime($orden['Fecha_Creacion'])), 1, 1);

// Fila 2
$pdf->Cell(35, 7, utf8_decode('Solicitante:'), 1);
$pdf->Cell(155, 7, utf8_decode($orden['Nombre_Solicitante']), 1, 1);

// Fila 3
$pdf->Cell(35, 7, utf8_decode('Departamento:'), 1);
$pdf->Cell(155, 7, utf8_decode($orden['Nombre_Departamento']), 1, 1);

// Fila 4
$pdf->Cell(35, 7, utf8_decode('Tipo Compra:'), 1);
$pdf->Cell(155, 7, utf8_decode($orden['Tipo_Compra']), 1, 1);

$pdf->Ln(5);

// --- SECCIÓN 2: DETALLES ---
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0, 8, utf8_decode('2. Detalle de la Compra'), 1, 1, 'L', true);
$pdf->SetFont('Arial','',10);

$pdf->Cell(35, 7, utf8_decode('Asunto:'), 1);
$pdf->Cell(155, 7, utf8_decode($orden['Nombre_Orden']), 1, 1);

// Motivo (Ajuste de altura dinámica simple)
$pdf->Cell(35, 14, utf8_decode('Motivo:'), 1);
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->MultiCell(155, 7, utf8_decode($orden['Motivo_Compra']), 1);
$pdf->SetXY($x + 155, $y + 14); // Forzar salto tras multicell simulado
$pdf->Ln(0); 

$pdf->Ln(5);

// --- SECCIÓN 3: ITEMS ---
$pdf->SetFont('Arial','B',9);
$pdf->Cell(15, 8, 'Cant.', 1, 0, 'C', true);
$pdf->Cell(105, 8, utf8_decode('Descripción / Producto'), 1, 0, 'C', true); // Más ancho
$pdf->Cell(35, 8, 'V. Unitario', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Total', 1, 1, 'C', true);

$pdf->SetFont('Arial','',9);

while($row = $res_items->fetch_assoc()) {
    $nombre_prod = utf8_decode($row['Nombre_producto_servicio']);
    $cant = $row['Cantidad'];
    
    // Formato moneda sin decimales si son ceros, o estándar CL
    $unit = number_format($row['Valor_Unitario'], 0, ',', '.');
    $total = number_format($row['Valor_Total'], 0, ',', '.');

    $pdf->Cell(15, 8, $cant, 1, 0, 'C');
    $pdf->Cell(105, 8, substr($nombre_prod, 0, 60), 1); 
    $pdf->Cell(35, 8, '$ '.$unit, 1, 0, 'R');
    $pdf->Cell(35, 8, '$ '.$total, 1, 1, 'R');
}

// --- TOTALES ---
$pdf->Ln(2);
$pdf->SetFont('Arial','B',10);

// Verificamos si es compra ágil/licitación para mostrar totales
$es_presupuesto = in_array($orden['Tipo_Compra'], ['Compra Ágil', 'Licitación Pública', 'Licitación Privada']);

if (!$es_presupuesto) {
    $pdf->Cell(155, 8, 'Neto', 1, 0, 'R');
    $pdf->Cell(35, 8, '$ '.number_format($orden['Valor_neto'], 0, ',', '.'), 1, 1, 'R');

    $pdf->Cell(155, 8, 'IVA (19%)', 1, 0, 'R');
    $pdf->Cell(35, 8, '$ '.number_format($orden['Iva'], 0, ',', '.'), 1, 1, 'R');
}

$pdf->SetFillColor(220, 230, 241); // Azul muy claro
$pdf->Cell(155, 10, 'TOTAL FINAL', 1, 0, 'R', true);
$pdf->Cell(35, 10, '$ '.number_format($orden['Valor_total'], 0, ',', '.'), 1, 1, 'R', true);

$pdf->Ln(15);

// --- ESTADO Y FIRMA ---
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0, 10, utf8_decode('ESTADO ACTUAL: ') . strtoupper($orden['Estado']), 0, 1, 'L');

if ($orden['Estado'] == 'Aprobado') {
    $pdf->Ln(10);
    $pdf->SetFont('Arial','I',10);
    $pdf->SetTextColor(0, 100, 0);
    $pdf->Cell(0, 10, utf8_decode('Este documento ha sido aprobado digitalmente en el sistema.'), 0, 1, 'C');
    $pdf->SetTextColor(0);
}

$pdf->Output('I', 'Orden_'.$orden_id.'.pdf'); 
?>