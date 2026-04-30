<?php
include 'db_config.php';
$res = $conn->query('DESCRIBE motorcycle_inventory');
if (!$res) die($conn->error);
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
