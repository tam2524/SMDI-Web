<?php
require_once 'c:/xampp/htdocs/SMDI-Web/api/db_config.php';
$tables = ['spareparts_inventory', 'spareparts_transactions', 'spareparts_aging', 'spareparts_sales_force'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo " - {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo " - ERROR: " . $conn->error . "\n";
    }
    echo "\n";
}
?>
