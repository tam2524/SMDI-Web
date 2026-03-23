<?php
require 'api/db_config.php';
$res = $conn->query("SELECT * FROM spareparts_customers");
if($res) {
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
} else {
    echo "Query failed";
}
?>
