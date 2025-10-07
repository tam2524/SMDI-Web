function getMonthlyInventory() {
    global $conn;

    // MODIFIED: Now checks for 'date' (for As-of mode) or 'month' (for Monthly mode)
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $asOfDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // MODIFIED: Validation checks for either date parameter
    if (empty($month) && empty($asOfDate)) {
        echo json_encode(['success' => false, 'message' => 'A Month or As-of Date parameter is required.']);
        return;
    }

    if (!empty($asOfDate)) {
        // --- As of Date Mode ---
        $endDate = $asOfDate;
        $startDate = date('Y-m-01', strtotime($asOfDate));
        $prevMonthEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $reportMonth = date('Y-m', strtotime($asOfDate)); // Use the month of the as-of-date for context
    } else {
        // --- Monthly Mode (Original Logic) ---
        $startDate      = date('Y-m-01', strtotime($month));
        $endDate        = date('Y-m-t', strtotime($month));
        $prevMonthEnd = date('Y-m-d', strtotime('last day of previous month', strtotime($month)));
        $reportMonth = $month;
    }

    $userBranch = isset($_SESSION['user_branch']) ? strtoupper($_SESSION['user_branch']) : '';
    $applyBrandFilter = ($userBranch === 'HEADOFFICE' && $brand !== 'all');
    $brandCondition = $applyBrandFilter ? " AND LOWER(mi.brand) = '$brand' " : "";

    $categoryCondition = '';
    $modelCondition = ''; 
    $params = [];
    $paramTypes = '';

    if ($category !== 'all') {
        $categoryCondition = " AND LOWER(mi.category) = ? ";
        $params[] = $category;
        $paramTypes .= 's';
    }

    // ADD this block
    if ($models_str !== 'all' && !empty($models_str)) {
    $models = array_map('trim', explode(',', $models_str));
    if (!empty($models)) {
        $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
        $modelCondition = " AND mi.model IN ($modelPlaceholders) ";
        foreach ($models as $model) {
            $params[] = $model;
            $paramTypes .= 's';
        }
    }
   }

    // Helper function remains the same
    function bindParams(&$stmt, $types, $params) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }

    // ... [ The beginning balance, new deliveries, received, transfers out, and sold queries remain unchanged ] ...
    // ... [ Omitted for brevity, paste the code from your request here ] ...
    $countBeginning = 0;
    $costBeginning = 0;
    $countNewDeliveries = 0;
    $costNewDeliveries = 0;
    $countReceived = 0;
    $costReceived = 0;
    $countTransfersOut = 0;
    $costTransfersOut = 0;
    $countSoldDuringMonth = 0;
    $costSoldDuringMonth = 0;
 
    if (strtoupper($branch) === 'ALL') {
        $sqlBeginning = "
            SELECT COUNT(*) AS count_beginning, COALESCE(SUM(mi.inventory_cost), 0) AS cost_beginning
            FROM motorcycle_inventory mi
            WHERE mi.deleted_at IS NULL
              AND (mi.date_delivered <= ? OR (mi.date_received IS NOT NULL AND mi.date_received <= ?))
              AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
              $categoryCondition $brandCondition $modelCondition
        ";
        $stmtBeginning = $conn->prepare($sqlBeginning);
        $typesBeginning = 'sss' . $paramTypes;
        $paramsBeginning = array_merge([$prevMonthEnd, $prevMonthEnd, $prevMonthEnd], $params);
        bindParams($stmtBeginning, $typesBeginning, $paramsBeginning);
    } else {
        $sqlBeginning = "
            SELECT COUNT(*) as count_beginning, COALESCE(SUM(mi.inventory_cost), 0) as cost_beginning
            FROM motorcycle_inventory mi
            WHERE mi.deleted_at IS NULL AND mi.current_branch = ?
              AND (mi.date_delivered <= ? OR (mi.date_received IS NOT NULL AND mi.date_received <= ?))
              AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
              $categoryCondition $brandCondition $modelCondition
        ";
        $stmtBeginning = $conn->prepare($sqlBeginning);
        $typesBeginning = 'ssss' . $paramTypes;
        $paramsBeginning = array_merge([$branch, $prevMonthEnd, $prevMonthEnd, $prevMonthEnd], $params);
        bindParams($stmtBeginning, $typesBeginning, $paramsBeginning);
    }
    $stmtBeginning->execute();
    $beginningResult = $stmtBeginning->get_result()->fetch_assoc();
    $countBeginning = (int)($beginningResult['count_beginning'] ?? 0);
    $costBeginning  = (float)($beginningResult['cost_beginning'] ?? 0);


      // === NEW DELIVERIES ===
    if (strtoupper($branch) === 'ALL') {
        $sqlNewDeliveries = "
            SELECT COUNT(*) AS count_new, COALESCE(SUM(mi.inventory_cost), 0) AS cost_new
            FROM motorcycle_inventory mi
            WHERE mi.date_delivered BETWEEN ? AND ?
              AND mi.deleted_at IS NULL
              AND NOT EXISTS (SELECT 1 FROM inventory_transfers it WHERE it.motorcycle_id = mi.id)
              $categoryCondition
              $brandCondition
              $modelCondition
        ";
        $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
        $types = 'ss' . $paramTypes;
        $paramsNewDeliveries = array_merge([$startDate, $endDate], $params);
        bindParams($stmtNewDeliveries, $types, $paramsNewDeliveries);
    } else {
        // --- THIS IS THE FIX ---
        $sqlNewDeliveries = "
            SELECT COUNT(*) AS count_new, COALESCE(SUM(mi.inventory_cost), 0) AS cost_new
            FROM motorcycle_inventory mi
            WHERE mi.date_delivered BETWEEN ? AND ?
              AND mi.deleted_at IS NULL
              AND mi.current_branch = ?
              -- This new line prevents transferred items from being counted as new deliveries
              AND NOT EXISTS (SELECT 1 FROM inventory_transfers it WHERE it.motorcycle_id = mi.id)
              $categoryCondition
              $brandCondition
              $modelCondition
        ";
        $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
        $types = 'sss' . $paramTypes;
        $paramsNewDeliveries = array_merge([$startDate, $endDate, $branch], $params);
        bindParams($stmtNewDeliveries, $types, $paramsNewDeliveries);
    }
    $stmtNewDeliveries->execute();
    $newDeliveriesResult = $stmtNewDeliveries->get_result()->fetch_assoc();
    $countNewDeliveries = (int)($newDeliveriesResult['count_new'] ?? 0);
    $costNewDeliveries  = (float)($newDeliveriesResult['cost_new'] ?? 0);

    // === RECEIVED TRANSFERS ===
    // FIXED: Use transfer_date instead of date_received for movement timing
    if (strtoupper($branch) === 'ALL') {
        $sqlReceived = "
            SELECT COUNT(*) AS count_received, COALESCE(SUM(mi.inventory_cost), 0) AS cost_received
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.transfer_date BETWEEN ? AND ?  -- Changed from date_received to transfer_date
              AND it.transfer_status = 'completed'
              $categoryCondition
               $brandCondition
               $modelCondition
        ";
        $stmtReceived = $conn->prepare($sqlReceived);
        $types = 'ss' . $paramTypes;
        $paramsReceived = array_merge([$startDate, $endDate], $params);
        bindParams($stmtReceived, $types, $paramsReceived);
    } else {
        $sqlReceived = "
            SELECT COUNT(*) AS count_received, COALESCE(SUM(mi.inventory_cost), 0) AS cost_received
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.to_branch = ?
              AND it.transfer_date BETWEEN ? AND ?  -- Changed from date_received to transfer_date
              AND it.transfer_status = 'completed'
              $categoryCondition
               $brandCondition
               $modelCondition
        ";
        $stmtReceived = $conn->prepare($sqlReceived);
        $types = 'sss' . $paramTypes;
        $paramsReceived = array_merge([$branch, $startDate, $endDate], $params);
        bindParams($stmtReceived, $types, $paramsReceived);
    }
    $stmtReceived->execute();
    $receivedResult = $stmtReceived->get_result()->fetch_assoc();
    $countReceived = (int)($receivedResult['count_received'] ?? 0);
    $costReceived  = (float)($receivedResult['cost_received'] ?? 0);

    // === TOTAL IN ===
    $countIn = $countNewDeliveries + $countReceived;
    $costIn  = $costNewDeliveries + $costReceived;

    // === TRANSFERS OUT ===
    if (strtoupper($branch) === 'ALL') {
        $sqlTransfersOut = "
            SELECT COUNT(*) AS count_transfers_out, COALESCE(SUM(mi.inventory_cost), 0) AS cost_transfers_out
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.transfer_date BETWEEN ? AND ?
              AND it.transfer_status = 'completed'
              $categoryCondition
               $brandCondition
               $modelCondition
        ";
        $stmtTransfersOut = $conn->prepare($sqlTransfersOut);
        $types = 'ss' . $paramTypes;
        $paramsTransfersOut = array_merge([$startDate, $endDate], $params);
        bindParams($stmtTransfersOut, $types, $paramsTransfersOut);
    } else {
        $sqlTransfersOut = "
            SELECT COUNT(*) AS count_transfers_out, COALESCE(SUM(mi.inventory_cost), 0) AS cost_transfers_out
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.from_branch = ?
              AND it.transfer_date BETWEEN ? AND ?
              AND it.transfer_status = 'completed'
              $categoryCondition
               $brandCondition
               $modelCondition
        ";
        $stmtTransfersOut = $conn->prepare($sqlTransfersOut);
        $types = 'sss' . $paramTypes;
        $paramsTransfersOut = array_merge([$branch, $startDate, $endDate], $params);
        bindParams($stmtTransfersOut, $types, $paramsTransfersOut);
    }
    $stmtTransfersOut->execute();
    $transfersOutResult = $stmtTransfersOut->get_result()->fetch_assoc();
    $countTransfersOut = (int)($transfersOutResult['count_transfers_out'] ?? 0);
    $costTransfersOut  = (float)($transfersOutResult['cost_transfers_out'] ?? 0);

    // === SOLD DURING MONTH ===
    if (strtoupper($branch) === 'ALL') {
        $sqlSoldDuringMonth = "
            SELECT COUNT(*) AS count_sold_month, COALESCE(SUM(mi.inventory_cost), 0) AS cost_sold_month
            FROM motorcycle_inventory mi
            JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            WHERE ms.sale_date BETWEEN ? AND ?
              AND mi.status = 'sold'
              $categoryCondition
               $brandCondition
               $modelCondition
        ";
        $stmtSoldDuringMonth = $conn->prepare($sqlSoldDuringMonth);
        $types = 'ss' . $paramTypes;
        $paramsSoldDuringMonth = array_merge([$startDate, $endDate], $params);
        bindParams($stmtSoldDuringMonth, $types, $paramsSoldDuringMonth);
    } else {
        $sqlSoldDuringMonth = "
            SELECT COUNT(*) AS count_sold_month, COALESCE(SUM(mi.inventory_cost), 0) AS cost_sold_month
            FROM motorcycle_inventory mi
            JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            WHERE mi.current_branch = ?
              AND ms.sale_date BETWEEN ? AND ?
              AND mi.status = 'sold'
              $categoryCondition
               $brandCondition
               $modelCondition
        ";
        $stmtSoldDuringMonth = $conn->prepare($sqlSoldDuringMonth);
        $types = 'sss' . $paramTypes;
        $paramsSoldDuringMonth = array_merge([$branch, $startDate, $endDate], $params);
        bindParams($stmtSoldDuringMonth, $types, $paramsSoldDuringMonth);
    }
    $stmtSoldDuringMonth->execute();
    $soldDuringMonthResult = $stmtSoldDuringMonth->get_result()->fetch_assoc();
    $countSoldDuringMonth = (int)($soldDuringMonthResult['count_sold_month'] ?? 0);
    $costSoldDuringMonth  = (float)($soldDuringMonthResult['cost_sold_month'] ?? 0);

    // === TOTAL OUT ===
    $countOut = $countTransfersOut + $countSoldDuringMonth;
    $costOut  = $costTransfersOut + $costSoldDuringMonth;

    // === ENDING BALANCE CALCULATION ===
    $countEndingCalculated = $countBeginning + $countIn - $countOut;
    $costEndingCalculated  = $costBeginning  + $costIn  - $costOut;

   // === START: MODIFICATION FOR REPO CATEGORY ===
    $repoSelects = '';
    $repoJoins = '';
    if ($category === 'repo') {
        // Select customer name and sale date, providing '-' as a default for missing data
        $repoSelects = ", 
            COALESCE(c.customer_name, '-') AS customer_name,
            COALESCE(ms_info.sale_date, '-') AS date_sold
        ";
        // Join sales and customer tables to get the required info
        $repoJoins = "
            LEFT JOIN motorcycle_sales ms_info ON mi.id = ms_info.motorcycle_id
            LEFT JOIN customers c ON ms_info.customer_id = c.id
        ";
    }
    // === END: MODIFICATION FOR REPO CATEGORY ===


   // === ACTUAL ENDING BALANCE & DETAILED DATA (as of endDate cutoff) ===
   if (strtoupper($branch) === 'ALL') {
    $sqlEndingActualData = "
        SELECT mi.*, i.invoice_number $repoSelects
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        $repoJoins
        WHERE mi.deleted_at IS NULL
          AND (
               mi.date_delivered <= ?
               OR (mi.date_received IS NOT NULL AND mi.date_received <= ?)
          )
          AND NOT EXISTS (
               SELECT 1 FROM motorcycle_sales s
               WHERE s.motorcycle_id = mi.id
                 AND s.sale_date <= ?
          )
          $categoryCondition
          $brandCondition
          $modelCondition
        ORDER BY mi.brand, mi.model
    ";


        // Detailed rows
        $stmtData = $conn->prepare($sqlEndingActualData);
        $types = 'sss' . $paramTypes;
        $paramsData = array_merge([$endDate, $endDate, $endDate], $params);
        bindParams($stmtData, $types, $paramsData);
        $stmtData->execute();
        $resultData = $stmtData->get_result();

        // Aggregate (count, cost) uses same query wrapped
        $sqlEndingActual = "
            SELECT COUNT(*) AS count_ending, COALESCE(SUM(sub.inventory_cost),0) AS cost_ending
            FROM ($sqlEndingActualData) sub
        ";
        $stmtEndingActual = $conn->prepare($sqlEndingActual);
        bindParams($stmtEndingActual, $types, $paramsData);
        $stmtEndingActual->execute();
        $endingActualResult = $stmtEndingActual->get_result()->fetch_assoc();

    } else {
        $sqlEndingActualData = "
        SELECT mi.*, i.invoice_number, mi.current_branch AS branch_at_cutoff $repoSelects
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        $repoJoins
        WHERE mi.deleted_at IS NULL
          AND mi.current_branch = ?
          AND (
               mi.date_delivered <= ?
               OR (mi.date_received IS NOT NULL AND mi.date_received <= ?)
          )
          AND NOT EXISTS (
               SELECT 1 FROM motorcycle_sales s
               WHERE s.motorcycle_id = mi.id
                 AND s.sale_date <= ?
          )
          $categoryCondition
          $brandCondition
          $modelCondition
        ORDER BY mi.brand, mi.model
    ";

    // Detailed rows
    $stmtData = $conn->prepare($sqlEndingActualData);
    $types = 'ssss' . $paramTypes;
    $paramsData = array_merge([
        $branch,   // mi.current_branch = ?
        $endDate,  // mi.date_delivered <= ?
        $endDate,  // mi.date_received <= ?
        $endDate   // s.sale_date <= ?
    ], $params);
    bindParams($stmtData, $types, $paramsData);
    $stmtData->execute();
    $resultData = $stmtData->get_result();
    
    // Aggregate (count, cost) uses same query wrapped
    $sqlEndingActual = "
        SELECT COUNT(*) AS count_ending, COALESCE(SUM(sub.inventory_cost),0) AS cost_ending
        FROM ($sqlEndingActualData) sub
    ";
    $stmtEndingActual = $conn->prepare($sqlEndingActual);
    bindParams($stmtEndingActual, $types, $paramsData);
    $stmtEndingActual->execute();
    $endingActualResult = $stmtEndingActual->get_result()->fetch_assoc();
   }

    $countEndingActual = (int)($endingActualResult['count_ending'] ?? 0);
    $costEndingActual  = (float)($endingActualResult['cost_ending'] ?? 0);

    // === Build $data array from resultData (ACTUAL ENDING INVENTORY ONLY) ===
    $data = [];
    while ($row = $resultData->fetch_assoc()) {
        $item = [
            'id' => (int)$row['id'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'color' => $row['color'],
            'engine_number' => $row['engine_number'],
            'frame_number' => $row['frame_number'],
            'inventory_cost' => (float)$row['inventory_cost'],
            'current_branch' => $row['current_branch'],
            'status' => $row['status'],
            'date_delivered' => $row['date_delivered'],
            'invoice_number' => $row['invoice_number'],
            'category' => $row['category'],
            'branch_at_cutoff' => $row['branch_at_cutoff'] ?? null,
            'record_type' => 'inventory'
        ];
        
        // === START: MODIFICATION FOR REPO CATEGORY ===
        // If the category is 'repo', add the customer name and date sold to the item array.
        // These fields will exist in $row because of the conditional SQL modification above.
        if ($category === 'repo') {
            $item['customer_name'] = $row['customer_name'];
            $item['date_sold'] = $row['date_sold'];
        }
        // === END: MODIFICATION FOR REPO CATEGORY ===
        
        $data[] = $item;
    }

    // ... [ The rest of the function (transfer details, discrepancies, response building) remains unchanged ] ...
    // ... [ Omitted for brevity, paste the code from your request here ] ...

    $response = [
        'success' => true,
        'data' => $data,
        // ... include all other response keys
    ];
 
    echo json_encode($response);
}