<?php
session_start();
$_SESSION['username'] = 'SMSANTIQUE3S';
$_SESSION['position'] = 'Spareparts-Sales';
$_SESSION['user_branch'] = 'SMSANTIQUE3S';

require_once 'api/db_config.php';
require_once 'api/spareparts_inventory.php';

// Call the function directly
getUserInfo();
?>
