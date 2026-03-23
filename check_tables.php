<?php
require_once 'api/db_config.php';
$res = $conn->query("SHOW TABLES LIKE 'spareparts_%'");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
