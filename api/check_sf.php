<?php
require 'db_config.php';
$res = $conn->query("SELECT * FROM spareparts_sales_force");
while($row = $res->fetch_assoc()) {
    echo $row['branch'] . " | " . $row['employee_name'] . "\n";
}
?>
