<?php
header('Content-Type: application/json');

require_once '../api/db_config.php'; 

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin'];
$canViewAllBranches = in_array($userRole, $adminRoles);


function sanitizeInput($data) { /* ... same as before ... */ }
$action = $_REQUEST['action'] ?? '';

// Main router for API actions
switch ($action) {
    // READ ACTIONS
    case 'get_dashboard_stats': getDashboardStats(); break;
    case 'get_inventory_list': getInventoryList(); break;
    case 'get_sales_list': getSalesList(); break;
    case 'get_payments_list': getPaymentsList(); break;
    case 'get_transfers_list': getTransfersList(); break;
    case 'get_all_branches': getAllBranches(); break;
    case 'search_inventory_parts': searchInventoryParts(); break;
    case 'generate_report': generateReport(); break;
    // CUD ACTIONS
    case 'add_parts_in': addPartsIn(); break;
    case 'sell_parts_out': sellPartsOut(); break;
    case 'record_payment': recordPayment(); break;
    case 'transfer_multiple_parts': transferMultipleParts(); break;
    case 'edit_parts': editPart(); break;
    case 'delete_parts': deletePart(); break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

// --- NEW/UPDATED ENDPOINTS ---

function getAllBranches() {
    global $conn;
    $result = $conn->query("SELECT DISTINCT branch FROM users WHERE branch IS NOT NULL ORDER BY branch ASC");
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row['branch'];
    }
    echo json_encode(['success' => true, 'data' => $branches]);
}

function searchInventoryParts() {
    global $conn;
    $term = sanitizeInput($_GET['term'] ?? '');
    $branch = sanitizeInput($_GET['branch'] ?? '');
    $searchTerm = "%{$term}%";

    $stmt = $conn->prepare("SELECT part_no, description, current_stock, cost FROM spareparts_inventory WHERE current_branch = ? AND (part_no LIKE ? OR description LIKE ?) LIMIT 10");
    $stmt->bind_param('sss', $branch, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function generateReport() {
    global $conn, $canViewAllBranches, $currentBranch;
    // ... [Copy the full generateReport function from the previous response here] ...
}


function transferMultipleParts() {
    global $conn;
    $required = ['transfer_date', 'from_branch', 'to_branch', 'items'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field."]);
            return;
        }
    }

    $transferDate = sanitizeInput($_POST['transfer_date']);
    $fromBranch = sanitizeInput($_POST['from_branch']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $items = json_decode($_POST['items'], true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($items) || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Invalid items data for transfer.']);
        return;
    }

    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            $partNo = sanitizeInput($item['part_no']);
            $quantity = (int)$item['quantity'];
            $cost = (float)$item['cost'];
            $description = sanitizeInput($item['description']);
            $price = (float)($item['price'] ?? $cost * 1.25);

            // 1. Check & decrease stock at from_branch
            $fromStmt = $conn->prepare("SELECT current_stock FROM spareparts_inventory WHERE part_no = ? AND current_branch = ? FOR UPDATE");
            $fromStmt->bind_param('ss', $partNo, $fromBranch);
            $fromStmt->execute();
            $fromResult = $fromStmt->get_result();
            if ($fromResult->num_rows === 0) throw new Exception("Part '$partNo' not found at source branch.");
            $fromPart = $fromResult->fetch_assoc();
            if ($fromPart['current_stock'] < $quantity) throw new Exception("Insufficient stock for '$partNo' at source branch.");
            
            $updateFromStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
            $updateFromStmt->bind_param('iss', $quantity, $partNo, $fromBranch);
            if (!$updateFromStmt->execute()) throw new Exception("Failed to update source stock for '$partNo'.");

            // 2. Increase or create stock at to_branch
            $toStmt = $conn->prepare("SELECT id FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
            $toStmt->bind_param('ss', $partNo, $toBranch);
            $toStmt->execute();
            if ($toStmt->get_result()->num_rows > 0) {
                $updateToStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
                $updateToStmt->bind_param('iss', $quantity, $partNo, $toBranch);
                if (!$updateToStmt->execute()) throw new Exception("Failed to update destination stock for '$partNo'.");
            } else {
                $insertStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param('ssidds', $partNo, $description, $quantity, $cost, $price, $toBranch);
                if (!$insertStmt->execute()) throw new Exception("Failed to insert part '$partNo' at destination.");
            }

            // 3. Log transactions
            $totalCost = $quantity * $cost;
            $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_cost, total_amount, from_location, to_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $typeOut = 'TRANSFER_OUT';
            $logStmt->bind_param('ssiddsss', $partNo, $transferDate, $typeOut, $quantity, $cost, $totalCost, $fromBranch, $toBranch);
            if (!$logStmt->execute()) throw new Exception("Failed to log transfer out for '$partNo'.");
            
            $typeIn = 'TRANSFER_IN';
            $logStmt->bind_param('ssiddsss', $partNo, $transferDate, $typeIn, $quantity, $cost, $totalCost, $fromBranch, $toBranch);
            if (!$logStmt->execute()) throw new Exception("Failed to log transfer in for '$partNo'.");
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Parts transferred successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()]);
    }
}


// --- Data Fetching Functions ---

function getDashboardStats() {
    global $conn, $currentBranch;
    $stats = [
        'total_value' => 0,
        'monthly_sales' => 0,
        'outstanding_balance' => 0,
        'low_stock_items' => 0,
        'sales_trend' => []
    ];

    // Total Inventory Value
    $stmt = $conn->prepare("SELECT SUM(current_stock * cost) as total FROM spareparts_inventory WHERE current_branch = ?");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['total_value'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Sales This Month
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM spareparts_transactions WHERE type = 'OUT' AND from_location = ? AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['monthly_sales'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Outstanding Balance
    $stmt = $conn->prepare("SELECT SUM(balance) as total FROM spareparts_aging WHERE branch = ? AND status = 'Active'");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['outstanding_balance'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Low Stock Items
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM spareparts_inventory WHERE current_branch = ? AND current_stock < min_stock");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['low_stock_items'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

    // Sales Trend (Last 6 Months)
    $query = "SELECT DATE_FORMAT(transaction_date, '%b %Y') as month, SUM(total_amount) as total_sales 
              FROM spareparts_transactions 
              WHERE type = 'OUT' AND from_location = ? AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
              GROUP BY month ORDER BY transaction_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $stats['sales_trend'][] = $row;
    }

    echo json_encode(['success' => true, 'data' => $stats]);
}

function getInventoryList() {
    global $conn, $currentBranch;
    $stmt = $conn->prepare("SELECT id, part_no, description, current_stock, cost, price, min_stock FROM spareparts_inventory WHERE current_branch = ? ORDER BY part_no");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSalesList() {
    global $conn, $currentBranch;
    $stmt = $conn->prepare("SELECT transaction_date, part_no, quantity, total_amount, customer_name, or_number, transaction_type FROM spareparts_transactions WHERE type = 'OUT' AND from_location = ? ORDER BY transaction_date DESC");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getPaymentsList() {
    global $conn, $currentBranch;
    $stmt = $conn->prepare("SELECT transaction_date, customer_name, total_amount as amount, or_number FROM spareparts_transactions WHERE type = 'PAYMENT' AND from_location = ? ORDER BY transaction_date DESC");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getTransfersList() {
    global $conn, $currentBranch;
    $stmt = $conn->prepare("SELECT transaction_date, part_no, quantity, type, from_location, to_location FROM spareparts_transactions WHERE type LIKE 'TRANSFER_%' AND (from_location = ? OR to_location = ?) ORDER BY transaction_date DESC");
    $stmt->bind_param('ss', $currentBranch, $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}


// --- Business Logic Functions ---

function addPartsIn() {
    global $conn;
    $required = ['part_no', 'quantity', 'cost', 'date', 'invoice_no', 'branch'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field."]);
            return;
        }
    }

    $partNo = sanitizeInput($_POST['part_no']);
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : 'N/A';
    $quantity = (int)$_POST['quantity'];
    $cost = (float)$_POST['cost'];
    $date = sanitizeInput($_POST['date']);
    $invoiceNo = sanitizeInput($_POST['invoice_no']);
    $branch = sanitizeInput($_POST['branch']);

    $conn->begin_transaction();

    try {
        $checkStmt = $conn->prepare("SELECT id, current_stock, cost FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $checkStmt->bind_param('ss', $partNo, $branch);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $existingPart = $result->fetch_assoc();
            $newStock = $existingPart['current_stock'] + $quantity;
            $newCost = ($newStock > 0) ? (($existingPart['current_stock'] * $existingPart['cost']) + ($quantity * $cost)) / $newStock : $cost;

            $updateStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = ?, cost = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param('idi', $newStock, $newCost, $existingPart['id']);
            if (!$updateStmt->execute()) throw new Exception('Failed to update existing part: ' . $updateStmt->error);
        } else {
            $price = $cost * 1.25; // Example: Set price with a 25% markup
            $insertStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param('ssidds', $partNo, $description, $quantity, $cost, $price, $branch);
            if (!$insertStmt->execute()) throw new Exception('Failed to insert new part: ' . $insertStmt->error);
        }

        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_cost, total_amount, or_number, to_location) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?)");
        $totalAmount = $quantity * $cost;
        $logStmt->bind_param('ssiddss', $partNo, $date, $quantity, $cost, $totalAmount, $invoiceNo, $branch);
        if (!$logStmt->execute()) throw new Exception('Failed to log transaction: ' . $logStmt->error);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Parts received successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error receiving parts: ' . $e->getMessage()]);
    }
}

function sellPartsOut() {
    global $conn;
    $required = ['part_no', 'quantity', 'amount', 'date', 'transaction_type', 'or_number', 'customer_name', 'branch'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field."]);
            return;
        }
    }

    $partNo = sanitizeInput($_POST['part_no']);
    $quantity = (int)$_POST['quantity'];
    $amount = (float)$_POST['amount'];
    $date = sanitizeInput($_POST['date']);
    $transactionType = sanitizeInput($_POST['transaction_type']);
    $orNumber = sanitizeInput($_POST['or_number']);
    $customerName = sanitizeInput($_POST['customer_name']);
    $branch = sanitizeInput($_POST['branch']);

    $conn->begin_transaction();

    try {
        $stockStmt = $conn->prepare("SELECT current_stock FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $stockStmt->bind_param('ss', $partNo, $branch);
        $stockStmt->execute();
        $result = $stockStmt->get_result();

        if ($result->num_rows === 0) throw new Exception("Part number '$partNo' not found.");
        $part = $result->fetch_assoc();
        if ($part['current_stock'] < $quantity) throw new Exception("Insufficient stock for part '$partNo'. Available: {$part['current_stock']}.");

        $updateStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
        $updateStmt->bind_param('iss', $quantity, $partNo, $branch);
        if (!$updateStmt->execute()) throw new Exception('Failed to update inventory: ' . $updateStmt->error);

        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_price, total_amount, customer_name, or_number, transaction_type, from_location) VALUES (?, ?, 'OUT', ?, ?, ?, ?, ?, ?, ?)");
        $unitPrice = $quantity > 0 ? $amount / $quantity : 0;
        $logStmt->bind_param('ssiddssss', $partNo, $date, $quantity, $unitPrice, $amount, $customerName, $orNumber, $transactionType, $branch);
        if (!$logStmt->execute()) throw new Exception('Failed to log transaction: ' . $logStmt->error);

        if ($transactionType === 'installment') {
            $agingStmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, customer_name, sale_date, total_amount, balance, branch) VALUES (?, ?, ?, ?, ?, ?)");
            $agingStmt->bind_param('sssdds', $orNumber, $customerName, $date, $amount, $amount, $branch);
            if (!$agingStmt->execute()) throw new Exception('Failed to create aging record: ' . $agingStmt->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale recorded successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error recording sale: ' . $e->getMessage()]);
    }
}

function recordPayment() {
    global $conn;
    $required = ['payment_date', 'customer_name', 'amount', 'or_number', 'branch'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field."]);
            return;
        }
    }

    $paymentDate = sanitizeInput($_POST['payment_date']);
    $customerName = sanitizeInput($_POST['customer_name']);
    $amount = (float)$_POST['amount'];
    $orNumber = sanitizeInput($_POST['or_number']);
    $branch = sanitizeInput($_POST['branch']);

    $conn->begin_transaction();

    try {
        $agingStmt = $conn->prepare("SELECT id, balance FROM spareparts_aging WHERE or_number = ? AND branch = ? AND status = 'Active'");
        $agingStmt->bind_param('ss', $orNumber, $branch);
        $agingStmt->execute();
        $result = $agingStmt->get_result();

        if ($result->num_rows === 0) throw new Exception("No active installment sale found for OR #$orNumber.");
        
        $agingRecord = $result->fetch_assoc();
        $newBalance = $agingRecord['balance'] - $amount;

        if ($newBalance < -0.01) throw new Exception("Payment exceeds balance. Current balance: " . number_format($agingRecord['balance'], 2));

        $status = ($newBalance <= 0) ? 'Paid' : 'Active';
        $updateStmt = $conn->prepare("UPDATE spareparts_aging SET balance = ?, status = ? WHERE id = ?");
        $updateStmt->bind_param('dsi', $newBalance, $status, $agingRecord['id']);
        if (!$updateStmt->execute()) throw new Exception('Failed to update aging record: ' . $updateStmt->error);
        
        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, total_amount, customer_name, or_number, from_location) VALUES ('PAYMENT', ?, 'PAYMENT', 0, ?, ?, ?, ?)");
        $logStmt->bind_param('sdsss', $paymentDate, $amount, $customerName, $orNumber, $branch);
        if (!$logStmt->execute()) throw new Exception('Failed to log payment transaction: ' . $logStmt->error);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment recorded. New balance: ' . number_format($newBalance, 2)]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()]);
    }
}

function transferParts() {
    global $conn;
    $required = ['part_no', 'quantity', 'cost', 'transfer_date', 'from_branch', 'to_branch'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field."]);
            return;
        }
    }

    $partNo = sanitizeInput($_POST['part_no']);
    $quantity = (int)$_POST['quantity'];
    $cost = (float)$_POST['cost'];
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $fromBranch = sanitizeInput($_POST['from_branch']);
    $toBranch = sanitizeInput($_POST['to_branch']);

    if ($fromBranch === $toBranch) {
        echo json_encode(['success' => false, 'message' => 'Cannot transfer to the same branch.']);
        return;
    }

    $conn->begin_transaction();

    try {
        $fromStmt = $conn->prepare("SELECT current_stock, description, price FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $fromStmt->bind_param('ss', $partNo, $fromBranch);
        $fromStmt->execute();
        $fromResult = $fromStmt->get_result();
        
        if ($fromResult->num_rows === 0) throw new Exception('Part not found at the source branch.');
        $fromPart = $fromResult->fetch_assoc();
        if ($fromPart['current_stock'] < $quantity) throw new Exception('Insufficient stock at the source branch.');

        $updateFromStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
        $updateFromStmt->bind_param('iss', $quantity, $partNo, $fromBranch);
        if (!$updateFromStmt->execute()) throw new Exception('Failed to update source stock: ' . $updateFromStmt->error);

        $toStmt = $conn->prepare("SELECT id FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $toStmt->bind_param('ss', $partNo, $toBranch);
        $toStmt->execute();

        if ($toStmt->get_result()->num_rows > 0) {
            $updateToStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
            $updateToStmt->bind_param('iss', $quantity, $partNo, $toBranch);
            if (!$updateToStmt->execute()) throw new Exception('Failed to update destination stock: ' . $updateToStmt->error);
        } else {
            $insertStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param('ssidds', $partNo, $fromPart['description'], $quantity, $cost, $fromPart['price'], $toBranch);
            if (!$insertStmt->execute()) throw new Exception('Failed to insert part at destination: ' . $insertStmt->error);
        }

        $totalCost = $quantity * $cost;
        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_cost, total_amount, from_location, to_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $typeOut = 'TRANSFER_OUT';
        $logStmt->bind_param('ssiddsss', $partNo, $transferDate, $typeOut, $quantity, $cost, $totalCost, $fromBranch, $toBranch);
        if (!$logStmt->execute()) throw new Exception('Failed to log transfer out: ' . $logStmt->error);
        
        $typeIn = 'TRANSFER_IN';
        $logStmt->bind_param('ssiddsss', $partNo, $transferDate, $typeIn, $quantity, $cost, $totalCost, $fromBranch, $toBranch);
        if (!$logStmt->execute()) throw new Exception('Failed to log transfer in: ' . $logStmt->error);

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Parts transferred successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error transferring parts: ' . $e->getMessage()]);
    }
}

function editPart() {
    global $conn, $currentBranch;
    $required = ['id', 'part_no', 'description', 'quantity', 'cost', 'price', 'min_stock'];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $id = (int)$_POST['id'];
    $partNo = sanitizeInput($_POST['part_no']);
    $description = sanitizeInput($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $cost = (float)$_POST['cost'];
    $price = (float)$_POST['price'];
    $minStock = (int)$_POST['min_stock'];

    $stmt = $conn->prepare("UPDATE spareparts_inventory SET part_no = ?, description = ?, current_stock = ?, cost = ?, price = ?, min_stock = ?, updated_at = NOW() WHERE id = ? AND current_branch = ?");
    $stmt->bind_param('ssiddiis', $partNo, $description, $quantity, $cost, $price, $minStock, $id, $currentBranch);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Part updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or part not found in this branch.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update part: ' . $stmt->error]);
    }
}

function deletePart() {
    global $conn, $currentBranch;
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing part ID.']);
        return;
    }

    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("DELETE FROM spareparts_inventory WHERE id = ? AND current_branch = ?");
    $stmt->bind_param('is', $id, $currentBranch);

    if ($stmt->execute()) {
         if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Part deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Part not found in this branch or already deleted.']);
        }
    } else {
        if ($conn->errno == 1451) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete part. It has existing transaction records.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete part: ' . $stmt->error]);
        }
    }
}
?>