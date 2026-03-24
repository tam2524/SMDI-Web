<?php
require_once 'api/db_config.php';
$sql = "ALTER TABLE spareparts_sales_force ADD COLUMN position VARCHAR(255) AFTER employee_name;";
if ($conn->query($sql) === TRUE) {
    echo "Column 'position' added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
$conn->close();
?>
