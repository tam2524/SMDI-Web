<?php
require_once 'db_config.php';

$res = $conn->query("SELECT * FROM spareparts_transactions WHERE or_number='AR15234'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
