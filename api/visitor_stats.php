<?php
require_once '../api/db_config.php';
header('Content-Type: application/json');

// Get date range from request
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

// Total visits
$query = "SELECT COUNT(*) as total_visits FROM visitor_logs $where_clause";
$result = $conn->query($query);
$total_visits = $result->fetch_assoc()['total_visits'];

// Unique visitors (count distinct IPs)
$query = "SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM visitor_logs $where_clause";
$result = $conn->query($query);
$unique_visitors = $result->fetch_assoc()['unique_visitors'];

// Today's visits (total and unique)
$query_today_total = "SELECT COUNT(*) as today_visits FROM visitor_logs WHERE DATE(visit_time) = CURDATE()";
$result_today_total = $conn->query($query_today_total);
$today_visits_total = $result_today_total->fetch_assoc()['today_visits'];

$query_today_unique = "SELECT COUNT(DISTINCT ip_address) as today_unique_visitors FROM visitor_logs WHERE DATE(visit_time) = CURDATE()";
$result_today_unique = $conn->query($query_today_unique);
$today_visits_unique = $result_today_unique->fetch_assoc()['today_unique_visitors'];

// This month's visits
$query = "SELECT COUNT(*) as month_visits FROM visitor_logs WHERE MONTH(visit_time) = MONTH(CURDATE()) AND YEAR(visit_time) = YEAR(CURDATE())";
$result = $conn->query($query);
$month_visits = $result->fetch_assoc()['month_visits'];

echo json_encode([
    'total_visits' => $total_visits,
    'unique_visitors' => $unique_visitors,
    'today_visits' => $today_visits_total,
    'month_visits' => $month_visits
]);

$conn->close();
?>