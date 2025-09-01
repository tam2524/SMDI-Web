<?php
// Prevent any output before JSON
ob_start();

// Set error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../api/db_config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function sanitizeInput($data) {
    global $conn;
    if (!$conn) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

// Function to send JSON response and exit
function sendJsonResponse($data) {
    if (ob_get_level()) {
        ob_clean();
    }
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = json_encode([
            'success' => false, 
            'message' => 'JSON encoding error: ' . json_last_error_msg()
        ]);
    }
    
    echo $json;
    exit;
}

// Error handler for uncaught exceptions
set_exception_handler(function($exception) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Server error: ' . $exception->getMessage()
    ]);
});

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
}

$action = isset($_REQUEST['action']) ? sanitizeInput($_REQUEST['action']) : '';

if (empty($action)) {
    sendJsonResponse(['success' => false, 'message' => 'No action specified']);
}

switch ($action) {
    // FORM 1: IN
    case 'add_spareparts_in':
        addSparepartsIn();
        break;
    case 'get_spareparts_in':
        getSparepartsIn();
        break;
    
    // FORM 2: SALES/OUT
    case 'add_sale':
        addSale();
        break;
    case 'get_sales':
        getSales();
        break;
    
    // FORM 3: PAYMENT
    case 'add_payment':
        addPayment();
        break;
    case 'get_payments':
        getPayments();
        break;
    
    // FORM 4: TRANSFER
    case 'add_transfer':
        addTransfer();
        break;
    case 'get_transfers':
        getTransfers();
        break;
    
    // REPORTS
    case 'get_aging_report':
        getAgingReport();
        break;
    case 'get_sales_report':
        getSalesReport();
        break;
    case 'get_payment_summary':
        getPaymentSummary();
        break;
    
    // UTILITY
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
    
    default:
        sendJsonResponse(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

// FORM 1: IN - Add spareparts received (simplified - no inventory table updates)
function addSparepartsIn() {
    global $conn;

    try {
        $required = ['part_no', 'quantity', 'cost', 'date_received', 'invoice_no'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                sendJsonResponse(['success' => false, 'message' => "Missing required field: $field"]);
            }
        }

        $partNo = sanitizeInput($_POST['part_no']);
        $quantity = intval($_POST['quantity']);
        $cost = floatval($_POST['cost']);
        $dateReceived = sanitizeInput($_POST['date_received']);
        $invoiceNo = sanitizeInput($_POST['invoice_no']);
        $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : 'MAIN';
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

        if ($quantity <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Quantity must be greater than 0']);
        }

        if ($cost < 0) {
            sendJsonResponse(['success' => false, 'message' => 'Cost cannot be negative']);
        }

        // Insert into spareparts_in only
        $stmt = $conn->prepare("INSERT INTO spareparts_in (part_no, quantity, cost, date_received, invoice_no, branch, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sidssss', $partNo, $quantity, $cost, $dateReceived, $invoiceNo, $branch, $notes);
        
        if (!$stmt->execute()) {
            throw new Exception('Error inserting spareparts_in: ' . $stmt->error);
        }

        sendJsonResponse(['success' => true, 'message' => 'Spareparts received successfully']);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error adding spareparts: ' . $e->getMessage()]);
    }
}

// Get spareparts IN records
function getSparepartsIn() {
    global $conn;

    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';

        $sql = "SELECT * FROM spareparts_in WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM spareparts_in WHERE 1=1";

        // Branch filtering
        if (!empty($branch)) {
            $branchCondition = " AND branch = '" . $conn->real_escape_string($branch) . "'";
            $sql .= $branchCondition;
            $countSql .= $branchCondition;
        }

        // Search filtering
        if (!empty($search)) {
            $searchTerm = $conn->real_escape_string($search);
            $searchCondition = " AND (part_no LIKE '%$searchTerm%' OR invoice_no LIKE '%$searchTerm%')";
            $sql .= $searchCondition;
            $countSql .= $searchCondition;
        }

        // Get total count
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];

        // Add sorting and pagination
        $sql .= " ORDER BY date_received DESC, id DESC LIMIT $limit OFFSET $offset";

        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'total' => (int)$totalRecords,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRecords / $limit)
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error fetching spareparts in: ' . $e->getMessage()]);
    }
}

// FORM 2: SALES/OUT - Add sale (simplified - no stock checking)
function addSale() {
    global $conn;

    try {
        $required = ['part_no', 'sale_date', 'transaction_type', 'quantity', 'amount', 'or_number', 'customer_name'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                sendJsonResponse(['success' => false, 'message' => "Missing required field: $field"]);
            }
        }

        $partNo = sanitizeInput($_POST['part_no']);
        $saleDate = sanitizeInput($_POST['sale_date']);
        $transactionType = sanitizeInput($_POST['transaction_type']);
        $quantity = intval($_POST['quantity']);
        $amount = floatval($_POST['amount']);
        $orNumber = sanitizeInput($_POST['or_number']);
        $customerName = sanitizeInput($_POST['customer_name']);
        $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : 'MAIN';

        if ($quantity <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Quantity must be greater than 0']);
        }

        if ($amount <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Amount must be greater than 0']);
        }

        // Calculate balance for installment
        $balance = ($transactionType === 'installment') ? $amount : 0.00;

        // Check for duplicate OR number
        $orCheckStmt = $conn->prepare("SELECT id FROM spareparts_out WHERE or_number = ? AND branch = ?");
        $orCheckStmt->bind_param('ss', $orNumber, $branch);
        $orCheckStmt->execute();
        if ($orCheckStmt->get_result()->num_rows > 0) {
            throw new Exception('OR Number already exists');
        }

        // Insert sale record
        $saleStmt = $conn->prepare("INSERT INTO spareparts_out (part_no, sale_date, transaction_type, quantity, amount, or_number, customer_name, balance, branch) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $saleStmt->bind_param('sssiissds', $partNo, $saleDate, $transactionType, $quantity, $amount, $orNumber, $customerName, $balance, $branch);
        
        if (!$saleStmt->execute()) {
            throw new Exception('Error inserting sale: ' . $saleStmt->error);
        }

        sendJsonResponse([
            'success' => true, 
            'message' => 'Sale recorded successfully'
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error recording sale: ' . $e->getMessage()]);
    }
}

// Get sales records
function getSales() {
    global $conn;

    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
        $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
        $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

        $sql = "SELECT * FROM spareparts_out WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM spareparts_out WHERE 1=1";

        // Branch filtering
        if (!empty($branch)) {
            $branchCondition = " AND branch = '" . $conn->real_escape_string($branch) . "'";
            $sql .= $branchCondition;
            $countSql .= $branchCondition;
        }

        // Date filtering
        if (!empty($startDate) && !empty($endDate)) {
            $dateCondition = " AND sale_date BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
            $sql .= $dateCondition;
            $countSql .= $dateCondition;
        }

        // Search filtering
        if (!empty($search)) {
            $searchTerm = $conn->real_escape_string($search);
            $searchCondition = " AND (part_no LIKE '%$searchTerm%' OR customer_name LIKE '%$searchTerm%' OR or_number LIKE '%$searchTerm%')";
            $sql .= $searchCondition;
            $countSql .= $searchCondition;
        }

        // Get total count
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];

        // Add sorting and pagination
        $sql .= " ORDER BY sale_date DESC, id DESC LIMIT $limit OFFSET $offset";

        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'part_no' => $row['part_no'],
                'sale_date' => $row['sale_date'],
                'transaction_type' => $row['transaction_type'],
                'quantity' => $row['quantity'],
                'amount' => $row['amount'],
                'or_number' => $row['or_number'],
                'customer_name' => $row['customer_name'],
                'balance' => $row['balance'],
                'branch' => $row['branch'],
                'created_at' => $row['created_at']
            ];
        }

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'total' => (int)$totalRecords,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRecords / $limit)
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error fetching sales: ' . $e->getMessage()]);
    }
}

// FORM 3: PAYMENT - Add payment
function addPayment() {
    global $conn;

    try {
        $required = ['payment_date', 'customer_name', 'amount'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                sendJsonResponse(['success' => false, 'message' => "Missing required field: $field"]);
            }
        }

        $paymentDate = sanitizeInput($_POST['payment_date']);
        $customerName = sanitizeInput($_POST['customer_name']);
        $amount = floatval($_POST['amount']);
        $orNumber = isset($_POST['or_number']) ? sanitizeInput($_POST['or_number']) : '';
        $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : 'MAIN';
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

        if ($amount <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Amount must be greater than 0']);
        }

        $conn->begin_transaction();

        // Find unpaid or partially paid sales for this customer
        $findSalesStmt = $conn->prepare("SELECT id, or_number, amount, balance FROM spareparts_out 
            WHERE customer_name = ? AND balance > 0 AND branch = ?
            ORDER BY sale_date ASC");
        $findSalesStmt->bind_param('ss', $customerName, $branch);
        $findSalesStmt->execute();
        $salesResult = $findSalesStmt->get_result();

        if ($salesResult->num_rows === 0) {
            throw new Exception('No unpaid sales found for customer: ' . $customerName);
        }

        $remainingPayment = $amount;
        $paymentsApplied = [];

        // Apply payment to sales (FIFO - First In, First Out)
        while ($remainingPayment > 0 && $saleRow = $salesResult->fetch_assoc()) {
            $saleId = $saleRow['id'];
            $currentBalance = $saleRow['balance'];
            
            if ($currentBalance <= 0) {
                continue; // Skip already paid sales
            }

            $paymentToApply = min($remainingPayment, $currentBalance);
            $newBalance = $currentBalance - $paymentToApply;

            // Update the sale balance
            $updateSaleStmt = $conn->prepare("UPDATE spareparts_out SET balance = ? WHERE id = ?");
            $updateSaleStmt->bind_param('di', $newBalance, $saleId);
            $updateSaleStmt->execute();

            $paymentsApplied[] = [
                'sale_id' => $saleId,
                'or_number' => $saleRow['or_number'],
                'payment_applied' => $paymentToApply,
                'new_balance' => $newBalance
            ];

            $remainingPayment -= $paymentToApply;
        }

        if ($remainingPayment > 0) {
            throw new Exception('Payment amount exceeds total outstanding balance');
        }

        // Insert payment record
        $paymentStmt = $conn->prepare("INSERT INTO spareparts_payments (payment_date, customer_name, amount, or_number, branch, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $paymentStmt->bind_param('ssdsss', $paymentDate, $customerName, $amount, $orNumber, $branch, $notes);
        $paymentStmt->execute();

        $conn->commit();
        sendJsonResponse([
            'success' => true, 
            'message' => 'Payment recorded successfully',
            'payment_id' => $conn->insert_id,
            'payments_applied' => $paymentsApplied
        ]);

    } catch (Exception $e) {
        if ($conn) {
            $conn->rollback();
        }
        sendJsonResponse(['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()]);
    }
}

// Get payment records
function getPayments() {
    global $conn;

    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
        $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
        $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

        $sql = "SELECT * FROM spareparts_payments WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM spareparts_payments WHERE 1=1";

        // Branch filtering
        if (!empty($branch)) {
            $branchCondition = " AND branch = '" . $conn->real_escape_string($branch) . "'";
            $sql .= $branchCondition;
            $countSql .= $branchCondition;
        }

        // Date filtering
        if (!empty($startDate) && !empty($endDate)) {
            $dateCondition = " AND payment_date BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
            $sql .= $dateCondition;
            $countSql .= $dateCondition;
        }

        // Search filtering
        if (!empty($search)) {
            $searchTerm = $conn->real_escape_string($search);
            $searchCondition = " AND (customer_name LIKE '%$searchTerm%' OR or_number LIKE '%$searchTerm%')";
            $sql .= $searchCondition;
            $countSql .= $searchCondition;
        }

        // Get total count
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];

        // Add sorting and pagination
        $sql .= " ORDER BY payment_date DESC, id DESC LIMIT $limit OFFSET $offset";

        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'payment_date' => $row['payment_date'],
                'customer_name' => $row['customer_name'],
                'amount' => $row['amount'],
                'or_number' => $row['or_number'],
                'branch' => $row['branch'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at']
            ];
        }

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'total' => (int)$totalRecords,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRecords / $limit)
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error fetching payments: ' . $e->getMessage()]);
    }
}

// FORM 4: TRANSFER - Add transfer (simplified - no stock checking)
function addTransfer() {
    global $conn;

    try {
        $required = ['transfer_date', 'part_no', 'quantity', 'cost'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                sendJsonResponse(['success' => false, 'message' => "Missing required field: $field"]);
            }
        }

        $transferDate = sanitizeInput($_POST['transfer_date']);
        $partNo = sanitizeInput($_POST['part_no']);
        $quantity = intval($_POST['quantity']);
        $cost = floatval($_POST['cost']);
        $fromBranch = isset($_POST['from_branch']) ? sanitizeInput($_POST['from_branch']) : 'MAIN';
        $toBranch = isset($_POST['to_branch']) ? sanitizeInput($_POST['to_branch']) : '';
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

        if ($quantity <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Quantity must be greater than 0']);
        }

        if ($cost < 0) {
            sendJsonResponse(['success' => false, 'message' => 'Cost cannot be negative']);
        }

        if (empty($toBranch)) {
            sendJsonResponse(['success' => false, 'message' => 'Destination branch is required']);
        }

        if ($fromBranch === $toBranch) {
            sendJsonResponse(['success' => false, 'message' => 'Cannot transfer to the same branch']);
        }

        // Insert transfer record only
        $transferStmt = $conn->prepare("INSERT INTO spareparts_transfers (transfer_date, part_no, quantity, cost, from_branch, to_branch, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $transferStmt->bind_param('sisdsss', $transferDate, $partNo, $quantity, $cost, $fromBranch, $toBranch, $notes);
        
        if (!$transferStmt->execute()) {
            throw new Exception('Error inserting transfer: ' . $transferStmt->error);
        }

        sendJsonResponse([
            'success' => true, 
            'message' => 'Transfer recorded successfully',
            'transfer_id' => $conn->insert_id
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error processing transfer: ' . $e->getMessage()]);
    }
}

// Get transfer records
function getTransfers() {
    global $conn;

    try {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;

        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
        $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
        $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

        $sql = "SELECT * FROM spareparts_transfers WHERE 1=1";
        $countSql = "SELECT COUNT(*) as total FROM spareparts_transfers WHERE 1=1";

        // Branch filtering (show transfers from or to the specified branch)
        if (!empty($branch)) {
            $branchCondition = " AND (from_branch = '" . $conn->real_escape_string($branch) . "' OR to_branch = '" . $conn->real_escape_string($branch) . "')";
            $sql .= $branchCondition;
            $countSql .= $branchCondition;
        }

        // Date filtering
        if (!empty($startDate) && !empty($endDate)) {
            $dateCondition = " AND transfer_date BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
            $sql .= $dateCondition;
            $countSql .= $dateCondition;
        }

        // Search filtering
        if (!empty($search)) {
            $searchTerm = $conn->real_escape_string($search);
            $searchCondition = " AND (part_no LIKE '%$searchTerm%' OR from_branch LIKE '%$searchTerm%' OR to_branch LIKE '%$searchTerm%')";
            $sql .= $searchCondition;
            $countSql .= $searchCondition;
        }

        // Get total count
        $countResult = $conn->query($countSql);
        $totalRecords = $countResult->fetch_assoc()['total'];

        // Add sorting and pagination
        $sql .= " ORDER BY transfer_date DESC, id DESC LIMIT $limit OFFSET $offset";

        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => $row['id'],
                'transfer_date' => $row['transfer_date'],
                'part_no' => $row['part_no'],
                'quantity' => $row['quantity'],
                'cost' => $row['cost'],
                'total_cost' => $row['quantity'] * $row['cost'],
                'from_branch' => $row['from_branch'],
                'to_branch' => $row['to_branch'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at']
            ];
        }

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'total' => (int)$totalRecords,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRecords / $limit)
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error fetching transfers: ' . $e->getMessage()]);
    }
}

// REPORTS - Monthly Aging Report
function getAgingReport() {
    global $conn;

    try {
        $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : date('Y-m');
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';

        // Parse month to get start and end dates
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT 
                    so.or_number,
                    so.customer_name,
                    so.part_no,
                    so.sale_date,
                    so.transaction_type,
                    so.amount as original_amount,
                    so.balance as current_balance,
                    so.branch,
                    DATEDIFF(CURDATE(), so.sale_date) as days_outstanding,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), so.sale_date) <= 30 THEN '0-30 days'
                        WHEN DATEDIFF(CURDATE(), so.sale_date) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURDATE(), so.sale_date) <= 90 THEN '61-90 days'
                        ELSE 'Over 90 days'
                    END as aging_category,
                    (SELECT SUM(sp.amount) FROM spareparts_payments sp 
                     WHERE sp.customer_name = so.customer_name 
                     AND sp.payment_date <= '$endDate') as total_payments
                FROM spareparts_out so
                WHERE so.balance > 0";

        // Add date filter for sales within or before the specified month
        $sql .= " AND so.sale_date <= '$endDate'";

        // Branch filtering
        if (!empty($branch)) {
            $sql .= " AND so.branch = '" . $conn->real_escape_string($branch) . "'";
        }

        $sql .= " ORDER BY so.customer_name, so.sale_date ASC";

        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception('Query failed: ' . $conn->error);
        }

        $data = [];
        $summary = [
            'total_outstanding' => 0,
            'total_customers' => 0,
            'aging_breakdown' => [
                '0-30 days' => ['count' => 0, 'amount' => 0],
                '31-60 days' => ['count' => 0, 'amount' => 0],
                '61-90 days' => ['count' => 0, 'amount' => 0],
                'Over 90 days' => ['count' => 0, 'amount' => 0]
            ],
            'by_branch' => []
        ];

        $customers = [];

        while ($row = $result->fetch_assoc()) {
            $balance = floatval($row['current_balance']);
            $agingCategory = $row['aging_category'];
            $customerBranch = $row['branch'];

            $data[] = [
                'or_number' => $row['or_number'],
                'customer_name' => $row['customer_name'],
                'part_no' => $row['part_no'],
                'sale_date' => $row['sale_date'],
                'transaction_type' => $row['transaction_type'],
                'original_amount' => floatval($row['original_amount']),
                'current_balance' => $balance,
                'total_payments' => floatval($row['total_payments'] ?? 0),
                'days_outstanding' => intval($row['days_outstanding']),
                'aging_category' => $agingCategory,
                'branch' => $customerBranch
            ];

            // Update summary
            $summary['total_outstanding'] += $balance;
            $summary['aging_breakdown'][$agingCategory]['amount'] += $balance;

            // Track unique customers
            $customers[$row['customer_name']] = true;

            // Branch summary
            if (!isset($summary['by_branch'][$customerBranch])) {
                $summary['by_branch'][$customerBranch] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $summary['by_branch'][$customerBranch]['count']++;
            $summary['by_branch'][$customerBranch]['amount'] += $balance;
        }

        $summary['total_customers'] = count($customers);

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
            'month' => $month,
            'branch' => $branch,
            'generated_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error generating aging report: ' . $e->getMessage()]);
    }
}

// Monthly and Daily Sales Report
function getSalesReport() {
    global $conn;

    try {
        $reportType = isset($_GET['report_type']) ? sanitizeInput($_GET['report_type']) : 'monthly'; // monthly or daily
        $period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '';
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';

        if (empty($period)) {
            if ($reportType === 'monthly') {
                $period = date('Y-m');
            } else {
                $period = date('Y-m-d');
            }
        }

        // Set date range based on report type
        if ($reportType === 'monthly') {
            $startDate = $period . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
        } else {
            $startDate = $period;
            $endDate = $period;
        }

        $sql = "SELECT * FROM spareparts_out WHERE sale_date BETWEEN '$startDate' AND '$endDate'";

        // Branch filtering
        if (!empty($branch)) {
            $sql .= " AND branch = '" . $conn->real_escape_string($branch) . "'";
        }

        $sql .= " ORDER BY sale_date DESC, id DESC";

        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception('Query failed: ' . $conn->error);
        }

        $data = [];
        $summary = [
            'total_sales' => 0,
            'total_amount' => 0,
            'cash_sales' => ['count' => 0, 'amount' => 0],
            'installment_sales' => ['count' => 0, 'amount' => 0],
            'by_branch' => [],
            'by_part' => [],
            'daily_breakdown' => []
        ];

        while ($row = $result->fetch_assoc()) {
            $amount = floatval($row['amount']);
            $transactionType = $row['transaction_type'];
            $saleDate = $row['sale_date'];
            $saleBranch = $row['branch'];
            $partNo = $row['part_no'];

            $data[] = [
                'id' => $row['id'],
                'or_number' => $row['or_number'],
                'sale_date' => $saleDate,
                'part_no' => $partNo,
                'quantity' => intval($row['quantity']),
                'amount' => $amount,
                'customer_name' => $row['customer_name'],
                'transaction_type' => $transactionType,
                'balance' => floatval($row['balance']),
                'branch' => $saleBranch
            ];

            // Update summary
            $summary['total_sales']++;
            $summary['total_amount'] += $amount;

            // Transaction type breakdown
            if ($transactionType === 'cash') {
                $summary['cash_sales']['count']++;
                $summary['cash_sales']['amount'] += $amount;
            } else {
                $summary['installment_sales']['count']++;
                $summary['installment_sales']['amount'] += $amount;
            }

            // Branch breakdown
            if (!isset($summary['by_branch'][$saleBranch])) {
                $summary['by_branch'][$saleBranch] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $summary['by_branch'][$saleBranch]['count']++;
            $summary['by_branch'][$saleBranch]['amount'] += $amount;

            // Part breakdown
            if (!isset($summary['by_part'][$partNo])) {
                $summary['by_part'][$partNo] = [
                    'count' => 0,
                    'quantity' => 0,
                    'amount' => 0
                ];
            }
            $summary['by_part'][$partNo]['count']++;
            $summary['by_part'][$partNo]['quantity'] += intval($row['quantity']);
            $summary['by_part'][$partNo]['amount'] += $amount;

            // Daily breakdown
            if (!isset($summary['daily_breakdown'][$saleDate])) {
                $summary['daily_breakdown'][$saleDate] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $summary['daily_breakdown'][$saleDate]['count']++;
            $summary['daily_breakdown'][$saleDate]['amount'] += $amount;
        }

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
            'report_type' => $reportType,
            'period' => $period,
            'branch' => $branch,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error generating sales report: ' . $e->getMessage()]);
    }
}

// Monthly and Daily Payment Summary
function getPaymentSummary() {
    global $conn;

    try {
        $reportType = isset($_GET['report_type']) ? sanitizeInput($_GET['report_type']) : 'monthly'; // monthly or daily
        $period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '';
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';

        if (empty($period)) {
            if ($reportType === 'monthly') {
                $period = date('Y-m');
            } else {
                $period = date('Y-m-d');
            }
        }

        // Set date range based on report type
        if ($reportType === 'monthly') {
            $startDate = $period . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));
        } else {
            $startDate = $period;
            $endDate = $period;
        }

        $sql = "SELECT * FROM spareparts_payments 
                WHERE payment_date BETWEEN '$startDate' AND '$endDate'";

        // Branch filtering
        if (!empty($branch)) {
            $sql .= " AND branch = '" . $conn->real_escape_string($branch) . "'";
        }

        $sql .= " ORDER BY payment_date DESC, id DESC";

        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception('Query failed: ' . $conn->error);
        }

        $data = [];
        $summary = [
            'total_payments' => 0,
            'total_amount' => 0,
            'by_branch' => [],
            'by_customer' => [],
            'daily_breakdown' => []
        ];

        while ($row = $result->fetch_assoc()) {
            $amount = floatval($row['amount']);
            $paymentDate = $row['payment_date'];
            $paymentBranch = $row['branch'];
            $customerName = $row['customer_name'];

            $data[] = [
                'id' => $row['id'],
                'payment_date' => $paymentDate,
                'customer_name' => $customerName,
                'amount' => $amount,
                'or_number' => $row['or_number'],
                'branch' => $paymentBranch,
                'notes' => $row['notes']
            ];

            // Update summary
            $summary['total_payments']++;
            $summary['total_amount'] += $amount;

            // Branch breakdown
            if (!isset($summary['by_branch'][$paymentBranch])) {
                $summary['by_branch'][$paymentBranch] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $summary['by_branch'][$paymentBranch]['count']++;
            $summary['by_branch'][$paymentBranch]['amount'] += $amount;

            // Customer breakdown
            if (!isset($summary['by_customer'][$customerName])) {
                $summary['by_customer'][$customerName] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $summary['by_customer'][$customerName]['count']++;
            $summary['by_customer'][$customerName]['amount'] += $amount;

            // Daily breakdown
            if (!isset($summary['daily_breakdown'][$paymentDate])) {
                $summary['daily_breakdown'][$paymentDate] = [
                    'count' => 0,
                    'amount' => 0
                ];
            }
            $summary['daily_breakdown'][$paymentDate]['count']++;
            $summary['daily_breakdown'][$paymentDate]['amount'] += $amount;
        }

        // Sort customer breakdown by amount (highest first)
        uasort($summary['by_customer'], function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        sendJsonResponse([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
            'report_type' => $reportType,
            'period' => $period,
            'branch' => $branch,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => 'Error generating payment summary: ' . $e->getMessage()]);
    }
}

// Get dashboard statistics (simplified for transaction tables only)
function getDashboardStats() {
    global $conn;

    try {
        $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
        $period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : 'current_month';

        // Set date range based on period
        switch ($period) {
            case 'today':
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
                break;
            case 'this_week':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'current_month':
            default:
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                break;
        }

        $branchCondition = '';
        if (!empty($branch)) {
            $branchCondition = " AND branch = '" . $conn->real_escape_string($branch) . "'";
        }

        // Sales for the period
        $salesResult = $conn->query("SELECT COUNT(*) as total_sales, SUM(amount) as total_amount FROM spareparts_out WHERE sale_date BETWEEN '$startDate' AND '$endDate' $branchCondition");
        $salesData = $salesResult->fetch_assoc();

        // Payments for the period
        $paymentsResult = $conn->query("SELECT COUNT(*) as total_payments, SUM(amount) as total_amount FROM spareparts_payments WHERE payment_date BETWEEN '$startDate' AND '$endDate' $branchCondition");
        $paymentsData = $paymentsResult->fetch_assoc();

        // Outstanding balances
        $outstandingResult = $conn->query("SELECT COUNT(DISTINCT customer_name) as total_customers, SUM(balance) as total_balance FROM spareparts_out WHERE balance > 0 $branchCondition");
        $outstandingData = $outstandingResult->fetch_assoc();

        // Recent transfers (last 7 days)
        $transfersResult = $conn->query("SELECT COUNT(*) as total FROM spareparts_transfers WHERE transfer_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)" . 
            (!empty($branch) ? " AND (from_branch = '$branch' OR to_branch = '$branch')" : ""));
        $recentTransfers = $transfersResult->fetch_assoc()['total'];

        // Cash vs Installment breakdown for the period
        $cashSalesResult = $conn->query("SELECT COUNT(*) as count, SUM(amount) as amount FROM spareparts_out WHERE transaction_type = 'cash' AND sale_date BETWEEN '$startDate' AND '$endDate' $branchCondition");
        $cashSales = $cashSalesResult->fetch_assoc();

        $installmentSalesResult = $conn->query("SELECT COUNT(*) as count, SUM(amount) as amount FROM spareparts_out WHERE transaction_type = 'installment' AND sale_date BETWEEN '$startDate' AND '$endDate' $branchCondition");
        $installmentSales = $installmentSalesResult->fetch_assoc();

        // Recent activities (last 10 transactions)
        $recentActivitiesResult = $conn->query("
            (SELECT 'sale' as type, sale_date as date, part_no, customer_name as description, amount, branch FROM spareparts_out WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) $branchCondition)
            UNION ALL
            (SELECT 'payment' as type, payment_date as date, '' as part_no, customer_name as description, amount, branch FROM spareparts_payments WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) $branchCondition)
            UNION ALL
            (SELECT 'transfer' as type, transfer_date as date, part_no, CONCAT('From ', from_branch, ' to ', to_branch) as description, (quantity * cost) as amount, from_branch as branch FROM spareparts_transfers WHERE transfer_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)" . 
            (!empty($branch) ? " AND (from_branch = '$branch' OR to_branch = '$branch')" : "") . ")
            ORDER BY date DESC, type
            LIMIT 10
        ");

        $recentActivities = [];
        while ($row = $recentActivitiesResult->fetch_assoc()) {
            $recentActivities[] = [
                'type' => $row['type'],
                'date' => $row['date'],
                'part_no' => $row['part_no'],
                'description' => $row['description'],
                'amount' => floatval($row['amount']),
                'branch' => $row['branch']
            ];
        }

        sendJsonResponse([
            'success' => true,
            'data' => [
                'sales' => [
                    'total_sales' => intval($salesData['total_sales'] ?? 0),
                    'total_amount' => floatval($salesData['total_amount'] ?? 0),
                    'cash_sales' => [
                        'count' => intval($cashSales['count'] ?? 0),
                        'amount' => floatval($cashSales['amount'] ?? 0)
                    ],
                    'installment_sales' => [
                        'count' => intval($installmentSales['count'] ?? 0),
                        'amount' => floatval($installmentSales['amount'] ?? 0)
                    ]
                ],
                'payments' => [
                    'total_payments' => intval($paymentsData['total_payments'] ?? 0),
                    'total_amount' => floatval($paymentsData['total_amount'] ?? 0)
                ],
                'outstanding' => [
                    'total_customers' => intval($outstandingData['total_customers'] ?? 0),
                    'total_balance' => floatval($outstandingData['total_balance'] ?? 0)
                ],
                'transfers' => [
                    'recent_count' => intval($recentTransfers)
                ],
                'recent_activities' => $recentActivities
            ],
            'period' => $period,
            'branch' => $branch,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false, 
            'message' => 'Error fetching dashboard stats: ' . $e->getMessage()
        ]);
    }
}

?>
