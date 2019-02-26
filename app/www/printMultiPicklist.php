<?php
require('fpdf181/fpdf.php');

//parameters sent from html script over ajax
if(isset($_POST['retrieveList'])) {
    $retrieveList = $_POST['retrieveList'];
} else {
    $retrieveList = null;
}

$retrieveList_array = json_decode($retrieveList);

$pdf = new FPDF();
$w = array(60, 40, 40);
//set left and top margins
$lMargin = ($pdf->GetPageWidth() - array_sum($w)) / 2;
$pdf->SetMargins($lMargin, 40);

$pdf->AddPage();
$pdf->SetFont('Arial','',16);


foreach ($retrieveList_array as $retrieve) {
    if ($retrieve[0] == "success") {
        printDelivery($retrieve[1], $retrieve[2], $pdf);
        $pdf->Ln();
    } else {
        $pdf->SetTextColor(0);
        $pdf->Cell(40,7,'Delivery Number: '. $retrieve[1]);
        $pdf->Ln();
        $pdf->SetTextColor(128,0,0);
        $pdf->Cell(40,7,'This delivery was not found.');
        $pdf->Ln();
        $pdf->Ln();
    }

}

function printDelivery($delivery_number, $picking_list_array, $pdf) {

    $w = array(60, 40, 40);
    $pdf->SetTextColor(0);
    $pdf->Cell(40,7,'Delivery Number: '. $delivery_number);
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
    foreach ($picking_list_array as $picking_list) {
        $row = json_decode(json_encode($picking_list), true);
        $pdf->Cell($w[0], 6, $row['material'], 'LR', 0, 'L', $fill);
        $pdf->Cell($w[1], 6, $row['bin'], 'LR', 0, 'L', $fill);
        $pdf->Cell($w[2], 6, $row['qty'], 'LR', 0, 'R', $fill);

        $pdf->Ln();
        $fill = !$fill;
    }
    // Closing line
    $pdf->Cell(array_sum($w), 0, '', 'T');
    $pdf->Cell(40,7,'');
}

$pdf->Output('I', 'pickingListMulti.pdf');

?>