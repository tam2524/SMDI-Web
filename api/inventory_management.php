<?php
/**
 * API Endpoint for Motorcycle Inventory Management
 *
 * This file handles all AJAX requests for the inventory system. It uses a single
 * 'action' parameter to route requests to the appropriate function.
 *
 * @version 1.0
 * @author [Your Name]
 */

// --- INITIALIZATION & CONFIGURATION ---

header('Content-Type: application/json');
require_once '../api/db_config.php';

// --- HELPER FUNCTIONS ---

/**
 * Sanitizes user input to prevent XSS and SQL injection.
 * @param mixed $data The input data to sanitize.
 * @return string The sanitized data.
 */
function sanitizeInput($data)
{
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}

/**
 * Calculates a date range based on a period type.
 * @return array An array containing [startDate, endDate, reportContext].
 */
function getDateRangeForReport($period_type, $date, $month, $start_date, $end_date)
{
    $startDate = '';
    $endDate = '';
    $reportContext = '';

    switch ($period_type) {
        case 'daily':
            if (!$date) {
                return [null, 'Date is required for daily reports.', null];
            }
            $startDate = $date;
            $endDate = $date;
            $reportContext = date('F j, Y', strtotime($date));
            break;
        case 'monthly':
            if (!$month) {
                return [null, 'Month is required for monthly reports.', null];
            }
            $startDate = date('Y-m-01', strtotime($month));
            $endDate = date('Y-m-t', strtotime($month));
            $reportContext = date('F Y', strtotime($month));
            break;
        case 'as_of_date':
            if (!$date) {
                return [null, 'Date is required for as-of-date reports.', null];
            }
            $startDate = date('Y-m-01', strtotime($date));
            $endDate = $date;
            $reportContext = "For the period ending " . date('F j, Y', strtotime($date));
            break;
        case 'custom':
            if (!$start_date || !$end_date) {
                return [null, 'Start and End dates are required.', null];
            }
            $startDate = $start_date;
            $endDate = $end_date;
            $reportContext = date('F j, Y', strtotime($start_date)) . " to " . date('F j, Y', strtotime($end_date));
            break;
        default:
            return [null, 'Invalid report period type.', null];
    }
    return [$startDate, $endDate, $reportContext];
}


// --- ACTION ROUTER ---

$action = isset($_REQUEST['action']) ? sanitizeInput($_REQUEST['action']) : '';

switch ($action) {
    // Core Inventory & Dashboard
    case 'get_inventory_dashboard':
        getInventoryDashboard();
        break;
    case 'get_inventory_table':
        getInventoryTable();
        break;
    case 'get_motorcycle':
        getMotorcycle();
        break;

    // Motorcycle CRUD (Create, Update, Delete)
    case 'add_motorcycle':
        addMotorcycle();
        break;
    case 'update_motorcycle':
        updateMotorcycle();
        break;
    case 'delete_motorcycle':
        deleteMotorcycle();
        break;
    case 'delete_multiple_motorcycles':
        deleteMultipleMotorcycles();
        break;
    case 'scrap_motorcycle':
        scrapMotorcycle();
        break;

     case 'mark_as_repo':
        markAsRepo();
        break;    

    // Sales & Invoicing
    case 'sell_motorcycle':
        sellMotorcycle();
        break;
    case 'get_invoice_details':
        getInvoiceDetails();
        break;
    case 'search_invoice_number':
        searchInvoiceNumber();
        break;

    // Inventory Transfers
    case 'get_motorcycle_transfers':
        getMotorcycleTransfers();
        break;
    case 'get_transfer_history':
        getTransferHistory();
        break;
    case 'get_all_transfer_histories':
        getAllTransferHistories();
        break;
    case 'transfer_multiple_motorcycles':
        transferMultipleMotorcycles();
        break;
    case 'get_incoming_transfers':
        getIncomingTransfers();
        break;
    case 'accept_transfers':
        acceptTransfers();
        break;
    case 'reject_transfers':
        rejectTransfers();
        break;
    case 'get_transfer_receipt':
        getTransferReceipt();
        break;
    case 'get_transfer_details_by_invoice':
        getTransferDetailsByInvoice();
        break;
    case 'update_transfer_items':
        updateTransferItems();
        break;

case 'get_sold_units':
    getSoldUnits();
    break;
case 'get_repo_units':
    getRepossessedUnits();
    break;
case 'get_scrapped_units':
    getScrappedUnits();
    break;
case 'revert_status':
    revertStatus();
    break;


    // Branch & Searching
    case 'get_branch_inventory':
        getBranchInventory();
        break;
    case 'get_branches_with_inventory':
        getBranchesWithInventory();
        break;
    case 'search_inventory':
        searchInventory();
        break;
    case 'search_inventory_by_engine':
        searchInventoryByEngine();
        break;
    case 'search_transfer_receipt':
        searchTransferReceipt();
        break;

    // Reports
    case 'get_all_models':
    getAllModels();
    break;
    case 'get_monthly_inventory':
        getMonthlyInventory();
        break;
        
    case 'get_monthly_transferred_summary':
        getMonthlyTransferredSummary();
        break;
    case 'get_monthly_received_summary':
        getMonthlyReceivedSummary();
        break;
        case 'get_delivered_stocks_summary':
    getDeliveredStocksSummary();
    break;
    case 'get_available_motorcycles_report':
        getAvailableMotorcyclesReport();
        break;
    case 'get_sold_motorcycles_report':
        getSoldMotorcyclesReport();
        break;
    case 'get_daily_sold_motorcycles_report':
        getDailySoldMotorcyclesReport();
        break;
    case 'get_monthly_scrapped_summary':
        getMonthlyScrappedSummary();
        break;

    // Validation Checks
    case 'check_invoice_number':
        checkInvoiceNumber();
        break;
    case 'check_engine_number':
        checkEngineNumber();
        break;
    case 'check_frame_number':
        checkFrameNumber();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}


// --- CORE INVENTORY & DASHBOARD FUNCTIONS ---

function getInventoryDashboard() {
    global $conn;

    $search = isset( $_GET[ 'search' ] ) ? sanitizeInput( $_GET[ 'search' ] ) : '';

    $userBranch = isset( $_SESSION[ 'user_branch' ] ) ? $_SESSION[ 'user_branch' ] : '';
    $userPosition = isset( $_SESSION[ 'position' ] ) ? $_SESSION[ 'position' ] : '';

   $sql = "SELECT model, brand, color, COUNT(*) as total_quantity 
        FROM motorcycle_inventory 
        WHERE status = 'available' AND status != 'scrapped'";

    if ( !empty( $userBranch ) && $userBranch !== 'HEADOFFICE' &&
    !in_array( strtoupper( $userPosition ), [ 'ADMIN', 'IT STAFF', 'HEAD' ] ) ) {
        $sql .= " AND current_branch = '$userBranch'";
    }

    if ( !empty( $search ) ) {
        $sql .= " AND (model LIKE '%$search%' OR brand LIKE '%$search%' OR color LIKE '%$search%')";
    }

    $sql .= ' GROUP BY model, brand, color ORDER BY total_quantity DESC';

    $result = $conn->query( $sql );

    if ( $result ) {
        $data = [];
        while ( $row = $result->fetch_assoc() ) {
            $data[] = $row;
        }
        echo json_encode( [ 'success' => true, 'data' => $data ] );
    } else {
        echo json_encode( [ 'success' => false, 'message' => 'Error fetching inventory data: ' . $conn->error ] );
    }
}


function getInventoryTable() {
    global $conn;

    $isAdmin = isset( $_SESSION[ 'user_role' ] ) && $_SESSION[ 'user_role' ] === 'admin';
    $userBranch = isset( $_SESSION[ 'user_branch' ] ) ? $_SESSION[ 'user_branch' ] : '';
    $userPosition = isset( $_SESSION[ 'position' ] ) ? $_SESSION[ 'position' ] : '';

    $page = isset( $_GET[ 'page' ] ) ? max( 1, intval( $_GET[ 'page' ] ) ) : 1;
    $perPage = 10;
    $offset = ( $page - 1 ) * $perPage;

    $sort = isset( $_GET[ 'sort' ] ) ? sanitizeInput( $_GET[ 'sort' ] ) : '';
    $sortField = 'mi.date_delivered';
    $sortOrder = 'DESC';

    if ( !empty( $sort ) ) {
        $parts = explode( '_', $sort );
        $validFields = [ 'date_delivered', 'brand', 'model', 'category', 'status', 'invoice_number', 'current_branch' ];

        if ( in_array( $parts[ 0 ], $validFields ) ) {
            $sortField = 'mi.' . $parts[ 0 ];
            $sortOrder = strtoupper( $parts[ 1 ] ) === 'ASC' ? 'ASC' : 'DESC';
        }
    }

    $search = isset( $_GET[ 'query' ] ) ? sanitizeInput( $_GET[ 'query' ] ) : '';
   $where = "WHERE mi.status != 'deleted'";

    if ( !empty( $userBranch ) && $userBranch !== 'HEADOFFICE' &&
    !in_array( strtoupper( $userPosition ), [ 'ADMIN', 'IT STAFF', 'HEAD' ] ) ) {
        $where .= " AND mi.current_branch = '$userBranch'";
    }

    $params = [];
    $types = '';

    if ( !empty( $search ) ) {
        $where .= " AND (mi.model LIKE ? OR mi.brand LIKE ? OR mi.category LIKE ? OR mi.engine_number LIKE ? 
                  OR mi.frame_number LIKE ? OR mi.color LIKE ? OR i.invoice_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill( 0, 7, $searchTerm );
        $types = str_repeat( 's', count( $params ) );
    }

    $countSql = "SELECT COUNT(*) as total 
                 FROM motorcycle_inventory mi 
                 LEFT JOIN invoices i ON mi.invoice_id = i.id 
                 $where";

    $countStmt = $conn->prepare( $countSql );

    if ( !empty( $params ) ) {
        $countStmt->bind_param( $types, ...$params );
    }

    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()[ 'total' ];
    $totalPages = ceil( $totalRecords / $perPage );

    // Updated SELECT to include category
   $sql = "SELECT mi.*, mi.date_received, i.invoice_number 
        FROM motorcycle_inventory mi 
        LEFT JOIN invoices i ON mi.invoice_id = i.id 
        $where 
        ORDER BY $sortField $sortOrder 
        LIMIT ? OFFSET ?";


    $stmt = $conn->prepare( $sql );

    if ( !empty( $params ) ) {
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param( $types, ...$params );
    } else {
        $stmt->bind_param( 'ii', $perPage, $offset );
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ( $row = $result->fetch_assoc() ) {
        $data[] = $row;
    }

    echo json_encode( [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalRecords,
            'itemsPerPage' => $perPage
        ]
    ] );
}



function getMotorcycle() {
    global $conn;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid motorcycle ID']);
        return;
    }

    $includeSaleDetails = isset($_GET['include_sale_details']) && $_GET['include_sale_details'] ? true : false;

    $stmt = $conn->prepare("SELECT mi.*, i.invoice_number 
                           FROM motorcycle_inventory mi 
                           LEFT JOIN invoices i ON mi.invoice_id = i.id 
                           WHERE mi.id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();

        // Include sale details if requested and motorcycle is sold
        if ($includeSaleDetails && isset($data['status']) && $data['status'] === 'sold') {
            $saleStmt = $conn->prepare("SELECT * FROM motorcycle_sales 
                                      WHERE motorcycle_id = ? 
                                      ORDER BY sale_date DESC LIMIT 1");
            if ($saleStmt) {
                $saleStmt->bind_param('i', $id);
                $saleStmt->execute();
                $saleResult = $saleStmt->get_result();

                if ($saleResult && $saleResult->num_rows > 0) {
                    $data['sale_details'] = $saleResult->fetch_assoc();
                } else {
                    $data['sale_details'] = null;
                }
                $saleStmt->close();
            } else {
                $data['sale_details'] = null;
            }
        }

        // Include transfer history if motorcycle is transferred
        if (isset($data['status']) && $data['status'] === 'transferred') {
            $transferStmt = $conn->prepare("SELECT * FROM inventory_transfers 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY transfer_date DESC");
            if ($transferStmt) {
                $transferStmt->bind_param('i', $id);
                $transferStmt->execute();
                $transferResult = $transferStmt->get_result();

                $transfers = [];
                if ($transferResult) {
                    while ($row = $transferResult->fetch_assoc()) {
                        $transfers[] = $row;
                    }
                }
                $data['transfer_history'] = $transfers;
                $transferStmt->close();
            } else {
                $data['transfer_history'] = [];
            }
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        if ($stmt) $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found']);
    }
}

// --- MOTORCYCLE CRUD FUNCTIONS ---

function addMotorcycle() {
    global $conn;

    if ( isset( $_POST[ 'models' ] ) && is_array( $_POST[ 'models' ] ) ) {
        $required = [ 'invoice_number', 'date_delivered', 'branch' ];
        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                echo json_encode( [ 'success' => false, 'message' => "Missing required field: $field" ] );
                return;
            }
        }

        $invoiceNumber = sanitizeInput( $_POST[ 'invoice_number' ] );
        $dateDelivered = sanitizeInput( $_POST[ 'date_delivered' ] );
        $branch = sanitizeInput( $_POST[ 'branch' ] );

        $conn->begin_transaction();
        $successCount = 0;
        $invoiceId = null;
        $isExistingInvoice = false;

        try {
            // Check if invoice already exists
            $checkInvoiceStmt = $conn->prepare( 'SELECT id FROM invoices WHERE invoice_number = ?' );
            if ( !$checkInvoiceStmt ) {
                throw new Exception( 'Error preparing invoice check statement: ' . $conn->error );
            }

            $checkInvoiceStmt->bind_param( 's', $invoiceNumber );
            if ( !$checkInvoiceStmt->execute() ) {
                throw new Exception( 'Error checking existing invoice: ' . $checkInvoiceStmt->error );
            }

            $existingInvoiceResult = $checkInvoiceStmt->get_result();
            
            if ( $existingInvoiceResult->num_rows > 0 ) {
                // Use existing invoice ID
                $existingInvoice = $existingInvoiceResult->fetch_assoc();
                $invoiceId = $existingInvoice['id'];
                $isExistingInvoice = true;
                // Log to console instead of showing error
                error_log("INFO: Using existing invoice ID $invoiceId for invoice number: $invoiceNumber");
            } else {
                // Create new invoice
                $invoiceStmt = $conn->prepare( 'INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)' );
                if ( !$invoiceStmt ) {
                    throw new Exception( 'Error preparing invoice statement: ' . $conn->error );
                }

                $notes = "Motorcycles delivered to $branch branch";
                $invoiceStmt->bind_param( 'sss', $invoiceNumber, $dateDelivered, $notes );

                if ( !$invoiceStmt->execute() ) {
                    throw new Exception( 'Error creating invoice: ' . $invoiceStmt->error );
                }

                $invoiceId = $conn->insert_id;
                error_log("INFO: Created new invoice ID $invoiceId for invoice number: $invoiceNumber");
            }

            foreach ( $_POST[ 'models' ] as $modelIndex => $modelData ) {
                $brand = sanitizeInput( $modelData[ 'brand' ] );
                $modelName = sanitizeInput( $modelData[ 'model' ] );
                $category = sanitizeInput( $modelData[ 'category' ] );
                $color = sanitizeInput( $modelData[ 'color' ] );
                $inventory_cost = !empty( $modelData[ 'inventory_cost' ] ) ? floatval( $modelData[ 'inventory_cost' ] ) : null;

                if ( isset( $modelData[ 'details' ] ) && is_array( $modelData[ 'details' ] ) ) {
                    foreach ( $modelData[ 'details' ] as $detailIndex => $detail ) {
                        $engineNumber = sanitizeInput( $detail[ 'engine_number' ] );
                        $frameNumber = sanitizeInput( $detail[ 'frame_number' ] );

                        if ( empty( $engineNumber ) || empty( $frameNumber ) ) {
                            throw new Exception( "Missing required detail fields for model $modelIndex, detail $detailIndex" );
                        }

                        // Enhanced duplicate checking with specific field identification
                        $engineCheck = $conn->prepare( 'SELECT id, engine_number FROM motorcycle_inventory WHERE engine_number = ?' );
                        if ( !$engineCheck ) {
                            throw new Exception( 'Error preparing engine number duplicate check: ' . $conn->error );
                        }

                        $engineCheck->bind_param( 's', $engineNumber );
                        if ( !$engineCheck->execute() ) {
                            throw new Exception( 'Error executing engine number duplicate check: ' . $engineCheck->error );
                        }

                        $engineResult = $engineCheck->get_result();
                        if ( $engineResult->num_rows > 0 ) {
                            $duplicateRow = $engineResult->fetch_assoc();
                            throw new Exception( "DUPLICATE_ENGINE_NUMBER: Engine number '$engineNumber' already exists in the system (ID: " . $duplicateRow[ 'id' ] . ")" );
                        }

                        $frameCheck = $conn->prepare( 'SELECT id, frame_number FROM motorcycle_inventory WHERE frame_number = ?' );
                        if ( !$frameCheck ) {
                            throw new Exception( 'Error preparing frame number duplicate check: ' . $conn->error );
                        }

                        $frameCheck->bind_param( 's', $frameNumber );
                        if ( !$frameCheck->execute() ) {
                            throw new Exception( 'Error executing frame number duplicate check: ' . $frameCheck->error );
                        }

                        $frameResult = $frameCheck->get_result();
                        if ( $frameResult->num_rows > 0 ) {
                            $duplicateRow = $frameResult->fetch_assoc();
                            throw new Exception( "DUPLICATE_FRAME_NUMBER: Frame number '$frameNumber' already exists in the system (ID: " . $duplicateRow[ 'id' ] . ")" );
                        }

                        // Insert motorcycle with existing or new invoice ID
                        $stmt = $conn->prepare( "INSERT INTO motorcycle_inventory 
                                               (date_delivered, brand, model, category, engine_number, frame_number, invoice_id, color, inventory_cost, current_branch, status) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')" );

                        if ( !$stmt ) {
                            throw new Exception( 'Error preparing motorcycle insert: ' . $conn->error );
                        }

                        $stmt->bind_param( 'ssssssisds', $dateDelivered, $brand, $modelName, $category, $engineNumber, $frameNumber, $invoiceId, $color, $inventory_cost, $branch );

                        if ( $stmt->execute() ) {
                            $successCount++;
                        } else {
                            throw new Exception( 'Error executing motorcycle insert: ' . $stmt->error );
                        }
                    }
                } else {
                    throw new Exception( 'No details found for model ' . $modelIndex );
                }
            }

            $conn->commit();
            
            // Always return success, but with different messages
            if ($isExistingInvoice) {
                echo json_encode( [ 
                    'success' => true, 
                    'message' => "Successfully added $successCount motorcycle(s) to existing invoice #$invoiceNumber",
                    'type' => 'existing_invoice',
                    'console_message' => "Used existing invoice ID: $invoiceId"
                ] );
            } else {
                echo json_encode( [ 
                    'success' => true, 
                    'message' => "Successfully added $successCount motorcycle(s) with new invoice #$invoiceNumber",
                    'type' => 'new_invoice',
                    'console_message' => "Created new invoice ID: $invoiceId"
                ] );
            }

        } catch ( Exception $e ) {
            $conn->rollback();
            
            // Log error to console instead of showing to user for certain errors
            $errorMessage = $e->getMessage();
            error_log("ERROR in addMotorcycle(): " . $errorMessage);
            
            // Only show user-friendly errors, log technical errors
            if (strpos($errorMessage, 'DUPLICATE_ENGINE_NUMBER') !== false || 
                strpos($errorMessage, 'DUPLICATE_FRAME_NUMBER') !== false) {
                echo json_encode( [ 'success' => false, 'message' => $errorMessage ] );
            } else {
                echo json_encode( [ 'success' => false, 'message' => 'Error adding motorcycle. Please check console for details.' ] );
            }
        }
    } else {
        echo json_encode( [ 'success' => false, 'message' => 'Invalid data format. Expected models array.' ] );
    }
}

function updateMotorcycle() {
    global $conn;

    $required = [ 'id', 'date_delivered', 'date_received', 'brand', 'model', 'category', 'engine_number', 'frame_number', 'color', 'current_branch', 'status' ];
    foreach ( $required as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            echo json_encode( [ 'success' => false, 'message' => "Missing required field: $field" ] );
            return;
        }
    }

    $id = intval( $_POST[ 'id' ] );
     $dateReceived = sanitizeInput( $_POST[ 'date_received' ] );
    $dateDelivered = sanitizeInput( $_POST[ 'date_delivered' ] );
    $brand = sanitizeInput( $_POST[ 'brand' ] );
    $model = sanitizeInput( $_POST[ 'model' ] );
    $category = sanitizeInput( $_POST[ 'category' ] );
    $engineNumber = sanitizeInput( $_POST[ 'engine_number' ] );
    $frameNumber = sanitizeInput( $_POST[ 'frame_number' ] );
    $invoiceNumber = isset($_POST['invoice_number']) ? sanitizeInput( $_POST[ 'invoice_number' ] ) : '';
    $color = sanitizeInput( $_POST[ 'color' ] );
    $inventory_cost = !empty( $_POST[ 'inventory_cost' ] ) ? floatval( $_POST[ 'inventory_cost' ] ) : null;
    $currentBranch = sanitizeInput( $_POST[ 'current_branch' ] );
    $status = sanitizeInput( $_POST[ 'status' ] );

    // Sold details (optional)
    $sale_date = isset($_POST['sale_date']) ? sanitizeInput($_POST['sale_date']) : null;
    $customer_name = isset($_POST['customer_name']) ? sanitizeInput($_POST['customer_name']) : null;
    $payment_type = isset($_POST['payment_type']) ? sanitizeInput($_POST['payment_type']) : null;
    $dr_number = isset($_POST['dr_number']) ? sanitizeInput($_POST['dr_number']) : null;
    $cod_amount = isset($_POST['cod_amount']) ? floatval($_POST['cod_amount']) : null;
    $terms = isset($_POST['terms']) ? intval($_POST['terms']) : null;
    $monthly_amortization = isset($_POST['monthly_amortization']) ? floatval($_POST['monthly_amortization']) : null;

    $conn->begin_transaction();

    try {
        // Duplicate checks
        $engineCheckStmt = $conn->prepare( "SELECT id FROM motorcycle_inventory WHERE engine_number = ? AND id != ?" );
        $engineCheckStmt->bind_param( 'si', $engineNumber, $id );
        $engineCheckStmt->execute();
        $engineCheckResult = $engineCheckStmt->get_result();
        if ( $engineCheckResult->num_rows > 0 ) {
            $duplicateRow = $engineCheckResult->fetch_assoc();
            throw new Exception( "DUPLICATE_ENGINE_NUMBER: Engine number '$engineNumber' already exists in another motorcycle (ID: " . $duplicateRow[ 'id' ] . ")" );
        }

        $frameCheckStmt = $conn->prepare( "SELECT id FROM motorcycle_inventory WHERE frame_number = ? AND id != ?" );
        $frameCheckStmt->bind_param( 'si', $frameNumber, $id );
        $frameCheckStmt->execute();
        $frameCheckResult = $frameCheckStmt->get_result();
        if ( $frameCheckResult->num_rows > 0 ) {
            $duplicateRow = $frameCheckResult->fetch_assoc();
            throw new Exception( "DUPLICATE_FRAME_NUMBER: Frame number '$frameNumber' already exists in another motorcycle (ID: " . $duplicateRow[ 'id' ] . ")" );
        }

        // Handle invoice number update
        $invoiceId = null;
        $isExistingInvoice = false;
        $invoiceMessage = "";

        if (!empty($invoiceNumber)) {
            $checkInvoiceStmt = $conn->prepare( 'SELECT id FROM invoices WHERE invoice_number = ?' );
            $checkInvoiceStmt->bind_param( 's', $invoiceNumber );
            $checkInvoiceStmt->execute();
            $existingInvoiceResult = $checkInvoiceStmt->get_result();

            if ( $existingInvoiceResult->num_rows > 0 ) {
                $existingInvoice = $existingInvoiceResult->fetch_assoc();
                $invoiceId = $existingInvoice['id'];
                $isExistingInvoice = true;
                $invoiceMessage = " (linked to existing invoice #$invoiceNumber)";
                error_log("INFO: Using existing invoice ID $invoiceId for invoice number: $invoiceNumber");
            } else {
                $invoiceStmt = $conn->prepare( 'INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)' );
                $notes = "Updated motorcycle record";
                $invoiceStmt->bind_param( 'sss', $invoiceNumber, $dateDelivered, $notes );
                if ( !$invoiceStmt->execute() ) {
                    throw new Exception( 'Error creating new invoice: ' . $invoiceStmt->error );
                }
                $invoiceId = $conn->insert_id;
                $invoiceMessage = " (created new invoice #$invoiceNumber)";
                error_log("INFO: Created new invoice ID $invoiceId for invoice number: $invoiceNumber");
            }
        }

        // Update motorcycle_inventory
        if ($invoiceId) {
            $stmt = $conn->prepare( "UPDATE motorcycle_inventory 
                                   SET date_delivered = ?, date_received = ?,brand = ?, model = ?, category = ?, engine_number = ?, 
                                       frame_number = ?, color = ?, inventory_cost = ?, current_branch = ?, status = ?, invoice_id = ?
                                   WHERE id = ?" );
            $stmt->bind_param( 'ssssssssdssii', $dateDelivered, $dateReceived, $brand, $model, $category, $engineNumber,
                              $frameNumber, $color, $inventory_cost, $currentBranch, $status, $invoiceId, $id );
        } else {
            $stmt = $conn->prepare( "UPDATE motorcycle_inventory 
                                   SET date_delivered = ?, date_received = ?, brand = ?, model = ?, category = ?, engine_number = ?, 
                                       frame_number = ?, color = ?, inventory_cost = ?, current_branch = ?, status = ?
                                   WHERE id = ?" );
            $stmt->bind_param( 'sssssssdsssi', $dateDelivered, $brand, $model, $category, $engineNumber,
                              $frameNumber, $color, $inventory_cost, $currentBranch, $status, $id );
        }

        if ( !$stmt->execute() ) {
            throw new Exception( 'Error updating motorcycle: ' . $stmt->error );
        }

        // Handle sale details if status is 'sold'
        if ($status === 'sold') {
            // Check if sale record exists
            $checkSaleStmt = $conn->prepare("SELECT id FROM motorcycle_sales WHERE motorcycle_id = ? ORDER BY sale_date DESC LIMIT 1");
            $checkSaleStmt->bind_param('i', $id);
            $checkSaleStmt->execute();
            $saleResult = $checkSaleStmt->get_result();

            if ($saleResult->num_rows > 0) {
                // Update existing sale record
                $saleRow = $saleResult->fetch_assoc();
                $updateSaleStmt = $conn->prepare("UPDATE motorcycle_sales SET sale_date = ?, customer_name = ?, payment_type = ?, dr_number = ?, cod_amount = ?, terms = ?, monthly_amortization = ? WHERE id = ?");
                $updateSaleStmt->bind_param('ssssdidi', $sale_date, $customer_name, $payment_type, $dr_number, $cod_amount, $terms, $monthly_amortization, $saleRow['id']);
                if (!$updateSaleStmt->execute()) {
                    throw new Exception('Error updating sale details: ' . $updateSaleStmt->error);
                }
            } else {
                // Insert new sale record
                $insertSaleStmt = $conn->prepare("INSERT INTO motorcycle_sales (motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertSaleStmt->bind_param('issssdid', $id, $sale_date, $customer_name, $payment_type, $dr_number, $cod_amount, $terms, $monthly_amortization);
                if (!$insertSaleStmt->execute()) {
                    throw new Exception('Error inserting sale details: ' . $insertSaleStmt->error);
                }
            }
        } else {
            // If status is not sold, delete any existing sale record
            $deleteSaleStmt = $conn->prepare("DELETE FROM motorcycle_sales WHERE motorcycle_id = ?");
            $deleteSaleStmt->bind_param('i', $id);
            $deleteSaleStmt->execute();
        }

        $conn->commit();

        if ($isExistingInvoice) {
            echo json_encode( [ 
                'success' => true, 
                'message' => "Motorcycle updated successfully$invoiceMessage",
                'type' => 'existing_invoice',
                'console_message' => "Used existing invoice ID: $invoiceId"
            ] );
        } else if ($invoiceId) {
            echo json_encode( [ 
                'success' => true, 
                'message' => "Motorcycle updated successfully$invoiceMessage",
                'type' => 'new_invoice',
                'console_message' => "Created new invoice ID: $invoiceId"
            ] );
        } else {
            echo json_encode( [ 
                'success' => true, 
                'message' => 'Motorcycle updated successfully'
            ] );
        }

    } catch ( Exception $e ) {
        $conn->rollback();

        $errorMessage = $e->getMessage();
        error_log("ERROR in updateMotorcycle(): " . $errorMessage);

        if (strpos($errorMessage, 'DUPLICATE_ENGINE_NUMBER') !== false || 
            strpos($errorMessage, 'DUPLICATE_FRAME_NUMBER') !== false) {
            echo json_encode( [ 'success' => false, 'message' => $errorMessage ] );
        } else {
            echo json_encode( [ 'success' => false, 'message' => 'Error updating motorcycle. Please check console for details.' ] );
        }
    }
}

function deleteMotorcycle() {
    global $conn;

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // First get the invoice_id before deleting
    $getInvoiceStmt = $conn->prepare("SELECT invoice_id FROM motorcycle_inventory WHERE id = ?");
    $getInvoiceStmt->bind_param('i', $id);
    $getInvoiceStmt->execute();
    $invoiceResult = $getInvoiceStmt->get_result();
    
    if ($invoiceResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found']);
        return;
    }
    
    $invoiceData = $invoiceResult->fetch_assoc();
    $invoiceId = $invoiceData['invoice_id'];

    $conn->begin_transaction();

    try {
        // Delete transfers first
        $deleteTransfers = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id = ?");
        $deleteTransfers->bind_param('i', $id);
        $deleteTransfers->execute();
        
        // Delete the motorcycle
        $stmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        // Check if other motorcycles use the same invoice
        $checkInvoiceStmt = $conn->prepare("SELECT COUNT(*) as remaining FROM motorcycle_inventory WHERE invoice_id = ?");
        $checkInvoiceStmt->bind_param('i', $invoiceId);
        $checkInvoiceStmt->execute();
        $checkResult = $checkInvoiceStmt->get_result();
        $remaining = $checkResult->fetch_assoc()['remaining'];
        
        // Delete invoice if no motorcycles are left using it
        if ($remaining == 0) {
            $deleteInvoiceStmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
            $deleteInvoiceStmt->bind_param('i', $invoiceId);
            $deleteInvoiceStmt->execute();
        }

        $conn->commit();

        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Motorcycle permanently deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Motorcycle not found or already deleted']);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting motorcycle: ' . $e->getMessage()]);
    }
}

function deleteMultipleMotorcycles() {
    global $conn;

    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];

    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['success' => false, 'message' => 'No motorcycles selected for deletion']);
        return;
    }

    $sanitizedIds = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    $types = str_repeat('i', count($sanitizedIds));

    // First get all invoice IDs that will be affected
    $getInvoicesStmt = $conn->prepare("SELECT DISTINCT invoice_id FROM motorcycle_inventory WHERE id IN ($placeholders)");
    $getInvoicesStmt->bind_param($types, ...$sanitizedIds);
    $getInvoicesStmt->execute();
    $invoiceResult = $getInvoicesStmt->get_result();
    
    $affectedInvoices = [];
    while ($row = $invoiceResult->fetch_assoc()) {
        $affectedInvoices[] = $row['invoice_id'];
    }

    $conn->begin_transaction();

    try {
        // Delete transfers first
        $deleteTransfers = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id IN ($placeholders)");
        $deleteTransfers->bind_param($types, ...$sanitizedIds);
        $deleteTransfers->execute();
        
        // Delete the motorcycles
        $stmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$sanitizedIds);
        $stmt->execute();

        $affectedRows = $stmt->affected_rows;
        
        // Check and delete invoices that are no longer used
        foreach ($affectedInvoices as $invoiceId) {
            $checkInvoiceStmt = $conn->prepare("SELECT COUNT(*) as remaining FROM motorcycle_inventory WHERE invoice_id = ?");
            $checkInvoiceStmt->bind_param('i', $invoiceId);
            $checkInvoiceStmt->execute();
            $checkResult = $checkInvoiceStmt->get_result();
            $remaining = $checkResult->fetch_assoc()['remaining'];
            
            // Delete invoice if no motorcycles are left using it
            if ($remaining == 0) {
                $deleteInvoiceStmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
                $deleteInvoiceStmt->bind_param('i', $invoiceId);
                $deleteInvoiceStmt->execute();
            }
        }

        $conn->commit();

        if ($affectedRows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => "Successfully permanently deleted $affectedRows motorcycle(s)",
                'deleted_count' => $affectedRows
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No motorcycles were deleted (possibly already deleted)'
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Error deleting motorcycles: ' . $e->getMessage()
        ]);
    }
}
function scrapMotorcycle() {
    global $conn;

    $required = ['motorcycle_id', 'scrap_date'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $motorcycleId = intval($_POST['motorcycle_id']);

        $checkStatusStmt = $conn->prepare("SELECT status FROM motorcycle_inventory WHERE id = ?");
    if (!$checkStatusStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error preparing status check.']);
        return;
    }
    $checkStatusStmt->bind_param('i', $motorcycleId);
    $checkStatusStmt->execute();
    $statusResult = $checkStatusStmt->get_result();
    
    if ($statusResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found.']);
        return;
    }
    
    $currentStatus = $statusResult->fetch_assoc()['status'];
    
    if ($currentStatus === 'scrapped') {
        echo json_encode(['success' => false, 'message' => 'This motorcycle has already been scrapped.']);
        return;
    }
    $scrapDate = sanitizeInput($_POST['scrap_date']);
    $scrapReason = isset($_POST['scrap_reason']) ? sanitizeInput($_POST['scrap_reason']) : '';

    $conn->begin_transaction();

    try {
        // Insert scrap record
        $scrapStmt = $conn->prepare("INSERT INTO motorcycle_scraps 
                                   (motorcycle_id, scrap_date, scrap_reason) 
                                   VALUES (?, ?, ?)");
        $scrapStmt->bind_param('iss', $motorcycleId, $scrapDate, $scrapReason);
        if (!$scrapStmt->execute()) {
            throw new Exception("Failed to insert scrap record: " . $scrapStmt->error);
        }

        // Update motorcycle status to scrapped
        $updateStmt = $conn->prepare("UPDATE motorcycle_inventory 
                                    SET status = 'scrapped' 
                                    WHERE id = ?");
        $updateStmt->bind_param('i', $motorcycleId);
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update motorcycle status: " . $updateStmt->error);
        }

        $conn->commit();

        // Fetch updated motorcycle data
        $stmt = $conn->prepare("SELECT * FROM motorcycle_inventory WHERE id = ?");
        $stmt->bind_param('i', $motorcycleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $motorcycle = $result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'message' => 'Motorcycle marked as scrapped successfully',
            'data' => $motorcycle
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error scrapping motorcycle: ' . $e->getMessage()]);
    }
}


// --- SALES & INVOICING FUNCTIONS ---

function sellMotorcycle() {
    global $conn;

    // 1. Check for all required fields
    $required = ['motorcycle_id', 'sale_date', 'customer_name', 'payment_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    // 2. Sanitize all inputs
    $motorcycleId = intval($_POST['motorcycle_id']);
    $saleDate = sanitizeInput($_POST['sale_date']);

    $today = date('Y-m-d');
    if ($saleDate > $today) {
        echo json_encode(['success' => false, 'message' => 'Error: The sale date cannot be in the future.']);
        return;
    }
    $customerName = sanitizeInput($_POST['customer_name']);
    $paymentType = sanitizeInput($_POST['payment_type']);
    $drNumber = isset($_POST['dr_number']) ? sanitizeInput($_POST['dr_number']) : null;
    $codAmount = isset($_POST['cod_amount']) ? floatval($_POST['cod_amount']) : null;
    $terms = isset($_POST['terms']) ? intval($_POST['terms']) : null;
    $monthlyAmortization = isset($_POST['monthly_amortization']) ? floatval($_POST['monthly_amortization']) : null;

    // 3. Validate the sale date (cannot be in the future)
    $today = date('Y-m-d');
    if ($saleDate > $today) {
        echo json_encode(['success' => false, 'message' => 'Error: The sale date cannot be in the future.']);
        return;
    }

    // 4. Validate the motorcycle exists and is available
    $checkStatusStmt = $conn->prepare("SELECT status FROM motorcycle_inventory WHERE id = ?");
    if (!$checkStatusStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error preparing status check.']);
        return;
    }
    $checkStatusStmt->bind_param('i', $motorcycleId);
    $checkStatusStmt->execute();
    $statusResult = $checkStatusStmt->get_result();

    if ($statusResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found.']);
        return;
    }

    $currentStatus = $statusResult->fetch_assoc()['status'];

    if ($currentStatus !== 'available') {
        echo json_encode(['success' => false, 'message' => "This unit cannot be sold because its status is currently '{$currentStatus}'."]);
        return;
    }

    // 5. Validate payment-specific fields
    if ($paymentType === 'COD') {
        if (empty($drNumber) || $codAmount === null) {
            echo json_encode(['success' => false, 'message' => 'DR Number and COD Amount are required for COD payment']);
            return;
        }
    } else if ($paymentType === 'Installment') {
        if ($terms === null || $monthlyAmortization === null) {
            echo json_encode(['success' => false, 'message' => 'Terms and Monthly Amortization are required for Installment payment']);
            return;
        }
    }

    // 6. Process the sale
    $conn->begin_transaction();
    try {
        $saleStmt = $conn->prepare("INSERT INTO motorcycle_sales (motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $saleStmt->bind_param('issssdid', $motorcycleId, $saleDate, $customerName, $paymentType, $drNumber, $codAmount, $terms, $monthlyAmortization);
        $saleStmt->execute();

        $updateStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'sold' WHERE id = ?");
        $updateStmt->bind_param('i', $motorcycleId);
        $updateStmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Motorcycle marked as sold successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error selling motorcycle: ' . $e->getMessage()]);
    }
}
function getInvoiceDetails() {
    global $conn;

    $invoiceId = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
    
    if ($invoiceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
        return;
    }
    
    $headerSql = "SELECT * FROM invoices WHERE id = ?";
    $headerStmt = $conn->prepare($headerSql);
    $headerStmt->bind_param('i', $invoiceId);
    $headerStmt->execute();
    $headerResult = $headerStmt->get_result();
    
    if ($headerResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        return;
    }
    
    $invoice = $headerResult->fetch_assoc();
    

    $motorcyclesSql = "SELECT * FROM motorcycle_inventory WHERE invoice_id = ?";
    $motorcyclesStmt = $conn->prepare($motorcyclesSql);
    $motorcyclesStmt->bind_param('i', $invoiceId);
    $motorcyclesStmt->execute();
    $motorcyclesResult = $motorcyclesStmt->get_result();
    
    $motorcycles = [];
    while ($row = $motorcyclesResult->fetch_assoc()) {
        $motorcycles[] = $row;
    }
    
    $invoice['motorcycles'] = $motorcycles;
    
    echo json_encode(['success' => true, 'data' => $invoice]);
}

function searchInvoiceNumber() {
    global $conn;

    $invoiceNumber = isset($_GET['invoice_number']) ? sanitizeInput($_GET['invoice_number']) : '';
    
    if (empty($invoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invoice number is required']);
        return;
    }
    
    $sql = "SELECT i.id, i.invoice_number, i.date_delivered, 
                   GROUP_CONCAT(DISTINCT CONCAT(mi.brand, ' ', mi.model) SEPARATOR ', ') as models,
                   COUNT(mi.id) as motorcycle_count,
                   mi.current_branch as branch
            FROM invoices i
            LEFT JOIN motorcycle_inventory mi ON i.id = mi.invoice_id
            WHERE i.invoice_number LIKE ?
            GROUP BY i.id
            ORDER BY i.date_delivered DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$invoiceNumber%";
    $stmt->bind_param('s', $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['models'] = !empty($row['models']) ? explode(', ', $row['models']) : [];
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}

// --- INVENTORY TRANSFER FUNCTIONS ---

function getMotorcycleTransfers() {
    global $conn;

    $id = isset( $_GET[ 'id' ] ) ? intval( $_GET[ 'id' ] ) : 0;

    $stmt = $conn->prepare( "SELECT it.*, u.username as transferred_by_name 
                          FROM inventory_transfers it
                          LEFT JOIN users u ON it.transferred_by = u.id
                          WHERE motorcycle_id = ? 
                          ORDER BY transfer_date DESC" );
    $stmt->bind_param( 'i', $id );
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ( $row = $result->fetch_assoc() ) {
        $data[] = $row;
    }

    echo json_encode( [ 'success' => true, 'data' => $data ] );
}


function getTransferHistory() {
    global $conn;

    $motorcycleId = isset( $_GET[ 'motorcycle_id' ] ) ? intval( $_GET[ 'motorcycle_id' ] ) : 0;

    $stmt = $conn->prepare( "SELECT it.*, u.username as transferred_by_name 
                           FROM inventory_transfers it
                           LEFT JOIN users u ON it.transferred_by = u.id
                           WHERE motorcycle_id = ? 
                           ORDER BY transfer_date DESC" );
    $stmt->bind_param( 'i', $motorcycleId );
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ( $row = $result->fetch_assoc() ) {
        $data[] = $row;
    }

    echo json_encode( [ 'success' => true, 'data' => $data ] );
}

function getAllTransferHistories() {
    global $conn;


    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;


    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
    $model = isset($_GET['model']) ? sanitizeInput($_GET['model']) : '';
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : ''; 

    $whereClauses = [];
    $params = [];
    $types = '';

    $sql = "SELECT it.id as transfer_id, it.transfer_date, it.from_branch, it.to_branch, it.notes, 
                   it.transfer_status AS status, it.transfer_invoice_number,
                   mi.id as motorcycle_id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.current_branch,
                   i.invoice_number,
                   u.username as transferred_by_name
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            LEFT JOIN users u ON it.transferred_by = u.id";

    if (!empty($search)) {
        $whereClauses[] = "(mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? OR mi.frame_number LIKE ? OR i.invoice_number LIKE ? OR it.transfer_invoice_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, array_fill(0, 6, $searchTerm));
        $types .= str_repeat('s', 6);
    }

    if (!empty($branch)) {
        $whereClauses[] = "(it.from_branch = ? OR it.to_branch = ?)";
        $params[] = $branch;
        $params[] = $branch;
        $types .= 'ss';
    }

    if (!empty($model)) {
        $whereClauses[] = "mi.model = ?";
        $params[] = $model;
        $types .= 's';
    }

    if (!empty($status)) {
        $whereClauses[] = "it.transfer_status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if (count($whereClauses) > 0) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $sql .= " ORDER BY it.transfer_date DESC LIMIT ? OFFSET ?";

    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        return;
    }

    if (!$stmt->bind_param($types, ...$params)) {
        echo json_encode(['success' => false, 'message' => 'Parameter binding error: ' . $stmt->error]);
        return;
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $countSql = "SELECT COUNT(*) as total FROM inventory_transfers it
             JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
             LEFT JOIN invoices i ON mi.invoice_id = i.id
             LEFT JOIN users u ON it.transferred_by = u.id";


    if (count($whereClauses) > 0) {
        $countSql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        return;
    }

    if (count($params) > 2) {
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);
        if (!$countStmt->bind_param($countTypes, ...$countParams)) {
            echo json_encode(['success' => false, 'message' => 'Count query parameter binding error: ' . $countStmt->error]);
            return;
        }
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages
        ]
    ]);
}

function transferMultipleMotorcycles() {
    global $conn;

    // 1. Validate required fields
    $required = [ 'motorcycle_ids', 'from_branch', 'to_branch', 'transfer_date', 'inventory_costs', 'transfer_invoice_number' ];
    foreach ( $required as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            echo json_encode( [ 'success' => false, 'message' => "Missing required field: $field" ] );
            return;
        }
    }

    // 2. Sanitize inputs
    $motorcycleIds = explode( ',', sanitizeInput( $_POST[ 'motorcycle_ids' ] ) );
    $inventoryCosts = array_map('floatval', explode(',', sanitizeInput( $_POST[ 'inventory_costs' ] )));
    $fromBranch = sanitizeInput( $_POST[ 'from_branch' ] );
    $toBranch = sanitizeInput( $_POST[ 'to_branch' ] );
    $transferDate = sanitizeInput( $_POST[ 'transfer_date' ] );
    $transferInvoiceNumber = sanitizeInput( $_POST[ 'transfer_invoice_number' ] );
    $notes = isset( $_POST[ 'notes' ] ) ? sanitizeInput( $_POST[ 'notes' ] ) : '';
    $transferredBy = isset( $_SESSION[ 'user_id' ] ) ? $_SESSION[ 'user_id' ] : 0;

    if ( $fromBranch === $toBranch ) {
        echo json_encode( [ 'success' => false, 'message' => 'Cannot transfer to the same branch' ] );
        return;
    }

    // THIS IS THE BLOCK THAT WAS REMOVED.
    // The check for an existing transfer invoice number has been deleted to allow grouping.

    $placeholders = implode( ',', array_fill( 0, count( $motorcycleIds ), '?' ) );
    $types = str_repeat( 'i', count( $motorcycleIds ) );

    // 3. Verify that the motorcycles are available for transfer from the correct branch
    $checkStmt = $conn->prepare( "SELECT COUNT(*) as count FROM motorcycle_inventory
                                  WHERE id IN ($placeholders) AND current_branch = ? AND status = 'available'" );
    $checkStmt->bind_param( $types.'s', ...array_merge( $motorcycleIds, [ $fromBranch ] ) );
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();

    if ( $result[ 'count' ] != count( $motorcycleIds ) ) {
        echo json_encode( [ 'success' => false, 'message' => 'Error: One or more selected motorcycles are not available or do not belong to the specified branch.' ] );
        return;
    }

    $conn->begin_transaction();

    try {
        // 4. Get motorcycle details for the receipt before updating their status
        $motorcycleDetails = [];
        $getDetailsStmt = $conn->prepare("SELECT id, brand, model, color, engine_number, frame_number, inventory_cost
                                           FROM motorcycle_inventory WHERE id IN ($placeholders)");
        $getDetailsStmt->bind_param($types, ...$motorcycleIds);
        $getDetailsStmt->execute();
        $detailsResult = $getDetailsStmt->get_result();
        
        while ($row = $detailsResult->fetch_assoc()) {
            $motorcycleDetails[] = $row;
        }

        // 5. Update each motorcycle's status to 'transferred'
        $updateStmt = $conn->prepare( "UPDATE motorcycle_inventory
                                       SET status = 'transferred', inventory_cost = ?
                                       WHERE id = ?" );

        foreach ( $motorcycleIds as $index => $id ) {
            $inventoryCost = $inventoryCosts[$index] ?? null;
            $updateStmt->bind_param( 'di', $inventoryCost, $id );
            $updateStmt->execute();
        }

        // 6. Insert a record for each transfer, linking them with the same invoice number
        $transferIds = [];
        $transferStmt = $conn->prepare( "INSERT INTO inventory_transfers
                                      (motorcycle_id, from_branch, to_branch, transfer_date, transferred_by, notes, transfer_status, transfer_invoice_number)
                                      VALUES (?, ?, ?, ?, ?, ?, 'in-transit', ?)" );

        foreach ( $motorcycleIds as $id ) {
            $transferStmt->bind_param( 'isssiss', $id, $fromBranch, $toBranch, $transferDate, $transferredBy, $notes, $transferInvoiceNumber );
            $transferStmt->execute();
            $transferIds[] = $conn->insert_id;
        }

        $conn->commit();
        
        $totalCost = array_sum($inventoryCosts);
        
        // 7. Send a success response with receipt data
        echo json_encode( [
            'success' => true,
            'message' => 'Successfully initiated transfer for ' . count( $motorcycleIds ) . ' motorcycle(s).',
            'receipt_data' => [
                'from_branch' => $fromBranch,
                'to_branch' => $toBranch,
                'transfer_date' => $transferDate,
                'notes' => $notes,
                'motorcycles' => $motorcycleDetails,
                'total_count' => count( $motorcycleIds ),
                'total_cost' => $totalCost,
                'transfer_invoice_number' => $transferInvoiceNumber
            ]
        ] );

    } catch ( Exception $e ) {
        $conn->rollback();
        echo json_encode( [
            'success' => false,
            'message' => 'Error transferring motorcycles: ' . $e->getMessage()
        ] );
    }
}

function getIncomingTransfers() {
    global $conn;

    $currentBranch = isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] :
        (isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '');

    if (empty($currentBranch)) {
        echo json_encode(['success' => false, 'message' => 'Branch parameter is required']);
        return;
    }

    $sql = "SELECT 
            t.id as transfer_id,
            m.id as motorcycle_id,
            m.brand, 
            m.model, 
            m.engine_number, 
            m.frame_number, 
            m.color,
            t.transfer_date,
            t.from_branch,
            t.to_branch,
            t.notes,
            t.transfer_status as transfer_status,
            t.transfer_invoice_number,
            u.username as transferred_by
        FROM inventory_transfers t
        JOIN motorcycle_inventory m ON t.motorcycle_id = m.id
        LEFT JOIN users u ON t.transferred_by = u.id
        WHERE t.to_branch = ?
        AND t.transfer_status = 'in-transit'
        ORDER BY t.transfer_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $currentBranch);
    $stmt->execute();
    $result = $stmt->get_result();

    $transfers = [];
    while ($row = $result->fetch_assoc()) {
        $transfers[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $transfers]);
}

function acceptTransfers() {
    global $conn;

    $transferIds = isset($_POST['transfer_ids']) ? explode(',', sanitizeInput($_POST['transfer_ids'])) : [];
    $currentBranch = isset($_POST['current_branch']) ? sanitizeInput($_POST['current_branch']) : '';

    if (empty($transferIds)) {
        echo json_encode(['success' => false, 'message' => 'No transfer IDs provided']);
        return;
    }

    if (empty($currentBranch)) {
        echo json_encode(['success' => false, 'message' => 'Current branch parameter is required']);
        return;
    }

    $transferIds = array_map('intval', $transferIds);
    $placeholders = implode(',', array_fill(0, count($transferIds), '?'));
    $currentDate = date('Y-m-d H:i:s'); 

    $conn->begin_transaction();

    try {

        $getTransfersStmt = $conn->prepare("SELECT id, motorcycle_id, to_branch, from_branch, transfer_invoice_number, transfer_date FROM inventory_transfers 
                                           WHERE id IN ($placeholders) AND transfer_status = 'in-transit'");
        $getTransfersStmt->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        $getTransfersStmt->execute();
        $transfersResult = $getTransfersStmt->get_result();

        $motorcycleUpdates = [];
        while ($row = $transfersResult->fetch_assoc()) {
            $motorcycleUpdates[] = $row;
        }

        if (empty($motorcycleUpdates)) {
            throw new Exception('No in-transit transfers found with the provided IDs');
        }

        // Verify transfers are for current branch
        foreach ($motorcycleUpdates as $update) {
            if ($update['to_branch'] !== $currentBranch) {
                throw new Exception('Transfer destination does not match current branch');
            }
        }

        // Update transfer status to completed with date_received = acceptance datetime
        $updateTransfers = $conn->prepare("UPDATE inventory_transfers 
                                         SET transfer_status = 'completed', date_received = ?
                                         WHERE id IN ($placeholders)");

        $params = array_merge([$currentDate], $transferIds);
        $types = 's' . str_repeat('i', count($transferIds));

        $updateTransfers->bind_param($types, ...$params);

        if (!$updateTransfers->execute()) {
            throw new Exception('Failed to update transfer status: ' . $updateTransfers->error);
        }

        // Check if date_received column exists in motorcycle_inventory table
        $checkColumnQuery = "SHOW COLUMNS FROM motorcycle_inventory LIKE 'date_received'";
        $columnResult = $conn->query($checkColumnQuery);
        $hasDateReceivedColumn = $columnResult->num_rows > 0;

        // Prepare statements for invoice lookup/creation
        $selectInvoiceStmt = $conn->prepare("SELECT id FROM invoices WHERE invoice_number = ?");
        $insertInvoiceStmt = $conn->prepare("INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)");

        // Update motorcycles - Change current_branch, status, date_received, invoice_id
        // **DO NOT update date_delivered here**
        foreach ($motorcycleUpdates as $update) {
            $transferInvoiceNumber = $update['transfer_invoice_number'];
            $transferDate = $update['transfer_date'];  // original transfer date

            // Find or create invoice for transfer_invoice_number
            $invoiceId = null;
            $selectInvoiceStmt->bind_param('s', $transferInvoiceNumber);
            $selectInvoiceStmt->execute();
            $invoiceResult = $selectInvoiceStmt->get_result();

            if ($invoiceResult->num_rows > 0) {
                $invoiceRow = $invoiceResult->fetch_assoc();
                $invoiceId = $invoiceRow['id'];
            } else {
                $notes = "Invoice created for transfer invoice number $transferInvoiceNumber";
                $insertInvoiceStmt->bind_param('sss', $transferInvoiceNumber, $transferDate, $notes);
                if (!$insertInvoiceStmt->execute()) {
                    throw new Exception('Failed to create invoice for transfer invoice number: ' . $insertInvoiceStmt->error);
                }
                $invoiceId = $conn->insert_id;
            }

            if ($hasDateReceivedColumn) {
                // Update with date_received, invoice_id, but NOT date_delivered
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available', date_received = ?, invoice_id = ?
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('ssii', $update['to_branch'], $currentDate, $invoiceId, $update['motorcycle_id']);
            } else {
                // Update without date_received, but NOT date_delivered
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available', invoice_id = ?
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('sii', $update['to_branch'], $invoiceId, $update['motorcycle_id']);
            }

            if (!$updateMotorcycle->execute()) {
                throw new Exception('Failed to update motorcycle status: ' . $updateMotorcycle->error);
            }
        }

        // Get accepted motorcycle details for response
        $acceptedDetails = [];
        foreach ($motorcycleUpdates as $update) {
            $detailStmt = $conn->prepare("SELECT mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.color, i.invoice_number
                                         FROM motorcycle_inventory mi
                                         LEFT JOIN invoices i ON mi.invoice_id = i.id
                                         WHERE mi.id = ?");
            $detailStmt->bind_param('i', $update['motorcycle_id']);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();

            if ($detailRow = $detailResult->fetch_assoc()) {
                $acceptedDetails[] = $detailRow;
            }
        }

        $conn->commit();

        $response = [
            'success' => true,
            'message' => 'Successfully accepted ' . count($transferIds) . ' transfer(s). Motorcycles are now available at your branch.',
            'accepted_count' => count($transferIds),
            'accepted_details' => $acceptedDetails
        ];

        if ($hasDateReceivedColumn) {
            $response['date_received'] = $currentDate;
        }

        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error accepting transfers: ' . $e->getMessage(),
            'debug_info' => [
                'transfer_ids' => $transferIds,
                'current_branch' => $currentBranch,
                'error_details' => $conn->error
            ]
        ]);
    }
}

function rejectTransfers() {
    global $conn;

    $transferIds = isset($_POST['transfer_ids']) ? explode(',', sanitizeInput($_POST['transfer_ids'])) : [];
    $currentBranch = isset($_POST['current_branch']) ? sanitizeInput($_POST['current_branch']) : '';

    if (empty($transferIds)) {
        echo json_encode(['success' => false, 'message' => 'No transfer IDs provided']);
        return;
    }

    if (empty($currentBranch)) {
        echo json_encode(['success' => false, 'message' => 'Current branch parameter is required']);
        return;
    }

    // Sanitize transfer IDs to integers
    $transferIds = array_map('intval', $transferIds);
    $placeholders = implode(',', array_fill(0, count($transferIds), '?'));

    $conn->begin_transaction();

    try {
        // Get transfer details before updating
        $getTransfersStmt = $conn->prepare("SELECT motorcycle_id, from_branch, to_branch FROM inventory_transfers 
                                           WHERE id IN ($placeholders) AND transfer_status = 'in-transit'");
        $getTransfersStmt->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        $getTransfersStmt->execute();
        $transfersResult = $getTransfersStmt->get_result();

        $motorcycleUpdates = [];
        while ($row = $transfersResult->fetch_assoc()) {
            $motorcycleUpdates[] = $row;
        }

        if (empty($motorcycleUpdates)) {
            throw new Exception('No in-transit transfers found with the provided IDs');
        }

        // Update transfer status to rejected (without date_rejected)
        $updateTransfers = $conn->prepare("UPDATE inventory_transfers 
                                         SET transfer_status = 'rejected'
                                         WHERE id IN ($placeholders)");
        $updateTransfers->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        
        if (!$updateTransfers->execute()) {
            throw new Exception('Failed to update transfer status: ' . $updateTransfers->error);
        }

        // Update motorcycles back to available status at original branch
        foreach ($motorcycleUpdates as $update) {
            $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                              SET status = 'available', current_branch = ?
                                              WHERE id = ?");
            $updateMotorcycle->bind_param('si', $update['from_branch'], $update['motorcycle_id']);
            
            if (!$updateMotorcycle->execute()) {
                throw new Exception('Failed to update motorcycle status: ' . $updateMotorcycle->error);
            }
        }

        // Get rejected motorcycle details for response
        $rejectedDetails = [];
        foreach ($motorcycleUpdates as $update) {
            $detailStmt = $conn->prepare("SELECT mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.color, i.invoice_number
                                         FROM motorcycle_inventory mi
                                         LEFT JOIN invoices i ON mi.invoice_id = i.id
                                         WHERE mi.id = ?");
            $detailStmt->bind_param('i', $update['motorcycle_id']);
            $detailStmt->execute();
            $detailResult = $detailStmt->get_result();

            if ($detailRow = $detailResult->fetch_assoc()) {
                $rejectedDetails[] = $detailRow;
            }
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully rejected ' . count($transferIds) . ' transfer(s). Motorcycles have been returned to their original branches.',
            'rejected_count' => count($transferIds),
            'rejected_details' => $rejectedDetails
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error rejecting transfers: ' . $e->getMessage(),
            'debug_info' => [
                'transfer_ids' => $transferIds,
                'current_branch' => $currentBranch,
                'error_details' => $conn->error
            ]
        ]);
    }
}


function getTransferReceipt() {
    global $conn;
    
    $transferId = isset($_GET['transfer_id']) ? intval($_GET['transfer_id']) : 0;
    
    if ($transferId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transfer ID']);
        return;
    }

    $headerSql = "SELECT it.*, u.username as transferred_by_name
                 FROM inventory_transfers it
                 LEFT JOIN users u ON it.transferred_by = u.id
                 WHERE it.id = ?";
    
    $headerStmt = $conn->prepare($headerSql);
    $headerStmt->bind_param('i', $transferId);
    $headerStmt->execute();
    $headerResult = $headerStmt->get_result();
    
    if ($headerResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Transfer not found']);
        return;
    }
    
    $headerData = $headerResult->fetch_assoc();
    

    $detailsSql = "SELECT mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.inventory_cost
                  FROM motorcycle_inventory mi
                  INNER JOIN inventory_transfers it ON mi.id = it.motorcycle_id
                  WHERE it.id = ? OR it.transfer_invoice_number = ?
                  ORDER BY mi.brand, mi.model";
    
    $detailsStmt = $conn->prepare($detailsSql);
    $detailsStmt->bind_param('is', $transferId, $headerData['transfer_invoice_number']);
    $detailsStmt->execute();
    $detailsResult = $detailsStmt->get_result();
    
    $motorcycles = [];
    $totalCost = 0;
    
    while ($row = $detailsResult->fetch_assoc()) {
        $motorcycles[] = $row;
        $totalCost += (float)$row['inventory_cost'];
    }
    
    $response = [
        'success' => true,
        'data' => [
            'header' => $headerData,
            'motorcycles' => $motorcycles,
            'total_count' => count($motorcycles),
            'total_cost' => $totalCost
        ]
    ];
    
    echo json_encode($response);
}

function getTransferDetailsByInvoice() {
    global $conn;
    
    $invoiceNumber = isset($_GET['transfer_invoice_number']) ? sanitizeInput($_GET['transfer_invoice_number']) : '';
    if (empty($invoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Transfer Invoice Number is required.']);
        return;
    }

    // Get header info from the first transfer record found
    $headerSql = "SELECT * FROM inventory_transfers WHERE transfer_invoice_number = ? LIMIT 1";
    $headerStmt = $conn->prepare($headerSql);
    $headerStmt->bind_param('s', $invoiceNumber);
    $headerStmt->execute();
    $headerResult = $headerStmt->get_result();

    if ($headerResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Transfer not found.']);
        return;
    }
    $headerData = $headerResult->fetch_assoc();

    // Get all motorcycles associated with this transfer invoice
    $motorcyclesSql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.inventory_cost
                       FROM inventory_transfers it
                       JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
                       WHERE it.transfer_invoice_number = ?";
    $motorcyclesStmt = $conn->prepare($motorcyclesSql);
    $motorcyclesStmt->bind_param('s', $invoiceNumber);
    $motorcyclesStmt->execute();
    $motorcyclesResult = $motorcyclesStmt->get_result();

    $motorcycles = [];
    while ($row = $motorcyclesResult->fetch_assoc()) {
        $motorcycles[] = $row;
    }

    echo json_encode([
        'success' => true,
        'header' => $headerData,
        'motorcycles' => $motorcycles
    ]);
}

function updateTransferItems() {
    global $conn;

    $invoiceNumber = isset($_POST['transfer_invoice_number']) ? sanitizeInput($_POST['transfer_invoice_number']) : '';
    $fromBranch = isset($_POST['from_branch']) ? sanitizeInput($_POST['from_branch']) : '';
    $toBranch = isset($_POST['to_branch']) ? sanitizeInput($_POST['to_branch']) : '';
    $transferDate = isset($_POST['transfer_date']) ? sanitizeInput($_POST['transfer_date']) : '';
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
    $transferredBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    $itemsToAdd = isset($_POST['motorcycles_to_add']) ? $_POST['motorcycles_to_add'] : [];
    $itemsToRemove = isset($_POST['motorcycles_to_remove']) ? $_POST['motorcycles_to_remove'] : [];

    if (empty($invoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invalid transfer invoice number.']);
        return;
    }

    $conn->begin_transaction();
    try {
        // Handle items to remove
        if (!empty($itemsToRemove)) {
            $removeIds = array_map('intval', $itemsToRemove);
            $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
            $types = str_repeat('i', count($removeIds));

            // Set motorcycle status back to 'available'
            $updateMotorcycleStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available' WHERE id IN ($placeholders)");
            $updateMotorcycleStmt->bind_param($types, ...$removeIds);
            $updateMotorcycleStmt->execute();

            // Delete from transfers table
            $deleteTransferStmt = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id IN ($placeholders) AND transfer_invoice_number = ?");
            $deleteTransferStmt->bind_param($types . 's', ...array_merge($removeIds, [$invoiceNumber]));
            $deleteTransferStmt->execute();
        }

        // Handle items to add
        if (!empty($itemsToAdd)) {
             $transferStmt = $conn->prepare( "INSERT INTO inventory_transfers
                                      (motorcycle_id, from_branch, to_branch, transfer_date, transferred_by, notes, transfer_status, transfer_invoice_number)
                                      VALUES (?, ?, ?, ?, ?, ?, 'in-transit', ?)" );
            $updateMotorcycleStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'transferred', inventory_cost = ? WHERE id = ?");

            foreach ($itemsToAdd as $item) {
                $motorcycleId = intval($item['id']);
                $inventoryCost = floatval($item['inventory_cost']);

                // Update motorcycle status and cost
                $updateMotorcycleStmt->bind_param('di', $inventoryCost, $motorcycleId);
                $updateMotorcycleStmt->execute();

                // Create new transfer record
                $transferStmt->bind_param('isssiss', $motorcycleId, $fromBranch, $toBranch, $transferDate, $transferredBy, $notes, $invoiceNumber);
                $transferStmt->execute();
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer has been successfully updated.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

// --- BRANCH & SEARCHING FUNCTIONS ---

function getBranchInventory() {
    global $conn;

    $branch = isset( $_GET[ 'branch' ] ) ? sanitizeInput( $_GET[ 'branch' ] ) : '';
    if ( empty( $branch ) ) {
        echo json_encode( [ 'success' => false, 'message' => 'Branch parameter is required' ] );
        return;
    }

    $status = isset( $_GET[ 'status' ] ) ? sanitizeInput( $_GET[ 'status' ] ) : 'available';
    $page = isset( $_GET[ 'page' ] ) ? max( 1, intval( $_GET[ 'page' ] ) ) : 1;
    $perPage = isset( $_GET[ 'per_page' ] ) ? min( max( 1, intval( $_GET[ 'per_page' ] ) ), 100 ) : 10;
    $offset = ( $page - 1 ) * $perPage;
    $search = isset( $_GET[ 'search' ] ) ? sanitizeInput( $_GET[ 'search' ] ) : '';

    $sql = "SELECT SQL_CALC_FOUND_ROWS mi.*, i.invoice_number 
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.current_branch = ?";

    $params = [ $branch ];
    $types = 's';

    if ( $status === 'available' ) {
        $sql .= " AND mi.status = 'available'";
    } elseif ( $status === 'transferred' ) {
        $sql .= " AND mi.status = 'transferred'";
    } else {
        $sql .= " AND mi.status IN ('available', 'transferred')";
    }

    if ( !empty( $search ) ) {
        $sql .= " AND (mi.model LIKE ? OR mi.brand LIKE ? OR mi.engine_number LIKE ? 
                  OR mi.frame_number LIKE ? OR mi.color LIKE ? OR i.invoice_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge( $params, array_fill( 0, 6, $searchTerm ) );
        $types .= str_repeat( 's', 6 );
    }

    $sortField = isset( $_GET[ 'sort' ] ) ? sanitizeInput( $_GET[ 'sort' ] ) : 'brand';
    $sortOrder = isset( $_GET[ 'order' ] ) && strtoupper( $_GET[ 'order' ] ) === 'DESC' ? 'DESC' : 'ASC';

    $validSortFields = [ 'brand', 'model', 'color', 'engine_number', 'frame_number', 'date_delivered', 'status', 'invoice_number' ];
    if ( !in_array( $sortField, $validSortFields ) ) {
        $sortField = 'brand';
    }

    if ( $sortField === 'invoice_number' ) {
        $sortField = 'i.invoice_number';
    } else {
        $sortField = 'mi.' . $sortField;
    }

    $sql .= " ORDER BY $sortField $sortOrder LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare( $sql );
    if ( !$stmt ) {
        echo json_encode( [ 'success' => false, 'message' => 'Database error: ' . $conn->error ] );
        return;
    }

    if ( $types !== 's' ) {
        $stmt->bind_param( $types, ...$params );
    } else {
        $stmt->bind_param( $types, $branch );
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $totalResult = $conn->query( 'SELECT FOUND_ROWS()' );
    $totalRows = $totalResult->fetch_row()[ 0 ];
    $totalPages = ceil( $totalRows / $perPage );

    $data = [];
    while ( $row = $result->fetch_assoc() ) {
        $rowData = [
            'id' => $row[ 'id' ],
            'date_delivered' => $row[ 'date_delivered' ],
            'brand' => $row[ 'brand' ],
            'model' => $row[ 'model' ],
            'engine_number' => $row[ 'engine_number' ],
            'frame_number' => $row[ 'frame_number' ],
            'color' => $row[ 'color' ],
            'current_branch' => $row[ 'current_branch' ],
            'status' => $row[ 'status' ],
            'invoice_number' => $row[ 'invoice_number' ]
        ];

        if ( $row[ 'status' ] === 'transferred' ) {
            $transferStmt = $conn->prepare( "SELECT * FROM inventory_transfers 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY transfer_date DESC LIMIT 1" );
            $transferStmt->bind_param( 'i', $row[ 'id' ] );
            $transferStmt->execute();
            $transferResult = $transferStmt->get_result();

            if ( $transferResult->num_rows > 0 ) {
                $rowData[ 'last_transfer' ] = $transferResult->fetch_assoc();
            }
        }
        $data[] = $rowData;
    }

    echo json_encode( [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_items' => $totalRows,
            'total_pages' => $totalPages
        ]
    ] );
}

function getBranchesWithInventory() {
    global $conn;

    $sql = "SELECT 
                mi.current_branch AS branch, 
                COALESCE(GROUP_CONCAT(DISTINCT CONCAT(mi.brand, ' ', mi.model) SEPARATOR ', '), '') AS models,
                COUNT(*) AS total_quantity,
                SUM(CASE WHEN mi.status = 'transferred' THEN 1 ELSE 0 END) AS transferred_count
            FROM motorcycle_inventory mi
            WHERE mi.status IN ('available', 'transferred')
            GROUP BY mi.current_branch
            HAVING COUNT(*) > 0
            ORDER BY mi.current_branch";

    $result = $conn->query( $sql );

    if ( !$result ) {
        echo json_encode( [ 'success' => false, 'message' => 'Error fetching branches: ' . $conn->error ] );
        return;
    }

    $data = [];
    while ( $row = $result->fetch_assoc() ) {
        $row[ 'models' ] = !empty( $row[ 'models' ] ) ? explode( ', ', $row[ 'models' ] ) : [];
        $data[] = $row;
    }

    echo json_encode( [ 'success' => true, 'data' => $data ] );
}


function searchInventory() {
    global $conn;

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    $field = isset($_GET['field']) ? trim($_GET['field']) : 'all';
    $includeInventoryCost = isset($_GET['include_inventory_cost']) ? true : false;

   $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
               mi.inventory_cost, mi.current_branch, mi.status, i.invoice_number,
               ms.sale_date, ms.customer_name, ms.payment_type, ms.dr_number, ms.cod_amount, ms.terms, ms.monthly_amortization
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        LEFT JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
        WHERE mi.status IN ('available', 'sold')";


    $params = [];
    $types = '';

    if (!empty($query)) {
        $searchTerm = "%$query%";
        if ($field === 'engine_number') {
            $sql .= " AND mi.engine_number LIKE ?";
            $params[] = $searchTerm;
            $types .= 's';
        } else {
            $sql .= " AND (mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? 
                        OR mi.frame_number LIKE ? OR i.invoice_number LIKE ?)";
            // Add the same search term 5 times for the 5 LIKE conditions
            for ($i = 0; $i < 5; $i++) {
                $params[] = $searchTerm;
                $types .= 's';
            }
        }
    }

    $sql .= " ORDER BY mi.brand, mi.model LIMIT 10";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        return;
    }

    if (!empty($params)) {
        // Use call_user_func_array to bind params dynamically
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name; // Note the reference
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to execute statement']);
        return;
    }

    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}


function searchInventoryByEngine() {
    global $conn;

    if ( !isset( $_SESSION[ 'user_branch' ] ) ) {
        echo json_encode( [ 'success' => false, 'message' => 'User branch not set' ] );
        return;
    }

    $userBranch = $_SESSION[ 'user_branch' ];
    $query = isset( $_GET[ 'query' ] ) ? sanitizeInput( $_GET[ 'query' ] ) : '';
    $field = isset( $_GET[ 'field' ] ) ? sanitizeInput( $_GET[ 'field' ] ) : 'all';
    $includeInventoryCost = isset( $_GET[ 'include_inventory_cost' ] ) ? true : false;
    $fuzzySearch = isset( $_GET[ 'fuzzy_search' ] ) ? true : false;

   $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
               mi.inventory_cost, mi.current_branch, mi.status, i.invoice_number,
               ms.sale_date
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        LEFT JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
        WHERE mi.status IN ('available', 'transferred', 'sold') AND mi.current_branch = '$userBranch'";

    $params = [];
    $types = '';

    if ( !empty( $query ) ) {
        if ( $field === 'engine_number' ) {
            if ( $fuzzySearch ) {
                $sql .= ' AND (mi.engine_number LIKE ? OR mi.engine_number LIKE ? OR mi.engine_number LIKE ?)';
                $searchTerm1 = "%$query%";
                $searchTerm2 = "$query%";
                $searchTerm3 = "%$query";
                $params = [ $searchTerm1, $searchTerm2, $searchTerm3 ];
                $types = str_repeat( 's', count( $params ) );
            } else {
                $sql .= ' AND mi.engine_number LIKE ?';
                $searchTerm = "%$query%";
                $params[] = $searchTerm;
                $types = 's';
            }
        } else {
            $sql .= " AND (mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? 
                      OR mi.frame_number LIKE ? OR i.invoice_number LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_fill( 0, 5, $searchTerm );
            $types = str_repeat( 's', count( $params ) );
        }
    }

    $sql .= ' ORDER BY mi.brand, mi.model LIMIT 20';

    $stmt = $conn->prepare( $sql );

    if ( !empty( $params ) ) {
        $stmt->bind_param( $types, ...$params );
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ( $row = $result->fetch_assoc() ) {
        $data[] = $row;
    }

    echo json_encode( [ 'success' => true, 'data' => $data ] );
}

function searchTransferReceipt() {
    global $conn;
    
    $transferInvoiceNumber = isset($_GET['transfer_invoice_number']) ? sanitizeInput($_GET['transfer_invoice_number']) : '';
    
    if (empty($transferInvoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Transfer invoice number is required']);
        return;
    }
    
    // NEW, CORRECTED QUERY USING GROUP BY
    $sql = "SELECT
                MIN(it.id) as id, -- Select one representative ID for the group
                it.transfer_invoice_number,
                it.from_branch,
                it.to_branch,
                it.transfer_date
            FROM inventory_transfers it
            WHERE it.transfer_invoice_number LIKE ?
            GROUP BY
                it.transfer_invoice_number,
                it.from_branch,
                it.to_branch,
                it.transfer_date
            ORDER BY it.transfer_date DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$transferInvoiceNumber%";
    $stmt->bind_param('s', $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
}


// --- REPORTING FUNCTIONS ---

function getAllModels() {
    global $conn;

    $sql = "SELECT DISTINCT model FROM motorcycle_inventory WHERE model IS NOT NULL AND model != '' ORDER BY model ASC";
    $result = $conn->query($sql);

    if ($result) {
        $models = [];
        while ($row = $result->fetch_assoc()) {
            $models[] = $row['model'];
        }
        echo json_encode(['success' => true, 'data' => $models]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching models: ' . $conn->error]);
    }
}
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
        $startDate    = date('Y-m-01', strtotime($month));
        $endDate      = date('Y-m-t', strtotime($month));
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

    // Initialize all variables to avoid undefined warnings
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
    // MODIFIED: Added checks for scraps and transfers before the period starts for a more accurate beginning balance.
    $sqlBeginning = "
        SELECT COUNT(*) AS count_beginning, COALESCE(SUM(mi.inventory_cost), 0) AS cost_beginning
        FROM motorcycle_inventory mi
        WHERE mi.deleted_at IS NULL
          AND (COALESCE(mi.date_received, mi.date_delivered) <= ?) -- Arrived on or before the previous month's end
          AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
          AND NOT EXISTS (SELECT 1 FROM motorcycle_scraps sc WHERE sc.motorcycle_id = mi.id AND sc.scrap_date <= ?)
          AND NOT EXISTS (SELECT 1 FROM inventory_transfers it WHERE it.motorcycle_id = mi.id AND it.transfer_status = 'completed' AND it.transfer_date <= ?)
          $categoryCondition $brandCondition $modelCondition
    ";
    $stmtBeginning = $conn->prepare($sqlBeginning);
    // MODIFIED: More parameters are needed for the new NOT EXISTS clauses.
    $typesBeginning = 'ssss' . $paramTypes;
    $paramsBeginning = array_merge([$prevMonthEnd, $prevMonthEnd, $prevMonthEnd, $prevMonthEnd], $params);
    bindParams($stmtBeginning, $typesBeginning, $paramsBeginning);
} else {
    // MODIFIED: Added checks for scraps and transfers for a more accurate beginning balance.
    $sqlBeginning = "
        SELECT COUNT(*) as count_beginning, COALESCE(SUM(mi.inventory_cost), 0) as cost_beginning
        FROM motorcycle_inventory mi
        WHERE mi.deleted_at IS NULL AND mi.current_branch = ?
          AND (COALESCE(mi.date_received, mi.date_delivered) <= ?) -- Arrived on or before the previous month's end
          AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
          AND NOT EXISTS (SELECT 1 FROM motorcycle_scraps sc WHERE sc.motorcycle_id = mi.id AND sc.scrap_date <= ?)
          AND NOT EXISTS (SELECT 1 FROM inventory_transfers it WHERE it.motorcycle_id = mi.id AND it.transfer_status = 'completed' AND it.transfer_date <= ?)
          $categoryCondition $brandCondition $modelCondition
    ";
    $stmtBeginning = $conn->prepare($sqlBeginning);
    // MODIFIED: More parameters are needed for the new NOT EXISTS clauses.
    $typesBeginning = 'sssss' . $paramTypes;
    $paramsBeginning = array_merge([$branch, $prevMonthEnd, $prevMonthEnd, $prevMonthEnd, $prevMonthEnd], $params);
    bindParams($stmtBeginning, $typesBeginning, $paramsBeginning);
}
    $stmtBeginning->execute();
    $beginningResult = $stmtBeginning->get_result()->fetch_assoc();
    $countBeginning = (int)($beginningResult['count_beginning'] ?? 0);
    $costBeginning  = (float)($beginningResult['cost_beginning'] ?? 0);


     // === NEW DELIVERIES ===
    // === NEW DELIVERIES ===
if (strtoupper($branch) === 'ALL') {
    // MODIFIED: Removed the NOT EXISTS clause to correctly count units that
    // were delivered and then transferred in the same period.
    $sqlNewDeliveries = "
        SELECT COUNT(*) AS count_new, COALESCE(SUM(mi.inventory_cost), 0) AS cost_new
        FROM motorcycle_inventory mi
       WHERE mi.date_delivered BETWEEN ? AND ?
  AND mi.deleted_at IS NULL
     $categoryCondition
          $brandCondition
          $modelCondition
    ";
    // ...
        $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
        $types = 'ss' . $paramTypes;
        $paramsNewDeliveries = array_merge([$startDate, $endDate], $params);
        bindParams($stmtNewDeliveries, $types, $paramsNewDeliveries);
   } else {
    // --- FINAL CORRECTED LOGIC ---
    $sqlNewDeliveries = "
        SELECT COUNT(DISTINCT mi.id) AS count_new, COALESCE(SUM(mi.inventory_cost), 0) AS cost_new
        FROM motorcycle_inventory mi
        WHERE mi.date_delivered BETWEEN ? AND ?
          AND mi.deleted_at IS NULL
          AND (
                -- Condition 1: The item was delivered, never transferred, and is at the selected branch.
                (
                    mi.current_branch = ?
                    AND NOT EXISTS (SELECT 1 FROM inventory_transfers it WHERE it.motorcycle_id = mi.id)
                )
            OR
                -- Condition 2: The item was delivered AND its first transfer FROM this branch
                -- happened IN THE SAME PERIOD.
                (
                    EXISTS (
                        SELECT 1 FROM inventory_transfers it
                        WHERE it.motorcycle_id = mi.id
                          AND it.from_branch = ?
                          -- THIS DATE CHECK IS THE CRITICAL FIX:
                          AND it.transfer_date BETWEEN ? AND ?
                          AND it.transfer_date = (SELECT MIN(it2.transfer_date) FROM inventory_transfers it2 WHERE it2.motorcycle_id = mi.id)
                    )
                )
          )
          $categoryCondition $brandCondition $modelCondition
    ";
    $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
    // Note: The parameters and types have changed due to the new date check
    $types = 'ssssss' . $paramTypes;
    $paramsNewDeliveries = array_merge([
        $startDate,  // for date_delivered
        $endDate,    // for date_delivered
        $branch,     // for current_branch
        $branch,     // for from_branch
        $startDate,  // for transfer_date
        $endDate     // for transfer_date
    ], $params);
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

   // === ACTUAL ENDING BALANCE & DETAILED DATA (as of endDate cutoff) ===
// This is the corrected code
$repoSelects = '';
$repoJoins = '';
if ($category === 'repo') {
    $repoSelects = ", 
        COALESCE(ms_info.customer_name, '-') AS customer_name,
        COALESCE(ms_info.sale_date, '-') AS date_sold
    ";
    $repoJoins = "
        LEFT JOIN motorcycle_sales ms_info ON mi.id = ms_info.motorcycle_id
    ";
}
  if (strtoupper($branch) === 'ALL') {
    // MODIFIED: Prioritize date_received over date_delivered for accurate inventory timing.
    $sqlEndingActualData = "
    SELECT mi.*, i.invoice_number $repoSelects
    FROM motorcycle_inventory mi
    LEFT JOIN invoices i ON mi.invoice_id = i.id
    $repoJoins
    WHERE mi.deleted_at IS NULL
          AND (COALESCE(mi.date_received, mi.date_delivered) <= ?) -- Use the true arrival date
         -- MODIFIED: Include units that are sold IF their category is 'repo'.
          AND (
               LOWER(mi.category) = 'repo' 
               OR NOT EXISTS (
                    SELECT 1 FROM motorcycle_sales s
                    WHERE s.motorcycle_id = mi.id
                      AND s.sale_date <= ?
               )
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
       $types = 'ss' . $paramTypes;
    $paramsData = array_merge([$endDate, $endDate], $params);
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
    // MODIFIED: Prioritize date_received over date_delivered for accurate inventory timing.
    $sqlEndingActualData = "
    SELECT mi.*, i.invoice_number, mi.current_branch AS branch_at_cutoff $repoSelects
    FROM motorcycle_inventory mi
    LEFT JOIN invoices i ON mi.invoice_id = i.id
    $repoJoins
    WHERE mi.deleted_at IS NULL
          AND mi.current_branch = ?
          AND (COALESCE(mi.date_received, mi.date_delivered) <= ?) -- Use the true arrival date
         AND (
               LOWER(mi.category) = 'repo' 
               OR NOT EXISTS (
                    SELECT 1 FROM motorcycle_sales s
                    WHERE s.motorcycle_id = mi.id
                      AND s.sale_date <= ?
               )
          )
          $categoryCondition
           $brandCondition
           $modelCondition
        ORDER BY mi.brand, mi.model
    ";

    // Detailed rows
    $stmtData = $conn->prepare($sqlEndingActualData);
    // MODIFIED: Parameter count reduced from 4 to 3 for the main query.
    $types = 'sss' . $paramTypes;
    $paramsData = array_merge([
        $branch,   // mi.current_branch = ?
        $endDate,  // COALESCE(...) <= ?
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
    
    // Conditionally add repo-specific data
    if ($category === 'repo') {
        $item['customer_name'] = $row['customer_name'];
        $item['date_sold'] = $row['date_sold'];
    }
    
    $data[] = $item;
}

    // === TRANSFER DETAILS (for discrepancy calc and separate reporting) ===
    if (strtoupper($branch) === 'ALL') {
        $sqlTransferDetails = "
            SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost,
                   'TRANSFER' AS transfer_type
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.transfer_date BETWEEN ? AND ?
              AND it.transfer_status = 'completed'
              $categoryCondition
               $brandCondition
               $modelCondition
            ORDER BY it.transfer_date DESC
        ";
        $stmtTransferDetails = $conn->prepare($sqlTransferDetails);
        $types = 'ss' . $paramTypes;
        $paramsTransferDetails = array_merge([$startDate, $endDate], $params);
        bindParams($stmtTransferDetails, $types, $paramsTransferDetails);
    } else {
        $sqlTransferDetails = "
            SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost,
                   CASE 
                       WHEN it.from_branch = ? THEN 'OUT'
                       WHEN it.to_branch   = ? THEN 'IN'
                       ELSE 'TRANSFER'
                   END AS transfer_type
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE (it.from_branch = ? OR it.to_branch = ?)
              AND it.transfer_date BETWEEN ? AND ?
              AND it.transfer_status = 'completed'
              $categoryCondition
               $brandCondition
               $modelCondition
            ORDER BY it.transfer_date DESC
        ";
        $stmtTransferDetails = $conn->prepare($sqlTransferDetails);
        $types = 'ssssss' . $paramTypes;
        $paramsTransferDetails = array_merge([$branch, $branch, $branch, $branch, $startDate, $endDate], $params);
        bindParams($stmtTransferDetails, $types, $paramsTransferDetails);
    }
    $stmtTransferDetails->execute();
    $transferDetailsResult = $stmtTransferDetails->get_result();

    $transferDetails = [];
    while ($row = $transferDetailsResult->fetch_assoc()) {
        $transferDetails[] = [
            'id' => (int)$row['motorcycle_id'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'color' => null,
            'engine_number' => $row['engine_number'],
            'frame_number' => $row['frame_number'],
            'inventory_cost' => (float)$row['inventory_cost'],
            'current_branch' => $row['to_branch'] ?? $row['from_branch'],
            'status' => 'transfer_' . strtolower($row['transfer_type']),
            'date_delivered' => $row['transfer_date'],
            'invoice_number' => null,
            'category' => null,
            'branch_at_cutoff' => null,
            'record_type' => 'transfer',
            'transfer_type' => $row['transfer_type'],
            'from_branch' => $row['from_branch'],
            'to_branch' => $row['to_branch'],
            'date_received' => $row['date_received']
        ];
    }


    $actualByModelBranch = [];
    foreach ($data as $item) {
        $branchForGrouping = !empty($item['branch_at_cutoff']) ? $item['branch_at_cutoff'] : $item['current_branch'];
        $key = $item['model'] . '||' . $branchForGrouping;
        if (!isset($actualByModelBranch[$key])) {
            $actualByModelBranch[$key] = ['count' => 0, 'cost' => 0];
        }
        $actualByModelBranch[$key]['count'] += 1;
        $actualByModelBranch[$key]['cost']  += $item['inventory_cost'];
    }

    $calculatedByModelBranch = [];
    foreach ($transferDetails as $transfer) {
        $toBranch = $transfer['to_branch'] ?? '';
        $model    = $transfer['model'] ?? '';
        $key = $model . '||' . $toBranch;
        if (!isset($calculatedByModelBranch[$key])) {
            $calculatedByModelBranch[$key] = ['count' => 0, 'cost' => 0];
        }
        if (in_array($transfer['transfer_type'], ['IN', 'TRANSFER'])) {
            $calculatedByModelBranch[$key]['count'] += 1;
            $calculatedByModelBranch[$key]['cost']  += (float)$transfer['inventory_cost'];
        }
    }
    foreach ($transferDetails as $transfer) {
        $fromBranch = $transfer['from_branch'] ?? '';
        $model      = $transfer['model'] ?? '';
        $key = $model . '||' . $fromBranch;
        if (!isset($calculatedByModelBranch[$key])) {
            $calculatedByModelBranch[$key] = ['count' => 0, 'cost' => 0];
        }
        if (in_array($transfer['transfer_type'], ['OUT', 'TRANSFER'])) {
            $calculatedByModelBranch[$key]['count'] -= 1;
            $calculatedByModelBranch[$key]['cost']  -= (float)$transfer['inventory_cost'];
        }
    }

    $discrepancies = [];
    foreach ($actualByModelBranch as $key => $actual) {
        $calculated = $calculatedByModelBranch[$key] ?? ['count' => 0, 'cost' => 0];
        list($model, $branchName) = explode('||', $key);
        $discrepancies[] = [
            'model' => $model,
            'branch' => $branchName,
            'actual_count' => $actual['count'],
            'actual_cost' => $actual['cost'],
            'calculated_count' => $calculated['count'],
            'calculated_cost' => $calculated['cost'],
            'count_discrepancy' => $actual['count'] - $calculated['count'],
            'cost_discrepancy' => $actual['cost'] - $calculated['cost'],
        ];
    }

    // === Build response ===
    $response = [
        'success' => true,
        'data' => $data,
        'transfer_details' => $transferDetails,
        'month' => $month,
        'as_of_date' => $asOfDate,
        'branch' => $branch,
        'summary' => [
            'beginning_balance' => $countBeginning,
            'received_transfers' => $countReceived,
            'new_deliveries' => $countNewDeliveries,
            'in' => $countIn,
            'transfers_out' => $countTransfersOut,
            'sold_during_month' => $countSoldDuringMonth,
            'out' => $countOut,
            'ending_calculated' => $countEndingCalculated,
            'ending_actual' => $countEndingActual,
            'inventory_cost' => [
                'beginning_balance' => $costBeginning,
                'received_transfers' => $costReceived,
                'new_deliveries' => $costNewDeliveries,
                'in' => $costIn,
                'transfers_out' => $costTransfersOut,
                'sold_during_month' => $costSoldDuringMonth,
                'out' => $costOut,
                'ending_calculated' => $costEndingCalculated,
                'ending_actual' => $costEndingActual
            ]
        ], 
        'inventory_cost_formatted' => [
            'beginning_balance' => number_format($costBeginning, 2),
            'received_transfers' => number_format($costReceived, 2),
            'new_deliveries' => number_format($costNewDeliveries, 2),
            'in' => number_format($costIn, 2),
            'transfers_out' => number_format($costTransfersOut, 2),
            'sold_during_month' => number_format($costSoldDuringMonth, 2),
            'out' => number_format($costOut, 2),
            'ending_calculated' => number_format($costEndingCalculated, 2),
            'ending_actual' => number_format($costEndingActual, 2)
        ],
        'discrepancy' => [
            'count' => $countEndingActual - $countEndingCalculated,
            'cost' => $costEndingActual - $costEndingCalculated,
            'cost_formatted' => number_format($costEndingActual - $costEndingCalculated, 2)
        ],
        'discrepancies_by_model_branch' => $discrepancies,
        'calculation_breakdown' => [
            'formula' => 'Beginning Balance + IN - OUT = Ending Balance',
            'detailed_formula' => 'Beginning Balance + (New Deliveries + Received Transfers) - (Transfers Out + Sold) = Ending Balance',
            'calculation' => "$countBeginning + $countIn - $countOut = $countEndingCalculated",
            'detailed_calculation' => "$countBeginning + ($countNewDeliveries + $countReceived) - ($countTransfersOut + $countSoldDuringMonth) = $countEndingCalculated",
            'cost_calculation' => number_format($costBeginning, 2) . ' + ' . number_format($costIn, 2) . ' - ' . number_format($costOut, 2) . ' = ' . number_format($costEndingCalculated, 2)
        ],
        'debug_info' => [
            'query_period' => "$startDate to $endDate",
            'previous_month_end' => $prevMonthEnd,
            'branch_filter' => $branch,
            'category_filter' => $category,
            'raw_counts' => [
                'beginning_balance' => $countBeginning,
                'new_deliveries' => $countNewDeliveries,
                'received_transfers' => $countReceived,
                'total_in' => $countIn,
                'transfers_out' => $countTransfersOut,
                'sold_during_month' => $countSoldDuringMonth,
                'total_out' => $countOut,
                'ending_calculated' => $countEndingCalculated,
                'ending_actual' => $countEndingActual
            ]
        ]
    ];

    echo json_encode($response);
}

function getMonthlyTransferredSummary() {
    global $conn;

    // --- 1. Dynamic Date Logic ---
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

    if (!empty($date)) {
        $startDate = $date;
        $endDate = $date;
    } elseif (!empty($month)) {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
    } elseif (empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'message' => 'A valid date, month, or custom date range is required.']);
        return;
    }
    
    // --- 2. Filter Logic (remains the same) ---
    $branch = isset($_GET['branch']) ? strtolower(sanitizeInput($_GET['branch'])) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

   // ADD this entire block
$whereClauses = ["it.transfer_status = 'completed'"];
$params = [];
$types = '';
$modelCondition = ''; // Initialize to prevent PHP warnings

if ($branch !== 'all') {
    $whereClauses[] = "it.from_branch = ?";
    $params[] = $branch;
    $types .= 's';
}
if ($category !== 'all') {
    $whereClauses[] = "LOWER(mi.category) = ?";
    $params[] = $category;
    $types .= 's';
}
if ($brand !== 'all') {
    $userBranch = isset($_SESSION['user_branch']) ? strtoupper($_SESSION['user_branch']) : '';
    if ($userBranch === 'HEADOFFICE') {
         $whereClauses[] = "LOWER(mi.brand) = ?";
         $params[] = $brand;
         $types .= 's';
    }
}

if ($models_str !== 'all' && !empty($models_str)) {
    $models = array_map('trim', explode(',', $models_str));
    if (!empty($models)) {
        $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
        $whereClauses[] = "mi.model IN ($modelPlaceholders)";
        foreach ($models as $model) {
            $params[] = $model;
            $types .= 's';
        }
    }
}

$whereClauses[] = "it.transfer_date BETWEEN ? AND ?";
$params[] = $startDate;
$params[] = $endDate;
$types .= 'ss';

$whereClause = implode(" AND ", $whereClauses);

$sql = "SELECT 
            mi.model, mi.color, mi.brand, mi.engine_number, mi.frame_number, mi.inventory_cost,
            it.transfer_date, it.to_branch as transferred_to, it.from_branch as transferred_from,
            i.invoice_number
        FROM motorcycle_inventory mi
        INNER JOIN inventory_transfers it ON mi.id = it.motorcycle_id
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        WHERE $whereClause
        ORDER BY it.transfer_date DESC, mi.model";

   $stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $totalTransferred = 0;
    $totalInventoryCost = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalTransferred++;
        $totalInventoryCost += (float)$row['inventory_cost'];
    }

    // --- 4. Return all relevant date parameters in the response ---
    echo json_encode([
        'success' => true,
        'data' => $data,
        'month' => $month,
        'date' => $date,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'branch' => $branch,
        'category' => $category,
        'summary' => [
            'total_transferred' => $totalTransferred,
            'total_inventory_cost' => $totalInventoryCost
        ]
    ]);
}

function getMonthlyReceivedSummary() {
    global $conn;

    // --- 1. Get all date and filter parameters ---
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // --- 2. Determine date range ---
    if (!empty($date)) {
        $startDate = $date;
        $endDate = $date;
    } elseif (!empty($month)) {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
    } elseif (empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'message' => 'A valid date, month, or custom date range is required.']);
        return;
    }

    // --- 3. Build WHERE clauses and parameters dynamically ---
    $mainWhereClauses = ["it.transfer_status = 'completed'"];
    $params = [];
    $types = '';

    if ($branch !== 'all') { 
        $mainWhereClauses[] = "UPPER(it.to_branch) = UPPER(?)"; 
        $params[] = $branch; 
        $types .= 's'; 
    }
    if ($category !== 'all') { 
        $mainWhereClauses[] = "LOWER(mi.category) = ?"; 
        $params[] = $category; 
        $types .= 's'; 
    }
    
    $userBranch = isset($_SESSION['user_branch']) ? strtoupper($_SESSION['user_branch']) : '';
    if ($brand !== 'all' && $userBranch === 'HEADOFFICE') { 
        $mainWhereClauses[] = "LOWER(mi.brand) = ?"; 
        $params[] = $brand; 
        $types .= 's'; 
    }
    
    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $mainWhereClauses[] = "mi.model IN ($modelPlaceholders)";
            foreach ($models as $model) {
                $params[] = $model;
                $types .= 's';
            }
        }
    }
    
    $mainWhereClauses[] = "it.date_received BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';

    $whereClause = implode(" AND ", $mainWhereClauses);

    // --- 4. Construct and Execute the SQL Query ---
    $sql = "SELECT 
                mi.model, mi.color, mi.brand, mi.engine_number, mi.frame_number, mi.inventory_cost,
                it.date_received, it.from_branch as received_from, it.to_branch as received_by, i.invoice_number
            FROM motorcycle_inventory mi
            JOIN inventory_transfers it ON mi.id = it.motorcycle_id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE $whereClause
            ORDER BY it.date_received DESC, mi.model";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
        return;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    // --- 5. Process and return the results ---
    $data = [];
    $totalReceived = 0;
    $totalInventoryCost = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalReceived++;
        $totalInventoryCost += (float)$row['inventory_cost'];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'month' => $month,
        'date' => $date,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'branch' => $branch,
        'category' => $category,
        'summary' => [
            'total_received' => $totalReceived,
            'total_inventory_cost' => $totalInventoryCost
        ]
    ]);
}

function getMonthlyScrappedSummary() {
    global $conn;

    // --- 1. Get all date and filter parameters ---
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // --- 2. Determine date range ---
    if (!empty($date)) {
        $startDate = $date;
        $endDate = $date;
    } elseif (!empty($month)) {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
    } elseif (empty($startDate) || empty($endDate)) {
        echo json_encode(['success' => false, 'message' => 'A valid date, month, or custom date range is required.']);
        return;
    }

    // --- 3. Build WHERE clauses and parameters dynamically ---
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($branch !== 'all') { $conditions[] = "mi.current_branch = ?"; $params[] = $branch; $types .= 's'; }
    if ($category !== 'all') { $conditions[] = "LOWER(mi.category) = ?"; $params[] = $category; $types .= 's'; }
    if ($brand !== 'all') { $conditions[] = "LOWER(mi.brand) = ?"; $params[] = $brand; $types .= 's'; }

    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $conditions[] = "mi.model IN ($modelPlaceholders)";
            foreach ($models as $model) {
                $params[] = $model;
                $types .= 's';
            }
        }
    }
    
    $conditions[] = "ms.scrap_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    // --- 4. Construct and Execute SQL Queries ---
    $sql = "SELECT 
                mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.current_branch,
                mi.inventory_cost, mi.category, ms.scrap_date, ms.scrap_reason, i.invoice_number
            FROM motorcycle_inventory mi
            INNER JOIN motorcycle_scraps ms ON mi.id = ms.motorcycle_id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            $whereClause
            ORDER BY ms.scrap_date DESC, mi.brand, mi.model";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $totalScrapped = 0;
    $totalInventoryCost = 0;
    $summaryByBrandBranch = [];
    $summaryByReason = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalScrapped++;
        $totalInventoryCost += (float)$row['inventory_cost'];
    }

    // You can add your more detailed summary queries here if needed, 
    // for now, we'll use the main data for a simple summary.
    
    // --- 5. Return JSON response ---
    echo json_encode([
        'success' => true,
        'month' => $month,
        'date' => $date,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'branch' => $branch,
        'category' => $category,
        'brand' => $brand,
        'data' => $data,
        'summary' => [
            'total_scrapped' => $totalScrapped,
            'total_inventory_cost' => $totalInventoryCost
        ],
        'summary_by_brand_branch' => [], // Populate if needed
        'summary_by_reason' => [] // Populate if needed
    ]);
}
function getAvailableMotorcyclesReport() {
    global $conn;

    // --- 1. Get all date and filter parameters ---
    $period_type = isset($_GET['period_type']) ? sanitizeInput($_GET['period_type']) : 'as_of_date';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $start_date_param = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $end_date_param = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // --- 2. Determine the report's effective end date ---
    $reportEndDate = date('Y-m-d'); // Default
    switch ($period_type) {
        case 'daily':
        case 'as_of_date':
            if (!empty($date)) { $reportEndDate = $date; }
            break;
        case 'monthly':
            if (!empty($month)) { $reportEndDate = date('Y-m-t', strtotime($month)); }
            break;
        case 'custom_range':
            if (!empty($end_date_param)) { $reportEndDate = $end_date_param; }
            break;
    }

    // --- 3. Build Query based on Branch Selection ---
    $sql = "";
    $params = [];
    $types = '';

    // Prepare filter conditions that are common to both queries
    $filterConditions = "";
    $filterParams = [];
    $filterTypes = '';

    if ($category !== 'all') { 
        $filterConditions .= " AND LOWER(mi.category) = ?"; 
        $filterParams[] = $category; 
        $filterTypes .= 's'; 
    }
    if ($brand !== 'all') { 
        $filterConditions .= " AND LOWER(mi.brand) = ?"; 
        $filterParams[] = $brand; 
        $filterTypes .= 's'; 
    }
    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $filterConditions .= " AND mi.model IN ($modelPlaceholders)";
            foreach ($models as $model) { 
                $filterParams[] = $model; 
                $filterTypes .= 's'; 
            }
        }
    }

    // This is the logic you requested to be copied
    if (strtoupper($branch) === 'ALL') {
        // Query for ALL branches
        $sql = "
            SELECT mi.*, i.invoice_number
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE
                mi.deleted_at IS NULL
                AND (COALESCE(mi.date_received, mi.date_delivered) <= ?)
                AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
                AND NOT EXISTS (SELECT 1 FROM motorcycle_scraps sc WHERE sc.motorcycle_id = mi.id AND sc.scrap_date <= ?)
                $filterConditions
            ORDER BY mi.current_branch, mi.brand, mi.model
        ";
        
        $params = array_merge([$reportEndDate, $reportEndDate, $reportEndDate], $filterParams);
        $types = 'sss' . $filterTypes;

    } else {
        // Query for a SPECIFIC branch
        $sql = "
            SELECT mi.*, i.invoice_number
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE
                mi.deleted_at IS NULL
                AND mi.current_branch = ?
                AND (COALESCE(mi.date_received, mi.date_delivered) <= ?)
                AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
                AND NOT EXISTS (SELECT 1 FROM motorcycle_scraps sc WHERE sc.motorcycle_id = mi.id AND sc.scrap_date <= ?)
                $filterConditions
            ORDER BY mi.brand, mi.model
        ";
        
        $params = array_merge([$branch, $reportEndDate, $reportEndDate, $reportEndDate], $filterParams);
        $types = 'ssss' . $filterTypes;
    }
    
    // --- 4. Execute Query and Build Response ---
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        
        $response = [
            'success' => true,
            'data' => $data,
            'date' => ($period_type === 'daily' || $period_type === 'as_of_date') ? $reportEndDate : null,
            'month' => ($period_type === 'monthly') ? date('Y-m', strtotime($reportEndDate)) : null,
            'start_date' => ($period_type === 'custom_range') ? $start_date_param : null,
            'end_date' => ($period_type === 'custom_range') ? $end_date_param : null
        ];
        
        echo json_encode($response);

    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement: ' . $conn->error]);
    }
}
function getSoldMotorcyclesReport() {
    global $conn;

    // --- 1. Get all date and filter parameters ---
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;

    $saleType = isset($_GET['sale_type']) ? strtolower(sanitizeInput($_GET['sale_type'])) : 'all';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // --- 2. Determine date range and prepare parameters ---
    $params = [];
    $types = '';
    $dateCondition = '';
    
    if ($date) { // Handles Daily and As-of Date
        $startDate = $date;
        $endDate = $date;
        $dateCondition = " AND ms.sale_date = ?";
        $params[] = $date;
        $types .= 's';
    } elseif ($month) { // Handles Monthly
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
        $dateCondition = " AND ms.sale_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    } elseif ($startDate && $endDate) { // Handles Custom Range
        $dateCondition = " AND ms.sale_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    } else {
        echo json_encode(['success' => false, 'message' => 'A valid date, month, or custom date range is required.']);
        return;
    }

    // --- 3. Build the SQL query ---
    $sqlBase = "SELECT ms.sale_date, ms.customer_name, mi.model, mi.engine_number, mi.frame_number,
                      ms.payment_type, ms.dr_number, ms.cod_amount, ms.terms, ms.monthly_amortization,
                      mi.current_branch, mi.brand, mi.category
                FROM motorcycle_sales ms
                INNER JOIN motorcycle_inventory mi ON ms.motorcycle_id = mi.id
                WHERE 1=1 ";

    $sqlBase .= $dateCondition;

    // --- 4. Add other filters ---
    if ($saleType !== 'all') { $sqlBase .= " AND LOWER(ms.payment_type) = ?"; $params[] = $saleType; $types .= 's'; }
    if ($branch !== 'all') { $sqlBase .= " AND mi.current_branch = ?"; $params[] = $branch; $types .= 's'; }
    if ($category !== 'all') { $sqlBase .= " AND LOWER(mi.category) = ?"; $params[] = $category; $types .= 's'; }
    if ($brand !== 'all') { $sqlBase .= " AND LOWER(mi.brand) = ?"; $params[] = $brand; $types .= 's'; }
    
    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $sqlBase .= " AND mi.model IN ($modelPlaceholders)";
            foreach ($models as $model) {
                $params[] = $model;
                $types .= 's';
            }
        }
    }

    $sqlBase .= " ORDER BY ms.sale_date DESC";

    // --- 5. Prepare and execute the query ---
    $stmt = $conn->prepare($sqlBase);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
        return;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // --- 6. Return JSON response ---
    echo json_encode([
        'success' => true, 
        'data' => $data,
        'month' => $month,   
        'date' => $date,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'branch' => $branch,
    ]);
}

function getDailySoldMotorcyclesReport() {
    global $conn;

    $saleType = isset($_GET['sale_type']) ? strtolower(sanitizeInput($_GET['sale_type'])) : 'all';
    $branch = isset($_GET['branch']) ? strtolower(sanitizeInput($_GET['branch'])) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    if (!$date) {
        echo json_encode(['success' => false, 'message' => 'Date parameter is required']);
        return;
    }

    $validTypes = ['all', 'cod', 'installment'];
    if (!in_array($saleType, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid sale type']);
        return;
    }

    $sqlBase = "SELECT ms.sale_date, ms.customer_name, mi.model, mi.engine_number, mi.frame_number,
                       ms.payment_type, ms.dr_number, ms.cod_amount, ms.terms, ms.monthly_amortization,
                       mi.current_branch, mi.brand, mi.category
                FROM motorcycle_sales ms
                INNER JOIN motorcycle_inventory mi ON ms.motorcycle_id = mi.id
                WHERE DATE(ms.sale_date) = ?";

    $params = [$date];
    $types = 's';

    if ($saleType !== 'all') {
        $sqlBase .= " AND LOWER(ms.payment_type) = ?";
        $params[] = $saleType;
        $types .= 's';
    }

    if ($branch !== 'all') {
        $sqlBase .= " AND LOWER(mi.current_branch) = ?";
        $params[] = $branch;
        $types .= 's';
    }

    if ($category !== 'all') {
        $sqlBase .= " AND LOWER(mi.category) = ?";
        $params[] = $category;
        $types .= 's';
    }

    if ($brand !== 'all') {
        $sqlBase .= " AND LOWER(mi.brand) = ?";
        $params[] = $brand;
        $types .= 's';
    }
    if ($models_str !== 'all' && !empty($models_str)) {
    $models = array_map('trim', explode(',', $models_str));
    if (!empty($models)) {
        $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
        $sqlBase .= " AND mi.model IN ($modelPlaceholders)";
        foreach ($models as $model) {
            $params[] = $model;
            $types .= 's';
        }
    }
}

    $sqlBase .= " ORDER BY ms.sale_date DESC";

    $stmt = $conn->prepare($sqlBase);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        return;
    }

    if (!empty($params)) {

        $bind_names[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}




// --- VALIDATION & CHECK FUNCTIONS ---

function checkInvoiceNumber() {
    global $conn;

    if ( empty( $_POST[ 'invoice_number' ] ) ) {
        echo json_encode( [ 'exists' => false ] );
        return;
    }

    $invoiceNumber = sanitizeInput( $_POST[ 'invoice_number' ] );

    $stmt = $conn->prepare( 'SELECT id FROM invoices WHERE invoice_number = ?' );
    $stmt->bind_param( 's', $invoiceNumber );
    $stmt->execute();

    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;

    echo json_encode( [ 'exists' => $exists ] );
}

function checkEngineNumber() {
    global $conn;

    if ( empty( $_POST[ 'engine_number' ] ) ) {
        echo json_encode( [ 'exists' => false ] );
        return;
    }

    $engineNumber = sanitizeInput( $_POST[ 'engine_number' ] );
    $excludeId = isset( $_POST[ 'exclude_id' ] ) ? intval( $_POST[ 'exclude_id' ] ) : 0;

    if ( $excludeId > 0 ) {
        // For updates - exclude current record
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE engine_number = ? AND id != ?' );
        $stmt->bind_param( 'si', $engineNumber, $excludeId );
    } else {
        // For new records
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE engine_number = ?' );
        $stmt->bind_param( 's', $engineNumber );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;

    echo json_encode( [ 'exists' => $exists ] );
}

function checkFrameNumber() {
    global $conn;

    if ( empty( $_POST[ 'frame_number' ] ) ) {
        echo json_encode( [ 'exists' => false ] );
        return;
    }

    $frameNumber = sanitizeInput( $_POST[ 'frame_number' ] );
    $excludeId = isset( $_POST[ 'exclude_id' ] ) ? intval( $_POST[ 'exclude_id' ] ) : 0;

    if ( $excludeId > 0 ) {
        // For updates - exclude current record
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE frame_number = ? AND id != ?' );
        $stmt->bind_param( 'si', $frameNumber, $excludeId );
    } else {
        // For new records
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE frame_number = ?' );
        $stmt->bind_param( 's', $frameNumber );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;

    echo json_encode( [ 'exists' => $exists ] );
}



// --- DEPRECATED OR MISC FUNCTIONS ---
// (Add any other functions that don't fit above here)
function getCurrentBranch() {
    echo json_encode( [
        'success' => true,
        'branch' => $_SESSION[ 'user_branch' ] ?? 'RXS-S'
    ] );
}

function markAsRepo() {
    global $conn;

    // 1. Validate input
    $motorcycleId = isset($_POST['motorcycle_id']) ? intval($_POST['motorcycle_id']) : 0;
    $repoDate = isset($_POST['repo_date']) ? sanitizeInput($_POST['repo_date']) : null;
    $repoReason = isset($_POST['repo_reason']) ? sanitizeInput($_POST['repo_reason']) : '';
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if ($motorcycleId <= 0 || empty($repoDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // 2. Lock the row and check the current status
        $stmt_check = $conn->prepare("SELECT status FROM motorcycle_inventory WHERE id = ? FOR UPDATE");
        $stmt_check->bind_param('i', $motorcycleId);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            throw new Exception("Motorcycle not found.");
        }

        $currentStatus = $result_check->fetch_assoc()['status'];
        if ($currentStatus !== 'sold') {
            throw new Exception("This unit cannot be repossessed because it is not currently marked as sold.");
        }

        // 3. Update the motorcycle's status and category
        $stmt_update = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available', category = 'repo' WHERE id = ?");
        $stmt_update->bind_param('i', $motorcycleId);
        if (!$stmt_update->execute()) {
            throw new Exception("Failed to update motorcycle status.");
        }

        // 4. Delete the corresponding sale record
        $stmt_delete_sale = $conn->prepare("DELETE FROM motorcycle_sales WHERE motorcycle_id = ?");
        $stmt_delete_sale->bind_param('i', $motorcycleId);
        if (!$stmt_delete_sale->execute()) {
            // This is not a critical failure, but good to log
            error_log("Could not delete sale record for repossessed motorcycle ID: " . $motorcycleId);
        }

        // 5. Log the repossession event (assuming a 'motorcycle_repo_history' table exists)
        // If this table doesn't exist, you should create it:
        // CREATE TABLE motorcycle_repo_history (id INT AUTO_INCREMENT PRIMARY KEY, motorcycle_id INT, repo_date DATE, repo_reason TEXT, user_id INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
        $stmt_log = $conn->prepare("INSERT INTO motorcycle_repo_history (motorcycle_id, repo_date, repo_reason, user_id) VALUES (?, ?, ?, ?)");
        $stmt_log->bind_param('issi', $motorcycleId, $repoDate, $repoReason, $userId);
        if (!$stmt_log->execute()) {
            throw new Exception("Failed to log the repossession event.");
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Motorcycle has been successfully marked as REPO.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

function getDeliveredStocksSummary() {
    global $conn;

    // --- 1. Get date and filter parameters ---
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // --- 2. Determine date range ---
    if ($date) {
        $startDate = $date; $endDate = $date;
    } elseif ($month) {
        $startDate = date('Y-m-01', strtotime($month)); $endDate = date('Y-m-t', strtotime($month));
    } elseif (!$startDate || !$endDate) {
        echo json_encode(['success' => false, 'message' => 'A valid date range is required.']);
        return;
    }

    // --- 3. Build base query and initial filters ---
    $params = [];
    $types = '';

    // The main query is built first, with the primary date filter.
    $sql = "
        SELECT 
            mi.*, i.invoice_number,
            COALESCE(
                (SELECT it_first.from_branch FROM inventory_transfers it_first WHERE it_first.motorcycle_id = mi.id ORDER BY it_first.transfer_date ASC, it_first.id ASC LIMIT 1),
                mi.current_branch
            ) AS initial_delivery_branch
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        WHERE mi.date_delivered BETWEEN ? AND ?
    ";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
    
    // --- 4. Append additional filters ---
    if ($category !== 'all') { $sql .= " AND LOWER(mi.category) = ?"; $params[] = $category; $types .= 's'; }
    if ($brand !== 'all') { $sql .= " AND LOWER(mi.brand) = ?"; $params[] = $brand; $types .= 's'; }
    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $sql .= " AND mi.model IN ($modelPlaceholders)";
            foreach ($models as $model) { $params[] = $model; $types .= 's'; }
        }
    }

    // MODIFIED: Aligned the logic with getMonthlyInventory to ensure counts match.
    // The critical change is adding a date constraint to the transfer check.
    if ($branch !== 'all') {
        $sql .= " AND (
                    -- Condition 1: The item was delivered, never transferred, and is at the selected branch.
                    (
                        mi.current_branch = ?
                        AND NOT EXISTS (SELECT 1 FROM inventory_transfers it WHERE it.motorcycle_id = mi.id)
                    )
                OR
                    -- Condition 2: The item was delivered AND its first transfer FROM this branch
                    -- happened IN THE SAME PERIOD.
                    (
                        EXISTS (
                            SELECT 1 FROM inventory_transfers it
                            WHERE it.motorcycle_id = mi.id
                              AND it.from_branch = ?
                              AND it.transfer_date BETWEEN ? AND ?
                              AND it.transfer_date = (SELECT MIN(it2.transfer_date) FROM inventory_transfers it2 WHERE it2.motorcycle_id = mi.id)
                        )
                    )
              )";
        // Parameters for the new clause: current_branch, from_branch, startDate, endDate
        $params[] = $branch;
        $params[] = $branch;
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ssss';
    }

    $sql .= " ORDER BY mi.date_delivered DESC, mi.model";

    // --- 5. Execute Query ---
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        $totalDelivered = 0;
        $totalInventoryCost = 0;

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            $totalDelivered++;
            $totalInventoryCost += (float)$row['inventory_cost'];
        }

        echo json_encode([
            'success' => true, 'data' => $data,
            'month' => $month, 'date' => $date, 'start_date' => $startDate, 'end_date' => $endDate,
            'branch' => $branch,
            'summary' => [
                'total_delivered' => $totalDelivered,
                'total_inventory_cost' => $totalInventoryCost
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
    }
}

function getSoldUnits() {
    global $conn;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 15;
    $offset = ($page - 1) * $perPage;

    $countSql = "SELECT COUNT(*) as total FROM motorcycle_inventory WHERE status = 'sold'";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.frame_number, mi.current_branch,
                   ms.sale_date, ms.customer_name, ms.payment_type
            FROM motorcycle_inventory mi
            JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            WHERE mi.status = 'sold'
            ORDER BY ms.sale_date DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]
    ]);
}

function getRepossessedUnits() {
    global $conn;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 15;
    $offset = ($page - 1) * $perPage;

    $countSql = "SELECT COUNT(*) as total FROM motorcycle_inventory WHERE category = 'repo'";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.frame_number, mi.current_branch,
                   mrh.repo_date, mrh.repo_reason
            FROM motorcycle_inventory mi
            JOIN motorcycle_repo_history mrh ON mi.id = mrh.motorcycle_id
            WHERE mi.category = 'repo'
            ORDER BY mrh.repo_date DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]
    ]);
}

function getScrappedUnits() {
    global $conn;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 15;
    $offset = ($page - 1) * $perPage;

    $countSql = "SELECT COUNT(*) as total FROM motorcycle_inventory WHERE status = 'scrapped'";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $perPage);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.frame_number, mi.current_branch,
                   ms.scrap_date, ms.scrap_reason
            FROM motorcycle_inventory mi
            JOIN motorcycle_scraps ms ON mi.id = ms.motorcycle_id
            WHERE mi.status = 'scrapped'
            ORDER BY ms.scrap_date DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]
    ]);
}

function revertStatus() {
    global $conn;

    $id = isset($_POST['motorcycle_id']) ? intval($_POST['motorcycle_id']) : 0;
    $original_status = isset($_POST['original_status']) ? sanitizeInput($_POST['original_status']) : '';

    if ($id <= 0 || empty($original_status)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
        return;
    }

    $conn->begin_transaction();

    try {
        if ($original_status === 'sold') {
            $stmt = $conn->prepare("DELETE FROM motorcycle_sales WHERE motorcycle_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $update_stmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available' WHERE id = ?");
            $update_stmt->bind_param('i', $id);
            $update_stmt->execute();
        } elseif ($original_status === 'repo') {
            $stmt = $conn->prepare("DELETE FROM motorcycle_repo_history WHERE motorcycle_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            // When reverting a REPO, it should go back to being a 'brandnew' unit
            $update_stmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available', category = 'brandnew' WHERE id = ?");
            $update_stmt->bind_param('i', $id);
            $update_stmt->execute();
        } elseif ($original_status === 'scrapped') {
            $stmt = $conn->prepare("DELETE FROM motorcycle_scraps WHERE motorcycle_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $update_stmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available' WHERE id = ?");
            $update_stmt->bind_param('i', $id);
            $update_stmt->execute();
        } else {
            throw new Exception("Invalid status to revert.");
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction successfully reverted.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error reverting transaction: ' . $e->getMessage()]);
    }
}
?>