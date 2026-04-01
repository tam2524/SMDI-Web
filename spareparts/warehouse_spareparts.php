<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.html');
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
    <title>RCSM - WAREHOUSE SPARE PARTS INVENTORY</title>
    <meta content='width=device-width, initial-scale=1.0' name='viewport'>
    <link rel='icon' href='../assets/img/smdi_logosmall.png' type='image/png'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css' />
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css' rel='stylesheet'>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="../css/spareparts_inventory_style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="../css/spareparts_premium.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        :root {
            --smdi-green: #004d40;
            --smdi-green-light: #00695c;
            --smdi-green-bg: #e0f2f1;
            --smdi-green-dark: #00251a;
            --smdi-green-hover: #00332c;
        }

        body {
            background-color: #f4f7f6;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 0.9rem;
            color: #2c3e50;
        }

        .bg-primary { background-color: var(--smdi-green) !important; }
        .text-primary { color: var(--smdi-green) !important; }
        
        .bg-dark-green { background-color: var(--smdi-green-dark) !important; color: #fff !important; }
        .text-dark-green { color: var(--smdi-green-dark) !important; }
        .bg-dark-green-light { background-color: var(--smdi-green-bg) !important; color: var(--smdi-green-dark) !important; }
        
        .btn-primary { 
            background-color: var(--smdi-green) !important; 
            border-color: var(--smdi-green) !important;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: var(--smdi-green-hover) !important;
            border-color: var(--smdi-green-hover) !important;
        }
        
        .btn-outline-primary {
            color: var(--smdi-green) !important;
            border-color: var(--smdi-green) !important;
            font-weight: 600;
        }
        .btn-outline-primary:hover {
            background-color: var(--smdi-green) !important;
            color: #fff !important;
        }

        /* Enforce green for info/warning elements */
        .badge.bg-info, .badge.bg-warning {
            background-color: var(--smdi-green-light) !important;
            color: #fff !important;
        }
        
        .text-info, .text-warning { color: var(--smdi-green-light) !important; }
        .btn-info, .btn-warning {
            background-color: var(--smdi-green-light) !important;
            border-color: var(--smdi-green-light) !important;
            color: #fff !important;
        }

        /* NEW HEADER STYLES mimicking inventory_in.php */
        .bg-navy {
            background-color: var(--smdi-green) !important;
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
            color: var(--smdi-green);
            background-color: #e8f5e9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-bottom: 2px solid var(--smdi-green);
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


        /* Inventory Table Polish */
        #inventoryTable thead tr th {
            background-color: var(--smdi-green) !important;
            color: #fff !important;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            border: none;
        }

        #inventoryTable tbody tr {
            transition: background 0.15s;
        }

        #inventoryTable tbody tr:hover {
            background-color: var(--smdi-green-bg) !important;
        }

        #inventoryTable tbody td {
            font-size: 0.85rem;
            border-color: #f2f2f2;
        }

    </style>
    <script>
        window.canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
        window.isBranchPage = true;
        window.currentBranch = "<?php echo $currentBranch; ?>";
    </script>
</head>

<body>
    <!-- INCOMING TRANSFER ALERT MODAL -->
    <div class="modal fade" id="incomingTransferAlertModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-exclamation-triangle-fill me-2"></i>PENDING INCOMING TRANSFERS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="incoming-alert-modal-body" style="background: #f8f9fa; max-height: 80vh; overflow-y: auto;">
                    <div class="p-5 text-center text-muted">
                        <div class="spinner-border text-danger mb-3" role="status"></div>
                        <p class="mb-0 fw-bold ls-1">Checking for new transfers...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- VIEW INCOMING TRANSFER MODAL (XL) -->
    <div class='modal fade modal-fs' id='viewIncomingTransferModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-primary text-white py-3'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-box-arrow-in-down me-2"></i>Incoming
                        Spare Parts
                        Transferred to Warehouse</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-0'>
                    <div class="modal-fs-container py-4">
                        <div class="px-4 py-2 bg-white border-bottom text-muted small fw-bold mb-3 rounded shadow-sm">
                            <i class="bi bi-info-circle me-1"></i> Review incoming spare parts transferred to your warehouse.
                        </div>

                        <div class='table-responsive card border-0 shadow-sm rounded-3 overflow-hidden'>
                        <table class='table table-hover align-middle mb-0' id="incomingTransferTable">
                            <thead class="bg-dark-grey text-white">
                                <tr>
                                    <th>Part No</th>
                                    <th>Part Name</th>
                                    <th>Brand</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Transfer Date</th>
                                    <th class="text-center">From</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id='incomingTransferDetailsBody'>
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4'>
                    <button type='button' class='btn btn-warning fw-bold px-4 rounded-pill' data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW HEADER mimicking inventory_in.php -->
    <nav class="navbar navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <a href="<?php
if ($userRole === 'Spareparts-Sales') {
    echo 'sales_dashboard.php';
}
elseif ($userRole === 'f' || $userRole === 'Spareparts-Retail') {
    echo 'sales_dashboard.php';
}
else {
    echo 'warehouse_dashboard.php';
}
?>" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand">
                    STOCK CARD – MASTER INVENTORY
                </span>
            </div>
            <div class="user-info">
                <?php if (isset($_SESSION['username'])): ?>
                    <!-- Notification Bell -->
                    <a href="javascript:void(0)" onclick="if(document.getElementById('incomingTransferAlertModal')) bootstrap.Modal.getOrCreateInstance(document.getElementById('incomingTransferAlertModal')).show()" class="text-white text-decoration-none me-4 position-relative" title="Incoming Transfers">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <span id="incoming-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.55rem; padding: 0.25em 0.5em;">0</span>
                    </a>
                    <span><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($currentBranch); ?></span>
                <?php
endif; ?>
                <a href="../api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <main class='container-fluid px-4 py-4'>
        <div class='card border-0 mb-4 bg-transparent shadow-none'>
            
            <!-- Custom Flat Navigation -->
            <ul class='nav custom-tabs' id='mainTabs' role='tablist'>
                <!-- Transfers and other modules removed to dedicated files -->
                <li class='nav-item'>
                    <button class='nav-link active d-flex align-items-center' id='inventory-tab' data-bs-toggle='tab' data-bs-target='#sub-stock' type='button'>
                        <i class="bi bi-card-list me-2"></i>Stock Card
                    </button>
                </li>

            </ul>

            <div class='card-body p-0 bg-transparent'>
                <div class='tab-content' id='mainTabContent'>
                    <!-- CONSOLIDATED STOCK CARD & INVENTORY -->
                    <div class="tab-pane fade show active" id="sub-stock" role="tabpanel">
                        <div class="container-fluid py-4">
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                <h4 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-card-list me-2 text-primary"></i>Warehouse Inventory
                                </h4>
                                <div class="d-flex gap-2 align-items-center">
                                    <div id="inventoryStats" class="small text-muted fw-bold border-end pe-3"></div>
                                    <button class='btn btn-sm btn-primary text-white fw-bold px-3'
                                        data-bs-toggle='modal' data-bs-target='#addPartModal'>
                                        <i class='bi bi-plus-lg me-1'></i>Add New Part
                                    </button>
                                    <input type='text' id='inventorySearch' class='form-control form-control-sm rounded-pill'
                                        style="width: 240px;" placeholder=' Search part...'>
                                </div>
                            </div>


                            <div class='table-responsive'>
                                <table class='table table-hover align-middle' id='inventoryTable'>
                                    <thead>
                                        <tr class="bg-primary text-white">
                                            <th class="ps-4 py-3" style="border-radius: 0;">Part No</th>
                                            <th class="py-3">Part Name</th>
                                            <th class="text-center py-3">Bin Location</th>
                                            <th class="text-center py-3">In Stock</th>
                                            <th class="text-end py-3">Cost</th>
                                            <th class="text-end py-3">Price</th>
                                            <th class="text-center py-3">Status</th>
                                            <th class="text-center pe-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id='inventoryTableBody'>
                                        <!-- Populated via JS -->
                                    </tbody>
                                 </table>
                             </div>
                             <!-- Pagination bar -->
                             <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-white">
                                 <div class="text-muted small fw-semibold" id="inventoryPageInfo"></div>
                                 <nav>
                                     <ul class="pagination pagination-sm mb-0 gap-1" id="inventoryPagination"></ul>
                                 </nav>
                             </div>
                         </div>
                     </div>


                    <!-- Tab content for Sales and Payments removed -->
                </div> <!-- End mainTabContent -->
            </div> <!-- End card-body -->
        </div> <!-- End card shadow-sm -->
    </main>

    <!-- ADD PART MODAL -->
    <div class='modal fade' id='addPartModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg' style="border-radius: 15px; overflow: hidden;">
                <form id='addPartForm' enctype="multipart/form-data">
                    <div class='modal-header bg-success text-white border-0 py-3'>
                        <h5 class='modal-title text-white fw-bold'><i class="bi bi-plus-square me-2"></i>Register New Inventory Part</h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4 bg-light'>
                        <!-- SECTION: PART INFORMATION -->
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center">
                                    <i class="bi bi-tag-fill me-2 text-success"></i> Basic Part Information
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Part Number</label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 fw-bold" name="part_no" placeholder="e.g. 123456-ABC" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Brand Name</label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-bookmark-star"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 fw-bold" name="brand" placeholder="e.g. Honda, Yamaha">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Part Name / Description</label>
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-card-text"></i></span>
                                            <textarea class="form-control border-start-0 ps-0" name="description" rows="2" placeholder="Enter full part description..." required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: STOCK & PRICING -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold mb-3 text-dark">
                                            <i class="bi bi-box-seam me-2 text-primary"></i> Initial Stock & Limits
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Initial Stock</label>
                                                <input type="number" class="form-control fw-bold" name="stock" value="0" required min="0">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Min. Stock Alert</label>
                                                <input type="number" class="form-control text-danger fw-bold" name="min_stock" value="5" required min="0">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold text-muted text-uppercase">Bin Location</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0" name="bin_location" placeholder="e.g. Row 1, Bin B">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold mb-3 text-dark">
                                            <i class="bi bi-cash-coin me-2 text-warning"></i> Master Pricing
                                        </h6>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Dealer Unit Cost</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted">₱</span>
                                                <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold" name="cost" value="0.00" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase">Standard Selling Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-success">₱</span>
                                                <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold text-success" name="price" value="0.00" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer border-0 bg-white py-3 px-4'>
                        <button type='button' class='btn btn-light px-4 fw-bold' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-success px-5 fw-bold shadow-sm'>
                            <i class="bi bi-check-circle me-1"></i>Save Part
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='editPartModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg' style="border-radius: 15px; overflow: hidden;">
                <form id='editPartForm' enctype="multipart/form-data">
                    <div class='modal-header bg-primary text-white border-0 py-3'>
                        <h5 class='modal-title text-white fw-bold'><i class="bi bi-pencil-square me-2"></i>Update Part Details</h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4 bg-light'>
                        <input type="hidden" name="id" id="edit_part_id">
                        
                        <!-- SECTION: PART INFORMATION -->
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3 text-dark d-flex align-items-center">
                                    <i class="bi bi-tag-fill me-2 text-primary"></i> Part Information
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Part Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 fw-bold" name="part_no" id="edit_part_no" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Brand Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-bookmark-star"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0 fw-bold" name="brand" id="edit_brand">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted text-uppercase">Part Name / Description</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-card-text"></i></span>
                                            <textarea class="form-control border-start-0 ps-0" name="description" id="edit_description" rows="2" required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: STOCK, PRICING & LOCATION -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center" style="font-size: 0.9rem;">
                                            <i class="bi bi-box-seam-fill me-2 text-success"></i> Stock Levels
                                        </h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">In Stock Now</label>
                                                <input type="number" class="form-control fw-bold form-control-sm" name="stock" id="edit_stock" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Alert if below</label>
                                                <input type="number" class="form-control text-danger fw-bold form-control-sm" name="min_stock" id="edit_min_stock" required>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Reference Invoice #</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-file-earmark-text"></i></span>
                                                    <input type="text" class="form-control border-start-0 ps-0" name="invoice_no" id="edit_invoice_no" placeholder="Optional">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center" style="font-size: 0.9rem;">
                                            <i class="bi bi-cash-coin me-2 text-warning"></i> Pricing
                                        </h6>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Cost (Master Inventory)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted">₱</span>
                                                <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold" name="cost" id="edit_cost" required>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Store Selling Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 text-success fw-bold">₱</span>
                                                <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold text-success" name="price" id="edit_price" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center" style="font-size: 0.9rem;">
                                            <i class="bi bi-geo-alt-fill me-2 text-primary"></i> Location
                                        </h6>
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Storage (Bin/Row)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-pin-map"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0" name="bin_location" id="edit_bin_location" placeholder="e.g. Row 2, Bin C">
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Current Branch</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-building"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0" name="branch" id="edit_branch" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- IMAGE & REASON SECTION -->
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Change Image</label>
                                <input type="file" class="form-control form-control-sm" name="part_image" id="edit_part_image" accept="image/*">
                            </div>
                            <div class="col-md-8">
                                <div id="edit_reason_container" style="display: none;">
                                    <div class="alert bg-primary bg-opacity-10 border-0 shadow-sm d-flex align-items-start p-2 mb-0" style="border-radius: 12px; border-left: 4px solid var(--smdi-green) !important;">
                                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-primary"></i>
                                        <div class="w-100">
                                            <label class="form-label fw-bold mb-0 small">Why are you changing this?</label>
                                            <textarea class="form-control bg-white border-0 py-1" name="change_reason" id="edit_change_reason" rows="1" placeholder="Required if updating stock quantity..." style="font-size: 0.85rem;"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer bg-white border-0 p-4 pt-1'>
                        <button type='button' class='btn btn-light fw-bold px-4 rounded-pill' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-dark fw-bold px-5 rounded-pill shadow-sm text-white'>Save Changes</button>
                    </div>
                </form>
            </div>
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
                        <div class="alert bg-primary bg-opacity-10 border-0 shadow-sm small py-2 mb-4">
                            <i class="bi bi-info-circle-fill me-2 text-primary"></i>Adjust transaction metadata below. Financial
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

    <div class='modal fade modal-fs' id='viewHistoryModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-dialog-centered'>
            <div class='modal-content border-0'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title text-white fw-bold'>Spare Parts Movement History</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4 bg-light'>
                    <div class="modal-fs-container">
                        <div class="mb-3">
                            <h5 class="fw-bold mb-1 text-primary" id="historyPartDescription"></h5>
                            <div class="text-muted small">
                                Brand: <span id="historyPartBrand"></span> | Part #: <span id="historyPartNumber"></span>
                            </div>
                        </div>
                        <div class="table-responsive card border-0 shadow-sm rounded-3 overflow-hidden">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="py-2 border-0">Date</th>
                                        <th class="py-2 border-0">Event</th>
                                        <th class="py-2 text-center border-0">Qty</th>
                                        <th class="py-2 border-0">From</th>
                                        <th class="py-2 border-0">To</th>
                                        <th class="py-2 border-0">Status</th>
                                        <th class="py-2 border-0">Invoice #</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class='modal-footer border-0 p-4 pt-0'>
                    <button type='button' class='btn btn-secondary fw-bold px-4'
                        data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='receiptModal' tabindex='-1'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
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
    <div class='modal fade modal-fs' id='stockCardModal' tabindex='-1'>
        <div class='modal-dialog modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg' style="border-radius: 16px; background: #f8fafc;">
                <div class='modal-header bg-white border-0 px-4 pt-4 pb-0 align-items-start'>
                    <div class="d-flex align-items-center">
                        <div class="p-3 rounded-4 bg-primary bg-opacity-10 me-3">
                            <i class="bi bi-upc-scan fs-3 text-primary"></i>
                        </div>
                        <div>
                            <h4 class='modal-title fw-bold mb-0 text-primary'>Detailed Stock Card</h4>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span id="stockCardPartNo" class="badge bg-dark px-3 py-2" style="font-size: 0.85rem; letter-spacing: 0.5px;">PART-NO-PLACEHOLDER</span>
                                <span id="stockCardBrand" class="text-muted small fw-bold text-uppercase border-start ps-2">BRAND NAME</span>
                            </div>
                        </div>
                    </div>
                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                </div>

                <div class='modal-body px-0 py-4'>
                    <div class="modal-fs-container px-4">
                    <div class="row g-4">
                        <!-- Left Panel: Profile & Details -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm mb-3" style="border-radius: 16px;">
                                <div class="card-body p-3 text-center">
                                    <div id="stockCardImage" class="rounded-4 border bg-white mx-auto d-flex align-items-center justify-content-center overflow-hidden shadow-sm mb-3" style="width: 150px; height: 150px; background: #f1f5f9;">
                                        <i class="bi bi-image text-muted opacity-25" style="font-size: 3rem;"></i>
                                    </div>
                                    <h5 id="stockCardDescription" class="fw-bold text-dark mb-1">PART NAME / DESCRIPTION</h5>
                                    <div class="badge bg-light text-muted border px-3 py-2 mt-2" style="font-size: 0.8rem;">
                                        <i class="bi bi-geo-alt me-1"></i> STORAGE: <span id="stockCardBin">N/A</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="p-3 bg-white border-0 shadow-sm rounded-4 d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <div class="small text-muted fw-bold text-uppercase mb-0" style="font-size: 0.65rem;">Currently In Stock</div>
                                            <div class="fw-bold fs-4 text-dark"><span id="stockCardQty">0</span> <span class="fs-6 fw-normal text-muted">pcs</span></div>
                                        </div>
                                        <div class="p-2 rounded-circle bg-success bg-opacity-10">
                                            <i class="bi bi-box-fill text-success fs-5"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-white border-0 shadow-sm rounded-4">
                                        <div class="small text-muted fw-bold text-uppercase mb-0" style="font-size: 0.65rem;">Cost</div>
                                        <div id="stockCardCost" class="fw-bold text-dark mb-0" style="font-size: 0.9rem;">₱0.00</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-white border-0 shadow-sm rounded-4">
                                        <div class="small text-muted fw-bold text-uppercase mb-0" style="font-size: 0.65rem;">Selling</div>
                                        <div id="stockCardPrice" class="fw-bold text-success mb-0" style="font-size: 0.9rem;">₱0.00</div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <!-- Right Panel: History Tabs -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm flex-grow-1" style="border-radius: 16px;">
                                <div class="card-header bg-white border-0 pt-4 px-4">
                                    <nav>
                                        <div class="nav nav-pills gap-2" id="nav-tab" role="tablist">
                                            <button class="nav-link active fw-bold px-4 rounded-pill d-flex align-items-center" id="nav-move-tab" data-bs-toggle="tab" data-bs-target="#sc-movement" type="button" role="tab">
                                                <i class="bi bi-arrow-left-right me-2"></i> Activity Log (Ins & Outs)
                                            </button>
                                            <button class="nav-link fw-bold px-4 rounded-pill d-flex align-items-center" id="nav-cost-tab" data-bs-toggle="tab" data-bs-target="#sc-history" type="button" role="tab">
                                                <i class="bi bi-clock-history me-2"></i> Cost History
                                            </button>
                                        </div>
                                    </nav>
                                </div>
                                <div class="card-body p-0 mt-2">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="sc-movement" role="tabpanel">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="bg-light sticky-top">
                                                        <tr>
                                                            <th class="ps-4 text-muted fw-bold small text-uppercase border-0 py-3">Date</th>
                                                            <th class="text-muted fw-bold small text-uppercase border-0 py-3">Type of Activity</th>
                                                            <th class="text-center text-muted fw-bold small text-uppercase border-0 py-3">Qty</th>
                                                            <th class="text-muted fw-bold small text-uppercase border-0 py-3">To/From</th>
                                                            <th class="pe-4 text-center text-muted fw-bold small text-uppercase border-0 py-3">Reference #</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="stockCardMovementBody" class="border-top-0">
                                                        <!-- Dynamic -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="sc-history" role="tabpanel">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0">
                                                    <thead class="bg-light sticky-top">
                                                        <tr>
                                                            <th class="ps-4 text-muted fw-bold small text-uppercase border-0 py-3">Receive Date</th>
                                                            <th class="text-muted fw-bold small text-uppercase border-0 py-3">Supplier Source</th>
                                                            <th class="text-end text-muted fw-bold small text-uppercase border-0 py-3">Cost</th>
                                                            <th class="pe-4 text-center text-muted fw-bold small text-uppercase border-0 py-3">Ref Invoice</th>
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
                    </div>
                </div> <!-- End modal-body -->
                <div class="modal-footer bg-white border-0 px-4 pb-4 pt-0">
                    <div class="modal-fs-container d-flex justify-content-end">
                        <button type="button" class="btn btn-dark fw-bold px-5 py-2 rounded-pill shadow-sm" data-bs-dismiss="modal">Close Card</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class='modal fade' id='successModal' tabindex='-1' aria-hidden="true" style="z-index: 2200;">
        <div class='modal-dialog modal-dialog-centered modal-sm'>
            <div class='modal-content border-0 shadow-lg' style="border-radius: 20px;">
                <div class='modal-body p-5 text-center'>
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h4 class='fw-bold text-dark mb-2'>Success!</h4>
                    <p class='text-muted mb-4' id='successMessage'></p>
                    <button type='button' class='btn btn-success fw-bold w-100 rounded-pill py-2' data-bs-dismiss='modal'>Excellent</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class='modal fade' id='errorModal' tabindex='-1' aria-hidden="true" style="z-index: 2200;">
        <div class='modal-dialog modal-dialog-centered modal-sm'>
            <div class='modal-content border-0 shadow-lg' style="border-radius: 20px;">
                <div class='modal-body p-5 text-center'>
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h4 class='fw-bold text-dark mb-2'>Oops!</h4>
                    <p class='text-muted mb-4' id='errorMessage'></p>
                    <button type='button' class='btn btn-danger fw-bold w-100 rounded-pill py-2' data-bs-dismiss='modal'>I'll check</button>
                </div>
            </div>
        </div>
    </div>









    <!-- REPORT MODALS -->
    <div class='modal fade' id='inventoryReportsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
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
                                <option value="delivered_stocks">Summary of stocks received from supplier</option>
                                <option value="inventory_summary">Inventory Summary (Tally Board)</option>
                                <option value="transferred_stocks">Summary of Transferred Stocks</option>
                                <option value="received_stocks">Summary of Received Stocks (from Branch)</option>
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



    <div class='modal fade modal-fs' id='inventoryPreviewModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i> Report Preview
                    </h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="modal-fs-container">
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








    <!-- CUSTOM CONFIRMATION MODAL -->
    <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 2200;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body p-5 text-center">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-question-circle-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Are you sure?</h4>
                    <p class="text-muted mb-4" id="confirmModalMessage">Do you really want to proceed with this action?</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light fw-bold flex-grow-1 rounded-pill py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary fw-bold flex-grow-1 rounded-pill py-2" id="confirmModalNext">Confirm</button>
                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script src='../js/spareparts_stock_card.js?v=<?php echo time(); ?>'></script>
    <script src="../js/spareparts_dashboard.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof updatePendingTransfers === 'function') {
                updatePendingTransfers();
                setInterval(updatePendingTransfers, 30000);
            }
        });
    </script>
    <script src='../js/spareparts_inventory.js?v=<?php echo time(); ?>'></script>
</body>

</html>
