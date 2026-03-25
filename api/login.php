<?php
include 'db_config.php';

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['position'] = $user['position'];
        $_SESSION['user_branch'] = $user['branch'];
        $position = trim($user['position']);

        switch ($position) {
            case 'IT Staff':
            case 'Admin':
                // header("Location: ../under_repair.html");
                header("Location: ../admin/admin_dashboard.php");
                break;
            case 'Head':
            case 'Branch Manager':
                header("Location: ../staff/staff_dashboard.html");
                break;
            case 'Liaison':
                header("Location: ../liaison/liaison_dashboard.php");
                break;
            case 'Sales':
                header("Location: ../sales/sales_dashboard.php");
                break;
            case 'BM':
                header("Location: ../inventory/branch_inventory.php");
                // header("Location: ../under_repair.html");
                break;
            case 'Inventory':
                header("Location: ../inventory/headoffice_inventory.php");
                // header("Location: ../under_repair.html");
                break;
            case 'Admin Inventory':
                header("Location: ../inventory/admin_inventory.php");
                // header("Location: ../under_repair.html");
                break;
            case 'Spareparts-Warehouse':
                header("Location: ../spareparts/warehouse_dashboard.php");
                break;
            case 'Spareparts-Sales':
                header("Location: ../spareparts/sales_dashboard.php");
                break;
            case 'Spareparts-Retail':
                header("Location: ../spareparts/sales_dashboard.php");
                break;
            case 'Spareparts-Owner':
                header("Location: ../spareparts/owner_dashboard.php");
                break;
            case 'Spareparts-Admin':
                header("Location: ../spareparts/admin_dashboard.php");
                break;
            case 'Disable':
                header("Location: ../under_repair.html");
                break;
            default:
                // Fallback if no matching case
                header("Location: ../login.html");
                break;
        }
        exit();
    }
    else {
        echo '<script>
            alert("Invalid password");
            window.location.href = "../login.html";
        </script>';
    }
}
else {
    echo '<script>
        alert("User not found");
        window.location.href = "../login.html";
    </script>';
}

$stmt->close();
$conn->close();
?>