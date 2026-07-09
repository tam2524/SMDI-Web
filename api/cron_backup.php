<?php
/**
 * Database Auto-Backup Script
 * This script exports all tables and their contents to a .sql file
 * in the project's 'backups' directory.
 */
require_once __DIR__ . '/db_config.php';

// Ensure the backups directory exists
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Generate the backup filename
$date = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/backup_' . $dbname . '_' . $date . '.sql';

// Set up the file handle
$handle = fopen($backupFile, 'w+');
if (!$handle) {
    die("Failed to create backup file. Please check directory permissions.");
}

// Add header to SQL file
fwrite($handle, "-- Database Backup\n");
fwrite($handle, "-- Database: {$dbname}\n");
fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
fwrite($handle, "SET AUTOCOMMIT = 0;\n");
fwrite($handle, "START TRANSACTION;\n");
fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
}

// Iterate through each table to get structure and data
foreach ($tables as $table) {
    // Add table structure
    $result = $conn->query("SHOW CREATE TABLE `{$table}`");
    $row2 = $result->fetch_row();
    
    fwrite($handle, "\n\n--\n-- Table structure for table `{$table}`\n--\n\n");
    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
    fwrite($handle, $row2[1] . ";\n\n");

    // Add table data
    $result = $conn->query("SELECT * FROM `{$table}`");
    $num_fields = $result->field_count;
    
    if ($result->num_rows > 0) {
        fwrite($handle, "--\n-- Dumping data for table `{$table}`\n--\n\n");
        $insert_statement = "INSERT INTO `{$table}` VALUES ";
        
        $counter = 0;
        while ($row = $result->fetch_row()) {
            if ($counter == 0) {
                fwrite($handle, $insert_statement);
            } else {
                fwrite($handle, ",\n");
            }
            
            $values = [];
            for ($i = 0; $i < $num_fields; $i++) {
                if (isset($row[$i])) {
                    $val = $conn->real_escape_string($row[$i]);
                    $values[] = "'$val'";
                } else {
                    $values[] = "NULL";
                }
            }
            fwrite($handle, "(" . implode(',', $values) . ")");
            
            $counter++;
            // Batch writes every 1000 rows
            if ($counter == 1000) {
                fwrite($handle, ";\n");
                $counter = 0;
            }
        }
        
        if ($counter > 0) {
            fwrite($handle, ";\n");
        }
    }
}

fwrite($handle, "\nCOMMIT;\n");
fclose($handle);

echo "Backup successful! Saved to: " . $backupFile;
