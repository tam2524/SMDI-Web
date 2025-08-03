<?php
include 'db_config.php';

$query = $_GET['query'] ?? '';

$sql = "SELECT * FROM records 
        WHERE family_name LIKE ? 
        OR first_name LIKE ? 
        OR middle_name LIKE ? 
        OR plate_number LIKE ? 
        OR mv_file LIKE ? 
        OR branch LIKE ? 
        OR batch LIKE ?";

$stmt = mysqli_prepare($conn, $sql);
$searchTerm = "%$query%";
mysqli_stmt_bind_param($stmt, "sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    while ($record = mysqli_fetch_assoc($result)) {
        echo "<tr data-id='{$record['record_id']}'>
                <td class='no-print'><input type='checkbox' name='recordCheckbox'></td>
                <td>{$record['family_name']}</td>
                <td>{$record['first_name']}</td>
                <td>{$record['middle_name']}</td>
                <td>{$record['plate_number']}</td>
                <td>{$record['mv_file']}</td>
                <td>{$record['branch']}</td>
                <td>{$record['batch']}</td>
                <td>{$record['remarks']}</td>
                <td class='no-print'>
                    <button class='btn btn-sm text-white btn-primary edit-button'>Edit</button>
         
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='10'>No records found</td></tr>";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
