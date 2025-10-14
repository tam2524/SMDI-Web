<?php include '../api/auth.php';
?>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='utf-8'>
    <title>SMDI - INVENTORY | The Highest Levels of Service</title>
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
    <link href='../css/inventory_style.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://printjs-4de6.kxcdn.com/print.min.css'>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

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
                        <a href='admin_dashboard.php' class='nav-item nav-link active'>Home</a>
                        <a href='admin_inventory.php' class='nav-item nav-link active'>Inventory</a>
                        <a href='../api/logout.php' class='nav-item nav-link active'>Logout</a>

                        <?php if ( isset( $_SESSION[ 'username' ] ) ): ?>
                        <span class='nav-item nav-link disabled' style='cursor: default; color: red;'>
                            <i class='fas fa-user-circle me-1'></i>
                            <?php echo htmlspecialchars( $_SESSION[ 'username' ] );
?>
                        </span>

                        <?php endif;
?>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <main class='container-fluid py-5' style='margin-top: 110px;'>
        <div class='card mb-4'>
            <div class='card-header bg-white'>
                <h1 class='h5 mb-0'>Motorcycle Inventory Management</h1>
            </div>
            <div class='card-body'>
                <ul class='nav nav-tabs mb-4' id='inventoryTabs' role='tablist'>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='activity-log-tab' data-bs-toggle='tab'
                            data-bs-target='#activityLog' type='button' role='tab'>Activity Log</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link active' id='dashboard-tab' data-bs-toggle='tab'
                            data-bs-target='#dashboard' type='button' role='tab'>Dashboard</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='management-tab' data-bs-toggle='tab' data-bs-target='#management'
                            type='button' role='tab'>Inventory Management</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='global-transfer-tab' data-bs-toggle='tab'
                            data-bs-target='#globalTransferHistory' type='button' role='tab'>Global Transfer
                            History</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='sold-units-tab' data-bs-toggle='tab' data-bs-target='#soldUnits'
                            type='button' role='tab'>Sold Units</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='repossessed-units-tab' data-bs-toggle='tab'
                            data-bs-target='#repossessedUnits' type='button' role='tab'>Repossessed Units</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='scrapped-units-tab' data-bs-toggle='tab'
                            data-bs-target='#scrappedUnits' type='button' role='tab'>Scrapped Units</button>
                    </li>
                    <li class='nav-item' role='presentation'>
                        <button class='nav-link' id='redeemed-units-tab' data-bs-toggle='tab'
                            data-bs-target='#redeemedUnits' type='button' role='tab'>Redeemed Units</button>
                    </li>
                </ul>

                <div class='tab-content' id='inventoryTabContent'>
                    <div class='tab-pane fade' id='activityLog' role='tabpanel' aria-labelledby='activity-log-tab'>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">System Activity Log</h5>
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" id="activityLogSearch" class="form-control"
                                    placeholder="Search by user, action, or details...">
                                <button class="btn btn-primary text-white" type="button" id="activityLogSearchBtn"><i
                                        class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover' id='activityLogTable'>
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Timestamp</th>
                                        <th style="width: 10%;">User</th>
                                        <th style="width: 15%;">Action</th>
                                        <th style="width: 10%;">Record ID</th>
                                        <th style="width: 50%;">Details</th>
                                    </tr>
                                </thead>
                                <tbody id='activityLogTableBody'>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="spinner-border spinner-border-sm"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class='pagination pagination-sm justify-content-center' id='activityLogPagination'></ul>
                        </nav>
                    </div>

                    <div class='tab-pane fade show active' id='dashboard' role='tabpanel'>
                        <div class='row mb-4'>
                            <div class='col-md-6'>
                                <h4>Inventory Overview</h4>
                            </div>
                            <div class='col-md-6 text-end'>
                                <div class='input-group' style='max-width: 300px; margin-left: auto;'>
                                    <input type='text' id='searchDashboard' class='form-control'
                                        placeholder='Search models...'>
                                    <button class='btn btn-primary text-white' type='button' id='searchDashboardBtn'>
                                        <i class='bi bi-search'></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class='row' id='inventoryCards'>
                            <div class='col-12 text-center py-5'>
                                <div class='spinner-border text-primary' role='status'>
                                    <span class='visually-hidden'>Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class='tab-pane fade' id='management' role='tabpanel'>
                        <div class='d-flex justify-content-between mb-4'>
                            <div>

                                <button type='button' class='btn btn-primary text-white me-2'
                                    id='generateReportsButton'>
                                    <i class='bi bi-file-earmark-text'></i> Generate Reports
                                </button>
                                <button class='btn btn-primary text-white me-2' id='searchTransferReceiptBtn'>
                                    <i class='bi bi-receipt'></i> Print by MT
                                </button>
                                <button class='btn btn-primary text-white me-2' id='searchInvoiceNumberBtn'>
                                    <i class='bi bi-receipt'></i> Print by Invoice
                                </button>
                            </div>

                            <div class='input-group' style='max-width: 300px;'>
                                <input type='text' id='searchInventory' class='form-control'
                                    placeholder='Search inventory...'>
                                <button class='btn btn-primary text-white' type='button' id='searchInventoryBtn'>
                                    <i class='bi bi-search'></i>
                                </button>
                            </div>
                        </div>

                        <div class='table-responsive'>
                            <table class='table table-striped' id='inventoryTable'>
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th class='sortable-header' data-sort='date_delivered'>Date Delivered</th>
                                        <th class='sortable-header' data-sort='brand'>Brand</th>
                                        <th class='sortable-header' data-sort='model'>Model</th>
                                        <th class='sortable-header' data-sort='category'>Category</th>
                                        <th>Engine No.</th>
                                        <th>Frame No.</th>
                                        <th>Color</th>
                                        <th>Inventory Cost</th>
                                        <th class='sortable-header' data-sort='current_branch'>Current Branch</th>
                                        <th class='no-print'>Actions</th>
                                    </tr>
                                </thead>

                                <tbody id='inventoryTableBody'>
                                    <tr>
                                        <td colspan='11' class='text-center py-5'>
                                            <div class='spinner-border text-primary' role='status'>
                                                <span class='visually-hidden'>Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label='Inventory pagination'>
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

                    <div class='tab-pane fade' id='globalTransferHistory' role='tabpanel'
                        aria-labelledby='global-transfer-tab'>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Global Transfer Overview</h4>
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" id="globalTransferSearch" class="form-control"
                                    placeholder="Search Invoice, Model, Engine, Branch...">
                                <button class="btn btn-primary text-white" type="button" id="globalTransferSearchBtn">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-header bg-warning d-flex align-items-center">
                                        <i class="bi bi-truck me-2 fs-5"></i>
                                        <h6 class="mb-0 fw-bold text-white">In-Transit</h6>
                                        <span class="badge bg-dark ms-auto" id="inTransitCount">0</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <tbody id="in-transitTransfersBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <nav>
                                            <ul class="pagination pagination-sm justify-content-center mb-0 transfer-pagination"
                                                id="in-transitTransfersPagination" data-status="in-transit"></ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-header bg-success text-white d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                                        <h6 class="mb-0 fw-bold text-white">Completed</h6>
                                        <span class="badge bg-light text-dark ms-auto" id="completedCount">0</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <tbody id="completedTransfersBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <nav>
                                            <ul class="pagination pagination-sm justify-content-center mb-0 transfer-pagination"
                                                id="completedTransfersPagination" data-status="completed"></ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-header bg-danger text-white d-flex align-items-center">
                                        <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                                        <h6 class="mb-0 fw-bold text-white">Rejected</h6>
                                        <span class="badge bg-light text-dark ms-auto" id="rejectedCount">0</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <tbody id="rejectedTransfersBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <nav>
                                            <ul class="pagination pagination-sm justify-content-center mb-0 transfer-pagination"
                                                id="rejectedTransfersPagination" data-status="rejected"></ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="modal fade" id="deleteTransferConfirmationModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm
                                        Deletion</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to permanently delete this entire transfer group? This action
                                    will revert the status of all involved motorcycles and cannot be undone.
                                    <input type="hidden" id="transferToDeleteId">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" id="confirmDeleteTransferBtn" class="btn btn-danger">Yes,
                                        Delete Transfer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='tab-pane fade' id='soldUnits' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Sold Units Log</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" id="soldUnitsSearch" class="form-control" placeholder="Search...">
                                <button class="btn btn-primary text-white" type="button" id="soldUnitsSearchBtn"><i
                                        class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover' id='soldUnitsTable'>
                                <thead>
                                    <tr>
                                        <th>Sale Date</th>
                                        <th>Customer Name</th>
                                        <th>Model</th>
                                        <th>Engine No.</th>
                                        <th>Branch</th>
                                        <th>Payment Type</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='soldUnitsTableBody'>
                                </tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class='pagination pagination-sm justify-content-center' id='soldUnitsPagination'></ul>
                        </nav>
                    </div>

                    <div class='tab-pane fade' id='repossessedUnits' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Repossessed Units Log</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" id="repoUnitsSearch" class="form-control" placeholder="Search...">
                                <button class="btn btn-primary text-white" type="button" id="repoUnitsSearchBtn"><i
                                        class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover' id='repossessedUnitsTable'>
                                <thead>
                                    <tr>
                                        <th>Repo Date</th>
                                        <th>Original Sale Date</th>
                                        <th>Model</th>
                                        <th>Engine No.</th>
                                        <th>Current Branch</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='repossessedUnitsTableBody'>
                                </tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class='pagination pagination-sm justify-content-center' id='repossessedUnitsPagination'>
                            </ul>
                        </nav>
                    </div>

                    <div class='tab-pane fade' id='scrappedUnits' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Scrapped Units Log</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" id="scrappedUnitsSearch" class="form-control"
                                    placeholder="Search...">
                                <button class="btn btn-primary text-white" type="button" id="scrappedUnitsSearchBtn"><i
                                        class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover' id='scrappedUnitsTable'>
                                <thead>
                                    <tr>
                                        <th>Scrap Date</th>
                                        <th>Model</th>
                                        <th>Engine No.</th>
                                        <th>Branch</th>
                                        <th class="text-end">Inventory Cost</th>
                                        <th>Reason</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='scrappedUnitsTableBody'>
                                </tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class='pagination pagination-sm justify-content-center' id='scrappedUnitsPagination'>
                            </ul>
                        </nav>
                    </div>

                    <div class='tab-pane fade' id='redeemedUnits' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Redeemed Units Log</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <input type="text" id="redeemedUnitsSearch" class="form-control"
                                    placeholder="Search...">
                                <button class="btn btn-primary text-white" type="button" id="redeemedUnitsSearchBtn"><i
                                        class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover' id='redeemedUnitsTable'>
                                <thead>
                                    <tr>
                                        <th>Redeem Date</th>
                                        <th>Original Repo Date</th>
                                        <th>Customer</th>
                                        <th>Model</th>
                                        <th>Engine No.</th>
                                        <th>Branch</th>
                                        <th class="text-end">Amount Paid</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='redeemedUnitsTableBody'>
                                </tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class='pagination pagination-sm justify-content-center' id='redeemedUnitsPagination'>
                            </ul>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
        </div>
    </main>

    <div class='modal fade' id='monthlyReportOptionsModal' tabindex='-1'
        aria-labelledby='monthlyReportOptionsModalLabel' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title text-white' id='monthlyReportOptionsModalLabel'><i
                            class='bi bi-file-earmark-text me-2 text-white'></i>Generate Reports</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <!-- Step 1: Report Type Selection -->
                    <div class='mb-4'>
                        <label for='reportType' class='form-label fw-bold'>1. Select Report Type</label>
                        <select class='form-select form-select-lg' id='reportType'>
                            <option value='inventory'>Inventory Balance Report</option>
                            <option value='inventory_summary'>Summary of Inventory</option>
                            <option value='transferred'>Summary of Transferred Stocks</option>
                            <option value='received'>Summary of Received Stocks</option>
                            <option value='delivered_stocks'>Summary of Delivered Stocks</option>
                            <option value='motorcycle'>Available Motorcycle Units Report</option>
                            <option value='sold_units'>Summary of Sold Units Report</option>
                            <option value='scrapped'>Summary of Scrapped Units Report</option>
                            <option value='redeemed'>Summary of Redeemed Units</option>
                        </select>
                    </div>

                    <!-- Step 2: Date & Period Options ( Dynamic ) -->
                    <div class='mb-4'>
                        <label class='form-label fw-bold'>2. Select Period</label>
                        <div id='periodOptionsContainer' class='p-3 bg-light border rounded'>
                            <!-- Radio buttons will be dynamically inserted here by JS -->
                        </div>
                    </div>

                    <!-- Date Picker Containers ( Dynamic ) -->
                    <div id='datePickerSection'>
                        <div id='dailyDatePickerContainer' class='mb-3' style='display: none;'>
                            <label for='dailyDate' class='form-label'>Select Date</label>
                            <input type='text' class='form-control datepicker' id='dailyDate'>
                        </div>
                        <div id='monthPickerContainer' class='mb-3' style='display: none;'>
                            <label for='reportMonth' class='form-label'>Select Month</label>
                            <input type='month' class='form-control' id='reportMonth'>
                        </div>
                        <div id='asOfDatePickerContainer' class='mb-3' style='display: none;'>
                            <label for='asOfDate' class='form-label'>Select As-of Date</label>
                            <input type='text' class='form-control datepicker' id='asOfDate'>
                        </div>
                        <div id='customDateRangeContainer' class='row mb-3' style='display: none;'>
                            <div class='col-md-6'>
                                <label for='startDate' class='form-label'>Start Date</label>
                                <input type='text' class='form-control datepicker' id='startDate'>
                            </div>
                            <div class='col-md-6'>
                                <label for='endDate' class='form-label'>End Date</label>
                                <input type='text' class='form-control datepicker' id='endDate'>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: General Filters -->
                    <div>
                        <label class='form-label fw-bold'>3. Apply Filters</label>
                        <div class='p-3 border rounded'>
                            <div class='row g-3'>
                                <div class='col-md-6'>
                                    <label for='reportBranch' class='form-label'>Branch</label>
                                    <select class='form-select' id='reportBranch'>
                                        <option value='ALL'>ALL BRANCHES</option>
                                        <!-- Options populated by JS -->
                                    </select>
                                </div>
                                <div class='col-md-6'>
                                    <label for='reportCategoryFilter' class='form-label'>Category</label>
                                    <select class='form-select' id='reportCategoryFilter'>
                                        <option value='all'>All Categories</option>
                                        <option value='brandnew'>Brand New</option>
                                        <option value='repo'>Repo</option>
                                    </select>
                                </div>
                                <div class='col-md-6'>
                                    <label for='reportBrandFilter' class='form-label'>Brand</label>
                                    <select class='form-select' id='reportBrandFilter'>
                                        <option value='all'>ALL BRANDS</option>
                                        <option value='Suzuki'>SUZUKI</option>
                                        <option value='Honda'>HONDA</option>
                                        <option value='Kawasaki'>KAWASAKI</option>
                                        <option value='Yamaha'>YAMAHA</option>
                                        <option value='Asiastar'>ASIASTAR</option>
                                    </select>
                                </div>
                                <div class='col-12'>
                                    <label for='reportModelSearch' class='form-label'>Model( s )</label>
                                    <div class='dropdown'>
                                        <div id='model-filter-container'
                                            class='form-control d-flex flex-wrap gap-1 align-items-center'
                                            style='min-height: 38px;' data-bs-toggle='dropdown' aria-expanded='false'>
                                            <span id='selected-models-tags' class='d-flex flex-wrap gap-1'>
                                            </span>
                                            <input type='text' id='reportModelSearch' class='flex-grow-1 border-0 p-0'
                                                placeholder='Search to add models...'
                                                style='min-width: 150px; outline: none; box-shadow: none;'>
                                        </div>
                                        <ul id='model-search-results' class='dropdown-menu w-100'
                                            aria-labelledby='model-filter-container'
                                            style='max-height: 200px; overflow-y: auto;'>
                                        </ul>
                                    </div>
                                    <input type='hidden' id='reportModelFilter'>
                                </div>
                                <div class='col-md-6' id='soldSaleTypeContainer' style='display: none;'>
                                    <label for='soldSaleTypeFilter' class='form-label'>Type of Sale</label>
                                    <select class='form-select' id='soldSaleTypeFilter'>
                                        <option value='all'>All</option>
                                        <option value='COD'>COD</option>
                                        <option value='Installment'>Installment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-primary text-white' id='generateReportBtn'><i
                            class='bi bi-play-fill me-1'></i>Generate Report</button>
                </div>
            </div>
        </div>
    </div>

    <div id='monthlyReportPrintContainer' style='display: none;'></div>

    <div class='modal fade' id='monthlyInventoryReportModal' tabindex='-1'
        aria-labelledby='monthlyInventoryReportModalLabel' aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='monthlyInventoryReportModalLabel'>Monthly Report</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='d-flex justify-content-between mb-3'>
                        <div>
                            <button class='btn btn-sm btn-outline-primary' id='exportMonthlyReportToPDF'>
                                <i class='bi bi-printer'></i> Print Report
                            </button>
                        </div>
                        <div class='text-muted small' id='monthlyReportTimestamp'></div>
                    </div>
                    <div id='monthlyReportContent'>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='editMotorcycleModal' tabindex='-1' aria-labelledby='editMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='editMotorcycleModalLabel'>Edit Motorcycle Details</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='editMotorcycleForm'>
                        <input type='hidden' id='editId'>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editDateDelivered' class='form-label'>Date Delivered</label>
                                <input type='text' class='form-control datepicker' id='editDateDelivered'
                                    placeholder="mm/dd/yyyy">
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editDateReceived' class='form-label'>Date Received</label>
                                <input type='text' class='form-control datepicker' id='editDateReceived'
                                    placeholder="mm/dd/yyyy">
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editInvoiceNumber' class='form-label'>Invoice Number/MT</label>
                                <input type='text' class='form-control' id='editInvoiceNumber' required>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-4 mb-3'>
                                <label for='editBrand' class='form-label'>Brand</label>
                                <select class='form-select' id='editBrand' required>
                                    <option value='Suzuki'>Suzuki</option>
                                    <option value='Honda'>Honda</option>
                                    <option value='Kawasaki'>Kawasaki</option>
                                    <option value='Yamaha'>Yamaha</option>
                                    <option value='Asiastar'>Asiastar</option>
                                </select>
                            </div>
                            <div class='col-md-4 mb-3'>
                                <label for='editModel' class='form-label'>Model</label>
                                <input type='text' class='form-control' id='editModel' required>
                            </div>
                            <div class='col-md-4 mb-3'>
                                <label for='editCategory' class='form-label'>Category</label>
                                <select class='form-select' id='editCategory' required>
                                    <option value='brandnew'>Brand New</option>
                                    <option value='repo'>Repo</option>
                                </select>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editEngineNumber' class='form-label'>Engine Number</label>
                                <input type='text' class='form-control' id='editEngineNumber' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editFrameNumber' class='form-label'>Frame Number</label>
                                <input type='text' class='form-control' id='editFrameNumber' required>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editColor' class='form-label'>Color</label>
                                <input type='text' class='form-control' id='editColor' required>
                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editInventoryCost' class='form-label'>Inventory Cost</label>
                                <input type='number' step='0.01' class='form-control' id='editInventoryCost'>
                            </div>
                        </div>
                        <div class='row'>
                            <div class='col-md-6 mb-3'>
                                <label for='editCurrentBranch' class='form-label'>Branch</label>

                                <select class='form-select' id='editCurrentBranch' required>
                                    <option value='HEADOFFICE'>HEADOFFICE</option>
                                    <option value='KINGDOM'>KINGDOM</option>
                                    <option value='TANQUE'>TANQUE</option>
                                    <option value='DFISHER'>DFISHER</option>
                                    <option value='ROXAS SUZUKI'>ROXAS SUZUKI</option>
                                    <option value='MAMBUSAO'>MAMBUSAO</option>
                                    <option value='SIGMA'>SIGMA</option>
                                    <option value='PRC'>PRC</option>
                                    <option value='BAILAN'>BAILAN</option>
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
                                    <option value='PEMDI BACOLOD'>PEMDI BACOLOD</option>
                                    <option value='INFINITY BACOLOD'>INFINITY BACOLOD</option>
                                    <option value='EEMSI-GUIMARAS'>EEMSI-GUIMARAS</option>
                                    <option value='AJUY'>AJUY</option>
                                    <option value='MINDORO-MB'>MINDORO-MB</option>
                                    <option value='MINDORO ROXAS'>MINDORO ROXAS</option>
                                    <option value='3S MINDORO'>3S MINDORO</option>
                                    <option value='MINDORO MANSALAY'>MINDORO MANSALAY</option>
                                    <option value='K-RIDERS ROXAS'>K-RIDERS ROXAS</option>
                                    <option value='IBAJAY'>IBAJAY</option>
                                    <option value='NUMANCIA'>NUMANCIA</option>
                                    <option value='CFCIPRC'>CFCIPRC</option>

                                </select>

                            </div>
                            <div class='col-md-6 mb-3'>
                                <label for='editStatus' class='form-label'>Status</label>
                                <select class='form-select' id='editStatus' required>
                                    <option value='available'>Available</option>
                                    <option value='sold'>Sold</option>
                                    <option value='transferred'>Transferred</option>
                                    <option value='scrapped'>Scrapped</option>

                                </select>
                            </div>

                            <div id="redeemInfoContainer" class="col-12" style="display: none;">
                                <div class="alert alert-success small p-2">
                                    <h6 class="alert-heading small mb-1"><i class="bi bi-award-fill me-1"></i>Redemption
                                        Information</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Redeemed On:</strong> <span id="redeemInfoDate"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Amount Paid:</strong> <span id="redeemInfoAmount"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Sold Details Section -->
                            <div id='soldDetailsContainer' style='display: none;'>
                                <hr>
                                <h6 class='text-primary'>Sale Information</h6>
                                <div class="row">
                                    <div class='col-md-4 mb-2'>
                                        <label for='editSaleDate' class='form-label'><strong>Sale Date:</strong></label>
                                        <input type='text' class='form-control datepicker' id='editSaleDate'
                                            name='sale_date' placeholder="mm/dd/yyyy">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="editCustomerName" class="form-label"><strong>Customer
                                                Name:</strong></label>
                                        <input type="text" class="form-control" id="editCustomerName"
                                            name="customer_name">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label for="editPaymentType" class="form-label"><strong>Payment
                                                Type:</strong></label>
                                        <select class="form-select" id="editPaymentType" name="payment_type">
                                            <option value="">Select Payment Type</option>
                                            <option value="COD">COD</option>
                                            <option value="Installment">Installment</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- COD Details -->
                                <div id='codDetails' style='display: none;'>
                                    <div class='row'>
                                        <div class='col-md-6 mb-2'>
                                            <label for='editDrNumber' class='form-label'><strong>DR
                                                    Number:</strong></label>
                                            <input type='text' class='form-control' id='editDrNumber' name='dr_number'>
                                        </div>
                                        <div class='col-md-6 mb-2'>
                                            <label for='editCodAmount' class='form-label'><strong>COD
                                                    Amount:</strong></label>
                                            <input type='number' step='0.01' class='form-control' id='editCodAmount'
                                                name='cod_amount' min='0'>
                                        </div>
                                    </div>
                                </div>

                                <!-- Installment Details -->
                                <div id='installmentDetails' style='display: none;'>
                                    <div class='row'>
                                        <div class='col-md-6 mb-2'>
                                            <label for='editTerms' class='form-label'><strong>Terms ( months
                                                    ):</strong></label>
                                            <input type='number' class='form-control' id='editTerms' name='terms'
                                                min='1'>
                                        </div>
                                        <div class='col-md-6 mb-2'>
                                            <label for='editMonthlyAmortization' class='form-label'><strong>Monthly
                                                    Amortization:</strong></label>
                                            <input type='number' step='0.01' class='form-control'
                                                id='editMonthlyAmortization' name='monthly_amortization' min='0'>
                                        </div>
                                    </div>
                                </div>
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

    <div class='modal fade' id='sellMotorcycleModal' tabindex='-1' aria-labelledby='sellMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='sellMotorcycleModalLabel'>Mark Motorcycle as Sold</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='saleForm'>
                        <input type='hidden' id='sellMotorcycleId'>

                        <div class='mb-3'>
                            <label for='saleDate' class='form-label'>Sale Date <span
                                    class='text-danger'>*</span></label>
                            <input type='date' class='form-control' id='saleDate' required>
                        </div>

                        <div class='mb-3'>
                            <label for='customerName' class='form-label'>Customer Name <span
                                    class='text-danger'>*</span></label>
                            <input type='text' class='form-control' id='customerName' required>
                        </div>

                        <div class='mb-3'>
                            <label for='paymentType' class='form-label'>Payment Type <span
                                    class='text-danger'>*</span></label>
                            <select class='form-select' id='paymentType' onchange='handlePaymentTypeChange()' required>
                                <option value=''>Select Payment Type</option>
                                <option value='COD'>Cash on Delivery ( COD )</option>
                                <option value='Installment'>Installment</option>
                            </select>
                        </div>

                        <div id='codFields' style='display: none;'>
                            <div class='mb-3'>
                                <label for='drNumber' class='form-label'>DR Number <span
                                        class='text-danger'>*</span></label>
                                <input type='text' class='form-control' id='drNumber'>
                            </div>

                            <div class='mb-3'>
                                <label for='codAmount' class='form-label'>COD Amount <span
                                        class='text-danger'>*</span></label>
                                <input type='number' step='0.01' class='form-control' id='codAmount'>
                            </div>
                        </div>

                        <div id='installmentFields' style='display: none;'>
                            <div class='mb-3'>
                                <label for='terms' class='form-label'>Terms ( months ) <span
                                        class='text-danger'>*</span></label>
                                <input type='number' class='form-control' id='terms' min='1'>
                            </div>

                            <div class='mb-3'>
                                <label for='monthlyAmortization' class='form-label'>Monthly Amortization <span
                                        class='text-danger'>*</span></label>
                                <input type='number' step='0.01' class='form-control' id='monthlyAmortization'>
                            </div>
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-primary text-white' onclick='submitSale()'>Mark as
                        Sold</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='scrapMotorcycleModal' tabindex='-1' aria-labelledby='scrapMotorcycleModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='scrapMotorcycleModalLabel'>Mark Motorcycle as Scrapped</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='scrapForm'>
                        <input type='hidden' id='scrapMotorcycleId'>

                        <div class='mb-3'>
                            <label for='scrapDate' class='form-label'>Scrap Date <span
                                    class='text-danger'>*</span></label>
                            <input type='date' class='form-control' id='scrapDate' required>
                        </div>

                        <div class='mb-3'>
                            <label for='scrapReason' class='form-label'>Reason for Scrapping</label>
                            <textarea class='form-control' id='scrapReason' rows='3'
                                placeholder='Optional reason for scrapping...'></textarea>
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-warning' onclick='submitScrap()'>Mark as Scrapped</button>
                </div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='repoModal' tabindex='-1' aria-labelledby='repoModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='repoModalLabel'>Mark Motorcycle as Repossessed</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <form id='repoForm'>
                        <input type='hidden' id='repoMotorcycleId'>

                        <div class='mb-3'>
                            <label for='repoDate' class='form-label'>Repo Date <span
                                    class='text-danger'>*</span></label>
                            <input type='date' class='form-control' id='repoDate' required>
                        </div>

                        <div class='mb-3'>
                            <label for='repoReason' class='form-label'>Reason for Repossession</label>
                            <textarea class='form-control' id='repoReason' rows='3'
                                placeholder='Enter reason (optional)'></textarea>
                        </div>
                    </form>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' class='btn btn-success' onclick='submitRepo()'>Confirm REPO</button>
                </div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='viewDetailsModal' tabindex='-1' aria-labelledby='viewDetailsModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='viewDetailsModalLabel'>Motorcycle Details</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='mb-3'>
                                <h6>Basic Information</h6>
                                <hr>
                                <p><strong>Invoice Number/MT:</strong> <span id='detailInvoiceNumber'></span></p>
                                <p><strong>Brand:</strong> <span id='detailBrand'></span></p>
                                <p><strong>Model:</strong> <span id='detailModel'></span></p>
                                <p><strong>Color:</strong> <span id='detailColor'></span></p>
                                <p><strong>Date Delivered:</strong> <span id='detailDateDelivered'></span></p>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='mb-3'>
                                <h6>Identification Numbers</h6>
                                <hr>
                                <p><strong>Engine Number:</strong> <span id='detailEngineNumber'></span></p>
                                <p><strong>Frame Number:</strong> <span id='detailFrameNumber'></span></p>
                            </div>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-6'>
                            <div class='mb-3'>
                                <h6>Inventory Details</h6>
                                <hr>
                                <p><strong>Current Branch:</strong> <span id='detailCurrentBranch'></span></p>
                                <p><strong>Status:</strong> <span id='detailStatus'></span></p>
                                <p><strong>Inventory Cost:</strong> <span id='detailInventoryCost'></span></p>
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class='mb-3'>
                                <h6>Location Map</h6>
                                <hr>
                                <div id='detailMap' style='height: 200px;'></div>
                            </div>
                        </div>
                    </div>
                    <div class='mb-3'>
                        <h6>Transfer History</h6>
                        <hr>
                        <div class='table-responsive'>
                            <table class='table table-sm' id='transferHistoryTable'>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id='transferHistoryBody'>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>





    <div class='modal fade' id='searchTransferReceiptModal' tabindex='-1'
        aria-labelledby='searchTransferReceiptModalLabel' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='searchTransferReceiptModalLabel'>Search Transfer Receipt</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='mb-3'>
                        <label for='transferInvoiceSearch' class='form-label'>Transfer Invoice Number</label>
                        <input type='text' class='form-control' id='transferInvoiceSearch'
                            placeholder='Enter transfer invoice number'>
                    </div>
                    <div id='searchResultsContainer' class='mt-3' style='display: none;'>
                        <h6>Search Results:</h6>
                        <div id='transferSearchResults' class='search-results'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-primary text-white' id='searchTransferBtn'>Search</button>
                </div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='searchInvoiceNumberModal' tabindex='-1' aria-labelledby='searchInvoiceNumberModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='searchInvoiceNumberModalLabel'>Search Invoice Number</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='mb-3'>
                        <label for='invoiceNumberSearch' class='form-label'>Invoice Number</label>
                        <input type='text' class='form-control' id='invoiceNumberSearch'
                            placeholder='Enter invoice number'>
                    </div>
                    <div id='invoiceSearchResultsContainer' class='mt-3' style='display: none;'>
                        <h6>Search Results:</h6>
                        <div id='invoiceSearchResults' class='search-results'></div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-primary text-white' id='searchInvoiceBtn'>Search</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="revertConfirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Confirm Revert Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="revertMessage"></p>
                    <input type="hidden" id="revertId">
                    <input type="hidden" id="revertType">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmRevertBtn" class="btn btn-warning">Confirm Revert</button>
                </div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='incomingTransfersModal' tabindex='-1' aria-labelledby='incomingTransfersModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title text-white' id='incomingTransfersModalLabel'>Incoming Units Transferred to
                        Your Branch</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='row mb-3'>
                        <div class='col-md-6'>
                            <div class='d-flex align-items-center'>
                                <input type='checkbox' id='selectAllTransfers' class='form-check-input me-2'>
                                <label for='selectAllTransfers' class='form-check-label fw-semibold'>Select All</label>
                            </div>
                        </div>
                        <div class='col-md-6 text-end'>
                            <span class='badge bg-info' id='selectedTransfersCount'>0 selected</span>
                        </div>
                    </div>

                    <div class='table-responsive'>
                        <table class='table table-striped table-hover'>
                            <thead class='table-dark'>
                                <tr>
                                    <th width='50'>
                                        <input type='checkbox' id='selectAllTransfersHeader' class='form-check-input'>
                                    </th>
                                    <th>Model</th>
                                    <th>Engine No.</th>
                                    <th>Frame No.</th>
                                    <th>Color</th>
                                    <th>Transfer Date</th>
                                    <th>From Branch</th>
                                    <th>Transfer Invoice</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id='incomingTransfersBody'>
                                <tr>
                                    <td colspan='9' class='text-center py-4'>
                                        <div class='spinner-border text-primary' role='status'>
                                            <span class='visually-hidden'>Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class='alert alert-info mt-3' id='transferSummary' style='display: none;'>
                        <h6 class='alert-heading'>Transfer Summary</h6>
                        <div class='row'>
                            <div class='col-md-4'>
                                <strong>Selected Transfers:</strong> <span id='summarySelectedCount'>0</span>
                            </div>
                            <div class='col-md-4'>
                                <strong>Total Units:</strong> <span id='summaryTotalUnits'>0</span>
                            </div>
                            <div class='col-md-4'>
                                <strong>From Branches:</strong> <span id='summaryFromBranches'>-</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-warning me-2' id='rejectSelectedBtn' disabled>
                        <i class='bi bi-x-circle me-1'></i>Reject Selected
                    </button>
                    <button type='button' class='btn btn-success' id='acceptSelectedBtn' disabled>
                        <i class='bi bi-check-circle me-1'></i>Accept Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='transferReceiptModal' tabindex='-1' aria-labelledby='transferReceiptModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title text-white' id='transferReceiptModalLabel'>
                        <i class='bi bi-receipt me-2'></i>Transfer Receipt
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <div class='receipt-header mb-4'>
                        <div class='row'>
                            <div class='col-md-6'>
                                <h5 class='mb-1'>SOLID MOTORCYCLE DISTRIBUTORS, INC.</h5>
                                <p class='mb-0 text-muted'>Merchandise Transfer Receipt</p>
                            </div>
                            <div class='col-md-6 text-end'>
                                <p class='mb-0'><strong>Date:</strong> <span id='receiptDate'></span></p>
                                <p class='mb-0'><strong>Transfer Invoice No:</strong> <span
                                        id='receiptInvoiceNo'></span></p>
                                <!-- <p class = 'mb-0'><strong>Transfer ID:</strong> <span id = 'receiptTransferId'></span></p> -->
                            </div>
                        </div>
                        <hr>
                        <div class='row'>
                            <div class='col-md-6'>
                                <p class='mb-1'><strong>From:</strong> <span id='receiptFromBranch'></span></p>
                            </div>
                            <div class='col-md-6'>
                                <p class='mb-1'><strong>To:</strong> <span id='receiptToBranch'></span></p>
                            </div>
                        </div>
                    </div>

                    <div class='table-responsive'>
                        <table class='table table-bordered table-sm'>
                            <thead class='table-light'>
                                <tr>
                                    <th>#</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Color</th>
                                    <th>Engine Number</th>
                                    <th>Frame Number</th>
                                    <th class='text-end'>Inventory Cost</th>
                                </tr>
                            </thead>
                            <tbody id='receiptMotorcyclesList'>
                            </tbody>
                            <tfoot class='table-group-divider'>
                                <tr>
                                    <td colspan='6' class='text-end fw-bold'>Total Motorcycles:</td>
                                    <td class='text-end fw-bold' id='receiptTotalCount'>0</td>
                                </tr>
                                <tr>
                                    <td colspan='6' class='text-end fw-bold'>Total Inventory Cost:</td>
                                    <td class='text-end fw-bold' id='receiptTotalCost'>₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class='mt-4'>
                        <h6>Transfer Notes:</h6>
                        <p id='receiptNotes' class='text-muted fst-italic'>No notes provided.</p>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <!-- <button type = 'button' class = 'btn btn-warning' id = 'editTransferBtn'>
    <i class = 'bi bi-pencil-square me-2'></i> Manage Items
    </button> -->
                    <button type='button' class='btn btn-primary text-white' id='printReceiptBtn'>
                        <i class='bi bi-printer me-2'></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unit Movement History Modal -->
    <div class="modal fade" id="unitMovementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">Unit Movement History</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <h6 id="movementUnitTitle" class="mb-1 fw-bold"></h6>
                        <small id="movementUnitDetails" class="text-muted"></small>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Event</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Status</th>
                                    <th>Invoice #</th>
                                </tr>
                            </thead>
                            <tbody id="movementHistoryBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No movement history found for this
                                        unit.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <div class='modal fade' id='confirmationModal' tabindex='-1' aria-labelledby='confirmationModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='confirmationModalLabel'>Confirm Action</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body' id='confirmationMessage'>
                    Are you sure you want to proceed?
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='button' id='confirmActionBtn' class='btn btn-danger'>Confirm</button>
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
    <div class='modal fade' id='infoModal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-info text-white'>
                    <h5 class='modal-title'>Information</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-info-circle-fill text-info me-3' style='font-size: 2rem;'></i>
                        <span id='infoMessage'></span>
                    </div>
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

    <div class="modal fade" id="manageTransferModal" tabindex="-1" aria-labelledby="manageTransferModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="manageTransferModalLabel">Manage Transfer Items</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="manageTransferLoading" class="text-center py-5">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Loading transfer details...</p>
                    </div>

                    <div id="manageTransferContent" style="display: none;">
                        <div class="row g-3 mb-4 p-3 border rounded bg-light">
                            <div class="col-md-3">
                                <label for="manageTransferDate" class="form-label">Transfer Date</label>
                                <input type="text" class="form-control datepicker" id="manageTransferDate"
                                    placeholder="mm/dd/yyyy">
                            </div>
                            <div class="col-md-3">
                                <label for="manageTransferInvoice" class="form-label">MT / Invoice #</label>
                                <input type="text" class="form-control" id="manageTransferInvoice">
                            </div>
                            <div class="col-md-3">
                                <label for="manageTransferFromBranch" class="form-label">From Branch</label>
                                <select id="manageTransferFromBranch" class="form-select">
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="manageTransferToBranch" class="form-label">To Branch</label>
                                <select id="manageTransferToBranch" class="form-select">
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="manageTransferNotes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="manageTransferNotes" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between">
                                        <span>Items in Transfer</span>
                                        <div>
                                            <span class="badge bg-primary">Total: <span
                                                    id="manageTransferTotal">0</span></span>
                                            <span class="badge bg-success">Added: <span
                                                    id="manageTransferAdded">0</span></span>
                                            <span class="badge bg-danger">Removed: <span
                                                    id="manageTransferRemoved">0</span></span>
                                        </div>
                                    </div>
                                    <div class="card-body list-container" id="managingTransferInitialList">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        Add Available Units from <strong id="manageTransferFrom"></strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">Search for available units from the 'From' branch to
                                            add to this transfer.</p>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" id="manageTransferSearch"
                                                placeholder="Search by Engine # or Model...">
                                            <button class="btn btn-primary text-white" type="button"
                                                id="manageTransferSearchBtn"><i class="bi bi-search"></i></button>
                                        </div>
                                        <div class="list-group" id="manageTransferSearchResults"
                                            style="max-height: 300px; overflow-y: auto;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary text-white" id="saveTransferChangesBtn">Save
                        Changes</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='sellMotorcycleModal' tabindex='-1' aria-labelledby='sellMotorcycleModalLabel'        
        aria-hidden='true'>
                <div class='modal-dialog'>
                        <div class='modal-content'>
                                <div class='modal-header'>
                                        <h5 class='modal-title' id='sellMotorcycleModalLabel'>Mark Motorcycle as Sold
                    </h5>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                                    </div>
                                <div class='modal-body'>
                                        <form id='saleForm'>
                                                <input type='hidden' id='sellMotorcycleId'>
                                                <div class='mb-3'>
                                                        <label for='saleDate' class='form-label'>Sale Date <span        
                                                                class='text-danger'>*</span></label>
                                                        <input type='date' class='form-control' id='saleDate' required>
                                                    </div>
                                                <div class='mb-3'>
                                                        <label for='customerName' class='form-label'>Customer Name <span
                                                                        class='text-danger'>*</span></label>
                                                        <input type='text' class='form-control' id='customerName'
                                required>
                                                    </div>
                                                <div class='mb-3'>
                                                        <label for='paymentType' class='form-label'>Payment Type <span  
                                                                      class='text-danger'>*</span></label>
                                                        <select class='form-select' id='paymentType'
                                onchange='handlePaymentTypeChange()' required>
                                                                <option value=''>Select Payment Type</option>
                                                                <option value='COD'>Cash on Delivery ( COD )</option>
                                                                <option value='Installment'>Installment</option>
                                                            </select>
                                                    </div>
                                                <div id='codFields' style='display: none;'>
                                                        <div class='mb-3'>
                                                                <label for='drNumber' class='form-label'>DR Number <span
                                                                                class='text-danger'>*</span></label>
                                                                <input type='text' class='form-control' id='drNumber'>
                                                            </div>
                                                        <div class='mb-3'>
                                                                <label for='codAmount' class='form-label'>COD Amount
                                    <span                                         class='text-danger'>*</span></label>
                                                      <input type='number' step='0.01' class='form-control'
                                    id='codAmount'>
                                                            </div>
                                                    </div>
                                                <div id='installmentFields' style='display: none;'>
                                                        <div class='mb-3'>
                                                                <label for='terms' class='form-label'>Terms ( months )
                                    <span                                         class='text-danger'>*</span></label>
                                                                <input type='number' class='form-control' id='terms'
                                    min='1'>
                                                    </div>
                                                        <div class='mb-3'>
                                                                <label for='monthlyAmortization'
                                    class='form-label'>Monthly Amortization <span                                      
                                          class='text-danger'>*</span></label>
                                                                <input type='number' step='0.01' class='form-control'
                                    id='monthlyAmortization'>
                                                            </div>
                                                    </div>
                                            </form>
                                    </div>
                                <div class='modal-footer'>
                                        <button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Cancel</button>
                                        <button type='button' class='btn btn-primary text-white'
                        onclick='submitSale()'>Mark as
                                                Sold</button>
                                    </div>
                            </div>
                    </div>
            </div>

    <div class='modal fade' id='repoModal' tabindex='-1' aria-labelledby='repoModalLabel' aria-hidden='true'>
                <div class='modal-dialog'>
                        <div class='modal-content'>
                                <div class='modal-header'>
                                        <h5 class='modal-title' id='repoModalLabel'>Mark Motorcycle as Repossessed</h5>
                                        <button type='button' class='btn-close' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                                    </div>
                                <div class='modal-body'>
                            <form id='repoForm'>
                                                <input type='hidden' id='repoMotorcycleId'>
                                                <div class='mb-3'>
                                                        <label for='repoDate' class='form-label'>Repo Date <span        
                                                                class='text-danger'>*</span></label>
                                                        <input type='date' class='form-control' id='repoDate' required>
                                                    </div>
                                                <div class='mb-3'>
                                                        <label for='repoReason' class='form-label'>Reason for
                                Repossession</label>
                                                        <textarea class='form-control' id='repoReason' rows='3'        
                                                        placeholder='Enter reason (optional)'></textarea>
                                                    </div>
                                            </form>
                                    </div>
                                <div class='modal-footer'>
                                        <button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Cancel</button>
                                        <button type='button' class='btn btn-success' onclick='submitRepo()'>Confirm
                        REPO</button>
                                    </div>
                            </div>
                    </div>
            </div>

    <div class='modal fade' id='scrapMotorcycleModal' tabindex='-1' aria-labelledby='scrapMotorcycleModalLabel'        
        aria-hidden='true'>
                <div class='modal-dialog'>
                        <div class='modal-content'>
                                <div class='modal-header'>
                                        <h5 class='modal-title' id='scrapMotorcycleModalLabel'>Mark Motorcycle as
                        Scrapped</h5>
                                <button type='button' class='btn-close' data-bs-dismiss='modal'
                        aria-label='Close'></button>
                                    </div>
                                <div class='modal-body'>
                                        <form id='scrapForm'>
                                                <input type='hidden' id='scrapMotorcycleId'>
                                                <div class='mb-3'>
                                                        <label for='scrapDate' class='form-label'>Scrap Date <span      
                                                                  class='text-danger'>*</span></label>
                                                        <input type='date' class='form-control' id='scrapDate' required>
                                                    </div>
                                                <div class='mb-3'>
                                                        <label for='scrapReason' class='form-label'>Reason for
                                Scrapping</label>
                                                        <textarea class='form-control' id='scrapReason' rows='3'        
                                                    placeholder='Optional reason for scrapping...'></textarea>
                                                    </div>
                                            </form>
                                    </div>
                                <div class='modal-footer'>
                                  <button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Cancel</button>
                                        <button type='button' class='btn btn-warning' onclick='submitScrap()'>Mark as
                        Scrapped</button>
                                    </div>
                            </div>
                    </div>
            </div>

    <div class="modal fade" id="redeemModal" tabindex="-1" aria-labelledby="redeemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="redeemModalLabel">Redeem Repossessed Motorcycle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="redeemForm">
                        <input type="hidden" id="redeemMotorcycleId">

                        <h6 class="text-success">Redemption Details</h6>
                        <hr class="mt-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="redeemDate" class="form-label">Redeem Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="redeemDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="redeemAmountPaid" class="form-label">Amount Paid <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="redeemAmountPaid" required>
                            </div>
                        </div>

                        <h6 class="text-primary mt-3">New Sale Information</h6>
                        <hr class="mt-0">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="redeemSaleDate" class="form-label">Sale Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="redeemSaleDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="redeemCustomerName" class="form-label">Customer Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="redeemCustomerName" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="redeemPaymentType" class="form-label">Payment Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="redeemPaymentType" required>
                                    <option value="">Select Payment Type</option>
                                    <option value="COD">Cash on Delivery ( COD )</option>
                                    <option value="Installment">Installment</option>
                                </select>
                            </div>
                        </div>

                        <div id="redeemCodFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="redeemDrNumber" class="form-label">DR Number <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="redeemDrNumber">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="redeemCodAmount" class="form-label">COD Amount <span
                                            class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="redeemCodAmount">
                                </div>
                            </div>
                        </div>

                        <div id="redeemInstallmentFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="redeemTerms" class="form-label">Terms ( months ) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="redeemTerms" min="1">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="redeemMonthlyAmortization" class="form-label">Monthly Amortization <span
                                            class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                        id="redeemMonthlyAmortization">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitRedeemBtn">Confirm Redemption</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteTransferConfirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to permanently delete this transfer record? This action will remove the entire
                    transfer history associated with this invoice number and cannot be undone.
                    <input type="hidden" id="transferToDeleteId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteTransferBtn" class="btn btn-danger">Yes, Delete
                        Transfer</button>
                </div>
            </div>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.js"></script>
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.js"></script>
    <script src='https://unpkg.com/leaflet@1.9.3/dist/leaflet.js'></script>
    <script>
    const currentBranch = '<?php echo $_SESSION['user_branch'] ?? 'RXS-S'; ?>';
    const currentUserBranch = "<?php echo isset($_SESSION['user_branch']) ? $_SESSION['user_branch'] : ''; ?>";
    const currentUserPosition = "<?php echo isset($_SESSION['position']) ? $_SESSION['position'] : ''; ?>";
    const isHeadOffice = currentUserBranch === 'HEADOFFICE';
    const isAdminUser = ['ADMIN', 'IT STAFF', 'HEAD'].includes(currentUserPosition.toUpperCase());
    </script>
    <script src='../js/admin_inventory.js'></script>
</body>

</html>