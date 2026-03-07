<?php
require_once 'api/db_config.php';
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