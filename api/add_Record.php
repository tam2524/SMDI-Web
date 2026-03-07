<?php
include '../api/db_config.php';

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
    $checkDuplicateQuery = "SELECT mv_file, plate_number FROM records WHERE mv_file = ?";
    $checkStmt = mysqli_prepare($conn, $checkDuplicateQuery);
    mysqli_stmt_bind_param($checkStmt, "s", $mvFile);
} else {
    $checkDuplicateQuery = "SELECT mv_file, plate_number FROM records WHERE plate_number = ? OR mv_file = ?";
    $checkStmt = mysqli_prepare($conn, $checkDuplicateQuery);
    mysqli_stmt_bind_param($checkStmt, "ss", $plateNumber, $mvFile);
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
} else {
    $sql = "INSERT INTO records (family_name, first_name, middle_name, plate_number, mv_file, branch, batch, remarks, date_reg) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssss", $familyName, $firstName, $middleName, $plateNumber, $mvFile, $branch, $batch, $remarks, $dateReg);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Record added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add record.']);
    }

    mysqli_stmt_close($stmt);
}

mysqli_stmt_close($checkStmt);
mysqli_close($conn);
?>