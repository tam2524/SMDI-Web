<?php
require_once 'api/db_config.php';

// 1. Get all parts
$partsQuery = $conn->query("SELECT id, part_no, current_branch, description, cost, current_stock FROM spareparts_inventory");

$fixedCount = 0;

while ($part = $partsQuery->fetch_assoc()) {
    $pno = $part['part_no'];
    $branch = $part['current_branch'];
    $actual_stock = (int)$part['current_stock'];
    
    // Calculate sum of transactions for this part at this branch
    $stmt = $conn->prepare("
        SELECT type, quantity, from_location, to_location 
        FROM spareparts_transactions 
        WHERE part_no = ? AND (from_location = ? OR to_location = ?)
    ");
    $stmt->bind_param('sss', $pno, $branch, $branch);
    $stmt->execute();
    $txs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $historical_stock = 0;
    foreach ($txs as $t) {
        $qty = (int)$t['quantity'];
        $type = $t['type'];
        
        $isIncoming = false;
        if ($type === 'IN' || $type === 'TRANSFER_IN' || $type === 'RETURN') {
            $isIncoming = true;
        } else if ($type === 'ADJUSTMENT') {
            if ($qty > 0) { $isIncoming = true; } 
            else { $isIncoming = false; $qty = abs($qty); } // negative qty
        }
        
        if ($isIncoming) {
            $historical_stock += $qty;
        } else {
            $historical_stock -= $qty;
        }
    }
    
    $discrepancy = $actual_stock - $historical_stock;
    
    if ($discrepancy > 0) {
        // We need to inject an 'IN' Initial Encoding
        $total = $discrepancy * (float)$part['cost'];
        $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, or_number, status) VALUES (CURDATE(), 'IN', ?, ?, ?, ?, ?, 'Initial Encoding', ?, 'ENCODE', 'Completed')");
        $log->bind_param('ssidds', $pno, $part['description'], $discrepancy, $part['cost'], $total, $branch);
        $log->execute();
        $fixedCount++;
    } else if ($discrepancy < 0) {
        // Technically overstocked in history, inject a negative adjustment
        $diff = abs($discrepancy);
        $total = $diff * (float)$part['cost'];
        $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, reason, status) VALUES (CURDATE(), 'ADJUSTMENT', ?, ?, ?, ?, ?, ?, 'System Alignment', 'Completed')");
        $negDiff = -$diff;
        $log->bind_param('ssidds', $pno, $part['description'], $negDiff, $part['cost'], $total, $branch);
        $log->execute();
        $fixedCount++;
    }
}

echo "Done. Fixed $fixedCount parts.\n";
?>
