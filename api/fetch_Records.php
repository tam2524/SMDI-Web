<?php
include '../api/db_config.php';

// Get parameters from request
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : null;

// Validate and sanitize
$page = max(1, $page);
$recordsPerPage = 15;
$offset = ($page - 1) * $recordsPerPage;

// Base SQL query
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM records 
        WHERE family_name LIKE ? 
        OR first_name LIKE ? 
        OR middle_name LIKE ? 
        OR plate_number LIKE ? 
        OR mv_file LIKE ? 
        OR branch LIKE ? 
        OR batch LIKE ?";

// Add sorting
if ($sort) {
    $validSortColumns = ['family_name', 'batch', 'branch'];
    $sortDirection = 'ASC'; // or add logic for DESC if needed
    
    if (in_array($sort, $validSortColumns)) {
        $sql .= " ORDER BY $sort $sortDirection";
    }
} else {
    // Default sorting
    $sql .= " ORDER BY record_id DESC";
}

// Add pagination
$sql .= " LIMIT ? OFFSET ?";

// Prepare and execute
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die(json_encode(['error' => 'Database error']));
}

$searchTerm = "%$query%";
mysqli_stmt_bind_param($stmt, "sssssssii", 
    $searchTerm, $searchTerm, $searchTerm,
    $searchTerm, $searchTerm, $searchTerm,
    $searchTerm, $recordsPerPage, $offset);

if (!mysqli_stmt_execute($stmt)) {
    die(json_encode(['error' => 'Query execution failed']));
}

$result = mysqli_stmt_get_result($stmt);

// Get total records
$totalRecordsResult = mysqli_query($conn, "SELECT FOUND_ROWS()");
$totalRecords = mysqli_fetch_row($totalRecordsResult)[0];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Generate HTML
ob_start();
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr data-id='{$row['record_id']}'>
                <td class='no-print'><input type='checkbox' name='recordCheckbox'></td>
                <td>{$row['family_name']}</td>
                <td>{$row['first_name']}</td>
                <td>{$row['middle_name']}</td>
                <td>{$row['plate_number']}</td>
                <td>{$row['mv_file']}</td>
                <td>{$row['branch']}</td>
                <td>{$row['batch']}</td>
                <td>{$row['remarks']}</td>
                <td class='no-print'>
                    <button class='btn btn-sm text-white btn-primary edit-button'>Edit</button>
                    <button class='btn btn-sm text-white btn-primary delete-button'>Delete</button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='10' class='text-center'>No records found</td></tr>";
}

$html = ob_get_clean();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'html' => $html,
    'pagination' => [
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalRecords' => $totalRecords
    ]
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>