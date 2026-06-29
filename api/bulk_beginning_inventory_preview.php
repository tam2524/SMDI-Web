<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$role = $_SESSION['position'] ?? $_SESSION['user_role'] ?? '';
$allowedRoles = ['spareparts-admin', 'spareparts-owner', 'spareparts-warehouse', 'spareparts-sales', 'spareparts-retail'];
$hasAccess = in_array(strtolower(trim($role)), $allowedRoles);
if (!$hasAccess) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit();
}

$currentBranch = trim($_SESSION['user_branch'] ?? 'HEADOFFICE');

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please select a valid Excel file.']);
    exit();
}

$fileTmpPath = $_FILES['excel_file']['tmp_name'];
$fileName = $_FILES['excel_file']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$allowedExtensions = ['xlsx', 'xls', 'csv'];
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported file format. Please upload .xlsx, .xls, or .csv.']);
    exit();
}

try {
    $spreadsheet = IOFactory::load($fileTmpPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    if (count($rows) <= 1) {
        echo json_encode(['success' => false, 'message' => 'The uploaded file is empty or has no data.']);
        exit();
    }

    // Parse header row
    $headerRow = array_shift($rows);
    $headerRow = array_map(function($val) {
        return strtolower(trim(str_replace([' ', '_'], '', $val)));
    }, $headerRow);

    // Find column indices
    $partNoIdx = array_search('partnumber', $headerRow);
    if ($partNoIdx === false) $partNoIdx = array_search('partno', $headerRow);
    if ($partNoIdx === false) {
        foreach ($headerRow as $key => $val) {
            if (strpos($val, 'part') !== false) {
                $partNoIdx = $key;
                break;
            }
        }
    }

    $brandIdx = array_search('brandname', $headerRow);
    if ($brandIdx === false) $brandIdx = array_search('brand', $headerRow);

    $descIdx = array_search('description', $headerRow);
    if ($descIdx === false) $descIdx = array_search('partdescription', $headerRow);
    if ($descIdx === false) $descIdx = array_search('name', $headerRow);
    if ($descIdx === false) $descIdx = array_search('partname', $headerRow);

    $qtyIdx = array_search('qty', $headerRow);
    if ($qtyIdx === false) $qtyIdx = array_search('quantity', $headerRow);
    if ($qtyIdx === false) $qtyIdx = array_search('currentstock', $headerRow);
    if ($qtyIdx === false) $qtyIdx = array_search('stock', $headerRow);

    $costIdx = array_search('cost', $headerRow);
    if ($costIdx === false) $costIdx = array_search('buyingcost', $headerRow);
    if ($costIdx === false) $costIdx = array_search('purchaseprice', $headerRow);
    if ($costIdx === false) $costIdx = array_search('suppliercost', $headerRow);

    if ($partNoIdx === false || $qtyIdx === false || $costIdx === false) {
        echo json_encode(['success' => false, 'message' => 'Could not find required columns in the Excel header. Ensure columns include Part Number, Qty, and Cost.']);
        exit();
    }

    $previewData = [];
    $validUpdates = [];

    foreach ($rows as $rowNum => $row) {
        $partNo = trim($row[$partNoIdx] ?? '');
        if ($partNo === '') continue;

        $brand = $brandIdx !== false ? trim($row[$brandIdx] ?? '') : '';
        $desc = $descIdx !== false ? trim($row[$descIdx] ?? '') : '';
        $qty = $qtyIdx !== false ? (int)$row[$qtyIdx] : 0;
        $cost = $costIdx !== false ? (float)str_replace([',', '₱', ' '], '', $row[$costIdx] ?? 0) : 0.00;

        if ($qty <= 0) {
            $previewData[] = [
                'part_no' => $partNo,
                'brand' => $brand,
                'description' => $desc,
                'qty' => $qty,
                'cost' => $cost,
                'status' => 'Invalid (Qty must be > 0)'
            ];
            continue;
        }

        // Check if already exists in this branch
        $checkStmt = $conn->prepare("SELECT id, brand, description FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $checkStmt->bind_param('ss', $partNo, $currentBranch);
        $checkStmt->execute();
        $res = $checkStmt->get_result();

        $status = 'New';
        if ($res->num_rows > 0) {
            $existing = $res->fetch_assoc();
            $status = 'Existing (Stock will be added)';
            // If brand or description is empty in excel, use existing one
            if (empty($brand)) $brand = $existing['brand'];
            if (empty($desc)) $desc = $existing['description'];
        }
        $checkStmt->close();

        $previewData[] = [
            'part_no' => $partNo,
            'brand' => $brand,
            'description' => $desc,
            'qty' => $qty,
            'cost' => $cost,
            'status' => $status
        ];

        $validUpdates[] = [
            'part_no' => $partNo,
            'brand' => $brand,
            'description' => $desc,
            'qty' => $qty,
            'cost' => $cost,
            'status' => $status
        ];
    }

    // Save valid updates in session
    $_SESSION['bulk_beginning_inventory'] = $validUpdates;

    echo json_encode([
        'success' => true,
        'data' => $previewData,
        'total_items' => count($validUpdates)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error reading Excel file: ' . $e->getMessage()]);
}
