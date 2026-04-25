<?php
header('Content-Type: application/json');
require_once 'db_config.php'; // Ensure you have session_start() in this file

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$currentBranch = trim($_SESSION['user_branch'] ?? 'HEADOFFICE');
$userRole = $_SESSION['position'] ?? $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$isAdmin = in_array(strtolower(trim($userRole)), array_map('strtolower', $adminRoles));
$canDelete = $isAdmin;

// Auto-repair tools removed to prevent PHP exceptions from halting script execution
try {
    $conn->query("ALTER TABLE spareparts_transfers ADD COLUMN IF NOT EXISTS transfer_no VARCHAR(100) DEFAULT NULL AFTER id");
    $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS transfer_no VARCHAR(100) DEFAULT NULL AFTER or_number");
    $conn->query("ALTER TABLE spareparts_sales_force ADD COLUMN IF NOT EXISTS position VARCHAR(255) DEFAULT NULL AFTER employee_name");
    $conn->query("ALTER TABLE spareparts_sales_force ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE spareparts_aging ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT NULL");
    // Attempt to add category to customers if table exists
    @$conn->query("ALTER TABLE spareparts_customers ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT NULL");
} catch (Exception $e) {
}

function getCurrentDivision()
{
    global $userRole;
    $pos = strtolower(trim($userRole));
    if (strpos($pos, 'retail') !== false || strpos($pos, 'sales') !== false) return 'Wholesale'; 
    return null; // Admin/HO
}

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

function addAuditLog($action_type, $table_name, $record_id, $details)
{
    global $conn;
    $user_id = $_SESSION['user_id'] ?? 0; // Ensure user_id is in session
    
    // If user_id is missing, try to find it by username
    if ($user_id === 0 && isset($_SESSION['username'])) {
        $uStmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $uStmt->bind_param('s', $_SESSION['username']);
        $uStmt->execute();
        $user_id = $uStmt->get_result()->fetch_assoc()['id'] ?? 0;
        $_SESSION['user_id'] = $user_id;
    }

    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, table_name, record_id, action_details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $user_id, $action_type, $table_name, $record_id, $details);
    $stmt->execute();
    $stmt->close();
}

function getPartColumnName($table)
{
    global $conn;
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE 'part_no'");
    return ($check && $check->num_rows > 0) ? 'part_no' : 'part_number';
}

function getModuleSettings()
{
    global $conn;
    
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Settings table is used for various global and role-based visibility flags

    $result = $conn->query("SELECT * FROM spareparts_settings");
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    echo json_encode(['success' => true, 'data' => $settings]);
}

function updateModuleSetting()
{
    global $conn;
    $key = sanitizeInput($_POST['key']);
    $val = sanitizeInput($_POST['value']);

    $stmt = $conn->prepare("INSERT INTO spareparts_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param('sss', $key, $val, $val);
    if ($stmt->execute()) {
        addAuditLog('UPDATE', 'spareparts_settings', $key, "Updated setting $key to $val");
        echo json_encode(['success' => true, 'message' => 'Setting updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update setting: ' . $conn->error]);
    }
}

function saveBeginningInventory()
{
    global $conn, $currentBranch;
    $items = json_decode($_POST['items'], true);
    $branch = sanitizeInput($_POST['branch'] ?? $currentBranch);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items provided.']);
        return;
    }

    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            $part_no = sanitizeInput($item['part_no']);
            $brand = sanitizeInput($item['brand']);
            $description = sanitizeInput($item['description']);
            $qty = (int) $item['qty'];
            $cost = (float) $item['cost'];
            $price = $cost * 1.30; 

            // Check if already exists in this branch
            $checkStmt = $conn->prepare("SELECT id FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
            $checkStmt->bind_param('ss', $part_no, $branch);
            $checkStmt->execute();
            $res = $checkStmt->get_result();

            if ($res->num_rows > 0) {
                // Update existing
                $stmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ?, cost = ?, brand = ?, description = ? WHERE part_no = ? AND current_branch = ?");
                $stmt->bind_param('idssss', $qty, $cost, $brand, $description, $part_no, $branch);
            } else {
                // Insert new
                $stmt = $conn->prepare("INSERT INTO spareparts_inventory (brand, part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssidds', $brand, $part_no, $description, $qty, $cost, $price, $branch);
            }
            $stmt->execute();

            // Log AS BEGINNING INVENTORY
            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status, reason) 
                                   VALUES (CURDATE(), 'IN', ?, ?, ?, ?, ?, 'BEGINNING_INV', ?, 'Completed', 'Beginning Inventory Entry')");
            $total = $qty * $cost;
            $log->bind_param('ssidds', $part_no, $description, $qty, $cost, $total, $branch);
            $log->execute();
        }

        $conn->commit();
        addAuditLog('INSERT', 'spareparts_inventory', 'BEGINNING_INV', "Recorded beginning inventory for " . count($items) . " items in $branch branch.");
        echo json_encode(['success' => true, 'message' => 'Beginning inventory successfully saved and recorded.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to save beginning inventory: ' . $e->getMessage()]);
    }
}

function saveBeginningCustomerBalance()
{
    global $conn, $currentBranch;
    $items = json_decode($_POST['items'], true);
    $branch = sanitizeInput($_POST['branch'] ?? $currentBranch);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items provided.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $division = getCurrentDivision();
        foreach ($items as $idx => $item) {
            $customerName = sanitizeInput($item['customer_name']);
            $refNo = sanitizeInput($item['ref_no'] ?? '');
            if (empty($refNo)) {
                $refNo = 'BEG-' . time() . '-' . $idx . '-' . rand(100, 999);
            }
            $amount = (float) $item['amount'];
            $asOfDate = sanitizeInput($item['date'] ?? date('Y-m-d'));

            if (empty($customerName) || $amount <= 0) continue;

            // 1. Ensure customer exists in spareparts_customers
            $custCheck = $conn->prepare("SELECT id FROM spareparts_customers WHERE name = ? AND branch = ?");
            $custCheck->bind_param('ss', $customerName, $branch);
            $custCheck->execute();
            if ($custCheck->get_result()->num_rows === 0) {
                $custInsert = $conn->prepare("INSERT INTO spareparts_customers (name, branch, category) VALUES (?, ?, ?)");
                $custInsert->bind_param('sss', $customerName, $branch, $division);
                $custInsert->execute();
            }

            // 2. Add to spareparts_aging
            $agingStmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, customer_name, sale_date, total_amount, balance, branch, category, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')");
            $agingStmt->bind_param('sssdsss', $refNo, $customerName, $asOfDate, $amount, $amount, $branch, $division);
            $agingStmt->execute();

            // 3. Log to transactions for ledger visibility
            $txStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, or_number, customer_name, transaction_type, type, total_amount, from_location, category, status) 
                                     VALUES (?, ?, ?, 'charge', 'OUT', ?, ?, ?, 'Completed')");
            $txStmt->bind_param('sssdss', $asOfDate, $refNo, $customerName, $amount, $branch, $division);
            $txStmt->execute();
        }

        $conn->commit();
        addAuditLog('INSERT', 'spareparts_aging', 'BEGINNING_BAL', "Recorded beginning customer balances for " . count($items) . " accounts in $branch branch.");
        echo json_encode(['success' => true, 'message' => 'Customer beginning balances successfully saved.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to save beginning balances: ' . $e->getMessage()]);
    }
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
    case 'add_part':
        addPart();
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
    case 'get_stock_card_data':
        getStockCardData();
        break;

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

    case 'get_sales_force':
        getSalesForce();
        break;
    case 'add_sales_force':
        addSalesForce();
        break;
    case 'delete_sales_force':
        deleteSalesForce();
        break;
    case 'edit_sales_force':
        editSalesForce();
        break;
    case 'search_sales_force':
        searchSalesForce();
        break;

    case 'get_part_details_with_compatibility':
        getPartDetailsWithCompatibility();
        break;
    case 'get_price_history':
        getPriceHistory();
        break;
    case 'search_parts_for_in':
        searchPartsForIn();
        break;
    case 'get_pricelists':
        getPricelists();
        break;
    case 'save_pricelist':
        savePricelist();
        break;
    case 'save_bulk_pricelists':
        saveBulkPricelists();
        break;
    case 'delete_pricelist':
        deletePricelist();
        break;
    case 'get_rank_price':
        getRankPrice();
        break;
    case 'save_beginning_inventory':
        saveBeginningInventory();
        break;
    case 'get_module_settings':
        getModuleSettings();
        break;
    case 'update_module_setting':
        updateModuleSetting();
        break;
    case 'save_beginning_customer_balance':
        saveBeginningCustomerBalance();
        break;
    case 'get_next_invoice_number':
        getNextInvoiceNumber();
        break;
    case 'update_invoice_sequence_start':
        updateInvoiceSequenceStart();
        break;
    case 'get_invoice_sequence_settings':
        getInvoiceSequenceSettings();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

/**
 * Get the next sequential Sales Invoice number for the current branch.
 * Format: {PREFIX}-{YEAR}-{PADDED_SEQ}
 * e.g. SI-2026-00123
 * The starting number can be configured per branch in spareparts_settings.
 */
function getNextInvoiceNumber()
{
    global $conn, $currentBranch;

    // Ensure settings table exists
    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $branch = sanitizeInput($_GET['branch'] ?? $currentBranch);
    $branchSlug = preg_replace('/[^A-Za-z0-9]+/', '', strtoupper($branch));
    $year = date('Y');

    // Key for the starting sequence override (admin-configurable)
    $startKey = "invoice_seq_start_{$branchSlug}";
    // Key for the prefix (optional per-branch prefix)
    $prefixKey = "invoice_seq_prefix_{$branchSlug}";

    // Fetch settings
    $stmtCfg = $conn->prepare("SELECT setting_key, setting_value FROM spareparts_settings WHERE setting_key IN (?, ?)");
    $stmtCfg->bind_param('ss', $startKey, $prefixKey);
    $stmtCfg->execute();
    $cfgRows = $stmtCfg->get_result();
    $cfg = [];
    while ($r = $cfgRows->fetch_assoc()) {
        $cfg[$r['setting_key']] = $r['setting_value'];
    }
    $stmtCfg->close();

    $startingNumber = isset($cfg[$startKey]) ? (int)$cfg[$startKey] : 1;
    $prefix = isset($cfg[$prefixKey]) && !empty($cfg[$prefixKey]) ? strtoupper($cfg[$prefixKey]) : 'SI';

    // Count existing invoices for this branch in the current year that are >= startingNumber
    // We look at spareparts_transactions (OUT type) for the branch and year
    $stmtCount = $conn->prepare("
        SELECT COUNT(DISTINCT or_number) as cnt
        FROM spareparts_transactions
        WHERE type = 'OUT'
          AND from_location = ?
          AND YEAR(transaction_date) = ?
          AND or_number IS NOT NULL
          AND or_number != ''
    ");
    $stmtCount->bind_param('ss', $branch, $year);
    $stmtCount->execute();
    $countRow = $stmtCount->get_result()->fetch_assoc();
    $stmtCount->close();

    $existingCount = (int)($countRow['cnt'] ?? 0);

    // Next number = max(startingNumber, existingCount + 1)
    // But also check the actual max OR number to avoid duplicates
    $stmtMax = $conn->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(or_number, '-', -1) AS UNSIGNED)) as maxSeq
        FROM spareparts_transactions
        WHERE type = 'OUT'
          AND from_location = ?
          AND YEAR(transaction_date) = ?
          AND or_number REGEXP '^[A-Z]+-[0-9]{4}-[0-9]+$'
    ");
    $stmtMax->bind_param('ss', $branch, $year);
    $stmtMax->execute();
    $maxRow = $stmtMax->get_result()->fetch_assoc();
    $stmtMax->close();

    $maxSeq = (int)($maxRow['maxSeq'] ?? 0);
    $nextSeq = max($startingNumber, $maxSeq + 1);

    $paddedSeq = str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    $nextInvoice = "{$prefix}-{$year}-{$paddedSeq}";

    echo json_encode([
        'success' => true,
        'invoice_number' => $nextInvoice,
        'prefix' => $prefix,
        'year' => $year,
        'sequence' => $nextSeq,
        'branch' => $branch
    ]);
}

/**
 * Admin: Set the starting sequence number and prefix for a specific branch.
 */
function updateInvoiceSequenceStart()
{
    global $conn, $isAdmin;

    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
        return;
    }

    $branch = sanitizeInput($_POST['branch'] ?? '');
    $startNum = (int)($_POST['start_number'] ?? 1);
    $prefix = strtoupper(sanitizeInput($_POST['prefix'] ?? 'SI'));

    if (empty($branch)) {
        echo json_encode(['success' => false, 'message' => 'Branch is required.']);
        return;
    }
    if ($startNum < 1) $startNum = 1;
    if (empty($prefix)) $prefix = 'SI';

    $branchSlug = preg_replace('/[^A-Za-z0-9]+/', '', strtoupper($branch));
    $startKey  = "invoice_seq_start_{$branchSlug}";
    $prefixKey = "invoice_seq_prefix_{$branchSlug}";

    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Upsert start number
    $s1 = $conn->prepare("INSERT INTO spareparts_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $startVal = (string)$startNum;
    $s1->bind_param('ss', $startKey, $startVal);
    $s1->execute();
    $s1->close();

    // Upsert prefix
    $s2 = $conn->prepare("INSERT INTO spareparts_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $s2->bind_param('ss', $prefixKey, $prefix);
    $s2->execute();
    $s2->close();

    addAuditLog('UPDATE', 'spareparts_settings', $branch, "Invoice sequence for branch $branch set to start: $startNum, prefix: $prefix");
    echo json_encode(['success' => true, 'message' => "Invoice sequence for branch '$branch' updated. Next SI will start from: {$prefix}-" . date('Y') . "-" . str_pad($startNum, 5, '0', STR_PAD_LEFT)]);
}

/**
 * Get all invoice sequence settings for all branches (admin view).
 */
function getInvoiceSequenceSettings()
{
    global $conn, $isAdmin;

    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        return;
    }

    // Get all branches from users table
    $branchesResult = $conn->query("SELECT DISTINCT branch FROM users WHERE LOWER(position) LIKE '%pareparts%' AND branch IS NOT NULL AND branch != '' ORDER BY branch ASC");
    $branches = ['HEADOFFICE'];
    if ($branchesResult) {
        while ($r = $branchesResult->fetch_assoc()) {
            if (!in_array($r['branch'], $branches)) {
                $branches[] = $r['branch'];
            }
        }
    }


    sort($branches);

    // Get all invoice settings
    $settingsResult = $conn->query("SELECT setting_key, setting_value FROM spareparts_settings WHERE setting_key LIKE 'invoice_seq_%'");
    $settings = [];
    if ($settingsResult) {
        while ($r = $settingsResult->fetch_assoc()) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
    }

    $year = date('Y');
    $result = [];
    foreach ($branches as $branch) {
        $branchSlug = preg_replace('/[^A-Za-z0-9]+/', '', strtoupper($branch));
        $startKey  = "invoice_seq_start_{$branchSlug}";
        $prefixKey = "invoice_seq_prefix_{$branchSlug}";

        $startNum = isset($settings[$startKey]) ? (int)$settings[$startKey] : 1;
        $prefix   = isset($settings[$prefixKey]) ? $settings[$prefixKey] : 'SI';

        // Get the actual current max sequence for this branch
        $stmtMax = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(or_number, '-', -1) AS UNSIGNED)) as maxSeq
            FROM spareparts_transactions
            WHERE type = 'OUT' AND from_location = ?
              AND YEAR(transaction_date) = ?
              AND or_number REGEXP '^[A-Z]+-[0-9]{4}-[0-9]+$'
        ");
        $stmtMax->bind_param('ss', $branch, $year);
        $stmtMax->execute();
        $maxRow = $stmtMax->get_result()->fetch_assoc();
        $stmtMax->close();
        $maxSeq = (int)($maxRow['maxSeq'] ?? 0);
        $nextSeq = max($startNum, $maxSeq + 1);

        $result[] = [
            'branch'       => $branch,
            'prefix'       => $prefix,
            'start_number' => $startNum,
            'current_max'  => $maxSeq,
            'next_number'  => "{$prefix}-{$year}-" . str_pad($nextSeq, 5, '0', STR_PAD_LEFT)
        ];
    }

    echo json_encode(['success' => true, 'data' => $result]);
}

// ===================================================================
// ============================= READ DATA ===========================
// ===================================================================
function getBranches()
{
    global $conn, $currentBranch, $isAdmin, $userRole;
    
    $allowedRoles = ['spareparts-warehouse', 'spareparts-sales', 'spareparts-retail'];
    $isSparepartsRole = in_array(strtolower(trim($userRole)), $allowedRoles);
    
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice' || $isSparepartsRole;

    if (!$seeAll) {
        // Only allow branch users to see Head Office (for transfers)
        echo json_encode(['success' => true, 'data' => ['HEADOFFICE']]);
        return;
    }


    $sql = "SELECT DISTINCT branch FROM users WHERE position IN ('Spareparts-Sales', 'Spareparts-Warehouse', 'Spareparts-Retail') AND branch IS NOT NULL AND branch != '' ORDER BY branch ASC";
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

function getPartDetailsWithCompatibility()
{
    global $conn, $currentBranch;
    $part_no = sanitizeInput($_GET['part_no'] ?? '');

    if (empty($part_no)) {
        echo json_encode(['success' => false, 'message' => 'Part number is required.']);
        return;
    }

    // We want to fetch the part details regardless of which branch it's currently in,
    // so we can use its master details (description, cost, price) for stock in.
    $stmt = $conn->prepare("SELECT * FROM spareparts_inventory WHERE part_no = ? LIMIT 1");
    $stmt->bind_param('s', $part_no);
    $stmt->execute();
    $part = $stmt->get_result()->fetch_assoc();

    if ($part) {
        // Fetch Compatibility
        $compStmt = $conn->prepare("SELECT model_name FROM spareparts_compatibility WHERE part_no = ?");
        $compStmt->bind_param('s', $part_no);
        $compStmt->execute();
        $compRes = $compStmt->get_result();
        $compatibility = [];
        while ($row = $compRes->fetch_assoc()) {
            $compatibility[] = $row['model_name'];
        }
        $part['compatibility'] = $compatibility;

        echo json_encode(['success' => true, 'data' => $part]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Part not found.']);
    }
}

function getPriceHistory()
{
    global $conn;
    $part_no = sanitizeInput($_GET['part_no'] ?? '');

    if (empty($part_no)) {
        echo json_encode(['success' => false, 'message' => 'Part number is required.']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM spareparts_price_history WHERE part_no = ? ORDER BY transaction_date DESC");
    $stmt->bind_param('s', $part_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}

function getStockCardData()
{
    global $conn, $currentBranch, $isAdmin;
    
    try {
        $part_no = sanitizeInput($_GET['part_no'] ?? '');
        
        // Allow overriding branch for Admin/HO viewing other branches
        $targetBranch = sanitizeInput($_GET['branch'] ?? $currentBranch);
        $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
        if (!$seeAll) {
            $targetBranch = $currentBranch; // Enforce current branch for non-admins
        }

        if (empty($part_no)) {
            echo json_encode(['success' => false, 'message' => 'Part number is required.']);
            return;
        }

        // 1. Core Inventory Info
        $stmt = $conn->prepare("SELECT * FROM spareparts_inventory WHERE part_no = ? AND current_branch = ? LIMIT 1");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query preparation failed (Inventory): ' . $conn->error]);
            return;
        }
        $stmt->bind_param('ss', $part_no, $targetBranch);
        $stmt->execute();
        $inventory = $stmt->get_result()->fetch_assoc();

        if (!$inventory) {
            echo json_encode(['success' => false, 'message' => 'Part not found in inventory for branch ' . $targetBranch]);
            return;
        }

        // 2. Compatibility (Dynamic column detection)
        $compCol = getPartColumnName('spareparts_compatibility');
        $compStmt = $conn->prepare("SELECT model_name FROM spareparts_compatibility WHERE $compCol = ?");
        if (!$compStmt) {
            echo json_encode(['success' => false, 'message' => "Query preparation failed (Compatibility on $compCol): " . $conn->error]);
            return;
        }
        $compStmt->bind_param('s', $part_no);
        $compStmt->execute();
        $compRes = $compStmt->get_result();
        $compatibility = [];
        while ($row = $compRes->fetch_assoc()) { $compatibility[] = $row['model_name']; }
        $inventory['compatibility'] = $compatibility;

        // 3. Movement Log (Uses part_no consistently in this table)
        $txStmt = $conn->prepare("
            SELECT t.*, 
                   COALESCE(NULLIF(t.transfer_no, ''), 
                           (SELECT st.transfer_no FROM spareparts_transfers st 
                            WHERE st.from_branch = t.from_location AND st.to_branch = t.to_location 
                            AND ABS(DATEDIFF(st.transfer_date, t.transaction_date)) <= 1 
                            LIMIT 1)) as recovered_transfer_no
            FROM spareparts_transactions t
            WHERE t.part_no = ? 
              AND (
                  (t.from_location = ? AND t.type NOT IN ('IN', 'TRANSFER_IN', 'RETURN', 'ADJUSTMENT_IN')) 
                  OR 
                  (t.to_location = ? AND t.type NOT IN ('OUT', 'TRANSFER_OUT', 'SALE', 'ADJUSTMENT_OUT'))
              )
            ORDER BY t.transaction_date DESC, t.id DESC LIMIT 50
        ");
        if (!$txStmt) {
            echo json_encode(['success' => false, 'message' => 'Query preparation failed (Transactions): ' . $conn->error]);
            return;
        }
        $txStmt->bind_param('sss', $part_no, $targetBranch, $targetBranch);
        $txStmt->execute();
        $transactions = $txStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 4. Cost History (Dynamic column detection)
        $histCol = getPartColumnName('spareparts_price_history');
        $histStmt = $conn->prepare("SELECT * FROM spareparts_price_history WHERE $histCol = ? ORDER BY transaction_date DESC LIMIT 50");
        if (!$histStmt) {
            echo json_encode(['success' => false, 'message' => "Query preparation failed (History on $histCol): " . $conn->error]);
            return;
        }
        $histStmt->bind_param('s', $part_no);
        $histStmt->execute();
        $history = $histStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'inventory' => $inventory,
                'transactions' => $transactions,
                'price_history' => $history
            ]
        ]);
    } catch (mysqli_sql_exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, "doesn't exist") !== false) {
            echo json_encode(['success' => false, 'message' => 'Database tables are missing. Please run the migration script: api/migrate_spareparts.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $msg]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
    }
}

function getDashboardStats()
{
    global $conn, $currentBranch, $isAdmin;
    $stats = [];

    $division = getCurrentDivision();
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $whereInv = $seeAll ? "" : "WHERE current_branch = ?";
    
    $whereTxOut = $seeAll ? "WHERE type = 'OUT'" : "WHERE type = 'OUT' AND from_location = ?";
    if (!$seeAll && $division) {
        $whereTxOut .= " AND category = '$division'";
    }

    $whereAging = $seeAll ? "WHERE status = 'Active'" : "WHERE branch = ? AND status = 'Active'";
    if (!$seeAll && $division) {
        $whereAging .= " AND category = '$division'";
    }

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
    
    $branch = sanitizeInput($_GET['branch'] ?? 'all');
    $dateFrom = sanitizeInput($_GET['date_from'] ?? '');
    $dateTo = sanitizeInput($_GET['date_to'] ?? '');

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    
    $whereClauses = ["t.type = 'OUT'"];
    $params = [];
    $types = "";

    if (!$seeAll) {
        $whereClauses[] = "t.from_location = ?";
        $params[] = $currentBranch;
        $types .= "s";
    } elseif ($branch !== 'all') {
        $whereClauses[] = "t.from_location = ?";
        $params[] = $branch;
        $types .= "s";
    }

    $division = getCurrentDivision();
    if (!$seeAll && $division) {
        $whereClauses[] = "t.category = ?";
        $params[] = $division;
        $types .= "s";
    }

    if (!empty($dateFrom)) {
        $whereClauses[] = "t.transaction_date >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }
    if (!empty($dateTo)) {
        $whereClauses[] = "t.transaction_date <= ?";
        $params[] = $dateTo . " 23:59:59";
        $types .= "s";
    }

    $whereStr = implode(" AND ", $whereClauses);

    $sql = "SELECT t.id, t.transaction_date as sale_date, t.customer_name, t.or_number, t.transaction_type, SUM(t.total_amount) as total_amount, a.balance, t.from_location, t.sales_force
            FROM spareparts_transactions t
            LEFT JOIN spareparts_aging a ON t.or_number = a.or_number AND t.from_location = a.branch
            WHERE $whereStr
            GROUP BY t.or_number, t.from_location
            ORDER BY t.id DESC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        return;
    }
    
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

function getSaleDetails()
{
    global $conn;
    $or_number = sanitizeInput($_GET['or_number'] ?? '');
    $branch = sanitizeInput($_GET['branch'] ?? '');

    if (empty($or_number) || empty($branch)) {
        echo json_encode(['success' => false, 'message' => 'Missing OR number or branch.']);
        return;
    }

    $division = getCurrentDivision();
    $sql = "SELECT * FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'";
    if ($division) {
        $sql .= " AND category = ? ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $or_number, $branch, $division);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $or_number, $branch);
    }
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

    $sales_force = !empty($items) ? ($items[0]['sales_force'] ?? '') : '';

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
            'sales_force' => $sales_force,
            'items' => $items
        ]
    ]);
}

function getTransfersList()
{
    global $conn, $currentBranch, $isAdmin;

    $seeAll = $isAdmin; // Only Admin can see everything, regular users (even at headoffice) see their own
    $where = $seeAll ? "1=1" : "st.from_branch = ?";
    $sql = "SELECT st.id, st.transfer_no, st.from_branch, st.to_branch, st.transfer_date, st.status, 
                   COALESCE(SUM(sti.quantity), 0) as item_count 
            FROM spareparts_transfers st
            LEFT JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
            WHERE $where
            GROUP BY st.id, st.transfer_no, st.from_branch, st.to_branch, st.transfer_date, st.status
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
    $seeAll = $isAdmin;


    // Auto-migrate schema to add status if missing
    try {
        @$conn->query("ALTER TABLE spareparts_transfer_items ADD COLUMN status VARCHAR(20) DEFAULT 'In-Transit'");
    } catch (Exception $e) {
    }

    // Get transfers waiting for acceptance (to this specific location)
    $where = $seeAll ? "st.status = 'In-Transit'" : "st.to_branch = ? AND st.status = 'In-Transit'";
    $sql = "SELECT st.id, st.transfer_no, st.from_branch, st.to_branch, st.transfer_date, st.status, 
                   SUM(sti.quantity) as item_count 
            FROM spareparts_transfers st
            LEFT JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id AND (sti.status = 'In-Transit' OR sti.status IS NULL)
            WHERE $where
            GROUP BY st.id, st.transfer_no, st.from_branch, st.to_branch, st.transfer_date, st.status
            HAVING item_count > 0
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
    $sql = "SELECT st.id, st.transfer_no, st.from_branch, st.to_branch, st.transfer_date, st.received_date, st.status, 
                   COALESCE(SUM(sti.quantity), 0) as item_count 
            FROM spareparts_transfers st
            LEFT JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
            $where
            GROUP BY st.id, st.transfer_no
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

    $division = getCurrentDivision();
    $whereClause = $seeAll ? "WHERE (customer_name LIKE ? OR or_number LIKE ?)" : "WHERE (customer_name LIKE ? OR or_number LIKE ?) AND branch = ?";
    $params = [$searchTerm, $searchTerm];
    $types = "ss";

    if (!$seeAll) {
        $params[] = $currentBranch;
        $types .= "s";
    }

    if (!$seeAll && $division) {
        $whereClause .= " AND category = ?";
        $params[] = $division;
        $types .= "s";
    }

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

    // 1. Search in spareparts_customers table (priority for rank info)
    $division = getCurrentDivision();
    // Relaxed division filter to show both categorized and legacy (uncategorized) customers
    $divFilter = (!$seeAll && $division) ? " AND (category = '$division' OR category IS NULL OR category = '') " : "";

    $sql_cust = "SELECT name AS customer_name, rank_level, credit_limit,
                 (SELECT SUM(balance) FROM spareparts_aging WHERE customer_name = spareparts_customers.name AND branch = spareparts_customers.branch AND status = 'Active') as current_balance
                 FROM spareparts_customers WHERE name LIKE ? " . ($seeAll ? "" : "AND branch = ?") . $divFilter . " LIMIT 10";
    $stmt_cust = $conn->prepare($sql_cust);
    if ($seeAll) $stmt_cust->bind_param('s', $searchTerm);
    else $stmt_cust->bind_param('ss', $searchTerm, $currentBranch);
    $stmt_cust->execute();
    $res_cust = $stmt_cust->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Search in transactions for historical names
    $whereBranch = $seeAll ? "" : "AND from_location = ?";
    if (!$seeAll && $division) {
        $whereBranch .= " AND (category = ? OR category IS NULL OR category = '')";
        $stmt_trans = $conn->prepare("SELECT DISTINCT customer_name FROM spareparts_transactions WHERE customer_name LIKE ? $whereBranch LIMIT 5");
        $stmt_trans->bind_param('sss', $searchTerm, $currentBranch, $division);
    } else {
        $stmt_trans = $conn->prepare("SELECT DISTINCT customer_name FROM spareparts_transactions WHERE customer_name LIKE ? $whereBranch LIMIT 5");
        if ($seeAll) $stmt_trans->bind_param('s', $searchTerm);
        else $stmt_trans->bind_param('ss', $searchTerm, $currentBranch);
    }
    $stmt_trans->execute();
    $res_trans = $stmt_trans->get_result()->fetch_all(MYSQLI_ASSOC);

    $names = [];
    $final = [];
    foreach ($res_cust as $c) {
        $names[] = strtolower($c['customer_name']);
        $c['current_balance'] = (float)($c['current_balance'] ?? 0);
        $final[] = $c;
    }
    foreach ($res_trans as $t) {
        if (!in_array(strtolower($t['customer_name']), $names)) {
            // Fetch balance even for names not in customers table
            $bStmt = $conn->prepare("SELECT SUM(balance) as b FROM spareparts_aging WHERE customer_name = ? AND status = 'Active' " . ($seeAll ? "" : "AND branch = ?"));
            if ($seeAll) $bStmt->bind_param('s', $t['customer_name']);
            else $bStmt->bind_param('ss', $t['customer_name'], $currentBranch);
            $bStmt->execute();
            $currBal = $bStmt->get_result()->fetch_assoc()['b'] ?? 0;
            $final[] = ['customer_name' => $t['customer_name'], 'rank_level' => 'Standard', 'credit_limit' => 0, 'current_balance' => (float)$currBal];
        }
    }

    echo json_encode(['success' => true, 'data' => $final]);
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

    $sql = "SELECT transaction_date, or_number, transfer_no, part_no, description, customer_name, type, quantity, price, total_amount, from_location, to_location, status, reason 
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
    $transferNo = sanitizeInput($_POST['transfer_no'] ?? '');
    $items = json_decode($_POST['items'], true);

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items selected for transfer.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $transferStmt = $conn->prepare("INSERT INTO spareparts_transfers (from_branch, to_branch, transfer_date, status, transfer_no) VALUES (?, ?, ?, 'In-Transit', ?)");
        $transferStmt->bind_param('ssss', $currentBranch, $toBranch, $transferDate, $transferNo);
        if (!$transferStmt->execute())
            throw new Exception('Failed to create transfer record.');
        $transferId = $conn->insert_id;

        foreach ($items as $item) {
            $pno = sanitizeInput($item['part_no']);
            $qty = (int) $item['quantity'];
            $desc = sanitizeInput($item['description']);
            $provided_cost = isset($item['cost']) ? (float)$item['cost'] : null;

            if ($provided_cost !== null) {
                $cost = $provided_cost;
            } else {
                $costStmt = $conn->prepare("SELECT cost FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
                $costStmt->bind_param('ss', $pno, $currentBranch);
                $costStmt->execute();
                $costRow = $costStmt->get_result()->fetch_assoc();
                $cost = $costRow ? (float) $costRow['cost'] : 0;
            }

            $updStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
            $updStmt->bind_param('iss', $qty, $pno, $currentBranch);
            $updStmt->execute();

            $liStmt = $conn->prepare("INSERT INTO spareparts_transfer_items (transfer_id, part_no, description, quantity, cost) VALUES (?, ?, ?, ?, ?)");
            $liStmt->bind_param('issid', $transferId, $pno, $desc, $qty, $cost);
            $liStmt->execute();

            $txStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status, transfer_no) 
                                     VALUES (?, 'TRANSFER_OUT', ?, ?, ?, ?, ?, ?, ?, 'In-Transit', ?)");
            $total_cost = $qty * $cost;
            $txStmt->bind_param('sssiddsss', $transferDate, $pno, $desc, $qty, $cost, $total_cost, $currentBranch, $toBranch, $transferNo);
            $txStmt->execute();
        }
        $conn->commit();
        addAuditLog('INSERT', 'spareparts_transfers', $transferId, "Initiated Transfer ID $transferId (No: $transferNo): $currentBranch -> $toBranch with " . count($items) . " items");
        echo json_encode(['success' => true, 'message' => 'Transfer initiated successfully.', 'transfer_id' => $transferId]);
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
        addAuditLog('UPDATE', 'spareparts_transfers', $transfer_id, "Cancelled Transfer ID $transfer_id at $currentBranch");
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
                                    cost = VALUES(cost),
                                    price = IF(VALUES(price) > 0, VALUES(price), price),
                                    brand = IF(VALUES(brand) != '', VALUES(brand), brand)");
            $stmt->bind_param('sssidds', $brand, $part_no, $description, $quantity, $cost, $price, $min_stock, $currentBranch);
            $stmt->execute();

            // Log TRANSFER_IN
            $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status, transfer_no) 
                                       VALUES (CURDATE(), 'TRANSFER_IN', ?, ?, ?, ?, ?, ?, ?, 'Completed', ?)");
            $total_amount = $quantity * $cost;
            // Get transfer_no from transfer record
            $tNoQuery = "SELECT transfer_no FROM spareparts_transfers WHERE id = ?";
            $tNoStmt = $conn->prepare($tNoQuery);
            $tNoStmt->bind_param('i', $transferId);
            $tNoStmt->execute();
            $tNo = $tNoStmt->get_result()->fetch_assoc()['transfer_no'] ?? '';
            
            $logStmt->bind_param('ssiddssss', $part_no, $description, $quantity, $cost, $total_amount, $from_branch, $currentBranch, $tNo);
            $logStmt->execute();
        }

        // Update transfer status
        $updateStmt = $conn->prepare("UPDATE spareparts_transfers SET status = 'Completed', received_date = NOW() WHERE id = ? AND to_branch = ?");
        $updateStmt->bind_param('is', $transferId, $currentBranch);
        $updateStmt->execute();

        $conn->commit();
        addAuditLog('UPDATE', 'spareparts_transfers', $transferId, "Accepted Transfer ID $transferId at $currentBranch from $from_branch");
        echo json_encode(['success' => true, 'message' => 'Transfer accepted and items added to inventory.', 'transfer_id' => $transferId, 'from_branch' => $from_branch]);
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
        addAuditLog('UPDATE', 'spareparts_transfers', $transferId, "Rejected Transfer ID $transferId at $currentBranch (from $transfer[from_branch])");
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
    $invoice_no = sanitizeInput($_POST['invoice_no'] ?? '');
    $supplier = sanitizeInput($_POST['supplier_source'] ?? 'SUPPLIER');
    $hikes = [];

    // Auto-create table if missing
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS spareparts_price_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            part_no VARCHAR(100) NOT NULL,
            cost DECIMAL(10,2) NOT NULL,
            supplier VARCHAR(255),
            invoice_no VARCHAR(100),
            transaction_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
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
    } catch(Exception $e) {}

    $conn->begin_transaction();
    try {
        $totalItemsAmount = 0;
        foreach ($items as $item) {
            $totalItemsAmount += ((int)$item['quantity'] * (float)$item['cost']);
        }

        $paymentMode = sanitizeInput($_POST['payment_mode'] ?? 'Cash');
        if ($paymentMode === 'Charge' && $totalItemsAmount > 0) {
            $stmt = $conn->prepare("INSERT INTO spareparts_supplier_aging (supplier_name, invoice_no, total_amount, balance, date_received, branch) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssddss', $supplier, $invoice_no, $totalItemsAmount, $totalItemsAmount, $date, $currentBranch);
            $stmt->execute();
        }

        foreach ($items as $item) {
            $brand       = sanitizeInput($item['brand'] ?? '');
            $part_no     = sanitizeInput($item['part_no']);
            $description = sanitizeInput($item['description']);
            $quantity    = (int) $item['quantity'];
            $new_cost    = (float) $item['cost'];
            $price       = (float) ($item['price'] ?? 0);
            $is_new      = !empty($item['isNew']);

            // Fetch Previous Cost from master inventory
            $prevStmt = $conn->prepare("SELECT cost FROM spareparts_inventory WHERE part_no = ? LIMIT 1");
            $prevStmt->bind_param('s', $part_no);
            $prevStmt->execute();
            $prevRes = $prevStmt->get_result()->fetch_assoc();
            $old_cost = $prevRes ? (float) $prevRes['cost'] : 0;

            // Price Hike Detection
            if (!$is_new && $old_cost > 0 && $new_cost > $old_cost) {
                $increase = (($new_cost - $old_cost) / $old_cost) * 100;
                $hikes[] = [
                    'part_no'  => $part_no,
                    'old_cost' => $old_cost,
                    'new_cost' => $new_cost,
                    'increase' => round($increase, 2)
                ];
            }

            // UPSERT inventory:
            $stmt = $conn->prepare(
                "INSERT INTO spareparts_inventory (brand, part_no, description, current_stock, cost, price, current_branch)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     current_stock = current_stock + VALUES(current_stock),
                     cost          = VALUES(cost),
                     price         = IF(VALUES(price) > 0, VALUES(price), price),
                     brand         = IF(VALUES(brand) != '' AND VALUES(brand) != 'N/A', VALUES(brand), brand),
                     description   = IF(VALUES(description) != '', VALUES(description), description)"
            );
            $stmt->bind_param('sssidds', $brand, $part_no, $description, $quantity, $new_cost, $price, $currentBranch);
            $stmt->execute();

            // Always log cost history
            $histCheck = $conn->query("SHOW COLUMNS FROM spareparts_price_history LIKE 'part_no'");
            if ($histCheck && $histCheck->num_rows > 0) {
                $histStmt = $conn->prepare(
                    "INSERT INTO spareparts_price_history (part_no, cost, supplier, invoice_no, transaction_date) VALUES (?, ?, ?, ?, ?)"
                );
            } else {
                $histStmt = $conn->prepare(
                    "INSERT INTO spareparts_price_history (part_number, cost, supplier, invoice_no, transaction_date) VALUES (?, ?, ?, ?, ?)"
                );
            }
            $histStmt->bind_param('sdsss', $part_no, $new_cost, $supplier, $invoice_no, $date);
            $histStmt->execute();

            // Log IN transaction
            $total = $quantity * $new_cost;
            $log = $conn->prepare(
                "INSERT INTO spareparts_transactions
                    (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, or_number, status, payment_method)
                 VALUES (?, 'IN', ?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?)"
            );
            $log->bind_param('sssiddssss', $date, $part_no, $description, $quantity, $new_cost, $total, $supplier, $currentBranch, $invoice_no, $paymentMode);
            $log->execute();
        }

        $conn->commit();
        addAuditLog(
            'INSERT', 'spareparts_inventory',
            $invoice_no ?: 'MULTIPLE',
            "Stock IN: " . count($items) . " part(s) via Invoice: $invoice_no | Supplier: $supplier | Branch: $currentBranch"
        );
        echo json_encode(['success' => true, 'message' => 'Stock received and inventory updated.', 'hikes' => $hikes]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to add stock: ' . $e->getMessage()]);
    }
}

function searchPartsForIn()
{
    global $conn, $currentBranch, $isAdmin;
    $query = sanitizeInput($_GET['query'] ?? '');

    if (strlen($query) < 1) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }

    $searchTerm = "%$query%";
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $whereBranch = $seeAll ? "" : " AND current_branch = ?";

    // Search specifically within the branch if not Head Office/Admin
    $sql = "SELECT part_no, description, brand, cost, price,
                SUM(current_stock) as current_stock
         FROM spareparts_inventory
         WHERE (part_no LIKE ? OR description LIKE ? OR brand LIKE ?) $whereBranch
         GROUP BY part_no
         ORDER BY
             CASE WHEN part_no LIKE ? THEN 0 ELSE 1 END,
             part_no ASC
         LIMIT 15";
         
    $stmt = $conn->prepare($sql);
    if ($seeAll) {
        $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    } else {
        $stmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $currentBranch, $searchTerm);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $data]);
}

function sellMultiplePartsOut()
{
    global $conn, $currentBranch;

    // Auto-migrate: add required columns if missing
    $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS sales_force VARCHAR(150) DEFAULT NULL");
    $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL");
    $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS check_date DATE DEFAULT NULL");

    $items = json_decode($_POST['items'], true);
    $or_number = sanitizeInput($_POST['or_number']);
    $customer_name = sanitizeInput($_POST['customer_name']);
    $date = sanitizeInput($_POST['date']);
    $transaction_type = sanitizeInput($_POST['transaction_type']);
    $sales_force = sanitizeInput($_POST['sales_force'] ?? '');
    
    // Captured fields for PDC
    $payment_method = sanitizeInput($_POST['payment_method'] ?? 'Cash');
    $check_date = !empty($_POST['check_date']) ? sanitizeInput($_POST['check_date']) : NULL;

    // If payment method is PDC, it defaults to a charge transaction for aging
    if ($payment_method === 'PDC') {
        $transaction_type = 'charge';
    }

    // Check if OR already exists
    $checkStmt = $conn->prepare("SELECT or_number FROM spareparts_transactions WHERE or_number = ? LIMIT 1");
    $checkStmt->bind_param('s', $or_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Duplicate OR: Receipt Number $or_number already exists."]);
        return;
    }

    // Server-side Credit Limit Validation for Charge/PDC sales
    if ($transaction_type === 'charge' || $payment_method === 'PDC') {
        // Fetch customer credit limit
        $stmt_l = $conn->prepare("SELECT credit_limit FROM spareparts_customers WHERE name = ? AND branch = ? LIMIT 1");
        $stmt_l->bind_param('ss', $customer_name, $currentBranch);
        $stmt_l->execute();
        $res_l = $stmt_l->get_result()->fetch_assoc();
        $limit = (float)($res_l['credit_limit'] ?? 0);

        if ($limit > 0) {
            // Fetch current outstanding balance
            $stmt_b = $conn->prepare("SELECT SUM(balance) as current_balance FROM spareparts_aging WHERE customer_name = ? AND status = 'Active' AND branch = ?");
            $stmt_b->bind_param('ss', $customer_name, $currentBranch);
            $stmt_b->execute();
            $res_b = $stmt_b->get_result()->fetch_assoc();
            $current_balance = (float)($res_b['current_balance'] ?? 0);

            $total_sale_est = 0;
            foreach ($items as $item) {
                $total_sale_est += ($item['quantity'] * $item['price']);
            }

            if (($current_balance + $total_sale_est) > $limit) {
                $excess = ($current_balance + $total_sale_est) - $limit;
                echo json_encode([
                    'success' => false, 
                    'message' => "Credit Limit Exceeded! \nLimit: PHP " . number_format($limit, 2) . "\nCurrent Bal: PHP " . number_format($current_balance, 2) . "\nThis Sale: PHP " . number_format($total_sale_est, 2) . "\nExceeds by PHP " . number_format($excess, 2)
                ]);
                return;
            }
        }
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
            $division = getCurrentDivision();
            $stmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, or_number, customer_name, transaction_type, type, part_no, description, quantity, price, total_amount, from_location, sales_force, category, payment_method, check_date) 
                                    VALUES (?, ?, ?, ?, 'OUT', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssssiddsssss', $date, $or_number, $customer_name, $transaction_type, $part_no, $description, $quantity, $price, $subtotal, $currentBranch, $sales_force, $division, $payment_method, $check_date);
            $stmt->execute();
        }

        // Add to aging if charge
        if ($transaction_type === 'charge') {
            $stmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, customer_name, sale_date, total_amount, balance, branch, category) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssddss', $or_number, $customer_name, $date, $total_sale_amount, $total_sale_amount, $currentBranch, $division);
            $stmt->execute();

            // Save PDC details if payment method is PDC
            if ($payment_method === 'PDC') {
                $pdc_bank = sanitizeInput($_POST['pdc_bank'] ?? '');
                $pdc_check_no = sanitizeInput($_POST['pdc_check_no'] ?? '');
                $pdc_maturity_date = sanitizeInput($_POST['pdc_maturity_date'] ?? '');
                $pdc_amount = !empty($_POST['pdc_amount']) ? (float)$_POST['pdc_amount'] : $total_sale_amount;
                $pdc_remarks = sanitizeInput($_POST['pdc_remarks'] ?? '');
                
                // Get customer ID
                $stmt_cid = $conn->prepare("SELECT id FROM spareparts_customers WHERE name = ? AND branch = ? LIMIT 1");
                $stmt_cid->bind_param('ss', $customer_name, $currentBranch);
                $stmt_cid->execute();
                $cid_res = $stmt_cid->get_result()->fetch_assoc();
                $customer_id = (int)($cid_res['id'] ?? 0);

                $encoded_by = $_SESSION['username'] ?? 'System';

                $stmt_pdc = $conn->prepare("INSERT INTO spareparts_pdc_payments (customer_id, customer_name, bank_name, check_no, check_date, amount, status, branch, encoded_by, or_number, remarks) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)");
                $stmt_pdc->bind_param('issssdssss', $customer_id, $customer_name, $pdc_bank, $pdc_check_no, $pdc_maturity_date, $pdc_amount, $currentBranch, $encoded_by, $or_number, $pdc_remarks);
                $stmt_pdc->execute();
            }
        }

        $conn->commit();
        addAuditLog('INSERT', 'spareparts_transactions', $or_number, "Recorded Sale: OR $or_number, Customer: $customer_name, Type: $transaction_type, Sales Force: $sales_force, Total: $total_sale_amount");
        echo json_encode(['success' => true, 'message' => 'Sale recorded successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to record sale: ' . $e->getMessage()]);
    }
}

// ===================== SALES FORCE CRUD =====================
function getSalesForce()
{
    global $conn, $currentBranch, $isAdmin;
    // Auto-create table if missing
    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_sales_force (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch VARCHAR(100) NOT NULL,
        employee_name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_emp_branch (branch, employee_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Auto-migrate: add sales_force column to transactions if missing
    $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS sales_force VARCHAR(150) DEFAULT NULL");

    $division = getCurrentDivision();
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';

    if ($seeAll) {
        $stmt = $conn->prepare("
            SELECT 
                sf.id, 
                sf.branch, 
                sf.employee_name, 
                sf.position,
                sf.created_at,
                COUNT(DISTINCT t.id) as total_sales
            FROM spareparts_sales_force sf
            LEFT JOIN spareparts_transactions t ON sf.employee_name = t.sales_force AND sf.branch = t.from_location AND t.type = 'OUT'
            GROUP BY sf.id, sf.branch, sf.employee_name, sf.position, sf.created_at
            ORDER BY sf.employee_name ASC
        ");
    } else if ($division) {
        $stmt = $conn->prepare("
            SELECT 
                sf.id, 
                sf.branch, 
                sf.employee_name, 
                sf.position,
                sf.created_at,
                COUNT(DISTINCT t.id) as total_sales
            FROM spareparts_sales_force sf
            LEFT JOIN spareparts_transactions t ON sf.employee_name = t.sales_force AND sf.branch = t.from_location AND t.type = 'OUT'
            WHERE sf.branch = ? AND sf.category = ?
            GROUP BY sf.id, sf.branch, sf.employee_name, sf.position, sf.created_at
            ORDER BY sf.employee_name ASC
        ");
        $stmt->bind_param('ss', $currentBranch, $division);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                sf.id, 
                sf.branch, 
                sf.employee_name, 
                sf.position,
                sf.created_at,
                COUNT(DISTINCT t.id) as total_sales
            FROM spareparts_sales_force sf
            LEFT JOIN spareparts_transactions t ON sf.employee_name = t.sales_force AND sf.branch = t.from_location AND t.type = 'OUT'
            WHERE sf.branch = ?
            GROUP BY sf.id, sf.branch, sf.employee_name, sf.position, sf.created_at
            ORDER BY sf.employee_name ASC
        ");
        $stmt->bind_param('s', $currentBranch);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function addSalesForce()
{
    global $conn, $currentBranch;
    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_sales_force (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch VARCHAR(100) NOT NULL,
        employee_name VARCHAR(150) NOT NULL,
        position VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_emp_branch (branch, employee_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $employee = trim($_POST['employee_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee name required.']);
        return;
    }
    $division = getCurrentDivision();
    $stmt = $conn->prepare("INSERT INTO spareparts_sales_force (branch, employee_name, position, category) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $currentBranch, $employee, $position, $division);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Employee added.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee already exists in this branch.']);
    }
}

function editSalesForce()
{
    global $conn, $currentBranch, $isAdmin;
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['employee_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    if (!$id || !$name) {
        echo json_encode(['success' => false, 'message' => 'ID and name required.']);
        return;
    }

    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    
    if ($seeAll) {
        $stmt = $conn->prepare("UPDATE spareparts_sales_force SET employee_name = ?, position = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $position, $id);
    } else {
        $stmt = $conn->prepare("UPDATE spareparts_sales_force SET employee_name = ?, position = ? WHERE id = ? AND branch = ?");
        $stmt->bind_param("ssis", $name, $position, $id, $currentBranch);
    }
    $stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Employee updated.']);
}

function deleteSalesForce()
{
    global $conn, $currentBranch, $isAdmin;
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); return; }
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    
    if ($seeAll) {
        $stmt = $conn->prepare("DELETE FROM spareparts_sales_force WHERE id = ?");
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $conn->prepare("DELETE FROM spareparts_sales_force WHERE id = ? AND branch = ?");
        $stmt->bind_param('is', $id, $currentBranch);
    }
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0, 'message' => $stmt->affected_rows > 0 ? 'Deleted.' : 'Not found.']);
}

function searchSalesForce()
{
    global $conn, $currentBranch, $isAdmin;
    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_sales_force (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch VARCHAR(100) NOT NULL,
        employee_name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_emp_branch (branch, employee_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $division = getCurrentDivision();
    $term = '%' . sanitizeInput($_GET['term'] ?? '') . '%';
    if (!$isAdmin && $division) {
        $stmt = $conn->prepare("SELECT id, employee_name, position FROM spareparts_sales_force WHERE branch = ? AND employee_name LIKE ? AND category = ? ORDER BY employee_name ASC LIMIT 10");
        $stmt->bind_param('sss', $currentBranch, $term, $division);
    } else {
        $stmt = $conn->prepare("SELECT id, employee_name, position FROM spareparts_sales_force WHERE branch = ? AND employee_name LIKE ? ORDER BY employee_name ASC LIMIT 10");
        $stmt->bind_param('ss', $currentBranch, $term);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

// ===================== PRICELIST MANAGEMENT =====================
function getPricelists()
{
    global $conn, $currentBranch;
    // Auto-create table if missing
    $conn->query("CREATE TABLE IF NOT EXISTS spareparts_pricelists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        part_no VARCHAR(100) NOT NULL,
        rank_level VARCHAR(50) NOT NULL,
        price DECIMAL(15,2) NOT NULL,
        branch VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY part_rank_branch (part_no, rank_level, branch)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $query = sanitizeInput($_GET['query'] ?? '');
    $searchTerm = "%$query%";
    // Robustness: ensure column exists if table was already created
    $chk = $conn->query("SHOW COLUMNS FROM spareparts_pricelists LIKE 'updated_at'");
    if ($chk && $chk->num_rows == 0) {
        $conn->query("ALTER TABLE spareparts_pricelists ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    $searchTerm = "%" . sanitizeInput($_GET['query'] ?? '') . "%";
    
    $sql = "SELECT p.*, i.description, i.brand 
            FROM spareparts_pricelists p
            JOIN spareparts_inventory i ON p.part_no = i.part_no AND p.branch = i.current_branch
            WHERE p.branch = ? AND (p.part_no LIKE ? OR i.description LIKE ? OR p.rank_level LIKE ? OR i.brand LIKE ?)
            ORDER BY p.updated_at DESC, p.part_no ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssss', $currentBranch, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
}

function savePricelist()
{
    global $conn, $currentBranch;
    $part_no = sanitizeInput($_POST['part_no'] ?? '');
    $rank_level = sanitizeInput($_POST['rank_level'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);

    if (empty($part_no) || empty($rank_level) || $price <= 0) {
        echo json_encode(['success' => false, 'message' => 'All fields are required and price must be greater than 0.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO spareparts_pricelists (part_no, rank_level, price, branch) 
                            VALUES (?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE price = VALUES(price), updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param('ssds', $part_no, $rank_level, $price, $currentBranch);
    $stmt->execute();
    
    addAuditLog('SAVE_PRICELIST', 'spareparts_pricelists', $part_no, "Set Price for $part_no Rank $rank_level at $currentBranch: $price");
    
    echo json_encode(['success' => true, 'message' => "Price for $part_no rank $rank_level saved!"]);
}

function deletePricelist()
{
    global $conn, $currentBranch;
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); return; }
    $stmt = $conn->prepare("DELETE FROM spareparts_pricelists WHERE id = ? AND branch = ?");
    $stmt->bind_param('is', $id, $currentBranch);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0, 'message' => $stmt->affected_rows > 0 ? 'Deleted.' : 'Not found.']);
}

function saveBulkPricelists()
{
    global $conn, $currentBranch;
    $rank_level = sanitizeInput($_POST['rank_level'] ?? '');
    $items = $_POST['items'] ?? []; // Array of objects {part_no, price}

    if (empty($rank_level) || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Rank and at least one item are required.']);
        return;
    }

    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            $part_no = sanitizeInput($item['part_no']);
            $price = (float)$item['price'];

            $stmt = $conn->prepare("INSERT INTO spareparts_pricelists (part_no, rank_level, price, branch) 
                                    VALUES (?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE price = VALUES(price), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param('ssds', $part_no, $rank_level, $price, $currentBranch);
            $stmt->execute();
            addAuditLog('SAVE_PRICELIST', 'spareparts_pricelists', $part_no, "Bulk Set Price for $part_no Rank $rank_level at $currentBranch: $price");
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => count($items) . ' rank prices saved successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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

        $division = getCurrentDivision();
        // Fetch active accounts for this customer or specific OR
        $sql = "SELECT id, or_number, customer_name, balance FROM spareparts_aging WHERE status = 'Active' ";
        if ($division) {
            $sql .= " AND category = '$division' ";
        }
        
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

            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, or_number, customer_name, type, total_amount, from_location, transaction_type, payment_method, check_number, bank_name, reference_number, category) 
                                    VALUES (?, ?, ?, 'PAYMENT', ?, ?, ?, ?, ?, ?, ?, ?)");
            $log->bind_param('sssdsssssss', $date, $receipt_no, $acc['customer_name'], $payAmount, $branch, $acc['or_number'], $payment_method, $check_number, $bank_name, $reference_number, $division);
            $log->execute();
        }

        $conn->commit();
        addAuditLog('INSERT', 'spareparts_transactions', $receipt_no, "Recorded Payment: Receipt $receipt_no, Customer: $customerToLog, Amount: $amount for OR(s): $or_number at $branch");
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
        addAuditLog('UPDATE', 'spareparts_transactions', $payment_id, "Updated Payment ID $payment_id: Receipt $new_receipt, New Amount: $new_amount");
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

function addPart()
{
    global $conn, $currentBranch;

    // Auto-migrate: add part_image column if missing
    $conn->query("ALTER TABLE spareparts_inventory ADD COLUMN IF NOT EXISTS part_image VARCHAR(255) DEFAULT NULL");

    $part_no = sanitizeInput($_POST['part_no'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $brand = sanitizeInput($_POST['brand'] ?? '');
    $stock = (int) ($_POST['stock'] ?? 0);
    $min_stock = (int) ($_POST['min_stock'] ?? 5);
    $cost = (float) ($_POST['cost'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $bin_location = sanitizeInput($_POST['bin_location'] ?? '');

    if (empty($part_no) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Part Number and Description are required.']);
        return;
    }

    // Check if part already exists in THIS branch
    $check = $conn->prepare("SELECT id FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
    $check->bind_param('ss', $part_no, $currentBranch);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Part $part_no already exists in $currentBranch."]);
        return;
    }

    $image_path = null;
    if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] == 0) {
        $target_dir = '../uploads/parts_images/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_ext = pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION);
        $file_name = $part_no . '_' . time() . '.' . $file_ext;
        $image_path = 'uploads/parts_images/' . $file_name;
        move_uploaded_file($_FILES['part_image']['tmp_name'], $target_dir . $file_name);
    }

    $sql = "INSERT INTO spareparts_inventory (part_no, description, brand, current_stock, min_stock, cost, price, bin_location, current_branch, part_image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssiiddsss', $part_no, $description, $brand, $stock, $min_stock, $cost, $price, $bin_location, $currentBranch, $image_path);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        
        // --- Added: Log initial stock into transactions history so it reflects in stock cards ---
        if ($stock > 0) {
            $total = $stock * $cost;
            $username = $_SESSION['username'] ?? 'System';
            $log = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, or_number, status) VALUES (CURDATE(), 'IN', ?, ?, ?, ?, ?, 'Initial Encoding', ?, 'ENCODE', 'Completed')");
            $log->bind_param('ssidds', $part_no, $description, $stock, $cost, $total, $currentBranch);
            $log->execute();
        }
        
        addAuditLog('INSERT', 'spareparts_inventory', $part_no, "Added New Part: $part_no ($description) with initial stock $stock");
        echo json_encode(['success' => true, 'message' => 'Part registered successfully!', 'id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
}

function editPart()
{
    global $conn, $currentBranch, $isAdmin;
    
    // Auto-create columns if missing
    try { $conn->query("ALTER TABLE spareparts_inventory ADD COLUMN image_url VARCHAR(255) DEFAULT NULL"); } catch(Exception $e) {}
    try { $conn->query("ALTER TABLE spareparts_inventory ADD COLUMN bin_location VARCHAR(100) DEFAULT NULL"); } catch(Exception $e) {}
    
    $id = (int) $_POST['id'];
    $description = sanitizeInput($_POST['description']);
    $cost = (float) $_POST['cost'];
    $price = (float) $_POST['price'];
    $min_stock = (int) $_POST['min_stock'];
    $invoice_no = sanitizeInput($_POST['invoice_no'] ?? '');
    $bin_location = sanitizeInput($_POST['bin_location'] ?? '');

    $brand = sanitizeInput($_POST['brand'] ?? '');
    $part_no = sanitizeInput($_POST['part_no'] ?? '');
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : null;
    $branch = sanitizeInput($_POST['branch'] ?? '');

    if (!$isAdmin) {
        $branch = $currentBranch;
    }

    $change_reason = sanitizeInput($_POST['change_reason'] ?? '');
    
    // Handle Image Upload
    $image_url = null;
    if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/img/parts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", basename($_FILES['part_image']['name']));
        $uploadFile = $uploadDir . $filename;
        
        $check = getimagesize($_FILES["part_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['part_image']['tmp_name'], $uploadFile)) {
                $image_url = 'assets/img/parts/' . $filename;
            }
        }
    }

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

        // Build base SQL and params based on whether image is updated
        $imgSql = $image_url ? ", image_url = ?" : "";
        
        if ($isAdmin) {
            $sql = "UPDATE spareparts_inventory SET description = ?, cost = ?, price = ?, min_stock = ?, brand = ?, part_no = ?, current_stock = ?, current_branch = ?, invoice_no = ?, bin_location = ? $imgSql WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($image_url) {
                $stmt->bind_param('sddississssi', $description, $cost, $price, $min_stock, $brand, $part_no, $stock, $branch, $invoice_no, $bin_location, $image_url, $id);
            } else {
                $stmt->bind_param('sddississsi', $description, $cost, $price, $min_stock, $brand, $part_no, $stock, $branch, $invoice_no, $bin_location, $id);
            }
        } else {
            $sql = "UPDATE spareparts_inventory SET description = ?, cost = ?, price = ?, min_stock = ?, brand = ?, part_no = ?, current_stock = ?, invoice_no = ?, bin_location = ? $imgSql WHERE id = ? AND current_branch = ?";
            $stmt = $conn->prepare($sql);
            if ($image_url) {
                $stmt->bind_param('sddississsis', $description, $cost, $price, $min_stock, $brand, $part_no, $stock, $invoice_no, $bin_location, $image_url, $id, $currentBranch);
            } else {
                $stmt->bind_param('sddississis', $description, $cost, $price, $min_stock, $brand, $part_no, $stock, $invoice_no, $bin_location, $id, $currentBranch);
            }
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to update part in inventory.");
        }

        // Log manual adjustment if stock changed
        if ($stock !== null && $stock != $oldStock) {
            $qtyDiff = $stock - $oldStock;
            $adjStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, transaction_type, type, brand, part_no, description, quantity, price, total_amount, from_location, reason) VALUES (CURDATE(), 'Manual Adjustment', 'ADJUSTMENT', ?, ?, ?, ?, ?, ?, ?, ?)");
            $tAmount = $qtyDiff * $price;
            $adjStmt->bind_param('sssiddss', $brand, $part_no, $description, $qtyDiff, $price, $tAmount, $originBranch, $change_reason);
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
        addAuditLog('UPDATE', 'spareparts_inventory', $id, "Updated Part $part_no ($description) at $branch" . ($stock !== null ? " (Stock Adj: $oldStock -> $stock)" : ""));
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
    $transaction_type = sanitizeInput($_POST['transaction_type'] ?? 'cash');
    $sales_force = sanitizeInput($_POST['sales_force'] ?? '');
    $reason = sanitizeInput($_POST['reason'] ?? '');
    $items = json_decode($_POST['items'], true);

    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'A reason for revision is required.']);
        return;
    }

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Sale must have at least one item.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // 1. Check for duplicate SI# if changed
        if ($new_or !== $original_or) {
            $stmt = $conn->prepare("SELECT id FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT' LIMIT 1");
            $stmt->bind_param('ss', $new_or, $new_branch);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Duplicate SI # already exists in this branch: $new_or");
            }
        }

        // 2. Return stock for old items
        $stmt = $conn->prepare("SELECT part_no, quantity FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
        $stmt->bind_param('ss', $original_or, $original_branch);
        $stmt->execute();
        $old_items_res = $stmt->get_result();
        while ($old_item = $old_items_res->fetch_assoc()) {
            $refill = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
            $refill->bind_param('iss', $old_item['quantity'], $old_item['part_no'], $original_branch);
            $refill->execute();
        }

        // 3. Delete old transaction records
        $stmt = $conn->prepare("DELETE FROM spareparts_transactions WHERE or_number = ? AND from_location = ? AND type = 'OUT'");
        $stmt->bind_param('ss', $original_or, $original_branch);
        $stmt->execute();

        // 4. Process new items
        $total_sale_amount = 0;
        foreach ($items as $item) {
            $pno = sanitizeInput($item['part_no']);
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $desc = sanitizeInput($item['description'] ?? '');
            $subtotal = $qty * $price;
            $total_sale_amount += $subtotal;

            // Deduct stock for new item
            $deduct = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
            $deduct->bind_param('iss', $qty, $pno, $new_branch);
            $deduct->execute();

            // Insert new transaction record
            $ins = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, transaction_type, or_number, customer_name, part_no, description, quantity, price, total_amount, from_location, sales_force, reason) 
                                   VALUES (?, 'OUT', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param('ssssssiddsss', $sale_date, $transaction_type, $new_or, $customer_name, $pno, $desc, $qty, $price, $subtotal, $new_branch, $sales_force, $reason);
            $ins->execute();
        }

        // 5. Update Aging record
        // - Delete old aging if it exists
        $conn->query("DELETE FROM spareparts_aging WHERE or_number = '$original_or' AND branch = '$original_branch'");

        // - Create new if charge
        if ($transaction_type === 'charge') {
            // Check if there are payments already for this OR (if OR was same but type became charge)
            $stmt = $conn->prepare("SELECT SUM(amount_paid) as total_paid FROM spareparts_payments WHERE (or_number = ? AND branch = ?) OR (or_number = ? AND branch = ?)");
            $stmt->bind_param('ssss', $new_or, $new_branch, $original_or, $original_branch);
            $stmt->execute();
            $paid_res = $stmt->get_result()->fetch_assoc();
            $total_paid = (float)($paid_res['total_paid'] ?? 0);
            
            // If OR changed, update payments to point to new OR
            if ($new_or !== $original_or) {
                $upd_pay = $conn->prepare("UPDATE spareparts_payments SET or_number = ? WHERE or_number = ? AND branch = ?");
                $upd_pay->bind_param('sss', $new_or, $original_or, $original_branch);
                $upd_pay->execute();
            }

            $balance = $total_sale_amount - $total_paid;
            $stmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, branch, customer_name, sale_date, total_amount, balance) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssdd', $new_or, $new_branch, $customer_name, $sale_date, $total_sale_amount, $balance);
            $stmt->execute();
        }

        $conn->commit();
        addAuditLog('UPDATE', 'spareparts_sales', $new_or, "Revised sale $original_or ($original_branch) to $new_or. New total: $total_sale_amount. Reason: $reason");
        echo json_encode(['success' => true, 'message' => 'Sale transaction revised successfully. Inventory levels updated.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to revise sale: ' . $e->getMessage()]);
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
                // First, fetch the part_no so we can delete its transaction history
                $pStmt = $isAdmin
                    ? $conn->prepare("SELECT part_no, current_branch FROM spareparts_inventory WHERE id = ?")
                    : $conn->prepare("SELECT part_no, current_branch FROM spareparts_inventory WHERE id = ? AND current_branch = ?");

                if ($isAdmin) {
                    $pStmt->bind_param('i', $id);
                } else {
                    $pStmt->bind_param('is', $id, $currentBranch);
                }
                $pStmt->execute();
                $pRes = $pStmt->get_result()->fetch_assoc();

                if ($pRes) {
                    $partNo = $pRes['part_no'];
                    $bNo = $pRes['current_branch'];
                    // Delete transaction history for this part in this branch
                    $delTx = $conn->prepare("DELETE FROM spareparts_transactions WHERE part_no = ? AND (from_location = ? OR to_location = ?)");
                    $delTx->bind_param('sss', $partNo, $bNo, $bNo);
                    $delTx->execute();
                }

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
        addAuditLog('DELETE', 'spareparts', $id, "Deleted $type ID/REF $id from $targetBranch");
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
        'part_summary' => []
    ];

    // Compute part summary
    $partsSum = [];
    foreach ($data as $row) {
        $key = $row['part_no'] . '|' . $row['description'];
        if (!isset($partsSum[$key])) {
            $partsSum[$key] = [
                'part_no' => $row['part_no'],
                'description' => $row['description'],
                'quantity' => 0
            ];
        }
        $partsSum[$key]['quantity'] += $row['ending_balance'];
    }
    foreach ($partsSum as $pItem) {
        $summary['part_summary'][] = $pItem;
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

function getRankPrice()
{
    global $conn, $currentBranch;
    $part_no = sanitizeInput($_GET['part_no'] ?? '');
    $rank_level = sanitizeInput($_GET['rank_level'] ?? 'Standard');

    $price = null;
    // 1. Check Pricelist table
    $stmt = $conn->prepare("SELECT price FROM spareparts_pricelists WHERE part_no = ? AND rank_level = ? AND branch = ?");
    $stmt->bind_param('sss', $part_no, $rank_level, $currentBranch);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        $price = $res['price'];
    } else {
        // 2. Fallback to Spareparts Inventory
        $stmt = $conn->prepare("SELECT price FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $stmt->bind_param('ss', $part_no, $currentBranch);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) $price = $res['price'];
    }

    echo json_encode(['success' => (null !== $price), 'price' => $price]);
}

function searchInventoryParts()
{
    global $conn, $currentBranch, $isAdmin;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice';
    $term = sanitizeInput($_GET['term'] ?? '');
    $searchTerm = "%{$term}%";

    // If Admin/HeadOffice, show parts from all branches
    $whereBranch = $seeAll ? "" : " AND LOWER(current_branch) = LOWER(?)";

    $stmt = $conn->prepare("SELECT id, brand, part_no, description, current_stock, price, cost, current_branch
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
    global $conn, $currentBranch, $isAdmin, $userRole;
    $seeAll = $isAdmin || strtolower(trim($currentBranch)) === 'headoffice' || strtolower(trim($userRole)) === 'spareparts-warehouse';

    if (!$seeAll) {
        // Only allow branch users to see Head Office (for transfers)
        echo json_encode(['success' => true, 'data' => ['HEADOFFICE']]);
        return;
    }

    $query = "SELECT DISTINCT branch FROM users WHERE position IN ('Spareparts-Sales', 'Spareparts-Warehouse', 'Spareparts-Retail') AND branch IS NOT NULL AND branch != '' ORDER BY branch ASC";
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

    $sql = "SELECT t.transfer_date, t.id as transfer_number, t.transfer_no, t.from_branch, t.to_branch, t.status,
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

    $division = getCurrentDivision();
    if (!$seeAll && $division) {
        $sql .= " AND category = ? ";
        $params[] = $division;
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
    $division = getCurrentDivision();
    $sql = "SELECT transaction_date as date, or_number, type, total_amount as amount, transaction_type
            FROM spareparts_transactions 
            WHERE customer_name = ? AND from_location = ? ";
    
    if ($division) {
        $sql .= " AND category = ? ";
    }
    
    $sql .= " AND (type = 'PAYMENT' OR (type = 'OUT' AND transaction_type = 'charge'))
            ORDER BY transaction_date ASC, id ASC";

    $stmt = $conn->prepare($sql);
    if ($division) {
        $stmt->bind_param('sss', $customer, $branch, $division);
    } else {
        $stmt->bind_param('ss', $customer, $branch);
    }
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

    // Auto-migrate schema to add status if missing
    try {
        @$conn->query("ALTER TABLE spareparts_transfer_items ADD COLUMN status VARCHAR(20) DEFAULT 'In-Transit'");
    } catch (Exception $e) {
    }

    $sql = "SELECT ti.id as item_id, ti.id as id, ti.part_no, ti.description, ti.quantity as qty, 
                   t.transfer_date, t.from_branch, t.id as transfer_number, t.transfer_no, t.status,
                   i.brand
            FROM spareparts_transfers t
            JOIN spareparts_transfer_items ti ON t.id = ti.transfer_id AND (ti.status = 'In-Transit' OR ti.status IS NULL)
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

    // Auto-migrate schema to add status if missing
    try {
        @$conn->query("ALTER TABLE spareparts_transfer_items ADD COLUMN status VARCHAR(20) DEFAULT 'In-Transit'");
    } catch (Exception $e) {
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

            // Fetch brand and price from the source branch
            $srcStmt = $conn->prepare("SELECT brand, price FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
            $srcStmt->bind_param('ss', $part_no, $fromBranch);
            $srcStmt->execute();
            $srcItem = $srcStmt->get_result()->fetch_assoc();

            $brand = $srcItem ? $srcItem['brand'] : '';
            $price = $srcItem ? (float) $srcItem['price'] : 0.0;

            // 2. Update/Insert into inventory at current branch
            $invStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, brand, current_stock, cost, price, current_branch) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE 
                                      current_stock = current_stock + VALUES(current_stock), 
                                      cost = IF(VALUES(cost) > cost, VALUES(cost), cost),
                                      brand = IF(brand = '' OR brand IS NULL, VALUES(brand), brand),
                                      price = IF(price = 0 OR price IS NULL, VALUES(price), price)");
            $invStmt->bind_param('sssidss', $part_no, $description, $brand, $quantity, $cost, $price, $currentBranch);
            $invStmt->execute();

            // 3. Log TRANSACTION_IN
            $txStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status) 
                                     VALUES (CURDATE(), 'TRANSFER_IN', ?, ?, ?, ?, ?, ?, ?, 'Completed')");
            $total_amount = $quantity * $cost;
            $txStmt->bind_param('ssiddss', $part_no, $description, $quantity, $cost, $total_amount, $fromBranch, $currentBranch);
            $txStmt->execute();

            // 4. Update the individual item status to Completed
            $updItem = $conn->prepare("UPDATE spareparts_transfer_items SET status = 'Completed' WHERE id = ?");
            $updItem->bind_param('i', $itemId);
            $updItem->execute();

            // 5. Check if any items in this transfer are still In-Transit or NULL
            $checkStmt = $conn->prepare("SELECT COUNT(*) as pending FROM spareparts_transfer_items WHERE transfer_id = ? AND (status = 'In-Transit' OR status IS NULL)");
            $checkStmt->bind_param('i', $transferId);
            $checkStmt->execute();
            $pendingResult = $checkStmt->get_result()->fetch_assoc();

            if ($pendingResult['pending'] == 0) {
                // All items received or rejected, complete the parent transfer
                $updTransfer = $conn->prepare("UPDATE spareparts_transfers SET status = 'Completed', received_date = NOW() WHERE id = ?");
                $updTransfer->bind_param('i', $transferId);
                $updTransfer->execute();
            }
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

    // Auto-migrate schema to add status if missing
    try {
        @$conn->query("ALTER TABLE spareparts_transfer_items ADD COLUMN status VARCHAR(20) DEFAULT 'In-Transit'");
    } catch (Exception $e) {
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

            // 4. Update the individual item status to Rejected
            $updItem = $conn->prepare("UPDATE spareparts_transfer_items SET status = 'Rejected' WHERE id = ?");
            $updItem->bind_param('i', $itemId);
            $updItem->execute();

            // 5. Check if any items in this transfer are still In-Transit or NULL
            $checkStmt = $conn->prepare("SELECT COUNT(*) as pending FROM spareparts_transfer_items WHERE transfer_id = ? AND (status = 'In-Transit' OR status IS NULL)");
            $checkStmt->bind_param('i', $transferId);
            $checkStmt->execute();
            $pendingResult = $checkStmt->get_result()->fetch_assoc();

            if ($pendingResult['pending'] == 0) {
                // All items received or rejected, complete the parent transfer
                // If all were rejected, the transfer status ideally reflects that, but 'Rejected' or 'Completed' operates the same visually. We will use 'Rejected' to match.
                $updTransfer = $conn->prepare("UPDATE spareparts_transfers SET status = 'Rejected', received_date = NOW() WHERE id = ?");
                $updTransfer->bind_param('i', $transferId);
                $updTransfer->execute();
            }
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
    global $conn;
    $term = sanitizeInput($_GET['term'] ?? $_GET['search'] ?? '');
    
    if (empty($term)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit();
    }

    $searchTerm = "%{$term}%";

    $stmt = $conn->prepare("SELECT brand, part_no, description, current_stock, price, current_branch
                            FROM spareparts_inventory 
                            WHERE (part_no LIKE ? OR description LIKE ? OR brand LIKE ?) 
                            AND current_stock > 0
                            ORDER BY current_branch ASC, part_no ASC
                            LIMIT 50");

    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);

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