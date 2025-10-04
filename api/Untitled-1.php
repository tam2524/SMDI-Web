// REPLACE getMonthlyTransferredSummary with this:
function getMonthlyTransferredSummary() {
    global $conn;

    // Get all possible date and filter parameters
    $period_type = isset($_GET['period_type']) ? sanitizeInput($_GET['period_type']) : 'monthly';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
    $brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : 'all';

    // Determine startDate and endDate from parameters
    list($startDate, $endDate, $reportContext) = getDateRangeForReport($period_type, $date, $month, $start_date, $end_date);
    if (!$startDate) { echo json_encode(['success' => false, 'message' => $endDate]); return; }

    $sql = "SELECT mi.model, mi.color, mi.brand, mi.engine_number, mi.frame_number, mi.inventory_cost,
                   it.transfer_date, it.to_branch as transferred_to, it.from_branch as transferred_from, i.invoice_number
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE it.transfer_date BETWEEN ? AND ? AND it.transfer_status = 'completed'";
    
    $params = [$startDate, $endDate];
    $types = 'ss';

    if ($branch !== 'all') { $sql .= " AND it.from_branch = ?"; $params[] = $branch; $types .= 's'; }
    if ($category !== 'all') { $sql .= " AND mi.category = ?"; $params[] = $category; $types .= 's'; }
    if ($brand !== 'all') { $sql .= " AND mi.brand = ?"; $params[] = $brand; $types .= 's'; }

    $sql .= " ORDER BY it.transfer_date DESC, mi.model";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $totalInventoryCost = 0;
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalInventoryCost += (float)$row['inventory_cost'];
    }

    echo json_encode([
        'success' => true, 'data' => $data, 'report_context' => $reportContext, 'period_type' => $period_type,
        'summary' => ['total_transferred' => count($data), 'total_inventory_cost' => $totalInventoryCost]
    ]);
}

// REPLACE getMonthlyReceivedSummary with this:
function getMonthlyReceivedSummary() {
    global $conn;
    
    $period_type = isset($_GET['period_type']) ? sanitizeInput($_GET['period_type']) : 'monthly';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;

    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
    $brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : 'all';

    list($startDate, $endDate, $reportContext) = getDateRangeForReport($period_type, $date, $month, $start_date, $end_date);
    if (!$startDate) { echo json_encode(['success' => false, 'message' => $endDate]); return; }

    $sql = "SELECT mi.model, mi.color, mi.brand, mi.engine_number, mi.frame_number, mi.inventory_cost,
                   it.date_received, it.from_branch as received_from, it.to_branch as received_by, i.invoice_number
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE it.date_received BETWEEN ? AND ? AND it.transfer_status = 'completed'";

    $params = [$startDate, $endDate];
    $types = 'ss';

    if ($branch !== 'all') { $sql .= " AND it.to_branch = ?"; $params[] = $branch; $types .= 's'; }
    if ($category !== 'all') { $sql .= " AND mi.category = ?"; $params[] = $category; $types .= 's'; }
    if ($brand !== 'all') { $sql .= " AND mi.brand = ?"; $params[] = $brand; $types .= 's'; }

    $sql .= " ORDER BY it.date_received DESC, mi.model";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $totalInventoryCost = 0;
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalInventoryCost += (float)$row['inventory_cost'];
    }
    
    echo json_encode([
        'success' => true, 'data' => $data, 'report_context' => $reportContext, 'period_type' => $period_type,
        'summary' => ['total_received' => count($data), 'total_inventory_cost' => $totalInventoryCost]
    ]);
}

// REPLACE getMonthlyScrappedSummary with this:
function getMonthlyScrappedSummary() {
    global $conn;
    
    $period_type = isset($_GET['period_type']) ? sanitizeInput($_GET['period_type']) : 'monthly';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
    $brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : 'all';

    list($startDate, $endDate, $reportContext) = getDateRangeForReport($period_type, $date, $month, $start_date, $end_date);
    if (!$startDate) { echo json_encode(['success' => false, 'message' => $endDate]); return; }

    $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.current_branch,
                   mi.inventory_cost, mi.category, ms.scrap_date, ms.scrap_reason, i.invoice_number
            FROM motorcycle_scraps ms
            INNER JOIN motorcycle_inventory mi ON ms.motorcycle_id = mi.id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE ms.scrap_date BETWEEN ? AND ?";

    $params = [$startDate, $endDate];
    $types = 'ss';

    if ($branch !== 'all') { $sql .= " AND mi.current_branch = ?"; $params[] = $branch; $types .= 's'; }
    if ($category !== 'all') { $sql .= " AND mi.category = ?"; $params[] = $category; $types .= 's'; }
    if ($brand !== 'all') { $sql .= " AND mi.brand = ?"; $params[] = $brand; $types .= 's'; }

    $sql .= " ORDER BY ms.scrap_date DESC, mi.brand, mi.model";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $totalInventoryCost = 0;
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalInventoryCost += (float)$row['inventory_cost'];
    }

    echo json_encode([
        'success' => true, 'data' => $data, 'report_context' => $reportContext, 'period_type' => $period_type,
        'summary' => ['total_scrapped' => count($data), 'total_inventory_cost' => $totalInventoryCost]
    ]);
}