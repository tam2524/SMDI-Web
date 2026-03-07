<?php
require 'db_config.php';
$conn->query("ALTER TABLE spareparts_transfer_items ADD COLUMN status VARCHAR(20) DEFAULT 'In-Transit'");
echo "Schema updated.";
?>