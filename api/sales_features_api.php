<?php
require_once 'db_config.php';

header('Content-Type: application/json');

// Ensure tables exist
$conn->query("ALTER TABLE spareparts_transactions MODIFY COLUMN type VARCHAR(50)");
$conn->query("UPDATE spareparts_transactions SET type = 'RETURN' WHERE type = '' AND type != 'OUT' AND type != 'IN'");

$conn->query("
CREATE TABLE IF NOT EXISTS spareparts_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_no VARCHAR(50),
    address TEXT,
    rank_level VARCHAR(50) DEFAULT 'Standard',
    term INT DEFAULT 0,
    credit_limit DECIMAL(15,2) DEFAULT 0.00,
    branch VARCHAR(100) DEFAULT 'HEADOFFICE',
    category VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

// Ensure new columns exist
$res = $conn->query("SHOW COLUMNS FROM spareparts_customers LIKE 'rank_level'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE spareparts_customers ADD COLUMN rank_level VARCHAR(50) DEFAULT 'Standard' AFTER address");
}
$res = $conn->query("SHOW COLUMNS FROM spareparts_customers LIKE 'term'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE spareparts_customers ADD COLUMN term INT DEFAULT 0 AFTER rank_level");
}
$res = $conn->query("SHOW COLUMNS FROM spareparts_customers LIKE 'credit_limit'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE spareparts_customers ADD COLUMN credit_limit DECIMAL(15,2) DEFAULT 0.00 AFTER term");
}
$res = $conn->query("SHOW COLUMNS FROM spareparts_customers LIKE 'category'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE spareparts_customers ADD COLUMN category VARCHAR(50) DEFAULT NULL AFTER branch");
}

$conn->query("
CREATE TABLE IF NOT EXISTS spareparts_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_returned DATE NOT NULL,
    customer_name VARCHAR(255),
    or_number VARCHAR(100),
    part_no VARCHAR(100) DEFAULT '',
    part_name VARCHAR(255),
    qty_returned INT DEFAULT 0,
    amount_credited DECIMAL(10,2) DEFAULT 0.00,
    remarks TEXT,
    branch VARCHAR(100) DEFAULT 'HEADOFFICE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
");

// Ensure part_no column exists for databases created before this migration
$colCheck = $conn->query("SHOW COLUMNS FROM spareparts_returns LIKE 'part_no'");
if ($colCheck && $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE spareparts_returns ADD COLUMN part_no VARCHAR(100) DEFAULT '' AFTER or_number");
}

$action = $_GET['action'] ?? '';
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['user_role'] ?? $_SESSION['position'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$isAdmin = in_array(strtolower(trim($userRole)), array_map('strtolower', $adminRoles));

switch ($action) {
    case 'get_customers':
        $sql = "SELECT * FROM spareparts_customers";
        $params = [];
        $types = "";
        
        if (!$isAdmin && $branch !== 'HEADOFFICE') {
            $sql .= " WHERE branch = ?";
            $params[] = $branch;
            $types .= "s";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $customers = $result->fetch_all(MYSQLI_ASSOC);
        
        // Let's attach balance from spareparts_aging
        foreach($customers as &$cust) {
            $cname = $cust['name'];
            $balStmt = $conn->prepare("SELECT SUM(balance) as balance FROM spareparts_aging WHERE customer_name=?");
            $balStmt->bind_param("s", $cname);
            $balStmt->execute();
            $br = $balStmt->get_result()->fetch_assoc();
            $cust['balance'] = $br['balance'] ?? 0;
            $balStmt->close();
        }
        
        echo json_encode(["success" => true, "data" => $customers]);
        break;

    case 'add_customer':
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_no'] ?? '';
        $address = $_POST['address'] ?? '';
        $rank = $_POST['rank_level'] ?? 'Standard';
        $term = $_POST['term'] ?? 0;
        $limit = $_POST['credit_limit'] ?? 0;
        
        // Determine category (both sales and retail map to Wholesale in spareparts_inventory.php)
        $category = (strpos(strtolower($userRole), 'retail') !== false || strpos(strtolower($userRole), 'sales') !== false) ? 'Wholesale' : null;

        $stmt = $conn->prepare("INSERT INTO spareparts_customers (name, contact_no, address, rank_level, term, credit_limit, branch, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssidss", $name, $contact, $address, $rank, $term, $limit, $branch, $category);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Customer added"]);
        } else {
            echo json_encode(["success" => false, "message" => $stmt->error]);
        }
        break;

    case 'edit_customer':
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact_no'] ?? '';
        $address = $_POST['address'] ?? '';
        $rank = $_POST['rank_level'] ?? 'Standard';
        $term = $_POST['term'] ?? 0;
        $limit = $_POST['credit_limit'] ?? 0;
        
        // Determine category
        $category = (strpos(strtolower($userRole), 'retail') !== false || strpos(strtolower($userRole), 'sales') !== false) ? 'Wholesale' : null;
        
        $ownerCheck = "";
        $params = [$name, $contact, $address, $rank, $term, $limit, $category, $id];
        $types = "sssidisi";
        
        if (!$isAdmin && $branch !== 'HEADOFFICE') {
            $ownerCheck = " AND branch = ?";
            $params[] = $branch;
            $types .= "s";
        }

        $stmt = $conn->prepare("UPDATE spareparts_customers SET name=?, contact_no=?, address=?, rank_level=?, term=?, credit_limit=?, category=? WHERE id=? $ownerCheck");
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Customer updated"]);
        } else {
            echo json_encode(["success" => false, "message" => $stmt->error]);
        }
        break;

    case 'delete_customer':
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            echo json_encode(["success" => false, "message" => "ID is required"]);
            exit;
        }
        
        $ownerCheck = "";
        $params = [$id];
        $types = "i";
        
        if (!$isAdmin && $branch !== 'HEADOFFICE') {
            $ownerCheck = " AND branch = ?";
            $params[] = $branch;
            $types .= "s";
        }

        $stmt = $conn->prepare("DELETE FROM spareparts_customers WHERE id=? $ownerCheck");
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Customer deleted"]);
        } else {
            echo json_encode(["success" => false, "message" => $stmt->error]);
        }
        break;

    case 'get_returns':
        $sql = "SELECT * FROM spareparts_returns";
        $params = [];
        $types = "";
        
        if (!$isAdmin && $branch !== 'HEADOFFICE') {
            $sql .= " WHERE branch = ?";
            $params[] = $branch;
            $types .= "s";
        }
        
        $sql .= " ORDER BY date_returned DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $returns = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(["success" => true, "data" => $returns]);
        break;

    case 'search_customers_for_return':
        $term = $_GET['term'] ?? '';
        if (strlen($term) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            break;
        }
        $likeTerm = "%$term%";
        $sql = "SELECT DISTINCT customer_name FROM spareparts_transactions WHERE type = 'OUT' AND customer_name LIKE ? AND customer_name != ''";
        if (!$isAdmin && $branch !== 'HEADOFFICE') {
            $sql .= " AND from_location = ?";
            $stmt = $conn->prepare($sql . " ORDER BY customer_name ASC LIMIT 20");
            $stmt->bind_param('ss', $likeTerm, $branch);
        } else {
            $stmt = $conn->prepare($sql . " ORDER BY customer_name ASC LIMIT 20");
            $stmt->bind_param('s', $likeTerm);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $names = array_column($result, 'customer_name');
        echo json_encode(['success' => true, 'data' => $names]);
        break;

    case 'get_customer_sales':
        $customer = $_GET['customer_name'] ?? '';
        if (empty($customer)) {
            echo json_encode(['success' => false, 'message' => 'Customer name is required.']);
            break;
        }
        // Get all OUT transactions for this customer, grouped by OR number
        $sql = "SELECT t.id, t.or_number, t.transaction_date, t.part_no, t.description, t.quantity, t.price, t.total_amount, t.from_location,
                    COALESCE((SELECT SUM(r.qty_returned) FROM spareparts_returns r WHERE r.or_number = t.or_number AND r.part_no = t.part_no AND r.branch = t.from_location), 0) as already_returned
                FROM spareparts_transactions t
                WHERE t.type = 'OUT' AND t.customer_name = ?";
        if (!$isAdmin && $branch !== 'HEADOFFICE') {
            $sql .= " AND t.from_location = ?";
            $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $customer, $branch);
        } else {
            $sql .= " ORDER BY t.transaction_date DESC, t.id DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $customer);
        }
        $stmt->execute();
        $sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate returnable qty
        foreach ($sales as &$s) {
            $s['returnable_qty'] = max(0, (int)$s['quantity'] - (int)$s['already_returned']);
        }
        
        echo json_encode(['success' => true, 'data' => $sales]);
        break;

    case 'add_return':
        $items = json_decode($_POST['items'] ?? '[]', true);
        $customer_name = $_POST['customer_name'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        $remarks = $_POST['remarks'] ?? 'Returned to inventory';

        if (empty($items) || empty($customer_name)) {
            echo json_encode(['success' => false, 'message' => 'No items or customer specified.']);
            break;
        }

        // Ensure part_no column exists in spareparts_returns
        $col = $conn->query("SHOW COLUMNS FROM spareparts_returns LIKE 'part_no'");
        if ($col->num_rows == 0) {
            $conn->query("ALTER TABLE spareparts_returns ADD COLUMN part_no VARCHAR(100) DEFAULT '' AFTER or_number");
        }

        $conn->begin_transaction();
        try {
            $totalCredited = 0;
            foreach ($items as $item) {
                $txId = (int)($item['tx_id'] ?? 0);
                $qtyReturn = (int)($item['qty'] ?? 0);
                if ($txId <= 0 || $qtyReturn <= 0) continue;

                // Fetch original transaction
                $txStmt = $conn->prepare("SELECT * FROM spareparts_transactions WHERE id = ? AND type = 'OUT'");
                $txStmt->bind_param('i', $txId);
                $txStmt->execute();
                $tx = $txStmt->get_result()->fetch_assoc();
                if (!$tx) throw new Exception("Transaction #$txId not found.");

                // Check returnable qty
                $retStmt = $conn->prepare("SELECT COALESCE(SUM(qty_returned),0) as returned FROM spareparts_returns WHERE or_number = ? AND part_no = ? AND branch = ?");
                $retStmt->bind_param('sss', $tx['or_number'], $tx['part_no'], $tx['from_location']);
                $retStmt->execute();
                $alreadyReturned = (int)$retStmt->get_result()->fetch_assoc()['returned'];
                $maxReturnable = (int)$tx['quantity'] - $alreadyReturned;
                if ($qtyReturn > $maxReturnable) {
                    throw new Exception("Cannot return $qtyReturn of {$tx['part_no']}. Max returnable: $maxReturnable.");
                }

                $creditAmount = $qtyReturn * (float)$tx['price'];
                $totalCredited += $creditAmount;

                // 1. Insert into spareparts_returns
                $retInsert = $conn->prepare("INSERT INTO spareparts_returns (date_returned, customer_name, or_number, part_no, part_name, qty_returned, amount_credited, branch, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $retInsert->bind_param('sssssidss', $date, $customer_name, $tx['or_number'], $tx['part_no'], $tx['description'], $qtyReturn, $creditAmount, $tx['from_location'], $remarks);
                $retInsert->execute();

                // 2. Add stock back
                $stockStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
                $stockStmt->bind_param('iss', $qtyReturn, $tx['part_no'], $tx['from_location']);
                $stockStmt->execute();

                // 3. Log RETURN transaction
                $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (transaction_date, type, part_no, description, quantity, price, total_amount, from_location, to_location, customer_name, or_number, status) VALUES (?, 'RETURN', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Completed')");
                $logStmt->bind_param('sssiddssss', $date, $tx['part_no'], $tx['description'], $qtyReturn, $tx['price'], $creditAmount, $customer_name, $tx['from_location'], $customer_name, $tx['or_number']);
                $logStmt->execute();

                // 4. Adjust aging balance if this was a charge sale
                $agingStmt = $conn->prepare("UPDATE spareparts_aging SET balance = GREATEST(0, balance - ?), status = IF(balance - ? <= 0, 'Paid', status) WHERE or_number = ? AND customer_name = ? AND branch = ?");
                $agingStmt->bind_param('ddsss', $creditAmount, $creditAmount, $tx['or_number'], $customer_name, $tx['from_location']);
                $agingStmt->execute();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => "Return processed. Total credited: ₱" . number_format($totalCredited, 2)]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Return failed: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}
?>
