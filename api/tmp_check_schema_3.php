<?php
require_once 'db_config.php';
$res = $conn->query('SHOW CREATE TABLE spareparts_aging');
$row = $res->fetch_assoc();
echo $row['Create Table'];
