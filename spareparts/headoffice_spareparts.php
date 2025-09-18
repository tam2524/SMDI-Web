<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin'];
$canDelete = in_array($userRole, $adminRoles); // MODIFIED: Check if user has deletion rights
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'>
    <title>SMDI - SPARE PARTS INVENTORY</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' rel='stylesheet'>
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css' rel='stylesheet'>
    <link href='../css/spareparts_inventory_style.css' rel='stylesheet'>
    <style>
        /* Custom styles for a more professional look */
        .stat-card {
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .nav-tabs .nav-link { font-weight: 500; }
        .nav-tabs .nav-link.active {
            background-color: var(--bs-primary);
            color: white;
            border-color: var(--bs-primary);
        }
        .table thead { background-color: #f8f9fa; }
        /* NEW: Style for zero-stock items */
        .stock-zero { background-color: #f8d7da !important; }
        /* NEW: Force modal text to be black */
        .modal-body, .modal-body label, .modal-body p, .modal-body span, .modal-body strong { color: #212529 !important; }
        .modal-body input, .modal-body select, .modal-body textarea { color: #212529 !important; }
        .modal-body .form-text { color: #6c757d !important; }
    </style>
</head>
<body>
    <div class='container-fluid fixed-top bg-white'>
        <div class='container topbar bg-primary d-none d-lg-block'>
            <div class='d-flex justify-content-between'>
                <div class='top-info ps-2'>
                    <small class='me-3'><i class='fas fa-map-marker-alt me-2 text-secondary'></i><a href='#' class='text-white'>1031, Victoria Building, Roxas Avenue, Roxas City, 5800</a></small>
                </div>
            </div>
        </div>
        <div class='container px-0'>
            <nav class='navbar navbar-light bg-white navbar-expand-lg'>
                <a class='navbar-brand'><img src='../assets/img/smdi_logo.jpg' alt='SMDI Logo' class='logo'></a>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarCollapse'><span class='navbar-toggler-icon'></span></button>
                <div class='collapse navbar-collapse' id='navbarCollapse'>
                    <div class='navbar-nav'>
                        <a href='headoffice_spareparts.php' class='nav-item nav-link active'>Home</a>
                        <a href='../api/logout.php' class='nav-item nav-link'>Logout</a>
                        <?php if (isset($_SESSION['username'])): ?>
                            <span class='nav-item nav-link' style='cursor: default; color: #dc3545;'><i class='bi bi-person-circle me-1'></i><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <main class='container-fluid py-5' style='margin-top: 110px;'>
        <div class='card shadow-sm mb-4'>
            <div class='card-header bg-light border-bottom'><h1 class='h5 mb-0 fw-semibold'>Spare Parts Inventory Management</h1></div>
            <div class='card-body p-4'>
                <ul class='nav nav-tabs mb-4' id='mainTabs' role='tablist'>
                    <li class='nav-item' role='presentation'><button class='nav-link active' id='dashboard-tab' data-bs-toggle='tab' data-bs-target='#dashboard' type='button'><i class="bi bi-bar-chart-line-fill me-2"></i>Dashboard</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='inventory-tab' data-bs-toggle='tab' data-bs-target='#inventory' type='button'><i class="bi bi-box-seam me-2"></i>Inventory</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='sales-tab' data-bs-toggle='tab' data-bs-target='#sales' type='button'><i class="bi bi-cash-stack me-2"></i>Sales</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='payments-tab' data-bs-toggle='tab' data-bs-target='#payments' type='button'><i class="bi bi-credit-card me-2"></i>Payments</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='transfer-tab' data-bs-toggle='tab' data-bs-target='#transfer' type='button'><i class="bi bi-truck me-2"></i>Outgoing Transfers</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='incoming-transfer-tab' data-bs-toggle='tab' data-bs-target='#incoming-transfer' type='button'><i class="bi bi-box-arrow-in-down me-2"></i>Incoming Transfers <span id="incoming-badge" class="badge bg-danger ms-1 d-none"></span></button></li>
                </ul>

                <div class='tab-content' id='mainTabContent'>
                    <div class='tab-pane fade show active' id='dashboard' role='tabpanel'>
                        <h4 class="mb-4">Branch Statistics Overview</h4>
                        <div id="dashboard-loader" class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
                        <div id="dashboard-content" class="d-none">
                            <div class='row g-4'>
                                <div class='col-lg-2 col-md-4'><div class='card stat-card h-100 border-start border-4 border-primary'><div class='card-body'><h6 class='card-title text-muted'>Total Qty of Inventory</h6><h4 class='card-text fw-bold' id='stat-total-qty'>-</h4></div></div></div>
                                <div class='col-lg-2 col-md-4'><div class='card stat-card h-100 border-start border-4 border-info'><div class='card-body'><h6 class='card-title text-muted'>Total Inventory Value</h6><h4 class='card-text fw-bold' id='stat-inventory-value'>-</h4></div></div></div>
                                <div class='col-lg-2 col-md-4'><div class='card stat-card h-100 border-start border-4 border-success'><div class='card-body'><h6 class='card-title text-muted'>Sales (This Month)</h6><h4 class='card-text fw-bold' id='stat-monthly-sales'>-</h4></div></div></div>
                                <div class='col-lg-2 col-md-4'><div class='card stat-card h-100 border-start border-4 border-secondary'><div class='card-body'><h6 class='card-title text-muted'>Sales (This Year)</h6><h4 class='card-text fw-bold' id='stat-yearly-sales'>-</h4></div></div></div>
                                <div class='col-lg-2 col-md-4'><div class='card stat-card h-100 border-start border-4 border-danger'><div class='card-body'><h6 class='card-title text-muted'>Outstanding Balance</h6><h4 class='card-text fw-bold' id='stat-outstanding-balance'>-</h4></div></div></div>
                                <div class='col-lg-2 col-md-4'><div class='card stat-card h-100 border-start border-4 border-warning'><div class='card-body'><h6 class='card-title text-muted'>Total Accounts</h6><h4 class='card-text fw-bold' id='stat-total-accounts'>-</h4></div></div></div>
                            </div>
                        </div>
                    </div>

                    <div class='tab-pane fade' id='inventory' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-3'>
                            <h4 class='mb-0'>Inventory Management</h4>
                            <div>
                                <button class='btn btn-outline-secondary' id="printInventoryBtn"><i class='bi bi-printer me-2'></i>Print</button>
                                <button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#addPartsInModal'><i class='bi bi-plus-circle me-2'></i>Add Stock (IN)</button>
                            </div>
                        </div>
                        <input type='text' id='inventorySearch' class='form-control mb-3' style="max-width: 300px;" placeholder='Search parts...'>
                        <div class='table-responsive'><table class='table table-hover' id='inventoryTable'><thead><tr><th>Part No</th><th>Description</th><th>Stock</th><th>Cost</th><th>Price</th><th>Total Value</th><th>Status</th><th>Actions</th></tr></thead><tbody id='inventoryTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='sales' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-3'>
                             <h4 class='mb-0'>Sales Management</h4>
                             <div>
                                <button class='btn btn-outline-secondary' id="printSalesBtn"><i class='bi bi-printer me-2'></i>Print</button>
                                <button class='btn btn-success' data-bs-toggle='modal' data-bs-target='#sellPartsOutModal'><i class='bi bi-cart-plus me-2'></i>Record Sale</button>
                            </div>
                        </div>
                        <input type='text' id='salesSearch' class='form-control mb-3' style="max-width: 300px;" placeholder='Search by customer, part no, or OR...'>
                        <div class='table-responsive'><table class='table table-hover' id='salesTable'><thead><tr><th>Date</th><th>Customer</th><th>OR #</th><th>Total</th><th>Type</th><th>Balance</th><th>Actions</th></tr></thead><tbody id='salesTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='payments' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-3'>
                            <h4 class='mb-0'>Payment Management</h4>
                            <div>
                                <button class='btn btn-outline-secondary' id="printPaymentsBtn"><i class='bi bi-printer me-2'></i>Print</button>
                                <button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#recordPaymentModal'><i class='bi bi-cash-coin me-2'></i>Record Payment</button>
                            </div>
                        </div>
                        <input type='text' id='paymentsSearch' class='form-control mb-3' style="max-width: 300px;" placeholder='Search by customer or OR...'>
                        <div class='table-responsive'><table class='table table-hover' id='paymentsTable'><thead><tr><th>Date</th><th>Customer</th><th>Amount Paid</th><th>Original OR #</th><th>Actions</th></tr></thead><tbody id='paymentsTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='transfer' role='tabpanel'>
                         <div class='d-flex justify-content-between align-items-center mb-3'>
                            <h4 class='mb-0'>Outgoing Inventory Transfers</h4>
                            <div>
                                <button class='btn btn-outline-secondary' id="printTransfersBtn"><i class='bi bi-printer me-2'></i>Print</button>
                                <button class='btn btn-info' data-bs-toggle='modal' data-bs-target='#transferPartsModal'><i class='bi bi-truck me-2'></i>New Transfer</button>
                            </div>
                        </div>
                        <input type='text' id='transfersSearch' class='form-control mb-3' style="max-width: 300px;" placeholder='Search by part no or branch...'>
                        <div class='table-responsive'><table class='table table-hover' id='transfersTable'><thead><tr><th>Date</th><th>Part No</th><th>Qty</th><th>From</th><th>To</th><th>Status</th><th>Actions</th></tr></thead><tbody id='transfersTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='incoming-transfer' role='tabpanel'>
                         <div class='d-flex justify-content-between align-items-center mb-3'>
                            <h4 class='mb-0'>Incoming Inventory Transfers</h4>
                        </div>
                        <p class="text-muted">These are items transferred to your branch that are awaiting your confirmation to be added to your inventory.</p>
                        <div class='table-responsive'><table class='table table-hover' id='incomingTransfersTable'><thead><tr><th>Date</th><th>From Branch</th><th>Total Items</th><th>Actions</th></tr></thead><tbody id='incomingTransfersTableBody'></tbody></table></div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <div class='modal fade' id='addPartsInModal' tabindex='-1' aria-hidden='true'><div class='modal-dialog modal-lg'><div class='modal-content'><form id='addPartsInForm'><div class='modal-header'><h5 class='modal-title'><i class="bi bi-box-arrow-in-down me-2"></i>Record Incoming Stock</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'></div><div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-primary'>Save Stock</button></div></form></div></div></div>
    <div class='modal fade' id='editPartModal' tabindex='-1' aria-hidden='true'><div class='modal-dialog'><div class='modal-content'><form id='editPartForm'><div class='modal-header'><h5 class='modal-title'><i class="bi bi-pencil-square me-2"></i>Edit Part Details</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'></div><div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-primary'>Save Changes</button></div></form></div></div></div>
    
    <div class='modal fade' id='sellPartsOutModal' tabindex='-1' aria-labelledby='sellPartsOutModalLabel' aria-hidden='true'>
        <div class='modal-dialog modal-lg'> <div class='modal-content'>
            <form id='sellPartsOutForm'>
                <div class='modal-header bg-success text-white'><h5 class='modal-title' id='sellPartsOutModalLabel'><i class="bi bi-cart-plus me-2"></i>Record New Sale</h5><button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button></div>
                <div class='modal-body p-4'>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label for='out_or_number' class='form-label'>Official Receipt No.</label><input type='text' class='form-control' id='out_or_number' required></div>
                        <div class="col-md-6"><label for='out_customer_name' class='form-label'>Customer Name</label><input type='text' class='form-control' id='out_customer_name' required></div>
                        <div class="col-md-6"><label for='out_date' class='form-label'>Date of Sale</label><input type='date' class='form-control' id='out_date' required></div>
                        <div class="col-md-6"><label for='out_transaction_type' class='form-label'>Payment Type</label><select class='form-select' id='out_transaction_type' required><option value='cash' selected>Cash</option><option value='installment'>Installment</option></select></div>
                    </div>
                    <input type="text" id="salePartSearchInput" class="form-control mb-2" placeholder="Enter Part No. or Description to add items...">
                    <div id="salePartSearchResults" class="list-group border rounded mb-4" style="max-height: 150px; overflow-y: auto;"></div>
                    <h6 class="mb-2">Sale Items</h6>
                    <div class="table-responsive border rounded"><table class="table table-striped table-sm mb-0"><thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Subtotal</th><th></th></tr></thead><tbody id="partsForSaleList"><tr id="emptySaleListRow"><td colspan="5" class="text-center text-muted p-4">Cart is empty</td></tr></tbody></table></div>
                    <div class="d-flex justify-content-end mt-3"><div style="width: 320px;"><hr><div class="d-flex justify-content-between fw-bold fs-5 text-success"><span>Grand Total:</span><span id="pos-grand-total">₱0.00</span></div></div></div>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-success'><i class="bi bi-check-circle me-2"></i>Confirm Sale</button></div>
            </form>
        </div></div>
    </div>

    <div class='modal fade' id='recordPaymentModal' tabindex='-1' aria-labelledby='recordPaymentModalLabel' aria-hidden='true'>
        <div class='modal-dialog'> <div class='modal-content'>
            <form id='recordPaymentForm'>
                <div class='modal-header bg-primary text-white'><h5 class='modal-title' id='recordPaymentModalLabel'><i class="bi bi-cash-coin me-2"></i>Record Payment</h5><button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button></div>
                <div class='modal-body p-4'>
                    <div class='mb-3'><label for='payment_customer_search' class='form-label'>Search Customer with Balance</label><input type='text' class='form-control' id='payment_customer_search' placeholder="Type customer name..."><div id="customerSearchResults" class="list-group mt-1"></div></div>
                    <div class='mb-3'><label for='payment_or_number' class='form-label'>Original Sale OR Number</label><input type='text' class='form-control' id='payment_or_number' required><small id="balance_info" class="form-text text-muted">Enter the OR number from the installment sale.</small></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label for='payment_date' class='form-label'>Payment Date</label><input type='date' class='form-control' id='payment_date' required></div>
                        <div class="col-md-6"><label for='payment_amount' class='form-label'>Amount Paid</label><div class="input-group"><span class="input-group-text">₱</span><input type='number' step='0.01' class='form-control' id='payment_amount' min='0' required></div></div>
                    </div>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-primary'><i class="bi bi-check-lg me-2"></i>Record Payment</button></div>
            </form>
        </div></div>
    </div>

    <div class='modal fade' id='transferPartsModal' tabindex='-1' aria-hidden='true'><div class='modal-dialog modal-lg'><div class='modal-content'><form id='transferPartsForm'><div class='modal-header'><h5 class='modal-title'><i class="bi bi-truck me-2"></i>New Transfer</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'></div><div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-info'>Submit Transfer</button></div></form></div></div></div>

    <div class='modal fade' id='receiptModal' tabindex='-1'><div class='modal-dialog'><div class='modal-content'><div class='modal-header'><h5 class='modal-title' id="receiptTitle"></h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body' id='receiptBody'></div><div class='modal-footer'><button type='button' class='btn btn-secondary no-print' data-bs-dismiss='modal'>Close</button><button type='button' class='btn btn-primary no-print' onclick="window.print();">Print</button></div></div></div></div>
    <div class='modal fade' id='viewIncomingTransferModal' tabindex='-1'><div class='modal-dialog modal-lg'><div class='modal-content'><div class='modal-header'><h5 class='modal-title'>Incoming Transfer Details</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body' id='incomingTransferDetailsBody'></div><div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button><button type='button' class='btn btn-success' id="confirmReceiveBtn">Confirm and Receive Items</button></div></div></div></div>

    <div class='modal fade' id='successModal' tabindex='-1'><div class='modal-dialog'><div class='modal-content'><div class='modal-header bg-success text-white'><h5 class='modal-title'>Success</h5><button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button></div><div class='modal-body'><p id='successMessage'></p></div><div class='modal-footer'><button type='button' class='btn btn-success' data-bs-dismiss='modal'>OK</button></div></div></div></div>
    <div class='modal fade' id='errorModal' tabindex='-1'><div class='modal-dialog'><div class='modal-content'><div class='modal-header bg-danger text-white'><h5 class='modal-title'>Error</h5><button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button></div><div class='modal-body'><p id='errorMessage'></p></div><div class='modal-footer'><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>OK</button></div></div></div></div>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

    <script>
        // NEW: Pass authorization status to JS
        const canDelete = <?php echo json_encode($canDelete); ?>;
        const currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
    </script>
    <script src='../js/spareparts_inventory.js'></script>
</body>
</html>