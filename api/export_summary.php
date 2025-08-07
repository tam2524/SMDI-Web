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
$format = $_GET['format'] ?? 'excel';
$branchFilter = $_GET['branch'] ?? 'all';
$brandFilter = $_GET['brand'] ?? 'all';
$monthFilter = $_GET['month'] ?? 'all';
$yearFilter = $_GET['year'] ?? date('Y');

// Calculate date range based on month and year filters
if ($monthFilter !== 'all') {
    // Specific month selected
    $fromDate = date("$yearFilter-$monthFilter-01");
    $toDate = date("$yearFilter-$monthFilter-t", strtotime($fromDate));
} else {
    // All months - use entire year
    $fromDate = "$yearFilter-01-01";
    $toDate = "$yearFilter-12-31";
}

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
              WHERE sales_date BETWEEN ? AND ?";

$params = [$fromDate, $toDate];
$types = 'ss';

if ($brandFilter !== 'all') {
    $salesQuery .= " AND brand = ?";
    $params[] = $brandFilter;
    $types .= 's';
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

// Get quotas data for the year (since quotas are yearly)
$quotasQuery = "SELECT branch, quota 
               FROM sales_quotas 
               WHERE year = ?";
$stmt = $conn->prepare($quotasQuery);
$stmt->bind_param('i', $yearFilter);
$stmt->execute();
$quotasResult = $stmt->get_result();

$quotas = [];
while ($row = $quotasResult->fetch_assoc()) {
    $row['quota'] = (int)$row['quota']; // Ensure quota is integer
    $quotas[] = $row;
}

// Process data - only include branches with sales
$allBranches = array_unique(array_column($sales, 'branch'));
$branches = array_intersect($orderedBranches, $allBranches);

// If a specific branch is filtered, only show that branch
if ($branchFilter !== 'all') {
    $branches = [$branchFilter];
}

// Determine if we need to show special columns (TTL, CEBU, GT)
$showTTL = count(array_intersect($allBranches, array_slice($orderedBranches, 0, -3))) > 0;
$showCEBU = in_array('CEBU', $allBranches);
$showGT = $showTTL || $showCEBU;

// Remove TTL, CEBU, GT to avoid duplicates
$displayBranches = array_diff(array_intersect($orderedBranches, $allBranches), ['TTL', 'CEBU', 'GT']);

// Add TTL, CEBU, GT in the desired order
if ($showTTL || $showCEBU || $showGT) {
    if ($showTTL) $displayBranches[] = 'TTL';
    if ($showCEBU) $displayBranches[] = 'CEBU';
    if ($showGT) $displayBranches[] = 'GT';
}


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

// After getting the sales data, check if it's empty
if (empty($sales)) {
    // For AJAX requests, return JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(404); // Not Found status
        echo json_encode([
            'success' => false,
            'message' => 'No sales data found for the selected filters'
        ]);
        exit;
    }
    
    // For direct browser requests
    http_response_code(404);
    die('No sales data found for the selected filters (Month: ' . $monthFilter . ', Year: ' . $yearFilter . ')');
}

// Only proceed with export if there's data
if ($format === 'excel') {
    exportToExcel($displayBranches, $models, $brands, $sales, $quotas, $branchTotals, 
                $modelTotals, $brandBranchTotals, $grandTotal, $yearFilter, 
                $monthFilter, $fromDate, $toDate, $brandFilter, $branchFilter);
    exit;
}

function exportToExcel($branches, $models, $brands, $sales, $quotas, $branchTotals, $modelTotals, $brandBranchTotals, $grandTotal, $year, $month, $fromDate, $toDate, $brandFilter, $branchFilter) {
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title and headers (same as your code)
        $lastCol = Coordinate::stringFromColumnIndex(count($branches) + 2); // +2: 1 for MODEL, 1 for % column
        $headerRow = 4; // You may adjust if filter row exists

        // Freeze header row
        $sheet->freezePane('A' . ($headerRow + 1)); 

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

        // Filter brandModels to only include models with sales
        foreach ($brandModels as $brand => $modelList) {
            $brandModels[$brand] = array_intersect($modelList, $models);
        }

        // Remove brands with no models
        $brandModels = array_filter($brandModels, function($models) {
            return !empty($models);
        });

        $lastCol = Coordinate::stringFromColumnIndex(count($branches) + 1); // +1 for model column

        // Prepare quota data
        $quotaData = [];
        foreach ($quotas as $q) {
            $quotaData[$q['branch']] = (int)$q['quota'];
        }

        // Calculate TTL quota (sum of all regular branches)
       if (in_array('TTL', $branches)) {
    $dataMatrix[$model]['TTL'] = $modelTotal;
    $columnTotals['TTL'] += $modelTotal;
    $brandTotal['TTL'] += $modelTotal;
}


        // Calculate GT quota (TTL quota + CEBU quota)
if (in_array('GT', $branches)) {
    $quotaData['GT'] = 0;

    foreach ($quotaData as $branch => $quota) {
        if (!in_array($branch, ['GT', 'TTL'])) {
            $quotaData['GT'] += $quota;
        }
    }
}

// Calculate TTL quota (sum of all regular branches excluding TTL, CEBU, GT)
if (in_array('TTL', $branches)) {
    $quotaData['TTL'] = 0;
    foreach ($quotaData as $branch => $quota) {
        if (!in_array($branch, ['CEBU', 'GT', 'TTL'])) {
            $quotaData['TTL'] += $quota;
        }
    }
}

        // Set main title with date range
        $title = 'SALES SUMMARY REPORT';
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add date range below title - always show the actual filtered dates
        $dateRangeText = date('M d, Y', strtotime($fromDate)) . ' to ' . date('M d, Y', strtotime($toDate));
        if ($month !== 'all') {
            $dateRangeText = date('F Y', strtotime($fromDate));
        }
        
        $sheet->mergeCells('A2:'.$lastCol.'2');
        $sheet->setCellValue('A2', $dateRangeText);
        $sheet->getStyle('A2')->getFont()->setBold(false)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add filter information
        $filterText = "Filters: ";
        $filters = [];
        if ($brandFilter !== 'all') $filters[] = "Brand: $brandFilter";
        if ($branchFilter !== 'all') $filters[] = "Branch: $branchFilter";
        
        if (!empty($filters)) {
            $filterText .= implode(", ", $filters);
            $sheet->mergeCells('A3:'.$lastCol.'3');
            $sheet->setCellValue('A3', $filterText);
            $sheet->getStyle('A3')->getFont()->setBold(false)->setSize(10);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $headerRow = 4;
        } else {
            $headerRow = 3;
        }
        
        // Set header row
        $sheet->setCellValue('A'.$headerRow, 'MODEL');
        $sheet->getColumnDimension('A')->setWidth(20);

        $col = 'B';
        foreach ($branches as $branch) {
            $sheet->setCellValue($col.$headerRow, $branch);
            $sheet->getColumnDimension($col)->setWidth(8);
            $col++;
        }
        // Add % column header
        $sheet->setCellValue($col.$headerRow, '%');
        $sheet->getColumnDimension($col)->setWidth(8);

        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDDDDD']]
        ];
        $sheet->getStyle('A'.$headerRow.':'.$col.$headerRow)->applyFromArray($headerStyle);

        // Adjust starting row for data
        $row = $headerRow + 1;

        // Define styles
        $highlightYellow = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFF00']
            ]
        ];
        $highlightGreen = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '39FF14']
            ]
        ];
        $highlightGray = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD9D9D9']
            ]
        ];
        // Initialize data structures
        $dataMatrix = [];
        $columnTotals = array_fill_keys($branches, 0);

        // Group models by brand
        foreach ($brandModels as $brand => $models) {
            // Add brand header
            $sheet->setCellValue('A'.$row, $brand);
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);
            $sheet->getStyle('A'.$row.':'.$col.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEEEEEE');
            $row++;
            
            $brandTotal = array_fill_keys($branches, 0);
            $brandCebuTotal = 0;
            $brandGTtotal = 0;
            $brandPercentageSum = 0;
            
            // First pass - calculate brand GT total
            foreach ($models as $model) {
                $modelTotal = 0;
                $modelCebu = 0;
                
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model) {
                        if (in_array($sale['branch'], $branches)) {
                            $modelTotal += (int)$sale['qty'];
                        } elseif ($sale['branch'] == 'CEBU' && in_array('CEBU', $branches)) {
                            $modelCebu += (int)$sale['qty'];
                        }
                    }
                }
                $brandGTtotal += ($modelTotal + (in_array('CEBU', $branches) ? $modelCebu : 0));
            }
            
            // Second pass - output rows
            foreach ($models as $model) {
               $dataMatrix[$model] = array_fill_keys($branches, 0);
    $modelTotal = 0;
    $modelCebu = 0;
                
               foreach ($sales as $sale) {
    if ($sale['model'] == $model && in_array($sale['branch'], $branches)) {
        $branch = $sale['branch'];
        $qty = (int)$sale['qty'];
        $dataMatrix[$model][$branch] = $qty;
        $columnTotals[$branch] += $qty;
        $brandTotal[$branch] += $qty;

        // Exclude CEBU from TTL
        if ($branch !== 'CEBU') {
            $modelTotal += $qty;
        }
    }
}

                // Process CEBU if showing
                if (in_array('CEBU', $branches)) {
                    foreach ($sales as $sale) {
                        if ($sale['model'] == $model && $sale['branch'] == 'CEBU') {
                            $qty = (int)$sale['qty'];
                            $dataMatrix[$model]['CEBU'] = $qty;
                            
                            $brandCebuTotal += $qty;
                            $modelCebu = $qty;
                            break;
                        }
                    }
                }
                
                // Set TTL and GT if showing
                if (in_array('TTL', $branches)) {
                    $dataMatrix[$model]['TTL'] = $modelTotal;
                    $columnTotals['TTL'] += $modelTotal;
                    $brandTotal['TTL'] += $modelTotal;
                }
                
                if (in_array('GT', $branches)) {
                    $dataMatrix[$model]['GT'] = $modelTotal + (in_array('CEBU', $branches) ? $modelCebu : 0);
                    $columnTotals['GT'] += ($modelTotal + (in_array('CEBU', $branches) ? $modelCebu : 0));
                    $brandTotal['GT'] += ($modelTotal + (in_array('CEBU', $branches) ? $modelCebu : 0));
                }
                
                // Write model row
                $sheet->setCellValue('A'.$row, $model);
                $colLetter = 'B';
                foreach ($branches as $branch) {
                    $value = $dataMatrix[$model][$branch];
                    $sheet->setCellValue($colLetter.$row, $value !== 0 ? $value : '');
                    $colLetter++;
                }

                
                
                $modelGT = $modelTotal + (in_array('CEBU', $branches) ? $modelCebu : 0);
                $percentage = ($brandGTtotal > 0) ? ($modelGT / $brandGTtotal) * 100 : 0;
                
                // Rounding: <1% → 0%, ≥1% → whole number
                $roundedPercentage = ($percentage >= 1) ? round($percentage) : 0;
                $sheet->setCellValue($colLetter.$row, $roundedPercentage.'%');
                
                // Accumulate percentages for brand subtotal
                $brandPercentageSum += $roundedPercentage;
                
                $row++;
            }

            // Brand subtotal row
            $sheet->setCellValue('A'.$row, $brand.' SUB-TOTAL');
            $sheet->getStyle('A'.$row)->getFont()->setBold(true);

            $colLetter = 'B';
            foreach ($branches as $branch) {
                $value = $branch == 'CEBU' ? $brandCebuTotal : 
                        ($branch == 'TTL' ? $brandTotal['TTL'] : 
                        ($branch == 'GT' ? $brandTotal['GT'] : $brandTotal[$branch]));
                $sheet->setCellValue($colLetter.$row, $value !== 0 ? $value : '');
                $colLetter++;
            }
            

            // Brand percentage is sum of all model percentages
            $sheet->setCellValue($colLetter.$row, $brandPercentageSum.'%');
            $row++;
        }

        // GRAND TOTAL row
        $sheet->setCellValue('A'.$row, 'GRAND TOTAL');
        $colLetter = 'B';
        foreach ($branches as $branch) {
            $value = $columnTotals[$branch];
            $sheet->setCellValue($colLetter.$row, $value !== 0 ? $value : '');
            $colLetter++;
        }
        $sheet->setCellValue($colLetter.$row, '100%');
        $row++;

        // QUOTA row
        $sheet->setCellValue('A'.$row, 'QUOTA');
        $colLetter = 'B';
        foreach ($branches as $branch) {
            $quota = $quotaData[$branch] ?? 0;
            $sheet->setCellValue($colLetter.$row, $quota > 0 ? $quota : '');
            $colLetter++;
        }
        $sheet->setCellValue($colLetter.$row, '');
        $row++;

        // PERCENTAGE row
        $sheet->setCellValue('A'.$row, '%');
        $colLetter = 'B';
        foreach ($branches as $branch) {
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
        $sheet->setCellValue($colLetter.$row, '');
        $row++;

        // Apply styling
        $sheet->getStyle('A'.$headerRow.':'.$col.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']]
        ];
        $sheet->getStyle('A'.($row-4).':'.$col.($row-1))->applyFromArray($summaryStyle);
        $sheet->freezePane('A' . ($headerRow + 1));


       // Highlight TTL column
        $ttlIndex = array_search('TTL', $branches);
        if ($ttlIndex !== false) {
            $ttlColLetter = Coordinate::stringFromColumnIndex($ttlIndex + 1); // +2 to account for MODEL in col A
            $sheet->getStyle($ttlColLetter . ($headerRow) . ':' . $ttlColLetter . ($sheet->getHighestRow()))
                ->applyFromArray($highlightYellow);
        }

        // Loop back through rows to apply highlights
        for ($i = $headerRow + 1; $i <= $sheet->getHighestRow(); $i++) {
            $rowLabel = $sheet->getCell('A' . $i)->getValue();

            if (strpos($rowLabel, 'SUB-TOTAL') !== false) {
                $sheet->getStyle('A' . $i . ':' . $lastCol . $i)->applyFromArray($highlightYellow);
            } elseif ($rowLabel === 'GRAND TOTAL') {
                $sheet->getStyle('A' . $i . ':' . $lastCol . $i)->applyFromArray($highlightGreen);
            }
        }

        // Center-align all numeric columns (B to last column) across all data rows
$dataStartRow = $headerRow + 1;
$dataEndRow = $sheet->getHighestRow();
$lastDataColLetter = Coordinate::stringFromColumnIndex(count($branches) + 2); // +2 for MODEL and %

$sheet->getStyle("B{$dataStartRow}:{$lastDataColLetter}{$dataEndRow}")
    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);



      // Output
        if (ob_get_length()) ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        
        // Create filename with filters
        $filenameParts = ["sales_summary"];
        if ($month !== 'all') $filenameParts[] = date('F', mktime(0, 0, 0, $month, 1));
        $filenameParts[] = $year;
        if ($brandFilter !== 'all') $filenameParts[] = $brandFilter;
        if ($branchFilter !== 'all') $filenameParts[] = $branchFilter;
        
        $filename = implode('_', $filenameParts) . '.xlsx';
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    } catch (Exception $e) {
        die('Error generating Excel file: ' . $e->getMessage());
    }
}