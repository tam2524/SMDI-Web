<?php
require_once 'api/db_config.php';
$res = $conn->query("SHOW COLUMNS FROM spareparts_transactions");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
unlink(__FILE__);
?>
