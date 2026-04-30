<?php
include 'db_config.php';
$res = $conn->query("SHOW COLUMNS FROM motorcycle_inventory LIKE 'deleted_at'");
echo "deleted_at: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
$res = $conn->query("SHOW COLUMNS FROM motorcycle_inventory LIKE 'transferred_from'");
echo "transferred_from: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
$res = $conn->query("SHOW COLUMNS FROM motorcycle_inventory LIKE 'origin_branch'");
echo "origin_branch: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
$res = $conn->query("SHOW COLUMNS FROM motorcycle_inventory LIKE 'delivery_type'");
echo "delivery_type: " . ($res->num_rows > 0 ? "YES" : "NO") . "\n";
