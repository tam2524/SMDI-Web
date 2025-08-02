<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("HTTP/1.0 404 Not Found");
    include('404_page.html'); 
    exit();
}
?>
