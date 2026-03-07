<?php
require_once 'db_config.php';

$sql = "ALTER TABLE spareparts_transactions ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER transaction_type";

if ($conn->query($sql)) {
    echo "Successfully added payment_method column to spareparts_transactions table.";
} else {
    if ($conn->errno == 1060) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>