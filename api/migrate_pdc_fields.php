<?php
require_once 'db_config.php';

// Add check_date to spareparts_transactions
$sql1 = "ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS check_date DATE DEFAULT NULL AFTER payment_method";
if ($conn->query($sql1)) {
    echo "Successfully added check_date column to spareparts_transactions.\n";
} else {
    echo "Error adding check_date: " . $conn->error . "\n";
}

// Ensure payment_method exists (already added by another migration but safe to check)
$sql2 = "ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL AFTER transaction_type";
$conn->query($sql2);

echo "Migration complete.";
$conn->close();
?>
