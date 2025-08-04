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
    echo json_encode(array("error" => "Invalid input"));
    exit;
}

// Prepare SQL query with LIKE operator for partial matches
$sql = "SELECT * FROM registration WHERE full_name LIKE CONCAT('%', ?, '%') AND full_name LIKE CONCAT('%', ?, '%')";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Failed to prepare SQL query"));
    exit;
}

// Bind parameters with % wildcard for partial matching
$stmt->bind_param("ss", $firstname, $lastname);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch data
    $row = $result->fetch_assoc();
    $response = array(
        "lto_plate_number" => isset($row['lto_plate_number']) ? $row['lto_plate_number'] : null,
              "full_name" => isset($row['full_name']) ? $row['full_name'] : null,
        "date_reg" => isset($row['date_reg']) ? $row['date_reg'] : null,
        "mv_file_number" => isset($row['mv_file_number']) ? $row['mv_file_number'] : null
    );
} else {
    $response = array("error" => "No results found");
}

// Close connection
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
