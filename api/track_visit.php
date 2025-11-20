<?php
require_once 'db_config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (empty($data['page'])) {
        throw new Exception('Missing page parameter');
    }

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $page_visited = substr($data['page'], 0, 255);
    $referrer = substr($data['referrer'] ?? 'direct', 0, 255);

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM visitor_logs 
        WHERE ip_address = ? 
        AND visit_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $response = ['status' => 'exists'];
    
    if ($count == 0) {
        $stmt = $conn->prepare("
            INSERT INTO visitor_logs 
            (ip_address, user_agent, page_visited) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $ip_address, $user_agent, $page_visited);

        if (!$stmt->execute()) {
            throw new Exception("Database insert failed: " . $stmt->error);
        }
        
        $stmt->close();
        $response = ['status' => 'recorded', 'record_id' => $conn->insert_id];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    
    error_log("Tracking Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}

if (isset($conn)) {
    $conn->close();
}
?>