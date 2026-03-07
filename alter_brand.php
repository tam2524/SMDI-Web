<?php
$servername = "127.0.0.1:3308";
$dbusername = "mbc-smdi";
$dbpassword = "mbccreations0331";
$dbname = "smdi_website_db";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    exit();
}

// 1. Alter spareparts_inventory
$sql1 = "ALTER TABLE spareparts_inventory ADD COLUMN brand VARCHAR(100) AFTER id";
if ($conn->query($sql1) === TRUE) {
    echo "Table spareparts_inventory altered successfully.\n";
} else {
    echo "Error altering spareparts_inventory: " . $conn->error . "\n";
}

// 2. Alter spareparts_transactions
$sql2 = "ALTER TABLE spareparts_transactions ADD COLUMN brand VARCHAR(100) AFTER type";
if ($conn->query($sql2) === TRUE) {
    echo "Table spareparts_transactions altered successfully.\n";
} else {
    echo "Error altering spareparts_transactions: " . $conn->error . "\n";
}

$conn->close();
?>