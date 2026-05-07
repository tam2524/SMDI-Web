<?php
require 'api/db_config.php';
$result = $conn->query("SELECT id, engine_number, current_branch, status, date_delivered FROM motorcycle_inventory WHERE engine_number='G3V9E-0026195'");
print_r($result->fetch_assoc());
$result2 = $conn->query("SELECT * FROM inventory_transfers WHERE engine_number='G3V9E-0026195' OR motorcycle_id = (SELECT id FROM motorcycle_inventory WHERE engine_number='G3V9E-0026195')");
while($row = $result2->fetch_assoc()) {
    print_r($row);
}
?>
