<?php
header('Content-Type: application/json');
require_once '../api/db_config.php';

function sanitizeInput($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

$action = isset($_REQUEST['action']) ? sanitizeInput($_REQUEST['action']) : '';

switch ($action) {
    case 'get_inventory_dashboard':
        getInventoryDashboard();
        break;
    case 'get_inventory_table':
        getInventoryTable();
        break;
    case 'get_motorcycle':
        getMotorcycle();
        break;
      case 'get_motorcycle_transfers':
        getMotorcycleTransfers();
        break;
    
    case 'add_motorcycle':
        addMotorcycle();
        break;
    case 'update_motorcycle':
        updateMotorcycle();
        break;
    case 'delete_motorcycle':
        deleteMotorcycle();
        break;
    case 'delete_multiple_motorcycles':
        deleteMultipleMotorcycles();
        break;
    case 'transfer_motorcycle':
        transferMotorcycle();
        break;
    case 'get_transfer_history':
        getTransferHistory();
        break;
    case 'get_branch_inventory':
        getBranchInventory();
        break;
    case 'get_branches_with_inventory':
        getBranchesWithInventory();
        break;
    case 'search_inventory':
        searchInventory();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getInventoryDashboard() {
    global $conn;
    
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    $sql = "SELECT model, brand, SUM(quantity) as total_quantity 
            FROM motorcycle_inventory 
            WHERE status = 'available'";
    
    if (!empty($search)) {
        $sql .= " AND (model LIKE '%$search%' OR brand LIKE '%$search%')";
    }
    
    $sql .= " GROUP BY model, brand ORDER BY total_quantity DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching inventory data: ' . $conn->error]);
    }
}

function getMotorcycleTransfers() {
    global $conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $stmt = $conn->prepare("SELECT it.*, u.username as transferred_by_name 
                          FROM inventory_transfers it
                          LEFT JOIN users u ON it.transferred_by = u.id
                          WHERE motorcycle_id = ? 
                          ORDER BY transfer_date DESC");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function getInventoryTable() {
    global $conn;
    
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Sorting parameters
    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : '';
    $sortField = 'date_delivered';
    $sortOrder = 'DESC';
    
    if (!empty($sort)) {
        $parts = explode('_', $sort);
        $validFields = ['date_delivered', 'brand', 'model', 'current_branch', 'status'];
        
        if (in_array($parts[0], $validFields)) {
            $sortField = $parts[0];
            $sortOrder = strtoupper($parts[1]) === 'ASC' ? 'ASC' : 'DESC';
        }
    }
    
    // Search parameters
    $search = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $where = "WHERE status != 'deleted'";
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $where .= " AND (model LIKE ? OR brand LIKE ? OR engine_number LIKE ? 
                  OR frame_number LIKE ? OR color LIKE ? OR current_branch LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill(0, 6, $searchTerm);
        $types = str_repeat('s', count($params));
    }
    
    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM motorcycle_inventory $where";
    $countStmt = $conn->prepare($countSql);
    
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get paginated data
    $sql = "SELECT * FROM motorcycle_inventory $where 
            ORDER BY $sortField $sortOrder 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    if (!empty($params)) {
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $perPage, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalRecords,
            'itemsPerPage' => $perPage
        ]
    ]);
}

function getMotorcycle() {
    global $conn;
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Get motorcycle details
    $stmt = $conn->prepare("SELECT * FROM motorcycle_inventory WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Get transfer history if motorcycle is transferred
        if ($data['status'] === 'transferred') {
            $transferStmt = $conn->prepare("SELECT * FROM inventory_transfers 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY transfer_date DESC");
            $transferStmt->bind_param('i', $id);
            $transferStmt->execute();
            $transferResult = $transferStmt->get_result();
            
            $transfers = [];
            while ($row = $transferResult->fetch_assoc()) {
                $transfers[] = $row;
            }
            
            $data['transfer_history'] = $transfers;
        }
        
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found']);
    }
}
function addMotorcycle() {
    global $conn;
    
    $required = ['date_delivered', 'brand', 'model', 'engine_number', 'frame_number', 'color', 'current_branch'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    $dateDelivered = sanitizeInput($_POST['date_delivered']);
    $brand = sanitizeInput($_POST['brand']);
    $model = sanitizeInput($_POST['model']);
    $engineNumber = sanitizeInput($_POST['engine_number']);
    $frameNumber = sanitizeInput($_POST['frame_number']);
    $color = sanitizeInput($_POST['color']);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $lcp = !empty($_POST['lcp']) ? floatval($_POST['lcp']) : null;
    $currentBranch = sanitizeInput($_POST['current_branch']);
    
    // Check for duplicates
    $checkStmt = $conn->prepare("SELECT id FROM motorcycle_inventory WHERE engine_number = ? OR frame_number = ?");
    $checkStmt->bind_param('ss', $engineNumber, $frameNumber);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A motorcycle with this engine or frame number already exists']);
        return;
    }
    
    $conn->begin_transaction();
    $successCount = 0;
    
    try {
        for ($i = 0; $i < $quantity; $i++) {
            $currentEngineNumber = $quantity > 1 ? $engineNumber . '-' . ($i + 1) : $engineNumber;
            $currentFrameNumber = $quantity > 1 ? $frameNumber . '-' . ($i + 1) : $frameNumber;
            
            $stmt = $conn->prepare("INSERT INTO motorcycle_inventory 
                                   (date_delivered, brand, model, engine_number, frame_number, color, lcp, current_branch, status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            
            $stmt->bind_param('ssssssds', $dateDelivered, $brand, $model, $currentEngineNumber, $currentFrameNumber, $color, $lcp, $currentBranch);
            
            if ($stmt->execute()) {
                $successCount++;
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Successfully added $successCount motorcycle(s)"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error adding motorcycle: ' . $e->getMessage()]);
    }
}

function updateMotorcycle() {
    global $conn;
    
    $required = ['id', 'date_delivered', 'brand', 'model', 'engine_number', 'frame_number', 'color', 'current_branch', 'status'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    $id = intval($_POST['id']);
    $dateDelivered = sanitizeInput($_POST['date_delivered']);
    $brand = sanitizeInput($_POST['brand']);
    $model = sanitizeInput($_POST['model']);
    $engineNumber = sanitizeInput($_POST['engine_number']);
    $frameNumber = sanitizeInput($_POST['frame_number']);
    $color = sanitizeInput($_POST['color']);
    $lcp = !empty($_POST['lcp']) ? floatval($_POST['lcp']) : null;
    $currentBranch = sanitizeInput($_POST['current_branch']);
    $status = sanitizeInput($_POST['status']);
    
    // Check for duplicates excluding current record
    $checkStmt = $conn->prepare("SELECT id FROM motorcycle_inventory 
                                WHERE (engine_number = ? OR frame_number = ?) AND id != ?");
    $checkStmt->bind_param('ssi', $engineNumber, $frameNumber, $id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Another motorcycle with this engine or frame number already exists']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE motorcycle_inventory 
                           SET date_delivered = ?, brand = ?, model = ?, engine_number = ?, 
                               frame_number = ?, color = ?, lcp = ?, current_branch = ?, status = ?
                           WHERE id = ?");
    $stmt->bind_param('ssssssdssi', $dateDelivered, $brand, $model, $engineNumber, 
                      $frameNumber, $color, $lcp, $currentBranch, $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Motorcycle updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating motorcycle: ' . $conn->error]);
    }
}

function deleteMotorcycle() {
    global $conn;
    
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'deleted', deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Motorcycle deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting motorcycle: ' . $conn->error]);
    }
}

function deleteMultipleMotorcycles() {
    global $conn;
    
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    
    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => 'No motorcycles selected for deletion']);
        return;
    }
    
    $sanitizedIds = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    
    $conn->begin_transaction();
    
    try {
        // Soft delete multiple motorcycles
        $stmt = $conn->prepare("UPDATE motorcycle_inventory 
                               SET status = 'deleted', deleted_at = NOW() 
                               WHERE id IN ($placeholders)");
        
        $types = str_repeat('i', count($sanitizedIds));
        $stmt->bind_param($types, ...$sanitizedIds);
        $stmt->execute();
        
        $affectedRows = $stmt->affected_rows;
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted $affectedRows motorcycle(s)",
            'deleted_count' => $affectedRows
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Error deleting motorcycles: ' . $e->getMessage()
        ]);
    }
}

function transferMotorcycle() {
    global $conn;
    
    $required = ['motorcycle_id', 'from_branch', 'to_branch', 'transfer_date'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    $motorcycleId = intval($_POST['motorcycle_id']);
    $fromBranch = sanitizeInput($_POST['from_branch']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
    
    if ($fromBranch === $toBranch) {
        echo json_encode(['success' => false, 'message' => 'Cannot transfer to the same branch']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Update motorcycle record
        $updateStmt = $conn->prepare("UPDATE motorcycle_inventory 
                                     SET current_branch = ?, status = 'transferred'
                                     WHERE id = ?");
        $updateStmt->bind_param('si', $toBranch, $motorcycleId);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows === 0) {
            throw new Exception('Motorcycle not found or no changes made');
        }
        
        // Record the transfer
        $userId = $_SESSION['user_id'] ?? 0;
        $transferStmt = $conn->prepare("INSERT INTO inventory_transfers 
                                       (motorcycle_id, from_branch, to_branch, transfer_date, transferred_by, notes)
                                       VALUES (?, ?, ?, ?, ?, ?)");
        $transferStmt->bind_param('isssis', $motorcycleId, $fromBranch, $toBranch, $transferDate, $userId, $notes);
        $transferStmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Motorcycle transferred successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error transferring motorcycle: ' . $e->getMessage()]);
    }
}

function getTransferHistory() {
    global $conn;
    
    $motorcycleId = isset($_GET['motorcycle_id']) ? intval($_GET['motorcycle_id']) : 0;
    
    $stmt = $conn->prepare("SELECT it.*, u.username as transferred_by_name 
                           FROM inventory_transfers it
                           LEFT JOIN users u ON it.transferred_by = u.id
                           WHERE motorcycle_id = ? 
                           ORDER BY transfer_date DESC");
    $stmt->bind_param('i', $motorcycleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function getBranchInventory() {
    global $conn;
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'available';
    
    $sql = "SELECT * FROM motorcycle_inventory 
           WHERE current_branch = ?";
    
    $params = [$branch];
    $types = 's';
    
    if ($status === 'available') {
        $sql .= " AND status = 'available'";
    } elseif ($status === 'transferred') {
        $sql .= " AND status = 'transferred'";
    } else {
        $sql .= " AND status IN ('available', 'transferred')";
    }
    
    $sql .= " ORDER BY brand, model";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // For transferred motorcycles, get the latest transfer record
        if ($row['status'] === 'transferred') {
            $transferStmt = $conn->prepare("SELECT * FROM inventory_transfers 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY transfer_date DESC LIMIT 1");
            $transferStmt->bind_param('i', $row['id']);
            $transferStmt->execute();
            $transferResult = $transferStmt->get_result();
            
            if ($transferResult->num_rows > 0) {
                $row['last_transfer'] = $transferResult->fetch_assoc();
            }
        }
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

function getBranchesWithInventory() {
    global $conn;
    
    $sql = "SELECT 
                current_branch AS branch, 
                COALESCE(GROUP_CONCAT(DISTINCT CONCAT(brand, ' ', model) SEPARATOR ', '), '') AS models,
                COUNT(*) AS total_quantity,
                SUM(CASE WHEN status = 'transferred' THEN 1 ELSE 0 END) AS transferred_count
            FROM motorcycle_inventory
            WHERE status IN ('available', 'transferred')
            GROUP BY current_branch
            HAVING COUNT(*) > 0
            ORDER BY current_branch";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching branches: ' . $conn->error]);
        return;
    }
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['models'] = !empty($row['models']) ? explode(', ', $row['models']) : [];
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}
function searchInventory() {
    global $conn;
    
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : null;
    $includeTransferred = isset($_GET['include_transferred']) ? boolval($_GET['include_transferred']) : true;
    
    $sql = "SELECT id, brand, model, color, engine_number, frame_number, current_branch, status
            FROM motorcycle_inventory
            WHERE status = 'available'";
    
    // Include transferred motorcycles if requested
    if ($includeTransferred) {
        $sql = "SELECT id, brand, model, color, engine_number, frame_number, current_branch, status
                FROM motorcycle_inventory
                WHERE status IN ('available', 'transferred')";
    }
    
    $params = [];
    $types = '';
    
    if (!empty($query)) {
        $sql .= " AND (brand LIKE ? OR model LIKE ? OR engine_number LIKE ? OR frame_number LIKE ?)";
        $searchTerm = "%$query%";
        $params = array_fill(0, 4, $searchTerm);
        $types = str_repeat('s', count($params));
    }
    
    if ($branch) {
        $sql .= " AND current_branch = ?";
        $params[] = $branch;
        $types .= 's';
    }
    
    $sql .= " ORDER BY brand, model LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}


function getCurrentBranch() {
    echo json_encode([
        'success' => true,
        'branch' => $_SESSION['user_branch'] ?? 'RXS-S'
    ]);
}
?>