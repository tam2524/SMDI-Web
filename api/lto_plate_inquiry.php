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

// Prepare SQL query with LIKE operator for partial matches
$sql = "SELECT * FROM records WHERE family_name LIKE CONCAT('%', ?, '%') AND first_name LIKE CONCAT('%', ?, '%')";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Failed to prepare SQL query: " . $conn->error));
    exit;
}

// Bind parameters
$stmt->bind_param("ss", $lastname, $firstname);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch data
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
    $response = array("error" => "No matching records found for the provided name");
}

// Close connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>