<?php
require_once 'db_config.php';

$reversions = [
    3543  => 350.00, // AR0000641
    3546  => 350.00, // AR0000644
    3572  => 350.00, // AR0000647
    3591  => 350.00, // AR0000754
    4588  => 350.00, // AR0000758
    5982  => 350.00, // AR0000760
    6379  => 350.00, // AR0000771
    9635  => 350.00, // AR0000774
    12463 => 350.00, // AR0000778
    13279 => 350.00, // AR0000780
    13897 => 350.00, // AR0000783
    14870 => 350.00, // AR0000786
    15253 => 350.00, // AR0000787
    15273 => 350.00, // AR0000788
    15716 => 350.00, // AR0000789
    15758 => 350.00, // AR0000791
    15774 => 350.00, // AR0000792
    16005 => 350.00, // AR0000796
    16025 => 350.00, // AR0000798
    16128 => 350.00, // AR0000799
    16130 => 350.00, // AR0000800
    16131 => 350.00, // AR00001151
    16250 => 380.00  // AR0001154
];

$conn->begin_transaction();
try {
    $updatedCount = 0;
    foreach ($reversions as $itemId => $originalPrice) {
        // Fetch current values to log or verify quantity
        $stmt = $conn->prepare("SELECT quantity, price, total_amount, or_number FROM spareparts_transactions WHERE id = ?");
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            $qty = (int)$row['quantity'];
            $newTotal = $qty * $originalPrice;
            
            $upd = $conn->prepare("UPDATE spareparts_transactions SET price = ?, total_amount = ? WHERE id = ?");
            $upd->bind_param('ddi', $originalPrice, $newTotal, $itemId);
            if ($upd->execute()) {
                echo "Reverted Item ID $itemId (OR: {$row['or_number']}): Price {$row['price']} -> $originalPrice | Total {$row['total_amount']} -> $newTotal\n";
                $updatedCount++;
            }
            $upd->close();
        }
    }
    
    $conn->commit();
    echo "\nSuccess! Successfully reverted $updatedCount transactions.\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "Error reverting prices: " . $e->getMessage() . "\n";
}
?>
