<?php
$c = new mysqli('127.0.0.1:3308', 'mbc-smdi', 'mbccreations0331', 'smdi_website_db');
if ($c->connect_error) {
    die("Connection failed: " . $c->connect_error);
}
$tables = ['spareparts_inventory', 'spareparts_transactions', 'spareparts_aging', 'spareparts_history', 'spareparts_transfers', 'spareparts_transfer_items'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $r = $c->query("SHOW CREATE TABLE $table");
    if ($r) {
        $row = $r->fetch_assoc();
        echo $row['Create Table'] . "\n\n";
    }
}
$c->close();
?>
