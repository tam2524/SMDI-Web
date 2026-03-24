<?php
require_once 'db_config.php';
$tables = ['spareparts_transactions', 'spareparts_aging'];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  {$row['Field']} - {$row['Type']}\n";
        }
    } else {
        echo "  Error: " . $conn->error . "\n";
    }
}
$conn->close();
?>
