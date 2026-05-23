<?php
require_once 'db_config.php';

$or = 'AR0002070';
$res = $conn->query("SELECT * FROM audit_log WHERE action_details LIKE '%$or%' ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Action: {$row['action_type']} | Table: {$row['table_name']} | Details: {$row['action_details']} | Time: {$row['action_timestamp']}\n";
}
?>
