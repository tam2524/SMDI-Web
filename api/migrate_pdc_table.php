<?php
require_once 'db_config.php';

// Create spareparts_pdc_payments table
$sql = "CREATE TABLE IF NOT EXISTS spareparts_pdc_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    check_no VARCHAR(50) NOT NULL,
    check_date DATE NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('Pending', 'Reflected', 'Cancelled') DEFAULT 'Pending',
    branch VARCHAR(100) NOT NULL,
    encoded_by VARCHAR(100) NOT NULL,
    encoded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reflected_date DATETIME DEFAULT NULL,
    remarks TEXT
)";

if ($conn->query($sql)) {
    echo "Table spareparts_pdc_payments created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
