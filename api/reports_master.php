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
    $brand = $_GET['brand'] ?? '';

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
                $finalResponse = handleInventoryReports($type, $period, $dateVal, $branch, $brand);
                break;
            case 'sales':
                $finalResponse = handleSalesReports($type, $period, $dateVal, $branch, $brand);
                break;
            case 'payments':
                $finalResponse = handlePaymentReports($type, $period, $dateVal, $branch, $brand);
                break;
            case 'transfer':
                $finalResponse = handleTransferReports($type, $period, $dateVal, $branch, $brand);
                break;
        }
    } catch (Exception $e) {
        $finalResponse = ['success' => false, 'message' => 'Internal Error: ' . $e->getMessage()];
    }

    echo json_encode($finalResponse);
    exit();
}

function handleInventoryReports($type, $period, $dateVal, $branch, $brand) {
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

function handleSalesReports($type, $period, $dateVal, $branch, $brand) {
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
                           SUM(t.quantity * i.cost) as total_cost,
                           (SUM(t.total_amount) - SUM(t.quantity * i.cost)) as profit
                    FROM spareparts_transactions t
                    JOIN spareparts_inventory i ON t.part_no = i.part_no AND t.from_location = i.current_branch
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

        default:
            return ['success' => false, 'message' => 'Sales report type not found.'];
    }
}

function handlePaymentReports($type, $period, $dateVal, $branch, $brand) {
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
            $sql = "SELECT t.transaction_date, t.or_number, t.customer_name, t.transaction_type as method, t.total_amount as amount, t.from_location as branch
                    FROM spareparts_transactions t WHERE $where ORDER BY t.transaction_date DESC";
            $headers = ['Date', 'Receipt #', 'Customer', 'Method', 'Amount', 'Branch'];
            $keys = ['transaction_date', 'or_number', 'customer_name', 'method', 'amount', 'branch'];
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
            // AR Aging doesn't really depend on the period dates for the filter, it's "as of now"
            $whereA = "a.balance > 0";
            if ($branch !== 'all') { $whereA .= " AND a.branch = '" . $conn->real_escape_string($branch) . "'"; }
            
            $sql = "SELECT a.customer_name, a.or_number, a.balance, DATEDIFF(NOW(), a.created_at) as age, a.branch 
                    FROM spareparts_aging a WHERE $whereA ORDER BY age DESC";
            $headers = ['Customer', 'Source OR', 'Balance', 'Days Outstanding', 'Branch'];
            $keys = ['customer_name', 'or_number', 'balance', 'age', 'branch'];
            $formatters = ['balance' => 'currency'];
            
            $res = exe($sql, "", []);
            $totalAR = array_sum(array_column($res, 'balance'));
            
            return [
                'success' => true,
                'data' => $res,
                'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters],
                'summary' => [
                    ['label' => 'Total Accounts Receivable', 'value' => $totalAR, 'format' => 'currency']
                ]
            ];

        case 'customer_ledger':
            $customerSearch = $_GET['customer_name'] ?? '';
            $whereC = "(t.transaction_date BETWEEN ? AND ?)";
            $cParams = [$dateRange['start'], $dateRange['end']];
            $cTypes = "ss";

            if ($branch !== 'all') {
                $whereC .= " AND t.from_location = ?";
                $cParams[] = $branch; $cTypes .= "s";
            }
            if (!empty($customerSearch)) {
                $whereC .= " AND t.customer_name LIKE ?";
                $cParams[] = "%$customerSearch%"; $cTypes .= "s";
            }

            $sql = "SELECT t.customer_name, 
                           SUM(CASE WHEN t.type = 'OUT' THEN t.total_amount ELSE 0 END) as total_sales,
                           SUM(CASE WHEN t.type = 'PAYMENT' THEN t.total_amount ELSE 0 END) as total_payments,
                           (SUM(CASE WHEN t.type = 'OUT' THEN t.total_amount ELSE 0 END) - SUM(CASE WHEN t.type = 'PAYMENT' THEN t.total_amount ELSE 0 END)) as balance
                    FROM spareparts_transactions t 
                    WHERE $whereC
                    GROUP BY t.customer_name
                    ORDER BY balance DESC";
            $headers = ['Customer Name', 'Total Sales', 'Total Payments', 'Balance'];
            $keys = ['customer_name', 'total_sales', 'total_payments', 'balance'];
            $formatters = ['total_sales' => 'currency', 'total_payments' => 'currency', 'balance' => 'currency'];
            
            $res = exe($sql, $cTypes, $cParams);
            return [ 'success' => true, 'data' => $res, 'config' => ['headers' => $headers, 'keys' => $keys, 'formatters' => $formatters], 'summary' => [['label' => 'Total Customers', 'value' => count($res)]] ];

        default:
            return ['success' => false, 'message' => 'Payment report type not found.'];
    }
}

function handleTransferReports($type, $period, $dateVal, $branch, $brand) {
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
            $whereH = "st.transfer_date BETWEEN ? AND ?";
            $hParams = [$dateRange['start'], $dateRange['end']];
            $hTypes = "ss";

            if ($branch !== 'all') {
                $whereH .= " AND (st.from_branch = ? OR st.to_branch = ?)";
                $hParams[] = $branch;
                $hParams[] = $branch;
                $hTypes .= "ss";
            }

            $sql = "SELECT st.id, st.transfer_date, st.from_branch, st.to_branch, st.status, COUNT(sti.id) as item_count, SUM(sti.quantity) as total_qty
                    FROM spareparts_transfers st
                    JOIN spareparts_transfer_items sti ON st.id = sti.transfer_id
                    WHERE $whereH GROUP BY st.id ORDER BY st.transfer_date DESC";
            $headers = ['Transfer ID', 'Date', 'From', 'To', 'Status', '# Items', 'Total Qty'];
            $keys = ['id', 'transfer_date', 'from_branch', 'to_branch', 'status', 'item_count', 'total_qty'];
            
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
