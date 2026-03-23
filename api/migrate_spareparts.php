<?php
include 'db_config.php';

$sql = [
    // Create spareparts_price_history
    "CREATE TABLE IF NOT EXISTS spareparts_price_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        part_no VARCHAR(100) NOT NULL,
        cost DECIMAL(10, 2) NOT NULL,
        supplier VARCHAR(255),
        invoice_no VARCHAR(100),
        transaction_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (part_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    // Create spareparts_compatibility
    "CREATE TABLE IF NOT EXISTS spareparts_compatibility (
        id INT AUTO_INCREMENT PRIMARY KEY,
        part_no VARCHAR(100) NOT NULL,
        model_name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE INDEX (part_no, model_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    // Update spareparts_inventory
    "ALTER TABLE spareparts_inventory ADD COLUMN IF NOT EXISTS bin_location VARCHAR(100) AFTER quantity;",
    "ALTER TABLE spareparts_inventory ADD COLUMN IF NOT EXISTS thumbnail_image VARCHAR(255) AFTER bin_location;"
];

foreach ($sql as $query) {
    if ($conn->query($query) === TRUE) {
        echo "Successfully executed: " . substr($query, 0, 50) . "...\n";
    } else {
        echo "Error executing query: " . $conn->error . "\n";
    }
}

$conn->close();
?>
