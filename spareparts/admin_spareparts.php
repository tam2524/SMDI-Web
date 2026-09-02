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

$dashboardTitle = ($userRole === 'Spareparts-Owner') ? 'OWNER SPAREPARTS INVENTORY' : 'ADMIN SPAREPARTS INVENTORY';
$backLink = ($userRole === 'Spareparts-Owner') ? 'owner_dashboard.php' : 'admin_dashboard.php';

// Determine active tab from URL parameter
$validTabs = ['dashboard', 'inventory', 'sales', 'payments', 'global-transfer', 'activity-log', 'reports'];
$activeTab = isset($_GET['tab']) && in_array($_GET['tab'], $validTabs) ? $_GET['tab'] : null;
$showTabs = ($activeTab === null); // show tab UI only when no ?tab= param
if ($activeTab === null)
    $activeTab = 'dashboard'; // default for logic below

// Map tab param -> tab button id, pane id, and module label
$tabMap = [
    'dashboard' => ['btn' => 'dashboard-tab', 'pane' => 'dashboard', 'label' => 'System Dashboard'],
    'inventory' => ['btn' => 'inventory-tab', 'pane' => 'inventory', 'label' => 'Inventory Control'],
    'sales' => ['btn' => 'sales-tab', 'pane' => 'sales', 'label' => 'Sales Transactions'],
    'payments' => ['btn' => 'payments-tab', 'pane' => 'payments', 'label' => 'Payment Management'],
    'global-transfer' => ['btn' => 'global-transfer-tab', 'pane' => 'global-transfer', 'label' => 'Transfer Monitor'],
    'activity-log' => ['btn' => 'activity-log-tab', 'pane' => 'activityLog', 'label' => 'Audit Logs'],
    'reports' => ['btn' => 'reports-tab', 'pane' => 'reports', 'label' => 'Master Reports'],
];
$activeBtn = $tabMap[$activeTab]['btn'];
$activePane = $tabMap[$activeTab]['pane'];
$moduleLabel = $tabMap[$activeTab]['label'];

// Page title: module name when coming from dashboard, generic otherwise
$pageTitle = $showTabs
    ? (($userRole === 'Spareparts-Owner') ? 'Owner Spare Parts Inventory Management' : 'Admin Spare Parts Inventory Management')
    : $moduleLabel;

function isActiveTab($tabParam, $activeTab)
{
    return $tabParam === $activeTab ? 'active' : '';
}
?>
<script>
    window.canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;
    window.userRole = "<?php echo $userRole; ?>";
    window.currentBranch = "<?php echo $currentBranch; ?>";
</script>
<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='utf-8'>
    <title>SMDI - <?php echo $dashboardTitle; ?></title>
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

        @media print {
            @page {
                size: landscape;
                margin: 0.5cm;
            }

            /* HIDE EVERYTHING BY DEFAULT */
            body>*:not(#inventoryPreviewModal),
            .sidebar,
            .navbar,
            .modal-header,
            .modal-footer,
            .no-print,
            .btn,
            .btn-close,
            main,
            footer {
                display: none !important;
            }

            /* SHOW ONLY THE MODAL AND ITS CONTENT */
            #inventoryPreviewModal {
                display: block !important;
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                visibility: visible !important;
                background: white !important;
            }

            #inventoryPreviewModal *,
            .print-only,
            .print-only * {
                visibility: visible !important;
                font-family: Calibri, 'Segoe UI', Arial, sans-serif !important;
                font-size: 10pt !important;
            }

            .modal-dialog,
            .modal-content,
            .modal-body {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .print-only {
                display: block !important;
            }

            table {
                width: 100% !important;
                table-layout: auto !important;
                border-collapse: collapse !important;
                margin-top: 10px !important;
            }

            th,
            td {
                border: 1px solid #333 !important;
                padding: 4px !important;
                word-wrap: break-word !important;
                vertical-align: middle !important;
                font-size: 10pt !important;
            }

            /* Force narrow widths for numeric columns */
            th.text-center,
            td.text-center {
                width: 40px !important;
            }

            th.text-end,
            td.text-end {
                width: 80px !important;
            }

            .table-responsive {
                overflow: visible !important;
                max-height: none !important;
            }
        }

        /* Sales Module Specific Styling */
        #sales .table thead th {
            background-color: #343a40;
            color: white;
            border-bottom: none;
        }

        #salesSubTabs .nav-link {
            transition: all 0.3s ease;
        }

        #salesSubTabs .nav-link.active {
            background-color: #343a40 !important;
            color: white !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #salesTable tbody tr:hover {
            background-color: rgba(52, 58, 64, 0.05);
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

        #inventoryPreviewModal .modal-body {
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            font-size: 12pt;
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
            background: var(--smdi-green);
            color: white;
            border-color: var(--smdi-green);
        }

        .global-transfer-col-header {
            background: var(--smdi-green) !important;
            color: white !important;
            border-bottom: 3px solid var(--smdi-green-dark) !important;
        }

        .global-transfer-col-header .badge {
            background: white !important;
            color: var(--smdi-green) !important;
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

        /* ─── Inventory Quick-Filter Buttons ─── */
        .inv-filter-btn {
            background: #fff;
            color: #555;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 5px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all .18s;
            cursor: pointer;
        }
        .inv-filter-btn:hover, .inv-filter-btn.active {
            background: var(--smdi-green);
            color: #fff !important;
            border-color: var(--smdi-green) !important;
        }
        .inv-filter-btn.active .bi { color: #fff !important; }

    </style>
    <script>window.canDelete = <?php echo $canDelete ? 'true' : 'false'; ?>;</script>
</head>

<body>
    <nav class="navbar navbar-custom sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center">
                <?php if ($showTabs): ?>
                <a href="<?php echo $backLink; ?>" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <?php
endif; ?>
                <span class="navbar-brand">
                    <?php echo $dashboardTitle; ?>
                </span>
            </div>
            <div class="user-info">
                <?php if (isset($_SESSION['username'])): ?>
                    <span><i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($currentBranch); ?></span>
                <?php
endif; ?>
                <a href="../api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <main class='container-fluid py-4'>
        <div class='card shadow-sm mb-4'>
            <div class='card-header bg-white d-flex align-items-center gap-3'>
                <?php if (!$showTabs): ?>
                <a href="<?php echo $backLink; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                </a>
                <?php
endif; ?>
                <h1 class='h5 mb-0'><?php echo htmlspecialchars($pageTitle); ?></h1>
            </div>
            <div class='card-body p-4'>
                <?php if ($showTabs): ?>
                <ul class='nav nav-tabs mb-4' id='mainTabs' role='tablist'>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("activity-log", $activeTab); ?>' id='activity-log-tab'
                            data-bs-toggle='tab' data-bs-target='#activityLog' type='button'><i
                                class="bi bi-clock-history me-2"></i>Audit Logs</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("dashboard", $activeTab); ?>' id='dashboard-tab'
                            data-bs-toggle='tab' data-bs-target='#dashboard' type='button'><i
                                class="bi bi-bar-chart-line-fill me-2"></i>Dashboard</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("inventory", $activeTab); ?>' id='inventory-tab'
                            data-bs-toggle='tab' data-bs-target='#inventory' type='button'><i
                                class="bi bi-box-seam me-2"></i>Inventory</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("sales", $activeTab); ?>' id='sales-tab'
                            data-bs-toggle='tab' data-bs-target='#sales' type='button'><i
                                class="bi bi-cash-stack me-2"></i>Sales</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("payments", $activeTab); ?>' id='payments-tab'
                            data-bs-toggle='tab' data-bs-target='#payments' type='button'><i
                                class="bi bi-credit-card me-2"></i>Payments</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("employees", $activeTab); ?>' id='employees-tab'
                            data-bs-toggle='tab' data-bs-target='#employees' type='button'><i
                                class="bi bi-person-badge me-2"></i>Employees</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("global-transfer", $activeTab); ?>' id='global-transfer-tab'
                            data-bs-toggle='tab' data-bs-target='#global-transfer' type='button'><i
                                class="bi bi-globe me-2"></i>Transfer Monitor</button></li>
                    <li class='nav-item' role='presentation'><button class='nav-link <?php echo isActiveTab("reports", $activeTab); ?>' id='reports-tab'
                            data-bs-toggle='tab' data-bs-target='#reports' type='button'><i
                                class="bi bi-file-earmark-bar-graph me-2"></i>Master Reports</button></li>
                </ul>
                <?php
endif; ?>

                <div class='tab-content' id='mainTabContent'>
                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "activityLog" ? "show active" : "") : ($activePane === "activityLog" ? "" : "d-none"); ?>' id='activityLog' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-3'>
                            <h4 class='mb-0'>System Activity Log</h4>
                            <div class='d-flex gap-2'>
                                <input type='text' id='activityLogSearch' class='form-control' style='width: 250px;'
                                    placeholder='Search logs...'>
                                <button class='btn btn-primary text-white' id='activityLogSearchBtn'><i
                                        class="bi bi-search me-1"></i>Search</button>
                            </div>
                        </div>
                        <div class='table-responsive'>
                            <table class='table table-hover' id='activityLogTable'>
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Username</th>
                                        <th>Action</th>
                                        <th class='text-center'>ID</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody id='activityLogTableBody'></tbody>
                            </table>
                        </div>
                        <nav aria-label='Activity Log Pagination'>
                            <ul class='pagination justify-content-center mt-3' id='activityLogPagination'></ul>
                        </nav>
                    </div>

                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "dashboard" ? "show active" : "") : ($activePane === "dashboard" ? "" : "d-none"); ?>' id='dashboard' role='tabpanel'>
                        <h4 class="mb-4">Branch Statistics Overview</h4>
                        <div id="dashboard-loader" class="text-center p-5">
                            <div class="spinner-border text-primary" role="status"><span
                                    class="visually-hidden">Loading...</span></div>
                        </div>
                        <div id="dashboard-content" class="d-none">
                            <div class='row g-4'>
                                <div class='col-lg-2 col-md-4'>
                                    <div class='card stat-card h-100 border-start border-4 border-primary'>
                                        <div class='card-body'>
                                            <h6 class='card-title text-muted'>Total Qty of Inventory</h6>
                                            <h4 class='card-text fw-bold' id='stat-total-qty'>-</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-lg-2 col-md-4'>
                                    <div class='card stat-card h-100 border-start border-4 border-info'>
                                        <div class='card-body'>
                                            <h6 class='card-title text-muted'>Total Inventory Value</h6>
                                            <h4 class='card-text fw-bold' id='stat-inventory-value'>-</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-lg-2 col-md-4'>
                                    <div class='card stat-card h-100 border-start border-4 border-success'>
                                        <div class='card-body'>
                                            <h6 class='card-title text-muted'>Sales (This Month)</h6>
                                            <h4 class='card-text fw-bold' id='stat-monthly-sales'>-</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-lg-2 col-md-4'>
                                    <div class='card stat-card h-100 border-start border-4 border-secondary'>
                                        <div class='card-body'>
                                            <h6 class='card-title text-muted'>Sales (This Year)</h6>
                                            <h4 class='card-text fw-bold' id='stat-yearly-sales'>-</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-lg-2 col-md-4'>
                                    <div class='card stat-card h-100 border-start border-4 border-danger'>
                                        <div class='card-body'>
                                            <h6 class='card-title text-muted'>Outstanding Balance</h6>
                                            <h4 class='card-text fw-bold' id='stat-outstanding-balance'>-</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class='col-lg-2 col-md-4'>
                                    <div class='card stat-card h-100 border-start border-4 border-warning'>
                                        <div class='card-body'>
                                            <h6 class='card-title text-muted'>Total Accounts</h6>
                                            <h4 class='card-text fw-bold' id='stat-total-accounts'>-</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "inventory" ? "show active" : "") : ($activePane === "inventory" ? "" : "d-none"); ?>' id='inventory' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
                            <h4 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Inventory Management</h4>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <div id="inventoryStats" class="small text-muted fw-bold border-end pe-3"></div>
                                <div class="btn-group shadow-sm">
                                    <button class='btn btn-sm btn-primary text-white fw-bold px-3'
                                        data-bs-toggle='modal' data-bs-target='#addPartModal'>
                                        <i class='bi bi-plus-circle me-1'></i>Add New Part
                                    </button>
                                    <button class='btn btn-sm btn-outline-success fw-bold px-3'
                                        data-bs-toggle='modal' data-bs-target='#bulkPriceUploadModal'>
                                        <i class='bi bi-file-earmark-excel me-1'></i>Bulk Price Update
                                    </button>
                                </div>
                                <select id="inventoryBranchFilter" class="form-select form-select-sm shadow-sm" style="width: 170px;">
                                    <option value="all">All Branches</option>
                                </select>
                                <div class="input-group input-group-sm shadow-sm" style="width: 220px;">
                                    <span class="input-group-text bg-white border-end-0 pe-1"><i class="bi bi-search text-muted"></i></span>
                                    <input type='text' id='inventorySearch' class='form-control border-start-0 ps-1'
                                        placeholder='Search parts...' autocomplete="off">
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted small text-nowrap">Show:</span>
                                    <select id="inventoryPageSize" class="form-select form-select-sm shadow-sm" style="width: 80px;">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="9999">All</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Removed Batch Action Bar as checkboxes are removed in Admin -->

                        <!-- Quick-Filter Bar -->
                        <div class="d-flex flex-wrap align-items-center gap-2 px-1 py-2 border-bottom bg-white mb-2">
                            <span class="text-muted small fw-semibold me-1">
                                <i class="bi bi-funnel me-1"></i>Quick Filters:
                            </span>
                            <button class="inv-filter-btn" data-filter="low_price"
                                    title="Show parts with cost ≤ ₱5.00">
                                <i class="bi bi-currency-exchange me-1 text-warning"></i>Cost ≤ ₱5
                            </button>
                            <button class="inv-filter-btn" data-filter="low_stock"
                                    title="Show parts at or below minimum stock">
                                <i class="bi bi-graph-down-arrow me-1 text-danger"></i>Low Stocks
                            </button>
                        </div>

                        <div class='table-responsive'>
                            <table class='table table-hover' id='inventoryTable'>
                                <thead>
                                    <tr>
                                        <!-- Removed checkbox headers -->
                                        <th>Part No / Brand</th>
                                        <th>Part Name</th>
                                        <th class="text-center">Branch</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-end">Cost</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total Value</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='inventoryTableBody'></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-white rounded-bottom shadow-sm">
                            <div class="text-muted small fw-semibold" id="inventoryPageInfo"></div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0 gap-1" id="inventoryPagination"></ul>
                            </nav>
                        </div>
                    </div>

                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "sales" ? "show active" : "") : ($activePane === "sales" ? "" : "d-none"); ?>' id='sales' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <h4 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Sales Transactions</h4>
                            <div class="d-flex gap-3 align-items-center">
                                <div id="salesStats" class="small text-muted fw-bold border-end pe-3"></div>
                                <div class="btn-group shadow-sm">
                                    <button class='btn btn-sm btn-success fw-bold px-3' data-bs-toggle='modal'
                                        data-bs-target='#sellPartsOutModal'>
                                        <i class='bi bi-cart-plus me-1'></i>Record Sale
                                    </button>
                                </div>
                                <input type='text' id='salesSearch'
                                    class='form-control form-control-sm border shadow-sm' style="width: 250px;"
                                    placeholder='Search customer or OR...' autocomplete="off">
                            </div>
                        </div>

                        <!-- SALES SUB-TABS -->
                        <div class="mb-4">
                            <ul class="nav nav-pills bg-light p-1 rounded shadow-sm d-inline-flex" id="salesSubTabs"
                                role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active py-2 px-4 fw-bold" id="all-sales-tab"
                                        data-bs-toggle="pill" data-type="all" type="button">All
                                        Transactions</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-4 fw-bold" id="cash-sales-tab" data-bs-toggle="pill"
                                        data-type="cash" type="button">Cash Sales</button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link py-2 px-4 fw-bold" id="charge-sales-tab"
                                        data-bs-toggle="pill" data-type="charge" type="button">Charge</button>
                                </li>
                            </ul>
                        </div>

                        <!-- SALES FILTER BAR -->
                        <div class="card mb-4 border-0 shadow-sm bg-white border-start border-4 border-primary">
                            <div class="card-body p-3">
                                <div class="row g-3 align-items-end">
                                    <?php if ($userRole === 'Spareparts-Owner' || $userRole === 'Spareparts-Admin' || $userRole === 'Admin' || $userRole === 'Head'): ?>
                                    <div class="col-md-3">
                                        <label class="small fw-bold text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>Filter by Branch</label>
                                        <select id="salesFilterBranch" class="form-select form-select-sm shadow-sm">
                                            <option value="all">All Branches</option>
                                        </select>
                                    </div>
                                    <?php
endif; ?>
                                    <div class="col-md-5">
                                        <label class="small fw-bold text-muted mb-1"><i class="bi bi-calendar-range me-1"></i>Date Range</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border shadow-sm">From</span>
                                            <input type="date" id="salesFilterDateFrom" class="form-control shadow-sm">
                                            <span class="input-group-text bg-light border shadow-sm">To</span>
                                            <input type="date" id="salesFilterDateTo" class="form-control shadow-sm">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button id="applySalesFiltersBtn" class="btn btn-sm btn-primary w-100 shadow-sm fw-bold text-white">
                                            <i class="bi bi-filter me-1"></i>Apply Filters
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button id="resetSalesFiltersBtn" class="btn btn-sm btn-outline-primary w-100 shadow-sm fw-bold text-white">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class='table-responsive'>
                            <table class='table table-hover align-middle' id='salesTable'>
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Customer</th>
                                        <th>OR #</th>
                                        <th>Sales Force</th>
                                        <th class="text-end">Total Amount</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-end col-balance">Balance</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='salesTableBody'></tbody>
                            </table>
                        </div>
                    </div>

                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "payments" ? "show active" : "") : ($activePane === "payments" ? "" : "d-none"); ?>' id='payments' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <h4 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i>Payment Management</h4>
                            <div class="d-flex gap-3 align-items-center">
                                <div class="btn-group shadow-sm">
                                    <button class='btn btn-sm btn-primary text-white fw-bold px-3'
                                        data-bs-toggle='modal' data-bs-target='#recordPaymentModal'>
                                        <i class='bi bi-cash-coin me-1'></i>Record Payment
                                    </button>
                                </div>
                                <input type='text' id='paymentsSearch' class='form-control form-control-sm'
                                    style="width: 250px;" placeholder='Search by customer or OR...' autocomplete="off">
                            </div>
                        </div>
                        <div class='table-responsive rounded shadow-sm bg-white'>
                            <table class='table table-hover align-middle mb-0' id='paymentsTable'>
                                <thead class="bg-light border-bottom border-2">
                                    <tr>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Customer</th>
                                        <th class="text-end">Amount Paid</th>
                                        <th class="text-center">OR # / REF</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='paymentsTableBody'></tbody>
                            </table>
                        </div>
                    </div>

                    <div class='tab-pane fade <?php echo($activePane === "global-transfer") ? "show active" : "d-none"; ?>' id='global-transfer' role='tabpanel'>
                        <div class='d-flex justify-content-between align-items-center mb-4'>
                            <h4 class='mb-0 text-primary fw-bold'>GLOBAL TRANSFERS OVERVIEW</h4>
                            <div class="d-flex gap-2 align-items-center">
                                <select id="globalTransfersBranchFilter" class="form-select shadow-sm" style="width: 180px;">
                                    <option value="">All Branches</option>
                                </select>
                                <input type="date" id="globalTransfersDateFilter" class="form-control shadow-sm" style="width: 150px;">
                                <div class="input-group" style="width: 250px;">
                                    <input type="text" id="globalTransfersSearch" class="form-control shadow-sm"
                                        placeholder="Search REF, Branch, Part...">
                                    <button class="btn btn-primary shadow-sm" id="globalTransfersSearchBtn"><i class="bi bi-search text-white"></i></button>
                                </div>
                                <button class="btn btn-outline-secondary shadow-sm" id="resetGlobalTransferFilters" title="Reset Filters">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button class='btn btn-outline-primary shadow-sm border-2 fw-bold'
                                    id="refreshGlobalTransfersBtn">
                                    <i class='bi bi-arrow-clockwise me-2'></i>REFRESH
                                </button>
                            </div>
                        </div>

                        <div class='row g-4'>
                            <!-- In-Transit Column -->
                            <div class='col-lg-4'>
                                <div class='card border-0 shadow-sm bg-light h-100' style="min-height: 600px;">
                                    <div
                                        class='card-header global-transfer-col-header text-white d-flex justify-content-between align-items-center border-0 py-3 rounded-top'>
                                        <h6 class='mb-0 fw-bold text-white'><i class="bi bi-truck me-2"></i>IN-TRANSIT</h6>
                                        <span class='badge rounded-pill px-3' id='count-in-transit'>0</span>
                                    </div>
                                    <div class='card-body p-3' id='col-in-transit'
                                        style="max-height: 75vh; overflow-y: auto;">
                                        <div class="text-center text-muted p-5 mt-4">
                                            <div class="spinner-border spinner-border-sm text-warning mb-2"></div>
                                            <p class="small">Loading transfers...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed Column -->
                            <div class='col-lg-4'>
                                <div class='card border-0 shadow-sm bg-light h-100' style="min-height: 600px;">
                                    <div
                                        class='card-header global-transfer-col-header text-white d-flex justify-content-between align-items-center border-0 py-3 rounded-top'>
                                        <h6 class='mb-0 fw-bold text-white'><i class="bi bi-check-circle me-2"></i>COMPLETED
                                        </h6>
                                        <span class='badge rounded-pill px-3' id='count-completed'>0</span>
                                    </div>
                                    <div class='card-body p-3' id='col-completed'
                                        style="max-height: 75vh; overflow-y: auto;">
                                        <div class="text-center text-muted p-5 mt-4">
                                            <div class="spinner-border spinner-border-sm text-success mb-2"></div>
                                            <p class="small">Loading transfers...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejected Column -->
                            <div class='col-lg-4'>
                                <div class='card border-0 shadow-sm bg-light h-100' style="min-height: 600px;">
                                    <div
                                        class='card-header global-transfer-col-header text-white d-flex justify-content-between align-items-center border-0 py-3 rounded-top'>
                                        <h6 class='mb-0 fw-bold text-white'><i class="bi bi-x-circle me-2"></i>REJECTED</h6>
                                        <span class='badge rounded-pill px-3' id='count-rejected'>0</span>
                                    </div>
                                    <div class='card-body p-3' id='col-rejected'
                                        style="max-height: 75vh; overflow-y: auto;">
                                        <div class="text-center text-muted p-5 mt-4">
                                            <div class="spinner-border spinner-border-sm text-danger mb-2"></div>
                                            <p class="small">Loading transfers...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "employees" ? "show active" : "") : ($activePane === "employees" ? "" : "d-none"); ?>' id='employees' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <h4 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2"></i>Employee Management</h4>
                            <div class="d-flex gap-3 align-items-center">
                                <button class="btn btn-sm btn-success fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#manageSalesForceModal">
                                    <i class='bi bi-person-plus me-1'></i>Manage / Add Employees
                                </button>
                                <input type='text' id='employeesSearch' class='form-control form-control-sm shadow-sm'
                                    style="width: 250px;" placeholder='Search employees...' autocomplete="off">
                            </div>
                        </div>
                        <div class='table-responsive rounded shadow-sm bg-white'>
                            <table class='table table-hover align-middle mb-0' id='employeesTable'>
                                <thead class="bg-light border-bottom border-2">
                                    <tr class="small text-uppercase text-muted fw-bold">
                                        <th>Employee Name & Position</th>
                                        <th>Assigned Branch</th>
                                        <th>Sales Performance</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id='employeesTableBody'></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 p-2">
                            <div class="text-muted small fw-semibold" id="employeesPageInfo"></div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0 gap-1 shadow-sm" id="employeesPagination"></ul>
                            </nav>
                        </div>
                    </div>

                    <!-- ===================== MASTER REPORTS TAB ===================== -->
                    <div class='<?php echo $showTabs ? "tab-pane fade " . ($activePane === "reports" ? "show active" : "") : ($activePane === "reports" ? "" : "d-none"); ?>' id='reports' role='tabpanel'>
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-bar-graph me-2"></i>Master Reports</h4>
                            <p class="text-muted mb-0 small">Generate and export system-wide reports</p>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3"><i class="bi bi-box-seam text-primary" style="font-size:2.5rem;"></i></div>
                                        <h5 class="fw-bold">Inventory Report</h5>
                                        <p class="text-muted small">Export current stock levels, values, and status across all branches.</p>
                                        <button class="btn btn-primary text-white fw-bold px-4"
                                            data-bs-toggle="modal" data-bs-target="#inventoryReportsModal">
                                            <i class="bi bi-download me-2"></i>Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3"><i class="bi bi-receipt text-success" style="font-size:2.5rem;"></i></div>
                                        <h5 class="fw-bold">Sales Report</h5>
                                        <p class="text-muted small">Export sales transactions by date range, branch, and payment type.</p>
                                        <button class="btn btn-success text-white fw-bold px-4"
                                            data-bs-toggle="modal" data-bs-target="#generateSalesReportModal">
                                            <i class="bi bi-download me-2"></i>Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3"><i class="bi bi-cash-coin text-warning" style="font-size:2.5rem;"></i></div>
                                        <h5 class="fw-bold">Payments Report</h5>
                                        <p class="text-muted small">Export payment collections and outstanding balances by branch.</p>
                                        <button class="btn btn-warning text-dark fw-bold px-4"
                                            data-bs-toggle="modal" data-bs-target="#paymentReportsModal">
                                            <i class="bi bi-download me-2"></i>Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3"><i class="bi bi-repeat text-info" style="font-size:2.5rem;"></i></div>
                                        <h5 class="fw-bold">Transfer Report</h5>
                                        <p class="text-muted small">Export global stock transfer records and their statuses.</p>
                                        <button class="btn btn-info text-dark fw-bold px-4"
                                            data-bs-toggle="modal" data-bs-target="#generateTransferReportModal">
                                            <i class="bi bi-download me-2"></i>Generate
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3"><i class="bi bi-shield-check text-secondary" style="font-size:2.5rem;"></i></div>
                                        <h5 class="fw-bold">Audit Log Report</h5>
                                        <p class="text-muted small">Export system activity and security event logs.</p>
                                        <button class="btn btn-secondary text-white fw-bold px-4" id="exportAuditLogBtn">
                                            <i class="bi bi-download me-2"></i>Export CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- =================== END MASTER REPORTS TAB =================== -->

                </div>
            </div>
        </div>
    </main>

    <div class='modal fade' id='addPartsInModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-l'>
            <div class='modal-content'>
                <form id='addPartsInForm'>
                    <div class='modal-header bg-primary text-white'>
                        <h5 class='modal-title'><i class="bi bi-box-arrow-in-down me-2"></i>Record Incoming Stock
                        </h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class='form-label'>Date Received</label>
                                <input type='date' class='form-control' id='add_date' name='date' required>
                            </div>
                            <div class="col-md-6">
                                <label class='form-label'>Invoice Number</label>
                                <input type='text' class='form-control' id='add_invoice_no' name='invoice_no'
                                    placeholder="Enter Invoice #">
                            </div>
                        </div>

                        <div class="position-relative mb-3">
                            <label class="form-label">Search Part to Add</label>
                            <input type="text" id="addPartSearchInput" class="form-control"
                                placeholder="Enter Part No. or Part Name...">
                            <div id="addPartSearchResults" class="list-group search-results-list d-none"></div>
                        </div>

                        <h6 class="border-bottom pb-2">Items to Add</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%;">Brand</th>
                                        <th style="width: 30%;">Part Details</th>
                                        <th class="text-center" style="width: 10%;">Qty</th>
                                        <th class="text-end" style="width: 20%;">Cost</th>
                                        <th class="text-end" style="width: 20%;">Selling Price</th>
                                        <th class="text-center" style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="partsToAddList">
                                    <tr id="emptyAddListRow">
                                        <td colspan="6" class="text-center text-muted p-4">Add parts using the
                                            search
                                            bar above or manual entry</td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light fw-bold" id="addStockTotals">
                                    <tr>
                                        <td colspan="2" class="text-end">TOTALS:</td>
                                        <td class="text-center text-primary fs-6" id="addTotalQty">0</td>
                                        <td class="text-end text-danger fs-6" id="addTotalCost">₱0.00</td>
                                        <td class="text-end text-success fs-6" id="addTotalPrice">₱0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class='modal-footer bg-light'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-primary px-4'>Save Stock (IN)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class='modal fade' id='editPartModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <form id='editPartForm' enctype="multipart/form-data">
                    <div class='modal-header bg-primary text-white'>
                        <h5 class='modal-title text-white'><i class="bi bi-pencil-square me-2 "></i>Edit Part
                            Details
                        </h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <input type="hidden" name="id" id="edit_part_id">
                        <div class="mb-3">
                            <label class="form-label">Part Number</label>
                            <input type="text" class="form-control bg-light" id="edit_part_no" readonly>
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
                                    <span class="input-group-text bg-light text-muted">₱</span>
                                    <input type="number" step="0.01" class="form-control bg-light text-muted" name="cost" id="edit_cost"
                                        readonly required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Selling Price</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">₱</span>
                                    <input type="number" step="0.01" class="form-control bg-light text-muted" name="price" id="edit_price"
                                        readonly required>
                                </div>
                            </div>
                            <div class="col-12 mt-1">
                                <small class="text-danger fw-bold d-block">
                                    <i class="bi bi-exclamation-circle-fill me-1"></i>To update pricing, please go to <a href="pricing_management.php" class="text-danger text-decoration-underline fw-bold">Price Management</a>.
                                </small>
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
                                <small class="text-muted">Please provide a reason if you are manually updating the
                                    stock
                                    quantity.</small>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer bg-light'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        <button type='submit' class='btn btn-primary px-4 text-white'>Save Changes</button>
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
                    <div class='modal-header bg-success text-white'>
                        <h5 class='modal-title' id='sellPartsOutModalLabel'><i class="bi bi-cart-plus me-2"></i>Record
                            New Sale</h5><button type='button' class='btn-close btn-close-white'
                            data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for='out_or_number' class='form-label'>Official Receipt No.</label>
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
                            <div class="col-md-6"><label for='out_date' class='form-label'>Date of
                                    Sale</label><input type='date' class='form-control' id='out_date' required>
                            </div>
                            <div class="col-md-6"><label for='out_transaction_type' class='form-label'>Payment
                                    Type</label><select class='form-select' id='out_transaction_type' required>
                                    <option value='cash' selected>Cash Sales</option>
                                    <option value='charge'>Charge Sales (Installment)</option>
                                </select></div>
                        </div>
                        <input type="text" id="salePartSearchInput" class="form-control mb-2"
                            placeholder="Enter Part No. or Part Name to add items...">
                        <div id="salePartSearchResults" class="list-group border rounded mb-4"
                            style="max-height: 150px; overflow-y: auto;"></div>
                        <h6 class="mb-2">Sale Items</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
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
                                <div class="d-flex justify-content-between fw-bold fs-5 text-success"><span>Grand
                                        Total:</span><span id="pos-grand-total">₱0.00</span></div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer'><button type='button' class='btn btn-secondary'
                            data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-success'><i
                                class="bi bi-check-circle me-2"></i>Confirm Sale</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class='modal fade' id='viewSaleDetailsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-primary text-white'>
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
                    <div class='modal-header bg-primary text-white'>
                        <h5 class='modal-title' id='recordPaymentModalLabel'><i class="bi bi-cash-coin me-2"></i>Record
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
                            class='btn btn-primary text-white'><i class=" text-white bi bi-check-lg me-2"></i>Record
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
                    <div class='modal-body p-4'>
                        <input type="hidden" id="edit_payment_id" name="payment_id">

                        <div class='mb-3'>
                            <label class='form-label fw-bold'>Customer Name</label>
                            <input type='text' class='form-control-plaintext fw-bold' id='edit_payment_customer'
                                readonly>
                        </div>

                        <div class='mb-3'>
                            <label for='edit_payment_receipt_no' class='form-label fw-bold text-success'>Payment
                                Receipt
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
                                    <option value="Cash" selected>Cash</option>
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


    <div class='modal fade' id='editSaleModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <form id='editSaleForm'>
                    <div class='modal-header bg-success text-white'>
                        <h5 class='modal-title'><i class="bi bi-pencil-square me-2"></i>Edit Sale Details</h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'><input type="hidden" id="edit_sale_or" name="or_number">
                        <div class='mb-3'><label class='form-label'>Customer Name</label><input type='text'
                                class='form-control' id='edit_sale_customer' name="customer_name" required></div>
                        <div class='mb-3'><label class='form-label'>Sale Date</label><input type='date'
                                class='form-control' id='edit_sale_date' name="sale_date" required></div>
                    </div>
                    <div class='modal-footer'><button type='button' class='btn btn-secondary'
                            data-bs-dismiss='modal'>Cancel</button><button type='submit' class='btn btn-success'>Update
                            Sale</button></div>
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
                <div class='modal-header bg-light'>
                    <h5 class='modal-title' id="receiptTitle"></h5><button type='button' class='btn-close'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-0' id='receiptBody'></div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary no-print'
                        data-bs-dismiss='modal'>Close</button><button type='button'
                        class='btn btn-outline-primary no-print' id="downloadPdfBtn"><i
                            class="bi bi-file-pdf me-2"></i>Download PDF</button><button type='button'
                        class='btn btn-primary no-print' onclick="window.print();"><i
                            class="bi bi-printer me-2"></i>Print</button></div>
            </div>
        </div>
    </div>
    <!-- VIEW TRANSFER MODAL -->
    <div class='modal fade' id='viewTransferDetailsModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg modal-dialog-centered'>
            <div class='modal-content border-0 shadow'>
                <div class='modal-header bg-dark text-white border-0'>
                    <h5 class='modal-title text-white fw-bold'><i class="bi bi-info-circle me-2"></i>Transfer Details</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-0' id="transferDetailsBody">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted">Loading transfer details...</p>
                    </div>
                </div>
                <div class='modal-footer border-0 bg-light p-3'>
                    <button type='button' class='btn btn-outline-secondary fw-bold px-4'
                        data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-outline-danger d-none fw-bold px-4' id="rejectTransferBtn">Reject Transfer</button>
                    <button type='button' class='btn btn-success d-none fw-bold px-4' id="confirmReceiveBtn">Receive Items</button>
                    <button type='button' class='btn btn-outline-warning d-none fw-bold px-4' id="cancelTransferBtn">Cancel Transfer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- REGISTER NEW PART MODAL -->
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

    <!-- UPDATE PART MODAL -->
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
                                                <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold bg-light text-muted" name="cost" id="edit_cost" readonly required>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Store Selling Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 text-success fw-bold">₱</span>
                                                <input type="number" step="0.01" class="form-control border-start-0 ps-0 fw-bold text-success bg-light" name="price" id="edit_price" readonly required>
                                            </div>
                                        </div>
                                         <div class="mt-2 pt-1 border-top">
                                             <span class="text-danger fw-bold d-block" style="font-size: 0.7rem; line-height: 1.25;">
                                                 <i class="bi bi-exclamation-circle-fill me-1"></i>To update pricing, please go to <a href="pricing_management.php" class="text-danger text-decoration-underline fw-bold">Price Management</a>.
                                             </span>
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='modal-footer border-0 bg-white py-3 px-4'>
                        <button type='button' class='btn btn-light px-4 fw-bold' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-primary px-5 fw-bold shadow-sm'>
                            <i class="bi bi-check-circle me-1"></i>Update Part
                        </button>
                    </div>
                </form>
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

    <!-- Bulk Price Upload Modal -->
    <div class='modal fade' id='bulkPriceUploadModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-lg'>
            <div class='modal-content'>
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-file-earmark-excel me-2"></i>Bulk Price Update</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div id="uploadView">
                        <div class="alert alert-info small">
                            <h6 class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i>Excel Format Instructions:</h6>
                            <p class="mb-0">Your Excel file must contain a header row with at least these columns: <strong>part_no</strong>, <strong>cost</strong>, and <strong>price</strong>. Existing parts will be updated based on matching <strong>part_no</strong>.</p>
                        </div>
                        <form id="bulkPriceForm" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="bulkExcelFile" class="form-label fw-bold">Select Excel File (.xlsx, .xls, .csv)</label>
                                <input type="file" class="form-control" id="bulkExcelFile" name="excel_file" accept=".xlsx, .xls, .csv" required>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-eye me-1"></i>Preview Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <div id="previewView" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-1 text-success">Update Preview List</h6>
                                <p class="small text-muted mb-0" id="previewSummaryText">Review changes before importing.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnBackToUpload"><i class="bi bi-arrow-left me-1"></i>Back</button>
                        </div>
                        <div class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
                            <table class="table table-sm table-bordered align-middle table-hover small">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Part No</th>
                                        <th>Description</th>
                                        <th class="text-end">Current Cost</th>
                                        <th class="text-end text-primary">New Cost</th>
                                        <th class="text-end">Current Price</th>
                                        <th class="text-end text-success">New Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkPreviewTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-success fw-bold" id="btnConfirmBulkUpdate"><i class="bi bi-check-circle me-1"></i>Apply Updates</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='inventoryBatchPreviewModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-xl'>
            <div class='modal-content'>
                <div class='modal-header bg-dark text-white'>
                    <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i>Print/Export Preview</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold mb-1">Refine Preview List</h6>
                            <p class="small text-muted mb-0">Review the items below before finalized printing or
                                exporting.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="text" id="previewSearch" class="form-control form-control-sm"
                                placeholder="Filter preview..." style="width: 200px;">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-layout-three-columns me-1"></i>Columns
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end p-3" id="previewColumnToggles"
                                    style="min-width: 200px;">
                                    <li>
                                        <div class="form-check"><input class="form-check-input col-toggle"
                                                type="checkbox" value="0" checked id="col0"><label
                                                class="form-check-label" for="col0">Part No</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input col-toggle"
                                                type="checkbox" value="1" checked id="col1"><label
                                                class="form-check-label" for="col1">Part Name</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input col-toggle"
                                                type="checkbox" value="2" checked id="col2"><label
                                                class="form-check-label" for="col2">Stock</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input col-toggle"
                                                type="checkbox" value="3" checked id="col3"><label
                                                class="form-check-label" for="col3">Price</label></div>
                                    </li>
                                    <li>
                                        <div class="form-check"><input class="form-check-input col-toggle"
                                                type="checkbox" value="4" checked id="col4"><label
                                                class="form-check-label" for="col4">Total Value</label></div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                        <table class="table table-sm table-bordered align-middle" id="previewTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Part No</th>
                                    <th>Part Name</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total Value</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody"></tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end">TOTALS:</td>
                                    <td class="text-center" id="previewTotalQty">0</td>
                                    <td></td>
                                    <td class="text-end text-primary" id="previewTotalValue">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class='modal-footer bg-light'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    <button type='button' class='btn btn-success' id="finalizeExportBtn">
                        <i class="bi bi-file-earmark-excel me-2"></i>Download CSV
                    </button>
                    <button type='button' class='btn btn-primary' id="finalizePrintBtn">
                        <i class="bi bi-printer me-2"></i>Print PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- STANDARD REPORT MODALS (Unifying with Head Office) -->
        <div class='modal fade' id='inventoryReportsModal' tabindex='-1' aria-hidden='true'>
            <div class='modal-dialog'>
                <div class='modal-content'>
                    <div class='modal-header bg-dark text-white'>
                        <h5 class='modal-title'><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Inventory
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
                                <input type="month" class="form-control border-0 bg-light fw-bold"
                                    id="inv_report_month">
                            </div>
                            <div class="mb-3 d-none" id="inv_report_year_container">
                                <label class="form-label fw-bold small text-muted text-uppercase">Select
                                    Year</label>
                                <input type="number" class="form-control border-0 bg-light fw-bold" id="inv_report_year"
                                    min="2000" max="2099">
                            </div>
                            <div class="mb-3d-none" id="inv_report_custom_container">
                                <label class="form-label fw-bold small text-muted text-uppercase">Custom
                                    Range</label>
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
                            <div class="mb-3" id="inv_report_branch_container">
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
                        <button type='button' class='btn btn-primary fw-bold px-4' id="btnGenerateReport">
                            <i class="bi bi-eye me-2"></i>Preview Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="transferReportsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white py-3">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Transfer Reports
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="transfer_generateReportForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Report
                                    Type</label>
                                <select class="form-select border-2" id="t_report_type">
                                    <option value="all">All Transfers</option>
                                    <option value="Completed">Completed Transfers</option>
                                    <option value="In-Transit">In-Transit Transfers</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Time
                                    Period</label>
                                <select class="form-select border-2" id="t_report_period">
                                    <option value="daily">Daily Report</option>
                                    <option value="monthly">Monthly Report</option>
                                    <option value="yearly">Yearly Report</option>
                                </select>
                            </div>
                            <div id="t_report_date_container">
                                <input type="date" class="form-control border-2 mb-3" id="t_report_date">
                            </div>
                            <div id="t_report_month_container" class="d-none">
                                <input type="month" class="form-control border-2 mb-3" id="t_report_month">
                            </div>
                            <div id="t_report_year_container" class="d-none">
                                <input type="number" class="form-control border-2 mb-3" id="t_report_year" min="2000"
                                    max="2100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Branch</label>
                                <select class="form-select border-2" id="t_report_branch">
                                    <option value="all">All Branches</option>
                                </select>
                            </div>
                            <div class="d-grid gap-2 mt-4">
                                <button type="button" class="btn btn-primary btn-lg fw-bold"
                                    id="btnGenerateTransferReport">
                                    <i class="bi bi-eye me-2"></i>Generate Preview
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="generateSalesReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                            Sales
                            Report</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Time Period</label>
                            <select class="form-select border-0 bg-light fw-bold" id="sales_report_period" required>
                                <option value="daily">Daily Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                        </div>
                        <div class="mb-3" id="sales_report_date_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Date</label>
                            <input type="date" class="form-control border-0 bg-light fw-bold" id="sales_report_date"
                                required>
                        </div>
                        <div class="mb-3 d-none" id="sales_report_month_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Month/Year</label>
                            <input type="month" class="form-control border-0 bg-light fw-bold" id="sales_report_month">
                        </div>
                        <div class="mb-3 d-none" id="sales_report_year_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Year</label>
                            <input type="number" class="form-control border-0 bg-light fw-bold" id="sales_report_year"
                                min="2000" max="2099">
                        </div>
                        <div class="mb-3">
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
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary fw-bold px-4" id="btnGenerateSalesReport">
                            <i class="bi bi-eye me-2"></i>Preview Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="paymentReportsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-bar-graph me-2"></i>Generate
                            Payments Report</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
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
                            <label class="form-label fw-bold small text-muted text-uppercase">Select
                                Month/Year</label>
                            <input type="month" class="form-control border-0 bg-light fw-bold"
                                id="payment_report_month">
                        </div>
                        <div class="mb-3 d-none" id="payment_report_year_container">
                            <label class="form-label fw-bold small text-muted text-uppercase">Select Year</label>
                            <input type="number" class="form-control border-0 bg-light fw-bold" id="payment_report_year"
                                min="2000" max="2099">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Branch</label>
                            <select class="form-select border-0 bg-light fw-bold" id="payment_report_branch">
                                <option value="all">All Branches</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary fw-bold px-4" id="btnGeneratePaymentReport">
                            <i class="bi bi-eye me-2"></i>Preview Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class='modal fade' id='inventoryPreviewModal' tabindex='-1' aria-hidden='true'>
            <div class='modal-dialog modal-xl'>
                <div class='modal-content'>
                    <div class='modal-header bg-dark text-white'>
                        <h5 class='modal-title text-white'><i class="bi bi-eye me-2"></i> Report Preview</h5>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body p-4'>
                        <!-- PRINT HEADER (Hidden on Screen) -->
                        <div class="print-only d-none mb-4">
                            <div class="text-center">
                                <h4 class="fw-bold mb-0">ROXAS CITY SOLID MERCHANDISING</h4>
                                <p class="small mb-0">1031 Victoria Bldg.,Roxas Avenue, Roxas City, Capiz</p>
                                <div style="border-bottom: 2px solid #333; margin-bottom: 20px;"></div>
                                <h5 class="text-uppercase fw-bold mb-1" id="printReportTitleDisplay">OFFICIAL REPORT
                                </h5>
                                <p class="small text-muted">Date Generated: <?php echo date('F d, Y h:i A'); ?></p>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                            <div>
                                <h6 class="fw-bold mb-1" id="inventoryPreviewSubtitle">Report Details</h6>
                                <p class="small text-muted mb-0">Review the records below before finalized printing
                                    or
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
                                    <table class="table table-sm table-bordered align-middle"
                                        id="inventoryPreviewTable">
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

                        <!-- PRINT FOOTER / SIGNATURES -->
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
                        </div>
                    </div>
                    <div class='modal-footer bg-light border-top p-3 text-white'>
                        <button type='button' class='btn btn-secondary fw-bold px-4'
                            data-bs-dismiss='modal'>Close</button>
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
                                        Excel</a></li>
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

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>

        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

        <script>
            window.canDelete = <?php echo json_encode($canDelete); ?>;
            window.currentBranch = '<?php echo htmlspecialchars($currentBranch); ?>';
        </script>
        <script src="../js/spareparts_stock_card.js?v=<?php echo time(); ?>"></script>
        <script src='../js/spareparts_inventory.js?v=<?php echo time(); ?>'></script>
        <script src='../js/sales_spareparts.js?v=<?php echo time(); ?>'></script>


    <!-- SHARED UTILITY MODALS -->
    <div class='modal fade' id='successModal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-success text-white'>
                    <h5 class='modal-title text-white'>Success</h5><button type='button' class='btn-close btn-close-white'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body'>
                    <p id='successMessage' class="p-3 mb-0"></p>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-success px-4'
                        data-bs-dismiss='modal'>OK</button></div>
            </div>
        </div>
    </div>

    <div class='modal fade' id='errorModal' tabindex='-1'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header bg-danger text-white'>
                    <h5 class='modal-title text-white'>Error</h5><button type='button' class='btn-close btn-close-white'
                        data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body text-danger'>
                    <p id='errorMessage' class="p-3 mb-0 fw-bold"></p>
                </div>
                <div class='modal-footer'><button type='button' class='btn btn-secondary'
                        data-bs-dismiss='modal'>Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 2100;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="bi bi-question-circle text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted mb-0" id="confirmModalMessage">Do you really want to proceed with this action?</p>
                </div>
                <div class="modal-footer border-0 p-3 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" id="confirmModalNext">Confirm</button>
                </div>
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

    <!-- MANAGE SALES FORCE MODAL -->
    <div class='modal fade' id='manageSalesForceModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-md modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-success text-white border-0 rounded-top'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-person-badge me-2"></i>Manage Employees</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <div class='modal-body p-4'>
                    <div class="card border-0 bg-light rounded-3 mb-4 shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-plus me-2 text-success"></i>Add Employee</h6>
                            <form id="addSalesForceForm">
                                <div class="row g-2">
                                    <div class="col-md-7">
                                        <input type="text" id="sf_employee_name" class="form-control shadow-sm" placeholder="Full Name..." required autocomplete="off">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" id="sf_position" class="form-control shadow-sm" placeholder="Position..." autocomplete="off">
                                    </div>
                                    <div class="col-12 text-end mt-2">
                                        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm"><i class="bi bi-plus-lg me-1"></i>Add Staff</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-people me-2 text-success"></i>Branch Employees</h6>
                    <div id="salesForceListContainer" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm text-success me-2"></div> Loading...
                        </div>
                    </div>
                </div>
                <div class='modal-footer border-0'>
                    <button type='button' class='btn btn-secondary px-4' data-bs-dismiss='modal'>Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT EMPLOYEE MODAL -->
    <div class='modal fade' id='editEmployeeModal' tabindex='-1' aria-hidden='true'>
        <div class='modal-dialog modal-sm modal-dialog-centered'>
            <div class='modal-content border-0 shadow-lg'>
                <div class='modal-header bg-dark text-white border-0'>
                    <h5 class='modal-title fw-bold text-white'><i class="bi bi-pencil-square me-2"></i>Edit Employee</h5>
                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                </div>
                <form id="editEmployeeForm">
                    <input type="hidden" id="edit_sf_id">
                    <div class='modal-body p-4'>
                        <div class="mb-3">
                            <label class="form-label x-small fw-bold text-muted text-uppercase">Employee Name</label>
                            <input type="text" id="edit_sf_name" class="form-control fw-bold" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label x-small fw-bold text-muted text-uppercase">Position</label>
                            <input type="text" id="edit_sf_position" class="form-control" placeholder="e.g. Sales, Mechanic...">
                        </div>
                    </div>
                    <div class='modal-footer border-0 pt-0'>
                        <button type='button' class='btn btn-light px-3' data-bs-dismiss='modal'>Cancel</button>
                        <button type='submit' class='btn btn-dark px-4 fw-bold'>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>