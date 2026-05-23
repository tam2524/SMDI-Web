<?php
require_once 'db_config.php';

echo "--- Recent Part Price Changes or Updates in audit_log ---\n";
// Let's search for "Updated Part" in audit_log
$res = $conn->query("SELECT * FROM audit_log WHERE action_details LIKE '%Updated Part%' ORDER BY id DESC LIMIT 50");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Date: {$row['action_timestamp']} | Details: {$row['action_details']}\n";
    }
}

echo "\n--- Let's find if there are any specific parts that were updated ---\n";
// Let's search for any details containing "Price" or "price" in audit_log
$res = $conn->query("SELECT * FROM audit_log WHERE action_details LIKE '%price%' OR action_details LIKE '%Price%' ORDER BY id DESC LIMIT 50");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Table: {$row['table_name']} | Details: {$row['action_details']}\n";
    }
}
?>
