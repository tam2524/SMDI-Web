<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['user_role'] ?? 'user';
// Define roles that can see the "All Branches" report option
$adminRoles = ['Admin', 'Head', 'itsuperadmin'];
$canViewAllBranches = in_array($userRole, $adminRoles);
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='utf-8'>
    <title>SMDI - SPARE PARTS INVENTORY</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' rel='stylesheet'>
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css' rel='stylesheet'>
    <style>
        .card-header { font-weight: bold; }
        .table thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; }
        .modal-body { max-height: 80vh; overflow-y: auto; }
        .stat-card { border-left: 5px solid; }
        .stat-card.border-primary { border-color: #0d6efd !important; }
        .stat-card.border-success { border-color: #198754 !important; }
        .stat-card.border-danger { border-color: #dc3545 !important; }
        .stat-card.border-warning { border-color: #ffc107 !important; }
        #partSearchResults { max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; }
        #partSearchResults .list-group-item { cursor: pointer; }
        #partSearchResults .list-group-item:hover { background-color: #f0f0f0; }
    </style>
</head>
<body>

    <div class='container-fluid fixed-top bg-white'>
        <div class='container px-0'>
            <nav class='navbar navbar-light bg-white navbar-expand-lg'>
                <a class='navbar-brand'><img src='../assets/img/smdi_logo.jpg' alt='SMDI Logo' class='logo'></a>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarCollapse'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='navbarCollapse'>
                    <div class='navbar-nav'>
                        <a href='headoffice_spareparts.php' class='nav-item nav-link active'>Home</a>
                        <a href='../api/logout.php' class='nav-item nav-link active'>Logout</a>
                        <?php if (isset($_SESSION['username'])): ?>
                            <span class='nav-item nav-link disabled' style='cursor: default; color: red;'>
                                <i class='fas fa-user-circle me-1'></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <main class='container-fluid py-5' style='margin-top: 80px;'>
        <div class='card mb-4'>
            <div class='card-header bg-white'>
                <h1 class='h5 mb-0'>Spare Parts Inventory Management</h1>
            </div>
            <div class='card-body'>
                <ul class='nav nav-tabs mb-4' id='mainTabs' role='tablist'>
                    <li class='nav-item' role='presentation'><button class='nav-link active' id='dashboard-tab' data-bs-toggle='tab' data-bs-target='#dashboard' type='button'>📊 Dashboard</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='inventory-tab' data-bs-toggle='tab' data-bs-target='#inventory' type='button'>📦 Inventory</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='sales-tab' data-bs-toggle='tab' data-bs-target='#sales' type='button'>💰 Sales</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='payments-tab' data-bs-toggle='tab' data-bs-target='#payments' type='button'>💳 Payments</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='transfer-tab' data-bs-toggle='tab' data-bs-target='#transfer' type='button'>🚚 Transfers</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link' id='reports-tab' data-bs-toggle='tab' data-bs-target='#reports' type='button'>📄 Reports</button></li>
                </ul>

                <div class='tab-content' id='mainTabContent'>
                    <div class='tab-pane fade show active' id='dashboard' role='tabpanel'>
                        <h4>Branch Statistics Overview</h4>
                        <div id="dashboard-loader" class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
                        <div id="dashboard-content" class="d-none">
                            <div class='row mb-4'>
                                <div class='col-lg-3 col-md-6 mb-3'><div class='card stat-card border-primary h-100'><div class='card-body'><h6 class='card-title text-muted'>Total Inventory Value</h6><h4 class='card-text' id='stat-inventory-value'>-</h4></div></div></div>
                                <div class='col-lg-3 col-md-6 mb-3'><div class='card stat-card border-success h-100'><div class='card-body'><h6 class='card-title text-muted'>Sales (This Month)</h6><h4 class='card-text' id='stat-monthly-sales'>-</h4></div></div></div>
                                <div class='col-lg-3 col-md-6 mb-3'><div class='card stat-card border-danger h-100'><div class='card-body'><h6 class='card-title text-muted'>Outstanding Balance</h6><h4 class='card-text' id='stat-outstanding-balance'>-</h4></div></div></div>
                                <div class='col-lg-3 col-md-6 mb-3'><div class='card stat-card border-warning h-100'><div class='card-body'><h6 class='card-title text-muted'>Low Stock Items</h6><h4 class='card-text' id='stat-low-stock'>-</h4></div></div></div>
                            </div>
                        </div>
                    </div>

                    <div class='tab-pane fade' id='inventory' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-3'><h4 class='mb-0'>Inventory Management</h4><button class='btn btn-primary text-white' data-bs-toggle='modal' data-bs-target='#addPartsInModal'><i class='bi bi-plus-circle'></i> Add Stock (IN)</button></div>
                        <input type='text' id='inventorySearch' class='form-control w-25 mb-3' placeholder='Search parts...'>
                        <div class='table-responsive'><table class='table table-striped' id='inventoryTable'><thead><tr><th>Part No</th><th>Description</th><th>Stock</th><th>Cost</th><th>Price</th><th>Total Value</th><th>Status</th><th>Actions</th></tr></thead><tbody id='inventoryTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='sales' role='tabpanel'>
                         <div class='d-flex justify-content-between align-items-center mb-3'><h4 class='mb-0'>Sales Management</h4><button class='btn btn-success text-white' data-bs-toggle='modal' data-bs-target='#sellPartsOutModal'><i class='bi bi-currency-dollar'></i> Record Sale</button></div>
                        <input type='text' id='salesSearch' class='form-control w-25 mb-3' placeholder='Search by customer, part no, or OR...'>
                        <div class='table-responsive'><table class='table table-striped' id='salesTable'><thead><tr><th>Date</th><th>Part No</th><th>Qty</th><th>Total</th><th>Customer</th><th>OR #</th><th>Type</th></tr></thead><tbody id='salesTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='payments' role='tabpanel'>
                         <div class='d-flex justify-content-between align-items-center mb-3'><h4 class='mb-0'>Payment Management</h4><button class='btn btn-warning text-white' data-bs-toggle='modal' data-bs-target='#recordPaymentModal'><i class='bi bi-cash'></i> Record Payment</button></div>
                        <input type='text' id='paymentsSearch' class='form-control w-25 mb-3' placeholder='Search by customer or OR...'>
                        <div class='table-responsive'><table class='table table-striped' id='paymentsTable'><thead><tr><th>Date</th><th>Customer</th><th>Amount</th><th>Original OR #</th></tr></thead><tbody id='paymentsTableBody'></tbody></table></div>
                    </div>

                    <div class='tab-pane fade' id='transfer' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-3'><h4 class='mb-0'>Inventory Transfers</h4><button class='btn btn-info text-white' data-bs-toggle='modal' data-bs-target='#transferPartsModal'><i class='bi bi-truck'></i> New Transfer</button></div>
                        <input type='text' id='transfersSearch' class='form-control w-25 mb-3' placeholder='Search by part no or branch...'>
                        <div class='table-responsive'><table class='table table-striped' id='transfersTable'><thead><tr><th>Date</th><th>Part No</th><th>Qty</th><th>Type</th><th>From</th><th>To</th></tr></thead><tbody id='transfersTableBody'></tbody></table></div>
                    </div>

                     <div class='tab-pane fade' id='reports' role='tabpanel'>
                        <div class='card'>
                            <div class='card-header'>Generate System Reports</div>
                            <div class='card-body'>
                                <form id="reportForm">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-3">
                                            <label for="reportType" class="form-label">Report Type</label>
                                            <select id="reportType" class="form-select">
                                                <option value="sales">Sales Report</option>
                                                <option value="inventory">Inventory Report</option>
                                                <option value="payments">Payments Report</option>
                                                <option value="transfers">Transfers Report</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="reportPeriod" class="form-label">Period</label>
                                            <select id="reportPeriod" class="form-select">
                                                <option value="daily">Daily</option>
                                                <option value="monthly">Monthly</option>
                                                <option value="yearly">Yearly</option>
                                                <option value="range">Date Range</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <div id="date-daily"><label class="form-label">Date</label><input type="date" id="reportDateDaily" class="form-control"></div>
                                            <div id="date-monthly" class="d-none"><label class="form-label">Month</label><input type="month" id="reportDateMonthly" class="form-control"></div>
                                            <div id="date-yearly" class="d-none"><label class="form-label">Year</label><input type="number" id="reportDateYearly" class="form-control" placeholder="YYYY"></div>
                                            <div id="date-range" class="d-none"><label class="form-label">Date Range</label><div class="input-group"><input type="date" id="reportDateStart" class="form-control"><input type="date" id="reportDateEnd" class="form-control"></div></div>
                                        </div>
                                        <?php if ($canViewAllBranches): ?>
                                        <div class="col-md-3">
                                            <label for="reportBranch" class="form-label">Branch</label>
                                            <select id="reportBranch" class="form-select"></select>
                                        </div>
                                        <?php endif; ?>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary text-white w-100">Generate Report</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div id="reportResultContainer" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div class='modal fade' id='addPartsInModal' tabindex='-1' aria-labelledby='addPartsInModalLabel' aria-hidden='true'> 
        <div class='modal-dialog'> 
            <div class='modal-content'> 
                <div class='modal-header'><h5 class='modal-title' id='addPartsInModalLabel'>Record Parts IN</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div> 
                <form id='addPartsInForm'> 
                    <div class='modal-body'> 
                        <div class='mb-3'><label for='in_part_no' class='form-label'>Part No</label><input type='text' class='form-control' id='in_part_no' name='part_no' required></div> 
                        <div class='mb-3'><label for='in_description' class='form-label'>Description</label><input type='text' class='form-control' id='in_description' name='description'></div> 
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for='in_quantity' class='form-label'>Quantity</label><input type='number' class='form-control' id='in_quantity' name='quantity' min='1' required></div> 
                            <div class="col-md-6 mb-3"><label for='in_cost' class='form-label'>Cost (per item)</label><div class="input-group"><span class="input-group-text">₱</span><input type='number' step='0.01' class='form-control' id='in_cost' name='cost' min='0' required></div></div>
                        </div>
                        <div class='mb-3'><label for='in_date' class='form-label'>Date</label><input type='date' class='form-control' id='in_date' name='date' required></div> 
                        <div class='mb-3'><label for='in_invoice_no' class='form-label'>Invoice / Order Sheet #</label><input type='text' class='form-control' id='in_invoice_no' name='invoice_no' required></div> 
                    </div> 
                    <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-primary text-white'>Save</button></div> 
                </form> 
            </div> 
        </div> 
    </div> 

    <div class='modal fade' id='sellPartsOutModal' tabindex='-1' aria-labelledby='sellPartsOutModalLabel' aria-hidden='true'> 
        <div class='modal-dialog'> 
            <div class='modal-content'> 
                <div class='modal-header'><h5 class='modal-title' id='sellPartsOutModalLabel'>Record Parts Sale</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div> 
                <form id='sellPartsOutForm'> 
                    <div class='modal-body'> 
                        <div class='mb-3'><label for='out_part_no' class='form-label'>Part No</label><input type='text' class='form-control' id='out_part_no' name='part_no' required></div> 
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for='out_date' class='form-label'>Date</label><input type='date' class='form-control' id='out_date' name='date' required></div> 
                            <div class="col-md-6 mb-3"><label for='out_transaction_type' class='form-label'>Type</label><select class='form-select' id='out_transaction_type' name='transaction_type' required><option value='cash'>Cash</option><option value='installment'>Installment</option></select></div> 
                        </div>
                         <div class="row">
                            <div class="col-md-6 mb-3"><label for='out_quantity' class='form-label'>Quantity</label><input type='number' class='form-control' id='out_quantity' name='quantity' min='1' required></div> 
                            <div class="col-md-6 mb-3"><label for='out_amount' class='form-label'>Total Amount</label><div class="input-group"><span class="input-group-text">₱</span><input type='number' step='0.01' class='form-control' id='out_amount' name='amount' min='0' required></div></div>
                        </div>
                        <div class='mb-3'><label for='out_or_number' class='form-label'>OR Number</label><input type='text' class='form-control' id='out_or_number' name='or_number' required></div> 
                        <div class='mb-3'><label for='out_customer_name' class='form-label'>Customer Name</label><input type='text' class='form-control' id='out_customer_name' name='customer_name' required></div> 
                    </div> 
                    <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-success text-white'>Record Sale</button></div> 
                </form> 
            </div> 
        </div> 
    </div> 

    <div class='modal fade' id='recordPaymentModal' tabindex='-1' aria-labelledby='recordPaymentModalLabel' aria-hidden='true'> 
        <div class='modal-dialog'> 
            <div class='modal-content'> 
                <div class='modal-header'><h5 class='modal-title' id='recordPaymentModalLabel'>Record Payment</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div> 
                <form id='recordPaymentForm'> 
                    <div class='modal-body'> 
                        <div class='mb-3'><label for='payment_or_number' class='form-label'>Original Sale OR Number</label><input type='text' class='form-control' id='payment_or_number' name='or_number' required><small class="form-text text-muted">Enter the OR number from the installment sale.</small></div>
                        <div class='mb-3'><label for='payment_customer_name' class='form-label'>Customer Name</label><input type='text' class='form-control' id='payment_customer_name' name='customer_name' required></div> 
                         <div class="row">
                            <div class="col-md-6 mb-3"><label for='payment_date' class='form-label'>Payment Date</label><input type='date' class='form-control' id='payment_date' name='payment_date' required></div> 
                            <div class="col-md-6 mb-3"><label for='payment_amount' class='form-label'>Amount Paid</label><div class="input-group"><span class="input-group-text">₱</span><input type='number' step='0.01' class='form-control' id='payment_amount' name='amount' min='0' required></div></div>
                        </div>
                    </div> 
                    <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-warning text-white'>Record Payment</button></div> 
                </form> 
            </div> 
        </div> 
    </div> 

    <div class='modal fade' id='transferPartsModal' tabindex='-1' aria-labelledby='transferPartsModalLabel' aria-hidden='true'> 
        <div class='modal-dialog modal-lg'> 
            <div class='modal-content'> 
                <div class='modal-header'><h5 class='modal-title' id='transferPartsModalLabel'>Create New Inventory Transfer</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div> 
                <form id='transferPartsForm'> 
                    <div class='modal-body'>
                        <div class="row border-bottom pb-3 mb-3">
                             <div class="col-md-4 mb-3"><label for='transfer_date' class='form-label'>Transfer Date</label><input type='date' class='form-control' id='transfer_date' required></div> 
                             <div class="col-md-4 mb-3"><label class='form-label'>From Branch</label><input type='text' class='form-control' value='<?php echo htmlspecialchars($currentBranch); ?>' readonly></div> 
                             <div class="col-md-4 mb-3"><label for='transfer_to_branch' class='form-label'>To Branch</label><select class='form-select' id='transfer_to_branch' required></select></div> 
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <h5>Add Part to Transfer</h5>
                                <div class="mb-2"><label for="partSearchInput" class="form-label">Search Part No or Description</label><input type="text" id="partSearchInput" class="form-control" placeholder="Start typing to search..."></div>
                                <div id="partSearchResults" class="list-group mb-2"></div>
                                <div class="mb-2 d-none" id="addPartSection">
                                    <p class="mb-1"><strong>Selected Part:</strong> <span id="selectedPartNo"></span></p>
                                    <p class="mb-1 text-muted"><span id="selectedPartDesc"></span></p>
                                    <p class="mb-2"><strong>Available Stock:</strong> <span id="selectedPartStock" class="badge bg-success"></span></p>
                                    <label for="quantityToTransfer" class="form-label">Quantity to Transfer</label>
                                    <div class="input-group">
                                        <input type="number" id="quantityToTransfer" class="form-control" min="1">
                                        <button class="btn btn-primary" type="button" id="addPartToTransferBtn">Add</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <h5>Parts to be Transferred</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead><tr><th>Part No</th><th>Description</th><th>Qty</th><th>Action</th></tr></thead>
                                        <tbody id="transferItemsList">
                                            <tr id="emptyTransferList"><td colspan="4" class="text-center text-muted">No parts added yet.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div> 
                    <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-info text-white'>Submit Transfer</button></div> 
                </form> 
            </div> 
        </div> 
    </div> 

    <div class='modal fade' id='editPartModal' tabindex='-1' aria-labelledby='editPartModalLabel' aria-hidden='true'> 
        <div class='modal-dialog'> 
            <div class='modal-content'> 
                <div class='modal-header'><h5 class='modal-title' id='editPartModalLabel'>Edit Part Details</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div> 
                <form id='editPartForm'> 
                    <input type='hidden' id='edit_part_id' name='id'> 
                    <div class='modal-body'> 
                        <div class='mb-3'><label for='edit_part_no' class='form-label'>Part No</label><input type='text' class='form-control' id='edit_part_no' name='part_no' required></div> 
                        <div class='mb-3'><label for='edit_description' class='form-label'>Description</label><input type='text' class='form-control' id='edit_description' name='description'></div> 
                        <div class="row">
                             <div class="col-md-6 mb-3"><label for='edit_quantity' class='form-label'>Current Stock</label><input type='number' class='form-control' id='edit_quantity' name='quantity' min='0' required></div> 
                             <div class="col-md-6 mb-3"><label for='edit_min_stock' class='form-label'>Minimum Stock</label><input type='number' class='form-control' id='edit_min_stock' name='min_stock' min='0' required></div> 
                        </div>
                        <div class="row">
                             <div class="col-md-6 mb-3"><label for='edit_cost' class='form-label'>Cost</label><div class="input-group"><span class="input-group-text">₱</span><input type='number' step='0.01' class='form-control' id='edit_cost' name='cost' min='0' required></div></div> 
                             <div class="col-md-6 mb-3"><label for='edit_price' class='form-label'>Price</label><div class="input-group"><span class="input-group-text">₱</span><input type='number' step='0.01' class='form-control' id='edit_price' name='price' min='0' required></div></div> 
                        </div>
                    </div> 
                    <div class='modal-footer'><button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button><button type='submit' class='btn btn-primary text-white'>Save Changes</button></div> 
                </form> 
            </div> 
        </div> 
    </div> 

    <div class='modal fade' id='successModal' tabindex='-1' aria-hidden='true'><div class='modal-dialog'><div class='modal-content'><div class='modal-header bg-success text-white'><h5 class='modal-title text-white'>Success</h5><button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button></div><div class='modal-body'><p id='successMessage'></p></div><div class='modal-footer'><button type='button' class='btn btn-success' data-bs-dismiss='modal'>OK</button></div></div></div></div>
    <div class='modal fade' id='errorModal' tabindex='-1' aria-hidden='true'><div class='modal-dialog'><div class='modal-content'><div class='modal-header bg-danger text-white'><h5 class='modal-title'>Error</h5><button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button></div><div class='modal-body'><p id='errorMessage'></p></div><div class='modal-footer'><button type='button' class='btn btn-danger' data-bs-dismiss='modal'>OK</button></div></div></div></div>
    
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    
    <script>
        const currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
        const userRole = '<?php echo htmlspecialchars($userRole); ?>';
        const canViewAllBranches = <?php echo json_encode($canViewAllBranches); ?>;
    </script>
    <script src='../js/spareparts_inventory.js'></script>
</body>
</html>