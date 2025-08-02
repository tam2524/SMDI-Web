<?php
include 'db_config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST["registerUsername"];
    $fullName = $_POST["registerFullName"];
    $position = $_POST["registerPosition"];
    $branch = $_POST["registerBranch"];
    $password = $_POST["registerPassword"];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

 
    $stmt = $conn->prepare("INSERT INTO users (username, fullName, position, branch, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $fullName, $position, $branch, $hashedPassword);

    
    if ($stmt->execute()) {
        
        echo '<script>alert("Registration successful! You can now log in.");</script>';
    } else {
        
        echo "Error: " . $stmt->error;
    }

   
    $stmt->close();
    $conn->close();
}


header("Location: login.html");
exit();

?>
