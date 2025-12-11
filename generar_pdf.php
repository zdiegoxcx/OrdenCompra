<?php
// generar_pdf.php
session_start();

// 1. Seguridad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Incluir FPDF y Conexión
// Asegúrate de que la carpeta 'fpdf' esté en el mismo lugar que este archivo
require('fpdf/fpdf.php');
include 'conectar.php';

// 3. Obtener ID
$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orden_id === 0) die("ID inválido");

// 4. Consulta de Datos (La misma de ver_orden.php mejorada)
$sql_orden = "
    SELECT 
        op.*, 
        u.Nombre AS Nombre_Solicitante, 
        u.Email AS Email_Solicitante, 
        d.Nombre AS Nombre_Departamento
    FROM Orden_Pedido op
    LEFT JOIN Usuario u ON op.Solicitante_Id = u.Id
    LEFT JOIN Departamento d ON u.Departamento_Id = d.Id
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
    // Cabecera de página
    function Header() {
        // Logo (Asegúrate de tener un logo o comenta esta línea)
        // $this->Image('logo.png',10,6,30); 
        
        $this->SetFont('Arial','B',15);
        $this->Cell(80); // Mover a la derecha
        $this->Cell(30,10,'ORDEN DE PEDIDO',0,0,'C');
        $this->Ln(20);
    }

    // Pie de página
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
$pdf->SetFillColor(230, 230, 230); // Gris claro
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode('1. Datos Generales'), 1, 1, 'L', true);
$pdf->SetFont('Arial','',10);

$pdf->Cell(40, 8, utf8_decode('N° Orden:'), 1);
$pdf->Cell(55, 8, $orden['Id'], 1);
$pdf->Cell(40, 8, utf8_decode('Fecha:'), 1);
$pdf->Cell(55, 8, date("d/m/Y", strtotime($orden['Fecha_Creacion'])), 1, 1);

$pdf->Cell(40, 8, utf8_decode('Solicitante:'), 1);
$pdf->Cell(150, 8, utf8_decode($orden['Nombre_Solicitante']), 1, 1);

$pdf->Cell(40, 8, utf8_decode('Departamento:'), 1);
$pdf->Cell(150, 8, utf8_decode($orden['Nombre_Departamento']), 1, 1);

$pdf->Cell(40, 8, utf8_decode('Tipo Compra:'), 1);
$pdf->Cell(150, 8, utf8_decode($orden['Tipo_Compra']), 1, 1);

$pdf->Ln(5);

// --- SECCIÓN 2: DETALLES ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode('2. Detalle de la Compra'), 1, 1, 'L', true);
$pdf->SetFont('Arial','',10);

$pdf->Cell(40, 8, utf8_decode('Nombre Compra:'), 1);
$pdf->Cell(150, 8, utf8_decode($orden['Nombre_Orden']), 1, 1);

// Motivo (MultiCell para que haga salto de línea)
$pdf->Cell(40, 16, utf8_decode('Motivo:'), 1); // Altura doble
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->MultiCell(150, 8, utf8_decode($orden['Motivo_Compra']), 1);
$pdf->SetXY($x + 150, $y); // Volver posición si hubiera algo al lado (aquí no es necesario pero es buena práctica)
$pdf->Ln(16); // Bajar la altura doble

$pdf->Ln(5);

// --- SECCIÓN 3: ITEMS ---
$pdf->SetFont('Arial','B',10);
$pdf->Cell(15, 8, 'Cant.', 1, 0, 'C', true);
$pdf->Cell(100, 8, utf8_decode('Descripción'), 1, 0, 'C', true);
$pdf->Cell(35, 8, 'V. Unitario', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Total', 1, 1, 'C', true);

$pdf->SetFont('Arial','',10);

$total_items = 0;
while($row = $res_items->fetch_assoc()) {
    $nombre_prod = utf8_decode($row['Nombre_producto_servicio']);
    $cant = $row['Cantidad'];
    $unit = number_format($row['Valor_Unitario'], 0, ',', '.');
    $total = number_format($row['Valor_Total'], 0, ',', '.');

    $pdf->Cell(15, 8, $cant, 1, 0, 'C');
    $pdf->Cell(100, 8, substr($nombre_prod, 0, 55), 1); // Cortamos texto para que no rompa la tabla simple
    $pdf->Cell(35, 8, '$ '.$unit, 1, 0, 'R');
    $pdf->Cell(40, 8, '$ '.$total, 1, 1, 'R');
}

// --- TOTALES ---
$pdf->SetFont('Arial','B',10);
$pdf->Cell(150, 8, 'Total Neto', 1, 0, 'R');
$pdf->Cell(40, 8, '$ '.number_format($orden['Valor_neto'], 0, ',', '.'), 1, 1, 'R');

$pdf->Cell(150, 8, 'IVA (19%)', 1, 0, 'R');
$pdf->Cell(40, 8, '$ '.number_format($orden['Iva'], 0, ',', '.'), 1, 1, 'R');

$pdf->SetFillColor(200, 255, 200); // Verde claro
$pdf->Cell(150, 10, 'TOTAL FINAL', 1, 0, 'R', true);
$pdf->Cell(40, 10, '$ '.number_format($orden['Valor_total'], 0, ',', '.'), 1, 1, 'R', true);

$pdf->Ln(20);

// --- FIRMA ---
$pdf->SetFont('Arial','',10);
$pdf->Cell(0, 10, utf8_decode('Estado Actual: ') . strtoupper($orden['Estado']), 0, 1, 'R');
if ($orden['Estado'] == 'Aprobado') {
    $pdf->SetTextColor(0, 128, 0);
    $pdf->Cell(0, 10, utf8_decode('DOCUMENTO APROBADO DIGITALMENTE'), 0, 1, 'R');
}

$pdf->Output('I', 'Orden_'.$orden_id.'.pdf'); // 'I' para mostrar en navegador, 'D' para forzar descarga
?>