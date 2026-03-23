<?php
require_once 'api/db_config.php';
$r = $conn->query("DESCRIBE spareparts_inventory");
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
