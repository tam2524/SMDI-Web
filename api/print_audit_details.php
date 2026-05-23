<?php
require_once 'db_config.php';

$mismatched_ors = [
    'AR15234', 'AR0000641', 'AR0000644', 'AR002077', 'AR0000647', 'AR0000754',
    'AR0000758', 'AR0000760', 'AR0000771', 'AR0020591', 'AR0000774', 'AR0000778',
    'AR0002070', 'AR0000780', 'AR0000783', 'AR0000786', 'AR0000787', 'AR0000788',
    'AR0000789', 'AR0000791', 'AR0000792', 'AR0000796', 'AR0000798', 'AR0000799',
    'AR0000800', 'AR00001151', 'AR0001154'
];

foreach ($mismatched_ors as $or) {
    echo "=== OR: $or ===\n";
    $searchPattern = "%" . $or . "%";
    $res = $conn->query("SELECT * FROM audit_log WHERE action_details LIKE '$searchPattern' AND table_name IN ('spareparts_transactions', 'spareparts_sales') ORDER BY id DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo " - ID: {$row['id']} | Table: {$row['table_name']} | Details: {$row['action_details']} | Date: {$row['action_timestamp']}\n";
        }
    }
}
?>
