<?php
require_once 'api/db_config.php';

$sql = "CREATE TABLE IF NOT EXISTS spareparts_price_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_no VARCHAR(100) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(255),
    invoice_no VARCHAR(100),
    transaction_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table spareparts_price_history created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
