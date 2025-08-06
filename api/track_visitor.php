<?php
require_once 'db_config.php';

function trackVisitor() {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $page_visited = $_SERVER['REQUEST_URI'];
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Check if this is a unique visit (same IP within last hour)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM visitor_logs 
                           WHERE ip_address = ? AND visit_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param("s", $ip_address);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count == 0) {
        $stmt = $conn->prepare("INSERT INTO visitor_logs 
                              (ip_address, user_agent, page_visited, referrer) 
                              VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $ip_address, $user_agent, $page_visited, $referrer);
        $stmt->execute();
        $stmt->close();
    }
}

// Call this function on your public pages
// trackVisitor();
?>