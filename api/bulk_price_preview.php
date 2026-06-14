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
$isAdmin = in_array(strtolower(trim($role)), ['spareparts-admin', 'spareparts-owner', 'admin', 'owner', 'itsuperadmin', 'admin spareparts', 'spareparts-sales']);
if (!$isAdmin) {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only Admins, Owners, and Sales can perform bulk updates.']);
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
    $partNoIdx = array_search('partno', $headerRow);
    if ($partNoIdx === false) $partNoIdx = array_search('partnumber', $headerRow);
    if ($partNoIdx === false) {
        foreach ($headerRow as $key => $val) {
            if (strpos($val, 'part') !== false) {
                $partNoIdx = $key;
                break;
            }
        }
    }

    $costIdx = array_search('cost', $headerRow);
    if ($costIdx === false) $costIdx = array_search('suppliercost', $headerRow);
    if ($costIdx === false) $costIdx = array_search('purchaseprice', $headerRow);

    $priceIdx = array_search('price', $headerRow);
    if ($priceIdx === false) $priceIdx = array_search('sellingprice', $headerRow);
    if ($priceIdx === false) $priceIdx = array_search('retailprice', $headerRow);

    if ($partNoIdx === false) {
        echo json_encode(['success' => false, 'message' => 'Could not find a column for "part_no" in the Excel header.']);
        exit();
    }
    if ($costIdx === false && $priceIdx === false) {
        echo json_encode(['success' => false, 'message' => 'Could not find a column for "cost" or "price" in the Excel header.']);
        exit();
    }

    $previewData = [];
    $validUpdates = [];

    foreach ($rows as $rowNum => $row) {
        $partNo = trim($row[$partNoIdx] ?? '');
        if ($partNo === '') continue;

        $newCost = $costIdx !== false ? (float)str_replace([',', '₱', ' '], '', $row[$costIdx] ?? 0) : null;
        $newPrice = $priceIdx !== false ? (float)str_replace([',', '₱', ' '], '', $row[$priceIdx] ?? 0) : null;

        // Find part in inventory for the logged-in user's branch
        $stmt = $conn->prepare("SELECT id, part_no, description, current_branch, cost, price FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $stmt->bind_param('ss', $partNo, $currentBranch);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($item = $result->fetch_assoc()) {
                $currentCost = (float)$item['cost'];
                $currentPrice = (float)$item['price'];

                $finalCost = ($newCost !== null && $newCost > 0) ? $newCost : $currentCost;
                $finalPrice = ($newPrice !== null && $newPrice > 0) ? $newPrice : $currentPrice;

                // Only include if there's an actual change
                if ($finalCost != $currentCost || $finalPrice != $currentPrice) {
                    $previewData[] = [
                        'part_no' => $item['part_no'],
                        'description' => $item['description'] . " (" . $item['current_branch'] . ")",
                        'current_cost' => $currentCost,
                        'new_cost' => $finalCost,
                        'current_price' => $currentPrice,
                        'new_price' => $finalPrice,
                        'status' => 'Update'
                    ];

                    $validUpdates[] = [
                        'id' => $item['id'],
                        'part_no' => $item['part_no'],
                        'branch' => $item['current_branch'],
                        'old_cost' => $currentCost,
                        'new_cost' => $finalCost,
                        'old_price' => $currentPrice,
                        'new_price' => $finalPrice
                    ];
                }
            }
        } else {
            $previewData[] = [
                'part_no' => $partNo,
                'description' => "Part not found in branch ($currentBranch)",
                'current_cost' => 0.00,
                'new_cost' => $newCost ?? 0.00,
                'current_price' => 0.00,
                'new_price' => $newPrice ?? 0.00,
                'status' => 'Not Found'
            ];
        }
        $stmt->close();
    }

    // Save valid updates in session
    $_SESSION['bulk_price_updates'] = $validUpdates;

    echo json_encode([
        'success' => true,
        'data' => $previewData,
        'total_updates' => count($validUpdates)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error reading Excel file: ' . $e->getMessage()]);
}
