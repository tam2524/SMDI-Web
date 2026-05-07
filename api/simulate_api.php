<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock the environment
$_SESSION['user_branch'] = 'HEADOFFICE'; // Assume headoffice user
$_GET['action'] = 'get_monthly_inventory';
$_GET['branch'] = 'JARO-1';
$_GET['brand'] = 'all';
$_GET['category'] = 'all';
$_GET['model'] = 'all';
$_GET['month'] = '2026-03';

// require 'api/db_config.php';
$conn = new mysqli("127.0.0.1", "mbc-smdi", "mbccreations0331", "smdi_website_db", 3308);
if ($conn->connect_error) { die("Conn failed: " . $conn->connect_error); }
$GLOBALS['conn'] = $conn;

// We need to capture the output of getMonthlyInventory
// Function sanitizeInput is already in inventory_management.php

// Instead of requiring the whole file, let's just extract the function or require it
// and hope it doesn't echo immediately if we wrap it.
// Actually, the function echoes JSON. We can use output buffering.

ob_start();
require 'inventory_management.php';
// The file might execute something on load, but getMonthlyInventory is a function.
// Wait, the file has a router at the top usually.
ob_end_clean();

if (function_exists('getMonthlyInventory')) {
    ob_start();
    getMonthlyInventory();
    $output = ob_get_clean();
    $data = json_decode($output, true);
    
    if ($data && isset($data['data'])) {
        $found = false;
        foreach ($data['data'] as $item) {
            if ($item['engine_number'] === 'G3V9E-0026195') {
                echo "FOUND UNIT IN API RESPONSE!\n";
                print_r($item);
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "UNIT NOT FOUND IN API RESPONSE.\n";
            echo "Total items in response: " . count($data['data']) . "\n";
            echo "Summary: ";
            print_r($data['summary'] ?? 'No summary');
        }
    } else {
        echo "Failed to decode API response or no data.\n";
        echo "Output was: " . substr($output, 0, 500) . "...\n";
    }
} else {
    echo "Function getMonthlyInventory not found.\n";
}
?>
