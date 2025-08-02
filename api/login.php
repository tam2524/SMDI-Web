<?php
include 'db_config.php';

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['position'] = $user['position']; 

        // Trim any extra whitespace from the position
        $position = trim($user['position']);

        // Redirect based on position
        switch ($position) {
            case 'IT Staff':
            case 'Head':
            case 'Branch Manager':
                header("Location: ../staffDashboard.html");
                break;
            case 'Liaison':
                header("Location: ../liason_dashboard.php");
                break;
            default:
                // Fallback if no matching case
                header("Location: ../login.html");
                break;
        }
        exit();
    } else {
        echo '<script>
            alert("Invalid username or password");
            window.location.href = "../login.html";
        </script>';
    }
} else {
    echo '<script>
        alert("Invalid username or password");
        window.location.href = "../login.html";
    </script>';
}

$stmt->close();
$conn->close();
?>
