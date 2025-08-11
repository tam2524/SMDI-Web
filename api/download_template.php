<?php
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

// Create a new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define branch headers
$branches = [
    'MODEL', 'RXS-S', 'RXS-H', 'ANT-1', 'ANT-2', 'SDH', 'SKS', 'JAR-1', 'JAR-2',
    'KAL-1', 'KAL-2', 'ALTA', 'EMAP', 'CUL', 'BAC', 'PAS-1', 'PAS-2',
    'BAL', 'GUIM', 'PEMDI', 'EEM', 'AJUY', 'BAIL', '3SMB', '3SMIN',
    'MAN', 'K-RID', 'IBAJAY', 'NUM', 'HO', 'CEBU'
];
$sheet->fromArray($branches, NULL, 'A1');

// Ordered model list (excluding SUB-TOTALs)
$orderedModels = [
    // Suzuki
    "GSX-250RL/FRLX", "GSX-150", "BIGBIKE", "GSX150FRF NEW", "GSX-S150", "UX110NER", "UB125", "AVENIS", "FU150", "FU150-FI",
    "FW110D", "FW110SD/SC", "DS250RL", "FJ110 LB-2", "FW110D(SMASH FI)", "FJ110LX", "UB125LNM(NEW)", "UK110", "UX110", "UK125", "GD110",

    // Honda
    "GIORNO+", "CCG 125", "CFT125MRCS", "AFB110MDJ", "AFS110MDJ", "AFB110MDH", "CFT125MSJ", "AFS110MCDE", "MRCP", "DIO",
    "MSM", "MRP", "MRS", "CFT125MRCJ", "MSP", "MSS", "AFP110DFP", "MRCP", "AFP110DFR", "ZN125", "PCX160NEW", "PCX160",
    "AFB110MSJ", "AFP110SFR", "AFP110SFP", "CBR650", "CB500", "CB650R", "GL150R", "CBR500", "AIRBLADE 150", "AIRBLADE160",
    "ADV160", "CBR150RMIV/RAP", "BEAT-CSFN/FR/R3/FS/3", "CB150X", "WINNER X", "CRF-150", "CRF300", "CMX500", "XR150",
    "ACB160", "ACB125",

    // Yamaha
    "MIO SPORTY", "MIOI125", "MIO GEAR", "SNIPER", "MIO GRAVIS", "YTX", "YZF R3", "FAZZIO", "XSR", "VEGA", "AEROX",
    "XTZ", "NMAX", "PG-1 BRN1", "MT-15", "FZ", "R15M BNE1/2", "XMAX", "WR155", "SEROW", "T-YAMAHA",

    // Kawasaki
    "CT100 A", "CT100B", "CT125", "CA100AA NEW", "BC175H/MS", "BC175J/NN/SN", "BC175 III ELECT.", "BC175 III KICK",
    "BRUSKY", "NS125", "ELIMINATOR SE", "CT100B", "NINJA ZX 4RR", "Z900 SE", "KLX140", "KLX150", "CT150BA",
    "ROUSER 200", "W800", "VERYS 650", "KLX232", "NINJA ZX-10R", "Z900 SE"
];

// Write model rows
$row = 2;
foreach ($orderedModels as $model) {
    $rowData = array_merge([$model], array_fill(1, count($branches) - 1, ''));
    $sheet->fromArray($rowData, NULL, 'A' . $row);
    $row++;
}

// Output settings
$filename = 'sales_data_template.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Write to output
$writer = new Csv($spreadsheet);
$writer->save('php://output');
exit;
