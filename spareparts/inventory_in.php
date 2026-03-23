<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}
$currentBranch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Receipts - Spareparts Warehouse</title>
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="../css/spareparts_premium.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #004d40;
            --accent-green: #26a69a;
            --hike-color: #ff5252;
            --bg-light: #f4f7f6;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .scanning-zone {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
            border-top: 5px solid var(--primary-dark);
        }

        /* Part Search Wrapper */
        .part-search-wrapper {
            position: relative;
        }
        .part-search-wrapper i.search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-dark);
            font-size: 1.4rem;
            pointer-events: none;
            z-index: 5;
        }
        #partSearchInput {
            padding-left: 50px;
            height: 58px;
            font-size: 1.3rem;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        #partSearchInput:focus {
            border-color: var(--accent-green);
            box-shadow: 0 0 10px rgba(38, 166, 154, 0.2);
            outline: none;
        }

        /* Autocomplete Dropdown */
        #searchDropdown {
            position: absolute;
            top: 100%;
            left: 0; right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .search-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s;
        }
        .search-item:hover, .search-item.active {
            background: #e8f5e9;
        }
        .search-item .part-no-badge {
            font-size: 0.75rem;
            background: var(--primary-dark);
            color: #fff;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 6px;
        }

        /* Part Details Card */
        .part-details-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid var(--accent-green);
            display: none;
            animation: slideIn 0.35s ease-out;
        }
        .part-details-card.new-part-mode {
            border-left-color: #ff9800;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .compatibility-badge {
            background: #e0f2f1;
            color: #00796b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 4px;
            margin-bottom: 4px;
            display: inline-block;
        }

        .hike-alert {
            background: #ffebee;
            color: #c62828;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #ffcdd2;
        }

        /* Cost comparison highlight */
        .cost-hike-input {
            border-color: #f44336 !important;
            color: #f44336 !important;
            font-weight: 700;
        }
        .cost-lower-input {
            border-color: #43a047 !important;
            color: #43a047 !important;
        }

        #inCartTable thead th {
            background: var(--primary-dark);
            color: white;
            padding: 13px 15px;
        }

        .btn-premium {
            background: var(--primary-dark);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-premium:hover {
            background: #00251a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .cost-history-link {
            font-size: 0.75rem;
            cursor: pointer;
            color: var(--accent-green);
            text-decoration: underline dotted;
        }
        .badge-new-part {
            background: #ff9800;
            color: #fff;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 20px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <a href="warehouse_dashboard.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <span class="navbar-brand">
                    RCSM Warehouse - Supplier Receipt (IN)
                </span>
            </div>
            <div class="user-info">
                <span><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row">
            <!-- LEFT COL -->
            <div class="col-lg-8">

                <!-- Step 1: Receipt Details -->
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top: 5px solid var(--accent-green) !important;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-2"></i>Step 1: Receipt Details</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Invoice / DR # *</label>
                                <input type="text" id="invoiceNo" class="form-control fw-bold border-primary" placeholder="Required" required autofocus>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Supplier Name</label>
                                <input type="text" id="supplier" class="form-control" placeholder="e.g. ABC Trading">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Date Received</label>
                                <input type="date" id="dateReceived" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Part Search & Entry -->
                <div class="scanning-zone">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-upc-scan me-2"></i>Step 2: Scan / Search Part Number</h5>
                        <small class="text-muted">Type to search or scan barcode</small>
                    </div>

                    <!-- Search Input -->
                    <div class="part-search-wrapper mb-3">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" id="partSearchInput" class="form-control"
                            placeholder="Type part number, name, or scan barcode..."
                            autocomplete="off">
                        <div id="searchDropdown"></div>
                    </div>

                    <!-- Existing Part Details Panel -->
                    <div id="partDisplay" class="part-details-card mb-3 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 id="partTitle" class="fw-bold mb-1">PART NAME</h5>
                                <p id="partNoDisplay" class="text-muted small mb-1">PART-NO</p>
                                <div id="compatibilityList"></div>
                            </div>
                            <span id="newPartBadge" class="badge-new-part d-none">NEW PART</span>
                        </div>

                        <!-- NEW PART: extra fields -->
                        <div id="newPartFields" class="d-none mb-3">
                            <div class="alert alert-warning p-2 small mb-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                This part number is <strong>not yet in the system</strong>. Please fill in the details below.
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted">Brand</label>
                                    <input type="text" id="newPartBrand" class="form-control form-control-sm" placeholder="e.g. Honda">
                                </div>
                                <div class="col-md-8">
                                    <label class="small fw-bold text-muted">Description / Part Name *</label>
                                    <input type="text" id="newPartDesc" class="form-control form-control-sm" placeholder="Full description" required>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-end g-3">
                            <div class="col-md-3">
                                <label class="small text-muted fw-bold d-block">
                                    Primary Cost
                                    <span id="costHistoryLink" class="cost-history-link ms-1 d-none" title="View cost history">(history)</span>
                                </label>
                                <span id="prevCostDisplay" class="fw-bold text-primary fs-5">₱0.00</span>
                                <div id="costChangeIndicator" class="small mt-1"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted fw-bold">Qty Received *</label>
                                <input type="number" id="scannedQty" class="form-control fw-bold border-primary" value="1" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted fw-bold">
                                    New Cost
                                    <span id="costUpdateHint" class="text-danger small d-none fw-normal"> (will update master)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" id="scannedCost" step="0.01" class="form-control fw-bold" placeholder="0.00" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button id="addScannedPart" class="btn btn-premium w-100">
                                    <i class="bi bi-plus-lg me-1"></i> ADD PART
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Incoming Items List</h5>
                        <button id="clearCart" class="btn btn-sm btn-outline-danger">Clear All</button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 500px;">
                            <table class="table table-hover align-middle mb-0" id="inCartTable">
                                <thead>
                                    <tr>
                                        <th>Part Info</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Prev Cost</th>
                                        <th class="text-end">New Cost</th>
                                        <th class="text-end">Subtotal</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light p-3 text-end">
                        <button id="submitStockIn" class="btn btn-success btn-lg px-5 fw-bold shadow">
                            <i class="bi bi-save me-2"></i> CONFIRM RECEIPT
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDEBAR -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Price Hike Alerts</h5>
                    </div>
                    <div class="card-body" id="hikeAlertsList">
                        <p class="text-muted small">Price increases will appear here after adding items.</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold">Receipt Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Total Unique Parts</span>
                            <span id="summaryTotalItems" class="fw-bold">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Total Qty Received</span>
                            <span id="summaryTotalQty" class="fw-bold">0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Total Value (Cost)</span>
                            <span id="summaryTotalValue" class="fw-bold text-success">₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Price Hikes Detected</span>
                            <span id="summaryHikeCount" class="badge bg-danger rounded-pill">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost History Modal -->
    <div class="modal fade" id="costHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background:var(--primary-dark);">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="bi bi-clock-history me-2"></i>Cost History: <span id="historyPartNo"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Cost</th>
                                    <th>Invoice #</th>
                                    <th>Supplier</th>
                                </tr>
                            </thead>
                            <tbody id="costHistoryBody">
                                <tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // ============================================================
    // STATE
    // ============================================================
    let cart = [];
    let currentlyScannedItem = null; // { part_no, description, brand, cost, price, isNew }
    let searchTimer = null;
    let dropdownIndex = -1;
    let dropdownResults = [];

    // ============================================================
    // DOCUMENT READY
    // ============================================================
    $(document).ready(function () {
        $('#invoiceNo').focus();

        // Move to part search when Enter pressed in header fields
        $('#invoiceNo, #supplier, #dateReceived').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#partSearchInput').focus(); }
        });

        // ---- SEARCH INPUT BEHAVIOUR ----
        $('#partSearchInput').on('input', function () {
            let q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 1) { hideDropdown(); return; }
            searchTimer = setTimeout(() => doSearch(q), 220);
        });

        $('#partSearchInput').on('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); moveDropdownSelection(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); moveDropdownSelection(-1); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                if (dropdownIndex >= 0 && dropdownResults[dropdownIndex]) {
                    selectPart(dropdownResults[dropdownIndex]);
                } else {
                    // Check exact match or treat as new
                    let q = $(this).val().trim();
                    if (q) commitPartSearch(q);
                }
            } else if (e.key === 'Escape') {
                hideDropdown();
            }
        });

        // Close dropdown on outside click
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.part-search-wrapper').length) hideDropdown();
        });

        // ---- COST INPUT — compare to prev ----
        $('#scannedCost').on('input', function () {
            if (!currentlyScannedItem || currentlyScannedItem.isNew) return;
            let newCost = parseFloat($(this).val()) || 0;
            let prevCost = parseFloat(currentlyScannedItem.cost) || 0;
            let $input = $(this);
            $input.removeClass('cost-hike-input cost-lower-input');
            $('#costChangeIndicator').empty();
            $('#costUpdateHint').addClass('d-none');
            if (prevCost > 0 && newCost !== prevCost) {
                $('#costUpdateHint').removeClass('d-none');
                if (newCost > prevCost) {
                    $input.addClass('cost-hike-input');
                    let pct = ((newCost - prevCost) / prevCost * 100).toFixed(1);
                    $('#costChangeIndicator').html(`<span class="text-danger small"><i class="bi bi-arrow-up-short"></i>+${pct}% cost hike</span>`);
                } else {
                    $input.addClass('cost-lower-input');
                    let pct = ((prevCost - newCost) / prevCost * 100).toFixed(1);
                    $('#costChangeIndicator').html(`<span class="text-success small"><i class="bi bi-arrow-down-short"></i>-${pct}% lower cost</span>`);
                }
            }
        });

        // ---- ADD PART BUTTON ----
        $('#addScannedPart').on('click', addCurrentPartToCart);

        // Enter from Qty jumps to Cost, Enter from Cost adds to cart
        $('#scannedQty').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#scannedCost').focus().select(); }
        });
        $('#scannedCost').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addCurrentPartToCart(); }
        });
        // For new part: Enter on desc goes to qty
        $('#newPartDesc').on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); $('#scannedQty').focus().select(); }
        });

        // ---- SUBMIT ----
        $('#submitStockIn').on('click', submitStockIn);

        // ---- CLEAR CART ----
        $('#clearCart').on('click', function () {
            if (cart.length === 0) return;
            Swal.fire({
                title: 'Clear all items?', icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d32f2f', confirmButtonText: 'Yes, clear'
            }).then(r => { if (r.isConfirmed) { cart = []; renderCart(); } });
        });

        // Cost history link
        $(document).on('click', '#costHistoryLink', function () {
            let pno = currentlyScannedItem ? currentlyScannedItem.part_no : '';
            if (pno) showCostHistory(pno);
        });

        // Auto-fill all date/month pickers with today's date
        function autoFillDates(container = document) {
            const today = new Date();
            const yyyy = today.getFullYear();
            let mm = today.getMonth() + 1;
            let dd = today.getDate();
            if (dd < 10) dd = '0' + dd;
            if (mm < 10) mm = '0' + mm;
            const formattedDate = `${yyyy}-${mm}-${dd}`;
            
            $(container).find('input[type="date"], input[type="datetime-local"]').each(function() {
                if (!$(this).val()) {
                    if ($(this).attr('type') === 'date') {
                        $(this).val(formattedDate);
                    } else if ($(this).attr('type') === 'datetime-local') {
                        let hh = today.getHours();
                        let min = today.getMinutes();
                        if (hh < 10) hh = '0' + hh;
                        if (min < 10) min = '0' + min;
                        $(this).val(`${formattedDate}T${hh}:${min}`);
                    }
                }
            });
        }
        autoFillDates();
    });

    // ============================================================
    // SEARCH & DROPDOWN
    // ============================================================
    function doSearch(q) {
        $.get('../api/spareparts_inventory.php', { action: 'search_parts_for_in', query: q }, function (res) {
            if (res.success && res.data && res.data.length) {
                dropdownResults = res.data;
                showDropdown(res.data, q);
            } else {
                // No results — show "add as new" option
                dropdownResults = [];
                showDropdownNew(q);
            }
        });
    }

    function showDropdown(parts, q) {
        dropdownIndex = -1;
        let html = '';
        parts.forEach((p, i) => {
            let pno = highlightMatch(p.part_no, q);
            let desc = highlightMatch(p.description, q);
            html += `<div class="search-item" data-idx="${i}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${pno}</strong>
                        <span class="part-no-badge">${escHtml(p.brand || '')}</span>
                        <div class="small text-muted">${desc}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary">₱${parseFloat(p.cost||0).toFixed(2)}</div>
                        <div class="small text-muted">Stock: ${p.current_stock}</div>
                    </div>
                </div>
            </div>`;
        });
        $('#searchDropdown').html(html).show();

        $('#searchDropdown').off('click').on('click', '.search-item', function () {
            let idx = parseInt($(this).data('idx'));
            selectPart(dropdownResults[idx]);
        });
    }

    function showDropdownNew(q) {
        dropdownIndex = -1;
        let html = `<div class="search-item" id="addNewPartOption">
            <i class="bi bi-plus-circle-fill text-warning me-2"></i>
            <strong>No match found.</strong> Add <em>"${escHtml(q)}"</em> as a <strong>new part</strong>?
        </div>`;
        $('#searchDropdown').html(html).show();
        $('#addNewPartOption').on('click', function () {
            commitPartSearch(q);
        });
    }

    function hideDropdown() {
        $('#searchDropdown').hide();
        dropdownIndex = -1;
        dropdownResults = [];
    }

    function moveDropdownSelection(dir) {
        let items = $('#searchDropdown .search-item');
        if (!items.length) return;
        items.removeClass('active');
        dropdownIndex = Math.max(0, Math.min(items.length - 1, dropdownIndex + dir));
        $(items[dropdownIndex]).addClass('active');
    }

    function highlightMatch(text, q) {
        if (!text) return '';
        let safe = escHtml(text);
        let safeQ = escHtml(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return safe.replace(new RegExp(safeQ, 'gi'), m => `<mark>${m}</mark>`);
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ============================================================
    // SELECT / FETCH PART
    // ============================================================
    function selectPart(partData) {
        hideDropdown();
        $('#partSearchInput').val(partData.part_no);
        
        // Show immediately using what we have
        showExistingPart(partData);
        
        // Background fetch for extra details (compatibility, latest cost from DB if needed)
        // This ensures the UI is responsive while still getting the full data
        $.get('../api/spareparts_inventory.php', { action: 'get_part_details_with_compatibility', part_no: partData.part_no }, function (res) {
            if (res.success && res.data) {
                // Update compatibility if it changed or was added
                if (res.data.compatibility && res.data.compatibility.length) {
                    $('#compatibilityList').empty();
                    res.data.compatibility.forEach(m => {
                        $('#compatibilityList').append(`<span class="compatibility-badge">${m}</span>`);
                    });
                }
                // Silently update cost/price in state if they differ
                if (currentlyScannedItem && currentlyScannedItem.part_no === res.data.part_no) {
                    currentlyScannedItem.cost = parseFloat(res.data.cost) || 0;
                    currentlyScannedItem.price = parseFloat(res.data.price) || 0;
                }
            }
        });
    }

    function commitPartSearch(q) {
        // Try exact lookup first
        $.get('../api/spareparts_inventory.php', { action: 'get_part_details_with_compatibility', part_no: q }, function (res) {
            hideDropdown();
            if (res.success && res.data) {
                $('#partSearchInput').val(res.data.part_no);
                showExistingPart(res.data);
            } else {
                // Truly new
                $('#partSearchInput').val(q);
                showNewPartEntry(q);
            }
        });
    }

    function fetchAndDisplayPart(part_no) {
        $.get('../api/spareparts_inventory.php', { action: 'get_part_details_with_compatibility', part_no: part_no }, function (res) {
            if (res.success && res.data) {
                showExistingPart(res.data);
            } else {
                showNewPartEntry(part_no);
            }
        });
    }

    function showExistingPart(data) {
        currentlyScannedItem = {
            part_no: data.part_no,
            description: data.description,
            brand: data.brand || '',
            cost: parseFloat(data.cost) || 0,
            price: parseFloat(data.price) || 0,
            isNew: false
        };

        const displayCost = currentlyScannedItem.cost;

        $('#partTitle').text(data.description || 'N/A');
        $('#partNoDisplay').html(`<span class="text-muted">${data.part_no}</span>`);
        $('#prevCostDisplay').text('₱' + displayCost.toLocaleString('en-PH', {minimumFractionDigits:2}));
        $('#scannedCost').val(displayCost.toFixed(2)).removeClass('cost-hike-input cost-lower-input');
        $('#scannedQty').val(1);
        $('#costChangeIndicator').empty();
        $('#costUpdateHint').addClass('d-none');
        $('#costHistoryLink').removeClass('d-none');
        $('#newPartBadge').addClass('d-none');
        $('#newPartFields').addClass('d-none');
        $('#newPartDesc').val('');
        $('#newPartBrand').val('');

        // Compatibility (if available in the initial data)
        $('#compatibilityList').empty();
        if (data.compatibility && data.compatibility.length) {
            data.compatibility.forEach(m => {
                $('#compatibilityList').append(`<span class="compatibility-badge">${m}</span>`);
            });
        }

        $('#partDisplay').removeClass('new-part-mode').show(); // Immediate show
        
        // Immediate focus on Qty
        $('#scannedQty').focus().select();
    }

    function showNewPartEntry(part_no) {
        currentlyScannedItem = {
            part_no: part_no,
            description: '',
            brand: '',
            cost: 0,
            price: 0,
            isNew: true
        };

        $('#partTitle').text('New Part Entry');
        $('#partNoDisplay').html(`<span class="fw-bold text-warning">${part_no}</span>`);
        $('#prevCostDisplay').text('N/A');
        $('#scannedCost').val('').removeClass('cost-hike-input cost-lower-input');
        $('#scannedQty').val(1);
        $('#costChangeIndicator').empty();
        $('#costUpdateHint').addClass('d-none');
        $('#costHistoryLink').addClass('d-none');
        $('#compatibilityList').empty();
        $('#newPartBadge').removeClass('d-none');
        $('#newPartFields').removeClass('d-none');
        $('#newPartDesc').val('');
        $('#newPartBrand').val('');

        $('#partDisplay').addClass('new-part-mode').fadeIn(300);
        setTimeout(() => $('#newPartDesc').focus(), 350);
    }

    // ============================================================
    // CART
    // ============================================================
    function addCurrentPartToCart() {
        if (!currentlyScannedItem) {
            Swal.fire('No Part Selected', 'Please search or scan a part number first.', 'warning');
            return;
        }

        let qty = parseInt($('#scannedQty').val()) || 1;
        let newCost = parseFloat($('#scannedCost').val());

        if (isNaN(newCost) || newCost < 0) {
            Swal.fire('Invalid Cost', 'Please enter a valid cost amount.', 'warning');
            $('#scannedCost').focus();
            return;
        }

        // For new parts, require description
        if (currentlyScannedItem.isNew) {
            let desc = $('#newPartDesc').val().trim();
            let brand = $('#newPartBrand').val().trim();
            if (!desc) {
                Swal.fire('Missing Description', 'Please enter the part description.', 'warning');
                $('#newPartDesc').focus();
                return;
            }
            currentlyScannedItem.description = desc;
            currentlyScannedItem.brand = brand;
        }

        let itemToAdd = {
            part_no: currentlyScannedItem.part_no,
            description: currentlyScannedItem.description,
            brand: currentlyScannedItem.brand,
            prev_cost: currentlyScannedItem.isNew ? 0 : currentlyScannedItem.cost,
            cost: newCost,
            price: currentlyScannedItem.price || 0,
            quantity: qty,
            isNew: currentlyScannedItem.isNew
        };

        // Check duplicate in cart
        let existing = cart.findIndex(c => c.part_no === itemToAdd.part_no);
        if (existing >= 0) {
            cart[existing].quantity += qty;
            // Update cost if changed
            cart[existing].cost = newCost;
        } else {
            cart.push(itemToAdd);
        }

        renderCart();

        // Reset
        $('#partDisplay').fadeOut(200);
        $('#partSearchInput').val('').focus();
        currentlyScannedItem = null;
    }

    function renderCart() {
        let html = '';
        let totalVal = 0, totalItems = 0, totalQty = 0, hikes = 0;

        cart.forEach((item, index) => {
            let subtotal = item.quantity * item.cost;
            totalVal += subtotal;
            totalItems++;
            totalQty += item.quantity;

            let isHike = !item.isNew && item.cost > item.prev_cost;
            if (isHike) hikes++;

            let costClass = isHike ? 'text-danger fw-bold' : (item.isNew ? 'text-warning' : '');
            let prevCostDisplay = item.isNew ? '<em class="text-muted small">New</em>' : '₱' + item.prev_cost.toFixed(2);

            html += `
            <tr>
                <td>
                    <div class="fw-bold">${item.description} ${item.isNew ? '<span class="badge-new-part">NEW</span>' : ''}</div>
                    <div class="small text-muted">${item.part_no}${item.brand ? ' · ' + item.brand : ''}</div>
                </td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm mx-auto text-center"
                        style="width:70px;" value="${item.quantity}" min="1"
                        onchange="updateCartItem(${index}, 'quantity', this.value)">
                </td>
                <td class="text-end">${prevCostDisplay}</td>
                <td class="text-end">
                    <input type="number" step="0.01" class="form-control form-control-sm ms-auto text-end ${costClass}"
                        style="width:110px;" value="${item.cost.toFixed(2)}"
                        onchange="updateCartItem(${index}, 'cost', this.value)">
                </td>
                <td class="text-end fw-bold">₱${subtotal.toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-link text-danger" onclick="removeFromCart(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`;
        });

        if (cart.length === 0) {
            html = `<tr><td colspan="6" class="text-center text-muted py-4">No items added yet.</td></tr>`;
        }

        $('#cartBody').html(html);
        $('#summaryTotalItems').text(totalItems);
        $('#summaryTotalQty').text(totalQty);
        $('#summaryTotalValue').text('₱' + totalVal.toLocaleString('en-PH', {minimumFractionDigits:2}));
        $('#summaryHikeCount').text(hikes);

        checkHikes();
    }

    function updateCartItem(index, key, value) {
        if (key === 'quantity') cart[index][key] = parseInt(value) || 1;
        else cart[index][key] = parseFloat(value) || 0;
        renderCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        renderCart();
    }

    function checkHikes() {
        $('#hikeAlertsList').empty();
        let hikeCount = 0;
        cart.forEach(item => {
            if (!item.isNew && item.cost > item.prev_cost) {
                hikeCount++;
                let diff = item.cost - item.prev_cost;
                let perc = item.prev_cost > 0 ? ((diff / item.prev_cost) * 100).toFixed(1) : '∞';
                $('#hikeAlertsList').append(`
                    <div class="hike-alert mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="d-block">${item.part_no}</strong>
                                <span class="small">${item.description}</span>
                                <div class="small">₱${item.prev_cost.toFixed(2)} → ₱${item.cost.toFixed(2)} (+${perc}%)</div>
                            </div>
                            <span class="badge bg-danger">+₱${diff.toFixed(2)}</span>
                        </div>
                    </div>`);
            }
        });
        if (hikeCount === 0) {
            $('#hikeAlertsList').html('<p class="text-muted small">No price hikes detected.</p>');
        }
    }

    // ============================================================
    // COST HISTORY MODAL
    // ============================================================
    function showCostHistory(part_no) {
        $('#historyPartNo').text(part_no);
        $('#costHistoryBody').html('<tr><td colspan="4" class="text-center text-muted py-3">Loading...</td></tr>');
        $('#costHistoryModal').modal('show');

        $.get('../api/spareparts_inventory.php', { action: 'get_price_history', part_no: part_no }, function (res) {
            if (res.success && res.data && res.data.length) {
                let html = '';
                res.data.forEach(h => {
                    html += `<tr>
                        <td>${h.transaction_date || h.date || ''}</td>
                        <td class="fw-bold">₱${parseFloat(h.cost||0).toFixed(2)}</td>
                        <td>${h.invoice_no || '—'}</td>
                        <td>${h.supplier || '—'}</td>
                    </tr>`;
                });
                $('#costHistoryBody').html(html);
            } else {
                $('#costHistoryBody').html('<tr><td colspan="4" class="text-center text-muted py-3">No history found.</td></tr>');
            }
        });
    }

    // ============================================================
    // SUBMIT
    // ============================================================
    function submitStockIn() {
        let invoice = $('#invoiceNo').val().trim();
        if (!invoice) {
            Swal.fire('Missing Invoice', 'Please enter an Invoice/DR number.', 'error');
            $('#invoiceNo').focus();
            return;
        }
        if (cart.length === 0) {
            Swal.fire('Empty List', 'Please add at least one part.', 'info');
            return;
        }

        let hikeParts = cart.filter(i => !i.isNew && i.cost > i.prev_cost);
        let hikeMsg = hikeParts.length ? `<br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${hikeParts.length} part(s) have price increases. The master cost will be updated.</small>` : '';

        Swal.fire({
            title: 'Confirm Receipt?',
            html: `Recording <strong>${cart.length}</strong> unique part(s) into inventory.${hikeMsg}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#004d40',
            confirmButtonText: 'Yes, Confirm Receipt'
        }).then(result => {
            if (!result.isConfirmed) return;

            $('#submitStockIn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            $.ajax({
                url: '../api/spareparts_inventory.php?action=add_multiple_parts_in',
                method: 'POST',
                data: {
                    items: JSON.stringify(cart),
                    invoice_no: invoice,
                    supplier_source: $('#supplier').val().trim(),
                    date_in: $('#dateReceived').val()
                },
                success: function (response) {
                    $('#submitStockIn').prop('disabled', false).html('<i class="bi bi-save me-2"></i> CONFIRM RECEIPT');
                    if (response.success) {
                        Swal.fire({
                            title: 'Receipt Confirmed!',
                            html: 'Stock received and inventory updated successfully.',
                            icon: 'success',
                            confirmButtonColor: '#004d40'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', response.message || 'Failed to save.', 'error');
                    }
                },
                error: function () {
                    $('#submitStockIn').prop('disabled', false).html('<i class="bi bi-save me-2"></i> CONFIRM RECEIPT');
                    Swal.fire('Error', 'Failed to communicate with server.', 'error');
                }
            });
        });
    }
    </script>
</body>
</html>
