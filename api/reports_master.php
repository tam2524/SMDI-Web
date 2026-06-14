<?php
header('Content-Type: application/json');
require_once 'db_config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$userRole = $_SESSION['position'] ?? $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$isAdmin = in_array(strtolower(trim($userRole)), array_map('strtolower', $adminRoles));
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';

$action = $_REQUEST['action'] ?? '';

if ($action === 'generate_master_report') {
    $category = $_GET['category'] ?? 'inventory';
    $type = $_GET['report_type'] ?? '';
    $period = $_GET['period'] ?? 'monthly';
    $dateVal = $_GET['date_value'] ?? date('Y-m');
    $branch = $_GET['branch'] ?? 'all';
    $brand = trim($_REQUEST['brand'] ?? '');
    $refNo = trim($_REQUEST['ref_no'] ?? '');

    // Enforce branch security
    if (!$isAdmin) {
        $branch = $currentBranch;
    } else {
        // Admin/Owner can pick any branch
        $branch = $_GET['branch'] ?? 'all';
    }

    $finalResponse = ['success' => false, 'message' => 'Report not implemented yet.'];

    try {
        switch ($category) {
            case 'inventory':
                $finalResponse = handleInventoryReports($type, $period, $dateVal, $branch, $brand, $refNo);
                break;
            case 'sales':
                $finalResponse = handleSalesReports($type, $period, $dateVal, $branch, $brand, $refNo);
                break;
            case 'payments':
                $finalResponse = handlePaymentReports($type, $period, $dateVal, $branch, $brand, $refNo);
                break;
            case 'transfer':
                $finalResponse = handleTransferReports($type, $period, $dateVal, $branch, $brand, $refNo);
                break;
            case 'payables':
                $finalResponse = handlePayableReports($type, $period, $dateVal, $branch, $brand, $refNo);
                break;
        }
    } catch (Exception $e) {
        $finalResponse = ['success' => false, 'message' => 'Internal Error: ' . $e->getMessage()];
    }

    echo json_encode($finalResponse);
    exit();
}

if ($action === 'customer_ledger') {
    $customerName = trim($_GET['customer'] ?? $_GET['customer_name'] ?? '');
    $branchVal = trim($_GET['branch'] ?? 'all');
    if (!$isAdmin) $branchVal = $currentBranch;
    
    // For single customer view from Dashboard, we want full history since start of year by default
    $period = $_GET['period'] ?? 'custom';
    $dateVal = $_GET['date_value'] ?? date('Y-01-01') . ' to ' . date('Y-m-d');
    
    // Ensure handlePaymentReports can find the name in $_GET
    $_GET['customer_name'] = $customerName; 
    
    try {
        $finalResponse = handlePaymentReports('customer_ledger', $period, $dateVal, $branchVal, '');
        echo json_encode($finalResponse);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

function handlePayableReports($type, $period, $dateVal, $branch, $brand, $refNo = '') {
    global $conn;
    $dateRange = parseDateRange($period, $dateVal);
    
    switch ($type) {
        case 'supplier_aging':
            // Auto-create table if missing
            try {
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

            $where = "a.balance > 0";
            if ($branch !== 'all') { $where .= " AND a.branch = '" . $conn->real_escape_string($branch) . "'"; }
            
            $sql = "SELECT a.supplier_name, a.invoice_no, a.total_amount as amount, a.balance, DATEDIFF(NOW(), a.date_received) as age, a.branch 
                    FROM spareparts_supplier_aging a WHERE $where ORDER BY age DESC";
            $headers = ['Supplier Name', 'Invoice / DR #', 'Orig. Amount', 'Balance', 'Days Outstanding', 'Branch'];
            $keys = ['supplier_name', 'invoice_no', 'amount', 'balance', 'age', 'branch'];
            $formatters = ['amount' => 'currency', 'balance' => 'currency'];
            
            $res = exe($sql, "", []);
            $totalPayable = array_sum(array_column($res, 'balance'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Total Accounts Payable', 'value' => $totalPayable, 'format' => 'currency']
                ]
            ];

        case 'supplier_payments':
            $where = "t.type = 'PAYMENT_TO_SUPPLIER' AND t.transaction_date BETWEEN ? AND ?";
            $params = [$dateRange['start'], $dateRange['end']];
            $types = "ss";

            if ($branch !== 'all') {
                $where .= " AND t.from_location = ?";
                $params[] = $branch; $types .= "s";
            }

            $sql = "SELECT t.transaction_date, t.to_location as supplier, t.or_number as reference, t.total_amount as amount, t.reason, t.from_location as branch
                    FROM spareparts_transactions t WHERE $where ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'Supplier', 'Reference', 'Amount Paid', 'Remarks', 'Branch'];
            $keys = ['transaction_date', 'supplier', 'reference', 'amount', 'reason', 'branch'];
            $formatters = ['amount' => 'currency'];
            
            $res = exe($sql, $types, $params);
            $totalPaid = array_sum(array_column($res, 'amount'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Total Payments Made', 'value' => $totalPaid, 'format' => 'currency']
                ]
            ];

        default:
            return ['success' => false, 'message' => 'Payable report type not found.'];
    }
}

function handleInventoryReports($type, $period, $dateVal, $branch, $brand, $refNo = '') {
    global $conn;
    $where = "i.current_stock >= 0";
    $params = [];
    $types = "";

    if ($branch !== 'all') {
        $where .= " AND i.current_branch = ?";
        $params[] = $branch; $types .= "s";
    }
    if (!empty($brand)) {
        $where .= " AND i.brand LIKE ?";
        $params[] = "%$brand%"; $types .= "s";
    }

    switch ($type) {
        case 'inventory_movement':
            $part_no = $_GET['part_no'] ?? '';
            $dateRange = parseDateRange($period, $dateVal);
            $whereM = "t.transaction_date BETWEEN ? AND ?";
            $mParams = [$dateRange['start'], $dateRange['end']];
            $mTypes = "ss";

            if (!empty($part_no)) {
                $whereM .= " AND t.part_no = ?";
                $mParams[] = $part_no; $mTypes .= "s";
            }
            if ($branch !== 'all') {
                $whereM .= " AND t.from_location = ?";
                $mParams[] = $branch; $mTypes .= "s";
            }

            $sql = "SELECT t.transaction_date, t.type, t.part_no, t.description, t.quantity, t.from_location, t.to_location, t.or_number 
                    FROM spareparts_transactions t 
                    WHERE $whereM ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'Type', 'Part No', 'Description', 'Qty', 'From', 'To', 'Ref #'];
            $keys = ['transaction_date', 'type', 'part_no', 'description', 'quantity', 'from_location', 'to_location', 'or_number'];
            
            $res = exe($sql, $mTypes, $mParams);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys], 'summary' => [['label' => 'Total Transactions', 'value' => count($res)]] ];
            
        case 'received_stocks_summary':
            $dateRange = parseDateRange($period, $dateVal);
            $whereM = "t.type IN ('IN', 'TRANSFER_IN', 'RETURN')";
            $mParams = [];
            $mTypes = "";
            
            if (empty($refNo)) {
                $whereM .= " AND t.transaction_date BETWEEN ? AND ?";
                $mParams[] = $dateRange['start']; $mParams[] = $dateRange['end'];
                $mTypes .= "ss";
            }

            if ($branch !== 'all') {
                $whereM .= " AND t.to_location = ?";
                $mParams[] = $branch; $mTypes .= "s";
            }
            
            // Precise heuristic backfill for incoming transfers
            try { 
                $conn->query("UPDATE spareparts_transactions t
                             JOIN spareparts_transfer_items sti ON sti.part_no = t.part_no AND ABS(sti.quantity) = ABS(t.quantity)
                             JOIN spareparts_transfers st ON st.id = sti.transfer_id 
                                  AND (st.from_branch = t.from_location AND st.to_branch = t.to_location)
                                  AND ABS(DATEDIFF(st.transfer_date, t.transaction_date)) <= 1
                             SET t.transfer_no = st.transfer_no
                             WHERE t.type = 'TRANSFER_IN' AND (t.transfer_no IS NULL OR t.transfer_no = '')");
            } catch(Exception $e) {}
            if (!empty($refNo)) {
                $whereM .= " AND (t.or_number = ? OR t.transfer_no = ? OR EXISTS (
                    SELECT 1 FROM spareparts_transfers st2 
                    JOIN spareparts_transfer_items sti2 ON sti2.transfer_id = st2.id
                    WHERE st2.transfer_no = ? 
                    AND sti2.part_no = t.part_no AND ABS(sti2.quantity) = ABS(t.quantity)
                    AND st2.from_branch = t.from_location 
                    AND st2.to_branch = t.to_location 
                    AND ABS(DATEDIFF(st2.transfer_date, t.transaction_date)) <= 2
                ))";
                $mParams[] = $refNo; $mParams[] = $refNo; $mParams[] = $refNo; $mTypes .= "sss";
            }
            
            $sql = "SELECT t.transaction_date, 
                           COALESCE(NULLIF(t.transfer_no, ''), 
                                   (SELECT st.transfer_no FROM spareparts_transfers st WHERE st.from_branch = t.from_location AND st.to_branch = t.to_location AND ABS(DATEDIFF(st.transfer_date, t.transaction_date)) <= 1 ORDER BY (st.transfer_no = ?) DESC, st.transfer_date ASC LIMIT 1),
                                   t.or_number, 'N/A') as reference, 
                           t.part_no, t.description, t.type as receive_type, t.quantity, t.from_location as source_branch, t.to_location as receiving_branch,
                           COALESCE(NULLIF(t.price, 0), (SELECT cost FROM spareparts_inventory WHERE part_no = t.part_no AND current_branch = t.to_location LIMIT 1), 0) as unit_cost,
                           COALESCE(NULLIF(t.total_amount, 0), t.quantity * COALESCE(NULLIF(t.price, 0), (SELECT cost FROM spareparts_inventory WHERE part_no = t.part_no AND current_branch = t.to_location LIMIT 1), 0)) as total_amount,
                           (SELECT id FROM spareparts_transfers WHERE (transfer_no = t.transfer_no OR (NULLIF(t.transfer_no, '') IS NULL AND from_branch = t.from_location AND to_branch = t.to_location AND ABS(DATEDIFF(transfer_date, t.transaction_date)) <= 1)) LIMIT 1) as transfer_id
                    FROM spareparts_transactions t 
                    WHERE $whereM 
                    ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'Reference', 'Part No', 'Description', 'Receive Type', 'Qty', 'Unit Cost', 'Subtotal', 'Source', 'Receiving Branch'];
            $keys = ['transaction_date', 'reference', 'part_no', 'description', 'receive_type', 'quantity', 'unit_cost', 'total_amount', 'source_branch', 'receiving_branch'];
            $formatters = ['unit_cost' => 'currency', 'total_amount' => 'currency'];
            
            // Add refNo to params for the SELECT subquery
            $finalParams = array_merge([$refNo], $mParams);
            $finalTypes = "s" . $mTypes;
            
            $res = exe($sql, $finalTypes, $finalParams);
            $totalReceived = array_sum(array_column($res, 'quantity'));
            
            return [ 
                'success' => true, 
                'data' => $res, 
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 
                'summary' => [
                    ['label' => 'Total Transactions', 'value' => count($res)],
                    ['label' => 'Total Received Qty', 'value' => $totalReceived]
                ] 
            ];
            
        case 'supplier_received_stocks':
            $dateRange = parseDateRange($period, $dateVal);
            $whereM = "t.type = 'IN' AND t.transaction_date BETWEEN ? AND ?";
            $mParams = [$dateRange['start'], $dateRange['end']];
            $mTypes = "ss";

            if ($branch !== 'all') {
                $whereM .= " AND t.to_location = ?";
                $mParams[] = $branch; $mTypes .= "s";
            }
            if (!empty($refNo)) {
                $whereM .= " AND t.or_number LIKE ?";
                $mParams[] = "%$refNo%"; $mTypes .= "s";
            }

            $sql = "SELECT t.transaction_date, COALESCE(t.or_number, 'N/A') as reference, t.from_location as supplier, 
                           t.part_no, t.description, t.quantity, t.price as unit_cost, t.total_amount, t.payment_method, t.to_location as receiving_branch
                    FROM spareparts_transactions t 
                    WHERE $whereM ORDER BY t.transaction_date DESC, t.or_number DESC";
            $headers = ['Date', 'Invoice / DR #', 'Supplier', 'Part No', 'Description', 'Qty', 'Unit Cost', 'Subtotal', 'Branch'];
            $keys = ['transaction_date', 'reference', 'supplier', 'part_no', 'description', 'quantity', 'unit_cost', 'total_amount', 'receiving_branch'];
            $formatters = ['unit_cost' => 'currency', 'total_amount' => 'currency'];
            
            $res = exe($sql, $mTypes, $mParams);
            $totalReceived = array_sum(array_column($res, 'quantity'));
            $totalAmount = array_sum(array_column($res, 'total_amount'));
            $uniqueInvoices = count(array_unique(array_column($res, 'reference')));
            
            return [ 
                'success' => true, 
                'data' => $res, 
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 
                'summary' => [
                    ['label' => 'Total Invoices', 'value' => $uniqueInvoices],
                    ['label' => 'Total Received Qty', 'value' => $totalReceived],
                    ['label' => 'Total Value', 'value' => $totalAmount, 'format' => 'currency']
                ] 
            ];

        case 'transferred_stocks_summary':
            // Ensure transfer_no exists in transactions and backfill for older records
            try { 
                $conn->query("ALTER TABLE spareparts_transactions ADD COLUMN IF NOT EXISTS transfer_no VARCHAR(100) DEFAULT NULL AFTER or_number"); 
                // Much more precise heuristic: Match by branches, date margin, AND the actual part_no/quantity from transfer items
                $conn->query("UPDATE spareparts_transactions t
                             JOIN spareparts_transfer_items sti ON sti.part_no = t.part_no AND ABS(sti.quantity) = ABS(t.quantity)
                             JOIN spareparts_transfers st ON st.id = sti.transfer_id 
                                  AND (st.from_branch = t.from_location AND st.to_branch = t.to_location)
                                  AND ABS(DATEDIFF(st.transfer_date, t.transaction_date)) <= 1
                             SET t.transfer_no = st.transfer_no
                             WHERE t.type = 'TRANSFER_OUT' AND (t.transfer_no IS NULL OR t.transfer_no = '')");
            } catch(Exception $e) {}

            $dateRange = parseDateRange($period, $dateVal);
            $whereM = "t.type IN ('TRANSFER_OUT')";
            $mParams = [];
            $mTypes = "";
            
            if (empty($refNo)) {
                $whereM .= " AND t.transaction_date BETWEEN ? AND ?";
                $mParams[] = $dateRange['start']; $mParams[] = $dateRange['end'];
                $mTypes .= "ss";
            }

            if ($branch !== 'all') {
                $whereM .= " AND t.from_location = ?";
                $mParams[] = $branch; $mTypes .= "s";
            }
            if (!empty($refNo)) {
                $whereM .= " AND (t.transfer_no = ? OR t.or_number = ? OR EXISTS (
                    SELECT 1 FROM spareparts_transfers st2 
                    JOIN spareparts_transfer_items sti2 ON sti2.transfer_id = st2.id
                    WHERE st2.transfer_no = ? 
                    AND sti2.part_no = t.part_no AND ABS(sti2.quantity) = ABS(t.quantity)
                    AND st2.from_branch = t.from_location 
                    AND st2.to_branch = t.to_location 
                    AND ABS(DATEDIFF(st2.transfer_date, t.transaction_date)) <= 2
                ))";
                $mParams[] = $refNo; $mParams[] = $refNo; $mParams[] = $refNo; $mTypes .= "sss";
            }

            $sql = "SELECT t.transaction_date, 
                           COALESCE(NULLIF(t.transfer_no, ''), 
                                   (SELECT st.transfer_no FROM spareparts_transfers st WHERE st.from_branch = t.from_location AND st.to_branch = t.to_location AND ABS(DATEDIFF(st.transfer_date, t.transaction_date)) <= 1 ORDER BY (st.transfer_no = ?) DESC, st.transfer_date ASC LIMIT 1),
                                   t.or_number, 'N/A') as transfer_no,
                           t.part_no, t.description, t.quantity, 
                           COALESCE(NULLIF(t.price, 0), (SELECT cost FROM spareparts_inventory WHERE part_no = t.part_no AND current_branch = t.from_location LIMIT 1), 0) as unit_cost,
                           COALESCE(NULLIF(t.total_amount, 0), t.quantity * COALESCE(NULLIF(t.price, 0), (SELECT cost FROM spareparts_inventory WHERE part_no = t.part_no AND current_branch = t.from_location LIMIT 1), 0)) as total_amount,
                           t.from_location as source_branch, 
                           t.to_location as receiving_branch,
                           (SELECT id FROM spareparts_transfers WHERE (transfer_no = t.transfer_no OR (NULLIF(t.transfer_no, '') IS NULL AND from_branch = t.from_location AND to_branch = t.to_location AND ABS(DATEDIFF(transfer_date, t.transaction_date)) <= 1)) LIMIT 1) as transfer_id
                    FROM spareparts_transactions t 
                    WHERE $whereM 
                    ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'Transfer #', 'Part No', 'Description', 'Qty', 'Unit Cost', 'Subtotal', 'Source Branch', 'Receiving Branch'];
            $keys = ['transaction_date', 'transfer_no', 'part_no', 'description', 'quantity', 'unit_cost', 'total_amount', 'source_branch', 'receiving_branch'];
            $formatters = ['unit_cost' => 'currency', 'total_amount' => 'currency'];
            
            // Add refNo to params for the SELECT subquery
            $finalParams = array_merge([$refNo], $mParams);
            $finalTypes = "s" . $mTypes;
            
            $res = exe($sql, $finalTypes, $finalParams);
            $totalTransferred = array_sum(array_column($res, 'quantity'));
            $totalAmount = array_sum(array_column($res, 'total_amount'));
            
            return [ 
                'success' => true, 
                'data' => $res, 
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 
                'summary' => [
                    ['label' => 'Total Transfer Out (Tx)', 'value' => count($res)],
                    ['label' => 'Total Transferred Qty', 'value' => $totalTransferred],
                    ['label' => 'Total Value', 'value' => $totalAmount, 'format' => 'currency']
                ] 
            ];
            
        case 'stock_status':
            $sql = "SELECT i.part_no, i.description, i.brand, i.category, i.current_stock, i.cost, (i.current_stock * i.cost) as total_value, i.current_branch as branch
                    FROM spareparts_inventory i WHERE $where ORDER BY i.current_branch, i.part_no";
            $headers = ['Part No', 'Description', 'Brand', 'Category', 'Stock', 'Unit Cost', 'Total Value', 'Branch'];
            $keys = ['part_no', 'description', 'brand', 'category', 'current_stock', 'cost', 'total_value', 'branch'];
            $formatters = ['cost' => 'currency', 'total_value' => 'currency'];
            
            $res = exe($sql, $types, $params);
            $totalVal = array_sum(array_column($res, 'total_value'));
            $totalQty = array_sum(array_column($res, 'current_stock'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Total Units', 'value' => $totalQty],
                    ['label' => 'Inventory Value', 'value' => $totalVal, 'format' => 'currency']
                ]
            ];

        case 'reorder_point':
            $where .= " AND i.current_stock <= i.min_stock";
            $sql = "SELECT i.part_no, i.description, i.brand, i.current_stock, i.min_stock, (i.min_stock - i.current_stock) as deficit, i.current_branch as branch
                    FROM spareparts_inventory i WHERE $where AND i.min_stock > 0 ORDER BY i.current_branch, i.part_no";
            $headers = ['Part No', 'Description', 'Brand', 'Actual Stock', 'Min Stock', 'To Restock', 'Branch'];
            $keys = ['part_no', 'description', 'brand', 'current_stock', 'min_stock', 'deficit', 'branch'];
            
            $res = exe($sql, $types, $params);
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys],
                'summary' => [
                    ['label' => 'Critical Items', 'value' => count($res)]
                ]
            ];

        case 'inventory_balance':
            $dateRange = parseDateRange($period, $dateVal);
            $start = $dateRange['start'];
            $end = $dateRange['end'];
            
            // Subquery based approach to get IN/OUT per part per branch in the period
            $sql = "SELECT i.part_no, i.description, i.brand, i.current_branch as branch,
                           COALESCE(trans.total_in, 0) as total_in,
                           COALESCE(trans.total_out, 0) as total_out,
                           (COALESCE(trans.total_in, 0) - COALESCE(trans.total_out, 0)) as net_change,
                           i.current_stock
                    FROM spareparts_inventory i
                    LEFT JOIN (
                        SELECT t.part_no, 
                               CASE 
                                 WHEN t.type IN ('IN', 'TRANSFER_IN', 'RETURN') THEN t.to_location 
                                 ELSE t.from_location 
                               END as branch_id,
                               SUM(CASE WHEN t.type IN ('IN', 'TRANSFER_IN', 'RETURN') OR (t.type = 'ADJUSTMENT' AND t.quantity > 0) THEN t.quantity ELSE 0 END) as total_in,
                               SUM(CASE WHEN t.type IN ('OUT', 'SALE', 'TRANSFER_OUT') OR (t.type = 'ADJUSTMENT' AND t.quantity < 0) THEN ABS(t.quantity) ELSE 0 END) as total_out
                        FROM spareparts_transactions t
                        WHERE t.transaction_date BETWEEN ? AND ?
                        GROUP BY t.part_no, branch_id
                    ) trans ON i.part_no = trans.part_no AND i.current_branch = trans.branch_id
                    WHERE $where
                    ORDER BY i.current_branch, i.part_no";
            
            $headers = ['Part No', 'Description', 'Brand', 'Total IN', 'Total OUT', 'Net Change', 'Closing Stock', 'Branch'];
            $keys = ['part_no', 'description', 'brand', 'total_in', 'total_out', 'net_change', 'current_stock', 'branch'];
            
            // Merging params: [start_date, end_date, ...$params]
            $allParams = array_merge([$start, $end], $params);
            $allTypes = "ss" . $types;
            
            $res = exe($sql, $allTypes, $allParams);
            $totalIn = array_sum(array_column($res, 'total_in'));
            $totalOut = array_sum(array_column($res, 'total_out'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys],
                'summary' => [
                    ['label' => 'Total Inflow', 'value' => $totalIn],
                    ['label' => 'Total Outflow', 'value' => $totalOut]
                ]
            ];

        case 'inventory_aging':
            $sql = "SELECT i.part_no, i.description, i.brand, i.current_stock, i.cost, (i.current_stock * i.cost) as total_value,
                           DATEDIFF(NOW(), COALESCE((SELECT MAX(transaction_date) FROM spareparts_transactions WHERE part_no = i.part_no AND type IN ('OUT','SALE') AND from_location = i.current_branch), '2020-01-01')) as days_idle,
                           i.current_branch as branch
                    FROM spareparts_inventory i WHERE $where AND i.current_stock > 0 ORDER BY days_idle DESC";
            $headers = ['Part No', 'Description', 'Brand', 'Stock', 'Unit Cost', 'Value', 'Days Idle (Last Sale)', 'Branch'];
            $keys = ['part_no', 'description', 'brand', 'current_stock', 'cost', 'total_value', 'days_idle', 'branch'];
            $formatters = ['cost' => 'currency', 'total_value' => 'currency'];
            
            $res = exe($sql, $types, $params);
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Oldest Stock (Days)', 'value' => $res[0]['days_idle'] ?? 0]
                ]
            ];
    }
}

function handleSalesReports($type, $period, $dateVal, $branch, $brand, $refNo = '') {
    global $conn;
    $dateRange = parseDateRange($period, $dateVal);
    $where = "t.type = 'OUT' AND t.transaction_date BETWEEN ? AND ?";
    $params = [$dateRange['start'], $dateRange['end']];
    $types = "ss";

    if ($branch !== 'all') {
        $where .= " AND t.from_location = ?";
        $params[] = $branch; $types .= "s";
    }

    switch ($type) {
        case 'daily_sales_summary':
            $sql = "SELECT t.transaction_date, t.or_number, t.customer_name, t.transaction_type, SUM(t.total_amount) as total_amount, t.from_location as branch
                    FROM spareparts_transactions t WHERE $where GROUP BY t.or_number, t.from_location ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'OR/Invoice', 'Customer', 'Type', 'Amount', 'Branch'];
            $keys = ['transaction_date', 'or_number', 'customer_name', 'transaction_type', 'total_amount', 'branch'];
            $formatters = ['total_amount' => 'currency'];
            
            $res = exe($sql, $types, $params);
            $totalSales = array_sum(array_column($res, 'total_amount'));
            $cashSales = array_sum(array_map(function($r){ return strtolower(trim($r['transaction_type'])) === 'cash' ? $r['total_amount'] : 0; }, $res));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Grand Total Sales', 'value' => $totalSales, 'format' => 'currency'],
                    ['label' => 'Cash Portion', 'value' => $cashSales, 'format' => 'currency']
                ]
            ];

        case 'sales_by_item':
            $sql = "SELECT t.part_no, t.description, SUM(t.quantity) as total_qty, SUM(t.total_amount) as total_revenue
                    FROM spareparts_transactions t WHERE $where GROUP BY t.part_no ORDER BY total_revenue DESC";
            $headers = ['Part No', 'Description', 'Quantity Sold', 'Total Revenue'];
            $keys = ['part_no', 'description', 'total_qty', 'total_revenue'];
            $formatters = ['total_revenue' => 'currency'];
            
            $res = exe($sql, $types, $params);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 'summary' => [['label' => 'Unique Items Sold', 'value' => count($res)]] ];
        
        case 'profit_margin':
            $sql = "SELECT t.part_no, t.description, SUM(t.quantity) as qty, 
                           SUM(t.total_amount) as revenue, 
                           SUM(t.quantity * COALESCE(NULLIF(t.cost, 0), i.cost, t.price)) as total_cost,
                           (SUM(t.total_amount) - SUM(t.quantity * COALESCE(NULLIF(t.cost, 0), i.cost, t.price))) as profit
                    FROM spareparts_transactions t
                    LEFT JOIN spareparts_inventory i ON t.part_no = i.part_no AND t.from_location = i.current_branch
                    WHERE $where GROUP BY t.part_no ORDER BY profit DESC";
            $headers = ['Part No', 'Description', 'Qty', 'Revenue', 'Cost', 'G. Profit'];
            $keys = ['part_no', 'description', 'qty', 'revenue', 'total_cost', 'profit'];
            $formatters = ['revenue' => 'currency', 'total_cost' => 'currency', 'profit' => 'currency'];
            $res = exe($sql, $types, $params);
            $grossProfit = array_sum(array_column($res, 'profit'));
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 'summary' => [['label' => 'Estimated Gross Profit', 'value' => $grossProfit, 'format' => 'currency']] ];

        case 'branch_performance':
            $sql = "SELECT t.from_location as branch, COUNT(DISTINCT t.or_number) as invoice_count, SUM(t.total_amount) as total_revenue
                    FROM spareparts_transactions t WHERE $where GROUP BY t.from_location ORDER BY total_revenue DESC";
            $headers = ['Branch', 'Invoices Issued', 'Total Revenue'];
            $keys = ['branch', 'invoice_count', 'total_revenue'];
            $formatters = ['total_revenue' => 'currency'];
            
            $res = exe($sql, $types, $params);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 'summary' => [['label' => 'Total Branches Participated', 'value' => count($res)]] ];
        
        case 'sales_per_employee':
            $sql = "SELECT t.sales_force as employee, 
                           SUM(t.quantity) as total_qty, 
                           SUM(t.total_amount) as total_revenue,
                           t.from_location as branch
                    FROM spareparts_transactions t 
                    WHERE $where AND t.sales_force IS NOT NULL AND t.sales_force != ''
                    GROUP BY t.sales_force, t.from_location 
                    ORDER BY total_revenue DESC";
            $headers = ['Employee Name', 'Branch', 'Items Sold', 'Total Revenue'];
            $keys = ['employee', 'branch', 'total_qty', 'total_revenue'];
            $formatters = ['total_revenue' => 'currency'];
            
            $res = exe($sql, $types, $params);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 'summary' => [['label' => 'Total Sales Force', 'value' => count($res)]] ];

        default:
            return ['success' => false, 'message' => 'Sales report type not found.'];
    }
}

function handlePaymentReports($type, $period, $dateVal, $branch, $brand, $refNo = '') {
    global $conn;
    $dateRange = parseDateRange($period, $dateVal);
    $where = "t.type = 'PAYMENT' AND t.transaction_date BETWEEN ? AND ?";
    $params = [$dateRange['start'], $dateRange['end']];
    $types = "ss";

    if ($branch !== 'all') {
        $where .= " AND t.from_location = ?";
        $params[] = $branch; $types .= "s";
    }

    switch ($type) {
        case 'collection_report':
            $sql = "SELECT t.transaction_date, t.or_number as receipt_no, t.customer_name, t.payment_method as method, t.total_amount as amount, t.from_location as branch
                    FROM spareparts_transactions t WHERE $where ORDER BY t.transaction_date DESC";
            $headers = ['Payment Date', 'Receipt #', 'Customer', 'Method', 'Amount', 'Branch'];
            $keys = ['transaction_date', 'receipt_no', 'customer_name', 'method', 'amount', 'branch'];
            $formatters = ['amount' => 'currency'];
            
            $res = exe($sql, $types, $params);
            $totalCollected = array_sum(array_column($res, 'amount'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Total Collections', 'value' => $totalCollected, 'format' => 'currency']
                ]
            ];

        case 'ar_aging':
            // AR Aging doesn't depend on the period dates, it shows current outstanding SITUATION
            $whereA = "a.balance > 0 AND a.status = 'Active'";
            $aParams = [];
            $aTypes = "";
            
            if ($branch !== 'all') { 
                $whereA .= " AND a.branch = ?"; 
                $aParams[] = $branch; $aTypes .= "s";
            }
            
            // Age should be calculated from sale_date which reflects when the transaction actually occurred
            $sql = "SELECT a.customer_name, 
                           SUM(CASE WHEN DATEDIFF(NOW(), a.sale_date) <= 30 THEN a.balance ELSE 0 END) as age_0_30,
                           SUM(CASE WHEN DATEDIFF(NOW(), a.sale_date) BETWEEN 31 AND 60 THEN a.balance ELSE 0 END) as age_31_60,
                           SUM(CASE WHEN DATEDIFF(NOW(), a.sale_date) BETWEEN 61 AND 90 THEN a.balance ELSE 0 END) as age_61_90,
                           SUM(CASE WHEN DATEDIFF(NOW(), a.sale_date) > 90 THEN a.balance ELSE 0 END) as age_90_plus,
                           SUM(a.balance) as total_balance,
                           a.branch 
                    FROM spareparts_aging a WHERE $whereA GROUP BY a.customer_name, a.branch ORDER BY a.customer_name ASC";
            
            $headers = ['Customer Name', '0-30 Days', '31-60 Days', '61-90 Days', '90+ Days', 'Total Balance'];
            $keys = ['customer_name', 'age_0_30', 'age_31_60', 'age_61_90', 'age_90_plus', 'total_balance'];
            $formatters = ['age_0_30' => 'currency', 'age_31_60' => 'currency', 'age_61_90' => 'currency', 'age_90_plus' => 'currency', 'total_balance' => 'currency'];
            
            $res = exe($sql, $aTypes, $aParams);
            $totalAR = array_sum(array_column($res, 'total_balance'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Total Accounts Receivable', 'value' => $totalAR, 'format' => 'currency']
                ]
            ];

        case 'customer_ledger':
            $customerSearch = trim($_GET['customer_name'] ?? '');
            
            // IF a specific customer is searched, show DETAILED STATEMENT (Ledger)
            if (!empty($customerSearch)) {
                // Ignore the date period and fetch ALL history for accurate running balance
                $whereL = "t.customer_name LIKE ?";
                $lParams = ["%{$customerSearch}%"];
                $lTypes = "s";
                
                if ($branch !== 'all') {
                    $whereL .= " AND t.from_location = ?";
                    $lParams[] = $branch; $lTypes .= "s";
                }
                
                // Fetch all OUT (charge), PAYMENT, and RETURN transactions
                // For payments: t.or_number is the receipt, t.transaction_type stores the SI# targeted
                // For sales: t.or_number is the SI#
                // For returns: acts as a credit memo
                $sql = "SELECT t.transaction_date as date, t.or_number, t.type, t.transaction_type, t.total_amount as amount, t.customer_name
                        FROM spareparts_transactions t 
                        WHERE $whereL AND (t.type IN ('PAYMENT', 'RETURN') OR (t.type = 'OUT' AND t.transaction_type = 'charge'))
                        ORDER BY t.transaction_date ASC, t.id ASC";
                
                $details = exe($sql, $lTypes, $lParams);
                
                // Calculate Running Balance
                $ledgerData = [];
                $runningBalance = 0;
                $totalDebit = 0;
                $totalCredit = 0;
                
                foreach ($details as $row) {
                    if ($row['type'] === 'OUT') {
                        $runningBalance += (float) $row['amount'];
                        $totalDebit += (float) $row['amount'];
                        $row['ref'] = $row['or_number']; // SI #
                        $row['debit_credit_type'] = "Sale SI# " . $row['or_number'];
                        $row['debit'] = $row['amount'];
                        $row['credit'] = null; // Render as '-'
                    } else {
                        $runningBalance -= (float) $row['amount'];
                        $totalCredit += (float) $row['amount'];
                        $row['ref'] = $row['or_number']; // Receipt or CM #
                        $row['debit_credit_type'] = ($row['type'] === 'RETURN') ? "Credit Memo (Return)" : "Payment for SI# " . ($row['transaction_type'] ?? '-');
                        $row['debit'] = null; // Render as '-'
                        $row['credit'] = $row['amount'];
                    }
                    $row['balance'] = $runningBalance;
                    $ledgerData[] = $row;
                }
                
                // Retrieve Customer Information
                $cSql = "SELECT * FROM spareparts_customers WHERE name LIKE ? LIMIT 1";
                $cData = exe($cSql, "s", ["%{$customerSearch}%"]);
                $cDetails = !empty($cData) ? $cData[0] : [];
                $customerInfo = [
                    'name' => $cDetails['name'] ?? $customerSearch,
                    'address' => $cDetails['address'] ?? '',
                    'contact' => $cDetails['contact_no'] ?? '',
                    'rank' => $cDetails['rank_level'] ?? '1',
                    'credit_limit' => $cDetails['credit_limit'] ?? 0
                ];

                $headers = ['Date', 'Reference #', 'Transaction Info', 'Debit (Charge)', 'Credit (Payment)', 'Running Balance'];
                $keys = ['date', 'ref', 'debit_credit_type', 'debit', 'credit', 'balance'];
                $formatters = ['debit' => 'currency', 'credit' => 'currency', 'balance' => 'currency'];
                
                return [
                    'success' => true,
                    'data' => $ledgerData,
                    'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                    'summary' => [
                        ['label' => 'Total Outstanding Balance', 'value' => $runningBalance, 'format' => 'currency']
                    ],
                    'customer_info' => $customerInfo
                ];
            }
            
            // OTHERWISE, show SUMMARY for all customers
            $whereC = "(t.transaction_date BETWEEN ? AND ?)";
            $cParams = [$dateRange['start'], $dateRange['end']];
            $cTypes = "ss";

            if ($branch !== 'all') {
                $whereC .= " AND t.from_location = ?";
                $cParams[] = $branch; $cTypes .= "s";
            }

            $sql = "SELECT t.customer_name, 
                           SUM(CASE WHEN t.type = 'OUT' AND t.transaction_type = 'charge' THEN t.total_amount ELSE 0 END) as total_sales,
                           SUM(CASE WHEN t.type = 'PAYMENT' THEN t.total_amount ELSE 0 END) as total_payments,
                           (SUM(CASE WHEN t.type = 'OUT' AND t.transaction_type = 'charge' THEN t.total_amount ELSE 0 END) - SUM(CASE WHEN t.type = 'PAYMENT' THEN t.total_amount ELSE 0 END)) as balance
                    FROM spareparts_transactions t 
                    WHERE $whereC
                    GROUP BY t.customer_name
                    HAVING balance != 0 OR total_sales != 0
                    ORDER BY balance DESC";
            
            $headers = ['Customer Name', 'Total Sales', 'Total Payments', 'Balance'];
            $keys = ['customer_name', 'total_sales', 'total_payments', 'balance'];
            $formatters = ['total_sales' => 'currency', 'total_payments' => 'currency', 'balance' => 'currency'];
            
            $res = exe($sql, $cTypes, $cParams);
            return [ 
                'success' => true, 
                'data' => $res, 
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 
                'summary' => [
                    ['label' => 'Total Customers', 'value' => count($res)],
                    ['label' => 'Total Receivables', 'value' => array_sum(array_column($res, 'balance')), 'format' => 'currency']
                ] 
            ];

        default:
            return ['success' => false, 'message' => 'Payment report type not found.'];
    }
}

function handleTransferReports($type, $period, $dateVal, $branch, $brand, $refNo = '') {
    global $conn;
    $dateRange = parseDateRange($period, $dateVal);
    
    switch ($type) {
        case 'in_transit':
            $where = "st.status = 'In-Transit'";
            if ($branch !== 'all') { $where .= " AND (st.from_branch = '$branch' OR st.to_branch = '$branch')"; }
            
            $sql = "SELECT st.id, st.transfer_date, st.from_branch, st.to_branch, COUNT(sti.id) as item_count, SUM(sti.quantity) as total_qty
                    FROM spareparts_transfers st
                    JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
                    WHERE $where GROUP BY st.id ORDER BY st.transfer_date DESC";
            $headers = ['Transfer ID', 'Date', 'From', 'To', 'Unique Parts', 'Total Qty'];
            $keys = ['id', 'transfer_date', 'from_branch', 'to_branch', 'item_count', 'total_qty'];
            
            $res = exe($sql, "", []);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys], 'summary' => [['label' => 'Open Transfers', 'value' => count($res)]] ];

        case 'transfer_history':
            $whereH = "1=1";
            $hParams = [];
            $hTypes = "";
            
            if (!empty($refNo)) {
                $whereH .= " AND (st.transfer_no LIKE ? OR st.id = ?)";
                $term = "%$refNo%";
                $hParams[] = $term;
                $hParams[] = $refNo;
                $hTypes .= "ss";
            } else {
                $whereH .= " AND st.transfer_date BETWEEN ? AND ?";
                $hParams[] = $dateRange['start'];
                $hParams[] = $dateRange['end'];
                $hTypes .= "ss";
            }

            if ($branch !== 'all') {
                $whereH .= " AND (st.from_branch = ? OR st.to_branch = ?)";
                $hParams[] = $branch;
                $hParams[] = $branch;
                $hTypes .= "ss";
            }

            $sql = "SELECT st.id, st.transfer_no, st.transfer_date, st.from_branch, st.to_branch, st.status, COUNT(sti.id) as item_count, SUM(sti.quantity) as total_qty
                    FROM spareparts_transfers st
                    JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
                    WHERE $whereH GROUP BY st.id ORDER BY st.transfer_date DESC";
            $headers = ['ID', 'Transfer #', 'Date', 'From', 'To', 'Status', '# Items', 'Total Qty'];
            $keys = ['id', 'transfer_no', 'transfer_date', 'from_branch', 'to_branch', 'status', 'item_count', 'total_qty'];
            
            $res = exe($sql, $hTypes, $hParams);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys], 'summary' => [['label' => 'Total Transfers', 'value' => count($res)]] ];

        case 'stock_reconciliation':
            $whereR = "t.type = 'ADJUSTMENT' AND t.transaction_date BETWEEN ? AND ?";
            $rParams = [$dateRange['start'], $dateRange['end']];
            $rTypes = "ss";

            if ($branch !== 'all') {
                $whereR .= " AND t.from_location = ?";
                $rParams[] = $branch;
                $rTypes .= "s";
            }

            $sql = "SELECT t.transaction_date, t.part_no, t.description, t.quantity as adjustment_qty, t.reason, t.from_location as branch
                    FROM spareparts_transactions t
                    WHERE $whereR ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'Part No', 'Description', 'Adj Qty', 'Reason', 'Branch'];
            $keys = ['transaction_date', 'part_no', 'description', 'adjustment_qty', 'reason', 'branch'];
            
            $res = exe($sql, $rTypes, $rParams);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys], 'summary' => [['label' => 'Total Adjustment Logs', 'value' => count($res)]] ];

        default:
            return ['success' => false, 'message' => 'Transfer report type not found.'];
    }
}

// Utility functions
function exe($sql, $types = "", $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($types && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function parseDateRange($period, $dateVal) {
    if ($period === 'monthly') {
        $start = $dateVal . "-01";
        $end = date("Y-m-t", strtotime($start));
    } else if ($period === 'daily') {
        $start = $dateVal;
        $end = $dateVal;
    } else {
        $parts = explode(' to ', $dateVal);
        $start = $parts[0] ?? date('Y-m-01');
        $end = $parts[1] ?? date('Y-m-d');
    }
    return ['start' => $start, 'end' => $end];
}
