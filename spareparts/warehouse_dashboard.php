<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['position'] !== 'Spareparts-Warehouse') {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$greeting = 'Welcome back, ' . htmlspecialchars($username) . '!';
$today = date('l, F j, Y');

// Check module settings
require_once '../api/db_config.php';
$allSettings = [];
$settingsRes = $conn->query("SELECT setting_key, setting_value FROM spareparts_settings");
if ($settingsRes) {
    while ($sRow = $settingsRes->fetch_assoc()) {
        $allSettings[$sRow['setting_key']] = $sRow['setting_value'];
    }
}

function isVisible($menuId) {
    global $allSettings, $_SESSION;
    $pos = $_SESSION['position'];
    $key = "menu_vis_{$pos}_{$menuId}";
    return !isset($allSettings[$key]) || $allSettings[$key] !== 'false';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Dashboard - Spareparts MS</title>
    <meta name="description" content="SMDI Spare Parts Management - Warehouse Dashboard">
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --green-900: #003d33;
            --green-800: #004d40;
            --green-700: #00695c;
            --green-600: #00796b;
            --green-400: #26a69a;
            --green-50:  #e0f2f1;
            --white: #ffffff;
            --gray-50: #f8fafb;
            --gray-100: #f1f5f4;
            --gray-200: #e2e8e6;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,.08), 0 2px 6px rgba(0,0,0,.05);
            --shadow-lg: 0 10px 30px rgba(0,0,0,.10), 0 4px 10px rgba(0,0,0,.06);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            margin: 0; padding: 0;
            color: var(--gray-700);
            min-height: 100vh;
        }

        /* ── TOP NAV ── */
        .top-nav {
            background: var(--green-800);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }
        .nav-brand {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
        }
        .nav-brand img { height: 34px; border-radius: 6px; }
        .nav-brand-text {
            font-size: 1rem; font-weight: 700; color: var(--white);
            letter-spacing: 0.3px; text-transform: uppercase;
        }
        .nav-right { display: flex; align-items: center; gap: 18px; }
        .nav-user {
            display: flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,0.9); font-size: 0.82rem; font-weight: 500;
        }
        .nav-user-avatar {
            width: 34px; height: 34px;
            background: rgba(255,255,255,0.2); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.85rem; color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .btn-logout {
            border: 1.5px solid rgba(255,255,255,0.45); color: white;
            background: transparent; padding: 5px 16px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600; text-decoration: none;
            transition: all 0.2s; letter-spacing: 0.3px;
        }
        .btn-logout:hover {
            background: rgba(255,255,255,0.15); border-color: white; color: white;
        }

        /* ── HERO BANNER ── */
        .hero-banner {
            background: linear-gradient(135deg, var(--green-900) 0%, var(--green-700) 60%, var(--green-400) 100%);
            padding: 2.5rem 2rem 3.5rem;
            position: relative; overflow: hidden;
        }
        .hero-banner::before {
            content: ''; position: absolute; top: -60px; right: -60px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.04); border-radius: 50%;
        }
        .hero-banner::after {
            content: ''; position: absolute; bottom: -80px; left: 30%;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.03); border-radius: 50%;
        }
        .hero-content { position: relative; z-index: 1; }
        .hero-greeting {
            font-size: 1.65rem; font-weight: 800; color: var(--white);
            margin: 0 0 4px; letter-spacing: -0.3px;
        }
        .hero-sub {
            color: rgba(255,255,255,0.72); font-size: 0.875rem; font-weight: 400;
            display: flex; align-items: center; gap: 8px;
        }
        .hero-sub i { font-size: 0.8rem; }
        .hero-role-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.9);
            padding: 3px 12px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 600; margin-left: 8px;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            max-width: 1300px; margin: -1.5rem auto 3rem;
            padding: 0 2rem; position: relative; z-index: 2;
        }

        /* ── SECTION LABEL ── */
        .section-label {
            font-size: 0.72rem; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; color: var(--green-700);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 8px;
        }
        .section-label::after {
            content: ''; flex: 1; height: 1px; background: var(--gray-200);
        }

        /* ── MODULE GRID ── */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem; margin-bottom: 2rem;
        }
        .module-card {
            background: var(--white); border-radius: 14px;
            padding: 1.6rem 1.4rem; text-decoration: none; color: inherit;
            border: 1.5px solid var(--gray-200); box-shadow: var(--shadow-sm);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; align-items: flex-start;
            gap: 12px; position: relative; overflow: hidden;
        }
        .module-card::before {
            content: ''; position: absolute; top: 0; left: 0;
            width: 100%; height: 3px;
            background: linear-gradient(90deg, var(--green-800), var(--green-400));
            opacity: 0; transition: opacity 0.25s;
        }
        .module-card:hover {
            transform: translateY(-5px); box-shadow: var(--shadow-lg);
            border-color: var(--green-400); color: inherit; text-decoration: none;
        }
        .module-card:hover::before { opacity: 1; }
        .module-icon-wrap {
            width: 52px; height: 52px; background: var(--green-50);
            border-radius: 12px; display: flex; align-items: center;
            justify-content: center; transition: background 0.25s;
        }
        .module-card:hover .module-icon-wrap { background: var(--green-800); }
        .module-icon-wrap i {
            font-size: 1.5rem; color: var(--green-800); transition: color 0.25s;
        }
        .module-card:hover .module-icon-wrap i { color: var(--white); }
        .module-title {
            font-size: 0.95rem; font-weight: 700; color: var(--gray-700); line-height: 1.3;
        }
        .module-desc {
            font-size: 0.78rem; color: var(--gray-500); line-height: 1.5;
        }
        .module-arrow {
            margin-top: auto; color: var(--green-600); font-size: 0.8rem;
            font-weight: 600; display: flex; align-items: center; gap: 4px;
            opacity: 0; transition: opacity 0.2s, transform 0.2s;
        }
        .module-card:hover .module-arrow { opacity: 1; transform: translateX(4px); }

        /* ── SUMMARY SIDEBAR ── */
        .sidebar-card {
            background: var(--white); border-radius: 14px;
            border: 1.5px solid var(--gray-200); box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .sidebar-header {
            background: var(--green-800); color: var(--white);
            padding: 14px 20px; font-size: 0.78rem; font-weight: 700;
            letter-spacing: 0.8px; text-transform: uppercase;
            display: flex; align-items: center; gap: 8px;
        }
        .sidebar-body { padding: 16px 20px; }
        .summary-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid var(--gray-100);
            font-size: 0.82rem;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row-label { color: var(--gray-500); font-weight: 500; }
        .summary-row-val { font-weight: 700; color: var(--green-800); }

        .sidebar-footer {
            background: var(--green-50); border-top: 1.5px solid var(--green-400);
            padding: 14px 20px;
        }
        .total-row {
            display: flex; justify-content: space-between;
            font-size: 0.82rem; margin-bottom: 8px; font-weight: 600;
        }
        .total-row:last-child { margin-bottom: 0; }
        .total-row-label { color: var(--gray-700); }
        .total-row-val { color: var(--green-800); font-size: 1rem; font-weight: 800; }

        .bg-menu-gray { background: #64748b !important; }
        .bg-menu-red { background: #ef4444 !important; }
        .bg-menu-purple { background: #8b5cf6 !important; }
        .bg-menu-orange { background: #f97316 !important; }
        .bg-menu-blue { background: #3b82f6 !important; }
        .bg-menu-teal { background: #0d9488 !important; }
        .bg-menu-cyan { background: #06b6d4 !important; }
        .bg-menu-green { background: #10b981 !important; }
        .bg-menu-indigo { background: #6366f1 !important; }

        .module-card[class*="bg-menu-"] {
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .module-card[class*="bg-menu-"] .module-title {
            color: white !important;
        }
        .module-card[class*="bg-menu-"] .module-desc {
            color: rgba(255,255,255,0.8) !important;
        }
        .module-card[class*="bg-menu-"] .module-icon-wrap {
            background: rgba(255,255,255,0.2) !important;
        }
        .module-card[class*="bg-menu-"] .module-icon-wrap i {
            color: white !important;
        }
        .module-card[class*="bg-menu-"] .module-arrow {
            color: white !important;
        }
        .module-card[class*="bg-menu-"]:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.2);
            filter: brightness(1.05);
        }

        /* ── FOOTER ── */
        .footer-strip {
            text-align: center; padding: 1.5rem;
            font-size: 0.75rem; color: var(--gray-500);
            border-top: 1px solid var(--gray-200);
        }

        /* ── MODAL STYLES (for IN modal) ── */
        .scanning-zone {
            background: #fff; border-radius: 16px;
            box-shadow: var(--shadow-md); padding: 30px;
            margin-bottom: 30px; border-top: 5px solid var(--green-400);
        }
        .part-search-wrapper { position: relative; }
        .part-search-wrapper i.search-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--green-800); font-size: 1.2rem; z-index: 5;
        }
        #partSearchInput {
            padding-left: 45px; height: 50px; border-radius: 8px; border: 2px solid #eee;
        }
        #searchDropdown {
            position: absolute; top: 100%; left: 0; right: 0;
            background: #fff; border: 1px solid #ddd; border-top: none;
            border-radius: 0 0 10px 10px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            z-index: 1050; max-height: 250px; overflow-y: auto; display: none;
        }
        .search-item {
            padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0;
        }
        .search-item:hover { background: #e8f5e9; }
        .part-details-card {
            background: #fff; border-radius: 10px; padding: 20px;
            border-left: 4px solid var(--green-400); display: none;
        }
        .cost-hike-input { border-color: #f44336 !important; color: #f44336 !important; font-weight: bold; }
        .cost-lower-input { border-color: #43a047 !important; color: #43a047 !important; }
        .btn-premium {
            background: var(--green-800); color: white; padding: 12px 25px;
            border-radius: 8px; font-weight: 600; transition: all 0.3s; border: none;
        }
        .btn-premium:hover {
            background: var(--green-900); color: white; transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .badge-new-part {
            background: #ff9800; color: #fff; font-size: 0.7rem;
            padding: 3px 8px; border-radius: 20px; vertical-align: middle;
        }
        .compatibility-badge {
            background: var(--green-50); color: #00796b; padding: 4px 10px;
            border-radius: 20px; font-size: 0.8rem; margin-right: 4px;
            margin-bottom: 4px; display: inline-block;
        }
        .cost-history-link {
            font-size: 0.75rem; cursor: pointer; color: var(--green-400);
            text-decoration: underline dotted;
        }

        /* Modal overrides for premium look */
        .modal-content { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header.bg-dark {
            background: linear-gradient(135deg, var(--green-800), var(--green-900)) !important;
        }
        .table thead th {
            background-color: var(--green-800) !important;
            color: #fff !important;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.6px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .hero-banner { padding: 2rem 1rem 3rem; }
            .main-content { padding: 0 1rem; }
            .modules-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
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

    <!-- TOP NAVBAR -->
    <nav class="top-nav">
        <a class="nav-brand" href="#">
            <img src="../assets/img/rcsm_logo.jpg" alt="SMDI Logo">
            <span class="nav-brand-text">Warehouse Dashboard</span>
        </a>
        <div class="nav-right">
            <!-- Notification Bell -->
            <a href="javascript:void(0)" onclick="if(document.getElementById('incomingTransferAlertModal')) bootstrap.Modal.getOrCreateInstance(document.getElementById('incomingTransferAlertModal')).show()" class="text-white text-decoration-none me-3 position-relative" title="Incoming Transfers">
                <i class="bi bi-bell-fill fs-5"></i>
                <span id="incoming-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.55rem; padding: 0.25em 0.5em;">0</span>
            </a>
            <div class="nav-user">
                <div class="nav-user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div>
                    <div style="font-weight:700;color:white;"><?php echo htmlspecialchars(strtoupper($username)); ?></div>
                    <div style="font-size:0.7rem;opacity:0.7;"><?php echo htmlspecialchars($branch); ?></div>
                </div>
            </div>
            <a href="../api/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <!-- HERO BANNER -->
    <div class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-greeting"><?php echo $greeting; ?></h1>
            <p class="hero-sub">
                <i class="bi bi-calendar3"></i> <?php echo $today; ?>
                &nbsp;|&nbsp; <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($branch); ?>
                <span class="hero-role-badge"><i class="bi bi-building"></i> Warehouse</span>
            </p>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="row g-4">

            <!-- Pending Transfers Section -->
            <div class="col-12" id="pending-transfers-container" style="display: none;">
                <!-- Populated via JS -->
            </div>

            <!-- Module Grid -->
            <div class="col-lg-9">
                <div class="section-label"><i class="bi bi-grid-3x3-gap"></i> Modules</div>
                <div class="modules-grid">

                    <?php if (isVisible('received-stocks-rr-warehouse')): ?>
                    <a href="#" class="module-card bg-menu-gray" data-bs-toggle="modal" data-bs-target="#inventoryInModal">
                        <div class="module-icon-wrap"><i class="bi bi-box-arrow-in-down"></i></div>
                        <div>
                            <div class="module-title">Received Stocks (RR/IN)</div>
                            <div class="module-desc">Receive stocks from supplier deliveries</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('stock-card-warehouse')): ?>
                    <a href="warehouse_spareparts.php?tab=inventory" class="module-card bg-menu-red">
                        <div class="module-icon-wrap"><i class="bi bi-card-list"></i></div>
                        <div>
                            <div class="module-title">Stock Card</div>
                            <div class="module-desc">View and manage stock list & levels</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('received-stock-warehouse')): ?>
                    <a href="received_stock.php" class="module-card bg-menu-purple">
                        <div class="module-icon-wrap"><i class="bi bi-journal-check"></i></div>
                        <div>
                            <div class="module-title">Received Stock</div>
                            <div class="module-desc">Stocks received from other branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('stock-transfer-warehouse')): ?>
                    <a href="transfer_stock.php" class="module-card bg-menu-blue">
                        <div class="module-icon-wrap"><i class="bi bi-truck"></i></div>
                        <div>
                            <div class="module-title">Stock Transfer</div>
                            <div class="module-desc">Transfer stocks to other branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('find-stocks-warehouse')): ?>
                    <a href="find_stocks.php" class="module-card bg-menu-purple">
                        <div class="module-icon-wrap"><i class="bi bi-search"></i></div>
                        <div>
                            <div class="module-title">Find Stocks</div>
                            <div class="module-desc">Find stocks available in other branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('master-reports-warehouse')): ?>
                    <a href="master_reports.php" class="module-card bg-menu-green">
                        <div class="module-icon-wrap"><i class="bi bi-file-earmark-bar-graph"></i></div>
                        <div>
                            <div class="module-title">Master Reports</div>
                            <div class="module-desc">View inventory & movement reports</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('beginning-inventory-warehouse')): ?>
                    <a href="beginning_inventory.php" class="module-card bg-menu-red" id="beginning-inventory-card">
                        <div class="module-icon-wrap"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
                        <div>
                            <div class="module-title">Beginning Inventory</div>
                            <div class="module-desc">Enter initial stock levels (Excel-style)</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('barcode-generator')): ?>
                    <a href="barcode_generator.php" class="module-card bg-menu-indigo">
                        <div class="module-icon-wrap"><i class="bi bi-upc-scan"></i></div>
                        <div>
                            <div class="module-title">Barcode Generator</div>
                            <div class="module-desc">Generate & print product barcodes</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-lg-3">
                <div class="section-label"><i class="bi bi-bar-chart"></i> Today's Summary</div>
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <i class="bi bi-calendar-check"></i> Stocks Summary
                    </div>
                    <div class="sidebar-body">
                        <div class="summary-row">
                            <span class="summary-row-label">Received Stocks (Qty)</span>
                            <span class="summary-row-val" id="received-qty">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Received Amount</span>
                            <span class="summary-row-val" id="received-amount">₱0.00</span>
                        </div>
                        <div class="summary-row border-top mt-2 pt-2">
                            <span class="summary-row-label">Transferred Stocks (Qty)</span>
                            <span class="summary-row-val" id="transferred-qty">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Transferred Amount</span>
                            <span class="summary-row-val" id="transferred-amount">₱0.00</span>
                        </div>
                    </div>
                </div>

                <div class="section-label mt-4"><i class="bi bi-box-seam"></i> Inventory Summary</div>
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <i class="bi bi-tags"></i> Stocks by Brand
                    </div>
                    <div class="sidebar-body" id="inventory-summary-container" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center py-3 text-muted small">Loading stocks...</div>
                    </div>
                    <div class="sidebar-footer">
                        <div class="total-row">
                            <span class="total-row-label">Total Qty</span>
                            <span class="total-row-val" id="inv-total-qty">0</span>
                        </div>
                        <div class="total-row">
                            <span class="total-row-label">Total Cost</span>
                            <span class="total-row-val" id="inv-total-value">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-strip">
        SMDI Spare Parts Management System &copy; <?php echo date('Y'); ?>
    </div>

    <!-- STOCK CARD MODAL -->
    <div class='modal fade' id='stockCardModal' tabindex='-1'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
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
                                    <div class="text-center mb-3">
                                        <div id="stockCardImage" class="rounded-4 border bg-white mx-auto d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 150px; height: 150px;">
                                            <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    </div>
                                    <h5 id="stockCardDescription" class="fw-bold text-center mb-1 text-dark">PART DESCRIPTION</h5>
                                    <p id="stockCardBrand" class="text-muted text-center mb-4 small fw-bold text-uppercase">BRAND NAME</p>
                                    
                                    <div class="row g-3 mb-4">
                                        <div class="col-6">
                                            <div class="p-3 bg-white rounded-4 border text-center shadow-sm">
                                                <div class="small text-muted fw-bold mb-1">STOCK LEVEL</div>
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
                </div>
            </div>
        </div>
    </div>
    
    <!-- INVENTORY IN MODAL -->
    <div class="modal fade" id="inventoryInModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-box-arrow-in-down me-2"></i>Supplier Receipt (IN)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-4">
                        <!-- LEFT COL -->
                        <div class="col-lg-9">
                            <!-- Step 1: Receipt Details -->
                            <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top: 5px solid #26a69a !important;">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold mb-3 text-muted text-uppercase small"><i class="bi bi-file-earmark-text me-2"></i>Step 1: Receipt Details</h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted">Invoice / DR # *</label>
                                            <input type="text" id="invoiceNo" class="form-control fw-bold border-primary" placeholder="Required" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted">Supplier Name</label>
                                            <input type="text" id="supplier" class="form-control" placeholder="e.g. ABC Trading">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted">Payment Mode</label>
                                            <select id="paymentMode" class="form-control">
                                                <option value="Cash">Cash</option>
                                                <option value="Charge">Charge (Payable)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small fw-bold text-muted">Date Received</label>
                                            <input type="date" id="dateReceived" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Part Search & Entry -->
                            <div class="scanning-zone shadow-sm mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold text-muted text-uppercase small"><i class="bi bi-upc-scan me-2"></i>Step 2: Scan / Search Part Number</h6>
                                </div>

                                <div class="part-search-wrapper mb-3">
                                    <i class="bi bi-search search-icon"></i>
                                    <input type="text" id="partSearchInput" class="form-control" placeholder="Type part number, name, or scan..." autocomplete="off">
                                    <div id="searchDropdown"></div>
                                </div>

                                <div id="partDisplay" class="part-details-card mb-3 shadow-sm p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 id="partTitle" class="fw-bold mb-1">PART NAME</h5>
                                            <p id="partNoDisplay" class="text-muted small mb-1">PART-NO</p>
                                            <div id="compatibilityList"></div>
                                        </div>
                                        <span id="newPartBadge" class="badge-new-part d-none">NEW PART</span>
                                    </div>

                                    <div id="newPartFields" class="d-none mb-3">
                                        <div class="alert alert-warning p-2 small mb-2">
                                            <i class="bi bi-exclamation-triangle me-1"></i> New part number detected.
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="small fw-bold text-muted">Brand</label>
                                                <input type="text" id="newPartBrand" class="form-control form-control-sm" placeholder="e.g. Honda">
                                            </div>
                                            <div class="col-md-8">
                                                <label class="small fw-bold text-muted">Description *</label>
                                                <input type="text" id="newPartDesc" class="form-control form-control-sm" placeholder="Full description" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row align-items-end g-3">
                                        <div class="col-md-3">
                                            <label class="small text-muted fw-bold d-block">Cost <span id="costHistoryLink" class="cost-history-link ms-1 d-none">(history)</span></label>
                                            <span id="prevCostDisplay" class="fw-bold text-black fs-5">₱0.00</span>
                                            <div id="costChangeIndicator" class="small mt-1"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small text-muted fw-bold">Qty *</label>
                                            <input type="number" id="scannedQty" class="form-control fw-bold border-primary" value="1" min="1">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small text-muted fw-bold">New Cost <span id="costUpdateHint" class="text-danger small d-none fw-normal">(updates master)</span></label>
                                            <input type="number" id="scannedCost" step="0.01" class="form-control fw-bold" placeholder="0.00" min="0">
                                        </div>
                                        <div class="col-md-3">
                                            <button id="addScannedPart" class="btn btn-premium w-100"><i class="bi bi-plus-lg me-1"></i> ADD</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Table -->
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold text-muted text-uppercase small"><i class="bi bi-list-check me-2"></i>Incoming Items</h6>
                                    <button id="clearCart" class="btn btn-sm btn-outline-danger px-3" style="border-radius:20px;">Clear All</button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 400px;">
                                        <table class="table table-hover align-middle mb-0" id="inCartTable">
                                            <thead class="bg-dark text-white">
                                                <tr>
                                                    <th class="ps-4">Item Details</th>
                                                    <th class="text-center">Qty</th>
                                                    <th class="text-end">Prev Cost</th>
                                                    <th class="text-end">New Cost</th>
                                                    <th class="text-end">Subtotal</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="cartBody">
                                                <tr><td colspan="6" class="text-center text-muted py-5">No items added yet.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT SIDEBAR -->
                        <div class="col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px; z-index: 10;">
                                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold text-muted text-uppercase small">Receipt Summary</h6></div>
                                <div class="card-body">
                                    <div id="brandSummaryContainer">
                                        <!-- Dynamic: Qty by Brand -->
                                    </div>
                                    <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                        <span class="text-muted">Total Qty</span>
                                        <span id="summaryTotalQty" class="fw-bold">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between fs-5 fw-bold mb-4 border-bottom pb-2">
                                        <span class="text-muted">Total Cost</span>
                                        <span id="summaryTotalValue" class="text-success">₱0.00</span>
                                    </div>
                                    <button id="submitStockIn" class="btn btn-success w-100 py-3 fw-bold shadow-sm" style="border-radius:12px;">
                                        <span id="confirmReceiptBtnInner"><i class="bi bi-save me-2"></i> CONFIRM RECEIPT</span>
                                    </button>
                                    <button id="printCurrentSummary" class="btn btn-outline-secondary w-100 mt-2 py-2 fw-bold" style="border-radius:12px;">
                                        <i class="bi bi-printer me-2"></i> PRINT SUMMARY
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost History Modal -->
    <div class="modal fade" id="costHistoryModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2"></i>Cost History: <span id="historyPartNo"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Cost</th>
                                    <th>Invoice #</th>
                                    <th class="pe-4">Supplier</th>
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

    <!-- INCOMING TRANSFER ALERT MODAL -->
    <div class="modal fade" id="incomingTransferAlertModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>PENDING INCOMING TRANSFERS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="incoming-alert-modal-body" style="background: #f8f9fa; max-height: 480px; overflow-y: auto;">
                    <!-- Populated via JS -->
                    <div class="text-center p-5">
                        <div class="spinner-border text-danger"></div>
                        <p class="mt-2 text-muted fw-bold small text-uppercase ls-1">Checking for pending transfers...</p>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white p-3 shadow-sm" style="background: #f8f9fa !important;">
                    <button type="button" class="btn btn-light fw-bold rounded-pill px-4 text-muted small" data-bs-dismiss="modal">Close</button>
                    <a href="transfer_stock.php?tab=transfer-in" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm small">
                        <i class="bi bi-box-arrow-in-down me-1"></i> View and Accept
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/spareparts_dashboard.js"></script>
    <script src="../js/spareparts_stock_card.js"></script>
    <script src="../js/spareparts_inventory_in.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.currentBranch = '<?php echo $branch; ?>';
        document.addEventListener('DOMContentLoaded', function() {
            updateWarehouseSummary();
            updateInventorySummary();
            updatePendingTransfers();
            // Refresh every 30 seconds
            setInterval(() => {
                updateWarehouseSummary();
                updateInventorySummary();
                updatePendingTransfers();
            }, 30000);
        });
    </script>

</body>
</html>
