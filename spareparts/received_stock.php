<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$userRole = $_SESSION['position'] ?? $_SESSION['user_role'] ?? 'user';
$adminRoles = ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'];
$canDelete = in_array(strtolower(trim($userRole)), array_map('strtolower', $adminRoles));
$filterType = $_GET['filter'] ?? 'all';
?>
<script>
    window.canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
    window.isBranchPage = true;
    window.currentBranch = "<?php echo $currentBranch; ?>";
    window.userRole = "<?php echo $userRole; ?>";
    window.filterType = "<?php echo $filterType; ?>";
</script>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='utf-8'>
    <title>RCSM - RECEIVED STOCK</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' rel='stylesheet'>
    <link href='../css/bootstrap.min.css' rel='stylesheet'>
    <link href='../css/style.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='../css/spareparts_inventory_style.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <link href='../css/spareparts_premium.css?v=<?php echo time(); ?>' rel='stylesheet'>
    <style>
        :root {
            --smdi-green: #004d40;
            --smdi-green-dark: #00251a;
            --smdi-green-light: #39796b;
            --smdi-grey: #343a40;
            --smdi-light-grey: #f8f9fa;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Arial, sans-serif !important;
        }

        .bg-primary {
            background-color: var(--smdi-green) !important;
        }

        .btn-primary {
            background-color: var(--smdi-green);
            border-color: var(--smdi-green);
        }

        .btn-primary:hover {
            background-color: var(--smdi-green-dark);
            border-color: var(--smdi-green-dark);
        }

        .btn-outline-primary {
            color: var(--smdi-green);
            border-color: var(--smdi-green);
        }

        .btn-outline-primary:hover {
            background-color: var(--smdi-green);
            border-color: var(--smdi-green);
            color: white;
        }

        .text-primary {
            color: var(--smdi-green) !important;
        }

        /* NEW HEADER STYLES mimicking inventory_in.php */
        .bg-navy {
            background-color: #395a53 !important; /* The dark cyan/grey from inventory_in */
        }
        
        .navbar-custom {
            background-color: var(--smdi-green);
            padding: 0.75rem 1rem;
            margin-bottom: 0px;
        }
        
        .navbar-custom .navbar-brand {
            color: white;
            font-weight: 700;
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .navbar-custom .back-btn {
            color: white !important;
            font-size: 1.25rem;
            margin-right: 1rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .navbar-custom .back-btn:hover {
            opacity: 0.8;
        }
        
        .navbar-custom .user-info {
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar-custom .user-info i {
            font-size: 1.1rem;
        }
        
        .navbar-custom .btn-logout {
            border: 1px solid rgba(255,255,255,0.5);
            color: white;
            background: transparent;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .navbar-custom .btn-logout:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--smdi-green);
            color: white;
            border-color: var(--smdi-green);
        }

        .nav-tabs .nav-link {
            color: var(--smdi-grey);
            font-weight: 500;
        }

        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s ease-in-out;
            background: white;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .modal-header.bg-primary {
            background-color: var(--smdi-green) !important;
            color: white !important;
        }

        .custom-tabs {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            background: transparent;
            gap: 15px;
            margin-bottom: 20px;
        }

        .custom-tabs .nav-link {
            color: #6c757d;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            background: white;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .custom-tabs .nav-link:hover {
            color: #343a40;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        }

        .custom-tabs .nav-link.active {
            color: #395a53;
            background-color: #e8f5e9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-bottom: 2px solid #395a53;
        }

        .custom-stat-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            height: 100%;
        }
        
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #343a40;
            margin: 0;
        }

        .modal-title i {
            margin-right: 10px;
        }

        .table thead th {
            background-color: var(--smdi-light-grey);
            color: var(--smdi-green);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .stock-zero {
            background-color: #fff5f5 !important;
        }

        .search-results-list {
            max-height: 200px;
            overflow-y: auto;
            position: absolute;
            z-index: 1050;
            width: 100%;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .transfer-overview-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 4px solid #dee2e6;
            transition: all 0.2s;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .transfer-overview-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .transfer-overview-card.status-in-transit {
            border-left-color: #ffc107;
        }

        .transfer-overview-card.status-completed {
            border-left-color: #198754;
        }

        .transfer-overview-card.status-rejected {
            border-left-color: #dc3545;
        }

        .transfer-card-meta {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .transfer-card-id {
            font-weight: 700;
            color: #343a40;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .transfer-card-branch {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .transfer-card-branch i {
            width: 18px;
            color: #adb5bd;
            font-size: 0.75rem;
        }

        .transfer-card-summary {
            background: #f8f9fa;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            margin-top: 10px;
            border: 1px dashed #dee2e6;
        }

        .transfer-card-actions {
            position: absolute;
            right: 10px;
            top: 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .transfer-card-btn {
            width: 28px;
            height: 28px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            font-size: 0.85rem;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
        }

        .transfer-card-btn:hover {
            background: #f8f9fa;
            color: var(--smdi-green);
            border-color: var(--smdi-green);
        }

        /* Shared Batch Action Bar Style */
        #inventoryBatchBar,
        #salesBatchBar,
        .batch-action-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            background: #fff;
            padding: 12px 25px;
            border-radius: 50px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            display: none;
            align-items: center;
            gap: 15px;
            border: 2px solid var(--smdi-green);
        }

        .selection-count {
            font-weight: 700;
            color: var(--smdi-green);
            padding-right: 15px;
            border-right: 1px solid #ddd;
        }

        .batch-btn {
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .batch-btn i {
            margin-right: 5px;
        }

        .inventory-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
    </style>
    <script>
        window.canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
        window.isBranchPage = true;
        window.currentBranch = "<?php echo $currentBranch; ?>";
    </script>
</head>

<body>



    <!-- VIEW INCOMING TRANSFER MODAL (XL) -->
    <div class='modal fade' id='viewIncomingTransferModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-xl modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-navy text-white py-3'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-box-arrow-in-down me-2"></i>Incoming
                        Spare Parts
                        Transferred to Warehouse</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-0'>
                    <!-- Selection Summary Bar -->
                    <div class="d-flex justify-content-between align-items-center px-4 py-2 bg-white border-bottom">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllIncoming">
                            <label class="form-check-label fw-bold text-muted" for="selectAllIncoming">Select
                                All</label>
                        </div>
                        <div id="incomingSelectionCount" class="badge rounded-pill bg-cyan px-3 py-2 d-none">
                            <span id="incomingSelectedNum">0</span> selected
                        </div>
                    </div>

                    <div class='table-responsive'>
                        <table class='table table-hover align-middle mb-0' id="incomingTransferTable">
                            <thead class="bg-dark-grey text-white">
                                <tr>
                                    <th class="ps-4" style="width: 40px;"></th>
                                    <th>Part No</th>
                                    <th>Part Name</th>
                                    <th>Brand</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Transfer Date</th>
                                    <th class="text-center">From</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id='incomingTransferDetailsBody'>
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4'>
                    <button type='button' class='btn btn-warning fw-bold px-4' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-outline-warning fw-bold px-4 d-none'
                        id="batchRejectIncomingBtn">
                        <i class="bi bi-x-circle me-1"></i> Reject Selected
                    </button>
                    <button type='button' class='btn btn-success-premium fw-bold px-4 d-none'
                        id="batchAcceptIncomingBtn">
                        <i class="bi bi-check2-circle me-1"></i> Accept Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW HEADER mimicking inventory_in.php -->
    <nav class="navbar navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <a href="warehouse_dashboard.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand">
                    RECEIVED STOCK
                </span>
            </div>
            <div class="user-info">
                <?php if (isset($_SESSION['username'])): ?>
                    <span><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($currentBranch); ?></span>
                <?php endif; ?>
                <a href="../api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <main class='container-fluid px-4 py-4'>
        <div class='card border-0 mb-4 bg-transparent shadow-none'>
            
            <!-- Custom Flat Navigation -->
            <!-- Tab navigation removed for single-purpose page -->
            <ul class="nav custom-tabs" id="mainTabs" role="tablist" style="display: none;">
                <li class="nav-item">
                    <button class="nav-link active" id="transfer-in-tab" data-bs-toggle="tab" data-bs-target="#sub-transfer-in" type="button" role="tab">
                        Transfers (In)
                    </button>
                </li>
            </ul>

            <div class='card-body p-0 bg-transparent'>
                <div class='tab-content' id='mainTabContent'>
                    <!-- TRANSFERS IN -->
                    <div class="tab-pane fade show active" id="sub-transfer-in" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <div>
                                <h4 class="mb-0 fw-bold"><i class="bi bi-box-arrow-in-down me-2"></i>Incoming
                                    Inventory Transfers</h4>
                                <p class="text-muted small mb-0 mt-1">These are items transferred to your branch
                                    that are awaiting your confirmation to be added to your inventory.</p>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-hover align-middle' id='incomingTransfersTable'>
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-center">Items</th>
                                        <th>From Branch</th>

                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='incomingTransfersTableBody'></tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <div class="text-muted small fw-semibold" id="incomingTransfersPageInfo"></div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0 gap-1" id="incomingTransfersPagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div> <!-- End mainTabContent -->
            </div> <!-- End card-body -->
        </div> <!-- End card shadow-sm -->
    </main>

    <div class='modal fade' id='addPartsInModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-xl modal-dialog-scrollable'>
            <form id='addPartsInForm' class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-box-arrow-in-down me-2"></i>Record Incoming Stock
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <!-- Section A: Stock Info -->
                    <div class="row g-3 mb-4 pb-3 border-bottom align-items-end">
                        <div class="col-sm-6 col-md-4">
                            <label class='form-label small fw-bold text-muted mb-1 text-nowrap'><i
                                    class="bi bi-calendar-date me-1"></i>Date Received *</label>
                            <input type='date' class='form-control form-control-sm' id='add_date' name='date' required>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class='form-label small fw-bold text-muted mb-1 text-nowrap'><i
                                    class="bi bi-hash me-1"></i>Invoice Number</label>
                            <input type='text' class='form-control form-control-sm' id='add_invoice_no'
                                name='invoice_no' placeholder="Enter Invoice #">
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class='form-label small fw-bold text-muted mb-1 text-nowrap'><i
                                    class="bi bi-hash me-1"></i>Reference No. (Optional)</label>
                            <input type='text' class='form-control form-control-sm' id='add_reference' name='reference'
                                placeholder="Optional">
                        </div>
                    </div>

                    <!-- Section B: Add Items -->
                    <ul class="nav nav-tabs nav-fill mb-3" id="addPartsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="search-existing-tab" data-bs-toggle="tab"
                                data-bs-target="#search-existing" type="button" role="tab"><i
                                    class="bi bi-search me-2"></i>Search Existing</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold" id="manual-entry-tab" data-bs-toggle="tab"
                                data-bs-target="#manual-entry" type="button" role="tab"><i
                                    class="bi bi-pencil-square me-2"></i>Manual Entry</button>
                        </li>
                    </ul>

                    <div class="tab-content mb-4" id="addPartsTabsContent">
                        <div class="tab-pane fade show active" id="search-existing" role="tabpanel">
                            <div class="position-relative">
                                <input type="text" id="addPartSearchInput" class="form-control form-control-lg"
                                    placeholder="🔍 Search by Part No. or Part Name...">
                                <div id="addPartSearchResults"
                                    class="list-group search-results-list d-none shadow-sm rounded-bottom"></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="manual-entry" role="tabpanel">
                            <div class="card bg-light border-0">
                                <div class="card-body p-3">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small mb-1 fw-bold text-muted">Brand *</label>
                                            <input type="text" id="manual_brand" class="form-control form-control-sm"
                                                placeholder="e.g. Honda">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small mb-1 fw-bold text-muted">Part No
                                                *</label>
                                            <input type="text" id="manual_part_no" class="form-control form-control-sm"
                                                placeholder="Required">
                                        </div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label small mb-1 fw-bold text-muted">Part Name
                                                *</label>
                                            <input type="text" id="manual_desc" class="form-control form-control-sm"
                                                placeholder="Required">
                                        </div>
                                        <div class="col-md-3 mt-2">
                                            <label class="form-label small mb-1 fw-bold text-muted">Qty *</label>
                                            <input type="number" id="manual_qty" class="form-control form-control-sm"
                                                value="1" min="1">
                                        </div>
                                        <div class="col-md-3 mt-2">
                                            <label class="form-label small mb-1 fw-bold text-muted">Cost</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" id="manual_cost" class="form-control" step="0.01"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-3 mt-2">
                                            <label class="form-label small mb-1 fw-bold text-muted">Sell
                                                Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" id="manual_price" class="form-control" step="0.01"
                                                    placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-3 mt-2 d-flex align-items-end">
                                            <button type="button"
                                                class="btn btn-primary btn-sm w-100 fw-bold text-white"
                                                id="addManualPartBtn" title="Press Enter to Add">
                                                <i class="bi bi-plus-circle me-1"></i> Add Item
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items to Add Table -->
                    <h6 class="border-bottom pb-2 fw-bold text-dark d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-ul me-2"></i>Items to Add</span>
                        <span class="badge bg-secondary rounded-pill" id="itemCountBadge">0 Items</span>
                    </h6>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered table-sm table-hover mb-0">
                            <thead class="table-light text-muted" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Brand</th>
                                    <th>Part Details</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-end">Sell Price</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="partsToAddList">
                                <tr id="emptyAddListRow">
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-box-seam display-4 d-block mb-2 text-secondary opacity-50"></i>
                                        <h6 class="fw-bold mb-1">No items yet</h6>
                                        <p class="small mb-0">Start by searching or adding manually</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Bar -->
                    <div class="mt-3 p-3 rounded" style="background-color: #ebf5f3; border: 1px solid #c8e6c9;">
                        <div class="row text-center fw-bold" style="color: var(--smdi-green-dark);">
                            <div class="col-4 border-end border-success border-opacity-25">
                                <span class="d-block small text-muted text-uppercase mb-1">Total Items</span>
                                <span class="fs-5" id="addTotalQty">0</span>
                            </div>
                            <div class="col-4 border-end border-success border-opacity-25">
                                <span class="d-block small text-muted text-uppercase mb-1">Total Cost</span>
                                <span class="fs-5 text-danger" id="addTotalCost">₱0.00</span>
                            </div>
                            <div class="col-4">
                                <span class="d-block small text-muted text-uppercase mb-1">Total Value</span>
                                <span class="fs-5 text-success" id="addTotalPrice">₱0.00</span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class='modal-footer bg-light border-top-0 flex-shrink-0'>
                    <button type='button' class='btn btn-outline-secondary px-4' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-primary px-4 fw-bold text-white'>Save Stock (IN)</button>
                </div>
            </form>
        </div>
    </div>
    <div class='modal fade' id='editPartModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <form id='editPartForm' enctype="multipart/form-data">
                    <div class='modal-header bg-dark text-white'>
                        <h5 class='modal-title text-white'><i class="bi bi-pencil-square me-2 "></i>Edit Part Details
                        </h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <input type="hidden" name="id" id="edit_part_id">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Branch</label>
                                <input type="text" class="form-control" name="branch" id="edit_branch" required>
                                <small class="text-muted" style="font-size: 0.7em;">Note: Only Admin can re-assign
                                    branch</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Brand</label>
                                <input type="text" class="form-control" name="brand" id="edit_brand">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Part Number</label>
                                <input type="text" class="form-control" name="part_no" id="edit_part_no" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" name="stock" id="edit_stock" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Part Name</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"
                                required></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Unit Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control" name="cost" id="edit_cost"
                                        required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control" name="price" id="edit_price"
                                        required>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <label class="form-label">Thumbnail Image (Optional)</label>
                                <input type="file" class="form-control" name="part_image" id="edit_part_image" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Minimum Stock (Low Stock Alert)</label>
                                <input type="number" class="form-control" name="min_stock" id="edit_min_stock" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Invoice Number</label>
                                <input type="text" class="form-control" name="invoice_no" id="edit_invoice_no"
                                    placeholder="Optional">
                            </div>
                            <div class="col-md-12 mt-2">
                                <label class="form-label">Warehouse Bin Location</label>
                                <input type="text" class="form-control" name="bin_location" id="edit_bin_location"
                                    placeholder="e.g. Row 2, Bin C">
                            </div>
                            <div class="col-12 mt-3" id="edit_reason_container">
                                <label class="form-label fw-bold text-danger">Reason for Adjustment</label>
                                <textarea class="form-control border-danger" name="change_reason"
                                    id="edit_change_reason" rows="2"
                                    placeholder="Required if changing quantity..."></textarea>
                                <small class="text-muted">Please provide a reason if you are manually updating the stock
                                    quantity.</small>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer bg-light'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-dark px-4 text-white'>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='sellPartsOutModal' tabindex='-1' aria-labelledby='sellPartsOutModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <form id='sellPartsOutForm'>
                    <div class='modal-header bg-dark text-white'>
                        <h5 class='modal-title text-white' id='sellPartsOutModalLabel'><i
                                class="bi bi-cart-plus me-2"></i>Record
                            New Sale</h5><button type='button' class='btn-close btn-close-white'
                            data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for='out_or_number' class='form-label'>Sales Invoice No.</label>
                                <input type='text' class='form-control' id='out_or_number' required autocomplete="off">
                                <div id="or_availability_feedback" class="small mt-1"></div>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label for='out_customer_name' class='form-label'>Customer Name</label>
                                <input type='text' class='form-control' id='out_customer_name' required
                                    autocomplete="off">
                                <div id="saleCustomerSearchResults" class="list-group border rounded shadow-sm mt-1"
                                    style="max-height: 200px; overflow-y: auto; position: absolute; width: calc(100% - 1.5rem); z-index: 1052; display: none; background: white;">
                                </div>
                            </div>
                            <div class="col-md-6"><label for='out_date' class='form-label'>Date of Sale</label><input
                                    type='date' class='form-control' id='out_date' required></div>
                            <div class="col-md-6"><label for='out_transaction_type' class='form-label'>Payment
                                    Type</label><select class='form-select' id='out_transaction_type' required>
                                    <option value='cash' selected>Cash Sales</option>
                                    <option value='charge'>Charge Sales (Installment)</option>
                                </select></div>
                        </div>
                        <div class="mb-3 position-relative">
                            <label class="form-label fw-bold small text-muted text-uppercase">Search Product</label>
                            <input type="text" id="salePartSearchInput" class="form-control"
                                placeholder="Enter Part No. or Part Name to add items..." autocomplete="off">
                            <div id="salePartSearchResults" class="list-group border rounded shadow-sm mt-1"
                                style="max-height: 200px; overflow-y: auto; position: absolute; width: 100%; z-index: 1051; display: none; background: white;">
                            </div>
                        </div>
                        <h6 class="mb-2">Sale Items</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-striped table-sm mb-0">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="partsForSaleList">
                                    <tr id="emptySaleListRow">
                                        <td colspan="5" class="text-center text-muted p-4">Cart is empty</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <div style="width: 320px;">
                                <hr>
                                <div class="d-flex justify-content-between fw-bold fs-5 text-dark"><span>Grand
                                        Total:</span><span id="pos-grand-total">₱0.00</span></div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer'><button type='button' class='btn btn-secondary'
                            data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-dark'><i
                                class="bi bi-check-circle me-2"></i>Confirm Sale</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='viewSaleDetailsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i>Sale Details</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4' id="saleDetailsContent">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading sale details...</p>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='recordPaymentModal' tabindex='-1' aria-labelledby='recordPaymentModalLabel'
        aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <form id='recordPaymentForm'>
                    <div class='modal-header bg-dark text-white'>
                        <h5 class='modal-title text-white' id='recordPaymentModalLabel'><i
                                class="bi bi-cash-coin me-2"></i>Record
                            Payment</h5><button type='button' class='btn-close btn-close-white'
                            data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-5'>
                        <div class='mb-3'><label for='payment_customer_search' class='form-label fw-bold'>Search
                                Customer with
                                Balance</label><input type='text' class='form-control' id='payment_customer_search'
                                placeholder="Type customer name..." autocomplete="off">
                            <div id="customerSearchResults" class="list-group mt-1 shadow-sm"></div>
                        </div>
                        <input type="hidden" id="payment_or_number" name="or_number">
                        <input type="hidden" id="payment_customer_name" name="customer_name">
                        <input type="hidden" id="payment_branch" name="branch">
                        <div class='mb-3'>
                            <label for='payment_receipt_no' class='form-label fw-bold text-success'>Payment Receipt
                                Number</label>
                            <input type='text' class='form-control border-success' id='payment_receipt_no'
                                name="payment_receipt_no" required placeholder="Enter the Receipt # issued">
                            <small id="balance_info" class="form-text text-muted d-block mt-2"></small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for='payment_date' class='form-label'>Payment Date</label>
                                <input type='date' class='form-control' id='payment_date' name="payment_date" required>
                            </div>
                            <div class="col-md-4">
                                <label for='payment_amount' class='form-label'>Amount Paid</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type='number' step='0.01' class='form-control' id='payment_amount'
                                        name="amount" min='0' required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for='payment_method' class='form-label'>Method</label>
                                <select class='form-select' id='payment_method' name="payment_method" required>
                                    <option value="Cash" selected>Cash</option>
                                    <option value="Check">Check</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <!-- Extra Payment Details -->
                        <div id="payment_details_container" class="mt-3 d-none">
                            <div class="row g-3">
                                <div class="col-md-6 payment-detail-field check-field d-none">
                                    <label class="form-label fw-bold">Check Number</label>
                                    <input type="text" class="form-control" name="check_number"
                                        id="payment_check_number">
                                </div>
                                <div class="col-md-6 payment-detail-field bank-transfer-field d-none">
                                    <label class="form-label fw-bold">Reference Number</label>
                                    <input type="text" class="form-control" name="reference_number"
                                        id="payment_reference_number">
                                </div>
                                <div class="col-md-6 payment-detail-field check-field bank-transfer-field d-none">
                                    <label class="form-label fw-bold">Bank Name</label>
                                    <input type="text" class="form-control" name="bank_name" id="payment_bank_name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer'><button type='button' class='btn btn-secondary '
                            data-bs-dismiss='modal'>Close</button><button type='submit'
                            class='btn btn-dark text-white'><i class=" text-white bi bi-check-lg me-2"></i>Record
                            Payment</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class='modal fade' id='editPaymentModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <form id="editPaymentForm">
                    <div class='modal-header bg-warning text-dark border-0'>
                        <h5 class='modal-title fw-bold'><i class="bi bi-pencil-square me-2"></i>Edit Payment</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-5'>
                        <input type="hidden" id="edit_payment_id" name="payment_id">

                        <div class='mb-3'>
                            <label class='form-label fw-bold'>Customer Name</label>
                            <input type='text' class='form-control-plaintext fw-bold' id='edit_payment_customer'
                                readonly>
                        </div>

                        <div class='mb-3'>
                            <label for='edit_payment_receipt_no' class='form-label fw-bold text-success'>Payment Receipt
                                Number</label>
                            <input type='text' class='form-control border-success' id='edit_payment_receipt_no'
                                name="payment_receipt_no" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for='edit_payment_date' class='form-label'>Payment Date</label>
                                <input type='date' class='form-control' id='edit_payment_date' name="payment_date"
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label for='edit_payment_amount' class='form-label'>Amount Paid</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type='number' step='0.01' class='form-control' id='edit_payment_amount'
                                        name="amount" min='0' required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for='edit_payment_method' class='form-label'>Method</label>
                                <select class='form-select' id='edit_payment_method' name="payment_method" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Check">Check</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <!-- Extra Payment Details -->
                        <div id="edit_payment_details_container" class="mt-3 d-none">
                            <div class="row g-3">
                                <div class="col-md-6 edit-payment-detail-field edit-check-field d-none">
                                    <label class="form-label fw-bold">Check Number</label>
                                    <input type="text" class="form-control" name="check_number"
                                        id="edit_payment_check_number">
                                </div>
                                <div class="col-md-6 edit-payment-detail-field edit-bank-transfer-field d-none">
                                    <label class="form-label fw-bold">Reference Number</label>
                                    <input type="text" class="form-control" name="reference_number"
                                        id="edit_payment_reference_number">
                                </div>
                                <div
                                    class="col-md-6 edit-payment-detail-field edit-check-field edit-bank-transfer-field d-none">
                                    <label class="form-label fw-bold">Bank Name</label>
                                    <input type="text" class="form-control" name="bank_name"
                                        id="edit_payment_bank_name">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-warning text-dark fw-bold'><i
                                class="bi bi-save me-2"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='transferPartsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-scrollable'>
            <form id='transferPartsForm' class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-dark text-white border-0'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-arrow-left-right me-2"></i>Transfer Spare
                        Parts</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class='form-label fw-bold small text-muted text-uppercase'>Transfer Date</label>
                            <input type='date' class='form-control border-0 bg-light fw-bold' id='transfer_date'
                                name='transfer_date' required>
                        </div>
                        <div class="col-md-6">
                            <label class='form-label fw-bold small text-muted text-uppercase'>Destination Branch</label>
                            <select class='form-select border-0 bg-light fw-bold' id='to_branch' name='to_branch'
                                required>
                                <option value="" disabled selected>Select destination...</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Search Part to
                            Transfer</label>
                        <input type="text" id="transferPartSearchInput" class="form-control"
                            placeholder="Enter Part No. or Part Name to add items..." autocomplete="off">
                        <div id="transferPartSearchResults" class="list-group border rounded shadow-sm mt-1"
                            style="max-height: 200px; overflow-y: auto; position: absolute; width: 95%; z-index: 1051; display: none;">
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 small text-muted text-uppercase">Items to Transfer</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-striped table-sm mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="ps-3 py-2">Part Details</th>
                                    <th class="text-center py-2" style="width: 120px;">Qty</th>
                                    <th class="text-center py-2" style="width: 150px;">Cost (ea)</th>
                                    <th class="text-center py-2" style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="partsToTransferList">
                                <tr id="emptyTransferListRow">
                                    <td colspan="4" class="text-center text-muted p-5">
                                        <i class="bi bi-search d-block fs-3 mb-2 opacity-25"></i>
                                        Use the search bar above to add items
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4 pt-0'>
                    <button type='button' class='btn btn-light fw-bold px-4' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-dark fw-bold px-4'>Initiate Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <div class='modal fade' id='editSaleModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-dialog-centered'>
            <div class='modal-content border-0 shadow'>
                <form id='editSaleForm'>
                    <div class='modal-header bg-dark text-white py-3'>
                        <h5 class='modal-title fw-bold'><i class="bi bi-pencil-square me-2"></i>Edit Sale </h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <div class="alert alert-info border-0 shadow-sm small py-2 mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>Adjust transaction metadata below. Financial
                            adjustments will update aging balances.
                        </div>
                        <input type="hidden" name="original_or" id="edit_sale_original_or">
                        <input type="hidden" name="original_branch" id="edit_sale_original_branch">

                        <div class='row g-3'>
                            <div class='col-md-6 text-center border-end'>
                                <div class="p-3 bg-light rounded-3">
                                    <div class='x-small fw-bold text-muted text-uppercase mb-1'>Current ID</div>
                                    <div class='fs-4 fw-bold text-primary font-monospace' id='edit_sale_or_display'>-
                                    </div>
                                    <div class="small text-muted" id="edit_sale_branch_display">-</div>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>New OR
                                    Number</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="bi bi-hash"></i></span>
                                    <input type='text' class='form-control border-start-0 ps-0 font-monospace'
                                        id='edit_sale_or' name="new_or_number" required>
                                </div>
                                <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Selling
                                    Branch</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="bi bi-geo-alt"></i></span>
                                    <select class='form-select border-start-0 ps-0' id='edit_sale_branch'
                                        name="from_location" required>
                                        <!-- Populated via JS -->
                                    </select>
                                </div>
                            </div>

                            <hr class="my-2 opacity-10">

                            <div class='col-12'>
                                <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Customer Full
                                    Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="bi bi-person"></i></span>
                                    <input type='text' class='form-control border-start-0 ps-0 fw-bold'
                                        id='edit_sale_customer' name="customer_name" required>
                                </div>
                            </div>

                            <div class='col-md-6'>
                                <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Transaction
                                    Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="bi bi-calendar3"></i></span>
                                    <input type='date' class='form-control border-start-0 ps-0' id='edit_sale_date'
                                        name="sale_date" required>
                                </div>
                            </div>

                            <div class='col-md-6'>
                                <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Transaction
                                    Type</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="bi bi-credit-card-2-front"></i></span>
                                    <select class='form-select border-start-0 ps-0' id='edit_sale_type'
                                        name="transaction_type" required>
                                        <option value="cash">Cash Sales</option>
                                        <option value="charge">Charge Sales (Installment)</option>
                                    </select>
                                </div>
                            </div>

                            <div class='col-12'>
                                <label class='form-label x-small fw-bold text-muted text-uppercase mb-1'>Total Sale
                                    Amount (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 fw-bold text-muted">₱</span>
                                    <input type='number' step="0.01"
                                        class='form-control border-start-0 ps-0 fs-5 fw-bold' id='edit_sale_amount'
                                        name="total_amount" required>
                                </div>
                                <small class="text-muted">Adjusting this updates charge balances.</small>
                            </div>

                            <div class='col-12 mt-3'>
                                <label class='form-label x-small fw-bold text-danger text-uppercase mb-1'>Reason for
                                    Revision</label>
                                <textarea class='form-control border-danger' id='edit_sale_reason' name="reason"
                                    required rows="2"
                                    placeholder="Mandatory: Why are you editing this sale?"></textarea>
                                <small class="text-muted">Audit logging is mandatory for metadata revisions.</small>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer bg-light py-3 border-top-0'>
                        <button type='button' class='btn btn-outline-secondary px-4'
                            data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-dark px-4'><i class="bi bi-check2-circle me-1"></i>Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='viewHistoryModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
            <div class='modal-content border-0'>
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title text-white fw-bold'>Spare Parts Movement History</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="mb-3">
                        <h5 class="fw-bold mb-1 text-success" id="historyPartDescription"></h5>
                        <div class="text-muted small">
                            Brand: <span id="historyPartBrand"></span> | Part #: <span id="historyPartNumber"></span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="border: 1px solid #e0e0e0;">
                            <thead class="bg-light text-success">
                                <tr>
                                    <th class="py-2">Date</th>
                                    <th class="py-2">Event</th>
                                    <th class="py-2 text-center">Qty</th>
                                    <th class="py-2">From</th>
                                    <th class="py-2">To</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Invoice #</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4 pt-0'>
                    <button type='button' class='btn btn-success text-white fw-bold px-4'
                        data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='receiptModal' tabindex='-1'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white' id="receiptTitle"></h5><button type='button'
                        class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-0' id='receiptBody'></div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary no-print'
                        data-bs-dismiss='modal'>Close</button><button type='button'
                        class='btn btn-outline-dark no-print' id="downloadPdfBtn"><i
                            class="bi bi-file-pdf me-2"></i>Download PDF</button><button type='button'
                        class='btn btn-dark no-print' onclick="window.print();"><i
                            class="bi bi-printer me-2"></i>Print</button></div>
            </div>
        </div>
    </div>

    <!-- STOCK CARD MODAL -->
    <div class='modal fade' id='stockCardModal' tabindex='-1'>
        <div class='modal-dialog modal-xl modal-dialog-scrollable'>
            <div class='modal-content border-0 shadow' style="border-radius: 12px; border-top: 5px solid #26a69a !important;">
                <div class='modal-header bg-white border-0 px-4 pt-4 pb-0'>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-upc-scan fs-3 me-3" style="color: #004d40;"></i>
                        <div>
                            <h5 class='modal-title fw-bold mb-0' style="color: #004d40;">Stock Card Master</h5>
                            <small id="stockCardPartNo" class="text-muted fw-bold">PART NO</small>
                        </div>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body px-4 py-3 bg-white'>
                    <div class="row g-4">
                        <!-- Left Panel: Profile & Details -->
                        <div class="col-md-5">
                            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: #f4f7f6;">
                                <div class="card-body p-4">
                                    <div class="text-center mb-4">
                                        <div id="stockCardImage" class="rounded-4 border bg-white mx-auto d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 180px; height: 180px;">
                                            <i class="bi bi-box-seam text-muted" style="font-size: 4rem;"></i>
                                        </div>
                                    </div>
                                    <h5 id="stockCardDescription" class="fw-bold text-center mb-1 text-dark">PART DESCRIPTION</h5>
                                    <p id="stockCardBrand" class="text-muted text-center mb-4 small fw-bold text-uppercase">BRAND NAME</p>
                                    
                                    <div class="row g-3 mb-4">
                                        <div class="col-6">
                                            <div class="p-3 bg-white rounded-4 border text-center shadow-sm">
                                                <div class="small text-muted fw-bold mb-1">STOCK LELVEL</div>
                                                <div id="stockCardQty" class="fw-bold fs-4" style="color: #004d40;">0</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-white rounded-4 border text-center shadow-sm">
                                                <div class="small text-muted fw-bold mb-1">BIN LOCATION</div>
                                                <div id="stockCardBin" class="badge mt-2" style="background: #26a69a; font-size: 0.85rem;">N/A</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-4">
                                        <div class="col-6">
                                            <label class="small text-muted fw-bold d-block mb-1">Cost</label>
                                            <div id="stockCardCost" class="fw-bold border rounded-3 px-3 py-2 bg-white text-muted">₱0.00</div>
                                        </div>
                                        <div class="col-6">
                                            <label class="small text-muted fw-bold d-block mb-1">Selling Price</label>
                                            <div id="stockCardPrice" class="fw-bold border rounded-3 px-3 py-2 bg-white text-success">₱0.00</div>
                                        </div>
                                    </div>

                                    <label class="small text-muted fw-bold d-block mb-2">Compatibility Tags</label>
                                    <div id="stockCardCompatibility" class="d-flex flex-wrap gap-2">
                                        <!-- Badges -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Panel: History Tabs -->
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                                    <ul class="nav nav-tabs border-bottom-0 gap-2" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link active fw-bold px-4 border-0 rounded-top pb-3" style="color: #004d40; border-bottom: 3px solid #26a69a !important; background: transparent;" data-bs-toggle="tab" data-bs-target="#sc-movement">Movement Log</button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link fw-bold px-4 border-0 rounded-top text-muted pb-3" style="background: transparent;" data-bs-toggle="tab" data-bs-target="#sc-history" onclick="$(this).css('border-bottom', '3px solid #26a69a').css('color', '#004d40'); $(this).parent().siblings().find('button').css('border-bottom', 'none').css('color', '#6c757d');">Cost History</button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body p-0">
                                    <div class="tab-content h-100">
                                        <div class="tab-pane fade show active h-100" id="sc-movement">
                                            <div class="table-responsive h-100" style="max-height: 500px;">
                                                <table class="table table-hover align-middle mb-0 small">
                                                    <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
                                                        <tr>
                                                            <th class="ps-4 text-muted fw-bold border-0 py-3">Date</th>
                                                            <th class="text-muted fw-bold border-0 py-3">Type</th>
                                                            <th class="text-center text-muted fw-bold border-0 py-3">Qty</th>
                                                            <th class="text-muted fw-bold border-0 py-3">To/From</th>
                                                            <th class="pe-4 text-muted fw-bold border-0 py-3">Ref #</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="stockCardMovementBody" class="border-top-0">
                                                        <!-- Dynamic -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade h-100" id="sc-history">
                                            <div class="table-responsive h-100" style="max-height: 500px;">
                                                <table class="table table-hover align-middle mb-0 small">
                                                    <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
                                                        <tr>
                                                            <th class="ps-4 text-muted fw-bold border-0 py-3">Date</th>
                                                            <th class="text-muted fw-bold border-0 py-3">Supplier</th>
                                                            <th class="text-end text-muted fw-bold border-0 py-3">Cost</th>
                                                            <th class="pe-4 text-muted fw-bold border-0 py-3">Invoice #</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="stockCardHistoryBody" class="border-top-0">
                                                        <!-- Dynamic -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- End modal-body -->
            </div>
        </div>
    </div>
    <div class='modal fade' id='viewIncomingTransferModal' tabindex='-1'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'>Incoming Transfer Details</h5><button type='button'
                        class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4' id='incomingTransferDetailsBody'></div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Cancel</button><button type='button' class='btn btn-success'
                        id="confirmReceiveBtn">Confirm and Receive Items</button></div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='successModal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title'>Success</h5><button type='button' class='btn-close btn-close-white'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body'>
                    <p id='successMessage'></p>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-success'
                        data-bs-dismiss='modal'>OK</button></div>
            </div>
        </div>
    </div>
    <div class='modal fade' id='errorModal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-danger text-white'>
                    <h5 class='modal-title'>Error</h5><button type='button' class='btn-close btn-close-white'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body'>
                    <p id='errorMessage'></p>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-danger'
                        data-bs-dismiss='modal'>OK</button></div>
            </div>
        </div>
    </div>


    <div class='modal fade' id='transferPartsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-scrollable'>
            <form id='transferPartsForm' class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-success text-white border-0'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-arrow-left-right me-2"></i>Transfer Spare
                        Parts</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class='form-label fw-bold small text-muted text-uppercase'>Transfer Date</label>
                            <input type='date' class='form-control border-0 bg-light fw-bold' id='transfer_date'
                                name='transfer_date' required>
                        </div>
                        <div class="col-md-6">
                            <label class='form-label fw-bold small text-muted text-uppercase'>Destination Branch</label>
                            <select class='form-select border-0 bg-light fw-bold' id='to_branch' name='to_branch'
                                required>
                                <option value="" disabled selected>Select destination...</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Search Part to
                            Transfer</label>
                        <input type="text" id="transferPartSearchInput" class="form-control"
                            placeholder="Enter Part No. or Part Name to add items..." autocomplete="off">
                        <div id="transferPartSearchResults" class="list-group border rounded shadow-sm mt-1"
                            style="max-height: 200px; overflow-y: auto; position: absolute; width: 95%; z-index: 1051; display: none;">
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 small text-muted text-uppercase">Items to Transfer</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-striped table-sm mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="ps-3 py-2">Part Details</th>
                                    <th class="text-center py-2" style="width: 120px;">Qty</th>
                                    <th class="text-center py-2" style="width: 150px;">Cost (ea)</th>
                                    <th class="text-center py-2" style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="partsToTransferList">
                                <tr id="emptyTransferListRow">
                                    <td colspan="4" class="text-center text-muted p-5">
                                        <i class="bi bi-search d-block fs-3 mb-2 opacity-25"></i>
                                        Use the search bar above to add items
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4 pt-0'>
                    <button type='button' class='btn btn-light fw-bold px-4' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' class='btn btn-dark-green fw-bold px-4'>Initiate Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <div class='modal fade' id='salesPreviewModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i>Sales Print/Export Preview</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold mb-1">Refine Sales Preview List</h6>
                            <p class="small text-muted mb-0">Review the records below before finalized printing or
                                exporting.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="salesPreviewSearch" class="form-control form-control-sm"
                                placeholder="Filter sales..." style="width: 200px;">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-layout-three-columns me-1"></i>Columns
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end p-3" id="salesPreviewColumnToggles"
                                    style="min-width: 200px;">
                                    <li>
                                        <div class="form-check"><input class="form-check-input sales-col-toggle"
                                                type="checkbox" value="0" checked id="scol0"><label
                                                class="form-check-label" for="scol0">Date</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input sales-col-toggle"
                                                type="checkbox" value="1" checked id="scol1"><label
                                                class="form-check-label" for="scol1">Customer</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input sales-col-toggle"
                                                type="checkbox" value="2" checked id="scol2"><label
                                                class="form-check-label" for="scol2">SI #</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input sales-col-toggle"
                                                type="checkbox" value="3" checked id="scol3"><label
                                                class="form-check-label" for="scol3">Total</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input sales-col-toggle"
                                                type="checkbox" value="4" checked id="scol4"><label
                                                class="form-check-label" for="scol4">Balance</label></div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle" id="salesPreviewTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>SI #</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="salesPreviewTableBody"></tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">TOTALS:</td>
                                    <td class="text-end text-success" id="salesPreviewTotalAmount">₱0.00</td>
                                    <td class="text-end text-danger" id="salesPreviewTotalBalance">₱0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class='modal-footer bg-light'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-success' id="finalizeSalesExportBtn">
                        <i class="bi bi-file-earmark-excel me-2"></i>Download CSV
                    </button>
                    <button type='button' class='btn btn-primary' id="finalizeSalesPrintBtn">
                        <i class="bi bi-printer me-2"></i>Print PDF
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- VIEW TRANSFER MODAL -->
    <div class='modal fade' id='viewTransferDetailsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content border-0 shadow'>
                <div class='modal-header bg-dark text-white border-0'>
                    <h5 class='modal-title'><i class="bi bi-info-circle me-2"></i>Transfer Details</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4' id="transferDetailsBody">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                <div class='modal-footer border-0 bg-light'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-danger d-none' id="rejectTransferBtn">Reject Transfer</button>
                    <button type='button' class='btn btn-success d-none' id="confirmReceiveBtn">Receive Items</button>
                </div>
            </div>
        </div>
    </div>

    <!-- REPORT MODALS -->
    <div class='modal fade' id='inventoryReportsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-file-earmark-bar-graph me-2 "></i>Generate
                        Inventory
                        Reports</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <form id="inv_generateInventoryReportForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Report
                                Type</label>
                            <select class="form-select border-0 bg-light fw-bold" id="inv_report_type" required>
                                <option value="inventory_balance">Inventory Balance Report</option>
                                <option value="inventory_summary">Inventory Summary (Tally Board)</option>
                                <option value="transferred_stocks">Summary of Transferred Stocks</option>
                                <option value="received_stocks">Summary of Received Stocks</option>
                                <option value="delivered_stocks">Summary of Delivered Stocks</option>
                                <option value="stock_in">Legacy: Stocks In</option>
                                <option value="stock_out">Legacy: Stocks Out</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Time
                                Period</label>
                            <select class="form-select border-0 bg-light fw-bold" id="inv_report_period" required>
                                <option value="monthly">Monthly</option>
                                <option value="daily">Daily / As of Date</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="mb-3" id="inv_report_date_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Date</label>
                            <input type="date" class="form-control border-0 bg-light fw-bold" id="inv_report_date"
                                required>
                        </div>
                        <div class="mb-3 d-none" id="inv_report_month_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Month/Year</label>
                            <input type="month" class="form-control border-0 bg-light fw-bold" id="inv_report_month">
                        </div>
                        <div class="mb-3 d-none" id="inv_report_year_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Year</label>
                            <input type="number" class="form-control border-0 bg-light fw-bold" id="inv_report_year"
                                min="2000" max="2099">
                        </div>
                        <div class="mb-3 d-none" id="inv_report_custom_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Custom Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" class="form-control border-0 bg-light fw-bold"
                                        id="inv_report_date_start">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control border-0 bg-light fw-bold"
                                        id="inv_report_date_end">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 d-none" id="inv_report_branch_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                            <select class="form-select border-0 bg-light fw-bold" id="inv_report_branch">
                                <option value="all">All Branches</option>
                            </select>
                        </div>
                        <hr>
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-funnel me-1"></i>Filters (Optional)
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small text-muted text-uppercase">Category</label>
                                <input type="text" class="form-control border-0 bg-light fw-bold"
                                    id="inv_report_filter_category" placeholder="e.g., Engine, Body...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small text-muted text-uppercase">Brand</label>
                                <input type="text" class="form-control border-0 bg-light fw-bold"
                                    id="inv_report_filter_brand" placeholder="e.g., Honda, Yamaha...">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase">Specific Part No.</label>
                            <input type="text" class="form-control border-0 bg-light fw-bold"
                                id="inv_report_filter_part_no" placeholder="Search by Part Number...">
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="inv_report_filter_low_stock">
                            <label class="form-check-label fw-bold small text-muted text-uppercase"
                                for="inv_report_filter_low_stock">Show Low Stock Items Only</label>
                        </div>
                    </form>
                </div>
                <div class='modal-footer border-0 p-4 pt-0'>
                    <button type='button' class='btn btn-light fw-bold px-4' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-primary fw-bold px-4 text-white' id="btnGenerateReport">
                        <i class="bi bi-eye me-2 "></i>Preview Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer  Modal -->
    <div class="modal fade" id="transferReportsModal" tabindex="-1" aria-labelledby="transferReportsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold text-white" id="transferReportsModalLabel">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Transfer Reports
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="transfer_generateReportForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Report Type</label>
                            <select class="form-select border-2" id="t_report_type" name="report_type">
                                <option value="all">All Transfers</option>
                                <option value="Completed">Completed Transfers</option>
                                <option value="In-Transit">In-Transit Transfers</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Time Period</label>
                            <select class="form-select border-2" id="t_report_period" name="period">
                                <option value="daily">Daily Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                        </div>

                        <div id="t_report_date_container">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Select Date</label>
                                <input type="date" class="form-control border-2" id="t_report_date" name="date_value">
                            </div>
                        </div>

                        <div id="t_report_month_container" class="d-none">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Select Month</label>
                                <input type="month" class="form-control border-2" id="t_report_month">
                            </div>
                        </div>

                        <div id="t_report_year_container" class="d-none">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Select Year</label>
                                <input type="number" class="form-control border-2" id="t_report_year" min="2000"
                                    max="2100">
                            </div>
                        </div>

                        <div class="mb-3 d-none">
                            <label class="form-label fw-bold small text-uppercase text-muted">Filter by Branch</label>
                            <select class="form-select border-2" id="t_report_branch" name="branch">
                                <option value="all">All Branches</option>
                                <!-- Dynamic Loading -->
                            </select>
                            <div class="form-text x-small mt-1"><i class="bi bi-info-circle me-1"></i>Filters transfers
                                where this branch is either the origin or destination.</div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-primary btn-lg fw-bold text-white"
                                id="btnGenerateTransferReport">
                                <i class="bi bi-eye me-2"></i>Generate Preview
                            </button>
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-danger w-100"
                                        id="btnGenerateTransferReportPdf">
                                        <i class="bi bi-file-pdf me-2"></i>PDF
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-outline-success w-100"
                                        id="btnGenerateTransferReportExcel">
                                        <i class="bi bi-file-excel me-2"></i>Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='inventoryPreviewModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i> Report Preview
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <!-- PRINT HEADER (Hidden on Screen) -->
                    <div class="print-only d-none mb-4">
                        <div class="text-center">
                            <h4 class="fw-bold mb-0">ROXAS CITY SOLID MERCHANDISING</h4>
                            <p class="small mb-0">1031 Victoria Bldg.,Roxas Avenue, Roxas City, Capiz</p>
                            <div style="border-bottom: 2px solid #333; margin-bottom: 20px;"></div>
                            <h5 class="text-uppercase fw-bold mb-1" id="printReportTitleDisplay">OFFICIAL REPORT</h5>
                            <p class="small text-muted">Date Generated: <?php echo date('F d, Y h:i A'); ?></p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <div>
                            <h6 class="fw-bold mb-1" id="inventoryPreviewSubtitle">Report Details</h6>
                            <p class="small text-muted mb-0">Review the records below before finalized printing or
                                exporting.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="inventoryPreviewSearch" class="form-control form-control-sm"
                                placeholder="Filter records..." style="width: 200px;">
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-9 border-end" id="reportPreviewMainCol">
                            <div id="reportSummaryTabsArea" class="mb-3 no-print"></div>
                            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                                <table class="table table-sm table-bordered align-middle" id="inventoryPreviewTable">
                                    <thead class="table-light sticky-top" id="inventoryPreviewHead"></thead>
                                    <tbody id="inventoryPreviewTableBody"></tbody>
                                    <tfoot class="table-light fw-bold" id="inventoryPreviewFoot"></tfoot>
                                </table>
                            </div>
                            <div id="reportBrandSummaryArea" class="mt-4"></div>
                        </div>
                        <div class="col-lg-3" id="reportSummarySidebarCol">
                            <div id="reportSummarySidebar"></div>
                        </div>
                    </div>

                    <!-- PRINT FOOTER / SIGNATURES (Hidden on Screen) -->
                    <div class="print-only d-none mt-5 pt-4">
                        <div class="row">
                            <div class="col-4 text-center">
                                <div class="border-top border-dark pt-2 mx-3">
                                    <p class="small fw-bold mb-0">PREPARED BY:</p>
                                </div>
                            </div>
                            <div class="col-4"></div>
                            <div class="col-4 text-center">
                                <div class="border-top border-dark pt-2 mx-3">
                                    <p class="small fw-bold mb-0">NOTED BY:</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-5">
                            <p class="small text-muted italic">*** This is a computer-generated report. ***</p>
                        </div>
                    </div>
                </div>
                <div class='modal-footer bg-light border-top p-3'>
                    <button type='button' class='btn btn-secondary fw-bold px-4' data-bs-dismiss='modal'>Close</button>
                    <div class="dropdown">
                        <button class="btn btn-success fw-bold px-4 dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item fw-bold" href="#" id="finalizeInventoryExportBtn"><i
                                        class="bi bi-file-earmark-excel me-2 text-success"></i>Export to CSV</a>
                            </li>
                            <li><a class="dropdown-item fw-bold" href="#" id="finalizeInventoryExportExcelBtn"><i
                                        class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Export to
                                    Excel (XLSX)</a></li>
                            <li><a class="dropdown-item fw-bold" href="#" id="finalizeInventoryExportPdfBtn"><i
                                        class="bi bi-file-earmark-pdf me-2 text-danger"></i>Export to PDF</a>
                            </li>
                        </ul>
                    </div>
                    <button type='button' class='btn btn-primary fw-bold px-4 text-white'
                        id="finalizeInventoryPrintBtn">
                        <i class="bi bi-printer me-2"></i>Direct Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="generateSalesReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                        Sales Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="sales_generateSalesReportForm">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Time
                                Period</label>
                            <select class="form-select border-0 bg-light fw-bold" id="sales_report_period" required>
                                <option value="daily">Daily Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                        </div>
                        <div class="mb-3" id="sales_report_date_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Date</label>
                            <input type="date" class="form-control border-0 bg-light fw-bold" id="sales_report_date"
                                required>
                        </div>
                        <div class="mb-3 d-none" id="sales_report_month_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Month/Year</label>
                            <input type="month" class="form-control border-0 bg-light fw-bold" id="sales_report_month">
                        </div>
                        <div class="mb-3 d-none" id="sales_report_year_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Year</label>
                            <input type="number" class="form-control border-0 bg-light fw-bold" id="sales_report_year"
                                min="2000" max="2099">
                        </div>
                        <div class="mb-3 d-none">
                            <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                            <select class="form-select border-0 bg-light fw-bold" id="sales_report_branch">
                                <option value="all">All Branches</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Transaction
                                Type</label>
                            <select class="form-select border-0 bg-light fw-bold" id="sales_report_type">
                                <option value="all">All Transactions</option>
                                <option value="cash">Cash Only</option>
                                <option value="charge">Charge</option>
                            </select>
                        </div>
                        <hr>
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-funnel me-1"></i>Filters (Optional)</h6>
                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase">Filter by Brand</label>
                            <input type="text" class="form-control border-0 bg-light fw-bold"
                                id="sales_report_filter_brand" placeholder="e.g., Honda, Yamaha...">
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary fw-bold px-4 text-white"
                            id="btnGenerateSalesReport">
                            <i class="bi bi-eye me-2"></i>Preview Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW PAYMENT REPORT MODAL -->
    <div class="modal fade" id="paymentReportsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                        Payments
                        Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="payment_generateReportForm">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Time Period</label>
                            <select class="form-select border-0 bg-light fw-bold" id="payment_report_period" required>
                                <option value="daily">Daily Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                        </div>
                        <div class="mb-3" id="payment_report_date_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Date</label>
                            <input type="date" class="form-control border-0 bg-light fw-bold" id="payment_report_date"
                                required>
                        </div>
                        <div class="mb-3 d-none" id="payment_report_month_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Month/Year</label>
                            <input type="month" class="form-control border-0 bg-light fw-bold"
                                id="payment_report_month">
                        </div>
                        <div class="mb-3 d-none" id="payment_report_year_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Year</label>
                            <input type="number" class="form-control border-0 bg-light fw-bold" id="payment_report_year"
                                min="2000" max="2099">
                        </div>
                        <div class="mb-3 d-none">
                            <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                            <select class="form-select border-0 bg-light fw-bold" id="payment_report_branch">
                                <option value="all">All Branches</option>
                            </select>
                        </div>
                        <hr>
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-funnel me-1"></i>Filters (Optional)</h6>
                        <div class="mb-3">
                            <label class="form-label small text-muted text-uppercase">Filter by Customer</label>
                            <input type="text" class="form-control border-0 bg-light fw-bold"
                                id="payment_report_filter_customer" placeholder="e.g., Juan Dela Cruz">
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary fw-bold px-4 text-white"
                            id="btnGeneratePaymentReport">
                            <i class="bi bi-eye me-2"></i>Preview Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RECEIPT MODAL -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="receiptTitle">Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="receiptBody">
                    <!-- Receipt Content Generated by JS -->
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success fw-bold px-4" id="downloadReceiptBtn">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                    </button>
                    <button type="button" class="btn btn-primary fw-bold px-4 text-white" id="printReceiptBtn">
                        <i class="bi bi-printer me-2"></i>Direct Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- PAYMENTS AGING MODAL -->
    <div class="modal fade" id="paymentsAgingModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Payments Aging View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row mb-3 align-items-end aging-branch-filter-container">
                        <div class="col-md-3 d-none">
                            <label class="form-label fw-bold small text-muted">Filter by Branch</label>
                            <select id="agingBranchFilter" class="form-select border-secondary">
                                <option value="All">All Branches</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted">Search Customer</label>
                            <input type='text' id='paymentsAgingSearch' class='form-control border-secondary'
                                placeholder='Search customer...'>
                        </div>
                        <div class="col text-end">
                            <button class="btn btn-outline-dark fw-bold" id="printAgingSummaryBtn">
                                <i class="bi bi-printer me-1"></i> Print Summary
                            </button>
                        </div>
                    </div>

                    <div class='table-responsive rounded shadow-sm bg-white'>
                        <table class='table table-hover align-middle mb-0' id='paymentsAgingTable'>
                            <thead class="bg-light border-bottom border-2">
                                <tr>
                                    <th style="width: 40px;"></th>

                                    <th>Customer</th>
                                    <th class="text-end">0-30 Days</th>
                                    <th class="text-end">31-60 Days</th>
                                    <th class="text-end">61-90 Days</th>
                                    <th class="text-end">90+ Days</th>
                                    <th class="text-end">Total Balance</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id='paymentsAgingTableBody'></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTOM CONFIRMATION MODAL -->
    <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 2100;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-question-circle text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted mb-0" id="confirmModalMessage">Do you really want to proceed with this action?
                    </p>
                </div>
                <div class="modal-footer border-0 p-3 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" id="confirmModalNext">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.isBranchPage = true;
        window.canDelete = <?php echo json_encode($canDelete); ?>;
        window.currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
    </script>
    <script src='https://code.jquery.com/jquery-3.7.1.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script src='../js/spareparts_stock_card.js?v=<?php echo time(); ?>'></script>
    <script src='../js/spareparts_inventory.js?v=<?php echo time(); ?>'></script>
</body>

</html>
