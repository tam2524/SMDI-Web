<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "127.0.0.1";
$dbusername = "mbc-smdi";
$dbpassword = "mbccreations0331";
$dbname = "smdi_website_db";
$port = 3308; // Fixed port

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$engine = 'G3V9E-0026195';
echo "Checking engine: $engine\n";

$res = $conn->query("SELECT * FROM motorcycle_inventory WHERE engine_number = '$engine'");
if ($res && $row = $res->fetch_assoc()) {
    echo "Inventory Record:\n";
    print_r($row);
    
    $id = $row['id'];
    echo "\nTransfers for ID $id:\n";
    $res2 = $conn->query("SELECT * FROM inventory_transfers WHERE motorcycle_id = $id ORDER BY transfer_date ASC");
    while ($t = $res2->fetch_assoc()) {
        print_r($t);
    }
    
    echo "\nSales for ID $id:\n";
    $res3 = $conn->query("SELECT * FROM motorcycle_sales WHERE motorcycle_id = $id");
    while ($s = $res3->fetch_assoc()) {
        print_r($s);
    }
} else {
    echo "Engine not found in inventory.\n";
}
?>
