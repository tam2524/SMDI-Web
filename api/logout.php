<?php
include 'db_config.php';

session_start();

$_SESSION = array();

session_destroy();
header("Location: ../login.html");
exit;
?>
