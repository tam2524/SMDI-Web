<?php
include 'db_config.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Define valid sorting columns
$validSortColumns = ['family_name', 'first_name', 'middle_name', 'plate_number', 'mv_file', 'branch', 'batch'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $validSortColumns) ? $_GET['sort'] : 'family_name'; // Default sorting

// Get batch range parameters
$fromBatch = isset($_GET['fromBatch']) ? $_GET['fromBatch'] : '';
$toBatch = isset($_GET['toBatch']) ? $_GET['toBatch'] : '';

$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 20; // Set the number of records per page
$offset = ($currentPage - 1) * $recordsPerPage;

// Prepare the base SQL query
$sql = "SELECT * FROM records WHERE (family_name LIKE ? 
        OR first_name LIKE ? 
        OR middle_name LIKE ? 
        OR plate_number LIKE ? 
        OR mv_file LIKE ? 
        OR branch LIKE ? 
        OR batch LIKE ?)";

// Add batch filtering if provided
if ($fromBatch !== '' && $toBatch !== '') {
    $sql .= " AND batch BETWEEN ? AND ?";
}

// Append sorting and pagination
$sql .= " ORDER BY $sort LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
$searchTerm = "%$query%";

// Prepare parameters
if ($fromBatch !== '' && $toBatch !== '') {
    mysqli_stmt_bind_param($stmt, "ssssssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $fromBatch, $toBatch, $offset, $recordsPerPage);
} else {
    mysqli_stmt_bind_param($stmt, "ssssssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $offset, $recordsPerPage);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch the total number of records for pagination
$totalSql = "SELECT COUNT(*) as total FROM records WHERE (family_name LIKE ? 
              OR first_name LIKE ? 
              OR middle_name LIKE ? 
              OR plate_number LIKE ? 
              OR mv_file LIKE ? 
              OR branch LIKE ? 
              OR batch LIKE ?)";

// Add batch filtering for total count
if ($fromBatch !== '' && $toBatch !== '') {
    $totalSql .= " AND batch BETWEEN ? AND ?";
}

$totalStmt = mysqli_prepare($conn, $totalSql);
mysqli_stmt_bind_param($totalStmt, "sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);

if ($fromBatch !== '' && $toBatch !== '') {
    mysqli_stmt_bind_param($totalStmt, "sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $fromBatch, $toBatch);
}

mysqli_stmt_execute($totalStmt);
$totalResult = mysqli_stmt_get_result($totalStmt);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

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

// Display pagination links
if ($totalPages > 1) {
    echo "<div class='pagination'>";
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            echo "<span>$i</span>"; // Current page
        } else {
            echo "<a href='?query=" . urlencode($query) . "&page=$i'>$i</a>";
        }
    }
    echo "</div>";
}

mysqli_stmt_close($stmt);
mysqli_stmt_close($totalStmt);
mysqli_close($conn);

header('Content-Type: text/html; charset=utf-8');
echo "<pre>";
echo htmlspecialchars($output); // Assuming $output contains the generated HTML
echo "</pre>";
?>

