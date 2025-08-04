<?php
ini_set('memory_limit', '4096M');
ini_set('max_execution_time', 300);

require_once '../vendor/autoload.php';
include '../api/db_config.php';

// Get and sanitize parameters
$documentType = isset($_GET['documentType']) ? urldecode($_GET['documentType']) : 'default';
$sortBy = isset($_GET['sortBy']) ? urldecode($_GET['sortBy']) : '';
$fromBatch = isset($_GET['fromBatch']) ? $conn->real_escape_string(urldecode($_GET['fromBatch'])) : '';
$toBatch = isset($_GET['toBatch']) ? $conn->real_escape_string(urldecode($_GET['toBatch'])) : '';
$outputFormat = isset($_GET['outputFormat']) ? urldecode($_GET['outputFormat']) : 'pdf';
$fromLetter = isset($_GET['fromLetter']) ? trim(strtoupper(urldecode($_GET['fromLetter']))) : '';
$toLetter = isset($_GET['toLetter']) ? trim(strtoupper(urldecode($_GET['toLetter']))) : '';

// Build query
$query = "SELECT * FROM records";

if ($sortBy === 'customerBatchRange' && !empty($fromBatch) && !empty($toBatch)) {
    $query .= " WHERE batch BETWEEN '$fromBatch' AND '$toBatch'";
} elseif ($sortBy === 'familyName' && !empty($fromLetter) && !empty($toLetter) && ctype_alpha($fromLetter) && ctype_alpha($toLetter)) {
    $query .= " WHERE family_name BETWEEN '$fromLetter' AND '$toLetter%' ORDER BY family_name ASC";
}

$result = $conn->query($query);
if (!$result) {
    die("Database query failed: " . $conn->error);
}

if ($outputFormat === 'pdf') {
    if ($documentType === 'masterlists') {
        // Create PDF in landscape
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SMDI');
        $pdf->SetTitle('Masterlists');
        
        // Set document properties
        $pdf->SetMargins(10, 15, 10, true);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        // Header with logo
        $html = '
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="assets/img/smdi_logo.jpg" alt="SMDI_Logo" style="max-width: 150px; margin-bottom: 5px;"/>
            <h4 style="font-size: 24px; margin: 0; padding: 0; line-height: 1;">Solid Motorcycle Distributors, Inc.</h4>
            <h2 style="font-size: 15px; font-weight: bold; margin: 5px 0 15px 0; padding: 0;">Masterlists</h2>
        </div>';

        // Table styling
        $html .= '
        <style>
            .masterlist-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 9pt;
                page-break-inside: avoid;
            }
            .masterlist-table th {
                background-color: #f5f5f5;
                font-weight: bold;
                text-align: center;
                padding: 4px;
                border: 1px solid #ddd;
            }
            .masterlist-table td {
                padding: 3px;
                border: 1px solid #ddd;
                word-wrap: break-word;
            }
            .remarks-cell {
                max-width: 50mm;
                word-break: break-word;
            }
        </style>';

        // Table header
        $html .= '
        <table class="masterlist-table">
            <thead>
                <tr>
                    <th>Family Name</th>
                    <th>First Name</th>
                    <th>M.I.</th>
                    <th>Plate Number</th>
                    <th>MV File</th>
                    <th>Branch</th>
                    <th>Batch</th>
                    <th>Remarks</th>
                    <th>Date Reg</th>
                </tr>
            </thead>
            <tbody>';

        // Table rows
        while ($row = $result->fetch_assoc()) {
            $remarks = isset($row['remarks']) ? htmlspecialchars($row['remarks']) : '';
            if (strlen($remarks) > 50) {
                $remarks = wordwrap($remarks, 30, "<br>", true);
            }

            $html .= '<tr>
                <td>'.htmlspecialchars($row['family_name']).'</td>
                <td>'.htmlspecialchars($row['first_name']).'</td>
                <td>'.htmlspecialchars($row['middle_name']).'</td>
                <td>'.htmlspecialchars($row['plate_number']).'</td>
                <td>'.htmlspecialchars($row['mv_file']).'</td>
                <td>'.htmlspecialchars($row['branch']).'</td>
                <td>'.htmlspecialchars($row['batch']).'</td>
                <td class="remarks-cell">'.$remarks.'</td>
                <td>'.htmlspecialchars($row['date_reg']).'</td>
            </tr>';
        }

            $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('masterlists.pdf', 'I');
        exit;
    } 
    elseif ($documentType === 'labels') {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SMDI');
        $pdf->SetTitle('Labels');
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        $html = '';
        while ($row = $result->fetch_assoc()) {
            $html .= '
            <div style="
                font-size: 12px;
                text-align: left; 
                padding: 2px;
                border: 1px solid #000; 
                display: inline-block; 
                width: 50%; 
                margin-bottom: 5px;
                height: 30px;
                overflow: hidden;
                line-height: 1.2;
                box-sizing: border-box;
            ">
                <p style="margin: 0;">
                    <span style="font-weight: bold;">'.htmlspecialchars($row['family_name']).', '.htmlspecialchars($row['first_name']).' '.htmlspecialchars($row['middle_name']).'</span>
                    <span style="color: red;"> Branch: </span> 
                    <span style="font-weight: bold;">'.htmlspecialchars($row['branch']).'</span>
                </p>
            </div>';
        }
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('labels.pdf', 'I');
    }
} 
elseif ($outputFormat === 'excel') {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set header
    $headers = ['Family Name', 'First Name', 'Middle Name', 'Plate Number', 'MV File', 'Branch', 'Batch', 'Remarks', 'Date Registered'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Add data
    $row = 2;
    while ($data = $result->fetch_assoc()) {
        $sheet->setCellValue('A'.$row, $data['family_name']);
        $sheet->setCellValue('B'.$row, $data['first_name']);
        $sheet->setCellValue('C'.$row, $data['middle_name']);
        $sheet->setCellValue('D'.$row, $data['plate_number']);
        $sheet->setCellValue('E'.$row, $data['mv_file']);
        $sheet->setCellValue('F'.$row, $data['branch']);
        $sheet->setCellValue('G'.$row, $data['batch']);
        $sheet->setCellValue('H'.$row, $data['remarks']);
        $sheet->setCellValue('I'.$row, $data['date_reg']);
        $row++;
    }

    // Auto-size columns
    foreach(range('A','I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output Excel
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $fileName = ($documentType === 'masterlists') ? 'masterlists.xlsx' : 'labels.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$fileName.'"');
    $writer->save('php://output');
}
?>