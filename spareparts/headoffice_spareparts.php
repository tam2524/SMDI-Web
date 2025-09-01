<?php include '../api/auth.php';
?>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='utf-8'>
    <title>SMDI - SPAREPARTS | The Highest Levels of Service</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <meta content='' name='keywords'>
    <meta content='' name='description'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css' rel='stylesheet'>
    <link href='../lib/lightbox/css/lightbox.min.css' rel='stylesheet'>
    <link href='../lib/owlcarousel/assets/owl.carousel.min.css' rel='stylesheet'>
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css' rel='stylesheet'>
    <link href='../css/spareparts_inventory_style.css' rel='stylesheet'>

    <link rel='stylesheet' href='https://printjs-4de6.kxcdn.com/print.min.css'>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js'></script>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://printjs-4de6.kxcdn.com/print.min.js'></script>
</head>

<body>

    <div class='container-fluid fixed-top bg-white'>
        <div class='container topbar bg-primary d-none d-lg-block'>
            <div class='d-flex justify-content-between'>
                <div class='top-info ps-2'>
                    <small class='me-3'>
                        <i class='fas fa-map-marker-alt me-2 text-primary'></i>
                        <a href='#' class='text-white'>1031, Victoria Building, Roxas Avenue, Roxas City, 5800</a>
                    </small>
                </div>
                <div class='top-link pe-2'></div>
            </div>
        </div>
        <div class='container px-0'>
            <nav class='navbar navbar-light bg-white navbar-expand-lg'>
                <a class='navbar-brand'>
                    <img src='../assets/img/smdi_logo.jpg' alt='SMDI Logo' class='logo'>
                </a>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarCollapse'
                    aria-controls='navbarCollapse' aria-expanded='false' aria-label='Toggle navigation'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='navbarCollapse'>
                    <div class='navbar-nav'>
                        <a href='../inventory/headoffice_inventory.php' class='nav-item nav-link active'>Home</a>

                        <a href='../api/logout.php' class='nav-item nav-link active'>Logout</a>

                        <?php if (isset($_SESSION['username'])): ?>
                        <span class='nav-item nav-link disabled' style='cursor: default; color: red;'>
                            <i class='fas fa-user-circle me-1'></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>

                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>

     <main class='container-fluid py-5' style='margin-top: 120px;'>
        <div class='card mb-4'>
            <div class='card-header bg-white'>
                <h1 class='h5 mb-0'>Spareparts Inventory Management</h1>
            </div>
            <div class='card-body'>
                <ul class='nav nav-tabs mb-4' id='inventoryTabs' role='tablist'>
    <li class='nav-item' role='presentation'>
        <button class='nav-link active' id='dashboard-tab' data-bs-toggle='tab'
            data-bs-target='#dashboard' type='button' role='tab'>Dashboard</button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='spareparts-in-tab' data-bs-toggle='tab' data-bs-target='#spareparts-in'
            type='button' role='tab'>Spareparts IN</button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='sales-tab' data-bs-toggle='tab' data-bs-target='#sales'
            type='button' role='tab'>Sales</button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='payments-tab' data-bs-toggle='tab' data-bs-target='#payments'
            type='button' role='tab'>Payments</button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='transfers-tab' data-bs-toggle='tab' data-bs-target='#transfers'
            type='button' role='tab'>Transfers</button>
    </li>
    <li class='nav-item' role='presentation'>
        <button class='nav-link' id='reports-tab' data-bs-toggle='tab' data-bs-target='#reports'
            type='button' role='tab'>Reports</button>
    </li>
</ul>


                <div class='tab-content' id='inventoryTabContent'>
                    <!-- Dashboard Tab (unchanged) -->
                    <div class='tab-pane fade show active' id='dashboard' role='tabpanel'>
                        <!-- Your existing dashboard content -->
                    </div>

                    <!-- Inventory Tab -->
                    <!-- Spareparts IN Tab -->
<div class='tab-pane fade' id='spareparts-in' role='tabpanel'>
    <div class='d-flex justify-content-between mb-4'>   
        <div>
            <button class='btn btn-primary text-white me-2' data-bs-toggle='modal'
                data-bs-target='#addSparepartsModal'>
                <i class='bi bi-plus-circle'></i> Receive Spareparts
            </button>
        </div>
        <div class='input-group' style='max-width: 300px;'>
            <input type='text' id='searchSparepartsIn' class='form-control'
                placeholder='Search spareparts in...'>
            <button class='btn btn-primary text-white' type='button' id='searchSparepartsInBtn'>
                <i class='bi bi-search'></i>
            </button>
        </div>
    </div>

    <div class='table-responsive'>
        <table class='table table-striped' id='sparepartsInTable'>
            <thead>
                <tr>
                    <th>Part No.</th>
                    <th>Quantity</th>
                    <th>Cost</th>
                    <th>Date Received</th>
                    <th>Invoice/Order #</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id='sparepartsInTableBody'>
                <!-- Content will be loaded via JavaScript -->
            </tbody>
        </table>
    </div>

    <nav aria-label='Spareparts IN pagination'>
        <ul id='paginationControls' class='pagination'>
            <li id='prevPage' class='page-item disabled'>
                <a class='page-link' href='#' tabindex='-1' aria-disabled='true'>
                    <i class='fas fa-chevron-left me-1'></i> Previous
                </a>
            </li>
            <li id='nextPage' class='page-item'>
                <a class='page-link' href='#'>
                    Next <i class='fas fa-chevron-right ms-1'></i>
                </a>
            </li>
        </ul>
    </nav>
</div>


                    <!-- Sales Tab -->
                    <div class='tab-pane fade' id='sales' role='tabpanel'>
                        <div class='d-flex justify-content-between mb-4'>   
                            <div>
                                <button class='btn btn-success text-white me-2' data-bs-toggle='modal'
                                    data-bs-target='#addSaleModal'>
                                    <i class='bi bi-cart-plus'></i> Add Sale
                                </button>
                            </div>
                            <div class='input-group' style='max-width: 300px;'>
                                <input type='text' id='searchSales' class='form-control'
                                    placeholder='Search sales...'>
                                <button class='btn btn-primary text-white' type='button' id='searchSalesBtn'>
                                    <i class='bi bi-search'></i>
                                </button>
                            </div>
                        </div>

                        <div class='table-responsive'>
                            <table class='table table-striped' id='salesTable'>
                                <thead>
    <tr>
        <th>Part No.</th>
        <th>Date</th>
        <th>Transaction Type</th>
        <th>Quantity</th>
        <th>Amount</th>
        <th>OR Number</th>
        <th>Customer</th>
        <th>Balance</th>
        <th>Actions</th>
    </tr>
</thead>

                                <tbody id='salesTableBody'>
                                    <!-- Sales data will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label='Sales pagination'>
                            <ul id='salesPaginationControls' class='pagination'>
                                <!-- Pagination controls for sales -->
                            </ul>
                        </nav>
                    </div>

                    <!-- Payments Tab -->
                    <div class='tab-pane fade' id='payments' role='tabpanel'>
                        <div class='d-flex justify-content-between mb-4'>   
                            <div>
                                <button class='btn btn-warning text-white me-2' data-bs-toggle='modal'
                                    data-bs-target='#addPaymentModal'>
                                    <i class='bi bi-credit-card'></i> Add Payment
                                </button>
                            </div>
                            <div class='input-group' style='max-width: 300px;'>
                                <input type='text' id='searchPayments' class='form-control'
                                    placeholder='Search payments...'>
                                <button class='btn btn-primary text-white' type='button' id='searchPaymentsBtn'>
                                    <i class='bi bi-search'></i>
                                </button>
                            </div>
                        </div>

                        <div class='table-responsive'>
                            <table class='table table-striped' id='paymentsTable'>
                                <thead>
    <tr>
        <th>Date</th>
        <th>Customer</th>
        <th>Amount</th>
        <th>Branch</th>
        <th>Actions</th>
    </tr>
</thead>

                                <tbody id='paymentsTableBody'>
                                    <!-- Payments data will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label='Payments pagination'>
                            <ul id='paymentsPaginationControls' class='pagination'>
                                <!-- Pagination controls for payments -->
                            </ul>
                        </nav>
                    </div>

                    <!-- Transfers Tab -->
                    <div class='tab-pane fade' id='transfers' role='tabpanel'>
                        <div class='d-flex justify-content-between mb-4'>   
                            <div>
                                <button class='btn btn-info text-white me-2' data-bs-toggle='modal'
                                    data-bs-target='#addTransferModal'>
                                    <i class='bi bi-arrow-left-right'></i> Add Transfer
                                </button>
                                <button class="btn btn-primary text-white me-2" id="searchTransferReceiptBtn">
                                    <i class="bi bi-receipt"></i> Search Transfer Receipt
                                </button>
                            </div>
                            <div class='input-group' style='max-width: 300px;'>
                                <input type='text' id='searchTransfers' class='form-control'
                                    placeholder='Search transfers...'>
                                <button class='btn btn-primary text-white' type='button' id='searchTransfersBtn'>
                                    <i class='bi bi-search'></i>
                                </button>
                            </div>
                        </div>

                        <div class='table-responsive'>
                            <table class='table table-striped' id='transfersTable'>
                                <thead>
    <tr>
        <th>Date</th>
        <th>Part No.</th>
        <th>Quantity</th>
        <th>Cost</th>
        <th>Total Cost</th>
        <th>Transfer Route</th>
        <th>Actions</th>
    </tr>
</thead>

                                <tbody id='transfersTableBody'>
                                    <!-- Transfers data will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label='Transfers pagination'>
                            <ul id='transfersPaginationControls' class='pagination'>
                                <!-- Pagination controls for transfers -->
                            </ul>
                        </nav>
                    </div>

                    <!-- Reports Tab -->
<div class='tab-pane fade' id='reports' role='tabpanel'>
    <div class='row'>
        <div class='col-md-4 mb-4'>
            <div class='card'>
                <div class='card-header'>
                    <h5 class='card-title mb-0'>Monthly Aging Report</h5>
                </div>
                <div class='card-body'>
                    <p class='card-text'>Shows all sales with outstanding balances and aging categories.</p>
                    <div class='mb-3'>
                        <label for='agingReportMonth' class='form-label'>Month</label>
                        <input type='month' class='form-control' id='agingReportMonth'>
                    </div>
                    <div class='mb-3'>
                        <label for='agingReportBranch' class='form-label'>Branch (Optional)</label>
                        <select class='form-select' id='agingReportBranch'>
                            <option value=''>All Branches</option>
                            <option value='MAIN'>MAIN</option>
                            <option value='HEADOFFICE'>HEADOFFICE</option>
                            <!-- Add other branches as needed -->
                        </select>
                    </div>
                    <button class='btn btn-primary text-white' id='generateAgingReportBtn'>
                        <i class='bi bi-file-earmark-text'></i> Generate Report
                    </button>
                </div>
            </div>
        </div>

        <div class='col-md-4 mb-4'>
            <div class='card'>
                <div class='card-header'>
                    <h5 class='card-title mb-0'>Sales Report</h5>
                </div>
                <div class='card-body'>
                    <p class='card-text'>Shows all sales (cash/installment) for selected period.</p>
                    <div class='mb-3'>
                        <label for='salesReportType' class='form-label'>Report Type</label>
                        <select class='form-select' id='salesReportType'>
                            <option value='daily'>Daily</option>
                            <option value='monthly'>Monthly</option>
                        </select>
                    </div>
                    <div class='mb-3'>
                        <label for='salesReportPeriod' class='form-label'>Period</label>
                        <input type='date' class='form-control' id='salesReportPeriod'>
                    </div>
                    <div class='mb-3'>
                        <label for='salesReportBranch' class='form-label'>Branch (Optional)</label>
                        <select class='form-select' id='salesReportBranch'>
                            <option value=''>All Branches</option>
                            <option value='MAIN'>MAIN</option>
                            <option value='HEADOFFICE'>HEADOFFICE</option>
                            <!-- Add other branches as needed -->
                        </select>
                    </div>
                    <button class='btn btn-success text-white' id='generateSalesReportBtn'>
                        <i class='bi bi-graph-up'></i> Generate Report
                    </button>
                </div>
            </div>
        </div>

        <div class='col-md-4 mb-4'>
            <div class='card'>
                <div class='card-header'>
                    <h5 class='card-title mb-0'>Payment Summary</h5>
                </div>
                <div class='card-body'>
                    <p class='card-text'>Shows all payments received for selected period.</p>
                    <div class='mb-3'>
                        <label for='paymentSummaryType' class='form-label'>Report Type</label>
                        <select class='form-select' id='paymentSummaryType'>
                            <option value='daily'>Daily</option>
                            <option value='monthly'>Monthly</option>
                        </select>
                    </div>
                    <div class='mb-3'>
                        <label for='paymentSummaryPeriod' class='form-label'>Period</label>
                        <input type='date' class='form-control' id='paymentSummaryPeriod'>
                    </div>
                    <div class='mb-3'>
                        <label for='paymentSummaryBranch' class='form-label'>Branch (Optional)</label>
                        <select class='form-select' id='paymentSummaryBranch'>
                            <option value=''>All Branches</option>
                            <option value='MAIN'>MAIN</option>
                            <option value='HEADOFFICE'>HEADOFFICE</option>
                            <!-- Add other branches as needed -->
                        </select>
                    </div>
                    <button class='btn btn-warning text-white' id='generatePaymentSummaryBtn'>
                        <i class='bi bi-cash-stack'></i> Generate Summary
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </main>



<!-- Add Spareparts IN Modal -->
<div class='modal fade' id='addSparepartsModal' tabindex='-1' aria-labelledby='addSparepartsModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='addSparepartsModalLabel'>Receive Spareparts (IN)</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='addSparepartsInForm'>
                    <div class='row mb-4'>
                        <div class='col-md-6 mb-3'>
                            <label for='partNo' class='form-label'>Part Number</label>
                            <input type='text' class='form-control' id='partNo' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='quantity' class='form-label'>Quantity</label>
                            <input type='number' class='form-control' id='quantity' min='1' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='cost' class='form-label'>Cost</label>
                            <input type='number' step='0.01' class='form-control' id='cost' min='0' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='dateReceived' class='form-label'>Date Received</label>
                            <input type='date' class='form-control' id='dateReceived' required>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='invoiceNo' class='form-label'>Invoice/Order Sheet #</label>
                            <input type='text' class='form-control' id='invoiceNo' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='supplier' class='form-label'>Supplier (Optional)</label>
                            <input type='text' class='form-control' id='supplier'>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='branch' class='form-label'>Branch</label>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') { ?>
                            <select class='form-select' id='branch' required>
                                <option value='MAIN'>MAIN</option>
                                <option value='HEADOFFICE'>HEADOFFICE</option>
                                <option value='ROXAS SUZUKI'>ROXAS SUZUKI</option>
                                <option value='MAMBUSAO'>MAMBUSAO</option>
                                <option value='SIGMA'>SIGMA</option>
                                <option value='PRC'>PRC</option>
                                <option value='CUARTERO'>CUARTERO</option>
                                <option value='JAMINDAN'>JAMINDAN</option>
                                <option value='ROXAS HONDA'>ROXAS HONDA</option>
                                <option value='ANTIQUE-1'>ANTIQUE-1</option>
                                <option value='ANTIQUE-2'>ANTIQUE-2</option>
                                <option value='DELGADO HONDA'>DELGADO HONDA</option>
                                <option value='DELGADO SUZUKI'>DELGADO SUZUKI</option>
                                <option value='JARO-1'>JARO-1</option>
                                <option value='JARO-2'>JARO-2</option>
                                <option value='KALIBO MABINI'>KALIBO MABINI</option>
                                <option value='KALIBO SUZUKI'>KALIBO SUZUKI</option>
                                <option value='ALTAVAS'>ALTAVAS</option>
                                <option value='EMAP'>EMAP</option>
                                <option value='CULASI'>CULASI</option>
                                <option value='BACOLOD'>BACOLOD</option>
                                <option value='PASSI-1'>PASSI-1</option>
                                <option value='PASSI-2'>PASSI-2</option>
                                <option value='BALASAN'>BALASAN</option>
                                <option value='GUIMARAS'>GUIMARAS</option>
                                <option value='PEMDI'>PEMDI</option>
                                <option value='EEMSI'>EEMSI</option>
                                <option value='AJUY'>AJUY</option>
                                <option value='BAILAN'>BAILAN</option>
                                <option value='3SMB'>3SMB</option>
                                <option value='3SMINDORO'>3SMINDORO</option>
                                <option value='MANSALAY'>MANSALAY</option>
                                <option value='K-RIDERS'>K-RIDERS</option>
                                <option value='IBAJAY'>IBAJAY</option>
                                <option value='NUMANCIA'>NUMANCIA</option>
                                <option value='CEBU'>CEBU</option>
                            </select>
                            <?php } else { ?>
                            <input type='text' class='form-control' id='branch'
                                value="<?php echo $_SESSION['user_branch']; ?>" readonly>
                            <?php } ?>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='notes' class='form-label'>Notes (Optional)</label>
                            <textarea class='form-control' id='notes' rows='3'></textarea>
                        </div>
                    </div>

                    <div class='d-grid mt-4'>
                        <button type='submit' class='btn btn-primary text-white'>Receive Spareparts</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Spareparts Modal (for editing existing spareparts master data) -->
<div class='modal fade' id='editSparepartsModal' tabindex='-1' aria-labelledby='editSparepartsModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='editSparepartsModalLabel'>Edit Sparepart Details</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='editSparepartForm'>
                    <input type='hidden' id='editId'>
                    <div class='row'>
                        <div class='col-md-6 mb-3'>
                            <label for='editPartNo' class='form-label'>Part Number</label>
                            <input type='text' class='form-control' id='editPartNo' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='editDescription' class='form-label'>Description</label>
                            <input type='text' class='form-control' id='editDescription' required>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-4 mb-3'>
                            <label for='editBrand' class='form-label'>Brand</label>
                            <select class='form-select' id='editBrand'>
                                <option value=''>Select Brand</option>
                                <option value='Suzuki'>Suzuki</option>
                                <option value='Honda'>Honda</option>
                                <option value='Kawasaki'>Kawasaki</option>
                                <option value='Yamaha'>Yamaha</option>
                                <option value='Asiastar'>Asiastar</option>
                                <option value='Generic'>Generic</option>
                            </select>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label for='editModelCompatibility' class='form-label'>Model Compatibility</label>
                            <input type='text' class='form-control' id='editModelCompatibility'>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label for='editCategory' class='form-label'>Category</label>
                            <select class='form-select' id='editCategory'>
                                <option value=''>Select Category</option>
                                <option value='Engine Parts'>Engine Parts</option>
                                <option value='Body Parts'>Body Parts</option>
                                <option value='Electrical'>Electrical</option>
                                <option value='Brake System'>Brake System</option>
                                <option value='Suspension'>Suspension</option>
                                <option value='Transmission'>Transmission</option>
                                <option value='Accessories'>Accessories</option>
                                <option value='Consumables'>Consumables</option>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6 mb-3'>
                            <label for='editUnitOfMeasure' class='form-label'>Unit of Measure</label>
                            <select class='form-select' id='editUnitOfMeasure'>
                                <option value='pcs'>Pieces (pcs)</option>
                                <option value='set'>Set</option>
                                <option value='pair'>Pair</option>
                                <option value='liter'>Liter</option>
                                <option value='bottle'>Bottle</option>
                                <option value='pack'>Pack</option>
                            </select>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='editMinStockLevel' class='form-label'>Minimum Stock Level</label>
                            <input type='number' class='form-control' id='editMinStockLevel' min='1'>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6 mb-3'>
                            <label for='editCurrentCost' class='form-label'>Current Cost</label>
                            <input type='number' step='0.01' class='form-control' id='editCurrentCost' min='0'>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='editSellingPrice' class='form-label'>Selling Price</label>
                            <input type='number' step='0.01' class='form-control' id='editSellingPrice' min='0'>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6 mb-3'>
                            <label for='editStatus' class='form-label'>Status</label>
                            <select class='form-select' id='editStatus'>
                                <option value='active'>Active</option>
                                <option value='inactive'>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class='d-grid'>
                        <button type='submit' class='btn btn-primary text-white'>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Add Sale Modal -->
<div class='modal fade' id='addSaleModal' tabindex='-1' aria-labelledby='addSaleModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='addSaleModalLabel'>Add Sale (OUT)</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='addSaleForm'>
                    <div class='row mb-4'>
                        <div class='col-md-6 mb-3'>
                            <label for='salePartNo' class='form-label'>Part Number</label>
                            <input type='text' class='form-control' id='salePartNo' required>
                            <div class='form-text'>Enter part number or search from inventory</div>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='saleDate' class='form-label'>Sale Date</label>
                            <input type='date' class='form-control' id='saleDate' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='transactionType' class='form-label'>Transaction Type</label>
                            <select class='form-select' id='transactionType' required>
                                <option value=''>Select Transaction Type</option>
                                <option value='cash'>Cash</option>
                                <option value='installment'>Installment</option>
                            </select>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='saleQuantity' class='form-label'>Quantity</label>
                            <input type='number' class='form-control' id='saleQuantity' min='1' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='saleAmount' class='form-label'>Total Amount</label>
                            <input type='number' step='0.01' class='form-control' id='saleAmount' min='0' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='orNumber' class='form-label'>OR Number</label>
                            <input type='text' class='form-control' id='orNumber' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='customerName' class='form-label'>Customer Name</label>
                            <input type='text' class='form-control' id='customerName' required>
                        </div>
                        <div class='col-md-6 mb-3'>
                            <label for='saleBranch' class='form-label'>Branch</label>
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') { ?>
                            <select class='form-select' id='saleBranch' required>
                                <option value='MAIN'>MAIN</option>
                                <option value='HEADOFFICE'>HEADOFFICE</option>
                                <option value='ROXAS SUZUKI'>ROXAS SUZUKI</option>
                                <option value='MAMBUSAO'>MAMBUSAO</option>
                                <option value='SIGMA'>SIGMA</option>
                                <option value='PRC'>PRC</option>
                                <option value='CUARTERO'>CUARTERO</option>
                                <option value='JAMINDAN'>JAMINDAN</option>
                                <option value='ROXAS HONDA'>ROXAS HONDA</option>
                                <option value='ANTIQUE-1'>ANTIQUE-1</option>
                                <option value='ANTIQUE-2'>ANTIQUE-2</option>
                                <option value='DELGADO HONDA'>DELGADO HONDA</option>
                                <option value='DELGADO SUZUKI'>DELGADO SUZUKI</option>
                                <option value='JARO-1'>JARO-1</option>
                                <option value='JARO-2'>JARO-2</option>
                                <option value='KALIBO MABINI'>KALIBO MABINI</option>
                                <option value='KALIBO SUZUKI'>KALIBO SUZUKI</option>
                                <option value='ALTAVAS'>ALTAVAS</option>
                                <option value='EMAP'>EMAP</option>
                                <option value='CULASI'>CULASI</option>
                                <option value='BACOLOD'>BACOLOD</option>
                                <option value='PASSI-1'>PASSI-1</option>
                                <option value='PASSI-2'>PASSI-2</option>
                                <option value='BALASAN'>BALASAN</option>
                                <option value='GUIMARAS'>GUIMARAS</option>
                                <option value='PEMDI'>PEMDI</option>
                                <option value='EEMSI'>EEMSI</option>
                                <option value='AJUY'>AJUY</option>
                                <option value='BAILAN'>BAILAN</option>
                                <option value='3SMB'>3SMB</option>
                                <option value='3SMINDORO'>3SMINDORO</option>
                                <option value='MANSALAY'>MANSALAY</option>
                                <option value='K-RIDERS'>K-RIDERS</option>
                                <option value='IBAJAY'>IBAJAY</option>
                                <option value='NUMANCIA'>NUMANCIA</option>
                                <option value='CEBU'>CEBU</option>
                            </select>
                            <?php } else { ?>
                            <input type='text' class='form-control' id='saleBranch'
                                value="<?php echo $_SESSION['user_branch']; ?>" readonly>
                            <?php } ?>
                        </div>
                        
                        <!-- Installment specific fields (hidden by default) -->
                        <div id='installmentFields' class='col-12' style='display: none;'>
                            <hr>
                            <h6 class='mb-3'>Installment Details</h6>
                            <div class='row'>
                                <div class='col-md-6 mb-3'>
                                    <label for='downPayment' class='form-label'>Down Payment</label>
                                    <input type='number' step='0.01' class='form-control' id='downPayment' min='0'>
                                </div>
                                <div class='col-md-6 mb-3'>
                                    <label for='balance' class='form-label'>Balance</label>
                                    <input type='number' step='0.01' class='form-control' id='balance' min='0' readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class='col-md-12 mb-3'>
                            <label for='saleNotes' class='form-label'>Notes (Optional)</label>
                            <textarea class='form-control' id='saleNotes' rows='3'></textarea>
                        </div>
                    </div>

                    <div class='d-grid mt-4'>
                        <button type='submit' class='btn btn-success text-white'>Record Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class='modal fade' id='addPaymentModal' tabindex='-1' aria-labelledby='addPaymentModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog modal-md'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='addPaymentModalLabel'>Add Payment</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='addPaymentForm'>
                    <div class='row mb-4'>
                        <div class='col-md-12 mb-3'>
                            <label for='paymentDate' class='form-label'>Payment Date</label>
                            <input type='date' class='form-control' id='paymentDate' required>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='paymentCustomerName' class='form-label'>Customer Name</label>
                            <input type='text' class='form-control' id='paymentCustomerName' required>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='paymentAmount' class='form-label'>Payment Amount</label>
                            <input type='number' step='0.01' class='form-control' id='paymentAmount' min='0' required>
                        </div>
                    </div>

                    <div class='d-grid mt-4'>
                        <button type='submit' class='btn btn-warning text-white'>Record Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Transfer Modal -->
<div class='modal fade' id='addTransferModal' tabindex='-1' aria-labelledby='addTransferModalLabel'
    aria-hidden='true'>
    <div class='modal-dialog modal-md'>
        <div class='modal-content'>
            <div class='modal-header'>
                <h5 class='modal-title' id='addTransferModalLabel'>Add Transfer</h5>
                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
            </div>
            <div class='modal-body'>
                <form id='addTransferForm'>
                    <div class='row mb-4'>
                        <div class='col-md-12 mb-3'>
                            <label for='transferDate' class='form-label'>Transfer Date</label>
                            <input type='date' class='form-control' id='transferDate' required>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='transferPartNo' class='form-label'>Part Number</label>
                            <input type='text' class='form-control' id='transferPartNo' required>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='transferQuantity' class='form-label'>Quantity</label>
                            <input type='number' class='form-control' id='transferQuantity' min='1' required>
                        </div>
                        <div class='col-md-12 mb-3'>
                            <label for='transferCost' class='form-label'>Cost</label>
                            <input type='number' step='0.01' class='form-control' id='transferCost' min='0' required>
                        </div>
                    </div>

                    <div class='d-grid mt-4'>
                        <button type='submit' class='btn btn-info text-white'>Record Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



    <div class='modal fade' id='confirmationModal' tabindex='-1' aria-labelledby='confirmationModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='confirmationModalLabel'>Confirm Deletion</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    Are you sure you want to delete this motorcycle from inventory?
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' id='confirmDeleteBtn' class='btn btn-danger'>Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='successModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title text-white'>Success</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <p id='successMessage'></p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-success' data-bs-dismiss='modal'>OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='errorModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-danger text-white'>
                    <h5 class='modal-title'>Error</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <p id='errorMessage'></p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-danger' data-bs-dismiss='modal'>OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='warningModal' tabindex='-1' role='dialog' aria-labelledby='warningModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog' role='document'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='warningModalLabel'>Warning</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <p id='warningMessage'></p>
                </div>
            </div>
        </div>
    </div>


    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://unpkg.com/leaflet@1.9.3/dist/leaflet.js'></script>
    <script>
    const currentBranch = '<?php echo $_SESSION['user_branch'] ?? 'RXS-S'; ?>';
    const currentUserBranch = "<?php echo isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] : ''; ?>";
    const currentUserPosition = "<?php echo isset($_SESSION['position']) ? $_SESSION['position'] : ''; ?>";
    const isHeadOffice = currentUserBranch === 'HEADOFFICE';
    const isAdminUser = ['ADMIN', 'IT STAFF', 'HEAD'].includes(currentUserPosition.toUpperCase());
    </script>
    <script src='../js/spareparts_inventory.js'></script>
</body>

</html>