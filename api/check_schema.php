<?php
include 'db_config.php';
$res = $conn->query("DESCRIBE spareparts_transactions");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
