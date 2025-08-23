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
    case 'search_inventory_by_engine':
        searchInventoryByEngine();
        break;
    case 'transfer_multiple_motorcycles':
        transferMultipleMotorcycles();
        break;
        case 'get_incoming_transfers':
        getIncomingTransfers();
        break;
        case 'accept_transfers':
    acceptTransfers();
    break;
    case 'get_monthly_inventory':
    getMonthlyInventory();
    break;

    case 'check_invoice_number':
    checkInvoiceNumber();
    break;
  
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getInventoryDashboard() {
    global $conn;
    
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    $sql = "SELECT model, brand, COUNT(*) as total_quantity 
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

    // Check if user role is set in session
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : '';
    $sortField = 'mi.date_delivered'; // Changed to use table alias
    $sortOrder = 'DESC';

    if (!empty($sort)) {
        $parts = explode('_', $sort);
        $validFields = ['date_delivered', 'brand', 'model', 'status', 'invoice_number', 'current_branch']; // Added valid fields

        if (in_array($parts[0], $validFields)) {
            $sortField = 'mi.' . $parts[0]; // Use table alias
            $sortOrder = strtoupper($parts[1]) === 'ASC' ? 'ASC' : 'DESC';
        }
    }

    $search = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $where = "WHERE mi.status != 'deleted'";

    // If the user is not an admin, filter by their branch
    if (!$isAdmin) {
        if (!isset($_SESSION['user_branch'])) {
            echo json_encode(['success' => false, 'message' => 'User branch not set']);
            return;
        }
        $userBranch = $_SESSION['user_branch'];
        $where .= " AND mi.current_branch = '$userBranch'";
    }

    $params = [];
    $types = '';

    if (!empty($search)) {
        $where .= " AND (mi.model LIKE ? OR mi.brand LIKE ? OR mi.engine_number LIKE ? 
                  OR mi.frame_number LIKE ? OR mi.color LIKE ? OR i.invoice_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill(0, 6, $searchTerm);
        $types = str_repeat('s', count($params));
    }

    // Updated SQL to join with invoices table
    $countSql = "SELECT COUNT(*) as total 
                 FROM motorcycle_inventory mi 
                 LEFT JOIN invoices i ON mi.invoice_id = i.id 
                 $where";
    
    $countStmt = $conn->prepare($countSql);

    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }

    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    // Updated SQL to include invoice_number from invoices table
    $sql = "SELECT mi.*, i.invoice_number 
            FROM motorcycle_inventory mi 
            LEFT JOIN invoices i ON mi.invoice_id = i.id 
            $where 
            ORDER BY $sortField $sortOrder 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);

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
    
    $stmt = $conn->prepare("SELECT mi.*, i.invoice_number 
                           FROM motorcycle_inventory mi 
                           LEFT JOIN invoices i ON mi.invoice_id = i.id 
                           WHERE mi.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
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
    
    // Check if we're receiving the new format with models array
    if (isset($_POST['models']) && is_array($_POST['models'])) {
        $required = ['invoice_number', 'date_delivered', 'branch'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        $invoiceNumber = sanitizeInput($_POST['invoice_number']);
        $dateDelivered = sanitizeInput($_POST['date_delivered']);
        $branch = sanitizeInput($_POST['branch']);
        
        $conn->begin_transaction();
        $successCount = 0;
        $invoiceId = null;
        
        try {
            // Create the invoice record first
            $invoiceStmt = $conn->prepare("INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)");
            if (!$invoiceStmt) {
                throw new Exception('Error preparing invoice statement: ' . $conn->error);
            }
            
            $notes = "Motorcycles delivered to $branch branch";
            $invoiceStmt->bind_param('sss', $invoiceNumber, $dateDelivered, $notes);
            
            if (!$invoiceStmt->execute()) {
                // Check if it's a duplicate invoice error
                if ($conn->errno == 1062) { // MySQL duplicate entry error code
                    throw new Exception('DUPLICATE_INVOICE');
                }
                throw new Exception('Error creating invoice: ' . $invoiceStmt->error);
            }
            
            $invoiceId = $conn->insert_id;
            
            // Now process each model and its details
            foreach ($_POST['models'] as $modelIndex => $modelData) {
                $brand = sanitizeInput($modelData['brand']);
                $modelName = sanitizeInput($modelData['model']);
                $lcp = !empty($modelData['lcp']) ? floatval($modelData['lcp']) : null;
                
                // Check if details exist and is an array
                if (isset($modelData['details']) && is_array($modelData['details'])) {
                    foreach ($modelData['details'] as $detailIndex => $detail) {
                        $engineNumber = sanitizeInput($detail['engine_number']);
                        $frameNumber = sanitizeInput($detail['frame_number']);
                        $color = sanitizeInput($detail['color']);
                        
                        // Validate required detail fields
                        if (empty($engineNumber) || empty($frameNumber) || empty($color)) {
                            throw new Exception("Missing required detail fields for model $modelIndex, detail $detailIndex");
                        }
                        
                        // Check for duplicate engine/frame numbers
                        $duplicateCheck = $conn->prepare("SELECT id FROM motorcycle_inventory WHERE engine_number = ? OR frame_number = ?");
                        if (!$duplicateCheck) {
                            throw new Exception('Error preparing duplicate check: ' . $conn->error);
                        }
                        
                        $duplicateCheck->bind_param('ss', $engineNumber, $frameNumber);
                        if (!$duplicateCheck->execute()) {
                            throw new Exception('Error executing duplicate check: ' . $duplicateCheck->error);
                        }
                        
                        $duplicateResult = $duplicateCheck->get_result();
                        if ($duplicateResult->num_rows > 0) {
                            $duplicateRow = $duplicateResult->fetch_assoc();
                            throw new Exception("Duplicate engine number ($engineNumber) or frame number ($frameNumber) found with ID: " . $duplicateRow['id']);
                        }
                        
                        // Insert motorcycle inventory record
                        $stmt = $conn->prepare("INSERT INTO motorcycle_inventory 
                                               (date_delivered, brand, model, engine_number, frame_number, invoice_id, color, lcp, current_branch, status) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
                        
                        if (!$stmt) {
                            throw new Exception('Error preparing motorcycle insert: ' . $conn->error);
                        }
                        
                        $stmt->bind_param('sssssisds', $dateDelivered, $brand, $modelName, $engineNumber, $frameNumber, $invoiceId, $color, $lcp, $branch);
                        
                        if ($stmt->execute()) {
                            $successCount++;
                        } else {
                            throw new Exception('Error executing motorcycle insert: ' . $stmt->error);
                        }
                    }
                } else {
                    throw new Exception('No details found for model ' . $modelIndex);
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Successfully added $successCount motorcycle(s) with invoice #$invoiceNumber"]);
            
        } catch (Exception $e) {
            $conn->rollback();
            
            if ($e->getMessage() === 'DUPLICATE_INVOICE') {
                echo json_encode(['success' => false, 'message' => 'DUPLICATE_INVOICE']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding motorcycle: ' . $e->getMessage()]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data format. Expected models array.']);
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
    
    $checkStmt = $conn->prepare("SELECT id FROM motorcycle_inventory 
                                WHERE (engine_number = ? OR frame_number = ?) AND id != ?");
    $checkStmt->bind_param('ssi', $engineNumber, $frameNumber, $id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Another motorcycle with this engine or frame number already exists']);
        return;
    }
    
    // Removed invoice_number from update since it's now handled by invoice_id
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
    
    $conn->begin_transaction();
    
    try {
        $deleteTransfers = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id = ?");
        $deleteTransfers->bind_param('i', $id);
        $deleteTransfers->execute();
        
        $stmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        $conn->commit();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Motorcycle permanently deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Motorcycle not found or already deleted']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting motorcycle: ' . $e->getMessage()]);
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
        $deleteTransfers = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id IN ($placeholders)");
        $types = str_repeat('i', count($sanitizedIds));
        $deleteTransfers->bind_param($types, ...$sanitizedIds);
        $deleteTransfers->execute();
        
        $stmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$sanitizedIds);
        $stmt->execute();
        
        $affectedRows = $stmt->affected_rows;
        
        $conn->commit();
        
        if ($affectedRows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Successfully permanently deleted $affectedRows motorcycle(s)",
                'deleted_count' => $affectedRows
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No motorcycles were deleted (possibly already deleted)'
            ]);
        }
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
    
    $motorcycleId = sanitizeInput($_POST['motorcycle_id']);
    $fromBranch = sanitizeInput($_POST['from_branch']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
    $transferredBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    if ($fromBranch === $toBranch) {
        echo json_encode(['success' => false, 'message' => 'Cannot transfer to the same branch']);
        return;
    }
    
    // Validate motorcycle exists and is from the correct branch
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM motorcycle_inventory 
                                WHERE id = ? AND current_branch = ?");
    $checkStmt->bind_param('is', $motorcycleId, $fromBranch);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['count'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found or not from the specified branch']);
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
            throw new Exception('Motorcycle was not updated');
        }
        
        // Insert transfer record without transfer_status
        $transferStmt = $conn->prepare("INSERT INTO inventory_transfers 
                                      (motorcycle_id, from_branch, to_branch, transfer_date, transferred_by, notes)
                                      VALUES (?, ?, ?, ?, ?, ?)");
        $transferStmt->bind_param('isssis', $motorcycleId, $fromBranch, $toBranch, $transferDate, $transferredBy, $notes);
        $transferStmt->execute();
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully transferred motorcycle',
            'motorcycle_id' => $motorcycleId
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Error transferring motorcycle: ' . $e->getMessage()
        ]);
    }
}

// For handling both single and multiple transfers
function handleTransferRequest() {
    if (isset($_POST['motorcycle_ids']) && strpos($_POST['motorcycle_ids'], ',') !== false) {
        transferMultipleMotorcycles();
    } else {
        // If single motorcycle, use motorcycle_id parameter
        if (isset($_POST['motorcycle_ids'])) {
            $_POST['motorcycle_id'] = $_POST['motorcycle_ids'];
        }
        transferMotorcycle();
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
    if (empty($branch)) {
        echo json_encode(['success' => false, 'message' => 'Branch parameter is required']);
        return;
    }

    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'available';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(max(1, intval($_GET['per_page'])), 100) : 10;
    $offset = ($page - 1) * $perPage;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

    $sql = "SELECT SQL_CALC_FOUND_ROWS mi.*, i.invoice_number 
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.current_branch = ?";
    
    $params = [$branch];
    $types = 's';
    
    if ($status === 'available') {
        $sql .= " AND mi.status = 'available'";
    } elseif ($status === 'transferred') {
        $sql .= " AND mi.status = 'transferred'";
    } else {
        $sql .= " AND mi.status IN ('available', 'transferred')";
    }

    if (!empty($search)) {
        $sql .= " AND (mi.model LIKE ? OR mi.brand LIKE ? OR mi.engine_number LIKE ? 
                  OR mi.frame_number LIKE ? OR mi.color LIKE ? OR i.invoice_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, array_fill(0, 6, $searchTerm));
        $types .= str_repeat('s', 6);
    }

    $sortField = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'brand';
    $sortOrder = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
    
    $validSortFields = ['brand', 'model', 'color', 'engine_number', 'frame_number', 'date_delivered', 'status', 'invoice_number'];
    if (!in_array($sortField, $validSortFields)) {
        $sortField = 'brand';
    }
    
    // Use table alias for sort field
    if ($sortField === 'invoice_number') {
        $sortField = 'i.invoice_number';
    } else {
        $sortField = 'mi.' . $sortField;
    }
    
    $sql .= " ORDER BY $sortField $sortOrder LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        return;
    }

    if ($types !== 's') {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param($types, $branch);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $totalResult = $conn->query("SELECT FOUND_ROWS()");
    $totalRows = $totalResult->fetch_row()[0];
    $totalPages = ceil($totalRows / $perPage);

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $rowData = [
            'id' => $row['id'],
            'date_delivered' => $row['date_delivered'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'engine_number' => $row['engine_number'],
            'frame_number' => $row['frame_number'],
            'color' => $row['color'],
            'current_branch' => $row['current_branch'],
            'status' => $row['status'],
            'invoice_number' => $row['invoice_number']
        ];

        if ($row['status'] === 'transferred') {
            $transferStmt = $conn->prepare("SELECT * FROM inventory_transfers 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY transfer_date DESC LIMIT 1");
            $transferStmt->bind_param('i', $row['id']);
            $transferStmt->execute();
            $transferResult = $transferStmt->get_result();
            
            if ($transferResult->num_rows > 0) {
                $rowData['last_transfer'] = $transferResult->fetch_assoc();
            }
        }
        $data[] = $rowData;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_items' => $totalRows,
            'total_pages' => $totalPages
        ]
    ]);
}

function getBranchesWithInventory() {
    global $conn;
    
    $sql = "SELECT 
                mi.current_branch AS branch, 
                COALESCE(GROUP_CONCAT(DISTINCT CONCAT(mi.brand, ' ', mi.model) SEPARATOR ', '), '') AS models,
                COUNT(*) AS total_quantity,
                SUM(CASE WHEN mi.status = 'transferred' THEN 1 ELSE 0 END) AS transferred_count
            FROM motorcycle_inventory mi
            WHERE mi.status IN ('available', 'transferred')
            GROUP BY mi.current_branch
            HAVING COUNT(*) > 0
            ORDER BY mi.current_branch";
    
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
    $field = isset($_GET['field']) ? sanitizeInput($_GET['field']) : 'all';
    $includeLcp = isset($_GET['include_lcp']) ? true : false;
    
    $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
                   mi.lcp, mi.current_branch, mi.status, i.invoice_number
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.status = 'available'";
    
    $params = [];
    $types = '';
    
    if (!empty($query)) {
        if ($field === 'engine_number') {
            $sql .= " AND mi.engine_number LIKE ?";
            $searchTerm = "%$query%";
            $params[] = $searchTerm;
            $types = 's';
        } else {
            $sql .= " AND (mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? 
                      OR mi.frame_number LIKE ? OR i.invoice_number LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_fill(0, 5, $searchTerm);
            $types = str_repeat('s', count($params));
        }
    }
    
    $sql .= " ORDER BY mi.brand, mi.model LIMIT 10";
    
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

function searchInventoryByEngine() {
    global $conn;
    
    // Get the logged-in user's branch from session
    if (!isset($_SESSION['user_branch'])) {
        echo json_encode(['success' => false, 'message' => 'User branch not set']);
        return;
    }
    
    $userBranch = $_SESSION['user_branch'];
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $field = isset($_GET['field']) ? sanitizeInput($_GET['field']) : 'all';
    $includeLcp = isset($_GET['include_lcp']) ? true : false;
    $fuzzySearch = isset($_GET['fuzzy_search']) ? true : false;
    
    $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
                   mi.lcp, mi.current_branch, mi.status, i.invoice_number
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.status = 'available' AND mi.current_branch = '$userBranch'";
    
    $params = [];
    $types = '';
    
    if (!empty($query)) {
        if ($field === 'engine_number') {
            if ($fuzzySearch) {
                $sql .= " AND (mi.engine_number LIKE ? OR mi.engine_number LIKE ? OR mi.engine_number LIKE ?)";
                $searchTerm1 = "%$query%";
                $searchTerm2 = "$query%";
                $searchTerm3 = "%$query";
                $params = [$searchTerm1, $searchTerm2, $searchTerm3];
                $types = str_repeat('s', count($params));
            } else {
                $sql .= " AND mi.engine_number LIKE ?";
                $searchTerm = "%$query%";
                $params[] = $searchTerm;
                $types = 's';
            }
        } else {
            $sql .= " AND (mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? 
                      OR mi.frame_number LIKE ? OR i.invoice_number LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_fill(0, 5, $searchTerm);
            $types = str_repeat('s', count($params));
        }
    }
    
    $sql .= " ORDER BY mi.brand, mi.model LIMIT 20";
    
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

function transferMultipleMotorcycles() {
    global $conn;
    
    $required = ['motorcycle_ids', 'from_branch', 'to_branch', 'transfer_date'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    $motorcycleIds = explode(',', sanitizeInput($_POST['motorcycle_ids']));
    $fromBranch = sanitizeInput($_POST['from_branch']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
    $transferredBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    if ($fromBranch === $toBranch) {
        echo json_encode(['success' => false, 'message' => 'Cannot transfer to the same branch']);
        return;
    }
    
    // Validate all motorcycles exist and are from the same branch
    $placeholders = implode(',', array_fill(0, count($motorcycleIds), '?'));
    $types = str_repeat('i', count($motorcycleIds));
    
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM motorcycle_inventory 
                               WHERE id IN ($placeholders) AND current_branch = ?");
    $checkStmt->bind_param($types.'s', ...array_merge($motorcycleIds, [$fromBranch]));
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['count'] != count($motorcycleIds)) {
        echo json_encode(['success' => false, 'message' => 'Some motorcycles not found or not from the specified branch']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // DO NOT update current_branch here - keep it at the original branch
        // Only update status to 'transferred' to indicate it's in transit
        $updateStmt = $conn->prepare("UPDATE motorcycle_inventory 
                                    SET status = 'transferred' 
                                    WHERE id IN ($placeholders)");
        
        $updateStmt->bind_param($types, ...$motorcycleIds);
        $updateStmt->execute();
        
        if ($updateStmt->affected_rows === 0) {
            throw new Exception('No motorcycles were updated');
        }
        
        // Record transfers with 'pending' status
        $transferStmt = $conn->prepare("INSERT INTO inventory_transfers 
                                      (motorcycle_id, from_branch, to_branch, transfer_date, transferred_by, notes, transfer_status)
                                      VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        
        foreach ($motorcycleIds as $id) {
            $transferStmt->bind_param('isssis', $id, $fromBranch, $toBranch, $transferDate, $transferredBy, $notes);
            $transferStmt->execute();
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully initiated transfer for ' . count($motorcycleIds) . ' motorcycle(s)',
            'transferred_count' => count($motorcycleIds),
            'to_branch' => $toBranch
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Error transferring motorcycles: ' . $e->getMessage(),
            'error_details' => $conn->error
        ]);
    }
}

function getIncomingTransfers() {
    global $conn;
    
    $currentBranch = isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] : 
                   (isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '');

    if (empty($currentBranch)) {
        echo json_encode(['success' => false, 'message' => 'Branch parameter is required']);
        return;
    }

    $sql = "SELECT 
                t.id as transfer_id,
                m.id as motorcycle_id,
                m.brand, 
                m.model, 
                m.engine_number, 
                m.frame_number, 
                m.color,
                t.transfer_date,
                t.from_branch,
                t.to_branch,
                t.notes,
                t.transfer_status as transfer_status,
                u.username as transferred_by
            FROM inventory_transfers t
            JOIN motorcycle_inventory m ON t.motorcycle_id = m.id
            LEFT JOIN users u ON t.transferred_by = u.id
            WHERE t.to_branch = ?
            AND t.transfer_status = 'pending'
            ORDER BY t.transfer_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transfers = [];
    while ($row = $result->fetch_assoc()) {
        $transfers[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $transfers]);
}

function acceptTransfers() {
    global $conn;
    
    $transferIds = isset($_POST['transfer_ids']) ? explode(',', sanitizeInput($_POST['transfer_ids'])) : [];
    $currentBranch = isset($_POST['current_branch']) ? sanitizeInput($_POST['current_branch']) : '';
    
    if (empty($transferIds)) {
        echo json_encode(['success' => false, 'message' => 'No transfer IDs provided']);
        return;
    }
    
    if (empty($currentBranch)) {
        echo json_encode(['success' => false, 'message' => 'Current branch parameter is required']);
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($transferIds), '?'));
    $currentDate = date('Y-m-d');
    
    $conn->begin_transaction();
    
    try {
        // Update transfer status to completed and set date_received
        $updateTransfers = $conn->prepare("UPDATE inventory_transfers 
                                         SET transfer_status = 'completed', date_received = ?
                                         WHERE id IN ($placeholders)");
        
        $params = array_merge([$currentDate], $transferIds);
        $types = 's' . str_repeat('i', count($transferIds));
        
        $updateTransfers->bind_param($types, ...$params);
        $updateTransfers->execute();
        
        // Get motorcycle IDs and destination branches from these transfers
        $getMotorcycles = $conn->prepare("SELECT motorcycle_id, to_branch FROM inventory_transfers 
                                        WHERE id IN ($placeholders)");
        $getMotorcycles->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        $getMotorcycles->execute();
        $result = $getMotorcycles->get_result();
        
        $motorcycleUpdates = [];
        while ($row = $result->fetch_assoc()) {
            $motorcycleUpdates[] = $row;
        }
        
        if (!empty($motorcycleUpdates)) {
            foreach ($motorcycleUpdates as $update) {
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available', date_received = ?
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('ssi', $update['to_branch'], $currentDate, $update['motorcycle_id']);
                $updateMotorcycle->execute();
            }
        }
        
        // Get accepted details for receipt including invoice numbers
        $acceptedDetails = [];
        foreach ($motorcycleUpdates as $update) {
            $detailStmt = $conn->prepare("SELECT mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.color, i.invoice_number
                                         FROM motorcycle_inventory mi
                                         LEFT JOIN invoices i ON mi.invoice_id = i.id
                                         WHERE mi.id = ?");
            $detailStmt->bind_param('i', $update['motorcycle_id']);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();
            
            if ($detailRow = $detailResult->fetch_assoc()) {
                $acceptedDetails[] = $detailRow;
            }
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Successfully accepted ' . count($transferIds) . ' transfer(s)',
            'date_received' => $currentDate,
            'accepted_details' => $acceptedDetails
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error accepting transfers: ' . $e->getMessage()
        ]);
    }
}

function getMonthlyInventory() {
    global $conn;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
    
    if (empty($month)) {
        echo json_encode(['success' => false, 'message' => 'Month parameter is required']);
        return;
    }
    
    $yearMonth = explode('-', $month);
    $year = $yearMonth[0];
    $monthNum = $yearMonth[1];
    
    
    $monthStart = "$year-$monthNum-01";
    $monthEnd = date("Y-m-t", strtotime($monthStart));
    
    
    $beginningInventory = getBeginningInventory($monthStart, $branch);
    
    
    $inventoryMovements = getInventoryMovements($monthStart, $monthEnd, $branch);
    
    
    $processedData = processInventoryData($beginningInventory, $inventoryMovements, $monthStart, $monthEnd, $branch);
    
    echo json_encode([
        'success' => true, 
        'data' => array_values($processedData),
        'month' => $month,
        'branch' => $branch,
        'summary' => calculateSummary($processedData)
    ]);
}

function getBeginningInventory($monthStart, $branchFilter) {
    global $conn;
    
   $sql = "SELECT 
            brand, model, color, current_branch,
            engine_number, frame_number, lcp,
            1 as beginning_balance
        FROM motorcycle_inventory 
        WHERE date_delivered < ? 
        AND status != 'deleted'";

    
    $params = [$monthStart];
    $types = 's';
    
    if (!empty($branchFilter) && $branchFilter !== 'all') {
        $sql .= " AND current_branch = ?";
        $params[] = $branchFilter;
        $types .= 's';
    }
    
    $sql .= " GROUP BY brand, model, color, current_branch";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $beginningInventory = [];
    while ($row = $result->fetch_assoc()) {
        $key = $row['brand'] . '|' . $row['model'] . '|' . $row['color'] . '|' . $row['current_branch'];
        $beginningInventory[$key] = $row;
    }
    
    return $beginningInventory;
}

function getInventoryMovements($monthStart, $monthEnd, $branchFilter) {
    global $conn;
    
  
   $sqlDeliveries = "SELECT 
                    brand, model, color, current_branch,
                    engine_number, frame_number, lcp,
                    1 as in_qty
                FROM motorcycle_inventory 
                WHERE date_delivered BETWEEN ? AND ?
                AND status != 'deleted'";

    
    $params = [$monthStart, $monthEnd];
    $types = 'ss';
    
    if (!empty($branchFilter) && $branchFilter !== 'all') {
        $sqlDeliveries .= " AND current_branch = ?";
        $params[] = $branchFilter;
        $types .= 's';
    }
    
    $sqlDeliveries .= " GROUP BY brand, model, color, current_branch";
    
    $stmt = $conn->prepare($sqlDeliveries);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $deliveries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    
    $sqlTransfersIn = "SELECT 
                        mi.brand, mi.model, mi.color, 
                        it.to_branch as current_branch,
                        COUNT(*) as in_qty
                    FROM inventory_transfers it
                    JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
                    WHERE it.transfer_date BETWEEN ? AND ?
                    AND it.transfer_status = 'completed'";
    
    $paramsIn = [$monthStart, $monthEnd];
    $typesIn = 'ss';
    
    if (!empty($branchFilter) && $branchFilter !== 'all') {
        $sqlTransfersIn .= " AND it.to_branch = ?";
        $paramsIn[] = $branchFilter;
        $typesIn .= 's';
    }
    
    $sqlTransfersIn .= " GROUP BY mi.brand, mi.model, mi.color, it.to_branch";
    
    $stmt = $conn->prepare($sqlTransfersIn);
    $stmt->bind_param($typesIn, ...$paramsIn);
    $stmt->execute();
    $transfersIn = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    
    $sqlTransfersOut = "SELECT 
                        mi.brand, mi.model, mi.color, 
                        it.from_branch as current_branch,
                        COUNT(*) as out_qty
                    FROM inventory_transfers it
                    JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
                    WHERE it.transfer_date BETWEEN ? AND ?
                    AND it.transfer_status = 'completed'";
    
    $paramsOut = [$monthStart, $monthEnd];
    $typesOut = 'ss';
    
    if (!empty($branchFilter) && $branchFilter !== 'all') {
        $sqlTransfersOut .= " AND it.from_branch = ?";
        $paramsOut[] = $branchFilter;
        $typesOut .= 's';
    }
    
    $sqlTransfersOut .= " GROUP BY mi.brand, mi.model, mi.color, it.from_branch";
    
    $stmt = $conn->prepare($sqlTransfersOut);
    $stmt->bind_param($typesOut, ...$paramsOut);
    $stmt->execute();
    $transfersOut = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    
    $sqlReturns = "SELECT 
                    mi.brand, mi.model, mi.color, 
                    it.from_branch as current_branch,
                    COUNT(*) as out_qty
                FROM inventory_transfers it
                JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
                WHERE it.transfer_date BETWEEN ? AND ?
                AND it.to_branch = 'HEADOFFICE'
                AND it.transfer_status = 'completed'";
    
    $paramsReturns = [$monthStart, $monthEnd];
    $typesReturns = 'ss';
    
    if (!empty($branchFilter) && $branchFilter !== 'all') {
        $sqlReturns .= " AND it.from_branch = ?";
        $paramsReturns[] = $branchFilter;
        $typesReturns .= 's';
    }
    
    $sqlReturns .= " GROUP BY mi.brand, mi.model, mi.color, it.from_branch";
    
    $stmt = $conn->prepare($sqlReturns);
    $stmt->bind_param($typesReturns, ...$paramsReturns);
    $stmt->execute();
    $returns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'deliveries' => $deliveries,
        'transfers_in' => $transfersIn,
        'transfers_out' => $transfersOut,
        'returns' => $returns
    ];
}

function processInventoryData($beginningInventory, $inventoryMovements, $monthStart, $monthEnd, $branchFilter) {
    $processed = [];

    
    foreach ($beginningInventory as $key => $item) {
        $processed[$key] = [
            'brand' => $item['brand'],
            'model' => $item['model'],
            'color' => $item['color'],
            'current_branch' => $item['current_branch'],
            'engine_number' => isset($item['engine_number']) ? $item['engine_number'] : null,
            'frame_number' => isset($item['frame_number']) ? $item['frame_number'] : null,
            'lcp' => isset($item['lcp']) ? $item['lcp'] : null,
            'beginning_balance' => (int)$item['beginning_balance'],
            'in_qty' => 0,
            'out_qty' => 0,
            'ending_balance' => (int)$item['beginning_balance']
        ];
    }

    
    foreach ($inventoryMovements['deliveries'] as $delivery) {
        $key = $delivery['brand'] . '|' . $delivery['model'] . '|' . $delivery['color'] . '|' . $delivery['current_branch'];

        if (!isset($processed[$key])) {
            $processed[$key] = [
                'brand' => $delivery['brand'],
                'model' => $delivery['model'],
                'color' => $delivery['color'],
                'current_branch' => $delivery['current_branch'],
                'engine_number' => isset($delivery['engine_number']) ? $delivery['engine_number'] : null,
                'frame_number' => isset($delivery['frame_number']) ? $delivery['frame_number'] : null,
                'lcp' => isset($delivery['lcp']) ? $delivery['lcp'] : null,
                'beginning_balance' => 0,
                'in_qty' => 0,
                'out_qty' => 0,
                'ending_balance' => 0
            ];
        }

        $processed[$key]['in_qty'] += (int)$delivery['in_qty'];
        $processed[$key]['ending_balance'] += (int)$delivery['in_qty'];
    }

    // Process transfers IN (from other branches)
    foreach ($inventoryMovements['transfers_in'] as $transfer) {
        $key = $transfer['brand'] . '|' . $transfer['model'] . '|' . $transfer['color'] . '|' . $transfer['current_branch'];

        if (!isset($processed[$key])) {
            $processed[$key] = [
                'brand' => $transfer['brand'],
                'model' => $transfer['model'],
                'color' => $transfer['color'],
                'current_branch' => $transfer['current_branch'],
                'engine_number' => isset($transfer['engine_number']) ? $transfer['engine_number'] : null,
                'frame_number' => isset($transfer['frame_number']) ? $transfer['frame_number'] : null,
                'lcp' => isset($transfer['lcp']) ? $transfer['lcp'] : null,
                'beginning_balance' => 0,
                'in_qty' => 0,
                'out_qty' => 0,
                'ending_balance' => 0
            ];
        }

        $processed[$key]['in_qty'] += (int)$transfer['in_qty'];
        $processed[$key]['ending_balance'] += (int)$transfer['in_qty'];
    }

    // Process transfers OUT (to other branches)
    foreach ($inventoryMovements['transfers_out'] as $transfer) {
        $key = $transfer['brand'] . '|' . $transfer['model'] . '|' . $transfer['color'] . '|' . $transfer['current_branch'];

        if (!isset($processed[$key])) {
            $processed[$key] = [
                'brand' => $transfer['brand'],
                'model' => $transfer['model'],
                'color' => $transfer['color'],
                'current_branch' => $transfer['current_branch'],
                'engine_number' => isset($transfer['engine_number']) ? $transfer['engine_number'] : null,
                'frame_number' => isset($transfer['frame_number']) ? $transfer['frame_number'] : null,
                'lcp' => isset($transfer['lcp']) ? $transfer['lcp'] : null,
                'beginning_balance' => 0,
                'in_qty' => 0,
                'out_qty' => 0,
                'ending_balance' => 0
            ];
        }

        $processed[$key]['out_qty'] += (int)$transfer['out_qty'];
        $processed[$key]['ending_balance'] -= (int)$transfer['out_qty'];
    }

    // Process returns to head office (special OUT category)
    foreach ($inventoryMovements['returns'] as $return) {
        $key = $return['brand'] . '|' . $return['model'] . '|' . $return['color'] . '|' . $return['current_branch'];

        if (!isset($processed[$key])) {
            $processed[$key] = [
                'brand' => $return['brand'],
                'model' => $return['model'],
                'color' => $return['color'],
                'current_branch' => $return['current_branch'],
                'engine_number' => isset($return['engine_number']) ? $return['engine_number'] : null,
                'frame_number' => isset($return['frame_number']) ? $return['frame_number'] : null,
                'lcp' => isset($return['lcp']) ? $return['lcp'] : null,
                'beginning_balance' => 0,
                'in_qty' => 0,
                'out_qty' => 0,
                'ending_balance' => 0
            ];
        }

        $processed[$key]['out_qty'] += (int)$return['out_qty'];
        $processed[$key]['ending_balance'] -= (int)$return['out_qty'];

        // Add a special field to track returns to head office
        if (!isset($processed[$key]['returns_to_head_office'])) {
            $processed[$key]['returns_to_head_office'] = 0;
        }
        $processed[$key]['returns_to_head_office'] += (int)$return['out_qty'];
    }

    return $processed;
}

function calculateSummary($processedData) {
    $summary = [
        'total_beginning' => 0,
        'total_in' => 0,
        'total_out' => 0,
        'total_ending' => 0,
        'total_returns' => 0
    ];
    
    foreach ($processedData as $item) {
        $summary['total_beginning'] += $item['beginning_balance'];
        $summary['total_in'] += $item['in_qty'];
        $summary['total_out'] += $item['out_qty'];
        $summary['total_ending'] += $item['ending_balance'];
        
        if (isset($item['returns_to_head_office'])) {
            $summary['total_returns'] += $item['returns_to_head_office'];
        }
    }
    
    return $summary;
}
function checkInvoiceNumber() {
    global $conn;
    
    if (empty($_POST['invoice_number'])) {
        echo json_encode(['exists' => false]);
        return;
    }
    
    $invoiceNumber = sanitizeInput($_POST['invoice_number']);
    
    $stmt = $conn->prepare("SELECT id FROM invoices WHERE invoice_number = ?");
    $stmt->bind_param('s', $invoiceNumber);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    
    echo json_encode(['exists' => $exists]);
}
?>