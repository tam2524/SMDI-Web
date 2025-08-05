<?php
include 'db_config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get user input and sanitize it
$lastname = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';

// Check if input is empty
if (empty($lastname) || empty($firstname)) {
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Both last name and first name are required"));
    exit;
}

// Function to search records table with exact match
function searchRecordsTable($conn, $lastname, $firstname) {
    $sql = "SELECT * FROM records WHERE family_name = ? AND first_name = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return false;
    
    $stmt->bind_param("ss", $lastname, $firstname);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

// Function to search registration table with exact match on full name
function searchRegistrationTable($conn, $lastname, $firstname) {
    // First try exact match on full name (lastname + firstname)
    $fullName = $lastname . ' ' . $firstname;
    $sql = "SELECT * FROM registration WHERE full_name = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return false;
    
    $stmt->bind_param("s", $fullName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

// First try the records table
if ($row = searchRecordsTable($conn, $lastname, $firstname)) {
    $response = array(
        "plate_number" => $row['plate_number'],
        "mv_file_number" => $row['mv_file'],
        "last_name" => $row['family_name'],
        "first_name" => $row['first_name'],
        "branch" => $row['branch'],
        "date_reg" => $row['date_reg'],
        "remarks" => $row['remarks']
    );
} 
// If not found, try the registration table
elseif ($row = searchRegistrationTable($conn, $lastname, $firstname)) {
    $response = array(
        "plate_number" => $row['lto_plate_number'],
        "mv_file_number" => $row['mv_file_number'],
        "full_name" => $row['full_name'],
        "date_reg" => $row['date_reg']
    );
} 
// If not found in either table
else {
    $response = array("error" => "No matching records found for the provided name");
}

// Close connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>