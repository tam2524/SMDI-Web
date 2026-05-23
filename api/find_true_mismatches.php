<?php
require_once 'db_config.php';

// Find all distinct OR numbers for sales
$sql = "SELECT DISTINCT or_number, from_location FROM spareparts_transactions WHERE type = 'OUT' AND or_number IS NOT NULL AND or_number != ''";
$res = $conn->query($sql);
$sales = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sales[] = $row;
    }
}

echo "Found " . count($sales) . " distinct sales records.\n";
$mismatches = [];

foreach ($sales as $sale) {
    $or = $sale['or_number'];
    $branch = $sale['from_location'];
    
    // 1. Current transaction sum
    $stmt = $conn->prepare("SELECT SUM(total_amount) as tx_sum, COUNT(*) as item_count FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
    $stmt->bind_param('ss', $or, $branch);
    $stmt->execute();
    $txRow = $stmt->get_result()->fetch_assoc();
    $txSum = (float)($txRow['tx_sum'] ?? 0);
    $itemCount = (int)$txRow['item_count'];
    $stmt->close();
    
    // 2. Total from spareparts_aging
    $agingTotal = null;
    $stmt = $conn->prepare("SELECT total_amount FROM spareparts_aging WHERE or_number = ? AND branch = ?");
    $stmt->bind_param('ss', $or, $branch);
    $stmt->execute();
    $agingRow = $stmt->get_result()->fetch_assoc();
    if ($agingRow) {
        $agingTotal = (float)$agingRow['total_amount'];
    }
    $stmt->close();
    
    // 3. Find all candidate audit logs
    $searchPattern = "%" . $or . "%";
    $auditStmt = $conn->prepare("SELECT id, action_details, action_type, table_name FROM audit_log WHERE action_details LIKE ? AND table_name IN ('spareparts_transactions', 'spareparts_sales') ORDER BY id DESC");
    $auditStmt->bind_param('s', $searchPattern);
    $auditStmt->execute();
    $auditRows = $auditStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $auditStmt->close();
    
    $expectedTotal = null;
    $source = "";
    
    // Process audit logs and find the latest one that exactly matches the OR number
    foreach ($auditRows as $row) {
        $details = $row['action_details'];
        
        // Exact match regex for OR number
        // Should match "OR [OR_NUMBER]" or "sale [OR_NUMBER]" with word boundaries or punctuation
        $escaped_or = preg_quote($or, '/');
        if (preg_match('/\bOR\s+' . $escaped_or . '\b/i', $details) || preg_match('/\bsale\s+' . $escaped_or . '\b/i', $details)) {
            // Found exact match! Extract total
            if (preg_match('/Total:\s*([0-9\.]+)/i', $details, $matches)) {
                $expectedTotal = (float)$matches[1];
                $source = "audit (insert)";
                break;
            } elseif (preg_match('/New total:\s*([0-9\.]+)/i', $details, $matches)) {
                $expectedTotal = (float)$matches[1];
                $source = "audit (update)";
                break;
            }
        }
    }
    
    // If aging total exists, let's use it as cross-reference
    if ($agingTotal !== null) {
        if ($expectedTotal === null) {
            $expectedTotal = $agingTotal;
            $source = "aging";
        }
    }
    
    // Check if current sum differs from expected
    if ($expectedTotal !== null && abs($txSum - $expectedTotal) > 0.01) {
        $mismatches[] = [
            'or_number' => $or,
            'branch' => $branch,
            'current_total' => $txSum,
            'expected_total' => $expectedTotal,
            'item_count' => $itemCount,
            'source' => $source
        ];
    }
}

echo "Found " . count($mismatches) . " TRUE mismatched sales records.\n";
foreach ($mismatches as $m) {
    echo "\nOR: {$m['or_number']} | Branch: {$m['branch']} | Expected Total: {$m['expected_total']} | Current Total: {$m['current_total']} (from {$m['source']})\n";
    
    // Print items
    $res = $conn->query("SELECT id, part_no, description, quantity, price, total_amount FROM spareparts_transactions WHERE or_number='{$m['or_number']}' AND from_location='{$m['branch']}' AND type='OUT'");
    while ($item = $res->fetch_assoc()) {
        echo " - Item ID: {$item['id']} | Part: {$item['part_no']} | Desc: {$item['description']} | Qty: {$item['quantity']} | Current Price: {$item['price']} | Current Subtotal: {$item['total_amount']}\n";
    }
}
?>
