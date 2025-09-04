<?php
header( 'Content-Type: application/json' );
require_once '../api/db_config.php';

function sanitizeInput( $data ) {
    global $conn;
    return $conn->real_escape_string( htmlspecialchars( strip_tags( trim( $data ) ) ) );
}

$action = isset( $_REQUEST[ 'action' ] ) ? sanitizeInput( $_REQUEST[ 'action' ] ) : '';

switch ( $action ) {
    case 'get_inventory_dashboard':
    getInventoryDashboard();
    break;
    case 'get_inventory_table':
    getInventoryTable();
    break;
    case 'get_motorcycle':
    getMotorcycle();
    break;
    case 'get_motorcycle_transfers':
    getMotorcycleTransfers();
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
    
    case 'get_transfer_history':
    getTransferHistory();
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
    case 'transfer_multiple_motorcycles':
    transferMultipleMotorcycles();
    break;
    case 'get_incoming_transfers':
    getIncomingTransfers();
    break;
     case 'accept_transfers':
        acceptTransfers();
        break;
    case 'reject_transfers':  // ADD THIS NEW CASE
        rejectTransfers();
        break;
    case 'get_monthly_inventory':
    getMonthlyInventory();
    break;
    case 'get_monthly_transferred_summary':
    getMonthlyTransferredSummary();
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
    case 'sell_motorcycle':
    sellMotorcycle();
    break;
    case 'get_available_motorcycles_report':
    getAvailableMotorcyclesReport();
    break;
    case 'search_transfer_receipt':
    searchTransferReceipt();
    break;
case 'get_transfer_receipt':
    getTransferReceipt();
    break;

    case 'search_invoice_number':
    searchInvoiceNumber();
    break;
case 'get_invoice_details':
    getInvoiceDetails();
    break;
    case 'get_all_transfer_histories':
    getAllTransferHistories();
    break;



    default:
    echo json_encode( [ 'success' => false, 'message' => 'Invalid action' ] );
    break;
}

function getInventoryDashboard() {
    global $conn;

    $search = isset( $_GET[ 'search' ] ) ? sanitizeInput( $_GET[ 'search' ] ) : '';

    $userBranch = isset( $_SESSION[ 'user_branch' ] ) ? $_SESSION[ 'user_branch' ] : '';
    $userPosition = isset( $_SESSION[ 'position' ] ) ? $_SESSION[ 'position' ] : '';

    $sql = "SELECT model, brand, color, COUNT(*) as total_quantity 
            FROM motorcycle_inventory 
            WHERE status = 'available'";

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
    $sql = "SELECT mi.*, i.invoice_number 
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

    $required = [ 'id', 'date_delivered', 'brand', 'model', 'category', 'engine_number', 'frame_number', 'color', 'current_branch', 'status' ];
    foreach ( $required as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            echo json_encode( [ 'success' => false, 'message' => "Missing required field: $field" ] );
            return;
        }
    }

    $id = intval( $_POST[ 'id' ] );
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
                                   SET date_delivered = ?, brand = ?, model = ?, category = ?, engine_number = ?, 
                                       frame_number = ?, color = ?, inventory_cost = ?, current_branch = ?, status = ?, invoice_id = ?
                                   WHERE id = ?" );
            $stmt->bind_param( 'sssssssdssii', $dateDelivered, $brand, $model, $category, $engineNumber,
                              $frameNumber, $color, $inventory_cost, $currentBranch, $status, $invoiceId, $id );
        } else {
            $stmt = $conn->prepare( "UPDATE motorcycle_inventory 
                                   SET date_delivered = ?, brand = ?, model = ?, category = ?, engine_number = ?, 
                                       frame_number = ?, color = ?, inventory_cost = ?, current_branch = ?, status = ?
                                   WHERE id = ?" );
            $stmt->bind_param( 'sssssssdssi', $dateDelivered, $brand, $model, $category, $engineNumber,
                              $frameNumber, $color, $inventory_cost, $currentBranch, $status, $id );
        }

        if ( !$stmt->execute() ) {
            throw new Exception( 'Error updating motorcycle: ' . $stmt->error );
        }

        // Handle sale details if status is 'sold'
        if ($status === 'sold') {
            // Check if sale record exists
            $checkSaleStmt = $conn->prepare("SELECT id FROM motorcycle_sales WHERE motorcycle_id = ?");
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

    $query = isset( $_GET[ 'query' ] ) ? sanitizeInput( $_GET[ 'query' ] ) : '';
    $field = isset( $_GET[ 'field' ] ) ? sanitizeInput( $_GET[ 'field' ] ) : 'all';
    $includeInventoryCost = isset( $_GET[ 'include_inventory_cost' ] ) ? true : false;

    $sql = "SELECT mi.id, mi.brand, mi.model, mi.color, mi.engine_number, mi.frame_number, 
                   mi.inventory_cost, mi.current_branch, mi.status, i.invoice_number
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.status = 'available'";

    $params = [];
    $types = '';

    if ( !empty( $query ) ) {
        if ( $field === 'engine_number' ) {
            $sql .= ' AND mi.engine_number LIKE ?';
            $searchTerm = "%$query%";
            $params[] = $searchTerm;
            $types = 's';
        } else {
            $sql .= " AND (mi.brand LIKE ? OR mi.model LIKE ? OR mi.engine_number LIKE ? 
                      OR mi.frame_number LIKE ? OR i.invoice_number LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_fill( 0, 5, $searchTerm );
            $types = str_repeat( 's', count( $params ) );
        }
    }

    $sql .= ' ORDER BY mi.brand, mi.model LIMIT 10';

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
                   mi.inventory_cost, mi.current_branch, mi.status, i.invoice_number
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.status = 'available' AND mi.current_branch = '$userBranch'";

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

function getCurrentBranch() {
    echo json_encode( [
        'success' => true,
        'branch' => $_SESSION[ 'user_branch' ] ?? 'RXS-S'
    ] );
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

    // Check if transfer invoice number already exists
    $checkInvoiceStmt = $conn->prepare("SELECT id FROM inventory_transfers WHERE transfer_invoice_number = ?");
    $checkInvoiceStmt->bind_param('s', $transferInvoiceNumber);
    $checkInvoiceStmt->execute();
    $invoiceResult = $checkInvoiceStmt->get_result();
    
    if ($invoiceResult->num_rows > 0) {
        echo json_encode([ 'success' => false, 'message' => 'Transfer invoice number already exists' ]);
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
        echo json_encode( [ 'success' => false, 'message' => 'Some motorcycles not found, not available, or not from the specified branch' ] );
        return;
    }

    $conn->begin_transaction();

    try {
        // Get motorcycle details for receipt before updating
        $motorcycleDetails = [];
        $getDetailsStmt = $conn->prepare("SELECT id, brand, model, color, engine_number, frame_number, inventory_cost 
                                        FROM motorcycle_inventory WHERE id IN ($placeholders)");
        $getDetailsStmt->bind_param($types, ...$motorcycleIds);
        $getDetailsStmt->execute();
        $detailsResult = $getDetailsStmt->get_result();
        
        while ($row = $detailsResult->fetch_assoc()) {
            $motorcycleDetails[] = $row;
        }

        // ONLY UPDATE STATUS TO 'transferred' and inventory_cost - DO NOT CHANGE current_branch YET
        $updateStmt = $conn->prepare( "UPDATE motorcycle_inventory 
                                    SET status = 'transferred', inventory_cost = ?
                                    WHERE id = ?" );

        foreach ( $motorcycleIds as $index => $id ) {
            $inventoryCost = $inventoryCosts[$index] ?? null;
            $updateStmt->bind_param( 'di', $inventoryCost, $id );
            $updateStmt->execute();
        }

        // Insert transfer records with the same transfer invoice number
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
        
        // Calculate totals for receipt
        $totalCost = array_sum($inventoryCosts);
        
        echo json_encode( [
            'success' => true,
            'message' => 'Successfully initiated transfer for ' . count( $motorcycleIds ) . ' motorcycle(s). Motorcycles will remain at current branch until accepted.',
            'transferred_count' => count( $motorcycleIds ),
            'to_branch' => $toBranch,
            'transfer_invoice_number' => $transferInvoiceNumber,
            'receipt_data' => [
                'transfer_ids' => $transferIds,
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
            'message' => 'Error transferring motorcycles: ' . $e->getMessage(),
            'error_details' => $conn->error
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

    // Sanitize transfer IDs to integers
    $transferIds = array_map('intval', $transferIds);
    $placeholders = implode(',', array_fill(0, count($transferIds), '?'));
    $currentDate = date('Y-m-d H:i:s'); // Acceptance datetime

    $conn->begin_transaction();

    try {
        // Get transfer details before updating, including transfer_invoice_number and transfer_date
        $getTransfersStmt = $conn->prepare("SELECT id, motorcycle_id, to_branch, from_branch, transfer_invoice_number, transfer_date FROM inventory_transfers 
                                           WHERE id IN ($placeholders) AND transfer_status = 'in'");
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

        // Verify that the transfers are actually for the current branch
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

        // Prepare statement to find or create invoice by transfer_invoice_number
        $selectInvoiceStmt = $conn->prepare("SELECT id FROM invoices WHERE invoice_number = ?");
        $insertInvoiceStmt = $conn->prepare("INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)");

        // Update motorcycles - Change current_branch, status, date_received, invoice_id, and date_delivered
        foreach ($motorcycleUpdates as $update) {
            $transferInvoiceNumber = $update['transfer_invoice_number'];
            $transferDate = $update['transfer_date'];  // Use original transfer date here

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
                // Update with date_received if column exists, also update invoice_id and date_delivered
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available', date_received = ?, invoice_id = ?, date_delivered = ?
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('ssisi', $update['to_branch'], $transferDate, $invoiceId, $transferDate, $update['motorcycle_id']);
            } else {
                // Update without date_received if column doesn't exist, also update invoice_id and date_delivered
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available', invoice_id = ?, date_delivered = ?
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('sisi', $update['to_branch'], $invoiceId, $transferDate, $update['motorcycle_id']);
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

function sellMotorcycle() {
    global $conn;

    $required = [ 'motorcycle_id', 'sale_date', 'customer_name', 'payment_type' ];
    foreach ( $required as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            echo json_encode( [ 'success' => false, 'message' => "Missing required field: $field" ] );
            return;
        }
    }

    $motorcycleId = intval( $_POST[ 'motorcycle_id' ] );
    $saleDate = sanitizeInput( $_POST[ 'sale_date' ] );
    $customerName = sanitizeInput( $_POST[ 'customer_name' ] );
    $paymentType = sanitizeInput( $_POST[ 'payment_type' ] );
    $drNumber = isset( $_POST[ 'dr_number' ] ) ? sanitizeInput( $_POST[ 'dr_number' ] ) : null;
    $codAmount = isset( $_POST[ 'cod_amount' ] ) ? floatval( $_POST[ 'cod_amount' ] ) : null;
    $terms = isset( $_POST[ 'terms' ] ) ? intval( $_POST[ 'terms' ] ) : null;
    $monthlyAmortization = isset( $_POST[ 'monthly_amortization' ] ) ? floatval( $_POST[ 'monthly_amortization' ] ) : null;

    if ( $paymentType === 'COD' ) {
        if ( empty( $drNumber ) || $codAmount === null ) {
            echo json_encode( [ 'success' => false, 'message' => 'DR Number and COD Amount are required for COD payment' ] );
            return;
        }
    } else if ( $paymentType === 'Installment' ) {
        if ( $terms === null || $monthlyAmortization === null ) {
            echo json_encode( [ 'success' => false, 'message' => 'Terms and Monthly Amortization are required for Installment payment' ] );
            return;
        }
    }

    $conn->begin_transaction();

    try {
        $saleStmt = $conn->prepare( "INSERT INTO motorcycle_sales 
                                  (motorcycle_id, sale_date, customer_name, payment_type, dr_number, cod_amount, terms, monthly_amortization)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)" );
        $saleStmt->bind_param( 'issssdid', $motorcycleId, $saleDate, $customerName, $paymentType, $drNumber, $codAmount, $terms, $monthlyAmortization );
        $saleStmt->execute();

        $updateStmt = $conn->prepare( "UPDATE motorcycle_inventory SET status = 'sold' WHERE id = ?" );
        $updateStmt->bind_param( 'i', $motorcycleId );
        $updateStmt->execute();

        $conn->commit();
        echo json_encode( [ 'success' => true, 'message' => 'Motorcycle marked as sold successfully' ] );
    } catch ( Exception $e ) {
        $conn->rollback();
        echo json_encode( [ 'success' => false, 'message' => 'Error selling motorcycle: ' . $e->getMessage() ] );
    }
}
function getMonthlyInventory() {
    global $conn;

    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';

    if (empty($month)) {
        echo json_encode(['success' => false, 'message' => 'Month parameter is required']);
        return;
    }

    $startDate = date('Y-m-01', strtotime($month));
    $endDate   = date('Y-m-t', strtotime($month));
    $prevMonthEnd = date('Y-m-t', strtotime($month . ' -1 month'));

    $categoryCondition = '';
    if ($category !== 'all') {
        $categoryCondition = " AND LOWER(mi.category) = '$category' ";
    }

    // BEGINNING BALANCE
    if (strtoupper($branch) === 'ALL') {
        $sqlBeginning = "
            SELECT COUNT(*) as count_beginning, COALESCE(SUM(inventory_cost), 0) as cost_beginning
            FROM motorcycle_inventory mi
            WHERE mi.status = 'available'
            AND mi.date_delivered <= ?
            $categoryCondition
        ";
        $stmtBeginning = $conn->prepare($sqlBeginning);
        $stmtBeginning->bind_param('s', $prevMonthEnd);
    } else {
        $sqlBeginning = "
            SELECT COUNT(*) as count_beginning, COALESCE(SUM(inventory_cost), 0) as cost_beginning
            FROM motorcycle_inventory mi
            WHERE mi.date_delivered <= ?
            AND mi.status != 'deleted'
            AND (
                (mi.current_branch = ? AND mi.status = 'available')
                OR
                mi.id IN (
                    SELECT it.motorcycle_id 
                    FROM inventory_transfers it
                    WHERE it.from_branch = ?
                    AND it.transfer_date BETWEEN ? AND ?
                    AND it.transfer_status = 'completed'
                    AND mi.date_delivered <= ?
                )
            )
            $categoryCondition
        ";
        $stmtBeginning = $conn->prepare($sqlBeginning);
        $stmtBeginning->bind_param('ssssss', $prevMonthEnd, $branch, $branch, $startDate, $endDate, $prevMonthEnd);
    }
    $stmtBeginning->execute();
    $beginningResult = $stmtBeginning->get_result()->fetch_assoc();
    $countBeginning = (int)$beginningResult['count_beginning'];
    $costBeginning = (float)$beginningResult['cost_beginning'];

    // NEW DELIVERIES
    if ($branch === 'all') {
        $sqlNewDeliveries = "
            SELECT COUNT(*) as count_new, COALESCE(SUM(inventory_cost), 0) as cost_new
            FROM motorcycle_inventory
            WHERE date_delivered BETWEEN ? AND ?
            AND status != 'deleted'
            $categoryCondition
        ";
        $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
        $stmtNewDeliveries->bind_param('ss', $startDate, $endDate);
    } else {
        $sqlNewDeliveries = "
            SELECT COUNT(*) as count_new, COALESCE(SUM(inventory_cost), 0) as cost_new
            FROM motorcycle_inventory mi
            WHERE mi.date_delivered BETWEEN ? AND ?
            AND mi.status != 'deleted'
            AND (
                mi.current_branch = ?
                OR
                mi.id IN (
                    SELECT it.motorcycle_id 
                    FROM inventory_transfers it
                    WHERE it.from_branch = ?
                    AND it.transfer_date BETWEEN ? AND ?
                    AND it.transfer_status = 'completed'
                )
            )
            AND mi.id NOT IN (
                SELECT it.motorcycle_id 
                FROM inventory_transfers it
                WHERE it.to_branch = ?
                AND it.transfer_date BETWEEN ? AND ?
                AND it.transfer_status = 'completed'
            )
            $categoryCondition
        ";
        $stmtNewDeliveries = $conn->prepare($sqlNewDeliveries);
        $stmtNewDeliveries->bind_param('sssssssss', $startDate, $endDate, $branch, $branch, $startDate, $endDate, $branch, $startDate, $endDate);
    }
    $stmtNewDeliveries->execute();
    $newDeliveriesResult = $stmtNewDeliveries->get_result()->fetch_assoc();
    $countNewDeliveries = (int)$newDeliveriesResult['count_new'];
    $costNewDeliveries = (float)$newDeliveriesResult['cost_new'];

    // RECEIVED TRANSFERS
    if ($branch === 'all') {
        $sqlReceived = "
            SELECT COUNT(*) as count_received, COALESCE(SUM(mi.inventory_cost), 0) as cost_received
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.transfer_date BETWEEN ? AND ? 
            AND it.transfer_status = 'completed'
            $categoryCondition
        ";
        $stmtReceived = $conn->prepare($sqlReceived);
        $stmtReceived->bind_param('ss', $startDate, $endDate);
    } else {
        $sqlReceived = "
            SELECT COUNT(*) as count_received, COALESCE(SUM(mi.inventory_cost), 0) as cost_received
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.to_branch = ?
            AND it.transfer_date BETWEEN ? AND ? 
            AND it.transfer_status = 'completed'
            $categoryCondition
        ";
        $stmtReceived = $conn->prepare($sqlReceived);
        $stmtReceived->bind_param('sss', $branch, $startDate, $endDate);
    }
    $stmtReceived->execute();
    $receivedResult = $stmtReceived->get_result()->fetch_assoc();
    $countReceived = (int)$receivedResult['count_received'];
    $costReceived = (float)$receivedResult['cost_received'];

    // TOTAL IN
    $countIn = $countNewDeliveries + $countReceived;
    $costIn = $costNewDeliveries + $costReceived;

    // TRANSFERS OUT
    if ($branch === 'all') {
        $sqlTransfersOut = "
            SELECT COUNT(*) as count_transfers_out, COALESCE(SUM(mi.inventory_cost), 0) as cost_transfers_out
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.transfer_date BETWEEN ? AND ? 
            AND it.transfer_status = 'completed'
            $categoryCondition
        ";
        $stmtTransfersOut = $conn->prepare($sqlTransfersOut);
        $stmtTransfersOut->bind_param('ss', $startDate, $endDate);
    } else {
        $sqlTransfersOut = "
            SELECT COUNT(*) as count_transfers_out, COALESCE(SUM(mi.inventory_cost), 0) as cost_transfers_out
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.from_branch = ?
            AND it.transfer_date BETWEEN ? AND ? 
            AND it.transfer_status = 'completed'
            $categoryCondition
        ";
        $stmtTransfersOut = $conn->prepare($sqlTransfersOut);
        $stmtTransfersOut->bind_param('sss', $branch, $startDate, $endDate);
    }
    $stmtTransfersOut->execute();
    $transfersOutResult = $stmtTransfersOut->get_result()->fetch_assoc();
    $countTransfersOut = (int)$transfersOutResult['count_transfers_out'];
    $costTransfersOut = (float)$transfersOutResult['cost_transfers_out'];

    // SOLD DURING MONTH
    if ($branch === 'all') {
        $sqlSoldDuringMonth = "
            SELECT COUNT(*) as count_sold_month, COALESCE(SUM(mi.inventory_cost), 0) as cost_sold_month
            FROM motorcycle_inventory mi
            JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            WHERE ms.sale_date BETWEEN ? AND ?
            AND mi.status = 'sold'
            $categoryCondition
        ";
        $stmtSoldDuringMonth = $conn->prepare($sqlSoldDuringMonth);
        $stmtSoldDuringMonth->bind_param('ss', $startDate, $endDate);
    } else {
        $sqlSoldDuringMonth = "
            SELECT COUNT(*) as count_sold_month, COALESCE(SUM(mi.inventory_cost), 0) as cost_sold_month
            FROM motorcycle_inventory mi
            JOIN motorcycle_sales ms ON mi.id = ms.motorcycle_id
            WHERE mi.current_branch = ?
            AND ms.sale_date BETWEEN ? AND ?
            AND mi.status = 'sold'
            $categoryCondition
        ";
        $stmtSoldDuringMonth = $conn->prepare($sqlSoldDuringMonth);
        $stmtSoldDuringMonth->bind_param('sss', $branch, $startDate, $endDate);
    }
    $stmtSoldDuringMonth->execute();
    $soldDuringMonthResult = $stmtSoldDuringMonth->get_result()->fetch_assoc();
    $countSoldDuringMonth = (int)$soldDuringMonthResult['count_sold_month'];
    $costSoldDuringMonth = (float)$soldDuringMonthResult['cost_sold_month'];

    // TOTAL OUT
    $countOut = $countTransfersOut + $countSoldDuringMonth;
    $costOut = $costTransfersOut + $costSoldDuringMonth;

    // ENDING BALANCE CALCULATION
    $countEndingCalculated = $countBeginning + $countIn - $countOut;
    $costEndingCalculated = $costBeginning + $costIn - $costOut;

    // ACTUAL ENDING BALANCE
    if ($branch === 'all') {
        $sqlEndingActual = "
            SELECT COUNT(*) as count_ending, COALESCE(SUM(inventory_cost),0) as cost_ending
            FROM motorcycle_inventory
            WHERE status = 'available'
            AND date_delivered <= ?
            $categoryCondition
        ";
        $stmtEndingActual = $conn->prepare($sqlEndingActual);
        $stmtEndingActual->bind_param('s', $endDate);
    } else {
        $sqlEndingActual = "
            SELECT COUNT(*) as count_ending, COALESCE(SUM(inventory_cost),0) as cost_ending
            FROM motorcycle_inventory mi
            WHERE mi.current_branch = ? 
            AND mi.status = 'available'
            AND mi.date_delivered <= ?
            $categoryCondition
        ";
        $stmtEndingActual = $conn->prepare($sqlEndingActual);
        $stmtEndingActual->bind_param('ss', $branch, $endDate);
    }
    $stmtEndingActual->execute();
    $endingActualResult = $stmtEndingActual->get_result()->fetch_assoc();
    $countEndingActual = (int)$endingActualResult['count_ending'];
    $costEndingActual = (float)$endingActualResult['cost_ending'];

    // DETAILED DATA
    if ($branch === 'all') {
        $sqlData = "SELECT mi.*, i.invoice_number FROM motorcycle_inventory mi 
                    LEFT JOIN invoices i ON mi.invoice_id = i.id 
                    WHERE mi.status = 'available'
                    AND mi.date_delivered <= ?
                    $categoryCondition
                    ORDER BY mi.brand, mi.model";
        $stmtData = $conn->prepare($sqlData);
        $stmtData->bind_param('s', $endDate);
    } else {
        $sqlData = "SELECT mi.*, i.invoice_number FROM motorcycle_inventory mi 
                    LEFT JOIN invoices i ON mi.invoice_id = i.id 
                    WHERE mi.current_branch = ? 
                    AND mi.status = 'available'
                    AND mi.date_delivered <= ?
                    $categoryCondition
                    ORDER BY mi.brand, mi.model";
        $stmtData = $conn->prepare($sqlData);
        $stmtData->bind_param('ss', $branch, $endDate);
    }
    $stmtData->execute();
    $resultData = $stmtData->get_result();

    $data = [];
    while ($row = $resultData->fetch_assoc()) {
        $data[] = [
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
            'category' => $row['category']
        ];
    }

    // TRANSFER DETAILS
    if ($branch === 'all') {
        $sqlTransferDetails = "
            SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost,
                   'TRANSFER' as transfer_type
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE it.transfer_date BETWEEN ? AND ? 
            AND it.transfer_status = 'completed'
            $categoryCondition
            ORDER BY it.transfer_date DESC
        ";
        $stmtTransferDetails = $conn->prepare($sqlTransferDetails);
        $stmtTransferDetails->bind_param('ss', $startDate, $endDate);
    } else {
        $sqlTransferDetails = "
            SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost,
                   CASE 
                       WHEN it.from_branch = ? THEN 'OUT'
                       WHEN it.to_branch = ? THEN 'IN'
                       ELSE 'TRANSFER'
                   END as transfer_type
            FROM inventory_transfers it
            JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
            WHERE (it.from_branch = ? OR it.to_branch = ?)
            AND it.transfer_date BETWEEN ? AND ? 
            AND it.transfer_status = 'completed'
            $categoryCondition
            ORDER BY it.transfer_date DESC
        ";
        $stmtTransferDetails = $conn->prepare($sqlTransferDetails);
        $stmtTransferDetails->bind_param('ssssss', $branch, $branch, $branch, $branch, $startDate, $endDate);
    }
    $stmtTransferDetails->execute();
    $transferDetailsResult = $stmtTransferDetails->get_result();

    $transferDetails = [];
    while ($row = $transferDetailsResult->fetch_assoc()) {
        $transferDetails[] = $row;
    }

    // === Calculate discrepancies per model and branch ===

    // Aggregate actual inventory by model and branch
    $actualByModelBranch = [];
    foreach ($data as $item) {
        $key = $item['model'] . '||' . $item['current_branch'];
        if (!isset($actualByModelBranch[$key])) {
            $actualByModelBranch[$key] = ['count' => 0, 'cost' => 0];
        }
        $actualByModelBranch[$key]['count'] += 1;
        $actualByModelBranch[$key]['cost'] += $item['inventory_cost'];
    }

    // Initialize calculated inventory by model and branch
    $calculatedByModelBranch = [];

    // Add beginning balance per model and branch as zero (or you can extend to calculate if you have data)

    // Add IN (new deliveries + received transfers) per model and branch
    foreach ($transferDetails as $transfer) {
        $toBranch = $transfer['to_branch'] ?? '';
        $model = $transfer['model'] ?? '';
        $key = $model . '||' . $toBranch;
        if (!isset($calculatedByModelBranch[$key])) {
            $calculatedByModelBranch[$key] = ['count' => 0, 'cost' => 0];
        }
        if (in_array($transfer['transfer_type'], ['IN', 'TRANSFER'])) {
            $calculatedByModelBranch[$key]['count'] += 1;
            $calculatedByModelBranch[$key]['cost'] += (float)$transfer['inventory_cost'];
        }
    }

    // Subtract OUT (transfers out) per model and branch
    foreach ($transferDetails as $transfer) {
        $fromBranch = $transfer['from_branch'] ?? '';
        $model = $transfer['model'] ?? '';
        $key = $model . '||' . $fromBranch;
        if (!isset($calculatedByModelBranch[$key])) {
            $calculatedByModelBranch[$key] = ['count' => 0, 'cost' => 0];
        }
        if (in_array($transfer['transfer_type'], ['OUT', 'TRANSFER'])) {
            $calculatedByModelBranch[$key]['count'] -= 1;
            $calculatedByModelBranch[$key]['cost'] -= (float)$transfer['inventory_cost'];
        }
    }

    // Note: Sold motorcycles are not included here; you can extend similarly if you have sales data by model and branch

    // Calculate discrepancies per model and branch
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
        'month' => $month,
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
        'transfer_details' => $transferDetails,
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
            'cost_calculation' => number_format($costBeginning, 2) . " + " . number_format($costIn, 2) . " - " . number_format($costOut, 2) . " = " . number_format($costEndingCalculated, 2)
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

    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $branch = isset($_GET['branch']) ? strtolower(sanitizeInput($_GET['branch'])) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';

    if (empty($month)) {
        echo json_encode(['success' => false, 'message' => 'Month parameter is required']);
        return;
    }

    $startDate = date('Y-m-01', strtotime($month));
    $endDate = date('Y-m-t', strtotime($month));

    $categoryCondition = '';
    if ($category !== 'all') {
        $categoryCondition = " AND LOWER(mi.category) = '$category' ";
    }

    $branchCondition = ($branch === 'all') ? "1=1" : "it.from_branch = ?";

    $sql = "SELECT 
                mi.model, 
                mi.color, 
                mi.brand, 
                mi.engine_number, 
                mi.frame_number, 
                mi.inventory_cost,
                it.transfer_date,
                it.to_branch as transferred_to,
                it.from_branch as transferred_from,
                i.invoice_number
            FROM motorcycle_inventory mi
            INNER JOIN inventory_transfers it ON mi.id = it.motorcycle_id
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE $branchCondition
            AND it.transfer_date BETWEEN ? AND ?
            AND it.transfer_status = 'completed'
            $categoryCondition
            ORDER BY it.transfer_date DESC, mi.model";

    $stmt = $conn->prepare($sql);
    if ($branch === 'all') {
        $stmt->bind_param('ss', $startDate, $endDate);
    } else {
        $stmt->bind_param('sss', $branch, $startDate, $endDate);
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
        'branch' => $branch,
        'category' => $category,
        'summary' => [
            'total_transferred' => $totalTransferred,
            'total_inventory_cost' => $totalInventoryCost
        ]
    ]);
}

function getAvailableMotorcyclesReport() {
    global $conn;

    $brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : 'all';
    $branch = isset($_GET['branch']) ? strtolower(sanitizeInput($_GET['branch'])) : 'all';
    $category = isset($_GET['category']) ? strtolower(sanitizeInput($_GET['category'])) : 'all';

    $userBranch = isset($_SESSION['user_branch']) ? strtolower($_SESSION['user_branch']) : '';
    $userPosition = isset($_SESSION['position']) ? strtoupper($_SESSION['position']) : '';

    $sql = "SELECT mi.*, i.invoice_number 
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.status = 'available'";

    if ($brand !== 'all') {
        $sql .= " AND mi.brand = '$brand'";
    }

    if ($category !== 'all') {
        $sql .= " AND LOWER(mi.category) = '$category'";
    }

    // Handle branch filter
    if ($branch !== 'all') {
        $sql .= " AND mi.current_branch = '$branch'";
    } elseif (!empty($userBranch) && $userBranch !== 'headoffice' &&
        !in_array($userPosition, ['ADMIN', 'IT STAFF', 'HEAD'])) {
        // For non-admin users, default to their branch if no branch specified
        $sql .= " AND mi.current_branch = '$userBranch'";
    }

    $sql .= " ORDER BY mi.current_branch, mi.brand, mi.model";

    $result = $conn->query($sql);

    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching report data: ' . $conn->error]);
    }
}

function searchTransferReceipt() {
    global $conn;
    
    $transferInvoiceNumber = isset($_GET['transfer_invoice_number']) ? sanitizeInput($_GET['transfer_invoice_number']) : '';
    
    if (empty($transferInvoiceNumber)) {
        echo json_encode(['success' => false, 'message' => 'Transfer invoice number is required']);
        return;
    }
    
    $sql = "SELECT DISTINCT it.id, it.transfer_invoice_number, it.from_branch, it.to_branch, it.transfer_date
            FROM inventory_transfers it
            WHERE it.transfer_invoice_number LIKE ?
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

function getTransferReceipt() {
    global $conn;
    
    $transferId = isset($_GET['transfer_id']) ? intval($_GET['transfer_id']) : 0;
    
    if ($transferId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transfer ID']);
        return;
    }
    
    // Get transfer header information
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
    
    // Get motorcycle details for this transfer
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
function getInvoiceDetails() {
    global $conn;

    $invoiceId = isset($_GET['invoice_id']) ? intval($_GET['invoice_id']) : 0;
    
    if ($invoiceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
        return;
    }
    
    // Get invoice header information
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
    
    // Get motorcycles associated with this invoice
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
function getAllTransferHistories() {
    global $conn;

    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
    $offset = ($page - 1) * $perPage;

    // Optional filters
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : '';
    $model = isset($_GET['model']) ? sanitizeInput($_GET['model']) : '';
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : ''; // New status filter

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

    // Count query
    $countSql = "SELECT COUNT(*) as total FROM inventory_transfers it
                 JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id";

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


?>