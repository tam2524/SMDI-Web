<?php
include 'api/db_config.php';
$q = "SELECT * FROM spareparts_transactions WHERE type='TRANSFER_OUT' ORDER BY id DESC LIMIT 5";
$r = $conn->query($q);
while($f = $r->fetch_assoc()) {
    print_r($f);
}
?>
