<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$currentBranch = trim($_SESSION['user_branch'] ?? 'HEADOFFICE');
$action = $_REQUEST['action'] ?? '';

$conn->query("CREATE TABLE IF NOT EXISTS spareparts_pdc_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    customer_name VARCHAR(255),
    bank_name VARCHAR(100),
    check_no VARCHAR(50),
    check_date DATE,
    amount DECIMAL(15,2),
    status VARCHAR(20) DEFAULT 'Pending',
    branch VARCHAR(100),
    encoded_by VARCHAR(100),
    or_number VARCHAR(100),
    remarks TEXT,
    reflected_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

switch ($action) {
    case 'get_customers':
        getCustomers($conn, $currentBranch);
        break;
    case 'save_pdc':
        savePdc($conn, $currentBranch);
        break;
    case 'delete_pdc':
        deletePdc($conn, $currentBranch);
        break;
    case 'update_pdc':
        updatePdc($conn, $currentBranch);
        break;
    case 'list_pdc':
        listPdc($conn, $currentBranch);
        break;
    case 'reflect_pdc':
        reflectPdc($conn, $currentBranch);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function getCustomers($conn, $branch) {
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";
    
    // Join with spareparts_customers to get real ID
    $sql = "SELECT c.id, c.name as customer_name, SUM(a.balance) as total_balance 
            FROM spareparts_aging a
            LEFT JOIN spareparts_customers c ON a.customer_name = c.name AND a.branch = c.branch
            WHERE (a.customer_name LIKE ? OR a.or_number LIKE ?) AND a.branch = ? AND a.status = 'Active' AND a.balance > 0
            GROUP BY a.customer_name LIMIT 20";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'customers' => $data]);
}

function savePdc($conn, $branch) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $customer_name = sanitizeInput($_POST['customer_name']);
    $bank_name = sanitizeInput($_POST['bank_name']);
    $check_no = sanitizeInput($_POST['check_no']);
    $check_date = sanitizeInput($_POST['check_date']);
    $amount = (float)$_POST['amount'];
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    
    if (empty($customer_name) || empty($bank_name) || empty($check_no) || empty($check_date) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and amount must be positive.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO spareparts_pdc_payments (customer_id, customer_name, bank_name, check_no, check_date, amount, status, branch, encoded_by, remarks) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?)");
    $encoded_by = $_SESSION['username'];
    $stmt->bind_param('issssdsss', $customer_id, $customer_name, $bank_name, $check_no, $check_date, $amount, $branch, $encoded_by, $remarks);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'PDC saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save PDC: ' . $conn->error]);
    }
}

function updatePdc($conn, $branch) {
    $id = (int)$_POST['id'];
    $bank_name = sanitizeInput($_POST['bank_name']);
    $check_no = sanitizeInput($_POST['check_no']);
    $check_date = sanitizeInput($_POST['check_date']);
    $amount = (float)$_POST['amount'];
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    
    if (empty($bank_name) || empty($check_no) || empty($check_date) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and amount must be positive.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE spareparts_pdc_payments SET bank_name = ?, check_no = ?, check_date = ?, amount = ?, remarks = ? WHERE id = ? AND branch = ? AND status = 'Pending'");
    $stmt->bind_param('sssdsis', $bank_name, $check_no, $check_date, $amount, $remarks, $id, $branch);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'PDC updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update PDC: ' . $conn->error]);
    }
}

function deletePdc($conn, $branch) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM spareparts_pdc_payments WHERE id = ? AND branch = ? AND status = 'Pending'");
    $stmt->bind_param('is', $id, $branch);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'PDC deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete PDC.']);
    }
}

function listPdc($conn, $branch) {
    $status = sanitizeInput($_GET['status'] ?? 'Pending');
    $search = sanitizeInput($_GET['search'] ?? '');
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = ($page - 1) * $limit;
    
    $searchTerm = "%{$search}%";
    
    // Count total for pagination
    $countSql = "SELECT COUNT(*) as total FROM spareparts_pdc_payments WHERE branch = ? AND status = ? AND (customer_name LIKE ? OR check_no LIKE ? OR bank_name LIKE ?)";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('sssss', $branch, $status, $searchTerm, $searchTerm, $searchTerm);
    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
    
    $sql = "SELECT * FROM spareparts_pdc_payments 
            WHERE branch = ? AND status = ? AND (customer_name LIKE ? OR check_no LIKE ? OR bank_name LIKE ?)
            ORDER BY check_date ASC LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssii', $branch, $status, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode([
        'success' => true, 
        'pdcs' => $data, 
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalCount / $limit)
    ]);
}


function reflectPdc($conn, $branch) {
    $id = (int)$_POST['id'];
    
    $conn->begin_transaction();
    try {
        // 1. Get PDC details
        $stmt = $conn->prepare("SELECT p.*, c.name as customer_name FROM spareparts_pdc_payments p LEFT JOIN spareparts_customers c ON p.customer_id = c.id WHERE p.id = ? AND p.status = 'Pending' LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pdc = $stmt->get_result()->fetch_assoc();
        
        if (!$pdc) throw new Exception("PDC not found or already reflected.");
        
        $amount = (float)$pdc['amount'];
        $customer_name = $pdc['customer_name'];
        $bank_name = $pdc['bank_name'];
        $check_no = $pdc['check_no'];
        $date = date('Y-m-d'); // Today's reflection date
        
        // 2. Perform Payment (Similar to recordPayment in spareparts_inventory.php)
        $remainingAmount = $amount;
        
        // Fetch active accounts for this customer
        $sql = "SELECT id, or_number, customer_name, balance FROM spareparts_aging WHERE customer_name = ? AND branch = ? AND status = 'Active' AND balance > 0 ORDER BY sale_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $customer_name, $branch);
        $stmt->execute();
        $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        if (empty($accounts)) throw new Exception("No active accounts found for this customer.");

        foreach ($accounts as $acc) {
            if ($remainingAmount <= 0) break;
            
            $payAmount = min($remainingAmount, $acc['balance']);
            $remainingAmount -= $payAmount;
            
            // Update balance
            $upd = $conn->prepare("UPDATE spareparts_aging SET balance = balance - ?, status = IF(balance - ? <= 0, 'Paid', 'Active') WHERE id = ?");
            $upd->bind_param('ddi', $payAmount, $payAmount, $acc['id']);
            $upd->execute();
            
            // Log transaction
            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, or_number, customer_name, type, total_amount, from_location, transaction_type, payment_method, check_number, bank_name, status) 
                                    VALUES (?, ?, ?, 'PAYMENT', ?, ?, ?, 'Check', ?, ?, 'Completed')");
            $receipt_no = "REF-" . $check_no;
            $type_out = $acc['or_number']; // Reference to original sale
            $log->bind_param('sssdssss', $date, $receipt_no, $acc['customer_name'], $payAmount, $branch, $type_out, $check_no, $bank_name);
            $log->execute();
        }
        
        // 3. Update PDC status
        $updStatus = $conn->prepare("UPDATE spareparts_pdc_payments SET status = 'Reflected', reflected_date = NOW() WHERE id = ?");
        $updStatus->bind_param('i', $id);
        $updStatus->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'PDC reflected and balance updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to reflect PDC: ' . $e->getMessage()]);
    }
}
