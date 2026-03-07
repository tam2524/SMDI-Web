<?php
header('Content-Type: application/json');
require_once 'db_config.php'; // Ensure you have session_start() in this file

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['position'] ?? $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts'];
$isAdmin = in_array(strtolower(trim($userRole)), array_map('strtolower', $adminRoles));
$canDelete = $isAdmin;

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    // READ
    case 'get_dashboard_stats':
        getDashboardStats();
        break;
    case 'get_inventory_list':
        getInventoryList();
        break;
    case 'get_sales_list':
        getSalesList();
        break;
    case 'get_sale_details':
        getSaleDetails();
        break;
    case 'get_payments_list':
        getPaymentsList();
        break;
    case 'get_payment_history':
        getPaymentHistory();
        break;
    case 'get_transfers_list':
        getTransfersList();
        break;
    case 'search_inventory_parts':
        searchInventoryParts();
        break;
    case 'get_global_transfers': // NEW
        getGlobalTransfers();
        break;
    case 'get_activity_log':
        getActivityLog();
        break;
    case 'search_customer_accounts':
        searchCustomerAccounts();
        break;
    case 'get_incoming_transfers':
        getIncomingTransfers();
        break;
    case 'get_incoming_count':
        getIncomingCount();
        break;
    case 'get_aging_summary':
        getAgingSummary();
        break;
    case 'get_customer_ledger':
        getCustomerLedger();
        break;
    case 'get_inventory_history':
        getInventoryHistory();
        break; // NEW
    case 'generate_inventory_report':
        generateInventoryReport();
        break;
    case 'get_spareparts_branches':
        getSparepartsBranches();
        break;
    case 'generate_sales_summary_report':
        generateSalesSummaryReport();
        break;
    case 'generate_transfer_report':
        generateTransferReport();
        break;

    // CUD
    case 'add_multiple_parts_in':
        addMultiplePartsIn();
        break;
    case 'sell_multiple_parts_out':
        sellMultiplePartsOut();
        break;
    case 'record_payment':
        recordPayment();
        break;
    case 'transfer_multiple_parts':
        transferMultipleParts();
        break;
    case 'accept_transfer':
        acceptTransfer();
        break;
    case 'get_incoming_transfers_detailed':
        getIncomingTransfersDetailed();
        break;
    case 'batch_receive_transfers':
        batchReceiveTransfers();
        break;
    case 'batch_reject_transfers':
        batchRejectTransfers();
        break;
    case 'edit_parts':
        editPart();
        break;
    case 'edit_sale':
        editSale();
        break;
    case 'search_parts_global':
        searchPartsGlobal();
        break;
    case 'edit_payment':
        editPayment();
        break; // NEW

    // DELETE (WITH AUTH)
    case 'delete_part':
        deleteItem('part');
        break;
    case 'delete_sale':
        deleteItem('sale');
        break;
    case 'delete_payment':
        deleteItem('payment');
        break;
    case 'delete_transfer':
        cancelTransfer();
        break;
    case 'reject_transfer':
        rejectTransfer();
        break;
    case 'get_transfer_details':
        getTransferDetails();
        break;
    case 'delete_monthly_snapshot':
    case 'revert_transaction':
        echo json_encode(['success' => false, 'message' => 'End point exists but is incomplete or restricted.']);
        break;
    case 'search_inventory':
        searchInventory();
        break;
    case 'check_or_exists':
        checkOrExists();
        break;
    case 'search_unique_customers':
        searchUniqueCustomers();
        break;
    case 'get_branches':
        getBranches();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

// ===================================================================
// ============================= READ DATA ===========================
// ===================================================================
function getBranches()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';

    if (!$seeAll) {
        // Only allow branch users to see Head Office (for transfers)
        echo json_encode(['success' => true, 'data' => ['HEADOFFICE']]);
        return;
    }

    $sql = "SELECT DISTINCT branch FROM users WHERE position = 'Spareparts-Branch' AND branch IS NOT NULL AND branch != '' ORDER BY branch ASC";
    $result = $conn->query($sql);

    $branches = ['HEADOFFICE'];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['branch'] !== 'HEADOFFICE') {
                $branches[] = $row['branch'];
            }
        }
    }
    echo json_encode(['success' => true, 'data' => $branches]);
}

function getActivityLog()
{
    global $conn, $currentBranch;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 25;
    $offset = ($page - 1) * $limit;

    $where = "WHERE al.table_name LIKE 'spareparts_%'";
    $params = [];
    $types = "";

    if (!empty($query)) {
        $searchTerm = "%$query%";

        $where .= " AND (al.action_details LIKE ? OR u.username LIKE ? OR al.action_type LIKE ? OR al.table_name LIKE ?)";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = "ssss";
    }

    // Count Total
    $countSql = "SELECT COUNT(al.id) as total 
                 FROM audit_log al 
                 LEFT JOIN users u ON al.user_id = u.id 
                 $where";

    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    $countStmt->close();

    // Fetch Data
    $sql = "SELECT al.id, al.action_timestamp, al.action_type, al.table_name, al.record_id, al.action_details, COALESCE(u.username, 'HO') as username
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            $where
            ORDER BY al.action_timestamp DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalRecords,
            'itemsPerPage' => $limit
        ]
    ]);
}

function getDashboardStats()
{
    global $conn, $currentBranch, $isAdmin;
    $stats = [];

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $whereInv = $seeAll ? "" : "WHERE current_branch = ?";
    $whereTxOut = $seeAll ? "WHERE type = 'OUT'" : "WHERE type = 'OUT' AND from_location = ?";
    $whereAging = $seeAll ? "WHERE status = 'Active'" : "WHERE branch = ? AND status = 'Active'";

    // Total Qty
    $stmt = $conn->prepare("SELECT SUM(current_stock) as total FROM spareparts_inventory $whereInv");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['total_quantity'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Total Value
    $stmt = $conn->prepare("SELECT SUM(current_stock * cost) as total FROM spareparts_inventory $whereInv");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['total_value'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Monthly Sales
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM spareparts_transactions $whereTxOut AND MONTH(transaction_date) = MONTH(CURDATE()) AND YEAR(transaction_date) = YEAR(CURDATE())");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['monthly_sales'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Yearly Sales
    $stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM spareparts_transactions $whereTxOut AND YEAR(transaction_date) = YEAR(CURDATE())");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['yearly_sales'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Outstanding Balance
    $stmt = $conn->prepare("SELECT SUM(balance) as total FROM spareparts_aging $whereAging");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['outstanding_balance'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Total Accounts
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM spareparts_aging $whereAging");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $stats['total_accounts'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;

    echo json_encode(['success' => true, 'data' => $stats]);
}

function getInventoryList()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $where = $seeAll ? "" : "WHERE current_branch = ?";
    $stmt = $conn->prepare("SELECT * FROM spareparts_inventory $where ORDER BY part_no ASC");
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['invoice_no'] = $row['invoice_no'] ?? '';
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function searchInventory()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';

    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }

    $searchTerm = "%$query%";
    $whereClause = $seeAll ? "WHERE part_no LIKE ? OR description LIKE ?" : "WHERE (part_no LIKE ? OR description LIKE ?) AND current_branch = ?";

    $stmt = $conn->prepare("SELECT * FROM spareparts_inventory $whereClause ORDER BY part_no ASC LIMIT 50");

    if ($seeAll) {
        $stmt->bind_param('ss', $searchTerm, $searchTerm);
    } else {
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $currentBranch);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSalesList()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $where = $seeAll ? "t.type = 'OUT'" : "t.type = 'OUT' AND t.from_location = ?";
    $sql = "SELECT t.id, t.transaction_date as sale_date, t.customer_name, t.or_number, t.transaction_type, SUM(t.total_amount) as total_amount, a.balance, t.from_location
            FROM spareparts_transactions t
            LEFT JOIN spareparts_aging a ON t.or_number = a.or_number AND t.from_location = a.branch
            WHERE $where
            GROUP BY t.or_number, t.from_location
            ORDER BY t.id DESC";
    $stmt = $conn->prepare($sql);
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSaleDetails()
{
    global $conn;
    $or_number = sanitizeInput($_GET['or_number'] ?? '');
    $branch = sanitizeInput($_GET['branch'] ?? '');

    if (empty($or_number) || empty($branch)) {
        echo json_encode(['success' => false, 'message' => 'Missing OR number or branch.']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
    $stmt->bind_param('ss', $or_number, $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    $customer_name = "";
    $sale_date = "";
    $transaction_type = "";
    $total_amount = 0;

    while ($row = $result->fetch_assoc()) {
        $row['description'] = $row['description'] ?? ''; // Handle old records
        $items[] = $row;
        $customer_name = $row['customer_name'];
        $sale_date = $row['transaction_date'];
        $transaction_type = $row['transaction_type'];
        $total_amount += (float) $row['total_amount'];
    }

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Sale not found.']);
        return;
    }

    // Get balance if charge
    $balance = 0;
    $agingStmt = $conn->prepare("SELECT balance FROM spareparts_aging WHERE or_number = ? AND branch = ?");
    $agingStmt->bind_param('ss', $or_number, $branch);
    $agingStmt->execute();
    $agingRes = $agingStmt->get_result()->fetch_assoc();
    if ($agingRes) {
        $balance = (float) $agingRes['balance'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'or_number' => $or_number,
            'customer_name' => $customer_name,
            'sale_date' => $sale_date,
            'transaction_type' => $transaction_type,
            'total_amount' => $total_amount,
            'balance' => $balance,
            'branch' => $branch,
            'items' => $items
        ]
    ]);
}

function getTransfersList()
{
    global $conn, $currentBranch, $isAdmin;

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $where = $seeAll ? "1=1" : "st.from_branch = ?";
    $sql = "SELECT st.id, st.from_branch, st.to_branch, st.transfer_date, st.status, 
                   SUM(sti.quantity) as item_count 
            FROM spareparts_transfers st
            LEFT JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
            WHERE $where
            GROUP BY st.id
            ORDER BY st.transfer_date DESC, st.id DESC";

    $stmt = $conn->prepare($sql);
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getPaymentsList()
{
    global $conn, $currentBranch, $isAdmin;
    $period = sanitizeInput($_REQUEST['period'] ?? 'all');
    $date_val = sanitizeInput($_REQUEST['date_value'] ?? $_REQUEST['date_val'] ?? '');
    $branch = sanitizeInput($_REQUEST['branch'] ?? 'all');
    $customer = sanitizeInput($_REQUEST['customer'] ?? '');

    $whereClauses = ["t.type = 'PAYMENT'"];
    $params = [];
    $types = "";

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';

    if (!$seeAll) {
        $whereClauses[] = "t.from_location = ?";
        $params[] = $currentBranch;
        $types .= "s";
    } elseif ($branch !== 'all') {
        $whereClauses[] = "t.from_location = ?";
        $params[] = $branch;
        $types .= "s";
    }

    if (!empty($customer)) {
        $whereClauses[] = "t.customer_name LIKE ?";
        $params[] = "%$customer%";
        $types .= "s";
    }

    if ($period !== 'all' && !empty($date_val)) {
        if ($period === 'daily') {
            $whereClauses[] = "DATE(t.transaction_date) = ?";
            $params[] = $date_val;
            $types .= "s";
        } elseif ($period === 'monthly') {
            $whereClauses[] = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
            $params[] = $date_val;
            $types .= "s";
        } elseif ($period === 'yearly') {
            $whereClauses[] = "YEAR(t.transaction_date) = ?";
            $params[] = $date_val;
            $types .= "s";
        }
    }

    $whereStr = implode(" AND ", $whereClauses);
    $stmt = $conn->prepare("SELECT t.id, t.transaction_date, t.customer_name, t.total_amount as amount, t.or_number, t.from_location, t.transaction_type 
                            FROM spareparts_transactions t 
                            WHERE $whereStr 
                            ORDER BY t.id DESC");

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getIncomingTransfers()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';

    // Get transfers waiting for acceptance (to this specific location)
    $where = $seeAll ? "st.status = 'In-Transit'" : "st.to_branch = ? AND st.status = 'In-Transit'";
    $sql = "SELECT st.id, st.from_branch, st.to_branch, st.transfer_date, st.status, 
                   SUM(sti.quantity) as item_count 
            FROM spareparts_transfers st
            LEFT JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
            WHERE $where
            GROUP BY st.id
            ORDER BY st.transfer_date DESC, st.id DESC";

    $stmt = $conn->prepare($sql);
    if (!$seeAll)
        $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getIncomingCount()
{
    global $conn, $currentBranch;
    // Notify only if something is transferred TO this specific location (Branch or Head Office)
    $sql = "SELECT COUNT(*) as count FROM spareparts_transfers WHERE to_branch = ? AND status = 'In-Transit'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'count' => (int) ($row['count'] ?? 0)]);
}


function getGlobalTransfers()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';

    $where = $seeAll ? "" : "WHERE st.from_branch = ? OR st.to_branch = ?";
    $sql = "SELECT st.id, st.from_branch, st.to_branch, st.transfer_date, st.received_date, st.status, 
                   SUM(sti.quantity) as item_count 
            FROM spareparts_transfers st
            LEFT JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
            $where
            GROUP BY st.id
            ORDER BY st.transfer_date DESC, st.id DESC";

    $stmt = $conn->prepare($sql);
    if (!$seeAll) {
        $stmt->bind_param('ss', $currentBranch, $currentBranch);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}


function searchCustomerAccounts()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";

    $whereClause = $seeAll ? "WHERE (customer_name LIKE ? OR or_number LIKE ?)" : "WHERE (customer_name LIKE ? OR or_number LIKE ?) AND branch = ?";

    $stmt = $conn->prepare("SELECT customer_name, SUM(balance) as total_balance, GROUP_CONCAT(or_number) as or_numbers, branch 
                            FROM spareparts_aging 
                            $whereClause AND status = 'Active' AND balance > 0
                            GROUP BY customer_name, branch 
                            LIMIT 10");

    if ($seeAll) {
        $stmt->bind_param('ss', $searchTerm, $searchTerm);
    } else {
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $currentBranch);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function searchUniqueCustomers()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";

    $whereBranch = $seeAll ? "" : "AND from_location = ?";

    // Search in transactions to find historical customers
    $stmt = $conn->prepare("SELECT DISTINCT customer_name FROM spareparts_transactions WHERE customer_name LIKE ? $whereBranch LIMIT 5");

    if ($seeAll) {
        $stmt->bind_param('s', $searchTerm);
    } else {
        $stmt->bind_param('ss', $searchTerm, $currentBranch);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row['customer_name'];
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function checkOrExists()
{
    global $conn;
    $or_number = sanitizeInput($_GET['or_number'] ?? '');
    if (empty($or_number)) {
        echo json_encode(['exists' => false]);
        return;
    }

    $stmt = $conn->prepare("SELECT or_number FROM spareparts_transactions WHERE or_number = ? LIMIT 1");
    $stmt->bind_param('s', $or_number);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(['exists' => $result->num_rows > 0]);
}


function getInventoryHistory()
{
    global $conn, $currentBranch;
    $id = sanitizeInput($_GET['id'] ?? '');
    $part_no = sanitizeInput($_GET['part_no'] ?? '');

    $sql = "SELECT transaction_date, or_number, part_no, description, customer_name, type, quantity, price, total_amount, from_location, to_location, status, reason 
            FROM spareparts_transactions 
            WHERE 1=1 ";

    $params = [];
    $types = "";

    $targetBranch = $currentBranch;

    // If ID is provided, we might want to find the part_no first if not provided
    if ($id) {
        $partQuery = "SELECT part_no, current_branch FROM spareparts_inventory WHERE id = ?";
        $pStmt = $conn->prepare($partQuery);
        $pStmt->bind_param('i', $id);
        $pStmt->execute();
        $partResult = $pStmt->get_result()->fetch_assoc();
        if (!$part_no) {
            $part_no = $partResult['part_no'] ?? '';
        }
        $targetBranch = $partResult['current_branch'] ?? $currentBranch;
        $pStmt->close();
    }

    if ($part_no) {
        $sql .= " AND part_no = ? ";
        $params[] = $part_no;
        $types .= "s";
    }

    // Filter history specifically for the target branch
    $sql .= " AND (from_location = ? OR (to_location = ? AND type IN ('TRANSFER_IN', 'IN', 'TRANSFER_OUT'))) ";
    $params[] = $targetBranch;
    $params[] = $targetBranch;
    $types .= "ss";

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

// ===================================================================
// ========================= CUD OPERATIONS ==========================
// ===================================================================

function transferMultipleParts()
{
    global $conn, $currentBranch;
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $items = json_decode($_POST['items'], true);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items selected for transfer.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $transferStmt = $conn->prepare("INSERT INTO spareparts_transfers (from_branch, to_branch, transfer_date, status) VALUES (?, ?, ?, 'In-Transit')");
        $transferStmt->bind_param('sss', $currentBranch, $toBranch, $transferDate);
        if (!$transferStmt->execute())
            throw new Exception('Failed to create transfer record.');
        $transferId = $conn->insert_id;

        foreach ($items as $item) {
            $pno = sanitizeInput($item['part_no']);
            $qty = (int) $item['quantity'];
            $desc = sanitizeInput($item['description']);

            $costStmt = $conn->prepare("SELECT cost FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
            $costStmt->bind_param('ss', $pno, $currentBranch);
            $costStmt->execute();
            $costRow = $costStmt->get_result()->fetch_assoc();
            $cost = $costRow ? (float) $costRow['cost'] : 0;

            $updStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
            $updStmt->bind_param('iss', $qty, $pno, $currentBranch);
            $updStmt->execute();

            $liStmt = $conn->prepare("INSERT INTO spareparts_transfer_items (transfer_id, part_no, description, quantity, cost) VALUES (?, ?, ?, ?, ?)");
            $liStmt->bind_param('issid', $transferId, $pno, $desc, $qty, $cost);
            $liStmt->execute();

            $txStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status) 
                                     VALUES (?, 'TRANSFER_OUT', ?, ?, ?, ?, ?, ?, ?, 'In-Transit')");
            $total_cost = $qty * $cost;
            $txStmt->bind_param('sssiddss', $transferDate, $pno, $desc, $qty, $cost, $total_cost, $currentBranch, $toBranch);
            $txStmt->execute();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer initiated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()]);
    }
}

function getTransferDetails()
{
    global $conn, $currentBranch, $isAdmin;
    $transfer_id = (int) $_GET['id'];

    $sql = "SELECT t.*, 
            (SELECT COUNT(*) FROM spareparts_transfer_items WHERE transfer_id = t.id) as item_count 
            FROM spareparts_transfers t 
            WHERE t.id = ?";

    if (!$isAdmin) {
        $sql .= " AND (t.from_branch = ? OR t.to_branch = ?)";
    }

    $stmt = $conn->prepare($sql);
    if ($isAdmin) {
        $stmt->bind_param('i', $transfer_id);
    } else {
        $stmt->bind_param('iss', $transfer_id, $currentBranch, $currentBranch);
    }

    $stmt->execute();
    $transfer = $stmt->get_result()->fetch_assoc();

    if (!$transfer) {
        echo json_encode(['success' => false, 'message' => 'Transfer not found or access denied.']);
        return;
    }

    $itemStmt = $conn->prepare("SELECT part_no, description, quantity, cost FROM spareparts_transfer_items WHERE transfer_id = ?");
    $itemStmt->bind_param('i', $transfer_id);
    $itemStmt->execute();
    $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $transfer['items'] = $items;

    echo json_encode(['success' => true, 'data' => $transfer]);
}

function cancelTransfer()
{
    global $conn, $currentBranch, $isAdmin;
    $transfer_id = (int) $_POST['id'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM spareparts_transfers WHERE id = ?");
        $stmt->bind_param('i', $transfer_id);
        $stmt->execute();
        $transfer = $stmt->get_result()->fetch_assoc();

        if (!$transfer)
            throw new Exception("Transfer record not found.");
        if ($transfer['status'] !== 'In-Transit')
            throw new Exception("Only In-Transit transfers can be cancelled.");
        if (!$isAdmin && $transfer['from_branch'] !== $currentBranch)
            throw new Exception("Access denied.");

        // Mark transfer as cancelled
        $upd = $conn->prepare("UPDATE spareparts_transfers SET status = 'Cancelled' WHERE id = ?");
        $upd->bind_param('i', $transfer_id);
        $upd->execute();

        // Get items and return to stock
        $itemStmt = $conn->prepare("SELECT * FROM spareparts_transfer_items WHERE transfer_id = ?");
        $itemStmt->bind_param('i', $transfer_id);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($items as $item) {
            $pno = $item['part_no'];
            $qty = (int) $item['quantity'];

            // Return stock
            $ret = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
            $ret->bind_param('iss', $qty, $pno, $transfer['from_branch']);
            $ret->execute();

            // Log cancellation
            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, from_location, to_location, status, reason) 
                                   VALUES (CURDATE(), 'ADJUSTMENT', ?, ?, ?, ?, ?, 'Cancelled', 'Transfer Cancelled')");
            $log->bind_param('ssiss', $pno, $item['description'], $qty, $transfer['to_branch'], $transfer['from_branch']);
            $log->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer cancelled successfully. Items returned to inventory.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to cancel transfer: ' . $e->getMessage()]);
    }
}

function acceptTransfer()
{
    global $conn, $currentBranch;
    $transferId = (int) $_POST['transfer_id'];

    $conn->begin_transaction();
    try {
        // Fetch transfer record to get from_branch
        $tStmt = $conn->prepare("SELECT from_branch FROM spareparts_transfers WHERE id = ?");
        $tStmt->bind_param('i', $transferId);
        $tStmt->execute();
        $tRow = $tStmt->get_result()->fetch_assoc();
        if (!$tRow)
            throw new Exception("Transfer not found.");
        $from_branch = $tRow['from_branch'];

        // Fetch transfer items
        $itemStmt = $conn->prepare("SELECT * FROM spareparts_transfer_items WHERE transfer_id = ?");
        $itemStmt->bind_param('i', $transferId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($items))
            throw new Exception("No items found for this transfer.");

        foreach ($items as $item) {
            $part_no = sanitizeInput($item['part_no']);
            $description = sanitizeInput($item['description']);
            $quantity = (int) $item['quantity'];
            $cost = (float) $item['cost'];

            // Fetch missing details from origin branch
            $origStmt = $conn->prepare("SELECT brand, price, min_stock FROM spareparts_inventory WHERE part_no = ? AND current_branch = ? LIMIT 1");
            $origStmt->bind_param('ss', $part_no, $from_branch);
            $origStmt->execute();
            $origRow = $origStmt->get_result()->fetch_assoc();

            $brand = $origRow ? $origRow['brand'] : '';
            $price = $origRow ? (float) $origRow['price'] : 0.00;
            $min_stock = $origRow ? (int) $origRow['min_stock'] : 5;

            // Add/Update stock in the destination branch
            $stmt = $conn->prepare("INSERT INTO spareparts_inventory (brand, part_no, description, current_stock, cost, price, min_stock, current_branch) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE 
                                    current_stock = current_stock + VALUES(current_stock), 
                                    cost = (cost + VALUES(cost)) / 2,
                                    price = VALUES(price),
                                    brand = VALUES(brand)");
            $stmt->bind_param('sssidds', $brand, $part_no, $description, $quantity, $cost, $price, $min_stock, $currentBranch);
            $stmt->execute();

            // Log TRANSFER_IN
            $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status) 
                                       VALUES (CURDATE(), 'TRANSFER_IN', ?, ?, ?, ?, ?, ?, ?, 'Completed')");
            $total_amount = $quantity * $cost;
            $logStmt->bind_param('ssiddss', $part_no, $description, $quantity, $cost, $total_amount, $from_branch, $currentBranch);
            $logStmt->execute();
        }

        // Update transfer status
        $updateStmt = $conn->prepare("UPDATE spareparts_transfers SET status = 'Completed', received_date = NOW() WHERE id = ? AND to_branch = ?");
        $updateStmt->bind_param('is', $transferId, $currentBranch);
        $updateStmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer accepted and items added to inventory.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to accept transfer: ' . $e->getMessage()]);
    }
}

function rejectTransfer()
{
    global $conn, $currentBranch;
    $transferId = (int) $_POST['transfer_id'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT * FROM spareparts_transfers WHERE id = ? AND to_branch = ? AND status = 'In-Transit'");
        $stmt->bind_param('is', $transferId, $currentBranch);
        $stmt->execute();
        $transfer = $stmt->get_result()->fetch_assoc();

        if (!$transfer) {
            throw new Exception("Transfer record not found or not in-transit.");
        }

        // Mark transfer as Rejected
        $upd = $conn->prepare("UPDATE spareparts_transfers SET status = 'Rejected', received_date = NOW() WHERE id = ?");
        $upd->bind_param('i', $transferId);
        $upd->execute();

        // Get items and return to origin stock
        $itemStmt = $conn->prepare("SELECT * FROM spareparts_transfer_items WHERE transfer_id = ?");
        $itemStmt->bind_param('i', $transferId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($items as $item) {
            $pno = $item['part_no'];
            $qty = (int) $item['quantity'];

            // Return stock to origin
            $ret = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
            $ret->bind_param('iss', $qty, $pno, $transfer['from_branch']);
            $ret->execute();

            // Log rejection
            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, from_location, to_location, status, reason) 
                                   VALUES (CURDATE(), 'ADJUSTMENT', ?, ?, ?, ?, ?, 'Rejected', 'Transfer Rejected by Branch')");
            $log->bind_param('ssiss', $pno, $item['description'], $qty, $transfer['to_branch'], $transfer['from_branch']);
            $log->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer rejected. Items returned to origin branch stock.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to reject transfer: ' . $e->getMessage()]);
    }
}

function addMultiplePartsIn()
{
    global $conn, $currentBranch;
    $items = json_decode($_POST['items'], true);
    $date = sanitizeInput($_POST['date_in'] ?? date('Y-m-d'));
    $reference = sanitizeInput($_POST['reference_no'] ?? '');
    $invoice_no = sanitizeInput($_POST['invoice_no'] ?? '');
    $supplier = sanitizeInput($_POST['supplier_source'] ?? 'HEADOFFICE');

    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            $brand = sanitizeInput($item['brand'] ?? '');
            $part_no = sanitizeInput($item['part_no']);
            $description = sanitizeInput($item['description']);
            $quantity = (int) $item['quantity'];
            $cost = (float) $item['cost'];
            $price = (float) $item['price'];

            // Update or Insert inventory
            $stmt = $conn->prepare("INSERT INTO spareparts_inventory (brand, part_no, description, current_stock, cost, price, current_branch) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE 
                                    current_stock = current_stock + VALUES(current_stock), 
                                    cost = (cost + VALUES(cost)) / 2, 
                                    price = VALUES(price)");
            $stmt->bind_param('sssidds', $brand, $part_no, $description, $quantity, $cost, $price, $currentBranch);
            $stmt->execute();

            // Log transaction
            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, or_number, status) 
                                   VALUES (?, 'IN', ?, ?, ?, ?, ?, ?, ?, ?, 'Completed')");
            $total = $quantity * $cost;
            // from_location is supplier, to_location is branch, or_number is invoice_no
            $log->bind_param('sssiddsss', $date, $part_no, $description, $quantity, $cost, $total, $supplier, $currentBranch, $invoice_no);
            $log->execute();
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Stock added successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to add stock: ' . $e->getMessage()]);
    }
}

function sellMultiplePartsOut()
{
    global $conn, $currentBranch;
    $items = json_decode($_POST['items'], true);
    $or_number = sanitizeInput($_POST['or_number']);
    $customer_name = sanitizeInput($_POST['customer_name']);
    $date = sanitizeInput($_POST['date']);
    $transaction_type = sanitizeInput($_POST['transaction_type']);

    // Check if OR already exists
    $checkStmt = $conn->prepare("SELECT or_number FROM spareparts_transactions WHERE or_number = ? LIMIT 1");
    $checkStmt->bind_param('s', $or_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Duplicate OR: Receipt Number $or_number already exists."]);
        return;
    }

    $conn->begin_transaction();
    try {
        $total_sale_amount = 0;
        foreach ($items as $item) {
            $part_no = sanitizeInput($item['part_no']);
            $description = sanitizeInput($item['description'] ?? '');
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];
            $subtotal = $quantity * $price;
            $total_sale_amount += $subtotal;

            // Deduct stock
            $stmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
            $stmt->bind_param('iss', $quantity, $part_no, $currentBranch);
            $stmt->execute();

            if ($stmt->affected_rows === 0)
                throw new Exception("Part $part_no not found or insufficient stock.");

            // Log transaction
            $stmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, or_number, customer_name, transaction_type, type, part_no, description, quantity, price, total_amount, from_location) 
                                    VALUES (?, ?, ?, ?, 'OUT', ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssidds', $date, $or_number, $customer_name, $transaction_type, $part_no, $description, $quantity, $price, $subtotal, $currentBranch);
            $stmt->execute();
        }

        // Add to aging if charge
        if ($transaction_type === 'charge') {
            $stmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, customer_name, sale_date, total_amount, balance, branch) 
                                    VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssdds', $or_number, $customer_name, $date, $total_sale_amount, $total_sale_amount, $currentBranch);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale recorded successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to record sale: ' . $e->getMessage()]);
    }
}

function recordPayment()
{
    global $conn, $currentBranch;
    $or_number = sanitizeInput($_POST['or_number'] ?? '');
    $customer_name = sanitizeInput($_POST['customer_name'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $date = sanitizeInput($_POST['payment_date'] ?? date('Y-m-d'));
    $branch = sanitizeInput($_POST['branch'] ?? $currentBranch);
    $receipt_no = sanitizeInput($_POST['payment_receipt_no'] ?? '');
    $payment_method = sanitizeInput($_POST['payment_method'] ?? 'Cash');
    $check_number = sanitizeInput($_POST['check_number'] ?? '');
    $bank_name = sanitizeInput($_POST['bank_name'] ?? '');
    $reference_number = sanitizeInput($_POST['reference_number'] ?? '');

    if (empty($or_number) && empty($customer_name)) {
        echo json_encode(['success' => false, 'message' => 'OR Number or Customer Name is required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $remainingAmount = $amount;
        $customerToLog = $customer_name;

        // Fetch active accounts for this customer or specific OR
        $sql = "SELECT id, or_number, customer_name, balance FROM spareparts_aging WHERE status = 'Active' ";
        // If or_number contains a comma, it was a multi-selection/grouped result, so we distribute by customer name
        if (!empty($or_number) && strpos($or_number, ',') === false) {
            $sql .= " AND or_number = ? AND branch = ? ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $or_number, $branch);
        } else {
            $sql .= " AND customer_name = ? AND branch = ? ORDER BY sale_date ASC ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $customer_name, $branch);
        }
        $stmt->execute();
        $accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($accounts)) {
            throw new Exception("No active accounts found.");
        }

        foreach ($accounts as $acc) {
            if ($remainingAmount <= 0)
                break;

            if ($acc['balance'] <= 0)
                continue;

            $payAmount = min($remainingAmount, $acc['balance']);
            $remainingAmount -= $payAmount;
            $customerToLog = $acc['customer_name'];

            // Update balance
            $upd = $conn->prepare("UPDATE spareparts_aging SET balance = balance - ?, status = IF(balance - ? <= 0, 'Paid', 'Active') WHERE id = ?");
            $upd->bind_param('ddi', $payAmount, $payAmount, $acc['id']);
            $upd->execute();

            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, or_number, customer_name, type, total_amount, from_location, transaction_type, payment_method, check_number, bank_name, reference_number) 
                                    VALUES (?, ?, ?, 'PAYMENT', ?, ?, ?, ?, ?, ?, ?)");
            $log->bind_param('sssdssssss', $date, $receipt_no, $acc['customer_name'], $payAmount, $branch, $acc['or_number'], $payment_method, $check_number, $bank_name, $reference_number);
            $log->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.', 'customer_name' => $customerToLog]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()]);
    }
}

function editPayment()
{
    global $conn;
    $payment_id = (int) ($_POST['payment_id'] ?? 0);
    $new_amount = (float) ($_POST['amount'] ?? 0);
    $new_date = sanitizeInput($_POST['payment_date'] ?? '');
    $new_receipt = sanitizeInput($_POST['payment_receipt_no'] ?? '');
    $payment_method = sanitizeInput($_POST['payment_method'] ?? 'Cash');
    $check_number = sanitizeInput($_POST['check_number'] ?? '');
    $bank_name = sanitizeInput($_POST['bank_name'] ?? '');
    $reference_number = sanitizeInput($_POST['reference_number'] ?? '');

    if ($payment_id <= 0 || $new_amount <= 0 || empty($new_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment data provided.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Get original payment details
        $stmt = $conn->prepare("SELECT total_amount, customer_name, from_location, transaction_type as or_number FROM spareparts_transactions WHERE id = ? AND type = 'PAYMENT'");
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Payment record not found.");
        }

        $original = $result->fetch_assoc();
        $old_amount = (float) $original['total_amount'];
        $diff = $new_amount - $old_amount; // Positive if paying more, negative if paying less

        // Update the payment record in transactions
        $upd = $conn->prepare("UPDATE spareparts_transactions SET total_amount = ?, transaction_date = ?, or_number = ?, payment_method = ?, check_number = ?, bank_name = ?, reference_number = ? WHERE id = ?");
        $upd->bind_param('dssssssi', $new_amount, $new_date, $new_receipt, $payment_method, $check_number, $bank_name, $reference_number, $payment_id);
        $upd->execute();

        // Adjust the aging balance
        // We find the active or paid records for this specific customer & branch, ordered by latest first or specific OR if available.
        // It's tricky to know exactly which OR a payment hit if it was distributed, but transaction_type stores the OR hit.

        if (!empty($original['or_number']) && strpos($original['or_number'], ',') === false) {
            // Apply diff directly to that OR
            $agingStmt = $conn->prepare("UPDATE spareparts_aging SET balance = balance - ?, status = IF(balance - ? <= 0, 'Paid', 'Active') WHERE or_number = ? AND branch = ? AND customer_name = ?");
            $agingStmt->bind_param('ddsss', $diff, $diff, $original['or_number'], $original['from_location'], $original['customer_name']);
            $agingStmt->execute();
        } else {
            // If it was a generic payment distributed across multiple ORs, we apply the diff to the oldest active/paid account
            // This is a simplification but works for correcting minor amounts.
            $agingStmt = $conn->prepare("SELECT id, balance FROM spareparts_aging WHERE customer_name = ? AND branch = ? ORDER BY sale_date DESC LIMIT 1");
            $agingStmt->bind_param('ss', $original['customer_name'], $original['from_location']);
            $agingStmt->execute();
            $agingRes = $agingStmt->get_result();
            if ($agingRes->num_rows > 0) {
                $acc = $agingRes->fetch_assoc();
                $updAging = $conn->prepare("UPDATE spareparts_aging SET balance = balance - ?, status = IF(balance - ? <= 0, 'Paid', 'Active') WHERE id = ?");
                $updAging->bind_param('ddi', $diff, $diff, $acc['id']);
                $updAging->execute();
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment edited successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to edit payment: ' . $e->getMessage()]);
    }
}

function getPaymentHistory()
{
    global $conn, $currentBranch;
    $ref_or = sanitizeInput($_GET['or_number'] ?? '');
    $branch = sanitizeInput($_GET['branch'] ?? $currentBranch);

    $stmt = $conn->prepare("SELECT transaction_date, total_amount as amount, or_number as receipt_no 
                            FROM spareparts_transactions 
                            WHERE type = 'PAYMENT' AND transaction_type = ? AND from_location = ?
                            ORDER BY transaction_date DESC");
    $stmt->bind_param('ss', $ref_or, $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function editPart()
{
    global $conn, $currentBranch, $isAdmin;
    $id = (int) $_POST['id'];
    $description = sanitizeInput($_POST['description']);
    $cost = (float) $_POST['cost'];
    $price = (float) $_POST['price'];
    $min_stock = (int) $_POST['min_stock'];
    $invoice_no = sanitizeInput($_POST['invoice_no'] ?? '');

    $brand = sanitizeInput($_POST['brand'] ?? '');
    $part_no = sanitizeInput($_POST['part_no'] ?? '');
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : null;
    $branch = sanitizeInput($_POST['branch'] ?? '');

    if (!$isAdmin) {
        $branch = $currentBranch;
    }

    $change_reason = sanitizeInput($_POST['change_reason'] ?? '');

    $conn->begin_transaction();
    try {
        // Fetch old data to update history and log adjustments
        $partStmt = $conn->prepare("SELECT part_no, current_stock, current_branch FROM spareparts_inventory WHERE id = ?");
        $partStmt->bind_param('i', $id);
        $partStmt->execute();
        $partRes = $partStmt->get_result()->fetch_assoc();

        $oldPNo = $partRes ? $partRes['part_no'] : $part_no;
        $oldStock = $partRes ? (int) $partRes['current_stock'] : 0;
        $originBranch = $partRes ? $partRes['current_branch'] : $branch;

        $stmt = $isAdmin
            ? $conn->prepare("UPDATE spareparts_inventory SET description = ?, cost = ?, price = ?, min_stock = ?, brand = ?, part_no = ?, current_stock = ?, current_branch = ?, invoice_no = ? WHERE id = ?")
            : $conn->prepare("UPDATE spareparts_inventory SET description = ?, cost = ?, price = ?, min_stock = ?, brand = ?, part_no = ?, current_stock = ?, invoice_no = ? WHERE id = ? AND current_branch = ?");

        if ($isAdmin) {
            $stmt->bind_param('sddississi', $description, $cost, $price, $min_stock, $brand, $part_no, $stock, $branch, $invoice_no, $id);
        } else {
            $stmt->bind_param('sddissisis', $description, $cost, $price, $min_stock, $brand, $part_no, $stock, $invoice_no, $id, $currentBranch);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to update part in inventory.");
        }

        // Log manual adjustment if stock changed
        if ($stock !== null && $stock != $oldStock) {
            $qtyDiff = $stock - $oldStock;
            $adjStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, transaction_type, type, brand, part_no, description, quantity, price, total_amount, from_location, reason) VALUES (CURDATE(), 'Manual Adjustment', 'ADJUSTMENT', ?, ?, ?, ?, ?, ?, ?, ?)");
            $tAmount = $qtyDiff * $price;
            $adjStmt->bind_param('sssiddds', $brand, $part_no, $description, $qtyDiff, $price, $tAmount, $originBranch, $change_reason);
            if (!$adjStmt->execute()) {
                throw new Exception("Failed to log inventory adjustment.");
            }
        }

        // Update history (cascading part_no, brand, description, and price)
        $updHist = $isAdmin
            ? $conn->prepare("UPDATE spareparts_transactions SET price = ?, total_amount = quantity * ?, part_no = ?, brand = ?, description = ? WHERE part_no = ?")
            : $conn->prepare("UPDATE spareparts_transactions SET price = ?, total_amount = quantity * ?, part_no = ?, brand = ?, description = ? WHERE part_no = ? AND from_location = ?");

        if ($isAdmin) {
            $updHist->bind_param('ddssss', $price, $price, $part_no, $brand, $description, $oldPNo);
        } else {
            $updHist->bind_param('ddsssss', $price, $price, $part_no, $brand, $description, $oldPNo, $currentBranch);
        }
        $updHist->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Part updated successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function editSale()
{
    global $conn, $currentBranch;
    $original_or = sanitizeInput($_POST['original_or']);
    $original_branch = sanitizeInput($_POST['original_branch']);

    $new_or = sanitizeInput($_POST['new_or_number'] ?? $original_or);
    $new_branch = sanitizeInput($_POST['from_location'] ?? $original_branch);
    $customer_name = sanitizeInput($_POST['customer_name']);
    $sale_date = sanitizeInput($_POST['sale_date']);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $transaction_type = sanitizeInput($_POST['transaction_type'] ?? 'cash');
    $reason = sanitizeInput($_POST['reason'] ?? '');

    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'A reason for revision is required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // 1. Get count and total for distribution calculation
        $stmt = $conn->prepare("SELECT COUNT(*) as item_count, SUM(total_amount) as current_total FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
        $stmt->bind_param('ss', $original_or, $original_branch);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $item_count = $res['item_count'] ?? 0;
        $old_total = $res['current_total'] ?? 0;

        if ($item_count > 0) {
            $new_per_item = $total_amount / $item_count;
            $diff = $total_amount - $old_total;

            // 2. Update transaction records (metadata AND redistributed amount)
            $stmt = $conn->prepare("UPDATE spareparts_transactions SET 
                or_number = ?, 
                from_location = ?, 
                customer_name = ?, 
                transaction_date = ?, 
                transaction_type = ?,
                total_amount = ?,
                reason = ?
                WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
            $stmt->bind_param('sssssdsss', $new_or, $new_branch, $customer_name, $sale_date, $transaction_type, $new_per_item, $reason, $original_or, $original_branch);
            $stmt->execute();
        } else {
            $diff = 0; // Should not happen if editing a valid sale
        }

        // 3. Update or Handle Aging record
        if ($transaction_type === 'charge') {
            $stmt = $conn->prepare("SELECT * FROM spareparts_aging WHERE or_number = ? AND branch = ?");
            $stmt->bind_param('ss', $original_or, $original_branch);
            $stmt->execute();
            $aging = $stmt->get_result()->fetch_assoc();

            if ($aging) {
                // Update existing aging record with new balance
                $new_balance = $aging['balance'] + $diff;
                $stmt = $conn->prepare("UPDATE spareparts_aging SET 
                    or_number = ?, 
                    branch = ?, 
                    customer_name = ?, 
                    sale_date = ?, 
                    total_amount = ?, 
                    balance = ? 
                    WHERE or_number = ? AND branch = ?");
                $stmt->bind_param('ssssddss', $new_or, $new_branch, $customer_name, $sale_date, $total_amount, $new_balance, $original_or, $original_branch);
                $stmt->execute();
            } else {
                // Was likely cash, now charge? Create aging
                $stmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, branch, customer_name, sale_date, total_amount, balance) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssdd', $new_or, $new_branch, $customer_name, $sale_date, $total_amount, $total_amount);
                $stmt->execute();
            }
        } else {
            // Delete aging if changed to cash
            $stmt = $conn->prepare("DELETE FROM spareparts_aging WHERE or_number = ? AND branch = ?");
            $stmt->bind_param('ss', $original_or, $original_branch);
            $stmt->execute();
        }

        // 4. Update payments if identification changed
        $stmt = $conn->prepare("UPDATE spareparts_payments SET or_number = ?, branch = ? WHERE or_number = ? AND branch = ?");
        $stmt->bind_param('ssss', $new_or, $new_branch, $original_or, $original_branch);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale updated successfully with all adjustments.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update sale: ' . $e->getMessage()]);
    }
}

// ===================================================================
// ========================= DELETE HANDLER ==========================
// ===================================================================

function deleteItem($type)
{
    global $conn, $canDelete, $currentBranch, $isAdmin;
    if (!$canDelete) {
        echo json_encode(['success' => false, 'message' => 'Authorization failed.']);
        return;
    }

    $id = sanitizeInput($_POST['id']);
    // For Admin, allow specifying the branch of the transaction to delete
    $targetBranch = ($isAdmin && isset($_POST['branch'])) ? sanitizeInput($_POST['branch']) : $currentBranch;

    // IMPROVEMENT: If ID is numeric for a sale, try to find the actual OR number first
    if ($type === 'sale' && is_numeric($id)) {
        $getOR = $conn->prepare("SELECT or_number, from_location FROM spareparts_transactions WHERE id = ?");
        $getOR->bind_param('i', $id);
        $getOR->execute();
        $orRes = $getOR->get_result()->fetch_assoc();
        if ($orRes) {
            $id = $orRes['or_number'];
            $targetBranch = $orRes['from_location'];
        }
    }

    $conn->begin_transaction();
    try {
        switch ($type) {
            case 'part':
                $stmt = $isAdmin
                    ? $conn->prepare("DELETE FROM spareparts_inventory WHERE id = ?")
                    : $conn->prepare("DELETE FROM spareparts_inventory WHERE id = ? AND current_branch = ?");

                if ($isAdmin) {
                    $stmt->bind_param('i', $id);
                } else {
                    $stmt->bind_param('is', $id, $currentBranch);
                }
                if (!$stmt->execute())
                    throw new Exception($stmt->error);
                if ($stmt->affected_rows === 0)
                    throw new Exception("Part not found or already deleted.");
                break;

            case 'sale': // ID is the OR number
                // Find all parts in the sale
                $findStmt = $conn->prepare("SELECT part_no, quantity FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
                $findStmt->bind_param('ss', $id, $targetBranch);
                $findStmt->execute();
                $items = $findStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                if (empty($items)) {
                    throw new Exception("Sale record (OR: $id) not found for branch $targetBranch. Operation aborted.");
                }

                // Return stock
                foreach ($items as $item) {
                    $updateStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
                    $updateStmt->bind_param('iss', $item['quantity'], $item['part_no'], $targetBranch);
                    if (!$updateStmt->execute())
                        throw new Exception("Failed to return stock for " . $item['part_no']);
                }

                // Delete sale records
                $delStmt = $conn->prepare("DELETE FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
                $delStmt->bind_param('ss', $id, $targetBranch);
                if (!$delStmt->execute())
                    throw new Exception("Failed to delete transaction log.");

                // Delete aging record
                $delAging = $conn->prepare("DELETE FROM spareparts_aging WHERE or_number = ? AND branch = ?");
                $delAging->bind_param('ss', $id, $targetBranch);
                $delAging->execute(); // It's okay if this fails (e.g., for cash sales)
                break;

            case 'payment': // ID is the transaction ID
                // Find payment details
                $findStmt = $isAdmin
                    ? $conn->prepare("SELECT total_amount, or_number, from_location FROM spareparts_transactions WHERE id = ? AND type = 'PAYMENT'")
                    : $conn->prepare("SELECT total_amount, or_number, from_location FROM spareparts_transactions WHERE id = ? AND from_location = ? AND type = 'PAYMENT'");

                if ($isAdmin) {
                    $findStmt->bind_param('i', $id);
                } else {
                    $findStmt->bind_param('is', $id, $currentBranch);
                }
                $findStmt->execute();
                $payment = $findStmt->get_result()->fetch_assoc();
                if (!$payment)
                    throw new Exception("Payment record not found.");

                $payBranch = $payment['from_location'];

                // Add amount back to balance
                $updateStmt = $conn->prepare("UPDATE spareparts_aging SET balance = balance + ? WHERE or_number = ? AND branch = ?");
                $updateStmt->bind_param('dss', $payment['total_amount'], $payment['or_number'], $payBranch);
                if (!$updateStmt->execute())
                    throw new Exception("Failed to update customer balance.");

                // Delete payment transaction
                $delStmt = $conn->prepare("DELETE FROM spareparts_transactions WHERE id = ?");
                $delStmt->bind_param('i', $id);
                if (!$delStmt->execute())
                    throw new Exception("Failed to delete payment log.");
                break;
            case 'transfer': // ID is the transfer record ID
                // Find transfer details
                $findStmt = $conn->prepare("SELECT status, from_branch FROM spareparts_transfers WHERE id = ?");
                $findStmt->bind_param('i', $id);
                $findStmt->execute();
                $transfer = $findStmt->get_result()->fetch_assoc();

                if (!$transfer)
                    throw new Exception("Transfer record not found.");
                if ($transfer['status'] !== 'In-Transit')
                    throw new Exception("Only 'In-Transit' transfers can be cancelled.");

                if (!$isAdmin && $transfer['from_branch'] !== $currentBranch)
                    throw new Exception("You can only cancel transfers initiated by your branch.");

                // Get items to return to stock
                $itemStmt = $conn->prepare("SELECT part_no, quantity FROM spareparts_transfer_items WHERE transfer_id = ?");
                $itemStmt->bind_param('i', $id);
                $itemStmt->execute();
                $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                foreach ($items as $item) {
                    $updateStock = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
                    $updateStock->bind_param('iss', $item['quantity'], $item['part_no'], $transfer['from_branch']);
                    $updateStock->execute();
                }

                // Delete or update status
                $delItems = $conn->prepare("DELETE FROM spareparts_transfer_items WHERE transfer_id = ?");
                $delItems->bind_param('i', $id);
                $delItems->execute();

                $delTransfer = $conn->prepare("DELETE FROM spareparts_transfers WHERE id = ?");
                $delTransfer->bind_param('i', $id);
                $delTransfer->execute();

                // Delete related transactions
                $delTx = $conn->prepare("DELETE FROM spareparts_transactions WHERE type = 'TRANSFER_OUT' AND from_location = ? AND transaction_date = ? AND status = 'In-Transit'");
                // This might be tricky if multiple transfers on same day, but transfer record is more specific.
                // Ideally we should have transfer_id in transactions table.
                // For now, mirroring cancelTransfer logic or similar.
                break;
                // We'll delete where type=TRANSFER_OUT and it matches our branch and transfer_id is not explicitly stored in transactions
                // But we can match on status 'In-Transit' and the specifics. 
                // A better way would be to store transfer_id in transactions. For now, we'll use a broad match.
                $delTrans = $conn->prepare("DELETE FROM spareparts_transactions WHERE type = 'TRANSFER_OUT' AND from_location = ? AND status = 'In-Transit'");
                $delTrans->bind_param('s', $currentBranch);
                $delTrans->execute();
                break;
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' deleted successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]);
    }
}

// ===================================================================
// ====================== REPORT GENERATION ==========================
// ===================================================================

function generateInventoryReport()
{
    global $conn, $currentBranch, $isAdmin;

    $type = $_POST['report_type'] ?? '';
    $period = $_POST['period'] ?? 'monthly';
    $dateVal = sanitizeInput($_POST['date_value'] ?? '');
    $brand = sanitizeInput($_POST['brand'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $part_no = sanitizeInput($_POST['part_no'] ?? '');
    $branch = sanitizeInput($_POST['branch'] ?? 'all');

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $targetBranch = $seeAll ? $branch : $currentBranch;

    $result = ['success' => false, 'message' => 'Invalid report type'];
    switch ($type) {
        case 'inventory_balance':
            $result = getInventoryBalanceReport($period, $dateVal, $targetBranch, $brand, $category, $part_no);
            break;
        case 'inventory_summary':
            $result = getInventorySummaryReport($period, $dateVal, $targetBranch, $brand, $category, $part_no);
            break;
        case 'transferred_stocks':
            $result = getInventoryMovementReport('TRANSFER_OUT', $period, $dateVal, $targetBranch, $brand, $category, $part_no);
            break;
        case 'received_stocks':
            $result = getInventoryMovementReport('TRANSFER_IN', $period, $dateVal, $targetBranch, $brand, $category, $part_no);
            break;
        case 'delivered_stocks':
            $result = getInventoryMovementReport('IN', $period, $dateVal, $targetBranch, $brand, $category, $part_no);
            break;
        default:
            $result = getLegacyInventoryReport($type, $period, $dateVal, $targetBranch, $brand);
            break;
    }
    header('Content-Type: application/json');
    echo json_encode($result);
}

function getInventoryBalanceReport($period, $dateVal, $branch, $brand, $category, $part_no)
{
    global $conn;
    $startDate = '';
    $endDate = '';

    if ($period === 'monthly') {
        $startDate = $dateVal . "-01";
        $endDate = date("Y-m-t", strtotime($startDate));
    } else { // as of date
        $startDate = "2000-01-01"; // effectively beginning of time
        $endDate = $dateVal;
    }

    $where = "1=1";
    $params = [];
    $types = "";

    if ($branch !== 'all') {
        $where .= " AND current_branch = ?";
        $params[] = $branch;
        $types .= "s";
    }
    if (!empty($brand)) {
        $where .= " AND brand LIKE ?";
        $params[] = "%$brand%";
        $types .= "s";
    }
    if (!empty($category)) {
        $where .= " AND category LIKE ?";
        $params[] = "%$category%";
        $types .= "s";
    }
    if (!empty($part_no)) {
        $where .= " AND part_no = ?";
        $params[] = $part_no;
        $types .= "s";
    }

    // Use GROUP BY to handle unique part numbers company-wide if branch is 'all', 
    // or unique per branch if filtered.
    $query = "SELECT brand, part_no, description, category FROM spareparts_inventory WHERE $where GROUP BY part_no, brand, description, category";
    $stmt = $conn->prepare($query);
    if (!empty($params))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $parts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $data = [];
    foreach ($parts as $p) {
        $pno = $p['part_no'];

        if ($branch !== 'all') {
            // BRANCH SPECIFIC BALANCE
            // 1. Beginning Balance (Transactions before startDate involving this branch as recipient or provider)
            $begStmt = $conn->prepare("SELECT SUM(CASE 
                                        WHEN (type IN ('IN', 'TRANSFER_IN') AND to_location = ?) THEN quantity 
                                        WHEN (type IN ('OUT', 'TRANSFER_OUT') AND from_location = ?) THEN -quantity 
                                        ELSE 0 END) as beg_bal 
                                       FROM spareparts_transactions 
                                       WHERE part_no = ? AND transaction_date < ?");
            $begStmt->bind_param('ssss', $branch, $branch, $pno, $startDate);
            $begStmt->execute();
            $begBal = (int) ($begStmt->get_result()->fetch_assoc()['beg_bal'] ?? 0);

            // 2. Inventory In Breakdown (Involving this branch as recipient)
            $stmtIn = $conn->prepare("SELECT type, SUM(quantity) as subtotal FROM spareparts_transactions 
                                      WHERE part_no = ? AND transaction_date BETWEEN ? AND ? AND type IN ('IN', 'TRANSFER_IN') AND to_location = ? GROUP BY type");
            $stmtIn->bind_param('ssss', $pno, $startDate, $endDate, $branch);
            $stmtIn->execute();
            $inResults = $stmtIn->get_result()->fetch_all(MYSQLI_ASSOC);
            $qtyNew = 0;
            $qtyRec = 0;
            foreach ($inResults as $ir) {
                if ($ir['type'] === 'IN')
                    $qtyNew = (int) $ir['subtotal'];
                else if ($ir['type'] === 'TRANSFER_IN')
                    $qtyRec = (int) $ir['subtotal'];
            }

            // 3. Inventory Out Breakdown (Involving this branch as provider)
            $stmtOut = $conn->prepare("SELECT type, SUM(quantity) as subtotal FROM spareparts_transactions 
                                       WHERE part_no = ? AND transaction_date BETWEEN ? AND ? AND type IN ('OUT', 'TRANSFER_OUT') AND from_location = ? GROUP BY type");
            $stmtOut->bind_param('ssss', $pno, $startDate, $endDate, $branch);
            $stmtOut->execute();
            $outResults = $stmtOut->get_result()->fetch_all(MYSQLI_ASSOC);
            $qtySold = 0;
            $qtyXfer = 0;
            foreach ($outResults as $or) {
                if ($or['type'] === 'OUT')
                    $qtySold = (int) $or['subtotal'];
                else if ($or['type'] === 'TRANSFER_OUT')
                    $qtyXfer = (int) $or['subtotal'];
            }
        } else {
            // GLOBAL CONSOLIDATED BALANCE
            // 1. Beginning Balance (Global transactions: internal transfers net to 0, only IN and OUT affect total)
            $begStmt = $conn->prepare("SELECT SUM(CASE WHEN type IN ('IN', 'TRANSFER_IN') THEN quantity ELSE -quantity END) as beg_bal 
                                       FROM spareparts_transactions 
                                       WHERE part_no = ? AND transaction_date < ?");
            $begStmt->bind_param('ss', $pno, $startDate);
            $begStmt->execute();
            $begBal = (int) ($begStmt->get_result()->fetch_assoc()['beg_bal'] ?? 0);

            // 2. Inventory In Breakdown (Globally: we show all IN and all TRANSFER_IN)
            // Note: internal transfers appear in both In and Out, netting to 0 in Ending Balance
            $stmtIn = $conn->prepare("SELECT type, SUM(quantity) as subtotal FROM spareparts_transactions 
                                      WHERE part_no = ? AND transaction_date BETWEEN ? AND ? AND type IN ('IN', 'TRANSFER_IN') GROUP BY type");
            $stmtIn->bind_param('sss', $pno, $startDate, $endDate);
            $stmtIn->execute();
            $inResults = $stmtIn->get_result()->fetch_all(MYSQLI_ASSOC);
            $qtyNew = 0;
            $qtyRec = 0;
            foreach ($inResults as $ir) {
                if ($ir['type'] === 'IN')
                    $qtyNew = (int) $ir['subtotal'];
                else if ($ir['type'] === 'TRANSFER_IN')
                    $qtyRec = (int) $ir['subtotal'];
            }

            // 3. Inventory Out Breakdown
            $stmtOut = $conn->prepare("SELECT type, SUM(quantity) as subtotal FROM spareparts_transactions 
                                       WHERE part_no = ? AND transaction_date BETWEEN ? AND ? AND type IN ('OUT', 'TRANSFER_OUT') GROUP BY type");
            $stmtOut->bind_param('sss', $pno, $startDate, $endDate);
            $stmtOut->execute();
            $outResults = $stmtOut->get_result()->fetch_all(MYSQLI_ASSOC);
            $qtySold = 0;
            $qtyXfer = 0;
            foreach ($outResults as $or) {
                if ($or['type'] === 'OUT')
                    $qtySold = (int) $or['subtotal'];
                else if ($or['type'] === 'TRANSFER_OUT')
                    $qtyXfer = (int) $or['subtotal'];
            }
        }

        $endBal = $begBal + ($qtyNew + $qtyRec) - ($qtySold + $qtyXfer);

        $data[] = array_merge($p, [
            'beginning_balance' => $begBal,
            'received_transfers' => $qtyRec,
            'new_deliveries' => $qtyNew,
            'inventory_in' => $qtyNew + $qtyRec,
            'transfers_out' => $qtyXfer,
            'sold_during_month' => $qtySold,
            'inventory_out' => $qtySold + $qtyXfer,
            'ending_balance' => $endBal
        ]);
    }

    $summary = [
        'total_beg' => array_sum(array_column($data, 'beginning_balance')),
        'total_received' => array_sum(array_column($data, 'received_transfers')),
        'total_new' => array_sum(array_column($data, 'new_deliveries')),
        'total_in' => array_sum(array_column($data, 'inventory_in')),
        'total_transfers_out' => array_sum(array_column($data, 'transfers_out')),
        'total_sold' => array_sum(array_column($data, 'sold_during_month')),
        'total_out' => array_sum(array_column($data, 'inventory_out')),
        'total_end' => array_sum(array_column($data, 'ending_balance')),
        'brand_summary' => []
    ];

    // Compute brand summary
    $brands = [];
    foreach ($data as $row) {
        $b = $row['brand'] ?: 'Unknown';
        if (!isset($brands[$b]))
            $brands[$b] = 0;
        $brands[$b] += $row['ending_balance'];
    }
    foreach ($brands as $name => $qty) {
        $summary['brand_summary'][] = ['brand' => $name, 'quantity' => $qty];
    }

    return ['success' => true, 'data' => $data, 'summary' => $summary];
}

function getInventorySummaryReport($period, $dateVal, $branch, $brand, $category, $part_no)
{
    global $conn;

    // Date Logic (same as Balance Report)
    if ($period === 'monthly') {
        $startDate = $dateVal . "-01";
        $endDate = date("Y-m-t", strtotime($startDate));
    } else if ($period === 'as_of') {
        $startDate = "1970-01-01";
        $endDate = $dateVal;
    } else { // daily or default
        $startDate = $dateVal;
        $endDate = $dateVal;
    }

    $where = "i.current_stock >= 0"; // Show all tracked items
    $params = [];
    $types = "";

    if ($branch !== 'all') {
        $where .= " AND i.current_branch = ?";
        $params[] = $branch;
        $types .= "s";
    }
    if (!empty($brand)) {
        $where .= " AND i.brand LIKE ?";
        $params[] = "%$brand%";
        $types .= "s";
    }
    if (!empty($category)) {
        $where .= " AND i.category LIKE ?";
        $params[] = "%$category%";
        $types .= "s";
    }
    if (!empty($part_no)) {
        $where .= " AND i.part_no = ?";
        $params[] = $part_no;
        $types .= "s";
    }

    // Matrix Query: Group by Branch and Part
    $query = "SELECT i.current_branch as branch, i.part_no, i.description, 
                     SUM(i.current_stock) as total_qty, 
                     SUM(i.current_stock * i.cost) as total_value
              FROM spareparts_inventory i
              WHERE $where
              GROUP BY i.current_branch, i.part_no, i.description
              ORDER BY i.current_branch, i.part_no";

    $stmt = $conn->prepare($query);
    if (!empty($params))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $matrixData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $branches = array_unique(array_column($matrixData, 'branch'));
    $parts = [];
    foreach ($matrixData as $row) {
        $parts[$row['part_no']] = $row['description'];
    }
    sort($branches);
    ksort($parts);

    // Calculate Global Movement Totals for the Period
    // 1. Beginning Balance
    $begTotal = 0;
    if ($branch !== 'all') {
        $begQuery = "SELECT SUM(CASE 
                        WHEN (type IN ('IN', 'TRANSFER_IN') AND to_location = ?) THEN quantity 
                        WHEN (type IN ('OUT', 'TRANSFER_OUT') AND from_location = ?) THEN -quantity 
                        ELSE 0 END) as beg_bal 
                    FROM spareparts_transactions t
                    JOIN spareparts_inventory i ON t.part_no = i.part_no
                    WHERE t.transaction_date < ? AND $where";
        $begParams = array_merge([$branch, $branch, $startDate], $params);
        $begTypes = "sss" . $types;
        $begStmt = $conn->prepare($begQuery);
        $begStmt->bind_param($begTypes, ...$begParams);
    } else {
        $begQuery = "SELECT SUM(CASE WHEN type IN ('IN', 'TRANSFER_IN') THEN quantity ELSE -quantity END) as beg_bal 
                    FROM spareparts_transactions t
                    JOIN spareparts_inventory i ON t.part_no = i.part_no
                    WHERE t.transaction_date < ? AND $where";
        $begParams = array_merge([$startDate], $params);
        $begTypes = "s" . $types;
        $begStmt = $conn->prepare($begQuery);
        $begStmt->bind_param($begTypes, ...$begParams);
    }
    $begStmt->execute();
    $begTotal = (int) ($begStmt->get_result()->fetch_assoc()['beg_bal'] ?? 0);

    // 2. Movements In
    $inRec = 0;
    $inNew = 0;
    $moveWhere = ($branch !== 'all') ? "AND t.to_location = ?" : "";
    $moveInQuery = "SELECT t.type, SUM(t.quantity) as subtotal FROM spareparts_transactions t
                    JOIN spareparts_inventory i ON t.part_no = i.part_no
                    WHERE t.transaction_date BETWEEN ? AND ? AND t.type IN ('IN', 'TRANSFER_IN') AND $where $moveWhere
                    GROUP BY t.type";
    $moveInParams = ($branch !== 'all') ? array_merge([$startDate, $endDate], $params, [$branch]) : array_merge([$startDate, $endDate], $params);
    $moveInTypes = ($branch !== 'all') ? "ss" . $types . "s" : "ss" . $types;
    $moveInStmt = $conn->prepare($moveInQuery);
    $moveInStmt->bind_param($moveInTypes, ...$moveInParams);
    $moveInStmt->execute();
    $inResults = $moveInStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($inResults as $ir) {
        if ($ir['type'] === 'IN')
            $inNew = (int) $ir['subtotal'];
        else
            $inRec = (int) $ir['subtotal'];
    }

    // 3. Movements Out
    $outSold = 0;
    $outXfer = 0;
    $moveOutWhere = ($branch !== 'all') ? "AND t.from_location = ?" : "";
    $moveOutQuery = "SELECT t.type, SUM(t.quantity) as subtotal FROM spareparts_transactions t
                     JOIN spareparts_inventory i ON t.part_no = i.part_no
                     WHERE t.transaction_date BETWEEN ? AND ? AND t.type IN ('OUT', 'TRANSFER_OUT') AND $where $moveOutWhere
                     GROUP BY t.type";
    $moveOutParams = ($branch !== 'all') ? array_merge([$startDate, $endDate], $params, [$branch]) : array_merge([$startDate, $endDate], $params);
    $moveOutTypes = ($branch !== 'all') ? "ss" . $types . "s" : "ss" . $types;
    $moveOutStmt = $conn->prepare($moveOutQuery);
    $moveOutStmt->bind_param($moveOutTypes, ...$moveOutParams);
    $moveOutStmt->execute();
    $outResults = $moveOutStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($outResults as $or) {
        if ($or['type'] === 'OUT')
            $outSold = (int) $or['subtotal'];
        else
            $outXfer = (int) $or['subtotal'];
    }

    $endTotal = $begTotal + ($inRec + $inNew) - ($outSold + $outXfer);

    // Calculate Financial Values for Summary (Sum of Qty * Cost)
    // We construct the value query based on branch selection to avoid parameter mismatch
    $valWhere = $where;
    $vParams = [];
    $vTypes = "";

    if ($branch !== 'all') {
        $valQuery = "SELECT 
            SUM(CASE WHEN t.transaction_date < ? AND ((t.type IN ('IN', 'TRANSFER_IN') AND t.to_location = ?) OR (t.type IN ('OUT', 'TRANSFER_OUT') AND t.from_location = ?)) THEN t.quantity * i.cost ELSE 0 END) as beg_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'IN' AND t.to_location = ? THEN t.quantity * i.cost ELSE 0 END) as in_new_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'TRANSFER_IN' AND t.to_location = ? THEN t.quantity * i.cost ELSE 0 END) as in_rec_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'OUT' AND t.from_location = ? THEN t.quantity * i.cost ELSE 0 END) as out_sold_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'TRANSFER_OUT' AND t.from_location = ? THEN t.quantity * i.cost ELSE 0 END) as out_xfer_val
            FROM spareparts_transactions t
            JOIN spareparts_inventory i ON t.part_no = i.part_no
            WHERE $valWhere";

        $vParams = [
            $startDate,
            $branch,
            $branch, // Beg Bal
            $startDate,
            $endDate,
            $branch, // New In
            $startDate,
            $endDate,
            $branch, // Rec In
            $startDate,
            $endDate,
            $branch, // Sold Out
            $startDate,
            $endDate,
            $branch  // Xfer Out
        ];
        $vTypes = "sssssssssssssss"; // 15 params
    } else {
        $valQuery = "SELECT 
            SUM(CASE WHEN t.transaction_date < ? THEN (CASE WHEN t.type IN ('IN', 'TRANSFER_IN') THEN t.quantity * i.cost ELSE -t.quantity * i.cost END) ELSE 0 END) as beg_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'IN' THEN t.quantity * i.cost ELSE 0 END) as in_new_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'TRANSFER_IN' THEN t.quantity * i.cost ELSE 0 END) as in_rec_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'OUT' THEN t.quantity * i.cost ELSE 0 END) as out_sold_val,
            SUM(CASE WHEN t.transaction_date BETWEEN ? AND ? AND t.type = 'TRANSFER_OUT' THEN t.quantity * i.cost ELSE 0 END) as out_xfer_val
            FROM spareparts_transactions t
            JOIN spareparts_inventory i ON t.part_no = i.part_no
            WHERE $valWhere";

        $vParams = [
            $startDate, // Beg Bal
            $startDate,
            $endDate, // New In
            $startDate,
            $endDate, // Rec In
            $startDate,
            $endDate, // Sold Out
            $startDate,
            $endDate  // Xfer Out
        ];
        $vTypes = "sssssssss"; // 9 params
    }

    $vParams = array_merge($vParams, $params);
    $vTypes .= $types;

    $valStmt = $conn->prepare($valQuery);
    $valStmt->bind_param($vTypes, ...$vParams);
    $valStmt->execute();
    $vRes = $valStmt->get_result()->fetch_assoc();

    // Detail Data for Tabs
    $detailQuery = "SELECT i.current_branch as branch, i.brand, i.part_no, i.description, i.category, i.current_stock as available, i.cost, (i.current_stock * i.cost) as total_value
                    FROM spareparts_inventory i WHERE $where ORDER BY i.current_branch, i.brand, i.part_no";
    $detailStmt = $conn->prepare($detailQuery);
    if (!empty($params))
        $detailStmt->bind_param($types, ...$params);
    $detailStmt->execute();
    $detailData = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return [
        'success' => true,
        'matrix' => $matrixData,
        'branches' => $branches,
        'parts' => $parts,
        'data' => $detailData,
        'summary' => [
            'total_beg' => $begTotal,
            'total_beg_value' => (float) ($vRes['beg_val'] ?? 0),
            'total_received' => $inRec,
            'total_received_value' => (float) ($vRes['in_rec_val'] ?? 0),
            'total_new' => $inNew,
            'total_new_value' => (float) ($vRes['in_new_val'] ?? 0),
            'total_in' => $inRec + $inNew,
            'total_in_value' => (float) (($vRes['in_rec_val'] ?? 0) + ($vRes['in_new_val'] ?? 0)),
            'total_transfers_out' => $outXfer,
            'total_transfers_out_value' => (float) ($vRes['out_xfer_val'] ?? 0),
            'total_sold' => $outSold,
            'total_sold_value' => (float) ($vRes['out_sold_val'] ?? 0),
            'total_out' => $outSold + $outXfer,
            'total_out_value' => (float) (($vRes['out_sold_val'] ?? 0) + ($vRes['out_xfer_val'] ?? 0)),
            'total_end' => $endTotal,
            'total_available' => array_sum(array_column($detailData, 'available')),
            'total_value' => array_sum(array_column($detailData, 'total_value')),
            'part_summary' => array_values(array_reduce($detailData, function ($acc, $item) {
                $pk = $item['part_no'];
                if (!isset($acc[$pk])) {
                    $acc[$pk] = ['part_no' => $pk, 'description' => $item['description'], 'quantity' => 0];
                }
                $acc[$pk]['quantity'] += (int) $item['available'];
                return $acc;
            }, []))
        ]
    ];
}

function getInventoryMovementReport($type, $period, $dateVal, $branch, $brand, $category, $part_no)
{
    global $conn;
    $where = "t.type = ?";
    $params = [$type];
    $types = "s";

    if ($period === 'daily') {
        $where .= " AND DATE(t.transaction_date) = ?";
        $params[] = $dateVal;
        $types .= "s";
    } else if ($period === 'monthly') {
        $where .= " AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
        $params[] = $dateVal;
        $types .= "s";
    } else if ($period === 'custom') {
        // Assume dateVal is "YYYY-MM-DD to YYYY-MM-DD"
        $dates = explode(' to ', $dateVal);
        if (count($dates) === 2) {
            $where .= " AND t.transaction_date BETWEEN ? AND ?";
            $params[] = $dates[0];
            $params[] = $dates[1];
            $types .= "ss";
        }
    }

    if ($branch !== 'all') {
        // For movements, check if it was into or out of this branch
        if ($type === 'TRANSFER_OUT' || $type === 'OUT') {
            $where .= " AND t.from_location = ?";
        } else {
            $where .= " AND t.to_location = ?";
        }
        $params[] = $branch;
        $types .= "s";
    }

    if (!empty($brand)) {
        $where .= " AND i.brand LIKE ?";
        $params[] = "%$brand%";
        $types .= "s";
    }
    if (!empty($category)) {
        $where .= " AND i.category LIKE ?";
        $params[] = "%$category%";
        $types .= "s";
    }
    if (!empty($part_no)) {
        $where .= " AND t.part_no = ?";
        $params[] = $part_no;
        $types .= "s";
    }

    // Join with unique part details to avoid row duplication if part exists in multiple branches
    $query = "SELECT t.*, i.brand, i.category 
              FROM spareparts_transactions t
              LEFT JOIN (
                  SELECT part_no, brand, category 
                  FROM spareparts_inventory 
                  GROUP BY part_no, brand, category
              ) i ON t.part_no = i.part_no 
              WHERE $where ORDER BY t.transaction_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $data = [];
    foreach ($result as $row) {
        $row['total_amount'] = (float) $row['quantity'] * (float) $row['price'];
        $data[] = $row;
    }

    $brands = [];
    $parts = [];
    foreach ($data as $row) {
        $partKey = $row['part_no'];
        if (!isset($parts[$partKey])) {
            $parts[$partKey] = [
                'part_no' => $row['part_no'],
                'description' => $row['description'] ?? 'No Description',
                'quantity' => 0
            ];
        }
        $parts[$partKey]['quantity'] += (int) $row['quantity'];
    }
    $partSummary = array_values($parts);

    return [
        'success' => true,
        'data' => $data,
        'summary' => [
            'total_quantity' => array_sum(array_column($data, 'quantity')),
            'total_amount' => array_sum(array_column($data, 'total_amount')),
            'part_summary' => $partSummary
        ]
    ];
}

function getLegacyInventoryReport($type, $period, $dateVal, $branch, $brand)
{
    // Keeping the original logic for compatibility if needed
    global $conn;
    $whereClauses = [];
    $params = [];
    $types = '';

    if ($branch !== 'all') {
        if ($type === 'stock_in') {
            $whereClauses[] = "(t.to_location = ? OR (t.type = 'IN' AND t.from_location = ?))";
            $params[] = $branch;
            $params[] = $branch;
            $types .= 'ss';
        } else {
            $whereClauses[] = "t.from_location = ?";
            $params[] = $branch;
            $types .= 's';
        }
    }

    if ($type === 'stock_in') {
        $whereClauses[] = "(t.type = 'IN' OR t.type = 'TRANSFER_IN')";
    } else {
        $whereClauses[] = "(t.type = 'OUT' OR t.type = 'TRANSFER_OUT')";
    }

    if ($period === 'daily') {
        $whereClauses[] = "DATE(t.transaction_date) = ?";
        $params[] = $dateVal;
        $types .= 's';
    } else if ($period === 'monthly') {
        $whereClauses[] = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
        $params[] = $dateVal;
        $types .= 's';
    }

    if (!empty($brand)) {
        $whereClauses[] = "i.brand LIKE ?";
        $params[] = "%$brand%";
        $types .= 's';
    }

    $whereSql = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : '';
    $query = "SELECT t.*, i.brand, i.description, i.current_stock FROM spareparts_transactions t 
              LEFT JOIN spareparts_inventory i ON t.part_no = i.part_no $whereSql ORDER BY t.transaction_date DESC";

    $stmt = $conn->prepare($query);
    if (!empty($params))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    return ['success' => true, 'data' => $data];
}

function searchInventoryParts()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";

    // If Admin/HeadOffice, show parts from all branches
    $whereBranch = $seeAll ? "" : " AND LOWER(current_branch) = LOWER(?)";

    $stmt = $conn->prepare("SELECT id, brand, part_no, description, current_stock, price, current_branch
                            FROM spareparts_inventory 
                            WHERE (part_no LIKE ? OR description LIKE ? OR brand LIKE ?) 
                            $whereBranch
                            LIMIT 10");

    if ($seeAll) {
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    } else {
        $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $currentBranch);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc())
        $data[] = $row;
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSparepartsBranches()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';

    if (!$seeAll) {
        // Only allow branch users to see Head Office (for transfers)
        echo json_encode(['success' => true, 'data' => ['HEADOFFICE']]);
        return;
    }

    $query = "SELECT DISTINCT branch FROM users WHERE position = 'Spareparts-Branch' AND branch IS NOT NULL AND branch != '' ORDER BY branch ASC";
    $result = $conn->query($query);
    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row['branch'];
    }
    echo json_encode(['success' => true, 'data' => $branches]);
}

function generateSalesSummaryReport()
{
    global $conn, $currentBranch, $isAdmin;

    $period = sanitizeInput($_POST['period'] ?? 'daily');
    $dateVal = sanitizeInput($_POST['date_value'] ?? $_POST['date_val'] ?? date('Y-m-d'));
    $branch = sanitizeInput($_POST['branch'] ?? 'all');
    $type = sanitizeInput($_POST['type'] ?? 'all');
    $brand = sanitizeInput($_POST['brand'] ?? '');

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    if (!$seeAll) {
        $branch = $currentBranch;
    }

    $whereClauses = ["t.type = 'OUT'"];
    $params = [];
    $types = '';

    // Period filter
    if ($period === 'daily') {
        $whereClauses[] = "DATE(t.transaction_date) = ?";
        $params[] = $dateVal;
        $types .= 's';
    } else if ($period === 'monthly') {
        $whereClauses[] = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
        $params[] = $dateVal;
        $types .= 's';
    } else if ($period === 'yearly') {
        $whereClauses[] = "YEAR(t.transaction_date) = ?";
        $params[] = $dateVal;
        $types .= 's';
    }

    // Branch filter
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    if (!$seeAll) {
        $whereClauses[] = "t.from_location = ?";
        $params[] = $currentBranch;
        $types .= 's';
    } else if ($branch !== 'all') {
        $whereClauses[] = "t.from_location = ?";
        $params[] = $branch;
        $types .= 's';
    }

    // Transaction Type filter
    if ($type === 'cash') {
        $whereClauses[] = "t.transaction_type = 'cash'";
    } else if ($type === 'charge') {
        $whereClauses[] = "t.transaction_type = 'charge'";
    }

    // Brand filter
    if (!empty($brand)) {
        $whereClauses[] = "(i.brand LIKE ? OR t.brand LIKE ?)";
        $params[] = "%$brand%";
        $params[] = "%$brand%";
        $types .= 'ss';
    }

    $whereSql = "WHERE " . implode(' AND ', $whereClauses);

    $sql = "SELECT t.transaction_date, t.or_number, t.customer_name, t.transaction_type, 
                   COALESCE(NULLIF(t.from_location, ''), NULLIF(t.to_location, ''), '-') as from_location,
                   t.part_no, t.quantity, t.price, t.total_amount, 
                   COALESCE(i.description, t.description) as description, 
                   COALESCE(i.brand, t.brand) as brand
            FROM spareparts_transactions t
            LEFT JOIN spareparts_inventory i ON t.part_no = i.part_no 
                AND i.current_branch = COALESCE(NULLIF(t.from_location, ''), 'HEADOFFICE')
            $whereSql
            ORDER BY t.transaction_date ASC, t.or_number ASC";

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
function generateTransferReport()
{
    global $conn, $currentBranch, $isAdmin;

    $type = sanitizeInput($_POST['report_type'] ?? 'all'); // all, Completed, In-Transit
    $period = sanitizeInput($_POST['period'] ?? 'daily');
    $dateVal = sanitizeInput($_POST['date_value'] ?? '');
    $branch = sanitizeInput($_POST['branch'] ?? 'all');

    $whereClauses = [];
    $params = [];
    $types = '';

    // Status/Type filter
    if ($type !== 'all') {
        $whereClauses[] = "t.status = ?";
        $params[] = $type;
        $types .= 's';
    }

    // Period filter
    $dateField = "t.transfer_date";
    if ($period === 'daily') {
        $whereClauses[] = "DATE($dateField) = ?";
        $params[] = $dateVal;
        $types .= 's';
    } else if ($period === 'monthly') {
        $whereClauses[] = "DATE_FORMAT($dateField, '%Y-%m') = ?";
        $params[] = $dateVal;
        $types .= 's';
    } else if ($period === 'yearly') {
        $whereClauses[] = "YEAR($dateField) = ?";
        $params[] = $dateVal;
        $types .= 's';
    }

    // Branch filter
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    if (!$seeAll) {
        $whereClauses[] = "(t.from_branch = ? OR t.to_branch = ?)";
        $params[] = $currentBranch;
        $params[] = $currentBranch;
        $types .= 'ss';
    } else if ($branch !== 'all') {
        $whereClauses[] = "(t.from_branch = ? OR t.to_branch = ?)";
        $params[] = $branch;
        $params[] = $branch;
        $types .= 'ss';
    }

    $whereSql = "";
    if (count($whereClauses) > 0) {
        $whereSql = "WHERE " . implode(' AND ', $whereClauses);
    }

    $sql = "SELECT t.transfer_date, t.id as transfer_number, t.from_branch, t.to_branch, t.status,
                   ti.part_no, ti.description, ti.quantity,
                   (SELECT brand FROM spareparts_inventory WHERE part_no = ti.part_no LIMIT 1) as brand
            FROM spareparts_transfers t
            JOIN spareparts_transfer_items ti ON t.id = ti.transfer_id
            $whereSql
            ORDER BY t.transfer_date DESC, t.id DESC";

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

    echo json_encode(['success' => true, 'count' => count($data), 'data' => $data]);
}

function getAgingSummary()
{
    global $conn, $currentBranch, $isAdmin;
    $branch = sanitizeInput($_GET['branch'] ?? 'all');
    $search = sanitizeInput($_GET['search'] ?? '');

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $params = [];
    $types = '';

    $sql = "SELECT branch, customer_name, COUNT(*) as active_accounts, SUM(balance) as total_balance,
                   SUM(CASE WHEN DATEDIFF(CURRENT_DATE(), sale_date) <= 30 THEN balance ELSE 0 END) as age_0_30,
                   SUM(CASE WHEN DATEDIFF(CURRENT_DATE(), sale_date) BETWEEN 31 AND 60 THEN balance ELSE 0 END) as age_31_60,
                   SUM(CASE WHEN DATEDIFF(CURRENT_DATE(), sale_date) BETWEEN 61 AND 90 THEN balance ELSE 0 END) as age_61_90,
                   SUM(CASE WHEN DATEDIFF(CURRENT_DATE(), sale_date) > 90 THEN balance ELSE 0 END) as age_over_90
            FROM spareparts_aging 
            WHERE status = 'Active' ";

    if (!$seeAll) {
        $sql .= " AND branch = ? ";
        $params[] = $currentBranch;
        $types .= 's';
    } else if ($branch !== 'all' && $branch !== 'All') {
        $sql .= " AND branch = ? ";
        $params[] = $branch;
        $types .= 's';
    }

    if (!empty($search)) {
        $sql .= " AND customer_name LIKE ? ";
        $params[] = "%$search%";
        $types .= 's';
    }

    $sql .= " GROUP BY branch, customer_name ORDER BY customer_name ASC";

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

function getCustomerLedger()
{
    global $conn;
    $customer = sanitizeInput($_GET['customer'] ?? '');
    $branch = sanitizeInput($_GET['branch'] ?? '');

    if (empty($customer)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
        return;
    }

    // Fetch all OUT and PAYMENT transactions for this customer
    $sql = "SELECT transaction_date as date, or_number, type, total_amount as amount, transaction_type
            FROM spareparts_transactions 
            WHERE customer_name = ? AND from_location = ? 
              AND (type = 'PAYMENT' OR (type = 'OUT' AND transaction_type = 'charge'))
            ORDER BY transaction_date ASC, id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $customer, $branch);
    $stmt->execute();
    $result = $stmt->get_result();

    $ledger = [];
    $runningBalance = 0;

    while ($row = $result->fetch_assoc()) {
        if ($row['type'] === 'OUT') {
            $runningBalance += (float) $row['amount'];
            $row['sale_amount'] = (float) $row['amount'];
            $row['payment_amount'] = 0;
        } else {
            $runningBalance -= (float) $row['amount'];
            $row['sale_amount'] = 0;
            $row['payment_amount'] = (float) $row['amount'];
        }
        $row['balance'] = $runningBalance;
        $ledger[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $ledger]);
}

function getIncomingTransfersDetailed()
{
    global $conn, $currentBranch;
    $sql = "SELECT ti.id as item_id, ti.id as id, ti.part_no, ti.description, ti.quantity as qty, 
                   t.transfer_date, t.from_branch, t.id as transfer_number, t.status,
                   i.brand
            FROM spareparts_transfers t
            JOIN spareparts_transfer_items ti ON t.id = ti.transfer_id
            LEFT JOIN spareparts_inventory i ON ti.part_no = i.part_no AND i.current_branch = t.from_branch
            WHERE t.to_branch = ? AND t.status = 'In-Transit'
            ORDER BY t.transfer_date DESC, t.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['transfer_date'] = date('m/d/Y', strtotime($row['transfer_date']));
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function batchReceiveTransfers()
{
    global $conn, $currentBranch;
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'No items selected.']);
        return;
    }

    $conn->begin_transaction();
    try {
        foreach ($ids as $itemId) {
            $itemId = (int) $itemId;

            // 1. Get item and transfer details
            $stmt = $conn->prepare("SELECT ti.*, t.from_branch, t.id as tid 
                                    FROM spareparts_transfer_items ti 
                                    JOIN spareparts_transfers t ON ti.transfer_id = t.id 
                                    WHERE ti.id = ? AND t.to_branch = ? AND t.status = 'In-Transit'");
            $stmt->bind_param('is', $itemId, $currentBranch);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();

            if (!$item)
                continue;

            $part_no = $item['part_no'];
            $description = $item['description'];
            $quantity = (int) $item['quantity'];
            $cost = (float) $item['cost'];
            $fromBranch = $item['from_branch'];
            $transferId = $item['tid'];

            // 2. Update/Insert into inventory at current branch
            $invStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, current_stock, cost, current_branch) 
                                      VALUES (?, ?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE 
                                      current_stock = current_stock + VALUES(current_stock), 
                                      cost = (cost + VALUES(cost)) / 2");
            $invStmt->bind_param('ssids', $part_no, $description, $quantity, $cost, $currentBranch);
            $invStmt->execute();

            // 3. Log TRANSACTION_IN
            $txStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status) 
                                     VALUES (CURDATE(), 'TRANSFER_IN', ?, ?, ?, ?, ?, ?, ?, 'Completed')");
            $total_amount = $quantity * $cost;
            $txStmt->bind_param('ssiddss', $part_no, $description, $quantity, $cost, $total_amount, $fromBranch, $currentBranch);
            $txStmt->execute();

            // 4. Update the transfer status to Completed (for the whole transfer record for now)
            // Note: In a more complex system, we might track per-item status, but here we'll assume receiving any item in the batch marks the transfer as completed or partially completed.
            // For now, let's mark the parent transfer as Completed if we reach this point.
            $updTransfer = $conn->prepare("UPDATE spareparts_transfers SET status = 'Completed', received_date = NOW() WHERE id = ?");
            $updTransfer->bind_param('i', $transferId);
            $updTransfer->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Batch reception completed successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function batchRejectTransfers()
{
    global $conn, $currentBranch;
    $ids = $_POST['ids'] ?? [];
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'No items selected.']);
        return;
    }

    $conn->begin_transaction();
    try {
        foreach ($ids as $itemId) {
            $itemId = (int) $itemId;

            // 1. Get item and transfer details
            $stmt = $conn->prepare("SELECT ti.*, t.from_branch, t.id as tid 
                                    FROM spareparts_transfer_items ti 
                                    JOIN spareparts_transfers t ON ti.transfer_id = t.id 
                                    WHERE ti.id = ? AND t.to_branch = ? AND t.status = 'In-Transit'");
            $stmt->bind_param('is', $itemId, $currentBranch);
            $stmt->execute();
            $item = $stmt->get_result()->fetch_assoc();

            if (!$item)
                continue;

            $part_no = $item['part_no'];
            $quantity = (int) $item['quantity'];
            $fromBranch = $item['from_branch'];
            $transferId = $item['tid'];

            // 2. Revert stock at source branch
            $invStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? 
                                       WHERE part_no = ? AND current_branch = ?");
            $invStmt->bind_param('iss', $quantity, $part_no, $fromBranch);
            $invStmt->execute();

            // 3. Log TRANSACTION_REJECTED (optional, but good for history)
            // For now, we'll just update the transfer status

            // 4. Update the transfer status to Rejected
            $updTransfer = $conn->prepare("UPDATE spareparts_transfers SET status = 'Rejected', received_date = NOW() WHERE id = ?");
            $updTransfer->bind_param('i', $transferId);
            $updTransfer->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Batch rejection completed successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function searchPartsGlobal()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";

    // Search specifically for a part across all branches
    // Grouping by branch to show where it is available
    $whereBranch = $seeAll ? "" : " AND current_branch = ?";

    $stmt = $conn->prepare("SELECT brand, part_no, description, current_stock, price, current_branch
                            FROM spareparts_inventory 
                            WHERE (part_no LIKE ? OR description LIKE ? OR brand LIKE ?) 
                            AND current_stock > 0
                            $whereBranch
                            ORDER BY current_branch ASC, part_no ASC
                            LIMIT 50");

    if ($seeAll) {
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    } else {
        $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $currentBranch);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}
?>