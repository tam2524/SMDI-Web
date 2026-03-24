<?php
require 'db_config.php';
echo "SESSION user_branch: " . ($_SESSION['user_branch'] ?? 'NOT SET') . "\n";
echo "SESSION position: " . ($_SESSION['position'] ?? 'NOT SET') . "\n";
?>
