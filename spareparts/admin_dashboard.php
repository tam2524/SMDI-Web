<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['position'], ['Admin', 'Head', 'itsuperadmin', 'Admin Spareparts', 'Spareparts-Admin', 'Spareparts-Owner'])) {
    header('Location: ../login.html');
    exit();
}
$username = $_SESSION['username'];
$userRole = $_SESSION['position'];
$branch = $_SESSION['user_branch'] ?? 'HEADOFFICE';
$isOwner = ($userRole === 'Spareparts-Owner');
$dashTitle = $isOwner ? 'Owner Dashboard' : 'Admin Dashboard';
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

function isVisible($menuId)
{
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
    <title>SMDI - <?php echo htmlspecialchars($dashTitle); ?></title>
    <meta name="description" content="SMDI Spare Parts Management - Admin Dashboard">
    <link rel="icon" href="../assets/img/smdi_logosmall.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --green-900: #003d33;
            --green-800: #004d40;
            --green-700: #00695c;
            --green-600: #00796b;
            --green-400: #26a69a;
            --green-50: #e0f2f1;
            --white: #ffffff;
            --gray-50: #f8fafb;
            --gray-100: #f1f5f4;
            --gray-200: #e2e8e6;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, .08), 0 2px 6px rgba(0, 0, 0, .05);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, .10), 0 4px 10px rgba(0, 0, 0, .06);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            margin: 0;
            padding: 0;
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
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .25);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .nav-brand img {
            height: 34px;
            border-radius: 6px;
        }

        .nav-brand-text {
            font-size: 1rem;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.82rem;
            font-weight: 500;
        }

        .nav-user-avatar {
            width: 34px;
            height: 34px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-logout {
            border: 1.5px solid rgba(255, 255, 255, 0.45);
            color: white;
            background: transparent;
            padding: 5px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: white;
            color: white;
        }

        /* ── HERO HEADER BANNER ── */
        .hero-banner {
            background: linear-gradient(135deg, var(--green-900) 0%, var(--green-700) 60%, var(--green-400) 100%);
            padding: 2.5rem 2rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: 30%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-greeting {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--white);
            margin: 0 0 4px;
            letter-spacing: -0.3px;
        }

        .hero-sub {
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.875rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-sub i {
            font-size: 0.8rem;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            max-width: 1300px;
            margin: -1.5rem auto 3rem;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        /* ── SECTION LABEL ── */
        .section-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--green-700);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }

        /* ── MODULE GRID ── */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .module-card {
            background: var(--white);
            border-radius: 14px;
            padding: 1.6rem 1.4rem;
            text-decoration: none;
            color: inherit;
            border: 1.5px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--green-800), var(--green-400));
            opacity: 0;
            transition: opacity 0.25s;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--green-400);
            color: inherit;
            text-decoration: none;
        }

        .module-card:hover::before {
            opacity: 1;
        }

        .module-icon-wrap {
            width: 52px;
            height: 52px;
            background: var(--green-50);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.25s;
        }

        .module-card:hover .module-icon-wrap {
            background: var(--green-800);
        }

        .module-icon-wrap i {
            font-size: 1.5rem;
            color: var(--green-800);
            transition: color 0.25s;
        }

        .module-card:hover .module-icon-wrap i {
            color: var(--white);
        }

        .module-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--gray-700);
            line-height: 1.3;
        }

        .module-desc {
            font-size: 0.78rem;
            color: var(--gray-500);
            line-height: 1.5;
        }

        .module-arrow {
            margin-top: auto;
            color: var(--green-600);
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
        }

        .module-card:hover .module-arrow {
            opacity: 1;
            transform: translateX(4px);
        }

        /* ── SUMMARY SIDEBAR ── */
        .sidebar-card {
            background: var(--white);
            border-radius: 14px;
            border: 1.5px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .sidebar-header {
            background: var(--green-800);
            color: var(--white);
            padding: 14px 20px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-body {
            padding: 16px 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.82rem;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row-label {
            color: var(--gray-500);
            font-weight: 500;
        }

        .summary-row-val {
            font-weight: 700;
            color: var(--green-800);
        }

        .sidebar-footer {
            background: var(--green-50);
            border-top: 1.5px solid var(--green-400);
            padding: 14px 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.82rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .total-row:last-child {
            margin-bottom: 0;
        }

        .total-row-label {
            color: var(--gray-700);
        }

        .total-row-val {
            color: var(--green-800);
            font-size: 1rem;
            font-weight: 800;
        }

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
        .bg-menu-emerald { background: #059669 !important; }
        .bg-menu-violet { background: #7c3aed !important; }


        .module-card[class*="bg-menu-"] {
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .module-card[class*="bg-menu-"] .module-title {
            color: white !important;
        }

        .module-card[class*="bg-menu-"] .module-desc {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .module-card[class*="bg-menu-"] .module-icon-wrap {
            background: rgba(255, 255, 255, 0.2) !important;
        }

        .module-card[class*="bg-menu-"] .module-icon-wrap i {
            color: white !important;
        }

        .module-card[class*="bg-menu-"] .module-arrow {
            color: white !important;
        }

        .module-card[class*="bg-menu-"]:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
            filter: brightness(1.05);
        }

        /* ── FOOTER STRIP ── */
        .footer-strip {
            text-align: center;
            padding: 1.5rem;
            font-size: 0.75rem;
            color: var(--gray-500);
            border-top: 1px solid var(--gray-200);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .hero-banner {
                padding: 2rem 1rem 3rem;
            }

            .main-content {
                padding: 0 1rem;
            }

            .modules-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- TOP NAVBAR -->
    <nav class="top-nav">
        <a class="nav-brand" href="#">
            <img src="../assets/img/rcsm_logo.jpg" alt="SMDI Logo">
            <span class="nav-brand-text"><?php echo htmlspecialchars($dashTitle); ?></span>
        </a>
        <div class="nav-right">
            <div class="nav-user">
                <div class="nav-user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div>
                    <div style="font-weight:700;color:white;"><?php echo htmlspecialchars(strtoupper($username)); ?>
                    </div>
                    <div style="font-size:0.7rem;opacity:0.7;"><?php echo htmlspecialchars($branch); ?></div>
                </div>
            </div>
            <a href="javascript:void(0)" onclick="openModuleSettings()" class="text-white text-decoration-none me-3"
                title="Module Settings">
                <i class="bi bi-gear-fill fs-5"></i>
            </a>
            <a href="../api/logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <!-- HERO BANNER -->
    <div class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-greeting"><?php echo $greeting; ?></h1>
            <p class="hero-sub"><i class="bi bi-calendar3"></i> <?php echo $today; ?> &nbsp;|&nbsp; <i
                    class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($branch); ?></p>
        </div>
    </div>

    <!-- INCOMING TRANSFER ALERT MODAL -->
    <div class="modal fade" id="incomingTransferAlertModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header bg-danger text-white border-0 py-3">
                    <h5 class="modal-title fw-bold text-white"><i
                            class="bi bi-exclamation-triangle-fill me-2"></i>PENDING INCOMING TRANSFERS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="incoming-alert-modal-body"
                    style="background: #f8f9fa; max-height: 480px; overflow-y: auto;">
                    <!-- Populated via JS -->
                </div>
            </div>
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

                    <?php if (isVisible('sys-dashboard-card')): ?>
                        <a href="admin_spareparts.php?tab=dashboard" class="module-card bg-menu-blue"
                            id="sys-dashboard-card">
                            <div class="module-icon-wrap"><i class="bi bi-speedometer2"></i></div>
                            <div>
                                <div class="module-title">System Dashboard</div>
                                <div class="module-desc">Real-time stats, KPIs &amp; branch overview</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('sales-card')): ?>
                        <a href="admin_spareparts.php?tab=sales" class="module-card bg-menu-purple" id="sales-card">
                            <div class="module-icon-wrap"><i class="bi bi-cart-check-fill"></i></div>
                            <div>
                                <div class="module-title">Sales Transactions</div>
                                <div class="module-desc">View all sales, filters by branch & date</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('inventory-card')): ?>
                        <a href="admin_spareparts.php?tab=inventory" class="module-card bg-menu-orange" id="inventory-card">
                            <div class="module-icon-wrap"><i class="bi bi-database-gear"></i></div>
                            <div>
                                <div class="module-title">Inventory Control</div>
                                <div class="module-desc">Master stock management across all branches</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('transfer-card')): ?>
                        <a href="admin_spareparts.php?tab=global-transfer" class="module-card bg-menu-teal"
                            id="transfer-card">
                            <div class="module-icon-wrap"><i class="bi bi-arrow-left-right"></i></div>
                            <div>
                                <div class="module-title">Transfer Monitor</div>
                                <div class="module-desc">Track global stock transfers &amp; movements</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('payments-mgmt-card')): ?>
                        <a href="admin_spareparts.php?tab=payments" class="module-card bg-menu-indigo"
                            id="payments-mgmt-card">
                            <div class="module-icon-wrap"><i class="bi bi-cash-coin"></i></div>
                            <div>
                                <div class="module-title">Payment Management</div>
                                <div class="module-desc">Review global payment transactions & records</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('audit-card')): ?>
                        <a href="admin_spareparts.php?tab=activity-log" class="module-card bg-menu-emerald" id="audit-card">
                            <div class="module-icon-wrap"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <div class="module-title">Audit Logs</div>
                                <div class="module-desc">Security events &amp; spareparts activity trail</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('usermgmt-card')): ?>
                        <a href="user_management.php" class="module-card bg-menu-cyan" id="usermgmt-card">
                            <div class="module-icon-wrap"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="module-title">User Management</div>
                                <div class="module-desc">Manage accounts, roles &amp; access rights</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('reports-card')): ?>
                        <a href="master_reports.php" class="module-card bg-menu-violet" id="reports-card">
                            <div class="module-icon-wrap"><i class="bi bi-file-earmark-bar-graph"></i></div>
                            <div>
                                <div class="module-title">Master Reports</div>
                                <div class="module-desc">Generate &amp; export system-wide reports</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('customer-mgmt-card')): ?>
                        <a href="sales_spareparts.php?tab=customers" class="module-card bg-menu-indigo"
                            id="customer-mgmt-card">
                            <div class="module-icon-wrap"><i class="bi bi-people"></i></div>
                            <div>
                                <div class="module-title">Customer Records</div>
                                <div class="module-desc">Manage customer ledgers, aging & profiles</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                    <?php if (isVisible('employee-mgmt-card')): ?>
                        <a href="sales_spareparts.php?tab=employees" class="module-card bg-menu-pink"
                            id="employee-mgmt-card">
                            <div class="module-icon-wrap"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <div class="module-title">Employee Records</div>
                                <div class="module-desc">Manage sales force and staff records</div>
                            </div>
                            <div class="module-arrow">Open <i class="bi bi-arrow-right"></i></div>
                        </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-lg-3">
                <div class="section-label"><i class="bi bi-bar-chart"></i> Quick Summary</div>
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <i class="bi bi-layers-half"></i> Global Sales Today
                    </div>
                    <div class="sidebar-body">
                        <div class="summary-row">
                            <span class="summary-row-label">Cash Sales</span>
                            <span class="summary-row-val" id="global-cash-sales-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Charge Sales</span>
                            <span class="summary-row-val" id="global-charge-sales-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label">Charge Sales with PDC</span>
                            <span class="summary-row-val" id="global-charge-pdc-amount">₱0.00</span>
                        </div>
                        <div class="summary-row border-bottom mb-2 pb-2">
                            <span class="summary-row-label fw-bold">Total Cash & Charge</span>
                            <span class="summary-row-val text-primary" id="global-total-sales-amount">₱0.00</span>
                        </div>
                        <div class="summary-row mt-3">
                            <span class="summary-row-label text-success">Cash Payments</span>
                            <span class="summary-row-val text-success" id="global-cash-payments-amount">₱0.00</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-row-label text-danger">Check Dues</span>
                            <span class="summary-row-val text-danger" id="global-check-dues-amount">₱0.00</span>
                        </div>
                    </div>
                </div>

                <div class="section-label mt-4"><i class="bi bi-building"></i> Branch Inventory</div>
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <i class="bi bi-database"></i> Global Stock Summary
                    </div>
                    <div class="sidebar-body" id="inventory-branch-summary-container"
                        style="max-height: 400px; overflow-y: auto;">
                        <div class="text-center py-3 text-muted small">Loading branch stocks...</div>
                    </div>
                    <div class="sidebar-footer">
                        <div class="total-row">
                            <span class="total-row-label">Total Qty</span>
                            <span class="total-row-val" id="global-inv-total-qty">0</span>
                        </div>
                        <div class="total-row">
                            <span class="total-row-label">Total Cost</span>
                            <span class="total-row-val" id="global-inv-total-value">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-strip">
        SMDI Spare Parts Management System &copy; <?php echo date('Y'); ?>
    </div>

    <div class="modal fade" id="moduleSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-gear-fill me-2"></i>System & Menu
                        Settings</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-fill bg-light border-bottom" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-3" id="menu-access-tab" data-bs-toggle="tab"
                                data-bs-target="#menu-access-pane" type="button" role="tab"><i
                                    class="bi bi-ui-checks-grid me-2"></i>Menu Access</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3" id="invoice-seq-tab" data-bs-toggle="tab"
                                data-bs-target="#invoice-seq-pane" type="button" role="tab"
                                onclick="loadInvoiceSequences()"><i class="bi bi-123 me-2"></i>Invoice
                                Sequences</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="settingsTabsContent">

                        <!-- Menu Access Tab Pane -->
                        <div class="tab-pane fade show active p-4" id="menu-access-pane" role="tabpanel" tabindex="0">
                            <!-- Menu Visibility Settings -->
                            <div id="menu-visibility">
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted small text-uppercase ls-1">Select
                                        Position / Role</label>
                                    <select class="form-select form-select-lg border-primary shadow-sm"
                                        id="visibilityPositionSelect" onchange="loadPositionMenus()"
                                        style="border-radius: 8px; font-weight: 600;">
                                        <option value="Spareparts-Admin">Spareparts Admin</option>
                                        <option value="Spareparts-Owner">Spareparts Owner</option>
                                        <option value="Spareparts-Sales">Spareparts Sales</option>
                                        <option value="Spareparts-Warehouse">Spareparts Warehouse</option>
                                    </select>
                                </div>

                                <div class="list-group border shadow-sm rounded-3" id="menus-container"
                                    style="max-height: 400px; overflow-y: auto;">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Sequence Tab Pane -->
                        <div class="tab-pane fade p-4" id="invoice-seq-pane" role="tabpanel" tabindex="0">
                            <div class="alert alert-info py-2 small mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i>Configure the starting Sales Invoice (SI)
                                number and prefix for each branch. The system will automatically resume from the highest
                                existing number if it surpasses this start.
                            </div>
                            <div class="table-responsive">
                                <table
                                    class="table table-hover align-middle border shadow-sm rounded-3 overflow-hidden">
                                    <thead class="table-light text-muted small text-uppercase ls-1">
                                        <tr>
                                            <th>Branch</th>
                                            <th class="text-center" style="width: 15%">Prefix</th>
                                            <th class="text-center" style="width: 20%">Starting #</th>
                                            <th class="text-center">Next Invoice</th>
                                            <th class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-seq-container">
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Loading branches...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal"
                            style="border-radius: 20px;">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="../js/spareparts_dashboard.js"></script>
        <script>
            const POSITION_MENUS = {
                'Spareparts-Admin': [
                    { id: 'sys-dashboard-card', label: 'System Dashboard' },
                    { id: 'sales-card', label: 'Sales Transactions' },
                    { id: 'inventory-card', label: 'Inventory Control' },
                    { id: 'transfer-card', label: 'Transfer Monitor' },
                    { id: 'payments-mgmt-card', label: 'Payment Management' },
                    { id: 'audit-card', label: 'Audit Logs' },
                    { id: 'usermgmt-card', label: 'User Management' },
                    { id: 'reports-card', label: 'Master Reports' },
                    { id: 'customer-mgmt-card', label: 'Customer Records' },
                    { id: 'employee-mgmt-card', label: 'Employee Records' }
                ],
                'Spareparts-Owner': [
                    { id: 'global-dashboard-card', label: 'Global Dashboard' },
                    { id: 'sales-transactions-card', label: 'Sales Transactions' },
                    { id: 'centralized-inventory-card', label: 'Centralized Inventory' },
                    { id: 'global-transfers-card', label: 'Global Transfers' },
                    { id: 'payment-management-card', label: 'Payment Management' },
                    { id: 'system-audit-card', label: 'System Audit' },
                    { id: 'master-reports-card', label: 'Master Reports' },
                    { id: 'find-tool-card', label: 'Find Tool' },
                    { id: 'user-management-card', label: 'User Management' }
                ],
                'Spareparts-Sales': [
                    { id: 'customers', label: 'Customer Records' },
                    { id: 'stock-card', label: 'Stock Card' },
                    { id: 'find-stocks', label: 'Find Stocks' },
                    { id: 'sales', label: 'Sales' },
                    { id: 'payments', label: 'Payments' },
                    { id: 'received-stocks-rr', label: 'Received Stocks (RR/IN)' },
                    { id: 'received-stock', label: 'Received Stock' },
                    { id: 'stock-transfer', label: 'Stock Transfer' },
                    { id: 'cm', label: 'Credit Memo' },
                    { id: 'employees', label: 'Employees' },
                    { id: 'pricelist', label: 'Pricelist' },
                    { id: 'master-reports', label: 'Master Reports' },
                    { id: 'beginning-inventory', label: 'Beginning Inventory' },
                    { id: 'beginning-customer-bal', label: 'Customer Beginning Balance' },
                    { id: 'pdc-payment', label: 'PDC Payment' }
                ],
                'Spareparts-Warehouse': [
                    { id: 'received-stocks-rr-warehouse', label: 'Received Stocks (RR/IN)' },
                    { id: 'stock-card-warehouse', label: 'Stock Card' },
                    { id: 'received-stock-warehouse', label: 'Received Stock' },
                    { id: 'stock-transfer-warehouse', label: 'Stock Transfer' },
                    { id: 'find-stocks-warehouse', label: 'Find Stocks' },
                    { id: 'master-reports-warehouse', label: 'Master Reports' },
                    { id: 'beginning-inventory-warehouse', label: 'Beginning Inventory' },
                    { id: 'barcode-generator', label: 'Barcode Generator' }
                ]
            };

            function openModuleSettings() {
                $.get('../api/spareparts_inventory.php?action=get_module_settings', function (response) {
                    if (response.success) {
                        loadPositionMenus(response.data);
                        new bootstrap.Modal(document.getElementById('moduleSettingsModal')).show();
                    } else {
                        Swal.fire('Error', 'Failed to load settings.', 'error');
                    }
                });
            }

            function loadPositionMenus(currentSettings = null) {
                const pos = $('#visibilityPositionSelect').val();
                const menus = POSITION_MENUS[pos] || [];
                const container = $('#menus-container');
                container.empty();

                if (currentSettings) {
                    renderMenus(menus, currentSettings, pos);
                } else {
                    $.get('../api/spareparts_inventory.php?action=get_module_settings', function (response) {
                        if (response.success) {
                            renderMenus(menus, response.data, pos);
                        }
                    });
                }
            }

            function renderMenus(menus, settings, pos) {
                const container = $('#menus-container');
                menus.forEach(menu => {
                    const settingKey = `menu_vis_${pos}_${menu.id}`;
                    const isVisible = settings[settingKey] !== 'false';

                    const html = `
                    <div class="list-group-item d-flex justify-content-between align-items-center border-0 border-bottom p-3">
                        <div>
                            <div class="fw-bold">${menu.label}</div>
                            <div class="text-muted small">ID: ${menu.id}</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" ${isVisible ? 'checked' : ''} 
                                   onchange="toggleMenuVisibility('${pos}', '${menu.id}', this.checked)">
                        </div>
                    </div>
                `;
                    container.append(html);
                });
            }

            function toggleMenuVisibility(pos, menuId, isVisible) {
                const settingKey = `menu_vis_${pos}_${menuId}`;
                toggleModule(settingKey, isVisible);
            }

            function toggleModule(key, enabled) {
                $.post('../api/spareparts_inventory.php', {
                    action: 'update_module_setting',
                    key: key,
                    value: enabled ? 'true' : 'false'
                }, function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Updated!',
                            text: response.message,
                            icon: 'success',
                            timer: 1000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                });
            }

            // --- Invoice Sequence Settings Logic ---
            function loadInvoiceSequences() {
                const container = $('#invoice-seq-container');
                container.html('<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading branches...</td></tr>');

                $.get('../api/spareparts_inventory.php?action=get_invoice_sequence_settings', function (res) {
                    container.empty();
                    if (res && res.success && res.data.length > 0) {
                        res.data.forEach(item => {
                            const html = `
                            <tr>
                                <td class="fw-bold"><i class="bi bi-shop me-2 text-secondary"></i>${item.branch}</td>
                                <td class="text-center">
                                    <input type="text" class="form-control form-control-sm text-center text-uppercase mx-auto fw-bold" id="seq_prefix_${item.branch}" value="${item.prefix}" style="max-width:80px; letter-spacing: 1px;">
                                </td>
                                <td class="text-center">
                                    <input type="number" class="form-control form-control-sm text-center mx-auto" id="seq_start_${item.branch}" value="${item.start_number}" style="max-width:100px;">
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border shadow-sm fs-6 font-monospace px-3 py-2" style="color: black !important;">${item.next_number}</span>
                                </td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-dark px-3 rounded-pill" onclick="saveInvoiceSequence('${item.branch}')"><i class="bi bi-floppy me-1"></i>Save</button>
                                </td>
                            </tr>
                        `;
                            container.append(html);
                        });
                    } else {
                        container.html('<tr><td colspan="5" class="text-center py-4 text-muted">No branches found or unauthorized.</td></tr>');
                    }
                }, 'json').fail(function () {
                    container.html('<tr><td colspan="5" class="text-center py-4 text-danger">Error loading sequence data.</td></tr>');
                });
            }

            function saveInvoiceSequence(branch) {
                const prefix = $(`#seq_prefix_${branch}`).val().trim();
                const startStr = $(`#seq_start_${branch}`).val();
                const startNum = parseInt(startStr);

                if (!startStr || isNaN(startNum) || startNum < 1) {
                    Swal.fire('Invalid Input', 'Starting number must be a valid positive integer.', 'warning');
                    return;
                }

                $.post('../api/spareparts_inventory.php?action=update_invoice_sequence_start', {
                    branch: branch,
                    prefix: prefix,
                    start_number: startNum
                }, function (res) {
                    if (res.success) {
                        Swal.fire({
                            title: 'Saved!',
                            text: res.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadInvoiceSequences(); // Reload to show updated "Next Invoice" preview
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json');
            }

            document.addEventListener('DOMContentLoaded', function () {
                updateConsolidatedSummary();
                updateInventorySummaryByBranch();
                updatePendingTransfers();
                setInterval(() => {
                    updateConsolidatedSummary();
                    updateInventorySummaryByBranch();
                    updatePendingTransfers();
                }, 30000);
            });
        </script>
</body>

</html>