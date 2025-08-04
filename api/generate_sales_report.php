<?php
require_once 'db_config.php';
// Include the Composer autoload file
require_once '../vendor/autoload.php'; // Adjust the path as necessary

// Include your database configuration
include '../api/db_config.php'; 

// Get parameters
$fromDate = $_GET['fromDate'] ?? '';
$toDate = $_GET['toDate'] ?? '';
$branch = $_GET['branch'] ?? 'all';
$format = $_GET['format'] ?? 'pdf';

// Validate inputs
if (empty($fromDate) || empty($toDate)) {
    die('Invalid date range');
}

// Prepare base query
$query = "SELECT brand, model, branch, SUM(qty) as total_qty 
          FROM sales 
          WHERE sales_date BETWEEN ? AND ?";

// Add branch filter if not 'all'
$params = [$fromDate, $toDate];
if ($branch !== 'all') {
    $query .= " AND branch = ?";
    $params[] = $branch;
}

$query .= " GROUP BY brand, model, branch ORDER BY brand, model, branch";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('Failed to prepare query: ' . $conn->error);
}

// Bind parameters dynamically
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();

// Collect data
$salesData = [];
$brandTotals = [];
$branchTotals = [];
$grandTotal = 0;

while ($row = $result->fetch_assoc()) {
    $salesData[] = $row;
    
    // Calculate brand totals
    if (!isset($brandTotals[$row['brand']])) {
        $brandTotals[$row['brand']] = 0;
    }
    $brandTotals[$row['brand']] += $row['total_qty'];
    
    // Calculate branch totals
    if (!isset($branchTotals[$row['branch']])) {
        $branchTotals[$row['branch']] = 0;
    }
    $branchTotals[$row['branch']] += $row['total_qty'];
    
    $grandTotal += $row['total_qty'];
}

$stmt->close();

// Calculate percentages
foreach ($salesData as &$row) {
    $row['percentage'] = $grandTotal > 0 ? round(($row['total_qty'] / $grandTotal) * 100, 2) : 0;
}
unset($row);

// Generate report based on format
if ($format === 'pdf') {
    require_once 'tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SMDI Sales System');
    $pdf->SetAuthor('SMDI');
    $pdf->SetTitle('Sales Summary Report');
    $pdf->SetSubject('Sales Summary');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'SALES SUMMARY REPORT', 0, 1, 'C');
    
    // Report details
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Date Range: ' . date('m/d/Y', strtotime($fromDate)) . ' - ' . date('m/d/Y', strtotime($toDate)), 0, 1);
    $pdf->Cell(0, 5, 'Branch: ' . ($branch === 'all' ? 'All Branches' : $branch), 0, 1);
    $pdf->Ln(10);
    
    // Create table header
    $pdf->SetFont('helvetica', 'B', 10);
    $header = ['Model', 'Quantity', 'Branch', 'Percentage'];
    $w = [70, 30, 50, 40];
    
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 9);
    $currentBrand = '';
    
    foreach ($salesData as $row) {
        // Add brand header if changed
        if ($row['brand'] !== $currentBrand) {
            $currentBrand = $row['brand'];
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(array_sum($w), 6, $currentBrand, 0, 1);
            $pdf->SetFont('helvetica', '', 9);
        }
        
        $pdf->Cell($w[0], 6, $row['model'], 'LR', 0);
        $pdf->Cell($w[1], 6, $row['total_qty'], 'LR', 0, 'R');
        $pdf->Cell($w[2], 6, $row['branch'], 'LR', 0);
        $pdf->Cell($w[3], 6, $row['percentage'] . '%', 'LR', 0, 'R');
        $pdf->Ln();
    }
    
    // Closing line
    $pdf->Cell(array_sum($w), 0, '', 'T');
    $pdf->Ln(10);
    
    // Brand totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Subtotal by Brand:', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    
    foreach ($brandTotals as $brand => $total) {
        $pdf->Cell(100, 6, $brand, 0, 0);
        $pdf->Cell(30, 6, $total, 0, 0, 'R');
        $pdf->Ln();
    }
    $pdf->Ln(5);
    
    // Branch totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'Subtotal by Branch:', 0, 1);
    $pdf->SetFont('helvetica', '', 9);
    
    foreach ($branchTotals as $branch => $total) {
        $pdf->Cell(100, 6, $branch, 0, 0);
        $pdf->Cell(30, 6, $total, 0, 0, 'R');
        $pdf->Ln();
    }
    $pdf->Ln(5);
    
    // Grand total
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(100, 8, 'GRAND TOTAL:', 0, 0);
    $pdf->Cell(30, 8, $grandTotal, 0, 0, 'R');
    
    // Output PDF
    $pdf->Output('sales_summary_' . date('Ymd_His') . '.pdf', 'D');
    
} elseif ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="sales_summary_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    echo '<tr><th colspan="4">SALES SUMMARY REPORT</th></tr>';
    echo '<tr><th colspan="4">Date Range: ' . date('m/d/Y', strtotime($fromDate)) . ' - ' . date('m/d/Y', strtotime($toDate)) . '</th></tr>';
    echo '<tr><th colspan="4">Branch: ' . ($branch === 'all' ? 'All Branches' : $branch) . '</th></tr>';
    echo '<tr><th>Brand</th><th>Model</th><th>Branch</th><th>Quantity</th><th>Percentage</th></tr>';
    
    foreach ($salesData as $row) {
        echo '<tr>';
        echo '<td>' . $row['brand'] . '</td>';
        echo '<td>' . $row['model'] . '</td>';
        echo '<td>' . $row['branch'] . '</td>';
        echo '<td>' . $row['total_qty'] . '</td>';
        echo '<td>' . $row['percentage'] . '%</td>';
        echo '</tr>';
    }
    
    // Brand totals
    echo '<tr><th colspan="4">Subtotal by Brand</th></tr>';
    foreach ($brandTotals as $brand => $total) {
        echo '<tr>';
        echo '<td colspan="3">' . $brand . '</td>';
        echo '<td>' . $total . '</td>';
        echo '<td></td>';
        echo '</tr>';
    }
    
    // Branch totals
    echo '<tr><th colspan="4">Subtotal by Branch</th></tr>';
    foreach ($branchTotals as $branch => $total) {
        echo '<tr>';
        echo '<td colspan="3">' . $branch . '</td>';
        echo '<td>' . $total . '</td>';
        echo '<td></td>';
        echo '</tr>';
    }
    
    // Grand total
    echo '<tr><th colspan="3">GRAND TOTAL</th><th>' . $grandTotal . '</th><th></th></tr>';
    echo '</table>';
}

$conn->close();
?>