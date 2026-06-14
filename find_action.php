<?php
$lines = file('api/spareparts_inventory.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'case ') !== false || strpos($line, 'switch') !== false || strpos($line, 'action') !== false) {
        if (strlen($line) < 100) {
            echo ($i+1) . ": " . trim($line) . "\n";
        }
    }
}
?>
