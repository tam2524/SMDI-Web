<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Get the requested action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'get_sales':
        handleGetSales();
        break;
    case 'get_sale':
        handleGetSale();
        break;
    case 'add_sale':
        handleAddSale();
        break;
    case 'update_sale':
        handleUpdateSale();
        break;
    case 'delete_sale':
        handleDeleteSale();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleGetSales() {
    global $conn;

    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 10; // Records per page
    $offset = ($page - 1) * $perPage;

    // Get search query
    $query = isset($_GET['query']) ? $_GET['query'] : '';
    $searchCondition = '';
    if (!empty($query)) {
        $searchCondition = "WHERE branch LIKE '%$query%' OR brand LIKE '%$query%' OR model LIKE '%$query%'";
    }

    // Get sort parameter
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'sales_date DESC';
    $validSorts = [
        'date' => 'sales_date',
        'branch' => 'branch',
        'brand' => 'brand'
    ];
    
    $sortColumn = $validSorts[$sort] ?? 'sales_date';
    $sortOrder = 'DESC'; // Default sort order

    // Count total records for pagination
    $countQuery = "SELECT COUNT(*) as total FROM sales $searchCondition";
    $countResult = $conn->query($countQuery);
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    // Get paginated data
    $dataQuery = "SELECT id, sales_date, branch, brand, model, qty FROM sales 
                 $searchCondition
                 ORDER BY $sortColumn $sortOrder
                 LIMIT $perPage OFFSET $offset";
    
    $result = $conn->query($dataQuery);

    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        return;
    }

    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }

    echo json_encode([
        'success' => true, 
        'data' => $sales,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
}

function handleGetSale() {
    global $conn;

    if (empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
        return;
    }

    $saleId = (int)$_GET['id'];

    $query = "SELECT id, sales_date, branch, brand, model, qty FROM sales WHERE id = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        return;
    }

    $stmt->bind_param('i', $saleId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        return;
    }

    $sale = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $sale]);

    $stmt->close();
}

function handleAddSale() {
    global $conn;

    $data = $_POST;

    $required = ['sales_date', 'branch', 'brand', 'model', 'qty'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $query = "INSERT INTO sales (sales_date, branch, brand, model, qty) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        return;
    }

    $stmt->bind_param('ssssi', 
        $data['sales_date'],
        $data['branch'],
        $data['brand'],
        $data['model'],
        $data['qty']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sale added successfully']);
    } else {
        // Check for duplicate entry
        if ($conn->errno == 1062) {
            echo json_encode(['success' => false, 'message' => 'Duplicate entry: This sale already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add sale: ' . $stmt->error]);
        }
    }

    $stmt->close();
}

function handleUpdateSale() {
    global $conn;

    $data = $_POST;

    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
        return;
    }

    $required = ['sales_date', 'branch', 'brand', 'model', 'qty'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $query = "UPDATE sales SET 
              sales_date = ?,
              branch = ?,
              brand = ?,
              model = ?,
              qty = ?
              WHERE id = ?";

    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        return;
    }

    $stmt->bind_param('ssssii', 
        $data['sales_date'],
        $data['branch'],
        $data['brand'],
        $data['model'],
        $data['qty'],
        $data['id']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sale updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update sale: ' . $stmt->error]);
    }

    $stmt->close();
}

function handleDeleteSale() {
    global $conn;

    $data = $_POST;

    if (empty($data['ids'])) {
        echo json_encode(['success' => false, 'message' => 'Sale ID(s) are required']);
        return;
    }

    // Handle both single ID and array of IDs
    $ids = is_array($data['ids']) ? $data['ids'] : [$data['ids']];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $query = "DELETE FROM sales WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        return;
    }

    // Dynamically bind parameters
    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sale(s) deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete sale(s): ' . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>