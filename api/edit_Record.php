<?php
include '../api/db_config.php';

$recordId = $_POST['Record_id'];
$familyName = $_POST['familyName'];
$firstName = $_POST['firstName'];
$middleName = $_POST['middleName'];
$plateNumber = $_POST['plateNumber'];
$mvFile = $_POST['mvFile'];
$branch = $_POST['branch'];
$batch = $_POST['batch'];
$remarks = $_POST['remarks'];
$dateReg = $_POST['date_reg'];

if ($plateNumber === "ND") {
    $checkDuplicateQuery = "SELECT mv_file, plate_number FROM records WHERE mv_file = ? AND record_id != ?";
    $checkStmt = mysqli_prepare($conn, $checkDuplicateQuery);
    mysqli_stmt_bind_param($checkStmt, "si", $mvFile, $recordId);
} else {
    $checkDuplicateQuery = "SELECT mv_file, plate_number FROM records WHERE (plate_number = ? OR mv_file = ?) AND record_id != ?";
    $checkStmt = mysqli_prepare($conn, $checkDuplicateQuery);
    mysqli_stmt_bind_param($checkStmt, "ssi", $plateNumber, $mvFile, $recordId);
}

mysqli_stmt_execute($checkStmt);
$result = mysqli_stmt_get_result($checkStmt);

$duplicatePlate = false;
$duplicateMV = false;

while ($row = mysqli_fetch_assoc($result)) {
    if (isset($row['plate_number']) && $row['plate_number'] === $plateNumber && $plateNumber !== "ND") {
        $duplicatePlate = true;
    }
    if (isset($row['mv_file']) && $row['mv_file'] === $mvFile) {
        $duplicateMV = true;
    }
}

if ($duplicatePlate || $duplicateMV) {
    if ($duplicatePlate && $duplicateMV) {
        $msg = 'Duplicate MV File and Plate Number found.';
    } elseif ($duplicatePlate) {
        $msg = 'Duplicate Plate Number found.';
    } else {
        $msg = 'Duplicate MV File found.';
    }
    echo json_encode(['status' => 'duplicate', 'message' => $msg]);
    mysqli_stmt_close($checkStmt);
    mysqli_close($conn);
    exit;
}

mysqli_stmt_close($checkStmt);

$sql = "UPDATE records 
        SET family_name = ?, first_name = ?, middle_name = ?, plate_number = ?, mv_file = ?, branch = ?, batch = ?, remarks = ?, date_reg = ?

        WHERE record_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssssssssi", $familyName, $firstName, $middleName, $plateNumber, $mvFile, $branch, $batch, $remarks, $dateReg, $recordId);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Record updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update record.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>