<?php
require_once 'db_config.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Get JSON input from frontend
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Validate required fields
    if (empty($data['page'])) {
        throw new Exception('Missing page parameter');
    }

    // Prepare data for database
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $page_visited = substr($data['page'], 0, 255); // Truncate to match VARCHAR(255)
    $referrer = substr($data['referrer'] ?? 'direct', 0, 255);

    // Check for existing visit (same IP within 1 hour)
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
        // Insert new visit
        $stmt = $conn->prepare("
            INSERT INTO visitor_logs 
            (ip_address, user_agent, page_visited, referrer) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $ip_address, $user_agent, $page_visited, $referrer);
        
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
    
    // Log full error for debugging
    error_log("Tracking Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>