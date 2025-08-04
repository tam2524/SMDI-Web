<?php
include '../api/db_config.php';

$recordId = $_GET['id'];
$sql = "SELECT * FROM records WHERE record_id = '$recordId'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $record = mysqli_fetch_assoc($result);
    echo json_encode($record);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Record not found']);
}
?>
