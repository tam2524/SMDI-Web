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

// Prepare SQL query for main records table
$sql = "SELECT * FROM records WHERE family_name LIKE CONCAT('%', ?, '%') AND first_name LIKE CONCAT('%', ?, '%')";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Failed to prepare SQL query: " . $conn->error));
    exit;
}

// Bind parameters and execute
$stmt->bind_param("ss", $lastname, $firstname);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch data from main records table
    $row = $result->fetch_assoc();
    $response = array(
        "plate_number" => $row['plate_number'],
        "mv_file_number" => $row['mv_file'],
        "last_name" => $row['family_name'],
        "first_name" => $row['first_name'],
        "branch" => $row['branch'],
        "date_reg" => $row['date_reg'],
        "remarks" => $row['remarks']
    );
} else {
    // If no records found in main table, try registration table
    $sql = "SELECT * FROM registration WHERE full_name LIKE CONCAT('%', ?, '%') OR 
            (full_name LIKE CONCAT('%', ?, '%') AND full_name LIKE CONCAT('%', ?, '%'))";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        header('Content-Type: application/json');
        echo json_encode(array("error" => "Failed to prepare SQL query: " . $conn->error));
        exit;
    }
    
    $stmt->bind_param("sss", $lastname, $lastname, $firstname);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Fetch data from registration table
        $row = $result->fetch_assoc();
        $response = array(
            "plate_number" => $row['lto_plate_number'],
            "mv_file_number" => $row['mv_file_number'],
            "full_name" => $row['full_name'],
            "date_reg" => $row['date_reg']
            // Other fields will be blank/N/A as they don't exist in registration table
        );
    } else {
        $response = array("error" => "No matching records found in either table");
    }
}

// Close connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>