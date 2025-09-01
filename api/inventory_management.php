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

    $id = isset( $_GET[ 'id' ] ) ? intval( $_GET[ 'id' ] ) : 0;

    $stmt = $conn->prepare( "SELECT mi.*, i.invoice_number 
                           FROM motorcycle_inventory mi 
                           LEFT JOIN invoices i ON mi.invoice_id = i.id 
                           WHERE mi.id = ?" );
    $stmt->bind_param( 'i', $id );
    $stmt->execute();
    $result = $stmt->get_result();

    if ( $result->num_rows > 0 ) {
        $data = $result->fetch_assoc();

        if ( $data[ 'status' ] === 'transferred' ) {
            $transferStmt = $conn->prepare( "SELECT * FROM inventory_transfers 
                                          WHERE motorcycle_id = ? 
                                          ORDER BY transfer_date DESC" );
            $transferStmt->bind_param( 'i', $id );
            $transferStmt->execute();
            $transferResult = $transferStmt->get_result();

            $transfers = [];
            while ( $row = $transferResult->fetch_assoc() ) {
                $transfers[] = $row;
            }

            $data[ 'transfer_history' ] = $transfers;
        }

        echo json_encode( [ 'success' => true, 'data' => $data ] );
    } else {
        echo json_encode( [ 'success' => false, 'message' => 'Motorcycle not found' ] );
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
    $invoiceNumber = sanitizeInput( $_POST[ 'invoice_number' ] );
    $color = sanitizeInput( $_POST[ 'color' ] );
    $inventory_cost = !empty( $_POST[ 'inventory_cost' ] ) ? floatval( $_POST[ 'inventory_cost' ] ) : null;
    $currentBranch = sanitizeInput( $_POST[ 'current_branch' ] );
    $status = sanitizeInput( $_POST[ 'status' ] );

    $conn->begin_transaction();

    try {
        // Enhanced duplicate checking for updates (excluding current record)
        $engineCheckStmt = $conn->prepare( "SELECT id, engine_number FROM motorcycle_inventory 
                                          WHERE engine_number = ? AND id != ?" );
        $engineCheckStmt->bind_param( 'si', $engineNumber, $id );
        $engineCheckStmt->execute();
        $engineCheckResult = $engineCheckStmt->get_result();
        
        if ( $engineCheckResult->num_rows > 0 ) {
            $duplicateRow = $engineCheckResult->fetch_assoc();
            throw new Exception( "DUPLICATE_ENGINE_NUMBER: Engine number '$engineNumber' already exists in another motorcycle (ID: " . $duplicateRow[ 'id' ] . ")" );
        }

        $frameCheckStmt = $conn->prepare( "SELECT id, frame_number FROM motorcycle_inventory 
                                         WHERE frame_number = ? AND id != ?" );
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
            // Check if invoice already exists
            $checkInvoiceStmt = $conn->prepare( 'SELECT id FROM invoices WHERE invoice_number = ?' );
            $checkInvoiceStmt->bind_param( 's', $invoiceNumber );
            $checkInvoiceStmt->execute();
            $existingInvoiceResult = $checkInvoiceStmt->get_result();
            
            if ( $existingInvoiceResult->num_rows > 0 ) {
                // Use existing invoice ID
                $existingInvoice = $existingInvoiceResult->fetch_assoc();
                $invoiceId = $existingInvoice['id'];
                $isExistingInvoice = true;
                $invoiceMessage = " (linked to existing invoice #$invoiceNumber)";
                error_log("INFO: Using existing invoice ID $invoiceId for invoice number: $invoiceNumber");
            } else {
                // Create new invoice
                $invoiceStmt = $conn->prepare( 'INSERT INTO invoices (invoice_number, date_delivered, notes) VALUES (?, ?, ?)' );
                $notes = "Updated motorcycle record";
                $invoiceStmt->bind_param( 'sss', $invoiceNumber, $dateDelivered, $notes );
                
                if ( $invoiceStmt->execute() ) {
                    $invoiceId = $conn->insert_id;
                    $invoiceMessage = " (created new invoice #$invoiceNumber)";
                    error_log("INFO: Created new invoice ID $invoiceId for invoice number: $invoiceNumber");
                } else {
                    throw new Exception( 'Error creating new invoice: ' . $invoiceStmt->error );
                }
            }
        }

        // Updated UPDATE statement to include category and invoice_id
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

        if ( $stmt->execute() ) {
            $conn->commit();
            
            // Provide different messages based on invoice handling
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
        } else {
            throw new Exception( 'Error updating motorcycle: ' . $stmt->error );
        }

    } catch ( Exception $e ) {
        $conn->rollback();
        
        // Log error to console instead of showing to user for certain errors
        $errorMessage = $e->getMessage();
        error_log("ERROR in updateMotorcycle(): " . $errorMessage);
        
        // Only show user-friendly errors, log technical errors
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
                                      VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)" );

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
            AND t.transfer_status = 'pending'
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
    $currentDate = date('Y-m-d H:i:s');

    $conn->begin_transaction();

    try {
        // Get transfer details before updating
        $getTransfersStmt = $conn->prepare("SELECT motorcycle_id, to_branch, from_branch FROM inventory_transfers 
                                           WHERE id IN ($placeholders) AND transfer_status = 'pending'");
        $getTransfersStmt->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        $getTransfersStmt->execute();
        $transfersResult = $getTransfersStmt->get_result();

        $motorcycleUpdates = [];
        while ($row = $transfersResult->fetch_assoc()) {
            $motorcycleUpdates[] = $row;
        }

        if (empty($motorcycleUpdates)) {
            throw new Exception('No pending transfers found with the provided IDs');
        }

        // Verify that the transfers are actually for the current branch
        foreach ($motorcycleUpdates as $update) {
            if ($update['to_branch'] !== $currentBranch) {
                throw new Exception('Transfer destination does not match current branch');
            }
        }

        // Update transfer status to completed with date_received
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

        // Update motorcycles - Change current_branch and status back to 'available'
        foreach ($motorcycleUpdates as $update) {
            if ($hasDateReceivedColumn) {
                // Update with date_received if column exists
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available', date_received = ?
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('ssi', $update['to_branch'], $currentDate, $update['motorcycle_id']);
            } else {
                // Update without date_received if column doesn't exist
                $updateMotorcycle = $conn->prepare("UPDATE motorcycle_inventory 
                                                  SET current_branch = ?, status = 'available'
                                                  WHERE id = ?");
                $updateMotorcycle->bind_param('si', $update['to_branch'], $update['motorcycle_id']);
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
                                           WHERE id IN ($placeholders) AND transfer_status = 'pending'");
        $getTransfersStmt->bind_param(str_repeat('i', count($transferIds)), ...$transferIds);
        $getTransfersStmt->execute();
        $transfersResult = $getTransfersStmt->get_result();

        $motorcycleUpdates = [];
        while ($row = $transfersResult->fetch_assoc()) {
            $motorcycleUpdates[] = $row;
        }

        if (empty($motorcycleUpdates)) {
            throw new Exception('No pending transfers found with the provided IDs');
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

    if (empty($month)) {
        echo json_encode(['success' => false, 'message' => 'Month parameter is required']);
        return;
    }

    $startDate = date('Y-m-01', strtotime($month));
    $endDate   = date('Y-m-t', strtotime($month));

    // Handle "all" branches case
    $branchCondition = ($branch === 'all') ? "1=1" : "current_branch = ?";
    $branchParamType = ($branch === 'all') ? "" : "s";
    
    // 1. Get current branch motorcycles count and cost (including sold for IN calculation)
    $sqlCurrent = "
        SELECT COUNT(*) as count_current, COALESCE(SUM(inventory_cost), 0) as cost_current
        FROM motorcycle_inventory 
        WHERE $branchCondition AND status != 'deleted'
    ";
    $stmtCurrent = $conn->prepare($sqlCurrent);
    if ($branch !== 'all') {
        $stmtCurrent->bind_param($branchParamType, $branch);
    }
    $stmtCurrent->execute();
    $currentResult = $stmtCurrent->get_result()->fetch_assoc();
    $countCurrent = (int)$currentResult['count_current'];
    $costCurrent = (float)$currentResult['cost_current'];

    // 2. Get transfers from branch count and total cost
    $transfersCondition = ($branch === 'all') ? "1=1" : "it.from_branch = ?";
    $sqlTransfers = "
        SELECT COUNT(*) as count_transfers, COALESCE(SUM(mi.inventory_cost), 0) as cost_transfers
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
        WHERE $transfersCondition 
        AND it.transfer_date BETWEEN ? AND ? 
        AND it.transfer_status = 'completed'
    ";
    $stmtTransfers = $conn->prepare($sqlTransfers);
    if ($branch === 'all') {
        $stmtTransfers->bind_param('ss', $startDate, $endDate);
    } else {
        $stmtTransfers->bind_param('sss', $branch, $startDate, $endDate);
    }
    $stmtTransfers->execute();
    $transfersResult = $stmtTransfers->get_result()->fetch_assoc();
    $countTransfers = (int)$transfersResult['count_transfers'];
    $costTransfers = (float)$transfersResult['cost_transfers'];

    // 3. Calculate IN: current branch count + transfers from count
    $countIn = $countCurrent + $countTransfers;
    $costIn = $costCurrent + $costTransfers;

    // 4. Calculate OUT: only transfers from branch during the month
    $countOut = $countTransfers;
    $costOut = $costTransfers;

    // 5. Calculate ENDING = motorcycles with current_branch = branch AND status = 'available'
    $sqlEnding = "
        SELECT COUNT(*) as count_ending, COALESCE(SUM(inventory_cost),0) as cost_ending
        FROM motorcycle_inventory
        WHERE $branchCondition AND status = 'available'
    ";
    $stmtEnding = $conn->prepare($sqlEnding);
    if ($branch !== 'all') {
        $stmtEnding->bind_param($branchParamType, $branch);
    }
    $stmtEnding->execute();
    $endingResult = $stmtEnding->get_result()->fetch_assoc();
    $countEnding = (int)$endingResult['count_ending'];
    $costEnding = (float)$endingResult['cost_ending'];

    // 6. Get detailed data for current branch motorcycles (EXCLUDE SOLD MODELS)
    $sqlData = "SELECT * FROM motorcycle_inventory WHERE $branchCondition AND status = 'available'";
    $stmtData = $conn->prepare($sqlData);
    if ($branch !== 'all') {
        $stmtData->bind_param($branchParamType, $branch);
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
            'date_delivered' => $row['date_delivered']
        ];
    }

    // 7. Get transfer details for the month (only include available motorcycles)
    $transferDetailsCondition = ($branch === 'all') ? "1=1" : "it.from_branch = ?";
    $sqlTransferDetails = "
        SELECT it.*, mi.brand, mi.model, mi.engine_number, mi.frame_number, mi.inventory_cost
        FROM inventory_transfers it
        JOIN motorcycle_inventory mi ON it.motorcycle_id = mi.id
        WHERE $transferDetailsCondition 
        AND it.transfer_date BETWEEN ? AND ? 
        AND it.transfer_status = 'completed'
        AND mi.status = 'available'
        ORDER BY it.transfer_date DESC
    ";
    $stmtTransferDetails = $conn->prepare($sqlTransferDetails);
    if ($branch === 'all') {
        $stmtTransferDetails->bind_param('ss', $startDate, $endDate);
    } else {
        $stmtTransferDetails->bind_param('sss', $branch, $startDate, $endDate);
    }
    $stmtTransferDetails->execute();
    $transferDetailsResult = $stmtTransferDetails->get_result();

    $transferDetails = [];
    while ($row = $transferDetailsResult->fetch_assoc()) {
        $transferDetails[] = $row;
    }

    // 8. Get sold count and cost for breakdown (but don't include in data table)
    $sqlSold = "
        SELECT COUNT(*) as count_sold, COALESCE(SUM(inventory_cost), 0) as cost_sold
        FROM motorcycle_inventory 
        WHERE $branchCondition AND status = 'sold'
    ";
    $stmtSold = $conn->prepare($sqlSold);
    if ($branch !== 'all') {
        $stmtSold->bind_param($branchParamType, $branch);
    }
    $stmtSold->execute();
    $soldResult = $stmtSold->get_result()->fetch_assoc();
    $countSold = (int)$soldResult['count_sold'];
    $costSold = (float)$soldResult['cost_sold'];

    $response = [
        'success' => true,
        'data' => $data, // This now only includes available motorcycles
        'month' => $month,
        'branch' => $branch,
        'summary' => [
            'in' => $countIn,
            'out' => $countOut,
            'ending' => $countEnding,
            'inventory_cost' => [
                'in' => $costIn,
                'out' => $costOut,
                'ending' => $costEnding
            ],
            'breakdown' => [
                'current_branch_count' => $countCurrent,
                'current_branch_cost' => $costCurrent,
                'available_count' => $countEnding, // Same as ending count
                'sold_count' => $countSold,
                'sold_cost' => $costSold,
                'transfers_from_count' => $countTransfers,
                'transfers_from_cost' => $costTransfers
            ]
        ],
        'transfer_details' => $transferDetails,
        'inventory_cost_formatted' => [
            'in' => number_format($costIn, 2),
            'out' => number_format($costOut, 2),
            'ending' => number_format($costEnding, 2),
            'current_branch' => number_format($costCurrent, 2),
            'sold' => number_format($costSold, 2),
            'transfers_from' => number_format($costTransfers, 2)
        ]
    ];

    echo json_encode($response);
}
function getMonthlyTransferredSummary() {
    global $conn;

    $month = isset($_GET['month']) ? sanitizeInput($_GET['month']) : '';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';

    if (empty($month)) {
        echo json_encode(['success' => false, 'message' => 'Month parameter is required']);
        return;
    }

    // Date range for the selected month
    $startDate = date('Y-m-01', strtotime($month));
    $endDate = date('Y-m-t', strtotime($month));

    // Handle "all" branches case
    $branchCondition = ($branch === 'all') ? "1=1" : "it.from_branch = ?";
    $branchParamType = ($branch === 'all') ? "" : "s";

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
        'summary' => [
            'total_transferred' => $totalTransferred,
            'total_inventory_cost' => $totalInventoryCost
        ]
    ]);
}

function getAvailableMotorcyclesReport() {
    global $conn;
    
    $brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : 'all';
    $branch = isset($_GET['branch']) ? sanitizeInput($_GET['branch']) : 'all';
    
    $userBranch = isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] : '';
    $userPosition = isset($_SESSION['position']) ? $_SESSION['position'] : '';
    
    $sql = "SELECT mi.*, i.invoice_number 
            FROM motorcycle_inventory mi
            LEFT JOIN invoices i ON mi.invoice_id = i.id
            WHERE mi.status = 'available'";
    
    if ($brand !== 'all') {
        $sql .= " AND mi.brand = '$brand'";
    }
    
    // Handle branch filter
    if ($branch !== 'all') {
        $sql .= " AND mi.current_branch = '$branch'";
    } elseif (!empty($userBranch) && $userBranch !== 'HEADOFFICE' &&
        !in_array(strtoupper($userPosition), ['ADMIN', 'IT STAFF', 'HEAD'])) {
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
?>