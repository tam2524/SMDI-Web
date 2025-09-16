<?php
header('Content-Type: application/json');
require_once '../api/db_config.php';

function sanitizeInput($data) {
    global $conn;
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

$action = isset($_REQUEST['action']) ? sanitizeInput($_REQUEST['action']) : '';

switch ($action) {
    case 'add_parts_in':
        addPartsIn();
        break;
    case 'sell_parts_out':
        sellPartsOut();
        break;
    case 'record_payment':
        recordPayment();
        break;
    case 'transfer_parts':
        transferParts();
        break;
    case 'get_inventory_dashboard':
        getInventoryDashboard();
        break;
    case 'get_aging_report':
        getAgingReport();
        break;
    case 'get_sales_report':
        getSalesReport();
        break;
    case 'get_payments_report':
        getPaymentsReport();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function addPartsIn() {
    global $conn;
    $required = ['part_no', 'quantity', 'cost', 'date', 'invoice_no', 'branch'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $partNo = sanitizeInput($_POST['part_no']);
    $quantity = (int)$_POST['quantity'];
    $cost = (float)$_POST['cost'];
    $date = sanitizeInput($_POST['date']);
    $invoiceNo = sanitizeInput($_POST['invoice_no']);
    $branch = sanitizeInput($_POST['branch']);
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : 'N/A';

    $conn->begin_transaction();

    try {
        // Check if part exists in inventory for the branch
        $checkStmt = $conn->prepare("SELECT * FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $checkStmt->bind_param('ss', $partNo, $branch);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $existingPart = $result->fetch_assoc();
            $newStock = $existingPart['current_stock'] + $quantity;
            $newCost = (($existingPart['current_stock'] * $existingPart['cost']) + ($quantity * $cost)) / $newStock;

            // Update existing part
            $updateStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = ?, cost = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->bind_param('idi', $newStock, $newCost, $existingPart['id']);
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update existing part: ' . $updateStmt->error);
            }
        } else {
            // Insert new part
            $insertStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?)");
            $price = $cost * 1.25; // Assuming 25% markup for new parts
            $insertStmt->bind_param('ssiddi', $partNo, $description, $quantity, $cost, $price, $branch);
            if (!$insertStmt->execute()) {
                throw new Exception('Failed to insert new part: ' . $insertStmt->error);
            }
        }

        // Log transaction
        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_cost, total_amount, or_number, from_location) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?)");
        $totalAmount = $quantity * $cost;
        $logStmt->bind_param('ssiddss', $partNo, $date, $quantity, $cost, $totalAmount, $invoiceNo, $branch);
        if (!$logStmt->execute()) {
            throw new Exception('Failed to log transaction: ' . $logStmt->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Parts received successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error receiving parts: ' . $e->getMessage()]);
    }
}

function sellPartsOut() {
    global $conn;
    $required = ['part_no', 'quantity', 'amount', 'date', 'transaction_type', 'or_number', 'customer_name', 'branch'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $partNo = sanitizeInput($_POST['part_no']);
    $quantity = (int)$_POST['quantity'];
    $amount = (float)$_POST['amount'];
    $date = sanitizeInput($_POST['date']);
    $transactionType = sanitizeInput($_POST['transaction_type']);
    $orNumber = sanitizeInput($_POST['or_number']);
    $customerName = sanitizeInput($_POST['customer_name']);
    $branch = sanitizeInput($_POST['branch']);

    $conn->begin_transaction();

    try {
        // Check stock availability
        $stockStmt = $conn->prepare("SELECT current_stock, cost FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $stockStmt->bind_param('ss', $partNo, $branch);
        $stockStmt->execute();
        $result = $stockStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Part number '$partNo' not found in inventory for this branch.");
        }
        $part = $result->fetch_assoc();
        if ($part['current_stock'] < $quantity) {
            throw new Exception("Insufficient stock for part number '$partNo'. Available: {$part['current_stock']}");
        }

        // Update inventory
        $newStock = $part['current_stock'] - $quantity;
        $updateStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = ? WHERE part_no = ? AND current_branch = ?");
        $updateStmt->bind_param('iss', $newStock, $partNo, $branch);
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update inventory: ' . $updateStmt->error);
        }

        // Log transaction
        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_price, total_amount, customer_name, or_number, from_location) VALUES (?, ?, 'OUT', ?, ?, ?, ?, ?, ?)");
        $unitPrice = $amount / $quantity;
        $logStmt->bind_param('ssiddsss', $partNo, $date, $quantity, $unitPrice, $amount, $customerName, $orNumber, $branch);
        if (!$logStmt->execute()) {
            throw new Exception('Failed to log transaction: ' . $logStmt->error);
        }

        // Handle installment/aging
        if ($transactionType === 'installment') {
            $agingStmt = $conn->prepare("INSERT INTO spareparts_aging (or_number, customer_name, sale_date, total_amount, balance, branch) VALUES (?, ?, ?, ?, ?, ?)");
            $agingStmt->bind_param('ssddss', $orNumber, $customerName, $date, $amount, $amount, $branch);
            if (!$agingStmt->execute()) {
                throw new Exception('Failed to create aging record: ' . $agingStmt->error);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale recorded successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error recording sale: ' . $e->getMessage()]);
    }
}

function recordPayment() {
    global $conn;
    $required = ['payment_date', 'customer_name', 'amount', 'or_number'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    $paymentDate = sanitizeInput($_POST['payment_date']);
    $customerName = sanitizeInput($_POST['customer_name']);
    $amount = (float)$_POST['amount'];
    $orNumber = sanitizeInput($_POST['or_number']);
    $branch = isset($_POST['branch']) ? sanitizeInput($_POST['branch']) : 'MAIN';

    $conn->begin_transaction();

    try {
        // Find and update the aging record
        $agingStmt = $conn->prepare("SELECT id, balance FROM spareparts_aging WHERE or_number = ? AND customer_name = ? AND branch = ? AND status = 'Active'");
        $agingStmt->bind_param('sss', $orNumber, $customerName, $branch);
        $agingStmt->execute();
        $result = $agingStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No active installment sale found for OR #$orNumber and customer '$customerName'.");
        }
        $agingRecord = $result->fetch_assoc();
        $newBalance = $agingRecord['balance'] - $amount;

        if ($newBalance < 0) {
            throw new Exception("Payment amount exceeds outstanding balance. Balance: {$agingRecord['balance']}");
        }

        $status = ($newBalance == 0) ? 'Paid' : 'Active';
        $updateStmt = $conn->prepare("UPDATE spareparts_aging SET balance = ?, status = ? WHERE id = ?");
        $updateStmt->bind_param('dsi', $newBalance, $status, $agingRecord['id']);
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update aging record: ' . $updateStmt->error);
        }

        // Log payment transaction
        $logStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, total_amount, customer_name, or_number, from_location) VALUES ('PAYMENT', ?, 'PAYMENT', 0, ?, ?, ?, ?)");
        $logStmt->bind_param('sdsss', $paymentDate, $amount, $customerName, $orNumber, $branch);
        if (!$logStmt->execute()) {
            throw new Exception('Failed to log payment transaction: ' . $logStmt->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully. New balance is ' . number_format($newBalance, 2)]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()]);
    }
}

function transferParts() {
    global $conn;
    $required = ['part_no', 'quantity', 'cost', 'transfer_date', 'from_branch', 'to_branch'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $partNo = sanitizeInput($_POST['part_no']);
    $quantity = (int)$_POST['quantity'];
    $cost = (float)$_POST['cost'];
    $transferDate = sanitizeInput($_POST['transfer_date']);
    $fromBranch = sanitizeInput($_POST['from_branch']);
    $toBranch = sanitizeInput($_POST['to_branch']);
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

    if ($fromBranch === $toBranch) {
        echo json_encode(['success' => false, 'message' => 'Cannot transfer to the same branch.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Decrease stock at from_branch
        $fromStmt = $conn->prepare("SELECT current_stock FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $fromStmt->bind_param('ss', $partNo, $fromBranch);
        $fromStmt->execute();
        $fromResult = $fromStmt->get_result();

        if ($fromResult->num_rows === 0 || $fromResult->fetch_assoc()['current_stock'] < $quantity) {
            throw new Exception('Insufficient stock at source branch.');
        }

        $updateFromStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock - ? WHERE part_no = ? AND current_branch = ?");
        $updateFromStmt->bind_param('iss', $quantity, $partNo, $fromBranch);
        if (!$updateFromStmt->execute()) {
            throw new Exception('Failed to update source branch stock: ' . $updateFromStmt->error);
        }

        // Increase stock at to_branch
        $toStmt = $conn->prepare("SELECT id FROM spareparts_inventory WHERE part_no = ? AND current_branch = ?");
        $toStmt->bind_param('ss', $partNo, $toBranch);
        $toStmt->execute();
        $toResult = $toStmt->get_result();

        if ($toResult->num_rows > 0) {
            $updateToStmt = $conn->prepare("UPDATE spareparts_inventory SET current_stock = current_stock + ? WHERE part_no = ? AND current_branch = ?");
            $updateToStmt->bind_param('iss', $quantity, $partNo, $toBranch);
            if (!$updateToStmt->execute()) {
                throw new Exception('Failed to update destination branch stock: ' . $updateToStmt->error);
            }
        } else {
            // Assuming we need a part description to add a new record.
            // This could be fetched from the source or passed in the form.
            $descriptionStmt = $conn->prepare("SELECT description, price FROM spareparts_inventory WHERE part_no = ? LIMIT 1");
            $descriptionStmt->bind_param('s', $partNo);
            $descriptionStmt->execute();
            $descriptionResult = $descriptionStmt->get_result();
            $partDetails = $descriptionResult->fetch_assoc();
            
            $insertStmt = $conn->prepare("INSERT INTO spareparts_inventory (part_no, description, current_stock, cost, price, current_branch) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param('ssiddi', $partNo, $partDetails['description'], $quantity, $cost, $partDetails['price'], $toBranch);
            if (!$insertStmt->execute()) {
                throw new Exception('Failed to insert new part at destination branch: ' . $insertStmt->error);
            }
        }

        // Log transaction for OUT
        $logOutStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_cost, total_amount, from_location, to_location, notes) VALUES (?, ?, 'TRANSFER_OUT', ?, ?, ?, ?, ?, ?)");
        $totalCost = $quantity * $cost;
        $logOutStmt->bind_param('sidddsss', $partNo, $transferDate, $quantity, $cost, $totalCost, $fromBranch, $toBranch, $notes);
        if (!$logOutStmt->execute()) {
            throw new Exception('Failed to log transfer out transaction: ' . $logOutStmt->error);
        }

        // Log transaction for IN
        $logInStmt = $conn->prepare("INSERT INTO spareparts_transactions (part_no, transaction_date, type, quantity, unit_cost, total_amount, from_location, to_location, notes) VALUES (?, ?, 'TRANSFER_IN', ?, ?, ?, ?, ?, ?)");
        $logInStmt->bind_param('sidddsss', $partNo, $transferDate, $quantity, $cost, $totalCost, $fromBranch, $toBranch, $notes);
        if (!$logInStmt->execute()) {
            throw new Exception('Failed to log transfer in transaction: ' . $logInStmt->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Parts transferred successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error transferring parts: ' . $e->getMessage()]);
    }
}

// Reports
function getInventoryDashboard() {
    global $conn;
    $sql = "SELECT part_no, description, current_stock, cost FROM spareparts_inventory ORDER BY part_no";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function getAgingReport() {
    global $conn;
    $sql = "SELECT * FROM spareparts_aging WHERE balance > 0 ORDER BY sale_date ASC";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}

function getSalesReport() {
    global $conn;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;

    $sql = "SELECT * FROM spareparts_transactions WHERE type = 'OUT'";
    $params = [];
    $types = '';

    if ($date) {
        $sql .= " AND transaction_date = ?";
        $params[] = $date;
        $types .= 's';
    } else if ($month) {
        $sql .= " AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
        $params[] = $month;
        $types .= 's';
    }

    $sql .= " ORDER BY transaction_date DESC";

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

function getPaymentsReport() {
    global $conn;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;

    $sql = "SELECT * FROM spareparts_transactions WHERE type = 'PAYMENT'";
    $params = [];
    $types = '';

    if ($date) {
        $sql .= " AND transaction_date = ?";
        $params[] = $date;
        $types .= 's';
    } else if ($month) {
        $sql .= " AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
        $params[] = $month;
        $types .= 's';
    }

    $sql .= " ORDER BY transaction_date DESC";

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
?>