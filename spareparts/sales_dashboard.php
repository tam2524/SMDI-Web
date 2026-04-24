<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['position'] !== 'Spareparts-Sales' && $_SESSION['position'] !== 'Spareparts-Retail')) {
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
    <title>Sales Dashboard - Spareparts MS</title>
    <meta name="description" content="SMDI Spare Parts Management - Sales Dashboard">
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
            background: var(--green-50); border-top: 2.5px solid var(--green-400);
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
        .bg-menu-pink { background: #ec4899 !important; }


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

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .hero-banner { padding: 2rem 1rem 3rem; }
            .main-content { padding: 0 1rem; }
            .modules-grid { grid-template-columns: 1fr 1fr; }
        }

        /* ── INVENTORY IN MODAL – true fullscreen forced –– */
        #inventoryInModal,
        #inventoryInModal .modal-dialog,
        #inventoryInModal .modal-content {
            width: 100vw !important;
            max-width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }
        #inventoryInModal,
        #inventoryInModal .modal-dialog,
        #inventoryInModal .modal-content {
            height: 100vh !important;
            min-height: 100vh !important;
        }
        #inventoryInModal .modal-content {
            display: flex !important;
            flex-direction: column !important;
        }
        #inventoryInModal .modal-body {
            flex: 1 1 auto !important;
            overflow-y: auto !important;
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
            <span class="nav-brand-text">Sales Dashboard</span>
        </a>
        <div class="nav-right">
            <!-- Notification Bell -->
            <a href="javascript:void(0)" onclick="bootstrap.Modal.getOrCreateInstance(document.getElementById('incomingTransferAlertModal')).show()" class="text-white text-decoration-none me-3 position-relative" title="Incoming Transfers">
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
                <span class="hero-role-badge"><i class="bi bi-receipt"></i> Sales</span>
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

                    <?php if (isVisible('customers')): ?>
                    <a href="sales_spareparts.php?tab=customers" class="module-card bg-menu-gray">
                        <div class="module-icon-wrap"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="module-title">Customer</div>
                            <div class="module-desc">Manage customer ledgers & aging</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('stock-card')): ?>
                    <a href="warehouse_spareparts.php" class="module-card bg-menu-red">
                        <div class="module-icon-wrap"><i class="bi bi-card-list"></i></div>
                        <div>
                            <div class="module-title">Stock Card</div>
                            <div class="module-desc">View your branch inventory &amp; stock levels</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('find-stocks')): ?>
                    <a href="find_stocks.php" class="module-card bg-menu-purple">
                        <div class="module-icon-wrap"><i class="bi bi-search"></i></div>
                        <div>
                            <div class="module-title">Find Stocks</div>
                            <div class="module-desc">Search parts available in other branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('sales')): ?>
                    <a href="sales_spareparts.php?tab=sales" class="module-card bg-menu-orange">
                        <div class="module-icon-wrap"><i class="bi bi-receipt"></i></div>
                        <div>
                            <div class="module-title">Sales</div>
                            <div class="module-desc">Record Charge & Cash sales</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('payments')): ?>
                    <a href="sales_spareparts.php?tab=payments" class="module-card bg-menu-blue">
                        <div class="module-icon-wrap"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="module-title">Payments</div>
                            <div class="module-desc">Record customer payments</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('received-stocks-rr')): ?>
                    <a href="#" class="module-card bg-menu-gray" data-bs-toggle="modal" data-bs-target="#inventoryInModal">
                        <div class="module-icon-wrap"><i class="bi bi-box-arrow-in-down"></i></div>
                        <div>
                            <div class="module-title">Received Stocks (RR/IN)</div>
                            <div class="module-desc">Receive stocks from supplier deliveries</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('received-stock')): ?>
                    <a href="received_stock.php" class="module-card bg-menu-purple">
                        <div class="module-icon-wrap"><i class="bi bi-journal-check"></i></div>
                        <div>
                            <div class="module-title">Received Stock</div>
                            <div class="module-desc">Stocks received from other branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('stock-transfer')): ?>
                    <a href="transfer_stock.php" class="module-card bg-menu-teal">
                        <div class="module-icon-wrap"><i class="bi bi-truck"></i></div>
                        <div>
                            <div class="module-title">Stock Transfer</div>
                            <div class="module-desc">Move stock between branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('cm')): ?>
                    <a href="sales_spareparts.php?tab=returns" class="module-card bg-menu-cyan">
                        <div class="module-icon-wrap"><i class="bi bi-arrow-return-left"></i></div>
                        <div>
                            <div class="module-title">CM</div>
                            <div class="module-desc">Credit Memo / Returned products</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('employees')): ?>
                    <a href="sales_spareparts.php?tab=employees" class="module-card bg-menu-pink">
                        <div class="module-icon-wrap"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <div class="module-title">Employees</div>
                            <div class="module-desc">Manage branch sales force</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('pricelist')): ?>
                    <a href="sales_spareparts.php?tab=pricelists" class="module-card bg-menu-green">
                        <div class="module-icon-wrap"><i class="bi bi-tags"></i></div>
                        <div>
                            <div class="module-title">Pricelist</div>
                            <div class="module-desc">Set prices per customer rank</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('master-reports')): ?>
                    <a href="master_reports.php" class="module-card bg-menu-indigo">
                        <div class="module-icon-wrap"><i class="bi bi-file-earmark-bar-graph"></i></div>
                        <div>
                            <div class="module-title">Master Reports</div>
                            <div class="module-desc">View sales & inventory reports</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('beginning-inventory')): ?>
                    <a href="beginning_inventory.php" class="module-card bg-menu-red" id="beginning-inventory-card">
                        <div class="module-icon-wrap"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
                        <div>
                            <div class="module-title">Beginning Inventory</div>
                            <div class="module-desc">Enter initial stock levels (Excel-style)</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('beginning-customer-bal')): ?>
                    <a href="beginning_customer_balance.php" class="module-card bg-menu-green" id="beginning-customer-bal-card">
                        <div class="module-icon-wrap"><i class="bi bi-person-fill-add"></i></div>
                        <div>
                            <div class="module-title">Customer Beginning Balance</div>
                            <div class="module-desc">Enter initial customer balances (Excel-style)</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php endif; ?>

                    <?php if (isVisible('pdc-payment')): ?>
                    <a href="sales_pdc_payments.php" class="module-card bg-menu-indigo">
                        <div class="module-icon-wrap"><i class="bi bi-calendar-check"></i></div>
                        <div>
                            <div class="module-title">PDC Payment</div>
                            <div class="module-desc">Record & manage post-dated check payments</div>
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
                    <div class="sidebar-header d-flex justify-content-between align-items-center">
                        <div><i class="bi bi-graph-up-arrow"></i> Today's Transactions</div>
                        <small class="badge bg-white text-success bg-opacity-75"><?php echo htmlspecialchars($branch); ?></small>
                    </div>
                    <div class="sidebar-body">
                        <div class="summary-row">
                            <span class="summary-row-label">Cash Sales</span>
                            <span class="summary-row-val" id="cash-sales-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Charge Sales</span>
                            <span class="summary-row-val" id="charge-sales-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Charge Sales with PDC</span>
                            <span class="summary-row-val" id="charge-pdc-amount">₱0.00</span>
                        </div>
                        <div class="summary-row border-bottom border-secondary mb-2 pb-2">
                            <span class="summary-row-label fw-bold">Total Cash & Charge</span>
                            <span class="summary-row-val text-primary" id="total-sales-amount">₱0.00</span>
                        </div>
                        
                        <div class="summary-row mt-3">
                            <span class="summary-row-label text-success">Cash Payments</span>
                            <span class="summary-row-val text-success" id="cash-payments-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label text-danger">Check Dues</span>
                            <span class="summary-row-val text-danger" id="check-dues-amount">₱0.00</span>
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

    <!-- INVENTORY IN MODAL -->
    <div class="modal fade" id="inventoryInModal" tabindex="-1" aria-hidden="true" style="padding:0!important;">
        <div class="modal-dialog" style="width:100vw;max-width:100vw;height:100vh;min-height:100vh;margin:0;padding:0;">
            <div class="modal-content border-0" style="width:100%;height:100vh;min-height:100vh;border-radius:0;display:flex;flex-direction:column;overflow:hidden;">
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
                                        <div class="col-md-4">
                                            <label class="small fw-bold text-muted">Invoice / DR # *</label>
                                            <input type="text" id="invoiceNo" class="form-control fw-bold border-primary" placeholder="Required" required>
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
                            <div class="card border-0 shadow-sm rounded-4 mb-4" style="padding: 30px; border-top: 5px solid #26a69a !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold text-muted text-uppercase small"><i class="bi bi-upc-scan me-2"></i>Step 2: Scan / Search Part Number</h6>
                                </div>

                                <div class="position-relative mb-3">
                                    <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #004d40; font-size: 1.2rem; z-index: 5;"></i>
                                    <input type="text" id="partSearchInput" class="form-control" style="padding-left: 45px; height: 50px; border-radius: 8px; border: 2px solid #eee;" placeholder="Type part number, name, or scan..." autocomplete="off">
                                    <div id="searchDropdown" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); z-index: 1050; max-height: 250px; overflow-y: auto; display: none;"></div>
                                </div>

                                <div id="partDisplay" class="mb-3 shadow-sm p-4" style="display:none; background: #fff; border-radius: 10px; border-left: 4px solid #26a69a;">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 id="partTitle" class="fw-bold mb-1">PART NAME</h5>
                                            <p id="partNoDisplay" class="text-muted small mb-1">PART-NO</p>
                                            <div id="compatibilityList"></div>
                                        </div>
                                        <span id="newPartBadge" class="badge bg-warning text-white d-none" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 20px;">NEW PART</span>
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
                                            <label class="small text-muted fw-bold d-block">Cost <span id="costHistoryLink" class="text-decoration-underline text-info small d-none" style="cursor:pointer">(history)</span></label>
                                            <span id="prevCostDisplay" class="fw-bold text-black fs-5">₱0.00</span>
                                            <div id="costChangeIndicator" class="small mt-1"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small text-muted fw-bold">Qty *</label>
                                            <input type="number" id="scannedQty" class="form-control fw-bold border-primary" value="1" min="1">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="small text-muted fw-bold">New Cost</label>
                                            <input type="number" id="scannedCost" step="0.01" class="form-control fw-bold" placeholder="0.00" min="0">
                                        </div>
                                        <div class="col-md-3">
                                            <button id="addScannedPart" class="btn btn-primary w-100" style="background:#004d40; border:none; padding:12px; font-weight:600;"><i class="bi bi-plus-lg me-1"></i> ADD</button>
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
                                    <div id="brandSummaryContainer"></div>
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

    <div class="footer-strip">
        SMDI Spare Parts Management System &copy; <?php echo date('Y'); ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/spareparts_dashboard.js"></script>
    <script src="../js/spareparts_inventory_in.js"></script>
    <script src="../js/spareparts_stock_card.js"></script>
    <script>
        window.currentBranch = '<?php echo $branch; ?>';
        document.addEventListener('DOMContentLoaded', function() {
            updateSalesSummary();
            updateInventorySummary();
            updatePendingTransfers();
            setInterval(() => {
                updateSalesSummary();
                updateInventorySummary();
                updatePendingTransfers();
            }, 30000);
        });
    </script>
</body>
</html>
