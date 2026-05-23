<?php
require_once 'db_config.php';

$mismatched_ors = [
    'AR15234', 'AR0000641', 'AR0000644', 'AR002077', 'AR0000647', 'AR0000754',
    'AR0000758', 'AR0000760', 'AR0000771', 'AR0020591', 'AR0000774', 'AR0000778',
    'AR0002070', 'AR0000780', 'AR0000783', 'AR0000786', 'AR0000787', 'AR0000788',
    'AR0000789', 'AR0000791', 'AR0000792', 'AR0000796', 'AR0000798', 'AR0000799',
    'AR0000800', 'AR00001151', 'AR0001154'
];

foreach ($mismatched_ors as $or) {
    echo "\n=== Sale OR: $or ===\n";
    
    // Fetch items
    $res = $conn->query("SELECT id, part_no, description, quantity, price, total_amount, from_location FROM spareparts_transactions WHERE or_number='$or' AND type='OUT'");
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    
    // Find expected total from audit log
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
    
    echo "Audit Log Expected Total: " . ($auditTotal !== null ? $auditTotal : "Not found") . "\n";
    echo "Current Tx Total: " . array_sum(array_column($items, 'total_amount')) . "\n";
    
    foreach ($items as $item) {
        echo " - ID: {$item['id']} | Part: {$item['part_no']} | Desc: {$item['description']} | Qty: {$item['quantity']} | Current Price: {$item['price']} | Current Subtotal: {$item['total_amount']}\n";
    }
}
?>
