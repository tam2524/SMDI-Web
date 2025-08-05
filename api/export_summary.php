<?php
require_once '../vendor/autoload.php';
include '../api/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use TCPDF;

// Get parameters
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? 'all';
$format = $_GET['format'] ?? 'excel';

// Define branch order (matching your example)
$orderedBranches = [
    'RXS-1', 'RXS-2', 'ANT-1', 'ANT-2', 'DEL-1', 'DEL-2', 'JAR-1', 'JAR-2',
    'KAL-1', 'KAL-2', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
    'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', 'MINDO', 'MIN',
    'SALAY', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'CEBU'
];

// Get sales data
$salesQuery = "SELECT branch, brand, model, SUM(qty) as qty 
              FROM sales 
              WHERE YEAR(sales_date) = ?";
$params = [$year];
$types = 'i';

if ($month !== 'all') {
    $salesQuery .= " AND MONTH(sales_date) = ?";
    $params[] = $month;
    $types .= 'i';
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

// Extract and filter data
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

// Calculate totals
$branchTotals = [];
$modelTotals = [];
$brandBranchTotals = [];

foreach ($sales as $sale) {
    $branchTotals[$sale['branch']] = ($branchTotals[$sale['branch']] ?? 0) + $sale['qty'];
    $modelTotals[$sale['model']] = ($modelTotals[$sale['model']] ?? 0) + $sale['qty'];
    $key = $sale['brand'] . '|' . $sale['branch'];
    $brandBranchTotals[$key] = ($brandBranchTotals[$key] ?? 0) + $sale['qty'];
}

$grandTotal = array_sum($branchTotals);

if ($format === 'excel') {
    exportToExcel($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month);
} else {
    exportToPDF($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month);
}

// Excel export function with the requested format
function exportToExcel($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("SMDI Sales System")
        ->setTitle("Sales Summary Report")
        ->setSubject("Sales Summary");

    // Title
    $sheet->mergeCells('A1:' . Coordinate::stringFromColumnIndex(count($branches) + 3) . '1');
    $sheet->setCellValue('A1', 'SALES SUMMARY REPORT - ' . strtoupper(date('F Y', strtotime($year.'-'.($month === 'all' ? '01' : $month).'-01'))));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Header row with styling matching your example
    $headerStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDDDDD']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    $sheet->setCellValue('A2', 'MODEL');
    $col = 'B';
    foreach ($branches as $branch) {
        $sheet->setCellValue($col.'2', $branch);
        $col++;
    }
    $sheet->setCellValue($col.'2', 'TTL');
    $col++;
    $sheet->setCellValue($col.'2', 'CEBU');
    $col++;
    $sheet->setCellValue($col.'2', 'GT');
    $col++;
    $sheet->setCellValue($col.'2', '%');
    
    // Apply header style
    $sheet->getStyle('A2:'.$col.'2')->applyFromArray($headerStyle);

    // Model rows (only those with sales)
    $row = 3;
    foreach ($models as $model) {
        $modelTotal = $modelTotals[$model] ?? 0;
        if ($modelTotal <= 0) continue;

        $sheet->setCellValue('A'.$row, $model);
        $col = 'B';
        
        // Branch quantities
        foreach ($branches as $branch) {
            $qty = 0;
            foreach ($sales as $sale) {
                if ($sale['model'] == $model && $sale['branch'] == $branch) {
                    $qty = $sale['qty'];
                    break;
                }
            }
            $sheet->setCellValue($col.$row, $qty ?: '');
            $col++;
        }
        
        // Total column
        $sheet->setCellValue($col.$row, $modelTotal);
        $col++;
        
        // Cebu column (you may need to adjust this based on your logic)
        $cebuTotal = 0; // Placeholder - adjust as needed
        $sheet->setCellValue($col.$row, $cebuTotal);
        $col++;
        
        // GT column (Grand Total for this model)
        $gtTotal = $modelTotal + $cebuTotal;
        $sheet->setCellValue($col.$row, $gtTotal);
        $col++;
        
        // Percentage column
        $percentage = $grandTotal > 0 ? round(($gtTotal / $grandTotal) * 100, 2) : 0;
        $sheet->setCellValue($col.$row, $percentage.'%');
        
        // Apply cell borders
        $sheet->getStyle('A'.$row.':'.$col.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $row++;
    }

    // SUB-TOTAL row (similar to your example)
    $sheet->setCellValue('A'.$row, 'SUB-TOTAL');
    $col = 'B';
    foreach ($branches as $branch) {
        $sheet->setCellValue($col.$row, $branchTotals[$branch] ?? '');
        $col++;
    }
    $sheet->setCellValue($col.$row, $grandTotal);
    $col++;
    $sheet->setCellValue($col.$row, ''); // Cebu placeholder
    $col++;
    $sheet->setCellValue($col.$row, $grandTotal); // GT
    $col++;
    $sheet->setCellValue($col.$row, '100%');
    
    // Style for sub-total row
    $subTotalStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A'.$row.':'.$col.$row)->applyFromArray($subTotalStyle);
    $row++;

    // QUOTA row (from your quotas data)
    $sheet->setCellValue('A'.$row, 'QUOTA');
    $col = 'B';
    $totalQuota = 0;
    foreach ($branches as $branch) {
        $quota = 0;
        foreach ($quotas as $q) {
            if ($q['branch'] == $branch) {
                $quota = $q['quota'];
                break;
            }
        }
        $sheet->setCellValue($col.$row, $quota);
        $totalQuota += $quota;
        $col++;
    }
    $sheet->setCellValue($col.$row, $totalQuota);
    $col++;
    $sheet->setCellValue($col.$row, ''); // Cebu quota placeholder
    $col++;
    $sheet->setCellValue($col.$row, $totalQuota);
    $col++;
    $sheet->setCellValue($col.$row, '');
    
    // Style for quota row
    $sheet->getStyle('A'.$row.':'.$col.$row)->applyFromArray($subTotalStyle);
    $row++;

    // % row (performance against quota)
    $sheet->setCellValue('A'.$row, '%');
    $col = 'B';
    foreach ($branches as $branch) {
        $branchTotal = $branchTotals[$branch] ?? 0;
        $quota = 0;
        foreach ($quotas as $q) {
            if ($q['branch'] == $branch) {
                $quota = $q['quota'];
                break;
            }
        }
        $percent = $quota > 0 ? round(($branchTotal / $quota) * 100) : 0;
        $sheet->setCellValue($col.$row, $percent.'%');
        $col++;
    }
    $percentTotal = $totalQuota > 0 ? round(($grandTotal / $totalQuota) * 100) : 0;
    $sheet->setCellValue($col.$row, $percentTotal.'%');
    $col++;
    $sheet->setCellValue($col.$row, ''); // Cebu %
    $col++;
    $sheet->setCellValue($col.$row, $percentTotal.'%');
    $col++;
    $sheet->setCellValue($col.$row, '');
    
    // Style for % row
    $sheet->getStyle('A'.$row.':'.$col.$row)->applyFromArray($subTotalStyle);

    // Auto-size columns
    foreach (range('A', $col) as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Freeze the header row
    $sheet->freezePane('A3');

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="sales_summary_'.$year.'_'.($month === 'all' ? 'all_months' : $month).'.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// PDF export function
function exportToPDF($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month) {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    $pdf->SetCreator('SMDI Sales System');
    $pdf->SetAuthor('SMDI');
    $pdf->SetTitle('Sales Summary Report');
    
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'SALES SUMMARY REPORT - ' . strtoupper(date('F Y', strtotime($year.'-'.($month === 'all' ? '01' : $month).'-01'))), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Ln(5);
    
    $colWidth = (270 - 30) / (count($branches) + 1);
    
    // Header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, 'Model/Branch', 1, 0, 'C');
    foreach ($branches as $branch) {
        $pdf->Cell($colWidth, 7, $branch, 1, 0, 'C');
    }
    $pdf->Cell($colWidth, 7, 'Total', 1, 0, 'C');
    $pdf->Cell($colWidth, 7, 'Percentage', 1, 1, 'C');
    
    // Data rows (only models with sales)
    $pdf->SetFont('helvetica', '', 9);
    foreach ($models as $model) {
        $modelTotal = $modelTotals[$model] ?? 0;
        if ($modelTotal <= 0) continue;

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
        $pdf->Cell($colWidth, 6, $modelTotal, 1, 0, 'C');
        
        $percentage = round(calculatePercentage($modelTotal, $branchTotals));
        $pdf->Cell($colWidth, 6, $percentage . '%', 1, 1, 'C');
    }
    
    // Totals
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(30, 6, 'Total', 1, 0, 'L');
    foreach ($branches as $branch) {
        $pdf->Cell($colWidth, 6, $branchTotals[$branch] ?? 0, 1, 0, 'C');
    }
    $pdf->Cell($colWidth, 6, $grandTotal, 1, 0, 'C');
    
    $totalPercentage = round(calculatePercentage($grandTotal, $quotas));
    $pdf->Cell($colWidth, 6, $totalPercentage . '%', 1, 1, 'C');
    
    // Quota row
    $pdf->Cell(30, 6, 'Quota', 1, 0, 'L');
    $totalQuota = 0;
    foreach ($branches as $branch) {
        $quota = 0;
        foreach ($quotas as $q) {
            if ($q['branch'] == $branch) {
                $quota = $q['quota'];
                break;
            }
        }
        $pdf->Cell($colWidth, 6, $quota, 1, 0, 'C');
        $totalQuota += $quota;
    }
    $pdf->Cell($colWidth, 6, $totalQuota, 1, 0, 'C');
    $pdf->Cell($colWidth, 6, '', 1, 1, 'C');
    
    // Percent row
    $pdf->Cell(30, 6, 'Percent', 1, 0, 'L');
    foreach ($branches as $branch) {
        $branchTotal = $branchTotals[$branch] ?? 0;
        $quota = 0;
        foreach ($quotas as $q) {
            if ($q['branch'] == $branch) {
                $quota = $q['quota'];
                break;
            }
        }
        $percent = $quota > 0 ? round(($branchTotal / $quota) * 100) : 0;
        $pdf->Cell($colWidth, 6, $percent . '%', 1, 0, 'C');
    }
    $percentTotal = $totalQuota > 0 ? round(($grandTotal / $totalQuota) * 100) : 0;
    $pdf->Cell($colWidth, 6, $percentTotal . '%', 1, 0, 'C');
    $pdf->Cell($colWidth, 6, '', 1, 1, 'C');
    
    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i:s'), 0, 0, 'C');
    
    $pdf->Output('sales_summary_'.$year.'_'.($month === 'all' ? 'all_months' : $month).'.pdf', 'D');
}

function calculatePercentage($total, $quotas) {
    $totalQuota = 0;

    if (is_array($quotas)) {
        foreach ($quotas as $q) {
            if (isset($q['quota'])) {
                $totalQuota += $q['quota'];
            }
        }
    }

    return $totalQuota > 0 ? round(($total / $totalQuota) * 100) : 0;
}