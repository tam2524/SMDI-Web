<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load .env configuration
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comment lines
        if (strpos(trim($line), '#') === 0)
            continue;

        // Split key and value by first "="
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Assign configuration variables with generic development fallbacks.
// Production credentials now exist ONLY inside the gitignored .env file.
$servername = "p:" . ($_ENV['DB_HOST'] ?? '127.0.0.1');
$dbusername = $_ENV['DB_USER'] ?? 'root';
$dbpassword = $_ENV['DB_PASS'] ?? '';
$dbname     = $_ENV['DB_NAME'] ?? 'smdi_db';
$dbport     = intval($_ENV['DB_PORT'] ?? 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, $dbport);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    die(json_encode([
        'error' => 'Database connection failed.',
        'message' => $e->getMessage()
    ]));
}