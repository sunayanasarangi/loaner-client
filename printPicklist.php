
<?php
require('fpdf181/fpdf.php');

//parameters sent from html script over ajax
if(isset($_POST['pickingList'])) {
    $pickingList = $_POST['pickingList'];
} else {
    $pickingList = null;
}

if(isset($_POST['delivery_number'])) {
    $delivery_number = $_POST['delivery_number'];
} else {
    $delivery_number = null;
}

$material_bin_array = json_decode($pickingList);


$pdf = new FPDF();
$w = array(40, 40, 40);
//set left and top margins
$lMargin = ($pdf->GetPageWidth() - array_sum($w)) / 2;
$pdf->SetMargins($lMargin, 40);

$pdf->AddPage();
$pdf->Image("goback.png",8,11.3,73,0,"","picklist.html");
$pdf->SetFont('Arial','',16);

$pdf->Cell(40,7,'Delivery Number: '. $delivery_number);
$pdf->Ln();
$pdf->Ln();
// Colors, line width and bold font
$pdf->SetFillColor(76,175,80);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(76,175,80);
$pdf->SetLineWidth(.3);
$pdf->SetFont('','B');

// Column headings

$header = array('Material', 'Bin', 'Quantity');
// Header
for ($i = 0; $i < count($header); $i++)
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
$pdf->Ln();

// Color and font restoration
$pdf->SetFillColor(224,235,255);
$pdf->SetTextColor(0);
$pdf->SetFont('');

// Data
$fill = false;
foreach ($material_bin_array as $row) {
    $pdf->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
    $pdf->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
    $pdf->Cell($w[2], 6, $row[2], 'LR', 0, 'R', $fill);

    $pdf->Ln();
    $fill = !$fill;
}
// Closing line
$pdf->Cell(array_sum($w), 0, '', 'T');

$pdf->Output('I', 'pickingList.pdf');

?>

