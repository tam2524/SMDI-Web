<?php
require_once 'db_config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Get data from both POST and GET (for fallback)
    $data = json_decode(file_get_contents('php://input'), true) ?? [
        'page' => $_GET['page'] ?? 'Unknown',
        'referrer' => $_GET['referrer'] ?? 'Direct'
    ];

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $page_visited = $data['page'] ?? 'Unknown';
    $referrer = $data['referrer'] ?? 'Direct';
    $screen_info = isset($data['screen_width']) ? $data['screen_width'].'x'.$data['screen_height'] : 'Unknown';
    $language = $data['language'] ?? 'Unknown';

    // Validate input
    if (strlen($page_visited) > 255) $page_visited = substr($page_visited, 0, 255);
    if (strlen($referrer) > 255) $referrer = substr($referrer, 0, 255);

    // Check for unique visit (same IP within last hour)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM visitor_logs 
                          WHERE ip_address = ? AND visit_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        $stmt = $conn->prepare("INSERT INTO visitor_logs 
                              (ip_address, user_agent, page_visited, referrer, screen_info, language) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $ip_address, $user_agent, $page_visited, $referrer, $screen_info, $language);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Visit recorded'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Tracking failed',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>