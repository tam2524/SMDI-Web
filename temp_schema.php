<?php
require_once 'api/db_config.php';
$res = $conn->query("SHOW CREATE TABLE spareparts_inventory");
$row = $res->fetch_assoc();
echo $row['Create Table'];
