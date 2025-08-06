<?php
require_once '../api/db_config.php';
header('Content-Type: application/json');

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Date range parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Build WHERE clause for date range
$where_clause = '';
if ($start_date && $end_date) {
    $where_clause = "WHERE visit_time BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'";
} elseif ($start_date) {
    $where_clause = "WHERE visit_time >= '{$start_date} 00:00:00'";
} elseif ($end_date) {
    $where_clause = "WHERE visit_time <= '{$end_date} 23:59:59'";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM visitor_logs $where_clause";
$result = $conn->query($count_query);
$total_records = $result->fetch_row()[0];
$total_pages = ceil($total_records / $per_page);

// Get paginated logs
$query = "SELECT * FROM visitor_logs $where_clause ORDER BY visit_time DESC LIMIT $per_page OFFSET $offset";
$result = $conn->query($query);
$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

echo json_encode([
    'logs' => $logs,
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_records' => $total_records
]);

$conn->close();
?>