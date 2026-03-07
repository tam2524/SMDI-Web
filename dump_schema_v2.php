<?php
$servername = "127.0.0.1";
$dbusername = "mbc-smdi";
$dbpassword = "mbccreations0331";
$dbname = "smdi_website_db";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error . "\n";
    exit();
}
echo "Connection successful!\n";

$tables = ['spareparts_inventory', 'spareparts_transactions', 'spareparts_aging', 'spareparts_history', 'spareparts_transfers', 'spareparts_transfer_items'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $result = $conn->query("SHOW CREATE TABLE $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo $row['Create Table'] . "\n\n";
    } else {
        echo "Table does not exist.\n\n";
    }
}
?>