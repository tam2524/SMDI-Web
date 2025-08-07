<?php
require_once 'db_config.php';

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';

// Change to handle both POST form data and JSON
$data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : json_decode(file_get_contents('php://input'), true);

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

function getUsers($conn) {
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Search parameter
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    
    // Base query
    $sql = "SELECT id, username, fullName, position, branch FROM users WHERE 1=1";
    $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    
    // Add search conditions if provided
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $sql .= " AND (username LIKE ? OR fullName LIKE ? OR email LIKE ?)";
        $countSql .= " AND (username LIKE ? OR fullName LIKE ? OR email LIKE ?)";
    }
    
    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    
    // Get total count
    $stmt = $conn->prepare($countSql);
    if (!empty($search)) {
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    
    // Get paginated results
    $stmt = $conn->prepare($sql);
    if (!empty($search)) {
        $stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $perPage, $offset);
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

function getUser($conn) {
    if (!isset($_GET['id'])) {
        throw new Exception('User ID is required');
    }
    
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, username, fullName, position, branch FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user' => $user]);
}

function addUser($conn, $data) {
    // Validate required fields
    $required = ['username', 'password', 'confirmPassword'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    if ($data['password'] !== $data['confirmPassword']) {
        throw new Exception('Passwords do not match');
    }
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $data['username']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Username already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Set default values for optional fields
    $fullName = $data['fullName'] ?? null;
    $position = $data['position'] ?? null;
    $branch = $data['branch'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO users (username, fullName, position, branch, password) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", 
        $data['username'],
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

function editUser($conn, $data) {
    // Validate required fields
    if (empty($data['id'])) {
        throw new Exception('User ID is required');
    }
    
    $id = intval($data['id']);
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    // Check if username is being changed to one that already exists
    if (!empty($data['username'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $data['username'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Username already exists');
        }
    }
    
    // Build update query
    $sql = "UPDATE users SET ";
    $params = [];
    $types = "";
    $updates = [];
    
    // Add fields to update
    if (!empty($data['username'])) {
        $updates[] = "username = ?";
        $params[] = $data['username'];
        $types .= "s";
    }
    
    if (isset($data['fullName'])) {
        $updates[] = "fullName = ?";
        $params[] = $data['fullName'];
        $types .= "s";
    }
    
    if (isset($data['position'])) {
        $updates[] = "position = ?";
        $params[] = $data['position'];
        $types .= "s";
    }
    
    if (isset($data['branch'])) {
        $updates[] = "branch = ?";
        $params[] = $data['branch'];
        $types .= "s";
    }
    
    // Update password if provided
    if (!empty($data['password'])) {
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

function deleteUser($conn, $data) {
    if (empty($data['id'])) {
        throw new Exception('User ID is required');
    }
    
    $id = intval($data['id']);
    
    // Check if user exists first
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
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