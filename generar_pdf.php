<?php
// generar_pdf.php

// 1. Seguridad y Configuración
session_start();
date_default_timezone_set('America/Santiago');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require('libs/fpdf/fpdf.php'); 
include 'config/db.php';

// 2. Obtener Datos
$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orden_id === 0) die("ID de orden no válido.");

// Consulta Principal (Datos Orden + Solicitante)
$sql_orden = "
    SELECT 
        op.*, 
        CONCAT(f.NOMBRE, ' ', f.APELLIDO) AS Nombre_Solicitante,
        f.RUT AS Rut_Solicitante,
        f.CORREO AS Email_Solicitante,
        f.DEPTO AS Nombre_Departamento
    FROM Orden_Pedido op
    LEFT JOIN FUNCIONARIOS_MUNI f ON op.Solicitante_Id = f.ID
    WHERE op.Id = ?
";
$stmt = $conn->prepare($sql_orden);
$stmt->bind_param("i", $orden_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("La orden solicitada no existe.");
$orden = $res->fetch_assoc();

// Consulta de Ítems
$sql_items = "SELECT * FROM Orden_Item WHERE Orden_Id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $orden_id);
$stmt_items->execute();
$res_items = $stmt_items->get_result();

// --- CLASE PDF PERSONALIZADA (DISEÑO PRO) ---
class PDF extends FPDF {
    
    // Variables para ancho de columnas y alineación (Para tablas dinámicas)
    var $widths;
    var $aligns;

    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    // Cabecera de Página
    function Header() {
        // --- LOGO (Asegúrate de que la ruta exista o coméntalo) ---
        $ruta_logo = 'assets/img/logo.png'; 
        if(file_exists($ruta_logo)){
            $this->Image($ruta_logo, 10, 10, 25); // X, Y, Ancho
        }

        // --- EMPRESA / INSTITUCIÓN ---
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(44, 62, 80); // Azul Oscuro
        $this->Cell(0, 10, utf8_decode('MUNICIPALIDAD DE QUILLECO'), 0, 1, 'C');
        
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100); // Gris
        $this->Cell(0, 5, utf8_decode('Departamento de Adquisiciones'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('RUT: 69.170.600-4 | Calle Principal #123'), 0, 1, 'C');
        
        // --- TÍTULO DOCUMENTO ---
        $this->Ln(10);
        $this->SetFillColor(41, 128, 185); // Azul Corporativo
        $this->SetTextColor(255, 255, 255); // Blanco
        $this->SetFont('Arial', 'B', 12);
        // Dibujamos un rectángulo redondeado visualmente con celdas
        $this->Cell(0, 10, utf8_decode(' ORDEN DE PEDIDO N° ' . $GLOBALS['orden_id']), 0, 1, 'R', true);
        
        // Reset colores
        $this->SetTextColor(0, 0, 0);
        $this->Ln(5);
    }

    // Pie de Página
    function Footer() {
        $this->SetY(-20);
        
        // Línea decorativa
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(2);

        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Izquierda: Fecha Impresión
        $this->Cell(65, 10, utf8_decode('Generado el: ' . date('d/m/Y H:i:s')), 0, 0, 'L');
        
        // Centro: Pagina
        $this->Cell(60, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
        
        // Derecha: Sistema
        $this->Cell(65, 10, utf8_decode('Sistema de Gestión de Compras'), 0, 0, 'R');
    }

    // --- FUNCIÓN MÁGICA PARA TABLAS (AJUSTA TEXTO LARGO) ---
    function Row($data, $fill = false) {
        $nb = 0;
        for($i=0;$i<count($data);$i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 6 * $nb; // 6 es la altura de línea base
        $this->CheckPageBreak($h);
        
        for($i=0;$i<count($data);$i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Pinta fondo si es necesario
            if ($fill) {
                $this->SetFillColor(245, 245, 245); // Gris muy claro alterno
                $this->Rect($x, $y, $w, $h, 'F'); 
            }
            
            // Dibuja borde
            $this->Rect($x, $y, $w, $h);
            
            // Escribe texto
            $this->MultiCell($w, 6, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if($nb > 0 and $s[$nb-1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++;
                continue;
            }
            if($c == ' ') $sep = $i;
            $l += $cw[$c];
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j) $i++;
                } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

// --- CREACIÓN DEL DOCUMENTO ---

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 20);

// ==========================================
// 1. INFORMACIÓN DEL ENCABEZADO (Bloques)
// ==========================================

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230); // Gris encabezado bloques

// -- Bloque Izquierdo: Solicitante --
$y_start = $pdf->GetY();
$pdf->SetXY(10, $y_start);
$pdf->Cell(90, 8, utf8_decode(' INFORMACIÓN DEL SOLICITANTE'), 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(30, 6, utf8_decode('Nombre:'), 'L', 0, 'L');
$pdf->Cell(60, 6, utf8_decode(substr($orden['Nombre_Solicitante'], 0, 35)), 'R', 1, 'L');

$pdf->Cell(30, 6, utf8_decode('Departamento:'), 'L', 0, 'L');
$pdf->Cell(60, 6, utf8_decode(substr($orden['Nombre_Departamento'], 0, 35)), 'R', 1, 'L');

$pdf->Cell(30, 6, utf8_decode('Email:'), 'L', 0, 'L');
$pdf->Cell(60, 6, utf8_decode(substr($orden['Email_Solicitante'], 0, 35)), 'R', 1, 'L');

$pdf->Cell(90, 2, '', 'LBR', 1); // Cierre bloque izquierdo

// -- Bloque Derecho: Detalles Orden --
$pdf->SetXY(105, $y_start);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 8, utf8_decode(' DETALLES DE LA SOLICITUD'), 1, 1, 'L', true);

$pdf->SetXY(105, $y_start + 8);
$pdf->SetFont('Arial', '', 9);

$pdf->Cell(35, 6, utf8_decode('Fecha Solicitud:'), 'L', 0, 'L');
$pdf->Cell(60, 6, date("d/m/Y", strtotime($orden['Fecha_Creacion'])), 'R', 1, 'L');

$pdf->SetX(105);
$pdf->Cell(35, 6, utf8_decode('Tipo de Compra:'), 'L', 0, 'L');
$pdf->Cell(60, 6, utf8_decode($orden['Tipo_Compra']), 'R', 1, 'L');

$pdf->SetX(105);
$pdf->Cell(35, 6, utf8_decode('Plazo Entrega:'), 'L', 0, 'L');
$pdf->Cell(60, 6, date("d/m/Y", strtotime($orden['Plazo_maximo'])), 'R', 1, 'L');

$pdf->SetX(105);
$pdf->Cell(95, 2, '', 'LBR', 1); // Cierre bloque derecho

$pdf->Ln(10); // Espacio vertical

// ==========================================
// 2. MOTIVO Y DESCRIPCIÓN (Ancho completo)
// ==========================================
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(190, 8, utf8_decode(' MOTIVO DE LA COMPRA'), 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(190, 6, utf8_decode("Asunto: " . $orden['Nombre_Orden'] . "\n\nJustificación: " . $orden['Motivo_Compra']), 1, 'L');

$pdf->Ln(5);

// ==========================================
// 3. IMPUTACIÓN (Una sola línea limpia)
// ==========================================
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 7, utf8_decode('Cta. Presupuestaria:'), 1, 0, 'L', true);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(55, 7, utf8_decode($orden['Cuenta_Presupuestaria']), 1, 0, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(40, 7, utf8_decode('Centro de Costos:'), 1, 0, 'L', true);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(55, 7, utf8_decode($orden['Centro_Costos']), 1, 1, 'L');

$pdf->Ln(10);

// ==========================================
// 4. TABLA DE PRODUCTOS (PRO)
// ==========================================

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(41, 128, 185); // Azul encabezado tabla
$pdf->SetTextColor(255, 255, 255); // Texto blanco

// Definir anchos de columna
$w = array(15, 105, 35, 35); // Suma = 190
$header = array('Cant.', utf8_decode('Descripción / Producto'), 'V. Unit.', 'Total');

// Dibujar cabecera
for($i=0;$i<count($header);$i++)
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
$pdf->Ln();

// Configurar cuerpo de la tabla
$pdf->SetFillColor(245, 245, 245);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 9);
$pdf->SetWidths($w);
$pdf->SetAligns(array('C', 'L', 'R', 'R'));

$fill = false; // Para alternar colores

while($row = $res_items->fetch_assoc()) {
    $nombre_prod = utf8_decode($row['Nombre_producto_servicio']);
    if (!empty($row['Codigo_Producto'])) {
        $nombre_prod = "[" . utf8_decode($row['Codigo_Producto']) . "] " . $nombre_prod;
    }

    $unit = number_format($row['Valor_Unitario'], 0, ',', '.');
    $total = number_format($row['Valor_Total'], 0, ',', '.');

    // Usamos la función Row personalizada que ajusta el alto automáticamente
    $pdf->Row(array(
        $row['Cantidad'],
        $nombre_prod,
        '$ ' . $unit,
        '$ ' . $total
    ), $fill);
    
    $fill = !$fill; // Alternar color
}

// ==========================================
// 5. TOTALES
// ==========================================
$pdf->Ln(2);

$es_presupuesto = in_array($orden['Tipo_Compra'], ['Compra Ágil', 'Licitación Pública', 'Licitación Privada']);

$pdf->SetFont('Arial', 'B', 10);

// Cuadro de totales alineado a la derecha
$x_totales = 135; // Posición X para empezar totales
$w_label = 25;
$w_value = 30;

if (!$es_presupuesto) {
    // Neto
    $pdf->SetX($x_totales);
    $pdf->Cell($w_label, 7, 'Neto:', 0, 0, 'R');
    $pdf->Cell($w_value, 7, '$ ' . number_format($orden['Valor_neto'], 0, ',', '.'), 1, 1, 'R');

    // IVA
    $pdf->SetX($x_totales);
    $pdf->Cell($w_label, 7, 'IVA (19%):', 0, 0, 'R');
    $pdf->Cell($w_value, 7, '$ ' . number_format($orden['Iva'], 0, ',', '.'), 1, 1, 'R');
}

// TOTAL FINAL DESTACADO
$pdf->SetX($x_totales);
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($w_label, 9, 'TOTAL:', 1, 0, 'R', true);
$pdf->SetTextColor(0, 0, 0); // Volver a negro para el número
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell($w_value, 9, '$ ' . number_format($orden['Valor_total'], 0, ',', '.'), 1, 1, 'R');

$pdf->Ln(20);

// ==========================================
// 6. ESTADO Y FIRMA DIGITAL
// ==========================================

// Caja de Estado
$estado = $orden['Estado'];
$color_estado = ($estado == 'Aprobado') ? [46, 204, 113] : (($estado == 'Rechazada') ? [231, 76, 60] : [241, 196, 15]);

$pdf->SetDrawColor($color_estado[0], $color_estado[1], $color_estado[2]);
$pdf->SetLineWidth(0.5);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor($color_estado[0], $color_estado[1], $color_estado[2]);

$pdf->Cell(0, 12, utf8_decode('ESTADO ACTUAL: ' . strtoupper($estado)), 1, 1, 'C');

// Firma Simbólica
if ($estado == 'Aprobado') {
    $pdf->Ln(5);
    $pdf->SetTextColor(0, 128, 0);
    $pdf->SetFont('Arial', 'I', 10);
    
    // --- VERIFICACIÓN DE EXISTENCIA ---
    $ruta_check = 'assets/img/check.png';
    if (file_exists($ruta_check)) {
        $pdf->Image($ruta_check, 85, $pdf->GetY(), 5);
    }
    // ----------------------------------

    $pdf->Cell(0, 6, utf8_decode('Documento Firmado y Aprobado Digitalmente'), 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100);
    $pdf->Cell(0, 5, utf8_decode('Hash de seguridad: ' . md5($orden['Id'] . $orden['Fecha_Creacion'])), 0, 1, 'C');
}

$pdf->Output('I', 'Orden_Pedido_N'.$orden_id.'.pdf'); 
?>