<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['position'] !== 'Spareparts-Sales') {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$greeting = 'Welcome back, ' . htmlspecialchars($username) . '!';
$today = date('l, F j, Y');

// Check if module is enabled
require_once '../api/db_config.php';
$checkSetting = $conn->query("SELECT setting_value FROM spareparts_settings WHERE setting_key = 'beginning_inventory_enabled'");
$beginningInvEnabled = true;
if ($checkSetting && $row = $checkSetting->fetch_assoc()) {
    $beginningInvEnabled = ($row['setting_value'] === 'true');
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

                    <a href="sales_spareparts.php?tab=customers" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="module-title">Customer</div>
                            <div class="module-desc">Manage customer ledgers & aging</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="warehouse_spareparts.php" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-card-list"></i></div>
                        <div>
                            <div class="module-title">Stock Card</div>
                            <div class="module-desc">View your branch inventory &amp; stock levels</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="find_stocks.php" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-search"></i></div>
                        <div>
                            <div class="module-title">Find Stocks</div>
                            <div class="module-desc">Search parts available in other branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="sales_spareparts.php?tab=sales" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-receipt"></i></div>
                        <div>
                            <div class="module-title">Sales</div>
                            <div class="module-desc">Record Charge & Cash sales</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>


                    <a href="sales_spareparts.php?tab=payments" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="module-title">Payments</div>
                            <div class="module-desc">Record customer payments</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="transfer_stock.php" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-truck"></i></div>
                        <div>
                            <div class="module-title">Transfer Stocks</div>
                            <div class="module-desc">Move stock between branches</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="sales_spareparts.php?tab=returns" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-arrow-return-left"></i></div>
                        <div>
                            <div class="module-title">CM</div>
                            <div class="module-desc">Credit Memo / Returned products</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="sales_spareparts.php?tab=employees" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <div class="module-title">Employees</div>
                            <div class="module-desc">Manage branch sales force</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="sales_spareparts.php?tab=pricelists" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-tags"></i></div>
                        <div>
                            <div class="module-title">Pricelist</div>
                            <div class="module-desc">Set prices per customer rank</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <a href="master_reports.php" class="module-card">
                        <div class="module-icon-wrap"><i class="bi bi-file-earmark-bar-graph"></i></div>
                        <div>
                            <div class="module-title">Master Reports</div>
                            <div class="module-desc">View sales & inventory reports</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>

                    <?php if ($beginningInvEnabled): ?>
                    <a href="beginning_inventory.php" class="module-card" id="beginning-inventory-card">
                        <div class="module-icon-wrap"><i class="bi bi-file-earmark-spreadsheet-fill text-primary"></i></div>
                        <div>
                            <div class="module-title">Beginning Inventory</div>
                            <div class="module-desc">Enter initial stock levels (Excel-style)</div>
                        </div>
                        <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                    </a>
                    <?php
endif; ?>

                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-lg-3">
                <div class="section-label"><i class="bi bi-bar-chart"></i> Today's Summary</div>
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <i class="bi bi-graph-up-arrow"></i> Sales Summary
                    </div>
                    <div class="sidebar-body">
                        <div class="summary-row">
                            <span class="summary-row-label">Charge Sales Qty</span>
                            <span class="summary-row-val" id="charge-qty">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Charge Amount</span>
                            <span class="summary-row-val" id="charge-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Cash Sales Qty</span>
                            <span class="summary-row-val" id="cash-qty">0</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Cash Amount</span>
                            <span class="summary-row-val" id="cash-amount">₱0.00</span>
                        </div>
                    </div>
                    <div class="sidebar-footer">
                        <div class="total-row">
                            <span class="total-row-label">Total Sales</span>
                            <span class="total-row-val" id="total-amount">₱0.00</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/spareparts_dashboard.js"></script>
    <script>
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
