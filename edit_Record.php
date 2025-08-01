<?php
include 'db_config.php';

$recordId = $_POST['Record_id'];
$familyName = $_POST['familyName'];
$firstName = $_POST['firstName'];
$middleName = $_POST['middleName'];
$plateNumber = $_POST['plateNumber'];
$mvFile = $_POST['mvFile'];
$branch = $_POST['branch'];
$batch = $_POST['batch'];
$remarks = $_POST['remarks'];

$sql = "UPDATE records 
        SET family_name = ?, first_name = ?, middle_name = ?, plate_number = ?, mv_file = ?, branch = ?, batch = ?, remarks = ? 
        WHERE record_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssssssssi", $familyName, $firstName, $middleName, $plateNumber, $mvFile, $branch, $batch, $remarks, $recordId);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Record updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update record.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
