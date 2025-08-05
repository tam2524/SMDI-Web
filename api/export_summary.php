<?php
require_once '../vendor/autoload.php';
include '../api/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Get and validate parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = $_GET['month'] ?? 'all';
$format = $_GET['format'] ?? 'excel';
$branchFilter = $_GET['branch'] ?? 'all';
$fromDate = $_GET['fromDate'] ?? null;
$toDate = $_GET['toDate'] ?? null;

// Define branch order
$orderedBranches = [
    'RXS-1', 'RXS-2', 'ANT-1', 'ANT-2', 'DEL-1', 'DEL-2', 'JAR-1', 'JAR-2',
    'KAL-1', 'KAL-2', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
    'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', 'MINDO', 'MIN',
    'SALAY', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'CEBU'
];

// Get sales data with filtering
$salesQuery = "SELECT branch, brand, model, SUM(qty) as qty 
              FROM sales 
              WHERE YEAR(sales_date) = ?";
$params = [$year];
$types = 'i';

if (!empty($fromDate) && !empty($toDate)) {
    $salesQuery .= " AND sales_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $types .= 'ss';
} elseif ($month !== 'all') {
    $salesQuery .= " AND MONTH(sales_date) = ?";
    $params[] = (int)$month;
    $types .= 'i';
}

if ($branchFilter !== 'all') {
    $salesQuery .= " AND branch = ?";
    $params[] = $branchFilter;
    $types .= 's';
}

$salesQuery .= " GROUP BY branch, brand, model";
$stmt = $conn->prepare($salesQuery);
if (!$stmt) {
    die('Query preparation failed: ' . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$salesResult = $stmt->get_result();

$sales = [];
while ($row = $salesResult->fetch_assoc()) {
    $sales[] = $row;
}

// Get quotas data
$quotasQuery = "SELECT branch, quota 
               FROM sales_quotas 
               WHERE year = ?";
$stmt = $conn->prepare($quotasQuery);
$stmt->bind_param('i', $year);
$stmt->execute();
$quotasResult = $stmt->get_result();

$quotas = [];
while ($row = $quotasResult->fetch_assoc()) {
    $quotas[] = $row;
}

// Process data
$allBranches = array_unique(array_column($sales, 'branch'));
$branches = array_intersect($orderedBranches, $allBranches);
$branches = array_unique(array_merge($orderedBranches, $branches));
$branches = array_intersect($branches, $orderedBranches);

// Filter models to only those with sales
$modelsWithSales = [];
foreach ($sales as $sale) {
    if ($sale['qty'] > 0) {
        $modelsWithSales[$sale['model']] = true;
    }
}
$models = array_keys($modelsWithSales);
sort($models);

$brands = array_unique(array_column($sales, 'brand'));
sort($brands);

// Calculate totals - ensure numeric values
$branchTotals = [];
$modelTotals = [];
$brandBranchTotals = [];

foreach ($sales as $sale) {
    $qty = (int)$sale['qty'];
    $branch = $sale['branch'];
    $model = $sale['model'];
    
    $branchTotals[$branch] = ($branchTotals[$branch] ?? 0) + $qty;
    $modelTotals[$model] = ($modelTotals[$model] ?? 0) + $qty;
    $key = $sale['brand'] . '|' . $branch;
    $brandBranchTotals[$key] = ($brandBranchTotals[$key] ?? 0) + $qty;
}

$grandTotal = (int)array_sum($branchTotals);

// Export based on format
if ($format === 'excel') {
    exportToExcel($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month, $fromDate, $toDate);
} else {
    die('PDF export not implemented');
}

function exportToExcel($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month = 'all', $fromDate = null, $toDate = null) {
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("SMDI Sales System")
            ->setTitle("Sales Summary Report")
            ->setSubject("Sales Summary");

        // Title with date range
        $title = 'SALES SUMMARY REPORT';
        if ($fromDate && $toDate) {
            $title .= ' (' . date('M d, Y', strtotime($fromDate)) . ' - ' . date('M d, Y', strtotime($toDate)) . ')';
        } elseif ($month !== 'all') {
            $title .= ' - ' . strtoupper(date('F Y', strtotime($year.'-'.$month.'-01')));
        } else {
            $title .= ' - ' . $year;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($branches) + 1);
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row with branch names
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDDDDD']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];

        $sheet->setCellValue('A2', 'MODEL');
        $col = 1; // Start with column B (index 1)
        foreach ($branches as $branch) {
            $sheet->setCellValueByColumnAndRow($col + 1, 2, $branch);
            $col++;
        }
        $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray($headerStyle);

        // Model rows with sales data
        $row = 3;
        foreach ($models as $model) {
            $sheet->setCellValue('A' . $row, $model);
            
            $col = 1; // Start with column B (index 1)
            foreach ($branches as $branch) {
                $qty = 0;
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model && $sale['branch'] == $branch) {
                        $qty = (int)$sale['qty'];
                        break;
                    }
                }
                $sheet->setCellValueByColumnAndRow($col + 1, $row, $qty ?: '');
                $col++;
            }
            
            $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }

        // SUB-TOTAL row
        $subTotalStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $sheet->setCellValue('A' . $row, 'SUB-TOTAL');
        $col = 1; // Start with column B (index 1)
        foreach ($branches as $branch) {
            $total = isset($branchTotals[$branch]) ? (int)$branchTotals[$branch] : 0;
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $total ?: '');
            $col++;
        }
        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray($subTotalStyle);

        // Auto-size columns
        foreach (range('A', $lastColumn) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A3');

        // Clear any previous output
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Set proper headers before output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="sales_summary_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // Create writer and save to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    } catch (Exception $e) {
        die('Error generating Excel file: ' . $e->getMessage());
    }
}

function exportToPDF($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month) {
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SMDI Sales System');
    $pdf->SetAuthor('SMDI');
    $pdf->SetTitle('Sales Summary Report');
    $pdf->SetSubject('Sales Summary');
    
    // Set margins
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'SALES SUMMARY REPORT - ' . strtoupper(date('F Y', strtotime($year.'-'.($month === 'all' ? '01' : $month).'-01'))), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(5);
    
    // Calculate column widths
    $colWidth = (270 - 30) / (count($branches) + 1); // Total width minus first column, divided by columns
    
    // Create header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, 'Model/Branch', 1, 0, 'C');
    foreach ($branches as $branch) {
        $pdf->Cell($colWidth, 7, $branch, 1, 0, 'C');
    }
    $pdf->Cell($colWidth, 7, 'Total', 1, 1, 'C');
    
    // Create data rows
    $pdf->SetFont('helvetica', '', 9);
    foreach ($models as $model) {
        $pdf->Cell(30, 6, $model, 1, 0, 'L');
        foreach ($branches as $branch) {
            $qty = 0;
            foreach ($sales as $sale) {
                if ($sale['model'] == $model && $sale['branch'] == $branch) {
                    $qty = $sale['qty'];
                    break;
                }
            }
            $pdf->Cell($colWidth, 6, $qty, 1, 0, 'C');
        }
        $pdf->Cell($colWidth, 6, $modelTotals[$model] ?? 0, 1, 1, 'C');
    }
    
    // Create totals row
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(30, 6, 'Total', 1, 0, 'L');
    foreach ($branches as $branch) {
        $pdf->Cell($colWidth, 6, $branchTotals[$branch] ?? 0, 1, 0, 'C');
    }
    $pdf->Cell($colWidth, 6, $grandTotal, 1, 1, 'C');
    $pdf->Ln(5);
    
    // Performance section
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'PERFORMANCE SUMMARY', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    
    foreach ($brands as $brand) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $brand . ' PERFORMANCE', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($branches as $branch) {
            $key = $brand.'|'.$branch;
            $subtotal = $brandBranchTotals[$key] ?? 0;
            
            $quota = 0;
            foreach ($quotas as $q) {
                if ($q['branch'] == $branch) {
                    $quota = $q['quota'];
                    break;
                }
            }
            
            $percentage = $quota > 0 ? round(($subtotal / $quota) * 100, 2) : 0;
            
            $pdf->Cell(60, 6, $branch . ' - ' . $brand, 0, 0, 'L');
            $pdf->Cell(0, 6, 'Subtotal: ' . $subtotal . ' | Quota: ' . $quota . ' | Performance: ' . $percentage . '%', 0, 1, 'L');
        }
        $pdf->Ln(2);
    }
    
    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i:s'), 0, 0, 'C');
    
    // Output PDF
    $filename = 'sales_summary_'.$year.'_'.($month === 'all' ? 'all_months' : $month).'.pdf';
    $pdf->Output($filename, 'D');
}