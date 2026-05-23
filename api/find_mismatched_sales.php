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
    
    // 3. Total from audit_log by searching in action_details
    $auditTotal = null;
    $searchPattern = "%OR " . $or . "%";
    $stmt = $conn->prepare("SELECT action_details FROM audit_log WHERE action_details LIKE ? AND table_name IN ('spareparts_transactions', 'spareparts_sales') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $searchPattern);
    $stmt->execute();
    $auditRow = $stmt->get_result()->fetch_assoc();
    if ($auditRow) {
        $details = $auditRow['action_details'];
        if (preg_match('/Total:\s*([0-9\.]+)/i', $details, $matches)) {
            $auditTotal = (float)$matches[1];
        } elseif (preg_match('/New total:\s*([0-9\.]+)/i', $details, $matches)) {
            $auditTotal = (float)$matches[1];
        }
    }
    $stmt->close();
    
    // Determine expected total
    $expectedTotal = null;
    $source = "";
    if ($agingTotal !== null) {
        $expectedTotal = $agingTotal;
        $source = "aging";
    }
    if ($auditTotal !== null) {
        $expectedTotal = $auditTotal;
        $source = $agingTotal !== null ? "aging & audit" : "audit";
    }
    
    // Check if the current sum differs from expected
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

echo "Found " . count($mismatches) . " mismatched sales records.\n";
foreach ($mismatches as $m) {
    echo "OR: {$m['or_number']} | Branch: {$m['branch']} | Items: {$m['item_count']} | Current Total: {$m['current_total']} | Expected Total: {$m['expected_total']} (from {$m['source']})\n";
}
?>
