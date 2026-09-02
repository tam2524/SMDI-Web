<?php
include 'db_config.php';

$username = trim($_POST['username']);
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax'] == 1);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['position'] = $user['position'];
        $_SESSION['user_branch'] = $user['branch'];
        $_SESSION['report_header_title'] = $user['report_header_title'] ?? 'ROXAS CITY SOLID MERCHANDISING';
        $position = trim($user['position']);

        $redirectUrl = "login.html";
        switch ($position) {
            case 'IT Staff':
            case 'Admin':
                $redirectUrl = "admin/admin_dashboard.php";
                break;
            case 'Head':
            case 'Branch Manager':
                $redirectUrl = "staff/staff_dashboard.html";
                break;
            case 'Liaison':
                $redirectUrl = "liaison/liaison_dashboard.php";
                break;
            case 'Sales':
                $redirectUrl = "sales/sales_dashboard.php";
                break;
            case 'BM':
                $redirectUrl = "inventory/branch_inventory.php";
                break;
            case 'Inventory':
                $redirectUrl = "inventory/headoffice_inventory.php";
                break;
            case 'Admin Inventory':
                $redirectUrl = "inventory/admin_inventory.php";
                break;
            case 'Spareparts-Warehouse':
                $redirectUrl = "spareparts/warehouse_dashboard.php";
                break;
            case 'Spareparts-Sales':
            case 'Spareparts-Retail':
                $redirectUrl = "spareparts/sales_dashboard.php";
                break;
            case 'Spareparts-Owner':
                $redirectUrl = "spareparts/owner_dashboard.php";
                break;
            case 'Spareparts-Admin':
                $redirectUrl = "spareparts/admin_dashboard.php";
                break;
            case 'Disable':
                $redirectUrl = "under_repair.html";
                break;
            default:
                $redirectUrl = "login.html";
                break;
        }

        if ($isAjax) {
            echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
        } else {
            header("Location: ../" . $redirectUrl);
        }
        exit();
    } else {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        } else {
            echo '<script>
                alert("Invalid password");
                window.location.href = "../login.html";
            </script>';
        }
    }
} else {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    } else {
        echo '<script>
            alert("User not found");
            window.location.href = "../login.html";
        </script>';
    }
}

$stmt->close();
$conn->close();
?>