<?php
require_once 'db_config.php';
$table = $_GET['table'] ?? 'spareparts_transactions';
$res = $conn->query("DESCRIBE $table");
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode($data, JSON_PRETTY_PRINT);
