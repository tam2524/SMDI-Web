<?php
header('Content-Type: application/json');
require_once 'db_config.php';

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

$updates = $_SESSION['bulk_price_updates'] ?? [];
if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No pending updates found. Please preview the file first.']);
    exit();
}

$conn->begin_transaction();
try {
    $changeReason = htmlspecialchars(strip_tags(trim($_POST['change_reason'] ?? 'Bulk pricing update')));
    $changedBy = htmlspecialchars(strip_tags(trim($_POST['changed_by'] ?? $_SESSION['username'] ?? 'System')));

    $updatedCount = 0;
    foreach ($updates as $update) {
        $id = (int)$update['id'];
        $partNo = $update['part_no'];
        $newCost = (float)$update['new_cost'];
        $newPrice = (float)$update['new_price'];
        
        // Update inventory record - secure it to match the logged-in user's branch
        $stmt = $conn->prepare("UPDATE spareparts_inventory SET cost = ?, price = ? WHERE id = ? AND current_branch = ?");
        $stmt->bind_param('ddis', $newCost, $newPrice, $id, $currentBranch);
        if ($stmt->execute()) {
            $updatedCount++;
        }
        $stmt->close();

        // Log to spareparts_price_history
        $histStmt = $conn->prepare("INSERT INTO spareparts_price_history (part_no, cost, price, supplier, invoice_no, transaction_date, change_reason, changed_by) VALUES (?, ?, ?, ?, 'BULK', NOW(), ?, ?)");
        $supplierName = "Bulk Excel Upload";
        $histStmt->bind_param('sddssss', $partNo, $newCost, $newPrice, $supplierName, $changeReason, $changedBy);
        $histStmt->execute();
        $histStmt->close();
    }

    $conn->commit();
    
    // Log to system audit logs
    $username = $_SESSION['username'] ?? 'System';
    $auditStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, description, from_location, status, reason) VALUES (CURDATE(), 'ADJUSTMENT', ?, ?, 'Completed', 'Bulk pricing update')");
    $auditMsg = "Bulk pricing update: $updatedCount items updated by $username";
    $auditStmt->bind_param('ss', $auditMsg, $currentBranch);
    $auditStmt->execute();
    $auditStmt->close();

    // Clear session updates
    unset($_SESSION['bulk_price_updates']);

    echo json_encode([
        'success' => true,
        'message' => "Successfully updated pricing for $updatedCount parts."
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to apply updates: ' . $e->getMessage()]);
}
