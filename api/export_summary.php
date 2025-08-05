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
              WHERE 1=1";

$params = [];
$types = '';

if (!empty($fromDate) && !empty($toDate)) {
    $salesQuery .= " AND sales_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $types .= 'ss';
} elseif (!empty($year)) {
    $salesQuery .= " AND YEAR(sales_date) = ?";
    $params[] = $year;
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

        // Define brand models grouping
        $brandModels = [
            "Suzuki" => ["GSX-250RL/FRLX", "GSX-150", "BIGBIKE", "GSX150FRF NEW", "GSX-S150", "UX110NER", "UB125", "AVENIS", "FU150", "FU150-FI", "FW110D", "FW110SD/SC", "DS250RL", "FJ110 LB-2", "FW110D(SMASH FI)", "FJ110LX", "UB125LNM(NEW)", "UK110", "UX110", "UK125", "GD110"],
            "Honda" => ["GIORNO+", "CCG 125", "CFT125MRCS", "AFB110MDJ", "AFS110MDJ", "AFB110MDH", "CFT125MSJ", "AFS110MCDE", "MRCP", "DIO", "MSM", "MRP", "MRS", "CFT125MRCJ", "MSP", "MSS", "AFP110DFP", "MRCP", "AFP110DFR", "ZN125", "PCX160NEW", "PCX160", "AFB110MSJ", "AFP110SFR", "AFP110SFP", "CBR650", "CB500", "CB650R", "GL150R", "CBR500", "AIRBLADE 150", "AIRBLADE160", "ADV160", "CBR150RMIV/RAP", "BEAT-CSFN/FR/R3/FS/3", "CB150X", "WINNER X", "CRF-150", "CRF300", "CMX500", "XR150", "ACB160", "ACB125"],
            "Yamaha" => ["MIO SPORTY", "MIOI125", "MIO GEAR", "SNIPER", "MIO GRAVIS", "YTX", "YZF R3", "FAZZIO", "XSR", "VEGA", "AEROX", "XTZ", "NMAX", "PG-1 BRN1", "MT-15", "FZ", "R15M BNE1/2", "XMAX", "WR155", "SEROW"],
            "Kawasaki" => ["CT100 A", "CT100B", "CT125", "CA100AA NEW", "BC175H/MS", "BC175J/NN/SN", "BC175 III ELECT.", "BC175 III KICK", "BRUSKY", "NS125", "ELIMINATOR SE", "CT100B", "NINJA ZX 4RR", "Z900 SE", "KLX140", "KLX150", "CT150BA", "ROUSER 200", "W800", "VERYS 650", "KLX232", "NINJA ZX-10R", "Z900 SE"]
        ];

        // Mapping from database branch names to report abbreviations
        $branchMapping = [
            'RXS-1' => 'RXS-1',
            'RXS-2' => 'RXS-2',
            'ANTIQUE-1' => 'ANT-1',
            'ANTIQUE-2' => 'ANT-2',
            'DELGADO-1' => 'DEL-1',
            'DELGADO-2' => 'DEL-2',
            'JARO-1' => 'JAR-1',
            'JARO-2' => 'JAR-2',
            'KALIBO-1' => 'KAL-1',
            'KALIBO-2' => 'KAL-2',
            'ALTAVAS' => 'ALTA',
            'EMAP' => 'EMAP',
            'CULASI' => 'CUL',
            'BACOLOD' => 'BAC',
            'PASSI-1' => 'PAS-1',
            'PASSI-2' => 'PAS-2',
            'BALASAN' => 'BAL',
            'GUIMARAS' => 'GUIM',
            'PEMDI' => 'PEMDI',
            'EEMSI' => 'EEM',
            'AJUY' => 'AJUY',
            'BAILAN' => 'BAIL',
            'MINDORO MB' => 'MINDO',
            'MINDORO 3S' => 'MIN',
            'MANSALAY' => 'SALAY',
            'K-RIDERS' => 'K-RID',
            'IBAJAY' => 'IBAJAY',
            'NUMANCIA' => 'NUM',
            'HEADOFFICE' => 'HO',
            'CEBU' => 'CEBU'
        ];

        // Define all branches in report order with abbreviations
        $allBranches = [
            'RXS-1', 'RXS-2', 'ANT-1', 'ANT-2', 'DEL-1', 'DEL-2', 'JAR-1', 'JAR-2',
            'KAL-1', 'KAL-2', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
            'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', 'MINDO', 'MIN',
            'SALAY', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'TTL', 'CEBU'
        ];

        // Set title with date range
        $title = 'SALES SUMMARY REPORT - TALLY BOARD';
        if ($fromDate && $toDate) {
            $title .= ' (' . date('M d, Y', strtotime($fromDate)) . ' to ' . date('M d, Y', strtotime($toDate)) . ')';
        } else {
            $title .= ' - ' . $year;
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($allBranches));
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set header row
        $sheet->setCellValue('A2', 'MODEL');
        $sheet->getColumnDimension('A')->setWidth(20);

        $col = 'B';
        foreach ($allBranches as $branch) {
            $sheet->setCellValue($col.'2', $branch);
            $sheet->getColumnDimension($col)->setWidth(8);
            $col++;
        }
        
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDDDDD']]
        ];
        $sheet->getStyle('A2:'.$lastCol.'2')->applyFromArray($headerStyle);

        // Prepare data matrix and calculate column totals
        $dataMatrix = [];
        $columnTotals = array_fill_keys($allBranches, 0);
        
        // Group models by brand and add to data matrix
        $row = 3;
        foreach ($brandModels as $brand => $models) {
            // Add brand header
            $sheet->setCellValue('A'.$row, $brand);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEEEEEE');
            $row++;
            
            $brandTotal = array_fill_keys($allBranches, 0);
            
            foreach ($models as $model) {
                $dataMatrix[$model] = array_fill_keys($allBranches, '');
                
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model && isset($branchMapping[$sale['branch']])) {
                        $reportBranch = $branchMapping[$sale['branch']];
                        $qty = (int)$sale['qty'];
                        $dataMatrix[$model][$reportBranch] = $qty;
                        $columnTotals[$reportBranch] += $qty;
                        $brandTotal[$reportBranch] += $qty;
                    }
                }
                
                // Calculate TTL for each model
                $branchesForSum = array_slice($allBranches, 0, -2); // Exclude TTL and CEBU
                $ttl = array_sum(array_intersect_key($dataMatrix[$model], array_flip($branchesForSum)));
                $dataMatrix[$model]['TTL'] = $ttl;
                
                // Get CEBU total
                $cebuTotal = 0;
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model && $sale['branch'] == 'CEBU') {
                        $cebuTotal = (int)$sale['qty'];
                        break;
                    }
                }
                $dataMatrix[$model]['CEBU'] = $cebuTotal;
                $columnTotals['CEBU'] += $cebuTotal;
                $brandTotal['CEBU'] += $cebuTotal;
                
                // Write model row
                $sheet->setCellValue('A'.$row, $model);
                
                $col = 'B';
                foreach ($allBranches as $branch) {
                    $value = $dataMatrix[$model][$branch];
                    $sheet->setCellValue($col.$row, $value !== '' ? $value : '');
                    $col++;
                }
                $row++;
            }
            
            // Add brand subtotal row
            $sheet->setCellValue('A'.$row, $brand.' SUB-TOTAL');
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            
            $col = 'B';
            foreach ($allBranches as $branch) {
                $sheet->setCellValue($col.$row, $brandTotal[$branch]);
                $col++;
            }
            $row++;
        }

        // Calculate TTL column total (sum of all regular branches)
        $columnTotals['TTL'] = array_sum(array_slice($columnTotals, 0, -2));

        // Add SUB-TOTAL row
        $sheet->setCellValue('A'.$row, 'SUB-TOTAL');
        $col = 'B';
        foreach ($allBranches as $branch) {
            $sheet->setCellValue($col.$row, $columnTotals[$branch]);
            $col++;
        }
        $row++;

        // Add GRAND TOTAL row (calculated from column totals)
        $sheet->setCellValue('A'.$row, 'GRAND TOTAL');
        $col = 'B';
        foreach ($allBranches as $branch) {
            $sheet->setCellValue($col.$row, $columnTotals[$branch]);
            $col++;
        }
        $row++;

        // Add QUOTA row
        $quotaRow = [];
        $sheet->setCellValue('A'.$row, 'QUOTA');
        $col = 'B';
        foreach ($allBranches as $branch) {
            $quota = 0;
            $dbBranch = array_search($branch, $branchMapping);
            if ($dbBranch !== false) {
                foreach ($quotas as $q) {
                    if ($q['branch'] == $dbBranch) {
                        $quota = (int)$q['quota'];
                        break;
                    }
                }
            }
            $quotaRow[] = $quota;
            $sheet->setCellValue($col.$row, $quota);
            $col++;
        }
        $row++;

        // Add PERCENTAGE row (calculated from GRAND TOTAL and QUOTA)
        $sheet->setCellValue('A'.$row, '%');
        $col = 'B';
        foreach ($allBranches as $index => $branch) {
            $actual = $columnTotals[$branch];
            $quota = $quotaRow[$index] ?: 1; // Avoid division by zero
            $percent = round(($actual / $quota) * 100);
            $sheet->setCellValue($col.$row, $quota > 0 ? $percent.'%' : '');
            $col++;
        }

        // Apply styling
        $sheet->getStyle('A3:'.$lastCol.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']]
        ];
        $sheet->getStyle('A'.($row-3).':'.$lastCol.$row)->applyFromArray($summaryStyle);
        $sheet->freezePane('A3');

        // Output
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="sales_summary_'.date('Ymd_His').'.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

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