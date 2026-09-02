<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Spareparts-Warehouse', 'Spareparts-Sales', 'Spareparts-Owner', 'Spareparts-Admin', 'Owner', 'Admin'])) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$role = $_SESSION['position'] ?? '';
$isAdminOrSales = in_array(strtolower(trim($role)), ['spareparts-admin', 'spareparts-owner', 'admin', 'owner', 'itsuperadmin', 'admin spareparts', 'spareparts-sales']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing Management - Spareparts MS</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/spareparts_premium.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --smdi-green: #004d40;
            --smdi-green-dark: #00251a;
            --smdi-green-light: #26a69a;
            --smdi-green-bg: #e0f2f1;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            font-size: 0.9rem;
        }

        .navbar { 
            background-color: var(--smdi-green) !important;
            padding: 0.75rem 1rem;
        }
        
        .navbar-brand {
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }

        .back-btn {
            color: white !important;
            font-size: 1.25rem;
            margin-right: 1rem;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .back-btn:hover {
            opacity: 0.8;
        }

        .user-profile {
            display: flex;
            align-items: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .user-profile i {
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }
        
        .text-primary { color: var(--smdi-green) !important; }
        .bg-primary { background-color: var(--smdi-green) !important; }
        .btn-primary { background-color: var(--smdi-green) !important; border-color: var(--smdi-green) !important; }
        .btn-outline-primary { color: var(--smdi-green) !important; border-color: var(--smdi-green) !important; }
        .btn-outline-primary:hover { background-color: var(--smdi-green) !important; color: white !important; }

        .pricing-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            background: #fff;
        }

        .search-input {
            border-radius: 50px;
            padding: 10px 20px;
            border: 2px solid #eee;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--smdi-green-light);
            box-shadow: 0 0 10px rgba(38, 166, 154, 0.2);
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.03);
        }

        .table thead th {
            background-color: var(--smdi-green-bg);
            color: var(--smdi-green);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border: none;
            padding: 15px;
        }

        .table tbody td {
            padding: 14px 15px;
            border-bottom: 1px solid #f2f2f2;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 77, 64, 0.02);
        }

        .branch-badge {
            background-color: #e2e8f0;
            color: #475569;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
        }

        .pagination-container .page-link {
            color: var(--smdi-green);
            border: none;
            margin: 0 3px;
            border-radius: 6px;
            font-weight: 600;
        }

        .pagination-container .page-item.active .page-link {
            background-color: var(--smdi-green) !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <a href="<?php 
                    if ($role === 'Spareparts-Sales' || $role === 'Spareparts-Retail') {
                        echo 'sales_dashboard.php';
                    } elseif ($role === 'Spareparts-Owner') {
                        echo 'owner_dashboard.php';
                    } elseif ($role === 'Spareparts-Admin') {
                        echo 'admin_dashboard.php';
                    } else {
                        echo 'warehouse_dashboard.php';
                    }
                ?>" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand fw-bold mb-0 text-uppercase"><i class="bi bi-tags-fill me-2"></i>Pricing Management</span>
            </div>
            <div class="ms-auto d-flex align-items-center">
                <div class="user-profile me-3">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo htmlspecialchars($branch); ?></span>
                </div>
                <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="../api/logout.php" style="font-size: 0.75rem;">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; max-width: 1200px;">
        
        <?php if ($isAdminOrSales): ?>
        <!-- Bulk Pricing Update Card -->
        <div class="card border-0 shadow-sm mb-4 pricing-card" style="border-radius: 15px;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-success bg-opacity-10 rounded-4 me-3 text-success">
                        <i class="bi bi-file-earmark-excel fs-3"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Bulk Price Excel Update</h5>
                        <p class="text-muted small mb-0">Upload an Excel or CSV file to update multiple parts pricing at once.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="../api/spareparts_inventory.php?action=download_price_template" class="btn btn-outline-success rounded-pill fw-semibold px-3 py-2 d-flex align-items-center">
                        <i class="bi bi-download me-2"></i>Download Template
                    </a>
                    <button class="btn btn-success text-white rounded-pill fw-bold px-4 py-2 d-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#bulkPriceUploadModal">
                        <i class="bi bi-upload me-2"></i>Upload & Update
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pricing Master Table Card -->
        <div class="card border-0 shadow-sm pricing-card">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">Parts Price List</h4>
                        <p class="text-muted mb-0">Browse and manage buying cost and selling prices for all inventory parts.</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <select id="branchFilter" class="form-select border-2 rounded-pill px-3" style="width: auto; max-width: 200px;">
                            <option value="ALL">All Branches</option>
                        </select>
                        <input type="text" id="pricingSearchInput" class="form-control search-input" placeholder="Search Part No, Brand, Desc..." style="width: 280px;">
                    </div>
                </div>

                <div class="table-responsive table-container">
                    <table class="table table-hover mb-0" id="pricingTable">
                        <thead>
                            <tr>
                                <th>Part No</th>
                                <th>Brand</th>
                                <th>Description</th>
                                <th class="text-end">Buying Cost</th>
                                <th class="text-end">Selling Price</th>
                                <th class="text-center">Branch</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pricingTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="spinner-border text-success spinner-border-sm me-2" role="status"></div>
                                    Loading pricing database...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination controls -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="small text-muted" id="paginationStats">Showing 0 to 0 of 0 entries</div>
                    <nav aria-label="Pricing navigation" class="pagination-container">
                        <ul class="pagination pagination-sm mb-0" id="pricingPagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Price Upload Modal -->
    <div class="modal fade modal-fs" id="bulkPriceUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 py-3">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-file-earmark-excel me-2"></i>Bulk Price Excel Update</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="modal-fs-container">
                    <!-- Upload View -->
                    <div id="uploadView">
                        <div class="alert alert-success bg-success bg-opacity-10 border-0 p-3 mb-4" style="border-radius: 10px;">
                            <h6 class="fw-bold text-success mb-2 d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2"></i> Excel Format Instructions:
                            </h6>
                            <p class="mb-0 small text-dark">Your file must include a header row with these columns: <strong class="text-success">Part No</strong>, <strong class="text-success">Brand</strong>, <strong class="text-success">Description</strong>, <strong class="text-success">Buying Cost</strong>, <strong class="text-success">Selling Price</strong>, and <strong class="text-success">Branch</strong>.</p>
                        </div>
                        <form id="bulkPriceForm" enctype="multipart/form-data">
                            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                                <div class="card-body p-4">
                                    <label for="bulkExcelFile" class="form-label fw-bold text-secondary small text-uppercase mb-2">Select Excel / CSV File</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-file-earmark-spreadsheet text-success"></i></span>
                                        <input type="file" class="form-control border-start-0 ps-0" id="bulkExcelFile" name="excel_file" accept=".xlsx, .xls, .csv" required>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success px-4 fw-bold text-white shadow-sm" id="btnPreviewBulk">
                                    <i class="bi bi-eye me-1"></i>Preview Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Preview View (Hidden by default) -->
                    <div id="previewView" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-success d-flex align-items-center">
                                    <i class="bi bi-card-checklist me-2"></i>Updates Preview List
                                </h6>
                                <p class="small text-muted mb-0" id="previewSummaryText">Review the changes below before finalized updating.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-3 rounded-pill fw-semibold" id="btnBackToUpload">
                                <i class="bi bi-arrow-left me-1"></i>Back
                            </button>
                        </div>
                        <div class="table-responsive border-0 shadow-sm bg-white rounded-3 mb-4" style="max-height: 40vh; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="py-3 ps-3">Part No</th>
                                        <th class="py-3">Description/Branch</th>
                                        <th class="py-3 text-end">Current Cost</th>
                                        <th class="py-3 text-end text-primary">New Cost</th>
                                        <th class="py-3 text-end">Current Price</th>
                                        <th class="py-3 text-end text-success">New Price</th>
                                        <th class="py-3 text-center pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkPreviewTbody"></tbody>
                            </table>
                        </div>
                        
                        <!-- Reason for Change Dropdown & Changed By input -->
                        <div class="row mb-3 g-2 align-items-center bg-white p-3 rounded-3 border shadow-sm mx-1">
                            <div class="col-md-6">
                                <label for="bulkChangeReason" class="form-label fw-bold text-dark mb-1 small"><i class="bi bi-question-circle me-1 text-success"></i>Reason for Change <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm fw-bold border-success" id="bulkChangeReason" required>
                                    <option value="" disabled selected>-- Select Reason --</option>
                                    <option value="Price Increase">Price Increase</option>
                                    <option value="Price Decrease">Price Decrease</option>
                                    <option value="Wrong Input">Wrong Input</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="bulkChangedBy" class="form-label fw-bold text-dark mb-1 small"><i class="bi bi-person me-1 text-success"></i>Changed By</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-bold text-muted" id="bulkChangedBy" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success px-4 fw-bold text-white shadow-sm" id="confirmBulkBtn">
                                <i class="bi bi-check-circle me-1"></i>Apply Updates
                            </button>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Card Modal -->
    <div class='modal fade modal-fs' id='stockCardModal' tabindex='-1'>
        <div class='modal-dialog modal-dialog-centered modal-xl'>
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
                                            <div class="p-2 rounded-circle bg-success bg-opacity-10"><i class="bi bi-box-fill text-success fs-5"></i></div>
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
                                                        <tbody id="stockCardMovementBody" class="border-top-0"></tbody>
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
                                                                <th class="text-end text-muted fw-bold small text-uppercase border-0 py-3">Selling Price</th>
                                                                <th class="text-muted fw-bold small text-uppercase border-0 py-3">Reason</th>
                                                                <th class="text-muted fw-bold small text-uppercase border-0 py-3">Changed By</th>
                                                                <th class="pe-4 text-center text-muted fw-bold small text-uppercase border-0 py-3">Ref Invoice</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="stockCardHistoryBody" class="border-top-0"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 px-4 pb-4 pt-0">
                    <div class="modal-fs-container d-flex justify-content-end">
                        <button type="button" class="btn btn-dark fw-bold px-5 py-2 rounded-pill shadow-sm" data-bs-dismiss="modal">Close Card</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Price Modal -->
    <div class="modal fade" id="editPriceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                <form id="editPriceForm">
                    <input type="hidden" name="id" id="editPriceId">
                    <input type="hidden" name="part_no" id="editPricePartNoHidden">
                    <input type="hidden" name="branch" id="editPriceBranchHidden">
                    
                    <div class="modal-header bg-success text-white border-0 py-3">
                        <h5 class="modal-title text-white fw-bold"><i class="bi bi-pencil-square me-2"></i>Update Specific Part Price</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-0">Part Number</label>
                                        <div class="fw-bold text-dark" id="editPricePartNoText">-</div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-0">Branch</label>
                                        <div class="fw-bold text-muted" id="editPriceBranchText">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-cash-coin me-1 text-success"></i>Pricing Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="editPriceCost" class="form-label small fw-bold text-muted text-uppercase">Buying Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0">₱</span>
                                            <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold" name="cost" id="editPriceCost" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="editPriceSelling" class="form-label small fw-bold text-muted text-uppercase">Selling Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0 text-success fw-bold">₱</span>
                                            <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold text-success" name="price" id="editPriceSelling" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-shield-check me-1 text-success"></i>Audit & Log Details</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="editPriceReason" class="form-label small fw-bold text-muted text-uppercase">Reason for Change <span class="text-danger">*</span></label>
                                        <select class="form-select fw-bold" name="change_reason" id="editPriceReason" required>
                                            <option value="" disabled selected>-- Select Reason --</option>
                                            <option value="Price Increase">Price Increase</option>
                                            <option value="Price Decrease">Price Decrease</option>
                                            <option value="Wrong Input">Wrong Input</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="editPriceChangedBy" class="form-label small fw-bold text-muted text-uppercase">Changed By</label>
                                        <input type="text" class="form-control bg-light text-muted fw-bold" id="editPriceChangedBy" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-0 py-3 px-4">
                        <button type="button" class="btn btn-light px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-5 fw-bold text-white shadow-sm">
                            <i class="bi bi-check-circle me-1"></i>Save Price
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/spareparts_stock_card.js"></script>
    <script>
        window.canDelete = false;
        window.isAdminOrSales = <?php echo $isAdminOrSales ? 'true' : 'false'; ?>;

        let inventoryData = [];
        let filteredData = [];
        let currentPage = 1;
        const itemsPerPage = 15;

        function formatCurrency(val) {
            let num = parseFloat(val);
            return isNaN(num) ? '0.00' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function loadBranches() {
            $.get('../api/spareparts_inventory.php?action=get_branches', function(response) {
                if (response.success && Array.isArray(response.data)) {
                    let branchFilter = $('#branchFilter');
                    branchFilter.html('<option value="ALL">All Branches</option>');
                    response.data.forEach(b => {
                        branchFilter.append(`<option value="${b}">${b}</option>`);
                    });
                }
            }, 'json');
        }

        let totalPricingItems = 0;
        function loadPricingData(page = 1) {
            currentPage = page;
            let searchTerm = ($('#pricingSearchInput').val() || '').trim();
            let branchVal = $('#branchFilter').val() || 'ALL';

            $('#pricingTableBody').html('<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading pricing data...</td></tr>');

            $.get('../api/spareparts_inventory.php?action=get_inventory_list', {
                page: currentPage,
                limit: itemsPerPage,
                search: searchTerm,
                branch: branchVal
            }, function(response) {
                if (response.success) {
                    inventoryData = response.data || [];
                    totalPricingItems = response.total !== undefined ? response.total : inventoryData.length;
                    renderPricingTable();
                } else {
                    Swal.fire('Error', response.message || 'Failed to load pricing data.', 'error');
                }
            }, 'json');
        }

        function renderPricingTable() {
            let pageItems = inventoryData;
            let tbody = $('#pricingTableBody');
            tbody.empty();

            if (pageItems.length === 0) {
                tbody.html('<tr><td colspan="7" class="text-center py-5 text-muted">No matching parts found.</td></tr>');
                $('#paginationStats').text('Showing 0 to 0 of 0 entries');
                $('#pricingPagination').empty();
                return;
            }

            pageItems.forEach(item => {
                let escapedPartNo = $('<div>').text(item.part_no).html();
                let escapedBranch = $('<div>').text(item.current_branch).html();
                tbody.append(`
                    <tr>
                        <td class="fw-bold">${escapedPartNo}</td>
                        <td>${$('<div>').text(item.brand || '').html()}</td>
                        <td>${$('<div>').text(item.description || '').html()}</td>
                        <td class="text-end fw-semibold text-secondary">₱${formatCurrency(item.cost)}</td>
                        <td class="text-end fw-bold text-success">₱${formatCurrency(item.price)}</td>
                        <td class="text-center">
                            <span class="branch-badge">${escapedBranch}</span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" onclick="showStockCard('${escapedPartNo}', '${escapedBranch}')">
                                    <i class="bi bi-clock-history me-1"></i>Price History
                                </button>
                                ${window.isAdminOrSales ? `
                                <button class="btn btn-sm btn-success text-white rounded-pill px-3 fw-bold" onclick="openEditPriceModal(${item.id}, '${escapedPartNo}', '${escapedBranch}', ${item.cost}, ${item.price})">
                                    <i class="bi bi-pencil-square me-1"></i>Edit Price
                                </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `);
            });

            // Update stats
            let start = totalPricingItems === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
            let displayEnd = Math.min((currentPage - 1) * itemsPerPage + pageItems.length, totalPricingItems);
            $('#paginationStats').text(`Showing ${start} to ${displayEnd} of ${totalPricingItems} entries`);

            renderPagination();
        }

        function renderPagination() {
            let totalPages = Math.ceil(totalPricingItems / itemsPerPage);
            let pagination = $('#pricingPagination');
            pagination.empty();

            if (totalPages <= 1) return;

            // Previous button
            pagination.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;"><i class="bi bi-chevron-left"></i></a>
                </li>
            `);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    pagination.append(`
                        <li class="page-item ${currentPage === i ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                        </li>
                    `);
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
                }
            }

            // Next button
            pagination.append(`
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;"><i class="bi bi-chevron-right"></i></a>
                </li>
            `);
        }

        function changePage(page) {
            loadPricingData(page);
        }

        function openEditPriceModal(id, partNo, branch, cost, price) {
            $('#editPriceId').val(id);
            $('#editPricePartNoHidden').val(partNo);
            $('#editPriceBranchHidden').val(branch);
            $('#editPricePartNoText').text(partNo);
            $('#editPriceBranchText').text(branch);
            $('#editPriceCost').val(cost);
            $('#editPriceSelling').val(price);
            $('#editPriceReason').val('');
            
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editPriceModal')).show();
        }

        let pricingSearchTimer;
        $(document).ready(function() {
            loadBranches();
            loadPricingData();

            $('#pricingSearchInput').on('input', function() {
                clearTimeout(pricingSearchTimer);
                pricingSearchTimer = setTimeout(() => {
                    loadPricingData(1);
                }, 300);
            });
            $('#branchFilter').on('change', function() {
                loadPricingData(1);
            });

            // Form submit for specific part price edit
            $('#editPriceForm').on('submit', function(e) {
                e.preventDefault();
                
                const btn = $(this).find('button[type="submit"]');
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
                
                const id = $('#editPriceId').val();
                const cost = $('#editPriceCost').val();
                const price = $('#editPriceSelling').val();
                const reason = $('#editPriceReason').val();
                
                $.ajax({
                    url: '../api/spareparts_inventory.php?action=edit_parts',
                    method: 'POST',
                    data: {
                        id: id,
                        cost: cost,
                        price: price,
                        change_reason: reason,
                        part_no: $('#editPricePartNoHidden').val(),
                        branch: $('#editPriceBranchHidden').val(),
                        description: inventoryData.find(item => item.id == id)?.description || '',
                        brand: inventoryData.find(item => item.id == id)?.brand || '',
                        min_stock: inventoryData.find(item => item.id == id)?.min_stock || 5,
                        bin_location: inventoryData.find(item => item.id == id)?.bin_location || '',
                        invoice_no: 'PRICE UPDATE'
                    },
                    dataType: 'json',
                    success: response => {
                        btn.prop('disabled', false).html(originalHtml);
                        if (response.success) {
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('editPriceModal')).hide();
                            Swal.fire({
                                title: 'Success!',
                                text: 'Pricing updated successfully.',
                                icon: 'success',
                                confirmButtonColor: '#004d40'
                            }).then(() => {
                                loadPricingData();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'Failed to update pricing.',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    },
                    error: () => {
                        btn.prop('disabled', false).html(originalHtml);
                        Swal.fire({
                            title: 'Error',
                            text: 'Connection error. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
