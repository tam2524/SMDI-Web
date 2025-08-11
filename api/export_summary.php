<?php
require_once '../vendor/autoload.php';
include '../api/db_config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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
    'RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SDS', 'JAR-1', 'JAR-2',
    'SKM', 'SKS', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
    'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', '3SMB', '3SMIN',
    'MAN', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'TTL', 'CEBU', 'GT'
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
        
        // Remove the default sheet since we'll create our own
        $spreadsheet->removeSheetByIndex(0);

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

        // Prepare quota data
        $quotaData = [];
        foreach ($quotas as $q) {
            $quotaData[$q['branch']] = (int)$q['quota'];
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

        // Calculate GT quota (sum of all quotas)
        if (in_array('GT', $branches)) {
            $quotaData['GT'] = 0;
            foreach ($quotaData as $branch => $quota) {
                if (!in_array($branch, ['GT', 'TTL'])) {
                    $quotaData['GT'] += $quota;
                }
            }
        }

        // Create a summary sheet first
        $summarySheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Summary');
        $spreadsheet->addSheet($summarySheet, 0);
        $summarySheet = $spreadsheet->getSheet(0);
        
        // Set up summary sheet (same as before)
        $lastCol = Coordinate::stringFromColumnIndex(count($branches) + 1);
        
        // Set main title with date range
        $title = 'SALES SUMMARY REPORT';
        $summarySheet->mergeCells('A1:'.$lastCol.'1');
        $summarySheet->setCellValue('A1', $title);
        $summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $summarySheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add date range below title
        $dateRangeText = date('M d, Y', strtotime($fromDate)) . ' to ' . date('M d, Y', strtotime($toDate));
        if ($month !== 'all') {
            $dateRangeText = date('F Y', strtotime($fromDate));
        }
        
        $summarySheet->mergeCells('A2:'.$lastCol.'2');
        $summarySheet->setCellValue('A2', $dateRangeText);
        $summarySheet->getStyle('A2')->getFont()->setBold(false)->setSize(18);
        $summarySheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add filter information
        $filterText = "Filters: ";
        $filters = [];
        if ($brandFilter !== 'all') $filters[] = "Brand: $brandFilter";
        if ($branchFilter !== 'all') $filters[] = "Branch: $branchFilter";
        
        if (!empty($filters)) {
            $filterText .= implode(", ", $filters);
            $summarySheet->mergeCells('A3:'.$lastCol.'3');
            $summarySheet->setCellValue('A3', $filterText);
            $summarySheet->getStyle('A3')->getFont()->setBold(false)->setSize(18);
            $summarySheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $summaryStartRow = 4; // Start of summary table
        } else {
            $summaryStartRow = 3; // Start of summary table
        }

        // Summary table title
        $summarySheet->setCellValue('A'.$summaryStartRow, 'SUMMARY');
        $summarySheet->mergeCells('A'.$summaryStartRow.':'.$lastCol.$summaryStartRow);
        $summarySheet->getStyle('A'.$summaryStartRow)->getFont()->setBold(true)->setSize(18);

        $summarySheet->getStyle('A'.$summaryStartRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summaryStartRow++;

        // Summary table headers
        $summarySheet->setCellValue('A'.$summaryStartRow, 'BRAND');
        $colLetter = 'B';
        foreach ($branches as $branch) {
            $summarySheet->setCellValue($colLetter.$summaryStartRow, $branch);
            $colLetter++;
        }
        
        $numberStyle = [
    'font' => ['size' => 18], // Set font size to 12 for numbers
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDDDDDD']]
        ];
        // $summarySheet->getStyle('A'.$summaryStartRow.':'.$colLetter.$summaryStartRow)->applyFromArray($headerStyle);
        
        $summaryStartRow++;

        // Initialize data structures for collecting brand totals
        $brandTotalsForSummary = [];
        $columnTotals = array_fill_keys($branches, 0);

        // Process each brand to collect totals for summary
        foreach ($brandModels as $brand => $models) {
            $brandTotal = array_fill_keys($branches, 0);
            $brandCebuTotal = 0;
            $brandPercentageSum = 0;
            
            foreach ($models as $model) {
                $modelTotal = 0;
                $modelCebu = 0;
                $modelTTL = 0;
                $modelGT = 0;

                foreach ($sales as $sale) {
                    if ($sale['model'] == $model) {
                        $branch = $sale['branch'];
                        $qty = (int)$sale['qty'];

                        if (in_array($branch, $branches)) {
                            $brandTotal[$branch] += $qty;
                            $columnTotals[$branch] += $qty;

                            if ($branch !== 'CEBU' && $branch !== 'TTL' && $branch !== 'GT') {
                                $modelTTL += $qty;
                            }

                            if ($branch === 'CEBU') {
                                $brandCebuTotal += $qty;
                                $modelCebu = $qty;
                            }
                        }
                    }
                }

                // Calculate TTL and GT values as before
                if (in_array('TTL', $branches)) {
                    $brandTotal['TTL'] += $modelTTL;
                }

                if (in_array('GT', $branches)) {
                    $modelGT = $modelTTL + (in_array('CEBU', $branches) ? $modelCebu : 0);
                    $brandTotal['GT'] += $modelGT;
                }
            }

            // Store brand totals for summary table
            $brandTotalsForSummary[$brand] = [
                'values' => $brandTotal,
                'cebu' => $brandCebuTotal,
                'percentage' => ($grandTotal > 0) ? round(($brandTotal['GT'] ?? 0) / $grandTotal * 100) : 0
            ];

            // Add TTL and GT to columnTotals for GRAND TOTAL row
            if (isset($brandTotal['TTL'])) {
                $columnTotals['TTL'] += $brandTotal['TTL'];
            }
            if (isset($brandTotal['GT'])) {
                $columnTotals['GT'] += $brandTotal['GT'];
            }
        }

        // Add brand subtotal rows to summary table
        foreach ($brandTotalsForSummary as $brand => $data) {
            $summarySheet->setCellValue('A'.$summaryStartRow, $brand.' SUB-TOTAL');
            $colLetter = 'B';
            foreach ($branches as $branch) {
                // For CEBU branch, use the special cebu total we calculated
                $value = $branch === 'CEBU' ? $data['cebu'] : ($data['values'][$branch] ?? 0);
                $summarySheet->setCellValue($colLetter.$summaryStartRow, $value !== 0 ? $value : '');
                $colLetter++;
            }
            $summaryStartRow++;
        }

        // GRAND TOTAL row in summary table
        $summarySheet->setCellValue('A'.$summaryStartRow, 'GRAND TOTAL');
        $colLetter = 'B';
        foreach ($branches as $branch) {
            $value = $columnTotals[$branch];
            $summarySheet->setCellValue($colLetter.$summaryStartRow, $value !== 0 ? $value : '');
            $colLetter++;
        }
        $summaryStartRow++;

        // QUOTA row in summary table
        $summarySheet->setCellValue('A'.$summaryStartRow, 'QUOTA');
        $colLetter = 'B';
        foreach ($branches as $branch) {
            $quota = $quotaData[$branch] ?? 0;
            $summarySheet->setCellValue($colLetter.$summaryStartRow, $quota > 0 ? $quota : '');
            $colLetter++;
        }
        // $summarySheet->setCellValue($colLetter.$summaryStartRow, '');
        $summaryStartRow++;

        // PERCENTAGE row in summary table
        $summarySheet->setCellValue('A'.$summaryStartRow, '%');
        $colLetter = 'B';
        foreach ($branches as $branch) {
            $actual = $columnTotals[$branch] ?? 0;
            $quota = $quotaData[$branch] ?? 0;
            
            if ($quota > 0) {
                $percent = round(($actual / $quota) * 100);
                $summarySheet->setCellValue($colLetter.$summaryStartRow, $percent.'%');
            } else {
                $summarySheet->setCellValue($colLetter.$summaryStartRow, '');
            }
            $colLetter++;
        }
        // $summarySheet->setCellValue($colLetter.$summaryStartRow, '');

        // Style the summary table
        $summaryStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEEEEEE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $lastBranchCol = Coordinate::stringFromColumnIndex(count($branches) + 1);
$summarySheet->getStyle('A'.($summaryStartRow-6-count($brandModels)).':'.$lastBranchCol.($summaryStartRow))->applyFromArray($summaryStyle);
// Apply number style to all numeric cells in the summary table
$summarySheet->getStyle('B'.($summaryStartRow-6-count($brandModels)).':'.$colLetter.($summaryStartRow))
    ->applyFromArray($numberStyle);
        // After creating the summary sheet and adding all the data...

// ====== OPTIMIZE SUMMARY SHEET LAYOUT ======

// Auto-size columns first to get proper widths
foreach (range('A', $lastCol) as $columnID) {
    $summarySheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Calculate how much space we have on an A4 page
$pageWidth = 1123; // A4 width in points (landscape)
$pageHeight = 794;  // A4 height in points (landscape)
$marginWidth = 100; // Approximate margin width
$marginHeight = 100; // Approximate margin height

// Calculate available width and adjust columns
$usedWidth = 0;
foreach (range('A', $lastCol) as $columnID) {
    $usedWidth += $summarySheet->getColumnDimension($columnID)->getWidth();
}

// If we have extra width, distribute it to columns
if ($usedWidth < ($pageWidth - $marginWidth)) {
    $extraWidth = ($pageWidth - $marginWidth - $usedWidth) / count($branches);
    foreach (range('A', $lastCol) as $columnID) {
        $currentWidth = $summarySheet->getColumnDimension($columnID)->getWidth();
        $summarySheet->getColumnDimension($columnID)->setWidth($currentWidth + $extraWidth);
    }
}

// Adjust row heights to maximize vertical space
$lastRow = $summarySheet->getHighestRow();
$headerRows = 4; // Title, date, filters, and header row
$dataRows = $lastRow - $headerRows;

if ($dataRows > 0) {
    $availableHeight = $pageHeight - $marginHeight;
    $rowHeight = $availableHeight / $dataRows;
    
    // Don't make rows too tall or too small
    $rowHeight = min($rowHeight, 30); // Max 30 points per row
    $rowHeight = max($rowHeight, 15); // Min 15 points per row
    
    for ($row = $headerRows + 1; $row <= $lastRow; $row++) {
        $summarySheet->getRowDimension($row)->setRowHeight($rowHeight);
    }
}

// Set page setup for summary sheet to maximize space
$summarySheet->getPageSetup()
    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
    ->setFitToWidth(1)
    ->setFitToHeight(0)
    ->setPaperSize(PageSetup::PAPERSIZE_A4)
    ->setHorizontalCentered(true)
    ->setVerticalCentered(false);

// Freeze the header row
$summarySheet->freezePane('A' . ($headerRows + 1));

// Apply borders to all data cells
$summarySheet->getStyle('A' . ($headerRows + 1) . ':' . $lastCol . $lastRow)
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

// Center-align all numeric cells
$summarySheet->getStyle('B' . ($headerRows + 1) . ':' . $lastCol . $lastRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Make sure the summary sheet is the first sheet
$spreadsheet->setActiveSheetIndex(0);
        
        // Now create detailed sheets for each brand
        foreach ($brandModels as $brand => $models) {
            $brandSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $brand);
            $spreadsheet->addSheet($brandSheet);
            $sheet = $spreadsheet->getSheetByName($brand);
            
            // Set up the brand sheet
            // $lastCol = Coordinate::stringFromColumnIndex(count($branches) + 1);
            $lastCol = Coordinate::stringFromColumnIndex(count($branches));
            // Title
            $sheet->mergeCells('A1:'.$lastCol.'1');
            $sheet->setCellValue('A1', $brand . ' SALES DETAILS');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Date range
            $sheet->mergeCells('A2:'.$lastCol.'2');
            $sheet->setCellValue('A2', $dateRangeText);
            $sheet->getStyle('A2')->getFont()->setBold(false)->setSize(18);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Filter info if any
            if ($branchFilter !== 'all') {
                $sheet->mergeCells('A3:'.$lastCol.'3');
                $sheet->setCellValue('A3', 'Branch: ' . $branchFilter);
                $sheet->getStyle('A3')->getFont()->setBold(false)->setSize(18);
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $startRow = 4;
            } else {
                $startRow = 3;
            }
            
            // Headers
            $sheet->setCellValue('A'.$startRow, 'MODEL');
            $colLetter = 'B';
            foreach ($branches as $branch) {
                $sheet->setCellValue($colLetter.$startRow, $branch);
                $sheet->getStyle($colLetter.$startRow)->getFont()->setBold(false)->setSize(18);

                $colLetter++;
            }
            $sheet->setCellValue($colLetter.$startRow, '%');
            
            // Style headers
            $sheet->getStyle('A'.$startRow.':'.$colLetter.$startRow)->applyFromArray($headerStyle);
            $sheet->getStyle('A'.$startRow)->getFont()->setBold(false)->setSize(18);

            $startRow++;
            
            // Calculate brand totals
            $brandTotal = array_fill_keys($branches, 0);
            $brandGTTotal = 0;
            
            foreach ($models as $model) {
                $dataMatrix = array_fill_keys($branches, 0);
                $modelTotal = 0;
                $modelCebu = 0;
                $modelTTL = 0;
                $modelGT = 0;
                
                foreach ($sales as $sale) {
                    if ($sale['model'] == $model && in_array($sale['branch'], $branches)) {
                        $branch = $sale['branch'];
                        $qty = (int)$sale['qty'];
                        $dataMatrix[$branch] = $qty;
                        $brandTotal[$branch] += $qty;
                        
                        if ($branch !== 'CEBU' && $branch !== 'TTL' && $branch !== 'GT') {
                            $modelTTL += $qty;
                        }
                    } elseif ($sale['model'] == $model && $sale['branch'] == 'CEBU' && in_array('CEBU', $branches)) {
                        $modelCebu = (int)$sale['qty'];
                        $dataMatrix['CEBU'] = $modelCebu;
                        $brandTotal['CEBU'] += $modelCebu;
                    }
                }
                
                // Calculate TTL for this model
if (in_array('TTL', $branches)) {
    $dataMatrix['TTL'] = $modelTTL;
    $brandTotal['TTL'] += $modelTTL;
}

// Calculate GT as TTL + CEBU
if (in_array('GT', $branches)) {
    $ttlValue = isset($dataMatrix['TTL']) ? $dataMatrix['TTL'] : 0;
    $cebuValue = (in_array('CEBU', $branches) && isset($dataMatrix['CEBU'])) ? $dataMatrix['CEBU'] : 0;

    $dataMatrix['GT'] = $ttlValue + $cebuValue;
    $brandTotal['GT'] += $dataMatrix['GT'];
    $brandGTTotal += $dataMatrix['GT'];
}

                // Write model row
                $sheet->setCellValue('A'.$startRow, $model);
                $colLetter = 'B';
                foreach ($branches as $branch) {
                    $value = $dataMatrix[$branch];
                    $sheet->setCellValue($colLetter.$startRow, $value !== 0 ? $value : '');
                    $colLetter++;
                }

                $modelGT = $modelTTL + (in_array('CEBU', $branches) ? $modelCebu : 0);
                $percentage = ($brandGTTotal > 0) ? ($modelGT / $brandGTTotal) * 100 : 0;
                $roundedPercentage = ($percentage >= 1) ? round($percentage) : 0;
                $sheet->setCellValue($colLetter.$startRow, $roundedPercentage.'%');
                
                $startRow++;
            }
            
            // Add brand total row
            $sheet->setCellValue('A'.$startRow, 'TOTAL');
            $colLetter = 'B';
            foreach ($branches as $branch) {
                $value = $brandTotal[$branch];
                $sheet->setCellValue($colLetter.$startRow, $value !== 0 ? $value : '');
                $colLetter++;
            }
            
            $percentage = ($grandTotal > 0) ? ($brandGTTotal / $grandTotal) * 100 : 0;
            $roundedPercentage = ($percentage >= 1) ? round($percentage) : 0;
            $sheet->setCellValue($colLetter.$startRow, $roundedPercentage.'%');
            
            // Style the total row
            $highlightYellow = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFF00']]
            ];
            $sheet->getStyle('A'.$startRow.':'.$colLetter.$startRow)->applyFromArray($highlightYellow);
            
            // Apply borders to all data
            $sheet->getStyle('A4:'.$colLetter.$startRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            // Center-align all numeric cells
            $sheet->getStyle('B4:'.$colLetter.$startRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
                $sheet->getStyle('B4:'.$colLetter.$startRow)
    ->applyFromArray($numberStyle);
            // Auto-size columns
            foreach (range('A', $colLetter) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            
            // Set page setup for brand sheet - maximize to one A4 page
            $sheet->getPageSetup()
                ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                ->setFitToWidth(1)
                ->setFitToHeight(0)
                ->setPaperSize(PageSetup::PAPERSIZE_A4);
                
            // Set row heights to maximize space
            $rowCount = $sheet->getHighestRow();
            $pageHeight = 842; // A4 height in points (landscape)
            $marginHeight = 100; // Approximate margin height
            $availableHeight = $pageHeight - $marginHeight;
            $rowHeight = $availableHeight / ($rowCount - 3); // Subtract header rows
            
            for ($row = 4; $row <= $rowCount; $row++) {
                $sheet->getRowDimension($row)->setRowHeight($rowHeight);
            }
            
            // Freeze headers
            $sheet->freezePane('A4');
        }

        // Set the first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

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