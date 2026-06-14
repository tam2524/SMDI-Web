<?php
header('Content-Type: text/plain');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    die("Unauthorized access. Please log in first.");
}

echo "=== Git Clean & Deploy Utility ===\n";
echo "Attempting to reset uncommitted files on server...\n\n";

if (function_exists('shell_exec')) {
    // Discard modified tracked files
    $resetOut = shell_exec('git reset --hard HEAD 2>&1');
    echo "Git Reset Output:\n$resetOut\n\n";

    // Clean untracked files
    $cleanOut = shell_exec('git clean -fd 2>&1');
    echo "Git Clean Output:\n$cleanOut\n\n";
    
    echo "Done! Try deploying again in cPanel.";
} else {
    echo "Error: shell_exec() function is disabled on this server's PHP configuration.\n";
    echo "Please use the cPanel Terminal or SSH, navigate to the directory, and run:\n";
    echo "  git reset --hard HEAD\n";
    echo "  git clean -fd\n";
}
?>
