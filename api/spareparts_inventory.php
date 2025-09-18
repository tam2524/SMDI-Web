<?php
header('Content-Type: application/json');
require_once 'db_config.php'; // Ensure you have session_start() in this file

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin'];
$canDelete = in_array($userRole, $adminRoles);

function sanitizeInput($data) { return htmlspecialchars(strip_tags(trim($data))); }

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    // READ
    case 'get_dashboard_stats': getDashboardStats(); break;
    case 'get_inventory_list': getInventoryList(); break;
    case 'get_sales_list': getSalesList(); break;
    case 'get_payments_list': getPaymentsList(); break;
    case 'get_transfers_list': getTransfersList(); break;
    case 'search_inventory_parts': searchInventoryParts(); break;
    case 'search_customer_accounts': searchCustomerAccounts(); break; // NEW
    case 'get_incoming_transfers': getIncomingTransfers(); break; // NEW
    
    // CUD
    case 'add_multiple_parts_in': addMultiplePartsIn(); break;
    case 'sell_multiple_parts_out': sellMultiplePartsOut(); break;
    case 'record_payment': recordPayment(); break;
    case 'transfer_multiple_parts': transferMultipleParts(); break; // MODIFIED
    case 'accept_transfer': acceptTransfer(); break; // NEW
    case 'edit_parts': editPart(); break;
    
    // DELETE (WITH AUTH)
    case 'delete_part': deleteItem('part'); break; // MODIFIED
    case 'delete_sale': deleteItem('sale'); break; // NEW
    case 'delete_payment': deleteItem('payment'); break; // NEW
    case 'delete_transfer': deleteItem('transfer'); break; // NEW
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

// ===================================================================
// ============================= READ DATA ===========================
// ===================================================================
function getDashboardStats() {
    global $conn, $currentBranch;
    $stats = [];
    // Total Qty
    $stmt = $conn->prepare("SELECT SUM(current_stock) as total FROM spareparts_inventory WHERE current_branch = ?");
    $stmt->bind_param('s', $currentBranch); $stmt->execute();
    $stats['total_quantity'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    // Total Value
    $stmt = $conn->prepare("SELECT SUM(current_stock * cost) as total FROM spareparts_inventory WHERE current_branch = ?");
    $stmt->bind_param('s', $currentBranch); $stmt->execute();
    $stats['total_value'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    // Monthly Sales
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM spareparts_transactions WHERE type = 'OUT' AND from_location = ? AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
    $stmt->bind_param('s', $currentBranch); $stmt->execute();
    $stats['monthly_sales'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    // Yearly Sales
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM spareparts_transactions WHERE type = 'OUT' AND from_location = ? AND YEAR(transaction_date) = YEAR(CURDATE())");
    $stmt->bind_param('s', $currentBranch); $stmt->execute();
    $stats['yearly_sales'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    // Outstanding Balance
    $stmt = $conn->prepare("SELECT SUM(balance) as total FROM spareparts_aging WHERE branch = ? AND status = 'Active'");
    $stmt->bind_param('s', $currentBranch); $stmt->execute();
    $stats['outstanding_balance'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    // Total Accounts
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM spareparts_aging WHERE branch = ? AND status = 'Active'");
    $stmt->bind_param('s', $currentBranch); $stmt->execute();
    $stats['total_accounts'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

    echo json_encode(['success' => true, 'data' => $stats]);
}

function getSalesList() {
    global $conn, $currentBranch;
    // MODIFIED: Join with aging to get current balance
    $sql = "SELECT t.id, t.transaction_date as sale_date, t.customer_name, t.or_number, t.transaction_type, SUM(t.total_amount) as total_amount, a.balance
            FROM spareparts_transactions t
            LEFT JOIN spareparts_aging a ON t.or_number = a.or_number AND t.from_location = a.branch
            WHERE t.type = 'OUT' AND t.from_location = ?
            GROUP BY t.or_number
            ORDER BY t.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getTransfersList() {
    global $conn, $currentBranch;
    // MODIFIED: Show status
    $stmt = $conn->prepare("SELECT id, transaction_date, part_no, quantity, from_location, to_location, status FROM spareparts_transactions WHERE type = 'TRANSFER_OUT' AND from_location = ? ORDER BY id DESC");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getIncomingTransfers() {
    global $conn, $currentBranch;
    // NEW: Get transfers waiting for acceptance
    $stmt = $conn->prepare("SELECT id, transaction_date, from_location, COUNT(part_no) as item_count FROM spareparts_transactions WHERE to_location = ? AND status = 'In-Transit' GROUP BY id ORDER BY id DESC");
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $transfers = [];
    while ($row = $result->fetch_assoc()) {
        $itemStmt = $conn->prepare("SELECT part_no, description, quantity FROM spareparts_transactions WHERE id = ?");
        $itemStmt->bind_param('i', $row['id']);
        $itemStmt->execute();
        $itemsResult = $itemStmt->get_result();
        $items = [];
        while($item = $itemsResult->fetch_assoc()) $items[] = $item;
        $row['items'] = $items;
        $transfers[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $transfers]);
}


function searchCustomerAccounts() {
    global $conn, $currentBranch;
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";
    $stmt = $conn->prepare("SELECT or_number, sale_date, balance FROM spareparts_aging WHERE customer_name LIKE ? AND branch = ? AND status = 'Active' LIMIT 5");
    $stmt->bind_param('ss', $searchTerm, $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

// ===================================================================
// ========================= CUD OPERATIONS ==========================
// ===================================================================

function transferMultipleParts() {
    global $conn, $currentBranch;
    // ... validation ...
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $items = json_decode($_POST['items'], true);

    $conn->begin_transaction();
    try {
        // Create a single transfer record
        $transferStmt = $conn->prepare("INSERT INTO spareparts_transfers (from_branch, to_branch, transfer_date, status) VALUES (?, ?, ?, 'In-Transit')");
        $transferStmt->bind_param('sss', $currentBranch, $toBranch, $transferDate);
        if(!$transferStmt->execute()) throw new Exception('Failed to create transfer record.');
        $transferId = $conn->insert_id;

        foreach ($items as $item) {
            // Deduct stock from source branch
            // ... (stock deduction logic from your original code) ...

            // Log each item in the transfer
            $logStmt = $conn->prepare("INSERT INTO spareparts_transfer_items (transfer_id, part_no, description, quantity, cost) VALUES (?, ?, ?, ?, ?)");
            $logStmt->bind_param('issid', $transferId, $item['part_no'], $item['description'], $item['quantity'], $item['cost']);
            if(!$logStmt->execute()) throw new Exception('Failed to log transfer item.');
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer initiated successfully. It is now In-Transit.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()]);
    }
}

function acceptTransfer() {
    global $conn, $currentBranch;
    $transferId = (int)$_POST['transfer_id'];

    $conn->begin_transaction();
    try {
        // Fetch transfer items
        $itemStmt = $conn->prepare("SELECT * FROM spareparts_transfer_items WHERE transfer_id = ?");
        $itemStmt->bind_param('i', $transferId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if(empty($items)) throw new Exception("No items found for this transfer.");

        foreach($items as $item){
            // Add/Update stock in the destination branch (current user's branch)
             // ... (add stock logic from your original code, using weighted average if part exists) ...
        }

        // Update transfer status
        $updateStmt = $conn->prepare("UPDATE spareparts_transfers SET status = 'Completed', received_date = NOW() WHERE id = ? AND to_branch = ?");
        $updateStmt->bind_param('is', $transferId, $currentBranch);
        if(!$updateStmt->execute() || $updateStmt->affected_rows == 0) throw new Exception("Failed to update transfer status or unauthorized.");
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer accepted and items added to inventory.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to accept transfer: ' . $e->getMessage()]);
    }
}


// ===================================================================
// ========================= DELETE HANDLER ==========================
// ===================================================================

function deleteItem($type) {
    global $conn, $canDelete, $currentBranch;
    if (!$canDelete) {
        echo json_encode(['success' => false, 'message' => 'Authorization failed.']);
        return;
    }
    
    $id = sanitizeInput($_POST['id']);
    $conn->begin_transaction();
    try {
        switch($type) {
            case 'part':
                $stmt = $conn->prepare("DELETE FROM spareparts_inventory WHERE id = ? AND current_branch = ?");
                $stmt->bind_param('is', $id, $currentBranch);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                if ($stmt->affected_rows === 0) throw new Exception("Part not found or already deleted.");
                break;

            case 'sale': // ID is the OR number
                // Find all parts in the sale
                $findStmt = $conn->prepare("SELECT part_no, quantity FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
                $findStmt->bind_param('ss', $id, $currentBranch);
                $findStmt->execute();
                $items = $findStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Return stock
                foreach ($items as $item) {
                    $updateStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
                    $updateStmt->bind_param('iss', $item['quantity'], $item['part_no'], $currentBranch);
                    if(!$updateStmt->execute()) throw new Exception("Failed to return stock for ".$item['part_no']);
                }
                
                // Delete sale records
                $delStmt = $conn->prepare("DELETE FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
                $delStmt->bind_param('ss', $id, $currentBranch);
                if(!$delStmt->execute()) throw new Exception("Failed to delete transaction log.");
                
                // Delete aging record
                $delAging = $conn->prepare("DELETE FROM spareparts_aging WHERE or_number = ? AND branch = ?");
                $delAging->bind_param('ss', $id, $currentBranch);
                $delAging->execute(); // It's okay if this fails (e.g., for cash sales)
                break;
            
            case 'payment': // ID is the transaction ID
                 // Find payment details
                $findStmt = $conn->prepare("SELECT total_amount, or_number FROM spareparts_transactions WHERE id = ? AND from_location = ? AND type = 'PAYMENT'");
                $findStmt->bind_param('is', $id, $currentBranch);
                $findStmt->execute();
                $payment = $findStmt->get_result()->fetch_assoc();
                if(!$payment) throw new Exception("Payment record not found.");
                
                // Add amount back to balance
                $updateStmt = $conn->prepare("UPDATE spareparts_aging SET balance = balance + ? WHERE or_number = ? AND branch = ?");
                $updateStmt->bind_param('dss', $payment['total_amount'], $payment['or_number'], $currentBranch);
                if(!$updateStmt->execute()) throw new Exception("Failed to update customer balance.");
                
                // Delete payment transaction
                $delStmt = $conn->prepare("DELETE FROM spareparts_transactions WHERE id = ?");
                $delStmt->bind_param('i', $id);
                if(!$delStmt->execute()) throw new Exception("Failed to delete payment log.");
                break;
            // ... (add transfer cancellation logic here if needed)
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' deleted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
    }
}
// Note: Other functions like editPart, addMultiplePartsIn etc. are assumed to be mostly correct from your original code. The key changes are highlighted.
?>