<?php
include 'api/db_config.php';
$res = $conn->query('SHOW COLUMNS FROM spareparts_transactions');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\n--- spareparts_inventory ---\n";
$res = $conn->query('SHOW COLUMNS FROM spareparts_inventory');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\n--- spareparts_customer_ledger ---\n";
// Let's check for a customer ledger table if it exists
$res = $conn->query("SHOW TABLES LIKE 'spareparts_customer%'");
while($row = $res->fetch_array()) {
    echo "Table: " . $row[0] . "\n";
    $res2 = $conn->query("SHOW COLUMNS FROM " . $row[0]);
    while($row2 = $res2->fetch_assoc()) {
        echo "  " . $row2['Field'] . " (" . $row2['Type'] . ")\n";
    }
}
?>
