<?php


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * 
 *
 * @param mysqli 
 * @param string 
 * @param string 
 * @param int|null 
 * @param string
 */
function log_action($conn, $action_type, $table_name, $record_id, $details = '') {

    if (!$conn || $conn->connect_error) {
        error_log("Audit Log Error: Database connection is not available.");
        return;
    }

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'SYSTEM';

    $stmt = $conn->prepare(
        "INSERT INTO audit_log (user_id, username, action_type, table_name, record_id, action_details) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if ($stmt === false) {
        error_log("Audit Log Error: Failed to prepare statement: " . $conn->error);
        return;
    }

    $stmt->bind_param("isssis", $user_id, $username, $action_type, $table_name, $record_id, $details);

    if (!$stmt->execute()) {
        error_log("Audit Log Error: Failed to execute statement: " . $stmt->error);
    }

    $stmt->close();
}
?>