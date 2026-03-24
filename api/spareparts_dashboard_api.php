<?php
require_once 'db_config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$today = date('Y-m-d');

try {
    switch ($action) {
        case 'get_warehouse_summary':
            getWarehouseSummary($conn, $branch, $today);
            break;
        case 'get_sales_summary':
            getSalesSummary($conn, $branch, $today);
            break;
        case 'get_consolidated_summary':
            getConsolidatedSummary($conn, $today);
            break;
        case 'get_global_inventory_stats':
            getGlobalInventoryStats($conn);
            break;
        case 'get_pending_transfers':
            getPendingTransfers($conn, $branch);
            break;
        case 'get_transfer_details':
            getTransferDetails($conn);
            break;
        case 'process_partial_transfer':
            processPartialTransfer($conn, $branch);
            break;
        case 'get_return_alerts':
            getReturnAlerts($conn, $branch);
            break;
        case 'get_inventory_summary':
            getInventorySummary($conn, $branch);
            break;
        case 'get_inventory_summary_by_branch':
            getInventorySummaryByBranch($conn);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Throwable $e) {
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
    exit;
}

function getPendingTransfers($conn, $branch) {
    $sql = "SELECT id, from_branch, transfer_date, 
                   (SELECT COUNT(*) FROM spareparts_transfer_items WHERE transfer_id = st.id) as item_count 
            FROM spareparts_transfers st 
            WHERE to_branch = ? AND status = 'In-Transit'
            ORDER BY transfer_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $transfers = [];
    while ($row = $res->fetch_assoc()) {
        $transfers[] = $row;
    }
    
    echo json_encode(['success' => true, 'transfers' => $transfers]);
}

function getGlobalInventoryStats($conn) {
    $stats = [
        'total_qty' => 0,
        'total_value' => 0,
        'monthly_sales' => 0,
        'yearly_sales' => 0
    ];

    // Total Inventory Qty & Value
    $res = $conn->query("SELECT SUM(current_stock) as qty, SUM(current_stock * cost) as value FROM spareparts_inventory")->fetch_assoc();
    $stats['total_qty'] = $res['qty'] ?? 0;
    $stats['total_value'] = $res['value'] ?? 0;

    // Monthly Sales (Aggregated)
    $monthStart = date('Y-m-01');
    $res = $conn->query("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE type = 'OUT' AND transaction_date >= '$monthStart'")->fetch_assoc();
    $stats['monthly_sales'] = $res['amount'] ?? 0;

    // Yearly Sales (Aggregated)
    $yearStart = date('Y-01-01');
    $res = $conn->query("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE type = 'OUT' AND transaction_date >= '$yearStart'")->fetch_assoc();
    $stats['yearly_sales'] = $res['amount'] ?? 0;

    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getWarehouseSummary($conn, $branch, $today) {
    $summary = [
        'received' => ['qty' => 0, 'amount' => 0],
        'transferred' => ['qty' => 0, 'amount' => 0]
    ];

    // RECEIVED STOCKS (IN + TRANSFER_IN) - Combined as RR/IN
    $stmt = $conn->prepare("SELECT SUM(quantity) as qty, SUM(total_amount) as amount FROM spareparts_transactions WHERE to_location = ? AND type IN ('IN', 'TRANSFER_IN') AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $summary['received']['qty'] = $res['qty'] ?? 0;
    $summary['received']['amount'] = $res['amount'] ?? 0;

    // TRANSFERRED STOCKS (TRANSFER_OUT)
    $stmt = $conn->prepare("SELECT SUM(quantity) as qty, SUM(total_amount) as amount FROM spareparts_transactions WHERE from_location = ? AND type = 'TRANSFER_OUT' AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $summary['transferred']['qty'] = $res['qty'] ?? 0;
    $summary['transferred']['amount'] = $res['amount'] ?? 0;

    echo json_encode(['success' => true, 'summary' => $summary]);
}

function getSalesSummary($conn, $branch, $today) {
    $summary = [
        'cash' => ['amount' => 0],
        'charge' => ['amount' => 0],
        'charge_pdc' => ['amount' => 0],
        'payments' => ['amount' => 0],
        'check_dues' => ['amount' => 0],
        'payables_due' => ['amount' => 0]
    ];

    // CASH SALES
    $stmt = $conn->prepare("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE from_location = ? AND type = 'OUT' AND LOWER(transaction_type) = 'cash' AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $summary['cash']['amount'] = $stmt->get_result()->fetch_assoc()['amount'] ?? 0;

    // CHARGE SALES (NORMAL - EXCLUDES PDC)
    $stmt = $conn->prepare("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE from_location = ? AND type = 'OUT' AND LOWER(transaction_type) = 'charge' AND (payment_method != 'PDC' OR payment_method IS NULL) AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $summary['charge']['amount'] = $stmt->get_result()->fetch_assoc()['amount'] ?? 0;

    // CHARGE SALES (WITH PDC)
    $stmt = $conn->prepare("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE from_location = ? AND type = 'OUT' AND LOWER(transaction_type) = 'charge' AND payment_method = 'PDC' AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $summary['charge_pdc']['amount'] = $stmt->get_result()->fetch_assoc()['amount'] ?? 0;

    // PAYMENTS AMOUNT
    $stmt = $conn->prepare("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE from_location = ? AND type = 'PAYMENT' AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $summary['payments']['amount'] = $stmt->get_result()->fetch_assoc()['amount'] ?? 0;

    // CHECK DUES AMOUNT (Payments with method Check dated today)
    $stmt = $conn->prepare("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE from_location = ? AND type = 'PAYMENT' AND payment_method = 'Check' AND DATE(transaction_date) = ?");
    $stmt->bind_param("ss", $branch, $today);
    $stmt->execute();
    $summary['check_dues']['amount'] = $stmt->get_result()->fetch_assoc()['amount'] ?? 0;

    // PAYABLES DUE AMOUNT (Active aging record balances)
    $stmt = $conn->prepare("SELECT SUM(balance) as total FROM spareparts_aging WHERE branch = ? AND status = 'Active'");
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $summary['payables_due']['amount'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    echo json_encode(['success' => true, 'summary' => $summary]);
}

function getConsolidatedSummary($conn, $today) {
    $summary = [
        'cash' => ['amount' => 0],
        'charge' => ['amount' => 0],
        'charge_pdc' => ['amount' => 0],
        'payments' => ['amount' => 0],
        'total' => ['amount' => 0]
    ];

    // CASH SALES (GLOBAL)
    $res = $conn->query("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE type = 'OUT' AND LOWER(transaction_type) = 'cash' AND DATE(transaction_date) = '$today'")->fetch_assoc();
    $summary['cash']['amount'] = $res['amount'] ?? 0;

    // CHARGE SALES (GLOBAL)
    $res = $conn->query("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE type = 'OUT' AND LOWER(transaction_type) = 'charge' AND (payment_method != 'PDC' OR payment_method IS NULL) AND DATE(transaction_date) = '$today'")->fetch_assoc();
    $summary['charge']['amount'] = $res['amount'] ?? 0;

    // CHARGE WITH PDC (GLOBAL)
    $res = $conn->query("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE type = 'OUT' AND LOWER(transaction_type) = 'charge' AND payment_method = 'PDC' AND DATE(transaction_date) = '$today'")->fetch_assoc();
    $summary['charge_pdc']['amount'] = $res['amount'] ?? 0;

    // PAYMENTS (GLOBAL)
    $res = $conn->query("SELECT SUM(total_amount) as amount FROM spareparts_transactions WHERE type = 'PAYMENT' AND DATE(transaction_date) = '$today'")->fetch_assoc();
    $summary['payments']['amount'] = $res['amount'] ?? 0;

    // TOTAL AMOUNT (Total Cash + Charge + Payments)
    $summary['total']['amount'] = $summary['cash']['amount'] + $summary['charge']['amount'] + $summary['charge_pdc']['amount'] + $summary['payments']['amount'];

    echo json_encode(['success' => true, 'summary' => $summary]);
}

function getReturnAlerts($conn, $branch) {
    // Get unread/recent returns for this branch
    // Showing returns from last 3 days for alert
    $sql = "SELECT id, part_no, description, quantity, from_location as sender, transaction_date, reason 
            FROM spareparts_transactions 
            WHERE to_location = ? AND status = 'Returned' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            ORDER BY transaction_date DESC, id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $alerts = [];
    while ($row = $res->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    echo json_encode(['success' => true, 'alerts' => $alerts]);
}

function getInventorySummary($conn, $branch) {
    $sql = "SELECT brand, SUM(current_stock) as qty, SUM(current_stock * cost) as value 
            FROM spareparts_inventory 
            WHERE current_branch = ? 
            GROUP BY brand 
            HAVING qty > 0
            ORDER BY brand ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $summary = [];
    while ($row = $res->fetch_assoc()) {
        $summary[] = $row;
    }
    
    echo json_encode(['success' => true, 'summary' => $summary]);
}

function getInventorySummaryByBranch($conn) {
    $sql = "SELECT current_branch as branch, SUM(current_stock) as qty, SUM(current_stock * cost) as value 
            FROM spareparts_inventory 
            GROUP BY current_branch 
            HAVING qty > 0
            ORDER BY current_branch ASC";
    $res = $conn->query($sql);
    
    $summary = [];
    while ($row = $res->fetch_assoc()) {
        $summary[] = $row;
    }
    
    echo json_encode(['success' => true, 'summary' => $summary]);
}

$conn->close();

function getTransferDetails($conn) {
    if (!isset($_GET['transfer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing Transfer ID']);
        return;
    }
    $id = (int)$_GET['transfer_id'];
    
    // Fetch items and their base inventory details (brand, price) from origin inventory
    // We join with the origin inventory to ensure we have all data needed for acceptance later
    $sql = "SELECT sti.id, sti.part_no, sti.description, sti.quantity, sti.cost, 
                   si.brand, si.price, si.min_stock
            FROM spareparts_transfer_items sti
            JOIN spareparts_transfers st ON sti.transfer_id = st.id
            LEFT JOIN spareparts_inventory si ON sti.part_no = si.part_no AND si.current_branch = st.from_branch
            WHERE sti.transfer_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode(['success' => true, 'items' => $items]);
}

function processPartialTransfer($conn, $currentBranch) {
    if (!isset($_POST['transfer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing Transfer ID']);
        return;
    }
    
    $transferId = (int)$_POST['transfer_id'];
    $acceptedIds = json_decode($_POST['accepted_ids'] ?? '[]', true);
    $returnedItems = json_decode($_POST['returned_items'] ?? '[]', true); // Array of {id, reason}

    $conn->begin_transaction();
    try {
        // Fetch transfer record
        $tStmt = $conn->prepare("SELECT from_branch, to_branch FROM spareparts_transfers WHERE id = ?");
        $tStmt->bind_param('i', $transferId);
        $tStmt->execute();
        $transfer = $tStmt->get_result()->fetch_assoc();
        
        if (!$transfer) throw new Exception("Transfer not found.");
        $from_branch = $transfer['from_branch'];

        // Process Accepted Items
        if (!empty($acceptedIds)) {
            $idsPlaceholders = implode(',', array_fill(0, count($acceptedIds), '?'));
            $sql = "SELECT sti.*, si.brand, si.price, si.min_stock 
                    FROM spareparts_transfer_items sti 
                    LEFT JOIN spareparts_inventory si ON sti.part_no = si.part_no AND si.current_branch = ?
                    WHERE sti.id IN ($idsPlaceholders)";
            
            $stmt = $conn->prepare($sql);
            $params = array_merge([$from_branch], $acceptedIds);
            $types = "s" . str_repeat('i', count($acceptedIds));
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($items as $item) {
                // Add to Current Branch Inventory
                $insertSql = "INSERT INTO spareparts_inventory (brand, part_no, description, current_stock, cost, price, min_stock, current_branch) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              current_stock = current_stock + VALUES(current_stock), 
                              cost = VALUES(cost)";
                $iStmt = $conn->prepare($insertSql);
                $brand = $item['brand'] ?? '';
                $price = (float)($item['price'] ?? 0);
                $min_stock = (int)($item['min_stock'] ?? 5);
                $iStmt->bind_param('sssiddis', $brand, $item['part_no'], $item['description'], $item['quantity'], $item['cost'], $price, $min_stock, $currentBranch);
                $iStmt->execute();

                // Log TRANSFER_IN Transaction
                $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, status) 
                                           VALUES (CURDATE(), 'TRANSFER_IN', ?, ?, ?, ?, ?, ?, ?, 'Completed')");
                $total = $item['quantity'] * $item['cost'];
                $logStmt->bind_param('ssiddss', $item['part_no'], $item['description'], $item['quantity'], $item['cost'], $total, $from_branch, $currentBranch);
                $logStmt->execute();
            }
        }

        // Process Returned Items
        if (!empty($returnedItems)) {
            foreach ($returnedItems as $ret) {
                $itemId = (int)$ret['id'];
                $reason = sanitizeIn($ret['reason'] ?? 'Rejected/Returned by branch');

                $rStmt = $conn->prepare("SELECT * FROM spareparts_transfer_items WHERE id = ?");
                $rStmt->bind_param('i', $itemId);
                $rStmt->execute();
                $item = $rStmt->get_result()->fetch_assoc();

                if ($item) {
                    // Return stock to origin inventory
                    $updSql = "UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?";
                    $upd = $conn->prepare($updSql);
                    $upd->bind_param('iss', $item['quantity'], $item['part_no'], $from_branch);
                    $upd->execute();

                    // Log RETURN Transaction
                    $logSql = "INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, from_location, to_location, status, reason) 
                               VALUES (CURDATE(), 'ADJUSTMENT', ?, ?, ?, ?, ?, 'Returned', ?)";
                    $log = $conn->prepare($logSql);
                    $log->bind_param('ssisss', $item['part_no'], $item['description'], $item['quantity'], $currentBranch, $from_branch, $reason);
                    $log->execute();
                    
                    // Add a notification/audit entry for the origin branch
                    if (function_exists('addAuditLog')) {
                        addAuditLog('RETURN', 'spareparts_transfers', $transferId, "Item {$item['part_no']} returned to $from_branch from $currentBranch. Reason: $reason", $conn);
                    }
                }
            }
        }

        // Finalize Transfer Status
        $finalStatus = empty($returnedItems) ? 'Completed' : (empty($acceptedIds) ? 'Rejected' : 'Completed (Partial)');
        $updFinal = $conn->prepare("UPDATE spareparts_transfers SET status = ?, received_date = NOW() WHERE id = ?");
        $updFinal->bind_param('si', $finalStatus, $transferId);
        $updFinal->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Transfer processed as $finalStatus."]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Helper for audit logs if not already included
if (!function_exists('addAuditLog')) {
    function addAuditLog($type, $table, $refId, $details, $conn) {
        $user = $_SESSION['username'] ?? 'SYSTEM';
        $sql = "INSERT INTO audit_logs (username, action_type, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $user, $type, $table, $refId, $details);
        $stmt->execute();
    }
}

function sanitizeIn($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}
