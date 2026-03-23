<?php
require_once '../api/db_config.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only Spareparts-Admin and Spareparts-Owner can access this API
if (!isset($_SESSION['position']) || !in_array($_SESSION['position'], ['Spareparts-Admin', 'Spareparts-Owner'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);
if ($data === null) {
    $data = $_POST;
}

try {
    switch ($action) {
        case 'get_users':
            getUsers($conn);
            break;

        case 'get_user':
            getUser($conn);
            break;

        case 'add_user':
            addUser($conn, $data);
            break;

        case 'edit_user':
            editUser($conn, $data);
            break;

        case 'delete_user':
            deleteUser($conn, $data);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getUsers($conn)
{
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchTerm = "%$search%";

    // STRICTLY ONLY SPAREPARTS USERS - Use parentheses for OR conditions to ensure AND search applies correctly
    $baseQuery = " FROM users WHERE (position LIKE 'Spareparts-%' OR position = 'f')";
    
    $sql = "SELECT id, username, fullName, position, branch" . $baseQuery;
    $countSql = "SELECT COUNT(*) as total" . $baseQuery;

    if (!empty($search)) {
        $sql .= " AND (username LIKE ? OR fullName LIKE ? OR position LIKE ? OR branch LIKE ?)";
        $countSql .= " AND (username LIKE ? OR fullName LIKE ? OR position LIKE ? OR branch LIKE ?)";
    }
    
    $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($countSql);
    if (!empty($search)) {
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $stmt->bind_param("ssssii", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $perPage, $offset);
    } else {
        $stmt->bind_param("ii", $perPage, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => $total,
        'current_page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ]);
}

function getUser($conn)
{
    if (!isset($_GET['id'])) {
        throw new Exception('User ID is required');
    }

    $id = intval($_GET['id']);
    // Ensure we only fetch spareparts users
    $stmt = $conn->prepare("SELECT id, username, fullName, position, branch FROM users WHERE id = ? AND (position LIKE 'Spareparts-%' OR position = 'f')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('User not found or unauthorized');
    }

    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user' => $user]);
}

function enforceSparepartsRole($position) {
    if (strpos($position, 'Spareparts-') !== 0 && $position !== 'f') {
        throw new Exception("You are only allowed to assign 'Spareparts-' roles.");
    }
}

function addUser($conn, $data)
{
    $required = ['username', 'password', 'confirmPassword', 'position'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    enforceSparepartsRole($data['position']);

    if ($data['password'] !== $data['confirmPassword']) {
        throw new Exception('Passwords do not match');
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $data['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Username already exists');
    }

    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $fullName = $data['fullName'] ?? null;
    $position = $data['position'];
    $branch = $data['branch'] ?? null;

    $stmt = $conn->prepare("INSERT INTO users (username, fullName, position, branch, password) 
                           VALUES (?, ?, ?, ?, ?)");
    $username = trim($data['username']);
    $stmt->bind_param(
        "sssss",
        $username,
        $fullName,
        $position,
        $branch,
        $hashedPassword
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } else {
        throw new Exception('Failed to create user: ' . $stmt->error);
    }
}

function editUser($conn, $data)
{
    if (empty($data['id'])) {
        throw new Exception('User ID is required');
    }

    $id = intval($data['id']);

    // Ensure they are only editing a spareparts user
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND (position LIKE 'Spareparts-%' OR position = 'f')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('User not found or unauthorized');
    }

    if (!empty($data['username'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $data['username'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Username already exists');
        }
    }

    $sql = "UPDATE users SET ";
    $params = [];
    $types = "";
    $updates = [];

    if (!empty($data['username'])) {
        $updates[] = "username = ?";
        $username = trim($data['username']);
        $params[] = $username;
        $types .= "s";
    }

    if (isset($data['fullName'])) {
        $updates[] = "fullName = ?";
        $params[] = $data['fullName'];
        $types .= "s";
    }

    if (isset($data['position'])) {
        enforceSparepartsRole($data['position']);
        $updates[] = "position = ?";
        $params[] = $data['position'];
        $types .= "s";
    }

    if (isset($data['branch'])) {
        $updates[] = "branch = ?";
        $params[] = $data['branch'];
        $types .= "s";
    }

    if (!empty($data['password'])) {
        if ($data['password'] !== ($data['confirmPassword'] ?? '')) {
            throw new Exception('Passwords do not match');
        }
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $params[] = $hashedPassword;
        $types .= "s";
    }

    if (empty($updates)) {
        throw new Exception('No fields to update');
    }

    $sql .= implode(", ", $updates) . " WHERE id = ?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        throw new Exception('Failed to update user: ' . $stmt->error);
    }
}

function deleteUser($conn, $data)
{
    if (empty($data['id'])) {
        throw new Exception('User ID is required');
    }

    $id = intval($data['id']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND (position LIKE 'Spareparts-%' OR position = 'f')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('User not found or unauthorized');
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND (position LIKE 'Spareparts-%' OR position = 'f')");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 1) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            throw new Exception('User not found or already deleted');
        }
    } else {
        throw new Exception('Failed to delete user: ' . $stmt->error);
    }
}

$conn->close();
?>
