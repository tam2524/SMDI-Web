<?php
/**
 * API Endpoint for Motorcycle Inventory Management
 *

 *
 * @version 1.0
 * @author [Your Name]
 */



header('Content-Type: application/json');
require_once '../api/db_config.php';
require_once '../api/audit_helper.php';



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
            
            $reportContext = date('m/d/Y', strtotime($date));
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
            
            $reportContext = "For the period ending " . date('m/d/Y', strtotime($date));
            break;
        case 'custom':
            if (!$start_date || !$end_date) {
                return [null, 'Start and End dates are required.', null];
            }
            $startDate = $start_date;
            $endDate = $end_date;
            
            $reportContext = date('m/d/Y', strtotime($start_date)) . " to " . date('m/d/Y', strtotime($end_date));
            break;
        default:
            return [null, 'Invalid report period type.', null];
    }
    return [$startDate, $endDate, $reportContext];
}




$action = isset($_REQUEST['action']) ? sanitizeInput($_REQUEST['action']) : '';

switch ($action) {
   
    case 'get_inventory_dashboard':
        getInventoryDashboard();
        break;
    case 'get_inventory_table':
        getInventoryTable();
        break;
    case 'get_motorcycle':
        getMotorcycle();
        break;
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
          case 'mark_as_redeem':
        markAsRedeem();
        break;
    case 'sell_motorcycle':
        sellMotorcycle();
        break;
    case 'get_invoice_details':
        getInvoiceDetails();
        break;
    case 'search_invoice_number':
        searchInvoiceNumber();
        break;

    case 'get_direct_shipments':
    getDirectShipments();
    break;  
    case 'delete_invoice_transaction':
    deleteInvoiceTransaction();
    break;  
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
   
    case 'update_transfer_items':
        updateTransferItems();
        break;
    case 'get_transfers_by_status':
        getTransfersByStatus();
        break;
    case 'delete_transfer':
        deleteTransfer();
        break;
           case 'get_direct_shipment_for_edit':
        getDirectShipmentForEdit();
        break;
    case 'update_direct_shipment':
        updateDirectShipment();
        break;
 case 'get_sold_units':
        getSoldUnits();
        break;
    case 'get_repossessed_units':
        getRepossessedUnits();
        break;
    case 'get_scrapped_units':
        getScrappedUnits();
        break;
    case 'get_redeemed_units':
        getRedeemedUnits();
        break;
    case 'revert_transaction':
        revertTransaction();
        break;
    case 'get_activity_log':
        getActivityLog();
        break;
            
    case 'get_transfer_details_by_invoice':
        getTransferDetailsByInvoice();
        break;
    case 'update_transfer_group': 
        update_transfer_group(); 
        break;
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
    case 'get_motorcycle_transfer_log':
        get_motorcycle_transfer_log();
        break;    
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
    case 'get_redeemed_units_report':
        getRedeemedUnitsReport();
        break;    
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

    $sql .= ' GROUP BY model, brand, color ORDER BY total_quantity ASC';

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
    $sortOrder = 'ASC';

    if ( !empty( $sort ) ) {
        $parts = explode( '_', $sort );
        $validFields = [ 'date_delivered', 'brand', 'model', 'category', 'status', 'invoice_number', 'current_branch', 'display_invoice_number' ];

        if ( in_array( $parts[ 0 ], $validFields ) ) {
            if ($parts[0] === 'display_invoice_number') {
                $sortField = 'display_invoice_number';
            } else {
                $sortField = 'mi.' . $parts[ 0 ];
            }
            $sortOrder = strtoupper( $parts[ 1 ] ) === 'ASC' ? 'ASC' : 'ASC';
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
                  OR mi.frame_number LIKE ? OR mi.color LIKE ? OR i.invoice_number LIKE ? OR mi.initial_dr_number LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_fill( 0, 8, $searchTerm );
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

    // Modified SQL query to include display_invoice_number and transfer_count
    $sql = "SELECT mi.*, mi.date_received, i.invoice_number,
                   COALESCE(
                       (SELECT it.transfer_invoice_number 
                        FROM inventory_transfers it 
                        WHERE it.motorcycle_id = mi.id 
                        ORDER BY it.transfer_date DESC, it.id DESC 
                        LIMIT 1),
                       mi.initial_dr_number,
                       i.invoice_number
                   ) as display_invoice_number,
                   (SELECT COUNT(*) FROM inventory_transfers it WHERE it.motorcycle_id = mi.id) as transfer_count
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

    // Get ALL data including initial_dr_number
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

        // DEBUG: Log what we're getting
        error_log("DEBUG getMotorcycle ID $id - initial_dr_number: " . ($data['initial_dr_number'] ?? 'NULL'));
        error_log("DEBUG getMotorcycle ID $id - invoice_number: " . ($data['invoice_number'] ?? 'NULL'));

        // Check transfer history
        $transferStmt = $conn->prepare("SELECT COUNT(*) as transfer_count FROM inventory_transfers WHERE motorcycle_id = ?");
        $transferStmt->bind_param('i', $id);
        $transferStmt->execute();
        $transferResult = $transferStmt->get_result();
        $hasTransfers = $transferResult->fetch_assoc()['transfer_count'] > 0;
        $transferStmt->close();

        // Determine display invoice number and source
        if ($hasTransfers) {
            // Get latest transfer invoice
            $latestTransferStmt = $conn->prepare("SELECT transfer_invoice_number FROM inventory_transfers WHERE motorcycle_id = ? ORDER BY transfer_date DESC, id DESC LIMIT 1");
            $latestTransferStmt->bind_param('i', $id);
            $latestTransferStmt->execute();
            $latestTransferResult = $latestTransferStmt->get_result();
            $latestTransfer = $latestTransferResult->fetch_assoc();
            $latestTransferStmt->close();

            $data['display_invoice_number'] = $latestTransfer['transfer_invoice_number'] ?? $data['invoice_number'];
            $data['invoice_source'] = 'transfer';
        } else {
            // No transfers - use initial_dr_number or invoice_number
            $data['display_invoice_number'] = $data['initial_dr_number'] ?? $data['invoice_number'];
            $data['invoice_source'] = 'direct';
        }


        // Include sale details if requested
        if ($includeSaleDetails && isset($data['status']) && $data['status'] === 'sold') {
            $saleStmt = $conn->prepare("SELECT * FROM motorcycle_sales 
                                        WHERE motorcycle_id = ? 
                                        ORDER BY sale_date ASC LIMIT 1");
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
        else if ($includeSaleDetails && isset($data['category']) && $data['category'] === 'repo') {
            $saleStmt = $conn->prepare("SELECT * FROM motorcycle_sales_history 
                                        WHERE motorcycle_id = ? 
                                        ORDER BY archived_at ASC LIMIT 1");
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

        // Get transfer history
        if (isset($data['status']) && $data['status'] === 'transferred') {
            $transferHistoryStmt = $conn->prepare("SELECT * FROM inventory_transfers 
                                                   WHERE motorcycle_id = ? 
                                                   ORDER BY transfer_date ASC");
            if ($transferHistoryStmt) {
                $transferHistoryStmt->bind_param('i', $id);
                $transferHistoryStmt->execute();
                $transferHistoryResult = $transferHistoryStmt->get_result();

                $transfers = [];
                if ($transferHistoryResult) {
                    while ($row = $transferHistoryResult->fetch_assoc()) {
                        $transfers[] = $row;
                    }
                }
                $data['transfer_history'] = $transfers;
                $transferHistoryStmt->close();
            } else {
                $data['transfer_history'] = [];
            }
        }

        // Include redeem details if requested
        if ($includeSaleDetails) {
            $redeemStmt = $conn->prepare("SELECT * FROM motorcycle_redeems 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY redeem_date ASC LIMIT 1");
            if ($redeemStmt) {
                $redeemStmt->bind_param('i', $id);
                $redeemStmt->execute();
                $redeemResult = $redeemStmt->get_result();
                if ($redeemResult && $redeemResult->num_rows > 0) {
                    $data['redeem_details'] = $redeemResult->fetch_assoc();
                } else {
                    $data['redeem_details'] = null;
                }
                $redeemStmt->close();
            }
        }

        echo json_encode([
            'success' => true, 
            'data' => $data,
            'debug' => [ // Remove this in production
                'has_transfers' => $hasTransfers,
                'initial_dr_number' => $data['initial_dr_number'] ?? 'NULL',
                'invoice_number' => $data['invoice_number'] ?? 'NULL'
            ]
        ]);
    } else {
        if ($stmt) $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found']);
    }
}


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
                
                $existingInvoice = $existingInvoiceResult->fetch_assoc();
                $invoiceId = $existingInvoice['id'];
                $isExistingInvoice = true;
                
                error_log("INFO: Using existing invoice ID $invoiceId for invoice number: $invoiceNumber");
            } else {
                
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

                        
                        $stmt = $conn->prepare( "INSERT INTO motorcycle_inventory 
                                               (date_delivered, brand, model, category, engine_number, frame_number, invoice_id, color, inventory_cost, current_branch, status) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')" );

                        if ( !$stmt ) {
                            throw new Exception( 'Error preparing motorcycle insert: ' . $conn->error );
                        }

                        $stmt->bind_param( 'ssssssisds', $dateDelivered, $brand, $modelName, $category, $engineNumber, $frameNumber, $invoiceId, $color, $inventory_cost, $branch );

                        if ( $stmt->execute() ) {
                            $successCount++;
                        $new_motorcycle_id = $conn->insert_id; 
    $log_details = "Added new motorcycle: Brand={$brand}, Model={$modelName}, Engine#={$engineNumber}. Delivered to {$branch}.";
    log_action($conn, 'CREATE', 'motorcycle_inventory', $new_motorcycle_id, $log_details);
} else {
    throw new Exception('Error executing motorcycle insert: ' . $stmt->error);
}
                    }
                } else {
                    throw new Exception( 'No details found for model ' . $modelIndex );
                }
            }

            $conn->commit();
            
            
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
            
            
            $errorMessage = $e->getMessage();
            error_log("ERROR in addMotorcycle(): " . $errorMessage);
            
            
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

    $required = ['id', 'date_delivered', 'brand', 'model', 'category', 'engine_number', 'frame_number', 'color', 'current_branch', 'status'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $id = intval($_POST['id']);
    $dateReceived = sanitizeInput($_POST['date_received']);
    $dateDelivered = sanitizeInput($_POST['date_delivered']);
    $brand = sanitizeInput($_POST['brand']);
    $model = sanitizeInput($_POST['model']);
    $category = sanitizeInput($_POST['category']);
    $engineNumber = sanitizeInput($_POST['engine_number']);
    $frameNumber = sanitizeInput($_POST['frame_number']);
    $invoiceNumber = isset($_POST['invoice_number']) ? sanitizeInput($_POST['invoice_number']) : '';
    $color = sanitizeInput($_POST['color']);
    $inventory_cost = !empty($_POST['inventory_cost']) ? floatval($_POST['inventory_cost']) : null;
    $currentBranch = sanitizeInput($_POST['current_branch']);
    $status = sanitizeInput($_POST['status']);

    // Sale details
    $sale_date = isset($_POST['sale_date']) ? sanitizeInput($_POST['sale_date']) : null;
    $customer_name = isset($_POST['customer_name']) ? sanitizeInput($_POST['customer_name']) : null;
    $payment_type = isset($_POST['payment_type']) ? sanitizeInput($_POST['payment_type']) : null;
    $dr_number = isset($_POST['dr_number']) ? sanitizeInput($_POST['dr_number']) : null;
    $cod_amount = isset($_POST['cod_amount']) ? floatval($_POST['cod_amount']) : null;
    $terms = isset($_POST['terms']) ? intval($_POST['terms']) : null;
    $monthly_amortization = isset($_POST['monthly_amortization']) ? floatval($_POST['monthly_amortization']) : null;

    $conn->begin_transaction();

    try {
        // Check for duplicate engine and frame numbers
        $engineCheckStmt = $conn->prepare("SELECT id FROM motorcycle_inventory WHERE engine_number = ? AND id != ?");
        $engineCheckStmt->bind_param('si', $engineNumber, $id);
        $engineCheckStmt->execute();
        $engineCheckResult = $engineCheckStmt->get_result();
        if ($engineCheckResult->num_rows > 0) {
            $duplicateRow = $engineCheckResult->fetch_assoc();
            throw new Exception("DUPLICATE_ENGINE_NUMBER: Engine number '$engineNumber' already exists in another motorcycle (ID: " . $duplicateRow['id'] . ")");
        }

        $frameCheckStmt = $conn->prepare("SELECT id FROM motorcycle_inventory WHERE frame_number = ? AND id != ?");
        $frameCheckStmt->bind_param('si', $frameNumber, $id);
        $frameCheckStmt->execute();
        $frameCheckResult = $frameCheckStmt->get_result();
        if ($frameCheckResult->num_rows > 0) {
            $duplicateRow = $frameCheckResult->fetch_assoc();
            throw new Exception("DUPLICATE_FRAME_NUMBER: Frame number '$frameNumber' already exists in another motorcycle (ID: " . $duplicateRow['id'] . ")");
        }

        // Check if unit has transfer history and get the LATEST transfer
        $transferCheckStmt = $conn->prepare("SELECT id, transfer_invoice_number FROM inventory_transfers WHERE motorcycle_id = ? ORDER BY transfer_date DESC, id DESC LIMIT 1");
        $transferCheckStmt->bind_param('i', $id);
        $transferCheckStmt->execute();
        $transferResult = $transferCheckStmt->get_result();
        $hasTransfers = $transferResult->num_rows > 0;
        $latestTransfer = $hasTransfers ? $transferResult->fetch_assoc() : null;
        $transferCheckStmt->close();

        $invoiceId = null;
        $invoiceMessage = "";

        // Handle invoice number for BOTH scenarios
        if (!empty($invoiceNumber)) {
            // Check if invoice already exists
            $checkInvoiceStmt = $conn->prepare('SELECT id FROM invoices WHERE invoice_number = ?');
            $checkInvoiceStmt->bind_param('s', $invoiceNumber);
            $checkInvoiceStmt->execute();
            $existingInvoiceResult = $checkInvoiceStmt->get_result();

            if ($existingInvoiceResult->num_rows > 0) {
                // Link to existing invoice
                $existingInvoice = $existingInvoiceResult->fetch_assoc();
                $invoiceId = $existingInvoice['id'];
                $invoiceMessage = " (linked to existing invoice #$invoiceNumber)";
            } else {
                // Create new invoice
                $invoiceStmt = $conn->prepare('INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)');
                $notes = "Updated motorcycle record - ID: $id";
                $invoiceStmt->bind_param('sss', $invoiceNumber, $dateDelivered, $notes);
                if (!$invoiceStmt->execute()) {
                    throw new Exception('Error creating new invoice: ' . $invoiceStmt->error);
                }
                $invoiceId = $conn->insert_id;
                $invoiceMessage = " (created new invoice #$invoiceNumber)";
            }
            $checkInvoiceStmt->close();

            // SCENARIO 1: Direct shipment (no transfers) - update initial_dr_number AND invoice_id
            if (!$hasTransfers) {
                // Update initial_dr_number in motorcycle_inventory
                $updateInitialDRStmt = $conn->prepare("UPDATE motorcycle_inventory SET initial_dr_number = ? WHERE id = ?");
                $updateInitialDRStmt->bind_param('si', $invoiceNumber, $id);
                if (!$updateInitialDRStmt->execute()) {
                    throw new Exception('Error updating initial DR number: ' . $updateInitialDRStmt->error);
                }
                $updateInitialDRStmt->close();
            }
            
            // SCENARIO 2: Transferred unit - update ONLY the LATEST transfer_invoice_number
            if ($hasTransfers && $latestTransfer) {
                $updateTransferStmt = $conn->prepare("UPDATE inventory_transfers SET transfer_invoice_number = ? WHERE id = ?");
                $updateTransferStmt->bind_param('si', $invoiceNumber, $latestTransfer['id']);
                if (!$updateTransferStmt->execute()) {
                    throw new Exception('Error updating transfer invoice number: ' . $updateTransferStmt->error);
                }
                $updateTransferStmt->close();
                
                // Log the specific transfer that was updated
                $previousInvoice = $latestTransfer['transfer_invoice_number'] ?: 'None';
                error_log("INFO: Updated transfer ID {$latestTransfer['id']} invoice from '{$previousInvoice}' to '{$invoiceNumber}' for motorcycle ID {$id}");
            }
        }

        // Update motorcycle inventory with invoice_id (for BOTH scenarios)
        if ($invoiceId) {
            $stmt = $conn->prepare("UPDATE motorcycle_inventory 
                                   SET date_delivered = ?, date_received = ?, brand = ?, model = ?, category = ?, engine_number = ?, 
                                       frame_number = ?, color = ?, inventory_cost = ?, current_branch = ?, status = ?, invoice_id = ?
                                   WHERE id = ?");
            $stmt->bind_param('ssssssssdssii', $dateDelivered, $dateReceived, $brand, $model, $category, $engineNumber,
                              $frameNumber, $color, $inventory_cost, $currentBranch, $status, $invoiceId, $id);
        } else {
            $stmt = $conn->prepare("UPDATE motorcycle_inventory 
                                   SET date_delivered = ?, date_received = ?, brand = ?, model = ?, category = ?, engine_number = ?, 
                                       frame_number = ?, color = ?, inventory_cost = ?, current_branch = ?, status = ?
                                   WHERE id = ?");
            $stmt->bind_param('ssssssssdssi', $dateDelivered, $dateReceived, $brand, $model, $category, $engineNumber,
                              $frameNumber, $color, $inventory_cost, $currentBranch, $status, $id);
        }

        if (!$stmt->execute()) {
            throw new Exception('Error updating motorcycle: ' . $stmt->error);
        }

        // Handle sale details
        if ($status === 'sold') {
            $checkSaleStmt = $conn->prepare("SELECT id FROM motorcycle_sales WHERE motorcycle_id = ? ORDER BY sale_date ASC LIMIT 1");
            $checkSaleStmt->bind_param('i', $id);
            $checkSaleStmt->execute();
            $saleResult = $checkSaleStmt->get_result();

            if ($saleResult->num_rows > 0) {
                $saleRow = $saleResult->fetch_assoc();
                $updateSaleStmt = $conn->prepare("UPDATE motorcycle_sales SET sale_date = ?, customer_name = ?, payment_type = ?, dr_number = ?, cod_amount = ?, terms = ?, monthly_amortization = ? WHERE id = ?");
                $updateSaleStmt->bind_param('ssssdidi', $sale_date, $customer_name, $payment_type, $dr_number, $cod_amount, $terms, $monthly_amortization, $saleRow['id']);
                if (!$updateSaleStmt->execute()) {
                    throw new Exception('Error updating sale details: ' . $updateSaleStmt->error);
                }
            } else {
                $insertSaleStmt = $conn->prepare("INSERT INTO motorcycle_sales (motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertSaleStmt->bind_param('issssdid', $id, $sale_date, $customer_name, $payment_type, $dr_number, $cod_amount, $terms, $monthly_amortization);
                if (!$insertSaleStmt->execute()) {
                    throw new Exception('Error inserting sale details: ' . $insertSaleStmt->error);
                }
            }
        } else {
            $deleteSaleStmt = $conn->prepare("DELETE FROM motorcycle_sales WHERE motorcycle_id = ?");
            $deleteSaleStmt->bind_param('i', $id);
            $deleteSaleStmt->execute();
        }

        $conn->commit();

        $log_details = "Updated motorcycle ID: {$id}. Status set to '{$status}', Branch set to '{$currentBranch}'.";
        if ($status === 'sold' && !empty($customer_name)) {
            $log_details .= " Sold to: {$customer_name}.";
        }
        if ($hasTransfers && $latestTransfer) {
            $log_details .= " Updated latest transfer (ID: {$latestTransfer['id']}) invoice to: {$invoiceNumber}.";
        } else if (!$hasTransfers) {
            $log_details .= " Updated initial DR number to: {$invoiceNumber}.";
        }
        log_action($conn, 'UPDATE', 'motorcycle_inventory', $id, $log_details);

        // Return success message
        if (!empty($invoiceNumber)) {
            if ($hasTransfers) {
                echo json_encode([ 
                    'success' => true, 
                    'message' => "Motorcycle updated successfully. Latest transfer invoice updated$invoiceMessage",
                    'type' => 'transfer_updated',
                    'transfer_id' => $latestTransfer ? $latestTransfer['id'] : null
                ]);
            } else {
                echo json_encode([ 
                    'success' => true, 
                    'message' => "Motorcycle updated successfully. Initial DR number updated$invoiceMessage",
                    'type' => 'direct_updated'
                ]);
            }
        } else {
            echo json_encode([ 
                'success' => true, 
                'message' => 'Motorcycle updated successfully'
            ]);
        }

    } catch (Exception $e) {
        $conn->rollback();

        $errorMessage = $e->getMessage();
        error_log("ERROR in updateMotorcycle(): " . $errorMessage);

        if (strpos($errorMessage, 'DUPLICATE_ENGINE_NUMBER') !== false || 
            strpos($errorMessage, 'DUPLICATE_FRAME_NUMBER') !== false) {
            echo json_encode(['success' => false, 'message' => $errorMessage]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating motorcycle. Please check console for details.']);
        }
    }
}
function deleteMotorcycle() {
    global $conn;

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    
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
        
        $deleteTransfers = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id = ?");
        $deleteTransfers->bind_param('i', $id);
        $deleteTransfers->execute();
        
        
        $stmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        
        $checkInvoiceStmt = $conn->prepare("SELECT COUNT(*) as remaining FROM motorcycle_inventory WHERE invoice_id = ?");
        $checkInvoiceStmt->bind_param('i', $invoiceId);
        $checkInvoiceStmt->execute();
        $checkResult = $checkInvoiceStmt->get_result();
        $remaining = $checkResult->fetch_assoc()['remaining'];
        
        
        if ($remaining == 0) {
            $deleteInvoiceStmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
            $deleteInvoiceStmt->bind_param('i', $invoiceId);
            $deleteInvoiceStmt->execute();
        }

        $conn->commit();

         log_action($conn, 'DELETE', 'motorcycle_inventory', $id, $log_details);

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
        
        $deleteTransfers = $conn->prepare("DELETE FROM inventory_transfers WHERE motorcycle_id IN ($placeholders)");
        $deleteTransfers->bind_param($types, ...$sanitizedIds);
        $deleteTransfers->execute();
        
        
        $stmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$sanitizedIds);
        $stmt->execute();

        $affectedRows = $stmt->affected_rows;
        
        
        foreach ($affectedInvoices as $invoiceId) {
            $checkInvoiceStmt = $conn->prepare("SELECT COUNT(*) as remaining FROM motorcycle_inventory WHERE invoice_id = ?");
            $checkInvoiceStmt->bind_param('i', $invoiceId);
            $checkInvoiceStmt->execute();
            $checkResult = $checkInvoiceStmt->get_result();
            $remaining = $checkResult->fetch_assoc()['remaining'];
            
            
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
        
        $scrapStmt = $conn->prepare("INSERT INTO motorcycle_scraps 
                                   (motorcycle_id, scrap_date, scrap_reason) 
                                   VALUES (?, ?, ?)");
        $scrapStmt->bind_param('iss', $motorcycleId, $scrapDate, $scrapReason);
        if (!$scrapStmt->execute()) {
            throw new Exception("Failed to insert scrap record: " . $scrapStmt->error);
        }

        
        $updateStmt = $conn->prepare("UPDATE motorcycle_inventory 
                                    SET status = 'scrapped' 
                                    WHERE id = ?");
        $updateStmt->bind_param('i', $motorcycleId);
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update motorcycle status: " . $updateStmt->error);
        }

        $conn->commit();
        $log_details = "Marked motorcycle ID {$motorcycleId} as SCRAPPED. Reason: {$scrapReason}.";
log_action($conn, 'UPDATE_STATUS', 'motorcycle_inventory', $motorcycleId, $log_details);


        
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




function sellMotorcycle() {
    global $conn;

    
    $required = ['motorcycle_id', 'sale_date', 'customer_name', 'payment_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    
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

    
    $today = date('Y-m-d');
    if ($saleDate > $today) {
        echo json_encode(['success' => false, 'message' => 'Error: The sale date cannot be in the future.']);
        return;
    }

    
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

    
    $conn->begin_transaction();
    try {
        $saleStmt = $conn->prepare("INSERT INTO motorcycle_sales (motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $saleStmt->bind_param('issssdid', $motorcycleId, $saleDate, $customerName, $paymentType, $drNumber, $codAmount, $terms, $monthlyAmortization);
        $saleStmt->execute();

        $updateStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'sold' WHERE id = ?");
        $updateStmt->bind_param('i', $motorcycleId);
        $updateStmt->execute();

        $conn->commit();
        $log_details = "Marked motorcycle ID {$motorcycleId} as SOLD to customer '{$customerName}'. Payment: {$paymentType}.";
log_action($conn, 'UPDATE_STATUS', 'motorcycle_inventory', $motorcycleId, $log_details);

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
            ORDER BY i.date_delivered ASC
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



function getMotorcycleTransfers() {
    global $conn;

    $id = isset( $_GET[ 'id' ] ) ? intval( $_GET[ 'id' ] ) : 0;

    $stmt = $conn->prepare( "SELECT it.*, u.username as transferred_by_name 
                          FROM inventory_transfers it
                          LEFT JOIN users u ON it.transferred_by = u.id
                          WHERE motorcycle_id = ? 
                          ORDER BY transfer_date ASC" );
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
                           ORDER BY transfer_date ASC" );
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

    $sql .= " ORDER BY it.transfer_date ASC LIMIT ? OFFSET ?";

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

    
    $required = [ 'motorcycle_ids', 'from_branch', 'to_branch', 'transfer_date', 'inventory_costs', 'transfer_invoice_number' ];
    foreach ( $required as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            echo json_encode( [ 'success' => false, 'message' => "Missing required field: $field" ] );
            return;
        }
    }

    
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

    
    

    $placeholders = implode( ',', array_fill( 0, count( $motorcycleIds ), '?' ) );
    $types = str_repeat( 'i', count( $motorcycleIds ) );

    
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
        
        $motorcycleDetails = [];
        $getDetailsStmt = $conn->prepare("SELECT id, brand, model, color, engine_number, frame_number, inventory_cost
                                           FROM motorcycle_inventory WHERE id IN ($placeholders)");
        $getDetailsStmt->bind_param($types, ...$motorcycleIds);
        $getDetailsStmt->execute();
        $detailsResult = $getDetailsStmt->get_result();
        
        while ($row = $detailsResult->fetch_assoc()) {
            $motorcycleDetails[] = $row;
        }

        
        $updateStmt = $conn->prepare( "UPDATE motorcycle_inventory
                                       SET status = 'transferred', inventory_cost = ?
                                       WHERE id = ?" );

        foreach ( $motorcycleIds as $index => $id ) {
            $inventoryCost = $inventoryCosts[$index] ?? null;
            $updateStmt->bind_param( 'di', $inventoryCost, $id );
            $updateStmt->execute();
        }

        
        $transferIds = [];
        $transferStmt = $conn->prepare( "INSERT INTO inventory_transfers
                                      (motorcycle_id, from_branch, to_branch, transfer_date, transferred_by, notes, transfer_status, transfer_invoice_number)
                                      VALUES (?, ?, ?, ?, ?, ?, 'in-transit', ?)" );

       foreach ( $motorcycleIds as $id ) {
    $transferStmt->bind_param( 'isssiss', $id, $fromBranch, $toBranch, $transferDate, $transferredBy, $notes, $transferInvoiceNumber );
    $transferStmt->execute();
    
    
    $current_transfer_id = $conn->insert_id; 
    
    
    $transferIds[] = $current_transfer_id;

    $log_details = "Initiated transfer of Motorcycle ID {$id} from {$fromBranch} to {$toBranch}. Transfer Invoice#: {$transferInvoiceNumber}.";
    
    
    log_action($conn, 'TRANSFER_INITIATE', 'inventory_transfers', $current_transfer_id, $log_details);
}

        $conn->commit();
        
        $totalCost = array_sum($inventoryCosts);
        
        
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
    
    

    $conn->begin_transaction();

    try {
        
        $getTransfersStmt = $conn->prepare("SELECT id, motorcycle_id, to_branch, transfer_invoice_number, transfer_date 
                                             FROM inventory_transfers 
                                             WHERE id IN ($placeholders) AND transfer_status = 'in-transit'");
        $getTransfersStmt->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        $getTransfersStmt->execute();
        $transfersResult = $getTransfersStmt->get_result();

        $transfersToProcess = [];
        while ($row = $transfersResult->fetch_assoc()) {
            
            if ($row['to_branch'] !== $currentBranch) {
                throw new Exception('Transfer ID ' . $row['id'] . ' is not destined for the current branch.');
            }
            $transfersToProcess[] = $row;
        }

        if (empty($transfersToProcess)) {
            throw new Exception('No valid in-transit transfers found for your branch with the provided IDs.');
        }

        
        $checkColumnQuery = "SHOW COLUMNS FROM motorcycle_inventory LIKE 'date_received'";
        $columnResult = $conn->query($checkColumnQuery);
        $hasDateReceivedColumn = $columnResult->num_rows > 0;

        
        $updateTransferStmt = $conn->prepare("UPDATE inventory_transfers SET transfer_status = 'completed', date_received = ? WHERE id = ?");
        $selectInvoiceStmt = $conn->prepare("SELECT id FROM invoices WHERE invoice_number = ?");
        $insertInvoiceStmt = $conn->prepare("INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)");

        if ($hasDateReceivedColumn) {
            $updateMotorcycleStmt = $conn->prepare("UPDATE motorcycle_inventory SET current_branch = ?, status = 'available', date_received = ?, invoice_id = ? WHERE id = ?");
        } else {
            $updateMotorcycleStmt = $conn->prepare("UPDATE motorcycle_inventory SET current_branch = ?, status = 'available', invoice_id = ? WHERE id = ?");
        }

        
        
        foreach ($transfersToProcess as $transfer) {
            $transferDate = $transfer['transfer_date']; 
            $transferInvoiceNumber = $transfer['transfer_invoice_number'];

            
            $updateTransferStmt->bind_param('si', $transferDate, $transfer['id']);
            if (!$updateTransferStmt->execute()) {
                throw new Exception('Failed to update transfer status for transfer ID ' . $transfer['id'] . ': ' . $updateTransferStmt->error);
            }

            
            $invoiceId = null;
            $selectInvoiceStmt->bind_param('s', $transferInvoiceNumber);
            $selectInvoiceStmt->execute();
            $invoiceResult = $selectInvoiceStmt->get_result();
            
            if ($invoiceRow = $invoiceResult->fetch_assoc()) {
                $invoiceId = $invoiceRow['id'];
            } else {
                $notes = "Auto-created from transfer invoice " . $transferInvoiceNumber;
                $insertInvoiceStmt->bind_param('sss', $transferInvoiceNumber, $transferDate, $notes);
                if (!$insertInvoiceStmt->execute()) {
                    throw new Exception('Failed to create invoice: ' . $insertInvoiceStmt->error);
                }
                $invoiceId = $conn->insert_id;
            }

            
            if ($hasDateReceivedColumn) {
                
                $updateMotorcycleStmt->bind_param('ssii', $transfer['to_branch'], $transferDate, $invoiceId, $transfer['motorcycle_id']);
            } else {
                $updateMotorcycleStmt->bind_param('sii', $transfer['to_branch'], $invoiceId, $transfer['motorcycle_id']);
            }

            if (!$updateMotorcycleStmt->execute()) {
                throw new Exception('Failed to update motorcycle inventory for ID ' . $transfer['motorcycle_id'] . ': ' . $updateMotorcycleStmt->error);
            }
            
        }
        
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Successfully accepted ' . count($transfersToProcess) . ' transfer(s). Motorcycles are now available at your branch.',
            'accepted_count' => count($transfersToProcess),
            
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error accepting transfers: ' . $e->getMessage(),
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

    
    $transferIds = array_map('intval', $transferIds);
    $placeholders = implode(',', array_fill(0, count($transferIds), '?'));

    $conn->begin_transaction();

    try {
        
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

        
        $updateTransfers = $conn->prepare("UPDATE inventory_transfers 
                                         SET transfer_status = 'rejected'
                                         WHERE id IN ($placeholders)");
        $updateTransfers->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        
        if (!$updateTransfers->execute()) {
            throw new Exception('Failed to update transfer status: ' . $updateTransfers->error);
        }

        
        foreach ($motorcycleUpdates as $update) {
            $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                              SET status = 'available', current_branch = ?
                                              WHERE id = ?");
            $updateMotorcycle->bind_param('si', $update['from_branch'], $update['motorcycle_id']);
            
            if (!$updateMotorcycle->execute()) {
                throw new Exception('Failed to update motorcycle status: ' . $updateMotorcycle->error);
            }
        }

        
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
    $sortOrder = isset( $_GET[ 'order' ] ) && strtoupper( $_GET[ 'order' ] ) === 'ASC' ? 'ASC' : 'ASC';

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
                                          ORDER BY transfer_date ASC LIMIT 1" );
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

    
    
    $sql = "SELECT 
                mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
                mi.inventory_cost, mi.current_branch, mi.status, i.invoice_number,
                ms.sale_date, ms.customer_name, ms.payment_type, ms.dr_number, ms.cod_amount, ms.terms, ms.monthly_amortization,
                mr.redeem_date, mr.amount_paid 
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            LEFT JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            LEFT JOIN motorcycle_redeems mr ON mi.id = mr.motorcycle_id
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
        $bind_names[] = $types;
        for ($i=0; $i<count($params); $i++) {
            $bind_name = 'bind' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
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
       
        if (isset($row['redeem_date']) && $row['redeem_date'] !== null) {
            $row['redeem_details'] = [
                'redeem_date' => $row['redeem_date'],
                'amount_paid' => $row['amount_paid']
            ];
        } else {
            $row['redeem_details'] = null;
        }

        unset($row['redeem_date']);
        unset($row['amount_paid']);

        $data[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data]);
}


function searchInventoryByEngine() {
    global $conn;

    if (!isset($_SESSION['user_branch'])) {
        echo json_encode(['success' => false, 'message' => 'User branch not set']);
        return;
    }

    error_log("searchInventoryByEngine called with params: " . print_r($_GET, true));

    $userBranch = $_SESSION['user_branch'];
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $field = isset($_GET['field']) ? sanitizeInput($_GET['field']) : 'all';
    $includeInventoryCost = isset($_GET['include_inventory_cost']) && $_GET['include_inventory_cost'] === 'true';
    $fuzzySearch = isset($_GET['fuzzy_search']) && $_GET['fuzzy_search'] === 'true';
    $statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'available';

    $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
                   mi.inventory_cost, mi.current_branch, mi.status, i.invoice_number,
                   ms.sale_date
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            LEFT JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            WHERE mi.current_branch = ? AND mi.status = ?";

    $params = [$userBranch, $statusFilter];
    $types = 'ss';

    if (!empty($query)) {
        if ($field === 'engine_number') {
            if ($fuzzySearch) {
                $sql .= ' AND (mi.engine_number LIKE ? OR mi.engine_number LIKE ? OR mi.engine_number LIKE ?)';
                $searchTerm1 = "%$query%";
                $searchTerm2 = "$query%";
                $searchTerm3 = "%$query";
                $params = array_merge($params, [$searchTerm1, $searchTerm2, $searchTerm3]);
                $types .= 'sss';
            } else {
                $sql .= ' AND mi.engine_number LIKE ?';
                $searchTerm = "%$query%";
                $params[] = $searchTerm;
                $types .= 's';
            }
        } else {
            $sql .= " AND (mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? 
                           OR mi.frame_number LIKE ? OR i.invoice_number LIKE ?)";
            $searchTerm = "%$query%";
            $additionalParams = array_fill(0, 5, $searchTerm);
            $params = array_merge($params, $additionalParams);
            $types .= 'sssss';
        }
    }

    $sql .= ' ORDER BY mi.brand, mi.model LIMIT 20';

    error_log("Final SQL: " . $sql);
    error_log("Params: " . print_r($params, true));
    error_log("Types: " . $types);

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        error_log("Query preparation failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $conn->error]);
    }
}


function searchTransferReceipt() {
    global $conn;
    
    $transferInvoiceNumber = isset($_GET['transfer_invoice_number']) ? sanitizeInput($_GET['transfer_invoice_number']) : '';
    
    if (empty($transferInvoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Transfer invoice number is required']);
        return;
    }
    
    
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
            ORDER BY it.transfer_date ASC
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

    // Get parameters
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $asOfDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // Validation
    if (empty($month) && empty($asOfDate)) {
        echo json_encode(['success' => false, 'message' => 'A Month or As-of Date parameter is required.']);
        return;
    }

    // Set date ranges
    if (!empty($asOfDate)) {
        $endDate = $asOfDate;
        $startDate = date('Y-m-01', strtotime($asOfDate));
        $prevMonthEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $reportMonth = date('Y-m', strtotime($asOfDate));
    } else {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
        $prevMonthEnd = date('Y-m-d', strtotime('last day of previous month', strtotime($month)));
        $reportMonth = $month;
    }

    // Build conditions
    $userBranch = isset($_SESSION['user_branch']) ? strtoupper($_SESSION['user_branch']) : '';
    $applyBrandFilter = ($userBranch === 'HEADOFFICE' && $brand !== 'all');

    // brandCondition: keep it as a placeholder-based condition when applicable
    $brandCondition = '';
    $params = [];
    $paramTypes = '';
    if ($applyBrandFilter) {
        $brandCondition = " AND LOWER(mi.brand) = ? ";
        $params[] = $brand;
    }

    $categoryCondition = '';
    $modelCondition = ''; 

    if ($category !== 'all') {
        $categoryCondition = " AND LOWER(mi.category) = ? ";
        $params[] = $category;
    }

    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $modelCondition = " AND mi.model IN ($modelPlaceholders) ";
            foreach ($models as $model) {
                $params[] = $model;
            }
        }
    }

    // Robust bindParams that builds the types string automatically and binds by reference
    function bindParams(&$stmt, $paramsArray) {
        if (empty($paramsArray)) return;
        $types = str_repeat('s', count($paramsArray));
        // mysqli requires references for bind_param when using call_user_func_array
        $refs = [];
        foreach ($paramsArray as $key => $value) {
            $refs[$key] = &$paramsArray[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    // === MAIN QUERY: get units that should exist as of the report end date ===
    // Note: ensure number/order of placeholders in SQL matches the $paramsMain array below
    $sqlMain = "
    SELECT 
        mi.*,
        i.invoice_number,
        -- Determine where the unit was as of report end date:
        COALESCE(
            (SELECT it.to_branch 
             FROM inventory_transfers it 
             WHERE it.motorcycle_id = mi.id 
               AND it.transfer_status = 'completed' 
               AND it.transfer_date <= ?
             ORDER BY it.transfer_date DESC 
             LIMIT 1),
            -- If none before cutoff, fall back to the earliest recorded from_branch (original location),
            (SELECT it2.from_branch
             FROM inventory_transfers it2
             WHERE it2.motorcycle_id = mi.id
               AND it2.transfer_status = 'completed'
             ORDER BY it2.transfer_date ASC
             LIMIT 1),
            -- Last fallback: the stored current_branch (if neither transfer subquery returns)
            mi.current_branch
        ) AS branch_as_of_report_date,
        mi.date_delivered as original_delivery_date
    FROM motorcycle_inventory mi
    LEFT JOIN invoices i ON mi.invoice_id = i.id
    WHERE mi.deleted_at IS NULL
        -- Unit was delivered before or on report end date
        AND mi.date_delivered <= ?
        -- Unit was NOT sold before or on report end date
        AND NOT EXISTS (
            SELECT 1 FROM motorcycle_sales s 
            WHERE s.motorcycle_id = mi.id 
              AND s.sale_date <= ?
        )
        -- Unit was NOT scrapped before or on report end date
        AND NOT EXISTS (
            SELECT 1 FROM motorcycle_scraps sc 
            WHERE sc.motorcycle_id = mi.id 
              AND sc.scrap_date <= ?
        )
        $brandCondition
        $categoryCondition
        $modelCondition
    ";

    $stmtMain = $conn->prepare($sqlMain);
    if ($stmtMain === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (main): ' . $conn->error]);
        return;
    }

    // params: subquery cutoff (1), delivered cutoff, sale cutoff, scrap cutoff => 4 x $endDate, then brand/category/models ($params)
    $paramsMain = array_merge([$endDate, $endDate, $endDate, $endDate], $params);
    bindParams($stmtMain, $paramsMain);

    $stmtMain->execute();
    $resultMain = $stmtMain->get_result();

    // Process results and filter by branch if needed
    $allUnits = [];
    $data = [];
    $countEndingActual = 0;
    $costEndingActual = 0;

    while ($row = $resultMain->fetch_assoc()) {
        $effectiveBranch = $row['branch_as_of_report_date'] ?? $row['current_branch'];
        $allUnits[] = [
            'row' => $row,
            'effective_branch' => $effectiveBranch
        ];

        // Apply branch filter
        if (strtoupper($branch) === 'ALL' || strtoupper($effectiveBranch) === strtoupper($branch)) {
            $countEndingActual++;
            $costEndingActual += (float)$row['inventory_cost'];

            $item = [
                'id' => (int)$row['id'],
                'brand' => $row['brand'],
                'model' => $row['model'],
                'color' => $row['color'],
                'engine_number' => $row['engine_number'],
                'frame_number' => $row['frame_number'],
                'inventory_cost' => (float)$row['inventory_cost'],
                'current_branch' => $effectiveBranch,
                'status' => $row['status'],
                'date_delivered' => $row['date_delivered'],
                'invoice_number' => $row['invoice_number'],
                'category' => $row['category'],
                'branch_at_cutoff' => $effectiveBranch,
                'record_type' => 'inventory',
                'original_delivery_date' => $row['original_delivery_date']
            ];

            $data[] = $item;
        }
    }

    // === CALCULATE BEGINNING BALANCE ===
    $countBeginning = 0;
    $costBeginning = 0;

    $sqlBeginning = "
    SELECT COUNT(*) as count_beginning, COALESCE(SUM(mi.inventory_cost), 0) as cost_beginning
    FROM motorcycle_inventory mi
    WHERE mi.deleted_at IS NULL
        AND mi.date_delivered <= ?
        AND NOT EXISTS (SELECT 1 FROM motorcycle_sales s WHERE s.motorcycle_id = mi.id AND s.sale_date <= ?)
        AND NOT EXISTS (SELECT 1 FROM motorcycle_scraps sc WHERE sc.motorcycle_id = mi.id AND sc.scrap_date <= ?)
        $brandCondition
        $categoryCondition
        $modelCondition
    ";

    $stmtBeginning = $conn->prepare($sqlBeginning);
    if ($stmtBeginning === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (beginning): ' . $conn->error]);
        return;
    }

    $paramsBeginning = array_merge([$prevMonthEnd, $prevMonthEnd, $prevMonthEnd], $params);
    bindParams($stmtBeginning, $paramsBeginning);
    $stmtBeginning->execute();
    $beginningResult = $stmtBeginning->get_result()->fetch_assoc();
    $countBeginning = (int)($beginningResult['count_beginning'] ?? 0);
    $costBeginning = (float)($beginningResult['cost_beginning'] ?? 0);

    // === NEW DELIVERIES DURING MONTH ===
    $countNewDeliveries = 0;
    $costNewDeliveries = 0;

    $sqlNewDeliveries = "
    SELECT COUNT(*) as count_new, COALESCE(SUM(mi.inventory_cost), 0) as cost_new
    FROM motorcycle_inventory mi
    WHERE mi.deleted_at IS NULL
        AND mi.date_delivered BETWEEN ? AND ?
        $brandCondition
        $categoryCondition
        $modelCondition
    ";

    $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
    if ($stmtNewDeliveries === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed (new deliveries): ' . $conn->error]);
        return;
    }

    $paramsNew = array_merge([$startDate, $endDate], $params);
    bindParams($stmtNewDeliveries, $paramsNew);
    $stmtNewDeliveries->execute();
    $newResult = $stmtNewDeliveries->get_result()->fetch_assoc();
    $countNewDeliveries = (int)($newResult['count_new'] ?? 0);
    $costNewDeliveries = (float)($newResult['cost_new'] ?? 0);

    // === TRANSFERS OUT DURING MONTH ===
    $countTransfersOut = 0;
    $costTransfersOut = 0;

    if (strtoupper($branch) === 'ALL') {
        $sqlTransfersOut = "
        SELECT COUNT(*) as count_out, COALESCE(SUM(mi.inventory_cost), 0) as cost_out
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
        WHERE it.transfer_date BETWEEN ? AND ?
          AND it.transfer_status = 'completed'
          $brandCondition
          $categoryCondition
          $modelCondition
        ";
        $stmtTransfersOut = $conn->prepare($sqlTransfersOut);
        if ($stmtTransfersOut === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed (transfers out all): ' . $conn->error]);
            return;
        }
        $paramsOut = array_merge([$startDate, $endDate], $params);
        bindParams($stmtTransfersOut, $paramsOut);
    } else {
        $sqlTransfersOut = "
        SELECT COUNT(*) as count_out, COALESCE(SUM(mi.inventory_cost), 0) as cost_out
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
        WHERE it.from_branch = ?
          AND it.transfer_date BETWEEN ? AND ?
          AND it.transfer_status = 'completed'
          $brandCondition
          $categoryCondition
          $modelCondition
        ";
        $stmtTransfersOut = $conn->prepare($sqlTransfersOut);
        if ($stmtTransfersOut === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed (transfers out branch): ' . $conn->error]);
            return;
        }
        $paramsOut = array_merge([$branch, $startDate, $endDate], $params);
        bindParams($stmtTransfersOut, $paramsOut);
    }
    $stmtTransfersOut->execute();
    $outResult = $stmtTransfersOut->get_result()->fetch_assoc();
    $countTransfersOut = (int)($outResult['count_out'] ?? 0);
    $costTransfersOut = (float)($outResult['cost_out'] ?? 0);

    // === SALES DURING MONTH ===
    $countSold = 0;
    $costSold = 0;

    if (strtoupper($branch) === 'ALL') {
        $sqlSold = "
        SELECT COUNT(*) as count_sold, COALESCE(SUM(mi.inventory_cost), 0) as cost_sold
        FROM motorcycle_sales ms
        JOIN motorcycle_inventory mi ON ms.motorcycle_id = mi.id
        WHERE ms.sale_date BETWEEN ? AND ?
          $brandCondition
          $categoryCondition
          $modelCondition
        ";
        $stmtSold = $conn->prepare($sqlSold);
        if ($stmtSold === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed (sold all): ' . $conn->error]);
            return;
        }
        $paramsSold = array_merge([$startDate, $endDate], $params);
        bindParams($stmtSold, $paramsSold);
    } else {
        $sqlSold = "
        SELECT COUNT(*) as count_sold, COALESCE(SUM(mi.inventory_cost), 0) as cost_sold
        FROM motorcycle_sales ms
        JOIN motorcycle_inventory mi ON ms.motorcycle_id = mi.id
        WHERE mi.current_branch = ?
          AND ms.sale_date BETWEEN ? AND ?
          $brandCondition
          $categoryCondition
          $modelCondition
        ";
        $stmtSold = $conn->prepare($sqlSold);
        if ($stmtSold === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed (sold branch): ' . $conn->error]);
            return;
        }
        $paramsSold = array_merge([$branch, $startDate, $endDate], $params);
        bindParams($stmtSold, $paramsSold);
    }
    $stmtSold->execute();
    $soldResult = $stmtSold->get_result()->fetch_assoc();
    $countSold = (int)($soldResult['count_sold'] ?? 0);
    $costSold = (float)($soldResult['cost_sold'] ?? 0);

    // === CALCULATE TOTALS ===
    $countIn = $countNewDeliveries;
    $costIn = $costNewDeliveries;
    $countOut = $countTransfersOut + $countSold;
    $costOut = $costTransfersOut + $costSold;
    $countEndingCalculated = $countBeginning + $countIn - $countOut;
    $costEndingCalculated = $costBeginning + $costIn - $costOut;

    // === GET TRANSFER DETAILS FOR DISPLAY ===
    $transferDetails = [];
    if (strtoupper($branch) === 'ALL') {
        $sqlTransfers = "
        SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
        WHERE it.transfer_date BETWEEN ? AND ?
          AND it.transfer_status = 'completed'
        ORDER BY it.transfer_date DESC
        ";
        $stmtTransfers = $conn->prepare($sqlTransfers);
        if ($stmtTransfers === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed (transfers list all): ' . $conn->error]);
            return;
        }
        bindParams($stmtTransfers, [$startDate, $endDate]);
    } else {
        $sqlTransfers = "
        SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
        WHERE (it.from_branch = ? OR it.to_branch = ?)
          AND it.transfer_date BETWEEN ? AND ?
          AND it.transfer_status = 'completed'
        ORDER BY it.transfer_date DESC
        ";
        $stmtTransfers = $conn->prepare($sqlTransfers);
        if ($stmtTransfers === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed (transfers list branch): ' . $conn->error]);
            return;
        }
        bindParams($stmtTransfers, [$branch, $branch, $startDate, $endDate]);
    }
    $stmtTransfers->execute();
    $transferResult = $stmtTransfers->get_result();
    while ($row = $transferResult->fetch_assoc()) {
        $transferDetails[] = [
            'id' => (int)$row['motorcycle_id'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'engine_number' => $row['engine_number'],
            'frame_number' => $row['frame_number'],
            'inventory_cost' => (float)$row['inventory_cost'],
            'from_branch' => $row['from_branch'],
            'to_branch' => $row['to_branch'],
            'transfer_date' => $row['transfer_date'],
            'record_type' => 'transfer'
        ];
    }

    // === BUILD RESPONSE ===
    $response = [
        'success' => true,
        'data' => $data,
        'transfer_details' => $transferDetails,
        'month' => $month,
        'as_of_date' => $asOfDate,
        'branch' => $branch,
        'summary' => [
            'beginning_balance' => $countBeginning,
            'new_deliveries' => $countNewDeliveries,
            'in' => $countIn,
            'transfers_out' => $countTransfersOut,
            'sold_during_month' => $countSold,
            'out' => $countOut,
            'ending_calculated' => $countEndingCalculated,
            'ending_actual' => $countEndingActual,
            'inventory_cost' => [
                'beginning_balance' => $costBeginning,
                'new_deliveries' => $costNewDeliveries,
                'in' => $costIn,
                'transfers_out' => $costTransfersOut,
                'sold_during_month' => $costSold,
                'out' => $costOut,
                'ending_calculated' => $costEndingCalculated,
                'ending_actual' => $costEndingActual
            ]
        ],
        'inventory_cost_formatted' => [
            'beginning_balance' => number_format($costBeginning, 2),
            'new_deliveries' => number_format($costNewDeliveries, 2),
            'in' => number_format($costIn, 2),
            'transfers_out' => number_format($costTransfersOut, 2),
            'sold_during_month' => number_format($costSold, 2),
            'out' => number_format($costOut, 2),
            'ending_calculated' => number_format($costEndingCalculated, 2),
            'ending_actual' => number_format($costEndingActual, 2)
        ],
        'discrepancy' => [
            'count' => $countEndingActual - $countEndingCalculated,
            'cost' => $costEndingActual - $costEndingCalculated,
            'cost_formatted' => number_format($costEndingActual - $costEndingCalculated, 2)
        ],
        'debug_info' => [
            'query_period' => "$startDate to $endDate",
            'previous_month_end' => $prevMonthEnd,
            'total_units_found' => count($allUnits),
            'units_after_branch_filter' => count($data),
            'branch_filter_applied' => $branch
        ]
    ];

    echo json_encode($response);
}

function getMonthlyTransferredSummary() {
    global $conn;

    
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
    
    
    $branch = isset($_GET['branch']) ? strtolower(sanitizeInput($_GET['branch'])) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

   
$whereClauses = ["it.transfer_status = 'completed'"];
$params = [];
$types = '';
$modelCondition = ''; 

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
        ORDER BY it.transfer_date ASC, mi.model";

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

    
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    
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

    
    $sql = "SELECT 
                mi.model, mi.color, mi.brand, mi.engine_number, mi.frame_number, mi.inventory_cost,
                it.date_received, it.from_branch as received_from, it.to_branch as received_by, i.invoice_number
            FROM motorcycle_inventory mi
            JOIN inventory_transfers it ON mi.id = it.motorcycle_id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE $whereClause
            ORDER BY it.date_received ASC, mi.model";

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

    
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    
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

    
    $sql = "SELECT 
                mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.current_branch,
                mi.inventory_cost, mi.category, ms.scrap_date, ms.scrap_reason, i.invoice_number
            FROM motorcycle_inventory mi
            INNER JOIN motorcycle_scraps ms ON mi.id = ms.motorcycle_id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            $whereClause
            ORDER BY ms.scrap_date ASC, mi.brand, mi.model";

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
        'summary_by_brand_branch' => [], 
        'summary_by_reason' => [] 
    ]);
}
function getAvailableMotorcyclesReport()
{
    global $conn;

    // === Parameters ===
    $period_type = $_GET['period_type'] ?? 'monthly';
    $month = $_GET['month'] ?? null;
    $branch = $_GET['branch'] ?? 'ALL';
    $category = strtolower($_GET['category'] ?? 'all');
    $brand = strtolower($_GET['brand'] ?? 'all');
    $model = $_GET['model'] ?? 'all';

    // === Determine cutoff date ===
    if ($period_type === 'monthly' && !empty($month)) {
        $reportEndDate = date('Y-m-t', strtotime($month)); // Last day of month
        $reportMonth = $month;
    } else {
        $reportEndDate = date('Y-m-d');
        $reportMonth = date('Y-m', strtotime($reportEndDate));
    }

    // === Filters ===
    $filters = "";
    $params = [];
    $types = "";

    if ($category !== 'all') {
        $filters .= " AND LOWER(mi.category) = ?";
        $params[] = $category;
        $types .= 's';
    }

    if ($brand !== 'all') {
        $filters .= " AND LOWER(mi.brand) = ?";
        $params[] = $brand;
        $types .= 's';
    }

    if ($model !== 'all' && !empty($model)) {
        $filters .= " AND mi.model = ?";
        $params[] = $model;
        $types .= 's';
    }

    // === Main Query (aligned with getMonthlyInventory) ===
    $sql = "
        SELECT 
            mi.*,
            COALESCE(
                (
                    SELECT it.to_branch
                    FROM inventory_transfers it
                    WHERE it.motorcycle_id = mi.id
                      AND it.transfer_status = 'completed'
                      AND it.transfer_date <= ?
                    ORDER BY it.transfer_date DESC
                    LIMIT 1
                ),
                (
                    SELECT it2.from_branch
                    FROM inventory_transfers it2
                    WHERE it2.motorcycle_id = mi.id
                      AND it2.transfer_status = 'completed'
                    ORDER BY it2.transfer_date ASC
                    LIMIT 1
                ),
                mi.current_branch
            ) AS branch_as_of_report_date
        FROM motorcycle_inventory mi
        WHERE mi.deleted_at IS NULL
          AND mi.date_delivered <= ?
          AND NOT EXISTS (
              SELECT 1 FROM motorcycle_sales s
              WHERE s.motorcycle_id = mi.id
                AND s.sale_date <= ?
          )
          AND NOT EXISTS (
              SELECT 1 FROM motorcycle_scraps sc
              WHERE sc.motorcycle_id = mi.id
                AND sc.scrap_date <= ?
          )
          $filters
        ORDER BY mi.brand, mi.model, mi.engine_number
    ";

    // === Bind parameters ===
    $paramsFinal = array_merge([$reportEndDate, $reportEndDate, $reportEndDate, $reportEndDate], $params);
    $typesFinal = 'ssss' . $types;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        return;
    }

    $stmt->bind_param($typesFinal, ...$paramsFinal);
    $stmt->execute();
    $result = $stmt->get_result();

    // === Build list of available units ===
    $data = [];
    $total_cost = 0;
    $total_units = 0;

    while ($row = $result->fetch_assoc()) {
        $effective_branch = strtoupper($row['branch_as_of_report_date'] ?? $row['current_branch']);

        // Include only matching branch (if not "ALL")
        if (strtoupper($branch) === 'ALL' || $effective_branch === strtoupper($branch)) {
            $row['effective_branch'] = $effective_branch; // For reference only
            $data[] = $row;
            $total_units++;
            $total_cost += (float)($row['inventory_cost'] ?? 0);
        }
    }

    $stmt->close();

    // === Return JSON Response ===
    echo json_encode([
        'success' => true,
        'month' => $reportMonth,
        'end_date' => $reportEndDate,
        'branch' => $branch,
        'category' => $category,
        'brand' => $brand,
        'model' => $model,
        'total_available_units' => $total_units,
        'total_inventory_cost' => $total_cost,
        'data' => $data
    ]);
}


function getSoldMotorcyclesReport() {
    global $conn;

    
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;

    $saleType = isset($_GET['sale_type']) ? strtolower(sanitizeInput($_GET['sale_type'])) : 'all';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    
    $params = [];
    $types = '';
    $dateCondition = '';
    
    if ($date) { 
        $startDate = $date;
        $endDate = $date;
        $dateCondition = " AND ms.sale_date = ?";
        $params[] = $date;
        $types .= 's';
    } elseif ($month) { 
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
        $dateCondition = " AND ms.sale_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    } elseif ($startDate && $endDate) { 
        $dateCondition = " AND ms.sale_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    } else {
        echo json_encode(['success' => false, 'message' => 'A valid date, month, or custom date range is required.']);
        return;
    }

    
    $sqlBase = "SELECT ms.sale_date, ms.customer_name, mi.model, mi.engine_number, mi.frame_number,
                      ms.payment_type, ms.dr_number, ms.cod_amount, ms.terms, ms.monthly_amortization,
                      mi.current_branch, mi.brand, mi.category
                FROM motorcycle_sales ms
                INNER JOIN motorcycle_inventory mi ON ms.motorcycle_id = mi.id
                WHERE 1=1 ";

    $sqlBase .= $dateCondition;

    
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

    $sqlBase .= " ORDER BY ms.sale_date ASC";

    
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

    $sqlBase .= " ORDER BY ms.sale_date ASC";

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
        
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE engine_number = ? AND id != ?' );
        $stmt->bind_param( 'si', $engineNumber, $excludeId );
    } else {
        
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
        
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE frame_number = ? AND id != ?' );
        $stmt->bind_param( 'si', $frameNumber, $excludeId );
    } else {
        
        $stmt = $conn->prepare( 'SELECT id FROM motorcycle_inventory WHERE frame_number = ?' );
        $stmt->bind_param( 's', $frameNumber );
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;

    echo json_encode( [ 'exists' => $exists ] );
}





function getCurrentBranch() {
    echo json_encode( [
        'success' => true,
        'branch' => $_SESSION[ 'user_branch' ] ?? 'RXS-S'
    ] );
}

function markAsRepo() {
    global $conn;

    
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

        
        $stmt_update = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available', category = 'repo' WHERE id = ?");
        $stmt_update->bind_param('i', $motorcycleId);
        if (!$stmt_update->execute()) {
            throw new Exception("Failed to update motorcycle status.");
        }

        
        $conn->query("INSERT INTO motorcycle_sales_history (id, motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization, created_at) SELECT id, motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization, created_at FROM motorcycle_sales WHERE motorcycle_id = $motorcycleId");

      $stmt_delete_sale = $conn->prepare("DELETE FROM motorcycle_sales WHERE motorcycle_id = ?");
      $stmt_delete_sale->bind_param('i', $motorcycleId);
      if (!$stmt_delete_sale->execute()) {
          
          error_log("Could not delete sale record for repossessed motorcycle ID: " . $motorcycleId);
      }

        
        
        
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

function markAsRedeem() {
    global $conn;

    
    $required = ['motorcycle_id', 'redeem_date', 'amount_paid', 'sale_date', 'customer_name', 'payment_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    
    $motorcycleId = intval($_POST['motorcycle_id']);
    $redeemDate = sanitizeInput($_POST['redeem_date']);
    $amountPaid = floatval($_POST['amount_paid']);
    $saleDate = sanitizeInput($_POST['sale_date']);
    $customerName = sanitizeInput($_POST['customer_name']);
    $paymentType = sanitizeInput($_POST['payment_type']);
    $drNumber = isset($_POST['dr_number']) ? sanitizeInput($_POST['dr_number']) : null;
    $codAmount = isset($_POST['cod_amount']) ? floatval($_POST['cod_amount']) : null;
    $terms = isset($_POST['terms']) ? intval($_POST['terms']) : null;
    $monthlyAmortization = isset($_POST['monthly_amortization']) ? floatval($_POST['monthly_amortization']) : null;
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    
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

    $conn->begin_transaction();
    try {
        
        $stmt_check = $conn->prepare("SELECT status, category FROM motorcycle_inventory WHERE id = ? FOR UPDATE");
        $stmt_check->bind_param('i', $motorcycleId);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            throw new Exception("Motorcycle not found.");
        }

        $motorcycle = $result_check->fetch_assoc();
        if ($motorcycle['category'] !== 'repo' || $motorcycle['status'] !== 'available') {
            throw new Exception("This unit is not a repossessed unit and cannot be redeemed.");
        }

        
        $stmt_update = $conn->prepare("UPDATE motorcycle_inventory SET status = 'sold', category = 'brandnew' WHERE id = ?");
        $stmt_update->bind_param('i', $motorcycleId);
        if (!$stmt_update->execute()) {
            throw new Exception("Failed to update motorcycle status.");
        }

        
        $stmt_sale = $conn->prepare("INSERT INTO motorcycle_sales (motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_sale->bind_param('issssdid', $motorcycleId, $saleDate, $customerName, $paymentType, $drNumber, $codAmount, $terms, $monthlyAmortization);
        if (!$stmt_sale->execute()) {
            throw new Exception("Failed to create new sale record.");
        }
        
        
        $stmt_log = $conn->prepare("INSERT INTO motorcycle_redeems (motorcycle_id, redeem_date, amount_paid, redeemed_by_customer, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_log->bind_param('isdsi', $motorcycleId, $redeemDate, $amountPaid, $customerName, $userId);
        if (!$stmt_log->execute()) {
            throw new Exception("Failed to log the redemption event.");
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Motorcycle has been successfully redeemed and marked as sold.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

function getDeliveredStocksSummary() {
    global $conn;

    // --- Parameters ---
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $asOfDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    // --- Validation ---
    if (empty($month) && empty($asOfDate)) {
        echo json_encode(['success' => false, 'message' => 'A Month or As-of Date parameter is required.']);
        return;
    }

    // --- Date setup ---
    if (!empty($asOfDate)) {
        $endDate = $asOfDate;
        $startDate = date('Y-m-01', strtotime($asOfDate));
    } else {
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
    }

    // --- Build filters ---
    $params = [$startDate, $endDate];
    $paramTypes = 'ss';
    $conditions = "mi.date_delivered BETWEEN ? AND ? AND mi.deleted_at IS NULL";

    if ($brand !== 'all') {
        $conditions .= " AND LOWER(mi.brand) = ? ";
        $params[] = $brand;
        $paramTypes .= 's';
    }

    if ($category !== 'all') {
        $conditions .= " AND LOWER(mi.category) = ? ";
        $params[] = $category;
        $paramTypes .= 's';
    }

    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $conditions .= " AND mi.model IN ($modelPlaceholders) ";
            foreach ($models as $m) {
                $params[] = $m;
                $paramTypes .= 's';
            }
        }
    }


    // --- SQL: delivered stocks during period (include later transfers) ---
    $sql = "
        SELECT 
            mi.id,
            mi.brand,
            mi.model,
            mi.color,
            mi.engine_number,
            mi.frame_number,
            mi.inventory_cost,
            mi.category,
            mi.date_delivered,
            mi.initial_dr_number,
            mi.current_branch,
            i.invoice_number
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        WHERE $conditions
        ORDER BY mi.date_delivered ASC
    ";

    // --- Prepare and execute ---
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'SQL Prepare failed: ' . $conn->error]);
        return;
    }

    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // --- Process result ---
    $data = [];
    $totalDelivered = 0;
    $totalCost = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => (int)$row['id'],
            'brand' => $row['brand'],
            'model' => $row['model'],
            'color' => $row['color'],
            'engine_number' => $row['engine_number'],
            'frame_number' => $row['frame_number'],
            'inventory_cost' => (float)$row['inventory_cost'],
            'category' => $row['category'],
            'invoice_number' => $row['invoice_number'],
            'date_delivered' => $row['date_delivered'],
            'initial_dr_number' => $row['initial_dr_number'],
            'branch' => $row['current_branch'],
        ];
        $totalDelivered++;
        $totalCost += (float)$row['inventory_cost'];
    }

    // --- Return JSON ---
    echo json_encode([
        'success' => true,
        'data' => $data,
        'branch' => $branch,
        'month' => $month,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'summary' => [
            'total_delivered' => $totalDelivered,
            'total_inventory_cost' => $totalCost,
        ],
    ]);
}



function getRedeemedUnitsReport() {
    global $conn;

    
    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : null;
    $date = isset($_GET['date']) ? sanitizeInput($_GET['date']) : null;
    $startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : null;
    $endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : null;
    
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    
    $brand = isset($_GET['brand']) ? strtolower(sanitizeInput($_GET['brand'])) : 'all';
    $models_str = isset($_GET['model']) ? sanitizeInput($_GET['model']) : 'all';

    
    if ($date) {
        $startDate = $date; $endDate = $date;
    } elseif ($month) {
        $startDate = date('Y-m-01', strtotime($month)); $endDate = date('Y-m-t', strtotime($month));
    } elseif (!$startDate || !$endDate) {
        echo json_encode(['success' => false, 'message' => 'A valid date range is required.']);
        return;
    }

    
    $conditions = [];
    $params = [];
    $types = '';
    
    if ($branch !== 'all') { $conditions[] = "mi.current_branch = ?"; $params[] = $branch; $types .= 's'; }
    
    
    
    
    
    if ($brand !== 'all') { $conditions[] = "LOWER(mi.brand) = ?"; $params[] = $brand; $types .= 's'; }

    if ($models_str !== 'all' && !empty($models_str)) {
        $models = array_map('trim', explode(',', $models_str));
        if (!empty($models)) {
            $modelPlaceholders = implode(',', array_fill(0, count($models), '?'));
            $conditions[] = "mi.model IN ($modelPlaceholders)";
            foreach ($models as $model) { $params[] = $model; $types .= 's'; }
        }
    }
    
    $conditions[] = "mr.redeem_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';

    $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    
    $sql = "SELECT 
                mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, mi.current_branch,
                mi.inventory_cost, mi.category, 
                mr.redeem_date, mr.amount_paid, mr.redeemed_by_customer,
                i.invoice_number
            FROM motorcycle_inventory mi
            INNER JOIN motorcycle_redeems mr ON mi.id = mr.motorcycle_id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            $whereClause
            ORDER BY mr.redeem_date ASC, mi.brand, mi.model";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    $totalRedeemed = 0;
    $totalAmountPaid = 0;

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalRedeemed++;
        $totalAmountPaid += (float)$row['amount_paid'];
    }
    
    
    echo json_encode([
        'success' => true,
        'month' => $month, 'date' => $date, 'start_date' => $startDate, 'end_date' => $endDate,
        'branch' => $branch, 'category' => 'all', 'brand' => $brand, 
        'data' => $data,
        'summary' => [
            'total_redeemed' => $totalRedeemed,
            'total_amount_paid' => $totalAmountPaid
        ]
    ]);
}

function getTransfersByStatus() {
    global $conn;

    
    $status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'in-transit';
    $query = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $sqlBase = "
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
    ";

    $whereClause = " WHERE it.transfer_status = ? ";
    $params = [$status];
    $param_types = "s";

    if (!empty($query)) {
        $whereClause .= " AND (it.transfer_invoice_number LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? OR it.from_branch LIKE ? OR it.to_branch LIKE ?) ";
        $searchTerm = "%" . $query . "%";
        for ($i = 0; $i < 5; $i++) {
            $params[] = $searchTerm;
        }
        $param_types .= "sssss";
    }

    
    $sqlCount = "SELECT COUNT(*) as total FROM (
                    SELECT 1
                    $sqlBase 
                    $whereClause
                    GROUP BY 
                        it.transfer_invoice_number, 
                        it.from_branch, 
                        it.to_branch, 
                        it.transfer_date,
                        it.transfer_status
                ) as grouped_transfers";
    
    $stmtCount = $conn->prepare($sqlCount);
    if ($stmtCount) {
        if (!empty($params)) {
            $stmtCount->bind_param($param_types, ...$params);
        }
        $stmtCount->execute();
        $totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);
        $stmtCount->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare count query: ' . $conn->error]);
        return;
    }

    
    $sqlData = "
        SELECT
            it.transfer_invoice_number, 
            it.from_branch, 
            it.to_branch, 
            it.transfer_date,
            it.transfer_status,
            COUNT(it.id) as total_units,
            MIN(it.id) as header_id,
            GROUP_CONCAT(DISTINCT mi.model SEPARATOR ', ') as models
        " . $sqlBase . $whereClause . "
        GROUP BY 
            it.transfer_invoice_number, 
            it.from_branch, 
            it.to_branch, 
            it.transfer_date,
            it.transfer_status
        ORDER BY it.transfer_date ASC, MIN(it.id) ASC
        LIMIT ? OFFSET ?
    ";

    $data_params = $params;
    $data_param_types = $param_types;
    $data_params[] = $limit;
    $data_params[] = $offset;
    $data_param_types .= "ii";

    $stmtData = $conn->prepare($sqlData);
    if ($stmtData) {
        if (!empty($data_params)) {
            $stmtData->bind_param($data_param_types, ...$data_params);
        }
        $stmtData->execute();
        $resultData = $stmtData->get_result();
        $data = [];
        while ($row = $resultData->fetch_assoc()) {
            $data[] = $row;
        }
        $stmtData->close();

        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalRecords' => $totalRecords
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare data query: ' . $conn->error]);
    }
}

/**
 * Permanently deletes an entire transfer group based on a representative transfer ID.
 * This is an admin-only action.
 */
function deleteTransfer() {
    global $conn;

    
    if (!isset($_SESSION['position']) || !in_array(strtoupper($_SESSION['position']), ['ADMIN', 'IT STAFF', 'HEAD'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: You do not have permission to perform this action.']);
        return;
    }

    $transferId = isset($_POST['transfer_header_id']) ? (int)$_POST['transfer_header_id'] : 0;
    if ($transferId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Transfer ID provided.']);
        return;
    }

    $conn->begin_transaction();
    try {
        
        $getInfoStmt = $conn->prepare("SELECT transfer_invoice_number FROM inventory_transfers WHERE id = ?");
        $getInfoStmt->bind_param("i", $transferId);
        $getInfoStmt->execute();
        $infoResult = $getInfoStmt->get_result();
        if ($infoResult->num_rows === 0) throw new Exception("Transfer record not found.");
        $transferInvoiceNumber = $infoResult->fetch_assoc()['transfer_invoice_number'];
        $getInfoStmt->close();
        
        
        $getMotorcyclesStmt = $conn->prepare("SELECT motorcycle_id, from_branch FROM inventory_transfers WHERE transfer_invoice_number = ?");
        $getMotorcyclesStmt->bind_param("s", $transferInvoiceNumber);
        $getMotorcyclesStmt->execute();
        $motorcyclesResult = $getMotorcyclesStmt->get_result();
        $motorcyclesToRevert = [];
        while ($row = $motorcyclesResult->fetch_assoc()) {
            $motorcyclesToRevert[] = $row;
        }
        $getMotorcyclesStmt->close();

        
        if (!empty($motorcyclesToRevert)) {
            $revertStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available', current_branch = ? WHERE id = ?");
            foreach ($motorcyclesToRevert as $moto) {
                $revertStmt->bind_param('si', $moto['from_branch'], $moto['motorcycle_id']);
                $revertStmt->execute();
            }
            $revertStmt->close();
        }

        
        $deleteStmt = $conn->prepare("DELETE FROM inventory_transfers WHERE transfer_invoice_number = ?");
        $deleteStmt->bind_param("s", $transferInvoiceNumber);
        $deleteStmt->execute();

        if ($deleteStmt->affected_rows > 0) {
            $conn->commit();
            log_action($conn, 'DELETE', 'inventory_transfers', 0, "Deleted all transfers with invoice #{$transferInvoiceNumber}");
            echo json_encode(['success' => true, 'message' => 'Transfer record(s) deleted and motorcycle statuses reverted.']);
        } else {
            throw new Exception("No transfer records found to delete.");
        }
        $deleteStmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting transfer: ' . $e->getMessage()]);
    }
}
function get_motorcycle_transfer_log() {
    global $conn;

    $motorcycleId = isset($_GET['motorcycle_id']) ? (int)$_GET['motorcycle_id'] : 0;

    if ($motorcycleId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Motorcycle ID.']);
        return;
    }

    // Get motorcycle details
    $detailsStmt = $conn->prepare("
        SELECT 
            mi.brand, 
            mi.model, 
            mi.engine_number, 
            mi.frame_number, 
            mi.date_delivered, 
            mi.current_branch, 
            mi.initial_dr_number,
            i.invoice_number 
        FROM motorcycle_inventory mi
        LEFT JOIN invoices i ON mi.invoice_id = i.id
        WHERE mi.id = ?
    ");
    $detailsStmt->bind_param('i', $motorcycleId);
    $detailsStmt->execute();
    $detailsResult = $detailsStmt->get_result();
    if ($detailsResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found.']);
        return;
    }
    $motorcycleDetails = $detailsResult->fetch_assoc();
    $detailsStmt->close();

    // Get transfer history
    $historyStmt = $conn->prepare("
        SELECT transfer_date, from_branch, to_branch, transfer_status, transfer_invoice_number
        FROM inventory_transfers
        WHERE motorcycle_id = ?
        ORDER BY transfer_date ASC, id ASC
    ");
    $historyStmt->bind_param('i', $motorcycleId);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();

    $historyLog = [];
    $transfers = [];
    while ($row = $historyResult->fetch_assoc()) {
        $transfers[] = $row;
    }
    $historyStmt->close();

    // Build history log

    // Delivery event - ALWAYS use initial_dr_number for delivery from supplier
    $delivery_to = !empty($transfers) ? $transfers[0]['from_branch'] : $motorcycleDetails['current_branch'];
    $historyLog[] = [
        'date' => $motorcycleDetails['date_delivered'],
        'event' => 'Delivered',
        'from' => 'Supplier',
        'to' => $delivery_to,
        'status' => 'Completed',
        'invoice' => $motorcycleDetails['initial_dr_number'] // Always use initial DR number for delivery
    ];

    // Transfer events - use transfer_invoice_number for each transfer
    foreach ($transfers as $transfer) {
        $historyLog[] = [
            'date' => $transfer['transfer_date'],
            'event' => 'Transferred',
            'from' => $transfer['from_branch'],
            'to' => $transfer['to_branch'],
            'status' => ucfirst($transfer['transfer_status']), 
            'invoice' => $transfer['transfer_invoice_number']
        ];
    }

    echo json_encode([
        'success' => true,
        'details' => $motorcycleDetails,
        'history' => $historyLog
    ]);
}
function getSoldUnits() {
    global $conn;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $where = "WHERE mi.status = 'sold'";
    if (!empty($query)) {
        $searchTerm = "%$query%";
        $where .= " AND (ms.customer_name LIKE '$searchTerm' OR mi.model LIKE '$searchTerm' OR mi.engine_number LIKE '$searchTerm' OR mi.current_branch LIKE '$searchTerm')";
    }

    $countSql = "SELECT COUNT(mi.id) as total FROM motorcycle_inventory mi JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id $where";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.current_branch, ms.sale_date, ms.customer_name, ms.payment_type 
            FROM motorcycle_inventory mi JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id $where 
            ORDER BY ms.sale_date ASC LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;

    echo json_encode(['success' => true, 'data' => $data, 'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]]);
}
function getRepossessedUnits() {
    global $conn;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $where = "WHERE mi.category = 'repo'";
    if (!empty($query)) {
        $searchTerm = "%$query%";
        $where .= " AND (mi.model LIKE '$searchTerm' OR mi.engine_number LIKE '$searchTerm' OR mi.current_branch LIKE '$searchTerm' OR mrh.repo_reason LIKE '$searchTerm')";
    }

    $countSql = "SELECT COUNT(mi.id) as total FROM motorcycle_inventory mi LEFT JOIN motorcycle_repo_history mrh ON mi.id = mrh.motorcycle_id $where";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.current_branch, mi.status, mrh.repo_date, mrh.repo_reason, 
                   (SELECT msh.sale_date FROM motorcycle_sales_history msh WHERE msh.motorcycle_id = mi.id ORDER BY msh.archived_at ASC LIMIT 1) as original_sale_date
            FROM motorcycle_inventory mi LEFT JOIN motorcycle_repo_history mrh ON mi.id = mrh.motorcycle_id $where 
            ORDER BY mrh.repo_date ASC LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;

    echo json_encode(['success' => true, 'data' => $data, 'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]]);
}

function getScrappedUnits() {
    global $conn;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $where = "WHERE mi.status = 'scrapped'";
    if (!empty($query)) {
        $searchTerm = "%$query%";
        $where .= " AND (mi.model LIKE '$searchTerm' OR mi.engine_number LIKE '$searchTerm' OR mi.current_branch LIKE '$searchTerm' OR ms.scrap_reason LIKE '$searchTerm')";
    }

    $countSql = "SELECT COUNT(mi.id) as total FROM motorcycle_inventory mi JOIN motorcycle_scraps ms ON mi.id = ms.motorcycle_id $where";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.current_branch, mi.inventory_cost, ms.scrap_date, ms.scrap_reason 
            FROM motorcycle_inventory mi JOIN motorcycle_scraps ms ON mi.id = ms.motorcycle_id $where 
            ORDER BY ms.scrap_date ASC LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;

    echo json_encode(['success' => true, 'data' => $data, 'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]]);
}

function getRedeemedUnits() {
    global $conn;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $where = "WHERE mr.id IS NOT NULL";
    if (!empty($query)) {
        $searchTerm = "%$query%";
        $where .= " AND (mr.redeemed_by_customer LIKE '$searchTerm' OR mi.model LIKE '$searchTerm' OR mi.engine_number LIKE '$searchTerm')";
    }

    $countSql = "SELECT COUNT(mr.id) as total FROM motorcycle_redeems mr JOIN motorcycle_inventory mi ON mr.motorcycle_id = mi.id $where";
    $totalRecords = $conn->query($countSql)->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);

    $sql = "SELECT mi.id, mi.model, mi.engine_number, mi.current_branch, 
                   mr.redeem_date, mr.redeemed_by_customer, mr.amount_paid,
                   (SELECT mrh.repo_date FROM motorcycle_repo_history mrh WHERE mrh.motorcycle_id = mi.id ORDER BY mrh.repo_date ASC LIMIT 1) as original_repo_date
            FROM motorcycle_redeems mr JOIN motorcycle_inventory mi ON mr.motorcycle_id = mi.id $where
            ORDER BY mr.redeem_date ASC LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;

    echo json_encode(['success' => true, 'data' => $data, 'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]]);
}

function revertTransaction() {
    global $conn;

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $type = isset($_POST['type']) ? sanitizeInput($_POST['type']) : '';
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    if ($id <= 0 || empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data for revert action.']);
        return;
    }

    $conn->begin_transaction();
    try {
        switch ($type) {
            case 'sold':
                
                $conn->query("INSERT INTO motorcycle_sales_history (id, motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization, created_at) SELECT id, motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization, created_at FROM motorcycle_sales WHERE motorcycle_id = $id");
                $conn->query("DELETE FROM motorcycle_sales WHERE motorcycle_id = $id");
                $conn->query("UPDATE motorcycle_inventory SET status = 'available' WHERE id = $id");
                $log_details = "Reverted sale for Motorcycle ID {$id}. Unit is now 'available'.";
                break;

            case 'repo':
                
                $last_sale_query = $conn->query("SELECT * FROM motorcycle_sales_history WHERE motorcycle_id = $id ORDER BY archived_at ASC LIMIT 1");
                if ($last_sale_query->num_rows > 0) {
                    $last_sale = $last_sale_query->fetch_assoc();
                    $sale_id = $last_sale['id'];
                    $conn->query("INSERT INTO motorcycle_sales SELECT * FROM motorcycle_sales_history WHERE id = $sale_id");
                    $conn->query("DELETE FROM motorcycle_sales_history WHERE id = $sale_id");
                }
                $conn->query("DELETE FROM motorcycle_repo_history WHERE motorcycle_id = $id");
                $conn->query("UPDATE motorcycle_inventory SET status = 'sold', category = 'brandnew' WHERE id = $id");
                $log_details = "Reverted repossession for Motorcycle ID {$id}. Unit is now 'sold'.";
                break;

            case 'scrapped':
                
                $conn->query("DELETE FROM motorcycle_scraps WHERE motorcycle_id = $id");
                $conn->query("UPDATE motorcycle_inventory SET status = 'available' WHERE id = $id");
                $log_details = "Reverted scrap status for Motorcycle ID {$id}. Unit is now 'available'.";
                break;

            case 'redeemed':
                
                $conn->query("DELETE FROM motorcycle_redeems WHERE motorcycle_id = $id");
                
                $conn->query("DELETE FROM motorcycle_sales WHERE motorcycle_id = $id");
                $conn->query("UPDATE motorcycle_inventory SET status = 'available', category = 'repo' WHERE id = $id");
                $log_details = "Reverted redemption for Motorcycle ID {$id}. Unit is now 'available' and 'repo'.";
                break;

            default:
                throw new Exception("Invalid revert type specified.");
        }

        log_action($conn, 'REVERT', 'motorcycle_inventory', $id, $log_details);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction successfully reverted.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error reverting transaction: ' . $e->getMessage()]);
    }
}

/**
 * Fetches all motorcycles associated with a single transfer invoice number.
 * Used to populate the "Manage Transfer" modal.
 */
function getTransferDetailsByInvoice() {
    global $conn;
    
    $invoiceNumber = isset($_GET['transfer_invoice_number']) ? sanitizeInput($_GET['transfer_invoice_number']) : '';
    if (empty($invoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Transfer Invoice Number is required.']);
        return;
    }

    
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

function update_transfer_group() {
    global $conn;
    $stmt = null;  

    try {
        
        $originalInvoiceNumber = isset($_POST['original_invoice_number']) ? sanitizeInput($_POST['original_invoice_number']) : '';
        $newInvoiceNumber = isset($_POST['transfer_invoice_number']) ? sanitizeInput($_POST['transfer_invoice_number']) : '';
        $fromBranch = isset($_POST['from_branch']) ? sanitizeInput($_POST['from_branch']) : '';
        $toBranch = isset($_POST['to_branch']) ? sanitizeInput($_POST['to_branch']) : '';
        $transferDate = isset($_POST['transfer_date']) ? sanitizeInput($_POST['transfer_date']) : '';
        $dateReceived = isset($_POST['date_received']) ? sanitizeInput($_POST['date_received']) : '';
        $transferredBy = isset($_POST['transferred_by']) ? intval($_POST['transferred_by']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0);
        $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
        $transferStatusProvided = isset($_POST['transfer_status']) ? sanitizeInput($_POST['transfer_status']) : null;
        
        $itemsToAdd = isset($_POST['motorcycles_to_add']) ? json_decode($_POST['motorcycles_to_add'], true) : [];
        $itemsToRemove = isset($_POST['motorcycles_to_remove']) ? json_decode($_POST['motorcycles_to_remove'], true) : [];

        $isDateReceivedEdit = !empty($dateReceived) && empty($transferDate) && empty($fromBranch) && empty($toBranch) && empty($notes) && empty($newInvoiceNumber) && empty($transferStatusProvided);

        if (empty($originalInvoiceNumber)) {
            echo json_encode(['success' => false, 'message' => 'Missing original invoice number.']);
            return;
        }

        if ($isDateReceivedEdit && !empty($transferStatusProvided)) {
            echo json_encode(['success' => false, 'message' => 'Cannot update transfer_status when only editing date_received.']);
            return;
        }

        
        if (!empty($transferDate)) {
            $dateObjTransfer = DateTime::createFromFormat('m/d/Y', $transferDate);
            if ($dateObjTransfer) $transferDate = $dateObjTransfer->format('Y-m-d');
            else {
                echo json_encode(['success' => false, 'message' => 'Invalid transfer date format.']);
                return;
            }
        }

        if (!empty($dateReceived)) {
            $dateObjReceived = DateTime::createFromFormat('m/d/Y', $dateReceived);
            if ($dateObjReceived) $dateReceived = $dateObjReceived->format('Y-m-d');
            else {
                echo json_encode(['success' => false, 'message' => 'Invalid date received format.']);
                return;
            }
        }

        
        $checkStmt = $conn->prepare('SELECT id FROM inventory_transfers WHERE transfer_invoice_number = ? AND transfer_invoice_number != ? LIMIT 1');
        $checkStmt->bind_param('ss', $newInvoiceNumber, $originalInvoiceNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();  
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'New invoice number already exists.']);
            $checkStmt->close();
            return;
        }
        $checkStmt->close();  

        $conn->begin_transaction();

        
        if (!empty($itemsToRemove) && !$isDateReceivedEdit) {
            $removeIds = array_map('intval', $itemsToRemove);
            $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
            $types = str_repeat('i', count($removeIds));

            $revertStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'available', current_branch = ? WHERE id IN ($placeholders)");
            $revertStmt->bind_param('s' . $types, $fromBranch, ...$removeIds);
            $revertStmt->execute();
            $revertStmt->close();  

            $deleteStmt = $conn->prepare("DELETE FROM inventory_transfers WHERE transfer_invoice_number = ? AND motorcycle_id IN ($placeholders)");
            $deleteStmt->bind_param('s' . $types, $originalInvoiceNumber, ...$removeIds);
            $deleteStmt->execute();
            $deleteResult = $deleteStmt->get_result();  
            $deleteStmt->close();  

            foreach ($removeIds as $id) {
                log_action($conn, 'UPDATE', 'motorcycle_inventory', $id, "Removed from transfer group {$originalInvoiceNumber} and reverted to available.");
            }
        }

        
        if (!empty($itemsToAdd) && !$isDateReceivedEdit) {
            $addTransferStmt = $conn->prepare("INSERT INTO inventory_transfers (motorcycle_id, from_branch, to_branch, transfer_date, date_received, transferred_by, notes, transfer_status, transfer_invoice_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $updateMotorcycleStmt = $conn->prepare("UPDATE motorcycle_inventory SET status = 'transferred', current_branch = ?, inventory_cost = ? WHERE id = ?");

            foreach ($itemsToAdd as $item) {
                $motorcycleId = intval($item['id']);
                $inventoryCost = floatval($item['inventory_cost']);

                $updateMotorcycleStmt->bind_param('sdi', $toBranch, $inventoryCost, $motorcycleId);
                $updateMotorcycleStmt->execute();
                $updateMotorcycleStmt->close();  

                $addTransferStmt->bind_param('issssssss', $motorcycleId, $fromBranch, $toBranch, $transferDate, $dateReceived, $transferredBy, $notes, $transferStatusProvided ?: 'in-transit', $newInvoiceNumber);
                $addTransferStmt->execute();
                log_action($conn, 'CREATE', 'inventory_transfers', $conn->insert_id, "Added to transfer group {$newInvoiceNumber}.");
            }
            $addTransferStmt->close();  
        }

        
        if ($isDateReceivedEdit) {
            $updateGroupStmt = $conn->prepare("UPDATE inventory_transfers SET date_received = ? WHERE transfer_invoice_number = ?");
            $updateGroupStmt->bind_param('ss', $dateReceived, $originalInvoiceNumber);
        } else {
            $updateGroupStmt = $conn->prepare("UPDATE inventory_transfers SET to_branch = ?, transfer_date = ?, date_received = ?, transferred_by = ?, notes = ?, transfer_invoice_number = ? WHERE transfer_invoice_number = ?");
            $updateGroupStmt->bind_param('sssssss', $toBranch, $transferDate, $dateReceived, $transferredBy, $notes, $newInvoiceNumber, $originalInvoiceNumber);
        }
        $updateGroupStmt->execute();
        $updateGroupStmt->close();  

        log_action($conn, 'UPDATE', 'inventory_transfers', 0, "Updated transfer group {$originalInvoiceNumber} with transfer_status protected.");

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer group updated successfully with transfer_status protected.']);

    } catch (Exception $e) {
        if ($conn->rollback()) {
            echo json_encode(['success' => false, 'message' => 'Error updating transfer group: ' . $e->getMessage() . '. Transaction rolled back.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating transfer group and rollback failed: ' . $e->getMessage()]);
        }
    } finally {
        
        if ($stmt) $stmt->close();  
    }
}
function getDirectShipments() {
    global $conn;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Select individual motorcycles for direct shipments
    $sql = "SELECT 
                mi.id,
                mi.initial_dr_number AS invoice_number,
                mi.date_delivered AS shipment_date,
                mi.brand,
                mi.model,
                mi.engine_number,
                mi.frame_number,
                mi.current_branch
            FROM motorcycle_inventory mi
            WHERE 1=1";

    $countSql = "SELECT COUNT(mi.id) AS total 
                 FROM motorcycle_inventory mi
                 WHERE 1=1";

    $params = [];
    $types = '';

    if (!empty($query)) {
        $searchTerm = "%$query%";
        $sql .= " AND (mi.initial_dr_number LIKE ? OR mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? OR mi.frame_number LIKE ? OR mi.current_branch LIKE ?)";
        $countSql .= " AND (mi.initial_dr_number LIKE ? OR mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? OR mi.frame_number LIKE ? OR mi.current_branch LIKE ?)";
        $params = array_fill(0, 6, $searchTerm);
        $types = str_repeat('s', 6);
    }

    $sql .= " ORDER BY mi.date_delivered DESC, mi.initial_dr_number DESC LIMIT ? OFFSET ?";
    $paramsWithLimit = $params;
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;
    $typesWithLimit = $types . 'ii';

    // Get total count
    $countStmt = $conn->prepare($countSql);
    if (!empty($query)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
    $totalPages = ceil($totalRecords / $limit);

    // Get data
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        return;
    }
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
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
        'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]
    ]);
}


function deleteInvoiceTransaction() {
    global $conn;

    $invoiceId = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;

    if ($invoiceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Invoice ID provided.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // Check if any motorcycles from this invoice have been transferred
        $checkTransfersStmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_transfers it 
                                            INNER JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id 
                                            WHERE mi.invoice_id = ?");
        $checkTransfersStmt->bind_param('i', $invoiceId);
        $checkTransfersStmt->execute();
        if ($checkTransfersStmt->get_result()->fetch_assoc()['count'] > 0) {
            throw new Exception("Cannot delete invoice: One or more motorcycles from this invoice have transfer history.");
        }
        $checkTransfersStmt->close();

        // Check if this invoice is used in any transfers as transfer_invoice_number
        $checkTransferInvoiceStmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_transfers WHERE transfer_invoice_number = (SELECT invoice_number FROM invoices WHERE id = ?)");
        $checkTransferInvoiceStmt->bind_param('i', $invoiceId);
        $checkTransferInvoiceStmt->execute();
        if ($checkTransferInvoiceStmt->get_result()->fetch_assoc()['count'] > 0) {
            throw new Exception("Cannot delete invoice: This invoice number is being used in transfer transactions.");
        }
        $checkTransferInvoiceStmt->close();

        // Get all motorcycles on this invoice to check their status
        $getMotorcyclesStmt = $conn->prepare("SELECT id FROM motorcycle_inventory WHERE invoice_id = ?");
        $getMotorcyclesStmt->bind_param('i', $invoiceId);
        $getMotorcyclesStmt->execute();
        $motorcycleResult = $getMotorcyclesStmt->get_result();
        
        $motorcycleIds = [];
        while ($row = $motorcycleResult->fetch_assoc()) {
            $motorcycleIds[] = $row['id'];
        }
        $getMotorcyclesStmt->close();

        if (!empty($motorcycleIds)) {
            $placeholders = implode(',', array_fill(0, count($motorcycleIds), '?'));
            $types = str_repeat('i', count($motorcycleIds));

            // Check if any have been sold
            $checkSalesStmt = $conn->prepare("SELECT COUNT(*) as count FROM motorcycle_sales WHERE motorcycle_id IN ($placeholders)");
            $checkSalesStmt->bind_param($types, ...$motorcycleIds);
            $checkSalesStmt->execute();
            if ($checkSalesStmt->get_result()->fetch_assoc()['count'] > 0) {
                throw new Exception("Cannot delete invoice: One or more motorcycles from this invoice have been sold.");
            }
            $checkSalesStmt->close();

            // Delete motorcycle records
            $deleteMotorcyclesStmt = $conn->prepare("DELETE FROM motorcycle_inventory WHERE invoice_id = ?");
            $deleteMotorcyclesStmt->bind_param('i', $invoiceId);
            $deleteMotorcyclesStmt->execute();
            $deleteMotorcyclesStmt->close();
        }

        // Delete the invoice
        $deleteInvoiceStmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        $deleteInvoiceStmt->bind_param('i', $invoiceId);
        $deleteInvoiceStmt->execute();
        $affectedRows = $deleteInvoiceStmt->affected_rows;
        $deleteInvoiceStmt->close();

        if ($affectedRows > 0) {
            $conn->commit();
            log_action($conn, 'DELETE', 'invoices', $invoiceId, "Deleted invoice and all associated direct-shipment motorcycles.");
            echo json_encode(['success' => true, 'message' => 'Invoice and all its motorcycles have been deleted successfully.']);
        } else {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Invoice transaction cleaned up successfully.']);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getDirectShipmentForEdit() {
    global $conn;

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid motorcycle ID']);
        return;
    }

    // ✅ FIXED: Fetch directly from motorcycle_inventory for direct shipments
    $stmt = $conn->prepare("
        SELECT 
            mi.id,
            mi.initial_dr_number AS invoice_number,
            mi.date_delivered AS shipment_date,
            mi.brand,
            mi.model,
            mi.engine_number,
            mi.frame_number,
            mi.current_branch
        FROM motorcycle_inventory mi
        WHERE mi.id = ?
    ");

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

        // Add fallback for empty values
        if (empty($data['shipment_date'])) {
            $data['shipment_date'] = '';
        }
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = '';
        }

        // Indicate this came from direct shipment
        $data['invoice_source'] = 'direct';

        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Motorcycle not found']);
    }
}
function updateDirectShipment() {
    global $conn;

    // Validate required fields
    if (empty($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: id (motorcycle inventory ID)']);
        return;
    }

    $inventoryId = intval($_POST['id']);
    $initialDrNumber = isset($_POST['invoice_number']) ? trim(sanitizeInput($_POST['invoice_number'])) : null;
    $dateDelivered = isset($_POST['shipment_date']) ? trim(sanitizeInput($_POST['shipment_date'])) : null;

    // Validate required fields
    if (empty($initialDrNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invoice Number is required.']);
        return;
    }

    if (empty($dateDelivered)) {
        echo json_encode(['success' => false, 'message' => 'Shipment Date is required.']);
        return;
    }

    // Validate date format and logic
    $deliveryTimestamp = strtotime($dateDelivered);
    if ($deliveryTimestamp === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
        return;
    }

    if ($deliveryTimestamp > time()) {
        echo json_encode(['success' => false, 'message' => 'Delivery date cannot be in the future.']);
        return;
    }

    $conn->begin_transaction();

    try {
        // ✅ MODIFIED: Check if invoice exists and get its ID, or create new one
        $invoiceId = null;
        
        // First, check if an invoice with this number already exists
        $checkInvoiceStmt = $conn->prepare("SELECT id, date_delivered FROM invoices WHERE invoice_number = ?");
        $checkInvoiceStmt->bind_param('s', $initialDrNumber);
        $checkInvoiceStmt->execute();
        $invoiceResult = $checkInvoiceStmt->get_result();
        
        if ($invoiceResult->num_rows > 0) {
            // Invoice exists - use existing invoice
            $existingInvoice = $invoiceResult->fetch_assoc();
            $invoiceId = $existingInvoice['id'];
            
            // Optional: You can update the existing invoice date if needed, or keep the original
            // For now, we'll keep the original invoice date
            $checkInvoiceStmt->close();
            
            // Log that we're linking to existing invoice
            error_log("Linking motorcycle {$inventoryId} to existing invoice {$initialDrNumber} (ID: {$invoiceId})");
        } else {
            // Invoice doesn't exist - create new one
            $checkInvoiceStmt->close();
            
            $insertInvoiceStmt = $conn->prepare("INSERT INTO invoices (invoice_number, date_delivered) VALUES (?, ?)");
            $insertInvoiceStmt->bind_param('ss', $initialDrNumber, $dateDelivered);
            
            if (!$insertInvoiceStmt->execute()) {
                throw new Exception("Failed to create invoice: " . $insertInvoiceStmt->error);
            }
            
            $invoiceId = $conn->insert_id;
            $insertInvoiceStmt->close();
            
            error_log("Created new invoice {$initialDrNumber} (ID: {$invoiceId}) for motorcycle {$inventoryId}");
        }

        // ✅ MODIFIED: Update motorcycle_inventory with invoice_id linkage
        $sql = "UPDATE motorcycle_inventory SET initial_dr_number = ?, date_delivered = ?, invoice_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

        $stmt->bind_param('ssii', $initialDrNumber, $dateDelivered, $invoiceId, $inventoryId);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows === 0) {
            throw new Exception("No changes made. Motorcycle not found or data unchanged.");
        }

        $conn->commit();

        // Log the changes
        $actionDetails = "Updated direct shipment: DR number to '{$initialDrNumber}', delivery date to '{$dateDelivered}'";
        if ($invoiceId) {
            $actionDetails .= ", linked to invoice ID: {$invoiceId}";
        }
        
        log_action($conn, 'UPDATE', 'motorcycle_inventory', $inventoryId, $actionDetails);

        echo json_encode([
            'success' => true,
            'message' => 'Direct shipment updated successfully and linked to invoice.',
            'type' => 'inventory_updated',
            'invoice_id' => $invoiceId
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("ERROR in updateDirectShipment(): " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
/**
 * Fetches paginated and searchable data from the audit_log.
 */
function getActivityLog() {
    global $conn;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $limit = 25; 
    $offset = ($page - 1) * $limit;

    $where = "";
    $params = [];
    $types = "";

    if (!empty($query)) {
        $searchTerm = "%$query%";
        
        $where = "WHERE al.action_details LIKE ? OR u.username LIKE ? OR al.action_type LIKE ? OR al.table_name LIKE ?";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        $types = "ssss";
    }

    
    $countSql = "SELECT COUNT(al.id) as total 
                 FROM audit_log al 
                 LEFT JOIN users u ON al.user_id = u.id 
                 $where";
    
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare count query: ' . $conn->error]);
        return;
    }
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    $countStmt->close();

    
    $sql = "SELECT al.id, al.action_timestamp, al.action_type, al.table_name, al.record_id, al.action_details, COALESCE(u.username, 'System') as username
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.id
            $where
            ORDER BY al.action_timestamp ASC
            LIMIT ? OFFSET ?";
    
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare data query: ' . $conn->error]);
        return;
    }
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
        'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages]
    ]);
}

function getBranchAtDate($motorcycleId, $cutoffDate, $conn) {
    $sql = "SELECT to_branch 
            FROM inventory_transfers 
            WHERE motorcycle_id = ? 
              AND transfer_status = 'completed' 
              AND transfer_date <= ? 
            ORDER BY transfer_date DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $motorcycleId, $cutoffDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['to_branch'];
    }
    
    // If no transfers, get current branch
    $sql = "SELECT current_branch FROM motorcycle_inventory WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $motorcycleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc()['current_branch'];
}
?>