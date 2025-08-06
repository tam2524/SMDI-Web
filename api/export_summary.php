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
    'SALAY', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'TTL', 'CEBU', 'GT'
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
    $row['qty'] = (int)$row['qty']; // Ensure qty is integer
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
    $row['quota'] = (int)$row['quota']; // Ensure quota is integer
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
            "Honda" => ["GIORNO+", "CCG 125", "CFT125MRCS", "AFB110MDJ", "AFS110MDJ", "AFB110MDH", "CFT125MSJ", "AFS110MCDE", "MRCP", "DIO", "MSM", "MRP", "MRS", "CFT125MRCJ", "MSP", "MSS", "AFP110DFP",  "AFP110DFR", "ZN125", "PCX160NEW", "PCX160", "AFB110MSJ", "AFP110SFR", "AFP110SFP", "CBR650", "CB500", "CB650R", "GL150R", "CBR500", "AIRBLADE 150", "AIRBLADE160", "ADV160", "CBR150RMIV/RAP", "BEAT-CSFN/FR/R3/FS/3", "CB150X", "WINNER X", "CRF-150", "CRF300", "CMX500", "XR150", "ACB160", "ACB125"],
            "Yamaha" => ["MIO SPORTY", "MIOI125", "MIO GEAR", "SNIPER", "MIO GRAVIS", "YTX", "YZF R3", "FAZZIO", "XSR", "VEGA", "AEROX", "XTZ", "NMAX", "PG-1 BRN1", "MT-15", "FZ", "R15M BNE1/2", "XMAX", "WR155", "SEROW"],
            "Kawasaki" => ["CT100 A", "CT100B", "CT125", "CA100AA NEW", "BC175H/MS", "BC175J/NN/SN", "BC175 III ELECT.", "BC175 III KICK", "BRUSKY", "NS125", "ELIMINATOR SE", "NINJA ZX 4RR", "KLX140", "KLX150", "CT150BA", "ROUSER 200", "W800", "VERYS 650", "KLX232", "NINJA ZX-10R", "Z900 SE"]
        ];
  $reportBranches = [
            'RXS-1', 'RXS-2', 'ANT-1', 'ANT-2', 'DEL-1', 'DEL-2', 'JAR-1', 'JAR-2',
            'KAL-1', 'KAL-2', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
            'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', 'MINDO', 'MIN',
            'SALAY', 'K-RID', 'IBAJAY', 'NUM', 'HO'
        ];

        // Correct column order: regular branches → TTL → CEBU → GT → % of GT
        $allBranches = array_merge($reportBranches, ['TTL', 'CEBU', 'GT']);
        $lastCol = Coordinate::stringFromColumnIndex(count($allBranches) + 2); // +2 because we're adding % column
        
        // Prepare quota data
        $quotaData = [];
        foreach ($quotas as $q) {
            $quotaData[$q['branch']] = (int)$q['quota'];
        }

        // Calculate TTL quota (sum of all regular branches)
        $quotaData['TTL'] = 0;
        foreach ($reportBranches as $branch) {
            $quotaData['TTL'] += $quotaData[$branch] ?? 0;
        }

        // Calculate GT quota (TTL quota + CEBU quota)
        $quotaData['GT'] = ($quotaData['TTL'] ?? 0) + ($quotaData['CEBU'] ?? 0);

        // Set title with date range
        $title = 'SALES SUMMARY REPORT - TALLY BOARD';
        
        if ($fromDate && $toDate) {
            $fromDateStr = date('M d, Y', strtotime($fromDate));
            $toDateStr = date('M d, Y', strtotime($toDate));
            $title .= ' (' . $fromDateStr . ' to ' . $toDateStr . ')';
        } else {
            $title .= ' - ' . $year;
        }

        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set header row with corrected column order
        $sheet->setCellValue('A2', 'MODEL');
        $sheet->getColumnDimension('A')->setWidth(20);

        $col = 'B';
        foreach ($allBranches as $branch) {
            $sheet->setCellValue($col.'2', $branch);
            $sheet->getColumnDimension($col)->setWidth(8);
            $col++;
        }
        // Add % column header
        $sheet->setCellValue($col.'2', '%');
        $sheet->getColumnDimension($col)->setWidth(8);
        
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDDDDD']]
        ];
        $sheet->getStyle('A2:'.$col.'2')->applyFromArray($headerStyle);

        // Initialize data structures
        $dataMatrix = [];
        $columnTotals = array_fill_keys($allBranches, 0);
        $brandGTtotals = []; // To store each brand's GT total for percentage calculation
        $row = 3;

        // Group models by brand
        foreach ($brandModels as $brand => $models) {
            // Add brand header
            $sheet->setCellValue('A'.$row, $brand);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->getStyle('A'.$row.':'.$col.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEEEEEE');
            $row++;
            
            $brandTotal = array_fill_keys($allBranches, 0);
            $brandCebuTotal = 0;
            $brandGTtotal = 0; // To calculate this brand's GT total
            
            foreach ($models as $model) {
                $dataMatrix[$model] = array_fill_keys($allBranches, 0);
                $modelTotal = 0;
                $modelCebu = 0;
                
                // Process regular branches (for TTL calculation)
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model && in_array($sale['branch'], $reportBranches)) {
                        $branch = $sale['branch'];
                        $qty = (int)$sale['qty'];
                        $dataMatrix[$model][$branch] = $qty;
                        $columnTotals[$branch] += $qty;
                        $brandTotal[$branch] += $qty;
                        $modelTotal += $qty;
                    }
                }
                
                // Process CEBU separately
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model && $sale['branch'] == 'CEBU') {
                        $qty = (int)$sale['qty'];
                        $dataMatrix[$model]['CEBU'] = $qty;
                        $columnTotals['CEBU'] += $qty;
                        $brandCebuTotal += $qty;
                        $modelCebu = $qty;
                        break;
                    }
                }
                
                // Set TTL (sum of regular branches only)
                $dataMatrix[$model]['TTL'] = $modelTotal;
                $columnTotals['TTL'] += $modelTotal;
                $brandTotal['TTL'] += $modelTotal;
                
               // Set GT (TTL + CEBU)
    $gt = $modelTotal + $modelCebu;
    $dataMatrix[$model]['GT'] = $gt;
    $columnTotals['GT'] += $gt;
    $brandTotal['GT'] += $gt;
    $brandGTtotal += $gt;
    
    // Write model row
    $sheet->setCellValue('A'.$row, $model);
    $colLetter = 'B';
    foreach ($allBranches as $branch) {
        $value = $dataMatrix[$model][$branch];
        $sheet->setCellValue($colLetter.$row, $value !== 0 ? $value : '');
        $colLetter++;
    }
                
                // Calculate percentage of this model's GT against brand's GT total
    $percentage = ($brandGTtotal > 0) ? ($gt / $brandGTtotal) * 100 : 0;
    $sheet->setCellValue($colLetter.$row, round($percentage, 1).'%');
    
    $row++;
            }
            
            // For brand subtotal row:
$sheet->setCellValue('A'.$row, $brand.' SUB-TOTAL');
$colLetter = 'B';
foreach ($allBranches as $branch) {
    if (in_array($branch, $reportBranches)) {
        $sheet->setCellValue($colLetter.$row, $brandTotal[$branch]);
    } elseif ($branch == 'TTL') {
        $sheet->setCellValue($colLetter.$row, $brandTotal['TTL']);
    } elseif ($branch == 'CEBU') {
        $sheet->setCellValue($colLetter.$row, $brandCebuTotal);
    } elseif ($branch == 'GT') {
        $sheet->setCellValue($colLetter.$row, $brandGTtotal);
    }
    $colLetter++;
}

// Brand GT percentage is always 100%
$sheet->setCellValue($colLetter.$row, '100%');
$row++;
        }

        // Add GRAND TOTAL row with corrected column order
        $sheet->setCellValue('A'.$row, 'GRAND TOTAL');
        $colLetter = 'B';
        foreach ($allBranches as $branch) {
            if (in_array($branch, $reportBranches)) {
                $sheet->setCellValue($colLetter.$row, $columnTotals[$branch]);
            } elseif ($branch == 'TTL') {
                $sheet->setCellValue($colLetter.$row, $columnTotals['TTL']);
            } elseif ($branch == 'CEBU') {
                $sheet->setCellValue($colLetter.$row, $columnTotals['CEBU']);
            } elseif ($branch == 'GT') {
                $sheet->setCellValue($colLetter.$row, $columnTotals['TTL'] + $columnTotals['CEBU']);
            }
            $colLetter++;
        }
        
        // Add 100% for grand total percentage
        $sheet->setCellValue($colLetter.$row, '100%');
        $row++;

        // Add QUOTA row (now includes TTL and GT)
        $sheet->setCellValue('A'.$row, 'QUOTA');
        $colLetter = 'B';
        foreach ($allBranches as $branch) {
            $quota = $quotaData[$branch] ?? 0;
            $sheet->setCellValue($colLetter.$row, $quota > 0 ? $quota : '');
            $colLetter++;
        }
        // Empty cell for percentage column
        $sheet->setCellValue($colLetter.$row, '');
        $row++;

        // Add PERCENTAGE row (now includes TTL and GT)
        $sheet->setCellValue('A'.$row, '%');
        $colLetter = 'B';
        foreach ($allBranches as $branch) {
            $actual = $columnTotals[$branch] ?? 0;
            $quota = $quotaData[$branch] ?? 0;
            
            if ($quota > 0) {
                $percent = round(($actual / $quota) * 100);
                $sheet->setCellValue($colLetter.$row, $percent.'%');
            } else {
                $sheet->setCellValue($colLetter.$row, '');
            }
            $colLetter++;
        }
        // Empty cell for percentage column
        $sheet->setCellValue($colLetter.$row, '');
        $row++;

        // Apply styling
        $sheet->getStyle('A3:'.$col.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']]
        ];
        $sheet->getStyle('A'.($row-4).':'.$col.($row-1))->applyFromArray($summaryStyle);
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