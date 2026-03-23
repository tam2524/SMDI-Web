<?php
require 'db_config.php';
$r = $conn->query('SELECT COUNT(*) as cnt FROM spareparts_transfers');
echo "Transfers Count: " . ($r ? $r->fetch_assoc()['cnt'] : 'Error');
echo "\n";
$r = $conn->query('SELECT COUNT(*) as cnt FROM spareparts_transfer_items');
echo "Items Count: " . ($r ? $r->fetch_assoc()['cnt'] : 'Error');
