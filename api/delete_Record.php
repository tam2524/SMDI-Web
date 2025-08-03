<?php
include '../api/db_config.php';

$ids = $_POST['ids'] ?? [];

if (!is_array($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data format.']);
    exit;
}

$ids = array_map('intval', $ids); 

if (count($ids) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No records selected.']);
    exit;
}


$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "DELETE FROM records WHERE record_id IN ($placeholders)";


$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    // Bind parameters
    mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids)), ...$ids);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Records deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete records.']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare SQL statement.']);
}

mysqli_close($conn);
?>
