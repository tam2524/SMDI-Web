<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$conn->query("CREATE TABLE IF NOT EXISTS spareparts_supplier_aging (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    invoice_no VARCHAR(100) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    date_received DATE,
    status VARCHAR(50) DEFAULT 'Active',
    branch VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$currentBranch = trim($_SESSION['user_branch'] ?? 'HEADOFFICE');
$action = $_REQUEST['action'] ?? '';

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

switch ($action) {
    case 'list':
        listPayables($conn, $currentBranch);
        break;
    case 'record_payment':
        recordPayment($conn, $currentBranch);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function listPayables($conn, $branch) {
    $sql = "SELECT * FROM spareparts_supplier_aging WHERE branch = ? ORDER BY date_received DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function recordPayment($conn, $branch) {
    $id = (int)$_POST['id'];
    $amount = (float)$_POST['amount'];
    $ref = sanitizeInput($_POST['reference'] ?? '');
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM spareparts_supplier_aging WHERE id = ? AND branch = ? AND status = 'Active' LIMIT 1");
        $stmt->bind_param('is', $id, $branch);
        $stmt->execute();
        $payable = $stmt->get_result()->fetch_assoc();
        
        if (!$payable) throw new Exception("Payable not found or already paid.");
        
        $newBalance = $payable['balance'] - $amount;
        $status = $newBalance <= 0 ? 'Paid' : 'Active';
        
        $stmt = $conn->prepare("UPDATE spareparts_supplier_aging SET balance = ?, status = ? WHERE id = ?");
        $stmt->bind_param('dsi', $newBalance, $status, $id);
        $stmt->execute();
        
        // Log transaction
        $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, total_amount, from_location, to_location, or_number, status, payment_method, reason) 
                                VALUES (CURDATE(), 'PAYMENT_TO_SUPPLIER', ?, ?, ?, ?, 'Completed', 'Cash', ?)");
        $supplier = $payable['supplier_name'];
        $invoice = $payable['invoice_no'];
        $reason = "Payment for Invoice $invoice. Ref: $ref";
        $log->bind_param('dssss', $amount, $branch, $supplier, $ref, $reason);
        $log->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()]);
    }
}
