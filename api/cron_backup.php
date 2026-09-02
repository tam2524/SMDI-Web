<?php
/**
 * Database Auto-Backup Script
 * This script exports all tables and their contents to a .sql file
 * in the project's 'backups' directory.
 */
ini_set('memory_limit', '-1');
set_time_limit(0);

require_once __DIR__ . '/db_config.php';

// Ensure the backups directory exists
$backupDir = 'D:/SMDI WEB - BACKUPS';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Generate the backup filename
$date = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/backup_' . $dbname . '_' . $date . '.sql';

// Try native mysqldump first for maximum speed and zero memory usage
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (file_exists($mysqldumpPath)) {
    $cleanHost = str_replace('p:', '', $servername);
    $backupFileWin = str_replace('/', DIRECTORY_SEPARATOR, $backupFile);
    $cmd = sprintf(
        '"%s" -h %s -P %d -u %s %s --single-transaction --quick --opt %s > "%s"',
        $mysqldumpPath,
        escapeshellarg($cleanHost),
        $dbport,
        escapeshellarg($dbusername),
        !empty($dbpassword) ? '-p' . escapeshellarg($dbpassword) : '',
        escapeshellarg($dbname),
        $backupFileWin
    );

    exec($cmd, $output, $returnCode);

    if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 1024) {
        // Auto-cleanup backups older than 30 days
        cleanupOldBackups($backupDir, 30);
        echo "Backup successful via mysqldump! Saved to: " . $backupFile . " (" . round(filesize($backupFile) / 1048576, 2) . " MB)\n";
        exit(0);
    }
}

// Fallback: Streaming PHP dump
$handle = fopen($backupFile, 'w+');
if (!$handle) {
    die("Failed to create backup file. Please check directory permissions.\n");
}

fwrite($handle, "-- Database Backup (PHP Streaming)\n");
fwrite($handle, "-- Database: {$dbname}\n");
fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
fwrite($handle, "SET AUTOCOMMIT = 0;\n");
fwrite($handle, "START TRANSACTION;\n");
fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

$tables = [];
$result = $conn->query("SHOW TABLES");
if ($result) {
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
}

foreach ($tables as $table) {
    $result = $conn->query("SHOW CREATE TABLE `{$table}`");
    $row2 = $result->fetch_row();
    
    fwrite($handle, "\n\n--\n-- Table structure for table `{$table}`\n--\n\n");
    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
    fwrite($handle, $row2[1] . ";\n\n");

    // Use MYSQLI_USE_RESULT to stream rows without consuming RAM
    $result = $conn->query("SELECT * FROM `{$table}`", MYSQLI_USE_RESULT);
    if ($result) {
        $num_fields = $result->field_count;
        $insert_statement = "INSERT INTO `{$table}` VALUES ";
        $counter = 0;
        
        while ($row = $result->fetch_row()) {
            if ($counter === 0) {
                fwrite($handle, $insert_statement);
            } else {
                fwrite($handle, ",\n");
            }
            
            $values = [];
            for ($i = 0; $i < $num_fields; $i++) {
                if (isset($row[$i])) {
                    $values[] = "'" . $conn->real_escape_string($row[$i]) . "'";
                } else {
                    $values[] = "NULL";
                }
            }
            fwrite($handle, "(" . implode(',', $values) . ")");
            
            $counter++;
            if ($counter === 500) {
                fwrite($handle, ";\n");
                $counter = 0;
            }
        }
        
        if ($counter > 0) {
            fwrite($handle, ";\n");
        }
        $result->close();
    }
}

fwrite($handle, "\nCOMMIT;\n");
fclose($handle);

cleanupOldBackups($backupDir, 30);
echo "Backup successful! Saved to: " . $backupFile . "\n";

/**
 * Remove backups older than $days
 */
function cleanupOldBackups($dir, $days = 30) {
    $now = time();
    foreach (glob($dir . '/backup_*.sql') as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                unlink($file);
            }
        }
    }
}
