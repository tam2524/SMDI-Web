<?php
header('Content-Type: application/json');
require_once 'db_config.php';

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
$items = $_SESSION['bulk_beginning_inventory'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No pending bulk inventory updates found. Please preview first.']);
    exit();
}

$conn->begin_transaction();
try {
    $insertedCount = 0;
    $updatedCount = 0;
    
    foreach ($items as $item) {
        $part_no = htmlspecialchars(strip_tags(trim($item['part_no'])));
        $brand = htmlspecialchars(strip_tags(trim($item['brand'])));
        $description = htmlspecialchars(strip_tags(trim($item['description'])));
        $qty = (int)$item['qty'];
        $cost = (float)$item['cost'];
        $price = 0.00; // default beginning price

        // Check if already exists in this branch
        $checkStmt = $conn->prepare("SELECT id FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $checkStmt->bind_param('ss', $part_no, $currentBranch);
        $checkStmt->execute();
        $res = $checkStmt->get_result();

        if ($res->num_rows > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ?, cost = ?, brand = ?, description = ? WHERE part_no = ? AND current_branch = ?");
            $stmt->bind_param('idssss', $qty, $cost, $brand, $description, $part_no, $currentBranch);
            $stmt->execute();
            $stmt->close();
            $updatedCount++;
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO spareparts_inventory (brand, part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssidds', $brand, $part_no, $description, $qty, $cost, $price, $currentBranch);
            $stmt->execute();
            $stmt->close();
            $insertedCount++;
        }
        $checkStmt->close();

        // Log AS BEGINNING INVENTORY in spareparts_transactions
        $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status, reason) 
                               VALUES (CURDATE(), 'IN', ?, ?, ?, ?, ?, 'BEGINNING_INV', ?, 'Completed', 'Beginning Inventory Entry')");
        $total = $qty * $cost;
        $log->bind_param('ssidds', $part_no, $description, $qty, $cost, $total, $currentBranch);
        $log->execute();
        $log->close();
    }

    $conn->commit();

    // Audit log
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id === 0 && isset($_SESSION['username'])) {
        $uStmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $uStmt->bind_param('s', $_SESSION['username']);
        $uStmt->execute();
        $user_id = $uStmt->get_result()->fetch_assoc()['id'] ?? 0;
        $_SESSION['user_id'] = $user_id;
        $uStmt->close();
    }
    
    $auditDetails = "Bulk uploaded beginning inventory. New parts: $insertedCount, Updated parts: $updatedCount in branch $currentBranch.";
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, table_name, record_id, action_details) VALUES (?, 'INSERT', 'spareparts_inventory', 'BEGINNING_INV_BULK', ?)");
    $stmt->bind_param('is', $user_id, $auditDetails);
    $stmt->execute();
    $stmt->close();

    // Clear session
    unset($_SESSION['bulk_beginning_inventory']);

    echo json_encode([
        'success' => true,
        'message' => "Successfully imported beginning inventory. Created $insertedCount new items and updated $updatedCount existing items."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to import inventory: ' . $e->getMessage()]);
}
