<?php
include 'db_config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get and sanitize input
$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';

if (empty($lastname) || empty($firstname)) {
    echo json_encode(["error" => "Both last name and first name are required"]);
    exit;
}

// Convert Windows-style pattern (text***) to SQL LIKE pattern
function convertPattern($input) {
    // If input contains ***, use the part before as prefix
    if (strpos($input, '***') !== false) {
        return str_replace('***', '%', $input);
    }
    // Otherwise search for exact match or prefix
    return $input . '%';
}

// Extract pure last name (before slash)
function extractLastName($fullLastName) {
    $parts = explode('/', $fullLastName);
    return trim($parts[0]);
}

// Search records table with Windows-style pattern matching
function searchRecordsTable($conn, $lastname, $firstname) {
    $lastPattern = convertPattern($lastname);
    $firstPattern = convertPattern($firstname);
    $pureLastName = extractLastName($lastname);
    $pureLastPattern = convertPattern($pureLastName);
    
    $sql = "SELECT * FROM records WHERE 
           (family_name LIKE ? OR family_name LIKE CONCAT(?, '/%')) AND 
           first_name LIKE ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("sss", $lastPattern, $pureLastPattern, $firstPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['pure_last_name'] = extractLastName($row['family_name']);
            $rows[] = $row;
        }
        return $rows;
    }
    return false;
}

// Search registration table with Windows-style pattern matching
function searchRegistrationTable($conn, $lastname, $firstname) {
    $pureLastName = extractLastName($lastname);
    $lastPattern = convertPattern($pureLastName);
    $firstPattern = convertPattern($firstname);
    
    $namePatterns = [
        $lastPattern . ', ' . $firstPattern,
        $lastPattern . ' ' . $firstPattern
    ];
    
    $rows = [];
    foreach ($namePatterns as $pattern) {
        $sql = "SELECT * FROM registration WHERE 
               full_name LIKE ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) continue;
        
        $pattern = "%" . $pattern . "%";
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
    }
    return !empty($rows) ? $rows : false;
}

// Search both tables
$response = ["error" => "No matching records found"];

// Check records table first
if ($rows = searchRecordsTable($conn, $lastname, $firstname)) {
    $response = array_map(function($row) {
        return [
            "plate_number" => $row['plate_number'],
            "mv_file_number" => $row['mv_file'],
            "last_name" => $row['pure_last_name'],
            "first_name" => $row['first_name'],
            "branch" => $row['branch'],
            "date_reg" => $row['date_reg'],
            "remarks" => $row['remarks'],
            "dealer" => isset($row['family_name']) ? trim(explode('/', $row['family_name'])[1] ?? '') : ''
        ];
    }, $rows);
} 
// If no results in records table, check registration table
elseif ($rows = searchRegistrationTable($conn, $lastname, $firstname)) {
    $response = array_map(function($row) {
        return [
            "plate_number" => $row['lto_plate_number'],
            "mv_file_number" => $row['mv_file_number'],
            "full_name" => $row['full_name'],
            "date_reg" => $row['date_reg']
        ];
    }, $rows);
}

// If we have only one result, return it as an object (backward compatibility)
if (is_array($response) && count($response) === 1 && !isset($response['error'])) {
    $response = $response[0];
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>